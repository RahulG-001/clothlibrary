<?php
/**
 * Parse quantity strings from DB (e.g. "3.50+1.50", "24.40 MTRS", "NILL", "19.10+3.90=23.00 mtrs+ 2 mtrs-ww")
 * and convert to numeric total. Format numeric back for saving.
 */

/**
 * Parse DB quantity string to a single numeric total (float).
 * Handles: decimals, sums (3.50+1.50), with units (mtrs, MTRS, mtrs-ww, WW), NILL, and calculated (7.90+1.00=8.90).
 *
 * @param string|null $quantityStr
 * @return float
 */
function parse_quantity_to_number($quantityStr) {
    if ($quantityStr === null || trim((string)$quantityStr) === '') {
        return 0.0;
    }
    $s = trim((string)$quantityStr);
    if (preg_match('/^NILL$/i', $s)) {
        return 0.0;
    }
    // Replace "number+number=result" with "result" so we don't double-count
    $s = preg_replace('/[\d.]+\s*\+\s*[\d.]+\s*=\s*([\d.]+)/', '$1', $s);
    $s = preg_replace('/[\d.]+\s*\+\s*[\d.]+\s*\+\s*[\d.]+\s*=\s*([\d.]+)/', '$1', $s);
    // Extract all numbers (including decimals) and sum
    if (!preg_match_all('/[\d.]+/', $s, $m)) {
        return 0.0;
    }
    $total = 0.0;
    foreach ($m[0] as $num) {
        $total += (float)$num;
    }
    return round($total, 2);
}

/**
 * Format a numeric quantity for saving back to DB (simple "X.XX" format).
 *
 * @param float|int $num
 * @return string
 */
function format_quantity_for_db($num) {
    $n = (float)$num;
    if ($n < 0) {
        $n = 0;
    }
    return number_format($n, 2, '.', '');
}
