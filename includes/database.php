<?php
// Thông số kết nối Database
$host = 'localhost';
$db_name = 'WEBMXH_Nhom06'; 
$username = 'root';
$password = '';

try {
    // Khởi tạo kết nối PDO
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    
    // Thiết lập chế độ báo lỗi
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Thiết lập kiểu trả về dữ liệu mặc định là mảng kết hợp (Associative Array)
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Nếu kết nối lỗi, dừng chương trình và báo lỗi
    die("Kết nối cơ sở dữ liệu thất bại: " . $e->getMessage());
}
?>