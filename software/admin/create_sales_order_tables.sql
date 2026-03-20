-- Run this in your database (cucinello) to create tables for salesman cart/orders.

CREATE TABLE IF NOT EXISTS `sales_order` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(100) NOT NULL COMMENT 'users.userid',
  `salesman_id` INT(11) NOT NULL COMMENT 'salesman.id',
  `status` ENUM('cart','placed') NOT NULL DEFAULT 'cart',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_salesman_status` (`user_id`,`salesman_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `sales_order_item` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `order_id` INT(11) NOT NULL,
  `itemcode` VARCHAR(50) NOT NULL COMMENT 'indiadata.itemcode',
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `meters` DECIMAL(10,2) NOT NULL DEFAULT 1.00 COMMENT 'Meters per line; total deduct = quantity * meters',
  `price` DECIMAL(12,2) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `sales_order` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- If sales_order_item already exists with INT quantity, run:
-- ALTER TABLE sales_order_item MODIFY quantity DECIMAL(10,2) NOT NULL DEFAULT 1.00;
