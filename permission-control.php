<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: cvsu-login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$profile_picture = 'images/person3.png';

// Get current user's profile picture
$stmt = $conn->prepare('SELECT profile_picture FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if ($user && !empty($user['profile_picture'])) {
    $profile_picture = htmlspecialchars($user['profile_picture']);
}

// Get organization where the current user is an admin/moderator
$organization = null;
$org_admin_role = null;

$stmt = $conn->prepare('
    SELECT o.id, o.name, o.main_admin_id, oa.role
    FROM organizations o
    JOIN organization_admins oa ON o.id = oa.organization_id
    WHERE oa.user_id = ? LIMIT 1
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($org_row = $result->fetch_assoc()) {
    $organization = $org_row;
    $org_admin_role = $org_row['role'];
}

// If also a main_admin, find their organization
if (!$organization) {
    $stmt = $conn->prepare('
        SELECT id, name, main_admin_id FROM organizations WHERE main_admin_id = ? LIMIT 1
    ');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($org_row = $result->fetch_assoc()) {
        $organization = $org_row;
        $org_admin_role = 'main_admin';
        $organization['role'] = 'main_admin';
    }
}

if (!$organization) {
    header('Location: dashboard.php?error=no_organization');
    exit;
}

$organization_id = $organization['id'];
$is_main_admin = ($user_id == $organization['main_admin_id']);
// Allow both main_admin and admin (but not moderator) to manage permissions
$can_manage_permissions = ($is_main_admin || $org_admin_role === 'admin');

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'search_users') {
        $search_email = isset($_POST['email']) ? trim($_POST['email']) : '';
        
        if (strlen($search_email) < 2) {
            echo json_encode(['users' => []]);
            exit;
        }
        
        // Search for CvSU users not already in organization_admins and not the main admin
        $search_term = '%' . $search_email . '%';
        $stmt = $conn->prepare('
            SELECT u.id, u.first_name, u.last_name, u.email, u.profile_picture
            FROM users u
            WHERE (u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)
            AND u.account_type = "cvsu"
            AND u.id != ?
            AND u.id != (SELECT main_admin_id FROM organizations WHERE id = ?)
            AND u.id NOT IN (
                SELECT user_id FROM organization_admins WHERE organization_id = ?
            )
            LIMIT 10
        ');
        $stmt->bind_param('sssiii', $search_term, $search_term, $search_term, $user_id, $organization_id, $organization_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = [
                'id' => $row['id'],
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'email' => $row['email'],
                'profile_picture' => $row['profile_picture'] ?: 'images/person3.png'
            ];
        }
        
        echo json_encode(['users' => $users]);
        exit;
    }
    
    if ($_POST['action'] === 'add_user') {
        // Only main admin and admin can add users (not moderator)
        if (!$can_manage_permissions) {
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        $new_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $role = isset($_POST['role']) ? $_POST['role'] : 'moderator';
        
        if (!in_array($role, ['admin', 'moderator'])) {
            $role = 'moderator';
        }
        
        if ($new_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user']);
            exit;
        }
        
        // Cannot add the main admin again
        if ($new_user_id == $organization['main_admin_id']) {
            echo json_encode(['success' => false, 'message' => 'This user is already the main admin']);
            exit;
        }

        // Only CvSU accounts can be added
        $stmt = $conn->prepare('SELECT account_type FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $new_user_id);
        $stmt->execute();
        $user_check = $stmt->get_result()->fetch_assoc();
        if (!$user_check || $user_check['account_type'] !== 'cvsu') {
            echo json_encode(['success' => false, 'message' => 'Only CvSU accounts can be added to the organization']);
            exit;
        }
        
        // Check if user is already in organization_admins
        $stmt = $conn->prepare('SELECT id FROM organization_admins WHERE organization_id = ? AND user_id = ?');
        $stmt->bind_param('ii', $organization_id, $new_user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'User already added']);
            exit;
        }
        
        // Add user to organization_admins
        $stmt = $conn->prepare('
            INSERT INTO organization_admins (organization_id, user_id, role)
            VALUES (?, ?, ?)
        ');
        $stmt->bind_param('iis', $organization_id, $new_user_id, $role);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add user']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete_user') {
        // Only main admin and admin can delete users (not moderator)
        if (!$can_manage_permissions) {
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit;
        }
        
        $delete_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        if ($delete_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user']);
            exit;
        }
        
        // Cannot delete main admin
        if ($delete_user_id == $organization['main_admin_id']) {
            echo json_encode(['success' => false, 'message' => 'Cannot remove the main admin']);
            exit;
        }
        
        // Delete user from organization_admins
        $stmt = $conn->prepare('
            DELETE FROM organization_admins
            WHERE organization_id = ? AND user_id = ?
        ');
        $stmt->bind_param('ii', $organization_id, $delete_user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove user']);
        }
        exit;
    }
}

// -----------------------------------------------------------------------
// Fetch all organization members using UNION:
//   1. The main admin from the organizations table (always shown first)
//   2. All users in organization_admins (excluding the main admin to avoid duplicates)
// -----------------------------------------------------------------------
$organization_admins = [];

$stmt = $conn->prepare('
    SELECT
        u.id AS user_id,
        "admin" AS role,
        u.first_name,
        u.last_name,
        u.email,
        u.profile_picture
    FROM organizations o
    JOIN users u ON o.main_admin_id = u.id
    WHERE o.id = ?

    UNION

    SELECT
        oa.user_id,
        oa.role,
        u.first_name,
        u.last_name,
        u.email,
        u.profile_picture
    FROM organization_admins oa
    JOIN users u ON oa.user_id = u.id
    WHERE oa.organization_id = ?
      AND oa.user_id != (SELECT main_admin_id FROM organizations WHERE id = ?)

    ORDER BY CASE WHEN user_id = ? THEN 0 ELSE 1 END, role ASC
');
$stmt->bind_param('iiii', $organization_id, $organization_id, $organization_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $organization_admins[] = $row;
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
                <li><a href="Myprofile.php">My Profile</a></li>
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
          
          <div style="width: 140px;">
            <div class="dropdown-selected-box" onclick="toggleDropdownEngine('toolbarSearchDropdown')" style="border-radius: 20px; border: 1px solid #000000; padding: 0.45rem 1.25rem;">
              <span style="color: #000000; font-size: 0.9rem;">Search</span>
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

        <?php if ($can_manage_permissions): ?>
        <button type="button" class="btn-add-user" onclick="openAddUserModal()">
          <i class="fa-solid fa-plus"></i> Add User
        </button>
        <?php endif; ?>
      </div>

      <div class="table-responsive-wrapper">
        <table class="permissions-table">
          <thead>
            <tr>
              <th>Full Name</th>
              <th>Email Address</th>
              <th>Role</th>
              <th class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody id="adminTableBody">
            <?php foreach ($organization_admins as $admin): 
              $full_name = htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']);
              $email = htmlspecialchars($admin['email']);
              $profile_pic = $admin['profile_picture'] ? htmlspecialchars($admin['profile_picture']) : 'images/person3.png';
              $is_current_user = ($admin['user_id'] == $user_id);
              $is_main = ($admin['user_id'] == $organization['main_admin_id']);
              
              // Determine role display text
              if ($is_current_user) {
                // Display current user's role
                if ($is_main) {
                  $role_text = 'You are the Main Admin';
                } elseif ($admin['role'] === 'admin') {
                  $role_text = 'You are the Admin';
                } else {
                  $role_text = 'You are the Moderator';
                }
              } else {
                // Display other user's role
                if ($is_main) {
                  $role_text = 'Main Admin';
                } elseif ($admin['role'] === 'admin') {
                  $role_text = 'Admin';
                } else {
                  $role_text = 'Moderator';
                }
              }
            ?>
            <tr>
              <td>
                <div class="user-identity">
                  <img src="<?php echo $profile_pic; ?>" alt="<?php echo $full_name; ?>" class="table-avatar">
                  <span class="user-name"><?php echo $full_name; ?></span>
                </div>
              </td>
              <td><span class="user-email"><?php echo $email; ?></span></td>
              <td><span class="user-role-text"><?php echo $role_text; ?></span></td>
              <td class="text-center">
                <?php if (!$is_main && $can_manage_permissions): ?>
                <div class="action-buttons-group">
                  <button type="button" class="action-btn delete-btn" onclick="deleteUserPermission(<?php echo $admin['user_id']; ?>, '<?php echo $full_name; ?>')" title="Remove User">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
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
      
      <div class="modal-form-group" style="position: relative;">
        <label class="modal-label">Enter Email or Search</label>
        <input type="text" class="modal-input" id="emailSearchInput" placeholder="Search accounts or enter email addresses..." onkeyup="searchUsers()">
        <div id="searchSuggestions" class="search-suggestions" style="display: none; position: absolute; background: white; border: 1px solid #ddd; border-radius: 8px; max-height: 250px; overflow-y: auto; width: 100%; z-index: 1000;"></div>
      </div>
      <input type="hidden" id="selectedUserId" value="">
      <input type="hidden" id="selectedUserName" value="">
      
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
    let selectedUser = null;

    function toggleDropdownEngine(dropdownId) {
      event.stopPropagation();
      
      // Close other dropdowns
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
        if (containerId === 'roleModalDropdown') {
          labelElement.style.color = '#0f172a';
        }
      }

      const hiddenInput = container.querySelector('input[type="hidden"]');
      if (hiddenInput) {
        hiddenInput.value = processingValue;
      }

      container.classList.remove('active');
    }

    // Search users by email
    function searchUsers() {
      const emailInput = document.getElementById('emailSearchInput');
      const email = emailInput.value.trim();
      const suggestionsDiv = document.getElementById('searchSuggestions');

      if (email.length < 2) {
        suggestionsDiv.style.display = 'none';
        return;
      }

      fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=search_users&email=' + encodeURIComponent(email)
      })
      .then(response => response.json())
      .then(data => {
        if (data.users && data.users.length > 0) {
          suggestionsDiv.innerHTML = data.users.map(user => `
            <div class="search-suggestion-item" onclick="selectUser(${user.id}, '${user.name}', '${user.email}')" style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; display: flex; align-items: center; gap: 10px;">
              <img src="${user.profile_picture}" alt="${user.name}" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
              <div>
                <div style="font-weight: 500;">${user.name}</div>
                <div style="font-size: 0.85rem; color: #666;">${user.email}</div>
              </div>
            </div>
          `).join('');
          suggestionsDiv.style.display = 'block';
        } else {
          suggestionsDiv.innerHTML = '<div style="padding: 15px; text-align: center; color: #999;">No users found</div>';
          suggestionsDiv.style.display = 'block';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        suggestionsDiv.style.display = 'none';
      });
    }

    // Select user from search results
    function selectUser(userId, userName, userEmail) {
      document.getElementById('selectedUserId').value = userId;
      document.getElementById('selectedUserName').value = userName;
      document.getElementById('emailSearchInput').value = userName + ' (' + userEmail + ')';
      document.getElementById('searchSuggestions').style.display = 'none';
      selectedUser = { id: userId, name: userName, email: userEmail };
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
          // Reset form
          document.getElementById('emailSearchInput').value = '';
          document.getElementById('selectedUserId').value = '';
          document.getElementById('selectedUserName').value = '';
          document.getElementById('roleModalDropdown').querySelector('.selected-value-label').innerText = 'Choose a role...';
          document.getElementById('roleModalDropdown').querySelector('.selected-value-label').style.color = '';
          document.getElementById('hiddenRoleInput').value = '';
          selectedUser = null;
        }, 200);
      }
    }

    // Add user to organization
    function addUserToOrganization() {
      const userId = document.getElementById('selectedUserId').value;
      const role = document.getElementById('hiddenRoleInput').value;

      if (!userId) {
        alert('Please select a user');
        return;
      }

      if (!role) {
        alert('Please select a role');
        return;
      }

      fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=add_user&user_id=' + userId + '&role=' + role
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('User added successfully!');
          closeAddUserModal();
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while adding the user');
      });
    }

    // Delete user from organization
    function deleteUserPermission(userId, userName) {
      if (confirm(`Are you sure you want to remove ${userName} from this organization?`)) {
        fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: 'action=delete_user&user_id=' + userId
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('User removed successfully!');
            location.reload();
          } else {
            alert('Error: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('An error occurred while removing the user');
        });
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      const saveBtn = document.getElementById('saveUserBtn');
      if (saveBtn) {
        saveBtn.onclick = addUserToOrganization;
      }
    });

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
  </script>
  <script src="js/navbar.js"></script>
  
</body>
</html>