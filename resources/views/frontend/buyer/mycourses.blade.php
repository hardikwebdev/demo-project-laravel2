@php
use App\Commands\SortableTrait;
use App\BillingInfo;
use App\Country;
@endphp
@extends('layouts.frontend.main')
@section('pageTitle', 'demo - My Courses')
@section('content')

<!-- Masthead -->
<header class="masthead text-white"> {{-- masthead  --}}
	<div class="overlay"></div>
    <div class="bg-dark w-100">
		<div class="container py-4">
			<h1 class="font-24 font-weight-bold font-lato text-white mb-0 py-3">My Courses</h1>
		</div>
    </div>
</header>

<div class="container pb-5 font-lato">
	{{ Form::open(['route' => ['buyer.mycourses'], 'method' => 'GET', 'id'=>'status_form_Search', 'name' => 'status_search','class' => 'sticky-filter-header']) }}
	<input type="hidden" name="total_filter_tags" id="total_filter_tags_id">
	<input type="hidden" name="page" id="order_paginate" value="1">
    <div class="row mt-4">
        <div class="col-12 col-md-5 pl-md-0 d-md-flex">
			<div class="d-flex align-items-center col-md-4 pr-0">
				<h1 class="font-18 text-color-2 mb-0 pr-2"><img src="{{front_asset('images/filter.png')}}" class="img-fluid mr-2 dark-to-white-img" alt=""> Filter by</h1>
			</div>
			<div class="mt-3 mt-md-0 col-md-8 px-0">
				{{ Form::text('search',isset($_GET['search'])?$_GET['search']:'',['class'=>'form-control font-14 text-color-4 summary', 'id'=>'search', 'placeholder'=>'Author Name', 'autocomplete'=>'off']) }}
			</div>
        </div>
        <div class="col-12 col-md-2 pl-md-0 mt-3 mt-md-0">
			{{Form::select('status', ['new' => 'Incomplete','on_hold'=>'On Hold', 'active' => 'Active', 'late' => 'Late', 'delivered' => 'Delivered', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'in_revision' => 'In Revision'], isset($_GET['status'])?$_GET['status']:'',['class'=>'form-control select_pr', 'id'=>'status_id','placeholder'=>'Order Status'])}}
        </div>
        <div class="col-12 col-md-2 pl-md-0 mt-3 mt-md-0">
			{{Form::select('created_by_filter', ['' => 'Select User']+$subusers,null,['class'=>'form-control capitalize-letter', 'id'=>'created_by_filter'])}}
		</div>
		<div class="col-12 col-md-3 pl-md-0  mt-3 mt-md-0 dropdown filter_tag_dropdown">
			<div class="dropdown-toggle cust-tag-filter form-control d-flex align-items-center filter_tag_drop" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<p class="font-14 text-truncate mb-0 filter_tag_name_list">Select Tag</p>
				<div class="d-flex align-items-center ml-auto mr-2">
					<span class="text-color-2 font-12 font-weight-bold ml-4 mr-2"><span id="total_number_of_filter_tags"></span></span>
                    <i class="fas fa-angle-down text-color-1"></i>
				</div>
			</div>
			<div class="row justify-content-center bg-white shadow  superstar border-radius-6px py-4 px-2 dropdown-menu legit-drop-scroll-y">
				<div class="col-12">
					<div class="d-flex justify-content-between">
						<p class="text-color-6 font-13 font-weight-bold">Filter by Tags</p>
						<a class="text-color-1 font-13 font-weight-bold cursor-pointer" id="deselect_all_filter_tags_btn">Deselect All</a>
					</div>
					<div class="d-flex flex-wrap" id="filter_tag_lists_id">
						@foreach ($OrderTags as $item)
							<a href="javascript:void(0)" class="filter_tag_add_btn mb-1" data-tagid="{{$item->id}}" data-tagname="{{$item->tag_name}}">
								<span class="bg-dark-white border-gray-1px border-radius-3px text-color-6 font-weight-bold font-12 px-2 py-1 mr-1 mt-1 cursor-pointer filter_tag_option_class tag_break" id="filter_tag_option{{$item->id}}">{{$item->tag_name}}</span>
							</a>
						@endforeach
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row mt-2 justify-content-end">
		<div class="col-12 col-md-2 pl-md-0 mt-3 mt-md-0 display-none from_datepicker">
			{{Form::text('from_date','',["class"=>"form-control custom_dates","placeholder"=>"From Date", "id"=>"from_datepicker", "data-provide" => "datepicker", "data-date-format"=>"mm/dd/yyyy", "autocomplete"=>"off", "readonly" => "readonly"])}}
		</div>
		<div class="col-12 col-md-2 pl-md-0 mt-3 mt-md-0 display-none to_datepicker">
			{{Form::text('to_date','',["class"=>"form-control custom_dates","placeholder"=>"To Date","id"=>"to_datepicker", "data-provide"=>"datepicker", "data-date-format"=>"mm/dd/yyyy", "autocomplete"=>"off", "readonly" => "readonly"])}}
		</div>
		<div class="col-12 col-md-2 pl-md-0 mt-3 mt-md-0 ">
			{{Form::select('filterbydate', ['' => 'All Time','today'=>'Today', 'week' => 'This Week', 'month' => 'This Month', 'year' => 'This Year','custom' => 'Custom Range'], isset($_GET['filterbydate'])?$_GET['filterbydate']:'',['class'=>'form-control', 'id'=>'filterbydate'])}}
        </div>
		<div class="col-12 col-md-1 pl-md-0 d-flex align-items-center mt-3 mt-xl-0">
			<a href="{{route('buyer.mycourses')}}" class="font-14 font-weight-bold text-color-1">Clear Filters</a>
        </div>
	</div>
	{{ Form::close() }}						
	<div id="tag_section_area">
		@include('frontend.buyer.include.order_list')
	</div>
</div>
@endsection

@section('css')
<link href="{{ web_asset('plugins/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ web_asset('plugins/select2/css/select2-bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">
<link href="{{ web_asset('plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('scripts')
<script type="text/javascript" src="{{front_asset('bootstrap/dist/js/bootstrap-tagsinput.js')}}"></script> 
<script type="text/javascript" src="{{url('public/frontend/js/price_range_script.js')}}"></script>
<script src="{{ web_asset('plugins/select2/js/select2.full.min.js')}}" type="text/javascript"></script>
<script src="{{ web_asset('plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js')}}" type="text/javascript"></script>
<script>
	var tag_list = {!! json_encode($OrderTags->toArray()) !!};
	var add_order_into_tag_route = "{{ route('add_order_into_tag') }}";
	var remove_tag_from_order_route = "{{ route('remove_tag_from_order') }}";
	var clear_all_tags_from_order_route = "{{ route('clear_all_tags_from_order') }}";
	var get_tags_list_route = "{{ route('get_tags_list') }}";
	var total_filter_tags = [];
	var total_filter_tag_names = [];
	var is_loader = true;
	/* get tag id and name from request params */
	var tag_params ="{{$_GET['total_filter_tags']}}";
	if(tag_params != undefined || tag_params != 'undefined') {
		if(tag_params.length > 0) {
			var total_filter_tags = tag_params.split(',');
			total_filter_tags.forEach((element,index) => {
				total_filter_tags[index] = parseInt(element);
				tag_list.forEach(tag => {
					if(element == tag.id) {
						total_filter_tag_names.push(tag.tag_name);
					}
				});
			});
		}
	}
</script>
<script src="{{ asset('resources/assets/js/order_tag.js?v='.env('CACHE_BUST')) }}"></script>

<script type="text/javascript">
  	$(document).ready(function () {
		$("#from_datepicker").datepicker({
			format: 'mm/dd/yyyy',
			autoclose: true,
		}).on('changeDate', function (selected) {
			var minDate = new Date(selected.date.valueOf());
			$('#to_datepicker').datepicker('setStartDate', minDate);
		});
		$("#to_datepicker").datepicker({
			format: 'mm/dd/yyyy',
			autoclose: true,
		}).on('changeDate', function (selected) {
			var minDate = new Date(selected.date.valueOf());
			$('#from_datepicker').datepicker('setEndDate', minDate);
		});
	});

	$(function(){
		/*$('.datepicker').datepicker({
			startDate: -Infinity
		});*/

		$('#serachBtn').on('click',function(e){
			$('#total_filter_tags_id').val(total_filter_tags);
			var todate = $("#to_date").val();
			var fromdate = $("#from_date").val();
			if(fromdate!=''){
				if(todate==''){
					e.preventDefault();
					toastr.error("please select end date.", "Error");
					// alert_error('please select end date');
				}else{
					$('#serachBtn').submit();
				}
			}
		});
		$('#serachBtn').on('click',function(e){
			var todate = $("#to_date").val();
			var fromdate = $("#from_date").val();
			if(todate!=''){
				if(fromdate==''){
					e.preventDefault();
					toastr.error("please select start date.", "Error");
					// alert_error('please select end date');
				}else{
					$('#serachBtn').submit();
				}
			}
		});
	});

	$('#filterbydate').on('change',function(e){
		var filter_value = $('#filterbydate').val()
		if(filter_value == 'custom'){
			$('.from_datepicker').show();
			$('.to_datepicker').show();
		}else{
			$('.from_datepicker').hide();
			$('.to_datepicker').hide();
		}
	});

	$(document).ready(function(){
        show_child_order_fc();
    });

	function show_child_order_fc(){
		// Toggle plus minus icon on show hide of collapse element
		$(".collapse").on('show.bs.collapse', function(){
        	$(this).prev(".clickable").find(".hideshowicon .fa").removeClass("fa-plus-circle").addClass("fa-minus-circle");
			$('.'+$(this).prev(".clickable").find(".hideshowicon").attr('id')); 
        }).on('hide.bs.collapse', function(){
        	$(this).prev(".clickable").find(".hideshowicon .fa").removeClass("fa-minus-circle").addClass("fa-plus-circle");
			$('.'+$(this).prev(".clickable").find(".hideshowicon").attr('id'));
        });
	}

	$(document).on('click', 'body .select2-container', function (e) {
		e.stopPropagation();
	});
	$(document).on('click', 'body .add_tag_dropdown', function (e) {
		e.stopPropagation();
	});
	$('body').on('click', function (e) {
		if (!$('.add_tag_dropdown  li.dropdown-menu').is(e.target) 
			&& $('.add_tag_dropdown  li.dropdown-menu').has(e.target).length === 0 
			&& $('.open').has(e.target).length === 0
		) {
			//$('.add_tag_dropdown .dropdown-menu').removeClass('open');
		}
	});
	$('.add_tag_dropdown').on('click', function (event) {
		$(this).parent().toggleClass('open');
	});
	$(document).on('click', 'body .clear_tag_dropdown', function (e) {
		e.stopPropagation();
	});
	$(document).on('click', 'body .filter_tag_dropdown', function (e) {
		e.stopPropagation();
	});
</script>

<script type="text/javascript">
	$(document).on('change','#status_form_Search select',function(){
		$('#order_paginate').val(1);
		if($(this).attr('id') == "filterbydate" && $(this).val() == "custom"){
			var fromdate = $("#from_datepicker").val();
			var todate = $("#to_datepicker").val();
			if(fromdate =='' || todate ==''){
				return;
			}
		}
		$('#status_form_Search').trigger('submit');
	});
	$(document).on('keyup','#status_form_Search input',function(){
		if($(this).attr('id') == "from_datepicker" || $(this).attr('id') == "to_datepicker"){
			var fromdate = $("#from_datepicker").val();
			var todate = $("#to_datepicker").val();
			if(fromdate =='' || todate ==''){
				return;
			}
		}
		if($(this).val().length > 2 || $(this).val().length == 0){
			$('#order_paginate').val(1);
			$('#status_form_Search').trigger('submit');
		}
	});
	$(document).on('change','.custom_dates',function(){
		if($(this).attr('id') == "from_datepicker" || $(this).attr('id') == "to_datepicker"){
			var fromdate = $("#from_datepicker").val();
			var todate = $("#to_datepicker").val();
			if(fromdate =='' || todate ==''){
				return;
			}
		}
		$('#order_paginate').val(1);
		$('#status_form_Search').trigger('submit');
	});
	
	/*Filter form submit ajax*/
	$(document).on('submit','#status_form_Search',function(e){
		e.preventDefault();
		search_order_list($(this).attr('action'),$(this).serialize());
	});
	/* Sort by ajax*/
	$(document).on('click','.custom-bold-header a',function(e){
		e.preventDefault();
		$('#order_paginate').val(1);
		search_order_list($(this).attr('href'));
	});
	/*Pagination*/
	$(document).on('click','.order-pagination a',function(e){
		e.preventDefault();
		var page = $(this).attr('href').split('page=')[1];
		$('#order_paginate').val(page);
		$('#status_form_Search').trigger('submit');
	});
	function search_order_list(url,formdata=[]){
		/* Loader for search*/
		if(is_loader == true){
			$('#tag_section_area .order-tab-body').html('<tr><td colspan="8" class="text-center p-0"><div class="lds-spinner"><div class="rect1"></div><div class="rect2"></div><div class="rect3"></div><div class="rect4"></div><div class="rect5"></div></div></td></tr>');
			$('#tag_section_area .order-pagination').remove();
		}

		$.ajax({
			type: "GET",
			url: url,
			dataType: 'json',
			cache: false,
			data: formdata,
			success: function(data) {
				if(data.status == 200){
					$('#tag_section_area').html(data.html);
					/*Reinitialise function*/
					update_all_tag_fns();
        			show_child_order_fc();
					
					/* Scroll Top */
					if(is_loader == true){
						window.scrollTo({ top: 150, behavior: 'smooth' });
					}
					/* END Scroll Top */
				} else {
					$('#tag_section_area .order-tab-body').html('');
					alert_error('Something went wrong.');
				}
				is_loader = true;
			},
			error: function(){
				is_loader = true;
				$('#tag_section_area .order-tab-body').html('');
				alert_error('Something went wrong.');
				setTimeout(function(){
					location.reload();
				},2000)
			}
		});
	}
</script>
@endsection