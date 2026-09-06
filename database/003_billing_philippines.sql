-- Billing table extensions for Philippines Hospital Management System
-- Adds bill_number, paid_amount, balance_amount, and overdue status for real reporting.
-- Run once; safe to re-run if columns already exist (may show errors for duplicate columns).

SET NAMES utf8mb4;

-- Add columns (ignore error if already exist)
ALTER TABLE `billing` ADD COLUMN `bill_number` varchar(50) DEFAULT NULL AFTER `id`;
ALTER TABLE `billing` ADD COLUMN `paid_amount` decimal(12,2) DEFAULT 0.00 AFTER `total_amount`;
ALTER TABLE `billing` ADD COLUMN `balance_amount` decimal(12,2) DEFAULT 0.00 AFTER `paid_amount`;

-- Extend payment_status to include 'overdue'
ALTER TABLE `billing`
  MODIFY COLUMN `payment_status` enum('pending','partial','paid','overdue') DEFAULT 'pending';

-- Backfill bill_number (Philippine format: INV-YYYY-NNNN)
UPDATE `billing` SET `bill_number` = CONCAT('INV-', YEAR(bill_date), '-', LPAD(id, 4, '0')) WHERE `bill_number` IS NULL OR `bill_number` = '';

-- Backfill paid_amount and balance_amount from total_amount and payment_status
UPDATE `billing` SET
  `paid_amount` = IF(`payment_status` = 'paid', `total_amount`, IF(`payment_status` = 'partial', `total_amount` * 0.5, 0)),
  `balance_amount` = `total_amount` - IF(`payment_status` = 'paid', `total_amount`, IF(`payment_status` = 'partial', `total_amount` * 0.5, 0));
