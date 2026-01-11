<?php
// friends.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/header.php'; 


if (!isset($_SESSION['user_id'])) exit();
$user_id = $_SESSION['user_id'];

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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bạn bè - TSix</title>
    <style>
        /* Layout riêng cho trang Friends */
        .friends-container {
            display: flex;
            height: calc(100vh - 60px); /* Trừ chiều cao navbar */
            background: #f0f2f5;
            overflow: hidden;
        }

        /* Sidebar bên trái */
        .friends-sidebar {
            width: 360px;
            background: #fff;
            padding: 10px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto;
        }
        .friends-sidebar h2 { font-size: 1.5rem; margin: 10px 10px 20px; color: #8B1E29 }
        .friends-sidebar a {
            text-decoration: none;
            color: inherit;
        }
        .sidebar-item {
            display: flex; align-items: center; gap: 12px;
            padding: 10px; border-radius: 8px; cursor: pointer;
            font-weight: 600; font-size: 1.05rem; color: #050505;
            transition: background 0.2s ease;
        }
        .sidebar-item span {
            text-decoration: none !important; /* Đảm bảo không có gạch chân */
            font-weight: 500;
        }
        .sidebar-item:hover { background: #8B1E29; }
        .sidebar-item.active { background: #e7f3ff; color: #8B1E29; }
        .sidebar-icon-circle {
            width: 36px; height: 36px; background: #e4e6eb; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }
        .active .sidebar-icon-circle { background: #8B1E29; color: white; }

        /* Khu vực chính bên phải */
        .friends-main {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Section Header */
        .section-header { margin-bottom: 20px; }
        .section-header h3 { font-size: 1.25rem; font-weight: 700; color: #8B1E29 }

        /* Grid Layout (Giống ảnh mẫu) */
        .friends-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        /* Card User */
        .friend-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); /* Chuyển động mượt mà */
            border: 1px solid #e4e6eb;
        }

        .friend-card:hover {
            transform: translateY(-5px); /* Bay nhẹ lên trên */
            box-shadow: 0 10px 20px rgba(0,0,0,0.15); /* Đổ bóng đậm hơn khi lướt qua */
            border-color: #d8dadf;
        }

        .card-img {
            width: 100%;
            aspect-ratio: 1/1; /* Ảnh vuông */
            object-fit: cover;
            cursor: pointer;
        }

        .card-body {
            padding: 12px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .card-name {
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #050505;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }

        .card-info {
            font-size: 0.85rem;
            color: #65676b;
            margin-bottom: 12px;
            flex: 1; /* Đẩy button xuống đáy */
        }

        /* Nút bấm */
        .btn-stack { display: flex; flex-direction: column; gap: 8px; }
        .btn {
            width: 100%;
            padding: 8px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            transition: 0.2s;
        }
        .btn-primary { background: #e7f3ff; color: Gray; }
        .btn-primary:hover { background: #8B1E29; }
        
        /* Nút Confirm đậm hơn */
        .btn-confirm { background: #8B1E29; color: white; }
        .btn-confirm:hover { background: #8B1E29; }

        .btn-secondary { background: #e4e6eb; color: #050505; }
        .btn-secondary:hover { background: #8B1E29; }
        .btn-disabled { background: #f0f2f5; color: #bcc0c4; cursor: not-allowed; }

        /* Responsive */
        @media (max-width: 900px) {
            .friends-sidebar { display: none; } /* Ẩn sidebar trên mobile */
            .friends-main { padding: 15px; }
            .friends-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
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
        
        <?php if (!empty($requests)): ?>
        <div class="section-header">
            <h3>Lời mời theo dõi <span style="color:#1877f2; font-size:1rem;"><?php echo count($requests); ?></span></h3>
        </div>
        <div class="friends-grid" style="margin-bottom: 30px;">
            <?php foreach ($requests as $req): ?>
            <div class="friend-card" id="card-req-<?php echo $req['UserID']; ?>">
                <img src="<?php echo BASE_URL; ?>uploads/avatars/<?php echo $req['Avatar'] ?: 'default_avatar.png'; ?>" class="card-img" onclick="location.href='profile.php?id=<?php echo $req['UserID']; ?>'">
                <div class="card-body">
                    <div class="card-name"><?php echo htmlspecialchars($req['FullName']); ?></div>
                    <div class="btn-stack">
                        <button class="btn btn-confirm" onclick="handleFriend(<?php echo $req['UserID']; ?>, 'accept_request')">
                            Xác nhận
                        </button>
                        <button class="btn btn-secondary" onclick="handleFriend(<?php echo $req['UserID']; ?>, 'decline_request')">
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="border-bottom: 1px solid #ccc; margin-bottom: 20px;"></div>
        <?php endif; ?>

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
                        <button id="follow-btn-<?php echo $sug['UserID']; ?>" 
                                class="btn btn-primary" 
                                onclick="handleFriend(<?php echo $sug['UserID']; ?>, 'send_request')">
                            Theo dõi
                        </button>
                        <button class="btn btn-secondary" onclick="this.closest('.friend-card').remove()">
                            Xóa
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<script>
function handleFriend(userId, action) {
    const btn = document.getElementById('follow-btn-' + userId);
    if(!btn) return;
    
    // Hiệu ứng loading nhẹ
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
            if (action === 'send_request') {
                btn.innerText = 'Đã gửi yêu cầu'; // Đổi tên nút
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-disabled');
                btn.onclick = null; // Chặn bấm lại
            } else if (action === 'accept_request') {
                btn.innerText = 'Đã chấp nhận';
                btn.classList.add('btn-disabled');
                // Ẩn nút xóa đi
                btn.nextElementSibling.style.display = 'none';
            } else if (action === 'decline_request') {
                // Xóa cả card
                document.getElementById('card-req-' + userId).remove();
            }
        } else {
            alert(data.message);
            btn.innerText = originalText;
            btn.disabled = false;
        }
    })
    .catch(err => {
        console.error(err);
        btn.innerText = originalText;
        btn.disabled = false;
    });
}
</script>

</body>
</html>