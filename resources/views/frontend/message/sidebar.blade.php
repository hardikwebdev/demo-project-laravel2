<?php 
$currentPage = \Request::route()->getName();
?>

<ul class="dropdown hover-effect secondary">
	<li class="dropdown-item @if($currentPage=='users'){{'active'}}@endif">
		<a href="{{url('messaging/conversations')}}">Conversations</a>
	</li>
	<!-- <li class="dropdown-item @if($currentPage=='msg_sent'){{'active'}}@endif">
		<a href="{{route('msg_sent')}}">Sent</a>
	</li> -->
	<!-- <li class="dropdown-item @if($currentPage=='msg_archived'){{'active'}}@endif">
		<a href="{{route('msg_archived')}}">Archived</a>
	</li> -->
</ul>