@if((isset($sponseredService) && count($sponseredService)) || count($Service))

	@if(isset($sponseredService) && count($sponseredService))
		@foreach($sponseredService as $sponser)
		<div class="col-xl-4 col-lg-6 col-md-6 mb-4">
			@include('frontend.service.single-item-sponser')
		</div>
		@endforeach
	@endif

	@if(count($Service))
		@foreach($Service as $service )
		<div class="col-xl-4 col-lg-6 col-md-6 mb-4">
			@include('frontend.service.single-item')
		</div>
		@endforeach

		<div class="col-sm-12 filterpagination">
		    {{ $Service->links("pagination::bootstrap-4") }}
		</div>
	@endif
@else
	<div class="col-12 text-center">
	    <span class="no-service-found">No services are available.</span>
	</div>
	
@endif

