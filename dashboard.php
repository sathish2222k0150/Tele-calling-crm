<?php
include 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userName = $user['name'];
    }
}

$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
           SUM(status='converted') as converted,
           COUNT(*) as total
    FROM leads
    GROUP BY month
    ORDER BY month DESC
    LIMIT 12
");
$conversionData = array_reverse($stmt->fetchAll());

$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM leads
    GROUP BY month
    ORDER BY month DESC
    LIMIT 12
");
$newLeadsData = array_reverse($stmt->fetchAll());

// Build arrays for Jan–Dec
$allMonths = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
$conversionRatesMap = array_fill_keys($allMonths, 0);
$newLeadsCountsMap = array_fill_keys($allMonths, 0);

// Fill real data
foreach ($conversionData as $row) {
  $monthNum = (int) date('n', strtotime($row['month'] . '-01'));
  $monthName = $allMonths[$monthNum - 1];
  $rate = $row['total'] > 0 ? round(($row['converted'] / $row['total']) * 100, 2) : 0;
  $conversionRatesMap[$monthName] = $rate;
}
foreach ($newLeadsData as $row) {
  $monthNum = (int) date('n', strtotime($row['month'] . '-01'));
  $monthName = $allMonths[$monthNum - 1];
  $newLeadsCountsMap[$monthName] = (int) $row['count'];
}

// Output JSON
$chartData = [
  'months' => array_values(array_keys($conversionRatesMap)),
  'conversionRates' => array_values($conversionRatesMap),
  'newLeadsCounts' => array_values($newLeadsCountsMap),
];

// Echo as JS variables (or use ajax/fetch)
echo "<script>
    const chartMonths = " . json_encode($chartData['months']) . ";
    const chartConversionRates = " . json_encode($chartData['conversionRates']) . ";
    const chartNewLeadsCounts = " . json_encode($chartData['newLeadsCounts']) . ";
</script>";
?>

<!doctype html>
<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Sharadha Skill Academy CRM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
  <meta name="color-scheme" content="light dark" />
  <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
  <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
  <meta name="title" content="AdminLTE | Dashboard v2" />
  <meta name="author" content="ColorlibHQ" />
  <meta name="description"
    content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS. Fully accessible with WCAG 2.1 AA compliance." />
  <meta name="keywords"
    content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard, accessible admin panel, WCAG compliant" />
  <meta name="supported-color-schemes" content="light dark" />
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
            <div class="col-sm-6">
              <h3 class="mb-0">Dashboard</h3>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
              </ol>
            </div>
          </div>
        </div>
      </div>
      <div class="app-content">
        <div class="container-fluid">
          <?php include 'cards.php'; ?>
          <?php include 'graph.php'; ?>
          <?php include 'footer.php'; ?>
          <script
            src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
            crossorigin="anonymous"></script>
          <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
            crossorigin="anonymous"></script>
          <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
            crossorigin="anonymous"></script>
          <script src="./js/adminlte.js"></script>
          <script>
            const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
            const Default = {
              scrollbarTheme: 'os-theme-light',
              scrollbarAutoHide: 'leave',
              scrollbarClickScroll: true,
            };
            document.addEventListener('DOMContentLoaded', function () {
              const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
              if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined) {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                  scrollbars: {
                    theme: Default.scrollbarTheme,
                    autoHide: Default.scrollbarAutoHide,
                    clickScroll: Default.scrollbarClickScroll,
                  },
                });
              }
            });
          </script>
          <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
            integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8=" crossorigin="anonymous"></script>
          <script>
            document.addEventListener('DOMContentLoaded', function () {
              const sales_chart_options = {
                series: [
                  {
                    name: 'Conversion Rate %',
                    data: chartConversionRates,
                  },
                  {
                    name: 'New Leads',
                    data: chartNewLeadsCounts,
                  },
                ],
                chart: {
                  height: 180,
                  type: 'area',
                  toolbar: { show: false },
                },
                colors: ['#0d6efd', '#20c997'],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth' },
                xaxis: {
                  categories: chartMonths,
                },
                tooltip: {
                  y: [
                    { formatter: (val) => val + "%" },
                    { formatter: (val) => val },
                  ]
                }
              };

              const sales_chart = new ApexCharts(document.querySelector('#sales-chart'), sales_chart_options);
              sales_chart.render();
            });
          </script>

        </div>
      </div>
    </main>
  </div>
</body>

</html>