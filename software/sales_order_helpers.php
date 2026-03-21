<?php
/**
 * @param mysqli $con
 * @return bool
 */
function sales_order_has_salesman_comment_column($con) {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $r = mysqli_query($con, "SHOW COLUMNS FROM `sales_order` LIKE 'salesman_comment'");
    $cached = ($r && mysqli_num_rows($r) > 0);
    return $cached;
}
