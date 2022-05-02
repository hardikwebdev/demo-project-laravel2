@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Services')
@section('content')
@include('frontend.service.header')

<section class="requirement-section">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="popular-grid ">
					<div class="seller pb-2">
						Requirement
					</div>
					{{ Form::open(['route' => ['services_req',$Service->seo_url], 'method' => 'POST', 'id' => 'frmReqService','class'=>"mb-10"]) }}
					<input type="hidden" name="current_step" value="4">
					<input type="hidden" name="preview" value="false" id="preview_input_id">

					<!-- INPUT CONTAINER -->
					<div class="input-container form-group">
						<label>Instructions <span class="text-danger">*</span></label>
						{{Form::textarea('basic[questions]',str_replace( '&', '&amp;',$Service->questions ),["class"=>"form-control","placeholder"=>"","id"=>"questions", 'hidden'])}}
						<div class="text-danger instructions-error" style="text-align: left;" ></div>
					</div>
					<!-- /INPUT CONTAINER -->


					<div class="row">
						<div class="col-md-6">
							<div class="form-group  add-extra-detail">
								<label class="cus-checkmark">  
									{{ Form::checkbox('basic[que_is_required]', '1', $Service->que_is_required,['id'=>'que_is_required'])}}  
									<span class="checkmark"></span>
								</label>
								<div class="detail-box text-color-6">
									<label>Is Required</label>
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-lg-12">
							<div class="popular-grid ">
								<div class="seller pb-2">
									Add Questions
								</div>
							</div>
						</div>
						<div class="col-lg-12">
							<div class="table-responsive">
								<table class="table box-border" id="tbl-questions" cellpadding="0">
									<tbody>
										<tr>
											<td class="form-group">
												<input type="text" id="question_to_ask" placeholder="Add your Question" class="form-control">
											</td>
											<td class="form-group">
												<select id="answer_type" class="answer_type form-control">
													<option value="">Select Answer Type</option>
													<option value="Free Text">Free Text</option>
													<option value="Multiple Answer">Multiple Answer</option>
													<option value="Attatched File">Attatched File</option>
												</select>
											</td>
											<td class="form-group is-required">
												<div class="add-extra-detail">
													<label class="cus-checkmark">    
														<input id="is_required_question" name="is_required_question" type="checkbox" value="1">
														<span class="checkmark"></span>
													</label>
													<div class="detail-box">
														<label class="text-color-6">Is Required</label>
													</div>
												</div>
											</td>
											<td class="form-group gradient"><input type="button" class="btn add-btnquestion-button" value="Add"></td>
											<td class="form-group gradient"><input type="button" class="btn cancel-question" value="Cancel"></td>
										</tr>



										<tr class="form-group multiple-answer">
											<td class="text-center" colspan="5">
												<input type="text" name="opt_answer[]" placeholder="Optional Answer" class="form-control opt_answer" id="opt_answer_1">
											</td>
										</tr>

										<tr class="form-group multiple-answer">
											<td class="text-center" colspan="4">
												<input type="text" name="opt_answer[]" placeholder="Optional Answer" class="form-control opt_answer" id="opt_answer_2">
											</td>
											<td class="text-center">
												<button type="button" class="btn mid-short primary small-btn add-opt-ans">Add More</button>
											</td>
										</tr>

										<tr class="multiple-answer multiple-answer-clone" id="multiple-answer-clone">
											<td class="text-center" colspan="4">
												<input type="text" name="opt_answer[]" placeholder="Optional Answer" class="form-control opt_answer required" id="opt_answer_3" value="">
											</td>
											<td class="text-center">
												<button type="button" class="btn mid-short primary small-btn remove-opt-ans">Remove</button>
											</td>
											<input type="hidden" id="extra_action" value="">
											<span class="error-ans text-danger" style="display: none;">Please fill all the fields</span>
										</tr>			
										
									</tbody>
								</table>

								<table class="table box-border table-hover">
									<thead class="thead-default">
										<tr>
											<th>Question</th>
											<th>Answer Type</th>
											<th>Expected Answer</th>
											<th>Required</th>

											<th class="width125">
												<a href="javascript:void(0);" class="button mid-short dark-light add-new-question">Add New</a>
											</th>
										</tr>	
									</thead>
									<tbody id="extra-body">
										@php $extra_index = 0; @endphp
										@if(count($Service->question_list))
										@foreach($Service->question_list as $key => $row)
										<tr>
											<td class="">{{$row->question}}</td>
											<td class="">{{$row->answer_type}}</td>
											<td class="">{{($row->expacted_answer)}}</td>
											<td class="">{{$row->is_required == 'true' ? 'Yes' : 'No'}}</td>

											<td class="text-center">
												<input type="hidden" name="extra[{{$key}}][question_info]" value="{{$row->question}}">
												<input type="hidden" name="extra[{{$key}}][answer_info]" value="{{$row->answer_type}}">
												<input type="hidden" name="extra[{{$key}}][expacted_answer]" value="{{$row->expacted_answer}}">
												<input type="hidden" name="extra[{{$key}}][is_required_question]" value="{{$row->is_required}}">

												<a href="javascript:void(0);" class="edit-extra" data-question="{{$row->question}}" data-answer_type="{{$row->answer_type}}" data-expacted_answer="{{$row->expacted_answer}}" data-is_required_question="{{$row->is_required}}">
													<i class="icon-pencil"></i>
												</a> &nbsp;
												<a href="javascript:void(0);" class="remove-extra">
													<i class="icon-trash"></i>
												</a>
											</td>
										</tr>
										@php $extra_index = $key+1; @endphp
										@endforeach
										@endif
									</tbody>
								</table>

							</div>
							<!-- End Add Extra  -->

							<div class="clearfix"></div>
							<div class="col-lg-12 create-new-service update-account text-right">
								@if($Service->current_step >= 5 && $Service->uid == Auth::id())
									<button type="button" value="Save & Preview" class="btn btn-primary save_and_preview_btn">Save & Preview Service</button> 
								@endif
								@if($Service->current_step >= 4 && $Service->uid == Auth::id())
								<button type="button" class="btn btn-primary preview_requirements_btn">Preview Requirements Page</button> 
								@endif
								<button type="submit" class="btn btn-primary">Save &amp; Continue</button> 
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

	<script src="{{front_asset('js/bootbox.min.js')}}"></script>
	<script type="text/javascript">
		$(document).ready(function () {
			var edit_img = "{{front_asset('images/dashboard/edit.png')}}";
			var delete_img = "{{front_asset('images/dashboard/delete.png')}}";
			var MAX_OPTIONS = 10;

			ClassicEditor.create( document.querySelector( '#questions' ) )
			.then( newEditor => {
				req_questions_editor = newEditor;
				req_questions_editor.model.document.on( 'change:data', ( evt, data ) => {
					var descriptions =  req_questions_editor.getData();
					if(descriptions==''){
						$(".instructions-error").html('Please enter instructions');
					}else{
						$(".instructions-error").html('');
					}
				});
			})
			.catch( error => {
				console.error( error );
			});

			$("#frmReqService").submit(function(e){
				var questions =  req_questions_editor.getData();
				$(".instructions-error").html('');
				if(questions==''){
					req_questions_editor.editing.view.focus();
					$(".instructions-error").html('Please enter instructions');
					$('#questions').focus();
					return false;
				}else{
					return true;
				}
			});

			$('.multiple-answer').css('display','none');
			$('#multiple-answer-clone').css('display','none');
			$(document).on('change','#answer_type',function(){
				$('.error-ans').css('display','none');
				if($(this).val() == 'Multiple Answer'){
					$('.multiple-answer').css('display','table-row');
					$('.opt_answer').val('');
					$('.multiple-answer-clone').css('display','none');
					$('#multiple-answer-clone').css('display','none');
				}else{
					$('.multiple-answer').css('display','none');

				}
			});

			$(document).on('click','.add-opt-ans',function(){
				$template = $('#multiple-answer-clone');
				var rowId = $('.opt_answer').length;
				var $template = $('#multiple-answer-clone'),
				$clone = $template
				.clone()
				.css('display','table-row')
				.insertBefore($template)
				.removeAttr('id'),
				$option = $clone.find('[name="opt_answer[]"]');
				$option.attr('id', 'opt_answer'+ "_" + rowId);
				$option.val('');

			// template.removeAttr('id');
			if ($(document).find(':visible[name="opt_answer[]"]').length >= MAX_OPTIONS) {
				$(document).find('.add-opt-ans').attr('disabled', 'disabled');
			}
			else{
				$(document).find('.add-opt-ans').removeAttr('disabled', '');
			}
		});

			$(document).on('click', '.remove-opt-ans', function () {
				var $row = $(this).parents('.multiple-answer-clone'),
				$option = $row.find('[name="opt_answer[]"]');

				$row.remove();
				if ($(document).find(':visible[name="opt_answer[]"]').length <= MAX_OPTIONS) {
					$(document).find('.add-opt-ans').removeAttr('disabled', '');
				}
				else{
					$(document).find('.add-opt-ans').attr('disabled', 'disabled');
				}

			});

			$('#tbl-questions').hide();

			$(document).on('click','.cancel-question',function(){
				$('#tbl-questions').hide();
				$('#question_to_ask').css("border-color", '#ebebeb');
				$('#answer_type').css("border-color", '#ebebeb');

			});
			$(document).on('click','.add-new-question',function(){
				$('#tbl-questions').show();
				$('#extra_action').val('add');
				// $('.add-btnquestion-button').html('add');
				$('.add-btnquestion-button').attr("value",'Add');
				$('#multiple-answer-clone').css('display','none');
				$('.multiple-answer').css('display','none');
				$('.error-ans').css('display','none');

				clear_extra();
			});

			function clear_extra(){
				$('#question_to_ask').val('');
				$('#answer_type').val('');
				$('#is_required_question').prop('checked',false);

			}
			var extra_index = {{$extra_index}};

			$(document).on('click','.add-btnquestion-button',function(){
				var question_info = $.trim($('#question_to_ask').val());
				var answer_info = $.trim($('#answer_type').val());
				var is_required_question = $('#is_required_question').prop("checked");
				var expacted_answer_parent = $(document).find(':visible[name="opt_answer[]"]');
				var expacted_answers = [];
				var isValidate = true;

				if($('#extra_action').val()=='add'){
					var isValidate = true;
					if(expacted_answer_parent.lengt != 0){


						for(var i = 0; i < expacted_answer_parent.length; i++){
							expacted_answers[i] =$(expacted_answer_parent[i]).val();
						}	
						for(var i = 0; i <= expacted_answer_parent.length; i++){
							if(expacted_answers[i] == ''){
								isValidate = false;
								break;
							}
						}
					}
					var isChecked;
					var Checked;
					if(is_required_question){
						isChecked = 'Yes';
						Checked = 'checked';
					}else{
						isChecked = 'No';
						Checked = '';
					}
					$('#question_to_ask').css("border-color", '#ebebeb');
					if(question_info==''){
						$('#question_to_ask').css("border-color", '#8c1616');
						isValidate = false;
					}

					$('#answer_type').css("border-color", '#ebebeb');
					if(answer_info==''){
						$('#answer_type').css("border-color", '#8c1616');
						isValidate = false;
					}

					if(!isValidate){
						$('.error-ans').css('display','block');
						return false;
					}else{
						$('.error-ans').hide();
					}
					var table_tr = '<tr>'+
					'<td class="">'+question_info+'</td>'+
					'<td class="">'+answer_info+'</td>'+
					'<td class="">'+expacted_answers

					+'</td>'+
					'<td class="">'+isChecked+'</td>'+
					'<td class="text-center">'+

					'<input type="hidden" name="extra['+extra_index+'][question_info]" value="'+question_info+'">'+
					'<input type="hidden" name="extra['+extra_index+'][answer_info]" value="'+answer_info+'">'+
					'<input type="hidden" name="extra['+extra_index+'][expacted_answer]" value="'+expacted_answers+'">'+
					'<input type="hidden" name="extra['+extra_index+'][is_required_question]" value="'+is_required_question+'">'+


					'<a href="javascript:void(0);" class="edit-extra" data-question="'+question_info+'" data-answer_type="'+answer_info+'" data-expacted_answer="'+expacted_answers+'" data-is_required_question="'+is_required_question+'"><i class="icon-pencil"></i></a> &nbsp; <a href="javascript:void(0);" class="remove-extra"><i class="icon-trash"></i></a>'+
					'</td>'+
					'</tr>';

					$('#extra-body').append(table_tr);
					$('#tbl-questions').hide();
					clear_extra();
				}else{
					var isValidate = true;
					for(var i = 0; i < expacted_answer_parent.length; i++){
						expacted_answers[i] =$(expacted_answer_parent[i]).val();

					}	
					for(var i = 0; i <= expacted_answer_parent.length; i++){
						if(expacted_answers[i] == ''){
							isValidate = false;
							break;
						}
					}
					$('#question_to_ask').css("border-color", '#ebebeb');
					if(question_info==''){
						$('#question_to_ask').css("border-color", '#8c1616');
						isValidate = false;
					}

					$('#answer_type').css("border-color", '#ebebeb');
					if(answer_info==''){
						$('#answer_type').css("border-color", '#8c1616');
						isValidate = false;
					}

					if(!isValidate){
						$('.error-ans').css('display','block');
						return false;
					}

					var isChecked;
					var Checked;
					if(is_required_question){
						isChecked = 'Yes';
						Checked = 'checked';
					}else{
						isChecked = 'No';
						Checked = '';
					}
					var table_tr = 	'<td class="">'+question_info+'</td>'+
					'<td class="">'+answer_info+'</td>'+
					'<td class="">'+expacted_answers

					+'</td>'+
					'<td class="">'+isChecked+'</td>'+
					'<td class="text-center">'+

					'<input type="hidden" name="extra['+extra_index+'][question_info]" value="'+question_info+'">'+
					'<input type="hidden" name="extra['+extra_index+'][answer_info]" value="'+answer_info+'">'+
					'<input type="hidden" name="extra['+extra_index+'][expacted_answer]" value="'+expacted_answers+'">'+
					'<input type="hidden" name="extra['+extra_index+'][is_required_question]" value="'+is_required_question+'">'+


					'<a href="javascript:void(0);" class="edit-extra" data-question="'+question_info+'" data-answer_type="'+answer_info+'" data-expacted_answer="'+expacted_answers+'" data-is_required_question="'+is_required_question+'"><i class="icon-pencil"></i></a> &nbsp; <a href="javascript:void(0);" class="remove-extra"><i class="icon-trash"></i></a>'+
					'</td>';

					$('#extra-body tr.editable-row').html(table_tr);
					$('#tbl-questions').hide();
					clear_extra();
				}
				extra_index++;
			});


$(document).on('click', '.edit-extra', function() {

	$('#question_to_ask').css("border-color", '#ebebeb');
	$('#answer_type').css("border-color", '#ebebeb');
	var ans_type = $(this).attr('data-answer_type');
	var ischeck = $(this).attr('data-is_required_question');
	var ex_answer = $(this).data('expacted_answer');
	$('#multiple-answer-clone').css('display','none');
	$('.multiple-answer').css('display','none');
	$('.error-ans').css('display','none');
	if(ans_type == 'Multiple Answer'){

		$('.multiple-answer').css('display','table-row');

		ex_answer = ex_answer.replace('"','');
		ex_answer = ex_answer.split(',');
		var expacted_answers = [];

		for(var i = 0; i < ex_answer.length; i++){

			if(i>1 ){
				$template = $('#multiple-answer-clone');

				var rowId = $('.opt_answer').length;
				var $template = $('#multiple-answer-clone'),
				$clone = $template
				.clone()
				.css('display','table-row')
				.insertBefore($template)
				.removeAttr('id'),
				$option = $clone.find('[name="opt_answer[]"]');
				$option.attr('id', 'opt_answer'+ "_" + rowId);
				$option.val(ex_answer[i].replace('"',''));


				if ($(document).find(':visible[name="opt_answer[]"]').length >= MAX_OPTIONS) {
					$(document).find('.add-opt-ans').attr('disabled', 'disabled');
				}
				else{
					$(document).find('.add-opt-ans').removeAttr('disabled', '');
				}
			}
			else{
				$('#multiple-answer-clone').css('display','none');
				$('.multiple-answer-clone').css('display','none');
				$('#multiple-answer-clone').css('display','none');
				$('#opt_answer_'+(i+1)).val(ex_answer[i].replace('"',''));
			}
		}
	}
	$('.add-btnquestion-button').attr('value','Update');
	$('#tbl-questions').show();
	$('#extra_action').val('edit');
	var checked;
	if (ischeck == "true") {
		checked = "checked";
	}else{
		checked = "";
	}


	$('#question_to_ask').val($(this).data('question'));
	$('#answer_type').val($(this).data('answer_type'));	
	$('#is_required_question').prop('checked', checked);

	$('#extra-body tr.editable-row').removeClass("editable-row");
	$(this).parents("tr").addClass("editable-row");
});

$(document).on('click', '.remove-extra', function() {
	var $this = $(this);
	bootbox.confirm("Are you sure you want delete this Question?", 
		function(result){ 
			if (result == true) {
				$this.parent().parent().remove();
			}	 
		});
});

});

$('.save_and_preview_btn').on('click', function(){
	var questions =  req_questions_editor.getData();
	$(".instructions-error").html('');
	if(questions==''){
		$(".instructions-error").html('Please enter instructions');
		$('#questions').focus();
	}
	
	$('#questions').val(questions);

	$('#preview_input_id').val('preview_service');
	$.ajax({
		type: "POST",
		url: $('#frmReqService').attr('action'),
		data: $('#frmReqService').serialize(),		
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

$('.preview_requirements_btn').on('click', function(){
	var questions =  req_questions_editor.getData();
	$(".instructions-error").html('');
	if(questions==''){
		$(".instructions-error").html('Please enter instructions');
		$('#questions').focus();
	}

	$('#questions').val(questions);

	$('#preview_input_id').val('preview_requirements');
	$.ajax({
		type: "POST",
		url: $('#frmReqService').attr('action'),
		data: $('#frmReqService').serialize(),		
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