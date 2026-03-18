-- Run once on existing DB: add meters column for cart (required meters per line)
ALTER TABLE `sales_order_item`
ADD COLUMN `meters` DECIMAL(10,2) NOT NULL DEFAULT 1.00 COMMENT 'Meters per unit; total stock deduct = quantity * meters' AFTER `quantity`;
