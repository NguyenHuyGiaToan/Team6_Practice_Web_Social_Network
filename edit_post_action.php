<?php
// actions/edit_post_action.php
require_once __DIR__ . '../includes/config.php';
require_once __DIR__ . '../includes/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $post_id = intval($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    
    // Kiểm tra quyền sở hữu
    $check_sql = "SELECT FK_UserID FROM POSTS WHERE PostID = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "i", $post_id);
    mysqli_stmt_execute($stmt);
    $post = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($post && $post['FK_UserID'] == $user_id) {
        mysqli_begin_transaction($conn); // Bắt đầu giao dịch để đảm bảo an toàn dữ liệu

        try {
            // 1. Cập nhật nội dung văn bản
            $update_sql = "UPDATE POSTS SET Content = ?, UpdatedAt = NOW() WHERE PostID = ?";
            $up_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($up_stmt, "si", $content, $post_id);
            mysqli_stmt_execute($up_stmt);

            // 2. Xử lý XÓA ẢNH (Nếu trong Modal có chức năng chọn xóa ảnh cũ)
            if (!empty($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $img_id) {
                    $img_id = intval($img_id);
                    // Lấy tên file để xóa trong thư mục
                    $get_img = mysqli_query($conn, "SELECT ImageUrl FROM Post_Images WHERE ImageID = $img_id");
                    if ($img_row = mysqli_fetch_assoc($get_img)) {
                        $path = "C:/wamp64/www/web-social-network/uploads/posts/" . $img_row['ImageUrl'];
                        if (file_exists($path)) unlink($path);
                    }
                    mysqli_query($conn, "DELETE FROM Post_Images WHERE ImageID = $img_id");
                }
            }

            // 3. Xử lý THÊM ẢNH MỚI
            if (!empty($_FILES['new_images']['name'][0])) {
                $upload_dir = "C:/wamp64/www/web-social-network/uploads/posts/";
                foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
                    $file_name = time() . "_" . $_FILES['new_images']['name'][$key];
                    if (move_uploaded_file($tmp_name, $upload_dir . $file_name)) {
                        $img_sql = "INSERT INTO Post_Images (FK_PostID, ImageUrl) VALUES (?, ?)";
                        $img_stmt = mysqli_prepare($conn, $img_sql);
                        mysqli_stmt_bind_param($img_stmt, "is", $post_id, $file_name);
                        mysqli_stmt_execute($img_stmt);
                    }
                }
            }

            mysqli_commit($conn);
            $_SESSION['msg'] = "Cập nhật bài viết thành công!";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = "Lỗi khi cập nhật: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Bạn không có quyền chỉnh sửa.";
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();