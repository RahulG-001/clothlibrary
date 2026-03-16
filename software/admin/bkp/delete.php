<?php 
require('db.php');
$itemcode=$_REQUEST['itemcode'];
$query = "DELETE FROM indiaData WHERE itemcode='$itemcode'"; 
$result = mysqli_query($con,$query);
header("Location: view.php"); 
 ?>
