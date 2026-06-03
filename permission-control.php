<?php
session_start();
require_once 'db.php';

$profile_picture = 'images/person3.png';
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Organization Dashboard - Permission Control</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <link rel="stylesheet" href="css/permissions-control.css"/>
  <link rel="stylesheet" href="css/org-navbar.css">
  <link rel="stylesheet" href="css/sidebar.css"/>
</head>
<body>

  <div class="sidebar">
    <div class="sidebar-top">
      <div class="menu">
        <img src="images/logo.png" class="sidebar-logo">
        
        <a href="dashboard.php">
          <img src="images/dashboard.png" class="sidebar-icon">
          Dashboard
        </a>

        <a href="manage.php">
          <img src="images/manageevent.png" class="sidebar-icon">
          Manage Events
        </a>

        <a href="org-profile.php">
          <img src="images/person2.png" class="sidebar-icon">
          Profile
        </a>

        <a href="#">
          <img src="images/analytics.png" class="sidebar-icon">
          Analytics
        </a>

        <a href="permission-control.php" class="active">
          <img src="images/permission.png" class="sidebar-icon">
          Permission Control
        </a>

        <a href="#">
          <img src="images/settings.png" class="sidebar-icon">
          Settings
        </a>
      </div>
    </div>

    <div class="sidebar-bottom">
      <button class="org-btn">
        View Organization Page
        <i class="fa-solid fa-arrow-up-right-from-square"></i>
      </button>
    </div>
  </div>

  <div class="main">
    <nav>
        <div class="container nav-container">
            <a href="#">
                <img src="images/logo.png" alt="Suni Logo" style="display:none;">
            </a>
            <ul>
                <li><a href="create-events.php">+ Create Events</a></li>
                <li><a href="index.php">CvSU Events</a></li>
                <li><a href="#">My Profile</a></li>
                <li><a href="dashboard.php" class="active">Organization Dashboard</a></li>
                <li class="nav-icons">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <i class="fa-regular fa-bell fa-lg"></i>
                </li>
                <li><img src="<?php echo $profile_picture; ?>" class="profile"></li>
            </ul>
        </div>
    </nav>

    <div class="permissions-view-container">
      
      <h1 class="view-title">Permission Control</h1>

      <div class="table-toolbar">
        <div class="filter-group">
          
          <div class="custom-modal-dropdown" id="toolbarSearchDropdown" style="width: 140px;">
            <div class="dropdown-selected-box" onclick="toggleDropdownEngine('toolbarSearchDropdown')" style="border-radius: 20px; border: 1px solid #000000; padding: 0.45rem 1.25rem;">
              <span class="selected-value-label" style="color: #000000; font-size: 0.9rem;">Search</span>
              <i class="fa-solid fa-chevron-down modal-select-caret"></i>
            </div>
            <ul class="dropdown-options-list">
              <li onclick="selectDropdownOption('toolbarSearchDropdown', 'Search', '')">Search</li>
            </ul>
            <input type="hidden" name="filter_search" id="hiddenSearchInput" value="">
          </div>

          <div class="custom-modal-dropdown" id="toolbarRoleDropdown" style="width: 140px;">
            <div class="dropdown-selected-box" onclick="toggleDropdownEngine('toolbarRoleDropdown')" style="border-radius: 20px; border: 1px solid #000000; padding: 0.45rem 1.25rem;">
              <span class="selected-value-label" style="color: #000000; font-size: 0.9rem;">Role</span>
              <i class="fa-solid fa-chevron-down modal-select-caret"></i>
            </div>
            <ul class="dropdown-options-list">
              <li onclick="selectDropdownOption('toolbarRoleDropdown', 'Role', '')">Role</li>
              <li onclick="selectDropdownOption('toolbarRoleDropdown', 'Admin', 'admin')">Admin</li>
              <li onclick="selectDropdownOption('toolbarRoleDropdown', 'Moderator', 'moderator')">Moderator</li>
            </ul>
            <input type="hidden" name="filter_role" id="hiddenRoleFilterInput" value="">
          </div>

        </div>

        <button type="button" class="btn-add-user" onclick="openAddUserModal()">
          <i class="fa-solid fa-plus"></i> Add User
        </button>
      </div>

      <div class="table-responsive-wrapper">
        <table class="permissions-table">
          <thead>
            <tr>
              <th>Full Name</th>
              <th>Email Address</th>
              <th>Role</th>
              <th>Last Active</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            
            <tr>
              <td>
                <div class="user-identity">
                  <img src="images/sid.png" alt="Xeed Love" class="table-avatar">
                  <span class="user-name">Xeed Love L. Magtira</span>
                </div>
              </td>
              <td><span class="user-email">magtiraxeedlove@gmail.com</span></td>
              <td><span class="user-role-text">Your are the Admin</span></td>
              <td><span class="user-activity-status">2 mins ago</span></td>
              <td class="text-center"></td>
            </tr>

            <tr>
              <td>
                <div class="user-identity">
                  <div class="table-avatar placeholder-avatar"></div>
                  <span class="user-name">Sem Pablo R. Mateo</span>
                </div>
              </td>
              <td><span class="user-email">sempablomateo@gmail.com</span></td>
              <td><span class="user-role-text">Moderator</span></td>
              <td><span class="user-activity-status">1 hour ago</span></td>
              <td class="text-center">
                <div class="action-buttons-group">
                  <button type="button" class="action-btn edit-btn" onclick="editUserPermission('Sem Pablo R. Mateo')" title="Edit Role"><i class="fa-solid fa-pen"></i></button>
                  <button type="button" class="action-btn delete-btn" onclick="deleteUserPermission('Sem Pablo R. Mateo')" title="Remove User"><i class="fa-solid fa-trash-can"></i></button>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div class="user-identity">
                  <div class="table-avatar placeholder-avatar"></div>
                  <span class="user-name">Kraig Friel B. Gonzales</span>
                </div>
              </td>
              <td><span class="user-email">kraigfrielgonzales@gmail.com</span></td>
              <td><span class="user-role-text">Moderator</span></td>
              <td><span class="user-activity-status">5 days ago</span></td>
              <td class="text-center">
                <div class="action-buttons-group">
                  <button type="button" class="action-btn edit-btn" onclick="editUserPermission('Kraig Friel B. Gonzales')"><i class="fa-solid fa-pen"></i></button>
                  <button type="button" class="action-btn delete-btn" onclick="deleteUserPermission('Kraig Friel B. Gonzales')"><i class="fa-solid fa-trash-can"></i></button>
                </div>
              </td>
            </tr>

            <tr>
              <td>
                <div class="user-identity">
                  <div class="table-avatar placeholder-avatar"></div>
                  <span class="user-name">Vincent P. Garcia</span>
                </div>
              </td>
              <td><span class="user-email">vincentgarcia@gmail.com</span></td>
              <td><span class="user-role-text">Moderator</span></td>
              <td><span class="user-activity-status">10 mins ago</span></td>
              <td class="text-center">
                <div class="action-buttons-group">
                  <button type="button" class="action-btn edit-btn" onclick="editUserPermission('Vincent P. Garcia')"><i class="fa-solid fa-pen"></i></button>
                  <button type="button" class="action-btn delete-btn" onclick="deleteUserPermission('Vincent P. Garcia')"><i class="fa-solid fa-trash-can"></i></button>
                </div>
              </td>
            </tr>

          </tbody>
        </table>
      </div>

    </div>
  </div>

            <div id="addHostModal" class="modal-overlay">
              <div class="modal-card">
                
                <button type="button" class="modal-close-btn" onclick="closeAddUserModal()">
                  <i class="fa-solid fa-xmark"></i>
                </button>
                
                <div class="modal-header-icon">
                  <i class="fa-solid fa-user-gear"></i>
                </div>
                
                <h2 class="modal-title">Add User</h2>
                <p class="modal-subtitle">Add a user to get help managing the event.</p>
                
                <div class="modal-form-group">
                  <label class="modal-label">Enter Email or Search</label>
                  <input type="text" class="modal-input" placeholder="Search accounts or enter email addresses...">
                </div>
                
                <div class="modal-form-group">
                  <label class="modal-label">Select Role</label>
                  <div class="custom-modal-dropdown" id="roleModalDropdown">
                    
                    <div class="dropdown-selected-box" onclick="toggleDropdownEngine('roleModalDropdown')">
                      <span class="selected-value-label" id="selectedRoleText">Choose a role...</span>
                      <i class="fa-solid fa-chevron-down modal-select-caret"></i>
                    </div>
                    
                    <ul class="dropdown-options-list">
                      <li onclick="selectDropdownOption('roleModalDropdown', 'Admin', 'admin')">Admin</li>
                      <li onclick="selectDropdownOption('roleModalDropdown', 'Moderator', 'moderator')">Moderator</li>
                    </ul>

                    <input type="hidden" name="modal_user_role" id="hiddenRoleInput" value="">
                  </div>
                </div>
                
                <div class="modal-empty-state">
                  <span class="empty-title">No Suggestions Found</span>
                  <span class="empty-desc">You can invite users by entering their email address.</span>
                </div>

                <div class="modal-actions-footer">
                  <button type="submit" class="btn-modal-primary" id="saveUserBtn">Add User</button>
                </div>



              </div>
            </div>

  <script>
    function toggleDropdownEngine(dropdownId) {
      event.stopPropagation();
      
      // Isinasara ang ibang dropdown kapag may binuksang bago
      document.querySelectorAll('.custom-modal-dropdown').forEach(dropdown => {
        if (dropdown.id !== dropdownId) {
          dropdown.classList.remove('active');
        }
      });

      const targetDropdown = document.getElementById(dropdownId);
      if (targetDropdown) {
        targetDropdown.classList.toggle('active');
      }
    }

    function selectDropdownOption(containerId, displayLabel, processingValue) {
      const container = document.getElementById(containerId);
      if (!container) return;

      const labelElement = container.querySelector('.selected-value-label');
      if (labelElement) {
        labelElement.innerText = displayLabel;
        
        // Pinapalitan ang kulay ng text kapag nakapili na (para sa modal body area)
        if (containerId === 'roleModalDropdown') {
          labelElement.style.color = '#0f172a';
        }
      }

      const hiddenInput = container.querySelector('input[type="hidden"]');
      if (hiddenInput) {
        hiddenInput.value = processingValue;
        console.log(`Dropdown tagumpay na nabago (${containerId}):`, processingValue);
      }

      container.classList.remove('active');
    }

    function openAddUserModal() {
      const modal = document.getElementById('addHostModal');
      if (modal) {
        modal.style.display = 'flex';
        setTimeout(() => {
          modal.classList.add('show-modal');
        }, 10);
      }
    }
    
    function closeAddUserModal() {
      const modal = document.getElementById('addHostModal');
      if (modal) {
        modal.classList.remove('show-modal');
        setTimeout(() => {
          modal.style.display = 'none';
          document.querySelectorAll('.custom-modal-dropdown').forEach(dropdown => {
            dropdown.classList.remove('active');
          });
        }, 200);
      }
    }

    window.addEventListener('click', function(e) {
      document.querySelectorAll('.custom-modal-dropdown').forEach(dropdown => {
        if (!dropdown.contains(e.target)) {
          dropdown.classList.remove('active');
        }
      });
      
      const modal = document.getElementById('addHostModal');
      if (e.target === modal) {
        closeAddUserModal();
      }
    });
    
    function editUserPermission(userName) { console.log("Edit requested for:", userName); }
    function deleteUserPermission(userName) { if(confirm(`Are you sure you want to remove ${userName}?`)) console.log("Delete executed for:", userName); }
  </script>
  <script src="js/navbar.js"></script>
  
</body>
</html>