@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Analytics')
@section('content')


<!-- Get Project -->

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Analytics</h2>
			</div>    
		</div>    
	</div>    
</section>

<section class="get-project transactions-section">
	<div class="container">
		<div class="row">
			<div class="col-cus-5 col-md-4 col-sm-12">
				<div class="project-block text-center">
					<div class="project-number">
						{{number_format($total_service_view,0)}} 
						<span class="people-text">People</span> 
					</div>
					<div class="project-detail">
						<div class="project-title">Total Viewed Service</div>
					</div>
				</div>
			</div>
			<div class="col-cus-5 col-md-4 col-sm-12">
				<div class="project-block text-center">
					<div class="project-number">
						{{number_format($total_in_cart,0)}}
						<span class="people-text">People</span> 
					</div>
					<div class="project-detail">
						<div class="project-title">Total Add to cart</div>
					</div>
				</div>
			</div>
			<div class="col-cus-5 col-md-4 col-sm-12">
				<div class="project-block text-center">
					<div class="project-number">
						{{number_format($total_orders,0)}}
						<span class="people-text">People</span> 
					</div>
					<div class="project-detail">
						<div class="project-title">Total Purchase</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<section class="get-project transactions-section pt-0">
	<div class="container">

		{{-- Service view --}}
		<div class="chart_service_view">
			<select class="form-control1" data-type="service_view">
				@foreach($yearList as $key => $val)
			    <option {{($val == date('Y'))?'selected':''}}>{{$val}}</option>
				@endforeach
			</select>
			<div id="service_view"></div>
		</div>

		{{-- Add to Cart --}}
		<div class="chart_cart mt-3">
			<select class="form-control1" data-type="add_to_cart">
				@foreach($yearList as $key => $val)
			    <option {{($val == date('Y'))?'selected':''}}>{{$val}}</option>
				@endforeach
			</select>
			<div id="add_to_cart"></div>
		</div>

		{{-- Purchase --}}
		<div class="chart_purchase mt-3">
			<select class="form-control1" data-type="purchase">
				@foreach($yearList as $key => $val)
			    <option {{($val == date('Y'))?'selected':''}}>{{$val}}</option>
				@endforeach
			</select>
			<div id="purchase"></div>
		</div>

	</div>
</section>
<!-- Get Project -->
@endsection

@section('css')
<style type="text/css">
	.chart_service_view,.chart_cart,.chart_purchase {
		position:relative;
	}
	.chart_service_view select,.chart_cart select,.chart_purchase select{
	    position:absolute;
	    right:3%;
	    top:15px;
	    z-index:5;
	}
</style>

@endsection

@section('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script type="text/javascript">
	var dark_mode = 0;
	dark_mode = "{{Auth::user()->web_dark_mode}}";
	var font_color = '#000000';
	var font_color2 = '#333333';
	var font_color3 = '#666666';
	var back_ground_color = '#FFFFFF';
	if(dark_mode == "1") {
		back_ground_color = '#36353B';
		font_color = "#FFFFFF";
		font_color2 = "#FFFFFF";
		font_color3 = "#FFFFFF";
	}
	$( document ).ready(function() {
		$(document).on('change','.chart_service_view select,.chart_cart select,.chart_purchase select',function(){
			var type = $(this).data('type');
			ajaxCall(type,this.value);
		});
	});

	ajaxCall('service_view','{{date('Y')}}');
	ajaxCall('add_to_cart','{{date('Y')}}');
	ajaxCall('purchase','{{date('Y')}}');

	function ajaxCall(type,year){
		$.ajax({
			url: '{{route('search_analiyics')}}',
			method: "GET",
			data: {'type' : type,'year':year},
			dataType: "json",
			success: function(result){
				chart_load(type,year,result);
			}
		});
	}

	function chart_load(type,year,result) {

		if(type == 'service_view'){
			var title_msg = 'Service View Analytics - '+year;
		}else if(type == 'add_to_cart'){
			var title_msg = 'Added To Cart Analytics - '+year;
		}else{
			var title_msg = 'Purchase Analytics - '+year;
		}

		Highcharts.chart(type, {
			credits: {
			    enabled: false
			},
		    chart: {
		        type: 'column',
				backgroundColor: back_ground_color,
				style: {
					color: font_color2
				},
		    },
		    title: {
		        text: title_msg,
				style: {
			        color: font_color2,
				}
		    },
		    subtitle: {
		        text: '',
				style: {
					color: font_color2,
				}
		    },

		    xAxis: {
		        categories: [
		            'Jan',
		            'Feb',
		            'Mar',
		            'Apr',
		            'May',
		            'Jun',
		            'Jul',
		            'Aug',
		            'Sep',
		            'Oct',
		            'Nov',
		            'Dec'
		        ],
		        crosshair: true,
				labels: {
					style: {
						color: font_color3,
					}
				}
		    },
		    yAxis: {
		    	allowDecimals: false,
		        min: 0,
		        title: {
		            text: 'Number of people',
					style: {
						color: font_color3,
					}
		        },
				labels: {
					style: {
						color: font_color3,
					}
				}
		    },
		    tooltip: {
		        headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
		        pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
		            '<td style="padding:0"><b>&nbsp;{point.y}</b></td></tr>',
		        footerFormat: '</table>',
		       // shared: true,
		        useHTML: true
		    },
		    plotOptions: {
		        column: {
		            pointPadding: 0.2,
		            borderWidth: 0
		        }
		    },
		    series: result,
			legend: {
				itemStyle: {
					color: font_color
				}  
			}
		});
	}

	
</script>
@endsection


