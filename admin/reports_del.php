<?php
    // Ensure database connection is available and session/config loaded
    require_once __DIR__ . '/../includes/database.php';
    require_once __DIR__ . '/../includes/config.php';

    if (isset($_GET['ReportID'])) {
        $reportId = (int)$_GET['ReportID'];
        $stmt = $conn->prepare("DELETE FROM Reports WHERE ReportID = ?");
        if ($stmt) {
            $stmt->bind_param('i', $reportId);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: reports.php");
    exit();
?>