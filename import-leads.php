<?php
include 'config.php';
session_start();


if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: index.php');
    exit();
}

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userName = $user['name'];
    }
}

use PhpOffice\PhpSpreadsheet\IOFactory;
require 'vendor/autoload.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];
    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 2) {
            throw new Exception("The file seems empty or missing data.");
        }

        // Normalize header
        $header = array_map(function ($h) {
            return strtolower(trim($h));
        }, $rows[0]);

        // Map Excel column names to DB columns
        $mapping = [
            'created date' => 'created_date',
            'platform' => 'platform',
            'educational qualification' => 'education',
            'age' => 'age',
            'full name' => 'name',
            'course' => 'course',
            'phone' => 'phone_number',
            'whatsapp number' => 'whatsapp',
            'email' => 'email',
            'city' => 'city',
            'phone number verified' => 'phone_verified',
            'status' => 'status',
            'feedback' => 'feedback'
        ];

        // Find column indexes if they exist in Excel
        $indexes = [];
        foreach ($mapping as $excelCol => $dbCol) {
            $i = array_search(strtolower($excelCol), $header);
            $indexes[$dbCol] = ($i !== false) ? $i : null; // if column missing, mark as null
        }

        $inserted = 0;

        foreach ($rows as $index => $row) {
            if ($index == 0)
                continue; // skip header

            // For each field, check if column exists, else set null/default
            $created_date = $indexes['created_date'] !== null ? trim($row[$indexes['created_date']]) : null;
            if ($created_date) {
                if (is_numeric($created_date)) {
                    $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($created_date);
                    $created_date = $excelDate->format('Y-m-d');
                } else {
                    $created_date = date('Y-m-d', strtotime($created_date));
                }
            } else {
                $created_date = null;
            }

            $platform = $indexes['platform'] !== null ? trim($row[$indexes['platform']]) : null;
            $education = $indexes['education'] !== null ? trim($row[$indexes['education']]) : null;
            $age = ($indexes['age'] !== null && is_numeric(trim($row[$indexes['age']]))) ? (int) trim($row[$indexes['age']]) : null;
            $name = $indexes['name'] !== null ? trim($row[$indexes['name']]) : null;
            $course = $indexes['course'] !== null ? trim($row[$indexes['course']]) : null;
            $phone_number = $indexes['phone_number'] !== null ? (string) trim((string) $row[$indexes['phone_number']]) : null;
            $whatsapp = $indexes['whatsapp'] !== null ? trim($row[$indexes['whatsapp']]) : null;
            $email = $indexes['email'] !== null ? trim($row[$indexes['email']]) : null;
            $city = $indexes['city'] !== null ? trim($row[$indexes['city']]) : null;
            $phone_verified = $indexes['phone_verified'] !== null ? trim($row[$indexes['phone_verified']]) : null;
            $status = $indexes['status'] !== null ? strtolower(trim($row[$indexes['status']])) : 'new';
            $feedback = $indexes['feedback'] !== null ? trim($row[$indexes['feedback']]) : null;

            // Sanitize values
            $allowed_status = ['new', 'in_progress', 'interested', 'not_interested', 'follow_up', 'converted', 'closed'];
            if (!in_array($status, $allowed_status)) {
                $status = 'new';
            }
            $phone_verified = in_array(strtolower($phone_verified), ['verified', 'not verified']) ? ucfirst(strtolower($phone_verified)) : null;

            // Fill missing fields
            $assigned_to = '';
            $follow_up_date = null;
            $created_at = date('Y-m-d H:i:s');
            $updated_at = $created_at;

            // Skip empty leads
            if (empty($name) && empty($phone_number) && empty($email)) {
                continue;
            }

            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO leads 
                (assigned_to, created_date, name, course, education, age, phone_verified, phone_number, whatsapp, email, city, platform, status, feedback, follow_up_date, created_at, updated_at)
                VALUES 
                (:assigned_to, :created_date, :name, :course, :education, :age, :phone_verified, :phone_number, :whatsapp, :email, :city, :platform, :status, :feedback, :follow_up_date, :created_at, :updated_at)
            ");
            $stmt->execute([
                ':assigned_to' => $assigned_to,
                ':created_date' => $created_date,
                ':name' => $name,
                ':course' => $course,
                ':education' => $education,
                ':age' => $age,
                ':phone_verified' => $phone_verified,
                ':phone_number' => $phone_number,
                ':whatsapp' => $whatsapp,
                ':email' => $email,
                ':city' => $city,
                ':platform' => $platform,
                ':status' => $status,
                ':feedback' => $feedback,
                ':follow_up_date' => $follow_up_date,
                ':created_at' => $created_at,
                ':updated_at' => $updated_at
            ]);

            $inserted++;
        }

        $message = "Imported $inserted leads successfully!";
    } catch (Exception $e) {
        $message = "Import failed: " . $e->getMessage();
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Sharadha Skill Academy CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <meta name="color-scheme" content="light dark" />
    <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
    <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
    <meta name="title" content="AdminLTE | Dashboard v2" />
    <meta name="author" content="ColorlibHQ" />
    <meta name="description"
        content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS. Fully accessible with WCAG 2.1 AA compliance." />
    <meta name="keywords"
        content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard, accessible admin panel, WCAG compliant" />
    <meta name="supported-color-schemes" content="light dark" />
    <link rel="preload" href="./css/adminlte.css" as="style" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
        integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" media="print"
        onload="this.media='all'" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        crossorigin="anonymous" />
    <link rel="stylesheet" href="./css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
        integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0=" crossorigin="anonymous" />
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
    <div class="app-wrapper">
        <?php include 'header.php'; ?>
        <?php include 'sidebar.php'; ?>

        <main class="app-main">
            <div class="container py-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title mb-4">Import Leads</h4>

                        <?php if ($message): ?>
                            <div class="alert alert-info"><?= htmlspecialchars($message); ?></div>
                        <?php endif; ?>

                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="excel_file" class="form-label">Choose Excel File</label>
                                <input type="file" class="form-control" id="excel_file" name="excel_file"
                                    accept=".xlsx,.xls" required>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Import
                            </button>
                        </form>

                        <div class="mt-3">
                            <a href="New Paid Course Leads.xlsx" class="btn btn-outline-secondary" download>
                                <i class="bi bi-download"></i> Download Sample File
                            </a>
                        </div>


                        <div class="mt-4">
                            <small class="text-muted">
                                Excel file should have headers:<br>
                                <strong>Created Date, Platform, Course, Educational Qualification, Age, Full Name,
                                    Phone,
                                    Whatsapp Number, Email, City, Phone Number Verified, Status, Feedback</strong>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include './footer.php'; ?>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"
    crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
    crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="./js/adminlte.js"></script>

</html>