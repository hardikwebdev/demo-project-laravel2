@extends('layouts.frontend.verification')
@section('pageTitle','Two-Factor Authentication-demo')
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

<section class="container-main mt-0">
	<div class="verify-container1 verify-app-container text-center">

		@include('layouts.frontend.messages')

		{{ Form::open(['method' => 'POST', 'id' => 'loginfrmTowfactorAuth', 'data-url' => route('login_verify_towfactorauth')]) }}

		<input type="hidden" name="action_type" id="action_type" value="submit" />
		<input type="hidden" id="profileurl" name="profileurl" value="{{Session::get('profileurl')}}">

		<div class="row">
			<div class="col-md-12">
				<div class="alert alert-danger" style="display:none;" id="verify_error_msg_div">
					<span id="verify_error_msg"></span>
				</div>
			</div>
		</div>

		<h2 class="heading mb-2">Verify your phone number</h2>
		<p class="text-auth">demo will send you a text message with a 4-digit verification code.</p>
		<p class="text-auth">Your mobile number end with : +{{$user->country_code}}*******{{substr($user->mobile_no, -3)}}</p>

		<div class="towfactorauth-div">
			<div class="row">
				<div class="col-md-8 text-right">
					<button type="submit" id="request_call" class="btn btn-link" disabled>Call instead</button>
					<div class="gradient pull-right"><input type="submit" id="send_otp" class="btn btn-primary" value="Send OTP" disabled></div>
				</div>
			</div>
		</div>

		<div class="otp-div" style="display: none;">
			<div class="row">
				<div class="col-md-8 offset-2">
					<div class="form-group">
						<input type="text" class="form-control" id="otp" name="otp" placeholder="Enter OTP">
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-2 offset-2">
					<button type="button" id="verify_go_back" class="btn btn-link">Back</button> 
				</div>

				<div class="col-md-6 text-right">
					<button type="submit" id="re_request_call" class="btn btn-link">Call instead</button>
					<div class="gradient pull-right"><button class="btn btn-primary" id="verify_otp" type="submit">&nbsp;&nbsp;Verify&nbsp;&nbsp;</button></div>
				</div>
			</div>
		</div>

		@if($user_device_count > 0)
		<p class="text-auth mt-5 cursor"><a href="javascript:void(0);" id="resend-verification" >Verify authentication via app.</a></p>
		@endif

		<div class="row mt-5">
			<div class="row text-center"> 
				<div class="col-md-12">
					<p class="text-left"><i>Download below app using click on below icons to enable authenticate via app push notification. App push notification must be enable from device settings to work this feature.</i></p>
				</div>
				<div class="col-md-6 mb-2">
					<a target="_blank" href="https://apps.apple.com/us/app/demo-freelance-marketplace/id1546041094"><img class="img-fluid" src="{{url('public/frontend/images/appstore-2x-1014.png')}}" /></a>
				</div>
				<div class="col-md-6 mb-2">
					<a target="_blank"  href="https://play.google.com/store/apps/details?id=com.demo"><img class="img-fluid" src="{{url('public/frontend/images/googleplay-2x-1014.png')}}" /></a>
				</div>
				
			</div>
		</div>

		{{Form::close()}}
	</div>
</section>
@endsection

@section('scripts')
	<script type="text/javascript">
		$(document).ready(function () {
			$('#send_otp').removeAttr('disabled');
			$('#request_call').removeAttr('disabled');

			$('#resend-verification').click(function(){
				$.ajax({
					type: "post",
					url: "{{ route('resend_app_verificarion') }}",
					data: {
						"_token": _token,
					},
					success: function (data) {
						if (data.status == true) {
							window.location.href = "{{route('app_twofactorauth')}}";
						} else {
							toastr.error(data.message);
							setTimeout(function(){ window.location.href = "{{url('/')}}"}, 1000);
						}
					}
				});
			});
		});
	</script>
@endsection