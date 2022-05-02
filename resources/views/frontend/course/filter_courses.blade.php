
@if( ($Service->currentPage() == 1 && $Service->total() == 0 ) )
<div class="cus-grid-full text-center">
	<span class="no-service-found">No courses are available.</span>
</div>
@endif
@if( ($Service->currentPage() == 1 && $Service->total() == 0 ) || ($Service->currentPage() == 2 && $Service->total() != 0) )
	@include('frontend.service.service_banner')
@endif

@if(count($Service))

	@if(count($Service))
		@foreach($Service as $serviceBox)
		<div class="legt-card-layout">
            @php 
            /* set price */
            $serviceBox->price = $serviceBox->lifetime_plans->price;
            @endphp
            @include('frontend.course.include.single-item')
		</div>
		@endforeach

        {{ $Service->links("pagination::bootstrap-4") }}
	@endif
	<div class="total-count-show col-12 text-center cus-show-entry cus-grid-full" >
		<div>
			Showing {{ $Service->firstItem() }} to {{ $Service->lastItem() }} of total {{$Service->total()}} courses
		</div>
	</div>
@endif

