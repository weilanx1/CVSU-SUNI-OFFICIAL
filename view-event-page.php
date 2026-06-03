<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: cvsu-login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = false;
$admin_role = null;
$profile_picture = 'images/person3.png';
$user_full_name = 'User';
$user_email = 'user@example.com';

// Check if user is organization admin/moderator or main admin
$admin_stmt = $conn->prepare('SELECT role FROM organization_admins WHERE user_id = ? AND role IN ("admin", "moderator") LIMIT 1');
$admin_stmt->bind_param('i', $user_id);
$admin_stmt->execute();
$admin_result = $admin_stmt->get_result();
if ($admin_row = $admin_result->fetch_assoc()) {
    $is_admin = true;
    $admin_role = $admin_row['role'];
} else {
    $main_stmt = $conn->prepare('SELECT id FROM organizations WHERE main_admin_id = ? LIMIT 1');
    $main_stmt->bind_param('i', $user_id);
    $main_stmt->execute();
    $main_result = $main_stmt->get_result();
    if ($main_row = $main_result->fetch_assoc()) {
        $is_admin = true;
        $admin_role = 'main_admin';
    }
}

$stmt = $conn->prepare('SELECT first_name, last_name, email, profile_picture FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if ($user) {
    $profile_picture = !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : $profile_picture;
    $user_full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User';
    $user_email = !empty($user['email']) ? htmlspecialchars($user['email']) : 'No email';
}

if (!$is_admin) {
    $error_message = 'You do not have permission to view this page. Only organization admins or moderators may access this section.';
}

$event = null;
$event_title = 'Event Details';
$hero_background = 'images/cover.png';
$hero_image = 'images/stardew.png';
$org_name = 'Unknown Organization';
$org_logo = 'images/logo.png';
$event_date_str = '';
$event_time_str = '';
$event_venue = '';
$event_description = 'No description available for this event.';
$guest_profiles = [];
$going_profiles = [];
$org_id = null;

if ($is_admin) {
    $oq = "SELECT o.id FROM organizations o WHERE o.main_admin_id = ? UNION SELECT o.id FROM organization_admins oa JOIN organizations o ON oa.organization_id = o.id WHERE oa.user_id = ? LIMIT 1";
    $os = $conn->prepare($oq);
    $os->bind_param('ii', $user_id, $user_id);
    $os->execute();
    $or = $os->get_result();
    if ($row = $or->fetch_assoc()) {
        $org_id = $row['id'];
    }
}

if ($is_admin && $org_id && isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);
    $est = $conn->prepare('SELECT e.*, o.name AS org_name, o.logo AS org_logo FROM events e JOIN organizations o ON e.organization_id = o.id WHERE e.id = ? AND e.organization_id = ? LIMIT 1');
    $est->bind_param('ii', $event_id, $org_id);
    $est->execute();
    $eres = $est->get_result();
    $event = $eres->fetch_assoc();

    if ($event) {
        $event_title = htmlspecialchars($event['title'] ?: 'Event Details');
        $hero_background = !empty($event['cover_photo']) ? htmlspecialchars($event['cover_photo']) : (!empty($event['event_banner']) ? htmlspecialchars($event['event_banner']) : 'images/cover.png');
        $hero_image = !empty($event['event_banner']) ? htmlspecialchars($event['event_banner']) : (!empty($event['cover_photo']) ? htmlspecialchars($event['cover_photo']) : 'images/stardew.png');
        $org_name = htmlspecialchars($event['org_name'] ?: 'Unknown Organization');
        $org_logo = !empty($event['org_logo']) ? htmlspecialchars($event['org_logo']) : 'images/logo.png';
        $event_venue = htmlspecialchars($event['venue'] ?: 'Location not specified');
        $event_description = htmlspecialchars($event['description'] ?: 'No description available for this event.');
        $start_dt = new DateTime($event['start_datetime']);
        $end_dt = new DateTime($event['end_datetime']);
        $event_date_str = $start_dt->format('F j, Y');
        $event_time_str = $start_dt->format('g:i A') . ' - ' . $end_dt->format('g:i A');

        $guest_stmt = $conn->prepare('SELECT u.profile_picture FROM registrations r JOIN users u ON r.user_id = u.id WHERE r.event_id = ? AND r.status = ? LIMIT 8');
        $approved_status = 'approved';
        $guest_stmt->bind_param('is', $event_id, $approved_status);
        $guest_stmt->execute();
        $gres = $guest_stmt->get_result();
        while ($g = $gres->fetch_assoc()) {
            $guest_profiles[] = !empty($g['profile_picture']) ? htmlspecialchars($g['profile_picture']) : 'images/person3.png';
        }

        $going_stmt = $conn->prepare('SELECT u.profile_picture FROM registrations r JOIN users u ON r.user_id = u.id WHERE r.event_id = ? AND r.attendance_confirmation = ? LIMIT 8');
        $going_status = 'going';
        $going_stmt->bind_param('is', $event_id, $going_status);
        $going_stmt->execute();
        $gres2 = $going_stmt->get_result();
        while ($g = $gres2->fetch_assoc()) {
            $going_profiles[] = !empty($g['profile_picture']) ? htmlspecialchars($g['profile_picture']) : 'images/person3.png';
        }
    } else {
        $error_message = 'Event not found or you do not have access.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $event_title; ?></title>
    <!-- FONT AWESOME -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/view-event-page.css">
    <link rel="stylesheet" href="css/org-navbar.css">
    <style>
      /* page-specific white navbar styling for view event page only */
      nav.white-navbar {
        background: transparent;
      }
      nav.white-navbar a,
      nav.white-navbar a.white,
      nav.white-navbar .profile-link,
      nav.white-navbar .sign-out-link,
      nav.white-navbar .nav-icons i {
        color: #fff !important;
      }
      nav.white-navbar .profile {
        border: 1px solid rgba(255,255,255,0.8);
      }
      .admin-verification-banner {
        background: rgba(255,255,255,0.12);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.25);
        border-radius: 10px;
        padding: 14px 18px;
        margin: 16px auto;
        max-width: 1180px;
        text-align: center;
      }
    </style>
</head>
<body>
    <!-- HERO SECTION -->
    <section class="hero" style="background-image: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('<?php echo $hero_background; ?>');">
        <!-- NAVBAR -->
         <nav class="white-navbar">
          <div class="container nav-container">
              <a href="index.php">
                <img src="images/suni-logo-white.png" alt="Suni Logo">
              </a>
              <ul>
                  <li><a href="index.php" class="white">CvSU Events</a></li>
                  <li><a href="Myprofile.php" class="white">My Profile</a></li>
                  <?php if ($is_admin): ?>
                    <li><a href="dashboard.php" class="white">Organization Dashboard</a></li>
                  <?php endif; ?>
                  <li class="nav-icons">
                      <i class="fa-solid fa-magnifying-glass"></i>
                      <i class="fa-regular fa-bell fa-lg"></i>
                  </li>
                  <li><img src="<?php echo $profile_picture; ?>" class="profile" alt="Profile"></li>
                  <li><a href="sign-in.php" class="sign-out-link">Sign Out</a></li>
              </ul>
          </div>
      </nav>
      <?php if (!empty($error_message)): ?>
        <div class="admin-verification-banner"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>
      <?php if (empty($error_message) && $event): ?>
        <!-- OVERLAY -->
        <div class="overlay"></div>
        <!-- CONTENT -->
        <div class="hero-content">
            <!-- LEFT -->
            <div class="hero-left">
                <img src="<?php echo $hero_image; ?>" alt="Event banner">
            </div>
            <!-- RIGHT -->
           <div class="right-side">
                <h1><?php echo $event_title; ?></h1>
                <div class="people">
                    <div class="guest">
                        <span>Guest</span>
                        <div class="avatars">
                            <?php if (!empty($guest_profiles)): ?>
                                <?php foreach ($guest_profiles as $profile): ?>
                                    <img src="<?php echo $profile; ?>" alt="Guest profile">
                                <?php endforeach; ?>
                            <?php else: ?>
                                <img src="images/person3.png" alt="Guest profile">
                            <?php endif; ?>
                            <p><?php echo count($guest_profiles) > 0 ? count($guest_profiles) : '0'; ?>+</p>
                        </div>
                    </div>
                    <div class="going">
                        <span>Going</span>
                        <div class="avatars">
                            <?php if (!empty($going_profiles)): ?>
                                <?php foreach ($going_profiles as $profile): ?>
                                    <img src="<?php echo $profile; ?>" alt="Going profile">
                                <?php endforeach; ?>
                            <?php else: ?>
                                <img src="images/person3.png" alt="Going profile">
                            <?php endif; ?>
                            <p><?php echo count($going_profiles) > 0 ? count($going_profiles) : '0'; ?>+</p>
                        </div>
                    </div>
                </div>
                <!-- HOST -->
                <div class="host">
                    <img src="<?php echo $org_logo; ?>" alt="Host logo">
                    <div>
                        <span>Hosted By</span>
                        <h3><?php echo $org_name; ?></h3>
                    </div>
                    <i class="fa-regular fa-envelope"></i>
                </div>
            <!-- EVENT INFO -->
                <div class="event-info">
                    <div class="info-box">
                        <img src="images/calendar.png" alt="Date icon">
                        <p><?php echo $event_date_str; ?></p>
                    </div>
                    <div class="info-box">
                        <img src="images/time.png" alt="Time icon">
                        <p><?php echo $event_time_str; ?></p>
                    </div>
                    <div class="info-box">
                        <img src="images/location.png" alt="Location icon">
                        <p><?php echo $event_venue; ?></p>
                    </div>
            </div>
            </div>
        </div>
    </section>
    <!-- MAIN CONTENT -->
    <section class="main-content">
        <!-- LEFT COLUMN -->
        <div class="left-column">
            <!-- TICKET -->
             <div id="ticket-ui" class="event-registration-card">
                <div id="ticket-content" class="ticket-content">
                    <div class="ticket-user-card">
                        <img src="<?php echo $profile_picture; ?>" alt="<?php echo htmlspecialchars($user_full_name); ?>" class="ticket-user-avatar">
                        <div class="ticket-user-info">
                            <h3>Welcome, <?php echo htmlspecialchars($user_full_name); ?>!</h3>
                            <p class="ticket-user-email"><?php echo htmlspecialchars($user_email); ?></p>
                            <p class="ticket-user-message">Please register to join event.</p>
                        </div>
                    </div>
                </div>
            </div>
    <!-- MODAL -->
<div class="req" id="req">
    <div class="req-inner">
        <!-- CLOSE BUTTON -->
        <div class="modal-close">
            <i class="fa-solid fa-xmark"></i>
        </div>
        <!-- HEADER -->
        <div class="popup-header">
            <h2>Register for the Event</h2>
            <p class="modal-subtitle">
                Please review your details before submitting.
            </p>
        </div>
        <div class="popup-body">
            <div class="popup-left">
                <img src="images/form.png" alt="Event Image">
            </div>
            <div class="popup-right">
                <div class="notice">
                    <i class="fa-solid fa-check"></i>
                    <span>
                        Your details are pre-filled. Please verify before continuing
                    </span>
                </div>
                <form id="regForm">
                    <h3 class="section-title">Personal Information</h3>
                    <div class="form-row">
                        <div class="input-wrapper">
                            <label>First Name*</label>
                            <input type="text" id="firstName" name="firstName" placeholder="Xeed Love">
                        </div>
                        <div class="input-wrapper">
                            <label>Last Name*</label>
                            <input type="text" id="lastName" name="lastName" placeholder="Magtira">
                        </div>
                    </div>
                <div class="input-wrapper">
                        <label>Student ID*</label>
                        <input type="text" id="studentID" name="studentID" placeholder="202400000">
                        <span class="helper-text">Your school-issued ID</span>
                    </div>
                    <hr>
                    <h3 class="section-title">
                        Academic Details
                    </h3>
<div class="form-row">
    <!-- DEPARTMENT -->
    <div class="input-wrapper" id="deptWrapper">
        <label>Department*</label>
        <div class="custom-dropdown">
            <div class="dropdown-header">
                <span id="selectedDept">CEIT</span>
                <i class='bx bx-chevron-down arrow'></i>
            </div>
            <ul class="dropdown-list">
                <li onclick="updateDropdown('selectedDept', 'CEIT')">CEIT</li>
                <li onclick="updateDropdown('selectedDept', 'CAS')">CAS</li>
                <li onclick="updateDropdown('selectedDept', 'CCJ')">CCJ</li>
            </ul>
        </div>
    </div>
</div>
                    <!-- BUTTON -->
                    <button type="submit" class="submit-btn">
                        Request to Join
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
            </div>
        </div>
        <!-- RIGHT COLUMN -->
        <div class="right-column">
            <h2>About Event</h2>
            <hr>
            <p>
                <?php echo nl2br($event_description); ?>
            </p>
        </div>

   <div id="ticketModal" class="modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <div class="ticket-header">
            <span class="ticket-label">TICKET</span>
            <h2><?php echo $event_title; ?></h2>
            <p class="event-details">
                <?php echo $event_date_str; ?>, <?php echo $event_time_str; ?><br>
                <?php echo $event_venue; ?>
            </p>
        </div>
        
        <div class="ticket-body">
            <img src="images/sid.png" class="guest-avatar">
            <div class="guest-info">
                <p>Present at entrance</p>
                <hr>
                <p><strong>Guest</strong><br>Xeed Love L. Magtira</p>
                <span class="status-badge">✔ Going</span>
            </div>
        </div>

        <div class="ticket-footer">
            <button class="btn-directions"><i class="fa fa-location-arrow"></i> Directions</button>
            <button class="btn-save" onclick="window.print()"><i class="fa fa-phone"></i> Save Ticket</button>
        </div>
    </div>
</div>
      <?php endif; ?>

    <script src="js/eventpage.js"></script>
    <script src="js/navbar.js"></script>
</body>
</html>