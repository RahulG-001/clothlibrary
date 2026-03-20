<?php 
	require('../admin/db.php');
	include("auth.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LC : View Stock</title>
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
        <li><a class="admin selected" href="../region.html">User Login</a></li>
        <li><a class="admin" href="../admin/login.php">Admin Login</a></li>
        <div class="clear"></div>
    </ul>
<!-- page content -->
<div id="mainPanel"> 

  <!-- logo -->
	<div id="logo">
		<h1><img src="../images/lc_logo_small.png" width="50" height="51" alt=" Kalidioscope" />Kalidioscope</h1>
    </div>
  
  <!-- content_area -->
  <div id="content_area">

    <div align="right"><?php echo "Hello <b>" .$_SESSION["userid"]; ?></b> | <a href="logout.php">
      <button type="button" class="btn btn-sm btn-danger">Logout</button>
      </a></div>
      
      <h3>View Records</h3>
    
    <!-- search form -->
    <div class="bg-success" style="padding:20px 20px 10px 20px; margin-bottom:30px;">
    <form method="post" action="" name="search" class="form-inline form-group" id="search" autocomplete="off">
    	<div class="form-group">
        	<label class="sr-only">search</label>
        	<p class="form-control-static">Search Stock by Item Code</p>
      	</div>
      <div class="form-group">
        <label for="inputPassword2" class="sr-only">search</label>
        <input type="text" class="form-control" id="find" name="find" require placeholder="Item Code">
      </div>
      <button type="submit" name="submit" class="btn btn-primary">search</button>
    </form>
    </div>
    
    <!-- dynamic data from search -->
    <div class="table-responsive">
      <?php 
		require('../admin/db.php');
		include("auth.php");
		
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
			$iname = mysql_query("SELECT * FROM re_indiadata WHERE itemcode LIKE '%$find%'")
			or die(mysql_error());
			
			//And we remind them what they searched for
			echo "<p><b>Searched For Item Code:</b> " .$find;
			echo "</p>";
					
			//This counts the number or results - and if there wasn't any it gives them a     little     message explaining that
			$anymatches = mysql_num_rows($iname);
					if ($anymatches == 0)
					// show 0 return error
					{
					echo "<p>Sorry, but we can not find an item to match your search...</p>";
					} 
					// display data
					else {
						//And we display the results
						while($result = mysql_fetch_array( $iname )) { 
						
						// heading
						//echo '<p class="dataHeading">ITEM CODE : <strong>' .$result['itemcode'];
						//echo '</strong></h4>';
						
						// item description
						echo '<table class="table table-striped" style="margin-bottom:30px">';
							echo '<tr style="background-color:#ccc"><th>ITEM CODE</th><td>';
							echo $result['itemcode'];
							echo '</td></tr><tr><th>DESCRIPTION</th><td>';
							echo $result['description'];
							echo '</td></tr><tr><th>WIDTH(INCHES)</th><td>';
							echo $result['width'];
							echo '</td></tr><tr><th>AVAILABLE QUANTITY</th><td>';
							echo $result['quantity'];
							echo '</td></tr><tr><td align="right" colspan="2">Last Updated : <em>';
							echo $result['trn_date'];
							echo '</em></td></tr>';
						echo '</table>';
						
						}
						
						
					}

			}
		?>
    </div>
    <!-- table ends --> 
  </div>
  
  <!-- footer -->
  <div id="footer">
    <div class="copyFtr">copyright &copy; 2017  Kalidioscope.</div>
     </div>
<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
</body>
</html>
