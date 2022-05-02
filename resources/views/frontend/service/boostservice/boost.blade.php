@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Create New Services')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Boost Services</h2>
			</div>    
		</div>    
	</div>    
</section>
<section class="pricing py-5">
	<div class="container boost_page">
		<div class="row"> 
			<div class="col-lg-12"> 
				<h3 class="cart-title">Select Plan</h3>
				{{-- @if($errors->any())
				<div class="alert alert-danger" role="alert">
						{{$errors->first()}}
					</div>
				@endif --}}
				@include('layouts.frontend.messages')
			</div>
		</div>
		{{ Form::open(['route' => ['boostPayment'], 'method' => 'POST', 'id' => 'frmServiceBoost']) }}
		<div class="row">
			@if(count($planInfo))

			@foreach ($planInfo as $plan => $val)
			<div class="col-cus-5 col-12 col-md-6 col-lg custom pb-12">
				<div class="card pack-box-{{ $val->id}} custom-webkit-full-width">
					<div class="card-body">
						@if( $val->id == 2 )
						<h5 class="card-title text-center">Home Page Top Rated Services</h5>
						@else
						<h5 class="card-title text-center">{{ $val->name}}</h5>
						@endif
						<h6 class="card-price text-center price-{{ $val->id}}">${{ $val->price}}</h6>
						<p class="credit">{{ $val->description}}</p>


						@if( $val->id == 4 || $val->id == 5)
						<div class="text-left">
							<div class="form-check">
	                            <label class="check-box-main ">
	                                <input class="form-check-input category_slot select-slot-{{ $val->id}}" name="category_slot" type="radio" value="1" checked=""  data-price="{{ $val->price }}" data-id="{{ $val->id }}">
	                                <span class="checkmark"></span>
	                                First slot (${{ $val->price}})
	                            </label>
	                        </div>
	                        <div class="form-check">
	                            <label class="check-box-main ">
	                                <input class="form-check-input category_slot select-slot-{{ $val->id}}" name="category_slot" type="radio" value="2" data-price="{{ $val->sub_price }}" data-id="{{ $val->id }}">
	                                <span class="checkmark"></span>
	                                Second or third slot (${{ $val->sub_price}})
	                            </label>
	                        </div>
                        </div>			
                        @endif


						{{-- <label class="radio-inline"><input type="radio" name="optradio" checked>Option 1</label>
						<label class="radio-inline"><input type="radio" name="optradio">Option 2</label> --}}

						
						@if($plan == 1)
						<select class="form-control select-days select-days-{{ $val->id}} required"  data-price="{{ $val->price }}" data-id="{{ $val->id }}" id="ad_days-{{ $val->id}}" name="ad_days" data-bv-field="ad_days">
							<option value="" selected="selected">Number of days</option>
							<option value="1">1 Day</option>
							<option value="2">2 Days</option>
							<option value="3">3 Days</option>
						</select>
						@else
						<select class="form-control select-days select-days-{{ $val->id}}"  data-price="{{ $val->price }}" data-id="{{ $val->id }}" id="ad_days-{{ $val->id}}" name="ad_days" data-bv-field="ad_days">
							<option value="" selected="selected">Number of days</option>
							<option value="1">1 Day</option>
							<option value="2">2 Days</option>
							<option value="3">3 Days</option>
						</select>
						@endif

						@if($plan == 1)
						<a href="#" class="btn active button-select-pack btn-block btn-primary selected selected-{{ $val->id}} custom-boost-button-width custom-primary" id="ad_days-{{ $val->id}}" data-id="{{ $val->price }}" data-sub-price="{{ $val->sub_price }}">Selected</a>
						@else
						<a href="#" class="btn active button-select-pack btn-block btn-primary selected selected-{{ $val->id}} custom-boost-button-width" id="ad_days-{{ $val->id}}" data-id="{{ $val->price }}" data-sub-price="{{ $val->sub_price }}">Select Pack</a>
						@endif
					</div>
				</div>
			</div>
			@endforeach
			<div class="show-error-parent">
				<div class="show-error"></div>
			</div>

			<input type="hidden" name="selected_pack" id="selected-pack" value="{{ $planInfo[0]->id}}">
			<input type="hidden" name="total_days" id="total_days" value="">
			<input type="hidden" name="service_seo_url" id="service_id" value="{{$seo_url}}">
			@endif

		</div>
		<div class="row"> 
			<div class="col-lg-12">
				<div class="process-pay">
					<button type="submit" class=" mid save-button pro-btn">Proceed to Pay</button>
				</div>
			</div>      
		</div> 

		{{ Form::close() }} 
	</div>
</section>

@php 
$plan_id = Session::get('rentAdSpotPlanId');
@endphp
@endsection

@section('scripts')

<script src="{{ web_asset('plugins/jquery-validation/js/jquery.validate.min.js')}}" type="text/javascript"></script>
<script src="{{ web_asset('plugins/jquery-validation/js/additional-methods.min.js')}}" type="text/javascript"></script>

<script>
	$('document').ready(function(){
		var plan_id = "{{$plan_id}}";
		if($('.select-days-'+plan_id).length > 0) {
			$('.select-days-'+plan_id).trigger('change');
		}
	});

	$('#frmServiceBoost').validate({
		rules :{
			total_days : {
				required : true,
			}
		},
		messages : {
			ad_days : {
				required :'Please select days for promoting service'
			}
		},
		errorPlacement : function(error , element){
			if(element.attr('name') == "ad_days" ){
				//error.insertAfter('.show-error');
			}else{
				error.insertAfter(element);
			}
		}	
	});
	/*$(document).on('click','.button-select-pack',function(){

		$( '.selected' ).each(function( index ) {
			$( this ).removeClass('selected').addClass('select-pack');
			$( this ).removeClass('custom-primary').addClass('dark-light');
			$( this ).text('Select Pack');
		});
		$(this).text('Selected');
		$(this).removeClass('select-pack').addClass('selected');
		$(this).removeClass('dark-light').addClass('custom-primary');
		$('#selected-pack').val($(this).attr('id'));
		$('.save-button').html('Proceed to Pay ($'+$(this).attr('data-id')+')')
		$('.select-days').val('');
		$('.select-days').removeClass('required');
		console.log('ad_days-'+$(this).attr('id'));
		$('#ad_days-'+$(this).attr('id')).addClass('required');
		$(document).find('.select-days').removeClass('error');
		$(document).find('.show-error-parent').children().css('display','none');

	});*/

	$(document).on('click','.category_slot',function(){
		var id = $(this).attr('data-id');
		var days = $('.select-days-'+id).val();
		$('.select-days-'+id).trigger('change');
	});

	$(document).on('change','.select-days',function(){

		var selected_days = $(this).val();
		var id = $(this).attr('id');
		var findId = id.split('-')[1];
		var plan_price = $('.selected-'+findId).attr('data-id');
		var category_slot = $('.category_slot:checked').val();
		
		if(findId == 4 || findId == 5){
			if(category_slot == 2){
				plan_price = $('.selected-'+findId).attr('data-sub-price');;
			}
		}

		var total;
		if($(this).val()){
			total = plan_price * $(this).val();
		}else{
			total = plan_price;
		}


		$('.price-'+findId).html('<span>$</span>'+total);

		var savebuttonId = $(document).find('.selected').attr('id');
		var days = $(document).find('#ad_days-'+savebuttonId).val();
		var total_price = $(document).find('.selected').attr('data-id');

		if(findId == 4){
			if(category_slot == 2){
				total_price = $(document).find('.selected').attr('data-sub-price');
			}
		}

		if(days){
			price_total = total_price *days ;
		}else{
			price_total = total_price;
		}
		$('.save-button').html('Proceed to Pay ($'+price_total+')');
		$('#total_days').val($(this).val());

		var selected = "a[id*='"+id+"']";
		var selected_id = "a[id*='"+id+"']";
		var selected_dropdown = "select-days-"+findId;

		$( '.selected' ).each(function( index ) {
			$( ".button-select-pack" ).removeClass('selected').addClass('select-pack');
			$( ".button-select-pack" ).removeClass('custom-primary').addClass('dark-light');
			$( ".button-select-pack" ).text('Select Pack');
		});
		$( selected ).text('Selected');
		$( selected ).removeClass('select-pack').addClass('selected');
		$( selected ).removeClass('dark-light').addClass('custom-primary');

		$('#selected-pack').val(findId);
		$('.save-button').html('Proceed to Pay ($'+(total)+')');

		$('.select-days').removeClass('required');
		$( selected ).addClass('required');

		$('.select-days').val('');
		$("."+selected_dropdown).val(selected_days);

		$('#ad_days-'+$( ".button-select-pack" ).attr('id')).addClass('required');

		$(document).find('.select-days').removeClass('error');
		$(document).find('.show-error-parent').children().css('display','none');
	});
</script>
@endsection
