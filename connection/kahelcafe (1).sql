-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Nov 20, 2024 at 02:52 PM
-- Server version: 10.1.9-MariaDB
-- PHP Version: 7.0.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kahelcafe`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_list`
--

CREATE TABLE `admin_list` (
  `id` int(11) NOT NULL,
  `username` varchar(55) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(55) NOT NULL,
  `role` varchar(55) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin_list`
--

INSERT INTO `admin_list` (`id`, `username`, `password`, `email`, `role`, `date_created`) VALUES
(8, 'admin', 'p', 'jewellsalongcong09@gmail.com', 'owner', '2024-10-30 10:25:19'),
(9, 'staff', 'p', 'jewellsalongcong09@gmail.com', 'staff', '2024-11-01 22:23:17');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `content` varchar(55) NOT NULL,
  `description` varchar(55) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `content`, `description`, `date_created`) VALUES
(56, './../../uploads/resumepic.png', 'Uploaded Image', '2024-11-06 09:01:21'),
(59, './../../uploads/uyuyui.jpg', 'Uploaded Image', '2024-11-08 07:12:35');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `size` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `temperature` varchar(255) NOT NULL,
  `added_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `item_id`, `quantity`, `size`, `price`, `temperature`, `added_at`) VALUES
(1, 83, 4, 1, '', 0, '', '2024-11-18 21:14:05'),
(2, 83, 4, 1, '', 0, '', '2024-11-18 21:19:40'),
(3, 83, 5, 1, '', 0, '', '2024-11-18 21:19:47'),
(4, 83, 5, 1, '', 0, '', '2024-11-18 21:21:12'),
(5, 83, 5, 1, '', 0, '', '2024-11-18 21:21:18'),
(6, 83, 5, 1, '', 0, '', '2024-11-18 21:32:22'),
(7, 83, 4, 1, '', 0, '', '2024-11-18 21:32:27'),
(8, 83, 4, 1, '', 0, '', '2024-11-18 21:35:06'),
(9, 83, 4, 1, '', 0, '', '2024-11-18 21:35:22'),
(10, 83, 4, 1, '', 0, '', '2024-11-18 21:35:51'),
(11, 83, 4, 1, '', 0, '', '2024-11-18 21:35:57'),
(12, 83, 4, 1, '', 0, '', '2024-11-18 21:37:16'),
(13, 83, 4, 1, '', 0, '', '2024-11-18 21:37:21'),
(14, 83, 4, 1, '', 0, '', '2024-11-18 21:38:02'),
(15, 83, 4, 1, '', 0, '', '2024-11-18 21:38:08'),
(16, 83, 4, 1, '', 0, '', '2024-11-18 21:39:31'),
(17, 83, 4, 1, '', 0, '', '2024-11-18 21:39:36'),
(18, 83, 4, 1, '', 48, '', '2024-11-18 21:40:46'),
(19, 83, 4, 1, '', 48, '', '2024-11-18 21:40:51'),
(20, 83, 3, 2, '', 44, '', '2024-11-18 21:41:05'),
(21, 83, 3, 1, '', 44, '', '2024-11-18 21:42:38'),
(22, 83, 4, 2, '', 48, '', '2024-11-18 21:52:20'),
(23, 83, 4, 2, '', 48, '', '2024-11-18 21:53:23'),
(24, 83, 4, 2, '', 48, '', '2024-11-18 21:53:27'),
(25, 83, 4, 2, '', 48, '', '2024-11-18 22:05:50'),
(26, 83, 3, 1, '', 44, '', '2024-11-18 22:08:33'),
(27, 83, 4, 6, '', 48, '', '2024-11-18 22:09:20'),
(28, 83, 4, 4, '', 0, '', '2024-11-18 22:12:10'),
(29, 83, 5, 5, '', 155, '', '2024-11-20 08:13:13'),
(30, 83, 4, 1, '', 48, '', '2024-11-20 12:20:02'),
(31, 83, 5, 9, '', 155, '', '2024-11-20 12:20:37');

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `id` int(11) NOT NULL,
  `firstname` varchar(55) NOT NULL,
  `lastname` varchar(55) NOT NULL,
  `email` varchar(55) NOT NULL,
  `contact_number` int(11) NOT NULL,
  `password` varchar(55) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verification_code` varchar(32) NOT NULL,
  `code_expiry` datetime NOT NULL,
  `verified` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`id`, `firstname`, `lastname`, `email`, `contact_number`, `password`, `created_at`, `verification_code`, `code_expiry`, `verified`) VALUES
(82, 'Duke', 'Duke', 'qkoxzxm946@khalisady.store', 2147483647, 'Duke123@12', '2024-11-05 02:04:32', 'a850339acd', '2024-11-04 19:06:32', 1),
(83, 'Jizzell', 'Salongcong', 'jewellsalongcong09@gmail.com', 2147483647, 'p', '2024-11-05 21:14:11', '461041eaed', '2024-11-05 14:16:11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `menu_name` varchar(255) NOT NULL,
  `menu_category` varchar(255) NOT NULL,
  `menu_size` text NOT NULL,
  `menu_price_small` decimal(10,2) NOT NULL,
  `menu_price_medium` decimal(10,2) NOT NULL,
  `menu_price_large` decimal(10,2) NOT NULL,
  `menu_quantity_small` int(11) NOT NULL,
  `menu_quantity_medium` int(11) NOT NULL,
  `menu_quantity_large` int(11) NOT NULL,
  `food_price` decimal(10,0) NOT NULL,
  `menu_temperature` text NOT NULL,
  `menu_quantity` int(11) NOT NULL,
  `product_status` enum('Available','Not Available') NOT NULL,
  `menu_image_path` varchar(255) NOT NULL,
  `menu_description` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `menu_name`, `menu_category`, `menu_size`, `menu_price_small`, `menu_price_medium`, `menu_price_large`, `menu_quantity_small`, `menu_quantity_medium`, `menu_quantity_large`, `food_price`, `menu_temperature`, `menu_quantity`, `product_status`, `menu_image_path`, `menu_description`, `date_created`) VALUES
(75, 'o', 'Coffee', '', '88.00', '0.00', '0.00', 0, 0, 0, '0', 'hot', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', 'kkk', '2024-11-14 15:20:52'),
(76, 'adad', 'Coffee', '', '44.00', '0.00', '0.00', 0, 0, 0, '0', 'hot', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', '4345345', '2024-11-14 15:22:56'),
(78, 'utyutyutyuty', 'Coffee', 'Small', '6.00', '0.00', '0.00', 0, 0, 0, '0', 'hot', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', 'tyutyutyuty', '2024-11-14 15:58:58'),
(79, 'ertert', 'Coffee', 'Small', '55.00', '0.00', '0.00', 15, 1, 1, '0', 'hot', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', 'etrerte', '2024-11-14 16:16:32'),
(80, 'rtrttt', 'Non-Coffee', 'Small,Medium', '66.00', '6.00', '0.00', 55, 5, 0, '0', 'hot', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', 'rtyrty', '2024-11-14 16:21:27'),
(81, 'eeeeeeeee', 'Non-Coffee', 'Small,Medium', '66.00', '66.00', '0.00', 6, 66, 0, '0', 'hot,warm', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', 'eeeeeeeeeee', '2024-11-14 16:22:31');

-- --------------------------------------------------------

--
-- Table structure for table `menu1`
--

CREATE TABLE `menu1` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `size` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `temperature` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('Available','Not Available') NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `menu1`
--

INSERT INTO `menu1` (`id`, `name`, `category`, `size`, `price`, `temperature`, `quantity`, `status`, `image`, `description`, `date_created`) VALUES
(3, 'adad', 'Coffee', 'Small', '44.33', 'Hot', 7, 'Available', '././../../uploads/resumepic.png', 'll', '2024-11-15 03:51:01'),
(4, 'adad', 'Non-Coffee', 'Small', '48.13', 'Hot', 11, 'Available', '././../../uploads/uyuyui.jpg', 'cvcv', '2024-11-16 04:25:53'),
(5, 'adad', 'Non-Coffee', 'Small,Medium,Large', '155.44', 'Hot,Warm,Cold', 30, 'Available', '././../../uploads/uyuyui.jpg', 'klkl', '2024-11-16 22:34:56'),
(7, 'wewewe', 'Pasta', '', '48.13', '', 8, 'Available', '././../../uploads/resumepic.png', 'werwer', '2024-11-17 00:01:24'),
(11, 'o', 'Coffee', 'Small,Medium', '30.73', 'Hot,Warm', 9, 'Available', '././../../uploads/resumepic.png', 'sdsfd', '2024-11-18 01:04:51'),
(13, 'fghfghfh', 'Coffee', 'Small', '90.41', 'Hot', 17, 'Available', '././../../uploads/salongcongresume.png', 'fghfg', '2024-11-18 01:10:44'),
(14, 'fghfghfh', 'Coffee', 'Small', '90.41', 'Hot', 17, 'Available', '././../../uploads/salongcongresume.png', 'fghfg', '2024-11-18 01:11:08'),
(15, 'dsfd', 'Coffee', 'Small', '60.68', 'Hot', 17, 'Available', '././../../uploads/uyuyui.jpg', 'sfds', '2024-11-18 01:11:26'),
(16, 'hbh', 'Non-Coffee', 'Small', '35.08', 'Hot', 6, 'Available', '././../../uploads/resumepic.png', 'bhb', '2024-11-18 01:25:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_list`
--
ALTER TABLE `admin_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu1`
--
ALTER TABLE `menu1`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_list`
--
ALTER TABLE `admin_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;
--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;
--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;
--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;
--
-- AUTO_INCREMENT for table `menu1`
--
ALTER TABLE `menu1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
