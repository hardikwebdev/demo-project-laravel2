@extends('layouts.frontend.main')

@if(isset($getSubCategoryId->seo_title) && $getSubCategoryId->seo_title != null)
@section('pageTitle',$getSubCategoryId->seo_title .' - Services')
@elseif(isset($getCategoryId->seo_title) && $getCategoryId->seo_title != null)
@section('pageTitle',$getCategoryId->seo_title .' - Services')
@else
@section('pageTitle','demo - Affiliates')
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

<!-- Masthead -->
@php
$bannerBg = $bannerText =$bannerTextColor =$bannerBgColor = $bannerTextSize = $bannerSubText =$bannerSubTextSize = $bannerSubColor = '';
	foreach($bannerGeneral as $bannerValue){
		if($bannerValue->settingkey == 'affiliate_banner'){
			$bannerBg = $bannerValue->settingvalue;
		}elseif($bannerValue->settingkey == 'affiliate_text'){
			$bannerText = trim($bannerValue->settingvalue);
		}elseif($bannerValue->settingkey == 'affiliate_text_color'){
			$bannerTextColor = $bannerValue->settingvalue;
		}elseif($bannerValue->settingkey == 'affiliate_bg_color'){
			$bannerBgColor = $bannerValue->settingvalue;
		}elseif($bannerValue->settingkey == 'affiliate_text_size'){
			$bannerTextSize = $bannerValue->settingvalue;
		}elseif($bannerValue->settingkey == 'affiliate_sub_text'){
			$bannerSubText = trim($bannerValue->settingvalue);
		}elseif($bannerValue->settingkey == 'affiliate_sub_text_size'){
			$bannerSubTextSize = $bannerValue->settingvalue;
		}elseif($bannerValue->settingkey == 'affiliate_sub_text_color'){
			$bannerSubColor = $bannerValue->settingvalue;
		}
	}
@endphp
@if($bannerBg != '' || $bannerText != '' || $bannerSubText != '' )
	<header class="masthead promoheade" style="background-image:url('{{($bannerBg != '') ? $bannerBg : ''}}'); background-color:{{ ($bannerBgColor != null) ? $bannerBgColor : '#ffb502' }};">
		<div class="container">
			<div class="row">
				<div class="col-md-8 col-lg-6 col-xl-6 col-10">
					@if(isset($bannerText) && $bannerText != '' )
						<h1 class="mb-2" style="color:{{($bannerTextColor != null ) ? $bannerTextColor : '#000'}};font-size:{{ ($bannerTextSize != null ) ? $bannerTextSize : '34' }}px">{!! ($bannerText != null ) ? $bannerText : 'Grab These Hot Deals Before</br> They Expire…' !!}</h1>
					@endif
					@if(isset($bannerSubText) && $bannerSubText != '' )
						<h3 class="" style="color:{{($bannerSubColor != null ) ? $bannerSubColor : '#000'}};font-size:{{ ($bannerSubTextSize != null ) ? $bannerSubTextSize : '22' }}px">{!! $bannerSubText !!}</h3>
					@endif
				</div>
			</div>
		</div>
	</header>
@else
	<header class="masthead promoheade">
		<div class="container">
			<div class="row">
				<div class="col-md-8 col-lg-6 col-xl-6">
					<p class="mb-3" style="color:{{($bannerTextColor != null ) ? $bannerTextColor : '#000'}};font-size:{{ ($bannerTextSize != null ) ? $bannerTextSize : '34' }}px">Grab These Hot Deals Before</br> They Expire…</p>
				</div>
			</div>
		</div>
	</header>
@endif
<section class="header-breadcrumb" id="location">
	<div class="container">

		{{-- Hidden Fields --}}
		@if(Request::segment(1) == "recently-uploaded")
		<input class="selectvalue" type="hidden" name="sort_by" id="sort_by_promo" value="recently_uploaded">
		@else 
		<input class="selectvalue" type="hidden" name="sort_by" id="sort_by_promo" value="top_rated">
		@endif

		<input class="service-category-id" type="hidden" name="service_category_id" id="service_category_id" value="">

		<input type="hidden" name="filter['category']" id="service-category-id" value="{{isset($current_category) ? $current_category : '' }}" />

		<input type="hidden" name="filter['subcategory']" id="service-subcategory-id" value="{{isset($defaultSubcatId) ? $defaultSubcatId : '' }}" />

		<input type="hidden" name="filter['delivery_days']" id="delivery_days" value="any" />

		<div class="row">
			<div class="col-md-6 col-12 d-flex justify-content-between align-items-center align-items-md-start">
				<ul class="cus-breadcrumb">
					<li><a href="{{url('/')}}">Home</a></li>
					<li><a href="javascript:void(0)">Affiliate Offers</a></li>
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
							@if(Request::segment(1) == "recently-uploaded")
							<h4>Filter Results</h4><a href="{{ route('affiliate_offers') }}"><span class="clearall">Clear all</span></a>
							@else
							<h4>Filter Results</h4><a href="{{ route('affiliate_offers') }}"><span class="clearall">Clear all</span></a>
							@endif
						</div>
						<div class="category-box category-list">
							<div class="all-cat custom-filte-header">All Category<span></span></div>
							<ul class="all-cat-list ">

								@foreach($categories as $category)
								<li class="{{ $category->seo_url == Request::segment(2)  ? 'active' : '' }}">
									<a href="javascript:void(0)" id="{{$category->id}}" class="category-name-promo default-gray">
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
									<a href="javascript:void(0)" id="{{$subcategory->id}}" class="subcategory-name-promo default-gray">{{$subcategory->subcategory_name}}</a>
								</li>
								@endforeach

								@else

								<?php 
								$seo_url = Request::segment(2);
								$current_category = App\Category::where('seo_url', $seo_url)->first(); 
								if(count($current_category)){
									$subcategories_new = App\Subcategory::where('category_id', $current_category->id)->get();
								}else{
									$subcategories_new = [];
								}
								?>
								@if(count($subcategories_new))
								@foreach($subcategories_new as $key => $subcategory)
								<li class="{{$subcategory->seo_url == Request::segment(3)  ? 'active' : '' }}" id="{{$subcategory->id}}">
									<a href="javascript:void(0)" id="{{$subcategory->id}}" class="subcategory-name-promo default-gray">{{$subcategory->subcategory_name}}</a>
								</li>
								@endforeach
								@endif
								@endif
							</ul>
							<hr>
						</div>
						<div class="delivery-day-block">
							<p class="custom-filte-header">Delivery Days</p>
							<ul class="delivery-radio">
								<li>
									<input type="radio" id="10days" name="delivery_days" class="delivery-days" value="10">
									<label for="10days">Up to 10 days</label>

									<div class="check"><div class="inside"></div></div>
								</li>

								<li>
									<input type="radio" id="20days" name="delivery_days" class="delivery-days" value="20">
									<label for="20days">Up to 20 days</label>

									<div class="check"><div class="inside"></div></div>
								</li>
								<li>
									<input type="radio" id="30days" name="delivery_days" class="delivery-days" value="30">
									<label for="30days">Up to 30 days</label>

									<div class="check"><div class="inside"></div></div>
								</li>
								<li>
									<input type="radio" id="any" name="delivery_days" class="delivery-days" value="any" checked="checked">
									<label for="any">Any</label>
									<div class="check"><div class="inside"></div></div>
								</li>
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

											<input type="number" class="form-control" id="min_price" placeholder="Min">
											
										</div>
										<div class="input-group">

											<span class="input-group-addon"><i class="fas fa-dollar-sign"></i></span>

											<input type="number" class="form-control" id="max_price" placeholder="Max" class="">

										</div>
										<button type="submit" class="btn"><i class="fa fa-search" onclick="filterpromo();"></i></button>
									</div>
								</div> 
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-9 mt-4 mt-md-0">
				<div class="legt-listing-container legt-grid-view filter-days services-filter-listing">
				
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
					<div class="cus-grid-full text-center">
						<span class="no-service-found">No services are available.</span>
					</div>
						@if(request()->has('search_by'))

							<div class="cus-grid-full text-center">
								<div class="row">
									<div class="col-12 text-center checkLabel">
										<label class="searching-feedback">If you don't find services for your search, please create a job for it.</label>
									</div>
									<div class="col-12">
										@if(Auth::check())
										<a href="{{route('jobs.create')}}" class="btn btn-info postJob"> Post a Job </a>
										@else
											<a href="{{url('login')}}?jobAdd=1" class="btn btn-info postJob"> Post a Job </a>
										@endif
									</div>
								</div>
								
							</div>
						@endif
					@endif
				</div>
				<div class="col-sm-12 text-center">
					<img src="{{url('public/frontend/assets/img/filter-loader.gif')}}" class="ajax-load"> 
				</div>
		</div>
	</div>
</div>

</section>
@endsection

@section('scripts')
<script src="{{front_asset('js/vendor/jquery.tooltipster.min.js')}}"></script>
<script src="{{front_asset('js/shop2.js')}}"></script>
<script type="text/javascript">

function filterpromo() {
	page = 1;
	call_pagination = 0;

	$(".services-filter-listing").empty();
	loadMoreDataForPromoSevicePageFilter(page);
}
	(function ($) {
		/**Pegination on scroll  */
		$(window).scroll(function() {
			if($(window).scrollTop() + $(window).height() >= ($(document).height() - $('footer').height() )) {
				if(call_pagination == 0){
					page++;
					loadMoreDataForPromoSevicePageFilter(page);
				}
			}
		});

		/*clicking on category*/
		$(".category-name-promo").click(function () {
			if ($(this).attr('id') != "") {
				$("#service-category-id").val($(this).attr('id'));
				$("#service-subcategory-id").val('');
				$('.category-list ul li').removeClass('active');
				$(this).closest('li').addClass('active');
				$.ajax({
					type: "get",
					url: "{{route('viewPromoSubCategories')}}",
					data: {"_token":"{{csrf_token()}}", 'id': $(this).attr('id')}, 
					success: function (data)
					{
						if (data.status == 200) {
							$("#subcategories").empty();
							$("#subcategories").append(data.html);
						}
						filterpromo();
					}
				});
			}
		});

		/*clicking on sub category*/
		$("body").on("click", ".subcategory-name-promo", function () {
			$("#service-subcategory-id").val($(this).attr('id'));
			$('.subcategory-list ul li').removeClass('active')
			$(this).closest('li').addClass('active');
			filterpromo();
		});

		$("body").on("click", ".sort_by_promo", function () {
			$("#sort_by_promo").val($(this).data('id'));
			$('.sortby-listing a').removeClass('theme-color');
			$(this).addClass('theme-color');
			filterpromo();
		});

		$("body").on("click", ".online_seller", function () {
			if ($('#online_seller').is(":checked"))
			{
				$('#online_seller_val').val('1');
			}
			else
			{
				$('#online_seller_val').val('0');
			}
			filterpromo();
		});

		/* Clicking on delivery days radio button*/
		$('.delivery-days').change(function () {
			$("#delivery_days").val($(this).val());
			filterpromo();
		});
		/*On change seller language*/
		var sellerlanguages = [];

		$(".seller-languages").change(function () {
			var $this = $(this);
			if ($this.prop('checked')) {
				sellerlanguages.push($this.attr('name'));
			} else {
				sellerlanguages.splice($.inArray($this.attr('name'), sellerlanguages), 1);
			}
			$('#seller-languages').val(sellerlanguages);
			filterpromo();
		});
	})(jQuery);
	
	function loadMoreDataForPromoSevicePageFilter(page ){
			$('#myOverlay').show();
			$('#loadingGIF').show();

			var url = "{{route('AffiliateOffersFilter')}}";
			var online_seller = $('#online_seller_val').val();
			var q = $(".searchtext").val();
			var searchtext = $(".searchtext").val();
			var categoryid = $("#service-category-id").val();
			var subcategoryid = $("#service-subcategory-id").val();
			var deliverydays = $("#delivery_days").val();
			var sellerlanguages = $("#seller-languages").val();
			var pricerange = $("#price").val();
			var min_price = $('#min_price').val();
			var max_price = $('#max_price').val();
			var sort_by = $("#sort_by_promo").val();
			var search_by = $("#search_by").val();
			var page = page;

			$.ajax({
				method:"get",
				url:url,
				async:false,
				data:{'q': q,'searchtext': searchtext, 'categories': categoryid, 'subcategories': subcategoryid, 'deliverydays': deliverydays, 'sellerlanguages': sellerlanguages,'pricerange':pricerange,"min_price":min_price,"max_price":max_price,'sort_by':sort_by,'search_by':search_by,'online_seller' : online_seller,'page':page},
				beforeSend: function()
				{
					$('.ajax-load').show();
				}
			})
			.done(function(data)
			{
				if(data == ""){
					$('.ajax-load').html("No more records found");
					call_pagination=1;
				}
				$('.ajax-load').hide();
				$('.services-filter-listing').append(data);

				setTimeout(function () {
					$('#myOverlay').hide();
					$('#loadingGIF').hide();
				}, 500);

			})
			.fail(function(jqXHR, ajaxOptions, thrownError)
			{
				setTimeout(function () {
					$('#myOverlay').hide();
					$('#loadingGIF').hide();
				}, 500);
				console.log(thrownError);
				alert('server not responding...');
			});
		}
</script>
@endsection