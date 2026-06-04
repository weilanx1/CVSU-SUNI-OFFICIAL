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

  <style>
    /* ── DELETE BUTTON ── */
    .delete-btn {
      position: absolute !important;
      top: 10px !important;
      right: 10px !important;
      background: #c0392b !important;
      border: 2.5px solid #fff !important;
      color: #fff !important;
      width: 30px !important;
      height: 30px !important;
      border-radius: 50% !important;
      cursor: pointer !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      box-shadow: 0 2px 8px rgba(192,57,43,0.4) !important;
      transition: background 0.2s, transform 0.15s, box-shadow 0.2s !important;
      z-index: 10 !important;
      padding: 0 !important;
    }
    .delete-btn:hover {
      background: #a93226 !important;
      transform: scale(1.12) !important;
      box-shadow: 0 4px 14px rgba(192,57,43,0.5) !important;
    }
    .delete-btn svg {
      pointer-events: none;
    }

    /* ── MODAL OVERLAY ── */
    .modal-overlay {
      display: none;
      position: fixed !important;
      top: 0 !important;
      left: 0 !important;
      width: 100vw !important;
      height: 100vh !important;
      background: rgba(0,0,0,0.5) !important;
      z-index: 9999 !important;
      align-items: center !important;
      justify-content: center !important;
      backdrop-filter: blur(3px) !important;
    }
    .modal-overlay.active {
      display: flex !important;
    }

    /* ── MODAL BOX ── */
    .modal-box {
      background: #fff !important;
      border-radius: 18px !important;
      padding: 40px 36px 32px !important;
      max-width: 400px !important;
      width: 90% !important;
      text-align: center !important;
      box-shadow: 0 12px 48px rgba(0,0,0,0.2) !important;
      animation: modalPop 0.22s cubic-bezier(.34,1.56,.64,1) both !important;
      position: relative !important;
    }
    @keyframes modalPop {
      from { transform: scale(0.88); opacity: 0; }
      to   { transform: scale(1);    opacity: 1; }
    }

    .modal-icon {
      width: 60px !important;
      height: 60px !important;
      border-radius: 50% !important;
      background: #fff4e5 !important;
      color: #e67e22 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      margin: 0 auto 18px !important;
    }

    .modal-title {
      font-family: 'Poppins', sans-serif !important;
      font-size: 22px !important;
      font-weight: 700 !important;
      color: #1a1a1a !important;
      margin-bottom: 10px !important;
    }

    .modal-msg {
      font-size: 13.5px !important;
      color: #555 !important;
      line-height: 1.6 !important;
      margin-bottom: 28px !important;
    }
    .modal-msg strong {
      color: #222 !important;
    }

    .modal-actions {
      display: flex !important;
      gap: 12px !important;
      justify-content: center !important;
    }

    .modal-cancel {
      flex: 1 !important;
      padding: 11px !important;
      border-radius: 8px !important;
      border: 1.5px solid #d6d6d6 !important;
      background: #fff !important;
      color: #555 !important;
      font-size: 14px !important;
      font-family: 'Poppins', sans-serif !important;
      cursor: pointer !important;
      transition: background 0.18s, border-color 0.18s !important;
    }
    .modal-cancel:hover {
      background: #f4f4f4 !important;
      border-color: #bbb !important;
    }

    .modal-confirm {
      flex: 1 !important;
      padding: 11px !important;
      border-radius: 8px !important;
      border: none !important;
      background: #c0392b !important;
      color: #fff !important;
      font-size: 14px !important;
      font-family: 'Poppins', sans-serif !important;
      font-weight: 600 !important;
      cursor: pointer !important;
      transition: background 0.18s, box-shadow 0.18s !important;
    }
    .modal-confirm:hover {
      background: #a93226 !important;
      box-shadow: 0 4px 14px rgba(192,57,43,0.28) !important;
    }
  </style>
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

    <div class="title-row">
      <h1>Manage events</h1>
      <a href="create-events.php" class="create-event">+ Create Event</a>
    </div>
    <div class="divider"></div>
    <div class="events">
    <?php
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
            $cstmt = $conn->prepare('SELECT COUNT(*) AS cnt FROM registrations WHERE event_id = ?');
            $cstmt->bind_param('i', $eid);
            $cstmt->execute();
            $cres = $cstmt->get_result()->fetch_assoc();
            $registered = intval($cres['cnt']);

            echo '<div class="event-card">';
            echo '<button class="delete-btn" onclick="confirmDelete(' . $eid . ', \'' . addslashes($title) . '\')" title="Delete Event">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                <path d="M10 11v6"></path>
                <path d="M14 11v6"></path>
                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
              </svg>
            </button>';
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

  <!-- Delete Confirmation Modal -->
  <div class="modal-overlay" id="deleteModal">
    <div class="modal-box">
      <div class="modal-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
          <line x1="12" y1="9" x2="12" y2="13"></line>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
      </div>
      <h2 class="modal-title">Delete Event?</h2>
      <p class="modal-msg">You are about to delete <strong id="modalEventName"></strong>. This action cannot be undone.</p>
      <div class="modal-actions">
        <button class="modal-cancel" onclick="closeModal()">Cancel</button>
        <button class="modal-confirm" id="confirmDeleteBtn">Delete Event</button>
      </div>
    </div>
  </div>

  <script>
    let pendingDeleteId = null;

    function confirmDelete(eventId, eventName) {
      pendingDeleteId = eventId;
      document.getElementById('modalEventName').textContent = eventName;
      document.getElementById('deleteModal').classList.add('active');
    }

    function closeModal() {
      pendingDeleteId = null;
      document.getElementById('deleteModal').classList.remove('active');
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
      if (pendingDeleteId) {
        window.location.href = 'delete-event.php?event_id=' + pendingDeleteId;
      }
    });

    document.getElementById('deleteModal').addEventListener('click', function (e) {
      if (e.target === this) closeModal();
    });
  </script>

</body>
</html>