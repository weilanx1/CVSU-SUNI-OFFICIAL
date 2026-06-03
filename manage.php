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
  <link rel="stylesheet" href="css/manage.css"/>
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

        <a href="manage.php"  class="active">
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

    <div class="title-row">
      <h1>Manage events</h1>

      <a href="create-events.php" class="create-event">+ Create Event</a>
    </div>
    <div class="divider"></div>
    <div class="events">
    <?php
    // Determine organization for current user (main admin or org admin)
    $org_id = null;
    if (isset($_SESSION['user_id'])) {
        $uid = $_SESSION['user_id'];
        $q = "SELECT o.id FROM organizations o WHERE o.main_admin_id = ? UNION SELECT o.id FROM organization_admins oa JOIN organizations o ON oa.organization_id = o.id WHERE oa.user_id = ? LIMIT 1";
        $s = $conn->prepare($q);
        $s->bind_param('ii', $uid, $uid);
        $s->execute();
        $r = $s->get_result();
        if ($row = $r->fetch_assoc()) $org_id = $row['id'];
    }

    if (!$org_id) {
        echo '<p>No organization found for your account.</p>';
    } else {
        $stmt = $conn->prepare('SELECT e.* FROM events e WHERE e.organization_id = ? ORDER BY e.created_at DESC');
        $stmt->bind_param('i', $org_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($ev = $res->fetch_assoc()) {
            $eid = $ev['id'];
            $title = htmlspecialchars($ev['title']);
            $desc = htmlspecialchars($ev['description']);
            $banner = !empty($ev['event_banner']) ? htmlspecialchars($ev['event_banner']) : 'images/stardew.png';
            $venue = htmlspecialchars($ev['venue']);
            $start = new DateTime($ev['start_datetime']);
            $end = new DateTime($ev['end_datetime']);
            $dateStr = $start->format('F j, Y');
            $timeStr = $start->format('g:i A') . ' – ' . $end->format('g:i A');
            // registrations count
            $cstmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM registrations WHERE event_id = ?');
            $cstmt->bind_param('i', $eid);
            $cstmt->execute();
            $cres = $cstmt->get_result()->fetch_assoc();
            $registered = intval($cres['cnt']);

            echo '<div class="event-card">';
            echo '<div class="event-img"><img src="' . $banner . '" alt=""></div>';
            echo '<div class="event-content">';
            echo '<h2>' . $title . '</h2>';
            echo '<p class="description">' . $desc . '</p>';
            echo '<div class="details">';
            echo '<div class="detail"><img src="images/calendar.png" class="btnIcon">' . $dateStr . '</div>';
            echo '<div class="detail"><img src="images/clock.png" class="btnIcon">' . $timeStr . '</div>';
            echo '<div class="detail"><img src="images/location.png" class="btnIcon">' . $venue . '</div>';
            echo '<div class="detail"><img src="images/register.png" class="btnIcon">' . $registered . ' Registered</div>';
            echo '</div>';
            echo '<div class="card-buttons">';
            echo '<a href="view-event-page.php?event_id=' . $eid . '" class="view-btn"><img src="images/openview.png" alt="" class="btn-icon">View Event Page</a>';
            echo '<a href="manage-events.php?event_id=' . $eid . '" class="manage-link"><i class="fa-regular fa-pen-to-square"></i>Manage Event</a>';
            echo '</div></div></div>';
        }
    }
    ?>
    </div>
  </div>
</body>
</html>