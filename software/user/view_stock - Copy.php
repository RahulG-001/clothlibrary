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

<!-- page content -->
<div id="mainPanel"> 
  <!-- top nav -->
  <ul class="topNav">
    	<li><a class="admin" href="../home.html">Home</a></li>
        <li><a class="admin" href="../region.html">User Login</a></li>
        <li><a class="admin" href="../admin/login.php">Admin Login</a></li>
        <div class="clear"></div>
    </ul>
  
  <!-- logo -->
  <div id="logo">
    <h1><span>Lanificio Cucinello</span></h1>
    <h2><span>Lanificio Cucinello</span></h2>
    <div class="clear"></div>
  </div>
  
  <!-- content_area -->
  <div id="content_area">
	<p align="right"><?php echo "hello" .$_SESSION["username"]; ?></p>
    <h3>View Records</h3>
    
    <!-- search form -->
    <div class="bg-success" style="padding:20px 20px 10px 20px; margin-bottom:30px;">
    <form method="post" action="" name="search" class="form-inline form-group form-group-lg" id="search" autocomplete="off">
      <div class="form-group">
        <label for="inputPassword2" class="sr-only">search</label>
        <input type="text" class="form-control" id="find" name="find" require placeholder="search...">
      </div>
      <button type="submit" name="submit" class="btn btn-primary">search</button>
    </form>
    </div>
    
    <!-- dynamic data from search -->
    <div class="table-responsive">
      <?php 
		require('../stock/db.php');
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
			$iname = mysql_query("SELECT * FROM indiaData WHERE itemcode LIKE '$find'")
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
						
						 echo '<table class="table table-striped">';
						echo '<thead>';
						echo '<tr>';
						echo '<th>ITEM<br>CODE</th>';
						echo '<th>DESCRIPTION<br>&nbsp;</th>';
						echo '<th>WIDTH<br>(INCHES)</th>';
						echo '<th>AVAILABLE<br>QUANTITY</th>';
						echo '<th>Last<br>Updated</th>';
						echo '<th>&nbsp;</th>';
						echo '<th>&nbsp;</th>';
						echo '</tr>';
						echo '</thead>';
						echo '<tbody>';
						
						//And we display the results
						while($result = mysql_fetch_array( $iname )) { 
								// desktop view
								echo "<tr class='desktopData'>";
								echo "<td>" .$result['itemcode'];
								echo "</td><td>" .$result['description'];
								echo "</td><td>" .$result['width'];
								echo "</td><td>" .$result['quantity'];
								echo "</td><td>" .$result['trn_date'];
								echo "</tr>";
								
								
								// mobile view
								echo '<div class="mobileData">';
									echo '<div class="row">';
										echo '<div class="col-xs-6 col-sm-4">ITEM CODE</div>';
										echo '<div class="col-xs-6 col-sm-4">' .$result['itemcode'];
										echo '</div><div class="clearfix visible-xs-block"></div>';
									echo '</div>';
									echo '<div class="row">';
										echo '<div class="col-xs-6 col-sm-4">DESCRIPTION</div>';
										echo '<div class="col-xs-6 col-sm-4">' .$result['description'];
										echo '</div><div class="clearfix visible-xs-block"></div>';
									echo '</div>';
									echo '<div class="row">';
										echo '<div class="col-xs-6 col-sm-4">WIDTH(INCHES)</div>';
										echo '<div class="col-xs-6 col-sm-4">' .$result['width'];
										echo '</div><div class="clearfix visible-xs-block"></div>';
									echo '</div>';
									echo '<div class="row">';
										echo '<div class="col-xs-6 col-sm-4">AVAILABLE QUANTITY</div>';
										echo '<div class="col-xs-6 col-sm-4">' .$result['quantity'];
										echo '</div><div class="clearfix visible-xs-block"></div>';
									echo '</div>';
									echo '<div class="row">';
										echo '<div class="col-xs-6 col-sm-4">Last Updated</div>';
										echo '<div class="col-xs-6 col-sm-4">' .$result['trn_date'];
										echo '</div><div class="clearfix visible-xs-block"></div>';
									echo '</div>';
								echo '</div>';
						}
						
						echo '</tbody>';
      					echo '</table>';
						
						
					}

			}
		?>
    </div>
    <!-- table ends --> 
  </div>
  
  <!-- footer -->
  <div id="footer">
    IA SAN MARCO 6, OCCHIEPPO SUPERIORE (Bi) ITALY<br /><div class="copyFtr">copyright &copy; 2000 Lanificio Cucinello.</div>
     </div>
<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>
</body>
</html>
