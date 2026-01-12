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

    case 'get_comments':
        $post_id = intval($_GET['post_id'] ?? 0);
        if ($post_id <= 0) {
            echo json_encode(['success' => false, 'comments' => []]);
            exit;
        }

        // Truy vấn lấy bình luận + thông tin người bình luận
        $sql = "SELECT c.*, u.FullName, u.Avatar 
                FROM COMMENTS c 
                JOIN Users u ON c.FK_UserID = u.UserID 
                WHERE c.FK_PostID = ? AND c.Status = 'active' 
                ORDER BY c.CreatedAt ASC"; // ASC: Cũ nhất hiện trước, Mới nhất hiện sau

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $post_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $comments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // Xử lý Avatar
            $row['avatar_url'] = !empty($row['Avatar']) 
                ? 'uploads/avatars/' . htmlspecialchars($row['Avatar']) 
                : 'https://ui-avatars.com/api/?name=' . urlencode($row['FullName']) . '&background=8B1E29&color=fff';
            
            // Xử lý thời gian (Hàm timeAgo phải có trong functions.php)
            $row['time_ago'] = function_exists('timeAgo') ? timeAgo($row['CreatedAt']) : $row['CreatedAt'];
            
            // Kiểm tra quyền sở hữu (để hiện nút Sửa/Xóa)
            $row['is_owner'] = ($row['FK_UserID'] == $user_id);

            $comments[] = $row;
        }

        echo json_encode(['success' => true, 'comments' => $comments]);
        break;
    // ================== LIKE / UNLIKE BÀI VIẾT ==================
    case 'toggle_like':
        $post_id = intval($_POST['post_id'] ?? 0);
        if ($post_id <= 0) exit(json_encode(['success' => false]));

        // 1. Kiểm tra xem user đã like bài này chưa
        $check = mysqli_prepare($conn, "SELECT 1 FROM LIKES WHERE FK_PostID = ? AND FK_UserID = ?");
        mysqli_stmt_bind_param($check, "ii", $post_id, $user_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        $is_liked = (mysqli_stmt_num_rows($check) > 0);
        mysqli_stmt_close($check);

        if ($is_liked) {
            // --- UNLIKE (Xóa like) ---
            $stmt = mysqli_prepare($conn, "DELETE FROM LIKES WHERE FK_PostID = ? AND FK_UserID = ?");
            mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
            mysqli_stmt_execute($stmt);
            $liked_status = false;
        } else {
            // --- LIKE (Thêm like) ---
            // Dùng INSERT IGNORE: Nếu lỡ mạng lag gửi 2 lần thì DB tự chặn, không báo lỗi
            $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO LIKES (FK_UserID, FK_PostID, CreatedAt) VALUES (?, ?, NOW())");
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $post_id);
            mysqli_stmt_execute($stmt);
            
            // Gửi thông báo (chỉ gửi nếu like thành công)
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $owner_res = mysqli_query($conn, "SELECT FK_UserID FROM POSTS WHERE PostID = $post_id");
                if ($owner = mysqli_fetch_assoc($owner_res)) {
                    if ($owner['FK_UserID'] != $user_id && function_exists('createNotification')) {
                        createNotification($conn, $owner['FK_UserID'], $user_id, 'Like', $post_id);
                    }
                }
            }
            $liked_status = true;
        }

        // --- [QUAN TRỌNG NHẤT] ĐẾM LẠI SỐ LIKE TỪ DATABASE ---
        // Không dùng cộng trừ, mà đếm trực tiếp số dòng trong bảng LIKES
        $count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM LIKES WHERE FK_PostID = $post_id");
        $real_count = mysqli_fetch_assoc($count_res)['total'];

        // Cập nhật con số chuẩn xác này vào bảng POSTS
        mysqli_query($conn, "UPDATE POSTS SET LikeCount = $real_count WHERE PostID = $post_id");
        
        echo json_encode([
            'success' => true,
            'liked' => $liked_status,
            'like_count' => $real_count, // Trả về con số chính xác tuyệt đối
            'message' => $liked_status ? 'Đã thích' : 'Đã bỏ thích'
        ]);
        break;
    // ================== THÊM BÌNH LUẬN ==================
    case 'add_comment':
        $post_id = intval($_POST['post_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($post_id <= 0 || empty($content)) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
            exit();
        }

        // --- 1. CHỐNG SPAM: Kiểm tra xem vừa bình luận câu y hệt chưa ---
        // (Trong vòng 10 giây)
        $check_dup = mysqli_prepare($conn, "SELECT 1 FROM COMMENTS WHERE FK_PostID = ? AND FK_UserID = ? AND Content = ? AND CreatedAt > NOW() - INTERVAL 10 SECOND");
        mysqli_stmt_bind_param($check_dup, "iis", $post_id, $user_id, $content);
        mysqli_stmt_execute($check_dup);
        mysqli_stmt_store_result($check_dup);
        
        if (mysqli_stmt_num_rows($check_dup) > 0) {
            // Nếu tìm thấy bình luận trùng trong 10s vừa qua -> Chặn luôn
            echo json_encode(['success' => false, 'message' => 'Bạn bình luận quá nhanh, vui lòng đợi giây lát!']);
            exit();
        }
        mysqli_stmt_close($check_dup);

        // --- 2. Kiểm tra bài viết tồn tại & lấy chủ bài viết ---
        $check_stmt = mysqli_prepare($conn, "SELECT FK_UserID FROM POSTS WHERE PostID = ? AND Status = 'active'");
        mysqli_stmt_bind_param($check_stmt, "i", $post_id);
        mysqli_stmt_execute($check_stmt);
        $check_res = mysqli_stmt_get_result($check_stmt);
        $post_data = mysqli_fetch_assoc($check_res);

        if (!$post_data) {
            echo json_encode(['success' => false, 'message' => 'Bài viết không tồn tại.']);
            exit();
        }
        $post_owner_id = $post_data['FK_UserID'];

        // --- 3. Thêm bình luận ---
        $insert = mysqli_prepare($conn, "INSERT INTO COMMENTS (FK_PostID, FK_UserID, Content, Status, CreatedAt, UpdatedAt) VALUES (?, ?, ?, 'active', NOW(), NOW())");
        mysqli_stmt_bind_param($insert, "iis", $post_id, $user_id, $content);
        $success = mysqli_stmt_execute($insert);

        if ($success) {
            $comment_id = mysqli_insert_id($conn);
            
            // --- 4. [QUAN TRỌNG] Đếm lại số bình luận thực tế ---
            // Đảm bảo số hiển thị luôn đúng với số dòng trong Database
            $count_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM COMMENTS WHERE FK_PostID = $post_id AND Status = 'active'");
            $real_count = mysqli_fetch_assoc($count_res)['total'];
            
            mysqli_query($conn, "UPDATE POSTS SET CommentCount = $real_count WHERE PostID = $post_id");

            // Tạo thông báo
            if ($post_owner_id != $user_id && function_exists('createNotification')) {
                createNotification($conn, $post_owner_id, $user_id, 'Comment', $post_id);
            }

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