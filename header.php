<?php

require 'config.php';
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
$userName = '';
$today = date('Y-m-d');
$followupCount = 0;
$reportCount = 0;
$totalNotifications = 0;
$followupsToday = [];
$reportsToday = [];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Get user name
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $userName = $user['name'] ?? '';

    // Unread follow-ups for today
    $stmt = $pdo->prepare("
        SELECT id, name, phone_number FROM leads
        WHERE DATE(follow_up_date) = ?
        AND assigned_to = ?
        AND id NOT IN (
            SELECT reference_id FROM notifications
            WHERE user_id = ? AND type = 'followup'
        )
    ");
    $stmt->execute([$today, $userId, $userId]);
    $followupsToday = $stmt->fetchAll();
    $followupCount = count($followupsToday);

    // Unread report uploads today
    $stmt = $pdo->prepare("
    SELECT dr.id, u.name 
    FROM daily_reports dr
    JOIN users u ON dr.user_id = u.id
    WHERE DATE(dr.created_at) = ?
    AND dr.id NOT IN (
        SELECT reference_id FROM notifications
        WHERE user_id = ? AND type = 'report'
    )
");
$stmt->execute([$today, $userId]);

    $reportsToday = $stmt->fetchAll();
    $reportCount = count($reportsToday);

    $totalNotifications = $followupCount + $reportCount;
}
?>

<nav class="app-header navbar navbar-expand bg-body">
  <div class="container-fluid">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
          <i class="bi bi-list"></i>
        </a>
      </li>
      <li class="nav-item d-none d-md-block"><a href="dashboard.php" class="nav-link">Home</a></li>
      <li class="nav-item d-none d-md-block"><a href="https://sharadhaskillacademy.org/" class="nav-link">Contact</a></li>
    </ul>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item dropdown">
        <a class="nav-link" data-bs-toggle="dropdown" href="#">
          <i class="bi bi-bell-fill"></i>
          <?php if ($totalNotifications > 0): ?>
            <span class="navbar-badge badge text-bg-warning"><?php echo $totalNotifications; ?></span>
          <?php endif; ?>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
          <span class="dropdown-item dropdown-header">
            <?php echo $totalNotifications; ?> Notifications
          </span>
          <div class="dropdown-divider"></div>

          <?php foreach ($followupsToday as $f): ?>
          <a href="#" class="dropdown-item mark-notification-read" data-id="<?= $f['id'] ?>" data-type="followup">
            <i class="bi bi-person-lines-fill me-2 text-success"></i>
            Follow-up: <?= htmlspecialchars($f['name']) ?> (<?= htmlspecialchars($f['phone_number']) ?>)
            <span class="float-end text-secondary fs-7">Today</span>
          </a>
          <div class="dropdown-divider"></div>
          <?php endforeach; ?>

          <?php foreach ($reportsToday as $r): ?>
          <a href="#" class="dropdown-item mark-notification-read" data-id="<?= $r['id'] ?>" data-type="report">
            <i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>
            New Upload: <?= htmlspecialchars($r['name']) ?>
            <span class="float-end text-secondary fs-7">Today</span>
          </a>
          <div class="dropdown-divider"></div>
          <?php endforeach; ?>

          <a href="notifications.php" class="dropdown-item dropdown-footer">See All Notifications</a>
        </div>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="#" data-lte-toggle="fullscreen">
          <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
          <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
        </a>
      </li>

      <li class="nav-item dropdown user-menu">
        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
          <img src="./assets/img/user2-160x160.jpg" class="user-image rounded-circle shadow" alt="User Image" />
          <span class="d-none d-md-inline"><?php echo htmlspecialchars($userName); ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
          <li class="user-header text-bg-primary">
            <img src="./assets/img/user2-160x160.jpg" class="rounded-circle shadow" alt="User Image" />
            <p>
              <?php echo htmlspecialchars($userName); ?>
              <small>Member since Nov. 2023</small>
            </p>
          </li>
          <li class="user-footer">
            <a href="./logout.php" class="btn btn-default btn-flat float-end">Sign out</a>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</nav>

<script>
document.querySelectorAll('.mark-notification-read').forEach(item => {
  item.addEventListener('click', function (e) {
    e.preventDefault();
    const refId = this.dataset.id;
    const type = this.dataset.type;

    fetch('mark_notification_read.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `reference_id=${refId}&type=${type}`
    }).then(() => location.reload());
  });
});
</script>
