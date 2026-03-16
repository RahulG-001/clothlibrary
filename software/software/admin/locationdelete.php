<?php 
	
	require('db.php');
	include("auth.php");
	/* echo 'hello'; exit ; */
	if(isset($_REQUEST['id'])){
		
		$id = $_REQUEST['id'];
		$sel_query ="DELETE FROM re_location Where id='$id'";
		$result = mysqli_query($con,$sel_query);
		if($result){
			echo 1  ;
		} else {
			echo 0;
		}
	}
?>