<?php
require 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: telecallers.php');
    exit;
}

$id = $_GET['id'];

// Check if telecaller exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'tele_caller'");
$stmt->execute([$id]);
$telecaller = $stmt->fetch(PDO::FETCH_ASSOC);

if ($telecaller) {
    // Delete related leads (optional – depending on foreign keys or cascade settings)
    // $pdo->prepare("DELETE FROM leads WHERE assigned_to = ?")->execute([$id]);

    // Delete user
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
}

header('Location: telecallers.php');
exit;
