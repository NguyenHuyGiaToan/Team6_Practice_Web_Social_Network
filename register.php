<?php
// register.php
require_once 'includes/config.php';
require_once 'includes/database.php'; // Đảm bảo dùng MySQLi
require_once 'includes/functions.php';

// --- LOGIC PHP (Giữ nguyên) ---
if (isset($_POST['ajax_check_contact'])) {
    $contact = $_POST['ajax_check_contact'];
    $sql = "SELECT UserID FROM Users WHERE Email = ? OR Phone = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $contact, $contact);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        echo (mysqli_stmt_num_rows($stmt) > 0) ? "exist" : "ok";
        mysqli_stmt_close($stmt);
    }
    exit(); 
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_register'])) {
    $fullname = trim($_POST['fullname']);
    $contact  = trim($_POST['contact']);
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
        $checkSql = "SELECT UserID FROM Users WHERE Email = ? OR Phone = ?";
        $stmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $phone);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Tài khoản này đã tồn tại!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO Users (FullName, Email, Phone, PasswordHash, Gender, BirthDate) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtInsert = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmtInsert, "ssssss", $fullname, $email, $phone, $hashed, $gender, $dob);
            if (mysqli_stmt_execute($stmtInsert)) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Lỗi hệ thống: " . mysqli_error($conn);
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
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* --- RESET CSS --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Mulish', sans-serif; }
        body { height: 100vh; display: flex; background: white; overflow: hidden; }
        
        /* --- LAYOUT CHIA ĐÔI --- */
        .container { display: flex; width: 100%; height: 100%; }

        /* 1. LEFT PANEL (Màu Đỏ) */
        .left-panel {
            width: 40%;
            background-color: #791a23; /* Màu đỏ đô chuẩn */
            color: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end; /* Nội dung nằm dưới đáy */
        }
        .left-panel h1 { font-size: 2.8rem; font-weight: 700; line-height: 1.2; margin-bottom: 20px; }
        .left-panel p { font-size: 1.05rem; line-height: 1.6; opacity: 0.9; font-weight: 400; }

        /* 2. RIGHT PANEL (Trắng) */
        .right-panel {
            width: 60%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* --- HEADER BAR (Fixed Top) --- */
        .top-bar {
            height: 80px; /* Chiều cao cố định */
            padding: 0 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            border-bottom: 1px solid #eee; /* Đường kẻ ngang giống mẫu */
            flex-shrink: 0; /* Không bị co lại */
        }
        
        /* Logo Style */
        .brand { display: flex; align-items: center; gap: 8px; color: #791a23; font-weight: 800; font-size: 1.8rem; }
        .brand img {height: 60px; width: auto; display: block; margin: 0 auto; object-fit: contain;}
        /* Login Link Style */
        .login-ask { font-size: 0.95rem; color: #333; font-weight: 600; }
        .login-ask a { color: #791a23; text-decoration: none; font-weight: 800; margin-left: 5px; }

        /* --- SCROLLABLE AREA --- */
        .scroll-content {
            flex: 1;
            overflow-y: auto; /* Cuộn dọc */
            padding: 40px 100px; /* Padding rộng để nội dung vào giữa */
        }
        
        /* Wrapper để giới hạn độ rộng form (giống mẫu) */
        .form-wrapper {
            max-width: 520px;
            margin: 0 auto; /* Căn giữa ngang */
        }

        /* Tiêu đề Form */
        .form-title { margin-bottom: 30px; text-align: left; }
        .form-title h2 { font-size: 1.8rem; color: #791a23; font-weight: 700; margin-bottom: 8px; }
        .form-title p { color: #666; font-size: 1rem; }

        /* Form Controls */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: 0.9rem; font-weight: 700; color: #333; margin-bottom: 8px; }
        .form-label span { color: #d32f2f; }

        .form-control {
            width: 100%; padding: 14px 16px;
            border: 1px solid #ddd; border-radius: 8px;
            font-size: 1rem; outline: none; transition: 0.2s; color: #333;
        }
        .form-control::placeholder { color: #bbb; font-weight: 400; }
        .form-control:focus { border-color: #791a23; box-shadow: 0 0 0 1px #791a23; }
        
        .input-error { border-color: #d32f2f !important; background: #fffafa; }
        .error-msg { color: #d32f2f; font-size: 0.85rem; margin-top: 5px; display: none; }

        /* Grid Row */
        .row { display: flex; gap: 20px; }
        .col { flex: 1; }

        /* --- CUSTOM RADIO BUTTON (STYLE OUTLINE GIỐNG MẪU) --- */
        .gender-options { display: flex; gap: 12px; }
        
        /* Ẩn input gốc */
        .radio-input { display: none; }
        
        /* Label giả làm nút */
        .radio-label {
            flex: 1; 
            padding: 12px;
            border: 1px solid #ddd; border-radius: 8px; 
            cursor: pointer;
            font-size: 0.95rem; color: #555; font-weight: 600;
            display: flex; align-items: center; justify-content: space-between; /* Đẩy chữ và tròn ra 2 bên */
            transition: all 0.2s;
            background: white;
        }

        /* Hình tròn radio bên trong */
        .radio-circle {
            width: 18px; height: 18px; 
            border: 2px solid #ccc; border-radius: 50%;
            position: relative;
        }

        /* Trạng thái CHECKED: Viền đỏ, Chữ đỏ, Chấm đỏ */
        .radio-input:checked + .radio-label {
            border-color: #791a23;
            color: #791a23;
            background: white; /* Giữ nền trắng giống mẫu */
        }
        
        /* Đổi màu viền tròn khi checked */
        .radio-input:checked + .radio-label .radio-circle {
            border-color: #791a23;
        }
        
        /* Dấu chấm bên trong khi checked */
        .radio-input:checked + .radio-label .radio-circle::after {
            content: "";
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 10px; height: 10px; background: #791a23; border-radius: 50%;
        }

        /* Password Eye */
        .password-wrap { position: relative; }
        .toggle-pass { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #999; cursor: pointer; }

        /* Terms */
        .terms-check { display: flex; align-items: flex-start; gap: 10px; font-size: 0.85rem; color: #666; margin: 25px 0; line-height: 1.5; }
        .terms-check input { margin-top: 4px; accent-color: #791a23; width: 16px; height: 16px; cursor: pointer; }
        .terms-check a { color: #791a23; text-decoration: none; font-weight: 700; }

        /* Button */
        .btn-submit {
            width: 100%; padding: 16px;
            background: #f1f2f4; color: #999;
            border: none; border-radius: 8px;
            font-size: 1rem; font-weight: 700; cursor: not-allowed; transition: 0.3s;
        }
        .btn-submit.active { background: #f1f2f4; color: #333; cursor: pointer; border: 1px solid #ddd; }
        .btn-submit.active:hover { background: #e5e5e5; }

        /* Footer */
        .social-divider { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; text-align: center; }
        .social-btns { display: flex; gap: 15px; margin-top: 10px; }
        .btn-social {
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 10px;
            padding: 12px; border: 1px solid #ddd; border-radius: 8px;
            background: white; cursor: pointer; font-weight: 600; color: #333; transition: 0.2s;
        }
        .btn-social:hover { background: #f9f9f9; }

        .copyright { text-align: center; margin-top: 40px; margin-bottom: 20px; font-size: 0.8rem; color: #aaa; }

        /* Responsive Mobile */
        @media (max-width: 1024px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; }
            .scroll-content { padding: 30px 20px; }
            .top-bar { padding: 0 20px; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="left-panel">
            <h1>Tham gia cộng đồng TSix</h1>
            <p>Kết nối với bạn bè, chia sẻ khoảnh khắc và khám phá thế giới xung quanh bạn theo cách hoàn toàn mới.</p>
        </div>

        <div class="right-panel">
            
            <div class="top-bar">
                <div class="brand">
                    <img src="<?= BASE_URL ?>/assets/images/avt.png" alt="TSix Logo">
                </div>
                <div class="login-ask">
                    Bạn đã có tài khoản? <a href="login.php">Đăng nhập</a>
                </div>
            </div>

            <div class="scroll-content">
                <div class="form-wrapper">
                    
                    <div class="form-title">
                        <h2>Đăng ký tài khoản mới</h2>
                        <p>Nhanh chóng và dễ dàng.</p>
                    </div>

                    <?php if($error): ?>
                        <div style="background:#ffebee; color:#c62828; padding:12px; border-radius:8px; font-size:0.9rem; margin-bottom:25px; display:flex; align-items:center; gap:10px;">
                            <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="regForm">
                        <div class="form-group">
                            <label class="form-label">Họ và tên <span>*</span></label>
                            <input type="text" name="fullname" class="form-control" placeholder="Nhập họ và tên của bạn" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email hoặc Số điện thoại <span>*</span></label>
                            <input type="text" name="contact" id="contact" class="form-control" placeholder="example@company.com" required>
                            <div id="ajaxError" class="error-msg">Thông tin này đã được sử dụng!</div>
                        </div>

                        <div class="row">
                            <div class="col form-group">
                                <label class="form-label">Giới tính</label>
                                <div class="gender-options">
                                    <input type="radio" name="gender" value="Nam" id="gMale" class="radio-input" checked>
                                    <label for="gMale" class="radio-label">
                                        Nam <div class="radio-circle"></div>
                                    </label>
                                    
                                    <input type="radio" name="gender" value="Nu" id="gFemale" class="radio-input">
                                    <label for="gFemale" class="radio-label">
                                        Nữ <div class="radio-circle"></div>
                                    </label>
                                    
                                    <input type="radio" name="gender" value="Khac" id="gOther" class="radio-input">
                                    <label for="gOther" class="radio-label">
                                        Khác <div class="radio-circle"></div>
                                    </label>
                                </div>
                            </div>
                            <div class="col form-group">
                                <label class="form-label">Ngày sinh</label>
                                <input type="date" name="dob" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col form-group">
                                <label class="form-label">Mật khẩu <span>*</span></label>
                                <div class="password-wrap">
                                    <input type="password" name="password" id="pass" class="form-control" placeholder="••••••••" required>
                                    <i class="fa-regular fa-eye-slash toggle-pass" onclick="togglePass('pass', this)"></i>
                                </div>
                            </div>
                            <div class="col form-group">
                                <label class="form-label">Xác nhận mật khẩu <span>*</span></label>
                                <div class="password-wrap">
                                    <input type="password" name="confirm_password" id="repass" class="form-control" placeholder="••••••••" required>
                                    <i class="fa-regular fa-eye-slash toggle-pass" onclick="togglePass('repass', this)"></i>
                                </div>
                            </div>
                        </div>

                        <div class="terms-check">
                            <input type="checkbox" id="termsCheck">
                            <label for="termsCheck">
                                Bằng cách nhấp vào Đăng ký, bạn đồng ý với <a href="#">Điều khoản</a>, <a href="#">Chính sách dữ liệu</a> & <a href="#">Chính sách cookie</a> của chúng tôi.
                            </label>
                        </div>

                        <button type="submit" name="btn_register" id="btnSubmit" class="btn-submit">Đăng ký</button>

                        <div class="social-divider">
                            <span style="background:white; padding:0 10px; color:#888; font-size:0.9rem; position:relative; top:-32px;">Đăng ký với</span>
                        </div>
                        <div class="social-btns" style="margin-top:-15px;">
                            <button type="button" class="btn-social">
                                <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="18"> Google
                            </button>
                            <button type="button" class="btn-social">
                                <i class="fa-brands fa-apple" style="font-size:1.2rem;"></i> Apple
                            </button>
                        </div>
                        
                        <div class="copyright">
                            &copy; 2026 TSix Social Network. Bảo mật & Điều khoản
                        </div>
                    </div> </div> </div>
        </div>
    </div>

    <script>
        // Logic ẩn hiện mật khẩu
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

        // Logic Checkbox & Button
        const termsCheck = document.getElementById('termsCheck');
        const btnSubmit = document.getElementById('btnSubmit');
        const contactInput = document.getElementById('contact');

        function checkSubmitState() {
            if (termsCheck.checked && !contactInput.classList.contains('input-error')) {
                btnSubmit.disabled = false;
                btnSubmit.classList.add('active');
            } else {
                btnSubmit.disabled = true;
                btnSubmit.classList.remove('active');
            }
        }
        termsCheck.addEventListener('change', checkSubmitState);

        // Logic AJAX Check
        const ajaxError = document.getElementById('ajaxError');
        contactInput.addEventListener('blur', function() {
            const val = this.value.trim();
            if (val.length > 0) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.responseText.trim() === 'exist') {
                        contactInput.classList.add('input-error');
                        ajaxError.style.display = 'block';
                        btnSubmit.disabled = true;
                        btnSubmit.classList.remove('active');
                    } else {
                        contactInput.classList.remove('input-error');
                        ajaxError.style.display = 'none';
                        checkSubmitState();
                    }
                };
                xhr.send('ajax_check_contact=' + encodeURIComponent(val));
            }
        });
        
        contactInput.addEventListener('input', function() {
            if(this.classList.contains('input-error')) {
                this.classList.remove('input-error');
                ajaxError.style.display = 'none';
            }
        });
    </script>
</body>
</html>