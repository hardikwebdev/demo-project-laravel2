@php
use App\CartCombo;
use App\BuyerReorderPromo;
use App\User;
use App\Cart;
use App\DiscountPriority;
$userObj = new User;
$cartObj = new Cart;

$Cart = CartCombo::with('extra.service_extra','service.images','plan','coupon')->where('uid',Auth::user()->id)->where('is_custom_order',$is_custom_order)->OrderBy('id','desc')->get();

$cartSubtotal = $selectedExtra  = $totalPrice = $totalQuantity = $totalPromoDiscount = $totalCoupenDiscount = $totalVolumeDiscount = $totalComboDiscount = 0; 

$discountPriority = DiscountPriority::OrderBy('priority','desc')->get();
$is_recurring_service = 0;
@endphp

@extends('layouts.frontend.main')
@section('pageTitle','demo - Services')
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
				@if(count($Cart))
				<div class="table-responsive">
					<table class="manage-sale-tabel payment-table payment-table-mb custom">
						<thead class="thead-default">
							<tr class="manage-sale-head">
								<td colspan="2">Service Name</td>
								<td>Price</td>
								<td>Amount</td>
							</tr>
						</thead>
						<tbody>
							@foreach($Cart as $row)
							@php
							$cartSubtotal += ($row->plan->price * $row->quantity);
							$totalPrice += $row->plan->price;
							$totalQuantity += $row->quantity;
							$afterDiscountPrice = $row->plan->price * $row->quantity;

							if($is_recurring_service == 0 && $row->service->is_recurring == 1){
								$is_recurring_service = 1;
							}

							$cart_extra_price_total = 0;
							foreach($row->extra as $extra) {
								$cart_extra_price_total += $extra->service_extra->price*$extra->qty;
							}

							$buyerPromo = BuyerReorderPromo::where('seller_id',$row->service->uid)
							->where('buyer_id',Auth::user()->id)
							->where('service_id',$row->service->id)
							->where('is_used',0)
							->first();

							/*Check any one discount is single*/
							$is_single = $cartObj->check_is_single_discount($discountPriority,$row,$buyerPromo,$Cart);
							foreach ($discountPriority as $priority) {
								if($afterDiscountPrice <= 5){
									continue;
								}

								if($is_single != ''){
									if($is_single != $priority->discount_type){
										continue;
									} 
								}

								if($priority->discount_type == 'reorder_promo'){
									if(count($buyerPromo) > 0){
										//$amount_to_used = $afterDiscountPrice + $cart_extra_price_total;
										$discountAmount = ($afterDiscountPrice * $buyerPromo->amount ) / 100;
										$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
										if($checkDiscountedPrice >= 5){
											$totalPromoDiscount += $discountAmount;
											$afterDiscountPrice -= $discountAmount;
										}
									}
								}elseif($priority->discount_type == 'coupan'){
									if($row->coupon){
										if($row->coupon->discount_type=="amount"){
											$discountAmount = $row->coupon->discount;
											$checkDiscountedPrice = ($afterDiscountPrice + $cart_extra_price_total) - $discountAmount;
											if($checkDiscountedPrice >= 5){
												$totalCoupenDiscount += $discountAmount;
												$afterDiscountPrice -= $discountAmount;
											}
										} else {
											$discountAmount = 1 * (($row->coupon->discount/100) * ($afterDiscountPrice + $cart_extra_price_total));
											$checkDiscountedPrice = ($afterDiscountPrice + $cart_extra_price_total) - $discountAmount;
											if($checkDiscountedPrice >= 5){
												$totalCoupenDiscount += $discountAmount;
												$afterDiscountPrice -= $discountAmount;
											}
										}
									}
								}elseif($priority->discount_type == 'volume_discount'){
									if($userObj->is_premium_seller($row->service->uid)){
										$v_discount_per = 0;
										foreach($row->service->volume_discount as $value1){
											if($row->quantity >= $value1->volume){
												$v_discount_per = $value1->discount;
											}
										}
										if($v_discount_per > 0){
											$discountAmount = ($afterDiscountPrice * $v_discount_per ) / 100;
											$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
											if($checkDiscountedPrice >= 5){
												$totalVolumeDiscount += $discountAmount;
												$afterDiscountPrice -= $discountAmount;
											}
										}
									}
								}elseif($priority->discount_type == 'combo_discount'){
									if($userObj->is_premium_seller($row->service->uid)){
										$combo_detail = $cartObj->check_is_combo($row->service_id,$Cart);
										if($combo_detail->combo_discount_per > 0){
											$discountAmount = ($afterDiscountPrice * $combo_detail->combo_discount_per ) / 100;
											$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
											if($checkDiscountedPrice >= 5){
												$totalComboDiscount += $discountAmount;
												$afterDiscountPrice -= $discountAmount;
											}
										}
									}
								}
							}
							@endphp
							<tr>
								@if($row->service->is_custom_order == 0)
								<td class="text-capitalize">
									{{$row->service->title}} 
									@if($is_recurring_service == 0)
									<span class="qty-color">x{{$row->quantity}}</span><br>
									@endif
								</td>
								<td class="width170 custom-grey-font"></td>
								<td class="custom-grey-font">${{$row->plan->price}}</td>
								<td class="">${{$row->plan->price*$row->quantity}}</td>
								@else
								<td></td>
								<td class="width170 custom-grey-font"></td>
								<td class="custom-grey-font">${{$row->plan->price}}</td>
								<td class="">${{$row->plan->price}}</td>
								@endif
							</tr>

							@foreach($row->extra as $extra)
								@php
								$selectedExtra += $extra->service_extra->price*$extra->qty;
								@endphp
								<tr>
									<td></td>
									<td>
										{{$extra->service_extra->title}}
										<span class="qty-color">x{{$extra->qty}}</span>
									</td>
									<td>${{$extra->service_extra->price}}</td>
									<td>${{$extra->service_extra->price*$extra->qty}}</td>

								</tr>
							@endforeach

							@endforeach

							<tr>
								<td></td>
								<td></td>
								<td>Subtotal</td>
								<td>${{round_price($cartSubtotal+$selectedExtra)}}</td>
							</tr>

							@foreach ($discountPriority as $priority)
								@if($priority->discount_type == 'reorder_promo')
									@if($totalPromoDiscount)
									<tr>
										<td></td>
										<td></td>
										<td>{{$priority->title}}</td>
										<td>${{round_price($totalPromoDiscount)}}</td>
									</tr>
									@endif
								@elseif($priority->discount_type == 'coupan')
									@if($totalCoupenDiscount)
									<tr>
										<td></td>
										<td></td>
										<td>{{$priority->title}}</td>
										<td>${{round_price($totalCoupenDiscount)}}</td>
									</tr>
									@endif
								@elseif($priority->discount_type == 'combo_discount')
									@if($totalComboDiscount)
									<tr>
										<td></td>
										<td></td>
										<td>{{$priority->title}}</td>
										<td>${{round_price($totalComboDiscount)}}</td>
									</tr>
									@endif
								@elseif($priority->discount_type == 'volume_discount')
									@if($totalVolumeDiscount)
									<tr>
										<td></td>
										<td></td>
										<td>{{$priority->title}}</td>
										<td>${{round_price($totalVolumeDiscount)}}</td>
									</tr>
									@endif
								@endif
							@endforeach
							<tr>
								<td></td>
								<td></td>
								<td>Total</td>
								<td>${{round_price($cartSubtotal+$selectedExtra-$totalPromoDiscount-$totalCoupenDiscount-$totalVolumeDiscount-$totalComboDiscount)}}
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				@endif
			</div>
			<div class="col-lg-4 col-md-12 col-12 payment-box">
				<div class="sticky-block">
					<div class="cart-box">
						<div class="cart-box-list">
							<p class="p-bold">Payment options</p>
							<div class="payment-option">

								{{-- <label class="payment-radio">Credit Card<span>(coming soon)</span>
									<input type="radio" id="credit_card" name="payment_method" name="radio" value="cc">
									<span class="radiomark"></span>
								</label>
 --}}
								<label class="payment-radio">Paypal
									<input type="radio" form="checkout-form" id="paypal" name="payment_method" value="pp" checked>
									<span class="radiomark"></span>
								</label>
							</div>
							<hr>
						</div>
						<div class="secure-checkout paypal">
							@php
							$cartTotal = $cartSubtotal+$selectedExtra-$totalPromoDiscount-$totalCoupenDiscount-$totalVolumeDiscount-$totalComboDiscount;

							if($is_recurring_service == 0){
								if(Auth::user()->earning == 0){
									$fromWalletAmount = 0;
								}elseif(Auth::user()->earning >= $cartTotal){
									$fromWalletAmount = $cartTotal;
								}else{
									$fromWalletAmount = Auth::user()->earning;
								}
							}
							@endphp

							@if(count($Cart))
							@if($fromWalletAmount > 0)
							<div class="input-container">
								<label class="cus-checkmark from-wallet-chk">  
									<input type="checkbox" data-totalcart="{{$cartTotal}}" data-fromwallet="{{$fromWalletAmount}}" data-promodiscount="{{$totalPromoDiscount}}" data-discountadd="{{$totalCoupenDiscount}}" data-volumediscount="{{$totalVolumeDiscount}}" data-combodiscount"{{$totalComboDiscount}}" id="from_wallet" name="from_wallet" class="cus-checkmark from-wallet-chk" checked="">

									<label for="from_wallet" class="label-check">
										<span class="checkbox primary"><span></span></span>
										Use From Wallet (${{$fromWalletAmount}})
									</label>

									<span class="checkmark"></span>
								</label>
							</div>
							<br>
							<form id="paynowBtn" action="{{route('paynow')}}" method="post" class="custom" style="display: @if($cartTotal == $fromWalletAmount){{'block'}} @else {{'none'}} @endif">
								<input  name="_token" class="form-control"  type="hidden" value="{{ csrf_token() }}" id="_token">
								<input type="hidden" name="is_custom_order" value="{{$is_custom_order}}">
								
								<input type="image" onclick="this.disabled=true;this.form.submit();" name="submit" border="0" class="paynow custom-payment-button-size" src="{{front_asset('assets/img/pay-now.png')}}"
								alt="Pay Now">

								<img alt="" border="0" width="1" height="1"
								src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" >
							</form>
							@endif

							<form action="{{route('paypal_express_checkout')}}" method="post" id="paypalBtn" class="custom" style="display: @if($cartTotal == $fromWalletAmount){{'none'}} @else {{'block'}} @endif">
								{{csrf_field()}}
								<input type="hidden" name="is_custom_order" value="{{$is_custom_order}}">
								<input type="hidden" name="is_from_wallet" id="is_from_wallet" value="{{($fromWalletAmount > 0)?1:0}}">
								@if($is_recurring_service == 0)
									<input type="image" onclick="this.disabled=true;this.form.submit();" name="submit" class="paynow custom-payment-button-size" src="{{front_asset('assets/img/paypal-btn.png')}}" alt="Pay Now">
								@else
									<input type="image" onclick="this.disabled=true;this.form.submit();" name="submit" class="paynow custom-payment-button-size" src="{{front_asset('assets/img/paypal-subscribe.jpg')}}" alt="Subscribe Now">
								@endif

							    <img alt="" border="0" width="1" height="1"
							    src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" >
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

@section('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		$('#from_wallet:checkbox').change(function(){
			var totalcart = $(this).data('totalcart');
			var fromwallet = $(this).data('fromwallet');
			var promodiscount = $(this).data('promodiscount');
			var discountadd = $(this).data('discountadd');
			var volumediscount = $(this).data('volumediscount');
			var combodiscount = $(this).data('combodiscount');

			if ($(this).is(':checked')) {
				$('#is_from_wallet').val(1);
				if(parseFloat(totalcart) == parseFloat(fromwallet)){
					$('#paynowBtn').show();
					$('#paypalBtn').hide();
				}else{
					$('#paynowBtn').hide();
					$('#paypalBtn').show();
					$("#discount_amount_cart").val(parseFloat(fromwallet) + parseFloat(promodiscount) + parseFloat(discountadd) + parseFloat(volumediscount) + parseFloat(combodiscount));
				}
			}else{
				$('#is_from_wallet').val(0);
				$('#paynowBtn').hide();
				$('#paypalBtn').show();
				$("#discount_amount_cart").val(parseFloat(promodiscount) + parseFloat(discountadd) + parseFloat(volumediscount) + parseFloat(combodiscount));
			}

		});

		$('input[name="payment_method"]').click(function(){
			if($(this).val() == 'cc'){
				$('.paypal').hide();
			}else{
				$('.paypal').show();
			}
		});
		/*$(document).on('contextmenu', function(e) {
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
		});*/
	});
</script>
@endsection