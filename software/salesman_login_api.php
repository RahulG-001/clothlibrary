<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require('admin/db.php');

$response = [
    'success' => false,
    'message' => '',
    'data'    => null
];

$phone    = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if ($phone === '' || $password === '') {
    $response['message'] = 'Phone and password are required.';
    echo json_encode($response);
    exit;
}

$phoneEsc    = mysqli_real_escape_string($con, $phone);
$passwordEsc = mysqli_real_escape_string($con, $password);

$query  = "SELECT id, first_name, last_name, phone FROM salesman ";
$query .= "WHERE phone = '".$phoneEsc."' AND password = '".$passwordEsc."' ";
$query .= "LIMIT 1";

$result = mysqli_query($con, $query);

if ($result && mysqli_num_rows($result) === 1) {
    $row = mysqli_fetch_assoc($result);
    $response['success'] = true;
    $response['message'] = 'Login successful.';
    $response['data']    = $row;
} else {
    $response['message'] = 'Invalid phone or password.';
}

echo json_encode($response);
exit;

