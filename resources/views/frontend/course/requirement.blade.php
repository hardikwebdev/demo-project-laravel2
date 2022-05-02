@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Course')
@section('content')
@include('frontend.course.header')

<section class="requirement-section">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="popular-grid ">
					<div class="seller pb-2">
						Requirement
					</div>
					{{ Form::open(['route' => ['course.requirement',$Course->seo_url], 'method' => 'POST', 'id' => 'frmReqCourse','class'=>"mb-10"]) }}
					<input type="hidden" name="current_step" value="4">
					<input type="hidden" name="preview" value="false" id="preview_input_id">

					<!-- INPUT CONTAINER -->
					<div class="input-container form-group">
						<label>Instructions</label>
						{{Form::textarea('questions',str_replace( '&', '&amp;',$Course->questions ),["class"=>"form-control","placeholder"=>"","id"=>"questions", 'hidden'])}}
						<div class="has-error"> <small class="help-block instructions-error" style="text-align: left;" ></small></div>
					</div>

                    <div class="input-container form-group hide-child-overflow">
                        <label>What you'll learn</label>
                        {{Form::text('what_you_learn',isset($Course->course_detail)?$Course->course_detail->what_you_learn:"",["class"=>"form-control","placeholder"=>"","id"=>"what_you_learn"])}}
                    </div>

					<!-- /INPUT CONTAINER -->
                    <div class="row">
						<div class="col-lg-12">
							<div class="col-lg-12 create-new-service update-account text-right">
								@if($Course->current_step >= 5 && $Course->uid == Auth::id())
									<button type="button" value="Save & Preview" class="btn bg-primary-blue save_and_preview_btn">Save & Preview</button> 
								@endif
								<button type="submit" class="btn bg-primary-blue save_and_continue">Save &amp; Continue</button> 
							</div>
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
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type="text/javascript">
    $(document).ready(function () {
        var edit_img = "{{front_asset('images/dashboard/edit.png')}}";
        var delete_img = "{{front_asset('images/dashboard/delete.png')}}";
        var MAX_OPTIONS = 10;

        ClassicEditor.create( document.querySelector( '#questions' ) )
        .then( newEditor => {
            req_questions_editor = newEditor;
        })
        .catch( error => {
            console.error( error );
        });

        $('.save_and_preview_btn').on('click', function(){
            var questions =  req_questions_editor.getData();
            
            $('#questions').val(questions);

            $('#preview_input_id').val('preview_course');
            $('.save_and_preview_btn').prop('disabled',true);
			$('.save_and_continue').prop('disabled',true);
            $.ajax({
                type: "POST",
                url: $('#frmReqCourse').attr('action'),
                data: $('#frmReqCourse').serialize(),		
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

        $('#what_you_learn').tagsinput({
			tagClass: 'big label label-primary',
			preventPost: true,
			maxTags: 10
		});
    });
</script>
@endsection