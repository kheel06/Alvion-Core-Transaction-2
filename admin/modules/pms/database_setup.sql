-- Pharmacy Management System (PMS) Database Tables
-- Run this SQL script to create all required tables for the PMS module

-- Table: medicine_master
CREATE TABLE IF NOT EXISTS medicine_master (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    drug_name VARCHAR(150) NOT NULL,
    generic_name VARCHAR(150),
    manufacturer VARCHAR(150),
    dosage_form VARCHAR(50),
    strength VARCHAR(50),
    therapeutic_class VARCHAR(100),
    price DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_drug_name (drug_name),
    INDEX idx_therapeutic_class (therapeutic_class),
    INDEX idx_dosage_form (dosage_form)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: inventory_stock
CREATE TABLE IF NOT EXISTS inventory_stock (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT UNSIGNED NOT NULL,
    current_stock INT UNSIGNED DEFAULT 0,
    minimum_stock_level INT UNSIGNED DEFAULT 0,
    reorder_level INT UNSIGNED DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicine_master(id) ON DELETE CASCADE,
    INDEX idx_medicine (medicine_id),
    INDEX idx_stock_level (current_stock)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: batch_expiry
CREATE TABLE IF NOT EXISTS batch_expiry (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT UNSIGNED NOT NULL,
    batch_number VARCHAR(100) NOT NULL,
    quantity INT UNSIGNED DEFAULT 0,
    expiry_date DATE NOT NULL,
    status ENUM('active', 'marked_for_return', 'returned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicine_master(id) ON DELETE CASCADE,
    INDEX idx_medicine (medicine_id),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_batch_number (batch_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: prescriptions
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50) NOT NULL,
    doctor VARCHAR(150),
    status ENUM('new', 'in_process', 'ready', 'dispensed') DEFAULT 'new',
    barcode VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    verified_by INT UNSIGNED,
    verified_at TIMESTAMP NULL,
    dispensed_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_patient (patient_id),
    INDEX idx_barcode (barcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: prescription_items
CREATE TABLE IF NOT EXISTS prescription_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT UNSIGNED NOT NULL,
    medicine_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED DEFAULT 1,
    instructions TEXT,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicine_master(id) ON DELETE CASCADE,
    INDEX idx_prescription (prescription_id),
    INDEX idx_medicine (medicine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: pharmacy_reports_data
CREATE TABLE IF NOT EXISTS pharmacy_reports_data (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    drug_name VARCHAR(150),
    therapeutic_class VARCHAR(100),
    quantity_sold INT UNSIGNED DEFAULT 0,
    revenue DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_date (report_date),
    INDEX idx_drug_name (drug_name),
    INDEX idx_therapeutic_class (therapeutic_class)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample medicine master data (30 medicines)
INSERT INTO medicine_master (drug_name, generic_name, manufacturer, dosage_form, strength, therapeutic_class, price) VALUES
('Amoxicillin 500mg', 'Amoxicillin', 'Unilab', 'Capsule', '500mg', 'Antibiotic', 25.50),
('Paracetamol 500mg', 'Paracetamol', 'GlaxoSmithKline', 'Tablet', '500mg', 'Analgesic', 5.75),
('Losartan 50mg', 'Losartan', 'Pfizer', 'Tablet', '50mg', 'Antihypertensive', 18.25),
('Metformin 500mg', 'Metformin', 'Merck', 'Tablet', '500mg', 'Antidiabetic', 12.00),
('Omeprazole 20mg', 'Omeprazole', 'AstraZeneca', 'Capsule', '20mg', 'Antacid', 15.50),
('Salbutamol Inhaler', 'Salbutamol', 'GSK', 'Inhaler', '100mcg', 'Bronchodilator', 180.00),
('Insulin Glargine', 'Insulin Glargine', 'Sanofi', 'Injection', '100 IU/ml', 'Antidiabetic', 450.00),
('Aspirin 100mg', 'Aspirin', 'Bayer', 'Tablet', '100mg', 'Antiplatelet', 3.50),
('Atorvastatin 20mg', 'Atorvastatin', 'Pfizer', 'Tablet', '20mg', 'Antilipemic', 22.75),
('Ciprofloxacin 500mg', 'Ciprofloxacin', 'Bayer', 'Tablet', '500mg', 'Antibiotic', 35.00),
('Amlodipine 5mg', 'Amlodipine', 'Pfizer', 'Tablet', '5mg', 'Antihypertensive', 8.50),
('Levothyroxine 50mcg', 'Levothyroxine', 'Abbott', 'Tablet', '50mcg', 'Hormone', 12.75),
('Warfarin 5mg', 'Warfarin', 'Bristol-Myers Squibb', 'Tablet', '5mg', 'Anticoagulant', 15.00),
('Furosemide 40mg', 'Furosemide', 'Sanofi', 'Tablet', '40mg', 'Diuretic', 6.25),
('Pantoprazole 40mg', 'Pantoprazole', 'Takeda', 'Tablet', '40mg', 'Antacid', 18.50),
('Montelukast 10mg', 'Montelukast', 'Merck', 'Tablet', '10mg', 'Antiasthmatic', 14.00),
('Cetirizine 10mg', 'Cetirizine', 'UCB', 'Tablet', '10mg', 'Antihistamine', 4.50),
('Diazepam 5mg', 'Diazepam', 'Roche', 'Tablet', '5mg', 'Anxiolytic', 9.75),
('Ibuprofen 400mg', 'Ibuprofen', 'Pfizer', 'Tablet', '400mg', 'NSAID', 7.25),
('Ranitidine 150mg', 'Ranitidine', 'GSK', 'Tablet', '150mg', 'Antacid', 5.00),
('Lisinopril 10mg', 'Lisinopril', 'AstraZeneca', 'Tablet', '10mg', 'ACE Inhibitor', 10.50),
('Simvastatin 20mg', 'Simvastatin', 'Merck', 'Tablet', '20mg', 'Antilipemic', 11.25),
('Atenolol 50mg', 'Atenolol', 'AstraZeneca', 'Tablet', '50mg', 'Beta Blocker', 7.75),
('Hydrochlorothiazide 25mg', 'Hydrochlorothiazide', 'Merck', 'Tablet', '25mg', 'Diuretic', 5.50),
('Clopidogrel 75mg', 'Clopidogrel', 'Sanofi', 'Tablet', '75mg', 'Antiplatelet', 28.00),
('Gliclazide 80mg', 'Gliclazide', 'Servier', 'Tablet', '80mg', 'Antidiabetic', 13.50),
('Metronidazole 500mg', 'Metronidazole', 'Pfizer', 'Tablet', '500mg', 'Antibiotic', 16.75),
('Doxycycline 100mg', 'Doxycycline', 'Pfizer', 'Capsule', '100mg', 'Antibiotic', 22.50),
('Fluoxetine 20mg', 'Fluoxetine', 'Eli Lilly', 'Capsule', '20mg', 'Antidepressant', 19.25),
('Tramadol 50mg', 'Tramadol', 'Grünenthal', 'Capsule', '50mg', 'Analgesic', 24.00)
ON DUPLICATE KEY UPDATE drug_name = drug_name;

-- Insert sample inventory stock (reorder_level 250-400)
-- Critical Stock (Below Minimum) - RED ALERT
INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated) VALUES
((SELECT id FROM medicine_master WHERE drug_name = 'Insulin Glargine' LIMIT 1), 8, 50, 280, NOW() - INTERVAL 2 DAY),
((SELECT id FROM medicine_master WHERE drug_name = 'Salbutamol Inhaler' LIMIT 1), 12, 50, 300, NOW() - INTERVAL 1 DAY),
((SELECT id FROM medicine_master WHERE drug_name = 'Warfarin 5mg' LIMIT 1), 25, 50, 320, NOW() - INTERVAL 3 DAY),
((SELECT id FROM medicine_master WHERE drug_name = 'Diazepam 5mg' LIMIT 1), 18, 50, 290, NOW() - INTERVAL 1 DAY),
((SELECT id FROM medicine_master WHERE drug_name = 'Clopidogrel 75mg' LIMIT 1), 35, 50, 350, NOW() - INTERVAL 2 DAY)
ON DUPLICATE KEY UPDATE 
    current_stock = VALUES(current_stock),
    minimum_stock_level = VALUES(minimum_stock_level),
    reorder_level = VALUES(reorder_level),
    last_updated = VALUES(last_updated);

-- Low Stock (Below Reorder Level) - AMBER ALERT
INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated) VALUES
((SELECT id FROM medicine_master WHERE drug_name = 'Losartan 50mg' LIMIT 1), 35, 50, 260, NOW() - INTERVAL 1 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Omeprazole 20mg' LIMIT 1), 55, 50, 275, NOW() - INTERVAL 5 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Atorvastatin 20mg' LIMIT 1), 42, 50, 310, NOW() - INTERVAL 3 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Ciprofloxacin 500mg' LIMIT 1), 22, 50, 330, NOW() - INTERVAL 2 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Pantoprazole 40mg' LIMIT 1), 60, 50, 340, NOW() - INTERVAL 4 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Montelukast 10mg' LIMIT 1), 48, 50, 290, NOW() - INTERVAL 6 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Gliclazide 80mg' LIMIT 1), 55, 50, 360, NOW() - INTERVAL 1 DAY),
((SELECT id FROM medicine_master WHERE drug_name = 'Metronidazole 500mg' LIMIT 1), 38, 50, 380, NOW() - INTERVAL 2 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Doxycycline 100mg' LIMIT 1), 28, 50, 270, NOW() - INTERVAL 3 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Tramadol 50mg' LIMIT 1), 45, 50, 400, NOW() - INTERVAL 1 HOUR)
ON DUPLICATE KEY UPDATE 
    current_stock = VALUES(current_stock),
    minimum_stock_level = VALUES(minimum_stock_level),
    reorder_level = VALUES(reorder_level),
    last_updated = VALUES(last_updated);

-- Normal Stock (Above Reorder Level) - GREEN
INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated) VALUES
((SELECT id FROM medicine_master WHERE drug_name = 'Amoxicillin 500mg' LIMIT 1), 450, 100, 280, NOW() - INTERVAL 1 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Paracetamol 500mg' LIMIT 1), 500, 100, 300, NOW() - INTERVAL 30 MINUTE),
((SELECT id FROM medicine_master WHERE drug_name = 'Metformin 500mg' LIMIT 1), 420, 100, 320, NOW() - INTERVAL 2 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Aspirin 100mg' LIMIT 1), 1000, 100, 350, NOW() - INTERVAL 1 DAY),
((SELECT id FROM medicine_master WHERE drug_name = 'Amlodipine 5mg' LIMIT 1), 380, 100, 290, NOW() - INTERVAL 3 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Levothyroxine 50mcg' LIMIT 1), 420, 100, 310, NOW() - INTERVAL 4 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Furosemide 40mg' LIMIT 1), 520, 100, 330, NOW() - INTERVAL 2 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Cetirizine 10mg' LIMIT 1), 550, 100, 340, NOW() - INTERVAL 1 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Ibuprofen 400mg' LIMIT 1), 480, 100, 360, NOW() - INTERVAL 5 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Ranitidine 150mg' LIMIT 1), 480, 100, 370, NOW() - INTERVAL 2 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Lisinopril 10mg' LIMIT 1), 395, 100, 250, NOW() - INTERVAL 3 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Simvastatin 20mg' LIMIT 1), 440, 100, 380, NOW() - INTERVAL 4 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Atenolol 50mg' LIMIT 1), 375, 100, 265, NOW() - INTERVAL 2 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Hydrochlorothiazide 25mg' LIMIT 1), 490, 100, 390, NOW() - INTERVAL 1 HOUR),
((SELECT id FROM medicine_master WHERE drug_name = 'Fluoxetine 20mg' LIMIT 1), 365, 80, 255, NOW() - INTERVAL 6 HOUR)
ON DUPLICATE KEY UPDATE 
    current_stock = VALUES(current_stock),
    minimum_stock_level = VALUES(minimum_stock_level),
    reorder_level = VALUES(reorder_level),
    last_updated = VALUES(last_updated);

-- Insert sample batch expiry data
INSERT INTO batch_expiry (medicine_id, batch_number, quantity, expiry_date, status) VALUES
(1, 'AMX-2024-001', 50, '2025-03-15', 'active'),
(1, 'AMX-2024-002', 100, '2025-06-20', 'active'),
(2, 'PAR-2024-001', 200, '2025-12-31', 'active'),
(2, 'PAR-2024-002', 300, '2026-03-10', 'active'),
(3, 'LOS-2024-001', 30, '2025-01-25', 'active'),
(3, 'LOS-2024-002', 15, '2025-02-10', 'active'),
(4, 'MET-2024-001', 100, '2025-08-15', 'active'),
(5, 'OME-2024-001', 50, '2025-04-30', 'active'),
(6, 'SAL-2024-001', 15, '2025-09-20', 'active'),
(7, 'INS-2024-001', 8, '2025-01-15', 'active'),
(8, 'ASP-2024-001', 500, '2026-06-30', 'active'),
(9, 'ATO-2024-001', 40, '2025-05-10', 'active'),
(10, 'CIP-2024-001', 25, '2025-07-25', 'active')
ON DUPLICATE KEY UPDATE batch_number = batch_number;

-- Insert sample prescriptions
INSERT INTO prescriptions (patient_id, doctor, status, barcode, created_at) VALUES
('P001', 'Dr. Smith', 'new', 'PRES-001', NOW()),
('P002', 'Dr. Johnson', 'in_process', 'PRES-002', NOW() - INTERVAL 1 HOUR),
('P003', 'Dr. Williams', 'ready', 'PRES-003', NOW() - INTERVAL 2 HOUR),
('P004', 'Dr. Brown', 'dispensed', 'PRES-004', NOW() - INTERVAL 3 HOUR),
('P005', 'Dr. Davis', 'new', 'PRES-005', NOW() - INTERVAL 30 MINUTE)
ON DUPLICATE KEY UPDATE patient_id = patient_id;

-- Insert sample prescription items
INSERT INTO prescription_items (prescription_id, medicine_id, quantity, instructions) VALUES
(1, 1, 21, 'Take 1 capsule three times daily for 7 days'),
(1, 2, 10, 'Take 1 tablet as needed for pain'),
(2, 3, 30, 'Take 1 tablet once daily'),
(2, 4, 60, 'Take 1 tablet twice daily with meals'),
(3, 5, 14, 'Take 1 capsule once daily before breakfast'),
(3, 2, 20, 'Take 1-2 tablets as needed'),
(4, 6, 1, 'Use 2 puffs as needed for breathing difficulty'),
(4, 7, 1, 'Inject as prescribed'),
(5, 8, 30, 'Take 1 tablet once daily'),
(5, 9, 30, 'Take 1 tablet once daily at bedtime')
ON DUPLICATE KEY UPDATE quantity = quantity;

-- Insert sample pharmacy reports data
INSERT INTO pharmacy_reports_data (report_date, drug_name, therapeutic_class, quantity_sold, revenue) VALUES
(CURDATE(), 'Paracetamol 500mg', 'Analgesic', 150, 862.50),
(CURDATE(), 'Amoxicillin 500mg', 'Antibiotic', 80, 2040.00),
(CURDATE(), 'Metformin 500mg', 'Antidiabetic', 120, 1440.00),
(CURDATE(), 'Omeprazole 20mg', 'Antacid', 60, 930.00),
(CURDATE(), 'Losartan 50mg', 'Antihypertensive', 45, 821.25),
(CURDATE() - INTERVAL 1 DAY, 'Paracetamol 500mg', 'Analgesic', 180, 1035.00),
(CURDATE() - INTERVAL 1 DAY, 'Amoxicillin 500mg', 'Antibiotic', 95, 2422.50),
(CURDATE() - INTERVAL 1 DAY, 'Aspirin 100mg', 'Antiplatelet', 200, 700.00),
(CURDATE() - INTERVAL 2 DAY, 'Paracetamol 500mg', 'Analgesic', 165, 948.75),
(CURDATE() - INTERVAL 2 DAY, 'Metformin 500mg', 'Antidiabetic', 110, 1320.00),
(CURDATE() - INTERVAL 2 DAY, 'Atorvastatin 20mg', 'Antilipemic', 35, 796.25),
(CURDATE() - INTERVAL 3 DAY, 'Amoxicillin 500mg', 'Antibiotic', 100, 2550.00),
(CURDATE() - INTERVAL 3 DAY, 'Ciprofloxacin 500mg', 'Antibiotic', 25, 875.00)
ON DUPLICATE KEY UPDATE quantity_sold = quantity_sold;

