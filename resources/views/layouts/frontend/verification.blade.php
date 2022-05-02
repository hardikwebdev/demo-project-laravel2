<!DOCTYPE html>
<html lang="en">

<head>
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-46324402-37"></script>
	
	<!-- Google Tag Manager -->
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-MXLWTRC');</script>
	<!-- End Google Tag Manager -->

	<script type="text/javascript">
	window.heap=window.heap||[],heap.load=function(e,t){window.heap.appid=e,window.heap.config=t=t||{};var r=document.createElement("script");r.type="text/javascript",r.async=!0,r.src="https://cdn.heapanalytics.com/js/heap-"+e+".js";var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(r,a);for(var n=function(e){return function(){heap.push([e].concat(Array.prototype.slice.call(arguments,0)))}},p=["addEventProperties","addUserProperties","clearEventProperties","identify","resetIdentity","removeEventProperty","setEventProperties","track","unsetEventProperty"],o=0;o<p.length;o++)heap[p[o]]=n(p[o])};
	heap.load("3889671231");
	</script>

	<script type="text/javascript" src="https://widget.wickedreports.com/v2/3890/wr-8a4eb2fab86bfc576f3f739bc43197fc.js" async></script>

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	
	@yield('metaTags')
	<title>@yield('pageTitle')</title>
	<meta name="facebook-domain-verification" content="bcn8s1gl707flv4ubrunlmhzo2ogi2" />
	<meta name="google-site-verification" content="auM-Sb2dSpXIzKoGDMvbItznl1lacAmWWBn-Jjive0E" />
	<!-- Favicons -->
	<link rel="shortcut icon" href="{{url('public/frontend/assets/img/logo/favicon.png')}}">
	<!-- Bootstrap core CSS -->
	<link href="{{url('public/frontend/assets/vendor/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
	<!-- Custom fonts for this template -->
	<link href="{{url('public/frontend/assets/vendor/fontawesome-free/css/all.css')}}" rel="stylesheet">
	<link href="{{url('public/frontend/assets/vendor/custom-fonts/css/cus-font.css')}}" rel="stylesheet" type="text/css">
	<link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,300italic,400italic,700italic" rel="stylesheet" type="text/css">
	<!-- Custom styles for this template -->
	<link rel="stylesheet" type="text/css" href="{{url('public/frontend/assets/css/slick.css')}}">
	<link rel="stylesheet" type="text/css" href="{{url('public/frontend/assets/css/slick-theme.css?v=1.0')}}"> 
	<link href="{{asset('resources/assets/sass/bootstrapValidator.min.css')}}" rel="stylesheet"> 
	<link rel="stylesheet" type="text/css" href="{{url('public/frontend/assets/css/datepicker.css')}}"> 
	<link href="{{asset('resources/assets/sass/custom.css?v=1.15')}}" rel="stylesheet"> 
	<link href="{{url('public/frontend/assets/css/style.css?v=1.11')}}" rel="stylesheet">
	<link href="{{url('public/frontend/assets/css/style2.css?v=1.11')}}" rel="stylesheet">
	<link href="{{url('public/frontend/toastr-master/build/toastr.min.css')}}" rel="stylesheet" type="text/css" />
	@yield('css')
	@yield('seoscript')
	@if(Route::getCurrentRoute()->uri() == "/")
	@include('layouts.frontend.seoscript')
	@endif  

	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());
		gtag('config', 'UA-139163885-1');
	</script>
</head>

<body>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MXLWTRC"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->

	<div id="myOverlay"></div>
	<div id="loadingGIF">
		<img src="{{url('public/frontend/images/lg.circle-slack-loading-icon.gif')}}">
	</div>

	<nav class="header navbar-light bg-light1 static-top login-user-menu mobile-hide1">
		<div class="container mt-3">
			<div class="row align-items-center login-menu">
				<div class="col-12 text-center"> 
					<a class="navbar-brand" href="{{url('/')}}"><img src="{{url('public/frontend/assets/img/logo/LogoHeader.png')}}" class="header-v-logo"></a>
				</div>
			</div>
		</div>
	</nav>

	@yield('content')

	<section class="footer-bottom">
		<div class="container">
			<div class="row text-center pt-4 pb-4">
				<div class="col-md-12 ">
					<div class="copyrighttext">
						<p><span>©</span><a href="https://www.demo.com">demo</a> All Rights Reserved {{date('Y')}} </p>
					</div>
				</div>    
			</div>
		</div>
	</section>  

	<script>
		var viewSubCategory = '{!! route('viewSubCategories') !!}';
		var servicefilter = '{!! route('filterServices') !!}';
		var promoServicefilter = '{!! route('promoFilterServices') !!}';
		var base_url = '{!! url('/') !!}';
		var _token ='{{ csrf_token() }}';
		var email_already='{!! route('alreadyemail') !!}';
		var username_already='{!! route('alreadyuser') !!}';
		var close_img_path = '{{front_asset('images/dashboard/notif-close-icon.png')}}';
		var auth = '{{Auth::check()}}';
		var auth_user_id = {{Auth::check()?Auth::user()->id:"0"}};
		var updating_header_data = "{{ route('updateing_header')}}";
		var imoji_url = '{{front_asset('img/emoji/')}}';
		var __service_min_price='{{env('MINIMUM_SERVICE_PRICE')}}';
		var current_browser_tab_page_title = "{!! preg_replace('/\s+/',' ',$app->view->getSections()['pageTitle']) !!}";
		var new_notification_blink_page_title = "📫 New Message";
	</script>  

	<!-- Bootstrap core JavaScript -->
	<script src="{{url('public/frontend/assets/vendor/jquery/jquery.min.js')}}"></script>
	<script src="{{url('public/frontend/assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
	<script src="{{url('public/frontend/assets/js/slick.js')}}" type="text/javascript" charset="utf-8"></script>

	<!-- xmAlerts -->
	<script src="{{front_asset('js/vendor/jquery.xmalert.min.js')}}"></script>
	<script src="{{front_asset('js/vendor/jquery.magnific-popup.min.js')}}"></script>

	<script type="text/javascript" src="{{url('public/frontend/assets/vendor/jquery-ui/jquery-ui.min.js')}}"></script>

	<!--copy clipboard -->
	<script src="{{front_asset('js/clipboard.min.js')}}"></script>

	<script src="{{ asset('resources/assets/js/bootstrapValidator.min.js') }}"></script>
	<script src="{{ asset('resources/assets/js/custom.js?v='.env('CACHE_BUST')) }}"></script>
	<script src="{{ asset('resources/assets/js/formvalidator.js?v='.env('CACHE_BUST')) }}"></script>
	<script src="{{front_asset('assets/js/bootstrap-datepicker.js') }}"></script>

	<script src="{{url('public/frontend/toastr-master/build/toastr.min.js')}}"></script>

	<script src="{{url('public/frontend/assets/js/custom.js?v='.env('CACHE_BUST'))}}"></script>

	<script src='https://www.google.com/recaptcha/api.js'></script>

	<!-- clicky -->
	<script src="//static.getclicky.com/js" type="text/javascript"></script>
	<script type="text/javascript">try{ clicky.init(101106104); }catch(e){}</script>
	<noscript><p><img alt="Clicky" width="1" height="1" src="//in.getclicky.com/101106104ns.gif" /></p></noscript>
	<!-- clicky -->

	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'UA-46324402-37');
	</script>

	<script>
		var clipboard = new Clipboard('.copy_btn');

		clipboard.on('success', function(e) {
			toastr.success('Link Copied!', '');
		});

		clipboard.on('error', function(e) {
			toastr.error('Something Goes Wrong', '');
		});

		
	</script>

	@if(Route::currentRouteName() != "app_twofactorauth")
	<script>
		//reset two factor timer
		localStorage.removeItem("two_factor_timer");
	</script>
	@endif  

	<?php
	if(Session::has('tostError')){?>
	<script type="text/javascript">
		toastr.error('<?=Session::get('tostError');?>', '');
	</script>
	<?php }
	if(Session::has('tostSuccess')){?>
	<script type="text/javascript">
		toastr.success('<?=Session::get('tostSuccess');?>', '');
	</script>
	<?php } ?>
	
	@yield('scripts')

</body>
</html>
