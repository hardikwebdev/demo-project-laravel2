@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Messages')
@section('content')

<div class="section-headline-wrap">
	<div class="section-headline">
		<h2>Sent Massages </h2>
		<p>Home<span class="separator">/</span><span class="current-section">Sent Massages</span></p>
	</div>
</div>

<!-- SECTION -->
<div class="section-wrap">
	<div class="section">
		<!-- CONTENT -->
		<div class="content">
			<div class="form-box-items" style="background-color: #fff;">
				<table class="table" id="order-table">
					<thead class="thead-default1">
						<tr>
							<th>SENDER</th>
							<th>LAST MESSAGE</th>
							<th style="min-width: 100px;">UPDATED</th>
						</tr>	
					</thead>
					<tbody>
						@foreach($Message as $row)
						<tr>
							<td style="width: 185px;">
								<a href="{{route('msg_details',$row->secret)}}" style="float: left;display: inline-block;margin-right: 5px;">
									<figure class="user-avatar small">
				                        <img src="{{get_user_profile_image_url($row->toUser)}}" alt="profile-image">
				                    </figure>
								</a>
								<a href="{{route('msg_details',$row->secret)}}" style="float: left;display: inline-block;">{{$row->toUser->username}}</a>
							</td>
							<td>
								<?php
								if(strlen($row->last_message) > 70){
									echo substr(strip_tags($row->last_message), 0, 70).'...';
								}else{
									echo $row->last_message;
								}
								?>
							</td>
							<td>
								@if($row->updated_at)
								{{date('d M Y',strtotime($row->updated_at))}}
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
				<div class="text-center">{{ $Message->links() }}</div>
				<!-- /PAGER -->
			</div>
		</div>
		<div class="sidebar">
			@include('frontend.message.sidebar')
		</div>
		<!-- CONTENT -->
	</div>
</div>
<!-- /SECTION -->

@endsection

@section('css')
 <link rel="stylesheet" href="{{front_asset('css/bootstrap.min.css')}}">
@endsection

@section('scripts')

@endsection