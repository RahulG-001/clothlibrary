<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require('admin/db.php');
require_once('quantity_parser.php');
require_once('salesman_auth.php');
require_once('cart_price.php');
require_once('product_stock_helpers.php');

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

$authSalesman = salesman_require_auth($con);
$salesman_id = (int)$authSalesman['id'];

$input = [];
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = [];
    }
} else {
    $input = $_POST;
}

$order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
$rawItems = isset($input['items']) ? $input['items'] : null;

if ($salesman_id <= 0 || $order_id <= 0 || !is_array($rawItems) || empty($rawItems)) {
    $response['message'] = 'Required: order_id and items[]. Each item needs line_id, plus either delete=true or new quantity fields.';
    echo json_encode($response);
    exit;
}

$metersColCheck = mysqli_query($con, "SHOW COLUMNS FROM sales_order_item LIKE 'meters'");
$hasMetersColumn = $metersColCheck && mysqli_num_rows($metersColCheck) > 0;
$hasPriceColumn  = sales_order_item_has_price_column($con);

// Ensure order belongs to this salesman and is already placed
$orderRes = mysqli_query(
    $con,
    "SELECT id AS order_id, user_id, salesman_id, status, created_at, updated_at
     FROM sales_order
     WHERE id = '".$order_id."'
       AND salesman_id = '".$salesman_id."'
     LIMIT 1"
);
if (!$orderRes || mysqli_num_rows($orderRes) === 0) {
    $response['message'] = 'Order not found.';
    echo json_encode($response);
    exit;
}
$order = mysqli_fetch_assoc($orderRes);
if (strtolower(trim((string)$order['status'])) !== 'placed') {
    $response['message'] = 'Only placed orders can be edited/deleted.';
    echo json_encode($response);
    exit;
}

// Normalize items and collect line_ids to validate existence up front
$mods = [];
$lineIds = [];
foreach ($rawItems as $it) {
    if (!is_array($it)) {
        continue;
    }
    $line_id = isset($it['line_id']) ? (int)$it['line_id'] : 0;
    $delete  = isset($it['delete']) ? (bool)$it['delete'] : false;
    if ($line_id <= 0) {
        continue;
    }
    if (!isset($mods[$line_id])) {
        $mods[$line_id] = $it;
        $lineIds[] = $line_id;
    }
}

if (empty($mods)) {
    $response['message'] = 'No valid line_id updates found.';
    echo json_encode($response);
    exit;
}

$lineIdSql = implode(',', array_map('intval', $lineIds));
$existingRes = mysqli_query(
    $con,
    "SELECT oi.id AS line_id, oi.itemcode,
            COALESCE(oi.quantity,0) AS line_qty,
            COALESCE(oi.meters,0)   AS line_meters,
            p.type AS product_type
     FROM sales_order_item oi
     LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
     WHERE oi.order_id = '".$order_id."'
       AND oi.id IN (".$lineIdSql.")"
);
if (!$existingRes || mysqli_num_rows($existingRes) === 0) {
    $response['message'] = 'No matching order lines found for provided line_id(s).';
    echo json_encode($response);
    exit;
}

$existingByLineId = [];
while ($r = mysqli_fetch_assoc($existingRes)) {
    $lid = (int)$r['line_id'];
    $existingByLineId[$lid] = $r;
}

// Validate that all requested lines exist in this order
foreach (array_keys($mods) as $lid) {
    if (!isset($existingByLineId[$lid])) {
        $response['message'] = 'One or more line_id(s) are not part of this order.';
        echo json_encode($response);
        exit;
    }
}

mysqli_begin_transaction($con);
$order_deleted = false;
try {
    // 1) Restore stock for the entire order first (reverse previous deduction)
    $restoreRes = mysqli_query(
        $con,
        "SELECT oi.itemcode,
                COALESCE(oi.quantity,0) AS line_qty,
                COALESCE(oi.meters,0)   AS line_meters,
                p.type AS product_type
         FROM sales_order_item oi
         INNER JOIN indiadata p ON p.itemcode = oi.itemcode
         WHERE oi.order_id = '".$order_id."'"
    );
    if (!$restoreRes) {
        throw new Exception('Failed to read order lines for stock restore.');
    }

    $restoreNeedByItemcode = [];
    while ($rr = mysqli_fetch_assoc($restoreRes)) {
        $ic = trim((string)$rr['itemcode']);
        if (!isset($restoreNeedByItemcode[$ic])) {
            $restoreNeedByItemcode[$ic] = ['m' => 0.0, 'pcs' => 0.0, 'product_type' => isset($rr['product_type']) ? $rr['product_type'] : ''];
        }
        $ptype = isset($rr['product_type']) ? $rr['product_type'] : '';
        if (product_stock_type_is_pcs($ptype)) {
            $restoreNeedByItemcode[$ic]['pcs'] += (float)$rr['line_qty'];
        } else {
            $restoreNeedByItemcode[$ic]['m'] += (float)$rr['line_meters'];
        }
    }

    foreach ($restoreNeedByItemcode as $ic => $need) {
        $icEsc = mysqli_real_escape_string($con, $ic);
        $availRes = mysqli_query($con, "SELECT quantity FROM indiadata WHERE itemcode = '".$icEsc."' LIMIT 1");
        if (!$availRes || mysqli_num_rows($availRes) === 0) {
            throw new Exception('Product not found for itemcode: '.$ic);
        }
        $availRow = mysqli_fetch_assoc($availRes);
        $curAvail = parse_quantity_to_number(isset($availRow['quantity']) ? $availRow['quantity'] : '');
        $addBack = product_stock_type_is_pcs($need['product_type']) ? (float)$need['pcs'] : (float)$need['m'];
        $newAvail = $curAvail + $addBack;
        $newAvailEsc = mysqli_real_escape_string($con, format_quantity_for_db($newAvail));
        $up = mysqli_query($con, "UPDATE indiadata SET quantity = '".$newAvailEsc."' WHERE itemcode = '".$icEsc."'");
        if (!$up) {
            throw new Exception('Failed to restore stock for itemcode: '.$ic);
        }
    }

    // 2) Apply edits/deletions to order items
    foreach ($mods as $line_id => $mod) {
        $line_id = (int)$line_id;
        $existing = $existingByLineId[$line_id];

        $delete = isset($mod['delete']) ? (bool)$mod['delete'] : false;
        if ($delete) {
            $delRes = mysqli_query(
                $con,
                "DELETE FROM sales_order_item
                 WHERE id = '".$line_id."'
                   AND order_id = '".$order_id."'"
            );
            if (!$delRes) {
                throw new Exception('Failed to delete order line '.$line_id);
            }
            continue;
        }

        $ptype = isset($existing['product_type']) ? $existing['product_type'] : '';

        // Parse optional new price
        $setPriceSql = '';
        if ($hasPriceColumn && (array_key_exists('price', $mod) || array_key_exists('line_price', $mod) || array_key_exists('unit_price', $mod))) {
            $rawPrice = null;
            if (array_key_exists('price', $mod)) {
                $rawPrice = $mod['price'];
            } elseif (array_key_exists('line_price', $mod)) {
                $rawPrice = $mod['line_price'];
            } else {
                $rawPrice = $mod['unit_price'];
            }
            $parsedPrice = cart_parse_line_price($rawPrice);
            $priceValSql = ($parsedPrice === null) ? 'NULL' : "'".mysqli_real_escape_string($con, number_format($parsedPrice, 4, '.', ''))."'";
            $setPriceSql = ", price = ".$priceValSql;
        }

        if (product_stock_type_is_pcs($ptype)) {
            $piecesIn = 0.0;
            if (isset($mod['pieces'])) {
                $piecesIn = (float)$mod['pieces'];
            } elseif (isset($mod['quantity'])) {
                $piecesIn = (float)$mod['quantity'];
            } elseif (isset($mod['PCS'])) {
                $piecesIn = (float)$mod['PCS'];
            } elseif (isset($mod['pcs'])) {
                $piecesIn = (float)$mod['pcs'];
            } elseif (isset($mod['order_qty'])) {
                $piecesIn = (float)$mod['order_qty'];
            }
            if ($piecesIn <= 0) {
                throw new Exception('PCS update requires pieces/quantity/PCS > 0 for line_id '.$line_id);
            }
            $newQ = mysqli_real_escape_string($con, number_format($piecesIn, 2, '.', ''));
            if ($hasMetersColumn) {
                $metersZero = mysqli_real_escape_string($con, '0.00');
                $sql = "UPDATE sales_order_item
                        SET quantity = '".$newQ."', meters = '".$metersZero."'".$setPriceSql."
                        WHERE id = '".$line_id."' AND order_id = '".$order_id."'";
            } else {
                $sql = "UPDATE sales_order_item
                        SET quantity = '".$newQ."'".$setPriceSql."
                        WHERE id = '".$line_id."' AND order_id = '".$order_id."'";
            }
        } else {
            $metersIn = 0.0;
            if (isset($mod['meters'])) {
                $metersIn = (float)$mod['meters'];
            } elseif (isset($mod['meter'])) {
                $metersIn = (float)$mod['meter'];
            } elseif (isset($mod['order_total_meters'])) {
                $metersIn = (float)$mod['order_total_meters'];
            }
            if ($metersIn <= 0) {
                throw new Exception('M update requires meters > 0 for line_id '.$line_id);
            }
            $newM = mysqli_real_escape_string($con, number_format($metersIn, 2, '.', ''));
            $qtyOne = mysqli_real_escape_string($con, '1');
            if ($hasMetersColumn) {
                $sql = "UPDATE sales_order_item
                        SET quantity = '".$qtyOne."', meters = '".$newM."'".$setPriceSql."
                        WHERE id = '".$line_id."' AND order_id = '".$order_id."'";
            } else {
                // Fallback (meters column missing). In your current system meters column should exist.
                $sql = "UPDATE sales_order_item
                        SET quantity = '".$newM."'".$setPriceSql."
                        WHERE id = '".$line_id."' AND order_id = '".$order_id."'";
            }
        }

        $upRes = mysqli_query($con, $sql);
        if (!$upRes) {
            throw new Exception('Failed to update order line '.$line_id);
        }
    }

    $remainingRes = mysqli_query(
        $con,
        "SELECT COUNT(*) AS c FROM sales_order_item WHERE order_id = '".$order_id."'"
    );
    if (!$remainingRes) {
        throw new Exception('Failed to count remaining order lines.');
    }
    $remainingRow = mysqli_fetch_assoc($remainingRes);
    $remainingCount = isset($remainingRow['c']) ? (int)$remainingRow['c'] : 0;
    if ($remainingCount === 0) {
        $delOrderRes = mysqli_query(
            $con,
            "DELETE FROM sales_order
             WHERE id = '".$order_id."'
               AND salesman_id = '".$salesman_id."'
             LIMIT 1"
        );
        if (!$delOrderRes || mysqli_affected_rows($con) !== 1) {
            throw new Exception('Failed to delete order after removing all lines.');
        }
        $order_deleted = true;
    }

    // 3) Deduct stock again for the updated order
    $deductRes = mysqli_query(
        $con,
        "SELECT oi.itemcode,
                COALESCE(oi.quantity,0) AS line_qty,
                COALESCE(oi.meters,0)   AS line_meters,
                p.type AS product_type
         FROM sales_order_item oi
         INNER JOIN indiadata p ON p.itemcode = oi.itemcode
         WHERE oi.order_id = '".$order_id."'"
    );
    if (!$deductRes) {
        throw new Exception('Failed to read order lines for stock deduction.');
    }

    $deductNeedByItemcode = [];
    while ($dr = mysqli_fetch_assoc($deductRes)) {
        $ic = trim((string)$dr['itemcode']);
        if (!isset($deductNeedByItemcode[$ic])) {
            $deductNeedByItemcode[$ic] = ['m' => 0.0, 'pcs' => 0.0, 'product_type' => isset($dr['product_type']) ? $dr['product_type'] : ''];
        }
        $ptype = isset($dr['product_type']) ? $dr['product_type'] : '';
        if (product_stock_type_is_pcs($ptype)) {
            $deductNeedByItemcode[$ic]['pcs'] += (float)$dr['line_qty'];
        } else {
            $deductNeedByItemcode[$ic]['m'] += (float)$dr['line_meters'];
        }
    }

    $deductErrors = [];
    foreach ($deductNeedByItemcode as $ic => $need) {
        $icEsc = mysqli_real_escape_string($con, $ic);
        $availRes = mysqli_query($con, "SELECT quantity FROM indiadata WHERE itemcode = '".$icEsc."' LIMIT 1");
        if (!$availRes || mysqli_num_rows($availRes) === 0) {
            $deductErrors[] = $ic.': product not found';
            continue;
        }
        $availRow = mysqli_fetch_assoc($availRes);
        $available = parse_quantity_to_number(isset($availRow['quantity']) ? $availRow['quantity'] : '');
        $needAmount = product_stock_type_is_pcs($need['product_type']) ? (float)$need['pcs'] : (float)$need['m'];
        if ($needAmount > $available + 0.0001) {
            $deductErrors[] = $ic.': need '.$needAmount.' but available '.$available;
        }
    }

    if (!empty($deductErrors)) {
        mysqli_rollback($con);
        $response['success'] = false;
        $response['message'] = 'Could not update stock: '.implode('; ', $deductErrors);
        $response['data'] = null;
        echo json_encode($response);
        exit;
    }

    foreach ($deductNeedByItemcode as $ic => $need) {
        $icEsc = mysqli_real_escape_string($con, $ic);
        $availRes = mysqli_query($con, "SELECT quantity FROM indiadata WHERE itemcode = '".$icEsc."' LIMIT 1");
        $availRow = $availRes ? mysqli_fetch_assoc($availRes) : null;
        $available = parse_quantity_to_number(isset($availRow['quantity']) ? $availRow['quantity'] : '');
        $needAmount = product_stock_type_is_pcs($need['product_type']) ? (float)$need['pcs'] : (float)$need['m'];
        $newAvail = $available - $needAmount;
        $newAvailEsc = mysqli_real_escape_string($con, format_quantity_for_db($newAvail));
        $up = mysqli_query($con, "UPDATE indiadata SET quantity = '".$newAvailEsc."' WHERE itemcode = '".$icEsc."'");
        if (!$up) {
            throw new Exception('Failed to deduct stock for itemcode: '.$ic);
        }
    }

    mysqli_commit($con);

    if ($order_deleted) {
        $response['success'] = true;
        $response['message'] = 'Order removed (no items left). Stock updated.';
        $response['data'] = [
            'order_id'      => $order_id,
            'order_deleted' => true,
            'status'        => 'deleted',
            'total_meters'  => 0.0,
            'total_pieces'  => null,
            'order_total'   => 0.0,
            'items'         => []
        ];
        echo json_encode($response);
        exit;
    }

    // 4) Build updated order items response (same shape as order_detail_api)
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

    $priceSel = $hasPriceColumn ? ', oi.price AS line_unit_price' : '';
    $itemsQuery = "SELECT oi.id AS line_id, oi.itemcode, oi.quantity AS order_qty, COALESCE(oi.meters,0) AS order_total_meters,
                           p.description, p.image, p.quantity AS db_quantity, p.width, p.type AS product_type, p.trn_date".$priceSel."
                    FROM sales_order_item oi
                    LEFT JOIN indiadata p ON p.itemcode = oi.itemcode
                    WHERE oi.order_id = '".$order_id."'";

    $itemsRes = mysqli_query($con, $itemsQuery);
    $items = [];
    $totalMeters = 0.0;
    $totalPieces = 0.0;
    $hasPcsItems = false;
    $orderTotal = 0.0;
    if ($itemsRes) {
        while ($row = mysqli_fetch_assoc($itemsRes)) {
            $img = isset($row['image']) ? $row['image'] : '';
            $ptype = isset($row['product_type']) ? $row['product_type'] : '';
            $qty = isset($row['order_qty']) ? (float)$row['order_qty'] : 0.0;
            $totalM = (float)$row['order_total_meters'];

            if (product_stock_type_is_pcs($ptype)) {
                $hasPcsItems = true;
                $totalPieces += $qty;
            } else {
                $totalMeters += $totalM;
            }

            $priceRow = [
                'total_meters'     => $totalM,
                'line_unit_price'  => isset($row['line_unit_price']) ? $row['line_unit_price'] : null,
            ];
            cart_attach_line_amounts($priceRow, $hasPriceColumn);
            $lt = $priceRow['line_total'];
            if ($lt !== null) {
                $orderTotal += (float)$lt;
            }

            $isPcs = product_stock_type_is_pcs($ptype);
            $items[] = [
                'line_id'            => (int)$row['line_id'],
                'itemcode'           => $row['itemcode'],
                'meters'             => $totalM,
                'total_meters'       => $totalM,
                'price'              => $priceRow['price'],
                'line_total'         => $lt,
                'available_quantity' => parse_quantity_to_number(isset($row['db_quantity']) ? $row['db_quantity'] : ''),
                'product_name'       => isset($row['description']) ? $row['description'] : null,
                'description'        => isset($row['description']) ? $row['description'] : null,
                'image'              => $img,
                'image_url'          => $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '',
                'width'              => isset($row['width']) ? $row['width'] : null,
                'type'               => $ptype !== '' ? $ptype : null,
                'stock_mode'         => $isPcs ? 'PCS' : 'M',
                'pieces'             => $isPcs ? $qty : null,
                'total_pieces'       => $isPcs ? $qty : null,
                'location'           => isset($row['location']) ? $row['location'] : null,
                'trn_date'           => isset($row['trn_date']) ? $row['trn_date'] : null
            ];
        }
    }

    $response['success'] = true;
    $response['message'] = 'Order updated successfully. Stock updated.';
    $response['data'] = [
        'order_id'     => $order_id,
        'status'       => 'placed',
        'total_meters' => round($totalMeters, 2),
        'total_pieces' => $hasPcsItems ? round($totalPieces, 2) : null,
        'order_total'  => round($orderTotal, 2),
        'items'        => $items
    ];
    echo json_encode($response);
    exit;
} catch (Throwable $e) {
    mysqli_rollback($con);
    $response['message'] = $e->getMessage();
    $response['data'] = null;
    echo json_encode($response);
    exit;
}

