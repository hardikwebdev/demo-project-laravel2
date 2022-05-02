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

	@if(Route::getCurrentRoute() != null && Route::getCurrentRoute()->uri() == "/")
	@include('layouts.frontend.seoscript')
	@endif 
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	@yield('metaTags')
	<title>@yield('pageTitle')</title>
	<meta name="facebook-domain-verification" content="bcn8s1gl707flv4ubrunlmhzo2ogi2" />
	<meta name="google-site-verification" content="auM-Sb2dSpXIzKoGDMvbItznl1lacAmWWBn-Jjive0E" />
	<!-- Favicons -->
	@yield('topcss')
	<link rel="shortcut icon" href="{{url('public/frontend/assets/img/logo/favicon.png')}}">
	<!-- Bootstrap core CSS -->
	<link href="{{url('public/frontend/assets/vendor/bootstrap/css/bootstrap.min.css?v=4.6.1')}}" rel="stylesheet">
	<!-- Custom fonts for this template -->
	<link href="{{url('public/frontend/assets/vendor/fontawesome-free/css/all.css')}}" rel="stylesheet">
	<link href="{{url('public/frontend/assets/vendor/custom-fonts/css/cus-font.css?v=1.0.0')}}" rel="stylesheet" type="text/css">
	<link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,300italic,400italic,700italic" rel="stylesheet" type="text/css">
	<!-- Custom styles for this template -->
	<link rel="stylesheet" type="text/css" href="{{url('public/frontend/assets/css/slick.css')}}">
	<link rel="stylesheet" type="text/css" href="{{url('public/frontend/assets/css/slick-theme.css?v=1.0')}}"> 
	<link href="{{asset('resources/assets/sass/bootstrapValidator.min.css')}}" rel="stylesheet"> 
	<link rel="stylesheet" type="text/css" href="{{url('public/frontend/assets/css/datepicker.css')}}"> 

	{{-- autocomple --}}
	<link type="text/css" href="{{url('public/frontend/assets/vendor/jquery-ui/jquery-ui.css?v=1.13.1')}}" rel="stylesheet" />

	<link href="{{url('public/frontend/assets/css/style.css?v='.env('CACHE_BUST'))}}" rel="stylesheet">
	<link href="{{url('public/frontend/assets/css/style2.css?v='.env('CACHE_BUST'))}}" rel="stylesheet">
	<link href="{{url('public/frontend/css/design-custom.css?v='.env('CACHE_BUST'))}}" rel="stylesheet">

	<link href="{{url('public/frontend/css/demo-custom.css?v='.env('CACHE_BUST'))}}" rel="stylesheet">

	<link href="{{url('public/frontend/toastr-master/build/toastr.min.css')}}" rel="stylesheet" type="text/css" />

	<link href="{{asset('resources/assets/sass/custom.css?v='.env('CACHE_BUST'))}}" rel="stylesheet"> 
	<link href="{{asset('resources/assets/sass/chatify-chat.css?v='.env('CACHE_BUST'))}}" rel="stylesheet"> 

	<link href="{{front_asset('assets/plugins/select2/css/select2.css')}}" rel="stylesheet" />

	<!-- highlight Js for ckeditor code block -->
	<link rel="stylesheet" href="{{url('public/frontend/ckeditor/highlight/default.min.css')}}">
	<link rel="stylesheet" href="{{front_asset('ckeditor/style.css')}}">

	@if(Auth::check() && Auth::user()->web_dark_mode == 1)
	<link href="{{asset('resources/assets/sass/dark_mode.css?v='.env('CACHE_BUST'))}}" rel="stylesheet"> 
	<link href="{{asset('resources/assets/sass/ckeditor-dark-mode.css?v='.env('CACHE_BUST'))}}" rel="stylesheet"> 
	@endif 

	@yield('css')
	@yield('seoscript')
    <!-- Hotjar Tracking Code for demo.com -->
	<script>
	(function(h,o,t,j,a,r){
	h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
	h._hjSettings={hjid:1717098,hjsv:6};
	a=o.getElementsByTagName('head')[0];
	r=o.createElement('script');r.async=1;
	r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
	a.appendChild(r);
	})(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
	</script>

	@yield('dataLayerTransction')
	
	<!-- Font Awesome -->
	<script src="https://kit.fontawesome.com/fa2592ce23.js" crossorigin="anonymous"></script>

	<!-- Facebook Open Graph -->
	@if(View::hasSection('og_url'))
	<meta property="og:url" content="@yield('og_url')" />
    @endif
	<meta property="og:app_id" content="@yield('og_app_id','298062924465542')" />
	{{-- <meta property="fb:app_id" content="@yield('og_app_id','298062924465542')" /> --}}
	<meta property="og:title" content="@yield('og_title','demo.com - Get More Stuff Done')"/>
    <meta property="og:type" content="@yield('og_type','website')"/>
    {{-- <meta property="og:image" content="@yield('og_image','https://www.demo.com/public/frontend/assets/img/FacebookOGImage.png')"/> --}}
	<meta property="og:image" content="@yield('og_image',url('public/frontend/assets/img/demoOGimage.jpg'))"/>
    <meta property="og:description" content="@yield('og_description','Join our world class marketplace and connect with businesseses and freelancers offering digital services in hundreds of categories.')"/>

	@if(View::hasSection('og_product_brand'))
	<meta property="product:brand" content="@yield('og_product_brand')" />
	@endif
	@if(View::hasSection('og_product_availibility'))
	<meta property="product:availability" content="@yield('og_product_availibility')">
	@endif
	@if(View::hasSection('og_product_category'))
	<meta property="product:category" content="@yield('og_product_category')">
	<meta property="fb_product_category" content="@yield('og_product_category')">
	@endif
	@if(View::hasSection('og_product_price_amount'))
	<meta property="product:price:amount" content="@yield('og_product_price_amount')">
	@endif
	@if(View::hasSection('og_product_price_currency'))
	<meta property="product:price:currency" content="@yield('og_product_price_currency')">
	@endif
	@if(View::hasSection('og_product_catalog_id'))
	<!-- <meta property="product:catalog_id" content="@yield('og_product_catalog_id')"> -->
	<meta property="product:retailer_item_id" content="@yield('og_product_catalog_id')"> 
	@endif

	@if(View::hasSection('og_product_price_standard'))
	<meta property="product:custom_label_0" content="@yield('og_product_price_standard')">
	@endif
	@if(View::hasSection('og_product_price_premium'))
	<meta property="product:custom_label_1" content="@yield('og_product_price_premium')">
	@endif

	<!-- ADA plugin -->
	<script> (function(){ var s = document.createElement('script'); var h = document.querySelector('head') || document.body; s.src = 'https://acsbapp.com/apps/app/dist/js/app.js'; s.async = true; s.onload = function(){ acsbJS.init({ statementLink : '', footerHtml : '', hideMobile : false, hideTrigger : false, disableBgProcess : false, language : 'en', position : 'left', leadColor : '#2a373b', triggerColor : '#2a373b', triggerRadius : '50%', triggerPositionX : 'left', triggerPositionY : 'bottom', triggerIcon : 'wheels2', triggerSize : 'small', triggerOffsetX : 20, triggerOffsetY : 20, mobile : { triggerSize : 'small', triggerPositionX : 'right', triggerPositionY : 'bottom', triggerOffsetX : 10, triggerOffsetY : 0, triggerRadius : '50%' } }); }; h.appendChild(s); })(); </script>

	<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date()); gtag('config', 'G-0NSE91Q720');
	</script>
        
</head>

<body>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MXLWTRC"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->

	<div id="myOverlay"></div>
	<div id="myOverlayPayment" style="display: none;">
		<img src="{{url('public/frontend/images/loading.gif')}}">
		<p>Your payment is being processed </br>please do not press back or refresh button.</p>
	</div>
	<div id="loadingGIF">
		@if(Auth::check() && Auth::user()->web_dark_mode == 1)
		<img src="{{url('public/frontend/images/shape-0.8s-667px.svg')}}">
		@else
		<img src="{{url('public/frontend/images/shape-0.8s-667px.svg')}}">
		@endif
	</div>
	
	@if(Route::currentRouteName() == 'coursePageDetail' || Route::currentRouteName() == 'buyer_orders_details' && isset($order->is_course) && $order->is_course == 1) 
		@include('layouts.frontend.header_course_details')
	@else
		@include('layouts.frontend.header')
	@endif

	<input type="hidden" id="global_id_dark_mode" value="{{Auth::user()->web_dark_mode}}">
	@yield('content')

	@include('layouts.frontend.footer')

	{{-- terms and services model --}}
	@if(Session::get('login_from_admin') != 'yes')
	@if(Auth::check() && !Auth::user()->terms_privacy)
	<div class="modal fade" id="tearms-of-use-popup" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true" data-backdrop="static" data-keyboard="false">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title bold-lable" id="bold-lable">Terms Of Use and Licensing Agreement</h5>
				</div>
				{!! Form::open(['route' => ['accept_tearms'],'method' => 'POST', 'id' => 'accept_tearms']) !!}

				<div class="modal-body form-group">
					@include('frontend.footerlink.tearms_contant')
				</div>

				<div class="modal-footer">
					{!! Form::submit('Accept',['class' => 'custom-sucess-btn']) !!}
				</div>

				{{ Form::close() }}
			</div>
		</div>
	</div>
	<script type="text/javascript">
		$('#tearms-of-use-popup .tearms_title').hide();
		$('#tearms-of-use-popup').modal('show');
	</script>
	<style type="text/css">
		#terms {
		    padding: 5px 15px 0px 15px;
		}
	</style>
	@endif

	{{-- new feature and improvement model --}}
	@if(Auth::check())
		@if(!empty($new_features) && !Auth::user()->new_features)
		<div class="modal fade pr-0" id="newfeature-popup" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close submit-newfeature" data-dismiss="modal">&times;</button>
						<div class="text-center">
							<img src="{{url('public/frontend/assets/img/Celebrate.png')}}" class="celebrate-img" width="100">
							<h4 class="modal-title bold-lable" id="bold-lable">{{$new_features->title}}</h4>
							<span>{{$new_features->sub_title}}</span>
						</div>
					</div>
					
					<div class="modal-body">
						{!! $new_features->description !!}
					</div>

					<div class="modal-footer submit-newfeature" data-dismiss="modal">
						Thanks For Being demo!
						{{-- <button type="button" class="btn d-inline m-btn-green" data-dismiss="modal">Thanks For Being demo!</button> --}}
					</div>

					{{ Form::close() }}
				</div>
			</div>
		</div>
		<script type="text/javascript">
			$('#newfeature-popup .tearms_title').hide();
			$('#newfeature-popup').modal('show');
		</script>
		@endif
		
	@endif
	@endif

	<script>
		$('document').ready(function(){
			var subscriber_id_updated = "{!! Session::get('subscriber_id_updated') !!}";
			if(subscriber_id_updated == "false") {
				setTimeout(function(){
					_aimtellGetSubscriberID().then(function(id) { 
						$.ajax({
							type: "POST",
							url: "{!! route('store_subscriber_id') !!}",
							dataType: 'json',
							data: { '_token': "{{csrf_token()}}",'subscriber_id': id},
							success: function(data) {
								//console.log(data);
							}
						});
					});
				},5000)
			}
		});
		
	</script>
	<script src="{{front_asset('assets/plugins/select2/js/select2.min.js')}}"></script>
	@yield('scripts')
	@if(Auth::check() && Auth::user()->is_premium_seller() == true)
		<input type="hidden" id="select_template_url_chat" value="{{url('seller/select_template')}}">
		<input type="hidden" id="update_template_url_chat" value="{{url('seller/update_template')}}">
		<input type="hidden" id="delete_template_url_chat" value="{{url('seller/delete_template')}}">
		<input type="hidden" id="template_for_chat" value="2">

		<!-- saveasteplate modal -->
		<div id="saveasteplate_chat" class="modal fade custompopup" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<!-- Modal content-->
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">Save As Template</h4>
					</div>
					<div class="modal-body">
						<form id="addTemplate">
							<input type="hidden" name="template_Data" id="template_Data">
							<div class="row">
								<div class="col-lg-12">
									<div class="form-group">
										<label>Title</label>
										<input type="text" class="form-control required" id="title" name="title" maxlength="50" placeholder="Enter Title">
									</div>
								</div>
								<div class="col-lg-12 create-new-service update-account text-right">
									<button type="button" class="btn btn-primary save_template_button">Save</button> 
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div id="tempalte_pop_chat" class="modal fade custompopup" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">Your Template</h4>
					</div>
					<div class="modal-body">
						<form id="update_template_seller_chat">
							<input type="hidden" name="template_id" id="template_id" value="" />
							<div class="row">
								<div class="col-lg-12">
									<div class="form-group">
										<label>Title</label>
										<input type="text" class="form-control" id="edit_title" name="edit_title" placeholder="Enter Title" disabled="">
									</div>
								</div>    
								<div class="col-lg-12">
									<div class="form-group">
										<label>Template</label>

										<div class="imoji_data">
											{{Form::textarea('tem_message','',["class"=>"form-control textarea-control","id"=>"tem_chat_message","placeholder"=>"Write your message here...","cols"=>50,"rows"=>10])}}
										</div>
									</div>    
								</div>
							</div>
							<div class="row">
								<div class="col-lg-6">
									<button type="button" class="btn btn-danger delete-tem delete_template_chat">Delete</button>
								</div>
								<div class="col-lg-6 create-new-service update-account text-right">
									<!-- <button type="button" class="btn btn-primary apply_template">Apply</button> -->
									<button type="submit" class="btn btn-primary submit_template update_template_seller_chat_submit" id="submit_template" >Apply & Update</button>
								</div>
							</div>

						</form>
					</div>
				</div>
			</div>
		</div>

		<div id="select_templet_chat" class="modal fade custompopup" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">Select Template</h4>
					</div>
					<div class="modal-body">
						{{Form::select('select_title',[""=>"Select Template"]+$save_template_chat_popup,null,['class'=>'form-control','id'=>'select_title_chat'])}}
					</div>
					<div class="modal-footer">
					</div>
				</div>
			</div>
		</div>
	@endif

	<!--begin::report chat as spam Modal-->
	<div class="modal fade custommodel chat_spam_report" id="chat_report_spam_modal_id" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Do you want to report this message as spam?</h5>

					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>

				{{ Form::open(['route' => 'report_as_spam', 'method' => 'POST', 'id' => 'formChatReportSpam']) }} 
				<input type="hidden" name="msg_secret" id="chat_report_spam_msg_secret_id">
				<div class="modal-body form-group">
					<div class="form-group">
						<div class="col-lg-12">
							<label for="recipient-name" class="form-control-label">By reporting this message as spam, you are notifying the demo team that you have been sent an unsolicited offer. A demo team member will promptly review and respond to this action.</label>
							<label for="recipient-name" class="form-control-label">Reason:</label>
						</div>
						<div class="col-lg-12">
							{{Form::textarea('reason','',["class"=>"form-control","placeholder"=>"Enter your reason here...","id"=>"reason",'maxlength'=>"2500",'rows' => 6])}}
							<div class="text-danger descriptions-error" id='show_error_report_spam_id' style="text-align: left;display:none;" >
								<strong>Hey!</strong> Please insert some reason for spam.
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					{!! Form::button('Report As Spam',['id' => 'submit_reportSpam_btn', 'class' => 'send-request-buttom']) !!}
					{!! Form::button('Never Mind',['id' => 'chat_spam_modal_close_id', 'class' => 'cancel-request-buttom']) !!}
				</div>
				{{ Form::close() }}
			</div>
		</div>
	</div>
	<!--end::report chat as spam Modal-->

	<!--begin::pick your own service Modal-->
	<div class="modal fade custompopup" id="select_your_service_modal_id" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Pick your service to share!</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body">
					<input type="hidden" id="chat_active_tab_id">
					<input type="hidden" id="chat_conversation_id">
					<div class="form">
						<div class="form-body">
							<div class="form-group chat_pick_service_div">
								<select name="search_service_term" class="form-control chat_pick_service select2-multiple">
									<option value=""></option>
								</select>
								<p class="error text-danger hide chat_pick_service_error">Please select service</p>
							</div>
						</div>
					</div>
					{{-- <h5>Please select service to see preview</h5> --}}
					<div class="service_card_preview"></div>
				</div>
				<div class="modal-footer">
					{!! Form::button('Send As Message',['id' => 'send_service_as_msg_btn', 'class' => 'send-request-buttom']) !!}
					{!! Form::button('Cancel',['id' => 'cancel_pick_service_modal_btn','class' => 'cancel-request-buttom']) !!}
				</div>
				
			</div>
		</div>
	</div>
	<!--end::pick your own service Modal-->

	<!--begin::upgrade order package Modal-->
	<div class="modal fade" id="upgrade_order_package_modal_id" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content border-radius-15px">
				<div class="modal-header modal-header-border-none border-0">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body p-0 border-0 px-3 px-md-5">
					<h3 class="font-weight-bold font-20 text-color-6 text-center ">Upgrade Package</h3>
					<p class="font-14 text-color-2 mt-3">Your current package for order #<span class="order_no_class"></span> is <b><span id="current_package_id"></span></b>. Select higher package to upgrade your order</p>
					<form action="{{route('upgrade_order_payment')}}" method="POST" id="upgrade_order_payment_frm">
						@csrf
						<input type="hidden" id="order_no_id" name="order_no">
						<select name="plan_id" class="form-control summary" id="upgrade_package_list_id">
							<option value="">Select Package</option>
						</select>
						<p class="error text-danger hide upgrade_order_package_error">Please select package</p>
					</form>
				</div>
				<div class="modal-footer border-0 px-3 px-md-5 justify-content-around">
					{!! Form::button('Cancel',['id' => 'cancel_upgrade_order_package_modal_btn','class' => 'btn text-color-1 bg-transparent']) !!}
					{!! Form::button('Submit',['id' => 'upgrade_order_package_submit_btn', 'class' => 'btn text-white bg-primary-blue border-radius-6px py-2 px-5']) !!}
				</div>
			</div>
		</div>
	</div>
	<!--end::upgrade order package Modal-->

	<!--begin::applied hidden pizza Modal-->
	<div class="modal fade custompopup" id="applied_hidden_pizza_modal" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
		<div class="modal-dialog modal-dialog-centered" role="document">
			<div class="modal-content p-3">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body text-center">
					<img src="{{url('public/frontend/assets/img/pizza_popup.png')}}" class="popup_pizza">
					<h1 class="text-primary applied_pizza_text_1">+ $6</h1>
					<h2>Woo Hoo!</h2>
					<h4>You found the hidden pizza!</h4>
					<div class="applied_pizza_text_2 mt-4">There may not be free pizza,but we just added <b>${{env('HIDDEN_PIZZA_AMOUNT')}}</b> to your demo Bucks so get yourself something nice!</div>
					<div class="applied_pizza_text_2">Come back again, there's always a pizza hiding somewhere random each day!</div>
				</div>
				<div class="modal-footer">
					<a href="javascript:location.reload();" class="btn btn-primary">Back to the Website</a>
				</div>
				
			</div>
		</div>
	</div>
	<!--end::applied hidden pizza Modal-->

	@if(Auth::check() &&  \Request::path() != 'messaging/conversations' && App\User::check_sub_user_permission('can_communicate_with_seller'))
		@if (Route::currentRouteName() == 'seller_orders_details' && isset($Order) && $Order->is_course == 0 || Route::currentRouteName() == 'buyer_orders_details' && isset($Order->is_course) && $Order->is_course == 0 || Route::currentRouteName() != 'seller_orders_details' && Route::currentRouteName() != 'buyer_orders_details')
				<div class="comment-btn-web"><i class="fas fa-comments"></i>
					<span class="message-count-badge {{ $unreadMessageCount == 0 ? 'display-none' : '' }}">{{ $unreadMessageCount }}</span>
				</div>
				<div class="chatify-main-container">
					<iframe id="chat-iframe" src="" height="0" width="0" title="Chat Window" style="border:none;"></iframe>
				</div>
				<div class="chatify_loader_clone">
					<img class="loading_gif" src="{{url('public/frontend/images/chat_loading.gif')}}">
				</div>
		@endif
	@endif

</body>

</html>
