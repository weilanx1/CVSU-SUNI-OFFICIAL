<?php
session_start();
require_once 'db.php';

$profile_picture = 'images/person3.png';
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    $stmt = $conn->prepare('SELECT profile_picture FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user && !empty($user['profile_picture'])) {
        $profile_picture = htmlspecialchars($user['profile_picture']);
    }
}

// ── Resolve org ───────────────────────────────────────────────────────────────
$org_id = null;
if ($user_id) {
    $oq = "SELECT o.id FROM organizations o WHERE o.main_admin_id = ?
           UNION
           SELECT o.id FROM organization_admins oa
           JOIN organizations o ON oa.organization_id = o.id
           WHERE oa.user_id = ? LIMIT 1";
    $os = $conn->prepare($oq);
    $os->bind_param('ii', $user_id, $user_id);
    $os->execute();
    if ($row = $os->get_result()->fetch_assoc()) $org_id = $row['id'];
}

// ── Load event ────────────────────────────────────────────────────────────────
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Organization Dashboard - Registration Settings</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  
  <link rel="stylesheet" href="css/manage-events-registration.css"/>
  <link rel="stylesheet" href="css/org-navbar.css">
  <link rel="stylesheet" href="css/sidebar.css"/>
</head>
<body>

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
    <form method="POST" action="save-registration-settings.php?event_id=<?php echo $event_id; ?>" id="mainRegistrationSettingsForm">
        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
        <div class="content">
            <a href="manage.php" class="back-link">
              <i class="fa-solid fa-arrow-left"></i> Back to Manage Events
            </a>

            <h2 class="page-title">Edit and Manage Event</h2>
            <p class="page-subtitle">Update your event details and settings.</p>

            <div class="content-tabs">
              <a href="manage-events.php?event_id=<?php echo $event_id; ?>" class="tab-item">Details</a>
              <a href="manage-events-banner.php?event_id=<?php echo $event_id; ?>" class="tab-item">Banner</a>
              <a href="manage-events-guest.php?event_id=<?php echo $event_id; ?>" class="tab-item">Guest</a>
              <a href="manage-events-registration.php?event_id=<?php echo $event_id; ?>" class="tab-item active">Registration</a>
            </div>
            
            <div class="registration-main-container">
                <div class="status-grid">
                    <div class="status-card-clickable" id="registrationStatusCard">
                        <span class="status-card-label">Registration</span>
                        <span class="status-card-value">open</span>
                    </div>
                    <div class="status-card-clickable" id="eventCapacityCard">
                        <span class="status-card-label">Event Capacity</span>
                        <span class="status-card-value" id="capacityDisplayValue">Unlimited</span>
                    </div>
                </div>

                <div class="tickets-section">
                    <h3>Tickets</h3>
                    <div class="ticket-card-container">
                        <div class="ticket-left">
                            <span><strong>Standard</strong> Free</span>
                        </div>
                        
                        <div class="custom-app-dropdown" id="approvalDropdown">
                            <div class="dropdown-selected-trigger">
                                <span class="dropdown-display-text">Require Approval</span>
                                <i class="fa-solid fa-chevron-down dropdown-caret"></i>
                            </div>
                            <ul class="dropdown-menu-panel">
                                <li data-value="required" class="selected">
                                    <span>Require Approval</span>
                                    <i class="fa-solid fa-check dropdown-check-icon"></i>
                                </li>
                                <li data-value="not_required">
                                    <span>Don't Require</span>
                                    <i class="fa-solid fa-check dropdown-check-icon"></i>
                                </li>
                            </ul>
                            <input type="hidden" name="require_approval_setting" id="approvalSettingInput" value="required">
                        </div>
                    </div>
                </div>

                <div class="questions-section">
                    <h3>Personal Questions</h3>
                    <p class="section-desc">We will ask guests the following questions when they register for the event.</p>
                    
                    <div class="question-list-row">
                        <div class="question-card-item">
                            <div class="card-left-content">
                                <span class="icon-span"><i class="fa-regular fa-user"></i></span>
                                <span class="label-span">Name</span>
                            </div>
                            
                            <div class="custom-luma-dropdown" id="nameQuestionDropdown">
                                <div class="luma-selected-trigger">
                                    <span class="luma-display-text">Full Name</span>
                                    <i class="fa-solid fa-chevron-down luma-caret"></i>
                                </div>
                                
                                <ul class="luma-dropdown-menu">
                                    <li data-value="full" class="selected">
                                        <span>Full Name</span>
                                        <i class="fa-solid fa-check luma-check-icon"></i>
                                    </li>
                                    <li data-value="first">
                                        <span>First Name Only</span>
                                        <i class="fa-solid fa-check luma-check-icon"></i>
                                    </li>
                                </ul>
                                <input type="hidden" name="name_question_setting" id="nameSettingInput" value="full">
                            </div>
                        </div>
                        
                        <div class="question-card-item">
                            <div class="card-left-content">
                                <span class="icon-span"><i class="fa-regular fa-envelope"></i></span>
                                <span class="label-span">Email</span>
                            </div>
                            <div class="badge-required">Required</div>
                            <input type="hidden" name="email_question_setting" value="required">
                        </div>
                        
                        <div class="question-card-item" data-id="phone">
                            <div class="card-left-content">
                                <span class="icon-span"><i class="fa-solid fa-phone-flip"></i></span>
                                <span class="label-span">Phone</span>
                            </div>
                            
                            <div class="custom-luma-dropdown" id="phoneQuestionDropdown">
                                <div class="luma-selected-trigger">
                                    <span class="luma-display-text">Off</span>
                                    <i class="fa-solid fa-chevron-down luma-caret"></i>
                                </div>
                                
                                <ul class="luma-dropdown-menu">
                                    <li data-value="on">
                                        <span>On</span>
                                        <i class="fa-solid fa-check luma-check-icon"></i>
                                    </li>
                                    <li data-value="off" class="selected">
                                        <span>Off</span>
                                        <i class="fa-solid fa-check luma-check-icon"></i>
                                    </li>
                                </ul>
                                <input type="hidden" name="phone_question_setting" id="phoneSettingInput" value="off">
                            </div>
                        </div>
                    </div>

                    <div class="custom-questions-divider">
                        <h3>Custom Questions</h3>
                        <button type="button" class="add-question-btn" onclick="openAddQuestionModal()">
                            + Add a Question
                        </button>
                    </div>
                </div>

                <div style="margin-top: 32px; padding-top: 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
                    <button type="submit" class="app-modal-btn-confirm" style="width: auto; padding: 12px 32px; margin: 0;">
                        Save All Changes
                    </button>
                </div>
            </div>
        </div>

        <div class="app-modal-backdrop" id="capacityModal">
            <div class="app-modal-content">
                <div class="modal-icon-header">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i>
                </div>
                <h2>Max Capacity</h2>
                <p class="modal-description">Close registration when reaching the capacity. Only approved guests count towards it.</p>
                
                <div class="modal-form-row">
                    <label for="limitCapacityToggle">Limit Event Capacity</label>
                    <label class="ui-toggle-switch">
                        <input type="checkbox" name="limit_capacity" value="1" id="limitCapacityToggle" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <div class="modal-form-row input-row" id="maxCapacityInputRow">
                    <label for="maxCapacityNumberInput">Max Capacity</label>
                    <input type="number" name="max_capacity" class="app-modal-number-input" id="maxCapacityNumberInput" value="50">
                </div>
                
                <div class="modal-form-row">
                    <label for="waitlistToggle">Over-Capacity Waitlist</label>
                    <label class="ui-toggle-switch">
                        <input type="checkbox" name="waitlist_enabled" value="1" id="waitlistToggle">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <button type="button" class="app-modal-btn-confirm" id="confirmCapacityBtn">Confirm</button>
            </div>
        </div>

        <div class="app-modal-backdrop" id="registrationModal">
            <div class="app-modal-content">
                <div class="modal-icon-header">
                    <i class="fa-solid fa-ticket"></i>
                </div>
                <h2>Registration</h2>
                <p class="modal-description">Close registration to stop accepting new guests, including anyone who's been invited.</p>
                <p class="modal-description sub-desc">Capacity and availability settings only apply when registration is open.</p>
                
                <div class="modal-form-row">
                    <label for="acceptRegistrationToggle">Accept Registration</label>
                    <label class="ui-toggle-switch">
                        <input type="checkbox" name="accept_registration" value="1" id="acceptRegistrationToggle" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
                
                <button type="button" class="app-modal-btn-confirm" id="confirmRegistrationBtn">Confirm</button>
            </div>
        </div>

        <div class="app-modal-backdrop" id="customQuestionTypeModal">
            <div class="app-modal-content question-type-modal">
                <div class="modal-header-with-close">
                    <div class="modal-icon-header font-icon">
                        <i class="fa-regular fa-comments"></i>
                    </div>
                    <button type="button" class="modal-close-x-btn" onclick="closeCustomQuestionModals()">&times;</button>
                </div>
                
                <h2>Add Question</h2>
                <p class="modal-description">Ask guests custom questions when they register.</p>
                
                <div class="question-types-grid two-choices-only">
                    <div class="type-selection-card active-type" id="typeTextBtn">
                        <i class="fa-solid fa-font type-icon"></i>
                        <span>Text</span>
                    </div>
                    <div class="type-selection-card active-type" id="typeSocialBtn">
                        <i class="fa-regular fa-user type-icon"></i>
                        <span>Social Profile</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-modal-backdrop" id="customQuestionSocialModal">
            <div class="app-modal-content question-setup-modal">
                
                <input type="hidden" name="custom_question_type" id="hiddenQuestionType" value="social">

                <div class="modal-nav-header">
                    <button type="button" class="modal-back-arrow-btn" id="backToTypeBtn">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <span class="modal-nav-title">Add Question</span>
                    <button type="button" class="modal-close-x-btn" onclick="closeCustomQuestionModals()">&times;</button>
                </div>

                <div class="sub-profile-header">
                    <div class="sub-profile-icon" id="setupModalIcon">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="sub-profile-text">
                        <h3 id="setupModalTitle">Social Profile</h3>
                        <p id="setupModalSub">Ask for a social network username</p>
                    </div>
                </div>

                <div class="modal-input-group" id="platformSelectGroup">
                    <label class="input-field-label">Platform</label>
                    <div class="modal-select-wrapper">
                        <select class="modal-native-select" name="custom_social_platform" id="socialPlatformSelect">
                            <option value="LinkedIn">LinkedIn</option>
                            <option value="Twitter">Twitter / X</option>
                            <option value="Instagram">Instagram</option>
                        </select>
                        <i class="fa-solid fa-chevron-down select-caret"></i>
                    </div>
                </div>

                <div class="modal-input-group">
                    <label class="input-field-label">Question</label>
                    <input type="text" class="modal-text-input" name="custom_question_text" id="socialQuestionInput" value="What is your LinkedIn profile?">
                </div>

                <p class="modal-hint-text" id="setupModalHint">We'll automatically get this information from their profile if available.</p>

                <div class="modal-form-row required-toggle-row">
                    <label for="customQuestionRequiredToggle">Required</label>
                    <label class="ui-toggle-switch">
                        <input type="checkbox" name="custom_question_required" value="1" id="customQuestionRequiredToggle">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <button type="button" class="app-modal-btn-confirm target-add-btn" id="confirmCustomQuestionBtn">Add Question</button>
            </div>
        </div>

    </form>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
      
      // A. ORIGINAL ENGINE: Luma Dropdowns (Personal Questions)
      const lumaDropdowns = document.querySelectorAll('.custom-luma-dropdown');
      lumaDropdowns.forEach(dropdown => {
          const triggerBox = dropdown.querySelector('.luma-selected-trigger');
          const displayText = dropdown.querySelector('.luma-display-text');
          const hiddenInput = dropdown.querySelector('input[type="hidden"]');
          const optionsRows = dropdown.querySelectorAll('.luma-dropdown-menu li');

          triggerBox.addEventListener('click', (event) => {
              event.stopPropagation();
              lumaDropdowns.forEach(other => { if (other !== dropdown) other.classList.remove('active'); });
              document.querySelectorAll('.custom-app-dropdown').forEach(d => d.classList.remove('active'));
              dropdown.classList.toggle('active');
          });

          optionsRows.forEach(row => {
              row.addEventListener('click', (event) => {
                  event.stopPropagation();
                  const selectionValue = row.getAttribute('data-value');
                  const selectionText = row.querySelector('span').innerText;

                  optionsRows.forEach(item => item.classList.remove('selected'));
                  row.classList.add('selected');

                  if (displayText) displayText.innerText = selectionText;
                  if (hiddenInput) hiddenInput.value = selectionValue;
                  dropdown.classList.remove('active');
              });
          });
      });

      // B. NEW ENGINE: App Dropdowns (Tickets Section)
      const appDropdowns = document.querySelectorAll('.custom-app-dropdown');
      appDropdowns.forEach(dropdown => {
          const triggerBox = dropdown.querySelector('.dropdown-selected-trigger');
          const displayText = dropdown.querySelector('.dropdown-display-text');
          const hiddenInput = dropdown.querySelector('input[type="hidden"]');
          const optionsRows = dropdown.querySelectorAll('.dropdown-menu-panel li');

          triggerBox.addEventListener('click', (event) => {
              event.stopPropagation();
              appDropdowns.forEach(other => { if (other !== dropdown) other.classList.remove('active'); });
              lumaDropdowns.forEach(d => d.classList.remove('active'));
              dropdown.classList.toggle('active');
          });

          optionsRows.forEach(row => {
              row.addEventListener('click', (event) => {
                  event.stopPropagation();
                  const selectionValue = row.getAttribute('data-value');
                  const selectionText = row.querySelector('span').innerText;

                  optionsRows.forEach(item => item.classList.remove('selected'));
                  row.classList.add('selected');

                  if (displayText) displayText.innerText = selectionText;
                  if (hiddenInput) hiddenInput.value = selectionValue;
                  dropdown.classList.remove('active');
              });
          });
      });

      document.addEventListener('click', () => {
          lumaDropdowns.forEach(d => d.classList.remove('active'));
          appDropdowns.forEach(d => d.classList.remove('active'));
      });

      // C. MODAL CONFIGURATOR HANDLERS (Capacity & Registration)
      const capacityCard = document.getElementById('eventCapacityCard');
      const registrationCard = document.getElementById('registrationStatusCard');
      const capacityModal = document.getElementById('capacityModal');
      const registrationModal = document.getElementById('registrationModal');
      
      const confirmCapacityBtn = document.getElementById('confirmCapacityBtn');
      const confirmRegistrationBtn = document.getElementById('confirmRegistrationBtn');

      capacityCard.addEventListener('click', () => capacityModal.classList.add('show'));
      registrationCard.addEventListener('click', () => registrationModal.classList.add('show'));

      // D. CUSTOM QUESTION WORKFLOW WITH DYNAMIC EFFECTS (Text & Social Engine)
      const typeTextBtn = document.getElementById('typeTextBtn');
      const typeSocialBtn = document.getElementById('typeSocialBtn');
      const backToTypeBtn = document.getElementById('backToTypeBtn');
      
      const typeModal = document.getElementById('customQuestionTypeModal');
      const socialModal = document.getElementById('customQuestionSocialModal');
      
      const hiddenQuestionType = document.getElementById('hiddenQuestionType');
      const platformSelectGroup = document.getElementById('platformSelectGroup');
      const platformSelect = document.getElementById('socialPlatformSelect');
      const questionInput = document.getElementById('socialQuestionInput');
      
      const setupModalIcon = document.getElementById('setupModalIcon');
      const setupModalTitle = document.getElementById('setupModalTitle');
      const setupModalSub = document.getElementById('setupModalSub');
      const setupModalHint = document.getElementById('setupModalHint');
      const confirmCustomQuestionBtn = document.getElementById('confirmCustomQuestionBtn');

      // Pag pinindot ang TEXT choice (Dynamic Tweak & Form Effect)
      if (typeTextBtn) {
          typeTextBtn.addEventListener('click', () => {
              hiddenQuestionType.value = "text";
              platformSelectGroup.style.display = "none"; // Itatago ang social platform select row
              setupModalIcon.innerHTML = '<i class="fa-solid fa-font"></i>';
              setupModalTitle.textContent = "Text Question";
              setupModalSub.textContent = "Ask guests a generic text question";
              setupModalHint.style.display = "none"; // Itatago ang auto-fill explanation hint
              questionInput.value = "Write your question here...";
              
              typeModal.classList.remove('show');
              socialModal.classList.add('show');
          });
      }

      // Pag pinindot ang SOCIAL PROFILE choice (Dynamic Tweak & Form Effect)
      if (typeSocialBtn) {
          typeSocialBtn.addEventListener('click', () => {
              hiddenQuestionType.value = "social";
              platformSelectGroup.style.display = "flex"; // Iapakita ang social options array wrapper
              setupModalIcon.innerHTML = '<i class="fa-regular fa-user"></i>';
              setupModalTitle.textContent = "Social Profile";
              setupModalSub.textContent = "Ask for a social network username";
              setupModalHint.style.display = "block";
              questionInput.value = `What is your ${platformSelect.value} profile?`;
              
              typeModal.classList.remove('show');
              socialModal.classList.add('show');
          });
      }

      // Back Button click effect mula Step 2 pabalik ng Step 1 Choice Menu
      if (backToTypeBtn) {
          backToTypeBtn.addEventListener('click', () => {
              socialModal.classList.remove('show');
              typeModal.classList.add('show');
          });
      }

      // Live text placeholder logic kapag binago ang social options active value
      if (platformSelect && questionInput) {
          platformSelect.addEventListener('change', function() {
              if (hiddenQuestionType.value === "social") {
                  questionInput.value = `What is your ${this.value} profile?`;
              }
          });
      }

      // Local Confirm handler para sa Custom Question modal fields array save
      if (confirmCustomQuestionBtn) {
          confirmCustomQuestionBtn.addEventListener('click', () => {
              console.log("Custom question dynamic parameters logged local state.");
              closeCustomQuestionModals();
          });
      }

      // Backdrop window clicks monitoring
      window.addEventListener('click', (e) => {
          if (e.target === capacityModal) capacityModal.classList.remove('show');
          if (e.target === registrationModal) registrationModal.classList.remove('show');
          if (e.target === typeModal) typeModal.classList.remove('show');
          if (e.target === socialModal) socialModal.classList.remove('show');
      });

      confirmCapacityBtn.addEventListener('click', () => {
          const limitToggle = document.getElementById('limitCapacityToggle').checked;
          const numberVal = document.getElementById('maxCapacityNumberInput').value;
          document.getElementById('capacityDisplayValue').innerText = limitToggle ? numberVal : 'Unlimited';
          capacityModal.classList.remove('show');
      });

      confirmRegistrationBtn.addEventListener('click', () => {
          const acceptToggle = document.getElementById('acceptRegistrationToggle').checked;
          registrationCard.querySelector('.status-card-value').innerText = acceptToggle ? 'open' : 'closed';
          registrationModal.classList.remove('show');
      });

      document.getElementById('limitCapacityToggle').addEventListener('change', function() {
          document.getElementById('maxCapacityInputRow').style.display = this.checked ? 'flex' : 'none';
      });
  });

  function openAddQuestionModal() {
      document.getElementById('customQuestionTypeModal').classList.add('show');
  }
  
  function closeCustomQuestionModals() {
      document.getElementById('customQuestionTypeModal').classList.remove('show');
      document.getElementById('customQuestionSocialModal').classList.remove('show');
  }
  </script>
</body>
</html>