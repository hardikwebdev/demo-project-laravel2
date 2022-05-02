@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Billing Information')
@section('content')

<!-- @include('frontend.seller.header') -->

<!-- popular service -->
<section class="popular-services popular-tab-icon user-profile-tab">
	<div class="container p-0">
		<div class="row m-0 justify-content-center">
			<div class="col-lg-3">
				@include('frontend.seller.myprofile_tabs')
			</div>

			<div class="col-lg-8">
				<div class="popular-tab-item p-0">
					<div class="profile-update tab-content" id="myTabContent">
						<div class="tab-pane fade show active" id="gigs" role="tabpanel" aria-labelledby="gigs">
							<div class="popular-grid m-0">
								@include('layouts.frontend.messages')
								<div class="seller p-4 border-bottom">
									<div class="row m-0">
										<div class="col-12">
											Billing Information
										</div>
									</div>
								</div>
								
								{{ Form::open(['route' => ['billing'], 'method' => 'POST', 'id' => 'frmUpdateBilling']) }}
								<div class="row px-4 mt-3 m-0">
									<div class="col-12">
										<div class="form-group">
											<label for="fullname" class="register-text-dark-black font-14 mb-1">Full Name</label>
											<input type="text" id="name" name="name" class="form-control" id="name" value="{{isset($billingInfo->name)?$billingInfo->name:''}}" placeholder="Enter your full name" maxlength="150">
										</div>
									</div>
									<div class="col-12">
										<div class="form-group">
											<label class="register-text-dark-black font-14 mb-1">VAT Number</label>
											<input type="text" class="form-control" id="vat_number" name="vat_number" value="{{isset($billingInfo->vat_number)?$billingInfo->vat_number:''}}" placeholder="VAT Number">
										</div>
									</div>

									<div class="col-12">
										<div class="form-group">
											<label class="register-text-dark-black font-14 mb-1">Address Line 1</label>
											<textarea class="form-control" id="address1" name="address1" placeholder="Address Line 1" maxlength="100">{{isset($billingInfo->address1)?$billingInfo->address1:''}}</textarea>
										</div>
									</div>

									<div class="col-12">
										<div class="form-group">
											<label class="register-text-dark-black font-14 mb-1">Address Line 2</label>
											<textarea class="form-control" id="address2" name="address2" placeholder="Address Line 2" maxlength="100">{{isset($billingInfo->address2)?$billingInfo->address2:''}}</textarea>
										</div>
									</div>

									<div class="col-12">
										<div class="form-group">
											<label class="register-text-dark-black font-14 mb-1">Country</label>
											{{Form::select('country_id',[''=>'Select Country']+$Country,isset($billingInfo->country_id)?$billingInfo->country_id:'',['id'=>'country_id',"class"=>"form-control"])}}
										</div>
									</div>

									<div class="col-12">
										<div class="form-group">
											<label class="register-text-dark-black font-14 mb-1">City</label>
											<input type="text" class="form-control" id="city" name="city" value="{{isset($billingInfo->city)?$billingInfo->city:''}}" placeholder="City" maxlength="42">
										</div>
									</div>

									<div class="col-12">
										<div class="form-group">
											<label class="register-text-dark-black font-14 mb-1">Zipcode</label>
											<input type="text" class="form-control" id="zipcode" name="zipcode" value="{{isset($billingInfo->zipcode)?$billingInfo->zipcode:''}}" placeholder="Zipcode" maxlength="20">
										</div>
									</div>
								</div>

								<div class="row px-4 py-3 mt-3 m-0 border-top">
									<div class="col-lg-12 create-new-service update-account text-right">
										<button type="submit" class="btn btn-primary font-14 px-3 py-2">Update</button> 
									</div>
								</div>
								
								{{Form::close()}}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>  

@endsection

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type="text/javascript">
	$(document).ready(function () {
		if($("#userprofile").length){
			$('html,body').animate({
				scrollTop: $("#userprofile").offset().top-150},
			'slow');
		}
	});
</script>    
@endsection