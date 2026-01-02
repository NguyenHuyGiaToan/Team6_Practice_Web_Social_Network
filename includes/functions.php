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
function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);           // value 60 is seconds  
    $hours   = round($seconds / 3600);         // value 3600 is 60 minutes * 60 sec  
    $days    = round($seconds / 86400);        // value 86400 is 24 hours * 60 minutes * 60 sec  
    
    if($seconds <= 60) return "Vừa xong";
    else if($minutes <= 60) return $minutes . " phút trước";
    else if($hours <= 24) return $hours . " giờ trước";
    else return $days . " ngày trước";
}

// 5. Hàm lọc từ khóa nhạy cảm
// Phục vụ task của Trọng Minh (Kiểm duyệt nội dung)
function filterBadWords($text) {
    $badWords = ['tu_nhay_cam_1', 'tu_nhay_cam_2']; // Trọng Minh cập nhật mảng này
    return str_ireplace($badWords, '***', $text);
}
?>