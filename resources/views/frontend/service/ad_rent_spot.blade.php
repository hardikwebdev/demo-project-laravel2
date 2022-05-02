<div class="new_service_box">
	<div class="thumbnail thumbnail-obj-cover">
		<a href="javascript:void(0)" data-plan="{{$boost_plan->secret}}" class="select_service_for_rent_spot">
			@php 
			$image_url = url('public/frontend/assets/img/rentthisspot.png');
			@endphp
			<img class="img-fluid" src="{{$image_url}}">
		</a>
	</div>
	<div class="product-info">
		<a href="javascript:void(0)" data-plan="{{$boost_plan->secret}}" class="select_service_for_rent_spot">
			<h4 class="text-header font-lato font-weight-bold min-title-height new_service_box_title text-color-2">
				Limited Offer - Rent This Spot Now           
			</h4>
		</a>

		<p class="product-description min-description-height new_service_box_desc">
			Get more views by renting this spot to promote your service
		</p>

		<div class="mb-2 d-flex mt-4 pt-2 justify-content-between">
			<div class="d-flex align-items-center"></div>
			<div>
				<p class="font-14 mb-0 text-color-3">AD</p>
			</div>
		</div>

		<div class="row align-items-center avtar-block py-2 card-footer-min-height">
			<div class="col-7">
				
			</div>
			<div class="col-5 display_grid">
				<span class="new_service_box_price text-color-3">${{isset($boost_plan->price)?$boost_plan->price:'0.0'}}</span>
				<span class="new_service_box_startingAt align-self-end pb-1 text-color-4">Starting at</span>
			</div>
		</div>
	</div>
</div>