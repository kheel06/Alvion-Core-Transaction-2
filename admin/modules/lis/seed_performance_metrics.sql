-- Seed performance_metrics for Feb 2026 so Lab Performance Reports show real data
-- Run via: php run_seed_performance.php

INSERT INTO performance_metrics (report_date, department, total_tests, tat_compliance_percent, rejection_rate) VALUES
('2026-02-01', 'Hematology', 44, 95.2, 2.6),
('2026-02-01', 'Chemistry', 76, 92.1, 3.2),
('2026-02-01', 'Microbiology', 22, 89.0, 4.0),
('2026-02-02', 'Hematology', 48, 96.0, 2.2),
('2026-02-02', 'Chemistry', 80, 93.5, 2.8),
('2026-02-02', 'Microbiology', 25, 88.5, 4.1),
('2026-02-03', 'Hematology', 42, 94.5, 2.9),
('2026-02-03', 'Chemistry', 74, 91.8, 3.4),
('2026-02-03', 'Microbiology', 24, 87.2, 4.3),
('2026-02-04', 'Hematology', 50, 95.8, 2.4),
('2026-02-04', 'Chemistry', 82, 92.9, 3.0),
('2026-02-04', 'Microbiology', 26, 88.0, 3.9),
('2026-02-05', 'Hematology', 46, 94.2, 2.7),
('2026-02-05', 'Chemistry', 79, 91.2, 3.6),
('2026-02-05', 'Microbiology', 23, 89.1, 3.8),
('2026-02-06', 'Hematology', 47, 96.2, 2.3),
('2026-02-06', 'Chemistry', 81, 93.1, 2.9),
('2026-02-06', 'Microbiology', 24, 87.5, 4.2);
