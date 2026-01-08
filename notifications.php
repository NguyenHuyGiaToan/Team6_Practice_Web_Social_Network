<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Khi truy cập trang → đánh dấu tất cả thông báo là đã đọc
$sql_read = "UPDATE NOTIFICATIONS SET IsRead = 1 WHERE FK_UserID = ? AND IsRead = 0";
$stmt_read = mysqli_prepare($conn, $sql_read);
mysqli_stmt_bind_param($stmt_read, "i", $current_user_id);
mysqli_stmt_execute($stmt_read);

// Lấy số lượng thông báo chưa đọc (để hiển thị badge ở header – dùng chung ở các trang khác cũng được)
$sql_count = "SELECT COUNT(*) as unread FROM NOTIFICATIONS WHERE FK_UserID = ? AND IsRead = 0";
$stmt_count = mysqli_prepare($conn, $sql_count);
mysqli_stmt_bind_param($stmt_count, "i", $current_user_id);
mysqli_stmt_execute($stmt_count);
$count_result = mysqli_stmt_get_result($stmt_count);
$unread_count = mysqli_fetch_assoc($count_result)['unread'];

// Lấy danh sách thông báo (kèm thông tin người thực hiện hành động)
$sql = "
    SELECT n.*, u.FullName AS ActorName, u.Avatar AS ActorAvatar,
           p.Content AS PostContent
    FROM NOTIFICATIONS n
    JOIN Users u ON n.ActorID = u.UserID
    LEFT JOIN POSTS p ON n.ReferenceID = p.PostID AND n.Type IN ('Like', 'Comment')
    WHERE n.FK_UserID = ?
    ORDER BY n.CreatedAt DESC
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}

// Tách riêng thông báo mới và cũ (dù đã đánh dấu đọc, vẫn giữ phân loại theo IsRead ban đầu nếu muốn)
$new_notifications = array_filter($notifications, fn($n) => $n['IsRead'] == 0);
$old_notifications = array_filter($notifications, fn($n) => $n['IsRead'] == 1);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo - TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: #f0f2f5; }
        a { text-decoration: none; color: inherit; }

        /* Navbar giống profile.php */
        .navbar {
            background: #fff; height: 60px; padding: 0 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 1000;
        }
        .logo { color: #8B1E29; font-weight: 800; font-size: 2rem; }
        .search-box { background: #f0f2f5; padding: 10px 16px; border-radius: 50px; display: flex; align-items: center; width: 300px; }
        .search-box input { border: none; background: transparent; outline: none; margin-left: 8px; width: 100%; }
        .nav-right { display: flex; align-items: center; gap: 10px; }
        .nav-icon-circle { width: 40px; height: 40px; background: #e4e6eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; }
        .badge { position: absolute; top: -6px; right: -6px; background: #e41e3f; color: white; font-size: 0.7rem; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .nav-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }

        /* Container */
        .container { max-width: 700px; margin: 20px auto; padding: 0 16px; }

        /* Tiêu đề */
        .page-title { font-size: 1.8rem; font-weight: 700; margin-bottom: 20px; }

        /* Section thông báo */
        .notification-section { margin-bottom: 30px; }
        .section-title { font-size: 1.3rem; font-weight: 600; margin-bottom: 12px; color: #050505; }

        /* Item thông báo */
        .noti-item {
            background: #fff; padding: 12px 16px; border-radius: 8px; margin-bottom: 8px;
            display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .noti-item.unread { background: #f0f8ff; font-weight: 600; }
        .noti-avatar {
            width: 48px; height: 48px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
        }
        .noti-content {
            flex: 1; font-size: 0.95rem; line-height: 1.4;
        }
        .noti-message { margin-bottom: 4px; }
        .noti-time { color: #65676b; font-size: 0.85rem; }
        .noti-actions { cursor: pointer; color: #65676b; font-size: 1.2rem; }
        .noti-actions:hover { color: #050505; }

        .empty-text { text-align: center; color: #65676b; padding: 40px; font-size: 1.1rem; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-left" style="display: flex; align-items: center; gap: 16px;">
            <a href="index.php" class="logo">TSix</a>
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass" style="color: #65676b"></i>
                <input type="text" placeholder="Tìm kiếm trên TSix">
            </div>
        </div>
        <div class="nav-right">
            <div class="nav-icon-circle"><i class="fa-solid fa-house"></i></div>
            <div class="nav-icon-circle">
                <i class="fa-solid fa-bell"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="badge"><?php echo $unread_count > 99 ? '99+' : $unread_count; ?></span>
                <?php endif; ?>
            </div>
            <a href="profile.php">
                <img src="<?php echo $_SESSION['user_avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_fullname'] ?? 'User'); ?>"
                     class="nav-avatar" alt="Avatar">
            </a>
        </div>
    </nav>

    <div class="container">
        <h1 class="page-title">Thông báo</h1>

        <?php if (empty($notifications)): ?>
            <div class="empty-text">Chưa có thông báo nào.</div>
        <?php else: ?>
            <!-- Thông báo mới -->
            <?php if (!empty($new_notifications)): ?>
                <div class="notification-section">
                    <div class="section-title">Mới</div>
                    <?php foreach ($new_notifications as $noti): ?>
                        <?php
                        $actor_avatar = !empty($noti['ActorAvatar'])
                            ? 'uploads/' . $noti['ActorAvatar']
                            : 'https://ui-avatars.com/api/?name=' . urlencode($noti['ActorName']);
                        $message = '';
                        switch ($noti['Type']) {
                            case 'Like':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã thích bài viết của bạn.';
                                break;
                            case 'Comment':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã bình luận bài viết của bạn.';
                                break;
                            case 'Follow':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã bắt đầu theo dõi bạn.';
                                break;
                            default:
                                $message = $noti['Message'];
                        }
                        ?>
                        <div class="noti-item unread">
                            <img src="<?php echo $actor_avatar; ?>" class="noti-avatar" alt="Avatar">
                            <div class="noti-content">
                                <div class="noti-message"><?php echo $message; ?></div>
                                <div class="noti-time"><?php echo timeAgo($noti['CreatedAt']); ?></div>
                            </div>
                            <div class="noti-actions">⋯</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Thông báo trước đó -->
            <?php if (!empty($old_notifications)): ?>
                <div class="notification-section">
                    <div class="section-title">Trước đó</div>
                    <?php foreach ($old_notifications as $noti): ?>
                        <?php
                        $actor_avatar = !empty($noti['ActorAvatar'])
                            ? 'uploads/' . $noti['ActorAvatar']
                            : 'https://ui-avatars.com/api/?name=' . urlencode($noti['ActorName']);
                        $message = '';
                        switch ($noti['Type']) {
                            case 'Like':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã thích bài viết của bạn.';
                                break;
                            case 'Comment':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã bình luận bài viết của bạn.';
                                break;
                            case 'Follow':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã bắt đầu theo dõi bạn.';
                                break;
                            default:
                                $message = $noti['Message'];
                        }
                        ?>
                        <div class="noti-item">
                            <img src="<?php echo $actor_avatar; ?>" class="noti-avatar" alt="Avatar">
                            <div class="noti-content">
                                <div class="noti-message"><?php echo $message; ?></div>
                                <div class="noti-time"><?php echo timeAgo($noti['CreatedAt']); ?></div>
                            </div>
                            <div class="noti-actions">⋯</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>