<div class="item popular-item">
	<div class="thumbnail">
		<a href="{{route('services_details',[$service->user->username,$service->seo_url])}}">
			@php 
			$image_url = url('public/frontend/assets/img/No-image-found.jpg');
			@endphp
			@if(isset($service->images[0]))
			@if($service->images[0]->photo_s3_key != '')
			@php 
			$image_url = $service->images[0]->media_url; 
			@endphp
			@else	
			@php 
			$image_url = url('public/services/images/'.$service->images[0]->media_url); 
			@endphp
			@endif 
			@endif
			<img class="img-fluid" src="{{$image_url}}">
		</a>

		@if(Auth::check())
		<a class="favorite-action service_{{$service->id}} promo-popup " data-id="{{$service->id}}" data-status="{{isset($service->favorite->service_id) ? '1' : '0'}}" >
			<div class="circle tiny secondary">
				<i class="far fa-heart heart_service_{{$service->id}} {{isset($service->favorite->service_id) ? 'is_favorite' : ''}}" data-id="{{$service->id}}"></i>
			</div>
		</a>
		@else
		<a class="favorite-action service_119 promo-popup" href="{{url('login')}}">
			<div class="circle tiny secondary">
				<i class="far fa-heart"></i>
			</div>
		</a>
		@endif
	</div>
	<div class="product-info">
		<a href="{{route('services_details',[$service->user->username,$service->seo_url])}}">
			<h4 class="text-header min-title-height text-capitalize">
				{{display_title($service->title, 35)}}   
			</h4>
		</a>

		<p class="product-description min-description-height">
			{!! display_subtitle($service->subtitle, $service->descriptions, 50) !!}
		</p>

		<div class="review mb-3">
			@if($service->service_rating > 0)
			{!! displayRating($service->service_rating ,$showFiveStar = 0) !!}
			<span>{{round($service->service_rating,1)}} </span>({{$service->total_review_count}} Reviews)
			@else
			&nbsp;
			@endif
		</div>

		<div class="row align-items-center avtar-block">
			<div class="col-md-6 col-xl-6 col-6">
				<div class="avtar">
					<div class="avtar-img">
						<a href="{{route('viewuserservices',[$service->user->username])}}">
							<figure class="user-avatar small">
								<img alt="" src="{{get_user_profile_image_url($service->user)}}">
							</figure>
						</a>
					</div>
					<div class="avtar-detail">
						<a href="{{route('viewuserservices',[$service->user->username])}}">
							<div class="custom-text-header">
								<span>{{display_username($service->user->username)}}</span>

								@if(App\User::checkPremiumUser($service->user->id) == true)
									<img src="{{ url('public/frontend/images/Badge.png') }}" alt="profile-image" class="premiumBadgeHeader1" height="10"></img>
								@else
									<i class="fa fa-check" aria-hidden="true"></i>
								@endif

								<span>
									@if($service->user->seller_level != 'Unranked')
										{{$service->user->seller_level}}
									@endif
								</span>
							</div>
						</a>
					</div>
				</div>
			</div>
			<div class="col-md-6 col-xl-6 col-6 pl-2">
				<div class="total-price">
					<p>Starting at <span>${{isset($service->basic_plans->price)?$service->basic_plans->price:'0.0'}}</span>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>