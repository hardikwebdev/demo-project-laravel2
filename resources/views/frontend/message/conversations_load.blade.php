@if($messageDetail)
@foreach($messageDetail as $row)

<div class="comment-wrap">
	<div class="row">
		<div class="col-2">
			<a href="javascript:void(0);" style="cursor: default;">
				<figure class="dispute-user-avatar medium">
					@if($row->is_admin == 1)
						<img src="{{url('public/frontend/assets/img/logo/LogoHeader.png')}}" alt="profile-image" class="admin_profile">
					@else
						<img class="img-fluid" src="{{get_user_profile_image_url($row->fromUser)}}" alt="profile-image">
					@endif
				</figure>
			</a>
		</div>
		<div class="col-10">
			<div class="comment">
				<p class="text-header">
					@if($row->is_admin == 1)
						<span class="user-image">demo Support Team
							<img src="{{url('public/frontend/assets/img/logo/demo-badge.png')}}" alt="profile-image">
						</span>
					@else
						@if($parent_username==$row->fromUser->username){{'Me'}}@else <a href="{{ route('viewuserservices',$row->fromUser->username) }}" class="text-header">{{$row->fromUser->username}}</a>@endif
					@endif
				</p>
				<p class="timestamp date-timestamp-font">{{date('M dS, Y - H:i A',strtotime($row->created_at))}}</p>
				<p class="text-word-wrap text-body custom-chat">
					@if(strpos($row->message, 'view-source:https://www.pleasantonchildcare.com/') !== false)
					@else
						@if($row->attachment == 0)
						@emojione(nl2br($row->message))
						@else
							@if($row->file_name != '')
								{{$row->file_name}}
							@elseif($row->photo_s3_key != '')
							{{substr($row->message, strrpos($row->message, '/') + 1)}}
							@endif
								
							@if($row->photo_s3_key != '')
								<a class="download-icon action-icon" title="Download file" href="{{route('download_files_s3')}}?bucket={{env('bucket_service')}}&key={{$row->photo_s3_key}}&filename={{substr($row->message, strrpos($row->message, '/') + 1)}}" download class="download-custom"><i class="fa fa-download" aria-hidden="true"></i></a>
							@endif
						@endif
					@endif
				</p>
			</div>
		</div>
	</div>
</div>
<hr class="line-separator">
@endforeach
@endif