<?php 
require('db.php');
include("auth.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : View List</title>
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
<!-- page content -->
<div id="mainPanel"> 

  <!-- logo -->
  <div id="logo">
    <h1><img src="../images/lc_logo_small.png" width="50" height="51" alt="Kalidioscope" /> Kalidioscope</h1>
  </div>
  
  <!-- content_area -->
  <div id="content_area">
  <div align="right" style="padding-bottom:10px;"><?php echo "Hello <b>" .$_SESSION["username"]; ?></b> | <a href="logout.php">
      <button type="button" class="btn btn-sm btn-danger">Logout</button>
      </a></div>
    <p> <a href="dashboard.php">
      <button type="button" class="btn btn-sm btn-primary">Dashboard</button>
      </a> <a href="insert.php">
      <button type="button" class="btn btn-sm btn-primary">Insert New Item</button>
      </a></p>
      
    <h3>View Records</h3>
    
    <!-- table -->
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ITEM<br>CODE</th>
            <th>DESCRIPTION<br>&nbsp;</th>
            <th>WIDTH<br>(INCHES)</th>
            <th>AVAILABLE<br>QUANTITY</th>
            <th>Last<br>Updated</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
          </tr>
        </thead>
        <tbody>
          <?php
			$count=1;
			$sel_query="SELECT * FROM re_indiadata";
			$result = mysql_query($sel_query);
			while($row = mysql_fetch_assoc($result)) { ?>
          
          <tr>
            <td><?php echo $row["itemcode"]; ?></td>
            <td><?php echo $row["description"]; ?></td>
            <td><?php echo $row["width"]; ?></td>
            <td><?php echo $row["quantity"]; ?></td>
            <td><?php echo $row["trn_date"]; ?></td>
            
            <td><a href="edit.php?itemcode=<?php echo $row["itemcode"]; ?>"><span class="glyphicon glyphicon-edit"></span> Edit</a></td>
            <td><a href="delete.php?itemcode=<?php echo $row["itemcode"]; ?>"><span class="glyphicon glyphicon glyphicon-remove"></span> Delete</a></td>
          </tr>
          <?php $count++; } ?>
        </tbody>
      </table>
    </div>
    <!-- table ends --> 
  </div>
  
  <!-- footer -->
  <div id="footer">
    <div class="copyFtr">copyright &copy; 2017 Kalidioscope.</div>
  </div>
<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
</body>
</html>
