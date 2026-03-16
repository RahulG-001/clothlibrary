<?php include('header.php'); ?>
<?php include('config.php'); ?>
<!-- Banner Start-->
<div class="banner">
    <img src="img/Home_1.png"  alt="" width="100%">
</div>
<!-- Recent Rooms Start-->
<div class="favorite-rooms content-area clearfix">
	<h1 class="cloth">"Every Fabric Tells a Story"</h1>
	<div class="container">
		<div class="row">
			<p class="clothp">Fabrics made by The Cloth Library have a well-thought color scheme working behind them, providing them with a certain je ne sais quoi quality for which our tagline – ‘Every fabric tells a story’ becomes actually true! Plus, the carefully thought of color scheme and assemblage aptly matches the aesthetics and composition of the yarn with which our fabrics are manufactured. With such amazing qualities, it is no wonder that our clientele always fetches a competitive edge and higher value in the market, and thus we enjoy their goodwill and faith as they come back to us again and again for fabrics! Next time – if you’re watching the Red Carpets closely, don’t be surprised if you see our fabrics being used to make one of those ‘Ooh’ celebrity wardrobes!<p>
			<p class="clothp"><i>Majority of the fabrics used in The Cloth Library are manufactured by independent small-scale industry units who work/have worked exclusively with the A-list European designers. These designers generally engage these small timers to encourage them, and also provide sufficient business for them to earn a decent livelihood. At The Cloth Library we also uphold such value and promote small-scale industry units so that it’s a win-win as well as a contribution towards society. </i><p>
			<p class="clothp"><i>At The Cloth Library – it’s not merely a telltale. For example, the usage of minimum fabrics and providing complete fashion solution to our European counterparts is not just an exaggeration, neither is the quality we provide. You need to step in our unit and see it for yourself – and we are sure that your eyes so adept at understanding the perfect fabric will appreciate what we have to offer.</i><p>
			<p class="clothp">Our competitive edge of providing fashion solutions, including designing complete seasonal forecast through our partners offices in Europe, as well as providing fabric in competitive and affordable prices also provide you with a competitive edge – upheaval in sales and appreciation of quality. Plus, you get to be a part of the goodwill at the fashion fraternity – which once acquired, works wonder in the long run!<p
			<p class="clothp">Come to The Cloth Library – maybe, for a cup of coffee, see our fabrics for yourself, and we are sure you won’t be disappointed!<p>
		</div>
	</div>
	<div class="row" style="margin-top: 40px;">
		<div class="container">				
				<?php 
					$page=$conn->prepare("SELECT * FROM cl_gallery WHERE status='1'");
					$page->execute();
					$sportfetch = $page->fetchAll();
					$s = 1;				
					foreach($sportfetch as $row){
				?>
						<div class="col-md-4 col-sm-12 col-xs-12"><img class="gallery" src="admin/lib/image/<?php echo $row['image'];?>"  alt=""></div>
				<?php $s++; } ?>
			
		</div>
	</div>
</div>
<?php include('footer.php');?>