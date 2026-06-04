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
// Determine user's organization and ensure access
$org_id = null;
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
  $oq = "SELECT o.id FROM organizations o WHERE o.main_admin_id = ? UNION SELECT o.id FROM organization_admins oa JOIN organizations o ON oa.organization_id = o.id WHERE oa.user_id = ? LIMIT 1";
  $os = $conn->prepare($oq);
  $os->bind_param('ii', $user_id, $user_id);
  $os->execute();
  $or = $os->get_result();
  if ($row = $or->fetch_assoc()) $org_id = $row['id'];
}

// Load department list for visibility UI
$dept_list = [];
$dept_res = $conn->query("SELECT id, code FROM departments ORDER BY code ASC");
if ($dept_res) {
  while ($d = $dept_res->fetch_assoc()) $dept_list[] = $d;
}

// Get organization department id
$organization_department_id = 0;
if ($org_id) {
  $odq = $conn->prepare('SELECT department_id FROM organizations WHERE id = ? LIMIT 1');
  $odq->bind_param('i', $org_id);
  $odq->execute();
  $odr = $odq->get_result();
  if ($odr && $odr->num_rows) {
    $odrow = $odr->fetch_assoc();
    $organization_department_id = intval($odrow['department_id']);
  }
}

// Load event if event_id provided
$event = null;
if (isset($_GET['event_id']) && $org_id) {
  $eid = intval($_GET['event_id']);
  $est = $conn->prepare('SELECT * FROM events WHERE id = ? AND organization_id = ? LIMIT 1');
  $est->bind_param('ii', $eid, $org_id);
  $est->execute();
  $eres = $est->get_result();
  $event = $eres->fetch_assoc();
  if (!$event) {
    die('Event not found or you do not have access.');
  }
  // load selected departments for this event
  $selected_departments = [];
  $sdst = $conn->prepare('SELECT department_id FROM event_departments WHERE event_id = ?');
  $sdst->bind_param('i', $eid);
  $sdst->execute();
  $sdr = $sdst->get_result();
  while ($rowd = $sdr->fetch_assoc()) $selected_departments[] = intval($rowd['department_id']);
  $selected_departments_csv = implode(',', $selected_departments);
}
// compute friendly visibility display (icons + dept codes)
$visibility_display_inline = '🌐 Public';
if (!empty($event)) {
  if ($event['visibility'] === 'private') {
    $visibility_display_inline = '🔒 Private';
  } elseif ($event['visibility'] === 'department_only') {
    $visibility_display_inline = '🏢 Department Only';
  } elseif ($event['visibility'] === 'restricted') {
    $codes = [];
    if (!empty($selected_departments)) {
      foreach ($selected_departments as $sd) {
        foreach ($dept_list as $d) if ($d['id'] == $sd) $codes[] = $d['code'];
      }
    }
    $visibility_display_inline = '🎯 Restricted' . (!empty($codes) ? ': ' . implode(', ', $codes) : '');
  }
}

// Handle POST update
$save_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && $org_id) {
  $eid = intval($_POST['event_id']);
  // validate ownership
  $vst = $conn->prepare('SELECT id FROM events WHERE id = ? AND organization_id = ? LIMIT 1');
  $vst->bind_param('ii', $eid, $org_id);
  $vst->execute();
  $vres = $vst->get_result();
  if (!$vres->fetch_assoc()) die('Unauthorized');

  $title = trim($_POST['event_name'] ?? '');
  $venue = trim($_POST['event_location'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $start_date = $_POST['start_date'] ?? '';
  $start_time = $_POST['start_time'] ?? '';
  $end_date = $_POST['end_date'] ?? '';
  $end_time = $_POST['end_time'] ?? '';
  $start_dt = date('Y-m-d H:i:s', strtotime($start_date . ' ' . $start_time));
  $end_dt = date('Y-m-d H:i:s', strtotime($end_date . ' ' . $end_time));
  $visibility = isset($_POST['visibility_type']) ? $_POST['visibility_type'] : 'public';
  $selected_departments_post = isset($_POST['selected_departments']) ? trim($_POST['selected_departments']) : '';

  // sanitize visibility
  $allowedVis = ['public','private','department_only','restricted'];
  if (!in_array($visibility, $allowedVis)) $visibility = 'public';

  $upd = $conn->prepare('UPDATE events SET title = ?, description = ?, venue = ?, start_datetime = ?, end_datetime = ?, visibility = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
  $upd->bind_param('ssssssi', $title, $description, $venue, $start_dt, $end_dt, $visibility, $eid);
  if ($upd->execute()) {
    $save_success = true;
    // reload event so form shows latest values
    $est = $conn->prepare('SELECT * FROM events WHERE id = ? AND organization_id = ? LIMIT 1');
    $est->bind_param('ii', $eid, $org_id);
    $est->execute();
    $eres = $est->get_result();
    $event = $eres->fetch_assoc();
    // Do NOT modify event_departments on edit. Only recompute selected departments for display.
    $selected_departments = [];
    $sdst = $conn->prepare('SELECT department_id FROM event_departments WHERE event_id = ?');
    $sdst->bind_param('i', $eid);
    $sdst->execute();
    $sdr = $sdst->get_result();
    while ($rowd = $sdr->fetch_assoc()) $selected_departments[] = intval($rowd['department_id']);
    $selected_departments_csv = implode(',', $selected_departments);

    // recompute friendly visibility display
    $visibility_display_inline = '🌐 Public';
    if ($event['visibility'] === 'department_only') {
      $visibility_display_inline = '🏢 Department Only';
    } elseif ($event['visibility'] === 'restricted') {
      $codes = [];
      if (!empty($selected_departments)) {
        foreach ($selected_departments as $sd) {
          foreach ($dept_list as $d) if ($d['id'] == $sd) $codes[] = $d['code'];
        }
      }
      $visibility_display_inline = '🎯 Restricted' . (!empty($codes) ? ': ' . implode(', ', $codes) : '');
    }
  } else {
    $save_error = 'Failed to save changes.';
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
  <link rel="stylesheet" href="css/manage-events.css"/>
  <link rel="stylesheet" href="css/create-events.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="css/org-navbar.css">
  <link rel="stylesheet" href="css/sidebar.css"/>
  <link rel="stylesheet" href="css/mini-nav.css"/>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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

        <a href="manage.php" class="active">
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

        <a href="permission-control.php">
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

    <a href="manage.php" class="back-link">
      <i class="fa-solid fa-arrow-left"></i> Back to Manage Events
    </a>

    <h2 class="page-title">Edit and Manage Event</h2>
    <p class="page-subtitle">Update your event details and settings.</p>

    <!-- visibility will be shown beside End datetime -->

    <div class="content-tabs">
      <a href="manage-events.php?event_id=<?php echo $event ? intval($event['id']) : ''; ?>" class="tab-item active">Details</a>
      <a href="manage-events-banner.php?event_id=<?php echo $event ? intval($event['id']) : ''; ?>" class="tab-item">Banner</a>
      <a href="manage-events-guest.php?event_id=<?php echo $event ? intval($event['id']) : ''; ?>" class="tab-item">Guest</a>
      <a href="#" class="tab-item" data-text="Registration">Registration</a>
    </div>

    <form action="" method="POST">
      <?php if (!$event): ?>
        <div style="padding:20px; background:#fff7e6; border:1px solid #ffecb3;">Select an event to manage from <a href="manage.php">Manage events</a>.</div>
      <?php endif; ?>
      <?php if (!empty($save_success)): ?>
        <div style="background:#e6ffef; color:#044d22; padding:12px; border-radius:6px; margin-bottom:12px;">Changes saved successfully.</div>
      <?php endif; ?>
      <?php if (!empty($save_error)): ?>
        <div style="background:#fff0f0; color:#8b1d1d; padding:12px; border-radius:6px; margin-bottom:12px;"><?php echo htmlspecialchars($save_error); ?></div>
      <?php endif; ?>
      <div class="manage-card">
        
        <div class="form-group">
          <label for="eventName">Event Name</label>
          <div class="input-wrapper">
              <input type="text" id="eventName" name="event_name" class="form-control" value="<?php echo $event ? htmlspecialchars($event['title']) : ''; ?>" maxlength="120">
              <span class="char-counter"><?php echo $event ? strlen($event['title']) : 0; ?>/120</span>
          </div>
        </div>

        <div class="form-row">
    <div class="form-group">
        <label>Start</label>
        <div class="split-input">
            <div class="input-wrapper date-side">
                <input type="text" id="start_date" name="start_date" class="form-control" value="<?php echo $event ? date('m/d/Y', strtotime($event['start_datetime'])) : ''; ?>">
            </div>
            <div class="input-wrapper time-side">
                <input type="text" id="start_time" name="start_time" class="custom-time-input form-control" value="<?php echo $event ? date('h:i A', strtotime($event['start_datetime'])) : '07:00 AM'; ?>" readonly>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label>End</label>
        <div class="split-input">
            <div class="input-wrapper date-side">
                <input type="text" id="end_date" name="end_date" class="form-control" value="<?php echo $event ? date('m/d/Y', strtotime($event['end_datetime'])) : ''; ?>">
            </div>
            <div class="input-wrapper time-side">
                <input type="text" id="end_time" name="end_time" class="custom-time-input form-control" value="<?php echo $event ? date('h:i A', strtotime($event['end_datetime'])) : '05:00 PM'; ?>" readonly>
            </div>
        </div>
    </div>

    <div class="form-group visibility-col">
        <label>Visibility</label>
        <div class="visibility-component">
            <div class="custom-visibility-trigger" id="visibilityTriggerInline">
                <span id="visibilityValueInline"><?php echo htmlspecialchars($visibility_display_inline); ?></span>
            </div>
            <div class="custom-visibility-panel" id="visibilityPanelInline">

                <div class="visibility-option" data-value="Public"><span>Public</span></div>

                <div class="visibility-option" data-value="Private"><span>Private (Only you can see)</span></div>

                <div class="visibility-option" data-value="Department Only"><span>Department Only</span></div>

                <div class="visibility-option-parent" id="filterDeptOptionInline">

                    <div class="option-header-title"><span>Restricted (Select Departments)</span></div>

                    <div class="dept-checklist" id="deptChecklistInline">

                        <?php foreach ($dept_list as $dept): ?>

                        <label class="checkbox-row">

                            <input type="checkbox" value="<?php echo $dept['id']; ?>" data-code="<?php echo htmlspecialchars($dept['code']); ?>" class="dept-cb-inline" <?php echo in_array(intval($dept['id']), $selected_departments ?? []) ? 'checked' : ''; ?>>

                            <span><?php echo htmlspecialchars($dept['code']); ?></span>

                        </label>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>

        </div>
          <!-- Time zone removed; managed implicitly -->

        </div>
                        </div>
        <div class="form-group">
          <label for="eventLocation">Event Location</label>
          <div class="input-wrapper has-icon">
            <i class="fa-solid fa-location-dot"></i>
                <input type="text" id="location" name="event_location" class="form-control" value="<?php echo $event ? htmlspecialchars($event['venue']) : ''; ?>">
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

        <div class="form-group" style="margin-bottom: 0;">
          <label for="description">Description</label>
          <div class="input-wrapper">
            <textarea id="description" name="description" class="form-control" placeholder="Add a description about your event..." maxlength="2000"><?php echo $event ? htmlspecialchars($event['description']) : ''; ?></textarea>
            <span class="char-counter"><?php echo $event ? strlen($event['description']) : 0; ?>/2000</span>
          </div>
        </div>

      </div>

      <input type="hidden" name="event_id" value="<?php echo $event ? intval($event['id']) : ''; ?>">
      <input type="hidden" name="visibility_type" id="hiddenVisibilityType" value="<?php echo isset($event['visibility']) ? htmlspecialchars($event['visibility']) : 'public'; ?>">
      <input type="hidden" name="selected_departments" id="hiddenSelectedDepartments" value="<?php echo isset($selected_departments_csv) ? htmlspecialchars($selected_departments_csv) : ''; ?>">
      <div class="form-actions">
        <button type="button" id="btnCancel" class="btn btn-cancel">Cancel</button>
        <button type="submit" class="btn btn-save">Save Changes</button>
      </div>
    </form>
    <script src="js/create-events.js"></script>
    <script>
      // capture initial form state to allow cancel -> revert
      (function(){
        var form = document.querySelector('form');
        if (!form) return;
        var initial = {
          title: document.querySelector('[name="event_name"]').value,
          start_date: document.querySelector('[name="start_date"]').value,
          start_time: document.querySelector('[name="start_time"]').value,
          end_date: document.querySelector('[name="end_date"]').value,
          end_time: document.querySelector('[name="end_time"]').value,
          location: document.getElementById('location').value,
          description: document.querySelector('[name="description"]').value,
          visibility: document.getElementById('hiddenVisibilityType') ? document.getElementById('hiddenVisibilityType').value : 'public',
          selected_departments: document.getElementById('hiddenSelectedDepartments') ? document.getElementById('hiddenSelectedDepartments').value : ''
        };

        document.getElementById('btnCancel').addEventListener('click', function(e){
          // revert inputs
          document.querySelector('[name="event_name"]').value = initial.title;
          document.querySelector('[name="start_date"]').value = initial.start_date;
          document.querySelector('[name="start_time"]').value = initial.start_time;
          document.querySelector('[name="end_date"]').value = initial.end_date;
          document.querySelector('[name="end_time"]').value = initial.end_time;
          document.getElementById('location').value = initial.location;
          document.querySelector('[name="description"]').value = initial.description;
          if (document.getElementById('hiddenVisibilityType')) document.getElementById('hiddenVisibilityType').value = initial.visibility;
          if (document.getElementById('hiddenSelectedDepartments')) document.getElementById('hiddenSelectedDepartments').value = initial.selected_departments;
          // restore dept checkboxes
          if (initial.selected_departments) {
            var parts = initial.selected_departments.split(',');
            // restore both inline and inline-old checkboxes
            document.querySelectorAll('.dept-cb, .dept-cb-inline').forEach(function(cb){ cb.checked = parts.indexOf(cb.value) !== -1; });
          } else {
            document.querySelectorAll('.dept-cb, .dept-cb-inline').forEach(function(cb){ cb.checked = false; });
          }
          // close any open panels
          if (document.getElementById('visibilityPanel')) document.getElementById('visibilityPanel').style.display = 'none';
          if (document.getElementById('visibilityPanelInline')) document.getElementById('visibilityPanelInline').style.display = 'none';
        });
      })();
    </script>
    <script>
      (function(){
        // Time dropdown init
        function generateTimeOptions(){
          var times = [], periods=['AM','PM'];
          for(var p=0;p<2;p++){ for(var h=1;h<=12;h++){ var hourStr = h<10? '0'+h: ''+h; times.push(hourStr+':00 '+periods[p]); times.push(hourStr+':30 '+periods[p]); }}
          return times;
        }
        function populate(dropdown, inputEl){ if(!dropdown||!inputEl) return; dropdown.innerHTML=''; var arr=generateTimeOptions(); arr.forEach(function(t){ var d=document.createElement('div'); d.className='time-option'; d.textContent=t; d.addEventListener('click', function(e){ e.stopPropagation(); inputEl.value=t; dropdown.style.display='none'; }); dropdown.appendChild(d); }); }
        function closeAllTimeDropdowns(){ document.querySelectorAll('.custom-time-dropdown').forEach(function(x){ x.style.display='none'; }); }

        var startInput = document.getElementById('start_time'), startDropdown=document.getElementById('start_time_dropdown');
        var endInput = document.getElementById('end_time'), endDropdown=document.getElementById('end_time_dropdown');
        if(startInput && startDropdown){ populate(startDropdown,startInput); startInput.addEventListener('click', function(e){ e.stopPropagation(); closeAllTimeDropdowns(); startDropdown.style.display='block'; }); }
        if(endInput && endDropdown){ populate(endDropdown,endInput); endInput.addEventListener('click', function(e){ e.stopPropagation(); closeAllTimeDropdowns(); endDropdown.style.display='block'; }); }
        document.addEventListener('click', closeAllTimeDropdowns);

        // Visibility inline init
        var visTrigger = document.getElementById('visibilityTriggerInline');
        var visPanel = document.getElementById('visibilityPanelInline');
        var visValue = document.getElementById('visibilityValueInline');
        var filterInline = document.getElementById('filterDeptOptionInline');
        var deptChecklistInline = document.getElementById('deptChecklistInline');
        var hiddenType = document.getElementById('hiddenVisibilityType');
        var hiddenDepts = document.getElementById('hiddenSelectedDepartments');

        function closeAllPanels(){ if(visPanel) visPanel.style.display='none'; if(deptChecklistInline) deptChecklistInline.style.display='none'; }

        if(visTrigger && visPanel){
          visTrigger.addEventListener('click', function(e){ e.stopPropagation(); visPanel.style.display = visPanel.style.display==='block' ? 'none' : 'block'; });
          // option clicks
          visPanel.querySelectorAll('.visibility-option').forEach(function(opt){
            opt.addEventListener('click', function(e){
              e.stopPropagation();
              var val = this.getAttribute('data-value');
              if (val === 'Public') {
                visValue.textContent = '🌐 Public';
                hiddenType.value = 'public';
                hiddenDepts.value = '';
                // clear any checks
                document.querySelectorAll('.dept-cb-inline').forEach(function(cb){ cb.checked = false; });
                if (deptChecklistInline) deptChecklistInline.style.display = 'none';
              } else if (val === 'Private') {
                visValue.textContent = '🔒 Private';
                hiddenType.value = 'private';
                hiddenDepts.value = '';
                document.querySelectorAll('.dept-cb-inline').forEach(function(cb){ cb.checked = false; });
                if (deptChecklistInline) deptChecklistInline.style.display = 'none';
              } else if (val === 'Department Only') {
                visValue.textContent = '🏢 Department Only';
                hiddenType.value = 'department_only';
                hiddenDepts.value = '';
                document.querySelectorAll('.dept-cb-inline').forEach(function(cb){ cb.checked = false; });
                if (deptChecklistInline) deptChecklistInline.style.display = 'none';
              }
              closeAllPanels();
            });
          });
          // filter toggle
          if(filterInline) filterInline.addEventListener('click', function(e){ e.stopPropagation(); this.classList.toggle('open'); if(deptChecklistInline) deptChecklistInline.style.display = deptChecklistInline.style.display==='block' ? 'none' : 'block'; });
          // dept checkbox handling
          Array.from(document.querySelectorAll('.dept-cb-inline')).forEach(function(cb){ cb.addEventListener('change', function(){ var checked = Array.from(document.querySelectorAll('.dept-cb-inline:checked')); if(checked.length===0){ visValue.textContent='🌐 Public'; hiddenType.value='public'; hiddenDepts.value=''; } else { var ids=checked.map(function(c){return c.value}); var codes=checked.map(function(c){return c.getAttribute('data-code')}); visValue.textContent='🎯 Restricted: '+codes.join(', '); hiddenType.value='restricted'; hiddenDepts.value=ids.join(','); } }); });
          document.addEventListener('click', function(){ closeAllPanels(); });
        }
      })();
    </script>
    </div>
    <script src="js/navbar.js"></script>
  </body>
</html>