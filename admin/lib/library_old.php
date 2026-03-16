<?php
ob_start();
ini_set('display_errors', 1);
class get
{ 
	//=============Connction=============
	private function getConnection(){
		$hostdb = 'localhost';
		$namedb = 'inventry';
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
	// ======================================//START CATEGORY FUNCTION//===========================================
	//=============================================================================================================
	
	
	
	
	//=============GET Category Function=============
	public function get_category(){  
		try { 
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM pet_category WHERE statud_id='1' order by category_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['category_id'] = $row['category_id'];
				$data['category_name'] = $row['category_name'];
				$data['Edit'] = "<a data-toggle='modal' href='#updateCategory' onclick='Edit($row[category_id])' title='Edit'  class='btn btn-icon-only red'> <i class='fa fa-edit'></i></a>";
				$data['Delete'] = "<a data-toggle='modal' onclick='deletecategory($row[category_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i> </a>";
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
	
	
	
	//=============End GET Category Function=============
	
	
	
	//============= ADD Category Function ===============
	public function add_category(){ 
		try{ 
			$con=$this->getConnection();
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			if($add=='add')
			{
				if(strlen(preg_replace('/\s+/u','',$name)) == 0)
				{ 
					$data['error'] = "Please fill category name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$sql = "SELECT * FROM pet_category WHERE category_name=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$categoryfetch = $stmt->fetch();
				if($categoryfetch != null){ 
					$data['error'] = "Category name already exist!"; 
				}else{ 
					$exq="INSERT INTO pet_category set category_name='$name'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0)
					{
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
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
	
	//===========End Add Category Function==============
	
	
	//===========Veiw Category list Function============
	
	
	public function veiw_category(){   
		try {
			$con=$this->getConnection();
			$editcategory =isset( $_POST['editcategory'] ) ? $_POST['editcategory']: '';
			$view=$con->prepare("SELECT * FROM pet_category WHERE category_id='$editcategory'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	
	//===========End View Category Function=================
	
	
	//============= Update Category Function ===============
	
	
	public function update_category(){ 
		try{ 
			$con=$this->getConnection();
			$category_id =isset( $_POST['category_id'] ) ? $_POST['category_id']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			if($category_id>0)
			{
				if($name == '')
				{ 
					$data['error'] = "Please fill category name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$exp="update  pet_category set category_name='$name' where category_id='$category_id'";
				$stmt = $con->prepare($exp);
				$stmt->execute(); 
				if($stmt)
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
	
	
	//===========End Update Category Function==============
	
	
	
	//===========End Delete Category Function==============
	
	
	public function deletecategory(){   
		try {
			$con=$this->getConnection();
			$deletecategory =isset( $_POST['deletecategory'] ) ? $_POST['deletecategory']: '';
			$view=$con->prepare("DELETE from pet_category WHERE category_id='$deletecategory'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Category no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	//===========End Delete Category Function==============
	
	
	//=============================================================================================================
	// =======================================//END CATEGORY FUNCTION//============================================
	//=============================================================================================================
	
	//=============================================================================================================
	// ========================================//START CITY FUNCTION//=============================================
	//=============================================================================================================
	
	
	
	
	//=============GET city Function=============
	
	
	public function get_city(){   
		try {
			$con=$this->getConnection();
			$page=$con->prepare("select c.*,s.state_id AS s_id,s.state_name AS s_name from city_list c left join state_list s on(c.state_id = s.state_id) where c.status = '1'");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['s_id'] = $row['s_id'];
				$data['c_name'] = 'India';
				$data['s_name'] = $row['s_name'];
				$data['city_name'] = $row['city_name'];
				$data['Edit'] = "<a data-toggle='modal' href='#updatecity' onclick='Edit($row[city_id])' title='Edit'  class='btn btn-icon-only red'> <i class='fa fa-edit'></i></a>";
				$data['Delete'] = "<a data-toggle='modal' onclick='deletecity($row[city_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	//=============End GET city Function=============
	
	
	
	//============= ADD city Function ===============
	public function add_city(){ 
		try{ 
			$con=$this->getConnection();
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$state_id =isset( $_POST['state_id']) ? $_POST['state_id']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			if($add=='add')
			{
				if(strlen(preg_replace('/\s+/u','',$name)) == 0)
				{ 
					$data['error'] = "Please fill city name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$sql = "SELECT * FROM city_list WHERE city_name=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$cityfetch = $stmt->fetch();
				if($cityfetch != null){ 
					$data['error'] = "city name already exist!"; 
				}else{ 
					$exq="INSERT INTO city_list set country_id='1',state_id='$state_id',city_name='$name'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0)
					{
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
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
	
	//===========End Add city Function==============
	
	
	//===========Veiw city list Function============
	
	
	public function veiw_city(){   
		try {
			$con=$this->getConnection();
			$editcity =isset( $_POST['editcity'] ) ? $_POST['editcity']: '';
			$view=$con->prepare("SELECT * FROM city_list WHERE city_id='$editcity'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	
	//===========End View city Function=================
	
	
	//============= Update city Function ===============
	
	
	public function update_city(){ 
		try{ 
			$con=$this->getConnection();
			$city_id =isset( $_POST['city_id'] ) ? $_POST['city_id']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			//$state_id =isset( $_POST['state_id']) ? $_POST['state_id']: '';
			if($city_id>0)
			{
				if($name == '')
				{ 
					$data['error'] = "Please fill city name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$exp="update  city_list set country_id='1',city_name='$name' where city_id='$city_id'";
				$stmt = $con->prepare($exp);
				$stmt->execute(); 
				if($stmt)
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
	
	
	//===========End Update city Function==============
	
	
	
	//===========End Delete city Function==============
	
	
	public function deletecity(){   
		try {
			$con=$this->getConnection();
			$deletecity =isset( $_POST['deletecity'] ) ? $_POST['deletecity']: '';
			$view=$con->prepare("DELETE from city_list WHERE city_id='$deletecity'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='city no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	//===========End Delete city Function==============
	
	
	//=============================================================================================================
	// =========================================//END CITY FUNCTION//==============================================
	//=============================================================================================================
	
	//=============================================================================================================
	// =======================================//START STATE FUNCTION//=============================================
	//=============================================================================================================
	
	
	
	
	//=============GET State Function=============
	
	
	public function get_state(){  
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM state_list where status=1 order by state_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['c_name'] = 'India';
				$data['state_id'] = $row['state_id'];
				$data['state_name'] = $row['state_name'];
				$data['Edit'] = "<a data-toggle='modal' href='#updatestate' onclick='Edit($row[state_id])' title='Edit'  class='btn btn-icon-only red'> <i class='fa fa-edit'></i></a>";
				$data['Delete'] = "<a data-toggle='modal' onclick='deletestate($row[state_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	//=============End GET State Function=============
	
	
	
	//============= ADD city Function ===============
	public function add_state(){ 
		try{ 
			$con=$this->getConnection();
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			if($add=='add')
			{
				if(strlen(preg_replace('/\s+/u','',$name)) == 0)
				{ 
					$data['error'] = "Please fill state name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$sql = "SELECT * FROM state_list WHERE state_name=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$statefetch = $stmt->fetch();
				if($statefetch != null){ 
					$data['error'] = "state name already exist!"; 
				}else{ 
					$exq="INSERT INTO state_list set country_id='1',state_name='$name'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0)
					{
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
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
	
	//===========End Add State Function==============
	
	
	//===========Veiw State list Function============
	
	
	public function veiw_state(){   
		try {
			$con=$this->getConnection();
			$editstate =isset( $_POST['editstate'] ) ? $_POST['editstate']: '';
			$view=$con->prepare("SELECT * FROM state_list WHERE state_id='$editstate'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	
	//===========End View State Function=================
	
	
	//============= Update State Function ===============
	
	
	public function update_state(){ 
		try{ 
			$con=$this->getConnection();
			$state_id =isset( $_POST['state_id'] ) ? $_POST['state_id']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			if($state_id>0)
			{
				if($name == '')
				{ 
					$data['error'] = "Please fill state name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$stmt = $con->prepare($exp);
				$stmt->execute(); 
				if($stmt)
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
	
	
	//===========End Update State Function==============
	
	
	
	//===========End Delete State Function==============
	
	
	public function deletestate(){   
		try {
			$con=$this->getConnection();
			$deletestate =isset( $_POST['deletestate'] ) ? $_POST['deletestate']: '';
			$view=$con->prepare("DELETE from state_list WHERE state_id='$deletestate'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='state no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	//===========End Delete State Function==============
	
	
	//=============================================================================================================
	// =========================================//END STATE FUNCTION//=============================================
	//=============================================================================================================
	//=============================================================================================================
	// ======================================//START PINCODE FUNCTION//===========================================
	//=============================================================================================================
	
	
	
	
	//=============GET PINCODE Function=============
	
	
	public function get_pincode(){  
		try { 
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM location WHERE status='1' order by id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['id'] = $row['id'];
				$data['location'] = $row['location'];
				$data['pincode'] = $row['pincode'];
				$data['Edit'] = "<a data-toggle='modal' href='#updatepincode' onclick='Edit($row[id])' title='Edit'  class='btn btn-icon-only red'> <i class='fa fa-edit'></i></a>";
				$data['Delete'] = "<a data-toggle='modal' onclick='deletepincode($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i> </a>";
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
	
	//=============End GET PINCODE Function=============
	
	
	
	//============= ADD PINCODE Function ===============
	public function add_pincode(){ 
		try{ 
			$con=$this->getConnection();
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$location =isset( $_POST['location']) ? $_POST['location']: '';
			$pincode =isset( $_POST['pincode']) ? $_POST['pincode']: '';
			if($add=='add')
			{
				if(strlen(preg_replace('/\s+/u','',$location)) == 0 || strlen(preg_replace('/\s+/u','',$pincode)) == 0)
				{ 
					$data['error'] = "Please fill location/pincode!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$sql = "SELECT * FROM location WHERE pincode=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$pincode"));
				$categoryfetch = $stmt->fetch();
				if($categoryfetch != null){ 
					$data['error'] = "Pincode number already exist!";  
				}else{ 
					$exq="INSERT INTO location set location='$location',pincode='$pincode'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0)
					{
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
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
	
	//===========End Add PINCODE Function==============
	
	
	//===========Veiw PINCODE list Function============
	
	
	public function veiw_pincode(){   
		try {
			$con=$this->getConnection();
			$editpincode =isset( $_POST['editpincode'] ) ? $_POST['editpincode']: '';
			$view=$con->prepare("SELECT * FROM location WHERE id='$editpincode'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	
	//===========End View PINCODE Function=================
	
	
	//============= Update PINCODE Function ===============
	
	
	public function update_pincode(){ 
		try{ 
			$con=$this->getConnection();
			$pincode_id =isset( $_REQUEST['pincode_id'] ) ? $_REQUEST['pincode_id']: '';
			$location =isset( $_REQUEST['location']) ? $_REQUEST['location']: '';
			$pincode =isset( $_REQUEST['pincode']) ? $_REQUEST['pincode']: '';
			if($pincode_id>0){
				if($pincode == ''||$location == ''){ 
					$data['error'] = "Please fill pincode/location!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$exp="update  location set location='$location',pincode='$pincode' where id='$pincode_id'";
				$stmt = $con->prepare($exp);
				$stmt->execute(); 
				if($stmt){ 
					$data['status']= '1';
				}else{ 
					$data['error']= "Something is tmissing. Please try again!";
				}
			}else{ 
				$data['error']= "Something is missing. Please try again!";
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	
	//===========End Update PINCODE Function==============
	
	
	
	//===========End Delete PINCODE Function==============
	
	
	public function deletepincode(){   
		try {
			$con=$this->getConnection();
			$deletepincode =isset( $_POST['deletepincode'] ) ? $_POST['deletepincode']: '';
			$view=$con->prepare("DELETE from location WHERE id='$deletepincode'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Category no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	//===========End Delete PINCODE Function==============
	
	
	//=============================================================================================================
	// =======================================//END PINCODE FUNCTION//============================================
	//=============================================================================================================
	
	
	//=============================================================================================================
	// ========================================//START BREED FUNCTION//============================================
	//=============================================================================================================
	
	
	
	
	//=============GET Breed Function=============
	
	
	public function get_breed(){   
		try { 
			$con=$this->getConnection();
			$page=$con->prepare("SELECT pb.*,pc.category_name as cname FROM pet_breed as pb left join pet_category as pc on pc.category_id= pb.category_id where pb.status_id=1 order by breed_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['breed_id'] = $row['breed_id'];
				$data['breed_name'] = $row['breed_name'];
				$data['image'] = '<img src=lib/image/'.$row['image'].' hieght=60px width=80px>';
				$data['category_name'] = $row['cname'];
				$data['Addimage'] = "<a data-toggle='modal' href='#addbreed' onclick='Addimage($row[breed_id])' title='Add image'  class='btn '>ADD IMAGE</a>";
				$data['Edit'] = "<a data-toggle='modal' href='#updatebreed' onclick='Edit($row[breed_id])' title='Edit'  class='btn btn-icon-only red'> <i class='fa fa-edit'></i></a>";
				$data['Delete'] = "<a data-toggle='modal' onclick='deletebreed($row[breed_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i> </a>";
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
	public function get_breed1(){    
		try { 
			$con=$this->getConnection();
			$dadat_id =isset( $_POST['dadat_id'] ) ? $_POST['dadat_id']: '';
			$page=$con->prepare("SELECT * FROM pet_breed where category_id ='$dadat_id' order by breed_id ASC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['breed_id'] = $row['breed_id'];
				$data['breed_name'] = $row['breed_name'];
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
	
	//=============End GET Breed Function=============
	
	
	
	//============= ADD Breed Function ===============
	public function add_breed(){ 
		try{ 
			$con=$this->getConnection();
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			$category_id =isset( $_POST['category_id']) ? $_POST['category_id']: '';
			$uploadedfile1 = $_FILES['images']['name'];
			if($add=='add')
			{
				$check = explode('.',$uploadedfile1);
				if($check !='PNG' || $check !='png' || $check !='jpg'||$check !='JPG'||$check !='JPEG'||$check !='jpeg'||$check !='SVG'||$check !='svg'){
					$data['error'] = "Please upload valid Extension (png,jpg,jpeg,svg)";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($category_id ==''){  
					$data['error'] = "Please select category name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if(strlen(preg_replace('/\s+/u','',$name)) == 0){  
					$data['error'] = "Please fill breed name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($uploadedfile1 == ''){  
					$data['error'] = "Please Select Breed Image!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$sql = "SELECT * FROM pet_breed WHERE breed_name=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$statefetch = $stmt->fetch();
				$path = "image/";
				$name2 = md5(rand(999,99999)).$uploadedfile1;
				move_uploaded_file($_FILES['images']['tmp_name'],$path.$name2);
				if($statefetch != null){ 
					$data['error'] = "breed name already exist!"; 
				}else{    
					$exq="INSERT INTO pet_breed set category_id='$category_id',breed_name='$name',image='$name2'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0)
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
	
	//===========End Add Breed Function==============
	
	//============= ADD Image Function ===============
	public function add_image(){
		try{ 
			$con=$this->getConnection();
			$breed_id =isset( $_POST['breed_id'] ) ? $_POST['breed_id']: ''; 
			$uploadedfile1 = $_FILES['files']['name'];
				if($name == '')
				{ 
					$data['error'] = "Please fill state name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if(count($uploadedfile1) == 0)
				{ 
					$data['error'] = "Please Select Breed Image!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				foreach($uploadedfile1 as $image){
					$path = "image/breedimage/";
					$name2 = md5(rand(999,99999)).$image;
					$url =$path.$name2;
					move_uploaded_file($_FILES['files']['tmp_name'][0],$path.$name2);
					$exq="INSERT INTO breed_multiimage set image_name='$name',image_url='$url',breed_id='$breed_id'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0)
					{
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
				}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}
		catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	
	//===========End Add Image Function==============
	
	//===========Veiw Breed list Function============
	
	
	public function veiw_breed(){   
		try {
			$con=$this->getConnection();
			$editbreed =isset( $_POST['editbreed'] ) ? $_POST['editbreed']: '';
			$view=$con->prepare("SELECT * FROM pet_breed WHERE breed_id='$editbreed'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	
	//===========End View Breed Function=================
	
	
	//============= Update Breed Function ===============
	
	
	public function update_breed(){ 
		try{ 
			$con=$this->getConnection();
			$breed_id =isset( $_POST['breed_id'] ) ? $_POST['breed_id']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			$uploadedfile1 = $_FILES['image']['name'];
			if($name == '')
			{ 
				$data['error'] = "Please fill breed name!";
				header('Content-type: application/json');
				echo json_encode($data,JSON_PRETTY_PRINT);
				return false;
			}
			
			if($uploadedfile1 == ''){ 
				$exq="UPDATE pet_breed set breed_name='$name' where breed_id='$breed_id'";
			}else{
				$path = "image/";
				$name2 = md5(rand(999,99999)).$uploadedfile1;
				move_uploaded_file($_FILES['image']['tmp_name'],$path.$name2);
				$exq="UPDATE pet_breed set breed_name='$name',image='$name2' where breed_id='$breed_id'";
			}
			$stmt = $con->prepare($exq);
			$stmt->execute(); 
			$lastId = $con->lastInsertId();
			if($stmt)
			{
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
	
	
	//===========End Update Breed Function==============
	
	
	
	//===========End Delete Breed Function==============
	
	
	public function deletebreed(){   
		try {
			$con=$this->getConnection();
			$deletebreed =isset( $_POST['deletebreed'] ) ? $_POST['deletebreed']: '';
			$view=$con->prepare("DELETE from pet_breed WHERE breed_id='$deletebreed'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='breed no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	//===========End Delete Breed Function==============
	
	
	//=============================================================================================================
	//==========================================//END BREED FUNCTION//=============================================
	//=============================================================================================================
	
	
	//=============================================================================================================
	// ======================================//START USER FUNCTION//===========================================
	//=============================================================================================================
	
	
	
	
	//=============GET User Function=============
	
	
	public function get_user(){  
		try { 
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM owner_registration WHERE status_id='1' order by owner_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['owner_id'] = $row['owner_id'];
				$page1=$con->prepare("SELECT * FROM pet_detail WHERE owner_id='$data[owner_id]'");
				$page1->execute();
				$total1 = $page1->rowCount();
				$data['full_name'] = $row['full_name'];
				$data['email_id'] = $row['email_id'];
				$data['contact_number'] = $row['contact_number'];
				if($total1 >0){   
					$data['View_Pet'] = "<a data-toggle='modal' href='#viewpet' onclick='Edit($row[owner_id])' title='View Pet'  class='btn btn-icon-only green'><b>$total1</b></a>"; 
				}else{ 
					$data['View_Pet'] = "<a data-toggle='modal' href='#viewpet' onclick='Edit($row[owner_id])' title='View Pet'  class='btn btn-icon-only red'><b>0</b></a>"; 
				}
				
				$data['Action'] = "<a data-toggle='modal' href='#viewUser' onclick='view($row[owner_id])' title='View'  class='btn btn-icon-only green'> <i class='fa fa-eye'></i></a>	<a data-toggle='modal' onclick='deleteUser($row[owner_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	
	
	//============= ADD User Function ===============
	public function add_user(){  
		try{ 
			$con=$this->getConnection();
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			if($add=='add')
			{
				if($name == '')
				{ 
					$data['error'] = "Please fill category name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$sql = "SELECT * FROM pet_category WHERE category_name=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$categoryfetch = $stmt->fetch();
				if($categoryfetch != null){ 
					$data['error'] = "Category name already exist!"; 
				}else{ 
					$exq="INSERT INTO pet_category set category_name='$name'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0)
					{
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
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
	
	//===========End Add User Function==============
	
	
	//===========Veiw User list Function============
	
	
	public function veiw_User(){   
		try {
			$con=$this->getConnection();
			$edituser =isset( $_POST['edituser'] ) ? $_POST['edituser']: '';
			$view=$con->prepare("SELECT * FROM owner_registration WHERE owner_id='$edituser'");
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
	
	//===========Veiw User profile Function============
	
	
	public function veiw_Userprofile(){   
		try {
			$con=$this->getConnection();
			$edituser =isset( $_POST['editUser'] ) ? $_POST['editUser']: '';
			$view=$con->prepare("SELECT * FROM owner_registration WHERE owner_id='$edituser'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	
	//===========End View User profile Function=================
	
	//===========Veiw User profile Function============
	
	
	public function veiw_petprofile(){  
		try { 
			$con=$this->getConnection();
			$edituser =isset( $_POST['editUser'] ) ? $_POST['editUser']: '';
			$page=$con->prepare("SELECT * FROM pet_detail WHERE owner_id='$edituser'");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			if($total >0){
				foreach($sportfetch as $row){ 
					$data['petdetails_id'] = $row['petdetails_id'];
					$page1=$con->prepare("SELECT * FROM mating_image WHERE pet_id='$data[petdetails_id]'");
					$page1->execute();
					$total1 = $page1->fetch(PDO::FETCH_ASSOC);
					$data['pet_image'] = '<img src="http://52.220.254.228/zoeyapp/'.$total1['image'].'" height="60 px;" width="70px">';
					$data['pet_name'] = $row['pet_name'];
					$data['age'] = $row['age'];
					$data['trained_type'] = $row['trained_type'];
					if($row['category_id'] == 1){
						$data['category_id'] = 'Dog';	
					}elseif($row['category_id'] == 2){ 
						$data['category_id'] = 'Cat';
					}
					$data['gender_id'] = $row['gender_id'];
					if($row['status_id'] == 1){
						$data['status_id'] = 'Active';	
					}else{ 
						$data['status_id'] = 'DeActive';
					}
					$data['DELETE'] = "<a data-toggle='modal' onclick='deletepet($row[petdetails_id])' title='Delete'  class='btn '> <i class='fa fa-trash'> Delete</i></a>";
					$getdata[]=$data;
				}
				$data = $getdata;
			}else{
				$data['error']='1';
			}
			
			
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	
	
	//===========End View User profile Function=================
	
	//============= Update User Function ===============
	
	
	public function update_user(){ 
		try{ 
			$con=$this->getConnection();
			$category_id =isset( $_POST['category_id'] ) ? $_POST['category_id']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			if($category_id>0)
			{
				if($name == '')
				{ 
					$data['error'] = "Please fill category name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$exp="update  pet_category set category_name='$name' where category_id='$category_id'";
				$stmt = $con->prepare($exp);
				$stmt->execute(); 
				if($stmt)
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
	
	
	//===========End Update USER Function==============
	
	
	
	//===========End Delete USER Function==============
	
	
	public function deleteUser(){   
		try {
			$con=$this->getConnection();
			$deleteUser =isset( $_POST['deleteUser'] ) ? $_POST['deleteUser']: '';
			$view=$con->prepare("DELETE from owner_registration WHERE owner_id='$deleteUser'");
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
	
	//===========End Delete PET Function==============
	
	
	public function deletepet(){   
		try {
			$con=$this->getConnection();
			$delete_pet =isset( $_POST['delete_pet'] ) ? $_POST['delete_pet']: '';
			$view=$con->prepare("DELETE from pet_detail WHERE petdetails_id='$delete_pet'");
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
	public function mating(){   
		try {
			$con=$this->getConnection();
			$editUser =isset( $_POST['editUser'] ) ? $_POST['editUser']: '';
			$view=$con->prepare("SELECT * from pet_mating WHERE owner_id='$editUser' and status_id=1");
			$view->execute();
			$matingfetch = $view->fetchAll();
			$total1 = $view->rowCount();
			if($total1 >0){
				foreach($matingfetch as $row){       
					$data['total'] = $total1;
					$data['pet_mating_id'] = $row['pet_mating_id'];
					$data['pet_category_id'] = $row['pet_category_id'];
					$pet_id= $row['pet_id'];
					$page1=$con->prepare("SELECT pd.pet_name,pd.age,c.category_name,b.breed_name FROM pet_detail as pd left join pet_category as c on c.category_id = pd.category_id left join pet_breed as b on b.breed_id =pd.breed_id WHERE pd.petdetails_id='$pet_id'");
					$page1->execute();
					$total11 = $page1->fetch(PDO::FETCH_ASSOC);
					$page12=$con->prepare("SELECT * FROM mating_image WHERE pet_id='$pet_id'");
					$page12->execute();
					$total12 = $page12->fetch(PDO::FETCH_ASSOC);
					$data['pet_image'] = '<img src="http://52.220.254.228/zoeyapp/'.$total12['image'].'" height="60 px;" width="70px">';
					$data['pet_name'] = $total11['pet_name'];
					$data['age'] = $total11['age'];
					$data['category_name'] = $total11['category_name'];
					$data['breed_name'] = $total11['breed_name'];
					$data['gender_id'] = $row['pet_gender_id'];
					$date = explode(' ',$row['created_date']);
					$data['request_date'] = $date[0];
					if($row['status_id'] == 1){ 
						$data['status_id'] = 'Pending';	
					}else{ 
						$data['status_id'] = 'Complete';
					}
					$getdata[]=$data;
				}
				$data = $getdata;
			}else{
				$data['error']='1';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	//===========End Delete PET Function==============
	public function grooming(){   
		try {
			$con=$this->getConnection();
			$editUser =isset( $_POST['editUser'] ) ? $_POST['editUser']: '';
			$view=$con->prepare("SELECT * from services WHERE user_id='$editUser'");
			$view->execute();
			$groomingfetch = $view->fetchAll();
			$total1 = $view->rowCount();
			if($total1 >0){ 
				foreach($groomingfetch as $row){         
					$data['total'] = $total1;
					$data['service_id'] = $row['service_id'];
					$data['category_id'] = $row['category_id'];
					$data['user_name'] = $row['user_name'];
					$data['phone_number'] = $row['phone_number'];
					$data['user_email'] = $row['user_email'];
					$data['order_id'] = $row['order_id'];
					$data['services'] = $row['services'];
					$data['order_status'] = $row['order_status'];
					$data['price'] = $row['price'];
					$data['ap_date'] = $row['ap_date'];
					$data['assigned_name'] = $row['assigned_name'];
					$data['accept'] = $row['accept'];
					$pet_id= $row['pet_id'];
					$page1=$con->prepare("SELECT pd.pet_name,pd.age,c.category_name,b.breed_name FROM pet_detail as pd left join pet_category as c on c.category_id = pd.category_id left join pet_breed as b on b.breed_id =pd.breed_id WHERE pd.petdetails_id='$pet_id'");
					$page1->execute();
					$total11 = $page1->fetch(PDO::FETCH_ASSOC);
					$page12=$con->prepare("SELECT * FROM mating_image WHERE pet_id='$pet_id'");
					$page12->execute();
					$total12 = $page12->fetch(PDO::FETCH_ASSOC);
					$data['pet_image'] = '<img src="http://52.220.254.228/zoeyapp/'.$total12['image'].'" height="60 px;" width="70px">';
					$data['pet_name'] = $total11['pet_name'];
					$data['age'] = $total11['age'];
					$data['category_name'] = $total11['category_name'];
					$data['breed_name'] = $total11['breed_name'];
					$date = explode(' ',$row['created_date']);
					$data['order_date'] = $date[0];
					if($row['status'] == 1){ 
						$data['status'] = 'Close';	
					}else{ 
						$data['status'] = 'Open';
					}
					$getdata[]=$data;
				}
				$data = $getdata;
			}else{
				$data['error']='1';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	public function walker(){   
		try {
			$con=$this->getConnection();
			$editUser =isset( $_POST['editUser'] ) ? $_POST['editUser']: '';
			$view=$con->prepare("SELECT * from s_petwalker_order WHERE user_id='$editUser'");
			$view->execute();
			$groomingfetch = $view->fetchAll();
			$total1 = $view->rowCount();
			if($total1 >0){ 
				foreach($groomingfetch as $row){         
					$data['total'] = $total1;
					$data['id'] = $row['id'];
					$data['order_no'] = $row['order_no'];
					$petwalker_id = $row['petwalker_id'];
					$page13=$con->prepare("SELECT full_name FROM s_registration WHERE id='$petwalker_id'");
					$page13->execute();
					$total13 = $page13->fetch(PDO::FETCH_ASSOC);
					$data['walker_name'] = $total13['full_name'];
					$data['size'] = $row['size'];
					$data['order_date'] = $row['order_date'];
					$data['amount'] = $row['amount'];
					$data['pet']= $row['pet_id'];
					$pet_id= $row['pet_id'];
					$page1=$con->prepare("SELECT pet_name FROM pet_detail WHERE petdetails_id='$pet_id'");
					$page1->execute();
					$total11 = $page1->fetch(PDO::FETCH_ASSOC);
					$page12=$con->prepare("SELECT * FROM mating_image WHERE pet_id='$pet_id'");
					$page12->execute();
					$total12 = $page12->fetch(PDO::FETCH_ASSOC);
					$data['pet_image'] = '<img src="http://52.220.254.228/zoeyapp/'.$total12['image'].'" height="60 px;" width="70px">';
					$data['pet_name'] = $total11['pet_name'];
					$date = explode(' ',$row['created_on']);
					$data['created_date'] = $date[0];
					if($row['status'] == 1){ 
						$data['status'] = 'Open';	
					}else{ 
						$data['status'] = 'Close';
					}
					$getdata[]=$data;
				}
				$data = $getdata;
			}else{
				$data['error']='1';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	public function vaccination(){   
		try {
			$con=$this->getConnection();
			$editUser =isset( $_POST['editUser'] ) ? $_POST['editUser']: '';
			$view=$con->prepare("SELECT * from vaccination_order WHERE user_id='$editUser'");
			$view->execute();
			$vaccingfetch = $view->fetchAll();
			$total1 = $view->rowCount();
			if($total1 >0){ 
				foreach($vaccingfetch as $row){         
					$data['total'] = $total1;
					$data['id'] = $row['id'];
					$data['order_no'] = $row['order_no'];
					$petwalker_id = $row['s_user_id'];
					$page13=$con->prepare("SELECT full_name FROM s_registration WHERE id='$petwalker_id'");
					$page13->execute();
					$total13 = $page13->fetch(PDO::FETCH_ASSOC);
					$data['Doctor'] = $total13['full_name'];
					$data['Booking_date'] = $row['Booking_date'];
					$data['amount'] = $row['total_price'];
					$date = explode(' ',$row['created']);
					$data['created_date'] = $date[0];
					if($row['status'] == 0){ 
						$data['status'] = 'Open';	
					}else{ 
						$data['status'] = 'Close';
					}
					$getdata[]=$data;
				}
				$data = $getdata;
			}else{
				$data['error']='1';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	public function training(){   
		try {
			$con=$this->getConnection();
			$editUser =isset( $_POST['editUser'] ) ? $_POST['editUser']: '';
			$view=$con->prepare("SELECT * from training WHERE user_id='$editUser'");
			$view->execute();
			$vaccingfetch = $view->fetchAll();
			$total1 = $view->rowCount();
			if($total1 >0){ 
				foreach($vaccingfetch as $row){         
					$data['training_id'] = $row['training_id'];
					$data['order_id'] = $row['order_id'];
					$data['type'] = $row['type'];
					$data['pet_name'] = $row['pet_name'];
					$data['order_status'] = $row['order_status'];
					$data['price'] = $row['price'];
					$data['ap_date'] = $row['ap_date'];
					$pet_id = $row['pet_id'];
					$page12=$con->prepare("SELECT * FROM mating_image WHERE pet_id='$pet_id'");
					$page12->execute();
					$total12 = $page12->fetch(PDO::FETCH_ASSOC);
					$data['pet_image'] = '<img src="http://52.220.254.228/zoeyapp/'.$total12['image'].'" height="60 px;" width="70px">';
					$data['assigned_name'] = $row['assigned_name'];
					$data['accept'] = $row['accept'];					
					$date = explode(' ',$row['created_date']);
					$data['order_date'] = $date[0];
					if($row['status'] == 1){ 
						$data['status'] = 'Close';	
					}else{ 
						$data['status'] = 'Open';
					}
					$getdata[]=$data;
				}
				$data = $getdata;
			}else{
				$data['error']='1';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	public function totalcount(){   
		try {
			$con=$this->getConnection();
			$editUser =isset( $_POST['editUser'] ) ? $_POST['editUser']: '';
			$page=$con->prepare("SELECT * FROM pet_detail WHERE owner_id='$editUser'");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			$data['count1'] = $total;
			$view1=$con->prepare("SELECT * from pet_mating WHERE owner_id='$editUser' and status_id=1");
			$view1->execute();
			$total1 = $view1->rowCount();
			$data['count2'] = $total1;
			$view2=$con->prepare("SELECT * from services WHERE user_id='$editUser'");
			$view2->execute();
			$total2 = $view2->rowCount();
			$data['count3'] = $total2;
			$view3=$con->prepare("SELECT * from s_petwalker_order WHERE user_id='$editUser'");
			$view3->execute();
			$total3 = $view3->rowCount();
			$data['count4'] = $total3;
			$view4=$con->prepare("SELECT * from vaccination_order WHERE user_id='$editUser'");
			$view4->execute();
			$total4 = $view4->rowCount();
			$data['count5'] = $total4;
			$view5=$con->prepare("SELECT * from training WHERE user_id='$editUser'");
			$view5->execute();
			$total5 = $view5->rowCount();
			$data['count6'] = $total5;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	//============================================================================================================
	// ======================================//END USER FUNCTION//============================================
	//============================================================================================================
	
	
	//============================================================================================================
	// ======================================//START PAYMENT FUNCTION//===========================================
	//============================================================================================================
		//=============GET Payment Function=============

		
		public function get_payment(){  
			try { 
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM payment order by id DESC");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){ 
					$data['id'] = $row['id'];
					$data['user_name'] = $row['user_name'];
					$data['order_id'] = $row['order_id'];
					$data['phone'] = $row['phone'];
					$data['purpose'] = $row['purpose'];
					$data['amount'] = $row['amount'];
					$data['payment_status'] = strtoupper($row['payment_status']);
					$date = explode(' ',$row['created_at']);
					$data['order_date'] = $date[0];
					$data['Action'] = "<a data-toggle='modal' onclick='deletePayment($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
		
		//=============End GET Payment Function=============
		
		//===========Start Banner Payment Function==============
		
		public function deletePayment(){   
			try {
				$con=$this->getConnection();
				$deletePayment =isset( $_POST['deletePayment'] ) ? $_POST['deletePayment']: '';
				$view=$con->prepare("DELETE from payment WHERE id='$deletePayment'");
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
		//===========End Banner Payment Function==============
	
	//============================================================================================================
	// ======================================//END PAYMENT FUNCTION//============================================
	//============================================================================================================
	
	
	//============================================================================================================
	// ======================================//START BANNER FUNCTION//===========================================
	//============================================================================================================
		//=============GET Banner Function=============

		
		public function get_banner(){  
			try { 
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM homepage_banner order by banner_id DESC");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){  
					$data['banner_id'] = $row['banner_id'];
					$image = $row['banner'];
					$data['banner'] ="<img src='lib/images/$image' height='80px;' width='120px;'>"; 
					if( $row['status_id']==1){ 
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
					$path = "image/";
					$name2 = md5(rand(999,99999)).$uploadedfile1;
					move_uploaded_file($_FILES['image']['tmp_name'],$path.$name2);
					$exq="INSERT INTO homepage_banner set banner='$name2'";
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
				$view=$con->prepare("SELECT * FROM homepage_banner WHERE banner_id='$veiw_banner'");
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
					$path = "image/";
					$name2 = md5(rand(999,99999)).$uploadedfile1;
					move_uploaded_file($_FILES['image']['tmp_name'],$path.$name2);
					$exq="UPDATE homepage_banner set banner='$name2' where banner_id='$banner_id'";
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
					$view=$con->prepare("SELECT * FROM homepage_banner WHERE  banner_id='$active_banner'");
					$view->execute();
					$dat1 = $view->fetch(PDO::FETCH_ASSOC);
					if($dat1['status_id'] ==1){
						$exq="UPDATE homepage_banner set status_id='0' where banner_id='$active_banner'";
						$stmt = $con->prepare($exq);
						$stmt->execute(); 
					}else{
						$exq="UPDATE homepage_banner set status_id='1' where banner_id='$active_banner'";
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
				$view=$con->prepare("DELETE from homepage_banner WHERE banner_id='$deleteBanner'");
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
	// ==================================//START STORE REQUEST FUNCTION//========================================
	//============================================================================================================
		//=============GET StoreRequest Function=============

		
		public function get_storeRequest(){  
			try { 
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM user_search order by id DESC");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){  
					$data['id'] = $row['id'];
					$user_id = $row['user_id'];
					$data['search_item']  = $row['search_item'];
					$page1=$con->prepare("SELECT * FROM owner_registration WHERE owner_id = '$user_id'");
					$page1->execute();
					$sportfetch1 = $page1->fetch(PDO::FETCH_ASSOC);
					//print_r($sportfetch1);
					$data['full_name'] = $sportfetch1['full_name'];
					$data['email_id'] = $sportfetch1['email_id'];
					$data['contact_number'] = $sportfetch1['contact_number'];
					$date = explode(' ',$row['created_date']);
					$data['order_date'] = $date[0];
					$data['Action'] = "<a data-toggle='modal' onclick='deletestoreRequest($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
		
		//=============End GET Payment Function=============
		
		//===========Start Banner Payment Function==============
		
		public function deletestoreRequest(){   
			try { 
				$con=$this->getConnection();
				$deletestoreRequest =isset( $_POST['deletestoreRequest'] ) ? $_POST['deletestoreRequest']: '';
				$view=$con->prepare("DELETE from user_search WHERE id='$deletestoreRequest'");
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
		//===========End Banner Payment Function==============
	
	//============================================================================================================
	// ====================================//END STORE REQUEST FUNCTION//=========================================
	//============================================================================================================
	
	
	//============================================================================================================
	// ======================================//START FORUM FUNCTION//===========================================
	//============================================================================================================
	
	
	
	
	//=============GET Forum Function=============
	
	
	public function get_forum(){  
		try{  
			$con=$this->getConnection();
			$page=$con->prepare("SELECT f.*,ur.full_name FROM `forum` as f left join owner_registration as ur on ur.owner_id=f.user_id  order by f.forum_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){    
				$data['forum_id'] = $row['forum_id'];
				$data['user_id'] = $row['user_id'];
				$data['full_name'] = $row['full_name'];
				$data['category'] = $row['category'];
				$data['post'] = $row['post'];
				$page1=$con->prepare("SELECT * FROM forum_question_like WHERE q_id = '$data[forum_id]'");
				$page1->execute();
				$total = $page1->rowCount();
				$data['like_count'] = $total;
				$page11=$con->prepare("SELECT * FROM forum_detail WHERE forum_id = '$data[forum_id]'");
				$page11->execute();
				$total1 = $page11->rowCount();
				$data['like_count'] ="<span style='padding-left: 14px; padding-right: 14px; margin-left: 10%;' class='btn btn-xs green'> $total</span>"; 
				if($total1 >0){   
					$data['View_Answer'] = "<a data-toggle='modal' href='#viewanswer' onclick='forumAnswer($row[forum_id])' title='View Answer' style='padding-left: 14px; padding-right: 14px;    margin-left: 10%;' class='btn btn-xs blue'> $total1 </a>"; 
				}else{ 
					$data['View_Answer'] = "<a style='padding-left: 14px; padding-right: 14px;    margin-left: 10%;' title='No Answer' class='btn btn-xs red'>0</a></button>"; 
				}
				$data['Action'] = "<a data-toggle='modal' onclick='deleteForum($row[forum_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Forum Function==============
		
	//=============Start Forum Answer Function=============
	
	public function forumAnswer(){  
		try{  
			$forumAnswer =isset( $_POST['forumAnswer'] ) ? $_POST['forumAnswer']: '';
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM `forum_detail`  where forum_id='$forumAnswer' order by f_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){    
				$data['f_id'] = $row['f_id'];
				$data['user_id'] = $row['user_id'];
				$data['doctor_id'] = $row['doctor_id'];
				if($data['user_id'] >0){
					$pag1=$con->prepare("SELECT * FROM owner_registration WHERE owner_id = '$data[user_id]'");
					$pag1->execute();
					$answerfetch1 = $pag1->fetch(PDO::FETCH_ASSOC);
					//print_r($sportfetch1);
					$data['full_name'] = $answerfetch1['full_name'];
				}else{
					$pag1=$con->prepare("SELECT * FROM s_registration WHERE id = '$data[doctor_id]'");
					$pag1->execute();
					$answerfetch1 = $pag1->fetch(PDO::FETCH_ASSOC);
					//print_r($sportfetch1);
					$data['full_name'] = $answerfetch1['full_name'];
				}
				$data['point'] = $row['point'];
				$data['answer'] = $row['answer'];
				$page1=$con->prepare("SELECT * FROM forum_answer_like WHERE a_id = '$data[f_id]'");
				$page1->execute();
				$total = $page1->rowCount();
				$data['like_count'] = $total;
				$page11=$con->prepare("SELECT * FROM forum_reply_answer WHERE forum_answer_id = '$data[f_id]'");
				$page11->execute();
				$total1 = $page11->rowCount();
				$data['like_count'] ="<span style='padding-left: 14px; padding-right: 14px; margin-left: 10%;' class='btn btn-xs green'> $total</span>"; 
				if($total1 >0){    
					$data['View_Answer'] = "<a data-toggle='modal' href='#viewcomment' onclick='forumreply($row[f_id])' title='View Comments' style='padding-left: 14px; padding-right: 14px;    margin-left: 10%;' class='btn btn-xs blue'> $total1 </a>"; 
				}else{ 
					$data['View_Answer'] = "<a style='padding-left: 14px; padding-right: 14px;    margin-left: 10%;' title='No Comments' class='btn btn-xs red'>0</a></button>"; 
				}
				$data['Action'] = "<a data-toggle='modal' onclick='deleteAnswer($row[f_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Forum Answer Function==============
	//=============Start Forum Answer Function=============
	
	public function forumreply(){  
		try{  
			$forumreply =isset( $_POST['forumreply'] ) ? $_POST['forumreply']: '';
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM forum_reply_answer  where forum_answer_id='$forumreply' order by id DESC");
			$page->execute();
			$replyfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($replyfetch as $row){    
				$data['id'] = $row['id'];
				$data['user_id'] = $row['user_id'];
				$data['doctor_id'] = $row['doctor_id'];
				if($data['user_id'] >0){
					$pag1=$con->prepare("SELECT * FROM owner_registration WHERE owner_id = '$data[user_id]'");
					$pag1->execute();
					$answerfetch1 = $pag1->fetch(PDO::FETCH_ASSOC);
					//print_r($sportfetch1);
					$data['full_name'] = $answerfetch1['full_name'];
				}else{
					$pag1=$con->prepare("SELECT * FROM s_registration WHERE id = '$data[doctor_id]'");
					$pag1->execute();
					$answerfetch1 = $pag1->fetch(PDO::FETCH_ASSOC);
					//print_r($sportfetch1);
					$data['full_name'] = $answerfetch1['full_name'];
				}
				$data['reply_text'] = $row['reply_text'];
				$page1=$con->prepare("SELECT * FROM forum_answer_like WHERE a_id = '$data[reply_text]'");
				$page1->execute();
				$total = $page1->rowCount();
				$data['like_count'] = $total;
				$data['like_count'] ="<span style='padding-left: 14px; padding-right: 14px; margin-left: 10%;' class='btn btn-xs green'> $total</span>"; 
				$data['Action'] = "<a data-toggle='modal' onclick='deleteComment($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Forum Answer Function==============
	
	//===========Delete Forum Function==============
	
		public function deleteForum(){   
			try { 
				$con=$this->getConnection();
				$deleteForum =isset( $_POST['deleteForum'] ) ? $_POST['deleteForum']: '';
				$view=$con->prepare("DELETE from forum WHERE forum_id='$deleteForum'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Forum no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End Forum Function==============
	
	//===========Delete Forum Function==============
	
		public function deleteComment(){   
			try { 
				$con=$this->getConnection();
				$deleteComment =isset( $_POST['deleteComment'] ) ? $_POST['deleteComment']: '';
				$view=$con->prepare("DELETE from forum_reply_answer WHERE id='$deleteComment'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Forum no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End Forum Function==============
	
	//===========Delete Forum Function==============
	
		public function deleteAnswer(){   
			try { 
				$con=$this->getConnection();
				$deleteAnswer =isset( $_POST['deleteAnswer'] ) ? $_POST['deleteAnswer']: '';
				$view=$con->prepare("DELETE from forum_detail WHERE f_id='$deleteAnswer'");
				$view->execute();
				if($view == true){ 
					$data='1';
				}else{
					$data='Forum no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End Forum Function==============
	//============================================================================================================
	// ======================================//END FORUM FUNCTION//===========================================
	//============================================================================================================
	
	//============================================================================================================
	// ==================================//START VETERINARIAN FUNCTION//=========================================
	//============================================================================================================
		
		//=============GET VETERINARIAN Function=============
	
	
		public function get_vaterinarian(){  
			try{  
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM `veterinarian` where 1=1 ");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){    
					$data['vet_id'] = $row['vet_id'];
					$data['name'] = $row['name'];
					$data['banner'] = '<img src="http://52.220.254.228/zoeyapp/admin/backend/web/img/'.$row['banner'].'" height="130px;" width="120px;">';
					$data['contact_number'] = $row['contact_number'];
					$data['hospital_name'] = $row['hospital_name'];
					$data['consultation_price'] = $row['consultation_price'];
					$data['address'] = $row['address1'].' '.$row['address2'];
					$data['Action'] = "<a data-toggle='modal' onclick='ViewForum($row[vet_id])' title='View'  class='btn btn-icon-only red'> <i class='fa fa-eye'></i></a><a data-toggle='modal' onclick='deleteForum($row[vet_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
					$getdata[]=$data;
				}
				$data = $getdata;
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){
				print $e->getMessage();
			}
		}
		
		//===========End Forum Function==============
		
	//============================================================================================================
	// ==================================//END VETERINARIAN FUNCTION//=======================================
	//============================================================================================================
	
	//============================================================================================================
	// ==================================//START STORE FUNCTION//=========================================
	//============================================================================================================
		
		//=============GET STORE Function=============
	
	
		public function get_store(){     
			try{  
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM `stores` where 1=1 ");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){    
					$data['store_id'] = $row['store_id'];
					$data['name'] = $row['name'];
					$data['banner'] = '<img src="http://52.220.254.228/zoeyapp/admin/backend/web/img/'.$row['banner'].'" height="130px;" width="120px;">';
					$data['contact_number'] = $row['contact_number'];
					$data['email_id'] = $row['email_id'];
					if($row['store_type'] ==2){
						$data['store_type'] = 'Retail Store';
					}else{
						$data['store_type'] = 'Online Store';
					}
					$data['address'] = $row['address1'].' '.$row['address2'];
					$data['Action'] = "<a data-toggle='modal' onclick='ViewForum($row[store_id])' title='View'  class='btn btn-icon-only red'> <i class='fa fa-eye'></i></a><a data-toggle='modal' onclick='deleteForum($row[store_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
					$getdata[]=$data;
				}
				$data = $getdata;
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){
				print $e->getMessage();
			}
		}
		
		//===========End STORE Function==============
		
	//============================================================================================================
	// ==================================//END STORE FUNCTION//=======================================
	//============================================================================================================
	
	//============================================================================================================
	// ======================================//START PET SIZE FUNCTION//==========================================
	//============================================================================================================
	
	
	
	
	//=============GET size Function=============
	
	
	public function get_size(){   
		try {
			$con=$this->getConnection();
			$page=$con->prepare("select * from services_category where status_id = '1'");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['s_category_id'] = $row['s_category_id'];
				$data['c_name'] = $row['name'];
				$data['Edit'] = "<a data-toggle='modal' href='#updatecity' onclick='Edit($row[s_category_id])' title='Edit'  class='btn btn-icon-only red'> <i class='fa fa-edit'></i></a>";
				$data['Delete'] = "<a data-toggle='modal' onclick='deletesize($row[s_category_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	//=============End GET size Function=============
	
	
	
	//============= ADD size Function ===============
	public function add_Size(){ 
		try{ 
			$con=$this->getConnection();
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			if($add=='add')
			{
				if(strlen(preg_replace('/\s+/u','',$name)) == '')
				{ 
					$data['error'] = "Please fill size name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$sql = "SELECT * FROM services_category WHERE name=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$cityfetch = $stmt->fetch();
				if($cityfetch != null){ 
					$data['error'] = "Pet size name already exist!"; 
				}else{ 
					$exq="INSERT INTO services_category set name='$name'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0)
					{
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
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
	
	//===========End Add size Function==============
	
	
	//===========Veiw size list Function============
	
	
	public function veiw_Size(){   
		try {
			$con=$this->getConnection();
			$editSize =isset( $_POST['editSize'] ) ? $_POST['editSize']: '';
			$view=$con->prepare("SELECT * FROM services_category WHERE s_category_id='$editSize'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	
	//===========End View size Function=================
	
	
	//============= Update size Function ===============
	
	
	public function update_Size(){ 
		try{ 
			$con=$this->getConnection();
			$Size_id =isset( $_POST['Size_id'] ) ? $_POST['Size_id']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			//$state_id =isset( $_POST['state_id']) ? $_POST['state_id']: '';
			if($Size_id>0)
			{
				if($name == '')
				{ 
					$data['error'] = "Please fill Size name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$exp="update  services_category set name='$name' where s_category_id='$Size_id'";
				$stmt = $con->prepare($exp);
				$stmt->execute(); 
				if($stmt)
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
	
	
	//===========End Update size Function==============
	
	
	
	//===========End Delete size Function==============
	
	
	public function deleteSize(){   
		try {
			$con=$this->getConnection();
			$deleteSize =isset( $_POST['deleteSize'] ) ? $_POST['deleteSize']: '';
			$view=$con->prepare("DELETE from services_category WHERE s_category_id='$deleteSize'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='city no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Delete size Function==============
	
	
	//============================================================================================================
	// ======================================//END PET SIZE FUNCTION//============================================
	//============================================================================================================
	
	//============================================================================================================
	// ====================================//START SERVICE LIST FUNCTION//=======================================
	//============================================================================================================
	//=============GET grooming Function=============
	
	
	public function get_grooming(){   
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT s.*,pc.category_name FROM `services` as s left join pet_category as pc on pc.category_id=s.category_id order by s.service_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['service_id'] = $row['service_id'];
				$category_id = $row['pet_id'];
				$page1=$con->prepare("SELECT * FROM pet_detail where petdetails_id ='$category_id'");
				$page1->execute();
				$sportfetch1 = $page1->fetch();
				$breed = $sportfetch1['breed_id'];
				$page2=$con->prepare("SELECT * FROM pet_breed where breed_id='$breed'");
				$page2->execute();
				$sportfetch2=$page2->fetch();
				$data['breed_name'] = $sportfetch2['breed_name'];
				$data['order_no'] = $row['order_id'];
				$data['category_name'] = $row['category_name'];
				$data['user_name'] = $row['user_name'];
				$data['phone_number'] = $row['phone_number'];
				$data['pet_name'] = $row['pet_name'];
				$data['age'] = $row['age'];
				$data['services'] = $row['services'];
				if($row['type'] ==1){ 
					$data['size'] = 'Small';
				}else if($row['type'] ==2){
					$data['size'] = 'Medium';
				}else if($row['type'] ==3){
					$data['size'] = 'Large';
				}
				$data['rating'] = $row['rating'];
				$data['ap_date'] = date("d-M-Y", strtotime($row['ap_date']));
				$data['created_date'] = date("d-M-Y H:i:s", strtotime($row['created_date']));
				$assigned_name = $row['assigned_name'];
				if( $row['accept']==0){   
				$data['Assigned'] = "<a data-toggle='modal' href='#acceptorder' onclick='Accept($row[service_id])' title='Accept Order'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #078467;'>Accept</a>";
				}else if($row['accept']==1 && $row['status']==1){ 
					$data['Assigned'] = "<a title='Cacel by User'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;  padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #218a23;'>Complete</a>";
				}else if($row['accept']==2){   
					$data['Assigned'] = "<a title='Cacel by User'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #e7174a;'>C B Admin</a>";
				}else if($row['accept']==3){  
					$data['Assigned'] = "<a title='Cacel by User'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #c35b75;'>C B User</a>";
				}else if($row['accept']==1 && $row['assigned_name'] ==''){  
					$data['Assigned'] = "<a data-toggle='modal' href='#assignorder' onclick='Assign($row[service_id])' title='Order Assign'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #e7b117;'>Pending</a>";
				}else if($row['accept']==1 && $row['assigned_name'] !=''){  
					$data['Assigned'] = "<a onclick='Complete($row[service_id])' title='Order Complete'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;  padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #067698;'>$assigned_name</a>";
				}
				if($row['status']==0 && $row['accept']==1){
					$data['Order_Status'] = "<a data-toggle='modal' onclick='Complete($row[service_id])' title='Order Complete'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #e7b117;'>Pending</a>";
				}else if( $row['status']==0 && $row['accept']==0){
					$data['Order_Status'] = "<a data-toggle='modal' onclick='Reject($row[service_id])' title='Reject Order'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #b50732;'>Reject</a>";
				}else if( $row['accept']==2){
					$data['Order_Status'] = "<a data-toggle='modal' onclick='active($row[service_id])' title='Inactive'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #e7174a;'>C B Admin</a>";
				}else if( $row['accept']==3){
					$data['Order_Status'] = "<a data-toggle='modal' onclick='active($row[service_id])' title='Inactive'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #c35b75;'>C B User</a>";
				}else if($row['accept']==1 && $row['status']==1){
					$data['Order_Status'] = "<a data-toggle='modal' onclick='active($row[service_id])' title='Inactive'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #218a23;'>Complete</a>";
				}
				$data['Action'] = "<a data-toggle='modal' href='#draggable3' onclick='viewdata($row[service_id])' title='View'> <i class='fa fa-eye' style='background-color:#d6064a;color: white;padding: 1px;'></i></a> &nbsp;<a data-toggle='modal' href='#draggable4' onclick='Invoicedata($row[service_id])' title='Invoice'> <i class='fa fa fa-info'style='background-color:#1a08af;color: white;padding: 1px;'></i></a>&nbsp;<a onclick='Reject($row[service_id])' title='Order Cencel'> <i class='fa fa-times-circle-o' style='background-color:#6d086f;color: white;padding: 1px;'></i></a>&nbsp;<a data-toggle='modal' href='#updateGrooming' onclick='Edit($row[service_id])' title='Edit Order'> <i class='fa fa-pencil' style='background-color:#6d086f;color: white;'></i></a>";
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
	
	//=============End GET grooming Function=============
	
	//===========Start Add order Function==============
	
	public function updategroomingorder(){  
		try{ 
			$con=$this->getConnection(); 
			
			$order_id =isset( $_POST['order_id'] ) ? $_POST['order_id']: '';
			$user_name =isset( $_POST['user_name']) ? $_POST['user_name']: '';
			$email =isset( $_POST['email']) ? $_POST['email']: '';
			$contact =isset( $_POST['contact']) ? $_POST['contact']: '';
			$address =isset( $_POST['address']) ? $_POST['address']: '';
			$category =isset( $_POST['category']) ? $_POST['category']: '';
			$pet_name =isset( $_POST['pet_name']) ? $_POST['pet_name']: '';
			$size =isset( $_POST['size']) ? $_POST['size']: '';
			$ap_date =isset( $_POST['ap_date']) ? $_POST['ap_date']: '';
			$price =isset( $_POST['price']) ? $_POST['price']: '';
			date_default_timezone_set('Asia/Kolkata');
			$mydate= date("Y-m-d H:i:s");
				$sql = "SELECT * FROM owner_registration WHERE contact_number=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$contact"));
				$cityfetch = $stmt->fetch();
				if($cityfetch != null){  
					$lastId = $cityfetch['owner_id']; 
				}else{   
					$exq="INSERT INTO owner_registration set full_name='$user_name',email_id='$email',contact_number='$contact',address='$address',status_id='1'";
					$stmt11 = $con->prepare($exq);
					$stmt11->execute(); 
					$lastId = $con->lastInsertId();
					$exq1="INSERT INTO login set user_name='$email',contact_no='$contact',owner_id='$lastId',status_id='1'";
					$stmt1 = $con->prepare($exq1);
					$stmt1->execute();
				}
				if($lastId >0){ 
					$service =$_REQUEST['service'];
					$service2= implode(',',$service);
					$order = substr(md5(microtime()),rand(0,26),5);
					$exq2="UPDATE services set category_id='$category',pet_name='$pet_name',user_id='$lastId',user_name='$user_name',phone_number='$contact',user_email='$email',order_id='$order',address='$address',services='$service2',type='$size',price='$price',ap_date='$ap_date',created_date='$mydate' where service_id='$order_id'";
					$stmt2 = $con->prepare($exq2);
					$stmt2->execute();
					$data= '1';
					header('Content-type: application/json');
					echo  json_encode($data,JSON_PRETTY_PRINT);
					return true;
					exit();
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
	
	//===========End Add order Function==============
	//===========Start Add order Function==============
	
	public function addgroomingorder(){  
		try{ 
			$con=$this->getConnection(); 
			//print_r($_REQUEST); die;
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$user_name =isset( $_POST['user_name']) ? $_POST['user_name']: '';
			$email =isset( $_POST['email']) ? $_POST['email']: '';
			$contact =isset( $_POST['contact']) ? $_POST['contact']: '';
			$address =isset( $_POST['address']) ? $_POST['address']: '';
			$category =isset( $_POST['category']) ? $_POST['category']: '';
			$pet_name =isset( $_POST['pet_name']) ? $_POST['pet_name']: '';
			$size =isset( $_POST['size']) ? $_POST['size']: '';
			$ap_date =isset( $_POST['ap_date']) ? $_POST['ap_date']: '';
			$price =isset( $_POST['price']) ? $_POST['price']: '';
			date_default_timezone_set('Asia/Kolkata');
			$mydate= date("Y-m-d H:i:s");
			if($add=='add')
			{ 
				if($user_name == ''){ 
					$data['error'] = "Please fill user name";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
					exit();
				}
				if($email == ''){  
					$data['error'] = "Please fill Email ID";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
					exit();
				}
				if($contact == ''){ 
					$data['error'] = "Please fill Contact";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
					exit();
				}
				if($address == ''){ 
					$data['error'] = "Please fill Address";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
					exit();
				}
				if($category == ''){ 
					$data['error'] = "Please select Category";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
					exit();
				}
				if($size == ''){ 
					$data['error'] = "Please select Size";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
					exit();
				}
				if($pet_name == ''){ 
					$data['error'] = "Please select Pet Name";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
					exit();
				}
				if($price == ''){ 
					$data['error'] = "Please fill price";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
					exit();
				}
				if($ap_date == ''){ 
					$data['error'] = "Please select Order Date!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
					exit();
				}
				$service1 = count(isset( $_POST['service']) ? $_POST['service']: '');
				if($_POST['service'] == ''){  
					$data['error'] = "Please select services!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
					exit();
				}
				if($service1 >0 || $service1 <6 ){ 
					$sql = "SELECT * FROM owner_registration WHERE contact_number=?";
					$stmt = $con->prepare($sql);
					$stmt->execute(array("$contact"));
					$cityfetch = $stmt->fetch();
					if($cityfetch != null){  
						$lastId = $cityfetch['owner_id']; 
					}else{   
						$exq="INSERT INTO owner_registration set full_name='$user_name',email_id='$email',contact_number='$contact',address='$address',status_id='1'";
						$stmt11 = $con->prepare($exq);
						$stmt11->execute(); 
						$lastId = $con->lastInsertId();
						$exq1="INSERT INTO login set user_name='$email',contact_no='$contact',owner_id='$lastId',status_id='1'";
						$stmt1 = $con->prepare($exq1);
						$stmt1->execute();
					}
					if($lastId >0){ 
						$service =$_REQUEST['service'];
						$service2= implode(',',$service);
						$order = substr(md5(microtime()),rand(0,26),5);
						$exq2="INSERT INTO services set category_id='$category',pet_name='$pet_name',user_id='$lastId',user_name='$user_name',phone_number='$contact',user_email='$email',order_id='$order',address='$address',services='$service2',type='$size',price='$price',ap_date='$ap_date',created_date='$mydate'";
						$stmt2 = $con->prepare($exq2);
						$stmt2->execute();
						$data= '1';
						header('Content-type: application/json');
						echo  json_encode($data,JSON_PRETTY_PRINT);
						return true;
						exit();
					}else{  
						$data['error']= "Something is missing. Please try again!";
						header('Content-type: application/json');
						echo  json_encode($data,JSON_PRETTY_PRINT);
						return true;
					}
					
				}else{
					$data= "Please select service!";
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
	
	//===========End Add order Function==============
	
	//=============GET grooming Function=============
	
	
	public function get_grooming1(){   
		try {
			$con=$this->getConnection();
			$dadat_id =isset( $_POST['dadat_id'] ) ? $_POST['dadat_id']: '';
			$page=$con->prepare("SELECT user_name,type,ap_date FROM `services` where service_id='$dadat_id'");
			$page->execute();
			$data = $page->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	
	//=============End GET grooming Function=============
	
	//=============GET grooming Function=============
	
	
	public function edit_grooming(){ 
		try {
			$con=$this->getConnection();
			session_start();
			unset($_SESSION['service']);
			unset($_SESSION['order_id']);
			$order_id =isset( $_POST['order_id'] ) ? $_POST['order_id']: '';
			$page=$con->prepare("SELECT * FROM `services` where service_id='$order_id'");
			$page->execute();
			$data = $page->fetch(PDO::FETCH_ASSOC);
			$_SESSION['order_id']=$order_id;
			$_SESSION['service']=$data['services'];
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//=============End GET grooming Function=============
	//=============GET grooming Function=============
	
	
	public function veiwdata_grooming(){   
		try {
			$con=$this->getConnection();
			$order_id =isset( $_POST['order_id'] ) ? $_POST['order_id']: '';
			$page=$con->prepare("SELECT s.*,pc.category_name FROM `services` as s left join pet_category as pc on pc.category_id=s.category_id where s.service_id='$order_id'");
			$page->execute();
			$data = $page->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	
	//=============End GET grooming Function=============
	
	//=============GET grooming Function=============
	
	
	public function veiwdata_training(){   
		try{ 
			$con=$this->getConnection();
			$order_id =isset( $_POST['order_id'] ) ? $_POST['order_id']: '';
			$page=$con->prepare("SELECT s.*,pc.category_name,tt.training_type FROM `training` as s left join pet_category as pc on pc.category_id=s.category_id left join training_type as tt on tt.training_id=s.type where s.training_id='$order_id'");
			$page->execute();
			$data = $page->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//=============End GET grooming Function=============
	//===========Start Accept grooming Function==============
	
	
	public function AcceptGroomingrequest(){   
		try {
			$con=$this->getConnection();
			$start_date1 =isset( $_REQUEST['start_date'] ) ? $_REQUEST['start_date']: '';
			$licence =isset( $_REQUEST['licence'] ) ? $_REQUEST['licence']: '';
			 if($start_date1 !=''){
				$view=$con->prepare("Update services SET accept ='1' ,ap_date='$start_date1' WHERE service_id='$licence'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Service no Update in record. Please try again!';
				}
			}else{
				$data='Something is missing';
			} 
			
			
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Accept grooming Function==============
	
	//===========Start Accept grooming Function==============
	
	
	public function AssignVendor(){   
		try {
			$con=$this->getConnection();
			$assign =isset( $_REQUEST['assign'] ) ? $_REQUEST['assign']: '';
			$assignid =isset( $_REQUEST['assignid'] ) ? $_REQUEST['assignid']: '';
			 if($assign !=''){
				$view=$con->prepare("Update services SET assigned_name ='$assign' WHERE service_id='$assignid'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Service no Update in record. Please try again!';
				}
			}else{
				$data='Something is missing';
			} 
			
			
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Accept grooming Function==============
	
	//===========Start Accept grooming Function==============
	
	
	public function RejectGroomingrequest(){   
		try {
			$con=$this->getConnection();
			$RejectGroomingrequest =isset( $_POST['RejectGroomingrequest'] ) ? $_POST['RejectGroomingrequest']: '';
			$view=$con->prepare("Update services SET accept ='2' WHERE service_id='$RejectGroomingrequest'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Service no Update in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Accept grooming Function==============
	
	//===========Start Accept grooming Function==============
	
	
	public function CompleteGroomingrequest(){   
		try {
			$con=$this->getConnection();
			$CompleteGroomingrequest =isset( $_POST['CompleteGroomingrequest'] ) ? $_POST['CompleteGroomingrequest']: '';
			$view=$con->prepare("Update services SET accept ='1',status=1 WHERE service_id='$CompleteGroomingrequest'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Service no Update in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Accept grooming Function==============
	
	//=============GET service Function=============
	
	
	public function get_servicelist(){   
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT sl.`s_list_id`,pc.category_name,sc.name,sl.`service_name`,sl.`price`,sl.`status` FROM `service_list` as sl left join pet_category as pc on pc.`category_id` =sl.`category_id` left join services_category as sc on sc.s_category_id=sl.`service_category_id` order by sl.s_list_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['s_list_id'] = $row['s_list_id'];
				$data['category_name'] = $row['category_name'];
				$data['name'] = $row['name'];
				$data['service_name'] = $row['service_name'];
				$data['price'] = $row['price'];
				if( $row['status']==1){   
				$data['Status'] = "<a data-toggle='modal' onclick='active($row[s_list_id])' title='Inactive'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 65px;padding-left: 10px; color: #FFFFFF;background-color: #26a69a;'>Active</a>";
				}else{ 
					$data['Status'] = "<a data-toggle='modal' onclick='active($row[s_list_id])' title='Active'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 65px;padding-left: 10px; color: #FFFFFF;background-color: #e7174a;'>DeActive</a>";
				}
				$data['Action'] = "<a data-toggle='modal' onclick='deleteservice($row[s_list_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	//=============End GET service Function=============
	
	
	
	//============= ADD service Function ===============
	public function add_service(){   
		try{ 
			$con=$this->getConnection();
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$category_id =isset( $_POST['category_id']) ? $_POST['category_id']: '';
			$pet_size =isset( $_POST['pet_size']) ? $_POST['pet_size']: '';
			$service =$_REQUEST['service'];
			$service1 = count($service);
			$price =isset( $_POST['price']) ? $_POST['price']: '';
			if($add=='add'){ 
				if($category_id == ''){   
					$data['error'] = "Please select category name";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}
				if($pet_size == ''){    
					$data['error'] = "Please select size name";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}
				if($service1 == ''){  
					$data['error'] = "Please select service name";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}
				if($price == ''){  
					$data['error'] = "Please fill price";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}
				if($service1 >0 ){ 
					foreach($service as $rowdata){  
						$sql = "SELECT * FROM service_list WHERE category_id=? and service_category_id=? and service_name=?";
						$stmt = $con->prepare($sql);
						$stmt->execute(array("$category_id","$pet_size","$rowdata"));
						$cityfetch = $stmt->fetch();
						if($cityfetch != null){  
							$data['error'] = "Service name already exist!"; 
							header('Content-type: application/json');
							echo  json_encode($data,JSON_PRETTY_PRINT);
							return true;
						}else{ 
							$exq="INSERT INTO service_list set category_id='$category_id',service_category_id='$pet_size',service_name='$rowdata',price='$price'";
							$stmt = $con->prepare($exq);
							$stmt->execute(); 
							$lastId = $con->lastInsertId();
						}
					}
					if($lastId >0){ 
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
	
	//===========End Add service Function==============
	//============= Edit service Function ===============
	public function active_service(){ 
		try{ 
			$con=$this->getConnection();
			$active_service =isset( $_POST['active_service'] ) ? $_POST['active_service']: '';
			
			if($active_service>0)
			{
				$view=$con->prepare("SELECT * FROM service_list WHERE  s_list_id='$active_service'");
				$view->execute();
				$dat1 = $view->fetch(PDO::FETCH_ASSOC);
				if($dat1['status'] ==1){
					$exq="UPDATE service_list set status='0' where s_list_id='$active_service'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
				}else{
					$exq="UPDATE service_list set status='1' where s_list_id='$active_service'";
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
	//===========End Edit service Function==============
	//===========End Delete service Function==============
	
	
	public function deleteservice(){   
		try {
			$con=$this->getConnection();
			$deleteservice =isset( $_POST['deleteservice'] ) ? $_POST['deleteservice']: '';
			$view=$con->prepare("DELETE from service_list WHERE s_list_id='$deleteservice'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Service no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Delete service Function==============
	
	
	//============================================================================================================
	// ===================================//END SERVICE LIST FUNCTION//=========================================
	//============================================================================================================
	
	//============================================================================================================
	// ===================================//START PET SERVICE FUNCTION//========================================
	//============================================================================================================
	
	
	//=============GET Service Function=============
	
	
	public function getService(){   
		try { 
			$con=$this->getConnection();
			$page=$con->prepare("select * from service_checkbox where 1=1");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['id'] = $row['id'];
				$data['service_name'] = $row['service_name'];
				$data['icon'] = '<img src="http://52.220.254.228/zoeyapp/admin/backend/web/img/icon/5/'.$row['icon'].'" height="80px;" width="80px;">';
				$data['c_icon'] = '<img src="http://52.220.254.228/zoeyapp/admin/backend/web/img/icon/5/'.$row['c_icon'].'" height="80px;" width="80px;">';
				$data['Edit'] = "<a data-toggle='modal' href='#updatecity' onclick='Edit($row[id])' title='Edit'  class='btn btn-icon-only red'> <i class='fa fa-edit'></i></a>";
				$data['Delete'] = "<a data-toggle='modal' onclick='deleteService1($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	//=============End GET Service Function=============
	
	//===========Veiw Service list Function============
	
	
	public function veiwService(){   
		try {
			$con=$this->getConnection();
			$editService =isset( $_POST['editService'] ) ? $_POST['editService']: '';
			$view=$con->prepare("SELECT * FROM service_checkbox WHERE id='$editService'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	
	//===========End View Service Function=================
	
	
	//============= Update Service Function ===============
	
	
	public function updateService(){ 
		try{ 
			$con=$this->getConnection();
			$Service_id =isset( $_POST['Service_id'] ) ? $_POST['Service_id']: '';
			$Service_name =isset( $_POST['Service_name']) ? $_POST['Service_name']: '';
			//$state_id =isset( $_POST['state_id']) ? $_POST['state_id']: '';
			if($Service_id>0)
			{
				if($Service_name == '')
				{ 
					$data['error'] = "Please fill Service name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$exp="update  service_checkbox set Service_name='$Service_name' where id='$Service_id'";
				$stmt = $con->prepare($exp);
				$stmt->execute(); 
				if($stmt)
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
	
	
	//===========End Update Service Function==============
	
	
	
	//===========End Delete Service Function==============
	
	
	public function deleteService1(){   
		try { 
			$con=$this->getConnection();
			$deleteService1=isset( $_POST['deleteService1'] ) ? $_POST['deleteService1']: '';
			$view=$con->prepare("DELETE from service_checkbox WHERE id='$deleteService1'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='service no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Delete Service Function==============
	
	
	//============================================================================================================
	// ====================================//END PET SERVICE FUNCTION//==========================================
	//============================================================================================================
	
	
	//============================================================================================================
	// ==================================//START PET VENDOR FUNCTION//==========================================
	//============================================================================================================
	
	
	
	
	//=============GET VENDOR Function=============
	
	
	public function get_Vendor(){   
		try { 
			$con=$this->getConnection();
			$page=$con->prepare("select * from petspa_list where 1=1");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){  
				$data['list_id'] = $row['list_id'];
				$data['name'] = $row['name'];
				$data['email'] = $row['email'];
				$data['contact'] = $row['contact'];
				$data['address'] = $row['address'];
				$data['services'] = $row['services'];
				$data['rating'] = $row['rating'];
				if( $row['status']==1){   
				$data['Status'] = "<a data-toggle='modal' onclick='active($row[list_id])' title='Inactive'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 65px;padding-left: 10px; color: #FFFFFF;background-color: #26a69a;'>Active</a>";
				}else{ 
					$data['Status'] = "<a data-toggle='modal' onclick='active($row[list_id])' title='Active'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 65px;padding-left: 10px; color: #FFFFFF;background-color: #e7174a;'>DeActive</a>";
				}
				$data['Assign'] = "<a data-toggle='modal' href='#updatevendor' onclick='Assign($row[list_id])' title='Assign Orders'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 100px;padding-left: 10px; color: #FFFFFF;background-color: #26a69a;'>Assign Orders</a>";
				$data['Action'] = "<a data-toggle='modal' href='#vendorupdate' onclick='Edit($row[list_id])' title='Edit'  class='btn btn-icon-only red'> <i class='fa fa-edit'></i></a><a data-toggle='modal' onclick='deleteVendor($row[list_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	//=============End GET VENDOR Function=============
	
	//=============GET Assign Function=============
	
	
	public function assignOrders(){ 
		try { 
			$con=$this->getConnection();
			$vendorid =isset( $_POST['vendorid'] ) ? $_POST['vendorid']: '';
			$page=$con->prepare("select * from services where assigned_name=(select name from petspa_list where list_id=:vendorid)");
			$page->execute(compact('vendorid'));
			$sportfetch = $page->fetchAll(\PDO::FETCH_ASSOC);
			foreach($sportfetch as $row){  
				$data['service_id'] = $row['service_id'];
				$data['order_id'] = $row['order_id'];
				$data['user_name'] = $row['user_name'];
				$data['phone_number'] = $row['phone_number'];
				$data['pet_name'] = $row['pet_name'];
				$data['type'] = $row['type'];
				$data['services'] = $row['services'];
				$data['price'] = $row['price'];
				$data['rating'] = $row['rating'];
				$data['ap_date'] = $row['ap_date'];
				$data['created_date'] = $row['created_date'];
				if($row['accept']==0 && $row['status']==0){
					$data['Status'] = "<a data-toggle='modal' class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 110px;padding-left: 10px; color: #FFFFFF;background-color: #fcbe53;'>Orders Placed</a>";
				}else if($row['accept']==1 && $row['status']==0){
					$data['Status'] = "<a data-toggle='modal' class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 110px;padding-left: 10px; color: #FFFFFF;background-color: #fa95e1;'>Orders Pending</a>";
				}else if($row['accept']==2 && $row['status']==0){
					$data['Status'] = "<a data-toggle='modal'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 110px;padding-left: 10px; color: #FFFFFF;background-color: ##cb5a5e;'>Orders Cancel</a>";
				}else if($row['accept']==3 && $row['status']==1){
					$data['Status'] = "<a data-toggle='modal'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 110px;padding-left: 10px; color: #FFFFFF;background-color: ##cb5a5e;'>Orders Cancel</a>";
				}else if($row['accept']==1&& $row['status']==1){
					$data['Status'] = "<a data-toggle='modal' class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 120px;padding-left: 10px; color: #FFFFFF;background-color: #26a69a;'>Orders Complete</a>";
				}
				
				
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			exit();
		}catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	
	//=============End GET Assign Function=============
	
	
	//============= Edit service Function ===============
	public function active_Vendor(){ 
		try{ 
			$con=$this->getConnection();
			$active_Vendor =isset( $_POST['active_Vendor'] ) ? $_POST['active_Vendor']: '';
			
			if($active_Vendor>0)
			{ 
				$view=$con->prepare("SELECT * FROM petspa_list WHERE  list_id='$active_Vendor'");
				$view->execute();
				$dat1 = $view->fetch(PDO::FETCH_ASSOC);
				if($dat1['status'] ==1){
					$exq="UPDATE petspa_list set status='0' where list_id='$active_Vendor'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
				}else{
					$exq="UPDATE petspa_list set status='1' where list_id='$active_Vendor'";
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
	//===========End Edit service Function==============
	
	
	
	//============= ADD VENDOR Function ===============
	public function add_Vendor(){ 
		try{ 
			$con=$this->getConnection();
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			$email =isset( $_POST['email']) ? $_POST['email']: '';
			$contact =isset( $_POST['contact']) ? $_POST['contact']: '';
			$address =isset( $_POST['address']) ? $_POST['address']: '';
			$service =isset( $_POST['service']) ? $_POST['service']: '';
			$count = count($service);
			$service2 = implode(',',$service);
			if($add=='add')
			{
				if($name == ''){ 
					$data['error'] = "Please fill city name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($email == ''){ 
					$data['error'] = "Please fill email!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($contact == ''){ 
					$data['error'] = "Please fill contact!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($address == ''){ 
					$data['error'] = "Please fill address!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($count ==0){ 
					$data['error'] = "Please select service!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$sql = "SELECT * FROM petspa_list WHERE name=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$cityfetch = $stmt->fetch();
				if($cityfetch != null){ 
					$data['error'] = "Vendor name already exist!"; 
				}else{ 
					$exq="INSERT INTO petspa_list set name='$name',email='$email',contact='$contact',address='$address',services='$service2',rating='0',created_date=now()";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0)
					{
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
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
	
	//===========End Add VENDOR Function==============
	
	
	//===========Veiw VENDOR list Function============
	
	
	public function veiw_Vendor(){   
		try {
			$con=$this->getConnection();
			$editVendor =isset( $_POST['editVendor'] ) ? $_POST['editVendor']: '';
			$view=$con->prepare("SELECT * FROM petspa_list WHERE list_id='$editVendor'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			$data['service'] = explode(',',$data['services']);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	
	//===========End View VENDOR Function=================
	
	
	//============= Update VENDOR Function ===============
	
	
	public function update_vendor(){  
		try{ 
			$con=$this->getConnection();
			$Size_id =isset( $_POST['Size_id'] ) ? $_POST['Size_id']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			//$state_id =isset( $_POST['state_id']) ? $_POST['state_id']: '';
			if($Size_id>0)
			{
				if($name == '')
				{ 
					$data['error'] = "Please fill Size name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$exp="update  services_category set name='$name' where s_category_id='$Size_id'";
				$stmt = $con->prepare($exp);
				$stmt->execute(); 
				if($stmt)
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
	
	
	//===========End Update VENDOR Function==============
	
	
	
	//===========End Delete VENDOR Function==============
	
	
	public function deleteVendor(){   
		try {
			$con=$this->getConnection();
			$deleteVendor =isset( $_POST['deleteVendor'] ) ? $_POST['deleteVendor']: '';
			$view=$con->prepare("DELETE from petspa_list WHERE list_id='$deleteVendor'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Vebdor no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Delete VENDOR Function==============
	
	
	//============================================================================================================
	// ====================================//END PET VENDOR FUNCTION//==========================================
	//============================================================================================================
	
	//=============GET service Function=============
	
	
	public function get_servicelist1(){    
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM service_checkbox");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['id'] = $row['id'];
				$data['service_name'] = $row['service_name'];
				$data['icon'] = $row['icon'];
				$data['c_icon'] = $row['c_icon'];
				$data['Action'] = "<a data-toggle='modal' onclick='deleteservice($row[id])' title='Delete'  class='btn btn-icon-only purple'><i class='fa fa-trash'></i></a>";
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
	public function get_servicelist2(){    
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM service_checkbox");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data = $row['service_name'];
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
	//=============End GET service Function=============
	
	//============================================================================================================
	// ==================================//START PEDIA REQUEST FUNCTION//========================================
	//============================================================================================================
	//=============GET PediaRequest Function=============

		public function get_pediaRequest(){   
			try { 
				$con=$this->getConnection();
				$page=$con->prepare("SELECT * FROM pedia_req order by pedia_req_id DESC");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$total = $page->rowCount();
				foreach($sportfetch as $row){  
					$data['id'] = $row['pedia_req_id'];
					$data['user_name'] = $row['user_name'];
					$data['cantact_no'] = $row['cantact_no'];
					$data['breed_name'] = $row['breed_name'];
					$data['created_date'] = date("d-M-Y H:i:s", strtotime($row['created_date']));
					$data['Action'] = "<a data-toggle='modal' onclick='deletepediaRequest($row[pedia_req_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
		
		//=============End GET PediaRequest Function=============
		
		//===========Start Delete PediaRequest Function==============
		
		public function deletepediaRequest(){   
			try { 
				$con=$this->getConnection();
				$deletepediaRequest =isset( $_POST['deletepediaRequest'] ) ? $_POST['deletepediaRequest']: '';
				$view=$con->prepare("DELETE from pedia_req WHERE pedia_req_id='$deletepediaRequest'");
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
		//===========End Delete PediaRequest Function==============
	
	//============================================================================================================
	// ====================================//END PEDIA REQUEST FUNCTION//=========================================
	//============================================================================================================
	
		
	//===========================================================================================================
	// ==================================//START Characteristics FUNCTION//======================================
	//===========================================================================================================
	
	
	
	
	//=============GET Characteristics Function=============
	
	
	public function get_characteristics(){  
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT c.*,pc.category_name FROM characteristics as c left join pet_category as pc on pc.category_id=c.category_id order by c.characteristics_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){  
				$data['characteristics_id'] = $row['characteristics_id'];
				$data['characteristics_name'] = $row['characteristics_name'];
				$data['category_name'] = $row['category_name'];
				if( $row['status_id']==1){   
				$data['status_id'] = "<a data-toggle='modal' onclick='active($row[characteristics_id])' title='Inactive'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 65px;padding-left: 10px; color: #FFFFFF;background-color: #26a69a;'>Active</a>";
				}else{ 
					$data['status_id'] = "<a data-toggle='modal' onclick='active($row[characteristics_id])' title='Active'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 65px;padding-left: 10px; color: #FFFFFF;background-color: #e7174a;'>DeActive</a>";
				}
				$data['Edit'] = "<a data-toggle='modal' href='#updatecharacteristics' onclick='Edit($row[characteristics_id])' title='Edit'  class='btn btn-icon-only red'> <i class='fa fa-edit'></i></a>";
				$data['Delete'] = "<a data-toggle='modal' onclick='deletecharacteristics($row[characteristics_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	//=============End GET Characteristics Function=============
	
	
	
	//============= ADD Characteristics Function ===============
	public function add_characteristics(){ 
		try{ 
			$con=$this->getConnection();
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$category =isset( $_POST['category']) ? $_POST['category']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			if($add=='add')
			{
				if($name == '' && $category=='')
				{ 
					$data['error'] = "Please fill Characteristics!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$sql = "SELECT * FROM characteristics WHERE characteristics_name=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$statefetch = $stmt->fetch();
				if($statefetch != null){ 
					$data['error'] = "state name already exist!"; 
				}else{ 
					$exq="INSERT INTO characteristics set category_id='$category',characteristics_name='$name'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0){   
						$data['status']= '1';
					}else{   
						$data['error']= "Something is missing. Please try again!";
					}
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
	
	//===========End Add Characteristics Function==============
	
	
	//===========Veiw Characteristics list Function============
	
	
	public function veiw_characteristics(){   
		try {
			$con=$this->getConnection();
			$editcharacteristics =isset( $_POST['editcharacteristics'] ) ? $_POST['editcharacteristics']: '';
			$view=$con->prepare("SELECT * FROM characteristics WHERE characteristics_id='$editcharacteristics'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch(PDOException $e){  
			print $e->getMessage();
		}
	}
	
	
	//===========End View Characteristics Function=================
	
	
	//============= Update Characteristics Function ===============
	
	public function update_characteristics(){ 
		try{  
			$con=$this->getConnection();
			$characteristics_id =isset( $_POST['characteristics_id'] ) ? $_POST['characteristics_id']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			if($characteristics_id>0){
				if($name == ''){ 
					$data['error'] = "Please fill Characteristics Name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$exp="update  characteristics set characteristics_name='$name' where characteristics_id='$state_id'";
				$stmt = $con->prepare($exp);
				$stmt->execute(); 
				if($stmt){
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
	
	
	//===========End Update Characteristics Function==============
	
	//================ Edit service Function =====================
	public function active_characteristics(){  
		try{ 
			$con=$this->getConnection();
			$active_characteristics =isset( $_POST['active_characteristics'] ) ? $_POST['active_characteristics']: '';
			if($active_characteristics > 0){  
				$view=$con->prepare("SELECT * FROM characteristics WHERE  characteristics_id='$active_characteristics'");
				$view->execute();
				$dat1 = $view->fetch(PDO::FETCH_ASSOC);
				if($dat1['status_id'] ==1){
					$exq="UPDATE characteristics set status_id='0' where characteristics_id='$active_characteristics'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
				}else{
					$exq="UPDATE characteristics set status_id='1' where characteristics_id='$active_characteristics'";
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
	
	
	//===========End Delete Characteristics Function==============
	
	public function deletecharacteristics(){   
		try {
			$con=$this->getConnection();
			$deletecharacteristics =isset( $_POST['deletecharacteristics'] ) ? $_POST['deletecharacteristics']: '';
			$view=$con->prepare("DELETE from characteristics WHERE characteristics_id='$deletecharacteristics'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='state no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	//===========End Delete Characteristics Function==============
	
	
	//==========================================================================================================
	//==================================//END Characteristics FUNCTION//======================================
	//==========================================================================================================
	
	
	//===========================================================================================================
	// =======================================//START PEDIA FUNCTION//===========================================
	//===========================================================================================================
	
	
	
	
	//=============GET Pedia Function=============
	
	
	public function get_pedia(){   
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT p.*,pc.category_name,pb.breed_name,sc.name FROM `pet_pedia` as p left join pet_category as pc on pc.category_id=p.`category_id` left join pet_breed as pb on pb.breed_id=p.`breed_id` left join services_category as sc on sc.s_category_id=p.`breed_tyle` order by `pedia_id` DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){  
				$data['pedia_id'] = $row['pedia_id'];
				$data['category_name'] = $row['category_name'];
				$data['breed_name'] = $row['breed_name'];
				$data['name'] = $row['name'];
				$data['weight'] = $row['weight'];
				$data['height'] = $row['height'];
				$data['origin'] = $row['origin'];
				$data['life_span'] = $row['life_span'];
				$data['video_url'] = $row['video_url'];
				$data['Action'] = "<a data-toggle='modal' href='#viewpedia' onclick='View($row[pedia_id])' title='View'  class='btn btn-icon-only red'> <i class='fa fa-eye'></i></a> <a data-toggle='modal' onclick='deletepedia($row[pedia_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch(PDOException $e){
			print $e->getMessage();
		}
	}
	
	//=============End GET Pedia Function=============
	//=============GET Pedia Function=============
	
	
	public function veiw_pedia(){   
		try {
			$con=$this->getConnection();
			$viewpedia =isset( $_POST['viewpedia'] ) ? $_POST['viewpedia']: '';
			$page=$con->prepare("SELECT p.*,pc.category_name,pb.breed_name,sc.name FROM `pet_pedia` as p left join pet_category as pc on pc.category_id=p.`category_id` left join pet_breed as pb on pb.breed_id=p.`breed_id` left join services_category as sc on sc.s_category_id=p.`breed_tyle` where p.pedia_id='$viewpedia'");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){  
				$data['pedia_id'] = $row['pedia_id'];
				$data['category_name'] = $row['category_name'];
				$data['breed_name'] = $row['breed_name'];
				$data['name'] = $row['name'];
				$data['weight'] = $row['weight'];
				$data['height'] = $row['height'];
				$data['origin'] = $row['origin'];
				$data['life_span'] = $row['life_span'];
				$video = explode('=',$row['video_url']);
				$data['video_url'] = '<iframe width="820" height="415" src="https://www.youtube.com/embed/'.$video[1].'"> </iframe>';
				$data['created_date'] = $row['created_date'];
				$page1=$con->prepare("SELECT po.*,c.characteristics_name FROM `pedia_option` as po left join characteristics as c on c.characteristics_id=po.`characteristics_id` WHERE c.characteristics_name!='' and po.pedia_id='$data[pedia_id]'");
				$page1->execute();
				$sportfetch1 = $page1->fetchAll();
				$abcd = array();
				foreach($sportfetch1 as $row1){
					$abc['characteristics_name'] = $row1['characteristics_name'];
					$abc['rating'] = $row1['rating'];
					$abcd[] =$abc;
				}
				$data['characteristics'] = $abcd;
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch(PDOException $e){
			print $e->getMessage();
		}
	}
	
	//=============End GET Pedia Function=============
	
	//============= ADD Pedia Function ===============
	public function add_pedia(){ 
		try{ 
			$con=$this->getConnection();
			$check_list =isset( $_REQUEST['check_list'] ) ? $_REQUEST['check_list']: '';
			$formGender =isset( $_REQUEST['formGender'] ) ? $_REQUEST['formGender']: '';
			//$formGender = explode(',',$formGender1);
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$category =isset( $_POST['category']) ? $_POST['category']: '';
			$breed =isset( $_POST['breed']) ? $_POST['breed']: '';
			$size =isset( $_POST['size']) ? $_POST['size']: '';
			$weight =isset( $_POST['weight']) ? $_POST['weight']: '';
			$height =isset( $_POST['height']) ? $_POST['height']: '';
			$origin =isset( $_POST['origin']) ? $_POST['origin']: '';
			$life_span =isset( $_POST['life_span']) ? $_POST['life_span']: '';
			$video_url =isset( $_POST['video_url']) ? $_POST['video_url']: '';
			if($add=='add'){
				if($category == '' || $breed=='' || $size=='' || $weight=='' || $height=='' || $origin=='' || $life_span=='' || $video_url== ''){  
					$data['error'] = "Please fill Pedia!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				} 
				$exq="INSERT INTO pet_pedia set category_id='$category',breed_id='$breed',breed_tyle='$size',weight='$weight',height='$height',origin='$origin',life_span='$life_span',video_url='$video_url'";
				$stmt = $con->prepare($exq);
				$stmt->execute(); 
				$lastId = $con->lastInsertId();
				if($lastId>0){
					foreach($check_list as $key=>$row){
						$exq="INSERT INTO pedia_option set characteristics_id='$row',rating='$formGender[$key]',pedia_id='$lastId'";
						$stmt = $con->prepare($exq);
						$stmt->execute(); 
					}
					
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
	
	//===========End Add Pedia Function==============
	
	
	//===========End Delete Pedia Function==============
	
	public function deletepedia(){   
		try {
			$con=$this->getConnection();
			$deletepedia =isset( $_POST['deletepedia'] ) ? $_POST['deletepedia']: '';
			$view=$con->prepare("DELETE from pet_pedia WHERE pedia_id='$deletepedia'");
			$view->execute();
			if($view == true){ 
				$data='1';
			}else{ 
				$data='pedia no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	public function characteristicsList(){  
		try {
			$con=$this->getConnection();
			$cid =isset( $_POST['id'] ) ? $_POST['id']: '';
			$page=$con->prepare("SELECT * FROM characteristics WHERE category_id='$cid'");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){  
				$data['characteristics_id'] = $row['characteristics_id'];
				$data['characteristics_name'] = $row['characteristics_name'];
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
	//===========End Delete Pedia Function==============
	
	
	//============================================================================================================
	// =================================//START TRAINING TYPE LIST FUNCTION//=====================================
	//============================================================================================================
	//=============GET Training Function=============
	
	
	public function get_Training(){   
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT s.*,pc.category_name,tt.training_type,tt.duration FROM `training` as s left join pet_category as pc on pc.category_id=s.category_id  left join training_type as tt on tt.training_id = s.type order by s.training_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['training_id'] = $row['training_id'];
				$data['order_no'] = $row['order_id'];
				$data['category_name'] = $row['category_name'];
				$data['user_name'] = $row['user_name'];
				$data['phone_number'] = $row['phone_number'];
				$data['breed_name'] = $row['breed_name'];
				$data['training_type'] = $row['training_type'].'('.$row['duration'].')';
				$data['breed_name'] = $row['breed_name'];
				$data['ap_date'] = date("d-M-Y", strtotime($row['ap_date']));
				$data['created_date'] = date("d-M-Y H:i:s", strtotime($row['created_date']));
				$assigned_name = $row['assigned_name'];
				if( $row['accept']==0){   
				$data['Assigned'] = "<a data-toggle='modal' href='#acceptorder' onclick='Accept($row[training_id])' title='Accept Order'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #078467;'>Accept</a>";
				}else if($row['accept']==1 && $row['status']==1){ 
					$data['Assigned'] = "<a title='Cacel by User'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;  padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #218a23;'>Complete</a>";
				}else if($row['accept']==2){   
					$data['Assigned'] = "<a title='Cacel by User'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #e7174a;'>C B Admin</a>";
				}else if($row['accept']==3){  
					$data['Assigned'] = "<a title='Cacel by User'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #c35b75;'>C B User</a>";
				}else if($row['accept']==1 && $row['assigned_name'] ==''){  
					$data['Assigned'] = "<a data-toggle='modal' href='#assignorder' onclick='Assign($row[training_id])' title='Order Assign'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #e7b117;'>Pending</a>";
				}else if($row['accept']==1 && $row['assigned_name'] !=''){  
					$data['Assigned'] = "<a onclick='Complete($row[training_id])' title='Order Complete'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;  padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #067698;'>$assigned_name</a>";
				}
				if($row['status']==0 && $row['accept']==1){
					$data['Order_Status'] = "<a data-toggle='modal' onclick='Complete($row[training_id])' title='Order Complete'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #e7b117;'>Pending</a>";
				}else if( $row['status']==0 && $row['accept']==0){
					$data['Order_Status'] = "<a data-toggle='modal' onclick='Reject($row[training_id])' title='Reject Order'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #b50732;'>Reject</a>";
				}else if( $row['accept']==2){
					$data['Order_Status'] = "<a data-toggle='modal' onclick='active($row[training_id])' title='Inactive'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #e7174a;'>C B Admin</a>";
				}else if( $row['accept']==3){
					$data['Order_Status'] = "<a data-toggle='modal' onclick='active($row[training_id])' title='Inactive'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #c35b75;'>C B User</a>";
				}else if($row['accept']==1 && $row['status']==1){
					$data['Order_Status'] = "<a data-toggle='modal' onclick='active($row[training_id])' title='Inactive'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 85px;padding-left: 10px; color: #FFFFFF;background-color: #218a23;'>Complete</a>";
				}
				$data['Action'] = "<a data-toggle='modal' href='#draggable3' onclick='viewdata($row[training_id])' title='View'> <i class='fa fa-eye' style='background-color:red;color: white;padding: 1px;'></i></a> &nbsp;<a data-toggle='modal' href='#draggable4' onclick='Invoicedata($row[training_id])' title='Edit'> <i class='fa fa fa-info' style='background-color:blue;color: white;padding: 1px;'></i></a>&nbsp;<a onclick='Reject($row[training_id])' title='Order Cencel'> <i class='fa fa-times-circle-o' style='background-color:#6d086f;color: white;padding: 1px;'></i></a>&nbsp;<a data-toggle='modal' href='#updatetraining' onclick='Edit($row[training_id])' title='Edit Order'> <i class='fa fa-pencil' style='background-color:#6d086f;color: white;'></i></a>";
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
	
	//=============End GET Training Function=============
	
	//===========Start Add Training order Function==============
	
	public function addtrainingorder(){  
		try{ 
			$con=$this->getConnection(); 
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$user_name =isset( $_POST['user_name']) ? $_POST['user_name']: '';
			$email =isset( $_POST['email']) ? $_POST['email']: '';
			$contact =isset( $_POST['contact']) ? $_POST['contact']: '';
			$address =isset( $_POST['address']) ? $_POST['address']: '';
			$category =isset( $_POST['category']) ? $_POST['category']: '';
			$size =isset( $_POST['breed']) ? $_POST['breed']: '';
			$ap_date =isset( $_POST['ap_date']) ? $_POST['ap_date']: '';
			$price =isset( $_POST['price']) ? $_POST['price']: '';
			$service2 =$_REQUEST['type'];
			date_default_timezone_set('Asia/Kolkata');
			$mydate= date("Y-m-d H:i:s");
			if($add=='add')
			{
				if($user_name == ''){ 
					$data['error'] = "Please fill user name";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($email == ''){  
					$data['error'] = "Please fill Email ID";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($contact == ''){ 
					$data['error'] = "Please fill Contact";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($address == ''){ 
					$data['error'] = "Please fill Address";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($category == ''){ 
					$data['error'] = "Please select Category";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($size == ''){ 
					$data['error'] = "Please select Breed";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($price == ''){ 
					$data['error'] = "Please fill price";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}
				if($service2 == ''){ 
					$data['error'] = "Please select Course!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}
				if($service2 !='' ){ 
					$sql = "SELECT * FROM owner_registration WHERE contact_number=?";
					$stmt = $con->prepare($sql);
					$stmt->execute(array("$contact"));
					$cityfetch = $stmt->fetch();
					if($cityfetch != null){  
						$lastId = $cityfetch['owner_id']; 
					}else{   
						$exq="INSERT INTO owner_registration set full_name='$user_name',email_id='$email',contact_number='$contact',address='$address',status_id='1'";
						$stmt = $con->prepare($exq);
						$stmt->execute(); 
						$lastId = $con->lastInsertId();
						$exq1="INSERT INTO login set user_name='$email',contact_no='$contact',owner_id='$lastId',status_id='1'";
						$stmt1 = $con->prepare($exq1);
						$stmt1->execute();
					}
					if($lastId >0){   
						$order = substr(md5(microtime()),rand(0,26),5);
						$exq2="INSERT INTO training set category_id='$category',user_id='$lastId',user_name='$user_name',phone_number='$contact',user_email='$email',order_id='$order',address='$address',type='$service2',breed_name='$size',price='$price',ap_date='$ap_date',created_date='$mydate'";
						$stmt2 = $con->prepare($exq2);
						$stmt2->execute();
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
					$data= "Please select service!";
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
	
	
	//===========End Add Training order Function==============
	//===========Start Add Training order Function==============
	
	public function updateTrainingorder(){   
		try{  
			$con=$this->getConnection();
			//print_r($_REQUEST); die;
			$order_id =isset( $_POST['order_id'] ) ? $_POST['order_id']: '';
			$user_name =isset( $_POST['user_name']) ? $_POST['user_name']: '';
			$email =isset( $_POST['email']) ? $_POST['email']: ''; 
			$contact =isset( $_POST['contact']) ? $_POST['contact']: ''; 
			$address =isset( $_POST['address']) ? $_POST['address']: ''; 
			$ap_date =isset( $_POST['ap_date']) ? $_POST['ap_date']: ''; 
			$price =isset( $_POST['price']) ? $_POST['price']: ''; 
			$service2 =$_REQUEST['type'];
			date_default_timezone_set('Asia/Kolkata'); 
			$mydate= date("Y-m-d H:i:s"); 
			if($service2 !='' ){   
				$sql = "SELECT * FROM owner_registration WHERE contact_number=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$contact"));
				$cityfetch = $stmt->fetch();
				if($cityfetch != null){  
					$lastId = $cityfetch['owner_id']; 
				}else{    
					$exq="INSERT INTO owner_registration set full_name='$user_name',email_id='$email',contact_number='$contact',address='$address',status_id='1'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					$exq1="INSERT INTO login set user_name='$email',contact_no='$contact',owner_id='$lastId',status_id='1'";
					$stmt1 = $con->prepare($exq1);
					$stmt1->execute();
				}
				if($lastId >0){       
					$order = substr(md5(microtime()),rand(0,26),5);
					$exq2="Update training set user_id='$lastId',user_name='$user_name',phone_number='$contact',user_email='$email',order_id='$order',address='$address',type='$service2',price='$price',ap_date='$ap_date',created_date='$mydate' where training_id='$order_id'";
					$stmt2 = $con->prepare($exq2);
					$stmt2->execute();
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
				$data= "Please select service!";
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}
		}
		catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	
	
	//=============GET Training Function=============
	
	
	public function get_Training1(){   
		try {
			$con=$this->getConnection();
			$dadat_id =isset( $_POST['dadat_id'] ) ? $_POST['dadat_id']: '';
			$page=$con->prepare("SELECT user_name,breed_name,ap_date FROM `training` where training_id='$dadat_id'");
			$page->execute();
			$data = $page->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	
	//=============End GET Training Function=============
	public function edit_training(){   
		try {
			$con=$this->getConnection();
			$order_id =isset( $_POST['order_id'] ) ? $_POST['order_id']: '';
			$page=$con->prepare("SELECT * FROM `training` where training_id='$order_id'");
			$page->execute();
			$data = $page->fetch(PDO::FETCH_ASSOC);
			$type = $data['type'];
			$page1=$con->prepare("SELECT * FROM `training_type` where training_id='$type'");
			$page1->execute();
			$data1 = $page1->fetch(PDO::FETCH_ASSOC);
			$data['t_id'] = $order_id;
			$data['type'] = $data1['training_type'];
			array_push($data,$data['t_id']);
			array_push($data,$data['type']);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	//===========Start Accept Training Function==============
	
	
	public function AcceptTrainingrequest(){   
		try {
			$con=$this->getConnection();
			$start_date1 =isset( $_REQUEST['start_date'] ) ? $_REQUEST['start_date']: '';
			$licence =isset( $_REQUEST['licence'] ) ? $_REQUEST['licence']: '';
			 if($start_date1 !=''){
				$view=$con->prepare("Update training SET accept ='1' ,ap_date='$start_date1' WHERE training_id='$licence'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Service no Update in record. Please try again!';
				}
			}else{
				$data='Something is missing';
			} 
			
			
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Accept Training Function==============
	
	//===========Start Accept Training Function==============
	
	
	public function AssignVendor1(){   
		try {
			$con=$this->getConnection();
			$assign =isset( $_REQUEST['assign'] ) ? $_REQUEST['assign']: '';
			$assignid =isset( $_REQUEST['assignid'] ) ? $_REQUEST['assignid']: '';
			 if($assign !=''){
				$view=$con->prepare("Update training SET assigned_name ='$assign' WHERE training_id='$assignid'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Training no Update in record. Please try again!';
				}
			}else{
				$data='Something is missing';
			} 
			
			
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Accept Training Function==============
	
	//===========Start Accept Training Function==============
	
	
	public function RejectTrainingrequest(){   
		try { 
			$con=$this->getConnection();
			$RejectTrainingrequest =isset( $_POST['RejectTrainingrequest'] ) ? $_POST['RejectTrainingrequest']: '';
			$view=$con->prepare("Update training SET accept ='2' WHERE training_id='$RejectTrainingrequest'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Service no Update in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Accept Training Function==============
	
	//===========Start Accept Training Function==============
	
	
	public function CompleteTrainingrequest(){   
		try {
			$con=$this->getConnection();
			$Completetrainingrequest =isset( $_POST['Completetrainingrequest'] ) ? $_POST['Completetrainingrequest']: '';
			$view=$con->prepare("Update training SET accept ='1',status=1 WHERE training_id='$Completetrainingrequest'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Training no Update in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Accept Training Function==============
	
	
	
	
	//=============GET TRAINING TYPE Function=============
	
	
	public function get_trainingtype(){   
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM training_type");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['training_id'] = $row['training_id'];
				$data['training_type'] = $row['training_type'];
				$data['duration'] = $row['duration'];
				$data['color'] = $row['color'];
				$data['price'] = $row['price'];
				$data['offer_price'] = $row['offer_price'];
				if( $row['status_id']==1){   
				$data['Status'] = "<a data-toggle='modal' onclick='active($row[training_id])' title='Inactive'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 65px;padding-left: 10px; color: #FFFFFF;background-color: #26a69a;'>Active</a>";
				}else{ 
					$data['Status'] = "<a data-toggle='modal' onclick='active($row[training_id])' title='Active'  class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right: 65px;padding-left: 10px; color: #FFFFFF;background-color: #e7174a;'>DeActive</a>";
				}
				$data['Action'] = "<a data-toggle='modal' href='#updatetrainingtype'  onclick='Edit($row[training_id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a> <a data-toggle='modal' onclick='deletetrainingtype($row[training_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	//=============End GET TRAINING TYPE Function=============
	
	
	
	//============= ADD TRAINING TYPE Function ===============
	public function add_trainingtype(){  
		try{ 
			$con=$this->getConnection(); 
			//print_r($_REQUEST); die;
			$add =isset( $_REQUEST['add']) ? $_REQUEST['add']: '';
			$name =isset( $_REQUEST['name']) ? $_REQUEST['name']: '';
			$duration =isset( $_REQUEST['duration']) ? $_REQUEST['duration']: '';
			$price =isset( $_REQUEST['price']) ? $_REQUEST['price']: '';
			$color =isset( $_REQUEST['color']) ? $_REQUEST['color']: '';
			$fprice =isset( $_REQUEST['fprice']) ? $_REQUEST['fprice']: '';
			if($add=='add'){ 
				if(strlen(preg_replace('/\s+/u','',$name)) == 0){ 
					$data['error'] = "Please Select Training Type name";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if(strlen(preg_replace('/\s+/u','',$duration)) == 0){  
					$data['error'] = "Please Select Duration Name";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if(strlen(preg_replace('/\s+/u','',$price)) == 0){ 
					$data['error'] = "Please fill price";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if(strlen(preg_replace('/\s+/u','',$color)) == 0){ 
					$data['error'] = "Please fill color";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if($fprice == ''){ 
					$data['error'] = "Please fill offer price";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}	 
				$sql = "SELECT * FROM training_type WHERE training_type=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$cityfetch = $stmt->fetch();
				if($cityfetch != null){ 
					$data['error'] = "Training name already exist!"; 
					header('Content-type: application/json');
					echo  json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}else{
					$exq="INSERT INTO training_type set training_type='$name',duration='$duration',color='$color',price='$price',offer_price='$fprice'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
				}
				if($lastId >0){ 
					$data['status']= '1';
				}else{  
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
				
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
	
	//===========End Add TRAINING TYPE Function==============
	
	//===========Veiw TRAINING list Function============
	
	
	public function veiw_trainingtype(){   
		try {
			$con=$this->getConnection();
			$edittrainingtype =isset( $_POST['edittrainingtype'] ) ? $_POST['edittrainingtype']: '';
			$view=$con->prepare("SELECT * FROM training_type WHERE training_id='$edittrainingtype'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	
	//===========End View TRAINING Function=================
	//============= Update TRAINING Function ===============
	
	
	public function update_trainingtype(){  
		try{ 
			$con=$this->getConnection();
			$training_id =isset( $_POST['training_id'] ) ? $_POST['training_id']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			$duration =isset( $_POST['duration']) ? $_POST['duration']: '';
			$price =isset( $_POST['price']) ? $_POST['price']: '';
			$color =isset( $_POST['color']) ? $_POST['color']: '';
			$fprice =isset( $_POST['fprice']) ? $_POST['fprice']: '';
			if($name == '' || $duration=='' || $price==''||$color==''||$fprice==''){   
				$data['error'] = "Please fill all field!";
				header('Content-type: application/json'); 
				echo json_encode($data,JSON_PRETTY_PRINT);
				return false;
			}
			$exq="UPDATE training_type set training_type='$name',duration='$duration',price='$price',color='$color',offer_price='$fprice' where training_id='$training_id'";
			$stmt = $con->prepare($exq);
			$stmt->execute(); 
			$lastId = $con->lastInsertId();
			if($stmt)
			{
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
	
	
	//===========End Update TRAINING Function==============
	
	//============= Edit TRAINING TYPE Function ===============
	public function active_trainingtype(){ 
		try{ 
			$con=$this->getConnection();
			$active_trainingtype =isset( $_POST['active_trainingtype'] ) ? $_POST['active_trainingtype']: '';
			if($active_trainingtype>0){ 
				$view=$con->prepare("SELECT * FROM training_type WHERE training_id='$active_trainingtype'");
				$view->execute();
				$dat1 = $view->fetch(PDO::FETCH_ASSOC);
				if($dat1['status_id'] ==1){ 
					$exq="UPDATE training_type set status_id='0' WHERE training_id='$active_trainingtype'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
				}else{  
					$exq="UPDATE training_type set status_id='1' WHERE training_id='$active_trainingtype'";
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
	//===========End Edit TRAINING TYPE Function==============
	//===========End Delete TRAINING TYPE Function==============
	
	
	public function deletetrainingtype(){   
		try {
			$con=$this->getConnection();
			$deletetrainingtype =isset( $_POST['deletetrainingtype'] ) ? $_POST['deletetrainingtype']: '';
			$view=$con->prepare("DELETE from training_type WHERE training_id='$deletetrainingtype'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Training no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Delete TRAINING TYPE Function==============
	
	
	//============================================================================================================
	// ===============================//END TRAINING TYPE LIST FUNCTION//======================================
	//============================================================================================================
	
	//============================================================================================================
	// =================================//START TRAINER LIST FUNCTION//=====================================
	//============================================================================================================
	
	
	//=============GET TRAINER Function=============
	
	
	public function get_trainer(){   
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM pet_trainer");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){  
				$data['trainer_id'] = $row['trainer_id'];
				$data['name'] = $row['name'];
				$data['email'] = $row['email'];
				$data['contact'] = $row['contact'];
				$data['work_area'] = $row['work_area'];
				$data['qualification'] = $row['qualification'];
				$page1=$con->prepare("SELECT * FROM training where assigned_name='$data[name]'");
				$page1->execute();
				$total1 = $page1->rowCount();
				if($total1 >0){   
				$data['Orders'] = "<a data-toggle='modal' onclick='orders($row[trainer_id])' title='Orders'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right:80px;padding-left: 10px; color: #FFFFFF;background-color: #26a69a;'>$total1 Orders</a>";
				}else{ 
					$data['Orders'] = "<a class='btn btn-icon-only red' style='margin-top: 0px;margin-left: 0px;margin-right: 6px;    padding-right:80px; padding-left: 10px; color: #FFFFFF;background-color: #e7174a;'>No Orders</a>";
				}
				$data['Action'] = "<a data-toggle='modal' href='#updatetrainer'  onclick='Edit($row[trainer_id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a> <a data-toggle='modal' onclick='deletetrainer($row[trainer_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	//=============End GET TRAINER Function=============	
	
	//============= ADD TRAINER Function ===============
	public function add_trainer(){  
		try{ 
			$con=$this->getConnection(); 
			//print_r($_REQUEST); die;
			$add =isset( $_REQUEST['add']) ? $_REQUEST['add']: '';
			$name =isset( $_REQUEST['name']) ? $_REQUEST['name']: '';
			$email =isset( $_REQUEST['email']) ? $_REQUEST['email']: '';
			$contact =isset( $_REQUEST['contact']) ? $_REQUEST['contact']: '';
			$work_area =isset( $_REQUEST['work_area']) ? $_REQUEST['work_area']: '';
			$qualification =isset( $_REQUEST['qualification']) ? $_REQUEST['qualification']: '';
			$Age =isset( $_REQUEST['Age']) ? $_REQUEST['Age']: '';
			$address =isset( $_REQUEST['address']) ? $_REQUEST['address']: '';
			if($add=='add'){ 
				if(strlen(preg_replace('/\s+/u','',$name)) == 0){ 
					$data['error'] = "Please Select Trainer name";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if(strlen(preg_replace('/\s+/u','',$email)) == 0){  
					$data['error'] = "Please Select Email ID";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if(strlen(preg_replace('/\s+/u','',$contact)) == 0){ 
					$data['error'] = "Please fill Contact";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if(strlen(preg_replace('/\s+/u','',$work_area)) == 0){ 
					$data['error'] = "Please fill Work Area";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				if(strlen(preg_replace('/\s+/u','',$address)) == 0){ 
					$data['error'] = "Please fill Address";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}	 
				$sql = "SELECT * FROM pet_trainer WHERE name=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$cityfetch = $stmt->fetch();
				if($cityfetch != null){ 
					$data['error'] = "Trainer name already exist!"; 
					header('Content-type: application/json');
					echo  json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}else{ 
					$exq="INSERT INTO pet_trainer set name='$name',email='$email',contact='$contact',address='$address',age='$Age',work_area='$work_area',qualification='$qualification'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
				}
				if($lastId >0){ 
					$data['status']= '1';
				}else{  
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
				
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
	
	//===========End Add TRAINER Function==============
	
	//===========Veiw TRAINER list Function============
	
	public function veiw_trainer(){   
		try {
			$con=$this->getConnection();
			$edittrainer =isset($_POST['edittrainer']) ? $_POST['edittrainer']: '';
			$view=$con->prepare("SELECT * FROM pet_trainer WHERE trainer_id='$edittrainer'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch(PDOException $e){ 
			print $e->getMessage();
		}
	}
	
	
	//===========End View TRAINER Function=================
	
	//===========Veiw Order TRAINER list Function============
	
	
	public function veiw_order(){   
		try {
			$con=$this->getConnection();
			$edittrainer =isset($_POST['edittrainer']) ? $_POST['edittrainer']: '';
			$view1=$con->prepare("SELECT * FROM pet_trainer WHERE trainer_id='$edittrainer'");
			$view1->execute();
			$data1 = $view1->fetch(PDO::FETCH_ASSOC);
			$name = $data1['name'];
			$view=$con->prepare("SELECT * FROM training WHERE assigned_name='$name'");
			$view->execute();
			$data = $view->fetchAll(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch(PDOException $e){ 
			print $e->getMessage();
		}
	}
	
	
	//===========End View Order TRAINER Function=================
	
	
	
	//============= Update TRAINER Function ===============
	
	
	public function update_trainer(){ 
		try{ 
			$con=$this->getConnection();
			$trainer_id =isset( $_POST['trainer_id'] ) ? $_POST['trainer_id']: '';
			$name =isset( $_REQUEST['name']) ? $_REQUEST['name']: '';
			$email =isset( $_REQUEST['email']) ? $_REQUEST['email']: '';
			$contact =isset( $_REQUEST['contact']) ? $_REQUEST['contact']: '';
			$work_area =isset( $_REQUEST['work_area']) ? $_REQUEST['work_area']: '';
			$qualification =isset( $_REQUEST['qualification']) ? $_REQUEST['qualification']: '';
			$Age =isset( $_REQUEST['Age']) ? $_REQUEST['Age']: '';
			$address =isset( $_REQUEST['address']) ? $_REQUEST['address']: '';
			if($name == '' || $email=='' || $contact==''||$work_area==''||$address==''){   
				$data['error'] = "Please fill all field!";
				header('Content-type: application/json');
				echo json_encode($data,JSON_PRETTY_PRINT);
				return false;
			}
			$exq="UPDATE pet_trainer set name='$name',email='$email',contact='$contact',address='$address',age='$Age',work_area='$work_area',qualification='$qualification' Where trainer_id='$trainer_id'";
			$stmt = $con->prepare($exq);
			$stmt->execute(); 
			if($stmt){
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
	
	
	//===========End Update TRAINER Function==============
	
	//============= Edit TRAINER Function ===============
	public function active_trainer(){ 
		try{ 
			$con=$this->getConnection();
			$active_trainingtype =isset( $_POST['active_trainingtype'] ) ? $_POST['active_trainingtype']: '';
			if($active_trainingtype>0){ 
				$view=$con->prepare("SELECT * FROM training_type WHERE training_id='$active_trainingtype'");
				$view->execute();
				$dat1 = $view->fetch(PDO::FETCH_ASSOC);
				if($dat1['status_id'] ==1){ 
					$exq="UPDATE training_type set status_id='0' WHERE training_id='$active_trainingtype'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
				}else{  
					$exq="UPDATE training_type set status_id='1' WHERE training_id='$active_trainingtype'";
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
	//===========End Edit TRAINER  Function==============
	//===========End Delete TRAINER Function==============
	
	
	public function deletetrainer(){   
		try {
			$con=$this->getConnection();
			$deletetrainer =isset( $_POST['deletetrainer'] ) ? $_POST['deletetrainer']: '';
			$view=$con->prepare("DELETE from pet_trainer WHERE trainer_id='$deletetrainer'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Training no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Delete TRAINER Function==============
	
	
	//==========================================================================================================
	// ===============================//END TRAINER LIST FUNCTION//======================================
	//==========================================================================================================
	
	//===========================================================================================================
	// ======================================//START MATING FUNCTION//===========================================
	//===========================================================================================================
	
	
	
	
	//=============GET Mating Function=============
		
	public function get_mating(){  
		try{  
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM pet_mating order by pet_mating_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			foreach($sportfetch as $row){
				$data['pet_mating_id'] = $row['pet_mating_id'];
				$data['pet_owner_name'] = $row['pet_owner_name'];
				$data['email'] = $row['email'];
				$data['contact_no'] = $row['contact_no'];
				$data['address'] = $row['address'];
				$data['pet_name'] = $row['pet_name'];
				$data['pet_dob'] = $row['pet_dob'];
				$data['pet_gender_id'] = $row['pet_gender_id'];
				if($row['status_id'] ==1){
					$status_id = 'open';
				}else{
					$status_id = 'close';
				}
				$data['status_id'] = $status_id;
				$data['created_date'] = $row['created_date'];
				$page1=$con->prepare("SELECT * FROM mating_request where pet_owner_request='$row[owner_id]' and pet_id='$row[pet_id]'");
				$page1->execute();
				$total1 = $page1->rowCount();
				if($total1 >0){   
					$data['View_Request'] = "<a data-toggle='modal' href='#viewrequest' onclick='matingRequest($row[pet_mating_id])' title='View Request' style='padding-left: 14px; padding-right: 14px;    margin-left: 10%;' class='btn btn-xs blue'> $total1 </a>"; 
				}else{ 
					$data['View_Request'] = "<a style='padding-left: 14px; padding-right: 14px; margin-left: 10%;' title='No Request' class='btn btn-xs red'>0</a></button>"; 
				}
				$data['Action'] = "<a data-toggle='modal' onclick='deletemating($row[pet_mating_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Mating Function==============
		
	//=============Start Mating Request Function=============
	
	public function matingRequest(){  
		try{  
			$mating_id =isset( $_REQUEST['mating_id'] ) ? $_REQUEST['mating_id']: '';
			$con=$this->getConnection();
			$page1=$con->prepare("SELECT * FROM pet_mating where pet_mating_id ='$mating_id'");
			$page1->execute();
			$sportfetch1 = $page1->fetch(PDO::FETCH_ASSOC);
			$owner_id = $sportfetch1['owner_id'];
			$pet_id = $sportfetch1['pet_id'];
			$page = $con->prepare("SELECT * FROM mating_request  where pet_owner_request='$owner_id' and pet_id='$pet_id' order by id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			foreach($sportfetch as $row){      
				$data['id'] = $row['id'];
				$data['user_pet_id'] = $row['user_pet_id'];
				$page2=$con->prepare("SELECT * FROM pet_detail where petdetails_id ='$data[user_pet_id]'");
				$page2->execute();
				$sportfetch2 = $page2->fetch(PDO::FETCH_ASSOC);
				$data['pet_name'] = $sportfetch2['pet_name'];
				$data['user_id'] = $row['user_id'];
				$page12=$con->prepare("SELECT * FROM owner_registration where owner_id ='$data[user_id]'");
				$page12->execute();
				$sportfetch12 = $page12->fetch(PDO::FETCH_ASSOC);
				$data['name'] = $sportfetch12['full_name'];
				$data['email'] = $sportfetch12['email_id'];
				$data['contact'] = $sportfetch12['contact_number'];
				$data['Action'] = "<a data-toggle='modal' onclick='deleteRequest($row[id])' title='Delete'  class='btn btn-icon-only purple'><i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Mating Request Function==============
	
	//===========Delete Mating Function==============
	
		public function deletemating(){   
			try { 
				$con=$this->getConnection();
				$deletemating =isset( $_POST['deletemating'] ) ? $_POST['deletemating']: '';
				$view=$con->prepare("DELETE from pet_mating WHERE pet_mating_id='$deletemating'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Mating no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End Mating Function==============
	
	//===========Delete Mating Function==============
	
		public function deletematingrequest(){   
			try { 
				$con=$this->getConnection();
				$deletematingrequest =isset( $_POST['deletematingrequest'] ) ? $_POST['deletematingrequest']: '';
				$view=$con->prepare("DELETE from mating_request WHERE id='$deletematingrequest'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Request no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End Mating Function==============
	
	//=========================================================================================================
	// ======================================//END MATING FUNCTION//===========================================
	//=========================================================================================================
	
	
	//===========================================================================================================
	// ======================================//START EVENT FUNCTION//===========================================
	//===========================================================================================================
	
	
	
	
	//=============GET Event Function=============
		
	public function getevent(){  
		try{  
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM pets_event order by event_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			foreach($sportfetch as $row){
				$data['event_id'] = $row['event_id'];
				$data['name'] = $row['name'];
				$data['banner'] = '<img src="lib/image/'.$row['banner'].'" width="100px;" hieght="70px;">';
				$data['start_date'] = $row['start_date'].' '.$row['start_time'];
				$data['end_date'] =  $row['end_date'].' '.$row['end_time'];
				$data['address'] = $row['address'];
				$data['web_url'] = $row['web_url'];
				$data['Action'] = "<a data-toggle='modal' href='#updateevent'  onclick='Edit($row[event_id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a> <a data-toggle='modal' onclick='deleteevent($row[event_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Event Function==============
	
	//===========Delete Event Function==============
	
		public function deleteevent(){   
			try{ 
				$con=$this->getConnection();
				$deleteevent =isset( $_POST['deleteevent'] ) ? $_POST['deleteevent']: '';
				$view=$con->prepare("DELETE from pets_event WHERE event_id='$deleteevent'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Request no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End Event Function==============
	//===========Veiw Event list Function============
		
		public function veiw_event(){   
			try{
				$con=$this->getConnection();
				$veiw_event =isset( $_POST['veiw_event'] ) ? $_POST['veiw_event']: '';
				$view=$con->prepare("SELECT * FROM pets_event WHERE event_id='$veiw_event'");
				$view->execute();
				$data = $view->fetch(PDO::FETCH_ASSOC);
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End View Event Function=================
		//============= Add Event Function ===============
		public function add_event(){ 
			try{ 
				$con=$this->getConnection();
				$add =isset( $_POST['add'] ) ? $_POST['add']: '';
				$event_name =isset( $_POST['event_name'] ) ? $_POST['event_name']: '';
				$address =isset( $_POST['address'] ) ? $_POST['address']: '';
				$start_date =isset( $_POST['start_date'] ) ? $_POST['start_date']: '';
				$start_time =isset( $_POST['start_time'] ) ? $_POST['start_time']: '';
				$end_date =isset( $_POST['end_date'] ) ? $_POST['end_date']: '';
				$end_time =isset( $_POST['end_time'] ) ? $_POST['end_time']: '';
				$link =isset( $_POST['link'] ) ? $_POST['link']: '';
				$uploadedfile1 = $_FILES['banner']['name'];
				if($add =='add'){  
					if($start_date == '' || $start_time ==''){   
						$data['error'] = "Please Select Start/End Date!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
						exit();
					}
					if($uploadedfile1 !=''){ 
						$path = "image/";
						$name2 = md5(rand(999,99999)).$uploadedfile1;
						move_uploaded_file($_FILES['banner']['tmp_name'],$path.$name2);
						$exq="INSERT INTO pets_event set name='$event_name',address='$address',start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time',web_url='$link',banner='$name2'";
						$stmt = $con->prepare($exq);
						$stmt->execute(); 
						$data['status']= '1';
					}else{  
						$exq="INSERT INTO pets_event set name='$event_name',address='$address',start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time',web_url='$link'";
						$stmt = $con->prepare($exq);
						$stmt->execute(); 
						$data['status']= '1';
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
		//===========End Add Event Function==============
		
		//============= Edit Event Function ===============
		public function updateevent(){ 
			try{ 
				$con=$this->getConnection();
				$event_id =isset( $_POST['event_id'] ) ? $_POST['event_id']: '';
				$event_name =isset( $_POST['event_name'] ) ? $_POST['event_name']: '';
				$address =isset( $_POST['address'] ) ? $_POST['address']: '';
				$start_date =isset( $_POST['start_date'] ) ? $_POST['start_date']: '';
				$start_time =isset( $_POST['start_time'] ) ? $_POST['start_time']: '';
				$end_date =isset( $_POST['end_date'] ) ? $_POST['end_date']: '';
				$end_time =isset( $_POST['end_time'] ) ? $_POST['end_time']: '';
				$link =isset( $_POST['link'] ) ? $_POST['link']: '';
				$uploadedfile1 = $_FILES['banner']['name'];
				if($event_id>0){  
					if($start_date == '' || $start_time ==''){ 
						$data['error'] = "Please Select Start/End Date!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
						exit();
					}
					if($uploadedfile1 !=''){ 
						$path = "image/";
						$name2 = md5(rand(999,99999)).$uploadedfile1;
						move_uploaded_file($_FILES['banner']['tmp_name'],$path.$name2);
						$exq="UPDATE pets_event set name='$event_name',address='$address',start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time',web_url='$link',banner='$name2' where event_id='$event_id	'";
						$stmt = $con->prepare($exq);
						$stmt->execute(); 
						$data['status']= '1';
					}else{  
						$exq="UPDATE pets_event set name='$event_name',address='$address',start_date='$start_date',end_date='$end_date',start_time='$start_time',end_time='$end_time',web_url='$link' where event_id='$event_id	'";
						$stmt = $con->prepare($exq);
						$stmt->execute(); 
						$data['status']= '1';
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
		//===========End Edit Event Function==============
		
	
	//===========================================================================================================
	// ======================================//END EVENT FUNCTION//===========================================
	//===========================================================================================================
	
	//===========================================================================================================
	// ====================================//START VACCINATION FUNCTION//=======================================
	//===========================================================================================================
	
	//==============GET Vaccination order Function=============
	
	public function get_vaccination(){  
		try{   
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM vaccination_order order by id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			foreach($sportfetch as $row){
				$data['id'] = $row['id'];
				$data['order_no'] = $row['order_no'];
				$data['user_name'] = $row['user_name'];
				$data['user_contact_no'] = $row['user_contact_no'];
				$data['doctor_name'] = $row['doctor_name'];
				$data['Booking_date'] = $row['Booking_date'].' '.$row['Booking_time'];
				$data['total_price'] = $row['total_price'];
				if($row['status'] ==0){     
					$data['status'] = 'Pending';
				}else if($row['status'] ==1){   
					$data['status'] = 'Complete';
				}else{   
					$data['status'] = 'Cancel';
				}
				$data['Action'] = "<a data-toggle='modal' href='#updateVaccination'  onclick='View($row[id])' title='View' class='btn btn-icon-only red'><i class='fa fa-eye'></i></a> <a data-toggle='modal' href='#editVaccination'  onclick='Edit($row[id])' title='Edit' class='btn btn-icon-only green'><i class='fa fa-pencil'></i></a>  <a data-toggle='modal' onclick='deleteVaccination($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Vaccination order Function==============
	//===========Veiw Vaccination list Function============
		
		public function veiw_vaccination(){    
			try{ 
				$con=$this->getConnection();
				$editvaccination =isset( $_POST['editvaccination'] ) ? $_POST['editvaccination']: '';
				$view=$con->prepare("SELECT * FROM vaccination_order WHERE id='$editvaccination'");
				$view->execute();
				$data = $view->fetch(PDO::FETCH_ASSOC);
				$view1=$con->prepare("SELECT * FROM vaccination_order_list WHERE order_id='$editvaccination'");
				$view1->execute();
				$data['vaccination'] = $view1->fetchAll();
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End View Vaccination order Function=================
		//===========Delete Vaccination order Function==============
	
		public function deletevaccination(){   
			try{ 
				$con=$this->getConnection();
				$deletevaccination =isset( $_POST['deletevaccination'] ) ? $_POST['deletevaccination']: '';
				$view=$con->prepare("DELETE from vaccination_order WHERE id='$deletevaccination'");
				$view->execute();
				$view1=$con->prepare("DELETE from vaccination_order_list WHERE order_id='$deletevaccination'");
				$view1->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Request no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End Vaccination order Function==============
	
	//==============GET Vaccination price Function=============
	
	public function get_vaccinationprice(){  
		try{   
			$con=$this->getConnection();
			$page=$con->prepare("SELECT s.*,v.vaccination,r.full_name FROM `s_vaccination_price` as s left join s_registration as r on r.id=s.`s_user_id` left join vaccination as v on v.vac_id=s.`vaccination_id` WHERE r.category_type=1 order by s.id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			foreach($sportfetch as $row){
				$data['id'] = $row['id'];
				$data['vaccination'] = $row['vaccination'];
				$data['doctor_name'] = $row['full_name'];
				$data['price'] = $row['price'];
				$data['Action'] = "<a data-toggle='modal' href='#large2'  onclick='View($row[id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a> <a data-toggle='modal' onclick='deletevaccinationprice($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Vaccination price Function==============
	//===========Veiw Vaccination price Function============
		
		public function veiw_vaccinationprice(){    
			try{ 
				$con=$this->getConnection();
				$editvaccinationprice =isset( $_POST['editvaccinationprice'] ) ? $_POST['editvaccinationprice']: '';
				$view=$con->prepare("SELECT s.*,v.vaccination,r.full_name FROM `s_vaccination_price` as s left join s_registration as r on r.id=s.`s_user_id` left join vaccination as v on v.vac_id=s.`vaccination_id` WHERE r.category_type=1 and id='$editvaccinationprice'");
				$view->execute();
				$data = $view->fetch(PDO::FETCH_ASSOC);
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End View Vaccination price Function=================
		//===========Delete Vaccination price Function==============
	
		public function deletevaccinationprice(){   
			try{ 
				$con=$this->getConnection();
				$deletevaccinationprice =isset( $_POST['deletevaccinationprice'] ) ? $_POST['deletevaccinationprice']: '';
				$view=$con->prepare("DELETE from s_vaccination_price WHERE id='$deletevaccinationprice'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Request no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End Vaccination price Function==============
	
	//==============GET Vaccination List Function=============
	
	public function get_vaccinationlistlist(){  
		try{   
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM `vaccination` order by vac_id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			foreach($sportfetch as $row){
				$data['vac_id'] = $row['vac_id'];
				$data['vaccination'] = $row['vaccination'];
				$data['Action'] = "<a data-toggle='modal' href='#large2'  onclick='View($row[vac_id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a> <a data-toggle='modal' onclick='deletevaccinationlist($row[vac_id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Vaccination List Function==============
	//===========Veiw Vaccination List Function============
		
		public function veiw_vaccinationlist(){    
			try{ 
				$con=$this->getConnection();
				$editvaccinationlist =isset( $_POST['editvaccinationlist'] ) ? $_POST['editvaccinationlist']: '';
				$view = $con->prepare("SELECT * FROM vaccination WHERE vac_id='$editvaccinationlist'");
				$view->execute();
				$data = $view->fetch(PDO::FETCH_ASSOC);
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//==========End View Vaccination List Function============
		
		public function updateorder(){     
			try{ 
				$con=$this->getConnection();
				$id =isset( $_POST['id'] ) ? $_POST['id']: '';
				$u_name =isset( $_POST['u_name'] ) ? $_POST['u_name']: '';
				$contact =isset( $_POST['contact'] ) ? $_POST['contact']: '';
				$d_name =isset( $_POST['d_name'] ) ? $_POST['d_name']: '';
				$b_date =isset( $_POST['b_date'] ) ? $_POST['b_date']: '';
				$price =isset( $_POST['price']) ? $_POST['price']: '';
				$status =isset( $_POST['status']) ? $_POST['status']: '';
				if($id!=''){ 
					$exq="UPDATE vaccination_order set user_name='$u_name',user_contact_no='$contact',doctor_name='$d_name',Booking_date='$b_date',total_price='$price',status='$status' where id='$id'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					if($stmt==true){   
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
		

		//============= ADD Vaccination List Function ===============
		public function add_vaccinationlist(){  
			try{ 
				$con=$this->getConnection();
				$add =isset( $_POST['add'] ) ? $_POST['add']: '';
				$vaccination =isset( $_POST['vaccination']) ? $_POST['vaccination']: '';
				if($add=='add')
				{
					if($vaccination == '')
					{ 
						$data['error'] = "Please fill vaccination name!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
					}
					$sql = "SELECT * FROM vaccination WHERE vaccination=?";
					$stmt = $con->prepare($sql);
					$stmt->execute(array("$vaccination"));
					$categoryfetch = $stmt->fetch();
					if($categoryfetch != null){ 
						$data['error'] = "Vaccination name already exist!"; 
					}else{ 
						$exq="INSERT INTO vaccination set vaccination='$vaccination'";
						$stmt = $con->prepare($exq);
						$stmt->execute(); 
						$lastId = $con->lastInsertId();
						if($lastId>0)
						{
							$data['status']= '1';
						}else{ 
							$data['error']= "Something is missing. Please try again!";
						}
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
		
		//===========End Add Vaccination List Function==============
		//============= EDIT Vaccination List Function ===============
		public function edit_vaccinationlist(){  
			try{ 
				$con=$this->getConnection();
				$id =isset( $_POST['id'] ) ? $_POST['id']: '';
				$vaccination =isset( $_POST['vaccination']) ? $_POST['vaccination']: '';
				if($id>0){ 
					if($vaccination == ''){ 
						$data['error'] = "Please fill vaccination name!";
						header('Content-type: application/json');
						echo json_encode($data,JSON_PRETTY_PRINT);
						return false;
					}
					$exq="UPDATE vaccination set vaccination='$vaccination' WHERE vac_id='$id'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					if($stmt==true){
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
			}catch (PDOException $e){
				print $e->getMessage();
			}
		}
		//===========End Add Vaccination List Function==============
	
		//===========Delete Vaccination List Function==============
	
		public function deletevaccinationlist(){   
			try{  
				$con=$this->getConnection();
				$deletevaccinationlist =isset( $_POST['deletevaccinationlist'] ) ? $_POST['deletevaccinationlist']: '';
				$view=$con->prepare("DELETE from vaccination WHERE vac_id='$deletevaccinationlist'");
				$view->execute();
				if($view == true){ 
					$data='1';
				}else{ 
					$data='Request no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		
	//===========End Vaccination List Function==============
	
	//===========================================================================================================
	// ====================================//END VACCINATION FUNCTION//=======================================
	//===========================================================================================================
	
	//===========================================================================================================
	// ======================================//START GAT FUNCTION//===========================================
	//===========================================================================================================
	
	
	
	
	//=============GET GAT Function=============
		
	public function get_GAT(){  
		try{  
			$con=$this->getConnection();
			$page=$con->prepare("SELECT u.*,pc.category_name,pb.breed_name,r.full_name,r.contact_number FROM `user_buy_pet` as u left join pet_category as pc on pc.category_id=u.`pet_type` left join pet_breed as pb on pb.breed_id=u.`pet_breed` left join owner_registration as r on r.owner_id=u.user_id order by u.id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			foreach($sportfetch as $row){ 
				$data['id'] = $row['id'];
				$data['Name'] = $row['full_name'];
				$data['Contact'] = $row['contact_number'];
				$data['category_name'] = $row['category_name'];
				$data['breed_name'] = $row['breed_name'];
				$data['buy_for'] = $row['buy_for'];
				$data['gender'] = $row['gender'];
				$data['price'] = $row['price'];
				$data['trained'] = $row['trained'];
				if($row['status'] ==1){
					$status = 'open';
				}else{
					$status = 'close';
				}
				$data['status'] = $status;
				$page1=$con->prepare("SELECT * FROM book_user_buy_pet where buy_adopt_id='$row[id]'");
				$page1->execute();
				$total1 = $page1->rowCount();
				if($total1 >0){   
					$data['View_Request'] = "<a data-toggle='modal' href='#viewrequest' onclick='GAPRequest($row[id])' title='View Request' style='padding-left: 14px; padding-right: 14px;    margin-left: 10%;' class='btn btn-xs blue'> $total1 </a>"; 
				}else{ 
					$data['View_Request'] = "<a style='padding-left: 14px; padding-right: 14px; margin-left: 10%;' title='No Request' class='btn btn-xs red'>0</a></button>"; 
				}
				$data['Action'] = "<a data-toggle='modal' onclick='deleteGAP($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End GAT Function==============
		
	//=============Start GAT Request Function=============
	
	public function GAPRequest(){  
		try{  
			$buy_id =isset( $_REQUEST['buy_id'] ) ? $_REQUEST['buy_id']: '';
			$con=$this->getConnection();
			$page = $con->prepare("SELECT bu.*,r.full_name,r.email_id,r.contact_number FROM book_user_buy_pet as bu left join owner_registration as r on r.owner_id=bu.buyer_id  where bu.buy_adopt_id='$buy_id' order by bu.id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			foreach($sportfetch as $row){      
				$data['id'] = $row['id'];
				$data['name'] = $row['full_name'];
				$data['email'] = $row['email_id'];
				$data['contact'] = $row['contact_number'];
				$data['Action'] = "<a data-toggle='modal' onclick='deleteRequest($row[id])' title='Delete'  class='btn btn-icon-only purple'><i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End GAT Request Function==============
	
	//===========Delete GAT Function==============
	
		public function deletebuy(){   
			try { 
				$con=$this->getConnection();
				$deletebuy =isset( $_POST['deletebuy'] ) ? $_POST['deletebuy']: '';
				$view=$con->prepare("DELETE from user_buy_pet WHERE id='$deletebuy'");
				$view->execute();
				$view=$con->prepare("DELETE from book_user_buy_pet WHERE buy_adopt_id='$deletebuy'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Mating no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End GAT Function==============
	
	//===========Delete GAT Function==============
	
		public function deletebuyrequest(){   
			try { 
				$con=$this->getConnection();
				$deletebuyrequest =isset( $_POST['deletebuyrequest'] ) ? $_POST['deletebuyrequest']: '';
				$view=$con->prepare("DELETE from book_user_buy_pet WHERE id='$deletebuyrequest'");
				$view->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Request no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End GAT Function==============
	
	//=========================================================================================================
	// ======================================//END GAT FUNCTION//===========================================
	//=========================================================================================================
	
	//===========================================================================================================
	// ====================================//START PETWALKER FUNCTION//=======================================
	//===========================================================================================================
	
	//==============GET Petwalker order Function=============
	
	public function get_petwalkerlist(){   
		try{   
			$con=$this->getConnection();
			$page=$con->prepare("SELECT pw.*,r.full_name,r.contact_number,p.packege_name,pd.pet_name,sr.full_name as petwlker_name FROM `s_petwalker_order` as pw left join owner_registration as r on r.owner_id=pw.user_id left join pet_detail as pd on pd.petdetails_id=pw.pet_id left join packeges as p on p.id=pw.`packege_id` left join s_registration as sr on sr.id=pw.petwalker_id order by pw.id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			foreach($sportfetch as $row){ 
				$data['id'] = $row['id'];
				$data['order_no'] = $row['order_no'];
				$data['full_name'] = $row['full_name'];
				$data['user_contact_no'] = $row['contact_number'];
				$data['petwlker_name'] = $row['petwlker_name'];
				$data['pet_name'] = $row['pet_name'];
				$data['packege_name'] = $row['packege_name'];
				$data['size'] = $row['size'];
				$data['order_date'] = $row['order_date'];
				$data['end_date'] = $row['end_date'];
				$data['amount'] = $row['amount'];
				$data['no_of_days'] = $row['no_of_days'];
				$data['no_of_walkes'] = $row['no_of_walkes'];
				if($row['status'] ==1){      
					$data['status'] = 'Pending';
				}else if($row['status'] ==2){    
					$data['status'] = 'Complete';
				}else{    
					$data['status'] = 'Cancel';
				}
				$data['Order_status'] = "<a data-toggle='modal' href='#large' onclick='order_status($row[id])' title='Order Status'  class='btn btn-icon-only green'style='margin-top: 0px;margin-left: 0px;margin-right: 6px; padding-right: 95px;padding-left: 10px; color: #FFFFFF;background-color: #26a69a;'>Order Status</a>"; 
				$data['Action'] = "<a data-toggle='modal' href='#large2'  onclick='View($row[id])' title='View Detail' class='btn btn-icon-only red'><i class='fa fa-eye'></i></a> <a data-toggle='modal' href='#large2'  onclick='View($row[id])' title='View Detail' class='btn btn-icon-only red'><i class='fa fa-eye'></i></a> <a data-toggle='modal' onclick='deletepetwalker($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Petwalker order Function==============
	
	//==============GET View Petwalker order Function=============
	
	public function get_petwalkerlist1(){    
		try{   
			$con=$this->getConnection();
			$petwalkerid =isset( $_POST['petwalkerid'] ) ? $_POST['petwalkerid']: '';
			$page=$con->prepare("SELECT pw.*,r.full_name,r.contact_number,p.packege_name,pd.pet_name,sr.full_name as petwlker_name FROM `s_petwalker_order` as pw left join owner_registration as r on r.owner_id=pw.user_id left join pet_detail as pd on pd.petdetails_id=pw.pet_id left join packeges as p on p.id=pw.`packege_id` left join s_registration as sr on sr.id=pw.petwalker_id where pw.id='$petwalkerid'");
			$page->execute();
			$data = $page->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End View Petwalker order Function==============
	
	//==============GET Petwalker order Status Function=============
	
	public function get_orderstatuslist(){   
		try{   
			$con=$this->getConnection();
			$order_id =isset( $_POST['order_id'] ) ? $_POST['order_id']: '';
			$page=$con->prepare("SELECT pw.*,pd.pet_name,sr.full_name,sr.contact_no FROM `s_petwalker_order_status` as pw left join pet_detail as pd on pd.petdetails_id=pw.pet_id left join s_registration as sr on sr.id=pw.petwalker_id where pw.oder_id='$order_id'");
			$page->execute();
			$sportfetch = $page->fetchAll();
			foreach($sportfetch as $row){ 
				$data['id'] = $row['id'];
				$data['full_name'] = $row['full_name'];
				$data['user_contact_no'] = $row['contact_no'];
				$data['pet_name'] = $row['pet_name'];
				$data['date'] = $row['date'];
				$data['timeslot_id'] = $row['timeslot_id'];
				$data['start_time'] = $row['start_time'];
				$data['end_time'] = $row['end_time'];
				$data['time_duretion'] = $row['time_duretion'];
				$data['distance'] = $row['distance'];
				$data['pee'] = $row['pee'];
				$data['poop'] = $row['poop'];
				$data['Action'] = "<a data-toggle='modal' onclick='deleteorderstatus($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Petwalker Order Status Function==============
	
	//===========Veiw Petwalker list Function============
		
		public function veiw_petwalker(){    
			try{ 
				$con=$this->getConnection();
				$editvaccination =isset( $_POST['editvaccination'] ) ? $_POST['editvaccination']: '';
				$view=$con->prepare("SELECT * FROM vaccination_order WHERE id='$editvaccination'");
				$view->execute();
				$data = $view->fetch(PDO::FETCH_ASSOC);
				$view1=$con->prepare("SELECT * FROM vaccination_order_list WHERE order_id='$editvaccination'");
				$view1->execute();
				$data['vaccination'] = $view1->fetchAll();
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e){ 
				print $e->getMessage();
			}
		}
		//===========End View Petwalker order Function=================
		
		//===========Delete Petwalker order Function==============
	
		public function deleteorderstatus(){   
			try{ 
				$con=$this->getConnection();
				$deleteorderstatus =isset( $_POST['deleteorderstatus'] ) ? $_POST['deleteorderstatus']: '';
				$view=$con->prepare("DELETE from s_petwalker_order_status WHERE id='$deleteorderstatus'");
				$view->execute();
				if($view == true){ 
					$data='1';
				}else{
					$data='Request no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End Petwalker order Function==============
	
	//===========Delete Petwalker order Function==============
	
		public function deletepetwalker(){   
			try{ 
				$con=$this->getConnection();
				$deletepetwalker =isset( $_POST['deletepetwalker'] ) ? $_POST['deletepetwalker']: '';
				$view=$con->prepare("DELETE from s_petwalker_order WHERE id='$deletepetwalker'");
				$view->execute();
				$view1=$con->prepare("DELETE from s_petwalker_order_status WHERE oder_id='$deletepetwalker'");
				$view1->execute();
				if($view == true){
					$data='1';
				}else{
					$data='Request no delete in record. Please try again!';
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
			}catch (PDOException $e) { 
				print $e->getMessage();
			}
		}
		
	//===========End Petwalker order Function==============
	
	
	//==============GET Petwalker order Status Function=============
	
	public function get_subscriptionlist(){   
		try{   
			$con=$this->getConnection();
			$page=$con->prepare("SELECT s.*,pd.pet_name,r.full_name,r.contact_number,so.package_name FROM `subscription` as s left join owner_registration as r on r.owner_id=s.user_id left join pet_detail as pd on pd.petdetails_id =s.pet_id left join subscription_offer as so on so.id=s.`package_id`");
			$page->execute();
			$sportfetch = $page->fetchAll();
			foreach($sportfetch as $row){ 
				$data['id'] = $row['id'];
				$data['full_name'] = $row['full_name'];
				$data['user_contact_no'] = $row['contact_number'];
				$data['pet_name'] = $row['pet_name'];
				$data['package_name'] = $row['package_name'];
				$data['payment_price'] = $row['payment_price'];
				if($row['status'] == 1){ 
					$data['status'] = 'Pending';
				}else{ 
					$data['status'] = 'Complete';
				}
				
				$data['Action'] = "<a data-toggle='modal' onclick='deleteorderstatus($row[id])' title='Delete'  class='btn btn-icon-only purple'><i class='fa fa-trash'></i></a><a data-toggle='modal' href='#updateSubscription' onclick='Edit($row[id])' title='Edit'  class='btn btn-icon-only green'><i class='fa fa-pencil'></i></a>";
				$getdata[]=$data;
			}
			$data = $getdata;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Petwalker Order Status Function==============
	//==============GET Dashboard Status Function=============
	
	public function countdashboard(){   
		try{   
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM `services` WHERE 1=1");
			$page->execute();
			$total = $page->rowCount();
			$data['count'] = $total;
			$page1=$con->prepare("SELECT * FROM `training` WHERE 1=1");
			$page1->execute();
			$total1 = $page1->rowCount();
			$data['count1'] = $total1;
			$page2=$con->prepare("SELECT * FROM `s_petwalker_order` WHERE 1=1");
			$page2->execute();
			$total2 = $page2->rowCount();
			$data['count2'] = $total2;
			$page3=$con->prepare("SELECT * FROM `vaccination_order` WHERE 1=1");
			$page3->execute();
			$total3 = $page3->rowCount();
			$data['count3'] = $total3;
			$page4=$con->prepare("SELECT * FROM `user_buy_pet` WHERE 1=1");
			$page4->execute();
			$total4 = $page4->rowCount();
			$data['count4'] = $total4;
			$page5=$con->prepare("SELECT * FROM `pet_mating` WHERE 1=1");
			$page5->execute();
			$total5 = $page5->rowCount();
			$data['count5'] = $total5;
			$page6=$con->prepare("SELECT * FROM `subscription` WHERE 1=1");
			$page6->execute();
			$total6 = $page6->rowCount();
			$data['count6'] = $total6;
			$page7=$con->prepare("SELECT * FROM `user_search` WHERE 1=1");
			$page7->execute();
			$total7 = $page7->rowCount();
			$data['count7'] = $total7;
			$page8=$con->prepare("SELECT * FROM `login` WHERE 1=1");
			$page8->execute();
			$total8 = $page8->rowCount();
			$data['count8'] = $total8;
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e){
			print $e->getMessage();
		}
	}
	
	//===========End Dashboard Status Function==============
	
	//============================================================================================================
	// =================================//START PACKAGE LIST FUNCTION//=====================================
	//============================================================================================================
	
	
	//=============GET PACKAGE Function=============
	
	
	public function get_package(){   
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM packeges");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){  
				$data['id'] = $row['id'];
				$data['packege_name'] = $row['packege_name'];
				$data['valid_days'] = $row['valid_days'];
				$data['per_day_walk'] = $row['per_day_walk'];
				$data['package_price'] = $row['package_price'];
				$data['actual_price'] = $row['actual_price'];
				$data['discription'] = $row['discription'];
				$data['Action'] = "<a data-toggle='modal' href='#updatepackage'  onclick='Edit($row[id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a><a data-toggle='modal' onclick='deletepackage($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	//=============End GET PACKAGE Function=============	
	
	//============= ADD PACKAGE Function ===============
	public function add_package(){   
		try{  
			$con=$this->getConnection(); 
			//print_r($_REQUEST); die;
			$add =isset( $_REQUEST['add']) ? $_REQUEST['add']: '';
			$name =isset( $_REQUEST['name']) ? $_REQUEST['name']:'';
			$price =isset( $_REQUEST['price']) ? $_REQUEST['price']:'';
			$aprice =isset( $_REQUEST['aprice']) ? $_REQUEST['aprice']:'';
			$vdays =isset( $_REQUEST['vdays']) ? $_REQUEST['vdays']:'';
			$pdayswalk =isset( $_REQUEST['pdayswalk']) ? $_REQUEST['pdayswalk']:'';
			$discription =isset( $_REQUEST['discription']) ? $_REQUEST['discription']: '';
			if($name == '' || $price=='' || $aprice==''||$vdays==''||$pdayswalk==''){   
				$data['error'] = "Please fill all field!";
				header('Content-type: application/json');
				echo json_encode($data,JSON_PRETTY_PRINT);
				return false;
			}
			if($add=='add'){  
				$sql = "SELECT * FROM packeges WHERE packege_name=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$cityfetch = $stmt->fetch();
				if($cityfetch != null){ 
					$data['error'] = "Packeges name already exist!"; 
					header('Content-type: application/json');
					echo  json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}else{ 
					$exq="INSERT INTO packeges set packege_name='$name',package_price='$price',actual_price='$aprice',valid_days='$vdays',per_day_walk='$pdayswalk',discription='$discription'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
				}
				if($lastId >0){  
					$data['status']= '1';
				}else{   
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
				
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
	
	//===========End Add PACKAGE Function==============
	
	//===========Veiw PACKAGE list Function============
	
	public function veiw_packege(){   
		try {
			$con=$this->getConnection();
			$editpackage =isset($_POST['editpackage']) ? $_POST['editpackage']: '';
			$view=$con->prepare("SELECT * FROM packeges WHERE id='$editpackage'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch(PDOException $e){ 
			print $e->getMessage();
		}
	}
	
	
	//===========End View PACKAGE Function=================
	
	//============= Update PACKAGE Function ===============
	
	
	public function update_package(){ 
		try{ 
			$con=$this->getConnection();
			$package_id =isset( $_POST['package_id'] ) ? $_POST['package_id']:'';
			$name =isset( $_REQUEST['name']) ? $_REQUEST['name']:'';
			$price =isset( $_REQUEST['price']) ? $_REQUEST['price']:'';
			$aprice =isset( $_REQUEST['aprice']) ? $_REQUEST['aprice']:'';
			$vdays =isset( $_REQUEST['vdays']) ? $_REQUEST['vdays']:'';
			$pdayswalk =isset( $_REQUEST['pdayswalk']) ? $_REQUEST['pdayswalk']:'';
			$discription =isset( $_REQUEST['discription']) ? $_REQUEST['discription']: '';
			if($name == '' || $price=='' || $aprice==''||$vdays==''||$pdayswalk==''){   
				$data['error'] = "Please fill all field!";
				header('Content-type: application/json');
				echo json_encode($data,JSON_PRETTY_PRINT);
				return false;
			}
			$exq="UPDATE packeges set packege_name='$name',package_price='$price',actual_price='$aprice',valid_days='$vdays',per_day_walk='$pdayswalk',discription='$discription' Where id='$package_id'";
			$stmt = $con->prepare($exq);
			$stmt->execute(); 
			if($stmt){
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
	
	
	//===========End Update PACKAGE Function==============
	
	//============= Edit PACKAGE Function ===============
	public function active_packege(){ 
		try{ 
			$con=$this->getConnection();
			$active_trainingtype =isset( $_POST['active_trainingtype'] ) ? $_POST['active_trainingtype']: '';
			if($active_trainingtype>0){ 
				$view=$con->prepare("SELECT * FROM training_type WHERE training_id='$active_trainingtype'");
				$view->execute();
				$dat1 = $view->fetch(PDO::FETCH_ASSOC);
				if($dat1['status_id'] ==1){ 
					$exq="UPDATE training_type set status_id='0' WHERE training_id='$active_trainingtype'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
				}else{  
					$exq="UPDATE training_type set status_id='1' WHERE training_id='$active_trainingtype'";
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
	//===========End Edit PACKAGE  Function==============
	//===========End Delete PACKAGE Function==============
	
	
	public function deletepackage(){   
		try {
			$con=$this->getConnection();
			$deletepackage =isset( $_POST['deletepackage'] ) ? $_POST['deletepackage']: '';
			$view=$con->prepare("DELETE from packeges WHERE id='$deletepackage'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Training no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Delete PACKAGE Function==============
	
	
	//==========================================================================================================
	// ===============================//END PACKAGE LIST FUNCTION//======================================
	//==========================================================================================================
	
	//============================================================================================================
	// =================================//START PACKAGE COUPON LIST FUNCTION//=====================================
	//============================================================================================================
	
	
	//=============GET PACKAGE COUPON Function=============
	
	
	public function get_coupon(){    
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT c.*,u.full_name FROM subscription_coupon as c left join owner_registration as u on u.owner_id=c.user_id");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){  
				$data['id'] = $row['id'];
				$data['full_name'] = $row['full_name'];
				$data['coupon_code'] = $row['coupon_code'];
				$data['validity'] = $row['validity'];
				$data['discount'] = $row['discount'];
				$data['Action'] = "<a data-toggle='modal' href='#updatecoupon'  onclick='Edit($row[id])' title='Edit' class='btn btn-icon-only red'><i class='fa fa-edit'></i></a><a data-toggle='modal' onclick='deletecoupon($row[id])' title='Delete'  class='btn btn-icon-only purple'> <i class='fa fa-trash'></i></a>";
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
	
	//=============End GET PACKAGE COUPON Function=============	
	
	//============= ADD PACKAGE COUPON Function ===============
	public function add_coupon(){   
		try{  
			$con=$this->getConnection(); 
			//print_r($_REQUEST); die;
			$add =isset( $_REQUEST['add']) ? $_REQUEST['add']: '';
			$name =isset( $_REQUEST['name']) ? $_REQUEST['name']:'';
			$price =isset( $_REQUEST['price']) ? $_REQUEST['price']:'';
			$aprice =isset( $_REQUEST['aprice']) ? $_REQUEST['aprice']:'';
			$vdays =isset( $_REQUEST['vdays']) ? $_REQUEST['vdays']:'';
			$pdayswalk =isset( $_REQUEST['pdayswalk']) ? $_REQUEST['pdayswalk']:'';
			$discription =isset( $_REQUEST['discription']) ? $_REQUEST['discription']: '';
			if($name == '' || $price=='' || $aprice==''||$vdays==''||$pdayswalk==''){   
				$data['error'] = "Please fill all field!";
				header('Content-type: application/json');
				echo json_encode($data,JSON_PRETTY_PRINT);
				return false;
			}
			if($add=='add'){  
				$sql = "SELECT * FROM packeges WHERE packege_name=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$name"));
				$cityfetch = $stmt->fetch();
				if($cityfetch != null){ 
					$data['error'] = "Packeges name already exist!"; 
					header('Content-type: application/json');
					echo  json_encode($data,JSON_PRETTY_PRINT);
					return true;
				}else{ 
					$exq="INSERT INTO subscription_coupon set packege_name='$name',package_price='$price',actual_price='$aprice',valid_days='$vdays',per_day_walk='$pdayswalk',discription='$discription'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
				}
				if($lastId >0){  
					$data['status']= '1';
				}else{   
					$data['error']= "Something is missing. Please try again!";
				}
				header('Content-type: application/json');
				echo  json_encode($data,JSON_PRETTY_PRINT);
				return true;
				
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
	
	//===========End Add PACKAGE COUPON Function==============
	
	//===========Veiw PACKAGE COUPON list Function============
	
	public function veiw_coupon(){   
		try {
			$con=$this->getConnection();
			$editcoupon =isset($_POST['editcoupon']) ? $_POST['editcoupon']: '';
			$view=$con->prepare("SELECT * FROM subscription_coupon WHERE id='$editcoupon'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch(PDOException $e){ 
			print $e->getMessage();
		}
	}
	
	
	//===========End View PACKAGE COUPON Function=================
	
	//============= Update PACKAGE COUPON Function ===============
	
	
	public function update_coupon(){ 
		try{ 
			$con=$this->getConnection();
			$coupon_id =isset( $_POST['coupon_id'] ) ? $_POST['coupon_id']:'';
			$coupon_code =isset( $_REQUEST['coupon_code']) ? $_REQUEST['coupon_code']:'';
			$validity =isset( $_REQUEST['validity']) ? $_REQUEST['validity']:'';
			$discount =isset( $_REQUEST['discount']) ? $_REQUEST['discount']:'';
			if($coupon_code == '' || $validity=='' || $discount==''){   
				$data['error'] = "Please fill all field!";
				header('Content-type: application/json');
				echo json_encode($data,JSON_PRETTY_PRINT);
				return false;
			}
			$exq="UPDATE subscription_coupon set coupon_code='$coupon_code',validity='$validity',discount='$discount' Where id='$coupon_id'";
			$stmt = $con->prepare($exq);
			$stmt->execute(); 
			if($stmt){
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
	
	
	//===========End Update PACKAGE COUPON Function==============
	
	
	//===========End Delete PACKAGE COUPON Function==============
	
	
	public function deletecoupon(){   
		try {
			$con=$this->getConnection();
			$deletecoupon =isset( $_POST['deletecoupon'] ) ? $_POST['deletecoupon']: '';
			$view=$con->prepare("DELETE from subscription_coupon WHERE id='$deletecoupon'");
			$view->execute();
			if($view == true){
				$data='1';
			}else{
				$data='Training no delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {  
			print $e->getMessage();
		}
	}
	//===========End Delete PACKAGE COUPON Function==============
	
	
	//==========================================================================================================
	// ===============================//END PACKAGE COUPON LIST FUNCTION//======================================
	//==========================================================================================================
	
	//============================================================================================================
	//========================================//START TIMING FUNCTION//===========================================
	//============================================================================================================
	
	
	//=============GET TIMING Function=============
	
	public function get_timing(){
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT st.*,sd.days FROM `s_timing` as st left join s_days as sd on sd.id=st.day_id order by st.id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$total = $page->rowCount();
			foreach($sportfetch as $row){ 
				$data['s_id'] = $row['id'];
				$data['days'] = $row['days'];
				if($row['slot'] ==1){
					$data['Slot'] = 'Morning';
				}else{
					$data['Slot'] = 'Evening';
				}
				$data['timing'] = $row['timing'];
				if($row['status']==1){
					$data['Status'] = 'Active';
				}else{ 
					$data['Status'] = 'DeActive';
				}
				$data['Delete'] = "<a data-toggle='modal' onclick='deletetiming($row[id])' title='Delete'  class='btn btn-icon-only purple'><i class='fa fa-trash'></i></a>";
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
	
	//=============End GET TIMING Function=============
	//=============GET Days Function=============
	public function get_days(){    
		try {
			$con=$this->getConnection();
			$view=$con->prepare("SELECT * FROM s_days");
			$view->execute();
			$data = $view->fetchAll(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	//=============End GET TIMING Function=============
	
	
	
	//============= ADD TIMING Function ===============
	public function add_timing(){ 
		try{ 
			$con=$this->getConnection();
			$add =isset( $_POST['add'] ) ? $_POST['add']: '';
			$days =isset( $_POST['days']) ? $_POST['days']: '';
			$slot =isset( $_POST['slot']) ? $_POST['slot']: '';
			$opentime =isset( $_POST['opentime']) ? $_POST['opentime']: '';
			$closetime =isset( $_POST['closetime']) ? $_POST['closetime']: '';
			$time = $opentime.'-'.$closetime;
			if($add=='add' || $time !=''){  
				if($days == ''){    
					$data['error'] = "Please fill Days!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$sql = "SELECT * FROM s_timing WHERE day_id=? and  slot=?";
				$stmt = $con->prepare($sql);
				$stmt->execute(array("$days","$slot"));
				$daysfetch = $stmt->fetch();
				if($daysfetch != null){  
					$data['error'] = "Days/Slot already exist!"; 
				}else{ 
					$exq="INSERT INTO s_timing set day_id=$days,slot='$slot',timing='$time'";
					$stmt = $con->prepare($exq);
					$stmt->execute(); 
					$lastId = $con->lastInsertId();
					if($lastId>0)
					{
						$data['status']= '1';
					}else{ 
						$data['error']= "Something is missing. Please try again!";
					}
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
	
	//===========End Add TIMING Function==============
	
	
	//===========Veiw TIMING list Function============
	
	
	public function veiw_timing(){   
		try {
			$con=$this->getConnection();
			$editcity =isset( $_POST['editcity'] ) ? $_POST['editcity']: '';
			$view=$con->prepare("SELECT * FROM city_list WHERE city_id='$editcity'");
			$view->execute();
			$data = $view->fetch(PDO::FETCH_ASSOC);
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	
	
	//===========End View TIMING Function=================
	
	
	//============= Update TIMING Function ===============
	
	
	public function update_timing(){ 
		try{ 
			$con=$this->getConnection();
			$city_id =isset( $_POST['city_id'] ) ? $_POST['city_id']: '';
			$name =isset( $_POST['name']) ? $_POST['name']: '';
			//$state_id =isset( $_POST['state_id']) ? $_POST['state_id']: '';
			if($city_id>0)
			{
				if($name == '')
				{ 
					$data['error'] = "Please fill city name!";
					header('Content-type: application/json');
					echo json_encode($data,JSON_PRETTY_PRINT);
					return false;
				}
				$exp="update  city_list set country_id='1',city_name='$name' where city_id='$city_id'";
				$stmt = $con->prepare($exp);
				$stmt->execute(); 
				if($stmt)
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
	
	
	//===========End Update TIMING Function==============
	
	
	
	//===========End Delete TIMING Function==============
	
	
	public function deletetiming(){   
		try {
			$con=$this->getConnection();
			$deletetiming =isset( $_POST['deletetiming'] ) ? $_POST['deletetiming']: '';
			$view=$con->prepare("DELETE from s_timing WHERE id='$deletetiming'");
			$view->execute();
			if($view == true){  
				$data='1';
			}else{ 
				$data='Timing slot not delete in record. Please try again!';
			}
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) { 
			print $e->getMessage();
		}
	}
	//===========End Delete TIMING Function==============
	//=============End GET Subscription Function=============
	public function edit_subscription(){   
		try {
			$con=$this->getConnection();
			$order_id =isset( $_POST['order_id'] ) ? $_POST['order_id']: '';
			$page=$con->prepare("SELECT * FROM `subscription` where id='$order_id'");
			$page->execute();
			$data = $page->fetch(PDO::FETCH_ASSOC);
			$package_id = $data['package_id'];
			$page1=$con->prepare("SELECT * FROM `packeges` where id='$package_id'");
			$page1->execute();
			$data1 = $page1->fetch(PDO::FETCH_ASSOC);
			$data['t_id'] = $order_id;
			$data['type'] = $data1['packege_name'];
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	public function get_packeges(){   
		try {
			$con=$this->getConnection();
			$page=$con->prepare("SELECT * FROM `packeges`");
			$page->execute();
			$data = $page->fetchAll();
			header('Content-type: application/json');
			echo  json_encode($data,JSON_PRETTY_PRINT);
			return true;
		}catch (PDOException $e) {
			print $e->getMessage();
		}
	}
	//===========Start Accept Subscription Function==============
	public function update_subscriptionlist(){  
		try{ 
			$con=$this->getConnection();
			$id =isset( $_POST['id'] ) ? $_POST['id']: '';
			$duration =isset( $_POST['duration']) ? $_POST['duration']: '';
			$payment_price =isset( $_POST['payment_price']) ? $_POST['payment_price']: '';
			$payment_mode =isset( $_POST['payment_mode']) ? $_POST['payment_mode']: '';
			$status =isset( $_POST['status']) ? $_POST['status']: '';
			$start_date =isset( $_POST['start_date']) ? $_POST['start_date']: '';
			$end_date =isset( $_POST['end_date']) ? $_POST['end_date']: '';
			$package_id =isset( $_POST['package_id']) ? $_POST['package_id']: '';
			if($duration == '' || $payment_price=='' || $payment_mode==''||$status==''||$start_date==''||$end_date==''){   
				$data['error'] = "Please fill all field!";
				header('Content-type: application/json'); 
				echo json_encode($data,JSON_PRETTY_PRINT);
				return false;
			}
			$exq="UPDATE subscription set duration='$duration',package_id='$package_id',payment_price='$payment_price',payment_mode='$payment_mode',status='$status',start_date='$start_date',end_date='$end_date'where id='$id'";
			$stmt = $con->prepare($exq);
			$stmt->execute(); 
			if($stmt)
			{
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
	
	//=============================================================================================================
	// =========================================//END TIMING FUNCTION//==============================================
	//=============================================================================================================
	
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