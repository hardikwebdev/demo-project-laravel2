@extends('layouts.frontend.main')
@section('pageTitle', 'Create New Password')
@section('content')
<section class="sub-header">
	<div class="container">
		<div class="row">    
			<div class="col-lg-12">    
				<h2 class="heading mb-2">Create New Password</h2>
                {{-- <div class="subheading">
                </div> --}}
            </div>
        </div>    
    </div>
</section>

<section class="login-section">
	<div class="container">
		<form class="form-horizontal" role="form" method="POST" action="{{ route('user.change_new_password') }}" id="reset_form">
			{{ csrf_field() }}
			@if (session('status'))
			<div class="alert alert-success">
				{{ session('status') }}
			</div>
			@endif

			<div class="row">
				<div class="col-md-6">
					<div class="form-group {{ $errors->has('email') ? ' has-error' : '' }}">
						<label>Email Address</label>
						<input class="form-control" id="email" type="text" placeholder="Email" name="email" value="{{ old('email') }}" autocomplete="off">
						@if ($errors->has('email'))
						<span class="help-block">
							<strong class="errorfield">{{ $errors->first('email') }}</strong>
						</span>
						@endif
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group {{ $errors->has('password') ? ' has-error' : '' }}">
						<label>Password</label>
						<input class="form-control" id="password" type="password" placeholder="Password" name="password" autocomplete="off">
						@if ($errors->has('password'))
						<span class="help-block">
							<strong class="errorfield">{{ $errors->first('password') }}</strong>
						</span>
						@endif
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group {{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
						<label>Confirm Password</label>
						<input class="form-control" id="password_confirmation" type="password" placeholder="Confirm Password" name="password_confirmation" autocomplete="off">
						@if ($errors->has('password_confirmation'))
						<span class="help-block">
							<strong class="errorfield">{{ $errors->first('password_confirmation') }}</strong>
						</span>
						@endif
					</div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group login-btn"> 
						<button type="submit" class="btn"> Password</button>
					</div>
					{{-- <p>Existing User? <a href="{{url('/login')}}" class="">Log In</a></p> --}}
				</div>   
			</div>

		</form>
	</div>
</section>
@endsection

@section('css')

@endsection

@section('scripts')
<script type="text/javascript" src="{{front_asset('js/bootstrapValidator.min.js')}}"></script>
@endsection