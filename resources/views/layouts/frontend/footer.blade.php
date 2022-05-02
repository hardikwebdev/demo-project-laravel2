<!-- Footer -->
<footer class="footer footer_section">
	<div class="container">
		<div class="row">
			<div class="col-md-3">
				<h5 class="cus-h5">Community</h5>
				<ul class="cus-ul">
					<li><a class="hyperlink-color-none"  href="{{route('blogs')}}">Blog</a></li>
					<li><a class="hyperlink-color-none" target="_blank" href="https://shop.demo.com/">Merch Store</a></li>
					<li><a class="hyperlink-color-none" target="_blank" href="https://www.facebook.com/groups/demo/">Facebook Group</a></li>
				</ul> 
				<h5 class="cus-h5">Accessibility</h5>
				<ul class="cus-ul">
					@if((Auth::check() && Auth::user()->parent_id == 0) || !Auth::check())
					<li><a class="hyperlink-color-none" target="_blank" href="{{route('view_cart')}}">Shopping Cart</a></li>
					@endif
					@if(Auth::check())
					<li><a class="hyperlink-color-none" target="_blank" href="{{url('messaging/conversations')}}">Messages</a></li>
					@else
					<li><a class="hyperlink-color-none" target="_blank" href="{{url('login')}}?GoToConversation=1">Messages</a></li>
					@endif
				</ul> 
			</div>
			<div class="col-md-3">
				<h5 class="cus-h5">Categories</h5>
				<ul class="cus-ul">
					@foreach($footer_category as $category)
					<li><a class="hyperlink-color-none" href="{{route('services_view',$category->seo_url)}}">{{ $category->category_name }}</a></li>
					@endforeach
				</ul> 
			</div>
			<div class="col-md-3">
				<h5 class="cus-h5">Resources</h5>
				<ul class="cus-ul">
					<li><a class="hyperlink-color-none" target="_blank" href="mailto:{{env('NEW_HELP_EMAIL')}}">Contact Us</a></li>
					<li><a class="hyperlink-color-none" target="_blank" href="{{url('terms')}}">Terms and Conditions</a></li>
					<li><a class="hyperlink-color-none" target="_blank" href="{{url('privacy')}}">Privacy Policy</a></li>
					<!-- <li><a class="hyperlink-color-none" target="_blank" href="https://feedback.userreport.com/54be0c03-a624-49d6-86e4-fa96ebc9c083#ideas/popular">Suggest a Feature</a></li> -->
					<li><a class="hyperlink-color-none" target="_blank" href="https://demo.freshdesk.com/support/home">Support</a></li>
				</ul> 
				<h5 class="cus-h5">mobile app</h5>
				<ul class="cus-ul">
					@if(Auth::user()->web_dark_mode == 1)
						<li><a class="hyperlink-color-none" target="_blank" href="https://apps.apple.com/us/app/demo-freelance-marketplace/id1546041094"><img src="{{url('public/frontend/images/homepage log out/icons/Apple_darkmode.png')}}" class="mr-2 pb-2" alt=""> App Store</a></li>
						<li><a class="hyperlink-color-none" target="_blank" href="https://play.google.com/store/apps/details?id=com.demo"><img src="{{url('public/frontend/images/homepage log out/icons/Android_darkmode.png')}}"  class="mr-2 pb-1" alt=""> Google Play</a></li>
					@else 
						<li><a class="hyperlink-color-none" target="_blank" href="https://apps.apple.com/us/app/demo-freelance-marketplace/id1546041094"><img src="{{url('public/frontend/images/homepage log out/icons/Apple.png')}}" class="mr-2 pb-2" alt=""> App Store</a></li>
						<li><a class="hyperlink-color-none" target="_blank" href="https://play.google.com/store/apps/details?id=com.demo"><img src="{{url('public/frontend/images/homepage log out/icons/Android.png')}}"  class="mr-2 pb-1" alt=""> Google Play</a></li>
					@endif
				</ul>
			</div>
			<div class="col-md-3">
				<h5 class="cus-h5">Secure with</h5>
				<div class="payple pt-2">
					<p class="pb-2"></p>
					<img src="{{url('public/frontend/assets/img/paypal.png')}}" width="120" class="img-fluid"> 
					<p class="pb-3"></p>
					<img src="{{url('public/frontend/images/skrill.png')}}" width="90" class="img-fluid"> 
					<p class="pb-3"></p>
					<img src="{{url('public/frontend/assets/img/Payoneer.png')}}" width="120" class="img-fluid"> 
					<p class="pb-3"></p>
					<img src="{{url('public/frontend/assets/img/Visa.png')}}" width="75" class="img-fluid">
					<p class="pb-3"></p>
					<img src="{{url('public/frontend/assets/img/Master card.png')}}" class="img-fluid">
					<p class="pb-3"></p>
					<img src="{{url('public/frontend/assets/img/Discover.png')}}" class="img-fluid">
				</div>
			</div>
		</div>
	</div>
</footer>
@if(!Auth::check())
<!-- login / register modal -->
<div class="modal fade custommodel" id="register-login-modal" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Go to checkout</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body form-group custom" id="checkout-login">
				<div class="col-lg-12">
					<div class="alert alert-danger" style="display:none;" id="forgot_error_msg_div">
		                <span id="forgot_error_msg"></span>
					</div>
					<div class="cus-login-model">
						@include('frontend/auto-login/login_register_popup')
					</div>
				</div>
				
			</div>
		</div>
	</div>
</div>
<!-- End login / register modal -->
@endif
<section class="footer-bottom">
	<div class="container">
		<div class="row pt-4 pb-4">
			<div class="col-md-2">
				@if(Auth::user()->web_dark_mode == 1)
				<a href="{{url('/')}}"><img src="{{url('public/frontend/assets/img/logo/LogoHeader_DarkMode.png')}}" class="footer-logo"></a>
				@else
				<a href="{{url('/')}}"><img src="{{url('public/frontend/assets/img/logo/LogoHeader.png')}}" class="footer-logo"></a>
				@endif
			</div>
			<div class="col-md-7 d-flex align-self-end pb-1">
				<div class="copyrighttext">
					<p class="footer_copyright_color pt-2 text-color-4 font-14 pl-md-5 pl-lg-0"><span>©</span><a href="{{url('/')}}">demo</a> All Rights Reserved {{ date('Y') }} </p>
				</div>
			</div>
			<div class="col-md-3">
				<ul class="list-inline mb-0 cus-social pt-1">
					<li class="list-inline-item mr-3">
						<a target="_blank" href="https://www.facebook.com/demoofficial/">
							<i class="fab fa-facebook"></i>
						</a>
					</li>
					<li class="list-inline-item mr-3">
						<a target="_blank" href="https://twitter.com/democom">
							<i class="fab fa-twitter"></i>
						</a>
					</li>
					<li class="list-inline-item mr-3">
						<a target="_blank" href="https://www.instagram.com/democom/">
							<i class="fab fa-instagram"></i>
						</a>
					</li>
					<li class="list-inline-item mr-3">
						<a target="_blank" href="https://www.youtube.com/c/demoOfficial">
							<i class="fab fa-youtube"></i>
						</a>
					</li>
					<li class="list-inline-item mr-3">
						<a target="_blank" href="https://www.tiktok.com/@demoofficial">
							<i class="fab fa-tiktok"></i>
						</a>
					</li>
					<li class="list-inline-item">
						<a target="_blank" href="https://www.linkedin.com/company/demo">
							<img src="{{url('public/frontend/images/linkedin.png')}}" class="pb-2" alt="">
						</a>
					</li>
				</ul>
			</div>  
		</div>
	</div>
</section>  


<!-- Show Video Model -->
<div class="modal md-effect-1" id="showVideo" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content border-radius-15px">
			<div class="modal-header modal-header-border-none border-0">
				<p class="mb-0 font-18 text-color-6" id="portfolio_title">Title</p>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body pt-0 border-0 px-3 pb-4">
				<div class="videoWrapper" id="play_video">
					<video class="responsive-iframe video-player" controls>
						<source src="" type="video/mp4">
						Your browser does not support HTML video.
					</video>
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Show Course article Model -->
<div class="modal md-effect-1" id="preview-course-article" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content border-radius-15px">
			<div class="modal-header modal-header-border-none border-0">
				<p class="mb-0 font-18 text-color-6" id="course-preview-title">Title</p>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body pt-0 border-0 px-3 pb-4">
				<div id="load-course-article" class="ck-custom-content">
				</div>
			</div>
		</div>
	</div>
</div>

<!-- Preview Course Video Modal -->
<div class="modal md-effect-1" id="preview_course_video_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg mt-5">
        <div class="modal-content">
            <div id="load-course-content-video">
				<div id="video-player-preview"></div>
			</div>
			<button type="button" class="close position-absolute video-player-close-btn" data-dismiss="modal">×</button>
        </div>
    </div>
</div>

<!-- The Modal -->
<div id="customViewImageModal" class="cust-view-image-modal">
	<span class="close">&times;</span>
	<div id="caption"></div>
	<img class="modal-content" id="imageView">
</div>

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
		var no_of_volume_service = '{{route('no_of_volume_service')}}';
		var check_bundle_discount = '{{route('check_bundle_discount')}}';
		var checkemail_sub_users='{!! route('checkemail_sub_users') !!}';
		var checkusername_sub_users='{!! route('checkusername_sub_users') !!}';
		var get_extra_detail_url='{!! route('get_extra_details') !!}';
		var service_search_suggestion='{!! route('service_search_suggestion') !!}';
		var restrict_email_domain='{!! route('restrict_email_domain') !!}';
		var submit_new_features='{!! route('submit_new_features') !!}';
		var update_confetti_effect='{!! route('update_confetti_effect') !!}';
		var __service_min_price='{{env('MINIMUM_SERVICE_PRICE')}}';
		var __payoneer_min_payout='{{env('PAYONEER_MINIMUM_PAYOUT')}}';
		var reviewFeedback = '{!! route('reviewFeedback') !!}';
		var cookieCartUrl= '{!! route('cookieCart') !!}';
		var register_login_popup = '{!! route('register_login_popup') !!}';
		var add_to_cart_combo_session_js = '{!! route('add_to_cart_combo_session') !!}';
		var loginRoute = '{!! url('login') !!}';
		var get_message_notification_list_route = '{!! route('get_message_notification_list_for_header') !!}';
		var chat_iframe_url = "{{url('messaging')}}?display_floating=true";
		var bundleCartNameCheck = '{!! route('bundleCartNameCheck') !!}'; 
		var check_auth_route = '{!! route('check_authentication') !!}';
		var get_my_service_list = '{!! route('get_my_service_list') !!}';
		var get_service_card_preview = '{!! route('get_service_card_preview') !!}';
		var send_service_as_message = '{!! route('send_service_as_message') !!}';
		var view_cart_route = '{!! route('view_cart') !!}';
		var current_browser_tab_page_title = "{!! preg_replace('/\s+/',' ',$app->view->getSections()['pageTitle']) !!}";
		var new_notification_blink_page_title = "📫 New Message";
		var unreadMessageCount = '{!! $unreadMessageCount !!}';
		var apply_hidden_pizza = '{!! route('apply_hidden_pizza') !!}';
		var can_show_pizza_for_category = '{!! route('can_show_pizza_for_category') !!}';
		var notification_mark_as_read = '{!! route('notification_mark_as_read') !!}';
		var notification_clear = '{!! route('notification_clear') !!}';
		var all_notification_mark_as_read = '{!! route('all_notification_mark_as_read') !!}';
		var all_notification_clear = '{!! route('all_notification_clear') !!}';
		var portfolio_change_ordering_url = '{!! route('portfolio.change_ordering') !!}';
		var change_service_ordering = '{!! route('changeServiceOrdering') !!}';
		var __payment_processing_fee='{{env('PAYMENT_PROCESSING_FEE')}}';

		var quick_checkout_id = null;
		var CHECK_SUBUSER_NAME_URL = '{!! route('subuser.check.username') !!}';
	</script>  

	<!-- Bootstrap core JavaScript -->
	<script src="{{url('public/frontend/assets/vendor/jquery/jquery.min.js?v=1.13.1')}}"></script>
	<script src="{{url('public/frontend/assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
	<script src="{{url('public/frontend/assets/js/slick.js')}}" type="text/javascript" charset="utf-8"></script>

	<!-- xmAlerts -->
	<script src="{{front_asset('js/vendor/jquery.xmalert.min.js')}}"></script>
	<script src="{{front_asset('js/vendor/jquery.magnific-popup.min.js')}}"></script>

	<!--copy clipboard -->
	<script src="{{front_asset('js/clipboard.min.js')}}"></script>

	<script src="{{ asset('resources/assets/js/bootstrapValidator.min.js') }}"></script>
	{{-- <script src="{{url('public/frontend/assets/js/bootstrap-datepicker.js')}}"></script> --}}

	<script src="{{ asset('resources/assets/js/formvalidator.js?v='.env('CACHE_BUST')) }}"></script>
	
	<script src="{{front_asset('assets/js/bootstrap-datepicker.js') }}"></script>

	<script src="{{url('public/frontend/toastr-master/build/toastr.min.js')}}"></script>

	<!-- CKEditor -->
	<script src="{{front_asset('ckeditor/build/ckeditor.js?v='.env('CACHE_BUST'))}}" type="text/javascript"></script>
	@if(skip_highlight_js(['jobs.edit']))
	<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.3.1/highlight.min.js"></script>
	@endif
	<script>
	@if(skip_highlight_js(['jobs.edit']))
		hljs.highlightAll();
	@endif
	var req_questions_editor,desc_editor,order_note_editor,temp_description,job_description;
	</script>

	<script src="{{front_asset('js/bootbox.min.js')}}"></script>
	<script type="text/javascript" src="{{front_asset('assets/js/jquery.lazy.min.js')}}"></script>
	{{-- autocomple --}}
	<script type="text/javascript" src="{{url('public/frontend/assets/vendor/jquery-ui/jquery-ui.min.js?v=1.13.1')}}"></script>
	{{-- custom --}}
	<script src="{{ asset('resources/assets/js/custom.js?v='.env('CACHE_BUST')) }}"></script>
	<script src="{{url('public/frontend/assets/js/custom.js?v='.env('CACHE_BUST'))}}"></script>
	@if(Auth::check() && Auth::user()->web_dark_mode == 1)
	<script src="{{ asset('resources/assets/js/dark_mode.js?v='.env('CACHE_BUST')) }}"></script>
	@endif 
	<script src="https://www.google.com/recaptcha/api.js" async defer></script>

	{{-- <script src='https://www.google.com/recaptcha/api.js'></script> --}}

	<!-- clicky -->
	<script src="//static.getclicky.com/js" type="text/javascript"></script>
	<script type="text/javascript">try{ clicky.init(101106104); }catch(e){}</script>
	<noscript><p><img alt="Clicky" width="1" height="1" src="//in.getclicky.com/101106104ns.gif" /></p></noscript>
	<!-- clicky -->

	<!-- start webpush tracking code --> 
	<script type='text/javascript'> var _at = {}; window._at.track = window._at.track || function(){(window._at.track.q = window._at.track.q || []).push(arguments);}; _at.domain = 'demo.com';_at.owner = '2d8b33e61ba4';_at.idSite = '20689';_at.attributes = {};_at.webpushid = 'web.46.aimtell.com';(function() { var u='//s3.amazonaws.com/cdn.aimtell.com/trackpush/'; var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0]; g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'trackpush.min.js'; s.parentNode.insertBefore(g,s); })();</script>
	<!-- end webpush tracking code -->

	{{-- JWPlayer libraries --}}
	<script src="https://cdn.jwplayer.com/libraries/yGFGKqFH.js" type="text/javascript"></script>

	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'UA-46324402-37');
	</script>
	<script type="text/javascript">
		// Show Video JS
		$(document).on('click','.video-link',function(){
			var mime = $(this).data('mime');
			var video_link = $(this).data('url');
			$('#showVideo #portfolio_title').html($(this).data('title'));
			$('#play_video video').html('<source src="'+video_link+'" type="'+mime+'">Your browser does not support HTML video.');
			$("#play_video video")[0].load();
			$('#showVideo').modal('show');
		});
		$('#showVideo').on('hidden.bs.modal', function () {
			$('#showVideo #portfolio_title').html('');
			$('#play_video video').html('<source src="" type="">Your browser does not support HTML video.');
			$("#play_video video")[0].load();
		});
		
		$('#preview_course_video_modal').on('hidden.bs.modal', function () {
			$('#load-course-content-video').html('<div id="video-player-preview"></div>');
		});

		$('#preview-course-article').on('hidden.bs.modal', function () {
			$('#course-content-preview-video').hide();
			$('#course-content-preview-video video').html('');
			$('#load-course-article').hide();
			$('#load-course-article').html('');
		});
	</script>
	<script>
		var clipboard = new Clipboard('.copy_btn');

		clipboard.on('success', function(e) {
			toastr.success('Link Copied!', '');
		});

		clipboard.on('error', function(e) {
			toastr.error('Something Goes Wrong', '');
		});

		
		var clipboard_promo = new Clipboard('.copy_promo');
		clipboard_promo.on('success', function(e) {
			toastr.success('Promo Code Copied!', '');
		});

		var clipboard_promo_home = new Clipboard('.copy_promo_home');
		clipboard_promo_home.on('success', function(e) {
			$('.show_home_copy_div').slideDown("fast");
			setTimeout(function(){ 
				$('.show_home_copy_div').slideUp("fast");
			}, 700);
		});
		
		var clipboard_promo = new Clipboard('.copy_affiliate');
		clipboard_promo.on('success', function(e) {
			toastr.success('Link Copied!', '');
		});

		clipboard_promo.on('error', function(e) {
			toastr.error('Something Goes Wrong', '');
		});

		//lazy load all images
		$('.lazy-load').lazy();
	</script>

	@if(Route::currentRouteName() != "app_twofactorauth")
	<script>
		//reset two factor timer
		localStorage.removeItem("two_factor_timer");
	</script>
	@endif


	@if (Auth::check())
		<!-- Custom Expanded Menu: Added inline for page speed. -->
		<script type="text/javascript">
		(function($){
		// console.log('Loaded external custommenu.js');

		$('ul.custom-user-profile-dropdown')
			.removeAttr('class')
			.wrap('<div id="custom-seller-menu" class="dropdown-menu dropdown-content custom-user-profile-dropdown"/>');
		// $('#custom-seller-menu').show();
		$('ul.primary-nav-dropdowns').clone().removeClass('primary-nav-dropdowns').prependTo( $('#custom-seller-menu') );
		$('ul.primary-nav-dropdowns').hide();

		var t;
		// $('#custom-seller-menu').mouseout(function(){
		//     $(this).addClass('active');
		//     t = setTimeout(function(){
		//         clearTimeout(t);
		//         $('#custom-seller-menu').removeClass('active');
		//     }, 2000);
		// });

		})(jQuery);
		</script>
	@endif

<script type="text/javascript">
	@if(Session::has('tostError'))
		toastr.error('<?=Session::get('tostError');?>', '');
	@endif
	@if(Session::has('tostSuccess'))
		toastr.success('<?=Session::get('tostSuccess');?>', '');
	@endif
</script>

@if(Auth::check())
@if(Session::has('login_from_admin') && Session::get('login_from_admin') == 'yes')
@if(Session::get('isAnyActiveService') > 0)
<style>
#toast-container {
	padding-top: 30px !important;
}
</style>
@endif
<script type="text/javascript">
	var uname = "{{ Auth::user()->username }}";
	toastr.error('', 'Viewing As '+uname,{
		"closeButton": false,
		"positionClass": "toast-top-center",
		"timeOut": "0",
  		"extendedTimeOut": "0",
	});
</script>
@endif
@endif