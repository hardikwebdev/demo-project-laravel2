@php
use App\Commands\SortableTrait;
@endphp
@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Orders')
@section('content')

<section class="extended-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading mb-3">Manage Extended Orders</h2>
			</div>    
		</div>    
	</div>    
</section>

<section class="custom-order-section sponsored-section block-section">
	<div class="container">
		<div class="cus-filter-data">
			<div class="cus-container-two">    
				<div class="table-responsive">
					<table class="manage-sale-tabel custom">
						<thead>
							<tr class="manage-sale-head table-row-text-center">
								<td>SERVICE</td>
								<td>ORDER DATE</td>
								<td>DUE ON</td>
								@if(isset($_GET['status']) && $_GET['status']=='delivered')
								<td>DELIVERED AT</td>
								@endif
								<td>EXTENDED DAY(S)</td>
								<td>STATUS</td>
								<td>REJECTION REASON</td>
							</tr>
						</thead>
						<tbody>
							@foreach($Order as $val => $row  )						
							<tr class="table-row-text-center">
								<td class="custom-default-service-name">
									@if($row->order->service->is_custom_order == 0)
									<a href="{{route('buyer_orders_details',$row->order->order_no).'?extend='.$row->is_accepted}}" class="text-capitalize">{{$row->order->service->title}}</a>
									@else
									<a href="{{route('buyer_orders_details',$row->order->order_no).'?extend='.$row->is_accepted}}">
										@if(strlen($row->order->service->descriptions) > 60)
										{!! nl2br(substr($row->order->service->descriptions, 0, 60)) !!}...
										@else
										{!! nl2br($row->order->service->descriptions) !!}
										@endif
									</a>
									@endif
								</td>
								<td>
									@if($row->order->start_date)
									{{date('d M Y',strtotime($row->order->start_date))}}
									@else
									-
									@endif
								</td>
								<td>
									@if($row->order->end_date)
									{{date('d M Y',strtotime($row->order->end_date))}}
									@else
									-
									@endif
								</td>
								@if(isset($_GET['status']) && $_GET['status']=='delivered')
								<td>
									@if($row->delivered_date)
									{{date('d M Y',strtotime($row->order->delivered_date))}}
									@else
									-
									@endif
								</td>
								@endif
								<td>
									@if(isset($row->id))
									{{$row->extend_days}}
									@else
									-
									@endif
								</td>							
								<td>
									@if(isset($row->id))
									@if($row->is_accepted == 0)
									Pending
									@elseif($row->is_accepted == 1)
									Accepted
									@else
									Rejected
									@endif
									@else
									-
									@endif
								</td>
								<td style="word-wrap: break-word;word-break: break-all;">
									@if(isset($row->buyer_note))
									{{$row->buyer_note}}
									@else
									-
									@endif
								</td>		
							</tr>
							@endforeach
							@if(count($Order)==0)
							<tr>
								<td colspan="7" class="text-center">
									No order found
								</td>
							</tr>
							@endif
						</tbody>
					</table>

					<div class="clearfix"></div>
					
					<!-- /PAGER -->
					<div class="text-center">@if(isset($Order)){{ $Order->links("pagination::bootstrap-4") }}@endif</div><br>
					<center>
						<div class="update-profile-btn back-button-width"> 
							<a href="{{route('seller_orders')}}" class="button secondary">
								<button type="button" class="btn">Back to orders</button>
							</a>
						</div>
					</center>
				</div>
			</div>
		</div>
	</div>        
</section>  

@endsection

@section('css')
{{-- <link rel="stylesheet" href="{{front_asset('css/bootstrap.min.css')}}"> --}}
<link href="{{ web_asset('plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">
<style type="text/css">
	.datepicker>div{display:block}
	.cke_reset_all table {
		z-index: 100008 !important;
	}
	.section{
		padding:0px;
	}
	.custom-back{
		margin-left: 45%;
		margin-bottom: 25px;

	}
	@media screen and (max-width: 1600px){
		.btn_set{
			width:40%;
			font-size: 12px;
			margin-top:0px;
		}
		#status_id{
			height:40px;
		}
	}
	@media screen and (max-width: 1260px){
		.service-menu .sidebar-menu .sidebar-menu-item {
			float: left;
			width: auto;
		}
		.form-box-items{
			overflow-x: scroll;
		}
		#status_id{
			height:40px;
		}
	}
	@media screen and (max-width: 767px){
		.service-menu .sidebar-nav {
			height: auto;
		}
		.btn_set{
			width:60%;font-size: 12px;
		}
		#status_id{
			height:40px;
		}
	}
	@media screen and (max-width: 630px){
		.btn_set{
			width:100%;
			font-size: 12px;
			margin-left:20px;
		}
		#status_id{
			height:40px;
		}
	}
</style>
@endsection

@section('scripts')
<script src=" {{front_asset('js/bootstrap-datepicker.js')}}"  type="text/javascript"></script>
<script type="text/javascript">
	$(document).ready(function () {

		// $('.order_note_textarea').each(function(){
		// 	CKEDITOR.replace( $(this).attr('id'),{
		// 		height: 300,
		// 	});
		// 	CKEDITOR.instances[$(this).attr('id')].updateElement();
		// });

		// $('.open-new-message').on('click',function(){
		// 	var id = $(this).attr('data-id');
		// 	var instname = "order_note_"+id;
		// 	if (CKEDITOR.instances[instname])
		// 	{
		// 		CKEDITOR.instances[instname].destroy();
		// 	}
		// 	CKEDITOR.replace( 'order_note_'+id,{
		// 		height: 300,
		// 	});
		// });

		$('.open-new-message').magnificPopup({
			type: 'inline',
			removalDelay: 300,
			mainClass: 'mfp-fade',
			closeMarkup: '<div class="close-btn mfp-close"><svg class="svg-plus"><use xlink:href="#svg-plus"></use></svg></div>'
		});
		// $('.form-box-items form').on('submit',function(event){
		// 	event.preventDefault();
		// 	var formdata = $(this).serializeArray();
		// 	CKEDITOR.instances["order_note_"+formdata[1]['value']].updateElement();

		// 	$.ajax({
		// 		type: "POST",
		// 		url: $(this).attr('action'),
		// 		dataType: 'json',
		// 		cache: false,
		// 		data: $(this).serialize(),
		// 		success: function(data) {
		// 			if(data.success == true){
		// 				$('#note-'+data.id).html(data.note);
		// 				alert_success(data.message);
		// 				$('.mfp-close').click();
		// 			} else {
		// 				alert_error(data.message);
		// 			}
		// 		}
		// 	});
		// });

		$('.filterToggle').on('click',function(){
			$('#filtersBox').slideToggle(500);
		});

		if($('#from_date').val() == ''){
			$('#to_date').attr('disabled',true);
		}
	});

	$(function(){
		$('.datepicker').datepicker({
			startDate: -Infinity
		});

		$('#serachBtn').on('click',function(e){
			var todate = $("#to_date").val();
			var fromdate = $("#from_date").val();
			if(fromdate!=''){
				if(todate==''){
					e.preventDefault();
					alert_error('please select end date');
				}else{
					$('#serachBtn').submit();
				}
			}
		});
	});

	$('#from_date').on('change',function(){
		var dates = $("#from_date").val();
		if(dates==''){
			$('#to_date').attr('disabled',true);
		}else{
			$('#to_date').attr('disabled',false);
		}
	});
</script>
@endsection
