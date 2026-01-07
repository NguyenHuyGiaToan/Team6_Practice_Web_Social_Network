<?php
    if (isset($_GET['ReportID'])) {
        require_once "../includes/config.php";
        require_once "../includes/database.php";
        $reportId = (int)$_GET['ReportID'];
        $sql = "UPDATE Reports SET Status = 'resolved' WHERE ReportID = $reportId";
        $conn->query($sql);
    }
    header("Location: reports.php");
    exit();
?>