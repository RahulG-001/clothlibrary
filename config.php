<?php 

	$hostdb = 'localhost';

	//$namedb = 'cloth_librery';

	$namedb = 'cloth_library';

	$userdb = 'root';

	$passdb = '';

	$conn = new PDO("mysql:host=$hostdb; dbname=$namedb", $userdb, $passdb);

	$conn->exec("SET CHARACTER SET utf8");

?>