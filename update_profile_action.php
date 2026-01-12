<?php
//update_profile_action.php
require_once __DIR__ . '../includes/config.php';
require_once __DIR__ . '../includes/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Nhận dữ liệu từ form
    $address = trim($_POST['address'] ?? '');
    $birthdate = $_POST['birthdate'] ?? null;
    $gender = $_POST['gender'] ?? 'Khac';

    // Thực hiện cập nhật các trường được cho phép
    $sql = "UPDATE Users SET Address = ?, BirthDate = ?, Gender = ? WHERE UserID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    // Bind 4 tham số: Address (s), BirthDate (s), Gender (s), UserID (i)
    mysqli_stmt_bind_param($stmt, "sssi", $address, $birthdate, $gender, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Thông tin đã được cập nhật!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật cơ sở dữ liệu.']);
    }
    exit();
}