-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 07, 2025 at 08:45 AM
-- Server version: 5.7.39
-- PHP Version: 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `btikp_kalsel`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cleanup_old_data` ()   BEGIN
    -- Hapus page views lebih dari 1 tahun
    DELETE FROM page_views WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
    
    -- Hapus activity logs lebih dari 6 bulan
    DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
    
    -- Hapus password reset tokens kadaluarsa
    DELETE FROM password_resets WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_dashboard_stats` ()   BEGIN
    SELECT 
        (SELECT COUNT(*) FROM schools WHERE deleted_at IS NULL) AS total_schools,
        (SELECT SUM(student_count) FROM schools WHERE deleted_at IS NULL) AS total_students,
        (SELECT SUM(teacher_count) FROM schools WHERE deleted_at IS NULL) AS total_teachers,
        (SELECT COUNT(*) FROM posts WHERE status = 'published' AND deleted_at IS NULL) AS total_posts,
        (SELECT COUNT(*) FROM services WHERE is_active = TRUE AND deleted_at IS NULL) AS total_services,
        (SELECT COUNT(*) FROM downloadable_files WHERE is_active = TRUE AND deleted_at IS NULL) AS total_files;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_type` enum('LOGIN','LOGOUT','CREATE','UPDATE','DELETE','VIEW','DOWNLOAD','SYNC') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama tabel yang dipengaruhi',
  `model_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID record yang dipengaruhi',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `user_name`, `action_type`, `description`, `model_type`, `model_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, NULL, 'Super Administrator', 'LOGIN', 'User berhasil login', NULL, NULL, '::1', NULL, '2025-11-03 23:51:12'),
(2, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-03 23:52:22'),
(3, 1, NULL, 'Super Administrator', 'LOGIN', 'User berhasil login', NULL, NULL, '::1', NULL, '2025-11-04 00:06:05'),
(4, 1, NULL, 'Test User', 'CREATE', 'Menambah post: test1', 'posts', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 01:41:48'),
(5, 1, NULL, 'Test User', 'UPDATE', 'Mengupdate post: test1', 'posts', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 01:42:31'),
(6, 1, NULL, 'Test User', 'UPDATE', 'Mengupdate post: test1', 'posts', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 01:42:41'),
(7, 1, NULL, 'Test User', 'DELETE', 'Menghapus post: test1', 'posts', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 01:42:51'),
(8, 1, NULL, 'Test User', 'CREATE', 'Menambah post: test 2', 'posts', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 02:32:56'),
(9, 1, NULL, 'Test User', 'CREATE', 'Menambah post: test3', 'posts', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 02:43:27'),
(10, 1, NULL, 'Test User', 'UPDATE', 'Mengupdate post: test3', 'posts', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 02:43:55'),
(11, 1, NULL, 'Super Administrator', 'LOGIN', 'User berhasil login', NULL, NULL, '::1', NULL, '2025-11-04 17:56:57'),
(12, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 17:57:26'),
(13, 1, NULL, 'Super Administrator', 'LOGIN', 'User berhasil login', NULL, NULL, '::1', NULL, '2025-11-04 17:57:30'),
(14, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 18:10:19'),
(15, 1, NULL, 'Super Administrator', 'LOGIN', 'User berhasil login', NULL, NULL, '::1', NULL, '2025-11-04 18:10:23'),
(16, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test3', 'posts', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 18:20:35'),
(17, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate kategori: Berita', 'post_categories', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 18:27:31'),
(18, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate kategori: Berita', 'post_categories', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 18:40:01'),
(19, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah kategori: test kategori', 'post_categories', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 18:40:19'),
(20, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus kategori: test kategori', 'post_categories', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 18:44:05'),
(21, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test3', 'posts', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 18:45:20'),
(22, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test3', 'posts', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 18:45:32'),
(23, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (16 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:03:52'),
(24, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (16 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:04:00'),
(25, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (17 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:04:41'),
(26, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (17 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:24:27'),
(27, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:40:43'),
(28, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:41:40'),
(29, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 19:42:07'),
(30, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:22:37'),
(31, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test3', 'posts', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:24:34'),
(32, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test 2', 'posts', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:32:24'),
(33, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test 2', 'posts', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:32:33'),
(34, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:33:06'),
(35, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:33:14'),
(36, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (20 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:33:29'),
(37, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (20 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:33:49'),
(38, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:33:59'),
(39, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:34:05'),
(40, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:34:17'),
(41, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (20 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:34:25'),
(42, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test3', 'posts', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 20:34:40'),
(43, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah post: test 4', 'posts', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:21:27'),
(44, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate pengguna: Super Administrator', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:26:00'),
(45, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:26:54'),
(46, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 21:27:05'),
(47, NULL, NULL, 'admin1', 'LOGIN', 'User login ke sistem', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:27:09'),
(48, NULL, NULL, 'admin1', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 21:27:34'),
(49, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:27:37'),
(50, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate pengguna: admin1', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:27:50'),
(51, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 21:27:54'),
(52, NULL, NULL, 'admin1', 'LOGIN', 'User login ke sistem', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:27:59'),
(53, NULL, NULL, 'admin1', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 21:28:15'),
(54, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:28:19'),
(55, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate pengguna: admin1', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:28:28'),
(56, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 21:28:31'),
(57, NULL, NULL, 'admin1', 'LOGIN', 'User login ke sistem', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:28:35'),
(58, NULL, NULL, 'admin1', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 21:28:44'),
(59, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:28:47'),
(60, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:28:51'),
(61, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 21:35:35'),
(62, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:35:38'),
(63, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate pengguna: Super Administrator', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:36:26'),
(64, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 21:36:32'),
(65, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:36:35'),
(66, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate pengguna: Super Administrator', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:36:47'),
(67, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 21:37:59'),
(68, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:38:01'),
(69, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate pengguna: Super Administrator', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:48:18'),
(70, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate pengguna: Super Administrator', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:49:26'),
(71, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 21:57:46'),
(72, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:57:51'),
(73, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:58:17'),
(74, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate pengguna: admin1', 'users', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:58:27'),
(75, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 21:58:36'),
(76, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:07:12'),
(77, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:07:16'),
(78, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:09:39'),
(79, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:09:41'),
(80, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:15:37'),
(81, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:16:17'),
(82, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:16:20'),
(83, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:36:20'),
(84, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:36:22'),
(85, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:38:26'),
(86, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:38:37'),
(87, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:42:30'),
(88, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:42:32'),
(89, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:50:37'),
(90, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:50:45'),
(91, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:54:58'),
(92, NULL, NULL, 'admin1', 'LOGIN', 'User login ke sistem', 'users', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:55:09'),
(93, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 22:55:16'),
(94, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:55:20'),
(95, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 21, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:55:27'),
(96, NULL, NULL, 'admin1', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-04 22:55:32'),
(97, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 22, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 22:57:21'),
(98, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:13:43'),
(99, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate pengguna: admin1', 'users', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:13:48'),
(100, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 23, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:13:52'),
(101, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:14:16'),
(102, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate pengguna: admin1', 'users', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:14:42'),
(103, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:14:44'),
(104, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:25:29'),
(105, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus pengguna: admin1', 'users', 25, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:25:35'),
(106, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate pengguna: Super Administrator', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:26:18'),
(107, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus layanan: Konsultasi IT', 'services', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-04 23:48:07'),
(108, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate layanan: Pengembangan Website', 'services', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 00:12:13'),
(109, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate layanan: Pengembangan Website', 'services', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 00:17:31'),
(110, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate layanan: Pengembangan Website', 'services', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:01:42'),
(111, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:07:20'),
(112, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah layanan: test layanan 2', 'services', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:08:17'),
(113, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate layanan: test layanan 2', 'services', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:09:12'),
(114, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate layanan: test layanan 2', 'services', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:09:49'),
(115, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate layanan: test layanan 2', 'services', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:09:54'),
(116, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus layanan: test layanan 2', 'services', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:10:55'),
(117, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus layanan: Pengembangan Website', 'services', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:17:01'),
(118, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-05 01:17:43'),
(119, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:18:16'),
(120, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah kategori: Perhatian', 'post_categories', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:19:39'),
(121, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah tag: test', 'tags', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:32:17'),
(122, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah tag: test2', 'tags', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:32:28'),
(123, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus tag: test2', 'tags', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:32:40'),
(124, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate tag: test ed', 'tags', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:32:53'),
(125, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate tag: test ed', 'tags', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:33:05'),
(126, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus tag: test ed', 'tags', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:33:08'),
(127, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah kategori: test', 'post_categories', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 02:34:31'),
(128, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 13:21:11'),
(129, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-05 13:32:40'),
(130, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 13:32:43'),
(131, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-05 13:33:24'),
(132, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 13:33:26'),
(133, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate tag: teknologi x', 'tags', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 13:43:13'),
(134, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah tag: test', 'tags', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 13:43:21'),
(135, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus tag: test', 'tags', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 13:43:25'),
(136, 1, NULL, 'Super Administrator', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-05 14:22:31'),
(137, 1, NULL, 'Super Administrator', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 14:22:35'),
(138, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate tag: teknologi', 'tags', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 14:23:24'),
(139, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate kategori: Perhatian x', 'post_categories', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 14:34:10'),
(140, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus kategori: test', 'post_categories', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 14:34:45'),
(141, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate kategori: test1', 'post_categories', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 14:35:21'),
(142, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate kategori: test1', 'post_categories', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 14:35:26'),
(143, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus kategori: test1', 'post_categories', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 14:35:29'),
(144, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah tag: testtt', 'tags', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 14:36:24'),
(145, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate tag: testttxx', 'tags', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 14:42:11'),
(146, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah post: test pakai tag', 'posts', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 14:43:39'),
(147, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 15:00:24'),
(148, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test 4', 'posts', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 15:05:53'),
(149, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test 2', 'posts', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 15:08:21'),
(150, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test pakai tag', 'posts', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 15:09:43'),
(151, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test 4', 'posts', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 15:11:20'),
(152, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test 4', 'posts', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 15:11:43'),
(153, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test3', 'posts', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 15:11:58'),
(154, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test pakai tag', 'posts', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 15:32:52'),
(155, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah post: test 3', 'posts', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 18:39:20'),
(156, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus post: test 3', 'posts', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 18:40:13'),
(157, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus post: test pakai tag', 'posts', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 18:40:17'),
(158, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus post: test 4', 'posts', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 18:40:20'),
(159, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus post: test3', 'posts', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 18:40:24'),
(160, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test 2', 'posts', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 18:40:35'),
(161, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test 2', 'posts', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 18:41:52'),
(162, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah post: test4', 'posts', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 18:55:03'),
(163, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus kategori: Perhatian x', 'post_categories', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 18:57:32'),
(164, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus kategori: Perhatian x', 'post_categories', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 18:57:38'),
(165, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah post: test 5', 'posts', 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 18:58:44'),
(166, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah post: test6', 'posts', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:01:48'),
(167, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah post: test 7', 'posts', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:17:41'),
(168, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah tag: test3', 'tags', 31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:24:20'),
(169, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate tag: test3 edit', 'tags', 31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:24:37'),
(170, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus tag: test3 edit', 'tags', 31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:24:41'),
(171, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test 7', 'posts', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:25:49'),
(172, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus post: test 7', 'posts', 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:25:58'),
(173, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus post: test6', 'posts', 10, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:28:29'),
(174, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah tag: test5', 'tags', 32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:29:02'),
(175, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate tag: test4', 'tags', 32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:29:08'),
(176, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus tag: test4', 'tags', 32, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:29:12'),
(177, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus kategori: Perhatian x', 'post_categories', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:33:31'),
(178, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus kategori: Perhatian x', 'post_categories', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:34:16'),
(179, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate kategori: test1', 'post_categories', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:34:22'),
(180, 1, NULL, 'Super Administrator', 'DELETE', 'Menghapus kategori: test1', 'post_categories', 7, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:34:31'),
(181, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah kategori: test4', 'post_categories', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:34:39'),
(182, 1, NULL, 'Super Administrator', 'UPDATE', 'Mengupdate post: test4', 'posts', 8, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 19:37:24'),
(183, 1, NULL, 'Lycoris', 'UPDATE', 'Mengupdate profil', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 21:49:43'),
(184, 1, NULL, 'Lycoris', 'CREATE', 'Menambah tag: test 3', 'tags', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 22:13:34'),
(185, 1, NULL, 'Lycoris', 'UPDATE', 'Mengupdate tag: test 3 x', 'tags', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 22:13:39'),
(186, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus tag: test 3 x', 'tags', 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 22:13:42'),
(187, 1, NULL, 'Lycoris', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 23:00:49'),
(188, 1, NULL, 'Lycoris', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 23:24:43'),
(189, 1, NULL, 'Lycoris', 'DELETE', 'Cleanup activity logs (0 records older than 30 days)', 'activity_logs', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 23:29:51'),
(190, 1, NULL, 'Lycoris', 'CREATE', 'Menambah post: test 5', 'posts', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 23:36:08'),
(191, 1, NULL, 'Lycoris', 'UPDATE', 'Mengupdate profil', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 23:37:00'),
(192, 1, NULL, 'Lycoris', 'UPDATE', 'Mengupdate profil', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 23:37:15'),
(193, 1, NULL, 'Lycoris', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 00:33:09'),
(194, 1, NULL, 'Lycoris', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-06 00:33:13'),
(195, NULL, NULL, 'admin1', 'LOGIN', 'User login ke sistem', 'users', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 00:33:17'),
(196, NULL, NULL, 'admin1', 'UPDATE', 'Mengupdate pengguna: admin1', 'users', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 00:34:25'),
(197, NULL, NULL, 'admin1', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-06 00:37:29'),
(198, 1, NULL, 'Lycoris', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 00:43:34'),
(199, 1, NULL, 'Lycoris', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 02:49:45'),
(200, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus album gallery: Pelatihan Guru 2024', 'gallery_albums', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:22:13'),
(201, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus album gallery: Workshop TIK', 'gallery_albums', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:22:17'),
(202, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus album gallery: Kunjungan Sekolah', 'gallery_albums', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:22:21'),
(203, 1, NULL, 'Lycoris', 'CREATE', 'Membuat album gallery: test album', 'gallery_albums', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:22:47'),
(204, 1, NULL, 'Lycoris', 'CREATE', 'Upload 2 foto ke album: test album', 'gallery_photos', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:23:15'),
(205, 1, NULL, 'Lycoris', 'UPDATE', 'Reorder foto di album ID: 4', 'gallery_photos', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:31:09'),
(206, 1, NULL, 'Lycoris', 'UPDATE', 'Reorder foto di album ID: 4', 'gallery_photos', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:31:26'),
(207, 1, NULL, 'Lycoris', 'UPDATE', 'Reorder foto di album ID: 4', 'gallery_photos', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:31:29'),
(208, 1, NULL, 'Lycoris', 'UPDATE', 'Reorder foto di album ID: 4', 'gallery_photos', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:31:53'),
(209, 1, NULL, 'Lycoris', 'CREATE', 'Upload 1 foto ke album: test album', 'gallery_photos', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:32:23'),
(210, 1, NULL, 'Lycoris', 'UPDATE', 'Reorder foto di album ID: 4', 'gallery_photos', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:32:26'),
(211, 1, NULL, 'Lycoris', 'UPDATE', 'Reorder foto di album ID: 4', 'gallery_photos', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:32:28'),
(212, 1, NULL, 'Lycoris', 'UPDATE', 'Mengupdate album gallery: test album edit', 'gallery_albums', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:33:25'),
(213, 1, NULL, 'Lycoris', 'UPDATE', 'Mengupdate album gallery: test album edit', 'gallery_albums', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 03:33:42'),
(214, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus pesan dari: John Doe (john@example.com)', 'contact_messages', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 04:35:08'),
(215, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus pesan dari: xsdcdsc (csdcdsc@gmail.com)', 'contact_messages', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 04:54:39'),
(216, 1, NULL, 'Lycoris', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 05:16:59'),
(217, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus layanan: Pengembangan Website', 'services', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 05:41:59'),
(218, 1, NULL, 'Lycoris', 'CREATE', 'Menambah post: test6', 'posts', 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 10:27:30'),
(219, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus post: test 2', 'posts', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 10:27:59'),
(220, 1, NULL, 'Lycoris', 'UPDATE', 'Reorder foto di album ID: 4', 'gallery_photos', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 12:29:49'),
(221, 1, NULL, 'Lycoris', 'CREATE', 'Membuat album gallery: test', 'gallery_albums', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 20:34:03'),
(222, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus album gallery: test', 'gallery_albums', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 20:34:27'),
(223, 1, NULL, 'Lycoris', 'UPDATE', 'Mengupdate album gallery: test album edit', 'gallery_albums', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 20:40:06'),
(224, 1, NULL, 'Lycoris', 'UPDATE', 'Mengupdate album gallery: test album edit', 'gallery_albums', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 20:40:51');
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `user_name`, `action_type`, `description`, `model_type`, `model_id`, `ip_address`, `user_agent`, `created_at`) VALUES
(225, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus pesan dari: Jane Smith (jane@example.com)', 'contact_messages', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 20:47:05'),
(226, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus pesan dari: test (test@gmail.com)', 'contact_messages', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 20:51:28'),
(227, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus pesan dari: cscd (cdscd@gmail.com)', 'contact_messages', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:04:24'),
(228, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus pesan dari: cdscdsc (vdfvdfv)', 'contact_messages', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:15:40'),
(229, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus pesan dari: cdscdsc (vdfvdfv)', 'contact_messages', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:15:55'),
(230, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus post: test 5', 'posts', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:16:33'),
(231, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus kategori: Artikel', 'post_categories', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:19:00'),
(232, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus layanan: Pengembangan Website', 'services', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:20:05'),
(233, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus album gallery: test album edit', 'gallery_albums', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:20:12'),
(234, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus post: test 5', 'posts', 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:20:28'),
(235, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus pengguna: admin1', 'users', 26, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:36:57'),
(236, 1, NULL, 'Lycoris', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:46:13'),
(237, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus pengguna: admin1', 'users', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:46:19'),
(238, 1, NULL, 'Lycoris', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 28, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:46:52'),
(239, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus pengguna: admin1', 'users', 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:53:13'),
(240, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus pengguna: admin1', 'users', 28, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:53:28'),
(241, 1, NULL, 'Lycoris', 'CREATE', 'Menambah pengguna baru: admin1', 'users', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 21:53:48'),
(242, 1, NULL, 'Lycoris', 'UPDATE', 'Mengubah halaman: testing', 'pages', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:22:08'),
(243, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus halaman: testing', 'pages', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:29:09'),
(244, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus halaman: test halaman', 'pages', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:30:49'),
(245, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus halaman (soft delete): test halaman', 'pages', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:33:50'),
(246, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus halaman (soft delete): test halaman', 'pages', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:33:58'),
(247, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus halaman (soft delete): test halaman', 'pages', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:38:03'),
(248, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus halaman (soft delete): test halaman', 'pages', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:39:55'),
(249, 1, NULL, 'Lycoris', 'DELETE', 'Menghapus halaman (soft delete): test halaman', 'pages', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:43:15'),
(250, 1, NULL, 'Lycoris', 'DELETE', 'Soft delete halaman: test halaman', 'pages', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:45:36'),
(251, 1, NULL, 'Lycoris', 'DELETE', 'Soft delete halaman: test halaman', 'pages', 3, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:45:42'),
(252, 1, NULL, 'Lycoris', 'DELETE', 'Soft delete halaman: test halaman', 'pages', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:46:23'),
(253, 1, NULL, 'Lycoris', 'DELETE', 'Soft delete halaman: test halaman', 'pages', 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:47:13'),
(254, 1, NULL, 'Lycoris', 'UPDATE', 'Mengubah halaman: test halaman lagi edit', 'pages', 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-06 23:52:17'),
(255, 1, NULL, 'Lycorismeeee', 'UPDATE', 'Mengupdate profil', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 00:01:43'),
(256, 1, NULL, 'Lycorismeeee', 'DELETE', 'Menghapus layanan: Pengembangan Website', 'services', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 00:45:28'),
(257, 1, NULL, 'Lycorismeeee', 'UPDATE', 'Mengubah layanan: test layanan', 'services', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 01:30:33'),
(258, 1, NULL, 'Lycorismeeee', 'DELETE', 'Soft delete layanan: test layanan', 'services', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 01:30:43'),
(259, 1, NULL, 'Lycorismeeee', 'DELETE', 'Soft delete layanan: test layanan', 'services', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 01:58:14'),
(260, 1, NULL, 'Lycorismeeee', 'DELETE', 'Soft delete layanan: test layanan', 'services', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 02:10:30'),
(261, 1, NULL, 'Lycorismeeee', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 02:43:20'),
(262, 1, NULL, 'Lycorismeeee', 'UPDATE', 'Mengupdate settings website (19 items)', 'settings', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 02:43:33'),
(263, 1, NULL, 'Lycorismeeee', 'CREATE', 'Upload 1 foto ke album: test album edit', 'gallery_photos', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 03:41:27'),
(264, 29, NULL, 'admin1', 'LOGIN', 'User login ke sistem', 'users', 29, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 04:58:12'),
(265, 29, NULL, 'admin1', 'LOGOUT', 'User melakukan logout', NULL, NULL, '::1', NULL, '2025-11-07 04:58:18'),
(266, 1, NULL, 'Lycorismeeee', 'LOGIN', 'User login ke sistem', 'users', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-07 04:58:20');

--
-- Triggers `activity_logs`
--
DELIMITER $$
CREATE TRIGGER `after_download_log` AFTER INSERT ON `activity_logs` FOR EACH ROW BEGIN
    IF NEW.action_type = 'DOWNLOAD' AND NEW.model_type = 'downloadable_files' THEN
        UPDATE downloadable_files 
        SET download_count = download_count + 1 
        WHERE id = NEW.model_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `agendas`
--

CREATE TABLE `agendas` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `location` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organizer` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_integrations`
--

CREATE TABLE `api_integrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'dapodik, siap, simpkb, dll',
  `endpoint_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Encrypted',
  `api_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Encrypted',
  `sync_frequency` enum('manual','daily','weekly','monthly') COLLATE utf8mb4_unicode_ci DEFAULT 'manual',
  `is_active` tinyint(1) DEFAULT '1',
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `next_sync_at` timestamp NULL DEFAULT NULL,
  `config` json DEFAULT NULL COMMENT 'Konfigurasi tambahan spesifik per API',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `link_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `button_text` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` int(10) UNSIGNED DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `ordering` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `title`, `image_path`, `link_url`, `description`, `button_text`, `position`, `is_active`, `start_date`, `end_date`, `created_at`, `updated_at`, `deleted_at`, `ordering`, `caption`) VALUES
(4, 'test', 'uploads/banners/1762466543-kalimantanselatan__1_.png', 'https://github.com/zuramai/mazer?tab=readme-ov-file', NULL, NULL, 0, 1, NULL, NULL, '2025-11-06 22:02:23', '2025-11-06 22:02:23', NULL, 1, 'test banner caption');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `order` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `parent_id`, `order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Berita', 'berita', 'Berita terkini seputar organisasi', NULL, 1, '2025-11-05 01:31:24', '2025-11-05 01:31:24', NULL),
(2, 'Kegiatan', 'kegiatan', 'Dokumentasi kegiatan organisasi', NULL, 2, '2025-11-05 01:31:24', '2025-11-05 01:31:24', NULL),
(3, 'Pengumuman', 'pengumuman', 'Pengumuman resmi', NULL, 3, '2025-11-05 01:31:24', '2025-11-05 01:31:24', NULL),
(4, 'Artikel', 'artikel', 'Artikel dan tulisan edukatif', NULL, 4, '2025-11-05 01:31:24', '2025-11-05 01:31:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(10) UNSIGNED NOT NULL,
  `commentable_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'post, service, page',
  `commentable_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Untuk reply/nested comments',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected','spam') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `replied_at` timestamp NULL DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `downloadable_files`
--

CREATE TABLE `downloadable_files` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'pdf, docx, pptx, xlsx, zip, dll',
  `file_size` bigint(20) UNSIGNED NOT NULL COMMENT 'Ukuran file dalam bytes',
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `thumbnail_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `download_count` int(10) UNSIGNED DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `downloadable_files`
--

INSERT INTO `downloadable_files` (`id`, `title`, `description`, `file_path`, `file_type`, `file_size`, `mime_type`, `category_id`, `thumbnail_path`, `download_count`, `is_active`, `uploaded_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(8, 'test1', 'csdccdscd', 'uploads/files/1762434090-133960034_p3_master1200.jpg', 'jpg', 1003907, 'image/jpeg', NULL, NULL, 0, 1, 1, '2025-11-06 13:01:30', '2025-11-06 20:16:54', NULL),
(9, 'test', 'cscdc', 'uploads/files/1762437390-6N-UTS.pdf', 'pdf', 95828, 'application/pdf', NULL, NULL, 0, 1, 1, '2025-11-06 13:56:30', '2025-11-06 21:21:13', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `file_categories`
--

CREATE TABLE `file_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int(10) UNSIGNED DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `file_categories`
--

INSERT INTO `file_categories` (`id`, `name`, `slug`, `parent_id`, `description`, `icon`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Modul Pembelajaran', 'modul-pembelajaran', NULL, 'Modul dan bahan ajar', 'fa-book', 1, 1, '2025-11-03 23:05:42', '2025-11-03 23:05:42'),
(2, 'Media Pembelajaran', 'media-pembelajaran', NULL, 'Video, audio, dan media interaktif', 'fa-photo-video', 2, 1, '2025-11-03 23:05:42', '2025-11-03 23:05:42'),
(3, 'Panduan & SOP', 'panduan-sop', NULL, 'Panduan teknis dan SOP', 'fa-file-pdf', 3, 1, '2025-11-03 23:05:42', '2025-11-03 23:05:42'),
(4, 'Formulir', 'formulir', NULL, 'Template formulir dan dokumen', 'fa-file-invoice', 4, 1, '2025-11-03 23:05:42', '2025-11-03 23:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `galleries`
--

CREATE TABLE `galleries` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('photo','video') COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path untuk foto',
  `video_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL YouTube/Vimeo untuk video',
  `video_embed_code` text COLLATE utf8mb4_unicode_ci COMMENT 'Embed code untuk video',
  `description` text COLLATE utf8mb4_unicode_ci,
  `album` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nama album untuk grouping',
  `display_order` int(10) UNSIGNED DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gallery_albums`
--

CREATE TABLE `gallery_albums` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `cover_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Path to cover image',
  `photo_count` int(10) UNSIGNED DEFAULT '0' COMMENT 'Cache count photos',
  `display_order` int(10) UNSIGNED DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery_albums`
--

INSERT INTO `gallery_albums` (`id`, `name`, `slug`, `description`, `cover_photo`, `photo_count`, `display_order`, `is_active`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 'test album edit', 'test-album-edit', 'testinggg brooooo', 'gallery/albums/cover_1762400022_690c1716734eb.JPG', 4, 6, 1, 1, '2025-11-06 03:22:47', '2025-11-07 03:41:27', NULL),
(5, 'test', 'test', '', 'gallery/albums/cover_1762461243_690d063bc6164.JPEG', 0, 1, 1, 1, '2025-11-06 20:34:03', '2025-11-06 20:34:27', '2025-11-06 20:34:27');

-- --------------------------------------------------------

--
-- Table structure for table `gallery_photos`
--

CREATE TABLE `gallery_photos` (
  `id` int(10) UNSIGNED NOT NULL,
  `album_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Path to photo',
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Auto-generated thumbnail',
  `caption` text COLLATE utf8mb4_unicode_ci,
  `file_size` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'Size in bytes',
  `width` int(10) UNSIGNED DEFAULT NULL,
  `height` int(10) UNSIGNED DEFAULT NULL,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `taken_at` date DEFAULT NULL COMMENT 'Photo taken date',
  `display_order` int(10) UNSIGNED DEFAULT '0',
  `view_count` int(10) UNSIGNED DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery_photos`
--

INSERT INTO `gallery_photos` (`id`, `album_id`, `title`, `description`, `filename`, `thumbnail`, `caption`, `file_size`, `width`, `height`, `uploaded_by`, `taken_at`, `display_order`, `view_count`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 4, NULL, NULL, 'gallery/photos/photo_1762399394_690c14a2d4308_0.jpg', 'gallery/thumbnails/thumb_photo_1762399394_690c14a2d4308_0.jpg', NULL, 1003907, 1200, 675, 1, NULL, 0, 0, '2025-11-06 03:23:15', '2025-11-06 21:32:24', NULL),
(2, 4, NULL, NULL, 'gallery/photos/photo_1762399395_690c14a323493_1.jpg', 'gallery/thumbnails/thumb_photo_1762399395_690c14a323493_1.jpg', NULL, 725744, 1200, 675, 1, NULL, 2, 0, '2025-11-06 03:23:15', '2025-11-06 21:20:12', '2025-11-06 21:20:12'),
(3, 4, NULL, NULL, 'gallery/photos/photo_1762399942_690c16c6f4142_0.png', 'gallery/thumbnails/thumb_photo_1762399942_690c16c6f4142_0.png', NULL, 1811940, 1366, 768, 1, NULL, 1, 0, '2025-11-06 03:32:23', '2025-11-06 21:20:12', '2025-11-06 21:20:12'),
(4, 4, NULL, NULL, 'gallery/photos/photo_1762486887_690d6a6765a57_0.jpg', 'gallery/thumbnails/thumb_photo_1762486887_690d6a6765a57_0.jpg', NULL, 1003907, 1200, 675, 1, NULL, 0, 0, '2025-11-07 03:41:27', '2025-11-07 03:41:27', NULL);

--
-- Triggers `gallery_photos`
--
DELIMITER $$
CREATE TRIGGER `after_photo_delete` AFTER DELETE ON `gallery_photos` FOR EACH ROW BEGIN
  UPDATE gallery_albums 
  SET photo_count = photo_count - 1 
  WHERE id = OLD.album_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_photo_insert` AFTER INSERT ON `gallery_photos` FOR EACH ROW BEGIN
  UPDATE gallery_albums 
  SET photo_count = photo_count + 1 
  WHERE id = NEW.album_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `integration_logs`
--

CREATE TABLE `integration_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `integration_id` int(10) UNSIGNED NOT NULL,
  `status` enum('success','failed','partial') COLLATE utf8mb4_unicode_ci NOT NULL,
  `records_processed` int(10) UNSIGNED DEFAULT '0',
  `records_inserted` int(10) UNSIGNED DEFAULT '0',
  `records_updated` int(10) UNSIGNED DEFAULT '0',
  `records_failed` int(10) UNSIGNED DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `levels`
--

CREATE TABLE `levels` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SD, SMP, SMA, SMK, SLB',
  `display_order` int(10) UNSIGNED DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `levels`
--

INSERT INTO `levels` (`id`, `name`, `code`, `display_order`, `created_at`) VALUES
(1, 'Sekolah Dasar', 'SD', 1, '2025-11-03 23:05:42'),
(2, 'Sekolah Menengah Pertama', 'SMP', 2, '2025-11-03 23:05:42'),
(3, 'Sekolah Menengah Atas', 'SMA', 3, '2025-11-03 23:05:42'),
(4, 'Sekolah Menengah Kejuruan', 'SMK', 4, '2025-11-03 23:05:42'),
(5, 'Sekolah Luar Biasa', 'SLB', 5, '2025-11-03 23:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(10) UNSIGNED NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` enum('image','video','document','audio','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL COMMENT 'Size in bytes',
  `width` int(10) UNSIGNED DEFAULT NULL COMMENT 'Untuk image/video',
  `height` int(10) UNSIGNED DEFAULT NULL COMMENT 'Untuk image/video',
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `uploaded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('internal','external','page','post','custom','dropdown') COLLATE utf8mb4_unicode_ci DEFAULT 'custom',
  `target_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID dari page/post jika type internal',
  `target_blank` tinyint(1) DEFAULT '0' COMMENT 'Buka di tab baru',
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` int(10) UNSIGNED DEFAULT '0',
  `menu_location` enum('header','footer','sidebar') COLLATE utf8mb4_unicode_ci DEFAULT 'header',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `content` longtext,
  `featured_image` varchar(255) DEFAULT NULL,
  `status` enum('published','draft') DEFAULT NULL,
  `seo_title` varchar(200) DEFAULT NULL,
  `seo_description` text,
  `template` varchar(50) DEFAULT 'default',
  `is_homepage` tinyint(1) DEFAULT '0',
  `display_order` int(11) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `featured_image`, `status`, `seo_title`, `seo_description`, `template`, `is_homepage`, `display_order`, `created_at`, `updated_at`, `deleted_at`) VALUES
(4, 'test halaman', 'cdscc', '<p>dsccdsc</p>', NULL, 'published', NULL, NULL, 'default', 0, 1, '2025-11-06 23:46:20', '2025-11-06 23:47:13', NULL),
(5, 'test halaman lagi edit', 'vdvfdvfvdfvdv', '<p>vdvdfv</p>', NULL, 'published', NULL, NULL, 'default', 0, 1, '2025-11-06 23:52:10', '2025-11-06 23:52:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `page_views`
--

CREATE TABLE `page_views` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `viewable_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'post, page, school, service',
  `viewable_id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `referer` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `page_views`
--
DELIMITER $$
CREATE TRIGGER `after_page_view_insert` AFTER INSERT ON `page_views` FOR EACH ROW BEGIN
    IF NEW.viewable_type = 'post' THEN
        UPDATE posts SET view_count = view_count + 1 WHERE id = NEW.viewable_id;
    ELSEIF NEW.viewable_type = 'service' THEN
        UPDATE services SET view_count = view_count + 1 WHERE id = NEW.viewable_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` text COLLATE utf8mb4_unicode_ci,
  `featured_image` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `category_id` int(10) UNSIGNED NOT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `view_count` int(10) UNSIGNED DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `status`, `category_id`, `author_id`, `view_count`, `is_featured`, `meta_title`, `meta_description`, `meta_keywords`, `published_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(8, 'test4', 'test4', '<p>csdcdcdsc</p>', '', 'posts/images_1762371444_fdb15d91.jpg', 'published', 3, 1, 0, 1, '', '', '', '2025-11-05 18:53:00', '2025-11-05 18:55:03', '2025-11-05 20:37:24', NULL),
(9, 'test 5', 'test-5', '<p>ckscmds</p>', '', 'posts/133960034_p6_master1200_1762369124_44fc8dd2.jpg', 'published', 4, 1, 2, 1, '', '', '', '2025-11-05 18:58:00', '2025-11-05 18:58:44', '2025-11-06 00:30:48', NULL),
(12, 'test 5', 'test-5-1_deleted1762463793_deleted1762464028', '<p>fdvvdfv</p>', '', 'posts/photo_6217739526839847528_y_1762385767_1d2d8948.jpg', 'archived', 2, 1, 1, 1, '', '', '', '2025-11-05 23:35:00', '2025-11-05 23:36:07', '2025-11-06 21:20:32', NULL),
(13, 'test6', 'test6', '<p>ackdslcsdcsdcsddcd</p>', '', 'posts/img_2747_1762424850_ceabdff8.JPG', 'draft', 2, 1, 0, 1, '', '', '', NULL, '2025-11-06 10:27:30', '2025-11-06 10:27:30', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `post_categories`
--

CREATE TABLE `post_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `icon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Hex color code',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_categories`
--

INSERT INTO `post_categories` (`id`, `name`, `slug`, `description`, `icon`, `color`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Berita', 'berita', 'Berita terkini seputar BTIKP', 'fa-newspaper', '#3B82F6', 1, '2025-11-03 23:05:42', '2025-11-04 19:40:00', NULL),
(3, 'Pengumuman', 'pengumuman', 'Pengumuman resmi BTIKP', 'fa-bullhorn', '#F59E0B', 1, '2025-11-03 23:05:42', '2025-11-03 23:05:42', NULL),
(4, 'Kegiatan', 'kegiatan', 'Liputan kegiatan dan acara', 'fa-calendar-check', '#8B5CF6', 1, '2025-11-03 23:05:42', '2025-11-03 23:05:42', NULL),
(8, 'test4', 'test4', '', NULL, NULL, 1, '2025-11-05 20:34:39', '2025-11-05 19:34:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `post_tags`
--

CREATE TABLE `post_tags` (
  `post_id` int(10) UNSIGNED NOT NULL,
  `tag_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `post_tags`
--

INSERT INTO `post_tags` (`post_id`, `tag_id`, `created_at`) VALUES
(8, 22, '2025-11-05 19:37:24'),
(8, 24, '2025-11-05 19:37:24'),
(9, 22, '2025-11-05 18:58:44'),
(9, 24, '2025-11-05 18:58:44'),
(12, 22, '2025-11-05 23:36:07'),
(12, 24, '2025-11-05 23:36:07'),
(13, 22, '2025-11-06 10:27:30'),
(13, 24, '2025-11-06 10:27:30');

-- --------------------------------------------------------

--
-- Table structure for table `regencies`
--

CREATE TABLE `regencies` (
  `id` char(4) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kode BPS 4 digit',
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('kabupaten','kota') COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `regencies`
--

INSERT INTO `regencies` (`id`, `name`, `type`, `latitude`, `longitude`, `created_at`) VALUES
('6301', 'Tanah Laut', 'kabupaten', NULL, NULL, '2025-11-03 23:05:42'),
('6302', 'Kotabaru', 'kabupaten', NULL, NULL, '2025-11-03 23:05:42'),
('6303', 'Banjar', 'kabupaten', NULL, NULL, '2025-11-03 23:05:42'),
('6304', 'Barito Kuala', 'kabupaten', NULL, NULL, '2025-11-03 23:05:42'),
('6305', 'Tapin', 'kabupaten', NULL, NULL, '2025-11-03 23:05:42'),
('6306', 'Hulu Sungai Selatan', 'kabupaten', NULL, NULL, '2025-11-03 23:05:42'),
('6307', 'Hulu Sungai Tengah', 'kabupaten', NULL, NULL, '2025-11-03 23:05:42'),
('6308', 'Hulu Sungai Utara', 'kabupaten', NULL, NULL, '2025-11-03 23:05:42'),
('6309', 'Tabalong', 'kabupaten', NULL, NULL, '2025-11-03 23:05:42'),
('6310', 'Tanah Bumbu', 'kabupaten', NULL, NULL, '2025-11-03 23:05:42'),
('6311', 'Balangan', 'kabupaten', NULL, NULL, '2025-11-03 23:05:42'),
('6371', 'Banjarmasin', 'kota', NULL, NULL, '2025-11-03 23:05:42'),
('6372', 'Banjarbaru', 'kota', NULL, NULL, '2025-11-03 23:05:42');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(10) UNSIGNED NOT NULL,
  `npsn` char(8) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nomor Pokok Sekolah Nasional',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Negeri','Swasta') COLLATE utf8mb4_unicode_ci NOT NULL,
  `level_id` int(10) UNSIGNED NOT NULL,
  `regency_id` char(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `headmaster_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `accreditation` enum('A','B','C','TT') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'TT = Tidak Terakreditasi',
  `student_count` int(10) UNSIGNED DEFAULT '0',
  `teacher_count` int(10) UNSIGNED DEFAULT '0',
  `data_source` enum('manual','integrasi_api','import') COLLATE utf8mb4_unicode_ci DEFAULT 'manual',
  `is_verified` tinyint(1) DEFAULT '0',
  `last_verified_at` timestamp NULL DEFAULT NULL,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_details`
--

CREATE TABLE `school_details` (
  `school_id` int(10) UNSIGNED NOT NULL,
  `profile` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Profil lengkap sekolah dari admin BTIKP',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_url` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram_url` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_url` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facilities` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array untuk daftar fasilitas',
  `achievements` text COLLATE utf8mb4_unicode_ci COMMENT 'Prestasi sekolah',
  `extracurriculars` text COLLATE utf8mb4_unicode_ci COMMENT 'JSON array untuk daftar ekstrakurikuler',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_galleries`
--

CREATE TABLE `school_galleries` (
  `id` int(10) UNSIGNED NOT NULL,
  `school_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` enum('building','facility','activity','other') COLLATE utf8mb4_unicode_ci DEFAULT 'other',
  `display_order` int(10) UNSIGNED DEFAULT '0',
  `is_featured` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `service_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('published','draft') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `title`, `slug`, `description`, `service_url`, `image_path`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, 'test layanan', 'scsdc', 'cdscdcdc', 'https://github.com/zuramai/mazer?tab=readme-ov-file', 'uploads/services/690d6e836d750.jpeg', 'published', '2025-11-07 03:58:59', '2025-11-07 03:58:59', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text,
  `type` enum('text','textarea','number','boolean','file') DEFAULT 'text',
  `group` varchar(50) DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `type`, `group`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'BTIKP Kalimantan Selatan', 'text', 'general', '2025-11-04 18:55:37', '2025-11-07 02:43:32'),
(2, 'site_tagline', 'Balai Teknologi Informasi dan Komunikasi Pendidikan', 'text', 'general', '2025-11-04 18:55:37', '2025-11-07 02:43:32'),
(3, 'site_description', 'Portal resmi BTIKP Provinsi Kalimantan Selatan', 'textarea', 'general', '2025-11-04 18:55:37', '2025-11-07 02:43:32'),
(4, 'site_keywords', 'btikp, kalsel, pendidikan, teknologi, informasi', 'text', 'general', '2025-11-04 18:55:37', '2025-11-07 02:43:32'),
(5, 'site_logo', 'settings/logo_1761875034_1762288465_0ec046e1.png', 'file', 'general', '2025-11-04 18:55:37', '2025-11-04 20:34:25'),
(6, 'site_favicon', 'settings/logo_1761875034_1762288409_65324299.png', 'file', 'general', '2025-11-04 18:55:37', '2025-11-04 20:33:29'),
(7, 'contact_phone', '(0511) 1234567', 'text', 'contact', '2025-11-04 18:55:37', '2025-11-07 02:43:33'),
(8, 'contact_email', 'haldi0230@gmail.com', 'text', 'contact', '2025-11-04 18:55:37', '2025-11-07 02:43:33'),
(9, 'contact_address', 'Jl. Pendidikan No. 123, Banjarmasin, Kalimantan Selatan', 'textarea', 'contact', '2025-11-04 18:55:37', '2025-11-07 02:43:33'),
(10, 'contact_maps_embed', '', 'textarea', 'contact', '2025-11-04 18:55:37', '2025-11-07 02:43:33'),
(11, 'social_facebook', '', 'text', 'social', '2025-11-04 18:55:37', '2025-11-07 02:43:33'),
(12, 'social_instagram', '', 'text', 'social', '2025-11-04 18:55:37', '2025-11-07 02:43:33'),
(13, 'social_youtube', '', 'text', 'social', '2025-11-04 18:55:37', '2025-11-07 02:43:33'),
(14, 'social_twitter', '', 'text', 'social', '2025-11-04 18:55:37', '2025-11-07 02:43:33'),
(15, 'upload_max_size', '5', 'number', 'upload', '2025-11-04 18:55:37', '2025-11-07 02:43:32'),
(16, 'upload_allowed_images', 'jpg,jpeg,png,gif,webp', 'text', 'upload', '2025-11-04 18:55:37', '2025-11-07 02:43:33'),
(17, 'upload_allowed_docs', 'pdf,doc,docx,xls,xlsx,ppt,pptx', 'text', 'upload', '2025-11-04 18:55:37', '2025-11-07 02:43:33'),
(18, 'items_per_page', '10', 'number', 'general', '2025-11-04 18:55:37', '2025-11-07 02:43:33'),
(19, 'posts_per_page_public', '12', 'number', 'general', '2025-11-04 18:55:37', NULL),
(22, 'site_copyright', '© {year} BTIKP Kalimantan Selatan. All Rights Reserved.', 'text', 'general', '2025-11-04 19:30:44', '2025-11-07 02:43:32'),
(23, 'site_logo_text', 'BTIKP KALSEL', 'text', 'general', '2025-11-04 19:30:44', '2025-11-07 02:43:32'),
(24, 'site_logo_show_text', '1', 'boolean', 'general', '2025-11-04 19:30:44', '2025-11-07 02:43:32');

-- --------------------------------------------------------

--
-- Table structure for table `statistics_snapshots`
--

CREATE TABLE `statistics_snapshots` (
  `id` int(10) UNSIGNED NOT NULL,
  `period_type` enum('monthly','quarterly','yearly') COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_value` date NOT NULL COMMENT 'Tanggal snapshot (awal bulan/tahun)',
  `total_schools` int(10) UNSIGNED DEFAULT '0',
  `total_students` int(10) UNSIGNED DEFAULT '0',
  `total_teachers` int(10) UNSIGNED DEFAULT '0',
  `breakdown_data` json DEFAULT NULL COMMENT 'Data breakdown per kabupaten/jenjang',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `created_at`, `deleted_at`) VALUES
(22, 'teknologi', 'teknologi', '2025-11-05 19:40:35', NULL),
(24, 'test 1', 'test-1', '2025-11-05 19:41:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','admin','editor','author') COLLATE utf8mb4_unicode_ci DEFAULT 'author',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  `login_attempts` tinyint(3) UNSIGNED DEFAULT '0',
  `locked_until` timestamp NULL DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `two_factor_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `photo`, `password`, `role`, `last_login_at`, `created_by`, `updated_by`, `login_attempts`, `locked_until`, `two_factor_enabled`, `two_factor_secret`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Lycorismeeee', 'admin@btikp-kalsel.id', '081234567891', 'Banjarmasin barat', 'users/img_8248_1762385835_8fb6a730.PNG', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', '2025-11-07 04:58:20', NULL, 1, 0, NULL, 0, NULL, 1, '2025-11-03 23:05:42', '2025-11-07 04:58:20', NULL),
(28, 'admin1', 'admin1@gmail.com', '081234567892', '', 'users/photo_6217739526839847528_y_1762465612_429e90df.jpg', '$2y$10$P148vcVXQ3lqM6PhOfQcX.yzAwzlE0VhcX2iXkHyvCEmI6oyW4ZoS', 'editor', NULL, 1, NULL, 0, NULL, 0, NULL, 0, '2025-11-06 22:46:52', '2025-11-06 21:53:28', '2025-11-06 21:53:28'),
(29, 'admin1', 'admin1@gmail.com', '', '', NULL, '$2y$10$FOf2jI0XlIkLAn8UqyHvcOJpFd8TUPZsLN8YTU9eyY1w3lS2nocg6', 'admin', '2025-11-07 04:58:12', 1, NULL, 0, NULL, 0, NULL, 1, '2025-11-06 22:53:48', '2025-11-07 04:58:12', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_published_posts`
-- (See below for the actual view)
--
CREATE TABLE `v_published_posts` (
`id` int(10) unsigned
,`title` varchar(255)
,`slug` varchar(255)
,`excerpt` text
,`featured_image` varchar(500)
,`view_count` int(10) unsigned
,`is_featured` tinyint(1)
,`published_at` timestamp
,`category_name` varchar(100)
,`category_slug` varchar(100)
,`author_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_schools_by_regency`
-- (See below for the actual view)
--
CREATE TABLE `v_schools_by_regency` (
`regency_id` char(4)
,`regency_name` varchar(100)
,`regency_type` enum('kabupaten','kota')
,`level_name` varchar(50)
,`status` enum('Negeri','Swasta')
,`total_schools` bigint(21)
,`total_students` decimal(32,0)
,`total_teachers` decimal(32,0)
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_model` (`model_type`,`model_id`);

--
-- Indexes for table `agendas`
--
ALTER TABLE `agendas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_start_time` (`start_time`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `api_integrations`
--
ALTER TABLE `api_integrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_position` (`position`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_commentable` (`commentable_type`,`commentable_id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_created` (`status`,`created_at`);

--
-- Indexes for table `downloadable_files`
--
ALTER TABLE `downloadable_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_file_type` (`file_type`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_deleted_at` (`deleted_at`);
ALTER TABLE `downloadable_files` ADD FULLTEXT KEY `idx_search` (`title`,`description`);

--
-- Indexes for table `file_categories`
--
ALTER TABLE `file_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `galleries`
--
ALTER TABLE `galleries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_album` (`album`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_display_order` (`display_order`);

--
-- Indexes for table `gallery_albums`
--
ALTER TABLE `gallery_albums`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `is_active` (`is_active`),
  ADD KEY `idx_active_order` (`is_active`,`display_order`);

--
-- Indexes for table `gallery_photos`
--
ALTER TABLE `gallery_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `album_id` (`album_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `display_order` (`display_order`),
  ADD KEY `idx_album_order` (`album_id`,`display_order`);

--
-- Indexes for table `integration_logs`
--
ALTER TABLE `integration_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_integration_id` (`integration_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `levels`
--
ALTER TABLE `levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_code` (`code`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_file_type` (`file_type`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_menu_location` (`menu_location`),
  ADD KEY `idx_position` (`position`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `page_views`
--
ALTER TABLE `page_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_viewable` (`viewable_type`,`viewable_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_session_id` (`session_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_token` (`token`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_author_id` (`author_id`),
  ADD KEY `idx_published_at` (`published_at`),
  ADD KEY `idx_is_featured` (`is_featured`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_status_published` (`status`,`published_at`),
  ADD KEY `idx_category_status` (`category_id`,`status`);
ALTER TABLE `posts` ADD FULLTEXT KEY `idx_search` (`title`,`content`,`excerpt`);

--
-- Indexes for table `post_categories`
--
ALTER TABLE `post_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`post_id`,`tag_id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_tag_id` (`tag_id`);

--
-- Indexes for table `regencies`
--
ALTER TABLE `regencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `npsn` (`npsn`),
  ADD KEY `idx_npsn` (`npsn`),
  ADD KEY `idx_level_id` (`level_id`),
  ADD KEY `idx_regency_id` (`regency_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_accreditation` (`accreditation`),
  ADD KEY `idx_is_verified` (`is_verified`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_level_regency` (`level_id`,`regency_id`),
  ADD KEY `idx_status_level` (`status`,`level_id`);
ALTER TABLE `schools` ADD FULLTEXT KEY `idx_search` (`name`,`address`);

--
-- Indexes for table `school_details`
--
ALTER TABLE `school_details`
  ADD PRIMARY KEY (`school_id`),
  ADD KEY `idx_coordinates` (`latitude`,`longitude`);

--
-- Indexes for table `school_galleries`
--
ALTER TABLE `school_galleries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_featured` (`is_featured`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `statistics_snapshots`
--
ALTER TABLE `statistics_snapshots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_snapshot` (`period_type`,`period_value`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_period` (`period_type`,`period_value`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `name_unique` (`name`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`is_active`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;

--
-- AUTO_INCREMENT for table `agendas`
--
ALTER TABLE `agendas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_integrations`
--
ALTER TABLE `api_integrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `downloadable_files`
--
ALTER TABLE `downloadable_files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `file_categories`
--
ALTER TABLE `file_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `galleries`
--
ALTER TABLE `galleries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gallery_albums`
--
ALTER TABLE `gallery_albums`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `gallery_photos`
--
ALTER TABLE `gallery_photos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `integration_logs`
--
ALTER TABLE `integration_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `levels`
--
ALTER TABLE `levels`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `page_views`
--
ALTER TABLE `page_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `post_categories`
--
ALTER TABLE `post_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_galleries`
--
ALTER TABLE `school_galleries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `statistics_snapshots`
--
ALTER TABLE `statistics_snapshots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

-- --------------------------------------------------------

--
-- Structure for view `v_published_posts`
--
DROP TABLE IF EXISTS `v_published_posts`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_published_posts`  AS SELECT `p`.`id` AS `id`, `p`.`title` AS `title`, `p`.`slug` AS `slug`, `p`.`excerpt` AS `excerpt`, `p`.`featured_image` AS `featured_image`, `p`.`view_count` AS `view_count`, `p`.`is_featured` AS `is_featured`, `p`.`published_at` AS `published_at`, `pc`.`name` AS `category_name`, `pc`.`slug` AS `category_slug`, `u`.`name` AS `author_name` FROM ((`posts` `p` join `post_categories` `pc` on((`p`.`category_id` = `pc`.`id`))) join `users` `u` on((`p`.`author_id` = `u`.`id`))) WHERE ((`p`.`status` = 'published') AND isnull(`p`.`deleted_at`) AND (`p`.`published_at` <= now())) ;

-- --------------------------------------------------------

--
-- Structure for view `v_schools_by_regency`
--
DROP TABLE IF EXISTS `v_schools_by_regency`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_schools_by_regency`  AS SELECT `r`.`id` AS `regency_id`, `r`.`name` AS `regency_name`, `r`.`type` AS `regency_type`, `l`.`name` AS `level_name`, `s`.`status` AS `status`, count(0) AS `total_schools`, sum(`s`.`student_count`) AS `total_students`, sum(`s`.`teacher_count`) AS `total_teachers` FROM ((`schools` `s` join `regencies` `r` on((`s`.`regency_id` = `r`.`id`))) join `levels` `l` on((`s`.`level_id` = `l`.`id`))) WHERE isnull(`s`.`deleted_at`) GROUP BY `r`.`id`, `r`.`name`, `r`.`type`, `l`.`name`, `s`.`status` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `downloadable_files`
--
ALTER TABLE `downloadable_files`
  ADD CONSTRAINT `downloadable_files_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `file_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `downloadable_files_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `file_categories`
--
ALTER TABLE `file_categories`
  ADD CONSTRAINT `file_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `file_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gallery_albums`
--
ALTER TABLE `gallery_albums`
  ADD CONSTRAINT `fk_albums_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gallery_photos`
--
ALTER TABLE `gallery_photos`
  ADD CONSTRAINT `fk_photos_album` FOREIGN KEY (`album_id`) REFERENCES `gallery_albums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_photos_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `integration_logs`
--
ALTER TABLE `integration_logs`
  ADD CONSTRAINT `integration_logs_ibfk_1` FOREIGN KEY (`integration_id`) REFERENCES `api_integrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `media`
--
ALTER TABLE `media`
  ADD CONSTRAINT `media_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_category_fk` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `posts_ibfk_2` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD CONSTRAINT `post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schools`
--
ALTER TABLE `schools`
  ADD CONSTRAINT `schools_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`),
  ADD CONSTRAINT `schools_ibfk_2` FOREIGN KEY (`regency_id`) REFERENCES `regencies` (`id`);

--
-- Constraints for table `school_details`
--
ALTER TABLE `school_details`
  ADD CONSTRAINT `school_details_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_galleries`
--
ALTER TABLE `school_galleries`
  ADD CONSTRAINT `school_galleries_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `statistics_snapshots`
--
ALTER TABLE `statistics_snapshots`
  ADD CONSTRAINT `statistics_snapshots_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
