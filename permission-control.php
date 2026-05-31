<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Organization Dashboard</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<link rel="stylesheet" href="css/permissions-control.css"/>
<link rel="stylesheet" href="css/org-navbar.css">
<link rel="stylesheet" href="css/sidebar.css"/>
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-top">
      <div class="menu">

        <a href="#">
          <img src="images/logo.png" class="sidebar-logo">
        </a>
        
        <a href="#">
          <img src="images/dashboard.png" class="sidebar-icon">
          Dashboard
        </a>

        <a href="manage.php">
          <img src="images/manageevent.png" class="sidebar-icon">
          Manage Events
        </a>

        <a href="#">
          <img src="images/person2.png" class="sidebar-icon">
          Profile
        </a>

        <a href="#">
          <img src="images/analytics.png" class="sidebar-icon">
          Analytics
        </a>

        <a href="permission-control.php" class="active">
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
               <li><a href="#">+ Create Events</a></li>
                <li><a href="index.php">CvSU Events</a></li>
                <li><a href="#">My Profile</a></li>
                <li><a href="#" class="active">Organization Dashboard</a></li>
                 <li class="nav-icons">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <i class="fa-regular fa-bell fa-lg"></i>
                </li>
                <li><img src="images/sid.png" class="profile"></li>
            </ul>
        </div>
    </nav>








  </div>
   
</body>
</html>