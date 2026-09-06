-- Seed real nutrition compliance data for Nutrition Compliance Reports page.
-- Report date range: Feb 1-6, 2026 (so default filter "this month" shows data).
-- Uses existing patient_diet_assignments (ids 1-8). Compliance scores spread across chart buckets: 0-40%, 41-60%, 61-80%, 81-100%.

-- Remove any existing compliance rows for this date range so re-run replaces them
DELETE FROM nutrition_compliance WHERE report_date BETWEEN '2026-02-01' AND '2026-02-06';

INSERT INTO nutrition_compliance (patient_id, assignment_id, report_date, assigned_plan, meals_consumed, meals_prescribed, calorie_intake, target_calories, protein_intake, target_protein, compliance_score, adherence_percentage, notes) VALUES
-- Feb 1, 2026 – spread across buckets for chart
('P001', 1, '2026-02-01', 'Diabetic Meal Plan Type 2', 4, 4, 1680, 1750, 118, 120, 96.0, 100.0, 'All meals consumed, targets met'),
('P002', 2, '2026-02-01', 'Cardiac Heart-Healthy Plan', 3, 4, 1520, 2000, 78, 100, 76.0, 75.0, 'Missed evening snack'),
('P003', 3, '2026-02-01', 'Renal Kidney-Friendly Plan', 1, 3, 280, 1800, 8, 60, 38.0, 33.3, 'Breakfast only, low adherence'),
('P004', 4, '2026-02-01', 'General Balanced Diet', 1, 4, 340, 2250, 16, 150, 32.0, 25.0, 'Partial intake, needs follow-up'),
('P005', 5, '2026-02-01', 'Diabetic Meal Plan Type 2', 4, 4, 1720, 1750, 122, 120, 98.5, 100.0, 'Excellent compliance'),
('P006', 6, '2026-02-01', 'Low Sodium Plan', 2, 4, 980, 2000, 58, 120, 52.0, 50.0, 'Lunch and dinner only'),
('P007', 7, '2026-02-01', 'Cardiac Heart-Healthy Plan', 4, 4, 1990, 2000, 99, 100, 99.5, 100.0, 'All targets met'),
('P008', 8, '2026-02-01', 'Soft/Liquid Diet Plan', 2, 4, 720, 1500, 38, 80, 65.0, 50.0, 'Two meals taken, texture tolerated'),
-- Feb 2, 2026
('P001', 1, '2026-02-02', 'Diabetic Meal Plan Type 2', 3, 4, 1240, 1750, 82, 120, 82.0, 75.0, 'Missed afternoon snack'),
('P002', 2, '2026-02-02', 'Cardiac Heart-Healthy Plan', 4, 4, 2010, 2000, 101, 100, 100.0, 100.0, 'Full compliance'),
('P003', 3, '2026-02-02', 'Renal Kidney-Friendly Plan', 2, 3, 520, 1800, 22, 60, 52.0, 66.7, 'Breakfast and lunch'),
('P004', 4, '2026-02-02', 'General Balanced Diet', 3, 4, 1580, 2250, 88, 150, 72.0, 75.0, 'Improved from yesterday'),
('P005', 5, '2026-02-02', 'Diabetic Meal Plan Type 2', 4, 4, 1700, 1750, 119, 120, 97.0, 100.0, 'Stable compliance'),
('P006', 6, '2026-02-02', 'Low Sodium Plan', 3, 4, 1380, 2000, 82, 120, 69.0, 75.0, 'Good adherence'),
('P007', 7, '2026-02-02', 'Cardiac Heart-Healthy Plan', 3, 4, 1540, 2000, 76, 100, 77.0, 75.0, 'Dinner skipped'),
('P008', 8, '2026-02-02', 'Soft/Liquid Diet Plan', 3, 4, 1100, 1500, 58, 80, 78.0, 75.0, 'Good intake'),
-- Feb 3–5 (sample so table has multiple days)
('P001', 1, '2026-02-03', 'Diabetic Meal Plan Type 2', 4, 4, 1690, 1750, 116, 120, 95.0, 100.0, NULL),
('P002', 2, '2026-02-03', 'Cardiac Heart-Healthy Plan', 2, 4, 920, 2000, 48, 100, 58.0, 50.0, NULL),
('P003', 3, '2026-02-03', 'Renal Kidney-Friendly Plan', 2, 3, 540, 1800, 20, 60, 48.0, 66.7, NULL),
('P004', 4, '2026-02-03', 'General Balanced Diet', 2, 4, 890, 2250, 48, 150, 48.0, 50.0, NULL),
('P005', 5, '2026-02-03', 'Diabetic Meal Plan Type 2', 4, 4, 1710, 1750, 120, 120, 98.0, 100.0, NULL),
('P006', 6, '2026-02-03', 'Low Sodium Plan', 4, 4, 1950, 2000, 118, 120, 97.5, 100.0, NULL),
('P007', 7, '2026-02-03', 'Cardiac Heart-Healthy Plan', 4, 4, 1980, 2000, 98, 100, 99.0, 100.0, NULL),
('P008', 8, '2026-02-03', 'Soft/Liquid Diet Plan', 1, 4, 360, 1500, 18, 80, 42.0, 25.0, NULL),
('P001', 1, '2026-02-05', 'Diabetic Meal Plan Type 2', 3, 4, 1320, 1750, 90, 120, 84.0, 75.0, NULL),
('P002', 2, '2026-02-05', 'Cardiac Heart-Healthy Plan', 4, 4, 2000, 2000, 100, 100, 100.0, 100.0, NULL),
('P003', 3, '2026-02-05', 'Renal Kidney-Friendly Plan', 3, 3, 1520, 1800, 52, 60, 88.0, 100.0, NULL),
('P004', 4, '2026-02-05', 'General Balanced Diet', 4, 4, 2180, 2250, 142, 150, 92.0, 100.0, NULL),
('P005', 5, '2026-02-05', 'Diabetic Meal Plan Type 2', 2, 4, 780, 1750, 52, 120, 58.0, 50.0, NULL),
('P006', 6, '2026-02-05', 'Low Sodium Plan', 3, 4, 1450, 2000, 86, 120, 72.5, 75.0, NULL),
('P007', 7, '2026-02-05', 'Cardiac Heart-Healthy Plan', 3, 4, 1480, 2000, 74, 100, 74.0, 75.0, NULL),
('P008', 8, '2026-02-05', 'Soft/Liquid Diet Plan', 2, 4, 680, 1500, 36, 80, 62.0, 50.0, NULL)
;
