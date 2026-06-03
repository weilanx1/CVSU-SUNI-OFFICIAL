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
    <title>Hydrofest 2026</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/navbar.css">
     <link rel="stylesheet" href="css/index.css">
    
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
                <li><a href="index.php" class="active">CvSU Events</a></li>
                <li><a href="Myprofile.php">My Profile</a></li>
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

    <section class="main">
        <img src="images/cover.png" class="cover-photo" alt="Cover">
        <div class="overlay"></div>

        <div class="container">
            <div class="main-content">
                <div class="participants">
                    <i class="fa-solid fa-users"></i>
                    <span>134</span>
                </div>
                <h1>Hydrofest 2026</h1>
                <p class="hosted">Hosted By</p>
                <p class="organization">Central Student Government</p>

                <div class="main-buttons">
                    <button class="join-btn">Join Event</button>
                    <button class="info-btn">
                        <i class="fa-solid fa-info"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <section class="organizations">
            <div class="arrow-btn"><i class="fa-solid fa-chevron-left"></i></div>
            
            <div class="org-card">
                <img src="images/aws.png" alt="Org">
                <p>AWS Cloud Club - Spade</p>
            </div>
            <div class="org-card">
                <img src="images/sinagtala.png" alt="Org">
                <p>Sinagtala Multimedia Arts</p>
            </div>
            <div class="org-card">
                <img src="images/ceit.png" alt="Org">
                <p>CEIT Student Council</p>
            </div>
            <div class="org-card">
                <img src="images/logocsg.png" alt="Org">
                <p>Central Student Government</p>
            </div>
            <div class="org-card">
                <img src="images/musikeros.png" alt="Org">
                <p>Musikeros</p>
            </div>

            <div class="arrow-btn"><i class="fa-solid fa-chevron-right"></i></div>
        </section>
    </div>

    <div class="container">
        <section class="events-section">
            <div class="tabs">
                <div class="tab">Popular Events</div>
                <div class="tab">Other Events</div>
            </div>

            <div class="event-grid">
                <div class="event-card">
                    <img src="images/event1.jpg" alt="Event">
                    <div class="event-overlay">
                        <div class="event-top">
                            <span><i class="fa-solid fa-users"></i> 134</span>
                            <span>💎</span>
                        </div>
                        <div>
                            <div class="event-title">Color Carnival</div>
                        </div>
                    </div>
                </div>

                <div class="event-card">
                    <img src="images/event2.jpg" alt="Event">
                    <div class="event-overlay">
                        <div class="event-top">
                            <span><i class="fa-solid fa-users"></i> 44</span>
                        </div>
                        <div>
                            <div class="event-title">WE ARE READY</div>
                            <div class="event-sub">Tech Conference</div>
                        </div>
                    </div>
                </div>

                <div class="event-card">
                    <img src="images/event3.jpg" alt="Event">
                    <div class="event-overlay">
                        <div class="event-top">
                            <span><i class="fa-solid fa-users"></i> 0</span>
                        </div>
                        <div>
                            <div class="event-title">WORKSHOP</div>
                            <div class="event-sub">Business Strategy</div>
                        </div>
                    </div>
                </div>

                <div class="event-card">
                    <img src="images/event4.jpg" alt="Event">
                    <div class="event-overlay">
                        <div class="event-top">
                            <span><i class="fa-solid fa-users"></i> 111</span>
                        </div>
                        <div>
                            <div class="event-title">SLEEP WORKSHOPS</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="container">
        
        <div class="org-host-header">
            <div class="org-host-left">
                <img src="images/ceit.png" alt="CEIT Logo" class="host-avatar">
                <div class="host-text-details">
                    <span class="host-label">Hosted by the</span>
                    <h2>CEIT Student Council</h2>
                </div>
            </div>
            <button class="show-all-btn">Show All</button>
        </div>

        <section class="secondary-events-section">
            <div class="event-grid-scroll">
                
                <div class="scroll-card">
                    <div class="card-media">
                        <img src="images/ceit1.jpg" alt="Event">
                        <div class="card-badge"><i class="fa-solid fa-users"></i> 134</div>
                        <button class="card-join-glass">Join Event</button>
                    </div>
                    <div class="card-info">
                        <h3>Hydrofest 2026</h3>
                        <p class="card-date">Today, 6:30 PM</p>
                    </div>
                </div>

                <div class="scroll-card">
                    <div class="card-media">
                        <img src="images/ceit2.jpg" alt="Event">
                        <div class="card-badge"><i class="fa-solid fa-users"></i> 44</div>
                    </div>
                    <div class="card-info">
                        <h3>The 2026 Global Artificial Intelligence & Quantum..</h3>
                        <p class="card-date">March 12, 2026</p>
                    </div>
                </div>

                <div class="scroll-card">
                    <div class="card-media">
                        <img src="images/ceit3.jpg" alt="Event">
                        <div class="card-badge"><i class="fa-solid fa-users"></i> 0</div>
                    </div>
                    <div class="card-info">
                        <h3>Xeed's Workshop</h3>
                        <p class="card-date">April 3, 2026</p>
                    </div>
                </div>

                <div class="scroll-card">
                    <div class="card-media">
                        <img src="images/ceit4.jpg" alt="Event">
                        <div class="card-badge"><i class="fa-solid fa-users"></i> 111</div>
                    </div>
                    <div class="card-info">
                        <h3>Kraig's Workshop</h3>
                        <p class="card-date">April 4, 2026</p>
                    </div>
                </div>

            </div>
            
            <div class="scroll-arrow-right">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
             <div class="org-host-header">
            <div class="org-host-left">
                <img src="images/aws.png" alt="CEIT Logo" class="host-avatar">
                <div class="host-text-details">
                    <span class="host-label">Hosted by the</span>
                    <h2>AWS Cloud Club - Spade</h2>
                </div>
            </div>
            <button class="show-all-btn">Show All</button>
        </div>

        <section class="secondary-events-section">
            <div class="event-grid-scroll">
                
                <div class="scroll-card">
                    <div class="card-media">
                        <img src="images/aws1.jpg" alt="Event">
                        <div class="card-badge"><i class="fa-solid fa-users"></i> 134</div>
                        <button class="card-join-glass">Join Event</button>
                    </div>
                    <div class="card-info">
                        <h3>Hydrofest 2026</h3>
                        <p class="card-date">Today, 6:30 PM</p>
                    </div>
                </div>

                <div class="scroll-card">
                    <div class="card-media">
                        <img src="images/aws2.jpg" alt="Event">
                        <div class="card-badge"><i class="fa-solid fa-users"></i> 44</div>
                    </div>
                    <div class="card-info">
                        <h3>The 2026 Global Artificial Intelligence & Quantum..</h3>
                        <p class="card-date">March 12, 2026</p>
                    </div>
                </div>

                <div class="scroll-card">
                    <div class="card-media">
                        <img src="images/aws3.jpg" alt="Event">
                        <div class="card-badge"><i class="fa-solid fa-users"></i> 0</div>
                    </div>
                    <div class="card-info">
                        <h3>Xeed's Workshop</h3>
                        <p class="card-date">April 3, 2026</p>
                    </div>
                </div>

                <div class="scroll-card">
                    <div class="card-media">
                        <img src="images/aws4.jpg" alt="Event">
                        <div class="card-badge"><i class="fa-solid fa-users"></i> 111</div>
                    </div>
                    <div class="card-info">
                        <h3>Kraig's Workshop</h3>
                        <p class="card-date">April 4, 2026</p>
                    </div>
                </div>

            </div>
            
            <div class="scroll-arrow-right">
                <i class="fa-solid fa-chevron-right"></i>
            </div>
        </section>
    </div>

</body>
</html>