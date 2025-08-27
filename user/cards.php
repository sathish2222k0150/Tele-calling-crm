<?php
include '../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Get telecaller role verification (optional if already filtered elsewhere)
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userRole = $stmt->fetchColumn();



// Total assigned leads
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = ?");
$stmt->execute([$userId]);
$totalLeads = $stmt->fetchColumn();

// New assigned leads today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE DATE(created_at) = CURDATE() AND assigned_to = ?");
$stmt->execute([$userId]);
$newLeadsToday = $stmt->fetchColumn();

// Total calls made today
$stmt = $pdo->prepare("SELECT COUNT(*) FROM call_logs WHERE user_id = ? AND DATE(created_at) = CURDATE()");
$stmt->execute([$userId]);
$totalCalls = $stmt->fetchColumn();

// Converted leads
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE status = 'converted' AND assigned_to = ?");
$stmt->execute([$userId]);
$convertedLeads = $stmt->fetchColumn();

// Conversion rate
$conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0;
?>


<main class="app-main">
  <div class="app-content">
    <div class="container-fluid">
      <h5 class="mb-2"></h5>
      <div class="row">
        <!-- Total Leads -->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-primary">
            <div class="inner">
              <h3><?= $totalLeads ?></h3>
              <p>Total Leads</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M2.25..."/></svg>
            <a href="#" class="small-box-footer link-light">More info <i class="bi bi-link-45deg"></i></a>
          </div>
        </div>

        <!-- Conversion Rate -->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-success">
            <div class="inner">
              <h3><?= $conversionRate ?><sup class="fs-5">%</sup></h3>
              <p>Conversion Rate</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M18.375..."/></svg>
            <a href="#" class="small-box-footer link-light">More info <i class="bi bi-link-45deg"></i></a>
          </div>
        </div>

        <!-- New Leads Today -->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-warning">
            <div class="inner">
              <h3><?= $newLeadsToday ?></h3>
              <p>New Leads Today</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M6.25..."/></svg>
            <a href="#" class="small-box-footer link-dark">More info <i class="bi bi-link-45deg"></i></a>
          </div>
        </div>

        <!-- Total Calls Today -->
        <div class="col-lg-3 col-6">
          <div class="small-box text-bg-danger">
            <div class="inner">
              <h3><?= $totalCalls ?></h3>
              <p>Total Calls Today</p>
            </div>
            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24"><path d="M2.25..."/></svg>
            <a href="#" class="small-box-footer link-light">More info <i class="bi bi-link-45deg"></i></a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

