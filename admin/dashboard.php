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
    <link rel="stylesheet" href="/web-social-network/assets/Style-css/dashboard.css">
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
                <li class="menu-selected"><a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> Tổng quan</a></li>
                <li><a href="users.php"><i class="fa-solid fa-users"></i> Người dùng</a></li>
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
                <span>Admin / Bảng điều khiển</span>
                <div class="topbar-right">
                    <input type="text" placeholder="Tìm kiếm...">
                    <i class="fa-regular fa-bell"></i>
                    <a href="../profile.php" style="cursor:pointer; display: grid; grid-template-columns: auto auto; align-items: center; gap: 8px;">
                        <img width="30px" height="30px" src="../uploads/avatars/<?= $admin_avatar; ?>" class="avatar" alt="">
                        <span style="font-weight: 600;"><?php echo htmlspecialchars($admin_name); ?></span>
                    </a>
                </div>
            </header>

            <!-- TITLE -->
            <section class="title">
                <h2>Bảng điều khiển</h2>
                <p>Chào mừng trở lại, quản trị viên. Dưới đây là tình hình hoạt động của mạng xã hội hôm nay.</p>
            </section>

            <?php
            // Lấy thống kê người dùng
            $sql_total_user = "SELECT total_today, total_yesterday, 
                                ROUND( (total_today - total_yesterday) / NULLIF(total_yesterday, 0) * 100, 2 ) AS percent_change 
                                FROM    (SELECT COUNT(*) AS total_today 
                                            FROM users 
                                            WHERE DATE(CreatedAt) = CURDATE()) AS today, 
                                        (SELECT COUNT(*) AS total_yesterday 
                                            FROM users 
                                            WHERE DATE(CreatedAt) = CURDATE() - INTERVAL 1 DAY) AS yesterday;";
            $result_total_user = $conn->query($sql_total_user);
            if ($result_total_user && ($row = $result_total_user->fetch_assoc())) {
                $total_user_today   = (int)$row['total_today'];
                $total_user_percent = (float)$row['percent_change'];
            } else {
                $total_user_today = 0;
                $total_user_percent = 0;
            }

            // Lấy thống kê bài viết
            $sql_total_posts = "SELECT total_today, total_yesterday, 
                                ROUND( (total_today - total_yesterday) / NULLIF(total_yesterday, 0) * 100, 2 ) AS percent_change 
                                FROM    (SELECT COUNT(*) AS total_today 
                                            FROM posts 
                                            WHERE DATE(UpdatedAt) = CURDATE()) AS today, 
                                        (SELECT COUNT(*) AS total_yesterday 
                                            FROM posts 
                                            WHERE DATE(UpdatedAt) = CURDATE() - INTERVAL 1 DAY) AS yesterday;";
            $result_total_posts = $conn->query($sql_total_posts);
            if ($result_total_posts && ($row = $result_total_posts->fetch_assoc())) {
                $total_posts_today   = (int)$row['total_today'];
                $total_posts_percent = (float)$row['percent_change'];
            } else {
                $total_posts_today = 0;
                $total_posts_percent = 0;
            }

            // Lấy thống kê lượt truy cập
            $sql_total_visits = "SELECT total_today, total_yesterday, 
                                ROUND( (total_today - total_yesterday) / NULLIF(total_yesterday, 0) * 100, 2 ) AS percent_change 
                                FROM    (SELECT TotalVisits as total_today
                                            FROM system_stats 
                                            WHERE DATE(StatDate) = CURDATE()) AS today, 
                                        (SELECT TotalVisits AS total_yesterday 
                                            FROM system_stats 
                                            WHERE DATE(StatDate) = CURDATE() - INTERVAL 1 DAY) AS yesterday;";
            $result_total_visits = $conn->query($sql_total_visits);
            if ($result_total_visits && ($row = $result_total_visits->fetch_assoc())) {
                $total_visits_today   = (int)$row['total_today'];
                $total_visits_percent = (float)$row['percent_change'];
            } else {
                $total_visits_today = 0;
                $total_visits_percent = 0;
            }
            ?>

            <!-- STATS -->
            <section class="stats">
                <div class="card">
                    <div class="card_title">
                        <i class="fa-solid fa-people-group" style="background-color: #2a69e5;"></i>
                        <p>Tổng thành viên</p>
                    </div>
                    <div class="card_stats">

                        <h3><?= $total_user_today ?></h3>
                        <div class="change-percent" style="background-color: <?= $total_user_percent >= 0 ? '#22c55e33' : '#ef444433' ?>;
                                                            color: <?= $total_user_percent >= 0 ? '#16a34a' : '#dc2626' ?>;">
                            <?= $total_user_percent >= 0 ? '<i class="fa-solid fa-arrow-trend-up"></i>' : '<i class="fa-solid fa-arrow-trend-down"></i>' ?>
                            <?= abs($total_user_percent) ?>%
                        </div>
                    </div>
                    <span>(so với hôm qua)</span>
                </div>
                <div class="card">
                    <div class="card_title">
                        <i class="fa-solid fa-book" style="background-color: #a353f0"></i>
                        <p>Tổng bài viết</p>
                    </div>
                    <div class="card_stats">
                        <h3><?= $total_posts_today ?></h3>
                        <div class="change-percent" style="background-color: <?= $total_posts_percent >= 0 ? '#22c55e33' : '#ef444433' ?>;
                                                            color: <?= $total_posts_percent >= 0 ? '#16a34a' : '#dc2626' ?>;">
                            <?= $total_posts_percent >= 0 ? '<i class="fa-solid fa-arrow-trend-up"></i>' : '<i class="fa-solid fa-arrow-trend-down"></i>' ?>
                            <?= abs($total_posts_percent) ?>%
                        </div>
                    </div>
                    <span>(so với hôm qua)</span>
                </div>
                <div class="card">
                    <div class="card_title">
                        <i class="fa-solid fa-eye" style="background-color: #e1930e"></i>
                        <p class="card_title">Tổng lượt truy cập</p>
                    </div>
                    <div class="card_stats">
                        <h3><?= $total_visits_today ?></h3>
                        <div class="change-percent" style="background-color: <?= $total_visits_percent >= 0 ? '#22c55e33' : '#ef444433' ?>;
                                                            color: <?= $total_visits_percent >= 0 ? '#16a34a' : '#dc2626' ?>;">
                            <?= $total_visits_percent >= 0 ? '<i class="fa-solid fa-arrow-trend-up"></i>' : '<i class="fa-solid fa-arrow-trend-down"></i>' ?>
                            <?= abs($total_visits_percent) ?>%
                        </div>
                    </div>
                    <span>(so với hôm qua)</span>
                </div>
            </section>

            <!-- CONTENT -->
            <section class="content">

                <div class="box large">
                    <div class="box_title_container">
                        <h4 class="box-title">Thống kê bài viết mới</h4>
                        <?php
                        $option = "";
                        if (isset($_POST['option']))
                            $option = $_POST['option'];

                        $sql_last_7_days = "WITH RECURSIVE last_7_days AS (
                                                SELECT CURDATE() AS day_date
                                                UNION ALL
                                                SELECT DATE_SUB(day_date, INTERVAL 1 DAY)
                                                FROM last_7_days
                                                WHERE day_date > CURDATE() - INTERVAL 6 DAY
                                                )
                                                SELECT
                                                    DATE_FORMAT(d.day_date, '%d/%m') AS label,
                                                    COUNT(p.PostID) AS total_posts
                                                FROM last_7_days d
                                                LEFT JOIN posts p
                                                    ON DATE(p.CreatedAt) = d.day_date
                                                GROUP BY d.day_date
                                                ORDER BY d.day_date;";

                        $sql_last_4_weeks = "WITH RECURSIVE last_4_weeks AS (
                                                    SELECT CURDATE() AS week_date
                                                    UNION ALL
                                                    SELECT week_date - INTERVAL 1 WEEK
                                                    FROM last_4_weeks
                                                    WHERE week_date > CURDATE() - INTERVAL 3 WEEK
                                                )
                                                SELECT
                                                    CONCAT('W', WEEK(week_date, 1), '/', YEAR(week_date)) AS label,
                                                    COUNT(p.PostID) AS total_posts
                                                FROM last_4_weeks w
                                                LEFT JOIN Posts p
                                                    ON YEAR(p.CreatedAt) = YEAR(w.week_date)
                                                AND WEEK(p.CreatedAt, 1) = WEEK(w.week_date, 1)
                                                GROUP BY YEAR(w.week_date), WEEK(w.week_date, 1)
                                                ORDER BY YEAR(w.week_date), WEEK(w.week_date, 1);";

                        $sql_last_6_months = "WITH RECURSIVE last_6_months AS (
                                                    SELECT DATE_FORMAT(CURDATE(), '%Y-%m-01') AS month_date
                                                    UNION ALL
                                                    SELECT month_date - INTERVAL 1 MONTH
                                                    FROM last_6_months
                                                    WHERE month_date > CURDATE() - INTERVAL 5 MONTH
                                                )
                                                SELECT
                                                    DATE_FORMAT(m.month_date, '%m/%Y') AS label,
                                                    COUNT(p.PostID) AS total_posts
                                                FROM last_6_months m
                                                LEFT JOIN Posts p
                                                    ON DATE_FORMAT(p.CreatedAt, '%Y-%m') = DATE_FORMAT(m.month_date, '%Y-%m')
                                                GROUP BY m.month_date
                                                ORDER BY m.month_date;";

                        $sql_posts_over_time = $sql_last_7_days;
                        if ($option == 'w') {
                            $sql_posts_over_time = $sql_last_4_weeks;
                        } else if ($option == 'm') {
                            $sql_posts_over_time = $sql_last_6_months;
                        }
                        ?>

                        <form action="" method="POST">
                            <select name="option" id="" onchange="this.form.submit()">
                                <option <?php echo $option == 'd' ? 'selected' : ''; ?> value="d">7 ngày qua</option>
                                <option <?php echo $option == 'w' ? 'selected' : ''; ?> value="w">4 tuần qua</option>
                                <option <?php echo $option == 'm' ? 'selected' : ''; ?> value="m">6 tháng qua</option>
                            </select>
                        </form>
                    </div>

                    <div class="chart-placeholder">
                        <?php
                        $result_posts_over_time = $conn->query($sql_posts_over_time);
                        $posts_over_time = $result_posts_over_time->fetch_all(MYSQLI_ASSOC);
                        $days = $posts_over_time ? array_column($posts_over_time, 'label') : [];
                        $data = [];

                        foreach ($posts_over_time as $row) {
                            $data[] = (int)$row['total_posts'];
                        }

                        $max = max($data);
                        $max = $max > 0 ? $max : 1;
                        ?>
                        <div class="bar-chart">
                            <?php foreach ($data as $i => $value):
                                $height = ($value / $max) * 100;
                            ?>
                                <div class="bar-item">
                                    <div class="bar" style="height: <?= $height ?>%">
                                        <span><?= $value ?></span>
                                    </div>
                                    <label><?= $days[$i] ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="box">
                    <h4 class="box-title">Tỉ lệ chất lượng bài viết</h4>
                    <?php
                    $sql_post_ratio = "SELECT
                        COUNT(DISTINCT p.PostID) AS total_posts,
                        COUNT(DISTINCT CASE
                            WHEN r.ReportID IS NULL OR r.Status = 'resolved'
                            THEN p.PostID
                        END) AS valid_posts,
                        COUNT(DISTINCT CASE
                            WHEN r.Status = 'pending'
                            THEN p.PostID
                        END) AS invalid_posts
                    FROM posts p
                    LEFT JOIN reports r ON p.PostID = r.FK_PostID;";
                    $result_post_ratio = $conn->query($sql_post_ratio);
                    $post_ratio = $result_post_ratio->fetch_assoc();
                    $total_posts = (int)$post_ratio['total_posts'];
                    $valid_posts = (int)$post_ratio['valid_posts'];
                    $invalid_posts = (int)$post_ratio['invalid_posts'];
                    $valid_percentage = $total_posts > 0 ? round(($valid_posts / $total_posts) * 100, 2) : 0;
                    $invalid_percentage = $total_posts > 0 ? round(($invalid_posts / $total_posts) * 100, 2) : 0;
                    ?>
                    <div class="donut" style="--valid: <?= $valid_percentage ?>%;--invalid: <?= $invalid_percentage ?>%;">
                        <span><?= $valid_percentage ?>%</span>
                    </div>
                    <div class="legend_container">
                        <div class="legend_box">
                            <p class=""><i class="fa-solid fa-circle"></i>Bình thường</p>
                            <p><?= $valid_posts ?> (<?= $valid_percentage ?>%)</p>
                        </div>
                        <div class="legend_box">
                            <p class=""><i class="fa-solid fa-circle"></i>Vi phạm</p>
                            <p><?= $invalid_posts ?> (<?= $invalid_percentage ?>%)</p>
                        </div>
                    </div>
                </div>

            </section>

            <!-- TABLE -->
            <section class="content">
                <section class="table-box">
                    <h4 class="box-title">Top User hoạt động tích cực</h4>
                    <table>
                        <tr>
                            <th>Thành viên</th>
                            <th>Bài viết</th>
                            <th>Tương tác</th>
                            <th>Rank</th>
                        </tr>
                        <?php
                        $sql = "SELECT u.UserID,u.FullName,u.Avatar, SUM(p.LikeCount) as 'Số tương tác' , COUNT(p.PostID) as 'Số bài viết', 
                                        DENSE_RANK() OVER ( ORDER BY SUM(p.LikeCount) desc, COUNT(p.PostID) desc ) as 'Xếp hạng' 
                                        FROM posts p 
                                        LEFT JOIN users u ON p.FK_UserID = u.UserID and u.Role = 'user' 
                                        GROUP BY u.UserID,u.FullName
                                        LiMIT 3;";
                        $result = $conn->query($sql);
                        $top_users = $result->fetch_all(MYSQLI_ASSOC);
                        foreach ($top_users as $user) { ?>
                            <tr>
                                <td>
                                    <img style="width: 30px; height: 30px;border-radius: 42px;position: relative;top: 8px;"
                                        src="../uploads/avatars/<?= $user['Avatar'] ?? 'default_avatar.png' ?>" alt="">
                                    <?= $user['FullName'] ?>
                                </td>
                                <td><?= $user['Số bài viết'] ?></td>
                                <td><?= $user['Số tương tác'] ?></td>
                                <td>#<?= $user['Xếp hạng'] ?></td>
                            </tr>
                        <?php } ?>
                    </table>
                </section>

                <section class="table-box">
                    <h4 class="box-title">Top bài viết được tương tác nhiều</h4>
                    <?php
                    $sql_top_post = "SELECT p.PostID, p.Content,img.ImageUrl,img.ImageID, u.FullName, p.LikeCount, p.CommentCount, p.UpdatedAt
                                    FROM posts p
                                    LEFT JOIN users u on p.FK_UserID = u.UserID
                                    LEFT JOIN post_images img on p.PostID = img.FK_PostID
                                    ORDER BY p.LikeCount desc, p.CommentCount DESC
                                    LIMIT 1;";
                    $result_top_post = $conn->query($sql_top_post);
                    $top_posts = [];
                    if ($result_top_post)
                        $top_posts = $result_top_post->fetch_all(MYSQLI_ASSOC);
                    ?>
                    <div class="top_post_container">
                        <?php foreach ($top_posts as $top_post) { ?>
                            <img src="../uploads/<?= $top_post['ImageUrl'] ?>" alt="" class="top_post_img">
                            <div class="top_post_content">
                                <h4><?= $top_post['Content'] ?></h4>
                                <p>Đăng bởi <strong><?= $top_post['FullName'] ?></strong></p>
                                <span><?= timeAgo($top_post['UpdatedAt']) ?></span>
                                <div class="top_post_stats">
                                    <span><i class="fa-solid fa-thumbs-up"></i> <?= $top_post['LikeCount'] ?></span>
                                    <span><i class="fa-solid fa-comments"></i> <?= $top_post['CommentCount'] ?></span>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </section>
            </section>

        </main>
    </div>

</body>

</html>