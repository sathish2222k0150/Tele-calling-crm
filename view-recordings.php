<?php
include 'config.php';
header('Content-Type: application/json');

if (!isset($_POST['lead_id']) || !is_numeric($_POST['lead_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Lead ID']);
    exit;
}

$lead_id = (int) $_POST['lead_id'];

$stmt = $pdo->prepare("
    SELECT recording_url, call_start, duration_seconds
    FROM call_logs
    WHERE lead_id = :lead_id AND recording_url IS NOT NULL
    ORDER BY call_start DESC
");
$stmt->execute(['lead_id' => $lead_id]);
$recordings = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$recordings) {
    echo json_encode(['success' => true, 'recordings' => []]);
    exit;
}

$data = [];
foreach ($recordings as $rec) {
    $file = "user/uploads/recordings/" . basename($rec['recording_url']);
    $data[] = [
        'url' => $file,
        'time' => date("d-m-Y h:i A", strtotime($rec['call_start'])),
        'duration' => (int)$rec['duration_seconds']
    ];
}

echo json_encode(['success' => true, 'recordings' => $data]);
exit;
?>
