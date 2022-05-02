@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Services')
@section('content')
<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">General Coupon</h2>
			</div>    
		</div>
	</div>    
</section>


<section class="transactions-table pad-t4">
	<div class="container">
		@include('layouts.frontend.messages')
		<div class="alert alert-primary" role="alert">
			<h5><i class="fa fa-info fa-2x"></i> &nbsp; These general coupons can be applied to any service you offer.  If you want to make a service specific coupon, please do so from that specific service.</h5>
		</div>
		<div class="row cus-filter align-items-center">
			<div class="col-md-8 col-12 pad0">
				<div class="transactions-heading"><span>{{ count($coupans) }}</span> Coupons Found
				</div>
			</div>
			<div class="col-md-4 col-12 pad0">
				<div class="sponsore-form service-filter">
					<div class="create-new-service"> 
						<a href="{{ route('add_general_coupon') }}" class="button primary" style="margin: 0px;"><button type="button" class="btn">Create New Coupon</button></a>
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
					<td>
						<a href="{{ route('edit_general_coupon',$coupan->secret) }}" class="send-request-buttom">Edit</a>  
						<a href="javascript:void(0)" class="cancel-request-buttom delete_coupon_btn" data-url="{{ route('delete_general_coupon',$coupan->secret) }}">Delete</a>
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
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type='text/javascript'>
$('document').ready(function() {
	$('.delete_coupon_btn').on('click', function(){
		var url = $(this).data('url');
		bootbox.confirm("Are you sure you want to delete this coupon?", function(result){ 
			if (result) {
				window.location.href = url;
			}	 
		});
	});
});
</script>
@endsection
