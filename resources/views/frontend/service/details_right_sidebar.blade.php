@php
use App\User;
$userObj = new User;

$unAvailableMsg = 'Seller is unavailable';
$enableChatCustomOrder = true;
$allowbackOrder = $Service->allowBackOrder();

if($Service->status == 'paused'){
	$allowbackOrder = (object)['allow_back_order' => false, 'allow_back_order_msg' => '', 'can_place_order' => false];
	$unAvailableMsg = 'Temporarily unavailable';
	$enableChatCustomOrder = false;
}elseif($Service->status == 'denied'){
	if($Service->user->status == 0 || $Service->user->soft_ban == 1){
		$allowbackOrder = (object)['allow_back_order' => false, 'allow_back_order_msg' => '', 'can_place_order' => false];
		$unAvailableMsg = 'Temporarily unavailable';
		$enableChatCustomOrder = false;
	}
}

$influencer = isset($_GET['influencer'])?$_GET['influencer']:'';

$reviewEditions = isset($_GET['review-edition'])?$_GET['review-edition']:'';

$is_review_edition = false;
$affiliate_base = 'promoteservice';

if($reviewEditions == 1){
	if($Service->is_allow_review_edition() == true){
		$is_review_edition = true;
		$affiliate_base = 'revieweditionservice';
	}
}
$can_make_purchases = User::check_sub_user_permission('can_make_purchases');
@endphp
@php
	$quantity_show = false;
	if($Service->is_recurring == 0 && $is_review_edition == false && $Service->is_job == 0 && $Service->is_custom_order == 0 && $Service->is_course == 0){
		if(Auth::check()){
			if($can_make_purchases == true && $parent_uid != $Service->uid){
				$quantity_show = true;
			}
		}else{
			$quantity_show = true;
		}
	}
@endphp
<div class="sticky-block1 sidebar sidebar-overflow">
	<div class="accordion md-accordion" id="accordionEx__{{$i}}" role="tablist" aria-multiselectable="true">

		@if(App\User::checkPremiumUser($Service->user->id) == true)
			<div class="premiumuserbanner"><span>Premium Seller<igl></igl></span></div>

		@else
		@endif

		@if($Service->three_plan == "1")

		<!-- Accordion card -->
		@if(isset($Service->premium_plans))
		<div class="card">
			<!-- Card header -->
			<div class="card-header" role="tab" id="premium1">
				<a data-toggle="collapse" data-parent="#accordionEx__{{$i}}" href="#premium_{{$i}}" aria-expanded="true" aria-controls="premium">
					<h6 class="mb-0 packagetitle">
						{{$Service->premium_plans->package_name}}
					</h6>
					<h6 class="packageprice">
						@if($is_review_edition == true)
						<span class="re-text-strike">${{$Service->premium_plans->price}} <i class="fa fa-angle-down rotate-icon"></i></span>
						<span class="re-discounted-price">${{$Service->premium_plans->review_edition_price}} </span>
						@else
						<span>${{$Service->premium_plans->price}} <i class="fa fa-angle-down rotate-icon"></i></span>
						@endif
					</h6>
				</a>
			</div>
			<!-- Card body -->
			<div id="premium_{{$i}}" class="collapse show" role="tabpanel" aria-labelledby="headingOne1" data-parent="#accordionEx__{{$i}}">
				<div class="card-body">
					<div class="d-flex justify-content-between">
						<h6 class="">Includes</h6>
						@if($quantity_show == true)
						@php
							$service_plan_id = $Service->premium_plans->id;
							$show_quantity_section = true; 
						@endphp
						@include('frontend.service.quantity_selector')
						@endif
					</div>

					@if($Service->premium_plans->no_of_revisions != null)
					<li>@if($Service->premium_plans->no_of_revisions == -1) Unlimited @else {{$Service->premium_plans->no_of_revisions}} x @endif revisions</li>
					@endif
					<li>{{$Service->premium_plans->offering_details}}</li>
					<div class="detail-point">
						<div class="detail-point-one">
							@if($Service->is_recurring == 0)
							<img src="{{front_asset('assets/img/clock.png')}}" class="img-fluid"> {{$Service->premium_plans->delivery_days}} days delivery
							@endif
						</div>
						<div class="detail-point-two">
							<span><i class="fas fa-lock"></i> Secure Transaction</span>
						</div>
					</div>

					@if($is_review_edition == false && $userObj->is_premium_seller($Service->uid))
					@foreach($Service->volume_discount as $key => $value)
					<div class="text-success">Buy {{$value->volume}} get {{$value->discount}}% discount</div>
					@endforeach
					@endif

					<div class="premium-btn">

							@if($allowbackOrder->can_place_order == true)
							{{ Form::open(['route' => ['cart_customize'], 'method' => 'POST']) }}
							<input type="hidden" name="id" value="{{$Service->id}}">
							<input type="hidden" name="plan_id" value="{{$Service->premium_plans->id}}">
							<input type="hidden" name="influencer" value="{{$influencer}}">
							<input type="hidden" name="is_review_edition" value="{{$is_review_edition}}">
							
							<input type="hidden" name="utm_source" class="utm_source">
							<input type="hidden" name="utm_term" class="utm_term">
							
							@if($quantity_show == true)
								@php
									$service_plan_id = $Service->premium_plans->id;
									$show_quantity_section = false; 
								@endphp
								@include('frontend.service.quantity_selector')
							@endif

							@if($Service->is_recurring == 1)	
							<label style="color: gray">Recurring Service</label>
							@endif

							@if (Auth::check())
								@if($can_make_purchases == true && $parent_uid != $Service->uid)

									@if($allowbackOrder->allow_back_order == true)
									<div class="order-in-queue">{{$Service->getExpectedDeliveredDays('premium_plans')->estimated_delivered_days_msg}}</div>
									@else
									<div class="mt-2"></div>
									@endif

									<button class="checkButton btn btn-success mt-0">Add to Cart</button>
									{{-- BEGIN - Quick checkout --}}
									@if($is_extra_available)
									<button name="direct_checkout" value="1" class="btn bg-primary-blue mt-3 quick-checkout-btn">Quick Checkout</button>
									@endif
									{{-- END - Quick checkout --}}
								@endif
							@else
								@if($allowbackOrder->allow_back_order == true)
								<div class="order-in-queue">{{$Service->getExpectedDeliveredDays('premium_plans')->estimated_delivered_days_msg}}</div>
								@else
								<div class="mt-2"></div>
								@endif

								<a href="{{url('login')}}?service_id={{$Service->premium_plans->id}}" class="btn btn-success cookie-cart-save register-login-modal cookie_cart_{{$Service->premium_plans->id}}" data-service_id="{{$Service->id}}" data-plan_id="{{$Service->premium_plans->id}}" data-influencer="{{$influencer}}" data-quantity="" data-is_review_edition="{{$is_review_edition}}">
									Add to Cart
								</a>
								{{-- BEGIN - Quick checkout --}}
								@if($is_extra_available)
									<button type="sumbit" class="quick-checkout-btn d-none" name="direct_checkout" value="1" id="quick-checkout-{{$Service->premium_plans->id}}">Checkout</button>
									<button type="button" class="btn bg-primary-blue mt-3 quick-checkout-btn without-login-quick-checkout-btn" data-id="quick-checkout-{{$Service->premium_plans->id}}">Quick Checkout</button>
								@endif
								{{-- END - Quick checkout --}}
							@endif
							{{ Form::close() }}

							@else

							@if(Auth::check())
								@if(Auth::user()->parent_id == 0 && Auth::user()->id != $Service->uid)
								<a href="javascript::void(0);" class="btn btn-success cart-btn-gray mt-2 disabled" >
									{{$unAvailableMsg}}
								</a>
								@endif
							@else
								<a href="javascript::void(0);" class="btn btn-success cart-btn-gray mt-2 disabled" >
									{{$unAvailableMsg}}
								</a>
							@endif

							@endif

					</div>   
				</div>

				<!-- Combo Offer Discount Card Start -->
				@if($allowbackOrder->can_place_order == true)
				@if(Auth::check())
					@if($Service->user->is_delete == 0 && $can_make_purchases == true && $Service->is_delete == 0)
						@if ($parent_uid != $Service->uid)
							@if($buddleservice && $otherservice && $otherservice->count() >= 1)
								@if($userObj->is_premium_seller($Service->uid))
								<div class="user-detail">
									<h6>Combo Offer Discount</h6>
									<div class="detail-point">
										<p class="text-oneline">Also buy 
											@if($otherservice->count() == 1)
												this service @else
												these services
											@endif
										</p>

										@foreach($otherservice as $service)
										<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
										@endforeach

										<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>

											@if($allowbackOrder->can_place_order == true)
											<div class="premium-btn">
												{{ Form::open(['route' => ['cart_customize_combo'], 'method' => 'POST']) }}

												<input type="hidden" name="id" value="{{$Service->id}}">
												<input type="hidden" name="package" value="premium">
												<input type="hidden" name="bundle_id" value="{{$buddleservice->bundle_id}}">
												<input type="hidden" name="utm_source" class="utm_source">
												<input type="hidden" name="utm_term" class="utm_term">

												<input type="hidden" name="plan_id" value="{{$Service->premium_plans->id}}">
												<input type="hidden" name="is_review_edition" value="{{$is_review_edition}}">
												@if($quantity_show == true)
													@php
														$service_plan_id = $Service->premium_plans->id;
														$show_quantity_section = false; 
													@endphp
													@include('frontend.service.quantity_selector')
												@endif

												@if($can_make_purchases == true && $parent_uid != $Service->uid)
													<button class="checkButton btn btn-success">Add Combo Service </button>
												@endif
												{{ Form::close() }}
											</div>
											@endif
									</div>
								</div>
								@endif
							@endif
						@endif
					@endif
				@else
					<!--shows default button for guest user without login for purchasing combo services-->
					@if($buddleservice && $otherservice && $otherservice->count() >= 1)
						<div class="user-detail">
							<h6>Combo Offer Discount</h6>
							<div class="detail-point">
								<p class="text-oneline">Also buy 
									@if($otherservice->count() == 1)
									this service @else
									these services
								@endif</p>

								@foreach($otherservice as $service)
								<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
								@endforeach
								
								<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>

								<div class="premium-btn">
									{{-- <input type="hidden" name="id" value="{{$Service->id}}">
									<input type="hidden" name="package" value="premium">
									<input type="hidden" name="bundle_id" value="{{$buddleservice->bundle_id}}">
									<input type="hidden" name="plan_id" value="{{$Service->premium_plans->id}}"> --}}

									@if($allowbackOrder->can_place_order == true)
										<!-- <a href="{{url('login')}}?combo_plan_id={{$Service->premium_plans->id}}&bundle_id={{$buddleservice->bundle_id}}&packageType=premium" class="btn btn-success">Add Combo Service </a> -->

										<a href="{{url('login')}}?combo_plan_id={{$Service->premium_plans->id}}&bundle_id={{$buddleservice->bundle_id}}&packageType=premium" data-quantity="" data-id="{{$Service->id}}"   data-combo_plan_id="{{$Service->premium_plans->id}}"  data-bundle_id="{{$buddleservice->bundle_id}}" data-is_review_edition="{{$is_review_edition}}" data-packageType="premium" class="btn btn-success cookie-cart-combo-save cookie_cart_{{$Service->premium_plans->id}}">Add Combo Service </a>


									@endif

								</div>
							</div>
						</div>
					@endif
				@endif
				@endif
				<!-- Combo Offer Discount Card End -->

			</div>
		</div>
		@endif
		<!-- Accordion card -->

		<!-- Accordion card -->
		@if(isset($Service->standard_plans))
		<div class="card">
			<!-- Card header -->
			<div class="card-header" role="tab" id="standard1">
				<a class="collapsed" data-toggle="collapse" data-parent="#accordionEx__{{$i}}" href="#standard_{{$i}}" aria-expanded="false" aria-controls="collapseTwo2">
					<h6 class="mb-0 packagetitle">
						{{$Service->standard_plans->package_name}}
					</h6>
					<h6 class="packageprice">
						@if($is_review_edition == true)
						<span class="re-text-strike">${{$Service->standard_plans->price}} <i class="fa fa-angle-down rotate-icon"></i></span>
						<span class="re-discounted-price">${{$Service->standard_plans->review_edition_price}} </span>
						@else
						<span>${{$Service->standard_plans->price}} <i class="fa fa-angle-down rotate-icon"></i></span>
						@endif
					</h6>
				</a>
			</div>
			<!-- Card body -->
			<div id="standard_{{$i}}" class="collapse" role="tabpanel" aria-labelledby="standard1" data-parent="#accordionEx__{{$i}}">
				<div class="card-body">
					<div class="d-flex justify-content-between">
						<h6 class="">Includes</h6>
						@if($quantity_show == true)
						@php
							$service_plan_id = $Service->standard_plans->id;
							$show_quantity_section = true; 
						@endphp
						@include('frontend.service.quantity_selector')
						@endif
					</div>
					@if($Service->standard_plans->no_of_revisions != null)
					<li>{{$Service->standard_plans->no_of_revisions}}x revisions</li>
					@endif
					<li>{{$Service->standard_plans->offering_details}}</li>
					<div class="detail-point">
						<div class="detail-point-one">
							@if($Service->is_recurring == 0)
							<img src="{{front_asset('assets/img/clock.png')}}" class="img-fluid"> {{$Service->standard_plans->delivery_days}} days delivery
							@endif
						</div>
						<div class="detail-point-two">
							<span><i class="fas fa-lock"></i> Secure Transaction</span>
						</div>
					</div>

					@if($is_review_edition == false && $userObj->is_premium_seller($Service->uid))
					@foreach($Service->volume_discount as $key => $value)
					<div class="text-success">Buy {{$value->volume}} get {{$value->discount}}% discount</div>
					@endforeach
					@endif

					<div class="premium-btn">

						@if($allowbackOrder->can_place_order == true)

							{{ Form::open(['route' => ['cart_customize'], 'method' => 'POST']) }}
							<input type="hidden" name="id" value="{{$Service->id}}">
							<input type="hidden" name="plan_id" value="{{$Service->standard_plans->id}}">
							<input type="hidden" name="influencer" value="{{$influencer}}">
							<input type="hidden" name="is_review_edition" value="{{$is_review_edition}}">
							<input type="hidden" name="utm_source" class="utm_source">
							<input type="hidden" name="utm_term" class="utm_term">
							@if($quantity_show == true)
								@php
									$service_plan_id = $Service->standard_plans->id;
									$show_quantity_section = false; 
								@endphp
								@include('frontend.service.quantity_selector')
							@endif
							
							@if($Service->is_recurring == 1)	
							<label style="color: gray">Recurring Service</label>
							@endif

							@if(Auth::check())
								@if($can_make_purchases == true && $parent_uid != $Service->uid)

									@if($allowbackOrder->allow_back_order == true)
									<div class="order-in-queue">{{$Service->getExpectedDeliveredDays('standard_plans')->estimated_delivered_days_msg}}</div>
									@else
									<div class="mt-2"></div>
									@endif

									<button class="checkButton btn btn-success mt-0">Add to Cart</button>
									{{-- BEGIN - Quick checkout --}}
									@if($is_extra_available)
										<button name="direct_checkout" value="1" class="btn bg-primary-blue mt-3 quick-checkout-btn">Quick Checkout</button>
									@endif
									{{-- END - Quick checkout --}}
								@endif
							@else

								@if($allowbackOrder->allow_back_order == true)
								<div class="order-in-queue">{{$Service->getExpectedDeliveredDays('standard_plans')->estimated_delivered_days_msg}}</div>
								@else
								<div class="mt-2"></div>
								@endif

								<a href="{{url('login')}}?service_id={{$Service->standard_plans->id}}" class="btn btn-success cookie-cart-save register-login-modal cookie_cart_{{$Service->standard_plans->id}}" data-service_id="{{$Service->id}}" data-plan_id="{{$Service->standard_plans->id}}" data-influencer="{{$influencer}}" data-quantity="" data-is_review_edition="{{$is_review_edition}}">Add to Cart</a>

								{{-- BEGIN - Quick checkout --}}
								@if($is_extra_available)
									<button type="sumbit" class="quick-checkout-btn d-none" name="direct_checkout" value="1" id="quick-checkout-{{$Service->standard_plans->id}}">Checkout</button>
									<button type="button" class="btn bg-primary-blue mt-3 quick-checkout-btn without-login-quick-checkout-btn" data-id="quick-checkout-{{$Service->standard_plans->id}}">Quick Checkout</button>
								@endif
								{{-- END - Quick checkout --}}

							@endif
							{{ Form::close() }}

						@else

							@if(Auth::check())
								@if(Auth::user()->parent_id == 0 && Auth::user()->id != $Service->uid)
								<a href="javascript::void(0);" class="btn btn-success cart-btn-gray mt-2 disabled" >
									{{$unAvailableMsg}}
								</a>
								@endif
							@else
								<a href="javascript::void(0);" class="btn btn-success cart-btn-gray mt-2 disabled" >
									{{$unAvailableMsg}}
								</a>
							@endif

						@endif

					</div>   
				</div>

				<!-- Combo Offer Discount Card Start -->
				@if($allowbackOrder->can_place_order == true)
				@if(Auth::check())
					@if($Service->user->is_delete == 0 && $can_make_purchases == true)
						@if ($parent_uid != $Service->uid)
							@if($buddleservice && $otherservice && $otherservice->count() >= 1)
								@if($userObj->is_premium_seller($Service->uid))
								<div class="user-detail">
									<h6>Combo Offer Discount</h6>
									<div class="detail-point">
										<p class="text-oneline">Also buy 
											@if($otherservice->count() == 1)
											this service @else
											these services
										@endif</p>

										@foreach($otherservice as $service)
										<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
										@endforeach

										<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>

										@if($allowbackOrder->can_place_order == true)
										<div class="premium-btn">
											{{ Form::open(['route' => ['cart_customize_combo'], 'method' => 'POST']) }}

											<input type="hidden" name="id" value="{{$Service->id}}">
											<input type="hidden" name="package" value="standard">
											<input type="hidden" name="bundle_id" value="{{$buddleservice->bundle_id}}">
											<input type="hidden" name="utm_source" class="utm_source">
											<input type="hidden" name="utm_term" class="utm_term">

											<input type="hidden" name="plan_id" value="{{$Service->standard_plans->id}}">
											<input type="hidden" name="is_review_edition" value="{{$is_review_edition}}">
											
											@if($quantity_show == true)
												@php
													$service_plan_id = $Service->standard_plans->id;
													$show_quantity_section = false; 
												@endphp
												@include('frontend.service.quantity_selector')
											@endif

											@if($can_make_purchases == true && $parent_uid != $Service->uid)
												<button class="checkButton btn btn-success">Add Combo Service </button>
											@endif
											{{ Form::close() }}
										</div>
										@endif

									</div>
								</div>
								@endif
							@endif
						@endif
					@endif
				@else
					<!--shows default button for guest user without login for purchasing combo services-->
					@if($buddleservice && $otherservice && $otherservice->count() >= 1)
						<div class="user-detail">
							<h6>Combo Offer Discount</h6>
							<div class="detail-point">
								<p class="text-oneline">Also buy 
									@if($otherservice->count() == 1)
									this service @else
									these services
								@endif</p>
							
								@foreach($otherservice as $service)
								<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
								@endforeach
								
								<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>

								<div class="premium-btn">
								{{-- <input type="hidden" name="id" value="{{$Service->id}}">
								<input type="hidden" name="package" value="standard">
								<input type="hidden" name="bundle_id" value="{{$buddleservice->bundle_id}}">
								<input type="hidden" name="plan_id" value="{{$Service->standard_plans->id}}"> --}}

								@if($allowbackOrder->can_place_order == true)
									<!-- <a href="{{url('login')}}?combo_plan_id={{$Service->standard_plans->id}}&bundle_id={{$buddleservice->bundle_id}}&packageType=standard" class="btn btn-success">Add Combo Service </a> -->

									<a href="{{url('login')}}?combo_plan_id={{$Service->standard_plans->id}}&bundle_id={{$buddleservice->bundle_id}}&packageType=standard" class="btn btn-success cookie-cart-combo-save cookie_cart_{{$Service->standard_plans->id}}" data-quantity="" data-id="{{$Service->id}}"  data-combo_plan_id="{{$Service->standard_plans->id}}"  data-bundle_id="{{$buddleservice->bundle_id}}" data-is_review_edition="{{$is_review_edition}}" data-packageType="standard"  >Add Combo Service </a>
								@endif
								</div>
							</div>
						</div>
					@endif
				@endif
				@endif
				<!-- Combo Offer Discount Card End -->

			</div>
		</div>
		@endif
		<!-- Accordion card -->
		@endif

		<!-- Accordion card Start-->
		@if(isset($Service->basic_plans))
		<div class="card">
			<div class="card-header" role="tab" id="basic1">
				<a class="collapsed" data-toggle="collapse" data-parent="#accordionEx__{{$i}}" href="#basic_{{$i}}" aria-expanded="false" aria-controls="collapseThree3">
					<h6 class="mb-0 packagetitle">
						{{$Service->basic_plans->package_name}}
					</h6>
					<h6 class="packageprice">
						@if($is_review_edition == true)
						<span class="re-text-strike">${{$Service->basic_plans->price}} <i class="fa fa-angle-down rotate-icon"></i></span>
						<span class="re-discounted-price">${{$Service->basic_plans->review_edition_price}} </span>
						@else
						<span>${{$Service->basic_plans->price}} <i class="fa fa-angle-down rotate-icon"></i></span>
						@endif
					</h6>
				</a>
			</div>
			<!-- Card body -->

			@if(!isset($Service->premium_plans))
			<div id="basic_{{$i}}" class="collapse show" role="tabpanel" aria-labelledby="basic1" data-parent="#accordionEx__{{$i}}">
				@else
				<div id="basic_{{$i}}" class="collapse" role="tabpanel" aria-labelledby="basic1" data-parent="#accordionEx__{{$i}}">
					@endif
					<div class="card-body">
						<div class="d-flex justify-content-between">
							<h6 class="">Includes</h6>
							@if($quantity_show == true)
							@php
								$service_plan_id = $Service->basic_plans->id;
								$show_quantity_section = true; 
							@endphp
							@include('frontend.service.quantity_selector')
							@endif
						</div>


						@if($Service->basic_plans->no_of_revisions != null)
						<li>{{$Service->basic_plans->no_of_revisions}}x revisions</li>
						@endif
						<li>{{$Service->basic_plans->offering_details}}</li>
						<div class="detail-point">
							<div class="detail-point-one">
								@if($Service->is_recurring == 0)
								<img src="{{front_asset('assets/img/clock.png')}}" class="img-fluid"> {{$Service->basic_plans->delivery_days}} days delivery
								@endif
							</div>
							<div class="detail-point-two">
								<span><i class="fas fa-lock"></i> Secure Transaction</span>
							</div>
						</div>

						@if($is_review_edition == false && $userObj->is_premium_seller($Service->uid))
						@foreach($Service->volume_discount as $key => $value)
						<div class="text-success">Buy {{$value->volume}} get {{$value->discount}}% discount</div>
						@endforeach
						@endif

						<div class="premium-btn">

							@if($allowbackOrder->can_place_order == true)

								{{ Form::open(['route' => ['cart_customize'], 'method' => 'POST']) }}
								<input type="hidden" name="id" value="{{$Service->id}}">
								<input type="hidden" name="plan_id" value="{{$Service->basic_plans->id}}">
								<input type="hidden" name="influencer" value="{{$influencer}}">
								<input type="hidden" name="is_review_edition" value="{{$is_review_edition}}">
								<input type="hidden" name="utm_source" class="utm_source">
								<input type="hidden" name="utm_term" class="utm_term">
								
								@if($quantity_show == true)
								@php
									$service_plan_id = $Service->basic_plans->id;
									$show_quantity_section = false; 
								@endphp
								@include('frontend.service.quantity_selector')
								@endif

								@if($Service->is_recurring == 1)	
								<label style="color: gray">Recurring Service</label>
								@endif
									
								@if(Auth::check())
									@if($can_make_purchases == true && $parent_uid != $Service->uid)
										@if($allowbackOrder->allow_back_order == true)
										<div class="order-in-queue">{{$Service->getExpectedDeliveredDays('basic_plans')->estimated_delivered_days_msg}}</div>
										@else
										<div class="mt-2"></div>
										@endif

										<button class="checkButton btn btn-success mt-0">Add to Cart</button>
										{{-- BEGIN - Quick checkout --}}
										@if($is_extra_available)
											<button name="direct_checkout" value="1" class="btn bg-primary-blue mt-3 quick-checkout-btn">Quick Checkout</button>
										@endif
										{{-- END - Quick checkout --}}
									@endif
								@else
									@if($allowbackOrder->allow_back_order == true)
									<div class="order-in-queue">{{$Service->getExpectedDeliveredDays('basic_plans')->estimated_delivered_days_msg}}</div>
									@else
									<div class="mt-2"></div>
									@endif

									<a href="{{url('login')}}?service_id={{$Service->basic_plans->id}}" class="btn btn-success cookie-cart-save register-login-modal cookie_cart_{{$Service->basic_plans->id}}" data-quantity="" data-service_id="{{$Service->id}}" data-plan_id="{{$Service->basic_plans->id}}" data-influencer="{{$influencer}}" data-is_review_edition="{{$is_review_edition}}">Add to Cart</a>
									{{-- BEGIN - Quick checkout --}}
									@if($is_extra_available)
										<button type="sumbit" class="quick-checkout-btn d-none" name="direct_checkout" value="1" id="quick-checkout-{{$Service->basic_plans->id}}">Checkout</button>
										<button type="button" class="btn bg-primary-blue mt-3 quick-checkout-btn without-login-quick-checkout-btn" data-id="quick-checkout-{{$Service->basic_plans->id}}">Quick Checkout</button>
									@endif
									{{-- END - Quick checkout --}}
									
								@endif
								{{ Form::close() }}

							@else

								@if(Auth::check())
									@if(Auth::user()->parent_id == 0 && Auth::user()->id != $Service->uid)
									<a href="javascript::void(0);" class="btn btn-success cart-btn-gray mt-2 disabled" >
										{{$unAvailableMsg}}
									</a>
									@endif
								@else
									<a href="javascript::void(0);" class="btn btn-success cart-btn-gray mt-2 disabled" >
										{{$unAvailableMsg}}
									</a>
								@endif

							@endif

						</div>   
					</div>

					<!-- Combo Offer Discount Card Start -->
					@if($allowbackOrder->can_place_order == true)
					@if(Auth::check())
						@if($Service->user->is_delete == 0 && $can_make_purchases == true)
							@if ($parent_uid != $Service->uid)
								@if($buddleservice && $otherservice && $otherservice->count() >= 1)
									@if($userObj->is_premium_seller($Service->uid))
									<div class="user-detail">
										<h6>Combo Offer Discount</h6>
										<div class="detail-point">
											<p class="text-oneline">Also buy 
												@if($otherservice->count() == 1)
												this service @else
												these services
											@endif</p>

											@foreach($otherservice as $service)
											<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
											@endforeach

											<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>

											@if($allowbackOrder->can_place_order == true)
												<div class="premium-btn">
													{{ Form::open(['route' => ['cart_customize_combo'], 'method' => 'POST']) }}

													<input type="hidden" name="id" value="{{$Service->id}}">
													<input type="hidden" name="package" value="basic">
													<input type="hidden" name="bundle_id" value="{{$buddleservice->bundle_id}}">
													<input type="hidden" name="utm_source" class="utm_source">
													<input type="hidden" name="utm_term" class="utm_term">

													<input type="hidden" name="plan_id" value="{{$Service->basic_plans->id}}">
													<input type="hidden" name="is_review_edition" value="{{$is_review_edition}}">
													@if($quantity_show == true)
													@php
														$service_plan_id = $Service->basic_plans->id;
														$show_quantity_section = false; 
													@endphp
													@include('frontend.service.quantity_selector')
													@endif
													
													@if($can_make_purchases == true && $parent_uid != $Service->uid)
														<button class="checkButton btn btn-success">Add Combo Service </button>
													@endif
													{{ Form::close() }}
												</div>
											@endif


										</div>
									</div>
									@endif
								@endif
							@endif
						@endif
					@else
					<!--shows default button for guest user without login for purchasing combo services-->
					@if($buddleservice && $otherservice && $otherservice->count() >= 1)
						<div class="user-detail">
							<h6>Combo Offer Discount</h6>
							<div class="detail-point">
								<p class="text-oneline">Also buy 
									@if($otherservice->count() == 1)
									this service @else
									these services
								@endif</p>
							
								@foreach($otherservice as $service)
								<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
								@endforeach
							
								<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>
								<div class="premium-btn">
									
									{{-- <input type="hidden" name="id" value="{{$Service->id}}">
									<input type="hidden" name="package" value="basic">
									<input type="hidden" name="bundle_id" value="{{$buddleservice->bundle_id}}">
									<input type="hidden" name="plan_id" value="{{$Service->basic_plans->id}}"> --}}


									@if($allowbackOrder->can_place_order == true)
										<!-- <a href="{{url('login')}}?combo_plan_id={{$Service->basic_plans->id}}&bundle_id={{$buddleservice->bundle_id}}&packageType=basic" class="btn btn-success">Add Combo Service </a> -->
										<a href="{{url('login')}}?combo_plan_id={{$Service->basic_plans->id}}&bundle_id={{$buddleservice->bundle_id}}&packageType=basic" class=" cookie-cart-combo-save btn btn-success cookie_cart_{{$Service->basic_plans->id}}" data-quantity="" data-id="{{$Service->id}}" data-combo_plan_id="{{$Service->basic_plans->id}}"  data-bundle_id="{{$buddleservice->bundle_id}}" data-is_review_edition="{{$is_review_edition}}" data-packageType="basic" >Add Combo Service </a>
									@endif
									
								</div>
							</div>
						</div>
					@endif
					@endif
					@endif
					<!-- Combo Offer Discount Card End -->

				</div>
			</div>
			@endif
			<!-- Accordion card End-->

		</div>

		<!-- User wrapper Start--> 
		<div class="user-detail">
			<div class="avtar">
				<div class="avtar-img">
					<a href="{{route('viewuserservices',$Service->user->username)}}">
						<figure class="user-avatar small">
							<img src="{{get_user_profile_image_url($Service->user)}}" alt="profile-image">
							@if(time()-strtotime($Service->user->last_login_at) <= 600 )
							<div class="seller-online"></div>
							@endif

						</figure>
					</a>
				</div>
				<div class="avtar-detail">
					<a href="{{route('viewuserservices',$Service->user->username)}}">
						<div class="custom-text-header">
							<span>{{$Service->user->username}}</span>
							@if(App\User::checkPremiumUser($Service->user->id) == true)

							{{-- <img src="{{ url('public/frontend/images/Badge.png') }}" alt="profile-image" class="premiumBadgeHeader1" height="15"></img> --}}

							@else
							<i class="fa fa-check" aria-hidden="true"></i>
							@endif
							<div>
								@if($Service->user->seller_level != 'Unranked')
								{{$Service->user->seller_level}}
								@endif
							</div>
						</div>
					</a>
				</div>
			</div>
			<div class="detail-point">
				<div class="detail-point-one">
					demo Rating
				</div>
				<div class="detail-point-two">
					<div class="user-review">
						{{-- @php
						$seller_rating = $objOrder->calculateSellerAverageRating($Service->user->id);
						@endphp --}}
						{!! displayRating($avg_seller_rating ,$showFiveStar = 2) !!}
					</div>
				</div>
			</div>
			<div class="user-detail-paragraph">
				<div class="about-title">About</div> 
				<div class="text show-more-height">
					<p>{{$Service->user->description}}</p>
				</div>
				<div class="show-more">+ More</div>
				<div class="general-info">
					<div class="about-title">General info</div>
					<table class="general-info-table">
						<tr>
							<td><i class="fas fa-map-marker-alt"></i></td>
							<td class="general-info-title">From</td>
							<td class="general-info-val">{{$Service->user->city}} {{$Service->user->state}} {{($Service->user->country) ? ",".$Service->user->country->country_code : ""}}</td>
						</tr>
						<tr>
							<td><i class="fas fa-user"></i></td>
							<td class="general-info-title">Member Since</td>
							<td class="general-info-val">{{date('F jS, Y',strtotime($Service->user->created_at))}}</td>
						</tr>

					{{-- <tr>
						<td><i class="fa fa-archive"></i></td>
						<td class="general-info-title">Recent Delivery</td>
						<td class="general-info-val">About 2 hours</td>
					</tr> --}}

					@if (Auth::check() && $Service->user->last_login_at != '0000-00-00 00:00:00')
					<tr>
						<td><i class="fa fa-clock"></i></td>
						<td class="general-info-title">Last Signed On</td>
						<td class="general-info-val">{{get_time_ago(strtotime($Service->user->last_login_at))}}</td>
					</tr>
					@endif
					
					@if(isset($Service->user->timezone) && $Service->user->timezone)
					@php
						$currentdate = new DateTime("now", new DateTimeZone($Service->user->timezone));
					@endphp
					<tr>
						<td><i class="fa fa-clock"></i></td>
						<td colspan="2" class="general-info-title">It's currently {{$currentdate->format('h:i A')}} here</td>
					</tr>
					@endif
				</table>      
			</div>
			@if (Auth::check() && $Service->user->is_delete == 0 && $enableChatCustomOrder == true)
			@if ($parent_uid != $Service->uid)
			<div class="row">
				<div class="col-md-12 col-xl-12 cus-order">

					@if(User::check_sub_user_permission('can_communicate_with_seller'))
					<button class="btn custom-order-btn-mail open_chat" data-user="{{$Service->user->secret}}" data-service="{{$Service->secret}}"><i class="fa fa-comment"></i></button>    
					@endif

					@if($can_make_purchases == true)
					<button type="button" class="btn custom-order-btn" data-toggle="modal" data-target="#customorder">Custom Order</button> 
					@endif

				</div>
			</div>
			@endif
			@endif

			<!--shows default button for login and custom order for guest user to redirect it to login page-->
			@if(!Auth::check() && $enableChatCustomOrder == true)
				<div class="row">
				<div class="col-md-12 col-xl-12 cus-order">

					<a href="{{url('login')}}?sendmsg={{$Service->id}}" class="btn custom-order-btn-mail custom-order-link"><i class="fa fa-comment "></i></a>    

					<a href="{{url('login')}}?customOrder={{$Service->id}}" class="btn custom-order-btn custom-order-link">Custom Order</a> 

				</div>
			</div>
			@endif

			@if (Auth::check() && Auth::user()->id == $Service->uid)
			<div class="row custom">
				<div class="col-md-4 col-xl-4 col-sm-12 cus-order display_block">
					<a href="{{route('overview_update',$Service->seo_url)}}"><button type="button" class="btn custom-order-btn custom-full-width" data-toggle="modal" data-target="#customorder">Edit</button></a>
				</div>

				@if($Service->current_step >= 5 && ($Service->status == 'draft' || $Service->status == 'paused'))
				<div class="col-md-4 col-xl-4 col-sm-12 cus-order display_block">
					<a href="{{route('service_publish',$Service->seo_url) . '?page=service_details'}}"><button type="button" class="btn custom-order-btn custom-full-width">Publish</button></a>
				</div>
				@endif

				<div class="col-md-4 col-xl-4 col-sm-12 cus-order display_block">
					<a href="{{route('boostService',$Service->seo_url)}}"><button type="button" class="btn custom-order-btn custom-full-width" data-toggle="modal" data-target="#customorder">Promote</button></a>
				</div>
				
			</div>
			@endif
		</div>
	</div>
	<!-- User wrapper End--> 
	{{-- Share and Earn Start --}}
	@php
	$responce = $Service->get_affiliate_discount($Service);
	@endphp
	@if (Auth::check() && $Service->user->is_delete == 0)
		<!-- Condition for premium user who enable affiliate link section need to display -->
		@if((App\User::checkPremiumUser($Service->user->id) == true && ($Service->is_affiliate_link==1)) || (App\User::checkPremiumUser($Service->user->id) == false))
			<div class="user-detail">
				<h6>Share & Earn {{$responce['percentage']}}%  Cash Back!</h6>
				<div class="detail-point">
					<p class="text-oneline">Earn A {{$responce['percentage']}}% Commission By Sharing This Service</p>
					<p class="text-oneline">Here’s Your Service Affiliate Link</p>
					@if(Auth::user()->is_sub_user() == false)
					<div class="form-group affiliate-form gradient">
						<input type="text" class="form-control" readonly="" value="{{url($affiliate_base.'/'.Auth::user()->affiliate_id.'/'.$Service->secret)}}" aria-describedby="basic-addon1">
						<button type="submit" class="btn btn-primary copy_btn" data-clipboard-text="{{url($affiliate_base.'/'.Auth::user()->affiliate_id.'/'.$Service->secret)}}">Copy</button> 
					</div>
					
					@elseif($dataUser!=null)
					<div class="form-group affiliate-form gradient">
						<input type="text" class="form-control" readonly="" value="{{url($affiliate_base.'/'.$dataUser->affiliate_id.'/'.$Service->secret)}}" aria-describedby="basic-addon1">
						<button type="submit" class="btn btn-primary copy_btn" data-clipboard-text="{{url($affiliate_base.'/'.$dataUser->affiliate_id.'/'.$Service->secret)}}">Copy</button>
					</div>	
					@endif
				</div>
			</div>
		@endif
	@elseif (Auth::check() && Auth::user()->username == 'culsons' && $Service->user->is_delete == 0)
	<div class="user-detail">
		<h6>Share & Earn {{$responce['percentage']}}%  Cash Back!</h6>
		<div class="detail-point">
			<p class="text-oneline">Earn A {{$responce['percentage']}}% Commission By Sharing This Service</p>
			<p class="text-oneline">Here’s Your Service Affiliate Link</p>
			@if(Auth::user()->is_sub_user() == false)
			<div class="form-group affiliate-form gradient">
				<input type="text" class="form-control" readonly="" value="{{url($affiliate_base.'/'.Auth::user()->affiliate_id.'/'.$Service->secret)}}" aria-describedby="basic-addon1">
				<button type="submit" class="btn btn-primary copy_btn" data-clipboard-text="{{url($affiliate_base.'/'.Auth::user()->affiliate_id.'/'.$Service->secret)}}">Copy</button> 
			</div>
			
			@else if($dataUser!=null)
			<div class="form-group affiliate-form gradient">
				<input type="text" class="form-control" readonly="" value="{{url($affiliate_base.'/'.$dataUser->affiliate_id.'/'.$Service->secret)}}" aria-describedby="basic-addon1">
				<button type="submit" class="btn btn-primary copy_btn" data-clipboard-text="{{url($affiliate_base.'/'.$dataUser->affiliate_id.'/'.$Service->secret)}}">Copy</button>
			</div>	
			@endif
		</div>
	</div>
	@endif
	{{-- Share and Earn End --}}
	@if(!is_null($Service->last_updated_on))
	<div class="text-right mt-1">Service Updated {{date('F j, Y',strtotime($Service->last_updated_on))}}</div>
	@endif
</div>  