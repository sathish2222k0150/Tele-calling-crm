<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
?>


<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="./dashboard.php" class="brand-link">
      <img src="./assets/img/logo sharadha.jpg" alt="Sharadha Skill Academy Logo" class="brand-image opacity-75 shadow" />
      <span class="brand-text fw-light">Sharadha Skill Academy</span>
    </a>
  </div>
  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation" aria-label="Main navigation" data-accordion="false" id="navigation">
        <li class="nav-item menu-open">
          <a href="dashboard.php" class="nav-link active">
            <i class="nav-icon bi bi-speedometer2"></i>
            <p>
              Dashboard
            </p>
          </a>
        </li>
        <li class="nav-header">MANAGEMENT</li>
        <li class="nav-item">
          <a href="leads.php" class="nav-link">
            <i class="nav-icon bi bi-people-fill"></i>
            <p>Leads Management</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="users.php" class="nav-link">
            <i class="nav-icon bi bi-telephone-fill"></i>
            <p>Users</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="call-logs.php" class="nav-link">
            <i class="nav-icon bi bi-journal-text"></i>
            <p>Call Logs</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="reports.php" class="nav-link">
            <i class="nav-icon bi bi-graph-up"></i>
            <p>Reports</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="./logout.php" class="nav-link">
            <i class="nav-icon bi bi-box-arrow-right"></i>
            <p>Logout</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
