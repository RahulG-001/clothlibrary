<?php include('header.php'); ?>
<!-- Page Banner Start -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
<script>
function submitform(){ //alert('hiii');
		$("#contact_form").submit(function(event) { 
			$("#show2").show();
			var abc = event.preventDefault();
			event.stopImmediatePropagation();
			$.ajax({
				url: 'contact_us.php',
				type:'POST',
				data: new FormData(this),
				cache:false,
				contentType:false,
				processData:false,
				success:function(data)
				{ alert(JSON.stringify(data)); 
					$("#show2").hide();
					if(data.status == '1' ){ 
						$("#msg").show();
						$("#msg").html('YOUR QUERY HAS BEEN SUCCESSFULLY SUBMIT');
						setTimeout(function(){
							$("#msg").hide();
						},5000);
						$('#contact_form').each(function(){
							this.reset();
						});
					}else{
						$("#msg").show();
						$("#msg").html(data.error);
						setTimeout(function(){
							$("#msg").hide();
						},3000);
					}
					
				}
			});
		});
	}
</script>
<div class="">
   <img src="img/banner-sub-slider.jpg" alt="" width="100%">
</div>
<!-- Page Banner End -->

<!-- contact us body Start-->
<div class="contact-us-body">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 col-md-8 col-sm-6 col-xs-12">
                <!-- Contact Form Start -->
                <div class="contact-form">
                    <!-- Header-->
                    <div class="header">
                        <h3>GET IN TOUCH</h3>
                       
                    </div>
                    <form id="contact_form" method="POST">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group fullname">
                                    <input type="text" name="name" class="input-text" placeholder="Enter your first name" required>
                                </div>
                            </div>
							<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group fullname">
                                    <input type="text" name="last" class="input-text" placeholder="Enter your last name" required>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group enter-email">
                                    <input type="email" name="email" class="input-text" placeholder="Enter your email" required>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group number">
                                    <input type="text" name="phone" class="input-text" placeholder="Enter your number" maxlength="10" required>
                                </div>
                            </div>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 clearfix">
                                <div class="form-group message">
                                    <textarea class="input-text" name="message" placeholder="Write your message"></textarea>
                                </div>
                            </div>
							<div id="msg"></div>
                            <div class="col-lg-4 col-md-12 col-sm-12 col-xs-12">
                                <div class="form-group send-btn">
									<input type="hidden" name="add"  value="add">
                                    <input type="submit" name="submit" onclick="submitform()" value="Submit Now" class="btn-submit">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- Contact Form End -->
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                <!-- Contact Details-->
                <div class="contact-details">
                    <!-- Header-->
                    <div class="header">
                        <h3>Contact Details</h3>
                    </div>
                    <!--  Contact-details-box-->
					<div class="media contact-details-box">
                        <div class="media-left">
                            <i class="fa fa-map-marker"></i>
                        </div>
                        <div class="media-body">
                            <h5>Shahpur Address</h5>
                            <p>5L, First Floor, Dada Jungi House, Lane no.1 New Delhi 110049</br>
							<a href="tel:011-41066588">office: 011-41721588</a></p>
                        </div>
                    </div>
                    <!--  Contact-details-box-->
                    <div class="media contact-details-box">
                        <div class="media-left">
                            <i class="fa fa-envelope"></i>
                        </div>
                        <div class="media-body">
                            <h5>Email</h5>
                            <p>
                                <a href="mailto:theclothlibrary@wwliving.com">theclothlibrary@wwliving.com</a>
                            </p>
                        </div>
                    </div>
                    <!-- Social List-->
                    
                    <div class="social-list clearfix">
                        <a href="https://www.facebook.com/TheClothLibraryOfficial" class="bg-facebook">
                            <i class="fa fa-facebook"></i>
                        </a>
                       <!-- <a href="#" class="bg-twitter">
                            <i class="fa fa-twitter"></i>
                        </a>
                        <a href="#" class="bg-google">
                            <i class="fa fa-google-plus"></i>
                        </a>
                        <a href="#" class="bg-linkedin">
                            <i class="fa fa-linkedin"></i>
                        </a>-->
                        <a href="https://www.instagram.com/theclothlibraryofficial/" class="bg-instagram">
                            <i class="fa fa-instagram"></i>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- contact-us-body End-->

<!-- Footer Start-->
<?php include('footer.php'); ?>