<?php
// 1. Cấu hình URL cơ sở (Base URL)
// đường dẫn khi include file hoặc làm link kết nối
define('BASE_URL', 'http://localhost/web-social-network/'); 

// 2. Cấu hình thư mục Upload
// nơi chính xác để lưu ảnh
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// 3. Thiết lập Múi giờ hệ thống
date_default_timezone_set('Asia/Ho_Chi_Minh');

// 4. Khởi tạo Session (Bắt buộc cho mọi trang)
// Giúp quản lý đăng nhập và kiểm tra quyền đăng bài
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 5. Bật báo lỗi (Chỉ dùng khi đang phát triển - Development)
// debug khi code bị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 6. XỬ LÝ TỰ ĐỘNG ĐĂNG NHẬP TỪ COOKIE
// Chỉ thực hiện nếu chưa đăng nhập và có cookie remember
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user_id']) && isset($_COOKIE['remember_token'])) {
    
    require_once __DIR__ . '/database.php';
    
    $cookie_user_id = (int)$_COOKIE['remember_user_id'];
    $cookie_token = $_COOKIE['remember_token'];
    
    // Kiểm tra token trong database
    $stmt = $conn->prepare("SELECT * FROM Users WHERE UserID = ? AND RememberToken = ? AND Status = 'active'");
    $stmt->execute([$cookie_user_id, $cookie_token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Token hợp lệ -> Tự động đăng nhập
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['user_name'] = $user['FullName'];
        $_SESSION['user_role'] = $user['Role'];
        $_SESSION['user_avatar'] = $user['Avatar'];
        $_SESSION['user_email'] = $user['Email'];
        $_SESSION['role'] = $user['Role']; // Tương thích với code admin
        
        // Cập nhật LastLogin
        $updateStmt = $conn->prepare("UPDATE Users SET LastLogin = NOW() WHERE UserID = ?");
        $updateStmt->execute([$user['UserID']]);
        
        // Làm mới cookie (gia hạn thêm 30 ngày)
        setcookie('remember_user_id', $user['UserID'], time() + (86400 * 30), "/");
        setcookie('remember_token', $cookie_token, time() + (86400 * 30), "/");
    } else {
        // Token không hợp lệ -> Xóa cookie
        setcookie('remember_user_id', '', time() - 3600, "/");
        setcookie('remember_token', '', time() - 3600, "/");
    }
}
?>