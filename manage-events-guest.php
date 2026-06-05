<?php
session_start();
require_once 'db.php';

// ── Auth & profile picture ────────────────────────────────────────────────────
$profile_picture = 'images/person3.png';
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    $stmt = $conn->prepare('SELECT profile_picture FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $u = $stmt->get_result()->fetch_assoc();
    if ($u && !empty($u['profile_picture'])) {
        $profile_picture = htmlspecialchars($u['profile_picture']);
    }
}

// ── Resolve the organisation this admin belongs to ────────────────────────────
$org_id = null;
if ($user_id) {
    $oq = "SELECT o.id FROM organizations o WHERE o.main_admin_id = ?
           UNION
           SELECT o.id FROM organization_admins oa
           JOIN organizations o ON oa.organization_id = o.id
           WHERE oa.user_id = ?
           LIMIT 1";
    $os = $conn->prepare($oq);
    $os->bind_param('ii', $user_id, $user_id);
    $os->execute();
    if ($row = $os->get_result()->fetch_assoc()) $org_id = $row['id'];
}

// ── Load event (must belong to this org) ─────────────────────────────────────
$event    = null;
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if ($event_id && $org_id) {
    $est = $conn->prepare('SELECT * FROM events WHERE id = ? AND organization_id = ? LIMIT 1');
    $est->bind_param('ii', $event_id, $org_id);
    $est->execute();
    $event = $est->get_result()->fetch_assoc();
}

if (!$event) {
    die('Event not found or you do not have access. <a href="manage.php">Go back</a>');
}

// ── Handle AJAX actions ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');

    $action     = $_POST['ajax_action'];
    $reg_id     = intval($_POST['registration_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? null;
    $new_attend = $_POST['new_attend'] ?? null;

    // Verify registration belongs to this event & org
    $chk = $conn->prepare(
        'SELECT r.id FROM registrations r
         JOIN events e ON e.id = r.event_id
         WHERE r.id = ? AND e.id = ? AND e.organization_id = ? LIMIT 1'
    );
    $chk->bind_param('iii', $reg_id, $event_id, $org_id);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) {
        echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
        exit;
    }

    if ($action === 'approve') {
        $upd = $conn->prepare(
            "UPDATE registrations SET status='approved', attendance_confirmation='unconfirmed', updated_at=NOW() WHERE id=?"
        );
        $upd->bind_param('i', $reg_id);
        $upd->execute();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'decline') {
        $upd = $conn->prepare(
            "UPDATE registrations SET status='rejected', attendance_confirmation='unconfirmed', updated_at=NOW() WHERE id=?"
        );
        $upd->bind_param('i', $reg_id);
        $upd->execute();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'update_status') {
        $allowed_status      = ['pending', 'approved', 'rejected', 'waitlisted'];
        $allowed_attend      = ['unconfirmed', 'going', 'not_going', 'cancelled'];
        $attend_ignored_for  = ['pending', 'rejected', 'waitlisted'];
        $set_parts  = [];
        $bind_types = '';
        $bind_vals  = [];

        if ($new_status && in_array($new_status, $allowed_status)) {
            $set_parts[]  = 'status = ?';
            $bind_types  .= 's';
            $bind_vals[]  = $new_status;
        }
        // If the new status makes attendance irrelevant, force-reset it to unconfirmed
        if ($new_status && in_array($new_status, $attend_ignored_for)) {
            $new_attend = 'unconfirmed';
        }
        if ($new_attend && in_array($new_attend, $allowed_attend)) {
            $set_parts[]  = 'attendance_confirmation = ?';
            $bind_types  .= 's';
            $bind_vals[]  = $new_attend;
        }

        if (!empty($set_parts)) {
            $sql = 'UPDATE registrations SET ' . implode(', ', $set_parts) . ', updated_at=NOW() WHERE id=?';
            $bind_types .= 'i';
            $bind_vals[] = $reg_id;
            $upd = $conn->prepare($sql);
            $upd->bind_param($bind_types, ...$bind_vals);
            $upd->execute();
        }
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
    exit;
}

// ── Guest statistics ──────────────────────────────────────────────────────────
$capacity    = isset($event['capacity']) && $event['capacity'] > 0 ? intval($event['capacity']) : null;
$cap_display = $capacity ? number_format($capacity) : 'Unlimited';

$s = $conn->prepare("SELECT COUNT(*) AS c FROM registrations WHERE event_id=? AND status='approved'");
$s->bind_param('i', $event_id); $s->execute();
$approved_count = intval($s->get_result()->fetch_assoc()['c']);

$s = $conn->prepare("SELECT COUNT(*) AS c FROM registrations WHERE event_id=?");
$s->bind_param('i', $event_id); $s->execute();
$total_count = intval($s->get_result()->fetch_assoc()['c']);

$s = $conn->prepare("SELECT COUNT(*) AS c FROM registrations WHERE event_id=? AND attendance_confirmation='going'");
$s->bind_param('i', $event_id); $s->execute();
$going_count = intval($s->get_result()->fetch_assoc()['c']);

$s = $conn->prepare("SELECT COUNT(*) AS c FROM registrations WHERE event_id=? AND status='pending'");
$s->bind_param('i', $event_id); $s->execute();
$pending_count = intval($s->get_result()->fetch_assoc()['c']);

$progress_pct = ($capacity && $capacity > 0)
    ? min(100, round(($approved_count / $capacity) * 100))
    : 0;

// ── Load all registrations with user info ─────────────────────────────────────
$registrations = [];
$rst = $conn->prepare(
    'SELECT r.id AS reg_id,
            r.status,
            r.attendance_confirmation,
            r.updated_at,
            u.id   AS user_id,
            u.first_name,
            u.last_name,
            u.email,
            u.profile_picture
     FROM registrations r
     JOIN users u ON u.id = r.user_id
     WHERE r.event_id = ?
     ORDER BY r.updated_at DESC'
);
$rst->bind_param('i', $event_id);
$rst->execute();
$rres = $rst->get_result();
while ($row = $rres->fetch_assoc()) {
    $registrations[] = $row;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function friendly_time(string $dt): string {
    $ts   = strtotime($dt);
    if (!$ts) return '—';
    $diff = time() - $ts;
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 172800) return 'Yesterday';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}

// Returns ['label'=>…, 'class'=>…, 'filter'=>…]
// filter key must match exactly what the JS filter buttons use (data-filter attribute)
function derive_display(array $r): array {
    $attend = $r['attendance_confirmation'];
    $status = $r['status'];

    if ($attend === 'going')      return ['label' => 'Going',      'class' => 'badge-going',     'filter' => 'going'];
    if ($attend === 'not_going')  return ['label' => 'Not Going',  'class' => 'badge-not-going', 'filter' => 'not_going'];
    if ($attend === 'cancelled')  return ['label' => 'Cancelled',  'class' => 'badge-cancelled', 'filter' => 'cancelled'];
    if ($status  === 'approved')  return ['label' => 'Approved',   'class' => 'badge-approved',  'filter' => 'approved'];
    if ($status  === 'rejected')  return ['label' => 'Declined',   'class' => 'badge-declined',  'filter' => 'rejected'];
    if ($status  === 'waitlisted')return ['label' => 'Waitlist',   'class' => 'badge-waitlist',  'filter' => 'waitlisted'];
    return                               ['label' => 'Pending',    'class' => 'badge-pending',   'filter' => 'pending'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Event – Guests | <?php echo htmlspecialchars($event['title']); ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

  <link rel="stylesheet" href="css/manage-events-guest.css"/>
  <link rel="stylesheet" href="css/org-navbar.css">
  <link rel="stylesheet" href="css/sidebar.css"/>
  <link rel="stylesheet" href="css/mini-nav.css"/>
</head>
<body>

  <!-- ── Sidebar ── -->
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

  <!-- ── Main ── -->
  <div class="main">
    <nav>
      <div class="container nav-container">
        <a href="#"><img src="images/logo.png" alt="Suni Logo" style="display:none;"></a>
        <ul>
          <li><a href="create-events.php">+ Create Events</a></li>
          <li><a href="index.php">CvSU Events</a></li>
          <li><a href="Myprofile.php">My Profile</a></li>
          <li><a href="dashboard.php">Organization Dashboard</a></li>
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

      <!-- Sub-nav tabs -->
      <div class="content-tabs">
        <a href="manage-events.php?event_id=<?php echo $event_id; ?>"              class="tab-item">Details</a>
        <a href="manage-events-banner.php?event_id=<?php echo $event_id; ?>"       class="tab-item">Banner</a>
        <a href="manage-events-guest.php?event_id=<?php echo $event_id; ?>"        class="tab-item active">Guest</a>
        <a href="manage-events-registration.php?event_id=<?php echo $event_id; ?>" class="tab-item" data-text="Registration">Registration</a>
      </div>

      <div class="manage-card">

        <!-- ── At a Glance ── -->
        <div class="glance-section">
          <h3 class="card-section-title">At a Glance</h3>
          <div class="glance-header">
            <span class="guest-counter">
              <strong id="glanceApproved"><?php echo $approved_count; ?></strong>
              <?php echo $approved_count === 1 ? 'guest' : 'guests'; ?>
            </span>
            <span class="guest-cap">
              cap <strong><?php echo $cap_display; ?></strong>
            </span>
          </div>

          <div class="glance-progress-bar">
            <div class="progress-fill" id="glanceBar" style="width:<?php echo $progress_pct; ?>%;"></div>
          </div>

          <div class="glance-stats-labels">
            <span id="glanceRegistered"><i class="fa-solid fa-circle status-dot-black"></i> <?php echo $total_count; ?> Registered</span>
            <span id="glanceGoing"><i class="fa-solid fa-circle status-dot-green"></i> <?php echo $going_count; ?> Going</span>
            <span id="glancePending"><i class="fa-solid fa-circle status-dot-yellow"></i> <?php echo $pending_count; ?> Pending</span>
          </div>

          <div class="glance-buttons-grid">
            <div class="glance-widget-btn" data-filter="pending">
              <div class="widget-icon-box"><i class="fa-solid fa-hourglass-half"></i></div>
              <div class="widget-text">Pending <span class="widget-badge" id="pendingBadge"><?php echo $pending_count; ?></span></div>
            </div>
            <div class="glance-widget-btn" data-filter="checkin">
              <div class="widget-icon-box"><i class="fa-solid fa-users"></i></div>
              <div class="widget-text">Check In Guests</div>
            </div>
            <div class="glance-widget-btn" data-filter="all">
              <div class="widget-icon-box"><i class="fa-regular fa-clipboard"></i></div>
              <div class="widget-text">Guest List</div>
            </div>
          </div>
        </div><!-- /glance-section -->

        <hr class="section-divider">

        <!-- ── Guest List ── -->
        <div class="guest-list-section">
          <h3 class="card-section-title">Guest List</h3>

          <div class="search-filter-row">
            <div class="search-wrapper">
              <i class="fa-solid fa-magnifying-glass search-icon"></i>
              <input type="text" id="guestSearchInput" placeholder="Search Guests.." class="search-input">
            </div>
          </div>

          <div class="filter-actions-row">
            <div class="filter-tabs">
              <button class="filter-btn active" data-filter="all">All Guests</button>
              <button class="filter-btn" data-filter="pending">Pending</button>
              <button class="filter-btn" data-filter="approved">Approved</button>
              <button class="filter-btn" data-filter="going">Going</button>
              <button class="filter-btn" data-filter="rejected">Declined</button>
            </div>
            <button class="action-trigger-btn">Guest Actions</button>
          </div>

          <div class="guest-list-container" id="guestListContainer">
            <?php if (empty($registrations)): ?>
              <div class="empty-state">
                <i class="fa-regular fa-face-smile"></i>
                <p>No guests have registered yet.</p>
              </div>
            <?php else: ?>
              <?php foreach ($registrations as $reg):
                $fullname   = htmlspecialchars(trim($reg['first_name'] . ' ' . $reg['last_name']));
                $email      = htmlspecialchars($reg['email']);
                $avatar     = !empty($reg['profile_picture']) ? htmlspecialchars($reg['profile_picture']) : 'images/person3.png';
                $display    = derive_display($reg);
                $time_label = friendly_time($reg['updated_at'] ?? '');
                $is_pending = ($reg['status'] === 'pending');
                $filter_key = $is_pending ? 'pending' : $display['filter'];
              ?>
              <div class="guest-row-item"
                   data-reg-id="<?php echo intval($reg['reg_id']); ?>"
                   data-name="<?php echo strtolower($fullname); ?>"
                   data-email="<?php echo strtolower($email); ?>"
                   data-filter="<?php echo htmlspecialchars($filter_key); ?>"
                   data-avatar="<?php echo $avatar; ?>"
                   data-fullname="<?php echo $fullname; ?>"
                   data-status="<?php echo htmlspecialchars($reg['status']); ?>"
                   data-attend="<?php echo htmlspecialchars($reg['attendance_confirmation']); ?>">

                <div class="guest-profile-meta">
                  <img src="<?php echo $avatar; ?>" class="guest-avatar" alt="Avatar"
                       onerror="this.src='images/person3.png'">
                  <div class="guest-info">
                    <div class="guest-name"><?php echo $fullname; ?></div>
                    <div class="guest-email"><?php echo $email; ?></div>
                  </div>
                </div>

                <div class="guest-actions-cell">
                  <?php if ($is_pending): ?>
                    <button class="btn-status btn-approve" onclick="event.stopPropagation(); handleApprove(this)">
                      Approve <i class="fa-solid fa-check"></i>
                    </button>
                    <button class="btn-status btn-decline" onclick="event.stopPropagation(); handleDecline(this)">
                      Decline
                    </button>
                  <?php else: ?>
                    <span class="status-badge <?php echo $display['class']; ?>"><?php echo $display['label']; ?></span>
                    <span class="status-timestamp"><?php echo $time_label; ?></span>
                  <?php endif; ?>
                  <button class="btn-more-options" onclick="event.stopPropagation(); openStatusModal(this)">
                    <i class="fa-solid fa-ellipsis"></i>
                  </button>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div><!-- /guest-list-container -->
        </div><!-- /guest-list-section -->

      </div><!-- /manage-card -->
    </div><!-- /contents -->
  </div><!-- /main -->

  <!-- ── Status modal ── -->
  <div id="statusModal" class="modal">
    <div class="modal-card">
      <button class="modal-close-btn" id="modalCloseBtn">&times;</button>
      <div class="modal-profile-header">
        <img src="images/person3.png" id="modalAvatar" class="modal-large-avatar"
             onerror="this.src='images/person3.png'">
        <h4 id="modalName">—</h4>
        <p id="modalEmail">—</p>
      </div>
      <input type="hidden" id="modalRegId" value="">
      <div class="modal-body-form">
        <label class="modal-label">Approval status:</label>
        <div class="custom-dropdown-wrapper">
          <select id="statusSelect" class="modal-select">
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="waitlisted">Waitlisted</option>
          </select>
        </div>

        <label class="modal-label" style="margin-top:12px;">Attendance confirmation:</label>
        <div class="custom-dropdown-wrapper">
          <select id="attendSelect" class="modal-select">
            <option value="unconfirmed">Unconfirmed</option>
            <option value="going">Going</option>
            <option value="not_going">Not Going</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>

        <button id="updateStatusBtn" class="modal-submit-btn">Update Status</button>
      </div>
    </div>
  </div>

  <!-- PHP-generated constants — must come BEFORE manage-event-guest.js -->
  <script>
    const EVENT_ID = <?php echo $event_id; ?>;
    const CAP      = <?php echo $capacity ? intval($capacity) : 0; ?>;
  </script>
  <script src="js/manage-event-guest.js"></script>
  <script src="js/navbar.js"></script>
</body>
</html>