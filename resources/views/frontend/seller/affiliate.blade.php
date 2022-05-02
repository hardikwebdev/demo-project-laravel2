@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Affiliate Setting')
@section('content')

<!-- @include('frontend.seller.header') -->

<div class="content right">
	@include('layouts.frontend.messages')
	
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
									<div class="seller p-4 border-bottom">
										<div class="row m-0">
											<div class="col-12">
												Affiliate Setting
											</div>
										</div>
									</div>
									<form>
										@if(Auth::check() && Auth::user()->is_premium_seller() == true)
											<div class="row px-4 mt-3 m-0">
												<div class="col-12">
													<div class="form-group cusswitch">
														<label for="notification" class="register-text-dark-black font-14 mb-1">Show / Hide Affiliation link on profile page</label>
														<label class="cus-switch togglenotification">
														@if(Auth::user()->is_affiliate_profile == 1)
															@php
																$check=true;
															@endphp
														@else
															@php
																$check=false;
															@endphp
														@endif
														{{ Form::checkbox('affiliation_profile', 1, $check,["class"=>"toggle-input affiliation_profile"]) }}
															<span class="checkslider round"></span>
														</label>
													</div>
												</div>
												<div class="col-lg-12 mb-3">
													<div class="form-group cusswitch">
														<label for="notification" class="register-text-dark-black font-14 mb-1">Enable / Disable Affiliation on services (Setting will override service specific settings and applied to all services)
														</label>
														<label class="cus-switch togglenotification">
														@if(Auth::user()->is_affiliate_service == 1)
															@php
																$checkService=true;
															@endphp
														@else
															@php
																$checkService=false;
															@endphp
														@endif
															{{ Form::checkbox('affiliation_service', 1, $checkService,["class"=>"toggle-input affiliation_service"]) }}
															<span class="checkslider round"></span>
														</label>
													</div>
												</div>
											</div>
										@endif

										<div class="row px-4 m-0 mt-3 pb-4">
											<div class="col-12">
												<label class="register-text-dark-black font-16 fw-600">Your personalized link</label>
												<p class="register-text-dark-black font-16">Share you personalized link to your friends</p>
											</div>
											<div class="col-lg-12 mt-3">
												<label for="fullname" class="register-text-dark-black font-14 mb-1">Your affiliate link</label>
												<div class="form-group affiliate-form d-md-flex">
													<input type="text" class="form-control bg-white font-14 py-2 h-auto account-w-100 w-75" readonly="" value="{{url('/')}}/promotedemo/{{Auth::user()->affiliate_id}}" aria-describedby="basic-addon1">
													<button type="button"  data-clipboard-text="{{url('/')}}/promotedemo/{{Auth::user()->affiliate_id}}"  class="btn btn-primary register-bg-dark-primary copy_btn font-14 py-2 account-w-100 w-25">Share & Copy link</button> 
												</div>
											</div>
										</div>
										
									</form>
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

		$(document).on('change','.affiliation_service',function(){
			if($(this).prop('checked') == true)
			{
				var status = 1;
			}
			else
			{
				var status = 0;
			}

			$.ajax({
				type : 'get',
				data :{ 'status':status},
				url :"{{route('afflink_service_change_status')}}",
				success:function(){
					 window.location.reload();
				}
			})
		});

		$(document).on('change','.affiliation_profile',function(){
			if($(this).prop('checked') == true)
			{
				var statusProfile = 1;
			}
			else
			{
				var statusProfile = 0;
			}

			$.ajax({
				type : 'get',
				data :{ 'status':statusProfile},
				url :"{{route('afflink_profile_change_status')}}",
				success:function(){
					 window.location.reload();
				}
			})
		});


		$(document).ready(function () {
			if($("#userprofile").length){
				$('html,body').animate({
					scrollTop: $("#userprofile").offset().top-150},
					'slow');
			}

		});
	</script>    
	@endsection