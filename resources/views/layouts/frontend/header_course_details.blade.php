<nav class="header navbar-light bg-light static-top login-user-menu course-details-header border-bottom">
	<div class="container-fluid font-lato right-sidebar-toggle-menu">
		<div class="row align-items-center login-menu">
			<div class="col-auto course-mob-hide"> 
				@if(Auth::user()->web_dark_mode == 1)
				<a class="navbar-brand" href="{{url('/')}}"><img src="{{url('public/frontend/assets/img/logo/LogoHeader_DarkMode.png')}}" class="header-logo"></a>
				@else
				<a class="navbar-brand" href="{{url('/')}}"><img src="{{url('public/frontend/assets/img/logo/LogoHeader.png')}}" class="header-logo"></a>
				@endif
			</div>
			<div class="col ">
				<div class="row align-items-center">
					<div class="col-md-8">
						<h4 class="text-warp-elips mb-0">{{ $course->title }}</h4>
					</div>
					<div class="col-md-4 text-right course-mob-hide">
						@if($order->seller_rating == 0)
						<a href="Javascript:;" class="review-and-rating-btn">
							<img src="{{ url('public/frontend/images/homepage log out/Star 1.png') }}" class="pr-1 img-fluid mb-1" alt="Star">
							<span class="couse-ratting">Leave a rating</span>
						</a>
						@endif
					</div>
				</div>
			</div>
		</div>
	</div>
</nav> 