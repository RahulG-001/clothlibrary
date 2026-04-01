<?php
/**
 * Default timezone for this project (India Standard Time).
 * Loaded from admin/db.php so PHP date/time uses IST.
 * admin/db.php also runs SET time_zone = '+05:30' on the MySQL connection so
 * created_at / updated_at defaults (CURRENT_TIMESTAMP) match the same instant.
 */
date_default_timezone_set('Asia/Kolkata');
