<?php
session_start();
require_once 'db.php';

// ── Auth ──────────────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: cvsu-login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ── Load current user ─────────────────────────────────────────────────────────
$profile_picture = 'images/person3.png';
$user_full_name  = 'User';
$user_email      = 'user@example.com';
$user_dept_id    = null;
$user_dept_code  = '';

$stmt = $conn->prepare(
    'SELECT u.first_name, u.last_name, u.email, u.profile_picture, u.department_id, d.code AS dept_code
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     WHERE u.id = ? LIMIT 1'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if ($user) {
    $profile_picture = !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : $profile_picture;
    $user_full_name  = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'User';
    $user_email      = !empty($user['email']) ? htmlspecialchars($user['email']) : 'No email';
    $user_dept_id    = $user['department_id'] ?? null;
    $user_dept_code  = $user['dept_code'] ?? '';
}

// ── Is admin? (for dashboard link in navbar) ──────────────────────────────────
$is_admin = false;
$adm = $conn->prepare('SELECT id FROM organization_admins WHERE user_id = ? AND role IN ("admin","moderator") LIMIT 1');
$adm->bind_param('i', $user_id);
$adm->execute();
if ($adm->get_result()->fetch_assoc()) {
    $is_admin = true;
} else {
    $madm = $conn->prepare('SELECT id FROM organizations WHERE main_admin_id = ? LIMIT 1');
    $madm->bind_param('i', $user_id);
    $madm->execute();
    if ($madm->get_result()->fetch_assoc()) $is_admin = true;
}

// ── Load event (any published event, no admin restriction) ────────────────────
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if (!$event_id) {
    die('No event specified. <a href="index.php">Go back</a>');
}

$est = $conn->prepare(
    'SELECT e.*, o.name AS org_name, o.logo AS org_logo
     FROM events e
     JOIN organizations o ON e.organization_id = o.id
     WHERE e.id = ? LIMIT 1'
);
$est->bind_param('i', $event_id);
$est->execute();
$event = $est->get_result()->fetch_assoc();

if (!$event) {
    die('Event not found. <a href="index.php">Go back</a>');
}

// ── Handle AJAX POST actions ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];

    // ── Register ──────────────────────────────────────────────────────────────
    if ($action === 'register') {
        // Check capacity
        if (!empty($event['capacity'])) {
            $cap_check = $conn->prepare(
                "SELECT COUNT(*) AS c FROM registrations WHERE event_id = ? AND status IN ('approved','pending')"
            );
            $cap_check->bind_param('i', $event_id);
            $cap_check->execute();
            $cap_count = intval($cap_check->get_result()->fetch_assoc()['c']);
            if ($cap_count >= intval($event['capacity'])) {
                // waitlist
                $ins = $conn->prepare(
                    "INSERT INTO registrations (event_id, user_id, status, attendance_confirmation) VALUES (?, ?, 'waitlisted', 'unconfirmed')"
                );
                $ins->bind_param('ii', $event_id, $user_id);
                $ins->execute();
                echo json_encode(['ok' => true, 'status' => 'waitlisted', 'reg_id' => $conn->insert_id]);
                exit;
            }
        }

        // FORCE PENDING STATUS IF REQUIRE APPROVAL IS TRUE
        $new_status = (!empty($event['require_approval']) && $event['require_approval'] == 1) ? 'pending' : 'approved';
        
        $ins = $conn->prepare(
            "INSERT INTO registrations (event_id, user_id, status, attendance_confirmation) VALUES (?, ?, ?, 'unconfirmed')"
        );
        $ins->bind_param('iis', $event_id, $user_id, $new_status);
        if ($ins->execute()) {
            echo json_encode(['ok' => true, 'status' => $new_status, 'reg_id' => $conn->insert_id]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'DB error: ' . $conn->error]);
        }
        exit;
    }

    // ── Update attendance ─────────────────────────────────────────────────────
    if ($action === 'update_attendance') {
        $attend = $_POST['attendance'] ?? '';
        $allowed = ['going', 'not_going', 'cancelled'];
        if (!in_array($attend, $allowed)) {
            echo json_encode(['ok' => false, 'msg' => 'Invalid attendance value']);
            exit;
        }
        $upd = $conn->prepare(
            "UPDATE registrations SET attendance_confirmation = ?, updated_at = NOW()
             WHERE event_id = ? AND user_id = ?"
        );
        $upd->bind_param('sii', $attend, $event_id, $user_id);
        $upd->execute();
        echo json_encode(['ok' => true, 'attendance' => $attend]);
        exit;
    }

    // ── Cancel Registration ───────────────────────────────────────────────────
    if ($action === 'cancel_registration') {
        $del = $conn->prepare("DELETE FROM registrations WHERE event_id = ? AND user_id = ?");
        $del->bind_param('ii', $event_id, $user_id);
        if ($del->execute()) {
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'DB error: ' . $conn->error]);
        }
        exit;
    }

    echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
    exit;
}

// ── Fetch user's existing registration ────────────────────────────────────────
$registration      = null;
$reg_status        = null; 
$reg_attendance    = null; 

$reg_stmt = $conn->prepare(
    'SELECT id, status, attendance_confirmation FROM registrations
     WHERE event_id = ? AND user_id = ? LIMIT 1'
);
$reg_stmt->bind_param('ii', $event_id, $user_id);
$reg_stmt->execute();
$registration = $reg_stmt->get_result()->fetch_assoc();
if ($registration) {
    $reg_status     = $registration['status'];
    $reg_attendance = $registration['attendance_confirmation'];
}

// ── Event display data ────────────────────────────────────────────────────────
$event_title     = htmlspecialchars($event['title'] ?: 'Event Details');
$hero_background = !empty($event['cover_photo'])   ? htmlspecialchars($event['cover_photo'])   :
                   (!empty($event['event_banner']) ? htmlspecialchars($event['event_banner'])  : 'images/cover.png');
$hero_image      = !empty($event['event_banner'])  ? htmlspecialchars($event['event_banner'])  :
                   (!empty($event['cover_photo'])  ? htmlspecialchars($event['cover_photo'])   : 'images/stardew.png');
$org_name        = htmlspecialchars($event['org_name']  ?: 'Unknown Organization');
$org_logo        = !empty($event['org_logo'])      ? htmlspecialchars($event['org_logo'])      : 'images/logo.png';
$event_venue     = htmlspecialchars($event['venue'] ?: 'Location not specified');
$event_description = htmlspecialchars($event['description'] ?: 'No description available for this event.');
$start_dt        = new DateTime($event['start_datetime']);
$end_dt          = new DateTime($event['end_datetime']);
$event_date_str  = $start_dt->format('F j, Y');
$event_time_str  = $start_dt->format('g:i A') . ' - ' . $end_dt->format('g:i A');
$require_approval = (bool) $event['require_approval'];

// ── Guest / going avatars ─────────────────────────────────────────────────────
$guest_profiles = [];
$going_profiles = [];

$gs = $conn->prepare(
    "SELECT u.profile_picture FROM registrations r
     JOIN users u ON r.user_id = u.id
     WHERE r.event_id = ? AND r.status = 'approved' LIMIT 8"
);
$gs->bind_param('i', $event_id);
$gs->execute();
$gres = $gs->get_result();
while ($g = $gres->fetch_assoc()) {
    $guest_profiles[] = !empty($g['profile_picture']) ? htmlspecialchars($g['profile_picture']) : 'images/person3.png';
}

$gg = $conn->prepare(
    "SELECT u.profile_picture FROM registrations r
     JOIN users u ON r.user_id = u.id
     WHERE r.event_id = ? AND r.attendance_confirmation = 'going' LIMIT 8"
);
$gg->bind_param('i', $event_id);
$gg->execute();
$gres2 = $gg->get_result();
while ($g = $gres2->fetch_assoc()) {
    $going_profiles[] = !empty($g['profile_picture']) ? htmlspecialchars($g['profile_picture']) : 'images/person3.png';
}

// ── Load all departments for registration form ────────────────────────────────
$dept_list = [];
$dr = $conn->query("SELECT id, code FROM departments ORDER BY code ASC");
if ($dr) while ($d = $dr->fetch_assoc()) $dept_list[] = $d;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo $event_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="css/view-event-page.css">
    <link rel="stylesheet" href="css/org-navbar.css">
    <style>
      nav.white-navbar { background: transparent; }
      nav.white-navbar a,
      nav.white-navbar a.white,
      nav.white-navbar .sign-out-link,
      nav.white-navbar .nav-icons i { color: #fff !important; }
      nav.white-navbar .profile { border: 1px solid rgba(255,255,255,0.8); }
    </style>
</head>
<body>
    <!-- HERO SECTION -->
    <section class="hero" style="background-image: linear-gradient(rgba(0,0,0,0.55), rgba(0,0,0,0.55)), url('<?php echo $hero_background; ?>');">
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
                
                </ul>
            </div>
        </nav>

        <div class="hero-content">
            <div class="hero-left">
                <img src="<?php echo $hero_image; ?>" alt="Event banner">
            </div>
            <div class="right-side">
                <h1><?php echo $event_title; ?></h1>
                <div class="people">
                    <div class="guest">
                        <span>Guest</span>
                        <div class="avatars">
                            <?php foreach ($guest_profiles as $p): ?>
                                <img src="<?php echo $p; ?>" alt="Guest">
                            <?php endforeach; ?>
                            <?php if (empty($guest_profiles)): ?><img src="images/person3.png" alt=""><?php endif; ?>
                            <p><?php echo count($guest_profiles); ?>+</p>
                        </div>
                    </div>
                    <div class="going">
                        <span>Going</span>
                        <div class="avatars">
                            <?php foreach ($going_profiles as $p): ?>
                                <img src="<?php echo $p; ?>" alt="Going">
                            <?php endforeach; ?>
                            <?php if (empty($going_profiles)): ?><img src="images/person3.png" alt=""><?php endif; ?>
                            <p><?php echo count($going_profiles); ?>+</p>
                        </div>
                    </div>
                </div>
                <div class="host">
                    <img src="<?php echo $org_logo; ?>" alt="Host logo">
                    <div>
                        <span>Hosted By</span>
                        <h3><?php echo $org_name; ?></h3>
                    </div>
                    <i class="fa-regular fa-envelope"></i>
                </div>
                <div class="event-info">
                    <div class="info-box">
                        <img src="images/calendar.png" alt="Date">
                        <p><?php echo $event_date_str; ?></p>
                    </div>
                    <div class="info-box">
                        <img src="images/time.png" alt="Time">
                        <p><?php echo $event_time_str; ?></p>
                    </div>
                    <div class="info-box">
                        <img src="images/location.png" alt="Location">
                        <p><?php echo $event_venue; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- MAIN CONTENT -->
    <section class="main-content">
        <!-- LEFT: Dynamic Ticket Container -->
        <div class="left-column">
            <div id="ticket-ui" class="ticket-scalloped-container">
                <div class="ticket-header-label">REGISTRATION TICKET</div>
                <div class="ticket-line-divider"></div>

                <div id="ticket-state-content">
                    <?php if (!$registration): ?>
                        <!-- ── STATE 1: UNREGISTERED / APPROVAL REQUIRED (image_3f5ae4.png) ── -->
                        <div class="ticket-body-layout">
                            <div class="ticket-profile-row">
                                <img src="<?php echo $profile_picture; ?>" alt="Profile">
                                <div class="ticket-profile-details">
                                    <h3><?php echo htmlspecialchars($user_full_name); ?></h3>
                                    <p><?php echo $user_email; ?></p>
                                </div>
                            </div>
                            <div class="ticket-status-text text-green">Approval required</div>
                            <div class="ticket-welcome-msg">Welcome, <?php echo explode(' ', htmlspecialchars($user_full_name))[0]; ?>! Please register to join the event.</div>
                            <button class="btn-ticket-join" onclick="openRegisterModal()">Request to Join</button>
                        </div>

                    <?php elseif ($reg_status === 'pending'): ?>
                        <!-- ── STATE 2: PENDING APPROVAL (image_3f5b08.png) ── -->
                        <div class="ticket-body-layout">
                            <div class="ticket-profile-row">
                                <img src="<?php echo $profile_picture; ?>" alt="Profile">
                                <div class="ticket-profile-details">
                                    <h3><?php echo htmlspecialchars($user_full_name); ?></h3>
                                    <p><?php echo $user_email; ?></p>
                                </div>
                            </div>
                            <div class="ticket-status-text text-teal">
                                Pending Approval <i class="fa-solid fa-arrow-rotate-right fa-spin-hover"></i>
                            </div>
                            <div class="ticket-welcome-msg">Welcome, <?php echo explode(' ', htmlspecialchars($user_full_name))[0]; ?>! We will let you know if your registration approves by the host.</div>
                            <div class="ticket-cancel-notice">No longer to attend? Notify the host by <a href="javascript:void(0)" onclick="showCancelState()">cancelling your registration.</a></div>
                        </div>

                    <?php elseif ($reg_status === 'rejected'): ?>
                        <!-- ── STATE 4: REQUEST DECLINED (image_3f5e69.png) ── -->
                        <div class="ticket-body-layout">
                            <div class="ticket-profile-row">
                                <img src="<?php echo $profile_picture; ?>" alt="Profile">
                                <div class="ticket-profile-details">
                                    <h3><?php echo htmlspecialchars($user_full_name); ?></h3>
                                    <p><?php echo $user_email; ?></p>
                                </div>
                            </div>
                            <div class="ticket-status-text text-red">
                                Request Declined <i class="fa-solid fa-circle-xmark"></i>
                            </div>
                            <div class="ticket-welcome-msg">Sorry, the host declined your request. But don't worry, there are plenty more events waiting for you!</div>
                            <div class="ticket-explore-text">Keep exploring to find the perfect event for you. <a href="index.php"><b>[Browse Other Events]</b></a></div>
                        </div>

                    <?php elseif ($reg_status === 'approved'): ?>
                        <!-- ── STATE 5: REQUEST APPROVED (image_3f5ee9.png) ── -->
                        <div class="ticket-body-layout">
                            <div class="ticket-approved-header-row">
                                <img src="<?php echo $profile_picture; ?>" alt="Profile" class="approved-avatar">
                                <div class="approved-title-block">
                                    <h2>REQUEST APPROVED <i class="fa-solid fa-circle-check text-green-check"></i></h2>
                                    <p>You're on the list. See you there!</p>
                                </div>
                            </div>
                            <div class="ticket-save-instruction">Save this ticket. Your unique QR code is required for entry.</div>
                            <div class="ticket-action-stub-box">
                                <div class="stub-brand-side">
                                    <img src="images/suni-logo-green.png" alt="SUNI" onerror="this.src='images/logo.png'">
                                </div>
                                <button class="btn-stub-view" onclick="openTicketModal()">VIEW TICKET</button>
                            </div>
                        </div>

                    <?php elseif ($reg_status === 'waitlisted'): ?>
                        <!-- ── STATE 6: WAITLISTED FALLBACK ── -->
                        <div class="ticket-body-layout">
                            <div class="ticket-profile-row">
                                <img src="<?php echo $profile_picture; ?>" alt="Profile">
                                <div class="ticket-profile-details">
                                    <h3><?php echo htmlspecialchars($user_full_name); ?></h3>
                                    <p><?php echo $user_email; ?></p>
                                </div>
                            </div>
                            <div class="ticket-status-text text-orange">Waitlisted</div>
                            <div class="ticket-welcome-msg">The event is currently full. You are on the waitlist and will be automatically added if spaces open up.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT: About -->
        <div class="right-column">
            <h2>About Event</h2>
            <hr>
            <p><?php echo nl2br($event_description); ?></p>
        </div>
    </section>

    <!-- ── Registration Form Modal ── -->
    <div class="req" id="req">
        <div class="req-inner">
            <div class="modal-close" id="modalCloseBtn">
                <i class="fa-solid fa-xmark"></i>
            </div>
            <div class="popup-header">
                <h2>Register for the Event</h2>
                <p class="modal-subtitle">Please review your details before submitting.</p>
            </div>
            <div class="popup-body">
                <div class="popup-left">
                    <img src="<?php echo $hero_image; ?>" alt="Event Image">
                </div>
                <div class="popup-right">
                    <div class="notice">
                        <i class="fa-solid fa-check"></i>
                        <span>Your details are pre-filled. Please verify before continuing.</span>
                    </div>
                    <form id="regForm">
                        <h3 class="section-title">Personal Information</h3>
                        <div class="form-row">
                            <div class="input-wrapper">
                                <label>First Name*</label>
                                <input type="text" id="firstName" name="firstName"
                                       value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                            </div>
                            <div class="input-wrapper">
                                <label>Last Name*</label>
                                <input type="text" id="lastName" name="lastName"
                                       value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="input-wrapper">
                                <label>Email*</label>
                                <input type="email" value="<?php echo $user_email; ?>" readonly style="background:#f5f5f5;color:#888;">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="input-wrapper" id="deptWrapper">
                                <label>Department*</label>
                                <div class="custom-dropdown" id="deptDropdown">
                                    <div class="dropdown-header" onclick="toggleDeptDropdown()">
                                        <span id="selectedDept"><?php echo htmlspecialchars($user_dept_code ?: 'Select Department'); ?></span>
                                        <i class='bx bx-chevron-down arrow'></i>
                                    </div>
                                    <ul class="dropdown-list" id="deptDropdownList">
                                        <?php foreach ($dept_list as $dept): ?>
                                            <li onclick="updateDropdown('<?php echo htmlspecialchars($dept['code']); ?>')">
                                                <?php echo htmlspecialchars($dept['code']); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <input type="hidden" id="selectedDeptId"
                                       value="<?php echo intval($user_dept_id ?? 0); ?>">
                            </div>
                        </div>
                        <button type="submit" class="submit-btn">
                            <?php echo $require_approval ? 'Request to Join' : 'RSVP Now'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Interactive Dashboard / Info Ticket Modal ── -->
    <div id="ticketModal">
        <div class="ticket-modal-inner">
            <span class="ticket-modal-close" onclick="closeTicketModal()">&times;</span>
            <div class="ticket-modal-header">
                <span class="ticket-label">TICKET</span>
                <h2><?php echo $event_title; ?></h2>
                <p class="event-details">
                    <?php echo $event_date_str; ?>, <?php echo $event_time_str; ?><br>
                    <?php echo $event_venue; ?>
                </p>
            </div>
            <div class="ticket-modal-body">
                <img src="<?php echo $profile_picture; ?>" class="ticket-modal-avatar"
                     alt="<?php echo htmlspecialchars($user_full_name); ?>"
                     onerror="this.src='images/person3.png'">
                <div class="ticket-modal-info">
                    <p>Present at entrance</p>
                    <hr style="margin:4px 0; border-color:#eee;">
                    <strong><?php echo htmlspecialchars($user_full_name); ?></strong>
                    <p><?php echo $user_email; ?></p>
                    <span class="ticket-status-badge-inline <?php
                        if ($reg_attendance === 'going') echo 'badge-going';
                        elseif ($reg_attendance === 'not_going') echo 'badge-not-going';
                        else echo 'badge-approved';
                    ?>" id="ticket-modal-attend-badge">
                        <?php
                        if ($reg_attendance === 'going') echo '✔ Going';
                        elseif ($reg_attendance === 'not_going') echo '✖ Not Going';
                        else echo '● Unconfirmed';
                        ?>
                    </span>
                </div>
            </div>
            <div class="ticket-modal-attendance">
                <p>Update your attendance status:</p>
                <div class="attendance-btn-row">
                    <button class="btn-going-select <?php echo ($reg_attendance === 'going') ? 'active' : ''; ?>"
                            id="modal-btn-going" onclick="setAttendance('going')">
                        <i class="fa-solid fa-thumbs-up"></i> Going
                    </button>
                    <button class="btn-not-going-select <?php echo ($reg_attendance === 'not_going') ? 'active' : ''; ?>"
                            id="modal-btn-not-going" onclick="setAttendance('not_going')">
                        <i class="fa-solid fa-thumbs-down"></i> Not Going
                    </button>
                </div>
            </div>
            <div class="ticket-modal-footer">
                <button class="btn-ticket-directions">
                    <i class="fa fa-location-arrow"></i> Directions
                </button>
                <button class="btn-ticket-save" onclick="window.print()">
                    <i class="fa fa-download"></i> Save Ticket
                </button>
            </div>
        </div>
    </div>

    <script src="js/navbar.js"></script>
    <script>
    const EVENT_ID = <?php echo intval($event_id); ?>;
    const FIRST_NAME = <?php echo json_encode(explode(' ', htmlspecialchars($user_full_name))[0]); ?>;
    const PROFILE_PICTURE = <?php echo json_encode($profile_picture); ?>;
    const USER_EMAIL = <?php echo json_encode($user_email); ?>;
    const USER_FULL_NAME = <?php echo json_encode($user_full_name); ?>;

    async function postAction(payload) {
        const fd = new FormData();
        Object.entries(payload).forEach(([k, v]) => fd.append(k, v));
        const res = await fetch(window.location.href.split('?')[0] + '?event_id=' + EVENT_ID, {
            method: 'POST', body: fd
        });
        return res.json();
    }

    function openRegisterModal() {
        document.getElementById('req').classList.add('open');
    }

    document.getElementById('modalCloseBtn').addEventListener('click', () => {
        document.getElementById('req').classList.remove('open');
    });

    document.getElementById('regForm').addEventListener('submit', function(e) {
        e.preventDefault();
        document.getElementById('req').classList.remove('open');
        postAction({ ajax_action: 'register' }).then(r => {
            if (!r.ok) { alert('Error: ' + (r.msg || 'Unknown error')); return; }
            
            // IF THE STATE RETURNED IS PENDING, FORCIBLY RENDER THE PENDING UI FRAME IMMEDIATELY BEFORE RELOAD FOR A SMOOTH TRANSITION
            if(r.status === 'pending') {
                const container = document.getElementById('ticket-state-content');
                container.innerHTML = `
                    <div class="ticket-body-layout">
                        <div class="ticket-profile-row">
                            <img src="${PROFILE_PICTURE}" alt="Profile">
                            <div class="ticket-profile-details">
                                <h3>\${USER_FULL_NAME}</h3>
                                <p>\${USER_EMAIL}</p>
                            </div>
                        </div>
                        <div class="ticket-status-text text-teal">
                            Pending Approval <i class="fa-solid fa-arrow-rotate-right fa-spin-hover"></i>
                        </div>
                        <div class="ticket-welcome-msg">Welcome, \${FIRST_NAME}! We will let you know if your registration approves by the host.</div>
                        <div class="ticket-cancel-notice">No longer to attend? Notify the host by <a href="javascript:void(0)" onclick="showCancelState()">cancelling your registration.</a></div>
                    </div>
                `;
            }
            
            // Reload window to lock states cleanly with session/database updates
            window.location.reload();
        });
    });

    function showCancelState() {
        const container = document.getElementById('ticket-state-content');
        container.innerHTML = `
            <div class="ticket-body-layout text-center">
                <div class="ticket-cancel-headline">
                    Cancel Registration <i class="fa-solid fa-circle-xmark text-red-icon"></i>
                </div>
                <div class="ticket-cancel-body-msg">
                    Click Confirm to cancel your registration. We'll let the host notified about your cancellation.
                </div>
                <div class="ticket-cancel-buttons-wrapper">
                    <button class="btn-confirm-cancel" onclick="confirmCancellation()">Confirm</button>
                    <button class="btn-dismiss-cancel" onclick="window.location.reload()">Dismiss</button>
                </div>
            </div>
        `;
    }

    function confirmCancellation() {
        postAction({ ajax_action: 'cancel_registration' }).then(r => {
            if (r.ok) {
                window.location.reload();
            } else {
                alert('Error: ' + (r.msg || 'Could not cancel execution.'));
            }
        });
    }

    function setAttendance(value) {
        postAction({ ajax_action: 'update_attendance', attendance: value }).then(r => {
            if (!r.ok) { alert('Error updating attendance.'); return; }
            const badge = document.getElementById('ticket-modal-attend-badge');
            if (badge) {
                badge.className = 'ticket-status-badge-inline ' + (value === 'going' ? 'badge-going' : 'badge-not-going');
                badge.textContent = value === 'going' ? '✔ Going' : '✖ Not Going';
            }
            const mGoing = document.getElementById('modal-btn-going');
            const mNotGoing = document.getElementById('modal-btn-not-going');
            if (mGoing) mGoing.classList.toggle('active', value === 'going');
            if (mNotGoing) mNotGoing.classList.toggle('active', value === 'not_going');
        });
    }

    function openTicketModal() { document.getElementById('ticketModal').classList.add('open'); }
    function closeTicketModal() { document.getElementById('ticketModal').classList.remove('open'); }
    
    function toggleDeptDropdown() { document.getElementById('deptDropdown').classList.toggle('open'); }
    function updateDropdown(code) {
        document.getElementById('selectedDept').textContent = code;
        document.getElementById('deptDropdown').classList.remove('open');
    }
    </script>
</body>
</html>