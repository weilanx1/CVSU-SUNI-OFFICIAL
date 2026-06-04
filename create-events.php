<?php
session_start();
require_once 'db.php';

$is_admin = $_SESSION['is_admin'] ?? false;
$user_id = $_SESSION['user_id'] ?? null;

$default_avatar = 'images/person3.png';
$default_logo = 'images/logocsg.png';

$profile_picture = $default_avatar; // Default backup

// Fetch user profile metrics
if ($user_id) {
    $stmt = $conn->prepare('SELECT profile_picture FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user) {
        $db_picture = trim($user['profile_picture'] ?? '');
        if (!empty($db_picture) && strtolower($db_picture) !== 'null') {
            if (file_exists($db_picture) && is_file($db_picture)) {
                $profile_picture = htmlspecialchars($db_picture);
            } else {
                $profile_picture = $default_avatar;
            }
        }
    }
}

// 1. Identify which organization this user manages (Checks either main_admin_id or organization_admins role)
$org_id = null;
$org_name = "Unknown Organization";
$org_logo = $default_logo;
$org_dept_code = "General";

if ($user_id) {
    $org_query = "SELECT o.id, o.name, o.logo, d.code AS dept_code 
                  FROM organizations o
                  LEFT JOIN departments d ON o.department_id = d.id
                  WHERE o.main_admin_id = ? 
                  UNION
                  SELECT o.id, o.name, o.logo, d.code AS dept_code 
                  FROM organization_admins oa
                  JOIN organizations o ON oa.organization_id = o.id
                  LEFT JOIN departments d ON o.department_id = d.id
                  WHERE oa.user_id = ? 
                  LIMIT 1";
    
    $stmt_org = $conn->prepare($org_query);
    $stmt_org->bind_param('ii', $user_id, $user_id);
    $stmt_org->execute();
    $org_res = $stmt_org->get_result();
    
    if ($my_org = $org_res->fetch_assoc()) {
        $org_id = $my_org['id'];
        $org_name = htmlspecialchars($my_org['name']);
        
        $db_logo = trim($my_org['logo'] ?? '');
        if (!empty($db_logo) && strtolower($db_logo) !== 'null') {
            if (file_exists($db_logo) && is_file($db_logo)) {
                $org_logo = htmlspecialchars($db_logo);
            } else {
                $org_logo = $default_logo;
            }
        }
        $org_dept_code = htmlspecialchars($my_org['dept_code'] ?? 'General');
    }
}

// 2. Fetch the department list to feed the visibility element checkbox list
$dept_list = [];
$dept_res = $conn->query("SELECT id, code FROM departments ORDER BY code ASC");
if ($dept_res) {
    while ($row = $dept_res->fetch_assoc()) {
        $dept_list[] = $row;
    }
}

// Inline SVG Black Canvas placeholder reference string
$black_placeholder = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22800%22%20height%3D%22450%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Crect%20width%3D%22100%25%22%20height%3D%22100%25%22%20fill%3D%22%23000000%22%2F%3E%3C%2Fsvg%3E';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUNI - Create Event</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="stylesheet" href="css/create-events.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <style>
        .img-box {
            position: relative;
            background-color: #000000;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Bottom Right Upload Button Placement */
        .modern-upload-btn {
            position: absolute;
            bottom: 14px;
            right: 14px;
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            border: 2px solid rgba(255, 255, 255, 0.7);
            border-radius: 50%;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease-in-out;
            backdrop-filter: blur(6px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4);
            z-index: 10;
        }

        .modern-upload-btn:hover {
            background: #ffffff;
            color: #000000;
            transform: scale(1.08);
            border-color: #ffffff;
        }

        /* Top Right Remove Button Placement */
        .remove-img-btn {
            position: absolute;
            top: 14px;
            right: 14px;
            background: rgba(231, 76, 60, 0.85); /* Smooth Red tint */
            color: #ffffff;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: none; /* Controlled dynamically by JavaScript when an image exists */
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            z-index: 11;
        }

        .remove-img-btn:hover {
            background: #c0392b;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <nav>
        <a href="index.php">
            <img src="images/logo.png" alt="Suni Logo">
        </a>
        <ul>
            <?php if ($is_admin): ?>
            <li><a href="create-events.php" class="active">+ Create Event</a></li>
            <?php endif; ?>
            <li><a href="index.php">CvSU Events</a></li>
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
    
    <form class="wrapper" method="POST" action="process-event.php" enctype="multipart/form-data">
        
        <input type="hidden" name="organization_id" value="<?php echo htmlspecialchars($org_id ?? ''); ?>">
        <input type="hidden" name="visibility_type" id="hiddenVisibilityType" value="public">
        <input type="hidden" name="selected_departments" id="hiddenSelectedDepartments" value="">

        <section class="header">
            <img src="<?php echo $org_logo; ?>" class="logo" alt="Organization Logo">

            <div class="org">
                <h4>You're creating an event for</h4>
                <h1><?php echo $org_name; ?></h1>
                <p>Fill in the details below to publish your event</p>
            </div>

            <div class="visibility-component">
                <span class="visibility-label">Visibility</span>
                <div class="custom-visibility-trigger" id="visibilityTrigger">
                    <span id="visibilityValue">🌐 Public</span>
                </div>
                
                <div class="custom-visibility-panel" id="visibilityPanel">
                    <div class="visibility-option" data-value="Public">
                        <span>Public</span>
                    </div>
                    <div class="visibility-option" data-value="Private">
                        <span>Private (Only you can see)</span>
                    </div>
                    <div class="visibility-option" data-value="Department Only">
                        <span>Department Only (<?php echo $org_dept_code; ?>)</span>
                    </div>
                    <div class="visibility-option-parent" id="filterDeptOption">
                        <div class="option-header-title">
                            <span>Restricted (Select Departments)</span>
                        </div>
                        
                        <div class="dept-checklist" id="deptChecklist">
                            <?php foreach ($dept_list as $dept): ?>
                            <label class="checkbox-row">
                                <input type="checkbox" value="<?php echo htmlspecialchars($dept['id']); ?>" data-code="<?php echo htmlspecialchars($dept['code']); ?>" class="dept-cb">
                                <span><?php echo htmlspecialchars($dept['code']); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="event-container">
            <div class="left-side">
                <div class="left-section-group">
                    <h3>Event Image/Banner</h3>
                    <p>Upload or choose a banner for your event.</p>
                    <div class="img-box">
                        <img src="<?php echo $black_placeholder; ?>" class="display" id="eventBannerPreview" alt="Event Banner">
                        
                        <button type="button" id="removeEventBannerBtn" class="remove-img-btn" onclick="clearImage('event_banner_input', 'eventBannerPreview', 'removeEventBannerBtn')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <button type="button" class="modern-upload-btn" onclick="document.getElementById('event_banner_input').click();">
                            <i class="fa-solid fa-camera"></i>
                        </button>
                        <input type="file" name="event_banner" id="event_banner_input" accept="image/*" style="display:none;" onchange="previewImage(this, 'eventBannerPreview', 'removeEventBannerBtn')">
                    </div>
                </div>

                <div class="left-section-group" style="margin-top: 15px;">
                    <h3>Cover Image/Banner</h3>
                    <div class="img-box">
                        <img src="<?php echo $black_placeholder; ?>" class="cover" id="coverPhotoPreview" alt="Cover Photo">
                        
                        <button type="button" id="removeCoverPhotoBtn" class="remove-img-btn" onclick="clearImage('cover_photo_input', 'coverPhotoPreview', 'removeCoverPhotoBtn')">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <button type="button" class="modern-upload-btn" onclick="document.getElementById('cover_photo_input').click();">
                            <i class="fa-solid fa-camera"></i>
                        </button>
                        <input type="file" name="cover_photo" id="cover_photo_input" accept="image/*" style="display:none;" onchange="previewImage(this, 'coverPhotoPreview', 'removeCoverPhotoBtn')">
                    </div>
                </div>
            </div>

            <div class="right-side">
                <div class="field">
                    <label for="event_name">Event Name</label>
                    <input type="text" id="event_name" name="event_name" placeholder="Enter event name" required>
                </div>

                <div class="date-grid">
                    <div>
                        <label class="time">Start</label>
                        <div class="datetime-box">
                            <div class="date-part">
                                <input type="text" id="start_date" name="start_date" placeholder="mm/dd/yyyy" required>
                            </div>
                            <div class="time-part" style="position: relative;">
                                <input type="text" id="start_time" name="start_time" value="07:00 AM" placeholder="Select Time" readonly class="custom-time-input">
                                <div id="start_time_dropdown" class="custom-time-dropdown"></div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="time">End</label>
                        <div class="datetime-box">
                            <div class="date-part">
                                <input type="text" id="end_date" name="end_date" placeholder="mm/dd/yyyy" required>
                            </div>
                            <div class="time-part" style="position: relative;">
                                <input type="text" id="end_time" name="end_time" value="05:00 PM" placeholder="Select Time" readonly class="custom-time-input">
                                <div id="end_time_dropdown" class="custom-time-dropdown"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field location-wrapper">
                    <label for="location" class="locat">Event Location</label>
                    <div style="position: relative; display: flex; align-items: center; width: 100%;">
                        <i class="fa-solid fa-location-dot" style="position: absolute; left: 14px; color: #8cb49c; font-size: 14px;"></i>
                        <input type="text" id="location" name="location" placeholder="Add location or virtual link" autocomplete="off" style="padding-left: 38px;" required>
                    </div>
                    
                    <div id="locationDropdown" class="location-dropdown">
                        <div class="dropdown-header">
                            <i class="fa-solid fa-map-location-dot"></i>
                            <div>
                                <strong>Search Locations</strong>
                                <span>Select Cavite State University Buildings</span>
                            </div>
                        </div>
                        <div class="dropdown-section" id="suggestionsSection">
                            <span class="section-title">Suggestions</span>
                            <div id="locationSuggestionsContainer"></div>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label for="description" class="title-p">Description</label>
                    <textarea id="description" name="description" placeholder="Add a description about your event..."></textarea>
                </div>

                <label class="title-p" style="margin-top: 20px;">Event Options</label>
                <div class="options-box">
                    <div class="option-row">
                        <div class="option-left">
                            <span>🎟 Ticket Price</span>
                        </div>
                        <div class="option-right" style="font-weight: 600; color: #034421;">
                            Free
                            <input type="hidden" name="ticket_price" value="0.00">
                        </div>
                    </div>

                    <div class="option-row">
                        <div class="option-left">
                            <span>👤 Require Approval</span>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="require_approval" value="1">
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="option-row" style="cursor: pointer;" onclick="handleCapacityClick()">
                        <div class="option-left">
                            <span>👥 Capacity</span>
                        </div>
                        <div class="option-right" id="capacityStatusText">
                            50 ✏️
                        </div>
                    </div>
                </div>

                <input type="hidden" name="limit_capacity" id="hiddenLimitCapacity" value="1">
                <input type="hidden" name="max_capacity" id="hiddenMaxCapacity" value="50">

                <button type="submit" class="create-btn">Create Event</button>
            </div>
        </section>

        <div id="capacityModal" class="modal-overlay">
            <div class="modal-card">
                <button type="button" class="modal-close-btn" onclick="handleCloseCapacityModal()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="modal-header-icon capacity-icon">
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <h2 class="modal-title">Max Capacity</h2>
                <p class="modal-subtitle">Close registration when reaching capacity parameters.</p>
              
                <div class="modal-content-box">
                    <div class="modal-row-item">
                        <span class="row-label">Limit Event Capacity</span>
                        <label class="switch">
                            <input type="checkbox" id="limitCapacityToggle" checked onchange="toggleCapacityInput()">
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="modal-row-item" id="capacityInputRow">
                        <span class="row-label">Max Capacity</span>
                        <input type="number" id="maxCapacityInput" class="modal-numeric-input" value="50" min="1">
                    </div>
                </div>
                <button type="button" class="modal-confirm-btn" onclick="saveCapacitySettings()">Confirm</button>
            </div>
        </div>
    </form>

    <script src="js/create-events.js"></script>
    <script>
        // Holds global access string reference to default placeholder map view layout
        const placeholderImg = '<?php echo $black_placeholder; ?>';

        function previewImage(input, previewId, removeBtnId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById(previewId).src = e.target.result;
                    document.getElementById(removeBtnId).style.display = 'flex'; // Reveals remove button
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function clearImage(inputId, previewId, removeBtnId) {
            document.getElementById(inputId).value = ""; // Clear file choice tracking
            document.getElementById(previewId).src = placeholderImg; // Revert layout to black canvas
            document.getElementById(removeBtnId).style.display = 'none'; // Hide delete wrapper until next update
        }
    </script>
</body>
</html>