<?php
require('db.php');
$status = "";
if(isset($_POST['new']) && $_POST['new']==1){ 
   // change time zone to india
	date_default_timezone_set('Asia/Kolkata');
	$trn_date = date("Y-m-d H:i:s");
	$password=$_REQUEST['password'];
	$cpassword=$_REQUEST['cpassword'];
    $check=mysqli_query($con,"select * from re_admin where password='$password'");
	//$check="SELECT * FROM indiaData where itemcode='$itemcode'";
	$duplicate= mysqli_num_rows($check);
	// incase itemid is present
	if($duplicate>0){  
		$ins_query="UPDATE `re_admin` SET `password`='$cpassword'";
		mysqli_query($con,$ins_query);
		$status = "<div class='alert alert-success' role='alert'><span class='glyphicon glyphicon-ok'></span> Password Successfully Change.</div>";
	}else{      
		$status = '<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"> </span><span 	class="sr-only">Error:</span> <strong>Duplicate Entry: Item ID already available in database.</strong></div>';
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
      <div align="right" style="padding-bottom:10px;">
		<?php echo "Hello <b>" .$_SESSION["username"]; ?></b> | <a href="logout.php">
		<button type="button" class="btn btn-sm btn-danger">Logout</button>
		</a>
	  </div>
        <h3>Admin Change Password</h3>
			<?php echo $status; ?>
        <form class="form-horizontal" name="form" method="post" action="" autocomplete="off">
			<input type="hidden" name="new" value="1" />
			<div class="form-group">
				<label for="itemcode" class="col-sm-2 control-label">Old Password</label>
				<div class="col-sm-10">
					<input type="text" name="password" class="form-control" required />
				</div>
			</div>
			<div class="form-group">
				<label for="itemcode" class="col-sm-2 control-label">Change Password</label>
				<div class="col-sm-10">
					<input type="text" name="cpassword" class="form-control" required />
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-primary">Submit</button>
				</div>
			</div>
        </form>
  </div>
  <!-- footer -->
  <div id="footer">
    copyright &copy; 2018 The Cloth Library. 
  </div>
</div>

<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
</body>
</html>
