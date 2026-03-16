<?php 
require('db.php');
include("auth.php");
$userid=$_REQUEST['userid'];
$query = "SELECT * from admin where userid='".$userid."'"; 
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : change admin password</title>
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
      <a href="view_users.php">
      <button type="button" class="btn btn-sm btn-primary">View Users</button>
      <a href="add_user.php">
      <button type="button" class="btn btn-sm btn-primary">Add New User</button>
      </a>
      <a href="view_admins.php">
      <button type="button" class="btn btn-sm btn-primary">View Admins</button>
      <a href="add_admin.php">
      <button type="button" class="btn btn-sm btn-primary">Add New Admin</button>
      </a>
    
    </p>
      
    <h3>Update Record</h3>
    <?php
$status = "";
if(isset($_POST['new']) && $_POST['new']==1) {

	$userid =$_REQUEST['userid'];
	$password = $_REQUEST['password'];

	$update="update admin set password='".$password."' where userid='".$userid."'";
	
	mysqli_query($con,$update);
	
	$status = "Password changed Successfully. <a href='view_admins.php'>Back to admin list</a>";	
	echo '<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon-ok"></span> '.$status.'</div>';
	
	}else {
	?>
    <form class="form-horizontal" name="form" method="post" action="" autocomplete="off">
      <input type="hidden" name="new" value="1" />
      
      
      <div class="form-group">
            <label for="userid" class="col-sm-2 control-label">User Id</label>
            <div class="col-sm-10">
              <input type="text" name="userid" class="form-control" placeholder="Enter user id" disabled value="<?php echo $row['userid'];?>" />
            </div>
      </div>
      
      <div class="form-group">
            <label for="password" class="col-sm-2 control-label">Password</label>
            <div class="col-sm-10">
              <input type="text" name="password" class="form-control" placeholder="Enter password" required value="<?php echo $row['password'];?>" />
            </div>
      </div>

      <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
      </div>

    </form>
    <?php } ?>
  </div>
  
  <!-- footer -->
  <div id="footer">
    copyright &copy; 2018 The Cloth Library. </div>
</div>
</div>
</body></html>
