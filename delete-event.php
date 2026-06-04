<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['event_id'])) {
    header('Location: manage.php');
    exit;
}

$event_id = intval($_GET['event_id']);
$uid = $_SESSION['user_id'];

// Verify this event belongs to the user's organization
$q = "SELECT e.id FROM events e 
      JOIN organizations o ON e.organization_id = o.id
      WHERE e.id = ? AND (
          o.main_admin_id = ? 
          OR EXISTS (
              SELECT 1 FROM organization_admins oa 
              WHERE oa.organization_id = o.id AND oa.user_id = ?
          )
      ) LIMIT 1";
$s = $conn->prepare($q);
$s->bind_param('iii', $event_id, $uid, $uid);
$s->execute();
$r = $s->get_result();

if ($r->num_rows === 0) {
    header('Location: manage.php');
    exit;
}

// Delete related registrations first (foreign key constraint)
$delReg = $conn->prepare('DELETE FROM registrations WHERE event_id = ?');
$delReg->bind_param('i', $event_id);
$delReg->execute();

// Now delete the event
$delEvt = $conn->prepare('DELETE FROM events WHERE id = ?');
$delEvt->bind_param('i', $event_id);
$delEvt->execute();

header('Location: manage.php');
exit;
?>
