<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once(__DIR__.'/admin/db.php');
require_once(__DIR__.'/salesman_auth.php');
require_once(__DIR__.'/vendor/autoload.php');

use Dompdf\Dompdf;
use Dompdf\Options;

$authSalesman = salesman_require_auth($con);
$salesmanId = isset($authSalesman['id']) ? (int)$authSalesman['id'] : 0;

$input = [];
if (isset($_SERVER['CONTENT_TYPE']) && strpos((string)$_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
} else {
    $input = $_POST;
}

$orderId = 0;
if (isset($_GET['order_id'])) {
    $orderId = (int)$_GET['order_id'];
} elseif (isset($input['order_id'])) {
    $orderId = (int)$input['order_id'];
} elseif (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
} elseif (isset($input['id'])) {
    $orderId = (int)$input['id'];
}

if ($salesmanId <= 0 || $orderId <= 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'order_id is required.',
        'data' => null,
    ]);
    exit;
}

$orderRes = mysqli_query(
    $con,
    "SELECT id
     FROM sales_order
     WHERE id = '".$orderId."'
       AND salesman_id = '".$salesmanId."'
     LIMIT 1"
);

if (!$orderRes || mysqli_num_rows($orderRes) === 0) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Order not found.',
        'data' => null,
    ]);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['username']) || trim((string)$_SESSION['username']) === '') {
    $_SESSION['username'] = 'api_salesman_'.$salesmanId;
}

// Build same themed HTML used by admin print page.
$_GET['id'] = $orderId;
$_GET['mode'] = 'render';
$_GET['embed'] = 1;
$_GET['download'] = 0;

while (ob_get_level()) {
    ob_end_clean();
}

ob_start();
include(__DIR__.'/admin/order_bill_print.php');
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$softwareRoot = realpath(__DIR__);
if ($softwareRoot) {
    $options->setChroot($softwareRoot);
}

$dompdf = new Dompdf($options);
$dompdf->setPaper('a4', 'portrait');
$dompdf->loadHtml($html);
$dompdf->render();

$filename = 'order_sheet_'.$orderId.'.pdf';
$pdfBytes = $dompdf->output();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.$filename.'"');
echo $pdfBytes;
exit;

