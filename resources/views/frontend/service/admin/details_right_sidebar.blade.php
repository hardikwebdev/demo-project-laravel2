@php
use App\User;
$userObj = new User;
@endphp

<div class="sticky-block1 sidebar sidebar-overflow">
	<div class="accordion md-accordion" id="accordionEx" role="tablist" aria-multiselectable="true">

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
				<a data-toggle="collapse" data-parent="#premium" href="#premium" aria-expanded="true" aria-controls="premium">
					<h6 class="mb-0 packagetitle">
						{{$Service->premium_plans->package_name}}
					</h6>
					<h6 class="packageprice">
						 <span>${{$Service->premium_plans->price}} <i class="fa fa-angle-down rotate-icon"></i></span>
					</h6>
				</a>
			</div>
			<!-- Card body -->
			<div id="premium" class="collapse show" role="tabpanel" aria-labelledby="headingOne1" data-parent="#accordionEx">
				<div class="card-body">
					<h6>Includes</h6>
					<li>{{$Service->premium_plans->offering_details}}</li>
					<div class="detail-point">
						<div class="detail-point-one">
							@if($Service->is_recurring == 0)
							<img src="{{front_asset('assets/img/clock.png')}}" class="img-fluid"> {{$Service->premium_plans->delivery_days}} days delivery
							@endif
						</div>
						<div class="detail-point-two">
							{{-- <span><i class="fas fa-lock"></i> Secure Transaction</span> --}}
						</div>
					</div>

					@if($userObj->is_premium_seller($Service->uid))
					@foreach($Service->volume_discount as $key => $value)
					<div class="text-success">Buy {{$value->volume}} get {{$value->discount}}% discount</div>
					@endforeach
					@endif

					<div class="premium-btn">
						
					</div>   
				</div>

				<!-- Combo Offer Discount Card Start -->
				@if(Auth::check())
					@if($Service->user->is_delete == 0 && Auth::user()->parent_id == 0)
						@if (Auth::user()->id != $Service->uid)
							@if($buddleservice && $otherservice->count() == 1)
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

										@if(!$otherservice->isempty())
										@foreach($otherservice as $service)


										<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
										@endforeach
										@endif

										<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>

										<div class="premium-btn">
											
										</div>

									</div>
								</div>
								@endif
							@endif
						@endif
					@endif
				@else
				<!--shows default button for guest user without login for purchasing combo services-->
					@if($buddleservice && $otherservice->count() == 1)
								
								<div class="user-detail">
									<h6>Combo Offer Discount</h6>
									<div class="detail-point">
										<p class="text-oneline">Also buy 
											@if($otherservice->count() == 1)
											this service @else
											these services
										@endif</p>
										@if(!$otherservice->isempty())
										@if (Auth::check())
										@foreach($otherservice as $service)

										<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
										@endforeach
										@endif
										@endif
										<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>

										<div class="premium-btn">
											
										</div>

									</div>
								</div>
								
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
				<a class="collapsed" data-toggle="collapse" data-parent="#accordionEx" href="#standard" aria-expanded="false" aria-controls="collapseTwo2">
					<h6 class="mb-0 packagetitle">
						{{$Service->standard_plans->package_name}}
					</h6>
					<h6 class="packageprice">
						<span>${{$Service->standard_plans->price}} <i class="fa fa-angle-down rotate-icon"></i></span>
					</h6>
				</a>
			</div>
			<!-- Card body -->
			<div id="standard" class="collapse" role="tabpanel" aria-labelledby="standard1" data-parent="#accordionEx">
				<div class="card-body">
					<h6>Includes</h6>
					<li>{{$Service->standard_plans->offering_details}}</li>
					<div class="detail-point">
						<div class="detail-point-one">
							@if($Service->is_recurring == 0)
							<img src="{{front_asset('assets/img/clock.png')}}" class="img-fluid"> {{$Service->standard_plans->delivery_days}} days delivery
							@endif
						</div>
						<div class="detail-point-two">
							{{-- <span><i class="fas fa-lock"></i> Secure Transaction</span> --}}
						</div>
					</div>

					@if($userObj->is_premium_seller($Service->uid))
					@foreach($Service->volume_discount as $key => $value)
					<div class="text-success">Buy {{$value->volume}} get {{$value->discount}}% discount</div>
					@endforeach
					@endif

					<div class="premium-btn">
						
					</div>   
				</div>

				<!-- Combo Offer Discount Card Start -->
				@if(Auth::check())
					@if($Service->user->is_delete == 0 && Auth::user()->parent_id == 0)
						@if (Auth::user()->id != $Service->uid)
							@if($buddleservice && $otherservice->count() == 1)
								@if($userObj->is_premium_seller($Service->uid))
								<div class="user-detail">
									<h6>Combo Offer Discount</h6>
									<div class="detail-point">
										<p class="text-oneline">Also buy 
											@if($otherservice->count() == 1)
											this service @else
											these services
										@endif</p>
										@if(!$otherservice->isempty())
										@foreach($otherservice as $service)

										<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
										@endforeach
										@endif
										<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>

										<div class="premium-btn">
											
										</div>

									</div>
								</div>
								@endif
							@endif
						@endif
					@endif
				@else
				<!--shows default button for guest user without login for purchasing combo services-->
					@if($buddleservice && $otherservice->count() == 1)
								
								<div class="user-detail">
									<h6>Combo Offer Discount</h6>
									<div class="detail-point">
										<p class="text-oneline">Also buy 
											@if($otherservice->count() == 1)
											this service @else
											these services
										@endif</p>
										@if(!$otherservice->isempty())
										@if (Auth::check())
										@foreach($otherservice as $service)

										<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
										@endforeach
										@endif
										@endif
										<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>

										<div class="premium-btn">
										
										</div>

									</div>
								</div>
								
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
				<a class="collapsed" data-toggle="collapse" data-parent="#accordionEx" href="#basic" aria-expanded="false" aria-controls="collapseThree3">
					<h6 class="mb-0 packagetitle">
						{{$Service->basic_plans->package_name}}
					</h6>
					<h6 class="packageprice">
						<span>${{$Service->basic_plans->price}} <i class="fa fa-angle-down rotate-icon"></i></span>
					</h6>
				</a>
			</div>
			<!-- Card body -->

			@if(!isset($Service->premium_plans))
			<div id="basic" class="collapse show" role="tabpanel" aria-labelledby="basic1" data-parent="#accordionEx">
				@else
				<div id="basic" class="collapse" role="tabpanel" aria-labelledby="basic1" data-parent="#accordionEx">
					@endif
					<div class="card-body">
						<h6>Includes</h6>
						<li>{{$Service->basic_plans->offering_details}}</li>
						<div class="detail-point">
							<div class="detail-point-one">
								@if($Service->is_recurring == 0)
								<img src="{{front_asset('assets/img/clock.png')}}" class="img-fluid"> {{$Service->basic_plans->delivery_days}} days delivery
								@endif
							</div>
							<div class="detail-point-two">
								{{-- <span><i class="fas fa-lock"></i> Secure Transaction</span> --}}
							</div>
						</div>

						@if($userObj->is_premium_seller($Service->uid))
						@foreach($Service->volume_discount as $key => $value)
						<div class="text-success">Buy {{$value->volume}} get {{$value->discount}}% discount</div>
						@endforeach
						@endif

						<div class="premium-btn">

						</div>   
					</div>

					<!-- Combo Offer Discount Card Start -->
					@if(Auth::check())
						@if($Service->user->is_delete == 0 && Auth::user()->parent_id == 0)
							@if (Auth::user()->id != $Service->uid)
								@if($buddleservice && $otherservice->count() == 1)
									@if($userObj->is_premium_seller($Service->uid))
									<div class="user-detail">
										<h6>Combo Offer Discount</h6>
										<div class="detail-point">
											<p class="text-oneline">Also buy 
												@if($otherservice->count() == 1)
												this service @else
												these services
											@endif</p>
											@if(!$otherservice->isempty())
											@foreach($otherservice as $service)

											<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
											@endforeach
											@endif
											<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>
											<div class="premium-btn">
												
											</div>
										</div>
									</div>
									@endif
								@endif
							@endif
						@endif
					@else
					<!--shows default button for guest user without login for purchasing combo services-->
					@if($buddleservice && $otherservice->count() == 1)
						<div class="user-detail">
										<h6>Combo Offer Discount</h6>
										<div class="detail-point">
											<p class="text-oneline">Also buy 
												@if($otherservice->count() == 1)
												this service @else
												these services
											@endif</p>
											@if(!$otherservice->isempty())
											@if (Auth::check())
											@foreach($otherservice as $service)

											<a target="_blank" href="{{route('services_details',[$service->service->user->username,$service->service->seo_url])}}"><p class="text-oneline text-capitalize" style="margin-bottom:0rem; ">- {{@$service->service->title}}</p></a>
											@endforeach
											@endif
											@endif
											<p class="text-oneline" style="margin-top: 1rem;">And get <span style="font-weight: bolder;">{{@$buddleservice->getBuddleDiscount->discount}}</span> % discount</p>
											<div class="premium-btn">
												
											</div>
										</div>
									</div>
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

							<img src="{{ url('public/frontend/images/Badge.png') }}" alt="profile-image" class="premiumBadgeHeader1" height="15"></img>

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
			
		</div>
	</div>
	<!-- User wrapper End--> 
	@if(!is_null($Service->last_updated_on))
	<div class="text-right mt-1">Service Updated {{date('F j, Y',strtotime($Service->last_updated_on))}}</div>
	@endif
</div>  