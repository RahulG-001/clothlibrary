<?php 
require('db.php');
include("auth.php");

function downloadQrImage($qrText, $fileNamePrefix = 'stock_qrcode') {
  $qrUrl = 'https://quickchart.io/qr?size=500&format=png&text='.rawurlencode($qrText);
  $qrImage = @file_get_contents($qrUrl);

  if ($qrImage === false) {
    return false;
  }

  header('Content-Type: image/png');
  header('Content-Disposition: attachment; filename="'.$fileNamePrefix.'_'.date('Ymd_His').'.png"');
  header('Content-Length: '.strlen($qrImage));
  echo $qrImage;
  exit;
}

$qrPreviewItemCode = '';
$qrPreviewItemId = 0;
$qrPreviewImageUrl = '';

if (isset($_GET['qr_itemcode'])) {
  $itemCode = trim($_GET['qr_itemcode']);
  if ($itemCode === '') {
    header("Location: view.php?qr_error=1");
    exit;
  }

  $itemCodeEsc = mysqli_real_escape_string($con, $itemCode);
  $itemRes = mysqli_query($con, "SELECT id FROM indiaData WHERE itemcode = '".$itemCodeEsc."' LIMIT 1");
  if (!$itemRes || mysqli_num_rows($itemRes) === 0) {
    header("Location: view.php?qr_error=1");
    exit;
  }
  $itemRow = mysqli_fetch_assoc($itemRes);
  $itemId = (int)$itemRow['id'];
  $qrText = 'ID: '.$itemId.', ITEMCODE: '.$itemCode;

  $safeFileCode = preg_replace('/[^A-Za-z0-9_-]/', '_', $itemCode);
  if ($safeFileCode === '') {
    $safeFileCode = 'item';
  }

  if (isset($_GET['download']) && $_GET['download'] == '1') {
    if (downloadQrImage($qrText, 'qrcode_'.$safeFileCode) === false) {
      header("Location: view.php?qr_error=1");
      exit;
    }
  } else {
    $qrPreviewItemCode = $itemCode;
    $qrPreviewItemId = $itemId;
    $qrPreviewImageUrl = 'https://quickchart.io/qr?size=400&format=png&text='.rawurlencode($qrText);
  }
}
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
      <button type="button" onClick='window.open("print.php", "_blank", "toolbar=no,scrollbars=yes,resizable=yes,width=800,height=600")' class="btn btn-sm btn-info"><span class="glyphicon glyphicon-print"></span> Print All Stock</button> 
          <a href="exportcsv.php">
          <button type="button" class="btn btn-sm btn-info"><span class="glyphicon glyphicon-download-alt"></span> Export All Data</button>
          </a>
      </p>

    <h3>View Records</h3>
    <?php if (isset($_GET['qr_error'])) { ?>
      <div class="alert alert-danger">Unable to generate QR code for the selected item. Please try again.</div>
    <?php } ?>
    <?php if ($qrPreviewItemCode !== '') { ?>
      <div class="panel panel-default noPrint" style="max-width:520px; margin-bottom:15px;">
        <div class="panel-body" style="text-align:center;">
          <h4 style="margin-top:0;">QR Code Preview</h4>
          <p><strong>ID:</strong> <?php echo (int)$qrPreviewItemId; ?></p>
          <p><strong>Item Code:</strong> <?php echo htmlspecialchars($qrPreviewItemCode); ?></p>
          <img src="<?php echo htmlspecialchars($qrPreviewImageUrl); ?>" alt="QR code" class="img-responsive" style="margin:0 auto 12px auto; max-width:320px;">
          <p style="margin-bottom:0;">
            <a class="btn btn-sm btn-success" href="view.php?qr_itemcode=<?php echo urlencode($qrPreviewItemCode); ?>&download=1">
              <span class="glyphicon glyphicon-download-alt"></span> Download QR
            </a>
            <a class="btn btn-sm btn-default" href="view.php">Back to List</a>
          </p>
        </div>
      </div>
    <?php } ?>
    
    <!-- table -->
    <div class="table-responsive yesPrint">
    <form name="form1" method="post" action="" onSubmit="return validate();">
      <!-- delete -->
      <p style="margin-top:10px" align="right">
        <input name="delete" type="submit" id="delete" value="Delete" class="btn btn-sm btn-danger" onclick="return checkDelete()">
        <input name="print" type="submit" id="Export" value="Export Selected Items" onclick="printSelected()" class="btn btn-sm btn-primary">
      </p>

    <!-- <div class="scrollTbl"> -->
      <table class="table table-striped display" id="stockList">
        <thead>
          <tr>
          <th>Stock<br>
              Inward</th>
              <th>Item<br>
              Image</th>
            <th>ITEM<br>
              CODE</th>
            <th>DESCRIPTION<br>
              &nbsp;</th>
            <th>WIDTH<br>
              (INCHES)</th>
            <th>AVAILABLE<br>
              QUANTITY</th>
              <th>TYPE<br>
              &nbsp;</th>
              <th>Sold<br>
              to</th>
              <th>Comments<br>
              &nbsp;</th>
            <th>Last<br>
              Updated</th>
            <th>&nbsp;</th>
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
          $sql = "DELETE FROM indiaData WHERE itemcode='$del_id'";
          $result = mysqli_query($con,$sql);
          }
          // if successful redirect to delete_multiple.php
          if($result) {
            echo '<meta http-equiv=\"refresh\" content=\"0;URL=view.php\">';
          }
          //mysql_close();

        }

        if(isset($_POST['print'])) {
          // set data in session
          $_SESSION["selected_data"] = $_POST['checkbox'];

          // open new tab
          {?>
            <script language="javascript">
                window.open("print_selected_data.php", "_blank", "toolbar=no,scrollbars=yes,resizable=yes,width=800,height=600")
            </script>
          <?php
          }

        }
        

			$count=1;
			$sel_query="SELECT * FROM indiaData";
			$result = mysqli_query($con,$sel_query);

			while($row = mysqli_fetch_assoc($result)) { ?>
          <tr>
            <td><?php echo $row["stockinward"]; ?></td>
            <td><a href="../item_images/<?php echo $row["image"]; ?>" target="_blank"><img src="../item_images/<?php echo $row["image"]; ?>" class="img-responsive" /></a></td>
            <td><?php echo $row["itemcode"]; ?></td>
            <td><?php echo $row["description"]; ?></td>
            <td><?php echo $row["width"]; ?></td>
            <td><?php echo $row["quantity"]; ?></td>
            <td><?php echo $row["type"]; ?></td>
            <td><?php echo $row["soldtoclients"]; ?></td>
            <td><?php echo $row["comments"]; ?></td>
            <td>
              <?php 
                $extDate = $row["trn_date"];
                $newDate = date("d-m-Y", strtotime($extDate));   
                echo $newDate; 
              ?>
            </td>
            <td class="noPrint"><a href="edit.php?itemcode=<?php echo $row["itemcode"]; ?>"><span class="glyphicon glyphicon-edit"></span> Edit</a></td>
            <td class="noPrint">
              <a class="btn btn-xs btn-success" href="view.php?qr_itemcode=<?php echo urlencode($row['itemcode']); ?>">
                <span class="glyphicon glyphicon-qrcode"></span> QR
              </a>
            </td>
            
            <td><input name="checkbox[]" type="checkbox" id="checkbox[]" value="<?php echo $row['itemcode']; ?>"> </td>

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
      alert("Please select at least one data item.");
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
