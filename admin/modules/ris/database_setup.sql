-- Radiology & Imaging System (RIS) Database Tables
-- Run this SQL script to create all required tables for the RIS module

-- Table: imaging_modalities
CREATE TABLE IF NOT EXISTS imaging_modalities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    modality_name VARCHAR(100) NOT NULL,
    model VARCHAR(150),
    location VARCHAR(100),
    status ENUM('operational', 'maintenance') DEFAULT 'operational',
    last_calibration_date DATE,
    scheduled_maintenance DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_location (location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: exam_types
CREATE TABLE IF NOT EXISTS exam_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_code VARCHAR(50) NOT NULL UNIQUE,
    exam_name VARCHAR(150) NOT NULL,
    modality_type VARCHAR(50) NOT NULL,
    body_part VARCHAR(100),
    duration_minutes INT,
    prep_instructions TEXT,
    report_template TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_modality (modality_type),
    INDEX idx_exam_code (exam_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: radiology_orders
CREATE TABLE IF NOT EXISTS radiology_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50) NOT NULL,
    exam_type VARCHAR(150) NOT NULL,
    referring_doctor VARCHAR(150),
    scheduled_time DATETIME NOT NULL,
    priority ENUM('routine', 'urgent', 'stat') DEFAULT 'routine',
    status ENUM('scheduled', 'in_progress', 'completed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_scheduled_time (scheduled_time),
    INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: radiology_reports
CREATE TABLE IF NOT EXISTS radiology_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50) NOT NULL,
    exam_type VARCHAR(150) NOT NULL,
    radiologist VARCHAR(150),
    findings_summary TEXT,
    full_report TEXT,
    status ENUM('draft', 'awaiting_review', 'approved', 'sent_back') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    approved_by INT UNSIGNED,
    sent_back_at TIMESTAMP NULL,
    sent_back_by INT UNSIGNED,
    INDEX idx_status (status),
    INDEX idx_patient (patient_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: radiology_analytics
CREATE TABLE IF NOT EXISTS radiology_analytics (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    modality VARCHAR(50),
    exams_per_modality INT UNSIGNED DEFAULT 0,
    report_turnaround_hours DECIMAL(5, 2) DEFAULT 0.00,
    radiologist_productivity DECIMAL(5, 2) DEFAULT 0.00,
    equipment_downtime_hours DECIMAL(5, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_report_date (report_date),
    INDEX idx_modality (modality)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample imaging modalities
INSERT INTO imaging_modalities (modality_name, model, location, status, last_calibration_date, scheduled_maintenance) VALUES
('CT-Scanner', 'Siemens SOMATOM Definition AS', 'Room-3A', 'operational', '2024-11-15', '2025-02-15'),
('MRI', 'Philips Ingenia 3.0T', 'Room-2B', 'operational', '2024-10-20', '2025-01-20'),
('X-Ray', 'GE Definium 8000', 'Room-1A', 'operational', '2024-12-01', '2025-03-01'),
('Ultrasound', 'Philips EPIQ 7', 'Room-4C', 'operational', '2024-11-10', '2025-02-10'),
('Mammography', 'Hologic Selenia Dimensions', 'Room-5A', 'operational', '2024-10-05', '2025-01-05'),
('CT-Scanner', 'GE Revolution CT', 'Room-3B', 'maintenance', '2024-09-15', '2024-12-20')
ON DUPLICATE KEY UPDATE modality_name = modality_name;

-- Insert sample exam types
INSERT INTO exam_types (exam_code, exam_name, modality_type, body_part, duration_minutes, prep_instructions, report_template) VALUES
('CT-CHEST-001', 'CT Chest with Contrast', 'CT', 'Chest', 30, 'NPO 4 hours before exam. Remove all jewelry and metal objects.', 
'PATIENT: [PATIENT_NAME]\nEXAM DATE: [EXAM_DATE]\nREFERRING PHYSICIAN: [REFERRING_DOCTOR]\n\nTECHNIQUE: CT scan of the chest with IV contrast\n\nFINDINGS:\n[FINDINGS]\n\nIMPRESSION:\n[IMPRESSION]\n\nRADIOLOGIST: [RADIOLOGIST]\nDATE: [REPORT_DATE]'),

('MRI-BRAIN-001', 'MRI Brain without Contrast', 'MRI', 'Head', 45, 'Remove all metal objects. No contrast required.', 
'PATIENT: [PATIENT_NAME]\nEXAM DATE: [EXAM_DATE]\nREFERRING PHYSICIAN: [REFERRING_DOCTOR]\n\nTECHNIQUE: MRI of the brain without contrast\n\nFINDINGS:\n[FINDINGS]\n\nIMPRESSION:\n[IMPRESSION]\n\nRADIOLOGIST: [RADIOLOGIST]\nDATE: [REPORT_DATE]'),

('XR-CHEST-001', 'Chest X-Ray PA and Lateral', 'X-Ray', 'Chest', 10, 'Remove jewelry and clothing from chest area.', 
'PATIENT: [PATIENT_NAME]\nEXAM DATE: [EXAM_DATE]\nREFERRING PHYSICIAN: [REFERRING_DOCTOR]\n\nTECHNIQUE: PA and lateral chest radiographs\n\nFINDINGS:\n[FINDINGS]\n\nIMPRESSION:\n[IMPRESSION]\n\nRADIOLOGIST: [RADIOLOGIST]\nDATE: [REPORT_DATE]'),

('US-ABD-001', 'Ultrasound Abdomen Complete', 'Ultrasound', 'Abdomen', 30, 'NPO 8 hours before exam. Full bladder required.', 
'PATIENT: [PATIENT_NAME]\nEXAM DATE: [EXAM_DATE]\nREFERRING PHYSICIAN: [REFERRING_DOCTOR]\n\nTECHNIQUE: Complete abdominal ultrasound\n\nFINDINGS:\n[FINDINGS]\n\nIMPRESSION:\n[IMPRESSION]\n\nRADIOLOGIST: [RADIOLOGIST]\nDATE: [REPORT_DATE]'),

('MAMMO-001', 'Mammography Bilateral Screening', 'Mammography', 'Breast', 20, 'No deodorant or powder on day of exam.', 
'PATIENT: [PATIENT_NAME]\nEXAM DATE: [EXAM_DATE]\nREFERRING PHYSICIAN: [REFERRING_DOCTOR]\n\nTECHNIQUE: Bilateral screening mammography\n\nFINDINGS:\n[FINDINGS]\n\nIMPRESSION:\n[IMPRESSION]\n\nRADIOLOGIST: [RADIOLOGIST]\nDATE: [REPORT_DATE]')
ON DUPLICATE KEY UPDATE exam_name = exam_name;

-- Insert sample radiology orders
INSERT INTO radiology_orders (patient_id, exam_type, referring_doctor, scheduled_time, priority, status) VALUES
('P001', 'CT Chest with Contrast', 'Dr. Smith', NOW() + INTERVAL 2 HOUR, 'routine', 'scheduled'),
('P002', 'MRI Brain without Contrast', 'Dr. Johnson', NOW() + INTERVAL 3 HOUR, 'urgent', 'scheduled'),
('P003', 'Chest X-Ray PA and Lateral', 'Dr. Williams', NOW() + INTERVAL 1 HOUR, 'stat', 'in_progress'),
('P004', 'Ultrasound Abdomen Complete', 'Dr. Brown', NOW() + INTERVAL 4 HOUR, 'routine', 'scheduled'),
('P005', 'Mammography Bilateral Screening', 'Dr. Davis', NOW() + INTERVAL 5 HOUR, 'routine', 'scheduled'),
('P006', 'CT Chest with Contrast', 'Dr. Miller', NOW() - INTERVAL 1 HOUR, 'urgent', 'completed'),
('P007', 'MRI Brain without Contrast', 'Dr. Wilson', NOW() + INTERVAL 6 HOUR, 'routine', 'scheduled')
ON DUPLICATE KEY UPDATE exam_type = exam_type;

-- Insert sample radiology reports
INSERT INTO radiology_reports (patient_id, exam_type, radiologist, findings_summary, full_report, status) VALUES
('P001', 'CT Chest with Contrast', 'Dr. Anderson', 'No acute pulmonary findings. Heart size normal.', 
'Full report details: CT chest shows clear lung fields, no masses or consolidation. Heart size and mediastinum are within normal limits.', 'draft'),

('P002', 'MRI Brain without Contrast', 'Dr. Taylor', 'Normal brain MRI. No acute intracranial abnormalities.', 
'Full report details: MRI brain demonstrates normal brain parenchyma, no mass effect, no acute infarct.', 'awaiting_review'),

('P003', 'Chest X-Ray PA and Lateral', 'Dr. Martinez', 'Mild cardiomegaly. Clear lung fields.', 
'Full report details: Chest X-ray shows mild cardiomegaly. Lung fields are clear bilaterally.', 'awaiting_review'),

('P004', 'Ultrasound Abdomen Complete', 'Dr. Garcia', 'Normal liver, kidneys, and spleen. No masses identified.', 
'Full report details: Ultrasound shows normal liver echotexture, normal kidney sizes, normal spleen.', 'approved'),

('P005', 'Mammography Bilateral Screening', 'Dr. Rodriguez', 'BI-RADS 1: Negative. No suspicious findings.', 
'Full report details: Bilateral screening mammography shows no suspicious masses, calcifications, or architectural distortion.', 'approved')
ON DUPLICATE KEY UPDATE exam_type = exam_type;

-- Insert sample radiology analytics
INSERT INTO radiology_analytics (report_date, modality, exams_per_modality, report_turnaround_hours, radiologist_productivity, equipment_downtime_hours) VALUES
(CURDATE(), 'CT', 45, 4.5, 92.5, 0.5),
(CURDATE(), 'MRI', 28, 6.2, 88.3, 1.2),
(CURDATE(), 'X-Ray', 120, 2.1, 95.8, 0.0),
(CURDATE(), 'Ultrasound', 35, 3.8, 90.2, 0.3),
(CURDATE(), 'Mammography', 18, 3.5, 94.1, 0.0),
(CURDATE() - INTERVAL 1 DAY, 'CT', 42, 4.8, 91.2, 0.8),
(CURDATE() - INTERVAL 1 DAY, 'MRI', 25, 6.5, 87.5, 1.5),
(CURDATE() - INTERVAL 1 DAY, 'X-Ray', 115, 2.3, 94.5, 0.0),
(CURDATE() - INTERVAL 2 DAY, 'CT', 48, 4.2, 93.1, 0.3),
(CURDATE() - INTERVAL 2 DAY, 'MRI', 30, 5.9, 89.2, 0.8),
(CURDATE() - INTERVAL 2 DAY, 'X-Ray', 125, 1.9, 96.2, 0.0)
ON DUPLICATE KEY UPDATE exams_per_modality = exams_per_modality;

