@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Services')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Coupon</h2>
			</div>    
		</div>    
	</div>    
</section>


<section class="transactions-table pad-t4">
	<div class="container">@include('layouts.frontend.messages')
		<div class="row cus-filter align-items-center">
			<div class="col-md-7 col-12 pad0">
				<div class="transactions-heading"><span>{{ count($coupans) }}</span> Coupons Found (<span class="text-capitalize">{{$service->title}}</span>)
				</div>
			</div>
			<div class="col-md-5 col-12 pad0">
				<div class="sponsore-form service-filter">
					<div class="create-new-service"> 
						<a href="{{ route('showAddCoupan',['id' => $id,'type' => $type]) }}" class="button primary" style="margin: 0px;"><button type="button" class="btn">Create New Coupon</button></a>
					</div>
				</div>    
			</div>
		</div>

	</div>        
</section>  

<section class="custom-block-section">
	<div class="container">

		<table class="table box-border table-hover">
			<thead class="thead-default">
				<tr>
					<th>COUPON CODE</th>
					<th>EXPIRY DATE</th>
					<th>ALLOWED USES</th>
					<th>TOTAL USES</th>
					<th>DISCOUNT</th>
					@if($service->is_course == 0)
					<th>SHOW ON DEALS PAGE</th>
					@endif
					<th>ACTION</th>
				</tr>	
			</thead>
			<tbody id="extra-body">
				@if(count($coupans) > 0)
				@foreach( $coupans as $coupan )
				<tr class="tr_{{$coupan->id}}">
					<td> {{ $coupan->coupan_code }} </td>
					<td> <?php echo date('d M Y',strtotime($coupan->expiry_date)); ?></td>
					<td> {{ $coupan->no_of_uses }} </td>
					<td> {{ count($coupan->coupan_applied) }} </td>
					<td> @if($coupan->discount_type=='amount')  
						{{ '$ '.$coupan->discount }}
						@else 
						{{ $coupan->discount.'%' }}
						@endif
					</td>
					@if($service->is_course == 0)
					<td> 
						@if($coupan->is_promo =='0')
							<a href="javascript:void(0)" class="cancel-request-buttom" onclick='promotionAdd("{{ $coupan->secret }}","Active")' >NO</a>
						@else
							<a href="javascript:void(0)" class="send-request-buttom" onclick='promotionAdd("{{ $coupan->secret }}","Inactive")' >YES</a>
						@endif
					</td>
					@endif
					<td>
						<a href="{{ route('showcoupanEdit',['id' => $coupan->id,'type' => $type]) }}" class="send-request-buttom">Edit</a>  
						<a href="javascript:void(0)" class="cancel-request-buttom" onclick='removeCoupan({{ $coupan->service_id }},{{ $coupan->id }})' >Delete</a>
					</td>
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan='6' class="text-danger text-center">No any coupons found</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>        
</section>  


@endsection

@section('scripts')
<script type='text/javascript'>
	function removeCoupan(service_id,id){
		if (confirm("Are you sure you want to delete the coupon?")) {
			var url = "{{ URL::route('coupanDelete') }}";
			$.ajax({
				url : url,
				type : 'get',
				data : { 'id': id, 'service_id' : service_id,'token': _token},
				success : function(data){
					alert_success(data.message);
					$('.tr_'+id).hide();
				}
			});
		}
		return false;
	}
	function alert_success(message){
		toastr.success(message,'');
	}
	function alert_error(message){
		toastr.error(message,'');
	}
	function promotionAdd(id,status){
		if (confirm("Are you sure you want to "+status+" this coupon? Note: Only one coupon can be active at a time per service. Your active coupon will be visible on the promotions page. ")) {
			var url = "{{ URL::route('updatePromotionStatus') }}";
			$.ajax({
				url : url,
				method : 'post',
				dataType: "html",
				data : { '_token': _token,'id': id,'status': status},
				success : function(data){
					var data = JSON.parse(data);
					// location.reload();
					if(data.status == 200){
						alert_success(data.message);
						location.reload();
					}else if(data.status == 400){
						alert_error(data.message);
					}
					// location.reload();
				}
			});
		}
		return false;
	}

</script>
@endsection
