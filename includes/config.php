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
?>