@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Message-box')
@section('content')
@section('css')
<link rel="stylesheet" href="{{front_asset('css/bootstrap.min.css')}}">
<link href="{{asset('resources/assets/sass/chat.css')}}" rel="stylesheet">   
@endsection

<div class="section-headline-wrap">
	<div class="section-headline">
		<h2>Massages </h2>
		<p>Home<span class="separator">/</span><span class="current-section">Massages</span></p>
	</div>
</div>

<!-- SECTION -->
<div class="section-wrap">
	<div class="section">
		<div class="inbox_box">	
			<div class="inbox_aside">
				<div class="aside_head">
					<h4>Inbox</h4>
				</div>
				<div class="aside_user">
					@foreach($Message as $row)
					<div class="row">
						<div class="aside-profile-image">
							<a href="javascript:void(0);" class="user-name-list" data-id="{{$row->id}}">
								<figure class="user-avatar miduim">
									@if($parent_username == $row->fromUser->username)
									<img src="{{get_user_profile_image_url($row->toUser)}}" alt="profile-image">
									@else
									<img src="{{get_user_profile_image_url($row->fromUser)}}" alt="profile-image">
									@endif
								</figure>
							</a>
						</div>
						<div class="aside-user-name">
							<a href="javascript:void(0);" class="user-name-list" data-id="{{$row->id}}">
								@if($parent_username == $row->fromUser->username)
								<h5>{{$row->toUser->username}}</h5>
								@else
								<h5>{{$row->fromUser->username}}</h5>
								@endif
							</a>
						</div>
					</div>
					@endforeach
				</div>
			</div>
			<div class="inbox_contant">
				<div class="contant_head">
					<h4>Message</h4>
				</div>
				<div class="contant_message">
				</div>
				<div class="chat-textbox">
					<div class="row">
						<div class="text-box">
							<input type="text" name="text-message" id="text-message" placeholder="Enter your message">
						</div>
						<div class="sent-button">
							<a href="javascript:void(0);" data-id=""  class="sent-message sent-button-design">Sent</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

@endsection


@section('scripts')
<script type="text/javascript">
	$(document).ready(function(){
		$(document).on("click",".user-name-list",function(){
			$('.row').removeClass('active');
			$(this).parents('div .row').addClass("active");
			var msg_id = $(this).attr('data-id');
			var username = $(this).find("h5").text();
			$.ajax({
				method:"GET",
				url:'{{url('/')}}/message/detail_ajax/'+msg_id,
				success:function(data)
				{
					if(data.success == true)
					{
						$('.contant_message').html("");
						$('.contant_head').find('h4').text(username);
						for (var i = 0; i < data.messageDetail.length; i++) 
						{
							if(data.messageDetail[i].to_user.id != data.user_id)
							{
								$('.contant_message').append('<div class="talk-box"><div class="talk-bubble tri-right right-top"><div class="talktext"><p class="talktext-right">'+data.messageDetail[i].message+'</p><p class="time-right">'+data.messageDetail[i].created_at+'</p></div></div></div>');
							}
							else
							{
								$('.contant_message').append('<div class="talk-box"><div class="talk-bubble tri-right left-top"><div class="talktext"><p class="talktext-left">'+data.messageDetail[i].message+'</p><p class="time-left">'+data.messageDetail[i].created_at+'</p></div></div></div>');
							}
						}
						$('.sent-message').attr("data-id",msg_id);
					}
					else
					{
						$('.contant_message').html("");
						$('.contant_head').find('h4').text("message");
						$('.sent-message').attr("data-id",'');
					}
				}
			});
		});
		$(document).on("click",".sent-message",function(){
			var msg = $("#text-message").val();
			var msg_id = $(this).attr("data-id");
			if(msg != "" && msg_id != "")
			{
				method:"GET",
				url:'{{url('/')}}/message/detail_ajax/'+msg_id,
				data:{}
				success:function(data)
				{
				}
			}
		});
	})
</script>
@endsection