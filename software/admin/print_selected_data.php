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
<button type="button" class="btn btn-sm btn-primary noPrint" onclick="window.print()">
  <span class="glyphicon glyphicon-print"></span> Print
</button>
<button type="button" class="btn btn-sm btn-primary noPrint" onclick="window.open('export_selected_data.php')">
  <span class="glyphicon glyphicon-print"></span> Export to Excel file
</button> 
<button type="button" class="btn btn-sm btn-primary noPrint" onclick="window.close()">
  <span class="glyphicon glyphicon-remove"></span> Close
</button>
<div id="content_area">
    
    <!-- table -->
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
          <th>Stock<br>
              Inward</th>
            <th>ITEM<br>CODE</th>
            <th>DESCRIPTION<br>&nbsp;</th>
            <th>WIDTH<br>(INCHES)</th>
            <th>AVAILABLE<br>QUANTITY</th>
            <th>TYPE<br>
              &nbsp;</th>
            <th>Sold to<br>
              Clients</th>
              <th>Comments<br>
              &nbsp;</th>
            <th>Last<br>Updated</th>
          </tr>
        </thead>
        <tbody>
          <?php

          $data = $_SESSION["selected_data"];

          for($i=0;$i<count($data);$i++) {
            $export_id=$data[$i];
        
            // Fetch records from database 
            $sel_query="SELECT * FROM  indiadata WHERE itemcode='$export_id'";
            $query = mysqli_query($con,$sel_query);

            //And we display the results
          while($result = mysqli_fetch_array($query)) { 
            // item description
            echo '<tr>';
            echo '<td>';
            echo $result['stockinward'];
            echo '</td><td>';
            echo $result['itemcode'];
            echo '</td><td>';
            echo $result['description'];
            echo '</td><td>';
            echo $result['width'];
            echo '</td><td>';
            echo $result['quantity'];
            echo '</td><td>';
            echo $result['type'];
            echo '</td><td>';
            echo $result['soldtoclients'];
            echo '</td><td>';
            echo $result['comments'];
            echo '</td><td>';
            echo date("d-m-Y", strtotime($result['trn_date']));
            echo '</td></tr>';
            
            }
          }
       
            ?>
        </tbody>
      </table>
    </div>
    <!-- table ends --> 
  </div>

<script src="../js/jquery-1.9.1.min.js"></script> 
<script src="../bootstarp/bootstrap.min.js"></script>

</body>
</html>
