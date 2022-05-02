 @foreach($jobs as $job)
        @php
            $count=0;
        @endphp
        @if(count($job->job_accept) > 0)
            @php
                $countCheck=$job->job_accept->where('status','Accepted')->count();
                if($countCheck > 0)
                {
                    $count=$count+1;
                }
            @endphp
        
        @endif

            @if($count == 0)
             <div class="m-job-listing mb-3">
                        <div class="row">
                            <div class="col-12 col-md-2 m-text-center align-self-center">
                                <img src="{{url('public/frontend/images/portfolio.png')}}" class="img-round img-fluid ">
                            </div>
                            <div class="col-12 col-md-10">
                                <a href="{{route('show.job_detail',$job->seo_url)}}" class="jobTitle"><h5>{{$job->title}}</h5></a>
                                <div class="row">
                                    <div class="col-sm-8 ">
                                        <p>{!! $job->descriptions !!}</p> 
                                    </div>
                                    <div class="col-sm-4 text-right">
                                        <strong class="mf-16 d-inline">${{$job->job_min_price}} - {{$job->job_max_price}} USD</strong>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-4 align-self-center">
                                        <!--redirection to profile page for buyer-->
                                       <a href="{{route('viewuserservices',$job->user->username)}}" class="jobTitle"><p class="mfs-16"><img src="{{url('public/frontend/images/user-1.png')}}" alt="" class="img-fluid text-primary  m-width-15 mr-1"> <strong>{{$job->user->Name}}</strong>
                                            @if($job->user->is_premium_seller($job->uid) == true)

                                    <img src="{{ url('public/frontend/images/Badge.png') }}" alt="profile-image" class="img-fluid text-primary ml-1 m-width-15" height="25"></img>

                                                    @endif
                                            
                                        </p></a>
                                    </div>
                                    <div class="col-sm-8 ">
                                        <div class="m-verti-midd">
                                            <i class="fas fa-tag mr-2"></i> {{$job->tags}}
                                        </div>
                                        <div class="row">
                                            <div class="col">
                                                <div class="m-verti-midd mt-2">
                                                    @php
                                                        $now = \Carbon\Carbon::now();
                                                        $hours= $now->diffInHours($job->created_at);
                                                        if($hours > 24)
                                                        {
                                                            
                                                            $days=$hours/24;
                                                          
                                                            $time=intval(round($days)).' Days Ago';
                                                        }
                                                        elseif($hours < 1)
                                                        {
                                                            $min = $now->diffInMinutes($job->created_at);
                                                            $time =$min.' Minutes Ago';
                                                        }
                                                        else
                                                        {
                                                            $time =$hours.' Hours Ago';
                                                        }

                                                    @endphp

                                                    <i class="far fa-clock mr-2"></i> {{$time}}
                                                </div>
                                            </div>
                                            <div class="col">
                                                <div class="m-verti-midd mt-2">
                                                    @php
                                                        $count=$job->job_accept->where('status','pending')->count();
                                                    @endphp
                                                    <i class="fas fa-gavel mr-2"></i> {{$count}} Bids
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
        </div>
        @endif
    @endforeach