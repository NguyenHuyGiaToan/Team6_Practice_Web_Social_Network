<?php
require_once '../includes/database.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';
// Phân trang
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$limit = 5; // Chỉ 6 dòng mỗi trang
$offset = ($page - 1) * $limit;

// Xử lý actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'lock') {
        $stmt = $conn->prepare("UPDATE Users SET Status = 'locked' WHERE UserID = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $_SESSION['message'] = "Đã khóa tài khoản!";
    } elseif ($action == 'unlock') {
        $stmt = $conn->prepare("UPDATE Users SET Status = 'active' WHERE UserID = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $_SESSION['message'] = "Đã mở khóa tài khoản!";
    } elseif ($action == 'delete') {
        $stmt = $conn->prepare("DELETE FROM Users WHERE UserID = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $_SESSION['message'] = "Đã xóa tài khoản!";
    }

    header('Location: users.php');
    exit;
}

// Xử lý thêm người dùng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');

    // Kiểm tra email đã tồn tại chưa
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM Users WHERE Email = ?");
    $check_stmt->bind_param('s', $email);
    $check_stmt->execute();
    $check_stmt->bind_result($existing_count);
    $check_stmt->fetch();
    $check_stmt->close();
    if ($existing_count > 0) {
        $_SESSION['error'] = "Email đã tồn tại!";
    } else {
        // Mật khẩu mặc định: 123456
        $hashed_password = password_hash('123456', PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO Users (FullName, Email, Phone, PasswordHash, Gender, Role, Status, Avatar) 
                               VALUES (?, ?, ?, ?, 'Nam', 'user', 'active', 'default_avatar.png')");
        $stmt->bind_param('ssss', $fullname, $email, $phone, $hashed_password);
        $stmt->execute();
        $_SESSION['message'] = "Đã thêm người dùng mới!";
    }

    header('Location: users.php');
    exit;
}

// Xử lý cập nhật người dùng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $id = intval($_POST['user_id']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone'] ?? '');

    // Kiểm tra email đã tồn tại chưa (trừ chính user đó)
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM Users WHERE Email = ? AND UserID != ?");
    $check_stmt->bind_param('si', $email, $id);
    $check_stmt->execute();
    $check_stmt->bind_result($existing_count2);
    $check_stmt->fetch();
    $check_stmt->close();
    if ($existing_count2 > 0) {
        $_SESSION['error'] = "Email đã tồn tại bởi người dùng khác!";
    } else {
        $stmt = $conn->prepare("UPDATE Users SET FullName = ?, Email = ?, Phone = ? WHERE UserID = ?");
        $stmt->bind_param('sssi', $fullname, $email, $phone, $id);
        $stmt->execute();
        $_SESSION['message'] = "Đã cập nhật thông tin người dùng!";
    }

    header('Location: users.php');
    exit;
}

// Lấy tham số tìm kiếm và lọc
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

// Xây dựng query
$sql = "SELECT UserID, FullName, Email, Phone, Status, CreatedAt, Avatar FROM Users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (FullName LIKE ? OR Email LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($status_filter == 'active') {
    $sql .= " AND Status = 'active'";
} elseif ($status_filter == 'locked') {
    $sql .= " AND Status = 'locked'";
}

$sql .= " ORDER BY UserID, CreatedAt DESC LIMIT $limit OFFSET $offset";

// Thực thi query
$stmt = $conn->prepare($sql);
if (!empty($search)) {
    // two string params for the LIKE clauses
    $stmt->bind_param('ss', $search_term, $search_term);
}
$stmt->execute();
$res = $stmt->get_result();
$users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Hiển thị thông báo
if (isset($_SESSION['message'])) {
    echo '<div style="background: #D1FAE5; color: #059669; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> ' . $_SESSION['message'] . '
          </div>';
    unset($_SESSION['message']);
}

if (isset($_SESSION['error'])) {
    echo '<div style="background: #FEE2E2; color: #DC2626; padding: 12px; border-radius: 8px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i> ' . $_SESSION['error'] . '
          </div>';
    unset($_SESSION['error']);
}
?>
<?php
// Bắt buộc phải đăng nhập và là Admin
// config.php already starts the session; avoid calling session_start() again

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
    <link rel="stylesheet" href="/web-social-network/assets/Style-css/users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="logo">
                <img src="../assets/images/avt.png">
                <span>TSix Admin</span>
            </div>

            <ul class="menu">
                <li><a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> Tổng quan</a></li>
                <li class="menu-selected"><a href="users.php"><i class="fa-solid fa-users"></i> Người dùng</a></li>
                <li><a href="posts.php"><i class="fa-solid fa-file-lines"></i> Bài viết và bình luận</a></li>
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
                <span>Admin / Quản lý người dùng</span>
                <div class="topbar-right">
                    <input type="text" placeholder="Tìm kiếm...">
                    <i class="fa-regular fa-bell"></i>
                    <a href="../profile.php" style="cursor:pointer; display: grid; grid-template-columns: auto auto; align-items: center; gap: 8px;">
                        <img width="30px" height="30px" src="../uploads/avatars/<?php echo $admin_avatar; ?>" class="avatar" alt="">
                        <span style="font-weight: 600;"><?php echo htmlspecialchars($admin_name ?? '', ENT_QUOTES); ?></span>
                    </a>
                </div>
            </header>
            <!-- TITLE -->
            <section class="title" >
                <h2>Quản lý người dùng</h2>
                <p>Xem và quản lý tất cả các thành viên trong mạng xã hội.</p>
            </section>
            <div class="card" style="margin-top: 20px;">
                <!-- Filter bar -->
                <div class="users-filter">
                    <form method="GET" action="users.php" class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($search) ?>">
                    </form>

                    <div class="filter-tabs">
                        <a href="?status=all&search=<?= urlencode($search) ?>"
                            class="filter-tab <?= $status_filter == 'all' ? 'active' : '' ?>">Tất cả</a>
                        <a href="?status=active&search=<?= urlencode($search) ?>"
                            class="filter-tab <?= $status_filter == 'active' ? 'active' : '' ?>">Active</a>
                        <a href="?status=locked&search=<?= urlencode($search) ?>"
                            class="filter-tab <?= $status_filter == 'locked' ? 'active' : '' ?>">Locked</a>
                    </div>

                    <button type="button" class="add-user-btn" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Thêm mới
                    </button>
                </div>

                <!-- Users table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Người dùng</th>
                                <th>Email/Phone</th>
                                <th>Ngày tham gia</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #6B7280;">
                                        <i class="fas fa-users-slash" style="font-size: 48px; margin-bottom: 15px;"></i>
                                        <h3>Không tìm thấy người dùng</h3>
                                        <p>Thử thay đổi từ khóa tìm kiếm hoặc bộ lọc</p>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #374151;"><?= htmlspecialchars($user['UserID']) ?></td>
                                    <td>
                                        <div class="user-info">
                                            <?php 
                                                $avatarPath = "../uploads/avatars/" . $user['Avatar'];
                                                  
                                                if (!empty($user['Avatar']) && file_exists($avatarPath)) {
                                                    $displayAvatar = $avatarPath;
                                                } else {
                                                    $displayAvatar = "../uploads/avatars/default_avatar.png";
                                                }
                                            ?>
                                            
                                            <img src="<?= htmlspecialchars($displayAvatar, ENT_QUOTES) ?>" 
                                                class="user-avatar-small" 
                                                alt="Avatar">
                                                
                                            <span><?= htmlspecialchars($user['FullName'] ?? '', ENT_QUOTES) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php  
                                            $displayContact = (!empty($user['Email'])) ? $user['Email'] : ($user['Phone'] ?? 'N/A');
                                            echo htmlspecialchars($displayContact, ENT_QUOTES); 
                                        ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($user['CreatedAt'])) ?></td>
                                    <td>
                                        <?php if ($user['Status'] == 'active'): ?>
                                            <a href="?action=lock&id=<?= $user['UserID'] ?>"
                                                style="background: #D1FAE5; color: #059669; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; text-decoration: none; display: inline-block;"
                                                onclick="return confirm('Khóa tài khoản này?')">
                                                Active
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=unlock&id=<?= $user['UserID'] ?>"
                                                style="background: #FEE2E2; color: #DC2626; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500; text-decoration: none; display: inline-block;"
                                                onclick="return confirm('Mở khóa tài khoản này?')">
                                                Locked
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($user['Status'] == 'active'): ?>
                                                <a href="?action=lock&id=<?= $user['UserID'] ?>"
                                                    style="background: #FEF3C7; color: #D97706; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px; cursor: pointer"
                                                    onclick="return confirm('Khóa tài khoản này?')">
                                                    <i class="fas fa-lock"></i> Khóa
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=unlock&id=<?= $user['UserID'] ?>"
                                                    style="background: #D1FAE5; color: #059669; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;cursor: pointer"
                                                    onclick="return confirm('Mở khóa tài khoản này?')">
                                                    <i class="fas fa-unlock"></i> Mở
                                                </a>
                                            <?php endif; ?>

                                            <!-- THÊM NÚT SỬA Ở ĐÂY - GIỮA NÚT KHÓA/MỞ VÀ XÓA -->
                                            <button type="button" onclick="openEditModal(
                                                '<?= $user['UserID'] ?>',
                                                '<?= htmlspecialchars(addslashes($user['FullName'] ?? ''), ENT_QUOTES) ?>',
                                                '<?= htmlspecialchars(addslashes($user['Email'] ?? ''), ENT_QUOTES) ?>',
                                                '<?= htmlspecialchars(addslashes($user['Phone'] ?? ''), ENT_QUOTES) ?>'
                                                )" style="background: #E0F2FE; color: #0369A1; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px; border: none; cursor: pointer;">
                                                <i class="fas fa-edit"></i> Sửa
                                            </button>

                                            <a href="?action=delete&id=<?= $user['UserID'] ?>"
                                                style="background: #FEE2E2; color: #DC2626; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;"
                                                onclick="return confirm('Xóa người dùng này?')">
                                                <i class="fas fa-trash"></i> Xóa
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <!-- PHÂN TRANG ĐƠN GIẢN -->
                    <?php if (count($users) == $limit || $page > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">&lt;</a>
                            <?php endif; ?>

                            <span>Trang <?= $page ?></span>

                            <?php if (count($users) == $limit): ?>
                                <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">&gt;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>


            <!-- MODAL THÊM MỚI -->
            <div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: white; padding: 30px; border-radius: 12px; width: 500px; max-width: 90%;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="font-size: 24px; font-weight: bold;">Thêm Người dùng Mới</h2>
                        <button onclick="closeAddModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6B7280;">&times;</button>
                    </div>

                    <form method="POST" action="users.php">
                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Họ và tên *</label>
                            <input type="text" name="fullname" required style="width: 100%; padding: 10px; border: 1px solid #D1D5DB; border-radius: 6px;">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Email *</label>
                            <input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #D1D5DB; border-radius: 6px;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Số điện thoại</label>
                            <input type="tel" name="phone" style="width: 100%; padding: 10px; border: 1px solid #D1D5DB; border-radius: 6px;">
                        </div>

                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button type="button" onclick="closeAddModal()" style="padding: 10px 20px; background: #F3F4F6; color: #374151; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Hủy</button>
                            <button type="submit" name="add_user" style="padding: 10px 20px; background: #791326; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Thêm mới</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL CHỈNH SỬA -->
            <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
                <div style="background: white; padding: 30px; border-radius: 12px; width: 500px; max-width: 90%;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="font-size: 24px; font-weight: bold;">Chỉnh sửa Người dùng</h2>
                        <button onclick="closeEditModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6B7280;">&times;</button>
                    </div>

                    <form method="POST" action="users.php">
                        <input type="hidden" name="user_id" id="edit_user_id">

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Họ và tên *</label>
                            <input type="text" name="fullname" id="edit_fullname" required style="width: 100%; padding: 10px; border: 1px solid #D1D5DB; border-radius: 6px;">
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Email *</label>
                            <input type="email" name="email" id="edit_email" required style="width: 100%; padding: 10px; border: 1px solid #D1D5DB; border-radius: 6px;">
                        </div>

                        <div style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Số điện thoại</label>
                            <input type="tel" name="phone" id="edit_phone" style="width: 100%; padding: 10px; border: 1px solid #D1D5DB; border-radius: 6px;">
                        </div>

                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button type="button" onclick="closeEditModal()" style="padding: 10px 20px; background: #F3F4F6; color: #374151; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Hủy</button>
                            <button type="submit" name="update_user" style="padding: 10px 20px; background: #791326; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;">Cập nhật</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</body>

</html>

<script>
    function openAddModal() {
        document.getElementById('addModal').style.display = 'flex';
    }

    function closeAddModal() {
        document.getElementById('addModal').style.display = 'none';
    }

    function openEditModal(userId, fullname, email, phone) {
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_fullname').value = fullname;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_phone').value = phone;
        document.getElementById('editModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Đóng modal khi click bên ngoài
    document.getElementById('addModal').addEventListener('click', function(e) {
        if (e.target.id === 'addModal') {
            closeAddModal();
        }
    });

    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target.id === 'editModal') {
            closeEditModal();
        }
    });

    // Enter để tìm kiếm
    document.querySelector('input[name="search"]').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            this.form.submit();
        }
    });
</script>