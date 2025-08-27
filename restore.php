<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
if (isset($_GET['ids'])) {
    $ids = explode(',', $_GET['ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("UPDATE leads SET moved = 0 WHERE id IN ($placeholders)");
    $stmt->execute($ids);
}

header("Location: leads.php?show=active");
exit;
