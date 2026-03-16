<?php include("pages/header.php");?>
<!-- BEGIN PAGE CONTENT-->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
<script>
	$(document).ready(function(){ 
	$("#show").show();
		GetAllData();
	});
	var table="";
	function GetAllData()
	{ 
		$.ajax({
			url: "lib/library.php?action=get_Project",
			type: "GET",
			success: function (data){ //alert(JSON.stringify(data));
				$("#show").hide();
				table= $('#Projects').DataTable({  
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
							exportOptions:{ 
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
						{ title:"Showroom Name", data: "title" },
						{ title:"Image", data: "image" },
						{ title:"Edit", data: "Edit" },
						{ title:"Delete", data: "Delete" },
					]
				});
				table.on('order.dt search.dt', function () {
					table.column(0, { search: 'applied', order: 'applied' }).nodes().each(function (cell, i) {
						cell.innerHTML = i + 1;
					});
				}).draw();
			},
			error:function(){  
				alert("Error! Record not found!!");
			}
		});
	}
	function submitform(){ 
		$("#addform").submit(function(event) { 
			$("#show2").show();
			var abc = event.preventDefault();
			event.stopImmediatePropagation();
			$.ajax({
				url: 'lib/library.php?action=add_Project',
				type:'POST',
				data: new FormData(this),
				cache:false,
				contentType:false,
				processData:false,
				success:function(data)
				{   //alert(JSON.stringify(data));
					$("#show2").hide();
					if(data.status  == 1 ){
						$('#addform' ).each(function(){
							this.reset();
						});
						$("#msg").show();
						$("#msg1").html('YOUR RECORD HAS BEEN SUCCESSFULLY ADDED');
						setTimeout(function(){
							$("#msg").hide();
						},3000);
						table.destroy();
						GetAllData();
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
	}
	function updateform(){
		$("#editform").submit(function(event) { 		
			$("#show2").show();
			var abc = event.preventDefault();
			event.stopImmediatePropagation();
			$.ajax({
				url: 'lib/library.php?action=edit_Project',
				type:'POST',
				data: new FormData(this),
				cache:false,
				contentType:false,
				processData:false,
				success:function(data)
				{   //alert(JSON.stringify(data));
					$("#show2").hide();
					if(data.status  == 1 ){ //alert(JSON.stringify(data));
						$('#editform' ).each(function(){
							this.reset();
						});
						$("#msg2").show();
						$("#msg3").html('YOUR IMAGE HAS BEEN SUCCESSFULLY SAVE');
						setTimeout(function(){
							$("#msg2").hide();
						},3000);
						table.destroy();
						GetAllData();
					}else{
						$("#msg2").show();
						$("#msg3").html(data.error);
						setTimeout(function(){
							$("#msg2").hide();
						},3000);
					}
					
				}
			});
		});
	}
	function Edit(data){
		$.ajax({
			type:"POST",
			url:"lib/library.php?action=veiw_Project",
			data:{veiw_Project:data},
			success:function(data1)
			{ //alert(JSON.stringify(data1));
				$("#Projects_id").val(data1.id);
				$("#name").val(data1.name);
				$("#title").val(data1.title);
				$("#disc").val(data1.disc);
				$("#image").html('<img src="lib/image/'+data1.image+'" hieght="140px" width="240px">');
				//$("#large1").modal("show");
			}
		  });
	}
	function active(data){
		$.ajax({
			type:"POST",
			url:"lib/library.php?action=active_Projects",
			data:{active_Projects:data},
			success:function(data1)
			{ 
				table.destroy();
				GetAllData();
			}
		  });
	}
	function deleteProject(data){
		var agree = confirm("Are you sure! you want to delete it");
		if(agree){
			$.ajax({
				type:"POST",
				url:"lib/library.php?action=deleteProject",
				data:{deleteProject:data},
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
<div class="row">
	<div class="col-md-12">
		<!-- BEGIN EXAMPLE TABLE PORTLET-->
		<div class="portlet box grey-cascade">
			<div class="portlet-title">
				<div class="caption">
					<i class="fa fa-globe"></i>Showroom List
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
							<div class="btn-group">
								<button id="sample_editable_1_new" data-toggle="modal" href="#large" class="btn green">
								Add New <i class="fa fa-plus"></i>
								</button>
							</div>
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
						<table class="table table-striped table-bordered table-hover" id="Projects">
							
						</table>
					</div>
				</div>
			</div>
			<!-------------modal start-------------->
			<div class="modal fade" id="large" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header" style="background:#95A5A6; padding-bottom: 7px; padding-top: 7px; color:#ffffff;">
							<button type="button" class="close btn green" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title"><b>Add Showroom</b></h4>
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
											<div class="col-md-12">
												
												<div class="col-md-6">
													<div class="form-group">
														<div class="input-group">
															<select name="title" class="form-control" >
																<option value="">Select Showroom</option>
																<option value="lajpat_nagar">Lajpat Nagar</option>
																<option value="shahpur_jat">Shahpur Jat</option>
															</select> 
														</div>
													</div>
												</div>
												<br>
												<div class="col-md-6">
													<div class="form-group">
														<p style="color:red;">The width of Banner should be 1920*1000 Pixels</p>
														<label>&nbsp;&nbsp;Projects Image</label>
														<div class="input-group">
															<span id="image1"></span>&nbsp;
															<span class="btn green fileinput-button">
																<i class="fa fa-plus"></i>
																<input type="file" name="image1" style="background: transparent;border: none;outline: none;" value = "Add files..." multiple>Edit files...
															</span>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="modal-footer">
									<input type="hidden" name="add" value="add">
									<button type="button" class="btn default" data-dismiss="modal">Close</button>
									<input type="submit" onclick="submitform()" class="btn blue" value="Add">
									<div id="show2"style="padding-left: 65%; display:none;margin-right: 10px;float:left"><img src="loader.gif" height="30px"/></div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
			<!-----------Update breed Modal Start---------------->
			<div class="modal fade" id="updateProject" tabindex="-1" role="basic" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
							<h4 class="modal-title"><b>Update Projects</b></h4>
						</div>
						<div id="msg2" style="display:none;"><div class='alert alert-success fade in block-inner'>
							<button type='button' class='close' data-dismiss='alert'>×</button>
							<i class='icon-cancel-circle'></i><span id="msg3"></span></div>
						</div>
						<div class="modal-body">
							<form role="form" name="myform" method="POST" action="" id="editform">
								<div class="modal-body">
									<div class="form-body">
										<div class="row">
											<div class="col-md-12">
												
												<div class="col-md-6">
													<div class="form-group">
														<div class="input-group">
															<select name="title" class="form-control" >
																<option value="">Select Showroom</option>
																<option value="lajpat_nagar">Lajpat Nagar</option>
																<option value="shahpur_jat">Shahpur Jat</option>
															</select>
														</div>
													</div>
												</div>
												<div class="col-md-6">
													<div class="form-group">
													<p style="color:red;">The width of Banner should be 1920*1000 Pixels</p>
														<label>&nbsp;&nbsp;Showroom Image</label>
														<div class="input-group">
															<span id="image1"></span>&nbsp;
															<span class="btn green fileinput-button">
																<i class="fa fa-plus"></i>
																<input type="file" name="image1" style="   background: transparent;border: none;outline: none;" value = "Add files..." multiple>Edit files...
															</span>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="modal-footer">
									<input type="hidden" id="Projects_id" name="Projects_id">
									<button type="button" class="btn default" data-dismiss="modal">Close</button>
									<input type="submit" onclick="updateform()" class="btn blue" value="Update">
									<div id="show1"style="padding-left: 65%; display:none;margin-right: 10px;float:left"><img src="loader.gif" height="30px"/></div>
								</div>
							</form>
						</div>
						
					</div>
				</div>
			</div><!-----------Update breed Modal End---------------->
		</div>
		<!-- END EXAMPLE TABLE PORTLET-->
	</div>
</div>
<!-- END PAGE CONTENT-->

<script id="template-upload" type="text/x-tmpl">
	{% for (var i=0, file; file=o.files[i]; i++) { %}
		<tr class="template-upload fade">
			<td>
				<span class="preview"></span>
			</td>
			<td>
				<p class="name">{%=file.name%}</p>
				<strong class="error text-danger label label-danger"></strong>
			</td>
			<td>
				<p class="size">Processing...</p>
				<div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
				<div class="progress-bar progress-bar-success" style="width:0%;"></div>
				</div>
			</td>
			<td>
				{% if (!i) { %}
					<button class="btn red cancel">
						<i class="fa fa-ban"></i>
						<span>Cancel</span>
					</button>
				{% } %}
			</td>
		</tr>
	{% } %}
	</script>
	<!-- The template to display files available for download -->
	<script id="template-download" type="text/x-tmpl">
			{% for (var i=0, file; file=o.files[i]; i++) { %}
				<tr class="template-download fade">
					<td>
						<span class="preview">
							{% if (file.thumbnailUrl) { %}
								<a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
							{% } %}
						</span>
					</td>
					<td>
						<p class="name">
							{% if (file.url) { %}
								<a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
							{% } else { %}
								<span>{%=file.name%}</span>
							{% } %}
						</p>
						{% if (file.error) { %}
							<div><span class="label label-danger">Error</span> {%=file.error%}</div>
						{% } %}
					</td>
					<td>
						<span class="size">{%=o.formatFileSize(file.size)%}</span>
					</td>
					<td>
						{% if (file.deleteUrl) { %}
							<button class="btn red delete btn-sm" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
								<i class="fa fa-trash-o"></i>
								<span>Delete</span>
							</button>
							<input type="checkbox" name="delete" value="1" class="toggle">
						{% } else { %}
							<button class="btn yellow cancel btn-sm">
								<i class="fa fa-ban"></i>
								<span>Cancel</span>
							</button>
						{% } %}
					</td>
				</tr>
			{% } %}
		</script>
	<!-- BEGIN CORE PLUGINS -->
	<script>
		$(document).ready(function(){		
			 FormFileUpload.init();
		});
	</script>
<?php include("pages/footer.php");?>