<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require(__DIR__.'/admin/db.php');
require_once(__DIR__.'/salesman_auth.php');
require_once(__DIR__.'/db_tables.php');
require_once(__DIR__.'/quantity_parser.php');

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

// Write recent API failures so we can inspect them from `software/error.php`.
// Each line is a JSON object (jsonl style).
$qrErrorLogFile = __DIR__.'/admin/qr_api_errors.log';
function qr_api_log_error($logFile, $payload) {
    if (!is_string($logFile) || $logFile === '') {
        return;
    }
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        return;
    }
    $line = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        return;
    }
    @file_put_contents($logFile, $line.PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Require salesman token
$authSalesman = salesman_require_auth($con);

$indiaTable = get_india_data_table($con);
if ($indiaTable === null) {
    $response['message'] = "DB error: table indiadata/indiadata not found";
    qr_api_log_error($qrErrorLogFile, [
        'time' => gmdate('c'),
        'message' => $response['message'],
        'response' => $response,
    ]);
    echo json_encode($response);
    exit;
}

// QR value can be passed as `qr` or `code` or `itemcode`
$qr = '';
if (isset($_GET['qr'])) {
    $qr = trim((string)$_GET['qr']);
} elseif (isset($_GET['code'])) {
    $qr = trim((string)$_GET['code']);
} elseif (isset($_GET['itemcode'])) {
    $qr = trim((string)$_GET['itemcode']);
}

$qr = rawurldecode($qr);
$qr = trim((string)$qr);

// Small debug info to help understand why parsing/lookup fails.
// Avoid returning the full QR payload if it is very long.
$qrPreview = $qr;
$qrLen = strlen($qrPreview);
if ($qrLen > 220) {
    $qrPreview = substr($qrPreview, 0, 220).'...';
}

if ($qr === '') {
    $response['message'] = 'qr (or code/itemcode) is required.';
    $response['debug'] = [
        'qr_preview' => $qrPreview,
        'qr_len' => 0,
    ];
    qr_api_log_error($qrErrorLogFile, [
        'time' => gmdate('c'),
        'message' => $response['message'],
        'response' => $response,
    ]);
    echo json_encode($response);
    exit;
}

$qrItemcode = '';
$qrId = 0;

function decode_qr_ref_token($ref) {
    $ref64 = strtr((string)$ref, '-_', '+/');
    $padding = strlen($ref64) % 4;
    if ($padding > 0) {
        $ref64 .= str_repeat('=', 4 - $padding);
    }
    $decoded = base64_decode($ref64, true);
    if ($decoded === false) {
        return null;
    }
    $json = json_decode($decoded, true);
    return is_array($json) ? $json : null;
}

// 1) Direct itemcode in QR
$qrItemcode = $qr;

// 2) URL QR support: ...?id=123&itemcode=ABC (clear itemcode — otherwise $qrItemcode stays the full URL)
if (strpos($qr, '://') !== false) {
    $qrItemcode = '';
    $parts = @parse_url($qr);
    if ($parts && isset($parts['query'])) {
        $params = [];
        parse_str($parts['query'], $params);
        if (isset($params['id']) && (int)$params['id'] > 0) {
            $qrId = (int)$params['id'];
        }
        if (isset($params['itemcode']) && trim((string)$params['itemcode']) !== '') {
            $qrItemcode = trim((string)$params['itemcode']);
        }
        if (isset($params['ref']) && trim((string)$params['ref']) !== '') {
            $decodedRef = decode_qr_ref_token(trim((string)$params['ref']));
            if (is_array($decodedRef)) {
                if (isset($decodedRef['id']) && (int)$decodedRef['id'] > 0) {
                    $qrId = (int)$decodedRef['id'];
                }
                if (isset($decodedRef['itemcode']) && trim((string)$decodedRef['itemcode']) !== '') {
                    $qrItemcode = trim((string)$decodedRef['itemcode']);
                }
            }
        }
    }
}

// 2c) Partial query support (some scanners/apps drop scheme/host)
// Examples the API should still understand:
//   id=123
//   ?id=123
//   id=123&itemcode=ABC
if (preg_match('/(?:\\?|&|^)id=([0-9]+)/', $qr, $mId2)) {
    $qrId = (int)$mId2[1];
    // If an id=... query fragment is present, we should not treat the full fragment as itemcode.
    $qrItemcode = '';
}
if (preg_match('/(?:\\?|&|^)itemcode=([A-Za-z0-9_\\-\\/]+)/', $qr, $mCode2)) {
    $qrItemcode = trim((string)$mCode2[1]);
}

// 2b) Raw text contains "ref=..." (for clients passing partial URL/query)
if (preg_match('/(?:\?|&|^)ref=([A-Za-z0-9\-_]+)/', $qr, $mRef)) {
    $decodedRef = decode_qr_ref_token($mRef[1]);
    if (is_array($decodedRef)) {
        if (isset($decodedRef['id']) && (int)$decodedRef['id'] > 0) {
            $qrId = (int)$decodedRef['id'];
        }
        if (isset($decodedRef['itemcode']) && trim((string)$decodedRef['itemcode']) !== '') {
            $qrItemcode = trim((string)$decodedRef['itemcode']);
        }
    }
}

// 3) Legacy text QR support: "ID: 12, ITEMCODE: ABC123"
if (preg_match('/ID\s*:\s*([0-9]+)/i', $qr, $mId)) {
    $qrId = (int)$mId[1];
}
if (preg_match('/ITEMCODE\s*:\s*([A-Za-z0-9_\-\/]+)/i', $qr, $mCode)) {
    $qrItemcode = trim((string)$mCode[1]);
}

// Build full image URL base
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

$where = [];
if ($qrId > 0) {
    $where[] = "id = '".intval($qrId)."'";
}
if ($qrItemcode !== '') {
    $qrEsc = mysqli_real_escape_string($con, $qrItemcode);
    $where[] = "LOWER(TRIM(itemcode)) = LOWER(TRIM('".$qrEsc."'))";
}

$debugInfo = [
    'qr_preview' => $qrPreview,
    'qr_len' => $qrLen,
    'indiaTable' => $indiaTable,
    'parsed' => [
        'qrId' => $qrId,
        'qrItemcode' => $qrItemcode,
    ],
    'where' => $where,
];

if (count($where) === 0) {
    $response['message'] = 'Invalid QR format.';
    $response['debug'] = $debugInfo;
    $response['debug']['reason'] = 'No id=... or itemcode=... (or legacy ID:/ITEMCODE:) could be extracted';
    qr_api_log_error($qrErrorLogFile, [
        'time' => gmdate('c'),
        'message' => $response['message'],
        'response' => $response,
    ]);
    echo json_encode($response);
    exit;
}
$query = "SELECT *
          FROM ".$indiaTable."
          WHERE ".implode(' OR ', $where)."
          LIMIT 1";

$res = mysqli_query($con, $query);
if (!$res || mysqli_num_rows($res) === 0) {
    $response['message'] = 'Product not found.';
    $response['debug'] = $debugInfo;
    $response['debug']['reason'] = 'Query returned 0 rows for extracted id/itemcode';
    qr_api_log_error($qrErrorLogFile, [
        'time' => gmdate('c'),
        'message' => $response['message'],
        'response' => $response,
    ]);
    echo json_encode($response);
    exit;
}

$row = mysqli_fetch_assoc($res);

// Add computed fields
$img = isset($row['image']) ? $row['image'] : '';
$row['image_url'] = $img !== '' ? $scheme.'://'.$host.$base.'/item_images/'.$img : '';
// Convert DB quantity string to numeric for app usage
$row['available_quantity'] = parse_quantity_to_number(isset($row['quantity']) ? $row['quantity'] : '');

$response['success'] = true;
$response['message'] = 'Product fetched successfully.';
$response['data'] = [
    'itemcode' => isset($row['itemcode']) ? $row['itemcode'] : $qrItemcode,
    'product'  => $row
];
$response['debug'] = $debugInfo;

// Log successful parses too (useful while debugging QR scan issues).
qr_api_log_error($qrErrorLogFile, [
    'time' => gmdate('c'),
    'message' => 'Product fetched successfully',
    'response' => $response,
]);

echo json_encode($response);
exit;

