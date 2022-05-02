@extends('layouts.frontend.main')
@section('pageTitle','Login-demo')
@section('content')
<section class="sub-header bg-login-banner py-5">
	<div class="container pb-5">
		<div class="row">    
			<div class="col-lg-12">    
				<h2 class="heading mb-2 text-white font-28">Login to your Account</h2>
				<div class="subheading text-color-4 font-14">Enter now to your account and start buying and selling!
				</div>
			</div>
		</div>    
	</div>
</section>

<section class="login-section">
	<div class="container">
		<div class="row justify-content-center">
            <div class="col-12 col-lg-5">
				<form id="login-form" name="login-form" action="{{ url('login') }}" method="post" class="bg-white summary shadow py-4 register-mt-90">

					<input type="hidden" id="is_check_captch" value="0">

					<div class="row px-3 m-0">
						<div class="col-12">
							<div class="alert alert-danger" style="display:none;" id="forgot_error_msg_div">
								<span id="forgot_error_msg"></span>
							</div>
						</div>
					</div>

					<div class="row border-bottom pb-4 px-3 mb-4 m-0">
                        <div class="col-12">
                            <label class="register-text-dark-black font-16 fw-600 register-text-dark-black font-16 fw-600 mb-0">Login To demo</label>
                        </div>
                    </div>
					
					<div class="row px-3 m-0">
						<div class="col-12">
							<div class="form-group">
								<label class="register-text-dark-black font-14 mb-1">Email</label>
								<input  name="_token" class="form-control"  type="hidden" value="{{ csrf_token() }}">
								<input  name="email" placeholder="Email" class="form-control"  type="text">
								@php
								$currentPage = \Request::route()->getName();
								@endphp
								<input type="hidden" id="profileurl" name="profileurl" value="{{\URL::previous()}}">
								<input type="hidden" id="reactivation_url" value="{{route('reactivation')}}">
							</div>
						</div>
					</div>
					<div class="row px-3 m-0">
						<div class="col-12">
							<div class="form-group">
								<label class="register-text-dark-black font-14 mb-1">Password</label>
								<input  name="password" placeholder="Password" class="form-control"  type="password">
							</div>
						</div>
					</div>
					<div class="row px-3 m-0">
						<div class="col-12 col-sm-6">
							<div class="form-group  add-extra-detail m-0">
								<label class="cus-checkmark">    
									{{-- <input id="rememberme" name="remember" type="checkbox" {{ old('remember') ? 'checked' : '' }}> --}}
									<input id="rememberme" name="remember" type="checkbox" value="true">
									<span class="checkmark"></span>
								</label>
								<div class="detail-box">
									<lable class="register-text-dark-black font-14 mb-1">Remember me</lable>
								</div>
							</div>
						</div> 
						<div class="col-12 col-sm-6 text-right">
							<div class="form-group  add-extra-detail m-0">
								<div class="forget-password">
									<p class="m-0 font-14"><a href="{{url('password/reset')}}" class="primary promo-popup text-color-1">Forgot Password?</a></p>  
								</div>
							</div>
						</div>    
					</div>

					<div class="row px-3 m-0 mt-3">
						<div class="col-12 text-center">
							<div class="form-group login-btn"> 
								<button type="submit" class="btn btn-block bg-primary-blue">Login</button>
							</div>
							<p class="register-text-dark-black font-14 mb-1">New to demo? <a href="{{url('/register')}}" class="text-color-1">Register Now</a></p>
						</div>   
					</div>

				</form>
			</div>
		</div>
	</div>
</section>
@endsection