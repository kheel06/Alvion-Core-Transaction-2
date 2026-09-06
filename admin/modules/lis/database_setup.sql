-- Laboratory Information System (LIS) Database Tables
-- Run this SQL script to create all required tables for the LIS module

-- Table: test_catalog
CREATE TABLE IF NOT EXISTS test_catalog (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_code VARCHAR(50) NOT NULL UNIQUE,
    test_name VARCHAR(150) NOT NULL,
    department VARCHAR(100),
    specimen_type VARCHAR(100),
    price DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department),
    INDEX idx_status (status),
    INDEX idx_test_code (test_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: lab_orders
CREATE TABLE IF NOT EXISTS lab_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50) NOT NULL,
    test_name VARCHAR(150) NOT NULL,
    order_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('received', 'processing', 'verification', 'completed') DEFAULT 'received',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_patient (patient_id),
    INDEX idx_order_time (order_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: validation_rules
CREATE TABLE IF NOT EXISTS validation_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_id INT UNSIGNED NOT NULL,
    auto_validate TINYINT(1) DEFAULT 0,
    critical_low DECIMAL(10, 2),
    critical_high DECIMAL(10, 2),
    validator_user_id INT UNSIGNED,
    created_by INT UNSIGNED,
    updated_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES test_catalog(id) ON DELETE CASCADE,
    INDEX idx_test (test_id),
    INDEX idx_validator (validator_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: reference_ranges
CREATE TABLE IF NOT EXISTS reference_ranges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_id INT UNSIGNED NOT NULL,
    age_group ENUM('pediatric', 'adult', 'geriatric', 'all') NOT NULL,
    gender ENUM('male', 'female', 'all') NOT NULL,
    unit_of_measure VARCHAR(50),
    normal_low DECIMAL(10, 2),
    normal_high DECIMAL(10, 2),
    critical_low DECIMAL(10, 2),
    critical_high DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES test_catalog(id) ON DELETE CASCADE,
    UNIQUE KEY unique_test_demographic (test_id, age_group, gender),
    INDEX idx_test (test_id),
    INDEX idx_age_group (age_group),
    INDEX idx_gender (gender)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: performance_metrics
CREATE TABLE IF NOT EXISTS performance_metrics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    department VARCHAR(100),
    total_tests INT UNSIGNED DEFAULT 0,
    tat_compliance_percent DECIMAL(5, 2) DEFAULT 0.00,
    rejection_rate DECIMAL(5, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_report_date (report_date),
    INDEX idx_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample test catalog data
INSERT INTO test_catalog (test_code, test_name, department, specimen_type, price, status) VALUES
('CBC-001', 'Complete Blood Count (CBC)', 'Hematology', 'Blood', 250.00, 'active'),
('HEMO-001', 'Hemoglobin', 'Hematology', 'Blood', 150.00, 'active'),
('GLUC-001', 'Blood Glucose (Fasting)', 'Chemistry', 'Blood', 200.00, 'active'),
('CREAT-001', 'Creatinine', 'Chemistry', 'Blood', 180.00, 'active'),
('ALT-001', 'ALT (SGPT)', 'Chemistry', 'Blood', 220.00, 'active'),
('CULT-001', 'Blood Culture', 'Microbiology', 'Blood', 500.00, 'active'),
('URCULT-001', 'Urine Culture', 'Microbiology', 'Urine', 400.00, 'active')
ON DUPLICATE KEY UPDATE test_name = test_name;

-- Insert sample lab orders
INSERT INTO lab_orders (patient_id, test_name, order_time, priority, status) VALUES
('P001', 'Complete Blood Count (CBC)', NOW() - INTERVAL 2 HOUR, 'medium', 'received'),
('P002', 'Blood Glucose (Fasting)', NOW() - INTERVAL 1 HOUR, 'high', 'processing'),
('P003', 'Creatinine', NOW() - INTERVAL 30 MINUTE, 'medium', 'verification'),
('P004', 'Hemoglobin', NOW() - INTERVAL 15 MINUTE, 'low', 'completed'),
('P005', 'ALT (SGPT)', NOW(), 'high', 'received')
ON DUPLICATE KEY UPDATE test_name = test_name;

-- Insert sample performance metrics
INSERT INTO performance_metrics (report_date, department, total_tests, tat_compliance_percent, rejection_rate) VALUES
(CURDATE(), 'Hematology', 45, 95.5, 2.5),
(CURDATE(), 'Chemistry', 78, 92.3, 3.1),
(CURDATE(), 'Microbiology', 23, 88.7, 4.2),
(CURDATE() - INTERVAL 1 DAY, 'Hematology', 42, 94.8, 2.8),
(CURDATE() - INTERVAL 1 DAY, 'Chemistry', 75, 91.5, 3.5),
(CURDATE() - INTERVAL 2 DAY, 'Hematology', 48, 96.2, 2.1),
(CURDATE() - INTERVAL 2 DAY, 'Chemistry', 82, 93.1, 2.9)
ON DUPLICATE KEY UPDATE total_tests = total_tests;
