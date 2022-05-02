@extends('layouts.frontend.main')

@section('pageTitle', 'demo - Course')
@section('content')

@include('frontend.course.header')

<section class="overview-section">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="popular-grid ">
					<div class="seller pb-2">
						Overview
					</div>
					@include('layouts.frontend.messages')
					@if(isset($Course))
					{{ Form::open(['route' => ['course.update_overview',$Course->seo_url], 'method' => 'POST', 'id' => 'frmCourseOverview','class'=>"mb-10"]) }}
					<input type="hidden" name="current_step" value="1">
					<input type="hidden" name="preview" value="false" id="preview_input_id">
					@else
					{{ Form::open(['route' => ['course.overview'], 'method' => 'POST', 'id' => 'frmCourseOverview','class'=>"mb-10"]) }}
					<input type="hidden" name="current_step" value="1">
					@endif
					<div class="row">
						<!-- Left column -->
						<div class="col-lg-6">
							<div class="form-group">
								<label>Course Title <span class="text-danger">*</span></label>
								<textarea class="form-control" rows="6" id="title" name="title" placeholder="Choose a title that's catchy and descriptive" maxlength="80" minlength="15">@isset($Course->title){{ $Course->title }}@endisset</textarea>
							</div>
							<div class="form-group">
								<label>Category <span class="text-danger">*</span></label>
								{{ Form::select('category_id', [''=>'Select Category']+$Category, (isset($Course->category_id))? $Course->category_id : null,['class' => 'form-control','id'=>'category_id', 'data-bv-field' => 'category_id',($Course->current_step == 5 && $Course->status != 'denied')?'disabled':'']) }}
							</div>
							<div class="form-group subcategory-section">
								<label>Sub Category <span class="text-danger">*</span></label>
								@if(isset($Course->subcategory_id))									
									{{ Form::select('subcategory_id',[''=>'Select Sub Category']+$Subcategory,$Course->subcategory_id,['class' => 'form-control',id=>"subcategory_id",data-bv-field=>"subcategory_id",($Course->current_step == 5 && $Course->status != 'denied')?'disabled':'']) }}
								@else
									<select name="subcategory_id" id="subcategory_id" class="form-control" data-bv-field="subcategory_id">
									<option value="">Select Sub Category</option>
								</select>
								@endif
							</div>  

							{{-- Course Image Upload --}}
							<div class="upload-file-section">
								<div class="delivery-now">
									<label class="mb-1 font-16 text-color-6">Image  <span class="text-danger">*</span></label>
									<p><em>Note :  Upload image that describe or are related to your course. (Preferable image resolution is 836X484)</em></p>
									
									<div id="course_image_dropzone" class="dropzone dropzone-file-area">
										<div class="fallback form-group">
											<input name="upload_profile" type="file" class="opacity-0"/>
										</div>
										<div class="dz-message needsclick">
											<span class="text">
												<img src="{{url('public/frontend/images/upload-cloud.png')}}" alt="">
												<h1 class="pt-2 mb-1 font-20 text-color-4 font-weight-normal">Drop files here or  <span class="text-color-1">browse</span></h1> 
												<h3 class="font-14 text-color-4 font-weight-normal">Maximum file size 20 MB </h3>
											</span>
										</div>
									</div>

									@if(!isset($Course->latest_media->thumbnail_media_url))
									<div class="form-group">
										<input class="" name="upload_image" type="hidden" id="upload_image">
									</div>
									@endif
								</div>

								<!-- Complete to show screen -->
								<div class="show-media">
									<div class="d-flex mt-2 justify-content-between align-items-center">
										<div class="d-flex align-items-center">
											@if(isset($Course->latest_media->thumbnail_media_url))
												<img src="{{$Course->latest_media->thumbnail_media_url}}" class="img-fluid cart-logo media_show border border-secondary" />
											@else
												<img class="img-fluid cart-logo media_show border border-secondary d-none" />
											@endif
										</div>
									</div>
								</div>
							</div>

						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<label>Course Subtitle</label>
								<textarea class="form-control" rows="6" id="subtitle" name="subtitle" placeholder="Add some short details or course points" maxlength="80" minlength="15">@isset($Course->subtitle){{ $Course->subtitle }}@endisset</textarea>
							</div>

							<div class="popular-grid">
								<div class="cusswitch">
									<label class="notification seller">Pricing </label>
								</div> 
							</div>
							<!-- <div class="form-group subcategory-section col-lg-6 pl-0">
								<label>Lifetime Access Price<span class="text-danger">*</span></label>
								<input type="number" name="price" id="price" placeholder="Enter Price" @isset($Course->lifetime_plans->price) value="{{$Course->lifetime_plans->price}}" @endisset class="form-control">
							</div> -->

							@if(Auth::user()->is_course_training_account() == false)
							<div class="form-group subcategory-section col-lg-6 pl-0">
								<label>Lifetime Access Price<span class="text-danger">*</span></label>
								<div class="input-group col-10 pl-0">
									<div class="input-group-prepend">
										<span class="input-group-text monthly-course-price px-3" id="basic-addon21">$</span>
									</div>
									<input type="number" name="price" id="price" placeholder="Enter Price" aria-describedby="basic-addon21" @isset($Course->lifetime_plans->price) value="{{$Course->lifetime_plans->price}}" @endisset class="form-control">
								</div>
							</div>
							@else
							<div class="form-group subcategory-section col-lg-6 pl-0">
								<label>Free Course</label>
							</div>
							@endif
							
							@if(Auth::user()->is_premium_seller($parent_uid) == true && Auth::user()->is_course_training_account() == false)
							@php
							$is_monthly_course = false;
							if(isset($Course->course_detail) && $Course->course_detail->is_monthly_course == 1){
								$is_monthly_course = true;
							}
							@endphp
							<div class="form-group custom-chk">
								<label class="cus-checkmark">  
									<input name="is_monthly_course" id="is_monthly_course" type="checkbox" value="1" {{($is_monthly_course == true)?'checked':''}}>  
									<span class="checkmark"></span>
								</label>
								<div class="detail-box">
									<label>Offer a monthly recurring subscription for this course?</label>
								</div>

								<div class="input-group col-6 pl-0 {{($is_monthly_course == false)?'d-none':''}}" id="monthly_price_section">
									<div class="input-group-prepend">
										<span class="input-group-text monthly-course-price px-3" id="basic-addon2">$</span>
									</div>
									<input type="number" class="form-control" placeholder="Enter Price" aria-describedby="basic-addon2" id="monthly_price" name="monthly_price"  @isset($Course->monthly_plans->price) value="{{($Course->monthly_plans->price > 0)?$Course->monthly_plans->price:''}}" @endisset >
									<div class="input-group-append">
										<span class="input-group-text monthly-course-price" id="basic-addon2"> / month</span>
									</div>
								</div>
								<div class="recursion_detail" style="display: none;"></div>
							</div>

							<!-- Switch for enable or disable affiliate link for the course in course detail page for premium users only -->
							<div class="cusswitch">
								<label class="notification " for="notification">Do you want to allow affiliates?</label>
								<label class="pm-switch">
									{{ Form::checkbox('is_affiliate_link',1, isset($Course->is_affiliate_link)? $Course->is_affiliate_link : true ,["class"=>"switch-input","id"=>"is_affiliate_link"]) }}
									<span class="switch-label" data-on="Yes" data-off="No"></span> 
									<span class="switch-handle"></span>
								</label>
								<div class="affiliate_link_details" @if(isset($Course->is_affiliate_link) && $Course->is_affiliate_link != 1) style="display: none;" @endif>
									<em>Affiliate link will be display for this course in detail page.</em>
								</div>
							</div>  
							@endif
						</div>

						<div class="col-lg-12 create-new-service update-account text-right pt-15">
							@if($Course->current_step >= 5 && $Course->uid == Auth::id())
								<button type="button" value="Save & Preview" class="btn bg-primary-blue save_and_preview_btn">Save & Preview</button> 
							@endif
							<button type="submit" value="Save & Continue" class="btn bg-primary-blue save_and_continue"> Save & Continue</button> 
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
<link href="{{url('public/frontend/assets/css/dropzone.css')}}" rel="stylesheet">
@endsection

@section('scripts')
<script src="{{url('public/frontend/assets/js/dropzone.js')}}" type="text/javascript"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.2/jquery.ui.touch-punch.min.js"></script>
<script>
	$(document).on('change','#is_affiliate_link',function(){
		if($(this).prop("checked") == true){
            $('.affiliate_link_details').html('<em>Affiliate link will be display for this course in detail page.</em>');
            $('.affiliate_link_details').css('display','block');
        }
        else if($(this).prop("checked") == false){
            $('.affiliate_link_details').css('display','none');
        }
	});

	$(document).on('change','#is_monthly_course',function(){
		$('#frmCourseOverview').bootstrapValidator('revalidateField', 'monthly_price');
		if($(this).prop("checked") == true){
			$('.recursion_detail').html('<em>All monthly courses are billed on 30 day recurring periods.</em>');
			$('.recursion_detail').css('display','block');
			$('#monthly_price_section').removeClass('d-none');
		}
		else if($(this).prop("checked") == false){
			$('.recursion_detail').css('display','none');
			$('#monthly_price_section').addClass('d-none');
		}
	});

	$(document).on('change','#category_id',function(e){
		$('#subcategory_id').val('');
		$('#frmCourseOverview').bootstrapValidator('revalidateField', 'subcategory_id');
		e.preventDefault();
		$.ajax({
			url: "{!! route('get_subcategory') !!}",
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
					$('#frmCourseOverview').bootstrapValidator('revalidateField', 'subcategory_id');
				}else{
					$('#subcategory_id').html('');
					for (var i=0; i<data.subcategory.length; i++) {
						var row = $('<option value="'+data.subcategory[i].id+'">' + data.subcategory[i].subcategory_name+ '</option>');
						$('#subcategory_id').append(row);
					}
					$('#frmCourseOverview').bootstrapValidator('revalidateField', 'subcategory_id');
					$('.subcategory-section').addClass('hide');
				}
			},
			error: function (xhr, desc, err) {

			}
		});
	});

	Dropzone.autoDiscover = false;
	$(document).ready(function () {
	  // form repeater jqueryx
	    $.fn.bootstrapValidator.validators.duplicateCategory = {
	        validate: function(validator, $field, options) {
	            return $('#subcategory_id').val() != $field.val();    
	        }
	    };
	    $('#subcategory_id').on('change',function(){
	    	$('.secondary_subcategory_class').each(function(elem){
	    		$('#frmCourseOverview').bootstrapValidator('revalidateField', $(this));
	    	})
	    });

		$('.save_and_preview_btn').on('click', function(){
			$('#preview_input_id').val('true');
			$('.save_and_preview_btn').prop('disabled',true);
			$('.save_and_continue').prop('disabled',true);
			$.ajax({
				type: "POST",
				url: $('#frmCourseOverview').attr('action'),
				data: $('#frmCourseOverview').serialize(),		
				success: function (result)
				{
					$('.save_and_preview_btn').prop('disabled',false);
					$('.save_and_continue').prop('disabled',false);
					$('#preview_input_id').val('false');
					if (result.status == 'success') {
						window.open(result.url, "_blank");
					}
				}
			});
			return false;
		});
		var is_delete_file = true;
		$("#course_image_dropzone").dropzone({
	        url: "{{route('course.content.upload_media')}}",
			parallelUploads: 1,
			maxFilesize: 20,
			maxFiles:1,
			dictFileTooBig: 'File is bigger than 100MB',
			paramName: "upload_profile",
			acceptedFiles: ".jpeg,.jpg,.png",
			// autoProcessQueue : false,
			addRemoveLinks: true,
			init: function() {
				myDropzone = this;

				myDropzone.on("sending", function(file, xhr, formData){
                   formData.append("_token", _token);
                });
				 
				myDropzone.on("addedfile", function(file) {
					is_delete_file = true;
					if (!file.type.match(/image.*/)) {
						if(file.type.match(/application.zip/)){
							myDropzone.emit("thumbnail", file, "path/to/img");
						} else {
							myDropzone.emit("thumbnail", file, "path/to/img");
						}
					}
				});
				myDropzone.on("maxfilesexceeded", function(file) {
					myDropzone.removeAllFiles();
					myDropzone.addFile(file);
				});
			},
			removedfile: function(file) {
				if($('#upload_image').length > 0){
					$('#upload_image').val('');
					$('#frmCourseOverview').bootstrapValidator('revalidateField', 'upload_image');
				}
				$('.dz-preview').remove();
				if(is_delete_file == true){
					$.ajax({
						url: "{{route('course.content.delete_media')}}",
						type: 'DELETE',
						data: {
							"_token": _token,
						},
						success: function (result)
						{
							
						}
					});
				}
			},
			error: function(file, response) {
				if($.type(response) === "string")
					var message = response;
				else
					var message = response.message;

				file.previewElement.classList.add("dz-error");
				_ref = file.previewElement.querySelectorAll("[data-dz-errormessage]");
				_results = [];
				for (_i = 0, _len = _ref.length; _i < _len; _i++) {
					node = _ref[_i];
					_results.push(node.textContent = message);
				}

				return _results;
			},
			success: function(file,response) {
				is_delete_file = false;
				this.removeFile(file);
				if(response.success == true){
					$('#upload_image').val('1');
					$('.media_show').removeClass('d-none').attr('src',response.source_url_thumb);
					$('#frmCourseOverview').bootstrapValidator('revalidateField', 'upload_image');
				}else{
					_ref = file.previewElement.querySelectorAll("[data-dz-errormessage]");
					_results = [];
					for (_i = 0, _len = _ref.length; _i < _len; _i++) {
						node = _ref[_i];
						if(response.message == 'undefined'){
							_results.push(node.textContent = "Something went wrong. Please try again.");
						}else{
							_results.push(node.textContent = response.message);
						}
					}
					alert_error(response.message);
					myDropzone.removeAllFiles();
				}
			}
	    });

	});

	/* Upload image */

</script>
@endsection