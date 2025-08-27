<?php
include '../config.php';
require_once './vendor/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

use Google\Client;
use Google\Service\Drive;

$feedback = null;

// If Sync button clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_drive'])) {
    $user_id = $_SESSION['user_id'];
    $uploadDir = __DIR__ . '/uploads/recordings/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $serviceAccountFile = __DIR__ . '/service-account.json';
    $folderId = '1iG6-5AIk90GAfL2iAEiLNfdfNsflg-GU';

    $client = new Client();
    $client->setAuthConfig($serviceAccountFile);
    $client->addScope(Drive::DRIVE_READONLY);
    $service = new Drive($client);

    // Get files in folder
    $response = $service->files->listFiles([
        'q' => "'" . $folderId . "' in parents and trashed = false",
        'fields' => 'files(id, name, mimeType)',
        'pageSize' => 100
    ]);

    $files = $response->files;
    if (empty($files)) {
        $_SESSION['upload_feedback'] = [
            'successCount' => 0,
            'errors' => ['No files found in Google Drive folder.']
        ];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $getID3 = new getID3;
    $successCount = 0;
    $errorFiles = [];

    foreach ($files as $file) {
        $originalName = $file->name;

        // Extract phone number from filename
        if (preg_match('/(?:\+91|91)?(\d{10})/', $originalName, $matches)) {
            $normalizedNumber = $matches[1];
            $possiblePhones = ['91' . $normalizedNumber, '+91' . $normalizedNumber, $normalizedNumber];
        } else {
            $errorFiles[] = $originalName . " (Phone number not found)";
            continue;
        }

        // Match lead in DB
        $lead = null;
        foreach ($possiblePhones as $phoneNumber) {
            $stmt = $pdo->prepare("
                SELECT l.id, l.status 
                FROM leads l
                INNER JOIN lead_assignments la ON la.lead_id = l.id
                WHERE l.phone_number = :phone AND la.user_id = :user_id
            ");
            $stmt->execute([
                ':phone' => $phoneNumber,
                ':user_id' => $user_id
            ]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($lead) break;
        }

        if (!$lead) {
            $errorFiles[] = $originalName . " (Lead not found or not assigned to you)";
            continue;
        }

        // Download file from Drive
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = 'call_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $destination = $uploadDir . $filename;

        $content = $service->files->get($file->id, ["alt" => "media"]);
        file_put_contents($destination, $content->getBody());

        // Get call start date from file name or fallback to now
        if (preg_match('/(\d{4}-\d{2}-\d{2})[\s_]+(\d{2})[-:](\d{2})[-:](\d{2})/', $originalName, $dtMatches)) {
            $date = $dtMatches[1];
            $time = "{$dtMatches[2]}:{$dtMatches[3]}:{$dtMatches[4]}";
            $callStart = date('Y-m-d H:i:s', strtotime("$date $time"));
        } else {
            $callStart = date('Y-m-d H:i:s');
        }

        // Get duration
        $fileInfo = $getID3->analyze($destination);
        $duration = isset($fileInfo['playtime_seconds']) ? round($fileInfo['playtime_seconds']) : 120;
        $callEnd = date('Y-m-d H:i:s', strtotime($callStart . " +$duration seconds"));

        // Insert into DB
        $stmt = $pdo->prepare("
            INSERT INTO call_logs (lead_id, user_id, call_start, call_end, duration_seconds, status, recording_url)
            VALUES (:lead_id, :user_id, :call_start, :call_end, :duration, :status, :recording_url)
        ");
        $stmt->execute([
            ':lead_id' => $lead['id'],
            ':user_id' => $user_id,
            ':call_start' => $callStart,
            ':call_end' => $callEnd,
            ':duration' => $duration,
            ':status' => $lead['status'] ?? 'pending',
            ':recording_url' => $destination
        ]);

        $successCount++;
    }

    $_SESSION['upload_feedback'] = [
        'successCount' => $successCount,
        'errors' => $errorFiles
    ];

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$feedback = $_SESSION['upload_feedback'] ?? null;
unset($_SESSION['upload_feedback']);
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Sharadha Skill Academy CRM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="../css/adminlte.css" />
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
  <div class="app-wrapper">
    <?php include './header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <main class="app-main">
      <div class="app-content-header">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-6">
              <h3 class="mb-0">Sync Call Recordings</h3>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Sync Recordings</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <div class="app-content">
        <div class="container-fluid">
          <div class="card mb-4">
            <div class="card-header bg-primary text-white">
              <h5 class="mb-0">Sync from Google Drive</h5>
            </div>
            <div class="card-body">
              <form method="POST">
                <button type="submit" name="sync_drive" class="btn btn-success">
                  <i class="bi bi-cloud-arrow-down"></i> Sync Recordings
                </button>
              </form>
              <p class="mt-3 text-muted">
                This will fetch all files from the linked Google Drive folder, match phone numbers in filenames, and insert them into the CRM.
              </p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Feedback Modal -->
  <div class="modal fade" id="uploadFeedbackModal" tabindex="-1" aria-labelledby="uploadFeedbackLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title" id="uploadFeedbackLabel">Sync Summary</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="uploadFeedbackBody"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/adminlte.js"></script>

  <?php if ($feedback): ?>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const successCount = <?= json_encode($feedback['successCount']) ?>;
      const errors = <?= json_encode($feedback['errors']) ?>;

      let html = `<p><strong>${successCount}</strong> recording(s) synced successfully.</p>`;
      if (errors.length > 0) {
        html += `<div class="alert alert-danger mt-3"><strong>Errors:</strong><ul>`;
        errors.forEach(err => {
          html += `<li>${err}</li>`;
        });
        html += `</ul></div>`;
      }

      document.getElementById("uploadFeedbackBody").innerHTML = html;
      const feedbackModal = new bootstrap.Modal(document.getElementById('uploadFeedbackModal'));
      feedbackModal.show();
    });
  </script>
  <?php endif; ?>
</body>
</html>
