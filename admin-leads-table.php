<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
$perPage = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Filters
$status = $_GET['status'] ?? '';
$assignedTo = $_GET['user'] ?? '';
$search = trim($_GET['search'] ?? '');

$where = "1";
$params = [];

if ($status) {
    $where .= " AND l.status = ?";
    $params[] = $status;
}

if ($assignedTo !== '') {
    $where .= " AND l.assigned_to = ?";
    $params[] = $assignedTo;
}

if ($search !== '') {
    $where .= " AND l.name LIKE ?";
    $params[] = "%$search%";
}

// Get total leads
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM leads l WHERE $where");
$totalStmt->execute($params);
$totalLeads = $totalStmt->fetchColumn();
$totalPages = ceil($totalLeads / $perPage);

// Main query with latest call_log for each lead
$sql = "
    SELECT 
        l.id, l.name, l.status, l.updated_at, u.name AS assigned_user
    FROM leads l
    LEFT JOIN users u ON l.assigned_to = u.id
    WHERE $where
    ORDER BY 
        CASE WHEN l.updated_at IS NULL OR l.updated_at = '' THEN 0 ELSE 1 END DESC,
        l.updated_at DESC
    LIMIT ? OFFSET ?
";

$params[] = $perPage;
$params[] = $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leads = $stmt->fetchAll();

$statuses = ['converted', 'in_progress', 'follow_up', 'not_interested'];
$users = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<style>
    .bulk-actions {
        display: none;
    }
    .top-controls .form-select,
    .top-controls .form-control,
    .top-controls .btn {
        min-width: 150px;
    }
    .top-controls {
        gap: 0.5rem;
    }
    .table td:nth-child(3),
    .table th:nth-child(3) {
        max-width: 120px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div class="container-fluid px-3">
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 top-controls">
                <h3 class="card-title mb-0">Leads Table</h3>
                <a href="export.php?<?= http_build_query($_GET) ?>" class="btn btn-sm btn-success">
                    <i class="fa fa-download"></i> Export CSV
                </a>
                <form method="GET" class="d-flex align-items-center gap-2 flex-wrap mb-0">
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                        class="form-control form-control-sm" placeholder="Search name...">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $s === $status ? 'selected' : '' ?>>
                                <?= ucfirst(str_replace('_', ' ', $s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="user" class="form-select form-select-sm">
                        <option value="">All Users</option>
                        <?php foreach ($users as $id => $name): ?>
                            <option value="<?= $id ?>" <?= $assignedTo == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-dark">Filter</button>
                    <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>"
                        class="btn btn-sm btn-outline-secondary">Clear</a>
                </form>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => max($page - 1, 1)])) ?>">«</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => min($page + 1, $totalPages)])) ?>">»</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <div class="bulk-actions mt-2">
                <form method="GET" id="bulkForm">
                    <input type="hidden" name="ids" id="bulkIds">
                    <button type="button" class="btn btn-sm btn-danger" onclick="submitBulk('bulk_delete.php')">
                        <i class="fa-solid fa-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>#</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Updated At</th>
                            <th>Assigned To</th>
                            <th>Recording</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($leads): ?>
                            <?php foreach ($leads as $index => $lead): ?>
                                <tr>
                                    <td><input type="checkbox" class="lead-checkbox" value="<?= $lead['id'] ?>"></td>
                                    <td><?= ($offset + $index + 1) ?></td>
                                    <td><?= htmlspecialchars($lead['name']) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php
                                            switch ($lead['status']) {
                                                case 'converted':
                                                    echo 'text-bg-success';
                                                    break;
                                                case 'in_progress':
                                                    echo 'text-bg-primary';
                                                    break;
                                                case 'follow_up':
                                                    echo 'text-bg-warning';
                                                    break;
                                                case 'not_interested':
                                                    echo 'text-bg-danger';
                                                    break;
                                                default:
                                                    echo 'text-bg-secondary';
                                                    break;
                                            } ?>">
                                            <?= htmlspecialchars($lead['status'] ?: 'N/A') ?>
                                        </span>
                                    </td>
                                    <td><?= $lead['updated_at'] ? date('Y-m-d H:i', strtotime($lead['updated_at'])) : 'Not Updated' ?>
                                    </td>
                                    <td><?= htmlspecialchars($lead['assigned_user'] ?? 'Unassigned') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="showRecordings(<?= $lead['id'] ?>)">
                                            <i class="fa fa-play-circle"></i> View
                                        </button>
                                    </td>
                                    <td>
                                        <a href="?delete=<?= $lead['id'] ?>&page=<?= $page ?>" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center">No leads found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Recordings -->
<div class="modal fade" id="recordingModal" tabindex="-1" aria-labelledby="recordingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa fa-headphones"></i> Lead Recordings</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="recordingList">
        <div class="text-center text-muted">Loading...</div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const checkboxes = document.querySelectorAll('.lead-checkbox');
    const selectAll = document.getElementById('selectAll');
    const bulkActions = document.querySelector('.bulk-actions');
    const bulkIds = document.getElementById('bulkIds');

    function updateBulkVisibility() {
        const selected = Array.from(checkboxes).filter(cb => cb.checked);
        bulkActions.style.display = selected.length > 0 ? 'flex' : 'none';
    }

    selectAll.addEventListener('change', () => {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateBulkVisibility();
    });

    checkboxes.forEach(cb => cb.addEventListener('change', updateBulkVisibility));

    function submitBulk(actionUrl) {
        const selectedIds = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value).join(',');
        if (!selectedIds) return;
        window.location.href = actionUrl + '?ids=' + encodeURIComponent(selectedIds) + '&<?= http_build_query($_GET) ?>';
    }

    function showRecordings(leadId) {
    const modal = new bootstrap.Modal(document.getElementById('recordingModal'));
    const list = document.getElementById('recordingList');
    list.innerHTML = `<div class="text-center text-muted">Loading...</div>`;
    modal.show();

    fetch('view-recordings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `lead_id=${encodeURIComponent(leadId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            list.innerHTML = `<div class="text-danger text-center">${data.message || 'Failed to load recordings.'}</div>`;
            return;
        }

        if (!data.recordings.length) {
            list.innerHTML = `<div class="text-muted text-center">No recordings available.</div>`;
            return;
        }

        list.innerHTML = data.recordings.map((item, i) => `
            <div class="mb-3">
                <strong>Recording ${i + 1}</strong><br>
                <audio controls class="w-100 mt-1">
                    <source src="${item.url}" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
                <small class="text-muted">Call Time: ${item.time} | Duration: ${item.duration} sec</small>
            </div>
        `).join('');
    })
    .catch(err => {
        list.innerHTML = `<div class="text-danger text-center">Failed to load recordings.</div>`;
        console.error(err);
    });
}

</script>
<script
            src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
            crossorigin="anonymous"></script>
          <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
            crossorigin="anonymous"></script>
          <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"
            crossorigin="anonymous"></script>
          <script src="./js/adminlte.js"></script>

<?php include 'footer.php'; ?>
