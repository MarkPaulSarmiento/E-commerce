-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 25, 2026 at 01:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dyna_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `session_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `is_active` tinyint(4) DEFAULT 1,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`session_id`, `admin_id`, `session_token`, `is_active`, `login_time`, `logout_time`) VALUES
(1, 1, '3bc2466560b866f82d31a610dbac71afceef2199b04c6ffeff343c03e4adf0ec', 0, '2026-03-24 22:56:35', '2026-03-24 23:33:51'),
(2, 1, '892967b7fa4fbb6eafa17c02f716b6d644820e31cbc97d7a87a0e801c0ada34e', 0, '2026-03-24 23:33:51', '2026-03-25 00:08:27'),
(3, 1, '3d7d6c1563fc0b163de96498c64fcb4a2431c21c482acd77b25e6cd32240f580', 0, '2026-03-25 00:08:27', '2026-03-25 00:43:52'),
(4, 1, 'ce63a44a96aa0705541b2d306859aae9ebc3fa03dbc534e7e5fd04f95a312e52', 0, '2026-03-25 00:43:52', '2026-03-25 01:49:25'),
(5, 1, '399af1689bfcaeae7dc62c942d2f4ecb902b48b80607eeddd701f039be1d94d0', 0, '2026-03-25 01:49:25', '2026-03-25 02:46:39'),
(6, 1, 'ea89314293b1a1612b72647ce42b9ecc465637eb4d718760cfce61e2f6a324d1', 0, '2026-03-25 02:46:39', '2026-03-25 02:56:32'),
(7, 1, '53c6a93abd74a325176b06118e33deaaa43c13f762b74762e30e48f37db473e6', 0, '2026-03-25 02:56:32', '2026-03-25 02:59:15'),
(8, 1, 'af1a271ab3652d9435325e9b1b900d577503b53a008798e53704448e6cf4ffb3', 0, '2026-03-25 02:59:15', '2026-03-25 03:57:43'),
(9, 1, '95f8a9bd4ccc5b3bb03675f9a147a0b6fc451046c2c78c962f0ded2992354e09', 0, '2026-03-25 03:57:43', '2026-03-25 04:24:57'),
(10, 1, 'a6b1015e4b28f22a78cfad030ec8cdda0a9321657b8c5a5367c00e30f39ed63b', 0, '2026-03-25 04:24:57', '2026-03-25 04:49:27'),
(11, 1, 'f118af8fc42c4a6d2445dd31103216c8800c580f26ca5864978fd41ddaa5dfe6', 0, '2026-03-25 04:49:27', '2026-03-25 05:08:05'),
(12, 1, '0aff243011070ae8939187c54d152dc8efb811c47a7509c40e80eb001d5fd829', 0, '2026-03-25 05:08:05', '2026-03-25 05:46:45'),
(13, 1, '3e4b752319ca6e4b52c40da452047eeb744a5a2ad70eaf682c6f2c96e71dc97d', 0, '2026-03-25 05:46:45', '2026-03-25 06:41:27'),
(14, 1, '4aa8a6d6916c39f56c601c3b27b55d0dd0e8c16df00a72f94f8c4864285175c2', 0, '2026-03-25 06:41:27', '2026-03-25 07:48:16'),
(15, 1, 'c54f2d3ce709ecd121e2094b72d3c47e577b823c9817171f8513fd3bcda82fc7', 0, '2026-03-25 07:48:16', '2026-03-25 07:54:01'),
(16, 1, 'f67cbf57ed8f42e54ebea000a52b38c490fdd0a45c00880c0001b2c0febb5f43', 1, '2026-03-25 07:54:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'Plain text password - TESTING ONLY!',
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('super_admin','admin','manager') DEFAULT 'admin',
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`admin_id`, `username`, `email`, `password`, `full_name`, `role`, `is_active`, `created_at`, `last_login`) VALUES
(1, 'admin', 'dynamastershop@gmail.com', 'Mspp1414!', 'System Administrator', 'super_admin', 1, '2026-03-24 14:52:29', '2026-03-25 07:54:01');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL,
  `reference_number` varchar(100) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `subtotal`, `tax`, `shipping`, `total_amount`, `shipping_address`, `payment_method`, `reference_number`, `order_date`, `status`, `tracking_number`) VALUES
(3, 1, 1232.00, 123.20, 5.00, 1360.20, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-B8FC51', '2026-03-24 11:49:31', 'completed', 'TRK-1101'),
(4, 1, 1232.00, 123.20, 5.00, 1360.20, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-674872', '2026-03-24 12:31:50', 'cancelled', NULL),
(5, 1, 1512.00, 151.20, 5.00, 1668.20, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-9BE752', '2026-03-24 13:47:05', 'completed', 'TRK-1100'),
(6, 1, 2240.00, 224.00, 5.00, 2469.00, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-FC8701', '2026-03-24 13:50:07', 'completed', 'TRK-1099'),
(7, 1, 4312.00, 431.20, 5.00, 4748.20, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-8CFF64', '2026-03-24 18:36:24', 'completed', 'TRK-1102'),
(8, 1, 1232.00, 123.20, 5.00, 1360.20, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-B4FDDA', '2026-03-24 18:58:51', 'shipped', 'TRK-1103'),
(9, 1, 1624.00, 162.40, 5.00, 1791.40, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-68A491', '2026-03-24 19:17:10', 'cancelled', NULL),
(10, 1, 1344.00, 134.40, 5.00, 1483.40, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-CDFCA7', '2026-03-24 19:17:32', 'pending', NULL),
(11, 1, 1064.00, 106.40, 5.00, 1175.40, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-8A9D76', '2026-03-24 19:38:32', 'pending', NULL),
(12, 1, 1008.00, 100.80, 5.00, 1113.80, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-202CAD', '2026-03-24 19:45:38', 'pending', NULL),
(13, 1, 1456.00, 145.60, 5.00, 1606.60, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-2EDF35', '2026-03-24 19:55:46', 'shipped', 'TRK-1101'),
(14, 1, 1512.00, 151.20, 5.00, 1668.20, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-66AE5D', '2026-03-24 19:56:38', 'cancelled', NULL),
(15, 1, 1008.00, 100.80, 5.00, 1113.80, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-F8E8B0', '2026-03-24 20:24:15', 'pending', NULL),
(16, 1, 1512.00, 151.20, 5.00, 1668.20, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-C374F9', '2026-03-24 20:49:00', 'pending', NULL),
(17, 1, 1176.00, 117.60, 5.00, 1298.60, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-76B23F', '2026-03-24 20:54:47', 'shipped', 'TRK-0999'),
(18, 1, 1512.00, 151.20, 5.00, 1668.20, 'Oraan East, Manaoag Pangasinan.', 'QR Code', 'DYNA-20260324-EDFCF6', '2026-03-24 21:07:26', 'shipped', 'TRK-1106'),
(19, 1, 1512.00, 151.20, 5.00, 1668.20, 'Wuhan China, Japangasinan.', 'QR Code', 'DYNA-20260325-1614CF', '2026-03-24 23:26:25', 'cancelled', NULL),
(20, 3, 1456.00, 145.60, 5.00, 1606.60, 'Hongkong Japan, Wuhan Beijing.', 'QR Code', 'DYNA-20260325-619D43', '2026-03-24 23:53:10', 'shipped', 'TRK-1105');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 3, 13, 1, 1232.00),
(2, 4, 7, 1, 1232.00),
(3, 5, 6, 1, 1512.00),
(4, 6, 5, 2, 1120.00),
(5, 7, 2, 1, 1400.00),
(6, 7, 1, 1, 1680.00),
(7, 7, 13, 1, 1232.00),
(8, 8, 7, 1, 1232.00),
(9, 9, 8, 1, 1624.00),
(10, 10, 10, 1, 1344.00),
(11, 11, 9, 1, 1064.00),
(12, 12, 3, 1, 1008.00),
(13, 13, 11, 1, 1456.00),
(14, 14, 6, 1, 1512.00),
(15, 15, 3, 1, 1008.00),
(16, 16, 6, 1, 1512.00),
(17, 17, 12, 1, 1176.00),
(18, 18, 6, 1, 1512.00),
(19, 19, 6, 1, 1512.00),
(20, 20, 11, 1, 1456.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `discounted_price` decimal(10,2) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `filter_tags` varchar(50) DEFAULT NULL,
  `product_link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `category`, `original_price`, `discounted_price`, `image_url`, `filter_tags`, `product_link`) VALUES
(1, 'Denim Jacket', 'Outerwear', 2520.00, 1680.00, 'assets/images/clothes-01.jpg', 'casual', 'product-details.php'),
(2, 'Leather Boots', 'Footwear', 2128.00, 1400.00, 'assets/images/clothes-02.jpg', 'str', 'product-details.php\r\n'),
(3, 'Cotton T-Shirt', 'Topwear', 1456.00, 1008.00, 'assets/images/clothes-03.jpg', 'adv', 'product-details.php'),
(4, 'Silk Dress', 'Dresses', 2352.00, 1568.00, 'assets/images/clothes-04.jpg', 'rac', 'product-details.php'),
(5, 'Sports Shorts', 'Activewear', 1680.00, 1120.00, 'assets/images/clothes-05.jpg', 'adv', 'product-details.php'),
(6, 'Wool Sweater', 'Knitwear', 2240.00, 1512.00, 'assets/images/clothes-06.jpg', 'casual', 'product-details.php'),
(7, 'Sneakers', 'Footwear', 1960.00, 1232.00, 'assets/images/clothes-07.jpg', 'str', 'product-details.php'),
(8, 'Formal Blazer', 'Outerwear', 2520.00, 1624.00, 'assets/images/clothes-08.jpg', 'rac', 'product-details.php'),
(9, 'Summer Hat', 'Accessories', 1508.00, 1064.00, 'assets/images/clothes-09.jpg', 'adv', 'product-details.php'),
(10, 'Jeans', 'Bottomwear', 2016.00, 1344.00, 'assets/images/clothes-10.jpg', 'casual', 'product-details.php'),
(11, 'Running Shoes', 'Footwear', 2184.00, 1456.00, 'assets/images/clothes-11.jpg', 'adv', 'product-details.php'),
(12, 'Hoodie', 'Outerwear', 1792.00, 1176.00, 'assets/images/clothes-12.jpg', 'str', 'product-details.php'),
(13, 'Luffy T-Shirt', 'Topwear', 2053.33, 1232.00, 'assets/images/clothes-13.jpg', 'casual', 'product-details.php'),
(14, 'Mechanical Arm', 'adv', 10000.00, 8000.00, 'assets/image/clothes-014', 'adv', 'product-details.php');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `email`, `address`, `created_at`) VALUES
(1, 'mark', '$2y$10$WqDBdc9tPSGJt4PSjVKZPeCwIN36bYnnGzNTjkhSuHuxq.wjAX/Ra', 'mrkpulsrmnt12@gmail.com', 'Oraan East, Manaoag Pangasinan', '2026-03-23 10:01:18'),
(3, 'Pat', '$2y$10$hAK5sgKDseF6cRZNkPwgMenqY5hhfS/BVimYyFXWlqlLvhBI5vGbW', 'mape.sarmiento.up@phinmaed.com', 'Hongkong Japan, Wuhan Beijing.', '2026-03-24 22:38:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`session_id`, `user_id`, `session_token`, `login_time`, `logout_time`, `is_active`) VALUES
(1, 1, '4995e92abbd10f6bd6d204048c769840c16cd75375c76c5465c0b83162bc1c26', '2026-03-23 10:39:28', '2026-03-23 11:20:33', 0),
(2, 1, 'd71bdf528d1f165329e78375bafecd5261d6932af50681fad2438396e5e74678', '2026-03-23 10:40:37', '2026-03-23 11:06:13', 0),
(3, 1, '5628d6be2bc42e186928631fdfe62614c5dd05bfbabb20f26a8f6da6d2947372', '2026-03-23 11:12:22', '2026-03-23 11:12:28', 0),
(4, 1, '5f4c680b7a485d4055dd6cef4e67c170007eeba8c7bcf1654fe2f822fb360f64', '2026-03-23 11:12:52', '2026-03-23 11:20:16', 0),
(5, 1, 'f043c440a84a01a1cf8082993ff81ac19685a9a1004bba1ffa70e73e09bb8363', '2026-03-23 11:20:33', '2026-03-24 08:09:12', 0),
(6, 1, 'af52651a75f99d0921958e35228ad577ef93ad1d91c6f814d7aa5e85dd9e3d42', '2026-03-24 08:09:12', '2026-03-24 11:34:21', 0),
(7, 1, '532c3f7e429eff9b05248135ab9ae1dcde3535de84f5453dcd1f71a170499467', '2026-03-24 11:34:21', '2026-03-24 14:33:20', 0),
(8, 1, '2b0cc826e9f3e2749a02042263dbffefbfde1afd9f38aae6c4fa7dedda8b2d44', '2026-03-24 16:04:23', '2026-03-24 16:08:11', 0),
(9, 1, '5cccb2843291860900f387b1934ee5b4a6fd5a115cb432bad17d4d52349e06e2', '2026-03-24 17:48:23', '2026-03-24 17:49:14', 0),
(10, 1, 'ceb90738dfdc08b89bc9677c2d50e76e06eb13d40c3bc32cd1905fa22567d5d2', '2026-03-24 18:35:21', '2026-03-24 18:46:15', 0),
(11, 1, 'f94af40a3ed70b473e087306e39dad62404974aaa081aa694268b3f8679479c0', '2026-03-24 18:58:31', '2026-03-24 18:59:07', 0),
(12, 1, '42ae6dc23605ed376bd41c91d2338de1935245314d57bc1c710590875dd239d2', '2026-03-24 19:11:25', '2026-03-24 19:57:11', 0),
(13, 1, 'd72c6fa785e85a92037b3645d805b5a2f06c505cca940507761e4980d6cd9664', '2026-03-24 20:11:56', '2026-03-24 20:24:48', 0),
(14, 1, '267e346c9e8cf49410ba8d15aaf3b8a845395bb58073875bcbe6cce59ea82a80', '2026-03-24 20:37:44', '2026-03-24 20:49:17', 0),
(15, 1, 'e2ceaf12a484d52b906f827b161f69e30a15ef6965e9d463c3b3b90c6bb3c901', '2026-03-24 20:54:11', '2026-03-24 21:07:52', 0),
(16, 1, '6df62501f578b8ba3a4f526f9a77d8177d66ea57cd255cd508d09f506d3df40a', '2026-03-24 22:09:56', '2026-03-24 22:30:08', 0),
(17, 3, 'd90af35e92f596212589a9af7c149686769c49331b46b5d9f27a502092dbdc71', '2026-03-24 22:39:41', '2026-03-24 22:41:16', 0),
(18, 1, 'bc869f0dd458cbc8d29a18b888b4d0ecc3810221093a8fce41a8579c1e853ac4', '2026-03-24 23:17:26', '2026-03-24 23:41:13', 0),
(19, 1, '350dd5f68966f2af71afced97e53c042894061a975eb267a48471ac3f86f4469', '2026-03-24 23:42:04', '2026-03-24 23:48:08', 0),
(20, 3, '02c362fd64d4b192a830fa066aa7b9db944bf5ae448d1bac9de287880466b01e', '2026-03-24 23:52:35', '2026-03-24 23:53:51', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `reference_number` (`reference_number`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_reference_number` (`reference_number`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
