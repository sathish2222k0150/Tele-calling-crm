<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
// Set headers for Excel export
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=call_logs_" . date('Ymd_His') . ".xls");

// Output UTF-8 BOM for Excel compatibility
echo "\xEF\xBB\xBF";

// Prepare and execute query
$stmt = $pdo->prepare("
  SELECT 
    cl.call_start,
    cl.duration_seconds,
    l.name AS lead_name,
    u.name AS telecaller_name,
    l.status
  FROM call_logs cl
  JOIN leads l ON cl.lead_id = l.id
  JOIN users u ON cl.user_id = u.id
  ORDER BY cl.call_start DESC
");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output header row
echo "Date/Time\tLead Name\tTelecaller\tDuration (sec)\tStatus\n";

// Output data rows
foreach ($results as $row) {
  echo date("d-m-Y h:i A", strtotime($row['call_start'])) . "\t";
  echo $row['lead_name'] . "\t";
  echo $row['telecaller_name'] . "\t";
  echo $row['duration_seconds'] . "\t";
  echo $row['status'] . "\n";
}
