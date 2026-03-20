<?php 
require('../admin/db.php');
include("auth.php");
 

// Filter the excel data 
function filterData(&$str){ 
    $str = preg_replace("/\t/", "\\t", $str); 
    $str = preg_replace("/\r?\n/", "\\n", $str); 
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"'; 
} 

$count=1;

// Excel file name for download 
$fileName = "stock-data_" . date('d-m-Y') . ".xls";

$fields = array('Item code', 'Description', 'Location', 'Width', 'Quantity', 'Type', 'Last Updated'); 

// Display column names as first row 
$excelData = implode("\t", array_values($fields)) . "\n";

$data = $_SESSION["selected_data"];

for($i=0;$i<count($data);$i++) {
    $export_id=$data[$i];

    // Fetch records from database 
    $sel_query="SELECT * FROM  indiadata WHERE itemcode='$export_id'";
    $query = mysqli_query($con,$sel_query);

    //And we display the results
    while($row = mysqli_fetch_array($query)) {
        $lineData = array($row['itemcode'], $row['description'],$row['location'],$row['width'], $row['quantity'], $row['type'],date("d-m-Y", strtotime($row['trn_date']))); 
        array_walk($lineData, 'filterData'); 
        $excelData .= implode("\t", array_values($lineData)) . "\n";
    }
}

// Headers for download 
header("Content-Type: application/vnd.ms-excel"); 
header("Content-Disposition: attachment; filename=\"$fileName\""); 

// Render excel data 
echo $excelData;
exit;
?>