@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Buyer Transactions')
@section('content')
<!-- Get Project -->
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
		@include('layouts.frontend.messages')

		{{ Form::open(['route' => ['paymentSingle'], 'method' => 'POST','id'=>'frm_cart_payment']) }}
		<div id="cart_data">
			@include('frontend.cart_single.load_cart')
		</div>
		{{ Form::close() }}

	</div>
</section>  

@endsection

@section('scripts')

<script type="text/javascript">
$(document).on('click','.discountCoupon',function(){
		var id = $(this).data('id');

		var couponCode = $(this).parent().find('.couponCodeNew').val();

		
		
		$.ajax({
			url : "{{ route('applyCouponCodeCombo') }}",
			type : 'get',
			data : {'id':id,'couponCode':couponCode},
			success : function(data){
				
					if(data.success == true)
					{

						$('.couponMessage'+id).html("<p style='color:"+data.color+"'>"+data.message+"</p>");

						$('.coupon_id_'+id).val(data.coupon_id);	

						 $.each(data.otherCoupon, function (index,value) {
						 	console.log(value.id);
						 	$('.coupon_id_'+value.service_id).val(value.coupon_id);	

						 });
						 // return false;
						cart_render_summary();
						
						
					}
					else
					{
						$('.couponMessage'+id).html("<p style='color:"+data.color+"'>"+data.message+"</p>");
					}
					
					


			}
		});
	});

</script>



@php
$LastCart = null;

if (\Session::has('dataLayerCartId') && \Session::get('dataLayerCartId') != '') {
    $LastCart = \App\CartCombo::with('service','plan')->find(\Session::get('dataLayerCartId'));
    \Session::forget('dataLayerCartId');
}
@endphp

@if($LastCart)
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
	'event' : 'addToCart',
	fbCustomData :
	{
		'content_name' : '{{display_title($LastCart->service->title)}}',
		'content_category' : '{{$LastCart->service->category->category_name}}',
		'content_ids' : '{{$LastCart->service->id}}',
		'content' : [{
			'id' : '{{$LastCart->service->id}}',
			'quantity' : '{{$LastCart->quantity}}',
			'price' : '{{number_format($LastCart->plan->price,2,'.','')}}'
		}],
		'content_type': 'product',
		'value' : '{{number_format($LastCart->plan->price,2,'.','')}}',
		'currency' : 'USD'
	}
});
</script>
@endif

@endsection