@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Services')
@section('content')

<div class="section-headline-wrap">
	<div class="section-headline">
		<h2>Update Services</h2>
		<p>Home<span class="separator">/</span><span class="current-section">Update Services</span></p>
	</div>
</div>

@include('frontend.service.header')

<div class="dashboard-content">
	<!-- FORM BOX ITEMS -->
	<div class="form-box-items">
	 	<!-- FORM BOX ITEM -->
		<div class="promo-banner dark fullimage" >

			<h1 style="margin-bottom: 20px;">Almost <span>There...</span></h1>
			<h5>Let's publish your Service and get some buyers rolling in.</h5>
			<br><br>

			

			{{ Form::open(['route' => ['service_publish',$Service->seo_url], 'method' => 'POST', 'id' => 'frmPublishOverview']) }}

			<input type="hidden" name="current_step" value="6">
			<input type="hidden" name="status" value="active">

			<input type="submit" value="Publish Service" class="button big primary">

			{{ Form::close() }}

		</div>
		<!-- /FORM BOX ITEM -->

	</div>
	<!-- /FORM BOX -->
</div>

@endsection

@section('scripts')

@endsection