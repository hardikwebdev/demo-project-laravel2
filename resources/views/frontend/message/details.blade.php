@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Messages')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Messages</h2>
			</div>    
		</div>    
	</div>    
</section>

<section class="block-section pad-t4">
	<div class="container">
		<div class="cus-filter-data custom">
			<div class="cus-container-two chat-message-padding">    

				<!-- CONTENT -->
				<div class='col-md-12 text-right custom-margin-top'>
					<input class="custom-sucess-btn report-spam" data-toggle="modal" data-target="#custom-order-popup" type="submit" value="Report as spam">
				</div>

				<!--begin::Custom Order Modal-->
				<div class="modal fade custommodel spam-message" id="custom-order-popup" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-dialog-centered" role="document">
						<div class="modal-content">
							<div class="modal-header">
								<h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Do you want to report this message as spam?</h5>

								<button type="button" class="close" data-dismiss="modal" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>

							{{ Form::open(['route' => ['spamReport',$secret], 'method' => 'POST', 'id' => 'submitReportSpam']) }} 
							{{-- <input type="hidden" name="from_user" value="<?php echo $messageDetail[0]->to_user ?>">
							<input type="hidden" name="to_user" value="<?php echo $messageDetail[0]->from_user ?>">
							<input type="hidden" name="conversion_id" value="<?php echo $messageDetail[0]->msg_id; ?>"> --}}
							<div class="modal-body form-group">
								<div class="form-group">
									<div class="col-lg-12">
										<label for="recipient-name" class="form-control-label">By reporting this message as spam, you are notifying the demo team that you have been sent an unsolicited offer. A demo team member will promptly review and respond to this action.</label>
										<label for="recipient-name" class="form-control-label">Reason:</label>
									</div>
									<div class="col-lg-12">
										{{Form::textarea('reason','',["class"=>"form-control","placeholder"=>"Enter your reason here...","id"=>"reason",'maxlength'=>"2500",'rows' => 6])}}
										<div class="text-danger descriptions-error" id='show_error_report' style="text-align: left;display:none;" >
											<strong>Hey!</strong> Please insert some reason for spam.
										</div>
									</div>
								</div>
							</div>
							<div class="modal-footer">
								{!! Form::button('Report As Spam',['id' => 'reportSpam', 'class' => 'send-request-buttom']) !!}
								{!! Form::button('Never Mind',['id' => 'clsPopup', 'class' => 'cancel-request-buttom']) !!}
							</div>
							{{ Form::close() }}
						</div>
					</div>
				</div>
				<!--end::Custom Order Modal-->

				<!-- CONTENT -->
				<div class="content full">
					@include('frontend.message.conversations_load')
					<div class="comment-wrap comment-reply">
						<div class="input-container form-group">
							<p class="lead emoji-picker-container"></p>
							<div class="row">
								<div class="col-lg-12">
									{{ Form::open(['route' => ['msg_attachment',$secret], 'method' => 'POST','class' => 'dropzone dropzone-file-area','id'=>'attachmentdropzone','files'=>true]) }}
									{{-- <input type="hidden" name="bucket" value="{{ env('bucket_service') }}">
									<input type="hidden" name="media_type" value="attachment"> --}}

									<div class="fallback">
										<input name="attachment" type="file" id="imgupload" class="inputfile" />
									</div>
									{{Form::close()}}
								</div>
							</div>
							<br>
							{{ Form::open(['route' => ['msg_reply',$secret], 'method' => 'POST', 'id' => 'frmMessage']) }}
							<div class="input-container form-group">
								<p class="lead emoji-picker-container">
									{{Form::textarea('message','',["class"=>"form-control textarea-control","id" => "chat_message","placeholder"=>"Write your message here...",'data-emojiable' => "true"])}}
								</p>
							</div>

							{{-- Begin : Preset Template --}}
							@if(Auth::user()->is_premium_seller() == true)
							<div class="row align-items-center">
								<div class="col-lg-3">
									<div class="form-group">
										{{Form::select('select_title',[""=>"Select Template"]+$save_template_chat,null,['class'=>'form-control','id'=>'select_title_chat'])}}
									</div>
								</div>
								<div class="col-lg-6"></div>
								<div class="col-lg-3">
									{{-- Save as Template --}}
									<div class="form-group add-extra-detail">
										<label class="cus-checkmark">    
											<input id="save_template_chat" name="save_template" type="checkbox" value="1">
											<span class="checkmark"></span>
										</label>
										<div class="detail-box">
											<lable>Save As Template?</lable>
										</div>
									</div>
								</div>
							</div>
							@endif
							{{-- End : Preset Template --}}

							<div class="sponsore-form">
								<div class="update-profile-btn"> 
									<button type="submit" class="btn conversations_reply">Reply</button>
								</div>
							</div>
							
							{{ Form::close() }}
						</div>
					</div>
				</div>

				<div class="clearfix"></div>
				<div class="text-center">

				</div>
			</div>
		</div>
	</div>    

</section> 
@endsection

@if(Auth::user()->is_premium_seller() == true)
@include('frontend.seller.save_template')
@endif

@section('css')
<link rel="stylesheet" href="{{front_asset('css/emoji/emoji.css')}}">
<link rel="stylesheet" type="text/json" href="{{front_asset('css/emoji/emoji.css.map')}}">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<link href="{{url('public/frontend/assets/css/dropzone.css')}}" rel="stylesheet">
<style type="text/css">
	.pack-box {
		height: auto;
	}	
	.fullimage-custom{
		width: 218px;
		height: 218px !important;
		object-fit: cover;
	}
	.user-image img{
		width: 15px;
	}
	.admin_profile {
		border-radius: 0% !important;
	}
</style>
@endsection

@section('scripts')
<script src="{{url('public/frontend/assets/js/dropzone.js')}}" type="text/javascript"></script>
<script src="{{front_asset('js/emoji/config.js')}}"></script>
<script src="{{front_asset('js/emoji/util.js')}}"></script>
<script src="{{front_asset('js/emoji/jquery.emojiarea.js')}}"></script>
<script src="{{front_asset('js/emoji/emoji-picker.js')}}"></script>
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type="text/javascript">
	var msg_box = $('.comment-wrap').height();
	var line = 40;
	$(document).ready(function () {
		$("html, body").animate({ scrollTop: ($('.comment-reply').offset().top - (msg_box + line)) }, 1000);
		/* $(document).on('click','.conversations_reply',function(e){
			e.preventdefault();
		}); */
	});
</script>  
<script>
	$(function() {
		window.emojiPicker = new EmojiPicker({
			emojiable_selector: '[data-emojiable=true]',
			assetsPath: "{{front_asset('img/emoji/')}}",
			popupButtonClasses: 'fa fa-smile-o'
		});
		window.emojiPicker.discover();

		$('.spam-message').magnificPopup({
			type: 'inline',
			removalDelay: 300,
			mainClass: 'mfp-fade',
			closeMarkup: '<div class="close-btn mfp-close" style="display:none;"><svg class="svg-plus"><use xlink:href="#svg-plus"></use></svg></div>'
		});
		$('#reportSpam').on('click',function(){
			var reason = $('#reason').val();
			if(reason == ''){
				$('#show_error_report').fadeIn(500);
			}else{
				$('#submitReportSpam').submit();
			}
		});
		$('#clsPopup').on('click',function(){
			$('#custom-order-popup').modal('hide');
			$("#submitReportSpam")[0].reset();
			$('#show_error_report').hide();
		});

	});

	Dropzone.options.attachmentdropzone = {
			maxFilesize: 100,
			// autoProcessQueue: false,
			// acceptedFiles: "jpeg,.jpg,.png,.gif",
			uploadMultiple: false,
			parallelUploads: 50,
			paramName: "attachment",
			addRemoveLinks: true,
			dictFileTooBig: 'Image is larger than 100MB',
			maxFiles: 1,
			timeout: 10000000,

			init: function () {
				var msg = 'Maximum File Size 100MB';
				$('#attachmentdropzone .dz-message').append('<br><p class="text-secondary">('+msg+')</p>');
			},
			success: function (file, done) {
				location.reload();
			}
		};
	</script>
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

		ga('create', 'UA-49610253-3', 'auto');
		ga('send', 'pageview');
	</script>
	@endsection