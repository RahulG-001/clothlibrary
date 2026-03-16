<?php 
	$hostdb = 'localhost';
	$namedb = 'cloth_library';
	$userdb = 'Clothlibrary';
	$passdb = 'tcl@123';
	$conn = new PDO("mysql:host=$hostdb; dbname=$namedb", $userdb, $passdb);
	$conn->exec("SET CHARACTER SET utf8");
	$add =isset( $_POST['add'] ) ? $_POST['add']: '';
	$name =$_POST['name'] .' '.$_POST['last'];
	$email =isset( $_POST['email'] ) ? $_POST['email']: '';
	$phone_no =isset( $_POST['phone'] ) ? $_POST['phone']: '';
	$massage =isset( $_POST['message'] ) ? $_POST['message']: '';
	$page=$conn->prepare("SELECT * FROM user_list WHERE email_id='$email'");
	$page->execute();
	$count = $page->rowCount();
	if($count==0){		
		if($add=='add' || $name!=''||$email !=''||$phone_no!==''){ 
			$exq="INSERT INTO cl_user_list set user_name='$name',email_id='$email',phone_no='$phone_no',massage='$massage'";
			$stmt = $conn->prepare($exq);
			$stmt->execute(); 
			//$data['status']= '1';
			if($stmt == true){ 
				$to = 'prateek.kalidioscope@gmail.com';
				$subject = 'contact us' ;
				$body = "<div> contact form</div>";
				$headers = 'From: prateek.kalidioscope@gmail.com' . "\r\n" ;
				$headers .='Reply-To: '. $to . "\r\n" ;
				$headers .='X-Mailer: PHP/' . phpversion();
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-type: text/html; charset=iso-8859-1\r\n";   
				if(mail($to, $subject, $body,$headers)) {
					$data['status']= '1';
				}else{
				  $data['error']= '<p>Email Message delivery failed...</p>';
				}
			}else{   
				$data['error']= "Something is missing. Please try again!";
			}
		}else{
			$data['error']= "Something is missing. Please try again!";
		}
	}else{
		$data['error']= "Email already exits!";
	}
		header('Content-type: application/json');
		echo  json_encode($data,JSON_PRETTY_PRINT);
		return true;
?>