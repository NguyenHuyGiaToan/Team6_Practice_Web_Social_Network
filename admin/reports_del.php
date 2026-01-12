<?php
    // Ensure database connection is available and session/config loaded
    require_once __DIR__ . '/../includes/database.php';
    require_once __DIR__ . '/../includes/config.php';

    if (isset($_GET['ReportID'])) {
        $reportId = (int)$_GET['ReportID'];
        $sql = "UPDATE Reports SET Status = 'rejected' WHERE ReportID = $reportId";
        $conn->query($sql);
    }

    header("Location: reports.php");
    exit();
?>