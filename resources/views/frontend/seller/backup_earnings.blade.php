@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Earnings')
@section('content')

<div class="section-headline-wrap">
	<div class="section-headline">
		<h2>Earnings</h2>
		<p>Home<span class="separator">/</span><span class="current-section">Earnings</span></p>
	</div>
</div>

<!-- SECTION -->
<div class="section-wrap">
	<div class="section">
		<!-- CONTENT -->
		<div class="content full">

			<!-- Display Error Message -->
			@include('layouts.frontend.messages')
			<!-- HEADLINE -->

			<div class="pack-boxes">
				<!-- PACK BOX -->
				<div class="pack-box" style="height: 140px;">
					<p class="text-header small">Net Income</p>
					<p class="price larger"><span>$</span>{{Auth::user()->net_income}}</p>
				</div>
				<!-- /PACK BOX -->

				<!-- PACK BOX -->
				<div class="pack-box" style="height: 140px;">
					<p class="text-header small">Withdrawn</p>
					<p class="price larger"><span>$</span>{{Auth::user()->withdraw_amount}}</p>
				</div>
				<!-- /PACK BOX -->

				<!-- PACK BOX -->
				<div class="pack-box" style="height: 140px;">
					<p class="text-header small">Pending Clearance</p>
					<p class="price larger"><span>$</span>{{$pendingClearance+$affiliatePending}}</p>
				</div>
				<!-- /PACK BOX -->

				<!-- Affiliate BOX -->
				{{-- update 26-02-2018 --}}
				<div class="pack-box" style="height: 140px;">
					<p class="text-header small">Affiliate</p>
					<p class="price larger"><span>$</span>{{number_format($affiliateAmount,2)}}</p>
				</div>
				{{-- update 26-02-2018 /--}}
				<!-- /PACK BOX -->

				<!-- PACK BOX -->
				<div class="pack-box" style="height: 140px;">
					<p class="text-header small">Available for Withdrawal</p>
					<p class="price larger"><span>$</span>{{$myBalance}}</p>

				</div>
				<!-- /PACK BOX -->

			</div>

			<div class="headline secondary withdraw">
				<h4 style="float: none;">Withdraw: <span class="left-30 color-gray">Paypal Account 
				@if(Auth::user()->paypal_email)
					({{Auth::user()->paypal_email}})
				@endif</span>
				<span class="left-30">
				@if(Auth::user()->paypal_email !='')
					{{ Form::open(['route' => ['withdraw_request'], 'method' => 'POST']) }}
					<button type="submit" class="button secondary">Send Request</button>
					{{ Form::close() }}
				@else
					<a href="{{route('accountsetting')}}" class="button secondary">Update Profile</a>
				@endif
				</h4> 
				</span>
			</div>

			<!-- <div class="clearfix1"></div> -->

			<div class="headline primary" style="clear: both;">
				<h4>{{count($transactions)}} Transactions Found</h4>
				
				<form id="frmSearch" name="frmSearch" method="get">
					<label for="price_filter" class="select-block">
						{{ Form::select('status', 
						[''=>'ALL','pending_clearance'=>'Pending Clearance','cleared'=>'Cleared','cancelled'=>'Cancelled']
						,isset($_GET['status'])?$_GET['status']:'',['onchange'=>'this.form.submit()']) }} 

						<svg class="svg-arrow">
							<use xlink:href="#svg-arrow"></use>
						</svg>
					</label>
				</form>
				<div class="clearfix"></div>
			</div>
			<!-- /HEADLINE -->

			<div class="form-box-items" style="background-color: #fff;">
				<table class="table" id="order-table">
					<thead class="thead-default1">
						<tr>
							<th style="width: 160px;">DATE</th>
							<th>FOR</th>
							<th class="text-center">AMOUNT</th>
						</tr>	
					</thead>
					<tbody>
						@foreach($transactions as $row)
						<tr>
							<td>
								{{date('d M Y',strtotime($row->created_at))}}
							</td>
							<td>
								{{$row->note}} <a href="{{route('seller_orders_details',$row->order->order_no)}}"> (view order)</a>
							</td>
							<td class="text-center">
								@if($row->status=='cleared')
									<div class="text-success">${{$row->anount}}</div>
								@else
									<div class="text-danger">-${{$row->anount}}</div>
								@endif
							</td>
						</tr>
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
				<div class="text-center">{{ $transactions->links() }}</div>
				<!-- /PAGER -->
			</div>
			
		</div>
		<!-- CONTENT -->
	</div>
</div>
<!-- /SECTION -->

@endsection

@section('css')
 <link rel="stylesheet" href="{{front_asset('css/bootstrap.min.css')}}">
@endsection

@section('scripts')

@endsection