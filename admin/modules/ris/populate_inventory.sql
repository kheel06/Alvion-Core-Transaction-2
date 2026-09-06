-- Simple Inventory Population Script
-- This script directly inserts inventory data using medicine IDs
-- Run this after medicine_master table is populated

-- First, let's check and insert medicines if they don't exist
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
('Ciprofloxacin 500mg', 'Ciprofloxacin', 'Bayer', 'Tablet', '500mg', 'Antibiotic', 35.00);

-- Now insert inventory using a simpler approach - get IDs first, then insert
-- Critical Stock Items (Below Minimum Level)
INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated)
SELECT id, 8, 10, 15, NOW() - INTERVAL 2 DAY FROM medicine_master WHERE drug_name = 'Insulin Glargine'
ON DUPLICATE KEY UPDATE current_stock = 8, minimum_stock_level = 10, reorder_level = 15;

INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated)
SELECT id, 12, 15, 20, NOW() - INTERVAL 1 DAY FROM medicine_master WHERE drug_name = 'Salbutamol Inhaler'
ON DUPLICATE KEY UPDATE current_stock = 12, minimum_stock_level = 15, reorder_level = 20;

INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated)
SELECT id, 25, 30, 50, NOW() - INTERVAL 3 DAY FROM medicine_master WHERE drug_name = 'Warfarin 5mg'
ON DUPLICATE KEY UPDATE current_stock = 25, minimum_stock_level = 30, reorder_level = 50;

-- Low Stock Items (Below Reorder Level)
INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated)
SELECT id, 35, 30, 50, NOW() - INTERVAL 1 HOUR FROM medicine_master WHERE drug_name = 'Losartan 50mg'
ON DUPLICATE KEY UPDATE current_stock = 35, minimum_stock_level = 30, reorder_level = 50;

INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated)
SELECT id, 55, 50, 75, NOW() - INTERVAL 5 HOUR FROM medicine_master WHERE drug_name = 'Omeprazole 20mg'
ON DUPLICATE KEY UPDATE current_stock = 55, minimum_stock_level = 50, reorder_level = 75;

INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated)
SELECT id, 42, 40, 50, NOW() - INTERVAL 3 HOUR FROM medicine_master WHERE drug_name = 'Atorvastatin 20mg'
ON DUPLICATE KEY UPDATE current_stock = 42, minimum_stock_level = 40, reorder_level = 50;

INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated)
SELECT id, 22, 25, 30, NOW() - INTERVAL 2 HOUR FROM medicine_master WHERE drug_name = 'Ciprofloxacin 500mg'
ON DUPLICATE KEY UPDATE current_stock = 22, minimum_stock_level = 25, reorder_level = 30;

-- Normal Stock Items (Above Reorder Level)
INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated)
SELECT id, 150, 50, 100, NOW() - INTERVAL 1 HOUR FROM medicine_master WHERE drug_name = 'Amoxicillin 500mg'
ON DUPLICATE KEY UPDATE current_stock = 150, minimum_stock_level = 50, reorder_level = 100;

INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated)
SELECT id, 500, 200, 300, NOW() - INTERVAL 30 MINUTE FROM medicine_master WHERE drug_name = 'Paracetamol 500mg'
ON DUPLICATE KEY UPDATE current_stock = 500, minimum_stock_level = 200, reorder_level = 300;

INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated)
SELECT id, 200, 100, 150, NOW() - INTERVAL 2 HOUR FROM medicine_master WHERE drug_name = 'Metformin 500mg'
ON DUPLICATE KEY UPDATE current_stock = 200, minimum_stock_level = 100, reorder_level = 150;

INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level, last_updated)
SELECT id, 1000, 500, 750, NOW() - INTERVAL 1 DAY FROM medicine_master WHERE drug_name = 'Aspirin 100mg'
ON DUPLICATE KEY UPDATE current_stock = 1000, minimum_stock_level = 500, reorder_level = 750;

-- Create inventory for ALL medicines that don't have inventory yet (with random stock levels)
INSERT INTO inventory_stock (medicine_id, current_stock, minimum_stock_level, reorder_level)
SELECT 
    mm.id,
    CASE 
        WHEN mm.id % 3 = 0 THEN FLOOR(10 + RAND() * 30)  -- Low stock
        WHEN mm.id % 3 = 1 THEN FLOOR(50 + RAND() * 50)   -- Medium stock
        ELSE FLOOR(100 + RAND() * 200)                    -- High stock
    END as current_stock,
    FLOOR(20 + RAND() * 30) as minimum_stock_level,
    FLOOR(40 + RAND() * 60) as reorder_level
FROM medicine_master mm
WHERE NOT EXISTS (
    SELECT 1 FROM inventory_stock inv WHERE inv.medicine_id = mm.id
);

-- Verify the data
SELECT 
    COUNT(*) as total_inventory_items,
    SUM(CASE WHEN current_stock <= minimum_stock_level THEN 1 ELSE 0 END) as critical_items,
    SUM(CASE WHEN current_stock > minimum_stock_level AND current_stock <= reorder_level THEN 1 ELSE 0 END) as low_stock_items,
    SUM(CASE WHEN current_stock > reorder_level THEN 1 ELSE 0 END) as normal_stock_items
FROM inventory_stock;

-- Show sample of inventory data
SELECT 
    inv.id,
    mm.drug_name,
    inv.current_stock,
    inv.minimum_stock_level,
    inv.reorder_level,
    inv.last_updated
FROM inventory_stock inv
JOIN medicine_master mm ON inv.medicine_id = mm.id
ORDER BY inv.current_stock ASC
LIMIT 10;

