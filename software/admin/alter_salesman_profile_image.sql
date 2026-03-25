-- Run once: stores salesman profile photo filename (file lives under software/salesman_profile_images/).
ALTER TABLE `salesman` ADD COLUMN `profile_image` VARCHAR(255) NULL DEFAULT NULL;
