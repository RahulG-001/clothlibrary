
<?php include('header.php'); ?>
<?php include('config.php'); ?>

<!-- Banner Start-->
<style>
  * {
    padding: 0;
    margin: 0;
  }
  .fit { /* set relative picture size */
    max-width: 100%;
    max-height: 100%;
  }
  .center {
    display: block;
    margin: auto;
  }
</style>
<div class="banner ab">
	<div id="myCarousel" class="carousel slide" data-ride="carousel">
		<div class="carousel-inner">
			<?php 
				$page=$conn->prepare("SELECT * FROM cl_testimonial WHERE status='1' order by id desc");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$s = 1;				
				foreach($sportfetch as $row){
			?>
				<div class="item <?php if($s==1){echo 'active';}else{ echo '';}?>">
					<img src="admin/lib/image/<?php echo $row['image'];?>" alt="Los Angeles" style="width:100%;">
				</div>
			 <?php $s++; } ?>
		 
		</div>
		<a class="left carousel-control" href="#myCarousel" data-slide="prev">
		  <span class="glyphicon glyphicon-chevron-left"></span>
		  <span class="sr-only">Previous</span>
		</a>
		<a class="right carousel-control" href="#myCarousel" data-slide="next">
		  <span class="glyphicon glyphicon-chevron-right"></span>
		  <span class="sr-only">Next</span>
		</a>
	  </div>
</div>
<div class="banner ab1">
    <div id="myCarousel" class="carousel slide" data-ride="carousel">
		<div class="carousel-inner">
			<?php 
				$page=$conn->prepare("SELECT * FROM cl_testimonial WHERE status='1' order by id desc");
				$page->execute();
				$sportfetch = $page->fetchAll();
				$s = 1;				
				foreach($sportfetch as $row){
			?>
				<div class="item <?php if($s==1){echo 'active';}else{ echo '';}?>">
					<img src="admin/lib/image/<?php echo $row['image1'];?>" alt="Los Angeles" style="width:100%;">
				</div>
			 <?php $s++; } ?>
		 
		</div>
		<a class="left carousel-control" href="#myCarousel" data-slide="prev">
		  <span class="glyphicon glyphicon-chevron-left"></span>
		  <span class="sr-only">Previous</span>
		</a>
		<a class="right carousel-control" href="#myCarousel" data-slide="next">
		  <span class="glyphicon glyphicon-chevron-right"></span>
		  <span class="sr-only">Next</span>
		</a>
	  </div>
</div>
<!-- Banner End-->
<div class="special-service">
    <div class="container">
        <div class="row">
            <div class="col-lg-6  col-md-4 col-sm-6 col-xs-12">
				<p>
				    <img src="img/Aboutus1.jpg" alt="special-service-img" class="image img-responsive">
					<div class="middle1">
						<h1 class="about1">About Us</h1>
						<p class="about2">Imagine yourself to be one of the crusaders of the multifaceted fashion world – how would you like to present your end product to your clientele? Exclusive and extraordinary. Yes, everyone with the unique fashion sense would like that. But economy is not an easy game, and in the un-innocent world of cutting-edge competition, you end up compromising on product quality.</p>
					</div>
					<div class="middle">
						<div class="text"><a href="about.php" class="btn1 btn-fill1">Read more</a></div>
				    </div>
				</p>
            </div>
            <div class="col-lg-6 col-md-8 col-sm-6 col-xs-12 special-service-img">
                <img src="img/Aboutus2.jpg" alt="special-service-img" class="img-responsive" style="padding-right: 5%;">
            </div>
        </div>
    </div>
</div>
<!-- Recent Rooms Start-->
<div class="favorite-rooms content-area clearfix ab">
	<h1 class="collection">Our Collection</h1>
	<div class="row" style="margin-top: 34px; margin-right:0px !important;">
	<?php 
		$page=$conn->prepare("SELECT * FROM cl_collection WHERE status='1' order by id DESC");
		$page->execute();
		$sportfetch = $page->fetchAll();
		$s = 1;				
		foreach($sportfetch as $row){
	?>
			<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12" style="padding-right: 0px;">
				<img class="img_collection" src="admin/lib/image/<?php echo $row['image'];?>">
				<a href="cloth.php">
					<div class="img__wrap middle2">
						<div class="img__description about2">
							<p style="padding-top:30%;"><i><?php echo $row['disc'];?></i></p>
						</div>
					</div>
				</a>
			</div>
		<?php $s++; } ?>
	</div>
</div>
<div class="favorite-rooms content-area clearfix ab1">
	<h1 class="collection">Our Collection</h1>
	<div class="container">
		<div class="row" style="margin-top: 34px;">
		<?php 
			$page=$conn->prepare("SELECT * FROM cl_collection WHERE status='1' order by id DESC");
			$page->execute();
			$sportfetch = $page->fetchAll();
			$s = 1;				
			foreach($sportfetch as $row){
		?>
				<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12" style="margin-bottom:10px;">
					<img class="img_collection" src="admin/lib/image/<?php echo $row['image'];?>">
					<a href="cloth.php">
						<div class="img__wrap middle2">
							<div class="img__description about2">
								<p style="padding-top:30%;"><i><?php echo $row['disc'];?></i></p>
							</div>
						</div>
					</a>
				</div>
			<?php $s++; } ?>
		</div>
	</div>
</div>
<div class="favorite-rooms content-area clearfix">
	<h1 class="showroom">Showroom</h1>
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<h1 class="showroom1"><p class="showroomp3"><i style="font-size:30px; color:#865c5e;" class="fa fa-map-marker" aria-hidden="true"></i>&nbsp;Lajpat Nagar<br><span class="addressshow">E-25,L.G.F. Lajpat Nagar-2 Delhi 110024</span></p></h1>
			<iframe class="map1" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?q=E-25,L.G.F.+LAJPAT+NAGAR-2+DELHI+110023&key=AIzaSyCFCk5ZCXwcADaO2_6hZDahVJ0wXQkaTD8" allowfullscreen></iframe>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<h1 class="showroom1"><p class="showroomp3"><i style="font-size:30px; color:#865c5e;" class="fa fa-map-marker" aria-hidden="true"></i>&nbsp;Shahpur Jat<br><span class="addressshow">5L, First Floor, Dada Jungi House, Lane no.1 New Delhi 110049</span></p></h1>
			<iframe class="map1" frameborder="0" style="border:0" src="https://www.google.com/maps/embed/v1/place?q=Dada%20Jungi%20House%2C%20Lane%20no.1%20New%20Delhi%20110049&key=AIzaSyCFCk5ZCXwcADaO2_6hZDahVJ0wXQkaTD8" allowfullscreen></iframe>
		</div>
	</div>
</div>
<?php include('footer.php');?>
<script src="https://code.jquery.com/jquery-latest.js"></script>
<script type="text/javascript" language="JavaScript">
  function set_body_height() { // set body height = window height
    $('body').height($(window).height());
  }
  $(document).ready(function() {
    $(window).bind('resize', set_body_height);
    set_body_height();
  });
</script>