<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// LẤY THÔNG TIN USER ĐỂ HEADER DÙNG (avatar + fullname)
$stmt = mysqli_prepare($conn, "SELECT FullName, Avatar FROM Users WHERE UserID = ?");
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$current_user = mysqli_fetch_assoc($user_result);

// Cập nhật session để header.php và các trang khác dùng chung
$_SESSION['user_fullname'] = $current_user['FullName'];
$_SESSION['user_avatar']    = $current_user['Avatar'] ?? null;

// Đánh dấu tất cả thông báo là đã đọc khi truy cập trang
$sql_read = "UPDATE NOTIFICATIONS SET IsRead = 1 WHERE FK_UserID = ? AND IsRead = 0";
$stmt_read = mysqli_prepare($conn, $sql_read);
mysqli_stmt_bind_param($stmt_read, "i", $current_user_id);
mysqli_stmt_execute($stmt_read);

// Lấy số lượng thông báo chưa đọc (dù đã đánh dấu đọc ở trên, nhưng để hiển thị badge chính xác trước khi update)
$sql_count = "SELECT COUNT(*) as unread FROM NOTIFICATIONS WHERE FK_UserID = ? AND IsRead = 0";
$stmt_count = mysqli_prepare($conn, $sql_count);
mysqli_stmt_bind_param($stmt_count, "i", $current_user_id);
mysqli_stmt_execute($stmt_count);
$count_result = mysqli_stmt_get_result($stmt_count);
$unread_count = mysqli_fetch_assoc($count_result)['unread'];

// Lấy danh sách thông báo
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

// Tách thông báo mới/cũ (dựa trên IsRead trước khi update)
$new_notifications = array_filter($notifications, fn($n) => $n['IsRead'] == 0);
$old_notifications = array_filter($notifications, fn($n) => $n['IsRead'] == 1);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo - TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/Style-css/notifications.css">
</head>
<body>

    <div class="container">
        <h1 class="page-title">Thông báo</h1>

        <?php if (empty($notifications)): ?>
            <div class="empty-text">
                <i class="fa-solid fa-bell fa-3x" style="color:#ccd0d5; margin-bottom:16px;"></i><br>
                Chưa có thông báo nào.
            </div>
        <?php else: ?>
            <!-- Thông báo mới -->
            <?php if (!empty($new_notifications)): ?>
                <div class="notification-section">
                    <div class="section-title">Mới</div>
                    <?php foreach ($new_notifications as $noti): ?>
                        <?php
                        $actor_avatar = !empty($noti['ActorAvatar'])
                            ? 'uploads/avatars/' . htmlspecialchars($noti['ActorAvatar'])
                            : 'https://ui-avatars.com/api/?name=' . urlencode($noti['ActorName']) . '&background=8B1E29&color=fff&size=200';

                        switch ($noti['Type']) {
                            case 'Like':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã thích bài viết của bạn.';
                                break;
                            case 'Comment':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã bình luận bài viết của bạn.';
                                break;
                            case 'Follow':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã gửi yêu cầu theo dõi bạn.';
                                break;
                            case 'AcceptFollow':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã chấp nhận yêu cầu theo dõi của bạn.';
                                break;
                            case 'DeclineFollow':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã từ chối yêu cầu theo dõi của bạn.';
                                break;
                             case 'Unfollow':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã hủy theo dõi bạn.';
                                break;
                            default:
                                $message = htmlspecialchars($noti['Message']);
                                break;
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
                            ? 'uploads/avatars/' . htmlspecialchars($noti['ActorAvatar'])
                            : 'https://ui-avatars.com/api/?name=' . urlencode($noti['ActorName']) . '&background=8B1E29&color=fff&size=200';

                        switch ($noti['Type']) {
                            case 'Like':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã thích bài viết của bạn.';
                                break;
                            case 'Comment':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã bình luận bài viết của bạn.';
                                break;
                            case 'Follow':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã gửi yêu cầu theo dõi bạn.';
                                break;
                            case 'AcceptFollow':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã chấp nhận yêu cầu theo dõi của bạn.';
                                break;
                            case 'DeclineFollow':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã từ chối yêu cầu theo dõi của bạn.';
                                break;
                            case 'Unfollow':
                                $message = '<strong>' . htmlspecialchars($noti['ActorName']) . '</strong> đã hủy theo dõi bạn.';
                                break;
                            default:
                                $message = htmlspecialchars($noti['Message']);
                                break;
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