@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Services')
@section('content')
@section('css')
<link href="{{url('public/frontend/assets/css/dropzone.css')}}" rel="stylesheet">
@endsection

@include('frontend.service.header')

<section class="requirement-section">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="popular-grid ">
					<div class="seller pb-2">
						Gallery
					</div>
					<div class="row">
						<div class="col-lg-12">
							<div class="form-group">
								<label><strong>Photos <span class="text-danger">*</span></strong> - Upload photos that describe or are related to your services. The first photo will be your service thumbnail that displays with your listing. (Preferable image resolution is 836X484)
								</label>
							</div>    
						</div>
					</div>
					<div class="row">
						<div class="col-lg-12">
							{{ Form::open(['route' => ['services_gallery',$Service->seo_url], 'method' => 'POST','class' => 'dropzone dropzone-file-area','id'=>'imagedropzone','files'=>true]) }}
							<input type="hidden" name="bucket" value="{{ env('bucket_service') }}">
							<input type="hidden" name="media_type" value="image">
							<input type="hidden" name="current_step" value="5">

							<div class="fallback">
								<input name="image" type="file" id="imgupload" class="inputfile" />
							</div>
							{{Form::close()}}
							<div class="alert icon-alert with-arrow alert-success form-alter" role="alert" style="display: none;">
								<i class="fa fa-fw fa-check-circle"></i>
								<strong> Success ! </strong> <span class="success-message"> Priority updated successfully </span>
							</div>
							<div class="alert icon-alert with-arrow alert-danger form-alter" role="alert" style="display: none;">
								<i class="fa fa-fw fa-times-circle"></i>
								<strong> Note !</strong> <span class="warning-message"> Empty list cant be updated </span>
							</div>	
							<ul id="sortable-gallery" class="ui-state-default row custom-margin-top custom_images">
								@if(count($Service->images))
								@foreach($Service->images as $row)
								<li class="pack-box col-md-2 get-gallery-id" data-id="{{$row->id}}">
									@if(count($Service->images)>1)
									<a href="{{route('remove_media',$row->id)}}">
										<span class="custom-danger-btn remove-image-pin">{{-- X --}}
											<i class="fa fa-trash"></i>
										</span>
									</a>
									@endif
									@if($row->photo_s3_key != '')
									<img src="{{$row->media_url}}" class="fullimage fullimage-custom">
									@else
									<img src="{{url('public/services/images/'.$row->media_url)}}" class="fullimage fullimage-custom">
									@endif
								</li>
								@endforeach
								@endif
							</ul>
						</div>
					</div>
				</div>   
			</div>
			<div class="col-lg-12">
				<div class="popular-grid ">
					<div class="row">
						<div class="col-lg-12">
							<div class="form-group">
								<label><strong>Video</strong> - Add a relevant, high quality video that best showcases your service offering
								</label>
							</div>    
						</div>
					</div>
					<div class="row">
						<div class="col-lg-12">
							{{ Form::open(['route' => ['services_gallery',$Service->seo_url], 'method' => 'POST','class' => 'dropzone dropzone-file-area','id'=>'vediodropzone','files'=>true]) }}
							<input type="hidden" name="bucket" value="{{ env('bucket_service') }}">
							<input type="hidden" name="media_type" value="video">
							<input type="hidden" name="current_step" value="5">

							<div class="fallback">
								<input name="video" type="file" id="imgupload" class="inputfile" />
							</div>
							{{Form::close()}}
							<div class=" row custom-margin-top">
								@if(count($Service->video))
								@foreach($Service->video as $row)
									<div class="pack-box col-md-2">
										<a href="{{route('remove_media',$row->id)}}">
											<span class="custom-danger-btn remove-image-pin bring-button-forward">{{-- X --}}
												<i class="fa fa-trash"></i>
											</span>
										</a>
										<div class="service-video-thumbnail" style="background-image: url('{{($row->photo_s3_key != '')? $row->thumbnail_media_url : url('public/services/video/thumb'.$row->thumbnail_media_url)}}')">
											<img data-url="{{$row->media_url}}" data-mime="video/mp4" data-title="" src="{{get_video_player_img()}}" class="img-fluid video-link service-video-thumbnail cust-pd-15 video-play-btn" >
										</div>
									</div>
								@endforeach
								<input type="hidden" name="media_type" value="video">
								@endif
							</div>
						</div>
					</div>
					<div class="col-lg-12">
						<div class="popular-grid ">
							<div class="row">
								<div class="col-lg-12">
									<div class="form-group">
										<label><strong>PDF</strong> - PDFs We only recommend adding a PDF file if it further clarifies the service you will be providing.

										</label>
									</div>    
								</div>
							</div>
							<div class="row">
								<div class="col-lg-12">
									{{ Form::open(['route' => ['services_gallery',$Service->seo_url], 'method' => 'POST','class' => 'dropzone dropzone-file-area','id'=>'pdfdropzone','files'=>true]) }}
									<input type="hidden" name="bucket" value="{{ env('bucket_service') }}">
									<input type="hidden" name="media_type" value="pdf">
									<input type="hidden" name="current_step" value="5">

									<div class="fallback">
										<input name="pdf" type="file" id="imgupload" class="inputfile" />
									</div>
									{{Form::close()}}
									<div class="row custom-margin-top">
										@if(count($Service->pdf))
										@foreach($Service->pdf as $row)
										<div class="pack-box col-md-2">
											<a href="{{route('remove_media',$row->id)}}">
												<span class="custom-danger-btn remove-image-pin">{{-- X --}}
													<i class="fa fa-trash"></i>
												</span>
											</a>
											@if($row->photo_s3_key != '')
											<a href="{{$row->media_url}}" target="_blank"><img src="{{front_asset('images/default_pdf.png')}}" width="100"></a>
											@else
											<a href="{{url('public/services/pdf/'.$row->media_url)}}" target="_blank"><img src="{{front_asset('images/default_pdf.png')}}" width="100"></a>
											@endif
										</div>
										@endforeach
										@endif

										<input type="hidden" name="media_type" value="video">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-12">
						<div class="popular-grid ">
							<div class="row">
								<div class="col-lg-12">
									<div class="form-group">
										<label><strong>Facebook Image </strong> - Upload photo that describe or is related to your services. (Image resolution must be 1200 x 628)
										</label>
									</div>    
								</div>
							</div>
							<div class="row">
								<div class="col-lg-12">
									{{ Form::open(['route' => ['services_gallery',$Service->seo_url], 'method' => 'POST','class' => 'dropzone dropzone-file-area','id'=>'fbimagedropzone','files'=>true]) }}
									<input type="hidden" name="bucket" value="{{ env('bucket_service') }}">
									<input type="hidden" name="media_type" value="fb_image">
									<input type="hidden" name="current_step" value="5">

									<div class="fallback">
										<input name="fb_image" type="file" id="imgupload" class="inputfile" />
									</div>
									{{Form::close()}}
									<div class=" row custom-margin-top">
										@if(count($Service->fbimages))
										@foreach($Service->fbimages as $row)
										<div class="pack-box col-md-2">
											<a href="{{route('remove_media',$row->id)}}">
												<span class="custom-danger-btn remove-image-pin fb_image_delete">{{-- X --}}
													<i class="fa fa-trash"></i>
												</span>
											</a>
											@if($row->photo_s3_key != '')
											<img src="{{$row->media_url}}" class="fullimage fullimage-custom">
											@else
											<img src="{{url('public/services/images/'.$row->media_url)}}" class="fullimage fullimage-custom">
											@endif
										</div>
										@endforeach
										@endif
									</div>
								</div>
							</div>
						</div>   
					</div>
					<div class="col-lg-12 create-new-service update-account text-right pt20 mb-10">
						@if($Service->current_step >= 5 && $Service->uid == Auth::id())
							@if($Service->is_review_edition == 1 && $Service->review_edition_count < $Service->no_of_review_editions)
							<a href="{{route('services_details',['username'=>$Service->user->username,'seo_url'=> $Service->seo_url,'review-edition'=>1])}}" target="_blank"><button type="button" class="btn btn-primary">Preview</button></a>
							@else
							<a href="{{route('services_details',[$Service->user->username,$Service->seo_url])}}" target="_blank"><button type="button" class="btn btn-primary">Preview</button></a>
							@endif
						@endif

						{{-- <button type="submit" class="btn btn-primary">Publish</button>  --}}
						<a style="display: none;" href="{{route('service_publish',$Service->seo_url)}}"><button type="submit" id="publish_btn">Publish</button> </a>

						@if($Service->current_step >= 5)
						<a href="{{route('get_faq',$Service->seo_url)}}" class="button big primary"><button type="submit" class="btn btn-primary">Save &amp; Continue</button> </a>
						@else
						<a href="javascript:void(0);" class="button big primary custom-publish"><button type="submit" class="btn btn-primary">Save &amp; Continue</button> </a>
						@endif
					</div>   
				</div>
			</div>
		</section>

		@endsection

		@section('scripts')
		<script src="{{url('public/frontend/assets/js/dropzone.js')}}" type="text/javascript"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.2/jquery.ui.touch-punch.min.js"></script>
		<script type="text/javascript">
		$(document).ready(function(){

			$('#sortable-gallery').sortable({
				axis: 'x',
				cursor: 'move', 
				tolerance: 'pointer', 
				revert: true, 
				placeholder: 'state', 
				forcePlaceholderSize: true,
				update: function (event, ui) {
					
					var ids = new Array();
					$('#sortable-gallery li').each(function(){
						ids.push($(this).data("id"));
					});
					console.log(ids);
					$.ajax({
						url:"{{route('galleryReorder')}}",
						method:"POST",
						data:{ids:ids,'_token':_token},
						success:function(data)
						{
							if(data.success == true){
								$(".alert-danger").hide();
								$(".alert-success ").show();
							}else{
								$(".alert-success").hide();
								$(".alert-danger").show();
							}
							$('.alert').delay(2000).slideUp(300);
						}
					});
				}
			});
			
			
		});
			
			$('.custom-publish').click(function(){
				 

				 if($.trim($(".custom_images").html())!=''){
				 	
				 	$('#publish_btn').trigger('click');
				 	
				 }else{
				 	toastr.error("Please add atleast one image")
				 }
				
			});	

			Dropzone.options.imagedropzone = {
				maxFilesize: 0.5,//2MB - older value
				acceptedFiles: "jpeg,.jpg,.png,.gif",
				uploadMultiple: false,
				parallelUploads: 50,
				paramName: "image",
				addRemoveLinks: true,
				dictFileTooBig: 'Image is larger than 500KB',
				timeout: 10000,

				init: function () {
					var msg = 'Maximum File Size 500KB';
					$('#imagedropzone .dz-message').append('<br><p class="text-secondary">('+msg+')</p>');
				},
				success: function (file, done) {
					location.reload();
				}
			};
			Dropzone.options.vediodropzone = {
				maxFilesize: 10,
				acceptedFiles: ".mp4",
				uploadMultiple: false,
				parallelUploads: 50,
				paramName: "video",
				addRemoveLinks: true,
				dictFileTooBig: 'Vedio is larger than 10MB',
				timeout: 100000,

				init: function () {
					var msg = 'Maximum File Size 10MB';
					$('#vediodropzone .dz-message').append('<br><p class="text-secondary">('+msg+')</p>');
				},
				success: function (file, done) {
					location.reload();
				}
			};
			Dropzone.options.pdfdropzone = {
				maxFilesize: 2,
				acceptedFiles: ".pdf",
				uploadMultiple: false,
				parallelUploads: 50,
				paramName: "pdf",
				addRemoveLinks: true,
				dictFileTooBig: 'pdf is larger than 2MB',
				timeout: 10000,

				init: function () {
					var msg = 'Maximum File Size 2MB';
					$('#pdfdropzone .dz-message').append('<br><p class="text-secondary">('+msg+')</p>');
				},
				success: function (file, done) {
					location.reload();
				}
			};

			Dropzone.options.fbimagedropzone = {
				maxFilesize: 2,
				maxFiles:1,
				acceptedFiles: "jpeg,.jpg,.png,.gif",
				uploadMultiple: false,
				parallelUploads: 50,
				paramName: "fb_image",
				addRemoveLinks: true,
				dictFileTooBig: 'Image is larger than 2MB',
				timeout: 10000,

				init: function () {
					var msg = 'Maximum File Size 2MB';
					$('#fbimagedropzone .dz-message').append('<br><p class="text-secondary">('+msg+')</p>');

					this.on("thumbnail", function (file) {
						if (file.width != 1200 || file.height != 628) {
							toastr.error('Please upload image with resolution 1200 x 628');
							this.removeFile(this.files[0]);
						}
					});
				},
				success: function (file, done) {
					location.reload();
				}
			};

			$(document).on("click",".remove-image-pin",function(e) {
				$('.remove-image-pin').hide();
			});
		</script>
		<script type="text/javascript">
			$(document).on('click','.video-link',function(){
				var video_link = $(this).data('url');
				var ext = video_link.split('.').pop();
				$('#play_video video').html('<source src="'+video_link+'" type="video/'+ext+'">Your browser does not support HTML video.');
				$("#play_video video")[0].load();
				$('#showVideo').modal('show');
			});
		</script>
		@endsection

		@section('css')
		<style type="text/css">
			.pack-box {
				height: auto;
			}	
			.fullimage-custom{
				width: 218px;
				height: 218px !important;
				object-fit: cover;
			}
		</style>

		@endsection