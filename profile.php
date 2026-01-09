<?php
// profile.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// --- XỬ LÝ UPLOAD ẢNH TRỰC TIẾP (Avatar & Cover) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tạo thư mục uploads nếu chưa tồn tại
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }

    // Đổi Avatar
    if (isset($_FILES['direct_avatar']) && $_FILES['direct_avatar']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['direct_avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $new_name = "avatar_" . $current_user_id . "_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['direct_avatar']['tmp_name'], BASE_URL . "uploads/avatars/" . $new_name)) {
                $sql = "UPDATE Users SET Avatar = ? WHERE UserID = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $new_name, $current_user_id);
                mysqli_stmt_execute($stmt);

                // Cập nhật session ngay lập tức
                $_SESSION['user_avatar'] = $new_name;

                header("Location: profile.php" . (isset($_GET['id']) ? '?id=' . $_GET['id'] : ''));
                exit();
            }
        }
    }

    // Đổi Ảnh bìa
    if (isset($_FILES['direct_cover']) && $_FILES['direct_cover']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['direct_cover']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $new_name = "cover_" . $current_user_id . "_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['direct_cover']['tmp_name'], BASE_URL . "uploads/cover_images/" . $new_name)) {
                $sql = "UPDATE Users SET CoverImage = ? WHERE UserID = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $new_name, $current_user_id);
                mysqli_stmt_execute($stmt);

                header("Location: profile.php" . (isset($_GET['id']) ? '?id=' . $_GET['id'] : ''));
                exit();
            }
        }
    }
}
// --- HẾT XỬ LÝ UPLOAD ---

// 2. Xác định profile cần xem
$profile_id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : $current_user_id;
$is_own_profile = ($current_user_id === $profile_id);

// 3. Lấy dữ liệu người dùng
$sql = "SELECT * FROM Users WHERE UserID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $profile_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("Không tìm thấy người dùng này!");
}

// Cập nhật session với thông tin user hiện tại (để header dùng avatar + fullname)
$_SESSION['user_fullname'] = $user['FullName'];
$_SESSION['user_avatar']    = $user['Avatar'] ?? null;

// 4. Xử lý hiển thị Avatar và Cover
$avatar_url = !empty($user['Avatar'])
    ? BASE_URL . 'uploads/avatars/' . htmlspecialchars($user['Avatar'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['FullName']) . '&background=8B1E29&color=fff&size=400';

$cover_style = !empty($user['CoverImage'])
    ? "background-image: url('" . BASE_URL . "uploads/cover_images/" . htmlspecialchars($user['CoverImage']) . "');"
    : "background-color: #d1d1d1;";

// Định dạng ngày sinh và giới tính
$birthDate = !empty($user['BirthDate']) 
    ? date("d/m", strtotime($user['BirthDate'])) 
    : "Chưa cập nhật";

$genderTxt = ($user['Gender'] === 'Nam') ? 'Nam' 
           : ($user['Gender'] === 'Nu' ? 'Nữ' : 'Khác');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['FullName']); ?> - TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* RESET & GLOBAL */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: #f0f2f5; color: #050505; }

        /* Container */
        .container { max-width: 1095px; margin: 0 auto; padding: 0 16px; }

        /* Profile Header */
        .profile-header { background: #fff; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin: 20px 0; overflow: hidden; }

        .cover-photo {
            width: 100%; height: 350px;
            background-size: cover; background-position: center;
            position: relative;
        }

        .btn-update-cover {
            position: absolute; bottom: 15px; right: 30px;
            background: #fff; padding: 8px 12px; border-radius: 6px;
            font-weight: 600; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.2);
            display: flex; align-items: center; gap: 6px;
        }
        .btn-update-cover:hover { background: #f0f2f5; }

        .header-details { padding: 20px 16px 16px; position: relative; }

        .header-top-row {
            display: flex; justify-content: space-between; align-items: flex-end;
            padding-bottom: 20px; border-bottom: 1px solid #ced0d4;
        }

        .user-identity { display: flex; align-items: flex-end; gap: 20px; }

        .avatar-container { margin-top: -80px; position: relative; z-index: 2; }
        .big-avatar {
            width: 168px; height: 168px; border-radius: 50%;
            border: 4px solid #fff; object-fit: cover; background: #fff;
        }

        .btn-update-avatar {
            position: absolute; bottom: 10px; right: 10px;
            width: 36px; height: 36px; background: #e4e6eb; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; border: 2px solid #fff;
        }
        .btn-update-avatar:hover { background: #d8dadf; }

        .fullname { font-size: 2rem; font-weight: 800; margin-bottom: 4px; }
        .stats-text { color: #65676b; font-size: 0.95rem; }
        .stats-text b { color: #050505; font-weight: 600; }

        .action-buttons { display: flex; gap: 8px; margin-top: 10px; }

        .btn-blue {
            background: #0866ff; color: white; border: none; border-radius: 6px;
            padding: 0 16px; height: 36px; font-weight: 600; display: flex; align-items: center; gap: 6px;
        }
        .btn-blue:hover { background: #0055d4; }

        .btn-gray {
            background: #e4e6eb; color: #050505; border: none; border-radius: 6px;
            padding: 0 16px; height: 36px; font-weight: 600; display: flex; align-items: center; gap: 6px;
        }
        .btn-gray:hover { background: #d8dadf; }

        .profile-menu {
            display: flex; gap: 4px; padding: 8px 16px 0;
        }
        .menu-item {
            padding: 12px 16px; font-weight: 600; color: #65676b; cursor: pointer; border-radius: 8px 8px 0 0;
        }
        .menu-item:hover { background: #f0f2f5; }
        .menu-item.active { color: #0866ff; border-bottom: 3px solid #0866ff; }

        /* Body */
        .profile-body {
            display: grid; grid-template-columns: 38% 60%; gap: 16px; margin-top: 20px; padding-bottom: 50px;
        }

        .card {
            background: #fff; border-radius: 8px; padding: 16px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 16px;
        }
        .card-title { font-size: 1.25rem; font-weight: 700; margin-bottom: 16px; }

        .intro-item {
            display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px;
            font-size: 0.95rem; color: #050505;
        }
        .intro-item i { color: #8c939d; width: 24px; font-size: 1.2rem; }

        .photo-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; border-radius: 8px; overflow: hidden;
        }
        .photo-grid img { width: 100%; aspect-ratio: 1/1; object-fit: cover; }

        /* Tăng độ bo góc và độ đậm khối cho khung tổng thể */
        .create-post { 
            margin-bottom: 20px; 
            background: #fff; 
            border-radius: 15px; /* Bo tròn góc khung ngoài nhiều hơn */
            padding: 15px; 
            /* Tăng độ đậm khối bằng cách chỉnh thông số box-shadow */
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
        }

        /* Căn chỉnh phần top */
        .create-post-top { 
            display: flex; 
            align-items: center; /* Căn giữa ảnh và khung nhập theo chiều dọc */
            gap: 12px; 
            padding-bottom: 15px; 
            margin-bottom: 12px; 
            border-bottom: 1px solid #e4e6eb; 
        }

        .create-post-top img { 
            width: 45px; 
            height: 45px; 
            border-radius: 50%; 
            object-fit: cover; 
            cursor: pointer;
            transition: filter 0.2s;
        }

        .create-post-top img:hover {
            filter: brightness(0.9);
        }
        /* Điều chỉnh khung "Bạn đang nghĩ gì" */
        .input-mind-trigger { 
            flex: 1; 
            padding: 12px 20px; 
            background: #f0f2f5; 
            border-radius: 25px; /* Bo tròn mạnh hai đầu */
            cursor: pointer; 
            font-size: 1rem; 
            color: gray;
            font-weight: bold;
            display: flex;
            align-items: center; 
            transition: background 0.2s;
        }

        .input-mind-trigger:hover {
            background: #8B1E29; /* Hiệu ứng hover cho khung nhập */
        }
        
        /* Các nút hành động (Ảnh, Cảm xúc) */
        .post-actions { 
            display: flex; 
            justify-content: space-around; 
        }

        .action-btn { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            padding: 10px 20px; 
            color: #65676b; 
            cursor: pointer; 
            border-radius: 8px; 
            font-weight: 600; 
            transition: background 0.2s;
        }

        .action-btn:hover { 
            background: #f0f2f5; 
        }

        .post { background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 16px; }
        .poster-info { display: flex; gap: 10px; margin-bottom: 12px; align-items: center; }
        .poster-name { font-weight: 600; }
        .post-meta { font-size: 0.8rem; color: #65676b; }
        .post-caption { margin-bottom: 12px; font-size: 0.95rem; }
        .post-img { width: 100%; border-radius: 8px; margin: 12px 0; }
        .post-stats { display: flex; justify-content: space-between; padding: 10px 0; border-top: 1px solid #e4e6eb; border-bottom: 1px solid #e4e6eb; color: #65676b; }

        @media (max-width: 900px) {
            .profile-body { grid-template-columns: 1fr; }
            .header-top-row { flex-direction: column; align-items: center; text-align: center; }
            .user-identity { flex-direction: column; gap: 10px; }
            .avatar-container { margin-top: -100px; }
            .action-buttons { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <div class="profile-header">
        <div class="container">
            <div class="cover-photo" style="<?php echo $cover_style; ?>">
                <?php if ($is_own_profile): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <label for="upload_cover" class="btn-update-cover">
                        <i class="fa-solid fa-camera"></i> Đổi ảnh bìa
                    </label>
                    <input type="file" name="direct_cover" id="upload_cover" accept="image/*" style="display:none;" onchange="this.form.submit()">
                </form>
                <?php endif; ?>
            </div>

            <div class="header-details">
                <div class="header-top-row">
                    <div class="user-identity">
                        <div class="avatar-container">
                            <img src="<?php echo $avatar_url; ?>" class="big-avatar" alt="Avatar">
                            <?php if ($is_own_profile): ?>
                            <form action="" method="POST" enctype="multipart/form-data">
                                <label for="upload_avatar" class="btn-update-avatar">
                                    <i class="fa-solid fa-camera"></i>
                                </label>
                                <input type="file" name="direct_avatar" id="upload_avatar" accept="image/*" style="display:none;" onchange="this.form.submit()">
                            </form>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h1 class="fullname"><?php echo htmlspecialchars($user['FullName']); ?></h1>
                            <div class="stats-text">
                                <b>123</b> Người theo dõi • <b>45</b> Đang theo dõi
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <?php if ($is_own_profile): ?>
                            <button class="btn-gray" onclick="window.location.href='edit_profile.php'">
                                <i class="fa-solid fa-pen"></i> Chỉnh sửa trang cá nhân
                            </button>
                        <?php else: ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-menu">
                    <div class="menu-item active">Bài viết</div>
                    <div class="menu-item">Giới thiệu</div>
                    <div class="menu-item">Bạn bè</div>
                    <div class="menu-item">Ảnh</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="profile-body">
            <!-- Cột trái -->
            <div class="left-col">
                <div class="card">
                    <div class="card-title">Giới thiệu</div>
                    <?php if (!empty($user['Bio'])): ?>
                        <div style="text-align:center; margin-bottom:16px; color:#65676b;">
                            <?php echo htmlspecialchars($user['Bio']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="intro-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Sống tại <b>TP. Hồ Chí Minh</b></span>
                    </div>
                    <div class="intro-item">
                        <i class="fa-solid fa-cake-candles"></i>
                        <span>Sinh ngày <b><?php echo $birthDate; ?></b></span>
                    </div>
                    <div class="intro-item">
                        <i class="fa-solid fa-venus-mars"></i>
                        <span>Giới tính <b><?php echo $genderTxt; ?></b></span>
                    </div>
                    <div class="intro-item">
                        <i class="fa-solid fa-envelope"></i>
                        <span><?php echo htmlspecialchars($user['Email']); ?></span>
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Ảnh</div>
                    <div class="photo-grid">
                        <?php for($i=1; $i<=9; $i++): ?>
                        <img src="https://picsum.photos/200?random=<?php echo $i; ?>">
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Cột phải -->
            <div class="right-col">
                <?php if ($is_own_profile): ?>
                <div class="create-post">
                    <div class="create-post-top">
                        <img src="<?php echo $avatar_url; ?>" onclick="window.location.href='profile.php'">
                        <div class="input-mind-trigger" onclick="window.location.href='post.php'">
                            Bạn đang nghĩ gì, <?php echo explode(' ', $_SESSION['user_fullname'])[0]; ?>?
                        </div>
                    </div>
                    <div class="post-actions">
                        <div class="action-btn" onclick="window.location.href='post.php'"><i class="fa-solid fa-image" style="color:#45bd62"></i> Ảnh</div>
                        <div class="action-btn" onclick="window.location.href='post.php'"><i class="fa-regular fa-face-smile" style="color:#f7b928"></i> Cảm xúc</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Bài viết mẫu -->
                <div class="card post">
                    <div class="poster-info">
                        <img src="<?php echo $avatar_url; ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                        <div>
                            <div class="poster-name"><?php echo htmlspecialchars($user['FullName']); ?></div>
                            <div class="post-meta">Vừa xong <i class="fa-solid fa-earth-americas"></i></div>
                        </div>
                        <i class="fa-solid fa-ellipsis" style="margin-left:auto; color:#65676b; cursor:pointer;"></i>
                    </div>
                    <div class="post-caption">Happy New Year 2026!!! 🎉🎉</div>
                    <img src="https://picsum.photos/800/500?random=99" class="post-img" alt="Post">
                    <div class="post-stats">
                        <div><span style="background:#0866ff;color:white;border-radius:50%;padding:2px 6px;font-size:0.8rem;">👍</span> 1.2K</div>
                        <div>234 Bình luận • 56 Chia sẻ</div>
                    </div>
                    <div class="post-actions" style="border-top:1px solid #e4e6eb; padding-top:8px;">
                        <div class="action-btn"><i class="fa-regular fa-thumbs-up"></i> Thích</div>
                        <div class="action-btn"><i class="fa-regular fa-message"></i> Bình luận</div> 
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>