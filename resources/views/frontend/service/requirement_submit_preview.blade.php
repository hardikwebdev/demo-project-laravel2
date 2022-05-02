@extends('layouts.frontend.main')
@section('pageTitle','demo - Service Requirements Preview')
@section('content')
<!-- Display Error Message -->
@include('layouts.frontend.messages')

<section class="pricing-section  transactions-table py-5">

    <div class="container font-lato">   
        <div class="row">
            <div class="col-12 col-md-8 col-lg-9 pr-md-0 pr-xl-3">    
                <div class="row pt-3 pb-2">
                    <div class="col-12">
                        <h1 class="font-30 text-color-2 font-weight-bold text-capitalize">{{$service->title}}</h1>
                    </div>
                </div>
                
                <hr/>

                @if($service->is_custom_order==0 && $service->is_job==0)
                <div class="row pt-2">
                    <div class="col-12">
                        <p class="mb-0 font-16 text-color-3 font-weight-bold">Thank you for purchasing our product</p>
                        <div>{!! $service->questions !!}</div>
                    </div>
                </div>

				<hr/>
                @endif

                <div class="row pt-2">
                    <div class="col-12 col-lg-9">
                        @if($service->que_is_required)
							<div class="input-container form-group">
								<p class="mb-2 font-16 font-weight-bold text-color-2">
									Enter Requirement <span class="text-danger">*</span>
								</p>
								{{Form::textarea('order_note','',["class"=>"form-control required","placeholder"=>"", "id"=>"order_note", "rows"=>"4",'disabled'])}}
							</div>
                        @else
							<div class="input-container form-group">
								<p class="mb-2 font-16 font-weight-bold text-color-2">
									Enter Requirement
								</p>
								{{Form::textarea('order_note','',["class"=>"form-control","placeholder"=>"", "id"=>"order_note", "rows"=>"4",'disabled'])}}
							</div>
                        @endif
                    </div>
                </div>

				<div class="row mt-4 mx-0">
                    <div class="col-12 col-lg-9 px-0 summary">
                        <div id="accordion">
                            <div class="card card-bark-mode border-0 px-3">
                                <div class="py-3" id="headingTwo">
                                    <div class="d-flex flex-column flex-md-row align-items-end align-items-md-center justify-content-between ">
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <img src="{{url('public/frontend/images/paperclip.png')}}" class="bg-light-gray-f0 p-3 rounded-circle" alt="">
                                            </div>
                                            <div class="ml-3">
                                                <p class="mb-0 font-18 text-color-2 font-weight-bold">Attachments</p>
                                                <p class="mb-0 font-16 text-color-3">You have <span class="text-color-2 font-weight-bold total_attach_files">0</span> files in attachments</p>
                                            </div>
                                        </div>
                                        <h5 class="mb-0">
                                            <button type="button" class="btn font-14 text-color-1 bg-transparent font-weight-bold arrow-down-btn" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseOne">
                                                Show All Attachments
                                                <i class="fas fa-chevron-down arrow-down"></i>
                                            </button>
                                        </h5>
                                    </div>
                                </div>

                                <div id="collapseThree" class="collapse border-top-gray" aria-labelledby="headingTwo" data-parent="#accordion">
                                    <div class="card-body px-2">
                                        <div class="delivery-now mt-3">
                                            <div id="requirementUpload_file" class="dropzone dropzone-file-area">
												<div class="fallback">
													<input name="file" type="file" id="file1" class="hide" />
												</div>
												<div class="dz-message needsclick">
													<span class="text">
														<img src="{{url('public/frontend/images/upload-cloud.png')}}" alt="">
														<h1 class="pt-2 mb-1 font-20 text-color-4 font-weight-normal">Drop files here or  <span class="text-color-1">browse</span></h1> 
														<h3 class="font-14 text-color-4 font-weight-normal">Maximum file size 100 MB </h3>
													</span>
												</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(count($questions))
                @foreach($questions as $row => $val)
                    <div class="row mt-4">
						<div class="col-12 col-lg-9">
							<p class="mb-2 font-16 font-weight-bold text-color-2 mt-2">{{ucfirst($val->question)}}
								@if($val->is_required == 'true')
								<span class="text-danger">*</span>
								@endif
							</p>
							@if($val->answer_type == "Multiple Answer")
								@php
								$ans = ($val->expacted_answer);
								$ans_data= explode(',', $ans);
								@endphp
								<div class="form-check">
								@for ($i =0 ; $i < count($ans_data) ; $i++)
									<label class="form-check-label font-14 text-color-2 @if($i>0) d-block @endif" data-val="amount">
										<input type="radio" name="ans_{{$val->id}}" value="{{$ans_data[$i]}}" class="form-check-input mt-1" disabled>
										<span>{{$ans_data[$i]}}</span>
									</label>
								@endfor
								</div>
							@elseif($val->answer_type == "Attatched File")
								<div class="dropzone dropzone-file-area requirement_attachment">
									<div class="dz-message needsclick">
										<span class="text">
											<img src="{{url('public/frontend/images/upload-cloud.png')}}" alt="">
											<h1 class="pt-2 mb-1 font-20 text-color-4 font-weight-normal">Drop files here or  <span class="text-color-1">browse</span></h1> 
											<h3 class="font-14 text-color-4 font-weight-normal">Maximum file size 100 MB </h3>
										</span>
									</div>
								</div>
							@else
								<textarea maxlength="500" name="ans_{{$val->id}}" placeholder="Your Answer" class="form-control" disabled></textarea>
							@endif
						</div>
                    </div>
                @endforeach
                @endif

                <hr>

                <div class="row">
                    <div class="col-12">
                        <div class="form-check">
                            <label class="form-check-label font-16 text-color-2">
                            <input type="checkbox" class="form-check-input" value="checkedValue" disabled>
                            The information I provided is accurate and complete. Any changes will require seller’s approval, and may be subject to additional costs.
                            </label>
                        </div>
                    </div>

                </div>
            </div>
                        
            <div class="col-12 col-md-4 col-lg-3">
                <div class="row mt-4 mt-md-0">
                    <div class="col-12">
                        <div class="summary">
                            <img src="{{get_service_image_url($service)}}" class="img-fluid w-100" alt="">
                            <div class="px-3 py-2">
                                <p class="text-color-2 py-2 font-16 font-weight-bold mb-0">{{$service->title}}</p>
                            </div> 
                        </div>
                    </div>
                </div>
            </div>       
        </div>
    </div>
</section>

@endsection

@section('css')
<link href="{{front_asset('assets/css/dropzone.css')}}" rel="stylesheet">
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="{{url('public/frontend/css/price_range_style.css')}}"/>

<style>
	.custom-button{
		/*float: right;*/
		max-width: 25%;
		margin-left: 10px;
	}
	.order-success-button{
		display: -webkit-box;
		margin-left: 30%;
	}
	.order-success-message{
		display: none;
	}
	@media only screen and (max-width: 600px) {

		#order-details{
			width: 100%;
			display: block;
			overflow-x: auto;
		}
	}
	.dropzone .dz-preview .dz-error-message {
		top: 148px !important;
	}
	#toast-container {
		padding-top: 30px !important;
	}
</style>
@endsection


@section('scripts')

<script type="text/javascript" src="{{front_asset('bootstrap/dist/js/bootstrap-tagsinput.js')}}"></script> 
<script type="text/javascript" src="{{url('public/frontend/js/price_range_script.js')}}"></script>

<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script src="{{ web_asset('plugins/jquery-validation/js/jquery.validate.min.js')}}" type="text/javascript"></script>
<script src="{{ web_asset('plugins/jquery-validation/js/additional-methods.min.js')}}" type="text/javascript"></script>
<script src="{{front_asset('assets/js/dropzone.js')}}" type="text/javascript"></script>
<script>

	/* Deliver Order */
	ClassicEditor.create( document.querySelector( '#order_note' ) )
	.then( newEditor => {
		desc_editor = newEditor;
		desc_editor.isReadOnly = true;
	})
	.catch( error => {
		console.error( error );
	});
	
	toastr.info('', 'Previewing Your Requirements Form',{
		"closeButton": false,
		"positionClass": "toast-top-center",
		"timeOut": "0",
  		"extendedTimeOut": "0",
	});
    Dropzone.autoDiscover = false;
	$(document).ready(function(){
		//Common Upload file
	    $("#requirementUpload_file").dropzone({
	        url: '{{route('upload_files_s3')}}',
			parallelUploads: 100,
			maxFilesize: 100,
			dictFileTooBig: 'File is bigger than 100MB',
			addRemoveLinks: true,
            clickable: false,
			init: function() {
			},
			error: function(file, response) {
			},
			success: function(file,data) {
			}
	    });
		
		$(".requirement_attachment").dropzone({
	        url: '{{route('upload_temp_file')}}',
			maxFilesize: 100,
			maxFiles: 1,
			dictFileTooBig: 'File is bigger than 100MB',
			addRemoveLinks: true,
            clickable: false,
			init: function() {
			},
			error: function(file, response) {
			},
			success: function(file,data) {
			}
	    });
	});
</script>
@endsection