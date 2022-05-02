@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Buyer Transactions')
@section('content')

<section class="extended-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">

				<h2 class="heading mb-3">Sponsored transactions</h2>
				<div class="col-md-4 col-5 pad0">
					<div class="transactions-heading"><span>{{$sponseredCount}}</span> Transactions Found</div>
				</div>
				<div class="col-md-8 col-7 pad0">
					<div class="sponsore-form">
						<form class="form-inline" id="frmSearch" name="frmSearch" method="get">
							<div class="form-group">
								<label for="price_filter" class="select-block">
									{{ Form::select('status',[''=>'ALL','Pending'=>'Pending','In progress'=>'In progress','Completed'=>'Completed','Cancelled'=>'Cancelled'],isset($_GET['status'])?$_GET['status']:'',['onchange'=>'this.form.submit()','class'=>'form-control']) }} 
								</label>
							</div>
						</form>
					</div>    
				</div>
			</div>    
		</div>    
	</div>    
</section>

<!-- popular service -->
<section class="sponsored-section block-section">
	<div class="container">
		<div class="cus-filter-data">
			<div class="cus-container-two">    
				<div class="table-responsive">
					<table class="manage-sale-tabel custom">
						<thead>
							<tr class="manage-sale-head custom-bold-header">
								<td width="20%">Service</td>
								<td>Date</td>
								<td>Plan Name</td>
								<td>Amount</td>
								<td>Payment Information</td>
								<td width="15%">Status</td>
							</tr>
						</thead>
						<tbody>
							@if(count($Order) > 0)
							@foreach($Order as $row)
								<tr>
									<td width="20%" class="default-td">
										<a href="{{route('services_details',[$row->service->user->username,$row->service->seo_url])}}" class="text-capitalize"> {{ $row->service->title}}</a>
									</td>

									@if($row->plan_id == 4 || $row->plan_id == 5)
									@php
									$dates_array = [];
									foreach($row->boosting_assign_dates as $row1){
										$dates_array[] =  $row1->date;
									}
									@endphp
									<td>{!! array_to_date_list($dates_array,'<br>') !!}</td>
									@else
									<td>{!! date_range_to_list($row->start_date,$row->end_date,'<br>') !!}</td>
									@endif
									<td>
										{{$row->boosting_plan->name}}
										@if($row->plan_id == 4 || $row->plan_id == 5)
										<br/>
										({{($row->slot == 1)?'First slot':'Second or third slot'}})
										@endif
									</td>

									<td>
										$ {{$row->amount}}
									</td>
									<td>
										Payment Status: 
										{{ ucFirst($row->payment_status) }}<br>
										Payment By :
										@if($row->payment_by == 'promotional')
										demo Bucks
										@else
										{{ ucFirst($row->payment_by) }}
										@endif
									</td>
									<td width="15%">
										@php
										if($row->plan_id == 4 || $row->plan_id == 5){
											$start_date = $row->get_category_assign_startdate->date;
                                    		$end_date = $row->get_category_assign_enddate->date;
										}else{
											$start_date = date('Y-m-d', strtotime($row->start_date));
											$end_date = date('Y-m-d', strtotime($row->end_date));
										}
										
										$now = date('Y-m-d');

										if($row->status == "cancel"){
											$status = 'Cancelled';
										}elseif($start_date <= $now && $end_date >= $now)
										{
											$status = 'In progress';
										}
										elseif($end_date < $now)
										{
											$status = 'Completed';
										}
										else
										{
											$status = 'Pending';
										}
										@endphp
										@if(isset($status) && $status=='In progress')
										<p class="inprogress">{{$status}}</p>
										@endif

										@if(isset($status) && $status=='Completed')
										<p class="completed">{{$status}}</p>
										@endif

										@if(isset($status) && $status=='Pending')
										<p class="inprogress">{{$status}}</p>
										@endif

										@if(isset($status) && $status=='Cancelled')
										<p class="cancelled-cus">{{$status}}</p>
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

					<!-- PAGER -->
					<div class="text-center">
						@if(count($Order) > 0)
						{{ $Order->appends(['status' => isset($_GET['status'])?$_GET['status']:''])->links("pagination::bootstrap-4") }}
						@endif
					</div>
				</div>
			</div>
		</div>
	</div>        
</section>  
@endsection