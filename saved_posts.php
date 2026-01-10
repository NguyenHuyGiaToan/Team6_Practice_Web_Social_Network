<?php
// saved-posts.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/header.php'; 

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
        /* Bố cục chính */      
        .main-layout {
            display: grid;
            grid-template-columns: 1000px 1fr 20px; 
            gap: 30px; 
            margin-top: 20px;
            align-items: start;
        }

        /* Nâng cấp News Feed (Khung chứa bài viết) */
        .news-feed {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            min-height: 70vh; /* Đảm bảo khung luôn đủ cao */
        }

        /* Header khu vực bài viết đã lưu */
        .feed-header {
            border-bottom: 1px solid #f0f2f5;
            padding-bottom: 20px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .feed-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            color: #1c1e21;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .feed-header h2 i {
            color: #8B1E29; 
        }

        .feed-header p {
            color: #65676b;
            font-size: 1rem;
            font-weight: 500;
        }

        /* Nâng cấp giao diện từng bài viết */
        .post {
            border: 1px solid #f0f2f5;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }

        .post:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-color: #e4e6eb;
        }

        
        .saved-badge {
            background: #f0f2f5;
            color: #65676b;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

       
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 100px 20px;
            text-align: center;
        }

        .empty-icon-container {
            width: 120px;
            height: 120px;
            background: #f0f2f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
            color: #bcc0c4;
        }
    </style>
</head>
<body>

    <!-- Main Content -->
    <div class="container">
        <div class="main-layout">
            <!-- News Feed -->
            <div class="news-feed">
                <!-- Header -->
                <div class="feed-header">
                    <h2><i class="fas fa-bookmark"></i> Bài viết đã lưu</h2>
                    <p><?php echo count($saved_posts); ?> Bài viết</p>
                </div>
                
                <!-- EF-03: Danh sách bài viết đã lưu -->
                <?php if (empty($saved_posts)): ?>
                    <div class="empty-state">
                        <div class="empty-icon-container">
                            <i class="fas fa-bookmark" style="font-size: 4rem;"></i>
                        </div>
                        <h3>Kho lưu trữ đang trống</h3>
                        <p>Hãy lưu lại những bài viết thú vị từ bảng tin để có thể xem lại tại đây bất cứ lúc nào.</p>
                        <a href="index.php" class="browse-btn" style="margin-top: 20px; padding: 12px 25px; background: #1877f2; color: white; border-radius: 8px; text-decoration: none; font-weight: 600;">Khám phá bảng tin ngay</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($saved_posts as $post): ?>
                    <div class="post" id="post-<?php echo $post['PostID']; ?>">
                        <div class="post-header">
                            <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo $post['Avatar'] ?: 'default_avatar.png'; ?>" class="post-avatar">
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
                            <img src="<?php echo BASE_URL; ?>uploads/posts/<?php echo $post['ImageUrl']; ?>" 
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