@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Earnings')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Earnings</h2>
			</div>    
		</div>    
	</div>    
</section>

<!-- Get Project -->
<section class="get-project transactions-section">
	<div class="container">
		@include('layouts.frontend.messages')
		<div class="row custom">
			<div class="col-cus-5 col-md-4 col-lg-4 col-sm-4  custom-margin-top">
				<div class="project-block text-center">
					<div class="project-number">
						<span>$</span>{{dispay_money_format(Auth::user()->net_income)}}
					</div>
					<div class="project-detail">
						<div class="project-title">Net Income</div>
					</div>
				</div>
			</div>
			<div class="col-cus-5 col-md-4 col-lg-4 col-sm-4  custom-margin-top">
				<div class="project-block text-center">
					<div class="project-number">
						<span>$</span>{{dispay_money_format(Auth::user()->withdraw_amount)}}
					</div>
					<div class="project-detail">
						<div class="project-title">Withdrawn</div>
					</div>
				</div>
			</div>
			<div class="col-cus-5 col-md-4 col-lg-4 col-sm-4  custom-margin-top">
				<div class="project-block text-center">
					<div class="project-number">
						<span>$</span>{{dispay_money_format($pendingClearance+$affiliatePending)}}
					</div>
					<div class="project-detail">
						<div class="project-title">Pending Clearance
						</div>
					</div>
				</div>
			</div>
			<div class="col-cus-5 col-md-4 col-lg-4 col-sm-4  custom-margin-top">
				<div class="project-block text-center">
					<div class="project-number">
						<span>$</span>@if(Auth::user()->freeze >= 0){{ dispay_money_format(Auth::user()->freeze + Auth::user()->dispute_amount) }}@else{{ dispay_money_format(Auth::user()->dispute_amount) }}@endif
					</div>
					<div class="project-detail">
						<div class="project-title">Escrow Amount
						</div>
					</div>
				</div>
			</div>
			<div class="col-cus-5 col-md-4 col-lg-4 col-sm-4  custom-margin-top">
				<div class="project-block text-center">
					<div class="project-number">
						<span>$</span>{{dispay_money_format($affiliateAmount)}}
					</div>
					<div class="project-detail">
						<div class="project-title">Affiliate</div>
					</div>
				</div>
			</div>
			<div class="col-cus-5 col-md-4 col-lg-4 col-sm-4  custom-margin-top">
				<div class="project-block text-center">
					<div class="project-number">
						<span>$</span>{{dispay_money_format($myBalance)}}
					</div>
					<div class="project-detail">
						<div class="project-title">Available for Withdrawal</div>
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
				<div class="transactions-heading"><span>Withdraw Request</div>
			</div>

			@if(Auth::user()->paypal_email || Auth::user()->is_enable_payee_account)
			<div class="col-md-8 col-7 pad0">
				<div class="sponsore-form">
					<div class="update-profile-btn"> 
						<div class="m-dropdown m-dropdown--inline m-dropdown--arrow m-dropdown--align-right m-dropdown--align-push">
							<button type="button" class="btn" data-toggle="modal" data-target="#open-withdraw-request">Request a Withdrawal</button>
						</div>
					</div>
				</div>    
			</div>
			@else
			<div class="col-md-8 col-7 pad0">
				<div class="sponsore-form">
					<div class="update-profile-btn"> 
						<a href="{{route('manage_accounts')}}" class="button secondary">
							<button type="button" class="btn">Manage accounts</button>
						</a>
					</div>
				</div>    
			</div>
			@endif

			<!--begin::Send Request Modal-->
			<div class="modal fade custommodel-new" id="open-withdraw-request" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
						<div class="modal-header req-withdrawal-header">
							<h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Request a Withdrawal</h5>

							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>

						{!! Form::open(['route' => ['withdraw_request'],'method' => 'post', 'id' => 'withdraw_request']) !!}
						<div class="modal-body">
							<div class="col-lg-12">
								<div id="withdraw_response"></div>
							</div>
							<div class="col-lg-12">
								<div class="form-group">
									<select class="form-control" name="request_from" id="request_from">
										<option value="">Select an account to send your funds to</option>
										@if(Auth::user()->paypal_email)
										<option value="1">Paypal</option>
										@endif
										@if(Auth::user()->is_enable_payee_account)
										<option value="2">Payoneer</option>
										@endif
									</select>
								</div>
							</div>

							<div class="col-lg-12">
								<label for="recipient-name" class="form-control-label">Enter The Amount You Wish To Withdraw</label>
							</div>

							<div class="col-lg-12">
								<div class="form-group">
									<div class="input-group">
										<div class="input-group-prepend">
											<span class="input-group-text group-before-text"> &nbsp;$&nbsp; </span>
										</div>
										{!! Form::text('withdraw_amount', null,['class' => 'form-control','placeholder' => 'Enter The Amount You Wish To Withdraw']) !!}
									</div>
									<br>
									@if(Auth::check() && Auth::user()->is_premium_seller() == true)
										<label>Withdraw Limit is $1000 per day.</label>
									@else
										<label>Withdraw Limit is $1000 per day.</label>
									@endif
								</div>
							</div>
							<hr>
							<div class="help text-center">or select an amount below</div>

							<div class="col-lg-12 center-block">
								<button type="button" class="withdrawal-price round-button price-20" data-price="20">$20</button>
								<button type="button" class="withdrawal-price round-button" data-price="50">$50</button>
								<button type="button" class="withdrawal-price round-button" data-price="70">$70</button>
								<button type="button" class="withdrawal-price round-button" data-price="100">$100</button>
								<button type="button" class="withdrawal-price round-button" data-price="{{Auth::user()->earning}}">All</button>
							</div>
							

						</div>
						
						<div class="modal-footer center-block">
							<button type="submit" name="Send Request" class="btn send-request-buttom-new withdraw-width">Send Request</button>

							{{-- {!! Form::submit('Send Request',['class' => 'btn send-request-buttom-new withdraw-width']) !!} --}}
						</div>
						<div class="text-center payoneer-charge text-danger" style="display: none;">Note : Payoneer charges $3 fee per withdrawal</div>
						<br>
						{{ Form::close() }}
					</div>
				</div>
			</div>
			<!--end::Send Request Modal-->



		</div>
		<div class="row cus-filter align-items-center">
			<div class="col-md-4 col-5 pad0">
				<div class="transactions-heading"><span>{{@$transactionsCount}}</span> Transactions Found
				</div>
			</div>
			<div class="col-md-8 col-7 pad0">
				<div class="sponsore-form">
					<form class="form-inline"  id="frmSearch" name="frmSearch" method="get">
						<div class="form-group">
							{{ Form::select('status',[null=>'ALL','pending_clearance'=>'Pending Clearance','cleared'=>'Cleared','cancelled'=>'Cancelled']
							,isset($_GET['status'])?$_GET['status']:'',['class' =>'form-control', 'onchange'=>'this.form.submit()']) }} 
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
							@if(count($transactions))
								@foreach($transactions as $row)
								<tr>
									<td>
										{{date('d M Y',strtotime($row->created_at))}}
									</td>
									<td>
										{{isset($row->note)?$row->note:''}}
										@if(isset($row->order))
											<a href="{{route('seller_orders_details',$row->order->order_no)}}">(view order)</a>
										@endif
									</td>
									<td class="text-center">
										@if($row->status=='cleared')
											<div class="text-success">${{isset($row->anount)?$row->anount:''}}</div>
										@else
											<div class="text-danger">-${{isset($row->anount)?$row->anount:''}}</div>
										@endif
									</td>
								</tr>
								@endforeach
							@else	
								<tr>
									<td colspan="7" class="text-center">
										No any transactions found
									</td>
								</tr>
							@endif

						</tbody>
					</table>

					<div class="clearfix"></div>
					<div class="text-center">
						@if(count($transactions))
						{{ $transactions->appends(['status' => isset($_GET['status'])?$_GET['status']:''])->links("pagination::bootstrap-4") }}
						@endif
					</div>
				</div>
			</div>
		</div>
	</div>        
</section>  

@endsection

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type="text/javascript">
	$(document).ready(function() {

		$(document).on("click",".withdraw_request",function(e) {
			e.preventDefault();
		});
		/*$('.open-withdraw-request').magnificPopup({
			type: 'inline',
			removalDelay: 300,
			mainClass: 'mfp-fade',
			closeMarkup: '<div class="close-btn mfp-close"><svg class="svg-plus"><use xlink:href="#svg-plus"></use></svg></div>'
		});*/
		$(document).on("change","#request_from",function(e) {
			if($(this).val() == '2'){
				$('.payoneer-charge').show();
			}else{
				$('.payoneer-charge').hide();
			}
		});
	});
</script>
@endsection