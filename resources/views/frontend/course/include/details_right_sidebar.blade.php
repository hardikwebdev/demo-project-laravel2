@php
use App\User;
use App\Service;
$userObj = new User;
$influencer = isset($_GET['influencer'])?$_GET['influencer']:'';
$can_make_purchases = User::check_sub_user_permission('can_make_purchases');
$is_enable_monthly_plan = $Service->is_enable_course_monthly_plan();
$purchaseMonthlyDetails = Service::purchaseCourseDetails($Service->id,$parent_uid,1);
$purchaseLifeTimeDetails = Service::purchaseCourseDetails($Service->id,$parent_uid,0);
$is_softban = User::is_soft_ban();
$s_course_training_account = $userObj->is_course_training_account($Service);
@endphp
<div @if($is_mobile_device == false) class="course_sticky-position" @endif>
	<div class="card @if($is_mobile_device == false) course_mt-140 @endif">
		<div id="accordion-for-plan">
			@if($is_enable_monthly_plan == true)
			<div class="card card-bark-mode border-0">
				@php
				$cart_btn_plan = $Service->monthly_plans;
				@endphp
				<div class="p-2 bg-light-gray-f0 border" id="monthly-access-heading">
					<div class="d-flex align-items-center justify-content-between cursor-pointer" data-toggle="collapse" data-target="#target-monthly-access" aria-expanded="false" aria-controls="target-monthly-access">
						<h5 class="mb-0">
							<button type="button" class="btn font-16 course_text-black bg-transparent font-weight-bold shadow-none">
								MONTHLY ACCESS
							</button>
						</h5>
						<div>
							<p class="course_text-black font-16 font-weight-bold m-0 arrow-down-btn">
								${{$Service->monthly_plans->price}}
								<i class="fas fa-chevron-down arrow-down font-12 ml-2"></i>
							</p>
						</div>
					</div>
				</div>
				<div id="target-monthly-access" class="collapse show border-left border-right course_bg-white" aria-labelledby="monthly-access-heading" data-parent="#accordion-for-plan">
					<div class="p-3">
						<p class="course_text-black font-weight-bold font-14 m-0">{{$Service->title}}</p>

						<hr/>

						<p class="course_text-black font-weight-bold font-14 m-0">This course includes:</p>

						@if($on_demand_video)
						<div class="mt-2">
							<i class="fas fa-check course_text-light-green"></i>
							<span class="course_text-black font-14">{{ get_duration_heading($on_demand_video) }} on-demand video</span>
						</div>
						@endif

						<div class="mt-2">
							<i class="fas fa-check course_text-light-green"></i>
							<span class="course_text-black font-14">{{$total_articles}} articles</span>
						</div>
						<div class="mt-2">
							<i class="fas fa-check course_text-light-green"></i>
							<span class="course_text-black font-14">{{$Service->downloadable_resources->count()}} downloadable resources</span>
						</div>
						<div class="mt-2">
							<i class="fas fa-check course_text-light-green"></i>
							<span class="course_text-black font-14">Certificate of completion</span>
						</div>

						@if($is_admin == false) <!-- Begin : check is admin -->
							@if (Auth::check()) <!-- Begin : login check -->
								@if(!empty($purchaseMonthlyDetails)) <!-- Begin : Check user have already purchased this course -->
									<div class="mt-3 text-center font-16"><i class="fas fa-info-circle text-color-1 "></i> <em>You purchased this course on {{date('M d, Y',strtotime($purchaseMonthlyDetails->created_at))}}</em></div>
									<a href="{{route('buyer_orders_details',[$purchaseMonthlyDetails->order_no])}}" class="btn text-white font-16 course_bg-light-green w-100 mt-3 py-2">Go to course</a>
								
									<!-- Begin : Dispute Process -->
									@if($purchaseMonthlyDetails->is_dispute == 0 && $purchaseMonthlyDetails->status != 'cancelled' )
										
										@if(Auth::user()->parent_id == 0 && $purchaseMonthlyDetails->status != "completed" && $purchaseMonthlyDetails->subscription->is_payment_received == 1)
										@if( isset($purchaseMonthlyDetails->dispute_order) && $purchaseMonthlyDetails->dispute_order->status == "rejected" || !isset($purchaseMonthlyDetails->dispute_order))
										<div class="mt-2 text-md-right">
											<a class="text-color-1 font-14 font-weight-bold complete-monthly-order" href="javascript:void(0);" data-url="{{ route('complete.monthly_course', [$purchaseMonthlyDetails->id]) }}"> Cancel Subscription </a>
										</div>
										@endif
										@endif

									@else
										<div class="mt-2 text-md-right">
											<a class="text-color-1 font-14 font-weight-bold" href="{{ route('getUserDisputeOrders')}}">Manage Disputes</a>
										</div>
									@endif
									<!-- End : Dispute Process -->
								@else
									@if($can_make_purchases == true && $parent_uid != $Service->uid) <!-- Begin : check sub user have permission & owner-->
										@include('frontend.course.include.btn_add_to_cart')
									@endif <!-- End : check sub user have permission & owner-->
								@endif <!-- End : Check user have already purchased this course -->
							@else
								@include('frontend.course.include.btn_add_to_cart')
							@endif <!-- End : login check -->
						@endif <!-- End : check is admin -->

					</div>
					{{-- 
					<!-- Begin : Add to combo offer -->
					@if($is_admin == false && $bundleService && $otherService && $otherService->count() >= 1 && $userObj->is_premium_seller($Service->uid) ) <!-- Begin : check is admin, is bundle-->
						@if(Auth::check())
							@if(empty($purchaseMonthlyDetails) && $can_make_purchases == true && $parent_uid != $Service->uid)
							@include('frontend.course.include.btn_add_to_cart_combo')
							@endif
						@else
							@include('frontend.course.include.btn_add_to_cart_combo')
						@endif
					@endif <!-- End : check is admin, is bundle -->
					<!-- End : Add to combo offer -->
					--}}
				</div>
			</div>
			@endif

			<div class="card card-bark-mode border-0">
				@php
				$cart_btn_plan = $Service->lifetime_plans;
				@endphp
				<div class="p-2 bg-light-gray-f0 border" id="lifetime-access-heading">
					<div class="d-flex align-items-center justify-content-between cursor-pointer" data-toggle="collapse" data-target="#target-lifetime-access" aria-expanded="false" aria-controls="target-lifetime-access">
						<h5 class="mb-0">
							<button type="button" class="btn font-16 course_text-black bg-transparent font-weight-bold shadow-none">
								LIFETIME ACCESS
							</button>
						</h5>
						<div>
							<p class="course_text-black font-16 font-weight-bold m-0 arrow-down-btn">
								${{$Service->lifetime_plans->price}}
								<i class="fas fa-chevron-down arrow-down font-12 ml-2"></i>
							</p>
						</div>
					</div>
				</div>
				<div id="target-lifetime-access" class="collapse {{($is_enable_monthly_plan == false)?'show':''}} border-left border-right course_bg-white" aria-labelledby="lifetime-access-heading" data-parent="#accordion-for-plan">
					<div class="p-3">
						<p class="course_text-black font-weight-bold font-14 m-0">{{$Service->title}}</p>

						<hr/>

						<p class="course_text-black font-weight-bold font-14 m-0">This course includes:</p>

						@if($on_demand_video)
						<div class="mt-2">
							<i class="fas fa-check course_text-light-green"></i>
							<span class="course_text-black font-14">{{ get_duration_heading($on_demand_video) }} on-demand video</span>
						</div>
						@endif

						<div class="mt-2">
							<i class="fas fa-check course_text-light-green"></i>
							<span class="course_text-black font-14">{{$total_articles}} articles</span>
						</div>
						<div class="mt-2">
							<i class="fas fa-check course_text-light-green"></i>
							<span class="course_text-black font-14">{{$Service->downloadable_resources->count()}} downloadable resources</span>
						</div>
						<div class="mt-2">
							<i class="fas fa-check course_text-light-green"></i>
							<span class="course_text-black font-14">Certificate of completion</span>
						</div>
						@if($is_admin == false) <!-- Begin : check is admin -->
							@if (Auth::check()) <!-- Begin : login check -->
								@if(!empty($purchaseLifeTimeDetails)) <!-- Begin : Check user have already purchased this course -->
									<div class="mt-3 text-center font-16"><i class="fas fa-info-circle text-color-1 "></i> <em>You purchased this course on {{date('M d, Y',strtotime($purchaseLifeTimeDetails->created_at))}}</em></div>
									<a href="{{route('buyer_orders_details',[$purchaseLifeTimeDetails->order_no])}}" class="btn text-white font-16 course_bg-light-green w-100 mt-3 py-2">Go to course</a>
									
									<!-- Begin : Dispute Process -->
									@if($purchaseLifeTimeDetails->is_dispute == 0 && $purchaseLifeTimeDetails->status != 'cancelled' )

									@else
										@if($purchaseLifeTimeDetails->dispute_order->status == 'open' && $is_softban == 0)
										<div class="mt-2 text-md-right">
											<a class="text-color-1 font-14 font-weight-bold cancel-dispute" href="javascript:void(0);" data-url="{{ route('cancel_dispute', $purchaseLifeTimeDetails->dispute_order->id) }}"> Cancel Dispute </a>
										</div>
										@endif
										<div class="mt-2 text-md-right">
											<a class="text-color-1 font-14 font-weight-bold" href="{{ route('getUserDisputeOrders')}}">Manage Disputes</a>
										</div>
									@endif
									<!-- End : Dispute Process -->
								@else
									@if($can_make_purchases == true && $parent_uid != $Service->uid) <!-- Begin : check sub user have permission & owner-->
										@include('frontend.course.include.btn_add_to_cart')
									@endif <!-- End : check sub user have permission & owner-->
								@endif <!-- End : Check user have already purchased this course -->
							@else
								@include('frontend.course.include.btn_add_to_cart')
							@endif <!-- End : login check -->
						@endif <!-- End : check is admin -->
					</div>

					{{-- 
					<!-- Begin : Add to combo offer -->
					@if($is_admin == false && $bundleService && $otherService && $otherService->count() >= 1 && $userObj->is_premium_seller($Service->uid) ) <!-- Begin : check is admin, is bundle-->
						@if(Auth::check())
							@if(empty($purchaseLifeTimeDetails) && $can_make_purchases == true && $parent_uid != $Service->uid)
							@include('frontend.course.include.btn_add_to_cart_combo')
							@endif
						@else
							@include('frontend.course.include.btn_add_to_cart_combo')
						@endif
					@endif <!-- End : check is admin, is bundle -->
					<!-- End : Add to combo offer -->
					--}}

				</div>
			</div>
		</div>
	</div>

	<div class="card mt-4 border">
		<div class="p-4 course_bg-white">
			<div class='d-flex flex-column flex-md-row'>
				<!-- <div class='text-center'>
					<img src="{{url('public/frontend/images/Ellipse-3.png')}}" class='img-fluid w-60 h-60 rounded-circle' alt="">
				</div> -->
				<div class='text-center'>
					<a href="{{route('viewuserservices',$serviceUser->username)}}" target="_blank">
						<figure class="user-avatar">
							<img src="{{get_user_profile_image_url($serviceUser)}}" class='img-fluid w-60  rounded-circle' alt="">
							@if(time()-strtotime($serviceUser->last_login_at) <= 600 )
							<div class="course-seller-online-right"></div>
							@endif
						</figure>
					</a>
				</div>
				<div class="ml-3 text-center text-md-left">
					<a href="{{route('viewuserservices',$serviceUser->username)}}" target="_blank"><p class='font-18 text-color-2 font-weight-bold mb-1 mt-3 mt-md-0'>{{$serviceUser->username}}</p></a>
					<!-- <p class='font-16 text-secondary mb-1'>Web Developer And Teacher</p> -->
					<div class="d-flex align-items-center justify-content-center">
						{!! displayCourseUserRating($avg_seller_rating) !!}
						<!-- <span class="d-flex align-items-center">
							<img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid' alt="">
							<img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
							<img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
							<img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
							<img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
						</span> -->
						<span class="text-secondary font-14 ml-3">Reviews  &nbsp; ({{$total_seller_rating}})</span>
					</div>
					@if($serviceUser->timezone != "")
					<div class="mt-3">
						<i class="fas fa-clock text-color-3"></i>
						@php
							$currentTime = \Carbon\Carbon::now()->timezone($serviceUser->timezone)
						@endphp
						<span class="course_text-black font-14">{{$currentTime->format('H:i A')}} local time</span>
					</div>
					@endif
				</div>
			</div>
			
			<hr/>
			
			<div id="course_readmore-text-style-1">
				<p class="text-color-3 font-16 mt-3 mb-0 font-weight-bold">{{display_content($serviceUser->description,80)}}</p>
				<p id="readmore-text2" class="text-color-3 font-16 font-weight-bold mt-3">{{$serviceUser->description}}</p>
			</div>
			<div class="@if($is_admin != true) text-center @endif">
				<button class="btn text-color-2 border-dark mt-2 px-4 py-2 font-14 readmore-button2">Read More <i  class="fas fa-chevron-down ml-2"></i></button>
                <button class="btn text-color-2 border-dark mt-2 px-4 py-2 font-14 readmore-button2 hide">Read Less <i  class="fas fa-chevron-up ml-3"></i></button>
				{{-- Check admin preview --}}
				@if($is_admin != true && Auth::check() && $parent_uid != $serviceUser->id)
					<button class='btn bg-primary-blue text-white font-14 mt-2 px-4 py-2 ml-md-3 ml-lg-0 ml-xl-2 open-new-message open_user_chat' data-user="{{$serviceUser->secret}}">Contact Author</button>
				@endif
				@if(!Auth::check())
					<a href="{{url('login')}}" class="btn bg-primary-blue text-white font-14 mt-2 px-4 py-2 ml-md-3 ml-lg-0 ml-xl-2">Contact Author</a>
				@endif
			</div>
		</div>

		<div class="text-center border-top p-3 course_bg-white">
			<p class="text-color-4 font-14 mb-0">Course Updated {{date('F j, Y',strtotime($Service->last_updated_on))}}</p>
		</div>
	</div>
	
	{{-- Affiliate Course Section --}}
	@if($s_course_training_account == false)
	@include('frontend.course.include.affiliate_section')
	@endif

	{{-- Order Dispute process --}}
	@if($is_admin == false && $s_course_training_account == false) <!-- Begin : check is admin -->
		@if (Auth::check()) <!-- Begin : login check -->
			@if(!empty($purchaseMonthlyDetails)) <!-- Begin : Check user have already purchased this course -->
				@if($purchaseMonthlyDetails->is_dispute == 0 && $purchaseMonthlyDetails->status != 'cancelled' )
					@if($is_softban == 0 && $purchaseMonthlyDetails->subscription->is_payment_received == 1 && $purchaseMonthlyDetails->status != "completed")
					<div class="mt-4">
						<div class="p-3 p-md-2 summary p-lg-4">
							<p class="font-16 font-weight-bold text-color-2">Having issues? </p>
							<a class="btn bg-transparent text-color-1 border-radius-6px w-100 py-2 primary-outline-btn button-dispute" data-id="{{$purchaseMonthlyDetails->id}}" href="Javascript:;" data-toggle="modal" data-target="#disputeorderpopup"> Contact Support</a>
						</div>
					</div>
					@endif
				@endif
			@elseif(!empty($purchaseLifeTimeDetails)) <!-- Begin : Check user have already purchased this course -->
				@php
				$createdAt = Carbon::parse($purchaseLifeTimeDetails->created_at);
				$diffInDays = $createdAt->diffInDays(Carbon::now());
				@endphp
				<!-- Begin : Dispute Process -->
				@if($purchaseLifeTimeDetails->is_dispute == 0 && $purchaseLifeTimeDetails->status != 'cancelled' )
					@if($diffInDays <= 30 && $is_softban == 0)
					<div class="mt-4">
						<div class="p-3 p-md-2 summary p-lg-4">
							<p class="font-16 font-weight-bold text-color-2">Having issues? </p>
							<a class="btn bg-transparent text-color-1 border-radius-6px w-100 py-2 primary-outline-btn button-dispute" data-id="{{$purchaseLifeTimeDetails->id}}" href="Javascript:;" data-toggle="modal" data-target="#disputeorderpopup"> Contact Support</a>
						</div>
					</div>
					@endif
				@endif
			@endif
		@endif 
	@endif
			

</div>