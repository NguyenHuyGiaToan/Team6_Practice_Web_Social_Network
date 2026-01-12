<?php
// saved-posts.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

// --- PHẦN TÍCH HỢP XỬ LÝ AJAX (Bỏ lưu) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unsave') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Hết phiên đăng nhập']);
        exit();
    }

    $u_id = $_SESSION['user_id'];
    $p_id = intval($_POST['post_id'] ?? 0);

    // Xóa khỏi Database
    $stmt_del = mysqli_prepare($conn, "DELETE FROM Saved_Posts WHERE FK_UserID = ? AND FK_PostID = ?");
    mysqli_stmt_bind_param($stmt_del, "ii", $u_id, $p_id);
    
    if (mysqli_stmt_execute($stmt_del)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn']);
    }
    exit(); // Dừng lại ở đây, không chạy xuống phần giao diện bên dưới
}
// --- HẾT PHẦN XỬ LÝ AJAX ---

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
            grid-template-columns: 800px 1fr 20px; 
            gap: 25px; 
            margin: 20px auto;
            max-width: 1100px;
            align-items: start;
            justify-content: center;
        }

        /* Nâng cấp News Feed (Khung chứa bài viết) */
        .news-feed {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin: 20px auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            min-height: 70vh; 
            max-width: 680px;
            border: #8B1E29 1px solid;
        }

        /* Header khu vực bài viết đã lưu */
        .feed-header {
            border-bottom: 1px solid #8B1E29;
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

        /* Container chính cho bài viết */
       .post {
            background: #fff;
            border: 1px solid #dddfe2;
            border-radius: 8px;
            width: 600px;
            padding: 12px 0 0 0;
            margin-bottom: 16px;
            /* Hiệu ứng mượt mà khi ẩn bài viết */
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        /* Header bài viết */
        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 12px 12px 12px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* FIX LỖI AVATAR QUÁ TO */
        .post-avatar {
            width: 40px !important;
            height: 40px !important;
            border-radius: 50%;
            object-fit: cover;
        }

        .post-user h4 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
        }

        .post-user .time {
            font-size: 13px;
            color: #65676b;
        }

        /* Nội dung văn bản */
        .post-content {
            padding: 0 12px 12px 12px;
            font-size: 15px;
        }

        /* Ảnh bài viết tràn viền */
        .post-images {
            width: 100%;
            border-top: 1px solid #ebedf0;
            border-bottom: 1px solid #ebedf0;
        }

        .post-images img {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            display: block;
        }

        /* Thống kê Like/Comment */
        .post-stats {
            display: flex;
            justify-content: space-between;
            padding: 10px 12px;
            border-bottom: 1px solid #ebedf0;
            font-size: 14px;
            color: #65676B;
        }

        /* Nút tương tác */
        .post-actions-row {
            display: flex;
            padding: 4px 12px;
        }

        .post-action-btn {
            flex: 1;
            background: none;
            border: none;
            padding: 8px;
            color: #65676b;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .post-action-btn:hover {
            background: #f2f2f2;
            border-radius: 4px;
        }

        .saved-badge-top {
            color: #65676b;
            font-size: 13px;
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
                            <div class="header-left">
                                <img src="<?php echo BASE_URL ?>/uploads/avatars/<?php echo $post['Avatar'] ?: 'default_avatar.png'; ?>" class="post-avatar">
                                <div class="post-user">
                                    <h4><?php echo htmlspecialchars($post['FullName']); ?></h4>
                                    <div class="time"><?php echo date('d/m/Y H:i', strtotime($post['CreatedAt'])); ?> <i class="fas fa-globe-asia"></i></div>
                                </div>
                            </div>
                            <div class="saved-badge-top">
                                <i class="fas fa-bookmark"></i> Đã lưu
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['Content'])); ?>
                        </div>
                        
                        <?php if (!empty($post['ImageUrl'])): ?>
                        <div class="post-images">
                            <img src="<?php echo BASE_URL ?>/uploads/posts/<?php echo $post['ImageUrl']; ?>">
                        </div>
                        <?php endif; ?>
                        
                        <div class="post-stats">
                            <div class="stats-left">
                                <i class="fas fa-thumbs-up" style="color:#e0245e;"></i> <span><?php echo $post['LikeCount']; ?></span>
                            </div>
                            <div class="stats-right">
                                <?php echo $post['CommentCount']; ?> bình luận
                            </div>
                        </div>
                        
                        <div class="post-actions-row">
                            <button class="post-action-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" onclick="likePost(this, <?php echo $post['PostID']; ?>)">
                                <i class="far fa-thumbs-up"></i> Thích
                            </button>
                            <button class="post-action-btn" onclick="commentPost(<?php echo $post['PostID']; ?>)">
                                <i class="far fa-comment"></i> Bình luận
                            </button>
                            <button class="post-action-btn" onclick="unsavePost(<?php echo $post['PostID']; ?>)">
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
            if(confirm('Bạn có chắc chắn muốn bỏ lưu bài viết này?')) {
                const postElement = document.getElementById('post-' + postId);
                
                // Gửi yêu cầu đến chính trang hiện tại
                fetch('saved_posts.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `post_id=${postId}&action=unsave`
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        // Hiệu ứng giao diện: Thu nhỏ và mờ dần
                        postElement.style.opacity = '0';
                        postElement.style.transform = 'scale(0.8)';
                        postElement.style.maxHeight = '0';
                        postElement.style.marginBottom = '0';
                        postElement.style.paddingTop = '0';

                        setTimeout(() => {
                            postElement.remove();
                            
                            // Cập nhật số lượng bài viết trên tiêu đề
                            const countElement = document.querySelector('.feed-header p');
                            if(countElement) {
                                let current = parseInt(countElement.textContent);
                                countElement.textContent = (current - 1) + ' Bài viết';
                            }
                            
                            // Nếu không còn bài nào, reload để hiện giao diện trống
                            if(document.querySelectorAll('.news-feed .post').length === 0) {
                                location.reload();
                            }
                        }, 400);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => console.error('Lỗi:', err));
            }
        }
        
        function commentPost(postId) {
            alert('Chức năng bình luận đang phát triển');
        }
    </script>
</body>
</html>