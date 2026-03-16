<?php  
	session_start(); 
	@$id = $_SESSION['id'];
	$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	$url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$exp = explode('/',$url); 
	$url = $exp[4];
	//echo $url; die;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>The Cloth Library</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/animate.min.css">
    <link rel="stylesheet" type="text/css" href="css/slider.css">
    <link rel="stylesheet" href="css/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="css/font-awesome-4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="css/flaticon/font/flaticon.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700,800%7CPlayfair+Display:400,700%7CRoboto:100,300,400,400i,500,700">
    <link href="css/ie10-viewport-bug-workaround.css" rel="stylesheet">
    <script src="js/ie-emulation-modes-warning.js"></script>
</head>
<body>
<header class="main-header">
	<nav class="navbar navbar-default">
		<div class="navbar-header ab">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			</button>
			<a href="index.php" class="logo">
				<img src="img/logo.png">
			</a>
		</div>
		<div class="navbar-header ab1">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
			<span class="sr-only">Toggle navigation</span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			<span class="icon-bar"></span>
			</button>
			<a href="index.php" class="logo">
				<img src="img/flogo.png">
			</a>
		</div>
		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			<ul class="nav nav-list navbar-nav navbar-right">
				<li <?php if($url=='index.php'){ echo"class='active'"; }else{ echo "";}?>><a href="index.php"><b>Home</b></a></li>
				<li <?php if($url=='about.php'){ echo"class='active'"; }else{ echo "";}?>><a href="about.php"><b>About us</b></a></li>
				<li <?php if($url=='cloth.php'){ echo"class='active'"; }else{ echo "";}?>><a href="cloth.php"><b>Our COLLECTION</b></a></li>
				<li <?php if($url=='showroom.php'){ echo"class='active'"; }else{ echo "";}?>><a href="showroom.php"><b>Showroom</b></a></li>
				<li <?php if($url=='contact.php'){ echo"class='active'"; }else{ echo "";}?>><a href="contact.php"><b>Contact Us</b></a></li>
				<li <?php if($url=='software/admin/login.php'){ echo"class='active'"; }else{ echo "";}?>><a href="software/admin/login.php"><b>Admin Login</b></a></li>
				<li <?php if($url=='software/user/login.php'){ echo"class='active'"; }else{ echo "";}?>><a href="software/user/login.php"><b>User Login</b></a></li>
			</ul>
		</div>
	</nav>
</header>
<!-- Header End -->