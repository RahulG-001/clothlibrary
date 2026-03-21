-- Run once on the database used by `software/admin/db.php`.
-- Adds customer profile fields for salesman-created users.

ALTER TABLE `users` ADD COLUMN `name` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `address` TEXT NULL;
ALTER TABLE `users` ADD COLUMN `visiting_card` VARCHAR(255) NULL DEFAULT NULL;
