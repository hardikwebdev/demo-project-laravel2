@if(count($Service))
@foreach($Service as $service )
<div class="legt-card-layout">
	@include('frontend.service.single-item')
</div>
@endforeach
<div class="total-count-show col-12 text-center cus-show-entry cus-grid-full" >
	<div>
		Showing {{ $Service->firstItem() }} to {{ $Service->lastItem() }} of total {{$Service->total()}} services
	</div>
</div>
<!-- <div class="col-sm-12 filterpagination">
	{{ $Service->links("pagination::bootstrap-4") }}
</div> -->
@else
@endif