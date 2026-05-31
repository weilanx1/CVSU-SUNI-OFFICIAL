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
        
        <a href="#">
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

    <div class="profile-content-container">
      <div class="profile-inner-card">
        
        <header class="profile-header">
          <h1 class="profile-title">Organization Profile</h1>
          <div class="profile-sub-nav">
            <a href="#" class="sub-nav-item active">Account</a>
          </div>
        </header>

        <section class="profile-form-section">
          <h2 class="form-section-title">Profile</h2>
          
          <form id="orgProfileForm" action="" method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
              <label class="input-label">Organization Profile Picture</label>
              <div class="avatar-uploader">
                <div class="avatar-display">
                  <img id="profileDisplayImage" src="images/logocsg.png" alt="Organization Logo">
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
                value="Central Student Government" 
                required
              >
            </div>

            <div class="form-group">
              <label class="input-label">College Department</label>
              <p class="form-static-value">Office of Student Affairs and Services</p>
            </div>

            <div class="form-submit-row">
              <button type="submit" class="btn-save-profile">
                <i class="fa-solid fa-user-plus"></i> Save Changes
              </button>
            </div>
            
            <p class="form-system-notice">Changes to your name or profile picture are applied across SUNI.</p>

          </form>
        </section>

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