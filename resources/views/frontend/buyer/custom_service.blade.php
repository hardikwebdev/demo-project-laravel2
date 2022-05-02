@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Custom Orders')
@section('content')

<section class="profile-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row">
				<div class="container cus-filter">
					<h2 class="heading mb-3">Custom Order Request</h2>
				<div class="col-md-12 pad0">
					<div class="custom-order-request-heading"><span>{{count($Service)}}</span> Custom Order Request Found</div>
				</div>
				</div>    
			</div>    
		</div>    
	</div>
</section>

<!--begin::Decline Offer Modal-->
<div class="modal fade custommodel" id="decline-order-popup" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Decline Offer</h5>

				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			{!! Form::open(['route' => ['decline_order_process'],'method' => 'POST', 'id' => 'frmDeclineOffer']) !!}
			<div class="modal-body form-group">

				<input type="hidden" name="id" value="" id="service_id">
				<input type="hidden" name="action_type" value="" id="action_type">
				
				<div class="input-container form-group">
					<label class="rl-label required">If you would like to ask the seller to revise their offer terms, please explain below:</label>
					{{Form::textarea('revised_order_desc','',["class"=>"form-control","placeholder"=>"Explain here...",'id'=>'explain_desc','rows' => 6])}}
					<div class="text-danger explain_desc-error" style="text-align: left;" ></div>
				</div>
			</div>
			<div class="modal-footer">
				{!! Form::button('No Thanks, Decline Offer',['class' => 'custom-sucess-btn tertiary nothanks-action btnDeclineSubmit','name' => 'action', 'value' => 'decline']) !!}
				{!! Form::button('Ask Seller For Revised Offer',['class' => 'custom-danger-btn btnDeclineSubmit','name' => 'action', 'value' => 'revised']) !!}
			</div>
			{{ Form::close() }}
		</div>
	</div>
</div>

<section class="custom-order-section sponsored-section block-section">
	<div class="container">
		<div class="cus-filter-data">
			<div class="cus-container-two">    
				<div class="table-responsive">
					<table class="manage-sale-tabel custom">
						<thead>
							<tr class="manage-sale-head custom-bold-header">
								<td class="text-center">Seller</td>
								<td class="text-center">Service Description</td>
								<td class="text-center">Requested Date</td>
								<td class="text-center">Delivery Days</td>
								<td class="text-center">Price</td>
								<td class="text-center">Status</td>
							</tr>
						</thead>
						<tbody>
							@foreach($Service as $row)
							<tr>
								<td class="text-center">
									<a class="default-gray" href="{{route('viewuserservices',[$row->seller->username])}}">{{$row->seller->username}}</a>
								</td>
								<td class="text-center">
									<a class="default-gray" href="{{route('buyer_custom_order_details',[$row->seo_url])}}">
										@if(strlen($row->descriptions) > 60)
										{!! nl2br(substr($row->descriptions, 0, 60)) !!}...
										@else
										{!! nl2br($row->descriptions) !!}
										@endif
									</a>
								</td>
								<td class="text-center">
									{{date('d M Y',strtotime($row->created_at))}}
								</td>
								<td class="text-center">
									{{($row->basic_plans->delivery_days !=0)?$row->basic_plans->delivery_days.' days':'--'}}
								</td>
								<td class="text-center">
									{{isset($row->basic_plans->price)?'$'.$row->basic_plans->price:''}}
								</td>
								<td class="custom-staus-{{$row->id}} text-center" style="width: 300px;">
									@if($row->custom_order_status == 0)
										<span class="pending">Waiting for response from {{$row->seller->username}}</span>
									@elseif($row->custom_order_status == 5)
										<span class="pending">Custom offer revised, Waiting for response from {{$row->seller->username}}</span>
									@elseif($row->custom_order_status == 1)
										{{ Form::open(['route' => ['payment'], 'method' => 'POST','style'=>'display: inline-block;']) }}

										<input type="hidden" name="is_custom_order" value="{{$row->id}}">
										<button type="submit" class="custom-sucess-btn">Accept Offer</button>
										{{ Form::close() }}
										<button type="button" class="custom-danger-btn open-decline-order" data-toggle="modal" data-target="#decline-order-popup" data-id="{{$row->id}}">Decline Offer</button>
									@elseif($row->custom_order_status == 2)
										<span class="inprogress">Custom offer decline by {{$row->seller->username}}</span>
									@elseif($row->custom_order_status == 4)
										<span class="inprogress">You declined this custom offer request</span>	
									@elseif($row->custom_order_status == 3)
										<span class="completed">Custom offer accepted</span>
									@endif
								</td>
							</tr>
							@endforeach
							@if(count($Service)==0)
							<tr>
								<td colspan="7" class="text-center">
									No request found
								</td>
							</tr>
							@endif

						</tbody>
					</table>
					<div class="clearfix"></div>

					<!-- PAGER -->
					<div class="text-center">{{ $Service->links() }}</div>
				</div>
			</div>
		</div>
	</div>        
</section>  

@endsection

@section('scripts')
<!--Bootbox-->
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type="text/javascript">

	$(document).on('click', '.change-status-action', function(e){
		var id = $(this).data('id');
		var url = $(this).data('url');
		var status = $(this).data('status');

		$this = $(this);
		if(status == 1){
			var cMessage = "Are you sure you want to accept this request?";
		}else{
			var cMessage = "Are you sure you want to reject this request?";
		}
		bootbox.confirm(cMessage, function(result){ 
			if(result == true){
				$.ajax({
					type: "POST",
					url: url,
					data: {"_token": _token, 'id': id,'status':status},
					success: function (data){
						if(data.code == 200){
							$('.custom-staus-'+id).html(data.action);
							alert_success(data.message);
						}else{
							alert_error(data.message);
						}
					}
				});
			}
		});
	});
	/*Create Custom Quote*/

	/*$('.open-decline-order').magnificPopup({
		type: 'inline',
		removalDelay: 300,
		mainClass: 'mfp-fade',
		closeMarkup: '<div class="close-btn mfp-close"><svg class="svg-plus"><use xlink:href="#svg-plus"></use></svg></div>'
	});*/
	
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
</script>
@endsection