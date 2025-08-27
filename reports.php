<?php
include 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
// Filters
$nameFilter = $_GET['name'] ?? '';
$fromDate = $_GET['from_date'] ?? '';
$toDate = $_GET['to_date'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where = [];
$params = [];

if (!empty($nameFilter)) {
  $where[] = "u.name LIKE :name";
  $params[':name'] = "%$nameFilter%";
}
if (!empty($fromDate)) {
  $where[] = "dr.report_date >= :from_date";
  $params[':from_date'] = $fromDate;
}
if (!empty($toDate)) {
  $where[] = "dr.report_date <= :to_date";
  $params[':to_date'] = $toDate;
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Total count for pagination
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM daily_reports dr JOIN users u ON dr.user_id = u.id $whereSql");
$totalStmt->execute($params);
$totalRecords = $totalStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Fetch paginated daily reports
$sql = "
  SELECT dr.*, u.name 
  FROM daily_reports dr
  JOIN users u ON dr.user_id = u.id
  $whereSql
  ORDER BY dr.report_date DESC 
  LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
  $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$dailyReports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sharadha Skill Academy CRM - Telecaller Upload Reports</title>
  <link rel="stylesheet" href="./css/adminlte.css" />
  <link rel="preload" href="./css/adminlte.css" as="style" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
    integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" media="print"
    onload="this.media='all'" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
    crossorigin="anonymous" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
    crossorigin="anonymous" />
  <link rel="stylesheet" href="./css/adminlte.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
    integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0=" crossorigin="anonymous" />
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6"><h3>Telecaller Upload Reports</h3></div>
        </div>
      </div>
    </div>

    <div class="app-content">
      <div class="container-fluid">

        <!-- Filter Form -->
        <form method="GET" class="row mb-3 g-3">
          <div class="col-md-3">
            <input type="text" name="name" class="form-control" placeholder="Filter by Name" value="<?= htmlspecialchars($nameFilter) ?>">
          </div>
          <div class="col-md-3">
            <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
          </div>
          <div class="col-md-3">
            <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
          </div>
          <div class="col-md-4">
            <button class="btn btn-primary" type="submit">Apply Filters</button>
            <a href="dashboard.php" class="btn btn-secondary">Reset</a>
            <a href="export_uploads.php?<?= http_build_query($_GET) ?>" class="btn btn-success">Export Upload CSV</a>
          </div>
        </form>

        <!-- DAILY REPORTS SECTION -->
        <div class="card mb-4">
          <div class="card-header bg-info text-white"><strong>Daily Telecaller Reports</strong></div>
          <div class="card-body table-responsive">
            <table class="table table-bordered table-hover text-center">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Date</th>
                  <th>Total Leads</th>
                  <th>Converted</th>
                  <th>Closed</th>
                  <th>Follow-ups</th>
                  <th>Calls Made</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($dailyReports) === 0): ?>
                  <tr><td colspan="8" class="text-center">No daily reports found.</td></tr>
                <?php else: ?>
                  <?php foreach ($dailyReports as $i => $report): ?>
                    <tr>
                      <td><?= $offset + $i + 1 ?></td>
                      <td><?= htmlspecialchars($report['name']) ?></td>
                      <td><?= date('d-m-Y', strtotime($report['report_date'])) ?></td>
                      <td><?= $report['today_leads'] ?></td>
                      <td><?= $report['today_converted'] ?></td>
                      <td><?= $report['today_closed'] ?></td>
                      <td><?= $report['follow_up_leads'] ?></td>
                      <td><?= $report['today_calls'] ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>

            <!-- Pagination -->
            <nav>
              <ul class="pagination justify-content-center mt-3">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
              </ul>
            </nav>
          </div>
        </div>

        <!-- UPLOAD REQUESTS TABLE (existing section) -->

      </div>
    </div>
  </main>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="./js/adminlte.js"></script>
</body>
</html>
