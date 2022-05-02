@if(count($contentMedia)>0)
<ul class="list-group mt-3 @if(count($contentMedia)>1) content_media_shorting @endif">
    @foreach($contentMedia as $row)
        <li class="list-group-item d-flex summary service-item align-items-center justify-content-between lecture-media-{{$row->secret}}" id="{{$row->secret}}">
            <div class="font-14 text-color-2 text-left">  {{ $row->name }} </div>
            <div class="font-14 text-color-2 text-left">  
                @if($row->media_type == 'video')
                {{ get_duration($row->media_time) }}
                @endif

                @if($row->media_type == 'video')
                    <a href="Javascript:;" data-url="{{$row->media_url}}" data-mime="{{$row->media_mime}}" data-title="{{$row->name}}" src="{{get_video_player_img()}}" title="Play" class="px-md-2 preview-course-video-btn no-filter" >
                        <i class="fa fa-play-circle-o text-color-6 font-16 align-middle" aria-hidden="true"></i>
                    </a>
                @else
                    <a href="Javascript:;" title="View Article" class="px-md-2 course-article-preview" data-url="{!! route('get_preview_content') !!}" data-id="{{$row->secret}}">
                        <i class="fa fa-file text-color-6 font-16 align-middle" aria-hidden="true"></i>
                    </a>
                @endif
                @if($row->video_description != "")
                    <a href="Javascript:;" title="Video Description" class="px-md-2 course-article-preview" data-url="{!! route('get_preview_content') !!}" data-is_description="true" data-id="{{$row->secret}}">
                        <i class="fa fa-file-text text-color-6 font-16 align-middle" aria-hidden="true"></i>
                    </a>
                @endif

                <button title="Downloadable Contents" class="px-md-2 btn btn-sm shadow-none get-downloadable-contents" data-id="{{$row->secret}}">
                    <i class="fa fa-download text-color-6 font-16 align-middle" aria-hidden="true"></i>
                    <i class="fa fa-spin fa-spinner text-color-6 font-16 align-middle d-none" aria-hidden="true"></i>
                    <section class="badge badge-warning text-dark" id="downloadable-count-{{$row->secret}}">{{$row->downloadable_resources->count()}}</section>
                </button>

                @if($row->is_approve == 0)
                <button class="btn btn-sm shadow-none edit_lecture_btn" data-lecture_id="{{$row->secret}}" type="button" title="Edit" data-id="{{$content_secret}}" data-url="{{route('course.content.get_form',['update',$seo_url,$content_secret])}}">
                    <i class="fa fa-pencil text-color-6 font-16 align-middle" aria-hidden="true"></i>
                    <i class="fa fa-spin fa-spinner text-color-6 d-none" aria-hidden="true"></i>
                </button>
                @endif

                <button class="btn btn-sm shadow-none delete-lecture" data-id="{{$row->secret}}" type="button" title="Delete" >
                    <i class="far fa-trash-alt text-color-6 font-16 align-middle" aria-hidden="true"></i>
                </button>
            </div>
        </li>
    @endforeach
</ul>
@else
    <div class="col-md-12 text-center my-5">
        <div class="overlayer"></div>
        <h3 class="text-center">No contents available.</h3>
    </div>
@endif