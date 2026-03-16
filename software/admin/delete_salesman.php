<?php 
require('db.php');
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$query = "DELETE FROM `salesman` WHERE id='$id'"; 
$result = mysqli_query($con,$query);
header("Location: view_salesman.php"); 
?>