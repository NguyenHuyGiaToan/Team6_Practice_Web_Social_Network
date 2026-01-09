<?php
// edit_profile.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = '';
$msg_type = ''; 

// 2. Xử lý khi bấm nút LƯU
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_save_changes'])) {
    $fullname = trim($_POST['fullname']);
    $bio = trim($_POST['bio']);
    
    if (empty($fullname)) {
        $msg = "Họ tên không được để trống!";
        $msg_type = 'danger';
    } else {
        // --- KHỞI TẠO SQL CƠ BẢN ---
        // Chúng ta sẽ nối chuỗi dần dần để đảm bảo thứ tự luôn đúng
        $sql = "UPDATE Users SET FullName = ?, Bio = ?";
        $params = [$fullname, $bio];
        $types = "ss"; // Tương ứng FullName, Bio

        // --- A. XỬ LÝ ẢNH ĐẠI DIỆN ---
        if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $new_avatar = "avatar_" . $user_id . "_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], "uploads/avatars/" . $new_avatar)) {
                    // Nối thêm vào SQL và Params
                    $sql .= ", Avatar = ?";
                    $params[] = $new_avatar;
                    $types .= "s";
                    
                    $_SESSION['user_avatar'] = $new_avatar; // Cập nhật session
                }
            }
        }

        // --- B. XỬ LÝ ẢNH BÌA ---
        if (isset($_FILES['cover_file']) && $_FILES['cover_file']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['cover_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $new_cover = "cover_" . $user_id . "_" . time() . "." . $ext;
                if (move_uploaded_file($_FILES['cover_file']['tmp_name'], "uploads/avatars/" . $new_cover)) {
                    // Nối thêm vào SQL và Params
                    $sql .= ", CoverImage = ?";
                    $params[] = $new_cover;
                    $types .= "s";
                }
            }
        }

        // --- KẾT THÚC CÂU LỆNH SQL ---
        // Luôn luôn thêm điều kiện WHERE ở cuối cùng
        $sql .= " WHERE UserID = ?";
        $params[] = $user_id;
        $types .= "i"; // Integer cho UserID

        // --- THỰC THI ---
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        
        if (mysqli_stmt_execute($stmt)) {
            $msg = "Cập nhật thành công!";
            $msg_type = 'success';
            $_SESSION['user_name'] = $fullname;
        } else {
            $msg = "Lỗi: " . mysqli_error($conn);
            $msg_type = 'danger';
        }
    }
}

// Xử lý đổi mật khẩu
if (isset($_POST['btn_change_pass'])) {
    $old_pass = $_POST['old_pass'];
    $new_pass = $_POST['new_pass'];
    $cfm_pass = $_POST['cfm_pass'];

    $sql = "SELECT PasswordHash FROM Users WHERE UserID = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!password_verify($old_pass, $data['PasswordHash'])) {
        $msg = "Mật khẩu cũ không đúng!"; $msg_type = 'danger';
    } elseif ($new_pass !== $cfm_pass) {
        $msg = "Mật khẩu xác nhận không khớp!"; $msg_type = 'danger';
    } elseif (strlen($new_pass) < 6) {
        $msg = "Mật khẩu mới phải từ 6 ký tự trở lên!"; $msg_type = 'danger';
    } else {
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $upd = mysqli_prepare($conn, "UPDATE Users SET PasswordHash = ? WHERE UserID = ?");
        mysqli_stmt_bind_param($upd, "si", $new_hash, $user_id);
        if (mysqli_stmt_execute($upd)) {
            $msg = "Đổi mật khẩu thành công!"; $msg_type = 'success';
        }
    }
}

// 3. Lấy dữ liệu user hiện tại
$query = mysqli_query($conn, "SELECT * FROM Users WHERE UserID = $user_id");
$user = mysqli_fetch_assoc($query);

$avatarUrl = !empty($user['Avatar']) ? 'uploads/'.$user['Avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($user['FullName']);
$coverUrl = !empty($user['CoverImage']) ? 'uploads/'.$user['CoverImage'] : ''; 
$coverStyle = !empty($coverUrl) ? "background-image: url('$coverUrl');" : "background-color: #d1d1d1;";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa trang cá nhân</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Mulish', sans-serif; margin: 0; padding: 0; }
        body { background: #e4e6eb; height: 100vh; display: flex; align-items: center; justify-content: center; }
        
        .edit-modal {
            background: #fff; width: 100%; max-width: 700px;
            border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            overflow: hidden; position: relative;
            max-height: 90vh; overflow-y: auto; 
        }
        
        /* Header */
        .modal-header { padding: 15px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { color: #8B1E29; font-size: 1.2rem; font-weight: 700; margin: 0; }
        .close-btn { font-size: 1.5rem; color: #666; cursor: pointer; text-decoration: none; }

        /* Vùng Ảnh Bìa */
        .cover-area {
            height: 200px; width: 100%; 
            background-size: cover; background-position: center;
            position: relative; background-color: #ddd;
        }
        /* Nút đổi ảnh bìa (Thêm z-index để nổi lên trên cùng) */
        .btn-change-cover {
            position: absolute; bottom: 15px; right: 15px;
            background: #fff; padding: 8px 12px; border-radius: 6px;
            font-size: 0.9rem; font-weight: 700; color: #333; cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 6px;
            z-index: 10; /* QUAN TRỌNG: Để không bị vùng avatar che */
        }
        .btn-change-cover:hover { background: #f0f2f5; }

        /* Vùng Avatar (Đã sửa lỗi không bấm được nút bìa) */
        .avatar-area {
            padding: 0 20px; position: relative; margin-top: -60px;
            display: flex; flex-direction: column; 
            pointer-events: none; /* QUAN TRỌNG: Cho phép click xuyên qua vùng trống */
        }
        
        /* Phải bật lại click cho các phần tử con */
        .avatar-wrapper, .user-name {
            pointer-events: auto;
        }

        .avatar-wrapper {
            width: 130px; height: 130px; position: relative;
            border-radius: 50%; border: 4px solid #fff; background: #fff;
        }
        .current-avatar { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        
        /* Nút camera nhỏ ở avatar */
        .btn-cam-avatar {
            position: absolute; bottom: 5px; right: 5px;
            width: 36px; height: 36px; background: #e4e6eb; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            border: 2px solid #fff; font-size: 1.1rem; color: #050505;
        }
        .btn-cam-avatar:hover { background: #d0d2d6; }

        .user-name { margin-top: 10px; font-weight: 700; font-size: 1.5rem; color: #050505; }

        /* Form Body */
        .form-body { padding: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 8px; color: #333; }
        .form-control {
            width: 100%; padding: 12px 15px; border: 1px solid #ced0d4;
            border-radius: 6px; font-size: 1rem; outline: none; transition: 0.2s;
        }
        .form-control:focus { border-color: #8B1E29; box-shadow: 0 0 0 1px #8B1E29; }

        /* Footer */
        .modal-footer {
            padding: 20px; border-top: 1px solid #ddd;
            display: flex; justify-content: flex-end; gap: 10px;
        }
        .btn-cancel { 
            padding: 10px 20px; border-radius: 6px; background: #fff; 
            border: 1px solid #ced0d4; color: #4b4f56; text-decoration: none; font-weight: 600; 
        }
        .btn-save { 
            padding: 10px 20px; border-radius: 6px; background: #8B1E29; 
            border: none; color: #fff; font-weight: 600; cursor: pointer; 
        }
        .btn-save:hover { background: #6b161f; }

        /* Alert & Others */
        .alert { padding: 10px; margin: 10px 20px; border-radius: 6px; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .pass-toggle { color: #0866ff; cursor: pointer; font-weight: 600; margin-bottom: 15px; display: inline-block; }
        .pass-section { display: none; background: #f7f8fa; padding: 15px; border-radius: 8px; }
        input[type="file"] { display: none; }
    </style>
</head>
<body>

    <form method="POST" enctype="multipart/form-data" class="edit-modal">
        <div class="modal-header">
            <h2>Chỉnh sửa trang cá nhân</h2>
            <a href="profile.php" class="close-btn"><i class="fa-solid fa-xmark"></i></a>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="cover-area" id="coverPreview" style="<?php echo $coverStyle; ?>">
            <label for="coverInput" class="btn-change-cover">
                <i class="fa-solid fa-camera"></i> Đổi ảnh bìa
            </label>
            <input type="file" name="cover_file" id="coverInput" accept="image/*" onchange="previewImage(this, 'coverPreview', true)">
        </div>

        <div class="avatar-area">
            <div class="avatar-wrapper">
                <img src="<?php echo $avatarUrl; ?>" class="current-avatar" id="avatarPreview">
                <label for="avatarInput" class="btn-cam-avatar">
                    <i class="fa-solid fa-camera"></i>
                </label>
                <input type="file" name="avatar_file" id="avatarInput" accept="image/*" onchange="previewImage(this, 'avatarPreview', false)">
            </div>
            <div class="user-name"><?php echo htmlspecialchars($user['FullName']); ?></div>
        </div>

        <div class="form-body">
            <div class="form-group">
                <label class="form-label">Họ và tên</label>
                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['FullName']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tiểu sử</label>
                <input type="text" name="bio" class="form-control" value="<?php echo htmlspecialchars($user['Bio'] ?? ''); ?>" placeholder="Mô tả ngắn về bạn...">
            </div>

            <div class="pass-toggle" onclick="document.getElementById('passArea').style.display = 'block'; this.style.display='none'">
                <i class="fa-solid fa-key"></i> Đổi mật khẩu
            </div>
            <div class="pass-section" id="passArea">
                <div class="form-group">
                    <label class="form-label">Mật khẩu hiện tại</label>
                    <input type="password" name="old_pass" class="form-control" placeholder="********">
                </div>
                <div class="form-group" style="display: flex; gap: 10px;">
                    <div style="flex:1">
                        <label class="form-label">Mật khẩu mới</label>
                        <input type="password" name="new_pass" class="form-control" placeholder="Mới">
                    </div>
                    <div style="flex:1">
                        <label class="form-label">Nhập lại</label>
                        <input type="password" name="cfm_pass" class="form-control" placeholder="Xác nhận">
                    </div>
                </div>
                <button type="submit" name="btn_change_pass" class="btn-save" style="background:#555; width:100%">Cập nhật mật khẩu</button>
            </div>
        </div>

        <div class="modal-footer">
            <a href="profile.php" class="btn-cancel">Hủy bỏ</a>
            <button type="submit" name="btn_save_changes" class="btn-save">Lưu thay đổi</button>
        </div>
    </form>

    <script>
        function previewImage(input, targetId, isBackground) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var target = document.getElementById(targetId);
                    if (isBackground) {
                        target.style.backgroundImage = "url('" + e.target.result + "')";
                    } else {
                        target.src = e.target.result;
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>

</body>
</html>