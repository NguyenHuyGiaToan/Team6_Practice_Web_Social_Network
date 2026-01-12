<?php
// report_action.php
require_once __DIR__ . '../includes/config.php';
require_once __DIR__ . '../includes/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = intval($_POST['post_id'] ?? 0);
    $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');

    if ($post_id > 0 && !empty($reason)) {
        // Chèn yêu cầu báo cáo vào database
        $sql = "INSERT INTO REPORTS (FK_PostID, FK_ReporterID, Reason, Status, ReportedAt) 
                VALUES (?, ?, ?, 'pending', NOW())";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $post_id, $user_id, $reason);
        
        if (mysqli_stmt_execute($stmt)) {
            // Có thể dùng thông báo Toast hoặc Alert sau khi quay về trang chủ
            $_SESSION['msg'] = "Cảm ơn bạn! Báo cáo của bạn đã được gửi tới quản trị viên.";
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra, vui lòng thử lại sau.";
        }
    } else {
        $_SESSION['error'] = "Vui lòng chọn lý do báo cáo.";
    }
}

// Quay lại trang trước đó
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();