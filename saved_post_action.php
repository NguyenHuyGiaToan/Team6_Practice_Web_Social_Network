<?php
// saved_post_action.php
require_once __DIR__ . '../includes/config.php';
require_once __DIR__ . '../includes/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($post_id > 0) {
    // 1. Kiểm tra bài viết này đã được user lưu chưa
    $check_sql = "SELECT * FROM SAVED_POSTS WHERE FK_UserID = ? AND FK_PostID = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        // Nếu đã tồn tại -> Bỏ lưu (Xóa)
        $delete_sql = "DELETE FROM SAVED_POSTS WHERE FK_UserID = ? AND FK_PostID = ?";
        $del_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($del_stmt, "ii", $user_id, $post_id);
        mysqli_stmt_execute($del_stmt);
        $_SESSION['msg'] = "Đã bỏ lưu bài viết.";
    } else {
        // Nếu chưa tồn tại -> Lưu bài viết
        $save_sql = "INSERT INTO SAVED_POSTS (FK_UserID, FK_PostID, SavedAt) VALUES (?, ?, NOW())";
        $save_stmt = mysqli_prepare($conn, $save_sql);
        mysqli_stmt_bind_param($save_stmt, "ii", $user_id, $post_id);
        mysqli_stmt_execute($save_stmt);
        $_SESSION['msg'] = "Đã lưu bài viết thành công.";
    }
}

// Quay lại trang trước đó
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();