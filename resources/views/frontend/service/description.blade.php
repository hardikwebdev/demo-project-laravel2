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
						Description
					</div>
					{{ Form::open(['route' => ['services_desc',$Service->seo_url], 'method' => 'POST','id'=>'frmServiceDesc','class'=>"mb-10"]) }}

					<input type="hidden" name="current_step" value="3">
					<input type="hidden" name="preview" value="false" id="preview_input_id">
					<div class="row">
						<div class="col-lg-6">
							<div class="form-group">
								<label>Youtube URL</label>
								{{Form::text('youtube_url',$Service->youtube_url,["class"=>"form-control","placeholder"=>"https://www.youtube.com/watch?v=ytQ5rgE21VZw","id"=>"youtube_url"])}}
								<div class="text-danger url-error" style="text-align: left;" ></div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<label>Tags</label>
								{{Form::text('tags',$Service->tags,["class"=>"form-control tags","placeholder"=>"","id"=>"tags"])}}
								<small class="dangerColor"><i>Maximum 3 tags allowed</i></small>
							</div>    
						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<label>Meta Title</label>
								{{Form::text('meta_title',$Service->meta_title,["class"=>"form-control keywords","placeholder"=>"","id"=>"meta_title","maxlength"=>"70"])}}
							</div>    
						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<label>Meta Keywords</label>
								{{Form::text('meta_keywords',$Service->meta_keywords,["class"=>"form-control keywords","placeholder"=>"","id"=>"meta_keywords"])}}
							</div>    
						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<label>Meta Description</label>
								{{Form::text('meta_description',$Service->meta_description,["class"=>"form-control keywords","placeholder"=>"","id"=>"meta_description" ,"maxlength"=>"160"])}}
							</div>    
						</div>
						<div class="col-lg-12">
							<div class="form-group">
								<label>Briefly Describe Your Service <span class="text-danger">*</span></label>
								{!! Form::textarea('descriptions',str_replace( '&', '&amp;', $Service->descriptions ),["class"=>"form-control","placeholder"=>"","id"=>"descriptions"]) !!}
								<div class="text-danger descriptions-error" style="text-align: left;" ></div>
							</div>    
						</div>
						<div class="col-lg-12 create-new-service update-account text-right">
							@if($Service->current_step >= 5 && $Service->uid == Auth::id())
								<button type="button" value="Save & Preview" class="btn btn-primary save_and_preview_btn">Save & Preview</button> 
							@endif
							<button type="submit" class="btn btn-primary">Save & Continue</button> 
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
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">

<style>
</style>
@endsection

@section('scripts')
<script type="text/javascript" src="{{front_asset('bootstrap/dist/js/bootstrap-tagsinput.js')}}"></script> 
<script type="text/javascript">
	$(document).ready(function () {
		ClassicEditor.create( document.querySelector( '#descriptions' ) )
		.then( newEditor => {
			desc_editor = newEditor;
			desc_editor.model.document.on( 'change:data', ( evt, data ) => {
				var descriptions =  desc_editor.getData();
				if(descriptions==''){
					$(".descriptions-error").html('Please enter descriptions');
				}else{
					$(".descriptions-error").html('');
				}
			});
		})
        .catch( error => {
            console.error( error );
        });


		$('.tags').tagsinput({
			tagClass: 'big label label-primary',
			preventPost: true,
			maxTags: 3
		});
		$('#meta_keywords').tagsinput({
			tagClass: 'big label label-primary',
			preventPost: true,
			maxTags: 10
		});
		
		$("#frmServiceDesc").submit(function(e){
			$(".url-error").html('');
			$(".descriptions-error").html('');
			if($(".url-error").parent().hasClass("has-error")) {
				$(".url-error").parent().removeClass("has-error");
			}
			if($('#youtube_url').val() !=''){
				var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/;
				var shortRegExp = /^.*(youtu.be\/|v\/|u\/\w\/|shorts\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/;
				var matches = $('#youtube_url').val().match(regExp);
				var matchesShort = $('#youtube_url').val().match(shortRegExp);
				if (!matches && !matchesShort) {
					$(".url-error").html('Please enter valid URL');
					$('#youtube_url').focus();
					$(".url-error").parent().addClass("has-error");
					return false;
				}
			}
			var descriptions =  desc_editor.getData();
			if(descriptions==''){
				desc_editor.editing.view.focus();
				$(".descriptions-error").html('Please enter descriptions');
				return false;
			}
		});

	});
	$('.save_and_preview_btn').on('click', function(){
		$(".url-error").html('');
		$(".descriptions-error").html('');
		if($(".url-error").parent().hasClass("has-error")) {
			$(".url-error").parent().removeClass("has-error");
		}
		if($('#youtube_url').val() !=''){
			var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/;
			var matches = $('#youtube_url').val().match(regExp);
			var shortRegExp = /^.*(youtu.be\/|v\/|u\/\w\/|shorts\/|watch\?v=|\&v=|\?v=)([^#\&\?]*).*/;
			var matchesShort = $('#youtube_url').val().match(shortRegExp);
			if (!matches && !matchesShort) {
				$(".url-error").html('Please enter valid URL');
				$('#youtube_url').focus();
				$(".url-error").parent().addClass("has-error");
				return false;
			}
		}
		var descriptions =  desc_editor.getData();
		if(descriptions==''){
			$(".descriptions-error").html('Please enter descriptions');
			desc_editor.editing.view.focus();
			return false;
		}

		$('#descriptions').val(descriptions);
		
		$('#preview_input_id').val('true');
		$.ajax({
			type: "POST",
			url: $('#frmServiceDesc').attr('action'),
			data: $('#frmServiceDesc').serialize(),		
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