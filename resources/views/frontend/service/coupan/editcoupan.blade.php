@extends('layouts.frontend.main')
@section('pageTitle','demo - Account Information')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Update Coupon</h2>
			</div>    
		</div>    
	</div>    
</section>

<section class="overview-section">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="popular-grid ">
					<div class="seller pb-2">
						Update Coupon
					</div> 
					{{ Form::open(['route' => ['coupaneditSubmit','id'=>$coupan->id,'type'=>$type], 'method' => 'POST', 'id' => 'createcoupan','files'=>true]) }}
					<input type='hidden' value='{{ $coupan->service_id }}' name='service_id'>
					<div class="row" id="userprofile">
						<div class="col-lg-4">
							<div class="form-group">
								<label>Coupon Code <span class="text-danger">*</span></label>
								{{Form::text('coupan_code',$coupan->coupan_code,["class"=>"form-control required","placeholder"=>"Coupon Code","id"=>"coupan_code"])}}
							</div>
						</div>
						<div class="col-lg-4">
							<div class="form-group">
								<label>Number Of Uses <span class="text-danger">*</span></label>
								{{Form::text('no_of_uses',$coupan->no_of_uses,["class"=>"form-control required","placeholder"=>"Number Of Uses","id"=>"no_of_users"])}}
							</div>
						</div>
						<div class="col-lg-4">
							<div class="form-group">
								<label>Expiry Date <span class="text-danger">*</span></label>
								{{Form::text('expiry_date',isset($coupan->expiry_date)?date('m/d/Y',strtotime($coupan->expiry_date)):date('m/d/Y'),["class"=>"form-control expiry_datepicker","placeholder"=>"Expiry Date","id"=>"expiry_datepicker", "readonly" => "readonly"])}}
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
												<input type="radio" id="Percentage" name="discount_type" value="percentage" @if($coupan->discount_type=='percentage') checked="" @endif>
												<label for="Percentage" class="radio" data-val='percentage'>Percentage</label>

												<div class="check"><div class="inside"></div></div>
											</li>
										</ul>
									</div>
									<input type='text' placeholder="Percentage" name='discount' class='form-control discounts per required' data-val='percentage' @if($coupan->discount_type=='percentage') value="{{ $coupan->discount  }}" @else hidden="" @endif>
								</div>
								<div class="col-lg-6">
									<div class="amount-button">
										<ul class="delivery-radio">
											<li>
												<input type="radio" id="Amount" name="discount_type" value="amount" @if($coupan->discount_type=='amount') checked="" @endif>
												<label for="Amount" class="radio" data-val='amount'>Amount</label>

												<div class="check"><div class="inside"></div></div>
											</li>
										</ul>
									</div>
									<input type='text' placeholder="Amount" name='discount' class='form-control discounts amt required' data-val='amount' @if($coupan->discount_type=='amount') value="{{ $coupan->discount  }}" @else hidden="" @endif>
								</div>
								<div class="col-lg-12">
									<div id='discount_errors' style='color:#a94442; font-family: sans-serif; font-size:11px; display:none'></div>
								</div>
								<input type='hidden' name='discount' id='discount' placeholder="Amount" value='{{ $coupan->discount }}'>

							</div>
						</div>
						<div class="col-lg-12">
							<div class="form-group  is-combined-discount">
								<label class="cus-checkmark">  
									<input name="is_combined_other" type="checkbox" value="1" {{($coupan->is_combined_other)?'checked':''}}>  
									<span class="checkmark"></span>
								</label>
								<div class="detail-box">
									<lable>can not be combined with other discounts</lable>
								</div>
							</div>
						</div>
						<div class="col-lg-2">
							<button type="submit" class="send-request-buttom" id='btncoupan'>Update Coupon</button>
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
{{-- <link rel="stylesheet" href="{{front_asset('css/bootstrap.min.css')}}"> --}}
<link href="{{ web_asset('plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}" rel="stylesheet" type="text/css" />
<style type="text/css">
.datepicker>div{display:block}
.cke_reset_all table {
	z-index: 100008 !important;
}
#createcoupan input[type="text"]{
	width: 95%;
}
</style>
@endsection
@section('scripts')
<script src=" {{front_asset('js/bootstrap-datepicker.js')}}"  type="text/javascript"></script>
<script type='text/javascript'>

	$('.radio').on('click',function(){
		var vals = $(this).data("val");
		if(vals=='percentage'){
			$('.per').attr("hidden", false);
			$('.amt').attr("hidden", true);
		}else{
			$('.per').attr("hidden", true);
			$('.amt').attr("hidden", false);
		}
	});

	$('.percentage-button').on('click',function(){
		$('#Percentage').attr('checked','checked');
		$('#Amount').attr('checked', false);
	});
	$('.amount-button').on('click',function(){
		$('#Percentage').attr('checked', false);
		$('#Amount').attr('checked','checked');
	});


	$('.discounts').on('keyup',function(e){
		var price = "{{$coupan->service_plan[0]->price}}";
		var discount = $(this).val();
		var dataval = $(this).data("val");
		$('#discount').val(discount);

		if(dataval == 'percentage'){

			/*Start: minimum $5 after discount*/
			var after_discount = ( parseFloat(price) - ((parseFloat(price) * discount) / 100));
			/*End: minimum $5 after discount*/

			if(discount == ''){ 
				$('#discount_errors').show(); 
				$('#discount_errors').text("Please enter Discount"); 
			}
			else if(discount <= 0){
				$('#discount_errors').show();
				$('#discount_errors').text("Percentage can't less than 1");
				$('.discounts').val('');
				$('#discount').val('');
				$('#btncoupan').addClass('custom-dissable');
			}
			else if( after_discount < __service_min_price ){
				$('#discount_errors').show();
				$('#discount_errors').text("Service price must be $"+__service_min_price+" after discount");
				$('.discounts').val('');
				$('#discount').val('');
				$('#btncoupan').addClass('custom-dissable');
			}else if(discount > 99){
				$('#discount_errors').show();
				$('#discount_errors').text("Percentage must be less than 100");
				$('.discounts').val('');
				$('#discount').val('');
				$('#btncoupan').addClass('custom-dissable');
			}else if(Number.isInteger(parseFloat(discount)) == false || Number.isNaN(after_discount) == true){
				$('#discount_errors').show(); 
				$('#discount_errors').text("Please enter only number"); 
				$('.discounts').val(''); $('#discount').val('');
				$('#btncoupan').addClass('custom-dissable');
			}else{
				$('#discount_errors').hide();
				$('#btncoupan').removeClass('custom-dissable');
			}

			var disc = $('#discount').val();
			if(disc != null)
			{
				$('#btncoupan').removeClass('custom-dissable');
			}
				
		}
		else if(dataval == 'amount'){

			/*Start: minimum $5 after discount*/
			var after_discount = (parseFloat(price) - discount);
			console.log(after_discount);
			/*End: minimum $5 after discount*/

			if(discount >= parseFloat(price)){
				$('#discount_errors').show(); $('#discount_errors').text("Discount must be less than price");
				$('#btncoupan').attr('disabled',true);
			}
			else if( after_discount < __service_min_price ){
				$('#discount_errors').show(); $('#discount_errors').text("Service price must be $"+__service_min_price+" after discount");
				$('#btncoupan').attr('disabled',true);
			}
			else if(discount == ''){ 
				$('#discount_errors').show(); 
				$('#discount_errors').text("Please enter Discount"); 
			}
			else if(discount <= 0){
				$('#discount_errors').show();
				$('#discount_errors').text("Amount can't less than 1");
				$('.discounts').val('');
				$('#discount').val('');
			}else if(jQuery.isNumeric(discount) == false){
				$('#discount_errors').show(); $('#discount_errors').text("Please enter only number"); $('.discounts').val(''); $('#discount').val('');
			}else{
				$('#discount_errors').hide();
			}
		}
	});
	$(function(){
		$('.expiry_datepicker').datepicker();
	});
</script>

@endsection
