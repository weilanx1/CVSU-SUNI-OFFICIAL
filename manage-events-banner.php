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
// Determine user's organization and load event
$org_id = null;
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
  $oq = "SELECT o.id FROM organizations o WHERE o.main_admin_id = ? UNION SELECT o.id FROM organization_admins oa JOIN organizations o ON oa.organization_id = o.id WHERE oa.user_id = ? LIMIT 1";
  $os = $conn->prepare($oq);
  $os->bind_param('ii', $user_id, $user_id);
  $os->execute();
  $or = $os->get_result();
  if ($row = $or->fetch_assoc()) $org_id = $row['id'];
}

$event = null;
if (isset($_GET['event_id']) && $org_id) {
  $eid = intval($_GET['event_id']);
  $est = $conn->prepare('SELECT * FROM events WHERE id = ? AND organization_id = ? LIMIT 1');
  $est->bind_param('ii', $eid, $org_id);
  $est->execute();
  $er = $est->get_result();
  $event = $er->fetch_assoc();
}

// Handle uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && $org_id) {
  $eid = intval($_POST['event_id']);
  // verify
  $vst = $conn->prepare('SELECT id FROM events WHERE id = ? AND organization_id = ? LIMIT 1');
  $vst->bind_param('ii', $eid, $org_id);
  $vst->execute();
  $vres = $vst->get_result();
  if (!$vres->fetch_assoc()) die('Unauthorized');

  $target_dir = 'uploads/';
  if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

  $event_banner = null;
  $cover_photo = null;

  if (isset($_FILES['event_banner']) && $_FILES['event_banner']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['event_banner']['name'], PATHINFO_EXTENSION);
    $fn = 'banner_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($_FILES['event_banner']['tmp_name'], $target_dir . $fn)) {
      $event_banner = $target_dir . $fn;
    }
  }
  if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION);
    $fn = 'cover_' . uniqid() . '.' . $ext;
    if (move_uploaded_file($_FILES['cover_photo']['tmp_name'], $target_dir . $fn)) {
      $cover_photo = $target_dir . $fn;
    }
  }

  if ($event_banner || $cover_photo) {
    $parts = [];
    $params = [];
    $types = '';
    if ($event_banner) { $parts[] = 'event_banner = ?'; $params[] = $event_banner; $types .= 's'; }
    if ($cover_photo) { $parts[] = 'cover_photo = ?'; $params[] = $cover_photo; $types .= 's'; }
    $sql = 'UPDATE events SET ' . implode(', ', $parts) . ', updated_at = CURRENT_TIMESTAMP WHERE id = ?';
    $params[] = $eid; $types .= 'i';
    $upd = $conn->prepare($sql);
    $upd->bind_param($types, ...$params);
    if ($upd->execute()) {
      $upload_success = true;
      // reload event so UI reflects new image paths
      $est = $conn->prepare('SELECT * FROM events WHERE id = ? AND organization_id = ? LIMIT 1');
      $est->bind_param('ii', $eid, $org_id);
      $est->execute();
      $er = $est->get_result();
      $event = $er->fetch_assoc();
    } else {
      $upload_error = 'Failed to save images.';
    }
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
      <a href="manage-events.php?event_id=<?php echo $event ? intval($event['id']) : ''; ?>" class="tab-item">Details</a>
      <a href="manage-events-banner.php?event_id=<?php echo $event ? intval($event['id']) : ''; ?>" class="tab-item active">Banner</a>
      <a href="manage-events-guest.php?event_id=<?php echo $event ? intval($event['id']) : ''; ?>" class="tab-item">Guest</a>
      <a href="manage-events-registration.php?event_id=<?php echo $event ? intval($event['id']) : ''; ?>" class="tab-item">Registration</a>
    </div>

    <!-- Form container area ready for image upload operations -->
    <form action="" method="POST" enctype="multipart/form-data">
      <?php if (!empty($upload_success)): ?>
        <div style="background:#e6ffef; color:#044d22; padding:12px; border-radius:6px; margin-bottom:12px;">Images updated successfully.</div>
      <?php endif; ?>
      <?php if (!empty($upload_error)): ?>
        <div style="background:#fff0f0; color:#8b1d1d; padding:12px; border-radius:6px; margin-bottom:12px;"><?php echo htmlspecialchars($upload_error); ?></div>
      <?php endif; ?>
      <div class="manage-card">
        
        <div class="banner-grid">
          
          <!-- Kaliwang Bahagi: Update Event Image/Banner (Square Layout) -->
          <div class="banner-column event-image">
            <div class="banner-title">Update Event Image/Banner</div>
            <div class="banner-instruction">Upload or choose a banner for your event<br>Recommended size: 1024 × 1024px..</div>
            
            <div class="image-preview-container">
              <!-- Default Image Preview Placeholder -->
              <img id="thumbPreview" src="<?php echo $event && !empty($event['event_banner']) ? htmlspecialchars($event['event_banner']) : 'images/stardew.png'; ?>" alt="Event Thumbnail Grid Preview">
              
              <!-- Action Buttons (Naka-iwan bilang handa para sa backend operations) -->
              <label class="img-overlay-btn btn-edit-img" title="Change Image" style="cursor:pointer;">
                <input type="file" name="event_banner" accept="image/*" style="display:none;" onchange="document.getElementById('thumbPreview').src = window.URL.createObjectURL(this.files[0])">
                <i class="fa-solid fa-pen"></i>
              </label>
            </div>
          </div>

          <!-- Kanang Bahagi: Update Cover Image/Banner (Widescreen Layout) -->
          <div class="banner-column cover-image">
            <div class="banner-title">Update Cover Image/Banner</div>
            <div class="banner-instruction">Displays as the background of your public event page.<br>Recommended size: 1920 × 1080px (16:9 widescreen).</div>
            
            <div class="image-preview-container">
              <!-- Default Widescreen Image Preview Placeholder -->
              <img id="coverPreview" src="<?php echo $event && !empty($event['cover_photo']) ? htmlspecialchars($event['cover_photo']) : 'images/stardew.png'; ?>" alt="Event Widescreen Cover Preview">
              
              <!-- Action Buttons (Naka-iwan bilang handa para sa backend operations) -->
              <label class="img-overlay-btn btn-edit-img" title="Change Cover" style="cursor:pointer;">
                <input type="file" name="cover_photo" accept="image/*" style="display:none;" onchange="document.getElementById('coverPreview').src = window.URL.createObjectURL(this.files[0])">
                <i class="fa-solid fa-pen"></i>
              </label>
            </div>
          </div>

        </div>

      </div>

      <input type="hidden" name="event_id" value="<?php echo $event ? intval($event['id']) : ''; ?>">
      <!-- Action Footer Buttons Layout Block -->
      <div class="form-actions">
        <a href="manage-events.php?event_id=<?php echo $event ? intval($event['id']) : ''; ?>" class="btn btn-cancel">Cancel</a>
        <button type="submit" class="btn btn-save">Save Changes</button>
      </div>
    </form>
    <!-- Dulo ng Banner Tab Content Area -->

  </div>
</body>
</html>