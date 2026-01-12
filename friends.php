<?php
// friends.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

// KIỂM TRA: Nếu được gọi qua AJAX từ Profile thì không load header
$is_tab = isset($_GET['view_as_tab']);
if (!$is_tab) {
    require_once __DIR__ . '/includes/header.php'; 
}

if (!isset($_SESSION['user_id'])) exit();

// LOGIC QUAN TRỌNG: 
// Nếu xem qua Tab, lấy ID từ GET. Nếu không, lấy ID của mình.
$user_id = (isset($_GET['user_id'])) ? intval($_GET['user_id']) : $_SESSION['user_id'];
$me_id = $_SESSION['user_id']; // ID của người đang xem để check nút follow

// 1. Lấy danh sách LỜI MỜI (Người khác follow mình nhưng status = pending)
$sql_requests = "
    SELECT u.UserID, u.FullName, u.Avatar
    FROM FOLLOWS f
    JOIN Users u ON f.FK_FollowerID = u.UserID
    WHERE f.FK_FollowingID = ? AND f.Status = 'pending'
    ORDER BY f.FollowedAt DESC
";
$stmt = mysqli_prepare($conn, $sql_requests);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$requests = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

// 2. Lấy danh sách GỢI Ý (Người mình chưa follow)
$sql_suggestions = "
    SELECT u.UserID, u.FullName, u.Avatar
    FROM Users u
    WHERE u.UserID != ? 
    AND u.UserID NOT IN (SELECT FK_FollowingID FROM FOLLOWS WHERE FK_FollowerID = ?)
    AND u.Status = 'active'
    ORDER BY RAND() LIMIT 20
";
$stmt2 = mysqli_prepare($conn, $sql_suggestions);
mysqli_stmt_bind_param($stmt2, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt2);
$suggestions = mysqli_fetch_all(mysqli_stmt_get_result($stmt2), MYSQLI_ASSOC);

// 3. Lấy danh sách "Tất cả bạn bè"
$sql_all_friends = "
    SELECT u.UserID, u.FullName, u.Avatar
    FROM FOLLOWS f
    JOIN Users u ON f.FK_FollowingID = u.UserID
    WHERE f.FK_FollowerID = ? AND f.Status = 'accepted'
    ORDER BY u.FullName ASC
";
$stmt_all = mysqli_prepare($conn, $sql_all_friends);
mysqli_stmt_bind_param($stmt_all, "i", $user_id);
mysqli_stmt_execute($stmt_all);
$all_friends = mysqli_fetch_all(mysqli_stmt_get_result($stmt_all), MYSQLI_ASSOC);

// Nếu là mode TAB, chúng ta chỉ trả về phần danh sách, không trả về Sidebar
if ($is_tab): ?>
    <div class="section-header" style="margin-bottom: 20px;">
        <h3 style="font-size: 1.25rem; font-weight: 700;">Bạn bè (<?php echo count($all_friends); ?>)</h3>
    </div>
    <div class="friends-grid">
        <?php foreach ($all_friends as $friend): ?>
            <div class="friend-card-mini">
                <img src="uploads/avatars/<?php echo $friend['Avatar'] ?: 'default_avatar.png'; ?>" 
                     onclick="location.href='profile.php?id=<?php echo $friend['UserID']; ?>'">
                <div class="friend-info-box">
                    <span class="name"><?php echo htmlspecialchars($friend['FullName']); ?></span>
                    <span class="meta">Đã theo dõi</span>
                    <button class="btn-friend-action btn-view-profile" 
                            onclick="location.href='profile.php?id=<?php echo $friend['UserID']; ?>'">
                        Xem trang cá nhân
                    </button>
                    <button class="btn-friend-action btn-unfollow" 
                            onclick="handleFriend(<?php echo $friend['UserID']; ?>, 'unfollow')">
                        Hủy theo dõi
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php exit(); endif; ?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bạn bè - TSix</title>
    <link rel="stylesheet" href="assets/Style-css/friends.css">
</head>
<body>

<div class="friends-container">
    <div class="friends-sidebar">
        <h2>Bạn bè</h2>
        <a href="friends.php" class="sidebar-item <?php echo !isset($_GET['view']) ? 'active' : ''; ?>">
            <div class="sidebar-icon-circle"><i class="fas fa-user-friends"></i></div>
            <span>Gợi ý theo dõi</span>
        </a>
        <a href="friends.php?view=requests" class="sidebar-item <?php echo ($_GET['view'] ?? '') == 'requests' ? 'active' : ''; ?>">
            <div class="sidebar-icon-circle"><i class="fas fa-user-plus"></i></div>
            <span>Yêu cầu theo dõi</span>
        </a>
        <a href="friends.php?view=all" class="sidebar-item <?php echo ($_GET['view'] ?? '') == 'all' ? 'active' : ''; ?>">
            <div class="sidebar-icon-circle"><i class="fas fa-list"></i></div>
            <span>Tất cả bạn bè</span>
        </a>
    </div>

    <div class="friends-main">
        <?php 
        $view = $_GET['view'] ?? 'default';

        // --- TRƯỜNG HỢP 1: TẤT CẢ BẠN BÈ ---
        if ($view == 'all'): ?>
            <div class="section-header">
                <h3>Tất cả bạn bè (<?php echo count($all_friends); ?>)</h3>
            </div>
            <?php if (empty($all_friends)): ?>
                <p style="text-align: center; color: #65676b; margin-top: 50px;">Bạn chưa theo dõi ai.</p>
            <?php else: ?>
                <div class="friends-grid">
                    <?php foreach ($all_friends as $friend): ?>
                        <div class="friend-card" id="card-all-<?php echo $friend['UserID']; ?>">
                            <img src="uploads/avatars/<?php echo $friend['Avatar'] ?: 'default_avatar.png'; ?>" 
                                 class="card-img" 
                                 onclick="location.href='profile.php?id=<?php echo $friend['UserID']; ?>'">
                            <div class="card-body">
                                <div class="card-name"><?php echo htmlspecialchars($friend['FullName']); ?></div>
                                <div class="card-info">Đã theo dõi</div>
                                <div class="btn-stack">
                                    <button class="btn btn-primary" onclick="location.href='profile.php?id=<?php echo $friend['UserID']; ?>'">
                                        Xem trang cá nhân
                                    </button>
                                    <button class="btn btn-secondary" onclick="handleFriend(<?php echo $friend['UserID']; ?>, 'unfollow')">
                                        Hủy theo dõi
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif;

        // --- TRƯỜNG HỢP 2: YÊU CẦU THEO DÕI ---
        elseif ($view == 'requests'): ?>
            <div class="section-header">
                <h3>Yêu cầu theo dõi (<?php echo count($requests); ?>)</h3>
            </div>
            <?php if (empty($requests)): ?>
                <p style="text-align: center; color: #65676b; margin-top: 50px;">Không có yêu cầu nào mới.</p>
            <?php else: ?>
                <div class="friends-grid">
                    <?php foreach ($requests as $req): ?>
                        <div class="friend-card" id="card-req-<?php echo $req['UserID']; ?>">
                            <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo $req['Avatar'] ?: 'default_avatar.png'; ?>" class="card-img" onclick="location.href='profile.php?id=<?php echo $req['UserID']; ?>'">
                            <div class="card-body">
                                <div class="card-name"><?php echo htmlspecialchars($req['FullName']); ?></div>
                                <div class="btn-stack">
                                    <button class="btn btn-confirm" onclick="handleFriend(<?php echo $req['UserID']; ?>, 'accept_request')">Xác nhận</button>
                                    <button class="btn btn-secondary" onclick="handleFriend(<?php echo $req['UserID']; ?>, 'decline_request')">Xóa</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif;

        // --- TRƯỜNG HỢP 3: GỢI Ý (MẶC ĐỊNH) ---
        else: ?>
            <div class="section-header">
                <h3>Những người bạn có thể biết</h3>
            </div>
            <div class="friends-grid">
                <?php foreach ($suggestions as $sug): ?>
                <div class="friend-card">
                    <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo $sug['Avatar'] ?: 'default_avatar.png'; ?>" class="card-img" onclick="location.href='profile.php?id=<?php echo $sug['UserID']; ?>'">
                    <div class="card-body">
                        <div class="card-name"><?php echo htmlspecialchars($sug['FullName']); ?></div>
                        <div class="card-info">Gợi ý cho bạn</div>
                        <div class="btn-stack">
                            <button id="follow-btn-<?php echo $sug['UserID']; ?>" class="btn btn-primary" onclick="handleFriend(<?php echo $sug['UserID']; ?>, 'send_request')">Theo dõi</button>
                            <button class="btn btn-secondary" onclick="this.closest('.friend-card').remove()">Xóa</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function handleFriend(userId, action) {
    // 1. Xác định nút bấm dựa trên hành động
    let btn;
    if (action === 'unfollow') {
        // Tìm nút Hủy theo dõi trong card-all
        const card = document.getElementById('card-all-' + userId);
        btn = card ? card.querySelector('.btn-secondary') : null;
    } else if (action === 'accept_request' || action === 'decline_request') {
        const card = document.getElementById('card-req-' + userId);
        btn = card ? card.querySelector('.btn-confirm, .btn-secondary') : null;
    } else {
        btn = document.getElementById('follow-btn-' + userId);
    }

    if (!btn) return;
    
    // Hiệu ứng loading
    const originalText = btn.innerText;
    btn.innerText = '...';
    btn.disabled = true;

    fetch('friends_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `target_id=${userId}&action=${action}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (action === 'unfollow') {
                // Xóa thẻ của người đó khỏi danh sách "Tất cả bạn bè" với hiệu ứng mờ dần
                const card = document.getElementById('card-all-' + userId);
                if (card) {
                    card.style.opacity = '0.5';
                    setTimeout(() => card.remove(), 300);
                }
            } else if (action === 'send_request') {
                btn.innerText = 'Đã gửi yêu cầu';
                btn.classList.replace('btn-primary', 'btn-disabled');
                btn.onclick = null;
            } else if (action === 'accept_request') {
                btn.innerText = 'Đã đồng ý'; // Cập nhật theo yêu cầu trước đó của bạn
                btn.classList.replace('btn-confirm', 'btn-disabled');
                if (btn.nextElementSibling) btn.nextElementSibling.style.display = 'none';
            } else if (action === 'decline_request') {
                const card = document.getElementById('card-req-' + userId);
                if (card) card.remove();
            }
        } else {
            alert(data.message);
            btn.innerText = originalText;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error("Lỗi fetch:", err);
        btn.innerText = originalText;
        btn.disabled = false;
    });
}
</script>
</body>
</html>