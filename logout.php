<?php
// logout.php
require_once 'includes/config.php';

// 1. Xóa tất cả biến session
session_unset();

// 2. Hủy session
session_destroy();

// 3. Xóa cookie ghi nhớ (nếu có)
if (isset($_COOKIE['user_login'])) {
    setcookie('user_login', '', time() - 3600, "/");
}

// 4. Chuyển về trang login
header("Location: login.php");
exit();
?>