<div class="row" id="author">
    <div class="col-12">
        <div class="py-3">
            <h2 class="text-color-2 font-28 font-weight-bold pb-3">About the Author</h2>
        </div>
    </div>
    <div class='col-12 col-xl-6 d-flex flex-column flex-md-row'>
        <div class='text-center'>
            <a href="{{route('viewuserservices',$serviceUser->username)}}" target="_blank">
                <figure class="user-avatar">
                    <img src="{{get_user_profile_image_url($serviceUser)}}" class='img-fluid course_w-110 course_h-110 rounded-circle' alt="">
                    @if(time()-strtotime($serviceUser->last_login_at) <= 600 )
                    <div class="course-seller-online"></div>
                    @endif
                </figure>
            </a>
        </div>
        <div class="ml-md-3 text-center text-md-left">
            <a href="{{route('viewuserservices',$serviceUser->username)}}" target="_blank"><p class='font-18 text-color-2 font-weight-bold mb-1 mt-3 mt-md-0'>{{$serviceUser->username}}</p></a>
            <!-- <p class='font-16 text-secondary mb-1'>{{$serviceUser->description}}</p> -->
            <div class="d-flex align-items-center justify-content-center margin-none">
                {!! displayCourseUserRating($avg_seller_rating) !!}
                {{-- <span class="d-flex align-items-center">
                    <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid' alt="">
                    <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
                    <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
                    <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
                    <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
                </span> --}}
                <span class="text-secondary font-14 ml-3">Reviews  &nbsp; ({{$total_seller_rating}})</span>
            </div>
            {{-- BEGIN Check is admin --}}	
            @if(!isset($is_chat))
            @if(Auth::check() && $is_admin != true && $parent_uid != $serviceUser->id)
                <button class='btn bg-primary-blue text-white font-14 mt-3 px-4 py-2 open-new-message open_user_chat' data-user="{{$serviceUser->secret}}">Contact Author</button>
            @endif
            @if(!Auth::check())
                <a href="{{url('login')}}" class="btn bg-primary-blue text-white font-14 mt-3 px-4 py-2">Contact Author</a>
            @endif
            @endif
            {{-- END - Check is admin --}}	
        </div>
    </div>
    <div class='col-12 col-xl-5 d-flex flex-column flex-xl-row justify-content-between mt-4 mt-xl-0'>
        <section>
            <div class="d-flex align-items-center">
                <div class="course_w-20 text-center">
                    <i class="fas fa-calendar-alt text-color-7"></i>
                </div>
                <div class="font-14 greytext ml-2">Member since {{date('Y',strtotime($serviceUser->created_at))}}</div>
            </div>
            @if($serviceUser->country)
            <div class="mt-2 d-flex align-items-center">
                <div class="course_w-20 text-center">
                    <i class="fas fa-map-pin text-color-7"></i>
                </div>
                <div class="font-14 greytext ml-2">From {{$serviceUser->country->country_code}}</div>
            </div> 
            @endif
            @if($serviceUser->timezone != "")
            <div class="mt-2 d-flex align-items-center">
                <div class="course_w-20 text-center">
                    <i class="fas fa-clock text-color-7"></i>
                </div>
                @php
                $currentTime = \Carbon\Carbon::now()->timezone($serviceUser->timezone)
                @endphp
                <div class="font-14 greytext ml-2">{{$currentTime->format('H:i A')}} local time</div>
                <!-- (GMT-4) -->
            </div>  
            @endif
            <div class="mt-2 d-flex align-items-center">
                <div class="course_w-20 text-center">
                    <i class="fas fa-play text-color-7"></i>
                </div>
                <div class="font-14 greytext ml-2">{{$no_of_courses}} Courses</div>
            </div>  
        </section>
        <section>
            <div class="mt-2 mt-xl-0 d-flex align-items-center">
                <div class="course_w-20 text-center">
                    <i class="fas fa-star text-color-7"></i>
                </div>
                <span class="font-14 greytext ml-2">
                    @if(round($avg_seller_rating,1) >= 5)
                        5 rating
                    @else
                        {{ round($avg_seller_rating,1) }} rating
                    @endif 
                </span>
            </div> 
            <div class="mt-2 d-flex align-items-center">
                <div class="course_w-20 text-center">
                    <i class="fas fa-award text-color-7"></i>
                </div>
                <span class="font-14 greytext ml-2">{{$total_seller_rating}} reviews</span>
            </div>
            <div class="mt-2 d-flex align-items-center">
                <div class="course_w-20 text-center">
                    <i class="fas fa-users text-color-7"></i>   
                </div>
                <span class="font-14 greytext ml-2">{{thousandsCurrencyFormat($no_of_students)}} students</span>
            </div>
        </section>                               
    </div>
    <div class="col-12">
        @if(isset($is_read_more) && $is_read_more == false)
            <div>
                <p class="text-color-3 font-16 font-weight-bold">{{$serviceUser->description}}</p>
            </div>
        @else
            <div id="course_readmore-text-style-2">
                <p class="text-color-3 font-16 mt-3 font-weight-bold">{{display_content($serviceUser->description,80)}}</p>
                <p id="readmore-text1" class="text-color-3 font-16 font-weight-bold">{{$serviceUser->description}}</p>
            </div>
            <button class="btn text-color-2 border-dark readmore-button1">Read More <i  class="fas fa-chevron-down ml-2"></i></button>
            <button class="btn text-color-2 border-dark readmore-button1 hide">Read Less <i  class="fas fa-chevron-up ml-3"></i></button>
        @endif
    </div>
</div>