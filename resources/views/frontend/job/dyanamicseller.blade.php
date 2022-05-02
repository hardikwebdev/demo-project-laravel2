<!--Dyanamic data for seller profile-->
@php
use App\Order;
$objOrder = new Order;
@endphp

<div class="row">
	<div class="col-md-4">
		<img src="{{get_user_profile_image_url($seller)}}" alt="profile-image" class='img-round img-fluid m-bidimg-round userPop'>
	</div>
	<div class="col-md-7 nameDesign">
		<div class="row">
			<h6>{{$seller->Name}} 
			@if($seller->is_premium_seller($seller->id) == true)
                <img src="{{url('public/frontend/images/Badge.png')}}" alt="" class="img-fluid text-primary ml-2 m-width-15">
            @endif</h6>
		</div>
		<div class="row">
			<a href="javascript:;"><b>{{$seller->seller_level}}</b></a>
		</div>
	</div>
</div>
<hr>
<div class="row">
	<div class="col-md-7">
		<span class="fontSizeCss">demo Rating</span>
	</div>
	<div class="col-md-5">
        @php
		$seller_rating = $objOrder->calculateSellerAverageRating($seller->id);
		@endphp
		{!! displayRating($seller_rating ,$showFiveStar = 1) !!}

	</div>
</div>

<div class="row marginTopCss">
	<div class="col-md-12">
		<span class="fontSizeCss">About:</span>
	</div>
</div>
<div class="row">
	<div class="col-md-12">
        {!! display_title($seller->description, $length="120") !!}
	</div>
</div>

<div class="row marginTopCss">
	<div class="col-md-12">
		<span class="fontSizeCss">General Info:</span>
	</div>
</div>
<div class="row marginTopCss">
	<div class="col-md-6">
		<i class="fas fa-map-marker-alt mr-2" aria-hidden="true"></i>From
	</div>
	<div class="col-md-6">
		{{$seller->city}},{{$seller->state}}</i>
	</div>
</div>

<div class="row marginTopCss">
	<div class="col-md-6">
		<i class="far fa-calendar-alt mr-2" aria-hidden="true"></i>Member Since
	</div>
	<div class="col-md-6">
		{{$seller->created_at->format('F jS,Y')}}</i>
	</div>
</div>

@if (Auth::check() && $seller->last_login_at != '0000-00-00 00:00:00')
<div class="row marginTopCss">
	<div class="col-md-6">
		<i class="far fa-clock mr-2" aria-hidden="true"></i>Last Signed On
	</div>
	<div class="col-md-6">
		{{get_time_ago(strtotime($seller->last_login_at))}}
	</div>
</div>
@endif