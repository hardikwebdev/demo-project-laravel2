@extends('layouts.frontend.main')

@if(isset($getSubCategoryId->seo_title) && $getSubCategoryId->seo_title != null)
@section('pageTitle',$getSubCategoryId->seo_title .' - Courses')
@elseif(isset($getCategoryId->seo_title) && $getCategoryId->seo_title != null)
@section('pageTitle',$getCategoryId->seo_title .' - Courses')
@else
@section('pageTitle','demo - Courses')
@endif
@section('metaTags')

@if(isset($getSubCategoryId) && $getSubCategoryId != null)
<meta name="title" content="{{($getSubCategoryId->seo_title)?$getSubCategoryId->seo_title:''}}">
<meta name="description" content="{{($getSubCategoryId->seo_description)?$getSubCategoryId->seo_description:'' }}">
@else
@if(isset($getCategoryId) && $getCategoryId != null)
<meta name="title" content="{{($getCategoryId->seo_title)?$getCategoryId->seo_title:''}}">
<meta name="description" content="{{($getCategoryId->seo_description)?$getCategoryId->seo_description:'' }}">
@endif
@endif
@endsection
@section('content')

@php
$bannerBg = $bannerBgColor = $bannerText =  $bannerSubTitleText = $bannerSubTitleTextColor = '';
$titleTextFontSize = '22';
$subTitleTextFontSize = '18';
$bannerTextColor = '#000';

foreach($forusByUsBanner as $bannerValue){
	if($bannerValue->settingkey == 'forusbyus_banner'){
		$bannerBg = $bannerValue->settingvalue;
	}elseif($bannerValue->settingkey == 'forusbyus_text'){
		$bannerText = $bannerValue->settingvalue;
	}elseif($bannerValue->settingkey == 'forusbyus_text_color'){
		$bannerTextColor = $bannerValue->settingvalue;
	}elseif($bannerValue->settingkey == 'forusbyus_bg_color'){
		$bannerBgColor = $bannerValue->settingvalue;
	}elseif($bannerValue->settingkey == 'forusbyus_title_font_size'){
		$titleTextFontSize = $bannerValue->settingvalue;
	}elseif($bannerValue->settingkey == 'forusbyus_subtitle_text'){
		$bannerSubTitleText = $bannerValue->settingvalue;
	}elseif($bannerValue->settingkey == 'forusbyus_subtitle_color'){
		$bannerSubTitleTextColor = $bannerValue->settingvalue;
	}elseif($bannerValue->settingkey == 'forusbyus_subtitle_font_size'){
		$subTitleTextFontSize = $bannerValue->settingvalue;
	}
}
@endphp
@if($bannerBg != '')
	<header class="masthead byusforusbanner" style="background-image:url('{{($bannerBg != '') ? $bannerBg : ''}}'); background-color:{{ ($bannerBgColor != null) ? $bannerBgColor : '#ffb502' }};">
		<div class="container">
			<div class="row">
				<div class="col-md-8 col-lg-6 col-xl-6 col-10">
					@if($bannerText)
					<h1 class="mb-1" style="color:{{$bannerTextColor}};font-size:{{$titleTextFontSize}}px;">{!! ($bannerText != null ) ? $bannerText : '' !!}</h1>
					@endif
					@if($bannerSubTitleText)
					<p class="h6 cus-text-color w-75" style="color:{{$bannerSubTitleTextColor}};font-size:{{$subTitleTextFontSize}}px;">{!! ($bannerSubTitleText != null ) ? $bannerSubTitleText : '' !!}</p>
					@endif
				</div>
			</div>
		</div>
	</header>
@endif

<section class="header-breadcrumb" id="location">
	<div class="container">

		{{-- Hidden Fields --}}
		@if(Request::segment(1) == "top-rated")
		<input class="selectvalue" type="hidden" name="sort_by" id="sort_by" value="top_rated">
		@elseif(Request::segment(1) == "recently-uploaded")
		<input class="selectvalue" type="hidden" name="sort_by" id="sort_by" value="recently_uploaded">
		@elseif(Request::segment(1) == "best-seller")
		<input class="selectvalue" type="hidden" name="sort_by" id="sort_by" value="best_seller">
		@elseif(Request::segment(1) == "recurring")
		<input class="selectvalue" type="hidden" name="sort_by" id="sort_by" value="recurring">
		@elseif(Request::segment(1) == "review-editions")
		<input class="selectvalue" type="hidden" name="sort_by" id="sort_by" value="least_reviews">
		@else 
		<input class="selectvalue" type="hidden" name="sort_by" id="sort_by" value="top_rated">
		@endif

		<input class="service-category-id" type="hidden" name="service_category_id" id="service_category_id" value="">
		<input type="hidden" name="filter['category']" id="service-category-id" value="{{isset($current_category) ? $current_category : '' }}" />
		<input type="hidden" name="filter['subcategory']" id="service-subcategory-id" value="{{isset($defaultSubcatId) ? $defaultSubcatId : '' }}" />

		<div class="row">
			<div class="col-md-6 col-12 d-flex justify-content-between align-items-center align-items-md-start service-listings">
				<ul class="cus-breadcrumb">
					<li><a href="{{url('/')}}">Home</a></li>
					<li><a href="javascript:void(0)">Courses</a></li>
					@if (isset($getCategoryId) && isset($getCategoryId->category_name))
						<li><a href="javascript:void(0)" id="category_name">{{ $getCategoryId->category_name }}</a></li>
					@endif
				</ul>
				<div id="activate-toggle">
					<span class="mr-2 d-md-none activate-btn rounded-circle">
						<img src="{{url('public/frontend/images/Filter-icon.svg')}}" class="img-fluid cus-filter-icon" alt="">
					</span>
					<span class="mr-2 d-md-none cus-close-btn rounded-circle">
						<img src="{{url('public/frontend/images/Filter-with-cancel.svg')}}" class="img-fluid cus-filter-icon" alt="">
					</span>
				</div>
			</div>
			<div class="col-md-6 col-12 text-right cus-deactive">
				<div class="cus-sort-by sortby-listing">
					<span class="online-seller-span">
						<label class="mb-0">
							Online Authors &nbsp;
						</label>
						<label class="cus-switch  category-toggle">
							<input type="checkbox" name="online_seller" class="togglethreeplans toggle-input online_seller " id="online_seller" value="1">
							<span class="checkslider round"></span>
						</label>
						<input type="hidden" name="online_seller_val" id="online_seller_val" value="0">
					</span>

					@if(!in_array(Request::segment(1),['recently-uploaded','top-rated','best-seller','recurring']))
					<a href="#" class="dropdown-toggle sortby-listing" data-toggle="dropdown" role="button" aria-expanded="false">Sort by <span class="selectvalue">{{(Request::segment(1) == 'review-editions')?'Default Sorting':'Top Rated'}}</span>
						<i class="fa fa-angle-down"></i>
					</a>

					<ul class="dropdown-menu" role="menu">
						@if(Request::segment(1) == 'review-editions')
						<li><a data-id="least_reviews" href="javascript:void(0)" class="sort_by">Default Sorting</a></li>
						@else
						<li><a data-id="top_rated" href="javascript:void(0)" class="sort_by">Top Rated</a></li>
						@endif
						<li><a data-id="recently_uploaded" href="javascript:void(0)" class="sort_by">Recently Uploaded</a></li>
						<li><a data-id="most_popular" href="javascript:void(0)" class="sort_by">Most Popular</a></li>
						<li><a data-id="low_to_high" href="javascript:void(0)" class="sort_by">Price (low to high)</a></li>
						<li><a data-id="high_to_low" href="javascript:void(0)" class="sort_by">Price (high to low)</a></li>
					</ul>
					@endif
					
				</div>
			</div> 
		</div>    
	</div>
</section>
<section class="search-block">
	<div class="container">
		<div class="row">
			<div class="col-md-3 cus-deactive mt-4 mt-md-0">
				<div class="sticky-block1 sidebar sidebar-overflow cus-sticky">
					<div class="filter-box custom">
						<div class="filter-title">
							<h4>Filter Results</h4><a href="{{ route('courses') }}"><span class="clearall">Clear all</span></a>
						</div>

						<div class="category-box category-list">
							<div class="all-cat custom-filte-header">All Category<span></span></div>
							<ul class="all-cat-list ">

								@foreach($categories as $category)
								<li class="{{ $category->seo_url == Request::segment(2)  ? 'active' : '' }}">
									<a href="javascript:void(0)" id="{{$category->id}}" class="category-name default-gray">
										{{$category->category_name}}
									</a>
								</li>

								@endforeach
							</ul>
							<hr>
						</div>
						<div class="subcategory-list">
							<div class="all-cat custom-filte-header">Sub Category<span></span></div>
							<ul class="all-cat-list" id="subcategories">
								@if(count($subcategories))
								@foreach($subcategories as $key => $subcategory)
								<li class="{{$subcategory->seo_url == Request::segment(3)  ? 'active' : '' }}" id="{{$subcategory->id}}">
									<a href="javascript:void(0)" id="{{$subcategory->id}}" class="subcategory-name default-gray">{{$subcategory->subcategory_name}}</a>
								</li>
								@endforeach

								@else

								<?php 
								$seo_url = Request::segment(2);
								$current_category = App\Category::where('seo_url', $seo_url)->first(); 
								if(count($current_category)){
									$subcategories_new = App\Subcategory::where('category_id', $current_category->id)->where('status',1)->get();
								}else{
									$subcategories_new = [];
								}
								?>
								@if(count($subcategories_new))
								@foreach($subcategories_new as $key => $subcategory)
								<li class="{{$subcategory->seo_url == Request::segment(3)  ? 'active' : '' }}" id="{{$subcategory->id}}">
									<a href="javascript:void(0)" id="{{$subcategory->id}}" class="subcategory-name default-gray">{{$subcategory->subcategory_name}}</a>
								</li>
								@endforeach
								@endif
								@endif
							</ul>
							<hr>
						</div>

						<div class="price-box">
							<p class="custom-filte-header">Price Range</p>
							<div class="row">
								<div class="col-md-12">
									<div class="price-filter">
										<div class="input-group">
											<span class="input-group-addon"><i class="fas fa-dollar-sign"></i></span>

											<input type="number" class="form-control shadow-none" id="min_price" placeholder="Min">
											
										</div>
										<div class="input-group">

											<span class="input-group-addon"><i class="fas fa-dollar-sign"></i></span>

											<input type="number" class="form-control shadow-none" id="max_price" placeholder="Max" class="">

										</div>
										<button type="submit" class="btn shadow-none"><i class="fa fa-search" onclick="filter();"></i></button>
									</div>
								</div> 
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-9 mt-4 mt-md-0">
			
				<div class="legt-listing-container legt-grid-view filter-days services-filter-listing">
                    @include('frontend.course.filter_courses')
				</div>
				<div class="col-sm-12 text-center">
					<img src="{{url('public/frontend/assets/img/filter-loader.gif')}}" class="ajax-load"> 
				</div>
			</div>
		</div>
	</div>
</section>

{{-- modal for pick service for rent spot --}}
<div class="modal fade custompopup" id="select_your_service_for_rent_spot_modal_id" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Pick your service to promote!</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				{{ Form::open(['route' => ['rentAdSpot'], 'method' => 'POST', 'id' => 'rent_ad_spot_form_id']) }}
				<input type="hidden" id="plan_secret" name="plan_secret">
				<div class="form">
					<div class="form-body">
						<div class="form-group rent_spot_pick_service_div">
							<select name="search_service_term" class="form-control rent_spot_pick_service select2-multiple">
								<option value=""></option>
							</select>
							<p class="error text-danger hide rent_spot_pick_service_error">Please select service</p>
						</div>
					</div>
				</div>
				{{ Form::close() }} 
				{{-- <h5>Please select service to see preview</h5> --}}
				<div class="rent_spot_service_card_preview"></div>
			</div>
			<div class="modal-footer">
				{!! Form::button('Promote It',['id' => 'promote_service_for_rent_spot_btn', 'class' => 'send-request-buttom']) !!}
				{!! Form::button('Cancel',['id' => 'cancel_promote_service_modal_btn','class' => 'cancel-request-buttom']) !!}
			</div>
			
		</div>
	</div>
</div>
{{-- modal for pick service for rent spot --}}
@endsection

@section('scripts')
<script src="{{front_asset('js/vendor/jquery.tooltipster.min.js')}}"></script>
<script src="{{front_asset('js/shop2.js')}}"></script>
<script src="{{ asset('resources/assets/js/ad_rent_spot.js') }}"></script>
<script type="text/javascript">
	(function ($) {
		/**Pegination on scroll  */
		$(window).scroll(function() {
			if($(window).scrollTop() + $(window).height() >= ($(document).height() - $('footer').height() )) {
				if(call_pagination == 0){
					page++;
					loadMoreDataForSevicePageFilter(page);
				}
			}
		});
	})(jQuery);
</script>
@endsection