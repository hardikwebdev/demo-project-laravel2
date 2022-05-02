@php
use App\Service;
use App\Category;
use App\Cart;
use App\User;
$categories = Category::withoutGlobalScope('is_custom')->with('menu_subcategory')
->where('menu_status',1)
->where('seo_url','!=','by-us-for-us')->orderBy('display_order', 'desc')->get();
if (Auth::check()) {

	$check_sub_user_permission = User::check_sub_user_permission_for_all();

	$is_notify = 1;
	$Notification = App\Notification::with('order', 'notifyby','service')->where(['notify_to' => Auth::user()->id/* , 'is_read' => 0 */])->where('is_delete',0)->where('type','!=','payment_failed');
	/*Check Block user*/
	$block_users = User::getBlockedByIds();
	if(count($block_users)>0){
		$Notification = $Notification->whereNotIn('notify_from',$block_users);
	}
	$Notification = $Notification->orderBy('id', 'desc')->limit(5)->get();
	foreach ($Notification as $key => $row) {
		$row->redirect_url = "javascript:void(0)";
		if(isset($row->order->id)){
			$isRejected = App\OrderExtendRequest::where('order_id',$row->order->id)->where('is_accepted','2')->count();
		}else{
			$isRejected = 0;
		}

		if($row->type == 'custom_order') {
			$Service = Service::select('*')->find($row->order_id);
			if(!isset($Service->seo_url)) { $Service->seo_url = ''; }

			if($Service->custom_order_buyer_uid == $row->notify_to) {
				$row->redirect_url = route('buyer_custom_order_details',$Service->seo_url);
			} else {
				$row->redirect_url = route('seller_custom_order_details',$Service->seo_url);
			}
		} else if($row->type == 'job_proposal_send') {
			$Service = 	Service::select('*')->find($row->order_id);
			if(!isset($Service->seo_url)) { $Service->seo_url = ''; }

			$row->redirect_url = route('show.job_detail',$Service->seo_url)."?notification_id".$row->id;
		} else {
			if($row->order_id != 0 && !empty($row->order)) {
				if($row->order->uid == $row->notify_to) {
					$row->redirect_url = route('buyer_orders_details',$row->order->order_no);
				} else {
					if($isRejected) {
						$row->redirect_url = route('seller_extended_order_request',$row->order->order_no);
					} else { 
						$row->redirect_url = route('seller_orders_details',$row->order->order_no);
					}
				}
			}
		}
	}

	$hCart = Cart::with('extra.service_extra','service.images','plan')
	->where(['uid'=>$parent_uid])
	->where('direct_checkout',0)
	->orderBy('id','desc')->get();

	$cartTotal = $selectedExtra = 0;
}
@endphp

@if (Auth::check() && Session::get('isAnyActiveService') > 0 && Auth::user()->parent_id == 0)
<div class="custom-header">
	<div class="custom-header-links">
		<ul class="pl-0">
			<li><a href="{{route('seller_orders').'?search=&status=active&from_date='}}"><i class="fa fa-flag"></i>{{Session::get('open_orders')}} open orders</a></li>
			@if(Session::get('orderDue') != '')
			<li><a href="{{url('seller/orders/details/'.Session::get('orderDueId'))}}"><i class="fa fa-clock-o"></i>Next Order Due in {{Session::get('orderDue')}}</a></li>
			@endif
			@if(Session::get('unansweredReviews')) 
			<li> 
				@if(Session::has('order_no') && Session::get('order_no') != '')
				<a href="{{route('seller_orders_details',Session::get('order_no'))}}?review=1"><i class="fa fa-comment"></i>{{Session::get('unansweredReviews')}} Reviews Unanswered</a>
				@else
				<a href="{{route('seller_orders').'?reviews='.Session::get('unansweredReviews')}}"><i class="fa fa-comment"></i>{{Session::get('unansweredReviews')}} Reviews Unanswered</a>
				@endif
			</li>
			@endif

			<li><a href="{{route('services')}}"><i class="fa fa-dollar"></i>New Promotion</a></li>
			
			@if($countOrderPurchaseService > 0)
			<li><a href="javascript:void(0)" class="copy_btn" data-clipboard-text="{{url('/')}}/promoteservice/{{Auth::user()->affiliate_id}}/{{$Service->id}}" "><i class="fa fa-link"></i>Affiliate Link This page</a></li>
			@elseif($countOrderPurchaseProfile > 0)
			<li><a href="javascript:void(0)" class="copy_btn" data-clipboard-text="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{Auth::user()->affiliate_id}}"><i class="fa fa-link"></i>Affiliate Link This page</a></li>
			@endif

			@if(Auth::check() && Auth::user()->is_premium_seller() == true)
			<li class="upgrade_none"><a  href="{{route('become_premium_seller')}}">Upgrade To Premium</a></li>
			@else
				@if(Auth::user()->is_sub_user() == false)
				<li><a  href="{{route('become_premium_seller')}}">Upgrade To Premium</a></li>
			@endif
			@endif

			@if(Auth::check() && Auth::user()->username == Request::segment(2) && Route::currentRouteName() == 'services_details')
			@php
				$current_service = App\Service::select('id','seo_url','current_step','status')->where('seo_url', Request::segment(3))->where('uid',Auth::id())->first();	
			@endphp
			<li>
				<a  href="{{route('overview_update',$current_service->seo_url)}}">
					<i class="fa fa-pencil" aria-hidden="true"></i>Edit Service
				</a>
			</li>
			@if($current_service->current_step >= 5 && ($current_service->status == 'draft' || $current_service->status == 'paused'))
			<li>
				<a  href="{{route('service_publish',$current_service->seo_url) . '?page=service_details'}}">
					{{-- <i class="fa fa-pencil" aria-hidden="true"></i> --}}Publish Service
				</a>
			</li>
			@endif
			@else
				@if($lateOrder != null ) 
				<li>
					<a  href="{{route('seller_orders')}}?status=late">
						Late Orders
					</a>
				</li>
				@endif
				@if($recuringOrder != null ) 
				<li>
					@if($recurring_order_for == 'seller')
					<a  href="{{route('seller_orders')}}?status=recursive&from_header=true">
						Recurring Orders
					</a>
					@else
					<a  href="{{route('buyer_orders')}}?status=recursive&from_header=true">
						Recurring Orders
					</a>
					@endif
				</li>
				@endif
			@endif
		</ul>
	</div>
</div>
@endif


<!-- login-Navigation -->  
@if (Auth::check())
<nav class="header navbar-light bg-light static-top login-user-menu mobile-hide">
	@if(can_show_hidden_pizza())
	<a class="navbar-brand apply_hidden_pizza" data-key="{{$pizza_verification_code}}" href="javascript:void(0)">
		<img src="{{url('public/frontend/assets/img/pizza_hidden.png')}}" class="hidden_pizza">
	</a>
	@endif
	<div class="container">
		<div class="row align-items-center login-menu">
			<div class="col-md-3 col-lg-3 col-12"> 
				@if(Auth::user()->web_dark_mode == 1)
				<a class="navbar-brand" href="{{url('/')}}"><img src="{{url('public/frontend/assets/img/logo/LogoHeader_DarkMode.png')}}" class="header-logo"></a>
				@else
				<a class="navbar-brand" href="{{url('/')}}"><img src="{{url('public/frontend/assets/img/logo/LogoHeader.png')}}" class="header-logo"></a>
				@endif
			</div>
			<div class="col-md-9 col-lg-9 col-12">
				<div class="">
					<nav class="navbar-expand-lg text-right" style="display:none;">
						<ul class="navbar-nav main-navigation primary-nav-dropdowns justify-content-end">
							@if((Auth::user()->address || Auth::user()->state) && Auth::user()->parent_id == 0)
							<li class="nav-item dropdown">
								<a href="javascript:void(0);" class="nav-link dropdown-toggle">
									Selling
								</a>
								<div class="dropdown-menu dropdown-content">
									<a class="dropdown-item" href="{{route('seller_orders')}}">Orders</a>
									<a class="dropdown-item" href="{{route('services')}}">Services</a>
									<a class="dropdown-item" href="{{route('mycourses')}}">Courses</a>
									<a class="dropdown-item" href="{{route('seller_coupons')}}">General Coupons</a>
									{{-- <a class="dropdown-item" href="{{route('earning')}}">Earning</a> --}}
									{{-- <a class="dropdown-item" href="{{route('withdraw_request_list')}}">Withdrawal History</a> --}}
									<a class="dropdown-item" href="{{route('seller_custom_order_request')}}">Custom Order Request</a>

									@if(empty(Auth::user()->subscription))
									<a class="dropdown-item" href="{{route('become_premium_seller')}}">Become A Premium Seller</a>
									@else
									<a class="dropdown-item" href="{{route('my_premium_subscription')}}">My Premium Subscription</a>
									@endif

									@if(Auth::check() && Auth::user()->is_premium_seller() == true)
									{{-- <a class="dropdown-item" href="{{route('sub_users')}}">Sub Users</a> --}}
									<a class="dropdown-item" href="{{route('view_analytics')}}">Analytics</a>
									@endif
								</div>
							</li>
							@elseif((Auth::user()->address || Auth::user()->state) && $check_sub_user_permission['allow_selling'] == true)
							<li class="nav-item dropdown">
								<a href="javascript:void(0);" class="nav-link dropdown-toggle">
									Selling
								</a>
								<div class="dropdown-menu dropdown-content">
									<a class="dropdown-item" href="{{route('seller_orders')}}">Orders</a>
									<a class="dropdown-item" href="{{route('services')}}">Services</a>
									<a class="dropdown-item" href="{{route('mycourses')}}">Courses</a>
									<a class="dropdown-item" href="{{route('seller_coupons')}}">General Coupons</a>
									<a class="dropdown-item" href="{{route('seller_custom_order_request')}}">Custom Order Request</a>
									
								</div>
							</li>
							@endif
							@if(!Auth::user()->address && !Auth::user()->state && Auth::user()->parent_id == 0)
							<li class="nav-item dropdown">
								<a href="{{route('accountsetting')}}" class="nav-link dropdown-toggle start-selling">
									Start Selling
								</a>
							</li>
							@endif

							@if(Auth::check() && Auth::user()->parent_id == 0)
							<li class="nav-item dropdown">
								<a href="javascript:void(0);" class="nav-link dropdown-toggle">
									Buying
								</a>
								<div class="dropdown-menu dropdown-content">
									<a class="dropdown-item" href="{{route('service_promo')}}">See Latest Deals <img src="{{front_asset('assets/img/celebrate-promo.png')}}" class="img-fluid  cus-nav-img" alt="profile-image"></a>
									<a class="dropdown-item" href="{{route('buyer_orders')}}">Orders</a>
									<a class="dropdown-item" href="{{route('buyer.mycourses')}}">My Courses</a>
									<a class="dropdown-item" href="{{route('transactions')}}">Transactions</a>
									<a class="dropdown-item" href="{{route('sponsered_transaction')}}">Sponsored Services Transactions</a>
									@if(Auth::check() && Auth::user()->is_premium_seller() == true)
									<a class="dropdown-item" href="{{ route('premium_transaction') }}">Premium User Transactions</a>
									@endif
									<a class="dropdown-item" href="{{route('favorites')}}">Favorites</a>
									<a class="dropdown-item" href="{{route('custom_order_request')}}">Custom Order Request</a>
									<a class="dropdown-item" href="{{route('bundle_cart')}}">Cart Bundles</a>
									{{-- <a class="dropdown-item" href="{{route('getUserDisputeOrders')}}">Manage Dispute</a> --}}
								</div>
							</li>
							@elseif($check_sub_user_permission['can_start_order'] == true || $check_sub_user_permission['can_make_purchases'] == true)
							<li class="nav-item dropdown">
								<a href="javascript:void(0);" class="nav-link dropdown-toggle">
									Buying
								</a>
								<div class="dropdown-menu dropdown-content">
									<a class="dropdown-item" href="{{route('buyer_orders')}}">Orders</a>
									@if($check_sub_user_permission['can_make_purchases'] == true)
									<a class="dropdown-item" href="{{route('custom_order_request')}}">Custom Order Request</a>
									@endif
								</div>
							</li>
							@endif
							@if(Auth::check() && Auth::user()->parent_id == 0)
								<li class="nav-item dropdown">
									<a href="javascript:void(0);" class="nav-link dropdown-toggle">
										Jobs
									</a>
									<div class="dropdown-menu dropdown-content">
										<a class="dropdown-item" href="{{route('jobs.create')}}">Post a Job</a>
										<a class="dropdown-item" href="{{route('browse.job')}}">Jobs</a>
										<a class="dropdown-item" href="{{route('jobs')}}">My Jobs</a>
										<a class="dropdown-item" href="{{route('seller.mybids')}}">My Proposals</a>
									</div>

									<a href="javascript:void(0);" class="nav-link dropdown-toggle submenu-title">
										FINANCIAL
									</a>
									<div class="dropdown-menu dropdown-content">
										@if((Auth::user()->address || Auth::user()->state) && Auth::user()->parent_id == 0)

										<a class="dropdown-item" href="{{route('earning')}}">Earnings</a>

										@endif

										@if(Auth::check() && Auth::user()->parent_id == 0)
										<a class="dropdown-item" href="{{route('earning')}}">Withdraw Funds</a>
										@endif
										
										<a class="dropdown-item" href="{{route('transactions')}}?deposite_wallet=true">Deposit Funds</a>
										
										@if((Auth::user()->address || Auth::user()->state) && Auth::user()->parent_id == 0)
										<a class="dropdown-item" href="{{route('withdraw_request_list')}}">Withdrawal History</a>
										@endif

										@if((Auth::user()->address || Auth::user()->state) && Auth::user()->parent_id == 0)
										<a class="dropdown-item" href="{{route('earning_report')}}">Reports</a>
										@endif
									</div>

								</li>
							@elseif($check_sub_user_permission['can_make_purchases'] == true)
								<li class="nav-item dropdown">
									<a href="javascript:void(0);" class="nav-link dropdown-toggle">Jobs</a>
									<div class="dropdown-menu dropdown-content">
										<a class="dropdown-item" href="{{route('browse.job')}}">Jobs</a>
										<a class="dropdown-item" href="{{route('jobs')}}">My Jobs</a>
										<a class="dropdown-item" href="{{route('seller.mybids')}}">My Proposals</a>
									</div>
								</li>	
							@endif
						</ul>    
					</nav>
				</div>
				<div class="row align-items-center">
					<div class="col-md-8">
						<form method="GET" action="{{route('services_view')}}">
							<div class="form-row header-search">
								<div class="col-md-12 col-lg-12 col-xl-12">
									@php
									$search = "";
									$search_by = "Services";
									$service_id = "";
									if (isset($_GET)) {
										if (isset($_GET['q'])) {
											$search = $_GET['q'];
										}
										if (isset($_GET['search_by'])) {
											$search_by = $_GET['search_by'];
										}
										if (isset($_GET['service_id'])) {
											$service_id = $_GET['service_id'];
										}
									}
									@endphp

									<div class="input-group bgsearch-grey  position-relative header_search_parent_div">
										<span class="input-group-btn home-page-search-btn">
											<button type="submit" class="btn btn-block bg-light-gray-f0f2 search-icon-btn btn-common-search"><i class="fa fa-search text-color-1" aria-hidden="true"></i></button>
										</span>

										<input type="text" class="form-control form-control-lg searchtext bg-light-gray-f0f2 border-none header_search_input" autocomplete="off" name="q" id="common_search"  placeholder="Search..." value="{{$search}}">

										<span class="close-icon hide">
											<i class="fas fa-times text-color-4"></i>
										</span>
								
										<span class="border-right-search"></span>
										<span class="input-group-btn">
											<input type="hidden" name="search_by" class="hid_search_by" value="{{$search_by}}" id="search_by">

											<input type="hidden" name="service_id" value="{{$service_id}}" id="hid_service_id">

											<button type="button" class="search-by btn-default dropdown-toggle text-color-6 font-weight-bold home-page-search-dropdown arrow-down-btn" data-toggle="dropdown">{{($search_by)?$search_by:'Services'}}</button>
											<ul class="dropdown-menu pull-right search_by_dropdown_menu">
												<li data-value="Services"><a href="javascript:void(0);" @if($search_by != 'Categories' && $search_by != 'Courses' && $search_by != 'Users') class="text-color-1" @endif>Services</a></li>
												<li data-value="Courses"><a href="javascript:void(0);" @if($search_by != 'Categories' && $search_by != 'Services' && $search_by != 'Users') class="text-color-1" @endif>Courses</a></li>
												<li data-value="Categories"><a href="javascript:void(0);" @if($search_by == 'Categories') class="text-color-1" @endif>Categories</a></li>
												<li data-value="Users"><a href="javascript:void(0);" @if($search_by == 'Users') class="text-color-1" @endif>Users</a></li>
											</ul>
										</span>
									</div>
									
								</div>
							</div>
						</form>
					</div>
					<div class="col">
						@if(Auth::check())
						<nav class="navbar-expand-lg text-right header-menu-item">
							<ul class="navbar-nav main-navigation justify-content-end">
								@if(Auth::user()->parent_id == 0)
								<li class="nav-item dropdown message-alert mr-2 custom bg-blue-hover dropdown-redesign">
									<a class="dropdown-toggle login-icon custom-dropdown-icon-size" id="notification_dropdown" data-toggle="dropdown">
										<img src="{{url('public/frontend/images/homepage log out/notification.png')}}" class="img-fluid p-2 blue-icon" alt="">
										@if($Notification->where('is_read',0)->count() > 0)
										<span class="notify-dot"></span>
										@endif
									</a>
									<div id="new_notification_section">
										<div class="dropdown-menu dropdown-content font-lato cus-overflow-y-1 dropdown-menu-center notification_div_content">
											<div class="row justify-content-between">
												<div class="col-auto">
													<p class="font-20 font-bold"> Notifications</p>
												</div>
												<div class="col-auto">
													<div class="d-flex align-items-center">
														<div class="">
															<div class="dropleft">
																<span class="cus-round--dropbtn dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
																<i class="fas fa-ellipsis-h cust-ellipsis-fa"></i>
																</span>
																<div class="dropdown-menu cus-dropdown-content" aria-labelledby="dropdownMenuButton">
																	<a class="dropdown-item all_notification_mark_as_read" href="javascript:void(0)">Mark all as read</a>
																	<a class="dropdown-item all_notification_clear" href="javascript:void(0)">Clear all</a>
																	<a class="dropdown-item" href="{{route('accountsetting')}}">Notification Settings</a>
																</div>
															</div>
														</div>
														<div class="close tex-color-1 font-14 ml-3">
															<a href="javascript:void(0)" class="close_notification_section"><i class=" fas fa-times"></i></a>
														</div>
													</div>
												</div>
											</div>
											@foreach($Notification as $row)
														
											<div class="row py-3 hover-bg-dark-grey-f2">    
												<div class="col-auto pr-2 text-center">
													<a href="{{$row->redirect_url}}" class="link-to notification_class" data-type="main">
														<img  src="{{get_user_profile_image_url($row->notifyby)}}" class="notification-profile-img img-fluid mt-1" alt="">
													</a>
												</div>
												<div class="col-9 pl-0">
													<div class="row">
														<div class="col-10">
															<a href="{{$row->redirect_url}}" class="link-to notification_class" data-type="main">
																<p class="font-12 text-color-2 font-16 font-weight-bold mb-0">
																	@if(isset($row->notifyby->username))
																	{{display_username($row->notifyby->username,15)}}
																	@else
																	demo
																	@endif
																</p>
																<p class="font-14 text-color-3 font-11 mb-0 text-truncate">{{strip_tags($row->message)}}</p>
																<p class="font-14 text-color-1 font-10 font-weight-bold mb-0">{{$row->updated_at->diffForHumans()}}</p>
															</a>
														</div>
														<div class="col-2 d-flex align-items-center">
															<div class="dropleft">
																<span class="cus-round--dropbtn  dropdown-toggle" type="button" id="dropdownMenuButton{{$row->id}}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
																<i class="fas fa-ellipsis-h cust-ellipsis-fa"></i>
																</span>
																<div class="dropdown-menu cus-dropdown-content" aria-labelledby="dropdownMenuButton{{$row->id}}">
																	@if($row->is_read == 0)
																	<button class="dropdown-item header_a_tag notification_class" data-type="mark_as_read" data-id="{{$row->id}}">Mark as Read</button>
																	@endif
																	<button class="dropdown-item header_a_tag notification_class" data-type="clear" data-id="{{$row->id}}">Clear</button>
																</div>
															</div>
															@if($row->is_read == 0)
															<span class="blue-dot ml-4"></span>
															@endif
														</div>
													</div>
												</div>
											</div>
											@endforeach
											@if(count($Notification)==0)
											<div class="row py-3 hover-bg-dark-grey-f2"><div class="col-12">No Notification Found</div></div>
											@endif
										</div>
									</div>
								</li>
								<li class="nav-item dropdown message-alert mr-2 custom bg-blue-hover">
									<a class="dropdown-toggle login-icon custom-dropdown-icon-size load-header-message">
										<img src="{{url('public/frontend/images/chat.png')}}" class="img-fluid p-2 blue-icon" alt="">
									</a>
									<div class="dropdown-menu dropdown-content dropdown-menu-center">
										<ul class="message-box header_message_list_div">
										</ul>
									</div>
								</li>
								@endif
								@if($check_sub_user_permission['can_make_purchases'] == true)
								<li class="nav-item dropdown message-alert mr-2 custom bg-blue-hover cusdrop-right">
									<a class="dropdown-toggle login-icon custom-dropdown-icon-size">
										<img src="{{url('public/frontend/images/cart.png')}}" class="img-fluid p-2 blue-icon" alt="">
									</a>
									<div class="dropdown-menu dropdown-content custom-header-cart border-radius-15px">
										<ul class="message-box">
										@if(count($hCart )  > 0 )
											@foreach($hCart as $row)
											@php 
											//Update price for review edition
											if($row->is_review_edition == 1){
												$row->plan->price = $row->plan->review_edition_price;
											}
											$cartTotal += ($row->plan->price * $row->quantity); 
											@endphp

											<li class="user-message custom"> 
												<div class="user-image custom-user-profile-width">
													@if($row->is_custom_order == 1)
													<img alt="product-image" class="profile-image"  src="{{ url('public/frontend/images/customorderthumb.jpg') }}">
													@elseif(isset($row->service->images[0]) && $row->service->images[0] !='')
														@if($row->service->images[0]->photo_s3_key != '')
														<img alt="product-image" class=""  src="{{$row->service->images[0]->media_url}}">
														@else
														<img alt="product-image" class=""  src="{{url('public/services/images/'.$row->service->images[0]->media_url)}}">
														@endif
													@else
														<img src="{{front_asset('assets/img/freelancer.png')}}" alt="profile-image">
													@endif
												</div>
												<div class="message-detail pad-left-10">
													<p class="text-capitalize">{{($row->service)?$row->service->title:''}}</p>
													<p class="timestamp">{{$row->plan->package_name}}</p>
													<p class="subject">{{$row->quantity}} x <span>$</span>{{$row->plan->price}}</p>
												</div>
											</li>

											@foreach($row->extra as $extra)
											@php 
											$selectedExtra += $extra->service_extra->price*$extra->qty;
											@endphp
											@endforeach

											@endforeach


											
											<!-- DROPDOWN ITEM -->
											<li class="user-message cart-box-pad">
												<div class="message-detail">
													<p>Total</p>
												</div>
												<div class="message-icon">
													${{$cartTotal+$selectedExtra}}
												</div>          
											</li>
											<!-- /DROPDOWN ITEM -->



											<li class="gradient">
												<div class="cart-btn-box">
													<a href="{{route('view_cart')}}" class="btn cart-menu-btn">View Cart</a>
													<a href="{{url('/')}}" class="btn shopping-btn">Continue Shopping</a>
												</div>
											</li>
										@else
											<li class="border-radius-15px bg-white p-4">
												<div class="text-center">
													<h1 class="text-color-6 font-weight-bold font-20">No Orders Yet</h1>
													<p class="text-color-4 font-14">Looks like you haven’t added any services to your cart yet.</p>
												</div>
											</li>
										@endif
										</ul>
									</div>
								</li>
								@endif
							</ul>    
						</nav>
						@endif
					</div>
					<div class="ml-2">
						<nav class="navbar-expand-lg text-right header-menu-item">
							<ul class="navbar-nav main-navigation">
								<li class="nav-item dropdown custom cus-menu-dropdown">
									<a href="javascript:void(0);" class="nav-item dropdown-toggle login-user-name pt-0 pb-0" id="menu_text_id">
										<img src="{{get_user_profile_image_url(Auth::user())}}" alt="profile-image" class='img-fluid rounded-circle profile-img-width-mobile'>
									</a>
									<ul class="dropdown-menu dropdown-content custom-user-profile-dropdown" id="li-remove-marker">
										<li>
											<div class="navbar-login">
												<div class="row">
													<div class="col-lg-4">
														<p class="text-center">
															<a href="{{route('accountsetting')}}">
																<img src="{{get_user_profile_image_url(Auth::user())}}" alt="profile-image" class='img-fluid headerProfile'>																
															</a>
														</p>
													</div>
													<div class="col-lg-8">
														<p class="text-left">
															<strong class="word-break">
																<a href="{{route('accountsetting')}}">
																	{{display_username(Auth::user()->Name)}}
																</a>
																@if(Auth::check() && Auth::user()->is_premium_seller() == true)
																<img src="{{ url('public/frontend/images/Badge.png') }}" alt="profile-image" class="premiumBadgeHeader" height="25"></img>
																@endif
															</strong>
														</p>
														@if(Auth::check() && Auth::user()->parent_id == 0)
														<p class="text-left">
															<a href="{{route('earning')}}" class="btn btn-primary btn-block btn-sm white">${{dispay_money_format(Auth::user()->earning)}}</a>
															@if(!Auth::user()->address && !Auth::user()->state)
															<a href="{{route('accountsetting')}}" class="btn btn-primary btn-block btn-sm white">Start Selling</a>
															@endif
														</p>
														@elseif(Auth::check() && $check_sub_user_permission['can_use_wallet_funds'] == true && $check_sub_user_permission['can_make_purchases'] == true)
														<p class="text-left">
															<a href="javascript:void(0)" class="btn btn-primary btn-block btn-sm white">${{dispay_money_format(Auth::user()->parent->earning)}}</a>
														</p>
														@endif
													</div>
												</div>
											</div>
										</li>
										<li class="nav-item dropdown">
											<div class="">
												@php 
													if(Auth::user()->parent_id == 0) {
														$myprofile = Auth::user()->username;
													} else {
														$myprofile = App\User::where('id',Auth::user()->parent_id)->select('username')->first();
														$myprofile = $myprofile['username'];
													}
												@endphp
												@if(Auth::check() && Auth::user()->parent_id == 0 && Auth::user()->promotional_fund > 0)
													<div class="dropdown-item">
														<div class="row">
															<div class="col-lg-6">
																<b>demo Bucks</b>
															</div>
															<div class="col-lg-6">
																<a href="{{route('earning')}}">
																	<button type="button" class="btn btn-outline-info btn-sm">${{dispay_money_format(Auth::user()->promotional_fund)}}</button>
																</a>
															</div>
														</div>
													</div>
												@endif
													
												<a class="dropdown-item" href="{{route('viewuserservices',$myprofile)}}">My Profile</a>
												<a class="dropdown-item" href="{{route('accountsetting')}}">Settings</a>

												<a class="dropdown-item" href="javascript:void(0)">
													Dark Mode
													<label class="dark_mode_switch mb-0 ml-1">
														<input type="checkbox" name="web_dark_mode" class="change_web_theme_mode" @if(Auth::user()->web_dark_mode == 1)checked @endif>
														<span class="dark_mode_slider dark_mode_round"></span>
													</label>
												</a>

												@if(Auth::check() && Auth::user()->is_premium_seller() == true)
												<a class="dropdown-item" href="{{route('canned_replay')}}">Manage Canned Replies</a>
												{{-- <a class="dropdown-item" href="{{route('bundle_cart')}}">Bundle Cart</a> --}}
												@endif

												@if($check_sub_user_permission['allow_selling'] == true)
												<a class="dropdown-item" href="{{route('affiliate_offers')}}">Affiliate Offers</a>
												@endif
											
												
												{{-- @if(Auth::check() && Auth::user()->parent_id == 0)
												<a class="dropdown-item" href="{{route('earning')}}">Withdraw Funds</a>
												@endif --}}

												{{-- <a class="dropdown-item" href="{{route('transactions')}}?deposite_wallet=true">Deposit Funds</a> --}}
												
												@if(Auth::check() && Auth::user()->is_sub_user() == false && $ordersReviewCount > 0 )
													<a class="dropdown-item" href="{{route('review_reminder_order')}}">Leave Reviews</a>
												@endif

												@if($check_sub_user_permission['allow_selling'] == true)
												<a class="dropdown-item" href="{{route('getUserDisputeOrders')}}">Manage Dispute</a>
												@endif

												@if(Auth::check() && Auth::user()->is_premium_seller() == true)
												<a class="become-premium upgrade_none" href="{{route('become_premium_seller')}}">Upgrade To Premium</a>
												@else
													@if(Auth::user()->is_sub_user() == false)
													<a class="become-premium" href="{{route('become_premium_seller')}}">Upgrade To Premium</a>
													@endif
												@endif
											</div>
										</li>
										<li>
											<div class="navbar-login navbar-login-session">
												<div class="row">
													<div class="col-lg-12 gradient">
														<p>
															<a href="{{url('/logout')}}" class="btn btn-danger btn-block">Logout</a>
														</p>
													</div>
												</div>
											</div>
										</li>
									</ul>
								</li>
							</ul> 
						</nav>                            
					</div> 
				</div>
			</div>
		</div>
	</div>
</nav> 
@endif

<!-- login-mobile-Navigation -->
@if (Auth::check())
<nav class="header navbar-light bg-light static-top mobile-login-user-menu mobile-show ">
	@if(can_show_hidden_pizza())
	<a class="navbar-brand apply_hidden_pizza" data-key="{{$pizza_verification_code}}" href="javascript:void(0)">
		<img src="{{url('public/frontend/assets/img/pizza_hidden.png')}}" class="hidden_pizza">
	</a>
	@endif
	<div class="container">
		<div class="row align-items-center justify-content-between login-menu">
			<div class="col-auto">
				<button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
					<img src="{{url('public/frontend/images/menu.png')}}" class="m-menu-icon" alt="">
				</button>
				<div class="collapse navbar-collapse cus-navbar-collapse w-100 overflow-auto" id="navbarNavDropdown">
					<div class="mt-4 text-right">
						<a class="px-3 py-2 cust-close-nav-btn" href="Javascript:;"><i class=" fas fa-times" aria-hidden="true"></i></a>
					</div>
					<ul class="navbar-nav main-navigation mt-3">
						@if(isset($categories))
						@foreach($categories as $category)
						<li class="nav-item dropdown">
							<div class="d-flex align-items-center justify-content-between">
								<a class="nav-link dropdown-toggle default-gray" href="{{get_category_menu_url($category)}}">
									{{$category->menu_display_name}}
								</a>
								<i class="fas fa-chevron-right cus-navigation-arrow px-3 text-color-4 font-18"></i>
							</div>
							<div class="dropdown-menu dropdown-content bg-white cus-mobile-dropdown cus-mobile-dropdown-content">
								@foreach($category->menu_subcategory as $subcategory)
								<a class="dropdown-item" href="{{get_subcategory_menu_url($category,$subcategory)}}">{{$subcategory->display_name}}</a>
								@endforeach
							</div>
						</li>
						@endforeach
						@endif
					</ul>
				</div>
			</div>
			<div class="col-auto"> 
				@if(Auth::user()->web_dark_mode == 1)
				<a class="navbar-brand mr-0" href="{{url('/')}}"><img src="{{url('public/frontend/assets/img/logo/LogoHeader_DarkMode.png')}}" class="header-logo"></a>
				@else
				<a class="navbar-brand mr-0" href="{{url('/')}}"><img src="{{url('public/frontend/assets/img/logo/LogoHeader.png')}}" class="header-logo"></a>
				@endif
			</div>
			<div class="col-auto">
				<nav class="navbar-expand-lg login-user-block">    
					<ul class="nav navbar-nav">
						<li class="nav-item dropdown">
							<a href="javascript:void(0);" class="nav-link dropdown-toggle login-user-name">
								<!-- Hello,<strong> {{display_username(Auth::user()->username)}}</strong> -->
								<img src="{{get_user_profile_image_url(Auth::user())}}" alt="profile-image" class="rounded-circle profile-img-width-mobile" alt="">
								<span class="glyphicon glyphicon-chevron-down"></span>
							</a>
							<ul class="dropdown-menu dropdown-content mobile-menu-overflow">
								<li>
									<div class="navbar-login">
										<div class="row">
											<div class="col-lg-4">
												<p class="text-center">
													<a href="{{route('accountsetting')}}">
														<img src="{{get_user_profile_image_url(Auth::user())}}" alt="profile-image" class='img-fluid user-img'>
													</a>
												</p>
											</div>
											<div class="col-lg-8">
												<p class="text-left">
													<strong>
														<a href="{{route('accountsetting')}}">
															{{display_username(Auth::user()->Name,30)}}
														</a>
													</strong>
												</p>
												@if(Auth::check() && Auth::user()->parent_id == 0)
												<p class="text-left mb-0">
													<a href="{{route('earning')}}" class="btn btn-primary btn-block btn-sm white">${{dispay_money_format(Auth::user()->earning)}}</a>
													@if(!Auth::user()->address && !Auth::user()->state)
													<a href="{{route('accountsetting')}}" class="btn btn-primary btn-block btn-sm white">Start Selling</a>
													@endif
												</p>
												@elseif(Auth::check() && $check_sub_user_permission['can_use_wallet_funds'] == true && $check_sub_user_permission['can_make_purchases'] == true)
													<p class="text-left">
														<a href="javascript:void(0)" class="btn btn-primary btn-block btn-sm white">${{dispay_money_format(Auth::user()->parent->earning)}}</a>
													</p>
												@endif
											</div>
										</div>
									</div>
								</li>

								@if((Auth::user()->address || Auth::user()->state) && Auth::user()->parent_id == 0)

								@if(Auth::check() && Auth::user()->parent_id == 0)
								<li class="nav-item dropdown">
									<div class="">
										@if(Auth::user()->promotional_fund > 0)
										<span class="dropdown-item"><b>demo Bucks</b>
											<a href="{{route('earning')}}">
												<button type="button" class="btn btn-outline-info btn-sm ml-2">${{dispay_money_format(Auth::user()->promotional_fund)}}</button>
											</a>
										</span>
										@endif
										<a class="dropdown-item" href="{{route('viewuserservices',$myprofile)}}">My Profile</a>
									</div>
								</li>
								@endif

								<li class="nav-item dropdown dropdown-submenu">
									<a class="cus-drop dropdown-item" href="javascript:void(0);">Selling <span class="caret"></span></a>
									<ul class="dropdown-menu cus-mobile-dropdown">
										<li><a class="dropdown-item" href="{{route('seller_orders')}}">Orders</a></li>
										<li><a class="dropdown-item" href="{{route('services')}}">Services</a></li>
										<li><a class="dropdown-item" href="{{route('mycourses')}}">Courses</a></li>
										<li><a class="dropdown-item" href="{{route('seller_coupons')}}">General Coupons</a></li>
										{{-- <li><a class="dropdown-item" href="{{route('earning')}}">Earnings</a></li> --}}
										{{-- <li><a class="dropdown-item" href="{{route('withdraw_request_list')}}">Withdrawal History</a></li> --}}
										
										<li><a class="dropdown-item" href="{{route('seller_custom_order_request')}}">Custom Order Request</a></li>

										@if(empty(Auth::user()->subscription))
										<li>
										<a class="dropdown-item" href="{{route('become_premium_seller')}}">Become A Premium Seller</a>
										</li>
										@else
										<li>
										<a class="dropdown-item" href="{{route('my_premium_subscription')}}">My Premium Subscription</a>
										</li>
										@endif

										@if(Auth::check() && Auth::user()->is_premium_seller() == true)
										{{-- <li>
											<a class="dropdown-item" href="{{route('sub_users')}}">Sub Users</a>
										</li> --}}
										<li>
											<a class="dropdown-item" href="{{route('view_analytics')}}">Analytics</a>
										</li>
										@endif
									</ul>
								</li>
								@elseif($check_sub_user_permission['allow_selling'] == true)
								<li class="nav-item dropdown dropdown-submenu">
									<a class="cus-drop dropdown-item" href="javascript:void(0);">Selling <span class="caret"></span></a>
									<ul class="dropdown-menu cus-mobile-dropdown">
										<li><a class="dropdown-item" href="{{route('seller_orders')}}">Orders</a></li>
										<li><a class="dropdown-item" href="{{route('services')}}">Services</a></li>
										<li><a class="dropdown-item" href="{{route('seller_coupons')}}">General Coupons</a></li>
										<li><a class="dropdown-item" href="{{route('seller_custom_order_request')}}">Custom Order Request</a></li>
									</ul>
								</li>
								@endif

								@if(Auth::check() && Auth::user()->parent_id == 0)
								<li class="nav-item dropdown dropdown-submenu">
									<a class="cus-drop dropdown-item" href="javascript:void(0);">Buying <span class="caret"></span></a>
									<ul class="dropdown-menu cus-mobile-dropdown">
										<li><a class="dropdown-item" href="{{route('service_promo')}}">See Latest Deals</a></li>
										<li><a class="dropdown-item" href="{{route('buyer_orders')}}">Orders</a></li>
										<li><a class="dropdown-item" href="{{route('buyer.mycourses')}}">My Courses</a></li>
										<li><a class="dropdown-item" href="{{route('transactions')}}">Transactions</a></li>
										<li><a class="dropdown-item" href="{{route('sponsered_transaction')}}">Sponsored Services Transaction</a></li>
										@if(Auth::check() && Auth::user()->is_premium_seller() == true)
										<li><a class="dropdown-item" href="{{ route('premium_transaction') }}">Premium User Transaction</a></li>
										@endif
										<li><a class="dropdown-item" href="{{route('favorites')}}">Favorites</a></li>
										<li><a class="dropdown-item" href="{{route('custom_order_request')}}">Custom Order Request</a></li>
										<li><a class="dropdown-item" href="{{route('bundle_cart')}}">Cart Bundles</a></li>
										{{-- <li>
											<a class="dropdown-item" href="{{route('getUserDisputeOrders')}}">Manage Dispute</a>
										</li> --}}
									</ul>
								</li>
								@elseif($check_sub_user_permission['can_start_order'] == true || $check_sub_user_permission['can_make_purchases'] == true)
								<li class="nav-item dropdown dropdown-submenu">
									<a class="cus-drop dropdown-item" href="javascript:void(0);">Buying <span class="caret"></span></a>
									<ul class="dropdown-menu cus-mobile-dropdown">
										<li><a class="dropdown-item" href="{{route('buyer_orders')}}">Orders</a></li>
										@if($check_sub_user_permission['can_make_purchases'] == true)
										<li><a class="dropdown-item" href="{{route('custom_order_request')}}">Custom Order Request</a></li>
										@endif
									</ul>
								</li>
								@endif

								@if(Auth::check() && Auth::user()->parent_id == 0)
								<li class="nav-item dropdown dropdown-submenu">
									<a class="cus-drop dropdown-item" href="javascript:void(0);">Jobs <span class="caret"></span></a>
									<ul class="dropdown-menu cus-mobile-dropdown">
										<li><a class="dropdown-item" href="{{route('jobs.create')}}">Post a Job</a></li>
										<li><a class="dropdown-item" href="{{route('browse.job')}}">Jobs</a></li>
										<li><a class="dropdown-item" href="{{route('jobs')}}">My Jobs</a></li>
										<li><a class="dropdown-item" href="{{route('seller.mybids')}}">My Proposals</a></li>
									</ul>
								</li>
								@elseif($check_sub_user_permission['can_make_purchases'] == true)
								<li class="nav-item dropdown dropdown-submenu">
									<a class="cus-drop dropdown-item" href="javascript:void(0);">Jobs <span class="caret"></span></a>
									<ul class="dropdown-menu cus-mobile-dropdown">
										<li><a class="dropdown-item" href="{{route('browse.job')}}">Jobs</a></li>
										<li><a class="dropdown-item" href="{{route('jobs')}}">My Jobs</a></li>
										<li><a class="dropdown-item" href="{{route('seller.mybids')}}">My Proposals</a></li>
									</ul>
								</li>
								@endif

								@if(Auth::user()->is_sub_user() == false)
								<li class="nav-item dropdown dropdown-submenu">
									<a class="cus-drop dropdown-item" href="javascript:void(0);">Financial <span class="caret"></span></a>
									<ul class="dropdown-menu cus-mobile-dropdown">
										@if((Auth::user()->address || Auth::user()->state) && Auth::user()->parent_id == 0)
										<li><a class="dropdown-item" href="{{route('earning')}}">Earnings</a></li>
										@endif
										
										@if(Auth::check() && Auth::user()->parent_id == 0)
										<li><a class="dropdown-item" href="{{route('earning')}}">Withdraw Funds</a></li>
										@endif

										<li><a class="dropdown-item" href="{{route('transactions')}}?deposite_wallet=true">Deposit Funds</a></li>
										
										@if((Auth::user()->address || Auth::user()->state) && Auth::user()->parent_id == 0)
										<li><a class="dropdown-item" href="{{route('withdraw_request_list')}}">Withdrawal History</a></li>
										@endif

										@if((Auth::user()->address || Auth::user()->state) && Auth::user()->parent_id == 0)
										<a class="dropdown-item" href="{{route('earning_report')}}">Reports</a>
										@endif

									</ul>
								</li>
								@endif

								<li class="nav-item dropdown">
									<div class="">
										@if(Auth::check() && Auth::user()->parent_id == 0)
										<a class="dropdown-item" href="{{route('view_cart')}}">Cart</a>
										<a class="dropdown-item" href="{{url('messaging/conversations')}}">Message</a>
										@endif
										
										@if($check_sub_user_permission['can_make_purchases'] == true)
										<a class="dropdown-item" href="{{route('view_cart')}}">Cart</a>
										@endif

										<a class="dropdown-item" href="{{route('accountsetting')}}">Settings</a>

										<a class="dropdown-item" href="javascript:void(0)">
											Dark Mode
											<label class="dark_mode_switch mb-0 ml-1">
												<input type="checkbox" name="web_dark_mode" class="change_web_theme_mode" @if(Auth::user()->web_dark_mode == 1)checked @endif>
												<span class="dark_mode_slider dark_mode_round"></span>
											</label>
										</a>

										@if(Auth::check() && Auth::user()->is_premium_seller() == true)
										<a class="dropdown-item" href="{{route('canned_replay')}}">Manage Canned Replies</a>
										@endif
										
										@if($check_sub_user_permission['allow_selling'] == true)
										<a class="dropdown-item" href="{{route('affiliate_offers')}}">Affiliate Offers</a>
										@endif
										
										@if(Auth::check() && Auth::user()->is_sub_user() == false && $ordersReviewCount > 0 )
											<a class="dropdown-item" href="{{route('review_reminder_order')}}">Leave Reviews</a>
										@endif
										
										@if($check_sub_user_permission['allow_selling'] == true)
										<a class="dropdown-item" href="{{route('getUserDisputeOrders')}}">Manage Dispute</a>
										@endif

									</div>
								</li>

								<li>
									<div class="navbar-login navbar-login-session">
										<div class="row">
											<div class="col-lg-12 gradient">
												<p>
													<a href="{{url('logout')}}" class="btn btn-danger btn-block">Logout</a>
												</p>
											</div>
										</div>
									</div>
								</li>
							</ul>
						</li>
					</ul> 
				</nav>                            
			</div>
		</div>
	</div>
</nav>
@endif

<!-- Navigation -->
@if (!Auth::check())
<nav class="header navbar-light bg-light static-top category-navigation">
	<div class="container">
		<div class="row align-items-center justify-content-between mob-row-full-col mob-cartinline">
			<div class="col-auto d-block d-lg-none pl-0 pl-sm-3">
				<button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
					<img src="{{url('public/frontend/images/menu.png')}}" alt="">
				</button>
				<div class="collapse navbar-collapse w-100 cus-navbar-collapse" id="navbarNavDropdown">
					<div class="mt-4 text-right">
						<a class="px-3 py-2 cust-close-nav-btn" href="Javascript:;"><i class=" fas fa-times" aria-hidden="true"></i></a>
					</div>
					<a class="btn text-white font-16 font-weight-bold bg-primary-blue d-block px-3 py-2 border-radius-6px w-50" href="{{url('/register')}}">Register</a>
					<a class="login btn font-16 text-color-1 font-weight-bold d-block mt-2 pl-0" href="{{url('/login')}}">Login</a>
					<ul class="navbar-nav main-navigation border-top-gray-2px mt-3 pt-3">
						@if(isset($categories))
						@foreach($categories as $category)
						<li class="nav-item dropdown">
							<div class="d-flex align-items-center justify-content-between">
								<a class="nav-link dropdown-toggle default-gray" href="{{get_category_menu_url($category)}}">
									{{$category->menu_display_name}}
								</a>
								<i class="fas fa-chevron-right cus-navigation-arrow px-3 text-color-4 font-18"></i>
							</div>
							<div class="dropdown-menu dropdown-content bg-white cus-mobile-dropdown cus-mobile-dropdown-content">
								@foreach($category->menu_subcategory as $subcategory)
								<a class="dropdown-item" href="{{get_subcategory_menu_url($category,$subcategory)}}">{{$subcategory->display_name}}</a>
								@endforeach
							</div>
						</li>
						@endforeach
						@endif
					</ul>
				</div>
			</div>
			<div class="col-auto col-lg-3"> 
				@if(Auth::user()->web_dark_mode == 1)
				<a class="navbar-brand" href="{{url('/')}}"><img src="{{url('public/frontend/assets/img/logo/LogoHeader_DarkMode.png')}}" class="header-logo"></a>
				@else
				<a class="navbar-brand" href="{{url('/')}}"><img src="{{url('public/frontend/assets/img/logo/LogoHeader.png')}}" class="header-logo"></a>
				@endif
			</div>
			<div class="col-auto col-lg-9 px-0 px-sm-3">
				<div class="row align-items-center justify-content-end">
					<!-- <div class="col-1 gradient">
						<a class="btn" href="{{route('browse.job')}}">Jobs</a>
					</div> -->
					<div class="col-lg-8 d-none d-lg-block">
						<form method="GET" action="{{route('services_view')}}">
							<div class="form-row header-search cus-web-search">
								@php
								$search = "";
								$search_by = "";
								$service_id = "";
								if (isset($_GET)) {
									if (isset($_GET['q'])) {
										$search = $_GET['q'];
									}
									if (isset($_GET['search_by'])) {
										$search_by = $_GET['search_by'];
									}
									if (isset($_GET['service_id'])) {
										$service_id = $_GET['service_id'];
									}
								}
								@endphp
								<div class="col-md-12 col-lg-12 col-xl-12">
									<div class="input-group  home-page-search bgsearch-grey position-relative header_search_parent_div">
									
										<span class="input-group-btn home-page-search-btn">
											<button type="submit" class="btn btn-block bg-light-gray-f0f2 search-icon-btn btn-common-m-search"><i class="fa fa-search text-color-1" aria-hidden="true"></i></button>
										</span>

										<input type="text" autocomplete="off" class="form-control form-control-lg searchtext bg-light-gray-f0f2 home-page-search-input header_search_input" id="common_search_m" name="q" placeholder="Search..." value="{{$search}}">

										<span class="close-icon hide">
											<i class="fas fa-times text-color-4"></i>
										</span>

										<span class="border-right-search"></span>
										<span class="input-group-btn">
											<input type="hidden" name="search_by" class="hid_search_by" value="{{($search_by)?$search_by:'Services'}}" id="search_by">

											<input type="hidden" name="service_id" value="{{$service_id}}" id="hid_m_service_id">

											<button type="button" class="form-control search-by btn-default dropdown-toggle text-color-6 font-weight-bold home-page-search-dropdown arrow-down-btn" data-toggle="dropdown">{{($search_by)?$search_by:'Services'}}</button>
											<ul class="dropdown-menu pull-right search_by_dropdown_menu">
												<li data-value="Services"><a href="javascript:void(0);" @if($search_by != 'Categories' && $search_by != 'Courses' && $search_by != 'Users') class="text-color-1" @endif>Services</a></li>
												<li data-value="Courses"><a href="javascript:void(0);" @if($search_by == 'Courses') class="text-color-1" @endif>Courses</a></li>
												<li data-value="Categories"><a href="javascript:void(0);" @if($search_by == 'Categories') class="text-color-1" @endif>Categories</a></li>
												<li data-value="Users"><a href="javascript:void(0);" @if($search_by == 'Users') class="text-color-1" @endif>Users</a></li>
											</ul>
										</span>
									</div>
								</div>
							</div>
						</form>
					</div>
					<div class="col-lg-4 header_cart_parent_div pl-0">
						<div class="text-right mob-pad-left-0">
							@if(!Auth::check())
								<ul class="message-box cart-message mb-0 main-navigation w-auto header-menu-item">
									<li class="nav-item dropdown message-alert custom cusdrop-right bg-blue-hover">
										<a class="dropdown-toggle login-icon custom-dropdown-icon-size d-none d-lg-block">
											<img src="{{url('public/frontend/images/cart.png')}}" class="img-fluid p-2 blue-icon" alt="">
										</a>
										<div class="dropdown-menu dropdown-content custom-header-cart no-pading border-radius-15px">
											<ul class="message-box">
												@if(count($sessionCart) > 0)
												
													@foreach($sessionCart as $row)
														@php 
														$service = Service::withoutGlobalScope('is_course')->select('id','title')->find($row['service_id']);
														array_push($service_id_list,$service->id);
														$servicePlan = Service::getServicePlan($row['plan_id'],$row['service_id']);
														
														//Update price for review edition
														if(isset($row['is_review_edition']) && $row['is_review_edition'] == 1){
															$servicePlan->price = $servicePlan->review_edition_price;
														}
														
														$getServiceAllPlan = Service::getServiceAllPlan($row['plan_id'],$row['service_id']);
														$cartTotal += ($row['quantity'] * $servicePlan->price); 
														@endphp

														<li class="user-message custom"> 
															<div class="user-image custom-user-profile-width">
																@if(isset($service->images[0]) && $service->images[0] !='')
																	@if($service->images[0]->photo_s3_key != '')
																	<img alt="product-image" class=""  src="{{$service->images[0]->media_url}}">
																	@else
																	<img alt="product-image" class=""  src="{{url('public/services/images/'.$service->images[0]->media_url)}}">
																	@endif
																@else
																	<img src="{{front_asset('assets/img/freelancer.png')}}" alt="profile-image">
																@endif
															</div>
															<div class="message-detail pad-left-10">
																<p class="text-capitalize">{{($service)?$service->title:''}}</p>
																<p class="timestamp">{{$servicePlan->package_name}}</p>
																<p class="subject">{{$row['quantity']}} x <span>$</span>{{$servicePlan->price}}</p>
															</div>
														</li>

														@foreach($row['extra'] as $extra)
															@php 
															$extraService = Service::ServiceExtra($extra['cart_extra_ids']);
															$selectedExtra += $extraService->price*$extra['quantity'];
															@endphp
														@endforeach
													@endforeach

													<!-- DROPDOWN ITEM -->
													<li class="user-message cart-box-pad">
														<div class="message-detail">
															<p>Total</p>
														</div>
														<div class="message-icon">
															${{$cartTotal+$selectedExtra}}
														</div>          
													</li>
													<!-- /DROPDOWN ITEM -->

													<li class="gradient">
														<div class="cart-btn-box">
															<a href="{{route('view_cart')}}" class="btn cart-menu-btn">View Cart</a>
															<a href="{{url('/')}}" class="btn shopping-btn">Continue Shopping</a>
														</div>
													</li>
												@else
													<li class="border-radius-15px bg-white p-4">
														<div class="text-center">
															<h1 class="text-color-6 font-weight-bold font-20">No Orders Yet</h1>
															<p class="text-color-4 font-14">Looks like you haven’t added any services to your cart yet.</p>
														</div>
													</li>
												@endif
											</ul>
										</div>
									</li>
								</ul>
							@endif
							<a class="font-14 font-weight-bold ml-2 d-none btn btn-outline-dark cust-login-dark-btn d-lg-inline-block px-3 bg-size-2" href="{{url('/login')}}">Login</a>
							<a class="btn text-white font-14 font-weight-bold bg-primary-blue border-radius-6px" href="{{url('/register')}}">Register</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</nav>
@endif

{{-- Display in Without Home Page --}}
<section class="main-header pb-1 stick-header">
	<div class="container">
		<nav class="navbar-expand-lg text-right">  
			<!-- <a class="navbar-toggler mt-show cus-search-icon" data-toggle="collapse" data-target="#navbarNavDropdownsearch" aria-controls="navbarNavDropdownsearch" aria-expanded="false" aria-label="Toggle navigation">
				<i class="fa fa-search" aria-hidden="true"></i>
			</a> -->
			<!-- <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
				<span class="navbar-toggler-icon"></span>
				<span class="navbar-toggler-icon"></span>
			</button> -->
			<div class="mt-show" id="navbarNavDropdownsearch">
				<form method="GET" class="mt-3 mb-3" action="{{route('services_view')}}">
					<div class="form-row header-search">
						@php
						$search = "";
						$search_by = "";
						$service_id = "";
						if (isset($_GET)) {
							if (isset($_GET['q'])) {
								$search = $_GET['q'];
							}
							if (isset($_GET['search_by'])) {
								$search_by = $_GET['search_by'];
							}
							if (isset($_GET['service_id'])) {
								$service_id = $_GET['service_id'];
							}
						}
						@endphp
						<div class="col-md-12 col-lg-12 col-xl-12">
							<div class="input-group  home-page-search bgsearch-grey position-relative header_search_parent_div">
									
								<span class="input-group-btn home-page-search-btn">
									<button type="submit" class="btn btn-block bg-light-gray-f0f2 search-icon-btn btn-common-m-search-mob"><i class="fa fa-search text-color-1" aria-hidden="true"></i></button>
								</span>

								<input type="text" autocomplete="off" class="form-control form-control-lg header_search_input searchtext common_search_mobile bg-light-gray-f0f2" id="common_search_mobile" name="q" placeholder="Search..."  value="{{$search}}">

								<span class="close-icon hide">
									<i class="fas fa-times text-color-4"></i>
								</span>

								<span class="border-right-search"></span>
								<span class="input-group-btn">
									<input type="hidden" name="search_by" class="hid_search_by search_by_mobile" value="{{($search_by)?$search_by:'Services'}}" id="search_by">

									<input type="hidden" name="service_id" value="{{$service_id}}" id="hid_m_service_id_mob">

									<button type="button" class="search-by btn-default dropdown-toggle home-page-search-dropdown arrow-down-btn" data-toggle="dropdown">{{($search_by)?$search_by:'Services'}}</button>
									<ul class="dropdown-menu pull-right search_by_dropdown_menu">
										<li data-value="Services"><a href="javascript:void(0);" @if($search_by != 'Categories' && $search_by != 'Users') class="text-color-1" @endif>Services</a></li>
										<li data-value="Courses"><a href="javascript:void(0);" @if($search_by == 'Courses') class="text-color-1" @endif>Courses</a></li>
										<li data-value="Categories"><a href="javascript:void(0);" @if($search_by == 'Categories') class="text-color-1" @endif>Categories</a></li>
										<li data-value="Users"><a href="javascript:void(0);" @if($search_by == 'Users') class="text-color-1" @endif>Users</a></li>
									</ul>
								</span>
								
								<!-- <span class="input-group-btn">
									<button type="submit" class="btn btn-block btn-lg btn-primary h-btn-search btn-common-m-search-mob"><i class="fa fa-search"></i> Search</button>
								</span> -->


								{{-- <div class="input-group-append">
									<select class="search-by" name="search_by" id="search_by">
										<option value="Services" {{($search_by=='Services'?'selected':'')}}>Services</option>
										<option value="Categories" {{($search_by=='Categories'?'selected':'')}}>Categories</option>
										<option value="Users" {{($search_by=='Users'?'selected':'')}}>Users</option>
									</select>
									<button type="submit" class="btn btn-block btn-lg btn-primary h-btn-search"><i class="fa fa-search"></i> Search</button>
								</div> --}}
							</div>
						</div>
					</div>
				</form>
			</div>

			<div class="collapse navbar-collapse cus-navbar-collapse desktop-browse-mega-menu" id="navbarNavDropdown1">
				<ul class="navbar-nav main-navigation">
					@if(isset($categories))
					@foreach($categories as $cat_key => $category)
					@php
					$dropdown_menu_right_class = '';
					if($cat_key == count($categories) || $cat_key == count($categories)-1 || $cat_key == count($categories)-2){
						$dropdown_menu_right_class = 'dropdown-menu-right';
					}
					@endphp
					<li class="nav-item dropdown">
						<a class="nav-link dropdown-toggle default-gray" href="{{get_category_menu_url($category)}}">
							{{$category->menu_display_name}}
						</a>
						<div class="dropdown-menu dropdown-content {{$dropdown_menu_right_class}}">
							<div class="d-flex">
								<div class="">
								@foreach($category->menu_subcategory as $key=>$subcategory)
								@if (count($category->menu_subcategory)>5)
									@php
									if (count($category->menu_subcategory)%2 == 0) {
										$subcategory_count = count($category->menu_subcategory)/2;
									}
									else {
										$subcategory_count = (count($category->menu_subcategory)/2)+1;
									}
									@endphp
									@if ($key != 0 && $key % $subcategory_count == 0)
										</div><div class="">
									@endif
								@endif
								<a class="dropdown-item" href="{{get_subcategory_menu_url($category,$subcategory)}}">{{$subcategory->display_name}}</a>
								@endforeach
								</div>
							</div>
						</div>
					</li>
					@endforeach
					@endif
				</ul>
			</div>
		</nav>
	</div>
</section> 

@auth
@if(Auth::user()->vacation_mode == 1)
<div class="alert-vacation-mode text-center">
	I am on vacation! <a href="{{route('endVacation')}}">End now</a> | <a href="{{route('accountsetting')}}">Vacation Settings</a>
</div>
@endif 

@if(Auth::User()->is_verify=='0')
<div class="alert-vacation-mode text-center">
	<img src="{{front_asset('images/icon.png')}}" class="resendemailalerticon">You need to activate your account ({{Auth::User()->email}}) | <a onclick='resendemail("{{route('resendemail')}}");' href="javascript:void(0)" id="resendemailbtn">Resend Email</a>
</div>
@endif

@if(Auth::User()->towfactorauth=='1' && Auth::User()->is_verify_towfactorauth=='0')
<div class="alert-vacation-mode text-center">
	<img src="{{front_asset('images/icon.png')}}" class="resendemailalerticon">You need to verify two factory authentication | <a href="{{route('security')}}#frmTowfactorAuth">Click here</a>
</div>
@endif

<!-- Notification header -->
@if(isset($NewOrder) && count($NewOrder)>0)
<div class="new-head-notifications text-center">
	<span class="icon-bell"></span>
	<a href="javascript:void(0)" class="new-order-title">Get New Orders</a>
	<a href="{{route('seller_orders_details',$NewOrder->order->order_no)}}" class="view-new-order-button">View</a>
</div>
@endif

@if(!empty($pendingReviewForReviewEdition))
<div class="alert-review-edition text-center">
	<img src="{{front_asset('images/icon.png')}}" class="resendemailalerticon">Please leave an honest and unbiased review for your recent Review Edition order before you can continue normal activity | <a href="{{route('buyer_orders_details',['id'=>$pendingReviewForReviewEdition->order_no,'review'=>1])}}">Click here</a>
</div>
@endif

@endauth




