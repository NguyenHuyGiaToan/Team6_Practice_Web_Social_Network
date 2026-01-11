<?php
// actions/delete_post_action.php
require_once __DIR__ . '../includes/config.php';
require_once __DIR__ . '../includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($post_id > 0) {
    // Kiểm tra quyền: Chỉ chủ bài viết mới được xóa
    $check_sql = "SELECT FK_UserID FROM POSTS WHERE PostID = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $post = mysqli_fetch_assoc($result);

    if ($post && $post['FK_UserID'] == $user_id) {
        // Thực hiện xóa mềm bằng cách cập nhật Status
        $delete_sql = "UPDATE POSTS SET Status = 'deleted' WHERE PostID = ?";
        $del_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($del_stmt, "i", $post_id);
        
        if (mysqli_stmt_execute($del_stmt)) {
            $_SESSION['msg'] = "Bài viết đã được xóa.";
        } else {
            $_SESSION['error'] = "Không thể xóa bài viết lúc này.";
        }
    } else {
        $_SESSION['error'] = "Bạn không có quyền xóa bài viết này.";
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();