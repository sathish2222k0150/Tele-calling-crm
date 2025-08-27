<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
// Get filter values
$from = $_GET['from_date'] ?? null;
$to = $_GET['to_date'] ?? null;
$telecaller_id = $_GET['telecaller_id'] ?? '';
$status = $_GET['status'] ?? '';

// Validate required fields
if (!$from || !$to) {
  die("From and To dates are required.");
}

// Get telecaller name from ID (used for leads.assigned_to)
$telecaller_name = '';
if (!empty($telecaller_id)) {
  $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
  $stmt->execute([$telecaller_id]);
  $telecaller_name = $stmt->fetchColumn();
}

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=lead_report_" . date("Y-m-d_H-i-s") . ".xls");

// Begin Excel table
echo "<table border='1'>";
echo "<tr>
  <th>ID</th>
  <th>Name</th>
  <th>Phone</th>
  <th>Email</th>
  <th>City</th>
  <th>Status</th>
  <th>Assigned To</th>
  <th>Created Date</th>
</tr>";

// Build SQL
$sql = "
  SELECT 
    leads.id,
    leads.name,
    leads.phone_number,
    leads.email,
    leads.city,
    leads.status,
    users.name AS telecaller,
    leads.created_date
  FROM leads
  LEFT JOIN lead_assignments ON leads.id = lead_assignments.lead_id
  LEFT JOIN users ON lead_assignments.user_id = users.id
  WHERE leads.created_date BETWEEN :from AND :to
";

$params = [
  ':from' => $from,
  ':to' => $to
];

// If telecaller is selected, filter by assigned user ID + assigned_to name
if (!empty($telecaller_id) && !empty($telecaller_name)) {
  $sql .= " AND lead_assignments.user_id = :telecaller_id AND leads.assigned_to = :assigned_to_name";
  $params[':telecaller_id'] = $telecaller_id;
  $params[':assigned_to_name'] = $telecaller_name;
}

// Filter by status if provided
if (!empty($status)) {
  $sql .= " AND leads.status = :status";
  $params[':status'] = $status;
}

// Order by created date
$sql .= " ORDER BY leads.created_date DESC";

// Execute and fetch data
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Output data rows
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  echo "<tr>
    <td>{$row['id']}</td>
    <td>" . htmlspecialchars($row['name']) . "</td>
    <td>{$row['phone_number']}</td>
    <td>{$row['email']}</td>
    <td>{$row['city']}</td>
    <td>{$row['status']}</td>
    <td>{$row['telecaller']}</td>
    <td>{$row['created_date']}</td>
  </tr>";
}

echo "</table>";
exit;
