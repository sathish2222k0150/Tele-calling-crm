<?php
include '../config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: ../index.php');
    exit();
}
$today = date('Y-m-d');
$userId = $_SESSION['user_id'] ?? null;
$userName = '';
$reportData = [
  'today_leads' => 0,
  'today_converted' => 0,
  'today_closed' => 0,
  'follow_up_leads' => 0,
  'today_calls' => 0,
];
$warningLeads = [];
$successMessage = '';
$errorMessage = '';

if ($userId) {
    // Get user's name
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $userName = $user['name'];
    }

    // Fetch report data
    $sql = "
SELECT 
  (SELECT COUNT(*) FROM leads 
   JOIN lead_assignments ON leads.id = lead_assignments.lead_id
   WHERE lead_assignments.user_id = ? AND DATE(leads.created_at) = ?) AS today_leads,

  (SELECT COUNT(*) FROM leads 
   JOIN lead_assignments ON leads.id = lead_assignments.lead_id
   WHERE lead_assignments.user_id = ? AND leads.status = 'converted' AND DATE(leads.updated_at) = ?) AS today_converted,

  (SELECT COUNT(*) FROM leads 
   JOIN lead_assignments ON leads.id = lead_assignments.lead_id
   WHERE lead_assignments.user_id = ? AND leads.status = 'closed' AND DATE(leads.updated_at) = ?) AS today_closed,

  (SELECT COUNT(*) FROM leads 
   JOIN lead_assignments ON leads.id = lead_assignments.lead_id
   WHERE lead_assignments.user_id = ? AND leads.status = 'follow_up' AND leads.follow_up_date >= ?) AS follow_up_leads,

  (SELECT COUNT(*) FROM call_logs 
   WHERE call_logs.user_id = ? AND DATE(call_logs.created_at) = ?) AS today_calls
";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $userId, $today,
        $userId, $today,
        $userId, $today,
        $userId, $today,
        $userId, $today
    ]);
    $reportData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
        // Get today's assigned leads
        $stmt = $pdo->prepare("
          SELECT leads.id 
          FROM leads
          JOIN lead_assignments ON leads.id = lead_assignments.lead_id
          WHERE lead_assignments.user_id = ? AND DATE(leads.created_at) = ?
        ");
        $stmt->execute([$userId, $today]);
        $leads = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($leads as $leadId) {
    $check = $pdo->prepare("
        SELECT COUNT(*) FROM call_logs 
        WHERE lead_id = ? AND user_id = ? AND recording_url IS NOT NULL AND recording_url != ''
    ");
    $check->execute([$leadId, $userId]);
    if ($check->fetchColumn() == 0) {
        $warningLeads[] = $leadId;
    }
}


        if (count($warningLeads) === 0) {
            // Save report
            try {
                $stmt = $pdo->prepare("
                  INSERT INTO daily_reports (user_id, report_date, today_leads, today_converted, today_closed, follow_up_leads, today_calls)
                  VALUES (?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                    today_leads = VALUES(today_leads),
                    today_converted = VALUES(today_converted),
                    today_closed = VALUES(today_closed),
                    follow_up_leads = VALUES(follow_up_leads),
                    today_calls = VALUES(today_calls)
                ");
                $stmt->execute([
                    $userId, $today,
                    $reportData['today_leads'],
                    $reportData['today_converted'],
                    $reportData['today_closed'],
                    $reportData['follow_up_leads'],
                    $reportData['today_calls']
                ]);
                $successMessage = "Report generated and saved successfully.";
            } catch (PDOException $e) {
                $errorMessage = "Error saving report: " . $e->getMessage();
            }
        } else {
            $errorMessage = "Cannot generate report. Missing call recordings for Lead IDs: " . implode(', ', $warningLeads);
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Sharadha Skill Academy CRM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../css/adminlte.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" />
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
  <div class="app-wrapper">
    <?php include './header.php'; ?>
    <?php include 'sidebar.php'; ?>
    <main class="app-main">
      <div class="app-content-header">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-6">
              <h3 class="mb-0">Dashboard</h3>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Dashboard</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <div class="app-content">
        <div class="container-fluid">
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Today's Report (<?= date('d M Y') ?>)</h5>
              <form method="post" class="mb-0">
                <button type="submit" name="generate_report" class="btn btn-warning btn-sm">Generate & Save Report</button>
              </form>
            </div>
            <div class="card-body p-4">
              <?php if ($successMessage): ?>
                <div class="alert alert-success"><?= $successMessage ?></div>
              <?php elseif ($errorMessage): ?>
                <div class="alert alert-danger"><?= $errorMessage ?></div>
              <?php endif; ?>
              <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Total Leads Today</th>
                      <th>Converted Today</th>
                      <th>Closed Today</th>
                      <th>Follow-ups (Today & Future)</th>
                      <th>Calls Made Today</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td><?= $reportData['today_leads'] ?></td>
                      <td><?= $reportData['today_converted'] ?></td>
                      <td><?= $reportData['today_closed'] ?></td>
                      <td><?= $reportData['follow_up_leads'] ?></td>
                      <td><?= $reportData['today_calls'] ?></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"></script>
          <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
          <script src="../js/adminlte.js"></script>
          <script>
            document.addEventListener('DOMContentLoaded', function () {
              const sidebarWrapper = document.querySelector('.sidebar-wrapper');
              if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                  scrollbars: {
                    theme: 'os-theme-light',
                    autoHide: 'leave',
                    clickScroll: true,
                  },
                });
              }
            });
          </script>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
