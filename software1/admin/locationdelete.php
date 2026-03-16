<?php 
	
	require('db.php');
	include("auth.php");
	/* echo 'hello'; exit ; */
	if(isset($_REQUEST['id'])){
		$id = $_REQUEST['id'];
		//$sel_query1 ="SELECT name FROM re_location Where id='$id'";
		//$result1 = mysqli_query($con,$sel_query1);
		//echo $data = $result1['name']; die;
		$sel_query2 ="DELETE FROM re_indiadata Where location=(SELECT name FROM re_location Where id='$id')";
		$result2 = mysqli_query($con,$sel_query2);
		if($result2 == true){
			$sel_query ="DELETE FROM re_location Where id='$id'";
			$result = mysqli_query($con,$sel_query);
			if($result){
				echo 1;
			} else { 
				echo 0;
			}
		}
		
	}
?>