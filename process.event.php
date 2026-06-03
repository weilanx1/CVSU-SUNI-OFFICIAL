<?php
session_start();
require_once 'db.php';

// 1. Strict Authentication Check
if (!isset($_SESSION['user_id'])) {
    die("Error: Session expired or account unauthenticated. Please log in again.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $created_by = $_SESSION['user_id'];
    $organization_id = !empty($_POST['organization_id']) ? intval($_POST['organization_id']) : null;

    if (!$organization_id) {
        die("Error: User account is not associated with an authorized active organization.");
    }

    // 2. Capture and Clean Form Data
    $title = isset($_POST['event_name']) ? trim($_POST['event_name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $venue = isset($_POST['location']) ? trim($_POST['location']) : '';
    $visibility = isset($_POST['visibility_type']) ? $_POST['visibility_type'] : 'public'; 
    $ticket_price = isset($_POST['ticket_price']) ? floatval($_POST['ticket_price']) : 0.00;
    $require_approval = isset($_POST['require_approval']) ? 1 : 0;

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

    // 5. Execute Main Event Entry Injection
    $sql = "INSERT INTO events (
                organization_id, created_by, title, description, event_banner, 
                cover_photo, venue, visibility, start_datetime, end_datetime, 
                capacity, ticket_price, require_approval, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare Error: " . $conn->error);
    }

    // Using type 'z' or passing variables carefully. Since capacity can be NULL, 
    // we use a ternary check or bind it safely. MySQLi bind_param handles native PHP nulls well if the type matches.
    $stmt->bind_param(
        'iissssssssidi',
        $organization_id, $created_by, $title, $description, $event_banner,
        $cover_photo, $venue, $visibility, $start_datetime, $end_datetime,
        $capacity, $ticket_price, $require_approval
    );

    if ($stmt->execute()) {
        $event_id = $conn->insert_id;

        // 6. Process Visibility Relational Mapping
        if ($visibility === 'restricted' && !empty($_POST['selected_departments'])) {
            $department_ids = explode(',', $_POST['selected_departments']);
            $dep_sql = "INSERT INTO event_departments (event_id, department_id) VALUES (?, ?)";
            $dep_stmt = $conn->prepare($dep_sql);

            if ($dep_stmt) {
                foreach ($department_ids as $dept_id) {
                    $dept_id_int = intval($dept_id);
                    $dep_stmt->bind_param('ii', $event_id, $dept_id_int);
                    $dep_stmt->execute();
                }
            }
        } 
        elseif ($visibility === 'department_only') {
            $dept_lookup = "SELECT department_id FROM organizations WHERE id = ? LIMIT 1";
            $lookup_stmt = $conn->prepare($dept_lookup);
            if ($lookup_stmt) {
                $lookup_stmt->bind_param('i', $organization_id);
                $lookup_stmt->execute();
                $lookup_res = $lookup_stmt->get_result()->fetch_assoc();

                if ($lookup_res && !empty($lookup_res['department_id'])) {
                    $org_dept_id = intval($lookup_res['department_id']);
                    $dep_sql = "INSERT INTO event_departments (event_id, department_id) VALUES (?, ?)";
                    $dep_stmt = $conn->prepare($dep_sql);
                    if ($dep_stmt) {
                        $dep_stmt->bind_param('ii', $event_id, $org_dept_id);
                        $dep_stmt->execute();
                    }
                }
            }
        }

        // Clean redirection
        header("Location: dashboard.php?status=success");
        exit;
    } else {
        // Output the precise database error preventing insertion
        die("Database Execution Error: " . $stmt->error . " (Code: " . $stmt->errno . ")");
    }
} else {
    die("Invalid Request Method.");
}
?>