@php
	use App\User;
	if(Auth::user()->is_premium_seller() == true) {
		$maxFilesize = 250;	
	} else {
		$maxFilesize = 100;	
	}
	$is_softban = User::is_soft_ban();
@endphp
@extends('layouts.frontend.main')
@section('pageTitle','demo - Order Details')
@section('content')

<!-- Masthead -->
<header class="masthead text-white font-lato"> {{-- masthead  --}}
    {{-- <div class="overlay"></div> --}}
    <div class="bg-dark w-100">
        <div class="container pt-2 pb-3 d-flex flex-column flex-lg-row justify-content-between align-items-center">
            <div>
                {{-- Order No --}}
                <h1 class="font-24 font-weight-bold font-lato text-white mb-0 py-3">Order #{{$Order->order_no}} 
                    {{-- Order Status --}}
                    {{-- <span class=" bg-primary-blue font-14 py-1 px-3 border-radius-12px ml-md-3"> --}}
                        @if($Order->status =='new') 
						<span class="align-middle font-14 bg-warning py-1 px-3 border-radius-12px ml-md-3 text-uppercase">In Complete</span>
						@endif
                        @if($Order->status =='on_hold')
						<span class="rejected align-middle font-14 py-1 px-3 border-radius-12px ml-md-3 text-uppercase">On Hold</span>
						@endif

                        @if($Order->status =='active')
						<span class="align-middle bg-primary-blue font-14 py-1 px-3 border-radius-12px ml-md-3 text-uppercase">In Progress</span>
						@endif

                        @if($Order->status =='cancelled') 
						<span class="align-middle font-14 bg-light-gray py-1 px-3 border-radius-12px ml-md-3 text-uppercase">Cancelled</span>
						@endif 

                        @if($Order->delivered_date !='' && $Order->status !='completed' && $Order->status !='cancelled' && $Order->status !='in_revision')
						<span class="align-middle bg-dark-green font-14 py-1 px-3 border-radius-12px ml-md-3 text-uppercase">Delivered</span> 
						@endif 

                        @if($Order->status == 'in_revision') 
						<span class="bg-primary align-middle font-14 py-1 px-3 border-radius-12px ml-md-3 text-uppercase">In Revision</span>
						@endif 

                        @if($Order->status =='completed') 
						<span class="align-middle font-14 bg-green py-1 px-3 border-radius-12px ml-md-3 text-uppercase">Completed</span> 
						@endif 

                        @if($Order->status !='new' && $Order->status !='on_hold' && $Order->status !='cancelled' && $Order->is_recurring == 0)
                        @if(($Order->delivered_date == '' && strtotime($Order->end_date) < time()) || ($Order->delivered_date !='' && strtotime($Order->delivered_date) > strtotime($Order->end_date)))
                        <span class="pl-2 pr-2">|</span> <span class="bg-danger align-middle font-14 py-1 px-3 border-radius-12px text-uppercase">Order is late</span>
                        @endif
                        @endif

                        @if($Order->is_dispute)
                        @if($Order->dispute_order->status == 'open' && $is_softban == 0)
                        <span class="pl-2 pr-2">|</span><span class="align-middle font-14 bg-primary-blue py-1 px-3 border-radius-12px text-uppercase">In dispute</span>
                        @endif
                        @endif
                    {{-- </span> --}}
                </h1>
            </div>
            {{-- Timer --}}
            @php
			if($Order->is_recurring == 0){
				$end_date = $Order->end_date;
				$diffDate = new DateTime($end_date);
			}else{
				$end_date = $Order->subscription->expiry_date;
				$diffDate = new DateTime($Order->subscription->expiry_date);
			}

			$now = new DateTime();
			$interval = $diffDate->diff($now);
			@endphp
            @if($Order->status =='active' && strtotime($end_date) >= time())
            <input hidden="" type="text" id="seller_countdown" data-days="{{$Order->new_interval_days}}" data-hours="{{$Order->new_interval_hours}}" data-minutes="{{$Order->new_interval_minutes}}" value="{{ $Order->new_end_date }}">
            <div class="d-flex" id="countdown">
                <div class="text-center countdown-animation countdown-label-days">
                    <p class="mb-0 font-11 text-color-4 timer pr-4 mr-md-3 font-weight-normal">Days</p>
                    <p class="mb-0 font-36 text-color-9 font-weight-800">
                        <b class="countdown-item days">0</b>
                        <b class='px-2 px-md-3'>:</b>
                    </p>
                </div>
                <div class="text-center countdown-animation countdown-label-hours">
                    <p class="mb-0 font-11 text-color-4 timer  pr-4 mr-md-3 font-weight-normal">Hours</p>
                    <p class="mb-0 font-36 text-color-9 font-weight-800">
                        <b class="countdown-item hours">0</b>
                        <b class='px-2 px-md-3'>:</b>
                    </p>
                </div>
                <div class="text-center countdown-animation countdown-label-minutes">
                    <p class="mb-0 font-11 text-color-4 timer  pr-4 mr-md-3 font-weight-normal">Minutes</p>
                    <p class="mb-0 font-36 text-color-9 font-weight-800">
                        <b class="countdown-item minutes">0</b>
                        <b class='px-2 px-md-3'>:</b>
                    </p>
                </div>
                <div class="text-center countdown-animation countdown-label-seconds">
                    <p class="mb-0 font-11 text-color-4 timer font-weight-normal">Seconds</p>
                    <p class="mb-0 font-36 text-color-9 font-weight-800">
						<b class="countdown-item seconds">0</b>
                    </p>
                </div>
            </div>
            @endif
			{{-- End Timer --}}
        </div>
    </div>
</header>

{{-- Content --}}
<section class="mt-5 transactions-table pb-5">

	{{-- Order Delivered Message --}}
	@if($Order->delivered_date !='' || $Order->status == 'completed')
	<div class="container mt-4 mt-md-0 font-lato">
        <div class="alert-success mb-3 mx-auto py-3 w-75">
            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center">
				@if($Order->delivered_date !='' || $Order->status == 'completed')
				<div>
                    <img src="{{url('public/frontend/images/Tik-mark.png')}}" class="img-fluid px-3" alt="Default Alt Tag for this page">
                </div>
				@endif
				@if($Order->delivered_date !='' && $Order->status != 'completed')
                <div class="text-center text-md-left">
                    <p class="mb-0 font-20 text-color-8 font-weight-bold">Your Order has been delivered</p>
                    <span class="font-16 font-weight-normal text-color-8">Please review and share experience.</span>
                </div>
				@endif
				@if($Order->status =='completed')
				<div class="text-center text-md-left">
					<p class="mb-0 font-20 text-color-8 font-weight-bold">Congratulations! This order is complete.</p>
					<span class="font-16 font-weight-normal text-color-8">Review your experience with this buyer.</span>
				</div>
				@endif
            </div>
        </div>
    </div>
	@endif

	@if(Session::has('errorSuccess'))
	<div class="container mt-4 mt-md-0 font-lato">
		<div class="alert-status mx-auto text-center py-2">
			<p class="mb-0 font-14 text-color-1 font-weight-bold"> <i class="fas fa-info-circle font-14 mx-2 icon-info" aria-hidden="true"></i>{{Session::get('errorSuccess')}}</p>
		</div>
	</div>
	@endif
	{{-- END Order Delivered Message --}}

	{{-- Begin Order Extension Approved Msg --}}
	@if(Session::has('successMessage'))
	<div class="container mt-4 mt-md-0 font-lato">
        <div class="alert-success mb-3 mx-auto py-3 w-75">
            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center">
				<div>
                    <img src="{{url('public/frontend/images/Tik-mark.png')}}" class="img-fluid px-3" alt="Default Alt Tag for this page">
                </div>
				<div class="text-center text-md-left">
					@if (Session::get('successMessage') == "approved_extend_due_date")
						<p class="mb-0 font-20 text-color-8 font-weight-bold">Thank you for accepting the offer!</p>
						<span class="font-16 font-weight-normal text-color-8">Delivery due date has been updated.</span>
					@endif
				</div>
            </div>
        </div>
    </div>
	@endif
	{{-- END Order Extension Approved Msg --}}
	
	@if($Order->delivered_date !='')
	@if($Order->is_dispute)
	<div class="container text-center font-16 font-weight-normal">
		@if($Order->dispute_order->status == 'approved')
		<div class="alert alert-success alert-dismissible mb-3 mt-3 mx-auto w-75">
			<span class="text-color-8"> Your dispute is <strong>approved</strong></span>.
			<a href="{{ route('viewDispueMessage',[$Order->dispute_order->messages->id])}}"> view details</a>
		</div>
		@elseif($Order->dispute_order->status == 'in progress')
		<div class="alert alert-primary alert-dismissible mb-3 mt-3 mx-auto w-75">
			Your dispute is <strong>in progress</strong>. 
			<a href="{{ route('viewDispueMessage',[$Order->dispute_order->messages->id])}}"> view details</a>
		</div>
		@elseif($Order->dispute_order->status == 'rejected')
		<div class="alert alert-danger alert-dismissible mb-3 mt-3 mx-auto w-75">
			Your dispute is 
			@if($Order->dispute_order->cancelled_by == 2)<strong>cancelled</strong>.
			@else <strong>rejected</strong>.
			@endif 
			<a href="{{ route('viewDispueMessage',[$Order->dispute_order->messages->id])}}"> view details</a>
		</div>
		@else
		<div class="alert alert-warning alert-dismissible mb-3 mt-3 mx-auto w-75">
			Dispute with <strong>#{{ $Order->dispute_order->dispute_id}}</strong> created for this service.
			<a href="{{ route('viewDispueMessage',[$Order->dispute_order->secret])}}"> view details</a>
		</div>
		@endif
	</div>
	@endif
	@endif

    <div class="container cart_page font-lato">
        <div class="row mt-5">
            <div class="col-12 col-md-8 col-xl-9">
					@if($Order->status =='cancelled')
					<div class="alert alert-warning">
						<h6>
							Order cancelled on {{date('M d,Y H:i',strtotime($Order->cancel_date))}}
						</h6> 
						Reason : {{$Order->cancel_note}}
					</div>
					@endif
					
					@if($Order->status == 'active' && count($Order->order_extend_requests) && ($Order->order_extend_requests[0]->id != null) && $Order->order_extend_requests[0]->is_accepted == 0) 
					@php 
					$extendDays = $Order->order_extend_requests[0]->extend_days;
					if($extendDays == 1){
						$extendDays = $extendDays.' day';
					}else{
						$extendDays = $extendDays.' days';
					}
					@endphp
					<div class="row alert-warning py-3 px-2 mx-0 mb-4">
						<div class="col-12">
							<div class="d-flex">
								<div class="pt-1">
									<img src="{{url('public/frontend/images/alert-triangle.png')}}" alt="">
								</div>
								<div class="ml-3">
									<p class="mb-0 font-20 text-color-2 font-weight-bold">{{ucfirst($Order->seller->Name)}} has offered to exted the delivery time of the order for {{$extendDays}}. </p>
									<p class="mb-0 font-16 text-color-2">Reason: {{ucfirst($Order->order_extend_requests[0]->seller_note)}} </p>
								</div>
							</div>
						</div>
						<div class="col-12  offset-lg-1 pl-lg-4 pl-xl-2">
							<div class="row mt-3 align-items-center">
								<div class="col-12 col-xl-6">
									<p class="font-14 text-color-3 ml-5 pl-2 ml-lg-0 pl-lg-0">Please respond within the next 3 days, or the request will be automatically withdrawn.</p>
								</div>
						 
								<div class="col-5 col-xl-2 text-right  text-xl-right">
									<a class="text-color-1 font-14 font-weight-bold" href="{{route('accept_extend_order_date',[$Order->id,$Order->order_extend_requests[0]->id,'2'])}}">Reject</a>
								</div>
								<div class="col-7 col-xl-3 text-right text-lg-center text-xl-right">
									{{ Form::open(['route' => ['accept_extend_order_date',$Order->id,$Order->order_extend_requests[0]->id,'1'], 'method' => 'POST','class'=>'custom-form','id'=>'frmCancelOrder']) }}
									<button type="submit" class="btn text-white bg-primary-blue border-radius-6px py-2 px-5 font-14">Accept</button>
									{{ Form::close() }}
								</div>
							</div>
						</div>
					</div>
				@endif
				
				{{-- Review --}}
				@if($Order->status =='completed' || ($Order->status =='cancelled' && $Order->seller_rating == 1))
				@if($Order->parent_id == 0)
					<div class=" row summary py-3 px-2 mx-0 mb-4">
						<div class="col-12">
							<h1 class="font-20 text-color-2 font-weight-bold text-center text-md-left pl-md-3">Review</h1>
						</div>
						<div class="col-12 mt-2">
							<div class="d-flex flex-column flex-md-row align-items-center">
								<div class="align-self-start">
									<img src="{{get_user_profile_image_url($Order->user)}}" class="review-profile" alt="">
								</div>
								<div class="ml-3 text-center text-md-left mt-3 mt-md-0">
									{!! displayRating($Order->seller_rating ,1) !!}
									<p class="mb-1 font-18 text-color-2 mt-2">
										<span class="readless-text-review{{$Order->secret}}">{{string_limit($Order->completed_note,150)}}</span>
										@if(strlen($Order->completed_note) > 150)
											<span class="d-none readmore-text-review{{$Order->secret}}">{{$Order->completed_note}}</span>
											<label class="text-primary btn-link read-more" id="readmore-review{{$Order->secret}}" data-id="review{{$Order->secret}}">Read More</label>
											<label class="text-primary btn-link read-less d-none" id="readless-review{{$Order->secret}}" data-id="review{{$Order->secret}}">Less</label>
										@endif
									</p>
									<p class="mb-0 font-14 text-color-4 font-italic">{{$Order->user->username}}</p>
								</div>
							</div>
							@if($Order->completed_reply != null)
							<div class="ml-md-5 pl-md-5">
								<p class="ml-md-3 mb-1 font-16 text-left text-color-2 mt-2 font-weight-bold">
									<i class="fa fa-reply custom-top-space  custom-reply-text"></i> 
									<span class="readless-text-{{$Order->secret}}">{{string_limit($Order->completed_reply,150)}}</span>
									@if(strlen($Order->completed_reply) > 150)
										<span class="d-none readmore-text-{{$Order->secret}}">{{$Order->completed_reply}}</span>
										<label class="text-primary btn-link read-more" id="readmore-{{$Order->secret}}" data-id="{{$Order->secret}}">Read More</label>
										<label class="text-primary btn-link read-less d-none" id="readless-{{$Order->secret}}" data-id="{{$Order->secret}}">Less</label>
									@endif
								</p>
							</div>
							@endif
						</div>
						@if($Order->seller_rating == 0)
						<div class="col-12 text-center text-md-right">
							<button class="btn text-white bg-primary-blue border-radius-6px py-1 px-3 font-14" data-toggle="modal" data-target="#updatereview">Give Rating</button>
						</div>
						@endif
					</div>
				@endif
				@endif
				{{--END Review --}}

				{{-- Tip Form --}}
				@if( $Order->status == 'completed' && count($Order->order_tip) == 0 && $Order->parent_id == 0 && Auth::user()->parent_id == 0 && $is_softban == 0)
					{!! Form::open(['route' => ['orderTipCheckout'],'method' => 'post', 'id' => 'frm_tip_checkout']) !!}
					{!! Form::hidden('order_id',$Order->secret) !!}
					<div class="row summary mx-0 mb-4 py-3 px-2 order-tip-section">
						<div class="col-12">
							<h1 class="font-20 text-color-2 font-weight-bold">Show your appreciation to {{$Order->seller->username}} by leaving a tip</h1>
							<p class="font-16 text-color-3">Choose an option below. The seller will receive 100% of this amount.</p>
						</div>
						<div class="col-12">
							<table class="table table-borderless table-responsive-sm summary border-separate tip-table">
								<tbody class="text-center">
									<tr>
										<td class="font-14 text-color-2 p-0 border-right-gray" width="20%">
											<input name="tip_amount" value="5" type="radio" hidden checked class="btn-check" id="tip-btn-5" autocomplete="off">
											<label class="tip-button selected" for="tip-btn-5">$5</label>
										</td>
										<td class="font-14 text-color-2 p-0 border-right-gray" width="20%">
											<input name="tip_amount" value="10" type="radio" hidden class="btn-check" id="tip-btn-10" autocomplete="off">
											<label class="tip-button" for="tip-btn-10">$10</label>
										</td>
										<td class="font-14 text-color-2 p-0 border-right-gray" width="20%">
											<input name="tip_amount" value="15" type="radio" hidden class="btn-check" id="tip-btn-15" autocomplete="off">
											<label class="tip-button" for="tip-btn-15">$15</label>
										</td>
										<td class="font-14 text-color-2 p-0 border-right-gray" width="20%">
											<input name="tip_amount" value="20" type="radio" hidden class="btn-check" id="tip-btn-20" autocomplete="off">
											<label class="tip-button" for="tip-btn-20">$20</label>
										</td>
										<td class="font-14 text-color-2 p-0" width="20%">
											<input name="tip_amount" value="25" type="radio" hidden class="btn-check" id="tip-btn-25" autocomplete="off">
											<label class="tip-button" for="tip-btn-25">$25</label>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="col-12">
							<div class="row align-items-center justify-content-end mt-4">
								<div class="-12 col-md-4 col-lg-3 col-xl-3">
									<div class="form-group">
										<select class="form-control" name="payment_from" id="payment_from">
											<option value="3">Skrill</option>
											<option value="1">Paypal</option>
											<option value="2">Credit Card</option>
										</select>
									</div>
								</div>
								<div class="-12 col-md-4 col-lg-3 col-xl-4">
									@if(Auth::user()->earning > 0)
									<div class="input-container from_wallet_amount">
										<label class="cus-checkmark from-wallet-chk">  
											<input type="checkbox" id="from_wallet" name="from_wallet" class="cus-checkmark from-wallet-chk" value="1" checked="">
											<label for="from_wallet" class="label-check">
												Use From Wallet (<b>${{dispay_money_format(Auth::user()->earning)}}</b>)
											</label>
											<span class="checkmark"></span>
										</label>
									</div>
									@endif
								</div>
								<div class="col-12 col-md-4 col-lg-3 col-xl-2 text-center mt-3 mt-md-0 order-2 order-md-1">
									<button type="button" class="btn bg-transparent text-color-1 border-radius-6px w-100 py-2" id="no_tips">No Thanks</button>
								</div>
								<div class="col-12 col-md-8 col-lg-5 col-xl-3 text-center  order-1 order-md-2">
									<button type="submit" name="Send Tip" class="btn text-white bg-primary-blue border-radius-6px py-2 px-5 font-14 send-request-buttom-new">Send Tip</button>
								</div>
							</div>
						</div>
					</div>
					{{ Form::close() }}
				@endif
				{{-- END Tip Form --}}

				{{-- Order Information --}}
                <div class="row summary py-3 px-2 mx-0" id="accordian">
                    <div class="col-12 col-md-3 text-center text-md-left">
						@if($Order->is_custom_order == 0)
                        	<img src="{{isset($Order->service->images->first()->thumbnail_media_url)? $Order->service->images->first()->thumbnail_media_url: url('public/frontend/assets/img/No-image-found.jpg')}}" class="img-fluid order-service-single-image" alt="">
						@else
                        	<img src="{{url('public/frontend/images/customorderthumb.jpg')}}" class="img-fluid order-service-single-image" alt="">
						@endif
					</div>
                    <div class="col-12 col-md-9">
                        <div class="row">
                            <div class="col-12 col-xl-9">
                                <p class="mb-0 font-20 font-weight-bold text-color-2 min-height-55-px">
									@if($Order->service->is_custom_order == 0)
										@if($Order->is_job == 1)
											<a href="{{route('show.job_detail',[$Order->service->seo_url])}}" class="text-color-2" target="_blank">
												<span class="text-capitalize">{{($Order->package_name)? $Order->package_name.' - ' : ''}}</span> {{ $Order->service->title }}
											</a>
										@else
											<a href="{{route('services_details',[$Order->seller->username,$Order->service->seo_url])}}" class="text-color-2" target="_blank">
												<span class="text-capitalize">{{($Order->plan_type)? $Order->plan_type.' - ' : ''}}</span> {{ $Order->service->title }}
											</a>
										@endif
									@else
										<span class="text-capitalize">{{($Order->package_name)? $Order->package_name.' - ' : ''}}</span> {{ $Order->service->title }}
									@endif
								</p>
                                <div class="py-0">
                                    <span class="font-14 text-color-4 pr-2 border-right border-gray">
										<span>Quantity</span>
										<span class="mx-2 text-color-2 font-weight-bold">{{$Order->qty}}</span>
									</span>
									<span class="font-14 text-color-4 pl-2">
										<span>Duration</span>
										<span class="mx-2 text-color-2 font-weight-bold">{{$Order->delivery_days}} Days</span>
									</span>
								</div>
								<div class="py-1">
                                    <span class="font-14 text-color-4 pr-2 border-right border-gray"><span>Seller</span><a href="{{route('viewuserservices',$Order->seller->username)}}"><span class="mx-2 text-color-1">{{$Order->seller->username}} </span></a></span>
                                    <span class="font-14 text-color-4 ml-2"><span>Delivery due date</span><span class="mx-md-2 text-color-2 font-weight-bold"> {{date('M d,Y',strtotime($Order->end_date))}}</span></span>
                                </div>
                            </div>
                            <div class="col-12 col-xl-3 text-right d-flex flex-row flex-xl-column justify-content-between mt-3 mt-xl-0">
                                <p class="mb-0 text-color2 font-24 font-weight-bold">${{$Order->order_total_amount}}</p>
                                <div class="pt-xl-3">
                                    <button class="btn font-14 text-color-1 bg-transparent font-weight-bold arrow-down-btn" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                        View more
                                        <i class="fas fa-chevron-down arrow-down font-10 ml-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
					{{-- Order Details --}}
                    <div id="collapseOne" class="collapse w-100" aria-labelledby="headingOne" data-parent="#accordion">
                        <div class="col-12 pt-3">
                            <i class="far fa-clock pr-2 text-color-4"></i>
                            <span class="font-14 text-color-4">You placed the order</span>
                            <span class="font-14 font-weight-bold text-color-2 pl-2">{{date('M d, Y',strtotime($Order->created_at))}}</span>
                        </div>
                        <div class="col-12 mt-3 pt-3 border-top-gray ">
                            <h1 class="font-20 text-color-2 font-weight-bold">What’s included?</h1>
                            <h1 class="font-16 text-color-2 font-weight-normal">{{$Order->package_name}}</h1>
                            <div class="mt-3">
                                @if($Order->no_of_revisions != null)
                                <p class='mb-0 text-color-3 font-14'><i class="fas fa-check text-color-1 mr-2"></i>
									@if($Order->no_of_revisions == -1) Unlimited @else {{$Order->no_of_revisions}}x @endif revisions
								</p>
                                @endif
                                <p class='mb-0 text-color-3 font-14 ml-4'>{{$Order->offering_details}}</p>
                                @php
                                    $extra = 0;
                                    $total_delivery_days = $Order->delivery_days;
                                @endphp
                                @foreach($Order->extra as $row)
                                    @php
                                    $extra += $row->qty*$row->price;
                                    $total_delivery_days += $row->delivery_days;
                                    @endphp
                                        <p class="mb-0 text-color-3 font-14 mt-2"><i class="fas fa-check text-color-1 mr-2" aria-hidden="true"></i>Add Ons Item <span class="font-14 font-weight-bold text-color-2 ml-4">{{$row->title}}</span></p>
                                        <p class="mb-0 text-color-3 font-14 ml-4"> Quantity <span class="font-14 font-weight-bold text-color-2 ml-4">{{$row->qty}}</span></p>
                                        <p class="mb-0 text-color-3 font-14 ml-4"> Duration <span class="font-14 font-weight-bold text-color-2 ml-4">{{$row->delivery_days}}</span> Days</p>
                                @endforeach
                            </div>
							{{-- Coupen Applied --}}
							@if($Order->coupon_applied)
							<p class='mb-0 text-color-3 font-14'><i class="fas fa-check text-color-1 mr-2"></i>
								Coupon Code <span class="font-14 font-weight-bold text-color-2 ml-4">{{$Order->coupon_applied->coupan_code}}</span>
							</p>
							@endif
							@if($Order->discount_priority)
								@foreach (json_decode($Order->discount_priority) as $priority)
									@if($priority->discount_type == 'reorder_promo')
										@if($Order->reorder_discount_amount)
										<p class='mb-0 text-color-3 font-14 ml-4'>
											{{$priority->title}} <span class="font-14 font-weight-bold text-color-2 ml-4">${{$Order->reorder_discount_amount}}</span>
										</p>
										@endif
									@elseif($priority->discount_type == 'coupan')
										@if($Order->coupon_discount)
										<p class='mb-0 text-color-3 font-14 ml-4'>
											{{$priority->title}} <span class="font-14 font-weight-bold text-color-2 ml-4">${{$Order->coupon_discount}}</span>
										</p>
										@endif
									@elseif($priority->discount_type == 'volume_discount')
										@if($Order->volume_discount)
										<p class='mb-0 text-color-3 font-14 ml-4'>
											{{$priority->title}} <span class="font-14 font-weight-bold text-color-2 ml-4">${{$Order->volume_discount}}</span>
										</p>
										@endif
									@elseif($priority->discount_type == 'combo_discount')
										@if($Order->combo_discount)
										<p class='mb-0 text-color-3 font-14 ml-4'>
											{{$priority->title}} <span class="font-14 font-weight-bold text-color-2 ml-4">${{$Order->combo_discount}}</span>
										</p>
										@endif
									@endif
								@endforeach
							@else
								{{-- Display Old Orders discount --}}
								@if($Order->reorder_discount_amount)
								<p class='mb-0 text-color-3 font-14 ml-4'>
									Re-order Promo Discount <span class="font-14 font-weight-bold text-color-2 ml-4">${{$Order->reorder_discount_amount}}</span>
								</p>
								@endif

								@if($Order->coupon_discount)
								<p class='mb-0 text-color-3 font-14 ml-4'>
									Coupon Discount <span class="font-14 font-weight-bold text-color-2 ml-4">${{$Order->coupon_discount}}</span>
								</p>
								@endif
							@endif
							{{-- END Coupen Applied --}}
                        </div>
                    </div>
					{{-- END Order Details --}}
                </div>
				
				{{-- Order Requirement --}}
                <div class="row summary mt-4 mx-0">
                    <div class="col-12 px-0">
                        <div id="accordion">
                            <div class="card border-0 px-3">
                                <div class="py-4" id="headingOne">
                                    <div class="d-flex flex-column flex-md-row align-items-end justify-content-between ">
                                        <div class="d-flex align-items-center align-self-start">
                                            <div>
                                                <img src="{{url('public/frontend/images/clipboard.png')}}" class="bg-light-gray-f0 p-3 rounded-circle" alt="">
                                            </div>
                                            <div class="ml-3">
                                                <p class="mb-0 font-18 text-color-2 font-weight-bold">Order Requirements</p>
                                                <p class="mb-0 font-16 text-color-3">You have filled out the requirements</p>
                                            </div>
                                        </div>
                                        <h5 class="mb-0">
                                            <button class="btn font-14 text-color-1 bg-transparent font-weight-bold arrow-down-btn" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false  " aria-controls="collapseOne">
                                                Show Requirements
                                                <i class="fas fa-chevron-down arrow-down font-10 ml-2"></i>
                                            </button>
                                        </h5>
                                    </div>
                                </div>

                                <div id="collapseTwo" class="collapse" aria-labelledby="headingOne" data-parent="#accordion">
                                    <div class="card-body px-2 unset-min-height">
                                        <div class="pb-2 border-bottom">
                                            <i class="far fa-clock pr-2 text-color-4"></i>
                                            <span class="font-14 text-color-4"> You submitted the requirements</span>
                                            <span class="font-14 font-weight-bold text-color-2 pl-2">{{date('M d, Y',strtotime($Order->start_date))}}</span>
                                        </div>
										<div class="py-2">
											@if($Order->service->questions != '' || $Order->service->is_custom_order !=0)
											@if( $Order->service->is_custom_order ==0)
											<div class="cus-service-question">{!! $Order->service->questions !!}</div>
											@endif
												<h1 class="mb-0 font-16 text-color-2 font-weight-bold">Buyer Requirements</h1>
												<h3 class="font-16 text-color-3 font-weight-normal">
													@if($Order->order_note != '')
														{!! nl2br($Order->order_note) !!}
													@else 
													-
													@endif 
												</h3>
											@endif
											@if($Order->is_job == 1)
												<h1 class="mb-0 font-16 text-color-2 font-weight-bold">Buyer Requirements</h1>
												<h3 class="font-16 text-color-3 font-weight-normal">
													@if($Order->order_note != '')
													{!! nl2br($Order->order_note) !!}
													@else 
													-
													@endif
												</h3>
											@endif
										</div>
										{{-- Question Answer --}}
										@php
											$ans = json_decode($Order->question_answers,true);
											$i = 1;
										@endphp
										@foreach ($ans['question'] as $key => $value)
										<div class="py-2">
                                            <h1 class="mb-0 font-16 text-color-2 font-weight-bold">{{ucfirst($value)}}</h1>
                                            <h3 class="font-16 text-color-3 font-weight-normal">
												@if($ans['answer_type'][$key] == 'Attatched File')
													@if($ans['answers'][$key]!= '')
														@php
														$s3_key  = explode('s3_key#',$ans['answers'][$key]);
														@endphp
														@if(count($s3_key) > 1)
															<a type="button" class="btn text-white bg-primary-blue border-radius-6px py-1 mt-1" data-id={{$ans['answers'][$key]}} href="{{route('download_files_s3')}}?bucket={{env('bucket_order')}}&key={{$s3_key[1]}}&filename={{$s3_key[0]}}">Click to download</a>
														@else
															<a type="button" class="btn text-white bg-primary-blue border-radius-6px py-1 mt-1" data-id={{$ans['answers'][$key]}} href="{front_asset('images/answers').'/'.$ans['answers'][$key]}}">Click to download</a>
														@endif
													@else
														No files uploaded
													@endif
												@else
												{{ucfirst($ans['answers'][$key])}}
												@endif
											</h3>
                                        </div>
										@php $i++; @endphp
										@endforeach
										{{-- END Question Answer --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				{{--END Order Requirement --}}

				{{--Order Attachmented --}}
                <div class="row summary mt-4 mx-0">
                    <div class="col-12 px-0">
                        <div id="accordion">
                            <div class="card border-0 px-3">
                                <div class="py-4" id="headingTwo">
                                    <div class="d-flex flex-column flex-md-row align-items-end justify-content-between ">
                                        <div class="d-flex align-items-center align-self-start">
                                            <div>
                                                <img src="{{url('public/frontend/images/paperclip.png')}}" class="bg-light-gray-f0 p-3 rounded-circle" alt="">
                                            </div>
                                            <div class="ml-3">
                                                <p class="mb-0 font-18 text-color-2 font-weight-bold">Attachments</p>
                                                <p class="mb-0 font-16 text-color-3">You have <span class="text-color-2 font-weight-bold total-attachment">{{$UserFiles->count()}}</span> files in attachments</p>
                                            </div>
                                        </div>
                                        <h5 class="mb-0">
                                            <button class="btn font-14 text-color-1 bg-transparent font-weight-bold arrow-down-btn" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseOne">
                                                Show All Attachments
                                                <i class="fas fa-chevron-down arrow-down font-10 ml-2"></i>
                                            </button>
                                        </h5>
                                    </div>
                                </div>

                                <div id="collapseThree" class="collapse border-top-gray" aria-labelledby="headingTwo" data-parent="#accordion">
                                    <div class="card-body px-2">
										<div class="delivery-now mt-3">
											{{ Form::open(['route' => ['upload_files_s3'], 'method' => 'POST','class' => 'dropzone dropzone-file-area','id'=>'dropzoneForm','files'=>'true']) }}
											<input type="hidden" name="order_id" id="drp_order_id" value="{{$Order->id}}">
											<input type="hidden" name="bucket" id="" value="{{env('bucket_order')}}">
											<div class="fallback">
												<input name="file" type="file" id="file1" class="hide" />
											</div>
											{{Form::close()}}
										</div>
										
										{{-- Attachment List --}}
										<div class="attached-box mt-3">
											<div class="profile-notifications ajax-pagination-div" id="prev_file_list">
												@include('frontend.buyer.file_list')
											</div>
										</div>
										{{--END Attachment List --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				{{-- END Order Attachmented --}}

				{{-- Order Delivered --}}
				@if($Order->delivered_note !='')
					<div class="row summary mt-4 mx-0">
						<div class="col-12 px-0">
							<div id="accordion">
								<div class="card border-0 px-3">
									<div class="py-4" id="headingOne">
										<div class="d-flex flex-column flex-md-row align-items-end justify-content-between ">
											<div class="d-flex align-items-center align-self-start">
												<div>
													<img src="{{url('public/frontend/images/Package.png')}}" class="bg-light-gray-f0 p-3 rounded-circle" alt="">
												</div>
												<div class="ml-3">
													<p class="mb-0 font-18 text-color-2 font-weight-bold">Delivery</p>
													<div class="text-color-2">
														@if($Order->delivered_date != "")
														<span class="font-weight-bold">Delivered Date:</span>
														<span class="mx-md-2 text-color-2"> {{date('M d, Y',strtotime($Order->delivered_date))}}</span>
														@endif
														@if($Order->completed_date != "")
															<span class="font-weight-bold border-left border-gray pl-2">Completed Date:</span>
															<span class="mx-md-2 text-color-2"> {{date('M d, Y',strtotime($Order->completed_date))}}</span>
														@endif
													</div>
													@if($Order->status != 'active' && $Order->status !='completed' && $Order->is_recurring == 0)
														<p class="mb-0 font-16 text-color-3">If you take no action, the order will automatically be marked as complete in 3 days.</p>
													@endif
												</div>
											</div>
											<h5 class="mb-0">
												@php
												$is_expanded = "false";
												$is_show = "";
												if($Order->delivered_date !='' && $Order->status =='delivered'){
													$is_show = "show";
													$is_expanded = "true";
												}
												@endphp
												<button class="btn font-14 text-color-1 bg-transparent font-weight-bold arrow-down-btn" data-toggle="collapse" data-target="#collapseFour" aria-expanded="{{$is_expanded}}" aria-controls="collapseOne">
													Show Delivery
													<i class="fas fa-chevron-down arrow-down font-10 ml-2"></i>
												</button>
											</h5>
										</div>
									</div>

									<div id="collapseFour" class="collapse border-top-gray {{$is_show}}" aria-labelledby="headingOne" data-parent="#accordion">
										<div class="card-body pl-md-5 ml-md-4">
											<div class="delivery-note show">
												{!! nl2br($Order->delivered_note) !!}
											</div>
										
											@if($Order->delivered_note !='')
											<!-- Check recursive order last child then display main order delivered files  -->
											@php
											$seller_work = $Order->seller_work;
											$is_latest_child_order = $Order->is_latest_child_order($Order);
											if($is_latest_child_order == true){
												$seller_work = $Order->parent->seller_work;
											}
											@endphp

											<div class="delivered_files_list border-top-gray mt-3">
												@include('frontend.seller.include.delivered_files')
											</div>
											@if(count($seller_work) > 1)
												<div class="text-center text-md-right">
													<a class="btn text-white bg-primary-blue border-radius-6px py-2 px-5 font-14 font-weight-bold" target="_blank" href="{{route('download.all.files',$Order->secret)}}"> <i class="fa fa-download"></i> Download All</a>
												</div>
											@endif
											@endif
										
											<div class="row align-items-center justify-content-end mt-3">
												{{-- Dispute Order --}}
												@if(Auth::user()->parent_id == 0)
													@if( $Order->status == 'completed' || $Order->status == 'delivered' || ($Order->status == 'delivered' && !empty($Order->dispute_order)) )
														@php 
															$curr= date('Y-m-d H:i:s');
															$to = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $Order->delivered_date);
															$from = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$curr);
															$diff_in_hours = $to->diffIndays($from);
														@endphp

														@if($Order->status == 'completed' && $Order->seller_rating == 0)
														<div class="text-center text-md-right mr-4">
															<button class="btn text-white bg-primary-blue border-radius-6px py-1 px-3 font-14" data-toggle="modal" data-target="#updatereview">Give Rating</button>
														</div>
														@endif

														@if($Order->is_dispute)
															@if($Order->dispute_order->status == 'open' && $is_softban == 0)
																<a class="text-color-1 font-14 font-weight-bold cancel-dispute mr-4" href="javascript:void(0);" data-url="{{ route('cancel_dispute', $Order->dispute_order->id) }}"> Cancel Dispute </a>
															@endif
														@endif

														@if( $Order->is_dispute ==0 && $Order->status != 'cancelled' )
															@if(90 >= $diff_in_hours && $Order->parent_id == 0 && $is_softban == 0)
															<div class="mr-4 text-md-right">
																<a class="text-color-1 font-14 font-weight-bold button-dispute" href="Javascript:;" data-toggle="modal" data-target="#disputeorderpopup">File a Dispute</a>
															</div>
															@endif
														@else
															<div class="text-md-right">
																<a class="text-color-1 font-14 font-weight-bold" href="{{ route('getUserDisputeOrders')}}">Manage Disputes</a>
															</div>
														@endif
													@endif
												@endif

												{{-- Complete Order --}}
												@if($Order->status == 'delivered' && $Order->parent_id == 0 && Auth::user()->parent_id == 0)
												@if( isset($Order->dispute_order) && $Order->dispute_order->status == "rejected" || !isset($Order->dispute_order))
												<div class="col-12 col-md-8 col-lg-4 text-lg-right mt-3 mt-md-0">
													<button class="btn text-white bg-primary-blue border-radius-6px py-2 px-5 font-14 font-weight-bold" data-toggle="modal" data-target="#completeorderpopup">{{($Order->is_recurring == 1)?'Cancel subscription':'Complete order'}}</button>
												</div>
												@endif
												@endif
												
												
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				@endif
				{{--END Order Delivered --}}

				{{-- Order Extras Purchase --}}
				@if(count($service_extra) > 0 && $Order->status =='delivered' || count($service_extra) > 0 && $Order->status == 'active')
					<div class="row summary mt-4 mx-0">
						<div class="col-12 px-0">
							<div id="accordion">
								<div class="card border-0 px-3">
									<div class="py-4" id="headingOne">
										<div class="d-flex flex-column justify-content-center">
											<div class="d-flex align-items-center align-self-start">
												<div>
													<img src="{{url('public/frontend/images/Package.png')}}" class="bg-light-gray-f0 p-3 rounded-circle" alt="">
												</div>
												<div class="ml-3">
													<p class="mb-0 font-18 text-color-2 font-weight-bold">Have everything you need?</p>
													<div class="text-color-2">
														<span class="font-weight-bold">Enhance your order with extras</span>
													</div>
												</div>
											</div>
										</div>
									</div>

									<div id="collapseExtras" class="collapse border-top-gray show" aria-labelledby="headingOne" data-parent="#accordion">
										<div class="card-body pl-md-5 ml-md-4">
											<form action="{{route('checkout.extras.payment',$Order->order_no)}}" id="add-extras-form" method="POST">
											@csrf
											<div class="summary table-responsive-sm">
												<table class="table table-borderless my-orders mb-0">
													<thead class="thead-light">
														<tr>
															<th width="70%" class="min-w-335">ITEM</th>
															<th width="10%">QTY.</th>
															<th width="10%">DURATION</th>
															<th width="10%">PRICE</th>
														</tr>
													</thead>
													<tbody>
														@foreach ($service_extra as $key => $item)
														<tr class="@if(count($service_extra) != $key+1) border-bottom @endif">
															<td>
																<div class="form-check">
																	<input class="form-check-input selected_extras" name="extras_id[]" type="checkbox" value="{{$item->secret}}" id="CheckDefault-{{$item->secret}}">
																	<label class="form-check-label wordbreack" for="CheckDefault-{{$item->secret}}">
																		{{$item->title}}
																	</label>
																</div>
															</td>
															<td>
																<div class="px-0 font-10">
																	<input type="hidden" value="1" name="quantity[{{$item->secret}}]" class="service_quantity_{{$item->secret}}">
																	<div class="summary d-flex p-2 align-items-center justify-content-center">
																		<span class="text-color-4 fa fa-minus font-8 px-2 cursor-pointer service-quantity-minus" data-id="{{$item->secret}}"></span>
																		<span class="font-weight-bold text-color-6 px-2 service_quantity{{$item->secret}}">1</span>
																		<span class="text-color-4 fa fa-plus font-8 px-2 cursor-pointer service-quantity-plus" data-id="{{$item->secret}}"></span>
																	</div>
																</div>
															</td>
															<td>{{$item->delivery_days}} {{($item->delivery_days > 1)? 'Days': 'Day'}}</td>
															<td><b>${{$item->price}}</b></td>
														</tr>
														@endforeach
													</tbody>
												</table>
											</div>
											<div class="row mt-2">
												<div class="offset-md-8 col-md-4">
													<button type="button" class="btn text-white bg-primary-blue border-radius-6px btn-block px-5 font-14 font-weight-bold submit-extras-frm-btn">Checkout</button>
												</div>
											</div>
											</form>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				@endif
				{{--END Order Delivered --}}

				{{-- Revision  --}}
				@if(!is_null($Order->order_revisions) && sizeof($Order->order_revisions) > 0)
				<div class="row summary mt-4 mx-0">
                    <div class="col-12 px-0">
                        <div id="accordion">
                            <div class="card border-0 px-3">
                                <div class="py-4" id="headingFive">
                                    <div class="d-flex flex-column flex-md-row align-items-end justify-content-between ">
                                        <div class="d-flex align-items-center align-self-start">
                                            <div>
                                                <img src="{{url('public/frontend/images/Package.png')}}" class="bg-light-gray-f0 p-3 rounded-circle" alt="">
                                            </div>
                                            <div class="ml-3">
                                                <p class="mb-0 font-18 text-color-2 font-weight-bold">Revision</p>
												{{-- Order revision --}}
												@php
													$pending_revisions = $Order->no_of_revisions - sizeof($Order->order_revisions);
													@endphp

													@if( ($Order->status == 'delivered' || $Order->status == 'in_revision') && $pending_revisions > 0)
                                                	<p class="mb-0 font-16 text-color-3">
														<span class="text-color-2 font-weight-bold">{{$pending_revisions}}</span> 
														{{($pending_revisions==1)?"revision":"revisions"}} pending
													</p>
												@endif
                                            </div>
                                        </div>
                                        <h5 class="mb-0">
                                            <button class="btn font-14 text-color-1 bg-transparent font-weight-bold arrow-down-btn" data-toggle="collapse" data-target="#collapseFive" aria-expanded="false  " aria-controls="collapseOne">
                                                Show Revision
                                                <i class="fas fa-chevron-down arrow-down font-10 ml-2"></i>
                                            </button>
                                        </h5>
                                    </div>
                                </div>

                                <div id="collapseFive" class="collapse border-top-gray" aria-labelledby="headingFive" data-parent="#accordion">
                                    <div class="card-body pl-md-5 ml-md-4">
										@foreach ($Order->order_revisions as $item)
											<p class="mb-0 text-color-3 font-14 mt-2 min-h-100">
												<span class="font-14 font-weight-bold text-color-2">{{date('M d, Y h:m A',strtotime($item->created_at))}}</span>
											</p>
											<div class="mb-0 text-color-3 font-14 no-min-height"> {!! $item->description !!} </div>
										@endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
				@endif
				{{-- END Revision  --}}
                
            </div>
			{{-- Right Side Section --}}
            <div class="col-12 col-md-4 col-xl-3">
                <div class="row mt-4 mt-md-0">
                    <div class="col-12 px-md-0 px-lg-2">
                        <div class="p-3 p-md-2 p-lg-4 summary">
							{{-- Upgrade order plan --}}
                            @if(Auth::user()->is_sub_user() == false && $allow_upgrade_order == 'yes' && $Order->parent_id == 0 && $is_softban == 0)
							<a class="btn text-white bg-primary-blue border-radius-6px w-100 py-2 order_detail_page_upgrade_order_link" href="javascript:void(0)" data-orderno="{{$Order->order_no}}" data-url="{{route('upgrade_order',$Order->order_no)}}">Upgrade Order</a>
							<hr>
                            @endif
							{{--END Upgrade order plan --}}
							
							{{-- Progress Status --}}
							{{-- Order Placed --}}
							{!! get_order_progress_status('Order Placed','completed') !!}
							{{-- Requirements Submitted --}}
							@if($Order->status =='new')
							{!! get_order_progress_status('Requirements Submitted','progress') !!}
							@else
							{!! get_order_progress_status('Requirements Submitted','completed') !!}
							@endif

							{{-- Order in Progress --}}
							@if($Order->status =='active') 
							{!! get_order_progress_status('Order in Progress','progress') !!}
							@elseif($Order->status =='new')
							{!! get_order_progress_status('Order in Progress','') !!}
							@elseif($Order->status =='cancelled')
							{!! get_order_progress_status('Order Cancelled','cancelled') !!}
							@elseif($Order->status =='on_hold')
							{!! get_order_progress_status('Order On Hold','') !!}
							@endif

							{{-- Review Delivery --}}
							@if($Order->delivered_date !='' && $Order->status !='completed' && $Order->status !='cancelled' && $Order->is_review == 0)
							{!! get_order_progress_status('Order Delivered','completed') !!}
							@if($Order->status != 'in_revision' && count($Order->order_revisions) == 0)
							{!! get_order_progress_status('Delivery in Review','progress') !!}
							@endif
							@elseif($Order->delivered_date !='' && $Order->status !='completed' && $Order->status !='cancelled' || $Order->status =='in_revision' || $Order->status =='completed')
							{!! get_order_progress_status('Order Delivered','completed') !!}
							@else
							{!! get_order_progress_status('Review Delivery','') !!}
							@endif

							{{-- In Revision --}}
							@if($Order->status == 'in_revision') 
							{!! get_order_progress_status('Review Delivery','completed') !!}
							{!! get_order_progress_status('In Revision','progress') !!}
							@endif
							@if($Order->delivered_date !='' && $Order->status !='completed' && $Order->status !='cancelled' && $Order->status !='in_revision' && count($Order->order_revisions) > 0)
							{!! get_order_progress_status('In Revision','completed') !!}
							@endif
							

							{{-- Complete Order --}}
							@if($Order->status =='completed')
							@if(count($Order->order_revisions) > 0)
							{!! get_order_progress_status('In Revision','completed') !!}
							@endif
							{!! get_order_progress_status('Review Delivery','completed') !!}
							{!! get_order_progress_status('Order Completed','completed','1') !!}
							@elseif($Order->delivered_date !='' && $Order->status !='completed' && $Order->status !='cancelled' && $Order->status !='in_revision' && $Order->is_review == 1)
							{!! get_order_progress_status('Review Delivery','progress') !!}
							{!! get_order_progress_status('Complete Order','') !!}
							@else
							{!! get_order_progress_status('Complete Order','') !!}
							@endif
							{{--END Progress Status --}}
							
							{{-- Request Revision --}}
							@if($Order->status == 'delivered' && $Order->is_recurring == 0 && Auth::user()->parent_id == 0)
								@if($Order->order_revisions_count < $Order->no_of_revisions || $Order->no_of_revisions == -1 && $is_softban == 0)
									<hr>
									<button class="btn bg-transparent text-color-1 border-radius-6px w-100 py-2 primary-outline-btn mt-3 request_revision" data-orderid="{{$Order->id}}">Request Revision</button>
								@endif
							@endif
							{{-- Cancel Order --}}
							@if($Order->status == 'active' && Auth::user()->parent_id == 0)
								@if($Order->is_recurring == 1)
									@if($Order->parent_id == 0)	
									<hr>
									<div class="mt-4 mb-3 text-center">
										<a class="text-color-1 font-14 font-weight-bold" data-toggle="modal" data-target="#cancelorderpopup" href="Javascript:;">Cancel Order</a>
									</div>
									@endif
								@elseif(($Order->delivered_date == '' && strtotime($Order->end_date) < time()) || ($Order->delivered_date !='' && strtotime($Order->delivered_date) > strtotime($Order->end_date)) || $checkHours == 'false')
									<hr>
									<div class="mt-4 mb-3 text-center">
										<a class="text-color-1 font-14 font-weight-bold" data-toggle="modal" data-target="#cancelorderpopup" href="Javascript:;">Cancel Order</a>
									</div>
								@endif
							@endif

							{{-- Re Order Service --}}
							@if($Order->is_custom_order == 0 && $Order->is_job == 0  && $Order->parent_id == 0 && $Order->status == 'completed' && Auth::user()->parent_id == 0 && $is_softban == 0)
								<button class="btn text-white bg-primary-blue border-radius-6px w-100 py-2 mt-3 reorder_btn" type="button">Reorder</button>
								{{ Form::open(['route' => ['reorder_service'], 'method' => 'POST', 'id' => 'reorder_form']) }}
								<input type="hidden" name="order_no" value="{{$Order->order_no}}">
								{{ Form::close() }}
							@endif
                        </div>
                    </div>
					{{-- Order Chat --}}
					@if($Order->status != 'new' && $Order->status != 'on_hold' && Auth::user()->check_sub_user_permission('can_communicate_with_seller'))
                    <div class="col-12 px-md-0 px-lg-2 mt-4">
                        <div class="p-3 p-md-2 summary p-lg-4">
                            <p class="font-16 font-weight-bold text-color-2">Have something to share with 
								<a href="{{route('viewuserservices',$Order->seller->username)}}"><span class="text-color-1">{{$Order->seller->username}}</span></a> ?
							</p>
							<a class="btn bg-transparent text-color-1 border-radius-6px w-100 py-2 primary-outline-btn open_order_chat" href="javascript:void(0);" data-user="{{$Order->seller->secret}}" data-order="{{($Order->parent_id == 0)?$Order->order_no:$Order->parent->order_no}}"> Chat</a>
                        </div>
                    </div>
					@endif
					{{--END Order Chat --}}
                </div>
            </div>
			{{--END Right Side Section --}}
        </div>
    </div>
</section>


<!-- Requirements modal -->
<div id="question_list_modal" class="modal fade custompopup" role="dialog" data-backdrop="static" tabindex="-2">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Requirements</h4>
			</div>
			<div class="modal-body question-modal-body">
			</div>
		</div>
	</div>
</div>


{{-- Complete Order modal --}}
@if($Order->status == 'completed' && $Order->seller_rating == 0 && Auth::user()->parent_id == 0)
<div id="updatereview" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content border-radius-15px">
			<div class="modal-header modal-header-border-none border-0">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body pt-0 border-0 px-3 px-md-5 new-seller-review">
				{{ Form::open(['route' => ['reviewupdate'], 'method' => 'POST', 'id' => 'BuyerReviewUpdate']) }}
				<input type="hidden" name="order_id" value="{{$Order->id}}">
				<h3 class="font-weight-bold font-20 text-color-6 text-center">Give ratings to seller</h3>
				{{-- <p class="font-16 text-color-2 mt-4 mb-0">Please rate your experience</p> --}}
				<div class="form-group">
					<div class="star-ratings">
						<div class="stars stars-example-fontawesome-o">
							<select id="seller_rating" name="seller_rating" data-current-rating="0" autocomplete="off">
								<option value=""></option>
								<option value="1">1</option>
								<option value="2">2</option>
								<option value="3">3</option>
								<option value="4">4</option>
								<option value="5">5</option>
							</select>
						</div>
					</div>
				</div>
				<div class="form-group">
					<p class="font-weight-bold font-16 text-color-6 mt-3 mb-1">Share some details</p>
					<textarea class="bg-transparent border-radius-6px border-danger-2px w-100 p-3 font-14 text-color-4" rows="6" id="complete_note" name="complete_note" placeholder="Tell your story" maxlength="2500"></textarea>
					<div class="font-12 text-color-10 mb-0 reviewnote-error"></div>
				</div>
				<div class="modal-footer border-0 px-3 px-md-5 pt-5 justify-content-around">
					<button type="button" class="btn text-color-1 bg-transparent" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn text-white bg-primary-blue border-radius-6px py-2 px-5 btn-complte-order">Rate & Review</button> 
				</div>
				{{Form::close()}}
			</div>
		</div>
	</div>
</div>
@endif

{{-- Complete Order modal --}}
@if($Order->status == 'delivered' && $Order->parent_id == 0 && Auth::user()->parent_id == 0)
@if( isset($Order->dispute_order) && $Order->dispute_order->status == "rejected" || !isset($Order->dispute_order))
<div id="completeorderpopup" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content border-radius-15px">
			<div class="modal-header modal-header-border-none border-0">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body pt-0 border-0 px-3 px-md-5 new-seller-review">
				{{ Form::open(['route' => ['complete_order',$Order->id], 'method' => 'POST','class'=>'','id'=>'frmCompleteOrder']) }}
				<h3 class="font-weight-bold font-20 text-color-6 text-center">Complete your Order</h3>
				<p class="font-16 text-color-2 mt-4 mb-0">Please rate your experience</p>
				<div class="star-ratings">
					<div class="stars stars-example-fontawesome-o">
						<select id="seller_rating" name="seller_rating" data-current-rating="0" autocomplete="off">
							<option value=""></option>
							<option value="1">1</option>
							<option value="2">2</option>
							<option value="3">3</option>
							<option value="4">4</option>
							<option value="5">5</option>
						</select>
					</div>
				</div>
				<p class="font-weight-bold font-16 text-color-6 mt-3 mb-1">Share some details</p>
				<textarea class="bg-transparent border-radius-6px border-danger-2px w-100 p-3 font-14 text-color-4" rows="6" id="complete_note" name="complete_note" placeholder="Tell your story" maxlength="2500"></textarea>
				<div class="font-12 text-color-10 mb-0 note-error"></div>
				<div class="modal-footer border-0 px-3 px-md-5 pt-5 justify-content-around">
					<button type="button" class="btn text-color-1 bg-transparent" data-dismiss="modal">Cancel</button>
					<button type="submit" class="btn text-white bg-primary-blue border-radius-6px py-2 px-5 btn-complte-order">{{($Order->is_recurring == 1)?'Cancel subscription & ':''}}Complete order</button> 
				</div>
				{{Form::close()}}
			</div>
		</div>
	</div>
</div>
@endif
@endif

<!-- Dispute order Modal -->
@if( $Order->is_dispute ==0 && $Order->status != 'cancelled' && Auth::user()->parent_id == 0)
@if(90 >= $diff_in_hours && $Order->parent_id == 0 && $is_softban == 0)
<div class="modal fade" id="disputeorderpopup" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content border-radius-15px">
			<div class="modal-header modal-header-border-none border-0">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body pt-0 border-0 px-3 px-md-5 dispute-body">
				
			</div>
		</div>
	</div>
</div>
@endif
@endif

<!-- cancelorderpopup modal-->
@if($Order->status == 'active' && Auth::user()->parent_id == 0)
	@if($Order->is_recurring == 1)
		@if($Order->parent_id == 0)	
			@include('frontend.buyer.include.cancel_order')
		@endif
	@elseif(($Order->delivered_date == '' && strtotime($Order->end_date) < time()) || ($Order->delivered_date !='' && strtotime($Order->delivered_date) > strtotime($Order->end_date)) || $checkHours == 'false')
		@include('frontend.buyer.include.cancel_order')
	@endif
@endif

<!-- saveasteplate modal -->
<div id="saveasteplate" class="modal fade custompopup" role="dialog">
	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Save As Template</h4>
			</div>
			<div class="modal-body">
				<form id="frmCancelOrder">
					<div class="row">
						<div class="col-lg-12">
							<div class="form-group">
								<label>Title</label>
								<input type="text" class="form-control">
							</div>
						</div>
						<div class="col-lg-12 create-new-service update-account text-right">
							<button type="submit" class="btn btn-primary">Save</button> 
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<!-- Tempalte modal-->
<div id="tempalte_pop" class="modal fade custompopup" role="dialog">
	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Your Template</h4>
			</div>
			<div class="modal-body">
				<form id="frmextensio">
					<div class="row">
						<div class="col-lg-12">
							<div class="form-group">
								<label>Title</label>
								<input type="text" class="form-control">
							</div>
						</div>    
						<div class="col-lg-12">
							<div class="form-group">
								<label>Template</label>
								<textarea class="form-control" placeholder="" id="tem_descriptions" name="descriptions" cols="50" rows="10" style="visibility: hidden; display: none;"></textarea>
							</div>    
						</div>
					</div>
					<div class="row">
						<div class="col-lg-6">
							<button type="submit" class="btn btn-danger delete-tem">Delete</button>
						</div>
						<div class="col-lg-6 create-new-service update-account text-right">
							<button type="submit" class="btn btn-primary">Apply</button>
							<button type="submit" class="btn btn-primary">Apply & Updates</button>
						</div>
					</div>

				</form>
			</div>
		</div>
	</div>
</div>

{{-- Request for Revision modal --}}
@if($Order->status == 'delivered' && $Order->is_recurring == 0 && Auth::user()->parent_id == 0)
@if($Order->order_revisions_count < $Order->no_of_revisions || $Order->no_of_revisions == -1 && $is_softban == 0)
<div id="revisionorderpopup" data-focus="false" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<!-- Modal content-->
		<div class="modal-content border-radius-15px">
			<div class="modal-header modal-header-border-none border-0">
				<h4 class="modal-title">Revision Request</h4>
				<button type="button" class="close" data-dismiss="modal">&times;</button>
			</div>
			<div class="modal-body">
				{{ Form::open(['route' => ['request_for_revision'], 'method' => 'POST','class'=>'','id'=>'frmRevisionRequest']) }}
				<input type="hidden" name="order_id" id="request_order_id">
				<div class="row">
					<div class="col-lg-12">
						<div class="input-container form-group">
							<label for="" class="requirenment-lable">Enter Description</label>
							{{Form::textarea('request_note','',["class"=>"form-control request_note","placeholder"=>""])}}
						</div>
						<span class="default_question_error text-danger" style="display: none;">This field is required</span>
						<div class="clearfix"></div>
					</div>
					<div class="col-lg-12 create-new-service update-account text-center mt-3 mb-3">
						<button type="submit" class="btn text-white bg-primary-blue border-radius-6px py-2 px-5">Send Request</button> 
				</div>
				</div>
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>
@endif
@endif

{{-- Share service modal --}}
@if (request()->input('is_share') == 1 && $service_can_share == true )
	@include('frontend.buyer.include.social_media_share_modal')
@endif
@endsection

@section('css')
<link href="{{front_asset('assets/css/bootstrap-tagsinput.css')}}" rel="stylesheet">
<link href="{{front_asset('assets/css/dropzone.css')}}" rel="stylesheet">
<link rel="stylesheet" href="{{front_asset('assets/css/emoji/emoji.css')}}">
<link rel="stylesheet" type="text/json" href="{{front_asset('assets/css/emoji/emoji.css.map')}}">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<link rel="stylesheet" href="{{front_asset('rating/dist/themes/css-stars.css')}}">
@endsection

@section('scripts')

<script src="{{front_asset('rating/jquery.barrating.js')}}"></script>
<script src="{{front_asset('assets/js/bootstrap-tagsinput.js')}}" type="text/javascript"></script>
<script src="{{front_asset('assets/js/dropzone.js')}}" type="text/javascript"></script>
<script src="{{front_asset('assets/js/countdown.js')}}" type="text/javascript"></script>
<!-- emoji -->
<script src="{{front_asset('assets/js/emoji/config.js')}}"></script>
<script src="{{front_asset('assets/js/emoji/util.js')}}"></script>
<script src="{{front_asset('assets/js/emoji/jquery.emojiarea.js')}}"></script>
<script src="{{front_asset('assets/js/emoji/emoji-picker.js')}}"></script>

<!--Bootbox-->
<script src="{{front_asset('js/bootbox.min.js')}}"></script>

<script type="text/javascript">
	var clickable = true;
	@if(Auth::user()->parent_id != 0)
	clickable = false;
	@endif

	if(window.location.hash) {
		var hash = location.hash.substr(1);
		if(hash == 'Chat'){
			$('#Chat-tab').click();
			parent.location.hash = '';
		}
	}

	var currentRating = $('#seller_rating').data('current-rating');
	$('#seller_rating').barrating({
		theme: 'css-stars',
		showSelectedRating: false,
		initialRating: currentRating,
		onSelect: function(value, text) {

		},
		onClear: function(value, text) {

		}
	});

	$(document).on('hidden.bs.modal', '.bootbox.modal', function (e) {  
		if($('.custompopup.show').length > 0){
			$('body').addClass('modal-open');
		}
	});
	
	$(document).on('click', '.notification-close', function(e){
		var id = $(this).data('id');
		var url = $(this).data('url');
		$this = $(this);
		bootbox.confirm("Are you sure you want to delete this file?", function(result){
			if(result == true){
				$.ajax({
					type: "POST",
					url: url,
					data: {"_token": _token, 'id': id},
					success: function (data){
						$('.total-attachment').html($('.total-attachment').html()-1);
						$this.parent().parent().remove();
					}
				});
			}
		});
	});

	@if($Order->is_dispute)
	@if($Order->dispute_order->status == 'open' && $is_softban == 0)
	$(document).on('click', '.cancel-dispute', function(e){
		var url = $(this).attr('data-url');
		$this = $(this);
		bootbox.confirm("Are you sure you want to cancel this dispute? Please note that you won’t be able to re-open it, or file another dispute for this order.", function(result){
			if(result == true){
				$.ajax({
					type: "POST",
					url: url,
					data: {"_token": _token},
					success: function (data){
						window.location.reload();
					}
				});
			}
		});
	});
	@endif
	@endif

	$('body').on('click', '.ajax-pagination-div .pagination a', function (e) {
		e.preventDefault();
		var order_id = $("#drp_order_id").val();
		var url = $(this).attr('href');
		$('.ajax-pagination-div').load(url + '&order_id=' + order_id);
		return false;
	});

	var maxFilesize = "{{ $maxFilesize }}";
	maxFilesize = parseInt(maxFilesize);
	Dropzone.options.dropzoneForm = {
		maxFilesize: maxFilesize,
		parallelUploads: 100,
		dictFileTooBig: 'File is bigger than '+maxFilesize+'MB',
		clickable: clickable,
		init: function() {
			var msg = 'Maximum File Size '+maxFilesize+'MB';
			var brswr_img = "{{url('public/frontend/images/upload-cloud.png')}}";
			var apnd_msg ='<img src="'+brswr_img+'" alt=""><h1 class="pt-2 mb-1 font-20 text-color-4 font-weight-normal">Drop files here or  <svp class="text-color-1">browse</svp></h1><h3 class="font-14 text-color-4 font-weight-normal">'+msg+'</h3>';
			$('#dropzoneForm .dz-message').append(apnd_msg);
			$('#dropzoneForm .dz-message span').hide();

		},
		error: function(file, response) {
			if($.type(response) === "string")
				var message = response;
			else
				var message = response.message;

			file.previewElement.classList.add("dz-error");
			_ref = file.previewElement.querySelectorAll("[data-dz-errormessage]");
			_results = [];
			for (_i = 0, _len = _ref.length; _i < _len; _i++) {
				node = _ref[_i];
				_results.push(node.textContent = message);
			}

			return _results;
		},
		success: function(file,data) {
			this.removeFile(file);
			if(data.code == 200){
				$('#prev_file_list').empty();
				$('#prev_file_list').append(data.data);
				setTimeout(function() {
					$('.total-attachment').html($('#get-total-attachement').attr('data-total'));
				},500)
			}else{
				if(!data.message){
					alert_error("Something wrong went");
				}else{
					alert_error(data.message);
				}

			}
		}
	};

	var maxLength = 2500;
	$('textarea').keyup(function() {
		var length = $(this).val().length;
		$('#chars').text(length);
	});
	var currentRating = $('#seller_rating').data('current-rating');
	
	$('#seller_rating').barrating({
		theme: 'css-stars',
		showSelectedRating: false,
		initialRating: currentRating,
		onSelect: function(value, text) {

		},
		onClear: function(value, text) {

		}
	});
	$('.edit_btn').on("click",function(){
		$(".edit_review_form").fadeIn('show');
		$('.edit_btn_div').hide();
	});
	$(".cancel_edit_btn").on("click",function(){
		$(".edit_review_form").fadeOut('hide',function(){
			$('.edit_btn_div').show();
		});
	});
	$(".update-review").on('click',function(){
		if($("#complete_note").val() != "")
		{
			$("#reviewupdate").submit();
		}
	});

	$('#cancel_note').keyup(function() {
		var length = $(this).val().length;
		$('#cancel_note_chars').text(length);
	});

	/* Request for Revision modal */
	@if($Order->status == 'delivered' && $Order->is_recurring == 0)
	@if($Order->order_revisions_count < $Order->no_of_revisions || $Order->no_of_revisions == -1 && $is_softban == 0)
	$(document).ready(function(){
		/* Deliver Order */
		ClassicEditor.create( document.querySelector( '.request_note' ) )
		.then( newEditor => {
			desc_editor = newEditor;
			desc_editor.model.document.on( 'change:data', ( evt, data ) => {
				var messageLength =  desc_editor.getData();
				if( !messageLength ) {
					$('.default_question_error').css('display','block');
					$("#frmRevisionRequest button").prop('disabled',true);
				}else{
					$('.default_question_error').css('display','none');
					$("#frmRevisionRequest button").prop('disabled',false);
				}
			});
		})
		.catch( error => {
			console.error( error );
		});

		$("#frmRevisionRequest").submit(function(e){
			var desc =  desc_editor.getData();
			$('.default_question_error').css('display','none');
			if(desc==''){
				desc_editor.editing.view.focus();
				$('.default_question_error').css('display','block');
				$("#frmRevisionRequest button").prop('disabled',true);
				return false;
			}else{
				$("#frmRevisionRequest button").prop('disabled',false);
				return true;
			}
		});
		
		$('.request_revision').on('click', function(){
			$('#request_order_id').val($(this).data('orderid'));
			$('#revisionorderpopup').modal('show');
		});

		$('#revisionorderpopup').on('hidden.bs.modal', function () {
			desc_editor.setData('');
		});

	});
	@endif
	@endif
	/* END Request for Revision modal */

</script>
<script type="text/javascript">
	$(document).ready(function(){
		$('.open-new-message').magnificPopup({
			type: 'inline',
			removalDelay: 300,
			mainClass: 'mfp-fade',
			closeMarkup: '<div class="close-btn mfp-close"><svg class="svg-plus"><use xlink:href="#svg-plus"></use></svg></div>'
		});
		$(document).on("click","#cancel_order",function(){
			bootbox.confirm("Are you sure you want to cancel this Order?", function(result){
				if(result == true){
					$('#cancel_hidden').trigger('click');
				} else {
			
				}
			});
		});
		$('#shareServiceModal').modal('show');
	});
</script>
<script>
	$(function() {
		window.emojiPicker = new EmojiPicker({
			emojiable_selector: '[data-emojiable=true]',
			assetsPath: "{{front_asset('img/emoji/')}}",
			popupButtonClasses: 'fa fa-smile-o'
		});

		window.emojiPicker.discover();
	});
</script>
<script>
	(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	ga('create', 'UA-49610253-3', 'auto');
	ga('send', 'pageview');


	$(document).on('click','.button-dispute',function(){
		$.ajax({
			type : 'post',
			url : "{{route('get_reasons')}}",
			data :  {'_token': "{{csrf_token()}}",
			'order_id' : "{{$Order->id}}",
			'user_type' : 'buyer'
			},
			success : function(data){
				$('.dispute-body').html(data.view);
				// $('.file-dispute').modal('show');
			}
  		})
	});

	$('.reorder_btn').on('click', function(){
		$('#reorder_form').submit();
	});

	/* Hide Tips*/
	$(document).on('click','#no_tips',function(){
		$('.order-tip-section').slideUp();
	});
	/* Hide Tips*/

	/* Update review hide modal*/
	$('#updatereview').on('hidden.bs.modal', function () {
		$('#BuyerReviewUpdate').bootstrapValidator('resetForm', true);
	});
	/* END Update review hide modal*/

	/* Open force review modal */
	url_params = getUrlVars();
	if(url_params !== undefined && url_params['review'] == '1') {
		$('#updatereview').modal('show');
    	clean_url();
	}
	$(document).on('click','.submit-extras-frm-btn',function(){
		if($(".selected_extras").filter(':checked').length > 0){
			$('#add-extras-form').trigger('submit');
			$('.add-extras-form').html('<i class="fa fa-spinner fa-spin"></i>').prop('disabled',true);
		}else{
			alert_error('Please select at least one item.');
		}
	});
</script>
@endsection