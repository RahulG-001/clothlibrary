<?php 
require('db.php');
include("auth.php");
$itemcode = $_REQUEST['itemcode'];
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
<link rel="stylesheet" media="screen" type="text/css" href="../bootstarp/dataTable.css" />
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
    <h1><img src="../images/lc_logo_small.png" width="50" height="51" alt="Kalidioscope" /> The Cloth Library</h1>
  </div>
  <!-- content_area -->
  <div id="content_area">
    <div align="right" style="padding-bottom:10px;" class="noPrint"><?php echo "Hello <b>" .$_SESSION["username"]; ?></b> | <a href="logout.php">
      <button type="button" class="btn btn-sm btn-danger">Logout</button>
      </a></div>
      <!-- filter and print -->
      <div align="right" style="border-top:1px solid #ccc; border-bottom:1px solid #ccc; padding:5px 0px 5px 0px">
        <form class="form-inline" action="filter_print.php" target="_blank">
          <button type="button" onClick='window.open("print1.php?id=<?php echo $itemcode;?>", "_blank", "toolbar=no,scrollbars=yes,resizable=yes,width=800,height=600")' class="btn btn-sm btn-info"><span class="glyphicon glyphicon-print"></span> Print All Stock</button>
        </form>
      </div>	  <h3>View Records</h3>
    <!-- table -->
    <div class="table-responsive yesPrint">
    <form name="form1" method="post" action="" onSubmit="return validate();">
    <!-- <div class="scrollTbl"> -->
      <table class="table table-striped display" id="stockList">
        <thead>
          <tr>
			<th>User Name</th>
            <th>ITEM<br>
              CODE</th>
            <th>DESCRIPTION<br>
              &nbsp;</th>
            <th>WIDTH<br>
              (INCHES)</th>
            <th>AVAILABLE<br>
              QUANTITY</th>
			<th>price</th>
            <th>Last<br>
              Updated</th>
          </tr>
        </thead>
        <tbody>
        <?php
			$count=1;
			$sel_query="SELECT * FROM re_product_details where itemcode='$itemcode'";
			$result = mysqli_query($con,$sel_query);
			while($row = mysqli_fetch_assoc($result)){?>
          <tr>
            <td><?php echo $row["user_id"]; ?></td>
            <td><?php echo $row["itemcode"]; ?></td>
            <td><?php echo $row["description"]; ?></td>
            <td><?php echo $row["width"]; ?></td>
            <td><?php echo $row["quantity"]; ?></td>
            <td><?php echo $row["price"]; ?></td>
            <td><?php echo $row["trn_date"]; ?></td>
          </tr>
          <?php $count++; } ?>
        </tbody>
      </table>
      </form>
    </div>
    <!-- </div> -->
    <!-- table ends --> 
  </div>
  
  <!-- footer -->
  <div id="footer">
    <div class="copyFtr">copyright &copy; 2018 The Cloth Library.</div>
  </div>
  <script src="../js/jquery-1.9.1.min.js"></script> 
  <script src="../bootstarp/bootstrap.min.js"></script>
  <script src="../bootstarp/jquery.dataTables.min.js"></script>
  <script type="text/javascript">
  $(document).ready(function() {
    $('#stockList').DataTable( {
  		responsive: true,
      "pageLength": 50,
      buttons: [
        {
            extend: 'print',
            text: 'Print current page',
            autoPrint: true
        }
    ]
	});
	});

// validate delete button
  function validate() {
    var chks = document.getElementsByName('checkbox[]');
    var hasChecked = false;
    for (var i = 0; i < chks.length; i++)  {
      if (chks[i].checked)  {
        hasChecked = true;
        break;
      }
    }
    if (hasChecked == false)   {
      alert("Please select at least one.");
      return false;
    }

    return true;
  }

// take confirmation for delete button
  function checkDelete(){
    return confirm('Are you sure you want to delete entry?');
  }
  </script> 
</div>
</body>
</html>
