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
                if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], "C:/wamp64/www/web-social-network/uploads/avatars/" . $new_avatar)) {
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
                if (move_uploaded_file($_FILES['cover_file']['tmp_name'], "C:/wamp64/www/web-social-network/uploads/cover_images/" . $new_cover)) {
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

$avatarUrl = !empty($user['Avatar']) ? BASE_URL . 'uploads/avatars/' . $user['Avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($user['FullName']);
$coverUrl = !empty($user['CoverImage']) ? BASE_URL . 'uploads/cover_images/' . $user['CoverImage'] : ''; 
$coverStyle = !empty($coverUrl) ? "background-image: url('$coverUrl');" : "background-color: #d1d1d1;";
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa trang cá nhân</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Mulish:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/Style-css/edit_profile.css">
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