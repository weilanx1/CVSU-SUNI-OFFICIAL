<?php
session_start();
require_once 'db.php';

$is_admin = $_SESSION['is_admin'] ?? false;
$default_avatar = 'images/person3.png';
$default_cover  = 'images/cover.png';
$profile_picture = $default_avatar;
$user_id            = $_SESSION['user_id'] ?? null;
$user_department_id = null;
$user_account_type  = 'guest';   // treat unknown sessions as guest
$user_org_ids       = [];

if ($user_id) {
    // Fetch account_type + department_id — both needed for visibility checks
    $stmt = $conn->prepare(
        'SELECT profile_picture, department_id, account_type FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        $db_picture = trim($user['profile_picture'] ?? '');
        if (!empty($db_picture) && strtolower($db_picture) !== 'null') {
            $profile_picture = (file_exists($db_picture) && is_file($db_picture))
                ? htmlspecialchars($db_picture)
                : $default_avatar;
        }
        $user_department_id = isset($user['department_id']) ? (int)$user['department_id'] : null;
        $user_account_type  = $user['account_type'] ?? 'guest';
    }

    // Collect every org this user administers (main admin OR org_admins table)
    $org_stmt = $conn->prepare(
        'SELECT organization_id AS oid FROM organization_admins WHERE user_id = ?
         UNION
         SELECT id AS oid FROM organizations WHERE main_admin_id = ?'
    );
    $org_stmt->bind_param('ii', $user_id, $user_id);
    $org_stmt->execute();
    $org_res = $org_stmt->get_result();
    while ($row = $org_res->fetch_assoc()) {
        $user_org_ids[] = (int)$row['oid'];
    }
    $user_org_ids = array_unique($user_org_ids);
}

// Fetch all published events; include org's department_id for department_only check
$events = [];
$event_sql =
    "SELECT e.*,
            o.name        AS org_name,
            o.logo        AS org_logo,
            o.department_id AS org_department_id,
            COALESCE((SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id), 0) AS attendee_count
     FROM   events e
     JOIN   organizations o ON e.organization_id = o.id
     WHERE  e.status = 'published'
     ORDER  BY e.start_datetime ASC";

$event_result = $conn->query($event_sql);
if ($event_result) {
    while ($row = $event_result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Build event_id → [department_id, ...] map from event_departments table (used for 'restricted')
$event_department_map = [];
if (!empty($events)) {
    $event_ids = array_map(fn($e) => (int)$e['id'], $events);
    $id_list   = implode(',', $event_ids);
    $dept_result = $conn->query(
        "SELECT event_id, department_id FROM event_departments WHERE event_id IN ($id_list)"
    );
    if ($dept_result) {
        while ($row = $dept_result->fetch_assoc()) {
            $event_department_map[(int)$row['event_id']][] = (int)$row['department_id'];
        }
    }
}

/**
 * Visibility rules (matching the actual DB ENUM):
 *
 *  public          → everyone, including unauthenticated guests
 *  department_only → must be a logged-in CvSU account AND user's department_id
 *                    matches the ORGANIZATION's department_id
 *  restricted      → must be logged-in AND user's department_id appears in
 *                    event_departments for this event
 *
 * Overrides (always granted before visibility check):
 *  • Org admins / main admins always see events of their own organization
 *  • The event creator always sees their own event
 */
function can_view_event(
    array $event,
    ?int  $user_id,
    ?int  $user_department_id,
    string $user_account_type,
    array $user_org_ids,
    array $event_department_map
): bool {
    $visibility      = $event['visibility'] ?? 'public';
    $event_id        = (int)($event['id'] ?? 0);
    $org_id          = (int)($event['organization_id'] ?? 0);
    $org_dept_id     = isset($event['org_department_id']) ? (int)$event['org_department_id'] : null;
    $created_by      = (int)($event['created_by'] ?? 0);

    // ── Public: visible to absolutely everyone ──────────────────────────────
    if ($visibility === 'public') {
        return true;
    }

    // ── All other visibility levels require a logged-in user ────────────────
    if (!$user_id) {
        return false;
    }

    // ── Org admin / main admin override: always see own org's events ────────
    if (in_array($org_id, $user_org_ids, true)) {
        return true;
    }

    // ── Event creator override ───────────────────────────────────────────────
    if ($created_by === $user_id) {
        return true;
    }

    // ── department_only ──────────────────────────────────────────────────────
    // Only CvSU-account users whose department matches the org's department
    if ($visibility === 'department_only') {
        if ($user_account_type !== 'cvsu') {
            return false;                       // guest accounts never qualify
        }
        if ($user_department_id === null || $org_dept_id === null) {
            return false;                       // no department assigned → no access
        }
        return $user_department_id === $org_dept_id;
    }

    // ── restricted ───────────────────────────────────────────────────────────
    // User's department must appear in event_departments for this event
    if ($visibility === 'restricted') {
        if ($user_account_type !== 'cvsu') {
            return false;                       // guest accounts never qualify
        }
        if ($user_department_id === null) {
            return false;
        }
        $allowed_depts = $event_department_map[$event_id] ?? [];
        return in_array($user_department_id, $allowed_depts, true);
    }

    // Fallback: deny unknown visibility values
    return false;
}

// Filter events the current viewer is allowed to see
$visible_events = [];
foreach ($events as $event) {
    if (can_view_event(
        $event,
        $user_id,
        $user_department_id,
        $user_account_type,
        $user_org_ids,
        $event_department_map
    )) {
        $visible_events[] = $event;
    }
}

// Sort by attendee count descending (most popular first)
usort($visible_events, fn($a, $b) => ($b['attendee_count'] ?? 0) <=> ($a['attendee_count'] ?? 0));

$featured_event  = $visible_events[0] ?? null;
$popular_events  = array_slice($visible_events, 0, 4);

// Organizations list (for the org strip)
$organizations = [];
$org_query = $conn->query('SELECT id, name, logo FROM organizations ORDER BY name ASC');
if ($org_query) {
    while ($org = $org_query->fetch_assoc()) {
        $organizations[] = $org;
    }
}

// Group visible events by organization (up to 3 orgs, 4 events each)
$org_event_groups = [];
foreach ($visible_events as $event) {
    $org_id = (int)($event['organization_id'] ?? 0);
    if ($org_id <= 0) continue;

    if (!isset($org_event_groups[$org_id])) {
        $org_event_groups[$org_id] = [
            'org_id'   => $org_id,
            'org_name' => $event['org_name'] ?? 'Unknown Organization',
            'org_logo' => $event['org_logo'] ?? '',
            'events'   => [],
        ];
    }
    if (count($org_event_groups[$org_id]['events']) < 4) {
        $org_event_groups[$org_id]['events'][] = $event;
    }
}
$org_event_groups = array_slice(array_values($org_event_groups), 0, 3);

// Build carousel data (JS-safe)
$carousel_events = [];
foreach ($visible_events as $ev) {
    $img = $default_cover;
    $raw = (!empty($ev['cover_photo'])   && strtolower($ev['cover_photo'])   !== 'null') ? $ev['cover_photo']
         : ((!empty($ev['event_banner']) && strtolower($ev['event_banner'])  !== 'null') ? $ev['event_banner'] : '');
    if (!empty($raw) && file_exists($raw) && is_file($raw)) {
        $img = $raw;
    }
    $carousel_events[] = [
        'id'             => (int)$ev['id'],
        'title'          => $ev['title'] ?? '',
        'org_name'       => $ev['org_name'] ?? '',
        'attendee_count' => (int)($ev['attendee_count'] ?? 0),
        'image'          => $img,
        'url'            => 'view-event-page.php?event_id=' . urlencode($ev['id'] ?? ''),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CvSU Events</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/sticky-nav.css">
    <link rel="stylesheet" href="css/index.css">

    <style>
        .featured-carousel {
            position: absolute;
            inset: 0;
            overflow: hidden;
        }
        .featured-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 0.7s ease, transform 0.7s ease;
            transform: scale(1.04);
            pointer-events: none;
        }
        .featured-slide.active {
            opacity: 1;
            transform: scale(1);
            pointer-events: auto;
        }
        .featured-slide.exit-left {
            opacity: 0;
            transform: translateX(-3%) scale(1.02);
        }
        .featured-slide.exit-right {
            opacity: 0;
            transform: translateX(3%) scale(1.02);
        }
        .featured-slide .cover-photo {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .featured-slide .overlay {
            position: absolute;
            inset: 0;
        }
        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 20;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }
        .carousel-nav:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-50%) scale(1.1);
        }
        .carousel-nav.prev { left: 18px; }
        .carousel-nav.next { right: 18px; }
        .carousel-dots {
            position: absolute;
            bottom: 18px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 7px;
            z-index: 20;
        }
        .carousel-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(255,255,255,0.45);
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }
        .carousel-dot.active {
            background: #fff;
            transform: scale(1.3);
        }
        .main { position: relative; }
        .main > .container { position: relative; z-index: 10; }
        .main > .overlay   { display: none; }
    </style>
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
            <li><a href="org-profile.php">Organization Dashboard</a></li>
            <?php endif; ?>
            <li class="nav-icons">
                <i class="fa-solid fa-magnifying-glass"></i>
                <i class="fa-regular fa-bell fa-lg"></i>
            </li>
            <li><img src="<?php echo $profile_picture; ?>" class="profile" alt="Profile"></li>
        </ul>
    </nav>

    <section class="main">
        <div class="featured-carousel" id="featuredCarousel">
            <?php foreach ($carousel_events as $i => $cev): ?>
                <div class="featured-slide" data-index="<?php echo $i; ?>">
                    <img src="<?php echo htmlspecialchars($cev['image']); ?>" class="cover-photo" alt="Cover">
                    <div class="overlay"></div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($carousel_events)): ?>
                <div class="featured-slide active">
                    <img src="<?php echo htmlspecialchars($default_cover); ?>" class="cover-photo" alt="Cover">
                    <div class="overlay"></div>
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($carousel_events) > 1): ?>
        <button class="carousel-nav prev" id="featPrev" aria-label="Previous event">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button class="carousel-nav next" id="featNext" aria-label="Next event">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
        <div class="carousel-dots" id="featDots">
            <?php foreach ($carousel_events as $i => $cev): ?>
                <div class="carousel-dot" data-index="<?php echo $i; ?>"></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="container">
            <div class="main-content">
                <div class="participants">
                    <i class="fa-solid fa-users"></i>
                    <span id="featCount"><?php echo $featured_event ? (int)($featured_event['attendee_count'] ?? 0) : 0; ?></span>
                </div>
                <h1 id="featTitle"><?php echo $featured_event ? htmlspecialchars($featured_event['title'] ?? '') : 'Browse CvSU Events'; ?></h1>
                <p class="hosted"><?php echo $featured_event ? 'Hosted By' : 'Featured Organizations'; ?></p>
                <p class="organization" id="featOrg"><?php echo $featured_event ? htmlspecialchars($featured_event['org_name'] ?? '') : 'Campus-wide community'; ?></p>
                <div class="main-buttons">
                    <?php if ($featured_event): ?>
                        <a id="featLink" href="view-event-page.php?event_id=<?php echo urlencode($featured_event['id'] ?? ''); ?>" class="join-btn">View Event</a>
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
                    <?php
                        $org_logo = $default_cover;
                        if (!empty($org['logo']) && strtolower($org['logo']) !== 'null') {
                            if (file_exists($org['logo']) && is_file($org['logo'])) {
                                $org_logo = $org['logo'];
                            }
                        }
                    ?>
                    <div class="org-card">
                        <img src="<?php echo htmlspecialchars($org_logo); ?>" alt="<?php echo htmlspecialchars($org['name'] ?? 'Org'); ?>">
                        <p><?php echo htmlspecialchars($org['name'] ?? ''); ?></p>
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
                        <?php
                            $raw_banner  = !empty($event['event_banner']) ? $event['event_banner']
                                         : (!empty($event['cover_photo']) ? $event['cover_photo'] : '');
                            $event_image = $default_cover;
                            if (!empty($raw_banner) && strtolower($raw_banner) !== 'null') {
                                if (file_exists($raw_banner) && is_file($raw_banner)) {
                                    $event_image = $raw_banner;
                                }
                            }
                        ?>
                        <a class="event-card" href="view-event-page.php?event_id=<?php echo urlencode($event['id'] ?? ''); ?>">
                            <div class="card-image-wrap">
                                <img src="<?php echo htmlspecialchars($event_image); ?>" alt="Event">
                                <div class="card-badge">
                                    <i class="fa-solid fa-users"></i>
                                    <?php echo (int)($event['attendee_count'] ?? 0); ?>
                                </div>
                                <div class="card-visibility">
                                    <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $event['visibility'] ?? 'public'))); ?>
                                </div>
                                <div class="card-hover-overlay"></div>
                                <span class="card-join-btn">Join Event</span>
                            </div>
                            <div class="card-info-below">
                                <div class="card-title"><?php echo htmlspecialchars($event['title'] ?? ''); ?></div>
                                <div class="card-org"><?php echo htmlspecialchars($event['org_name'] ?? ''); ?></div>
                                <div class="card-date">
                                    <?php echo htmlspecialchars(date('M j, Y · g:i A', strtotime($event['start_datetime'] ?? 'now'))); ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="event-grid">
                    <div class="event-card" style="background:#f7f7f7; display:flex; justify-content:center; align-items:center; min-height:200px;">
                        <div style="padding:20px; text-align:center; color:#555;">No events found. Check back later.</div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="container">
        <?php if (!empty($org_event_groups)): ?>
            <?php foreach ($org_event_groups as $org_group): ?>
                <?php
                    $group_logo = $default_cover;
                    if (!empty($org_group['org_logo']) && strtolower($org_group['org_logo']) !== 'null') {
                        if (file_exists($org_group['org_logo']) && is_file($org_group['org_logo'])) {
                            $group_logo = $org_group['org_logo'];
                        }
                    }
                ?>
                <div class="org-host-header">
                    <div class="org-host-left">
                        <img src="<?php echo htmlspecialchars($group_logo); ?>"
                             alt="<?php echo htmlspecialchars($org_group['org_name'] ?? ''); ?> Logo"
                             class="host-avatar">
                        <div class="host-text-details">
                            <span class="host-label">Hosted by the</span>
                            <h2><?php echo htmlspecialchars($org_group['org_name'] ?? ''); ?></h2>
                        </div>
                    </div>
                    <button class="show-all-btn" type="button">Show All</button>
                </div>
                <section class="secondary-events-section">
                    <div class="event-grid-scroll">
                        <?php foreach ($org_group['events'] as $event): ?>
                            <?php
                                $sub_raw = !empty($event['event_banner']) ? $event['event_banner']
                                         : (!empty($event['cover_photo']) ? $event['cover_photo'] : '');
                                $sub_img = $default_cover;
                                if (!empty($sub_raw) && strtolower($sub_raw) !== 'null') {
                                    if (file_exists($sub_raw) && is_file($sub_raw)) {
                                        $sub_img = $sub_raw;
                                    }
                                }
                            ?>
                            <a href="view-event-page.php?event_id=<?php echo urlencode($event['id'] ?? ''); ?>" class="scroll-card">
                                <div class="card-media">
                                    <img src="<?php echo htmlspecialchars($sub_img); ?>" alt="Event">
                                    <div class="card-badge">
                                        <i class="fa-solid fa-users"></i>
                                        <?php echo (int)($event['attendee_count'] ?? 0); ?>
                                    </div>
                                    <button class="card-join-glass" type="button">Join Event</button>
                                </div>
                                <div class="card-info">
                                    <h3><?php echo htmlspecialchars($event['title'] ?? ''); ?></h3>
                                    <p class="card-date"><?php echo htmlspecialchars(date('M j, Y · g:i A', strtotime($event['start_datetime'] ?? 'now'))); ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="scroll-arrow-right"><i class="fa-solid fa-chevron-right"></i></div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="js/navbar.js"></script>

    <script>
    (function () {
        const events = <?php echo json_encode(array_values($carousel_events)); ?>;
        if (!events.length) return;

        const slides = document.querySelectorAll('#featuredCarousel .featured-slide');
        const dots   = document.querySelectorAll('#featDots .carousel-dot');
        const title  = document.getElementById('featTitle');
        const org    = document.getElementById('featOrg');
        const count  = document.getElementById('featCount');
        const link   = document.getElementById('featLink');

        let current = Math.floor(Math.random() * events.length);
        let autoTimer;

        function goTo(next, direction) {
            if (next === current) return;
            const exitClass = direction === 'next' ? 'exit-left' : 'exit-right';

            slides[current].classList.add(exitClass);
            slides[current].classList.remove('active');
            dots[current]?.classList.remove('active');

            const exiting = slides[current];
            exiting.addEventListener('transitionend', () => {
                exiting.classList.remove(exitClass);
            }, { once: true });

            current = next;
            slides[current].classList.add('active');
            dots[current]?.classList.add('active');
            updateInfo(current);
        }

        function updateInfo(idx) {
            const ev = events[idx];
            if (!ev) return;
            if (title) title.textContent = ev.title || 'Browse CvSU Events';
            if (org)   org.textContent   = ev.org_name || 'Campus-wide community';
            if (count) count.textContent = ev.attendee_count ?? 0;
            if (link)  link.href         = ev.url || '#';
        }

        function nextSlide() { goTo((current + 1) % events.length, 'next'); }
        function prevSlide() { goTo((current - 1 + events.length) % events.length, 'prev'); }

        function resetTimer() {
            clearInterval(autoTimer);
            autoTimer = setInterval(nextSlide, 5000);
        }

        slides.forEach(s => s.classList.remove('active'));
        slides[current]?.classList.add('active');
        dots[current]?.classList.add('active');
        updateInfo(current);
        resetTimer();

        document.getElementById('featNext')?.addEventListener('click', () => { nextSlide(); resetTimer(); });
        document.getElementById('featPrev')?.addEventListener('click', () => { prevSlide(); resetTimer(); });

        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                const idx = parseInt(dot.dataset.index, 10);
                goTo(idx, idx > current ? 'next' : 'prev');
                resetTimer();
            });
        });

        document.querySelector('.main')?.addEventListener('mouseenter', () => clearInterval(autoTimer));
        document.querySelector('.main')?.addEventListener('mouseleave', resetTimer);
    })();
    </script>
</body>
</html>