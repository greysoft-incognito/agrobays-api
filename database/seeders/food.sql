-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2022 at 05:21 PM
-- Server version: 10.5.15-MariaDB-cll-lve
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Dumping data for table `food`
--

INSERT INTO `food` (`id`, `food_bag_id`, `name`, `description`, `weight`, `image`, `created_at`, `updated_at`) VALUES
(1, 1, 'Rice', 'an sort after foodbag with exquisite package', '5kg', 'public/uploads/images/331187855_1626754096.jpg', '2022-04-01 16:14:54', '2022-04-01 16:14:54'),
(2, 1, 'Beans', 'a well sort after pack', '5kg', 'public/uploads/images/897535437_604669899.jpg', '2022-04-01 16:15:47', '2022-04-01 16:15:47'),
(3, 1, 'Orange', 'orange orange orange', '600g', 'public/uploads/images/2124570262_1922365797.png', '2022-04-01 16:16:48', '2022-04-01 16:16:48'),
(4, 1, 'Carrot', 'carrot carrot carrot', '300g', 'public/uploads/images/1379810441_732995598.jpg', '2022-04-01 16:18:07', '2022-04-01 16:18:07'),
(5, 2, 'Rice', 'likee rice on fireeee', '10kg', 'public/uploads/images/723306719_901411457.jpg', '2022-04-01 16:19:08', '2022-04-01 16:19:08'),
(6, 2, 'Beans', 'mung beans beans', '1kg', 'public/uploads/images/470784670_1000028914.jpg', '2022-04-01 16:20:24', '2022-04-01 16:20:24'),
(7, 2, 'Lime', 'Lime Lime Citrus', '4kg', 'public/uploads/images/1635980665_667966377.jpg', '2022-04-01 16:21:30', '2022-04-01 16:21:30'),
(8, 3, 'Mango', 'mango is a type of fruit', '1kg', 'public/uploads/images/740393636_1649524124.jpg', '2022-04-01 16:22:48', '2022-04-01 16:22:48'),
(9, 3, 'Paw Paw', 'Paw Paw is a kind of fruit sweet like sugar', '5kg', 'public/uploads/images/276224287_714135846.jpg', '2022-04-01 16:24:03', '2022-04-01 16:24:03'),
(10, 4, 'Banana', 'banana is good for you', '10kg', 'public/uploads/images/981261877_961204285.png', '2022-04-01 16:24:56', '2022-04-01 16:24:56'),
(11, 4, 'Pea', 'pea pea pea pea pea', '5kg', 'public/uploads/images/1883300436_347682806.jpg', '2022-04-01 16:25:52', '2022-04-01 16:25:52'),
(12, 5, 'Green Beans', 'greener greener greener', '4kg', 'public/uploads/images/1182994497_1525950568.jpg', '2022-04-01 16:26:40', '2022-04-01 16:26:40'),
(13, 9, 'Beans', 'munged beans beans', '50kg', 'public/uploads/images/529762226_1356788169.jpg', '2022-04-01 16:27:44', '2022-04-01 16:27:44'),
(14, 9, 'Grape', 'grape fruit greener', '10kg', 'public/uploads/images/2134081014_1518180081.jpg', '2022-04-01 16:28:53', '2022-04-01 16:28:53'),
(15, 4, 'Orange', 'orange orange orange', '4g', 'public/uploads/images/1040574570_667296698.png', '2022-04-01 17:40:12', '2022-04-01 17:40:12'),
(16, 4, 'Apple', 'Apple Apple Apple', '', 'public/uploads/images/497089112_231971298.jpg', '2022-04-01 17:40:59', '2022-04-01 17:40:59'),
(17, 5, 'Orange', 'oroma orange orange', '1g', 'public/uploads/images/1895328337_146889141.png', '2022-04-01 17:41:57', '2022-04-01 17:41:57'),
(18, 5, 'Beans', 'beans i like you', '5kg', 'public/uploads/images/406855054_2045059350.jpg', '2022-04-01 17:42:50', '2022-04-01 17:42:50'),
(19, 6, 'Rice', 'BAg of rice rice rice', '20kg', 'public/uploads/images/2872841_981649129.png', '2022-04-01 17:44:07', '2022-04-01 17:44:07'),
(20, 6, 'Banana', 'banana banana banana', '4kg', 'public/uploads/images/963063789_1540633106.png', '2022-04-01 17:44:50', '2022-04-01 17:44:50'),
(21, 6, 'Banana', 'banana banana banana', '4kg', 'public/uploads/images/870193431_1716843907.png', '2022-04-01 17:45:44', '2022-04-01 17:45:44'),
(22, 7, 'Rice', 'a bag of rice is big', '60kg', 'public/uploads/images/393561674_185335752.jpg', '2022-04-01 17:47:17', '2022-04-01 17:47:17'),
(23, 7, 'Beans', 'beans beans beans', '50kg', 'public/uploads/images/1916682039_90508515.jpg', '2022-04-01 17:47:59', '2022-04-01 17:47:59'),
(24, 7, 'Carrot', 'Carrot Carrot Carrot', '600g', 'public/uploads/images/585012948_218220457.jpg', '2022-04-01 17:49:06', '2022-04-01 17:49:06'),
(25, 7, 'Carrot', 'car car car is a carrot', '3kg', 'public/uploads/images/126922628_1403004904.jpg', '2022-04-01 17:51:07', '2022-04-01 17:51:07'),
(26, 8, 'Cabbage', 'cabbbage cabbage cabbage', '4kg', 'public/uploads/images/2094948526_1902909716.jpg', '2022-04-01 17:52:07', '2022-04-01 17:52:07'),
(27, 8, 'Cabbage', 'cabbage cabbage cabbage', '4kg', 'public/uploads/images/211891138_491206519.jpg', '2022-04-01 18:19:03', '2022-04-01 18:19:03'),
(28, 8, 'Banana', 'banana banana banana', '6kg', 'public/uploads/images/1559617614_1374482678.png', '2022-04-01 18:21:02', '2022-04-01 18:21:02');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
