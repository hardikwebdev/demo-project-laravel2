@extends('layouts.frontend.main')
@section('pageTitle', 'Reset Password')
@section('content')
<section class="sub-header bg-login-banner py-5">
	<div class="container pb-5">
		<div class="row">
			<div class="col-lg-12">
				<h2 class="heading mb-2 text-white font-28">Reset your password</h2>
				{{-- <div class="subheading">
				</div> --}}
			</div>
		</div>
	</div>
</section>

<section class="login-section">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-12 col-lg-5">
				<form class="form-horizontal bg-white summary shadow py-4 register-mt-90" role="form" method="POST" action="{{ route('password.requestFront') }}"
					id="reset_form">
					{{ csrf_field() }}
					<input type="hidden" name="token" value="{{ $token }}">
					@if (session('status'))
					<div class="alert alert-success">
						{{ session('status') }}
					</div>
					@endif

					<div class="row border-bottom pb-4 px-3 mb-4 m-0">
                        <div class="col-12">
                            <label class="register-text-dark-black font-16 fw-600 register-text-dark-black font-16 fw-600 mb-0">Reset Password</label>
                        </div>
                    </div>

					<div class="row px-3 m-0">
						<div class="col-12">
							<div class="form-group {{ $errors->has('email') ? ' has-error' : '' }}">
								<label class="register-text-dark-black font-14 mb-1">Email Address</label>
								<input class="form-control" id="email" type="text" placeholder="Email" name="email"
									value="{{ $email or old('email') }}">
								@if ($errors->has('email'))
								<span class="help-block">
									<strong class="errorfield">{{ $errors->first('email') }}</strong>
								</span>
								@endif
							</div>
						</div>
					</div>

					<div class="row px-3 m-0">
						<div class="col-12">
							<div class="form-group {{ $errors->has('password') ? ' has-error' : '' }}">
								<label class="register-text-dark-black font-14 mb-1">Password</label>
								<input class="form-control" id="password" type="password" placeholder="Password"
									name="password">
								@if ($errors->has('password'))
								<span class="help-block">
									<strong class="errorfield">{{ $errors->first('password') }}</strong>
								</span>
								@endif
							</div>
						</div>
					</div>

					<div class="row px-3 m-0">
						<div class="col-12">
							<div class="form-group {{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
								<label class="register-text-dark-black font-14 mb-1">Confirm Password</label>
								<input class="form-control" id="password_confirmation" type="password"
									placeholder="Confirm Password" name="password_confirmation">
								@if ($errors->has('password_confirmation'))
								<span class="help-block">
									<strong class="errorfield">{{ $errors->first('password_confirmation') }}</strong>
								</span>
								@endif
							</div>
						</div>
					</div>

					<div class="row px-3 m-0">
						<div class="col-12">
							<div class="form-group login-btn">
								<button type="submit" class="btn btn-block bg-primary-blue"> Password</button>
							</div>
							{{-- <p>Existing User? <a href="{{url('/login')}}" class="">Log In</a></p> --}}
						</div>
					</div>

				</form>
			</div>
		</div>
	</div>
</section>
@endsection

@section('css')

@endsection

@section('scripts')
<script type="text/javascript" src="{{front_asset('js/bootstrapValidator.min.js')}}"></script>
@endsection