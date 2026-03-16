<?php 
require('db.php');
include("auth.php");
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$query = "SELECT * from salesman where id='".$id."'"; 
$result = mysqli_query($con,$query);
$row = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Change salesman password</title>
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
    <div align="right" style="padding-bottom:10px;">
      <?php echo "Hello <b>" .$_SESSION["username"]; ?></b> | <a href="logout.php">
        <button type="button" class="btn btn-sm btn-danger">Logout</button>
      </a></div>
      
    <p>
      <a href="dashboard.php">
        <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-modal-window"></span> Dashboard</button>
      </a> 
      <a href="view_salesman.php">
        <button type="button" class="btn btn-sm btn-primary">View Salesman</button>
      </a>
      <a href="add_salesman.php">
        <button type="button" class="btn btn-sm btn-primary">Add New Salesman</button>
      </a>
    </p>
      
    <h3>Update Salesman Details</h3>
    <?php
      $status = "";
      if(isset($_POST['new']) && $_POST['new']==1) {
        $id = (int)$_REQUEST['id'];
        $first_name = mysqli_real_escape_string($con, stripslashes($_POST['first_name']));
        $last_name  = mysqli_real_escape_string($con, stripslashes($_POST['last_name']));
        $phone      = mysqli_real_escape_string($con, stripslashes($_POST['phone']));
        $password   = mysqli_real_escape_string($con, stripslashes($_POST['password']));

        $update="UPDATE salesman 
                 SET first_name='".$first_name."',
                     last_name='".$last_name."',
                     phone='".$phone."',
                     password='".$password."'
                 WHERE id='".$id."'";
        mysqli_query($con,$update);
        $status = "Salesman details updated successfully. <a href='view_salesman.php'>Back to salesman list</a>"; 
        echo '<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon-ok"></span> '.$status.'</div>';
      } else {
    ?>
    <form class="form-horizontal" name="form" method="post" action="" autocomplete="off">
      <input type="hidden" name="new" value="1" />
      <input type="hidden" name="id" value="<?php echo $row['id']; ?>" />

      <div class="form-group">
        <label for="first_name" class="col-sm-2 control-label">First Name</label>
        <div class="col-sm-10">
          <input type="text" name="first_name" class="form-control" required value="<?php echo $row['first_name'];?>" />
        </div>
      </div>

      <div class="form-group">
        <label for="last_name" class="col-sm-2 control-label">Last Name</label>
        <div class="col-sm-10">
          <input type="text" name="last_name" class="form-control" required value="<?php echo $row['last_name'];?>" />
        </div>
      </div>

      <div class="form-group">
        <label for="phone" class="col-sm-2 control-label">Phone Number</label>
        <div class="col-sm-10">
          <input type="text" name="phone" class="form-control" required value="<?php echo $row['phone'];?>" />
        </div>
      </div>

      <div class="form-group">
        <label for="password" class="col-sm-2 control-label">Password</label>
        <div class="col-sm-10">
          <input type="text" name="password" class="form-control" required value="<?php echo $row['password'];?>" />
        </div>
      </div>

      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary">Update Salesman</button>
        </div>
      </div>
    </form>
    <?php } ?>
  </div>
  
  <div id="footer">
    copyright &copy; 2018 The Cloth Library.
  </div>
</div>

</body>
</html>

