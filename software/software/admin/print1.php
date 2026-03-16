<?php 
require('db.php');
include("auth.php");
$itemcode = $_REQUEST['id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : Stock List</title>
<script src="../js/modernizr-2.6.2.min.js"></script>
<link rel="stylesheet" type="text/css" href="../bootstarp/bootstrap.css" />
<link rel="stylesheet" type="text/css" href="../css/style.css" />
<!-- HTML5 shim for IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
   <script src="../js/html5shiv.min.js"></script>
<![endif]-->
<style media="print">
.noPrint { display:none;}
</style>
</head>

<body>
<button type="button" class="btn btn-sm btn-primary noPrint" onclick="window.print()"><span class="glyphicon glyphicon-print"></span> Print</button> <button type="button" class="btn btn-sm btn-primary noPrint" onclick="window.close()"><span class="glyphicon glyphicon-remove"></span> Close</button>
<div id="content_area">
    <!-- search -->
    <div class="bg-success noPrint" style="padding:20px 20px 10px 20px; margin-bottom:30px;">
      <form method="post" action="" name="search" class="form-inline form-group" id="search" autocomplete="off">
        <div class="form-group">
          <label for="inputPassword2" class="sr-only">search</label>
          <input type="text" class="form-control" id="find" name="find" require placeholder="Item ID">
        </div>
        <button type="submit" name="submit" class="btn btn-primary"><span class="glyphicon glyphicon-search"></span> Filter and Print</button>
      </form>
    </div>
    <!-- table -->
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>ITEM<br>CODE</th>
            <th>DESCRIPTION<br>&nbsp;</th>
            <th>WIDTH<br>(INCHES)</th>
            <th>AVAILABLE<br>QUANTITY</th>
            <th>Price</th>
            <th>Last<br>Updated</th>
          </tr>
        </thead>
        <tbody>
			<?php
			$count=1;
			// show all records
			$sel_query="SELECT * FROM re_product_details where itemcode='$itemcode'";
			$result = mysql_query($sel_query);
			while($row = mysql_fetch_assoc($result)) { ?>
			  <tr>
				<td><?php echo $row["itemcode"]; ?></td>
				<td><?php echo $row["description"]; ?></td>
				<td><?php echo $row["width"]; ?></td>
				<td><?php echo $row["quantity"]; ?></td>
				<td><?php echo $row["price"]; ?></td>
				<td><?php echo $row["trn_date"]; ?></td>
			  </tr>
			 <?php $count++; }?>
        </tbody>
      </table>
    </div>
    <!-- table ends --> 
  </div>

<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
<script type="text/javascript">
  $(document).ready(function() {
    window.print();
	});
  </script> 
</body>
</html>
