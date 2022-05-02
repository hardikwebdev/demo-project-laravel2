@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Buyer Transactions')
@section('content')


<!-- Get Project -->

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Premium Transactions</h2>
			</div>    
		</div>    
	</div>    
</section>

<br/>
<!-- Get Project -->


<!-- popular service -->
<section class="block-section transactions-table">
	<div class="container">
		<div class="row cus-filter align-items-center">
			<div class="col-md-4 col-5 pad0">
				<div class="transactions-heading"><span>{{count($transactions)}}</span> Transactions Found
				</div>
			</div>
			<div class="col-md-8 col-7 pad0">
				<div class="sponsore-form">
					<div class="process-pay bcmseller" style="margin: 0px;font-size: 10px;">  
					@if(Auth::check() && Auth::user()->is_premium_seller() == true && Auth::user()->subscription->is_cancel == 0)
						<button type="button" class="pro-btn" style="font-size: 15px;padding: 6px;">
  							<a href="{{route('cancel_premium_subscription')}}" role="button">Cancel My Premium Subscription </a>
						</button>
					@else
						<button type="button" class="pro-btn" style="font-size: 15px;padding: 6px;">
							<a href="{{route('become_premium_seller')}}" role="button">Upgrade To Premium</a>
						</button>
					@endif
					</div>
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
							@if(!$transactions->isempty())

							@foreach($transactions as $row)
							<tr>
								<td>
									{{date('d M Y',strtotime($row->updated_at))}}
								</td>
								<td>
									@if($row->status!='commission')
										@if($row->is_sponsered == 1)
											@if(isset($row->service_order))
											{{$row->note}} <a href="{{route('services_details',[$row->service_order->service->user->username,$row->service_order->service->seo_url])}}"> (view service)</a>
											@endif
										@else
											{{$row->note}}
											@if(isset($row->order))
											<a href="{{route('buyer_orders_details',$row->order->order_no)}}"> (view order)</a>
											@endif
										@endif
									@endif
								</td>
								<td class="text-center">
									@if($row->status != 'premium_subscription_cancel')
										@if($row->status=='deposite_amount' || $row->status=='deposit' || $row->status=='commission' || $row->status=='credit')
											<div class="text-success">${{$row->anount}}</div>
										@else
											<div class="text-danger">-${{$row->anount}}</div>
										@endif
									@endif
								</td>
							</tr>
							@endforeach
							@endif
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
