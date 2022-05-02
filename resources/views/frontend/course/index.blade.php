@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Courses')
@section('content')

{{-- masthead  --}}
<header class="masthead text-white"> 
	<div class="overlay"></div>
    <div class="bg-dark w-100">
	<div class="container py-4">
		<div class="row py-2">
            <div class="col-12 col-md-8">
                <p class="text-center text-md-left mb-0 text-white font-24 font-weight-bold">Courses</p></a>
            </div>
            <div class="col-12 col-md-4 text-center text-md-right">
                @if(Auth::user()->username != 'scottfarrar' && App\User::is_soft_ban() == 0)
					<a href="{{route('course.overview')}}" class="btn bg-primary-blue text-white font-13 py-2 px-3 mt-3 mt-md-0 border-radius-6px" data-create="{{Auth::user()->can_create_service}}" data-profile="{{ Auth::user()->parent_id == 0 && (!Auth::user()->description || (!Auth::user()->profile_photo || !Auth::user()->photo_s3_key)) ? 1 : 0}}">
						<img src="{{url('public/frontend/images/plus-circle.png')}}" class="pr-2 align-bottom" alt=""> Create New Course
					</a>
				@endif
            </div>

			

        </div>
	</div>
    </div>
</header>

<div class="container pb-5 font-lato">
	@include('layouts.frontend.messages')
	{{ Form::open(['route' => ['mycourses'], 'method' => 'GET', 'id'=>'status_form_Search', 'name' => 'status_search']) }}
    <div class="row mt-4">
        <div class="col-12 col-md-auto d-flex align-items-center">
            <h1 class="font-18 text-color-2 mb-0"><img src="{{front_asset('images/filter.png')}}" class="img-fluid mr-2 dark-to-white-img" alt=""> Filter by</h1>
        </div>
        <div class="col-12 col-md-auto pl-md-0 col-lg-3 mt-3 mt-md-0">
			{{ Form::text('search',isset($_GET['search'])?$_GET['search']:'',['class'=>'form-control font-14 text-color-4 summary', 'id'=>'search', 'placeholder'=>'Search', 'autocomplete'=>'off']) }}
        </div>
        <div class="col-12 col-md-auto pl-md-0 mt-3 mt-md-0">
			{{Form::select('status', [''=>'Select Status','active'=>'Active','draft'=>'Draft','denied'=>'Denied','permanently_denied'=>'Permanently Denied'], isset($_GET['status'])?$_GET['status']:'',['class'=>'form-control select_pr', 'id'=>'status_id'])}}
        </div>
        <div class="col-12 col-md-auto pl-md-0 d-flex align-items-center mt-xl-0">
			<button class="btn btn-sm bg-primary-blue text-white">Search</button>
        </div>
        <div class="col-12 col-md-auto pl-md-0 d-flex align-items-center mt-xl-0">
			<a href="{{route('mycourses')}}" class="font-14 font-weight-bold text-color-1">Clear Filters</a>
        </div>
		@if( Auth::user()->is_course_training_account() == false && Auth::user()->is_premium_seller($parent_uid) == true && App\User::is_soft_ban() == 0)
		<div class="col-12 col-md-auto pl-md-0 d-flex align-items-center mt-xl-0 ml-auto">
			<a href="{{route('course.offer_bundle_discount')}}" class="text-right pull-right"><button type="button" class="btn bg-primary-blue text-white font-13 py-2 px-3 mt-3 mt-md-0 border-radius-6px">Combo Discount</button></a>
		</div>
		@endif

    </div>
	{{ Form::close() }}
</div>

<!-- popular service -->
<section class="mb-5">
	<div class="container">
		@include('frontend.course.include.course_list')
	</div>        
</section>  
@endsection


@section('css')
<style>
.custom_select_width {
	width: 115px !important;
}
.trash_btn {
	background: linear-gradient(90deg, #ef3d4e , #ff8e98 ) !important;
}
</style>
@endsection

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script>
	function isSponseredFunction(element) {
		var id = element;
		var confirmPause ;
		$.ajax({
			url : "{{route('check_if_sponsered')}}",
			data : {'_token':"{{ csrf_token() }}",'id' :id},
			type : 'post',
			success : function(data){
				var messgeConfirm;
				if(data.status == 200){

					messgeConfirm =  'There is pending course promotion request scheduled on '+data.startdate+'. If Job is in Pause status on scheduled date will not be visible to other users. Do you still want to continue?';

				}else{
					messgeConfirm = 'Are you sure you want to pause this course?';
				}
				bootbox.confirm({
					message: messgeConfirm,
					buttons: {
						confirm: {
							label: 'Continue',
							className: 'btn-default'
						},
						cancel: {
							label: 'Cancel',
							className: 'btn-default'
						}
					},
					callback: function (result) {
						if(result == true){
							$.ajax({
								type : 'get',
								data :{'_token':"{{ csrf_token() }}",'id':id ,'status':'paused'},
								url :"{{route('change_status')}}",
								success:function(){
									window.location.reload();
								}
							})
						}else{
							$(".category_submit").prop('selectedIndex',0);
						}
					}
				});
			}
		});
	}

	$(document).on('click', '.create_new_service', function (e) {
		e.preventDefault();
		var href = $(this).attr('href');
		var allow = $(this).data('create');
		var profile = $(this).data('profile');
		if(profile == 1) {
			toastr.error("Please update your profile to include a profile photo and description before creating or editing a service.", "Error");
			setTimeout(() => {
				window.location = href;
			}, 1500);
		} else if(allow == 0) {
			window.location = href;
		} else {
			toastr.error("Your account is unable to create new services at this time. Please contact support for more information.", "Error");
		}
	});

	$(document).on('click', '.open_service_edit', function (e) {
		e.preventDefault();
		var href = $(this).attr('href');
		var profile = $(this).data('profile');
		if(profile == 1 && href != "javascript:void(0)") {
			toastr.error("Please update your profile to include a profile photo and description before creating or editing a service.", "Error");
			setTimeout(() => {
				window.location = href;
			}, 1500);
		} else {
			window.location = href;
		}
	});
</script>
@endsection