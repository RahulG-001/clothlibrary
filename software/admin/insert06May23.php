<?php
require('db.php');
include("auth.php");

$status = "";

if(isset($_POST['new']) && $_POST['new']==1) {
	
  // change time zone to india
  date_default_timezone_set('Asia/Kolkata');
  
	$trn_date = date("Y-m-d H:i:s");
	$itemcode =mysqli_real_escape_string($con,$_REQUEST['itemcode']);
	$description = mysqli_real_escape_string($con,$_REQUEST['description']);
	$width = mysqli_real_escape_string($con,$_REQUEST['width']);
	$quantity = mysqli_real_escape_string($con,$_REQUEST['quantity']);
	$country = mysqli_real_escape_string($con,$_REQUEST['country']);


  $check=mysqli_query($con,"select * from indiadata where itemcode='$itemcode'") or die(mysql_error());

  //$check="SELECT * FROM indiadata where itemcode='$itemcode'";
  $duplicate= mysqli_num_rows($check);

  // incase itemid is present
  if($duplicate>0) {
    $status = '<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"> </span><span class="sr-only">Error:</span> <strong>Duplicate Entry: Item ID already available in database.</strong></div>';
  } 
  // incase itemid is new
  else {
  	$ins_query="insert into indiadata(`trn_date`, `itemcode`,`description`,`width`,`quantity`,`country`)values('$trn_date', '$itemcode','$description','$width','$quantity','$country')";
  	
  	mysqli_query($con,$ins_query);
  	
  	$status = "<div class='alert alert-success' role='alert'><span class='glyphicon glyphicon-ok'></span> New Item added Successfully. <a href='view.php'>View Inserted Record</a></div>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Insert Item</title>
<script src="../js/modernizr-2.6.2.min.js"></script>
<link rel="stylesheet" type="text/css" href="../bootstarp/bootstrap.css" />
<link rel="stylesheet" type="text/css" href="../css/style.css" />
<!-- HTML5 shim for IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
   <script src="../js/html5shiv.min.js"></script>
<![endif]-->
</head>

<body>
<!-- top nav -->
  <ul class="topNav">
    	<li><a class="admin" href="../home.html">Home</a></li>
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
      </a> <a href="view.php">
      <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-th-list"></span> View Items</button>
      </a> </p>
        
        <h3>Insert New Record</h3>
        
        <!-- status -->
        <?php echo $status; ?>
        
        <!-- form -->
        <form class="form-horizontal" name="form" method="post" action="" autocomplete="off">
        <input type="hidden" name="new" value="1" />
          <div class="form-group">
            <label for="itemcode" class="col-sm-2 control-label">Item Code</label>
            <div class="col-sm-10">
              <input type="text" name="itemcode" class="form-control" required />
            </div>
          </div>
          <div class="form-group">
            <label for="description" class="col-sm-2 control-label">Description</label>
            <div class="col-sm-10">
              <input type="text" name="description" class="form-control" required />
            </div>
          </div>
          <div class="form-group">
            <label for="width" class="col-sm-2 control-label">Width (Inches)</label>
            <div class="col-sm-10">
              <input type="text" name="width" class="form-control" required />
            </div>
          </div>
          <div class="form-group">
            <label for="quantity" class="col-sm-2 control-label">Available Quantity</label>
            <div class="col-sm-10">
              <input type="text" name="quantity" class="form-control" required />
            </div>
          </div>
          <div class="form-group">
            <label for="country" class="col-sm-2 control-label">Country</label>
            <div class="col-sm-10">
              <input type="text" name="country" class="form-control" value="India" required />
            </div>
          </div>
          
          <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" class="btn btn-primary">Add Item</button>
            </div>
          </div>
        </form>
  </div>
  
  <!-- footer -->
   <div id="footer">
    copyright &copy; 2018 The Cloth Library. </div>
</div>

<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
</body>
</html>
