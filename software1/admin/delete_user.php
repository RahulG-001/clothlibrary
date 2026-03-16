<?php 
require('db.php');
$userid=$_REQUEST['userid'];
$query = "DELETE FROM re_users WHERE userid='$userid'"; 
$result = mysqli_query($con,$query) or die ( mysql_error());
header("Location: view_users.php"); 
?>
