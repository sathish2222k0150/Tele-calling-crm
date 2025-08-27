<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
// Filters
$where = [];
$params = [];

if (!empty($_GET['from_date'])) {
  $where[] = "report_date >= :from_date";
  $params[':from_date'] = $_GET['from_date'];
}

if (!empty($_GET['to_date'])) {
  $where[] = "report_date <= :to_date";
  $params[':to_date'] = $_GET['to_date'];
}

if (!empty($_GET['user_id'])) {
  $where[] = "user_id = :user_id";
  $params[':user_id'] = $_GET['user_id'];
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fetch data
$sql = "
  SELECT dr.*, u.name AS user_name
  FROM daily_reports dr
  LEFT JOIN users u ON dr.user_id = u.id
  $whereSql
  ORDER BY dr.report_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=daily_reports_export.csv');
echo "\xEF\xBB\xBF"; // UTF-8 BOM

$output = fopen('php://output', 'w');

// Write column headers
fputcsv($output, [
  'User Name',
  'Report Date',
  'Today Leads',
  'Converted Leads',
  'Closed Leads',
  'Follow-Up Leads',
  'Total Calls',
  'Created At'
]);

// Write data rows
foreach ($records as $row) {
  fputcsv($output, [
    $row['user_name'] ?? 'Unknown',
    $row['report_date'],
    $row['today_leads'],
    $row['today_converted'],
    $row['today_closed'],
    $row['follow_up_leads'],
    $row['today_calls'],
    date('d-m-Y h:i A', strtotime($row['created_at']))
  ]);
}

fclose($output);
exit;
