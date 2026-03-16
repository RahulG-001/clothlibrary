<?php 

$hostdb = 'localhost';
	$namedb = 'thecloth_software';
	$userdb = 'root';
	$passdb = '';
	$conn = new PDO("mysql:host=$hostdb; dbname=$namedb", $userdb, $passdb);
	$conn->exec("SET CHARACTER SET utf8"); 





?>





<?php include("contact_us.php");?>
