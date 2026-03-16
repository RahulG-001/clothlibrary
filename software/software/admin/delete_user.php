<?php 
require('db.php');
$userid=$_REQUEST['userid'];
$query = "DELETE FROM `re_users` WHERE userid='$userid'"; 
$result = mysql_query($query) or die ( mysql_error());
header("Location: view_users.php"); 
?>
