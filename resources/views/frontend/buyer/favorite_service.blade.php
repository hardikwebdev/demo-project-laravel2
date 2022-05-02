@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Favorites')

@section('content')
<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Favorites</h2>
			</div>    
		</div>    
	</div>    
</section>


<section class="popular-services popular-tab-icon">
	<div class="container">
		<div class="row">
			<div class="offset-md-9 col-md-3 mb-4">
				{!! Form::open(['route'=>'favorites','id'=>'favorite_filter_form','method'=>'get']) !!}
				{!! Form::select('type',['services'=>'Services','courses'=>'Courses'],request()->type,['class'=>'form-control pull-right','id'=>'favorite_type']) !!}
				{!! Form::close() !!}
			</div>
			@if(count($services) > 0)
			@foreach($services as $service)
				@if(isset($service->favoriteservice))
					{{-- Service --}}
					@if($service->favoriteservice->is_course == 0)
						@php 
						$service = $service->favoriteservice; 
						@endphp
						<div class="col-lg-3 col-md-4 mb-4">
							@include('frontend.service.single-item')
						</div>
					@else
					{{-- Course --}}
						@php 
						$serviceBox = $service->favoriteservice; 
						/* set price */
						$serviceBox->price = $serviceBox->lifetime_plans->price;
						@endphp
						<div class="col-lg-3 col-md-4 mb-4">
							@include('frontend.course.include.single-item')
						</div>
					@endif
				@endif
			@endforeach

			<div class="clearfix"></div>
			<div class=" col-md-12">
				<div class="paginate-center">
					{{ $services->links("pagination::bootstrap-4") }}
				</div>
			</div>
			@else
			<div class="text-center-width">
				<span class="NoServicesAreAvailable">No favorites are available.</span>
			</div>
			@endif
		</div>
	</div>
</section>          

@endsection

@section('scripts')
<script type="text/javascript">
	$( window ).on("load",function(){
		var count = $(document).find('.product-item').length;
		if(count == 0)
		{
			$('.no_service_msg').show();
			$('.noshow').hide();
		}
	})
	/* Trigger filter form */
	$(document).on('change','#favorite_type',function(){
		$('#favorite_filter_form').trigger('submit');
	});

</script>
@endsection