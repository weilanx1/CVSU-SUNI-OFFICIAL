<?php
session_start();
require_once 'db.php';

// Siguraduhin na may laman ang session para sa user details
// Kung wala pa, maaari mong i-fetch dito
$user_name = $_SESSION['user_name'] ?? 'User Name';
$user_email = $_SESSION['user_email'] ?? 'email@example.com';

$default_avatar = 'images/person3.png';
$profile_picture = $default_avatar;

if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('SELECT profile_picture FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && !empty($user['profile_picture'])) {
        $db_path = $user['profile_picture'];
        if (file_exists($db_path) && is_file($db_path)) {
            $profile_picture = htmlspecialchars($db_path);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Organization Dashboard</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  
  <link rel="stylesheet" href="css/dashboard.css"/>
  <link rel="stylesheet" href="css/org-navbar.css">
  <link rel="stylesheet" href="css/sidebar.css"/>

  <style>
    /* I-paste dito ang CSS para sa Dropdown para siguradong gagana */
    .profile-dropdown-wrapper { position: relative; display: inline-block; }
    .profile-dropdown-menu {
        display: none; position: absolute; right: 0; top: 50px;
        background-color: #ffffff; min-width: 260px;
        box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
        border-radius: 12px; z-index: 9999; padding: 15px 0;
        border: 1px solid #e2e8f0; font-family: 'Poppins', sans-serif;
    }
    .profile-dropdown-menu.active { display: block; }
    .dropdown-user-details { padding: 0 20px 15px 20px; }
    .dropdown-user-details p { margin: 0; font-size: 14px; font-weight: 700; color: #1e293b; }
    .dropdown-user-details span { font-size: 13px; color: #64748b; }
    .profile-dropdown-menu hr { border: 0; height: 1px; background-color: #f1f5f9; margin: 5px 0; }
    .profile-dropdown-menu a {
        color: #334155 !important; padding: 10px 20px; text-decoration: none !important;
        display: flex !important; align-items: center; font-size: 14px;
    }
    .profile-dropdown-menu a:hover { background-color: #f8fafc; }
    .profile-dropdown-menu a.sign-out-item { color: #ef4444 !important; margin-top: 5px; }
    .profile-dropdown-menu a.sign-out-item:hover { background-color: #fef2f2 !important; color: #dc2626 !important; }
  </style>
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-top">
      <div class="menu">
        <img src="images/logo.png" class="sidebar-logo">
        <a href="dashboard.php" class="active"><img src="images/dashboard.png" class="sidebar-icon"> Dashboard</a>
        <a href="manage.php"><img src="images/manageevent.png" class="sidebar-icon"> Manage Events</a>
        <a href="org-profile.php"><img src="images/person2.png" class="sidebar-icon"> Profile</a>
        <a href="#"><img src="images/analytics.png" class="sidebar-icon"> Analytics</a>
        <a href="permission-control.php"><img src="images/permission.png" class="sidebar-icon"> Permission Control</a>
        <a href="#"><img src="images/settings.png" class="sidebar-icon"> Settings</a>
      </div>
    </div>
    <div class="sidebar-bottom">
      <button class="org-btn">View Organization Page <i class="fa-solid fa-arrow-up-right-from-square"></i></button>
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
                <li><a href="#">My Profile</a></li>
                <li><a href="dashboard.php" class="active">Organization Dashboard</a></li>
                <li class="nav-icons">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <i class="fa-regular fa-bell fa-lg"></i>
                </li>
                <li><img src="<?php echo $profile_picture; ?>" class="profile"></li>
            </ul>
        </div>
    </nav>
  <script>
    // JS para sa Dropdown toggle
    const profileBtn = document.getElementById('profileBtn');
    const profileMenu = document.getElementById('profileMenu');

    profileBtn.addEventListener('click', function(e) {
        profileMenu.classList.toggle('active');
        e.stopPropagation();
    });

    document.addEventListener('click', function(e) {
        if (!profileMenu.contains(e.target) && e.target !== profileBtn) {
            profileMenu.classList.remove('active');
        }
    });
  </script>
</body>
</html>