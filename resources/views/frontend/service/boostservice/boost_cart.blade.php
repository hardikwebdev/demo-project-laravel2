@extends('layouts.frontend.main')
@section('pageTitle','demo - Sponsor Service Transactions')
@section('content')
<!-- Display Error Message -->
@include('layouts.frontend.messages')


<!-- Masthead -->
<header class="masthead text-white"> {{-- masthead  --}}
	<div class="overlay"></div>
    <div class="bg-dark w-100">
	<div class="container py-4 d-flex justify-content-center" id="cart_header_div">
        <div class="d-flex align-items-center flex-column flex-md-row justify-content-md-around parent-cart-step">
            <p class="text-color-1 mb-0 font-16"><span class="font-13 cart-step-1 font-weight-bold mx-2">1</span>Order Details </p>
            <i class="fas fa-chevron-right arrow-right"></i>
            <p class="text-color-7 mb-0 font-16"><span class="font-13 cart-step-2 text-color-7 font-weight-bold mx-2">2</span>Payment Options </p>
            <i class="fas fa-chevron-right arrow-right"></i>
            <p class="text-color-7 mb-0 font-16"><span class="font-13 cart-step-2 text-color-7 font-weight-bold mx-2">3</span>Submit Requirements </p>
        </div>
	</div>
    </div>
</header>

<section class="pricing-section transactions-table pb-5">
    <div id="main_div_id">
        <div class="container mt-4 mt-md-0 font-lato">
            <div class="alert-status mx-auto text-center py-2">
                <p class="mb-0 font-14 text-color-1 font-weight-bold"> <i class="fas fa-info-circle font-14 mx-2"></i>  You have 1 Items in Your Cart. Please ensure to customize it.</p>
            </div>
        </div>

        <div class="container cart_page font-lato">   
            <div class="row mt-5">
                <div class="col-md-8 pr-xl-0">
                    <div class="row" id="accordion">
                        <div class="col-12 pb-4">
                            <h1 class="font-30 text-color-2 font-weight-bold">Customize Your Sponsor Service</h1>
                        </div>
                    </div>   
                    <div class="row cart-item-block">
                        <div class="col-12 col-md-12">
                            <div class="row">
                                <div class="col-12 col-xl-7 mt-2 mt-md-0">
                                    <p class="mb-0 font-16 font-weight-bold text-color-2">
                                        <a href="{{route('services_details',[$serviceData->user->username,$serviceData->seo_url])}}"> {{$serviceData->title}}</a>
                                    </p>
                                    <div class="py-1">
                                        <span class="font-14 text-color-4 pr-3 border-right border-gray"><i class="far fa-user"></i><span class="mx-2">{{$serviceData->user->username}}</span></span> 
                                        <span class="font-14 text-color-4 ml-3"><i class="far fa-clock"></i><span class="mx-2"> {{$total_days}} sponsor days </span></span>
                                    </div>  
                                    {{-- <div>
                                        <button class="btn font-14 text-color-1 bg-transparent font-weight-bold arrow-down-btn shadow-none pl-0" data-toggle="collapse" data-target="#collapseOne{{$row->id}}" aria-expanded="false" aria-controls="collapseOne{{$row->id}}">
                                            What’s included
                                            <i class="fas fa-chevron-down arrow-down"></i>
                                        </button>
                                    </div> --}}
                                    <div>
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
                                <div class="col-12 col-xl-5 d-flex align-items-center mt-2 mt-xl-0">
                                    <div class='d-flex align-items-center'>
                                        <p class="mb-0 text-color-2 font-weight-bold font-18 ml-5">${{$subtotal}}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- <div id="collapseOne{{$row->id}}" class="collapse w-100" aria-labelledby="headingOne" data-parent="#accordion">
                        <div class="col-12 mt-4">
                            <h1 class="font-18 text-color-2 font-weight-bold">{{$row->plan->package_name}}</h1>
                            <div class="mt-3">
                                @if($row->plan->no_of_revisions != null)
                                <p class='mb-0 text-color-3 font-14'><i class="fas fa-check text-color-1 mr-2"></i> {{$row->plan->no_of_revisions}}x revisions</p>
                                @endif
                                <p class='mb-0 text-color-3 font-14 ml-4'>{{$row->plan->offering_details}}</p>
                            </div>
                        </div>
                    </div> --}}
                </div>
                            
                <div class="col-md-4" id="sticky_block">
                    <div class="row mt-4 mt-md-0">
                        <div class="col-12 px-md-0 px-lg-4 px-xl-5">
                            <div class="p-3 summary">
                                <p class="text-left text-color-2 py-2 font-18 font-weight-bold">Summary</p>
                                <div class="d-flex justify-content-between py-2">
                                    <span class="w-75 font-12 text-color-3 text-truncate">{{Str::limit($serviceData->title,25)}}</span> 
                                    <span class="float-right font-14 text-color-3">${{$subtotal}}</span>
                                </div>
                                <hr>
                                {{-- apply coupon - start --}}
                                <div class="pb-3 apply_promo_input_section">
                                    <div class="pb-2">
                                        <span class="font-14 font-weight-bold text-color-2">Promo Code</span>
                                    </div>
                                    <div class="input-group summary align-items-center">
                                        <input type="text" class="border-0 form-control font-12 text-color-2" placeholder="Promo Code" id='coupan_code_new'>
                                        <div class="input-group-append ml-auto mr-3">
                                            <a href="javascript:void(0)" class="font-12 text-color-1 font-weight-bold discountCoupon">APPLY</a>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="font-12 text-color-10 couponMessage"></p>
                                    </div>
                                </div>
                                {{-- apply coupon - end --}}

                                {{-- appllied all coupon - start --}}
                                <div class="mt-2 alert-success border-0 w-100 p-2 d-flex justify-content-between align-items-center applied_coupon_section" style="display: none !important;">
                                    <div class="d-flex align-items-center ml-2">
                                        <img src="{{url('public/frontend/images/Tik-mark.png')}}" class="w-24" alt=""> 
                                        <p class="mb-0 text-color-8 font-12 font-weight-bold pl-2">(<span id="applied_coupon_name" class="text-color-8 font-12 font-weight-bold"></span>) Promo Added</p>
                                    </div>
                                    <div class="mr-2">
                                        <a href="javascript:void(0)" class="remove_coupon_btn text-danger">
                                            <i class="far fa-trash-alt text-color-8"></i>
                                        </a>
                                    </div>
                                </div>
                                {{-- appllied all coupon - end --}}
                                <hr>
                                <div class="justify-content-between pt-2" style="display: none;" id="summary_coupon">
                                    <p class="mb-0 font-12 text-color-3">Discount</p>
                                    <p class="mb-0 font-14 text-color-3">$<span id="summary_coupon_amount"></span></p>
                                </div>
                                <div class="d-flex justify-content-between pt-1">
                                    <p class="mb-0 font-16 text-color-3">Total</p>
                                    <p class="mb-0 font-18 text-color-3 font-weight-bold">$<span id="summary_total">{{$final_total}}</span></p>
                                </div>         
                                <hr>
                                <form action="{{route('boost_cart_payment_options')}}" method="POST">
                                {{csrf_field()}}
                                <input type="hidden" name="coupon_id" id="coupon_id_for_pay">
                                <input type="hidden" name="service_id" value="{{$serviceData->id}}">
                                <input type="hidden" name="sub_total" value="{{$subtotal}}">
                                <input type="hidden" name="final_total" value="{{$final_total}}" id="final_total_to_pay">
                                <input type="hidden" name="total_days" value="{{$total_days}}">
                                <button type="submit" class="btn text-white bg-primary-blue border-radius-6px w-100 py-2 mt-2">Continue to Checkout</button> 
                                </form>
                            </div>
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
    var is_free = false;
    $('.discountCoupon').on('click', function () {
        is_free = false;
        var coupon_code = $('#coupan_code_new').val();
        if(coupon_code.length == 0) {
            $('.couponMessage').addClass('text-color-10');
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
                    $('.couponMessage').removeClass('text-color-10');
                    $('.couponMessage').addClass('success');
                    $('.couponMessage').text(data.message);
                    $('#summary_coupon').css('display','flex');
                    $('#summary_coupon_amount').text(data.discount);
                    $('#summary_total').text(data.price);
                    $('#final_total_to_pay').val(data.price);
                    $('#coupon_id_for_pay').val(data.coupon_id);
                    $('#from_wallet_amt').text(data.fromWalletAmount);
                    $('#from_promo_amt').text(data.fromPromotionalAmount);
                    $('#applied_coupon_name').text(data.coupon_code);
                    if($('#applied_coupon_name').hasClass('global-dark-text')) {
                        $('#applied_coupon_name').removeClass('global-dark-text');
                    }
                    if(data.price == 0){
                        is_free = true;
                    }
                    $('.apply_promo_input_section').hide();
                    $('.applied_coupon_section').attr('style', 'display: flex !important');
                } else {
                    $('.couponMessage').removeClass('success');
                    $('.couponMessage').addClass('text-color-10');
                    $('.couponMessage').text(data.message);
                    $('#summary_coupon').css('display','none');
                    $('#from_wallet_amt').text(from_wallet_amt);
                    $('#from_promo_amt').text(from_promo_amt);
                    $('#summary_total').text(find_total_amt);
                    $('#final_total_to_pay').val(find_total_amt);
                    $('#coupon_id_for_pay').val('');
                }
            }
        });
    });

    $('.remove_coupon_btn').unbind('click').bind('click', function () {
		$('.couponMessage').removeClass('success');
        if(!$('.couponMessage').hasClass('text-color-10')) {
            $('.couponMessage').addClass('text-color-10');
        }
        $('.couponMessage').text('');
        $('#summary_coupon').css('display','none');
        $('#from_wallet_amt').text(from_wallet_amt);
        $('#from_promo_amt').text(from_promo_amt);
        $('#summary_total').text(find_total_amt);
        $('#final_total_to_pay').val(find_total_amt);
        $('#coupon_id_for_pay').val('');
        $('#coupan_code_new').val('');
        $('.apply_promo_input_section').show();
        $('.applied_coupon_section').attr('style', 'display: none !important');
    });
</script>
@endsection