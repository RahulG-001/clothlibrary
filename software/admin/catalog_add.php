<?php
require('db.php');
include("auth.php");
require_once("catalog_lib.php");

$status = "";

if (isset($_POST['new']) && $_POST['new'] == 1) {
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $selectedType = isset($_POST['file_type']) ? trim($_POST['file_type']) : '';
    $files = isset($_FILES['files']) ? $_FILES['files'] : null;

    if ($title === '') {
        $status = '<div class="alert alert-danger" role="alert"><strong>Title is required.</strong></div>';
    } elseif ($files === null) {
        $status = '<div class="alert alert-danger" role="alert"><strong>Please select files.</strong></div>';
    } else {
        // Enforce: multiple images OR multiple PDFs, and match selected type
        list($detectedType, $detectErr) = catalog_detect_type_from_files($files);
        if ($detectErr) {
            $status = '<div class="alert alert-danger" role="alert"><strong>'.$detectErr.'</strong></div>';
        } elseif ($selectedType !== 'image' && $selectedType !== 'pdf') {
            $status = '<div class="alert alert-danger" role="alert"><strong>Please select Image or PDF.</strong></div>';
        } elseif ($detectedType !== $selectedType) {
            $status = '<div class="alert alert-danger" role="alert"><strong>Selected type does not match uploaded files.</strong></div>';
        } else {
            $titleEsc = mysqli_real_escape_string($con, $title);
            $typeEsc = mysqli_real_escape_string($con, $selectedType);

            $ins = "INSERT INTO catalog (title, file_type) VALUES ('$titleEsc', '$typeEsc')";
            if (!mysqli_query($con, $ins)) {
                $status = '<div class="alert alert-danger" role="alert"><strong>Database error while creating catalog.</strong></div>';
            } else {
                $catalogId = (int)mysqli_insert_id($con);
                $err = null;
                if (!catalog_move_and_insert_files($con, $catalogId, $selectedType, $files, $err)) {
                    // cleanup the catalog row
                    mysqli_query($con, "DELETE FROM catalog WHERE id=".$catalogId);
                    $status = '<div class="alert alert-danger" role="alert"><strong>'.htmlspecialchars($err).'</strong></div>';
                } else {
                    $status = '<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon-ok"></span> Catalog added successfully. <a href="catalog_index.php">View Catalog</a></div>';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Add Catalog</title>
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
    </p>

    <h3>Add Catalog</h3>
    <?php echo $status; ?>

    <form class="form-horizontal" method="post" action="" autocomplete="off" enctype="multipart/form-data">
      <input type="hidden" name="new" value="1" />

      <div class="form-group">
        <label for="title" class="col-sm-2 control-label">Title</label>
        <div class="col-sm-10">
          <input type="text" name="title" class="form-control" required />
        </div>
      </div>

      <div class="form-group">
        <label class="col-sm-2 control-label">Upload Type</label>
        <div class="col-sm-10">
          <label class="radio-inline">
            <input type="radio" name="file_type" value="image" required> Multiple Images
          </label>
          <label class="radio-inline">
            <input type="radio" name="file_type" value="pdf" required> Multiple PDFs
          </label>
          <p class="help-block">At one time: upload either multiple images or multiple PDFs.</p>
        </div>
      </div>

      <div class="form-group">
        <label for="files" class="col-sm-2 control-label">Files</label>
        <div class="col-sm-10">
          <input type="file" name="files[]" id="files" class="form-control" multiple required disabled />
          <p class="help-block">First select upload type, then choose files.</p>
        </div>
      </div>

      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary">Save Catalog</button>
        </div>
      </div>
    </form>
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
        fileInput.disabled = false;
        fileInput.value = '';
      } else if (type === 'pdf') {
        fileInput.accept = 'application/pdf,.pdf';
        fileInput.disabled = false;
        fileInput.value = '';
      } else {
        fileInput.accept = '';
        fileInput.disabled = true;
        fileInput.value = '';
      }
    }

    for (var i = 0; i < typeRadios.length; i++) {
      typeRadios[i].addEventListener('change', function() {
        setAccept(this.value);
      });
    }

    // init on load
    var checked = document.querySelector('input[name="file_type"]:checked');
    setAccept(checked ? checked.value : '');
  })();
</script>
</body>
</html>

