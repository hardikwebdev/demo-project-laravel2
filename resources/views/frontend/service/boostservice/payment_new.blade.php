@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Sponsor Service Transactions')
@section('content')

<section class="transactions-header filter-header cart-header cart-header-new">
    <div class="container">
        <div class="profile-detail">
            <div class="row cus-filter align-items-center">
                <h2 class="heading banner-cart d-flex align-items-center">
                    <img src="{{url('public/frontend/assets/img/circle-icon-edit.png')}}" class="img-fluid banner-round cus-box-shadow "> Payment Summary
                </h2>
            </div>
        </div>
    </div>
</section>

<section class="pricing-section transactions-table">
    <div class="container">

        @include('layouts.frontend.messages')

        <div class="row" id="main_div_id">
            {{-- services customization section (left side) - start --}}
            <div class="col-md-8 item-block1 cart-item-block cart-item-block-customize">
                {{-- customize single service section with service details - start --}}
                <div class="row">
                    <div class="navbar navbar-dark bg-info rounded_div text-center text-white brd-rad-10 bg-bar-blue">
                        <span class="text-uppercase p-2">customize your sponsor service</span>
                    </div>
                </div>
                <div class="row mt-4 " id="cart_details_div{{$row->id}}">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-12 col-md-8">
                                <div class="text-title">
                                    <a href="{{route('services_details',[$serviceData->user->username,$serviceData->seo_url])}}"> {{$serviceData->title}}</a>
                                </div>
                                <div class="re-order-promo text-center1 fs-16 fw-600">
                                    {!! nl2br($selectedPlan->description) !!}
                                </div>
                            </div>
                            <div class="col-12 col-md-4 align-self-end">
                                <div class="text-plan-price text-right">
                                    $<span id="id_price">{{$subtotal}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4 align-items-center">
                            <div class="col-12 col-md-8">
                                <div class="row">
                                    <div class="col-12 mr-4 d-flex align-items-center">
                                        <div class="delivery-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="text-cu-dgrey fw-600">
                                            {{$total_days}} Sponsor days 
                                            <br>
                                            <span class="text-danger">
                                                <b>Your service will be advertised on the following dates</b>
                                                @if($selectedPlan->id == 4 || $selectedPlan->id == 5)
                                                ({!! array_to_date_list($dates_array) !!})
                                                @else
                                                ({!! date_range_to_list($yourStartDate,$yourEndDate) !!})
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            {{-- begin : apply promo --}}
                            <div class="col-12">
                                <div class="text-center">
                                    <div class="form-group affiliate-form gradient w-90">
                                        <button type='button' class="btn btn-info discountCoupon btn-green-gradient" style="width: 30%;">Apply</button>

                                        <input type='text' id='coupan_code_new' class="form-control couponCodeNew from-he-auto" placeholder='Promo Code'>

                                        <div class="couponMessage mt-2 text-left"></div>
                                    </div>
                                </div>
                            </div>
                            {{-- end : apply promo --}}
                        </div>
                    </div>
                </div>
                {{-- customize single service section with service details - end --}}
            </div>
            {{-- services customization section (left side) - end --}}
            {{-- cart summary section (right side) - start --}}
            <div class="col-md-4">
                <div class="sticky-block" id="sticky_block">
                    <div class="cart-box">

                        {{-- Summany Block --}}
                        <div class="bg_gray less_rounded_div p-4 summary_div bg-f5f5  text-cu-lgrey" id="summary_div">
                            <p class="text-right mb-4 fw-600 text-cu-dgrey">Summary</p>
                            <hr class="hr mb-4">
                            <div class="d-inlne-block"><span class="w-75"><b>{{Str::limit($serviceData->title,25)}}</b></span> <span class="float-right fw-600 text-cu-dgrey"> ${{$subtotal}} </span></div>

                            <div class=" d-flex align-items-center">
                                <div class="delivery-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="ml-2">
                                    <span class="float-right fw-600 text-cu-dgrey">{{$total_days}} Sponsor days</span>
                                </div>
                            </div>
                            <hr>
                            <div style="display: none;" id="summary_coupon">
                                <span id="summary_coupon_code">Discount</span>
                                <span class="float-right fw-600 text-cu-dgrey">
                                    $<span id="summary_coupon_amount"></span>
                                </span>
                            </div>

                            <div>
                                <h6 class="fw-600 text-cu-dgrey">Total<span class="float-right font_20 fw-600 text-cu-dgrey">$<span id="summary_total">{{$final_total}}</span></span></h6>
                            </div>
                            <button class="btn btn-success btn-lighgreen w-100 checkout_btn mt-4 check-pd-10 fw-600">CHECKOUT</button>
                        </div>

                        {{-- Checkout Block --}}
                        <div class="bg_gray less_rounded_div p-4 checkout_div bg-f5f5  text-cu-lgrey" style="display: none">
                            <p class="text-right  mb-4 fw-600 text-cu-dgrey">Payment Options</p>
                            <hr>
                            <div>
                                <div class="form-check d-flex">
                                    <label class="check-custom @if($fromWalletAmount ==0 || $fromPromotionalAmount == $final_total) disable-class @endif">
                                        <input class="form-check-input" type="checkbox" id="wallet_check" value="wallet" @if ($fromWalletAmount > 0 && $fromPromotionalAmount < $final_total) checked @endif data-wallet="{{$fromWalletAmount}}" data-bill="{{$final_total}}" data-promotional="{{$fromPromotionalAmount}}" @if($fromWalletAmount ==0 || $fromPromotionalAmount == $final_total) disabled @endif>
                                        <span class="checkmark-custom"></span>
                                    </label>

                                    <label class="form-check-label w-100" for="wallet_check">
                                        <i class="fas fa-wallet success cus-text-green mr-2"></i> Use From Wallet
                                        <span class="float-right cus-text-green fw-600">$<span id="from_wallet_amt">{{$fromWalletAmount}}</span></span>
                                    </label>
                                </div>
                            </div>
                            <hr>
                            @if($fromPromotionalAmount > 0)
                            <div>
                                <div class="form-check d-flex">
                                    <label class="check-custom @if($fromPromotionalAmount ==0) disable-class @endif">
                                        <input class="form-check-input" type="checkbox" id="promotional_check" value="promotional" @if(($fromWalletAmount == 0 && $fromPromotionalAmount > 0) || $fromPromotionalAmount == $final_total) checked @endif data-wallet="{{$fromWalletAmount}}" data-bill="{{$final_total}}" data-promotional="{{$fromPromotionalAmount}}" @if($fromPromotionalAmount ==0) disabled @endif>
                                        <span class="checkmark-custom"></span>
                                    </label>

                                    <label class="form-check-label w-100" for="promotional_check">
                                        <i class="fas fa-wallet success cus-text-green mr-2"></i>  Use From demo Bucks
                                        <span class="float-right cus-text-green fw-600">$<span id="from_promo_amt">{{$fromPromotionalAmount}}</span></span>
                                    </label>
                                </div>
                            </div>
                            <hr>
                            @endif
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
                            @if($is_recurring_service == 0 && $res['status'] == "success" && $isvalid_bluesnap_max_amount == true)
                            <div>
                                <div class="form-check  d-flex">
                                    <label class="check-box-main">
                                        <input class="form-check-input" type="checkbox" id="creditcard_check" value="creditcard" @if($fromWalletAmount==0) checked @endif>
                                        <span class="checkmark"></span>
                                    </label>
                                    <label class="form-check-label" for="creditcard_check">
                                        <i class="fas fa-credit-card primary mr-2 text-color-blue"></i> Credit Card
                                    </label>
                                </div>
                            </div>
                            <hr>
                            @endif

                            @if($is_recurring_service == 0)
                            <form id="payFromWalletForm" action="{{route('paypal_express_checkout_boost')}}" method="post" class="custom @if(($fromWalletAmount > 0 && $fromWalletAmount < $final_total) && ($fromPromotionalAmount > 0 && $fromPromotionalAmount < $final_total)) disable-class @endif">
                                {{csrf_field()}}
                                <input type="hidden" name="is_from_wallet" class="allow_wallet" value="0">
                                <input type="hidden" name="is_from_promotional" class="allow_promotional" value="0">
                                <input type="hidden" name="coupon_id" class="coupon_id_for_pay">
                                <button class="btn btn-success btn-lighgreen w-100 continue_checkout_btn check-pd-10 fw-600" type="submit" onclick="this.disabled=true;this.form.submit();">CONTINUE CHECKOUT</button>
                            </form>

                            @php
                            $is_complete_billing = false;
                            if(Auth::user()->billinginfo && Auth::user()->billinginfo->address1 != ''){
                                $is_complete_billing = true;
                            }
                            @endphp
                            @if($is_recurring_service == 0 && $res['status'] == "success" && $isvalid_bluesnap_max_amount == true)
                            <form class="panel-body" id="payCreditCardForm" action="{{ route('bluesnapBootServicePayment') }}" method="POST" style="display: none;">
                                {{csrf_field()}}
                                <input type="hidden" name="from_wallet" value="0">
                                <input type="hidden" name="coupon_id" class="coupon_id_for_pay">
                                <button type="submit" onclick="this.disabled=true;this.form.submit();" class="btn btn-success btn-lighgreen w-100 continue_checkout_btn check-pd-10 fw-600">CONTINUE CHECKOUT</button>
                            </form>
                            @endif

                            <div class="extra_continue_checkout_btn" style="display: none;">
                                <button type="button" class="btn btn-success btn-lighgreen w-100 check-pd-10 fw-600">CONTINUE CHECKOUT</button>
                            </div>
                            <div class="text-center or_div mt-2 mb-2">or</div>
                            @endif

                            <form action="{{route('paypal_express_checkout_boost')}}" method="post" id="paypalForm" class="custom  @if($fromWalletAmount > 0 && $fromWalletAmount == $final_total) disable-class @endif">
                                {{csrf_field()}}
                                <input type="hidden" name="is_from_wallet" class="allow_wallet" value="0">
                                <input type="hidden" name="is_from_promotional" class="allow_promotional" value="0">
                                <input type="hidden" name="coupon_id" class="coupon_id_for_pay">
                                <button class="btn paypal_btn w-100 check-pd-10">
                                    <img src="{{url('public/frontend/assets/img/paypal.png')}}" class="img-fluid">
                                </button>
                            </form>
                        </div>

                        {{-- Checkout Free Block --}}
                        <div class="bg_gray less_rounded_div p-4 checkout_free_div bg-f5f5  text-cu-lgrey" style="display: none">
                            <p class="text-right  mb-4 fw-600 text-cu-dgrey">Payment Options</p>
                            <hr>
                            <div>
                                <div class="form-check d-flex">
                                    <label class="check-box-main">
                                        <input class="form-check-input" type="checkbox" id="wallet_check1" value="wallet" checked data-wallet="0" data-bill="0">
                                        <span class="checkmark"></span>
                                    </label>

                                    <label class="form-check-label w-100" for="wallet_check">
                                        <i class="fas fa-wallet success cus-text-green mr-2"></i> Use From Wallet
                                        <span class="float-right cus-text-green fw-600">$0</span>
                                    </label>
                                </div>
                            </div>
                            <hr>

                            <form id="payFromWalletForm" action="{{route('paypal_express_checkout_boost')}}" method="post" class="custom">
                                {{csrf_field()}}
                                <input type="hidden" name="is_from_wallet" class="allow_wallet" value="0">
                                <input type="hidden" name="is_from_promotional" class="allow_promotional" value="0">
                                <input type="hidden" name="coupon_id" class="coupon_id_for_pay">
                                <button class="btn btn-success btn-lighgreen w-100 continue_checkout_btn check-pd-10 fw-600" type="submit" onclick="this.disabled=true;this.form.submit();">CONTINUE CHECKOUT</button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
            {{-- cart summary section (right side) - end --}}
        </div>
    </div>
</section>
@endsection


@section('css')
<style>
    .rounded_div {
        border-radius: 25px;
        width: 100%;
    }

    .price {
        font-size: 20px;
    }

    .bg_gray {
        background-color: lightgray;
    }

    .less_rounded_div {
        border-radius: 10px;
    }

    .hr {
        margin-top: 0.25rem;
        margin-bottom: 0.25rem;
    }

    .font_20 {
        font-size: 20px;
    }

    .no_link {
        width: 100%;
        color: black;
    }

    .top_none {
        top: 0px !important;
    }

    .paypal_btn {
        background-color: #cece0f;
        color: blue;
    }
</style>
@endsection

@section('scripts')
<script>
    $('document').ready(function(){
        checkout_btn_click();
    });
    var from_wallet_amt = '{{$fromWalletAmount}}';
    var from_promo_amt = '{{$fromPromotionalAmount}}';
    var find_total_amt = '{{$final_total}}';
    var is_free = false;
    $('.discountCoupon').on('click', function () {
        is_free = false;
        $('.summary_div').show();
        $('.checkout_div').hide();
        $('.checkout_free_div').hide();

        var coupon_code = $('#coupan_code_new').val();
        if(coupon_code.length == 0) {
            $('.couponMessage').addClass('error');
            $('.couponMessage').text("Please enter coupon code");
            return false;
        }
        $.ajax({
            type: "POST",
            url: "{{  route('apply_coupon_on_sponsor_service') }}",
            data: {
                "_token": _token,
                "coupon_code": coupon_code,
            },
            success: function (data) {
                if (data.status == 'success') {
                    $('.couponMessage').removeClass('error');
                    $('.couponMessage').addClass('success');
                    $('.couponMessage').text(data.message);
                    $('#summary_coupon').css('display','block');
                    //$('#summary_coupon_code').text(data.coupon_code);
                    $('#summary_coupon_amount').text(data.discount);
                    $('#summary_total').text(data.price);
                    $('.coupon_id_for_pay').val(data.coupon_id);
                    $('#from_wallet_amt').text(data.fromWalletAmount);
                    $('#from_promo_amt').text(data.fromPromotionalAmount);
                    if(data.price == 0){
                        is_free = true;
                    }
                } else {
                    $('.couponMessage').removeClass('success');
                    $('.couponMessage').addClass('error');
                    $('.couponMessage').text(data.message);
                    $('#summary_coupon').css('display','none');
                    $('#from_wallet_amt').text(from_wallet_amt);
                    $('#from_promo_amt').text(from_promo_amt);
                    $('#summary_total').text(find_total_amt);
                    $('.coupon_id_for_pay').val('');
                }
            }
        });
    });

    function load_div(id, callback = false) {
        $(id).load(window.location.href + ' ' + id + ' > div', function () {
            if (callback != false) {
                callback();
            }
        });
    }

    function update_summary() {
        load_div('#sticky_block', function () {
            checkout_btn_click();
        });
    };

    function checkout_btn_click() {
        $('.checkout_btn').unbind('click').bind('click', function() {
            $('.checkout_div').show();
            $('.summary_div').hide();
            if($("#wallet_check").prop("checked") == true){
                $('.allow_wallet').val(1);
                $('#payFromWalletForm').css('display','block');
                $('#payCreditCardForm').css('display','none');
            }
            if($("#promotional_check").prop("checked") == true){
                $('.allow_promotional').val(1);
                $('#payFromWalletForm').css('display','block');
                $('#payCreditCardForm').css('display','none');
            }
            if($("#creditcard_check").prop("checked") == true){
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','block');
            }
            if($("#wallet_check").prop("checked") != true && $("#promotional_check").prop("checked") != true && $("#creditcard_check").prop("checked") != true) {
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','none');
                $('.extra_continue_checkout_btn').show();
            }
            checkboxes_click();
        });
    }

    function checkboxes_click() {
        $("#wallet_check").change(function() {
            $('.extra_continue_checkout_btn').hide();
            $('.allow_wallet').val(0);
            $('#payFromWalletForm').removeClass('disable-class');
            $('#paypalForm').removeClass('disable-class');
            var pro_amount = $(this).data('promotional');
            var wallet_amount = $(this).data('wallet');
            var bill_amount = $(this).data('bill');
            if(this.checked) {
                /* if(pro_amount == bill_amount && bill_amount == wallet_amount) {
                    $("#promotional_check").prop("checked", false);
                } */
                if($("#promotional_check").prop("checked") == true) {
                    wallet_amount = wallet_amount + pro_amount;
                }
                $("#creditcard_check").prop("checked", false);
                $('.allow_wallet').val(1);
                $('#payCreditCardForm').css('display','none');
                $('#payFromWalletForm').css('display','block');
                if(wallet_amount == bill_amount) {
                    $('#paypalForm').addClass('disable-class');
                } else if(wallet_amount == 0) {
                    $('#payFromWalletForm').addClass('disable-class');
                } else if(wallet_amount < bill_amount) {
                    $('#payFromWalletForm').addClass('disable-class');
                } else {
                    $('#paypalForm').css('display','block');
                }
            } else {
                $('#paypalForm').css('display','block');
                if($("#promotional_check").prop("checked") == true) {
                    $("#creditcard_check").prop("checked", false);
                    $('#payCreditCardForm').css('display','none');
                    $('#payFromWalletForm').css('display','block');
                    if(pro_amount == bill_amount) {
                        $('#paypalForm').addClass('disable-class');
                    } else if(pro_amount == 0) {
                        $('#payFromWalletForm').addClass('disable-class');
                    } else if(pro_amount < bill_amount) {
                        $('#payFromWalletForm').addClass('disable-class');
                    } else {
                        $('#paypalForm').css('display','block');
                    }
                } else 
                if($("#creditcard_check").prop("checked") == true){
                    $('#payFromWalletForm').css('display','none');
                    $('#payCreditCardForm').css('display','block');
                } else {
                    $('#payFromWalletForm').css('display','none');
                    $('#payCreditCardForm').css('display','none');
                    $('.extra_continue_checkout_btn').show();
                }
            }
        });

        $("#promotional_check").change(function() {
            $('.extra_continue_checkout_btn').hide();
            $('.allow_promotional').val(0);
            $('#payFromWalletForm').removeClass('disable-class');
            $('#paypalForm').removeClass('disable-class');
            var wallet_amount = $(this).data('wallet');
            var pro_amount = $(this).data('promotional');
            var bill_amount = $(this).data('bill');
            if(this.checked) {
                if(pro_amount == bill_amount) {
                    $("#wallet_check").prop("checked", false);
                    $("#wallet_check").parent().addClass('disable-class');
                    $("#wallet_check").attr('disabled',true);
                }
                if($("#wallet_check").prop("checked") == true) {
                    pro_amount = pro_amount + wallet_amount;
                }
                $("#creditcard_check").prop("checked", false);
                $('.allow_promotional').val(1);
                $('#payCreditCardForm').css('display','none');
                $('#payFromWalletForm').css('display','block');
                if(pro_amount == bill_amount) {
                    $('#paypalForm').addClass('disable-class');
                } else if(pro_amount == 0) {
                    $('#payFromWalletForm').addClass('disable-class');
                } else if(pro_amount < bill_amount) {
                    $('#payFromWalletForm').addClass('disable-class');
                } else {
                    $('#paypalForm').css('display','block');
                }
            } else {
                $('#paypalForm').css('display','block');
                if($("#wallet_check").prop("checked") == true) {
                    $("#creditcard_check").prop("checked", false);
                    $('#payCreditCardForm').css('display','none');
                    $('#payFromWalletForm').css('display','block');
                    if(wallet_amount == bill_amount) {
                        $('#paypalForm').addClass('disable-class');
                    } else if(wallet_amount == 0) {
                        $('#payFromWalletForm').addClass('disable-class');
                    } else if(wallet_amount < bill_amount) {
                        $('#payFromWalletForm').addClass('disable-class');
                    } else {
                        $('#paypalForm').css('display','block');
                    }
                } else if($("#creditcard_check").prop("checked") == true){
                    $('#payFromWalletForm').css('display','none');
                    $('#payCreditCardForm').css('display','block');
                } else {
                    $('#payFromWalletForm').css('display','none');
                    $('#payCreditCardForm').css('display','none');
                    $('.extra_continue_checkout_btn').show();
                }
            }
        });

        $("#creditcard_check").change(function() {
            $('.extra_continue_checkout_btn').hide();
            $('#payFromWalletForm').removeClass('disable-class');
            $('#paypalForm').removeClass('disable-class');
            $('#paypalForm').css('display','block');
            if(this.checked) {
                console.log('credit checked-------');
                $("#wallet_check").prop("checked", false);
                $("#promotional_check").prop("checked", false);
                $('#payFromWalletForm').css('display','none');
                $('#payCreditCardForm').css('display','block');
            } else {
                if($("#wallet_check").prop("checked") == true){
                    $('#payFromWalletForm').css('display','block');
                    $('#payCreditCardForm').css('display','none');
                } else {
                    $('#payFromWalletForm').css('display','none');
                    $('#payCreditCardForm').css('display','none');
                    $('.extra_continue_checkout_btn').show();
                }
            }
        });
    }

    $('.extra_continue_checkout_btn').click(function(){
        toastr.error("Please choose atleast one payment method for proceed further.", "Error");
    });
</script>
@endsection