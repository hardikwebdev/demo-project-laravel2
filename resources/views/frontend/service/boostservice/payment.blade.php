@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Services')
@section('content')
<section class="transactions-header filter-header">
    <div class="container">
        <div class="profile-detail">
            <div class="row cus-filter align-items-center">
                <h2 class="heading">Payment</h2>
            </div>    
        </div>    
    </div>    
</section>
<section class="cart-block">
    <div class="container">
        <div class="row"> 
            <div class="col-lg-12"> 
                <h3 class="cart-title">Summary</h3>
            </div>
        </div>
        <div class="row"> 
            <div class="col-lg-8 col-md-12 col-12"> 
                <div class="table-responsive">
                    <table class="manage-sale-tabel payment-table payment-table-mb">
                        <thead class="thead-default">
                            <tr class="manage-sale-head">
                                <td colspan="2">Service Name</td>
                                <td>{{$selectedPlan->name}}</td>
                                <td colspan="2">Date</td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="2">
                                    <a href="{{route('services_details',[$serviceData->user->username,$serviceData->seo_url])}}"> {{$serviceData->title}}</a>
                                </td>
                                <td>{!! nl2br($selectedPlan->description) !!}</td>

                                @if($selectedPlan->id == 4 || $selectedPlan->id == 5)
                                <td colspan="2">{!! array_to_date_list($dates_array,'<br>') !!}</td>
                                @else
                                <td colspan="2">{!! date_range_to_list($yourStartDate,$yourEndDate,'<br>') !!}</td>
                                @endif
                            </tr>

                            @if($selectedPlan->id == 4)
                                @if($category_slot == 1)
                                <tr>
                                    <td colspan="2">Selected Plan</td>
                                    <td>$ {{$selectedPlan->price}}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="2">Selected Slot</td>
                                    <td>First slot</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                <td colspan="2">Sponsor days</td>
                                    <td>{{$total_days}}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>Total</td>
                                    <td>$ {{$total_days * $selectedPlan->price}}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                @else
                                <tr>
                                    <td colspan="2">Selected Plan</td>
                                    <td>$ {{$selectedPlan->sub_price}}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="2">Selected Slot</td>
                                    <td>Second or third slot</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td colspan="2">Sponsor days</td>
                                    <td>{{$total_days}}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>Total</td>
                                    <td>$ {{$total_days * $selectedPlan->sub_price}}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                @endif
                            @else
                            <tr>
                                <td colspan="2">Selected Plan</td>
                                <td>$ {{$selectedPlan->price}}</td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="2">Sponsor days</td>
                                <td>{{$total_days}}</td>
                                <td></td>
                                <td></td>
                            </tr>

                            <tr>
                                <td></td>
                                <td>Total</td>
                                <td>$ {{$total_days * $selectedPlan->price}}</td>
                                <td></td>
                                <td></td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>


            </div>
            {{--  --}}
            <div class="col-lg-4 col-md-12 col-12 payment-box">
                <div class="sticky-block">
                    <div class="cart-box">
                        <div class="cart-box-list">
                            <p class="p-bold">Payment options</p>
                            <div class="payment-option">
                                <label class="payment-radio inline-checkbox">Paypal
                                    <input type="radio" form="checkout-form" id="paypal" name="payment_method" value="pp" checked>
                                    <span class="radiomark"></span>
                                </label>

                                <label class="payment-radio inline-checkbox">Credit Card
                                    <input type="radio" id="credit_card" name="payment_method" name="radio" value="cc">
                                    <span class="radiomark"></span>
                                </label>

                                <label class="payment-radio inline-checkbox">Wallet
                                    <input type="radio" id="wallet" name="payment_method" name="radio" value="wp">
                                    <span class="radiomark"></span>
                                </label>

                            </div>
                            <hr>
                        </div>

                        {{-- begin : Paypal payment --}}
                        <div class="secure-checkout paypal">
                        @if(count($selectedPlan))
                        <form action="{{route('paypal_express_checkout_boost')}}" method="post" id="paypalBtn">
                            {{csrf_field()}}
                            <input type="hidden" name="selected_pack" value="{{$selectedPlan->id}}">
                            <input type="hidden" name="service_id" value="{{$service_id}}">
                            <input type="hidden" name="total_days" value="{{$total_days}}">
                            <input type="hidden" name="from_wallet" value="0">
                            <input type="hidden" name="category_slot" value="{{$category_slot}}">
                            <button type="submit" onclick="this.disabled=true;this.form.submit();" class="btn btn-success">Pay with Paypal</button>
                        </form>
                        @endif
                        </div>
                        {{-- end : Paypal payment --}}

                        {{-- begin : wallet payment --}}
                        <div class="secure-checkout wallet" style="display: none;">
                        @if(count($selectedPlan))
                        <form action="{{route('paypal_express_checkout_boost')}}" method="post" id="paypalBtn">
                            {{csrf_field()}}
                            <input type="hidden" name="selected_pack" value="{{$selectedPlan->id}}">
                            <input type="hidden" name="service_id" value="{{$service_id}}">
                            <input type="hidden" name="total_days" value="{{$total_days}}">
                            <input type="hidden" name="from_wallet" value="1">
                            <input type="hidden" name="category_slot" value="{{$category_slot}}">

                            @if( ($total_days * $selectedPlan->price) <= Auth::user()->earning)

                            <button type="submit" onclick="this.disabled=true;this.form.submit();" class="btn btn-success">Pay Now</button>
                            @else
                            <span class="text-danger">You have not sufficient amount in your wallet</span>
                            @endif
                        </form>
                        @endif
                        </div>
                        {{-- end : wallet payment --}}

                        {{-- begin : Bluesnap payment --}}
                        <div class="secure-checkout bluesnap" style="display: none;">
                        @if(count($selectedPlan))
                            <form class="panel-body" id="checkout-form_cc" action="{{ route('bluesnapBootServicePayment') }}" method="POST">
                                {{csrf_field()}}
                                <input type="hidden" name="selected_pack" value="{{$selectedPlan->id}}">
                                <input type="hidden" name="service_id" value="{{$service_id}}">
                                <input type="hidden" name="total_days" value="{{$total_days}}">
                                <input type="hidden" name="from_wallet" value="0">
                                <input type="hidden" name="category_slot" value="{{$category_slot}}">
                                <button type="submit" onclick="this.disabled=true;this.form.submit();" class="btn btn-success">Pay Now</button>
                            </form>
                        @endif    
                        </div>
                        {{-- end : Bluesnap payment --}}

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script src="{{front_asset('js/radio-link.js')}}">
</script>
<script src="{{ web_asset('plugins/jquery-validation/js/jquery.validate.min.js')}}" type="text/javascript"></script>
<script src="{{ web_asset('plugins/jquery-validation/js/additional-methods.min.js')}}" type="text/javascript"></script>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/3.1.60/inputmask/jquery.inputmask.js"></script>

<script type="text/javascript">

    $(document).ready(function() {
        $('#paypalBtn').show();
        
        $('input[name="payment_method"]').click(function(){
            if($(this).val() == 'cc'){
                $('.bluesnap').show();
                $('.paypal').hide();
                $('.wallet').hide();
            }else if($(this).val() == 'pp'){
                $('.paypal').show();
                $('.wallet').hide();
                $('.bluesnap').hide();
            }else if($(this).val() == 'wp'){
                $('.wallet').show();
                $('.paypal').hide();
                $('.bluesnap').hide();
            }
        });
        $(document).on('contextmenu', function(e) {
            return false;
        });
        $(document).keydown(function (event) {
            if (event.keyCode == 123) {
                return false;
            } else if (event.ctrlKey && event.shiftKey && event.keyCode == 73) { 
                return false;
            }else if (event.ctrlKey && event.shiftKey && event.keyCode == 74) {
                return false;
            }else if (event.ctrlKey && 
                (event.keyCode === 67 || 
                    event.keyCode === 86 || 
                    event.keyCode === 85 || 
                    event.keyCode === 117)) {
                return false;
            }else if (event.keyCode === 91 || 
                event.keyCode === 18 || 
                event.keyCode === 73 ) {
                return false;
            }
        });
    });
</script>
@endsection



