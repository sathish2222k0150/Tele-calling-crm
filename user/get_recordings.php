<?php
require '../config.php';

header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate lead ID
if (!isset($_POST['lead_id']) || !is_numeric($_POST['lead_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
    exit;
}

$leadId = (int) $_POST['lead_id'];

try {
    // Fetch recordings with both timestamps
    $stmt = $pdo->prepare("
        SELECT recording_url, call_start, created_at 
        FROM call_logs 
        WHERE lead_id = ? AND recording_url IS NOT NULL 
        ORDER BY call_start DESC
    ");
    $stmt->execute([$leadId]);
    $recordings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build proper public URLs
    $data = array_map(function($row) {
        $filename = basename($row['recording_url']);

        // Auto-detect base URL for current environment
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $baseUrl .= "://".$_SERVER['HTTP_HOST'];

        // Match your actual folder structure
        $publicUrl = $baseUrl . "/crm/crm/user/uploads/recordings/" . $filename;

        return [
            'url' => $publicUrl,
            'call_time' => date('Y-m-d H:i', strtotime($row['call_start'])),
            'created_time' => date('Y-m-d H:i', strtotime($row['created_at']))
        ];
    }, $recordings);

    echo json_encode(['success' => true, 'recordings' => $data]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'DB error', 
        'error' => $e->getMessage()
    ]);
}