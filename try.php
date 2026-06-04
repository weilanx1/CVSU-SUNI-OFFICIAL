<?php
session_start();
require_once 'db.php';

$is_admin = $_SESSION['is_admin'] ?? false;
$default_avatar = 'images/person3.png';
$default_cover = 'images/cover.png';
$profile_picture = $default_avatar; // Default fallback image
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
        // Clean up the string and ensure it isn't literally the word 'null' or completely empty
        $db_picture = trim($user['profile_picture'] ?? '');
        if (!empty($db_picture) && strtolower($db_picture) !== 'null') {
            if (file_exists($db_picture) && is_file($db_picture)) {
                $profile_picture = htmlspecialchars($db_picture);
            } else {
                $profile_picture = $default_avatar;
            }
        }
        $user_department_id = $user['department_id'] ?? null;
    }

    $org_stmt = $conn->prepare('SELECT organization_id FROM organization_admins WHERE user_id = ? UNION SELECT id FROM organizations WHERE main_admin_id = ?');
    $org_stmt->bind_param('ii', $user_id, $user_id);
    $org_stmt->execute();
    $org_result = $org_stmt->get_result();
    while ($row = $org_result->fetch_assoc()) {
        if (isset($row['organization_id'])) {
            $user_org_ids[] = (int)$row['organization_id'];
        }
    }
    $user_org_ids = array_unique($user_org_ids);
}

$events = [];
$event_sql = "SELECT e.*, o.name AS org_name, o.logo AS org_logo, COALESCE((SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id), 0) AS attendee_count FROM events e JOIN organizations o ON e.organization_id = o.id WHERE e.status = 'published' ORDER BY e.start_datetime ASC";
$event_result = $conn->query($event_sql);
if ($event_result) {
    while ($row = $event_result->fetch_assoc()) {
        $events[] = $row;
    }
}

$event_department_map = [];
if (!empty($events)) {
    $event_ids = array_map(function ($event) {
        return (int)($event['id'] ?? 0);
    }, $events);
    $id_list = implode(',', $event_ids);
    $dept_result = $conn->query("SELECT event_id, department_id FROM event_departments WHERE event_id IN ($id_list)");
    if ($dept_result) {
        while ($row = $dept_result->fetch_assoc()) {
            $event_department_map[(int)$row['event_id']][] = (int)$row['department_id'];
        }
    }
}

function can_view_event($event, $user_id, $user_department_id, $user_org_ids, $event_department_map)
{
    $visibility = $event['visibility'] ?? 'public';
    if ($visibility === 'public') {
        return true;
    }

    if (!$user_id) {
        return false;
    }

    if (($event['created_by'] ?? null) == $user_id) {
        return true;
    }

    if (in_array((int)($event['organization_id'] ?? 0), $user_org_ids, true)) {
        return true;
    }

    switch ($visibility) {
        case 'organization_only':
            return in_array((int)($event['organization_id'] ?? 0), $user_org_ids, true);
        case 'department_only':
        case 'restricted':
            return $user_department_id && isset($event_department_map[(int)($event['id'] ?? 0)]) && in_array((int)$user_department_id, $event_department_map[(int)$event['id']], true);
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
    return ($b['attendee_count'] ?? 0) <=> ($a['attendee_count'] ?? 0);
});

$featured_event = $visible_events[0] ?? null;
$popular_events = array_slice($visible_events, 0, 4);

// Inihanda ang Slider Array para sa JavaScript hero banner
$slider_slides = [];
foreach ($popular_events as $p_event) {
    $raw_banner = !empty($p_event['event_banner']) ? $p_event['event_banner'] : (!empty($p_event['cover_photo']) ? $p_event['cover_photo'] : '');
    $img_src = $default_cover;
    if (!empty($raw_banner) && strtolower($raw_banner) !== 'null' && file_exists($raw_banner) && is_file($raw_banner)) {
        $img_src = $raw_banner;
    }
    $slider_slides[] = [
        'id' => urlencode($p_event['id'] ?? ''),
        'title' => htmlspecialchars($p_event['title'] ?? ''),
        'org_name' => htmlspecialchars($p_event['org_name'] ?? 'Campus-wide community'),
        'attendees' => (int)($p_event['attendee_count'] ?? 0),
        'image' => htmlspecialchars($img_src)
    ];
}

$organizations = [];
$org_query = $conn->query('SELECT id, name, logo FROM organizations ORDER BY name ASC');
if ($org_query) {
    while ($org = $org_query->fetch_assoc()) {
        $organizations[] = $org;
    }
}

$org_event_groups = [];
foreach ($visible_events as $event) {
    $org_id = (int)($event['organization_id'] ?? 0);
    if ($org_id <= 0) continue;
    
    if (!isset($org_event_groups[$org_id])) {
        $org_event_groups[$org_id] = [
            'org_id' => $org_id,
            'org_name' => $event['org_name'] ?? 'Unknown Organization',
            'org_logo' => $event['org_logo'] ?? '',
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
    
    <style>
        /* Tinitiyak na swabe ang pagpalit ng mga larawan at teksto sa Hero Banner */
        .main {
            transition: background-image 0.8s ease-in-out !important;
            background-size: cover !important;
            background-position: center !important;
            position: relative;
        }
        .main-content h1, .main-content .organization, .main-content .participants span {
            transition: opacity 0.3s ease-in-out;
        }
        /* Para sa opsyonal na manual slider arrows sa Hero Section kung nais lagyan */
        .hero-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .hero-arrow:hover { background: rgba(255,255,255,0.4); }
        #heroLeftArrow { left: 20px; }
        #heroRightArrow { right: 20px; }

        /* Sinisigurado na pwedeng i-scroll gamit ang JS ang listahan ng Organizations */
        .organizations {
            display: flex;
            align-items: center;
            overflow-x: auto;
            scroll-behavior: smooth;
            gap: 20px;
            padding: 10px 0;
        }
        .organizations::-webkit-scrollbar {
            display: none; /* Itatago ang scrollbar para malinis */
        }
        .org-card-container {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            scroll-behavior: smooth;
            width: 100%;
        }
        .org-card-container::-webkit-scrollbar { display: none; }
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
            <li><a href="dashboard.php">Organization Dashboard</a></li>
            <?php endif; ?>
            <li class="nav-icons">
                <i class="fa-solid fa-magnifying-glass"></i>
                <i class="fa-regular fa-bell fa-lg"></i>
            </li>
            <li><img src="<?php echo $profile_picture; ?>" class="profile" id="navProfileAvatar" alt="Profile"></li>
        </ul>
    </nav>

    <section class="main" id="dynamicHeroSection" style="background-image: url('<?php echo !empty($slider_slides) ? $slider_slides[0]['image'] : $default_cover; ?>');">
        <button class="hero-arrow" id="heroLeftArrow" type="button"><i class="fa-solid fa-chevron-left"></i></button>
        <button class="hero-arrow" id="heroRightArrow" type="button"><i class="fa-solid fa-chevron-right"></i></button>

        <div class="overlay"></div>
        <div class="container">
            <div class="main-content">
                <div class="participants">
                    <i class="fa-solid fa-users"></i>
                    <span id="sliderAttendeeCount"><?php echo !empty($slider_slides) ? $slider_slides[0]['attendees'] : 0; ?></span>
                </div>
                <h1 id="sliderEventTitle"><?php echo !empty($slider_slides) ? $slider_slides[0]['title'] : 'Browse CvSU Events'; ?></h1>
                <p class="hosted" id="sliderHostedLabel"><?php echo !empty($slider_slides) ? 'Hosted By' : 'Featured Organizations'; ?></p>
                <p class="organization" id="sliderOrgName"><?php echo !empty($slider_slides) ? $slider_slides[0]['org_name'] : 'Campus-wide community'; ?></p>
                <div class="main-buttons">
                    <?php if (!empty($slider_slides)): ?>
                        <a href="view-event-page.php?event_id=<?php echo $slider_slides[0]['id']; ?>" id="sliderViewBtn" class="join-btn">View Event</a>
                    <?php else: ?>
                        <button class="join-btn" disabled>No events available</button>
                    <?php endif; ?>
                    <button class="info-btn" type="button"><i class="fa-solid fa-info"></i></button>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <section class="organizations" style="position: relative; display: flex; align-items: center;">
            <div class="arrow-btn" id="orgScrollLeft" style="cursor: pointer; z-index: 5;"><i class="fa-solid fa-chevron-left"></i></div>
            
            <div class="org-card-container" id="orgCardsWrapper">
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
                        <div class="org-card" style="flex: 0 0 auto;">
                            <img src="<?php echo htmlspecialchars($org_logo); ?>" alt="<?php echo htmlspecialchars($org['name'] ?? 'Org'); ?>">
                            <p><?php echo htmlspecialchars($org['name'] ?? ''); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="org-card" style="flex: 0 0 auto;">
                        <img src="images/cover.png" alt="Org">
                        <p>No organizations yet</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="arrow-btn" id="orgScrollRight" style="cursor: pointer; z-index: 5;"><i class="fa-solid fa-chevron-right"></i></div>
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
                            $raw_banner = !empty($event['event_banner']) ? $event['event_banner'] : (!empty($event['cover_photo']) ? $event['cover_photo'] : '');
                            $event_image = $default_cover;
                            
                            if (!empty($raw_banner) && strtolower($raw_banner) !== 'null') {
                                if (file_exists($raw_banner) && is_file($raw_banner)) {
                                    $event_image = $raw_banner;
                                }
                            }
                        ?>
                        <a class="event-card" href="view-event-page.php?event_id=<?php echo urlencode($event['id'] ?? ''); ?>">
                            <img src="<?php echo htmlspecialchars($event_image); ?>" alt="Event">
                            <div class="event-overlay">
                                <div class="event-top">
                                    <span><i class="fa-solid fa-users"></i> <?php echo (int)($event['attendee_count'] ?? 0); ?></span>
                                    <span><?php echo htmlspecialchars(ucfirst($event['visibility'] ?? 'public')); ?></span>
                                </div>
                                <div>
                                    <div class="event-title"><?php echo htmlspecialchars($event['title'] ?? ''); ?></div>
                                    <div class="event-sub"><?php echo htmlspecialchars($event['org_name'] ?? ''); ?></div>
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
            <?php $group_counter = 0; foreach ($org_event_groups as $org_group): $group_counter++; ?>
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
                        <img src="<?php echo htmlspecialchars($group_logo); ?>" alt="<?php echo htmlspecialchars($org_group['org_name'] ?? ''); ?> Logo" class="host-avatar">
                        <div class="host-text-details">
                            <span class="host-label">Hosted by the</span>
                            <h2><?php echo htmlspecialchars($org_group['org_name'] ?? ''); ?></h2>
                        </div>
                    </div>
                    <button class="show-all-btn" type="button">Show All</button>
                </div>
                
                <section class="secondary-events-section" style="position: relative; display: flex; align-items: center;">
                    <div class="event-grid-scroll" id="eventGridScroll_<?php echo $group_counter; ?>" style="overflow-x: auto; scroll-behavior: smooth; width: 100%;">
                        <?php foreach ($org_group['events'] as $event): ?>
                            <?php 
                                $sub_raw_banner = !empty($event['event_banner']) ? $event['event_banner'] : (!empty($event['cover_photo']) ? $event['cover_photo'] : '');
                                $sub_event_image = $default_cover;
                                
                                if (!empty($sub_raw_banner) && strtolower($sub_raw_banner) !== 'null') {
                                    if (file_exists($sub_raw_banner) && is_file($sub_raw_banner)) {
                                        $sub_event_image = $sub_raw_banner;
                                    }
                                }
                            ?>
                            <a href="view-event-page.php?event_id=<?php echo urlencode($event['id'] ?? ''); ?>" class="scroll-card" style="flex: 0 0 auto;">
                                <div class="card-media">
                                    <img src="<?php echo htmlspecialchars($sub_event_image); ?>" alt="Event">
                                    <div class="card-badge"><i class="fa-solid fa-users"></i> <?php echo (int)($event['attendee_count'] ?? 0); ?></div>
                                    <button class="card-join-glass" type="button">View Event</button>
                                </div>
                                <div class="card-info">
                                    <h3><?php echo htmlspecialchars($event['title'] ?? ''); ?></h3>
                                    <p class="card-date"><?php echo htmlspecialchars(date('M j, Y', strtotime($event['start_datetime'] ?? 'now'))); ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="scroll-arrow-right org-row-arrow-right" data-target="eventGridScroll_<?php echo $group_counter; ?>" style="cursor: pointer; z-index: 5;"><i class="fa-solid fa-chevron-right"></i></div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        
        // ========================================================
        // 1. AUTO-SLIDING + MANUAL ARROW HERO BANNER ENGINE
        // ========================================================
        const slidesData = <?php echo json_encode($slider_slides); ?>;
        
        if (slidesData && slidesData.length > 1) {
            let currentSlideIndex = 0;
            let slideTimer;
            
            const heroSection = document.getElementById('dynamicHeroSection');
            const attendeeSpan = document.getElementById('sliderAttendeeCount');
            const titleHeading = document.getElementById('sliderEventTitle');
            const orgNamePara = document.getElementById('sliderOrgName');
            const viewEventBtn = document.getElementById('sliderViewBtn');

            const leftArrow = document.getElementById('heroLeftArrow');
            const rightArrow = document.getElementById('heroRightArrow');

            function renderSlide(index) {
                const currentSlide = slidesData[index];
                titleHeading.style.opacity = 0;
                orgNamePara.style.opacity = 0;
                attendeeSpan.style.opacity = 0;

                setTimeout(() => {
                    heroSection.style.backgroundImage = `url('${currentSlide.image}')`;
                    titleHeading.textContent = currentSlide.title;
                    orgNamePara.textContent = currentSlide.org_name;
                    attendeeSpan.textContent = currentSlide.attendees;
                    
                    if (viewEventBtn) {
                        viewEventBtn.setAttribute('href', `view-event-page.php?event_id=${currentSlide.id}`);
                    }

                    titleHeading.style.opacity = 1;
                    orgNamePara.style.opacity = 1;
                    attendeeSpan.style.opacity = 1;
                }, 200);
            }

            function autoAdvanceSlide() {
                currentSlideIndex = (currentSlideIndex + 1) % slidesData.length;
                renderSlide(currentSlideIndex);
            }

            function startAutoSlide() {
                clearInterval(slideTimer);
                slideTimer = setInterval(autoAdvanceSlide, 5000);
            }

            if (rightArrow) {
                rightArrow.addEventListener('click', function () {
                    currentSlideIndex = (currentSlideIndex + 1) % slidesData.length;
                    renderSlide(currentSlideIndex);
                    startAutoSlide();
                });
            }

            if (leftArrow) {
                leftArrow.addEventListener('click', function () {
                    currentSlideIndex = (currentSlideIndex - 1 + slidesData.length) % slidesData.length;
                    renderSlide(currentSlideIndex);
                    startAutoSlide();
                });
            }

            renderSlide(currentSlideIndex);
            startAutoSlide();
        }

        // ========================================================
        // 2. GUMAGANANG ARROWS PARA SA ORGANIZATIONS SLIDER
        // ========================================================
        const orgWrapper = document.getElementById('orgCardsWrapper');
        const orgLeftBtn = document.getElementById('orgScrollLeft');
        const orgRightBtn = document.getElementById('orgScrollRight');

        if (orgWrapper && orgLeftBtn && orgRightBtn) {
            orgRightBtn.addEventListener('click', function () {
                orgWrapper.scrollBy({ left: 240, behavior: 'smooth' });
            });
            orgLeftBtn.addEventListener('click', function () {
                orgWrapper.scrollBy({ left: -240, behavior: 'smooth' });
            });
        }

        // ========================================================
        // 3. GUMAGANANG ARROWS PARA SA MGA EVENT ROWS (HOSTED BY ORGS)
        // ========================================================
        const rowArrows = document.querySelectorAll('.org-row-arrow-right');
        rowArrows.forEach(arrow => {
            arrow.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const targetRow = document.getElementById(targetId);
                if (targetRow) {
                    // Mag-scroll pakanan. Kung sagad na, babalik ito sa simula (0)
                    if (targetRow.scrollLeft + targetRow.clientWidth >= targetRow.scrollWidth - 10) {
                        targetRow.scrollTo({ left: 0, behavior: 'smooth' });
                    } else {
                        targetRow.scrollBy({ left: 280, behavior: 'smooth' });
                    }
                }
            });
        });

        // ========================================================
        // 4. COMPACT WHITE SIGN-OUT DROPDOWN CONTAINER (SIGN-IN.PHP)
        // ========================================================
        const profileImg = document.getElementById('navProfileAvatar');
        if (profileImg) {
            const parentLi = profileImg.parentElement;
            parentLi.style.position = 'relative';
            parentLi.style.display = 'inline-block';
            parentLi.style.listStyle = 'none';
            
            const currentAvatarSrc = profileImg.getAttribute('src');
            const dropdownHTML = `
                <div class="profile-dropdown-menu" id="userDropdownMenu" style="
                    display: none;
                    position: absolute;
                    right: 0;
                    top: 45px;
                    background-color: #ffffff;
                    min-width: 230px;
                    box-shadow: 0px 8px 24px rgba(0, 0, 0, 0.08);
                    border-radius: 16px;
                    z-index: 9999;
                    padding: 16px 0 10px 0;
                    font-family: 'Poppins', sans-serif;
                    text-align: left;
                    box-sizing: border-box;
                    border: 1px solid #eef0f2;
                ">
                    <div class="dropdown-user-card" style="display: flex; align-items: center; gap: 12px; padding: 0 16px 14px 16px; width: 100%; box-sizing: border-box;">
                        <img src="${currentAvatarSrc}" alt="Avatar" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover;">
                        <div style="display: flex; flex-direction: column; min-width: 0;">
                            <h4 style="margin: 0; font-size: 15px; font-weight: 700; color: #2d2d2d; font-family: 'Poppins', sans-serif; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Kraig Gonzales</h4>
                            <p style="margin: 0; font-size: 11.5px; color: #8e8e93; font-weight: 400; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">krggnzls@gmail.com</p>
                        </div>
                    </div>
                    <hr style="border: 0; height: 1px; background-color: #f2f2f7; margin: 0 0 4px 0; width: 100%;">
                    <a href="sign-in.php" class="sign-out-item" id="signOutLink" style="
                        color: #2d2d2d !important;
                        padding: 10px 16px;
                        text-decoration: none !important;
                        display: flex !important;
                        flex-direction: row !important;
                        align-items: center;
                        font-size: 14px;
                        font-weight: 500;
                        width: 100%;
                        box-sizing: border-box;
                        transition: background 0.2s, color 0.2s;
                    " onmouseover="this.style.backgroundColor='#f8f9fa'; this.style.color='#ff453a' !important;" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#2d2d2d' !important;">
                        Sign Out
                    </a>
                </div>
            `;
            
            parentLi.insertAdjacentHTML('beforeend', dropdownHTML);
            const dropdownMenu = document.getElementById('userDropdownMenu');
            const signOutLink = document.getElementById('signOutLink');
            
            profileImg.addEventListener('click', function (e) {
                e.stopPropagation();
                const isClosed = dropdownMenu.style.display === 'none' || dropdownMenu.style.display === '';
                if (isClosed) {
                    dropdownMenu.style.setProperty('display', 'flex', 'important');
                    dropdownMenu.style.setProperty('flex-direction', 'column', 'important');
                } else {
                    dropdownMenu.style.display = 'none';
                }
            });
            
            signOutLink.addEventListener('click', function (e) {
                const confirmLogout = confirm("Are you sure you want to sign out?");
                if (!confirmLogout) {
                    e.preventDefault();
                }
            });
            
            document.addEventListener('click', function (e) {
                if (!dropdownMenu.contains(e.target) && e.target !== profileImg) {
                    dropdownMenu.style.display = 'none';
                }
            });
        }
    });
    </script>
    <script src="js/navbar.js"></script>
</body>
</html>