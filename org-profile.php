<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$default_avatar = 'images/person3.png';
$profile_picture = $default_avatar;
$organization = null;
$department_name = '';
$error_msg = '';
$success_msg = '';

// Fetch user profile picture
$stmt = $conn->prepare('SELECT profile_picture FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && !empty($user['profile_picture'])) {
    $db_user_path = $user['profile_picture'];
    // Defensive physical file check
    if (file_exists($db_user_path) && is_file($db_user_path)) {
        $profile_picture = htmlspecialchars($db_user_path);
    } else {
        $profile_picture = $default_avatar;
    }
}

// Fetch organization where user is main_admin or organization admin/moderator
$org_stmt = $conn->prepare('
    SELECT o.id, o.name, o.logo, o.department_id, d.name as dept_name, o.main_admin_id
    FROM organizations o
    JOIN departments d ON o.department_id = d.id
    WHERE o.main_admin_id = ?
    LIMIT 1
');
$org_stmt->bind_param('i', $user_id);
$org_stmt->execute();
$org_result = $org_stmt->get_result();

if ($org_result->num_rows == 0) {
    // Check if user is an organization admin or moderator
    $admin_stmt = $conn->prepare('
        SELECT o.id, o.name, o.logo, o.department_id, d.name as dept_name, o.main_admin_id, oa.role
        FROM organizations o
        JOIN organization_admins oa ON o.id = oa.organization_id
        JOIN departments d ON o.department_id = d.id
        WHERE oa.user_id = ? AND oa.role IN ("admin", "moderator")
        LIMIT 1
    ');
    $admin_stmt->bind_param('i', $user_id);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    
    if ($admin_result->num_rows > 0) {
        $organization = $admin_result->fetch_assoc();
    } else {
        $error_msg = 'You do not have permission to view this page. Only organization admins or moderators can access this.';
    }
} else {
    $organization = $org_result->fetch_assoc();
    $organization['role'] = 'main_admin';
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $organization) {
    $org_id = $organization['id'];
    $org_name = trim($_POST['organization_name'] ?? '');
    $logo_path = $organization['logo']; // Keep existing logo by default
    
    // Validate organization name
    if (empty($org_name)) {
        $error_msg = 'Organization name cannot be empty.';
    } else {
        // Handle logo upload if provided
        if (isset($_FILES['org_logo']) && $_FILES['org_logo']['size'] > 0) {
            $file = $_FILES['org_logo'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowed_types)) {
                $error_msg = 'Invalid file type. Only image files are allowed.';
            } elseif ($file['size'] > 5000000) { // 5MB limit
                $error_msg = 'File size exceeds 5MB limit.';
            } else {
                $upload_dir = 'images/org-logos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_name = 'org_' . $org_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $logo_path = $file_path;
                } else {
                    $error_msg = 'Failed to upload image.';
                }
            }
        }
        
        // Update organization in database
        if (empty($error_msg)) {
            $update_stmt = $conn->prepare('UPDATE organizations SET name = ?, logo = ? WHERE id = ?');
            $update_stmt->bind_param('ssi', $org_name, $logo_path, $org_id);
            
            if ($update_stmt->execute()) {
                $success_msg = 'Organization profile updated successfully!';
                $organization['name'] = $org_name;
                $organization['logo'] = $logo_path;
            } else {
                $error_msg = 'Failed to update organization.';
            }
        }
    }
}

// Fallback logic for Organization Logo path validation
$org_logo = $default_avatar;
if ($organization && !empty($organization['logo'])) {
    $db_logo_path = $organization['logo'];
    if (file_exists($db_logo_path) && is_file($db_logo_path)) {
        $org_logo = htmlspecialchars($db_logo_path);
    }
}

$org_name = $organization ? htmlspecialchars($organization['name']) : '';
$dept_name = $organization ? htmlspecialchars($organization['dept_name']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Organization Dashboard - Profile</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <link rel="stylesheet" href="css/org-navbar.css">
  <link rel="stylesheet" href="css/sidebar.css"/>
  <link rel="stylesheet" href="css/org-profile.css"/>
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-top">
      <div class="menu">
        <img src="images/logo.png" class="sidebar-logo">
        
        <a href="dashboard.php">
          <img src="images/dashboard.png" class="sidebar-icon">
          Dashboard
        </a>

        <a href="manage.php">
          <img src="images/manageevent.png" class="sidebar-icon">
          Manage Events
        </a>

        <a href="org-profile.php" class="active">
          <img src="images/person2.png" class="sidebar-icon">
          Profile
        </a>

        <a href="#">
          <img src="images/analytics.png" class="sidebar-icon">
          Analytics
        </a>

        <a href="permission-control.php">
          <img src="images/permission.png" class="sidebar-icon">
          Permission Control
        </a>

        <a href="#">
          <img src="images/settings.png" class="sidebar-icon">
          Settings
        </a>
      </div>
    </div>

    <div class="sidebar-bottom">
      <button class="org-btn">
        View Organization Page
        <i class="fa-solid fa-arrow-up-right-from-square"></i>
      </button>
    </div>
  </div>

  <div class="main">
    <nav>
        <div class="container nav-container">
            <a href="#">
                <img src="images/logo.png" alt="Suni Logo" style="display:none;">
            </a>
            <ul>
                <li><a href="create-events.php">+ Create Events</a></li>
                <li><a href="index.php">CvSU Events</a></li>
                <li><a href="org-profile.php">My Profile</a></li>
                <li><a href="dashboard.php" class="active">Organization Dashboard</a></li>
                <li class="nav-icons">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <i class="fa-regular fa-bell fa-lg"></i>
                </li>
                <li><img src="<?php echo $profile_picture; ?>" class="profile" alt="User Profile Image"></li>
            </ul>
        </div>
    </nav>

    <div class="profile-content-container">
      <div class="profile-inner-card">
        
        <header class="profile-header">
          <h1 class="profile-title">Organization Profile</h1>
          <div class="profile-sub-nav">
            <a href="#" class="sub-nav-item active">Account</a>
          </div>
        </header>

        <?php if (!empty($error_msg)): ?>
          <div style="background-color: #fee; color: #c33; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
            <?php echo htmlspecialchars($error_msg); ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
          <div style="background-color: #efe; color: #3c3; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
            <?php echo htmlspecialchars($success_msg); ?>
          </div>
        <?php endif; ?>

        <?php if ($organization): ?>
        <section class="profile-form-section">
          <h2 class="form-section-title">Profile</h2>
          
          <form id="orgProfileForm" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
              <label class="input-label">Organization Profile Picture</label>
              <div class="avatar-uploader">
                <div class="avatar-display">
                  <img id="profileDisplayImage" src="<?php echo $org_logo; ?>" alt="Organization Logo">
                </div>
                <label for="imageUploadInput" class="avatar-upload-badge" title="Upload Image">
                  <i class="fa-solid fa-arrow-up"></i>
                </label>
                <input type="file" id="imageUploadInput" name="org_logo" accept="image/*" style="display: none;">
              </div>
            </div>

            <div class="form-group">
              <label class="input-label" for="organizationName">Organization Name</label>
              <input 
                type="text" 
                id="organizationName" 
                name="organization_name" 
                class="form-text-input" 
                value="<?php echo $org_name; ?>" 
                required
              >
            </div>

            <div class="form-group">
              <label class="input-label">College Department</label>
              <p class="form-static-value"><?php echo $dept_name; ?></p>
            </div>

            <div class="form-submit-row">
              <button type="submit" class="btn-save-profile">
                <i class="fa-solid fa-user-plus"></i> Save Changes
              </button>
            </div>
            
            <p class="form-system-notice">Changes to your organization name or profile picture are applied across SUNI.</p>

          </form>
        </section>
        <?php else: ?>
        <div style="padding: 20px; text-align: center; color: #666;">
          <p><?php echo htmlspecialchars($error_msg); ?></p>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <script>
    // Live update profile photo display upon selection
    document.getElementById('imageUploadInput').addEventListener('change', function(e) {
      if(e.target.files && e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(event) {
          document.getElementById('profileDisplayImage').src = event.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
      }
    });
  </script>
</body>
</html>