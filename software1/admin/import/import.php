<?php
	include 'db.php';
	date_default_timezone_set('Asia/Kolkata');
	$trn_date = date("Y-m-d H:i:s");
	if(isset($_POST["Import"])){  
		echo $filename=$_FILES["file"]["tmp_name"];
		if($_FILES["file"]["size"] > 0){
		  	$file = fopen($filename, "r");
			while(($emapData = fgetcsv($file,10000, ","))!== FALSE){  
				$check =  mysqli_query($con,"select * FROM re_indiadata where itemcode='$emapData[0]'");
				$row = mysqli_fetch_array($check);
				$count = mysqli_num_rows($check);
				if($count==0){   
					if($emapData[0] != 'itemcode'){  
						date_default_timezone_set('Asia/Kolkata');
						$date = date('Y-m-d H:m:s');
						$ins_query="insert into re_indiadata(`trn_date`, `itemcode`,`description`,`width`,`quantity`,`price`,`country`,`location`)values('$trn_date', '$emapData[0]','$emapData[1]','$emapData[2]','$emapData[3]','$emapData[4]','$emapData[5]','$emapData[6]')";
						$result = mysqli_query($con,$ins_query, $connection);
					}
				}
			}
	        fclose($file);
	        echo header("Location:../view.php");
		    mysqli_close($connection); 
		}
	}	
?>		 