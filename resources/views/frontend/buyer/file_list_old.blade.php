@foreach($UserFiles as $row)
<div class="row review-item align-items-center">
	<div class="col-md-3 col-lg-2">

		<div class="review-img">
			<img src="{{get_user_profile_image_url($row->user)}}" class="img-fluid" alt="profile-image">
		</div>
	</div>
	<div class="col-md-6 col-lg-7">
		<div class="comment-title">{{$row->user->username}} <span>added a file</span></div>
		<div class="review-date">{{date('d M,Y h:i',strtotime($row->created_at))}}</div>
	</div>
	<div class="col-md-3 col-lg-3">
		<div class="action-icon">

			<span class="download_size">({{bytesToHuman($row->filename_size)}}) </span>

			@if($row->photo_s3_key != '')
				<p class="mb-0">{{remove_timestamp_from_filename($row->filename)}}</p>
				<a class="download-icon" title="Download" href="{{route('download_files_s3')}}?bucket={{env('bucket_order')}}&key={{$row->photo_s3_key}}&filename={{$row->filename}}"><i class="fa fa-download" aria-hidden="true"></i></a>
			@else
				<a class="download-icon" title="Download" href="{{route('download_files',[$row->id])}}" download><i class="fa fa-download" aria-hidden="true"></i></a>
			@endif

			@if($row->uid == $parent_uid)
				@if($row->photo_s3_key != '')
					<a href="javascript:void(0);" class="delete-icon notification-close" data-id="{{$row->id}}" data-bucket={{env('bucket_order')}} data-url="{{route('removefile')}}"><i class="fa fa-trash" aria-hidden="true"></i></a>
				@else
					<a href="javascript:void(0);" class="delete-icon notification-close" data-id="{{$row->id}}" data-url="{{route('removefile')}}"><i class="fa fa-trash" aria-hidden="true"></i></a>
				@endif
			@endif
		</div>
	</div>
</div>
@endforeach
<div class="pull-right">
	@if(count($UserFiles)){!! $UserFiles->links("pagination::bootstrap-4") !!}@endif
</div>