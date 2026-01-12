-- 1. Bảng users (Bảng chứa thông tin người dùng)
CREATE TABLE `users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `FullName` varchar(100) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Phone` varchar(15) DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Gender` enum('Nam','Nu','Khac') DEFAULT 'Khac',
  `BirthDate` date DEFAULT NULL,
  `Avatar` varchar(255) DEFAULT 'default_avatar.png',
  `Address` varchar(255) DEFAULT NULL,
  `CoverImage` varchar(255) DEFAULT NULL,
  `Bio` text DEFAULT NULL,
  `Role` enum('user','admin') DEFAULT 'user',
  `Status` enum('active','locked') DEFAULT 'active',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `LastLogin` timestamp NULL DEFAULT NULL,
  `ResetToken` varchar(64) DEFAULT NULL,
  `ResetTokenExpiry` datetime DEFAULT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng posts (Bài viết)
CREATE TABLE `posts` (
  `PostID` int(11) NOT NULL AUTO_INCREMENT,
  `FK_UserID` int(11) NOT NULL,
  `Content` text DEFAULT NULL,
  `Status` enum('active','deleted','hidden') DEFAULT 'active',
  `LikeCount` int(11) DEFAULT 0,
  `CommentCount` int(11) DEFAULT 0,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`PostID`),
  KEY `FK_UserID` (`FK_UserID`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`FK_UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bảng comments (Bình luận)
CREATE TABLE `comments` (
  `CommentID` int(11) NOT NULL AUTO_INCREMENT,
  `FK_PostID` int(11) NOT NULL,
  `FK_UserID` int(11) NOT NULL,
  `Content` text NOT NULL,
  `Status` enum('active','pending','deleted') DEFAULT 'active',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CommentID`),
  KEY `FK_PostID` (`FK_PostID`),
  KEY `FK_UserID` (`FK_UserID`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`FK_PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`FK_UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bảng likes (Lượt thích - Bảng trung gian Many-to-Many)
CREATE TABLE `likes` (
  `FK_UserID` int(11) NOT NULL,
  `FK_PostID` int(11) NOT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`FK_UserID`,`FK_PostID`),
  UNIQUE KEY `unique_user_post_like` (`FK_UserID`,`FK_PostID`),
  KEY `FK_PostID` (`FK_PostID`),
  CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`FK_UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`FK_PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Bảng follows (Theo dõi người dùng)
CREATE TABLE `follows` (
  `FK_FollowerID` int(11) NOT NULL,
  `FK_FollowingID` int(11) NOT NULL,
  `FollowedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `Status` enum('pending','accepted') DEFAULT 'accepted',
  PRIMARY KEY (`FK_FollowerID`,`FK_FollowingID`),
  KEY `FK_FollowingID` (`FK_FollowingID`),
  CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`FK_FollowerID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`FK_FollowingID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng post_images (Hình ảnh của bài viết)
CREATE TABLE `post_images` (
  `ImageID` int(11) NOT NULL AUTO_INCREMENT,
  `FK_PostID` int(11) NOT NULL,
  `ImageUrl` varchar(255) NOT NULL,
  PRIMARY KEY (`ImageID`),
  KEY `FK_PostID` (`FK_PostID`),
  CONSTRAINT `post_images_ibfk_1` FOREIGN KEY (`FK_PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Bảng saved_posts (Bài viết đã lưu)
CREATE TABLE `saved_posts` (
  `FK_UserID` int(11) NOT NULL,
  `FK_PostID` int(11) NOT NULL,
  `SavedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`FK_UserID`,`FK_PostID`),
  KEY `FK_PostID` (`FK_PostID`),
  CONSTRAINT `saved_posts_ibfk_1` FOREIGN KEY (`FK_UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  CONSTRAINT `saved_posts_ibfk_2` FOREIGN KEY (`FK_PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Bảng notifications (Thông báo)
CREATE TABLE `notifications` (
  `NotificationID` int(11) NOT NULL AUTO_INCREMENT,
  `FK_UserID` int(11) NOT NULL,
  `ActorID` int(11) DEFAULT NULL,
  `Type` enum('Like','Comment','Follow','AcceptFollow') NOT NULL,
  `ReferenceID` int(11) DEFAULT NULL,
  `Message` text DEFAULT NULL,
  `IsRead` tinyint(1) DEFAULT 0,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`NotificationID`),
  KEY `FK_UserID` (`FK_UserID`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`FK_UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Bảng reports (Báo cáo vi phạm)
CREATE TABLE `reports` (
  `ReportID` int(11) NOT NULL AUTO_INCREMENT,
  `FK_PostID` int(11) DEFAULT NULL,
  `FK_CommentID` int(11) DEFAULT NULL,
  `FK_ReporterID` int(11) NOT NULL,
  `Reason` varchar(255) DEFAULT NULL,
  `Status` enum('pending','resolved','rejected') DEFAULT 'pending',
  `ReportedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReportID`),
  KEY `FK_PostID` (`FK_PostID`),
  KEY `FK_CommentID` (`FK_CommentID`),
  KEY `FK_ReporterID` (`FK_ReporterID`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`FK_PostID`) REFERENCES `posts` (`PostID`) ON DELETE SET NULL,
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`FK_CommentID`) REFERENCES `comments` (`CommentID`) ON DELETE SET NULL,
  CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`FK_ReporterID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bảng system_stats (Thống kê hệ thống)
CREATE TABLE `system_stats` (
  `StatDate` date NOT NULL,
  `TotalVisits` int(11) DEFAULT 0,
  `NewSignups` int(11) DEFAULT 0,
  PRIMARY KEY (`StatDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;