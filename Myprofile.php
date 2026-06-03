<?php
session_start();
require_once 'db.php';

$is_admin = $_SESSION['is_admin'] ?? false;
$profile_picture = 'images/person3.png'; // Default profile picture

// Fetch user's profile picture if logged in
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
        <a href="">
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
            <li><img src="<?php echo $profile_picture; ?>" class="profile" alt="Profile"></li>
        </ul>
    </nav>
    <section class="profile-header-card">
            <div class="banner-background">
                </div>
            <div class="profile-info-wrapper">
                <div class="profile-avatar-container">
                    <div class="avatar-placeholder">
                        </div>
                    <form action="update_avatar.php" method="POST" enctype="multipart/form-data" class="avatar-edit-form">
                        <label for="avatar-input" class="edit-avatar-btn"><i class="fa-solid fa-camera"></i></label>
                        <input type="file" id="avatar-input" name="profile_img" onchange="this.form.submit()" style="display: none;">
                    </form>
                </div>
                
                <div class="profile-text-details">
                    <h1 class="user-name">Sem Pablo R. Mateo</h1>
                    <p class="user-college">College of Engineering and Information Technology</p>
                    
                    <div class="bio-container">
                        <form action="update_bio.php" method="POST" class="bio-form">
                            <span class="bio-text">Add bio</span>
                            <button type="button" class="edit-bio-btn"><i class="fa-regular fa-pen-to-square"></i></button>
                            <input type="text" name="bio" class="bio-input hidden" placeholder="Write something about yourself..." maxlength="150">
                        </form>
                    </div>

                    <div class="stats-badge">
                        <span class="stats-num">12</span>
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
                        <div class="ticket-card">
                            <div class="ticket-img-blank"></div>
                            <div class="ticket-body">
                                <h3 class="ticket-title">IT DAY 2026: Beyond The Mask</h3>
                                <p class="ticket-date">Event Date<br><span>Mar 14, 2026 • 11:59 PM</span></p>
                                <div class="ticket-meta-grid">
                                    <p class="status-indicator approved">Status <span>Approved</span></p>
                                    <p class="checkin-indicator">Check-in <span>Attended</span></p>
                                </div>
                                <div class="ticket-action-row">
                                    <a href="ticket-details.php?id=1" class="btn btn-primary">View Ticket</a>
                                    <button class="btn btn-icon"><i class="fa-solid fa-qrcode"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="ticket-card">
                            <div class="ticket-img-blank"></div>
                            <div class="ticket-body">
                                <h3 class="ticket-title">HR CUP 2026 UNITAS: Bridging Generations</h3>
                                <p class="ticket-date">Event Date<br><span>May 10, 2026 • 11:59 PM</span></p>
                                <div class="ticket-meta-grid">
                                    <p class="status-indicator pending">Status <span>Pending Approval</span></p>
                                    <p class="checkin-indicator">Check-in <span>No Show</span></p>
                                </div>
                                <div class="ticket-action-row">
                                    <a href="ticket-details.php?id=2" class="btn btn-primary">View Ticket</a>
                                    <button class="btn btn-icon"><i class="fa-solid fa-qrcode"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="ticket-card">
                            <div class="ticket-img-blank"></div>
                            <div class="ticket-body">
                                <h3 class="ticket-title">Kiro Workshop</h3>
                                <p class="ticket-date">Event Date<br><span>Feb 03, 2025</span></p>
                                <div class="ticket-meta-grid">
                                    <p class="status-indicator approved">Status <span>Approved</span></p>
                                    <p class="checkin-indicator">Check-in <span>Not Arrived</span></p>
                                </div>
                                <div class="ticket-action-row">
                                    <a href="ticket-details.php?id=3" class="btn btn-primary">View Ticket</a>
                                    <button class="btn btn-icon"><i class="fa-solid fa-qrcode"></i></button>
                                </div>
                            </div>
                        </div>
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
                            <div class="event-display-card">
                                <div class="event-banner-blank">
                                    <span class="event-badge-date"><strong>10</strong><br>MAR 4 2026</span>
                                </div>
                                <div class="event-display-body">
                                    <h4 class="event-display-title">HR CUP 2026 UNITAS: Bridging Generations,...</h4>
                                    <p class="event-display-info"><i class="fa-regular fa-user"></i> Central Student Government</p>
                                    <p class="event-display-info"><i class="fa-solid fa-location-dot"></i> S.M. Rolle Hall</p>
                                </div>
                            </div>
                            <div class="event-display-card">
                                <div class="event-banner-blank">
                                    <span class="event-badge-date"><strong>10</strong><br>MAR 4 2026</span>
                                </div>
                                <div class="event-display-body">
                                    <h4 class="event-display-title">HR CUP 2026 UNITAS: Bridging Generations,...</h4>
                                    <p class="event-display-info"><i class="fa-regular fa-user"></i> Central Student Government</p>
                                    <p class="event-display-info"><i class="fa-solid fa-location-dot"></i> S.M. Rolle Hall</p>
                                </div>
                            </div>
                            <div class="event-display-card">
                                <div class="event-banner-blank">
                                    <span class="event-badge-date"><strong>10</strong><br>MAR 4 2026</span>
                                </div>
                                <div class="event-display-body">
                                    <h4 class="event-display-title">HR CUP 2026 UNITAS: Bridging Generations,...</h4>
                                    <p class="event-display-info"><i class="fa-regular fa-user"></i> Central Student Government</p>
                                    <p class="event-display-info"><i class="fa-solid fa-location-dot"></i> S.M. Rolle Hall</p>
                                </div>
                            </div>
                        <div class="event-display-none">
                            <button type="button" class="join-event-btn">
                                <span class="plus-icon">+</span> Join Events
                            </button>
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
                                <span class="node-date">Mar 10, 2026</span>
                                <p class="node-action">Attended HR Cup 2026</p>
                            </div>
                        </div>
                        
                        <div class="timeline-node">
                            <div class="node-marker status-neutral"></div>
                            <div class="node-content">
                                <span class="node-date">Mar 3, 2026</span>
                                <p class="node-action">Registered for Design Fest</p>
                            </div>
                        </div>

                        <div class="timeline-node">
                            <div class="node-marker status-neutral"></div>
                            <div class="node-content">
                                <span class="node-date">Feb 25, 2026</span>
                                <p class="node-action">Earned "The Explorer" badge</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                // You can add logic here later to filter via AJAX fetch or simple display toggle rules.
            });
        });
    </script>
</body>
</html>
