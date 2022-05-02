@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Buyer Transactions')
@section('content')


<!-- Get Project -->

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Buyer Transactions</h2>
			</div>    
		</div>    
	</div>    
</section>



<section class="get-project transactions-section">
	<div class="container">

		@include('layouts.frontend.messages')

		<div class="row">
			<div class="col-cus-5 col-md-3 col-sm-12">
				<div class="project-block text-center">
					<div class="project-number">
						<span>$</span>{{number_format($purchage,2)}}
					</div>
					<div class="project-detail">
						<div class="project-title">Purchases</div>
					</div>
				</div>
			</div>
			<div class="col-cus-5 col-md-3 col-sm-12">
				<div class="project-block text-center">
					<div class="project-number">
						<span>$</span>{{number_format($activeOrderPurchage,2)}}
					</div>
					<div class="project-detail">
						<div class="project-title">Active Orders Purchased</div>
					</div>
				</div>
			</div>
			{{-- <div class="col-cus-5">
				<div class="project-block text-center">
					<div class="project-number">
						<span>$</span>{{number_format($completedOrderPurchage,2)}}
					</div>
					<div class="project-detail">
						<div class="project-title">Completed Orders Purchased</div>
					</div>
				</div>
			</div> --}}
			<div class="col-cus-5 col-md-3 col-sm-12">
				<div class="project-block text-center">
					<div class="project-number">
						<span>$</span>{{number_format($myBalance,2)}}
					</div>
					<div class="project-detail">
						<div class="project-title">Personal Balance</div>
					</div>
				</div>
			</div>
			<div class="col-cus-5 col-md-3 col-sm-12">
				<div class="project-block text-center">
					<div class="project-number">
						<span>$</span>{{number_format($sponseredAmount,2)}}
					</div>
					<div class="project-detail">
						<div class="project-title">Sponsored Services</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- Get Project -->


<!-- popular service -->
<section class="block-section transactions-table">
	<div class="container">

		<div class="row cus-filter align-items-center withdraw-box">
			<div class="col-md-4 col-5 pad0">
				<div class="transactions-heading"><span>Deposit Amount To Wallet</div>
			</div>

			<div class="col-md-8 col-7 pad0">
				<div class="sponsore-form">
					<div class="update-profile-btn"> 
						<div class="m-dropdown m-dropdown--inline m-dropdown--arrow m-dropdown--align-right m-dropdown--align-push">
							<button type="button" class="btn" data-toggle="modal" data-target="#add_money_to_wallet_model">Deposit Amount To Wallet</button>
						</div>	
					</div>
				</div>    
			</div>

			<div class="modal fade custommodel-new" id="add_money_to_wallet_model" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						<div class="modal-header req-withdrawal-header">
							<h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Deposit Amount To Wallet</h5>

							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>

						{!! Form::open(['route' => ['paypal_add_money_to_wallet'],'method' => 'post', 'id' => 'paypal_add_money_to_wallet']) !!}
						<div class="modal-body">
							<div class="col-lg-12">
								<div id="withdraw_response"></div>
							</div>

							<div class="col-lg-12">
								<label for="recipient-name" class="form-control-label">Payment From</label>
							</div>

							<div class="col-lg-12">
								<div class="form-group">

									<select class="form-control" name="request_from" id="deposite_from">
										<option value="1">Paypal</option>
										<option value="3">Skrill</option>
										@if(auth()->user()->earning < env('CC_MIN_WALLET_AMT_REQ_TO_DEPOSITE'))
										<option value="2">Credit Card</option>
										@endif
									</select>
								</div>
							</div>

							<div class="col-lg-12">
								<label for="recipient-name" class="form-control-label">Enter The Amount You Wish To Deposit to Wallet</label>
							</div>

							<div class="col-lg-12">
								<div class="form-group">
									<div class="input-group">
										<div class="input-group-prepend">
											<span class="input-group-text group-before-text"> &nbsp;$&nbsp; </span>
										</div>
										{!! Form::text('wallet_amount', null,['class' => 'form-control','maxlength'=>'10','placeholder' => 'Enter The Amount You Wish To Deposit to Wallet']) !!}
									</div>
								</div>
							</div>
							<hr>
							<div class="help text-center">or select an amount below</div>

							<div class="col-lg-12 text-center deposite_from_paypal">
								<button type="button" class="wallet-price round-button" data-price="100">$100</button>
								<button type="button" class="wallet-price round-button" data-price="500">$500</button>
								<button type="button" class="wallet-price round-button" data-price="1000">$1000</button>
								<button type="button" class="wallet-price round-button" data-price="5000">$5000</button>
								<button type="button" class="wallet-price round-button" data-price="10000">$10,000</button>
							</div>
							<div class="col-lg-12 text-center deposite_from_cc hide">
								<button type="button" class="wallet-price round-button" data-price="50">$50</button>
								<button type="button" class="wallet-price round-button" data-price="100">$100</button>
								<button type="button" class="wallet-price round-button" data-price="200">$200</button>
								<button type="button" class="wallet-price round-button" data-price="500">$500</button>
								<button type="button" class="wallet-price round-button" data-price="1000">$1000</button>
							</div>
						</div>
						
						<div class="modal-footer center-block secure-checkout">
							<button type="submit" class="btn btn-success btn_deposite_with">Pay with Paypal</button>
						</div>
						<br>
						{{ Form::close() }}
					</div>
				</div>
			</div>
		</div>


		<div class="row cus-filter align-items-center">
			<div class="col-md-4 col-5 pad0">
				<div class="transactions-heading"><span>{{count($transactions)}}</span> Transactions Found
				</div>
			</div>
			<div class="col-md-8 col-7 pad0">
				<div class="sponsore-form">

					<form id="frmSearch" name="frmSearch" method="get" class="form-inline">

						<div class="form-group">
							<label for="price_filter" class="">
								{{ Form::select('status',[''=>'ALL','deposit'=>'Purchase','payment'=>'Payment','cancelled'=>'Cancelled','add_money_to_wallet'=>'Deposit To Wallet','tip_deposit'=>'Tips'] ,isset($_GET['status'])?$_GET['status']:'',['onchange'=>'this.form.submit()','class'=>'form-control']) }} 

							</label>
						</div>
					</form>
				</div>    
			</div>

		</div>
		<div class="cus-filter-data">
			<div class="cus-container-two">    
				<div class="table-responsive">
					<table class="manage-sale-tabel custom">
						<thead>
							<tr class="manage-sale-head custom-bold-header">
								<td class="width180">Date</td>
								<td>For</td>
								<td class="text-center">Amount</td>
							</tr>
						</thead>
						<tbody>
							@foreach($transactions as $row)
							<tr>
								<td>
									{{date('d M Y',strtotime($row->updated_at))}}
								</td>
								<td>
									@if($row->status!='commission')
										@if($row->is_sponsered == 1)
											@if(isset($row->service_order))
											{{$row->note}} 
											@if(isset($row->service_order->service->user->username) && isset($row->service_order->service->seo_url))
											<a href="{{route('services_details',[$row->service_order->service->user->username,$row->service_order->service->seo_url])}}"> (view service)</a>
											@endif
											@endif
										@else
											{{get_buyer_transaction_note($row->note)}}

											@if(isset($row->order))
											<a href="{{route('buyer_orders_details',$row->order->order_no)}}"> (view order)</a>
											<div>Order No: <b>{{$row->order->order_no}}</b></div>
											@endif
											
											<!-- For cancel order refund -->
											@if(isset($row->order) && count($row->order->upgrade_history) > 0 && !is_null($row->credit_to))
												@if($row->status == "pending_payment")
													<p>Refund pending</p>
												@else
													@if($row->credit_to == "bluesnap" && $row->status == "cancelled")
														<p>(Amount was refunded to your credit card)</p>
													@elseif($row->credit_to == "wallet" && $row->status == "cancelled")
														<p>(Amount was refunded to your wallet)</p>
													@endif
												@endif
											@else
												@if($row->status == "pending_payment")
													<p>Refund pending</p>
												@else
													@if(isset($row->order) && $row->order->payment_by == "bluesnap" && $row->status == "cancelled")
														<p>(Amount was refunded to your credit card)</p>
													@endif
												@endif
											@endif

											<!-- For deposit to wallet refund -->
											@if($row->status=='add_money_to_wallet' && $row->creditcard_amount > 0 && $row->cc_refund_status != 0)
												@if($row->cc_refund_status == 1)
												<p>(Refund pending)</p>
												@else
												<p>(Amount was refunded to your credit card)</p>
												@endif
											@endif

											<!-- For refund amount to skrill -->
											@if($row->status=='refund_transaction' && $row->skrill_amount > 0 && $row->cc_refund_status != 0)
												@if($row->cc_refund_status == 1)
												<p>(Refund pending)</p>
												@elseif($row->cc_refund_status == 3)
												<p>(Refund failed)</p>
												@else
												<p>(Amount was refunded to your Skrill account)</p>
												@endif
											@endif
										@endif
									@endif
								</td>
								<td class="text-center">
									@if($row->status != 'premium_subscription_cancel')
										@if(in_array($row->status,['deposite_amount','commission','credit','add_money_to_wallet','cancelled','refund_transaction']))
											
											@if($row->status=='refund_transaction' && $row->skrill_amount > 0 && $row->cc_refund_status != 2)
											<div class="text-danger">${{$row->anount}}</div>
											@else
											<div class="text-success">${{$row->anount}}</div>
											@endif

											@if($row->canRefundCCTransaction() && App\User::is_soft_ban() == 0)
											<a href="javascript:void(0);" class="refund-amount-to-wallet" data-txnid="{{$row->transaction_id}}" data-secret="{{$row->secret}}">Refund</a>
											@endif
										@else
											<div class="text-danger">-${{$row->anount}}</div>
										@endif
									@endif
								</td>
							</tr>
							@if($row->payment_processing_fee > 0 && in_array($row->status,['add_money_to_wallet','deposit','promote_bid_on_job','deposit_extra'])) 
							<tr>
								<td>
									{{date('d M Y',strtotime($row->updated_at))}}
								</td>
								<td>
									@if($row->status == 'deposit')
									Payment Processing Fee {{$row->note}}
									@else 
									Payment Processing Fee for {{$row->note}}
									@endif
									@if(isset($row->order))
									<a href="{{route('buyer_orders_details',$row->order->order_no)}}"> (view order)</a>
									<div>Order No: <b>{{$row->order->order_no}}</b></div>
									@endif
								</td>
								<td class="text-center">
									<div class="text-danger">-${{$row->payment_processing_fee}}</div>
								</td>
							</tr>
							@endif
							@endforeach
							@if(count($transactions)==0)
							<tr>
								<td colspan="7" class="text-center">
									No any transactions found
								</td>
							</tr>
							@endif
						</tbody>
					</table>
					
					<div class="clearfix"></div>

					<!-- PAGER -->
					<div class="text-center">{{ $transactions->appends(['status' => isset($_GET['status'])?$_GET['status']:''])->links("pagination::bootstrap-4") }}</div>
					<!-- /PAGER -->
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
	$('document').ready(function(){
		var url = window.location.href.split('?')[1];
		if(url != undefined) {
			var params = url.split('=');
			if(params[0] == "deposite_wallet" && params[1] == "true") {
				$('#add_money_to_wallet_model').modal('show');
			}
		}
	});

	$(document).on('click', '.refund-amount-to-wallet', function(e){
		var txnId = $(this).data('txnid');
		var secret = $(this).data('secret');
		$this = $(this);
		bootbox.confirm("Are you sure you want to refund this transaction?", function(result){
			if(result == true){
				$.ajax({
					type: "POST",
					url: "{{route('cc_deposite_amt.refund')}}",
					data: {"_token": _token, 'txnId': txnId,'secret': secret},
					success: function (data){
						if(data.success == true){
							window.location.reload();
						}else{
							alert_error(data.message);
						}
					}
				});
			}
		});
	});


</script>
@endsection