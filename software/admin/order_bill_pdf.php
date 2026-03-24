<?php
require_once(__DIR__.'/../vendor/autoload.php');

use Dompdf\Dompdf;
use Dompdf\Options;

// Admin pages rely on session auth. We do NOT want redirects here,
// because dompdf expects bill HTML/PDF content, not the login HTML.
$isAuthed = false;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isAuthed = isset($_SESSION['username']) && trim((string)$_SESSION['username']) !== '';

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$download = isset($_GET['download']) ? (bool)$_GET['download'] : false;

if ($orderId <= 0) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Invalid order id.';
    exit;
}

if (!$isAuthed) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unauthorized';
    exit;
}

// Tell the HTML renderer to skip headers and render a clean HTML string.
$_GET['mode'] = 'render';
$_GET['embed'] = 1;
$_GET['download'] = 0;

while (ob_get_level()) {
    ob_end_clean();
}

ob_start();
include(__DIR__.'/order_bill_print.php');
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$softwareRoot = realpath(__DIR__.'/..');
if ($softwareRoot) {
    $options->setChroot($softwareRoot);
}

$dompdf = new Dompdf($options);

$dompdf->setPaper('a4', 'portrait');

$dompdf->loadHtml($html);
$dompdf->render();

$filename = 'client_bill_'.$orderId.'.pdf';
$pdfBytes = $dompdf->output();

header('Content-Type: application/pdf');
header('Content-Disposition: '.($download ? 'attachment' : 'inline').'; filename="'.$filename.'"');
echo $pdfBytes;
exit;

