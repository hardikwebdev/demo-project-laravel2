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
	<div class="verify-app-container text-center">

		@include('layouts.frontend.messages')

		<div class="mb-4"><img src="{{url('public/frontend/images/web-to-app-verification.gif')}}" width="100"/></div>

		<h2 class="heading mb-2">We're waiting for your response</h2>

		<p class="text-auth mb-4">Approve this login by oppening the demo app and tapping <br>"Yes, it's me"</p>
		<div id="load_spinner"><img src="{{url('public/frontend/images/processing.gif')}}" /></div>
		<div id="two-factor-timer"></div>
		
		<div class="row">
			<div class="col-md-12">
				<div class="alert alert-danger" style="display:none;" id="verify_error_msg_div">
					<span id="verify_error_msg"></span>
				</div>
			</div>
		</div>
		
		<div class="app-options mt-2 collapse multi-collapse" id="app-varification-col">
			<div class="card card-body resend-verification-card make-hidden">
				<a href="javascript:void(0);" id="resend-verification" data-toggle="collapse" data-target="#app-varification-col" aria-expanded="false" aria-controls="app-varification-col">Re send verification</a>
			</div>
			<div class="card card-body">
			<a href="{{url('twofactorauth')}}">Verification via sms or call</a>
			</div>
		</div>
		<p class="text-auth mt-4 cursor checkotherway"><a href="javascript:void(0);"  data-toggle="collapse" data-target="#app-varification-col" aria-expanded="false" aria-controls="app-varification-col">I haven't received an approval request</a></p>
		<!-- <p class="text-danger"><i>Note: App push notification must be enable from device settings to work this feature.</i></p> -->
	
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
	
	</div>
</section>
@endsection

@section('scripts')
	<script type="text/javascript">
		$(document).ready(function () {
			$('#resend-verification').click(function(){
				$.ajax({
					type: "post",
					url: "{{ route('resend_app_verificarion') }}",
					data: {
						"_token": _token,
					},
					success: function (data) {
						if (data.status == true) {
							toastr.success(data.message);
							$('#verify_error_msg').html('');
							$('#load_spinner').show();
							$('#verify_error_msg_div').hide();
							two_factor_timer =  60;
							localStorage.setItem("two_factor_timer", 60);
							can_check_verification = true;
							$('.resend-verification-card').addClass('make-hidden');
						} else {
							toastr.error(data.message);
							setTimeout(function(){ window.location.href = "{{url('/')}}"}, 1000);
						}
					}
				});
			});
			var can_check_verification = true;
			//var can_check_verification = false; //
			var two_factor_timer = localStorage.getItem("two_factor_timer");
			if(two_factor_timer == undefined || two_factor_timer == ''){
				two_factor_timer =  60;
				localStorage.setItem("two_factor_timer", 60);
			}
			
			setInterval(function() {
				if(can_check_verification == true){
					$.ajax({
						type: "post",
						url: "{{ route('check_app_verificarion') }}",
						data: {
							"_token": _token,"two_factor_timer":two_factor_timer
						},
						success: function (data) {
							if (data.status == true) {
								window.location.href = data.return_url;
							}else{
								if(data.message != ''){
									toastr.error(data.message);
									setTimeout(function(){ window.location.href = "{{url('/login')}}"}, 1000);
								}else if(data.timeout == true){
									$('#verify_error_msg').html('Opps! don\'t get any response.');
									$('#load_spinner').hide();
									$('#verify_error_msg_div').show();
									can_check_verification = false;
									$('.resend-verification-card').removeClass('make-hidden');
								}
							}
						}
					});
				}
			}, 3000);

			
			setInterval(function() {
				if(can_check_verification == true){
					if(two_factor_timer > 0 ){
						$('#two-factor-timer').html(two_factor_timer-1);
						two_factor_timer -= 1;
						localStorage.setItem("two_factor_timer", two_factor_timer);
					}else{
						$('#two-factor-timer').html("");
						localStorage.removeItem("two_factor_timer");
					}
				}
			}, 1000);
			
		});
	</script>
@endsection