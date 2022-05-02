@extends('layouts.frontend.main')
@section('pageTitle', 'Portfolio | demo')
@section('content')
<!-- Display Error Message -->
@include('layouts.frontend.messages')

<!-- Masthead -->
<header class="masthead text-white"> {{-- masthead  --}}
	<div class="overlay"></div>
    <div class="bg-dark w-100">
	<div class="container py-4">
        <div class="row py-2">
            <div class="col-12 col-md-4">
                <a href="{{route('viewuserservices',$username)}}"> <p class="text-center text-md-left mb-0 text-white font-24 font-weight-bold"><i class="fas fa-chevron-left px-2"></i> Back to profile</p></a>
            </div>
            <div class="col-12 col-md-4">
                <p class="text-center mb-0 text-white font-24 font-weight-bold mt-3 mt-md-0">User's Portfolio</p>
            </div>
        </div>
	</div>
    </div>
</header>

<div class="container my-5 font-lato">
    <div class="row justify-content-center">
        @if(count($portfolios) > 0)
        @foreach($portfolios as $portfolio)
	        <div class="col-12 col-md-6 col-lg-4 mt-3">
	            <div class="summary overflow-hidden min-h-100">
	            	@if($portfolio->media_type == 'image')
                		<img src="{{$portfolio->thumbnail_url}}" data-link="{{$portfolio->media_link}}" class="img-fluid cust-portfolio-img drag-image custViewImage" alt="{{$portfolio->title}}">
                    @else
						<div class="cust-portfolio-img" style="background-image: url('{{($portfolio->thumbnail_url)? $portfolio->thumbnail_url : url('public/frontend/images/video_players.png')}}')">
							<img data-url="{{$portfolio->media_link}}" data-mime="{{$portfolio->mime}}" data-title="{{$portfolio->title}}" src="{{get_video_player_img()}}" class="img-fluid cust-portfolio-img video-link video-play-btn" >
						</div>
					@endif
	                <div class="p-3">
	                    <p class="font-16 text-color-2 font-weight-bold">{{$portfolio->title}}</p>
	                    <p  class="font-14 text-color-4 portfolio-description mb-0" data-content="{{$portfolio->description}}">
	                    	<span class="readless-text-{{$portfolio->secret}}">{{string_limit($portfolio->description,70)}}</span>
	                    	@if(strlen($portfolio->description) > 70)
		                    	<span class="d-none readmore-text-{{$portfolio->secret}}">{{$portfolio->description}}</span>
								<label class="text-primary btn-link read-more" id="readmore-{{$portfolio->secret}}" data-id="{{$portfolio->secret}}">Read More</label>
								<label class="text-primary btn-link read-less d-none" id="readless-{{$portfolio->secret}}" data-id="{{$portfolio->secret}}">Less</label>
							@endif
	                    </p>
	                </div>  
	            </div>
	        </div>
        @endforeach
        @else
	        <div class="py-5 text-danger">
	        	No any projects are added.
	        </div>
        @endif
    </div>
</div>
@endsection

@section('css')
<link href="{{front_asset('bootstrap/dist/css/bootstrap-tagsinput.css')}}" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="{{url('public/frontend/css/price_range_style.css')}}"/>
<link href="{{front_asset('assets/css/dropzone.css')}}" rel="stylesheet">
@endsection
@section('scripts')
<script type="text/javascript" src="{{front_asset('bootstrap/dist/js/bootstrap-tagsinput.js')}}"></script> 
<script type="text/javascript" src="{{url('public/frontend/js/price_range_script.js')}}"></script>
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script type="text/javascript">
	$(document).on('click','.video-link',function(){
		var video_link = $(this).data('url');
		var ext = video_link.split('.').pop();
		$('#play_video video').html('<source src="'+video_link+'" type="video/'+ext+'">Your browser does not support HTML video.');
		$("#play_video video")[0].load();
		$('#showVideo').modal('show');
	});
</script>
@endsection