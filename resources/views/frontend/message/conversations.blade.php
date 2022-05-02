@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Messages')
@section('content')

<section class="profile-header filter-header message-converstion">
	<div class="container">
		<div class="profile-detail">
			<div class="row">
				<div class="container cus-filter">
					<h2 class="heading mb-3">Manage Conversations</h2>	
					@if(Auth::check() && Auth::user()->is_premium_seller() == true)
					<a href="{{ route('canned_replay') }}" class="btn btn-info mb-3" style="float: right">Manage Canned Replies</a>
					@endif
				</div>    
			</div>    
		</div>    
	</div>
</section>
<!-- Conversations -->

<section class="block-section">
	<div class="container">
		<div class="messagebox custom-margin-top">
			<div class="cus-container-two">    

				<div class="messaging chat-screen">

					<table class="manage-sale-tabel">
						<thead>
							<tr class="manage-sale-head">
								<td class="text-center">SENDER</td>
								<td class="text-center">LAST MESSAGE</td>
								<td class="text-center">FOR SERVICE</td>
								<td class="text-center">UPDATED</td>
							</tr>
						</thead>
						<tbody>
							@foreach($Message as $row)
							<tr>
								<td class="text-center">
									<div class="text-align-webkit-center">
										<a href="{{route('msg_details',[$row->secret])}}" style="display: inline-block;margin-right: 5px;">
											<figure class="user-avatar small">
												@if($row->is_admin == 1)
													<img src="{{url('public/frontend/assets/img/logo/favicon.png')}}" alt="profile-image" class="admin_profile">
												@else
													@if($parent_username == $row->fromUser->username)
														<img src="{{get_user_profile_image_url($row->toUser)}}" alt="profile-image">
													@else
														<img src="{{get_user_profile_image_url($row->fromUser)}}" alt="profile-image">
													@endif
												@endif
											</figure>
										</a>
										@if($row->is_admin == 1)
											demo Support Team
											<img src="{{url('public/frontend/assets/img/logo/demo-badge.png')}}" alt="profile-image" class="badge_img">
										@else
											@if($parent_username == $row->fromUser->username)
												<a href="{{route('viewuserservices',$row->toUser->username)}}" style="display: inline-block;">
													{{$row->toUser->username}}
												</a>
												@if(App\User::checkPremiumUser($row->toUser->id) == true)
												<img src="{{ url('public/frontend/images/Badge.png') }}" alt="profile-image" class="premiumBadgeHeader1" height="10"></img>
												@endif
											@else
												<a href="{{route('viewuserservices',$row->fromUser->username)}}" style="display: inline-block;">
													{{$row->fromUser->username}}
												</a>
												@if(App\User::checkPremiumUser($row->fromUser->id) == true)
												<img src="{{ url('public/frontend/images/Badge.png') }}" alt="profile-image" class="premiumBadgeHeader1" height="10"></img>
												@endif
											@endif
										@endif
									</div>
								</td>
								<td class="text-center custom-word-break">
									<a href="{{route('msg_details',[$row->secret])}}" style="display: inline-block;">
										@if($row->last_message != '')
										@emojione($row->last_message)
										@else
										-
										@endif
									</a>
								</td>
								<td class="text-center">

									{{-- <a href="{{route('services_details',[$row->fromUser->username,$row->service->seo_url])}}" style="display: inline-block;">
										{{$row->fromUser->username}} --}}
										{{isset($row->service['title'])?$row->service['title']:'-'}}
									{{-- </a> --}}
								</td>

								<td class="text-center">
									@if($row->updated_at)
									{{date('M d Y h:i A',strtotime($row->updated_at))}}
									@else
									-
									@endif
								</td>
							</tr>
							@endforeach
							@if(count($Message)==0)
							<tr>
								<td colspan="7" class="text-center">
									No conversations found
								</td>
							</tr>
							@endif
						</tbody>
					</table>
					<div class="clearfix"></div>

					<!-- PAGER -->
					<div class="text-center">{{ $Message->links("pagination::bootstrap-4") }}</div>
				</div>
			</div>
		</div>
	</div>        
</section>  
@endsection

@section('css')
<style type="text/css">
	#order-table img{
		vertical-align: middle;
	}

	@media screen and (max-width: 735px){
		.custom-table{
			display: block !important;
		}
	}
	.badge_img{
		width: 15px;
	}
	.admin_profile {
		border-radius: 50% !important;
		width: 95px !important;
		height: 30px;
		object-fit: cover;
		object-position: 3px;
	}
</style>
@endsection

@section('scripts')
@endsection