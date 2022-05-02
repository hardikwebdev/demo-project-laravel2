@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Registration Successful')
@section('content')

<!-- Get Project -->

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Registration Successful</h2>
			</div>    
		</div>    
	</div>    
</section>

<div id="clear-f"></div>
<section class="get-project transactions-section">
	<div class="container">
		<div class="row">
			<div class="col-12">
				<section class="banner">
					<h2 class="text-blue">
						<span class="icon-check"></span>
					</h2>
					<br>
					<h2 class="text-black">Thank you for registration</h2>
					<p class="text-black">You've successfully created a demo account. Just confirm your email and login</p>
					<br>
					<p class="text-center">
						<a href="{{url('/login')}}" class="send-request-buttom">Login</a>
					</p>
				</section>
			</div>
		</div>
	</div>
</section>
@endsection
@section('css')
<style type="text/css">
	.banner-wrap{
		min-height: 400px;
		height: 400px;
		background: none;
		background-color: #fff;
		position: relative;
		display: block;
		overflow: hidden;
	}
	.banner{
		/*padding:0px;*/
		min-height:0px!important;
		/*position: absolute;*/
		top:50%;
		text-align:center;
		left: 0;
		right: 0;
		/*transform: translateY(-50%);*/
	/*text-align: center;
	width: 100%;*/
}
p {
	width: 100% !important;
}
.text-green{
	color: #16ffd8 !important;
}
.text-blue{
	color: #55bef9 !important;
}
.text-black{
	color: #2b373a !important;	
}
h3{
	color: #16ffd8
}
.login-btn{
	display: inline-block;
	color: #fff !important;
}
</style>	
@endsection

@if($_SERVER['HTTP_HOST'] == 'www.demo.com' || $_SERVER['HTTP_HOST'] == 'demo.com')
@section('seoscript')
<!-- Facebook Pixel Code -->
<script>
	!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
		n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
		n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
		t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
			document,'script','https://connect.facebook.net/en_US/fbevents.js');
		fbq('init', '771544636257431');
		fbq('track', 'PageView');
		<script>
		fbq('track', 'CompleteRegistration', {
			value: 0,
			currency: 'USD'
		});
	</script>
</script>
<noscript><img height="1" width="1" style="display:none"
	src="https://www.facebook.com/tr?id=771544636257431&ev=PageView&noscript=1"
	/></noscript>
	<!-- DO NOT MODIFY -->
	<!-- End Facebook Pixel Code -->

	<!-- Google Code for demo Registrations Conversion Page -->
	<script type="text/javascript">
		/* <![CDATA[ */
		var google_conversion_id = 881372725;
		var google_conversion_label = "1kHJCP6Xp4QBELXcoqQD";
		var google_remarketing_only = false;
		/* ]]> */
	</script>
	<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
	</script>
	<noscript>
		<div style="display:inline;">
			<img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/881372725/?label=1kHJCP6Xp4QBELXcoqQD&amp;guid=ON&amp;script=0"/>
		</div>
	</noscript>


	<!-- Global site tag (gtag.js) - AdWords: 881372725 -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=AW-881372725"></script>
	<script type="text/plain" data-cookieconsent="marketing">
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'AW-881372725');
	</script>

	<script type="text/javascript" src="https://superstarseo.iljmp.com/improvely.js"></script>
	<script type="text/javascript">
		improvely.init('superstarseo', 14);
		improvely.conversion({
			goal: 'new registration',    
			revenue: 0.00,   
			reference: 'new account'
		});
	</script>
	<noscript>
		<img src="https://superstarseo.iljmp.com/track/conversion?project=14&goal=sale&revenue=9.95&reference=1160" width="1" height="1" />
	</noscript>
	@endsection
	@endif

	@section('scripts')
	<script type="text/javascript">
		onClick=”ga(‘send’, ‘event’, { eventCategory: ‘register‘, eventAction: ‘submit’, eventLabel: ‘registration form’});”
	</script>
	@endsection