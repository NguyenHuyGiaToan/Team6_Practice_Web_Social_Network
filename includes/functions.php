<?php
// 1. Hàm làm sạch dữ liệu đầu vào (Chống XSS)
// Tất cả thành viên cần dùng hàm này trước khi lưu dữ liệu vào DB
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// 2. Hàm kiểm tra đăng nhập
// Thị Như, Thanh Ngọc cần dùng hàm này để bảo vệ các trang index.php, admin.php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 3. Hàm chuyển hướng nhanh
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

// 4. Hàm định dạng thời gian (Ví dụ: 5 phút trước, 1 giờ trước)
// Phục vụ task của Thị Như khi hiển thị bài viết
function timeAgo($datetime) {
    // Chấp nhận cả string datetime hoặc timestamp
    $time = is_numeric($datetime) ? $datetime : strtotime($datetime);
    if ($time === false) {
        return 'Không xác định';
    }

    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'Vừa xong';
    }

    $intervals = [
        'năm'   => 31536000,  // 365 * 24 * 60 * 60
        'tháng' => 2592000,   // 30 * 24 * 60 * 60
        'tuần'  => 604800,    // 7 * 24 * 60 * 60
        'ngày'  => 86400,
        'giờ'   => 3600,
        'phút'  => 60,
    ];

    foreach ($intervals as $unit => $seconds) {
        $count = floor($diff / $seconds);
        if ($count >= 1) {
            // Xử lý số nhiều (phút, giờ, ngày,...)
            if ($count == 1 && in_array($unit, ['ngày', 'giờ', 'phút'])) {
                $unit = rtrim($unit, 's'); // không cần, vì tiếng Việt không đổi dạng
            }
            return $count . ' ' . $unit . ' trước';
        }
    }

    return 'Vừa xong';
}

// 5. Hàm lọc từ khóa nhạy cảm
// Phục vụ task của Trọng Minh (Kiểm duyệt nội dung)
function filterBadWords($text) {
    $badWords = ['tu_nhay_cam_1', 'tu_nhay_cam_2']; // Trọng Minh cập nhật mảng này
    return str_ireplace($badWords, '***', $text);
}
?>