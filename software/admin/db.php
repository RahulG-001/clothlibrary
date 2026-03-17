<?php
	$host = "localhost";
	$username = "cucinello_usr";
	$password = "Ravi@94277";
	$database = "cucinello";
	$con = mysqli_connect($host, $username, $password,$database);
	if (mysqli_connect_errno()){
	    echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
?>