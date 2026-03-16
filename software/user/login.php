<?php
    require('../admin/db.php');
    session_start();
    // If form submitted, insert values into the database.
    if (isset($_POST['userid'])){     
      // param values
        $username = $_POST['userid'];
        $password = $_POST['password'];
        $username = stripslashes($username);
    	$userid = mysqli_real_escape_string($con,$username);
    	$password = stripslashes($password);
    	$password = mysqli_real_escape_string($con,$password);
      //Checking is user existing in the database or not
      $query = "SELECT * FROM users WHERE 1=1 and userid='$userid' and password='$password'";
      $result = mysqli_query($con,$query);
      $count = mysqli_num_rows($result);
      // check if result comes
      if($count==1){
        $_SESSION['userid'] = $userid;
        header("location: view_stock.php");
      } else {
        echo '<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"> </span><span class="sr-only">Error:</span> <strong>Invalid Credentials!</strong> Please provide correct user id and  password.</div>';
      }
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : User : Login</title>
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
  <li><a class="admin selected" href="../region.html">User Login</a></li>
  <li><a class="admin" href="../admin/login.php">Admin Login</a></li>
  <div class="clear"></div>
</ul>
<!-- page content -->
<div id="mainPanel"> 
  <!-- logo -->
 <div id="logo" style="margin-left:10%; margin-right:10%;text-align:center;"><img src="../images/lc_logo_small.jpg" width="100%" height="80px;" alt="Kalidioscope" />
  </div>
  
  <!-- content_area -->
  <div id="content_area">
    
    
    <!-- form -->
    
    <form action="" method="post" name="login" class="form-horizontal" autocomplete="off">
      <div class="form-group">
        <label for="userid" class="col-sm-2 control-label">User ID</label>
        <div class="col-sm-10">
          <input type="text" name="userid" placeholder="User ID" class="form-control" required />
        </div>
      </div>
      <div class="form-group">
        <label for="password" class="col-sm-2 control-label">Password</label>
        <div class="col-sm-10">
          <input type="password" name="password" placeholder="Password" class="form-control" required />
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary">Sign in</button>
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
