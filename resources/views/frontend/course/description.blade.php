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
						Description
					</div>
					{{ Form::open(['route' => ['course.description',$Course->seo_url], 'method' => 'POST','id'=>'frmCourseDesc','class'=>"mb-10"]) }}
					<input type="hidden" name="current_step" value="2">
					<input type="hidden" name="preview" value="false" id="preview_input_id">
					<div class="row">
						<div class="col-lg-6">
							<div class="form-group">
								<label>Meta Title</label>
								{{Form::text('meta_title',$Course->meta_title,["class"=>"form-control keywords","placeholder"=>"","id"=>"meta_title","maxlength"=>"70"])}}
							</div>    
						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<label>Meta Keywords</label>
								{{Form::text('meta_keywords',$Course->meta_keywords,["class"=>"form-control keywords","placeholder"=>"","id"=>"meta_keywords"])}}
							</div>    
						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<label>Meta Description</label>
								{{Form::text('meta_description',$Course->meta_description,["class"=>"form-control keywords","placeholder"=>"","id"=>"meta_description" ,"maxlength"=>"160"])}}
							</div>    
						</div>
						<div class="col-lg-6">
							<div class="form-group">
								<label>Tags</label>
								{{Form::text('tags',$Course->tags,["class"=>"form-control tags","placeholder"=>"","id"=>"tags"])}}
								<small class="dangerColor"><i>Maximum 3 tags allowed</i></small>
							</div>    
						</div>
						<div class="col-lg-12">
							<div class="form-group">
								<label>Description <span class="text-danger">*</span></label>
								{!! Form::textarea('descriptions',str_replace( '&', '&amp;', $Course->descriptions ),["class"=>"form-control","placeholder"=>"","id"=>"descriptions"]) !!}
								<div class="has-error"><small class="help-block descriptions-error" style="text-align: left;" ></small></div>
							</div>
						</div>
						<div class="col-lg-12 create-new-service update-account text-right">
							@if($Course->current_step >= 5 && $Course->uid == Auth::id())
								<button type="button" value="Save & Preview" class="btn bg-primary-blue save_and_preview_btn">Save & Preview</button> 
							@endif
							<button type="submit" class="btn bg-primary-blue save_and_continue">Save & Continue</button> 
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
				    $(':input[type="submit"]').prop('disabled',true);
				}else{
					$(".descriptions-error").html('');
				    $(':input[type="submit"]').prop('disabled',false);
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

		$("#frmCourseDesc").submit(function(e){
			$(".descriptions-error").html('');
			var descriptions =  desc_editor.getData();
			if(descriptions==''){
				desc_editor.editing.view.focus();
				$(".descriptions-error").html('Please enter descriptions');
				$(':input[type="submit"]').prop('disabled',true);
				return false;
			}
		});

	});

	$('.save_and_preview_btn').on('click', function(){
		$(".descriptions-error").html('');

		var descriptions =  desc_editor.getData();
		if(descriptions==''){
			$(".descriptions-error").html('Please enter descriptions');
			desc_editor.editing.view.focus();
			return false;
		}

		$('#descriptions').val(descriptions);
		$('#preview_input_id').val('true');
		$('.save_and_preview_btn').prop('disabled',true);
		$('.save_and_continue').prop('disabled',true);
		$.ajax({
			type: "POST",
			url: $('#frmCourseDesc').attr('action'),
			data: $('#frmCourseDesc').serialize(),		
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
</script>
@endsection