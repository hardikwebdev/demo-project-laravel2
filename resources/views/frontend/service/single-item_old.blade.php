@php
$append_url = '';
if(\Request::segment(1) == 'influencer') {
	$append_url = '?influencer='.\Request::segment(2);
}
@endphp
<div class="item popular-item">
	<div class="thumbnail">
		<a href="{{route('services_details',[$service->user->username,$service->seo_url]).$append_url}}" target="_blank">
			@php 
			$image_url = url('public/frontend/assets/img/No-image-found.jpg');
			@endphp
			@if(isset($service->images[0]))
				@if(!is_null($service->images[0]->thumbnail_media_url))
					@php 
						$image_url = $service->images[0]->thumbnail_media_url; 
					@endphp
				@elseif($service->images[0]->photo_s3_key != '')
					@php 
						$image_url = $service->images[0]->media_url; 
					@endphp
				@else	
					@php 
						$image_url = url('public/services/images/'.$service->images[0]->media_url); 
					@endphp
				@endif 
			@endif
			<img class="img-fluid lazy-load" src="{{$image_url}}">
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
		<a href="{{route('services_details',[$service->user->username,$service->seo_url]).$append_url}}" target="_blank">
			<h4 class="text-header min-title-height text-capitalize">
				{!! display_title($service->title, 50) !!}  
			</h4>
		</a>

		<p class="product-description min-description-height">
			{!! display_subtitle($service->subtitle, $service->descriptions, 60) !!}
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
	
		
	@if(isset($isPromate) && $isPromate ==1 )
		<div class="promot-service">
			@if(count($service->coupon))
				@foreach($service->coupon as $valueCoupon)
					@if($valueCoupon->is_promo == 1 && $valueCoupon->is_delete == 0)
						<h4>SAVE @if($valueCoupon->discount_type=='amount')  
						${{ $valueCoupon->discount }}
						@else 
						{{ $valueCoupon->discount }}%
						@endif  </h4>
						<p class="fw-100">WITH CODE</p>
						<div class="copycode-promo">
							<h5 class="copy_promo " data-clipboard-text="{{$valueCoupon->coupan_code}}">{!! display_content($valueCoupon->coupan_code, 10) !!}</h5>
							<i class="copy_promo far fa-copy" data-clipboard-text="{{$valueCoupon->coupan_code}}"></i>
						</div>
						<div class="userleft d-flex mt-2">

							<p class="text-danger mb-0 text-uppercase fw-600 d-inline-block">{{$valueCoupon->no_of_uses - $valueCoupon->coupan_applied_count}} Uses Left</p>
							@php
								$now = strtotime(date('Y-m-d')); // or your date as well
								$your_date = strtotime($valueCoupon->expiry_date);
								$datediff = $your_date - $now;

								$diff_date = round($datediff / (60 * 60 * 24));
							@endphp
							<p class="d-inline-block text-danger text-right mb-0 text-uppercase ml-auto fw-600">
								@if($diff_date == 1)
									Expires Today
								@else
									Expires in {{$diff_date}} days
								@endif
							</p>
						</div>
					@endif
				@endforeach
			@endif
		</div>
	@endif

	<!-- Affiliate offers page-->
	@if(isset($isAffiliate) && $isAffiliate ==1 )
		<div class="promot-service">
			@php
			$responce = $service->get_affiliate_discount($service);
			@endphp
			<h4>Earn <span class="text-success">${{number_format($responce['discount'],2)}} & UP</span></h4>
			<p class="fw-100 cust-line-h">Earn a {{$responce['percentage']}}% commission by sharing this service</p>

			@if(Auth::user()->is_sub_user() == false)
				<div class="copycode-promo">
					<h6 class="copy_affiliate " data-clipboard-text="{{url('promoteservice/'.Auth::user()->affiliate_id.'/'.$service->secret)}}">{!! display_content(url('promoteservice/'.Auth::user()->affiliate_id.'/'.$service->secret), 20) !!}</h6>
					<i class="copy_affiliate far fa-copy" data-clipboard-text="{{url('promoteservice/'.Auth::user()->affiliate_id.'/'.$service->secret)}}"></i>
				</div>
			@else
				<div class="copycode-promo">
					<h6 class="copy_affiliate " data-clipboard-text="{{url('promoteservice/'.$userData->affiliate_id.'/'.$service->secret)}}">{!! display_content(url('promoteservice/'.$userData->affiliate_id.'/'.$service->secret), 20) !!}</h6>
					<i class="copy_affiliate far fa-copy" data-clipboard-text="{{url('promoteservice/'.$userData->affiliate_id.'/'.$service->secret)}}"></i>
				</div>
			@endif
		</div>
	@endif
</div>