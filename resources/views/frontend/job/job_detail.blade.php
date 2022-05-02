@php 
use App\User; 
@endphp
@extends('layouts.frontend.main')
@section('pageTitle','demo - Job Detail')
@section('content')

<!-- Display Error Message -->
@include('layouts.frontend.messages')
<!-- header Title -->
<section class="sub-header product-detail-header">
	<div class="container">
		<div class="row align-items-center">
			<div class="col-lg-8">
				<ul class="cus-breadcrumb">
					<li><a href="{{url('/')}}">Home</a></li>
                    <li><a href="{{url('browse/job')}}">Jobs</a></li>
                    <li>
                        <form method="post" id="formCategory" action="{{route('browse.job')}}">
                            <input type="hidden" name="_token" value="{{Session::token()}}">
                            <input type="hidden" name="category_search" value="{{$job->category->id}}" id="category_search">
                        </form>
                        <a href="javascript:;" class="sendForm">{{$job->category->category_name}}</a>
                    </li>
				</ul>  
                <!-- Display title -->
                <h2 class="heading mb-2 m-color-gray mt-3">{!! display_subtitle($job->title,null,50) !!}</h2>
				
            </div>
            <div class="col-lg-4">
                <!-- Display min price and max price -->
                <h2 class="text-center">${{$job->job_min_price}} - {{$job->job_max_price}} USD</h2>
            </div>
		</div>    
	</div>
</section>
<!-- End header Title -->
<!-- content with Sidebar -->
<section class="product-block pt-0">
	<div class="container">
		<div class="row ">
			<div class="col-lg-8">
                <!-- check current login user -->
                @php
                    $checkUser=0;
                    if(Auth::check() && $parent_uid == $job->uid)
                    {
                        $checkUser=1;
                    }
                @endphp
                <ul class="nav nav-tabs m-nav-design mt-3 pb-3" id="myTab" role="tablist">
					<li class="nav-item">
                        <a class="nav-link @if($checkUser == 0) active @endif" id="home-tab-md" data-toggle="tab" href="#home-md" role="tab" aria-controls="home-md"
                          aria-selected="true">Project Details</a>
                    </li>
                    <!-- Display bid menu shows only to buyer-->
                    @if(Auth::check() && $parent_uid == $job->uid)
                      <li class="nav-item">
                        <a class="nav-link @if($checkUser == 1) active @endif" id="profile-tab-md" data-toggle="tab" href="#profile-md" role="tab" aria-controls="profile-md"
                          aria-selected="false">Bids</a>
                      </li>
                    @endif
                </ul>
                <div class="popular-tab-item">
					<div class="tab-content" id="myTabContent">
						<div class="tab-pane fade" id="project-details" role="tabpanel" aria-labelledby="overview-tab">
                        </div>
                    </div>
                </div>
                <div class="tab-content" id="myTabContentMD">
                    <div class="tab-pane fade show @if($checkUser == 0) active @endif" id="home-md" role="tabpanel" aria-labelledby="home-tab-md">
                        <!-- Display title -->
        				<h3 class=" mb-3 m-color-gray mt-4 jobTitleDetail">{{$job->title}}</h3>
                        <div class="detail-contetnt">
                            <!-- Display description-->
                          {!! $job->descriptions !!}

                        </div>

                        <h3 class=" mb-3 m-color-gray mt-5">Attached files</h3>
                        <div class="m-attach-files">
                            <!-- Display all media related to job-->
                            @if(count($job->jobMedia) > 0)
                                @foreach($job->jobMedia as $media)
                                     <a href="{{route('download_files_s3')}}?bucket={{env('bucket_order')}}&key={{$media->photo_s3_key}}&filename={{$media->media_url}}" class="btn btn-default m-btn-attch mr-2"><i class="fas fa-long-arrow-alt-down mr-2" aria-hidden="true"></i> {{$media->media_type}}</a>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <!-- Display bid tab for buyer only-->
                    <div class="tab-pane  @if($checkUser == 1) show active @else hide @endif" id="profile-md" role="tabpanel" aria-labelledby="profile-tab-md">
                        @if(count($job->job_bids) > 0)
                        @php
                            $countCheck=0;
                        @endphp
                        @foreach($job->job_bids as $key=>$jobAccept)
                            @if($jobAccept->status == 'accepted')
                                @php
                                    $countCheck=1;
                                @endphp
                            @endif
                        @endforeach
                        <!-- Display all the bids of sellers-->
                            @foreach($job->job_bids as $key=>$jobAccept)
                            <div class="m-bid-listing mb-3 p-3 ribbon_main" id="{{$jobAccept->secret}}">
                                @if($jobAccept->is_promoted_job == 1)
                                <div class="ribbon ribbon-top-left"><span>Promoted</span></div>
                                @endif
                                <div class="row">
                                    <div class="col-12 col-md-2 m-text-center">
                                        <a href="{{route('viewuserservices',$jobAccept->user['username'])}}" rel="comments4" data-id="{{$jobAccept->user['id']}}" data-poload="{{route('loadSellerProfile')}}?id={{$jobAccept->user['id']}}" class="jobTitle checkPopOver">
                                            <img src="{{get_user_profile_image_url($jobAccept->user)}}" alt="profile-image" class='img-round img-fluid m-bidimg-round'>
                                        </a>
                                    </div>
                                    <div class="col-12 col-md-10">
                                        <div class="row ">
                                            <div class="col-12 col-md-7">
                                                <div class="row">
                                                    <div class="col-8">
                                                        <!--redirection in profile page to seller page-->
                                                        <!--rel="comment3" is used for load seller profile data-->
                                                        <a href="{{route('viewuserservices',$jobAccept->user['username'])}}" rel="comments3" data-poload="{{route('loadSellerProfile')}}?id={{$jobAccept->user['id']}}" class="jobTitle"><p class="mf-16 mb-0"><strong>{{ $jobAccept->user['Name'] }}</strong></p></a>
                                                    </div>
                                                      @php
                                                        $now = \Carbon\Carbon::now();
                                                        $hours= $now->diffInHours($jobAccept->created_at);
                                                        if($hours > 24)
                                                        {
                                                            
                                                            $days=$hours/24;
                                                          
                                                            $time=intval(round($days)).' Days Ago';
                                                        }
                                                        elseif($hours < 1)
                                                        {
                                                            $min = $now->diffInMinutes($jobAccept->created_at);
                                                            $time =$min.' Minutes Ago';
                                                        }
                                                        else
                                                        {
                                                            $time =$hours.' Hours Ago';
                                                        }

                                                    @endphp
                                                    <div class="col">
                                                        <p class="mb-0">{{ $time }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-5">
                                                <div class="row">
                                                    <div class="col">
                                                        <p class="mf-16 mb-0">{{ $jobAccept->delivery_days }} Days</p>
                                                    </div>
                                                    <div class="col">
                                                        <p class="mf-16 mb-0"><strong>${{$jobAccept->price}} USD</strong></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="viewsellerprofile"><a href="{{route('viewuserservices',$jobAccept->user->username)}}" target="_blank">View Seller's Profile</a></div>

                                        <div class="m-rating d-innline mb-2 mt-2">
                                            <!-- Display rating of that seller-->
                                            @php
                                                $count=App\Order::calculateSellerAverageRatingCheck($jobAccept->seller_id);
                                                $totalReviews=App\Order::getReviewTotal($jobAccept->seller_id);
                                            @endphp

                                            {!! displayRating($count,$showFiveStar = 1) !!}
                                            <strong class="ml-2 mr-3">{{number_format($count,1)}}</strong>
                                            <span class="m-review m-text-lgray">({{$totalReviews}} Reviews)</span>
                                        </div>
                                        <div class="row">
                                           <div class="col-12 col-md-6">
                                                <p class="mb-2 dots">{!! display_subtitle($jobAccept->description,null,150) !!}
                                                    @if(strlen($jobAccept->description) > 150)
                                                    <a href="javascript:;" class="see_more_job" data-content="{{$jobAccept->description}}">See more</a>
                                                    @endif
                                                </p>
                                            </div>
                                            @if($jobAccept->status == 'accepted')
                                               @php
                                                    $countCheck=1;
                                                @endphp
                                            @endif

                                            <!-- Display button for awarting job to seller-->
                                            @if(strtotime($job->expire_on) >= time())
                                            @if($countCheck != 1)
                                                @if($job->status=='active' && $jobAccept->user->status == 1 && $jobAccept->user->is_delete == 0 && $jobAccept->user->soft_ban == 0)
                                                <div class="col-12 col-md-6 text-right">
                                                    <form method="post" action="{{route('payment')}}" id="submit_form_{{$jobAccept->secret}}">
                                                        <input type="hidden" name="_token" value="{{Session::token()}}">
                                                        <input type="hidden" name="job" value="{{$jobAccept->secret}}">
                                                    </form>
                                                    @if($job->is_approved == 1 && User::check_sub_user_permission('can_make_purchases'))
                                                    <span class="completed acceptAwarted cursor" data-id="{{$jobAccept->secret}}">Award</span>
                                                    @endif
                                                    {{-- <span class="job-chat-btn w-60 sendMsg" data-url="{{route('message_compose',['job',$job->seo_url,$jobAccept->user['username']])}}">Chat</span> --}}
                                                    @if(User::check_sub_user_permission('can_communicate_with_seller'))
                                                    <span class="job-chat-btn w-60 open_job_chat" data-user="{{$jobAccept->user->secret}}" data-service="{{$job->secret}}">Chat</span>
                                                    @endif
                                                </div>
                                                @endif
                                            @else
                                                @if($jobAccept->status == 'accepted')
                                                    @if($job->status=='active')
                                                        <div class="col-12 col-md-6 text-right">
                                                            <span class="completed jobMsg">Awarded</span>
                                                            {{-- <span class="job-chat-btn w-76 sendMsg" data-url="{{route('message_compose',['job',$job->seo_url,$jobAccept->user['username']])}}">Chat</span> --}}
                                                            @if(User::check_sub_user_permission('can_communicate_with_seller'))
                                                            <span class="job-chat-btn w-60 open_job_chat" data-user="{{$jobAccept->user->secret}}" data-service="{{$job->secret}}">Chat</span>
                                                            @endif
                                                        </div>
                                                    @endif    
                                                @endif
                                            @endif
                                            @endif

                                        </div>
                                        @if($jobAccept->is_promoted_job == 0 && strtotime($job->expire_on) >= time())
                                        <div class="row job_hide_rate_div">
                                            <div class="col-md-12 d_inherit">
                                                <a href="javascript:void(0)" data-secret="{{$jobAccept->secret}}" data-url="{{route('hide_job_bid',$jobAccept->secret)}}" data-toggle="tooltip" data-placement="bottom" title="Hide Bid" class="bid_hide_link_class"><i class="far fa-times-circle close_job_icon"></i></a>
                                                <div class="vl"></div>
                                                <div class="bid_rating" data-id="{{$jobAccept->secret}}" data-value="{{$jobAccept->rating}}"></div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else 
                        <!-- Display message if no order found-->
                            <div class="text-center pt-10">
                                <h5>No offers found..</h5>
                            </div>  
                        @endif
                    </div>
                </div>    
            </div>
            <!-- Sidebar -->
            <div class="col-lg-4">
                 

                <div class="sidetop-lay-round">
                    <div class="m-img-round-down">
                        <!--redirection in profile page to buyer page-->
                        <a href="{{route('viewuserservices',$job->user->username)}}" > <img src="{{get_user_profile_image_url($job->user)}}" alt="" class="img-fluid m-auto text-primary img-round"></a>
                    </div>
                    <div class="owner-title">
                        <!-- Display user name of buyer-->
                        <!--redirection in profile page to buyer page-->
                        <a href="{{route('viewuserservices',$job->user->username)}}"><h4 class="jobTitle">{{$job->user->Name}}
                            @if($job->user->is_premium_seller($job->uid) == true)
                                <img src="{{url('public/frontend/images/Badge.png')}}" alt="" class="img-fluid text-primary ml-2 m-width-15">
                            @endif
                            </h4></a>
                        <p class>Buyer</p>
                    </div>
                </div>
                <div class="side-layout m-bg-gray m-brd-rad-15">
                    <div class="siderbar-lay mt-5 m-brdr-btm ">
                        <!-- Display user description-->
                        {{--<p>Hi im Tom. Looking new web site</p> --}}
                        <p>{{ $job->user->description }}</p>

                    </div>
                    <div class="col-sm-12">
                        @if(isset($job->user->city) || isset($job->user->state) || isset($job->created_at))
                        <div class="row m-brdr-btm m-pd-3">
                            @if(isset($job->user->city) || isset($job->user->state))
                            <div class="col-sm-6 col-xs-6 pl-0">
                                <div class="m-verti-midd">
                                    <i class="fas fa-map-marker-alt mr-2"></i> <strong>
                                        <!-- Display city and state of user-->
                                        {{$job->user->city}},{{$job->user->state}}</strong>
                                </div>
                            </div>
                            @endif
                            @if(isset($job->created_at))
                            <div class="col-sm-6 col-xs-6 pl-0">
                                <div class="m-verti-midd">
                                    <i class="far fa-calendar-alt mr-2"></i> <strong>
                                        <!-- Display date since user using site-->
                                        {{$job->created_at->format('F jS,Y')}}</strong>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
                        @if(isset($job->tags))
                        <div class="row m-brdr-btm m-pd-3">
                            <div class="col-sm-12 col-xs-12 pl-0">
                                <div class="m-verti-midd">
                                    <!-- Display user all tags -->
                                    <i class="fas fa-tag mr-2"></i> {{$job->tags}}
                                </div>
                            </div>
                        </div>
                        @endif
                        <div class="row m-pd-3">
                            <div class="col-sm-6 col-xs-6 pl-0">
                                <!-- Display total time created task to current time interval -->
                                <div class="m-verti-midd">
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
                                    <i class="far fa-clock mr-2"></i>{{$time}}
                                </div>
                            </div>
                            <div class="col-sm-6 col-xs-6 pl-0">
                                <!-- Display total number of bids on that particular job -->
                                <div class="m-verti-midd">
                                    <i class="fas fa-gavel mr-2"></i> {{($job->job_offers)?$job->job_offers->count():0}}  Bids
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- new box -->
                @if(Auth::check() && $parent_uid == $job->uid)
                    <div class="side-layout m-brd-rad-15 mt-3  m-bg-lblue">
                        <div class="col-sm-12">
                            <div class="row m-brdr-btm border-lblue m-pd-3">
                                  <!-- Display total number of bids on that particular job -->
                                <div class="col-8  pl-0">
                                    
                                    <p class="mb-0 text-primary">Total Bids</p>
                                </div>
                                <div class="col pl-0">

                                    <p class="mb-0 text-primary m-text-right"><strong>{{($job->job_offers)?$job->job_offers->count():0}} </strong></p>
                                </div>
                            </div>
                            <div class="row m-brdr-btm border-lblue m-pd-3">
                                <!-- Display total average time for that particular job -->
                                <div class="col-8  pl-0">        
                                    <p class="mb-0 text-primary">Average Delivery Time</p>
                                </div>
                                <div class="col pl-0">
                                    @php
                                        if(count($job->job_offers) > 0)
                                        {
                                            $avg_delivery_time=$job->job_offers->avg('delivery_days');
                                        }
                                        else
                                        {
                                            $avg_delivery_time=0;
                                        }
                                    @endphp
                                    <p class="mb-0 text-primary m-text-right"><strong>{{number_format($avg_delivery_time,1)}} Days</strong></p>
                                </div>
                            </div>
                            <div class="row m-brdr-btm border-lblue m-pd-3">
                                <!-- Display total average price for that particular job -->
                                <div class="col-8  pl-0">
                                    <p class="mb-0 text-primary">Average Bid Price</p>
                                </div>
                                <div class="col pl-0">
                                    @php
                                        if(count($job->job_offers) > 0)
                                        {
                                            $avg_bid_price=$job->job_offers->avg('price');
                                        }
                                        else
                                        {
                                            $avg_bid_price=0;
                                        }
                                    @endphp
                                    <p class="mb-0 text-primary m-text-right"><strong>${{ number_format($avg_bid_price,1)}} USD</strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Make Offer -->
                    <div class="dyanamicDiv">
                    <!-- Display proposal box to send proposal to buyer only shows to seller -->
                        @if(Auth::check() && $parent_uid != $job->uid)
                            @if(count($job->job_accept) > 0)
                                @php
                                    $count=0;
                                @endphp
                                @foreach($job->job_accept as $checkData)
                                    @if($parent_uid == $checkData->seller_id)
                                        @php
                                        $count=$count+1;
                                        $detail=$checkData;
                                        @endphp
                                    @endif
                                @endforeach
                                @if($count == 0)
                                    @if($countCheck == 0 && strtotime($job->expire_on) >= time() && App\User::is_soft_ban() == 0)
                                    <form action="{{route('send.job_proposal')}}" method="post" id="job_proposal_1">
                                          <input type="hidden" name="_token" value="{{ Session::token() }}">
                                          <input type="hidden" name="service_id" value="{{$job->secret}}">
                                            <div class="side-layout m-brd-rad-15 mt-3 m-wh-brder-shadow">
                                                <p class="m-text-green mb-2"><strong>Make An offer to This Project</strong></p>
                                                <div class="row">
                                                    <div class="col-6">
                                                        <div class="form-group m-primary-input">
                                                            <label class="mb-2 m-text-lgray">Delivery (Days)</label>
                                                            <input type="number" class="form-control " name="days" id="days" autocomplete="off" placeholder="Delivery (Days)" value="">
                                                        </div>
                                                    </div>
                                                    <div class="col-6 pl-0">
                                                        <div class="form-group m-green-input">
                                                            <label class="mb-2 m-text-green">Price</label>
                                                             <input type="number" class="form-control " name="price" id="price_by" id="price"  autocomplete="off" placeholder="Enter Price">
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="m-text-lgray mb-1">Define your proposal</p>
                                                <textarea class="form-control noresize m-bg-gray mb-1"  rows="5" cols="50" placeholder="Write a catchy proposal" name="description" id="description"></textarea>
                                                @if($is_promoted_bid_exist == 0)
                                                <div class="row mt-1 mb-1">
                                                    <div class="col-12">
                                                        <div class="form-check">
                                                            <input type="checkbox" class="form-check-input" id="promote_bid" name="promote_bid">
                                                            <label class="form-check-label" for="promote_bid">
                                                                Make your bid stand out for just ${{env('JOB_PROMOTE_BID_FEE')}} and we’ll show your bid as featured on top of the list.  Only one available per job!
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mt-1 mb-1" id="payment_option_div" style="display: none;">
                                                    @php 
                                                        if(Auth::user()->earning >= env('JOB_PROMOTE_BID_FEE')) {
                                                            $use_from_wallet = env('JOB_PROMOTE_BID_FEE');
                                                        } else {
                                                            $use_from_wallet = Auth::user()->earning;
                                                        }
                                                    @endphp
                                                    <div class="col-12">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="payment_by" id="skrill_id" value="skrill" @if(Auth::user()->earning < env('JOB_PROMOTE_BID_FEE')) checked @endif>
                                                            <label class="form-check-label" for="skrill_id">Skrill</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="payment_by" id="paypal_id" value="paypal">
                                                            <label class="form-check-label" for="paypal_id">Paypal</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="radio" name="payment_by" id="wallet_id" value="wallet" @if(Auth::user()->earning >= env('JOB_PROMOTE_BID_FEE')) checked @endif @if(Auth::user()->earning < env('JOB_PROMOTE_BID_FEE')) disabled @endif >
                                                            <label class="form-check-label" for="wallet_id">Wallet (${{$use_from_wallet}})</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                                <button type="submit" class="btn m-btn-full m-btn-green m-brd-rad-15"><strong>Give Offer</strong></button>
                                            </div>
                                    </form>
                                    @endif
                                @else
                                    <div class="side-layout m-brd-rad-15 mt-3 m-wh-brder-shadow">
                                        <p class="m-text-green mb-2"><strong>Make An offer to This Project</strong></p>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group m-primary-input">
                                                    <label class="mb-2 d-block">Delivery (Days)</label>
                                                    <strong>{{$detail->delivery_days}}</strong>
                                                </div>
                                            </div>
                                            <div class="col-6 pl-0">
                                                <div class="form-group m-green-input">
                                                    <label class="mb-2 d-block">Price</label>
                                                    <strong class="mr-2">${{$detail->price}}</strong> USD
                                                </div>
                                            </div>
                                        </div>
                                        <p class="m-text-lgray mb-2">Proposal</p>
                                        <div class=" mb-1">
                                            <p class="mb-2 dots">{!! display_subtitle($detail->description,null,150) !!}
                                                    @if(strlen($jobAccept->description) > 150)
                                                    <a href="javascript:;" id="see_more_job" data-content="{{$detail->description}}">See more</a>
                                                    @endif
                                            </p>
                                        </div>
                                        <div class="prposal-footer mt-5 pt-5 mb-3">
                                            <h5 class="d-inline m-text-green"><i class="fas fa-check bid-green-round"></i> Done Bid</h5>
                                            @if($countCheck == 0 && strtotime($job->expire_on) >= time())
                                            <a href="javascript:;" data-id="{{$detail->secret}}" class="d-inline btn m-btn-gray pull-right editData"><i class="fas fa-pencil-alt"></i> Edit</a>
                                            <a href="javascript:;" class="d-inline btn m-btn-gray pull-right confirmation" data-id="{{$detail->secret}}"><i class="fas fa-trash "></i> Delete</a>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @else
                                @if(strtotime($job->expire_on) >= time() && App\User::is_soft_ban() == 0)
                                <form action="{{route('send.job_proposal')}}" method="post" id="job_proposal_1">
                                    <input type="hidden" name="_token" value="{{ Session::token() }}">
                                    <input type="hidden" name="service_id" value="{{$job->secret}}">
                                    <div class="side-layout m-brd-rad-15 mt-3 m-wh-brder-shadow">
                                        <p class="m-text-green mb-2"><strong>Make An offer to This Project</strong></p>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="form-group m-primary-input">
                                                    <label class="mb-2 m-text-lgray">Delivery (Days)</label>
                                                    <input type="number" class="form-control " name="days" id="days" autocomplete="off" placeholder="Delivery (Days)" value="">
                                                </div>
                                            </div>
                                            <div class="col-6 pl-0">
                                                <div class="form-group m-green-input">
                                                    <label class="mb-2 m-text-green">Price</label>
                                                     <input type="number" class="form-control " name="price"  id="price" autocomplete="off" placeholder="Enter Price">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <p class="m-text-lgray mb-1">Define your proposal</p>
                                                <textarea class="form-control noresize m-bg-gray mb-1"  rows="5" cols="50" placeholder="Write a catchy proposal" name="description" id="description"></textarea>
                                            </div>
                                        </div>
                                        @if($is_promoted_bid_exist == 0)
                                        <div class="row mt-1 mb-1">
                                            <div class="col-12">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" id="promote_bid" name="promote_bid">
                                                    <label class="form-check-label" for="promote_bid">
                                                        Make your bid stand out for just ${{env('JOB_PROMOTE_BID_FEE')}} and we’ll show your bid as featured on top of the list.  Only one available per job!
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-1 mb-1" id="payment_option_div" style="display: none;">
                                            @php 
                                                if(Auth::user()->earning >= env('JOB_PROMOTE_BID_FEE')) {
                                                    $use_from_wallet = env('JOB_PROMOTE_BID_FEE');
                                                } else {
                                                    $use_from_wallet = Auth::user()->earning;
                                                }
                                            @endphp
                                            <div class="col-12">
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="payment_by" id="skrill_id" value="skrill" @if(Auth::user()->earning < env('JOB_PROMOTE_BID_FEE')) checked @endif>
                                                    <label class="form-check-label" for="skrill_id">Skrill</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="payment_by" id="paypal_id" value="paypal">
                                                    <label class="form-check-label" for="paypal_id">Paypal</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="radio" name="payment_by" id="wallet_id" value="wallet" @if(Auth::user()->earning >= env('JOB_PROMOTE_BID_FEE')) checked @endif @if(Auth::user()->earning < env('JOB_PROMOTE_BID_FEE')) disabled @endif >
                                                    <label class="form-check-label" for="wallet_id">Wallet (${{$use_from_wallet}})</label>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="row">
                                            <div class="col-12">
                                                <button type="submit" class="btn m-btn-full m-btn-green m-brd-rad-15"><strong>Give Offer</strong></button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                @endif
                            @endif
                        @endif
                    @endif
                </div>
            </div>
            <!-- End Sidebar -->
		</div>    
	</div>
</section>

<!--modal for sending message to user who make offer for our job-->
<div class="modal fade custommodel" id="new-message-popup" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Send A Message</h5>

                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                {{ Form::open(['url' => '', 'method' => 'POST', 'id' => 'frmMessage']) }}  

               {{--  <input type="hidden" name="to_user" id="to_user_message" value="">
                <input type="hidden" name="service_id" value="{{$job->secret}}"> --}}

                <div class="modal-body form-group">
                    <div class="form-group">
                        <div class="col-lg-12">
                            <div id="withdraw_response"></div>
                            <label for="recipient-name" class="form-control-label">
                                <span style="color: #505050">Local Time: {{date('D h:i')}}</span>
                            </label>
                        </div>
                        <div class="col-lg-12">
                            <p class="lead emoji-picker-container">
                                {{Form::textarea('chat_message','',["class"=>"form-control","placeholder"=>"Write your message here...","id"=>"chat_message","maxlength"=>2500])}}
                            </p>
                            
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-12">
                            <div class="text-danger note-error" style="text-align: left;" ></div>
                            <div style="float: right;"><span id="chars">0</span> / 2500 chars max</div>
                        </div>
                    </div>
                    <div style="clear: both;"><br>
                    <div class="form-group">
                        <div class="col-lg-12">
                            @if(Auth::check() && Auth::user()->is_premium_seller() == true)
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        {{Form::select('select_title',[""=>"Select Template"]+$save_template_chat,null,['class'=>'form-control','id'=>'select_title_chat'])}}
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    {{-- Save as Template --}}
                                    <div class="form-group add-extra-detail">
                                        <label class="cus-checkmark">    
                                            <input id="save_template_chat" name="save_template" type="checkbox" value="1">
                                            <span class="checkmark"></span>
                                        </label>
                                        <div class="detail-box">
                                            <lable>Save As Template?</lable>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    {!! Form::submit('Send',['class' => 'btn send-request-buttom']) !!}
                </div>
                {{ Form::close() }}
            </div>
        </div>
    </div>
</div>
<!-- End content with Sidebar -->

@if(Auth::check() && Auth::user()->is_premium_seller() == true)
     @include('frontend.seller.save_template_contact_seller')
@endif

@endsection

@section('css')
<style>
</style>
@endsection

@section('scripts')
<script src="{{front_asset('assets/js/rating.js')}}"></script>
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type="text/javascript">
$('document').ready(function(){
    $(".bid_rating").each(function(){
        let value  = $(this).data('value');
        $(this).rating({
            "color":"green",
            "emptyStar":"fa fa-check-circle-o",
            "filledStar":"fa fa-check-circle",
            "value": value,
            "click":function (e) {
                if(e.data != undefined){
                    var bid_id = e.data;
                    var rating = e.stars;
                    $.ajax({
                        type : 'post',
                        url : "{{route('update_job_bid_rating')}}",
                        data : {
                            '_token':"{{csrf_token()}}",
                            'bid_id' : bid_id,
                            'rating':rating,
                        },
                        success : function(data){
                            if(data.status == 'success') {
                                //window.location.reload();
                                toastr.success('Job bid rating saved successfully.', 'Success');
                            } else {
                                toastr.error("Something went wrong, please try again.", "Error");
                            }
                        }
                    });
                }
            }
        });
    });

    $('.bid_hide_link_class').on('click', function(){
        var secret = $(this).data('secret');
        var url = $(this).data('url');
        $.ajax({
            type : 'get',
            url : url,
            success : function(data){
                if(data.status == 'success') {
                    $('#'+secret).hide();
                    toastr.success('Job bid hidden successfully.', 'Success');
                } else {
                    toastr.error("Something went wrong, please try again.", "Error");
                }
            }
        });
    });

    $('#payment_option_div').hide();
    $('#promote_bid').on('change', function(){
        if(this.checked) {
            $('#payment_option_div').show();
        } else {
            $('#payment_option_div').hide();
        }
    });
});

    /*function used to open send message modal for sending message to praticular user who give offer for our job*/
    $(document).on('click','.sendMsg',function(){
        var action_url=$(this).data('url');
        $('#frmMessage').attr('action', action_url);
        $('#new-message-popup').modal('show');
    });

    $(document).on('click','.sendForm',function()
    {
        $('#formCategory').trigger('submit');
    });

    function fetchData()
    {
        var content = '';
        var element = $(this);
        var id = element.attr("data-id");
        $.ajax({
        url: "{{route('loadSellerProfile')}}",
        method: "GET",
        async: false,
        data:{
        id : id
        },
        dataType: "JSON",
        success:function(data){
            content = $("#popover_html").html();
            content = content.replace(/p_image/g, "{{front_asset('images/avatars/avatar_01.png')}}");
            content = content.replace(/p_name/g, data.Name);
            }
        });
        return content;
    }

    $(document).ready(function(){
        var username="";  
        $(document).on('click', function (e) {
            $('[data-toggle="popover"],[data-original-title]').each(function () {
                if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {                
                    (($(this).popover('hide').data('bs.popover')||{}).inState||{}).click = false  // fix for BS 3.3.6
                }
            });
        });

        $('#chat_message').keyup(function () {
            var length = $(this).val().length;
            $('#chars').text(length);
        });
   
        /*rel="comment3" hover function is used for load seller profile data*/
        @if(!isMobileDevice())
        $('[rel=comments3]').mouseenter(function() {
        	$('.popover').popover('hide');
            var e = $(this);
            e.off('hover');

            $.ajax({
                url: e.data('poload'),
                method: "GET",
                async: false,
                success:function(d){
                    e.popover({
                        html:true,
                        content: d
                    }).popover('show');
                }
            });
        });
        /*rel="comment4" hover function is used for load seller profile data*/
        $('[rel=comments4]').mouseenter(function() {
        	$('.popover').popover('hide');
            var e = $(this);
            e.off('hover');
            $.ajax({
                url: e.data('poload'),
                method: "GET",
                async: false,
                success:function(d){
                    e.popover({
                        html:true,
                        content: d
                    }).popover('show');
                }
            });
        });
        @endif

        $(document).find(".alert").delay(3000).fadeOut("slow");
    });

    var minPrice={{$job->job_min_price}};
    var checkMinPrice={{env('OLD_MINIMUM_SERVICE_PRICE')}};
    if(minPrice < checkMinPrice){
        minPrice=checkMinPrice;
    }


    var maxPrice={{$job->job_max_price}};
    validate_joboffer_form();
    function validate_joboffer_form(){
        $('#job_proposal_1').bootstrapValidator({
            fields: {
                days: {
                    validators: {
                        notEmpty: {
                            message: 'Delivery days is required.'
                        },
                        lessThan: {
                            value: 90,
                            message: 'Delivery days must be less than or equal to 90.'
                        },greaterThan: {
                            value: 1,
                            message: 'Delivery days must be greater than or equal to 1.'
                        }
                    }
                },
                price: {
                    validators: {
                        notEmpty: {
                            message: 'Price is required.'
                        },
                        greaterThan: {
                            value: minPrice,
                            message: 'Price must be greater than or equal to '+minPrice
                        },
                        lessThan:{
                            value:maxPrice,
                            message: 'Price must be less than or equal to '+maxPrice
                        }
                    }
                },
                description: {
                    validators: {
                        notEmpty: {
                            message: 'description is required.'
                        },
                    }
                },
            }
        }).on('error.validator.bv', function (e, data) {
        }).on('success.form.bv', function (e, data) {
        });
    }

    


    $(document).on('click','.editData',function(){
        var id=$(this).data('id');

        $.ajax({
            url: '{{route("dyanamic.job_div")}}',
            type: 'get',
            data: "id="+id,
            success: function (data) {
                $('.dyanamicDiv').html(data.html);
                validate_joboffer_form();
            },
            error: function (xhr, desc, err) {
                //console.log(xhr);
                //console.log("Details: " + desc + "\nError:" + err);
            }
        });
    });

    $(document).on('click','.acceptAwarted',function(){
             var id=$(this).data('id');
        return bootbox.confirm('Are you sure to accept this proposal?', function (result) {
            if (result) {
                
               $('#submit_form_'+id).trigger('submit');
            }
        });
    });
    
    $(document).on('click','.see_more_job',function()
    {
        var data=$(this).data('content');
        $(this).parent().parent().find('.dots').html(data);
    });



    $(document).on('click', '.confirmation', function (e) {
        var id=$(this).data('id');
    
    return bootbox.confirm('Are you sure to remove this proposal?', function (result) {
        if (result) {
            
            $.ajax({
                url: '{{route("delete.proposal")}}',
                type: 'get',
                data: "id="+id,
                success: function (data) {
                    location.reload();
                },
                error: function (xhr, desc, err) {
                    //console.log(xhr);
                    //console.log("Details: " + desc + "\nError:" + err);
                }
            });
        }
    });
});

</script>
@endsection