<?php
include 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$users = $pdo->query("SELECT id, name, phone_number, role FROM users");
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Sharadha Skill Academy CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="./css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" />
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <div class="app-wrapper">
        <?php include 'header.php'; ?>
        <?php include 'sidebar.php'; ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6"><h3 class="mb-0">Dashboard</h3></div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active">Dashboard</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-header d-flex flex-row justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Users Table</h5>
                            <div class="ms-auto">
                                <a href="user-register.php" class="btn btn-primary btn-sm">
                                    <i class="bi bi-person-plus"></i> Add User
                                </a>
                            </div>
                        </div>

                        <div class="card-body table-responsive p-0">
                            <table class="table table-striped table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Total Leads</th>
                                        <th>Converted Leads</th>
                                        <th>Total Calls</th>
                                        <th>Conversion Rate</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = $users->fetch(PDO::FETCH_ASSOC)):
                                        $id = $user['id'];
                                        $role = $user['role'];
                                        $name = htmlspecialchars($user['name']);
                                        $phone = htmlspecialchars($user['phone_number']);
                                        $isTelecallerOrStaff = in_array($role, ['telecaller', 'staff']);

                                        // Defaults
                                        $totalLeads = $convertedLeads = $totalCalls = 0;
                                        $conversionRate = '0%';
                                        $status = 'Idle';
                                        $weekCalls = $weekLeads = $monthCalls = $monthLeads = 0;
                                        $weekRate = $monthRate = '0%';

                                        if ($isTelecallerOrStaff) {
                                            $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM leads WHERE assigned_to = ?");
                                            $stmt->execute([$id]);
                                            $totalLeads = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                                            $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM leads WHERE assigned_to = ? AND status = 'converted'");
                                            $stmt->execute([$id]);
                                            $convertedLeads = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                                            $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM call_logs WHERE user_id = ?");
                                            $stmt->execute([$id]);
                                            $totalCalls = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                                            $conversionRate = $totalLeads ? round(($convertedLeads / $totalLeads) * 100, 2) . '%' : '0%';
                                            $status = $totalLeads > 0 ? 'Active' : 'Idle';

                                            $weekStart = date('Y-m-d', strtotime('monday this week'));
                                            $monthStart = date('Y-m-01');

                                            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM leads WHERE assigned_to = ? AND DATE(updated_at) >= ?");
                                            $stmt->execute([$id, $weekStart]);
                                            $weekCalls = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

                                            $stmt->execute([$id, $monthStart]);
                                            $monthCalls = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

                                            $stmt = $pdo->prepare("SELECT COUNT(*) AS c FROM leads WHERE assigned_to = ? AND status = 'converted' AND DATE(updated_at) >= ?");
                                            $stmt->execute([$id, $weekStart]);
                                            $weekLeads = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

                                            $stmt->execute([$id, $monthStart]);
                                            $monthLeads = $stmt->fetch(PDO::FETCH_ASSOC)['c'];

                                            $weekRate = $weekCalls ? round(($weekLeads / $weekCalls) * 100, 2) . '%' : '0%';
                                            $monthRate = $monthCalls ? round(($monthLeads / $monthCalls) * 100, 2) . '%' : '0%';
                                        }
                                    ?>
                                    <tr>
                                        <td><?= $name ?></td>
                                        <td><?= $phone ?></td>
                                        <td><?= htmlspecialchars($role) ?></td>
                                        <td><?= $totalLeads ?></td>
                                        <td><?= $convertedLeads ?></td>
                                        <td><?= $totalCalls ?></td>
                                        <td><?= $conversionRate ?></td>
                                        <td><span class="badge bg-<?= $status == 'Active' ? 'success' : 'secondary' ?>"><?= $status ?></span></td>
                                        <td>
                                            <?php if ($isTelecallerOrStaff): ?>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?= $id ?>">View</button>
                                            <?php endif; ?>
                                            <a href="edit_telecaller.php?id=<?= $id ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="delete_telecaller.php?id=<?= $id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                        </td>
                                    </tr>

                                    <?php if ($isTelecallerOrStaff): ?>
                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewModal<?= $id ?>" tabindex="-1" aria-labelledby="viewModalLabel<?= $id ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="viewModalLabel<?= $id ?>">Performance: <?= $name ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>This Week</h6>
                                                                <ul>
                                                                    <li><strong>Total Leads:</strong> <?= $weekCalls ?></li>
                                                                    <li><strong>Converted Leads:</strong> <?= $weekLeads ?></li>
                                                                    <li><strong>Conversion Rate:</strong> <?= $weekRate ?></li>
                                                                </ul>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>This Month</h6>
                                                                <ul>
                                                                    <li><strong>Total Leads:</strong> <?= $monthCalls ?></li>
                                                                    <li><strong>Converted Leads:</strong> <?= $monthLeads ?></li>
                                                                    <li><strong>Conversion Rate:</strong> <?= $monthRate ?></li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                        <hr>
                                                        <p><strong>Total Calls (All Time):</strong> <?= $totalCalls ?></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"></script>
    <script src="./js/adminlte.js"></script>
</body>
</html>
