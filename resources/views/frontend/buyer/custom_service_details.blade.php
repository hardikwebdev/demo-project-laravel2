@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Custom Order Details')
@section('content')
<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Custom Order Details</h2>
			</div>    
		</div>    
	</div>    
</section>



<section class="cart-block">
	<div class="container">
		<div class="row"> 
			<div class="col-lg-12"> 
				<h3 class="cart-title">Custom Order Details</h3>
				<h7 class="order-txt">Seller: <a href="{{route('viewuserservices',$Service->seller->username)}}">{{$Service->seller->username}} </a> | {{date('M d,Y',strtotime($Service->created_at))}}</h7>
			</div>
		</div>
		<div class="row"> 
			<div class="col-lg-8 col-md-12 col-12"> 

				<div class="table-responsive">
					<table class="manage-sale-tabel payment-table payment-table-mb">
						<thead class="thead-default">
							<tr class="manage-sale-head">
								<td colspan="2">Description</td>
								<td>Package</td>
								<td>Duration</td>
								<td>Amount</td>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>{!!nl2br($Service->descriptions)!!}</td>
								<td></td>
								<td>Custom Order</td>
								<td>{{($Service->basic_plans->delivery_days !=0)?$Service->basic_plans->delivery_days.' days':'--'}}</td>
								<td>${{$Service->basic_plans->price}}</td>
							</tr>
							<tr>
								<td></td>
								<td></td>
								<td></td>
								<td>Total</td>
								<td>${{$Service->basic_plans->price}}</td>
							</tr>
						</tbody>
					</table>
				</div>

				@if(isset($Service->order->order_note) && $Service->order->order_note!= '')
				<div class="form-box-item not-padded">
					<div class="text-center default-question-answer">
						<strong>Service Requirements</strong>
					</div>
					<div class="text-center default-question-answer">
						<?php echo nl2br($Service->order->order_note); ?>
					</div>
				</div>
				@endif

			</div>
			<div class="col-lg-4 col-md-12 col-12"> 
				<div>

					@if($Service->custom_order_status == 1)
					<table class="manage-sale-tabel payment-table payment-table-mb">
						<thead class="thead-default">
							<tr class="manage-sale-head">
								<td colspan="2" class="text-center">Order Actions</td>
							</tr>
						</thead>
					</table> 
					<div class="text-center text-success ">
						<div class="prompt-btn custom text-center"> 

							{{ Form::open(['route' => ['payment'], 'method' => 'POST']) }}
							<input type="hidden" name="is_custom_order" value="{{$Service->id}}">
							<a href="javascript:void(0)">
								<button type="submit" class="send-request-buttom custom-width-max custom-margin-top">Accept Offer</button>
							</a>

							<a href="javacript:void(0)" class="open-decline-order" data-id="{{$Service->id}}" data-toggle="modal" data-target="#decline-order-popup">
								<button type="button" class="cancel-request-buttom custom-width-max custom-margin-top">Decline Offer</button>  
							</a>
							{{ Form::close() }}
						</div>
					</div>

					<!-- /START - Decline ORDER POPUP -->
					<div class="modal fade custommodel" id="decline-order-popup" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
						<div class="modal-dialog modal-dialog-centered" role="document">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Decline Offer</h5>

									<button type="button" class="close" data-dismiss="modal" aria-label="Close">
										<span aria-hidden="true">&times;</span>
									</button>
								</div>

								{{ Form::open(['route' => ['decline_order_process'], 'method' => 'POST', 'id' => 'frmDeclineOffer']) }} 
								<input type="hidden" name="id" value="" id="service_id">
								<input type="hidden" name="action_type" value="" id="action_type">

								<div class="modal-body form-group">
									<div class="col-lg-12">
										<label class="rl-label required">If you would like to ask the seller to revise their offer terms, please explain below:</label>
										{{Form::textarea('revised_order_desc','',["class"=>"form-control","placeholder"=>"Explain here...",'id'=>'explain_desc','rows' => 7])}}
										<div class="text-danger explain_desc-error" style="text-align: left;" ></div>
									</div>
								</div>
								<div class="modal-body form-group">
									<div class="col-lg-12">
										<a href="javascript:void(0)">
											<button type="button" name="action" value="decline" class="cancel-request-buttom custom-width-max custom-margin-top btnDeclineSubmit">No Thanks, Decline Offer</button> 
										</a>

										<a href="javacript:void(0)" class="open-decline-order" data-id="{{$Service->id}}" data-toggle="modal" data-target="#decline-order-popup">
											<button type="button" name="action" value="revised" value="revise" class="send-request-buttom custom-width-max custom-margin-top btnDeclineSubmit">Ask Seller For Revised Offer</button>
										</a>
									</div>
								</div>
								{{ Form::close() }}
							</div>
						</div>
					</div>
					<!-- /END - Decline ORDER POPUP  -->

					@else

					<div class="text-center text-success">
						@if($Service->custom_order_status == 0)
						<div class="alert alert-success  mb-3">
							<button class="close" data-close="alert"></button>
							<span>Waiting for response from {{$Service->seller->username}}</span>
						</div>
						@elseif($Service->custom_order_status == 2)
						<div class="alert alert-danger alert-dismissible mb-3">
							<strong>Custom offer decline by {{$Service->seller->username}}</strong> 
						</div>
						@elseif($Service->custom_order_status == 4)
						<div class="alert alert-danger alert-dismissible mb-3">
							<strong>You declined this custom offer request</strong>
						</div>
						@elseif($Service->custom_order_status == 3)
						<div class="alert alert-success  mb-3">
							<button class="close" data-close="alert"></button>
							<span>Custom offer accepted</span>
						</div>
						@elseif($Service->custom_order_status == 5)
						<div class="alert alert-success  mb-3">
							<button class="close" data-close="alert"></button>
							<span>Custom offer revised,Waiting for respond by {{$Service->seller->username}}</span>
						</div>
						@endif
					</div>

					@endif

				</div>
				{{--  --}}
			</div>
		</div>
	</div>
</section>  



<!-- /SECTION -->

@endsection

@section('scripts')
<!--Bootbox-->
<script src="{{front_asset('js/bootbox.min.js')}}"></script>

<script type="text/javascript">
	$('#descriptions').keyup(function () {
		var length = $(this).val().length;
		$('#chars_desc').text(length);
	});
	/*Create Custom Quote*/
	/*$('.open-decline-order').magnificPopup({
		type: 'inline',
		removalDelay: 300,
		mainClass: 'mfp-fade',
		closeMarkup: '<div class="close-btn mfp-close"><svg class="svg-plus"><use xlink:href="#svg-plus"></use></svg></div>'
	});
	*/
	$(".open-decline-order").click(function(){
		var id = $(this).data('id'); 
		$('#service_id').val(id);
	});

	$('.btnDeclineSubmit').click(function () {
		$('.explain_desc-error').html('');
		$('#action_type').val(this.value);
		if(this.value == 'revised'){
			var explain_desc = $.trim($('#explain_desc').val());
			if (explain_desc  === '') {
				$('.explain_desc-error').html('Descriptions is required.');
				return false;
			}
		}
		$('#frmDeclineOffer').submit();
	});
	$('#cancel_note').keyup(function() {
		var length = $(this).val().length;
		$('#cancel_note_chars').text(length);
	});

</script>
@endsection