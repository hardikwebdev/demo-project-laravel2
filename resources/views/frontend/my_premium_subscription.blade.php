@extends('layouts.frontend.main')
@section('pageTitle', 'demo - My Premium Subscription')
@section('content')
@include('layouts.frontend.messages')

<section class="profile-header-new"></section>

<section class="accountsetting profile-header-new2">
	<div class="profile-sec">
		<div class="cus-container container">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">My Premium Subscription</h2>
			</div>
		</div>
		<div class="profile-detail container">
			<div class="row second-row align-items-center">          
				<div class="col-lg-8">
					<div class="profile-desc bcmseller">

						<h4>Your subscription is expire on {{date('d M, Y h:i A',strtotime(Auth::user()->subscription->end_date))}}</h4>

						<h5 class="mt-2">Your exclusive benefits...</h5>
						<ul class="greenbullets">
							<li>
								<p class="boldcust">Selling Analytics</p>
								<p>Get deeper insights into views, add to carts, and more. </p>
							</li>
							<li>
								<p class="boldcust">Bundled Services</p>
								<p>Create packages of services to help cross sell your services.</p>
							</li>
							<li>
								<p class="boldcust">Volume Discounts</p>
								<p>Offer customers volume based discounts to encourage sales.</p>
							</li>
							<li>
								<p class="boldcust">User Accounts</p>
								<p>Create sub user accounts for your team members with limited access.</p>
							</li>
							<li>
								<p class="boldcust">Recurring Services</p>
								<p>Offer customers recurring services that get billed automatically. </p>
							</li>
							<li>
								<p class="boldcust">Canned Replies</p>
								<p>Save common replies to customer questions for faster response.</p>
							</li>
						</ul>
					</div>
				</div>
				<div class="col-lg-4">
					<img src="{{url('public/frontend/images/bcm-seller.png')}}" alt="" class="img-fluid m-auto d-block">
				</div>
			</div>
		</div>      
		<div class="process-pay bcmseller">  

			@if(empty(Auth::user()->subscription) || Auth::user()->subscription->is_cancel == 1)
			<a href="{{route('show_premium_payment')}}" role="button"><button type="button" class="pro-btn">Upgrade My Premium Benefits (${{number_format($subscription->price,2)}})</button></a>
			@else
			<a href="{{route('cancel_premium_subscription')}}" role="button"><button type="button" class="pro-btn">Cancel My Premium Subscription</button></a>
			@endif
		</div>
	</div>
</section>
@endsection

@section('css')
@endsection