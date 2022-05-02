@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Transaction')
@section('content')
<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Transaction</h2>
			</div>    
		</div>    
	</div>    
</section>

<section class="cart-block">
	<div class="container">
		<div class="row"> 
			<div class="col-lg-12"> 
				<h3 class="cart-title">Summary</h3>
			</div>
		</div>
		<div class="row"> 
			<div class="col-lg-8 col-md-12 col-12"> 


				<div class="table-responsive">
					<table class="manage-sale-tabel payment-table payment-table-mb">
						<thead class="thead-default">
							<tr class="manage-sale-head">
								<td colspan="2">Service Name</td>
								<td>{{$Order->boosting_plan->name}}</td>
								<td colspan="2" width="180">Date</td>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td colspan="2">
									<a href="{{route('services_details',[$Order->Service->user->username,$Order->Service->seo_url])}}" class="text-capitalize"> {{$Order->Service->title}}
									</a>
								</td>
								<td>{!! nl2br($Order->boosting_plan->description) !!}</td>

								@if($Order->plan_id == 4 || $Order->plan_id == 5)
								@php
								$dates_array = [];
								foreach($Order->boosting_assign_dates as $row1){
									$dates_array[] = $row1->date;
								}
								@endphp
                                <td colspan="2">{!! array_to_date_list($dates_array,'<br>') !!}</td>
                                @else
                                <td colspan="2">{!! date_range_to_list($Order->start_date,$Order->end_date,'<br>') !!}</td>
                                @endif
							</tr>


							<tr>
								<td colspan="2">Selected plan</td>
								<td>$ {{($Order->slot == 2)?$Order->boosting_plan->sub_price:$Order->boosting_plan->price}}</td>
								<td></td>
								<td></td>
							</tr>

							<tr>
								<td colspan="2">Sponsor days</td>
								<td>{{$Order->total_days}}</td>
								<td></td>
								<td></td>
							</tr>

							@if($Order->coupon_id)
							<tr>
								<td colspan="2">Coupon discount</td>
								@if($Order->coupon_amount_type == 1)
								<td>
									@php
									if($Order->slot == 2){
										$subtotal = $Order->boosting_plan->sub_price * $Order->total_days;
									}else{
										$subtotal = $Order->boosting_plan->price * $Order->total_days;
									}
									@endphp
									{{ ($subtotal * $Order->coupon_amount) / 100 }}
								</td>
								@else
								<td>{{ $Order->coupon_amount }} </td>
								@endif
								<td></td>
								<td></td>
							</tr>
							@endif

							<tr>
								<td></td>
								<td>Total</td>
								<td>$ {{$Order->amount}}</td>
								<td></td>
								<td></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</section>  

<section class="cart-block">
	<div class="container">
		<div class="row"> 
			<div class="col-lg-5"> 
				<h3 class="cart-title">Transaction Information</h3>
			</div>
			<div class="col-lg-3 text-right"> 
				<h3 class="cart-title">
					<a href="{{route('sponsered_transaction')}}">
						<button type="button" class="btn view_sponsored_services view_sponsored_services_text">View Sponsored Services</button>
					</a>
				</h3>
			</div>
		</div>
		<div class="row"> 
			<div class="col-lg-8 col-md-12 col-12"> 


				<div class="table-responsive">
					<table class="manage-sale-tabel payment-table payment-table-mb">
						<thead class="thead-default">
							<tr class="manage-sale-head">
								<td>Transaction ID:</td>
								<td>Transaction Date:</td>
								<td>Total Amount:</td>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>{{$Order->txn_id}}</td>
								<td>{{date('M d,Y',strtotime($Order->created_at))}}</td>
								<td>${{$Order->amount}}</td>
							</tr>

						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</section>  
@endsection

@section('css')
{{-- <link rel="stylesheet" href="{{front_asset('css/bootstrap.min.css')}}"> --}}
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">
<style>
	.information-layout .information-layout-item p {
		font-size: 1em !important;
	}
	.category {
		font-size: 1em !important;
	}
	.category-custom{
		left: 185px !important;

	}
	.custom-cart-total{
		padding-left: 185px !important;
	}
	.text-header {
		font-size: 1.075em;
	}
	.view_sponsored_services, .view_sponsored_services:focus{
		width: auto;
		border-radius: 5px;
		border-color: transparent;
		padding: 5.5px 10px;
		background: linear-gradient(90deg, #35abe9 , #08d6c1 );
		border: none;
		color: white;
		font-size: 1rem;
	}
	.view_sponsored_services_text:hover, .view_sponsored_services_text:focus{
		color: white;
	}
</style>
@endsection

@section('scripts')

@endsection
