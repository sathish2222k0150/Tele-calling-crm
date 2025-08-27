<?php
require '../config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: ../index.php');
    exit();
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Unauthorized";
    exit;
}

$leadId = $_POST['lead_id'] ?? null;
$type = $_POST['type'] ?? null;

if (!$leadId || !in_array($type, ['call', 'whatsapp'])) {
    http_response_code(400);
    echo "Invalid request";
    exit;
}

// Validate assignment
$stmt = $pdo->prepare("SELECT assigned_to FROM leads WHERE id = ?");
$stmt->execute([$leadId]);
$lead = $stmt->fetch();

if (!$lead || $lead['assigned_to'] != $_SESSION['user_id']) {
    http_response_code(403);
    echo "Access Denied";
    exit;
}

$column = $type === 'call' ? 'call_triggered_at' : 'whatsapp_triggered_at';
$stmt = $pdo->prepare("UPDATE leads SET $column = NOW(), updated_at = NOW() WHERE id = ?");
$success = $stmt->execute([$leadId]);

echo $success ? "Logged" : "Failed";
