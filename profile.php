<?php
// profile.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// 1. Kiểm tra đăng nhập
if (!isLoggedIn()) {
    redirect('login.php');
}

$current_user_id = $_SESSION['user_id'];
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : $current_user_id;
$is_own_profile = ($current_user_id == $profile_id);

// 2. Xử lý Cập nhật thông tin (Khi submit form từ Modal)
if ($is_own_profile && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_save_profile'])) {
    $fullname = $_POST['fullname'];
    $bio = $_POST['bio'];
    $address = $_POST['address'];
    $gender = $_POST['gender'];
    
    // Upload Avatar
    if (!empty($_FILES['avatar']['name'])) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $avatarName = "avatar_" . $current_user_id . "_" . time() . "." . $ext;
        move_uploaded_file($_FILES['avatar']['tmp_name'], "uploads/" . $avatarName);
        $conn->prepare("UPDATE Users SET Avatar = ? WHERE UserID = ?")->execute([$avatarName, $current_user_id]);
    }
    
    // Upload Cover
    if (!empty($_FILES['cover']['name'])) {
        $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
        $coverName = "cover_" . $current_user_id . "_" . time() . "." . $ext;
        move_uploaded_file($_FILES['cover']['tmp_name'], "uploads/" . $coverName);
        // Lưu ý: Cần có cột CoverImage trong DB. Nếu chưa có, lệnh SQL dưới sẽ lỗi nhẹ hoặc cần thêm cột.
        // Tạm thời tôi giả định bạn đã thêm cột CoverImage vào bảng Users.
        $conn->prepare("UPDATE Users SET CoverImage = ? WHERE UserID = ?")->execute([$coverName, $current_user_id]);
    }

    // Cập nhật thông tin text
    // Giả định bảng Users có cột Bio, Address. Nếu chưa có bạn cần vào phpMyAdmin thêm.
    // ALTER TABLE Users ADD Bio TEXT, ADD Address VARCHAR(255);
    $sql = "UPDATE Users SET FullName = ?, Bio = ?, Address = ?, Gender = ? WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$fullname, $bio, $address, $gender, $current_user_id]);
    
    // Refresh lại trang để thấy thay đổi
    header("Location: profile.php");
    exit();
}

// 3. Lấy dữ liệu người dùng
$stmt = $conn->prepare("SELECT * FROM Users WHERE UserID = ?");
$stmt->execute([$profile_id]);
$user = $stmt->fetch();

if (!$user) die("Người dùng không tồn tại");

// 4. Lấy thống kê (Giả lập số liệu hoặc query thật)
$stmtStats = $conn->prepare("SELECT COUNT(*) FROM Posts WHERE FK_UserID = ?");
$stmtStats->execute([$profile_id]);
$count_posts = $stmtStats->fetchColumn();
$count_followers = 123; // Demo số tĩnh
$count_following = 123; // Demo số tĩnh

// 5. Lấy ảnh (Photos)
$stmtPhotos = $conn->prepare("SELECT ImageUrl FROM Post_Images pi JOIN Posts p ON pi.FK_PostID = p.PostID WHERE p.FK_UserID = ? ORDER BY p.CreatedAt DESC LIMIT 9");
$stmtPhotos->execute([$profile_id]);
$photos = $stmtPhotos->fetchAll();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($user['FullName']); ?> | TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- RESET & VARIABLES (LIGHT THEME) --- */
        :root {
            --bg-body: #f0f2f5;
            --bg-card: #ffffff;
            --text-main: #050505;
            --text-gray: #65676b;
            --primary: #1877f2;
            --border: #ddd;
            --radius: 8px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--bg-body); color: var(--text-main); }
        a { text-decoration: none; color: inherit; }
        
        /* --- NAVBAR --- */
        .navbar {
            background: var(--bg-card); height: 56px; padding: 0 16px;
            display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100;
        }
        .nav-left { display: flex; align-items: center; gap: 10px; }
        .logo { width: 40px; height: 40px; background: var(--primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 24px; }
        .search-box { background: #f0f2f5; padding: 10px 15px; border-radius: 20px; color: var(--text-gray); width: 250px; display: flex; align-items: center; gap: 10px; }
        .search-box input { border: none; background: transparent; outline: none; width: 100%; }
        
        .nav-middle { display: flex; gap: 10px; height: 100%; }
        .nav-icon { width: 100px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--text-gray); border-bottom: 3px solid transparent; cursor: pointer; }
        .nav-icon.active { color: var(--primary); border-bottom-color: var(--primary); }
        .nav-icon:hover:not(.active) { background: #f2f2f2; border-radius: 8px; }

        .nav-right { display: flex; gap: 10px; align-items: center; }
        .circle-btn { width: 40px; height: 40px; background: #e4e6eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; cursor: pointer; }
        .user-menu-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }

        /* --- CONTAINER --- */
        .container { max-width: 1050px; margin: 0 auto; }

        /* --- HEADER PROFILE --- */
        .profile-header-wrap { background: var(--bg-card); box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 16px; padding-bottom: 16px; }
        .cover-photo {
            width: 100%; height: 350px; background: #ccc; position: relative; border-bottom-left-radius: 10px; border-bottom-right-radius: 10px;
            background-image: url('uploads/<?php echo !empty($user['CoverImage']) ? $user['CoverImage'] : "default_cover.jpg"; ?>');
            background-size: cover; background-position: center;
        }
        .profile-info-section { padding: 0 32px; position: relative; display: flex; justify-content: space-between; align-items: flex-end; margin-top: -30px; }
        .profile-pic-wrap { position: relative; padding: 4px; background: var(--bg-card); border-radius: 50%; margin-top: -130px; }
        .profile-pic { width: 168px; height: 168px; border-radius: 50%; object-fit: cover; border: 4px solid var(--bg-card); }
        
        .name-details { margin-left: 20px; margin-bottom: 10px; flex: 1; }
        .name-details h1 { font-size: 32px; font-weight: bold; margin-bottom: 0; }
        .name-details .bio { font-size: 15px; color: var(--text-gray); font-weight: 600; margin-bottom: 5px; }
        .stats-text { font-size: 14px; color: var(--text-gray); }
        .stats-text strong { color: var(--text-main); margin-right: 5px; }
        .stats-text span { margin-right: 15px; }

        .edit-profile-btn {
            background: var(--primary); color: #fff; border: none; padding: 0 16px; height: 36px; border-radius: 6px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; margin-bottom: 15px;
        }
        .edit-profile-btn:hover { background: #166fe5; }

        .profile-tabs { display: flex; padding: 0 32px; margin-top: 20px; border-top: 1px solid #ddd; padding-top: 5px; }
        .p-tab { padding: 15px 20px; font-weight: 600; color: var(--text-gray); cursor: pointer; border-radius: 6px; }
        .p-tab.active { color: var(--primary); border-bottom: 3px solid var(--primary); border-radius: 0; }
        .p-tab:hover:not(.active) { background: #f2f2f2; }

        /* --- CONTENT GRID --- */
        .content-area { display: grid; grid-template-columns: 400px 1fr; gap: 16px; padding: 0 10px; }
        
        /* Sidebar Cards */
        .card { background: var(--bg-card); border-radius: var(--radius); padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .card h3 { font-size: 20px; font-weight: bold; margin-bottom: 16px; }
        
        .intro-item { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 15px; color: var(--text-main); }
        .intro-item i { width: 20px; color: var(--text-gray); text-align: center; }
        
        .photo-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; border-radius: 8px; overflow: hidden; }
        .photo-grid img { width: 100%; height: 120px; object-fit: cover; }

        /* Feed */
        .create-post { background: var(--bg-card); padding: 12px 16px; border-radius: var(--radius); box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 16px; }
        .cp-top { display: flex; gap: 10px; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #eff2f5; }
        .cp-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .cp-input { background: #f0f2f5; border-radius: 20px; flex: 1; padding: 10px 15px; border: none; outline: none; cursor: pointer; color: var(--text-gray); }
        .cp-input:hover { background: #e4e6eb; }
        .cp-actions { display: flex; justify-content: space-around; }
        .cp-btn { display: flex; align-items: center; gap: 8px; color: var(--text-gray); font-weight: 600; cursor: pointer; padding: 8px; border-radius: 8px; }
        .cp-btn:hover { background: #f2f2f2; }

        /* Post */
        .post { background: var(--bg-card); border-radius: var(--radius); box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 16px; padding: 12px 16px; }
        .post-header { display: flex; justify-content: space-between; margin-bottom: 12px; }
        .ph-left { display: flex; gap: 10px; }
        .ph-info h4 { font-size: 15px; font-weight: 600; margin-bottom: 2px; }
        .ph-info span { font-size: 13px; color: var(--text-gray); }
        .post-caption { font-size: 15px; margin-bottom: 12px; }
        .post-img { width: calc(100% + 32px); margin-left: -16px; margin-bottom: 10px; }
        .post-stats { display: flex; justify-content: space-between; font-size: 14px; color: var(--text-gray); padding-bottom: 10px; border-bottom: 1px solid #eff2f5; }
        .post-actions { display: flex; justify-content: space-around; padding-top: 5px; }
        .pa-btn { flex: 1; display: flex; justify-content: center; align-items: center; gap: 8px; padding: 8px; border-radius: 4px; cursor: pointer; color: var(--text-gray); font-weight: 600; }
        .pa-btn:hover { background: #f2f2f2; }

        /* --- MODAL EDIT --- */
        .modal { display: none; position: fixed; z-index: 200; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .modal-content { background-color: #fff; margin: auto; padding: 0; border: 1px solid #888; width: 700px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); animation: fadeIn 0.3s; }
        .modal-header { padding: 15px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 20px; font-weight: bold; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }
        .modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
        .form-row { margin-bottom: 15px; }
        .form-row label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 14px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; }
        .modal-footer { padding: 15px 20px; border-top: 1px solid #ddd; text-align: right; }
        
        @keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }
    </style>
</head>
<body>

    <div class="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo">T</a>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Tìm kiếm trên TSix">
            </div>
        </div>
        <div class="nav-middle">
            <div class="nav-icon"><i class="fas fa-home"></i></div>
            <div class="nav-icon"><i class="fas fa-user-friends"></i></div>
            <div class="nav-icon"><i class="fas fa-video"></i></div>
            <div class="nav-icon"><i class="fas fa-store"></i></div>
            <div class="nav-icon"><i class="fas fa-users"></i></div>
        </div>
        <div class="nav-right">
            <div class="circle-btn"><i class="fas fa-bars"></i></div>
            <div class="circle-btn"><i class="fab fa-facebook-messenger"></i></div>
            <div class="circle-btn"><i class="fas fa-bell"></i></div>
            <a href="profile.php"><img src="uploads/<?php echo !empty($user['Avatar']) ? $user['Avatar'] : 'default_avatar.png'; ?>" class="user-menu-img"></a>
            <a href="logout.php" class="circle-btn" title="Đăng xuất"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>

    <div class="container">
        <div class="profile-header-wrap">
            <div class="cover-photo"></div>
            <div class="profile-info-section">
                <div style="display: flex; align-items: flex-end;">
                    <div class="profile-pic-wrap">
                        <img src="uploads/<?php echo !empty($user['Avatar']) ? $user['Avatar'] : 'default_avatar.png'; ?>" class="profile-pic">
                    </div>
                    <div class="name-details">
                        <h1><?php echo htmlspecialchars($user['FullName']); ?></h1>
                        <div class="bio"><?php echo htmlspecialchars($user['Bio'] ?? 'IT Specialist'); ?></div>
                        <div class="bio" style="font-weight: normal; font-size: 14px; margin-bottom: 5px;">
                            <?php echo htmlspecialchars($user['Bio'] ?? 'Sinh viên K39 - Khoa Hệ thống thông tin quản lý Trường Đại học Ngân hàng TPHCM'); ?>
                        </div>
                        <div class="stats-text">
                            <span><strong><?php echo $count_posts; ?></strong> Bài viết</span>
                            <span><strong><?php echo $count_followers; ?></strong> Người theo dõi</span>
                            <span><strong><?php echo $count_following; ?></strong> Đang theo dõi</span>
                        </div>
                    </div>
                </div>
                
                <?php if($is_own_profile): ?>
                    <button class="edit-profile-btn" onclick="openModal()"><i class="fas fa-pen"></i> Chỉnh sửa trang cá nhân</button>
                <?php else: ?>
                    <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                        <button class="edit-profile-btn"><i class="fas fa-user-plus"></i> Theo dõi</button>
                        <button class="edit-profile-btn" style="background: #e4e6eb; color: #000;"><i class="fab fa-facebook-messenger"></i> Nhắn tin</button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="profile-tabs">
                <div class="p-tab active">Bài viết</div>
                <div class="p-tab">Giới thiệu</div>
                <div class="p-tab">Ảnh</div>
                <div class="p-tab">Video</div>
                <div class="p-tab">Xem thêm</div>
            </div>
        </div>

        <div class="content-area">
            <div class="left-sidebar">
                <div class="card">
                    <h3>Giới thiệu</h3>
                    <div class="intro-item">
                        <i class="fas fa-graduation-cap"></i> 
                        <span>Đã học tại <strong>HUB</strong></span>
                    </div>
                    <div class="intro-item">
                        <i class="fas fa-home"></i> 
                        <span>Sống tại <strong><?php echo $user['Address'] ? $user['Address'] : 'TPHCM'; ?></strong></span>
                    </div>
                    <div class="intro-item">
                        <i class="fas fa-birthday-cake"></i> 
                        <span>Sinh ngày <strong><?php echo date("d/m", strtotime($user['BirthDate'])); ?></strong></span>
                    </div>
                    <div class="intro-item">
                        <i class="fas fa-venus-mars"></i> 
                        <span>Giới tính <strong><?php echo ($user['Gender']=='Nam'?'Nam': ($user['Gender']=='Nu'?'Nữ':'Khác')); ?></strong></span>
                    </div>
                    <div class="intro-item">
                        <i class="fas fa-envelope"></i> 
                        <span><?php echo $user['Email']; ?></span>
                    </div>
                    <div class="intro-item">
                        <i class="fas fa-clock"></i> 
                        <span>Tham gia tháng <?php echo date("m", strtotime($user['CreatedAt'])); ?> năm <?php echo date("Y", strtotime($user['CreatedAt'])); ?></span>
                    </div>
                </div>

                <div class="card">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <h3>Ảnh</h3>
                        <a href="#" style="color:var(--primary); font-size:15px;">Xem tất cả</a>
                    </div>
                    <div class="photo-grid">
                        <?php 
                        if ($photos) {
                            foreach($photos as $p) {
                                echo "<img src='uploads/".$p['ImageUrl']."'>";
                            }
                        } else {
                            echo "<div style='grid-column: span 3; color: #777; text-align:center;'>Chưa có ảnh</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="main-feed">
                <?php if($is_own_profile): ?>
                <div class="create-post">
                    <div class="cp-top">
                        <img src="uploads/<?php echo !empty($user['Avatar']) ? $user['Avatar'] : 'default_avatar.png'; ?>" class="cp-avatar">
                        <input type="text" class="cp-input" placeholder="Bạn đang nghĩ gì <?php echo $user['FullName']; ?>?">
                    </div>
                    <div class="cp-actions">
                        <div class="cp-btn"><i class="fas fa-video" style="color: #f3425f;"></i> Video trực tiếp</div>
                        <div class="cp-btn"><i class="fas fa-images" style="color: #45bd62;"></i> Ảnh/Video</div>
                        <div class="cp-btn"><i class="fas fa-flag" style="color: #f7b928;"></i> Sự kiện</div>
                    </div>
                </div>
                <?php endif; ?>

                <?php
                $stmtPosts = $conn->prepare("SELECT p.*, u.FullName, u.Avatar FROM Posts p JOIN Users u ON p.FK_UserID = u.UserID WHERE p.FK_UserID = ? ORDER BY p.CreatedAt DESC");
                $stmtPosts->execute([$profile_id]);
                while ($post = $stmtPosts->fetch()) {
                    // Lấy ảnh bài viết
                    $stmtPImg = $conn->prepare("SELECT ImageUrl FROM Post_Images WHERE FK_PostID = ? LIMIT 1");
                    $stmtPImg->execute([$post['PostID']]);
                    $pImg = $stmtPImg->fetch();
                ?>
                <div class="post">
                    <div class="post-header">
                        <div class="ph-left">
                            <img src="uploads/<?php echo !empty($post['Avatar']) ? $post['Avatar'] : 'default_avatar.png'; ?>" class="cp-avatar">
                            <div class="ph-info">
                                <h4><?php echo $post['FullName']; ?></h4>
                                <span><?php echo timeAgo($post['CreatedAt']); ?> · <i class="fas fa-globe-asia"></i></span>
                            </div>
                        </div>
                        <i class="fas fa-ellipsis-h" style="color:var(--text-gray); cursor:pointer;"></i>
                    </div>
                    <div class="post-caption">
                        <?php echo nl2br(htmlspecialchars($post['Content'])); ?>
                    </div>
                    <?php if($pImg): ?>
                        <img src="uploads/<?php echo $pImg['ImageUrl']; ?>" class="post-img">
                    <?php endif; ?>
                    <div class="post-stats">
                        <span><i class="fas fa-thumbs-up" style="color:var(--primary);"></i> <?php echo $post['LikeCount']; ?></span>
                        <span><?php echo $post['CommentCount']; ?> Bình luận</span>
                    </div>
                    <div class="post-actions">
                        <div class="pa-btn"><i class="far fa-thumbs-up"></i> Thích</div>
                        <div class="pa-btn"><i class="far fa-comment-alt"></i> Bình luận</div>
                        <div class="pa-btn"><i class="fas fa-share"></i> Chia sẻ</div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Chỉnh sửa trang cá nhân</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <label style="font-weight: bold;">Ảnh đại diện</label><br>
                        <img src="uploads/<?php echo !empty($user['Avatar']) ? $user['Avatar'] : 'default_avatar.png'; ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 10px 0;"><br>
                        <input type="file" name="avatar">
                    </div>
                    <div style="text-align: center; margin-bottom: 20px;">
                        <label style="font-weight: bold;">Ảnh bìa</label><br>
                        <input type="file" name="cover">
                    </div>
                    <div class="form-row">
                        <label>Tên hiển thị</label>
                        <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['FullName']); ?>" required>
                    </div>
                    <div class="form-row">
                        <label>Tiểu sử (Bio)</label>
                        <input type="text" name="bio" class="form-control" value="<?php echo htmlspecialchars($user['Bio'] ?? ''); ?>">
                    </div>
                    <div class="form-row">
                        <label>Sống tại</label>
                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['Address'] ?? ''); ?>">
                    </div>
                    <div class="form-row">
                        <label>Giới tính</label>
                        <select name="gender" class="form-control">
                            <option value="Nam" <?php if($user['Gender']=='Nam') echo 'selected'; ?>>Nam</option>
                            <option value="Nu" <?php if($user['Gender']=='Nu') echo 'selected'; ?>>Nữ</option>
                            <option value="Khac" <?php if($user['Gender']=='Khac') echo 'selected'; ?>>Khác</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="edit-profile-btn" style="background: #e4e6eb; color: #000; display: inline-block; margin-bottom:0;" onclick="closeModal()">Hủy</button>
                    <button type="submit" name="btn_save_profile" class="edit-profile-btn" style="display: inline-block; margin-bottom:0;">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Logic
        var modal = document.getElementById("editModal");
        function openModal() { modal.style.display = "flex"; }
        function closeModal() { modal.style.display = "none"; }
        window.onclick = function(event) {
            if (event.target == modal) { modal.style.display = "none"; }
        }
    </script>

</body>
</html>