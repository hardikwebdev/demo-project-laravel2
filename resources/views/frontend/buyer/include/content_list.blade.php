@php $index = 1; @endphp
@foreach($course_sections as $sectionKey => $section)
    @php
    $expanded = "false";
    $collapse = "";
    if($active_content_media->course_content_id == $section->id){
        $expanded = "true";
        $collapse = "show";
    }
    @endphp
    <div class="card card-bark-mode border-0">
        <div class="p-2 bg-light-gray-f0 border bg-light-gray-f0 border-left-0" id="headingOne">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <button type="button" class="btn font-18 text-color-2 text-left d-inline-flex bg-transparent font-weight-bold arrow-down-btn shadow-none open-section-{{$section->secret}}" data-toggle="collapse" data-target="#sectionCollapse{{$sectionKey}}" aria-expanded="{{$expanded}}" aria-controls="sectionCollapse{{$sectionKey}}">
                        <i class="fas fa-chevron-down arrow-down font-12 d-table-cell align-self-center"></i>
                        <span class="ml-3 d-table-cell font-16">{{$section->name}} </span>
                    </button>
                </h5>
                <div>
                    <p class="text-color-2 font-14 m-0">{{ get_duration_heading($section->content_medias->sum('media_time')) }}</p>
                </div>
            </div>
        </div>

        <div id="sectionCollapse{{$sectionKey}}" class="collapse {{ $collapse }} border-right" aria-labelledby="sectionCollapse{{$sectionKey}}" data-parent="#{{$accordion_id}}">
            @if(count($section->content_medias)) 
            @foreach($section->content_medias as $media)
                <div class="d-flex justify-content-between align-items-center border-bottom hover-content-bg p-2 {{($active_content_media->secret == $media->secret)? 'active-content-media' : ''}}" id="content-{{$media->secret}}">
                    {{-- Content Title --}}
                    <div class="d-flex align-items-start">
                        <div class="custom-control custom-checkbox text-color-2 font-16 ">
                            @php
                                $is_check = "";
                                if(in_array($media->id, $completed_learn_content_ids)){
                                    $is_check = "checked";
                                }
                            @endphp
                            <input type="checkbox" class="custom-control-input mr-3 complete-learn-course-content" id="checklabel_{{$media->secret}}" data-content_media_id="{{$media->secret}}" {{$is_check}}>
                            <label class="custom-control-label" for="checklabel_{{$media->secret}}"></label>
                        </div>
                        @if($media->media_type == 'video')
                            <div class="pl-3">
                                <a href="Javascript:;" class="show-course-content" data-index="{{$index++}}" data-section_id="{{$section->secret}}" data-url="{!! route('get_preview_content') !!}" data-id="{{$media->secret}}" data-course_id="{{$course->secret}}">
                                    <p class="text-color-2 font-16 m-0 ">{{$media->name}}</p>
                                    <img src="{{url('public/frontend/images/play-icon.png')}}" class='img-fluid pr-1' alt="">
                                </a>
                            </div>
                        @else
                            <div class="pl-3">
                                <a href="Javascript:;" class="show-course-content" data-index="{{$index++}}" data-section_id="{{$section->secret}}" data-url="{!! route('get_preview_content') !!}" data-id="{{$media->secret}}" data-course_id="{{$course->secret}}"> 
                                    <p class="text-color-2 font-16 m-0 ">{{$media->name}}</p>
                                    <div class="d-flex">
                                        <i class="course-cion-color fas fa-file"></i>
                                    </div>
                                </a>
                            </div>
                        @endif
                    </div>
                    {{-- Right Side --}}
                    <div class="d-flex align-items-center">
                            {{-- Downloadable resources --}}
                            @if($media->downloadable_resources->count() > 0)
                            <div class="align-self-end pr-2" >
                                <div class="nav-item dropdown">
                                    <span class="nav-link dropdown-toggle course-btn">
                                    <i class="far fa-folder-open"></i>  Resources  <i class="fas fa-chevron-down"></i>
                                    </span>
                                    <div class="dropdown-menu dropdown-content cust-dropdown-item">
                                        @foreach ($media->downloadable_resources as $item)
                                            <a class="dropdown-item text-color-2" href="{{route('download_files_s3')}}?bucket={{env('bucket_course')}}&key={{$item->file_s3_key}}&filename={{$item->filename}}">
                                                @php
                                                    $item_name_split = explode('.',$item->filename);
                                                    $item_name = $item_name_split[0];
                                                    $item_extension = $item_name_split[1];
                                                @endphp
                                                {{substr($item_name,0,20).".".$item_extension}}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endif
                        @if($media->media_type == 'video')
                        <p class="text-color-1 font-12 m-0 text-nowrap">{{get_duration($media->media_time)}}</p>
                        @endif
                    </div>
                </div>
            @endforeach
            @endif
        </div>
    </div>
@endforeach