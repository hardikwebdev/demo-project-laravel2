@extends('layouts.frontend.main')
@section('pageTitle', 'Portfolio | demo')
@section('content')
<!-- Display Error Message -->
@include('layouts.frontend.messages')

<!-- Masthead -->
<header class="masthead text-white"> {{-- masthead  --}}
	<div class="overlay"></div>
    <div class="bg-dark w-100">
	<div class="container py-4">
        <div class="row py-2">
            <div class="col-12 col-md-4">
                <a href="{{route('viewuserservices',$username)}}"> <p class="text-center text-md-left mb-0 text-white font-24 font-weight-bold"><i class="fas fa-chevron-left px-2"></i> Back to my profile</p></a>
            </div>
            <div class="col-12 col-md-4">
                <p class="text-center mb-0 text-white font-24 font-weight-bold mt-3 mt-md-0">My Portfolio</p>
            </div>
            <div class="col-12 col-md-4 text-center text-md-right">
                <button class="btn bg-primary-blue text-white font-13 py-2 px-3 mt-3 mt-md-0 border-radius-6px add-project" id="add-project" data-url="{{route('portfolio.create')}}"><img src="{{url('public/frontend/images/plus-circle.png')}}" class="pr-2 align-bottom" alt=""> Create New</button>
            </div>
        </div>
	</div>
    </div>
</header>

<div class="container my-5 font-lato">
    <div class="row justify-content-center @if(count($portfolios) > 1) drag-list @endif">
        @if(count($portfolios) > 0)
        @foreach($portfolios as $portfolio)
	        <div class="col-12 col-md-6 col-lg-4 mt-3 drag-item" id="{{$portfolio->secret}}" draggable="true">
	            <div class="summary min-h-100">
	                <div class="d-flex justify-content-between py-2 align-items-center px-3">
	                    <div>
	                        <img src="{{url('public/frontend/images/more-vertical.png')}}" class="img-fluid drag-icon" alt="">
	                    </div>
	                    <div>
                        	<a href="Javascript:;" class="edit-project" data-url="{{route('portfolio.update',$portfolio->secret)}}"><img src="{{url('public/frontend/images/edit.png')}}" class="img-fluid px-2"></a>
	                        <a href="Javascript:;" class="delete-project" data-url="{{route('portfolio.delete',$portfolio->secret)}}"><i class="far fa-trash-alt text-color-6 font-16 align-middle"></i></a>
	                    </div>
	                </div>
					@if($portfolio->media_type == 'image')
						<img src="{{$portfolio->thumbnail_url}}" data-link="{{$portfolio->media_link}}" class="img-fluid cust-portfolio-img drag-image custViewImage" alt="{{$portfolio->title}}">
					@else
						<div class="cust-portfolio-img drag-image" style="background-image: url('{{($portfolio->thumbnail_url)? $portfolio->thumbnail_url : url('public/frontend/images/video_players.png')}}')">
							<img data-url="{{$portfolio->media_link}}" data-mime="{{$portfolio->mime}}" data-title="{{$portfolio->title}}" src="{{get_video_player_img()}}" class="img-fluid cust-portfolio-img video-link video-play-btn" >
						</div>
					@endif
	                <div class="p-3">
	                    <p class="font-16 text-color-2 font-weight-bold">{{$portfolio->title}}</p>
	                    <p  class="font-14 text-color-4 portfolio-description mb-0" data-content="{{$portfolio->description}}">
	                    	<span class="readless-text-{{$portfolio->secret}}">{{string_limit($portfolio->description,70)}}</span>
	                    	@if(strlen($portfolio->description) > 70)
		                    	<span class="d-none readmore-text-{{$portfolio->secret}}">{{$portfolio->description}}</span>
								<label class="text-primary btn-link read-more" id="readmore-{{$portfolio->secret}}" data-id="{{$portfolio->secret}}">Read More</label>
								<label class="text-primary btn-link read-less d-none" id="readless-{{$portfolio->secret}}" data-id="{{$portfolio->secret}}">Less</label>
							@endif
	                    </p>
	                </div>  
	            </div>
	        </div>
        @endforeach
        @else
        <div class="col-lg-12 text-center">
        	<div>
    			<img src="{{url('public/frontend/images/upload-cloud.png')}}" class="img-fluid" alt="">
        	</div>
        	<p class="protfolio-note">Looks like you haven’t added any projects to your portfolio yet.</p>
    		<a href="Javascript:;" data-url="{{route('portfolio.create')}}" class="font-11 text-color-1 font-weight-bold add-project">+ Create New</a>
        </div>
        @endif
    </div>
</div>

<!--Create Portfolio Modal -->
<div class="modal fade" id="ProjectModel" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content border-radius-15px" id="load_project_content">
			
		</div>
	</div>
</div>
@endsection

@section('css')
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="{{url('public/frontend/css/price_range_style.css')}}"/>
<link href="{{front_asset('assets/css/dropzone.css')}}" rel="stylesheet">
@endsection

@section('scripts')
<script type="text/javascript" src="{{front_asset('bootstrap/dist/js/bootstrap-tagsinput.js')}}"></script> 
<script type="text/javascript" src="{{url('public/frontend/js/price_range_script.js')}}"></script>
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script src="{{front_asset('assets/js/dropzone.js')}}" type="text/javascript"></script>
<script type="text/javascript">

	$('#ProjectModel').bind('hidden.bs.modal', function () {
		$('#load_project_content').html('');
	  	removeFiles();
 	});

  	function upload_file_dropzone(is_update=0){
  		console.log(is_update);
	  	var previewNode = document.querySelector(".template");
	    previewNode.id = "";
	    var previewTemplate = previewNode.parentNode.innerHTML;
	    previewNode.parentNode.removeChild(previewNode);
		
		Dropzone.autoDiscover = false;
	    var myDropzone = new Dropzone('.dropzone', { // Make the whole body a dropzone
		  	url: '{!! route('portfolio.mediaupload') !!}?_token={{csrf_token()}}', // Set the url
	    	acceptedFiles: "jpeg,.jpg,.png,.mp4,.mov,.webm",
			maxFilesize: 250,//250MB
		  	parallelUploads: 1,
	    	dictFileTooBig: 'File is larger than 250MB',
		  	previewTemplate: previewTemplate,
		  	autoQueue: false, // Make sure the files aren't queued until manually added
        	maxFiles:1,
		  	previewsContainer: ".previews", // Define the container to display the previews
		  	clickable: ".fileinput-button" // Define the element that should be used as click trigger to select files.
	  	});

		myDropzone.on("addedfile", function(file) {
		  	// Hookup the start button
		  	$('.media-upload-form').hide();
			$('.previews').show();
		  	file.previewElement.getElementsByClassName("start_upload").onclick = function() { myDropzone.enqueueFile(file); };
		  	setTimeout(function(){ $('.start_upload').trigger('click'); },500);
		});

		// Update the total progress bar
		myDropzone.on("totaluploadprogress", function(progress) {
		  	$(".progress-bar").css('width',progress + "%");
		  	$(".progress-percentage").html(progress.toFixed() + "%");
		});

		myDropzone.on("sending", function(file) {
		  	// Show the total progress bar when upload starts
		  	$('#total-progress').css('opacity',"1");
		  	// And disable the start button
		  	file.previewElement.querySelector(".start_upload").setAttribute("disabled", "disabled");
		});

		// Hide the total progress bar when nothing's uploading anymore
		myDropzone.on("queuecomplete", function(progress) {
		  	// $("#total-progress").css('opacity',"0");
		});

		// Hide the total progress bar when nothing's uploading anymore
		myDropzone.on("error", function(file, response, message) {
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
		});

		// The "add files" button doesn't need to be setup because the config
		// `clickable` has already been specified.	
		$(document).on('click','.start_upload',function(){
	  		myDropzone.enqueueFiles(myDropzone.getFilesWithStatus(Dropzone.ADDED));
		});
		$(document).on('click','.cancel_upload',function(){
		  	$('.media-upload-form').show();
			$('.show-media').hide();
		  	myDropzone.removeAllFiles(true);
		});
		$(document).on('click','.remove-file',function(){
		  	$('.media-upload-form').show();
			$('.show-media').hide();
			$('#upload_media').val('');
		  	myDropzone.removeAllFiles(true);
		});

		myDropzone.on("removedfile", function(e){
		  	$('.media-upload-form').show();
			$('.show-media').hide();
			$('#upload_media').val('');
		  	removeFiles();
		});

		myDropzone.on("success", function(file,response){
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

	function removeFiles(){
		$.ajax({
			type: "POST",
			url: "{{route('delete.tempmedia')}}",
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

	/*add/edit project function*/ 
	$(document).on("click",".add-project, .edit-project",function(e) {
		$.ajax({
			type: "GET",
			url: $(this).data('url'),
			dataType: 'json',
			cache: false,
			success: function(data) {
				if(data.success == true){
					$('#load_project_content').html(data.html);
					$('#ProjectModel').modal('show');
					upload_file_dropzone();
					cus_portfolio_form();
				} else {
					alert_error("Something goes wrong.");
				}
			},
			error: function(){
				alert_error("Something goes wrong.");
			}
		});
	});
	@if(Request::has('addproject'))
		$('#add-project').click();
		window.history.pushState("","","{{Request::url()}}");
	@endif
</script>
<script type="text/javascript">
	$(document).on('click', '.delete-project', function(e){
		e.preventDefault();
		var url = $(this).data('url');
		var cMessage = "Are you sure you want to delete this project?";

		bootbox.confirm(cMessage, function(result){ 
			if(result == true){
				window.location.href = url;
			}
		});
	});
</script>
@endsection