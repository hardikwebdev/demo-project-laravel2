@extends('layouts.frontend.main')
@section('pageTitle','Register-demo')
@section('content')

<section class="sub-header bg-login-banner py-5">
    <div class="container pb-5">
        <div class="row">
            <div class="col-lg-12">
                <h2 class="heading mb-2 text-white font-28">Register Account</h2>
                <div class="subheading text-color-4 font-14">Join the platform where people go to Get More Stuff Done™</div>
            </div>
        </div>
    </div>
</section>

<section class="all-category register-section">
    <div class="container">
        @include('layouts.frontend.messages')
        <div class="row justify-content-center">
            <div class="col-12 col-lg-7">
                <form id="createaccount" name="createaccount" action="{{route('register')}}" method="post" class="bg-white summary shadow py-4 register-mt-90">

                    {{-- <div class="row">
                        <div class="col-md-6">
                            <div id="response" class="registersucess"></div>
                        </div>
                    </div> --}}

                    
                    <div class="row border-bottom pb-4 px-3 mb-4 m-0">
                        <div class="col-12">
                            <label class="register-text-dark-black font-16 fw-600 register-text-dark-black font-16 fw-600 mb-0">Fill the form to create your Account</label>
                        </div>
                    </div>

                    <div class="row px-3 m-0">
                        <div class="col-md-6">
                            <div class="alert alert-danger" style="display:none;" id="forgot_error_msg_div">
                                <span id="forgot_error_msg"></span>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="register-text-dark-black font-14 mb-1">Full Name*</label>
                                <input name="name" placeholder="Full Name" class="form-control" type="text">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="register-text-dark-black font-14 mb-1">Email*</label>
                                <input name="email" placeholder="Email" class="form-control" type="text">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="register-text-dark-black font-14 mb-1">Username*</label>
                                <input name="username" placeholder="Username" class="form-control" type="text"
                                    data-bv-field="username">
                                <input name="_token" class="form-control" type="hidden" value="{{ csrf_token() }}"
                                    id="_token">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="register-text-dark-black font-14 mb-1">Password*</label>
                                <input name="password" placeholder="Password" class="form-control" type="password">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="register-text-dark-black font-14 mb-1">Confirm Password*</label>
                                <input name="confirm_password" placeholder="Confirm Password" class="form-control"
                                    type="password" data-bv-field="confirm_password">
                            </div>
                        </div>
                    </div>

                    <div class="row px-3 border-top border-bottom py-4 my-2 m-0">
                        <div class="col-12">
                            <div class="row register_radio">
                                <div class="col-12">
                                    <label class="register-text-dark-black font-16 fw-600">Account Type*</label>
                                    <p class="register-text-dark-black font-16">Select what type of account you're interested in</p>
                                </div>
                                <div class="col-12 col-md-6 col-xl-4 pr-xl-2">
                                    <div class="h-100">
                                        <input class="form-check-input d-none" type="radio" name="interested_in"
                                            id="buying_service" value="1" checked data-bv-field="interested_in">
                                        <label class="h-100 form-check-label summary p-3 d-flex align-items-center cursor-pointer service_label" for="buying_service">
                                            <div>
                                                <img src="{{url('public/frontend/images/buyer.png')}}">			
                                            </div>
                                            <div class="ml-3">
                                                <label class="register-text-dark-black font-14 fw-600 mb-0">Buying</label>
                                                <p class="register-text-light-gray font-12 mb-0">Buying services to grow my business</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-xl-4 mt-3 mt-md-0 px-xl-2">
                                    <div class="h-100">
                                        <input class="form-check-input d-none" type="radio" name="interested_in"
                                            id="selling_service" value="2" data-bv-field="interested_in">
                                        <label class="h-100 form-check-label summary p-3 d-flex align-items-center cursor-pointer service_label" for="selling_service">
                                            <div>
                                                <img src="{{url('public/frontend/images/seller.png')}}">			
                                            </div>
                                            <div class="ml-3">
                                                <label class="register-text-dark-black font-14 fw-600 mb-0">Selling</label>
                                                <p class="register-text-light-gray font-12 mb-0">Selling my services to make money</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6 col-xl-4 mt-3 mt-xl-0 pl-xl-2">
                                    <div class="h-100">
                                        <input class="form-check-input d-none" type="radio" name="interested_in"
                                            id="both_service" value="3" data-bv-field="interested_in">
                                        <label class="h-100 form-check-label summary p-3 d-flex align-items-center cursor-pointer service_label" for="both_service">
                                            <div>
                                                <img src="{{url('public/frontend/images/buyer-seller.png')}}">			
                                            </div>
                                            <div class="ml-3">
                                                <label class="register-text-dark-black font-14 fw-600 mb-0">Buying & Selling</label>
                                                <p class="register-text-light-gray font-12 mb-0">The best of both worlds</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row px-3 mt-4 m-0">            
                        {{-- <div class="col-12">
                            <div class="form-group cusswitch">
                                <label for="notification">Two-Factor Authentication</label>
                                <label class="cus-switch togglenotification">
                                    <input class="toggle-input" name="towfactorauth" type="checkbox" value="1">
                                    <span class="checkslider round"></span>
                                </label>
                            </div>
                        </div> --}}

                        <div class="col-12">
                            @if(config('services.recaptcha.sitekey'))
                            <div class="g-recaptcha" data-sitekey="{{config('services.recaptcha.sitekey')}}">
                            </div>
                            @endif
                        </div>

                        <div class="col-12 mt-3">
                            <div class="form-group  add-extra-detail">
                                <label class="cus-checkmark">
                                    <input name="terms_privacy" type="checkbox">
                                    <span class="checkmark"></span>
                                </label>
                                <div class="detail-box">
                                    <lable class="register-text-dark-black font-14 fw-600"> I agree to the <a target="_blank" class="text-color-1" href="https://www.demo.com/terms">terms
                                            of service</a> and <a target="_blank" class="text-color-1"
                                            href="https://www.demo.com/privacy">privacy policy</a></lable>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group register-btn">
                                <button type="submit" class="btn register-btn bg-primary-blue">Register</button>
                            </div>
                            <p class="register-text-dark-black font-14 mb-1">Existing User? <a href="{{url('/login')}}" class="text-color-1">Log in</a></p>
                        </div>
                    </div>


                    {{-- <div class="col-md-6">
                        <div class="form-group">
                            <label>Email</label>
                            <input name="email" placeholder="Email" class="form-control" type="text">
                        </div>
                    </div> --}}

                    {{-- <div class="row">
                        <div class="col-md-6 form-group">
                            <input type="hidden" name="is_verify_towfactorauth" id="is_verify_towfactorauth" value="0">
                            <input type="hidden" name="verified_mobile_no" id="verified_mobile_no" value="">

                            <div class="input-group">
                                <span class="input-group-addon">
                                    {{Form::select('country_code',country_code_list(),1,["id"=>"country_code","class"=>"form-control"])}}
                                </span>
                                <input id="mobile_no" type="text" class="form-control" name="mobile_no"
                                    placeholder="Enter valid mobile number" value="" maxlength="12" width="100">
                            </div>
                        </div>
                    </div> --}}
                </form>
            </div>
        </div>
    </div>
</section>

@endsection