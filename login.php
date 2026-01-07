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
    
    <style>
        /* CSS RESET */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Mulish', sans-serif; }
        body { height: 100vh; display: flex; background: #fff; overflow: hidden; }

        /* --- CỘT TRÁI (ĐỎ) --- */
        .left-panel {
            width: 45%; 
            background-color: #791a23; 
            color: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end; 
            padding-bottom: 100px; 
        }
        
      

        .left-panel h1 { font-size: 2.5rem; font-weight: 800; line-height: 1.2; margin-bottom: 20px; }
        .left-panel p { font-size: 1rem; line-height: 1.6; opacity: 0.9; }

        /* --- CỘT PHẢI (TRẮNG) - ĐÃ SỬA --- */
        .right-panel {
            width: 55%;
            display: flex;
            flex-direction: column; /* Xếp dọc */
            /* Bỏ justify-content: center để tránh lỗi overlap */
            align-items: center;
            padding: 20px 40px; /* Thêm padding trên dưới */
            height: 100vh;
            overflow-y: auto; /* Cho phép cuộn nếu màn hình quá nhỏ */
        }

        /* Wrapper form: Tự động căn giữa dọc bằng margin auto */
        .login-wrapper { 
            width: 100%; 
            max-width: 420px; 
            margin-top: auto;    /* Đẩy xuống dưới */
            margin-bottom: auto; /* Đẩy lên trên -> Kết quả: Nằm giữa */
        }

        /* --- LOGO --- */
        .logo-area { text-align: center; margin-bottom: 20px; }
        .logo-area img { height: 90px; width: auto; display: block; margin: 0 auto; object-fit: contain; }
        
        .form-title { text-align: center; font-size: 1.5rem; font-weight: 700; margin-bottom: 30px; color: #333; }

        /* --- INPUTS --- */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 8px; color: #333; }
        .form-label span { color: #d32f2f; }

        .form-control {
            width: 100%; padding: 12px 15px;
            border: 1px solid #ddd; border-radius: 6px; 
            font-size: 1rem; outline: none; transition: 0.2s;
        }
        .form-control:focus { border-color: #791a23; box-shadow: 0 0 0 1px #791a23; }
        .form-control:invalid { border-color: #ddd; } 
        
        .password-wrap { position: relative; }
        .toggle-pass { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #999; }

        /* --- OPTIONS --- */
        .options-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; font-size: 0.85rem; }
        .remember-label { display: flex; align-items: center; gap: 8px; cursor: pointer; color: #555; }
        .remember-label input { accent-color: #791a23; width: 16px; height: 16px; }
        .forgot-link { color: #666; text-decoration: none; }
        .forgot-link:hover { text-decoration: underline; }

        /* --- BUTTONS --- */
        .btn-login {
            width: 100%; padding: 14px;
            background: #f3f4f6; color: #9ca3af; 
            border: none; border-radius: 6px;
            font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.2s;
        }
        .btn-login:hover { background: #e5e7eb; color: #6b7280; }

        .divider { text-align: center; color: #aaa; font-size: 0.8rem; margin: 30px 0 20px 0; position: relative; }
        .divider::before, .divider::after { content: ""; position: absolute; top: 50%; width: 35%; height: 1px; background: #eee; }
        .divider::before { left: 0; } .divider::after { right: 0; }

        .social-row { display: flex; gap: 15px; margin-bottom: 30px; }
        .btn-social {
            flex: 1; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; background: white;
            display: flex; justify-content: center; align-items: center; gap: 10px;
            font-weight: 600; cursor: pointer; color: #333; transition: 0.2s; font-size: 0.9rem;
        }
        .btn-social:hover { background: #f9f9f9; border-color: #ccc; }
        .btn-social img { width: 18px; height: 18px; }

        /* --- FOOTER & COPYRIGHT (ĐÃ FIX) --- */
        .auth-footer { text-align: center; font-size: 0.85rem; color: #999; }
        .auth-footer a { color: #b91c1c; font-weight: 700; text-decoration: none; }

        /* Fix lỗi: Bỏ absolute, dùng margin để đẩy khoảng cách */
        .copyright { 
            text-align: center; 
            font-size: 0.75rem; 
            color: #ccc; 
            margin-top: 40px; /* Cách form 40px */
            flex-shrink: 0;   /* Không bị co lại */
            width: 100%;
        }

        .error-msg {
            color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 6px; 
            margin-bottom: 20px; text-align: center; font-size: 0.9rem; 
            display: flex; justify-content: center; align-items: center; gap: 8px;
        }

        @media (max-width: 900px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; }
        }
    </style>
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
                    <a href="#" class="forgot-link">Quên mật khẩu?</a>
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