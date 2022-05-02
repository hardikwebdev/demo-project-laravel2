@extends('layouts.frontend.main')
@section('pageTitle', 'Course Details | demo')
@section('content')
<!-- Display Error Message -->
@include('layouts.frontend.messages')
<!-- Masthead -->
<section class="product-detail-header pb-5">
    <input type="hidden" id="course_secret" value="{{$course->secret}}">
    <input type="hidden" id="ratting_count_id" value="0">
	<div class="container-fluid font-lato right-sidebar-toggle">
       
		<div class="row" id="course-content">
			<div class="col-12 col-lg-8 {{($is_mobile_device != true)? 'pr-0 pl-0' : ''}} couser-right-full">
                {{-- Preview Section --}}
                <div class="course-video-embed border-botton">
                    {{-- Next & Previous Button --}}
                    <a href="javascript:;" class="previous-content-btn-arrow d-none" >
                        <i class="fas fa-arrow-left"></i><span class="pl-1">Prev</span>
                    </a>
                    <a href="javascript:;" class="next-content-btn-arrow d-none">
                        <span class="pr-1">Next</span><i class="fas fa-arrow-right"></i>
                    </a>
                    {{-- Side section hide button --}}
                    <a href="javascript:;" class="course-content-show-arrow" >
                        <i class="fas fa-arrow-left"></i>
                        <span aria-label="" class="course-details-span">Course content</span>
                    </a>
                    {{-- Media Player Preview Section --}}
                    <div class="course-video-player-embed @if($active_content_media->media_type != "video") d-none @endif">
                        <div id="course-videos-player"></div>
                    </div>
                    @if($is_mobile_device)
                        @php
                            if($course->images[0]->photo_s3_key != ''){
                                $course_img_url = $course->images[0]->thumbnail_media_url;
                            }else{
                                $course_img_url = $course->images[0]->media_url;
                            }
                        @endphp
                        <div class="course-article-view @if($active_content_media->media_type == "video") d-none @endif">
                            <img src="{{$course_img_url}}" class="mobile-curriculum-item-img">
                            <div class="mobile-curriculum-item-container view-aricle-screen">
                            <div class="fullscreen-toggle-view-mob">
                                    <p class="open-content-name font-18">
                                        {{ ($active_content_media->media_type != "video")? $active_content_media->name : '' }}
                                    </p>
                                    <button class="btn bg-primary-blue text-white font-13 py-2 px-3 rounded-0" id="fullscreen-toggle-btn-mob">
                                        Open
                                    </button>
                                </div>
                                <div class="course-article-embed-mob text-color-6">
                                    <div class="load-course-article-content"> 
                                        {!! ($active_content_media->media_type != "video")? $active_content_media->article_text : '' !!}
                                    </div>
                                    <button class="btn btn-secondary" id="fullscreen-toggle-btn">
                                        <i class="fa fa-compress" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="course-article-view course-article-embed text-color-6 view-aricle-screen @if($active_content_media->media_type == "video") d-none @endif">
                            <div class="load-course-article-content"> {!! $active_content_media->article_text !!} </div>
                            <button class="btn btn-secondary" id="fullscreen-toggle-btn">
                                <i class="fa fa-expand" aria-hidden="true"></i>
                            </button>
                        </div>
                    @endif
                    <div class="overview-loader">
                        <div class="overview-html-loader"></div>
                    </div>
                </div>
                {{-- Content Menu --}}
                <div class="couser-right-full-width-80 border-top">
                    <ul class="nav nav-tabs bg-white mt-0 border-bottom course_sticky-position course_sticky-position_tab {{($is_mobile_device == true)? ' course-slide-menu ' : ''}}" id="myTab" role="tablist">
                        <li class="nav-item mob-course-show">
                            <a class="nav-link cust-nav-item active scrollbtn" data-scroll="course-content" data-toggle="tab" id="course-contentbtn" href="#course-content">Course content</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link cust-nav-item px-0 scrollbtn" data-scroll="overview" data-toggle="tab" id="overviewbtn" href="#overview">Overview</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link cust-nav-item scrollbtn" data-scroll="requirements" data-toggle="tab" id="requirementsbtn" href="#requirements">Requirements</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link cust-nav-item scrollbtn" data-scroll="reviews" data-toggle="tab" id="reviewsbtn" href="#reviews">Reviews</a>
                        </li>
                        {{-- <li class="nav-item ml-3 mt-1 d-none">
                            <span class="text-color-4 font-14 border border-radius-15px px-3 py-1 d-flex align-items-center">
                                <i class="far fa-heart text-color-2 pr-2"></i>
                                0
                            </span>
                        </li> --}}
                    </ul>
                    <div class="popular-tab-item">
                        {{-- BEGIN - Content List --}}
                        @if($is_mobile_device)
                            @include('frontend.buyer.include.course_botton_sidebar')
                        @endif
                        {{-- END - Content List --}}
                        <div class="pl-3 pr-3" id="myTabContent">
                            <div class=" ctab-pane cfade active show overflow-hidden" id='overview' role="tabpanel" aria-labelledby="overview-tab">
                                <div class="read-more-faded">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="py-3">
                                                <h2 class="text-color-2 font-28 font-weight-bold pb-3">Description</h2>
                                                <div class="ck-custom-content"> {!! $course->descriptions !!} </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Auther section --}}
                                    @php 
                                    $serviceUser = $course_user;
                                    $is_read_more = false;
                                    $is_chat = false;
                                    @endphp
                                    @include('frontend.course.include.auther_section')
                                    {{-- END - Auther section --}}
                                    
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="py-3">
                                                <h2 class="text-color-2 font-28 font-weight-bold pb-3">What you'll learn</h2>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <div class="d-flex align-items-center">
                                                <div class="border rounded-circle course_what-you-learn-tikmark d-flex justify-content-center align-items-center">
                                                    <i class="fas fa-check text-color-1"></i>
                                                </div>
                                                <p class="font-16 text-color-2 font-weight-bold m-0 pl-3">Grow a Business Online From Scratch</p>
                                            </div>
                                            <div class="d-flex jalign-items-center mt-4">
                                                <div class="border rounded-circle course_what-you-learn-tikmark d-flex justify-content-center align-items-center">
                                                    <i class="fas fa-check text-color-1"></i>
                                                </div>
                                                <p class="font-16 text-color-2 font-weight-bold m-0 pl-3">Get Hired as a Digital Marketing Expert</p>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-6">
                                            <div class="d-flex align-items-center mt-4 mt-lg-0">
                                                <div class="border rounded-circle course_what-you-learn-tikmark d-flex justify-content-center align-items-center">
                                                    <i class="fas fa-check text-color-1"></i>
                                                </div>
                                                <p class="font-16 text-color-2 font-weight-bold m-0 pl-3">Make Money as an Affiliate Marketer</p>
                                            </div>
                                            <div class="d-flex align-items-center mt-4">
                                                <div class="border rounded-circle course_what-you-learn-tikmark d-flex justify-content-center align-items-center">
                                                    <i class="fas fa-check text-color-1"></i>
                                                </div>
                                                <p class="font-16 text-color-2 font-weight-bold m-0 pl-3">Work From Home as a Freelance Marketer</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="courseread-more-btn mt-3">
                                    <button id="read-more-detail" class="btn text-color-2 border-dark shadow-none">
                                        <div class="read-up-more">
                                            Read More <i class="fas fa-chevron-down pl-2 mt-1"></i>
                                        </div>
                                        <div class="read-down-more">
                                            Read Less <i class="fas fa-chevron-up pl-2 mt-1"></i>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div class=" ctab-pane cfade"  role="tabpanel" aria-labelledby="requirements-tab" id="requirements">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="py-3">
                                            <h2 class="text-color-2 font-28 font-weight-bold pb-3">Requirements</h2>
                                            <div class="ck-custom-content"> {!! $course->questions !!} </div>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div class=" ctab-pane cfade"  role="tabpanel" aria-labelledby="reviews-tab"  id="reviews">
                                <h2 class="text-color-2 font-28 font-weight-bold pb-3">Reviews</h2>
                                {{-- begin; Review Section --}}
                                <div class="row ajax-pagination-div" id="reviews">
                                    @php 
                                    $Service = $course;
                                    $seo_url = $course->seo_url;
                                    @endphp
                                    @include('frontend.course.include.rating_review')
                                </div>
                                {{-- end : Review Section --}}
                            </div>
                        </div>
                    </div>
                </div>
                @if(count($other_related_courses) > 0)
                <section class="w-100 bg-dark-white px-md-5 pb-5">
                    <div class="container font-lato">
                        <div class="row">
                            <div class="col-12">
                                <div class="py-3">
                                    <h2 class="text-color-2 font-28 font-weight-bold mt-3">Other courses you may like</h2>
                                </div>
                            </div>
                            <div class="col-12"> 
                                <div class="owl-carousel slick_service_box other-course-likes slick-slider">
                                    @foreach($other_related_courses as $serviceBox)
                                    @include('frontend.course.include.single-item')
                                    @endforeach
                                </div>  
                            </div>
                        </div>
                    </div>
                </section>
                @endif
			</div>
            {{-- Section Right sidebar --}}
            <div class="col-12 col-lg-4 pl-0 pr-0 couser-right-hide">
                @if($is_mobile_device != true)
                    @include('frontend.buyer.include.course_right_sidebar')
                @endif
            </div>
		</div>    
	</div>  
</section>

<div id="give-rating-reviwe-model" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content border-radius-15px">
			<div class="modal-header modal-header-border-none border-0">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body pt-0 border-0 px-3 px-md-5 new-seller-review">
				{{ Form::open(['route' => ['course.give.rating.review',$order->secret], 'method' => 'POST','class'=>'','id'=>'frmCoursReviewrating']) }}
				<h3 class="font-weight-bold font-20 text-color-6 text-center">Give rating & review</h3>
				<p class="font-16 text-color-2 mt-4 mb-0">Please rate your experience</p>
				<div class="star-ratings">
					<div class="stars stars-example-fontawesome-o">
						<select id="seller_rating" name="seller_rating" data-current-rating="0" autocomplete="off">
							<option value=""></option>
							<option value="1">1</option>
							<option value="2">2</option>
							<option value="3">3</option>
							<option value="4">4</option>
							<option value="5">5</option>
						</select>
					</div>
                    <div class="font-12 text-color-10 mb-0 seller_rating_error"></div>
				</div>
				<p class="font-weight-bold font-16 text-color-6 mt-3 mb-1">Share some details</p>
				<textarea class="bg-transparent border-radius-6px border-danger-2px w-100 p-3 font-14 text-color-4" rows="6" id="complete_note" name="complete_note" placeholder="Tell your story" maxlength="2500"></textarea>
				<div class="font-12 text-color-10 mb-0 note-error"></div>
				<div class="modal-footer border-0 px-3 px-md-5 pt-5 justify-content-around">
					<button type="button" class="btn text-color-1 bg-transparent" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn text-white bg-primary-blue border-radius-6px py-2 px-5">Submit</button> 
				</div>
				{{Form::close()}}
			</div>
		</div>
	</div>
</div>

{{-- Share service modal --}}
@if (request()->input('is_share') == 1  && $service_can_share == true )
	@include('frontend.buyer.include.social_media_share_modal')
@endif
@endsection
@section('css')
<link rel="stylesheet" href="{{front_asset('rating/dist/themes/css-stars.css')}}">
@endsection
@section('scripts')
<script src="{{front_asset('rating/jquery.barrating.js')}}"></script>

<script>
    /* Give review and rating JS */
    $(document).ready(function(){
		$('#shareServiceModal').modal('show');
        var currentRating = $('#seller_rating').data('current-rating');
        $('#seller_rating').barrating({
            theme: 'css-stars',
            showSelectedRating: false,
            initialRating: currentRating,
            onSelect: function(value, text) {

            },
            onClear: function(value, text) {

            }
        });

        $(document).on('click','.review-and-rating-btn',function(){
            $('#give-rating-reviwe-model').modal('show');
        });
        
		$('#complete_note').on('blur',function(){
			var length = $.trim(this.value).length;
			if(length == 0){
                $('.note-error').html('This is required.');
				$(this).val($.trim(this.value));
			}else{
                $('.note-error').html('');
            }
		});
        $(document).on('submit','#frmCoursReviewrating',function(event){
            $('.seller_rating_error').html('');
            $('.note-error').html('');
            var isAllValid = true;
			if ($('#seller_rating').val().length == "") {
				$('.seller_rating_error').html('This is required.');
				isAllValid = false;
			}

            if($('#complete_note').val().length == ""){
				$('.note-error').html('This is required.');
				isAllValid = false;
			}

			if(isAllValid == false) {
                event.preventDefault();
				return false;
			}
			//All validate
			if(isAllValid == true) {
				$('#frmCoursReviewrating').find('button').prop('disabled',true);
                $('#frmCoursReviewrating').trigger('submit');
			}

		});
    });
</script>

{{-- Course Player JS --}}
<script>
    $(document).ready(function(){
        var loader_interval = 1000;
        var _should_play = false;
        var _active_content_duration = 0; 
        var course_id = $("#course_secret").val();
        var _active_id = "{{$active_content_media->secret}}";
        var _media_type = "{{$active_content_media->media_type}}";
        var _player_id = "course-videos-player";
        var _is_description = false;
        @if($active_content_media->video_description != "")
            _is_description = true;
        @endif
        
        @if($active_content_media->media_type == 'video')
            var _video_link = "{{$active_content_media->media_url}}";
            var _title = "{{$active_content_media->name}}";
            _active_content_duration = "{{$active_content_duration}}";
            var _duration = 0; 
            loader_interval = 1500;
            play_course_content_video(_player_id, _video_link, _title)
        @endif

        stop_loader(loader_interval);
        
        /* get course Artiacle/Video JS */
		$(document).on('click','.show-course-content',function(){
			var url = $(this).data('url');
			var id = $(this).data('id');
			var course_id = $(this).data('course_id');
            $('.overview-loader').removeClass('d-none');
            update_content_time();
			$.ajax({
				url: url,
				method: "POST",
				data: {'_token': _token, id:id,course_id:course_id},
				dataType: "json",
				success: function(data){
					if(data.status == true){
                        _active_id = id;
                        show_next_and_prev_btn();
                        $('.active-content-media').removeClass('active-content-media');
                        $('#content-'+id).addClass('active-content-media');
						if(data.type == 'video'){
                            var player_div = '<div id="course-videos-player"></div>';
                            $('.course-video-player-embed').removeClass('d-none').html(player_div);
                            $('.course-article-view').addClass('d-none');
                            $('.video-desscription').html(data.video_description);
                            
							var _player_id = "course-videos-player";
                            var _video_link = data.link;
                            var _title = data.name;
                            _active_content_duration = data.duration;
                            _should_play = true;
                            play_course_content_video(_player_id, _video_link, _title);
                            loader_interval = 500;
						}else{
                            $('.course-video-player-embed').addClass('d-none').html('');
                            $('.course-article-view').removeClass('d-none');
                            if($('.open-content-name').length > 0){
                                $('.open-content-name').html(data.name);
                            }
							$(".load-course-article-content").empty().html(data.article_text);
                            $('.video-desscription').html('');
                            loader_interval = 500;
                        }
                        if($.trim(data.video_description).length > 0){
                            _is_description = true;
                        }else{
                            _is_description = false;
                        }
                        if(_is_description == false && $('.custom-content-description.hide').length == 0){
                            $('.custom-content-description').addClass('hide');
                            $('.custom-content-list').removeClass('hide');
                        }
                        $("html, body").animate({ scrollTop: 0 }, "slow");
                        stop_loader();
					}else{
                        stop_loader();
						alert_error('Something went wrong.');
					}
				},
				error: function(){
					alert_error('Something went wrong.');
				}
			});
		});

        $(".course-content-close").click(function(){
            $(".couser-right-hide").toggle();
            $(".right-sidebar-toggle").toggleClass('course-right-sidebar-full');
        });
        $(".course-content-show-arrow").click(function(){
            $(".couser-right-hide").show();
            $(".right-sidebar-toggle").removeClass('course-right-sidebar-full');
        });
        
        $("#read-more-detail").click(function(){
            $('.read-more-faded').toggleClass('couser-content-full');
            $(this).toggleClass('couser-content-up');
        });

        $(".scrollbtn").click(function() {
            var scrollID = $(this).attr('data-scroll');
            $('html, body').animate({
                scrollTop: $("#"+scrollID).offset().top - 150
            }, 1000);
        });

        /*Pagination through jquery load method*/
		$('body').on('click', '.ajax-pagination-div .pagination a', function (e) {
			e.preventDefault();

			var rating_count = $('#ratting_count_id').val();
			var id = $("#service_id").val();
			var url = $(this).attr('href');
			var rating = $('#review_rating').val();

			$.ajax({
				url : url + '&id=' + id+'&rating='+rating+'&rating_count='+rating_count,
				type : "get",
				success : function(data){
					$('.ajax-pagination-div').html(data);
				}
			});
			/*$('.ajax-pagination-div').load(url + '&id=' + id);return false;*/
		});
        /* END Pagination */

        /*load method*/
		$(document).on('click', '.complete-learn-course-content', function () {
			var course_id = $("#course_secret").val();
			var content_media_id = $(this).data('content_media_id');
            update_learn_course_content(course_id,content_media_id);
        });
        /* END Update learn content */

        /* Next Button JS */
        $(document).on('click','.next-content-btn-arrow',function() { 
            get_next_content(1);
        });
        /* Prev Button JS */
        $(document).on('click','.previous-content-btn-arrow',function() { 
            get_next_content(-1);
        });

        setTimeout(() => {
            show_next_and_prev_btn();
        }, 1000);
        
        /* On chane screen to update player time */
        window.addEventListener("beforeunload", function (e) {
            update_content_time();
        });

        /* Play coontent video */
        function play_course_content_video(player_id, video_link, title){
            const playerInstance = jwplayer(player_id).setup({
				"file": video_link,
				"title": title,
				"autostart": _should_play
			});
            /* set Time */
            playerInstance.on('firstFrame', () => {
                if (_active_content_duration) {
                    playerInstance.seek(_active_content_duration);
                }
            });;
            /* Complete Event */
            playerInstance.on('complete', function(){
                get_next_content(1,1);
            });
            /* Complete Event */
            playerInstance.on('pause', function(){
                update_content_time();
            });
            /* Complete Event */
            playerInstance.on('fullscreen', function(){
                if(playerInstance.getFullscreen() == true){
                    playerInstance.removeButton('0p25xslow');
                }else{
                    playerInstance.addButton(
                        "{{url('public/frontend/assets/img/file-text.png')}}",
                        "Video Description", 
                        function() {
                            hide_show_description();
                        },
                        "0p25xslow"
                    );
                }
            });
            
            /* Added custom button */
            playerInstance.on('ready',function() {
                if (_is_description == true){
                    playerInstance.addButton(
                        "{{url('public/frontend/assets/img/file-text.png')}}",
                        "Video Description", 
                        function() {
                            hide_show_description();
                        },
                        "0p25xslow"
                    );
                }
            });
        }

        /* Update learn course content */
        function update_learn_course_content(course_id,content_media_id){
            $.ajax({
				url : "{{route('update.learn.content')}}",
				type : "POST",
                data : { '_token':_token, 'course_id':course_id, 'content_media_id':content_media_id },
				success : function(data){
                    return data;
				}
			});
        }

        /* Update learn course content */
        function update_content_time(){
            var course_id = $("#course_secret").val();
            var content_media_id = _active_id;
            var duration = jwplayer(_player_id).getPosition();
            if(duration != null){
                $.ajax({
                    url : "{{route('update.content.time')}}",
                    type : "POST",
                    data : { '_token':_token, 'course_id':course_id, 'content_media_id':content_media_id, 'duration':duration },
                    success : function(data){
                        return true;
                    }
                });
            }
        }

        /* Go next and previous content */
        function get_next_content(next_index,is_complete=0){
            var course_id = $("#course_secret").val();
            var content_media_id = $(".show-course-content[data-id='"+_active_id+"']").data('id');
            var prev_section_id = $(".show-course-content[data-id='"+_active_id+"']").data('section_id');
            if(is_complete == 1){
                $('#checklabel_'+content_media_id).prop('checked',true);
                update_learn_course_content(course_id,content_media_id);
            }
            var index = $(".show-course-content[data-id='"+_active_id+"']").data('index') + next_index;
            var next_content_id = $(".show-course-content[data-index='"+index+"']").data('id');
            var section_id = $(".show-course-content[data-index='"+index+"']").data('section_id');
            if(next_content_id != undefined){
                if(prev_section_id != section_id){
                    $(".open-section-"+section_id).trigger('click');
                }
                _active_id = next_content_id;
                $(".show-course-content[data-index='"+index+"']").trigger('click');
            }
        }

        /* show next and previous button */
        function show_next_and_prev_btn(){
            var index = $(".show-course-content[data-id='"+_active_id+"']").data('index');
            /* Check previous button conditoin */
            if(index != 1){
                $('.previous-content-btn-arrow').removeClass('d-none');
            }else{
                $('.previous-content-btn-arrow').addClass('d-none');
            }
            index = index +1;
            var next_content_id = $(".show-course-content[data-index='"+index+"']").data('id');
            var section_id = $(".show-course-content[data-index='"+index+"']").data('section_id');
            if(next_content_id != undefined){
                $('.next-content-btn-arrow').removeClass('d-none');
            }else{
                $('.next-content-btn-arrow').addClass('d-none');
            }
        }

        /* Stop loader JS */ 
        function stop_loader(){
            setTimeout(() => {
                $('.overview-loader').addClass('d-none');
            }, loader_interval);
        }

        /* Show video description JS */
        function hide_show_description(){
            if($(".couser-right-hide").css('display') == 'none'){
                $(".right-sidebar-toggle").toggleClass('course-right-sidebar-full');
                $(".couser-right-hide").css('display','block');
            }
            if($('.custom-content-description.hide').length > 0){
                $('.custom-content-description').removeClass('hide');
                $('.custom-content-list').addClass('hide');
            }else{
                $('.custom-content-description').addClass('hide');
                $('.custom-content-list').removeClass('hide');
            }
        } 
     });
</script>
<script>
    /* Disabled view source code */ 
    document.onkeydown = function(e) {
        if(e.keyCode == 123) {
            return false;
        }
        if(e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)){
            return false;
        }
        if(e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)){
            return false;
        }
        if(e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)){
            return false;
        }
    }
    /* Disabled right click */ 
    document.addEventListener('contextmenu', event => event.preventDefault());
</script>
@if($is_mobile_device == true)
<script>
    /* Mobile JS */ 
    $(document).ready(function(){
        $(document).on('click','.cust-nav-item',function(){
            $('.cust-nav-item').removeClass('active');
            $(this).addClass('active');
        });
    });
</script>
@endif
@endsection


