<?php
/**
 * Indiadata `type`: M = stock quantity is meters; PCS (and aliases) = stock is piece count.
 */

function product_stock_type_is_pcs($type) {
    $t = strtoupper(trim(preg_replace('/\s+/', '', (string)$type)));
    return $t === 'PCS' || $t === 'PC' || $t === 'PIECE' || $t === 'PIECES' || $t === 'PC.';
}

function product_stock_type_is_meters($type) {
    if ($type === null || trim((string)$type) === '') {
        return true;
    }
    if (product_stock_type_is_pcs($type)) {
        return false;
    }
    $t = strtoupper(trim(preg_replace('/\s+/', '', (string)$type)));
    return $t === 'M' || $t === 'MTR' || $t === 'METER' || $t === 'METERS' || $t === 'MTRS';
}
