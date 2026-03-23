-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Aug 22, 2025 at 08:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `oto`
--

-- --------------------------------------------------------

--
-- Table structure for table `auto_sequences`
--

DROP TABLE IF EXISTS `auto_sequences`;
CREATE TABLE `auto_sequences` (
  `seq_key` varchar(50) NOT NULL,
  `last_no` int(11) NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auto_sequences`
--

INSERT INTO `auto_sequences` (`seq_key`, `last_no`, `updated_at`) VALUES
('CT', 2, '2025-08-18 20:02:45'),
('CUS', 8, '2025-08-16 17:44:45'),
('INV', 3, '2025-08-18 20:02:45'),
('USR', 4, '2025-08-16 17:44:45');

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `brand_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `logo_file_code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`brand_code`, `name`, `country`, `created_at`, `updated_at`, `logo_file_code`) VALUES
('AUD', 'Audi', 'Đức', '2025-08-18 20:42:55', NULL, 'F25081815425508689A5'),
('BMW', 'BMW', 'Đức', '2025-08-18 20:41:19', NULL, 'F250818154119B5415AC'),
('LAM', 'Lamborghini', 'Ý', '2025-08-18 20:42:11', NULL, 'F25081815421134B0779'),
('POR', 'Porsche', 'Đức', '2025-08-18 20:44:21', NULL, 'F2508181544213353FF8'),
('ROL', 'Roll Royce', 'Anh', '2025-08-18 20:43:51', NULL, 'F2508181543517CD3E26');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

DROP TABLE IF EXISTS `cars`;
CREATE TABLE `cars` (
  `car_code` varchar(20) NOT NULL,
  `brand_code` varchar(3) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `engine` varchar(100) DEFAULT NULL,
  `power` varchar(50) DEFAULT NULL,
  `acceleration` varchar(50) DEFAULT NULL,
  `top_speed` varchar(50) DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `transmission` varchar(50) DEFAULT NULL,
  `seats` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`car_code`, `brand_code`, `name`, `description`, `price`, `engine`, `power`, `acceleration`, `top_speed`, `fuel_type`, `transmission`, `seats`, `created_at`) VALUES
('CAR-AUD-001', 'AUD', 'Audi A3 Sedan', 'Sedan hạng D sang trọng, cạnh tranh với BMW 3-Series và Mercedes C-Class.', 1600000000.00, '1.4L TFSI', '150', '8.2s', '220', 'Xăng', '7 cấp S Tronic', 5, '2025-08-18 20:49:13'),
('CAR-AUD-002', 'AUD', 'Audi 4', 'Sedan hạng C nhỏ gọn, thiết kế hiện đại, phù hợp đô thị.', 2100000000.00, '2.0L TFSI', '190 HP', '7.3s', '240', 'Xăng', '7', 5, '2025-08-18 20:52:46'),
('CAR-BMW-001', 'BMW', 'BMW 3 Serie', 'Sedan hạng sang cỡ nhỏ, đối thủ trực tiếp của Audi A4 và Mercedes C-Class.', 1950000000.00, '2.0L TwinPower Turbo', '184 HP', '7.1s', '235', 'Xăng', '8', 5, '2025-08-18 21:02:02'),
('CAR-BMW-002', 'BMW', 'BMW X5', 'SUV hạng sang cỡ lớn, trang bị nhiều công nghệ hiện đại và khả năng vận hành mạnh mẽ.', 4300000000.00, '3.0L I6 TwinPower Turbo', '340 HP', '5.5s', '243', 'Xăng', '8', 5, '2025-08-18 21:03:27'),
('CAR-POR-001', 'POR', 'Porsche Cayenne', 'siêu xe thể thao nổi tiếng với khả năng tăng tốc mạnh mẽ, thiết kế sang trọng và hiệu năng đỉnh cao.', 2100000000.00, '3.8l twin-turbo flat-6', '640 HP', '2.7s', '330', 'Xăng', '8 cấp pdk', 2, '2025-08-18 22:46:21'),
('CAR-ROL-001', 'ROL', 'Phantom VIII', 'Sedan siêu sang, được mệnh danh là “ông hoàng” của Rolls-Royce với thiết kế sang trọng và khoang nội thất xa hoa', 46000000000.00, 'V12 6.75L Twin-Turbo', '563 mã lực', '5.3s', '250', 'Xăng', 'Tự động 8 cấp', 4, '2025-08-22 11:57:21');

-- --------------------------------------------------------

--
-- Table structure for table `car_images`
--

DROP TABLE IF EXISTS `car_images`;
CREATE TABLE `car_images` (
  `car_image_code` varchar(20) NOT NULL,
  `car_code` varchar(12) NOT NULL,
  `file_code` varchar(20) NOT NULL,
  `is_primary` tinyint(4) DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `car_images`
--

INSERT INTO `car_images` (`car_image_code`, `car_code`, `file_code`, `is_primary`, `created_at`) VALUES
('IMG-AUD-001-001', 'CAR-AUD-001', 'F250818154913248D64B', 1, '2025-08-18 20:49:13'),
('IMG-AUD-001-002', 'CAR-AUD-001', 'F25081815491373D5780', 0, '2025-08-18 20:49:13'),
('IMG-AUD-001-004', 'CAR-AUD-001', 'F25081815491375319FC', 0, '2025-08-18 20:49:13'),
('IMG-AUD-002-001', 'CAR-AUD-002', 'F250818155246846D831', 1, '2025-08-18 20:52:46'),
('IMG-AUD-002-002', 'CAR-AUD-002', 'F250818155246ED5E8C4', 0, '2025-08-18 20:52:46'),
('IMG-AUD-002-003', 'CAR-AUD-002', 'F2508181552469496D28', 0, '2025-08-18 20:52:46'),
('IMG-AUD-002-004', 'CAR-AUD-002', 'F250818155246ABAB8DA', 0, '2025-08-18 20:52:46'),
('IMG-BMW-001-001', 'CAR-BMW-001', 'F250818160202E4C8BD2', 1, '2025-08-18 21:02:02'),
('IMG-BMW-001-002', 'CAR-BMW-001', 'F25081816020267925C7', 0, '2025-08-18 21:02:02'),
('IMG-BMW-001-003', 'CAR-BMW-001', 'F250818160202ACD0732', 0, '2025-08-18 21:02:02'),
('IMG-BMW-001-004', 'CAR-BMW-001', 'F25081816020267DDE78', 0, '2025-08-18 21:02:02'),
('IMG-BMW-002-001', 'CAR-BMW-002', 'F250818160327DEEC9D5', 1, '2025-08-18 21:03:27'),
('IMG-BMW-002-002', 'CAR-BMW-002', 'F2508181603276CDD6A5', 0, '2025-08-18 21:03:27'),
('IMG-BMW-002-003', 'CAR-BMW-002', 'F250818160327A9B8A23', 0, '2025-08-18 21:03:27'),
('IMG-BMW-002-004', 'CAR-BMW-002', 'F250818160327CB2CF64', 0, '2025-08-18 21:03:27'),
('IMG-LAM-001-001', 'CAR-LAM-001', 'F250818160659000D7DC', 1, '2025-08-18 21:06:59'),
('IMG-LAM-001-002', 'CAR-LAM-001', 'F2508181606596A1F153', 0, '2025-08-18 21:06:59'),
('IMG-LAM-001-003', 'CAR-LAM-001', 'F250818160659FACDACE', 0, '2025-08-18 21:06:59'),
('IMG-LAM-001-004', 'CAR-LAM-001', 'F2508181606590AC0685', 0, '2025-08-18 21:06:59'),
('IMG-LAM-002-001', 'CAR-LAM-002', 'F250818173609CBC00DE', 1, '2025-08-18 22:36:09'),
('IMG-LAM-002-002', 'CAR-LAM-002', 'F25081817360933B2D5E', 0, '2025-08-18 22:36:09'),
('IMG-LAM-002-003', 'CAR-LAM-002', 'F2508181736097E71D3B', 0, '2025-08-18 22:36:09'),
('IMG-LAM-002-004', 'CAR-LAM-002', 'F2508181736098871E30', 0, '2025-08-18 22:36:09'),
('IMG-LAM-002-005', 'CAR-LAM-002', 'F250818173609D2064AA', 0, '2025-08-18 22:36:09'),
('IMG-LAM-002-006', 'CAR-LAM-002', 'F25081817360966462B3', 0, '2025-08-18 22:36:09'),
('IMG-POR-001-001', 'CAR-POR-001', 'F250818174621170F1D5', 1, '2025-08-18 22:46:21'),
('IMG-POR-001-002', 'CAR-POR-001', 'F25081817462163EC29D', 0, '2025-08-18 22:46:21'),
('IMG-POR-001-003', 'CAR-POR-001', 'F250818174621822942A', 0, '2025-08-18 22:46:21'),
('IMG-POR-001-004', 'CAR-POR-001', 'F2508181746213615664', 0, '2025-08-18 22:46:21'),
('IMG-POR-001-005', 'CAR-POR-001', 'F25081817462187F504B', 0, '2025-08-18 22:46:21'),
('IMG-POR-001-006', 'CAR-POR-001', 'F250818174621AE51C68', 0, '2025-08-18 22:46:21'),
('IMG-POR-001-007', 'CAR-POR-001', 'F25081817462194E2421', 0, '2025-08-18 22:46:21'),
('IMG-POR-001-008', 'CAR-POR-001', 'F2508181746219B3FBA7', 0, '2025-08-18 22:46:21'),
('IMG-ROL-001-001', 'CAR-ROL-001', 'F250822065721DAF4BE3', 1, '2025-08-22 11:57:21'),
('IMG-ROL-001-002', 'CAR-ROL-001', 'F250822065721898E9CB', 0, '2025-08-22 11:57:21'),
('IMG-ROL-001-003', 'CAR-ROL-001', 'F250822065721EB49DC0', 0, '2025-08-22 11:57:21'),
('IMG-ROL-001-004', 'CAR-ROL-001', 'F2508220657219E1991E', 0, '2025-08-22 11:57:21'),
('IMG-ROL-001-005', 'CAR-ROL-001', 'F250822065721F9D2A4E', 0, '2025-08-22 11:57:21'),
('IMG-ROL-001-006', 'CAR-ROL-001', 'F2508220657211A16D4C', 0, '2025-08-22 11:57:21');

-- --------------------------------------------------------

--
-- Table structure for table `code_seq`
--

DROP TABLE IF EXISTS `code_seq`;
CREATE TABLE `code_seq` (
  `prefix` varchar(10) NOT NULL,
  `val` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `code_seq`
--

INSERT INTO `code_seq` (`prefix`, `val`) VALUES
('CT', 0),
('INV', 0);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `customer_code` varchar(20) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_code`, `full_name`, `phone`, `email`, `address`, `notes`, `created_at`, `id`) VALUES
('CUS-0014', 'Nguyễn Thị Quỳnh Như', '095683590', 'qn@gmail.com', '8 street 28', 'khách hàng tiềm năng', '2025-08-18 18:37:05', 1),
('CUS-0015', 'Trần Trọng Hiệp', NULL, 'th@gmail.com', NULL, 'auto-sync from users', '2025-08-21 17:43:22', 2),
('CUS-0022', 'Trần Thị Bình', '0912345678', 'binh.tran@example.com', 'Q.3, TP.HCM', NULL, '2025-08-20 14:23:15', 3),
('CUS-0023', 'Trần Trọng Hiệp', '0468846584', 'tth@gmail.com', '8 street 28', 'tui', '2025-08-20 18:27:21', 4),
('CUS-0024', 'gia tiên', '0987654321', NULL, NULL, 'auto-sync from users', '2025-08-21 18:18:59', 5),
('CUS-0025', 'Anh Thư', '03456789323', 'anhthu1@gmail.com', '1600 Amphitheatre Parkway', 'không', '2025-08-22 11:50:19', 6),
('CUS-0007', 'Đặng Hoàng Gia Tiên', NULL, 'gtien@gmail.com', '12344566', '23', '2025-08-22 12:29:54', 7),
('CUS-0008', 'Đặng Hoàng Gia Tiên', NULL, 'gtien2@gmail.com', '12344566', 'ỉoijhbf', '2025-08-22 12:31:25', 8);

-- --------------------------------------------------------

--
-- Table structure for table `customer_feedback`
--

DROP TABLE IF EXISTS `customer_feedback`;
CREATE TABLE `customer_feedback` (
  `feedback_no` varchar(12) NOT NULL,
  `customer_code` varchar(15) NOT NULL,
  `car_code` varchar(12) DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_feedback`
--

INSERT INTO `customer_feedback` (`feedback_no`, `customer_code`, `car_code`, `rating`, `comment`, `created_at`) VALUES
('FB2025081818', 'CUS-0014', NULL, NULL, 'tôi rất thích dịch vụ ở đây', '2025-08-18 23:06:59'),
('FB2025082113', 'CUS-0015', NULL, 5, 'tôi thấy khá hài lòng', '2025-08-21 18:43:24'),
('FB2025082207', 'CUS-0015', NULL, 1, 'đẹo', '2025-08-22 12:03:02');

-- --------------------------------------------------------

--
-- Table structure for table `export_files`
--

DROP TABLE IF EXISTS `export_files`;
CREATE TABLE `export_files` (
  `export_code` varchar(12) NOT NULL,
  `stat_code` varchar(12) DEFAULT NULL,
  `export_type` enum('EXCEL','PDF') NOT NULL,
  `file_code` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `export_files`
--

INSERT INTO `export_files` (`export_code`, `stat_code`, `export_type`, `file_code`, `created_at`) VALUES
('EXP-0001', 'STAT0001', 'EXCEL', 'F00000005', '2025-08-13 15:42:00'),
('EXP-0002', NULL, 'PDF', 'F00000004', '2025-08-13 15:42:00'),
('EXP-0003', NULL, 'PDF', 'F55073670', '2025-08-13 15:27:50'),
('EXP-0004', NULL, 'EXCEL', 'F55074091', '2025-08-13 15:34:51'),
('EXP-0005', NULL, 'EXCEL', 'F55074112', '2025-08-13 15:35:12');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

DROP TABLE IF EXISTS `files`;
CREATE TABLE `files` (
  `file_code` varchar(40) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `storage_path` varchar(255) NOT NULL,
  `uploaded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`file_code`, `original_name`, `mime_type`, `file_size`, `storage_path`, `uploaded_at`) VALUES
('F250816145959CE6F4A9', 'BMW.jpg', 'image/png', 226522, 'uploads/brands/BMW/F250816145959CE6F4A92.jpg', '2025-08-16 19:59:59'),
('F25081615042733F60C1', 'BMW.jpg', 'image/png', 226522, 'uploads/brands/BMW/F25081615042733F60C16.jpg', '2025-08-16 20:04:27'),
('F250816150501CC3E4F2', 'BMW.jpg', 'image/png', 226522, 'uploads/brands/BMW/F250816150501CC3E4F24.jpg', '2025-08-16 20:05:01'),
('F250816150522C5AE2EE', 'Lamborghini.jpg', 'image/png', 593837, 'uploads/brands/LAM/F250816150522C5AE2EE1.jpg', '2025-08-16 20:05:23'),
('F250816152129AED3E4A', 'Porsche.jpg', 'image/png', 534373, 'uploads/brands/POR/F250816152129AED3E4A5.jpg', '2025-08-16 20:21:29'),
('F250816152155FB2C96D', 'Porsche.jpg', 'image/png', 534373, 'uploads/brands/POR/F250816152155FB2C96DB.jpg', '2025-08-16 20:21:55'),
('F250816152631E765752', 'BMW.jpg', 'image/png', 226522, 'uploads/brands/BMW/F250816152631E765752B.jpg', '2025-08-16 20:26:31'),
('F250816152641EA0C290', 'BMW.jpg', 'image/png', 226522, 'uploads/brands/BMW/F250816152641EA0C290B.jpg', '2025-08-16 20:26:41'),
('F25081615364135EA4FD', 'F250816B00207.jpg', 'image/png', 226522, 'uploads/brands/BMW/F25081615364135EA4FD9.jpg', '2025-08-16 20:36:41'),
('F2508161543442367EA7', 'Audi.jpg', 'image/jpeg', 85124, 'uploads/cars/F2508161543442367EA76.jpg', '2025-08-16 20:43:44'),
('F25081615460077CFD79', 'BMW.jpg', 'image/png', 226522, 'uploads/brands/BMW/F25081615460077CFD796.jpg', '2025-08-16 20:46:00'),
('F2508161552525952A81', 'Screenshot 2025-08-14 133826.png', 'image/png', 68119, 'uploads/brands/BMW/F2508161552525952A815.png', '2025-08-16 20:52:52'),
('F25081616211475D90F7', 'Screenshot 2025-05-13 160059.png', 'image/png', 142986, 'uploads/cars/F25081616211475D90F7A.png', '2025-08-16 21:21:14'),
('F2508166D604E', 'BMW.jpg', 'image/png', 226522, 'uploads/brands/F2508166D604E.jpg', '2025-08-16 19:38:19'),
('F250817065701378740B', 'Screenshot (2).png', 'image/png', 589554, 'C:\\xampp\\htdocs\\showroom_oto\\uploads\\brands\\BMW\\F250817065701378740BB.png', '2025-08-17 11:57:01'),
('F250817065718FF4C363', 'Screenshot (1).png', 'image/png', 1049805, 'C:\\xampp\\htdocs\\showroom_oto\\uploads\\cars\\F250817065718FF4C3632.png', '2025-08-17 11:57:18'),
('F2508170658582F515E0', 'Screenshot (4).png', 'image/png', 1042223, 'C:\\xampp\\htdocs\\showroom_oto\\uploads\\brands\\AUD\\F2508170658582F515E0F.png', '2025-08-17 11:58:58'),
('F250817071009FC90D74', 'BMW.jpg', 'image/png', 226522, 'uploads/brands/AUD/F250817071009FC90D74.png', '2025-08-17 12:10:09'),
('F250817071027A664663', 'deepseek_mermaid_20250729_e5e42f.png', 'image/png', 578707, 'uploads/brands/BMW/F250817071027A664663.png', '2025-08-17 12:10:27'),
('F250817080940734BFAA', '1.png', 'image/png', 61446, '/showroom_oto/uploads/brands/HON/F250817080940734BFAA.png', '2025-08-17 13:09:40'),
('F250817080951B5863FE', '5.png', 'image/png', 42813, '/showroom_oto/uploads/brands/TOY/F250817080951B5863FE.png', '2025-08-17 13:09:51'),
('F2508170810449EB17B4', 'Audi.jpg', 'image/jpeg', 85124, '/showroom_oto/uploads/brands/AUD/F2508170810449EB17B4.jpg', '2025-08-17 13:10:44'),
('F250817081050720BB35', 'BMW.jpg', 'image/png', 226522, '/showroom_oto/uploads/brands/BMW/F250817081050720BB35.jpg', '2025-08-17 13:10:50'),
('F2508170810568BDD0BF', 'hero-service.jpg', 'image/jpeg', 356260, '/showroom_oto/uploads/brands/HON/F2508170810568BDD0BF.jpg', '2025-08-17 13:10:56'),
('F250817081105A251D69', 'Lamborghini.jpg', 'image/png', 593837, '/showroom_oto/uploads/brands/LAM/F250817081105A251D69.jpg', '2025-08-17 13:11:05'),
('F2508170811117940926', 'Porsche.jpg', 'image/png', 534373, '/showroom_oto/uploads/brands/POR/F2508170811117940926.jpg', '2025-08-17 13:11:11'),
('F25081708111779C52D3', 'RR.jpg', 'image/png', 39060, '/showroom_oto/uploads/brands/TOY/F25081708111779C52D3.jpg', '2025-08-17 13:11:17'),
('F250817081122EEE847A', 'hero-service.jpg', 'image/jpeg', 356260, '/showroom_oto/uploads/brands/VIN/F250817081122EEE847A.jpg', '2025-08-17 13:11:22'),
('F250818070949370F200', '911turbo.jpg', 'image/jpeg', 552178, '/showroom_oto/uploads/cars/CAR-HON-003/F250818070949370F200.jpg', '2025-08-18 12:09:49'),
('F2508180709493CC1FD9', 'boattail.jpg', 'image/jpeg', 832978, '/showroom_oto/uploads/cars/CAR-HON-003/F2508180709493CC1FD9.jpg', '2025-08-18 12:09:49'),
('F2508180709495E327F9', 'boxster.jpg', 'image/jpeg', 2004050, '/showroom_oto/uploads/cars/CAR-HON-003/F2508180709495E327F9.jpg', '2025-08-18 12:09:49'),
('F250818070949828046D', 'aventador.jpg', 'image/jpeg', 633162, '/showroom_oto/uploads/cars/CAR-HON-003/F250818070949828046D.jpg', '2025-08-18 12:09:49'),
('F250818070949BB24F7D', '7series.jpg', 'image/jpeg', 1282586, '/showroom_oto/uploads/cars/CAR-HON-003/F250818070949BB24F7D.jpg', '2025-08-18 12:09:49'),
('F250818070949C394DB7', 'a4.jpg', 'image/jpeg', 150702, '/showroom_oto/uploads/cars/CAR-HON-003/F250818070949C394DB7.jpg', '2025-08-18 12:09:49'),
('F250818071147037C2BF', 'i8.jpg', 'image/jpeg', 525440, '/showroom_oto/uploads/cars/CAR-HON-002/F250818071147037C2BF.jpg', '2025-08-18 12:11:47'),
('F25081807114707809C1', 'm5cs.jpg', 'image/jpeg', 189858, '/showroom_oto/uploads/cars/CAR-HON-002/F25081807114707809C1.jpg', '2025-08-18 12:11:47'),
('F2508180711473051A07', 'huracan.jpg', 'image/jpeg', 1010866, '/showroom_oto/uploads/cars/CAR-HON-002/F2508180711473051A07.jpg', '2025-08-18 12:11:47'),
('F2508180711479824254', 'm8.jpg', 'image/jpeg', 272013, '/showroom_oto/uploads/cars/CAR-HON-002/F2508180711479824254.jpg', '2025-08-18 12:11:47'),
('F250818071147B538160', 'macan.jpg', 'image/jpeg', 205242, '/showroom_oto/uploads/cars/CAR-HON-002/F250818071147B538160.jpg', '2025-08-18 12:11:47'),
('F250818071147E48BD5C', 'gt3rs.jpg', 'image/jpeg', 335493, '/showroom_oto/uploads/cars/CAR-HON-002/F250818071147E48BD5C.jpg', '2025-08-18 12:11:47'),
('F2508180712117ECEE32', 'wraith.jpg', 'image/jpeg', 481364, '/showroom_oto/uploads/cars/CAR-BMW-005/F2508180712117ECEE32.jpg', '2025-08-18 12:12:11'),
('F2508180712118DB89AB', 'veneno.jpg', 'image/jpeg', 1286476, '/showroom_oto/uploads/cars/CAR-BMW-005/F2508180712118DB89AB.jpg', '2025-08-18 12:12:11'),
('F250818071211BEFB598', 'rs7.jpg', 'image/jpeg', 311248, '/showroom_oto/uploads/cars/CAR-BMW-005/F250818071211BEFB598.jpg', '2025-08-18 12:12:11'),
('F250818071211D8BF1FE', 'taycan.jpg', 'image/jpeg', 247213, '/showroom_oto/uploads/cars/CAR-BMW-005/F250818071211D8BF1FE.jpg', '2025-08-18 12:12:11'),
('F250818071211E5A8BA8', 'spectre.jpg', 'image/jpeg', 1132455, '/showroom_oto/uploads/cars/CAR-BMW-005/F250818071211E5A8BA8.jpg', '2025-08-18 12:12:11'),
('F250818071211F1C57DD', 'reventon.jpg', 'image/jpeg', 328408, '/showroom_oto/uploads/cars/CAR-BMW-005/F250818071211F1C57DD.jpg', '2025-08-18 12:12:11'),
('F2508180712303CE2842', 'taycan.jpg', 'image/jpeg', 247213, '/showroom_oto/uploads/cars/CAR-TOY-001/F2508180712303CE2842.jpg', '2025-08-18 12:12:30'),
('F2508180712304388ED6', 'veneno.jpg', 'image/jpeg', 1286476, '/showroom_oto/uploads/cars/CAR-TOY-001/F2508180712304388ED6.jpg', '2025-08-18 12:12:30'),
('F250818071230756B406', 'x5m.jpg', 'image/jpeg', 241729, '/showroom_oto/uploads/cars/CAR-TOY-001/F250818071230756B406.jpg', '2025-08-18 12:12:30'),
('F250818071230CADBE9B', 'wraith.jpg', 'image/jpeg', 481364, '/showroom_oto/uploads/cars/CAR-TOY-001/F250818071230CADBE9B.jpg', '2025-08-18 12:12:30'),
('F250818071230EF0679A', 'spectre.jpg', 'image/jpeg', 1132455, '/showroom_oto/uploads/cars/CAR-TOY-001/F250818071230EF0679A.jpg', '2025-08-18 12:12:30'),
('F250818071230FD0CE4F', 'taycan_cross.jpg', 'image/jpeg', 1201794, '/showroom_oto/uploads/cars/CAR-TOY-001/F250818071230FD0CE4F.jpg', '2025-08-18 12:12:30'),
('F25081807124714C2382', 'q8.jpg', 'image/jpeg', 477646, '/showroom_oto/uploads/cars/CAR-AUD-001/F25081807124714C2382.jpg', '2025-08-18 12:12:47'),
('F25081807124719CF90E', 'phantom.jpg', 'image/jpeg', 618986, '/showroom_oto/uploads/cars/CAR-AUD-001/F25081807124719CF90E.jpg', '2025-08-18 12:12:47'),
('F250818071247924EC34', 'q3.jpg', 'image/jpeg', 1408762, '/showroom_oto/uploads/cars/CAR-AUD-001/F250818071247924EC34.jpg', '2025-08-18 12:12:47'),
('F25081807124792744E5', 'q5.jpg', 'image/jpeg', 509725, '/showroom_oto/uploads/cars/CAR-AUD-001/F25081807124792744E5.jpg', '2025-08-18 12:12:47'),
('F2508180713438EB22AA', 'gt3rs.jpg', 'image/jpeg', 335493, '/showroom_oto/uploads/cars/CAR-VIN-001/F2508180713438EB22AA.jpg', '2025-08-18 12:13:43'),
('F250818071343A3E5A67', 'i8.jpg', 'image/jpeg', 525440, '/showroom_oto/uploads/cars/CAR-VIN-001/F250818071343A3E5A67.jpg', '2025-08-18 12:13:43'),
('F250818071343C921E54', 'm2.jpg', 'image/jpeg', 485578, '/showroom_oto/uploads/cars/CAR-VIN-001/F250818071343C921E54.jpg', '2025-08-18 12:13:43'),
('F250818071343CEC2E44', 'huracan.jpg', 'image/jpeg', 1010866, '/showroom_oto/uploads/cars/CAR-VIN-001/F250818071343CEC2E44.jpg', '2025-08-18 12:13:43'),
('F250818071343D5AA906', 'ixm60.jpg', 'image/jpeg', 494700, '/showroom_oto/uploads/cars/CAR-VIN-001/F250818071343D5AA906.jpg', '2025-08-18 12:13:43'),
('F250818151820FAA34A5', '911turbo.jpg', 'image/jpeg', 552178, '/showroom_oto/uploads/brands/P/F250818151820FAA34A5.jpg', '2025-08-18 20:18:20'),
('F250818152027A09725D', '7series.jpg', 'image/jpeg', 1282586, '/showroom_oto/uploads/brands/P/F250818152027A09725D.jpg', '2025-08-18 20:20:27'),
('F250818152638510D391', '5series.jpg', 'image/jpeg', 751886, '/showroom_oto/uploads/brands/POR/F250818152638510D391.jpg', '2025-08-18 20:26:38'),
('F2508181532008D9BF28', '7series.jpg', 'image/jpeg', 1282586, '/showroom_oto/uploads/brands/POR/F2508181532008D9BF28.jpg', '2025-08-18 20:32:00'),
('F250818154119B5415AC', 'BMW.jpg', 'image/png', 226522, '/showroom_oto/uploads/brands/BMW/F250818154119B5415AC.jpg', '2025-08-18 20:41:19'),
('F25081815421134B0779', 'Lamborghini.jpg', 'image/png', 593837, '/showroom_oto/uploads/brands/LAM/F25081815421134B0779.jpg', '2025-08-18 20:42:11'),
('F25081815425508689A5', 'Audi.jpg', 'image/jpeg', 85124, '/showroom_oto/uploads/brands/AUD/F25081815425508689A5.jpg', '2025-08-18 20:42:55'),
('F2508181543517CD3E26', 'RR.jpg', 'image/png', 39060, '/showroom_oto/uploads/brands/ROL/F2508181543517CD3E26.jpg', '2025-08-18 20:43:51'),
('F2508181544213353FF8', 'Porsche.jpg', 'image/png', 534373, '/showroom_oto/uploads/brands/POR/F2508181544213353FF8.jpg', '2025-08-18 20:44:21'),
('F250818154913248D64B', '5series.jpg', 'image/jpeg', 751886, '/showroom_oto/uploads/cars/CAR-AUD-001/F250818154913248D64B.jpg', '2025-08-18 20:49:13'),
('F25081815491373D5780', '7series.jpg', 'image/jpeg', 1282586, '/showroom_oto/uploads/cars/CAR-AUD-001/F25081815491373D5780.jpg', '2025-08-18 20:49:13'),
('F25081815491375319FC', 'a4.jpg', 'image/jpeg', 150702, '/showroom_oto/uploads/cars/CAR-AUD-001/F25081815491375319FC.jpg', '2025-08-18 20:49:13'),
('F250818155246846D831', 'a8l.jpg', 'image/jpeg', 2086479, '/showroom_oto/uploads/cars/CAR-AUD-002/F250818155246846D831.jpg', '2025-08-18 20:52:46'),
('F2508181552469496D28', 'boattail.jpg', 'image/jpeg', 832978, '/showroom_oto/uploads/cars/CAR-AUD-002/F2508181552469496D28.jpg', '2025-08-18 20:52:46'),
('F250818155246ABAB8DA', 'boxster.jpg', 'image/jpeg', 2004050, '/showroom_oto/uploads/cars/CAR-AUD-002/F250818155246ABAB8DA.jpg', '2025-08-18 20:52:46'),
('F250818155246ED5E8C4', 'aventador.jpg', 'image/jpeg', 633162, '/showroom_oto/uploads/cars/CAR-AUD-002/F250818155246ED5E8C4.jpg', '2025-08-18 20:52:46'),
('F25081816020267925C7', 'diablo.jpg', 'image/jpeg', 1169590, '/showroom_oto/uploads/cars/CAR-BMW-001/F25081816020267925C7.jpg', '2025-08-18 21:02:02'),
('F25081816020267DDE78', 'gallardo.jpg', 'image/jpeg', 412541, '/showroom_oto/uploads/cars/CAR-BMW-001/F25081816020267DDE78.jpg', '2025-08-18 21:02:02'),
('F250818160202ACD0732', 'etron_gt.jpg', 'image/jpeg', 929937, '/showroom_oto/uploads/cars/CAR-BMW-001/F250818160202ACD0732.jpg', '2025-08-18 21:02:02'),
('F250818160202E4C8BD2', 'dawn.jpg', 'image/jpeg', 737703, '/showroom_oto/uploads/cars/CAR-BMW-001/F250818160202E4C8BD2.jpg', '2025-08-18 21:02:02'),
('F2508181603276CDD6A5', 'cayman.jpg', 'image/jpeg', 91605, '/showroom_oto/uploads/cars/CAR-BMW-002/F2508181603276CDD6A5.jpg', '2025-08-18 21:03:27'),
('F250818160327A9B8A23', 'centenario.jpg', 'image/jpeg', 805960, '/showroom_oto/uploads/cars/CAR-BMW-002/F250818160327A9B8A23.jpg', '2025-08-18 21:03:27'),
('F250818160327CB2CF64', 'corniche.jpg', 'image/jpeg', 265776, '/showroom_oto/uploads/cars/CAR-BMW-002/F250818160327CB2CF64.jpg', '2025-08-18 21:03:27'),
('F250818160327DEEC9D5', 'cayenne.jpg', 'image/jpeg', 80250, '/showroom_oto/uploads/cars/CAR-BMW-002/F250818160327DEEC9D5.jpg', '2025-08-18 21:03:27'),
('F250818160659000D7DC', 'spectre.jpg', 'image/jpeg', 1132455, '/showroom_oto/uploads/cars/CAR-LAM-001/F250818160659000D7DC.jpg', '2025-08-18 21:06:59'),
('F2508181606590AC0685', 'tt.jpg', 'image/jpeg', 329952, '/showroom_oto/uploads/cars/CAR-LAM-001/F2508181606590AC0685.jpg', '2025-08-18 21:06:59'),
('F2508181606596A1F153', 'taycan.jpg', 'image/jpeg', 247213, '/showroom_oto/uploads/cars/CAR-LAM-001/F2508181606596A1F153.jpg', '2025-08-18 21:06:59'),
('F250818160659FACDACE', 'taycan_cross.jpg', 'image/jpeg', 1201794, '/showroom_oto/uploads/cars/CAR-LAM-001/F250818160659FACDACE.jpg', '2025-08-18 21:06:59'),
('F25081817360933B2D5E', 'm8.jpg', 'image/jpeg', 272013, '/showroom_oto/uploads/cars/CAR-LAM-002/F25081817360933B2D5E.jpg', '2025-08-18 22:36:09'),
('F25081817360966462B3', 'q5.jpg', 'image/jpeg', 509725, '/showroom_oto/uploads/cars/CAR-LAM-002/F25081817360966462B3.jpg', '2025-08-18 22:36:09'),
('F2508181736097E71D3B', 'macan.jpg', 'image/jpeg', 205242, '/showroom_oto/uploads/cars/CAR-LAM-002/F2508181736097E71D3B.jpg', '2025-08-18 22:36:09'),
('F2508181736098871E30', 'phantom.jpg', 'image/jpeg', 618986, '/showroom_oto/uploads/cars/CAR-LAM-002/F2508181736098871E30.jpg', '2025-08-18 22:36:09'),
('F250818173609CBC00DE', 'm5cs.jpg', 'image/jpeg', 189858, '/showroom_oto/uploads/cars/CAR-LAM-002/F250818173609CBC00DE.jpg', '2025-08-18 22:36:09'),
('F250818173609D2064AA', 'q3.jpg', 'image/jpeg', 1408762, '/showroom_oto/uploads/cars/CAR-LAM-002/F250818173609D2064AA.jpg', '2025-08-18 22:36:09'),
('F250818174621170F1D5', 'a8l.jpg', 'image/jpeg', 2086479, '/showroom_oto/uploads/cars/CAR-POR-001/F250818174621170F1D5.jpg', '2025-08-18 22:46:21'),
('F2508181746213615664', 'boxster.jpg', 'image/jpeg', 2004050, '/showroom_oto/uploads/cars/CAR-POR-001/F2508181746213615664.jpg', '2025-08-18 22:46:21'),
('F25081817462163EC29D', 'aventador.jpg', 'image/jpeg', 633162, '/showroom_oto/uploads/cars/CAR-POR-001/F25081817462163EC29D.jpg', '2025-08-18 22:46:21'),
('F250818174621822942A', 'boattail.jpg', 'image/jpeg', 832978, '/showroom_oto/uploads/cars/CAR-POR-001/F250818174621822942A.jpg', '2025-08-18 22:46:21'),
('F25081817462187F504B', 'cayenne.jpg', 'image/jpeg', 80250, '/showroom_oto/uploads/cars/CAR-POR-001/F25081817462187F504B.jpg', '2025-08-18 22:46:21'),
('F25081817462194E2421', 'centenario.jpg', 'image/jpeg', 805960, '/showroom_oto/uploads/cars/CAR-POR-001/F25081817462194E2421.jpg', '2025-08-18 22:46:21'),
('F2508181746219B3FBA7', 'corniche.jpg', 'image/jpeg', 265776, '/showroom_oto/uploads/cars/CAR-POR-001/F2508181746219B3FBA7.jpg', '2025-08-18 22:46:21'),
('F250818174621AE51C68', 'cayman.jpg', 'image/jpeg', 91605, '/showroom_oto/uploads/cars/CAR-POR-001/F250818174621AE51C68.jpg', '2025-08-18 22:46:21'),
('F2508220657211A16D4C', 'boattail.jpg', 'image/jpeg', 832978, '/showroom_oto/uploads/cars/CAR-ROL-001/F2508220657211A16D4C.jpg', '2025-08-22 11:57:21'),
('F250822065721898E9CB', '7series.jpg', 'image/jpeg', 1282586, '/showroom_oto/uploads/cars/CAR-ROL-001/F250822065721898E9CB.jpg', '2025-08-22 11:57:21'),
('F2508220657219E1991E', 'a8l.jpg', 'image/jpeg', 2086479, '/showroom_oto/uploads/cars/CAR-ROL-001/F2508220657219E1991E.jpg', '2025-08-22 11:57:21'),
('F250822065721DAF4BE3', '5series.jpg', 'image/jpeg', 751886, '/showroom_oto/uploads/cars/CAR-ROL-001/F250822065721DAF4BE3.jpg', '2025-08-22 11:57:21'),
('F250822065721EB49DC0', '911turbo.jpg', 'image/jpeg', 552178, '/showroom_oto/uploads/cars/CAR-ROL-001/F250822065721EB49DC0.jpg', '2025-08-22 11:57:21'),
('F250822065721F9D2A4E', 'aventador.jpg', 'image/jpeg', 633162, '/showroom_oto/uploads/cars/CAR-ROL-001/F250822065721F9D2A4E.jpg', '2025-08-22 11:57:21'),
('F250822065820EFBEB04', 'BMW.jpg', 'image/png', 226522, '/showroom_oto/uploads/brands/KIM/F250822065820EFBEB04.jpg', '2025-08-22 11:58:20'),
('FILE-0001', 'invoices_20250813_102750.pdf', 'application/pdf', 106069, 'exports/invoices_20250813_102750.pdf', '2025-08-13 15:27:50'),
('FILE-0002', 'invoices_20250813_103451.xls', 'application/vnd.ms-excel', 1088, 'exports/invoices_20250813_103451.xls', '2025-08-13 15:34:51'),
('FILE-0003', 'invoices_20250813_103512.xls', 'application/vnd.ms-excel', 1088, 'exports/invoices_20250813_103512.xls', '2025-08-13 15:35:12'),
('FILE-0004', 'brochure_iv0001.pdf', 'application/pdf', 204800, 'uploads/brochure_iv0001.pdf', '2025-08-13 15:41:59'),
('FILE-0005', 'invoices_sample.pdf', 'application/pdf', 102400, 'exports/invoices_sample.pdf', '2025-08-13 15:41:59'),
('FILE-0006', 'CAR-BMW-003_1.png', 'image/png', 578707, 'uploads/cars/F250814713896.png', '2025-08-14 15:31:47'),
('FILE-0007', 'Screenshot (1).png', 'image/png', 1049805, 'uploads/cars/F250814706239.png', '2025-08-14 15:33:02'),
('FILE-0008', 'Screenshot (2).png', 'image/png', 589554, 'uploads/cars/F250814064689.png', '2025-08-14 15:33:34'),
('FILE-0009', 'Screenshot 2025-05-13 160059.png', 'image/png', 142986, 'uploads/cars/F2508147026A6.png', '2025-08-14 15:33:34'),
('FILE-0010', 'Screenshot (3).png', 'image/png', 1042223, 'uploads/cars/F250814BB4DE7.png', '2025-08-14 15:33:34'),
('FILE-0011', 'Screenshot 2025-05-13 160107.png', 'image/png', 154474, 'uploads/cars/F250814C72DD1.png', '2025-08-14 15:33:34'),
('FILE-0012', 'Screenshot (1).png', 'image/png', 1049805, 'uploads/cars/F250814D0646E.png', '2025-08-14 15:33:34'),
('FILE-0013', 'Screenshot 2025-06-26 190716.png', 'image/png', 69785, 'uploads/cars/F25081412A7EA.png', '2025-08-14 16:10:23'),
('FILE-0014', '0b4985627cec4da868b4276095837b16.png', 'image/webp', 423918, 'uploads/cars/F250814598B28.png', '2025-08-14 16:13:17'),
('FILE-0015', '5a88700404405255cf61a4aa796e49e9.png', 'image/webp', 692660, 'uploads/cars/F250814F61FE9.png', '2025-08-14 16:47:42'),
('FILE-0016', '01cced396269da07d331dbf238ecc1ec 1.png', 'image/webp', 189338, 'uploads/cars/F250814485339.png', '2025-08-14 16:48:00'),
('FILE-0017', '0bc2c98baf578c1cbe07a327ba28e37f.png', 'image/webp', 230924, 'uploads/cars/F250814F3DBEF.png', '2025-08-14 16:48:21'),
('FILE-0018', '0a2ef57713e7a60ca236697925dd82a7.png', 'image/webp', 207944, 'uploads/cars/F250814FC61B1.png', '2025-08-14 16:48:57'),
('FILE-0019', '0b4985627cec4da868b4276095837b16.png', 'image/webp', 423918, 'uploads/cars/F250814E2B9D6.png', '2025-08-14 16:49:21'),
('FILE-0020', '0bc2c98baf578c1cbe07a327ba28e37f.png', 'image/webp', 230924, 'uploads/cars/F250814B20E9F.png', '2025-08-14 16:49:37'),
('FILE-0021', '0d6518098597376c1f8600c96db61ae9.png', 'image/webp', 663132, 'uploads/cars/F25081480E147.png', '2025-08-14 16:49:46'),
('FILE-0022', '0a2ef57713e7a60ca236697925dd82a7.png', 'image/webp', 207944, 'uploads/cars/F250814DE7224.png', '2025-08-14 16:50:22'),
('FILE-0023', '3d0967b63b41af908373703c54a4acbd.png', 'image/webp', 261476, 'uploads/cars/F25081470B0EF.png', '2025-08-14 16:50:58'),
('FILE-0024', '3e25162c41d2777a73de9cacf9685efc 1.png', 'image/webp', 315310, 'uploads/cars/F2508147182CB.png', '2025-08-14 16:50:58'),
('FILE-0025', '2f950c850f9481e096877389c3c698c1 (1).png', 'image/webp', 110618, 'uploads/cars/F2508147B0E98.png', '2025-08-14 16:50:58'),
('FILE-0026', '3e25162c41d2777a73de9cacf9685efc.png', 'image/webp', 315310, 'uploads/cars/F2508149DB1B8.png', '2025-08-14 16:50:58'),
('FILE-0027', '4a0a787308fe350496ee57e623885d21.png', 'image/webp', 366596, 'uploads/cars/F250814A6E735.png', '2025-08-14 16:50:58'),
('FILE-0028', '3c151863802958eb06b88e8435c69c47.png', 'image/webp', 418664, 'uploads/cars/F250814D7B7C0.png', '2025-08-14 16:50:58'),
('FILE-0029', '3b490c287ec51cb5a537c9b0144d14de.png', 'image/webp', 255768, 'uploads/cars/F250814F6C529.png', '2025-08-14 16:50:58'),
('FILE-0030', '-bs59tz.jpg', 'image/jpeg', 37933, 'uploads/cars/F2508140AFEB8.jpg', '2025-08-14 16:51:16'),
('FILE-0031', 'x5m.jpg', 'image/jpeg', 241729, 'uploads/cars/F25081471E7EA.jpg', '2025-08-14 17:16:49'),
('FILE-0032', 'a6.jpg', 'image/jpeg', 312013, 'uploads/cars/F250814103C8D.jpg', '2025-08-14 17:24:21'),
('FILE-0033', 'a4.jpg', 'image/jpeg', 150702, 'uploads/cars/F25081441675C.jpg', '2025-08-14 17:24:21'),
('FILE-0034', '5series.jpg', 'image/jpeg', 751886, 'uploads/cars/F250814BF0FB6.jpg', '2025-08-14 17:24:21'),
('FILE-0035', '7series.jpg', 'image/jpeg', 1282586, 'uploads/cars/F250814D9C828.jpg', '2025-08-14 17:24:21'),
('FILE-0036', '911turbo.jpg', 'image/jpeg', 552178, 'uploads/cars/F250814E9F537.jpg', '2025-08-14 17:24:21'),
('FILE-0037', 'boattail.jpg', 'image/jpeg', 832978, 'uploads/cars/F25081556F4F4.jpg', '2025-08-15 13:01:41');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `invoice_no` varchar(20) NOT NULL,
  `contract_no` varchar(20) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `vat_rate` decimal(4,2) NOT NULL DEFAULT 10.00,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`invoice_no`, `contract_no`, `invoice_date`, `amount`, `vat_rate`, `vat_amount`, `total_amount`, `created_at`) VALUES
('INV-1006', 'SC1006', '2025-08-21', 2100000000.00, 10.00, 210000000.00, 2310000000.00, '2025-08-21 18:18:59'),
('INV-1007', 'SC1007', '2025-08-21', 2100000000.00, 10.00, 210000000.00, 2310000000.00, '2025-08-21 18:20:32'),
('INV-1008', 'SC1008', '2025-08-21', 1600000000.00, 10.00, 160000000.00, 1760000000.00, '2025-08-21 20:07:04'),
('INV-1010', 'SC1010', '2025-08-22', 4300000000.00, 10.00, 430000000.00, 4730000000.00, '2025-08-22 11:52:08'),
('INV-1011', 'SC1011', '2025-07-22', 46000000000.00, 10.00, 4600000000.00, 50600000000.00, '2025-08-22 12:00:47');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `receipt_no` varchar(20) NOT NULL,
  `contract_no` varchar(12) NOT NULL,
  `payment_date` date NOT NULL,
  `method` enum('CASH','BANK_TRANSFER','CARD') NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`receipt_no`, `contract_no`, `payment_date`, `method`, `reference_no`, `amount`, `created_at`) VALUES
('RC-1006', 'SC1006', '2025-08-21', 'CASH', 'không', 2310000000.00, '2025-08-21 18:18:59'),
('RC-1007', 'SC1007', '2025-08-21', 'CARD', 'không', 2310000000.00, '2025-08-21 18:20:32'),
('RC-1008', 'SC1008', '2025-08-21', 'CASH', 'không', 1760000000.00, '2025-08-21 20:07:04'),
('RC-1009', 'SC1010', '2025-08-22', 'CARD', 'không', 4730000000.00, '2025-08-22 11:52:08'),
('RC-1010', 'SC1011', '2025-07-22', 'BANK_TRANSFER', 'không', 50600000000.00, '2025-08-22 12:00:47');

-- --------------------------------------------------------

--
-- Table structure for table `revenue_stats`
--

DROP TABLE IF EXISTS `revenue_stats`;
CREATE TABLE `revenue_stats` (
  `stat_code` varchar(12) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `total_contracts` int(11) NOT NULL DEFAULT 0,
  `total_revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `generated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `revenue_stats`
--

INSERT INTO `revenue_stats` (`stat_code`, `from_date`, `to_date`, `total_contracts`, `total_revenue`, `generated_at`) VALUES
('STAT-0001', '2025-08-01', '2025-08-31', 3, 2387000000.00, '2025-08-13 15:42:00');

-- --------------------------------------------------------

--
-- Table structure for table `sales_contracts`
--

DROP TABLE IF EXISTS `sales_contracts`;
CREATE TABLE `sales_contracts` (
  `contract_no` varchar(20) NOT NULL,
  `contract_date` date NOT NULL,
  `customer_code` varchar(15) NOT NULL,
  `car_code` varchar(12) NOT NULL,
  `appointment_no` varchar(12) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(15,2) NOT NULL,
  `vat_rate` decimal(4,2) NOT NULL DEFAULT 10.00,
  `vat_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `status` enum('SIGNED') NOT NULL DEFAULT 'SIGNED',
  `contract_file` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_contracts`
--

INSERT INTO `sales_contracts` (`contract_no`, `contract_date`, `customer_code`, `car_code`, `appointment_no`, `quantity`, `unit_price`, `vat_rate`, `vat_amount`, `total_amount`, `status`, `contract_file`, `notes`, `created_at`) VALUES
('SC1006', '2025-08-21', 'CUS-0024', 'CAR-AUD-002', NULL, 1, 2100000000.00, 10.00, 210000000.00, 2310000000.00, 'SIGNED', '/uploads/contracts/contract_SC1006.pdf', 'tôi mua', '2025-08-21 18:18:59'),
('SC1007', '2025-08-21', 'CUS-0015', 'CAR-AUD-002', NULL, 1, 2100000000.00, 10.00, 210000000.00, 2310000000.00, 'SIGNED', '/uploads/contracts/contract_SC1007.pdf', 'tôi quyết định mua', '2025-08-21 18:20:32'),
('SC1008', '2025-08-21', 'CUS-0024', 'CAR-AUD-001', NULL, 1, 1600000000.00, 10.00, 160000000.00, 1760000000.00, 'SIGNED', '/uploads/contracts/contract_SC1008.pdf', 'z á', '2025-08-21 20:07:04'),
('SC1010', '2025-08-22', 'CUS-0015', 'CAR-BMW-002', NULL, 1, 4300000000.00, 10.00, 430000000.00, 4730000000.00, 'SIGNED', NULL, 'không', '2025-08-22 11:52:08'),
('SC1011', '2025-07-22', 'CUS-0022', 'CAR-ROL-001', NULL, 1, 46000000000.00, 10.00, 4600000000.00, 50600000000.00, 'SIGNED', NULL, 'không', '2025-08-22 12:00:47');

-- --------------------------------------------------------

--
-- Table structure for table `sequences`
--

DROP TABLE IF EXISTS `sequences`;
CREATE TABLE `sequences` (
  `code` varchar(10) NOT NULL,
  `cur_no` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sequences`
--

INSERT INTO `sequences` (`code`, `cur_no`) VALUES
('COST', 7),
('CT', 0),
('INV', 0),
('SRQ', 7),
('WRR', 5);

-- --------------------------------------------------------

--
-- Table structure for table `seq_customers`
--

DROP TABLE IF EXISTS `seq_customers`;
CREATE TABLE `seq_customers` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seq_customers`
--

INSERT INTO `seq_customers` (`id`) VALUES
(11),
(13),
(14),
(15);

-- --------------------------------------------------------

--
-- Table structure for table `seq_users`
--

DROP TABLE IF EXISTS `seq_users`;
CREATE TABLE `seq_users` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seq_users`
--

INSERT INTO `seq_users` (`id`) VALUES
(5),
(6),
(7),
(8);

-- --------------------------------------------------------

--
-- Table structure for table `service_appointments`
--

DROP TABLE IF EXISTS `service_appointments`;
CREATE TABLE `service_appointments` (
  `service_appointment_no` varchar(12) NOT NULL,
  `request_no` varchar(12) NOT NULL,
  `appointment_dt` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('PENDING','CONFIRMED','DONE','CANCELLED') NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_appointments`
--

INSERT INTO `service_appointments` (`service_appointment_no`, `request_no`, `appointment_dt`, `notes`, `status`, `created_at`) VALUES
('SAP-0001', 'SRQ0001', '2025-08-12 09:00:00', 'Mang xe đến sớm', 'DONE', '2025-08-13 15:42:00'),
('SAP-0002', 'SRQ0002', '2025-08-15 10:00:00', 'Đặt lịch kiểm tra phanh', 'CONFIRMED', '2025-08-13 15:42:00');

-- --------------------------------------------------------

--
-- Table structure for table `service_costs`
--

DROP TABLE IF EXISTS `service_costs`;
CREATE TABLE `service_costs` (
  `id` int(10) UNSIGNED NOT NULL,
  `cost_no` varchar(12) NOT NULL,
  `request_no` varchar(12) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_costs`
--

INSERT INTO `service_costs` (`id`, `cost_no`, `request_no`, `item_name`, `quantity`, `unit_price`, `amount`, `created_at`) VALUES
(1, 'COST0001', 'SRQ0001', 'Thay dầu', 1.00, 800000.00, 800000.00, '2025-08-13 15:42:00'),
(2, 'COST0002', 'SRQ0001', 'Lọc gió', 1.00, 300000.00, 300000.00, '2025-08-13 15:42:00'),
(3, 'COST0003', 'SRQ0002', 'Sửa phanh', 1.00, 1500000.00, 1500000.00, '2025-08-13 15:42:00'),
(4, 'COST0001', 'SRQ0003', 'Bảo dưỡng cơ bản', 1.00, 500000.00, 500000.00, '2025-08-20 18:09:15'),
(5, 'COST0002', 'SRQ0004', 'Kiểm tra lốp xe', 1.00, 100000.00, 100000.00, '2025-08-20 18:19:40'),
(6, 'COST0003', 'SRQ0005', 'Thay dầu máy', 1.00, 300000.00, 300000.00, '2025-08-20 18:25:28'),
(7, 'COST0004', 'SRQ0006', 'Bảo dưỡng cơ bản', 1.00, 500000.00, 500000.00, '2025-08-20 18:27:56'),
(8, 'COST0005', 'SRQ0006', 'Kiểm tra lốp xe', 1.00, 100000.00, 100000.00, '2025-08-20 18:27:56'),
(9, 'COST0006', 'SRQ0007', 'Sửa chữa động cơ', 1.00, 2000000.00, 2000000.00, '2025-08-20 18:38:10'),
(10, 'COST0007', 'SRQ0007', 'Sơn xe', 1.00, 1500000.00, 1500000.00, '2025-08-20 18:38:10');

-- --------------------------------------------------------

--
-- Table structure for table `service_records`
--

DROP TABLE IF EXISTS `service_records`;
CREATE TABLE `service_records` (
  `service_record_no` varchar(12) NOT NULL,
  `request_no` varchar(12) DEFAULT NULL,
  `service_dt` datetime NOT NULL,
  `details` text DEFAULT NULL,
  `cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_records`
--

INSERT INTO `service_records` (`service_record_no`, `request_no`, `service_dt`, `details`, `cost`, `created_at`) VALUES
('SR-0001', 'SRQ0001', '2025-08-12 11:00:00', 'Bảo dưỡng 10.000km', 1100000.00, '2025-08-13 15:42:00'),
('SR-0002', 'SRQ0002', '2025-08-16 16:00:00', 'Kiểm tra & căn chỉnh phanh', 1500000.00, '2025-08-13 15:42:00');

-- --------------------------------------------------------

--
-- Table structure for table `service_requests`
--

DROP TABLE IF EXISTS `service_requests`;
CREATE TABLE `service_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `request_no` varchar(12) NOT NULL,
  `customer_code` varchar(15) NOT NULL,
  `car_code` varchar(12) NOT NULL,
  `request_type` enum('MAINTENANCE','REPAIR') NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('OPEN','IN_PROGRESS','DONE','CANCELLED') NOT NULL DEFAULT 'OPEN',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `service_requests`
--

INSERT INTO `service_requests` (`id`, `request_no`, `customer_code`, `car_code`, `request_type`, `description`, `status`, `created_at`) VALUES
(1, 'SRQ-0000', 'CUS-0014', 'CAR-AUD-001', 'MAINTENANCE', 'vậy á', 'OPEN', '2025-08-18 23:43:41'),
(2, 'SRQ-0001', 'CUS-0015', 'CAR-AUD-002', 'MAINTENANCE', 'quá đỉnh', 'OPEN', '2025-08-20 12:03:22'),
(3, 'SRQ-0002', 'CUS-0015', 'CAR-AUD-002', 'REPAIR', 'ee', 'OPEN', '2025-08-20 12:05:23'),
(4, 'SRQ0003', 'CUS-0015', 'CAR-AUD-002', 'MAINTENANCE', 'l', 'OPEN', '2025-08-20 18:09:15'),
(5, 'SRQ0004', 'CUS-0015', 'CAR-LAM-001', 'MAINTENANCE', '4', 'OPEN', '2025-08-20 18:19:40'),
(6, 'SRQ0005', 'CUS-0015', 'CAR-AUD-001', 'MAINTENANCE', 'tui', 'OPEN', '2025-08-20 18:25:28'),
(7, 'SRQ0006', 'CUS-0015', 'CAR-LAM-001', 'MAINTENANCE', 'tui nè', 'OPEN', '2025-08-20 18:27:56'),
(8, 'SRQ0007', 'CUS-0015', 'CAR-LAM-001', 'MAINTENANCE', 'hay qua ne', 'OPEN', '2025-08-20 18:38:10');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_code` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `role` enum('ADMIN','CUSTOMER') NOT NULL DEFAULT 'CUSTOMER',
  `customer_code` varchar(15) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_code`, `username`, `email`, `password_hash`, `full_name`, `role`, `customer_code`, `is_active`, `created_at`, `id`) VALUES
('USR-0006', 'kphine', 'kphine@gmail.com', '$2y$10$Gym9i9zDKwVV5oLrQLIjveCI1L2v6lXBIpIPZd1N0hpbO2KdRaGYq', 'Trần Kim Thị Phí', 'ADMIN', NULL, 1, '2025-08-16 18:09:00', 2),
('USR-0008', 'th', 'th@gmail.com', '$2y$10$Qj3N.RXYs/29G4SRI/6Oreemf4jyXIBaqSQWBQRzitT.7EzOS295i', 'Trần Trọng Hiệp', 'CUSTOMER', 'CUS-0015', 1, '2025-08-20 11:30:19', 3),
('USR-0004', 'gt', 'gtien@gmail.com', '$2y$10$7LSq7HrnATQNpZqXZpOcfOxDkGzPSYphrEZa73OZNe0qWrNaDjL52', 'Đặng Hoàng Gia Tiên', 'CUSTOMER', 'CUS-0007', 0, '2025-08-22 12:29:54', 4),
('USR-0005', 'gt2', 'gtien2@gmail.com', '$2y$10$256mYmcKXPu0Nr/cyyem7ephe.oOB1G.CMrjZ0nWCu8xbZsFHocsm', 'Đặng Hoàng Gia Tiên', 'CUSTOMER', 'CUS-0008', 0, '2025-08-22 12:31:25', 5);

-- --------------------------------------------------------

--
-- Table structure for table `warranties`
--

DROP TABLE IF EXISTS `warranties`;
CREATE TABLE `warranties` (
  `id` int(10) UNSIGNED NOT NULL,
  `warranty_no` varchar(12) NOT NULL,
  `customer_code` varchar(15) NOT NULL,
  `car_code` varchar(12) NOT NULL,
  `vehicle_vin` varchar(50) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `policy` varchar(200) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `package_names` varchar(255) DEFAULT NULL,
  `total_amount` decimal(18,2) NOT NULL DEFAULT 0.00,
  `warranty_fee` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `warranties`
--

INSERT INTO `warranties` (`id`, `warranty_no`, `customer_code`, `car_code`, `vehicle_vin`, `start_date`, `end_date`, `policy`, `created_at`, `package_names`, `total_amount`, `warranty_fee`) VALUES
(6, 'WRR0005', 'CUS-0015', 'CAR-AUD-001', NULL, '2025-08-21', '2027-08-21', 'không', '2025-08-21 20:41:14', 'Bảo hành 2 năm', 1800000.00, 0.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD UNIQUE KEY `uq_brands_code` (`brand_code`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD UNIQUE KEY `uq_cars_code` (`car_code`);

--
-- Indexes for table `car_images`
--
ALTER TABLE `car_images`
  ADD UNIQUE KEY `uq_car_images` (`car_code`,`file_code`,`is_primary`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customers_code` (`customer_code`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD UNIQUE KEY `uq_files_code` (`file_code`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD UNIQUE KEY `uq_invoices_no` (`invoice_no`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD UNIQUE KEY `uq_payments_no` (`receipt_no`);

--
-- Indexes for table `sales_contracts`
--
ALTER TABLE `sales_contracts`
  ADD UNIQUE KEY `uq_sales_contracts_no` (`contract_no`);

--
-- Indexes for table `sequences`
--
ALTER TABLE `sequences`
  ADD PRIMARY KEY (`code`);

--
-- Indexes for table `service_costs`
--
ALTER TABLE `service_costs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_requests`
--
ALTER TABLE `service_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_code` (`user_code`);

--
-- Indexes for table `warranties`
--
ALTER TABLE `warranties`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `service_costs`
--
ALTER TABLE `service_costs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `service_requests`
--
ALTER TABLE `service_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `warranties`
--
ALTER TABLE `warranties`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
