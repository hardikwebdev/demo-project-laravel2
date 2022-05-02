@extends('layouts.frontend.main')
@section('pageTitle','404')
@section('content')
@php
	$treadingServices = [] ;
	if(Auth::check()){
		$treadingServices = app('App\Http\Controllers\HomeController')->get_recent_filtered_services_new();
	}
	if(count($treadingServices)==0){
		$treadingServices = app('App\Http\Controllers\HomeController')->get_treading_services();
	}
@endphp
<section class="container">
		<div class="row">    
			<div class="col-12 text-center">
				@if(Auth::check() && Auth::user()->web_dark_mode == 1)
					<img class="w-100" src="{{url('public/frontend/images/404-dark.svg')}}">
				@else
					<img class="w-100" src="{{url('public/frontend/images/404.svg')}}">
				@endif
				<div class="overlayout-section"></div>
			</div>
			<div class="col-12 text-center font-lato pl-1 mt-3 text-color-6">
				<div class="font-30 font-weight-bold pr-md-5">
					Let's fix it!
				</div>
				<div class="font-30 font-weight-bold pr-md-5">
					Try other services instead or create a job post.
				</div>
				<div class="font-14 pr-md-5 mt-2">
					We are very sorry for inconvenience. It looks like you're trying to access a service that either has been deleted or never even existed.
				</div>
				<div class="font-14 pr-md-5 mt-2">
					If you're looking for something custom you can open a job post.
				</div>
				<div class="my-3 pr-md-5">
					@if(Auth::check())
						<a class="btn bg-primary-blue text-white font-13 py-2 px-4 mt-3 mt-md-0 border-radius-6px add-project" href="{{route('jobs.create')}}">Create a Job Post</a>
					@else
						<a class="btn bg-primary-blue text-white font-13 py-2 px-4 mt-3 mt-md-0 border-radius-6px add-project" href="{{url('login')}}?jobAdd=1">Create a Job Post</a>						
					@endif
				</div>
			</div>    
		</div>    
</section>
 <!--------------------------------------New & Trending Services------------------------------------------>
 @if(count($treadingServices))
 <section class="w-100 bg-dark-white pb-5">
    <div class="container">
        <div class="row">
		    <div class="col-12"> 
				<h3 class="heading py-4 text-center">or try one of these services</h3>
                <div class="owl-carousel slick_service_box popular-grid-three slick-slider new_trending_slider">
					@foreach($treadingServices as $service)
					@include('frontend.service.single-item')
					@endforeach
                </div>  
            </div>
        </div>
    </div>
 </section>
@endif

@endsection