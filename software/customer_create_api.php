<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require(__DIR__.'/admin/db.php');
require_once(__DIR__.'/salesman_auth.php');

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Method not allowed. Use POST.';
    echo json_encode($response);
    exit;
}

$authSalesman = salesman_require_auth($con);

function users_has_column($con, $column) {
    $col = mysqli_real_escape_string($con, $column);
    $r = mysqli_query($con, "SHOW COLUMNS FROM `users` LIKE '".$col."'");
    return $r && mysqli_num_rows($r) > 0;
}

if (!users_has_column($con, 'name') || !users_has_column($con, 'address') || !users_has_column($con, 'visiting_card')) {
    $response['message'] = 'Database is missing customer columns. Run software/admin/alter_users_customer_fields.sql on this database.';
    echo json_encode($response);
    exit;
}

function pick_string_from_keys($src, $keys) {
    if (!is_array($src)) {
        return '';
    }
    foreach ($keys as $k) {
        if (isset($src[$k]) && trim((string)$src[$k]) !== '') {
            return trim((string)$src[$k]);
        }
    }
    return '';
}

// multipart/form-data (visiting card) or JSON / x-www-form-urlencoded
$isJson = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
$name = '';
$address = '';

$nameKeys    = ['name', 'Name', 'NAME', 'customer_name', 'full_name', 'display_name'];
$addressKeys = ['address', 'Address', 'ADDRESS', 'customer_address'];

if ($isJson) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (is_array($input)) {
        $name    = pick_string_from_keys($input, $nameKeys);
        $address = pick_string_from_keys($input, $addressKeys);
    }
} else {
    $name    = pick_string_from_keys($_POST, $nameKeys);
    $address = pick_string_from_keys($_POST, $addressKeys);
}

if ($name === '') {
    $response['message'] = 'name is required.';
    echo json_encode($response);
    exit;
}

$defaultPassword = '123456';

function generate_unique_customer_userid($con) {
    for ($i = 0; $i < 12; $i++) {
        $id = 'C'.date('Ymd').'_'.substr(bin2hex(random_bytes(4)), 0, 8);
        $idEsc = mysqli_real_escape_string($con, $id);
        $r = mysqli_query($con, "SELECT id FROM users WHERE userid = '".$idEsc."' LIMIT 1");
        if ($r && mysqli_num_rows($r) === 0) {
            return $id;
        }
    }
    return null;
}

$userid = generate_unique_customer_userid($con);
if ($userid === null) {
    $response['message'] = 'Could not generate a unique user id. Try again.';
    echo json_encode($response);
    exit;
}

$visitingCardFile = '';

$uploadDir = __DIR__.DIRECTORY_SEPARATOR.'visiting_cards'.DIRECTORY_SEPARATOR;
if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
    $response['message'] = 'Could not create local folder for visiting cards (visiting_cards).';
    echo json_encode($response);
    exit;
}
if (!is_writable($uploadDir)) {
    $response['message'] = 'Visiting cards folder is not writable (visiting_cards).';
    echo json_encode($response);
    exit;
}

$fileFieldKeys = [
    'visiting_cards',
    'visiting_card',
    'VisitingCards',
    'VisitingCard',
    'visitingCard',
    'card',
    'card_image',
    'image',
    'file',
];
$uploadFile = null;
foreach ($fileFieldKeys as $fk) {
    if (!empty($_FILES[$fk]) && isset($_FILES[$fk]['error']) && $_FILES[$fk]['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadFile = $_FILES[$fk];
        break;
    }
}

if ($uploadFile !== null) {
    $f = $uploadFile;
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'Visiting card upload failed (error code '.$f['error'].').';
        echo json_encode($response);
        exit;
    }
    $maxBytes = 5 * 1024 * 1024;
    if ($f['size'] > $maxBytes) {
        $response['message'] = 'Visiting card image is too large (max 5MB).';
        echo json_encode($response);
        exit;
    }
    $tmp = $f['tmp_name'];
    $check = @getimagesize($tmp);
    if ($check === false) {
        $response['message'] = 'visiting_card must be an image file.';
        echo json_encode($response);
        exit;
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if ($ext === '' || !in_array($ext, $allowed, true)) {
        $response['message'] = 'Allowed visiting_card types: jpg, jpeg, png, gif, webp.';
        echo json_encode($response);
        exit;
    }
    $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($f['name'], PATHINFO_FILENAME));
    if ($safeBase === '') {
        $safeBase = 'card';
    }
    $newName = 'vc_'.$safeBase.'_'.date('Ymd_His').'_'.substr(bin2hex(random_bytes(3)), 0, 6).'.'.$ext;
    $targetPath = $uploadDir.$newName;
    if (!move_uploaded_file($tmp, $targetPath)) {
        $response['message'] = 'Could not save visiting card image to local disk.';
        echo json_encode($response);
        exit;
    }
    if (!is_file($targetPath)) {
        $response['message'] = 'Visiting card was not found on disk after upload.';
        echo json_encode($response);
        exit;
    }
    $visitingCardFile = $newName;
}

$useridEsc = mysqli_real_escape_string($con, $userid);
$pwEsc     = mysqli_real_escape_string($con, $defaultPassword);
$nameEsc   = mysqli_real_escape_string($con, $name);
$addrEsc   = mysqli_real_escape_string($con, $address);
$vcEsc     = mysqli_real_escape_string($con, $visitingCardFile);

$sql = "INSERT INTO users (userid, password, name, address, visiting_card) VALUES ('".$useridEsc."', '".$pwEsc."', '".$nameEsc."', '".$addrEsc."', ".($visitingCardFile === '' ? "NULL" : "'".$vcEsc."'").")";

if (!mysqli_query($con, $sql)) {
    $response['message'] = 'Could not create customer: '.mysqli_error($con);
    echo json_encode($response);
    exit;
}

$newId = (int)mysqli_insert_id($con);

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$base   = rtrim(dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : ''), '/\\');
$vcUrl  = $visitingCardFile !== '' ? $scheme.'://'.$host.$base.'/visiting_cards/'.$visitingCardFile : '';
$vcLocalPath = $visitingCardFile !== '' ? $uploadDir.$visitingCardFile : '';

$response['success'] = true;
$response['message'] = 'Customer created. Name saved in database. Login user id is '.$userid.'. Default password is '.$defaultPassword.'.'
    .($visitingCardFile !== '' ? ' Visiting card saved on server.' : '');
$response['data'] = [
    'id'                 => $newId,
    'user_id'            => $userid,
    'userid'             => $userid,
    'name'               => $name,
    'address'            => $address,
    'visiting_card'      => $visitingCardFile,
    'visiting_card_url'  => $vcUrl,
    'visiting_card_path' => $vcLocalPath !== '' ? str_replace('\\', '/', realpath($vcLocalPath) ?: $vcLocalPath) : '',
    'salesman_id'        => (int)$authSalesman['id'],
];

echo json_encode($response);
exit;
