-- Pharmacy Management System - Inventory Sample Data
-- This script populates the inventory_stock table with realistic sample data
-- Run this after the main database_setup.sql script

-- First, ensure medicine_master has data (if not already populated)
INSERT IGNORE INTO medicine_master (drug_name, generic_name, manufacturer, dosage_form, strength, therapeutic_class, price) VALUES
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
('Tramadol 50mg', 'Tramadol', 'Grünenthal', 'Capsule', '50mg', 'Analgesic', 24.00);

-- Populate inventory_stock with realistic data
-- This includes various stock levels: critical (below minimum), low (below reorder), and normal

-- Critical Stock Items (Below Minimum Level) - RED ALERT (reorder_level 250-400)
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

-- Low Stock Items (Below Reorder Level) - AMBER ALERT (reorder_level 250-400)
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

-- Normal Stock Items (Above Reorder Level) - GREEN (reorder_level 250-400)
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

-- Create inventory records for any remaining medicines without inventory
INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level)
SELECT 
    mm.id,
    FLOOR(50 + RAND() * 200) as current_stock,
    FLOOR(80 + RAND() * 40) as minimum_stock_level,
    FLOOR(250 + RAND() * 151) as reorder_level
FROM medicine_master mm
LEFT JOIN inventory_stock inv ON mm.id = inv.medicine_id
WHERE inv.id IS NULL
ON DUPLICATE KEY UPDATE current_stock = current_stock;

-- Summary of inserted data
SELECT 
    COUNT(*) as total_inventory_items,
    SUM(CASE WHEN current_stock <= minimum_stock_level THEN 1 ELSE 0 END) as critical_items,
    SUM(CASE WHEN current_stock > minimum_stock_level AND current_stock <= reorder_level THEN 1 ELSE 0 END) as low_stock_items,
    SUM(CASE WHEN current_stock > reorder_level THEN 1 ELSE 0 END) as normal_stock_items
FROM inventory_stock;

