<?php
// reset_password.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$error = '';
$success = '';
$validToken = false;

// 1. Kiểm tra Token có hợp lệ không
if (!empty($token) && !empty($email)) {
    $stmt = mysqli_prepare($conn, "SELECT UserID, ResetTokenExpiry FROM Users WHERE Email = ? AND ResetToken = ?");
    mysqli_stmt_bind_param($stmt, "ss", $email, $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user) {
        if (strtotime($user['ResetTokenExpiry']) > time()) {
            $validToken = true;
        } else {
            $error = "Đường dẫn đã hết hạn. Vui lòng gửi yêu cầu mới.";
        }
    } else {
        $error = "Đường dẫn không hợp lệ.";
    }
} else {
    $error = "Thiếu thông tin xác thực.";
}

// 2. Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validToken) {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    if (strlen($pass1) < 6) {
        $error = "Mật khẩu phải có ít nhất 6 ký tự.";
    } elseif ($pass1 !== $pass2) {
        $error = "Hai mật khẩu không khớp nhau.";
    } else {
        $newHash = password_hash($pass1, PASSWORD_DEFAULT);
        
        // Cập nhật mật khẩu và xóa token
        $update = mysqli_prepare($conn, "UPDATE Users SET PasswordHash = ?, ResetToken = NULL, ResetTokenExpiry = NULL WHERE UserID = ?");
        mysqli_stmt_bind_param($update, "si", $newHash, $user['UserID']);
        
        if (mysqli_stmt_execute($update)) {
            $success = "Đặt lại mật khẩu thành công! Bạn sẽ được chuyển về trang đăng nhập...";
            echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 3000);</script>";
            $validToken = false; // Ẩn form đi
        } else {
            $error = "Lỗi hệ thống, vui lòng thử lại.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu - TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Copy CSS từ forgot_password.php vào đây để tiết kiệm dòng */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Mulish', sans-serif; }
        body { height: 100vh; display: flex; background: #fff; overflow: hidden; }
        .left-panel { width: 45%; background-color: #791a23; color: white; padding: 60px; display: flex; flex-direction: column; justify-content: flex-end; padding-bottom: 100px; }
        .left-panel h1 { font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 20px; }
        .right-panel { width: 55%; display: flex; flex-direction: column; align-items: center; padding: 20px 40px; height: 100vh; overflow-y: auto; }
        .login-wrapper { width: 100%; max-width: 420px; margin: auto; }
        .logo-area { text-align: center; margin-bottom: 20px; }
        .logo-area img { height: 90px; width: auto; display: block; margin: 0 auto; object-fit: contain; }
        .form-title { text-align: center; font-size: 1.5rem; font-weight: 700; margin-bottom: 30px; color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 8px; color: #333; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; outline: none; }
        .form-control:focus { border-color: #791a23; box-shadow: 0 0 0 1px #791a23; }
        .btn-login { width: 100%; padding: 14px; background: #791a23; color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 700; cursor: pointer; }
        .msg-box { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; }
        .msg-error { background: #ffebee; color: #d32f2f; border: 1px solid #ffcdd2; }
        .msg-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        @media (max-width: 900px) { .left-panel { display: none; } .right-panel { width: 100%; } }
    </style>
</head>
<body>
    <div class="left-panel">
        <h1>Bảo mật<br>là trên hết.</h1>
        <p>Đặt lại mật khẩu mới để tiếp tục hành trình cùng TSix một cách an toàn.</p>
    </div>

    <div class="right-panel">
        <div class="login-wrapper">
            <div class="logo-area"><img src="assets/images/avt.png" alt="TSix Logo"></div>
            <div class="form-title">Đặt lại mật khẩu</div>

            <?php if($error): ?>
                <div class="msg-box msg-error"><i class="fa fa-times-circle"></i> <?php echo $error; ?></div>
                <div style="text-align: center;"><a href="forgot_password.php" style="color:#791a23; font-weight:bold;">Gửi lại yêu cầu</a></div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="msg-box msg-success"><i class="fa fa-check-circle"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if($validToken): ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Mật khẩu mới</label>
                    <input type="password" name="pass1" class="form-control" placeholder="Nhập mật khẩu mới" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Xác nhận mật khẩu</label>
                    <input type="password" name="pass2" class="form-control" placeholder="Nhập lại mật khẩu mới" required>
                </div>
                <button type="submit" class="btn-login">Đổi mật khẩu</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>