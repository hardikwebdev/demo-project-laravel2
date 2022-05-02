@extends('layouts.frontend.main')
@section('pageTitle','Login-demo')
@section('content')
<section class="sub-header bg-login-banner py-5">
    <div class="container pb-5">
        <div class="row">    
            <div class="col-lg-12">    
                <h2 class="heading mb-2 text-white font-28">Forgot Password</h2>
                <div class="subheading text-color-4 font-14">Please enter your email address. You will receive a link to create a new password via email.
                </div>
            </div>
        </div>    
    </div>
</section>

<section class="login-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-5">
                <form class="form-horizontal bg-white summary shadow py-4 register-mt-90" role="form" method="POST" action="{{ url('password/email') }}" id="forgot_form">

                    {{ csrf_field() }}

                    <div class="row px-3 m-0">
                        <div class="col-12">
                            <div class="alert alert-danger" style="display:none;" id="forgot_error_msg_div">
                                <span id="forgot_error_msg"></span>
                            </div>
                            <div class="alert alert-success" style="display:none;" id="forgot_success_msg_div">
                                <span id="forgot_success_msg"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row border-bottom pb-4 px-3 mb-4 m-0">
                        <div class="col-12">
                            <label class="register-text-dark-black font-16 fw-600 mb-0">Forgot Password</label>
                        </div>
                    </div>
                    
                    <div class="row px-3 m-0">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="register-text-dark-black font-14 mb-1">Email Address</label>
                                <input  name="email" placeholder="Email" class="form-control"  type="text">
                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong class="errorfield">{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row px-3 m-0">
                        <div class="col-12 text-center">
                            <div class="form-group login-btn"> 
                                <button type="submit" class="btn btn-block bg-primary-blue">Reset Password</button>
                            </div>
                            <p class="register-text-dark-black font-14 mb-1">Existing User? <a href="{{url('/login')}}" class="text-color-1">Log In</a></p>
                        </div>   
                    </div>

                </form>
            </div>
        </div>
    </div>
</section>
@endsection