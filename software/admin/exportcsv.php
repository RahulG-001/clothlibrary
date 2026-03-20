
<?php 
require('db.php');
include("auth.php");

 // Filter the excel data 
function filterData(&$str){ 
    $str = preg_replace("/\t/", "\\t", $str); 
    $str = preg_replace("/\r?\n/", "\\n", $str); 
    if(strstr($str, '"')) $str = '"' . str_replace('"', '""', $str) . '"'; 
} 

// Excel file name for download 
$fileName = "stock-data_" . date('d-m-Y') . ".xls";

$fields = array('Stock Inward', 'Item code', 'Description', 'Location', 'Width', 'Quantity', 'Type', 'Sold to Clients', 'Comments', 'Last Updated'); 

// Display column names as first row 
$excelData = implode("\t", array_values($fields)) . "\n"; 

// Fetch records from database 
$sel_query="SELECT * FROM indiaData ORDER BY itemcode ASC";
$query = mysqli_query($con,$sel_query);

if($query->num_rows > 0){ 
    // Output each row of the data 
    while($row = $query->fetch_assoc()){ 
        $lineData = array($row['stockinward'],$row['itemcode'], $row['description'],$row['width'], $row['quantity'],$row['type'],$row['soldtoclients'],$row['comments'],date("d-m-Y", strtotime($row['trn_date']))); 
        array_walk($lineData, 'filterData'); 
        $excelData .= implode("\t", array_values($lineData)) . "\n";
    } 
} else{ 
    $excelData .= 'No records found...'. "\n"; 
}
 
// Headers for download 
header("Content-Type: application/vnd.ms-excel"); 
header("Content-Disposition: attachment; filename=\"$fileName\""); 
 
// Render excel data 
echo $excelData; 
 
exit;
 
?>