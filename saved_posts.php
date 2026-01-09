<?php
// saved-posts.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin user
$stmt = mysqli_prepare($conn, "SELECT FullName, Avatar FROM Users WHERE UserID = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$current_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// EF-03: Lấy bài viết đã lưu
$sql = "
    SELECT p.*, u.FullName, u.Avatar,
           pi.ImageUrl,
           p.LikeCount,
           p.CommentCount,
           (SELECT COUNT(*) FROM Likes WHERE FK_PostID = p.PostID AND FK_UserID = ?) as user_liked,
           sp.SavedAt
    FROM Saved_Posts sp
    JOIN Posts p ON sp.FK_PostID = p.PostID
    JOIN Users u ON p.FK_UserID = u.UserID
    LEFT JOIN Post_Images pi ON p.PostID = pi.FK_PostID
    WHERE sp.FK_UserID = ?
    AND p.Status != 'deleted'
    GROUP BY p.PostID
    ORDER BY sp.SavedAt DESC
    LIMIT 20
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt);
$saved_posts = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đã lưu - TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* GIỮ NGUYÊN TOÀN BỘ CSS CỦA BẠN TỪ INDEX.PHP */
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background:#f0f2f5; color:#1c1e21; }
        
        .container { max-width:1200px; margin:0 auto; padding:0 20px; }
        
        .navbar {
            background:#fff; height:60px; padding:0 16px;
            display:flex; align-items:center; justify-content:space-between;
            box-shadow:0 1px 2px rgba(0,0,0,0.1); position:sticky; top:0; z-index:1000;
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
        
        .main-layout {
            display:grid;
            grid-template-columns:220px 1fr 320px;
            gap:20px;
            margin-top:20px;
        }
        
        .left-sidebar { background:#fff; border-radius:8px; padding:20px; }
        .user-card { display:flex; align-items:center; gap:12px; margin-bottom:20px; }
        .user-avatar { width:60px; height:60px; border-radius:50%; object-fit:cover; border:3px solid #1877f2; }
        .user-info h3 { font-size:1.1rem; font-weight:600; }
        .user-info p { color:#65676b; font-size:0.9rem; }
        
        .menu { margin-bottom:20px; }
        .menu-item { display:flex; align-items:center; gap:12px; padding:12px; border-radius:8px; cursor:pointer; margin:4px 0; }
        .menu-item:hover { background:#f0f2f5; }
        .menu-item.active { background:#e7f3ff; color:#1877f2; font-weight:600; }
        .menu-item i { width:24px; }
        
        .news-feed { background:#fff; border-radius:8px; padding:20px; }
        
        .post { border:1px solid #e4e6eb; border-radius:8px; padding:16px; margin-bottom:16px; }
        .post-header { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
        .post-avatar { width:40px; height:40px; border-radius:50%; object-fit:cover; }
        .post-user h4 { font-weight:600; font-size:0.95rem; }
        .post-user .time { color:#65676b; font-size:0.8rem; }
        
        .post-content { margin-bottom:12px; line-height:1.5; }
        .post-stats { display:flex; justify-content:space-between; color:#65676b; font-size:0.9rem; padding:8px 0; border-top:1px solid #e4e6eb; border-bottom:1px solid #e4e6eb; margin:10px 0; }
        
        .post-actions-row { display:flex; justify-content:space-around; padding-top:8px; }
        .post-action-btn { display:flex; align-items:center; gap:8px; padding:8px 12px; color:#65676b; cursor:pointer; border-radius:4px; background:none; border:none; font-size:0.9rem; font-weight:600; width:100%; justify-content:center; }
        .post-action-btn:hover { background:#f0f2f5; }
        .post-action-btn.liked { color:#1877f2; }
        .post-action-btn.saved { color:#ffc107; }
        
        .right-sidebar { background:#fff; border-radius:8px; padding:20px; }
        .section { margin-bottom:24px; }
        .section-title { font-size:1.1rem; font-weight:600; margin-bottom:16px; padding-bottom:8px; border-bottom:2px solid #1877f2; }
        
        .friend { display:flex; align-items:center; gap:12px; padding:12px; border-radius:8px; margin-bottom:8px; }
        .friend:hover { background:#f0f2f5; }
        .friend-avatar { width:40px; height:40px; border-radius:50%; object-fit:cover; }
        .friend-info { flex:1; }
        .friend-name { font-weight:600; font-size:0.95rem; }
        .friend-desc { color:#65676b; font-size:0.85rem; }
        .add-btn { padding:6px 12px; background:#1877f2; color:white; border:none; border-radius:6px; cursor:pointer; font-size:0.85rem; }
        
        /* EF-03: Saved badge */
        .saved-badge {
            margin-left: auto;
            background: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #65676b;
        }
        .empty-icon {
            font-size: 4rem;
            color: #e4e6eb;
            margin-bottom: 20px;
        }
        .browse-btn {
            display: inline-block;
            margin-top: 15px;
            background: #1877f2;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
        }
        
        @media (max-width: 1100px) {
            .main-layout { grid-template-columns:280px 1fr; }
            .right-sidebar { display:none; }
        }
        @media (max-width: 768px) {
            .main-layout { grid-template-columns:1fr; }
            .left-sidebar { display:none; }
            .nav-item { padding:0 15px; }
            .search-box { width:180px; }
        }
    </style>
</head>
<body>
    
    <nav class="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo">TSix</a>
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass" style="color: #65676b"></i>
                <input type="text" placeholder="Tìm kiếm bài đã lưu">
            </div>
        </div>
        <div class="nav-center">
            <div class="nav-item"><i class="fas fa-home"></i></div>
            <div class="nav-item active"><i class="fas fa-bookmark"></i></div>
        </div>
        
        
        <div class="nav-right">
            <div class="nav-icon-circle"><i class="fas fa-bars"></i></div>
            <div class="nav-icon-circle"><i class="fab fa-facebook-messenger"></i></div>
            <div class="nav-icon-circle"><i class="fas fa-bell"></i></div>
            
            <img src="uploads/avatars/<?php echo $current_user['Avatar'] ?: 'default_avatar.png'; ?>" 
                 class="nav-avatar" 
                 onclick="window.location.href='profile.php'">
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="main-layout">
            
            <!-- Left Sidebar -->
            <div class="left-sidebar">
                <div class="user-card">
                    <img src="uploads/avatars/<?php echo $current_user['Avatar'] ?: 'default_avatar.png'; ?>" class="user-avatar">
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($current_user['FullName']); ?></h3>
                        <p>@<?php echo strtolower(str_replace(' ', '', $current_user['FullName'])); ?></p>
                    </div>
                </div>
                
                <div class="menu">
                    <div class="menu-item" onclick="window.location.href='index.php'">
                        <i class="fas fa-home"></i>
                        <span>Bảng tin</span>
                    </div>
                    <div class="menu-item" onclick="window.location.href='profile.php'">
                        <i class="fas fa-user"></i>
                        <span>Trang cá nhân</span>
                    </div>
                    <div class="menu-item active">
                        <i class="fas fa-bookmark"></i>
                        <span>Đã lưu</span>
                    </div>
                    <div class="menu-item">
                        <i class="fas fa-user-friends"></i>
                        <span>Bạn bè</span>
                    </div>
                </div>
            </div>

            <!-- News Feed -->
            <div class="news-feed">
                <!-- Header -->
                <div style="border-bottom:1px solid #e4e6eb; padding-bottom:20px; margin-bottom:20px;">
                    <h2 style="font-size:1.5rem; color:#1c1e21; display:flex; align-items:center; gap:10px;">
                        <i class="fas fa-bookmark"></i> Bài viết đã lưu
                    </h2>
                    <p style="color:#65676b; font-size:0.9rem; margin-top:5px;">
                        <?php echo count($saved_posts); ?> bài viết
                    </p>
                </div>
                
                <!-- EF-03: Danh sách bài viết đã lưu -->
                <?php if (empty($saved_posts)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fas fa-bookmark"></i></div>
                        <h3>Chưa có bài viết nào được lưu</h3>
                        <p>Khi bạn lưu bài viết, chúng sẽ xuất hiện ở đây.</p>
                        <a href="index.php" class="browse-btn">
                            <i class="fas fa-newspaper"></i> Duyệt bài viết
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($saved_posts as $post): ?>
                    <div class="post" id="post-<?php echo $post['PostID']; ?>">
                        <div class="post-header">
                            <img src="uploads/avatars/<?php echo $post['Avatar'] ?: 'default_avatar.png'; ?>" class="post-avatar">
                            <div class="post-user">
                                <h4><?php echo htmlspecialchars($post['FullName']); ?></h4>
                                <div class="time"><?php echo date('d/m/Y H:i', strtotime($post['CreatedAt'])); ?></div>
                            </div>
                            <div class="saved-badge">
                                <i class="fas fa-bookmark"></i> Đã lưu
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['Content'])); ?>
                        </div>
                        
                        <?php if (!empty($post['ImageUrl'])): ?>
                        <div style="margin:10px 0;">
                            <img src="uploads/posts/<?php echo $post['ImageUrl']; ?>" 
                                 style="width:100%; max-height:500px; object-fit:cover; border-radius:8px;">
                        </div>
                        <?php endif; ?>
                        
                        <div class="post-stats">
                            <div>
                                <i class="fas fa-thumbs-up" style="color:#1877f2; font-size:12px;"></i>
                                <?php echo $post['LikeCount']; ?>
                            </div>
                            <div><?php echo $post['CommentCount']; ?> bình luận</div>
                        </div>
                        
                        <div class="post-actions-row">
                            <button class="post-action-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                    onclick="likePost(this, <?php echo $post['PostID']; ?>)">
                                <i class="far fa-thumbs-up"></i> 
                                <?php echo $post['user_liked'] ? 'Đã thích' : 'Thích'; ?>
                            </button>
                            <button class="post-action-btn" onclick="commentPost(<?php echo $post['PostID']; ?>)">
                                <i class="far fa-comment"></i> Bình luận
                            </button>
                            <button class="post-action-btn saved" onclick="unsavePost(<?php echo $post['PostID']; ?>)">
                                <i class="fas fa-bookmark"></i> Bỏ lưu
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Right Sidebar -->
            <div class="right-sidebar">
                <div class="section">
                    <div class="section-title">Lưu ý</div>
                    <p style="color:#65676b; font-size:0.9rem; line-height:1.5;">
                        <i class="fas fa-info-circle" style="color:#1877f2;"></i> 
                        Bài viết đã lưu sẽ được lưu trữ riêng tư.
                    </p>
                    <p style="color:#65676b; font-size:0.9rem; line-height:1.5; margin-top:10px;">
                        <i class="fas fa-trash-alt" style="color:#f02849;"></i> 
                        Nhấn "Bỏ lưu" để xóa bài viết khỏi danh sách này.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Like post
        function likePost(btn, postId) {
            const isLiked = btn.classList.contains('liked');
            
            fetch('ajax/like.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'post_id=' + postId + '&action=' + (isLiked ? 'unlike' : 'like')
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    btn.classList.toggle('liked');
                    btn.innerHTML = `<i class="far fa-thumbs-up"></i> ${data.liked ? 'Đã thích' : 'Thích'}`;
                    
                    const likeCount = btn.closest('.post').querySelector('.post-stats i.fa-thumbs-up').parentNode;
                    likeCount.innerHTML = `<i class="fas fa-thumbs-up" style="color:#1877f2; font-size:12px;"></i> ${data.like_count}`;
                }
            });
        }
        
        // EF-03: Unsave post
        function unsavePost(postId) {
            if(confirm('Bỏ lưu bài viết này?')) {
                fetch('ajax/save.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'post_id=' + postId + '&action=unsave'
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success && !data.saved) {
                        const postElement = document.getElementById('post-' + postId);
                        postElement.style.opacity = '0.5';
                        setTimeout(() => {
                            postElement.remove();
                            
                            // Cập nhật số lượng
                            const countElement = document.querySelector('.news-feed p');
                            if(countElement) {
                                const current = parseInt(countElement.textContent);
                                countElement.textContent = (current - 1) + ' bài viết';
                            }
                            
                            // Hiển thị empty state nếu không còn bài
                            if(document.querySelectorAll('.post').length === 0) {
                                location.reload();
                            }
                        }, 300);
                    }
                });
            }
        }
        
        function commentPost(postId) {
            alert('Chức năng bình luận đang phát triển');
        }
    </script>
</body>
</html>