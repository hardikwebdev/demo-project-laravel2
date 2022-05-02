@extends('layouts.frontend.main')
@section('pageTitle',(!is_null($settings) && $settings->meta_title)?$settings->meta_title:'demo - Home')

@section('metaTags')
@if(!is_null($settings) && $settings->meta_title)
<meta name="title" content="{{$settings->meta_title}}">
@endif
@if(!is_null($settings) && $settings->meta_keywords)
<meta name="keywords" content="{{$settings->meta_keywords}}">
@endif
@if(!is_null($settings) && $settings->meta_description)
<meta name="description" content="{{strip_tags($settings->meta_description)}}">
@endif
@endsection

@section('content')

<!-- Display Error Message -->
@include('layouts.frontend.messages')


<!-- Masthead -->
@if(count($home_slider))
<header class="masthead text-white"> {{-- masthead  --}}
	<div class="overlay"></div>
	<div class="home-slick-slider">
		@foreach($home_slider as $row)
		<div class="home-back-style" style="background: url({{$row->media_url}}) center right no-repeat;">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-md-8 col-lg-6 col-xl-5">
						<div class="custom-slider-block">
							<h1 class="mb-3">{!! $row->title !!}</h1>
							<p>{!! $row->sub_title !!}</p>
							@if($row->link_text && $row->link_url)
							<div class="text-right btn-explore"><a target="_blank" href="{{ $row->link_url}}">{!! $row->link_text !!}</a></div>
							@endif
						</div>
					</div>
				</div>
			</div>
		</div>
		@endforeach
	</div>
</header>
@endif

<div class="cus-sub-header container">
	<div class="bg-blue-grediant">
		<div class="row align-items-center justify-content-around">
			<div class="promo-content">
				<h4 class="pl-3 text-white">Check Out The Latest Savings Before They Expire…</h4>
			</div>
			<div class="promo-btn">
				<a href="{{route('service_promo')}}" class="btn btn-primary">
					See Deals <img src="{{url('public/frontend/assets/img/celebrate-promo.png')}}" class="img-fluid">			
				</a> 
			</div>
		</div>
	</div>
</div>
<!-- Icons Grid -->
<section class="features-icons">
	<div class="container">
		<div class="row">
			<div class="col-lg-4 col-md-4">
				<div class="row align-items-center mob-mb-30">
					<div class="col-lg-3 col-md-4 col-5">
						<div class="features-icons-icon">
							<img src="{{url('public/frontend/assets/img/business.png')}}" alt="" class="img-fluid m-auto text-primary">
						</div>
					</div>
					<div class="col-lg-9 col-md-8 col-7">
						<h4>900k+</h4>
						{{-- <h4>{{isset($reports->order_delivered)?$reports->order_delivered:'0'}}</h4> --}}
						<p>Orders Delivered</p>
					</div>
				</div>
			</div>
			<div class="col-lg-4 col-md-4">
				<div class="row align-items-center mob-mb-30">
					<div class="col-lg-3 col-md-4 col-5">
						<div class="features-icons-icon">
							<img src="{{url('public/frontend/assets/img/freelancer.png')}}" alt="" class="img-fluid m-auto text-primary">
						</div>
					</div>
					<div class="col-lg-9 col-md-8 col-7">
						<h4>225k+</h4>
						{{-- <h4>{{isset($reports->freelances)?$reports->freelances:'0'}}</h4> --}}
						<p>Freelancers</p>
					</div>
				</div>
			</div>
			<div class="col-lg-4 col-md-4">
				<div class="row align-items-center mob-mb-30">
					<div class="col-lg-3 col-md-4 col-5">
						<div class="features-icons-icon">
							<img src="{{url('public/frontend/assets/img/earned_by_freelancer.png')}}" alt="" class="img-fluid m-auto text-primary">
						</div>
					</div>
					<div class="col-lg-9 col-md-8 col-7">
						<h4>Over 4m</h4>
						{{-- <h4>${{isset($reports->earned_by_freelances)?dispay_money_format($reports->earned_by_freelances):'0'}}</h4> --}}
						<p>Earned by Freelancers</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- popular service -->
<section class="popular-services popular-tab-icon pt-0">
	<div class="container">

		{{-- begin : New & Trending Services --}}
		@if(count($pickedYourServiceListing))
		<div class="row">
			<div class="col-lg-12"> 
				<h3 class="heading mt-5 mb-0">Picked Just For You</h3>
				<p class="text-muted mb-3">We've picked these services based on your search and buying activity</p>
				<div class="owl-carousel owl-theme popular-grid-three">
					@foreach($pickedYourServiceListing as $service)
					@include('frontend.service.single-item')
					@endforeach
				</div>
			</div>
		</div>
		@endif
		{{-- end : New & Trending Services --}}

		{{-- begin : New & Trending Services --}}
		@if(count($treadingServices))
		<div class="row">
			<div class="col-lg-12"> 
				<h3 class="heading mt-5 mb-3">New & Trending Services</h3>
				<div class="owl-carousel owl-theme popular-grid-three">

					@if(count($sponsered_trading))
						@php
						$sponser = $sponsered_trading;
						@endphp
						@include('frontend.service.single-item-sponser')
					@else 
						@if(Auth::check())
							@php
							$boost_plan = $boosting_plans[6];	
							@endphp
							@include('frontend.service.ad_rent_spot')
						@endif
					@endif

					@foreach($treadingServices as $service)
					@include('frontend.service.single-item')
					@endforeach
				</div>
			</div>
		</div>
		@endif
		{{-- end : New & Trending Services --}}

		{{--begin :  Top Rated Services --}}
		@if(count($toprateServices))
		<div class="row">
			<div class="col-lg-12"> 
				<h3 class="heading mt-5 mb-3">Top Rated Services</h3>
				<div class="owl-carousel owl-theme popular-grid-three">

					@if(count($sponsered_middle))
						@php
						$sponser = $sponsered_middle;
						@endphp
						@include('frontend.service.single-item-sponser')
					@else 
						@if(Auth::check())
							@php
							$boost_plan = $boosting_plans[2];	
							@endphp
							@include('frontend.service.ad_rent_spot')
						@endif
					@endif

					@foreach($toprateServices as $service)
					@include('frontend.service.single-item')
					@endforeach
				</div>
			</div>
		</div>
		<br><br>
		@endif
		{{--end :  Top Rated Services --}}

		{{--begin :  Most Popular Services --}}
		@guest
		<div class="row align-items-center">
			<div class="col-lg-3">
				<div class="popular-block">
					<div class="sub-heading">FEATURED</div>

					<h3 class="heading mt-3 mb-3">Most Popular Services</h3>
					<p>Check out these popular services on demo that have been catching a lot of attention lately.</p>
				</div>
			</div>
			<div class="col-lg-9">
				<div class="popular-tab-item">
					<ul class="nav nav-tabs" id="myTab" role="tablist">
						@foreach($categories as $key => $category)
						<li class="nav-item">
							<a class="nav-link {{($key==0)?'active':''}}" id="{{$category->seo_url}}-tab" 
								data-toggle="tab" href="#{{$category->seo_url}}" 
								role="tab" 
								aria-controls="{{$category->seo_url}}" 
								aria-selected="true">{{$category->category_name}}
							</a>
						</li>
						@endforeach
					</ul>
					<div class="tab-content" id="myTabContent">
						@foreach($categories as $key => $category)
						<div class="tab-pane fade  {{($key==0)?'show active':''}}" id="{{$category->seo_url}}" role="tabpanel" aria-labelledby="graphics-design-tab">
							<div class="popular-grid popular-grid-one">
								@if($category->id == 1)
									@foreach($popularGraphicServices as $service)
										@include('frontend.service.single-item')
									@endforeach
								@endif
								
								@if($category->id == 2)
									@foreach($popularSeoServices as $service)
										@include('frontend.service.single-item')
									@endforeach
								@endif

								@if($category->id == 3)
									@foreach($popularMarketingServices as $service)
										@include('frontend.service.single-item')
									@endforeach
								@endif

								@if($category->id == 4)
									@foreach($popularVedioServices as $service)
										@include('frontend.service.single-item')
									@endforeach
								@endif

								@if($category->id == 5)
									@foreach($popularMusicServices as $service)
										@include('frontend.service.single-item')
									@endforeach
								@endif

								@if($category->id == 6)
									@foreach($popularProgrammingServices as $service)
										@include('frontend.service.single-item')
									@endforeach
								@endif
							</div>
						</div>
						@endforeach
					</div>
				</div>
			</div>
		</div>
		@endguest
		{{--end :  Most Popular Services --}}

	</div>
</section>

<!-- Get Project -->
<section class="get-project">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">    
				<h3 class="heading mb-5 text-center">Get your project started now... </h3>
			</div>    
		</div> 

		<div class="row">
			<div class="col-lg-3 col-md-4 custom-margin-top">
				<a href="{{url('/categories/programming-technology')}}">
					<div class="project-block text-center">
						<div class="project-img">
							{{-- @if(Auth::user()->web_dark_mode == 1)
							<img src="{{url('public/frontend/assets/img/programming_darkmode.png')}}">
							@else
							<img src="{{url('public/frontend/assets/img/programming.png')}}">
							@endif --}}
							<img src="{{url('public/frontend/assets/img/programming_new.png')}}">
						</div>
						<div class="project-detail">
							<div class="project-title">Programming</div>
						</div>
					</div>
				</a>
			</div>
			<div class="col-lg-3 col-md-4 custom-margin-top">
				<a href="{{url('/categories/graphics-design')}}">
					<div class="project-block text-center">
						<div class="project-img">
							{{-- @if(Auth::user()->web_dark_mode == 1)
							<img src="{{url('public/frontend/assets/img/vector_darkmode.png')}}">
							@else
							<img src="{{url('public/frontend/assets/img/vector.png')}}">
							@endif --}}
							<img src="{{url('public/frontend/assets/img/vector_new.png')}}">
						</div>
						<div class="project-detail">
							<div class="project-title">Design</div>
						</div>
					</div>
				</a>
			</div>
			<div class="col-lg-3 col-md-4 custom-margin-top">
				<a href="{{url('/categories/internet-marketing')}}">
					<div class="project-block text-center">
						<div class="project-img">
							{{-- @if(Auth::user()->web_dark_mode == 1)
							<img src="{{url('public/frontend/assets/img/marketing_darkmode.png')}}">
							@else
							<img src="{{url('public/frontend/assets/img/marketing.png')}}">
							@endif --}}
							<img src="{{url('public/frontend/assets/img/marketing_new.png')}}">
						</div>
						<div class="project-detail">
							<div class="project-title">Marketing</div>
						</div>
					</div>
				</a>
			</div>
			<div class="col-lg-3 col-md-4 custom-margin-top">
				<a href="{{url('/categories/seo')}}">
					<div class="project-block text-center">
						<div class="project-img">
							{{-- @if(Auth::user()->web_dark_mode == 1)
							<img src="{{url('public/frontend/assets/img/seo_darkmode.png')}}">
							@else
							<img src="{{url('public/frontend/assets/img/seo.png')}}">
							@endif --}}
							<img src="{{url('public/frontend/assets/img/seo_new.png')}}">
						</div>
						<div class="project-detail">
							<div class="project-title">SEO</div>
						</div>
					</div>
				</a>
			</div>
			<div class="col-lg-3 col-md-4 custom-margin-top">
				<a href="{{url('/categories/audio-music')}}">
					<div class="project-block text-center">
						<div class="project-img">
							{{-- @if(Auth::user()->web_dark_mode == 1)
							<img src="{{url('public/frontend/assets/img/music_darkmode.png')}}">
							@else
							<img src="{{url('public/frontend/assets/img/music.png')}}">
							@endif --}}
							<img src="{{url('public/frontend/assets/img/music_new.png')}}">
						</div>
						<div class="project-detail">
							<div class="project-title">Music</div>
						</div>
					</div>
				</a>
			</div>
			<div class="col-lg-3 col-md-4 custom-margin-top">
				<a href="{{url('/categories/video')}}">
					<div class="project-block text-center">
						<div class="project-img">
							{{-- @if(Auth::user()->web_dark_mode == 1)
							<img src="{{url('public/frontend/assets/img/video_darkmode.png')}}">
							@else
							<img src="{{url('public/frontend/assets/img/video.png')}}">
							@endif --}}
							<img src="{{url('public/frontend/assets/img/video_new.png')}}">
						</div>
						<div class="project-detail">
							<div class="project-title">Video</div>
						</div>
					</div>
				</a>
			</div>
			<div class="col-lg-3 col-md-4 custom-margin-top">
				<a href="{{url('/categories/business')}}">
					<div class="project-block text-center">
						<div class="project-img">
							{{-- @if(Auth::user()->web_dark_mode == 1)
							<img src="{{url('public/frontend/assets/img/business-logo_darkmode.png')}}">
							@else
							<img src="{{url('public/frontend/assets/img/business-logo.png')}}">
							@endif --}}
							<img src="{{url('public/frontend/assets/img/business_logo_new.png')}}">
						</div>
						<div class="project-detail">
							<div class="project-title">Business</div>
						</div>
					</div>
				</a>
			</div>
			<div class="col-lg-3 col-md-4 custom-margin-top">
				<a href="{{url('/categories/writing')}}">
					<div class="project-block text-center">
						<div class="project-img">
							{{-- @if(Auth::user()->web_dark_mode == 1)
							<img src="{{url('public/frontend/assets/img/writing_darkmode.png')}}">
							@else
							<img src="{{url('public/frontend/assets/img/writing.png')}}">
							@endif --}}
							<img src="{{url('public/frontend/assets/img/writing_new.png')}}">
						</div>
						<div class="project-detail">
							<div class="project-title">Writing</div>
						</div>
					</div>
				</a>
			</div>
		</div>
		
	</div>
</section>
<!-- Get Project -->
@endsection

@section('scripts')
<script src="{{ asset('resources/assets/js/ad_rent_spot.js') }}"></script>
@endsection