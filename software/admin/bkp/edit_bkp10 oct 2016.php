<?php 
require('db.php');
include("auth.php");
$itemcode=$_REQUEST['itemcode'];
$query = "SELECT * from indiadata where itemcode='".$itemcode."'"; 
$result = mysql_query($query) or die ( mysql_error());
$row = mysql_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Update Item</title>
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
      </a> 
      <a href="view.php">
      <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-th-list"></span> View Items</button>
      </a>
      <a href="insert.php">
      <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-plus"></span> Insert New Item</button>
      </a> </p>
      
    <h3>Update Record</h3>
    <?php
$status = "";
if(isset($_POST['new']) && $_POST['new']==1) {
	$itemcode =$_REQUEST['itemcode'];
	$description = $_REQUEST['description'];
	$width = $_REQUEST['width'];
	$quantity = $_REQUEST['quantity'];
	$country = $_REQUEST['country'];
	
	$update="update indiadata set description='".$description."', width='".$width."', quantity='".$quantity."', country='".$country."' where itemcode='".$itemcode."'";
	
	mysql_query($update) or die(mysql_error());
	
	$status = "Item Updated Successfully. <a href='view.php'>View Updated Record</a>";	
	echo '<div class="alert alert-success" role="alert"><span class="glyphicon glyphicon-ok"></span> '.$status.'</div>';
	
	}else {
	?>
    <form class="form-horizontal" name="form" method="post" action="" autocomplete="off">
      <input type="hidden" name="new" value="1" />
      <!--<input name="itemcode" type="hidden" value="<?php echo $row['itemcode'];?>" /> -->
      <div class="form-group">
            <label for="itemcode" class="col-sm-2 control-label">item code</label>
            <div class="col-sm-10">
              <input type="text" name="itemcode" class="form-control" placeholder="Enter ItemCode" disabled value="<?php echo $row['itemcode'];?>" />
            </div>
      </div>
      
      <div class="form-group">
            <label for="description" class="col-sm-2 control-label">description</label>
            <div class="col-sm-10">
              <input type="text" name="description" class="form-control" placeholder="Enter description" required value="<?php echo $row['description'];?>" />
            </div>
      </div>
      
      <div class="form-group">
            <label for="width" class="col-sm-2 control-label">Width</label>
            <div class="col-sm-10">
              <input type="text" name="width" class="form-control" placeholder="Enter width" required value="<?php echo $row['width'];?>" />
            </div>
      </div>
      
      <div class="form-group">
            <label for="quantity" class="col-sm-2 control-label">Quantity</label>
            <div class="col-sm-10">
              <input type="text" name="quantity" class="form-control" placeholder="Enter Quantity" required value="<?php echo $row['quantity'];?>" />
            </div>
      </div>
      
      <div class="form-group">
            <label for="country" class="col-sm-2 control-label">Country</label>
            <div class="col-sm-10">
              <input type="text" name="country" class="form-control" placeholder="Enter country" required value="<?php echo $row['country'];?>" />
            </div>
      </div>

      <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" class="btn btn-primary">Update Item</button>
            </div>
      </div>

    </form>
    <?php } ?>
  </div>
  
  <!-- footer -->
  <div id="footer">
    IA SAN MARCO 6, OCCHIEPPO SUPERIORE (Bi) ITALY<br /><div class="copyFtr">copyright &copy; 2000 Lanificio Cucinello.</div>
     </div>
</div>
</div>
</body></html>
