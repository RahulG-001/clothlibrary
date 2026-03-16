<?php 
require('db.php');
$userid=$_REQUEST['userid'];
$query = "DELETE FROM `re_admin` WHERE userid='$userid'"; 
$result = mysql_query($query) or die ( mysql_error());
header("Location: view_admins.php"); 
?>
