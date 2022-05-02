
<h4>Reviews</h4>
<div class="row">
	<div class="col-md-4" style="padding-top: 10px;">
		<h6>Review Highlights</h6>
	</div>
</div>
<div class="review-block New" id="review-box">
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
		<div class="col-md-12">
			<p class="pull-right">

				@if( isset($rowComment) && (count($rowComment->review_log) > 1) && (Auth::check()))
				@if(Auth::user()->id == $Service->uid)
				<button type="button" data-toggle="modal" data-target="#myModal_{{$rowComment->id}}" class="comment-reply-btn" href='#review-log-model-{{$rowComment->id}}'>Review Edit History
				</button>
				@endif
				@endif

				@if(Auth::check())
				@if(isset($rowComment) && empty($rowComment->completed_reply) && (!empty($rowComment->completed_note)))
				@if(Auth::user()->id == $Service->uid)
				<a class='pull-right openTextbox comment-reply-btn-a' id='replay_{{$rowComment->id}}' style=''> <i class="fa fa-reply" aria-hidden="true"></i> Reply </a>
				@endif
				@endif
				@endif
			</p>
		</div>
	</div>
	@endforeach
	@endif
</div>

@if($Service->total_review_count > 0)
<div class="cus-ratting-box" >
	<div class="ratting-content">
		{!! displayRating($Service->service_rating ,$showFiveStar = 1) !!}
		<span class="fs-18"><b>{{number_format($Service->service_rating,1, '.', '')}} out of 5</b></span>
	</div>
	<div> 
		<span class="pl-3 cus-grey pb-3 pt-3">{{$Service->total_review_count}} global ratings</span>
	</div>
	<table class="table ratting-table cus-w-80">
		<tbody>
			@for ($i = 5; $i >= 1; $i--)
			<tr >
				<td class="cus-white-space-nowrap">
					<a href="javascript:void(0)" class="get_review_by_count_link rating_hover_{{$i}}" data-ratting_count="{{$i}}" data-url="{{route('getallreview').'?seo_url='.$seo_url.'&username='.$username.'&id='.$Service->id.'&rating_count='.$i}}">
						<span class="cus-label-star">{{$i}} star</span>
					</a>
				</td> 
				<td class="middle">
					<a href="javascript:void(0)" class="get_review_by_count_link" data-ratting_count="{{$i}}" data-url="{{route('getallreview').'?seo_url='.$seo_url.'&username='.$username.'&id='.$Service->id.'&rating_count='.$i}}">
						<div class="progress rating_middle_hover_{{$i}}">
							<div class="progress-bar progress_color" style="width: {{review_in_percentage($Service->id,$i,$Service->total_review_count)}}%" role="progressbar"></div>
						</div>
					</a>
				</td>
				<td class="cus-white-space-nowrap">
					<a href="javascript:void(0)" class="get_review_by_count_link rating_hover_{{$i}}" data-ratting_count="{{$i}}" data-url="{{route('getallreview').'?seo_url='.$seo_url.'&username='.$username.'&id='.$Service->id.'&rating_count='.$i}}">
						<span class="cus-label-star-count">{{review_in_percentage($Service->id,$i,$Service->total_review_count)}}%</span>
					</a>
				</td>
			</tr>
			@endfor
		</tbody>
	</table>
</div>
@endif

<div class="all_review_section" style="padding-top: 10px;">
	<div class="row">
		<div class="col-md-4" style="padding-top: 10px;">
			<h6>All Reviews</h6>
		</div>
		<div class="col-md-8 review_filter" style="padding-bottom: 10px;">
			<select class="form-control col-md-3" id="review_rating" name="rating" style="float: right;">
				<option value="all" @if(@$_GET['rating']=='all' ) selected @endif data-url="{{route('getallreview').'?seo_url='.$seo_url.'&username='.$username.'&id='.$Service->id.'&rating=all'}}">All</option>
				{{-- <option value="best" @if(@$_GET['rating']=='best' ) selected @endif data-url="{{url('profile').'/'.$username.'/'.$seo_url}}?rating=best">Best</option> --}}
				<option value="best" @if(@$_GET['rating']=='best' ) selected @endif data-url="{{route('getallreview').'?seo_url='.$seo_url.'&username='.$username.'&id='.$Service->id.'&rating=best'}}">Best</option>
				<option value="worst" @if(@$_GET['rating']=='worst' ) selected @endif data-url="{{route('getallreview').'?seo_url='.$seo_url.'&username='.$username.'&id='.$Service->id.'&rating=worst'}}">Worst</option>
				<option value="newest" @if(@$_GET['rating']=='newest' ) selected @endif data-url="{{route('getallreview').'?seo_url='.$seo_url.'&username='.$username.'&id='.$Service->id.'&rating=newest'}}">Newest</option>
				<option value="oldest" @if(@$_GET['rating']=='oldest' ) selected @endif data-url="{{route('getallreview').'?seo_url='.$seo_url.'&username='.$username.'&id='.$Service->id.'&rating=oldest'}}">Oldest</option>
			</select>
		</div>
	</div>
	@if($Service->reuse_denied_status == 1 && $Service->total_review_count > 0)
	<div class="row">
		<div class="col-md-12">
			<small><b><i>*This service has been updated. Some reviews may reference services no longer offered.</i></b></small>
		</div>
	</div>
	@endif
</div>
@if(count($Comment) > 0)
<div class="review-block">
	<div class="row review-item">
		@foreach($Comment as $rowComment)
		<div class="col-md-2">
			<div class="review-img">
				<img src="{{get_user_profile_image_url($rowComment->user)}}" alt="profile-image" class="img-fluid">
			</div>
		</div>
		<div class="col-md-9">
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
				@endif &nbsp;&nbsp;
				<strong class="text-black">
					@if($rowComment->status == 'completed')
					{{date('d M,Y h:i',strtotime($rowComment->review_date))}}
					@else
					{{date('d M,Y h:i',strtotime($rowComment->cancel_date))}}
					@endif
				</strong>
				@if(($Service->uid == Auth::id() || $Service->uid == Auth::user()->parent_id )  && $rowComment->seller_rating == 5)
					<a href="javascript:" class="share_link badge-primary" data-review="{{$rowComment->completed_note}}" data-buyer="{{$rowComment->user->username}}" data-sellerratings="{{$rowComment->seller_rating}}">SHARE!</a>
				@endif
				
			</div><br>
			<div><b>"{{$rowComment->package_name}}{!! ($rowComment->is_review_edition == 1)?' (<i>Invited to review</i>)':'' !!}" -- For {{$rowComment->plan_type}} plan</b></div>
			<p class="comment-reply-text review-margin-buyer" style="padding-left: 0px"><?=$rowComment->completed_note;?></p>
			@php
				$helpFeedback = $rowComment->reviewFeedbackCount();
				$reportFeedback =  $rowComment->reviewFeedbackReportCount();
				$helpTotalFeedback =  $rowComment->helpful_count;
				$reportFeedbackLast =  $rowComment->reportFeedbackLast();
			@endphp
			@if($helpTotalFeedback > 0)
				<div class="review-feedback-count mb-2 mt-2">
					<span class="help-count-in" data-count="{{$helpTotalFeedback}}">{{($helpTotalFeedback == 1 ) ? 'One person' : $helpTotalFeedback.' people'}}</span>  found this helpful
				</div>
			@endif
			<div class="review-feedback d-flex">
				{{--
				@if( $helpFeedback > 0 && $reportFeedback > 0)
				
					@if($reportFeedbackLast->type == "report_abuse")
						<div class="help-full-need pr-2 text-danger" data-report="0">
							Thank you for Reporting
						</div>
					@else
						<div class="review-feedback-count mb-2">
							<span class="help-full-need pr-2  text-primary" data-feedback="0" >
							<i class="fa fa-check" aria-hidden="true"></i>  This review was helpful
							</span>
						</div>
					@endif
				@else
				--}}
					@if($helpFeedback > 0)
						<span class="help-full-need pr-2  text-primary cureview-feedback " data-feedback="0" >
						<i class="fa fa-check" aria-hidden="true"></i>  This review was helpful 
						</span>

					@else
					<!--shows default button for proceed to for redirection of login page-->
						{{--
						@if(!Auth::check())
							<a href="{{url('login')}}?service_id={{$Service->basic_plans->id}}" class="help-full-need pr-2 text-primary">
						@else
							<a href="javascript:;" class="help-full-need pr-2 ajax-helpful cureview-feedback text-primary" data-type="helpful"  data-orderid="{{$rowComment->secret}}" data-feedback="1">
						@endif
							<span class="badge badge-info">Helpful</span>
						</a>
						--}}
						@if(Auth::check())
							@if($Service->uid != Auth::id())
								<a href="javascript:;" class="help-full-need pr-2 mt-2 ajax-helpful cureview-feedback text-primary" data-type="helpful"  data-orderid="{{$rowComment->secret}}" data-feedback="1"><span class="badge badge-info">Helpful</span></a>
							@endif
						@else 
							<a href="{{url('login')}}" class="help-full-need pr-2 mt-2 ajax-helpful cureview-feedback text-primary" data-type="helpful"  data-orderid="{{$rowComment->secret}}" data-feedback="1"><span class="badge badge-info">Helpful</span></a>
						@endif
					@endif
						
					{{--
					@if($reportFeedback > 0)
						<div class="help-full-need pr-2 text-danger cureview-report " data-report="0">
							Thank you for Reporting
						</div>
					@else
					@if(!Auth::check())
							<a href="{{url('login')}}?service_id={{$Service->basic_plans->id}}" class="help-full-need  text-danger pr-2">
						@else
							<a href="javascript:;" class="help-full-need pr-2 text-danger cureview-report ajax-helpful" data-type="report_abuse" data-report="1"  data-orderid="{{$rowComment->secret}}">
						@endif
								<span class="badge badge-danger">Report Abuse</span>
							</a>
					@endif
					
				@endif
				--}}
			</div>
			
			<div>
				<p class="comment-reply-text review-margin-seller" id='showComReply_{{$rowComment->id}}'>
					@if($rowComment->completed_reply)
					<i class="fa fa-reply" aria-hidden="true"></i>
					{{$rowComment->completed_reply}}
					@endif
				</p>
			</div>

		</div>
		<div class="col-md-12">
			<p class="pull-right">

				@if((count($rowComment->review_log) > 1) && (Auth::check()))

				@if($parent_uid == @$Service->uid)
				<button type="button" data-toggle="modal" data-target="#myModal_{{$rowComment->id}}" class="comment-reply-btn" href='#review-log-model-{{$rowComment->id}}'>Review Edit History
				</button>
				@endif
				@endif

				@if(Auth::check())
				@if( empty($rowComment->completed_reply) && (!empty($rowComment->completed_note)) && $rowComment->status= 'completed')
				@if($parent_uid == $Service->uid)
				<a class='pull-right openTextbox comment-reply-btn-a' id='replay_{{$rowComment->id}}' style=''> <i class="fa fa-reply" aria-hidden="true"></i> Reply </a>
				@endif
				@endif
				@endif
			</p>
		</div>


		@if((count($rowComment->review_log) > 1) && (Auth::check()))
		@if($parent_uid == $Service->uid)
		<div id="myModal_{{$rowComment->id}}" class="modal fade custommodel" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h4 class="modal-title">Review edit history log</h4>
					</div>
					<div class="modal-body">
						@foreach($rowComment->review_log as $key => $value)
						@php
						$reviewdata = json_decode($value->log);
						@endphp
						<div class="review-edit-log">
							<table>
								<tr>
									<td width="90%">{{$reviewdata->review}}</td>
									<td width="10%" class="text-right">
										{{$rowComment->seller_rating}}
									</td>
								</tr>
							</table>
							<p class="timestamp text-right">

								{{date('d M,Y h:i',strtotime($reviewdata->review_date))}}
							</p>
						</div>
						@endforeach
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>
		@endif
		@endif

		@if(Auth::check())
		<div class="col-md-12">
			{{ Form::open(['route' => ['replayComment'], 'method' => 'POST', 'id' => 'replayForm_'.$rowComment->id]) }}
			<div style='display:none' id='replayBox_{{$rowComment->id}}'>
				<div class="form-group">
					<textarea class="form-control" id='CommentReply_{{$rowComment->id}}' name='completed_reply' required></textarea>
				</div>
				<input type="hidden" name="id" value="{{$rowComment->id}}">
				<input type="hidden" name="username" value="{{$rowComment->user->username}}">
				<input type="hidden" name="sender" value="{{$parent_username}}">
				<input type="hidden" name="completed_note" value="{{$rowComment->completed_note}}">
				<button type="button" class="btn mb-2 button mid primary pull-right submitReplay_{{$rowComment->id}}" onclick="submitReplay(<?php echo $rowComment->id; ?>)">
					<img class="submit-reply-loader" src="{{url('public/frontend/images/loader.gif')}}" alt="" id='loader_{{$rowComment->id}}'>
					<span id='re_{{$rowComment->id}}'>Submit</span>
				</button>
			</div>
			<div class="alert alert-warning col-md-6" style="display: none;" id="warningBox_{{$rowComment->id}}">
				Please Insert Something In It.
			</div>
			{{ Form::close() }}
		</div>
		@endif
		@if(!$loop->last && Auth::user()->web_dark_mode == 1)
		<div class="div_border_bottom"></div>
		@endif
		@endforeach
	</div>
</div>

<div class="paginate-center">
	@if(count($Comment))
	{{ $Comment->appends(['seo_url' => $seo_url,
		'username' => $username])->links("pagination::bootstrap-4") }}
	@endif
</div>
<div id="review_modal" class="modal fade review-modal-pop" role="dialog">
	<div class="modal-dialog modal-dialog-centered modal_size">
		<div class="modal-content">
			<div class="modal-header custom_head">
				<button type="button" class=" close_left" data-dismiss="modal"><img class="close-img" src="{{url('public/frontend/assets/img/Close.png')}}"> Close</button>
			</div>
			<div class="modal-body custom_body">
				<div id="html-content-holder"></div>
			</div>
			<div class="modal-footer custom_foot">
				<span class="download_msg">Download this shareable image and spread the good news!</span>
				<a class="btn btn-primary float-right" id="download_review" download="share_review.png"><img class="down-img" src="{{url('public/frontend/assets/img/download.png')}}"> Download</a>
			</div>
		</div>
	</div>
</div>
@else 
<div class="empty_review_div text-center">
	<h6>No any reviews found</h6>
</div>
@endif