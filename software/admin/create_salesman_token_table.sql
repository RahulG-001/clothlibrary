-- Run this in your database (cucinello)

CREATE TABLE IF NOT EXISTS `salesman_token` (
  `token` VARCHAR(128) NOT NULL,
  `salesman_id` INT(11) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`token`),
  KEY `salesman_id` (`salesman_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

