<?php
	require('db.php');
	require('auth.php');

function ensure_salesman_phone_text_column($con) {
  $r = mysqli_query($con, "SHOW COLUMNS FROM `salesman` LIKE 'phone'");
  if (!$r || mysqli_num_rows($r) === 0) {
    return false;
  }
  $col = mysqli_fetch_assoc($r);
  $type = isset($col['Type']) ? strtolower((string)$col['Type']) : '';
  if (strpos($type, 'varchar') === false && strpos($type, 'char') === false && strpos($type, 'text') === false) {
    return mysqli_query($con, "ALTER TABLE `salesman` MODIFY `phone` VARCHAR(30) NOT NULL");
  }
  return true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Add Salesman</title>
<script src="../js/modernizr-2.6.2.min.js"></script>
<link rel="stylesheet" type="text/css" href="../bootstarp/bootstrap.css" />
<link rel="stylesheet" type="text/css" href="../css/style.css" />
<!--[if lt IE 9]>
   <script src="../js/html5shiv.min.js"></script>
<![endif]-->
</head>

<body>
<ul class="topNav">
  <li><a class="admin" href="https://theclothlibrary.com">Home</a></li>
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
      </a>
    </div>
    <p>
      <a href="dashboard.php">
        <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-modal-window"></span> Dashboard</button>
      </a> 
      <a href="view_salesman.php">
        <button type="button" class="btn btn-sm btn-primary">View Salesman</button>
      </a>
    </p>
    <h3>Add New Salesman</h3>
    
    <?php
      if (isset($_POST['first_name'])){
        $status = '';
        if (!ensure_salesman_phone_text_column($con)) {
          $status = '<div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-warning-sign"></span> Could not prepare phone field type in DB.</div>';
        }
        $first_name = mysqli_real_escape_string($con, stripslashes($_POST['first_name']));
        $last_name  = mysqli_real_escape_string($con, stripslashes($_POST['last_name']));
        $phone      = mysqli_real_escape_string($con, stripslashes($_POST['phone']));
        $password   = mysqli_real_escape_string($con, stripslashes($_POST['password']));

        if($status === '' && ($first_name =='' || $last_name =='' || $phone=='' || $password =='') ){
          $status = '<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon-ok"></span>Something is missing.</div>';
        }elseif ($status === ''){
          $query = "INSERT into `salesman` (first_name, last_name, phone, password) VALUES ('$first_name', '$last_name', '$phone', '$password')";
          $result = mysqli_query($con,$query);
          if($result){
            $status = '<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon-ok"></span> New Salesman has been added successfully.</div>';
          }
        }
      }
    ?>
    <?php echo isset($status) ? $status : ''; ?> 
    
    <form class="form-horizontal" name="form" method="post" action="" autocomplete="off">
      <div class="form-group">
        <label for="first_name" class="col-sm-2 control-label">First Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="first_name" required />
        </div>
      </div>
      <div class="form-group">
        <label for="last_name" class="col-sm-2 control-label">Last Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="last_name" required />
        </div>
      </div>
      <div class="form-group">
        <label for="phone" class="col-sm-2 control-label">Phone Number</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="phone" required />
        </div>
      </div>
      <div class="form-group">
        <label for="password" class="col-sm-2 control-label">Password</label>
        <div class="col-sm-10">
          <input type="text" class="form-control" name="password" required />
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <button type="submit" class="btn btn-primary">Add Salesman</button>
        </div>
      </div>
    </form>
  </div>
  
  <div id="footer">
    copyright &copy; 2018 The Cloth Library.
  </div>
</div>
<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
</body>
</html>

