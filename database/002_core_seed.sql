-- Core seed data for hospital-core2-system

INSERT IGNORE INTO `roles` (`id`, `role_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'Full system control', NOW(), NOW()),
(2, 'admin', 'HR / Timekeeping admin', NOW(), NOW()),
(3, 'staff', 'Supervisor / Dept Head', NOW(), NOW()),
(4, 'employee', 'Employee portal account', NOW(), NOW()),
(5, 'doctor', 'Doctor portal', NOW(), NOW()),
(6, 'nurse', 'Nursing staff', NOW(), NOW()),
(7, 'receptionist', 'Front desk', NOW(), NOW());

INSERT IGNORE INTO `department_accounts` (`employee_id`, `employee_fname`, `employee_lname`, `employee_email`, `password`, `role_name`, `is_active`, `status`) VALUES
('F-2025-01', 'Michael', 'Petras', 'petrasmichael06@gmail.com', 'F202501#AV06', 'super_admin', 1, 'active'),
('F-2025-02', 'Michael', 'Petras', 'michaelpetras123@gmail.com', 'F202502#AV06', 'admin', 1, 'active'),
('F-2025-03', 'Staff', 'Petras', 'staff@admin.alvion.com', 'F202503#AV06', 'staff', 1, 'active'),
('F-2025-04', 'Employee', 'Petras', 'employee@admin.alvion.com', 'F202506#AV06', 'employee', 1, 'active');

-- Sample patients (P001-P030 for lab orders)
INSERT IGNORE INTO `patients` (`id`, `hospital_id`, `first_name`, `last_name`, `birth_date`, `gender`, `email`, `contact_number`, `status`, `created_at`) VALUES
(1, 'P001', 'Juan', 'Dela Cruz', '1985-03-15', 'M', 'juan@example.com', '09171234567', 'active', NOW()),
(2, 'P002', 'Maria', 'Santos', '1990-07-22', 'F', 'maria@example.com', '09187654321', 'active', NOW()),
(3, 'P003', 'Pedro', 'Reyes', '1978-11-08', 'M', 'pedro@example.com', '09171112222', 'active', NOW());

-- Sample beds
INSERT IGNORE INTO `beds` (`id`, `bed_number`, `ward`, `status`) VALUES
(1, 'B-101', 'Ward A', 'available'),
(2, 'B-102', 'Ward A', 'available'),
(3, 'B-103', 'Ward A', 'occupied'),
(4, 'B-201', 'Ward B', 'available'),
(5, 'B-202', 'Ward B', 'available');
