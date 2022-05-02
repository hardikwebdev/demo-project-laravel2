@extends('layouts.frontend.main')
@section('pageTitle','demo - Payment Method')
@section('content')
<!-- Display Error Message -->
@include('layouts.frontend.messages')



<!-- Masthead -->
<header class="masthead text-white"> {{-- masthead  --}}
	<div class="overlay"></div>
    <div class="bg-dark w-100">
	<div class="container py-4 d-flex justify-content-center font-lato">
        <div class="d-flex align-items-center flex-column flex-md-row justify-content-md-around parent-cart-step">
            <p class="text-color-1 mb-0 font-16"><i class="fas fa-check cart-step-1 mx-2 font-10 py-2"></i> Order Details </p>
            <i class="fas fa-chevron-right arrow-right"></i>
            <p class="text-color-1 mb-0 font-16"><span class="font-13 cart-step-1 font-weight-bold mx-2">2</span>Payment Options </p>
            <i class="fas fa-chevron-right arrow-right"></i>
            <p class="text-color-7 mb-0 font-16"><span class="font-13 cart-step-2 text-color-7 font-weight-bold mx-2">3</span>Submit Requirements </p>
        </div>
	</div>
    </div>
</header>

<section class="pricing-section transactions-table pb-5">

    <div class="container font-lato">   
        <div class="row mt-5">
            <div class="col-12 col-md-8 col-lg-9 pr-md-0 pr-xl-3">    
                <div class="row">
                    <div class="col-12 pb-4">
                        <h1 class="font-30 text-color-2 font-weight-bold">Select Payment Method</h1>
                    </div>
                </div>
                <div class="row summary mx-0">
                    @php
                        /* check for restricted categories */
                        $res = services_with_restricted_cat_subcat($service_id_list);

                        /*Check max order amount of bluesnap*/
                        $isvalid_bluesnap_max_amount = true;
                        if(!empty($settings) && $settings->max_bluesnap_order_amount){
                            if($final_total > $settings->max_bluesnap_order_amount){
                                $isvalid_bluesnap_max_amount = false;
                            }
                        }
                    @endphp

                    {{-- credit card option - start --}}
                    @if($is_recurring_service == 0 && $final_total != 0)
                    @if($res['status'] == "success" && $isvalid_bluesnap_max_amount == true)
                    <div class="col-12">
                        <div class=" d-flex align-items-center py-3 ">
                            <input type="radio" class="mx-3 radio_class" autocomplete="off" value="creditcard" name="radio_payment">
                            <p class="font-20 font-weight-bold text-color-2 mb-0">Credit Card</p>
                        </div>
                    </div>
                    @else 
                    <div class="col-12 py-3">
                        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center">
                            <div class=" d-flex align-items-center">
                                <input type="radio" class="mx-3" autocomplete="off" disabled>
                                <p class="font-20 font-weight-bold text-color-2 mb-0 opacity-3">Credit Card</p>
                            </div>
                            <p class="font-12 text-color-4 mb-0 ml-5 ml-lg-4">Services in this category are unavailable for checkout via credit card</p>
                        </div>
                    </div>
                    @endif
                    @endif
                    {{-- credit card option - end --}}

                    {{-- skrill payment option - start --}}
                    @if($final_total > 0)
                    <div class="col-12 border-top-gray">
                        <div class=" d-flex align-items-center py-3 ">
                            <input type="radio" class="mx-3 radio_class" autocomplete="off" name="radio_payment" @if(($fromPromotionalAmount + $fromWalletAmount) < $final_total) checked @endif value="skrill">
                            <img src="{{url('public/frontend/images/skrill.png')}}" class="skrill-logo img-fluid" alt="Pay By Skrill">
                        </div>
                        @if($fromWalletAmount > 0 && $fromWalletAmount < $final_total)
                        <div class="alert-secondary ml-auto py-2 mb-4 d-flex" id="use_wallet_with_skrill_div" @if(($fromPromotionalAmount + $fromWalletAmount) >= $final_total) style="display:none!important" @endif>
                            <div class="px-2 px-md-0">
                                <label class="payment-switch mb-0 mx-2 mx-md-4">
                                    <input type="checkbox" id="use_wallet_with_skrill" data-bill="{{$final_total}}" data-walletandpromo="{{$fromPromotionalAmount + $fromWalletAmount}}">
                                    <span class="payment-slider round"></span>
                                </label>
                            </div>
                            <div class="d-flex flex-column flex-xl-row align-items-start"> 
                                <span class="font-14 text-color-4 font-weight-bold"  id="use_wallet_with_skrill_text">Use your ${{$fromWalletAmount}} balance from your Wallet</span>
                                <span class="font-14  text-color-4 font-weight-normal ml-xl-3">We will charge the rest to Skrill</span>
                            </div>
                        </div>
                        @endif
                        @if($fromPromotionalAmount > 0 && $fromPromotionalAmount < $final_total)
                        <div class="alert-primary ml-auto py-2 mt-4 mb-4 d-flex" id="use_promo_with_skrill_div" @if(($fromPromotionalAmount + $fromWalletAmount) >= $final_total) style="display:none!important" @endif>
                            <div class='px-2 px-md-0'>
                                <label class="payment-switch mb-0 mx-2 mx-md-4">
                                    <input type="checkbox" checked id="use_promo_with_skrill" data-bill="{{$final_total}}" data-walletandpromo="{{$fromPromotionalAmount + $fromWalletAmount}}">
                                    <span class="payment-slider round"></span>
                                </label>
                            </div>
                            <div class="d-flex flex-column flex-xl-row align-items-start"> 
                               <span class="font-14 text-color-1 font-weight-bold" id="use_promo_with_skrill_text">Use your ${{$fromPromotionalAmount}} balance from your demo Bucks</span>
                               <span class="font-14 text-color-4 font-weight-normal ml-xl-3">We will charge the rest to Skrill</span>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                    {{-- skrill payment option - end --}}

                    {{-- paypal option - start --}}
                    @if($final_total != 0)
                    <div class="col-12 border-top-gray">
                        <div class=" d-flex align-items-center py-3">
                            <input type="radio" class="mx-3 radio_class" autocomplete="off" name="radio_payment" value="paypal">
                            <img src="{{url('public/frontend/images/paypal.png')}}" class="img-fluid" alt="">
                        </div>
                        @if($fromWalletAmount > 0 && $fromWalletAmount < $final_total)
                        <div class="alert-secondary ml-auto py-2 mb-4 d-flex" id="use_wallet_with_paypal_div" style="display:none!important">
                            <div class="px-2 px-md-0">
                                <label class="payment-switch mb-0 mx-2 mx-md-4">
                                    <input type="checkbox" id="use_wallet_with_paypal" data-walletandpromo="{{$fromPromotionalAmount + $fromWalletAmount}}">
                                    <span class="payment-slider round"></span>
                                </label>
                            </div>
                            <div class="d-flex flex-column flex-xl-row align-items-start"> 
                                <span class="font-14 text-color-4 font-weight-bold"  id="use_wallet_with_paypal_text">Use your ${{$fromWalletAmount}} balance from your Wallet</span>
                                <span class="font-14  text-color-4 font-weight-normal ml-xl-3">We will charge the rest to PayPal</span>
                            </div>
                        </div>
                        @endif
                        @if($fromPromotionalAmount > 0 && $fromPromotionalAmount < $final_total)
                        <div class="alert-primary ml-auto py-2 mt-4 d-flex mb-4" id="use_promo_with_paypal_div" style="display:none!important">
                            <div class='px-2 px-md-0'>
                                <label class="payment-switch mb-0 mx-2 mx-md-4">
                                    <input type="checkbox" checked id="use_promo_with_paypal" data-walletandpromo="{{$fromPromotionalAmount + $fromWalletAmount}}">
                                    <span class="payment-slider round"></span>
                                </label>
                            </div>
                            <div class="d-flex flex-column flex-xl-row align-items-start"> 
                               <span class="font-14 text-color-1 font-weight-bold" id="use_promo_with_paypal_text">Use your ${{$fromPromotionalAmount}} balance from your demo Bucks</span>
                               <span class="font-14  text-color-4 font-weight-normal ml-xl-3">We will charge the rest to PayPal</span>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                    {{-- paypal option - end --}}
                    
                    {{-- wallet option - start --}}
                    @if($is_recurring_service == 0)
                    <div class="col-12 border-top-gray pb-3">
                        @if(($fromPromotionalAmount + $fromWalletAmount) >= $final_total)
                        <div class="row">
                            <div class="col-12 col-md-6 col-lg-8">
                                <div class=" d-flex align-items-center py-3">
                                    <input type="radio" class="mx-3 radio_class" autocomplete="off" name="radio_payment" checked value="wallet">
                                    <p class="font-20 font-weight-bold text-color-2 mb-0"><img src="{{url('public/frontend/images/account_balance_wallet.png')}}" class="img-fluid pr-2" alt="">  Wallet</p>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 col-lg-4 sub-wallet-desc">
                                <div class="row align-items-center pt-3">
                                    <div class="col-8 text-right">
                                        <p class="mb-0 font-12 text-color-2">Total Balance</p>
                                    </div>
                                    <div class="col-4 text-right pl-lg-0 ">
                                        <p class="mb-0 font-20 text-color-1 font-weight-bold">${{$fromWalletAmount}}</p>
                                    </div>
                                </div> 
                                <div class="row pt-2">
                                    <div class="col-8 text-right">
                                        <p class="mb-0 font-12 text-color-2">demo Bucks</p>
                                    </div>
                                    <div class="col-4 text-right">
                                        <p class="mb-0 font-14 text-color-1">${{$fromPromotionalAmount}}</p>
                                    </div>
                                </div> 
                            </div>
                        </div>
       
                        @if($fromPromotionalAmount > 0)
                        <div class="alert-primary ml-auto py-2 mt-4 d-flex sub-wallet-desc">
                            <div class='px-2 px-md-0'>
                                <label class="payment-switch mb-0 mx-md-4">
                                    <input type="checkbox" checked disabled>
                                    <span class="payment-slider round"></span>
                                </label>
                            </div>
                            <div class="d-flex  align-items-center"> 
                                <span class="font-14 text-color-1 font-weight-bold">Use your demo Bucks first</span> 
                            </div>
                        </div>
                        @else 
                        <div class="alert-secondary ml-auto py-2 mt-4 d-flex sub-wallet-desc">
                            <div class='px-2 px-md-0'>
                                <label class="payment-switch mb-0 mx-md-4">
                                    <input type="checkbox" disabled>
                                    <span class="payment-slider round"></span>
                                </label>
                            </div>
                            <div class="d-flex  align-items-center"> 
                                <span class="font-14 text-color-4 font-weight-bold">Use your demo Bucks first</span> 
                            </div>
                        </div>
                        @endif
                        @else 
                        <div class="row">
                            <div class="col-12 col-lg-8">
                                <div class="d-flex flex-column flex-xl-row align-items-start align-items-xl-center">
                                    <div class=" d-flex align-items-center py-3">
                                        <input type="radio" class="mx-3" autocomplete="off" disabled>
                                        <p class="font-20 font-weight-bold text-color-2 mb-0 opacity-3"><img src="{{url('public/frontend/images/account_balance_wallet.png')}}" class="img-fluid pr-2" alt=""> Wallet</p>
                                    </div>
                                    <p class="font-12 text-color-4 mb-0 ml-5 ml-xl-4 sub-wallet-desc" style="display: none;">We’re sorry, you don’t have enough money in your wallet </p>
                                </div>         
                            </div>
                            <div class="col-12 col-lg-4 sub-wallet-desc" style="display: none;">
                                <div class="row align-items-center pt-3">
                                    <div class="col-8 col-md-10 col-lg-8 text-right">
                                        <p class="mb-0 font-12 text-color-2">Total Balance</p>
                                    </div>
                                    <div class="col-4 col-md-2 col-lg-4 text-right pl-lg-0 ">
                                        <p class="mb-0 font-20 text-color-1 font-weight-bold">${{$fromWalletAmount}}</p>
                                    </div>
                                </div> 
                                <div class="row pt-2">
                                    <div class="col-8 col-md-10 col-lg-8 text-right">
                                        <p class="mb-0 font-12 text-color-2">demo Bucks</p>
                                    </div>
                                    <div class="col-4 col-md-2 col-lg-4 text-right">
                                        <p class="mb-0 font-14 text-color-1">${{$fromPromotionalAmount}}</p>
                                    </div>
                                </div> 
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif
                    {{-- wallet option - end --}}
                </div>
            </div>
                        
            <div class="col-12 col-md-4 col-lg-3">
                <div class="row mt-4 mt-md-0">
                    <div class="col-12">
                        <div class="p-3 summary">
                            
                            <p class="text-left text-color-2 py-2 font-13 font-weight-bold mb-0 text-capitalize">{{Str::limit($service->title,30)}}</p>
                            <div class="d-flex justify-content-between py-2">
                                {{-- <i class="fas fa-check text-color-1 mr-2"></i>
                                <span class="w-75 font-12 text-color-3">{{$service_info['plan_title']}}</span> 
                                <span class="font-12 text-color-3 font-weight-bold">x{{$service_info['quantity']}}</span> --}}
                                <span class="font-12 text-color-4"><i class="far fa-clock" aria-hidden="true"></i><span class="mx-2"> {{$total_days}} sponsor days</span></span>
                                <span class="w-25 text-right float-right font-14 text-color-3">${{$sub_total}}</span>
                            </div>
                            <hr>
   
                            <div class="d-flex justify-content-between pt-1">
                                <p class="mb-0 font-16 text-color-3">Total</p>
                                <p class="mb-0 font-18 text-color-3 font-weight-bold">${{$final_total}}</p>
                            </div>         
                            <hr>
                            
                            @if($is_recurring_service == 0)

                            <form id="payFromWalletForm" action="{{route('paypal_express_checkout_boost_paynow')}}" method="post" class="custom" style="display: none;">
								{{csrf_field()}}
                                <input type="hidden" name="is_from_wallet" id="allow_wallet" @if($fromPromotionalAmount > 0 && $fromPromotionalAmount >= $final_total) value="0" @else value="1" @endif>
                                <input type="hidden" name="is_from_promotional" id="allow_promotional" @if($fromPromotionalAmount > 0) value='1' @else value="0" @endif>
                                <input type="hidden" name="coupon_id" @if($coupon_id != '0') value="{{$coupon_id}}" @endif>
                                <button class="btn text-white bg-primary-blue border-radius-6px w-100 py-2 mt-2 continue_checkout_btn" type="submit" onclick="this.disabled=true;this.form.submit();">Confirm and Pay</button>
                            </form>

                            
                            
                            @if($is_recurring_service == 0 && $res['status'] == "success" && $isvalid_bluesnap_max_amount == true && $final_total != 0)
                            <form class="panel-body" id="payCreditCardForm" action="{{route('bluesnapBootServicePayment')}}" method="POST" style="display: none;">
                                {{csrf_field()}}
                                <input type="hidden" name="from_wallet" value="0">
                                <input type="hidden" name="coupon_id" @if($coupon_id != '0') value="{{$coupon_id}}" @endif>
                                <button class="btn text-white bg-primary-blue border-radius-6px w-100 py-2 mt-2 continue_checkout_btn" type="submit" onclick="this.disabled=true;this.form.submit();">Confirm and Pay</button>
                            </form>
                            @endif
                            @endif

                            @if($final_total != 0)
                            <form action="{{route('paypal_express_checkout_boost')}}" method="post" id="paypalForm" class="custom" style="display: none;">
								{{csrf_field()}}
                                <input type="hidden" name="is_from_wallet" id="is_from_wallet" value="0">
                                <input type="hidden" name="is_from_promotional" id="is_from_promotional" @if($fromPromotionalAmount < $final_total && $fromPromotionalAmount > 0) value="1" @else value="0" @endif>
                                <input type="hidden" name="coupon_id" @if($coupon_id != '0') value="{{$coupon_id}}" @endif>
                                <button class="btn text-white bg-primary-blue border-radius-6px w-100 py-2 mt-2 paypal_btn">Confirm and Pay</button>
                            </form>

                            <form action="{{route('skrill.boost.checkout')}}" method="post" id="paySkrillForm" class="custom" style="display: none;">
								{{csrf_field()}}
                                <input type="hidden" name="is_from_wallet" id="allow_from_wallet" value="0">
                                <input type="hidden" name="is_from_promotional" id="allow_from_promotional" @if($fromPromotionalAmount < $final_total && $fromPromotionalAmount > 0) value="1" @else value="0" @endif>
                                <input type="hidden" name="coupon_id" @if($coupon_id != '0') value="{{$coupon_id}}" @endif>
                                <button class="btn text-white bg-primary-blue border-radius-6px w-100 py-2 mt-2 paypal_btn">Confirm and Pay</button>
                            </form>
                            @endif

                        </div>
                    </div>
                </div>
            </div>       
        </div>
    </div>
</section>

@endsection

@section('css')
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="{{url('public/frontend/css/price_range_style.css')}}"/>
@endsection

@section('scripts')
<script type="text/javascript" src="{{front_asset('bootstrap/dist/js/bootstrap-tagsinput.js')}}"></script> 
<script type="text/javascript" src="{{url('public/frontend/js/price_range_script.js')}}"></script>
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script src="{{ asset('resources/assets/js/ad_rent_spot.js') }}"></script>

<script>
    var from_wallet_amt = '{{$fromWalletAmount}}';
    var from_promo_amt = '{{$fromPromotionalAmount}}';
    var find_total_amt = '{{$final_total}}';
    $(document).ready(function(){
        var active_radio = $("input[name='radio_payment']:checked").val();
        if(active_radio == 'wallet'){
            $('#payFromWalletForm').css('display','block');
            $('#payCreditCardForm').css('display','none');
            $('#paypalForm').css('display','none');
            $('#paySkrillForm').css('display','none');
        }
        if(active_radio == 'paypal'){
            $('#payFromWalletForm').css('display','none');
            $('#payCreditCardForm').css('display','none');
            $('#paypalForm').css('display','block');
            $('#paySkrillForm').css('display','none');
        }
        if(active_radio == 'creditcard'){
            $('#payFromWalletForm').css('display','none');
            $('#payCreditCardForm').css('display','block');
            $('#paypalForm').css('display','none');
            $('#paySkrillForm').css('display','none');
        }

        if(active_radio == 'skrill'){
            $('#payFromWalletForm').css('display','none');
            $('#payCreditCardForm').css('display','none');
            $('#paypalForm').css('display','none');
            $('#paySkrillForm').css('display','block');
        }

        $(".radio_class").unbind('click').bind('click', function() {
            var active_radio = $("input[name='radio_payment']:checked").val();
            if(active_radio == 'wallet'){
                $('#payFromWalletForm').css('display','block');
                $('#payCreditCardForm').css('display','none');
                $('#paypalForm').css('display','none');
                $('#paySkrillForm').css('display','none');
                $('.sub-wallet-desc').css('display','block');
                $('#use_wallet_with_paypal_div').attr("style", "display: none !important");
                $('#use_promo_with_paypal_div').attr("style", "display: none !important");
                $('#use_wallet_with_skrill_div').attr("style", "display: none !important");
                $('#use_promo_with_skrill_div').attr("style", "display: none !important");
            }
            if(active_radio == 'paypal'){
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','none');
                $('#paypalForm').css('display','block');
                $('#paySkrillForm').css('display','none');
                $('.sub-wallet-desc').attr("style", "display: none !important");
                $('#use_wallet_with_paypal_div').css('display','block');
                $('#use_promo_with_paypal_div').attr("style", "display: block");
                $('#use_wallet_with_skrill_div').attr("style", "display: none !important");
                $('#use_promo_with_skrill_div').attr("style", "display: none !important");
            }
            if(active_radio == 'creditcard'){
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','block');
                $('#paypalForm').css('display','none');
                $('#paySkrillForm').css('display','none');
                $('.sub-wallet-desc').attr("style", "display: none !important");
                $('#use_wallet_with_paypal_div').attr("style", "display: none !important");
                $('#use_promo_with_paypal_div').attr("style", "display: none !important");
                $('#use_wallet_with_skrill_div').attr("style", "display: none !important");
                $('#use_promo_with_skrill_div').attr("style", "display: none !important");
            }
            if(active_radio == 'skrill'){
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','none');
                $('#paypalForm').css('display','none');
                $('#paySkrillForm').css('display','block');
                $('.sub-wallet-desc').attr("style", "display: none !important");
                $('#use_wallet_with_paypal_div').attr("style", "display: none !important");
                $('#use_promo_with_paypal_div').attr("style", "display: none !important");
                $('#use_wallet_with_skrill_div').attr("style", "display: block");
                $('#use_promo_with_skrill_div').attr("style", "display: block");
            }
        });

        $('#use_wallet_with_paypal').unbind('change').bind('change', function () {
            var walletandpromo = $(this).data('walletandpromo');
            if(this.checked) {
                if(walletandpromo >= find_total_amt && $('#use_promo_with_paypal').is(':checked')) {
                    $('#use_wallet_with_paypal').prop('checked', false);
                    toastr.error("You have already enough balance to place the order.", "Error");
                    return false;
                }
                $('#is_from_wallet').val(1);
                if($('#use_wallet_with_paypal_div').hasClass('alert-secondary')) {
                    $('#use_wallet_with_paypal_div').removeClass('alert-secondary');
                }
                if(!$('#use_wallet_with_paypal_div').hasClass('alert-primary')) {
                    $('#use_wallet_with_paypal_div').addClass('alert-primary');
                }
                if($('#use_wallet_with_paypal_text').hasClass('text-color-4')) {
                    $('#use_wallet_with_paypal_text').removeClass('text-color-4');
                }
                if(!$('#use_wallet_with_paypal_text').hasClass('text-color-1')) {
                    $('#use_wallet_with_paypal_text').addClass('text-color-1');
                }
            } else {
                $('#is_from_wallet').val(0);
                if($('#use_wallet_with_paypal_div').hasClass('alert-primary')) {
                    $('#use_wallet_with_paypal_div').removeClass('alert-primary');
                }
                if(!$('#use_wallet_with_paypal_div').hasClass('alert-secondary')) {
                    $('#use_wallet_with_paypal_div').addClass('alert-secondary');
                }
                if($('#use_wallet_with_paypal_text').hasClass('text-color-1')) {
                    $('#use_wallet_with_paypal_text').removeClass('text-color-1');
                }
                if(!$('#use_wallet_with_paypal_text').hasClass('text-color-4')) {
                    $('#use_wallet_with_paypal_text').addClass('text-color-4');
                }
            }
        });

        $('#use_wallet_with_skrill').unbind('change').bind('change', function () {
            var walletandpromo = $(this).data('walletandpromo');
            if(this.checked) {
                if(walletandpromo >= find_total_amt && $('#use_promo_with_skrill').is(':checked')) {
                    $('#use_wallet_with_skrill').prop('checked', false);
                    toastr.error("You have already enough balance to place the order.", "Error");
                    return false;
                }
                $('#allow_from_wallet').val(1);
                if($('#use_wallet_with_skrill_div').hasClass('alert-secondary')) {
                    $('#use_wallet_with_skrill_div').removeClass('alert-secondary');
                }
                if(!$('#use_wallet_with_skrill_div').hasClass('alert-primary')) {
                    $('#use_wallet_with_skrill_div').addClass('alert-primary');
                }
                if($('#use_wallet_with_skrill_text').hasClass('text-color-4')) {
                    $('#use_wallet_with_skrill_text').removeClass('text-color-4');
                }
                if(!$('#use_wallet_with_skrill_text').hasClass('text-color-1')) {
                    $('#use_wallet_with_skrill_text').addClass('text-color-1');
                }
            } else {
                $('#allow_from_wallet').val(0);
                if($('#use_wallet_with_skrill_div').hasClass('alert-primary')) {
                    $('#use_wallet_with_skrill_div').removeClass('alert-primary');
                }
                if(!$('#use_wallet_with_skrill_div').hasClass('alert-secondary')) {
                    $('#use_wallet_with_skrill_div').addClass('alert-secondary');
                }
                if($('#use_wallet_with_skrill_text').hasClass('text-color-1')) {
                    $('#use_wallet_with_skrill_text').removeClass('text-color-1');
                }
                if(!$('#use_wallet_with_skrill_text').hasClass('text-color-4')) {
                    $('#use_wallet_with_skrill_text').addClass('text-color-4');
                }
            }
        });

        $('#use_promo_with_paypal').unbind('change').bind('change', function () {
            var walletandpromo = $(this).data('walletandpromo');
            if(this.checked) {
                if(walletandpromo >= find_total_amt && $('#use_wallet_with_paypal').is(':checked')) {
                    $('#use_promo_with_paypal').prop('checked', false);
                    toastr.error("You have already enough balance to place the order.", "Error");
                    return false;
                }
                $('#is_from_promotional').val(1);
                if($('#use_promo_with_paypal_div').hasClass('alert-secondary')) {
                    $('#use_promo_with_paypal_div').removeClass('alert-secondary');
                }
                if(!$('#use_promo_with_paypal_div').hasClass('alert-primary')) {
                    $('#use_promo_with_paypal_div').addClass('alert-primary');
                }
                if($('#use_promo_with_paypal_text').hasClass('text-color-4')) {
                    $('#use_promo_with_paypal_text').removeClass('text-color-4');
                }
                if(!$('#use_promo_with_paypal_text').hasClass('text-color-1')) {
                    $('#use_promo_with_paypal_text').addClass('text-color-1');
                }
            } else {
                $('#is_from_promotional').val(0);
                if($('#use_promo_with_paypal_div').hasClass('alert-primary')) {
                    $('#use_promo_with_paypal_div').removeClass('alert-primary');
                }
                if(!$('#use_promo_with_paypal_div').hasClass('alert-secondary')) {
                    $('#use_promo_with_paypal_div').addClass('alert-secondary');
                }
                if($('#use_promo_with_paypal_text').hasClass('text-color-1')) {
                    $('#use_promo_with_paypal_text').removeClass('text-color-1');
                }
                if(!$('#use_promo_with_paypal_text').hasClass('text-color-4')) {
                    $('#use_promo_with_paypal_text').addClass('text-color-4');
                }
            }
        });

        $('#use_promo_with_skrill').unbind('change').bind('change', function () {
            var walletandpromo = $(this).data('walletandpromo');
            if(this.checked) {
                if(walletandpromo >= find_total_amt && $('#use_wallet_with_skrill').is(':checked')) {
                    $('#use_promo_with_skrill').prop('checked', false);
                    toastr.error("You have already enough balance to place the order.", "Error");
                    return false;
                }
                $('#allow_from_promotional').val(1);
                if($('#use_promo_with_skrill_div').hasClass('alert-secondary')) {
                    $('#use_promo_with_skrill_div').removeClass('alert-secondary');
                }
                if(!$('#use_promo_with_skrill_div').hasClass('alert-primary')) {
                    $('#use_promo_with_skrill_div').addClass('alert-primary');
                }
                if($('#use_promo_with_skrill_text').hasClass('text-color-4')) {
                    $('#use_promo_with_skrill_text').removeClass('text-color-4');
                }
                if(!$('#use_promo_with_skrill_text').hasClass('text-color-1')) {
                    $('#use_promo_with_skrill_text').addClass('text-color-1');
                }
            } else {
                $('#allow_from_promotional').val(0);
                if($('#use_promo_with_skrill_div').hasClass('alert-primary')) {
                    $('#use_promo_with_skrill_div').removeClass('alert-primary');
                }
                if(!$('#use_promo_with_skrill_div').hasClass('alert-secondary')) {
                    $('#use_promo_with_skrill_div').addClass('alert-secondary');
                }
                if($('#use_promo_with_skrill_text').hasClass('text-color-1')) {
                    $('#use_promo_with_skrill_text').removeClass('text-color-1');
                }
                if(!$('#use_promo_with_skrill_text').hasClass('text-color-4')) {
                    $('#use_promo_with_skrill_text').addClass('text-color-4');
                }
            }
        });

    });
</script>
@endsection