-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 22, 2026 at 04:30 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `food_waste_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `full_name`, `email`, `password`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$7XcBiTL6MXDX9j3ufO9w5.k9Aw7UqWanqwZJttXsdzmJHbG/lgqdS', '2026-07-21 21:27:43');

-- --------------------------------------------------------

--
-- Table structure for table `charities`
--

CREATE TABLE `charities` (
  `charity_id` int(11) NOT NULL,
  `organization_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `registration_number` varchar(100) DEFAULT NULL,
  `account_status` enum('Active','Suspended') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `charities`
--

INSERT INTO `charities` (`charity_id`, `organization_name`, `email`, `password`, `phone`, `address`, `registration_number`, `account_status`, `created_at`, `updated_at`) VALUES
(1, 'HT', 'charity@gmail.com', '$2y$10$vvLyTuVoP0TTNlDEME2vuO6maoTr7fKNGa12lUOXwNw44jJ/ZVtRy', '0542448862', 'riyadh', '125456', 'Active', '2026-07-21 21:21:58', '2026-07-21 21:21:58');

-- --------------------------------------------------------

--
-- Table structure for table `donation_requests`
--

CREATE TABLE `donation_requests` (
  `request_id` int(11) NOT NULL,
  `donation_id` int(11) NOT NULL,
  `charity_id` int(11) NOT NULL,
  `request_message` text DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Rejected','Cancelled','Completed') DEFAULT 'Pending',
  `donor_response` text DEFAULT NULL,
  `response_date` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donation_requests`
--

INSERT INTO `donation_requests` (`request_id`, `donation_id`, `charity_id`, `request_message`, `request_date`, `status`, `donor_response`, `response_date`, `updated_at`) VALUES
(1, 1, 1, 'hhghh', '2026-07-21 21:22:40', 'Approved', NULL, NULL, '2026-07-21 21:24:15');

-- --------------------------------------------------------

--
-- Table structure for table `donors`
--

CREATE TABLE `donors` (
  `donor_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `donor_type` enum('Individual','Restaurant','Hotel','Supermarket','Other') NOT NULL DEFAULT 'Individual',
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `account_status` enum('Active','Suspended') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `donors`
--

INSERT INTO `donors` (`donor_id`, `full_name`, `donor_type`, `email`, `password`, `phone`, `address`, `account_status`, `created_at`, `updated_at`) VALUES
(1, 'User', 'Individual', 'user@gmail.com', '$2y$10$7XcBiTL6MXDX9j3ufO9w5.k9Aw7UqWanqwZJttXsdzmJHbG/lgqdS', '0542448862', 'riyadh', 'Active', '2026-07-21 20:50:39', '2026-07-21 21:38:14');

-- --------------------------------------------------------

--
-- Table structure for table `food_donations`
--

CREATE TABLE `food_donations` (
  `donation_id` int(11) NOT NULL,
  `donor_id` int(11) NOT NULL,
  `food_name` varchar(100) NOT NULL,
  `category` enum('Cooked Meals','Bakery','Fruits','Vegetables','Dairy','Dry Food','Other') NOT NULL,
  `quantity` int(11) NOT NULL,
  `quantity_unit` enum('Meals','Kilograms','Boxes','Bags','Pieces','Liters') NOT NULL,
  `description` text DEFAULT NULL,
  `preparation_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `pickup_location` varchar(255) NOT NULL,
  `pickup_date` date DEFAULT NULL,
  `pickup_time` time DEFAULT NULL,
  `status` enum('Available','Requested','Approved','Completed','Expired','Cancelled') NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `food_donations`
--

INSERT INTO `food_donations` (`donation_id`, `donor_id`, `food_name`, `category`, `quantity`, `quantity_unit`, `description`, `preparation_date`, `expiry_date`, `pickup_location`, `pickup_date`, `pickup_time`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'food', 'Cooked Meals', 2, 'Meals', 'dddddddd', '2026-07-22', '2026-07-30', 'rrr', '2026-07-23', '00:04:00', 'Completed', '2026-07-21 21:06:09', '2026-07-21 21:40:27');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `report_type` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `report_data` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `charities`
--
ALTER TABLE `charities`
  ADD PRIMARY KEY (`charity_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `donation_requests`
--
ALTER TABLE `donation_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `uq_charity_donation_request` (`donation_id`,`charity_id`),
  ADD KEY `idx_requests_donation` (`donation_id`),
  ADD KEY `idx_requests_charity` (`charity_id`),
  ADD KEY `idx_requests_status` (`status`);

--
-- Indexes for table `donors`
--
ALTER TABLE `donors`
  ADD PRIMARY KEY (`donor_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `food_donations`
--
ALTER TABLE `food_donations`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `idx_donations_donor` (`donor_id`),
  ADD KEY `idx_donations_status` (`status`),
  ADD KEY `idx_donations_expiry` (`expiry_date`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_reports_admin` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `charities`
--
ALTER TABLE `charities`
  MODIFY `charity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `donation_requests`
--
ALTER TABLE `donation_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `donors`
--
ALTER TABLE `donors`
  MODIFY `donor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `food_donations`
--
ALTER TABLE `food_donations`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `donation_requests`
--
ALTER TABLE `donation_requests`
  ADD CONSTRAINT `fk_request_charity` FOREIGN KEY (`charity_id`) REFERENCES `charities` (`charity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_request_donation` FOREIGN KEY (`donation_id`) REFERENCES `food_donations` (`donation_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `food_donations`
--
ALTER TABLE `food_donations`
  ADD CONSTRAINT `fk_donation_donor` FOREIGN KEY (`donor_id`) REFERENCES `donors` (`donor_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
