<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = '';

// Fetch user name
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user) {
    $userName = $user['name'];
}

$errors = [];
$success = false;

// Initialize all fields to avoid undefined variable notices
$name = '';
$course = '';
$education = '';
$age = '';
$phone_verified = '';
$phone_number = '';
$whatsapp = '';
$email = '';
$city = '';
$platform = '';
$feedback = '';
$follow_up_date = '';

// Handle lead submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lead'])) {
    $name = $_POST['name'] ?? '';
    $course = $_POST['course'] ?? '';
    $education = $_POST['education'] ?? '';
    $age = $_POST['age'] ?? null;
    $phone_verified = $_POST['phone_verified'] ?? null;
    $phone_number = $_POST['phone_number'] ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';
    $email = $_POST['email'] ?? '';
    $city = $_POST['city'] ?? '';
    $platform = $_POST['platform'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    $follow_up_date = $_POST['follow_up_date'] ?? null;

    if (empty($name) || empty($course) || empty($phone_number)) {
        $errors[] = "Name, Course and Phone Number are required.";
    }

    if (!$errors) {
        // Insert lead
        $stmt = $pdo->prepare("INSERT INTO leads 
            (assigned_to, created_date, name, course, education, age, phone_verified, phone_number, whatsapp, email, city, platform, status, feedback, follow_up_date) 
            VALUES 
            (:assigned_to, CURDATE(), :name, :course, :education, :age, :phone_verified, :phone_number, :whatsapp, :email, :city, :platform, 'new', :feedback, :follow_up_date)");

        $stmt->execute([
            ':assigned_to' => $userId,
            ':name' => $name,
            ':course' => $course,
            ':education' => $education,
            ':age' => $age ?: null,
            ':phone_verified' => $phone_verified ?: null,
            ':phone_number' => $phone_number,
            ':whatsapp' => $whatsapp,
            ':email' => $email,
            ':city' => $city,
            ':platform' => $platform,
            ':feedback' => $feedback,
            ':follow_up_date' => $follow_up_date ?: null
        ]);

        // Get the inserted lead ID
        $leadId = $pdo->lastInsertId();

        // Insert into lead_assignments
        $assignStmt = $pdo->prepare("INSERT INTO lead_assignments (lead_id, user_id) VALUES (:lead_id, :user_id)");
        $assignStmt->execute([
            ':lead_id' => $leadId,
            ':user_id' => $userId
        ]);

        $success = true;

        // Reset fields after successful insert
        $name = $course = $education = $age = $phone_verified = $phone_number = $whatsapp = $email = $city = $platform = $feedback = $follow_up_date = '';
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Sharadha Skill Academy CRM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="preload" href="../css/adminlte.css" as="style" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" crossorigin="anonymous" media="print" onload="this.media='all'" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="../css/adminlte.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css" crossorigin="anonymous" />
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
  <?php include './header.php'; ?>
  <?php include 'sidebar.php'; ?>

  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6"><h3 class="mb-0">Dashboard</h3></div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-end">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Dashboard</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="app-content py-4">
      <div class="container-fluid">

        <?php if ($success): ?>
          <div class="alert alert-success">New lead added successfully!</div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger"><?= implode("<br>", $errors) ?></div>
        <?php endif; ?>

        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Add New Lead</h5>
          </div>
          <div class="card-body">
            <form method="POST" class="row g-3">
              <input type="hidden" name="add_lead" value="1" />
              <div class="col-md-6">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($name) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Course *</label>
                <input type="text" name="course" class="form-control" required value="<?= htmlspecialchars($course) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Education</label>
                <input type="text" name="education" class="form-control" value="<?= htmlspecialchars($education) ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label">Age</label>
                <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($age) ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label">Phone Verified</label>
                <select name="phone_verified" class="form-select">
                  <option value="">Select</option>
                  <option value="Verified" <?= $phone_verified == 'Verified' ? 'selected' : '' ?>>Verified</option>
                  <option value="Not Verified" <?= $phone_verified == 'Not Verified' ? 'selected' : '' ?>>Not Verified</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Follow Up Date</label>
                <input type="date" name="follow_up_date" class="form-control" value="<?= htmlspecialchars($follow_up_date) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone Number *</label>
                <input type="text" name="phone_number" class="form-control" required value="<?= htmlspecialchars($phone_number) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">WhatsApp</label>
                <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($whatsapp) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($city) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Platform</label>
                <input type="text" name="platform" class="form-control" value="<?= htmlspecialchars($platform) ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Feedback</label>
                <textarea name="feedback" rows="3" class="form-control"><?= htmlspecialchars($feedback) ?></textarea>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-success">Save Lead</button>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="../js/adminlte.js"></script>
</body>
</html>
