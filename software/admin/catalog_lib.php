<?php

function catalog_upload_dir() {
    // From /software/admin -> /software/catalog_uploads
    return realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'catalog_uploads' . DIRECTORY_SEPARATOR;
}

function catalog_public_base() {
    // From /software/admin -> /software/catalog_uploads (public path)
    return '../catalog_uploads/';
}

function catalog_allowed_exts($fileType) {
    if ($fileType === 'image') {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    }
    if ($fileType === 'pdf') {
        return ['pdf'];
    }
    return [];
}

function catalog_detect_type_from_files($files) {
    $hasImage = false;
    $hasPdf = false;

    if (!isset($files['name']) || !is_array($files['name'])) {
        return [null, 'No files selected.'];
    }

    for ($i = 0; $i < count($files['name']); $i++) {
        if (!isset($files['error'][$i]) || $files['error'][$i] === 4) {
            continue;
        }
        if ($files['error'][$i] !== 0) {
            return [null, 'One or more files failed to upload.'];
        }
        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (in_array($ext, catalog_allowed_exts('pdf'))) {
            $hasPdf = true;
        } elseif (in_array($ext, catalog_allowed_exts('image'))) {
            $hasImage = true;
        } else {
            return [null, 'Invalid file type: ' . htmlspecialchars($ext)];
        }
    }

    if ($hasImage && $hasPdf) {
        return [null, 'Upload either multiple images OR multiple PDFs (not both).'];
    }
    if ($hasPdf) return ['pdf', null];
    if ($hasImage) return ['image', null];
    return [null, 'No files selected.'];
}

function catalog_ensure_upload_dir() {
    $dir = catalog_upload_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return is_dir($dir) && is_writable($dir);
}

function catalog_move_and_insert_files($con, $catalogId, $fileType, $files, &$errorMsg) {
    $errorMsg = null;

    if (!catalog_ensure_upload_dir()) {
        $errorMsg = 'Upload folder is not writable.';
        return false;
    }

    $allowedExts = catalog_allowed_exts($fileType);
    if (empty($allowedExts)) {
        $errorMsg = 'Invalid upload type.';
        return false;
    }

    $uploadDir = catalog_upload_dir();

    $inserted = 0;
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === 4) {
            continue;
        }
        if ($files['error'][$i] !== 0) {
            $errorMsg = 'One or more files failed to upload.';
            return false;
        }

        $originalName = $files['name'][$i];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            $errorMsg = 'Only ' . strtoupper($fileType) . ' files are allowed.';
            return false;
        }

        // For images, ensure it is actually an image
        if ($fileType === 'image') {
            $imgCheck = @getimagesize($files['tmp_name'][$i]);
            if ($imgCheck === false) {
                $errorMsg = 'One or more uploaded files are not valid images.';
                return false;
            }
        }

        $mimeType = isset($files['type'][$i]) ? $files['type'][$i] : '';

        $safeExt = preg_replace('/[^a-z0-9]+/', '', $ext);
        $fileName = 'cat_' . (int)$catalogId . '_' . uniqid('', true) . '.' . $safeExt;
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
            $errorMsg = 'Error saving uploaded file.';
            return false;
        }

        $fileNameEsc = mysqli_real_escape_string($con, $fileName);
        $originalEsc = mysqli_real_escape_string($con, $originalName);
        $mimeEsc = mysqli_real_escape_string($con, $mimeType);
        $ins = "INSERT INTO catalog_file (catalog_id, file_name, original_name, mime_type)
                VALUES (" . (int)$catalogId . ", '$fileNameEsc', '$originalEsc', '$mimeEsc')";
        if (!mysqli_query($con, $ins)) {
            // rollback file on disk
            @unlink($targetPath);
            $errorMsg = 'Database error while saving file.';
            return false;
        }

        $inserted++;
    }

    if ($inserted === 0) {
        $errorMsg = 'No files selected.';
        return false;
    }

    return true;
}

function catalog_get_files($con, $catalogId) {
    $rows = [];
    $res = mysqli_query($con, "SELECT id, file_name, original_name, mime_type FROM catalog_file WHERE catalog_id=".(int)$catalogId." ORDER BY id DESC");
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }
    }
    return $rows;
}

function catalog_delete_files_on_disk($files) {
    $uploadDir = catalog_upload_dir();
    foreach ($files as $f) {
        if (!isset($f['file_name'])) continue;
        $path = $uploadDir . $f['file_name'];
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

?>

