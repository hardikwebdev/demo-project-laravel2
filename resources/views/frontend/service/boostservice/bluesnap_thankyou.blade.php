@extends('layouts.frontend.main')
@section('pageTitle','demo - Bluesnap Thankyou')

@section('css')
<style type="text/css">
	.banner-wrap{
		min-height: 250px;
		background-image: none;
	}
	.tranction-detail{
		width: 100% !important;
		color:#2b373a !important;
		font-size: 18px !important;
	}
	.text-green{
		color: #16ffd8 !important;
	}
	h3{
		color: #16ffd8
	}
	.loader {
		margin: 0 auto 15px auto;
		border: 8px solid #3a494d;
		border-radius: 50%;
		border-top: 8px solid #16ffd8;
		width: 60px;
		height: 60px;
		-webkit-animation: spin 2s linear infinite; /* Safari */
		animation: spin 2s linear infinite;
	}

	/* Safari */
	@-webkit-keyframes spin {
		0% { -webkit-transform: rotate(0deg); }
		100% { -webkit-transform: rotate(360deg); }
	}

	@keyframes spin {
		0% { transform: rotate(0deg); }
		100% { transform: rotate(360deg); }
	}
	.loader_div
	{
		top: 50%;
		position: absolute;
		transform: translateY(-50%);
		text-align: center;
		width: 100%;
	}
</style>
@endsection


@section('content')
<section class="cart-block displaynone"> 
	<div class="container">
		<div class="row"> 
			<div class="col-lg-12"> 
				<div id="myOverlay"></div>
				<div id="loadingGIF">
					{{ Html::image('public/frontend/images/lg.circle-slack-loading-icon.gif') }}
				</div>
				<div class="banner-wrap" >
					<section class="banner">
						<div class="loader_div">
							<div class="loader"></div>
							<p class="tranction-detail">Your order has been processing, Do not refresh page</p>
							<p class="tranction-detail">Payment Ref: #{{$invoice_id}}</p>
						</div>
					</section>
				</div>
			</div>
		</div>
	</div>
</section>  
@endsection

@section('scripts')
<script type="text/javascript">
	$(document).ready(function() {
		
		setInterval(function(){checkpayment();}, 5000);

		function checkpayment(){
			$.ajax({
				url : "{{ route('bluesnap.boostservice.checkpayment') }}",
				type : 'get',
				dataType: 'json',
				data : {'invoice_id':'{{$invoice_id}}'},
				success : function(data){
					if(data.success==true){
						window.location.href = data.url;
					}
				}
			});
		}
	});
</script>
@endsection