<?php
// admin/post_detail.php
session_start();

// Chỉ cho phép admin truy cập
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/database.php';
require_once '../includes/functions.php';


$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id <= 0) {
    echo '<p>Bài viết không hợp lệ.</p>';
    exit;
}

// Lấy thông tin bài viết
$sql = "SELECT p.PostID, p.Content, u.FullName, u.Avatar, p.LikeCount, p.CommentCount, p.CreatedAt, p.UpdatedAt
        FROM posts p
        LEFT JOIN users u ON p.FK_UserID = u.UserID
        WHERE p.PostID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    echo '<p>Không tìm thấy bài viết.</p>';
    exit;
}

// Lấy ảnh bài viết (nếu có)
$sql_img = "SELECT ImageUrl FROM post_images WHERE FK_PostID = ? LIMIT 1";
$stmt_img = $conn->prepare($sql_img);
$stmt_img->bind_param('i', $post_id);
$stmt_img->execute();
$result_img = $stmt_img->get_result();
$img = $result_img->fetch_assoc();
$image_src = $img && !empty($img['ImageUrl']) ? "../uploads/posts/" . $img['ImageUrl'] : "../assets/images/default_post_image.png";

// Lấy bình luận
$sql_comments = "SELECT c.CommentID, c.Content, c.CreatedAt, u.FullName, u.Avatar
                FROM comments c
                LEFT JOIN users u ON c.FK_UserID = u.UserID
                WHERE c.FK_PostID = ?
                ORDER BY c.CreatedAt DESC";
$stmt_cmt = $conn->prepare($sql_comments);
$stmt_cmt->bind_param('i', $post_id);
$stmt_cmt->execute();
$result_cmt = $stmt_cmt->get_result();
$comments = $result_cmt->fetch_all(MYSQLI_ASSOC);

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == 1;

if ($isAjax) {
?>
    <div class="post-detail-header" style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">
        <img src="<?= !empty($post['Avatar']) ? '../uploads/avatars/' . htmlspecialchars($post['Avatar']) : '../uploads/avatars/default_avatar.png' ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;" alt="Avatar">
        <div>
            <strong><?= htmlspecialchars($post['FullName']) ?></strong><br>
            <span style="color:#888;font-size:0.95em;">Đăng lúc <?= date('H:i d/m/Y', strtotime($post['CreatedAt'])) ?></span>
        </div>
    </div>
    <img src="<?= $image_src ?>" style="width:100%;max-height:350px;object-fit:cover;border-radius:8px;margin-bottom:16px;" alt="Ảnh bài viết">
    <div style="font-size:1.2em;margin-bottom:12px;">
        <?= nl2br(htmlspecialchars($post['Content'])) ?>
    </div>
    <div style="margin-bottom:16px;">
        <span style="margin-right:18px;color:#555;"><i class="fa-solid fa-thumbs-up"></i> <?= $post['LikeCount'] ?></span>
        <span style="margin-right:18px;color:#555;"><i class="fa-solid fa-comments"></i> <?= $post['CommentCount'] ?></span>
        <span style="color:#555;"><i class="fa-regular fa-clock"></i> Cập nhật: <?= timeAgo($post['UpdatedAt']) ?></span>
    </div>
    <div class="comments-section" style="margin-top:32px;">
        <h4>Bình luận (<?= count($comments) ?>)</h4>
        <?php foreach ($comments as $cmt) { ?>
            <div style="border-top:1px solid #eee;padding:12px 0;display:flex;gap:12px;">
                <img src="<?= !empty($cmt['Avatar']) ? '../uploads/avatars/' . htmlspecialchars($cmt['Avatar']) : '../uploads/avatars/default_avatar.png' ?>" style="width:32px;height:32px;border-radius:50%;object-fit:cover;" alt="Avatar">
                <div>
                    <div style="background:#f7f7f7;border-radius:8px;padding:8px 14px;">
                        <?= nl2br(htmlspecialchars($cmt['Content'])) ?>
                    </div>
                    <div style="color:#888;font-size:0.9em;">
                        <?= htmlspecialchars($cmt['FullName']) ?> - <?= date('H:i d/m/Y', strtotime($cmt['CreatedAt'])) ?>
                    </div>
                </div>
            </div>
        <?php } ?>
        <?php if (count($comments) === 0) echo '<p>Chưa có bình luận nào.</p>'; ?>
    </div>
<?php
    exit;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết bài viết</title>
    <link rel="stylesheet" href="../assets/Style-css/admin_layout.css">
    <link rel="stylesheet" href="../assets/Style-css/posts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .post-detail-container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px #0001; padding: 32px; }
        .post-detail-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
        .post-detail-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; }
        .post-detail-img { width: 100%; max-height: 350px; object-fit: cover; border-radius: 8px; margin-bottom: 16px; }
        .post-detail-content { font-size: 1.2em; margin-bottom: 12px; }
        .post-detail-meta { color: #888; font-size: 0.95em; margin-bottom: 8px; }
        .post-detail-stats { margin-bottom: 16px; }
        .post-detail-stats span { margin-right: 18px; color: #555; }
        .comments-section { margin-top: 32px; }
        .comment-item { border-top: 1px solid #eee; padding: 12px 0; display: flex; gap: 12px; }
        .comment-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
        .comment-content { background: #f7f7f7; border-radius: 8px; padding: 8px 14px; }
        .comment-meta { color: #888; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="post-detail-container">
        <div class="post-detail-header">
            <img src="<?= !empty($post['Avatar']) ? '../uploads/avatars/' . htmlspecialchars($post['Avatar']) : '../uploads/avatars/default_avatar.png' ?>" class="post-detail-avatar" alt="Avatar">
            <div>
                <strong><?= htmlspecialchars($post['FullName']) ?></strong><br>
                <span class="post-detail-meta">Đăng lúc <?= date('H:i d/m/Y', strtotime($post['CreatedAt'])) ?></span>
            </div>
        </div>
        <img src="<?= $image_src ?>" class="post-detail-img" alt="Ảnh bài viết">
        <div class="post-detail-content">
            <?= nl2br(htmlspecialchars($post['Content'])) ?>
        </div>
        <div class="post-detail-stats">
            <span><i class="fa-solid fa-thumbs-up"></i> <?= $post['LikeCount'] ?></span>
            <span><i class="fa-solid fa-comments"></i> <?= $post['CommentCount'] ?></span>
            <span><i class="fa-regular fa-clock"></i> Cập nhật: <?= timeAgo($post['UpdatedAt']) ?></span>
        </div>
        <a href="dashboard.php" style="color:#2a69e5; text-decoration:underline;">&larr; Quay lại Dashboard</a>
        <div class="comments-section">
            <h4>Bình luận (<?= count($comments) ?>)</h4>
            <?php foreach ($comments as $cmt) { ?>
                <div class="comment-item">
                    <img src="<?= !empty($cmt['Avatar']) ? '../uploads/avatars/' . htmlspecialchars($cmt['Avatar']) : '../uploads/avatars/default_avatar.png' ?>" class="comment-avatar" alt="Avatar">
                    <div>
                        <div class="comment-content">
                            <?= nl2br(htmlspecialchars($cmt['Content'])) ?>
                        </div>
                        <div class="comment-meta">
                            <?= htmlspecialchars($cmt['FullName']) ?> - <?= date('H:i d/m/Y', strtotime($cmt['CreatedAt'])) ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <?php if (count($comments) === 0) echo '<p>Chưa có bình luận nào.</p>'; ?>
        </div>
    </div>
</body>
</html>