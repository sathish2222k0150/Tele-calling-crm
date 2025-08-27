<?php

require_once '../config.php'; // include your DB connection here
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: ../index.php');
    exit();
}
$today = date('Y-m-d');
$userName = '';
$notifications = [];
$unreadCount = 0;

if (isset($_SESSION['user_id'])) {
    // Get user info
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $userName = $user['name'];
    }

    // Get today's follow-ups for the logged-in user
    $stmt = $pdo->prepare("
        SELECT l.id as lead_id, l.name as lead_name, l.follow_up_date, n.id as notification_id, n.is_read
        FROM leads l
        LEFT JOIN notifications n ON n.reference_id = l.id AND n.type = 'followup' AND n.user_id = ?
        WHERE l.follow_up_date = ? AND l.assigned_to = ?
        ORDER BY l.follow_up_date ASC
    ");
    $stmt->execute([$_SESSION['user_id'], $today, $_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unread
    $unreadCount = count(array_filter($notifications, fn($n) => $n['is_read'] == 0));

    // Mark all unread follow-up notifications as read
    $unreadIds = array_column(array_filter($notifications, fn($n) => $n['notification_id'] && $n['is_read'] == 0), 'notification_id');
    if (!empty($unreadIds)) {
        $inQuery = implode(',', array_fill(0, count($unreadIds), '?'));
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id IN ($inQuery)");
        $stmt->execute($unreadIds);
    }

    // Insert notification entries if missing
    foreach ($notifications as $n) {
        if (!$n['notification_id']) {
            $insert = $pdo->prepare("INSERT INTO notifications (user_id, type, reference_id, is_read) VALUES (?, 'followup', ?, 1)");
            $insert->execute([$_SESSION['user_id'], $n['lead_id']]);
        }
    }
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
      <li class="nav-item d-none d-md-block"><a href="index.php" class="nav-link">Home</a></li>
      <li class="nav-item d-none d-md-block"><a href="https://sharadhaskillacademy.org/" class="nav-link">Contact</a></li>
    </ul>
    <ul class="navbar-nav ms-auto">
      <!-- Notification Bell -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-bs-toggle="dropdown" href="#">
          <i class="bi bi-bell-fill"></i>
          <?php if ($unreadCount > 0): ?>
            <span class="navbar-badge badge text-bg-warning"><?php echo $unreadCount; ?></span>
          <?php endif; ?>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
          <span class="dropdown-item dropdown-header"><?php echo count($notifications); ?> Follow-ups Today</span>
          <div class="dropdown-divider"></div>
          <?php if (count($notifications) > 0): ?>
            <?php foreach ($notifications as $note): ?>
              <a href="edit.php?id=<?php echo $note['lead_id']; ?>" class="dropdown-item">
                <i class="bi bi-person-lines-fill me-2"></i>
                <?php echo htmlspecialchars($note['lead_name']); ?> – Follow-up Today
                <span class="float-end text-secondary fs-7"><?php echo date('H:i'); ?></span>
              </a>
              <div class="dropdown-divider"></div>
            <?php endforeach; ?>
          <?php else: ?>
            <span class="dropdown-item">No follow-ups today</span>
          <?php endif; ?>
        </div>
      </li>

      <!-- Fullscreen -->
      <li class="nav-item">
        <a class="nav-link" href="#" data-lte-toggle="fullscreen">
          <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
          <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
        </a>
      </li>

      <!-- User Dropdown -->
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
            <a href="../logout.php" class="btn btn-default btn-flat float-end">Sign out</a>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</nav>
