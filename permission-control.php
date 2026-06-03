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
          
          <div class="custom-select-wrapper">
            <select id="searchFilter" class="toolbar-select">
              <option value="">Search</option>
            </select>
            <i class="fa-solid fa-chevron-down select-caret"></i>
          </div>

          <div class="custom-select-wrapper">
            <select id="roleFilter" class="toolbar-select">
              <option value="">Role</option>
              <option value="admin">Admin</option>
              <option value="moderator">Moderator</option>
            </select>
            <i class="fa-solid fa-chevron-down select-caret"></i>
          </div>

        </div>

        <button type="button" class="btn-add-user" onclick="handleAddUserClick()">
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

  <script>
    function handleAddUserClick() {
      console.log("Trigger Add User dialog interface layer.");
    }
    
    function editUserPermission(userName) {
      console.log("Modify system properties permissions for structural account node:", userName);
    }

    function deleteUserPermission(userName) {
      if(confirm(`Are you sure you want to revoke system panel access permissions from ${userName}?`)) {
        console.log("Revoke application credentials request executed for target entry.");
      }
    }
  </script>
</body>
</html>