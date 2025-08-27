<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}

if (!isset($_SESSION['user_id']) || !isset($_POST['reference_id'], $_POST['type'])) {
    http_response_code(400);
    exit('Invalid request');
}

$userId = $_SESSION['user_id'];
$referenceId = $_POST['reference_id'];
$type = $_POST['type'];

$stmt = $pdo->prepare("
    INSERT INTO notifications (user_id, type, reference_id, is_read)
    VALUES (?, ?, ?, 1)
");
$stmt->execute([$userId, $type, $referenceId]);

echo 'done';
?>
