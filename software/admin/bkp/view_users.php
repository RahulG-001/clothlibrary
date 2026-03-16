<?php 
require('db.php');
include("auth.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : View Users</title>
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
    	<li><a class="admin" href="https://theclothlibrary.com/">Home</a></li>
        <li><a class="admin" href="../region.html">User Login</a></li>
        <li><a class="admin selected" href="login.php">Admin Login</a></li>
        <div class="clear"></div>
    </ul>
<!-- page content -->
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
      </a> <a href="add_user.php">
      <button type="button" class="btn btn-sm btn-primary">Add New User</button>
      </a></p>
      
    <h3>View Users</h3>
    
    <!-- table -->
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>User ID</th>
            <th>Password</th>
            <th>&nbsp;</th>
          </tr>
        </thead>
        <tbody>
          <?php
			$count=1;
			$sel_query="SELECT * FROM users";
			$result = mysqli_query($con,$sel_query);
			while($row = mysqli_fetch_assoc($result)) { ?>
          <tr>
            <td><?php echo $row["userid"]; ?></td>
            <td><?php echo $row["password"]; ?></td>
            <td><a onclick="return checkDelete()" href="delete_user.php?userid=<?php echo $row["userid"]; ?>"><span class="glyphicon glyphicon glyphicon-remove"></span> Delete</a></td>
          </tr>
          <?php $count++; } ?>
        </tbody>
      </table>
    </div>
    <!-- table ends --> 
  </div>
  
  <!-- footer -->
 <div id="footer">
    copyright &copy; 2018 The Cloth Library. </div>
<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
<script type="text/javascript">
  // take confirmation for delete button
  function checkDelete(){
    return confirm('Are you sure you want to delete Admin?');
  }
</script>
</body>
</html>
