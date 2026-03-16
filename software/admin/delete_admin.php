<?php 
require('db.php');
$userid=$_REQUEST['userid'];
$query = "DELETE FROM `admin` WHERE userid='$userid'"; 
$result = mysqli_query($con,$query);
header("Location: view_admins.php"); 
?>
