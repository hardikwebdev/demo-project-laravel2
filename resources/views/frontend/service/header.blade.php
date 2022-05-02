@php
/*$tab_name = \Request::route()->getName();*/
$serviceId='null';$current_step=0;
if(isset($Service)){
	$current_step=$Service->current_step;
	$serviceId=$Service->id;
	$serviceSEO=$Service->seo_url;
}
$currentPage = \Request::route()->getName();
@endphp
<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				@if(isset($Service) && isset($Service->id) && $current_step>=5)
				<h2 class="heading">Update Service</h2>
				@else
				<h2 class="heading">Create New Service</h2>
				@endif
			</div>    
		</div>    
	</div>    
</section>
@if($Service->revisions != null)
<div class="alert-review-edition text-center @if ($Service->revisions->is_approved == 0) bg-light-sky-blue @elseif ($Service->revisions->is_approved == 2) bg-light-danger @endif">
	<i class="fa fa-info-circle"></i>
	@if ($Service->revisions->is_approved == 0)
		Your submitted changes are pending for approval.
	@elseif ($Service->revisions->is_approved == 2)
		Your submitted changes are rejected by admin.
		@if($service->revisions->reject_reason != "")
			Reason: <span class="font-14 text-danger">{{$service->revisions->reject_reason}}</span>
		@endif
	@endif
</div>
@endif
<section class="popular-tab-icon create-new-service-menu">
	<div class="container">
		<div class="row">
			<div class="popular-tab-item">
				<ul class="nav nav-tabs" id="myTab" role="tablist">
					<li class="nav-item">

						<a class="nav-link @if($currentPage=='create_services' || $currentPage=='overview_update'){{'active'}}@endif"  @if($serviceId == 'null')href="{{route('create_services')}}"@else href="{{route('overview_update',$serviceSEO)}}" @endif>
							Overview
						</a>
					</li>
					<li class="nav-item">
						<a class="nav-link @if($currentPage=='services_pricing'){{'active'}}@endif"  @if($serviceId == 'null' || $current_step<1)href="javascript:void(0);"  class="tab-disabled"@else href="{{route('services_pricing',$serviceSEO)}}" @endif>Pricing</a>
					</li>
					<li class="nav-item">
						<a class="nav-link @if($currentPage=='services_desc'){{'active'}}@endif"  @if($serviceId == 'null' || $current_step<2)href="javascript:void(0);"  class="tab-disabled"@else href="{{route('services_desc',$serviceSEO)}}" @endif>Description</a>
					</li>
					<li class="nav-item">
						<a class="nav-link  @if($currentPage=='services_req'){{'active'}}@endif"  @if($serviceId == 'null' || $current_step<3)href="javascript:void(0);"  class="tab-disabled"@else href="{{route('services_req',$serviceSEO)}}" @endif>Requirements</a>
					</li>
					<li class="nav-item">
						<a class="nav-link  @if($currentPage=='services_gallery'){{'active'}}@endif"  @if($serviceId == 'null' || $current_step<4)href="javascript:void(0);"  class="tab-disabled"@else href="{{route('services_gallery',$serviceSEO)}}" @endif>Gallery</a>
					</li>
					<li class="nav-item">
						<a class="nav-link  @if($currentPage=='get_faq') active @endif" @if($serviceId == 'null' || $current_step<5) href="javascript:void(0);" class="tab-disabled" @else href="{{route('get_faq', $serviceSEO) }}" @endif>FAQs</a>
					</li>
					@if($current_step<=5)
					<li class="nav-item">
						<a class="nav-link" @if($currentPage=='service_publish'){{'active'}}@endif"  @if($serviceId == 'null' || $current_step<5)href="javascript:void(0);"  class="tab-disabled"@else href="{{route('service_publish',$serviceSEO)}}" @endif>Publish</a>
					</li>
					@endif
				</ul>
			</div>          
		</div>
	</div>
	
</section>