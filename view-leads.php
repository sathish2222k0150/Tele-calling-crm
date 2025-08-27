<?php
require 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}

// Pagination setup
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search term handling
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParam = '%' . $search . '%';

// Count total leads with optional search
if ($search) {
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE name LIKE :search1 OR phone_number LIKE :search2");
    $totalStmt->bindValue(':search1', $searchParam, PDO::PARAM_STR);
    $totalStmt->bindValue(':search2', $searchParam, PDO::PARAM_STR);
    $totalStmt->execute();
} else {
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM leads");
}

$totalLeads = $totalStmt->fetchColumn();
$totalPages = ceil($totalLeads / $limit);

// Fetch leads with telecaller name, search conditionally applied
if ($search) {
    $stmt = $pdo->prepare("
        SELECT leads.*, users.name AS telecaller_name
        FROM leads
        LEFT JOIN users ON leads.assigned_to = users.id
        WHERE leads.name LIKE :search1 OR leads.phone_number LIKE :search2
        ORDER BY leads.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':search1', $searchParam, PDO::PARAM_STR);
    $stmt->bindValue(':search2', $searchParam, PDO::PARAM_STR);
} else {
    $stmt = $pdo->prepare("
        SELECT leads.*, users.name AS telecaller_name
        FROM leads
        LEFT JOIN users ON leads.assigned_to = users.id
        ORDER BY leads.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>All Leads</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="./css/adminlte.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <style>
    .scroll-wrapper {
      max-height: 550px;
      overflow-y: auto;
    }
    .feedback-cell, .education-cell {
      max-width: 200px;
      overflow-x: auto;
      white-space: nowrap;
    }
    table td {
      vertical-align: top;
    }
  </style>
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <h3 class="mb-0">All Leads</h3>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
          <li class="breadcrumb-item active">Leads</li>
        </ol>
      </div>
    </div>

    <div class="app-content">
      <div class="container-fluid">
        <div class="card">
          <div class="card-body">

            <!-- Search Form -->
            <form method="GET" class="row mb-3">
              <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by name or phone number" value="<?= htmlspecialchars($search) ?>">
              </div>
              <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
                <?php if ($search): ?>
                  <a href="view-leads.php" class="btn btn-secondary"><i class="bi bi-x-circle"></i> Clear</a>
                <?php endif; ?>
              </div>
            </form>

            <div class="scroll-wrapper">
              <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                  <tr>
                    <th>Edit</th>
                    <th>ID</th>
                    <th>Assign To</th>
                    <th>Created Date</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Education</th>
                    <th>Age</th>
                    <th>Phone Verified</th>
                    <th>Phone Number</th>
                    <th>WhatsApp</th>
                    <th>Email</th>
                    <th>City</th>
                    <th>Platform</th>
                    <th>Status</th>
                    <th>Feedback</th>
                    <th>Follow-up Date</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Call Triggered At</th>
                    <th>WhatsApp Triggered At</th>
                  </tr>
                </thead>
                <tbody>
                <?php if ($leads): ?>
                  <?php foreach ($leads as $lead): ?>
                    <tr>
                      <td><a href="./edit.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-warning">Edit</a></td>
                      <td><?= htmlspecialchars($lead['id']) ?></td>
                      <td><?= htmlspecialchars($lead['telecaller_name'] ?? 'Unassigned') ?></td>
                      <td><?= htmlspecialchars($lead['created_date']) ?></td>
                      <td><?= htmlspecialchars($lead['name']) ?></td>
                      <td><?= htmlspecialchars($lead['course']) ?></td>
                      <td class="education-cell"><?= htmlspecialchars($lead['education']) ?></td>
                      <td><?= htmlspecialchars($lead['age']) ?></td>
                      <td><?= htmlspecialchars($lead['phone_verified']) ?></td>
                      <td><?= htmlspecialchars($lead['phone_number']) ?></td>
                      <td><?= htmlspecialchars($lead['whatsapp']) ?></td>
                      <td><?= htmlspecialchars($lead['email']) ?></td>
                      <td><?= htmlspecialchars($lead['city']) ?></td>
                      <td><?= htmlspecialchars($lead['platform']) ?></td>
                      <td><?= htmlspecialchars($lead['status']) ?></td>
                      <td class="feedback-cell"><?= htmlspecialchars($lead['feedback']) ?></td>
                      <td><?= htmlspecialchars($lead['follow_up_date']) ?></td>
                      <td><?= htmlspecialchars($lead['created_at']) ?></td>
                      <td><?= htmlspecialchars($lead['updated_at']) ?></td>
                      <td><?= htmlspecialchars($lead['call_triggered_at']) ?></td>
                      <td><?= htmlspecialchars($lead['whatsapp_triggered_at']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="21" class="text-center">No leads found.</td></tr>
                <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Pagination -->
          <div class="card-footer">
            <nav>
              <ul class="pagination justify-content-center m-0">
                <?php if ($page > 1): ?>
                  <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
                  </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                  <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
                  </li>
                <?php endif; ?>
              </ul>
            </nav>
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
