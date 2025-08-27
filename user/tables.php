<?php
require '../config.php'; // this should define $pdo

if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch 5 most recently updated leads with follow-up status assigned to current user
$stmt = $pdo->prepare("
    SELECT id, name, status, updated_at 
    FROM leads 
    WHERE assigned_to = :user_id AND status = 'follow_up'
    ORDER BY updated_at DESC 
    LIMIT 5
");
$stmt->execute(['user_id' => $userId]);
$leads = $stmt->fetchAll();
?>

<div class="card mb-4">
  <div class="card-header">
    <h3 class="card-title">Your Follow-Up Leads</h3>
  </div>

  <div class="card-body p-0">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 10px">#</th>
          <th>Name</th>
          <th>Status</th>
          <th>Updated At</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($leads): ?>
          <?php foreach ($leads as $index => $lead): ?>
            <tr class="align-middle">
              <td><?= ($index + 1) ?>.</td>
              <td><?= htmlspecialchars($lead['name']) ?></td>
              <td>
                <span class="badge text-bg-warning">
                  <?= htmlspecialchars($lead['status']) ?>
                </span>
              </td>
              <td><?= date('Y-m-d H:i', strtotime($lead['updated_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="4" class="text-center">No follow-up leads found</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../footer.php'; ?>
