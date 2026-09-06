-- Diet & Nutrition Management System (DNMS) Database Setup
-- This script creates all necessary tables with sample data

-- ============================================
-- SUPPORTING TABLES
-- ============================================

-- Dietitians Table
CREATE TABLE IF NOT EXISTS dietitians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    phone VARCHAR(20),
    license_number VARCHAR(50),
    specialization VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Food Items Master Table
CREATE TABLE IF NOT EXISTS food_items_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(200) NOT NULL,
    category VARCHAR(100),
    unit VARCHAR(50) DEFAULT 'g',
    calories_per_unit DECIMAL(10,2) DEFAULT 0,
    protein_per_unit DECIMAL(10,2) DEFAULT 0,
    carbs_per_unit DECIMAL(10,2) DEFAULT 0,
    fats_per_unit DECIMAL(10,2) DEFAULT 0,
    fiber_per_unit DECIMAL(10,2) DEFAULT 0,
    sodium_per_unit DECIMAL(10,2) DEFAULT 0,
    allergens TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MAIN TABLES
-- ============================================

-- Diet Plans Table
CREATE TABLE IF NOT EXISTS diet_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_name VARCHAR(200) NOT NULL,
    category VARCHAR(100) NOT NULL,
    calorie_range_low INT NOT NULL,
    calorie_range_high INT NOT NULL,
    allowed_food_groups TEXT,
    macro_carbs DECIMAL(10,2),
    macro_protein DECIMAL(10,2),
    macro_fats DECIMAL(10,2),
    restrictions TEXT,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Meal Templates Table
CREATE TABLE IF NOT EXISTS meal_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_name VARCHAR(200) NOT NULL,
    meal_type ENUM('Breakfast', 'Lunch', 'Dinner', 'Snack') NOT NULL,
    scheduled_time VARCHAR(50),
    day_of_week VARCHAR(20),
    food_items TEXT NOT NULL,
    portion_sizes TEXT NOT NULL,
    calories DECIMAL(10,2) DEFAULT 0,
    protein DECIMAL(10,2) DEFAULT 0,
    carbs DECIMAL(10,2) DEFAULT 0,
    fats DECIMAL(10,2) DEFAULT 0,
    fiber DECIMAL(10,2) DEFAULT 0,
    sodium DECIMAL(10,2) DEFAULT 0,
    instructions TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_meal_type (meal_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Patient Diet Assignments Table
CREATE TABLE IF NOT EXISTS patient_diet_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50) NOT NULL,
    diet_plan_id INT NOT NULL,
    assigned_by INT,
    approved_by INT,
    reason VARCHAR(200),
    change_reason VARCHAR(200),
    status ENUM('pending', 'active', 'changed', 'discontinued') DEFAULT 'pending',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (diet_plan_id) REFERENCES diet_plans(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_by) REFERENCES dietitians(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES dietitians(id) ON DELETE SET NULL,
    INDEX idx_patient_id (patient_id),
    INDEX idx_status (status),
    INDEX idx_diet_plan_id (diet_plan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Patient Meal Schedule Table
CREATE TABLE IF NOT EXISTS patient_meal_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT NOT NULL,
    meal_template_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME,
    status ENUM('scheduled', 'served', 'skipped', 'cancelled') DEFAULT 'scheduled',
    served_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES patient_diet_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_template_id) REFERENCES meal_templates(id) ON DELETE RESTRICT,
    INDEX idx_assignment_id (assignment_id),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Meal Consumption Log Table
CREATE TABLE IF NOT EXISTS meal_consumption_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50) NOT NULL,
    assignment_id INT NOT NULL,
    meal_schedule_id INT,
    meal_template_id INT,
    consumed_date DATE NOT NULL,
    consumed_time TIME,
    food_items_consumed TEXT,
    actual_calories DECIMAL(10,2),
    actual_protein DECIMAL(10,2),
    actual_carbs DECIMAL(10,2),
    actual_fats DECIMAL(10,2),
    consumption_status ENUM('full', 'partial', 'skipped') DEFAULT 'full',
    notes TEXT,
    logged_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES patient_diet_assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (meal_schedule_id) REFERENCES patient_meal_schedule(id) ON DELETE SET NULL,
    FOREIGN KEY (meal_template_id) REFERENCES meal_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (logged_by) REFERENCES dietitians(id) ON DELETE SET NULL,
    INDEX idx_patient_id (patient_id),
    INDEX idx_consumed_date (consumed_date),
    INDEX idx_assignment_id (assignment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nutrition Compliance Table
CREATE TABLE IF NOT EXISTS nutrition_compliance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id VARCHAR(50) NOT NULL,
    assignment_id INT NOT NULL,
    report_date DATE NOT NULL,
    assigned_plan VARCHAR(200),
    meals_consumed INT DEFAULT 0,
    meals_prescribed INT DEFAULT 0,
    calorie_intake DECIMAL(10,2) DEFAULT 0,
    target_calories DECIMAL(10,2) DEFAULT 0,
    protein_intake DECIMAL(10,2) DEFAULT 0,
    target_protein DECIMAL(10,2) DEFAULT 0,
    compliance_score DECIMAL(5,2) DEFAULT 0,
    adherence_percentage DECIMAL(5,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assignment_id) REFERENCES patient_diet_assignments(id) ON DELETE CASCADE,
    INDEX idx_patient_id (patient_id),
    INDEX idx_report_date (report_date),
    INDEX idx_compliance_score (compliance_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nutrition Analytics Table
CREATE TABLE IF NOT EXISTS nutrition_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2),
    metric_unit VARCHAR(50),
    category VARCHAR(100),
    diet_plan_category VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_date (report_date),
    INDEX idx_metric_name (metric_name),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Insert Dietitians
INSERT INTO dietitians (name, email, phone, license_number, specialization, status) VALUES
('Dr. Sarah Johnson', 'sarah.johnson@hospital.com', '555-0101', 'RD-2020-001', 'Clinical Nutrition, Diabetes Management', 'active'),
('Dr. Michael Chen', 'michael.chen@hospital.com', '555-0102', 'RD-2019-045', 'Renal Nutrition, Critical Care', 'active'),
('Dr. Emily Rodriguez', 'emily.rodriguez@hospital.com', '555-0103', 'RD-2021-078', 'Pediatric Nutrition, Weight Management', 'active'),
('Dr. James Wilson', 'james.wilson@hospital.com', '555-0104', 'RD-2018-112', 'Cardiac Nutrition, Geriatric Care', 'active'),
('Dr. Lisa Anderson', 'lisa.anderson@hospital.com', '555-0105', 'RD-2022-023', 'Oncology Nutrition, Enteral Nutrition', 'active');

-- Insert Food Items Master
INSERT INTO food_items_master (item_name, category, unit, calories_per_unit, protein_per_unit, carbs_per_unit, fats_per_unit, fiber_per_unit, sodium_per_unit, allergens, status) VALUES
('Grilled Chicken Breast', 'Protein', 'g', 1.65, 0.31, 0, 0.036, 0, 0.074, 'None', 'active'),
('Steamed Broccoli', 'Vegetable', 'g', 0.34, 0.028, 0.07, 0.004, 0.026, 0.033, 'None', 'active'),
('Brown Rice (cooked)', 'Grain', 'g', 1.11, 0.026, 0.23, 0.009, 0.016, 0.005, 'None', 'active'),
('Salmon Fillet', 'Protein', 'g', 2.08, 0.25, 0, 0.12, 0, 0.044, 'Fish', 'active'),
('Quinoa (cooked)', 'Grain', 'g', 1.20, 0.044, 0.22, 0.019, 0.021, 0.007, 'None', 'active'),
('Spinach (raw)', 'Vegetable', 'g', 0.23, 0.029, 0.037, 0.004, 0.022, 0.079, 'None', 'active'),
('Sweet Potato (baked)', 'Vegetable', 'g', 0.90, 0.02, 0.21, 0.002, 0.033, 0.054, 'None', 'active'),
('Greek Yogurt (plain)', 'Dairy', 'g', 0.59, 0.10, 0.036, 0.004, 0, 0.036, 'Milk', 'active'),
('Oatmeal (cooked)', 'Grain', 'g', 0.68, 0.024, 0.12, 0.015, 0.017, 0.004, 'Gluten', 'active'),
('Eggs (whole)', 'Protein', 'piece', 70, 6, 0.6, 5, 0, 70, 'Eggs', 'active'),
('Avocado', 'Fruit', 'g', 1.60, 0.02, 0.089, 0.147, 0.067, 0.007, 'None', 'active'),
('Almonds', 'Nuts', 'g', 5.79, 0.21, 0.22, 0.50, 0.12, 0.001, 'Tree Nuts', 'active'),
('Whole Wheat Bread', 'Grain', 'slice', 80, 4, 13, 1, 2, 150, 'Gluten, Wheat', 'active'),
('Low-Fat Milk', 'Dairy', 'ml', 0.42, 0.034, 0.05, 0.01, 0, 0.044, 'Milk', 'active'),
('Banana', 'Fruit', 'g', 0.89, 0.011, 0.23, 0.003, 0.026, 0.001, 'None', 'active');

-- Insert Diet Plans
INSERT INTO diet_plans (plan_name, category, calorie_range_low, calorie_range_high, allowed_food_groups, macro_carbs, macro_protein, macro_fats, restrictions, description, status) VALUES
('Diabetic Meal Plan Type 2', 'Diabetic', 1500, 2000, 'Whole Grains, Lean Proteins, Non-Starchy Vegetables, Low-Glycemic Fruits, Healthy Fats', 180, 120, 60, 'No added sugar, Limited refined carbs, Portion control', 'Balanced meal plan for Type 2 diabetes management with controlled carbohydrates', 'active'),
('Cardiac Heart-Healthy Plan', 'Cardiac', 1800, 2200, 'Lean Proteins, Whole Grains, Fruits, Vegetables, Nuts, Seeds, Low-Fat Dairy', 200, 100, 50, 'Low sodium (<2000mg), Low saturated fat, No trans fats, Limited cholesterol', 'Heart-healthy diet focusing on omega-3s, fiber, and lean proteins', 'active'),
('Renal Kidney-Friendly Plan', 'Renal', 1600, 2000, 'Low-Potassium Vegetables, Controlled Protein, Limited Phosphorus Foods, Low Sodium Options', 250, 60, 70, 'Low potassium, Low phosphorus, Low sodium, Controlled protein', 'Specialized diet for chronic kidney disease patients', 'active'),
('General Balanced Diet', 'General', 2000, 2500, 'All Food Groups: Proteins, Grains, Vegetables, Fruits, Dairy, Healthy Fats', 275, 150, 75, 'None', 'Standard balanced diet for general patient population', 'active'),
('Low Sodium Plan', 'Low Sodium', 1800, 2200, 'Fresh Vegetables, Lean Meats, Whole Grains, Fruits, Unsalted Nuts', 200, 120, 65, 'No added salt, Low sodium foods only, Avoid processed foods', 'Sodium-restricted diet for hypertension and heart conditions', 'active'),
('Soft/Liquid Diet Plan', 'Soft/Liquid', 1200, 1800, 'Pureed Foods, Smooth Soups, Yogurt, Smoothies, Soft Cooked Vegetables', 180, 80, 50, 'No solid foods, No hard textures, Easy to swallow', 'Modified texture diet for patients with swallowing difficulties', 'active'),
('Diabetic Meal Plan Type 1', 'Diabetic', 1800, 2400, 'Complex Carbs, Lean Proteins, Vegetables, Fruits, Healthy Fats', 220, 130, 70, 'Carb counting required, No added sugar, Regular meal timing', 'Meal plan with carb counting for Type 1 diabetes management', 'active'),
('Weight Management Plan', 'General', 1400, 1800, 'High Protein, Vegetables, Whole Grains, Fruits, Healthy Fats', 150, 120, 55, 'Calorie controlled, Portion control, Limited processed foods', 'Calorie-controlled plan for weight management', 'active');

-- Insert Meal Templates
INSERT INTO meal_templates (meal_name, meal_type, scheduled_time, food_items, portion_sizes, calories, protein, carbs, fats, fiber, sodium, instructions, status) VALUES
('Diabetic Breakfast', 'Breakfast', '7:00 AM - 9:00 AM', 'Oatmeal, Greek Yogurt, Berries, Almonds', '80g oatmeal, 100g yogurt, 50g berries, 15g almonds', 350, 18, 45, 12, 8, 120, 'Serve warm, include protein to stabilize blood sugar', 'active'),
('Heart-Healthy Breakfast', 'Breakfast', '7:00 AM - 9:00 AM', 'Whole Wheat Toast, Avocado, Poached Eggs, Spinach', '2 slices bread, 50g avocado, 2 eggs, 30g spinach', 420, 22, 35, 20, 8, 380, 'Use minimal salt, focus on healthy fats', 'active'),
('Renal-Friendly Breakfast', 'Breakfast', '7:00 AM - 9:00 AM', 'Low-Potassium Cereal, Low-Fat Milk, Apple Slices', '40g cereal, 150ml milk, 80g apple', 280, 8, 55, 4, 5, 180, 'Ensure low potassium and phosphorus content', 'active'),
('Diabetic Lunch', 'Lunch', '12:00 PM - 2:00 PM', 'Grilled Chicken, Steamed Broccoli, Brown Rice, Side Salad', '150g chicken, 100g broccoli, 80g rice, 50g salad', 480, 42, 45, 12, 6, 320, 'Balanced macros, monitor portion sizes', 'active'),
('Cardiac Lunch', 'Lunch', '12:00 PM - 2:00 PM', 'Baked Salmon, Quinoa, Roasted Vegetables, Olive Oil Dressing', '120g salmon, 100g quinoa, 150g vegetables, 10ml olive oil', 520, 38, 48, 18, 7, 280, 'Rich in omega-3s, low sodium preparation', 'active'),
('Renal Lunch', 'Lunch', '12:00 PM - 2:00 PM', 'Lean Turkey, White Rice, Green Beans, Low-Sodium Bread', '100g turkey, 100g rice, 80g beans, 1 slice bread', 450, 35, 52, 8, 4, 250, 'Controlled protein, low potassium vegetables', 'active'),
('Diabetic Dinner', 'Dinner', '6:00 PM - 8:00 PM', 'Baked Chicken, Sweet Potato, Steamed Vegetables, Whole Grain Roll', '140g chicken, 120g sweet potato, 100g vegetables, 1 roll', 510, 40, 50, 14, 9, 380, 'Evening meal with controlled carbs', 'active'),
('Cardiac Dinner', 'Dinner', '6:00 PM - 8:00 PM', 'Grilled Fish, Brown Rice, Steamed Asparagus, Mixed Salad', '130g fish, 90g rice, 100g asparagus, 60g salad', 490, 36, 42, 16, 6, 320, 'Light dinner, heart-healthy preparation', 'active'),
('Renal Dinner', 'Dinner', '6:00 PM - 8:00 PM', 'Egg White Omelet, White Pasta, Low-Potassium Vegetables', '3 egg whites, 80g pasta, 100g vegetables', 380, 28, 48, 6, 5, 220, 'Low protein, low potassium meal', 'active'),
('Healthy Snack', 'Snack', '10:00 AM / 3:00 PM', 'Greek Yogurt with Berries and Nuts', '150g yogurt, 50g berries, 10g almonds', 180, 12, 18, 6, 4, 60, 'Protein-rich snack to maintain energy', 'active'),
('Diabetic Snack', 'Snack', '10:00 AM / 3:00 PM', 'Apple Slices with Peanut Butter', '100g apple, 15g peanut butter', 150, 4, 20, 6, 4, 5, 'Low glycemic snack option', 'active'),
('Cardiac Snack', 'Snack', '10:00 AM / 3:00 PM', 'Mixed Nuts and Dried Fruits', '30g nuts, 20g dried fruits', 200, 6, 18, 12, 3, 2, 'Unsalted nuts, heart-healthy fats', 'active');

-- Insert Patient Diet Assignments (Sample)
INSERT INTO patient_diet_assignments (patient_id, diet_plan_id, assigned_by, approved_by, reason, status, assigned_at, approved_at) VALUES
('P001', 1, 1, 1, 'Medical', 'active', '2024-01-15 08:00:00', '2024-01-15 09:30:00'),
('P002', 2, 2, 2, 'Medical', 'active', '2024-01-16 09:00:00', '2024-01-16 10:15:00'),
('P003', 3, 3, 3, 'Medical', 'active', '2024-01-17 08:30:00', '2024-01-17 09:45:00'),
('P004', 4, 1, 1, 'Preference', 'active', '2024-01-18 10:00:00', '2024-01-18 11:00:00'),
('P005', 1, 2, 2, 'Medical', 'active', '2024-01-19 08:15:00', '2024-01-19 09:30:00'),
('P006', 5, 3, 3, 'Medical', 'active', '2024-01-20 09:30:00', '2024-01-20 10:45:00'),
('P007', 2, 1, 1, 'Compliance', 'active', '2024-01-21 08:45:00', '2024-01-21 10:00:00'),
('P008', 6, 2, 2, 'Medical', 'active', '2024-01-22 10:15:00', '2024-01-22 11:30:00'),
('P009', 1, 3, 3, 'Medical', 'pending', '2024-01-23 09:00:00', NULL),
('P010', 4, 1, NULL, 'Preference', 'pending', '2024-01-24 08:30:00', NULL);

-- Insert Patient Meal Schedules (Sample - linking to assignments)
INSERT INTO patient_meal_schedule (assignment_id, meal_template_id, scheduled_date, scheduled_time, status) VALUES
(1, 1, CURDATE(), '08:00:00', 'scheduled'),
(1, 4, CURDATE(), '13:00:00', 'scheduled'),
(1, 7, CURDATE(), '19:00:00', 'scheduled'),
(1, 11, CURDATE(), '10:30:00', 'scheduled'),
(2, 2, CURDATE(), '08:00:00', 'scheduled'),
(2, 5, CURDATE(), '13:00:00', 'scheduled'),
(2, 8, CURDATE(), '19:00:00', 'scheduled'),
(2, 13, CURDATE(), '15:00:00', 'scheduled'),
(3, 3, CURDATE(), '08:00:00', 'scheduled'),
(3, 6, CURDATE(), '13:00:00', 'scheduled'),
(3, 9, CURDATE(), '19:00:00', 'scheduled');

-- Insert Meal Consumption Logs (Sample)
INSERT INTO meal_consumption_log (patient_id, assignment_id, meal_schedule_id, meal_template_id, consumed_date, consumed_time, food_items_consumed, actual_calories, actual_protein, actual_carbs, actual_fats, consumption_status, notes, logged_by) VALUES
('P001', 1, 1, 1, CURDATE(), '08:15:00', 'Oatmeal, Greek Yogurt, Berries, Almonds', 345, 17.5, 44, 11.5, 'full', 'Patient consumed full meal', 1),
('P001', 1, 4, 4, CURDATE(), '13:20:00', 'Grilled Chicken, Steamed Broccoli, Brown Rice, Side Salad', 475, 41, 43, 11.5, 'full', 'All items consumed', 1),
('P002', 2, 5, 5, CURDATE(), '08:10:00', 'Whole Wheat Toast, Avocado, Poached Eggs, Spinach', 415, 21.5, 34, 19.5, 'full', 'Patient enjoyed breakfast', 2),
('P002', 2, 6, 5, CURDATE(), '13:30:00', 'Baked Salmon, Quinoa, Roasted Vegetables, Olive Oil Dressing', 515, 37.5, 47, 17.5, 'full', 'Complete meal consumed', 2),
('P003', 3, 9, 3, CURDATE(), '08:25:00', 'Low-Potassium Cereal, Low-Fat Milk, Apple Slices', 275, 7.5, 54, 3.5, 'full', 'Breakfast completed', 3),
('P004', 4, NULL, 1, CURDATE(), '08:00:00', 'Oatmeal, Greek Yogurt, Berries', 320, 15, 42, 8, 'partial', 'Patient skipped almonds', 1);

-- Insert Nutrition Compliance (Sample)
INSERT INTO nutrition_compliance (patient_id, assignment_id, report_date, assigned_plan, meals_consumed, meals_prescribed, calorie_intake, target_calories, protein_intake, target_protein, compliance_score, adherence_percentage, notes) VALUES
('P001', 1, CURDATE(), 'Diabetic Meal Plan Type 2', 3, 4, 820, 1750, 58.5, 120, 85.5, 87.5, 'Good compliance, missed one snack'),
('P002', 2, CURDATE(), 'Cardiac Heart-Healthy Plan', 2, 4, 930, 2000, 59, 100, 72.0, 50.0, 'Breakfast and lunch consumed, dinner pending'),
('P003', 3, CURDATE(), 'Renal Kidney-Friendly Plan', 1, 3, 275, 1800, 7.5, 60, 45.0, 33.3, 'Only breakfast consumed, low compliance'),
('P004', 4, CURDATE(), 'General Balanced Diet', 1, 4, 320, 2250, 15, 150, 28.5, 25.0, 'Partial breakfast only, needs follow-up'),
('P005', 5, CURDATE(), 'Diabetic Meal Plan Type 2', 4, 4, 1650, 1750, 115, 120, 94.5, 100.0, 'Excellent compliance, all meals consumed'),
('P006', 6, CURDATE(), 'Low Sodium Plan', 3, 4, 1420, 2000, 85, 120, 71.0, 75.0, 'Good adherence, missed afternoon snack'),
('P007', 7, CURDATE(), 'Cardiac Heart-Healthy Plan', 4, 4, 1980, 2000, 98, 100, 99.0, 100.0, 'Perfect compliance, all targets met'),
('P008', 8, CURDATE(), 'Soft/Liquid Diet Plan', 2, 4, 680, 1500, 35, 80, 68.0, 50.0, 'Partial consumption, texture issues noted');

-- Insert Nutrition Analytics (Sample)
INSERT INTO nutrition_analytics (report_date, metric_name, metric_value, metric_unit, category, diet_plan_category, notes) VALUES
(CURDATE(), 'Average Compliance Score', 70.1, 'percentage', 'Overall', 'All', 'System-wide average compliance'),
(CURDATE(), 'Total Active Assignments', 8, 'count', 'Overall', 'All', 'Active diet plan assignments'),
(CURDATE(), 'Diabetic Plan Compliance', 85.5, 'percentage', 'Compliance', 'Diabetic', 'Average compliance for diabetic patients'),
(CURDATE(), 'Cardiac Plan Compliance', 85.5, 'percentage', 'Compliance', 'Cardiac', 'Average compliance for cardiac patients'),
(CURDATE(), 'Renal Plan Compliance', 45.0, 'percentage', 'Compliance', 'Renal', 'Lower compliance in renal patients'),
(CURDATE(), 'Meals Served Today', 18, 'count', 'Operations', 'All', 'Total meals served across all patients'),
(CURDATE(), 'Average Calorie Intake', 1232, 'calories', 'Nutrition', 'All', 'Average daily calorie consumption'),
(CURDATE(), 'High Compliance Patients', 2, 'count', 'Compliance', 'All', 'Patients with compliance ≥90%');

-- ============================================
-- END OF SCRIPT
-- ============================================

