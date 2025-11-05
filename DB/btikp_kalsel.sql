-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 05, 2025 at 01:31 AM
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
(120, 1, NULL, 'Super Administrator', 'CREATE', 'Menambah kategori: Perhatian', 'post_categories', 6, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-05 01:19:39');

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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_description` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT '1',
  `last_updated_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `meta_description` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `content`, `excerpt`, `featured_image`, `status`, `category_id`, `author_id`, `view_count`, `is_featured`, `meta_description`, `published_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'test1', 'test1', '<p>test buat artikel test edit</p>', 'test ringkas', 'posts/2025/11/1762220508_690959dc1eded.png', 'published', 2, 1, 0, 1, 'test seo', '2025-11-04 02:42:41', '2025-11-04 01:41:48', '2025-11-04 01:42:51', '2025-11-04 01:42:51'),
(2, 'test 2', 'test-2', '<p>cdsccdcsdcfdv</p>', 'test', 'posts/images_1762288353_b54f615e.png', 'published', 2, 1, 13, 1, 'test', '2025-11-04 03:32:00', '2025-11-04 02:32:56', '2025-11-04 20:32:39', NULL),
(3, 'test3', 'test3', '<blockquote><p>testting 3</p></blockquote>', 'testting 3', 'posts/bg_1762288480_b909a4e7.jpg', 'published', 1, 1, 1, 1, '', '2025-11-04 19:20:00', '2025-11-04 02:43:27', '2025-11-04 20:34:40', NULL),
(4, 'test 4', 'test-4', '<p>oinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndf</p>', 'oinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfuvniudvndfoinvcidsfu...', 'posts/logo_1762291287_1b6ace41.png', 'published', 3, 1, 0, 0, '', '2025-11-04 22:21:00', '2025-11-04 21:21:27', '2025-11-04 21:21:27', NULL);

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
(2, 'Artikel', 'artikel', 'Artikel edukatif dan best practice', 'fa-file-alt', '#10B981', 1, '2025-11-03 23:05:42', '2025-11-03 23:05:42', NULL),
(3, 'Pengumuman', 'pengumuman', 'Pengumuman resmi BTIKP', 'fa-bullhorn', '#F59E0B', 1, '2025-11-03 23:05:42', '2025-11-03 23:05:42', NULL),
(4, 'Kegiatan', 'kegiatan', 'Liputan kegiatan dan acara', 'fa-calendar-check', '#8B5CF6', 1, '2025-11-03 23:05:42', '2025-11-03 23:05:42', NULL),
(6, 'Perhatian', 'perhatian', 'perhatian perhatian', NULL, NULL, 1, '2025-11-05 02:19:39', '2025-11-05 01:19:39', NULL);

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
(1, 2, '2025-11-04 01:42:41'),
(2, 2, '2025-11-04 20:32:33');

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
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Bootstrap icon class',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `featured` tinyint(1) DEFAULT '0',
  `order` int(11) DEFAULT '0',
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `meta_keywords` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `title`, `slug`, `description`, `content`, `icon`, `image`, `featured`, `order`, `status`, `meta_title`, `meta_description`, `meta_keywords`, `author_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Konsultasi IT', 'konsultasi-it_deleted1762300087', 'Layanan konsultasi teknologi informasi untuk meningkatkan efisiensi bisnis Anda', '<p>Kami menyediakan layanan konsultasi IT profesional untuk membantu bisnis Anda berkembang di era digital.</p>', 'bi-laptop', NULL, 1, 1, 'archived', NULL, NULL, NULL, 1, '2025-11-04 23:31:17', '2025-11-04 23:48:07', '2025-11-04 23:48:07'),
(2, 'Pengembangan Website', 'pengembangan-website_deleted1762305421', 'Pembuatan website profesional dengan teknologi terkini', '<p>Dapatkan website profesional yang sesuai dengan kebutuhan bisnis Anda.</p>', 'bi-people', NULL, 1, 3, 'archived', 'Deprecated:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in C:\\laragon\\www\\btikp-kalsel\\admin\\modules\\services\\services_edit.php on line 191', 'Deprecated:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in C:\\laragon\\www\\btikp-kalsel\\admin\\modules\\services\\services_edit.php on line 196', 'Deprecated:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in C:\\laragon\\www\\btikp-kalsel\\admin\\modules\\services\\services_edit.php on line 202', 1, '2025-11-04 23:31:17', '2025-11-05 01:17:01', '2025-11-05 01:17:01'),
(3, 'Pelatihan & Workshop', 'pelatihan-workshop', 'Program pelatihan dan workshop teknologi informasi', '<p>Tingkatkan skill tim Anda dengan program pelatihan berkualitas.</p>', 'bi-book', NULL, 1, 3, 'published', NULL, NULL, NULL, 1, '2025-11-04 23:31:17', '2025-11-04 23:31:17', NULL),
(4, 'Maintenance & Support', 'maintenance-support', 'Layanan pemeliharaan dan dukungan teknis', '<p>Dukungan teknis 24/7 untuk sistem IT Anda.</p>', 'bi-tools', NULL, 0, 4, 'published', NULL, NULL, NULL, 1, '2025-11-04 23:31:17', '2025-11-04 23:31:17', NULL),
(5, 'test layanan 2', 'test-layanan-2_deleted1762305055', 'testing layanan 2', '<p>Sistem ini dirancang untuk mempermudah proses pengelolaan data secara efisien dan terstruktur. Dengan antarmuka yang sederhana namun fungsional, pengguna dapat melakukan input, pembaruan, dan pemantauan informasi dengan cepat. Tujuannya adalah meningkatkan produktivitas serta meminimalkan kesalahan dalam pengolahan data.</p>', 'bi-display', NULL, 1, 1, 'archived', '', '', '', 1, '2025-11-05 02:08:17', '2025-11-05 01:10:55', '2025-11-05 01:10:55');

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
(1, 'site_name', 'BTIKP Kalimantan Selatan', 'text', 'general', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(2, 'site_tagline', 'Balai Teknologi Informasi dan Komunikasi Pendidikan', 'text', 'general', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(3, 'site_description', 'Portal resmi BTIKP Provinsi Kalimantan Selatan', 'textarea', 'general', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(4, 'site_keywords', 'btikp, kalsel, pendidikan, teknologi, informasi', 'text', 'general', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(5, 'site_logo', 'settings/logo_1761875034_1762288465_0ec046e1.png', 'file', 'general', '2025-11-04 18:55:37', '2025-11-04 20:34:25'),
(6, 'site_favicon', 'settings/logo_1761875034_1762288409_65324299.png', 'file', 'general', '2025-11-04 18:55:37', '2025-11-04 20:33:29'),
(7, 'contact_phone', '(0511) 1234567', 'text', 'contact', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(8, 'contact_email', 'info@btikp-kalsel.id', 'text', 'contact', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(9, 'contact_address', 'Jl. Pendidikan No. 123, Banjarmasin, Kalimantan Selatan', 'textarea', 'contact', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(10, 'contact_maps_embed', '', 'textarea', 'contact', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(11, 'social_facebook', '', 'text', 'social', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(12, 'social_instagram', '', 'text', 'social', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(13, 'social_youtube', '', 'text', 'social', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(14, 'social_twitter', '', 'text', 'social', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(15, 'upload_max_size', '7', 'number', 'upload', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(16, 'upload_allowed_images', 'jpg,jpeg,png,gif,webp', 'text', 'upload', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(17, 'upload_allowed_docs', 'pdf,doc,docx,xls,xlsx,ppt,pptx', 'text', 'upload', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(18, 'items_per_page', '10', 'number', 'general', '2025-11-04 18:55:37', '2025-11-05 01:07:20'),
(19, 'posts_per_page_public', '12', 'number', 'general', '2025-11-04 18:55:37', NULL),
(22, 'site_copyright', '© {year} BTIKP Kalimantan Selatan. All Rights Reserved.', 'text', 'general', '2025-11-04 19:30:44', '2025-11-05 01:07:20'),
(23, 'site_logo_text', 'BTIKP KALSEL', 'text', 'general', '2025-11-04 19:30:44', '2025-11-05 01:07:20'),
(24, 'site_logo_show_text', '1', 'boolean', 'general', '2025-11-04 19:30:44', '2025-11-05 01:07:20');

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
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `created_at`) VALUES
(1, 'Test Tag', 'test-tag', '2025-11-04 00:21:05'),
(2, 'teknologi', 'teknologi', '2025-11-04 01:41:48');

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
(1, 'Super Administrator', 'admin@btikp-kalsel.id', '081234567891', 'Banjarmasin barat', 'users/avatar-3_1762292898_a63d422a.png', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', '2025-11-05 01:18:16', NULL, 1, 0, NULL, 0, NULL, 1, '2025-11-03 23:05:42', '2025-11-05 01:18:16', NULL),
(23, 'admin1', 'admin1_deleted1762298032@gmail.com', '081234567892', 'cdcdsc', 'users/avatar-3_1762298023_a1ce8797.png', '$2y$10$72qIhq9lgdYLs0ZX3ljVuO/3cp.YJp9vtHoB1qiNSYfSm1Y6IY2ye', 'admin', NULL, 1, 1, 0, NULL, 0, NULL, 0, '2025-11-05 00:13:43', '2025-11-04 23:13:52', '2025-11-04 23:13:52'),
(24, 'admin1', 'admin1_deleted1762298084@gmail.com', '081234567892', 'cdscdsc', 'users/avatar-1_1762298056_af4fc7bc.png', '$2y$10$v6jctD3.Vx99eZD1zUx05uNex1zd5BmeHtvnFnCnSdkGUjXeKux6.', 'admin', NULL, 1, 1, 0, NULL, 0, NULL, 1, '2025-11-05 00:14:16', '2025-11-04 23:14:44', '2025-11-04 23:14:44'),
(25, 'admin1', 'admin1_deleted1762298735@gmail.com', '081234567892', 'cdscdc', 'users/avatar-3_1762298728_00a7d6a3.png', '$2y$10$8sbP6o9/XN3Zbf0qSdJWzOqmwDgIHSb02aKNSyEhrB3ieI.Rdp.R.', 'editor', NULL, 1, NULL, 0, NULL, 0, NULL, 0, '2025-11-05 00:25:29', '2025-11-04 23:25:35', '2025-11-04 23:25:35');

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
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_commentable` (`commentable_type`,`commentable_id`),
  ADD KEY `idx_parent_id` (`parent_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

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
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `last_updated_by` (`last_updated_by`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_is_published` (`is_published`),
  ADD KEY `idx_deleted_at` (`deleted_at`);

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
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`featured`),
  ADD KEY `idx_author` (`author_id`),
  ADD KEY `idx_deleted_at` (`deleted_at`),
  ADD KEY `idx_order` (`order`);

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
  ADD KEY `idx_slug` (`slug`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `downloadable_files`
--
ALTER TABLE `downloadable_files`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_views`
--
ALTER TABLE `page_views`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `post_categories`
--
ALTER TABLE `post_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=278;

--
-- AUTO_INCREMENT for table `statistics_snapshots`
--
ALTER TABLE `statistics_snapshots`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

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
-- Constraints for table `pages`
--
ALTER TABLE `pages`
  ADD CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`last_updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `post_categories` (`id`),
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
