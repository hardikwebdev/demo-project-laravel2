<div class="new_service_box">
	{{-- <img class="sponsered" src="{{ url('public/frontend/images/my_image_promote.png')}}"> --}}
	<div class="thumbnail thumbnail-obj-cover">
		<a href="{{route('services_details',[$sponser->service->user->username,$sponser->service->seo_url])}}"  target="_blank">
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
			<img class="img-fluid lazy-load" src="{{$image_url}}">
		</a>

		@if(Auth::check())
		<a class="favorite-action service_{{$sponser->service->id}} promo-popup heart " data-id="{{$sponser->service->id}}" data-status="{{isset($sponser->service->favorite->service_id) ? '1' : '0'}}" >
			<div class="circle tiny secondary">
				<i class="far fa-heart heart_service_{{$sponser->service->id}} {{isset($sponser->service->favorite->service_id) ? 'is_favorite' : ''}}" data-id="{{$sponser->service->id}}"></i>
			</div>
		</a>
		@else
		<a class="favorite-action service_119 promo-popup heart" href="{{url('login')}}">
			<div class="circle tiny secondary">
				<i class="far fa-heart"></i>
			</div>
		</a>
		@endif
	</div>
	<div class="product-info">
		<a href="{{route('services_details',[$sponser->service->user->username,$sponser->service->seo_url])}}" target="_blank">
			<h4 class="text-header font-lato font-weight-bold min-title-height new_service_box_title text-color-2 text-capitalize">
				{{display_title($sponser->service->title, 50)}}            
			</h4>
		</a>

		<p class="product-description min-description-height new_service_box_desc">
			{!! display_subtitle($sponser->service->subtitle, $sponser->service->descriptions, 60) !!}
		</p>

		<div class="mb-2 d-flex mt-4 pt-2 justify-content-between">
			<div class="d-flex align-items-center">
				<span>
					@if($sponser->service->service_rating > 0)
					<img src="{{ url('public/frontend/images/homepage log out/Star 1.png') }}" class="pr-1 img- fluid" alt="">
					@else 
					<img src="{{ url('public/frontend/images/homepage log out/Star 1_no_rating.png') }}" class="pr-1 img-fluid no_rating_star" alt="Star">
					@endif
				</span>
				<span class="text-color-1 fontn-14"><b>{{round($sponser->service->service_rating,1)}}</b></span>
				<span class="font-12 align-self-center pt-1 pl-1 text-color-4">({{$sponser->service->total_review_count}})</span>
			</div>
			<div>
				<p class="font-14 mb-0 text-color-3">AD</p>
			</div>
		</div>

		{{-- <div class="review mb-3 row">
			<div class="col-md-10">
				@if($sponser->service->service_rating > 0)
				{!! displayRating($sponser->service->service_rating ,$showFiveStar = 0) !!}
				<span class="new_service_box_rating"><b>{{round($sponser->service->service_rating,1)}}</b></span>
				<span class="new_service_box_reviews">{{$sponser->service->total_review_count}} reviews</span>
				@else
				<span>&nbsp;</span>
				@endif
			</div>
			<div class="col-md-2">
				<div>Ad</div>
			</div>
		</div> --}}

		<div class="row align-items-center avtar-block py-2 card-footer-min-height">
			<div class="col-6">
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
								<span class="new_service_box_username text-color-3 font-weight-bold">{{display_username($sponser->service->user->username , 11)}}</span>
								
								@if(App\User::checkPremiumUser($sponser->service->user->id) == true)
									<img src="{{ url('public/frontend/images/Badge.png') }}" alt="profile-image" class="premiumBadgeHeader1" height="10">
								@else
									<i class="fa fa-check" aria-hidden="true"></i>
								@endif

								<span class="new_service_box_sellerlevel text-color-4">
									@if($sponser->service->user->seller_level != 'Unranked')
										{{$sponser->service->user->seller_level}}
									@endif
								</span>
							</div>
						</a>
					</div>
				</div>
			</div>
			<div class="col-6 display_grid">
				<span class="new_service_box_price text-color-3">${{isset($sponser->service->basic_plans->price)?$sponser->service->basic_plans->price:'0.0'}}</span>
				<span class="new_service_box_startingAt align-self-end pb-1 text-color-4">Starting at</span>
			</div>
		</div>
	</div>
</div>