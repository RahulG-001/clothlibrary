<?php
	$host = "localhost";
	$username = "thecloth_soft";
	$password = "thecloth_soft@123";
	$database = "thecloth_software";
	$con = mysqli_connect($host, $username, $password,$database);
	// Check connection
  	if (mysqli_connect_errno())
  	{
 	 echo "Failed to connect to MySQL: " . mysqli_connect_error();
  	}
?>