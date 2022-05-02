@php

use App\CartCombo;
use App\CartExtraCombo;
use App\BuyerReorderPromo;
use App\User;
use App\ServiceExtra;
use App\DiscountPriority;
$userObj = new User;
$cartObj = new CartCombo;

$Cart = CartCombo::with('extra','service.images','service.extra','service.user','plan','extra','coupon')->where('uid',Auth::user()->id)->where('is_custom_order',0)->OrderBy('id','desc')->get();
$cartSubtotal = $selectedExtra = $totalDeliveryTime = $totalDeliveryTimeExtra = $totalPromoDiscount = $totalCoupenDiscount = $totalVolumeDiscount = $totalComboDiscount = 0; 
$discountPriority = DiscountPriority::OrderBy('priority','desc')->get();
$is_recurring_service = 0;
@endphp

@foreach($Cart as $row)
@php
$cartSubtotal += ($row->plan->price * $row->quantity);
$totalDeliveryTime +=  $row->plan->delivery_days;
$afterDiscountPrice = $row->plan->price * $row->quantity;

if($is_recurring_service == 0 && $row->service->is_recurring == 1){
	$is_recurring_service = 1;
}
$cart_extra_price_total = 0;
foreach($row->service->extra as $key => $extra){
	$checked = '';
	$selectedQty=1;
	foreach($row->extra as $cartExtra){
		if($cartExtra->service_extra_id==$extra->id){
			$serviceExtraPrice=ServiceExtra::where('id',$cartExtra->service_extra_id)->first();
			$checked = 'checked';
			$selectedQty = $cartExtra->qty;
			$selectedExtra += $serviceExtraPrice->price*$cartExtra->qty;
			$cart_extra_price_total += $serviceExtraPrice->price*$cartExtra->qty;
			$totalDeliveryTimeExtra += $extra->delivery_days;
		}
	}
}

/*Check for priority*/
$buyerPromo = BuyerReorderPromo::where('seller_id',$row->service->uid)
->where('buyer_id',Auth::user()->id)
->where('service_id',$row->service->id)
->where('is_used',0)
->first();

/*Check any one discount is single*/
$is_single = $cartObj->check_is_single_discount($discountPriority,$row,$buyerPromo,$Cart);
	//dd($discountPriority->toArray());

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

@endforeach

<div class="cart-box-list">
	<p class="p-bold">Summary </p>

	<ul class="all-item-cart-list">
		<li>Subtotal<span class="subtotal-amt">${{$cartSubtotal+$selectedExtra}}</span></li>
		
		@foreach ($discountPriority as $priority)
		@if($priority->discount_type == 'reorder_promo')
		@if($totalPromoDiscount)
		<li>{{$priority->title}}<span class="promo-discount">${{round_price($totalPromoDiscount)}}</span></li>
		@endif
		@elseif($priority->discount_type == 'coupan')
		@if($totalCoupenDiscount)
		<li>{{$priority->title}}<span class="promocode-discount">${{round_price($totalCoupenDiscount)}}</span></li>
		@endif
		@elseif($priority->discount_type == 'combo_discount')
		@if($totalComboDiscount)
		<li>{{$priority->title}}<span class="volume-discount">${{round_price($totalComboDiscount)}}</span></li>
		@endif
		@elseif($priority->discount_type == 'volume_discount')
		@if($totalVolumeDiscount)
		<li>{{$priority->title}}<span class="volume-discount">${{round_price($totalVolumeDiscount)}}</span></li>
		@endif
		@endif
		@endforeach
	</ul>

	<hr>
	<ul class="all-item-cart-list">
		<li class="p-bold">Total<span class="total-amt">${{round_price($cartSubtotal+$selectedExtra-$totalPromoDiscount-$totalCoupenDiscount-$totalVolumeDiscount-$totalComboDiscount)}}</span></li>
		@if($is_recurring_service == 0)
		<li>Delivery Time<span class="delivery-days">{{$totalDeliveryTime+$totalDeliveryTimeExtra}} Days</span></li>
		@endif
	</ul>
	@if($is_recurring_service == 0)
	<div class="form-group affiliate-form gradient">
		<div class="couponMessage{{$row->service_id}}"></div>
		<input type='text' id='coupan_code_new' class="form-control couponCodeNew"  placeholder='Promo Code'  style="width: 70%;">
		<button type='button' class="btn btn-primary discountCoupon discountCpn{{$row->service_id}}" data-id='{{$row->service_id}}' style="width: 30%;">Apply</button>
	</div>
	@endif
	<hr>
</div>


