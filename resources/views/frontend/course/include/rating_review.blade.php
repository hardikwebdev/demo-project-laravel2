<div class="col-12">
    <div class="py-3 d-flex flex-column flex-md-row align-items-center">
        <div class="d-flex align-items-center">
            <span class="ml-3">
                {!! displayCourseUserRating($Service->service_rating) !!}
                {{-- <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid pr-1' alt="">
                <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid pr-1' alt="">
                <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid pr-1' alt="">
                <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid pr-1' alt="">
                <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid pr-1' alt=""> --}}
            </span>
            <h2 class="text-warning font-28 font-weight-bold m-0 pl-3">{{number_format($Service->service_rating,1, '.', '')}}</h2>
        </div>
        <h2 class="text-color-2 font-28 font-weight-bold m-0 pl-md-5">{{$Service->total_review_count}} Reviews</h2>
    </div>
    {{-- Review Table --}}
    <table class="table ratting-table course_cus-w-60">
        <tbody>
            @for ($i = 5; $i >= 1; $i--)
                <tr>
                    <td class="cus-white-space-nowrap">
                        <a href="javascript:void(0)" class="get_review_by_count_link rating_hover_{{$i}}" data-ratting_count="{{$i}}" data-url="{{route('course.get_all_review').'?seo_url='.$seo_url.'&rating_count='.$i}}">
                            <span class="font-16 text-color-1 font-weight-bold">{{$i}} star</span>
                        </a>
                    </td> 
                    <td class="middle">
                        <a href="javascript:void(0)" class="get_review_by_count_link" data-ratting_count="{{$i}}" data-url="{{route('course.get_all_review').'?seo_url='.$seo_url.'&rating_count='.$i}}">
                            <div class="progress rating_middle_hover_{{$i}}">
                                <div class="progress-bar bg-warning" style="width: {{review_in_percentage($Service->id,$i,$Service->total_review_count)}}%;" role="progressbar"></div>
                            </div>
                        </a>
                    </td>
                    <td class="cus-white-space-nowrap text-right pb-2">
                        <a href="javascript:void(0)" class="get_review_by_count_link rating_hover_{{$i}}" data-ratting_count="{{$i}}" data-url="{{route('course.get_all_review').'?seo_url='.$seo_url.'&rating_count='.$i}}">
                            <span class="font-16 text-color-1">({{review_in_percentage($Service->id,$i,$Service->total_review_count,1)}})</span>
                        </a>
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>

{{-- Filter --}}
<div class="col-12">
    <div class="row">
		<div class="col-md-4" style="padding-top: 10px;">
			<h6>All Reviews</h6>
		</div>
		<div class="col-md-8 review_filter" style="padding-bottom: 10px;">
			<select class="form-control col-md-3" id="review_rating" name="rating" style="float: right;">
				<option value="all" @if(@$_GET['rating']=='all' ) selected @endif data-url="{{route('course.get_all_review').'?seo_url='.$seo_url.'&rating=all'}}">All</option>
				<option value="best" @if(@$_GET['rating']=='best' ) selected @endif data-url="{{route('course.get_all_review').'?seo_url='.$seo_url.'&rating=best'}}">Best</option>
				<option value="worst" @if(@$_GET['rating']=='worst' ) selected @endif data-url="{{route('course.get_all_review').'?seo_url='.$seo_url.'&rating=worst'}}">Worst</option>
				<option value="newest" @if(@$_GET['rating']=='newest' ) selected @endif data-url="{{route('course.get_all_review').'?seo_url='.$seo_url.'&rating=newest'}}">Newest</option>
				<option value="oldest" @if(@$_GET['rating']=='oldest' ) selected @endif data-url="{{route('course.get_all_review').'?seo_url='.$seo_url.'&rating=oldest'}}">Oldest</option>
			</select>
		</div>
	</div>
	@if($Service->reuse_denied_status == 1 && $Service->total_review_count > 0)
        <div class="row">
            <div class="col-md-12">
                <small><b><i>*This course has been updated. Some reviews may reference courses no longer offered.</i></b></small>
            </div>
        </div>
	@endif
</div>
{{-- END Filter --}}

{{-- Review Section --}}
@if(count($Comment) > 0)
    @foreach($Comment as $rowComment)
    <div class="col-12">
        <div class='row mt-4'>
            <div class='col-auto'>
                <img src="{{get_user_profile_image_url($rowComment->user)}}" class='w-60 rounded-circle' alt="">
            </div>
            <div class="col-auto col-md-5 col-xl-10 d-flex justify-content-start">
                <div @if(strlen($rowComment->user->username) < 6) class="mr-md-5" @endif>
                    <h2 class='font-18 text-color-2 font-weight-bold m-0'>{{$rowComment->user->username}}</h2>
                    
                    {{-- @if(isset($rowComment->user->country))
                    <div class="d-flex align-items-center pt-2">
                        <img src="{{url('public/frontend/images/usa.png')}}" class='img-fluid' alt="">
                        <p class="text-color-4 font-16 m-0 pl-2">{{$rowComment->user->country->country_code}}</p>
                    </div>
                    @endif --}}
                </div>
                <div>
                    <div class="d-flex align-items-center justify-content-center">
                        <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid' alt="">
                        <h2 class="text-warning font-18 font-weight-bold m-0 pl-2">{{$rowComment->seller_rating}}</h2>
                    </div>
                    <p class="text-color-4 font-16 pt-2">
                        @if($rowComment->status == 'completed')
                            {{date('d M,Y h:i',strtotime($rowComment->review_date))}}
                        @else
                            {{date('d M,Y h:i',strtotime($rowComment->cancel_date))}}
                        @endif
                    </p>
                </div>
            </div>
            <div class="col-12">
                {{-- Review Note --}}
                <p class="text-color-4 font-16 pt-2">{!! $rowComment->completed_note !!}</p>
            </div>

            {{-- Review Reply --}}
            @if($rowComment->completed_reply)
            <div class="col-11 ml-auto">
                <div class="d-flex align-items-start pt-2">
                    <img src="{{get_user_profile_image_url($rowComment->seller)}}" class='img-fluid rounded-circle course_w-40 course_h-40' alt="">   
                    <div class="ml-3">
                        <h2 class='font-18 text-color-2 font-weight-bold mt-2'>{{ $rowComment->seller->username }}</h2>
                        <p class="text-color-4 font-16">{!! $rowComment->completed_reply !!}</p>
                    </div>
                </div>
            </div>
            @endif
            {{-- END Review Reply --}}
        </div>

        <hr/>

    </div>
    @endforeach

<div class="paginate-center">
    @if(count($Comment))
    {{ $Comment->appends(['seo_url' => $seo_url,
        'username' => $username])->links("pagination::bootstrap-4") }}
    @endif
</div>
@else
    <div class="col-12 empty_review_div text-center">
        <h6 class="py-3">No reviews yet</h6>
    </div>
@endif
{{-- END - Review Section --}}
