<h4>Reviews</h4>
<div class="row">
	<div class="col-md-4" style="padding-top: 10px;"><h6>Review Highlights</h6></div>
</div>
<div class="review-block New">

	@if(count($reviewPlanData) > 0)	
	@foreach($reviewPlanData as $rowCommentPlan)
	<div class="row review-item">
		<div class="col-md-3">
			<div class="package_name">
				<h5 style="margin-bottom: 0px;">{{ucfirst($rowCommentPlan['plan_type'])}}</h5>
			</div>
			<div class="no_of_reviews">
				in {{$rowCommentPlan['total_review']}} Reviews
			</div>
		</div>
		<div class="col-md-9">
			<p class="comment-reply-text review-margin-buyer" style="padding-left: 0px">{{$rowCommentPlan['review']}}</p>

		</div>
	</div>
	@endforeach
	@endif
</div>

<div class="row" style="padding-top: 10px;">
	<div class="col-md-4" style="padding-top: 10px;">
		<h6>All Reviews</h6>
	</div>
</div>
@if($Service->reuse_denied_status == 1 && $Service->total_review_count > 0)
	<div class="row">
		<div class="col-md-12">
			<small><b><i>*This service has been updated. Some reviews may reference services no longer offered.</i></b></small>
		</div>
	</div>
@endif
<div class="review-block">
	<div class="row review-item">
		@foreach($Comment as $rowComment)
		<div class="col-md-2">
			<div class="review-img">
				<img src="{{get_user_profile_image_url($rowComment->user)}}" alt="profile-image" class="img-fluid">
			</div>
		</div>
		<div class="col-md-10">
			<div class="review-title"><b>{{$rowComment->user->username}}</b></div><br>

			<div>@if($rowComment->seller_rating == 5)
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star checked"></span>

				@elseif($rowComment->seller_rating == 4)
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star unchecked"></span>
				@elseif($rowComment->seller_rating == 3)
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star unchecked"></span>
				<span class="fa fa-star unchecked"></span>
				@elseif($rowComment->seller_rating == 2)
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star unchecked"></span>
				<span class="fa fa-star unchecked"></span>
				<span class="fa fa-star unchecked"></span>
				@elseif($rowComment->seller_rating == 1)
				<span class="fa fa-star checked"></span>
				<span class="fa fa-star unchecked"></span>
				<span class="fa fa-star unchecked"></span>
				<span class="fa fa-star unchecked"></span>
				<span class="fa fa-star unchecked"></span>
				@else
				<span class="fa fa-star unchecked"></span>
				<span class="fa fa-star unchecked"></span>
				<span class="fa fa-star unchecked"></span>
				<span class="fa fa-star unchecked"></span>
				<span class="fa fa-star unchecked"></span>
				@endif  &nbsp;&nbsp;&nbsp; {{date('d M,Y h:i',strtotime($rowComment->review_date))}}</div><br>
				<div><b>"{{$rowComment->package_name}}{!! ($rowComment->is_review_edition == 1)?' (<i>Invited to review</i>)':'' !!}" -- For {{$rowComment->plan_type}} plan</b></div>
				<p class="comment-reply-text review-margin-buyer" style="padding-left: 0px"><?=$rowComment->completed_note;?></p>
				<div>
					<p class="comment-reply-text review-margin-seller" id='showComReply_{{$rowComment->id}}'>
						@if($rowComment->completed_reply)
						<i class="fa fa-reply" aria-hidden="true"></i> 
						{{$rowComment->completed_reply}}
						@endif
					</p></div>
				</div>
				@endforeach
			</div>
		</div>

		<div class="paginate-center">
			@if(count($Comment))
			{{ $Comment->appends(['seo_url' => $seo_url,
			'username' => $username])->links("pagination::bootstrap-4") }}
			@endif
		</div>


