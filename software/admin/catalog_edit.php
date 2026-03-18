<?php
require('db.php');
include("auth.php");
require_once("catalog_lib.php");

$status = "";
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: catalog_index.php");
    exit;
}

$res = mysqli_query($con, "SELECT * FROM catalog WHERE id=".$id." LIMIT 1");
$catalog = $res ? mysqli_fetch_assoc($res) : null;
if (!$catalog) {
    header("Location: catalog_index.php");
    exit;
}

if (isset($_POST['new']) && $_POST['new'] == 1) {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $selectedType = isset($_POST['file_type']) ? trim($_POST['file_type']) : $catalog['file_type'];
    $files = isset($_FILES['files']) ? $_FILES['files'] : null;

    if ($title === '') {
        $status = '<div class="alert alert-danger" role="alert"><strong>Title is required.</strong></div>';
    } else {
        $titleEsc = mysqli_real_escape_string($con, $title);

        // If new files uploaded (any file not error=4), replace existing set
        $hasNewFiles = false;
        if ($files && isset($files['error']) && is_array($files['error'])) {
            foreach ($files['error'] as $e) {
                if ($e !== 4) { $hasNewFiles = true; break; }
            }
        }

        if ($hasNewFiles) {
            list($detectedType, $detectErr) = catalog_detect_type_from_files($files);
            if ($detectErr) {
                $status = '<div class="alert alert-danger" role="alert"><strong>'.$detectErr.'</strong></div>';
            } elseif ($selectedType !== 'image' && $selectedType !== 'pdf') {
                $status = '<div class="alert alert-danger" role="alert"><strong>Please select Image or PDF.</strong></div>';
            } elseif ($detectedType !== $selectedType) {
                $status = '<div class="alert alert-danger" role="alert"><strong>Selected type does not match uploaded files.</strong></div>';
            } else {
                // delete old files (disk + db)
                $oldFiles = catalog_get_files($con, $id);
                catalog_delete_files_on_disk($oldFiles);
                mysqli_query($con, "DELETE FROM catalog_file WHERE catalog_id=".$id);

                // update catalog meta first
                $typeEsc = mysqli_real_escape_string($con, $selectedType);
                mysqli_query($con, "UPDATE catalog SET title='$titleEsc', file_type='$typeEsc' WHERE id=".$id);

                // insert new files
                $err = null;
                if (!catalog_move_and_insert_files($con, $id, $selectedType, $files, $err)) {
                    $status = '<div class="alert alert-danger" role="alert"><strong>'.htmlspecialchars($err).'</strong></div>';
                } else {
                    $status = '<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon-ok"></span> Catalog updated successfully. <a href="catalog_index.php">Back to list</a></div>';
                    // refresh catalog
                    $res2 = mysqli_query($con, "SELECT * FROM catalog WHERE id=".$id." LIMIT 1");
                    $catalog = $res2 ? mysqli_fetch_assoc($res2) : $catalog;
                }
            }
        } else {
            // only title update
            mysqli_query($con, "UPDATE catalog SET title='$titleEsc' WHERE id=".$id);
            $status = '<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon-ok"></span> Title updated successfully. <a href="catalog_index.php">Back to list</a></div>';
            $catalog['title'] = $title;
        }
    }
}

$filesList = catalog_get_files($con, $id);
$publicBase = catalog_public_base();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Edit Catalog</title>
<script src="../js/modernizr-2.6.2.min.js"></script>
<link rel="stylesheet" type="text/css" href="../bootstarp/bootstrap.css" />
<link rel="stylesheet" type="text/css" href="../css/style.css" />
<!--[if lt IE 9]>
   <script src="../js/html5shiv.min.js"></script>
<![endif]-->
</head>
<body>
<ul class="topNav">
  <li><a class="admin" href="https://theclothlibrary.com/">Home</a></li>
  <li><a class="admin" href="../region.html">User Login</a></li>
  <li><a class="admin selected" href="login.php">Admin Login</a></li>
  <div class="clear"></div>
</ul>
<div id="mainPanel">
  <div id="logo" style="margin-left:10%; margin-right:10%;text-align:center;">
    <img src="../images/lc_logo_small.jpg" width="100%" height="80px;" alt="Kalidioscope" />
  </div>

  <div id="content_area">
    <div align="right" style="padding-bottom:10px;"><?php echo "Hello <b>" .$_SESSION["username"]; ?></b> | <a href="logout.php">
      <button type="button" class="btn btn-sm btn-danger">Logout</button>
      </a></div>

    <p>
      <a href="dashboard.php"><button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-modal-window"></span> Dashboard</button></a>
      <a href="catalog_index.php"><button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-th-list"></span> Catalog List</button></a>
      <a href="catalog_add.php"><button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-plus"></span> Add Catalog</button></a>
    </p>

    <h3>Edit Catalog (#<?php echo (int)$catalog['id']; ?>)</h3>
    <?php echo $status; ?>

    <form class="form-horizontal" method="post" action="" autocomplete="off" enctype="multipart/form-data">
      <input type="hidden" name="new" value="1" />

      <div class="form-group">
        <label class="col-sm-2 control-label">Title</label>
        <div class="col-sm-10">
          <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($catalog['title']); ?>" required />
        </div>
      </div>

      <div class="form-group">
        <label class="col-sm-2 control-label">Type</label>
        <div class="col-sm-10">
          <label class="radio-inline">
            <input type="radio" name="file_type" value="image" <?php echo ($catalog['file_type'] === 'image') ? 'checked' : ''; ?>> Multiple Images
          </label>
          <label class="radio-inline">
            <input type="radio" name="file_type" value="pdf" <?php echo ($catalog['file_type'] === 'pdf') ? 'checked' : ''; ?>> Multiple PDFs
          </label>
          <p class="help-block">If you upload new files, existing files will be replaced.</p>
        </div>
      </div>

      <div class="form-group">
        <label class="col-sm-2 control-label">Upload New Files</label>
        <div class="col-sm-10">
          <input type="file" name="files[]" id="files" class="form-control" multiple />
          <p class="help-block">Leave empty to keep current files.</p>
        </div>
      </div>

      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary">Update Catalog</button>
        </div>
      </div>
    </form>

    <h4>Current Files</h4>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Preview</th>
            <th>Original Name</th>
            <th>File</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($filesList) > 0) { ?>
            <?php foreach ($filesList as $f) { ?>
              <tr>
                <td>
                  <?php if ($catalog['file_type'] === 'image') { ?>
                    <a href="<?php echo $publicBase . htmlspecialchars($f['file_name']); ?>" target="_blank">
                      <img src="<?php echo $publicBase . htmlspecialchars($f['file_name']); ?>" style="max-width:120px; max-height:80px;" />
                    </a>
                  <?php } else { ?>
                    <a href="<?php echo $publicBase . htmlspecialchars($f['file_name']); ?>" target="_blank" title="Open PDF">
                      <span class="glyphicon glyphicon-file"></span> PDF
                    </a>
                  <?php } ?>
                </td>
                <td><?php echo htmlspecialchars($f['original_name']); ?></td>
                <td><a href="<?php echo $publicBase . htmlspecialchars($f['file_name']); ?>" target="_blank">Open</a></td>
              </tr>
            <?php } ?>
          <?php } else { ?>
            <tr><td colspan="3">No files.</td></tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>

  <div id="footer">copyright &copy; 2018 The Cloth Library.</div>
</div>
<script src="../js/jquery-1.9.1.min.js"></script>
<script src="../bootstarp/bootstrap.min.js"></script>
<script type="text/javascript">
  (function() {
    var fileInput = document.getElementById('files');
    var typeRadios = document.querySelectorAll('input[name="file_type"]');

    function setAccept(type) {
      if (!fileInput) return;
      if (type === 'image') {
        fileInput.accept = 'image/*,.jpg,.jpeg,.png,.gif,.webp';
      } else if (type === 'pdf') {
        fileInput.accept = 'application/pdf,.pdf';
      } else {
        fileInput.accept = '';
      }
    }

    for (var i = 0; i < typeRadios.length; i++) {
      typeRadios[i].addEventListener('change', function() {
        setAccept(this.value);
        fileInput.value = '';
      });
    }

    var checked = document.querySelector('input[name="file_type"]:checked');
    setAccept(checked ? checked.value : '');
  })();
</script>
</body>
</html>

