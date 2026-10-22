-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2026 at 08:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30
drop database if exists `ecommerce`;
create database if not exists `ecommerce`;
use `ecommerce`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

CREATE TABLE `brands` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`, `description`, `logo`, `created_at`) VALUES
(1, 'Apple', 'Premium technology products.', '6a9b0b24ba1d3.png', '2026-09-04 17:47:23'),
(2, 'Samsung', 'Consumer electronics and smartphones.', '6a9b0b11afb24.png', '2026-09-04 17:47:23'),
(3, 'Dell', 'Computers, laptops and monitors.', '6a9b0b02218fe.png', '2026-09-04 17:47:23'),
(4, 'HP', 'Computers, laptops and accessories.', '6a9b0ae2ddf64.png', '2026-09-04 17:47:23'),
(5, 'Lenovo', 'Laptops and business computers.', '6a9b0af122678.png', '2026-09-04 17:47:23'),
(6, 'ASUS', 'Gaming and professional technology.', '6a9b076b9e2ea.png', '2026-09-04 17:47:23'),
(7, 'Acer', 'Computers and gaming products.', '6a9b0b62e2317.png', '2026-09-04 17:47:23'),
(8, 'Sony', 'Entertainment and audio technology.', '6a9b06c599266.png', '2026-09-04 17:47:23'),
(9, 'Logitech', 'Computer accessories and peripherals.', '6a9b0ad13e1bf.png', '2026-09-04 17:47:23'),
(10, 'JBL', 'Audio products and speakers.', '6a9b0aae4dcf3.png', '2026-09-04 17:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `created_at`) VALUES
(1, 'Laptops', 'Powerful laptops for work, gaming and study.', '6a9b09622327a.png', '2026-09-04 17:47:23'),
(2, 'Smartphones', 'Latest smartphones and mobile devices.', '6a9b090b016b4.png', '2026-09-04 17:47:23'),
(3, 'Headphones', 'Wireless and wired headphones and earbuds.', '6a9b0a0dabaa4.png', '2026-09-04 17:47:23'),
(4, 'Monitors', 'Professional and gaming monitors.', '6a9b097cb7f88.png', '2026-09-04 17:47:23'),
(5, 'Keyboards', 'Mechanical and wireless keyboards.', '6a9b091a7f725.png', '2026-09-04 17:47:23'),
(6, 'Mouses', 'Gaming and professional computer mouse.', '6a9b085c906d8.png', '2026-09-04 17:47:23'),
(7, 'Accessories', 'Useful technology accessories.', '6a9b08b26a518.png', '2026-09-04 17:47:23'),
(8, 'Gaming', 'Gaming products and equipment.', '6a9b07fb504f6.png', '2026-09-04 17:47:23'),
(9, 'Tablets', 'Tablets for work, entertainment and study.', '6a9b07bf6d3e6.png', '2026-09-04 17:47:23'),
(10, 'Smartwatches', 'Smart watches and wearable devices.', '6a9b06f57ab8d.png', '2026-09-04 17:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `user_id`, `phone`, `address`, `city`, `created_at`) VALUES
(1, 1, '01011111111', '15 Tahrir Street', 'Cairo', '2026-09-04 17:47:23'),
(2, 2, '01022222222', '25 Abbas El Akkad', 'Cairo', '2026-09-04 17:47:23'),
(3, 3, '01033333333', '10 El Geish Street', 'Alexandria', '2026-09-04 17:47:23'),
(4, 4, '01044444444', '20 El Haram Street', 'Giza', '2026-09-04 17:47:23'),
(5, 5, '01055555555', '8 Nasr City Street', 'Cairo', '2026-09-04 17:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `client_id`, `total_price`, `status`, `created_at`) VALUES
(1, 1, 42999.00, 'delivered', '2026-08-05 17:48:22'),
(2, 1, 16999.00, 'delivered', '2026-08-08 17:48:22'),
(3, 1, 7498.00, 'delivered', '2026-08-11 17:48:22'),
(4, 1, 5999.00, 'delivered', '2026-08-14 17:48:22'),
(5, 1, 24999.00, 'delivered', '2026-08-17 17:48:22'),
(6, 1, 39999.00, 'delivered', '2026-08-20 17:48:22'),
(7, 1, 8999.00, 'delivered', '2026-08-23 17:48:22'),
(8, 1, 5499.00, 'delivered', '2026-08-25 17:48:22'),
(9, 1, 39999.00, 'shipped', '2026-08-30 17:48:22'),
(10, 1, 8999.00, 'shipped', '2026-08-31 17:48:22'),
(11, 1, 4999.00, 'processing', '2026-09-02 17:48:22'),
(12, 1, 18999.00, 'processing', '2026-09-03 17:48:22'),
(13, 1, 2499.00, 'pending', '2026-09-04 17:48:22'),
(14, 1, 12999.00, 'pending', '2026-09-04 17:48:22'),
(15, 1, 54999.00, 'cancelled', '2026-08-25 17:48:22'),
(16, 1, 6999.00, 'cancelled', '2026-08-23 17:48:22'),
(17, 1, 3999.00, 'cancelled', '2026-08-21 17:48:22'),
(18, 1, 22999.00, 'cancelled', '2026-08-19 17:48:22'),
(19, 1, 16497.00, 'delivered', '2026-08-27 17:48:22'),
(20, 1, 12997.00, 'processing', '2026-09-01 17:48:22');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 8, 1, 42999.00),
(2, 2, 11, 1, 16999.00),
(3, 3, 20, 1, 2499.00),
(4, 3, 26, 1, 2999.00),
(5, 3, 28, 1, 999.00),
(6, 3, 29, 1, 899.00),
(7, 3, 30, 1, 1299.00),
(8, 4, 12, 1, 5999.00),
(9, 5, 3, 1, 23999.00),
(10, 5, 28, 1, 999.00),
(11, 6, 6, 1, 39999.00),
(12, 7, 17, 1, 8999.00),
(13, 8, 24, 1, 5499.00),
(14, 9, 6, 1, 39999.00),
(15, 10, 17, 1, 8999.00),
(16, 11, 32, 1, 4999.00),
(17, 12, 9, 1, 18999.00),
(18, 13, 20, 1, 2499.00),
(19, 14, 13, 1, 12999.00),
(20, 15, 7, 1, 54999.00),
(21, 16, 14, 1, 6999.00),
(22, 17, 32, 1, 3999.00),
(23, 18, 2, 1, 22999.00),
(24, 19, 12, 1, 5999.00),
(25, 19, 20, 1, 2499.00),
(26, 19, 29, 1, 899.00),
(27, 19, 30, 1, 1299.00),
(28, 19, 28, 1, 999.00),
(29, 19, 31, 1, 4999.00),
(30, 20, 21, 1, 4999.00),
(31, 20, 22, 1, 3999.00),
(32, 20, 35, 1, 3999.00);

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`id`, `name`, `logo`, `website`, `created_at`) VALUES
(1, 'Tech Partner', '6a9b0668c5e37.webp', 'https://example.com', '2026-09-04 17:47:23'),
(2, 'Digital Solutions', '6a9b065fead40.png', 'https://example.com', '2026-09-04 17:47:23'),
(3, 'Smart Technology', '6a9b065487c58.webp', 'https://example.com', '2026-09-04 17:47:23'),
(4, 'Future Tech', '6a9b064993bd4.png', 'https://example.com', '2026-09-04 17:47:23'),
(5, 'Global Electronics', '6a9b063555e58.webp', 'https://example.com', '2026-09-04 17:47:23'),
(6, 'Innovation Hub', '6a9b075ba598c.png', 'https://example.com', '2026-09-04 17:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `brand_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `brand_id`, `name`, `description`, `price`, `stock`, `image`, `created_at`) VALUES
(1, 1, 3, 'Dell Inspiron 15', '15-inch laptop suitable for work and study.', 24999.00, 15, '6a9b134527c1b.png', '2026-09-04 17:47:23'),
(2, 1, 4, 'HP Pavilion 15', 'Reliable laptop for everyday productivity.', 22999.00, 20, '6a9b12fc5e1ee.png', '2026-09-04 17:47:23'),
(3, 1, 5, 'Lenovo IdeaPad 5', 'Slim laptop with excellent performance.', 23999.00, 12, 'lenovo-ideapad.jpg', '2026-09-04 17:47:23'),
(4, 1, 6, 'ASUS VivoBook 15', 'Lightweight laptop for students and professionals.', 21999.00, 18, '6a9b12cb72f6e.png', '2026-09-04 17:47:23'),
(5, 1, 7, 'Acer Aspire 5', 'Affordable laptop with strong performance.', 19999.00, 10, '6a9b128d190d4.png', '2026-09-04 17:47:23'),
(6, 2, 1, 'iPhone 15', 'Apple smartphone with advanced camera system.', 39999.00, 8, 'iphone15.jpg', '2026-09-04 17:47:23'),
(7, 2, 1, 'iPhone 15 Pro', 'Premium Apple smartphone with Pro features.', 54999.00, 5, '6a9b123970ab6.png', '2026-09-04 17:47:23'),
(8, 2, 2, 'Samsung Galaxy S24', 'Flagship Samsung smartphone.', 42999.00, 10, 'galaxy-s24.jpg', '2026-09-04 17:47:23'),
(9, 2, 2, 'Samsung Galaxy A55', 'Mid-range smartphone with excellent display.', 18999.00, 25, '6a9b120179012.png', '2026-09-04 17:47:23'),
(10, 2, 2, 'Samsung Galaxy S24 Ultra', 'Premium flagship smartphone with powerful camera.', 59999.00, 4, '6a9b131c87274.png', '2026-09-04 17:47:23'),
(11, 3, 8, 'Sony WH-1000XM5', 'Premium wireless noise cancelling headphones.', 16999.00, 7, '6a9b12e5999c8.png', '2026-09-04 17:47:23'),
(12, 3, 10, 'JBL Tune 770NC', 'Wireless noise cancelling headphones.', 5999.00, 20, '6a9b12a5de029.png', '2026-09-04 17:47:23'),
(13, 3, 8, 'Sony WF-1000XM5', 'Premium wireless noise cancelling earbuds.', 13999.00, 9, '6a9b1254bc57a.png', '2026-09-04 17:47:23'),
(14, 3, 10, 'JBL Live 660NC', 'Comfortable wireless headphones.', 7499.00, 14, '6a9b1219a6c0a.png', '2026-09-04 17:47:23'),
(15, 3, 9, 'Logitech Zone Vibe 100', 'Wireless headset for work and calls.', 6999.00, 11, '6a9b113544515.png', '2026-09-04 17:47:23'),
(16, 4, 3, 'Dell 24 Inch Monitor', 'Full HD monitor for work and entertainment.', 6999.00, 16, '6a9b10fecb3cb.png', '2026-09-04 17:47:23'),
(17, 4, 4, 'HP 27 Inch Monitor', '27-inch Full HD professional monitor.', 8999.00, 12, '6a9b1097c1e62.png', '2026-09-04 17:47:23'),
(18, 4, 6, 'ASUS TUF Gaming 27', 'Gaming monitor with high refresh rate.', 12999.00, 6, '6a9b105084b20.png', '2026-09-04 17:47:23'),
(19, 4, 7, 'Acer Nitro 24', 'Fast gaming monitor with excellent response time.', 10999.00, 8, '6a9b100143192.png', '2026-09-04 17:47:23'),
(20, 5, 9, 'Logitech K380', 'Compact wireless keyboard.', 2499.00, 30, '6a9b1190cc614.png', '2026-09-04 17:47:23'),
(21, 5, 9, 'Logitech MX Keys', 'Premium wireless productivity keyboard.', 4999.00, 15, '6a9b115d3a28d.png', '2026-09-04 17:47:23'),
(22, 5, 6, 'ASUS Mechanical Keyboard', 'RGB mechanical gaming keyboard.', 3999.00, 10, '6a9b1114005f6.png', '2026-09-04 17:47:23'),
(23, 5, 7, 'Acer Gaming Keyboard', 'Affordable RGB gaming keyboard.', 2999.00, 18, '6a9b10ac13dbe.png', '2026-09-04 17:47:23'),
(24, 6, 9, 'Logitech MX Master 3S', 'Professional wireless mouse.', 5499.00, 13, '6a9b101757fca.png', '2026-09-04 17:47:23'),
(25, 6, 9, 'Logitech G502 Hero', 'Popular wired gaming mouse.', 3499.00, 20, '6a9b0f5d46018.png', '2026-09-04 17:47:23'),
(26, 6, 6, 'ASUS ROG Gaming Mouse', 'High-performance gaming mouse.', 2999.00, 9, '6a9b0f1c22a40.png', '2026-09-04 17:47:23'),
(27, 6, 7, 'Acer Gaming Mouse', 'RGB gaming mouse with ergonomic design.', 1999.00, 15, '6a9b0edb3bb51.png', '2026-09-04 17:47:23'),
(28, 7, 9, 'Logitech Wireless Adapter', 'Wireless USB adapter.', 999.00, 25, '6a9b0e3a0d51c.png', '2026-09-04 17:47:23'),
(29, 7, 2, 'Samsung 25W Charger', 'Fast USB-C smartphone charger.', 899.00, 35, '6a9b0e86313fb.png', '2026-09-04 17:47:23'),
(30, 7, 1, 'Apple USB-C Cable', 'Original USB-C charging cable.', 1299.00, 40, '6a9b0fb41ddcb.png', '2026-09-04 17:47:23'),
(31, 7, 8, 'Sony Portable Speaker', 'Compact wireless Bluetooth speaker.', 4999.00, 12, '6a9b0f77b4df4.png', '2026-09-04 17:47:23'),
(32, 8, 6, 'ASUS ROG Gaming Headset', 'Gaming headset with surround sound.', 4999.00, 8, '6a9b0f3878002.png', '2026-09-04 17:47:23'),
(33, 8, 6, 'ASUS ROG Gaming Monitor', 'High refresh rate gaming monitor.', 18999.00, 5, '6a9b0f06c5b12.png', '2026-09-04 17:47:23'),
(34, 8, 7, 'Acer Gaming Laptop', 'Powerful gaming laptop.', 44999.00, 3, '6a9b0e28d28ff.png', '2026-09-04 17:47:23'),
(35, 8, 9, 'Logitech Gaming Headset', 'Gaming headset with clear microphone.', 3999.00, 14, '6a9b0cd42448e.png', '2026-09-04 17:47:23'),
(36, 9, 1, 'iPad 10th Generation', 'Modern tablet for study and entertainment.', 22999.00, 9, '6a9b0ca026ebe.png', '2026-09-04 17:47:23'),
(37, 9, 2, 'Samsung Galaxy Tab S9', 'Premium Android tablet.', 24999.00, 6, '6a9b0c8a3ab54.png', '2026-09-04 17:47:23'),
(38, 9, 1, 'iPad Air', 'Powerful lightweight tablet.', 32999.00, 5, '6a9b0c33e606b.png', '2026-09-04 17:47:23'),
(39, 9, 2, 'Samsung Galaxy Tab A9', 'Affordable tablet for everyday use.', 8999.00, 15, '6a9b0bcc7c628.png', '2026-09-04 17:47:23'),
(40, 10, 1, 'Apple Watch Series 9', 'Smartwatch with health and fitness features.', 18999.00, 7, '6a9b0d2a9bbbe.png', '2026-09-04 17:47:23'),
(41, 10, 2, 'Samsung Galaxy Watch 6', 'Smartwatch with advanced health tracking.', 9999.00, 10, '6a9b0d8df11da.png', '2026-09-04 17:47:23'),
(42, 1, 6, 'ASUS ROG Zephyrus G14', 'Premium compact gaming laptop.', 59999.00, 0, '6a9b0c1b64e53.png', '2026-09-04 17:47:23'),
(43, 2, 1, 'iPhone 15 Pro Max', 'Premium Apple flagship smartphone.', 64999.00, 0, '6a9b0be707b10.png', '2026-09-04 17:47:23'),
(44, 3, 8, 'Sony WH-1000XM4', 'Wireless noise cancelling headphones.', 12999.00, 0, '6a9b0cf9b5cfd.png', '2026-09-04 17:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `team`
--

CREATE TABLE `team` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team`
--

INSERT INTO `team` (`id`, `name`, `position`, `description`, `image`, `facebook`, `instagram`, `linkedin`, `created_at`) VALUES
(1, 'mariam mohamed', 'CEO & Founder', 'Leading the company and business strategy.', '6a9b061769859.webp', 'https://facebook.com/', 'https://instagram.com/', 'https://linkedin.com/', '2026-09-04 17:47:23'),
(2, 'Mohamed Ali', 'Full Stack Developer', 'Responsible for backend and frontend development.', '6a9b05f28e82f.webp', 'https://facebook.com/', 'https://instagram.com/', 'https://linkedin.com/', '2026-09-04 17:47:23'),
(3, 'Omar Khaled', 'UI/UX Designer', 'Designing modern and user-friendly interfaces.', '6a9b05e2e3923.webp', 'https://facebook.com/', 'https://instagram.com/', 'https://linkedin.com/', '2026-09-04 17:47:23'),
(4, 'Youssef Mahmoud', 'Marketing Manager', 'Managing marketing campaigns and branding.', '6a9b05da40843.webp', 'https://facebook.com/', 'https://instagram.com/', 'https://linkedin.com/', '2026-09-04 17:47:23'),
(5, 'Ali Mostafa', 'Product Manager', 'Managing products and customer experience.', '6a9b05d0400dc.webp', 'https://facebook.com/', 'https://instagram.com/', 'https://linkedin.com/', '2026-09-04 17:47:23'),
(6, 'Sara Ahmed', 'Customer Support', 'Helping customers with orders and questions.', '6a9b05c7decd4.webp', 'https://facebook.com/', 'https://instagram.com/', 'https://linkedin.com/', '2026-09-04 17:47:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Client','Staff') NOT NULL DEFAULT 'Client',
  `two_factor_secret` varchar(255) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `two_factor_backup_codes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(150) NOT NULL,
  `attempted_at` datetime NOT NULL,
  `is_success` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Ahmed Hassan', 'ahmed@example.com', '$2y$12$/reE.O/fRuM9YvAY69v9ke/1YjGBc.2vz3ihS/fJY4bqJZvzzQZb6', 'Client', '2026-09-04 17:47:23'),
(2, 'Mohamed Ali', 'mohamed@example.com', '$2y$12$/reE.O/fRuM9YvAY69v9ke/1YjGBc.2vz3ihS/fJY4bqJZvzzQZb6', 'Client', '2026-09-04 17:47:23'),
(3, 'Omar Khaled', 'omar@example.com', '$2y$12$/reE.O/fRuM9YvAY69v9ke/1YjGBc.2vz3ihS/fJY4bqJZvzzQZb6', 'Client', '2026-09-04 17:47:23'),
(4, 'Youssef Mahmoud', 'youssef@example.com', '$2y$12$/reE.O/fRuM9YvAY69v9ke/1YjGBc.2vz3ihS/fJY4bqJZvzzQZb6', 'Client', '2026-09-04 17:47:23'),
(5, 'Ali Mostafa', 'ali@example.com', '$2y$12$/reE.O/fRuM9YvAY69v9ke/1YjGBc.2vz3ihS/fJY4bqJZvzzQZb6', 'Client', '2026-09-04 17:47:23'),
(6, 'Admin User', 'admin@shopease.com', '$2y$12$/reE.O/fRuM9YvAY69v9ke/1YjGBc.2vz3ihS/fJY4bqJZvzzQZb6', 'Admin', '2026-09-04 17:47:23'),
(7, 'Staff User', 'staff@shopease.com', '$2y$12$/reE.O/fRuM9YvAY69v9ke/1YjGBc.2vz3ihS/fJY4bqJZvzzQZb6', 'Staff', '2026-09-04 17:47:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_client` (`client_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`),
  ADD KEY `fk_products_brand` (`brand_id`);

--
-- Indexes for table `team`
--
ALTER TABLE `team`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_attempt` (`ip_address`, `attempted_at`),
  ADD KEY `idx_email_attempt` (`email`, `attempted_at`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_user_expires` (`user_id`, `expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `team`
--
ALTER TABLE `team`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `fk_clients_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
