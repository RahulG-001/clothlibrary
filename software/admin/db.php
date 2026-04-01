<?php
	require_once __DIR__ . '/../bootstrap_timezone.php';

	$host = "localhost";
	$username = "root";
	$password = "";
	$database = "cucinello";
	$con = mysqli_connect($host, $username, $password,$database);
	// Check connection
	if (mysqli_connect_errno()){
	    echo "Failed to connect to MySQL: " . mysqli_connect_error();
	} else {
		// Match PHP default (Asia/Kolkata): CURRENT_TIMESTAMP, NOW(), ON UPDATE use IST for this connection.
		mysqli_query($con, "SET time_zone = '+05:30'");
	}
?>