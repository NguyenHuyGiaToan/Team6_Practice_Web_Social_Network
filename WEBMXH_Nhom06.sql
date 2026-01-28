-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 28, 2026 at 02:38 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `webmxh_nhom06`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `CommentID` int NOT NULL AUTO_INCREMENT,
  `FK_PostID` int NOT NULL,
  `FK_UserID` int NOT NULL,
  `Content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `Status` enum('active','pending','deleted') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CommentID`),
  KEY `FK_PostID` (`FK_PostID`),
  KEY `FK_UserID` (`FK_UserID`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`CommentID`, `FK_PostID`, `FK_UserID`, `Content`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 3, 3, 'haha', 'active', '2026-01-08 11:56:24', '2026-01-08 11:56:24'),
(2, 11, 28, 'wow! sao tôi lại buồn', 'active', '2026-01-08 18:07:16', '2026-01-08 18:07:16'),
(3, 17, 28, 'Test bình luận', 'active', '2026-01-08 18:43:46', '2026-01-08 18:43:46'),
(4, 19, 28, 'a', 'active', '2026-01-09 11:17:59', '2026-01-09 11:17:59'),
(5, 19, 28, 'b', 'active', '2026-01-09 11:18:09', '2026-01-09 11:18:09'),
(6, 20, 30, 'Hello', 'active', '2026-01-09 18:05:36', '2026-01-09 18:05:36'),
(7, 20, 30, 'How are you?', 'active', '2026-01-09 18:05:52', '2026-01-09 18:05:52'),
(8, 20, 30, 'I am fine thank you and you', 'active', '2026-01-09 18:06:02', '2026-01-09 18:06:02'),
(9, 27, 28, 'Chào Ôn nhé!', 'active', '2026-01-10 18:55:00', '2026-01-10 18:55:00'),
(10, 28, 30, 'Chào be', 'active', '2026-01-10 19:07:46', '2026-01-10 19:07:46'),
(11, 28, 30, 'xin chào', 'active', '2026-01-10 20:22:47', '2026-01-10 20:22:47'),
(12, 29, 28, 'Khùng!', 'active', '2026-01-11 09:38:19', '2026-01-11 09:38:19'),
(13, 36, 30, 'thấy r chị', 'active', '2026-01-11 10:42:37', '2026-01-11 10:42:37'),
(14, 34, 31, 'chào anh ÔN, em Tùng nè', 'active', '2026-01-11 10:45:20', '2026-01-11 10:45:20'),
(15, 37, 30, 'Chào Tùng, rất vui được khi kết bạn với em nhé!', 'active', '2026-01-11 10:49:05', '2026-01-11 10:49:05'),
(16, 34, 30, 'haha', 'active', '2026-01-11 12:49:16', '2026-01-11 12:49:16'),
(17, 27, 30, 'Chào!', 'active', '2026-01-11 18:41:53', '2026-01-11 18:41:53'),
(18, 40, 33, 'nuôi con đi', 'active', '2026-01-12 06:18:49', '2026-01-12 06:18:49'),
(19, 40, 3, '5 triệu', 'active', '2026-01-12 07:37:01', '2026-01-12 07:37:01'),
(20, 41, 3, 'aaa', 'active', '2026-01-12 07:44:00', '2026-01-12 07:44:00'),
(21, 41, 3, 'qq', 'active', '2026-01-12 07:54:36', '2026-01-12 07:54:36'),
(22, 41, 3, '2', 'active', '2026-01-12 07:54:45', '2026-01-12 07:54:45'),
(23, 42, 3, 'ádasdasdasd', 'active', '2026-01-12 08:07:14', '2026-01-12 08:07:14'),
(24, 42, 3, 'likrrr', 'active', '2026-01-12 08:07:54', '2026-01-12 08:07:54'),
(25, 42, 3, 'fsjlfsjdlf', 'active', '2026-01-12 08:09:11', '2026-01-12 08:09:11'),
(26, 43, 33, 'đẹp đôi', 'active', '2026-01-12 08:36:27', '2026-01-12 08:36:27'),
(27, 27, 33, 'hi', 'active', '2026-01-12 08:38:11', '2026-01-12 08:38:11'),
(28, 44, 33, 'tại sao', 'active', '2026-01-12 08:38:36', '2026-01-12 08:38:36'),
(29, 44, 33, 'kkk', 'active', '2026-01-12 08:38:39', '2026-01-12 08:38:39'),
(30, 27, 28, 'haha', 'active', '2026-01-12 14:21:01', '2026-01-12 14:21:01');

--
-- Triggers `comments`
--
DROP TRIGGER IF EXISTS `tg_Comments_Delete`;
DELIMITER $$
CREATE TRIGGER `tg_Comments_Delete` AFTER DELETE ON `comments` FOR EACH ROW BEGIN
    UPDATE Posts SET CommentCount = CommentCount - 1 WHERE PostID = OLD.FK_PostID;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `tg_Comments_Insert`;
DELIMITER $$
CREATE TRIGGER `tg_Comments_Insert` AFTER INSERT ON `comments` FOR EACH ROW BEGIN
    UPDATE Posts SET CommentCount = CommentCount + 1 WHERE PostID = NEW.FK_PostID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

DROP TABLE IF EXISTS `follows`;
CREATE TABLE IF NOT EXISTS `follows` (
  `FK_FollowerID` int NOT NULL,
  `FK_FollowingID` int NOT NULL,
  `FollowedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` enum('pending','accepted') COLLATE utf8mb4_unicode_ci DEFAULT 'accepted',
  PRIMARY KEY (`FK_FollowerID`,`FK_FollowingID`),
  KEY `FK_FollowingID` (`FK_FollowingID`)
) ;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`FK_FollowerID`, `FK_FollowingID`, `FollowedAt`, `Status`) VALUES
(30, 13, '2026-01-10 18:49:16', 'pending'),
(30, 3, '2026-01-10 18:49:54', 'pending'),
(28, 30, '2026-01-10 18:50:25', 'accepted'),
(30, 28, '2026-01-11 08:24:57', 'accepted'),
(28, 3, '2026-01-10 18:56:06', 'pending'),
(30, 1, '2026-01-10 19:36:50', 'pending'),
(30, 25, '2026-01-10 19:46:21', 'pending'),
(30, 17, '2026-01-10 19:46:36', 'pending'),
(30, 8, '2026-01-10 20:23:07', 'pending'),
(30, 4, '2026-01-11 08:21:57', 'pending'),
(30, 27, '2026-01-11 08:24:36', 'pending'),
(30, 15, '2026-01-11 08:27:49', 'pending'),
(30, 26, '2026-01-11 10:36:38', 'pending'),
(31, 30, '2026-01-11 10:44:35', 'accepted'),
(30, 31, '2026-01-11 10:48:07', 'accepted'),
(31, 3, '2026-01-11 11:03:02', 'pending'),
(30, 21, '2026-01-11 17:28:27', 'pending'),
(30, 14, '2026-01-11 18:13:48', 'pending'),
(33, 30, '2026-01-11 19:15:42', 'accepted'),
(30, 33, '2026-01-12 06:17:43', 'accepted'),
(3, 30, '2026-01-12 06:47:15', 'accepted'),
(30, 18, '2026-01-12 06:57:20', 'pending'),
(3, 7, '2026-01-12 07:30:26', 'pending'),
(3, 8, '2026-01-12 07:30:34', 'pending'),
(3, 32, '2026-01-12 07:31:18', 'pending'),
(3, 33, '2026-01-12 07:32:01', 'accepted'),
(33, 28, '2026-01-12 08:33:33', 'accepted'),
(33, 16, '2026-01-12 08:44:23', 'pending'),
(33, 31, '2026-01-12 08:45:55', 'pending'),
(33, 21, '2026-01-12 08:47:19', 'pending'),
(33, 27, '2026-01-12 08:47:20', 'pending'),
(33, 19, '2026-01-12 08:47:21', 'pending'),
(33, 14, '2026-01-12 08:47:23', 'pending'),
(33, 15, '2026-01-12 08:54:01', 'pending'),
(33, 12, '2026-01-12 08:54:13', 'pending'),
(33, 8, '2026-01-12 08:54:34', 'pending'),
(33, 1, '2026-01-12 08:56:42', 'accepted'),
(28, 26, '2026-01-12 14:21:20', 'pending'),
(34, 1, '2026-01-12 16:50:54', 'pending'),
(33, 34, '2026-01-12 16:59:08', 'accepted');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

DROP TABLE IF EXISTS `likes`;
CREATE TABLE IF NOT EXISTS `likes` (
  `FK_UserID` int NOT NULL,
  `FK_PostID` int NOT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`FK_UserID`,`FK_PostID`),
  UNIQUE KEY `unique_user_post_like` (`FK_UserID`,`FK_PostID`),
  KEY `FK_PostID` (`FK_PostID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`FK_UserID`, `FK_PostID`, `CreatedAt`) VALUES
(3, 40, '2026-01-12 07:36:49'),
(3, 37, '2026-01-12 07:37:14'),
(3, 36, '2026-01-12 07:37:16'),
(3, 2, '2026-01-12 07:37:17'),
(3, 3, '2026-01-12 07:37:18'),
(3, 7, '2026-01-12 07:37:20'),
(3, 41, '2026-01-12 07:55:42'),
(3, 42, '2026-01-12 08:08:22'),
(33, 43, '2026-01-12 08:36:20'),
(33, 27, '2026-01-12 08:37:39'),
(33, 44, '2026-01-12 08:39:21'),
(33, 20, '2026-01-12 08:53:37'),
(33, 45, '2026-01-12 08:59:08'),
(28, 27, '2026-01-12 14:20:50'),
(28, 3, '2026-01-12 14:21:09');

--
-- Triggers `likes`
--
DROP TRIGGER IF EXISTS `tg_Likes_Delete`;
DELIMITER $$
CREATE TRIGGER `tg_Likes_Delete` AFTER DELETE ON `likes` FOR EACH ROW BEGIN
    UPDATE Posts SET LikeCount = LikeCount - 1 WHERE PostID = OLD.FK_PostID;
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `tg_Likes_Insert`;
DELIMITER $$
CREATE TRIGGER `tg_Likes_Insert` AFTER INSERT ON `likes` FOR EACH ROW BEGIN
    UPDATE Posts SET LikeCount = LikeCount + 1 WHERE PostID = NEW.FK_PostID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `NotificationID` int NOT NULL AUTO_INCREMENT,
  `FK_UserID` int NOT NULL,
  `ActorID` int DEFAULT NULL,
  `Type` enum('Like','Comment','Follow','AcceptFollow','DeclineFollow','Unfollow') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ReferenceID` int DEFAULT NULL,
  `Message` text COLLATE utf8mb4_unicode_ci,
  `IsRead` tinyint(1) DEFAULT '0',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`NotificationID`),
  KEY `FK_UserID` (`FK_UserID`)
) ENGINE=MyISAM AUTO_INCREMENT=76 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`NotificationID`, `FK_UserID`, `ActorID`, `Type`, `ReferenceID`, `Message`, `IsRead`, `CreatedAt`) VALUES
(1, 28, 30, 'Like', 27, '', 1, '2026-01-11 18:41:48'),
(2, 28, 30, 'Comment', 27, '', 1, '2026-01-11 18:41:53'),
(3, 30, 31, 'Like', 36, '', 1, '2026-01-11 18:48:45'),
(4, 30, 33, 'Follow', NULL, '', 1, '2026-01-11 18:59:42'),
(5, 33, 30, 'Follow', NULL, '', 1, '2026-01-11 19:01:36'),
(6, 30, 33, 'Unfollow', NULL, '', 1, '2026-01-11 19:15:21'),
(7, 30, 33, 'Follow', NULL, '', 1, '2026-01-11 19:15:42'),
(8, 33, 30, 'AcceptFollow', NULL, '', 1, '2026-01-11 19:17:37'),
(9, 30, 33, 'Like', 36, '', 1, '2026-01-11 19:19:58'),
(10, 8, 30, 'Like', 37, '', 0, '2026-01-11 20:04:47'),
(11, 8, 30, 'Like', 37, '', 0, '2026-01-11 20:04:59'),
(12, 8, 30, 'Like', 37, '', 0, '2026-01-11 20:08:31'),
(13, 8, 30, 'Like', 37, '', 0, '2026-01-11 20:08:48'),
(14, 8, 30, 'Like', 37, '', 0, '2026-01-11 20:17:29'),
(15, 8, 30, 'Like', 37, '', 0, '2026-01-11 20:17:52'),
(16, 8, 30, 'Like', 37, '', 0, '2026-01-11 20:25:28'),
(17, 8, 30, 'Like', 37, '', 0, '2026-01-11 20:25:29'),
(18, 8, 30, 'Like', 37, '', 0, '2026-01-11 20:25:51'),
(19, 33, 30, 'Unfollow', NULL, '', 1, '2026-01-12 06:17:35'),
(20, 33, 30, 'Follow', NULL, '', 1, '2026-01-12 06:17:43'),
(21, 33, 30, 'Like', 40, '', 1, '2026-01-12 06:34:09'),
(22, 30, 3, 'Follow', NULL, '', 1, '2026-01-12 06:47:15'),
(23, 3, 30, 'AcceptFollow', NULL, '', 1, '2026-01-12 06:49:46'),
(24, 3, 30, 'Like', 3, '', 1, '2026-01-12 06:51:35'),
(25, 18, 30, 'Follow', NULL, '', 0, '2026-01-12 06:57:20'),
(26, 7, 3, 'Follow', NULL, '', 0, '2026-01-12 07:01:55'),
(27, 7, 3, 'Unfollow', NULL, '', 0, '2026-01-12 07:03:18'),
(28, 7, 3, 'Follow', NULL, '', 0, '2026-01-12 07:30:26'),
(29, 8, 3, 'Follow', NULL, '', 0, '2026-01-12 07:30:34'),
(30, 32, 3, 'Follow', NULL, '', 0, '2026-01-12 07:31:18'),
(31, 8, 3, 'Like', 37, '', 0, '2026-01-12 07:31:33'),
(32, 8, 3, 'Like', 37, '', 0, '2026-01-12 07:31:36'),
(33, 8, 3, 'Like', 37, '', 0, '2026-01-12 07:31:40'),
(34, 33, 3, 'Follow', NULL, '', 1, '2026-01-12 07:32:01'),
(35, 33, 3, 'Like', 40, '', 1, '2026-01-12 07:36:49'),
(36, 33, 3, 'Comment', 40, '', 1, '2026-01-12 07:37:01'),
(37, 8, 3, 'Like', 37, '', 0, '2026-01-12 07:37:14'),
(38, 30, 3, 'Like', 36, '', 0, '2026-01-12 07:37:16'),
(39, 7, 3, 'Like', 2, '', 0, '2026-01-12 07:37:17'),
(40, 8, 3, 'Like', 7, '', 0, '2026-01-12 07:37:20'),
(41, 28, 33, 'Follow', NULL, '', 1, '2026-01-12 08:33:33'),
(42, 28, 33, 'Like', 27, '', 1, '2026-01-12 08:37:39'),
(43, 28, 33, 'Comment', 27, '', 1, '2026-01-12 08:38:11'),
(44, 16, 33, 'Follow', NULL, '', 0, '2026-01-12 08:44:23'),
(45, 31, 33, 'Follow', NULL, '', 0, '2026-01-12 08:45:55'),
(46, 21, 33, 'Follow', NULL, '', 0, '2026-01-12 08:47:19'),
(47, 27, 33, 'Follow', NULL, '', 0, '2026-01-12 08:47:20'),
(48, 19, 33, 'Follow', NULL, '', 0, '2026-01-12 08:47:21'),
(49, 14, 33, 'Follow', NULL, '', 0, '2026-01-12 08:47:23'),
(50, 3, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:31'),
(51, 3, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:32'),
(52, 3, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:33'),
(53, 3, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:33'),
(54, 30, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:34'),
(55, 30, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:34'),
(56, 30, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:34'),
(57, 30, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:34'),
(58, 3, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:35'),
(59, 3, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:35'),
(60, 3, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:36'),
(61, 3, 33, 'AcceptFollow', NULL, '', 0, '2026-01-12 08:47:36'),
(62, 21, 33, 'Like', 20, '', 0, '2026-01-12 08:53:37'),
(63, 15, 33, 'Follow', NULL, '', 0, '2026-01-12 08:54:01'),
(64, 12, 33, 'Follow', NULL, '', 0, '2026-01-12 08:54:13'),
(65, 8, 33, 'Follow', NULL, '', 0, '2026-01-12 08:54:34'),
(66, 1, 33, 'Follow', NULL, '', 1, '2026-01-12 08:56:42'),
(67, 33, 1, 'AcceptFollow', NULL, '', 1, '2026-01-12 08:56:57'),
(68, 33, 1, 'AcceptFollow', NULL, '', 1, '2026-01-12 08:56:57'),
(69, 1, 33, 'Like', 45, '', 1, '2026-01-12 08:59:08'),
(70, 3, 28, 'Like', 3, '', 0, '2026-01-12 14:21:09'),
(71, 26, 28, 'Follow', NULL, '', 0, '2026-01-12 14:21:20'),
(72, 1, 34, 'Follow', NULL, '', 0, '2026-01-12 16:50:54'),
(73, 34, 33, 'Follow', NULL, '', 1, '2026-01-12 16:59:08'),
(74, 33, 34, 'AcceptFollow', NULL, '', 1, '2026-01-12 16:59:25'),
(75, 33, 28, 'AcceptFollow', NULL, '', 1, '2026-01-12 17:06:09');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE IF NOT EXISTS `posts` (
  `PostID` int NOT NULL AUTO_INCREMENT,
  `FK_UserID` int NOT NULL,
  `Content` text COLLATE utf8mb4_unicode_ci,
  `Status` enum('active','deleted','hidden') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `LikeCount` int DEFAULT '0',
  `CommentCount` int DEFAULT '0',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`PostID`),
  KEY `FK_UserID` (`FK_UserID`)
) ENGINE=MyISAM AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`PostID`, `FK_UserID`, `Content`, `Status`, `LikeCount`, `CommentCount`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 1, 'Hôm nay trời Sài Gòn đẹp quá các bạn ơi! ☀️ Ai đi cà phê không?', 'active', 0, 12, '2026-01-08 03:30:00', '2026-01-12 07:36:08'),
(2, 7, 'Vừa hoàn thành đồ án môn Lập trình Web giữa kỳ, mệt nhưng vui vì nhóm làm ăn ý lắm! 💻🎉', 'active', 1, 25, '2026-01-07 15:15:00', '2026-01-12 07:37:17'),
(3, 3, 'Ai học HUB Ngân Hàng biết quán ăn ngon quận 7 giới thiệu mình với! Đói quá rồi 😭', 'active', 2, 44, '2026-01-07 05:20:00', '2026-01-12 14:21:09'),
(4, 4, 'Chúc mừng năm mới 2026 mọi người! Mong một năm mới thật nhiều sức khỏe, thành công và bình an 🥂✨', 'active', 0, 68, '2025-12-31 17:05:00', '2026-01-12 07:36:08'),
(5, 5, 'Cuối tuần này có ai đi xem phim không? Mình đang muốn xem bộ mới ra mắt lắm 🎬🍿', 'active', 0, 19, '2026-01-05 11:45:00', '2026-01-12 07:36:08'),
(6, 30, 'Học online mãi cũng chán, mong sớm được quay lại trường gặp bạn bè quá huhu 😢', 'deleted', 0, 35, '2025-12-28 07:10:00', '2026-01-12 07:36:08'),
(7, 8, 'Share cho mọi người một mẹo học tập: hãy dùng Pomodoro 25 phút học + 5 phút nghỉ, hiệu quả lắm đấy! ⏰', 'active', 1, 28, '2026-01-06 02:00:00', '2026-01-12 07:37:20'),
(8, 9, 'Vừa mua được chiếc tai nghe mới, âm thanh đỉnh cao luôn! Ai thích nghe nhạc thì recommend nhé 🎧🔥', 'active', 0, 17, '2026-01-04 13:00:00', '2026-01-12 07:36:08'),
(9, 10, 'Cảnh báo: Cẩn thận lừa đảo giả mạo ngân hàng qua tin nhắn nhé các bạn! Đừng click link lạ ⚠️', 'active', 0, 31, '2026-01-03 04:35:00', '2026-01-12 07:36:08'),
(10, 11, 'Tối nay trời mát, mình đang đi dạo công viên Lê Văn Tám. Không khí đầu năm thích thật sự 🌳🌙', 'active', 0, 14, '2026-01-08 12:20:00', '2026-01-12 07:36:08'),
(11, 12, 'Hôm nay tôi buồn giữa phố đông!', 'active', 0, 2, '2026-01-08 18:06:57', '2026-01-10 12:21:54'),
(12, 13, '\n\n[Activity: 😞 đang cảm thấy Buồn]', '', 0, 0, '2026-01-08 18:22:17', '2026-01-10 12:21:54'),
(13, 14, 'Vui quá!\n\n[Activity: 🤩 đang cảm thấy Hào hứng]', '', 0, 0, '2026-01-08 18:22:37', '2026-01-10 12:21:54'),
(14, 15, 'Ủa!', '', 0, 0, '2026-01-08 18:23:04', '2026-01-10 12:21:54'),
(15, 16, '', '', 0, 0, '2026-01-08 18:24:45', '2026-01-10 12:21:54'),
(16, 17, '', '', 0, 0, '2026-01-08 18:26:04', '2026-01-10 12:21:54'),
(17, 18, '\n\n[Activity: 🤩 đang cảm thấy Hào hứng]', 'active', 0, 2, '2026-01-08 18:42:10', '2026-01-10 12:21:54'),
(18, 19, 'Test tính năng!', 'active', 0, 0, '2026-01-08 18:43:10', '2026-01-10 12:21:54'),
(19, 20, 'Happy New Year!!!', 'active', 0, 4, '2026-01-09 11:17:44', '2026-01-10 12:21:54'),
(20, 21, 'Tôi đang kiểm tra website này hoạt động như thế nào:>mn cho tôi 1 like nhe, vui vẻ!!!', 'active', 1, 111, '2026-01-09 18:05:13', '2026-01-12 08:53:37'),
(21, 22, '\n\n[Activity: 🤩 đang cảm thấy Hào hứng]', 'active', 0, 0, '2026-01-09 19:23:58', '2026-01-10 12:21:54'),
(22, 23, '', 'active', 0, 0, '2026-01-10 12:49:23', '2026-01-10 20:13:16'),
(23, 24, '', 'active', 0, 0, '2026-01-10 12:51:02', '2026-01-10 20:13:16'),
(24, 25, '', 'active', 0, 0, '2026-01-10 12:51:11', '2026-01-12 07:36:08'),
(25, 26, '', 'active', 0, 0, '2026-01-10 12:51:15', '2026-01-10 20:13:16'),
(26, 27, '', 'active', 0, 0, '2026-01-10 12:56:40', '2026-01-10 20:13:16'),
(27, 28, 'Test', 'active', 2, 4, '2026-01-10 13:03:34', '2026-01-12 14:21:01'),
(28, 31, 'Xin chào cả nhà!!!', 'hidden', 0, 4, '2026-01-10 19:07:24', '2026-01-12 07:36:08'),
(29, 32, '123456789', 'active', 0, 2, '2026-01-11 09:33:46', '2026-01-12 07:38:50'),
(42, 11, 'tôi chả nghĩ gì cả', 'deleted', 1, 3, '2026-01-12 08:07:09', '2026-01-12 08:21:20'),
(34, 7, 'công khai nè, haha', 'active', 0, 4, '2026-01-11 10:39:48', '2026-01-12 07:38:50'),
(31, 33, '', 'deleted', 0, 0, '2026-01-11 10:03:28', '2026-01-12 07:38:50'),
(33, 1, 'riêng tư nhe!', 'deleted', 0, 0, '2026-01-11 10:38:34', '2026-01-12 07:38:50'),
(35, 3, 'Buồn nhỉ :(\n\n[Activity: 😞 đang cảm thấy Buồn]', '', 0, 0, '2026-01-11 10:41:31', '2026-01-12 07:38:50'),
(36, 4, 'Thấy gì không nào.', 'active', 1, 2, '2026-01-11 10:41:59', '2026-01-12 07:38:50'),
(37, 5, 'Tùng mới tạo nick mới, mn follow nhé!', 'active', 1, 2, '2026-01-11 10:47:40', '2026-01-12 07:38:50'),
(38, 30, 'Quê Hương', 'deleted', 0, 0, '2026-01-11 13:42:34', '2026-01-12 07:38:50'),
(39, 8, 'Tôi tiếp tục test tính năng', 'active', 0, 0, '2026-01-11 17:33:50', '2026-01-12 07:38:50'),
(41, 10, 'aaa', 'deleted', 1, 6, '2026-01-12 07:43:54', '2026-01-12 08:21:26'),
(40, 9, '5 triệu', 'active', 1, 4, '2026-01-12 06:18:18', '2026-01-12 07:38:50'),
(43, 12, '', 'active', 1, 1, '2026-01-12 08:32:24', '2026-01-12 09:01:38'),
(44, 13, 'tôi thích jack', 'active', 1, 2, '2026-01-12 08:38:29', '2026-01-12 09:01:38'),
(45, 14, 'gsdgs\n\n[Activity: 🙂 đang cảm thấy Hạnh phúc]', 'hidden', 1, 0, '2026-01-12 08:57:48', '2026-01-12 09:02:14');

-- --------------------------------------------------------

--
-- Table structure for table `post_images`
--

DROP TABLE IF EXISTS `post_images`;
CREATE TABLE IF NOT EXISTS `post_images` (
  `ImageID` int NOT NULL AUTO_INCREMENT,
  `FK_PostID` int NOT NULL,
  `ImageUrl` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`ImageID`),
  KEY `FK_PostID` (`FK_PostID`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_images`
--

INSERT INTO `post_images` (`ImageID`, `FK_PostID`, `ImageUrl`) VALUES
(1, 11, '695ff24150f3f.jpg'),
(2, 15, '695ff66d66e0a.jpg'),
(3, 16, '695ff6bca9635.jpg'),
(4, 20, '6961435924680.jpg'),
(5, 27, '69624e264f5c3.jpg'),
(6, 30, '696371814ba4c.jpg'),
(7, 32, '696379a3a88b8.jpg'),
(8, 33, '69637daa9d755.jpg'),
(9, 37, '69637fcc72491.jpg'),
(11, 34, '1768135970_canhque.jpg'),
(12, 38, '6963a8cadf0d6.jpg'),
(13, 39, '6963defe56d08.jpg'),
(17, 40, '6964922a8a726.jpg'),
(15, 39, '1768153027_canhque.jpg'),
(18, 40, '1768199053_5tr.jpg'),
(19, 43, '6964b1985cf52.jpg'),
(20, 43, '1768206754_5tr.jpg'),
(24, 45, '6964b78ce1471.jpg'),
(23, 43, '1768206791_quehuong.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
CREATE TABLE IF NOT EXISTS `reports` (
  `ReportID` int NOT NULL AUTO_INCREMENT,
  `FK_PostID` int DEFAULT NULL,
  `FK_CommentID` int DEFAULT NULL,
  `FK_ReporterID` int NOT NULL,
  `Reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Status` enum('pending','resolved','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `ReportedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReportID`),
  KEY `FK_PostID` (`FK_PostID`),
  KEY `FK_CommentID` (`FK_CommentID`),
  KEY `FK_ReporterID` (`FK_ReporterID`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`ReportID`, `FK_PostID`, `FK_CommentID`, `FK_ReporterID`, `Reason`, `Status`, `ReportedAt`) VALUES
(1, 28, NULL, 30, 'Nội dung chưa chuẩn mực.', 'rejected', '2026-01-11 07:55:13'),
(2, 20, NULL, 1, 'Không thích người dùng này.', 'rejected', '2026-01-11 07:56:10'),
(3, 27, NULL, 30, 'fake_news', 'pending', '2026-01-11 09:15:19'),
(4, 29, NULL, 28, 'spam', 'pending', '2026-01-11 09:37:55'),
(5, 37, NULL, 30, 'fake_news', 'pending', '2026-01-11 17:38:00'),
(6, 40, NULL, 3, 'harassment', 'pending', '2026-01-12 07:37:28'),
(7, 45, NULL, 33, 'spam', 'resolved', '2026-01-12 08:59:57');

-- --------------------------------------------------------

--
-- Table structure for table `saved_posts`
--

DROP TABLE IF EXISTS `saved_posts`;
CREATE TABLE IF NOT EXISTS `saved_posts` (
  `FK_UserID` int NOT NULL,
  `FK_PostID` int NOT NULL,
  `SavedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`FK_UserID`,`FK_PostID`),
  KEY `FK_PostID` (`FK_PostID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `saved_posts`
--

INSERT INTO `saved_posts` (`FK_UserID`, `FK_PostID`, `SavedAt`) VALUES
(30, 20, '2026-01-10 12:45:06'),
(28, 3, '2026-01-11 10:39:18'),
(30, 34, '2026-01-11 12:52:08'),
(33, 40, '2026-01-12 06:19:02'),
(1, 45, '2026-01-12 08:59:39');

-- --------------------------------------------------------

--
-- Table structure for table `system_stats`
--

DROP TABLE IF EXISTS `system_stats`;
CREATE TABLE IF NOT EXISTS `system_stats` (
  `StatDate` date NOT NULL,
  `TotalVisits` int DEFAULT '0',
  `NewSignups` int DEFAULT '0',
  PRIMARY KEY (`StatDate`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `UserID` int NOT NULL AUTO_INCREMENT,
  `FullName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Phone` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PasswordHash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Gender` enum('Nam','Nu','Khac') COLLATE utf8mb4_unicode_ci DEFAULT 'Khac',
  `BirthDate` date DEFAULT NULL,
  `Avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default_avatar.png',
  `Address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `CoverImage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Bio` text COLLATE utf8mb4_unicode_ci,
  `Role` enum('user','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `Status` enum('active','locked') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `LastLogin` timestamp NULL DEFAULT NULL,
  `ResetToken` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ResetTokenExpiry` datetime DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `Phone` (`Phone`)
) ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `FullName`, `Email`, `Phone`, `PasswordHash`, `Gender`, `BirthDate`, `Avatar`, `Address`, `CoverImage`, `Bio`, `Role`, `Status`, `CreatedAt`, `LastLogin`, `ResetToken`, `ResetTokenExpiry`) VALUES
(1, 'Nguyễn Văn Toàn', 'nvt215@gmail.com', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nam', '1999-07-06', 'default_avatar.png', 'TP Hồ Chí Minh', NULL, NULL, 'user', 'active', '2026-01-06 14:40:16', '2026-01-12 09:05:01', 'e1cd466154e0cc80e30dffe6ef4485e47ffcd8af06f9385c8a19cf9904077264', '2026-01-12 16:18:43'),
(7, 'Trần Thị B', NULL, '0347555921', '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nu', '1978-01-01', 'default_avatar.png', 'TP Hồ Chí Minh', NULL, NULL, 'user', 'active', '2026-01-08 09:28:41', NULL, NULL, NULL),
(3, 'Đoàn Lê Duy Long', 'dld333@gmail.com', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nam', '2000-12-03', 'avatar_3_1767864171.jpg', 'TP Hồ Chí Minh', 'cover_3_1767864176.jpg', 'Cuộc đời là vô thường', 'user', 'active', '2026-01-07 11:13:18', '2026-01-12 08:06:22', NULL, NULL),
(4, 'Nguyễn Văn Admin', 'admin1@example.com', '0901234567', '$2y$10$7P3FJMstDHpz4io5/1Rtr.yFU.kuZ4q640Q/UK8oLkth80FGGXH/2', 'Nam', '1995-05-10', 'default_avatar.png', 'TP Hồ Chí Minh', NULL, 'Quản trị viên hệ thống', 'admin', 'active', '2026-01-07 11:48:43', '2026-01-12 09:00:46', NULL, NULL),
(5, 'Trần Thị Quản Trị', 'admin2@example.com', '0912345678', '$2y$10$eHjNYB5AN9Upv4D8v9xlt.ATzjqmKSXBFeauvnqXes5hmt6Q.5haK', 'Nu', '1996-08-20', 'default_avatar.png', 'Đồng Tháp', NULL, 'Admin phụ trách nội dung', 'admin', 'active', '2026-01-07 11:48:43', NULL, NULL, NULL),
(30, 'Lưu Bá Ôn', 'lbo123@gmai.com', NULL, '$2y$10$AaFS6ii8Rw6mmjYiI0p/IuuK.mFRNUVz09Uj7TnnlxDgeboTnFfg2', 'Nam', '1999-02-02', 'avatar_30_1767982023.jpg', 'TP Hồ Chí Minh', 'cover_30_1768153459.jpg', 'Xin chào cả nhà nhé. Tôi là Ôn đây!', 'user', 'active', '2026-01-09 18:02:41', '2026-01-12 06:49:34', NULL, NULL),
(8, 'Nguyễn Huy Gia Toàn', 'toan.nguyen@hcmut.edu.vn', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nam', '2003-05-15', '', 'Đà Nẵng', '', 'Sinh viên năm 3 ngành HTTTQL - HUB ❤️', 'user', 'active', '2025-09-01 03:30:00', '2026-01-08 11:00:00', NULL, NULL),
(9, 'Trần Thị Mỹ Linh', 'linh.tran@gmail.com', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nu', '2004-02-20', '', 'Cần Thơ', NULL, 'Yêu màu hồng, thích trà sữa và chụp ảnh 🌸', 'user', 'active', '2025-09-15 07:20:00', '2026-01-08 10:30:00', NULL, NULL),
(10, 'Lê Văn Duy', 'duy.le@hcmbank.edu.vn', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nam', '2003-11-10', '', 'Cà Mau', '', 'Code là đam mê, cà phê là nhiên liệu ☕💻', 'admin', 'locked', '2025-08-20 02:00:00', '2026-01-08 09:45:00', NULL, NULL),
(11, 'Phạm Minh Anh', 'anh.pham@yahoo.com', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nu', '2004-08-05', NULL, 'Quảng Ngãi', NULL, 'Thích đọc sách và du lịch một mình 📚✈️', 'user', 'locked', '2025-10-01 04:15:00', '2026-01-07 13:00:00', NULL, NULL),
(12, 'Huỳnh Quốc Bảo', 'bao.huynh@gmail.com', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nam', '2003-07-22', '', 'Nghệ An', NULL, 'Bóng đá là cuộc sống ⚽️', 'user', 'active', '2025-09-20 09:40:00', '2026-01-08 08:20:00', NULL, NULL),
(13, 'Võ Thanh Ngọc', 'ngoc.vo@hcmut.edu.vn', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nu', '2004-03-18', NULL, 'Thanh Hóa', '', 'Design lover | Figma enthusiast 🎨', 'user', 'active', '2025-09-10 06:50:00', '2026-01-08 07:10:00', NULL, NULL),
(14, 'Đặng Hoàng Long', 'long.dang@gmail.com', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nam', '2003-12-01', '', 'TP Hồ Chí Minh', NULL, 'Học để thay đổi tương lai 🚀', 'user', 'active', '2025-10-05 01:30:00', NULL, NULL, NULL),
(15, 'Bùi Thị Kim Ngân', 'ngan.bui@yahoo.com', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nu', '2004-06-14', NULL, 'TP Hồ Chí Minh', '', 'Ăn uống là niềm vui lớn nhất 🍜🥤', 'user', 'active', '2025-09-25 12:00:00', '2026-01-06 14:30:00', NULL, NULL),
(16, 'Ngô Văn Khánh', 'khanh.ngo@hcmbank.edu.vn', '0967890123', '$2y$10$9Tfg4bQibuoN5fT4vKgYleoWXZLGFh93DHUKFbk8Elg4BIv5mHiVO', 'Nam', '2003-09-30', '', 'TP Hồ Chí Minh', NULL, 'Game thủ chính hiệu 🎮', 'user', 'active', '2025-09-05 05:00:00', '2026-01-08 03:00:00', NULL, NULL),
(17, 'Hà Minh Tú', 'tu.ha@gmail.com', NULL, '$2y$10$ScP9JUBna/31BW9f0cC8Ru3oM/fpqga7HKrxjVjIPnRLgOadD0M2W', 'Nu', '2004-01-25', '', 'TP Hồ Chí Minh', NULL, 'Thích nghe nhạc lofi khi học 🌙', 'user', 'active', '2025-10-10 08:45:00', '2026-01-07 16:15:00', NULL, NULL),
(18, 'Đỗ Quang Vinh', 'vinh.do@gmail.com', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nam', '2003-04-12', NULL, NULL, NULL, 'Đang tìm việc part-time', 'user', 'active', '2025-11-01 03:10:00', NULL, NULL, NULL),
(19, 'Lý Thị Hồng', 'hong.ly@yahoo.com', '0989012345', '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nu', '2004-10-08', '', NULL, '', 'Mèo là chân ái 🐱', 'user', 'active', '2025-09-30 10:20:00', '2026-01-08 12:00:00', NULL, NULL),
(20, 'Trương Văn Hùng', 'hung.truong@hcmut.edu.vn', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nam', '2003-03-17', '', NULL, NULL, 'Chuyên gia ngủ nướng 😴', 'user', 'locked', '2025-10-15 04:11:00', '2025-12-20 01:00:00', NULL, NULL),
(21, 'Mai Anh Thư', 'thu.mai@gmail.com', '0990123456', '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nu', '2004-05-29', NULL, NULL, NULL, 'Thích vẽ và sáng tạo ✏️', 'user', 'active', '2025-09-12 07:30:00', '2026-01-08 05:45:00', NULL, NULL),
(22, 'Cao Văn Nam', NULL, '0902345678', '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nam', '2003-08-03', '', NULL, '', 'Fitness & Gym 💪', 'user', 'active', '2025-09-08 02:45:00', '2026-01-08 00:30:00', NULL, NULL),
(23, 'Dương Thị Lan', 'lan.duong@yahoo.com', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nu', '2004-11-11', '', NULL, NULL, 'Yêu thiên nhiên và cây cối 🌿', 'user', 'active', '2025-10-20 06:20:00', NULL, NULL, NULL),
(24, 'Phan Văn Tài', NULL, '0913456789', '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nam', '2003-06-06', NULL, NULL, NULL, 'Đang học PHP và MySQL', 'user', 'active', '2025-09-18 09:00:00', '2026-01-07 15:00:00', NULL, NULL),
(25, 'Tô Ngọc Ánh', NULL, '0924567890', '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nu', '2004-09-09', '', NULL, '', 'Thích xem phim Hàn 🇰🇷', 'user', 'active', '2025-09-22 13:15:00', '2026-01-08 13:30:00', NULL, NULL),
(26, 'Kiều Văn Minh', 'minh.kieu@gmail.com', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nam', '2003-02-28', '', NULL, NULL, 'Coffee addict ☕', 'user', 'active', '2025-10-08 03:50:00', '2026-01-08 02:00:00', NULL, NULL),
(27, 'Chu Thị Diễm', 'diem.chu@yahoo.com', NULL, '$2y$10$dvom5K2N7eGxO7C5hYCGduePq3X2PBPVzO4jWsNQxnsh3cGNLjvpK', 'Nu', '2004-07-19', NULL, NULL, NULL, 'Sống chậm và tận hưởng từng khoảnh khắc 🌼', 'user', 'active', '2025-09-28 11:30:00', '2026-01-06 12:45:00', NULL, NULL),
(28, 'Trần Thị B', 'tthb@gmail.com', NULL, '$2y$10$c.k.KBTWuY0b6jn4hLC18uSuHLgCJ1nBUvQOOlfsDPJp9iBW/8GoS', 'Nu', '1988-01-01', 'avatar_28_1767875516.jpg', 'Cao Bằng', 'cover_28_1767968847.jpg', NULL, 'user', 'active', '2026-01-08 12:01:47', '2026-01-12 17:13:56', '84813e4802bb2de796915b0c1c38aa5c8629658e22599ea0d08d9ebf14c556ad', '2026-01-12 21:41:47'),
(31, 'Sơn Tùng MTP', 'mtp@gmail.com', NULL, '$2y$10$HPfmwxymufxp7IjhixMFS.0E6WOVGng8frCcKvoAlMTzmh6celtUe', 'Nam', '1996-12-10', 'avatar_31_1768128905.jpg', 'An Giang', 'cover_31_1768128988.jpg', NULL, 'user', 'active', '2026-01-11 10:44:17', '2026-01-11 18:48:42', NULL, NULL),
(32, 'Trần Tâm', NULL, '0377856621', '$2y$10$hE.omTmvpLDFEd8hk3ynK.gHIC4y.QyffC5f1HGA.voTeM0NwiRi2', 'Nam', '1988-02-12', 'default_avatar.png', NULL, NULL, NULL, 'user', 'active', '2026-01-11 18:12:33', NULL, NULL, NULL),
(33, 'Trịnh Trần Phương Tuấn', 'jack@5gmail.com', NULL, '$2y$10$uZkciOY012v6sMvYAABpyOOFRvov0L79mRNhgen2eSNh5tOqPdLrW', 'Nam', '2005-05-05', 'avatar_33_1768157509.jpg', 'Bến Tre', NULL, 'Tôi là J97, Five milions', 'user', 'active', '2026-01-11 18:50:24', '2026-01-12 16:58:59', NULL, NULL),
(34, 'Gia Toàn', 'toan91toto@gmail.com', NULL, '$2y$10$RJHC2mp60M8LSTOc3TN5wu0oz0xdcy7BJDc.iWIIvRQvppZKwdeM6', 'Nam', '2005-10-28', 'default_avatar.png', NULL, NULL, NULL, 'user', 'active', '2026-01-12 14:39:55', '2026-01-12 16:50:49', NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
