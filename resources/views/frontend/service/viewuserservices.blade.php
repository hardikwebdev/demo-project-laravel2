@extends('layouts.frontend.main')
@section('pageTitle', $user->username.' | demo')

@section('og_app_id', '298062924465542')
@section('og_url', URL::current())
@section('og_title', $user->username." Profile")
@section('og_type', 'website')
@section('og_description', string_limit(strip_tags($user->description),100))
@section('og_image', get_user_profile_image_url($user))

@section('content')
@php
    use App\User;
    $UserDetail= new App\User;
    $dataUser=null;    

    if(Auth::check())
    {
    $parent_data=Auth::user()->parent_id;
    $dataUser=User::select('id','affiliate_id')->where('id',$parent_data)->first();
    }
@endphp

<!-- Masthead -->
<header class="masthead text-white">
	<div class="overlay"></div>
    <div class="bg-dark w-100">
	<div class="container py-4">
        <h1 class="font-24 font-weight-bold font-lato text-white mb-0 py-3">@if($user->username == Auth::user()->username) My Profile @else Profile @endif</h1>
	</div>
    </div>
</header>

<div class="container pb-5 font-lato">
    <div class="row ">
        <div class="col-12 col-lg-3 p-2 pt--15">
            <!-- Profile Section -->
            <div class="premium-container profile-sidebar h-max-content">
	            <!-- Begin Basic details Section -->
                @if($user->userDetails != null && $user->userDetails->intro_video_link != "")
	            <div class="row justify-content-between mt-1 position-relative">
                   <img src="{{$user->userDetails->intro_video_thumbnail}}" class="w-100 custom-intro-video-img" height="100">
                    <div class="video-lable video-link cursor-pointer" data-url="{{$user->userDetails->intro_video_link}}" data-mime="video/mp4" data-title="{{ucfirst($user->username)}}'s introduction video">
                        Play Video
                    </div>
                    <a href="Javascript:;" data-url="{{$user->userDetails->intro_video_link}}" data-mime="video/mp4" data-title="{{ucfirst($user->username)}}'s introduction video" class="video-link">
                        <img src="{{asset('public/frontend/images/video-play-icon.png')}}" class="intro-video-play-button new-play-btn">
                    </a>
                </div>
                @endif
	            <div class="row justify-content-between mt-3">
	                <div class="col-auto d-flex">
	                    @if(App\User::checkPremiumUser($user->id) == true)
	                        <img src="{{url('public/frontend/images/premium-bagde.png')}}" alt="" class="profile-premium-bagde">
	                        <p class="text-color-1 pl-2">Premium</p>
	                    @endif                    
	                </div>
	                <div class="col-auto pm-share-profile">
	                    @if(Auth::check())
	                    @if(Auth::user()->id == $user->id)
	                    <a href="Javascript:;" class="text-color-white copy_btn" data-clipboard-text="{{URL::current()}}"><img src="{{url('public/frontend/images/homepage log out/Link.png')}}"  alt=""></a>
	                    @endif
	                    @endif
	                </div>
	            </div> 
	            <div class="row justify-content-center">
	                <div class="col-12 text-center">
                        <img class="img-fluid cust-profile-user-picture rounded-circle" src="{{get_user_profile_image_url($user)}}">
	                    <p class="font-18 mb-0 mt-2 text-color-6 font-weight-bold">{{$user->username}}</p>   
	                    <div class="d-flex align-items-center justify-content-center">
	                        {!! displayRating($avg_seller_rating ,$showFiveStar = 2) !!} 
	                    </div> 
	                    <span class="text-color-1 font-16 font-weight-bold mx-1">
                            @if(round($avg_seller_rating,1) >= 5)
                                5
                            @else
                                {{ round($avg_seller_rating,1) }}
                            @endif
                        </span>
	                    <span class="font-12 text-color-4">({{$total_seller_rating}} reviews)</span>
	                    
	                    @if($user->seller_level != 'Unranked')
	                        <p class="font-14 text-color-4 pt-2">{{$user->seller_level}}</p>
	                    @endif

	                    @if (Auth::check() && $user->is_delete == 0)
	                    @if (Auth::user()->id != $user->id)
                            
                            @if($parent_uid != $user->id)
	                        <input class="btn btn-lg btn-block bg-primary-blue text-white mt-3 border-radius-6px font-13 open-new-message open_user_chat" type="button" value="Contact Me" data-user="{{$user->secret}}">
                            @endif

                            @if(Auth::user()->is_sub_user() == false)
                            <input class="btn btn-lg btn-block w-100 mt-3 text-color-1 custom-order-btn font-13 send-request-buttom open-custom-order custom-bg-transparent" data-toggle="modal" data-target="#custom-order-popup" type="submit" value="Custom Order">
                            @endif

                            @if($parent_uid == $user->id)
                                <div class="row justify-content-center">
                                    <div class="col-auto">
                                        <a href="{{route('user.followers',$user->username)}}" class="font-14 mt-2 d-block font-weight-bold text-color-1">Followers ({{$total_followers}})</a>
                                    </div>
                                    <div class="col-auto">
                                        <a href="{{route('user.followings',$user->username)}}" class="font-14 mt-2 d-block font-weight-bold text-color-1">Following ({{$total_following}})</a>
                                    </div>
                                </div>
                            @else
                                @if($user->followers->where('follower_id', $parent_uid)->where('status', \App\UserFollow::FOLLOW)->first())
                                <input id ="follow_user" class="font-14 mt-3 d-block font-weight-bold text-color-1 send-request-buttom custom-bg-transparent follow_action user_{{$user->secret}}" data-id="{{$user->secret}}" type="submit" value="Following">
                                @else
                                <input id="follow_user" class="font-14 mt-3 d-block font-weight-bold text-color-1 send-request-buttom  custom-bg-transparent follow_action user_{{$user->secret}}" data-id="{{$user->secret}}" type="submit" value="+ Follow"> 
                                @endif
                                
                                <div class="row justify-content-center">
                                    <div class="col-auto">
                                        <p class="font-14 mt-2 d-block font-weight-bold">Followers ({{$total_followers}})</p>
                                    </div>
                                    <div class="col-auto">
                                        <p class="font-14 mt-2 d-block font-weight-bold">Following ({{$total_following}})</p>
                                    </div>
                                </div>
                            @endif

                        @else
                        <div class="row justify-content-center">
                            <div class="col-auto">
                                <a href="{{route('user.followers',$user->username)}}" class="font-14 mt-2 d-block font-weight-bold text-color-1">Followers ({{$total_followers}})</a>
                            </div>
                            <div class="col-auto">
                                <a href="{{route('user.followings',$user->username)}}" class="font-14 mt-2 d-block font-weight-bold text-color-1">Following ({{$total_following}})</a>
                            </div>
                        </div>
	                    @endif
	                    @endif     
	                    
	                    @if(!Auth::check()) 
	                    <a href="{{url('login')}}" class="btn btn-lg btn-block bg-primary-blue text-white mt-3 border-radius-6px font-13 bg-transparent">Contact Me</a>
	                    
	                    <a href="{{url('login')}}" class="btn btn-lg btn-block w-100 mt-3 text-color-1 custom-order-btn font-13 bg-transparent" >Custom Order</button>

	                    <a href="{{url('login')}}" class="font-14 mt-3 d-block border-bottom border-gray pb-3 font-weight-bold text-color-1">+ Follow</a>
	                    @endif
	                </div>   
	            </div>
	            <!-- End Basic details Section -->

                <div class="row justify-content-between mt-3 d-none">
	                <div class="col-auto">
	                    <h5 class="font-18 text-color-2 font-weight-bold">Badges <span class="font-8 px-1 bg-orange text-white font-weight-bold border-radius-2px">NEW</span></h5>
	                </div>
	                <div class="col-auto">
                    	<a href="{{route('portfolio',$user->username)}}" class="font-11 text-color-1 font-weight-bold">View all</a>
	                </div>
	            </div>    

                <div class="row justify-content-between mt-3 d-none">
                    <div class="col-auto">
                        <img src="{{url('public/frontend/images/badge1.png')}}" class="img-fluid" >
                    </div>
                    <div class="col-auto">
                        <img src="{{url('public/frontend/images/badge2.png')}}" class="img-fluid" >
                    </div>
                    <div class="col-auto">
                        <img src="{{url('public/frontend/images/badge3.png')}}" class="img-fluid" >
                    </div>
                    <div class="col-auto">
                        <img src="{{url('public/frontend/images/badge4.png')}}" class="img-fluid" >
                    </div>

                    <div class="col-12">
	                    <div class="border-bottom border-gray pb-3"></div>
	                </div>
                </div>

	            <!-- Begin Portfolio Section -->
	            @if(count($Service)>0)
	            <div class="row justify-content-between mt-3">
	                <div class="col-auto">
	                    <h5 class="font-18 text-color-2 font-weight-bold">Portfolio <span class="font-8 px-1 bg-orange text-white font-weight-bold border-radius-2px">NEW</span></h5>
	                </div>
	                <div class="col-auto">
	            		@if(count($portfolio) > 0)
                    		<a href="{{route('portfolio',$user->username)}}" class="font-11 text-color-1 font-weight-bold">View all</a>
	            		@endif
	                </div>
	            </div>    
	            <div class="row justify-content-center mt-1">
	            	@if(count($portfolio) > 0)
		                @php $i = 1; @endphp
		                @foreach($portfolio as $data)
			                <div class="col-6 @if($i == 1) pr-1 @else pl-0 @endif pt-1">
			                	@php 
			                	$i = ($i==1)? 0 : 1; 
			                	@endphp
			                	@if($data->media_type == 'video')
                                    <div class="portfolio-img" style="background-image: url('{{($data->thumbnail_url)? $data->thumbnail_url : url('public/frontend/images/video_players.png')}}')">
                                        <img data-url="{{$data->media_link}}" data-mime="{{$data->mime}}" data-title="{{$data->title}}" src="{{get_video_player_img()}}" class="img-fluid cust-pd-15 portfolio-img video-link video-play-btn" >
                                    </div>
                                @else
			                		<img src="{{$data->thumbnail_url}}" data-link="{{$data->media_link}}" class="img-fluid portfolio-img custViewImage" alt="{{$data->title}}">
			                	@endif
		                	</div>
		                @endforeach
	                @else
	                @if($user->username == Auth::user()->username)
		            <div class="col-12">
	                    <div class="mt-3 text-center">
	                    	<div>
	                			<img src="{{url('public/frontend/images/upload-cloud.png')}}" class="img-fluid" alt="">
	                    	</div>
	                    	<p class="protfolio-note">Looks like you haven’t added any projects to your portfolio yet.</p>
                    		<a href="{{route('portfolio',$user->username)}}?addproject=show" class="font-11 text-color-1 font-weight-bold">+ Create New</a>
	                    </div>
	                </div>    
	                @else
	                <div class="text-center">
	                	<p class="protfolio-note">No portfolio items yet.</p>
	                </div>
	                @endif
	                @endif
	            </div>
                @endif
	            <!-- End Portfolio Section -->

	            <div class="row justify-content-center mt-3">   
	                <div class="col-7">
	                    <p class="font-12 text-color-6 font-weight-400 mb-1">From:</p>
	                </div>
	                <div class="col-5 text-right">
	                    <p class="font-12 font-weight-bold text-color-6 mb-1"> {{($user->country) ? $user->country->country_code : ""}}</p>
	                </div>
	                <div class="col-7">
	                    <p class="font-12 text-color-6 font-weight-400 mb-1">Member since:</p>
	                </div>
	                <div class="col-5 text-right">
	                    <p class="font-12 font-weight-bold text-color-6 mb-1">{{date('M Y',strtotime($user->created_at))}}</p>
	                </div>
                    <div class="col-7">
	                    <p class="font-12 text-color-6 font-weight-400">Last signed on:</p>
	                </div>
	                <div class="col-5 text-right">
	                    <p class="font-12 font-weight-bold text-color-6">
                            @if ($user->last_login_at != '0000-00-00 00:00:00')
                                {{get_time_ago(strtotime($user->last_login_at))}}
                            @else
                                -
                            @endif
                        </p>
	                </div>
	            </div>
	            @if($totalOrders)
	            <div class="row justify-content-center mt-3 pb-2">
	                <div class="col-7">
	                    <p class="font-12 text-color-6 font-weight-400 mb-1">Completed orders:</p>
	                </div>
	                <div class="col-5 text-right">
	                    <p class="font-12 font-weight-bold text-color-6 mb-1">{{ $totalOrders}}</p>
	                </div>
	                <div class="col-7">
	                    <p class="font-12 text-color-6 font-weight-400 mb-1"> On time:</p>
	                </div>
	                <div class="col-5 text-right"> 
	                    <p class="font-12 font-weight-bold text-color-6 mb-1">{{ number_format($totalDeliveredPer,2)}} %</p>
	                </div>
	                <div class="col-7">
	                    <p class="font-12 text-color-6 font-weight-400 mb-1">Late:</p>
	                </div>
	                <div class="col-5 text-right">
	                    <p class="font-12 font-weight-bold text-color-6 mb-1">{{ number_format($dileveredLatePer,2)}} %</p>
	                </div>
	                <div class="col-7 pr-0">
	                    <p class="font-12 text-color-6 font-weight-400 mb-1">  Cancelled after delay:</p>
	                </div>
	                <div class="col-5 text-right">
	                    <p class="font-12 font-weight-bold text-color-6 mb-1">{{ number_format($caceledAfterLatePer,2)}} %</p>
	                </div>
	                <div class="col-12">
	                    <div class="border-bottom border-gray pb-3"></div>
	                </div>
                </div>
	            @endif

                <div class="row justify-content-center mt-1">
	                <div class="col-8 pr-0">
                        <a href="{{url('getCustomOrderDetailPage')}}?id={{$user->secret}}" class="font-12 text-color-6 font-weight-400 mb-1">
                            Custom Orders Reviews:
                        </a>
	                </div>
	                <div class="col-4 text-right">
                        <a href="{{url('getCustomOrderDetailPage')}}?id={{@$user->secret}}"
                            class="font-12 font-weight-bold text-color-6 mb-1">
                            {{@$countReviewCustom}}
                        </a>
	                </div>
	                <div class="col-8 pr-0">
                        <a href="{{url('getJobOrderDetailPage')}}?id={{$user->secret}}" class="font-12 text-color-6 font-weight-400 mb-1">
                            Job Reviews:
                        </a>
	                </div>
	                <div class="col-4 text-right">
                        <a href="{{url('getJobOrderDetailPage')}}?id={{$user->secret}}"
                            class="font-12 font-weight-bold text-color-6 mb-1">
                            {{$countReviewJob}}
                        </a>
	                </div>

                    <div class="col-12">
	                    <div class="border-bottom border-gray pb-3"></div>
	                </div>
	            </div>
	            
	            <!--Begin Social Media Section-->
                @if(isset($user->social_links) && $user->social_links != "")

	            <div class="row justify-content-start mt-3 pt-1">  
	                <div class="col-12 pb-2">
	                    <h4 class="font-16 font-weight-bold text-color-2">Social Links <span class="font-8 px-1 bg-orange text-white font-weight-bold border-radius-2px">NEW</span></h4>
	                </div>

                    {{-- Facebook  --}}
                    @if(isset($user->social_links->facebook_link) && $user->social_links->facebook_url != "")
	                <div class="col-2">
                        <a href="{{$user->social_links->facebook_url}}" target="_blank">
                            <img src="{{url('public/frontend/images/social/fb-circle.png')}}">
                        </a>
	                </div>
                    @else
                    <div class="col-2">
                        <img src="{{url('public/frontend/images/social/fb-circle-black.png')}}">
	                </div>
                    @endif

                    {{-- Twitter  --}}
                    @if(isset($user->social_links->twitter_link) && $user->social_links->twitter_url != "")
	                <div class="col-2">
                        <a href="{{$user->social_links->twitter_url}}" target="_blank">
                            <img src="{{url('public/frontend/images/social/tw-circle.png')}}">
                        </a>
	                </div>
                    @else
                    <div class="col-2">
                        <img src="{{url('public/frontend/images/social/tw-circle-black.png')}}">
	                </div>
                    @endif

                    {{-- YouTube  --}}
                     @if(isset($user->social_links->youtube_link) && $user->social_links->youtube_url != "")
	                <div class="col-2">
                        <a href="{{$user->social_links->youtube_url}}" target="_blank">
                            <img src="{{url('public/frontend/images/social/yu-circle.png')}}">
                        </a>
	                </div>
                    @else
                    <div class="col-2">
                        <img src="{{url('public/frontend/images/social/yu-circle-black.png')}}">
	                </div>
                    @endif

                    {{-- Linkedin  --}}
                    @if(isset($user->social_links->linkedin_link) && $user->social_links->linkedin_url != "")
                    <div class="col-2">
                        <a href="{{$user->social_links->linkedin_url}}" target="_blank">
                            <img src="{{url('public/frontend/images/social/link-circle.png')}}">
                        </a>
                    </div>
                    @else
                    <div class="col-2">
                        <img src="{{url('public/frontend/images/social/link-circle-black.png')}}">
	                </div>
                    @endif

                    {{-- Instagram  --}}
                    @if(isset($user->social_links->instagram_link) && $user->social_links->instagram_url != "")
                    <div class="col-2">
                        <a href="{{$user->social_links->instagram_url}}" target="_blank">
                            <img src="{{url('public/frontend/images/social/insta-circle.png')}}">
                        </a>
                    </div>
                    @else
                    <div class="col-2">
                        <img src="{{url('public/frontend/images/social/insta-circle-black.png')}}">
	                </div>
                    @endif
                     
                    <div class="col-12">
	                    <div class="border-bottom border-gray pb-3"></div>
	                </div>
	            </div>
                @endif
	            <!--END Social Media Section-->

	            <!--Begin Language Section-->
	            <div class="row justify-content-center mt-3 pb-4 pt-1">  
	                <div class="col-12 pb-2">
	                    <h4 class="font-16 font-weight-bold text-color-2">Languages</h4>
	                </div> 
	                @foreach($user->language as $language)
	                <div class="col-5">
	                    <p class="font-12 text-color-6 font-weight-400">{{$language->language}}</p>
	                </div>
	                <div class="col-7 text-right">
	                    <p class="font-12 font-weight-bold text-color-6">{{ucwords(str_replace('_',' ',$language->level))}}</p>
	                </div>
	                @endforeach
	            </div>
	            <!--End Language Section-->

	            <!--Begin Skill Section-->
	            @if(count($user->skill))
	            <div class="row justify-content-center mt-3 pb-4 pt-1">  
	                <div class="col-12 pb-2">
	                    <h4 class="font-16 font-weight-bold text-color-2">Skills</h4>
	                </div>
	                @foreach($user->skill as $skills) 
	                <div class="col-6">
	                    <p class="font-12 text-color-6 font-weight-400">{{$skills->skill}}</p>
	                </div>
	                @endforeach
	            </div>
	            @endif
	            <!--End Skill Section-->
            </div>
            <!-- End Profile Section -->

            <!-- Description Section -->
            <div class="premium-container profile-sidebar h-max-content  mt-4">
            	<div class="row custom-margin-top">
            		<div class="col-12 profile-description-title text-color-2">
            			Description
            		</div>
            		<div class="col-12 custom-profile-text font-12 text-color-6">
            			<span class="wordbreack readless-text-{{$user->secret}}">{!! string_limit( nl2br($user->description) ,150) !!}</span>
                    	@if(strlen($user->description) > 150)
	                    	<span class="wordbreack d-none readmore-text-{{$user->secret}}">{!! nl2br($user->description) !!}</span>
							<label class="text-primary btn-link read-more" id="readmore-{{$user->secret}}" data-id="{{$user->secret}}">Read More</label>
							<label class="text-primary btn-link read-less d-none" id="readless-{{$user->secret}}" data-id="{{$user->secret}}">Less</label>
						@endif
            		</div>
            	</div>
            </div>
            <!-- Description Section -->

            <!--Begin Affiliate Section-->
            @if(Auth::check() && $countOrderPurchaseProfile > 0)
            @if($user->is_premium_seller($user->id) == true)
	            @if($user->is_affiliate_profile == 1)
	            <div class="premium-container profile-sidebar mt-4 afilliate-content-bg pb-4">
	                <div class="category-list">
	                    <div class="custom-sidebar-header font-weight-bold">
	                        Share & Earn 15% Cash Back!
	                    </div>
	                </div>
	                <div class="delivery-day-block custom">
	                    <div class="row custom-margin-top">
	                        <div class="col-12 text-left"><span><p class="custom-sidebar-subheader">Earn A 15% Commission By Sharing This Service here's Your Personal Affiliate Link</p></span></div>
	                    </div>
	                    @if(Auth::user()->is_sub_user() == false)
	                    <div class="input-group mt-2">
	                        <input type="text" class="form-control aff_link_browser border-right-0 bg-white" readonly="" value="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{Auth::user()->affiliate_id}}" aria-describedby="basic-addon1">
	                    	<div class="input-group-append">
	                        	<a href="javascript::void(0)" class="input-group-text bg-transparent copy_btn border-left-0" data-clipboard-text="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{Auth::user()->affiliate_id}}"><i class="fa fa-clone" aria-hidden="true"></i></a>
	                    	</div>
	                    </div>
	                    @else
	                    <div class="input-group mt-2">
	                        <input type="text" class="form-control border-right-0 bg-white aff_link_browser" readonly="" value="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{$dataUser->affiliate_id}}" aria-describedby="basic-addon1">
	                    	<div class="input-group-append">
	                        	<a href="javascript::void(0)" class="border-leftbg-transparent" data-clipboard-text="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{$dataUser->affiliate_id}}"><i class="fa fa-clone" aria-hidden="true"></i></a>
	                    	</div>
	                    </div>
	                    @endif
	                </div>
	            </div> 
	            @endif
            @else
            <div class="premium-container profile-sidebar mt-4 afilliate-content-bg pb-4">
                <div class="category-list">
                    <div class="custom-sidebar-header font-weight-bold">
                        Share & Earn 15% Cash Back!
                    </div>
                </div>

                <div class="delivery-day-block custom">

                    <div class="row custom-margin-top">
                        <div class="col-12 text-left"><span><p class="custom-sidebar-subheader">Earn A 15% Commission By Sharing This Service here's Your Personal Affiliate Link</p></span></div>
                    </div>
                    @if(Auth::user()->is_sub_user() == false)
                    <div class="input-group mt-2">
                        <input type="text" class="form-control aff_link_browser border-right-0 bg-white" readonly="" value="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{Auth::user()->affiliate_id}}" aria-describedby="basic-addon1">
                    	<div class="input-group-append">
                        	<a href="javascript::void(0)" class="input-group-text bg-transparent copy_btn border-left-0" data-clipboard-text="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{Auth::user()->affiliate_id}}"><i class="fa fa-clone" aria-hidden="true"></i></a>
                    	</div>
                    </div>
                    @else
                    <div class="input-group mt-2">
                        <input type="text" class="form-control aff_link_browser border-right-0 bg-white" readonly="" value="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{$dataUser->affiliate_id}}" aria-describedby="basic-addon1">
                    	<div class="input-group-append">
                        	<a href="javascript::void(0)" class="input-group-text bg-transparent copy_btn border-left-0" data-clipboard-text="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{$dataUser->affiliate_id}}"><i class="fa fa-clone" aria-hidden="true"></i></a>
                    	</div>
                    </div>
                    @endif
                </div>
            </div> 
            @endif
            @elseif(Auth::check() && Auth::user()->username == 'culsons')
            @if($user->is_premium_seller($user->id) == true)
                @if($user->is_affiliate_profile == 1)
                <div class="premium-container profile-sidebar mt-4 afilliate-content-bg pb-4">
                    <div class="category-list">
                        <div class="custom-sidebar-header font-weight-bold">
                            Share & Earn 15% Cash Back!
                        </div>
                    </div>

                    <div class="delivery-day-block custom">

                        <div class="row custom-margin-top">
                            <div class="col-12 text-left"><span><p class="custom-sidebar-subheader">Earn A 15% Commission By Sharing This Service here's Your Personal Affiliate Link</p></span></div>
                        </div>
                        @if(Auth::user()->is_sub_user() == false)
                        <div class="input-group mt-2">
	                        <input type="text" class="form-control aff_link_browser border-right-0 bg-white" readonly="" value="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{Auth::user()->affiliate_id}}" aria-describedby="basic-addon1">
	                    	<div class="input-group-append">
	                        	<a href="javascript::void(0)" class="input-group-text bg-transparent copy_btn border-left-0" data-clipboard-text="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{Auth::user()->affiliate_id}}"><i class="fa fa-clone" aria-hidden="true"></i></a>
	                    	</div>
	                    </div>
                        @else
                        <div class="input-group mt-2">
	                        <input type="text" class="form-control aff_link_browser border-right-0 bg-white" readonly="" value="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{$dataUser->affiliate_id}}" aria-describedby="basic-addon1">
	                    	<div class="input-group-append">
	                        	<a href="javascript::void(0)" class="input-group-text bg-transparent copy_btn border-left-0" data-clipboard-text="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{$dataUser->affiliate_id}}"><i class="fa fa-clone" aria-hidden="true"></i></a>
	                    	</div>
	                    </div>
                        @endif
                    </div>
                </div> 
                @endif
            @else
                <div class="premium-container profile-sidebar mt-4 afilliate-content-bg pb-4">
                    <div class="category-list">
                        <div class="custom-sidebar-header font-weight-bold">
                            Share & Earn 15% Cash Back!
                        </div>
                    </div>

                    <div class="delivery-day-block custom">

                        <div class="row custom-margin-top">
                            <div class="col-12 text-left"><span><p class="custom-sidebar-subheader">Earn A 15% Commission By Sharing This Service here's Your Personal Affiliate Link</p></span></div>
                        </div>
                        @if(Auth::user()->is_sub_user($user->id) == false)
                        <div class="input-group mt-2">
	                        <input type="text" class="form-control aff_link_browser border-right-0 bg-white" readonly="" value="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{Auth::user()->affiliate_id}}" aria-describedby="basic-addon1">
	                    	<div class="input-group-append">
	                        	<a href="javascript::void(0)" class="input-group-text bg-transparent copy_btn border-left-0" data-clipboard-text="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{Auth::user()->affiliate_id}}"><i class="fa fa-clone" aria-hidden="true"></i></a>
	                    	</div>
	                    </div>
                        @else
                        <div class="input-group mt-2">
	                        <input type="text" class="form-control aff_link_browser border-right-0 bg-white" readonly="" value="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{$dataUser->affiliate_id}}" aria-describedby="basic-addon1">
	                    	<div class="input-group-append">
	                        	<a href="javascript::void(0)" class="input-group-text bg-transparent copy_btn border-left-0" data-clipboard-text="{{url('/')}}/promoteprofile/{{$user->affiliate_id}}/{{$dataUser->affiliate_id}}"><i class="fa fa-clone" aria-hidden="true"></i></a>
	                    	</div>
	                    </div>
                        @endif
                    </div>
                </div> 
            @endif
            @endif
            <!--End Affiliate Section-->

            {{-- Start Direct Follow Link Section --}}
            <div class="premium-container profile-sidebar mt-4 afilliate-content-bg pb-4">
                <div class="category-list">
                    <div class="custom-sidebar-header font-weight-bold">
                        Share & Follow this profile
                    </div>
                </div>

                <div class="delivery-day-block custom">

                    <div class="row custom-margin-top">
                        <div class="col-12 text-left"><span><p class="custom-sidebar-subheader">Share and Follow this profile</p></span></div>
                    </div>
                    <div class="input-group mt-2">
                        <input type="text" class="form-control aff_link_browser border-right-0 bg-white" readonly="" value="{{route('viewuserservices',[$user->username])}}?follow_confirmation=1" aria-describedby="basic-addon1">
                        <div class="input-group-append">
                            <a href="javascript::void(0)" class="input-group-text bg-transparent copy_btn border-left-0" data-clipboard-text="{{route('viewuserservices',[$user->username])}}?follow_confirmation=1"><i class="fa fa-clone" aria-hidden="true"></i></a>
                        </div>
                    </div>
                </div>
            </div> 
            {{-- End Direct Follow Link Section --}}
            
        </div>

        <div class="col-12 col-lg-9 ">
            <!--Begin Testimonials Section-->
            @if(count($services_review)>0)
            <div class="bg-slider w-100 mt-5 rounded">
                <div class="container  py-3">
                    <div class="owl-carousel popular-grid-five slick-slider">   
                        @foreach($services_review as $review)
                            <div>
                                <div class="row justify-content-center position-relative">
                                    <div class="col-12 col-lg-4 text-center pr-lg-0 py-3" style="background-image: url('{{ front_asset('images/homepage log out/quotes.png')}}'); background-repeat: no-repeat; background-position: 55px 4px;">
                                        <img src="{{get_user_profile_image_url($review->user)}}" alt="profile-image" class="rounded-circle slider-proflie-img mx-auto">
                                        <p class="font-16 pt-1 text-white"><i>{{$review->user->Name}}</i></p>
                                    </div>
                                    <span class="mr-3 slider-separator"></span>
                                    <div class="col-12 col-lg-7 align-self-center text-center text-lg-left py-3">
                                        {!! displayRating($review->seller_rating ,$showFiveStar = 2) !!}
                                        <p class="font-14 text-center text-lg-left text-color-4 pt-3 pr-lg-5">
                                            <span class="text-color-4 readless-text-{{$review->id}}">{{string_limit($review->completed_note,100)}}</span>
                                            @if(strlen($review->completed_note) > 100)
                                                <span class="d-none text-color-4 readmore-text-{{$review->id}}">{{$review->completed_note}}</span>
                                                <label class="text-primary btn-link read-more" id="readmore-{{$review->id}}" data-id="{{$review->id}}">Read More</label>
                                                <label class="text-primary btn-link read-less d-none" id="readless-{{$review->id}}" data-id="{{$review->id}}">Less</label>
                                            @endif
                                        </p>
                                    </div>
                                </div>  
                            </div> 
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            <!--End Testimonials Section-->

            <!--Begin Services Section-->
            <div class="row custom-clearfix-margin-top">
                <div class="@if(count($Service) > 1 && $parent_uid == $user->id) offset-md-8 @else offset-md-10 @endif col-md-2 text-right">
                    @if($user->username == $parent_username)
                    <a href="{{route('block.userlist')}}">Blocked Users</a>
                    @endif
                </div>
                @if(count($Service) > 1 && $parent_uid == $user->id)
                <div class="col-md-2 pl-0 text-right">
                    <a href="{{route('serviceChangeOrder')}}">Rearrange Services </a>
                </div>
                @endif
            </div>
            <div class="legt-listing-container legt-grid-view filter-days custom services-filter-listing custom-clearfix-margin-top">
                @if(count($Service))
                    @foreach($Service as $service )
                        <div class="legt-card-layout">
                            @include('frontend.service.single-item')
                        </div>
                    @endforeach
                    <div class="total-count-show col-12 text-center cus-show-entry cus-grid-full">
                        <div>
                            Showing {{ $Service->firstItem() }} to {{ $Service->lastItem() }} of
                            total {{$Service->total()}} services
                        </div>
                    </div>
                @else
                    <span class="text-center no-service-found no-service-found-margin-left">No services are available.</span>
                @endif
            </div>
            <div class="col-sm-12 text-center">
                <img src="{{url('public/frontend/assets/img/filter-loader.gif')}}" class="ajax-load">
            </div>
            <!--End Services Section-->

            <!--Begin Custom order Section-->
            @if (!Auth::check() || (Auth::user()->id != $user->id && Auth::user()->is_sub_user() == false))        
            <div class="row bg-slider mt-3 border-radius-6px mx-1 py-4">
                <div class="col-12 col-lg-6 text-center text-lg-right align-self-center">
                    <p class="font-22 text-white mb-0"><span class="text-color-5"> Need something </span> <span class="custom-ul">custom?</span></p>
                </div>
                <div class="col-12 col-lg-6 text-center text-lg-left mt-2 mt-lg-0">
                    @if(!Auth::check()) 
                    <a href="{{url('login')}}" class="btn bg-primary-blue text-white px-5 border-radius-6px  position-relative parent-diamond-btn">Contact Me <img src="public/frontend/images/homepage log out/diamond.png" class="pb-2 diamond-icon"></a>
                    @else
                    @if (Auth::user()->id != $user->id)
                    <button class="btn bg-primary-blue text-white px-5 border-radius-6px  position-relative parent-diamond-btn open-custom-order" data-toggle="modal" data-target="#custom-order-popup">Contact Me <img src="public/frontend/images/homepage log out/diamond.png" class="pb-2 diamond-icon"></button>
                    @endif
                    @endif
                </div>
            </div>
            @endif
            <!--End Custom order Section-->
        </div>
    </div>
</div>



 
@if (Auth::check() && $user->is_delete == 0)
    @if (Auth::user()->id != $user->id)
        <!--begin::Custom Order Modal-->
        <div class="modal fade custommodel" id="custom-order-popup" tabindex="-1" role="dialog"
             aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Custom Order</h5>

                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    {{ Form::open(['route' => ['request_custom_quote'], 'method' => 'POST', 'id' => 'frmCustomQuote']) }}
                    <input type="hidden" name="seller_uid" value="{{$user->id}}">
                    <div class="modal-body form-group">
                        <div class="form-group">
                            <div class="col-lg-12">
                                <div id="withdraw_response"></div>
                                <label for="recipient-name" class="form-control-label">Please describe your request
                                    in as much detail as possible:</label>
                            </div>
                            <div class="col-lg-12">
                                {{Form::textarea('descriptions','',["class"=>"form-control","placeholder"=>"Enter your descriptions here...","id"=>"descriptions",'maxlength'=>"2500",'rows' => 6])}}
                                <div class="text-danger descriptions-error" style="text-align: left;"></div>
                                <div style="float: right;"><span id="chars_desc">0</span> / 2500 chars max</div>
                            </div>
                        </div>
                        <div class="form-group hide">
                            <div class="col-lg-12">
                                <div id="withdraw_response"></div>
                                <label for="recipient-name" class="form-control-label">Delivery days</label>
                            </div>
                            <div class="col-lg-12">
                                {{Form::text('delivery_days','0',["class"=>"form-control custom-desc","placeholder"=>"Enter your delivery days here..."])}}
                                <div class="text-danger delivery-days-error" style="text-align: left;"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-12">
                                <div id="withdraw_response"></div>
                                <label for="recipient-name" class="form-control-label">Max Budget</label>
                            </div>
                            <div class="col-lg-12">
                                {{Form::text('price','',["class"=>"form-control custom-desc","placeholder"=>"Enter your price here..."])}}
                                <div class="text-danger price-error" style="text-align: left;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">


                        {!! Form::submit('Request Custom Order',['class' => 'btn send-request-buttom']) !!}
                    </div>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
        <!--end::Custom Order Modal-->

        <!--begin::Send A Message Modal-->
        <div class="modal fade custommodel" id="new-message-popup" tabindex="-1" role="dialog"
             aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Send A Message</h5>

                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>


                    {{ Form::open(['route' => ['message_compose',['user',$user->username]], 'method' => 'POST', 'id' => 'frmMessage']) }}
                    {{-- <input type="hidden" name="to_user" value="{{$user->id}}">
                    <input type="hidden" name="service_id" value="0"> --}}

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
                                <div class="text-danger note-error" style="text-align: left;"></div>
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
                                                        <input id="save_template_chat" name="save_template"
                                                               type="checkbox" value="1">
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
            <!--end::Send A Message-->
        </div>
    @endif
@endif

{{-- modal for direct follow link --}}
@if($direct_follow_response['type'] == 'success')
    <div class="modal fade custompopup" id="direct_follow_link_modal_id" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title bold-lable" id="directFollowModalLabel">{{ $direct_follow_response['msg'] }}</h5>
                    <button type="button" class="close cancel_follow" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="" class="send-request-buttom" id="send_follow" onclick="onSendFollowClick('{{ $direct_follow_response['user_id'] }}')">Yes</a>
                        </div>
                        <div class="col-md-6">
                            <a href="javascript:void(0);" class="cancel-request-buttom cancel_follow" id="cancel_follow">No</a>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
@endif
{{-- modal for direct follow link --}}

@endsection

@section('css')
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="{{url('public/frontend/css/price_range_style.css')}}"/>
{{-- Emoji CSS --}}
<link rel="stylesheet" type="text/css" href="{{front_asset('css/emoji/emoji.css')}}">
<link rel="stylesheet" type="text/json" href="{{front_asset('css/emoji/emoji.css.map')}}">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
{{-- END Emoji CSS --}}
@endsection
@section('scripts')
<script type="text/javascript" src="{{front_asset('bootstrap/dist/js/bootstrap-tagsinput.js')}}"></script> 
<script type="text/javascript" src="{{url('public/frontend/js/price_range_script.js')}}"></script>

{{-- Emoji JS --}}
<script type="text/javascript" src="{{front_asset('js/emoji/config.js')}}"></script>
<script type="text/javascript" src="{{front_asset('js/emoji/util.js')}}"></script>
<script type="text/javascript" src="{{front_asset('js/emoji/jquery.emojiarea.js')}}"></script>
<script type="text/javascript" src="{{front_asset('js/emoji/emoji-picker.js')}}"></script>
{{-- END Emoji JS --}}

<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type="text/javascript">

    
    var checkMsg={{$showMsg}};
    if(checkMsg == 1)
    {
        $('#new-message-popup').modal('show');
    }

    var checkCustom={{$showCustomBox}};
    if(checkCustom == 1)
    {
        $('#custom-order-popup').modal('show');
    }

    $(document).ready(function () {


        var page = 1;
        var call_pagination = 0;
        $(window).scroll(function() {
            if($(window).scrollTop() + $(window).height() >= ($(document).height() - $('footer').height() )) {
                console.log(call_pagination);
                if(call_pagination == 0){
                    page++;
                    loadMoreData(page);
                }
            }
        });

        function loadMoreData(page){
            var url = '{{url()->current()}}';
            var page = page;
            $.ajax({
                method:"get",
                async:false,
                url:url,
                data:{'page':page},
                beforeSend: function()
                {
                    $('.ajax-load').show();
                }
            })
            .done(function(data)
            {
                if(data == ""){
                    $('.ajax-load').html("No more records found");
                    call_pagination = 1;
                }
                $('.ajax-load').hide();
                $('.services-filter-listing').append(data);
            })
            .fail(function(jqXHR, ajaxOptions, thrownError)
            {
                console.log(thrownError);
                alert('server not responding...');
            });
        }



    $(document).on('click','.getReview',function(){
        var review=$(this).data('review');
        var ordername=$(this).data('ordername');
        var rating=$(this).data('rating');
        var user=$(this).data('user');

        if(rating == 5)
        {
            $('#rating').html('<span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span>');
        }
        else if(rating == 4)
        {
            $('#rating').html('<span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star unchecked"></span>');
        }
        else if(rating == 3)
        {
            $('#rating').html('<span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span>');
        }
        else if(rating == 2)
        {
            $('#rating').html('<span class="fa fa-star checked"></span><span class="fa fa-star checked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span>');
        }
        else if(rating == 1)
        {
             $('#rating').html('<span class="fa fa-star checked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span>');
        }
        else
        {
            $('#rating').html('<span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span><span class="fa fa-star unchecked"></span>');
        }

        $('#orderName').text(ordername);
        $('#username').text(user);
        $('#review').text(review);
        $('#myModal').modal('show');
    })

    var maxLength = 2500;

    $(document).on('click','.open-new-message',function(){
        $('#chat_message').val('');
        $('#chars').text(0);
        $('#save_template_chat').prop("checked",false);
    });

    $('#message').keyup(function () {
        var length = $(this).val().length;
        $('#chars').text(length);
    });

    /*Create Custom Quote*/
    // $('.open-custom-order').magnificPopup({
    //     type: 'inline',
    //     removalDelay: 300,
    //     mainClass: 'mfp-fade',
    //     closeMarkup: '<div class="close-btn mfp-close"><svg class="svg-plus"><use xlink:href="#svg-plus"></use></svg></div>'
    // });
    
    $('#descriptions').keyup(function () {
        var length = $(this).val().length;
        $('#chars_desc').text(length);
    });
    $('textarea').keyup(function() {
        var length = $(this).val().length;
        var length = maxLength-length;
        $('#chars_desc').text(length);
    });
});
</script>    
<script>
    $(function () {
        // Initializes and creates emoji set from sprite sheet
        window.emojiPicker = new EmojiPicker({
            emojiable_selector: '[data-emojiable=true]',
            assetsPath: "{{front_asset('img/emoji/')}}",
            popupButtonClasses: 'fa fa-smile-o'
        });
        // Finds all elements with `emojiable_selector` and converts them to rich emoji input fields
        // You may want to delay this step if you have dynamically created input fields that appear later in the loading process
        // It can be called as many times as necessary; previously converted input fields will not be converted again
        window.emojiPicker.discover();
    });
    $('#chat_message').keyup(function() {
        var length = $(this).val().length;
        $('#chars').text(length);
    });
</script>
<script>
    // Google Analytics
    (function (i, s, o, g, r, a, m) {
        i['GoogleAnalyticsObject'] = r;
        i[r] = i[r] || function () {
            (i[r].q = i[r].q || []).push(arguments)
        }, i[r].l = 1 * new Date();
        a = s.createElement(o),
        m = s.getElementsByTagName(o)[0];
        a.async = 1;
        a.src = g;
        m.parentNode.insertBefore(a, m)
    })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

    ga('create', 'UA-49610253-3', 'auto');
    ga('send', 'pageview');
</script>
@if($direct_follow_response['type'] == 'success')
<script>
        $('#direct_follow_link_modal_id').modal('show');
        $('.cancel_follow').on('click', function(){
            $('#direct_follow_link_modal_id').modal('hide');
            window.location.href = window.location.origin + window.location.pathname ;
        });

        function onSendFollowClick(follow_userid) {
            $.ajax({
                method: "POST",
                url: "{{route('follower')}}",
                data: {"_token": _token, "user_id": follow_userid},
                success: function (data) {
                    if (data.status == true) {
                       //show toast message when sucesssfully follow done
                       toastr.success("You have followed sucessfully");
                    }
                    $('#direct_follow_link_modal_id').modal('hide');
                    window.location.href = window.location.origin + window.location.pathname ;
                }
            });
        };
</script>
@endif
@if($direct_follow_response['type'] == 'error_already_follow')
    <script>
            window.location.href = window.location.origin + window.location.pathname ;
    </script>
@endif
@endsection