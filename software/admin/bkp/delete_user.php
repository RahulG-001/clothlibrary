<?php 
require('db.php');
$userid=$_REQUEST['userid'];
$query = "DELETE FROM `users` WHERE userid='$userid'"; 
$result = mysqli_query($con,$query);
header("Location: view_users.php"); 
?>
