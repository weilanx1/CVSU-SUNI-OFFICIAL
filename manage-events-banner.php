<?php
session_start();
require_once 'db.php';

$profile_picture = 'images/person3.png';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('SELECT profile_picture FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user && !empty($user['profile_picture'])) {
        $profile_picture = htmlspecialchars($user['profile_picture']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Organization Dashboard - Permission Control</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <link rel="stylesheet" href="css/manage-events-banner.css"/>
  <link rel="stylesheet" href="css/org-navbar.css">
  <link rel="stylesheet" href="css/sidebar.css"/>
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

        <a href="manage.php" class="active">
          <img src="images/manageevent.png" class="sidebar-icon">
          Manage Events
        </a>

        <a href="org-profile.php">
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
    <div class="contents">
    <!-- Simula ng Edit at Manage Event - Banner Content -->
    <a href="manage.php" class="back-link">
      <i class="fa-solid fa-arrow-left"></i> Back to Manage Events
    </a>

    <h2 class="page-title">Edit and Manage Event</h2>
    <p class="page-subtitle">Update your event details and settings.</p>

    <!-- Sub-navigation tabs layout -->
    <div class="content-tabs">
      <a href="manage-events.php" class="tab-item">Details</a>
      <a href="manage-events-banner.php" class="tab-item active">Banner</a>
      <a href="manage-events-guest.php" class="tab-item">Guest</a>
      <a href="#" class="tab-item">Registration</a>
    </div>

    <!-- Form container area ready for image upload operations -->
    <form action="" method="POST" enctype="multipart/form-data">
      <div class="manage-card">
        
        <div class="banner-grid">
          
          <!-- Kaliwang Bahagi: Update Event Image/Banner (Square Layout) -->
          <div class="banner-column event-image">
            <div class="banner-title">Update Event Image/Banner</div>
            <div class="banner-instruction">Upload or choose a banner for your event<br>Recommended size: 1024 × 1024px..</div>
            
            <div class="image-preview-container">
              <!-- Default Image Preview Placeholder -->
              <img src="images/stardew.png" alt="Event Thumbnail Grid Preview">
              
              <!-- Action Buttons (Naka-iwan bilang handa para sa backend operations) -->
              <button type="button" class="img-overlay-btn btn-delete-img" title="Remove Image">
                <i class="fa-solid fa-xmark"></i>
              </button>
              <button type="button" class="img-overlay-btn btn-edit-img" title="Change Image">
                <i class="fa-solid fa-pen"></i>
              </button>
            </div>
          </div>

          <!-- Kanang Bahagi: Update Cover Image/Banner (Widescreen Layout) -->
          <div class="banner-column cover-image">
            <div class="banner-title">Update Cover Image/Banner</div>
            <div class="banner-instruction">Displays as the background of your public event page.<br>Recommended size: 1920 × 1080px (16:9 widescreen).</div>
            
            <div class="image-preview-container">
              <!-- Default Widescreen Image Preview Placeholder -->
              <img src="images/stardew.png" alt="Event Widescreen Cover Preview">
              
              <!-- Action Buttons (Naka-iwan bilang handa para sa backend operations) -->
              <button type="button" class="img-overlay-btn btn-delete-img" title="Remove Cover">
                <i class="fa-solid fa-xmark"></i>
              </button>
              <button type="button" class="img-overlay-btn btn-edit-img" title="Change Cover">
                <i class="fa-solid fa-pen"></i>
              </button>
            </div>
          </div>

        </div>

      </div>

      <!-- Action Footer Buttons Layout Block -->
      <div class="form-actions">
        <button type="button" class="btn btn-cancel">Cancel</button>
        <button type="submit" class="btn btn-save">Save Changes</button>
      </div>
    </form>
    <!-- Dulo ng Banner Tab Content Area -->

  </div>
</body>
</html>