<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit;
}

if (!isset($_FILES['profile_img']) || $_FILES['profile_img']['error'] !== UPLOAD_ERR_OK) {
    header('Location: Myprofile.php');
    exit;
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
$fileInfo = pathinfo($_FILES['profile_img']['name']);
$extension = strtolower($fileInfo['extension'] ?? '');
if (!in_array($extension, $allowedExtensions, true)) {
    header('Location: Myprofile.php');
    exit;
}

$uploadDir = 'uploads/profile_pictures/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
$destination = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['profile_img']['tmp_name'], $destination)) {
    header('Location: Myprofile.php');
    exit;
}

$profilePath = $destination;
$stmt = $conn->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
$stmt->bind_param('si', $profilePath, $_SESSION['user_id']);
$stmt->execute();

header('Location: Myprofile.php');
exit;
