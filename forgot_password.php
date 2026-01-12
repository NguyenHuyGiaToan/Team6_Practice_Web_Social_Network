<?php
// forgot_password.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Nhập các lớp PHPMailer vào không gian tên toàn cục
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Yêu cầu các file thư viện PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Vui lòng nhập email của bạn.";
    } else {
        // Kiểm tra email có tồn tại trong hệ thống không
        $stmt = mysqli_prepare($conn, "SELECT UserID, FullName FROM Users WHERE Email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            // Tạo Token ngẫu nhiên và thời gian hết hạn (15 phút)
            $token = bin2hex(random_bytes(32)); 
            $expiry = date('Y-m-d H:i:s', time() + (15 * 60)); 

            // Lưu Token vào cơ sở dữ liệu
            $update = mysqli_prepare($conn, "UPDATE Users SET ResetToken = ?, ResetTokenExpiry = ? WHERE UserID = ?");
            mysqli_stmt_bind_param($update, "ssi", $token, $expiry, $user['UserID']);
            
            if (mysqli_stmt_execute($update)) {
                // Tạo đường link đổi mật khẩu
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token . "&email=" . urlencode($email);
                
                // --- Cấu hình và gửi Email qua PHPMailer ---
                $mail = new PHPMailer(true);
                try {
                    // Cài đặt Server dựa trên test_email.php
                    $mail->isSMTP();                                            
                    $mail->Host       = 'smtp.gmail.com';                     
                    $mail->SMTPAuth   = true;                                   
                    $mail->Username   = 'tsix.social.network@gmail.com'; // Tài khoản Gmail gửi
                    $mail->Password   = 'dzes lpok maws zlrx';                   // Mật khẩu ứng dụng Gmail
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
                    $mail->Port       = 465;                                    
                    $mail->CharSet    = 'utf8';

                    // Người gửi và người nhận
                    $mail->setFrom('tsix.social.network@gmail.com', 'TSix');
                    $mail->addAddress($email, $user['FullName']); 

                    // Nội dung Email
                    $mail->isHTML(true);                                  
                    $mail->Subject = 'Yêu cầu đặt lại mật khẩu - TSix';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                            <h2>Xin chào " . htmlspecialchars($user['FullName']) . ",</h2>
                            <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại mạng xã hội TSix.</p>
                            <p>Vui lòng nhấn vào đường link dưới đây để cập nhật mật khẩu mới (Link có hiệu lực trong 15 phút):</p>
                            <p><a href='$resetLink' style='color: #791a23; font-weight: bold;'>$resetLink</a></p>
                            <p>Nếu bạn không gửi yêu cầu này, vui lòng bỏ qua email này.</p>
                            <br>
                            <p>Trân trọng,<br>Đội ngũ TSix</p>
                        </div>";

                    $mail->send();
                    $message = "Một liên kết đặt lại mật khẩu đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.";
                } catch (Exception $e) {
                    $error = "Không thể gửi email. Lỗi: {$mail->ErrorInfo}";
                }
                // --- Kết thúc phần gửi Email ---
                
            } else {
                $error = "Có lỗi xảy ra, vui lòng thử lại.";
            }
        } else {
            $error = "Email này chưa được đăng ký trong hệ thống.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Mulish', sans-serif; }
        body { height: 100vh; display: flex; background: #fff; overflow: hidden; }
        .left-panel { width: 45%; background-color: #791a23; color: white; padding: 60px; display: flex; flex-direction: column; justify-content: flex-end; padding-bottom: 100px; }
        .left-panel h1 { font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 20px; }
        .left-panel p { font-size: 1rem; line-height: 1.6; opacity: 0.9; }
        .right-panel { width: 55%; display: flex; flex-direction: column; align-items: center; padding: 20px 40px; height: 100vh; overflow-y: auto; }
        .login-wrapper { width: 100%; max-width: 420px; margin: auto; }
        .logo-area { text-align: center; margin-bottom: 20px; }
        .logo-area img { height: 90px; width: auto; display: block; margin: 0 auto; object-fit: contain; }
        .form-title { text-align: center; font-size: 1.5rem; font-weight: 700; margin-bottom: 10px; color: #333; }
        .form-subtitle { text-align: center; font-size: 0.9rem; color: #666; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 8px; color: #333; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; outline: none; transition: 0.2s; }
        .form-control:focus { border-color: #791a23; box-shadow: 0 0 0 1px #791a23; }
        .btn-login { width: 100%; padding: 14px; background: #791a23; color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
        .btn-login:hover { background: #9c222e; }
        .msg-box { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; line-height: 1.5; }
        .msg-error { background: #ffebee; color: #d32f2f; border: 1px solid #ffcdd2; }
        .msg-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #666; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .back-link a:hover { color: #791a23; }
        @media (max-width: 900px) { .left-panel { display: none; } .right-panel { width: 100%; } }
    </style>
</head>
<body>
    <div class="left-panel">
        <h1>Khôi phục<br>quyền truy cập.</h1>
        <p>Đừng lo lắng, hãy nhập email của bạn và chúng tôi sẽ giúp bạn lấy lại mật khẩu ngay lập tức.</p>
    </div>

    <div class="right-panel">
        <div class="login-wrapper">
            <div class="logo-area">
                <img src="assets/images/avt.png" alt="TSix Logo">
            </div>

            <div class="form-title">Quên mật khẩu?</div>
            <p class="form-subtitle">Nhập email đã đăng ký để nhận liên kết đặt lại mật khẩu.</p>

            <?php if($error): ?>
                <div class="msg-box msg-error"><i class="fa fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <?php if($message): ?>
                <div class="msg-box msg-success"><i class="fa fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email đăng ký</label>
                    <input type="email" name="email" class="form-control" placeholder="vidu@email.com" required>
                </div>

                <button type="submit" class="btn-login">Gửi yêu cầu</button>

                <div class="back-link">
                    <a href="login.php"><i class="fa fa-arrow-left"></i> Quay lại đăng nhập</a>
                </div>
            </form>
        </div>
        <div style="margin-top: auto; color: #ccc; font-size: 0.75rem;">© 2026 TSix Social Network</div>
    </div>
</body>
</html>