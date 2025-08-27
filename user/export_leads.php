<?php
require '../config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: ../index.php');
    exit();
}
$role = $_SESSION['role']; // 'admin' or 'telecaller'
$userId = $_SESSION['user_id']; // assuming this is set

// Prepare the SQL query
$sql = "
    SELECT 
        l.id, l.name, l.status, l.updated_at, u.name AS assigned_user, cl.recording_url
    FROM leads l
    LEFT JOIN users u ON l.assigned_to = u.id
    LEFT JOIN (
        SELECT lead_id, recording_url
        FROM call_logs
        WHERE recording_url IS NOT NULL
        ORDER BY call_start DESC
    ) cl ON cl.lead_id = l.id
    " . ($role === 'admin' ? "" : "WHERE l.assigned_to = :userId") . "
    GROUP BY l.id
    ORDER BY l.updated_at DESC
";

$stmt = $pdo->prepare($sql);
if ($role !== 'admin') {
    $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
}
$stmt->execute();
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Send CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=leads_export.csv');

// Output BOM for UTF-8
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Name', 'Status', 'Updated At', 'Assigned To', 'Recording URL']);

foreach ($leads as $lead) {
    fputcsv($output, [
        $lead['id'],
        $lead['name'],
        $lead['status'],
        date('Y-m-d H:i', strtotime($lead['updated_at'])),
        $lead['assigned_user'] ?? 'Unassigned',
        $lead['recording_url'] ?? 'No recording',
    ]);
}

fclose($output);
exit;
?>
