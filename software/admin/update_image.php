<?php 
require('db.php');
include("auth.php");
$itemcode=$_REQUEST['itemcode'];
$query = "SELECT * from indiaData where itemcode='".$itemcode."'"; 
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Update image</title>
<script src="../js/modernizr-2.6.2.min.js"></script>
<link rel="stylesheet" type="text/css" href="../bootstarp/bootstrap.css" />
<link rel="stylesheet" type="text/css" href="../css/style.css" />
<!-- HTML5 shim for IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
   <script src="../js/html5shiv.min.js"></script>
<![endif]-->
</head>
<!-- top nav -->
  <ul class="topNav">
    	<li><a class="admin" href="https://theclothlibrary.com/">Home</a></li>
        <li><a class="admin" href="../region.html">User Login</a></li>
        <li><a class="admin selected" href="login.php">Admin Login</a></li>
        <div class="clear"></div>
    </ul>
<div id="mainPanel"> 
  <!-- logo -->
<div id="logo" style="margin-left:10%; margin-right:10%;text-align:center;"><img src="../images/lc_logo_small.jpg" width="100%" height="80px;" alt="Kalidioscope" />
  </div>
  
  <!-- content_area -->
  <div id="content_area">
    <div align="right" style="padding-bottom:10px;"><?php echo "Hello <b>" .$_SESSION["username"]; ?></b> | <a href="logout.php">
      <button type="button" class="btn btn-sm btn-danger">Logout</button>
      </a></div>
      
      <p> <a href="dashboard.php">
      <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-modal-window"></span> Dashboard</button>
      </a> 
      <a href="view.php">
      <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-th-list"></span> View Items</button>
      </a>
      <a href="insert.php">
      <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-plus"></span> Insert New Item</button>
      </a> </p>
      
    <h3>Update Image for Item Code: <?php echo $row['itemcode'];?></h3>
    <?php
$status = "";
if(isset($_POST['new']) && $_POST['new']==1) {

	$itemcode =$_REQUEST['itemcode'];

    // image processing and validations
    $target_dir = "../item_images/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
    if($check !== false) {
       // echo "File is an image - " . $check["mime"] . ".";
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }

        // Check if file already exists
       // if (file_exists($target_file)) {
      //  echo "Sorry, file already exists.";
      //  $uploadOk = 0;
      //  }

        // Check file size
        if ($_FILES["fileToUpload"]["size"] > 1000000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
        }

        // Allow certain file formats
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
        }

        $image = $_FILES["fileToUpload"]["name"];

    if ($uploadOk == 0) {
        $status = '<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"> </span><span class="sr-only">Error:</span> <strong>Sorry. Uploaded image was not a correct image file</strong></div>';
        echo '<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-ok"></span> '.$status.'</div>';
    } else {
        $update="update indiaData set image='".$image."' where itemcode='".$itemcode."'";
        mysqli_query($con,$update);

        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $status = "The file ". htmlspecialchars( basename( $_FILES["fileToUpload"]["name"])). " has been uploaded.";
          } else {
            $status = "Sorry, there was an error uploading your file.";
          }
        echo '<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon-ok"></span> '.$status.'</div>';
    }
	}   
    else {
	?>

  <div class="row">

    <div class="col-sm-5 col-md-6">
      <div class="row">
        <p>Current Image:<br>
        <img class="img-responsive"  src="../item_images/<?php echo $row["image"]; ?>" />
      </div>
    </div>

    <div class="col-sm-5 col-md-6">
      <div class="row">

      <form class="form-horizontal" name="form" method="post" action="" autocomplete="off" enctype="multipart/form-data">
      <input type="hidden" name="new" value="1" />
      
      <div class="form-group">
        <label for="fileToUpload" class="col-sm-2 control-label">Select new image</label>
        <div class="col-sm-10">
            <input type="file" name="fileToUpload" id="fileToUpload" class="form-control" required />
        </div>
        </div>

      <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" class="btn btn-primary">Update Image</button>
            </div>
      </div>

    </form>


      </div>
    </div>

  </div>
    <?php } ?>
  </div>
  
  <!-- footer -->
  <div id="footer">
    copyright &copy; 2018 The Cloth Library. </div>
</div>
</div>
</body></html>
