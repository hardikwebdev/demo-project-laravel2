<div class="item popular-item">
	{{-- <img class="sponsered" src="{{ url('public/frontend/images/my_image_promote.png')}}"> --}}
	<div class="thumbnail">
		<a href="{{route('services_details',[$sponser->service->user->username,$sponser->service->seo_url])}}" target="_blank">
			@php 
			$image_url = url('public/frontend/assets/img/No-image-found.jpg');
			@endphp
			@if(isset($sponser->service->images[0]))
			@if(!is_null($sponser->service->images[0]->thumbnail_media_url))
					@php 
						$image_url = $sponser->service->images[0]->thumbnail_media_url; 
					@endphp
			@elseif($sponser->service->images[0]->photo_s3_key != '')
			@php 
			$image_url = $sponser->service->images[0]->media_url; 
			@endphp
			@else	
			@php 
			$image_url = url('public/services/images/'.$sponser->service->images[0]->media_url); 
			@endphp
			@endif 
			@endif
			<img class="img-fluid" src="{{$image_url}}">
		</a>

		@if(Auth::check())
		<a class="favorite-action service_{{$sponser->service->id}} promo-popup " data-id="{{$sponser->service->id}}" data-status="{{isset($sponser->service->favorite->service_id) ? '1' : '0'}}" >
			<div class="circle tiny secondary">
				<i class="far fa-heart heart_service_{{$sponser->service->id}} {{isset($sponser->service->favorite->service_id) ? 'is_favorite' : ''}}" data-id="{{$sponser->service->id}}"></i>
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
		<a href="{{route('services_details',[$sponser->service->user->username,$sponser->service->seo_url])}}" target="_blank">
			<h4 class="text-header min-title-height text-capitalize">
				{{display_title($sponser->service->title, 50)}}            
			</h4>
		</a>

		<p class="product-description min-description-height">
			{!! display_subtitle($sponser->service->subtitle, $sponser->service->descriptions, 60) !!}
		</p>

		<div class="review mb-3 row">
			<div class="col-md-10">
				@if($sponser->service->service_rating > 0)
				{!! displayRating($sponser->service->service_rating ,$showFiveStar = 0) !!}
				<span>{{round($sponser->service->service_rating,2)}} </span>({{$sponser->service->total_review_count}} Reviews)
				@else
				&nbsp;
				@endif
			</div>
			<div class="col-md-2">
				<div>Ad</div>
			</div>
		</div>

		<div class="row align-items-center avtar-block">
			<div class="col-md-6 col-xl-6 col-6">
				<div class="avtar">
					<div class="avtar-img">
						<a href="{{route('viewuserservices',[$sponser->service->user->username])}}">
							<figure class="user-avatar small">
								<img alt="" src="{{get_user_profile_image_url($sponser->service->user)}}">
							</figure>
						</a>
					</div>
					<div class="avtar-detail">
						<a href="{{route('viewuserservices',[$sponser->service->user->username])}}">
							<div class="custom-text-header">
								<span>{{display_username($sponser->service->user->username)}}</span>
								
								@if(App\User::checkPremiumUser($sponser->service->user->id) == true)
									<img src="{{ url('public/frontend/images/Badge.png') }}" alt="profile-image" class="premiumBadgeHeader1" height="10"></img>
								@else
									<i class="fa fa-check" aria-hidden="true"></i>
								@endif

								<span>
									@if($sponser->service->user->seller_level != 'Unranked')
										{{$sponser->service->user->seller_level}}
									@endif
								</span>
							</div>
						</a>
					</div>
				</div>
			</div>
			<div class="col-md-6 col-xl-6 col-6 pl-2">
				<div class="total-price">
					<p>Starting at <span>${{isset($sponser->service->basic_plans->price)?$sponser->service->basic_plans->price:'0.0'}}</span>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>