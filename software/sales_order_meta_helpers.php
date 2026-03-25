<?php

function sales_order_meta_table_exists($con) {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $r = mysqli_query($con, "SHOW TABLES LIKE 'sales_order_meta'");
    $cached = ($r && mysqli_num_rows($r) > 0);
    return $cached;
}

function sales_order_meta_clean_text($value, $maxLen) {
    $s = trim((string)$value);
    if ($maxLen > 0 && strlen($s) > $maxLen) {
        $s = substr($s, 0, $maxLen);
    }
    return $s;
}

function sales_order_meta_pick($input, $keys, $maxLen) {
    foreach ($keys as $k) {
        if (isset($input[$k])) {
            return sales_order_meta_clean_text($input[$k], $maxLen);
        }
    }
    return '';
}

function sales_order_meta_from_input($input) {
    return [
        'packing' => sales_order_meta_pick($input, ['packing', 'imballaggio'], 255),
        'conditions_text' => sales_order_meta_pick($input, ['conditions', 'terms', 'conditions_terms'], 4000),
        'delivery_to' => sales_order_meta_pick($input, ['delivery_to', 'delivery_address', 'consegnare_a'], 4000),
        'dispatch_method' => sales_order_meta_pick($input, ['method_of_dispatch', 'dispatch_method', 'shipping_method'], 255),
        'delivery_date' => sales_order_meta_pick($input, ['delivery_date', 'date_of_delivery'], 100),
        'patterns_labels' => sales_order_meta_pick($input, ['patterns_labels', 'patterns', 'modelli_labels'], 4000),
        'delivery_terms' => sales_order_meta_pick($input, ['delivery_terms', 'terms_of_delivery'], 4000),
    ];
}

function sales_order_meta_has_any($meta) {
    foreach ($meta as $v) {
        if (trim((string)$v) !== '') {
            return true;
        }
    }
    return false;
}

function sales_order_meta_upsert($con, $orderId, $meta) {
    $orderId = (int)$orderId;
    $packing = mysqli_real_escape_string($con, (string)$meta['packing']);
    $conditions = mysqli_real_escape_string($con, (string)$meta['conditions_text']);
    $deliveryTo = mysqli_real_escape_string($con, (string)$meta['delivery_to']);
    $dispatchMethod = mysqli_real_escape_string($con, (string)$meta['dispatch_method']);
    $deliveryDate = mysqli_real_escape_string($con, (string)$meta['delivery_date']);
    $patterns = mysqli_real_escape_string($con, (string)$meta['patterns_labels']);
    $deliveryTerms = mysqli_real_escape_string($con, (string)$meta['delivery_terms']);

    $sql = "INSERT INTO sales_order_meta
              (order_id, packing, conditions_text, delivery_to, dispatch_method, delivery_date, patterns_labels, delivery_terms)
            VALUES
              ('".$orderId."', '".$packing."', '".$conditions."', '".$deliveryTo."', '".$dispatchMethod."', '".$deliveryDate."', '".$patterns."', '".$deliveryTerms."')
            ON DUPLICATE KEY UPDATE
              packing = VALUES(packing),
              conditions_text = VALUES(conditions_text),
              delivery_to = VALUES(delivery_to),
              dispatch_method = VALUES(dispatch_method),
              delivery_date = VALUES(delivery_date),
              patterns_labels = VALUES(patterns_labels),
              delivery_terms = VALUES(delivery_terms)";
    return mysqli_query($con, $sql);
}

function sales_order_meta_fetch($con, $orderId) {
    $out = [
        'packing' => '',
        'conditions_text' => '',
        'delivery_to' => '',
        'dispatch_method' => '',
        'delivery_date' => '',
        'patterns_labels' => '',
        'delivery_terms' => '',
    ];
    if (!sales_order_meta_table_exists($con)) {
        return $out;
    }
    $r = mysqli_query(
        $con,
        "SELECT packing, conditions_text, delivery_to, dispatch_method, delivery_date, patterns_labels, delivery_terms
         FROM sales_order_meta
         WHERE order_id = '".(int)$orderId."'
         LIMIT 1"
    );
    if ($r && mysqli_num_rows($r) === 1) {
        $row = mysqli_fetch_assoc($r);
        foreach ($out as $k => $v) {
            if (isset($row[$k])) {
                $out[$k] = trim((string)$row[$k]);
            }
        }
    }
    return $out;
}

