{{-- Old Video --}}
@if($User->userDetails != null && $User->userDetails->intro_video_link != "")
<div class="col-6">
    <div class="summary overflow-hidden">
        <div class="d-flex justify-content-between py-2 align-items-center px-2 border-bottom">
            <div>Approved Video</div>
            <div>
                <a href="Javascript:;" title="Resize thumbnail" class="resize-thumbnail mr-2" data-type="old_thumbnail" data-toggle="modal" data-target="#resize-thumbnail-modal" data-url="{{$User->userDetails->intro_video_thumbnail}}"><i class="fa fa-pencil text-color-6 font-16 align-middle"></i></a>
                <a href="Javascript:;" class="delete-intro-video" data-url="{{route('delete.introduction.video',[old_video,$User->userDetails->secret])}}"><i class="far fa-trash-alt text-color-6 font-16 align-middle"></i></a>
            </div>
        </div>
        <div class="d-flex justify-content-center align-items-center old_thumbnail_img" style="height: 150px; background-size: cover; background-image: url('{{$User->userDetails->intro_video_thumbnail}}')">
            <img data-url="{{$User->userDetails->intro_video_link}}" data-mime="video/mp4" data-title="" src="{{asset('public/frontend/images/video-play-icon.png')}}" class="img-fluid video-link new-play-btn" >
        </div>
    </div>
</div>
@endif
{{-- New Video --}}
@if($User->introductionVideoHistory != null)
<div class="col-6">
    <div class="summary overflow-hidden">
        <div class="d-flex justify-content-between py-2 align-items-center px-2 border-bottom">
            <div>New Video
                @if($User->introductionVideoHistory->is_approved == 2)
                <div class="custom-video-bagde badge badge-danger">
                    <a href="Javascript:;" class="text-white pt-2" data-container="body" data-toggle="popover" data-placement="top" data-content="{{$User->introductionVideoHistory->reject_reason}}"><i class="fa fa-info-circle"></i> Rejected </a>
                </div>
                @elseif($User->introductionVideoHistory->is_approved == 0)
                <div class="custom-video-bagde badge badge-info">
                    <span class="text-white pt-2"><i class="fa fa-info-circle"></i> Pending Approval </span>
                </div>
                @endif
            </div>
            <div>
                @if($User->introductionVideoHistory->is_approved != 2)
                <a href="Javascript:;" title="Resize thumbnail" class="resize-thumbnail mr-2" data-type="new_thumbnail" data-toggle="modal" data-target="#resize-thumbnail-modal" data-url="{{$User->introductionVideoHistory->intro_video_thumbnail}}"><i class="fa fa-pencil text-color-6 font-16 align-middle"></i></a>
                @endif
                <a href="Javascript:;" class="delete-intro-video" data-url="{{route('delete.introduction.video',[new_video,$User->introductionVideoHistory->secret])}}"><i class="far fa-trash-alt text-color-6 font-16 align-middle"></i></a>
            </div>
        </div>
        <div class="d-flex justify-content-center align-items-center new_thumbnail_img" style="height: 150px; background-size: cover; background-image: url('{{$User->introductionVideoHistory->intro_video_thumbnail}}')">
            <img data-url="{{$User->introductionVideoHistory->intro_video_link}}" data-mime="video/mp4" data-title="" src="{{asset('public/frontend/images/video-play-icon.png')}}" class="img-fluid video-link new-play-btn">
        </div>
    </div>
</div>
@endif