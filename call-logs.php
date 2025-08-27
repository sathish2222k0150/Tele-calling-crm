<?php
include 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
$limit = 15;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
  $countQuery = "
    SELECT COUNT(*) FROM call_logs cl
    JOIN leads l ON cl.lead_id = l.id
    WHERE l.name COLLATE utf8mb4_general_ci LIKE :search
  ";
  $totalStmt = $pdo->prepare($countQuery);
  $totalStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
  $totalStmt->execute();
  $totalRows = $totalStmt->fetchColumn();
  $totalPages = ceil($totalRows / $limit);

  $stmt = $pdo->prepare("
    SELECT 
      cl.id,
      cl.call_start,
      cl.duration_seconds,
      cl.recording_url,
      l.status,
      l.name AS lead_name,
      l.id AS lead_id,
      u.name AS telecaller_name
    FROM call_logs cl
    JOIN leads l ON cl.lead_id = l.id
    JOIN users u ON cl.user_id = u.id
    WHERE l.name COLLATE utf8mb4_general_ci LIKE :search
    ORDER BY cl.call_start DESC
    LIMIT :start, :limit
  ");
  $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
  $stmt->bindValue(':start', $start, PDO::PARAM_INT);
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  echo "Error fetching data: " . $e->getMessage();
  exit;
}
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

  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6">
            <h3 class="mb-0">Call Logs</h3>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-end">
              <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
              <li class="breadcrumb-item active">Call Logs</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="app-content">
      <div class="container-fluid">
        <div class="card-header bg-primary text-white">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Recent Call Logs</h5>
            <div class="d-flex gap-2">
              <form action="export_call_logs.php" method="post" class="d-inline">
                <button type="submit" class="btn btn-success btn-sm">
                  <i class="bi bi-file-earmark-excel"></i> Export
                </button>
              </form>
              <button id="deleteSelected" class="btn btn-danger btn-sm d-none">
                <i class="bi bi-trash"></i> Delete Selected
              </button>
            </div>
          </div>
        </div>

        <div class="card-body table-responsive">
          <form method="get" class="mb-4 mt-2 d-flex justify-content-end gap-2">
            <input type="text" name="search" class="form-control w-25" placeholder="Search Lead Name" value="<?= htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-secondary">Search</button>
            <?php if (!empty($search)): ?>
              <a href="call_logs.php" class="btn btn-outline-danger">Clear</a>
            <?php endif; ?>
          </form>

          <form id="callLogForm">
            <table class="table table-bordered table-hover">
              <thead class="table-dark">
              <tr>
                <th><input type="checkbox" id="selectAll" /></th>
                <th>Date/Time</th>
                <th>Lead Name</th>
                <th>Telecaller Name</th>
                <th>Duration</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
              </thead>
              <tbody>
              <?php if (!empty($results)): ?>
                <?php foreach ($results as $row): ?>
                  <tr>
                    <td><input type="checkbox" class="selectItem" name="ids[]" value="<?= $row['id'] ?>"></td>
                    <td><?= date("d-m-Y h:i A", strtotime($row['call_start'])); ?></td>
                    <td><?= htmlspecialchars($row['lead_name']); ?></td>
                    <td><?= htmlspecialchars($row['telecaller_name']); ?></td>
                    <td><?= (int) $row['duration_seconds']; ?> sec</td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($row['status']); ?></span></td>
                    <td>
                      <?php if (!empty($row['recording_url'])): ?>
                        <button type="button" class="btn btn-sm btn-primary view-recordings-btn" data-lead-id="<?= $row['lead_id'] ?>">
                          <i class="bi bi-play-circle"></i> View Recordings
                        </button>
                      <?php else: ?>
                        <span class="text-muted">No Recording</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="text-center text-muted">No call logs found for the given search.</td>
                </tr>
              <?php endif; ?>
              </tbody>
            </table>
          </form>

          <nav>
            <ul class="pagination justify-content-center mt-3">
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>"><?= $i; ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Modal -->
<div class="modal fade" id="recordingsModal" tabindex="-1" aria-labelledby="recordingsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Call Recordings</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="recordingsModalBody">
        <p class="text-muted">Loading recordings...</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="./js/adminlte.js"></script>
<script>
  document.getElementById('selectAll').addEventListener('change', function () {
    const checkboxes = document.querySelectorAll('.selectItem');
    checkboxes.forEach(cb => cb.checked = this.checked);
    toggleDeleteButton();
  });

  document.querySelectorAll('.selectItem').forEach(cb => {
    cb.addEventListener('change', toggleDeleteButton);
  });

  function toggleDeleteButton() {
    const selected = document.querySelectorAll('.selectItem:checked').length;
    document.getElementById('deleteSelected').classList.toggle('d-none', selected === 0);
  }

  document.getElementById('deleteSelected').addEventListener('click', function () {
    if (confirm('Are you sure you want to delete selected call logs?')) {
      const form = document.getElementById('callLogForm');
      const formData = new FormData(form);
      fetch('delete_call_logs.php', {
        method: 'POST',
        body: formData
      }).then(res => res.text()).then(res => {
        location.reload();
      });
    }
  });

  // Load recordings via AJAX
  document.querySelectorAll('.view-recordings-btn').forEach(button => {
    button.addEventListener('click', function () {
      const leadId = this.getAttribute('data-lead-id');
      const modalBody = document.getElementById('recordingsModalBody');
      modalBody.innerHTML = '<p class="text-muted">Loading recordings...</p>';

      fetch('get_recordings.php?lead_id=' + leadId)
        .then(response => response.text())
        .then(data => {
          modalBody.innerHTML = data;
          const modal = new bootstrap.Modal(document.getElementById('recordingsModal'));
          modal.show();
        });
    });
  });
</script>
</body>
</html>
