<?php 
require('db.php');
include("auth.php");
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
    <h1><img src="../images/lc_logo_small.png" width="50" height="51" alt="Kalidioscope" />Kalidioscope</h1>
  </div>
  
  <!-- content_area -->
  <div id="content_area">
    <div align="right" style="padding-bottom:10px;" class="noPrint"><?php echo "Hello <b>" .$_SESSION["username"]; ?></b> | <a href="logout.php">
      <button type="button" class="btn btn-sm btn-danger">Logout</button>
      </a></div>
    <p> <a href="dashboard.php">
      <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-modal-window"></span> Dashboard</button>
      </a> <a href="view.php">
      <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-th-list"></span> View Items</button>
      </a> <a href="insert.php">
      <button type="button" class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-plus"></span> Insert New Item</button>
      </a>
      
 <button type="button" onClick='window.open("print.php", "_blank", "toolbar=no,scrollbars=yes,resizable=yes,width=800,height=600")' class="btn btn-sm btn-primary"><span class="glyphicon glyphicon-print"></span> Print Stock</button>
      </p>
    <h3>View Records</h3>
    
    <!-- search form 
    <div class="bg-success noPrint" style="padding:20px 20px 10px 20px; margin-bottom:30px;">
      <form method="post" action="" name="search" class="form-inline form-group" id="search" autocomplete="off">
        <div class="form-group">
          <label for="inputPassword2" class="sr-only">search</label>
          <input type="text" class="form-control" id="find" name="find" require placeholder="Item ID">
        </div>
        <button type="submit" name="submit" class="btn btn-primary"><span class="glyphicon glyphicon-search"></span> Filter</button>
      </form>
    </div> -->
    
    <!-- table -->
    <div class="table-responsive yesPrint">
    <!-- <div class="scrollTbl"> -->
      <table class="table table-striped display" id="stockList">
        <thead>
          <tr>
            <th>ITEM<br>
              CODE</th>
            <th>DESCRIPTION<br>
              &nbsp;</th>
            <th>WIDTH<br>
              (INCHES)</th>
            <th>AVAILABLE<br>
              QUANTITY</th>
            <th>Last<br>
              Updated</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
          </tr>
        </thead>
        <tbody>
          <?php
		  if(isset($_POST['find'])) {
		   $find =$_POST['find'];
		   
		   //error for empty search
		   if ($find == "") {
			  echo "<p class='bg-danger'>You forgot to enter a search item!!!<p>";
			  exit;
		   }
		   
		   // filtering
		   $find = strtoupper($find);
		   $find = strip_tags($find);
		   $find = trim ($find);
		   
			// search the database
			$iname = mysql_query("SELECT * FROM re_indiaData WHERE itemcode LIKE '$find%'")
			//$iname = mysql_query("SELECT * FROM indiaData WHERE itemcode IN('$find')")
			or die(mysql_error());
			
			//And we remind them what they searched for
			echo "<p><b>Searched For Item Code:</b> " .$find;
			echo "</p>";
					
			//This counts the number or results - and if there wasn't any it gives them a     little     message explaining that
			$anymatches = mysql_num_rows($iname);
					if ($anymatches == 0)
					// show 0 return error
					{
					echo "<p style='color:#ff0000'><b>Sorry, but we can not find an item to match your search...</b></p>";
					} 
					// display data
					else {
						//And we display the results
						while($result = mysql_fetch_array( $iname )) { 
						
						// heading
						//echo '<p class="dataHeading">ITEM CODE : <strong>' .$result['itemcode'];
						//echo '</strong></h4>';
						
						// item description
						echo '<tr>';
							echo '<td>';
							echo $result['itemcode'];
							echo '</td><td>';
							echo $result['description'];
							echo '</td><td>';
							echo $result['width'];
							echo '</td><td>';
							echo $result['quantity'];
							echo '</td><td>';
							echo $result['trn_date'];
							echo '</td><td><a href="edit.php?itemcode=' .$result["itemcode"];
            				echo '"><span class="glyphicon glyphicon-edit"></span> Edit</a></td><td><a href="delete.php?itemcode=' .$result["itemcode"];
							echo '"><span class="glyphicon glyphicon-trash"></span> Delete</a></td></td></tr>';
						
						}
					}
			} 
			// show all records
			else {
			$count=1;
			$sel_query="SELECT * FROM re_indiaData";
			$result = mysql_query($sel_query);
			while($row = mysql_fetch_assoc($result)) { ?>
          <tr>
            <td><?php echo $row["itemcode"]; ?></td>
            <td><?php echo $row["description"]; ?></td>
            <td><?php echo $row["width"]; ?></td>
            <td><?php echo $row["quantity"]; ?></td>
            <td><?php echo $row["trn_date"]; ?></td>
            <td class="noPrint"><a href="edit.php?itemcode=<?php echo $row["itemcode"]; ?>"><span class="glyphicon glyphicon-edit"></span> Edit</a></td>
            <td class="noPrint"><a href="delete.php?itemcode=<?php echo $row["itemcode"]; ?>"><span class="glyphicon glyphicon glyphicon-trash"></span> Delete</a></td>
          </tr>
          <?php $count++; } } ?>
        </tbody>
      </table>
    </div>
    <!-- </div> -->
    <!-- table ends --> 
  </div>
  
  <!-- footer -->
  <div id="footer">
    <div class="copyFtr">copyright &copy; 2017 Kalidioscope.</div>
  </div>
  <script src="../js/jquery-1.9.1.min.js"></script> 
  <script src="../bootstarp/bootstrap.min.js"></script>
  <script src="../bootstarp/jquery.dataTables.min.js"></script>
  <script type="text/javascript">
  $(document).ready(function() {
    $('#stockList').DataTable( {
		buttons: [
        'print'
    ],
		responsive: true
	});
	});
  </script> 
</div>
</body>
</html>
