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
            if (move_uploaded_file($_FILES['direct_avatar']['tmp_name'], "C:/wamp64/www/web-social-network/uploads/avatars/" . $new_name)) {
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
            if (move_uploaded_file($_FILES['direct_cover']['tmp_name'], "C:/wamp64/www/web-social-network/uploads/cover_images/" . $new_name)) {
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

// 5. Lấy số lượng người theo dõi và đang theo dõi
// ID của người dùng đang được xem trang cá nhân
$profile_id = $user['UserID']; 

// Đếm số người theo dõi (Followers): Những người đang follow profile_id
$sql_followers = "SELECT COUNT(*) as total FROM FOLLOWS WHERE FK_FollowingID = ? AND Status = 'accepted'";
$stmt_f1 = mysqli_prepare($conn, $sql_followers);
mysqli_stmt_bind_param($stmt_f1, "i", $profile_id);
mysqli_stmt_execute($stmt_f1);
$res_f1 = mysqli_stmt_get_result($stmt_f1);
$follower_count = mysqli_fetch_assoc($res_f1)['total'];

// Đếm số người đang theo dõi (Following): Những người mà profile_id đang follow
$sql_following = "SELECT COUNT(*) as total FROM FOLLOWS WHERE FK_FollowerID = ? AND Status = 'accepted'";
$stmt_f2 = mysqli_prepare($conn, $sql_following);
mysqli_stmt_bind_param($stmt_f2, "i", $profile_id);
mysqli_stmt_execute($stmt_f2);
$res_f2 = mysqli_stmt_get_result($stmt_f2);
$following_count = mysqli_fetch_assoc($res_f2)['total'];

// Định dạng ngày sinh và giới tính, địa chỉ
$birthDate = !empty($user['BirthDate']) 
    ? date("d/m", strtotime($user['BirthDate'])) 
    : "Chưa cập nhật";

$genderTxt = ($user['Gender'] === 'Nam') ? 'Nam' 
           : ($user['Gender'] === 'Nu' ? 'Nữ' : 'Khác');

$Address = !empty($user['Address']) 
    ? htmlspecialchars($user['Address']) : "Chưa cập nhật";

// ... (Code cũ lấy thông tin user) ...

// [MỚI] 6. Lấy danh sách bài viết của người dùng này (Profile ID)
$posts_query = "
    SELECT 
        p.PostID, p.FK_UserID as UserID, p.Content, p.LikeCount, p.CommentCount, p.CreatedAt, p.Status,
        u.FullName, u.Avatar, 
        (l.FK_UserID IS NOT NULL) AS user_liked
    FROM POSTS p
    JOIN Users u ON p.FK_UserID = u.UserID
    LEFT JOIN LIKES l ON l.FK_PostID = p.PostID AND l.FK_UserID = ?
    WHERE 
        p.FK_UserID = ? 
        AND p.Status != 'deleted'
    ORDER BY p.CreatedAt DESC
";

$posts_stmt = mysqli_prepare($conn, $posts_query);
mysqli_stmt_bind_param($posts_stmt, "ii", $current_user_id, $profile_id);
mysqli_stmt_execute($posts_stmt);
$posts_result = mysqli_stmt_get_result($posts_stmt);

$profile_posts = [];
while ($post = mysqli_fetch_assoc($posts_result)) {
    // Hàm timeAgo nằm trong functions.php
    $post['time_ago'] = function_exists('timeAgo') ? timeAgo($post['CreatedAt']) : $post['CreatedAt'];

    $post['avatar_url'] = !empty($post['Avatar'])
        ? BASE_URL . 'uploads/avatars/' . htmlspecialchars($post['Avatar'])
        : 'https://ui-avatars.com/api/?name=' . urlencode($post['FullName']) . '&background=8B1E29&color=fff&size=200';

    $profile_posts[] = $post;
}

// Lấy ảnh cho bài viết
foreach ($profile_posts as &$post) {
    $img_stmt = mysqli_prepare($conn, "SELECT ImageUrl, ImageID FROM POST_IMAGES WHERE FK_PostID = ? ORDER BY ImageID");
    mysqli_stmt_bind_param($img_stmt, "i", $post['PostID']);
    mysqli_stmt_execute($img_stmt);
    $img_result = mysqli_stmt_get_result($img_stmt);

    $post['images'] = [];
    $post['image_ids'] = []; // Để dùng cho việc xóa ảnh khi edit
    while ($img = mysqli_fetch_assoc($img_result)) {
        $post['images'][] = BASE_URL . 'uploads/posts/' . htmlspecialchars($img['ImageUrl']);
        $post['image_ids'][] = $img['ImageID'];
    }
}
unset($post);

// Hàm hỗ trợ avatar cho comment input (Current User)
function getCurrentUserAvatar($conn, $uid) {
    if (!empty($_SESSION['user_avatar'])) {
        return BASE_URL . 'uploads/avatars/' . $_SESSION['user_avatar'];
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_fullname'] ?? 'User') . '&background=8B1E29&color=fff';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['FullName']); ?> - TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="assets/Style-css/profile.css">
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
                                <b><?php echo number_format($follower_count); ?></b> Người theo dõi • 
                                <b><?php echo number_format($following_count); ?></b> Đang theo dõi
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
                    <div class="menu-item active" onclick="switchTab(this, 'posts')">Bài viết</div>
                    <div class="menu-item" onclick="switchTab(this, 'about')">Giới thiệu</div>
                    <div class="menu-item" onclick="switchTab(this, 'friends')">Bạn bè</div>
                </div>
            </div>
        </div>
    </div>

    <div class="profile-content-container">
        <div id="tab-posts" class="tab-content active">
            </div>

        <div id="tab-about" class="tab-content" style="display:none;">
            <div class="about-card">  
                <div class="info-display-list">
                    <div class="intro-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Đến từ <b><?php echo $Address; ?></b></span>
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
                        <span><?php echo htmlspecialchars($user['Email']); ?></span> </div>
                </div>

                <hr style="margin: 20px 0; border: 0; border-top: 1px solid #e4e6eb;">

                <?php if ($is_own_profile): ?>
                <form id="edit-about-form" onsubmit="updateAbout(event)">
                    <div class="form-group">
                        <label><i class="fa-solid fa-pen-to-square"></i> Chỉnh sửa địa chỉ:</label>
                        <input type="text" name="address" value="<?php echo htmlspecialchars($user['Address'] ?? ''); ?>" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fa-solid fa-calendar-days"></i> Chỉnh sửa ngày sinh:</label>
                        <input type="date" name="birthdate" value="<?php echo $user['BirthDate']; ?>" class="form-control">
                    </div>

                    <div class="form-group">
                        <label><i class="fa-solid fa-user-half-slash"></i> Giới tính:</label>
                        <select name="gender" class="form-control">
                            <option value="Nam" <?php echo ($user['Gender'] === 'Nam') ? 'selected' : ''; ?>>Nam</option>
                            <option value="Nu" <?php echo ($user['Gender'] === 'Nu') ? 'selected' : ''; ?>>Nữ</option>
                            <option value="Khac" <?php echo ($user['Gender'] === 'Khac') ? 'selected' : ''; ?>>Khác</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-submit-about">Lưu thay đổi</button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div id="tab-friends" class="tab-content" style="display:none;">
            <div class="friends-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                <?php if (empty($all_friends)): ?>
                    <p>Chưa có bạn bè nào.</p>
                <?php else: ?>
                    <?php foreach ($all_friends as $friend): ?>
                        <div class="friend-card-mini" style="display: flex; align-items: center; background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 8px;">
                            <img src="uploads/avatars/<?php echo $friend['Avatar'] ?: 'default_avatar.png'; ?>" style="width: 60px; height: 60px; border-radius: 8px; margin-right: 15px; object-fit: cover;">
                            <span style="font-weight: 600;"><?php echo htmlspecialchars($friend['FullName']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
                        <span>Đến từ <b><?php echo $Address; ?></b></span>
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

                <!-- Bài viết -->
                <div id="tab-posts" class="tab-content active">
                    <?php if (empty($profile_posts)): ?>
                        <div style="text-align:center; padding: 40px; color: #65676b; background: #fff; border-radius: 8px; border: 1px solid #e4e6eb;">
                            <h3>Chưa có bài viết nào</h3>
                            <p>Người dùng này chưa đăng bài viết nào.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($profile_posts as $post): ?>
                        <div class="card post" id="post-<?php echo $post['PostID']; ?>">
                            <div class="post-header" style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
                                <img src="<?php echo $post['avatar_url']; ?>" class="post-avatar" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                                <div class="post-user">
                                    <h4 style="margin:0; font-size:0.95rem;"><?php echo htmlspecialchars($post['FullName']); ?></h4>
                                    <div class="time" style="font-size:0.8rem; color:#65676b;">
                                        <?php echo $post['time_ago']; ?> <i class="fa-solid fa-earth-americas" style="font-size:12px;"></i>
                                    </div>
                                </div>

                                <div class="post-header-right">
                                    <div class="post-menu-btn" onclick="togglePostMenu('menu-<?php echo $post['PostID']; ?>')">
                                        <i class="fa-solid fa-ellipsis"></i>
                                    </div>
                                    <div id="menu-<?php echo $post['PostID']; ?>" class="post-options-menu">
                                        <a href="saved_post_action.php?id=<?php echo $post['PostID']; ?>" class="menu-item">
                                            <i class="fa-solid fa-bookmark"></i> Lưu bài viết
                                        </a>
                                        
                                        <?php if ($post['UserID'] == $_SESSION['user_id']): ?>
                                            <div class="menu-item" onclick="openModal('edit-modal-<?php echo $post['PostID']; ?>')">
                                                <i class="fa-solid fa-pen"></i> Chỉnh sửa bài viết
                                            </div>
                                            <a href="deleted_post_action.php?id=<?php echo $post['PostID']; ?>" class="menu-item" onclick="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">
                                                <i class="fa-solid fa-trash"></i> Xóa bài viết
                                            </a>
                                        <?php else: ?>
                                            <div class="menu-item" onclick="openModal('report-modal-<?php echo $post['PostID']; ?>')">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Báo cáo bài viết
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="post-content" style="margin-bottom:12px; font-size:0.95rem; line-height:1.5;">
                                <?php echo nl2br(htmlspecialchars($post['Content'])); ?>
                            </div>

                            <?php if (!empty($post['images'])): ?>
                                <div class="post-images" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:4px; margin:12px -16px;">
                                    <?php foreach ($post['images'] as $img): ?>
                                        <img src="<?php echo $img; ?>" style="width:100%; border-radius:0; object-fit:cover; max-height:500px;">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="post-stats" style="display:flex; justify-content:space-between; padding:10px 0; border-top:1px solid #e4e6eb; border-bottom:1px solid #e4e6eb; color:#65676b; font-size:0.9rem;">
                                <div>
                                    <i class="fa-solid fa-thumbs-up" style="color:#8B1E29;"></i>
                                    <span class="like-count"><?php echo $post['LikeCount']; ?></span>
                                </div>
                                <div><span class="comment-count"><?php echo $post['CommentCount']; ?></span> bình luận</div>
                            </div>

                            <div class="post-actions-row" style="display:flex; padding-top:8px;">
                                <button class="post-action-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                        onclick="toggleLike(this, <?php echo $post['PostID']; ?>)"
                                        style="flex:1; background:none; border:none; padding:8px; cursor:pointer; font-weight:600; color: #65676b; display:flex; align-items:center; justify-content:center; gap:8px;">
                                    <i class="fa-regular fa-thumbs-up"></i> 
                                    <span><?php echo $post['user_liked'] ? 'Đã thích' : 'Thích'; ?></span>
                                </button>

                                <button class="post-action-btn btn-show-comments"
                                        onclick="toggleCommentSection(this)"
                                        style="flex:1; background:none; border:none; padding:8px; cursor:pointer; font-weight:600; color: #65676b; display:flex; align-items:center; justify-content:center; gap:8px;">
                                    <i class="fa-regular fa-comment"></i> Bình luận
                                </button>
                            </div>

                            <div class="comments-section">
                                <div class="comments-list" data-post-id="<?php echo $post['PostID']; ?>">
                                    <div class="text-center" style="color:#65676b; padding:20px;">Đang tải bình luận...</div>
                                </div>
                                <div class="comment-form" style="display:flex; gap:8px; margin-top:12px;">
                                    <img src="<?php echo getCurrentUserAvatar($conn, $_SESSION['user_id']); ?>" style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
                                    <input type="text" class="comment-input" placeholder="Viết bình luận...">
                                    <button class="send-comment" style="background:none; border:none; color:#8B1E29; font-size:1.2rem; cursor:pointer;">
                                        <i class="fa-solid fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>

                            <?php if ($post['UserID'] == $_SESSION['user_id']): ?>
                                <div id="edit-modal-<?php echo $post['PostID']; ?>" class="modal-overlay">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h3>Chỉnh sửa bài viết</h3>
                                            <button class="close-modal" onclick="closeModal('edit-modal-<?php echo $post['PostID']; ?>')">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="edit_post_action.php" method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                                <textarea name="content" class="edit-textarea"><?php echo htmlspecialchars($post['Content']); ?></textarea>
                                                
                                                <?php if (!empty($post['images'])): ?>
                                                    <div style="margin-top:10px; display:flex; gap:5px; flex-wrap:wrap;">
                                                    <?php foreach ($post['images'] as $idx => $imgUrl): ?>
                                                        <div style="position:relative;">
                                                            <img src="<?php echo $imgUrl; ?>" style="width:60px; height:60px; object-fit:cover;">
                                                            <input type="checkbox" name="delete_images[]" value="<?php echo $post['image_ids'][$idx]; ?>" style="position:absolute; top:0; right:0;">
                                                        </div>
                                                    <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div style="margin-top:10px;">
                                                    <label>Thêm ảnh mới: <input type="file" name="new_images[]" multiple></label>
                                                </div>
                                                <button type="submit" class="btn-submit-modal">Lưu thay đổi</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div id="report-modal-<?php echo $post['PostID']; ?>" class="modal-overlay">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h3>Báo cáo</h3>
                                            <button class="close-modal" onclick="closeModal('report-modal-<?php echo $post['PostID']; ?>')">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="report_post_action.php" method="POST">
                                                <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                                <ul class="report-list">
                                                    <li class="report-item"><label><input type="radio" name="reason" value="spam"> Spam</label></li>
                                                    <li class="report-item"><label><input type="radio" name="reason" value="violence"> Bạo lực</label></li>
                                                    </ul>
                                                <button type="submit" class="btn-submit-modal" style="background:#e4e6eb; color:black;">Gửi báo cáo</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div> 
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<script>
    // Hàm chuyển đổi tab
    // --- 1. HÀM CHUYỂN TAB (Code cũ của bạn + điều chỉnh nhỏ) ---
    function switchTab(element, tabName) {
        document.querySelectorAll('.menu-item').forEach(item => item.classList.remove('active'));
        element.classList.add('active');

        // Ẩn tất cả tab trong profile-content-container (Lớp cha chứa tab)
        document.querySelectorAll('.profile-content-container .tab-content').forEach(content => {
            content.style.display = 'none';
            content.classList.remove('active'); // Remove class active nếu có dùng CSS
        });

        // Hiện tab được chọn
        const target = document.getElementById('tab-' + tabName);
        if (target) {
            target.style.display = 'block';
            target.classList.add('active');
        }

        // Logic load bạn bè (giữ nguyên của bạn)
        if (tabName === 'friends') {
            const container = document.querySelector('#tab-friends .friends-grid');
            const urlParams = new URLSearchParams(window.location.search);
            const profileUserId = urlParams.get('id') || '<?php echo $_SESSION['user_id']; ?>';
            
            // Nếu chưa có nội dung thì mới fetch (hoặc luôn fetch tùy ý)
            if (container && container.innerHTML.trim() === '') {
                fetch(`friends.php?user_id=${profileUserId}&view=all&view_as_tab=1`)
                    .then(res => res.text())
                    .then(html => { container.innerHTML = html; })
                    .catch(err => { container.innerHTML = "Lỗi tải bạn bè."; });
            }
        }
    }

    // --- 2. HÀM XỬ LÝ POST (Copy từ index.php) ---
    
    // Toggle Menu 3 chấm
    function togglePostMenu(menuId) {
        document.querySelectorAll('.post-options-menu').forEach(menu => {
            if (menu.id !== menuId) menu.classList.remove('show');
        });
        const menu = document.getElementById(menuId);
        if (menu) menu.classList.toggle('show');
        event.stopPropagation();
    }

    // Modal Handling
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.querySelectorAll('.post-options-menu').forEach(menu => menu.classList.remove('show'));
        }
    }
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
    }

    // Close when clicking outside
    window.onclick = function(event) {
        if (!event.target.closest('.post-menu-btn') && !event.target.closest('.post-options-menu')) {
            document.querySelectorAll('.post-options-menu').forEach(menu => menu.classList.remove('show'));
        }
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    }

    // --- 3. XỬ LÝ LIKE & COMMENT (AJAX) ---
    
    // --- XỬ LÝ LIKE ---
    function toggleLike(btn, postId) {
        // Chống click liên tục khi đang xử lý
        if (btn.disabled) return;
        btn.disabled = true;

        fetch('interaction_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_like&post_id=${postId}`
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false; // Mở khóa nút
            if (data.success) {
                btn.classList.toggle('liked', data.liked);
                btn.querySelector('span').textContent = data.liked ? 'Đã thích' : 'Thích';
                
                // Cập nhật con số hiển thị
                const likeCountEl = btn.closest('.post').querySelector('.like-count');
                if (likeCountEl) likeCountEl.textContent = data.like_count;
            }
        })
        .catch(() => { btn.disabled = false; });
    }

    // --- XỬ LÝ ẨN/HIỆN KHUNG BÌNH LUẬN ---
    function toggleCommentSection(btn) {
        const postContainer = btn.closest('.post');
        const section = postContainer.querySelector('.comments-section');
        const list = section.querySelector('.comments-list');
        const postId = list.dataset.postId;

        if (section.style.display === 'block') {
            section.style.display = 'none';
        } else {
            section.style.display = 'block';
            // Chỉ tải dữ liệu nếu khung đang trống hoặc đang ở trạng thái 'Đang tải'
            if (list.innerHTML.includes('Đang tải') || list.innerHTML.trim() === '') {
                loadComments(postId, list);
            }
        }
    }
    // Send Comment
    document.querySelectorAll('.send-comment').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.closest('.comment-form').querySelector('.comment-input');
            const content = input.value.trim();
            if (!content) return;
            const postId = this.closest('.post').querySelector('.comments-list').dataset.postId;

            fetch('interaction_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add_comment&post_id=${postId}&content=${encodeURIComponent(content)}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    const list = this.closest('.post').querySelector('.comments-list');
                    list.insertAdjacentHTML('beforeend', renderCommentHTML(data.comment));
                    // Update count visually if needed
                } else {
                    alert(data.message);
                }
            });
        });
    });

    function loadComments(postId, container) {
        fetch(`interaction_handler.php?action=get_comments&post_id=${postId}`)
        .then(r => r.json())
        .then(data => {
            container.innerHTML = '';
            if (data.comments.length === 0) {
                container.innerHTML = '<div class="text-center" style="padding:10px; color:#65676b;">Chưa có bình luận.</div>';
            } else {
                data.comments.forEach(c => {
                    container.insertAdjacentHTML('beforeend', renderCommentHTML(c));
                });
            }
        });
    }

    function renderCommentHTML(c) {
        return `
        <div class="comment-item">
            <img src="${c.avatar_url}" class="comment-avatar">
            <div class="comment-bubble">
                <div class="comment-author">${c.FullName}</div>
                <div class="comment-content">${c.Content}</div>
                <div class="comment-meta">${c.time_ago}</div>
            </div>
        </div>`;
    }

    function switchTab(element, tabName) {
            // 1. Reset class active cho menu
        document.querySelectorAll('.profile-menu .menu-item').forEach(item => item.classList.remove('active'));
        element.classList.add('active');

        // 2. Ẩn tất cả tab trong container chính
        const tabs = document.querySelectorAll('.profile-content-container .tab-content');
        tabs.forEach(tab => {
            tab.style.display = 'none';
            tab.classList.remove('active');
        });

        // 3. Hiện tab được chọn
        const targetTab = document.getElementById('tab-' + tabName);
        if (targetTab) {
            targetTab.style.display = 'block';
            targetTab.classList.add('active');
        }

        // 4. Load AJAX cho bạn bè
        if (tabName === 'friends') {
            const container = targetTab; // Hoặc targetTab.querySelector('#friends-loader')
            
            // Lấy ID người dùng từ URL của trang profile hiện tại
            const urlParams = new URLSearchParams(window.location.search);
            const profileUserId = urlParams.get('id') || '<?php echo $_SESSION['user_id']; ?>';

            fetch(`friends.php?user_id=${profileUserId}&view=all&view_as_tab=1`)
                .then(res => res.text())
                .then(html => {
                    container.innerHTML = html;
                })
                .catch(err => {
                    container.innerHTML = "Không thể tải danh sách bạn bè.";
                });
        }
    }

        function updateAbout(event) {
        event.preventDefault(); // Ngăn trang web tải lại
        
        const form = event.target;
        const formData = new FormData(form);

        fetch('update_profile_action.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); // Tải lại trang để cập nhật thông tin mới hiển thị
            } else {
                alert("Lỗi: " + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Có lỗi xảy ra trong quá trình gửi dữ liệu.");
        });
    }
</script>
</body>
</html>