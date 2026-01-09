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

$user_id = $_SESSION['user_id'];

// Lấy thông tin user một lần duy nhất (chỉ cần FullName và Avatar)
$stmt = mysqli_prepare($conn, "SELECT FullName, Avatar FROM Users WHERE UserID = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$current_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Lưu thông tin vào session để sử dụng sau này
$_SESSION['user_fullname'] = $current_user['FullName'];
$_SESSION['user_avatar']    = $current_user['Avatar'] ?? null;

// Hàm hỗ trợ hiển thị avatar
function getAvatarUrl() {
    if (!empty($_SESSION['user_avatar'])) {
        return 'uploads/avatars/' . htmlspecialchars($_SESSION['user_avatar']);
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user_fullname']) . '&background=8B1E29&color=fff&size=200';
}

// 1. Lấy danh sách UserID mà current user đang theo dõi
$following_stmt = mysqli_prepare($conn, "
    SELECT FK_FollowingID 
    FROM FOLLOWS 
    WHERE FK_FollowerID = ?
");
mysqli_stmt_bind_param($following_stmt, "i", $user_id);
mysqli_stmt_execute($following_stmt);
$following_result = mysqli_stmt_get_result($following_stmt);

$following_ids = [$user_id]; // Luôn bao gồm bài viết của chính mình
while ($row = mysqli_fetch_assoc($following_result)) {
    $following_ids[] = $row['FK_FollowingID'];
}
$following_list = implode(',', $following_ids);

// 2. Lấy bài viết từ những người đang theo dõi + bản thân

// Logic: (Là bài active của người mình follow) HOẶC (Là bài của chính mình dù trạng thái gì, trừ đã xóa)
$posts_query = "
    SELECT 
        p.PostID, p.FK_UserID, p.Content, p.LikeCount, p.CommentCount, p.CreatedAt, p.Status,
        u.FullName, u.Avatar, 
        (l.FK_UserID IS NOT NULL) AS user_liked
    FROM POSTS p
    JOIN Users u ON p.FK_UserID = u.UserID
    LEFT JOIN LIKES l ON l.FK_PostID = p.PostID AND l.FK_UserID = ?
    WHERE 
        -- Điều kiện 1: Nằm trong danh sách follow hoặc là chính mình
        p.FK_UserID IN ($following_list) 
        AND (
            -- Điều kiện 2a: Nếu là bài người khác -> Phải Active
            (p.FK_UserID != ? AND p.Status = 'active')
            OR
            -- Điều kiện 2b: Nếu là bài của mình -> Hiện tất cả (Active/Private) trừ Deleted
            (p.FK_UserID = ? AND p.Status != 'deleted')
        )
    ORDER BY p.CreatedAt DESC 
    LIMIT 20
";

$posts_stmt = mysqli_prepare($conn, $posts_query);

mysqli_stmt_bind_param($posts_stmt, "iii", $user_id, $user_id, $user_id);
mysqli_stmt_execute($posts_stmt);
$posts_result = mysqli_stmt_get_result($posts_stmt);

$posts = [];
while ($post = mysqli_fetch_assoc($posts_result)) {
    // Định dạng thời gian tương đối
    $post['time_ago'] = timeAgo($post['CreatedAt']); // Hàm timeAgo() phải có trong functions.php

    // Xử lý avatar
    $post['avatar_url'] = !empty($post['Avatar'])
        ? 'uploads/avatars/' . htmlspecialchars($post['Avatar'])
        : 'https://ui-avatars.com/api/?name=' . urlencode($post['FullName']) . '&background=8B1E29&color=fff&size=200';

    $posts[] = $post;
}

// 3. Lấy hình ảnh cho từng bài viết (nếu có)
foreach ($posts as &$post) {
    $img_stmt = mysqli_prepare($conn, "
        SELECT ImageUrl 
        FROM POST_IMAGES 
        WHERE FK_PostID = ? 
        ORDER BY ImageID 
        LIMIT 4
    ");
    mysqli_stmt_bind_param($img_stmt, "i", $post['PostID']);
    mysqli_stmt_execute($img_stmt);
    $img_result = mysqli_stmt_get_result($img_stmt);

    $post['images'] = [];
    while ($img = mysqli_fetch_assoc($img_result)) {
        $post['images'][] = 'uploads/posts/' . htmlspecialchars($img['ImageUrl']);
    }
}
unset($post); 

// === GỢI Ý KẾT BẠN (RIGHT SIDEBAR) ===
// Lấy 5 người dùng ngẫu nhiên mà current user CHƯA follow và họ cũng CHƯA follow mình
$friends_query = "
    SELECT 
        u.UserID, 
        u.FullName, 
        u.Avatar,
        'Đại học Ngân hàng TPHCM' AS info
    FROM Users u
    WHERE u.UserID != ?
      AND u.Status = 'active'
      AND u.Role = 'user'
      AND u.UserID NOT IN (
          SELECT FK_FollowingID FROM FOLLOWS WHERE FK_FollowerID = ?
      )
      AND u.UserID NOT IN (
          SELECT FK_FollowerID FROM FOLLOWS WHERE FK_FollowingID = ?
      )
    ORDER BY RAND()
    LIMIT 5
";

$friends_stmt = mysqli_prepare($conn, $friends_query);
mysqli_stmt_bind_param($friends_stmt, "iii", $user_id, $user_id, $user_id);
mysqli_stmt_execute($friends_stmt);
$friends_result = mysqli_stmt_get_result($friends_stmt);

$friends = [];
while ($friend = mysqli_fetch_assoc($friends_result)) {
    $friend['avatar_url'] = !empty($friend['Avatar'])
        ? 'uploads/avatars/' . htmlspecialchars($friend['Avatar'])
        : 'https://ui-avatars.com/api/?name=' . urlencode($friend['FullName']) . '&background=8B1E29&color=fff&size=200';
    
    $friends[] = $friend;
}



?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TSix - Bảng tin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background:#f0f2f5; color:#1c1e21; }
        
        /* Container */
        .container { max-width:1200px; margin:0 auto; padding:0 20px; }
        
        /* Main Layout */
        .main-layout {
            display:grid;
            grid-template-columns:280px 1fr 320px;
            gap:20px;
            margin-top:20px;
        }
        
        /* Left Sidebar */
        .left-sidebar { background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 2px rgba(0,0,0,0.1); }
        .user-card { display:flex; align-items:center; gap:12px; margin-bottom:20px; }
        .user-avatar { width:60px; height:60px; border-radius:50%; object-fit:cover; }
        .user-info h3 { font-size:1.1rem; font-weight:600; }
        .user-info p { color:#65676b; font-size:0.9rem; }
        
        .menu { margin-bottom:20px; }
        .menu-item { display:flex; align-items:center; gap:12px; padding:12px; border-radius:8px; cursor:pointer; margin:4px 0; }
        .menu-item:hover { background:#f0f2f5; }
        .menu-item.active { background:#e7f3ff; color:#8B1E29; font-weight:600; }
        .menu-item i { width:24px; font-size:1.2rem; }
        
        /* News Feed */
        .news-feed { background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 2px rgba(0,0,0,0.1); }
        
        .create-post { margin-bottom:20px; background:#fff; border-radius:8px; padding:12px; box-shadow:0 1px 2px rgba(0,0,0,0.1); }
        .create-post-top { display:flex; gap:10px; padding-bottom:12px; margin-bottom:12px; border-bottom:1px solid #e4e6eb; }
        .create-post-top img { width:40px; height:40px; border-radius:50%; object-fit:cover; }
        .input-mind { flex:1; padding:12px; background:#f0f2f5; border:none; border-radius:30px; cursor:pointer; font-size:1rem; }
        .input-mind::placeholder { color:#65676b; }
        
        .post-actions { display:flex; justify-content:space-around; }
        .action-btn { display:flex; align-items:center; gap:8px; padding:10px; color:#65676b; cursor:pointer; border-radius:8px; font-weight:600; }
        .action-btn:hover { background:#f0f2f5; }
        
        .post { background:#fff; border-radius:8px; padding:16px; margin-bottom:16px; box-shadow:0 1px 2px rgba(0,0,0,0.1); }
        .post-header { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
        .post-avatar { width:40px; height:40px; border-radius:50%; object-fit:cover; }
        .post-user h4 { font-weight:600; font-size:0.95rem; }
        .post-user .time { color:#65676b; font-size:0.8rem; }
        
        .post-content { margin-bottom:12px; line-height:1.5; font-size:0.95rem; }
        .post-stats { display:flex; justify-content:space-between; color:#65676b; font-size:0.9rem; padding:8px 0; border-top:1px solid #e4e6eb; border-bottom:1px solid #e4e6eb; margin:10px 0; }
        
        .post-actions-row { display:flex; justify-content:space-around; padding-top:8px; }
        .post-action-btn { display:flex; align-items:center; gap:8px; padding:8px 12px; color:#65676b; cursor:pointer; border-radius:4px; background:none; border:none; font-size:0.9rem; font-weight:600; width:100%; justify-content:center; }
        .post-action-btn:hover { background:#f0f2f5; }
        .post-action-btn.liked { color:#8B1E29; }
        
        /* Right Sidebar */
        .right-sidebar { background:#fff; border-radius:8px; padding:20px; box-shadow:0 1px 2px rgba(0,0,0,0.1); }
        .section { margin-bottom:24px; }
        .section-title { font-size:1.1rem; font-weight:600; margin-bottom:16px; padding-bottom:8px; border-bottom:2px solid #8B1E29; }
        
        .friend { display:flex; align-items:center; gap:12px; padding:12px; border-radius:8px; margin-bottom:8px; cursor:pointer; }
        .friend:hover { background:#f0f2f5; }
        .friend-avatar { width:40px; height:40px; border-radius:50%; object-fit:cover; }
        .friend-info { flex:1; }
        .friend-name { font-weight:600; font-size:0.95rem; }
        .friend-desc { color:#65676b; font-size:0.85rem; }
        .add-btn { padding:6px 12px; background:#8B1E29; color:white; border:none; border-radius:6px; cursor:pointer; font-size:0.85rem; font-weight:600; }
        .add-btn:hover { background:#70171f; }

        /* Responsive */
        @media (max-width: 1100px) {
            .main-layout { grid-template-columns:280px 1fr; }
            .right-sidebar { display:none; }
        }
        @media (max-width: 768px) {
            .main-layout { grid-template-columns:1fr; }
            .left-sidebar { display:none; }
        }
        .comments-section {
        border-top: 1px solid #e4e6eb;
        padding-top: 12px;
        }

        .comment-item {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            align-items: flex-start;
        }

        .comment-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .comment-bubble {
            background: #f0f2f5;
            padding: 8px 12px;
            border-radius: 18px;
            max-width: 80%;
            position: relative;
        }

        .comment-author {
            font-weight: 600;
            font-size: 0.9rem;
            color: #050505;
        }

        .comment-content {
            font-size: 0.95rem;
            margin-top: 2px;
        }

        .comment-meta {
            font-size: 0.8rem;
            color: #65676b;
            margin-top: 4px;
        }

        .comment-actions {
            margin-top: 4px;
            font-size: 0.85rem;
            color: #65676b;
        }

        .comment-actions span {
            cursor: pointer;
            margin-right: 12px;
        }

        .comment-actions span:hover {
            text-decoration: underline;
        }

        .comment-edit-input {
            width: 100%;
            padding: 8px 12px;
            border-radius: 18px;
            border: 1px solid #ccd0d5;
            outline: none;
            margin-top: 8px;
        }
    </style>
</head>
<body>

    <!-- Main Content -->
    <div class="container">
        <div class="main-layout">
            
            <!-- Left Sidebar -->
            <div class="left-sidebar">
                <div class="user-card">
                    <img src="<?php echo getAvatarUrl(); ?>" class="user-avatar" alt="Avatar">
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($_SESSION['user_fullname']); ?></h3>
                        <p>@<?php echo strtolower(str_replace(' ', '', $_SESSION['user_fullname'])); ?></p>
                    </div>
                </div>
                
                <div class="menu">
                    <div class="menu-item active">
                        <i class="fa-solid fa-house"></i>
                        <span>Bảng tin</span>
                    </div>
                    <div class="menu-item" onclick="window.location.href='profile.php'">
                        <i class="fa-solid fa-user"></i>
                        <span>Trang cá nhân</span>
                    </div>
                    <div class="menu-item" onclick="window.location.href='saved-posts.php'">
                        <i class="fa-solid fa-bookmark"></i>
                        <span>Đã lưu</span>
                    </div>
                    <div class="menu-item" onclick="window.location.href='friends.php'">
                        <i class="fa-solid fa-user-group"></i>
                        <span>Bạn bè</span>
                    </div>
                    <div class="menu-item">
                        <i class="fa-solid fa-cog"></i>
                        <span>Cài đặt</span>
                    </div>
                </div>
            </div>

            <!-- News Feed -->
            <div class="news-feed">
                <!-- Create Post -->
                <div class="create-post">
                    <div class="create-post-top">
                        <img src="<?php echo getAvatarUrl(); ?>" alt="Avatar" onclick="window.location.href='profile.php'">
                        <div class="input-mind-trigger" onclick="window.location.href='post.php'">
                            Bạn đang nghĩ gì, <?php echo explode(' ', $_SESSION['user_fullname'])[0]; ?>?
                        </div>
                    </div>
                    <div class="post-actions">
                        <div class="action-btn" onclick="window.location.href='post.php'"><i class="fa-solid fa-image" style="color:#45bd62"></i> Ảnh</div>
                        <div class="action-btn" onclick="window.location.href='post.php'"><i class="fa-regular fa-face-smile" style="color:#f7b928"></i> Cảm xúc</div>
                    </div>
                </div>

                <!-- Danh sách bài viết -->
                <?php foreach($posts as $post): ?>
                <div class="post">
                    <div class="post-header">
                        <img src="<?php echo $post['avatar_url']; ?>" class="post-avatar" alt="Avatar">
                        <div class="post-user">
                            <h4><?php echo htmlspecialchars($post['FullName']); ?></h4>
                            <div class="time"><?php echo $post['time_ago']; ?> <i class="fa-solid fa-earth-americas" style="font-size:12px;"></i></div>
                        </div>
                        <i class="fa-solid fa-ellipsis" style="margin-left:auto; color:#65676b; cursor:pointer;"></i>
                    </div>
                    
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['Content'])); ?>
                    </div>

                    <!-- Hiển thị hình ảnh nếu có -->
                    <?php if (!empty($post['images'])): ?>
                    <div class="post-images" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:4px; margin:12px -16px;">
                        <?php foreach($post['images'] as $img): ?>
                        <img src="<?php echo $img; ?>" style="width:100%; border-radius:8px; object-fit:cover;" alt="Post image">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="post-stats">
                        <div>
                            <i class="fa-solid fa-thumbs-up" style="color:#8B1E29;"></i>
                            <span class="like-count"><?php echo $post['LikeCount']; ?></span>
                        </div>
                        <div><span class="comment-count"><?php echo $post['CommentCount']; ?></span> bình luận</div>
                    </div>

                    <div class="post-actions-row">
                        <button class="post-action-btn <?php echo $post['user_liked'] ? 'liked' : ''; ?>" 
                                data-post-id="<?php echo $post['PostID']; ?>">
                            <i class="fa-regular fa-thumbs-up"></i> 
                            <span><?php echo $post['user_liked'] ? 'Đã thích' : 'Thích'; ?></span>
                        </button>
                        <button class="post-action-btn btn-show-comments">
                            <i class="fa-regular fa-comment"></i> Bình luận
                        </button>
                        <button class="post-action-btn">
                            <i class="fa-solid fa-share"></i> Chia sẻ
                        </button>
                    </div>

                    <!-- Khu vực bình luận -->
                    <div class="comments-section" style="margin-top: 16px; display: none;">
                        <!-- Danh sách bình luận -->
                        <div class="comments-list" data-post-id="<?php echo $post['PostID']; ?>">
                            <!-- Bình luận sẽ được load bằng JS -->
                            <div class="text-center" style="color:#65676b; padding:20px;">Đang tải bình luận...</div>
                        </div>

                        <!-- Form thêm bình luận -->
                        <div class="comment-form" style="display:flex; gap:8px; margin-top:12px;">
                            <img src="<?php echo getAvatarUrl(); ?>" style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
                            <input type="text" class="comment-input" placeholder="Viết bình luận..." style="flex:1; padding:10px; border-radius:20px; border:1px solid #ccd0d5; outline:none;">
                            <button class="send-comment" style="background:none; border:none; color:#8B1E29; font-size:1.2rem; cursor:pointer;">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

<?php if (empty($posts)): ?>
<div class="post" style="text-align:center; color:#65676b; padding:40px;">
    Chưa có bài viết nào từ bạn bè. Hãy theo dõi thêm người dùng!
</div>
<?php endif; ?>
            </div>

            <!-- Right Sidebar -->
            <div class="right-sidebar">
                <div class="section">
                    <div class="section-title">Gợi ý kết bạn</div>
                    <?php foreach($friends as $friend): ?>
                    <div class="friend">
                        <img src="<?php echo $friend['avatar_url']; ?>" class="friend-avatar" alt="Avatar">
                        <div class="friend-info">
                            <div class="friend-name"><?php echo htmlspecialchars($friend['FullName']); ?></div>
                            <div class="friend-desc"><?php echo htmlspecialchars($friend['info']); ?></div>
                        </div>
                        <button class="add-btn" data-user-id="<?php echo $friend['UserID']; ?>">
                            + Theo dõi
                        </button>
                    </div>
                    <?php endforeach; ?>
                    <div style="text-align:right; margin-top:10px;">
                        <a href="friends.php" style="color:#8B1E29; font-size:0.9rem; font-weight:600;">Xem tất cả →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Like bài viết (chỉ hiệu ứng giao diện)
        document.querySelectorAll('.post-action-btn').forEach(btn => {
            if (btn.querySelector('i.fa-thumbs-up')) {
                btn.addEventListener('click', function() {
                    this.classList.toggle('liked');
                    const text = this.querySelector('span');
                    if (this.classList.contains('liked')) {
                        text.textContent = 'Đã thích';
                    } else {
                        text.textContent = 'Thích';
                    }
                });
            }
        });
    
        // Load bình luận khi click nút "Bình luận"
        document.querySelectorAll('.btn-show-comments').forEach(btn => {
            btn.addEventListener('click', function() {
                const commentsSection = this.closest('.post').querySelector('.comments-section');
                const commentsList = commentsSection.querySelector('.comments-list');
                const postId = commentsList.dataset.postId;

                if (commentsSection.style.display === 'block') {
                    commentsSection.style.display = 'none';
                } else {
                    commentsSection.style.display = 'block';
                    if (commentsList.innerHTML.includes('Đang tải')) {
                        loadComments(postId, commentsList);
                    }
                }
            });
        });

        // Gửi bình luận
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
                        const commentsList = document.querySelector(`.comments-list[data-post-id="${postId}"]`);
                        commentsList.insertAdjacentHTML('afterbegin', renderComment(data.comment));
                        updateCommentCount(postId, true);
                    } else {
                        alert(data.message);
                    }
                });
            });
        });

        // Enter để gửi bình luận
        document.querySelectorAll('.comment-input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.closest('.comment-form').querySelector('.send-comment').click();
                }
            });
        });

        // Like bài viết
        document.querySelectorAll('.post-action-btn[data-post-id]').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!this.dataset.postId) return;

                const postId = this.dataset.postId;
                const isLiked = this.classList.contains('liked');

                fetch('interaction_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=toggle_like&post_id=${postId}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.classList.toggle('liked', data.liked);
                        this.querySelector('span').textContent = data.liked ? 'Đã thích' : 'Thích';
                        this.closest('.post').querySelector('.like-count').textContent = data.like_count;
                    }
                });
            });
        });

        // Hàm load bình luận
        function loadComments(postId, container) {
            fetch(`interaction_handler.php?action=get_comments&post_id=${postId}`)
            .then(r => r.json())
            .then(data => {
                container.innerHTML = '';
                if (data.comments.length === 0) {
                    container.innerHTML = '<div style="text-align:center; color:#65676b; padding:20px;">Chưa có bình luận nào.</div>';
                } else {
                    data.comments.forEach(comment => {
                        container.insertAdjacentHTML('beforeend', renderComment(comment));
                    });
                }
            });
        }

        // Hàm render một bình luận
        function renderComment(c) {
            return `
            <div class="comment-item" data-comment-id="${c.CommentID}">
                <img src="${c.avatar_url}" class="comment-avatar">
                <div>
                    <div class="comment-bubble">
                        <div class="comment-author">${c.FullName}</div>
                        <div class="comment-content">${c.Content.replace(/\n/g, '<br>')}</div>
                        <div class="comment-meta">${c.time_ago}</div>
                        ${c.is_owner ? `
                        <div class="comment-actions">
                            <span onclick="editComment(this)">Sửa</span>
                            <span onclick="deleteComment(this)">Xóa</span>
                        </div>` : ''}
                    </div>
                </div>
            </div>`;
        }

        // Cập nhật số bình luận
        function updateCommentCount(postId, increase = false) {
            const post = document.querySelector(`.comments-list[data-post-id="${postId}"]`).closest('.post');
            const countEl = post.querySelector('.comment-count');
            let count = parseInt(countEl.textContent);
            countEl.textContent = increase ? count + 1 : count - 1;
        }
    </script>
</body>
</html>