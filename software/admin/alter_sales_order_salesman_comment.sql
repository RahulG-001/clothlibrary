-- Run once: stores salesman notes when placing an order (see place_order_api.php).

ALTER TABLE `sales_order` ADD COLUMN `salesman_comment` TEXT NULL DEFAULT NULL;
