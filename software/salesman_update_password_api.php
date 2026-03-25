<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require('admin/db.php');
require_once('salesman_auth.php');

$salesman = salesman_require_auth($con);

// JSON body, or form-data / multipart (use multipart if sending an image)
$input = [];
$isJson = isset($_SERVER['CONTENT_TYPE']) && strpos((string)$_SERVER['CONTENT_TYPE'], 'application/json') !== false;
if ($isJson) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        $input = [];
    }
} else {
    $input = $_POST;
}

$new_password = isset($input['new_password']) ? trim((string)$input['new_password']) : '';
if ($new_password === '') {
    echo json_encode([
        'success' => false,
        'message' => 'new_password is required.',
    ]);
    exit;
}

$pwEsc = mysqli_real_escape_string($con, $new_password);
$sid = (int)$salesman['id'];

$newProfileImage = null;
$uploadDir = __DIR__.DIRECTORY_SEPARATOR.'salesman_profile_images'.DIRECTORY_SEPARATOR;

if (!$isJson && !empty($_FILES)) {
    $fileFieldKeys = ['profile_image', 'image', 'photo', 'file'];
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
            echo json_encode([
                'success' => false,
                'message' => 'Image upload failed (error code '.$f['error'].').',
            ]);
            exit;
        }
        $maxBytes = 5 * 1024 * 1024;
        if ($f['size'] > $maxBytes) {
            echo json_encode([
                'success' => false,
                'message' => 'Image is too large (max 5MB).',
            ]);
            exit;
        }
        $tmp = $f['tmp_name'];
        $check = @getimagesize($tmp);
        if ($check === false) {
            echo json_encode([
                'success' => false,
                'message' => 'Uploaded file must be an image.',
            ]);
            exit;
        }
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if ($ext === '' || !in_array($ext, $allowed, true)) {
            echo json_encode([
                'success' => false,
                'message' => 'Allowed image types: jpg, jpeg, png, gif, webp.',
            ]);
            exit;
        }

        if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
            echo json_encode([
                'success' => false,
                'message' => 'Could not create upload folder.',
            ]);
            exit;
        }
        if (!is_writable($uploadDir)) {
            echo json_encode([
                'success' => false,
                'message' => 'Upload folder is not writable.',
            ]);
            exit;
        }

        $newName = 'sm_'.$sid.'_'.date('Ymd_His').'_'.substr(bin2hex(random_bytes(4)), 0, 8).'.'.$ext;
        $targetPath = $uploadDir.$newName;
        if (!move_uploaded_file($tmp, $targetPath)) {
            echo json_encode([
                'success' => false,
                'message' => 'Could not save image.',
            ]);
            exit;
        }
        $newProfileImage = $newName;

        if (!salesman_table_has_profile_image($con)) {
            @unlink($targetPath);
            echo json_encode([
                'success' => false,
                'message' => 'profile_image column missing. Run software/admin/alter_salesman_profile_image.sql on your database.',
            ]);
            exit;
        }

        $oldName = isset($salesman['profile_image']) ? trim((string)$salesman['profile_image']) : '';
        if ($oldName !== '' && preg_match('/^[a-zA-Z0-9_\-\.]+$/', $oldName)) {
            $oldPath = $uploadDir.$oldName;
            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
    }
}

$sets = ["password = '".$pwEsc."'"];
if ($newProfileImage !== null) {
    $sets[] = "profile_image = '".mysqli_real_escape_string($con, $newProfileImage)."'";
}
$sql = "UPDATE salesman SET ".implode(', ', $sets)." WHERE id = '".$sid."' LIMIT 1";

$ok = mysqli_query($con, $sql);
if (!$ok) {
    if ($newProfileImage !== null && is_file($uploadDir.$newProfileImage)) {
        @unlink($uploadDir.$newProfileImage);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update: '.mysqli_error($con),
    ]);
    exit;
}

$out = [
    'success' => true,
    'message' => 'Password updated.',
];
if ($newProfileImage !== null) {
    $out['message'] = 'Password and profile image updated.';
    $out['profile_image'] = $newProfileImage;
}
echo json_encode($out);
exit;
