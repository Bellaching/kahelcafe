-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 03:13 PM
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
-- Database: `kahelcafe1`
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
  `profile_picture` varchar(55) NOT NULL,
  `role` varchar(55) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_list`
--

INSERT INTO `admin_list` (`id`, `username`, `password`, `email`, `profile_picture`, `role`, `date_created`) VALUES
(1, 'adminp', '$2y$10$iuQx5j/Myg33zSWrxNF4HeFAbkav9ca5Y6YXlnJ08ufiOeDruYHte', 'admin@gmail.com', '', 'owner', '2025-04-08 03:59:28'),
(9, 'r', 'rrrrrrrr', 'jewellsalogcong09@gmail.com', '', 'staff', '0000-00-00 00:00:00'),
(10, 'hjkhj', 'jjjjjjjj', 'nelixe4509@exclussi.com', '', 'staff', '2025-05-08 02:10:43'),
(11, 'l', 'llllllll', 'wiyahoy345@arinuse.com', '', 'staff', '2025-05-08 02:11:39'),
(12, 'pp', 'pppppppp', 'jewellongcong09@gmail.com', '', 'staff', '2025-05-08 02:17:42'),
(13, 'ad', 'aaaaaaaa', 'jewellsalongcong09@gmail.com', '', 'owner', '2025-05-08 02:19:42'),
(14, 'p', 'pppppppp', 'jewellsalongcong09@gmail.com', '', 'staff', '2025-05-08 02:21:21'),
(15, 'iiii', 'iiiiiiii', 'wafin8146@exclussi.com', '', 'staff', '2025-05-08 03:17:23'),
(16, 'a', 'pppppppp', 'jewellsalocong09@gmail.com', '', 'staff', '2025-05-08 03:20:59'),
(17, 'iiiii', 'iiiiiiii', 'wafin891@exclussi.com', '', 'staff', '2025-05-08 03:25:37'),
(18, 'staff', '12345678', 'wiya45@arinuse.com', '', 'staff', '2025-05-11 16:23:56');

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `content` varchar(55) NOT NULL,
  `description` varchar(55) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `content`, `description`, `date_created`) VALUES
(74, './../../uploadsuploadsuploadsBella1.png', 'Uploaded Image', '2025-05-10 22:47:04');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `size` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `temperature` varchar(255) NOT NULL,
  `note` text NOT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chart`
--

CREATE TABLE `chart` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `id` int(11) NOT NULL,
  `firstname` varchar(55) NOT NULL,
  `lastname` varchar(55) NOT NULL,
  `email` varchar(55) NOT NULL,
  `contact_number` varchar(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(55) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `verification_code` varchar(32) NOT NULL,
  `code_expiry` datetime NOT NULL,
  `verified` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`id`, `firstname`, `lastname`, `email`, `contact_number`, `password`, `profile_picture`, `created_at`, `verification_code`, `code_expiry`, `verified`) VALUES
(1, 'Bella', 'Romano', 'bellaromano27@gmail.com', '09123456780', '$2y$10$dJnZpzPKCWnOvchnquerVOwVM91f62Qm94jvhYV2qEl8ZG.Dej/XG', '', '2025-04-09 01:11:33', '6df9256b09', '2025-04-08 19:14:25', 0),
(82, 'Duke', 'Duke', 'qkoxzxm946@khalisady.store', '2147483647', 'Duke123@12', '', '2024-11-05 02:04:32', 'a850339acd', '2024-11-04 19:06:32', 1),
(83, 'Jizzelliiioi', 'Salongcong', 'jewellsalongcong09@gmail.com', '09123456789', '$2y$10$7Z8rVTT2.1Bx.YhsngVC.eOgD701UtkoynljSRnSn7VeniC7QX286', './../../uploads/profile_pictures/profile_68053ba3b2b0d.', '2024-11-05 21:14:11', '559112', '2025-03-20 16:02:52', 1),
(84, 'Dkk', 'Dkkk', 'wiyahoy345@arinuse.com', '09123456789', 'pppppppp', '', '2025-03-04 13:51:42', 'afdebc5993', '2025-03-04 06:55:40', 1),
(85, 'Bbbp', 'Bbbp', 'bellachingaling@gmail.com', '09123456789', '$2y$10$fWAy21wWPEd7b9PTrXvp9ewqxoPI5ALO7sGde5E50Kutrk5/Zxs/C', '', '2025-04-09 02:39:09', '318a7f5265', '2025-04-08 20:41:09', 0),
(86, 'Pppp', 'Pppp', 'nelixe4509@exclussi.com', '09123456789', '$2y$10$/NXRrIyopuY9uh5GVn3MgOglWp35GB/6EFh8oLIK5dSrzenvU5yEi', '', '2025-04-09 02:41:04', '3a413ad6df', '2025-04-08 20:43:04', 1),
(87, 'Bella', 'Poarch', 'samaxa6228@ptiong.com', '09123456780', '$2y$10$9meDb74EQ7Ce/MbvUSdEDeGJwxl7GV8ghKNp5Joxp1tNvn5fvZ9j6', '', '2025-04-11 10:09:52', '893d5c819c', '2025-04-11 04:11:52', 1),
(88, 'Duke', 'Duke', 'mojiya8233@dpcos.com', '09123456789', '$2y$10$8X.1Z2NinNb3HYXDdRnJw.pMKpsxvZiB.gp90lyAcs3UoACVgFG3G', '', '2025-04-11 17:06:18', '57e0f081b0', '2025-04-11 11:08:18', 0),
(89, 'Duke', 'Duke', 'toxeho3310@insfou.com', '09123456789', '$2y$10$VJxMhO9QlRvBSzDB9qN0d.mXGzcTxcD2Or4U3DQbfleyfnrsMyYym', '', '2025-04-11 17:08:12', '47f7b09044', '2025-04-11 11:10:12', 1),
(90, 'Duke', 'Duke', 'lodeto2630@dpcos.com', '09123456789', '$2y$10$Qajle6hlGm73kyzpUzOVeemr1QCFb4chniEOTz//M0SVbfm4dvZz.', './../../uploads/profile_pictures/profile_67f8e01a01f91.', '2025-04-11 17:12:11', '16a17d704c', '2025-04-11 11:14:11', 1),
(91, 'Dgfd', 'Dfgdg', 'xenik46333@dpcos.com', '09123456789', '$2y$10$CNITePvXYYwEvnMnRom4mu2eLmHh35oHwr1C7cxTXi8zHneSu9Lie', '', '2025-04-11 17:37:34', '9d5fa839bb', '2025-04-11 11:39:34', 1),
(92, 'Ssssss', 'Ssssssss', 'kabomal527@dpcos.com', '09123456789', '$2y$10$FuM8LdUNEPPSJCzIPi/i7.VwFyRatPD3L0R3EvOP5b6w/D61GyJnO', '', '2025-04-11 17:55:25', 'c28d494770', '2025-04-11 11:57:25', 0),
(93, 'Duke', 'Duke', 'jetad59632@insfou.com', '09123456789', '$2y$10$pndMlhrIr8NdpY7cz04q0uHHSJAbXVYw7QOOs6lFdTOXgnxlZyt0a', '', '2025-04-11 19:02:49', '', '2025-04-11 13:04:49', 1);

-- --------------------------------------------------------

--
-- Table structure for table `gcash`
--

CREATE TABLE `gcash` (
  `id` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `menu_name`, `menu_category`, `menu_size`, `menu_price_small`, `menu_price_medium`, `menu_price_large`, `menu_quantity_small`, `menu_quantity_medium`, `menu_quantity_large`, `food_price`, `menu_temperature`, `menu_quantity`, `product_status`, `menu_image_path`, `menu_description`, `date_created`) VALUES
(75, 'o', 'Coffee', '', 88.00, 0.00, 0.00, 0, 0, 0, 0, 'hot', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', 'kkk', '2024-11-14 15:20:52'),
(76, 'adad', 'Coffee', '', 44.00, 0.00, 0.00, 0, 0, 0, 0, 'hot', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', '4345345', '2024-11-14 15:22:56'),
(78, 'utyutyutyuty', 'Coffee', 'Small', 6.00, 0.00, 0.00, 0, 0, 0, 0, 'hot', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', 'tyutyutyuty', '2024-11-14 15:58:58'),
(79, 'ertert', 'Coffee', 'Small', 55.00, 0.00, 0.00, 15, 1, 1, 0, 'hot', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', 'etrerte', '2024-11-14 16:16:32'),
(80, 'rtrttt', 'Non-Coffee', 'Small,Medium', 66.00, 6.00, 0.00, 55, 5, 0, 0, 'hot', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', 'rtyrty', '2024-11-14 16:21:27'),
(81, 'eeeeeeeee', 'Non-Coffee', 'Small,Medium', 66.00, 66.00, 0.00, 6, 66, 0, 0, 'hot,warm', 0, 'Available', '././../../uploads/640px-Cappuccino_at_Sightglass_Coffee.jpg', 'eeeeeeeeeee', '2024-11-14 16:22:31');

-- --------------------------------------------------------

--
-- Table structure for table `menu1`
--

CREATE TABLE `menu1` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `size` text NOT NULL,
  `price` text NOT NULL,
  `menuPriceSmall` int(11) NOT NULL,
  `menuPriceMedium` int(11) NOT NULL,
  `menuPriceLarge` int(11) NOT NULL,
  `temperature` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('Available','Not Available') NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `rating` decimal(11,0) NOT NULL,
  `type` varchar(10) NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `menu1`
--

INSERT INTO `menu1` (`id`, `name`, `category`, `size`, `price`, `menuPriceSmall`, `menuPriceMedium`, `menuPriceLarge`, `temperature`, `quantity`, `status`, `image`, `description`, `rating`, `type`, `date_created`) VALUES
(50, 'ss', 'Espresso', 'Small,Large', '{\"Small\":199,\"Large\":334}', 0, 0, 0, 'Warm', 2, 'Available', '././../../uploads/uploadsBella1.png', 'hjhjk', 0, '', '2025-05-07 01:42:52'),
(51, 'dddd', 'Espresso', 'Small,Medium', '{\"Small\":66,\"Medium\":77}', 0, 0, 0, 'Hot,Warm,Cold', 0, '', '././../../uploads/uploadsBella1.png', 'dd', 5, '', '2025-05-07 03:58:46'),
(52, 'jjj', 'Non-Coffee', 'Small,Medium,Large', '{\"Small\":77,\"Medium\":88,\"Large\":99}', 0, 0, 0, 'Hot,Warm,Cold', 6, 'Available', '././../../uploads/uploadstr.png', 'hkjhjk', 0, '', '2025-05-08 02:13:26');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `client_full_name` varchar(255) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `reservation_type` varchar(50) DEFAULT NULL,
  `reservation_fee` decimal(11,0) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `reservation_time` varchar(55) NOT NULL,
  `party_size` int(11) NOT NULL,
  `status` varchar(50) DEFAULT NULL,
  `reservation_date` date DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `client_full_name`, `total_price`, `transaction_id`, `created_at`, `reservation_type`, `reservation_fee`, `reservation_id`, `reservation_time`, `party_size`, `status`, `reservation_date`, `last_updated`) VALUES
(154, 83, 'Jizzelliiioi Salongcong', 93.85, '034933E15604', '2025-02-13 17:09:31', '', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(155, 83, 'Jizzelliiioi Salongcong', 59.13, '25EE31D66389', '2025-02-13 17:15:52', '', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(156, 83, 'Jizzelliiioi Salongcong', 93.85, '231B0C8C317D', '2025-02-13 17:17:35', '', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(158, 83, 'Jizzelliiioi Salongcong', 41.77, '425C65D6DC49', '2025-02-13 19:47:05', '', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(163, 83, 'Jizzelliiioi Salongcong', 41.77, '49EFB8BBF3EE', '2025-02-13 20:52:48', '', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(164, 83, 'Jizzelliiioi Salongcong', 29.18, '345979F9E04D', '2025-02-13 21:14:52', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(172, 83, 'Jizzelliiioi Salongcong', 29.18, '621261026D91', '2025-02-15 18:41:12', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(173, 83, 'Jizzelliiioi Salongcong', 29.18, '089F5215A174', '2025-02-15 19:04:54', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(176, 83, 'Jizzelliiioi Salongcong', 29.18, '9A7B984A468D', '2025-02-15 23:44:17', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(177, 83, 'Jizzelliiioi Salongcong', 29.18, '57577454989C', '2025-02-15 23:44:45', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(178, 83, 'Jizzelliiioi Salongcong', 29.18, '413B96678743', '2025-02-15 23:45:30', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(181, 83, 'Jizzelliiioi Salongcong', 41.77, '30AE413EB98B', '2025-02-16 00:32:36', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(182, 83, 'Jizzelliiioi Salongcong', 41.77, '927178802B02', '2025-02-16 00:34:00', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(185, 83, 'Jizzelliiioi Salongcong', 29.18, '84E4EEC5523C', '2025-02-16 00:37:42', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(187, 83, 'Jizzelliiioi Salongcong', 29.18, '783F205145B3', '2025-02-16 00:39:47', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(189, 83, 'Jizzelliiioi Salongcong', 41.77, '0557DAAB2CC1', '2025-02-16 00:43:09', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(190, 83, 'Jizzelliiioi Salongcong', 41.77, '76C9C932F917', '2025-02-16 02:49:26', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(194, 83, 'Jizzelliiioi Salongcong', 29.18, '55E9CE6F4AD1', '2025-02-16 03:27:42', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(198, 83, 'Jizzelliiioi Salongcong', 41.77, '517DE148AF30', '2025-02-16 03:35:24', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(201, 83, 'Jizzelliiioi Salongcong', 29.18, '83D0FB856F01', '2025-02-17 20:03:41', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(204, 83, 'Jizzelliiioi Salongcong', 29.18, '3893F0FF9201', '2025-02-17 20:14:35', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(209, 83, 'Jizzelliiioi Salongcong', 41.77, '046DAEFB0633', '2025-02-19 00:03:56', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(210, 83, 'Jizzelliiioi Salongcong', 29.18, '2E60B885CB75', '2025-02-19 00:05:53', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(224, 83, 'Jizzelliiioi Salongcong', 41.77, '1855D0861EF1', '2025-02-19 16:54:04', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(226, 83, 'Jizzelliiioi Salongcong', 29.18, '46C9D9A65848', '2025-02-19 18:28:38', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(227, 83, 'Jizzelliiioi Salongcong', 41.77, '18521B5D2CA3', '2025-02-19 18:29:08', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(230, 83, 'Jizzelliiioi Salongcong', 41.77, '6755E78D1AE6', '2025-02-19 18:36:28', 'Over the counter', 50, 0, '0', 0, 'cancelled', NULL, '2025-04-08 02:43:51'),
(263, 83, 'Jizzelliiioi Salongcong', 29.18, '82543FE9833A', '2025-03-29 02:13:57', 'Over the counter', 50, 0, '', 0, 'cancelled', '2025-03-29', '2025-04-08 02:43:51'),
(273, 83, 'Jizzelliiioi Salongcong', 58.36, '2625DA712A2E', '2025-03-30 14:12:39', 'Over the counter', 50, 9, '6:00pm- 7:00pm', 1, 'rate us', '2025-03-30', '2025-04-08 02:43:51'),
(277, 83, 'Jizzelliiioi Salongcong', 599.33, '032A6994F8CA', '2025-04-06 05:39:41', 'Over the counter', 50, 9, '6:00pm- 7:00pm', 1, 'rate us', '2025-04-06', '2025-04-08 02:43:51'),
(278, 83, 'Jizzelliiioi Salongcong', 172.67, '5205BF89B595', '2025-04-06 05:41:11', 'Over the counter', 50, 2, '10:00am - 11:00am', 1, 'rate us', '2025-04-06', '2025-04-08 02:43:51'),
(280, 83, 'Jizzelliiioi Salongcong', 172.67, '1674F903D443', '2025-04-06 15:18:25', 'Over the counter', 50, 9, '6:00pm- 7:00pm', 1, 'rate us', '2025-04-07', '2025-04-08 03:32:45'),
(286, 83, 'Jizzelliiioi Salongcong', 81.32, '179160450FCF', '2025-04-06 16:44:56', 'Over the counter', 50, 2, '10:00am - 11:00am', 1, 'rate us', '2025-04-06', '2025-04-08 10:00:40'),
(287, 83, 'Jizzelliiioi Salongcong', 222.67, '304F72E4E945', '2025-04-08 20:29:15', 'Over the counter', 50, 2, '10:00am - 11:00am', 1, 'cancelled', '2025-04-08', '2025-04-08 21:57:58'),
(289, 86, 'Pppp Pppp', 131.32, 'D140354F9CE5', '2025-04-09 05:53:48', 'Over the counter', 50, 2, '10:00am - 11:00am', 1, 'rate us', '2025-04-11', '2025-05-07 03:21:57'),
(290, 83, 'Jizzelliiioi Salongcong', 50.00, '063FBDFFC429', '2025-04-09 06:55:28', 'Over the counter', 50, 1, '9:00am - 10:00am', 1, 'rate us', '2025-04-09', '2025-04-08 22:56:27'),
(291, 83, 'Jizzelliiioi Salongcong', 103.60, '8CF9332BB449', '2025-04-09 07:24:47', 'Over the counter', 50, 8, '5:00pm- 6:00pm', 1, 'rate us', '2025-04-09', '2025-04-10 17:56:04'),
(292, 83, 'Jizzelliiioi Salongcong', 131.32, '8291D2C5FC4B', '2025-04-09 08:30:37', 'Over the counter', 50, 1, '9:00am - 10:00am', 1, 'cancelled', '2025-04-12', '2025-04-09 00:31:22'),
(293, 83, 'Jizzelliiioi Salongcong', 131.32, '493B0D474544', '2025-04-09 08:32:30', 'Over the counter', 50, 1, '9:00am - 10:00am', 1, 'rate us', '2025-04-10', '2025-04-09 00:34:46'),
(294, 83, 'Jizzelliiioi Salongcong', 131.32, 'EF8E4FA74C9E', '2025-04-09 09:51:14', 'Over the counter', 50, 2, '10:00am - 11:00am', 1, 'rate us', '2025-04-10', '2025-05-07 03:21:57'),
(295, 83, 'Jizzelliiioi Salongcong', 375.28, '240B9A476F92', '2025-04-09 22:55:44', 'Over the counter', 50, 1, '9:00am - 10:00am', 1, 'rate us', '2025-04-11', '2025-04-10 17:29:02'),
(296, 83, 'Jizzelliiioi Salongcong', 50.00, 'D3B4D899A1C9', '2025-04-10 01:51:24', 'Over the counter', 50, 1, '9:00am - 10:00am', 1, 'rate us', '2025-04-10', '2025-05-07 03:21:57'),
(297, 86, 'Pppp Pppp', 358.84, 'A8E4F581D524', '2025-04-10 05:37:20', 'Over the counter', 50, 10, '7:00pm- 8:00pm', 1, 'rate us', '2025-04-10', '2025-05-07 03:21:57'),
(298, 86, 'Pppp Pppp', 358.84, 'F806054B0E25', '2025-04-10 05:40:14', 'Over the counter', 50, 2, '10:00am - 11:00am', 1, 'rate us', '2025-04-10', '2025-05-07 03:21:57'),
(299, 86, 'Pppp Pppp', 358.84, 'B1176E610C57', '2025-04-10 05:45:51', 'Over the counter', 50, 10, '7:00pm- 8:00pm', 1, 'rate us', '2025-04-10', '2025-05-07 03:21:57'),
(300, 83, 'Jizzelliiioi Salongcong', 50.00, '7B48F514A7E6', '2025-04-10 17:20:52', 'Over the counter', 50, 1, '9:00am - 10:00am', 1, 'cancelled', '2025-04-10', '2025-04-10 13:43:26'),
(301, 83, 'Jizzelliiioi Salongcong', 358.84, '566F2EEE7172', '2025-04-10 23:41:32', 'Over the counter', 50, 3, '11:00am - 12:00pm', 1, 'cancelled', '2025-04-10', '2025-04-10 15:43:45'),
(303, 83, 'Jizzelliiioi Salongcong', 358.84, 'ACDF390DF24F', '2025-04-10 23:45:06', 'Over the counter', 50, 6, '2:00pm- 3:00pm', 1, 'rate us', '2025-04-10', '2025-05-07 03:21:57'),
(304, 83, 'Jizzelliiioi Salongcong', 358.84, 'C100B710DEB7', '2025-04-11 00:54:50', 'Over the counter', 50, 9, '6:00pm- 7:00pm', 1, 'rate us', '2025-04-11', '2025-05-07 03:21:57'),
(305, 83, 'Jizzelliiioi Salongcong', 358.84, '4D8EDF4AA9DA', '2025-04-11 01:16:25', 'Over the counter', 50, 7, '3:00pm- 4:00pm', 1, 'rate us', '2025-04-11', '2025-04-10 18:02:40'),
(306, 83, 'Jizzelliiioi Salongcong', 358.84, '852F0BE1EF94', '2025-04-11 01:34:52', 'Over the counter', 50, 6, '2:00pm- 3:00pm', 1, 'cancelled', '2025-04-11', '2025-04-10 17:41:56'),
(307, 83, 'Jizzelliiioi Salongcong', 6535.64, '187B1972272A', '2025-04-11 01:41:03', 'Over the counter', 50, 6, '2:00pm- 3:00pm', 1, 'cancelled', '2025-04-11', '2025-04-10 17:42:13'),
(308, 83, 'Jizzelliiioi Salongcong', 358.84, '10E7F334D1F3', '2025-04-11 01:43:05', 'Over the counter', 50, 6, '2:00pm- 3:00pm', 1, 'cancelled', '2025-04-11', '2025-04-10 17:43:21'),
(309, 83, 'Jizzelliiioi Salongcong', 358.84, '8F05DD131C14', '2025-04-11 01:43:35', 'Over the counter', 50, 6, '2:00pm- 3:00pm', 1, 'rate us', '2025-04-11', '2025-04-10 17:56:04'),
(310, 83, 'Jizzelliiioi Salongcong', 358.84, '4C77D0922A16', '2025-04-11 01:57:18', 'Over the counter', 50, 4, '12:00pm- 1:00pm', 1, 'rate us', '2025-04-11', '2025-04-10 18:02:40'),
(311, 83, 'Jizzelliiioi Salongcong', 358.84, '39027B7DB28B', '2025-04-11 02:03:32', 'Over the counter', 50, 3, '11:00am - 12:00pm', 1, 'rate us', '2025-04-16', '2025-04-16 16:46:32'),
(312, 87, 'Bella Poarch', 667.68, 'BE20AD240E3A', '2025-04-11 10:11:44', 'Over the counter', 50, 10, '7:00pm- 8:00pm', 1, 'rate us', '2025-04-11', '2025-05-07 03:21:57'),
(313, 83, 'Jizzelliiioi Salongcong', 50.00, 'E2AD4147EB85', '2025-04-11 10:28:07', 'Over the counter', 50, 5, '1:00pm- 2:00pm', 1, 'rate us', '2025-04-12', '2025-05-07 03:21:57'),
(314, 83, 'Jizzelliiioi Salongcong', 358.84, '13ADDC165A8C', '2025-04-11 15:44:00', 'Over the counter', 50, 1, '9:00am - 10:00am', 1, 'rate us', '2025-04-12', '2025-04-16 09:54:44'),
(315, 90, 'Duke Duke', 358.84, '310EE4D3E3D5', '2025-04-11 17:13:01', 'Over the counter', 50, 3, '11:00am - 12:00pm', 1, 'rate us', '2025-04-12', '2025-04-11 09:14:33'),
(316, 83, 'Jizzelliiioi Salongcong', 358.84, '66B6816DF99C', '2025-04-16 17:54:55', 'Over the counter', 50, 9, '6:00pm- 7:00pm', 1, 'cancelled', '2025-04-17', '2025-04-16 12:01:05'),
(317, 83, 'Jizzelliiioi Salongcong', 358.84, '57FC586350D0', '2025-04-17 00:41:05', 'Over the counter', 50, 5, '1:00pm- 2:00pm', 1, 'cancelled', '2025-04-18', '2025-04-16 16:44:01'),
(318, 83, 'Jizzelliiioi Salongcong', 358.84, 'A0EBB7E5A2F6', '2025-04-17 00:44:10', 'Over the counter', 50, 1, '9:00am - 10:00am', 1, 'rate us', '2025-04-17', '2025-05-07 03:21:57'),
(319, 83, 'Jizzelliiioi Salongcong', 358.84, '6A38C86E5F05', '2025-04-17 01:11:14', 'Over the counter', 50, 8, '5:00pm- 6:00pm', 1, 'cancelled', '2025-04-17', '2025-04-16 17:13:19'),
(320, 83, 'Jizzelliiioi Salongcong', 358.84, '1A78350DEAB4', '2025-04-17 01:13:32', 'Over the counter', 50, 8, '5:00pm- 6:00pm', 1, 'rate us', '2025-04-17', '2025-04-16 17:23:00'),
(321, 83, 'Jizzelliiioi Salongcong', 358.84, '1921DB58B1BE', '2025-04-17 01:34:38', 'Over the counter', 50, 9, '6:00pm- 7:00pm', 1, 'cancelled', '2025-04-17', '2025-04-16 17:36:20'),
(322, 83, 'Jizzelliiioi Salongcong', 50.00, '29D06ACA9393', '2025-04-17 01:36:39', 'Over the counter', 50, 9, '6:00pm- 7:00pm', 1, 'cancelled', '2025-04-17', '2025-04-16 17:36:42'),
(323, 83, 'Jizzelliiioi Salongcong', 358.84, '525C6857B8AE', '2025-04-18 13:31:35', 'Over the counter', 50, 2, '10:00am - 11:00am', 1, 'cancelled', '2025-04-19', '2025-04-20 18:24:03'),
(324, 1, 'Bella Romano', 70.12, 'FA0BF3C6F137', '2025-04-18 13:32:57', 'Over the counter', 50, 2, '10:00am - 11:00am', 1, 'rate us', '2025-04-18', '2025-05-07 03:21:57'),
(325, 83, 'Jizzelliiioi Salongcong', 127.00, 'BF8BAD49787D', '2025-05-07 04:43:18', 'Over the counter', 50, 2, '10:00am - 11:00am', 1, 'rate us', '2025-05-08', '2025-05-07 03:21:57'),
(326, 83, 'Jizzelliiioi Salongcong', 381.00, 'E37AA1B1DE25', '2025-05-07 13:34:28', 'Over the counter', 50, 1, '9:00am - 10:00am', 1, 'cancelled', '2025-05-07', '2025-05-07 18:14:28'),
(328, 83, 'Jizzelliiioi Salongcong', 116.00, '3A2CF5DB4907', '2025-05-13 03:24:49', 'Over the counter', 50, 5, '1:00pm- 2:00pm', 1, 'cancelled', '2025-05-13', '2025-05-12 19:25:54'),
(329, 83, 'Jizzelliiioi Salongcong', 127.00, '54013B7C3254', '2025-05-13 03:28:20', 'Over the counter', 50, 8, '5:00pm- 6:00pm', 1, 'cancelled', '2025-05-13', '2025-05-13 12:04:49'),
(330, 83, 'Jizzelliiioi Salongcong', 67753.00, '03C0A0803492', '2025-05-13 20:04:55', 'Over the counter', 67676, 6, '2:00pm- 3:00pm', 1, 'for confirmation', '2025-05-13', '2025-05-13 12:04:55');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `size` varchar(50) DEFAULT NULL,
  `temperature` varchar(50) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `receipt` varchar(255) NOT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `is_rated` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `item_id`, `order_id`, `item_name`, `size`, `temperature`, `quantity`, `note`, `price`, `receipt`, `rating`, `is_rated`) VALUES
(1, 0, 170, 'o', 'Small', 'Hot', 1, '', 59.13, '', NULL, 0),
(2, 0, 171, 'ghjghj', '', '', 1, '', 93.85, '', NULL, 0),
(3, 0, 169, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(4, 0, 168, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(5, 0, 145, 'jkjklj', '', '', 1, '0', 41.77, '', NULL, 0),
(6, 0, 146, 'jkljkljkl', 'Small', 'Warm', 1, '0', 29.18, '', NULL, 0),
(7, 0, 147, 'ghjghj', '', '', 1, '0', 93.85, '', NULL, 0),
(8, 0, 148, 'jkljkljkl', 'Small', 'Warm', 1, '0', 29.18, '', NULL, 0),
(9, 0, 149, 'jkljkljkl', 'Small', 'Warm', 1, '0', 29.18, '', NULL, 0),
(10, 0, 150, 'jkljkljkl', 'Small', 'Warm', 1, '0', 29.18, 'Bella1.png', NULL, 0),
(11, 0, 151, 'jkjklj', '', '', 1, '0', 41.77, 'Bella1.png', NULL, 0),
(12, 0, 152, 'jkjklj', '', '', 1, '0', 41.77, '', NULL, 0),
(13, 0, 153, 'jkjklj', '', '', 1, '0', 41.77, 'Bella1.png', NULL, 0),
(14, 0, 154, 'ghjghj', '', '', 1, '0', 93.85, '', NULL, 0),
(15, 0, 155, 'o', 'Small', 'Hot', 1, '0', 59.13, '', NULL, 0),
(16, 0, 156, 'ghjghj', '', '', 1, '', 93.85, '', NULL, 0),
(17, 0, 157, 'jkjklj', '', '', 1, NULL, 41.77, '', NULL, 0),
(18, 0, 158, 'jkjklj', '', '', 1, NULL, 41.77, '', NULL, 0),
(19, 0, 160, 'jkljkljkl', 'Small', 'Warm', 1, NULL, 29.18, '', NULL, 0),
(20, 0, 161, 'jkljkljkl', 'Small', 'Warm', 1, NULL, 29.18, '', NULL, 0),
(21, 0, 162, 'jkljkljkl', 'Small', 'Warm', 1, NULL, 29.18, '', NULL, 0),
(22, 0, 163, 'jkjklj', '', '', 1, NULL, 41.77, '', NULL, 0),
(23, 0, 164, 'jkljkljkl', 'Small', 'Warm', 1, 'fhgfgh', 29.18, '', NULL, 0),
(24, 0, 165, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(25, 0, 166, 'jkljkljkl', 'Small', 'Warm', 1, 'ertert', 29.18, '', NULL, 0),
(26, 0, 180, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(27, 0, 182, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(28, 0, 189, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(29, 0, 190, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(30, 0, 191, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(31, 0, 192, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(32, 0, 193, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(33, 0, 194, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(34, 0, 195, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(35, 0, 196, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(36, 0, 197, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(37, 0, 198, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(38, 0, 199, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(39, 0, 200, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(40, 0, 201, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(41, 0, 202, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(42, 0, 203, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(43, 0, 204, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(44, 0, 205, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(45, 0, 206, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(46, 0, 207, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(47, 0, 208, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(48, 0, 209, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(49, 0, 210, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(50, 0, 211, 'ghjghj', '', '', 1, '', 93.85, '', NULL, 0),
(51, 0, 212, 'ghjghj', '', '', 1, '', 93.85, '', NULL, 0),
(52, 0, 213, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(53, 0, 214, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(54, 0, 215, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(55, 0, 216, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(56, 0, 219, '', '', '', 1, '', 0.00, '', NULL, 0),
(57, 0, 224, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(58, 0, 225, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(59, 0, 226, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(60, 0, 227, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(61, 0, 228, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(62, 0, 230, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(63, 0, 232, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(64, 0, 233, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(65, 0, 234, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(66, 0, 234, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(67, 0, 235, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(68, 0, 236, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(69, 0, 237, 'jkljkljkl', 'Small', 'Warm', 1, 'm', 29.18, '', NULL, 0),
(70, 0, 238, 'jkljkljkl', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(71, 0, 239, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(72, 0, 244, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(73, 0, 244, 'o', 'Small', 'Hot', 1, '', 59.13, '', NULL, 0),
(74, 30, 245, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(75, 29, 246, 'ghjghj', '', '', 1, '', 93.85, '', NULL, 0),
(76, 30, 246, 'jkjklj', '', '', 1, '', 41.77, '', NULL, 0),
(77, 32, 246, 's', '', '', 1, '', 76.49, '', NULL, 0),
(78, 25, 246, 'o', 'Small', 'Hot', 1, '', 59.13, '', NULL, 0),
(79, 34, 247, 'ww', '', '', 1, '', 39.29, '', NULL, 0),
(80, 34, 248, 'ww', '', '', 1, '', 39.29, '', NULL, 0),
(81, 29, 249, 'ghjghj', '', '', 1, '', 93.85, '', NULL, 0),
(82, 34, 250, 'ww', '', '', 1, '', 39.29, '', NULL, 1),
(83, 34, 251, 'ww', '', '', 1, '', 39.29, '', NULL, 1),
(84, 34, 252, 'ww', '', '', 1, '', 39.29, '', NULL, 0),
(85, 34, 253, 'ww', '', '', 1, '', 39.29, 'Bella1.png', NULL, 1),
(86, 34, 254, 'ww', '', '', 1, '', 39.29, '', NULL, 0),
(87, 34, 255, 'ww', '', '', 1, '', 39.29, '', NULL, 0),
(88, 34, 256, 'ww', '', '', 1, '', 39.29, '', NULL, 1),
(89, 35, 257, 'dd', 'Small', 'Warm', 1, '', 172.67, '', NULL, 0),
(90, 35, 259, '', 'Small', 'Warm', 1, '', 0.00, '', NULL, 0),
(91, 35, 260, 'dd', 'Small', 'Warm', 1, '', 172.67, '', NULL, 0),
(92, 35, 261, 'dd', 'Small', 'Warm', 1, '', 172.67, '', NULL, 0),
(95, 31, 266, 'jkljklj', 'Small', 'Warm', 2, '', 29.18, '', NULL, 0),
(96, 35, 266, 'dd', 'Small', 'Warm', 1, '', 172.67, '', NULL, 0),
(103, 35, 267, 'dd', 'Small', 'Warm', 1, '', 172.67, '', NULL, 0),
(107, 31, 268, 'jkljklj', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(108, 31, 269, 'jkljklj', 'Small', 'Warm', 1, '', 29.18, '', NULL, 0),
(109, 35, 272, 'dd', 'Small', 'Warm', 1, NULL, 172.67, '', NULL, 0),
(110, 31, 273, 'jkljklj', 'Small', 'Warm', 2, NULL, 29.18, '', NULL, 1),
(111, 35, 274, 'dd', 'Small', 'Warm', 2, NULL, 172.67, '', NULL, 0),
(112, 35, 275, 'dd', 'Small', 'Warm', 1, NULL, 172.67, '', NULL, 0),
(113, 35, 276, 'dd', 'Small', 'Warm', 1, NULL, 172.67, 'sig.jpg', NULL, 1),
(114, 35, 277, 'dd', 'Small', 'Warm', 3, NULL, 172.67, '', NULL, 1),
(115, 41, 277, 's', 'Small', 'Warm', 1, NULL, 81.32, '', NULL, 1),
(116, 35, 278, 'dd', 'Small', 'Warm', 1, NULL, 172.67, '', NULL, 1),
(117, 41, 279, 's', 'Small', 'Warm', 1, NULL, 81.32, '', NULL, 0),
(118, 35, 280, 'dd', 'Small', 'Warm', 1, NULL, 172.67, '', 4, 1),
(119, 35, 281, 'dd', 'Small', 'Warm', 1, NULL, 172.67, '', 4, 1),
(120, 41, 282, 's', 'Small', 'Warm', 1, NULL, 81.32, '', 5, 1),
(121, 41, 283, 's', 'Small', 'Warm', 1, NULL, 81.32, '', 4, 1),
(122, 35, 284, 'dd', 'Small', 'Warm', 1, NULL, 172.67, '', 5, 1),
(123, 41, 285, 's', 'Small', 'Warm', 1, NULL, 81.32, '', 1, 1),
(124, 41, 286, 's', 'Small', 'Warm', 1, NULL, 81.32, '', 5, 1),
(125, 35, 0, 'dd', 'Small', 'Warm', 1, NULL, 172.67, '', NULL, 0),
(126, 35, 287, 'dd', 'Small', 'Warm', 1, NULL, 172.67, '67f530a7a2d0a_Screenshot_2025-04-08-13-14-52-80_cc0c40aae00121c8e1b1866ef91e05c7.jpg', 5, 1),
(127, 41, 288, 's', 'Small', 'Warm', 1, NULL, 81.32, '67f5302389452_order_qr_308.png', NULL, 0),
(128, 41, 289, 's', 'Small', 'Warm', 1, NULL, 81.32, '67f59b95b6094_order_qr_308.png', 5, 1),
(129, 41, 290, '', 'Small', 'Warm', 1, '', 0.00, '67f5a97dd140f_order_qr_289.png', 5, 1),
(130, 40, 291, 'k', 'Small', 'Hot', 1, NULL, 53.60, '67f5b06a5c7e5_order_qr_289.png', NULL, 0),
(131, 41, 292, 's', 'Small', 'Warm', 1, NULL, 81.32, '', NULL, 0),
(132, 41, 293, 's', 'Small', 'Warm', 1, NULL, 81.32, '67f5c033a3d6d_order_qr_289.png', 5, 1),
(133, 41, 294, 's', 'Small', 'Warm', 1, NULL, 81.32, '67f5d2b24be58_order_qr_289.png', 5, 1),
(134, 41, 295, 's', 'Small', 'Warm', 4, NULL, 81.32, '67f68a992b28b_reservation_qr__br ____b_Warning__b__  Undefined variable $reservation in _b_C__xampp_htdocs_kahelcafe_user_views_order-track.php__b_ on line _b_855__b__br ____br ____b_Warning__b__  Trying to access array offset o.png', 5, 1),
(135, 34, 296, 'ww', '', '', 0, NULL, 308.84, '', NULL, 0),
(136, 34, 297, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(137, 34, 298, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(138, 34, 299, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(139, 34, 300, '', '', '', 1, '', 0.00, '', NULL, 0),
(140, 34, 301, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(141, 34, 302, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(142, 34, 303, 'ww', '', '', 1, NULL, 308.84, '67f7e79e8ec8a_this.png', 5, 1),
(143, 34, 304, 'ww', '', '', 1, NULL, 308.84, '67f7fc52398de_this.png', 5, 1),
(144, 34, 305, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(145, 34, 306, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(146, 34, 307, 'ww', '', '', 21, NULL, 308.84, '', NULL, 0),
(147, 34, 308, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(148, 34, 309, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(149, 34, 310, 'ww', '', '', 1, NULL, 308.84, '67f8069402a40_this.png', NULL, 0),
(150, 34, 311, 'ww', '', '', 1, NULL, 308.84, '67f80815c9bd1_this1.png', NULL, 0),
(151, 34, 312, 'ww', '', '', 2, NULL, 308.84, '67f87a8cbe654_GCash-639451755304-10042025223116.PNG.jpg', NULL, 0),
(152, 34, 313, '', '', '', 1, '', 0.00, '', NULL, 0),
(153, 34, 314, 'ww', '', '', 1, NULL, 308.84, '67f8c8b81403d_this1.png', NULL, 0),
(154, 34, 315, 'ww', '', '', 1, NULL, 308.84, '67f8dd3dc9b98_test1.jpg', 5, 1),
(155, 34, 316, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(156, 34, 317, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(157, 34, 318, 'ww', '', '', 1, NULL, 308.84, '67ffdf329e675_tr.png', NULL, 0),
(158, 34, 319, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(159, 34, 320, 'ww', '', '', 1, NULL, 308.84, '67ffe76a2242f_tr.png', 1, 1),
(160, 34, 321, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(161, 34, 322, 'ww', '', '', 0, NULL, 308.84, '', NULL, 0),
(162, 34, 323, 'ww', '', '', 1, NULL, 308.84, '', NULL, 0),
(163, 42, 324, 'ddu', '', '', 1, NULL, 20.12, '', NULL, 0),
(164, 51, 325, 'dddd', 'Medium', 'Hot', 1, NULL, 77.00, '681ad04db4cbc_uploadsBella1.png', 5, 1),
(165, 51, 326, 'dddd', 'Small', 'Hot', 2, NULL, 66.00, '', NULL, 0),
(166, 50, 326, 'ss', 'Small', 'Warm', 1, NULL, 199.00, '', NULL, 0),
(167, 51, 327, 'dddd', 'Small', 'Hot', 1, NULL, 66.00, '', NULL, 0),
(168, 51, 328, 'dddd', 'Small', 'Hot', 1, NULL, 66.00, '', NULL, 0),
(169, 52, 329, 'jjj', 'Small', 'Hot', 1, NULL, 77.00, '', NULL, 0),
(170, 51, 330, 'dddd', 'Medium', 'Hot', 1, NULL, 77.00, '', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `payment_settings`
--

CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL,
  `gcash_number` varchar(20) NOT NULL,
  `gcash_name` varchar(100) NOT NULL,
  `reservation_fee` decimal(10,2) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_settings`
--

INSERT INTO `payment_settings` (`id`, `gcash_number`, `gcash_name`, `reservation_fee`, `updated_at`) VALUES
(1, '09999999999', 'ioioioioioioi', 560.00, '2025-05-13 12:33:15');

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE `reservation` (
  `id` int(11) NOT NULL,
  `transaction_code` varchar(50) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `clientFullName` varchar(255) DEFAULT NULL,
  `reservation_date` date DEFAULT NULL,
  `reservation_time` varchar(20) DEFAULT NULL,
  `reservation_time_id` int(11) DEFAULT NULL,
  `party_size` int(11) DEFAULT NULL,
  `note_area` text DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `res_status` varchar(50) DEFAULT NULL,
  `receipt` varchar(255) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation`
--

INSERT INTO `reservation` (`id`, `transaction_code`, `client_id`, `clientFullName`, `reservation_date`, `reservation_time`, `reservation_time_id`, `party_size`, `note_area`, `amount`, `res_status`, `receipt`, `date_created`) VALUES
(2, 'RES-67F4F52CC5A07', 83, 'Jizzelliiioi Salongcong', '2025-04-09', '7:00pm- 8:00pm', 10, 1, '', 50.00, 'payment', NULL, '2025-04-08 18:06:36'),
(3, 'RES-67F4F766BEE21', 83, 'Jizzelliiioi Salongcong', '2025-04-10', '12:00pm- 1:00pm', 4, 1, '', 50.00, 'rate us', '67f4ffefc733c_order_qr_308.png', '2025-04-08 18:16:06'),
(4, 'RES-67F500DE4205D', 83, 'Jizzelliiioi Salongcong', NULL, '12:00pm- 1:00pm', 4, 1, '', 50.00, 'cancel', NULL, '2025-04-08 18:56:30'),
(5, 'RES-67F58643CAB6B', 86, 'Pppp Pppp', NULL, '7:00pm- 8:00pm', 10, 1, '', 50.00, 'cancel', NULL, '2025-04-09 04:25:39'),
(6, 'RES-67F5A5D33F513', 83, 'Jizzelliiioi Salongcong', '2025-04-11', '7:00pm- 8:00pm', 10, 1, '', 50.00, 'rate us', '67f5a6016fe89_order_qr_289.png', '2025-04-09 06:40:19'),
(7, 'RES-67F5AA4B130BA', 83, 'Jizzelliiioi Salongcong', '2025-04-09', '3:00pm- 4:00pm', 7, 1, '', 50.00, 'cancel', NULL, '2025-04-09 06:59:23'),
(8, 'RES-67F5AEDCE2411', 83, 'Jizzelliiioi Salongcong', '2025-04-09', '9:00am - 10:00am', 1, 1, '', 50.00, 'cancel', '67f5afa349948_order_qr_289.png', '2025-04-09 07:18:52'),
(9, 'RES-67F5B5601A664', 83, 'Jizzelliiioi Salongcong', '2025-04-11', '7:00pm- 8:00pm', 10, 1, '', 50.00, 'cancel', NULL, '2025-04-09 07:46:40'),
(10, 'RES-67F5B9168ACF6', 83, 'Jizzelliiioi Salongcong', '2025-04-11', '7:00pm- 8:00pm', 10, 1, '', 50.00, 'cancel', '67f5b94a8a9dd_order_qr_289.png', '2025-04-09 08:02:30'),
(11, 'RES-67F5B9CAB2469', 83, 'Jizzelliiioi Salongcong', NULL, '3:00pm- 4:00pm', 7, 1, '', 50.00, 'cancel', NULL, '2025-04-09 08:05:30'),
(12, 'RES-67F5BA19E9F83', 83, 'Jizzelliiioi Salongcong', '2025-04-09', '9:00am - 10:00am', 1, 1, '', 50.00, 'rate us', '67f5ba37ae21b_order_qr_289.png', '2025-04-09 08:06:49'),
(13, 'RES-67F5C2B8131A2', 83, 'Jizzelliiioi Salongcong', '2025-04-11', '3:00pm- 4:00pm', 7, 1, '', 50.00, 'cancel', '67f5c2ce28e0b_order_qr_289.png', '2025-04-09 08:43:36'),
(14, 'RES-67F5C33A0F47B', 83, 'Jizzelliiioi Salongcong', NULL, '7:00pm- 8:00pm', 10, 1, '', 50.00, 'cancel', NULL, '2025-04-09 08:45:46'),
(15, 'RES-67F5C34B1774B', 83, 'Jizzelliiioi Salongcong', '2025-04-11', '9:00am - 10:00am', 1, 1, '', 50.00, 'paid', '67f5c364ada68_order_qr_289.png', '2025-04-09 08:46:03'),
(16, 'RES-67F5CBDEB0A68', 83, 'Jizzelliiioi Salongcong', '2025-04-11', '7:00pm- 8:00pm', 10, 1, '', 50.00, 'rate us', '67f5cbf808cc9_order_qr_289.png', '2025-04-09 09:22:38'),
(17, 'RES-67F6A338B29EA', 83, 'Jizzelliiioi Salongcong', '2025-04-11', '7:00pm- 8:00pm', 10, 1, '', 50.00, 'cancel', '67f6a35a9d92b_this.png', '2025-04-10 00:41:28'),
(18, 'RES-67F6D33A5A505', 83, 'Jizzelliiioi Salongcong', '2025-04-10', '10:00am - 11:00am', 2, 1, '', 50.00, 'rate us', NULL, '2025-04-10 04:06:18'),
(19, 'RES-67F6D701CC694', 83, 'Jizzelliiioi Salongcong', NULL, '11:00am - 12:00pm', 3, 1, '', 50.00, 'cancel', NULL, '2025-04-10 04:22:25'),
(20, 'RES-67F6DE3B257D0', 86, 'Pppp Pppp', NULL, '10:00am - 11:00am', 2, 1, '', 50.00, 'cancel', NULL, '2025-04-10 04:53:15'),
(21, 'RES-67F6E6040B91C', 86, 'Pppp Pppp', '2025-04-10', '10:00am - 11:00am', 2, 1, '', 50.00, 'for confirmation', NULL, '2025-04-10 05:26:28'),
(22, 'RES-67F6E76D96A22', 86, 'Pppp Pppp', '2025-04-10', '9:00am - 10:00am', 1, 1, '', 50.00, 'booked', '67f6e78b57daa_this.png', '2025-04-10 05:32:29'),
(23, 'RES-67F6EA4CC84BC', 86, 'Pppp Pppp', NULL, '10:00am - 11:00am', 2, 1, '', 50.00, 'cancel', NULL, '2025-04-10 05:44:44'),
(24, 'RES-67F6EADDDAE3D', 86, 'Pppp Pppp', '2025-04-11', '10:00am - 11:00am', 2, 1, '', 50.00, 'for confirmation', NULL, '2025-04-10 05:47:09'),
(25, 'RES-67F6EB49CB13C', 86, 'Pppp Pppp', NULL, '11:00am - 12:00pm', 3, 1, '', 50.00, 'cancel', NULL, '2025-04-10 05:48:57'),
(26, 'RES-67F772BA80770', 83, 'Jizzelliiioi Salongcong', NULL, '7:00pm- 8:00pm', 10, 1, '', 50.00, 'cancel', NULL, '2025-04-10 15:26:50'),
(27, 'RES-67F7730E588E8', 83, 'Jizzelliiioi Salongcong', '2025-04-11', '1:00pm- 2:00pm', 5, 1, '', 50.00, 'booked', NULL, '2025-04-10 15:28:14'),
(28, 'RES-67F778B4D1A26', 83, 'Jizzelliiioi Salongcong', '2025-04-10', '11:00am - 12:00pm', 3, 1, '', 50.00, 'cancel', '67f778cad1084_this.png', '2025-04-10 15:52:20'),
(29, 'RES-67F77C855CF8D', 83, 'Jizzelliiioi Salongcong', '2025-04-10', '11:00am - 12:00pm', 3, 1, '', 50.00, 'rate us', NULL, '2025-04-10 16:08:37'),
(30, 'RES-67F78DBE01604', 83, 'Jizzelliiioi Salongcong', '2025-04-11', '5:00pm- 6:00pm', 8, 1, '', 50.00, 'payment', NULL, '2025-04-10 17:22:06'),
(31, 'RES-67F790F452EC6', 83, 'Jizzelliiioi Salongcong', NULL, '2:00pm- 3:00pm', 6, 1, '', 50.00, 'cancel', NULL, '2025-04-10 17:35:48'),
(32, 'RES-67F7C2112A85C', 86, 'Pppp Pppp', '2025-04-10', '11:00am - 12:00pm', 3, 1, '', 50.00, 'rate us', NULL, '2025-04-10 21:05:21'),
(33, 'RES-67F7C745A21C9', 86, 'Pppp Pppp', NULL, '1:00pm- 2:00pm', 5, 1, '', 50.00, 'cancel', NULL, '2025-04-10 21:27:33'),
(34, 'RES-67F7C780B84C0', 86, 'Pppp Pppp', '2025-04-11', '11:00am - 12:00pm', 3, 1, '', 50.00, 'for confirmation', NULL, '2025-04-10 21:28:32'),
(35, 'RES-67F7CABF8B261', 83, 'Jizzelliiioi Salongcong', '2025-04-11', '2:00pm- 3:00pm', 6, 1, '', 50.00, 'rate us', NULL, '2025-04-10 21:42:23'),
(36, 'RES-67F7E25828397', 83, 'Jizzelliiioi Salongcong', NULL, '2:00pm- 3:00pm', 6, 1, '', 50.00, 'cancel', NULL, '2025-04-10 23:23:04'),
(37, 'RES-67F80CEA671B1', 83, 'Jizzelliiioi Salongcong', NULL, '10:00am - 11:00am', 2, 1, '', 50.00, 'cancel', NULL, '2025-04-11 02:24:42'),
(38, 'RES-67F8DDBBAFF6E', 90, 'Duke Duke', '2025-04-12', '10:00am - 11:00am', 2, 1, '', 50.00, 'booked', '67f8ddcf8fa35_order_qr_315.png', '2025-04-11 17:15:39'),
(39, 'RES-681F64136AC13', 83, 'Jizzelliiioi Salongcong', '2025-05-17', '1:00pm- 2:00pm', 5, 1, '', 50.00, 'for confirmation', NULL, '2025-05-10 22:34:59'),
(40, 'RES-6820791888D05', 83, 'Jizzelliiioi Salongcong', '2025-05-11', '1:00pm- 2:00pm', 5, 1, '', 50.00, 'booked', '68223b2c0d47f_uploadsBella1.png', '2025-05-11 18:16:56'),
(41, 'RES-68224059E5FDF', 83, 'Jizzelliiioi Salongcong', NULL, '9:00am - 10:00am', 1, 1, '', 50.00, 'cancel', NULL, '2025-05-13 02:39:21'),
(42, 'RES-6822465D3A92A', 83, 'Jizzelliiioi Salongcong', '2025-05-13', '9:00am - 10:00am', 1, 1, '', 50.00, 'rate us', '682246af3b1d7_uploadsBella1.png', '2025-05-13 03:05:01'),
(43, 'RES-68224862934ED', 83, 'Jizzelliiioi Salongcong', '2025-05-17', '2:00pm- 3:00pm', 6, 1, '', 67676.00, 'booked', NULL, '2025-05-13 03:13:38');

--
-- Triggers `reservation`
--
DELIMITER $$
CREATE TRIGGER `after_reservation_update` AFTER UPDATE ON `reservation` FOR EACH ROW BEGIN
    /*
     * Synchronize status changes from reservation to orders table
     * Ensures both tables reflect the same state for linked records
     */
    IF NEW.res_status != OLD.res_status THEN
        UPDATE orders 
        SET status = NEW.res_status 
        WHERE reservation_id = NEW.id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `resservation_time`
--

CREATE TABLE `resservation_time` (
  `id` int(11) NOT NULL,
  `reservation_time_id` int(11) DEFAULT NULL,
  `reservation_time` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resservation_time`
--

INSERT INTO `resservation_time` (`id`, `reservation_time_id`, `reservation_time`) VALUES
(2, 10, '7:00pm- 8:00pm'),
(5, 10, '7:00pm- 8:00pm'),
(6, 10, '7:00pm- 8:00pm'),
(7, 7, '3:00pm- 4:00pm'),
(8, 1, '9:00am - 10:00am'),
(9, 10, '7:00pm- 8:00pm'),
(10, 10, '7:00pm- 8:00pm'),
(11, 7, '3:00pm- 4:00pm'),
(12, 1, '9:00am - 10:00am'),
(13, 7, '3:00pm- 4:00pm'),
(14, 10, '7:00pm- 8:00pm'),
(15, 1, '9:00am - 10:00am'),
(16, 10, '7:00pm- 8:00pm'),
(17, 10, '7:00pm- 8:00pm'),
(18, 2, '10:00am - 11:00am'),
(19, 3, '11:00am - 12:00pm'),
(20, 2, '10:00am - 11:00am'),
(21, 2, '10:00am - 11:00am'),
(22, 1, '9:00am - 10:00am'),
(23, 2, '10:00am - 11:00am'),
(24, 2, '10:00am - 11:00am'),
(25, 3, '11:00am - 12:00pm'),
(26, 10, '7:00pm- 8:00pm'),
(27, 5, '1:00pm- 2:00pm'),
(28, 3, '11:00am - 12:00pm'),
(29, 3, '11:00am - 12:00pm'),
(30, 8, '5:00pm- 6:00pm'),
(31, 6, '2:00pm- 3:00pm'),
(32, 3, '11:00am - 12:00pm'),
(33, 5, '1:00pm- 2:00pm'),
(34, 3, '11:00am - 12:00pm'),
(35, 6, '2:00pm- 3:00pm'),
(36, 6, '2:00pm- 3:00pm'),
(37, 2, '10:00am - 11:00am'),
(38, 2, '10:00am - 11:00am'),
(39, 5, '1:00pm- 2:00pm'),
(40, 5, '1:00pm- 2:00pm'),
(41, 1, '9:00am - 10:00am'),
(42, 1, '9:00am - 10:00am'),
(43, 6, '2:00pm- 3:00pm');

-- --------------------------------------------------------

--
-- Table structure for table `res_time`
--

CREATE TABLE `res_time` (
  `id` int(11) NOT NULL,
  `time` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `res_time`
--

INSERT INTO `res_time` (`id`, `time`) VALUES
(1, '9:00am - 10:00am'),
(2, '10:00am - 11:00am'),
(3, '11:00am - 12:00pm'),
(4, '12:00pm- 1:00pm'),
(5, '1:00pm- 2:00pm'),
(6, '2:00pm- 3:00pm'),
(7, '3:00pm- 4:00pm'),
(8, '5:00pm- 6:00pm'),
(9, '6:00pm- 7:00pm'),
(10, '7:00pm- 8:00pm');

-- --------------------------------------------------------

--
-- Table structure for table `virt`
--

CREATE TABLE `virt` (
  `id` int(11) NOT NULL,
  `content` varchar(255) NOT NULL,
  `date_created` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `virt`
--

INSERT INTO `virt` (`id`, `content`, `date_created`) VALUES
(1, './../../uploads/475354308_625022750479290_1519575774274973835_n.jpg', '0000-00-00'),
(2, './../../uploads/this.png', '0000-00-00'),
(3, './../../uploads/test1.jpg', '0000-00-00');

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
-- Indexes for table `chart`
--
ALTER TABLE `chart`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gcash`
--
ALTER TABLE `gcash`
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
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payment_settings`
--
ALTER TABLE `payment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `resservation_time`
--
ALTER TABLE `resservation_time`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `virt`
--
ALTER TABLE `virt`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_list`
--
ALTER TABLE `admin_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=261;

--
-- AUTO_INCREMENT for table `client`
--
ALTER TABLE `client`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `gcash`
--
ALTER TABLE `gcash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `menu1`
--
ALTER TABLE `menu1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=331;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `payment_settings`
--
ALTER TABLE `payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `resservation_time`
--
ALTER TABLE `resservation_time`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `virt`
--
ALTER TABLE `virt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
