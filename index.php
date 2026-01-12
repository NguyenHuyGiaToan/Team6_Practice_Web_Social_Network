<?php
require_once __DIR__ . '../includes/config.php';
require_once __DIR__ . '../includes/database.php';
require_once __DIR__ . '../includes/functions.php';
require_once __DIR__ . '../includes/header.php'; 

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
        return BASE_URL . 'uploads/avatars/' . htmlspecialchars($_SESSION['user_avatar']);
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
        p.PostID, p.FK_UserID as UserID, p.Content, p.LikeCount, p.CommentCount, p.CreatedAt, p.Status,
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
    $post['time_ago'] = timeAgo($post['CreatedAt']); 

    // Xử lý avatar
    $post['avatar_url'] = !empty($post['Avatar'])
        ? BASE_URL . 'uploads/avatars/' . htmlspecialchars($post['Avatar'])
        : 'https://ui-avatars.com/api/?name=' . urlencode($post['FullName']) . '&background=8B1E29&color=fff&size=200';

    $posts[] = $post;
}

// 3. Lấy hình ảnh cho từng bài viết (nếu có)
foreach ($posts as &$post) {
    $img_stmt = mysqli_prepare($conn, "
        SELECT ImageUrl, ImageID
        FROM POST_IMAGES 
        WHERE FK_PostID = ? 
        ORDER BY ImageID 
        LIMIT 4
    ");
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

// === GỢI Ý KẾT BẠN (RIGHT SIDEBAR) ===
// Lấy 5 người dùng ngẫu nhiên mà current user CHƯA follow và họ cũng CHƯA follow mình
$friends_query = "
    SELECT 
        u.UserID, 
        u.FullName, 
        u.Avatar
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
    LIMIT 10
";

$friends_stmt = mysqli_prepare($conn, $friends_query);
mysqli_stmt_bind_param($friends_stmt, "iii", $user_id, $user_id, $user_id);
mysqli_stmt_execute($friends_stmt);
$friends_result = mysqli_stmt_get_result($friends_stmt);

$suggestions = []; 
while ($friend = mysqli_fetch_assoc($friends_result)) {
    // Xử lý đường dẫn avatar
    $friend['avatar_url'] = !empty($friend['Avatar'])
        ? BASE_URL . 'uploads/avatars/' . htmlspecialchars($friend['Avatar'])
        : 'https://ui-avatars.com/api/?name=' . urlencode($friend['FullName']) . '&background=8B1E29&color=fff&size=200';
    
    $suggestions[] = $friend; 
}



?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TSix - Bảng tin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/Style-css/index.css">
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
                    <div class="menu-item" onclick="window.location.href='saved_posts.php'">
                        <i class="fa-solid fa-bookmark"></i>
                        <span>Đã lưu</span>
                    </div>
                    <div class="menu-item" onclick="window.location.href='friends.php'">
                        <i class="fa-solid fa-user-group"></i>
                        <span>Bạn bè</span>
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
                    
                    <div class="post-content">
                        <?php echo nl2br(htmlspecialchars($post['Content'])); ?>
                    </div>

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
                                data-action="toggle-like" 
                                data-post-id="<?php echo $post['PostID']; ?>">
                            <i class="fa-regular fa-thumbs-up"></i> 
                            <span><?php echo $post['user_liked'] ? 'Đã thích' : 'Thích'; ?></span>
                        </button>

                        <button class="post-action-btn btn-show-comments" 
                                data-action="toggle-comments" 
                                data-post-id="<?php echo $post['PostID']; ?>">
                            <i class="fa-regular fa-comment"></i> Bình luận
                        </button>
                    </div>

                    <div class="comments-section" style="margin-top: 16px; display: none;">
                        <div class="comments-list" data-post-id="<?php echo $post['PostID']; ?>">
                            <div class="text-center" style="color:#65676b; padding:20px;">Đang tải bình luận...</div>
                        </div>

                        <div class="comment-form" style="display:flex; gap:8px; margin-top:12px;">
                            <img src="<?php echo getAvatarUrl(); ?>" style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
                            <input type="text" class="comment-input" placeholder="Viết bình luận..." style="flex:1; padding:10px; border-radius:20px; border:1px solid #ccd0d5; outline:none;">
                            
                            <button class="send-comment" 
                                    onclick="sendComment(this)" 
                                    style="background:none; border:none; color:#8B1E29; font-size:1.2rem; cursor:pointer;">
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
                                        
                                        <textarea name="content" class="edit-textarea" placeholder="Bạn đang nghĩ gì?"><?php echo htmlspecialchars($post['Content']); ?></textarea>

                                        <?php if (!empty($post['images'])): ?>
                                            <div class="edit-current-images" style="margin-top: 15px;">
                                                <p style="font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">Ảnh hiện tại (Chọn để xóa):</p>
                                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                    <?php foreach ($post['images'] as $index => $img_url): 
                                                        // Giả sử mảng $post['image_ids'] chứa ID tương ứng của từng ảnh từ DB
                                                        $img_id = $post['image_ids'][$index] ?? 0; 
                                                    ?>
                                                        <div class="img-thumb-container" style="position: relative; width: 80px; height: 80px;">
                                                            <img src="<?php echo $img_url; ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">
                                                            <label style="position: absolute; top: -5px; right: -5px; background: rgba(255,255,255,0.9); border-radius: 50%; padding: 2px; cursor: pointer; border: 1px solid #ddd;">
                                                                <input type="checkbox" name="delete_images[]" value="<?php echo $img_id; ?>" title="Xóa ảnh này">
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <div style="margin-top: 20px; padding: 10px; border: 1px dashed #ccd0d5; border-radius: 8px;">
                                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #65676b;">
                                                <i class="fa-solid fa-images" style="color: #45bd62; font-size: 1.2rem;"></i>
                                                <span style="font-weight: 600;">Thêm ảnh mới</span>
                                                <input type="file" name="new_images[]" multiple accept="image/*" style="display: none;" onchange="previewImages(this, 'preview-<?php echo $post['PostID']; ?>')">
                                            </label>
                                            <div id="preview-<?php echo $post['PostID']; ?>" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px;"></div>
                                        </div>

                                        <button type="submit" class="btn-submit-modal">Lưu thay đổi</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div id="report-modal-<?php echo $post['PostID']; ?>" class="modal-overlay">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3>Báo cáo bài viết</h3>
                                <button class="close-modal" onclick="closeModal('report-modal-<?php echo $post['PostID']; ?>')">&times;</button>
                            </div>
                            <div class="modal-body">
                                <p>Hãy chọn lý do báo cáo:</p>
                                <form action="report_post_action.php" method="POST">
                                    <input type="hidden" name="post_id" value="<?php echo $post['PostID']; ?>">
                                    <ul class="report-list">
                                        <li class="report-item"><input type="radio" name="reason" value="spam" id="r1-<?php echo $post['PostID']; ?>"><label for="r1-<?php echo $post['PostID']; ?>">Spam</label></li>
                                        <li class="report-item"><input type="radio" name="reason" value="violence" id="r2-<?php echo $post['PostID']; ?>"><label for="r2-<?php echo $post['PostID']; ?>">Bạo lực</label></li>
                                        <li class="report-item"><input type="radio" name="reason" value="harassment" id="r3-<?php echo $post['PostID']; ?>"><label for="r3-<?php echo $post['PostID']; ?>">Quấy rối</label></li>
                                        <li class="report-item"><input type="radio" name="reason" value="fake_news" id="r4-<?php echo $post['PostID']; ?>"><label for="r4-<?php echo $post['PostID']; ?>">Tin giả</label></li>
                                    </ul>
                                    <button type="submit" class="btn-submit-modal" style="background:#e4e6eb; color:black;">Gửi báo cáo</button>
                                </form>
                            </div>
                        </div>
                    </div>

                </div> <?php endforeach; ?>

<?php if (empty($posts)): ?>
<div class="post" style="text-align:center; color:#65676b; padding:40px;">
    Chưa có bài viết nào từ bạn bè. Hãy theo dõi thêm người dùng!
</div>
<?php endif; ?>
            </div>

            <div class="right-sidebar">
                <div class="section">
                    <div class="section-title">Gợi ý kết bạn</div>
                    
                    <?php if (empty($suggestions)): ?>
                        <p style="color:#65676b; font-size:0.9rem;">Không có gợi ý mới.</p>
                    <?php else: ?>
                        <?php foreach ($suggestions as $sug): ?>
                            <div class="friend" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?php echo $sug['avatar_url']; ?>" 
                                        onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($sug['FullName']); ?>&background=8B1E29&color=fff'"
                                        class="friend-avatar" alt="Avatar" 
                                        style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    
                                    <div class="friend-info">
                                        <?php echo htmlspecialchars($sug['FullName']); ?>
                                    </div>
                                </div>
                                
                                <button id="follow-btn-sidebar-<?php echo $sug['UserID']; ?>" 
                                        class="btn-follow" 
                                        onclick="handleFollowSidebar(<?php echo $sug['UserID']; ?>)">
                                    + Theo dõi
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div style="text-align:right; margin-top:10px; border-top: 1px solid #e4e6eb; padding-top: 10px;">
                        <a href="friends.php" style="color:#8B1E29; font-size:0.85rem; font-weight:600; text-decoration: none;">Xem tất cả →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
// Đóng menu & modal khi click ra ngoài
window.onclick = function(event) {
    if (!event.target.closest('.post-menu-btn') && !event.target.closest('.post-options-menu')) {
        document.querySelectorAll('.post-options-menu').forEach(menu => menu.classList.remove('show'));
    }
    if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
    }
};

// Xử lý tất cả click bằng event delegation (chỉ 1 listener)
document.addEventListener('click', function(e) {
    const btn = e.target.closest('button[data-action]');

    if (!btn) return;

    const action = btn.dataset.action;
    const postId = btn.dataset.postId;

    if (action === 'toggle-like') {
        if (btn.disabled) return;
        btn.disabled = true;

        fetch('interaction_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=toggle_like&post_id=${postId}`
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                btn.classList.toggle('liked', data.liked);
                btn.querySelector('span').textContent = data.liked ? 'Đã thích' : 'Thích';
                
                const countEl = btn.closest('.post').querySelector('.like-count');
                if (countEl) countEl.textContent = data.like_count;
            } else {
                alert(data.message || 'Có lỗi xảy ra');
            }
        })
        .catch(err => {
            console.error('Lỗi toggle like:', err);
            btn.disabled = false;
        });
    }

    // Xử lý toggle comment section (nếu nút comment cũng dùng data-action)
    if (action === 'toggle-comments') {
        const post = btn.closest('.post');
        const section = post.querySelector('.comments-section');
        const list = section.querySelector('.comments-list');
        const postId = list.dataset.postId;

        if (section.style.display === 'block') {
            section.style.display = 'none';
        } else {
            section.style.display = 'block';
            if (list.innerHTML.includes('Đang tải') || list.innerHTML.trim() === '') {
                loadComments(postId, list);
            }
        }
    }
});

// 3. Gửi bình luận
function sendComment(btn) {
    const form = btn.closest('.comment-form');
    const input = form.querySelector('.comment-input');
    const content = input.value.trim();
    
    if (!content || btn.disabled) return;

    btn.disabled = true;
    const list = btn.closest('.post').querySelector('.comments-list');
    const postId = list.dataset.postId;

    fetch('interaction_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_comment&post_id=${postId}&content=${encodeURIComponent(content)}`
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        if (data.success) {
            input.value = '';
            if (list.querySelector('.text-center')) list.innerHTML = '';
            list.insertAdjacentHTML('afterbegin', renderComment(data.comment));

            const countEl = btn.closest('.post').querySelector('.comment-count');
            if (countEl) {
                let cnt = parseInt(countEl.textContent) || 0;
                countEl.textContent = cnt + 1;
            }
        } else {
            alert(data.message || 'Không thể gửi bình luận');
        }
    })
    .catch(err => {
        console.error('Lỗi gửi bình luận:', err);
        btn.disabled = false;
    });
}

// 4. Tải danh sách bình luận
function loadComments(postId, container) {
    fetch(`interaction_handler.php?action=get_comments&post_id=${postId}`)
        .then(res => res.json())
        .then(data => {
            container.innerHTML = '';
            if (data.comments?.length > 0) {
                data.comments.forEach(c => container.insertAdjacentHTML('beforeend', renderComment(c)));
            } else {
                container.innerHTML = '<div class="text-center" style="color:#65676b; padding:10px;">Chưa có bình luận nào.</div>';
            }
        })
        .catch(err => console.error('Lỗi load comments:', err));
}

// 5. Render HTML bình luận
function renderComment(c) {
    return `
    <div class="comment-item" data-comment-id="${c.CommentID}">
        <img src="${c.avatar_url}" class="comment-avatar">
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
    </div>`;
}

function handleFollowSidebar(userId) {
    const btn = document.getElementById('follow-btn-sidebar-' + userId);
    if (!btn || btn.disabled) return;

    // 1. Khóa nút ngay lập tức để chống click đúp
    btn.disabled = true;
    const originalText = btn.innerText;
    btn.innerText = '...';

    // 2. Gửi yêu cầu đến friends_action.php
    fetch('friends_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `target_id=${userId}&action=send_request`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Thay đổi giao diện nút thành công
            btn.innerText = 'Đã gửi yêu cầu';
            btn.style.background = '#e4e6eb';
            btn.style.color = '#65676b';
            btn.onclick = null; // Chặn bấm lại hoàn toàn
        } else {
            alert(data.message);
            btn.disabled = false;
            btn.innerText = originalText;
        }
    })
    .catch(err => {
        console.error('Lỗi:', err);
        btn.disabled = false;
        btn.innerText = originalText;
    });
}

// Các hàm còn lại (nếu dùng)
function openModal(id) {
    document.getElementById(id).style.display = 'flex';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function togglePostMenu(menuId) {
    document.querySelectorAll('.post-options-menu.show').forEach(m => m.classList.remove('show'));
    const menu = document.getElementById(menuId);
    if (menu) menu.classList.toggle('show');
    event?.stopPropagation();
}

function previewImages(input, containerId) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    [...input.files].forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:60px;height:60px;object-fit:cover;border-radius:4px;border:1px solid #ddd;';
            container.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}
</script>
</body>
</html>