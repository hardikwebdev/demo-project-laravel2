@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Course')
@section('content')
@section('css')
<link href="{{url('public/frontend/assets/css/dropzone.css')}}" rel="stylesheet">
@endsection
@include('frontend.course.header')

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
								<label><strong>Photos <span class="text-danger">*</span></strong> - Upload photos that describe or are related to your course. The first photo will be your course thumbnail that displays with your listing. (Preferable image resolution is 836X484)
								</label>
							</div>    
						</div>
					</div>
					<div class="row">
						<div class="col-lg-12">
							{{ Form::open(['route' => ['course.gallery',$Course->seo_url], 'method' => 'POST','class' => 'dropzone dropzone-file-area','id'=>'imagedropzone','files'=>true]) }}
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
								@if(count($Course->images))
								@foreach($Course->images as $row)
								<li class="pack-box col-md-2 get-gallery-id" data-id="{{$row->id}}">
									@if(count($Course->images)>1)
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
					<div class="col-lg-12 create-new-service update-account text-right pt20 mb-10">
						@if($Course->current_step >= 5 && $Course->uid == Auth::id())
							@if($Course->is_review_edition == 1 && $Course->review_edition_count < $Course->no_of_review_editions)
							<a href="{{route('services_details',['username'=>$Course->user->username,'seo_url'=> $Course->seo_url,'review-edition'=>1])}}" target="_blank"><button type="button" class="btn btn-primary">Preview</button></a>
							@else
							<a href="{{route('services_details',[$Course->user->username,$Course->seo_url])}}" target="_blank"><button type="button" class="btn btn-primary">Preview</button></a>
							@endif
						@endif

						@if($Course->current_step >= 5)
						<a href="{{route('course.section',$Course->seo_url)}}" class="button big primary"><button type="submit" class="btn btn-primary">Save &amp; Continue</button> </a>
						@else
						<a href="javascript:void(0);" class="button big primary custom-publish"><button type="submit" class="btn btn-primary">Save &amp; Continue</button> </a>
						@endif
					</div>   
				</div>
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
            // location.reload();
        }
    };
    $(document).on("click",".remove-image-pin",function(e) {
        $('.remove-image-pin').hide();
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