@php
use App\User;
$userObj = new User; 
@endphp


@extends('layouts.frontend.main')
@section('pageTitle','demo - Services')
@section('content')

<section class="sub-header cart-header">
	<div class="container">
		<div class="row">    
			<div class="col-lg-12">    
				<ul class="cus-breadcrumb">
					<li><a href="{{url('/')}}">Home</a></li>
					<li><a href="javascript:void();">Services</a></li>
				</ul>    
				<h2 class="heading mb-2">Your Service</h2>
			</div>
		</div>    
	</div>
</section>

<section class="cart-block">
	<div class="container">

		<div class="row"> 
			<div class="col-lg-12"> 
				<h3 class="cart-title">Customize your Service</h3>
			</div>
		</div>

		{{ Form::open(['route' => ['add_to_cart'], 'method' => 'POST','id'=>'frm_add_to_cart']) }}
		<div class="row"> 

			<div class="col-lg-8 col-md-8 col-sm-12"> 
				<div class="row">
					<div class="col-md-3"></div>
					<div class="col-md-4"></div>
					@if($Service->is_recurring == 0)
					<div class="col-md-3">Quantity</div>
					@else
					<div class="col-md-3"></div>
					@endif
					<div class="col-md-2">Price</div>
				</div>
				<hr>
				<div class="row item-block">
					<div class="col-md-3">
						@php 
						$image_url = url('public/frontend/assets/img/No-image-found.jpg');
						@endphp
						@if(isset($Service->images[0]))
						@if($Service->images[0]->photo_s3_key != '')
						@php 
						$image_url = $Service->images[0]->media_url; 
						@endphp
						@else	
						@php 
						$image_url = url('public/services/images/'.$Service->images[0]->media_url); 
						@endphp
						@endif 
						@endif
						<img class="img-fluid" src="{{$image_url}}">
					</div>
					<div class="col-md-4">
						<div class="product-title text-capitalize">{{$Service->title}}</div>
						{{-- <div class="product-delete">
							<a href="">Delete</a> 
						</div> --}}
						<p class="text-header small"><?=$ServicePlan->package_name;?></p>
						@if($Service->is_recurring == 1)
							<span class="recurring-lable">Recurring Service</span>
							<br>
						@endif
						<p class="description"><?=$ServicePlan->offering_details;?></p>
						{{-- Start Volume discount --}}
						@if($userObj->is_premium_seller($Service->uid))
						<p>
						@foreach($Service->volume_discount as $value1)
							<div class="text-success">Buy {{$value1->volume}} get {{$value1->discount}}% discount</div>
						@endforeach
						</p>
						@endif
						{{-- End Volume discount --}}
					</div>

					@if($Service->is_recurring == 0)
					<div class="col-md-3">
						<div class="product-quantity">
							<div class="input-group">
								<span class="input-group-btn cart-customize minus" data-price="{{$ServicePlan->price}}" data-delivery_days="{{$ServicePlan->delivery_days}}">
									<button type="button" class="btn btn-number">
										<i class="fa fa-minus"></i>
									</button>
								</span>
								<input type="text" id="quantity" name="quantity" class="form-control input-number white-bg qty" value="1" min="1" max="100" readonly>
								<span class="input-group-btn cart-customize plus" data-price="{{$ServicePlan->price}}" data-delivery_days="{{$ServicePlan->delivery_days}}">
									<button type="button" class="btn btn-number">
										<i class="fa fa-plus"></i>
									</button>
								</span>
							</div>
						</div>
					</div>
					@else
					<div class="col-md-3">
						<input type="hidden" id="quantity" name="quantity" class="form-control input-number white-bg qty" value="1" min="1" max="100" readonly>
					</div>
					@endif

					<div class="col-md-2">
						<div class="product-price">${{$ServicePlan->price}}</div>
					</div>
					<span class="cusborder"></span>
				</div>

				

				@if(count($Service->extra))
				<div class="extra-block">
					<div class="row">
						<div class="col-lg-12">
							<h4 class="add-extras">Add on Extras</h4>
						</div>    
					</div>
					@foreach($Service->extra as $key => $row)
					<div class="row add-extra-row"> 
						<div class="col-lg-6 col-md-6 col-6">
							<div class="add-extra-detail">
								<label class="cus-checkmark groupChk">  

									<input type="checkbox" id="chk_{{$key}}" name="extra_chk[]" class="chklist_customize" data-price="{{$row->price}}" value="{{$row->id}}" data-delivery_days="{{$row->delivery_days}}">

									<label for="chk_{{$key}}" class="label-check">
										<span class="checkbox primary"><span></span></span>
									</label>

									<span class="checkmark"></span>
								</label>
								<div class="detail-box">
									<h6>{{$row->title}}</h6>
								</div>  
							</div>
						</div>

						<div class="col-lg-3 col-md-3 col-3">
							
							<div class="input-group qty-selector" style="display: none;">
								<span class="input-group-btn cart-extra-customize minus" data-price="{{$row->price}}" data-delivery_days="{{$row->delivery_days}}">
									<button type="button" class="btn btn-number">
										<i class="fa fa-minus"></i>
									</button>
								</span>

								<input type="text" name="extra_qty_{{$row->id}}" {{-- name="qty[]" --}} class="form-control input-number text-center white-bg qty" value="1" min="1" max="100" readonly>

								<span class="input-group-btn cart-extra-customize plus" data-price="{{$row->price}}" data-delivery_days="{{$row->delivery_days}}">
									<button type="button" class="btn btn-number">
										<i class="fa fa-plus"></i>
									</button>
								</span>
							</div>
						</div>
						<div class="col-lg-3 col-md-3 col-3 text-right">${{$row->price}}</div>
					</div>
					@endforeach

				</div>
				@endif
			</div>
			<div class="col-lg-4 col-md-4 col-sm-12">
				<div class="sticky-block">
					<div class="cart-box">
						<div class="cart-box-list">
							<p class="p-bold">Summary</p>
							<ul class="all-item-cart-list">
								<li >Subtotal<span class="subtotal-amt">${{$ServicePlan->price}}</span></li>
							</ul>
							<hr>
							<ul class="all-item-cart-list">
								<li class="p-bold">Total<span class="total-amt">${{$ServicePlan->price}}</span></li>
								@if($Service->is_recurring == 0)
								<li>Delivery Time<span class="delivery-days">{{$ServicePlan->delivery_days}} Days</span></li>
								@endif
							</ul>
							<hr>
						</div>

						<input type="hidden" name="id" value="{{$Service->id}}">
						<input type="hidden" name="plan_id" value="{{$ServicePlan->id}}">
						<input type="hidden" id="current_subtotal" value="{{$ServicePlan->price}}">
						<input type="hidden" id = "current_total" value="{{$ServicePlan->price}}">
						<input type="hidden" id="delivery_days" value="{{$ServicePlan->delivery_days}}">
						<input type="hidden" id="coupon_id" name='coupon_id'>

						<div id='show_discount'></div>
						<div id='show_discount_error'></div>

						<div class="form-group affiliate-form gradient">

							<input type='text' name='coupan_code' class="form-control" id='couponCode' placeholder='Promo Code'>

							<button type='button' class="btn btn-primary discountCpn" data-id='{{$Service->id}}'>Apply</button>

						</div>

						<div class="secure-checkout">
							<button type="submit" class="btn btn-success">Add to Cart</button>
						</div>
					</div>
					{{-- <div class="continue-shopping text-center">
						<div><a href="">Continue Shopping</a></div>
					</div> --}}
				</div>
			</div>
		</div>
		{{ Form::close() }}

	</div>
</section>  

@endsection

@section('scripts')
<script type='text/javascript'>
	$('.discountCpn').on('click',function(){
		var id = $(this).data('id');
		var couponCode = $('#couponCode').val();
		$.ajax({
			url : "{{ route('applyCouponCodeCombo') }}",
			type : 'get',
			data : {'id':id,'couponCode':couponCode},
			success : function(data){
				if(data.success==true){
					$('#show_discount_error').hide();
					$('#show_discount').show();
					$('#coupon_id').val(data.coupon_id);
					$('#show_discount').html(data.message);
				}else{
					$('#show_discount').hide();
					$('#show_discount_error').show();
					$('#coupon_id').val('0');
					$('#show_discount_error').html(data.message);
				}
			}
		});
	});
</script>

<script>
	function add_to_dataLayer(){
		var qty = $('#quantity').val();
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push({
			'event' : 'addToCart',
			'eventCallback': function() { 
				$('#frm_add_to_cart').submit();
			},
			fbCustomData :
			{
				'content_name' : '{{$Service->title}}',
				'content_category' : '{{$Service->category->category_name}}',
				'content_ids' : '{{$Service->id}}',
				'content' : [{
					'id' : '{{$Service->id}}',
					'quantity' : qty,
					'price' : '{{$ServicePlan->price}}'
				}],
				'content_type': 'product',
				'value' : '{{number_format($ServicePlan->price,2,'.','')}}',
				'currency' : 'USD'
			}
		});
		return false;
	}
</script>

@endsection