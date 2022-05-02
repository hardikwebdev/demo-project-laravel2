@extends('layouts.frontend.main')
@section('pageTitle','demo - Services')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Leave Seller Tip</h2>
			</div>    
		</div>    
	</div>    
</section>

<section class="cart-block displaynone"> 
	<div class="container">
		{{-- <div class="row"> 
			<div class="col-lg-12"> 
				<h3 class="cart-title">Summary</h3>
			</div>
		</div> --}}
		<div class="row"> 
			<div class="mx-auto col-lg-6 col-md-12 col-12 payment-box">
				<div class="sticky-block">
					<div class="cart-box">
						<div class="cart-box-list">
							<p class="p-bold">Select Payment options</p>
							<div class="payment-option">
								<label class="payment-radio inline-checkbox">Paypal
									<input type="radio" form="checkout-form" id="paypal" name="payment_method" value="pp" checked>
									<span class="radiomark"></span>
								</label>

								<label class="payment-radio inline-checkbox">Credit Card
									<input type="radio" id="credit_card" name="payment_method" name="radio" value="cc">
									<span class="radiomark"></span>
								</label>
							</div>
							<hr>
						</div>
						<div class="secure-checkout paypal">
							
							<form action="{{route('paypal_express_checkout')}}" method="post" id="paypalBtn" class="custom">
								{{csrf_field()}}

								@if(Auth::user()->earning > 0)
								<div class="input-container">
									<label class="cus-checkmark from-wallet-chk">  
										<input type="checkbox" id="from_wallet" name="from_wallet" class="cus-checkmark from-wallet-chk" checked="">

										<label for="from_wallet" class="label-check">
											<span class="checkbox primary"><span></span></span>
											Use From Wallet (${{round(Auth::user()->earning,2)}})
										</label>

										<span class="checkmark"></span>
									</label>
								</div>
								<br>
								<input type="hidden" name="is_from_wallet" id="is_from_wallet" value="1">
								@else
								<input type="hidden" name="is_from_wallet" id="is_from_wallet" value="0">
								@endif

								
								<button type="submit" onclick="this.disabled=true;this.form.submit();" class="btn btn-success">Pay with Paypal</button>
							</form>
							
						</div>

						
						<div class="secure-checkout bluesnap" style="display:none;">
							<form class="panel-body" id="checkout-form_cc" action="{{ route('bluesnap.checkout') }}" method="POST">
								{{csrf_field()}}
								
								<button type="submit" onclick="this.disabled=true;this.form.submit();" class="btn btn-success">Pay Now</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>  
@endsection

@section('scripts')
<script src="{{ web_asset('plugins/jquery-validation/js/jquery.validate.min.js')}}" type="text/javascript"></script>
<script src="{{ web_asset('plugins/jquery-validation/js/additional-methods.min.js')}}" type="text/javascript"></script>

<script type="text/javascript">
	$(document).ready(function() {
		$('#from_wallet:checkbox').change(function(){
			if ($(this).is(':checked')) {
				$('#is_from_wallet').val(1);
			}else{
				$('#is_from_wallet').val(0);
			}
		});

		$('input[name="payment_method"]').click(function(){
			if($(this).val() == 'cc')
			{
				$('.paypal').hide();
				$('.bluesnap').show();
			}
			else
			{
				$('.paypal').show();
				$('.bluesnap').hide();
			}
		});
	});
</script>
@endsection