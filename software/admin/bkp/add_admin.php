<?php
	require('db.php');
	require('auth.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Add Admin</title>
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
  <div id="logo">
    <h1><img src="../images/lc_logo_small.png" width="50" height="51" alt="Lanificio Cucinello" /> Lanificio Cucinello</h1>
  </div>
  
  <!-- content_area -->
  <div id="content_area">
    <div align="right" style="padding-bottom:10px;"><?php echo "Hello <b>" .$_SESSION["username"]; ?></b> | <a href="logout.php">
      <button type="button" class="btn btn-sm btn-danger">Logout</button>
      </a></div>
    <p> <a href="dashboard.php">
      <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-modal-window"></span> Dashboard</button>
      </a> <a href="view_users.php">
      <button type="button" class="btn btn-sm btn-primary">View Users</button>
      </a> <a href="view_admins.php">
      <button type="button" class="btn btn-sm btn-primary">View Admins</button>
      </a></p>
    <h3>Add New User</h3>
    
    <!-- form -->
    <?php
			// If form submitted, insert values into the database.
			if (isset($_POST['userid'])){
				$status = '';
				$userid = $_POST['userid'];
				$password = $_POST['password'];
				$username = stripslashes($username);
				$username = mysql_real_escape_string($username);
				$password = stripslashes($password);
				$password = mysql_real_escape_string($password);
				$query = "INSERT into `admin` (userid, password) VALUES ('$userid', '$password')";
				$result = mysql_query($query);
				if($result){
					$status = '<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon-ok"></span> New Admin has been added successfully.</div>';
				}
			}
		?>
    <?php echo $status; ?> 
    
    <!-- form -->
    <form class="form-horizontal" name="form" method="post" action="" autocomplete="off">
      <div class="form-group">
        <label for="userid" class="col-sm-2 control-label">Admin Id</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="userid" required />
        </div>
      </div>
      <div class="form-group">
        <label for="password" class="col-sm-2 control-label">password</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="password" required />
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary">Add Admin</button>
        </div>
      </div>
    </form>
  </div>
  
  <!-- footer -->
  <div id="footer">
    <p>VIA SAN MARCO 6, OCCHIEPPO SUPERIORE (Bi) ITALY</p>
    copyright &copy; 2000 Lanificio Cucinello. </div>
</div>
<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
</body>
</html>
