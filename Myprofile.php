<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit;
}

$is_admin = $_SESSION['is_admin'] ?? false;
$user_id = $_SESSION['user_id'];
$profile_picture = 'images/person3.png';
$user_full_name = 'My Profile';
$user_department_name = 'College / Department not set';
$user_bio = '';

$stmt = $conn->prepare('SELECT u.first_name, u.last_name, u.email, u.profile_picture, u.bio, d.name AS department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if ($user) {
    $user_full_name = trim($user['first_name'] . ' ' . $user['last_name']);
    if (empty($user_full_name)) {
        $user_full_name = 'My Profile';
    }
    if (!empty($user['department_name'])) {
        $user_department_name = $user['department_name'];
    }
    if (!empty($user['profile_picture'])) {
        $profile_picture = htmlspecialchars($user['profile_picture']);
    }
    if (!empty($user['bio'])) {
        $user_bio = htmlspecialchars($user['bio']);
    }
}

$attended_stmt = $conn->prepare('SELECT COUNT(DISTINCT r.event_id) AS cnt FROM registrations r LEFT JOIN attendance a ON r.id = a.registration_id WHERE r.user_id = ? AND ((a.checked_in_at IS NOT NULL) OR r.attendance_confirmation = ?)');
$attendance_value = 'going';
$attended_stmt->bind_param('is', $user_id, $attendance_value);
$attended_stmt->execute();
$attended_result = $attended_stmt->get_result();
$attended_count = 0;
if ($row = $attended_result->fetch_assoc()) {
    $attended_count = (int)$row['cnt'];
}

$tickets = [];
$ticket_stmt = $conn->prepare('SELECT e.id, e.title, e.start_datetime, e.venue, r.status, r.attendance_confirmation FROM registrations r JOIN events e ON r.event_id = e.id WHERE r.user_id = ? ORDER BY e.start_datetime DESC LIMIT 3');
$ticket_stmt->bind_param('i', $user_id);
$ticket_stmt->execute();
$ticket_result = $ticket_stmt->get_result();
while ($ticket = $ticket_result->fetch_assoc()) {
    $tickets[] = $ticket;
}

$upcoming_events = [];
$upcoming_stmt = $conn->prepare('SELECT e.id, e.title, e.start_datetime, e.venue, e.event_banner, e.cover_photo, o.name AS org_name FROM registrations r JOIN events e ON r.event_id = e.id JOIN organizations o ON e.organization_id = o.id WHERE r.user_id = ? AND e.start_datetime >= NOW() ORDER BY e.start_datetime ASC LIMIT 8');
$upcoming_stmt->bind_param('i', $user_id);
$upcoming_stmt->execute();
$upcoming_result = $upcoming_stmt->get_result();
while ($event = $upcoming_result->fetch_assoc()) {
    $upcoming_events[] = $event;
}

$attended_events = [];
$attended_events_stmt = $conn->prepare('SELECT e.id, e.title, e.start_datetime, e.venue, e.event_banner, e.cover_photo, o.name AS org_name FROM registrations r JOIN events e ON r.event_id = e.id JOIN organizations o ON e.organization_id = o.id LEFT JOIN attendance a ON r.id = a.registration_id WHERE r.user_id = ? AND ((a.checked_in_at IS NOT NULL) OR r.attendance_confirmation = ?) ORDER BY e.start_datetime DESC LIMIT 8');
$attended_events_stmt->bind_param('is', $user_id, $attendance_value);
$attended_events_stmt->execute();
$attended_events_result = $attended_events_stmt->get_result();
while ($event = $attended_events_result->fetch_assoc()) {
    $attended_events[] = $event;
}

function format_event_date($dateString)
{
    $timestamp = strtotime($dateString);
    if (!$timestamp) {
        return 'TBA';
    }
    return date('M j, Y • g:i A', $timestamp);
}

define('DEFAULT_EVENT_IMAGE', 'images/cover.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/Myprofile.css">
</head>
<body>
    <nav>
        <a href="index.php">
            <img src="images/logo.png" alt="Suni Logo">
        </a>
        <ul>
            <?php if ($is_admin): ?>
            <li><a href="create-events.php">+ Create Event</a></li>
            <?php endif; ?>
            <li><a href="index.php">CvSU Events</a></li>
            <li><a href="Myprofile.php" class="active">My Profile</a></li>
            <?php if ($is_admin): ?>
            <li><a href="dashboard.php">Organization Dashboard</a></li>
            <?php endif; ?>
            <li class="nav-icons">
                <i class="fa-solid fa-magnifying-glass"></i>
                <i class="fa-regular fa-bell fa-lg"></i>
            </li>
            <li><img src="<?php echo htmlspecialchars($profile_picture); ?>" class="profile" alt="Profile"></li>
        </ul>
    </nav>

    <section class="profile-header-card">
        <div class="banner-background"></div>
        <div class="profile-info-wrapper">
            <div class="profile-avatar-container">
                <div class="avatar-placeholder">
                    <img src="<?php echo $profile_picture; ?>" alt="Profile photo">
                </div>
                <form action="update_avatar.php" method="POST" enctype="multipart/form-data" class="avatar-edit-form">
                    <label for="avatar-input" class="edit-avatar-btn"><i class="fa-solid fa-camera"></i></label>
                    <input type="file" id="avatar-input" name="profile_img" onchange="this.form.submit()" style="display: none;">
                </form>
            </div>

            <div class="profile-text-details">
                <h1 class="user-name"><?php echo htmlspecialchars($user_full_name); ?></h1>
                <p class="user-college"><?php echo htmlspecialchars($user_department_name); ?></p>
                <div class="bio-container">
                    <form action="update_bio.php" method="POST" class="bio-form">
                        <span class="bio-text"><?php echo $user_bio ?: 'Add bio'; ?></span>
                        <button type="button" class="edit-bio-btn"><i class="fa-regular fa-pen-to-square"></i></button>
                        <input type="text" name="bio" class="bio-input hidden" value="<?php echo htmlspecialchars($user_bio); ?>" placeholder="Write something about yourself..." maxlength="150">
                    </form>
                </div>
                <div class="stats-badge">
                    <span class="stats-num"><?php echo $attended_count; ?></span>
                    <span class="stats-label">Events Attended</span>
                </div>
            </div>
        </div>
    </section>

    <main class="profile-container">
        <div class="dashboard-grid">
            <div class="main-left-column">
                <section class="dashboard-panel ticket-status-center">
                    <div class="panel-header">
                        <h2>Ticket Status Center</h2>
                        <a href="all-tickets.php" class="view-all-link">View All Tickets</a>
                    </div>
                    <div class="tickets-flex-row">
                        <?php if (!empty($tickets)): ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <?php
                                    $status_label = ucfirst($ticket['status']);
                                    $attendance_info = $ticket['attendance_confirmation'] === 'going' ? 'Attended' : ucfirst($ticket['attendance_confirmation']);
                                    $ticket_date = format_event_date($ticket['start_datetime']);
                                ?>
                                <div class="ticket-card">
                                    <div class="ticket-img-blank"></div>
                                    <div class="ticket-body">
                                        <h3 class="ticket-title"><?php echo htmlspecialchars($ticket['title']); ?></h3>
                                        <p class="ticket-date">Event Date<br><span><?php echo htmlspecialchars($ticket_date); ?></span></p>
                                        <div class="ticket-meta-grid">
                                            <p class="status-indicator <?php echo htmlspecialchars($ticket['status']); ?>">Status <span><?php echo htmlspecialchars($status_label); ?></span></p>
                                            <p class="checkin-indicator">Check-in <span><?php echo htmlspecialchars($attendance_info); ?></span></p>
                                        </div>
                                        <div class="ticket-action-row">
                                            <a href="ticket-details.php?id=<?php echo urlencode($ticket['id']); ?>" class="btn btn-primary">View Ticket</a>
                                            <button class="btn btn-icon"><i class="fa-solid fa-qrcode"></i></button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="ticket-card" style="flex:1; min-width:100%;">
                                <div class="ticket-body">
                                    <h3 class="ticket-title">No tickets yet</h3>
                                    <p class="ticket-date">Attend events or register for a ticket to see it here.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="dashboard-panel events-tabs-panel">
                    <div class="tabs-navigation-bar">
                        <button class="tab-btn active" data-tab="upcoming">Upcoming Events</button>
                        <button class="tab-btn" data-tab="attended">Attended Events</button>
                        <button class="tab-btn" data-tab="saved">Saved Events</button>
                        <button class="tab-btn" data-tab="certificates">Certificates</button>
                    </div>

                    <div class="tab-content active" id="upcoming">
                        <div class="events-grid-row">
                            <?php if (!empty($upcoming_events)): ?>
                                <?php foreach ($upcoming_events as $event): ?>
                                    <?php $event_image = htmlspecialchars($event['event_banner'] ?: $event['cover_photo'] ?: DEFAULT_EVENT_IMAGE); ?>
                                    <div class="event-display-card">
                                        <div class="event-banner-blank" style="background-image:url('<?php echo $event_image; ?>'); background-size:cover; background-position:center;">
                                            <span class="event-badge-date"><strong><?php echo date('j', strtotime($event['start_datetime'])); ?></strong><br><?php echo strtoupper(date('M Y', strtotime($event['start_datetime']))); ?></span>
                                        </div>
                                        <div class="event-display-body">
                                            <h4 class="event-display-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                                            <p class="event-display-info"><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($event['org_name']); ?></p>
                                            <p class="event-display-info"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($event['venue'] ?: 'No venue'); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="event-display-none">
                                    <button type="button" class="join-event-btn">
                                        <span class="plus-icon">+</span> No upcoming registered events
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-content" id="attended">
                        <div class="events-grid-row">
                            <?php if (!empty($attended_events)): ?>
                                <?php foreach ($attended_events as $event): ?>
                                    <?php $event_image = htmlspecialchars($event['event_banner'] ?: $event['cover_photo'] ?: DEFAULT_EVENT_IMAGE); ?>
                                    <div class="event-display-card">
                                        <div class="event-banner-blank" style="background-image:url('<?php echo $event_image; ?>'); background-size:cover; background-position:center;">
                                            <span class="event-badge-date"><strong><?php echo date('j', strtotime($event['start_datetime'])); ?></strong><br><?php echo strtoupper(date('M Y', strtotime($event['start_datetime']))); ?></span>
                                        </div>
                                        <div class="event-display-body">
                                            <h4 class="event-display-title"><?php echo htmlspecialchars($event['title']); ?></h4>
                                            <p class="event-display-info"><i class="fa-regular fa-user"></i> <?php echo htmlspecialchars($event['org_name']); ?></p>
                                            <p class="event-display-info"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($event['venue'] ?: 'No venue'); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="event-display-none">
                                    <button type="button" class="join-event-btn">
                                        <span class="plus-icon">+</span> No attended events yet
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-content" id="saved">
                        <div class="events-grid-row">
                            <div class="event-display-none">
                                <button type="button" class="join-event-btn">
                                    <span class="plus-icon">+</span> No saved events yet
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="certificates">
                        <div class="events-grid-row">
                            <div class="event-display-none">
                                <button type="button" class="join-event-btn">
                                    <span class="plus-icon">+</span> No certificates available
                                </button>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="main-right-column">
                <section class="dashboard-panel updates-panel">
                    <div class="panel-header">
                        <h2>Notifications</h2>
                        <a href="notifications.php" class="view-all-link">View All</a>
                    </div>
                    <div class="empty-feed-container">
                        <p class="empty-feed-text">Your feed is quiet. We'll let you know when an event updates.</p>
                        <a href="notifications.php" class="action-footer-link">View All Notifications <i class="fa-solid fa-chevron-right"></i></a>
                    </div>
                </section>

                <section class="dashboard-panel updates-panel">
                    <div class="panel-header">
                        <h2>Activity Timeline</h2>
                        <a href="timeline.php" class="view-all-link">View All</a>
                    </div>
                    <div class="timeline-stream">
                        <div class="timeline-node">
                            <div class="node-marker status-green"></div>
                            <div class="node-content">
                                <span class="node-date">Most recent attended event</span>
                                <p class="node-action">Your attended events are listed in the tabs.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const target = btn.getAttribute('data-tab');
                tabButtons.forEach(item => item.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(target)?.classList.add('active');
            });
        });

        const editBioBtn = document.querySelector('.edit-bio-btn');
        const bioText = document.querySelector('.bio-text');
        const bioInput = document.querySelector('.bio-input');
        const bioForm = document.querySelector('.bio-form');

        if (editBioBtn && bioText && bioInput && bioForm) {
            editBioBtn.addEventListener('click', () => {
                bioInput.classList.toggle('hidden');
                if (!bioInput.classList.contains('hidden')) {
                    bioInput.focus();
                }
            });
            bioInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    bioForm.submit();
                }
            });
        }
    </script>
</body>
</html>
