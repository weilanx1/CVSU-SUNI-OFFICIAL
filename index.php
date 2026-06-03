<?php
session_start();
require_once 'db.php';

$is_admin = $_SESSION['is_admin'] ?? false;
$profile_picture = 'images/person3.png';
$user_id = $_SESSION['user_id'] ?? null;
$user_department_id = null;
$user_org_ids = [];

if ($user_id) {
    $stmt = $conn->prepare('SELECT profile_picture, department_id FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user) {
        if (!empty($user['profile_picture'])) {
            $profile_picture = htmlspecialchars($user['profile_picture']);
        }
        $user_department_id = $user['department_id'];
    }

    $org_stmt = $conn->prepare('SELECT organization_id FROM organization_admins WHERE user_id = ? UNION SELECT id FROM organizations WHERE main_admin_id = ?');
    $org_stmt->bind_param('ii', $user_id, $user_id);
    $org_stmt->execute();
    $org_result = $org_stmt->get_result();
    while ($row = $org_result->fetch_assoc()) {
        $user_org_ids[] = (int)$row['organization_id'];
    }
    $user_org_ids = array_unique($user_org_ids);
}

$events = [];
$event_sql = "SELECT e.*, o.name AS org_name, o.logo AS org_logo, COALESCE((SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id), 0) AS attendee_count FROM events e JOIN organizations o ON e.organization_id = o.id WHERE e.status = 'published' ORDER BY e.start_datetime ASC";
$event_result = $conn->query($event_sql);
while ($row = $event_result->fetch_assoc()) {
    $events[] = $row;
}

$event_department_map = [];
if (!empty($events)) {
    $event_ids = array_map(function ($event) {
        return (int)$event['id'];
    }, $events);
    $id_list = implode(',', $event_ids);
    $dept_result = $conn->query("SELECT event_id, department_id FROM event_departments WHERE event_id IN ($id_list)");
    while ($row = $dept_result->fetch_assoc()) {
        $event_department_map[(int)$row['event_id']][] = (int)$row['department_id'];
    }
}

function can_view_event($event, $user_id, $user_department_id, $user_org_ids, $event_department_map)
{
    if ($event['visibility'] === 'public') {
        return true;
    }

    if (!$user_id) {
        return false;
    }

    if ($event['created_by'] == $user_id) {
        return true;
    }

    if (in_array((int)$event['organization_id'], $user_org_ids, true)) {
        return true;
    }

    switch ($event['visibility']) {
        case 'organization_only':
            return in_array((int)$event['organization_id'], $user_org_ids, true);
        case 'department_only':
        case 'restricted':
            return $user_department_id && isset($event_department_map[(int)$event['id']]) && in_array((int)$user_department_id, $event_department_map[(int)$event['id']], true);
        case 'private':
            return false;
        default:
            return false;
    }
}

$visible_events = [];
foreach ($events as $event) {
    if (can_view_event($event, $user_id, $user_department_id, $user_org_ids, $event_department_map)) {
        $visible_events[] = $event;
    }
}

usort($visible_events, function ($a, $b) {
    return $b['attendee_count'] <=> $a['attendee_count'];
});

$featured_event = $visible_events[0] ?? null;
$popular_events = array_slice($visible_events, 0, 4);

$organizations = [];
$org_query = $conn->query('SELECT id, name, logo FROM organizations ORDER BY name ASC');
while ($org = $org_query->fetch_assoc()) {
    $organizations[] = $org;
}

$org_event_groups = [];
foreach ($visible_events as $event) {
    $org_id = (int)$event['organization_id'];
    if (!isset($org_event_groups[$org_id])) {
        $org_event_groups[$org_id] = [
            'org_id' => $org_id,
            'org_name' => $event['org_name'],
            'org_logo' => $event['org_logo'],
            'events' => [],
        ];
    }
    if (count($org_event_groups[$org_id]['events']) < 4) {
        $org_event_groups[$org_id]['events'][] = $event;
    }
}

$org_event_groups = array_values($org_event_groups);
$org_event_groups = array_slice($org_event_groups, 0, 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CvSU Events</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/index.css">
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
            <li><a href="index.php" class="active">CvSU Events</a></li>
            <li><a href="Myprofile.php">My Profile</a></li>
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

    <section class="main">
        <img src="<?php echo htmlspecialchars($featured_event['cover_photo'] ?: $featured_event['event_banner'] ?: 'images/cover.png'); ?>" class="cover-photo" alt="Cover">
        <div class="overlay"></div>
        <div class="container">
            <div class="main-content">
                <div class="participants">
                    <i class="fa-solid fa-users"></i>
                    <span><?php echo $featured_event ? (int)$featured_event['attendee_count'] : 0; ?></span>
                </div>
                <h1><?php echo $featured_event ? htmlspecialchars($featured_event['title']) : 'Browse CvSU Events'; ?></h1>
                <p class="hosted"><?php echo $featured_event ? 'Hosted By' : 'Featured Organizations'; ?></p>
                <p class="organization"><?php echo $featured_event ? htmlspecialchars($featured_event['org_name']) : 'Campus-wide community'; ?></p>
                <div class="main-buttons">
                    <?php if ($featured_event): ?>
                        <a href="view-event-page.php?event_id=<?php echo urlencode($featured_event['id']); ?>" class="join-btn">View Event</a>
                    <?php else: ?>
                        <button class="join-btn" disabled>No events available</button>
                    <?php endif; ?>
                    <button class="info-btn" type="button"><i class="fa-solid fa-info"></i></button>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <section class="organizations">
            <div class="arrow-btn"><i class="fa-solid fa-chevron-left"></i></div>
            <?php if (!empty($organizations)): ?>
                <?php foreach ($organizations as $org): ?>
                    <div class="org-card">
                        <img src="<?php echo htmlspecialchars($org['logo'] ?: 'images/cover.png'); ?>" alt="<?php echo htmlspecialchars($org['name']); ?>">
                        <p><?php echo htmlspecialchars($org['name']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="org-card">
                    <img src="images/cover.png" alt="Org">
                    <p>No organizations yet</p>
                </div>
            <?php endif; ?>
            <div class="arrow-btn"><i class="fa-solid fa-chevron-right"></i></div>
        </section>
    </div>

    <div class="container">
        <section class="events-section">
            <div class="tabs">
                <div class="tab">Popular Events</div>
                <div class="tab">Other Events</div>
            </div>
            <?php if (!empty($popular_events)): ?>
                <div class="event-grid">
                    <?php foreach ($popular_events as $event): ?>
                        <?php $event_image = htmlspecialchars($event['event_banner'] ?: $event['cover_photo'] ?: 'images/cover.png'); ?>
                        <a class="event-card" href="view-event-page.php?event_id=<?php echo urlencode($event['id']); ?>">
                            <img src="<?php echo $event_image; ?>" alt="Event">
                            <div class="event-overlay">
                                <div class="event-top">
                                    <span><i class="fa-solid fa-users"></i> <?php echo (int)$event['attendee_count']; ?></span>
                                    <span><?php echo htmlspecialchars(ucfirst($event['visibility'])); ?></span>
                                </div>
                                <div>
                                    <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                    <div class="event-sub"><?php echo htmlspecialchars($event['org_name']); ?></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="event-grid">
                    <div class="event-card" style="background:#f7f7f7; display:flex; justify-content:center; align-items:center;">
                        <div style="padding:20px; text-align:center; color:#555;">No events found. Check back later.</div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="container">
        <?php if (!empty($org_event_groups)): ?>
            <?php foreach ($org_event_groups as $org_group): ?>
                <div class="org-host-header">
                    <div class="org-host-left">
                        <img src="<?php echo htmlspecialchars($org_group['org_logo'] ?: 'images/cover.png'); ?>" alt="<?php echo htmlspecialchars($org_group['org_name']); ?> Logo" class="host-avatar">
                        <div class="host-text-details">
                            <span class="host-label">Hosted by the</span>
                            <h2><?php echo htmlspecialchars($org_group['org_name']); ?></h2>
                        </div>
                    </div>
                    <button class="show-all-btn" type="button">Show All</button>
                </div>
                <section class="secondary-events-section">
                    <div class="event-grid-scroll">
                        <?php foreach ($org_group['events'] as $event): ?>
                            <?php $event_image = htmlspecialchars($event['event_banner'] ?: $event['cover_photo'] ?: 'images/cover.png'); ?>
                            <a href="view-event-page.php?event_id=<?php echo urlencode($event['id']); ?>" class="scroll-card">
                                <div class="card-media">
                                    <img src="<?php echo $event_image; ?>" alt="Event">
                                    <div class="card-badge"><i class="fa-solid fa-users"></i> <?php echo (int)$event['attendee_count']; ?></div>
                                    <button class="card-join-glass" type="button">View Event</button>
                                </div>
                                <div class="card-info">
                                    <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                    <p class="card-date"><?php echo htmlspecialchars(date('M j, Y', strtotime($event['start_datetime']))); ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="scroll-arrow-right"><i class="fa-solid fa-chevron-right"></i></div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>