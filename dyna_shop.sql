-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 23, 2026 at 12:21 PM
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
(9, 'Summer Hat', 'Accessories', 1568.00, 1064.00, 'assets/images/clothes-09.jpg', 'adv', 'product-details.php'),
(10, 'Jeans', 'Bottomwear', 2016.00, 1344.00, 'assets/images/clothes-10.jpg', 'casual', 'product-details.php'),
(11, 'Running Shoes', 'Footwear', 2184.00, 1456.00, 'assets/images/clothes-11.jpg', 'adv', 'product-details.php'),
(12, 'Hoodie', 'Outerwear', 1792.00, 1176.00, 'assets/images/clothes-12.jpg', 'str', 'product-details.php'),
(13, 'Luffy T-Shirt', 'Topwear', 2053.33, 1232.00, 'assets/images/clothes-13.jpg', 'casual', 'product-details.php');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `email`, `created_at`) VALUES
(1, 'mark', '$2y$10$WqDBdc9tPSGJt4PSjVKZPeCwIN36bYnnGzNTjkhSuHuxq.wjAX/Ra', 'mrkpulsrmnt12@gmail.com', '2026-03-23 10:01:18');

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
(5, 1, 'f043c440a84a01a1cf8082993ff81ac19685a9a1004bba1ffa70e73e09bb8363', '2026-03-23 11:20:33', NULL, 1);

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
