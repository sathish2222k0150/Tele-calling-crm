<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['ids']) && is_array($_POST['ids'])) {
        $ids = $_POST['ids'];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM call_logs WHERE id IN ($placeholders)");
        if ($stmt->execute($ids)) {
            echo json_encode(['success' => true, 'message' => 'Deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No IDs received.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
