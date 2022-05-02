@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Buyer Transactions')
@section('content')
<div id="loadingGIF">
		{{ Html::image('public/frontend/images/lg.circle-slack-loading-icon.gif') }}
	</div>
<div class="banner-wrap" >
		<section class="banner">
			<input type="hidden" name="txn_id" id="txn_id" value="{{$txn_id}}">
			<div class="loader_div">
				<div class="loader"></div>
				<p class="tranction-detail">Your transaction has been processing, Do not refresh page</p>
				<p class="tranction-detail">Tranction Id: {{$txn_id}}</p>
			</div>
		</section>
	</div>
@endsection

@section('css')
	<style type="text/css">
	.banner-wrap{
		min-height: 350px;
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
		/*top: 50%;
		position: absolute;
		transform: translateY(-50%);*/
		margin-top: 100px !important;
		text-align: center;
		width: 100%;
	}
</style>	
@endsection

@section('scripts')
<script type="text/javascript">
		$(document).ready(function(){
			var txn_id = $('#txn_id').val();	
			window.setInterval(function(){
			  checkipn(txn_id);
			}, 3000);
		});	
		function checkipn(txn_id)
		{
			if(txn_id != "")
			{
				$.ajax({
					method:"GET",
					url:"{{URL('/cart/checkipnstatus')}}/"+txn_id,
					success:function(data)
					{
						if(data.success == true)
						{
							location.href = "{{url('/')}}/payment/details/"+txn_id;
						}
					}
				});
			}
		}
	</script>s
@endsection