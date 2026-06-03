<?php
session_start();
require_once 'db.php';

// 1. Strict Authentication Check
if (!isset($_SESSION['user_id'])) {
    die("Error: Session expired or account unauthenticated. Please log in again.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $created_by = $_SESSION['user_id'];

    $org_query = "SELECT o.id, o.department_id
                  FROM organizations o
                  WHERE o.main_admin_id = ?
                  UNION
                  SELECT o.id, o.department_id
                  FROM organization_admins oa
                  JOIN organizations o ON oa.organization_id = o.id
                  WHERE oa.user_id = ?
                  LIMIT 1";
    $org_stmt = $conn->prepare($org_query);
    $org_stmt->bind_param('ii', $created_by, $created_by);
    $org_stmt->execute();
    $org_res = $org_stmt->get_result();
    $org_info = $org_res->fetch_assoc();

    if (!$org_info) {
        die("Error: User account is not associated with an authorized active organization.");
    }

    $organization_id = intval($org_info['id']);
    $organization_department_id = intval($org_info['department_id']);

    // 2. Capture and Clean Form Data
    $title = isset($_POST['event_name']) ? trim($_POST['event_name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $venue = isset($_POST['location']) ? trim($_POST['location']) : '';
    $visibility = isset($_POST['visibility_type']) ? $_POST['visibility_type'] : 'public'; 
    $ticket_price = isset($_POST['ticket_price']) ? floatval($_POST['ticket_price']) : 0.00;
    $require_approval = isset($_POST['require_approval']) ? 1 : 0;
    $selected_departments = isset($_POST['selected_departments']) ? trim($_POST['selected_departments']) : '';

    if ($visibility === 'restricted' && $selected_departments === '') {
        die('Error: Please select at least one department for restricted visibility.');
    }
    if ($visibility === 'department_only' && $organization_department_id <= 0) {
        die('Error: Organization department not found for department_only visibility.');
    }

    // Handle Capacity Values safely (Treating NULL cleanly for MySQL)
    $limit_capacity = isset($_POST['limit_capacity']) ? $_POST['limit_capacity'] : "0";
    $capacity = ($limit_capacity === "1" && !empty($_POST['max_capacity']) && $_POST['max_capacity'] !== 'null') ? intval($_POST['max_capacity']) : null;

    // 3. Normalize Date Formatting
    $start_date = $_POST['start_date'] ?? ''; 
    $start_time = $_POST['start_time'] ?? ''; 
    $end_date = $_POST['end_date'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    if (empty($start_date) || empty($end_date)) {
        die("Error: Start and End dates are required.");
    }

    $start_datetime = date('Y-m-d H:i:s', strtotime("$start_date $start_time"));
    $end_datetime = date('Y-m-d H:i:s', strtotime("$end_date $end_time"));

    // 4. Secure File Stream Target Directories
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
            die("Error: Failed to create uploads directory. Check server permissions.");
        }
    }

    $event_banner = null;
    $cover_photo = null;

    // Process Event Banner Upload
    if (isset($_FILES['event_banner']) && $_FILES['event_banner']['error'] === UPLOAD_ERR_OK) {
        $file_ext = pathinfo($_FILES['event_banner']['name'], PATHINFO_EXTENSION);
        $banner_name = "banner_" . uniqid() . "." . $file_ext;
        if (move_uploaded_file($_FILES['event_banner']['tmp_name'], $target_dir . $banner_name)) {
            $event_banner = $target_dir . $banner_name;
        }
    }

    // Process Cover Photo Upload
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
        $file_ext = pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION);
        $cover_name = "cover_" . uniqid() . "." . $file_ext;
        if (move_uploaded_file($_FILES['cover_photo']['tmp_name'], $target_dir . $cover_name)) {
            $cover_photo = $target_dir . $cover_name;
        }
    }

    // 5. Execute Main Event Entry Injection with transaction and NULL-capacity handling
    $conn->begin_transaction();
    try {
        // sanitize visibility, including new 'private' option
        $allowedVis = ['public','private','department_only','restricted'];
        if (!in_array($visibility, $allowedVis)) $visibility = 'public';

        if ($capacity === null) {
            $sql = "INSERT INTO events (
                        organization_id, created_by, title, description, event_banner,
                        cover_photo, venue, visibility, start_datetime, end_datetime,
                        capacity, ticket_price, require_approval, status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, 'published', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception('SQL Prepare Error: ' . $conn->error);

            $types = 'iissssssssdi'; // org_id, created_by, title, desc, banner, cover, venue, visibility, start, end, ticket_price (d), require_approval (i)
            if (!$stmt->bind_param($types,
                $organization_id, $created_by, $title, $description, $event_banner,
                $cover_photo, $venue, $visibility, $start_datetime, $end_datetime,
                $ticket_price, $require_approval)) {
                throw new Exception('Bind Param Failed: ' . $stmt->error);
            }
        } else {
            $sql = "INSERT INTO events (
                        organization_id, created_by, title, description, event_banner,
                        cover_photo, venue, visibility, start_datetime, end_datetime,
                        capacity, ticket_price, require_approval, status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception('SQL Prepare Error: ' . $conn->error);

            $types = 'iissssssssidi'; // org_id, created_by, title, desc, banner, cover, venue, visibility, start, end, capacity (i), ticket_price (d), require_approval (i)
            if (!$stmt->bind_param($types,
                $organization_id, $created_by, $title, $description, $event_banner,
                $cover_photo, $venue, $visibility, $start_datetime, $end_datetime,
                $capacity, $ticket_price, $require_approval)) {
                throw new Exception('Bind Param Failed: ' . $stmt->error);
            }
        }

        if (!$stmt->execute()) {
            throw new Exception('Execute Failed: ' . $stmt->error);
        }

        $event_id = $conn->insert_id;

        // 6. Process Visibility Relational Mapping
        if ($visibility === 'restricted') {
            $department_ids = array_filter(array_map('intval', explode(',', $selected_departments)));
            if (empty($department_ids)) {
                throw new Exception('Restricted visibility requires selected departments.');
            }
            $dep_sql = "INSERT INTO event_departments (event_id, department_id) VALUES (?, ?)";
            $dep_stmt = $conn->prepare($dep_sql);
            if (!$dep_stmt) throw new Exception('Prepare event_departments failed: ' . $conn->error);
            foreach ($department_ids as $dept_id) {
                $dep_stmt->bind_param('ii', $event_id, $dept_id);
                if (!$dep_stmt->execute()) throw new Exception('Insert event_departments failed: ' . $dep_stmt->error);
            }
        } elseif ($visibility === 'department_only') {
            if ($organization_department_id > 0) {
                $dep_sql = "INSERT INTO event_departments (event_id, department_id) VALUES (?, ?)";
                $dep_stmt = $conn->prepare($dep_sql);
                if (!$dep_stmt) throw new Exception('Prepare event_departments failed: ' . $conn->error);
                $dep_stmt->bind_param('ii', $event_id, $organization_department_id);
                if (!$dep_stmt->execute()) throw new Exception('Insert event_departments failed: ' . $dep_stmt->error);
            }
        }

        $conn->commit();
        header("Location: manage.php?created=1");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        // Log error and show a concise message
        error_log('process.event.php error: ' . $e->getMessage());
        die('An error occurred while creating the event. Check server logs.');
    }
} else {
    die("Invalid Request Method.");
}
?>