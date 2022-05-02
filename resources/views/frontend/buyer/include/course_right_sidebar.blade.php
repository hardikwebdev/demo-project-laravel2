<div class="course_sticky-position bg-white course-fixed-right custom-content-list">
    <div class="d-flex align-items-center navbar-collapse cus-flex-border justify-content-between">
        <div class="">
            <h4 class="mb-0 font-20">Course content</h4>
        </div>
        <div class="">
            <i class="fas fa-times course-content-close"></i>
        </div>
    </div>
    <div class="course-height-fxed">
        <div class="card card-bark-mode cou-border-radius-0 border-0">
            <div class="">
                <div id="accordion">
                    {{-- Course content --}}
                    @php
                        $accordion_id = "accordion";
                    @endphp
                    @include('frontend.buyer.include.content_list')
                </div>
            </div>
        </div>
    </div>
</div>
<div class="course_sticky-position bg-white course-fixed-right custom-content-description hide">
    <div class="d-flex align-items-center navbar-collapse cus-flex-border justify-content-between">
        <div class="">
            <h4 class="mb-0 font-20">Video Description</h4>
        </div>
        <div class="">
            <i class="fas fa-times course-content-close"></i>
        </div>
    </div>
    <div class="course-height-fxed">
        <div class="card card-bark-mode cou-border-radius-0 border-0">
            <div class="video-desscription p-3 white-space-pre-line">
                {!! $active_content_media->video_description !!}
            </div>
        </div>
    </div>
</div>