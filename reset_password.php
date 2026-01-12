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
    <link rel="stylesheet" href="assets/Style-css/reset_password.css">
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