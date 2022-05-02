@php
$objOrder = new App\Order;

use App\User;
$UserDetail= new App\User;
$dataUser=null;
if(Auth::check())
{
	$parent_data=Auth::user()->parent_id;
	$dataUser=User::select('id','affiliate_id')->where('id',$parent_data)->first();
}

@endphp

@extends('layouts.frontend.main')
@section('pageTitle', ucwords($Service->title).' | demo')
@section('metaTags')
<meta name="title" content="{{($Service->meta_title)?$Service->meta_title:$Service->title}}">
<meta name="keywords" content="{{$Service->meta_keywords}}">
<meta name="description" content="{{strip_tags(($Service->meta_description)?$Service->meta_description:$Service->descriptions)}}">

@if($Service!= null && $Service->is_private == 1)
	<meta name="robots" content="noindex">
@endif

@endsection

@php 
$og_image = '';
if(count($Service->fbimages) > 0) {
	$og_image = $Service->fbimages[0]->media_url;
} else {
	if(count($Service->images)) {
		if(!is_null($Service->images[0]->thumbnail_media_url)) {
			$og_image = $Service->images[0]->thumbnail_media_url; 
		} else if($Service->images[0]->photo_s3_key != '') {
			$og_image = $Service->images[0]->media_url;
		} else {
			$og_image = url('public/services/images/'.$Service->images[0]->media_url);
		}
	}
}
@endphp

@section('og_app_id', '298062924465542')
@section('og_url', URL::current())
@section('og_title', ($Service->meta_title)?$Service->meta_title:$Service->title)
@section('og_type', 'website')
@section('og_description', strip_tags(($Service->meta_description)?$Service->meta_description:$Service->descriptions))

@if(strlen($og_image) > 0) 
@section('og_image', $og_image)
@endif

@php
$cate_name = "";
$cate_name .= $Service->category->category_name ?? "";
$cate_name .= ' > ';
$cate_name .= $Service->subcategory->subcategory_name ?? "";

$og_basic_price = $Service->lifetime_plans->price ?? 0.0;

@endphp
@section('og_product_category', $cate_name)
@section('og_product_brand', "demo")
@section('og_product_availibility', "in stock")
@section('og_product_price_amount', $og_basic_price)
@section('og_product_price_currency', "USD")
@section('og_product_catalog_id', $Service->seo_url)

@section('content')
<!-- Display Error Message -->
@include('layouts.frontend.messages')

<!-- Masthead -->
<input type="hidden" id="ratting_count_id" value="0">
<header class="masthead text-white"> {{-- masthead  --}}
	<div class="overlay"></div>
    <div class="course_bg-course-banner">
        <div class="container py-5 font-lato">
            <div class="row">
                <div class="col-12 col-lg-8">
                    <ul class="cus-breadcrumb">
                        <li>
                            <a class="text-color-4 font-14" href="{{url('/')}}">Home</a>
                        </li>
                        <li>
                            <a class="text-color-4 font-14" href="javascript::void(0)">Courses</a>
                        </li>
                        <li>
							<a href="javascript::void(0)">{{$Service->category->category_name}}</a>
                        </li>
                    </ul>
                    <h2 class="text-white font-28 font-weight-bold pt-3 text-capitalize">{{$Service->title}}</h2>
                    <div class="pt-3 d-flex align-items-center">

                        <a href="{{route('viewuserservices',$serviceUser->username)}}" target="_blank">
                            <figure class="user-avatar mb-0">
                                <img src="{{get_user_profile_image_url($serviceUser)}}" class='img-fluid rounded-circle course_w-40 course_h-40' alt="profile-image">
                                @if(time()-strtotime($serviceUser->last_login_at) <= 600 )
                                <div class="seller-online"></div>
                                @endif
                            </figure>
                        </a>
						<!-- <img src="{{get_user_profile_image_url($serviceUser)}}" class='img-fluid rounded-circle course_w-40 course_h-40' alt="profile-image"> -->
						
                        <span @if($Service->total_review_count > 0) class="ml-3 d-flex align-items-center justify-content-center show_rating_popup" @else class="ml-3 d-flex align-items-center justify-content-center"  @endif data-toggle="popover" data-placement="bottom">
							{!! displayCourseUserRating($Service->service_rating) !!}
							<i class="ml-1 fas fa-angle-down text-secondary"></i>
						</span>
						
						<!-- <span class="ml-3 d-flex align-items-center justify-content-center">
                            <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid' alt="">
                            <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
                            <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
                            <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
                            <img src="{{url('public/frontend/images/Vector.png')}}" class='img-fluid ml-1' alt="">
                        </span> -->
                        <span class="text-color-4 font-14 ml-3 mt-1">Reviews  &nbsp; ({{$Service->total_review_count}})</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="product-detail-header pb-5">
	<div class="container font-lato">
		<div class="row">
			<div class="col-12 col-lg-8">
				<ul class="nav nav-tabs bg-white course_sticky-position" id="myTab" role="tablist">
					<li class="nav-item">
						<a class="nav-link pb-3 active pl-0" data-toggle="tab" id="descriptionbtn" href="#description">Description</a>
					</li>
					@if($Service->questions)
					<li class="nav-item">
						<a class="nav-link pb-3" data-toggle="tab" id="requirementsbtn" href="#requirements">Requirements</a>
					</li>
					@endif

                    @if(isset($Service->course_detail) && $Service->course_detail->what_you_learn)
					<li class="nav-item">
						<a class="nav-link pb-3" data-toggle="tab" id="whatyoulearnbtn" href="#whatyoulearn">What you’ll learn</a>
					</li>
                    @endif

                    <li class="nav-item">
						<a class="nav-link pb-3" data-toggle="tab" id="contentbtn" href="#content">Content</a>
					</li>
                    <li class="nav-item">
						<a class="nav-link pb-3" data-toggle="tab" id="authorbtn" href="#author">Author</a>
					</li>
                    <li class="nav-item">
						<a class="nav-link pb-3" data-toggle="tab" id="reviewsbtn" href="#reviews">Reviews</a>
					</li>
                    
                    {{-- <li class="nav-item ml-3 mt-1">
                        <span class="text-color-4 font-14 border border-radius-15px px-3 py-1 d-flex align-items-center">
                            <i class="far fa-heart text-color-2 pr-2"></i>
                            283
                        </span>
                    </li> --}}
				</ul>

                <div class="row" id='description'>
                    <div class="col-12">
                        <div class="py-3">
                            <h2 class="text-color-2 font-28 font-weight-bold pb-3">Description</h2>
                            <div class="ck-custom-content"> {!! $Service->descriptions !!} </div>
                        </div>
                    </div>
                </div>
                <hr/>

				@if($Service->questions)
                <div class="row" id="requirements">
                    <div class="col-12">
                        <div class="py-3">
                            <h2 class="text-color-2 font-28 font-weight-bold pb-3">Requirements</h2>
							<div class="ck-custom-content"> {!! $Service->questions !!} </div>
                        </div>
                    </div>
                </div>
                <hr/>
				@endif

                @if(isset($Service->course_detail) && trim($Service->course_detail->what_you_learn))
                <div class="row" id="whatyoulearn">
                    <div class="col-12">
                        <div class="py-3">
                            <h2 class="text-color-2 font-28 font-weight-bold pb-3">What you'll learn</h2>
                        </div>
                    </div>
                    @php
                    $whats_learn = explode(",",$Service->course_detail->what_you_learn);
                    @endphp

                    @foreach($whats_learn  as $learn)
                    <div class="d-flex align-items-center col-lg-6 mb-3">
                        <div>
                            <div class="border rounded-circle course_what-you-learn-tikmark d-flex justify-content-center align-items-center">
                                <i class="fas fa-check text-color-1"></i>
                            </div>
                        </div>
                        <p class="font-16 text-color-2 font-weight-bold m-0 pl-3">{!! $learn !!}</p>
                    </div>
                    @endforeach
                </div>
                <hr/>
                @endif

                <div class="row" id="content">
                    <div class="col-12">
                        <div class="py-3">
                            <h2 class="text-color-2 font-28 font-weight-bold pb-3">Course Content</h2>
                        </div>
                        @if(count($Service->course_sections))
                        <div id="accordion">
                            @foreach($Service->course_sections as $sectionKey => $section)
                            <div class="card card-bark-mode border-0">

                                <!-- Begin : course section heading -->
                                <div class="p-3 bg-light-gray-f0 border rounded-top" id="sectionHeading{{$sectionKey}}">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h5 class="mb-0">
                                            <button type="button" class="btn font-18 text-color-2 text-left d-inline-flex bg-transparent font-weight-bold arrow-down-btn shadow-none" data-toggle="collapse" data-target="#sectionCollapse{{$sectionKey}}" aria-expanded="{{ ($sectionKey==0)?'true':'false' }}" aria-controls="collapseOne">
                                                <i class="fas fa-chevron-down arrow-down font-12 d-table-cell align-self-center"></i>
                                                <span class="ml-3 d-table-cell">{{$section->name}}</span>
                                            </button>
                                            {{-- Update information --}}
                                            @if($is_admin == true && $section->is_approve == 0 || $is_admin == true && $section->is_draft == 1)
                                                @php
                                                    $color_class = "text-color-1"; 
                                                    $icon_color_class = "fa-info-circle"; 
                                                    $title = "New"; 
                                                    if($section->is_draft == 1){
                                                        $icon_color_class = "fa-danger-circle"; 
                                                        $title = "Deleted"; 
                                                        $color_class = "text-danger"; 
                                                    }
                                                @endphp
                                                <b class="{{$color_class}} font-12">
                                                    <i class="fas fa-info-circle"></i> {{$title}}
                                                </b>
                                            @endif
                                        </h5>
                                        <div>
                                            <p class="text-color-2 font-14 m-0">{{ get_duration_heading($section->content_medias->sum('media_time')) }}</p>
                                        </div>
                                    </div>
                                </div> 
                                <!-- End : course section heading -->

                                <!-- Begin : course contents -->
                                
                                <div id="sectionCollapse{{$sectionKey}}" class="collapse {{ ($sectionKey==0)?'show':'' }} border-left border-right" aria-labelledby="sectionHeading{{$sectionKey}}" data-parent="#accordion">
                                    @if(count($section->content_medias)) 
                                    @foreach($section->content_medias as $media)
                                    <div class="d-flex justify-content-between align-items-center border-bottom p-3">
                                        <div class="d-flex align-items-center">
                                            @if($media->media_type == 'video')
                                            <img src="{{url('public/frontend/images/play-icon.png')}}" class='img-fluid pr-1' alt="Video">
                                            @else
                                            <img src="{{url('public/frontend/images/File.png')}}" class='img-fluid pr-1' alt="Document">
                                            @endif
                                            <p class="text-color-2 font-16 m-0 pl-3">{{$media->name}} 
                                                @if($is_admin == true && $media->is_approve == 0 || $is_admin == true && $media->is_draft == 1)
                                                    @php
                                                        $color_class = "text-color-1"; 
                                                        $title = "New"; 
                                                        if($section->is_draft == 1){
                                                            $title = "Deleted"; 
                                                            $color_class = "text-danger"; 
                                                        }
                                                    @endphp
                                                    <b class="{{$color_class}} font-12">
                                                        <i class="fas fa-info-circle"></i> {{$title}}
                                                    </b>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            @if($media->is_preview == 1 || $is_admin == true)
                                                <a class="text-color-1 btn-link font-12 m-0 mr-3 course-article-preview" @if(isset($token) && $token != "") data-token="{{$token}}" @endif data-url="{!! route('get_preview_content') !!}" data-id="{{$media->secret}}" href="Javascript:;">Preview</a>
                                            @endif
                                            <p class="text-color-1 font-12 m-0 text-nowrap">{{get_duration($media->media_time)}}</p>
                                        </div>
                                    </div>
                                    @endforeach
                                    @else
                                    <div class="text-center my-2">No contents available</div>
                                    @endif
                                </div>
                               
                                <!-- End : course contents -->

                            </div>
                            @endforeach
                        </div>
                        @else
                        <h6>No sections available</h6>
                        @endif
                    </div>
                </div>

                {{-- begin : RIGHT SIDEBAR  --}}
                @if($is_mobile_device == true)
                <div class="row mt-4">
                    <div class="col-12">
                        @include('frontend.course.include.details_right_sidebar')
                    </div>
                </div>
                @endif
                {{-- end : RIGHT SIDEBAR --}}

                <hr/>
                {{-- BEGIN - Auther Section --}}
                @include('frontend.course.include.auther_section')
                {{-- END - Auther Section --}}
                <hr/>
                {{-- begin; Review Section --}}
                <div class="row ajax-pagination-div" id="reviews">
                    @include('frontend.course.include.rating_review')
                </div>
                {{-- end : Review Section --}}

			</div>
            {{-- begin : RIGHT SIDEBAR  --}}
            @if($is_mobile_device == false)
            <div class="col-12 col-lg-4">
            @include('frontend.course.include.details_right_sidebar')
            </div>
            @endif
            {{-- end : RIGHT SIDEBAR --}}
		</div>    
	</div>  
</section>

<!-- Dispute order Modal -->
<div class="modal fade" id="disputeorderpopup" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content border-radius-15px">
			<div class="modal-header modal-header-border-none border-0">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body pt-0 border-0 px-3 px-md-5 dispute-body">
				
			</div>
		</div>
	</div>
</div>

@if(count($other_related_courses) > 0)
<section class="w-100 bg-dark-white pb-5">
    <div class="container font-lato">
        <div class="row">
            <div class="col-12">
                <div class="py-3">
                    <h2 class="text-color-2 font-28 font-weight-bold mt-3">Other courses you may like</h2>
                </div>
            </div>
		    <div class="col-12"> 
                <div class="owl-carousel slick_service_box popular-grid-three slick-slider">
                    @foreach($other_related_courses as $serviceBox)
                    @include('frontend.course.include.single-item');
                    @endforeach
                </div>  
            </div>
        </div>
    </div>
</section>
@endif

<!-- Begin : Show Rating model -->
<div id="popover_content_wrapper" class="hide cus-ratting-box">
	<div class="ratting-content">
		{!! displayCourseUserRating($Service->service_rating) !!}
		<span class="fs-18"><b>{{number_format($Service->service_rating,1, '.', '')}} out of 5</b></span>
	</div>
	<div> 
		<span class="pl-3 cus-grey">{{$Service->total_review_count}} global ratings</span>
	</div>
	<table class="table ratting-table">
		<tbody>
		@for ($i = 5; $i >= 1; $i--)
			<tr >
				<td class="cus-white-space-nowrap">
					<a href="javascript:void(0)" class="get_review_by_count_link rating_hover_{{$i}}" data-ratting_count="{{$i}}" data-url="{{route('getallreview').'?seo_url='.$seo_url.'&username='.$username.'&id='.$Service->id.'&rating_count='.$i}}">
						<span class="cus-label-star">{{$i}} star</span>
					</a>
				</td> 
				<td class="middle">
					<a href="javascript:void(0)" class="get_review_by_count_link" data-ratting_count="{{$i}}" data-url="{{route('getallreview').'?seo_url='.$seo_url.'&username='.$username.'&id='.$Service->id.'&rating_count='.$i}}">
						<div class="progress rating_middle_hover_{{$i}}">
							<div class="progress-bar progress_color" style="width: {{review_in_percentage($Service->id,$i,$Service->total_review_count)}}%" role="progressbar"></div>
						</div>
					</a>
				</td>
				<td class="cus-white-space-nowrap">
					<a href="javascript:void(0)" class="get_review_by_count_link rating_hover_{{$i}}" data-ratting_count="{{$i}}" data-url="{{route('getallreview').'?seo_url='.$seo_url.'&username='.$username.'&id='.$Service->id.'&rating_count='.$i}}">
						<span class="cus-label-star-count">{{review_in_percentage($Service->id,$i,$Service->total_review_count)}}%</span>
					</a>
				</td>
			</tr>
		@endfor
		</tbody>
	</table>
	<hr>
	<div class="text-center  pb-2"> 
		<a href="#reviews" classs="scroll_bottom ">See all reviews</a>
	</div>
</div>
<!-- End : Show Rating model -->

@endsection

@section('scripts')
<script>
    $(document).ready(function(){
        $(".show_rating_popup").popover({ trigger: "manual" , html: true, animation:false,content: function () {
            return $('#popover_content_wrapper').html();
        }})
        .on("mouseenter", function () {
            var _this = this;
            $(this).popover("show");
            review_hover_fn();
            stop_to_open_context_menu();
            $(".popover").on("mouseleave", function () {
                $(_this).popover('hide');
            });
        }).on("mouseleave", function () {
            var _this = this;
            setTimeout(function () {
                if (!$(".popover:hover").length) {
                    $(_this).popover("hide");
                }
            }, 300);
		});

		url_params = getUrlVars();
		if( url_params.utm_source !== undefined && url_params.utm_term !== undefined && url_params.utm_source != 0 && url_params.utm_term  != 0) {
			$('.utm_source').val(url_params.utm_source);
			$('.utm_term').val(url_params.utm_term);
			$('.cookie-cart-save').attr('data-utm_source',url_params.utm_source);
			$('.cookie-cart-save').attr('data-utm_term',url_params.utm_term);
			$('#utm_source_for_customorder').val(url_params.utm_source);
			$('#utm_term_for_customorder').val(url_params.utm_term);
		}

		if(url_params !== undefined && url_params['review-edition'] != '1') {
			clean_url();
		}

		$("#readmore-text1").slideUp();
        $('#course_readmore-text-style-2').addClass('course_readmore-text-style-2');
        
        $(".readmore-button1").click(function(){
            $("#readmore-text1").slideToggle();
            $('#course_readmore-text-style-2').toggleClass('course_readmore-text-style-2');
            $('.readmore-button1').not(this).removeClass('hide');
            $(this).toggleClass('hide');
        });

        $("#readmore-text2").slideUp();
        $('#course_readmore-text-style-1').addClass('course_readmore-text-style-2');
        
        $(".readmore-button2").click(function(){
            $("#readmore-text2").slideToggle();
            $('#course_readmore-text-style-1').toggleClass('course_readmore-text-style-2');
            $('.readmore-button2').not(this).removeClass('hide');
            $(this).toggleClass('hide');
        });

        $("#descriptionbtn").click(function() {
            $('html, body').animate({
                scrollTop: $("#description").offset().top - 150
            }, 1000);
        });
        $("#requirementsbtn").click(function() {
            $('html, body').animate({
                scrollTop: $("#requirements").offset().top - 150
            }, 1000);
        });
        $("#whatyoulearnbtn").click(function() {
            $('html, body').animate({
                scrollTop: $("#whatyoulearn").offset().top - 150
            }, 1000);
        });
        $("#contentbtn").click(function() {
            $('html, body').animate({
                scrollTop: $("#content").offset().top - 150
            }, 1000);
        });
        $("#authorbtn").click(function() {
            $('html, body').animate({
                scrollTop: $("#author").offset().top - 150
            }, 1000);
        });
        $("#reviewsbtn").click(function() {
            $('html, body').animate({
                scrollTop: $("#reviews").offset().top - 150
            }, 1000);
        });

        /*Pagination through jquery load method*/
		$('body').on('click', '.ajax-pagination-div .pagination a', function (e) {
			e.preventDefault();

			var rating_count = $('#ratting_count_id').val();
			var url = $(this).attr('href');
			var rating = $('#review_rating').val();
			console.log(url);

			$.ajax({
				url : url + '&rating='+rating+'&rating_count='+rating_count,
				type : "get",
				success : function(data){
					$('.ajax-pagination-div').html(data);
				}
			});
			/*$('.ajax-pagination-div').load(url + '&id=' + id);return false;*/
		});
        /* END Pagination */

        $(document).on('click','.button-dispute',function(){
            $.ajax({
                type : 'post',
                url : "{{route('get_reasons')}}",
                data :  {'_token': "{{csrf_token()}}",
                'order_id' : $(this).attr('data-id'),
                'user_type' : 'buyer'
                },
                success : function(data){
                    $('.dispute-body').html(data.view);
                }
            })
        });

        $(document).on('click', '.cancel-dispute', function(e){
            var url = $(this).attr('data-url');
            $this = $(this);
            bootbox.confirm("Are you sure you want to cancel this dispute? Please note that you won’t be able to re-open it, or file another dispute for this order.", function(result){
                if(result == true){
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: {"_token": _token},
                        success: function (data){
                            window.location.reload();
                        }
                    });
                }
            });
        });

        $(document).on('click', '.complete-monthly-order', function(e){
            var url = $(this).attr('data-url');
            $this = $(this);
            bootbox.confirm("Are you sure you want to complete this monthly subscription?", function(result){
                if(result == true){
                    $.ajax({
                        type: "POST",
                        url: url,
                        data: {"_token": _token},
                        success: function (data){
                            window.location.reload();
                        }
                    });
                }
            });
        });

        
        /* Preview Artiacle JS */
		$(document).on('click','.course-article-preview',function(){
			var url = $(this).data('url');
			var id = $(this).data('id');
			var token = "";
			if($(this).data('token') != undefined){
				token = $(this).data('token');
			}

			$.ajax({
				url: url,
				method: "POST",
				data: {'_token': _token, id:id,token:token},
				dataType: "json",
				success: function(data){
					if(data.status == true){
						if(data.type == 'video'){
							var player_id = "video-player-preview";
							load_jwplayer(player_id, data.link, data.name);
							$('#preview_course_video_modal').modal('show');
						}else{
							$('#course-preview-title').html(data.name);
							$("#load-course-article").html(data.article_text).show();
                            hljs.highlightAll();
							$('#preview-course-article').modal('show');
						}
					}else{
						alert_error('Something went wrong.');
					}
				},
				error: function(){
					alert_error('Something went wrong.');
				}
			});
		});
        
        /* Preview Course JS */
        $(document).on('click','.preview-course-video-btn',function(){
            var video_link = $(this).data('url');
            var title = $(this).data('title');
            var player_id = "video-player-preview";
            load_jwplayer(player_id, video_link, title);
            $('#preview_course_video_modal').modal('show');

        });

        function load_jwplayer(player_id, video_link, title){
            jwplayer(player_id).setup({
                "file": video_link,
                title: title,
                autostart: true,
            });
        }
     });
</script>
@endsection