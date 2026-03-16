<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Admin : Login</title>
<script src="../js/modernizr-2.6.2.min.js"></script>
<link rel="stylesheet" type="text/css" href="../bootstarp/bootstrap.css" />
<link rel="stylesheet" type="text/css" href="../css/style.css" />
<link rel="stylesheet" type="text/css" href="../css/application.css" />
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
<!-- page content -->
<div id="mainPanel"> 
  <!-- logo -->
  <div id="logo">
    <h1><img src="../images/lc_logo_small.png" width="50" height="51" alt="Kalidioscope" /> Kalidioscope</h1>
  </div>
  
  <!-- content_area -->
  <div id="content_area">
    <?php
		require('db.php');
		session_start();
		// If form submitted, insert values into the database.
		if (isset($_POST['username'])){			
			// param values
			$username = $_POST['username'];
			$password = $_POST['password'];
			$username = stripslashes($username);
			$username = mysql_real_escape_string($username);
			$password = stripslashes($password);
			$password = mysql_real_escape_string($password);
			
			//Checking is user existing in the database or not
			$query = "SELECT * FROM `re_admin` WHERE userid='$username' and password='$password'";
			$result = mysql_query($query) or die(mysql_error());
			$rows = mysql_num_rows($result);
			
			// check if result comes
			if($rows==1){
				$_SESSION['username'] = $username;
				echo "<script type='text/javascript'>window.location.href = 'dashboard.php';</script>";
				//header("Location: dashboard.php"); // Redirect user to index.php
			} else{
				echo '<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"> </span><span class="sr-only">Error:</span> <strong>Invalid Credentials!</strong> Please provide correct user id and  password.</div>';
			}
		}
		?>
    
    <!-- form -->
    
    <form action="" method="post" name="login" class="form-horizontal" autocomplete="off">
      <div class="form-group">
        <label for="username" class="col-sm-2 control-label">User ID</label>
        <div class="col-sm-10">
          <input type="text" name="username" placeholder="User ID" class="form-control" required />
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
    <div class="copyFtr">copyright &copy; 2017 Kalidioscope.</div>
  </div>
</div>
<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
</body>
</html>
