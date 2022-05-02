@extends('layouts.frontend.main')

@section('pageTitle', 'demo - Services')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Create Coupon</h2>
			</div>    
		</div>    
	</div>    
</section>

<section class="overview-section">
	<div class="container pb-4">
		<div class="row">
			<div class="col-lg-12">
				<div class="popular-grid ">
					<div class="seller pb-2">
						Add New Coupon
					</div> 
					{{ Form::open(['route' => ['save_add_general_coupon'], 'method' => 'POST', 'id' => 'create_general_coupan','class'=>'mb-10','files'=>true]) }}
					<div class="row" id="userprofile">
						<div class="col-lg-4">
							<div class="form-group">
								<label>Coupon Code <span class="text-danger">*</span></label>
								{{Form::text('coupan_code',null,["class"=>"form-control required","placeholder"=>"Coupon Code","id"=>"coupan_code"])}}
							</div>
						</div>
						<div class="col-lg-4">
							<div class="form-group">
								<label>Number Of Uses <span class="text-danger">*</span></label>
								{{Form::text('no_of_uses',null,["class"=>"form-control required","placeholder"=>"Number Of Uses","id"=>"no_of_users"])}}
							</div>
						</div>
						<div class="col-lg-4">
							<div class="form-group">
								<label>Expiry Date <span class="text-danger">*</span></label>
								{{Form::text('expiry_date',date('m/d/Y'),["class"=>"form-control expiry_datepicker","placeholder"=>"Expiry Date","id"=>"expiry_datepicker", "readonly" => "readonly"])}}
							</div>
						</div>
						<div class="col-lg-4 coupon-class form-group">
							<div class="form-group margin-bottom-null">
								<label>Choose Your Discount Method <span class="text-danger">*</span></label>
							</div>
							<div class="row custom-margin-top">
								<div class="col-lg-6">
									<div class="percentage-button">
										<ul class="delivery-radio">
											<li>
												<input type="radio" id="Percentage" name="payment_method" value="percentage" checked="">
												<label for="Percentage" class="radio" data-val='percentage'>Percentage</label>

												<div class="check"><div class="inside"></div></div>
											</li>
										</ul>
									</div>
									<input type='text' placeholder="Percentage" name='discount' class='form-control discounts per required' data-val='percentage'>
								</div>
								<div class="col-lg-6">
									<div class="amount-button">
										<ul class="delivery-radio">
											<li>
												<input type="radio" id="Amount" name="payment_method" value="amount">
												<label for="Amount" class="radio" data-val='amount'>Amount</label>

												<div class="check"><div class="inside"></div></div>
											</li>
										</ul>
									</div>
									<input type='text' placeholder="Amount" name='discount' class='form-control discounts amt display-none required' data-val='amount'>
								</div>
								<div class="col-lg-12">
									<div id='discount_errors' style='color:#a94442; font-family: sans-serif; font-size:11px; display:none'></div>
								</div>
								<input type='hidden' name='discount' id='discount' placeholder="Amount">

							</div>
						</div>

						<div class="col-lg-12">
							<div class="form-group  is-combined-discount">
								<label class="cus-checkmark">  
									<input name="is_combined_other" type="checkbox" value="1">  
									<span class="checkmark"></span>
								</label>
								<div class="detail-box">
									<lable>Can not be combined with other discounts</lable>
								</div>
							</div>
						</div>

						<div class="col-lg-12">
							<div class="form-group is-combined-discount">
								<label class="cus-checkmark">  
									<input name="allow_on_recurring_order" id="allowonlyrecurring" type="checkbox" value="1">  
									<span class="checkmark"></span>
								</label>
								<div class="detail-box">
									<lable>Allow only recurring orders</lable>
									<div class="text-danger shownote hide"><i>Note : This coupon will be apply only on recurring orders</i></div>
								</div>
							</div>
						</div>

						<div class="col-lg-12">
							<div class="form-group is-combined-discount">
								<label class="cus-checkmark">  
									<input name="is_follower_mail_disabled" type="checkbox" value="1" id="is_follower_mail_disabled">  
									<span class="checkmark"></span>
								</label>
								<div class="detail-box">
									<label for="is_follower_mail_disabled">Prevent this coupon from being sent to followers</label>
								</div>
							</div>
						</div>

						<div class="col-lg-2">
							<button name="submit_add_coupon" type="submit" class="send-request-buttom" id='btncoupan'>Add Coupon</button>
						</div>

						<div class="col-lg-12">
							<em>Note : It will be applied for services and courses both.</em>
						</div>

					</div>
					{{ Form::close() }}
				</div>
			</div>   
		</div>
	</div>
</section>
@endsection

@section('css')
<link href="{{ web_asset('plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}" rel="stylesheet" type="text/css" />
<style type="text/css">
	.datepicker>div{display:block}
	.cke_reset_all table {
		z-index: 100008 !important;
	}
	#create_general_coupan input[type="text"]{
		width: 95%;
	}
</style>
@endsection


@section('scripts')
<script type='text/javascript'>

	$(document).ready(function() {

		$('#allowonlyrecurring').click(function(){
            if($(this).prop("checked") == true){
                $('.shownote').removeClass('hide');
            }else{
				$('.shownote').addClass('hide');
			}
        });

		$('.expiry_datepicker').datepicker();
		$('.amt').hide();
		// $('#Percentage').attr('checked','checked');
		$('.percentage-button').on('click',function(){
			$('#Percentage').attr('checked','checked');
			$('#Amount').attr('checked', false);
		});
		$('.amount-button').on('click',function(){
			$('#Percentage').attr('checked', false);
			$('#Amount').attr('checked','checked');
		});

		$('.radio').on('click',function(){
			$('.discounts').val('');
			$('#discount').val('');
			var vals = $(this).data("val");
			if(vals=='percentage'){
				$('.per').show();
				$('.amt').hide();
			}else{
				$('.per').hide();
				$('.amt').show();
			}
		});

		$('.discounts').on('keyup',function(e){
			var discount = $(this).val();
			var dataval = $(this).data("val");
			$('#discount').val(discount);
			if(dataval == 'percentage'){

				if(discount == ''){ 
					$('#discount_errors').show(); 
					$('#discount_errors').text("Please enter Discount"); 
				}
				else if(discount <= 0){
					$('#discount_errors').show();
					$('#discount_errors').text("Percentage can't less than 1");
					$('.discounts').val('');
					$('#discount').val('');
					$('#btncoupan').attr('disabled',true);
					$('#btncoupan').addClass('custom-dissable');
				}
				else if(discount > 99){
					$('#discount_errors').show();
					$('#discount_errors').text("Percentage must be less than 100");
					$('.discounts').val('');
					$('#discount').val('');
					$('#btncoupan').attr('disabled',true);
					$('#btncoupan').addClass('custom-dissable');
				}else if(Number.isInteger(parseFloat(discount)) == false || Number.isNaN(after_discount) == true){
					$('#discount_errors').show(); 
					$('#discount_errors').text("Please enter only number"); 
					$('.discounts').val(''); $('#discount').val('');
					$('#btncoupan').attr('disabled',true);
					$('#btncoupan').addClass('custom-dissable');
				}else{
					$('#discount_errors').hide();
					$('#btncoupan').attr('disabled',false);
					$('#btncoupan').removeClass('custom-dissable');
				}
			}
			else if(dataval == 'amount'){

				if(discount == ''){ 
					$('#btncoupan').attr('disabled',false); 
					$('#discount_errors').show(); 
					$('#discount_errors').text("Please enter Discount"); 
					$('#btncoupan').addClass('custom-dissable');
				}
				else if(discount <= 0){
					$('#btncoupan').attr('disabled',false);
					$('#discount_errors').show();
					$('#discount_errors').text("Amount can't less than 1");
					$('#btncoupan').addClass('custom-dissable');
					$('.discounts').val('');
					$('#discount').val('');
				}else if(jQuery.isNumeric(discount) == false){
					$('#btncoupan').attr('disabled',false);
					$('#discount_errors').show(); $('#discount_errors').text("Please enter only number"); $('.discounts').val(''); $('#discount').val('');
					$('#btncoupan').addClass('custom-dissable');
				}else{
					$('#discount_errors').hide();
					$('#btncoupan').attr('disabled',false);
					$('#btncoupan').removeClass('custom-dissable');
				}
			}
		});
	});

</script>

@endsection
