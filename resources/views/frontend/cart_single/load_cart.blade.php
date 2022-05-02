@php
use App\CartCombo;
use App\BuyerReorderPromo;
use App\User;
use App\ServicePlan;
$userObj = new User;

$Cart = CartCombo::with('extra','service.images','service.extra','service.user','plan','extra','coupon')->where('uid',Auth::user()->id)->where('is_custom_order',0)->OrderBy('id','desc')->get();
$cartSubtotal = $selectedExtra = $totalDeliveryTime = $totalDeliveryTimeExtra = $totalPromoDiscount = $addDiscount = 0;  

$is_recurring_service = 0;
foreach($Cart as $row){
	if($is_recurring_service == 0 && $row->service->is_recurring == 1){
		$is_recurring_service = 1;
	}
}
@endphp
<div class="row"> 
	<div class="col-lg-8 col-md-8 col-sm-12"> 
		@if(count($Cart))
		<div class="row">
			<div class="col-md-3"></div>
			<div class="col-md-4"></div>
			@if($is_recurring_service == 0)
			<div class="col-md-3">Quantity</div>
			@else
			<div class="col-md-3"></div>
			@endif
			<div class="col-md-1">Price</div>
			<div class="col-md-1"></div>
		</div>
		<hr>

		@foreach($Cart as $row)
		@php
		$cartSubtotal += ($row->plan->price * $row->quantity);
		$totalDeliveryTime +=  $row->plan->delivery_days;

		$buyerPromo = BuyerReorderPromo::where('seller_id',$row->service->uid)
		->where('buyer_id',Auth::user()->id)
		->where('service_id',$row->service->id)
		->where('is_used',0)
		->first();
		$discount_per = 0;
		if(count($buyerPromo)){
			$discount_per = $buyerPromo->amount;
			$totalPromoDiscount += (($row->plan->price * $row->quantity) * $buyerPromo->amount ) / 100;
		}
		@endphp


		<div class="row item-block">
			<div class="col-md-3">
				@php 
				$image_url = url('public/frontend/assets/img/No-image-found.jpg');
				@endphp

				@if(isset($row->service->images[0]))
				@if($row->service->images[0]->photo_s3_key != '')
				@php 
				$image_url = $row->service->images[0]->media_url; 
				@endphp
				@else	
				@php 
				$image_url = url('public/services/images/'.$row->service->images[0]->media_url); 
				@endphp
				@endif 
				@endif
				<img class="img-fluid" src="{{$image_url}}">
			</div>
			<div class="col-md-4 custom">
				<a href="{{route('services_details',[$row->service->user->username,$row->service->seo_url])}}">
					<p class="text-header custom-cart-font-padding text-capitalize">{{$row->service->title}}</p>
				</a>

				<p><strong>{{$ServicePlan->plan_type}}</strong></p>			
				<input type="hidden" name="plan_id[]" value="{{$ServicePlan->id}}">

				@if($row->service->is_recurring == 1)
				<span class="recurring-lable">Recurring Service</span>
				<br>
				@endif
				<p class="description"><?=$row->plan->offering_details;?></p>
				@if($row->coupon)
				<p>
					<span>Coupon Discount</span>
					<span class="promo_price"> 
						@if($row->coupon->discount_type=='amount') 
						${{$row->coupon->discount}} 
						@else 
						{{$row->coupon->discount}}% 
						@endif
					</span>
					@php
					if($row->coupon->discount_type=="amount"){
						$addDiscount+= $row->coupon->discount;
					} else {
						$addDiscount+= 1 * (($row->coupon->discount/100) * $row->plan->price);
					}
					@endphp
				</p>
				@endif

				{{-- Start Volume discount --}}
				@if($userObj->is_premium_seller($row->service->uid))
				<p>
					@foreach($row->service->volume_discount as $value1)
					<span class="text-success">Buy {{$value1->volume}} get {{$value1->discount}}% discount<br></span>
					@endforeach
				</p>
				@endif
				{{-- End Volume discount --}}
				<input type="hidden" name="cart_ids[]" value="{{$row->id}}">
				<input type="hidden" name="coupon_id[]" class="couponID coupon_id_{{$row->service_id}}">
			</div>
			@if($is_recurring_service == 0)
			<div class="col-md-3">
				<div class="product-quantity">
					<div class="input-group">
						<span class="input-group-btn quantity-selector minus" data-price="{{$row->plan->price}}" data-discount_per="{{$discount_per}}" @if($row->coupon) data-promo_price="@if($row->coupon->discount_type=='amount') {{$row->coupon->discount}} @else {{$row->coupon->discount}}% @endif" data-discount_type="{{ $row->coupon->discount_type }}" @endif data-delivery_days="{{$row->plan->delivery_days}}">
							<button type="button" class="btn btn-number">
								<i class="fa fa-minus"></i>
							</button>
						</span>

						<input type="text" id="quantity" name="cart_quantities[]" class="form-control input-number text-center white-bg qty" value="{{$row->quantity}}" min="1" max="100" readonly>
						<span class="input-group-btn quantity-selector plus" data-price="{{$row->plan->price}}" data-discount_per="{{$discount_per}}" @if($row->coupon) data-promo_price="@if($row->coupon->discount_type=='amount') {{$row->coupon->discount}} @else {{$row->coupon->discount}}% @endif" data-discount_type="{{ $row->coupon->discount_type }}" @endif data-delivery_days="{{$row->plan->delivery_days}}">
							<button type="button" class="btn btn-number">
								<i class="fa fa-plus"></i>
							</button>
						</span>
					</div>
				</div>
			</div>
			@else
			<div class="col-md-3">
				<input type="hidden" id="quantity" name="cart_quantities[]" class="form-control input-number text-center white-bg qty" value="{{$row->quantity}}" min="1" max="100" readonly>
			</div>
			@endif

			<div class="col-md-1">
				<div class="product-price">${{$row->plan->price}}</div>
			</div>
		</div> <!-- end iteam-block-->

		@if(count($row->service->extra) > 0)
		<div class="extra-block">
			<div class="row">
				<div class="col-lg-12">
					<h4 class="add-extras">Add on Extras</h4>
				</div>    
			</div>
			@foreach($row->service->extra as $key => $extra)

			@php
			$checked = '';$selectedQty=1;
			@endphp
			@foreach($row->extra as $cartExtra)
			@if($cartExtra->service_extra_id==$extra->id)
			@php
			$checked = 'checked';
			$selectedQty = $cartExtra->qty;
			$selectedExtra = $extra->price*$cartExtra->qty;
			$totalDeliveryTimeExtra = $extra->delivery_days;
			@endphp
			@endif
			@endforeach

			<div class="row add-extra-row"> 
				<div class="col-lg-6 col-md-6 col-6">
					<div class="add-extra-detail">
						<label class="cus-checkmark groupChk">  
							<input type="checkbox" id="chk_{{$row->id}}_{{$key}}" name="extra_chk[{{$row->id}}][]" class="chklist" data-currentsubtotal="{{$cartSubtotal+$selectedExtra}}" data-price="{{$extra->price}}" data-delivery_days="{{$extra->delivery_days}}" value="{{$extra->id}}" {{$checked}}>

							<label for="chk_{{$row->id}}_{{$key}}" class="label-check">
								<span class="checkbox primary"><span></span></span>
							</label>

							<span class="checkmark"></span>
						</label>
						<div class="detail-box">
							<h6>{{$extra->title}}</h6>
						</div>  
					</div>
				</div>

				<div class="col-lg-3 col-md-3 col-3">
					<div class="input-group qty-selector" style="display: @if($checked==''){{'none'}}@else{{'flex'}}@endif";>
						<span class="input-group-btn cart-extra minus" data-price="{{$extra->price}}" data-disVal="{{$totalPromoDiscount+$addDiscount}}" data-delivery_days="{{$extra->delivery_days}}">
							<button type="button" class="btn btn-number">
								<i class="fa fa-minus"></i>
							</button>
						</span>

						<input type="text" name="extra_qty_{{$extra->id}}" class="form-control input-number text-center white-bg qty" value="{{$selectedQty}}" min="1" max="100" readonly>

						<span class="input-group-btn cart-extra plus" data-price="{{$extra->price}}" data-disVal="{{$totalPromoDiscount+$addDiscount}}" data-delivery_days="{{$extra->delivery_days}}">
							<button type="button" class="btn btn-number">
								<i class="fa fa-plus"></i>
							</button>
						</span>
					</div>
				</div>
				<div class="col-lg-3 col-md-3 col-3 text-right price">${{$extra->price}}</div>
			</div>
			<span class="cusborder"></span><br>
			@endforeach
		</div>
		@endif
		@endforeach
		@else
		<div class="text-center text-danger"><h4>You already added combo to cart</h4></div>
		@endif
	</div>
	<div class="col-lg-4 col-md-4 col-sm-12">
		<div class="sticky-block">
			<div class="cart-box">
				<div id="cart-summary-list">
					@include('frontend.cart_single.summary')
				</div>
				{{ Form::close() }}
				{{ Form::open(['route' => ['add_to_cart'], 'method' => 'POST','id'=>'frm_add_to_cart']) }}	
				<div class="secure-checkout">
					<button type="submit" class="btn btn-success">Add to Cart</button>
				</div>
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>

