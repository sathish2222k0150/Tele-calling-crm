<?php
$pageTitle = "Register - TeleCRM";
require 'config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}
$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $phone = trim($_POST["phone"]);
    $role = $_POST["role"];

    if ($name && $email && $password && $role && $phone) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email format.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $status = 'active';

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $message = "Email already registered.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, phone_number, role, status) VALUES (?, ?, ?, ?, ?, ?)");
                try {
                    $stmt->execute([$name, $email, $password_hash, $phone, $role, $status]);
                    $message = "✅ Registration successful as <strong>" . htmlspecialchars($role) . "</strong>! You can now <a href='index.php' class='text-primary'>login</a>.";
                } catch (PDOException $e) {
                    $message = "Error: " . htmlspecialchars($e->getMessage());
                }
            }
        }
    } else {
        $message = "All fields are required.";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Sharadha Skill Academy CRM</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="./css/adminlte.css" />
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
  <div class="register-box">
    <div class="register-logo text-center mb-3">
      <a href="#"><b>Sharadha Skill Academy</b></a>
    </div>
    <div class="card">
      <div class="card-body register-card-body">
        <p class="register-box-msg">Register a new user</p>

        <?php if (!empty($message)): ?>
          <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="input-group mb-3">
            <input type="text" name="name" class="form-control" placeholder="Full Name" required />
            <div class="input-group-text"><span class="bi bi-person"></span></div>
          </div>
          <div class="input-group mb-3">
            <input type="email" name="email" class="form-control" placeholder="Email" required />
            <div class="input-group-text"><span class="bi bi-envelope"></span></div>
          </div>
          <div class="input-group mb-3">
            <input type="text" name="phone" class="form-control" placeholder="Phone Number" required />
            <div class="input-group-text"><span class="bi bi-telephone"></span></div>
          </div>
          <div class="input-group mb-3">
            <input type="password" name="password" class="form-control" placeholder="Password" required />
            <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
          </div>

          <!-- Role Selection Dropdown -->
          <div class="input-group mb-3">
            <select name="role" class="form-select" required>
              <option value="">Select Role</option>
              <option value="admin">Admin</option>
              <option value="telecaller">Telecaller</option>
              <option value="staff">Staff</option>
            </select>
            <div class="input-group-text"><span class="bi bi-person-badge"></span></div>
          </div>

          <div class="row">
            <div class="col-4">
              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Register</button>
              </div>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="./js/adminlte.js"></script>
</body>
</html>
