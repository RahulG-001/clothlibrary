<?php include('header.php');?>
<?php include('config.php'); ?>
<!-- Banner Start-->
<script>
	$( document ).ready(function() {
		$('#shahpur').show();
	});
	
	function shahpur(){ 
		$('.lajpat').hide();
		$('#lajpat').hide();
		$('.shahpur').show();
		$('#shahpur').show();
	}
</script>
<h1 class="showroom">Showroom</h1>
<div class="banner">
  <img src="img/1.jpg"  alt="" width="100%" style="margin-top:50px;">
</div>
<div class="favorite-rooms content-area clearfix">
	<div class="col-lg-4 col-md-4"></div>
	<div class="col-lg-4 col-md-4 col-sm-6 col-xs-12" onclick="shahpur()" style="padding-left: 0px !important; padding-right: 0px !important;">
		<h1 class=""><p class="showroomp" style="cursor: pointer;"><i style="font-size:25px; color:#865c5e;" class="fa fa-map-marker" aria-hidden="true"></i>&nbsp;SHAHPUR JAT</p><hr class=""></h1>
	</div>
	<div class="col-lg-4 col-md-4"></div>
</div>
<div id="shahpur">
	<div class="favorite-rooms content-area clearfix">
		<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12" style="padding-left: 0px !important; padding-right: 0px !important;">
			<img src="img/shahpur.jpg"  alt="" width="100%" >
		</div>
		<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12" style="padding-left: 0px !important; padding-right: 0px !important;">
			<p class="lajpat1">SHAHPUR JAT</p>
			<p class="lajpat2" style="text-transform: uppercase;">5L, First Floor, Dada Jungi House, <br>Lane no.1 New Delhi 110049<p>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12" style="padding-left: 0px !important; padding-right: 0px !important;">
			<img src="img/shahpur_right_image.jpg"  alt="" width="100%">
		</div>
	</div>
	<div class="favorite-rooms content-area clearfix">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<?php 
				$page=$conn->prepare("SELECT * FROM cl_projects WHERE status='1' order by id DESC");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$s = 1;	
				foreach($sportfetch as $row){
			?>
					<div class="col-md-6 col-sm-12 col-xs-12" style="padding-top:15px !important; padding-bottom:15px !important;">
						<img src="admin/lib/image/<?php echo $row['image'];?>" alt="" width="100%">
					</div>
			<?php $s++; } ?>
		</div>
	</div>
</div>
<?php include('footer.php');?>