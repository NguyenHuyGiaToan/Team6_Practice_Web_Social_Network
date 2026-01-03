<?php
// register.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// --- PHẦN 1: XỬ LÝ AJAX (Check trùng Email/SĐT) ---
// Giữ nguyên logic cũ để đảm bảo chức năng
if (isset($_POST['ajax_check_contact'])) {
    $contact = $_POST['ajax_check_contact'];
    $sql = "SELECT UserID FROM Users WHERE Email = ? OR Phone = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$contact, $contact]);
    
    if ($stmt->rowCount() > 0) echo "exist";
    else echo "ok";
    exit(); 
}

// --- PHẦN 2: XỬ LÝ ĐĂNG KÝ ---
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_register'])) {
    $fullname = sanitizeInput($_POST['fullname']);
    $contact  = sanitizeInput($_POST['contact']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];
    $gender   = $_POST['gender'];
    $dob      = $_POST['dob'];

    $email = (strpos($contact, '@') !== false) ? $contact : null;
    $phone = (strpos($contact, '@') === false) ? $contact : null;

    if (empty($fullname) || empty($contact) || empty($password)) {
        $error = "Vui lòng nhập đủ thông tin!";
    } elseif ($password !== $confirm) {
        $error = "Mật khẩu xác nhận không khớp!";
    } else {
        // Check trùng lần cuối phía server
        $checkSql = "SELECT UserID FROM Users WHERE Email = ? OR Phone = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->execute([$email, $phone]);

        if ($stmt->rowCount() > 0) {
            $error = "Tài khoản này đã tồn tại!";
        } else {
            // Insert
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO Users (FullName, Email, Phone, PasswordHash, Gender, BirthDate) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtInsert = $conn->prepare($sql);
            
            if ($stmtInsert->execute([$fullname, $email, $phone, $hashed_password, $gender, $dob])) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Lỗi hệ thống!";
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
    <title>Đăng ký tài khoản - TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Reset & Font */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { height: 100vh; display: flex; overflow: hidden; }

        /* --- LAYOUT 2 CỘT --- */
        
        /* 1. Cột Trái (Màu Đỏ) */
        .left-panel {
            width: 40%;
            background-color: #8B1E29; /* Màu đỏ giống ảnh */
            color: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end; /* Nội dung dồn xuống dưới */
            position: relative;
        }
        .left-panel h1 { font-size: 3rem; font-weight: bold; line-height: 1.2; margin-bottom: 20px; }
        .left-panel p { font-size: 1.1rem; line-height: 1.6; opacity: 0.9; }

        /* 2. Cột Phải (Form Trắng) */
        .right-panel {
            width: 60%;
            background: #fff;
            padding: 40px 80px;
            overflow-y: auto; /* Cho phép cuộn nếu màn hình nhỏ */
            display: flex;
            flex-direction: column;
        }

        /* Header Form */
        .form-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .logo { font-size: 2rem; font-weight: bold; color: #8B1E29; display: flex; flex-direction: column; align-items: center; line-height: 1;}
        .logo span { font-size: 0.8rem; letter-spacing: 2px; color: #333; margin-top: 5px;}
        
        .login-ask { font-size: 0.9rem; color: #666; }
        .login-ask a { color: #8B1E29; font-weight: bold; text-decoration: none; }

        .form-title h2 { font-size: 2rem; color: #8B1E29; margin-bottom: 10px; }
        .form-title p { color: #666; margin-bottom: 30px; }

        /* Form Elements */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 8px; color: #333; }
        .form-group label span { color: #d93025; } /* Dấu sao đỏ */

        .form-control {
            width: 100%; padding: 12px 15px;
            border: 1px solid #ddd; border-radius: 8px;
            font-size: 1rem; outline: none; transition: 0.3s;
        }
        .form-control:focus { border-color: #8B1E29; box-shadow: 0 0 0 3px rgba(139, 30, 41, 0.1); }
        
        /* Input Validation Styles */
        .input-error { border-color: #d93025 !important; background: #fff5f5; }
        .error-text { color: #d93025; font-size: 0.85rem; margin-top: 5px; display: none; }

        /* Grid Layout cho Form (Giới tính, Ngày sinh, Pass) */
        .row { display: flex; gap: 20px; }
        .col { flex: 1; }

        /* Gender Selection (Custom Radio Boxes) */
        .gender-group { display: flex; gap: 10px; }
        .gender-option { flex: 1; position: relative; }
        .gender-option input { position: absolute; opacity: 0; cursor: pointer; height: 0; width: 0; }
        .gender-label {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 15px; border: 1px solid #ddd; border-radius: 8px;
            cursor: pointer; transition: 0.2s; font-size: 0.95rem;
        }
        .gender-circle {
            width: 18px; height: 18px; border: 2px solid #ccc; border-radius: 50%; position: relative;
        }
        /* Khi được chọn */
        .gender-option input:checked ~ .gender-label { border-color: #8B1E29; background: #fff5f5; color: #8B1E29; font-weight: bold; }
        .gender-option input:checked ~ .gender-label .gender-circle { border-color: #8B1E29; }
        .gender-option input:checked ~ .gender-label .gender-circle::after {
            content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 10px; height: 10px; background: #8B1E29; border-radius: 50%;
        }

        /* Password Eye */
        .password-input-container { position: relative; }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; }

        /* Checkbox Terms */
        .terms { display: flex; align-items: flex-start; gap: 10px; font-size: 0.9rem; color: #555; margin-bottom: 25px; }
        .terms input { margin-top: 3px; accent-color: #8B1E29; }
        .terms a { color: #8B1E29; text-decoration: none; font-weight: 600; }

        /* Buttons */
        .btn-register {
            width: 100%; padding: 14px; background: #f3f4f6; color: #aaa;
            border: none; border-radius: 8px; font-size: 1rem; font-weight: bold;
            cursor: not-allowed; transition: 0.3s;
        }
        /* Active State của nút (khi check box) */
        .btn-register.active { background: #8B1E29; color: white; cursor: pointer; }
        .btn-register.active:hover { background: #6b161f; }

        .divider { text-align: center; color: #888; font-size: 0.9rem; margin: 20px 0; position: relative; }
        .divider::before, .divider::after { content: ""; position: absolute; top: 50%; width: 40%; height: 1px; background: #eee; }
        .divider::before { left: 0; } .divider::after { right: 0; }

        .social-login { display: flex; gap: 20px; }
        .btn-social {
            flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 8px; background: white;
            display: flex; justify-content: center; align-items: center; gap: 10px;
            font-weight: 600; cursor: pointer; color: #333; transition: 0.2s;
        }
        .btn-social:hover { background: #f9f9f9; border-color: #ccc; }

        /* Responsive */
        @media (max-width: 900px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; padding: 30px; }
        }
    </style>
</head>
<body>

    <div class="left-panel">
        <h1>Tham gia cộng đồng <br>TSix</h1>
        <p>Kết nối với bạn bè, chia sẻ khoảnh khắc và khám phá thế giới xung quanh bạn theo cách hoàn toàn mới.</p>
    </div>

    <div class="right-panel">
        <div class="form-header">
            <div class="logo">
                <i class="fa-brands fa-xing"></i> 
                <span>TSix</span>
            </div>
            <div class="login-ask">
                Bạn đã có tài khoản? <a href="login.php">Đăng nhập</a>
            </div>
        </div>

        <div class="form-title">
            <h2>Đăng ký tài khoản mới</h2>
            <p>Nhanh chóng và dễ dàng.</p>
            <?php if($error): ?>
                <div style="color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" action="" id="regForm">
            <div class="form-group">
                <label>Họ và tên <span>*</span></label>
                <input type="text" name="fullname" class="form-control" placeholder="Nhập họ và tên của bạn" required>
            </div>

            <div class="form-group">
                <label>Email hoặc Số điện thoại <span>*</span></label>
                <input type="text" name="contact" id="contact" class="form-control" placeholder="example@company.com" required>
                <div id="ajaxError" class="error-text">Thông tin này đã được sử dụng!</div>
            </div>

            <div class="row">
                <div class="col form-group">
                    <label>Giới tính</label>
                    <div class="gender-group">
                        <div class="gender-option">
                            <input type="radio" name="gender" value="Nam" id="gMale" checked>
                            <label for="gMale" class="gender-label">Nam <div class="gender-circle"></div></label>
                        </div>
                        <div class="gender-option">
                            <input type="radio" name="gender" value="Nu" id="gFemale">
                            <label for="gFemale" class="gender-label">Nữ <div class="gender-circle"></div></label>
                        </div>
                        <div class="gender-option">
                            <input type="radio" name="gender" value="Khac" id="gOther">
                            <label for="gOther" class="gender-label">Khác <div class="gender-circle"></div></label>
                        </div>
                    </div>
                </div>
                <div class="col form-group">
                    <label>Ngày sinh</label>
                    <input type="date" name="dob" class="form-control" required>
                </div>
            </div>

            <div class="row">
                <div class="col form-group">
                    <label>Mật khẩu <span>*</span></label>
                    <div class="password-input-container">
                        <input type="password" name="password" id="pass" class="form-control" placeholder="••••••••" required>
                        <i class="fa fa-eye-slash toggle-password" onclick="togglePass('pass', this)"></i>
                    </div>
                </div>
                <div class="col form-group">
                    <label>Xác nhận mật khẩu <span>*</span></label>
                    <div class="password-input-container">
                        <input type="password" name="confirm_password" id="repass" class="form-control" placeholder="••••••••" required>
                        <i class="fa fa-eye-slash toggle-password" onclick="togglePass('repass', this)"></i>
                    </div>
                </div>
            </div>

            <div class="terms">
                <input type="checkbox" id="termsCheck">
                <label for="termsCheck">
                    Bằng cách nhấp vào Đăng ký, bạn đồng ý với <a href="#">Điều khoản</a>, <a href="#">Chính sách dữ liệu</a> & <a href="#">Chính sách cookie</a> của chúng tôi.
                </label>
            </div>

            <button type="submit" name="btn_register" id="btnSubmit" class="btn-register">Đăng ký</button>

            <div class="divider">Đăng ký với</div>
            <div class="social-login">
                <button type="button" class="btn-social">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg" width="20"> Google
                </button>
                <button type="button" class="btn-social">
                    <i class="fa-brands fa-apple" style="font-size: 1.2rem;"></i> Apple
                </button>
            </div>
            
            <div style="text-align: center; margin-top: 30px; font-size: 0.8rem; color: #999;">
                &copy; 2026 TSix Social Network. Bảo mật & Điều khoản
            </div>
        </form>
    </div>

    <script>
        // 1. Logic ẩn hiện mật khẩu
        function togglePass(id, icon) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            } else {
                input.type = "password";
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            }
        }

        // 2. Logic Checkbox Điều khoản -> Enable nút Đăng ký
        const termsCheck = document.getElementById('termsCheck');
        const btnSubmit = document.getElementById('btnSubmit');

        termsCheck.addEventListener('change', function() {
            if (this.checked) {
                btnSubmit.classList.add('active');
                if(!document.getElementById('contact').classList.contains('input-error')) {
                    btnSubmit.disabled = false;
                }
            } else {
                btnSubmit.classList.remove('active');
                btnSubmit.disabled = true;
            }
        });

        // 3. Logic AJAX Check trùng
        const contactInput = document.getElementById('contact');
        const ajaxError = document.getElementById('ajaxError');

        contactInput.addEventListener('blur', function() {
            const val = this.value.trim();
            if (val.length > 0) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '', true); // Post về chính file này
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    if (this.responseText.trim() === 'exist') {
                        contactInput.classList.add('input-error');
                        ajaxError.style.display = 'block';
                        btnSubmit.disabled = true;
                        btnSubmit.classList.remove('active'); // Tắt nút nếu lỗi
                    } else {
                        contactInput.classList.remove('input-error');
                        ajaxError.style.display = 'none';
                        if(termsCheck.checked) {
                            btnSubmit.disabled = false;
                            btnSubmit.classList.add('active');
                        }
                    }
                };
                xhr.send('ajax_check_contact=' + encodeURIComponent(val));
            }
        });
        
        // Reset lỗi khi nhập lại
        contactInput.addEventListener('input', function() {
            contactInput.classList.remove('input-error');
            ajaxError.style.display = 'none';
        });
    </script>

</body>
</html>