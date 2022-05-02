<div class=" ctab-pane cfade mob-course-show mob-tab-content mt-3" id='course-content' role="tabpanel" aria-labelledby="course-content-tab">
    <div class=" border-0">
        <div class="card card-bark-mode cou-border-radius-0 ">
            <div class="">
                <div id="accordion-bottom-menu">
                    {{-- Course content --}}
                    @php
                        $accordion_id = "accordion-bottom-menu";
                    @endphp
                    @include('frontend.buyer.include.content_list')
                </div>
            </div>
        </div>
    </div>
</div>