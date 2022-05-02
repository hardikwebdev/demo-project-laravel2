@extends('layouts.frontend.main')

@section('pageTitle', 'demo - Services')
@section('content')
@include('frontend.service.header')

<section class="overview-section">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="popular-grid ">
					<div class="seller pb-2">
						Overview
					</div>
					
					{{ Form::open(['route' => ['create_services'], 'method' => 'POST', 'id' => 'frmServiceOverview','class'=>"mb-10"]) }}
					<input type="hidden" name="current_step" value="1">
					<div class="row">
						<div class="col-lg-6">
							<div class="form-group">
								<label>Service Title <span class="text-danger">*</span></label>
								<textarea class="form-control" rows="6" id="title" name="title" placeholder="Choose a title that's catchy and descriptive" maxlength="80" minlength="15"></textarea>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<label>Service Subtitle</label>
								<textarea class="form-control" rows="4" id="subtitle" name="subtitle" placeholder="Add some short details or selling points" maxlength="80" minlength="15"></textarea>
							</div>
						</div>
						<div class="col-lg-12">
							<div class="row">
								<div class="col-lg-6">
									<div class="form-group">
										<label>Category <span class="text-danger">*</span></label>
										{{ Form::select('category_id', [''=>'Select Category']+$Category,null,['class' => 'form-control','id'=>'category_id', 'data-bv-field' => 'category_id']) }} 
									</div>
								</div>
								<div class="col-lg-6">
									<div class="form-group subcategory-section">
										<label>Sub Category <span class="text-danger">*</span></label>
										<select name="subcategory_id" id="subcategory_id" class="form-control" data-bv-field="subcategory_id">
											<option value="">Select Sub Category</option>
										</select>
									</div>   
								</div>
							</div>
						</div>
						
						<div class="col-lg-12">
							<div class="row">

								@if(Auth::user()->is_premium_seller() == true || Auth::user()->parent_id != 0)
								<div class="col-lg-6">
									<div class="form-group custom-chk">
										<label class="cus-checkmark">  
											<input name="is_recurring" id="is_recurring" type="checkbox" value="1">  
											<span class="checkmark"></span>
										</label>
										<div class="detail-box">
											<lable>Is recurring service</lable>
										</div>
										<div class="recursion_detail" style="display: none;">
										</div>
									</div>
								</div>
								@endif

								{{-- begin :Limit no of orders and allow backorders --}}
								
								<div class="col-lg-3 limit_no_of_orders_div">
									<div class="form-group mb-0">
										<label>Limit Number Of Active Orders</label>
										{{ Form::number('limit_no_of_orders',null,['class' => 'form-control','id'=>'limit_no_of_orders','maxlength'=>4,'step'=>1,'min'=>0]) }} 
									</div>
								</div>
								<div class="col-lg-3 limit_no_of_orders_div">
									<br>
									<div class="form-group mb-0 custom-chk">
										<label class="cus-checkmark">  
											<input name="allow_backorders" id="allow_backorders" type="checkbox" value="1" disabled>
											<span class="checkmark"></span>
										</label>
										<div class="detail-box">
											<lable>Allow Backorders</lable>
										</div>
									</div> 
								</div>

								<div class="col-lg-6 pull-right offset-lg-6 limit_no_of_orders_div">
									<span class="text-info"><b>Note :</b> By limiting the number of active orders on this service, you are only allowing that number of orders to be active at one time.  If you allow backorders, people will still be allowed to place an order however their order will not start until you complete one of your current active orders.</span>
								</div>


								
							</div>
						</div>
						{{-- end :Limit no of orders and allow backorders --}}

						

						{{-- Switch for enable or disable affiliate link for the service in service detail page for premium users only --}}
						@if(Auth::user()->is_premium_seller($parent_uid) == true)
						<div class="col-lg-12">
							<div class="row">
								<div class="col-lg-3">
									<div class="cusswitch col-12">
										<label class="notification " for="notification">Do you want to allow affiliates?</label>
										<label class="pm-switch">
											{{ Form::checkbox('is_affiliate_link',1,'true',["class"=>"switch-input","id"=>"is_affiliate_link"]) }}
											<span class="switch-label" data-on="Yes" data-off="No"></span> 
											<span class="switch-handle"></span>
										</label>
										<div class="affiliate_link_details">
											<p style="font-weight:bolder;margin-top:5px">Affiliate link will be display for this service in detail page.</p>
										</div>
									</div>  
								</div>  

								@if(Auth::user()->is_premium_seller() == true)
								<div class="col-lg-3">
									<div class="cusswitch col-auto">
										<label class="notification " for="notification">Should this service be private or public?</label>
										<label class="pm-switch">
											{{ Form::checkbox('is_private',1,true,["class"=>"switch-input","id"=>"is_private"]) }}
											<span class="switch-label" data-on="Public" data-off="Private"></span> 
											<span class="switch-handle"></span>
										</label>
									</div> 
								</div> 
								@endif
							</div> 
						</div> 
						@endif

						<div class="col-lg-12 create-new-service update-account text-right pt-15">
							<button type="submit" value="Save & Continue" class="btn btn-primary">Save & Continue</button> 
						</div>

					</div>
					{{ Form::close() }}
				</div>
			</div>   
		</div>
	</div>
</section>


@endsection

@section('scripts')
<script src="{{ asset('resources/assets/js/jquery.repeater.min.js') }}"></script>
<script>

	
	$(document).on('keyup mouseup','#limit_no_of_orders',function(){
		if($(this).val() == ''){
			$('#allow_backorders').prop('checked',false);
			$('#allow_backorders').attr('disabled',true);
		}else if($(this).val() > 0){
			$('#allow_backorders').attr('disabled',false);
		}else{
			$('#allow_backorders').prop('checked',false);
			$('#allow_backorders').attr('disabled',true);
		}
	});

	$(document).on('change','#is_recurring',function(){

		if($(this).prop("checked") == true){
            $('.recursion_detail').html('<p style="font-weight:bolder;margin-top:5px">All recurring services are billed on 30 day recurring periods.</p>');
            $('.recursion_detail').css('display','block');
            $('.limit_no_of_orders_div').fadeOut();
        }
        else if($(this).prop("checked") == false){
            $('.recursion_detail').css('display','none');
            $('.limit_no_of_orders_div').fadeIn();
        }

	});
	$(document).on('change','#is_affiliate_link',function(){

		if($(this).prop("checked") == true){
            $('.affiliate_link_details').html('<p style="font-weight:bolder;margin-top:5px">Affiliate link will be display for this service in detail page.</p>');
            $('.affiliate_link_details').css('display','block');
        }
        else if($(this).prop("checked") == false){
            $('.affiliate_link_details').css('display','none');
        }

	});
	$(function () {
		var maxLength = 80;

		$('textarea').keyup(function() {
			var length = $(this).val().length;
			var length = maxLength-length;
			$('#chars').text(length);
		});
		$('#category_id').on('change', function (e) {
			$('#subcategory_id').val('');
			$('#frmServiceOverview').bootstrapValidator('revalidateField', 'subcategory_id');
			e.preventDefault();
			$.ajax({
				url: '{!! route('get_subcategory') !!}',
				type: 'post',
				data: {'_token': _token, 'category_id': this.value},
				dataType: 'json',
				success: function (data, status) {
					$('.subcategory-section').removeClass('hide');
					if(data.category_slug != 'by-us-for-us'){
						$('#subcategory_id').html('<option value="">Select Sub Category</option>');
						for (var i=0; i<data.subcategory.length; i++) {
							var row = $('<option value="'+data.subcategory[i].id+'">' + data.subcategory[i].subcategory_name+ '</option>');
							$('#subcategory_id').append(row);
						}
						$('#frmServiceOverview').bootstrapValidator('revalidateField', 'subcategory_id');
					}else{
						$('#subcategory_id').html('');
						for (var i=0; i<data.subcategory.length; i++) {
							var row = $('<option value="'+data.subcategory[i].id+'">' + data.subcategory[i].subcategory_name+ '</option>');
							$('#subcategory_id').append(row);
						}
						$('#frmServiceOverview').bootstrapValidator('revalidateField', 'subcategory_id');
						$('.subcategory-section').addClass('hide');
					}
				},
				error: function (xhr, desc, err) {
					//console.log(xhr);
					//console.log("Details: " + desc + "\nError:" + err);
				}
			});
		});

	});
	function bindChangeEventOfRepeater(){
		$('.secondary_category_class').unbind('change').bind('change', function (e) {
			console.log(this.value);
			var _this = $(this);
			//e.preventDefault();
			$.ajax({
				url: '{!! route('get_subcategory') !!}',
				type: 'post',
				data: {'_token': _token, 'category_id': this.value},
				dataType: 'json',
				success: function (data, status) {
					var html_str = '<option value="">Select Sub Category</option>';
					var sub_cat_dom = _this.parents('.col-lg-6').next().find('.secondary_subcategory_class');
					for (var i=0; i<data.subcategory.length; i++) {
						html_str += '<option value="'+data.subcategory[i].id+'">' + data.subcategory[i].subcategory_name+ '</option>';
					}
					sub_cat_dom.html(html_str);
					$('#frmServiceOverview').bootstrapValidator('revalidateField', sub_cat_dom);
				},
				error: function (xhr, desc, err) {
				}
			});
		}); 
	}
	$(document).ready(function () {
	  // form repeater jqueryx
	    $.fn.bootstrapValidator.validators.duplicateCategory = {
	        validate: function(validator, $field, options) {
	            return $('#subcategory_id').val() != $field.val();    
	        }
	    };
	    $('#subcategory_id').on('change',function(){
	    	$('.secondary_subcategory_class').each(function(elem){
	    		$('#frmServiceOverview').bootstrapValidator('revalidateField', $(this));
	    	})
	    });
	  $('.repeater-default').repeater({
	  	initEmpty: true,
	    show: function () {
	      $(this).slideDown();
	      bindChangeEventOfRepeater();
	      $('#frmServiceOverview').data('bootstrapValidator').addField($(this).find('.secondary_category_class'),{
	      		validators: {
    				notEmpty: {
    					message: 'Category is required.'
    				}
    			}
	      });
	      $('#frmServiceOverview').data('bootstrapValidator').addField($(this).find('.secondary_subcategory_class'),{
	      		validators: {
    				notEmpty: {
    					message: 'Sub category is required.'
    				},
    				duplicateCategory:{
    					message:'Can not select secondary category pair same as primary.'
    				}
    			}
	      });
	      $('#add_secondery_cat_btn').hide();
	    },
	    hide: function (deleteElement) {
	    	$(this).slideUp(deleteElement);
	    	$('#add_secondery_cat_btn').show();
	      // if (confirm('Are you sure you want to delete this element?')) {
	      //   $(this).slideUp(deleteElement);
	      // }
	    }
	  });
	}); 
</script>
@endsection

@section('css')

<style type="text/css">
.videoWrapperOuter {
	max-width:640px; 
	margin-left:auto;
	margin-right:auto;
}
.videoWrapperInner {
	float: none;
	clear: both;
	width: 100%;
	position: relative;
	padding-bottom: 50%;
	padding-top: 25px;
	height: 0;
}
.videoWrapperInner iframe {
	position: absolute;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
}
@media screen and (max-width: 630px){
	.service-menu .sidebar-nav {
		height: unset !important; 
	}
}
</style>
@endsection