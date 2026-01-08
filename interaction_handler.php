<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện hành động này.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Thiếu hành động.']);
    exit();
}

switch ($action) {
    // ================== LIKE / UNLIKE BÀI VIẾT ==================
    case 'toggle_like':
        $post_id = intval($_POST['post_id'] ?? 0);
        if ($post_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID bài viết không hợp lệ.']);
            exit();
        }

        // Kiểm tra bài viết có tồn tại và active không
        $check_post = mysqli_prepare($conn, "SELECT PostID FROM POSTS WHERE PostID = ? AND Status = 'active'");
        mysqli_stmt_bind_param($check_post, "i", $post_id);
        mysqli_stmt_execute($check_post);
        mysqli_stmt_store_result($check_post);
        if (mysqli_stmt_num_rows($check_post) == 0) {
            echo json_encode(['success' => false, 'message' => 'Bài viết không tồn tại.']);
            exit();
        }

        // Kiểm tra đã like chưa
        $check_like = mysqli_prepare($conn, "SELECT FK_UserID FROM LIKES WHERE FK_PostID = ? AND FK_UserID = ?");
        mysqli_stmt_bind_param($check_like, "ii", $post_id, $user_id);
        mysqli_stmt_execute($check_like);
        mysqli_stmt_store_result($check_like);

        if (mysqli_stmt_num_rows($check_like) > 0) {
            // Đã like → Unlike
            $unlike = mysqli_prepare($conn, "DELETE FROM LIKES WHERE FK_PostID = ? AND FK_UserID = ?");
            mysqli_stmt_bind_param($unlike, "ii", $post_id, $user_id);
            mysqli_stmt_execute($unlike);

            // Giảm LikeCount trong bảng POSTS
            mysqli_query($conn, "UPDATE POSTS SET LikeCount = LikeCount - 1 WHERE PostID = $post_id");

            $new_count = max(0, getPostLikeCount($post_id, $conn));
            echo json_encode([
                'success' => true,
                'liked' => false,
                'like_count' => $new_count,
                'message' => 'Đã bỏ thích'
            ]);
        } else {
            // Chưa like → Like
            $like = mysqli_prepare($conn, "INSERT INTO LIKES (FK_UserID, FK_PostID, CreatedAt) VALUES (?, ?, NOW())");
            mysqli_stmt_bind_param($like, "ii", $user_id, $post_id);
            mysqli_stmt_execute($like);

            // Tăng LikeCount trong bảng POSTS
            mysqli_query($conn, "UPDATE POSTS SET LikeCount = LikeCount + 1 WHERE PostID = $post_id");

            $new_count = getPostLikeCount($post_id, $conn);
            echo json_encode([
                'success' => true,
                'liked' => true,
                'like_count' => $new_count,
                'message' => 'Đã thích'
            ]);
        }
        break;

    // ================== THÊM BÌNH LUẬN ==================
    case 'add_comment':
        $post_id = intval($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($post_id <= 0 || empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
            exit();
        }

        // Kiểm tra bài viết tồn tại
        $check = mysqli_prepare($conn, "SELECT PostID FROM POSTS WHERE PostID = ? AND Status = 'active'");
        mysqli_stmt_bind_param($check, "i", $post_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) == 0) {
            echo json_encode(['success' => false, 'message' => 'Bài viết không tồn tại.']);
            exit();
        }

        // Thêm bình luận
        $insert = mysqli_prepare($conn, "
            INSERT INTO COMMENTS (FK_PostID, FK_UserID, Content, Status, CreatedAt, UpdatedAt) 
            VALUES (?, ?, ?, 'active', NOW(), NOW())
        ");
        mysqli_stmt_bind_param($insert, "iis", $post_id, $user_id, $content);
        $success = mysqli_stmt_execute($insert);

        if ($success) {
            $comment_id = mysqli_insert_id($conn);

            // Tăng CommentCount
            mysqli_query($conn, "UPDATE POSTS SET CommentCount = CommentCount + 1 WHERE PostID = $post_id");

            // Lấy thông tin bình luận vừa thêm để trả về
            $new_comment = getCommentById($comment_id, $conn, $user_id);

            echo json_encode([
                'success' => true,
                'comment' => $new_comment,
                'message' => 'Bình luận thành công'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm bình luận.']);
        }
        break;

    // ================== SỬA BÌNH LUẬN ==================
    case 'edit_comment':
        $comment_id = intval($_POST['comment_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($comment_id <= 0 || empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
            exit();
        }

        // Kiểm tra quyền sở hữu
        $check = mysqli_prepare($conn, "SELECT CommentID, FK_PostID FROM COMMENTS WHERE CommentID = ? AND FK_UserID = ? AND Status != 'deleted'");
        mysqli_stmt_bind_param($check, "ii", $comment_id, $user_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) == 0) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền sửa bình luận này.']);
            exit();
        }

        mysqli_stmt_bind_result($check, $cid, $post_id);
        mysqli_stmt_fetch($check);

        // Cập nhật nội dung
        $update = mysqli_prepare($conn, "UPDATE COMMENTS SET Content = ?, UpdatedAt = NOW() WHERE CommentID = ?");
        mysqli_stmt_bind_param($update, "si", $content, $comment_id);
        $success = mysqli_stmt_execute($update);

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Đã cập nhật bình luận' : 'Lỗi khi cập nhật'
        ]);
        break;

    // ================== XÓA BÌNH LUẬN ==================
    case 'delete_comment':
        $comment_id = intval($_POST['comment_id'] ?? 0);

        if ($comment_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID bình luận không hợp lệ.']);
            exit();
        }

        // Kiểm tra quyền sở hữu
        $check = mysqli_prepare($conn, "SELECT FK_PostID FROM COMMENTS WHERE CommentID = ? AND FK_UserID = ? AND Status != 'deleted'");
        mysqli_stmt_bind_param($check, "ii", $comment_id, $user_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) == 0) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền xóa bình luận này.']);
            exit();
        }

        mysqli_stmt_bind_result($check, $post_id);
        mysqli_stmt_fetch($check);

        // Đánh dấu xóa (soft delete)
        $delete = mysqli_prepare($conn, "UPDATE COMMENTS SET Status = 'deleted', UpdatedAt = NOW() WHERE CommentID = ?");
        mysqli_stmt_bind_param($delete, "i", $comment_id);
        $success = mysqli_stmt_execute($delete);

        if ($success) {
            // Giảm CommentCount
            mysqli_query($conn, "UPDATE POSTS SET CommentCount = CommentCount - 1 WHERE PostID = $post_id");
        }

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Đã xóa bình luận' : 'Lỗi khi xóa'
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        exit();
}

// ================== HÀM HỖ TRỢ ==================
function getPostLikeCount($post_id, $conn) {
    $result = mysqli_query($conn, "SELECT LikeCount FROM POSTS WHERE PostID = " . intval($post_id));
    $row = mysqli_fetch_assoc($result);
    return $row['LikeCount'] ?? 0;
}

function getCommentById($comment_id, $conn, $current_user_id) {
    $query = "
        SELECT c.CommentID, c.Content, c.CreatedAt, c.UpdatedAt,
               u.UserID, u.FullName, u.Avatar,
               (c.FK_UserID = ?) AS is_owner
        FROM COMMENTS c
        JOIN Users u ON c.FK_UserID = u.UserID
        WHERE c.CommentID = ? AND c.Status = 'active'
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $current_user_id, $comment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $comment = mysqli_fetch_assoc($result);

    if ($comment) {
        $comment['avatar_url'] = !empty($comment['Avatar'])
            ? 'uploads/avatars/' . htmlspecialchars($comment['Avatar'])
            : 'https://ui-avatars.com/api/?name=' . urlencode($comment['FullName']) . '&background=8B1E29&color=fff';
        $comment['time_ago'] = timeAgo($comment['CreatedAt']);
    }
    return $comment;
}
?>