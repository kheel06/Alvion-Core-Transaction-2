-- Surgery & Operating Room Scheduler (SORS) Database Tables
-- Run this SQL script to create all required tables for the SORS module

-- Supporting Tables First

-- Table: surgeons
CREATE TABLE IF NOT EXISTS surgeons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    privilege_level ENUM('Level 1', 'Level 2', 'Level 3', 'Level 4') DEFAULT 'Level 1',
    license_number VARCHAR(50),
    phone VARCHAR(20),
    email VARCHAR(100),
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_specialization (specialization),
    INDEX idx_status (status),
    INDEX idx_privilege_level (privilege_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: anesthesiologists
CREATE TABLE IF NOT EXISTS anesthesiologists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    license_number VARCHAR(50),
    phone VARCHAR(20),
    email VARCHAR(100),
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_specialization (specialization)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: procedure_catalog
CREATE TABLE IF NOT EXISTS procedure_catalog (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    procedure_code VARCHAR(50) UNIQUE NOT NULL,
    procedure_name VARCHAR(200) NOT NULL,
    category VARCHAR(100),
    specialty VARCHAR(100),
    estimated_duration INT UNSIGNED DEFAULT 60 COMMENT 'Duration in minutes',
    default_equipment TEXT,
    requirements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_specialty (specialty),
    INDEX idx_procedure_code (procedure_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Main SORS Tables

-- Table: operating_rooms
CREATE TABLE IF NOT EXISTS operating_rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) UNIQUE NOT NULL,
    room_type ENUM('General', 'Cardiac', 'Neuro', 'Orthopedic', 'Pediatric', 'Emergency') DEFAULT 'General',
    status ENUM('Ready', 'In Use', 'Cleaning', 'Maintenance') DEFAULT 'Ready',
    capacity INT UNSIGNED DEFAULT 1,
    floor_number INT,
    location VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_room_type (room_type),
    INDEX idx_status (status),
    INDEX idx_room_number (room_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: or_equipment
CREATE TABLE IF NOT EXISTS or_equipment (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    or_id INT UNSIGNED NOT NULL,
    equipment_name VARCHAR(200) NOT NULL,
    equipment_type VARCHAR(100),
    serial_number VARCHAR(100),
    status ENUM('Available', 'In Use', 'Maintenance', 'Out of Service') DEFAULT 'Available',
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (or_id) REFERENCES operating_rooms(id) ON DELETE CASCADE,
    INDEX idx_or_id (or_id),
    INDEX idx_status (status),
    INDEX idx_equipment_type (equipment_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: surgery_blocks
CREATE TABLE IF NOT EXISTS surgery_blocks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    block_name VARCHAR(100),
    surgeon_id INT UNSIGNED NOT NULL,
    or_id INT UNSIGNED NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
    is_recurring BOOLEAN DEFAULT FALSE,
    status ENUM('Active', 'Inactive', 'Cancelled') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (surgeon_id) REFERENCES surgeons(id) ON DELETE CASCADE,
    FOREIGN KEY (or_id) REFERENCES operating_rooms(id) ON DELETE CASCADE,
    INDEX idx_surgeon (surgeon_id),
    INDEX idx_or (or_id),
    INDEX idx_start_time (start_time),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: surgery_schedule
CREATE TABLE IF NOT EXISTS surgery_schedule (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50) NOT NULL,
    patient_name VARCHAR(200) NOT NULL,
    procedure_id INT UNSIGNED NOT NULL,
    surgeon_id INT UNSIGNED NOT NULL,
    anesthesiologist_id INT UNSIGNED,
    or_id INT UNSIGNED NOT NULL,
    block_id INT UNSIGNED,
    scheduled_date DATE NOT NULL,
    scheduled_start_time TIME NOT NULL,
    scheduled_end_time TIME NOT NULL,
    actual_start_time DATETIME,
    actual_end_time DATETIME,
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled', 'Postponed') DEFAULT 'Scheduled',
    priority ENUM('Routine', 'Urgent', 'Emergency') DEFAULT 'Routine',
    estimated_duration INT UNSIGNED DEFAULT 60 COMMENT 'Duration in minutes',
    notes TEXT,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (procedure_id) REFERENCES procedure_catalog(id) ON DELETE RESTRICT,
    FOREIGN KEY (surgeon_id) REFERENCES surgeons(id) ON DELETE RESTRICT,
    FOREIGN KEY (anesthesiologist_id) REFERENCES anesthesiologists(id) ON DELETE SET NULL,
    FOREIGN KEY (or_id) REFERENCES operating_rooms(id) ON DELETE RESTRICT,
    FOREIGN KEY (block_id) REFERENCES surgery_blocks(id) ON DELETE SET NULL,
    INDEX idx_patient (patient_id),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status),
    INDEX idx_surgeon (surgeon_id),
    INDEX idx_or (or_id),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: surgery_monitoring
CREATE TABLE IF NOT EXISTS surgery_monitoring (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    surgery_id INT UNSIGNED NOT NULL,
    progress_percentage INT UNSIGNED DEFAULT 0 COMMENT '0-100',
    vital_signs JSON,
    current_phase VARCHAR(100),
    complications TEXT,
    notes TEXT,
    monitored_by INT UNSIGNED,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (surgery_id) REFERENCES surgery_schedule(id) ON DELETE CASCADE,
    INDEX idx_surgery (surgery_id),
    INDEX idx_recorded_at (recorded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: surgery_rules
CREATE TABLE IF NOT EXISTS surgery_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(200) NOT NULL,
    rule_type ENUM('Lead Time', 'Emergency Slot', 'Privilege Level', 'Equipment Requirement', 'Other') NOT NULL,
    rule_description TEXT,
    minimum_lead_time_hours INT UNSIGNED DEFAULT 24 COMMENT 'Minimum hours before surgery',
    emergency_slot_count INT UNSIGNED DEFAULT 2,
    privilege_level_required VARCHAR(50),
    required_equipment TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rule_type (rule_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: surgery_approvals
CREATE TABLE IF NOT EXISTS surgery_approvals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    surgery_id INT UNSIGNED NOT NULL,
    approval_type ENUM('Department Head', 'Anesthesia', 'Administration', 'Other') NOT NULL,
    requested_by INT UNSIGNED,
    approved_by INT UNSIGNED,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    reason TEXT,
    rejection_reason TEXT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (surgery_id) REFERENCES surgery_schedule(id) ON DELETE CASCADE,
    INDEX idx_surgery (surgery_id),
    INDEX idx_status (status),
    INDEX idx_approval_type (approval_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: surgery_audit_log
CREATE TABLE IF NOT EXISTS surgery_audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    surgery_id INT UNSIGNED,
    action_type VARCHAR(100) NOT NULL,
    action_description TEXT,
    changed_by INT UNSIGNED,
    changed_from TEXT,
    changed_to TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_surgery (surgery_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at),
    INDEX idx_changed_by (changed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: surgery_reports
CREATE TABLE IF NOT EXISTS surgery_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_type ENUM('Utilization', 'Surgeon Productivity', 'Turnaround Time', 'Equipment Usage', 'Other') NOT NULL,
    report_date DATE NOT NULL,
    or_id INT UNSIGNED,
    surgeon_id INT UNSIGNED,
    metrics JSON,
    generated_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (or_id) REFERENCES operating_rooms(id) ON DELETE SET NULL,
    FOREIGN KEY (surgeon_id) REFERENCES surgeons(id) ON DELETE SET NULL,
    INDEX idx_report_type (report_type),
    INDEX idx_report_date (report_date),
    INDEX idx_or (or_id),
    INDEX idx_surgeon (surgeon_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: surgery_analytics
CREATE TABLE IF NOT EXISTS surgery_analytics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    analysis_date DATE NOT NULL,
    total_surgeries INT UNSIGNED DEFAULT 0,
    completed_surgeries INT UNSIGNED DEFAULT 0,
    cancelled_surgeries INT UNSIGNED DEFAULT 0,
    average_duration_minutes DECIMAL(10, 2) DEFAULT 0,
    or_utilization_rate DECIMAL(5, 2) DEFAULT 0 COMMENT 'Percentage',
    surgeon_productivity JSON,
    equipment_usage JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_analysis_date (analysis_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT SAMPLE DATA (Philippine Hospital Context)
-- Surgeons: PRC license format; contact style used in PH hospitals
-- ============================================

-- Insert Surgeons (Philippine doctors – PRC license numbers)
INSERT INTO surgeons (employee_id, first_name, last_name, specialization, privilege_level, license_number, phone, email, status) VALUES
('EMP-SURG-001', 'Maria', 'Santos', 'Cardiac Surgery', 'Level 4', 'PRC-MD-108234', '+63-2-8765-0101', 'msantos@hospital.ph', 'active'),
('EMP-SURG-002', 'Roberto', 'Cruz', 'Neurosurgery', 'Level 4', 'PRC-MD-109456', '+63-2-8765-0102', 'rcruz@hospital.ph', 'active'),
('EMP-SURG-003', 'Carmen', 'Reyes', 'Orthopedic Surgery', 'Level 3', 'PRC-MD-110782', '+63-2-8765-0103', 'creyes@hospital.ph', 'active'),
('EMP-SURG-004', 'Antonio', 'Garcia', 'General Surgery', 'Level 3', 'PRC-MD-111234', '+63-2-8765-0104', 'agarcia@hospital.ph', 'active'),
('EMP-SURG-005', 'Teresita', 'Ramos', 'Pediatric Surgery', 'Level 3', 'PRC-MD-112567', '+63-2-8765-0105', 'tramos@hospital.ph', 'active'),
('EMP-SURG-006', 'Fernando', 'Dela Cruz', 'Cardiac Surgery', 'Level 2', 'PRC-MD-113890', '+63-2-8765-0106', 'fdelacruz@hospital.ph', 'active'),
('EMP-SURG-007', 'Elena', 'Villanueva', 'General Surgery', 'Level 2', 'PRC-MD-114123', '+63-2-8765-0107', 'evillanueva@hospital.ph', 'active'),
('EMP-SURG-008', 'Jose', 'Mendoza', 'Neurosurgery', 'Level 3', 'PRC-MD-115456', '+63-2-8765-0108', 'jmendoza@hospital.ph', 'active')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), specialization = VALUES(specialization), privilege_level = VALUES(privilege_level), license_number = VALUES(license_number), phone = VALUES(phone), email = VALUES(email);

-- Ensure Philippine surgeon names (sync after any other seed that may have filled surgeons)
UPDATE surgeons SET first_name = 'Maria', last_name = 'Santos', specialization = 'Cardiac Surgery' WHERE employee_id = 'EMP-SURG-001';
UPDATE surgeons SET first_name = 'Roberto', last_name = 'Cruz', specialization = 'Neurosurgery' WHERE employee_id = 'EMP-SURG-002';
UPDATE surgeons SET first_name = 'Carmen', last_name = 'Reyes', specialization = 'Orthopedic Surgery' WHERE employee_id = 'EMP-SURG-003';
UPDATE surgeons SET first_name = 'Antonio', last_name = 'Garcia', specialization = 'General Surgery' WHERE employee_id = 'EMP-SURG-004';
UPDATE surgeons SET first_name = 'Teresita', last_name = 'Ramos', specialization = 'Pediatric Surgery' WHERE employee_id = 'EMP-SURG-005';
UPDATE surgeons SET first_name = 'Fernando', last_name = 'Dela Cruz', specialization = 'Cardiac Surgery' WHERE employee_id = 'EMP-SURG-006';
UPDATE surgeons SET first_name = 'Elena', last_name = 'Villanueva', specialization = 'General Surgery' WHERE employee_id = 'EMP-SURG-007';
UPDATE surgeons SET first_name = 'Jose', last_name = 'Mendoza', specialization = 'Neurosurgery' WHERE employee_id = 'EMP-SURG-008';

-- Insert Anesthesiologists (Philippine doctors)
INSERT INTO anesthesiologists (employee_id, first_name, last_name, specialization, license_number, phone, email, status) VALUES
('EMP-ANES-001', 'Ricardo', 'Bautista', 'Cardiac Anesthesia', 'PRC-MD-200101', '+63-2-8765-0201', 'rbautista@hospital.ph', 'active'),
('EMP-ANES-002', 'Lourdes', 'Castillo', 'Pediatric Anesthesia', 'PRC-MD-200202', '+63-2-8765-0202', 'lcastillo@hospital.ph', 'active'),
('EMP-ANES-003', 'Manuel', 'Aquino', 'General Anesthesia', 'PRC-MD-200303', '+63-2-8765-0203', 'maquino@hospital.ph', 'active'),
('EMP-ANES-004', 'Rosa', 'Fernandez', 'Neuro Anesthesia', 'PRC-MD-200404', '+63-2-8765-0204', 'rfernandez@hospital.ph', 'active'),
('EMP-ANES-005', 'Pedro', 'Gonzalez', 'Cardiac Anesthesia', 'PRC-MD-200505', '+63-2-8765-0205', 'pgonzalez@hospital.ph', 'active'),
('EMP-ANES-006', 'Socorro', 'Lopez', 'General Anesthesia', 'PRC-MD-200606', '+63-2-8765-0206', 'slopez@hospital.ph', 'active')
ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name), license_number = VALUES(license_number), phone = VALUES(phone), email = VALUES(email);

-- Insert Procedure Catalog (common procedures in Philippine hospitals)
INSERT INTO procedure_catalog (procedure_code, procedure_name, category, specialty, estimated_duration, default_equipment, requirements) VALUES
('CABG-001', 'Coronary Artery Bypass Graft (CABG)', 'Cardiac', 'Cardiac Surgery', 240, 'Heart-Lung Machine, Defibrillator, Monitoring', 'Level 4 Privilege, Cardiac OR'),
('APP-001', 'Appendectomy', 'General', 'General Surgery', 60, 'Laparoscope, Standard OR Equipment', 'Level 2 Privilege'),
('CHOL-001', 'Cholecystectomy (Laparoscopic)', 'General', 'General Surgery', 90, 'Laparoscope, Standard OR Equipment', 'Level 2 Privilege'),
('CRAN-001', 'Craniotomy', 'Neurosurgery', 'Neurosurgery', 180, 'Neurosurgical Microscope, Navigation System', 'Level 4 Privilege, Neuro OR'),
('HIP-001', 'Total Hip Replacement (THR)', 'Orthopedic', 'Orthopedic Surgery', 120, 'Orthopedic Implants, C-Arm', 'Level 3 Privilege'),
('KNEE-001', 'Total Knee Replacement (TKR)', 'Orthopedic', 'Orthopedic Surgery', 105, 'Orthopedic Implants, C-Arm', 'Level 3 Privilege'),
('SPINE-001', 'Spinal Fusion', 'Neurosurgery', 'Neurosurgery', 210, 'Neurosurgical Microscope, Spinal Implants', 'Level 3 Privilege, Neuro OR'),
('PED-001', 'Pediatric Appendectomy', 'Pediatric', 'Pediatric Surgery', 75, 'Pediatric Laparoscope, Standard OR', 'Level 3 Privilege, Pediatric OR'),
('CATH-001', 'Cardiac Catheterization', 'Cardiac', 'Cardiac Surgery', 90, 'Cath Lab Equipment, Fluoroscopy', 'Level 3 Privilege, Cardiac OR'),
('HERN-001', 'Hernia Repair (Open/Laparoscopic)', 'General', 'General Surgery', 60, 'Laparoscope, Standard OR Equipment', 'Level 2 Privilege'),
('CS-001', 'Cesarean Section', 'Ob-Gyn', 'General Surgery', 60, 'Standard OR, Fetal Monitor', 'Level 2 Privilege'),
('THY-001', 'Thyroidectomy', 'General', 'General Surgery', 90, 'Standard OR, Nerve Monitor', 'Level 2 Privilege')
ON DUPLICATE KEY UPDATE procedure_name = VALUES(procedure_name), category = VALUES(category), estimated_duration = VALUES(estimated_duration), default_equipment = VALUES(default_equipment), requirements = VALUES(requirements);

-- Insert Operating Rooms (Philippine hospital layout)
INSERT INTO operating_rooms (room_number, room_type, status, capacity, floor_number, location, notes) VALUES
('OR-01', 'General', 'Ready', 1, 3, '3rd Floor - Main Building, East Wing', 'General surgery'),
('OR-02', 'General', 'Ready', 1, 3, '3rd Floor - Main Building, East Wing', 'General surgery'),
('OR-03', 'Cardiac', 'Ready', 1, 4, '4th Floor - Main Building, Cardiac Suite', 'Heart-lung machine'),
('OR-04', 'Cardiac', 'In Use', 1, 4, '4th Floor - Main Building, Cardiac Suite', 'Heart-lung machine'),
('OR-05', 'Neuro', 'Ready', 1, 4, '4th Floor - Main Building, Neuro Suite', 'Neurosurgical microscope'),
('OR-06', 'Neuro', 'Cleaning', 1, 4, '4th Floor - Main Building, Neuro Suite', 'Neurosurgical microscope'),
('OR-07', 'Orthopedic', 'Ready', 1, 3, '3rd Floor - Main Building, West Wing', 'C-Arm'),
('OR-08', 'Pediatric', 'Ready', 1, 2, '2nd Floor - Pediatric Wing', 'Pediatric equipment'),
('OR-09', 'General', 'Maintenance', 1, 3, '3rd Floor - Main Building, East Wing', 'Scheduled maintenance'),
('OR-10', 'Emergency', 'Ready', 1, 1, '1st Floor - ER Complex', '24/7 emergency OR')
ON DUPLICATE KEY UPDATE location = VALUES(location), notes = VALUES(notes), status = VALUES(status);

-- Insert OR Equipment
INSERT INTO or_equipment (or_id, equipment_name, equipment_type, serial_number, status, last_maintenance_date, next_maintenance_date, notes) VALUES
((SELECT id FROM operating_rooms WHERE room_number = 'OR-01' LIMIT 1), 'Anesthesia Machine', 'Anesthesia', 'ANES-OR01-001', 'Available', '2024-01-15', '2024-07-15', 'Regular maintenance'),
((SELECT id FROM operating_rooms WHERE room_number = 'OR-01' LIMIT 1), 'Surgical Lights', 'Lighting', 'LIGHT-OR01-001', 'Available', '2024-02-01', '2024-08-01', NULL),
((SELECT id FROM operating_rooms WHERE room_number = 'OR-01' LIMIT 1), 'Laparoscope System', 'Endoscopy', 'LAP-OR01-001', 'Available', '2024-01-20', '2024-07-20', NULL),
((SELECT id FROM operating_rooms WHERE room_number = 'OR-03' LIMIT 1), 'Heart-Lung Machine', 'Cardiac', 'HLM-OR03-001', 'Available', '2024-01-10', '2024-07-10', 'Critical equipment'),
((SELECT id FROM operating_rooms WHERE room_number = 'OR-03' LIMIT 1), 'Defibrillator', 'Cardiac', 'DEF-OR03-001', 'Available', '2024-02-05', '2024-08-05', NULL),
((SELECT id FROM operating_rooms WHERE room_number = 'OR-05' LIMIT 1), 'Neurosurgical Microscope', 'Neurosurgery', 'NSM-OR05-001', 'Available', '2024-01-25', '2024-07-25', 'High-precision equipment'),
((SELECT id FROM operating_rooms WHERE room_number = 'OR-05' LIMIT 1), 'Navigation System', 'Neurosurgery', 'NAV-OR05-001', 'Available', '2024-02-10', '2024-08-10', NULL),
((SELECT id FROM operating_rooms WHERE room_number = 'OR-07' LIMIT 1), 'C-Arm Fluoroscopy', 'Imaging', 'CARM-OR07-001', 'In Use', '2024-01-18', '2024-07-18', NULL),
((SELECT id FROM operating_rooms WHERE room_number = 'OR-08' LIMIT 1), 'Pediatric Anesthesia Machine', 'Anesthesia', 'PED-ANES-001', 'Available', '2024-01-12', '2024-07-12', 'Pediatric-specific'),
((SELECT id FROM operating_rooms WHERE room_number = 'OR-10' LIMIT 1), 'Emergency Crash Cart', 'Emergency', 'CRASH-OR10-001', 'Available', '2024-02-01', '2024-08-01', '24/7 availability')
ON DUPLICATE KEY UPDATE equipment_name = equipment_name;

-- Insert Surgery Blocks (surgeon block time – Philippine practice)
INSERT INTO surgery_blocks (block_name, surgeon_id, or_id, start_time, end_time, day_of_week, is_recurring, status) VALUES
('Dr. Santos Cardiac Block', (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-001' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-03' LIMIT 1), '2024-12-16 08:00:00', '2024-12-16 12:00:00', 'Monday', TRUE, 'Active'),
('Dr. Cruz Neuro Block', (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-002' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-05' LIMIT 1), '2024-12-16 08:00:00', '2024-12-16 14:00:00', 'Monday', TRUE, 'Active'),
('Dr. Reyes Ortho Block', (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-003' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-07' LIMIT 1), '2024-12-16 09:00:00', '2024-12-16 15:00:00', 'Monday', TRUE, 'Active'),
('Dr. Garcia General Block', (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-004' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-01' LIMIT 1), '2024-12-16 08:00:00', '2024-12-16 13:00:00', 'Monday', TRUE, 'Active')
ON DUPLICATE KEY UPDATE block_name = VALUES(block_name);

-- Insert Surgery Schedule (Philippine patient names; surgeons/anesthesiologists from seed above)
INSERT INTO surgery_schedule (patient_id, patient_name, procedure_id, surgeon_id, anesthesiologist_id, or_id, block_id, scheduled_date, scheduled_start_time, scheduled_end_time, status, priority, estimated_duration, notes) VALUES
('P-2024-001', 'Juan Dela Cruz', (SELECT id FROM procedure_catalog WHERE procedure_code = 'CABG-001' LIMIT 1), (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-001' LIMIT 1), (SELECT id FROM anesthesiologists WHERE employee_id = 'EMP-ANES-001' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-03' LIMIT 1), NULL, '2024-12-18', '08:00:00', '12:00:00', 'Scheduled', 'Routine', 240, 'Pre-op clearance done'),
('P-2024-002', 'Maria Clara Reyes', (SELECT id FROM procedure_catalog WHERE procedure_code = 'APP-001' LIMIT 1), (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-004' LIMIT 1), (SELECT id FROM anesthesiologists WHERE employee_id = 'EMP-ANES-003' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-01' LIMIT 1), NULL, '2024-12-16', '09:00:00', '10:00:00', 'In Progress', 'Urgent', 60, 'Emergency case'),
('P-2024-003', 'Roberto Santiago', (SELECT id FROM procedure_catalog WHERE procedure_code = 'CRAN-001' LIMIT 1), (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-002' LIMIT 1), (SELECT id FROM anesthesiologists WHERE employee_id = 'EMP-ANES-004' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-05' LIMIT 1), NULL, '2024-12-16', '10:00:00', '13:00:00', 'Scheduled', 'Routine', 180, NULL),
('P-2024-004', 'Lourdes Medina', (SELECT id FROM procedure_catalog WHERE procedure_code = 'HIP-001' LIMIT 1), (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-003' LIMIT 1), (SELECT id FROM anesthesiologists WHERE employee_id = 'EMP-ANES-003' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-07' LIMIT 1), NULL, '2024-12-17', '08:00:00', '10:00:00', 'Scheduled', 'Routine', 120, NULL),
('P-2024-005', 'Pedro Bautista', (SELECT id FROM procedure_catalog WHERE procedure_code = 'CHOL-001' LIMIT 1), (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-007' LIMIT 1), (SELECT id FROM anesthesiologists WHERE employee_id = 'EMP-ANES-006' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-02' LIMIT 1), NULL, '2024-12-16', '14:00:00', '15:30:00', 'Scheduled', 'Routine', 90, NULL),
('P-2024-006', 'Ana Patricia Gomez', (SELECT id FROM procedure_catalog WHERE procedure_code = 'KNEE-001' LIMIT 1), (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-003' LIMIT 1), (SELECT id FROM anesthesiologists WHERE employee_id = 'EMP-ANES-003' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-07' LIMIT 1), NULL, '2024-12-17', '10:30:00', '12:15:00', 'Scheduled', 'Routine', 105, NULL),
('P-2024-007', 'Miguel Torres', (SELECT id FROM procedure_catalog WHERE procedure_code = 'PED-001' LIMIT 1), (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-005' LIMIT 1), (SELECT id FROM anesthesiologists WHERE employee_id = 'EMP-ANES-002' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-08' LIMIT 1), NULL, '2024-12-18', '09:00:00', '10:15:00', 'Scheduled', 'Routine', 75, 'Pediatric case'),
('P-2024-008', 'Elena Vasquez', (SELECT id FROM procedure_catalog WHERE procedure_code = 'SPINE-001' LIMIT 1), (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-008' LIMIT 1), (SELECT id FROM anesthesiologists WHERE employee_id = 'EMP-ANES-004' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-05' LIMIT 1), NULL, '2024-12-19', '08:00:00', '11:30:00', 'Scheduled', 'Routine', 210, NULL),
('P-2024-009', 'Carlos Andres Lim', (SELECT id FROM procedure_catalog WHERE procedure_code = 'HERN-001' LIMIT 1), (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-004' LIMIT 1), (SELECT id FROM anesthesiologists WHERE employee_id = 'EMP-ANES-006' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-01' LIMIT 1), NULL, '2024-12-16', '11:00:00', '12:00:00', 'Scheduled', 'Routine', 60, NULL),
('P-2024-010', 'Corazon Dimaculangan', (SELECT id FROM procedure_catalog WHERE procedure_code = 'CATH-001' LIMIT 1), (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-001' LIMIT 1), (SELECT id FROM anesthesiologists WHERE employee_id = 'EMP-ANES-001' LIMIT 1), (SELECT id FROM operating_rooms WHERE room_number = 'OR-03' LIMIT 1), NULL, '2024-12-17', '13:00:00', '14:30:00', 'Scheduled', 'Routine', 90, NULL)
ON DUPLICATE KEY UPDATE patient_name = VALUES(patient_name), notes = VALUES(notes);

-- Insert Surgery Monitoring (for in-progress surgery)
INSERT INTO surgery_monitoring (surgery_id, progress_percentage, vital_signs, current_phase, complications, notes, monitored_by) VALUES
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-002' AND status = 'In Progress' LIMIT 1), 45, '{"bp": "120/80", "heart_rate": 72, "oxygen_sat": 98, "temperature": 36.5}', 'Procedure in Progress', NULL, 'Patient stable, procedure proceeding normally', 1),
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-002' AND status = 'In Progress' LIMIT 1), 60, '{"bp": "118/78", "heart_rate": 70, "oxygen_sat": 99, "temperature": 36.6}', 'Procedure in Progress', NULL, 'Continuing smoothly', 1)
ON DUPLICATE KEY UPDATE progress_percentage = progress_percentage;

-- Insert Surgery Rules (scheduling and clearance rules – Philippine hospital practice)
INSERT INTO surgery_rules (rule_name, rule_type, rule_description, minimum_lead_time_hours, emergency_slot_count, privilege_level_required, required_equipment, is_active) VALUES
('Minimum lead time for elective surgery', 'Lead Time', 'Elective cases must be booked at least 24 hours before scheduled time', 24, NULL, NULL, NULL, TRUE),
('Emergency OR slot reservation', 'Emergency Slot', 'Each OR maintains at least 2 slots per day for emergency cases', NULL, 2, NULL, NULL, TRUE),
('Cardiac surgery privilege', 'Privilege Level', 'Cardiac procedures require surgeon with Level 4 privilege', NULL, NULL, 'Level 4', NULL, TRUE),
('Neurosurgery equipment', 'Equipment Requirement', 'Neurosurgery requires neurosurgical microscope and navigation system', NULL, NULL, NULL, 'Neurosurgical Microscope, Navigation System', TRUE),
('General surgery privilege', 'Privilege Level', 'General surgery requires at least Level 2 privilege', NULL, NULL, 'Level 2', NULL, TRUE),
('Cardiac OR equipment', 'Equipment Requirement', 'Cardiac OR must have heart-lung machine and defibrillator', NULL, NULL, NULL, 'Heart-Lung Machine, Defibrillator', TRUE)
ON DUPLICATE KEY UPDATE rule_description = VALUES(rule_description), minimum_lead_time_hours = VALUES(minimum_lead_time_hours), emergency_slot_count = VALUES(emergency_slot_count), privilege_level_required = VALUES(privilege_level_required), required_equipment = VALUES(required_equipment);

-- Insert Surgery Approvals (clearance workflow: Department Head, Anesthesia, Administration)
INSERT INTO surgery_approvals (surgery_id, approval_type, requested_by, status, reason) VALUES
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-001' LIMIT 1), 'Department Head', 1, 'Pending', 'Cardiac surgery – department head clearance required'),
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-001' LIMIT 1), 'Anesthesia', 1, 'Pending', 'Cardiac case – anesthesia clearance required'),
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-003' LIMIT 1), 'Department Head', 1, 'Approved', 'Neurosurgery case – approved'),
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-003' LIMIT 1), 'Anesthesia', 1, 'Approved', 'Anesthesia clearance granted'),
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-008' LIMIT 1), 'Department Head', 1, 'Pending', 'Spinal fusion – department head clearance')
ON DUPLICATE KEY UPDATE reason = VALUES(reason);

-- Insert Surgery Audit Log
INSERT INTO surgery_audit_log (surgery_id, action_type, action_description, changed_by, changed_from, changed_to, ip_address) VALUES
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-002' LIMIT 1), 'Status Change', 'Surgery status changed from Scheduled to In Progress', 1, 'Scheduled', 'In Progress', '192.168.1.100'),
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-001' LIMIT 1), 'Schedule Created', 'New surgery scheduled for patient P-2024-001', 1, NULL, 'Scheduled for 2024-12-18 08:00', '192.168.1.101'),
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-003' LIMIT 1), 'Schedule Created', 'New surgery scheduled for patient P-2024-003', 1, NULL, 'Scheduled for 2024-12-16 10:00', '192.168.1.102'),
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-004' LIMIT 1), 'Schedule Created', 'New surgery scheduled for patient P-2024-004', 1, NULL, 'Scheduled for 2024-12-17 08:00', '192.168.1.103'),
((SELECT id FROM surgery_schedule WHERE patient_id = 'P-2024-005' LIMIT 1), 'Schedule Created', 'New surgery scheduled for patient P-2024-005', 1, NULL, 'Scheduled for 2024-12-16 14:00', '192.168.1.104')
ON DUPLICATE KEY UPDATE action_type = action_type;

-- Insert Surgery Reports (OR utilization, surgeon productivity, turnaround – for reporting page)
INSERT INTO surgery_reports (report_type, report_date, or_id, surgeon_id, metrics, generated_by) VALUES
('Utilization', CURDATE(), (SELECT id FROM operating_rooms WHERE room_number = 'OR-01' LIMIT 1), NULL, '{"utilization_rate": 75.5, "total_hours": 6.5, "available_hours": 8.0}', 1),
('Surgeon Productivity', CURDATE(), NULL, (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-001' LIMIT 1), '{"surgeries_completed": 3, "average_duration": 195, "total_hours": 9.75}', 1),
('Turnaround Time', CURDATE(), (SELECT id FROM operating_rooms WHERE room_number = 'OR-01' LIMIT 1), NULL, '{"average_turnaround": 45, "min_turnaround": 30, "max_turnaround": 60}', 1),
('Utilization', CURDATE() - INTERVAL 1 DAY, (SELECT id FROM operating_rooms WHERE room_number = 'OR-03' LIMIT 1), NULL, '{"utilization_rate": 82.3, "total_hours": 7.2, "available_hours": 8.0}', 1),
('Surgeon Productivity', CURDATE() - INTERVAL 1 DAY, NULL, (SELECT id FROM surgeons WHERE employee_id = 'EMP-SURG-002' LIMIT 1), '{"surgeries_completed": 2, "average_duration": 180, "total_hours": 6.0}', 1)
ON DUPLICATE KEY UPDATE metrics = VALUES(metrics);

-- Insert Surgery Analytics (for OR Utilization & Reports page)
INSERT INTO surgery_analytics (analysis_date, total_surgeries, completed_surgeries, cancelled_surgeries, average_duration_minutes, or_utilization_rate, surgeon_productivity, equipment_usage) VALUES
(CURDATE(), 8, 6, 0, 125.5, 78.5, '{"top_surgeon": "Dr. Maria Santos", "surgeries_per_surgeon": 1.5}', '{"most_used": "Laparoscope System", "usage_count": 4}'),
(CURDATE() - INTERVAL 1 DAY, 10, 9, 1, 142.3, 85.2, '{"top_surgeon": "Dr. Roberto Cruz", "surgeries_per_surgeon": 1.8}', '{"most_used": "Anesthesia Machine", "usage_count": 10}'),
(CURDATE() - INTERVAL 2 DAY, 7, 7, 0, 118.7, 72.1, '{"top_surgeon": "Dr. Carmen Reyes", "surgeries_per_surgeon": 1.4}', '{"most_used": "C-Arm Fluoroscopy", "usage_count": 3}')
ON DUPLICATE KEY UPDATE surgeon_productivity = VALUES(surgeon_productivity), equipment_usage = VALUES(equipment_usage);
