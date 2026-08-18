-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 18, 2026 at 10:34 AM
-- Server version: 11.8.8-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u533806958_inspectionform`
--

-- --------------------------------------------------------

--
-- Table structure for table `inspections`
--

CREATE TABLE `inspections` (
  `id` varchar(40) NOT NULL,
  `client` varchar(255) NOT NULL,
  `inspection_date` date NOT NULL,
  `report_number` varchar(100) DEFAULT '',
  `address` varchar(500) NOT NULL,
  `iecc_year` varchar(10) DEFAULT '',
  `iecc_year2` varchar(10) DEFAULT '',
  `areas` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`areas`)),
  `fee` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_option` tinyint(4) DEFAULT NULL,
  `re_field` varchar(500) DEFAULT '',
  `pm_name` varchar(255) DEFAULT '',
  `pm_cell` varchar(50) DEFAULT '',
  `company_name` varchar(255) DEFAULT '',
  `company_contact` varchar(255) DEFAULT '',
  `signature_name` varchar(255) DEFAULT '',
  `signature_date` date DEFAULT NULL,
  `recipient_email` varchar(255) DEFAULT '',
  `saved_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inspections`
--

INSERT INTO `inspections` (`id`, `client`, `inspection_date`, `report_number`, `address`, `iecc_year`, `iecc_year2`, `areas`, `fee`, `payment_method`, `payment_option`, `re_field`, `pm_name`, `pm_cell`, `company_name`, `company_contact`, `signature_name`, `signature_date`, `recipient_email`, `saved_at`) VALUES
('r6a5ddfdadc16b6.27469794', 'Pradosh Mukh', '2026-07-20', '0023', 'Circus Maidan Near Anita Studio, Katwa\nPo Katwa', '20026', '20026', '[{\"name\":\"Attic insulation\",\"status\":\"pass\"},{\"name\":\"Wall insulation\",\"status\":\"fail\"},{\"name\":\"Air sealing \\/ infiltration\",\"status\":\"pass\"},{\"name\":\"Duct testing\",\"status\":\"fail\"},{\"name\":\"Window U-factor \\/ SHGC\",\"status\":\"pass\"}]', 100.00, 'Cash', 2, '20026', 'test Manager', '20026', 'sdfsfasfasfdasfd', 'sfgsfsdgffdgfdsgdgsd', 'gdfsgdsgdfgddgfs', '2026-07-11', 'pradoshbig0@gmail.com', '2026-07-20 08:44:10');

-- --------------------------------------------------------

--
-- Table structure for table `plumbing_inspections`
--

CREATE TABLE `plumbing_inspections` (
  `id` varchar(40) NOT NULL,
  `report_title` varchar(255) DEFAULT '',
  `client` varchar(255) NOT NULL,
  `inspection_address` varchar(500) NOT NULL,
  `inspector_license` varchar(100) DEFAULT '',
  `inspector_name` varchar(255) DEFAULT '',
  `inspection_date` date NOT NULL,
  `scope_plumbing` tinyint(1) NOT NULL DEFAULT 0,
  `scope_electrical` tinyint(1) NOT NULL DEFAULT 0,
  `scope_hvac` tinyint(1) NOT NULL DEFAULT 0,
  `scope_other` tinyint(1) NOT NULL DEFAULT 0,
  `scope_other_text` varchar(255) DEFAULT '',
  `parties_superintendent` tinyint(1) NOT NULL DEFAULT 0,
  `parties_subcontractor` tinyint(1) NOT NULL DEFAULT 0,
  `parties_other` tinyint(1) NOT NULL DEFAULT 0,
  `parties_other_text` varchar(255) DEFAULT '',
  `weather` varchar(20) DEFAULT '',
  `time_of_inspection` varchar(20) DEFAULT '',
  `outside_temp` varchar(20) DEFAULT '',
  `additional_info` varchar(10) DEFAULT '',
  `items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items`)),
  `recipient_email` varchar(255) DEFAULT '',
  `saved_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `plumbing_inspections`
--

INSERT INTO `plumbing_inspections` (`id`, `report_title`, `client`, `inspection_address`, `inspector_license`, `inspector_name`, `inspection_date`, `scope_plumbing`, `scope_electrical`, `scope_hvac`, `scope_other`, `scope_other_text`, `parties_superintendent`, `parties_subcontractor`, `parties_other`, `parties_other_text`, `weather`, `time_of_inspection`, `outside_temp`, `additional_info`, `items`, `recipient_email`, `saved_at`) VALUES
('p6a5def75444a33.99966229', 'text', 'Pradosh Mukh', 'Katwa', '100002', 'James Southerland', '2026-07-20', 1, 0, 0, 0, '', 1, 0, 0, '', 'Sunny', '15:20', '82', 'No', '[{\"category\":\"I. MEP\",\"subcategory\":\"A. Underground Plumbing\",\"status\":\"I\",\"findings\":\"test test\"}]', 'pradoshbig0@gmail.com', '2026-07-20 09:50:45'),
('p6a5df0946dc123.91438285', 'Test', 'Dean Ambrose', 'San Diego', '879546', 'James Southerland', '2026-07-20', 1, 0, 0, 0, '', 0, 1, 0, '', 'Sunny', '18:28', '56', 'No', '[{\"category\":\"I. MEP\",\"subcategory\":\"A. Underground Plumbing\",\"status\":\"I\",\"findings\":\"\"}]', 'tirthabig0@gmail.com', '2026-07-20 09:55:32'),
('p6a5f9c4ae31654.06641114', 'Test Report Checking', 'Gourav Biswas', '204 Chatsworth, LA, USA', '098745', 'James Southerland', '2026-07-21', 0, 1, 0, 0, '', 1, 0, 0, '', 'Sunny', '22:52', '82F', 'No', '[{\"category\":\"I. MEP\",\"subcategory\":\"A. Underground Plumbing\",\"status\":\"I\",\"findings\":\"Test notes for the fidings\"}]', 'gourav@pravixaai.com', '2026-07-21 16:20:26'),
('p6a5fa2ccd59b11.60542847', 'text', 'Pradosh Mukh', 'asfsafsafasfas', '100002', 'James Southerland', '2026-07-21', 1, 0, 0, 0, '', 0, 0, 0, '', 'Sunny', '22:18', '82', 'No', '[{\"category\":\"I. MEP\",\"subcategory\":\"A. Underground Plumbing\",\"status\":\"I\",\"findings\":\"\"}]', 'pradoshbig0@gmail.com', '2026-07-21 16:48:12'),
('p6a680bf4988ae7.03127972', '7.28 AUXILIARY BUILDING', 'OTC Cypress', '16614 spring Cypress Cypress Texas 77429 auxiliary', '', 'James Southerland', '2026-07-27', 1, 0, 0, 0, '', 0, 0, 0, '', 'Sunny', '22:01', '96°', 'No', '[{\"category\":\"I. MEP\",\"subcategory\":\"A. Underground Plumbing\",\"status\":\"I\",\"findings\":\"Passed - PVC plumbing pipe has been assembled and embedded in sand per plans\"}]', '', '2026-07-28 01:55:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `reset_token`, `reset_token_expires`, `created_at`) VALUES
(1, 'James', 'james@adinspections.com', '$2y$10$dnFDy99wuMIY8hOJMCe22OuEjUdkbhoKsuLuESiRpqAnRpyTDJM12', '84b65379174ccb4ca0794a050c4ef533fa38e39029c9350197a57110fda79ae1', '2026-07-29 14:22:12', '2026-07-11 07:32:19');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inspections`
--
ALTER TABLE `inspections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_saved_at` (`saved_at`);

--
-- Indexes for table `plumbing_inspections`
--
ALTER TABLE `plumbing_inspections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plumbing_saved_at` (`saved_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
