<?php
include 'config.php';

if (!isset($_GET['lead_id'])) {
  echo "<p class='text-danger'>Invalid Lead ID.</p>";
  exit;
}

$lead_id = (int) $_GET['lead_id'];

$stmt = $pdo->prepare("
  SELECT recording_url, call_start, duration_seconds
  FROM call_logs
  WHERE lead_id = :lead_id AND recording_url IS NOT NULL
  ORDER BY call_start DESC
");
$stmt->execute(['lead_id' => $lead_id]);
$recordings = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$recordings) {
  echo "<p class='text-muted'>No recordings found for this lead.</p>";
  exit;
}

foreach ($recordings as $rec) {
  $file = "user/uploads/recordings/" . basename($rec['recording_url']);
  echo "<div class='mb-3'>";
  echo "<strong>" . date("d-m-Y h:i A", strtotime($rec['call_start'])) . "</strong> (" . (int)$rec['duration_seconds'] . " sec)<br/>";
  echo "<audio controls preload='none' style='width:100%;'><source src='" . htmlspecialchars($file) . "' type='audio/mp4'></audio>";
  echo "</div>";
}
?>
