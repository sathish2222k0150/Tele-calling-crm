<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
// Optional filters
$params = [];
$conditions = [];

if (!empty($_GET['status'])) {
    $conditions[] = 'status = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['user'])) {
    $conditions[] = 'assigned_to = ?';
    $params[] = $_GET['user'];
}

$whereSql = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Fetch leads
$sql = "
    SELECT l.name, l.status, l.updated_at, u.name AS assigned_user
    FROM leads l
    LEFT JOIN users u ON l.assigned_to = u.id
    $whereSql
    ORDER BY 
        CASE 
            WHEN l.updated_at IS NULL OR l.updated_at = '' THEN 0 
            ELSE 1 
        END DESC,
        l.updated_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="leads_export.csv"');
echo "\xEF\xBB\xBF"; // UTF-8 BOM
$output = fopen('php://output', 'w');
fputcsv($output, ['Name', 'Status', 'Updated At', 'Assigned To']);

foreach ($leads as $lead) {
    fputcsv($output, [
        $lead['name'],
        $lead['status'],
        $lead['updated_at'] ? date('Y-m-d H:i', strtotime($lead['updated_at'])) : 'Not Updated',
        $lead['assigned_user'] ?? 'Unassigned'
    ]);
}
fclose($output);
exit;
