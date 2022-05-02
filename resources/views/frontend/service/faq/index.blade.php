@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Service FAQ')
@section('content')
@include('frontend.service.header')

<section class="transactions-table pad-t4">
	<div class="container">
        @include('layouts.frontend.messages')
		<div class="row cus-filter align-items-center">
			<div class="col-md-7 col-12 pad0">
				<div class="transactions-heading">{{ count($faqs) }} FAQs Found (<span class="text-capitalize">{{$Service->title}}</span>)
				</div>
			</div>
			<div class="col-md-5 col-12 pad0">
				<div class="sponsore-form service-filter">
					<div class="create-new-service"> 
						<a href="javascript:void(0)" class="button primary add_new_faq_btn" style="margin: 0px;" data-service="{{$Service->seo_url}}"><button type="button" class="btn">Add New FAQ</button></a>
                    </div>
                    @if($Service->current_step >= 5)
                    <div class="create-new-service"> 
                        <a href="{{ route('service_publish',$Service->seo_url) }}" class="button primary" style="margin: 0px;"><button type="button" class="btn">Publish</button></a>
                    </div>
                    @endif
				</div>    
			</div>
		</div>

	</div>        
</section> 

<section class="custom-block-section">
	<div class="container mt-2">
        @if(count($faqs) > 0)
        <div class="pt-4 row">
            @foreach ($faqs as $faq_item)
            <div class="col-md-4 mb-2">
            <div class="card pr-0 pl-0 h-100 faq_card_main">
                <div class="card-body custom_scroll faq_card">
                    <h5 class="card-title">{{ $faq_item->question }}</h5>
                    {!! $faq_item->answer !!}
                </div>
                <div class="card-footer">
                    <div class=" float-right">
                        <a href="javascript:void(0)" class="card-link faq_link btn btn-primary edit_faq_btn" data-id="{{$faq_item->secret}}">Edit</a>
                        <button type="button" data-url="{{ route('delete_faq',$faq_item->secret) }}" class="card-link faq_link btn btn-danger delete_faq">Delete</button>
                    </div>
                </div>
            </div>
            </div>
            @endforeach
        </div>
        @else 
        <div class="text-center pt-5"><h4>No FAQ added</h4></div>
        @endif
    </div>
</section>

<div id="add_faq_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New FAQ</h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            {{ Form::open(['route' => ['save_faq'], 'method' => 'POST', 'id' => 'create_faq']) }}
            <input type='hidden' name='service_seo' id="add_faq_service_id">
            <div class="modal-body">
                <div class="row" id="userprofile">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label>Question <span class="text-danger">*</span></label>
                            {{Form::text('question',null,["class"=>"form-control required","placeholder"=>"Question","autocomplete"=>"off","id"=>"question"])}}
                            <div class="text-danger question-error" style="text-align: left;" ></div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label>Answer <span class="text-danger">*</span></label>
                            {{Form::textarea('answer',null,["class"=>"form-control","placeholder"=>"Answer","id"=>"answer"])}}
                            <div class="text-danger answer-error" style="text-align: left;" ></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="col-md-2">
                    <button type="submit" class="btn send-request-buttom float-right">Add FAQ</button>
                </div>
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>

<div id="edit_faq_modal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit FAQ</h5>
                <button type="button" class="close" data-dismiss="modal">×</button>
            </div>
            {{ Form::open(['route' => ['update_faq'], 'method' => 'POST', 'id' => 'editfaq']) }}
            <input type='hidden' name='id' id="edit_faq_id">
            <div class="modal-body">
                <div class="row" id="userprofile">
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label>Question <span class="text-danger">*</span></label>
                            {{Form::text('question',null,["class"=>"form-control required","placeholder"=>"Question","autocomplete"=>"off","id"=>"edit_question"])}}
                            <div class="text-danger edit-question-error" style="text-align: left;" ></div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label>Answer <span class="text-danger">*</span></label>
                            {{Form::textarea('answer',null,["class"=>"form-control","placeholder"=>"Answer","id"=>"edit_answer"])}}
                            <div class="text-danger edit-answer-error" style="text-align: left;" ></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="col-md-2">
                    <button type="submit" class="btn send-request-buttom float-right">Edit FAQ</button>
                </div>
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>
@endsection 

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type='text/javascript'>
$(document).ready(function() {

    /* Add Service FAQ */
    ClassicEditor.create( document.querySelector( '#answer' ) )
    .then( newEditor => {
        req_questions_editor = newEditor;
        req_questions_editor.model.document.on( 'change:data', ( evt, data ) => {
            var answer =  req_questions_editor.getData();
            if(answer == ''){
                req_questions_editor.editing.view.focus();
                $(".answer-error").html('Please enter answer');
            }else{
                $(".answer-error").html('');
            }
        });
    })
    .catch( error => {
        console.error( error );
    });
    /* END Add Service FAQ */
    
    /*Edit Service FAQ */
    ClassicEditor.create( document.querySelector( '#edit_answer' ) )
    .then( newEditor => {
        desc_editor = newEditor;
        desc_editor.model.document.on( 'change:data', ( evt, data ) => {
            var answer =  desc_editor.getData();
            if(answer == ''){
                desc_editor.editing.view.focus();
                $(".edit-answer-error").html('Please enter answer');
            }else{
                $(".edit-answer-error").html('');
            }
        });
    })
    .catch( error => {
        console.error( error );
    });
    /* END Edit Service FAQ */

    $("#create_faq").submit(function(e){
        $(".answer-error").html('');
        $(".question-error").html('');
        
        var question = $('#question').val();
        var answer = req_questions_editor.getData();
        if(question=='' || answer==''){
            if(question=='') {
                $(".question-error").html('Please enter question');
                $('#question').focus();
            }
            if(answer==''){
                req_questions_editor.editing.view.focus();
                $(".answer-error").html('Please enter answer');
            }
            return false;
        }
    });
        
    $('.delete_faq').on('click', function(){
        var url = $(this).data('url');
        bootbox.confirm("Are you sure you want to delete this FAQ?", function(result){ 
			if (result) {
				window.location.href = url;
			}	 
		});
    });

    $('.add_new_faq_btn').on('click', function() {
        $('#add_faq_service_id').val($(this).data('service'));
        $('#add_faq_modal').modal('show');
    });

    $('#add_faq_modal').on('hidden.bs.modal', function () {
        $('#question').val('');
        req_questions_editor.setData('');
        /* Sleep 0.5 second */
        setTimeout(() => {
            $(".answer-error").html('');
        }, 500);
    });

    $('.edit_faq_btn').on('click', function() {
        var id = $(this).data('id');
        $.ajax({
			url : "{{route('get_faq_details')}}",
			data : {'_token':"{{ csrf_token() }}",'id' :id},
			type : 'post',
			success : function(res){
                if(res.status == 'success') {
                    var data = res.data;
                    $('#edit_faq_id').val(data['id']);
                    $('#edit_question').val(data['question']);
                    //$('#edit_answer').html(data['answer']);
                    desc_editor.setData(data['answer']);
                    $('#edit_faq_modal').modal('show');
                }
            }
        });
    });

    $("#editfaq").submit(function(e){
        $(".edit-answer-error").html('');
        $(".edit-question-error").html('');
        var question = $('#edit_question').val();
        var answer =  desc_editor.getData();
        if(question=='' || answer==''){
            if(question=='') {
                $(".edit-question-error").html('Please enter question');
                $('#edit_question').focus();
            }
            if(answer==''){
                $(".edit-answer-error").html('Please enter answer');
                if(question!='') {
                    desc_editor.editing.view.focus();
                }
            }
            return false;
        }
    });
});
</script>
@endsection