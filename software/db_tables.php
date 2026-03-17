<?php
/**
 * Helper to handle table name differences between environments.
 * Some servers treat table names as case-sensitive (Linux), so indiaData != indiadata.
 */

function get_india_data_table($con) {
    $r1 = mysqli_query($con, "SHOW TABLES LIKE 'indiaData'");
    if ($r1 && mysqli_num_rows($r1) > 0) {
        return 'indiaData';
    }
    $r2 = mysqli_query($con, "SHOW TABLES LIKE 'indiadata'");
    if ($r2 && mysqli_num_rows($r2) > 0) {
        return 'indiadata';
    }
    return null;
}

