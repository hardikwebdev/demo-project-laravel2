<div class="new_service_box" style="width: 100%; display: inline-block;">
    <div class="thumbnail thumbnail-obj-cover">
        <a href="{{route('course_details',[$serviceBox->user->username,$serviceBox->seo_url])}}" target="_blank" tabindex="0">    
            @php 
			$image_url = url('public/frontend/assets/img/No-image-found.jpg');
			@endphp
			@if(isset($serviceBox->images[0]))
				@if(!is_null($serviceBox->images[0]->thumbnail_media_url))
					@php 
						$image_url = $serviceBox->images[0]->thumbnail_media_url; 
					@endphp
				@elseif($serviceBox->images[0]->photo_s3_key != '')
					@php 
						$image_url = $serviceBox->images[0]->media_url; 
					@endphp
				@else	
					@php 
						$image_url = url('public/services/images/'.$serviceBox->images[0]->media_url); 
					@endphp
				@endif 
			@endif                                                 
            <img class="img-fluid lazy-load" src="{{$image_url}}" alt="{{$serviceBox->title}}">
        </a>

        @if(Auth::check())
		<a class="favorite-action service_{{$serviceBox->id}} promo-popup heart" data-id="{{$serviceBox->id}}" data-status="{{isset($serviceBox->favorite->service_id) ? '1' : '0'}}" >
			<div class="circle tiny secondary">
				<i class="far fa-heart heart_service_{{$serviceBox->id}} {{isset($serviceBox->favorite->service_id) ? 'is_favorite' : ''}}" data-id="{{$service->id}}"></i>
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
        <a href="{{route('course_details',[$serviceBox->user->username,$serviceBox->seo_url])}}" target="_blank" tabindex="0">
            <h4 class="font-16 font-lato font-weight-bold text-color-2 mt-3 text-capitalize min-title-height">
                {!! display_title($serviceBox->title, 50) !!}
            </h4>
        </a>

        <p class="font-14 text-color-3 mt-3 min-description-height">
            {!! display_subtitle($serviceBox->subtitle, $serviceBox->descriptions, 60) !!}
        </p>

        <div class="d-flex mb-4 mt-3 pt-2 justify-content-between">
            <div class="d-flex align-items-center">
                <span class="font-12 text-color-4 mt-1">From</span>
                <span class="font-18 font-weight-bold text-color-2 pl-2">${{isset($serviceBox->price)?$serviceBox->price:'0.0'}}</span>
            </div>
        </div>

        <div class="row align-items-center avtar-block py-2 card-footer-min-height">
            <div class="col-6 pr-0">
                <div class="avtar">
                    <div class="avtar-img">
                        <a href="{{route('viewuserservices',[$serviceBox->user->username])}}" tabindex="0">
                            <div class="course_w-32 course_h-32">
                                <img class='img-fluid' alt="{{$serviceBox->user->username}}" src="{{get_user_profile_image_url($serviceBox->user)}}">
                            </div>
                        </a>
                    </div>
                    <div class="avtar-detail">
                        <a href="{{route('viewuserservices',[$serviceBox->user->username])}}" tabindex="0">
                            <div class="custom-text-header ml-2">
                                <span class="font-14 text-color-2 font-weight-bold">{{$serviceBox->user->username}}</span>
                                    <i class="fa fa-check" aria-hidden="true"></i>   
                                <span class="font-12 text-color-4">
                                    @if($serviceBox->user->seller_level != 'Unranked')
										{{$serviceBox->user->seller_level}}
									@endif
                                </span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-6 d-flex align-items-center justify-content-end">
                <span>
                    @if($serviceBox->service_rating > 0)
					<img src="{{ url('public/frontend/images/homepage log out/Star 1.png') }}" class="pr-1 img-fluid" alt="Star Image">
					@else 
					<img src="{{ url('public/frontend/images/homepage log out/Star 1_no_rating.png') }}" class="pr-1 img-fluid mb-1 no_rating_star" alt="Star">
					@endif
                </span>
                <span class="text-color-1 fontn-14 font-weight-bold">{{round($serviceBox->service_rating,1)}}</span>
                <span class="text-color-4 fontn-12 pl-2">({{$serviceBox->total_review_count}})</span>
            </div>
        </div>
    </div>
</div>