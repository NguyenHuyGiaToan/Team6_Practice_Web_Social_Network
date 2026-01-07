<?php
// profile.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// 2. Xác định Profile cần xem (Của mình hay của người khác?)
// Nếu có ?id=... trên URL thì xem người đó, ngược lại xem chính mình
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $profile_id = $_GET['id'];
} else {
    $profile_id = $current_user_id;
}

// Kiểm tra xem đây có phải là trang của chính mình không
$is_own_profile = ($current_user_id == $profile_id);

// 3. Lấy dữ liệu người dùng từ Database
$sql = "SELECT * FROM Users WHERE UserID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $profile_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("Không tìm thấy người dùng này!"); // Hoặc chuyển hướng về 404
}

// 4. Xử lý hiển thị (Avatar/Cover)
$avatar = !empty($user['Avatar']) 
    ? 'uploads/' . $user['Avatar'] 
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['FullName']) . '&background=random&size=200';

$cover_style = !empty($user['CoverPhoto']) 
    ? "background-image: url('uploads/{$user['CoverPhoto']}');" 
    : "background-color: #d1d1d1;"; 

// Định dạng ngày sinh
$birthDate = !empty($user['BirthDate']) ? date("d/m", strtotime($user['BirthDate'])) : "Chưa cập nhật";
$genderTxt = ($user['Gender'] == 'Nu') ? 'Nữ' : ($user['Gender'] == 'Nam' ? 'Nam' : 'Khác');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['FullName']); ?> - TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* --- RESET & GLOBAL --- */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Helvetica, Arial, sans-serif; }
        body { background-color: #f0f2f5; color: #050505; overflow-y: scroll; }
        a { text-decoration: none; color: inherit; }
        ul { list-style: none; }
        button { cursor: pointer; outline: none; }

        /* --- NAVBAR (Giữ nguyên) --- */
        .navbar {
            background: #fff; height: 60px; padding: 0 16px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 1000;
        }
        .nav-left { display: flex; align-items: center; gap: 10px; }
        .logo { color: #8B1E29; font-weight: 800; font-size: 2rem; letter-spacing: -1px; }
        .search-box { background: #f0f2f5; padding: 10px 16px; border-radius: 50px; display: flex; align-items: center; width: 240px; }
        .search-box input { border: none; background: transparent; outline: none; margin-left: 8px; font-size: 0.95rem; width: 100%; }
        
        .nav-center { display: flex; height: 100%; gap: 8px; }
        .nav-item { padding: 0 35px; display: flex; align-items: center; height: 100%; color: #65676b; cursor: pointer; border-bottom: 3px solid transparent; font-size: 1.5rem; }
        .nav-item:hover { background: #f2f2f2; border-radius: 8px; }
        .nav-item.active { color: #8B1E29; border-bottom-color: #8B1E29; border-radius: 0; }
        
        .nav-right { display: flex; align-items: center; gap: 10px; }
        .nav-icon-circle { width: 40px; height: 40px; background: #e4e6eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 1.2rem; }
        .nav-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; cursor: pointer; }

        /* --- LAYOUT CONTAINER --- */
        .container { max-width: 1095px; margin: 0 auto; }

        /* --- PROFILE HEADER --- */
        .profile-header { background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.1); padding-bottom: 0; margin-bottom: 16px; }
        
        /* Ảnh bìa */
        .cover-photo {
            width: 100%; height: 350px;
            background-size: cover; background-position: center;
            border-bottom-left-radius: 8px; border-bottom-right-radius: 8px;
            position: relative;
        }

        /* Thông tin User */
        .header-details {
            max-width: 1030px; margin: 0 auto; padding: 0 16px;
            position: relative; padding-bottom: 16px;
        }

        .header-top-row { display: flex; align-items: flex-end; justify-content: space-between; padding-bottom: 20px; border-bottom: 1px solid #ced0d4; }
        
        .user-identity { display: flex; align-items: flex-end; gap: 20px; }
        
        /* Avatar đè lên ảnh bìa */
        .avatar-container { margin-top: -30px; position: relative; }
        .big-avatar {
            width: 168px; height: 168px; border-radius: 50%;
            border: 4px solid #fff; object-fit: cover; background: #fff;
        }

        .name-wrapper { margin-bottom: 10px; }
        .fullname { font-size: 2rem; font-weight: 800; color: #050505; line-height: 1.1; margin-bottom: 4px; }
        .headline { font-size: 1.1rem; color: #65676b; font-weight: 500; }
        .stats-text { color: #65676b; font-size: 0.95rem; margin-top: 6px; }
        .stats-text b { color: #050505; font-weight: 600; }

        /* Buttons Action */
        .action-buttons { display: flex; gap: 8px; margin-bottom: 15px; }
        
        /* Nút Xanh (Chỉnh sửa / Theo dõi) */
        .btn-blue {
            background-color: #0866ff; color: white; border: none;
            padding: 0 16px; border-radius: 6px; font-weight: 600; font-size: 0.95rem;
            height: 36px; display: flex; align-items: center; gap: 6px; transition: 0.2s;
        }
        .btn-blue:hover { background-color: #0055d4; }
        
        /* Nút Xám (Nhắn tin) */
        .btn-gray {
            background-color: #e4e6eb; color: #050505; border: none;
            padding: 0 16px; border-radius: 6px; font-weight: 600; font-size: 0.95rem;
            height: 36px; display: flex; align-items: center; gap: 6px; transition: 0.2s;
        }
        .btn-gray:hover { background-color: #d8dadf; }

        /* Menu Tabs dưới Header */
        .profile-menu { display: flex; gap: 4px; max-width: 1030px; margin: 0 auto; padding: 4px 16px; }
        .menu-item {
            padding: 0 16px; height: 48px; display: flex; align-items: center; justify-content: center;
            font-weight: 600; color: #65676b; border-radius: 6px; cursor: pointer; position: relative;
        }
        .menu-item:hover { background: #f0f2f5; }
        .menu-item.active { color: #0866ff; border-bottom: 3px solid #0866ff; border-radius: 0; height: 45px; } /* Trừ đi border */

        /* --- BODY GRID --- */
        .profile-body {
            display: grid; grid-template-columns: 38% 60%; /* Tỉ lệ giống ảnh */
            gap: 16px; margin-top: 16px; padding-bottom: 50px;
        }

        /* Card Style */
        .card { background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 16px; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .card-title { font-size: 1.25rem; font-weight: 700; color: #050505; }
        .link-action { color: #0866ff; font-size: 1rem; cursor: pointer; }
        .link-action:hover { text-decoration: underline; }

        /* Intro List */
        .intro-text { text-align: center; margin-bottom: 16px; font-size: 0.95rem; }
        .intro-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; color: #050505; font-size: 0.95rem; }
        .intro-item i { font-size: 1.2rem; color: #8c939d; width: 20px; }
        .intro-item span b { font-weight: 600; }

        /* Photos Grid */
        .photo-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 4px; border-radius: 8px; overflow: hidden; }
        .photo-grid img { width: 100%; aspect-ratio: 1/1; object-fit: cover; cursor: pointer; }

        /* Create Post */
        .create-post-top { display: flex; gap: 10px; border-bottom: 1px solid #e4e6eb; padding-bottom: 12px; margin-bottom: 12px; }
        .input-mind { background: #f0f2f5; border-radius: 20px; flex: 1; border: none; padding: 8px 12px; font-size: 1.05rem; cursor: pointer; color: #65676b; }
        .input-mind:hover { background: #e4e6eb; }
        .post-actions { display: flex; justify-content: space-around; }
        .action-btn { display: flex; align-items: center; gap: 8px; color: #65676b; font-weight: 600; cursor: pointer; padding: 8px; border-radius: 8px; font-size: 0.95rem; flex: 1; justify-content: center;}
        .action-btn:hover { background: #f0f2f5; }

        /* Post Content */
        .poster-info { display: flex; gap: 10px; margin-bottom: 12px; }
        .poster-name { font-weight: 600; color: #050505; font-size: 0.95rem; }
        .post-meta { font-size: 0.8rem; color: #65676b; }
        .post-caption { font-size: 0.95rem; margin-bottom: 12px; color: #050505; }
        .post-image-container { margin: 0 -16px; } /* Tràn viền */
        .post-img { width: 100%; display: block; }
        
        .post-stats { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e4e6eb; color: #65676b; font-size: 0.9rem; }
        .like-circle { width: 18px; height: 18px; background: #0866ff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 0.7rem; }

        /* Responsive */
        @media (max-width: 900px) {
            .profile-body { grid-template-columns: 1fr; }
            .header-top-row { flex-direction: column; align-items: center; text-align: center; border-bottom: none; }
            .user-identity { flex-direction: column; align-items: center; gap: 10px; width: 100%; }
            .avatar-container { margin-top: -80px; }
            .action-buttons { width: 100%; justify-content: center; margin-top: 10px; }
            .btn-blue, .btn-gray { flex: 1; justify-content: center; }
            .menu-item { padding: 0 10px; font-size: 0.9rem; }
            .nav-item span { display: none; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo">TSix</a>
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass" style="color: #65676b"></i>
                <input type="text" placeholder="Tìm kiếm trên TSix">
            </div>
        </div>
        <div class="nav-center">
            <div class="nav-item active"><i class="fa-solid fa-house"></i></div>
            <div class="nav-item"><i class="fa-solid fa-users"></i></div>
            <div class="nav-item"><i class="fa-solid fa-store"></i></div>
            <div class="nav-item"><i class="fa-solid fa-gamepad"></i></div>
        </div>
        <div class="nav-right">
            <div class="nav-icon-circle"><i class="fa-solid fa-bars"></i></div>
            <div class="nav-icon-circle"><i class="fa-brands fa-facebook-messenger"></i></div>
            <div class="nav-icon-circle"><i class="fa-solid fa-bell"></i></div>
            
            <a href="logout.php" title="Đăng xuất">
                <img src="<?php echo isset($_SESSION['user_avatar']) ? 'uploads/'.$_SESSION['user_avatar'] : 'https://ui-avatars.com/api/?name=User'; ?>" class="nav-avatar">
            </a>
        </div>
    </nav>

    <div class="profile-header">
        <div class="container">
            <div class="cover-photo" style="<?php echo $cover_style; ?>"></div>
            
            <div class="header-details">
                <div class="header-top-row">
                    <div class="user-identity">
                        <div class="avatar-container">
                            <img src="<?php echo $avatar; ?>" class="big-avatar" alt="Avatar">
                        </div>
                        
                        <div class="name-wrapper">
                            <h1 class="fullname"><?php echo htmlspecialchars($user['FullName']); ?></h1>
                            <div class="stats-text">
                                <b>123</b> Người theo dõi &nbsp;•&nbsp; 
                                <b>45</b> Đang theo dõi
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <?php if ($is_own_profile): ?>
                            <button class="btn-gray" onclick="window.location.href='edit_profile.php'">
                                <i class="fa-solid fa-pen"></i> Chỉnh sửa trang cá nhân
                            </button>
                        <?php else: ?>
                            <button class="btn-blue">
                                <i class="fa-solid fa-user-plus"></i> Theo dõi
                            </button>
                            <button class="btn-gray">
                                <i class="fa-brands fa-facebook-messenger"></i> Nhắn tin
                            </button>
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
            
            <div class="left-col">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Giới thiệu</div>
                    </div>
                    <?php if (!empty($user['Bio'])): ?>
                        <div class="intro-text"><?php echo htmlspecialchars($user['Bio']); ?></div>
                    <?php endif; ?>
                    
                    <ul class="intro-list">
                        <li class="intro-item">
                            <i class="fa-solid fa-graduation-cap"></i>
                            <span>Đã học tại <b>HUB - Đại học Ngân hàng</b></span>
                        </li>
                        <li class="intro-item">
                            <i class="fa-solid fa-location-dot"></i>
                            <span>Sống tại <b>TP. Hồ Chí Minh</b></span>
                        </li>
                        <li class="intro-item">
                            <i class="fa-solid fa-cake-candles"></i>
                            <span>Sinh ngày <b><?php echo $birthDate; ?></b></span>
                        </li>
                        <li class="intro-item">
                            <i class="fa-solid fa-venus-mars"></i>
                            <span>Giới tính <b><?php echo $genderTxt; ?></b></span>
                        </li>
                        <li class="intro-item">
                            <i class="fa-solid fa-envelope"></i>
                            <span><?php echo htmlspecialchars($user['Email']); ?></span>
                        </li>
                    </ul>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Ảnh</div>
                        <span class="link-action">Xem tất cả ảnh</span>
                    </div>
                    <div class="photo-grid">
                        <img src="https://picsum.photos/200?random=1">
                        <img src="https://picsum.photos/200?random=2">
                        <img src="https://picsum.photos/200?random=3">
                        <img src="https://picsum.photos/200?random=4">
                        <img src="https://picsum.photos/200?random=5">
                        <img src="https://picsum.photos/200?random=6">
                        <img src="https://picsum.photos/200?random=7">
                        <img src="https://picsum.photos/200?random=8">
                        <img src="https://picsum.photos/200?random=9">
                    </div>
                </div>
            </div>

            <div class="right-col">
                
                <?php if ($is_own_profile): ?>
                <div class="card">
                    <div class="create-post-top">
                        <img src="<?php echo $avatar; ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <input type="text" class="input-mind" placeholder="Bạn đang nghĩ gì thế?">
                    </div>
                    <div class="post-actions">
                        <div class="action-btn"><i class="fa-solid fa-video" style="color: #f02849;"></i> Video</div>
                        <div class="action-btn"><i class="fa-solid fa-images" style="color: #45bd62;"></i> Ảnh</div>
                        <div class="action-btn"><i class="fa-regular fa-face-smile" style="color: #f7b928;"></i> Cảm xúc</div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="poster-info">
                        <img src="<?php echo $avatar; ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <div class="poster-name"><?php echo htmlspecialchars($user['FullName']); ?></div>
                            <div class="post-meta">Vừa xong <i class="fa-solid fa-earth-americas"></i></div>
                        </div>
                        <i class="fa-solid fa-ellipsis" style="margin-left: auto; color: #65676b; cursor: pointer;"></i>
                    </div>

                    <div class="post-caption">
                        Happy New Year 2026!!! 🎉🎉    
                    </div>

                    <div class="post-image-container">
                        <img src="https://picsum.photos/800/500?random=99" class="post-img" alt="Post Image">
                    </div>

                    <div class="post-stats">
                        <div style="display: flex; align-items: center; gap: 4px;">
                            <span class="like-circle"><i class="fa-solid fa-thumbs-up"></i></span>
                            1.2K
                        </div>
                        <div>234 Bình luận • 56 Chia sẻ</div>
                    </div>

                    <div class="post-actions" style="border-top: 1px solid #e4e6eb; margin-top: 4px; padding-top: 4px;">
                        <div class="action-btn"><i class="fa-regular fa-thumbs-up"></i> Thích</div>
                        <div class="action-btn"><i class="fa-regular fa-message"></i> Bình luận</div>
                        <div class="action-btn"><i class="fa-solid fa-share"></i> Chia sẻ</div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>