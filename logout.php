<?php
// logout.php
session_start(); // Khởi động session để lấy dữ liệu hiện tại

// 1. Xóa tất cả các biến trong Session
$_SESSION = array();

// 2. Hủy Session Cookie (của trình duyệt)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Xóa Cookie "Ghi nhớ đăng nhập" (Quan trọng)
// Phải set thời gian về quá khứ (time() - 3600) để trình duyệt xóa nó ngay lập tức
if (isset($_COOKIE['user_login'])) {
    setcookie('user_login', '', time() - 3600, "/");
    unset($_COOKIE['user_login']); // Xóa biến khỏi mảng $_COOKIE hiện tại
}

// 4. Hủy hoàn toàn phiên làm việc
session_destroy();

// 5. Chuyển hướng về trang đăng nhập
header("Location: login.php");
exit();
?>