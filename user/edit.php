<?php
require '../config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    // Not logged in, redirect to login page
    header('Location: ../index.php');
    exit();
}
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo "Invalid ID";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
$stmt->execute([$id]);
$lead = $stmt->fetch();

if (!$lead) {
    echo "Lead not found.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $course = $_POST['course'];
    $education = $_POST['education'];
    $age = $_POST['age'];
    $phone_number = $_POST['phone_number'];
    $whatsapp = $_POST['whatsapp'];
    $email = $_POST['email'];
    $city = $_POST['city'];
    $platform = $_POST['platform'];
    $status = $_POST['status'];
    $feedback = $_POST['feedback'];
    $follow_up_date = $_POST['follow_up_date'];

    if ($status === 'follow_up') {
        if (empty($follow_up_date)) {
            $error = "Follow up date is required when status is 'Follow Up'.";
        } elseif ($follow_up_date === $lead['follow_up_date']) {
            $error = "Please change the follow up date before saving.";
        }
    }

    if (!isset($error)) {
        $sql = "UPDATE leads SET 
                    name = ?, course = ?, education = ?, age = ?, phone_number = ?, whatsapp = ?, email = ?, 
                    city = ?, platform = ?, status = ?, feedback = ?, follow_up_date = ?, updated_at = NOW()
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $name, $course, $education, $age, $phone_number, $whatsapp, $email,
            $city, $platform, $status, $feedback, $follow_up_date, $id
        ]);
        header("Location: leads.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Sharadha Skill Academy CRM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" />
    <link rel="stylesheet" href="../css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
<div class="app-wrapper">
    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="container mt-4">
        <h3>Edit Lead</h3>
        <?php if (!empty($error)): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" id="leadForm" class="row g-3">
            <!-- Row 1 -->
            <div class="col-md-6">
                <label class="form-label">Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($lead['name']) ?>" class="form-control" disabled required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Course</label>
                <input type="text" name="course" value="<?= htmlspecialchars($lead['course']) ?>" class="form-control" disabled required>
            </div>

            <!-- Row 2 -->
            <div class="col-md-6">
                <label class="form-label">Education</label>
                <input type="text" name="education" value="<?= htmlspecialchars($lead['education']) ?>" class="form-control" disabled>
            </div>
            <div class="col-md-2">
                <label class="form-label">Age</label>
                <input type="number" name="age" value="<?= htmlspecialchars($lead['age']) ?>" class="form-control" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label">City</label>
                <input type="text" name="city" value="<?= htmlspecialchars($lead['city']) ?>" class="form-control" disabled>
            </div>
            <div class="col-md-4">
                <label class="form-label">Phone Number</label>
                <div class="input-group">
                    <input type="text" name="phone_number" value="<?= htmlspecialchars($lead['phone_number']) ?>" class="form-control" disabled>
                    <button type="button" class="btn btn-outline-primary call-btn" data-id="<?= $lead['id'] ?>">
                        <i class="bi bi-telephone-fill"></i>
                    </button>
                </div>
            </div>

            <!-- Row 3 -->
            <div class="col-md-4">
                <label class="form-label">WhatsApp</label>
                <div class="input-group">
                    <input type="text" name="whatsapp" value="<?= htmlspecialchars($lead['whatsapp']) ?>" class="form-control" disabled>
                    <a href="https://wa.me/91<?= htmlspecialchars($lead['whatsapp']) ?>" target="_blank"
                       class="btn btn-outline-success whatsapp-btn" data-id="<?= $lead['id'] ?>">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($lead['email']) ?>" class="form-control" disabled>
            </div>
            <!-- Row 4 -->
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" disabled>
                    <?php
                    $statuses = ['new', 'in_progress', 'interested', 'not_interested', 'follow_up', 'converted', 'closed'];
                    foreach ($statuses as $status) {
                        $selected = $lead['status'] === $status ? 'selected' : '';
                        echo "<option value=\"$status\" $selected>" . ucfirst(str_replace('_', ' ', $status)) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Follow Up Date</label>
                <input type="date" name="follow_up_date" value="<?= htmlspecialchars($lead['follow_up_date']) ?>" class="form-control" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label">Platform</label>
                <input type="text" name="platform" value="<?= htmlspecialchars($lead['platform']) ?>" class="form-control" disabled>
            </div>
            <!-- Row 5 -->
            <div class="col-12">
                <label class="form-label">Feedback</label>
                <textarea name="feedback" class="form-control" rows="3" disabled><?= htmlspecialchars($lead['feedback']) ?></textarea>
            </div>

            <!-- Buttons -->
            <div class="col-12">
                <button type="button" id="editBtn" class="btn btn-primary">Edit</button>
                <button type="submit" id="saveBtn" class="btn btn-success d-none">Save</button>
                <a href="leads.php" id="cancelBtn" class="btn btn-secondary d-none">Cancel</a>
            </div>
        </form>
    </div>

</div>

<script>
document.getElementById('editBtn').addEventListener('click', function () {
    const form = document.getElementById('leadForm');
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => input.removeAttribute('disabled'));
    document.getElementById('editBtn').classList.add('d-none');
    document.getElementById('saveBtn').classList.remove('d-none');
    document.getElementById('cancelBtn').classList.remove('d-none');
});

document.querySelectorAll('.call-btn').forEach(button => {
    button.addEventListener('click', function () {
        const leadId = this.getAttribute('data-id');
        const number = this.closest('.input-group').querySelector('input').value;

        fetch('log_interaction.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `lead_id=${leadId}&type=call`
        }).then(res => res.text()).then(console.log);

        if (number) {
            window.location.href = `tel:${number}`;
        }
    });
});

document.querySelectorAll('.whatsapp-btn').forEach(button => {
    button.addEventListener('click', function () {
        const leadId = this.getAttribute('data-id');
        fetch('log_interaction.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `lead_id=${leadId}&type=whatsapp`
        }).then(res => res.text()).then(console.log);
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/adminlte.js"></script>
</body>
</html>
