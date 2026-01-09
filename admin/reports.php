<?php
// Bắt buộc phải đăng nhập và là Admin
session_start();

// Kiểm tra xem đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Kiểm tra quyền Admin
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

require_once '../includes/database.php';
require_once '../includes/functions.php';

// Lấy thông tin admin đang đăng nhập
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['user_name'];
$admin_avatar = $_SESSION['user_avatar'] ?? '../uploads/avatars/default_admin_avatar.png';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - TSix</title>
    <link rel="stylesheet" href="/web-social-network/assets/Style-css/admin_layout.css">
    <link rel="stylesheet" href="/web-social-network/assets/Style-css/reports.css">
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
                <li><a href="users.php"><i class="fa-solid fa-users"></i> Người dùng</a></li>
                <li><a href="posts.php"><i class="fa-solid fa-file-lines"></i> Bài viết và bình luận</a></li>
                <li class="menu-selected"><a href="reports.php"><i class="fa-solid fa-flag"></i> Báo cáo</a></li>
            </ul>

            <div class="logout">
                <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
            </div>
        </aside>

        <!-- MAIN -->
        <main class="main">

            <!-- TOPBAR -->
            <header class="topbar">
                <span>Admin / Báo cáo</span>
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
                <h2>Danh sách Báo cáo</h2>
                <p>Xem xét và xử lý các nội dung vi phạm tiêu chuẩn cộng đồng.</p>
                
            </section>

            <!-- STATS -->
            <?php
            require_once '../includes/database.php';
            $sql_pending = "SELECT COUNT(*) AS count FROM Reports WHERE Status = 'pending'";
            $result_sql_pending = mysqli_query($conn, $sql_pending);
            $pending = $result_sql_pending->fetch_assoc();

            $sql_resolved = "SELECT COUNT(*) AS count FROM Reports WHERE Status = 'resolved'";
            $result_sql_resolved = mysqli_query($conn, $sql_resolved);
            $resolved = $result_sql_resolved->fetch_assoc();

            $sql_rejected = "SELECT COUNT(*) AS count FROM Reports WHERE Status = 'rejected'";
            $result_sql_rejected = mysqli_query($conn, $sql_rejected);
            $rejected = $result_sql_rejected->fetch_assoc();
            ?>
            <section class="stats">
                <div class="stat waiting">
                    <p>Chờ xử lý</p>
                    <h3><?= $pending['count'] ?></h3>
                    <i class="fa-solid fa-hourglass"></i>
                </div>
                <div class="stat done">
                    <p>Đã xử lý hôm nay</p>
                    <h3><?= $resolved['count'] ?></h3>
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="stat deleted">
                    <p>Đã từ chối</p>
                    <h3><?= $rejected['count'] ?></h3>
                    <i class="fa-solid fa-trash"></i>
                </div>
            </section>

            <!-- FILTER -->
            <?php
            // Xử lý Filter
            $where = [];
            $status = '';
            $ObjectType = '';
            /* Keyword: ReportID hoặc tên người báo cáo */
            if (!empty($_GET['keyword'])) {
                $kw = mysqli_real_escape_string($conn, $_GET['keyword']);
                $where[] = "(r.ReportID LIKE '%$kw%' OR u.FullName LIKE '%$kw%')";
            }

            /* Status */
            if (!empty($_GET['status'])) {
                $status = mysqli_real_escape_string($conn, $_GET['status']);
                $where[] = "r.Status = '$status'";
            }

            /* Đối tượng: Bài viết / Bình luận */
            if (!empty($_GET['ObjectType'])) {
                if ($_GET['ObjectType'] === 'post') {
                    $where[] = "r.FK_PostID IS NOT NULL";
                } elseif ($_GET['ObjectType'] === 'comment') {
                    $where[] = "r.FK_CommentID IS NOT NULL";
                }
            }
            ?>
            <section class="filter">
                <form method="GET" class="filter">
                    <input type="text" name="keyword" placeholder="Tìm theo ID, Người báo cáo...">

                    <select name="status">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>
                            Chờ xử lý
                        </option>
                        <option value="resolved" <?= ($_GET['status'] ?? '') === 'resolved' ? 'selected' : '' ?>>
                            Đã xử lý
                        </option>
                        <option value="rejected" <?= ($_GET['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>
                            Bỏ qua
                        </option>
                    </select>


                    <select name="ObjectType">
                        <option value="">Tất cả loại</option>
                        <option value="post" <?= ($_GET['ObjectType'] ?? '') === 'post' ? 'selected' : '' ?>>
                            Bài viết
                        </option>
                        <option value="comment" <?= ($_GET['ObjectType'] ?? '') === 'comment' ? 'selected' : '' ?>>
                            Bình luận
                        </option>
                    </select>


                    <button type="submit">Lọc</button>
                </form>

            </section>

            <!-- TABLE -->
            <section class="table-box">
                <table>
                    <tr>
                        <th>ReportID</th>
                        <th>Người báo cáo</th>
                        <th>Đối tượng</th>
                        <th>Lý do</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                    <?php
                    $sql_reports = "SELECT 
                                        r.ReportID, 
                                        u.Avatar,
                                        u.FullName, u.UserID,
                                        r.FK_PostID, 
                                        p.Content as 'p.Content', 
                                        r.FK_CommentID, 
                                        c.Content as 'c.Content', 
                                        r.Reason,
                                        r.Status,
                                        u.Email,
                                        CASE 
                                            WHEN r.FK_PostID IS NULL THEN 'Bình luận' 
                                            ELSE 'Bài viết' 
                                        END AS 'ObjectType'
                                    FROM Reports r 
                                    LEFT JOIN Posts p ON r.FK_PostID = p.PostID
                                    LEFT JOIN Comments c ON r.FK_CommentID = c.CommentID
                                    LEFT JOIN Users u ON p.FK_UserID = u.UserID";
                    if (!empty($where)) {
                        $sql_reports .= " WHERE " . implode(" AND ", $where);
                    }
                    $results_reports = mysqli_query($conn, $sql_reports);
                    $reports_lists = $results_reports->fetch_all(MYSQLI_ASSOC);

                    $count = 0;
                    foreach ($reports_lists as $report) {
                        $count++; ?>
                        <tr>
                            <td><?= $report['ReportID'] ?></td>
                            <td>
                                <div class="reporter-info">
                                    <img style="width: 30px; height: 30px;border-radius: 42px;" src="../uploads/avatars/<?= $report['Avatar'] ?>" alt="">
                                    <div>
                                        <p><?= $report['FullName'] ?></p>
                                        <p><?= $report['Email'] ?></p>
                                    </div>
                                </div>

                            </td>

                            <td class="reporter-content">

                                <p><?= $report['ObjectType'] == "Bài viết" ? '<i class="fa-solid fa-comment"></i>' : '<i class="fa-solid fa-inbox"></i>' ?> <?= $report['ObjectType'] ?></p>
                                <p><?= $report['ObjectType'] == "Bài viết" ? $report['p.Content'] : $report['c.Content'] ?></p>
                                <p>ID: <?= $report['ObjectType'] == "Bài viết" ? $report['FK_PostID'] : $report['FK_CommentID'] ?></p>
                            </td>
                            <td><span class="tag red"><?= $report['Reason'] ?></span></td>
                            <td><span class="status <?php
                                                    if ($report['Status'] === 'pending') {
                                                        echo 'orange';
                                                    } elseif ($report['Status'] === 'resolved') {
                                                        echo 'green';
                                                    } else {
                                                        echo 'red';
                                                    } ?>"><?= $report['Status'] ?></span></td>

                            <td class="actions">
                                <a href="post.php?FK_PostID=<?= $report['FK_PostID'] ?>"><i class="fa-solid fa-eye"></i></a>
                                <a href="reports_del.php?ReportID=<?= $report['ReportID'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa báo cáo này?');"><i class="fa-solid fa-trash"></i></a>
                                <a href="reports_res.php?ReportID=<?= $report['ReportID'] ?>"><i class="fa-solid fa-check"></i></a>
                            </td>
                        </tr>
                    <?php }
                    ?>



                </table>

                <div class="pagination">
                    Tìm thấy <?= $count ?> kết quả. </div>
            </section>

    </div>
</body>

</html>