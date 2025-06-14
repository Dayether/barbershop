-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 28, 2025 at 05:07 PM
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
-- Database: `barbershop`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `au_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`au_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`au_id`, `user_id`, `created_at`) VALUES
(1, 6, '2025-05-13 17:21:29');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_reference` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `service_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` varchar(10) NOT NULL,
  `barber_id` int(11) DEFAULT NULL,
  `client_name` varchar(100) NOT NULL,
  `client_email` varchar(100) NOT NULL,
  `client_phone` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`appointment_id`),
  UNIQUE KEY `booking_reference` (`booking_reference`),
  KEY `user_id` (`user_id`),
  KEY `service_id` (`service_id`),
  KEY `barber_id` (`barber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `booking_reference`, `user_id`, `service_id`, `appointment_date`, `appointment_time`, `barber_id`, `client_name`, `client_email`, `client_phone`, `notes`, `status`, `created_at`) VALUES
(1, 'TIPD382CD1A', 2, 1, '2025-05-13', '09:00', 'John', 'cjhoy agno', 'cjhoyagno941@gmail.com', '09911776722', '123124124', 'pending', '2025-05-11 12:38:17'),
(2, 'TIPD25B74F3', 2, 1, '2025-05-13', '09:30', 'John', 'Cath Agno', 'cjhoyagno941@gmail.com', '09911776722', '123123', 'pending', '2025-05-11 13:44:54'),
(3, 'TIP5012D7FA', 2, 2, '2025-05-17', '11:00', 'David', 'Cath Agno', 'cjhoyagno941@gmail.com', '09911776722', '123123', 'pending', '2025-05-11 13:46:07'),
(4, 'TIP14DE2C29', 1, 3, '2025-05-14', '11:00', 'David', 'ivan alc', 'alcantaraivan2003@gmail.com', '09911776722', 'make it sexy', 'cancelled', '2025-05-13 14:50:02'),
(5, 'TIP589FBBEC', 1, 1, '2025-05-14', '09:00', 'Robert', 'ivan alc', 'alcantaraivan2003@gmail.com', '09911776722', 'none', 'pending', '2025-05-13 15:27:16'),
(6, 'TIPD7FEB3C9', 1, 1, '2025-05-31', '09:00:00', '8', 'ivan alc', 'alcantaraivan2003@gmail.com', '09911776722', 'none', 'cancelled', '2025-05-13 15:28:52'),
(7, 'TIP06296D52', 1, 2, '2025-05-14', '16:30', '', 'ivan alc', 'alcantaraivan2003@gmail.com', '09911776722', 'test', 'pending', '2025-05-13 15:31:30'),
(8, 'TIP8270D257', 1, 2, '2025-05-17', '10:00', 'John', 'ivan loop', 'alcantaraivan2003@gmail.com', '09911776722', 'testcheck', 'pending', '2025-05-13 17:33:03'),
(9, 'TIPBBC8340B', 7, 3, '2025-05-28', '11:30', 'Michael', 'abcde agno', 'abcde@gmail.com', '09911776722', 'kalbo', 'pending', '2025-05-28 12:01:17'),
(10, 'TIP3015FE0A', 7, 3, '2025-05-28', '12:00', 'Michael', 'abcde agno', 'abcde@gmail.com', '09911776722', 'kalbo', 'pending', '2025-05-28 12:01:45');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_history`
--

CREATE TABLE `appointment_history` (
  `ah_id` int(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` int(11) NOT NULL,
  `action` enum('create','reschedule','cancel','complete','no-show') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`ah_id`),
  KEY `appointment_id` (`appointment_id`),
  KEY `user_id` (`user_id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointment_history`
--

INSERT INTO `appointment_history` (`ah_id`, `appointment_id`, `action`, `notes`, `created_at`, `user_id`, `staff_id`) VALUES
(1, 6, 'reschedule', 'Appointment rescheduled by customer to May 31, 2025 at 9:00 AM', '2025-05-14 00:24:50', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `barbers`
--

CREATE TABLE `barbers` (
  `barber_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `bio` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`barber_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barbers`
--

INSERT INTO `barbers` (`barber_id`, `name`, `bio`, `image`, `active`) VALUES
(1, 'John', 'Master barber with over 10 years of experience', 'uploads/barbers/john.jpg', 1),
(2, 'Michael', 'Specializes in modern styles and beard sculpting', 'uploads/barbers/michael.jpg', 1),
(3, 'David', 'Expert in classic cuts and traditional hot towel shaves', 'uploads/barbers/david.jpg', 1),
(4, 'Robert', 'Award-winning barber with a passion for precision', 'uploads/barbers/robert.jpg', 1),
(5, 'John', 'Master barber with over 10 years of experience', 'uploads/barbers/john.jpg', 1),
(6, 'Michael', 'Specializes in modern styles and beard sculpting', 'uploads/barbers/michael.jpg', 1),
(7, 'David', 'Expert in classic cuts and traditional hot towel shaves', 'uploads/barbers/david.jpg', 1),
(8, 'Robert', 'Award-winning barber with a passion for precision', 'uploads/barbers/robert.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `status`, `created_at`) VALUES
(3, 'ALEA SHANE ALCANTARA', 'alcantaraivan2003@gmail.com', '09911776722', 'wewewe', 'asdasd', 'new', '2025-05-13 14:17:01'),
(4, 'ALEA SHANE ALCANTARA', 'alcantaraivan2003@gmail.com', '09911776722', 'wewewe', 'asdasd', 'new', '2025-05-13 14:20:26'),
(5, 'ALEA SHANE ALCANTARA', 'alcantaraivan2003@gmail.com', '09911776722', 'wewewe', 'asdasd', 'new', '2025-05-13 14:25:02'),
(6, 'Cath Agno', 'cjhoyagno941@gmail.com', '09911776722', 'Pricing Question', 'ily', 'new', '2025-05-13 16:53:53'),
(7, 'Cath Agno', 'alcantaraivan2003@gmail.com', '09911776722', 'Appointment Request', 'bini', 'new', '2025-05-13 17:33:41');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_reference` varchar(50) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `city` varchar(100) NOT NULL,
  `zip` varchar(20) NOT NULL,
  `country` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `payment_method` varchar(32) NOT NULL DEFAULT 'credit_card',
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_reference`, `user_id`, `total_amount`, `status`, `created_at`, `first_name`, `last_name`, `email`, `address`, `city`, `zip`, `country`, `phone`) VALUES
(3, 'ORD-6820BCBA446D', 3, 23.00, 'pending', '2025-05-11 15:05:30', 'testvan', '1asdasd', 'alcantarafinals@gmail.com', 'lipa', 'tamnbo', '123412', 'USA', '12323213'),
(4, 'ORD-6820BD387A56', 4, 29.00, 'pending', '2025-05-11 15:07:36', 'alea', 'ALCANTARA', 'aleash@gmail.com', 'LIPA', 'LIPA CITY', '4217', 'IN', '09911776722'),
(5, 'ORD-6820BE8CEACD', 4, 29.00, 'pending', '2025-05-11 15:13:16', 'alea', '12312', 'aleash@gmail.com', 'LIPA', 'LIPA CITY', '4217', 'USA', '09911776722'),
(6, 'ORD-6822F47AE1A8', 1, 51.00, 'pending', '2025-05-13 07:27:54', 'ivan', 'alc', 'alcantaraivan2003@gmail.com', 'LIPA', 'LIPA CITY', '4217', 'AU', '09911776722'),
(7, 'ORD-6822F4F5EA1A', 1, 51.00, 'pending', '2025-05-13 07:29:57', 'ivan', 'alc', 'alcantaraivan2003@gmail.com', 'LIPA', 'LIPA CITY', '4217', 'AU', '09911776722'),
(8, 'ORD-6822F50A8EEB', 1, 51.00, 'pending', '2025-05-13 07:30:18', 'ivan', 'alc', 'alcantaraivan2003@gmail.com', 'LIPA', 'LIPA CITY', '4217', 'CA', '09911776722'),
(9, 'ORD-6822F68343AE', 1, 75.00, 'cancelled', '2025-05-13 07:36:35', 'ivan', 'alc', 'alcantaraivan2003@gmail.com', 'LIPA', 'LIPA CITY', '4217', 'AU', '09911776722'),
(10, 'ORD-6822F6FD74D0', 1, 75.00, 'cancelled', '2025-05-13 07:38:37', 'ashley', 'alc', 'alcantaraivan2003@gmail.com', 'bauan', 'batangas city', '4217', 'AU', '09911776733'),
(11, 'ORD-682367222D9D', 1, 23.00, 'cancelled', '2025-05-13 15:37:06', 'ivan', 'alc', 'alcantaraivan2003@gmail.com', 'LIPA', 'LIPA CITY', '4217', 'AU', '09911776722'),
(12, 'ORD-6823695EBF84', 1, 59.00, 'cancelled', '2025-05-13 15:46:38', 'ivan', 'alc', 'alcantaraivan2003@gmail.com', 'LIPA', 'LIPA CITY', '4217', 'CA', '09911776722'),
(13, 'ORD-682376A33C49', 1, 83.00, 'cancelled', '2025-05-13 16:43:15', 'ivan', 'alc', 'alcantaraivan2003@gmail.com', 'LIPA', 'LIPA CITY', '4217', 'USA', '09911776722'),
(14, 'ORD-682382313A52', 1, 105.00, 'pending', '2025-05-13 17:32:33', 'ivan', 'loop', 'alcantaraivan2003@gmail.com', 'LIPA', 'LIPA CITY', '4217', 'CA', '09911776722'),
(15, 'ORD-6836FB782BD7', 7, 151.00, 'pending', '2025-05-28 12:03:04', 'abcde', 'agno', 'abcde@gmail.com', 'LIPA', 'LIPA CITY', '4217', 'USA', '09911776722');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `name`, `quantity`, `price`) VALUES
(1, 3, 2, 'Beard Oil', 1, 18.00),
(2, 4, 3, 'Matte Clay', 1, 24.00),
(3, 5, 3, 'Matte Clay', 1, 24.00),
(4, 6, 3, 'Matte Clay', 1, 24.00),
(5, 6, 1, 'Premium Pomade', 1, 22.00),
(6, 7, 3, 'Matte Clay', 1, 24.00),
(7, 7, 1, 'Premium Pomade', 1, 22.00),
(8, 8, 3, 'Matte Clay', 1, 24.00),
(9, 8, 1, 'Premium Pomade', 1, 22.00),
(10, 9, 3, 'Matte Clay', 2, 24.00),
(11, 9, 1, 'Premium Pomade', 1, 22.00),
(12, 10, 3, 'Matte Clay', 2, 24.00),
(13, 10, 1, 'Premium Pomade', 1, 22.00),
(14, 11, 2, 'Beard Oil', 1, 18.00),
(15, 12, 2, 'Beard Oil', 3, 18.00),
(16, 13, 2, 'Beard Oil', 3, 18.00),
(17, 13, 3, 'Matte Clay', 1, 24.00),
(18, 14, 2, 'Beard Oil', 3, 18.00),
(19, 14, 3, 'Matte Clay', 1, 24.00),
(20, 14, 1, 'Premium Pomade', 1, 22.00),
(21, 15, 2, 'Beard Oil', 3, 18.00),
(22, 15, 3, 'Matte Clay', 1, 24.00),
(23, 15, 1, 'Premium Pomade', 2, 22.00),
(24, 15, 6, 'Shaving Cream', 2, 12.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `description`, `price`, `image`, `stock`, `active`) VALUES
(1, 'Premium Pomade', 'Medium hold with high shine for classic styles.', 22.00, 'uploads/products/pomade.jpg', 42, 1),
(2, 'Beard Oil', 'Nourishing oil for soft, manageable beard hair.', 18.00, 'uploads/products/beard-oil.jpg', 16, 1),
(3, 'Matte Clay', 'Strong hold with no shine for textured styles.', 24.00, 'uploads/products/matte-clay.jpg', 28, 1),
(4, 'Beard Balm', 'Conditioning balm for styling and taming beard hair.', 20.00, 'uploads/products/beard-balm.jpg', 25, 1),
(5, 'Hair Spray', 'Flexible hold for all-day style.', 15.00, 'uploads/products/hair-spray.jpg', 60, 1),
(6, 'Shaving Cream', 'Rich lather for a smooth, comfortable shave.', 12.00, 'uploads/products/shaving-cream.jpg', 33, 1);

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `name`, `description`, `duration`, `price`, `image`, `active`) VALUES
(1, 'Classic Haircut', 'Precision cuts tailored to your style', 45, 30.00, 'uploads/services/haircut.jpg', 1),
(2, 'Beard Trim', 'Expert grooming for a sharp look', 30, 25.00, 'uploads/services/beard.jpg', 1),
(3, 'Hot Towel Shave', 'Relax with a traditional shave experience', 45, 35.00, 'uploads/services/shave.jpg', 1),
(4, 'Complete Package', 'Haircut, beard trim, and hot towel shave', 90, 75.00, 'uploads/services/package.jpg', 1),
(5, 'Classic Haircut', 'Precision cuts tailored to your style', 45, 30.00, 'uploads/services/haircut.jpg', 1),
(6, 'Beard Trim', 'Expert grooming for a sharp look', 30, 25.00, 'uploads/services/beard.jpg', 1),
(7, 'Hot Towel Shave', 'Relax with a traditional shave experience', 45, 35.00, 'uploads/services/shave.jpg', 1),
(8, 'Complete Package', 'Haircut, beard trim, and hot towel shave', 90, 75.00, 'uploads/services/package.jpg', 1),
(9, 'Classic Haircut', 'Precision cuts tailored to your style', 45, 30.00, 'uploads/services/haircut.jpg', 1),
(10, 'Beard Trim', 'Expert grooming for a sharp look', 30, 25.00, 'uploads/services/beard.jpg', 1),
(11, 'Hot Towel Shave', 'Relax with a traditional shave experience', 45, 35.00, 'uploads/services/shave.jpg', 1),
(12, 'Complete Package', 'Haircut, beard trim, and hot towel shave', 90, 75.00, 'uploads/services/package.jpg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT 'uploads/default-profile.jpg',
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_type` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password`, `profile_pic`, `phone`, `created_at`, `account_type`) VALUES
(1, 'ivan', 'loop', 'alcantaraivan2003@gmail.com', '$2y$10$xFcFIMG1ltsnjihYwCuc8ODMh2HReoApYqWS9j3nKh5F7O2zAiFai', 'uploads/profiles/profile_1_1747121145.jpg', '', '2025-05-11 12:28:25', '1'),
(2, 'Cath', 'Agno', 'cjhoyagno941@gmail.com', '$2y$10$DlLggsLsVVIbiqj2HL9zLOFdIomET2bR8R/XoPyvZYQokzRqIuRKi', 'uploads/profiles/profile_2_1746966826.jpg', NULL, '2025-05-11 12:31:54', '0'),
(3, 'test', 'van', 'alcantarafinals@gmail.com', '$2y$10$5wFo0bPl7Y0dJEoXRgmf4uuEz0h5Pf7ohehZIvYLPbdrMMrS2Rnzi', 'images/default-profile.png', NULL, '2025-05-11 14:49:17', NULL),
(4, 'alea', 'ALCANTARA', 'aleash@gmail.com', '$2y$10$DsMYUWFowBnvO.7FR6R/I.8bekPQJZOaZou9.OBvfwMdoLz84en1m', 'uploads/profiles/profile_4_1746978623.jpg', '', '2025-05-11 14:52:02', NULL),
(5, 'ranuel', 'viray', 'superviray@gmail.com', '$2y$10$BFyIitZAUwqW4xEBwt33dO9NBtWrgfx0qtzXB0yzlr0ebeBjtft5y', 'images/default-profile.png', NULL, '2025-05-13 07:43:43', NULL),
(6, 'Admin', 'User', 'admin@tipunobarbershop.com', 'password', 'uploads/default-profile.jpg', NULL, '2025-05-13 17:11:37', '1'),
(7, 'abcde', 'agno', 'abcde@gmail.com', '$2y$10$t1.cUxIZazzwzzdET0pmvOf3cT1OtY//e9b5y9MbpMVE3/ADOQJk2', 'uploads/profiles/profile_7_1748433283.jpg', '', '2025-05-28 11:53:43', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`au_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD UNIQUE KEY `booking_reference` (`booking_reference`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `barber_id` (`barber_id`);

--
-- Indexes for table `appointment_history`
--
ALTER TABLE `appointment_history`
  ADD PRIMARY KEY (`ah_id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `barbers`
--
ALTER TABLE `barbers`
  ADD PRIMARY KEY (`barber_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `au_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `appointment_history`
--
ALTER TABLE `appointment_history`
  MODIFY `ah_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `barbers`
--
ALTER TABLE `barbers`
  MODIFY `barber_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD CONSTRAINT `admin_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`service_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`barber_id`) ON DELETE SET NULL;

--
-- Constraints for table `appointment_history`
--
ALTER TABLE `appointment_history`
  ADD CONSTRAINT `appointment_history_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Fix invalid barber_id values in appointments
UPDATE appointments SET barber_id = 1 WHERE barber_id = 'John';
UPDATE appointments SET barber_id = 2 WHERE barber_id = 'Michael';
UPDATE appointments SET barber_id = 3 WHERE barber_id = 'David';
UPDATE appointments SET barber_id = 4 WHERE barber_id = 'Robert';
UPDATE appointments SET barber_id = NULL WHERE barber_id = '';

-- Trigger: When product price changes, update order_items price for that product
DELIMITER $$
CREATE TRIGGER trg_update_order_items_price
AFTER UPDATE ON products
FOR EACH ROW
BEGIN
    IF NEW.price <> OLD.price THEN
        UPDATE order_items
        SET price = NEW.price
        WHERE product_id = NEW.product_id;
    END IF;
END$$
DELIMITER ;

-- Trigger: When order_items price or quantity changes, update orders total_amount
DELIMITER $$
CREATE TRIGGER trg_update_order_total_on_item_update
AFTER UPDATE ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders
    SET total_amount = (
        SELECT IFNULL(SUM(price * quantity), 0)
        FROM order_items
        WHERE order_id = NEW.order_id
    ) + 5.00 + (
        SELECT IFNULL(SUM(price * quantity), 0) * 0.08
        FROM order_items
        WHERE order_id = NEW.order_id
    )
    WHERE order_id = NEW.order_id;
END$$
DELIMITER ;

-- Trigger: When order_items are inserted, update orders total_amount
DELIMITER $$
CREATE TRIGGER trg_update_order_total_on_item_insert
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders
    SET total_amount = (
        SELECT IFNULL(SUM(price * quantity), 0)
        FROM order_items
        WHERE order_id = NEW.order_id
    ) + 5.00 + (
        SELECT IFNULL(SUM(price * quantity), 0) * 0.08
        FROM order_items
        WHERE order_id = NEW.order_id
    )
    WHERE order_id = NEW.order_id;
END$$
DELIMITER ;

-- Trigger: When order_items are deleted, update orders total_amount
DELIMITER $$
CREATE TRIGGER trg_update_order_total_on_item_delete
AFTER DELETE ON order_items
FOR EACH ROW
BEGIN
    UPDATE orders
    SET total_amount = (
        SELECT IFNULL(SUM(price * quantity), 0)
        FROM order_items
        WHERE order_id = OLD.order_id
    ) + 5.00 + (
        SELECT IFNULL(SUM(price * quantity), 0) * 0.08
        FROM order_items
        WHERE order_id = OLD.order_id
    )
    WHERE order_id = OLD.order_id;
END$$
DELIMITER ;

-- Decrease product stock when an order item is inserted
DELIMITER $$
CREATE TRIGGER trg_decrease_product_stock_after_order_item_insert
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock = stock - NEW.quantity
    WHERE product_id = NEW.product_id;
END$$
DELIMITER ;

-- Restore product stock when an order item is deleted
DELIMITER $$
CREATE TRIGGER trg_increase_product_stock_after_order_item_delete
AFTER DELETE ON order_items
FOR EACH ROW
BEGIN
    UPDATE products
    SET stock = stock + OLD.quantity
    WHERE product_id = OLD.product_id;
END$$
DELIMITER ;

-- Adjust product stock when an order item is updated (quantity changed)
DELIMITER $$
CREATE TRIGGER trg_adjust_product_stock_after_order_item_update
AFTER UPDATE ON order_items
FOR EACH ROW
BEGIN
    IF NEW.quantity <> OLD.quantity THEN
        UPDATE products
        SET stock = stock + (OLD.quantity - NEW.quantity)
        WHERE product_id = NEW.product_id;
    END IF;
END$$
DELIMITER ;
DELIMITER ;

ALTER TABLE `orders`
MODIFY COLUMN `user_id` INT NULL;

ALTER TABLE `orders`
    MODIFY COLUMN `user_id` INT NULL,
    DROP FOREIGN KEY `orders_ibfk_1`,
    ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
