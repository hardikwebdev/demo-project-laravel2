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
					
					{{ Form::open(['route' => ['overview_update',$Service->seo_url], 'method' => 'POST', 'id' => 'frmServiceOverviewUpdate','class'=>"mb-10"]) }}
					<input type="hidden" name="current_step" value="1">
					<input type="hidden" name="preview" value="false" id="preview_input_id">
					<div class="row">
						<div class="col-lg-6">
							<div class="form-group">
								<label>Service Title <span class="text-danger">*</span></label>
								<textarea class="form-control" rows="6" id="title" name="title" placeholder="Choose a title that's catchy and descriptive" maxlength="80" minlength="15">{{$Service->title}}</textarea>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<label>Service Subtitle</label>
								<textarea class="form-control" rows="4" id="subtitle" name="subtitle" placeholder="Add some short details or selling points" maxlength="80" minlength="15">{{$Service->subtitle}}</textarea>
							</div>
						</div>

						<div class="col-lg-12">
							<div class="row">
								<div class="col-lg-6">
									<div class="form-group">
										<label>Category
											<span class="text-danger">*</span>
										</label>
										{{ Form::select('category_id', [''=>'Select Category']+$Category,$Service->category_id,['class' => 'form-control','id'=>'category_id', 'data-bv-field' => 'category_id',($Service->current_step == 5 && $Service->status != 'denied')?'disabled':'']) }}
									</div>
								</div>
								<div class="col-lg-6">
									<div class="form-group subcategory-section {{($category_slug == 'by-us-for-us')?'hide':''}}">
										<label>Sub Category 
											<span class="text-danger">*</span>
										</label>
										{{ Form::select('subcategory_id', [''=>'Select Sub Category']+$Subcategory,$Service->subcategory_id,['class' => 'form-control',id=>"subcategory_id",data-bv-field=>"subcategory_id",($Service->current_step == 5 && $Service->status != 'denied')?'disabled':'']) }}
									</div>
								</div>
							</div>
						</div>

						@php
						$is_display_message = false;
						@endphp

						{{-- begin :Limit no of orders and allow backorders --}}
						@if($Service->is_recurring==0)  
						<div class="col-lg-12">
							<div class="row">
								<div class="col-lg-3">
									<div class="form-group mb-0">
										<label>Limit Number Of Active Orders</label>
										{{ Form::number('limit_no_of_orders',($Service->limit_no_of_orders)?$Service->limit_no_of_orders:'',['class' => 'form-control','id'=>'limit_no_of_orders','maxlength'=>4,'step'=>1,'min'=>0]) }} 
										
									</div>
								</div>
								<div class="col-lg-3">
									<br>
									<div class="form-group mb-0 custom-chk">
										@php
										$make_disable = 'disabled';
										$make_checked = '';
										if($Service->limit_no_of_orders){
											$make_disable = '';
											if($Service->allow_backorders){
												$make_checked = 'checked';
											}
										}
										@endphp


										<label class="cus-checkmark">  
											<input name="allow_backorders" id="allow_backorders" type="checkbox" value="1" {{$make_disable}} {{$make_checked}}>
											<span class="checkmark"></span>
										</label>
										<div class="detail-box">
											<lable>Allow Backorders</lable>
										</div>
									</div> 
								</div>

								<div class="col-lg-12">
									<span class="text-info"><b>Note :</b> By limiting the number of active orders on this service, you are only allowing that number of orders to be active at one time.  If you allow backorders, people will still be allowed to place an order however their order will not start until you complete one of your current active orders.</span>
								</div>
							</div>
						</div>
						@endif

						{{-- end :Limit no of orders and allow backorders --}}

						@if(Auth::user()->is_premium_seller($parent_uid) == true)
						<div class="col-lg-6">
							<br>
							<div class="form-group custom-chk" style="display: none">
								<label class="cus-checkmark">

									@if($Service->is_recurring==1)  
									<input name="is_recurring" id="is_recurring" type="checkbox" value="1" checked="true">  
									<span class="checkmark"></span>
									@else
									<input name="is_recurring" id="is_recurring" type="checkbox" value="0">  
									<span class="checkmark"></span>
									@endif
								</label>
								<div class="detail-box">
									<lable>Is recurring service</lable>
								</div>
								<div class="recursion_detail" style="display: none;">
								</div>
							</div>
							<div class="detail-box">
							@if($Service->is_recurring==1)
								<lable class="text-info">This is recurring service</lable>
							@endif
							</div>
							<br>
							<!-- Switch for enable or disable affiliate link for the service in service detail page for premium users only -->
							<div class="row">
								<div class="cusswitch col-12 col-lg-6">
									<label class="notification " for="notification">Do you want to allow affiliates?</label>
									<label class="pm-switch">
										{{ Form::checkbox('is_affiliate_link',1,$Service->is_affiliate_link,["class"=>"switch-input","id"=>"is_affiliate_link"]) }}
										<span class="switch-label" data-on="Yes" data-off="No"></span> 
										<span class="switch-handle"></span>
									</label>
									<div class="affiliate_link_details" @if($Service->is_affiliate_link != 1) style="display: none;" @endif>
										<p style="font-weight:bolder;margin-top:5px">Affiliate link will be display for this service in detail page.</p>
									</div>
								</div>  
								<div class="cusswitch col-auto">
									<label class="notification " for="notification">Should this service be private or public?</label>
									<label class="pm-switch">
										{{ Form::checkbox('is_private',1,($Service->is_private)?0:1,["class"=>"switch-input","id"=>"is_private"]) }}
										<span class="switch-label" data-on="Public" data-off="Private"></span> 
										<span class="switch-handle"></span>
									</label>
								</div> 
							</div>
						</div>
						@endif

						<div class="col-lg-12 create-new-service update-account text-right pt-15">
							@if($Service->current_step >= 5 && $Service->uid == Auth::id())
								<button type="button" value="Save & Preview" class="btn btn-primary save_and_preview_btn">Save & Preview</button> 
							@endif
							<button type="submit" value="Save & Continue" class="btn btn-primary">Save & Continue</button> 
						</div>
						<div class="col-lg-12  text-right pt-10">
							@if($is_display_message == true)
							<span class="text-danger">You can't update category and sub category, contact administrator to update category.</span>
							@endif
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
<!-- <script src="{{front_asset('js/vendor/jquery.xmtab.min.js')}}"></script>
	<script src="{{front_asset('js/post-tab.js')}}"></script> -->
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
		$(document).on('change','#is_recurring',function() {
			if($(this).prop("checked") == true){
                $('.recursion_detail').html('<p style="font-weight:bolder;margin-top:5px">All recurring services are billed on 30 day recurring periods.</p>');
                $('.recursion_detail').css('display','block');
            }
            else if($(this).prop("checked") == false){
                $('.recursion_detail').css('display','none');
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
				$('#frmServiceOverviewUpdate').bootstrapValidator('revalidateField', 'subcategory_id');
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
							$('#frmServiceOverviewUpdate').bootstrapValidator('revalidateField', 'subcategory_id');
						}else{
							$('#subcategory_id').html('');
							for (var i=0; i<data.subcategory.length; i++) {
								var row = $('<option value="'+data.subcategory[i].id+'">' + data.subcategory[i].subcategory_name+ '</option>');
								$('#subcategory_id').append(row);
							}
							$('#frmServiceOverviewUpdate').bootstrapValidator('revalidateField', 'subcategory_id');
							$('.subcategory-section').addClass('hide');
						}
					},
					error: function (xhr, desc, err) {
						//console.log(xhr);
						//console.log("Details: " + desc + "\nError:" + err);
					}
				});
			});
			$('.primary_category_class').on('change', function (e) {
				e.preventDefault();
				var _this = $(this);
				$.ajax({
					url: '{!! route('get_subcategory') !!}',
					type: 'post',
					data: {'_token': _token, 'category_id': this.value},
					dataType: 'json',
					success: function (data, status) {
						var html_str = '<option value="">Select Sub Category</option>';
						var sub_cat_dom = _this.parents('.col-lg-6').next().find('.primary_subcategory_class');
						for (var i=0; i<data.subcategory.length; i++) {
							html_str += '<option value="'+data.subcategory[i].id+'">' + data.subcategory[i].subcategory_name+ '</option>';
						}
						sub_cat_dom.html(html_str);
						$('#frmServiceOverviewUpdate').bootstrapValidator('revalidateField', sub_cat_dom);
					},
					error: function (xhr, desc, err) {
						console.log(xhr);
						console.log("Details: " + desc + "\nError:" + err);
					}
				});
			}); 
		}); 
		function bindChangeEventOfRepeater(){
			$('.secondary_category_class').unbind('change').bind('change', function (e) {
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
						$('#frmServiceOverviewUpdate').bootstrapValidator('revalidateField', sub_cat_dom);
					},
					error: function (xhr, desc, err) {
					}
				});
			}); 
		}
		$(document).ready(function () {

			$.fn.bootstrapValidator.validators.duplicateCategory = {
				validate: function(validator, $field, options) {
					return $('#subcategory_id').val() != $field.val();    
				}
			};
			 
			if($('.primary_category_class').length > 1){
				$('#add_secondery_cat_btn').hide();
			}
			$('.delete_category').click(function(){
				var _this = $(this);
				$.ajax({
					url: '{!! route('delete_category') !!}',
					type: 'post',
					data: {'_token': _token, 'c_id': $(this).data('id')},
					dataType: 'json',
					success: function (data, status) {
						_this.parents('.col-lg-6').parent().parent().remove();
						$('#add_secondery_cat_btn').show();	
					},
					error: function (xhr, desc, err) {
					}
				});
			})
		    $('#subcategory_id_0').on('change',function(){
		    	$('.secondary_subcategory_class').each(function(elem){
		    		$('#frmServiceOverviewUpdate').bootstrapValidator('revalidateField', $(this));
		    	});
		    	$('#frmServiceOverviewUpdate').bootstrapValidator('revalidateField', $('#subcategory_id_1'));
		    });
			// form repeater jquery
			$('.repeater-default').repeater({
			  	initEmpty: true,
			    show: function () {
			      $(this).slideDown();
			      bindChangeEventOfRepeater();
			      $('#frmServiceOverviewUpdate').data('bootstrapValidator').addField($(this).find('.secondary_category_class'),{
			      		validators: {
		    				notEmpty: {
		    					message: 'Category is required.'
		    				}
		    			}
			      });
			      $('#frmServiceOverviewUpdate').data('bootstrapValidator').addField($(this).find('.secondary_subcategory_class'),{
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
		$('.save_and_preview_btn').on('click', function(){
			$('#preview_input_id').val('true');
			$.ajax({
				type: "POST",
				url: $('#frmServiceOverviewUpdate').attr('action'),
				data: $('#frmServiceOverviewUpdate').serialize(),		
				success: function (result)
				{
					$('#preview_input_id').val('false');
					if (result.status == 'success') {
						window.open(result.url, "_blank");
						if(result.current_url.length > 0) {
							window.location.href = result.current_url;
						}
					}
				}
			});
			return false;
		});
	</script>
	@endsection