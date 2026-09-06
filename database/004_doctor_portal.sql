-- Doctor Portal – real data tables and seed (Philippine HMS).
-- Run after 001_core_schema.sql and 002_core_seed.sql. Links to users (doctors), patients, appointments.

SET NAMES utf8mb4;

-- Patient extras (allergies, code status, philhealth) – links to patients.id
CREATE TABLE IF NOT EXISTS patient_extras (
  patient_id INT UNSIGNED NOT NULL PRIMARY KEY,
  allergies VARCHAR(255) DEFAULT NULL,
  code_status VARCHAR(50) DEFAULT 'Full Code',
  philhealth VARCHAR(30) DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inpatient assignments: which doctor is following which patient in which ward
CREATE TABLE IF NOT EXISTS doctor_inpatient_assignments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NOT NULL,
  ward VARCHAR(100) DEFAULT NULL,
  bed VARCHAR(20) DEFAULT NULL,
  admission_date DATE DEFAULT NULL,
  discharge_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_doctor (doctor_id),
  INDEX idx_patient (patient_id),
  INDEX idx_admission (admission_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Consults (specialty consult requests)
CREATE TABLE IF NOT EXISTS doctor_consults (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  doctor_id INT UNSIGNED NOT NULL,
  service VARCHAR(100) NOT NULL,
  indication TEXT,
  requested_date DATE NOT NULL,
  status ENUM('Pending','Completed','Cancelled') DEFAULT 'Pending',
  completed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_doctor (doctor_id),
  INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Referrals (incoming to this doctor)
CREATE TABLE IF NOT EXISTS doctor_referrals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  to_doctor_id INT UNSIGNED NOT NULL,
  from_source VARCHAR(150) DEFAULT NULL,
  reason TEXT,
  referral_date DATE NOT NULL,
  status ENUM('Pending','Accepted','Declined') DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_to_doctor (to_doctor_id),
  INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Signing queue (orders to sign)
CREATE TABLE IF NOT EXISTS doctor_signing_queue (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT UNSIGNED NOT NULL,
  patient_id INT UNSIGNED NOT NULL,
  order_type VARCHAR(50) NOT NULL,
  order_description TEXT,
  ordered_at DATETIME NOT NULL,
  status ENUM('Pending','Signed') DEFAULT 'Pending',
  signed_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_doctor (doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Result alerts (critical values)
CREATE TABLE IF NOT EXISTS doctor_result_alerts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT UNSIGNED NOT NULL,
  patient_id INT UNSIGNED NOT NULL,
  result_text TEXT NOT NULL,
  alerted_at DATETIME NOT NULL,
  acknowledged TINYINT(1) DEFAULT 0,
  acknowledged_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_doctor (doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pending results (orders not yet resulted)
CREATE TABLE IF NOT EXISTS doctor_pending_results (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT UNSIGNED NOT NULL,
  patient_id INT UNSIGNED NOT NULL,
  order_description VARCHAR(255) NOT NULL,
  ordered_at DATETIME NOT NULL,
  status VARCHAR(50) DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_doctor (doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages (inbox for doctor)
CREATE TABLE IF NOT EXISTS doctor_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT UNSIGNED NOT NULL,
  from_name VARCHAR(150) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT,
  sent_at DATETIME NOT NULL,
  unread TINYINT(1) DEFAULT 1,
  read_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_doctor (doctor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements (hospital-wide)
CREATE TABLE IF NOT EXISTS doctor_announcements (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  body TEXT,
  department VARCHAR(100) DEFAULT NULL,
  published_at DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_published (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Doctor audit log (chart access, break-glass)
CREATE TABLE IF NOT EXISTS doctor_audit_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT UNSIGNED NOT NULL,
  action VARCHAR(100) NOT NULL,
  patient_id INT UNSIGNED DEFAULT NULL,
  reason VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  INDEX idx_doctor (doctor_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order sets (templates) – reference only, no doctor_id
CREATE TABLE IF NOT EXISTS doctor_order_sets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  category VARCHAR(100) DEFAULT NULL,
  description TEXT,
  updated_at DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========== SEED: Doctor users (Philippine, role doctor) ==========
-- Use INSERT IGNORE and assume role_id 5 = doctor from roles table
INSERT IGNORE INTO users (id, username, first_name, last_name, email, password, role, role_id, status, created_at, updated_at) VALUES
(10, 'dr.reyes', 'Eduardo', 'Reyes', 'e.reyes@hospital.ph', NULL, 'doctor', 5, 'active', NOW(), NOW()),
(11, 'dr.cruz', 'Imelda', 'Cruz', 'i.cruz@hospital.ph', NULL, 'doctor', 5, 'active', NOW(), NOW()),
(12, 'dr.villanueva', 'Roberto', 'Villanueva', 'r.villanueva@hospital.ph', NULL, 'doctor', 5, 'active', NOW(), NOW()),
(13, 'dr.abad', 'Corazon', 'Abad', 'c.abad@hospital.ph', NULL, 'doctor', 5, 'active', NOW(), NOW()),
(14, 'dr.navarro', 'Felipe', 'Navarro', 'f.navarro@hospital.ph', NULL, 'doctor', 5, 'active', NOW(), NOW()),
(15, 'dr.tan', 'Lorna', 'Tan', 'l.tan@hospital.ph', NULL, 'doctor', 5, 'active', NOW(), NOW());

-- ========== SEED: More patients (Philippine names, for doctor portal) ==========
INSERT IGNORE INTO patients (id, hospital_id, first_name, last_name, birth_date, gender, email, contact_number, address, status, created_at) VALUES
(10, 'MRN-2025-010', 'Maria', 'Santos', '1975-03-15', 'F', 'maria.santos@example.ph', '09171234001', 'Quezon City', 'active', NOW()),
(11, 'MRN-2025-011', 'Rodrigo', 'Mendoza', '1982-07-22', 'M', 'rodrigo.m@example.ph', '09171234002', 'Manila', 'active', NOW()),
(12, 'MRN-2025-012', 'Cecilia', 'Bautista', '1968-11-08', 'F', 'cecilia.b@example.ph', '09171234003', 'Cebu City', 'active', NOW()),
(13, 'MRN-2025-013', 'Hector', 'dela Rosa', '1990-01-30', 'M', 'hector.d@example.ph', '09171234004', 'Davao', 'active', NOW()),
(14, 'MRN-2025-014', 'Aurora', 'Santiago', '1955-09-12', 'F', 'aurora.s@example.ph', '09171234005', 'Iloilo', 'active', NOW()),
(15, 'MRN-2025-015', 'Antonio', 'Garcia', '1978-05-20', 'M', 'antonio.g@example.ph', '09171234006', 'Bacolod', 'active', NOW()),
(16, 'MRN-2025-016', 'Lorna', 'Ramos', '1985-12-03', 'F', 'lorna.r@example.ph', '09171234007', 'Pampanga', 'active', NOW()),
(17, 'MRN-2025-017', 'Emilio', 'Castillo', '1962-08-14', 'M', 'emilio.c@example.ph', '09171234008', 'Baguio', 'active', NOW()),
(18, 'MRN-2025-018', 'Rosa', 'Almario', '1970-04-10', 'F', 'rosa.a@example.ph', '09171234009', 'Bulacan', 'active', NOW()),
(19, 'MRN-2025-019', 'Gregorio', 'Villanueva', '1988-02-28', 'M', 'gregorio.v@example.ph', '09171234010', 'Laguna', 'active', NOW()),
(20, 'MRN-2025-020', 'Corazon', 'Dimaculangan', '1965-06-15', 'F', 'corazon.d@example.ph', '09171234011', 'Cavite', 'active', NOW());

INSERT IGNORE INTO patient_extras (patient_id, allergies, code_status, philhealth) VALUES
(1, 'None', 'Full Code', '12-345678901-2'),
(2, 'None', 'Full Code', '98-765432109-8'),
(10, 'Penicillin', 'Full Code', '12-345678901-2'),
(11, 'None', 'Full Code', '98-765432109-8'),
(12, 'Sulfa', 'Full Code', '11-223344556-7'),
(13, 'None', 'DNR', '22-334455667-8'),
(14, 'Latex', 'Full Code', '33-445566778-9'),
(15, 'None', 'Full Code', '44-556677889-0'),
(16, 'Iodine', 'Full Code', NULL),
(17, 'None', 'Full Code', '55-667788990-1'),
(18, 'None', 'Full Code', '66-778899001-2'),
(19, 'None', 'Full Code', '77-889900112-3'),
(20, 'None', 'Full Code', '88-990011223-4');

-- ========== SEED: Appointments (today + past/future, multiple doctors) ==========
INSERT IGNORE INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status, notes, created_at, updated_at) VALUES
(10, 10, CURDATE(), '08:00:00', 'in_progress', 'Follow-up hypertension', NOW(), NOW()),
(11, 10, CURDATE(), '08:30:00', 'scheduled', 'Post-op suture removal', NOW(), NOW()),
(12, 10, CURDATE(), '09:00:00', 'scheduled', 'Diabetes monitoring', NOW(), NOW()),
(17, 10, CURDATE(), '09:30:00', 'scheduled', 'Annual physical', NOW(), NOW()),
(16, 10, CURDATE(), '10:00:00', 'scheduled', 'UTI follow-up', NOW(), NOW()),
(18, 10, CURDATE(), '10:30:00', 'scheduled', 'Routine check', NOW(), NOW()),
(19, 10, CURDATE(), '11:00:00', 'scheduled', 'Pre-op clearance', NOW(), NOW()),
(20, 10, CURDATE(), '14:00:00', 'scheduled', 'Hypertension', NOW(), NOW()),
(13, 11, CURDATE(), '08:00:00', 'scheduled', 'Cardiology consult', NOW(), NOW()),
(14, 11, CURDATE(), '09:00:00', 'completed', 'Echo review', NOW(), NOW()),
(15, 11, CURDATE(), '09:30:00', 'scheduled', 'Stress test follow-up', NOW(), NOW()),
(10, 11, CURDATE()-INTERVAL 2 DAY, '10:00:00', 'completed', 'Hypertension, echo', NOW(), NOW()),
(12, 12, CURDATE(), '08:00:00', 'scheduled', 'Surgical follow-up', NOW(), NOW()),
(11, 12, CURDATE(), '11:00:00', 'scheduled', 'Gallbladder post-op', NOW(), NOW()),
(10, 10, CURDATE()+INTERVAL 1 DAY, '08:00:00', 'scheduled', 'Follow-up', NOW(), NOW()),
(11, 10, CURDATE()+INTERVAL 1 DAY, '09:00:00', 'scheduled', 'Wound check', NOW(), NOW()),
(1, 1, CURDATE(), '08:00:00', 'scheduled', 'Clinic visit', NOW(), NOW()),
(2, 1, CURDATE(), '09:00:00', 'scheduled', 'Follow-up', NOW(), NOW()),
(3, 1, CURDATE(), '10:00:00', 'scheduled', 'Check-up', NOW(), NOW());

-- ========== SEED: Inpatient assignments (Dr. Reyes = 10, Dr. Cruz = 11) ==========
INSERT IGNORE INTO doctor_inpatient_assignments (patient_id, doctor_id, ward, bed, admission_date, discharge_date) VALUES
(10, 10, 'Medical Ward 2', '2A', CURDATE()-INTERVAL 5 DAY, NULL),
(11, 10, 'Surgical Ward 1', '3B', CURDATE()-INTERVAL 3 DAY, NULL),
(13, 11, 'ICU', '1', CURDATE()-INTERVAL 2 DAY, NULL),
(14, 10, 'Medical Ward 2', '4C', CURDATE()-INTERVAL 7 DAY, NULL),
(15, 10, 'Pay Ward', '101', CURDATE()-INTERVAL 1 DAY, NULL),
(16, 10, 'Charity Ward', '5A', CURDATE()-INTERVAL 4 DAY, NULL);

-- ========== SEED: Consults ==========
INSERT IGNORE INTO doctor_consults (patient_id, doctor_id, service, indication, requested_date, status) VALUES
(10, 10, 'Cardiology', 'Hypertension, echo for murmur', CURDATE()-INTERVAL 2 DAY, 'Completed'),
(13, 10, 'Pulmonology', 'ARDS, vent management', CURDATE(), 'Pending'),
(14, 10, 'Nephrology', 'CKD stage 3, electrolyte review', CURDATE()-INTERVAL 1 DAY, 'Completed'),
(16, 10, 'Infectious Disease', 'Pyelonephritis, culture review', CURDATE(), 'Pending');

-- ========== SEED: Referrals ==========
INSERT IGNORE INTO doctor_referrals (patient_id, to_doctor_id, from_source, reason, referral_date, status) VALUES
(10, 10, 'Dr. Imelda Cruz', 'Hypertension follow-up, echo done', CURDATE()-INTERVAL 2 DAY, 'Accepted'),
(13, 10, 'ER', 'Chest pain rule-out MI', CURDATE(), 'Pending'),
(17, 10, 'Outpatient Dept', 'PhilHealth referral – diabetes screening', CURDATE()-INTERVAL 1 DAY, 'Accepted');

-- ========== SEED: Signing queue ==========
INSERT IGNORE INTO doctor_signing_queue (doctor_id, patient_id, order_type, order_description, ordered_at, status) VALUES
(10, 10, 'Lab', 'CBC, CMP, HbA1c', NOW()-INTERVAL 2 HOUR, 'Pending'),
(10, 13, 'Imaging', 'CT Chest with contrast', NOW()-INTERVAL 1 HOUR, 'Pending'),
(10, 14, 'Medication', 'Losartan 50 mg PO daily', NOW()-INTERVAL 30 MINUTE, 'Pending'),
(11, 10, 'Lab', 'Lipid panel', NOW()-INTERVAL 1 HOUR, 'Pending');

-- ========== SEED: Result alerts ==========
INSERT IGNORE INTO doctor_result_alerts (doctor_id, patient_id, result_text, alerted_at, acknowledged) VALUES
(10, 13, 'K+ 6.2 mEq/L (Critical high)', NOW()-INTERVAL 4 HOUR, 0);

-- ========== SEED: Pending results ==========
INSERT IGNORE INTO doctor_pending_results (doctor_id, patient_id, order_description, ordered_at, status) VALUES
(10, 11, 'CXR 2-view', NOW()-INTERVAL 3 HOUR, 'In progress'),
(10, 10, 'HbA1c', NOW()-INTERVAL 5 HOUR, 'Specimen received'),
(10, 15, 'Blood culture x2', NOW()-INTERVAL 2 HOUR, 'Pending');

-- ========== SEED: Messages ==========
INSERT IGNORE INTO doctor_messages (doctor_id, from_name, subject, body, sent_at, unread) VALUES
(10, 'Nursing – Medical Ward 2', 'Re: Santos, Maria – BP elevated 160/95', 'Patient Maria Santos BP 160/95 at 06:00. Please advise.', NOW()-INTERVAL 1 HOUR, 1),
(10, 'Lab', 'Critical: dela Rosa, Hector – Potassium', 'Critical result K+ 6.2. Notify physician.', NOW()-INTERVAL 4 HOUR, 1),
(10, 'Dr. Imelda Cruz', 'Cardiology consult note – Santos, Maria', 'Echo done. No acute intervention. See note in chart.', NOW()-INTERVAL 1 DAY, 0),
(11, 'Cardiac Cath Lab', 'Schedule – Santiago, Aurora', 'Echo scheduled tomorrow 08:00.', NOW()-INTERVAL 2 HOUR, 1);

-- ========== SEED: Announcements ==========
INSERT IGNORE INTO doctor_announcements (title, body, department, published_at) VALUES
('HIS maintenance – Sunday 02:00–06:00', 'Planned downtime. Please save work.', 'IT', CURDATE()-INTERVAL 3 DAY),
('PhilHealth case rate updates (Feb 2025)', 'New case rates effective 01 Feb. See Billing.', 'Billing', CURDATE()-INTERVAL 1 DAY),
('New dengue NS1 protocol – ER and OPD', 'Please follow updated DOH/PCMC protocol.', 'Medical', CURDATE());

-- ========== SEED: Order sets ==========
INSERT IGNORE INTO doctor_order_sets (name, category, description, updated_at) VALUES
('Admission – General Medicine', 'Admission', 'CBC, CMP, CXR, ECG, urinalysis', CURDATE()-INTERVAL 1 MONTH),
('Dengue protocol (PCMC/DOH)', 'Protocol', 'CBC, platelet, Hct, NS1, IgM/IgG', CURDATE()-INTERVAL 2 WEEK),
('TB DOTS work-up', 'Protocol', 'Sputum AFB x3, CXR, LFT', CURDATE()-INTERVAL 2 WEEK),
('Chest pain rule-out MI', 'Protocol', 'ECG, Troponin I serial, CXR', CURDATE()-INTERVAL 1 WEEK),
('Post-op day 1 (General Surgery)', 'Surgery', 'CBC, CMP, wound check orders', CURDATE()-INTERVAL 1 WEEK);

-- ========== SEED: Audit log (sample) ==========
INSERT IGNORE INTO doctor_audit_log (doctor_id, action, patient_id, reason, created_at, ip_address) VALUES
(10, 'Chart access', 10, 'Routine rounding', NOW(), '192.168.1.10'),
(10, 'Break-glass access', 13, 'Emergency consult', NOW()-INTERVAL 1 DAY, '192.168.1.10');

-- ========== Chart data tables (vitals, labs, problems, meds, notes, orders) ==========
CREATE TABLE IF NOT EXISTS patient_vitals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  recorded_at DATETIME NOT NULL,
  bp_sys INT DEFAULT NULL,
  bp_dia INT DEFAULT NULL,
  hr INT DEFAULT NULL,
  temp DECIMAL(4,2) DEFAULT NULL,
  rr INT DEFAULT NULL,
  spo2 INT DEFAULT NULL,
  INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_lab_results (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  panel VARCHAR(100) NOT NULL,
  result_text TEXT,
  status VARCHAR(50) DEFAULT 'Final',
  collected_at DATETIME DEFAULT NULL,
  reported_at DATETIME DEFAULT NULL,
  INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_problems (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  icd VARCHAR(20) DEFAULT NULL,
  description VARCHAR(255) NOT NULL,
  status VARCHAR(50) DEFAULT 'Active',
  onset_date DATE DEFAULT NULL,
  INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_medications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  drug VARCHAR(200) NOT NULL,
  dose VARCHAR(100) DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'Active',
  start_date DATE DEFAULT NULL,
  prescriber VARCHAR(150) DEFAULT NULL,
  INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  type VARCHAR(50) DEFAULT 'Progress',
  author VARCHAR(150) DEFAULT NULL,
  note_date DATETIME NOT NULL,
  snippet TEXT,
  INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  order_type VARCHAR(50) NOT NULL,
  order_description VARCHAR(255) DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'Pending',
  order_date DATE NOT NULL,
  INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  task VARCHAR(255) NOT NULL,
  due_datetime DATETIME DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'Pending',
  INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_imaging (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  modality VARCHAR(50) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  result_text TEXT,
  status VARCHAR(50) DEFAULT 'Final',
  performed_at DATETIME DEFAULT NULL,
  INDEX idx_patient (patient_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed chart data for patients 10, 11
INSERT IGNORE INTO patient_vitals (patient_id, recorded_at, bp_sys, bp_dia, hr, temp, rr, spo2) VALUES
(10, CONCAT(CURDATE(), ' 06:00:00'), 118, 72, 72, 36.8, 16, 98),
(10, CONCAT(CURDATE(), ' 10:00:00'), 122, 75, 75, 36.9, 16, 99),
(10, CONCAT(CURDATE(), ' 14:00:00'), 120, 74, 74, 37.0, 18, 97),
(11, CONCAT(CURDATE(), ' 08:00:00'), 110, 70, 68, 36.5, 14, 99);

INSERT IGNORE INTO patient_lab_results (patient_id, panel, result_text, status, collected_at, reported_at) VALUES
(10, 'CBC', 'WBC 7.2, Hgb 12.4, Hct 37.2, Plt 245', 'Final', CONCAT(CURDATE()-INTERVAL 1 DAY, ' 08:00:00'), CONCAT(CURDATE()-INTERVAL 1 DAY, ' 09:15:00')),
(10, 'CMP', 'Na 138, K 4.2, Cl 102, CO2 24, Glu 95, Creat 1.0, BUN 14', 'Final', CONCAT(CURDATE()-INTERVAL 1 DAY, ' 08:00:00'), CONCAT(CURDATE()-INTERVAL 1 DAY, ' 10:00:00')),
(11, 'CBC', 'WBC 8.1, Hgb 13.0, Hct 39, Plt 220', 'Final', CONCAT(CURDATE(), ' 07:00:00'), CONCAT(CURDATE(), ' 08:30:00'));

INSERT IGNORE INTO patient_problems (patient_id, icd, description, status, onset_date) VALUES
(10, 'I10', 'Essential hypertension', 'Active', CURDATE()-INTERVAL 1 YEAR),
(10, 'E11.9', 'Type 2 diabetes without complications', 'Active', CURDATE()-INTERVAL 2 YEAR),
(10, 'J18.9', 'Pneumonia, unspecified', 'Resolved', CURDATE()-INTERVAL 5 DAY),
(11, 'K80.0', 'Gallbladder stones', 'Resolved', CURDATE()-INTERVAL 7 DAY);

INSERT IGNORE INTO patient_medications (patient_id, drug, dose, status, start_date, prescriber) VALUES
(10, 'Amlodipine 5 mg', '1 tab PO daily', 'Active', CURDATE()-INTERVAL 30 DAY, 'Dr. Reyes'),
(10, 'Metformin 500 mg', '1 tab PO BID', 'Active', CURDATE()-INTERVAL 60 DAY, 'Dr. Reyes'),
(10, 'Losartan 50 mg', '1 tab PO daily', 'Active', CURDATE()-INTERVAL 14 DAY, 'Dr. Cruz'),
(11, 'Paracetamol 500 mg', '2 tabs PRN pain', 'Active', CURDATE()-INTERVAL 3 DAY, 'Dr. Villanueva');

INSERT IGNORE INTO patient_notes (patient_id, type, author, note_date, snippet) VALUES
(10, 'Progress', 'Dr. Reyes', CONCAT(CURDATE()-INTERVAL 1 DAY, ' 09:00:00'), 'Patient stable. Continue current regimen. Plan discharge tomorrow if labs remain stable.'),
(10, 'Consult', 'Cardiology', CONCAT(CURDATE()-INTERVAL 2 DAY, ' 14:00:00'), 'Recommend echo for evaluation of murmur. No acute intervention.'),
(11, 'Progress', 'Dr. Villanueva', CONCAT(CURDATE(), ' 08:30:00'), 'Post-op day 3. Wound clean. Advance diet. Plan discharge tomorrow.');

INSERT IGNORE INTO patient_orders (patient_id, order_type, order_description, status, order_date) VALUES
(10, 'Lab', 'CBC, CMP', 'Completed', CURDATE()-INTERVAL 1 DAY),
(10, 'Medication', 'Amlodipine 5 mg PO daily', 'Active', CURDATE()),
(10, 'Imaging', 'CXR portable', 'Completed', CURDATE()-INTERVAL 1 DAY),
(11, 'Lab', 'CBC', 'Completed', CURDATE());

INSERT IGNORE INTO patient_tasks (patient_id, task, due_datetime, status) VALUES
(10, 'Review morning labs', CONCAT(CURDATE(), ' 09:00:00'), 'Done'),
(10, 'Discharge summary', CONCAT(CURDATE(), ' 12:00:00'), 'Pending'),
(11, 'Remove sutures', CONCAT(CURDATE(), ' 14:00:00'), 'Pending');

INSERT IGNORE INTO patient_imaging (patient_id, modality, description, result_text, status, performed_at) VALUES
(10, 'CXR', 'Portable chest X-ray', 'No focal consolidation. Heart size normal.', 'Final', CONCAT(CURDATE()-INTERVAL 1 DAY, ' 07:30:00')),
(10, 'CT Chest', 'CT chest with contrast', 'No pulmonary embolism. Small RLL atelectasis.', 'Final', CONCAT(CURDATE()-INTERVAL 2 DAY, ' 16:00:00'));
