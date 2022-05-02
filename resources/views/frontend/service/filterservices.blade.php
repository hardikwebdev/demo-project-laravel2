
@if( ($Service->currentPage() == 1 && $Service->total() == 0 ) )
<div class="cus-grid-full text-center">
	<span class="no-service-found">No services are available.</span>
</div>
@endif
@if( ($Service->currentPage() == 1 && $Service->total() == 0 ) || ($Service->currentPage() == 2 && $Service->total() != 0) )
	@include('frontend.service.service_banner')
@endif


@if((isset($sponseredService) && count($sponseredService)) || count($Service))

	@if(isset($sponseredService) && count($sponseredService))
		@foreach($sponseredService as $sponser)
		<div class="legt-card-layout">
			@include('frontend.service.single-item-sponser')
		</div>
		@endforeach
	{{-- @else 
		@if(Auth::check())
			<div class="legt-card-layout">
				@include('frontend.service.ad_rent_spot')
			</div>
		@endif --}}
	@endif

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
	<div class="total-count-show col-12 text-center cus-show-entry cus-grid-full"  >
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

