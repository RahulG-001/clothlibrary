<?php 
include("auth.php"); //include auth.php file on all secure pages 
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Admin Dashboard</title>
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
<div id="mainPanel">
<!-- logo -->
<div id="logo" style="margin-left:10%; margin-right:10%;text-align:center;"><img src="../images/lc_logo_small.jpg" width="100%" height="80px;" alt="Kalidioscope" />
  </div>

<!-- content_area -->
<div id="content_area">
  <div align="right" style="padding-bottom:10px;"><?php echo "Hello <b>" .$_SESSION["username"]; ?></b> | <a href="logout.php">
    <button type="button" class="btn btn-sm btn-danger">Logout</button>
    </a></div>
  <br>
  <div class="row"> 
    
    <!-- left -->
    <div class="col-sm-5 col-md-6">
      <div class="row">
        <div class="col-xs-4" align="center"><img src="../images/list_icon.gif" width="79" height="100" alt=" "> </div>
        <div class="col-xs-6">
          <p style="padding-bottom:5px; margin-bottom:0px;"><a href="view.php">
            <button type="button" class="btn btn-primary">View Records</button>
            </a></p>
          <p><a href="insert.php">
            <button type="button" class="btn btn-primary">Insert New Item</button>
            </a></p>
          <p><a href="catalog_index.php">
            <button type="button" class="btn btn-primary">Catalog</button>
            </a></p>
            <p>
            <a href="exportcsv.php">
            <button type="button" class="btn btn-primary"><span class="glyphicon glyphicon-download-alt"></span> Export Data</button>
            </a>
            </p>
        </div>
      </div>
    </div>
    
    <!-- right -->
    <div class="col-sm-5 col-sm-offset-2 col-md-6 col-md-offset-0">
      <div class="row">
        <div class="col-xs-4" align="center"><img src="../images/user.png" width="79" height="79" alt=" "></div>
        <div class="col-xs-6">
          <p style="padding-bottom:5px; margin-bottom:0px;"><a href="add_user.php">
            <button type="button" class="btn btn-info">Add User</button>
            </a></p>
          <p><a href="view_users.php">
            <button type="button" class="btn btn-info">View Users</button>
            </a></p>
            

            <p style="padding-bottom:5px; margin-bottom:0px;"><a href="add_admin.php">
            <button type="button" class="btn btn-info">Add Admin</button>
            </a></p>
          <p><a href="view_admins.php">
            <button type="button" class="btn btn-info">View Admins</button>
            </a></p>

            <p style="padding-bottom:5px; margin-bottom:0px;"><a href="add_salesman.php">
            <button type="button" class="btn btn-info">Add Salesman</button>
            </a></p>
          <p><a href="view_salesman.php">
            <button type="button" class="btn btn-info">View Salesman</button>
            </a></p>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- footer -->
 <div id="footer">
    copyright &copy; 2018 The Cloth Library. </div>
<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
</body>
</html>
