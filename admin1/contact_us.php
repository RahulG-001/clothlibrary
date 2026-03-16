<?php include("pages/header.php");?>
<link href="assets/admin/pages/css/profile-old.css" rel="stylesheet" type="text/css" />
<!-- BEGIN PAGE CONTENT-->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
<script>
	$(document).ready(function(){ 
	$("#show").show();
		GetAllData();
	});
	var table="";
	var table1="";
	function GetAllData(){ 
		$.ajax({  
			url: "lib/library.php?action=get_User",
			type: "GET",
			success: function (data){ 
				$("#show").hide();
				table= $('#User').DataTable({
					data: data,
					"processing": true,
					"columnDefs":[{
						"searchable": false,
						"orderable": false,
						"targets": 0
					}],
					dom: 'Bfrtip',
					buttons:[
						{
							extend: 'excel',
							exportOptions: {
								columns: [1,2]
							}
						},
						{
							extend: 'csv',
							exportOptions: {
								columns: [1,2]
							}
						},
						{
							extend: 'pdf',
							exportOptions: {
								columns: [1,2]
							}
						},
						{
							extend: 'print',
							exportOptions: {
								columns: [1,2]
							}
						},
					],
					columns:[
						{ title:"S.No", data: null },
						{ title:"User Name", data: "user_name"},
						{ title:"Email ID", data: "email_id"},
						{ title:"Phone No", data: "phone_no"},
						{ title:"Massage", data: "massage"},
						{ title:"Action", data: "Action" },
					]
				});
				table.on('order.dt search.dt', function () {
					table.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
						cell.innerHTML = i + 1;
					});
				}).draw();
			},
			error:function(){  
				alert("Error! Recod not found!!");
			}
		});
		categorylist();
		breedlist();
		size();
	}
	/* function submitform(){  
		$("#addform").submit(function(event) { 
			$("#show2").show();
			var abc = event.preventDefault();
			event.stopImmediatePropagation();
			$.ajax({
				url: 'lib/library.php?action=add_User',
				type:'POST',
				data: new FormData(this),
				cache:false,
				contentType:false,
				processData:false,
				success:function(data)
				{   //alert(JSON.stringify(data));
					$("#show2").hide();
					if(data.status  == 1 ){
						$("#msg1").html('YOUR RECORD HAS BEEN SUCCESSFULLY ADDED');
						setTimeout(function(){
							$("#msg").hide();
						},3000);
						window.location = "userlist.php";
					}else{
						$("#msg").show();
						$("#msg1").html(data.error);
						setTimeout(function(){
							$("#msg2").hide();
						},3000);
					}
					
				}
			});
		});
	} */
	function updateform(){  //alert('hii');
		$("#updateform").submit(function(event) { 
			$("#show2").show();
			var abc = event.preventDefault();
			event.stopImmediatePropagation();
			$.ajax({
				url: 'lib/library.php?action=update_User',
				type:'POST',
				data: new FormData(this),
				cache:false,
				contentType:false,
				processData:false,
				success:function(data)
				{  // alert(JSON.stringify(data));
					$("#show2").hide();
					if(data.status  == 1 ){
						$("#msg4").html('YOUR RECORD HAS BEEN SUCCESSFULLY ADDED');
						setTimeout(function(){
							$("#msg3").hide();
						},3000);
						window.location = "contact_us.php";
					}else{
						$("#msg3").show();
						$("#msg4").html(data.error);
						setTimeout(function(){
							$("#msg4").hide();
						},3000);
					}
					
				}
			});
		});
	}
	function View(data){ //alert(data);
		$.ajax({
			type:"POST",
			url:"lib/library.php?action=veiw_User",
			data:{viewUser:data},
			success:function(data1)
			{   //alert(JSON.stringify(data1));
				$("#username").val(data1.user_name);
				$("#email_id").val(data1.email_id);
				$("#phone_no").val(data1.phone_no);
				$("#massage").val(data1.massage);
				$("#ids").val(data1.id);
			}
		});
	}
	function deleteUser(data){
		var agree = confirm("Are you sure! you want to delete it");
		if(agree){
			$.ajax({
				type:"POST",
				url:"lib/library.php?action=deleteUser",
				data:{deleteUser:data},
				success:function(data1)
				{
				  if(data1==1)
				  {
					alert('Record has been successfully deleted !...');  
					table.destroy();
					GetAllData();
				  }else{
					alert('Please try again !...');         
				  }
				}
			});
		}
	}

</script>
<style>
.color{color:green;text:bold;}
	.UserOptionMainDiv{
		background: rgba(247, 245, 245, 0.94);
		width: 100%;
		float: left;
		padding: 20px 10px;
		font-weight: 600;
		

	}
	.UserOptionListItems{
		border: 1px solid rgba(204, 204, 204, 0.3);
		border-radius: 4px;
		margin-bottom: 10px;
		width: 100%;
		float: left;
		background: #ffffff;
		padding: 8px 5px;
	}
	.listItem{
		text-align:center;
		padding-bottom: 10px;
		color:#b8860b;
	}
	.listValue{
		text-align:center;
		font-weight: 600;
		font-size: 16px;
	}
	.chHeading{
		text-transform: uppercase;
		font-weight: bold;
		padding-bottom: 10px;
		font-size: 15px;
	}
	.modal-header{
		background: #2d99b1;
	}
	.modal-header h4{
		text-transform: uppercase;
		font-weight: bold;
		color: #fff;
	}
	.close{
		width:16px;
		height:16px;
		opacity:1;
	}
	
	
	
</style>
<div class="row">
	<div class="col-md-12">
		<!-- BEGIN EXAMPLE TABLE PORTLET-->
		<div class="portlet box grey-cascade">
			<div class="portlet-title">
				<div class="caption"style="text-transform: uppercase;font-weight: bold;">
					<i class="fa fa-globe"></i>Contact Us List
				</div>
				<div class="tools">
					<a href="javascript:;" class="reload">
					</a>
				</div>
			</div>
			<div class="portlet-body">
				<div class="table-toolbar">
					<div class="row">
						<div class="col-md-6">
							<!--<div class="btn-group">
								<button id="sample_editable_1_new" data-toggle="modal" href="#large" class="btn green">
								Add New <i class="fa fa-plus"></i>
								</button>
							</div> -->
						</div>
						<div class="col-md-6">
							<div class="btn-group pull-right">
								<button class="btn dropdown-toggle" data-toggle="dropdown">Tools <i class="fa fa-angle-down"></i>
								</button>
								<ul class="dropdown-menu pull-right">
									<li onclick="javascript:window.print();">
										<a href="javascript:;">
										Print </a>
									</li>
									<li>
										<a href="javascript:;">
										Export to Excel </a>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
				<div id="sample_editable_1_wrapper" class="dataTables_wrapper no-footer">
					<div class="row">
						<div class="col-md-6 col-sm-12"></div>
						<div class="col-md-6 col-sm-12"></div>
					</div>

					<div class="table-scrollable">
						<table class="table table-striped table-bordered table-hover" id="User">
							
						</table>
					</div>
				</div>
			</div>
			<div class="modal fade" id="large" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Add User</h4>
						</div>
						<div id="msg" style="display:none;"><div class='alert alert-success fade in block-inner'>
							<button type='button' class='close' data-dismiss='alert'>×</button>
							<i class='icon-cancel-circle'></i><span id="msg1"></span></div>
						</div>
						<div class="modal-body">
							<form role="form" name="myform" method="POST" action="" id="addform" enctype="multipart/form-data">
								<div class="modal-body">
									<div class="form-body">
										<div class="row">
											<div class="col-md-6">
												<div class="form-group">
													<input type="text" name="username" class="form-control" placeholder="User Name" required>
												</div>
											</div>
											<div class="col-md-6">
												<div class="form-group">
													<input type="email" name="email_id" class="form-control" placeholder="Email ID" required>
												</div>
											</div>
											<div class="col-md-6">
												<div class="form-group">
													<input type="password" name="password" class="form-control" placeholder="Password" required>
												</div>
											</div>
											<div class="col-md-6">
												<div class="form-group">
													<input type="number" name="phone_no" class="form-control" placeholder="Phone No" required>
												</div>
											</div>
											<div class="col-md-6">
												<div class="form-group">
													<input type="text" name="address" class="form-control" placeholder="Address" required>
												</div>
											</div>
											<div class="col-md-6">
												<p>Profile Image</p>
												<div class="form-group">
													<input type="file" name="images" value="" class="form-control">
												</div>
											</div>
											
											
										</div>
									</div>
								</div>
								<div class="modal-footer">
									<input type="hidden" name="add" value="add">
									<button type="button" class="btn default" data-dismiss="modal">Close</button>
									<input type="submit" onclick="submitform()" class="btn blue" value="Save">
									<div id="show2"style="padding-left: 65%; display:none;margin-right: 10px;float:left"><img src="loader.gif" height="30px"/></div>
								</div>
							</form>
						</div>
						
					</div>
				</div>
			</div>
			
			<div class="modal fade" id="viewUser" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog modal-lg">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title">Update Contact Us</h4>
						</div>
						<div class="modal-body">
							<div id="msg3" style="display:none;"><div class='alert alert-success fade in block-inner'>
								<button type='button' class='close' data-dismiss='alert'>×</button>
								<i class='icon-cancel-circle'></i><span id="msg4"></span></div>
							</div>
							<form role="form" name="myform" method="POST" action="" id="updateform" enctype="multipart/form-data">
								<div class="modal-body">
									<div class="form-body">
										<div class="row">
											<div class="col-md-4">
												<p>User Name</p>
												<div class="form-group">
													<input type="text" name="username" id="username" class="form-control" placeholder="User Name">
												</div>
											</div>
											<div class="col-md-4">
												<p>Email ID</p>
												<div class="form-group">
													<input type="email" name="email_id" id="email_id" class="form-control" placeholder="Email ID">
												</div>
											</div>
											<div class="col-md-4">
												<p>Phone No</p>
												<div class="form-group">
													<input type="number" name="phone_no" id="phone_no" class="form-control" placeholder="Phone No">
												</div>
											</div>
											<div class="col-md-12">
												<p>Massage</p>
												<div class="form-group">
													<input type="textarea" name="massage" id="massage" class="form-control" placeholder="Massage">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="modal-footer">
									<input type="hidden" name="id" id="ids" value="">
									<button type="button" class="btn default" data-dismiss="modal">Close</button>
									<input type="submit" onclick="updateform()" class="btn blue" value="Save">
									<div id="show2"style="padding-left: 65%; display:none;margin-right: 10px;float:left"><img src="loader.gif" height="30px"/></div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div><!-----------Update User Modal End---------------->
		</div>
		<!-- END EXAMPLE TABLE PORTLET-->
	</div>
</div>
<!-- END PAGE CONTENT-->
<?php include("pages/footer.php");?>