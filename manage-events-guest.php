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
  <title>Organization Dashboard - Guest Management</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  
  <link rel="stylesheet" href="css/manage-events-guest.css"/>
  <link rel="stylesheet" href="css/org-navbar.css">
  <link rel="stylesheet" href="css/sidebar.css"/>
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-top">
      <div class="menu">
        <img src="images/logo.png" class="sidebar-logo">
        <a href="dashboard.php"><img src="images/dashboard.png" class="sidebar-icon"> Dashboard</a>
        <a href="manage.php" class="active"><img src="images/manageevent.png" class="sidebar-icon"> Manage Events</a>
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
            <a href="#"><img src="images/logo.png" alt="Suni Logo" style="display:none;"></a>
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
      <a href="manage.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Manage Events</a>
      <h2 class="page-title">Edit and Manage Event</h2>
      <p class="page-subtitle">Update your event details and settings.</p>

      <div class="content-tabs">
        <a href="manage-events.php" class="tab-item">Details</a>
        <a href="manage-events-banner.php" class="tab-item">Banner</a>
        <a href="manage-events-guest.php" class="tab-item active">Guest</a>
        <a href="#" class="tab-item">Registration</a>
      </div>

      <div class="manage-card">
        <div class="glance-section">
          <h3 class="card-section-title">At a Glance</h3>
          <div class="glance-header">
            <span class="guest-counter"><strong>1</strong> guest</span>
            <span class="guest-cap">cap <strong>100</strong></span>
          </div>
          <div class="glance-progress-bar"><div class="progress-fill" style="width: 1%;"></div></div>
          <div class="glance-stats-labels">
            <span><i class="fa-solid fa-circle status-dot-black"></i> 1 Registered</span>
            <span><i class="fa-solid fa-circle status-dot-green"></i> 1 Going</span>
          </div>
          <div class="glance-buttons-grid">
            <div class="glance-widget-btn"><div class="widget-icon-box"><i class="fa-solid fa-hourglass-half"></i></div><div class="widget-text">Pending Guests</div></div>
            <div class="glance-widget-btn"><div class="widget-icon-box"><i class="fa-solid fa-users"></i></div><div class="widget-text">Check In Guests</div></div>
            <div class="glance-widget-btn"><div class="widget-icon-box"><i class="fa-regular fa-clipboard"></i></div><div class="widget-text">Guest List</div></div>
          </div>
        </div>

        <hr class="section-divider">

        <div class="guest-list-section">
          <h3 class="card-section-title">Guest List</h3>
          <div class="search-filter-row">
            <div class="search-wrapper">
              <i class="fa-solid fa-magnifying-glass search-icon"></i>
              <input type="text" placeholder="Search Guests.." class="search-input">
            </div>
          </div>
          <div class="filter-actions-row">
            <div class="filter-tabs"><button class="filter-btn active">All Guests</button></div>
            <button class="action-trigger-btn">Guest Actions</button>
          </div>

          <div class="guest-list-container">
            
            <div class="guest-row-item" data-avatar="images/sid.png">
              <div class="guest-profile-meta">
                <img src="images/sid.png" class="guest-avatar" alt="Avatar">
                <div class="guest-info">
                  <div class="guest-name">Kraig Friel B. Gonzales</div>
                  <div class="guest-email">kraigfriel.gonzales@cvsu.edu.ph</div>
                </div>
              </div>
              <div class="guest-actions-cell">
                <button class="btn-status btn-approve">Approve <i class="fa-solid fa-check"></i></button>
                <button class="btn-status btn-decline">Decline</button>
                <button class="btn-more-options"><i class="fa-solid fa-ellipsis"></i></button>
              </div>
            </div>

            <div class="guest-row-item" data-avatar="images/sid.png">
              <div class="guest-profile-meta">
                <img src="images/sid.png" class="guest-avatar" alt="Avatar">
                <div class="guest-info">
                  <div class="guest-name">Michael Chang</div>
                  <div class="guest-email">michael.chang@cvsu.edu.ph</div>
                </div>
              </div>
              <div class="guest-actions-cell">
                <span class="status-badge badge-going">Going</span>
                <span class="status-timestamp">Yesterday</span>
                <button class="btn-more-options"><i class="fa-solid fa-ellipsis"></i></button>
              </div>
            </div>

          </div> </div>
      </div> </div> </div> <div id="statusModal" class="modal">
    <div class="modal-card">
      <button class="modal-close-btn">&times;</button>
      
      <div class="modal-profile-header">
        <img src="images/sid.png" id="modalAvatar" class="modal-large-avatar">
        <h4 id="modalName">Kraig Friel B. Gonzales</h4>
        <p id="modalEmail">kraigfriel.gonzales@cvsu.edu.ph</p>
      </div>

      <div class="modal-body-form">
        <label class="modal-label">Change status to:</label>
        
        <div class="custom-dropdown-wrapper">
          <select id="statusSelect" class="modal-select">
            <option value="going">Going</option>
            <option value="not-going">Not Going</option>
            <option value="pending" selected>Pending</option>
            <option value="waitlist">Waitlist</option>
          </select>
        </div>

        <div class="notify-checkbox-row">
          <input type="checkbox" id="notifyGuest" checked>
          <label for="notifyGuest">Notify Guest</label>
        </div>

        <div class="message-box-wrapper">
          <textarea id="modalMessage" placeholder="Add a message..." rows="3" class="modal-textarea"></textarea>
        </div>

        <button id="updateStatusBtn" class="modal-submit-btn">Update Status</button>
      </div>
    </div>
  </div>
<script src="js/manage-event-guest.js"></script>
</body>
</html>