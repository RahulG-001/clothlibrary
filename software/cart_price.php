<?php
/**
 * Cart / order line pricing: `sales_order_item.price` = line total (as entered by salesman).
 * Not multiplied by meters. `line_total` in JSON equals that stored amount.
 */

function sales_order_item_has_price_column($con) {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $r = mysqli_query($con, "SHOW COLUMNS FROM `sales_order_item` LIKE 'price'");
    $cached = ($r && mysqli_num_rows($r) > 0);
    return $cached;
}

function indiadata_has_catalog_price_column($con) {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $r = mysqli_query($con, "SHOW COLUMNS FROM `indiadata` LIKE 'price'");
    $cached = ($r && mysqli_num_rows($r) > 0);
    return $cached;
}

/**
 * Parse price from request (line total amount).
 *
 * @param mixed $raw
 * @return float|null
 */
function cart_parse_line_price($raw) {
    if ($raw === null || $raw === '') {
        return null;
    }
    if (is_string($raw)) {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
    }
    $p = (float)$raw;
    if (!is_finite($p) || $p < 0) {
        return null;
    }
    if ($p > 99999999.9999) {
        $p = 99999999.9999;
    }
    return round($p, 4);
}

/** @deprecated alias */
function cart_parse_unit_price($raw) {
    return cart_parse_line_price($raw);
}

/**
 * @param mixed $v DB value
 * @return float|null
 */
function cart_normalize_stored_price($v) {
    if ($v === null || $v === '') {
        return null;
    }
    $p = (float)$v;
    if (!is_finite($p) || $p < 0) {
        return null;
    }
    return round($p, 4);
}

/**
 * Line total for API = stored line price (no meter multiplication).
 *
 * @param float|null $storedLinePrice
 * @return float|null
 */
function cart_line_total($storedLinePrice) {
    if ($storedLinePrice === null) {
        return null;
    }
    return round((float)$storedLinePrice, 2);
}

/**
 * Merging same itemcode: add new segment's price to existing line total.
 *
 * @param float|null $oldLinePrice
 * @param float|null $addSegmentPrice
 * @return float|null
 */
function cart_blend_line_price($oldLinePrice, $addSegmentPrice) {
    $o = cart_normalize_stored_price($oldLinePrice);
    $n = $addSegmentPrice;
    if ($n === null) {
        return $o;
    }
    if ($o === null) {
        return round($n, 2);
    }
    return round($o + $n, 2);
}

/**
 * @param array $row  Must have total_meters (or caller sets before)
 * @param bool $hasPriceCol
 */
function cart_attach_line_amounts(&$row, $hasPriceCol) {
    if ($hasPriceCol && array_key_exists('line_unit_price', $row)) {
        $stored = cart_normalize_stored_price($row['line_unit_price']);
        unset($row['line_unit_price']);
    } else {
        $stored = null;
    }
    $row['price'] = $stored;
    $row['line_total'] = cart_line_total($stored);
}

/**
 * @param array $products list of rows already with price + line_total
 * @return float
 */
function cart_sum_line_totals($products) {
    $sum = 0.0;
    foreach ($products as $p) {
        if (isset($p['line_total']) && $p['line_total'] !== null) {
            $sum += (float)$p['line_total'];
        }
    }
    return round($sum, 2);
}
