@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Suspension/Reactivation Procedure')
@section('content')
<div class="section-headline-wrap">
	<div class="section-headline">
		<h2>Suspension/Reactivation Procedure</h2>
	</div>
</div>
<div id="myOverlay"></div>
<div class="banner-wrap" >
	<section class="banner" >
		<!-- <h2 class="text-blue">
			<span class="icon-check"></span>
		</h2>
		<br> -->
		<!-- <h2 class="text-black"></h2> -->

		<p class="text-black">Your account has been suspended due to an extended period of inactivity.  <br>To request that your account be reactivated, please follow the button below.</p><br>
		{{ Form::open(['route' => ['reactivation'], 'method' => 'POST', 'id' => 'frmReactivation']) }}

		<div class="input-container form-group">
		{{Form::textarea('reason','',["class"=>"form-control","placeholder"=>"Write your reason here..."])}}
		</div>
		
		<br><br>
		<p class="text-center">
			<button type="submit" class="button secondary login-btn">Request Reactivation</button>
		</p>
		{{Form::close()}}
	</section>
</div>
@endsection
@section('css')
<style type="text/css">
.banner-wrap{
	min-height: 400px;
	height: 400px;
	background: none;
	position: relative;
	display: block;
	overflow: hidden;
	width: 50%;
	margin: 0px auto;
}
.banner{
	padding:0px;
	min-height:0px!important;
	position: absolute;
	top:50%;
	text-align:center;
	left: 0;
	right: 0;
	transform: translateY(-50%);
	/*text-align: center;
	width: 100%;*/
}
p {
	width: 100% !important;
}
.text-green{
	color: #16ffd8 !important;
}
.text-blue{
	color: #55bef9 !important;
}
.text-black{
	color: #2b373a !important;	
}
h3{
	color: #16ffd8
}
.login-btn{
	display: inline-block;
	color: #fff !important;
}
</style>	
@endsection

@section('scripts')
@endsection