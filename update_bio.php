<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit;
}

$bio = isset($_POST['bio']) ? trim($_POST['bio']) : null;
if ($bio === null) {
    header('Location: Myprofile.php');
    exit;
}

$stmt = $conn->prepare('UPDATE users SET bio = ? WHERE id = ?');
$stmt->bind_param('si', $bio, $_SESSION['user_id']);
$stmt->execute();

header('Location: Myprofile.php');
exit;
