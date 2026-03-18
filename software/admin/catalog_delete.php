<?php
require('db.php');
include("auth.php");
require_once("catalog_lib.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: catalog_index.php");
    exit;
}

// delete files on disk first
$files = catalog_get_files($con, $id);
catalog_delete_files_on_disk($files);

// delete row (will cascade delete catalog_file)
mysqli_query($con, "DELETE FROM catalog WHERE id=".$id);

header("Location: catalog_index.php");
exit;
?>

