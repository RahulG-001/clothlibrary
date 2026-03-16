<?PHP
	//namespace Chirp;
	$hostdb = 'localhost';
	$namedb = 'thecloth_software';
	$userdb = 'thecloth_soft';
	$passdb = 'thecloth_soft@123';
	$conn = new PDO("mysql:host=$hostdb; dbname=$namedb", $userdb, $passdb);
	$conn->exec("SET CHARACTER SET utf8");
	$page=$conn->prepare("SELECT itemcode as ITEMCODE,description as DESCRIPTION,location AS LOCATION,width AS WIDTH,quantity AS QUANTITY,price AS PRICE,country AS COUNTRY,trn_date AS TRN_DATE FROM re_indiadata"); 
	$page->execute();
	$data = $page->fetchAll(PDO::FETCH_ASSOC);
	function cleanData(&$str){
		$str = preg_replace("/\t/", "\\t", $str);
		$str = preg_replace("/\r?\n/", "\\n", $str);
		if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"';
	}
	// file name for download
	$filename = "backup_data_" . date('Ymd') . ".xls";
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Content-Type: application/vnd.ms-excel");
	$flag = false;
	foreach($data as $row) {
		if(!$flag) {
			// display field/column names as first row
			echo implode("\t", array_keys($row)) . "\n";
			$flag = true;
		}
		array_walk($row, __NAMESPACE__ . '\cleanData');
		echo implode("\t", array_values($row)) . "\n";
	}
	exit;
?>