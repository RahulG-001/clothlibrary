<?php 

	$hostdb = 'localhost';

	$namedb = 'clothlibrary';

	$userdb = 'clothlibrary';

	$passdb = 'Ravi@94277';

	$conn = new PDO("mysql:host=$hostdb; dbname=$namedb", $userdb, $passdb);

	$conn->exec("SET CHARACTER SET utf8");

?>