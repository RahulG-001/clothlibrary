-- Run if `sales_order_item` has no `price` column (older installs).
-- Standard schema already includes `price` — see create_sales_order_tables.sql.

ALTER TABLE `sales_order_item` ADD COLUMN `price` DECIMAL(12,2) NULL DEFAULT NULL;
