@extends('layouts.frontend.main')
@section('pageTitle','demo - Account Information')
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
											Account Information
										</div>
									</div>
								</div>

								{{ Form::open(['route' => ['accountsetting'], 'method' => 'POST', 'id' =>
								'frmUpdateAccount','files'=>true]) }}
								<div class="row px-4 mt-3 m-0">
									<div class="col-12 col-md-6 col-lg-4">
										<div class="form-group register">
											<label for="exampleInputEmail1" class="register-text-dark-black font-14 mb-1">Profile Photo</label>
											<div class="avatar-upload">
												<div class="avatar-edit">
													{{-- <input type='file' id="imageUpload"
														accept=".png, .jpg, .jpeg" /> --}}
													<label for="imageUpload">
														<i class="far fa-edit font-12 cursor-pointer" data-toggle="modal"
															data-target="#spam-message"></i>
													</label>
												</div>
												<div class="avatar-preview">
													<div id="profile_image">
														<img src="{{get_user_profile_image_url(\Auth::user())}}"
															alt="profile-image" class='img-fluid profile_image'>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="modal fade custommodel" id="spam-message" tabindex="-1" role="dialog"
										aria-labelledby="exampleModalLabel" aria-hidden="true">
										<div class="modal-dialog modal-dialog-centered" role="document">
											<div class="modal-content">
												<div class="modal-header">
													<div class="container">
														<div class="row display-block">
															<center>
																<div class="col-md-12">
																	<h5 class="bold-lable"
																		id="exampleModalLabel bold-lable">Please Select
																		Profile Image</h5>
																</div>
																<div class="col-md-12">
																	<div class="form-group text-center">
																		<label class="btn default-btn"> Choose Image
																			<input type="file" id="upload" size="60"
																				style='display: none;'></label>
																	</div>
																</div>
															</center>
														</div>
													</div>
													<button type="button" class="close" data-dismiss="modal"
														aria-label="Close">
														<span aria-hidden="true">×</span>
													</button>
												</div>
												<div class="modal-body text-right">
													<center>
														<div id="upload-demo" style="width:350px;"></div>
													</center>
													<label class="btn  default-btn upload-result"
														data-url="{{route('profileImage')}}">Save</label>
													<label class="btn  default-btn close_pop"
														data-url="{{route('profileImage')}}">Close</label>
													<input type='hidden' id='textshow'>
												</div>
											</div>
										</div>
									</div>

									<div class="col-12 col-md-6 col-lg-8">
										<div class="row">
											<div class="col-md-6">
												<div class="form-group cusswitch">
													<label for="notification" class="register-text-dark-black font-14 mb-1">Email Notifications</label>
													<label for="notification" class="register-text-dark-black font-10 mb-1">Disabling this option will stop you from receiving emails like order updates and direct messages.</label>
													<label class="cus-switch togglenotification">
														{{ Form::checkbox('notification', 1,
														$user->notification,["class"=>"toggle-input"]) }}
														<span class="checkslider round"></span>
													</label>
												</div>
												<div class="form-group cusswitch">
													<label for="Deactive Account" class="register-text-dark-black font-14 mb-1">Deactivate Account
													</label>
													<label for="notification" class="register-text-dark-black font-10 mb-1">Enabling this option will deactivate your account on the site.</label>
													<label class="cus-switch toggledeactive">
														@php
														$status = $user->status ? 0 : 1;
														@endphp
														{{ Form::checkbox('deactive_account', 1,
														$status,["class"=>"toggle-input","id"=>"deactive_account"]) }}
														<a data-toggle="modal" data-target="#deactiveAccountPopup"
															href="javascript:void(0)"><span
																class="checkslider round"></span></a>
													</label>
												</div>
												<div class="form-group cusswitch">
													<label for="web_notification" class="register-text-dark-black font-14 mb-1">Push Notifications</label>
													<label for="notification" class="register-text-dark-black font-10 mb-1">Enable or disable the receipt of push notifications from demo</label>
													<label class="cus-switch togglenotification">
														{{ Form::checkbox('web_notification', 1,
														$user->web_notification,["class"=>"toggle-input"]) }}
														<span class="checkslider round"></span>
													</label>
												</div>
											</div>

											<div class="col-md-6">
												<div class="form-group cusswitch">
													<label for="disable_animations" class="register-text-dark-black font-14 mb-1">Disable Animations</label>
													<label for="notification" class="register-text-dark-black font-10 mb-1">Enabling this will remove animations from the site.</label>
													<label class="cus-switch togglenotification">
														{{ Form::checkbox('disable_animations', 1,
														$user->disable_animations,["class"=>"toggle-input"]) }}
														<span class="checkslider round"></span>
													</label>
												</div>

												<div class="form-group cusswitch">
													<label for="Vacation Mode" class="register-text-dark-black font-14 mb-1">Vacation Mode</label>
													<label for="notification" class="register-text-dark-black font-10 mb-1">Enabling vacation mode will hide your services from getting new sales while you're away.</label>
													<label class="cus-switch">
														{{ Form::checkbox('vacation_mode', 1,
														$user->vacation_mode,["class"=>"toggle-input","id"=>"vacation_mode"]) }}
														<span class="checkslider round"></span>
													</label>
												</div>

											</div>

											<input type="hidden" value="{{$user->city}}" name="city" id="city">
											<input type="hidden" value="{{$user->state}}" name="state" id="state">
											<input type="hidden" id="country" value="{{$user->country_id}}" name="country">

											<div class="col-12">
												<div class="form-group">
													<label class="register-text-dark-black font-14 mb-1">Time Zone</label>
													<select class="form-control" id="timezone" name="timezone">
														<option value="">--Select Time Zone--</option>
														@foreach($timezone as $row)
														<option value="{{$row}}" @if($row==$user->timezone) selected @endif>{{$row}}</option>
														@endforeach
													</select>
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="row px-4 mt-3 pt-3 border-top m-0">

									{{-- <div class="col-lg-12">
										<div class="input-container form-group" id="deactivereason"
											style="display: @if($status==1){{'block'}}@else{{'none'}}@endif;">
											<label for="deactive_reason" class="rl-label">Deactive Reason</label>
											<input type="text" id="deactive_reason" name="deactive_reason"
												placeholder="Describe Reason" class="form-control"
												value="{{$user->deactive_reason}}">
										</div>
									</div> --}}

									<div class="col-12">
										<div class="form-group">
											<label for="fullname" class="register-text-dark-black font-14 mb-1">Full
												Name</label>
											<input type="text" name="Name" class="form-control" value="{{$user->Name}}"
												id="name" placeholder="Enter your full name here...">

										</div>
									</div>
									<div class="col-12">
										<div class="form-group">
											<label class="register-text-dark-black font-14 mb-1">Email</label>
											<input type="text" name="email" class="form-control"
												value="{{$user->email}}" placeholder="Enter your email address here...">
										</div>
									</div>
									@if(Auth::user()->is_sub_user() == false)
									<div class="col-12 form-group">
										<div class="">
											<label class="register-text-dark-black font-14 mb-1">Username</label>
											<input type="text" class="form-control" id="username"
												value="{{$user->username}}" name="username"
												placeholder="Enter username">
											{{-- <input type="text" class="form-control" id="username"
												value="{{$user->username}}" name="username"
												{{\Auth::user()->is_username_update == "1" ? "Disabled" : "" }}
											placeholder="Enter username"> --}}
											{{-- <label class="text-danger">Note: username update once</label> --}}
										</div>
									</div>
									@endif

									<div class="col-12">
										<div class="form-group">
											<label class="register-text-dark-black font-14 mb-1">Paypal Email
												Address</label>
											<input type="email" class="form-control" name="paypal_email"
												id="paypal_email" value="{{$user->paypal_email}}"
												placeholder="Enter your paypal email address here...">
										</div>
									</div>

									@if(Auth::user()->is_sub_user() == false)
									<div class="col-12">
										<div class="form-group" id="locationField">
											<label class="register-text-dark-black font-14 mb-1">Address</label>
											<input class="form-control" name="address" value="{{$user->address}}"
												id="autocomplete" placeholder="Enter your address" onFocus="geolocate()"
												type="text" />
										</div>
									</div>
									{{-- <div class="col-lg-6">
										<div class="form-group">
											<label>City</label>
											<input type="text" value="{{$user->city}}" class="form-control" name="city"
												id="city" placeholder="City" readonly>
										</div>
									</div>
									<div class="col-lg-6">
										<div class="form-group">
											<label>State</label>
											<input type="text" value="{{$user->state}}" class="form-control"
												name="state" id="state" placeholder="State" readonly>
										</div>
									</div>
									<div class="col-lg-6">
										<div class="form-group">
											<label>Country</label>
											<input type="text" class="form-control" id="country_name"
												placeholder="Country" value="{{$country->toArray()[$user->country_id]}}"
												readonly>

											<input type="hidden" id="country" value="{{$user->country_id}}"
												name="country">
										</div>
									</div> --}}
								</div>
								<div class="row px-4 mt-2 pt-3 border-top m-0">
									<div class="col-12">
										<div class="form-group">
											<label class="register-text-dark-black font-16 fw-600">Social Links</label>
											<p class="register-text-dark-black font-14">Add your social network links,
												to display on your profile</p>
										</div>
										<div class="form-group">
											<div class="input-group mb-3">
												<div class="input-group-prepend">
													<span class="input-group-text" id="facebook">
														<img
															src="{{url('public/frontend/images/social/facebook-icon.png')}}">&nbsp;
														facebook.com/
													</span>
												</div>
												<input type="text" class="form-control h-auto" maxlength="100"
													name="facebook_link" id="facebook_link"
													@isset($user->social_links->facebook_link)
												value="{{$user->social_links->facebook_link}}" @endisset
												aria-describedby="facebook">
											</div>
										</div>
										<div class="form-group">
											<div class="input-group mb-3">
												<div class="input-group-prepend">
													<span class="input-group-text" id="twitter">
														<img
															src="{{url('public/frontend/images/social/twitter-icon.png')}}">&nbsp;
														twitter.com/&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
													</span>
												</div>
												<input type="text" class="form-control h-auto" maxlength="100"
													name="twitter_link" id="twitter_link"
													@isset($user->social_links->twitter_link)
												value="{{$user->social_links->twitter_link}}" @endisset
												aria-describedby="twitter">
											</div>
										</div>
										<div class="form-group">
											<div class="input-group mb-3">
												<div class="input-group-prepend">
													<span class="input-group-text pr-0" id="instagram">
														<img
															src="{{url('public/frontend/images/social/insta.png')}}">&nbsp;
															instagram.com/&nbsp;&nbsp;
													</span>
												</div>
												<input type="text" class="form-control h-auto" maxlength="100"
													name="instagram_link" id="instagram_link"
													@isset($user->social_links->instagram_link)
												value="{{$user->social_links->instagram_link}}" @endisset
												aria-describedby="instagram">
											</div>
										</div>
										<div class="form-group">
											<div class="input-group mb-3">
												<div class="input-group-prepend">
													<span class="input-group-text" id="youtube">
														<img
															src="{{url('public/frontend/images/social/youtube-icon.png')}}">&nbsp;
														youtube.com/&nbsp;&nbsp;
													</span>
												</div>
												<input type="text" class="form-control h-auto" maxlength="100"
													name="youtube_link" id="youtube_link"
													@isset($user->social_links->youtube_link)
												value="{{$user->social_links->youtube_link}}" @endisset
												aria-describedby="youtube">
											</div>
										</div>
										<div class="form-group">
											<div class="input-group mb-3">
												<div class="input-group-prepend">
													<span class="input-group-text" id="linkedin">
														<img
															src="{{url('public/frontend/images/social/linkedin-icon.png')}}">&nbsp;
														linkedin.com/&nbsp;&nbsp;&nbsp;
													</span>
												</div>
												<input type="text" class="form-control h-auto" maxlength="100"
													name="linkedin_link" id="linkedin_link"
													@isset($user->social_links->linkedin_link)
												value="{{$user->social_links->linkedin_link}}" @endisset
												aria-describedby="linkedin">
											</div>
										</div>
									</div>

									<div class="col-12">
										<div class="form-group">
											<div class="form-group">
												<label class="register-text-dark-black font-16 fw-600">Account Type</label>
												<p class="register-text-dark-black font-14">Select what type of account you're interested in</p>
											</div>
											<!-- <div>
												<div class="form-check form-check-inline">
													<input class="form-check-input" type="radio" name="interested_in" id="buying_service" value="1" @if($user->interested_in == 1) checked @endif>
													<label class="form-check-label" for="buying_service">Buying services to grow my business</label>
												</div>
											</div>
											<div>
												<div class="form-check form-check-inline">
													<input class="form-check-input" type="radio" name="interested_in" id="selling_service" value="2" @if($user->interested_in == 2) checked @endif>
													<label class="form-check-label" for="selling_service">Selling my services</label>
												</div>
											</div>
											<div>
												<div class="form-check form-check-inline">
													<input class="form-check-input" type="radio" name="interested_in" id="both_service" value="3"  @if($user->interested_in == 3) checked @endif>
													<label class="form-check-label" for="both_service">Both buying and selling</label>
												</div>
											</div> -->
											<div class="dropdown show cus-buyer-dropdown">
												<a class="btn register-bg-light-gray dropdown-toggle pr-4 position-relative" href="#" role="button"
													id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true"
													aria-expanded="false">
													@if (isset($user->interested_in))
														<div class="px-3 d-flex align-items-center cursor-pointer">
															@if ($user->interested_in==1)
																<div>
																	<img src="{{url('public/frontend/images/buyer.png')}}">			
																</div>
																<div class="ml-3">
																	<label class="register-text-dark-black font-14 fw-600 mb-0">Buying</label>
																</div>
															@elseif ($user->interested_in==2)
																<div>
																	<img src="{{url('public/frontend/images/seller.png')}}">			
																</div>
																<div class="ml-3">
																	<label class="register-text-dark-black font-14 fw-600 mb-0">Selling</label>
																</div>
															@elseif ($user->interested_in==3)
																<div>
																	<img src="{{url('public/frontend/images/buyer-seller.png')}}">			
																</div>
																<div class="ml-3">
																	<label class="register-text-dark-black font-14 fw-600 mb-0">Buying & Selling</label>
																</div>
															@else
																Select
															@endif
															
														</div>
													@else
														Select
													@endif
												</a>
												<input type="hidden" name="interested_in" id="interested_in_input" value= @if(isset($user->interested_in)){{ $user->interested_in}} @endif>
												<div class="dropdown-menu border-radius-8px z-10" aria-labelledby="dropdownMenuLink">
													<a class="dropdown-item py-3 interested_in_select" data-id="1">
														<div class="px-3 d-flex align-items-center cursor-pointer">
															<div>
																<img src="{{url('public/frontend/images/buyer.png')}}">			
															</div>
															<div class="ml-3">
																<label class="register-text-dark-black font-14 fw-600 mb-0">Buying</label>
																<p class="register-text-light-gray font-12 mb-0 text-wrap">Buying services to grow my business</p>
															</div>
														</div>
													</a>
													<a class="dropdown-item py-3 interested_in_select" data-id="2">
														<div class="form-check-label px-3 d-flex align-items-center cursor-pointer">
															<div>
																<img src="{{url('public/frontend/images/seller.png')}}">			
															</div>
															<div class="ml-3">
																<label class="register-text-dark-black font-14 fw-600 mb-0">Selling</label>
																<p class="register-text-light-gray font-12 mb-0 text-wrap">Selling my services to make money</p>
															</div>
														</div>
													</a>
													<a class="dropdown-item py-3 interested_in_select" data-id="3">
														<div class="form-check-label px-3 d-flex align-items-center cursor-pointer">
															<div>
																<img src="{{url('public/frontend/images/buyer-seller.png')}}">			
															</div>
															<div class="ml-3">
																<label class="register-text-dark-black font-14 fw-600 mb-0">Buying & Selling</label>
																<p class="register-text-light-gray font-12 mb-0 text-wrap">The best of both worlds</p>
															</div>
														</div>
													</a>
												</div>
											</div>
										</div>
									</div>

									<input type="hidden" name="latitude" id="latitude" value="{{$user->latitude}}">
									<input type="hidden" name="longitude" id="longitude" value="{{$user->longitude}}">
									@endif
								</div>
								<div class="row px-4 mt-2 pt-3 pb-4 border-top m-0">
									<div class="col-lg-12 create-new-service update-account text-right">
										<button type="submit" class="btn btn-primary font-14 px-3 py-2">Update</button>
									</div>
								</div>

								<!-- Deactivate Account modal-->
								<div id="deactiveAccountPopup" class="modal fade custompopup" role="dialog">
									<div class="modal-dialog">
										<!-- Modal content-->
										<div class="modal-content">
											<div class="modal-header">
												<button type="button" class="close"
													data-dismiss="modal">&times;</button>
												<h4 class="modal-title">Deactivate Account?</h4>
											</div>
											<div class="modal-body">
												{{-- {{ Form::open(['route' => ['accountsetting'], 'method' =>
												'POST','class'=>'','id'=>'deactiveAccountPopup']) }} --}}
												<div class="row">
													<div class="col-lg-12">
														<div class="form-group">
															<label>Reason for deactivation</label>
															<textarea class="form-control" rows="6" id="cancel_note"
																name="deactive_reason"
																placeholder="Reason for deactivation..."
																maxlength="2500"></textarea>

															<div class="text-danger text-left note-error"></div>
														</div>
													</div>
													<div class="col-lg-12 create-new-service update-account text-right">
														<button type="submit"
															class="btn btn-primary deactive_button">Deactivate</button>
													</div>
												</div>
												{{-- {{ Form::close() }} --}}
											</div>
										</div>
									</div>
								</div>
								{{ Form::close() }}
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal -->
	<div class="modal fade d-none" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
		@php 
			if(Auth::user()->parent_id == 0) {
				$myprofile = Auth::user()->username;
			} else {
				$myprofile = App\User::where('id',Auth::user()->parent_id)->select('username')->first();
				$myprofile = $myprofile['username'];
			}
		@endphp
		<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
			<div class="modal-content">
				<div class="modal-body">
					<div class="row align-items-center py-4">
						<div class="col-md-8 order-2 order-md-1 mt-3">
							<label class="register-text-dark-black font-16 fw-600">Congrats!</label>
							<p class="register-text-dark-black font-16 mt-3">You won your first badge - Profile Completed</p>
							<div class="pt-3">
								<a href="{{route('viewuserservices',$myprofile)}}" class="border rounded px-4 py-2 course_text-black font-14">Check your profile page</a>
							</div>
						</div>
						<div class="col-md-4 text-center order-1 order-md-2">
	                        <img src="{{url('public/frontend/images/congrets.png')}}" class="img-fluid" >
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
</section>

@endsection

@section('css')
<link rel="stylesheet" type='text/css' href="{{front_asset('cropper/cropper.css')}}">
<style type="text/css">
	#upload-demo {
		width: 350px;
	}

	@media only screen and (max-width: 450px) {
		#upload-demo {
			width: unset !important;
		}
	}
</style>
@endsection

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script src="{{front_asset('cropper/cropper.js')}}"></script>
<script type="text/javascript">
	$(".deactive_button").click(function () {
		$("#deactive_account").attr("checked", "checked");
	});

	$('#deactiveAccountPopup').bootstrapValidator({
		fields: {
			deactive_reason: {
				validators: {
					notEmpty: {
						message: 'Deactivation reason is required.'
					}
				}
			}
		}
	}).on('error.validator.bv', function (e, data) {

	});

	$(document).ready(function () {
		// if (<?php echo Session::has('firstAttempt'); ?>) {
		// 	if (<?php echo Session::has('errorSuccess'); ?> && <?php echo Session::get('firstAttempt'); ?>==1) {
		// 		$('#exampleModalCenter').modal('show');
		// 	}
		// }
		if ($("#userprofile").length) {
			$('html,body').animate({
				scrollTop: $("#userprofile").offset().top - 150
			},
				'slow');
		}

		function readURL(input) {
			if (input.files && input.files[0]) {
				var reader = new FileReader();

				reader.onload = function (e) {
					$('#profile_image').attr('src', e.target.result);
				}

				reader.readAsDataURL(input.files[0]);
			}
		}
		$("#imgupload").change(function () {
			readURL(this);
		});
		$(".interested_in_select").click(function () {
			$("#interested_in_input").val($(this).data('id'));
		});
	});
</script>

<script type="text/javascript">


	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		}
	});


	$uploadCrop = $('#upload-demo').croppie({
		enableExif: true,
		viewport: {
			width: 200,
			height: 200,
			type: 'circle'
		},
		boundary: {
			width: 200,
			height: 200,
		}
	});
	$('#upload').on('change', function () {
		var reader = new FileReader();
		reader.onload = function (e) {
			$uploadCrop.croppie('bind', {
				url: e.target.result
			}).then(function () { });
		}
		reader.readAsDataURL(this.files[0]);
	});

	$(document).ready(function () {

		$('input[type="file"]').change(function (e) {
			var fileName = e.target.files[0].name;
			$('#textshow').val(fileName);
		});
	});
	$('.close_pop').on('click', function () {
		$('#spam-message').modal('hide');
	});

	$('.upload-result').on('click', function (ev) {
		var textshow = $('#textshow').val();
		if (textshow == '') {
			alert_error('please select an image from choose image option first');
		} else {
			$('#spam-message').modal('hide');
			var url = $(this).data('url');
			$uploadCrop.croppie('result', {
				type: 'canvas',
				size: 'viewport'
			}).then(function (resp) {

				$.ajax({
					url: url,
					type: "POST",
					data: { "image": resp, '_token': _token },
					success: function (data) {
						$('.profile_image').hide();
						$('.profile_image_avt').hide();
						var html = '<img src="' + resp + '" />';
						var html = '<img src="' + resp + '" /><div class="seller-online"></div>';
						$("#profile_image").html(html);
						$(".user-avatar").html(html);
						toastr.success(data.message, "Success");
					}
				});
			});
		}

	});
</script>

<script>
	// This sample uses the Autocomplete widget to help the user select a
	// place, then it retrieves the address components associated with that
	// place, and then it populates the form fields with those details.
	// This sample requires the Places library. Include the libraries=places
	// parameter when you first load the API. For example:
	// <script
	// src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places">

	var country_list = {!! json_encode($country -> toArray())!!};

	var placeSearch, autocomplete;

	var componentForm = {
		city: 'long_name',
		state: 'long_name',
		country: 'long_name'
	};

	function initAutocomplete() {
		// Create the autocomplete object, restricting the search predictions to
		// geographical location types.
		autocomplete = new google.maps.places.Autocomplete(
			document.getElementById('autocomplete'), { types: ['geocode'] });

		// Avoid paying for data that you don't need by restricting the set of
		// place fields that are returned to just the address components.
		autocomplete.setFields(['address_component', 'geometry']);

		// When the user selects an address from the drop-down, populate the
		// address fields in the form.
		autocomplete.addListener('place_changed', fillInAddress);
	}

	function fillInAddress() {
		// Get the place details from the autocomplete object.
		var place = autocomplete.getPlace();
		for (var component in componentForm) {
			document.getElementById(component).value = '';
			document.getElementById(component).disabled = false;
		}

		// Get each component of the address from the place details,
		// and then fill-in the corresponding field on the form.
		for (var i = 0; i < place.address_components.length; i++) {
			var addressType = place.address_components[i].types[0];
			if (addressType == 'locality') {
				addressType = 'city';
			} else if (addressType == 'administrative_area_level_1') {
				addressType = 'state';
			}
			if (componentForm[addressType]) {
				var val = place.address_components[i][componentForm[addressType]];
				if (addressType == 'country') {
					for (i in country_list) {
						if (country_list[i] == val) {
							val = i;
							$('#country_name').val(country_list[i]);
						}
					}
				}
				document.getElementById(addressType).value = val;
			}
		}
		getLatLong();
	}

	// Bias the autocomplete object to the user's geographical location,
	// as supplied by the browser's 'navigator.geolocation' object.
	function geolocate() {
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(function (position) {
				var geolocation = {
					lat: position.coords.latitude,
					lng: position.coords.longitude
				};
				var circle = new google.maps.Circle(
					{ center: geolocation, radius: position.coords.accuracy });
				autocomplete.setBounds(circle.getBounds());
			});
		}
	}

	function getTimezone(location) {
		timestamp = Math.round(new Date().getTime() / 1000);
		var apicall = "https://maps.googleapis.com/maps/api/timezone/json?location=" + location + "&timestamp=" + timestamp + "&key={!! env('GOOGLE_API_KEY') !!}";

		var xhr = new XMLHttpRequest();
		xhr.open('GET', apicall);
		xhr.onload = function () {
			if (xhr.status === 200) {
				var output = JSON.parse(xhr.responseText);
				$('#timezone').val(output.timeZoneId);
				$('#frmUpdateAccount').bootstrapValidator('revalidateField', 'timezone');
			}
			else {
				console.log('Request failed.  Returned status of ' + xhr.status);
			}
		}
		xhr.send()
	}

	function getLatLong(address) {
		var add_str = $('#autocomplete').val();
		var address = add_str.replace(/\s/g, '+');
		if (address.length > 0) {
			var apicall = "https://maps.googleapis.com/maps/api/geocode/json?address=" + address + "&key={!! env('GOOGLE_API_KEY') !!}";
			var xhr = new XMLHttpRequest();
			xhr.open('GET', apicall);
			xhr.onload = function () {
				if (xhr.status === 200) {
					var output = JSON.parse(xhr.responseText);
					if (output.results[0].geometry) {
						var loc = output.results[0].geometry.location;
						$('#latitude').val(loc.lat);
						$('#longitude').val(loc.lng);
						var let_lng = loc.lat + ',' + loc.lng;
						getTimezone(let_lng);
					} else {
						console.log('enable to get geo code.');
					}
				}
				else {
					console.log('Request failed.  Returned status of ' + xhr.status);
				}
			}
			xhr.send();
		} else {
			console.log("enter address");
		}
	}
</script>

<script
	src="https://maps.googleapis.com/maps/api/js?key={!! env('GOOGLE_API_KEY') !!}&libraries=places&callback=initAutocomplete"
	async defer></script>

@endsection