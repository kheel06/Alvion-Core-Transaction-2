-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 29, 2025 at 11:10 AM
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
-- Database: `hospital-fin-system`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `employee_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(8, 'C1-2025-02', 'login', 'authentication', NULL, NULL, '{\"details\":\"Employee C1-2025-02 logged in successfully\",\"employee_id\":\"C1-2025-02\",\"ip_address\":\"192.168.100.9\",\"timestamp\":\"2025-11-24 09:39:45\"}', '192.168.100.9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 08:39:45'),
(11, 'C1-2025-01', 'logout', 'authentication', NULL, NULL, '{\"details\":\"Employee C1-2025-01 logged out\",\"employee_id\":\"C1-2025-01\",\"ip_address\":\"192.168.100.9\",\"timestamp\":\"2025-11-24 10:00:54\"}', '192.168.100.9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:00:54'),
(12, 'C1-2025-01', 'login', 'authentication', NULL, NULL, '{\"details\":\"Employee C1-2025-01 logged in successfully\",\"employee_id\":\"C1-2025-01\",\"ip_address\":\"192.168.100.9\",\"timestamp\":\"2025-11-24 10:02:19\"}', '192.168.100.9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:02:19'),
(13, 'C1-2025-02', 'login', 'authentication', NULL, NULL, '{\"details\":\"Employee C1-2025-02 logged in successfully\",\"employee_id\":\"C1-2025-02\",\"ip_address\":\"192.168.100.9\",\"timestamp\":\"2025-11-24 10:04:15\"}', '192.168.100.9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 09:04:15'),
(14, 'C1-2025-02', 'logout', 'authentication', NULL, NULL, '{\"details\":\"Employee C1-2025-02 logged out\",\"employee_id\":\"C1-2025-02\",\"ip_address\":\"192.168.100.9\",\"timestamp\":\"2025-11-24 13:00:01\"}', '192.168.100.9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 12:00:01'),
(15, 'C1-2025-01', 'logout', 'authentication', NULL, NULL, '{\"details\":\"Employee C1-2025-01 logged out\",\"employee_id\":\"C1-2025-01\",\"ip_address\":\"192.168.100.9\",\"timestamp\":\"2025-11-24 15:01:50\"}', '192.168.100.9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 14:01:50'),
(16, NULL, 'login', 'authentication', NULL, NULL, '{\"details\":\"Employee employee logged in\",\"employee_id\":null,\"ip_address\":\"192.168.100.9\",\"timestamp\":\"2025-11-24 15:03:03\"}', '192.168.100.9', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 14:03:03');

-- --------------------------------------------------------

--
-- Table structure for table `department_accounts`
--

CREATE TABLE `department_accounts` (
  `employee_id` varchar(11) NOT NULL,
  `employee_fname` varchar(100) NOT NULL,
  `employee_lname` varchar(100) NOT NULL,
  `employee_email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` enum('active','inactive','suspended','pending') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_accounts`
--

INSERT INTO `department_accounts` (`employee_id`, `employee_fname`, `employee_lname`, `employee_email`, `password`, `role_name`, `profile_picture`, `is_active`, `status`, `last_login`, `created_at`, `updated_at`) VALUES
('F-2025-01', 'Michael', 'Petras', 'petrasmichael06@gmail.com', 'F202501#AV06', 'super_admin', 'assets/uploads/profile_pictures/employee_profile_C1-2025-01_1763409947.gif', 1, 'active', '2025-11-24 09:02:19', '2025-11-17 13:34:35', '2025-11-29 10:09:45'),
('F-2025-02', 'Michael', 'Petras', 'michaelpetras123@gmail.com', 'F202502#AV06', 'admin', NULL, 1, 'active', '2025-11-26 16:19:01', '2025-11-17 13:34:35', '2025-11-29 10:09:59'),
('F-2025-03', 'Staff', 'Petras', '	\npetrasmichael06@gmail.com', 'F202503#AV06', 'staff', NULL, 1, 'active', NULL, '2025-11-17 13:34:35', '2025-11-29 10:10:04'),
('F-2025-04', 'Employee', 'Petras', 'jdoe.finance@admin.alvion.com', 'F202506#AV06', 'employee', NULL, 1, 'active', NULL, '2025-11-17 13:34:35', '2025-11-29 10:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employee_number` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('M','F','O') DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` enum('active','inactive','resigned','terminated','on_leave') DEFAULT 'active',
  `department_id` bigint(20) UNSIGNED DEFAULT NULL,
  `position` varchar(150) DEFAULT NULL,
  `employment_type` enum('regular','contract','casual','consultant','part_time') DEFAULT 'regular',
  `email` varchar(150) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'Full system control', '2025-11-24 16:51:41', '2025-11-24 16:51:41'),
(2, 'admin', 'HR / Timekeeping admin', '2025-11-24 16:51:41', '2025-11-24 16:51:41'),
(3, 'staff', 'Supervisor / Dept Head', '2025-11-24 16:51:41', '2025-11-24 16:51:41'),
(4, 'employee', 'Employee portal account', '2025-11-24 16:51:41', '2025-11-24 16:51:41');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `permission_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `department_accounts`
--
ALTER TABLE `department_accounts`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `employee_email` (`employee_email`),
  ADD KEY `fk_role_name` (`role_name`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_number` (`employee_number`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
