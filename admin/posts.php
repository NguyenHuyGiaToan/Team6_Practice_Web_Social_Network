<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 4; // 6 bài viết mỗi trang
$offset = ($page - 1) * $limit;

// PHẦN 1: TỰ ĐỘNG PHÂN PHỐI CHO NHIỀU NGƯỜI
$all_users = [];
$res = $conn->query("SELECT UserID, FullName FROM Users");
if ($res) $all_users = $res->fetch_all(MYSQLI_ASSOC);

if (count($all_users) > 1) {
    // 1. Phân phối bài viết
    $all_posts = [];
    $res2 = $conn->query("SELECT PostID FROM Posts");
    if ($res2) {
        $rows = $res2->fetch_all(MYSQLI_ASSOC);
        $all_posts = array_column($rows, 'PostID');
    }

    $updPostStmt = $conn->prepare("UPDATE Posts SET FK_UserID = ? WHERE PostID = ?");
    foreach ($all_posts as $index => $post_id) {
        $user_index = $index % count($all_users);
        $user_id = $all_users[$user_index]['UserID'];
        $updPostStmt->bind_param('ii', $user_id, $post_id);
        $updPostStmt->execute();
    }

    // 2. Phân phối bình luận chờ duyệt
    $pending_comments = [];
    $res3 = $conn->query("SELECT CommentID FROM Comments WHERE Status = 'pending'");
    if ($res3) {
        $rows = $res3->fetch_all(MYSQLI_ASSOC);
        $pending_comments = array_column($rows, 'CommentID');
    }

    if (count($pending_comments) > 0) {
        $updCommentStmt = $conn->prepare("UPDATE Comments SET FK_UserID = ? WHERE CommentID = ?");
        foreach ($pending_comments as $index => $comment_id) {
            $user_index = rand(0, count($all_users) - 1);
            $user_id = $all_users[$user_index]['UserID'];
            $updCommentStmt->bind_param('ii', $user_id, $comment_id);
            $updCommentStmt->execute();
        }
    }
}


// PHẦN 2: Xử lý actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $type = $_GET['type'] ?? 'post';
    
    if ($_GET['action'] == 'delete') {
        if ($type == 'post') {
            $stmtu = $conn->prepare("UPDATE Posts SET Status = 'deleted' WHERE PostID = ?");
            $stmtu->bind_param('i', $id);
            $stmtu->execute();
            $_SESSION['message'] = "Đã xóa bài viết!";
        } else {
            $stmtu = $conn->prepare("UPDATE Comments SET Status = 'deleted' WHERE CommentID = ?");
            $stmtu->bind_param('i', $id);
            $stmtu->execute();
            $_SESSION['message'] = "Đã xóa bình luận!";
        }
    } elseif ($_GET['action'] == 'approve') {
        $stmtu = $conn->prepare("UPDATE Comments SET Status = 'active' WHERE CommentID = ?");
        $stmtu->bind_param('i', $id);
        $stmtu->execute();
        $_SESSION['message'] = "Đã duyệt bình luận!";
    } elseif ($_GET['action'] == 'hide') {
        $stmtu = $conn->prepare("UPDATE Posts SET Status = 'hidden' WHERE PostID = ?");
        $stmtu->bind_param('i', $id);
        $stmtu->execute();
        $_SESSION['message'] = "Đã ẩn bài viết!";
    } elseif ($_GET['action'] == 'show') {
        $stmtu = $conn->prepare("UPDATE Posts SET Status = 'active' WHERE PostID = ?");
        $stmtu->bind_param('i', $id);
        $stmtu->execute();
        $_SESSION['message'] = "Đã hiển thị bài viết!";
    }
    
    header('Location: posts.php');
    exit;
}

// Lấy tham số
$search = $_GET['search'] ?? '';
$time_filter = $_GET['time'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Query bài viết
$sql_posts = "SELECT p.*, u.FullName, u.Avatar 
              FROM Posts p 
              JOIN Users u ON p.FK_UserID = u.UserID 
              WHERE p.Status != 'deleted'";
$params = [];

if (!empty($search)) {
    $sql_posts .= " AND (p.Content LIKE ? OR u.FullName LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($time_filter == 'today') {
    $sql_posts .= " AND DATE(p.CreatedAt) = CURDATE()";
} elseif ($time_filter == 'week') {
    $sql_posts .= " AND p.CreatedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($time_filter == 'month') {
    $sql_posts .= " AND p.CreatedAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
}

if ($status_filter == 'active') {
    $sql_posts .= " AND p.Status = 'active'";
} elseif ($status_filter == 'hidden') {
    $sql_posts .= " AND p.Status = 'hidden'";
}

$sql_posts .= " ORDER BY p.CreatedAt DESC LIMIT $limit OFFSET $offset";
$sql_exec = $sql_posts;
// If search present, escape it into the SQL
if (!empty($search)) {
    $esc = $conn->real_escape_string($search);
    // replace the placeholders with escaped LIKEs
    // Note: original $sql_posts already included the LIKE placeholders; rebuild to be safe
    $sql_exec = str_replace("(p.Content LIKE ? OR u.FullName LIKE ?)", "(p.Content LIKE '%$esc%' OR u.FullName LIKE '%$esc%')", $sql_posts);
}
$resPosts = $conn->query($sql_exec);
$posts = $resPosts ? $resPosts->fetch_all(MYSQLI_ASSOC) : [];

// Query bình luận chờ duyệt
$sql_comments = "SELECT c.*, u.FullName, p.Content as PostContent 
                 FROM Comments c 
                 JOIN Users u ON c.FK_UserID = u.UserID 
                 JOIN Posts p ON c.FK_PostID = p.PostID 
                 WHERE c.Status = 'pending' 
                 ORDER BY c.CreatedAt DESC LIMIT 20";
$resComments = $conn->query($sql_comments);
$pending_comments = $resComments ? $resComments->fetch_all(MYSQLI_ASSOC) : [];

$pending_count = count($pending_comments);

// Hiển thị thông báo
if (isset($_SESSION['message'])) {
    echo '<div style="background: #D1FAE5; color: #059669; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> ' . $_SESSION['message'] . '
          </div>';
    unset($_SESSION['message']);
}
?>
<?php
// Bắt buộc phải đăng nhập và là Admin
// `config.php` already starts the session; avoid calling session_start() again

// Kiểm tra xem đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Kiểm tra quyền Admin
if (($_SESSION['user_role'] ?? null) !== 'admin' && ($_SESSION['role'] ?? null) !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Lấy thông tin admin đang đăng nhập
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'] ?? '';
$admin_avatar = $_SESSION['user_avatar'] ?? '../uploads/avatars/default_admin_avatar.png';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - TSix</title>
    <link rel="stylesheet" href="/web-social-network/assets/Style-css/admin_layout.css">
    <link rel="stylesheet" href="/web-social-network/assets/Style-css/posts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo">
                <i class="fa-solid fa-shield-halved"></i>
                <span>TSix Admin</span>
            </div>

            <ul class="menu">
                <li><a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> Tổng quan</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users"></i> Người dùng</a></li>
                <li class="menu-selected"><a href="posts.php"><i class="fa-solid fa-file-lines"></i> Bài viết và bình luận</a></li>
                <li><a href="reports.php"><i class="fa-solid fa-flag"></i> Báo cáo</a></li>
            </ul>

            <div class="logout">
                <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="main">

            <!-- TOPBAR -->
            <header class="topbar">
                <span>Admin / Quản lý bài viết</span>
                <div class="topbar-right">
                    <input type="text" placeholder="Tìm kiếm...">
                    <i class="fa-regular fa-bell"></i>
                    <a href="../profile.php" style="cursor:pointer; display: grid; grid-template-columns: auto auto; align-items: center; gap: 8px;">
                        <img width="30px" height="30px" src="../uploads/avatars/<?php echo $admin_avatar; ?>" class="avatar" alt="">
                        <span style="font-weight: 600;"><?php echo htmlspecialchars($admin_name); ?></span>
                    </a>
                </div>
            </header>
            <!-- TITLE -->
            <section class="title">
                <h2>Quản lý bài viết</h2>
                <p>Kiểm duyệt nội dung bài đăng, xóa các bài viết vi phạm và xử lý các bình luận bị báo cáo từ người dùng.</p>
            </section>
            <!-- CONTENT -->
            <div class="card" style="margin-top: 20px;">
                <!-- Tabs -->
                <div class="posts-tabs">
                    <div class="post-tab active" onclick="switchTab('posts')">Tất cả bài viết</div>
                    <div class="post-tab" onclick="switchTab('comments')">
                        Bình luận chờ duyệt 
                        <?php if ($pending_count > 0): ?>
                            <span class="pending-count"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            
                <!-- Posts section -->
                <div id="postsSection">
                    <!-- Filter -->
                    <div class="posts-filter">
                        <form method="GET" action="posts.php">
                            <input type="text" name="search" class="search-input" 
                                   placeholder="Tìm kiếm theo nội dung hoặc tên tác giả" 
                                   value="<?= htmlspecialchars($search) ?>">
                            
                            <select name="time" class="time-select">
                                <option value="all" <?= $time_filter == 'all' ? 'selected' : '' ?>>Tất cả thời gian</option>
                                <option value="today" <?= $time_filter == 'today' ? 'selected' : '' ?>>Hôm nay</option>
                                <option value="week" <?= $time_filter == 'week' ? 'selected' : '' ?>>Tuần này</option>
                                <option value="month" <?= $time_filter == 'month' ? 'selected' : '' ?>>Tháng này</option>
                            </select>
                            
                            <select name="status" class="status-select">
                                <option value="all" <?= $status_filter == 'all' ? 'selected' : '' ?>>Trạng thái</option>
                                <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="hidden" <?= $status_filter == 'hidden' ? 'selected' : '' ?>>Hidden</option>
                            </select>
                            
                            <button type="submit" class="apply-btn">Áp dụng</button>
                        </form>
                    </div>
            
                    <!-- Posts table -->
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>BÀI VIẾT</th>
                                    <th>TÁC GIẢ</th>
                                    <th>NGÀY ĐĂNG</th>
                                    <th>TRẠNG THÁI</th>
                                    <th>HÀNH ĐỘNG</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fas fa-file-alt"></i>
                                        <h3>Không tìm thấy bài viết</h3>
                                        <p>Thử thay đổi từ khóa tìm kiếm hoặc bộ lọc</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <div class="post-with-image">
                                            <div class="post-image">
                                                <img src="https://picsum.photos/60/60?random=<?= $post['PostID'] ?>" alt="Ảnh">
                                            </div>
                                            <div class="post-content">
                                                <div class="post-text"><?= htmlspecialchars(substr($post['Content'], 0, 70)) ?>...</div>
                                                <div class="post-id">ID: <?= $post['PostID'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <img src="../uploads/avatars/<?= htmlspecialchars($post['Avatar'] ?? 'default.png') ?>" 
                                                 class="user-avatar" 
                                                 onerror="this.src='https://i.pravatar.cc/40?u=<?= $post['FK_UserID'] ?>'">
                                            <span><?= htmlspecialchars($post['FullName']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($post['CreatedAt'])) ?></td>
                                    <td>
                                        <?php if ($post['Status'] == 'active'): ?>
                                            <a href="?action=hide&id=<?= $post['PostID'] ?>" 
                                               class="status-badge active"
                                               onclick="return confirm('Ẩn bài viết này?')">
                                                <i class="fas fa-eye"></i> Hiển thị
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=show&id=<?= $post['PostID'] ?>" 
                                               class="status-badge hidden"
                                               onclick="return confirm('Hiển thị bài viết này?')">
                                                <i class="fas fa-eye-slash"></i> Ẩn
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="../post.php?id=<?= $post['PostID'] ?>" class="btn view">
                                                <i class="fas fa-eye"></i> Xem chi tiết
                                            </a>
                                            <a href="?action=delete&id=<?= $post['PostID'] ?>" 
                                               class="btn delete"
                                               onclick="return confirm('Xóa bài viết này?')">
                                                <i class="fas fa-trash"></i> Xóa bài
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                                        <!-- PHÂN TRANG BÀI VIẾT -->
                    <?php if (count($posts) == $limit || $page > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&time=<?= $time_filter ?>">&lt;</a>
                        <?php endif; ?>
                        
                        <span>Trang <?= $page ?></span>
                        
                        <?php if (count($posts) == $limit): ?>
                        <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&time=<?= $time_filter ?>">&gt;</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    </div>
                </div>
            
                <!-- Comments section -->
                <div id="commentsSection" style="display: none;">
                    <div class="comment-moderation">
                        <div class="moderation-header">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Bình luận chờ duyệt</h3>
                        </div>
                        <p>Các bình luận này chứa từ khóa nhạy cảm và cần được kiểm duyệt trước khi hiển thị.</p>
                    </div>
            
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>NỘI DUNG BÌNH LUẬN</th>
                                    <th>BÀI VIẾT</th>
                                    <th>TÁC GIẢ</th>
                                    <th>NGÀY ĐĂNG</th>
                                    <th>HÀNH ĐỘNG</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pending_comments)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="fas fa-check-circle"></i>
                                        <h3>Không có bình luận nào chờ duyệt</h3>
                                        <p>Tất cả bình luận đã được kiểm duyệt và hiển thị.</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                
                                <?php foreach ($pending_comments as $comment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($comment['Content']) ?></td>
                                    <td>
                                        <a href="../post.php?id=<?= $comment['FK_PostID'] ?>" class="post-link">
                                            <?= htmlspecialchars(substr($comment['PostContent'], 0, 50)) ?>...
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($comment['FullName']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($comment['CreatedAt'])) ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="?action=approve&id=<?= $comment['CommentID'] ?>&type=comment" 
                                               class="btn approve"
                                               onclick="return confirm('Duyệt bình luận này?')">
                                                <i class="fas fa-check"></i> Duyệt hiển thị
                                            </a>
                                            <a href="?action=delete&id=<?= $comment['CommentID'] ?>&type=comment" 
                                               class="btn delete"
                                               onclick="return confirm('Xóa vĩnh viễn bình luận này?')">
                                                <i class="fas fa-trash"></i> Xóa vĩnh viễn
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
</body>
</html>

<script>
function switchTab(tab) {
    const postsTab = document.querySelector('.post-tab:first-child');
    const commentsTab = document.querySelector('.post-tab:last-child');
    const postsSection = document.getElementById('postsSection');
    const commentsSection = document.getElementById('commentsSection');
    
    if (tab === 'posts') {
        postsTab.classList.add('active');
        commentsTab.classList.remove('active');
        postsSection.style.display = 'block';
        commentsSection.style.display = 'none';
    } else {
        postsTab.classList.remove('active');
        commentsTab.classList.add('active');
        postsSection.style.display = 'none';
        commentsSection.style.display = 'block';
    }
}

// Enter để tìm kiếm
document.querySelector('input[name="search"]').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') this.closest('form').submit();
});
</script>
<?php require_once '../includes/footer.php'; ?>