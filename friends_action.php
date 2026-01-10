<?php
// Tắt hiển thị lỗi trực tiếp ra màn hình để tránh làm hỏng chuỗi JSON
error_reporting(0);
ini_set('display_errors', 0);

// Khởi động bộ đệm đầu ra
ob_start();

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

ob_clean();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Hết phiên làm việc, vui lòng đăng nhập lại.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$target_id = intval($_POST['target_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($target_id <= 0 || $target_id == $user_id) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit();
}

$response = ['success' => false, 'message' => 'Lỗi chưa xác định'];

switch ($action) {
    case 'send_request':
        $check = mysqli_query($conn, "SELECT * FROM FOLLOWS WHERE FK_FollowerID = $user_id AND FK_FollowingID = $target_id");
        if (mysqli_num_rows($check) == 0) {
            $stmt = mysqli_prepare($conn, "INSERT INTO FOLLOWS (FK_FollowerID, FK_FollowingID, FollowedAt, Status) VALUES (?, ?, NOW(), 'pending')");
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $target_id);
            if (mysqli_stmt_execute($stmt)) {
                $response = ['success' => true, 'text' => 'Đã gửi yêu cầu'];
            } else {
                $response = ['success' => false, 'message' => 'Lỗi DB: ' . mysqli_error($conn)];
            }
        } else {
            $response = ['success' => false, 'message' => 'Bạn đã gửi yêu cầu rồi.'];
        }
        break;

    case 'accept_request': 
        // Target_id: Người đã nhấn follow bạn (follower)
        // User_id: Là bạn, người đang nhấn chấp nhận (following)
        $stmt = mysqli_prepare($conn, "UPDATE FOLLOWS SET Status = 'accepted' WHERE FK_FollowerID = ? AND FK_FollowingID = ?");
        mysqli_stmt_bind_param($stmt, "ii", $target_id, $user_id); 
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Đã đồng ý']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật database']);
        }
        break;

    case 'decline_request':
        $stmt = mysqli_prepare($conn, "DELETE FROM FOLLOWS WHERE FK_FollowerID = ? AND FK_FollowingID = ? AND Status = 'pending'");
        mysqli_stmt_bind_param($stmt, "ii", $target_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $response = ['success' => true];
        }
        break;

    case 'unfollow':
        $stmt = mysqli_prepare($conn, "DELETE FROM FOLLOWS WHERE FK_FollowerID = ? AND FK_FollowingID = ?");
        mysqli_stmt_bind_param($stmt, "ii", $user_id, $target_id);
        if (mysqli_stmt_execute($stmt)) {
            $response = ['success' => true];
        }
        break;
}

echo json_encode($response);
exit();