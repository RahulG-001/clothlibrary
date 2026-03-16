<?php
ob_start();
ini_set('display_errors', 1);
class get
{ 
	//=============Connction=============
	private function getConnection(){
		$hostdb = 'localhost';
		$namedb = 'thecloth_software';
		$userdb = 'root';
		$passdb = '';
		try {
			$conn = new PDO("mysql:host=$hostdb; dbname=$namedb", $userdb, $passdb);
			$conn->exec("SET CHARACTER SET utf8");      
			// Sets encoding UTF-8
			return $conn;
		}
		catch(PDOException $e) {
		   echo $e->getMessage();
		}
	}
	
	//============End Connection =============
	
	
	//=============Function Check=============
	
	public function processApi(){ 
		$func = strtolower(trim(str_replace("/","",$_REQUEST['action'])));
		if((int)method_exists($this,$func) > 0)
			$this->$func();
		else
			self::responce();
	}
	
	//===============End Function ================
	
	//=============================================================================================================
	// ======================================//START LOGIN FUNCTION//===========================================
	//=============================================================================================================

	//=============start GET SESSION Function=============
	public function session(){   
		try { 
			$con=$this->getConnection();
			session_start();
			$username = $_SESSION['login'];
			if($username !=''){
				$data = '1';
			}else{
				$data = '0';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){  
			print $e->getMessage();
		}
	}
	
	//=============End GET SESSION Function=============
	//=============start GET Logout Function=============
	public function loguot(){   
		try { 
			$con=$this->getConnection();
			session_start();
			$data = session_destroy();
			if($data ==true){
				header("location:../index.php");
			}
		}catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	
	//=============End GET Logout Function=============
	
	
	//=============================================================================================================
	// ======================================//END LOGIN FUNCTION//===========================================
	//=============================================================================================================
		

	//=============================================================================================================
	// ======================================//START USER FUNCTION//===========================================
	//=============================================================================================================
	

	//=============GET User Function=============
	
	public function get_User(){  
		try { 
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM cl_user_list WHERE status='1' order by id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['user_name'] = $row['user_name'];
				$data['email_id'] = $row['email_id'];
				$data['phone_no'] = $row['phone_no'];
				$data['massage'] = $row['massage'];
				$data['Action'] = "<a data-toggle='modal' href='#viewUser' onclick='View($row[id])' title='View'  class='btn btn-icon-only green'> <i class='fa fa-pencil'></i></a>	<a data-toggle='modal' onclick='deleteUser($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	
	//=============End GET User Function=============
	

	//===========Veiw User list Function============
	
	public function veiw_User(){    
		try { 
			$con=$this->getConnection();
			$edituser =isset( $_POST['viewUser'] ) ? $_POST['viewUser']: '';
			$view=$con->prepare("SELECT * FROM cl_user_list WHERE id='$edituser'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	//===========End View User Function=================
	
	//============= ADD User Function ===============
	
	public function update_User(){ 
		try{ 
			$con=$this->getConnection();
			$id =isset( $_POST['id'] ) ? $_POST['id']: '';
			$username =isset($_POST['username']) ? $_POST['username']: '';
			$email_id =isset($_POST['email_id']) ? $_POST['email_id']: '';
			$phone_no =isset($_POST['phone_no']) ? $_POST['phone_no']: '';
			$massage =isset($_POST['massage']) ? $_POST['massage']: '';
			if($id != ''){  
				if($username == ''){  
					$data['error'] = "Please select User name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if(strlen(preg_replace('/\s+/u','',$username)) == 0){  
					$data['error'] = "Please fill User Name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($email_id == ''){  
					$data['error'] = "Please select Email ID!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($massage == ''){  
					$data['error'] = "Please select massage!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if(strlen(preg_replace('/\s+/u','',$massage)) == 0){  
					$data['error'] = "Please fill massage!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$exq="UPDATE cl_user_list set user_name='$username',email_id='$email_id',phone_no='$phone_no',massage='$massage' WHERE id='$id'"; 
				$stmt = $con->prepare($exq);
				$stmt->execute(); 
				if($stmt ==true)
				{
					$data['status']= '1';
					header('Content-type: application/json');
					echo  json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}else{ 
					$data['error']= "Something is missing. Please try again!";
					header('Content-type: application/json');
					echo  json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}
				
			}else{
				$data['error']= "Something is missing. Please try again!";
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			
		}
		catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	
	//===========End Add User Function==============
	
	
	//===========Start Delete USER Function==============
	
	
	public function deleteUser(){   
		try {
			$con=$this->getConnection();
			$deleteUser =isset( $_POST['deleteUser'] ) ? $_POST['deleteUser']: '';
			$view=$con->prepare("DELETE from cl_user_list WHERE id='$deleteUser'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='User no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	//===========End Delete USER Function==============
	
	//=============================================================================================================
	// ======================================//END USER FUNCTION//===========================================
	//=============================================================================================================
	
	
		
	//============================================================================================================
	// ======================================//START BANNER FUNCTION//===========================================
	//============================================================================================================
		//=============GET Banner Function=============

		
		public function get_banner(){  
			try { 
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM cl_homepage_banner order by banner_id DESC");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){  
					$data['banner_id'] = $row['banner_id'];
					$image = $row['banner'];
					$data['banner'] ="<img src='lib/image/$image' height='80px;' width='120px;'>"; 
					if( $row['status']==1){ 
					$data['Status'] = "<a data-toggle='modal' onclick='active($row[banner_id])' title='Inactive'  class='btn '><b style='color:green;'>Click to Inactive</b></a>";
					}else{ 
						$data['Status'] = "<a data-toggle='modal' onclick='active($row[banner_id])' title='Active'  class='btn '><b style='color:red;'>Click to Active</b></a>";
					}
					$data['Edit'] = "<a data-toggle='modal' href='#updatebanner'  onclick='Edit($row[banner_id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a>";
					$data['Delete'] = "<a data-toggle='modal' onclick='deleteBanner($row[banner_id])' title='Delete'   class='btn btn-icon-only purple'><i class='fa fa-trash'></i></a>";
					$getdata[]=$data;
				}
				$data = $getdata;
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) {
				print $e->getMessage();
			}
		}
		
		//=============End GET Banner Function=============
		
		//============= ADD Banner Function ===============
		public function add_banner(){ 
			try{ 
				$con=$this->getConnection();
				$add =isset( $_POST['add'] ) ? $_POST['add']: '';
				$uploadedfile1 = $_FILES['image']['name'];
				if($add=='add')
				{
					if($uploadedfile1 == '')
					{ 
						$data['error'] = "Please Select Banner Image!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
					}
					$path = "image/img/";
					$name2 = md5(rand(999,99999)).$uploadedfile1;
					$name3 = 'img/'.$name2;
					move_uploaded_file($_FILES['image']['tmp_name'],$path.$name2);
					$exq="INSERT INTO cl_homepage_banner set banner='$name3'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0)
					{
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
					
				}else{
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch (PDOException $e) {
				print $e->getMessage();
			}
		}
		
		//===========End Add Banner Function==============
		
		//===========Veiw Breed list Function============
		
		public function veiw_banner(){   
			try{
				$con=$this->getConnection();
				$veiw_banner =isset( $_POST['veiw_banner'] ) ? $_POST['veiw_banner']: '';
				$view=$con->prepare("SELECT * FROM cl_homepage_banner WHERE banner_id='$veiw_banner'");
				$view->execute();
				$data = $view->fetch(PDO::FETCH_ASSOC);
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End View Breed Function=================
		
		//============= Edit Banner Function ===============
		public function edit_banner(){ 
			try{ 
				$con=$this->getConnection();
				$banner_id =isset( $_POST['banner_id'] ) ? $_POST['banner_id']: '';
				$uploadedfile1 = $_FILES['image']['name'];
				if($banner_id>0)
				{
					if($uploadedfile1 == '')
					{ 
						$data['error'] = "Please Select Banner Image!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
					}
					$path = "image/img/";
					$name2 = md5(rand(999,99999)).$uploadedfile1;
					$name3 = 'img/'.$name2;
					move_uploaded_file($_FILES['image']['tmp_name'],$path.$name2);
					$exq="UPDATE cl_homepage_banner set banner='$name3' where banner_id='$banner_id'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$data['status']= '1';
					
				}else{
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch (PDOException $e) {
				print $e->getMessage();
			}
		}
		//===========End Edit Banner Function==============
		
		
		//============= Edit Banner Function ===============
		public function active_banner(){ 
			try{ 
				$con=$this->getConnection();
				$active_banner =isset( $_POST['active_banner'] ) ? $_POST['active_banner']: '';
				
				if($active_banner>0)
				{
					$view=$con->prepare("SELECT * FROM cl_homepage_banner WHERE  banner_id='$active_banner'");
					$view->execute();
					$dat1 = $view->fetch(PDO::FETCH_ASSOC);
					if($dat1['status_id'] ==1){
						$exq="UPDATE cl_homepage_banner set status='0' where banner_id='$active_banner'";
						$stmt = $con->prepare($exq);
						$stmt->execute(); 
					}else{
						$exq="UPDATE cl_homepage_banner set status='1' where banner_id='$active_banner'";
						$stmt = $con->prepare($exq);
						$stmt->execute(); 
					}
					$data['status']= '1';
				}else{ 
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch (PDOException $e) {
				print $e->getMessage();
			}
		}
		//===========End Edit Banner Function==============
		
		//===========Start Delete Banner Function==============
		
		public function deleteBanner(){   
			try {
				$con=$this->getConnection();
				$deleteBanner =isset( $_POST['deleteBanner'] ) ? $_POST['deleteBanner']: '';
				$view=$con->prepare("DELETE from cl_homepage_banner WHERE banner_id='$deleteBanner'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='User no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		//===========End Delete Banner Function==============
	
	//============================================================================================================
	// ======================================//END BANNER FUNCTION//============================================
	//============================================================================================================
	
	//============================================================================================================
	// ======================================//START Client FUNCTION//===========================================
	//============================================================================================================
		//=============GET Client Function=============

		
		public function get_Client(){  
			try { 
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM cl_client order by id DESC");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){  
					$data['id'] = $row['id'];
					$image = $row['image'];
					$data['image'] ="<img src='lib/image/$image' height='80px;' width='120px;'>"; 
					if( $row['status']==1){ 
					$data['Status'] = "<a data-toggle='modal' onclick='active($row[id])' title='Inactive'  class='btn '><b style='color:green;'>Click to Inactive</b></a>";
					}else{ 
						$data['Status'] = "<a data-toggle='modal' onclick='active($row[id])' title='Active'  class='btn '><b style='color:red;'>Click to Active</b></a>";
					}
					$data['Edit'] = "<a data-toggle='modal' href='#updateClient'  onclick='Edit($row[id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a>";
					$data['Delete'] = "<a data-toggle='modal' onclick='deleteClient($row[id])' title='Delete'   class='btn btn-icon-only purple'><i class='fa fa-trash'></i></a>";
					$getdata[]=$data;
				}
				$data = $getdata;
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) {
				print $e->getMessage();
			}
		}
		
		//=============End GET Client Function=============
			//============= ADD Client Function ===============
		public function add_Client(){ 
			try{ 
				$con=$this->getConnection();
				$add =isset( $_POST['add'] ) ? $_POST['add']: '';
				$uploadedfile1 = $_FILES['image']['name'];
				if($add=='add'){ 
					if($uploadedfile1 == ''){  
						$data['error'] = "Please Select Banner Image!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
					}
					$path = "image/";
					$name2 = md5(rand(999,99999)).$uploadedfile1;
					move_uploaded_file($_FILES['image']['tmp_name'],$path.$name2);
					$exq="INSERT INTO cl_client set image='$name2'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0){
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
					
				}else{
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch(PDOException $e){
				print $e->getMessage();
			}
		}
		
		//===========End Add Banner Function==============
		
	
		//===========Veiw Client list Function============
		
		public function veiw_Client(){   
			try{
				$con=$this->getConnection();
				$veiw_Client =isset( $_POST['veiw_Client'] ) ? $_POST['veiw_Client']: '';
				$view=$con->prepare("SELECT * FROM cl_client WHERE id='$veiw_Client'");
				$view->execute();
				$data = $view->fetch(PDO::FETCH_ASSOC);
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End View Client Function=================
		
		//============= Edit Client Function ===============
		public function edit_Client(){ 
			try{ 
				$con=$this->getConnection();
				$id =isset( $_POST['Client_id'] ) ? $_POST['Client_id']: '';
				$uploadedfile1 = $_FILES['image']['name'];
				if($id>0)
				{
					if($uploadedfile1 == '')
					{ 
						$data['error'] = "Please Select Banner Image!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
					}
					$path = "image/";
					$name2 = md5(rand(999,99999)).$uploadedfile1;
					move_uploaded_file($_FILES['image']['tmp_name'],$path.$name2);
					$exq="UPDATE cl_client set image='$name2' where id='$id'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$data['status']= '1';
					
				}else{
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch (PDOException $e) {
				print $e->getMessage();
			}
		}
		//===========End Edit Client Function==============
		//===========Start Delete Banner Function==============
		
		public function deleteClient(){   
			try {
				$con=$this->getConnection();
				$deleteClient =isset( $_POST['deleteClient'] ) ? $_POST['deleteClient']: '';
				$view=$con->prepare("DELETE from cl_client WHERE id='$deleteClient'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Client no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		//===========End Delete Banner Function==============
	
	//============================================================================================================
	// ======================================//END Client FUNCTION//============================================
	//============================================================================================================
	
	//============================================================================================================
	// ======================================//START GALLERY FUNCTION//===========================================
	//============================================================================================================
		//=============GET Gallery Function=============

		public function get_Gallery(){  
			try { 
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM cl_gallery order by id DESC");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){  
					$data['id'] = $row['id'];
					$image = $row['image'];
					$data['image'] ="<img src='lib/image/$image' height='80px;' width='120px;'>"; 
					if( $row['status']==1){ 
					$data['Status'] = "<a data-toggle='modal' onclick='active($row[id])' title='Inactive'  class='btn '><b style='color:green;'>Click to Inactive</b></a>";
					}else{ 
						$data['Status'] = "<a data-toggle='modal' onclick='active($row[id])' title='Active'  class='btn '><b style='color:red;'>Click to Active</b></a>";
					}
					$data['Edit'] = "<a data-toggle='modal' href='#updateGallery'  onclick='Edit($row[id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a>";
					$data['Delete'] = "<a data-toggle='modal' onclick='deleteGallery($row[id])' title='Delete'   class='btn btn-icon-only purple'><i class='fa fa-trash'></i></a>";
					$getdata[]=$data;
				}
				$data = $getdata;
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) {
				print $e->getMessage();
			}
		}
		
		//=============End GET Gallery Function=============
			//============= ADD Gallery Function ===============
		public function add_Gallery(){ 
			try{ 
				$con=$this->getConnection();
				$add =isset( $_POST['add'] ) ? $_POST['add']: '';
				$uploadedfile1 = $_FILES['image']['name'];
				if($add=='add'){ 
					if($uploadedfile1 == ''){  
						$data['error'] = "Please Select Banner Image!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
					}
					foreach($_FILES['image']['tmp_name'] as $key=>$image){
						$file_name = $key.$_FILES['image']['name'][$key];
						$file_tmp =$_FILES['image']['tmp_name'][$key];
						$path = "image/gallery/";
						$ext = explode('.',$file_name);
						$name2 = md5(rand(999,99999)).'.'.$ext[1];
						$name3 = 'gallery/'.$name2;
						move_uploaded_file($file_tmp,$path.$name2);
						$exq="INSERT INTO cl_gallery set image='$name3'";
						$stmt = $con->prepare($exq);
						$stmt->execute(); 
					}
					if($stmt == true){
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
					
				}else{
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch(PDOException $e){
				print $e->getMessage();
			}
		}
		
		//===========End Add Gallery Function==============
		
	
		//===========Veiw Gallery list Function============
		
		public function veiw_Gallery(){   
			try{
				$con=$this->getConnection();
				$veiw_Gallery =isset( $_POST['veiw_Gallery'] ) ? $_POST['veiw_Gallery']: '';
				$view=$con->prepare("SELECT * FROM cl_gallery WHERE id='$veiw_Gallery'");
				$view->execute();
				$data = $view->fetch(PDO::FETCH_ASSOC);
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End View Gallery Function=================
		
		//============= Edit Gallery Function ===============
		public function edit_Gallery(){ 
			try{  
				$con=$this->getConnection();
				$id =isset( $_POST['Gallery_id'] ) ? $_POST['Gallery_id']: '';
				$uploadedfile1 = $_FILES['image']['name'];
				if($id>0){
					if($uploadedfile1 == ''){  
						$data['error'] = "Please Select Gallery Image!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
					}
					$path = "image/gallery/";
					$ext = explode('.',$uploadedfile1);
					$name2 = md5(rand(999,99999)).'.'.$ext[1];
					$name3 = 'gallery/'.$name2;
					move_uploaded_file($_FILES['image']['tmp_name'],$path.$name2);
					$exq="UPDATE cl_gallery set image='$name3' where id='$id'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$data['status']= '1';
				}else{
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch (PDOException $e){
				print $e->getMessage();
			}
		}
		//===========End Edit Client Function==============
		//===========Start Delete Gallery Function==============
		
		public function deleteGallery(){   
			try {
				$con=$this->getConnection();
				$deleteClient =isset( $_POST['deleteGallery'] ) ? $_POST['deleteGallery']: '';
				$view=$con->prepare("DELETE from cl_gallery WHERE id='$deleteClient'");
				$view->execute();
				if($view == true){ 
					$data='1';
				}else{
					$data='Gallery no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End Delete Gallery Function==============
	
	//============================================================================================================
	// ======================================//END Gallery FUNCTION//============================================
	//============================================================================================================
	
	
	//============================================================================================================
	// ======================================//START PROJECT FUNCTION//===========================================
	//============================================================================================================
		//=============GET Project Function=============

		public function get_Project(){   
			try{    
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM cl_projects order by id DESC");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){    
					$data['id'] = $row['id'];
					$data['title'] = $row['title'];
					if($row['title'] =='lajpat_nagar'){
						$data['title'] ='Lajpat Nagar';
					}else if($row['title'] =='shahpur_jat'){
						$data['title'] ='Shahpur Jat';
					}
					
					$image = $row['image'];
					$data['image'] ="<img src='lib/image/$image' height='80px;' width='120px;'>"; 
					$data['Edit'] = "<a data-toggle='modal' href='#updateProject'  onclick='Edit($row[id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a>";
					$data['Delete'] = "<a data-toggle='modal' onclick='deleteProject($row[id])' title='Delete'   class='btn btn-icon-only purple'><i class='fa fa-trash'></i></a>";
					$getdata[]=$data;
				}
				$data = $getdata;
				header('Content-type: application/json');
				echo  json_encode($getdata,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) {
				print $e->getMessage();
			}
		}
		
		//=============End GET Project Function=============
			//============= ADD Project Function ===============
		public function add_Project(){ 
			try{ 
				$con=$this->getConnection();
				$add =isset( $_POST['add'] ) ? $_POST['add']: '';
				$title =isset( $_POST['title'] ) ? $_POST['title']: '';
				$uploadedfile1 =$_FILES['image1']['name'];
				$uploadedfile2 = $_FILES['image1']['tmp_name'];
				if($add=='add'){ 
					if($uploadedfile1 == ''){  
						$data['error'] = "Please Select Project Image!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
					}
					$path = "image/";
					$ext = explode('.',$uploadedfile1);
					$name2 = md5(rand(999,99999)).'.'.$ext[1];
					move_uploaded_file($uploadedfile2,$path.$name2);
					$exq="INSERT INTO cl_projects set title='$title',image='$name2'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					if($stmt == true){ 
						$data['status']= '1';
					}else{   
						$data['error']= "Something is missing. Please try again!";
					}
				}else{
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch(PDOException $e){
				print $e->getMessage();
			}
		}
		
		//===========End Add Project Function==============
		
	
		//===========Veiw Project list Function============
		
		public function veiw_Project(){   
			try{
				$con=$this->getConnection();
				$veiw_Project =isset( $_POST['veiw_Project'] ) ? $_POST['veiw_Project']: '';
				$view=$con->prepare("SELECT * FROM cl_projects WHERE id='$veiw_Project'");
				$view->execute();
				$data = $view->fetch(PDO::FETCH_ASSOC);
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End View Project Function=================
		
		//============= Edit Project Function ===============
		public function edit_Project(){ 
			try{  
				$con=$this->getConnection();
				$id =isset( $_POST['Projects_id'] ) ? $_POST['Projects_id']: '';
				$title =isset( $_POST['title'] ) ? $_POST['title']: '';
				$uploadedfile1 = $_FILES['image1']['name'];
				if($id>0){
					if($uploadedfile1 == ''){  
						$exq="UPDATE cl_projects set start_date='$start_date',title='$title',disc='$disc' where id='$id'";
					}else{
						$path = "image/";
						$ext = explode('.',$uploadedfile1);
						$name2 = md5(rand(999,99999)).'.'.$ext[1];
						move_uploaded_file($_FILES['image1']['tmp_name'],$path.$name2);
						$exq="UPDATE cl_projects set start_date='$start_date',title='$title',disc='$disc',image='$name2' where id='$id'";
						
					}
					
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$data['status']= '1';
				}else{
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch (PDOException $e){
				print $e->getMessage();
			}
		}
		//===========End Edit Project Function==============
		//===========Start Delete Project Function==============
		
		public function deleteProject(){   
			try {
				$con=$this->getConnection();
				$deleteProject =isset( $_POST['deleteProject'] ) ? $_POST['deleteProject']: '';
				$view=$con->prepare("DELETE from cl_projects WHERE id='$deleteProject'");
				$view->execute();
				if($view == true){  
					$data='1';
				}else{
					$data='projects no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End Delete Project Function==============
	
	//============================================================================================================
	// ======================================//END Project FUNCTION//============================================
	//============================================================================================================
	
	
	//============================================================================================================
	// ======================================//START TESTIMONIAL FUNCTION//===========================================
	//============================================================================================================
		//=============GET Testimonial Function=============

		public function get_Testimonial(){   
			try{    
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM cl_testimonial order by id DESC");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){    
					$data['id'] = $row['id'];
					$image = $row['image'];
					$image1 = $row['image1'];
					$data['image'] ="<img src='lib/image/$image' height='80px;' width='120px;'>"; 
					$data['image1'] ="<img src='lib/image/$image1' height='80px;' width='120px;'>"; 
					$data['Edit'] = "<a data-toggle='modal' href='#updateTestimonial'  onclick='Edit($row[id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a>";
					$data['Delete'] = "<a data-toggle='modal' onclick='deleteTestimonial($row[id])' title='Delete'   class='btn btn-icon-only purple'><i class='fa fa-trash'></i></a>";
					$getdata[]=$data;
				}
				$data = $getdata;
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) {
				print $e->getMessage();
			}
		}
		
		//=============End GET Testimonial Function=============
			//============= ADD Testimonial Function ===============
		public function add_Testimonial(){ 
			try{ 
				$con=$this->getConnection();
				$add =isset( $_POST['add'] ) ? $_POST['add']: '';
				$title =isset( $_POST['title'] ) ? $_POST['title']: '';
				$disc =isset( $_POST['disc'] ) ? $_POST['disc']: '';
				$uploadedfile1 = $_FILES['image']['name'];
				if($add=='add'){ 
					if($uploadedfile1 == ''){  
						$data['error'] = "Please Select Testimonial Image!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
					}
					$path = "image/";
					$ext = explode('.',$uploadedfile1);
					$name2 = md5(rand(999,99999)).'.'.$ext[1];
					move_uploaded_file($_FILES['image']['tmp_name'],$path.$name2);
					$exq="INSERT INTO cl_Testimonial set title='$title',disc='$disc',image='$name2'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					if($stmt == true){ 
						$data['status']= '1';
					}else{   
						$data['error']= "Something is missing. Please try again!";
					}
				}else{
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch(PDOException $e){
				print $e->getMessage();
			}
		}
		
		//===========End Add Testimonial Function==============
		
	
		//===========Veiw Testimonial list Function============
		
		public function veiw_Testimonial(){   
			try{
				$con=$this->getConnection();
				$veiw_Testimonial =isset( $_POST['veiw_Testimonial'] ) ? $_POST['veiw_Testimonial']: '';
				$view=$con->prepare("SELECT * FROM cl_testimonial WHERE id='$veiw_Testimonial'");
				$view->execute();
				$data = $view->fetch(PDO::FETCH_ASSOC);
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){   
				print $e->getMessage();
			}
		}
		//===========End View Testimonial Function=================
		
		//============= Edit Testimonial Function ===============
		public function edit_Testimonial(){ 
			try{  
				$con=$this->getConnection();
				$id =isset( $_POST['Testimonial_id'] ) ? $_POST['Testimonial_id']: '';
				$uploadedfile1 = $_FILES['image']['name'];
				$uploadedfile2 = $_FILES['image']['tmp_name'];
				if($id>0){
					if($uploadedfile1[0] != '' && $uploadedfile1[1] == ''){ 
						$data = $uploadedfile1[0];
						$data1 = $uploadedfile2[0];
						$path = "image/";
						$ext2 = explode('.',$data);
						$name3 = md5(rand(999,99999)).'.'.$ext2[1];
						move_uploaded_file($data1,$path.$name3);
						$exq="UPDATE cl_testimonial set image='$name3' where id='$id'";
					}else if($uploadedfile1[1] != '' && $uploadedfile1[0] == ''){
						$data2 = $uploadedfile1[1];
						$data3 = $uploadedfile2[1];
						$path = "image/";
						$ext1 = explode('.',$data2);
						$name4 = md5(rand(999,99999)).'.'.$ext1[1];
						move_uploaded_file($data3,$path.$name4);
						$exq="UPDATE cl_testimonial set image1='$name4' where id='$id'";
					}else if($uploadedfile1[1] != '' && $uploadedfile1[1] != ''){
						$data2 = $uploadedfile1[1];
						$data3 = $uploadedfile2[1];
						$data = $uploadedfile1[0];
						$data1 = $uploadedfile2[0];
						$path = "image/";
						$ext2 = explode('.',$data);
						$ext1 = explode('.',$data2);
						$name3 = md5(rand(999,99999)).'.'.$ext2[1];
						$name4 = md5(rand(999,99999)).'.'.$ext1[1];
						move_uploaded_file($data1,$path.$name3);
						move_uploaded_file($data3,$path.$name4);
						$exq="UPDATE cl_testimonial set image='$name3',image1='$name4' where id='$id'";
					}
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$data['status']= '1';
				}else{
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch (PDOException $e){
				print $e->getMessage();
			}
		}
		//===========End Edit Testimonial Function==============
		//===========Start Delete Testimonial Function==========
		
		public function deleteTestimonial(){   
			try {
				$con=$this->getConnection();
				$deleteTestimonial =isset( $_POST['deleteTestimonial'] ) ? $_POST['deleteTestimonial']: '';
				$view=$con->prepare("DELETE from cl_testimonial WHERE id='$deleteTestimonial'");
				$view->execute();
				if($view == true){  
					$data='1';
				}else{
					$data='Testimonial no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End Delete Testimonial Function==============
	
	//============================================================================================================
	// ======================================//END TESTIMONIAL FUNCTION//=========================================
	//============================================================================================================
	
	
	//============================================================================================================
	// ======================================//START Collection FUNCTION//===========================================
	//============================================================================================================
		//=============GET Collection Function=============

		public function get_collection(){   
			try{    
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM cl_collection order by id DESC");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){    
					$data['id'] = $row['id'];
					$data['disc'] = $row['disc'];
					$image = $row['image'];
					$data['image'] ="<img src='lib/image/$image' height='80px;' width='120px;'>"; 
					$data['Edit'] = "<a data-toggle='modal' href='#updatecollection'  onclick='Edit($row[id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a>";
					$data['Delete'] = "<a data-toggle='modal' onclick='deletecollection($row[id])' title='Delete'   class='btn btn-icon-only purple'><i class='fa fa-trash'></i></a>";
					$getdata[]=$data;
				}
				$data = $getdata;
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) {
				print $e->getMessage();
			}
		}
		
		//=============End GET Collection Function=============
		
	
		//===========Veiw Collection list Function============
		
		public function veiw_collection(){   
			try{
				$con=$this->getConnection();
				$veiw_collection =isset( $_POST['veiw_collection'] ) ? $_POST['veiw_collection']: '';
				$view=$con->prepare("SELECT * FROM cl_collection WHERE id='$veiw_collection'");
				$view->execute();
				$data = $view->fetch(PDO::FETCH_ASSOC);
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End View Collection Function=================
		
		//============= Edit Collection Function ===============
		public function edit_collection(){ 
			try{  
				$con=$this->getConnection();
				$id =isset( $_POST['collection_id'] ) ? $_POST['collection_id']: '';
				$disc =isset( $_POST['disc'] ) ? $_POST['disc']: '';
				$uploadedfile1 = $_FILES['image']['name'];
				if($id>0){
					if($uploadedfile1 == ''){  
						$exq="UPDATE cl_collection set disc='$disc' where id='$id'";
					}else{
						$path = "image/";
						$ext = explode('.',$uploadedfile1);
						$name2 = md5(rand(999,99999)).'.'.$ext[1];
						move_uploaded_file($_FILES['image']['tmp_name'],$path.$name2);
						$exq="UPDATE cl_collection set disc='$disc',image='$name2' where id='$id'";
						
					}
					
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$data['status']= '1';
				}else{
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
			catch (PDOException $e){
				print $e->getMessage();
			}
		}
		//===========End Edit Collection Function==============
		//===========Start Delete Collection Function==========
		
		public function deletecollection(){   
			try {
				$con=$this->getConnection();
				$deletecollection =isset( $_POST['deletecollection'] ) ? $_POST['deletecollection']: '';
				$view=$con->prepare("DELETE from cl_collection WHERE id='$deletecollection'");
				$view->execute();
				if($view == true){  
					$data='1';
				}else{
					$data='collection no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End Delete Collection Function==============
	
	//============================================================================================================
	// ======================================//END Collection FUNCTION//=========================================
	//============================================================================================================
	
	public function error(){    
		$ab['error'] =  'Record Not Available!'; 
		echo json_encode($ab);
	}
	public function responce(){  
		$data['error'] =  '0'; 
		echo json_encode($data);
	}
}
$employee=new get;
$employee->processApi();
?>