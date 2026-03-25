-- Run once: additional bill/order fields captured at order placement.
CREATE TABLE IF NOT EXISTS `sales_order_meta` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `packing` VARCHAR(255) NULL DEFAULT NULL,
  `conditions_text` TEXT NULL,
  `delivery_to` TEXT NULL,
  `dispatch_method` VARCHAR(255) NULL DEFAULT NULL,
  `delivery_date` VARCHAR(100) NULL DEFAULT NULL,
  `patterns_labels` TEXT NULL,
  `delivery_terms` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_order_id` (`order_id`),
  CONSTRAINT `fk_sales_order_meta_order` FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

