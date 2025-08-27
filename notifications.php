<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$notifications = [];

// ✅ 1. Report + follow-up notification from `notifications` table
$stmt = $pdo->prepare("
    SELECT n.type, n.reference_id, n.created_at,
           CASE
               WHEN n.type = 'followup' THEN l.name
               WHEN n.type = 'report' THEN u.name
           END AS name,
           CASE
               WHEN n.type = 'followup' THEN l.phone_number
               WHEN n.type = 'report' THEN dr.report_date
           END AS info
    FROM notifications n
    LEFT JOIN leads l ON n.reference_id = l.id AND n.type = 'followup'
    LEFT JOIN daily_reports dr ON n.reference_id = dr.id AND n.type = 'report'
    LEFT JOIN users u ON dr.user_id = u.id AND n.type = 'report'
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ 2. Today + Tomorrow follow-ups
$stmt2 = $pdo->prepare("
    SELECT 'future_followup' AS type, id AS reference_id, NULL AS created_at,
           name, phone_number AS info, follow_up_date
    FROM leads
    WHERE follow_up_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
      AND status = 'follow_up'
    ORDER BY follow_up_date ASC
");
$stmt2->execute();
$futureFollows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// ✅ 3. Merge both
$notifications = array_merge($notifications, $futureFollows);

// ✅ 4. Sort by date (latest first)
usort($notifications, function($a, $b) {
    $dateA = $a['created_at'] ?? $a['follow_up_date'];
    $dateB = $b['created_at'] ?? $b['follow_up_date'];
    return strtotime($dateB) <=> strtotime($dateA);
});
?>



<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>Sharadha Skill Academy CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="./css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" media="print"
        onload="this.media='all'" />
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

                    <?php if (count($notifications) === 0): ?>
                        <div class="alert alert-info">No notifications found.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $note): ?>
                                <div class="alert alert-light d-flex align-items-center gap-2 shadow-sm border mb-2">
                                    <?php if ($note['type'] === 'report'): ?>
                                        <i class="bi bi-file-earmark-text text-primary"></i>
                                        <div><strong>Report Uploaded:</strong> <?= htmlspecialchars($note['name']) ?></div>
                                    <?php elseif ($note['type'] === 'followup'): ?>
                                        <i class="bi bi-telephone text-warning"></i>
                                        <div><strong>Follow-up Reminder:</strong> <?= htmlspecialchars($note['name']) ?>
                                            (<?= $note['info'] ?>)</div>
                                    <?php elseif ($note['type'] === 'future_followup'): ?>
                                        <i class="bi bi-calendar-check text-success"></i>
                                        <div><strong>Upcoming Follow-up:</strong> <?= htmlspecialchars($note['name']) ?>
                                            (<?= $note['info'] ?>)</div>
                                    <?php endif; ?>
                                    <span class="ms-auto small text-muted">
                                        <?= isset($note['created_at']) ? date('d M Y', strtotime($note['created_at'])) : date('d M Y', strtotime($note['follow_up_date'])) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>

                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </main>
    </div>

    <script
        src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"></script>
    <script src="./js/adminlte.js"></script>
</body>

</html>