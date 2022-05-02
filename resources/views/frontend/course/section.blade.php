@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Course Section')
@section('content')

@include('frontend.course.header')

<section class="transactions-table pad-t4">
	<div class="container">
        @include('layouts.frontend.messages')
		<div class="row cus-filter align-items-center">
			<div class="col-md-7 col-12 pad0">
				<div class="transactions-heading"><span class="text-capitalize">{{$Course->title}}</span>
				</div>
			</div>
			<div class="col-md-5 col-12 pad0">
				<div class="sponsore-form service-filter">
					<button type="button" class="btn bg-primary-blue text-white font-13 py-2 px-3 border-radius-6px mr-2" data-toggle="modal" data-target="#add_content_modal">Add New Section</button>
					@if($Course->current_step >= 5 && $Course->uid == Auth::id())
						<a href="{{route('course_details',[$Course->user->username,$Course->seo_url])}}#content" class="btn bg-primary-blue text-white font-13 py-2 px-3 border-radius-6px mr-2" target="_blank">Preview</a>
					@endif
                    @if($Course->current_step >= 5)
                    <a href="{{route('course.publish',$Course->seo_url)}}"><button type="button" class="btn bg-primary-blue text-white font-13 py-2 px-3 border-radius-6px mr-2">Publish</button></a>
                    @endif
				</div>    
			</div>
		</div>
	</div>        
</section> 

<section class="requirement-section">
	<div class="container pb-5">
		<div class="row">
			<div class="col-lg-12">
				<div class="popular-grid cart_page">
					@if(count($course_sections) > 0)
					<div class="seller pb-2">
						Content
					</div>
					@endif
					<div class="row">
						<div class="col-md-12">
							@if(count($course_sections) > 0)
							<ul id="courseContentList" class="pl-0">
								@foreach ($course_sections as $value)
								<li id="{{$value->secret}}" class="mb-2">
									<div class="card">
										<div class="card-header d-flex justify-content-between py-2">
											<div class="d-flex view-content" id="view-content-{{$value->secret}}">
												<button class="btn btn-sm shadow-none panel-title px-0 text-color-2" id="title-{{$value->secret}}" type="button" data-toggle="collapse" data-target="#collapse-{{$value->secret}}" aria-expanded="{{(@session('new_section') && session('new_section') == $value->secret)?'true':'false'}}" aria-controls="collapseOne">
													{{ $value->name }}
												</button>
												<div class="ml-2 edit-course-content">
													@if($value->is_approve == 0)
													<button class="btn btn-sm shadow-none edit_content_btn" title="Edit" type="button" data-id="{{$value->secret}}">
														<i class="fa fa-pencil text-color-6 font-16 align-middle" aria-hidden="true"></i>
													</button>
													@endif

													<button class="btn btn-sm shadow-none delete-content" title="Delete" type="button" data-url="{{route('course.section.delete',[$Course->seo_url,$value->secret])}}" data-id="{{$value->secret}}">
														<i class="far fa-trash-alt text-color-6 font-16 align-middle" aria-hidden="true"></i>
													</button>
												</div>
											</div>
											@if($value->is_approve == 0)
											<div class="update-content" id="update-content-{{$value->secret}}">
												{{ Form::open(['route' => ['course.section.create',$Course->seo_url], 'method' => 'POST', 'class'=>'frmUpdateCourseContent d-flex']) }}
													<input type='hidden' name='id' value="{{$value->secret}}">
													<input class="form-control update-content-input update-input-{{$value->secret}}" name="name" value="{{$value->name}}" autocomplete="off">
													<button class="btn-secondary btn-sm btn text-white font-13 py-0 px-3 border-radius-6px ml-2" type="submit">Update</button>
													<button class="btn-default btn-sm btn font-13 py-0 px-3 text-color-2 border-radius-6px ml-2 cancel-update-content" data-id="{{$value->secret}}" data-value="{{$value->name}}" type="button">Cancel</button>
												{{ Form::close() }}
											</div>
											@endif
											<div>
												@if(count($course_sections) > 1)
													<img src="{{url('public/frontend/images/more-vertical.png')}}" class="img-fluid drag-icon">
												@endif
											</div>
										</div>
										<div id="collapse-{{$value->secret}}" class="collapse {{(@session('new_section') && session('new_section') == $value->secret)?'show':''}}" aria-labelledby="heading-{{$value->secret}}" data-parent="#courseContentList">
											<div class="card-body">
												<div class="mb-2">
													<button type="button" class="btn bg-primary-blue text-white font-13 py-2 px-3 border-radius-6px mr-2 float-right get-lecture-form" id="get-lecture-form-{{$value->secret}}" data-id="{{$value->secret}}" data-url="{{route('course.content.get_form',['create',$Course->seo_url,$value->secret])}}">Add New Content <i class="fa fa-spin fa-spinner d-none"></i></button>
													<div class="clearfix"></div>
													{{-- END Add lecture div --}}
													<div class="load-lecture-list" id="lecture-list-{{$value->secret}}">
														@php 
														$contentMedia = $value->content_medias; $content_secret = $value->secret; $seo_url = $Course->seo_url;
														@endphp
														
														@include('frontend.course.include.content_list')
													</div>
												</div>
											</div>
										</div>
										{{-- END Course Section Div --}}
									</div>
								</li>
								@endforeach
							</ul>
							@else
							<div class="text-center font-18">No course section available</div>
							@endif
						</div>
						
					</div>
				</div>   
			</div>
        </div>
    </div>
	<div id="scrollToDiv"></div>
</section>

{{-- Add Section Modal --}}
<div id="add_content_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Section</h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            {{ Form::open(['route' => ['course.section.create',$Course->seo_url], 'method' => 'POST', 'id' => 'frmCourseContent']) }}
			<input type="hidden" name="type" value="new">
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label>Title <span class="text-danger">*</span></label>
                            {{Form::text('name',null,["class"=>"form-control required","placeholder"=>"Enter Title","autocomplete"=>"off","id"=>"name"])}}
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="col-md-3">
                    <button type="submit" class="btn send-request-buttom float-right">Add Section</button>
                </div>
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>
<!-- Add Lectures Modal -->
<div id="add_lecture_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title lecture-title">Add New Content</h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            <div id="addEditLecture" class="pt-3 pb-4">
			</div>
        </div>
    </div>
</div>

<!-- Video Description Modal -->
<div id="video_description_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">	
            <div class="modal-header">
                <h5 class="modal-title">Video Description</h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            <div id="video_description" class="p-3 white-space-pre-line">
			</div>
        </div>
    </div>
</div>

<!-- Course downaloadabale resourses Modal -->
<div id="downloadable_content_model" data-focus="false" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content border-radius-15px">
            <div class="modal-header modal-header-border-none border-0">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body pt-0 border-0 px-3 px-md-5">
				<h3 class="font-weight-bold font-20 text-color-6 text-center">Downloadable Resources</h3>
				<div class="delivery-now mt-4">
					{{ Form::open(['route' => ['upload_downloadable_file'], 'method' => 'POST','class' => 'dropzone dropzone-file-area','id'=>'dropzoneForm','files'=>'true']) }}
					<input type="hidden" name="content_media_id" id="hidden_content_media_id">
					<div class="fallback">
						<input name="upload_downloadable_file" type="file" id="file2" class="hide" />
					</div>
					{{Form::close()}}
				</div>
				<div class="load_course_resourse_content mb-4">
					
				</div>
			</div>
		</div>
	</div>
</div>
<!-- END Course downaloadabale resourses Modal -->

@endsection

@section('css')
<link href="{{front_asset('dropzone/dropzone.min.css')}}" rel="stylesheet">
@endsection

@section('scripts')
<script src="{{front_asset('dropzone/dropzone.min.js')}}" type="text/javascript"></script>
<script src="{{url('public/frontend/assets/js/jquery.inputmask.bundle.js')}}" type="text/javascript"></script>
<script>
	var articles = '';
	$(document).ready(function() {

		@if(session('new_section'))
		setTimeout(function(){
			$('html, body').animate({
				scrollTop: $("#{{session('new_section')}}").offset().top - 150
			},800);
		},500);
		@endif
		
	});
	
	/* Edit Section JS */ 
	$('.cancel-update-content').on('click', function() {
		var id = $(this).data('id');
		var value = $(this).data('value');
		$('.frmUpdateCourseContent').bootstrapValidator('resetForm', true);
		$('.update-input-'+id).val(value);
		$('.view-content').removeClass('d-flex').addClass('d-flex').show();
        $('.update-content').hide();
	});

	/* Edit Section JS */ 
	$('.edit_content_btn').on('click', function() {
        $('.view-content').addClass('d-flex').show();
        $('.update-content').hide();

		var id = $(this).data('id');
		$('#view-content-'+id).removeClass('d-flex').hide();
		$('#update-content-'+id).show();
    });
	
	/* Delete Section JS */
	$('.delete-content').on('click', function(){
        var id = $(this).data('id');
        var url = $(this).data('url');
        bootbox.confirm("Are you sure you want to delete this course section?", function(result){ 
			if (result) {
				var _token = "{{ csrf_token() }}";
				$.ajax({
					url: url,
					method: "DELETE",
					data: {'_token': _token},
					dataType: "json",
					success: function(data){
						if(data.status == true){
							alert_success(data.message);
							$('#'+id).remove();
						}else if(data.status == false && data.url != null){
							window.location.href = data.url;
						}else{
							alert_error(data.message);
						}
					},
					error: function(){
						alert('Something went wrong.');
						location.reload();
					}
				});
			}	 
		});
    });

	/* Shorting Section Ordering JS */
	@if(count($course_sections)>1)
	$( "#courseContentList" ).sortable({
		connectWith: "#courseContentListcolumn li",
		handle: ".card-header",
		//cancel: ".portlet-toggle",
		//placeholder: "drop-placeholder",
		update: function(event, ui) {
			$.ajax({
				url: "{{route('course.section.change_ordering')}}",
				method: "POST",
				data: {'_token':_token , 'id' : $(this).sortable("toArray")},
				dataType: "json",
				success: function(data){
					if(data.success == true){
						alert_success(data.message);
					}
				}
			});
		}
	});
	@endif
	
	/* Shorting Content Media JS */ 
	update_shorting();
	function update_shorting(){
		$( ".content_media_shorting" ).sortable({
			update: function(event, ui) {
				$.ajax({
					url: "{{route('course.section.change_ordering')}}",
					method: "POST",
					data: {'_token':_token , 'id' : $(this).sortable("toArray"), 'type': 'content_media'},
					dataType: "json",
					success: function(data){
						if(data.success == true){
							alert_success(data.message);
						}
					}
				});
			}
		});
	}

	/* Delete content JS */
	$(document).on('click','.delete-lecture',function(e){
        var id = $(this).data('id');
        bootbox.confirm("Are you sure you want to delete this content?", function(result){ 
			if (result) {
				var _token = "{{ csrf_token() }}";
				$.ajax({
					url: "{{route('course.content.delete')}}",
					method: "DELETE",
					data: {'_token': _token,'id':id},
					dataType: "json",
					success: function(data){
						if(data.status == true){
							alert_success(data.message);
							$('.lecture-media-'+id).remove();
						}else if(data.status == false && data.url != null){
							window.location.href = data.url;
						}else{
							alert_error(data.message);
						}
					},
					error: function(){
						alert('Something went wrong.');
						// location.reload();
					}
				});
			}	 
		});
    });

	$(document).on('click','.get-lecture-form, .edit_lecture_btn',function(e){
		var id = $(this).data('id');
		var url = $(this).data('url');
		var _token = "{{ csrf_token() }}";
		var $this = $(this);
		var lecture_id = $(this).data('lecture_id');
		if(lecture_id == undefined){
			lecture_id = 0;
			$('#get-lecture-form-'+id).children().removeClass('d-none');
			$('#get-lecture-form-'+id).prop('disabled',true);
			$('.lecture-title').html('Add New Content');
		}else{
			$(this).prop('disabled',true);
			$(this).children().removeClass('d-none');
			//$('#get-lecture-form-'+id).hide().prop('disabled',true);
			$('.lecture-title').html('Edit Content');
		}

		$.ajax({
			url: url,
			method: "POST",
			data: {'_token': _token, content_secret:lecture_id},
			dataType: "json",
			success: function(data){
				if(data.status == true){
					//$('#addLecture-'+data.secret).html(data.html);
					$('#addEditLecture').html(data.html);
					
					setTimeout(function(){
						$this.prop('disabled',false);
						//$this.hide();
						//$this.children().addClass('d-none');
						$this.children('.fa-spin').addClass('d-none');
						$('#add_lecture_modal').modal('show');
						//$('#addLecture-'+data.secret).collapse('show');
					}, 500);
					upload_file_dropzone();
					upload_article();
					update_shorting();
				}else{
					reinitialise_form();
					$this.prop('disabled',false);
					alert_error('Something went wrong.');
				}
			},
			error: function(){
				reinitialise_form();
				$this.prop('disabled',false);
				alert_error('Something went wrong.');
			}
		});
	});

	$(document).on('click','.cancel-lecture-form',function(e){
		$('#get-lecture-form-'+$(this).data('id')).prop('disabled',false).show();
		$('#get-lecture-form-'+$(this).data('id')).children().addClass('d-none');
		$('#add_lecture_modal').modal('hide');
		//$('#addLecture-'+$(this).data('id')).collapse('hide');
		//$('#addLecture-'+$(this).data('id')).html('');
		$('.edit_lecture_btn').children('.fa-spin').addClass('d-none');
		$('.edit_lecture_btn').prop('disabled',false).show();
	});

	$(document).on('keyup','#store_lecture_name',function(e){
		if($(this).val().length) {
			$('.lecture_name_error').html('');
			$('.submit-lecture-btn').prop('disabled',false);
			$('.cancel-lecture-form').prop('disabled',false);
		}else{
			$('.lecture_name_error').html('This field is required.');
		}
	});

	$(document).on('keyup','.estimate-times',function(e){
		if($(this).val().length) {
			$('.duration-error').html('');
			$('.submit-lecture-btn').prop('disabled',false);
			$('.cancel-lecture-form').prop('disabled',false);
		}else{
			$('.duration-error').html('Estimate time is required.');
		}
	});

	$(document).on('blur','#store_lecture_name',function(e){
		var length = $.trim(this.value).length;
		if(length == 0) {
			$(this).val($.trim(this.value));
		}
	});

	/* Submit content form */ 
	$(document).on('submit','#lecture_store_form',function(e){
		e.preventDefault();
		$('#file_upload_error_msg').html('');
		$('.lecture_name_error').html('');
		$('.media-upload-form').removeClass('cust-dropzone-error');
		$('.duration-error').html('');
		var is_valid = true;
		if($('#store_lecture_name').val().length == 0){
			$('#store_lecture_name').focus();
			$('.lecture_name_error').html('Title is required.');
			is_valid = false;
		}

		$('.submit-lecture-btn').prop('disabled',true);
		//$('.cancel-lecture-form').prop('disabled',true);

		var selected_media_type = $("input[name='media_type']").val();
		if(selected_media_type == 'video'){
			if($('#upload_media').val() == 0){
				$('#file_upload_error_msg').html('Please upload video.');
				$('.media-upload-form').addClass('cust-dropzone-error');
				is_valid = false;
			}
		}else{
			articles =  desc_editor.getData();
			$('#upload_article').val(articles);
			if(articles == '' ){
				$('#article_error_msg').html('Please enter article.');
				is_valid = false;
			}
			var estimate_time = $('.estimate-times').val();
			if(estimate_time == '' ){
				$('.duration-error').html('Estimate time is required.');
				is_valid = false;
			}
		}

		if(is_valid == false){
			return false;
		}
		$('.submit-lecture-btn').children().removeClass('d-none');
		$('#add_lecture_modal').find('button').prop('disabled',true);

		var $form = $(this);
		$.ajax({
			type: "POST",
			url: $form.attr('action'),
			data: $form.serialize(),
			dataType: 'json',
			cache: false,
			success: function (result)
			{
				$('#add_lecture_modal').find('button').prop('disabled',false);
				$('.submit-lecture-btn').prop('disabled',false);
				$('.cancel-lecture-form').prop('disabled',false);
				$('.submit-lecture-btn').children().addClass('d-none');
				if(result.status == true){
					$('#lecture-list-'+result.secret).html(result.html);
					alert_success(result.message);
					reinitialise_form();
					update_shorting();
				}else{
					alert_error(result.message);
				} 
			},
			error: function () 
			{ 
				alert_error('Something went wrong. Try again sometime.');
				$('#add_lecture_modal').find('button').prop('disabled',false);
				$('.submit-lecture-btn').prop('disabled',false);
				$('.cancel-lecture-form').prop('disabled',false);
			}
		});
	});

	/* Submit content form */ 
	$(document).on('click','.get-downloadable-contents',function(){
		$('.get-downloadable-contents').prop('disabled',true);
		var $this = $(this);
		var content_media_id = $(this).data('id');
		
		$this.find('.fa-spin').removeClass('d-none');
		$this.find('.fa-file').addClass('d-none');

		$('#hidden_content_media_id').val(content_media_id);
		get_downloadable_resource(content_media_id,$this,true);
	});
	
	$('#addLectureModel').bind('hidden.bs.modal', function () {
		$('#load_lecture_content').html('');
	  	removeFiles();
 	});

	$(document).on('click','.select-media-type',function(e){
		$("#article_error_msg").html('');
		$("#file_upload_error_msg").html('');
		$('.media-upload-form').removeClass('cust-dropzone-error');
		var media_type = $(this).val();
		if(media_type == 'video'){
			$('.media-upload-form').removeClass('hide');
			$('.upload-article').addClass('hide');
			$('.enable_show_preview').removeClass('hide');
			$('.video-description').removeClass('hide');
			$('#hid_media_type').val('video');
		}else{
			$('.video-description').addClass('hide');
			$('.media-upload-form').addClass('hide');
			$('.upload-article').removeClass('hide');
			$('.enable_show_preview').addClass('hide');
			$('#hid_media_type').val('article');
		}
	});

	$('#add_lecture_modal').bind('hidden.bs.modal', function () {
		$('#addEditLecture').html('');
	  	removeFiles();
 	});

	function upload_file_dropzone(){
		if($('.template').length){
			var previewNode = document.querySelector(".template");
			previewNode.id = "";
			var previewTemplate = previewNode.parentNode.innerHTML;
			previewNode.parentNode.removeChild(previewNode);
			
			Dropzone.autoDiscover = false;
			// url: "{!! route('course.content.upload_media') !!}?_token={{csrf_token()}}", // Set the url
			var myDropzone = new Dropzone('#video-dropzone', { // Make the whole body a dropzone
				url: "{{route('chunk_upload')}}",
				params: function (files, xhr, chunk) {
					if (chunk) {
						return {
							dzuuid: chunk.file.upload.uuid,
							dzchunkindex: chunk.index,
							course_id: "{{$Course->secret}}",
						};
					}
				},
				acceptedFiles: ".mp4,.mov,.webm",
				parallelChunkUploads: true,
				chunking: true,
				retryChunks: true,
				retryChunksLimit: 3,
				forceChunking: true,
				chunkSize: 50000000, // chunk size 1,000,000 bytes (~1MB)
				maxFilesize: 1024,
				dictFileTooBig: "File is larger than 1GB",
				maxFiles:1,
				parallelUploads: 1,
				previewTemplate: previewTemplate,
				autoQueue: false, // Make sure the files aren't queued until manually added
				previewsContainer: ".previews", // Define the container to display the previews
				clickable: ".video-input-button", // Define the element that should be used as click trigger to select files.
				chunksUploaded: function(file, done) {
					// All chunks have been uploaded. Perform any other actions
					let currentFile = file;
					// This calls server-side code to merge all chunks for the currentFile
					$.ajax({
						url: "{{route('upload.course.video')}}",
						type: "POST",
						dataType: "json",
						data: {
							"_token": _token,
							'dzuuid':currentFile.upload.uuid,
							'dztotalchunkcount':currentFile.upload.totalChunkCount,
							'extension':currentFile.name.substr( (currentFile.name.lastIndexOf('.') +1) ),
							'fileName':currentFile.name,
						},
						success: function (response) {
							$('#add_lecture_modal').find('button').prop('disabled',false);
							if(response.success == true){
								$('.media-validation-message').html('');
								if(response.media_type == 'video'){
									if(response.source_url_thumb == ''){
										$('.media_show').attr('src',"{{url('public/frontend/images/video_players.png')}}");
									}else{
										$('.media_show').attr('src',response.source_url_thumb);
									}
								}else{
									$('.media_show').attr('src',response.source_url_thumb)
								}
								var media = response.originalName.split(".");
								var filename = media[0];
								var media_extention = "."+media[1];
								$('.media_name').html(response.originalName);
								$('#upload_media').val(response.originalName);
								$('.media_extension').html(media_extention);
								$('.media_size').html(response.media_size);
								$(".show-video-processing").addClass('d-none');
								$('.previews').hide();
								$('.show-media').show();
								$('.submit-btn').removeAttr('disabled');
								$('#file_upload_error_msg').html('');
								$('.media-upload-form').removeClass('cust-dropzone-error');
								$('.submit-lecture-btn').prop('disabled',false);
								$('.cancel-lecture-form').prop('disabled',false);
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
							}
							//done();
						},
						error: function (response) {
							currentFile.accepted = false;
							myDropzone._errorProcessing([currentFile], response.responseText);
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
						}
					});
				},
				canceled: function (file) {
					$('#add_lecture_modal').find('button').prop('disabled',false);
					var chunks = file.upload.chunks;
					if (chunks) {
						for (var i = 0; i < chunks.length; i++) {
							if (chunks[i].xhr) {
								chunks[i].xhr.abort();
							}
						}
					}
				}
			});

			myDropzone.on("addedfile", function(file) {
				// Hookup the start button
				$('.select-media-type').attr('disabled',true);
				$('.upload-file-section').hide();
				$('.previews').show();
				file.previewElement.getElementsByClassName("start_upload").onclick = function() { myDropzone.enqueueFile(file); };
				setTimeout(function(){ $('.start_upload').trigger('click'); $('#add_lecture_modal').find('button').prop('disabled',true); },500);
			});

			// Update the total progress bar
			myDropzone.on("uploadprogress", function(file, progress, bytesSent) {
				console.log(progress);
				$(".progress-bar").css('width',progress + "%");
				$(".progress-percentage").html(progress.toFixed() + "%");
				if(progress.toFixed() >= 100){
					$(".show-video-processing").removeClass('d-none');
				}
			});

			myDropzone.on("sending", function(file, xhr, formData) {
				formData.append("_token", _token);
				// Show the total progress bar when upload starts
				$('#total-progress').css('opacity',"1");
				// And disable the start button
				file.previewElement.querySelector(".start_upload").setAttribute("disabled", "disabled");
			});

			// Hide the total progress bar when nothing's uploading anymore
			myDropzone.on("error", function(file, response, message) {
				$('#add_lecture_modal').find('button').prop('disabled',true);
				_ref = file.previewElement.querySelectorAll("[data-dz-errormessage]");
				_results = [];
				for (_i = 0, _len = _ref.length; _i < _len; _i++) {
					node = _ref[_i];

					if(response.message != undefined){
						_results.push(node.textContent = response.message);
					}else if(response != undefined){
						_results.push(node.textContent = response);
					}else {
						_results.push(node.textContent = "Something went wrong. Please try again.");
					}
				}
			});

			// The "add files" button doesn't need to be setup because the config
			// `clickable` has already been specified.	
			$(document).on('click','.start_upload',function(){
				myDropzone.enqueueFiles(myDropzone.getFilesWithStatus(Dropzone.ADDED));
			});
			$(document).on('click','.cancel_upload',function(){
				$('.upload-file-section').show();
				$('.show-media').hide();
				myDropzone.removeAllFiles(true);
			});
			$(document).on('click','.remove-file',function(){
				$('#add_lecture_modal').find('button').prop('disabled',false);
				if($('#lecture_store_form #hidden_id').length == 0){
					$('.select-media-type').attr('disabled',false);
				}
				
				$('.upload-file-section').show();
				$('.media-upload-form').removeClass('hide');
				$('.show-media').hide();
				$('#upload_media').val('');
				$(".show-video-processing").addClass('d-none');
				myDropzone.removeAllFiles(true);
			});

			myDropzone.on("removedfile", function(e){
				$('#add_lecture_modal').find('button').prop('disabled',false);
				if($('#lecture_store_form #hidden_id').length == 0){
					$('.select-media-type').attr('disabled',false);
				}

				$('.upload-file-section').show();
				$('.show-media').hide();
				$('#upload_media').val('');
				$(".show-video-processing").addClass('d-none');
				removeFiles();
				myDropzone.removeAllFiles(true);
			});

			myDropzone.on("success", function(file,response){
				$('#add_lecture_modal').find('button').prop('disabled',false);
				if(response.success == true){
					$('.media-validation-message').html('');
					if(response.media_type == 'video'){
						if(response.source_url_thumb == ''){
							$('.media_show').attr('src',"{{url('public/frontend/images/video_players.png')}}");
						}else{
							$('.media_show').attr('src',response.source_url_thumb);
						}
					}else{
						$('.media_show').attr('src',response.source_url_thumb)
					}
					var media = response.originalName.split(".");
					var filename = media[0];
					var media_extention = "."+media[1];
					$('.media_name').html(response.originalName);
					$('#upload_media').val(response.originalName);
					$('.media_extension').html(media_extention);
					$('.media_size').html(response.media_size);
					$('.previews').hide();
					$('.show-media').show();
					$('.submit-btn').removeAttr('disabled');
					$('#file_upload_error_msg').html('');
					$('.media-upload-form').removeClass('cust-dropzone-error');
					$('.submit-lecture-btn').prop('disabled',false);
					$('.cancel-lecture-form').prop('disabled',false);
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
				}
			});
		}
  	}

	function upload_article(){
		ClassicEditor.create( document.querySelector( '#upload_article' ) )
		.then( newEditor => {
			desc_editor = newEditor;
			desc_editor.model.document.on( 'change:data', ( evt, data ) => {
				articles =  desc_editor.getData();
				if(articles==''){
					$("#article_error_msg").html('Please enter article');
				    $('.submit-lecture-btn').prop('disabled',true);
				}else{
					$("#article_error_msg").html('');
				    $('.submit-lecture-btn').prop('disabled',false);
				}
			});
		})
        .catch( error => {
            console.error( error );
        });

		$('input[name$="duration"]').inputmask("hh:mm:ss", {
			placeholder: "00:00:00", 
			insertMode: false, 
			showMaskOnHover: false,
			hourFormat: 24
		});
	}

	function removeFiles(){
		$('#file_upload_error_msg').html('Please upload video.');
		$('.media-upload-form').addClass('cust-dropzone-error');
		$.ajax({
			type: "DELETE",
			url: "{{route('course.content.delete_media')}}",
			dataType: 'json',
			cache: false,
			data:  { _token: "{{csrf_token()}}"},
			success: function(data) {
				if(data.success == true){
					// alert_success("File removed successfully.");
				} else {
					alert_error("Something goes wrong.");
				}
			},
			error: function(){

			}
		});
	}

	function reinitialise_form(){
		$('.get-lecture-form').prop('disabled',false);
		$('.get-lecture-form').children().addClass('d-none');
		$('.get-lecture-form').show();
		//$('.addLecture').html('').collapse('hide');
		$('#add_lecture_modal').modal('hide');
		$('#addEditLecture').html('');

		upload_file_dropzone();
	}

	/* upload resource file js */
	var maxFilesize = {{$maxFilesize}};
	maxFilesize = parseInt(maxFilesize);
	Dropzone.options.dropzoneForm = {
		maxFilesize: maxFilesize,
		parallelUploads: 100,
		dictFileTooBig: 'File is bigger than '+maxFilesize+'MB',
		addRemoveLinks: true,
		init: function() {
			var msg = 'Maximum File Size '+maxFilesize+'MB';
			var brswr_img = "{{url('public/frontend/images/upload-cloud.png')}}";
			var apnd_msg ='<img src="'+brswr_img+'" alt=""><h1 class="pt-2 mb-1 font-20 text-color-4 font-weight-normal">Drop files here or  <svp class="text-color-1">browse</svp></h1><h3 class="font-14 text-color-4 font-weight-normal">'+msg+'</h3>';
			$('#dropzoneForm .dz-message').append(apnd_msg);
			$('#dropzoneForm .dz-message span').hide();
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
		success: function(file,data) {
			this.removeFile(file);
			if(data.code == 200){
				$('.load_course_resourse_content').empty();
				$('.load_course_resourse_content').html(data.html);
				$('#downloadable-count-'+data.id).html(data.total_resources);
				donwloadable_resourse_init();
				alert_success(data.message);
			}else{
				this.removeFile(file);
				if(!data.message){
					alert_error("Something wrong went");
				}else{
					alert_error(data.message);
				}

			}
		}
	};

	/* Donwloadable JS */
	function donwloadable_resourse_init(){
		$(document).on('hidden.bs.modal', '.bootbox.modal', function (e) {  
			if($('#downloadable_content_model.show').length > 0){
				$('body').addClass('modal-open');
			}
		});
		
		$('#resourses-paginate .pagination a').click(function(e){
			e.preventDefault();
			var url = $(this).attr('href');
			$.ajax({
				type: "GET",
				url: url,
				dataType: 'json',
				cache: false,
				success: function (result)
				{
					if(result.status == true){
						$('.load_course_resourse_content').html(result.html);
						donwloadable_resourse_init();
					}else{
						alert_error(result.message);
					} 
				},
				error: function () 
				{ 
					alert_error('Something went wrong. Try again sometime.');
				}
			});
		});

		/* Delete donwloadable JS */
		$('.remove-downloadable-file').on('click', function(){
			var id = $(this).data('id');
			var url = $(this).data('url');
			bootbox.confirm("Are you sure you want to delete this resource file?", function(result){ 
				if (result) {
					var _token = "{{ csrf_token() }}";
					$.ajax({
						url: url,
						method: "DELETE",
						data: {'_token': _token},
						dataType: "json",
						success: function(data){
							if(data.status == true){
								alert_success(data.message);
								var content_id = $('#hidden_content_media_id').val();
								var count = $('#downloadable-count-'+content_id).html();
								$('#downloadable-count-'+content_id).html(parseInt(count) - 1);
								// $('#resource-'+id).remove();
								get_downloadable_resource(content_id,true);
							}else if(data.status == false && data.url != null){
								window.location.href = data.url;
							}else{
								alert_error(data.message);
							}
						},
						error: function(){
							alert('Something went wrong.');
							location.reload();
						}
					});
				}	 
			});
		});
	}

	/* Get Donwloadable resources */
	function get_downloadable_resource(content_media_id,$this=null,is_default=false){
		$.ajax({
			type: "GET",
			url: "{{route('course.get_downloadable_content')}}",
			data: {'content_media_id':content_media_id},
			dataType: 'json',
			cache: false,
			success: function (result)
			{
				if(is_default==true){
					$('.get-downloadable-contents').prop('disabled',false);
					$this.find('.fa-spin').addClass('d-none');
					$this.find('.fa-file').removeClass('d-none');
				}

				if(result.status == true){
					$('.load_course_resourse_content').html(result.html);
					if(is_default==true){
						$('#downloadable_content_model').modal('show');
						$('#downloadable-count-'+content_media_id).html(result.total_resources);
					}
					donwloadable_resourse_init();
				}else{
					alert_error(result.message);
				} 
			},
			error: function () 
			{ 
				alert_error('Something went wrong. Try again sometime.');
				if(is_default==true){
					$('.get-downloadable-contents').prop('disabled',false);
					$this.find('.fa-spin').addClass('d-none');
					$this.find('.fa-file').removeClass('d-none');
				}
			}
		});
	}

	/* Preview Artiacle JS */
	$(document).on('click','.course-article-preview',function(){
		var url = $(this).data('url');
		var id = $(this).data('id');
		var token = "";
		var is_description = false;
		if($(this).data('token') != undefined){
			token = $(this).data('token');
		}
		if($(this).data('is_description') != undefined){
			is_description = true;
		}

		$.ajax({
			url: url,
			method: "POST",
			data: {'_token': _token, 'id':id,'token':token,'is_description':is_description},
			dataType: "json",
			success: function(data){
				if(data.status == true){
					if(is_description == false){
						if(data.type == 'video'){
							var player_id = "video-player-preview";
							load_jwplayer(player_id, data.link, data.name);
							$('#preview_course_video_modal').modal('show');
						}else{
							$('#course-preview-title').html(data.name);
							$("#load-course-article").html(data.article_text).show();
							hljs.highlightAll();
							$('#preview-course-article').modal('show');
						}
					}else{
						$('#video_description').html(data.video_description);
						$('#video_description_modal').modal('show');
					}
				}else{
					alert_error('Something went wrong.');
				}
			},
			error: function(){
				alert_error('Something went wrong.');
			}
		});
	});

	/* Preview Course JS */
	$(document).on('click','.preview-course-video-btn',function(){
		var video_link = $(this).data('url');
		var title = $(this).data('title');
		var player_id = "video-player-preview";
		load_jwplayer(player_id, video_link, title);
		$('#preview_course_video_modal').modal('show');

	});

	function load_jwplayer(player_id, video_link, title){
		jwplayer(player_id).setup({
			"file": video_link,
			title: title,
			autostart: true,
		});
	}
</script>
@endsection