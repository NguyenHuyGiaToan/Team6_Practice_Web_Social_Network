<?php
// login.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// 1. Nếu đã đăng nhập thì về trang chủ
if (function_exists('isLoggedIn') && isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$loginInput = ''; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $loginInput = trim($_POST['login_input']);
    $password   = $_POST['password'];
    $remember   = isset($_POST['remember']);

    if (empty($loginInput) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        $sql = "SELECT * FROM Users WHERE Email = ? OR Phone = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ss", $loginInput, $loginInput);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($user && password_verify($password, $user['PasswordHash'])) {
                if (isset($user['Status']) && $user['Status'] == 'locked') {
                    $error = "Tài khoản bị khóa!";
                } else {
                    $_SESSION['user_id'] = $user['UserID'];
                    $_SESSION['user_name'] = $user['FullName'];
                    $_SESSION['user_role'] = $user['Role'] ?? 'user';
                    $_SESSION['user_avatar'] = $user['Avatar'] ?? '';

                    if ($remember) {
                        setcookie('user_login', $user['UserID'], time() + (86400 * 30), "/");
                    }
                    
                    $upSql = "UPDATE Users SET LastLogin = NOW() WHERE UserID = ?";
                    if($upStmt = mysqli_prepare($conn, $upSql)){
                         mysqli_stmt_bind_param($upStmt, "i", $user['UserID']);
                         mysqli_stmt_execute($upStmt);
                    }

                    header("Location: " . ($user['Role'] == 'admin' ? "admin/dashboard.php" : "index.php"));
                    exit();
                }
            } else {
                $error = "Tài khoản hoặc mật khẩu không đúng!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/Style-css/login.css">
</head>
<body>

    <div class="left-panel">
        <div></div>
        <hr>
        <h1>Kết nối đam mê, <br>chia sẻ khoảnh khắc.</h1>
        <p>Tham gia cộng đồng TSix để khám phá những ý tưởng mới và kết nối hàng triệu người dùng.</p>
    </div>

    <div class="right-panel">
        <div class="login-wrapper">
            <div class="logo-area">
                <img src="<?= BASE_URL ?>/assets/images/avt.png" alt="TSix Logo" 
                     onerror="this.style.display='none'; document.getElementById('alt-logo').style.display='block';">
                <div id="alt-logo" style="display:none; color:#791a23; font-size:2rem; font-weight:800;">TSix</div>
            </div>

            <div class="form-title">Đăng nhập tài khoản</div>

            <?php if($error): ?>
                <div class="error-msg">
                    <i class="fa fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email hoặc Số điện thoại <span>*</span></label>
                    <input type="text" name="login_input" class="form-control" 
                           placeholder="Nhập Email hoặc Số điện thoại" 
                           value="<?php echo htmlspecialchars($loginInput); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mật khẩu <span>*</span></label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="pass" class="form-control" 
                               placeholder="Nhập mật khẩu" required>
                        <i class="fa-regular fa-eye-slash toggle-pass" onclick="togglePass()"></i>
                    </div>
                </div>

                <div class="options-row">
                    <label class="remember-label">
                        <input type="checkbox" name="remember"> Ghi nhớ đăng nhập
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn-login">Đăng nhập</button>

                <div class="divider">Đăng nhập với</div>

                <div class="social-row">
                    <button type="button" class="btn-social">
                        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google"> 
                        Google
                    </button>
                    <button type="button" class="btn-social">
                        <i class="fa-brands fa-apple" style="font-size: 1.2rem;"></i> Apple
                    </button>
                </div>

                <div class="auth-footer">
                    Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
                </div>
            </form>
        </div>

        <div class="copyright">
            © 2026 TSix Social Network. Bảo mật & Điều khoản
        </div>
    </div>

    <script>
        function togglePass() {
            const input = document.getElementById('pass');
            const icon = document.querySelector('.toggle-pass');
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            } else {
                input.type = "password";
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            }
        }
    </script>
</body>
</html>