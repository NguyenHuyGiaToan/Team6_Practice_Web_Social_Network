<?php
// includes/header.php
// Đảm bảo session đã khởi động và user đã login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Lấy thông tin user (Avatar, FullName) để hiển thị
require_once 'database.php'; // hoặc db.php tùy tên file của nhóm
$sql = "SELECT FullName, Avatar FROM Users WHERE UserID = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current_user = mysqli_fetch_assoc($result);

// Xử lý avatar
$avatar_url = !empty($current_user['Avatar'])
    ? 'uploads/avatars/' . htmlspecialchars($current_user['Avatar'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($current_user['FullName']) . '&background=8B1E29&color=fff&size=120';

// Đếm số thông báo chưa đọc
$sql_noti = "SELECT COUNT(*) AS unread FROM NOTIFICATIONS WHERE FK_UserID = ? AND IsRead = 0";
$stmt_noti = mysqli_prepare($conn, $sql_noti);
mysqli_stmt_bind_param($stmt_noti, "i", $current_user_id);
mysqli_stmt_execute($stmt_noti);
$noti_result = mysqli_stmt_get_result($stmt_noti);
$unread_count = mysqli_fetch_assoc($noti_result)['unread'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Navbar hiện đại, lấy cảm hứng Facebook 2025 */
        .navbar {
            background: #ffffff;
            height: 60px;
            padding: 0 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            max-width: 400px;
        }

        .logo {
            color: #8B1E29;
            font-weight: 900;
            font-size: 2.2rem;
            letter-spacing: -1px;
        }

        /* Ô tìm kiếm */
        .search-form {
            flex: 1;
        }
        .search-box {
            background: #f0f2f5;
            border-radius: 30px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            width: 100%;
            transition: background 0.2s;
        }
        .search-box:hover {
            background: #e4e6eb;
        }
        .search-box i {
            color: #65676b;
            font-size: 1.1rem;
        }
        .search-input {
            border: none;
            background: transparent;
            outline: none;
            margin-left: 10px;
            font-size: 1rem;
            width: 100%;
            color: #050505;
        }

        /* Nav center - chỉ giữ Home và Friends */
        .nav-center {
            display: flex;
            gap: 8px;
        }
        .nav-item {
            width: 110px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #65676b;
            font-size: 1.5rem;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .nav-item:hover {
            background: #f0f2f5;
        }
        .nav-item.active {
            color: #8B1E29;
            position: relative;
        }
        .nav-item.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 4px;
            background: #8B1E29;
            border-radius: 4px 4px 0 0;
        }

        /* Nav right */
        .nav-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-icon-circle {
            width: 40px;
            height: 40px;
            background: #e4e6eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            color: #050505;
            position: relative;
            transition: background 0.2s;
        }
        .nav-icon-circle:hover {
            background: #d8dadf;
        }

        /* Badge thông báo */
        .badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #e41e3f;
            color: white;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }

        .nav-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border 0.2s;
        }
        .nav-avatar:hover {
            border-color: #8B1E29;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .nav-left { max-width: 200px; }
            .search-box { padding: 8px 12px; }
            .search-input { font-size: 0.95rem; }
            .nav-center { gap: 4px; }
            .nav-item { width: 80px; }
        }
        @media (max-width: 600px) {
            .search-input::placeholder { font-size: 0.9rem; }
            .nav-item span { display: none; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <!-- Left: Logo + Search -->
        <div class="nav-left">
            <a href="index.php" class="logo">TSix</a>
            <form action="search.php" method="GET" class="search-form">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="q" class="search-input" placeholder="Tìm kiếm trên TSix" autocomplete="off">
                </div>
            </form>
        </div>

        <!-- Center: Home & Friends -->
        <div class="nav-center">
            <a href="index.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i>
            </a>
            <a href="friends.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'friends.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-group"></i>
            </a>
        </div>

        <!-- Right: Saved Posts, Notifications, Avatar -->
        <div class="nav-right">
            <!-- Bài viết đã lưu -->
            <a href="saved_posts.php" title="Bài viết đã lưu">
                <div class="nav-icon-circle">
                    <i class="fa-solid fa-bookmark"></i>
                </div>
            </a>

            <!-- Thông báo -->
            <a href="notifications.php" title="Thông báo">
                <div class="nav-icon-circle">
                    <i class="fa-solid fa-bell"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="badge"><?php echo $unread_count > 99 ? '99+' : $unread_count; ?></span>
                    <?php endif; ?>
                </div>
            </a>

            <!-- Avatar → Trang cá nhân -->
            <a href="logout.php">
                <img src="<?php echo $avatar_url; ?>" class="nav-avatar" alt="Avatar">
            </a>
        </div>
    </nav>