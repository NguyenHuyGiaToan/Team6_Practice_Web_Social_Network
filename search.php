<?php
// search.php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- NHẬN THAM SỐ FILTER & PAGINATION ---
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$tab     = isset($_GET['tab']) ? $_GET['tab'] : 'all'; 
$sort    = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$filter  = isset($_GET['filter']) ? $_GET['filter'] : 'all'; 
$time    = isset($_GET['time']) ? $_GET['time'] : 'any'; // [MỚI] Filter thời gian
$page    = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // [MỚI] Trang hiện tại
$limit   = 10; // Số lượng kết quả mỗi trang
$offset  = ($page - 1) * $limit;

// Lấy thông tin user hiện tại
$stmt = mysqli_prepare($conn, "SELECT FullName, Avatar FROM Users WHERE UserID = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$current_user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$_SESSION['user_fullname'] = $current_user['FullName'];
$_SESSION['user_avatar']   = $current_user['Avatar'] ?? null;

// Hàm helper hiển thị avatar
function getAvatarUrl($avatarName = null, $fullName = '') {
    if ($avatarName === null && $fullName === '') {
        $avatarName = $_SESSION['user_avatar'];
        $fullName = $_SESSION['user_fullname'];
    }
    if (!empty($avatarName)) {
        return BASE_URL . 'uploads/avatars/' . htmlspecialchars($avatarName);
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=8B1E29&color=fff&size=200';
}

// [MỚI] Hàm Highlight từ khóa
function highlightKeyword($text, $keyword) {
    if (empty($keyword)) return htmlspecialchars($text);
    // Dùng preg_replace để thay thế từ khóa bằng thẻ <mark> (không phân biệt hoa thường)
    return preg_replace('/(' . preg_quote($keyword, '/') . ')/iu', '<mark>$1</mark>', htmlspecialchars($text));
}

$user_results = [];
$post_results = [];
$total_posts = 0; // Đếm tổng bài viết để làm phân trang

if (!empty($keyword)) {
    $search_term = "%" . $keyword . "%";

    // --- 1. TÌM KIẾM NGƯỜI DÙNG ---
   if ($tab == 'all' || $tab == 'people') {
    $sql = "SELECT u.*, 
            (SELECT Status FROM FOLLOWS WHERE FK_FollowerID = ? AND FK_FollowingID = u.UserID) as follow_status
            FROM Users u
            WHERE (u.FullName LIKE ? OR u.Email LIKE ?)
            AND u.UserID != ? 
            AND u.Role = 'user'
            AND u.Status = 'active'
            LIMIT 5"; 
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "issi", $user_id, $search_term, $search_term, $user_id);
    mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $row['avatar_url'] = getAvatarUrl($row['Avatar'], $row['FullName']);
            $user_results[] = $row;
        }
    }

    // --- 2. TÌM KIẾM BÀI VIẾT (ADVANCED FILTER) ---
    if ($tab == 'all' || $tab == 'posts') {
        // Query cơ bản
        $sql = "SELECT SQL_CALC_FOUND_ROWS p.*, u.FullName, u.Avatar,
                (SELECT ImageUrl FROM post_images WHERE FK_PostID = p.PostID LIMIT 1) as PostImage,
                (SELECT COUNT(*) FROM likes WHERE FK_PostID = p.PostID AND FK_UserID = ?) as is_liked,
                (SELECT COUNT(*) FROM likes WHERE FK_PostID = p.PostID) as total_likes,
                (SELECT COUNT(*) FROM comments WHERE FK_PostID = p.PostID) as total_comments
                FROM posts p
                JOIN users u ON p.FK_UserID = u.UserID
                WHERE p.Content LIKE ? AND p.Status = 'active'";
        
        $types = "is"; 
        $params = [$user_id, $search_term];

        // [FILTER] Lọc bài đã like
        if ($filter == 'liked') {
            $sql .= " AND EXISTS (SELECT 1 FROM likes WHERE FK_PostID = p.PostID AND FK_UserID = ?) ";
            $types .= "i";
            $params[] = $user_id;
        }

        // [FILTER] Lọc theo thời gian (EF-04)
        if ($time == 'day') {
            $sql .= " AND p.CreatedAt >= NOW() - INTERVAL 1 DAY ";
        } elseif ($time == 'week') {
            $sql .= " AND p.CreatedAt >= NOW() - INTERVAL 1 WEEK ";
        } elseif ($time == 'month') {
            $sql .= " AND p.CreatedAt >= NOW() - INTERVAL 1 MONTH ";
        }

        // [SORT] Sắp xếp
        if ($sort == 'popular') {
            $sql .= " ORDER BY p.LikeCount DESC, p.CreatedAt DESC";
        } else {
            $sql .= " ORDER BY p.CreatedAt DESC";
        }

        // [PAGINATION] Phân trang
        $sql .= " LIMIT ? OFFSET ?";
        $types .= "ii";
        $params[] = $limit;
        $params[] = $offset;
                
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        
        // Lấy tổng số dòng để tính số trang
        $total_res = mysqli_query($conn, "SELECT FOUND_ROWS() as total");
        $total_posts = mysqli_fetch_assoc($total_res)['total'];
        $total_pages = ceil($total_posts / $limit);

        while ($row = mysqli_fetch_assoc($res)) {
            $row['avatar_url'] = getAvatarUrl($row['Avatar'], $row['FullName']);
            $row['time_ago'] = date("d/m H:i", strtotime($row['CreatedAt']));
            $row['post_image_url'] = null;
            if ($row['PostImage']) {
                $row['post_image_url'] = 'uploads/posts/' . $row['PostImage'];
            }
            $post_results[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tìm kiếm: <?php echo htmlspecialchars($keyword); ?> - TSix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="assets/Style-css/search.css">
</head>
<body>

    <nav class="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo"><img src="assets/images/avt.png" alt="Logo"></a>
        </div>
        <div class="nav-center">
            <form action="search.php" method="GET" class="search-box">
                <i class="fa-solid fa-magnifying-glass" style="color: #65676b"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Tìm kiếm trên TSix">
                <?php if($tab != 'all'): ?><input type="hidden" name="tab" value="<?php echo $tab; ?>"><?php endif; ?>
            </form>
        </div>
        <div class="nav-right">
            <i class="fa-solid fa-bell" style="font-size:1.2rem; cursor:pointer;"></i>
            <a href="profile.php"><img src="<?php echo getAvatarUrl($_SESSION['user_avatar']); ?>" class="nav-avatar"></a>
        </div>
    </nav>

    <div class="container">
        <div class="main-layout">
            <div class="left-sidebar">
                <div class="user-card">
                    <img src="<?php echo getAvatarUrl($_SESSION['user_avatar']); ?>" class="nav-avatar" style="width:50px; height:50px;">
                    <div><h3 style="font-size:1rem;"><?php echo htmlspecialchars($_SESSION['user_fullname']); ?></h3></div>
                </div>
                <div class="menu">
                    <a href="index.php" class="menu-item"><i class="fa-solid fa-house"></i> Bảng tin</a>
                    <a href="profile.php" class="menu-item"><i class="fa-solid fa-user"></i> Trang cá nhân</a>
                    <a href="saved_posts.php" class="menu-item"><i class="fa-solid fa-bookmark"></i> Đã lưu</a>
                    <div class="menu-item active"><i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm</div>
                    <a href="logout.php" class="menu-item"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
                </div>
            </div>

            <div class="search-content">
                
                <div class="search-tabs">
                    <a href="search.php?q=<?php echo urlencode($keyword); ?>&tab=all&sort=<?php echo $sort; ?>&filter=<?php echo $filter; ?>&time=<?php echo $time; ?>" class="tab-item <?php echo $tab == 'all' ? 'active' : ''; ?>">Tất cả</a>
                    <a href="search.php?q=<?php echo urlencode($keyword); ?>&tab=people&sort=<?php echo $sort; ?>&filter=<?php echo $filter; ?>&time=<?php echo $time; ?>" class="tab-item <?php echo $tab == 'people' ? 'active' : ''; ?>">Mọi người</a>
                    <a href="search.php?q=<?php echo urlencode($keyword); ?>&tab=posts&sort=<?php echo $sort; ?>&filter=<?php echo $filter; ?>&time=<?php echo $time; ?>" class="tab-item <?php echo $tab == 'posts' ? 'active' : ''; ?>">Bài viết</a>
                </div>

                <?php if ($tab != 'people' && !empty($keyword)): ?>
                <div class="filter-bar">
                    <form action="search.php" method="GET" id="filterForm" style="display:flex; gap:15px; width:100%; flex-wrap:wrap;">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($keyword); ?>">
                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                        
                        <div class="filter-group">
                            <i class="fa-solid fa-arrow-down-short-wide"></i> Sắp xếp:
                            <select name="sort" class="filter-select" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                                <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Nhiều Like</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <i class="fa-solid fa-filter"></i> Lọc:
                            <select name="filter" class="filter-select" onchange="this.form.submit()">
                                <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>Tất cả</option>
                                <option value="liked" <?php echo $filter == 'liked' ? 'selected' : ''; ?>>Đã thích</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <i class="fa-regular fa-clock"></i> Thời gian:
                            <select name="time" class="filter-select" onchange="this.form.submit()">
                                <option value="any" <?php echo $time == 'any' ? 'selected' : ''; ?>>Mọi lúc</option>
                                <option value="day" <?php echo $time == 'day' ? 'selected' : ''; ?>>24 giờ qua</option>
                                <option value="week" <?php echo $time == 'week' ? 'selected' : ''; ?>>Tuần qua</option>
                                <option value="month" <?php echo $time == 'month' ? 'selected' : ''; ?>>Tháng qua</option>
                            </select>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($keyword): ?>
                    
                    <?php if (($tab == 'all' || $tab == 'people') && !empty($user_results)): ?>
                        <div class="result-title">Mọi người</div>
                        <?php foreach ($user_results as $u): ?>
                        <div class="user-result-card" id="user-card-<?php echo $u['UserID']; ?>">
                            <div class="ur-left">
                                <a href="profile.php?id=<?php echo $u['UserID']; ?>">
                                    <img src="<?php echo $u['avatar_url']; ?>" class="ur-avatar">
                                </a>
                                <div>
                                    <a href="profile.php?id=<?php echo $u['UserID']; ?>" class="ur-name">
                                        <?php echo highlightKeyword($u['FullName'], $keyword); ?>
                                    </a>
                                    <div class="ur-bio"><?php echo !empty($u['Bio']) ? htmlspecialchars($u['Bio']) : 'Nothing'; ?></div>
                                </div>
                            </div>

                            <div class="action-box">
                                <?php if ($u['follow_status'] === 'accepted'): ?>
                                    <button class="btn-connect following" 
                                            onclick="handleFollowAction(this, <?php echo $u['UserID']; ?>, 'unfollow')">
                                        <i class="fa-solid fa-user-minus"></i> Hủy theo dõi
                                    </button>
                                <?php elseif ($u['follow_status'] === 'pending'): ?>
                                    <button class="btn-connect" style="background: #f0f2f5; color: #65676b; cursor: default;" disabled>
                                        <i class="fa-solid fa-clock"></i> Đã gửi yêu cầu
                                    </button>
                                <?php else: ?>
                                    <button class="btn-connect" 
                                            onclick="handleFollowAction(this, <?php echo $u['UserID']; ?>, 'send_request')">
                                        <i class="fa-solid fa-plus"></i> Theo dõi
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (($tab == 'all' || $tab == 'posts') && !empty($post_results)): ?>
                        <div class="result-title" style="margin-top: 20px;">Bài viết</div>
                        <?php foreach ($post_results as $p): ?>
                        <div class="post">
                            <div class="p-header">
                                <img src="<?php echo $p['avatar_url']; ?>" class="p-avatar">
                                <div class="p-info">
                                    <div style="font-weight:600; font-size:0.95rem;"><?php echo htmlspecialchars($p['FullName']); ?></div>
                                    <div class="p-time"><?php echo $p['time_ago']; ?> <i class="fa-solid fa-earth-americas"></i></div>
                                </div>
                            </div>
                            <div class="p-content">
                                <?php echo nl2br(highlightKeyword($p['Content'], $keyword)); ?>
                            </div>
                            <?php if (!empty($p['post_image_url'])): ?>
                                <img src="<?php echo $p['post_image_url']; ?>" class="p-image">
                            <?php endif; ?>
                            <div class="p-actions">
                                <div class="act-btn <?php echo $p['is_liked'] ? 'liked' : ''; ?>"><i class="fa-regular fa-thumbs-up"></i> Thích (<?php echo $p['total_likes']; ?>)</div>
                                <div class="act-btn"><i class="fa-regular fa-comment"></i> Bình luận</div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php 
                                $base_link = "search.php?q=".urlencode($keyword)."&tab=$tab&sort=$sort&filter=$filter&time=$time";
                                if ($page > 1): 
                            ?>
                                <a href="<?php echo $base_link . '&page=' . ($page - 1); ?>" class="page-btn">← Trước</a>
                            <?php endif; ?>
                            
                            <span style="padding:8px;">Trang <?php echo $page; ?> / <?php echo $total_pages; ?></span>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo $base_link . '&page=' . ($page + 1); ?>" class="page-btn">Sau →</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    <?php endif; ?>

                    <?php if (empty($user_results) && empty($post_results)): ?>
                        <div style="text-align:center; padding:40px; color:#65676b; background:#fff; border-radius:8px;">
                            <i class="fa-solid fa-magnifying-glass" style="font-size:2rem; margin-bottom:10px;"></i><br>
                            Không tìm thấy kết quả nào cho "<?php echo htmlspecialchars($keyword); ?>".
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div style="text-align:center; padding:40px; color:#65676b; background:#fff; border-radius:8px;">
                        Nhập tên người dùng hoặc nội dung bài viết để tìm kiếm.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <script>
            function handleFollowAction(btn, targetId, action) {
                if (btn.disabled) return;
                
                // Khóa nút và hiển thị hiệu ứng chờ
                btn.disabled = true;
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>...';

                // Dùng URLSearchParams để gửi dữ liệu chuẩn
                const formData = new URLSearchParams();
                formData.append('target_id', targetId);
                formData.append('action', action);

                fetch('friends_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (action === 'send_request') {
                            // Chuyển sang trạng thái chờ
                            btn.style.background = '#f0f2f5';
                            btn.style.color = '#65676b';
                            btn.innerHTML = '<i class="fa-solid fa-clock"></i> Đã gửi yêu cầu';
                            btn.onclick = null; 
                        } else if (action === 'unfollow') {
                            // Chuyển về trạng thái Theo dõi ban đầu
                            btn.disabled = false;
                            btn.className = 'btn-connect';
                            btn.innerHTML = '<i class="fa-solid fa-plus"></i> Theo dõi';
                            // Gán lại sự kiện send_request
                            btn.onclick = function() { handleFollowAction(this, targetId, 'send_request'); };
                        }
                    } else {
                        alert(data.message);
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                    }
                })
                .catch(err => {
                    console.error("Lỗi:", err);
                    alert("Có lỗi xảy ra trong quá trình gửi dữ liệu.");
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                });
            }
    </script>

</body>
</html>