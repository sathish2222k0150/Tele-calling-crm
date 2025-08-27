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
    $userName = $user['name'] ?? '';
}

$users = $pdo->query("SELECT id, name FROM users")->fetchAll(PDO::FETCH_ASSOC);

// Get course list
$courseList = $pdo->query("SELECT DISTINCT course FROM leads WHERE course != ''")->fetchAll(PDO::FETCH_COLUMN);

// Filters
$filter = $_GET['filter'] ?? 'all';
$view = $_GET['view'] ?? 'unassigned';
$courseFilter = $_GET['course'] ?? '';

// Date filters
$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Build WHERE clause
$where = "WHERE 1";
$params = [];

if ($view === 'assigned') {
    $where .= " AND l.assigned_to IS NOT NULL";
} else {
    $where .= " AND (l.assigned_to IS NULL OR l.assigned_to = '')";
}

switch ($filter) {
    case 'today':
        $where .= " AND DATE(l.created_at) = ?";
        $params[] = $today;
        break;
    case 'yesterday':
        $where .= " AND DATE(l.created_at) = ?";
        $params[] = $yesterday;
        break;
}

if (!empty($courseFilter)) {
    $where .= " AND l.course = ?";
    $params[] = $courseFilter;
}

// Fetch leads
$sql = "SELECT l.id, l.name, l.course, u.name AS assigned_user
        FROM leads l
        LEFT JOIN users u ON l.assigned_to = u.id
        $where
        ORDER BY l.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$successMessage = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $leadIds = json_decode($_POST['all_lead_ids'] ?? '[]', true);
    $viewMode = $_POST['view_mode'] ?? 'unassigned';

    if (!empty($leadIds) && $userId) {
        try {
            $pdo->beginTransaction();

            $insertStmt = $pdo->prepare("INSERT INTO lead_assignments (lead_id, user_id) VALUES (:lead_id, :user_id)");
            $updateStmt = $pdo->prepare("UPDATE leads SET assigned_to = :user_id WHERE id = :lead_id");
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM lead_assignments WHERE lead_id = :lead_id");

            $totalProcessed = 0;

            foreach ($leadIds as $leadId) {
                if ($viewMode === 'unassigned') {
                    $checkStmt->execute([':lead_id' => $leadId]);
                    $alreadyAssigned = $checkStmt->fetchColumn();
                    if (!$alreadyAssigned) {
                        $insertStmt->execute([':lead_id' => $leadId, ':user_id' => $userId]);
                        $updateStmt->execute([':lead_id' => $leadId, ':user_id' => $userId]);
                        $totalProcessed++;
                    }
                } else {
                    $pdo->prepare("DELETE FROM lead_assignments WHERE lead_id = :lead_id")
                        ->execute([':lead_id' => $leadId]);

                    $insertStmt->execute([':lead_id' => $leadId, ':user_id' => $userId]);
                    $updateStmt->execute([':lead_id' => $leadId, ':user_id' => $userId]);
                    $totalProcessed++;
                }
            }

            $pdo->commit();
            $_SESSION['success'] = "Successfully " . ($viewMode === 'assigned' ? 'reassigned' : 'assigned') . " $totalProcessed leads.";
            header("Location: bulk-assign.php?view=$viewMode&filter=$filter&course=" . urlencode($courseFilter));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error occurred: " . $e->getMessage();
        }
    } else {
        $message = "Please select a user.";
    }
}

// Fetch course-wise previous/current assignment summary
$assignmentSummary = $pdo->query("
    SELECT 
        l.course,
        GROUP_CONCAT(DISTINCT u1.name) AS current_users,
        GROUP_CONCAT(DISTINCT u2.name) AS previous_users
    FROM leads l
    LEFT JOIN users u1 ON l.assigned_to = u1.id
    LEFT JOIN lead_assignments la ON l.id = la.lead_id
    LEFT JOIN users u2 ON la.user_id = u2.id
    WHERE l.course != ''
    GROUP BY l.course
    ORDER BY l.course ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Sharadha Skill Academy CRM - Call Logs</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="./css/adminlte.css" />
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
  <?php include 'header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <main class="app-main container-fluid py-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h4 class="card-title mb-4">Bulk <?= $view === 'assigned' ? 'Reassign' : 'Assign' ?> Leads by Course</h4>

        <?php if ($message): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
          <div class="alert alert-success"><?= htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <!-- View Toggle -->
        <div class="mb-3 d-flex gap-2">
          <a href="?view=unassigned&filter=<?= $filter ?>&course=<?= urlencode($courseFilter) ?>" class="btn btn-outline-secondary <?= $view === 'unassigned' ? 'active' : '' ?>">Unassigned</a>
          <a href="?view=assigned&filter=<?= $filter ?>&course=<?= urlencode($courseFilter) ?>" class="btn btn-outline-secondary <?= $view === 'assigned' ? 'active' : '' ?>">Assigned</a>
        </div>

        <!-- Date Filter -->
        <div class="mb-3 d-flex gap-2">
          <a href="?view=<?= $view ?>&filter=all&course=<?= urlencode($courseFilter) ?>" class="btn btn-outline-primary <?= $filter == 'all' ? 'active' : '' ?>">All</a>
          <a href="?view=<?= $view ?>&filter=today&course=<?= urlencode($courseFilter) ?>" class="btn btn-outline-primary <?= $filter == 'today' ? 'active' : '' ?>">Today</a>
          <a href="?view=<?= $view ?>&filter=yesterday&course=<?= urlencode($courseFilter) ?>" class="btn btn-outline-primary <?= $filter == 'yesterday' ? 'active' : '' ?>">Yesterday</a>
        </div>

        <!-- Course Filter -->
        <div class="mb-3">
          <form method="get" class="row g-3">
            <input type="hidden" name="view" value="<?= $view ?>">
            <input type="hidden" name="filter" value="<?= $filter ?>">
            <div class="col-md-4">
              <select name="course" class="form-select" onchange="this.form.submit()">
                <option value="">-- Filter by Course --</option>
                <?php foreach ($courseList as $course): ?>
                  <option value="<?= htmlspecialchars($course) ?>" <?= $course === $courseFilter ? 'selected' : '' ?>>
                    <?= htmlspecialchars($course) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </form>
        </div>

        <?php if (count($leads)): ?>
          <form method="post">
            <input type="hidden" name="view_mode" value="<?= $view ?>">
            <input type="hidden" name="all_lead_ids" value='<?= htmlspecialchars(json_encode(array_column($leads, 'id'))) ?>'>

            <div class="mb-3">
              <label for="user_id" class="form-label">Select User:</label>
              <select name="user_id" id="user_id" class="form-select" required>
                <option value="">-- Select User --</option>
                <?php foreach ($users as $user): ?>
                  <option value="<?= $user['id']; ?>"><?= htmlspecialchars($user['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="alert alert-info">
              <strong><?= count($leads) ?> leads</strong> matching course <strong><?= htmlspecialchars($courseFilter ?: 'All') ?></strong> will be <?= $view === 'assigned' ? 'reassigned' : 'assigned' ?> to the selected user.
            </div>

            <button type="submit" class="btn btn-success">
              <i class="bi bi-person-check"></i> <?= $view === 'assigned' ? 'Reassign' : 'Assign' ?> All Leads
            </button>
          </form>
        <?php else: ?>
          <div class="alert alert-warning">No leads found matching selected criteria.</div>
        <?php endif; ?>

        <!-- Course Assignment Summary -->
        <?php if (!empty($assignmentSummary)): ?>
          <div class="mt-5">
            <h5>Course Assignment Summary</h5>
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead class="table-light">
                  <tr>
                    <th>Course</th>
                    <th>Previously Assigned To</th>
                    <th>Currently Assigned To</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($assignmentSummary as $row): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['course']) ?></td>
                      <td><?= $row['previous_users'] ?: '<span class="text-muted">Unassigned</span>' ?></td>
                      <td><?= $row['current_users'] ?: '<span class="text-muted">Unassigned</span>' ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </main>
</div>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="./js/adminlte.js"></script>
</html>
