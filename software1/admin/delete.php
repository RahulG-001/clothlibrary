<?php 
require('db.php');
$itemcode=$_REQUEST['itemcode'];
$query = "DELETE FROM re_indiadata WHERE itemcode='$itemcode'"; 
$result = mysql_query($query) or die ( mysql_error());
header("Location: view.php"); 
 ?>
