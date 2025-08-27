<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
// Get first lead date
$firstDate = $pdo->query("SELECT MIN(DATE(created_at)) FROM leads")->fetchColumn();

// Get last lead date
$lastDate = $pdo->query("SELECT MAX(DATE(created_at)) FROM leads")->fetchColumn();

// Format nicely, e.g., "1 Jan, 2023"
$firstDateFormatted = $firstDate ? date('j M, Y', strtotime($firstDate)) : '-';
$lastDateFormatted = $lastDate ? date('j M, Y', strtotime($lastDate)) : '-';


$totalLeads = $pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();

// Total converted leads
$totalConverted = $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'converted'")->fetchColumn();

// Total leads with follow-up (distinct lead_id)
$totalFollowUp = $pdo->query("
    SELECT COUNT(*) 
    FROM leads 
    WHERE status = 'follow_up'
")->fetchColumn();


// Total leads in progress
$totalInProgress = $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'in_progress'")->fetchColumn();

// Total leads marked as not interested
$totalNotInterested = $pdo->query("SELECT COUNT(*) FROM leads WHERE status = 'not_interested'")->fetchColumn();

// Total leads uploaded today
$totalToday = $pdo->query("SELECT COUNT(*) FROM leads WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// Avoid division by zero
$totalLeads = max($totalLeads, 1);

// Calculate percentages
$convertedPercent = round(($totalConverted / $totalLeads) * 100);
$followUpPercent = round(($totalFollowUp / $totalLeads) * 100);
$inProgressPercent = round(($totalInProgress / $totalLeads) * 100);
$notInterestedPercent = round(($totalNotInterested / $totalLeads) * 100);
$todayPercent = round(($totalToday / $totalLeads) * 100);
?>
<div class="row">
  <div class="col-md-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title">Monthly Recap Report</h5>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-lte-toggle="card-collapse">
            <i data-lte-icon="expand" class="bi bi-plus-lg"></i>
            <i data-lte-icon="collapse" class="bi bi-dash-lg"></i>
          </button>
          <div class="btn-group">
            <button type="button" class="btn btn-tool dropdown-toggle" data-bs-toggle="dropdown">
              <i class="bi bi-wrench"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end" role="menu">
              <a href="#" class="dropdown-item">Action</a>
              <a href="#" class="dropdown-item">Another action</a>
              <a href="#" class="dropdown-item"> Something else here </a>
              <a class="dropdown-divider"></a>
              <a href="#" class="dropdown-item">Separated link</a>
            </div>
          </div>
          <button type="button" class="btn btn-tool" data-lte-toggle="card-remove">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </div>
      <!-- /.card-header -->
      <div class="card-body">
        <!--begin::Row-->
        <div class="row">
          <div class="col-md-8">
            <p class="text-center">
              <strong>Sales: <?= $firstDateFormatted ?> - <?= $lastDateFormatted ?></strong>
            </p>
            <div id="sales-chart"></div>
          </div>
          <!-- /.col -->
          <div class="col-md-4">
            <p class="text-center"><strong>Goal Completion</strong></p>
            <div class="progress-group mb-3">
              Converted Leads
              <span class="float-end"><b><?= $totalConverted ?></b> / <?= $totalLeads ?></span>
              <div class="progress progress-sm">
                <div class="progress-bar bg-success" style="width: <?= $convertedPercent ?>%"></div>
              </div>
            </div>
            <!-- /.progress-group -->
            <div class="progress-group mb-3">
              Leads with Follow-up
              <span class="float-end"><b><?= $totalFollowUp ?></b> / <?= $totalLeads ?></span>
              <div class="progress progress-sm">
                <div class="progress-bar bg-info" style="width: <?= $followUpPercent ?>%"></div>
              </div>
            </div>
            <div class="progress-group mb-3">
              In Progress
              <span class="float-end"><b><?= $totalInProgress ?></b> / <?= $totalLeads ?></span>
              <div class="progress progress-sm">
                <div class="progress-bar bg-primary" style="width: <?= $inProgressPercent ?>%"></div>
              </div>
            </div>
            <div class="progress-group mb-3">
              Not Interested
              <span class="float-end"><b><?= $totalNotInterested ?></b> / <?= $totalLeads ?></span>
              <div class="progress progress-sm">
                <div class="progress-bar bg-danger" style="width: <?= $notInterestedPercent ?>%"></div>
              </div>
            </div>
            <div class="progress-group mb-3">
              Uploaded Today
              <span class="float-end"><b><?= $totalToday ?></b> / <?= $totalLeads ?></span>
              <div class="progress progress-sm">
                <div class="progress-bar bg-warning" style="width: <?= $todayPercent ?>%"></div>
              </div>
            </div>
            <!-- /.progress-group -->
          </div>
          <!-- /.col -->
        </div>
        <!--end::Row-->
      </div>
      <!-- ./card-body -->
    </div>
    <!--end::App Main-->
  </div>
</div>