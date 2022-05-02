@extends('layouts.frontend.verification')
@section('pageTitle','Unsubscribed from mail')
@section('content')

{{-- <section class="sub-header">
	<div class="container">
		<div class="row">    
			<div class="col-lg-12">    
				<h2 class="heading mb-2">Two-Factor Authentication</h2>
			</div>
		</div>    
	</div>
</section> --}}

<section class="container-main">
	<div class="verify-container">
		<h2 class="heading mb-2 text-warning text-center">Success!</h2>
		<p class="text-auth text-center">Your email ID <b>{{$user->email}}</b> has been successfully unsubscribed to our mailing list(s).</p>

		<p class="text-center">Click here to visit <a href="{{url('/')}}" >demo.com</a></p>

	</div>
</section>
@endsection