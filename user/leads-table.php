<?php
require '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// AJAX recording fetch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_recordings'])) {
    header('Content-Type: application/json');

    if (!isset($_POST['lead_id']) || !is_numeric($_POST['lead_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
        exit;
    }

    $leadId = (int) $_POST['lead_id'];

    try {
        $stmt = $pdo->prepare("SELECT recording_url, call_start FROM call_logs WHERE lead_id = ? AND recording_url IS NOT NULL ORDER BY call_start DESC");
        $stmt->execute([$leadId]);
        $recordings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = array_map(function ($row) {
            return [
                'url' => $row['recording_url'],
                'time' => date('Y-m-d H:i', strtotime($row['call_start']))
            ];
        }, $recordings);

        echo json_encode(['success' => true, 'recordings' => $data]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'DB error', 'error' => $e->getMessage()]);
    }
    exit;
}

// Main table display logic
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

$perPage = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$filterStatus = $_GET['status'] ?? '';
$filterCourse = $_GET['course'] ?? '';
$searchQuery = $_GET['search'] ?? '';

// ---------- COUNT QUERY ----------
$countSql = "SELECT COUNT(*) FROM leads WHERE 1";
$countParams = [];

if ($role !== 'admin') {
    $countSql .= " AND assigned_to = :userId";
    $countParams[':userId'] = $userId;
}
if (!empty($filterStatus)) {
    $countSql .= " AND status = :status";
    $countParams[':status'] = $filterStatus;
}
if (!empty($filterCourse)) {
    $countSql .= " AND course = :course";
    $countParams[':course'] = $filterCourse;
}
if (!empty($searchQuery)) {
    $countSql .= " AND (name LIKE :search1 OR phone_number LIKE :search2 OR email LIKE :search3 OR course LIKE :search4)";
    $countParams[':search1'] = "%$searchQuery%";
    $countParams[':search2'] = "%$searchQuery%";
    $countParams[':search3'] = "%$searchQuery%";
    $countParams[':search4'] = "%$searchQuery%";
}


$countStmt = $pdo->prepare($countSql);
$countStmt->execute($countParams);
$totalLeads = $countStmt->fetchColumn();
$totalPages = ceil($totalLeads / $perPage);

// ---------- DATA QUERY ----------
$orderClause = "CASE 
    WHEN l.status IS NULL OR l.status = '' THEN 1
    WHEN l.status = 'follow_up' THEN 2
    ELSE 3
END, l.updated_at DESC";

$sql = "
    SELECT l.id, l.name, l.course, l.status, l.updated_at, u.name AS assigned_user, l.phone_number
    FROM leads l
    LEFT JOIN users u ON l.assigned_to = u.id
    WHERE 1";
$params = [];

if ($role !== 'admin') {
    $sql .= " AND l.assigned_to = :userId";
    $params[':userId'] = $userId;
}
if (!empty($filterStatus)) {
    $sql .= " AND l.status = :status";
    $params[':status'] = $filterStatus;
}
if (!empty($filterCourse)) {
    $sql .= " AND l.course = :course";
    $params[':course'] = $filterCourse;
}
if (!empty($searchQuery)) {
    $sql .= " AND (l.name LIKE :search1 OR l.phone_number LIKE :search2 OR l.email LIKE :search3 OR l.course LIKE :search4)";
    $params[':search1'] = "%$searchQuery%";
    $params[':search2'] = "%$searchQuery%";
    $params[':search3'] = "%$searchQuery%";
    $params[':search4'] = "%$searchQuery%";
}


// Append LIMIT + OFFSET directly (safe because they’re integers)
$sql .= " ORDER BY $orderClause LIMIT $perPage OFFSET $offset";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);  // ✅ safest method to avoid parameter mismatches
    $leads = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Fetch course list
$courses = $pdo->query("SELECT DISTINCT course FROM leads WHERE course IS NOT NULL AND course != '' ORDER BY course ASC")->fetchAll(PDO::FETCH_COLUMN);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leads Table</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-container {
            max-width: 300px;
        }
        @media (max-width: 768px) {
            .search-container {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid px-3 py-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h3 class="card-title mb-2 mb-md-0">Leads Table</h3>
                <form method="GET" class="d-flex gap-2 mb-2 mb-md-0 align-items-center flex-wrap">
                    <div class="search-container me-2">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="Search..." 
                                value="<?= htmlspecialchars($searchQuery) ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-search"></i>
                            </button>
                            <?php if (!empty($searchQuery)): ?>
                            <a href="?" class="btn btn-outline-secondary">
                                <i class="fa fa-times"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <?php foreach (['new', 'in_progress', 'interested', 'follow_up', 'converted', 'not_interested', 'closed'] as $status): ?>
                            <option value="<?= $status ?>" <?= $filterStatus == $status ? 'selected' : '' ?>>
                                <?= ucwords(str_replace('_', ' ', $status)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="course" class="form-select form-select-sm">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= htmlspecialchars($course) ?>" <?= $filterCourse === $course ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fa fa-filter"></i> Filter
                    </button>
                </form>

                <div class="card-tools d-flex align-items-center gap-2">
                    <a href="export_leads.php" class="btn btn-sm btn-success">
                        <i class="fa-solid fa-file-csv"></i> Export CSV
                    </a>
                    <a href="add-leads.php" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-plus"></i> Add Lead
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>S.No</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th>Recording</th>
                                <th>Assigned To</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leads as $index => $lead): ?>
                                <tr>
                                    <td><?= $offset + $index + 1 ?>.</td>
                                    <td><?= htmlspecialchars($lead['name']) ?></td>
                                    <td><?= htmlspecialchars($lead['phone_number']) ?></td>
                                    <td><?= htmlspecialchars($lead['course']) ?></td>
                                    <td>
                                        <span
                                            class="badge bg-secondary"><?= htmlspecialchars($lead['status'] ?: 'N/A') ?></span>
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($lead['updated_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info view-recordings-btn"
                                            data-lead-id="<?= $lead['id'] ?>">
                                            <i class="fa fa-headphones"></i> View
                                        </button>
                                    </td>
                                    <td><?= htmlspecialchars($lead['assigned_user'] ?? 'Unassigned') ?></td>
                                    <td>
                                        <a href="edit.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($leads)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-3">No leads found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-center">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link"
                            href="?page=<?= $page - 1 ?>&status=<?= $filterStatus ?>&course=<?= $filterCourse ?>&search=<?= urlencode($searchQuery) ?>">&laquo;</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                            <a class="page-link"
                                href="?page=<?= $i ?>&status=<?= $filterStatus ?>&course=<?= $filterCourse ?>&search=<?= urlencode($searchQuery) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link"
                            href="?page=<?= $page + 1 ?>&status=<?= $filterStatus ?>&course=<?= $filterCourse ?>&search=<?= urlencode($searchQuery) ?>">&raquo;</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="recordingModal" tabindex="-1" aria-labelledby="recordingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recordingModalLabel">Lead Recordings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="recordingList" class="list-group"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(function () {
    $('.view-recordings-btn').on('click', function () {
        const leadId = $(this).data('lead-id');
        $('#recordingList').html('<div class="text-center py-3">Loading recordings...</div>');
        $('#recordingModal').modal('show');

        $.post('get_recordings.php', { lead_id: leadId }, function (response) {
            if (response.success && response.recordings.length > 0) {
                $('#recordingModalLabel').text(`Recordings`);
                let html = '';
                response.recordings.forEach(function (rec, index) {
                    html += `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between mb-2">
                                <strong>Recording ${index + 1}</strong>
                                <div class="text-end">
                                    <small class="text-muted">Uploaded at: ${rec.created_time}</small><br>
                                    <small class="text-muted">Call Time: ${rec.call_time}</small>
                                </div>
                            </div>
                            <audio controls style="width: 100%;" preload="none">
                                <source src="${rec.url}" type="audio/mpeg">
                                Your browser does not support the audio element.
                            </audio>
                        </div>`;
                });
                $('#recordingList').html(html);
            } else {
                $('#recordingList').html('<div class="text-muted">No recordings found for this lead.</div>');
            }
        }, 'json').fail(function () {
            $('#recordingList').html('<div class="text-danger">AJAX request failed. Please check the console.</div>');
        });
    });
});
    </script>
</body>

</html>