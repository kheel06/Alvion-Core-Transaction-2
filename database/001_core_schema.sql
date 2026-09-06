-- Core schema for hospital-core2-system
-- Patients, appointments, er_triage, beds, users, otp_codes, clinic_rooms, billing

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- roles (required by permissions)
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `role_description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- department_accounts (employee login)
CREATE TABLE IF NOT EXISTS `department_accounts` (
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- users (doctors, nurses, etc.)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- patients
CREATE TABLE IF NOT EXISTS `patients` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hospital_id` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- clinic_rooms
CREATE TABLE IF NOT EXISTS `clinic_rooms` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_number` varchar(50) NOT NULL,
  `room_name` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'available',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- appointments
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) UNSIGNED NOT NULL,
  `doctor_id` int(11) UNSIGNED DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('scheduled','confirmed','in_progress','completed','cancelled','no_show') DEFAULT 'scheduled',
  `room_id` int(11) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- er_triage
CREATE TABLE IF NOT EXISTS `er_triage` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) UNSIGNED NOT NULL,
  `triage_level` enum('resuscitation','emergency','urgent','semi_urgent','non_urgent') NOT NULL,
  `status` enum('waiting','in_progress','admitted','discharged','transferred') DEFAULT 'waiting',
  `triage_nurse_id` int(11) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- beds
CREATE TABLE IF NOT EXISTS `beds` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `bed_number` varchar(50) NOT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `status` enum('available','occupied','maintenance','reserved') DEFAULT 'available',
  `patient_id` int(11) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- billing
CREATE TABLE IF NOT EXISTS `billing` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) UNSIGNED NOT NULL,
  `bill_date` date NOT NULL,
  `total_amount` decimal(12,2) DEFAULT 0.00,
  `payment_status` enum('pending','partial','paid') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `created_by` int(11) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- otp_codes (login verification)
CREATE TABLE IF NOT EXISTS `otp_codes` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `user_id` int(11) UNSIGNED DEFAULT NULL,
  `department_account_id` varchar(50) DEFAULT NULL,
  `source_table` varchar(50) DEFAULT NULL,
  `destination` varchar(255) NOT NULL,
  `purpose` varchar(50) DEFAULT 'login',
  `expires_at` datetime NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `consumed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
