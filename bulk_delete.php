<?php
require 'config.php';

if (isset($_GET['ids'])) {
    $ids = explode(',', $_GET['ids']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $pdo->beginTransaction();

    try {
        // Optional: delete from call_logs if applicable
        $pdo->prepare("DELETE FROM call_logs WHERE lead_id IN ($placeholders)")->execute($ids);

        // Delete from lead_assignments
        $pdo->prepare("DELETE FROM lead_assignments WHERE lead_id IN ($placeholders)")->execute($ids);

        // Delete from leads
        $pdo->prepare("DELETE FROM leads WHERE id IN ($placeholders)")->execute($ids);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
        exit;
    }
}

header('Location: leads.php');
exit;
