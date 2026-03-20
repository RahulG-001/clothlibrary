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
  <li><a class="admin" href="https://theclothlibrary.com/">Home</a></li>
  <li><a class="admin" href="../region.html">User Login</a></li>
  <li><a class="admin selected" href="login.php">Admin Login</a></li>
  <div class="clear"></div>
</ul>
<!-- page content -->
<div id="mainPanel"> 
  
  <!-- logo -->
 <div id="logo" style="margin-left:10%; margin-right:10%;text-align:center;"><img src="../images/lc_logo_small.jpg" width="100%" height="80px;" alt="Kalidioscope" />
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
      
 
      </p>

      <!-- filter and print -->
      <div align="right" style="border-top:1px solid #ccc; border-bottom:1px solid #ccc; padding:5px 0px 5px 0px">
        <form class="form-inline" action="filter_print.php" target="_blank">
          <button type="button" onClick='window.open("print.php", "_blank", "toolbar=no,scrollbars=yes,resizable=yes,width=800,height=600")' class="btn btn-sm btn-info"><span class="glyphicon glyphicon-print"></span> Print All Stock</button> | 

            <div class="input-group">
              <input type="text" class="form-control" id="filter" name="filter" placeholder="Item ID">
            
            <span class="input-group-btn">
              <button type="submit" class="btn btn-info"><span class="glyphicon glyphicon-print"></span> Filter and Print</button>
            </span>
            </div>

        </form>
      </div>

    <h3>View Records</h3>
    
    <!-- table -->
    <div class="table-responsive yesPrint">
    <form name="form1" method="post" action="" onSubmit="return validate();">
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

        // Check if delete button active, start this
        if(isset($_POST['delete'])){

          for($i=0;$i<count($_POST['checkbox']);$i++){
          $del_id=$_POST['checkbox'][$i];
          $sql = "DELETE FROM indiadata WHERE itemcode='$del_id'";
          //$sql = "DELETE FROM $tbl_name WHERE id='$del_id'";
          $result = mysqli_query($con,$sql);
          }
          // if successful redirect to delete_multiple.php
          if($result) {
            echo '<meta http-equiv=\"refresh\" content=\"0;URL=view.php\">';
          }
          //mysql_close();

        }
        
        // delete logic ends

			$count=1;
			$sel_query="SELECT * FROM indiadata";
			$result = mysqli_query($con,$sel_query);

			while($row = mysqli_fetch_assoc($result)) { ?>
          <tr>
            
            <td><?php echo $row["itemcode"]; ?></td>
            <td><?php echo $row["description"]; ?></td>
            <td><?php echo $row["width"]; ?></td>
            <td><?php echo $row["quantity"]; ?></td>
            <td>
              <?php 
                $extDate = $row["trn_date"];
                $newDate = date("d-m-Y", strtotime($extDate));   
                echo $newDate; 
              ?>
            </td>
            <td class="noPrint"><a href="edit.php?itemcode=<?php echo $row["itemcode"]; ?>"><span class="glyphicon glyphicon-edit"></span> Edit</a></td>
            
            <td><input name="checkbox[]" type="checkbox" id="checkbox[]" value="<?php echo $row['itemcode']; ?>"> <span style="color:#8B0000" class="glyphicon glyphicon glyphicon-trash"></span></td>

          </tr>
          <?php $count++; } ?>
        </tbody>
      </table>

      <!-- delete -->
      <p style="margin-top:10px" align="right"><input name="delete" type="submit" id="delete" value="Delete" class="btn btn-danger" onclick="return checkDelete()"></p>

      </form>
    </div>
    <!-- </div> -->
    <!-- table ends --> 
  </div>
  
  <!-- footer -->
  <div id="footer">
    copyright &copy; 2018 The Cloth Library. </div>
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
