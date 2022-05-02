@if((isset($sponseredService) && count($sponseredService)) || count($Service))

	@if(count($Service))
		@foreach($Service as $service )
		<div class="legt-card-layout">
			@include('frontend.service.single-item')
		</div>
		@endforeach

		<!-- <div class="col-sm-12 filterpagination">
		    {{ $Service->links("pagination::bootstrap-4") }}
		</div> -->
	@endif
	<div class="total-count-show col-12 text-center cus-show-entry cus-grid-full" >
		<div>
			Showing {{ $Service->firstItem() }} to {{ $Service->lastItem() }} of total {{$Service->total()}} services
		</div>
	</div>
{{--
@else
	<div class="col-12 text-center">
	    <span class="no-service-found">No services are available.</span>
	</div>
--}}	
@endif

