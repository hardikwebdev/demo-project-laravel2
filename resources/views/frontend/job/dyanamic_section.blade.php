<!--Loads all jobs from dyanamic section -->
@if(count($jobs) > 0)
@foreach($jobs as $job)

<div class="m-job-listing mb-3">
	<div class="row">
		<div class="col-12 col-md-2 m-text-center align-self-center">
			<img src="{{url('public/frontend/images/portfolio.png')}}" class="img-round img-fluid ">
		</div>
		<div class="col-12 col-md-10">
			@if(Auth::check())
				<a href="{{route('show.job_detail',$job->seo_url)}}" class="jobTitle"><h5>
					{!! display_subtitle($job->title, null, 50) !!}</h5>
				</a>
			@else
				<a href="{{url('/login')}}?job_url={{$job->seo_url}}" class="jobTitle"><h5>{!! display_subtitle($job->title, null, 50) !!}</h5></a>
			@endif
			<div class="row">
				<div class="col-sm-8 ">
					@php
					$description=strip_tags($job->descriptions);
					@endphp
					<p>{!! display_subtitle($description,null,70) !!} &nbsp; 
						@if(strlen($job->descriptions) > 70)
						<a href="{{route('show.job_detail',$job->seo_url)}}">see more</a>
						@endif
					</p> 
				</div>
				<div class="col-sm-4 text-right">
					<strong class="mf-16 d-inline">${{$job->job_min_price}} - {{$job->job_max_price}} USD</strong>
				</div>
			</div>

			<div class="row">
				<div class="col-sm-4 align-self-center">
					<!--redirection to profile page for buyer-->
					<a href="{{route('viewuserservices',$job->user->username)}}" class="jobTitle"> <p class="mfs-16"><img src="{{url('public/frontend/images/user-1.png')}}" alt="" class="img-fluid text-primary  m-width-15 mr-1"> <strong>{{$job->user->Name}}</strong>
						@if($job->user->is_premium_seller($job->uid) == true)
						<img src="{{ url('public/frontend/images/Badge.png') }}" alt="profile-image" class="img-fluid text-primary ml-1 m-width-15" height="25"></img>
						@endif
					</p></a>
				</div>
				<div class="col-sm-8">
					<div class="row">
						<div class="col rm-custom-pading">
							<div class="m-verti-midd mt-2">
								<i class="far fa-clock mr-2"></i> {{get_job_days_ago($job->created_at)}}
							</div>
						</div>

						<div class="col rm-custom-pading">
							<div class="m-verti-midd mt-2">
								<i class="fas fa-stopwatch mr-2 text-danger"></i> {{display_expire_on($job->expire_on)}}
							</div>
						</div>

						<div class="col rm-custom-pading">
							<div class="m-verti-midd mt-2">

								<i class="fas fa-gavel mr-2"></i> {{($job->job_accept)?$job->job_accept->count():0}} Bids
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endforeach
<div class="total-count-show col-12 text-center cus-show-entry cus-grid-full" >
	<div>
		Showing {{ $jobs->firstItem() }} to {{ $jobs->lastItem() }} of total {{$jobs->total()}} jobs
	</div>
</div>
@endif