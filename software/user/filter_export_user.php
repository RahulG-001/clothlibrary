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

if(isset($_GET['filterexport'])) {

        $find =$_GET['filterexport'];
            
        //error for empty search
        if ($find == "") {
            echo "<p class='bg-danger'>You forgot to enter a search item!!!<p>";
            exit;
        }

        // search the database
        $query = mysqli_query($con,"SELECT * FROM indiaData WHERE itemcode LIKE '%$find%'");

        //This counts the number or results - and if there wasn't any it gives them a     little     message explaining that
        $anymatches = mysqli_num_rows($query);
        if ($anymatches == 0)
        // show 0 return error
        {
            echo "<p style='color:#ff0000'><b>Sorry, but we can not find an item to match your search...</b></p>";
        } 
        // display data
        else {
                // Excel file name for download 
                $fileName = "stock-data_" . date('d-m-Y') . ".xls";

                $fields = array('Item code', 'Description', 'Location', 'Width', 'Quantity', 'Type', 'Last Updated'); 

                // Display column names as first row 
                $excelData = implode("\t", array_values($fields)) . "\n"; 

                if($query->num_rows > 0) { 
                    // Output each row of the data 
                    while($row = $query->fetch_assoc()) { 
                        $lineData = array($row['itemcode'], $row['description'], $row['location'], $row['width'], $row['quantity'],$row['type'],date("d-m-Y", strtotime($row['trn_date']))); 
                        array_walk($lineData, 'filterData'); 
                        $excelData .= implode("\t", array_values($lineData)) . "\n"; 
                    } 
                } else { 
                    $excelData .= 'No records found...'. "\n"; 
                } 
                
                // Headers for download 
                header("Content-Type: application/vnd.ms-excel"); 
                header("Content-Disposition: attachment; filename=\"$fileName\""); 
                
                // Render excel data 
                echo $excelData; 
            }
}
exit;
?>