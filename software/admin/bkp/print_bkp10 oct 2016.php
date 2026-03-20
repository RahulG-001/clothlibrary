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
<!-- HTML5 shim for IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
   <script src="../js/html5shiv.min.js"></script>
<![endif]-->
<style media="print">
.noPrint { display:none; }
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
            <th>Last<br>Updated</th>
          </tr>
        </thead>
        <tbody>
          <?php
      			$count=1;

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
      $iname = mysql_query("SELECT * FROM indiadata WHERE itemcode LIKE '$find%'")
      //$iname = mysql_query("SELECT * FROM indiadata WHERE itemcode IN('$find')")
      or die(mysql_error());
          
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
              echo '</td></tr>';
            
            }
          }
      } 
      // show all records
      else {

      			$sel_query="SELECT * FROM indiadata";
      			$result = mysql_query($sel_query);
      			while($row = mysql_fetch_assoc($result)) { ?>
          
          <tr>
            <td><?php echo $row["itemcode"]; ?></td>
            <td><?php echo $row["description"]; ?></td>
            <td><?php echo $row["width"]; ?></td>
            <td><?php echo $row["quantity"]; ?></td>
            <td><?php echo $row["trn_date"]; ?></td>
          </tr>
          <?php $count++; } } ?>
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
