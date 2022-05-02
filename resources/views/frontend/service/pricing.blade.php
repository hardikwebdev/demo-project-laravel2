@extends('layouts.frontend.main')

@section('pageTitle', 'demo - Services')
@section('content')
@include('frontend.service.header')

<section class="pricing-section transactions-table">

	<div class="container">

		@include('layouts.frontend.messages')
		
		{{ Form::open(['route' => ['services_pricing',$Service->seo_url], 'method' => 'POST', 'id' => 'frmPricingService','class'=>"mb-10"]) }}
		<input type="hidden" id="service_price_validate_url" value="{{ route('validate_service_price',[$Service->secret]) }}">
		<input type="hidden" name="preview" value="false" id="preview_input_id">
		<div class="row cus-filter align-items-center">
			<div class="col-md-4 col-5 pad0">
				<div class="transactions-heading"> Scope & Pricing{{isset($extra_index)?$extra_index:''}}</div>
			</div>
			<div class="col-md-8 col-7 pad0">
				<div class="sponsore-form">
					<div class="cusswitch">
						<label class="notification " for="notification">3 Packages</label>
						<label class="cus-switch checkpackage">
							{{ Form::checkbox('three_plan',1,$Service->three_plan,["class"=>"togglethreeplans toggle-input","id"=>"three_plan"]) }}
							<span class="checkslider round"></span>
						</label>
					</div>
				</div>    
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12">
				<div class="table-responsive">
					<table class="table box-border">
						<thead class="thead-default">
							<tr>
								<th class="width180"></th>
								<th>BASIC</th>
								<th>STANDARD</th>
								<th>PREMIUM</th>
							</tr>	
						</thead>
						<tbody>
							<tr>
								<td class="text-center" rowspan="2">
									Description <span class="text-danger">*</span>
								</td>
								<td class="text-center form-group">
									{{Form::textarea('basic[package_name]',isset($Service->basic_plans->package_name)?$Service->basic_plans->package_name:'',["class"=>"form-control","placeholder"=>"Name your package","rows"=>2])}}
								</td>
								<td class="text-center form-group">
									{{Form::textarea('standard[package_name]',isset($Service->standard_plans->package_name)?$Service->standard_plans->package_name:'',["class"=>"form-control threeplans","placeholder"=>"Name your package","rows"=>2, 'data-bv-field' => "standard[package_name]"])}}
								</td>
								<td class="text-center form-group">
									{{Form::textarea('premium[package_name]',isset($Service->premium_plans->package_name)?$Service->premium_plans->package_name:'',["class"=>"form-control threeplans","placeholder"=>"Name your package","rows"=>2, 'data-bv-field' => "premium[package_name]"])}}
								</td>
							</tr>
							<tr>

								<td class="text-center form-group">
									{{Form::textarea('basic[offering_details]',isset($Service->basic_plans->offering_details)?$Service->basic_plans->offering_details:'',["class"=>"form-control","placeholder"=>"Describe the details of your offering","rows"=>2, 'data-bv-field' => "basic[offering_details]"])}}
								</td>
								<td class="text-center form-group">
									{{Form::textarea('standard[offering_details]',isset($Service->standard_plans->offering_details)?$Service->standard_plans->offering_details:'',["class"=>"form-control threeplans","placeholder"=>"Describe the details of your offering","rows"=>2, 'data-bv-field' => "standard[offering_details]"])}}
								</td>
								<td class="text-center form-group">
									{{Form::textarea('premium[offering_details]',isset($Service->premium_plans->offering_details)?$Service->premium_plans->offering_details:'',["class"=>"form-control threeplans","placeholder"=>"Describe the details of your offering","rows"=>2, 'data-bv-field' => "premium[offering_details]"])}}
								</td>
							</tr>
							@if($Service->is_recurring == 1)
							<tr style="display: none">
								<td class="text-center verticlemiddle">
									Delivery Days <span class="text-danger">*</span>
								</td>
								<td class="text-center form-group">
									{{Form::text('basic[delivery_days]',1,["class"=>"form-control", 'data-bv-field' => "basic[delivery_days]"])}}
								</td>
								<td class="text-center form-group">
									{{Form::text('standard[delivery_days]',1,["class"=>"form-control threeplans", 'data-bv-field' => "standard[delivery_days]"])}}
								</td>
								<td class="text-center form-group">
									{{Form::text('premium[delivery_days]',1,["class"=>"form-control threeplans", 'data-bv-field' => "premium[delivery_days]"])}}
								</td>
							</tr>
							@else
							<tr>
								<td class="text-center verticlemiddle">
									Delivery Days <span class="text-danger">*</span>
								</td>
								<td class="text-center form-group">
									{{Form::text('basic[delivery_days]',isset($Service->basic_plans->delivery_days)?$Service->basic_plans->delivery_days:'',["class"=>"form-control", 'data-bv-field' => "basic[delivery_days]"])}}
								</td>
								<td class="text-center form-group">
									{{Form::text('standard[delivery_days]',isset($Service->standard_plans->delivery_days)?$Service->standard_plans->delivery_days:'',["class"=>"form-control threeplans", 'data-bv-field' => "standard[delivery_days]"])}}
								</td>
								<td class="text-center form-group">
									{{Form::text('premium[delivery_days]',isset($Service->premium_plans->delivery_days)?$Service->premium_plans->delivery_days:'',["class"=>"form-control threeplans", 'data-bv-field' => "premium[delivery_days]"])}}
								</td>
							</tr>
							@endif
							
							<tr>
								<td class="text-center verticlemiddle">
									Price <span class="text-danger">*</span>
								</td>
								<td class="text-center form-group">
									{{Form::text('basic[price]',isset($Service->basic_plans->price)?$Service->basic_plans->price:'',["class"=>"form-control", 'data-bv-field' => "basic[price]"])}}
								</td>
								<td class="text-center form-group">
									{{Form::text('standard[price]',isset($Service->standard_plans->price)?$Service->standard_plans->price:'',["class"=>"form-control threeplans", 'data-bv-field' => "standard[price]"])}}
								</td>
								<td class="text-center form-group">
									{{Form::text('premium[price]',isset($Service->premium_plans->price)?$Service->premium_plans->price:'',["class"=>"form-control threeplans", 'data-bv-field' => "premium[price]"])}}
								</td>
							</tr>
							<tr @if($Service->is_recurring == 1) style="display:none" @endif>
								<td class="text-center verticlemiddle">
									Number of Revisions <span class="text-danger">*</span>
								</td>
								<td class="text-center form-group">
									{{Form::text('basic[no_of_revisions]',isset($Service->basic_plans->no_of_revisions)?$Service->basic_plans->no_of_revisions:'',["class"=>"form-control", 'data-bv-field' => "basic[no_of_revisions]"])}}
								</td>
								<td class="text-center form-group">
									{{Form::text('standard[no_of_revisions]',isset($Service->standard_plans->no_of_revisions)?$Service->standard_plans->no_of_revisions:'',["class"=>"form-control threeplans", 'data-bv-field' => "standard[no_of_revisions]"])}}
								</td>
								<td>
									<input type="hidden" name="unlimited_revision" value="false" id="unlimited_revision_id">
									@php
									$premium_rev = '';
									if(isset($Service->premium_plans->no_of_revisions)) {
										$premium_rev = $Service->premium_plans->no_of_revisions;
										if($premium_rev == -1) {
											$premium_rev = 'Unlimited';
										}
									}	
									@endphp
									<div class="text-center form-group">
										{{Form::text('premium[no_of_revisions]',$premium_rev,["class"=>"form-control threeplans", 'data-bv-field' => "premium[no_of_revisions]", "id" => "premium_revisions"])}}
									</div>
									<div class="form-check pt-3 text-right text-center form-group">
										<input type="checkbox" class="form-check-input" id="unlimited_revision" value="{{ ($basic_rev == 'Unlimited' || $standard_rev == 'Unlimited' || $premium_rev == 'Unlimited')?false : true}}">
										<label class="form-check-label" for="unlimited_revision">Unlimited Revisions</label>
									</div>
								</td>
							</tr>
						</tbody>
					</table> 
					
					<input type="hidden" name="current_step" value="2">
				</div>        
			</div>   
		</div>

		{{-- ======================================Review Edition================================================================ --}}
		<div class="row {{($Service->is_recurring == 1)?'hide':''}}">
			<div class="col-lg-12">
				<div class="popular-grid">

					<div class="cusswitch">
						<label class="notification seller">Review Editions </label>
						<label class="cus-switch checkpackage">
							{{ Form::checkbox('is_review_edition',1,$Service->is_review_edition,["class"=>"toggle-input","id"=>"is_review_edition"]) }}
							<span class="checkslider round"></span>
						</label>
					</div> 
					<p>Review editions are services that you offer at a reduced price in exchange for a guaranteed honest review. By selecting this option you agree to give up to 3 review editions of your service at the prices you enter below.</p>
				</div>
			</div>
			<div class="col-lg-12">
				<div class="table-responsive">
					<table class="table box-border {{($Service->is_review_edition == 0)?'hide':''}}" id="tbl-review-edition" cellpadding="0">
						<tbody>
							<tr>
								<td class="text-center verticlemiddle">
								No Of Review Editions <span class="text-danger">*</span>
								</td>
								<td class="text-center form-group">
									<input type="hidden" id="max_review_edition" value="{{$Service->get_no_of_review_editions()}}"/>
									@if(Auth::user()->is_premium_seller($parent_uid))
									<input type="text" name="no_of_review_editions" value="{{($Service->no_of_review_editions)?$Service->no_of_review_editions:''}}" placeholder="No Of Review Editions" class="form-control">
									@else
									<input type="text" name="no_of_review_editions" value="1" placeholder="No Of Review Editions" class="form-control" readonly>
									@endif
								
								</td>
								<td class="text-center" colspan="2">

								</td>
							</tr>
							<tr>
								<td class="text-center verticlemiddle">
									The Price You Want To Sell The Review Edition At <span class="text-danger">*</span>
								</td>
								<td class="text-center form-group">
									<input type="text" name="re_basic_price" value="{{(isset($Service->basic_plans->review_edition_price))?$Service->basic_plans->review_edition_price:''}}" placeholder="Enter Basic Price" class="form-control">
								</td>
								<td class="text-center form-group">
									<input type="text" name="re_standard_price" value="{{ (isset($Service->standard_plans->review_edition_price))?$Service->standard_plans->review_edition_price:''}}" placeholder="Enter Standard Price" class="form-control threeplans">
								</td>
								<td class="text-center form-group">
									<input type="text" name="re_premium_price" value="{{ (isset($Service->premium_plans->review_edition_price))?$Service->premium_plans->review_edition_price:''}}" placeholder="Enter Premium Price" class="form-control threeplans">
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<!-- End Add Extra  -->
			</div>
		</div>

		
		{{-- ======================================Add Extras================================================================ --}}
		<div class="row">
			<div class="col-lg-12">
				<div class="popular-grid ">
					<div class="seller pb-2">
						Add Extras
					</div>
				</div>
			</div>
			<div class="col-lg-12">
				<div class="table-responsive">
					<table class="table box-border custom" id="tbl-questions" cellpadding="0">
						<tbody>
							<tr>
								<td class="form-group">
									<input type="text" name="extra_title" id="extra_title" placeholder="Title" class="form-control title_validation">
								</td>
								<td class="form-group">
									<input type="text" name="extra_description" id="extra_description" placeholder="Description" class="form-control">
								</td>
								<td class="form-group is-required">
									<input type="text" name="extradelivery_days" maxlength="2" id="extradelivery_days" class="form-control numeric custom-dilivery-days-width" placeholder="Delivery Days">
								</td>
								<td class="form-group is-required">
									<input type="text" name="extra_price" class="desimal form-control" id="extra_price" placeholder="Price">
								</td>
								<td class="form-group gradient"><input type="button" class="btn add-btnquestion-button" value="Add"></td>
								<td class="form-group gradient"><input type="button" class="btn cancel-question" value="Cancel"><input type="hidden" id="extra_action" value=""></td>
								<div class="error-message">
									
									<span class="error-ans" style="display: none;color: red">Please fill all the fields</span>
								</div>
							</tr>
						</tbody>
					</table>

					<table class="table box-border table-hover">
						<thead class="thead-default">
							<tr>
								<th>Title</th>
								<th>Description</th>
								<th>Delivery Days</th>
								<th>Price</th>

								<th class="width125">
									<a href="javascript:void(0);" class="button mid-short dark-light add-new-question">Add New</a>
								</th>
							</tr>	
						</thead>
						<tbody id="extra-body">
							@php 
								$extra_index = 0; 
								$service_extras = $Service->extra->where('is_delete',0)->merge($Service->revision_extra);
							@endphp
							@foreach($service_extras as $key => $row)
							<tr>
								<td class="">{{$row->title}}</td>
								<td class="">{{$row->description}}</td>
								<td class="">{{$row->delivery_days}}</td>
								<td class="">{{($row->price)}}</td>
								<td class="text-center">
									<input type="hidden" name="extra[{{$key}}][title]" value="{{$row->title}}">
									<input type="hidden" name="extra[{{$key}}][description]" value="{{$row->description}}">
									<input type="hidden" name="extra[{{$key}}][price]" value="{{$row->price}}">
									<input type="hidden" name="extra[{{$key}}][delivery_days]" value="{{$row->delivery_days}}">

									<a href="javascript:void(0);" class="edit-extra" data-extra_title="{{$row->title}}" data-extra_description="{{$row->description}}" data-extra_price="{{$row->price}}" data-extradelivery_days="{{$row->delivery_days}}">
										<i class="icon-pencil"></i>
									</a> &nbsp; 
									<a href="javascript:void(0);" class="remove-extra">
										<i class="icon-trash"></i>
									</a>
								</td>
							</tr>
							@php $extra_index = $key+1; @endphp
							@endforeach
						</tbody>
					</table>

				</div>
				<!-- End Add Extra  -->

				<div class="clearfix"></div>
				
			</div>

		</div>


		<div class="row">
			<div class="col-lg-12 pricing-btn update-account text-right">
				@if($Service->current_step >= 5 && $Service->uid == Auth::id())
					<button type="button" value="Save & Preview" class="btn btn-primary save_and_preview_btn">Save & Preview</button> 
				@endif
				<button type="submit" class="btn btn-primary save_button">Save &amp; Continue</button> 
			</div>
		</div>
		{{ Form::close() }}

	</div>
	

</section>

@endsection
{{-- ====================================================================================================== --}}

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type="text/javascript">

@if($Service->three_plan==0)
$('.threeplans').attr('disabled', 'disabled'); 
@endif

$('.togglethreeplans').change(function() {
	var validatorPrice = $('#frmPricingService').data('bootstrapValidator');
	if($(this).is(':checked')){

		$('.threeplans').removeAttr('disabled');   
		validatorPrice.enableFieldValidators('standard[package_name]', true);
		validatorPrice.enableFieldValidators('standard[offering_details]', true);
		validatorPrice.enableFieldValidators('standard[delivery_days]', true);
		validatorPrice.enableFieldValidators('standard[price]', true);
		validatorPrice.enableFieldValidators('premium[package_name]', true);
		validatorPrice.enableFieldValidators('premium[offering_details]', true);
		validatorPrice.enableFieldValidators('premium[delivery_days]', true);
		validatorPrice.enableFieldValidators('premium[price]', true);

		validatorPrice.enableFieldValidators('re_standard_price', true);
		validatorPrice.enableFieldValidators('re_premium_price', true);



	} else {

		validatorPrice.enableFieldValidators('standard[package_name]', false);
		validatorPrice.enableFieldValidators('standard[offering_details]', false);
		validatorPrice.enableFieldValidators('standard[delivery_days]', false);
		validatorPrice.enableFieldValidators('standard[price]', false);
		validatorPrice.enableFieldValidators('premium[package_name]', false);
		validatorPrice.enableFieldValidators('premium[offering_details]', false);
		validatorPrice.enableFieldValidators('premium[delivery_days]', false);
		validatorPrice.enableFieldValidators('premium[price]', false);

		validatorPrice.enableFieldValidators('re_standard_price', false);
		validatorPrice.enableFieldValidators('re_premium_price', false);

		//$('.threeplans').val(''); 
		$('.threeplans').attr('disabled', 'disabled'); 
	}		
});

// Review edition checkbox check event
$('#is_review_edition').change(function() {
	if($(this).is(':checked')){
		$('#tbl-review-edition').removeClass('hide');
	}else{
		$('#tbl-review-edition').addClass('hide');
	}

	if($('#three_plan').is(':checked')){
		$('.threeplans').removeAttr('disabled');   
	}

	$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="basic[price]"]'));
	$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="standard[price]"]'));
	$('#frmPricingService').bootstrapValidator('revalidateField', $("input[name='premium[price]']"));

	
	$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="no_of_review_editions"]'));
	$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="re_basic_price"]'));
	$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="re_standard_price"]'));
	$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="re_premium_price"]'));

});

$('input[name="re_basic_price"]').change(function() {
	$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="no_of_review_editions"]'));
	$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="re_basic_price"]'));
	$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="re_standard_price"]'));
	$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="re_premium_price"]'));
});



$('#tbl-questions').hide();
$(document).on('click','.cancel-question',function(){
	$('#tbl-questions').hide();
	$('.error-message').html('');
	$('#extra_title').css("border-color", '#ebebeb');
	$('#extra_description').css("border-color", '#ebebeb');
	$('#extra_price').css("border-color", '#ebebeb');
	$('#extradelivery_days').css("border-color", '#ebebeb');
});
$(document).on('click','.add-new-question',function(){
	$('#tbl-questions').show();
	$('#extra_action').val('Add');
	$('.add-btnquestion-button').attr("value",'Add');
	$('#multiple-answer-clone').css('display','none');
	$('.multiple-answer').css('display','none');
	$('.error-ans').css('display','none');


	clear_extra();
});

function clear_extra(){
	$('#extra_title').val('');
	$('#extra_description').val('');
	$('#extra_price').val('');
	$('#extradelivery_days').val('');
}

var extra_index = {{$extra_index}};

$(document).on('click','.add-btnquestion-button',function(){
	var extra_title = $.trim($('#extra_title').val());
	var extra_description = $.trim($('#extra_description').val());
	var extra_price = $('#extra_price').val();
	var extradelivery_days = $('#extradelivery_days').val();
	var isValidate = true;
	console.log(extra_price);
	console.log(extradelivery_days);

	if($('#extra_action').val()=='Add'){


		$('#extra_title').css("border-color", '#ebebeb');
		if(extra_title==''){
			$('#extra_title').css("border-color", '#8c1616');
			isValidate = false;
		}
		$('#extra_description').css("border-color", '#ebebeb');
		if(extra_description==''){
			$('#extra_description').css("border-color", '#8c1616');
			isValidate = false;
		}


		$('#extra_price').css("border-color", '#ebebeb');
		if(extra_price=='' || !extra_price.match(/^\d+$/)){
			$('#extra_price').css("border-color", '#8c1616');
			isValidate = false;
		}
		$('#extradelivery_days').css("border-color", '#ebebeb');
		if(extradelivery_days=='' || !extradelivery_days.match(/^\d+$/) || parseInt(extradelivery_days) < 1){
			$('#extradelivery_days').css("border-color", '#8c1616');
			isValidate = false;
		}



		if(!isValidate){
			$('.error-ans').css('display','block');
			return false;
		}
		var table_tr = '<tr>'+
		'<td class="">'+extra_title+'</td>'+
		'<td class="">'+extra_description+'</td>'+
		'<td class="">'+extradelivery_days+'</td>'+
		'<td class="">'+extra_price+'</td>'+
		'<td class="text-center">'+

		'<input type="hidden" name="extra['+extra_index+'][title]" value="'+extra_title+'">'+
		'<input type="hidden" name="extra['+extra_index+'][description]" value="'+extra_description+'">'+
		'<input type="hidden" name="extra['+extra_index+'][price]" value="'+extra_price+'">'+
		'<input type="hidden" name="extra['+extra_index+'][delivery_days]" value="'+extradelivery_days+'">'+


		'<a href="javascript:void(0);" class="edit-extra" data-extra_title="'+extra_title+'" data-extra_description="'+extra_description+'" data-extra_price="'+extra_price+'" data-extradelivery_days="'+extradelivery_days+'"><i class="icon-pencil"></i></a> &nbsp; <a href="javascript:void(0);" class="remove-extra"><i class="icon-trash"></i></a>'+
		'</td>'+
		'</tr>';

		$('#extra-body').append(table_tr);
		$('#tbl-questions').hide();
		clear_extra();

		$('.error-message').html('');

	}else{
		var isValidate = true;
		
		$('#extra_title').css("border-color", '#ebebeb');
		if(extra_title==''){
			$('#extra_title').css("border-color", '#8c1616');
			isValidate = false;
		}
		$('#extra_description').css("border-color", '#ebebeb');
		if(extra_description==''){
			$('#extra_description').css("border-color", '#8c1616');
			isValidate = false;
		}

		$('#extra_price').css("border-color", '#ebebeb');
		if(extra_price=='' || !extra_price.match(/^\d+$/)){
			$('#extra_price').css("border-color", '#8c1616');
			isValidate = false;
		}
		$('#extradelivery_days').css("border-color", '#ebebeb');
		if(extradelivery_days=='' || !extradelivery_days.match(/^\d+$/) || parseInt(extradelivery_days) < 1){
			$('#extradelivery_days').css("border-color", '#8c1616');
			isValidate = false;
		}

		if(!isValidate){
			$('.error-ans').css('display','block');
			return false;
		}

		var table_tr = 
		'<td class="">'+extra_title+'</td>'+
		'<td class="">'+extra_description+'</td>'+
		'<td class="">'+extradelivery_days+'</td>'+
		'<td class="">'+extra_price+'</td>'+
		'<td class="text-center">'+

		'<input type="hidden" name="extra['+extra_index+'][title]" value="'+extra_title+'">'+
		'<input type="hidden" name="extra['+extra_index+'][description]" value="'+extra_description+'">'+
		'<input type="hidden" name="extra['+extra_index+'][delivery_days]" value="'+extradelivery_days+'">'+
		'<input type="hidden" name="extra['+extra_index+'][price]" value="'+extra_price+'">'+


		'<a href="javascript:void(0);" class="edit-extra" data-extra_title="'+extra_title+'" data-extra_description="'+extra_description+'" data-extra_price="'+extra_price+'" data-extradelivery_days="'+extradelivery_days+'"><i class="icon-pencil"></i></a> &nbsp; <a href="javascript:void(0);" class="remove-extra"><i class="icon-trash"></i></a>'+
		'</td>';

		$('#extra-body tr.editable-row').html(table_tr);
		$('#tbl-questions').hide();
		clear_extra();
	}

	extra_index++;
});

$(document).on('click', '.edit-extra', function() {
	var extra_title = $(this).attr('data-extra_title');
	var extra_description = $(this).attr('data-extra_description');
	var extra_price = $(this).attr('data-extra_price');
	var extradelivery_days = $(this).attr('data-extradelivery_days');
	
	$('#extra_title').css("border-color", '#ebebeb');
	$('#extra_description').css("border-color", '#ebebeb');
	$('#extra_price').css("border-color", '#ebebeb');
	$('#extradelivery_days').css("border-color", '#ebebeb');
	
	$('.error-ans').css('display','none');
	
	$('.add-btnquestion-button').attr('value','Update');
	$('#tbl-questions').show();
	$('#extra_action').val('edit');



	$('#extra_title').val(extra_title);
	$('#extra_description').val(extra_description);		
	$('#extra_price').val(extra_price);		
	$('#extradelivery_days').val(extradelivery_days);		

	$('#extra-body tr.editable-row').removeClass("editable-row");
	$(this).parents("tr").addClass("editable-row");
});

$(document).on('click', '.remove-extra', function() {
	var $this = $(this);
	bootbox.confirm("Are you sure want to delete this extra option!", 
		function(result){ 
			console.log(result);
			if (result == true) {
				$this.parent().parent().remove();
			}	 
		});
});

$(".checkpackage").click(function(){
	$('.save_button').prop("disabled", false);
});

$('document').ready(function(){
	if($('#premium_revisions').val() == 'Unlimited') {
		$('#premium_revisions').prop( "disabled", true );
		$('#unlimited_revision').prop( "checked", true );
	}
	$('#unlimited_revision').change(function(){
		if($('#unlimited_revision').prop('checked') == true) {
			$('#unlimited_revision_id').val(true);
			$('#premium_revisions').val('Unlimited');
			$('#premium_revisions').prop( "disabled", true );
		} else {
			$('#unlimited_revision_id').val(false);
			$('#premium_revisions').val('');
			$('#premium_revisions').prop( "disabled", false  );
		}
	});

	$("[name='basic[no_of_revisions]']").change(function(){
		$('#frmPricingService').bootstrapValidator('revalidateField', $("[name='standard[no_of_revisions]']"));
		$('#frmPricingService').bootstrapValidator('revalidateField', $("[name='premium[no_of_revisions]']"));
	});
	$("[name='standard[no_of_revisions]']").change(function(){
		$('#frmPricingService').bootstrapValidator('revalidateField', $("[name='standard[no_of_revisions]']"));
		$('#frmPricingService').bootstrapValidator('revalidateField', $("[name='premium[no_of_revisions]']"));
	});
	$('input[name="basic[price]"]').on('change', function(){
		$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="standard[price]"]'));
		$('#frmPricingService').bootstrapValidator('revalidateField', $("input[name='premium[price]']"));
		$('#frmPricingService').bootstrapValidator('revalidateField', $("input[name='re_basic_price']"));
	});
	$("[name='standard[price]']").change(function(){
		$('#frmPricingService').bootstrapValidator('revalidateField', $("[name='premium[price]']"));
		$('#frmPricingService').bootstrapValidator('revalidateField', $("[name='re_standard_price']"));
	});

	$("[name='premium[price]']").change(function(){
		$('#frmPricingService').bootstrapValidator('revalidateField', $("[name='re_premium_price']"));
	});

	$("[name='re_basic_price']").change(function(){
		$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="basic[price]"]'));
	});

	$("[name='re_standard_price']").change(function(){
		$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="standard[price]"]'));
	});

	$("[name='re_premium_price']").change(function(){
		$('#frmPricingService').bootstrapValidator('revalidateField', $('input[name="premium[price]"]'));
	});

});

$('.save_and_preview_btn').on('click', function(){
	$('#preview_input_id').val('true');
	$.ajax({
		type: "POST",
		url: $('#frmPricingService').attr('action'),
		data: $('#frmPricingService').serialize(),		
		success: function (result)
		{
			$('#preview_input_id').val('false');
			if (result.status == 'success') {
				window.open(result.url, "_blank");
			}
		}
	});
	return false;
});
</script>
@endsection
{{-- ====================================================================================================== --}}
