@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Extend Order Date')
@section('content')

<section class="order-detail-new"></section>
<section class="profile-header-new2 order-detail-section-box">
	<div class="profile-sec">
		<div class="cus-container container">
		<div class="row first-row align-items-center"> 	
<div class="section-headline-wrap">
	<div class="section-headline">
		<h2>Extend Order Date</h2>
		<p>Home<span class="separator">/</span><span class="current-section">Extend Order Date</span></p>
	</div>
</div>
</div>
</div>
<div class="profile-detail container">
			<div class="row second-row">
				<div class="desc-border"> 
					<div class="col-lg-12">
						<div class="profile-desc">	
			@if($isAccept == 2)
		{{-- 	<h4>Accept Extend Order Date Request</h4>
			@else --}}
			<h4>Reason to reject order due date extension request</h4>
			@endif

			<hr class="line-separator">
			{{ Form::open(['route' => ['accept_extend_order_date',$Order->id,$Order->order_extend_requests[0]->id,$isAccept], 'method' => 'POST','class'=>'','id'=>'accept_extend_order']) }}
			
		
			<!-- INPUT Message -->
			<div class="input-container form-group">
				@if($isAccept == 2)
				{{-- <label class="rl-label required">Enter Order Date Extend Accept Note</label> --}}
				{{-- @else --}}
				<label class="rl-label required">Notes</label>
				
				<textarea id="buyer_note" name="buyer_note" placeholder="" class="form-control"  maxlength="2500"></textarea>
				<div class="text-danger note-error" style="text-align: left;" ></div>
				<div style="float: right;"><span id="chars">0</span> / 2500 CHARS MAX</div>
				@endif
			</div>
			<!-- /INPUT Message -->

			<div class="clearfix"></div>
			<br>
			@if($isAccept == 1)
			<button type="submit" class="button big primary btn-right" style="width: 100px;" id="extrabutton">Accept</button>
			@else
			<button type="submit" class="button big primary btn-right" style="width: 100px;" id="extrabutton">Reject</button>
			@endif
			{{ Form::close() }}
		</div>
		<!-- /FORM BOX ITEM -->
		
	</div>
	<!-- /FORM BOX -->
</div>
</div>
</div>
</section>
@endsection

@section('scripts')
<script>
	$(function () {
		var maxLength = 2500;
		$('textarea').keyup(function() {
			var length = $(this).val().length;
		//var length = maxLength-length;
		$('#chars').text(length);
	});
	}); 

	$(document).ready(function() {
    $('#accept_extend_order').bootstrapValidator({
            fields: {
                'buyer_note': {
                    validators: {
                        notEmpty: {
                            message: 'Please add note.'
                        }
                    }
                },
            }
        }).on('error.validator.bv', function (e, data) {

});
});
</script>
@endsection

@section('css')
<link rel="stylesheet" href="{{front_asset('css/bootstrap.min.css')}}">
@endsection