<?php
// login.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// 1. Kiểm tra nếu đã đăng nhập rồi thì chuyển hướng
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

// --- XỬ LÝ FORM ĐĂNG NHẬP ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $loginInput = sanitizeInput($_POST['login_input']);
    $password   = $_POST['password'];
    $remember   = isset($_POST['remember']);

    if (empty($loginInput) || empty($password)) {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        // Tìm User (PDO)
        $sql = "SELECT * FROM Users WHERE Email = ? OR Phone = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$loginInput, $loginInput]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['PasswordHash'])) {
            if ($user['Status'] == 'locked') {
                $error = "Tài khoản của bạn đã bị khóa!";
            } else {
                // Đăng nhập thành công -> Lưu Session
                $_SESSION['user_id'] = $user['UserID'];
                $_SESSION['user_name'] = $user['FullName'];
                $_SESSION['user_role'] = $user['Role'];
                $_SESSION['user_avatar'] = $user['Avatar'];

                // Cookie ghi nhớ (30 ngày)
                if ($remember) {
                    setcookie('user_login', $user['UserID'], time() + (86400 * 30), "/");
                }

                // Cập nhật LastLogin
                $updateStmt = $conn->prepare("UPDATE Users SET LastLogin = NOW() WHERE UserID = ?");
                $updateStmt->execute([$user['UserID']]);

                // Chuyển hướng theo quyền
                if ($user['Role'] == 'admin') {
                    header("Location: admin.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            }
        } else {
            $error = "Thông tin đăng nhập hoặc mật khẩu không đúng!";
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
    <style>
        /* CSS Reset & Global */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { 
            height: 100vh; 
            display: flex; 
            overflow: hidden; /* Ẩn thanh cuộn thừa */
            background: #fff; 
        }

        /* --- CỘT TRÁI (Màu đỏ) --- */
        .left-panel {
            width: 45%; 
            background-color: #8B1E29; /* Màu đỏ đô */
            color: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center; /* Đưa nội dung vào giữa theo chiều dọc */
            position: relative;
        }
        
        .left-content { 
            position: relative; 
            z-index: 2; 
            max-width: 80%; /* Giới hạn chiều rộng chữ */
        }
        
        .left-content h1 { 
            font-size: 3rem; 
            font-weight: 800; 
            line-height: 1.2; 
            margin-bottom: 20px; 
        }
        
        .left-content p { 
            font-size: 1.1rem; 
            line-height: 1.6; 
            opacity: 0.9; 
        }
        
        .decor-line { 
            width: 80px; 
            height: 5px; 
            background: rgba(255,255,255,0.3); 
            margin-top: 30px; 
            border-radius: 2px;
        }

        /* --- CỘT PHẢI (Form trắng) --- */
        .right-panel {
            width: 55%;
            display: flex;
            flex-direction: column;
            justify-content: center; /* Căn giữa dọc */
            align-items: center;     /* Căn giữa ngang */
            padding: 40px;
            background-color: #fff;
            position: relative;
        }

        /* Wrapper giới hạn độ rộng form - KHẮC PHỤC LỖI FORM QUÁ TO */
        .login-wrapper { 
            width: 100%; 
            max-width: 420px; /* Giới hạn chiều rộng tối đa */
        }

        /* Logo TSix */
        .logo-area { text-align: center; margin-bottom: 25px; }
        .logo-icon { font-size: 3.5rem; color: #8B1E29; display: block; margin-bottom: 5px; }
        .brand-name { font-weight: 800; font-size: 1.8rem; color: #333; letter-spacing: 2px; text-transform: uppercase; }

        .form-title { text-align: center; font-size: 1.4rem; font-weight: 700; margin-bottom: 25px; color: #333; }

        /* Form Controls */
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: #444; }
        .form-group label span { color: #d93025; }

        .form-control {
            width: 100%; padding: 12px 15px;
            border: 1px solid #ddd; border-radius: 8px;
            font-size: 1rem; outline: none; transition: 0.3s;
            background: #f9f9f9;
        }
        .form-control:focus { 
            border-color: #8B1E29; 
            background: #fff;
            box-shadow: 0 0 0 3px rgba(139, 30, 41, 0.1); 
        }
        
        .password-container { position: relative; }
        .toggle-eye { 
            position: absolute; 
            right: 15px; top: 50%; 
            transform: translateY(-50%); 
            cursor: pointer; color: #888; 
            padding: 5px;
        }

        /* Options Row */
        .options-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; font-size: 0.9rem; }
        .remember-label { display: flex; align-items: center; gap: 8px; cursor: pointer; color: #666; }
        .remember-label input { width: 16px; height: 16px; accent-color: #8B1E29; cursor: pointer; }
        .forgot-link { text-decoration: none; color: #666; transition: 0.2s; }
        .forgot-link:hover { color: #8B1E29; text-decoration: underline; }

        /* Buttons */
        .btn-login {
            width: 100%; padding: 12px;
            background: #8B1E29; /* Màu đỏ thương hiệu */
            color: white; border: none; border-radius: 8px;
            font-size: 1.1rem; font-weight: 700; cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 4px 6px rgba(139, 30, 41, 0.2);
        }
        .btn-login:hover { background: #6b161f; transform: translateY(-1px); }

        /* Divider */
        .divider { text-align: center; color: #999; font-size: 0.85rem; margin: 25px 0; position: relative; }
        .divider::before, .divider::after { content: ""; position: absolute; top: 50%; width: 30%; height: 1px; background: #eee; }
        .divider::before { left: 0; } .divider::after { right: 0; }

        /* Social Buttons */
        .social-row { display: flex; gap: 15px; margin-bottom: 25px; }
        .btn-social {
            flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 8px; background: white;
            display: flex; justify-content: center; align-items: center; gap: 10px;
            font-weight: 600; cursor: pointer; color: #333; transition: 0.2s; font-size: 0.95rem;
        }
        .btn-social:hover { background: #f9f9f9; border-color: #bbb; }

        /* Footer */
        .form-footer { text-align: center; font-size: 0.95rem; color: #666; }
        .form-footer a { color: #8B1E29; font-weight: 700; text-decoration: none; }
        .form-footer a:hover { text-decoration: underline; }
        
        .copyright { 
            position: absolute; bottom: 20px; left: 0; right: 0;
            text-align: center; font-size: 0.75rem; color: #aaa; 
        }

        /* Lỗi */
        .error-msg {
            color: #d93025; background: #ffe6e6; padding: 10px; border-radius: 6px; 
            margin-bottom: 20px; text-align: center; font-size: 0.9rem; border: 1px solid #ffcccc;
        }

        /* --- RESPONSIVE (Xử lý lỗi giao diện trên màn hình nhỏ) --- */
        @media (max-width: 992px) {
            .left-panel h1 { font-size: 2.2rem; }
            .left-panel { padding: 40px; }
        }

        @media (max-width: 768px) {
            body { overflow-y: auto; flex-direction: column; } /* Cho phép cuộn trên mobile */
            .left-panel { display: none; } /* Ẩn cột đỏ trên mobile để tập trung vào form */
            .right-panel { width: 100%; padding: 40px 20px; min-height: 100vh; }
            .copyright { position: relative; margin-top: 40px; }
        }
    </style>
</head>
<body>

    <div class="left-panel">
        <div class="left-content">
            <h1>Kết nối đam mê, <br>chia sẻ khoảnh khắc.</h1>
            <p>Tham gia cộng đồng TSix để khám phá những ý tưởng mới và kết nối hàng triệu người dùng.</p>
            <div class="decor-line"></div>
        </div>
    </div>

    <div class="right-panel">
        <div class="login-wrapper">
            
            <div class="logo-area">
                <div class="logo-icon"><i class="fa-brands fa-xing"></i></div>
                <div class="brand-name">TSIX</div>
            </div>

            <div class="form-title">Đăng nhập tài khoản</div>

            <?php if($error): ?>
                <div class="error-msg">
                    <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Email hoặc Số điện thoại <span>*</span></label>
                    <input type="text" name="login_input" class="form-control" placeholder="Nhập Email hoặc Số điện thoại" required>
                </div>

                <div class="form-group">
                    <label>Mật khẩu <span>*</span></label>
                    <div class="password-container">
                        <input type="password" name="password" id="pass" class="form-control" placeholder="Nhập mật khẩu" required>
                        <i class="fa fa-eye-slash toggle-eye" onclick="togglePass()"></i>
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
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" width="20"> Google
                    </button>
                    <button type="button" class="btn-social">
                        <i class="fa-brands fa-apple" style="font-size: 1.3rem;"></i> Apple
                    </button>
                </div>

                <div class="form-footer">
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
            const icon = document.querySelector('.toggle-eye');
            
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