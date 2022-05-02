@php
$objOrder = new App\Order;

use App\User;
$UserDetail= new App\User;
$dataUser=null;
if(Auth::check())
{
	$parent_data=Auth::user()->parent_id;
	$dataUser=User::select('id','affiliate_id')->where('id',$parent_data)->first();
}
@endphp

@extends('layouts.frontend.main')
@section('pageTitle', ucwords($Service->title).' | demo')
@section('metaTags')
<meta name="title" content="{{($Service->meta_title)?$Service->meta_title:$Service->title}}">
<meta name="keywords" content="{{$Service->meta_keywords}}">
<meta name="description" content="{{strip_tags(($Service->meta_description)?$Service->meta_description:$Service->descriptions)}}">

@if($Service!= null && $Service->is_private == 1)
	<meta name="robots" content="noindex">
@endif

{{-- <meta property="og:url" content="{{URL::current()}}" />
<meta property="og:type" content="website" />
<meta property="og:app_id" content="298062924465542" />
<meta property="og:title" content="{{($Service->meta_title)?$Service->meta_title:$Service->title}}" />
<meta property="og:description" content="{{strip_tags(($Service->meta_description)?$Service->meta_description:$Service->descriptions)}}" /> 
@if(count($Service->fbimages) > 0)
<meta property="og:image" content="{{$Service->fbimages[0]->media_url}}" />
@else
@if(count($Service->images))
@if($Service->images[0]->photo_s3_key != '')
<meta property="og:image" content="{{$Service->images[0]->media_url}}" />
@else
<meta property="og:image" content="{{url('public/services/images/'.$Service->images[0]->media_url)}}" />
@endif
@endif
@endif --}}
@endsection

@php 
$og_image = '';
if(count($Service->fbimages) > 0) {
	$og_image = $Service->fbimages[0]->media_url;
} else {
	if(count($Service->images)) {
		if(!is_null($Service->images[0]->thumbnail_media_url)) {
			$og_image = $Service->images[0]->thumbnail_media_url; 
		} else if($Service->images[0]->photo_s3_key != '') {
			$og_image = $Service->images[0]->media_url;
		} else {
			$og_image = url('public/services/images/'.$Service->images[0]->media_url);
		}
	}
}
@endphp

@section('og_app_id', '298062924465542')
@section('og_url', URL::current())
@section('og_title', ($Service->meta_title)?$Service->meta_title:$Service->title)
@section('og_type', 'website')
@section('og_description', strip_tags(($Service->meta_description)?$Service->meta_description:$Service->descriptions))

@if(strlen($og_image) > 0) 
@section('og_image', $og_image)
@endif

@php
$cate_name = "";
$cate_name .= $Service->category->category_name ?? "";
$cate_name .= ' > ';
$cate_name .= $Service->subcategory->subcategory_name ?? "";

$og_basic_price = $Service->basic_plans->price ?? 0.0;
/* $og_basic_price .= " (Basic Plan)"; */
@endphp
@section('og_product_category', $cate_name)
@section('og_product_brand', "demo")
@section('og_product_availibility', "in stock")
@section('og_product_price_amount', $og_basic_price)
@section('og_product_price_currency', "USD")
@section('og_product_catalog_id', $Service->seo_url)

@if($Service->standard_plans)
@php
$og_standard_price = $Service->standard_plans->price ?? 0.0;
$og_standard_price .= " (Standard Plan) USD";
@endphp
@section('og_product_price_standard', $og_standard_price)
@endif

@if($Service->premium_plans)
@php
$og_premium_price = $Service->premium_plans->price ?? 0.0;
$og_premium_price .= " (Premium Plan) USD";
@endphp
@section('og_product_price_premium', $og_premium_price)
@endif

@section('content')

<!-- Display Error Message -->
@include('layouts.frontend.messages')

<input type="hidden" id="service_id" value="{{$Service->id}}">
<input type="hidden" id="ratting_count_id" value="0">
<div class="reve-shot">
	<div id="html-content-holder-canvas"></div>
</div>
<section class="sub-header product-detail-header">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<ul class="cus-breadcrumb">
					<li><a href="{{url('/')}}">Home</a></li>
					<li><a href="{{url('categories?q=')}}">Services</a></li>
					@if($Service->category->seo_url != 'by-us-for-us')
					<li><a href="{{url('categories/'.$Service->category->seo_url)}}">{{$Service->category->category_name}}</a></li>
					<li><a href="{{url('categories/'.$Service->category->seo_url.'/'.$Service->subcategory->seo_url)}}">{{$Service->subcategory->subcategory_name}}</a></li>
					@endif
					<li class="text-capitalize"><a href="javascript:void(0)">{{$Service->title}}</a></li>
				</ul>  

				<h2 class="heading mb-2 text-capitalize">{{$Service->title}}</h2>

				<div class="service">
					<span @if($Service->total_review_count > 0)class="show_rating_popup" @endif data-toggle="popover" data-placement="bottom">
						{!! displayRating($Service->service_rating ,$showFiveStar = 1) !!}
						<i class="fas fa-angle-down text-secondary"></i>
					</span>
					<span> ({{$Service->total_review_count}} Reviews)</span>
					
					@if($total_queue_orders > 0)
					<span class="ml-10 order-in-queue"><strong>&nbsp;{{$total_queue_orders}} {{($total_queue_orders == 1)?'Order':'Orders'}} in Queue</strong></span>
					@endif
					
				</div>
				<ul class="nav nav-tabs" id="myTab" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" id="overview-tab" data-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Overview</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" id="description-tab" data-toggle="tab" href="#description" role="tab" aria-controls="description" aria-selected="false">Description</a>
					</li>
				{{-- 	<li class="nav-item">
						<a class="nav-link" id="support-tab" data-toggle="tab" href="#support" role="tab" aria-controls="support" aria-selected="false">Support</a>
					</li> --}}
					<li class="nav-item">
						<a class="nav-link" id="recommendation-tab" data-toggle="tab" href="#recommendation" role="tab" aria-controls="recommendation" aria-selected="false">Recommendation</a>
					</li>
					@if(isset($Service->extra) && count($Service->extra)>0)
					<li class="nav-item">
						<a class="nav-link" id="extra-show-tab" data-toggle="tab" href="#extrashow" role="tab" aria-controls="audio" aria-selected="false">Extras</a>
					</li>
					@endif
				</ul>
			</div>
		</div>    
	</div>
</section>

<section class="product-block">
	<div class="container">
		<div class="row"> 
			

			{{-- begin : LEFT SIDEBAR  --}}
			<div class="col-md-8 col-sm-12"> 
				<div class="popular-tab-item">
					<div class="tab-content" id="myTabContent">
						{{-- Tab 1 --}}
						<div class="tab-pane fade show active mb-4" id="overview" role="tabpanel" aria-labelledby="overview-tab">
							<div class="product-slider">
								<div class="slider slider-for product-slider-img">
									
									@if(count($Service->images))
									@foreach($Service->images as $row)
									<div>
										@if($row->photo_s3_key != '')
										<img src="{{$row->media_url}}"  alt="product-image" class="img-fluid">
										@else
										<img src="{{url('public/services/images/'.$row->media_url)}}"  alt="product-image" class="img-fluid">
										@endif
									</div>
									@endforeach
									@endif


									@php
									$youtube_count = 0;
									if($Service->youtube_url != ''){
										$video_id = explode("?v=", $Service->youtube_url);
										if(!isset($video_id[1])){
											$video_id = explode("shorts/", $Service->youtube_url);
										}
										if(isset($video_id[1])){
											$video_id = $video_id[1];
											if($video_id!=''){
												$youtube_count = 1;
											}
										}
									}
									$count_slides = count($Service->images)+count($Service->video)+$youtube_count;
									@endphp

									
									@if(count($Service->video))
									@foreach($Service->video as $row)
									<div>
										@if($row->photo_s3_key != '')
										<video class="fullimage_video" controls disablepictureinpicture controlslist="nodownload"><source src="{{$row->media_url}}" type="video/mp4">
										</video>
										@else
										<video class="fullimage_video"  controls disablepictureinpicture controlslist="nodownload"><source src="{{url('public/services/video/'.$row->media_url)}}" type="video/mp4">
										</video>
										@endif
									</div>
									@endforeach
									@endif


									@if($youtube_count == 1)
									@php
									$youtubeUrl = 'https://www.youtube.com/embed/'.$video_id;
									@endphp
									<div>
										<div class="videoWrapper">
											<iframe width="100%" height="515px" src="{{$youtubeUrl}}?rel=0&amp;showinfo=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
										</div>
									</div>
									@endif
								</div>

								<div class="slider slider-nav  product-slider-nav-img">
									@if(count($Service->images))
									@foreach($Service->images as $row)
									<div>
										@if($row->photo_s3_key != '')
											@if($row->thumbnail_media_url != '')
												<img src="{{$row->thumbnail_media_url}}" data-src="{{$row->media_url}}" alt="product-image" class="img-fluid">
											@else
												<img src="{{$row->media_url}}"  alt="product-image" class="img-fluid">
											@endif
										@else
										<img src="{{url('public/services/images/'.$row->media_url)}}"  alt="product-image" class="img-fluid">
										@endif
									</div>
									@endforeach
									@endif

									@if(count($Service->video))
									@foreach($Service->video as $row)
									<div>
										@if($row->photo_s3_key != '')
											@if($row->thumbnail_media_url != '')
												<div class="img-fluid" style="background-image: url('{{$row->thumbnail_media_url}}')">
													<img src="{{get_video_player_img()}}" class="img-fluid video-play-btn cust-pd-15" >
												</div>
												@else
												<img src="{{url('public/frontend/images/default_video.png')}}" data-src="{{$row->media_url}}" data-type="video" alt="product-image" class="img-fluid">
											@endif
										@else
										<img src="{{url('public/frontend/images/default_video.png')}}" data-src="{{url('public/services/video/'.$row->media_url)}}" data-type="video" alt="product-image" class="img-fluid">
										@endif
									</div>
									@endforeach
									@endif

									{{-- files --}}

									@if(count($Service->pdf))
									<div>
										@if(count($Service->pdf))
										@foreach($Service->pdf as $row)
										<div class="pack-box" style="height: auto;padding-top: 0px;">
											@if($row->photo_s3_key != '')
											<a href="{{$row->media_url}}" target="_blank" class="button dark-light">
												<img src="{{front_asset('images/default_pdf.png')}}" width="100">
											</a>
											@else
											<a href="{{url('public/services/pdf/'.$row->media_url)}}" target="_blank" class="button dark-light">
												<img src="{{front_asset('images/default_pdf.png')}}" width="100">
											</a>
											@endif
										</div>
										@endforeach
										@endif
									</div>
									@endif

									{{-- files --}}


									@if($youtube_count == 1)
									@php
									$youtubeUrl = 'https://www.youtube.com/embed/'.$video_id;
									@endphp
									<div>
										<img src="{{url('public/frontend/images/default_video.png')}}" data-src="{{$youtubeUrl}}" data-type="youtube" alt="product-image" class="img-fluid">
									</div>
									@endif
								</div>
							</div>
							<div class="custom" style="overflow: auto">
								<div class="row custom-bottom-border">
									<div class="col-10 custom-detail-service-title"><h5>About This Service</h5></div>
									<div class="col-2 custom-detail-service-favorite">
										@if(Auth::check())
										<a class="favorite-action service_{{$Service->id}} promo-popup " data-id="{{$Service->id}}" data-status="{{isset($Service->favorite->service_id) ? '1' : '0'}}" >
											<div class="circle tiny secondary">
												<i class="far fa-heart heart_service_{{$Service->id}} {{isset($Service->favorite->service_id) ? 'is_favorite' : ''}}" data-id="{{$Service->id}}"></i>
											</div>
										</a>
										@else
										<a class="favorite-action service_119 promo-popup" href="{{url('login')}}">
											<div class="circle tiny secondary">
												<i class="far fa-heart"></i>
											</div>
										</a>
										@endif
									</div>
								</div>
								{{-- <h5>About This Service</h5> --}}
								{{-- <div class="text-right custom_favorite_margin"> --}}

								{{-- </div> --}}
								<div class="cus-d-block">
									<?=$Service->descriptions;?>
								</div>
							</div>
							@if(count($Service->faqs) > 0)
							<div class="accordion custom" id="accordionExample">
								<div class="mb-2 mt-2">
									<div class="col-12 custom-detail-service-title"><h5>Frequently Asked Questions</h5></div>
								</div>
								@foreach ($Service->faqs as $index => $faq_item)
								<div class="card border-0 dark_mode_card_bg">
									<a href="javascript:void(0)" class="faq_link_class{{-- @if(!$loop->first) collapsed @endif --}}" data-toggle="collapse" data-target="#faq_collapse_{{$index}}" aria-expanded="true" aria-controls="faq_collapse_{{$index}}" data-index="{{$index}}">
										<div class="card-header border-bottom-1 bg-white faq_que_div" id="heading_{{$index}}">
											{{ $faq_item->question }}
											<span class="float-right faq_icon" id="faq_icon_{{$index}}">
												<i class="fa fa-plus text-secondary"></i>
											</span>
										</div>
									</a>
									<div id="faq_collapse_{{$index}}" class="collapse {{-- @if($loop->first)show @endif --}}" aria-labelledby="heading_{{$index}}" data-parent="#accordionExample">
										<div class="card-body">
											{!! $faq_item->answer !!}
										</div>
									</div>
								</div>
								@endforeach
							</div>
							@endif
						</div>
						{{-- Tab 2 --}}
						<div class="tab-pane fade" id="seo" role="tabpanel" aria-labelledby="seo-tab">...</div>
						{{-- Tab 3 --}}
						<div class="tab-pane fade" id="description" role="tabpanel" aria-labelledby="description-tab">
							<?=$Service->descriptions;?>
						</div>
						{{-- Tab 4 --}}
						{{-- <div class="tab-pane fade" id="support" role="tabpanel" aria-labelledby="support-tab">...</div> --}}

						{{-- Tab 5 --}}
						<div class="tab-pane fade" id="recommendation" role="tabpanel" aria-labelledby="recommendation-tab">

							@if(!empty($OtherService))
							<div class="row filter-days">
								@foreach($OtherService as $key => $Services )
								<div class="col-xl-4 col-lg-6 col-md-6 mb-4">
									<div class="item popular-item">
										<div class="thumbnail">
											<a href="{{route('services_details',[$Services->user->username,$Services->seo_url])}}">

												@php 
												$image_url = url('public/frontend/assets/img/No-image-found.jpg');
												@endphp
												@if(isset($Services->images[0]))
												@if($Services->images[0]->photo_s3_key != '')
												@php 
												$image_url = $Services->images[0]->media_url; 
												@endphp
												@else	
												@php 
												$image_url = url('public/services/images/'.$Services->images[0]->media_url); 
												@endphp
												@endif 
												@endif

												<img class="img-fluid" src="{{$image_url}}">
											</a>

											@if(\Auth::check())
											<a class="favorite-action service_{{$Services->id}} {{\auth::check() ? '':'promo-popup'}} promo-popup " data-id="{{$Services->id}}" data-status="{{isset($Services->favorite->service_id) ? '1' : '0'}}" >
												<div class="circle tiny secondary">
													<i class="far fa-heart heart_service_{{$Services->id}} {{isset($Services->favorite->service_id) ? 'is_favorite' : ''}}" data-id="{{$Services->id}}">
													</i>
												</div>
											</a>
											@else
											<a class="favorite-action service_{{$Services->id}} {{\auth::check() ? '':'promo-popup'}} promo-popup " data-id="{{$Services->id}}" data-status="{{isset($Services->favorite->service_id) ? '1' : '0'}}" href="{{route('login')}}">
												<div class="circle tiny secondary">
													<i class="far fa-heart heart_service_{{$Services->id}} {{isset($Services->favorite->service_id) ? 'is_favorite' : ''}}" data-id="{{$Services->id}}">
													</i>
												</div>
											</a>
											@endif
										</div>
										<div class="product-info">
											<a href="{{route('services_details',[$Service->user->username,$Service->seo_url])}}">
												<h4 class="text-header">
													{{$Services->title}}         <input hidden="" type="text" name="days" class="days" value="{{$Services->basic_plans->delivery_days}}">   
												</h4>
											</a>
											<p class="product-description">
												<?=substr(strip_tags($Services->descriptions), 0, 38); ?>
											</p>
											<div class="review mb-3">
												<svg id="svg-star" viewBox="0 0 10 10" preserveAspectRatio="xMinYMin meet" width="100%" height="100%">	
													<polygon points="4.994,0.249 6.538,3.376 9.99,3.878 7.492,6.313 8.082,9.751 4.994,8.129 1.907,9.751 
													2.495,6.313 -0.002,3.878 3.45,3.376 ">
												</polygon>
												</svg>
												<span>
													{{$Services->service_rating}}
												</span>
												({{$Services->total_review_count}} Reviews)
											</div>

											<div class="row align-items-center avtar-block">
												<div class="col-md-6 col-xl-6 col-6">
													<div class="avtar">
														<div class="avtar-img">
															<a href="{{route('viewuserservices',[$Services->user->username])}}">
																<figure class="user-avatar small">
																	<img alt="" src="{{get_user_profile_image_url($Services->user)}}">
																</figure>
															</a>
														</div>
														<div class="avtar-detail">
															<a href="{{route('viewuserservices',[$Services->user->username])}}">
																<div class="custom-text-header">
																	<span>{{$Services->user->username}}	</span><i class="fa fa-check" aria-hidden="true"></i>
																	<span>
																		@if($Services->user->seller_level == 'Unranked')
																		Level 0
																		@else
																		{{$Services->user->seller_level}}
																		@endif
																	</span>
																</div>
															</a>
														</div>
													</div>
												</div>
												<div class="col-md-6 col-xl-6 text-right">
													<div class="total-price">
														<p>Starting at  <span>${{isset($Services->basic_plans->price)?$Services->basic_plans->price:'0.0'}}</span>
														</p>
													</div>
												</div>
											</div>

										</div>
									</div>
								</div>
								@endforeach
							</div>
							@endif
						</div>

						{{-- Tab 6 --}}
						<div class="tab-pane fade" id="extrashow" role="tabpanel" aria-labelledby="extra-show-tab">
							@if(count($Service->extra))
							<div class="extra-block">
								@foreach($Service->extra as $key => $row)
								<div class="row add-extra-row"> 
									<div class="col-lg-6 col-md-6 col-6">
										<div class="detail-box">
											<h6>{{$row->title}}</h6>
										</div>  
									</div>

									<div class="col-lg-3 col-md-3 col-3">

										<div class="input-group qty-selector" style="display: none;">
											<span class="input-group-btn cart-extra-customize minus" data-price="{{$row->price}}" data-delivery_days="{{$row->delivery_days}}">
												<button type="button" class="btn btn-number">
													<i class="fa fa-minus"></i>
												</button>
											</span>

											<input type="text" name="extra_qty_{{$row->id}}" {{-- name="qty[]" --}} class="form-control input-number text-center white-bg qty" value="1" min="1" max="100" readonly>

											<span class="input-group-btn cart-extra-customize plus" data-price="{{$row->price}}" data-delivery_days="{{$row->delivery_days}}">
												<button type="button" class="btn btn-number">
													<i class="fa fa-plus"></i>
												</button>
											</span>
										</div>
									</div>
									<div class="col-lg-3 col-md-3 col-3 text-left">${{$row->price}}</div>
								</div>
								@endforeach
							</div>
							@endif
						</div>

					</div>
				</div>

				{{-- begin : Mobile pricing --}}
				<div class="mobile-pricing-section">
					@php
						$i = 1;
					@endphp
					@include('frontend.service.details_right_sidebar')
				</div>
				{{-- end : Mobile pricing --}}

				{{-- begin; Review Section --}}
				<div class="comment-list ajax-pagination-div" id="reviewlist">
					@php
						$i = 2;
					@endphp
					@include('frontend.service.reviewlist')
				</div>
				{{-- end : Review Section --}}

			</div>
			{{-- end : LEFT SIDEBAR  --}}

			{{-- begin : RIGHT SIDEBAR  --}}
			<div class="col-md-4 col-sm-12 desktop-pricing-section">
				@include('frontend.service.details_right_sidebar')
			</div>
			{{-- end : RIGHT SIDEBAR --}}
			
		</div>
	</div>
</section>
{{-- Modal --}}

@if (Auth::check() && $Service->user->is_delete == 0)
@if (Auth::user()->id != $Service->uid)
@if(User::check_sub_user_permission('can_make_purchases'))
<div id="customorder" class="modal fade" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Custom Order</h4>
			</div>
			<div class="modal-body">
				{{ Form::open(['route' => ['request_custom_quote'], 'method' => 'POST', 'id' => 'frmCustomQuote']) }}

				<input type="hidden" name="seller_uid" value="{{$Service->uid}}">
				<input type="hidden" name="service_id" value="{{$Service->id}}">
				<input type="hidden" name="utm_source" id="utm_source_for_customorder">
				<input type="hidden" name="utm_term" id="utm_term_for_customorder">

				<div class="form-group">
					<label>Please describe your request in as much as possible</label>
					{{Form::textarea('descriptions','',["class"=>"form-control custom-desc","placeholder"=>"Enter your descriptions here...","id"=>"descriptions","rows"=>3])}}
					<div class="text-danger descriptions-error text-left" ></div>
					<p class="text-right"><span id="chars_desc">0</span>/2500 character Max</p>
				</div>

				<div class="form-group">
					<label>Delivery days</label>
					{{Form::text('delivery_days','0',["class"=>"form-control custom-desc","placeholder"=>"Enter your delivery days here..."])}}
					<div class="text-danger delivery-days-error text-left"></div>
				</div>

				<div class="form-group">
					<label>Max Budget</label>
					{{Form::text('price','',["class"=>"form-control custom-desc","placeholder"=>"Enter your price here..."])}}
					<div class="text-danger price-error text-left"></div>
				</div>
				<div class="form-group text-right"> 
					<button type="submit" class="btn default-btn">Request custom order</button>
				</div>
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>
@endif
@if(Auth::user()->parent_id == 0)
<div id="new-message-popup" class="modal fade custommodel" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Send A Message</h4>
			</div>
			<div class="modal-body">

				{{ Form::open(['route' => ['message_compose',['service',$Service->seo_url]], 'method' => 'POST', 'id' => 'frmMessage']) }}

				{{-- <input type="hidden" name="to_user" value="{{$Service->uid}}">
				<input type="hidden" name="service_id" value="{{$Service->id}}"> --}}

				<div class="form-group">
					<label>Local Time: {{date('D h:i')}}</label>
					<p class="lead emoji-picker-container">
						{{Form::textarea('message','',["class"=>"form-control","placeholder"=>"Write your message here...",'data-emojiable' => "true",'rows'=>'3','id' => 'chat_message'])}}
					</p>
					<div class="text-danger note-error text-left" ></div>
					{{-- <p class="text-right"><span id="chars">0</span>/2500 character Max</p> --}}
					<div class="row"></div>
				</div>
				<div class="col-lg-12">
					@if(Auth::check() && Auth::user()->is_premium_seller() == true)
					<div class="row align-items-center">
						<div class="col-lg-6">
							<div class="form-group">
								{{Form::select('select_title',[""=>"Select Template"]+$save_template_chat,null,['class'=>'form-control','id'=>'select_title_chat'])}}
							</div>
						</div>
						<div class="col-lg-6">
							{{-- Save as Template --}}
							<div class="form-group add-extra-detail">
								<label class="cus-checkmark">    
									<input id="save_template_chat" name="save_template" type="checkbox" value="1">
									<span class="checkmark"></span>
								</label>
								<div class="detail-box">
									<lable>Save As Template?</lable>
								</div>
							</div>
						</div>
					</div>
					@endif
				</div>

				<div class="form-group text-right"> 
					<button type="submit" class="btn priv msg-btn">Send </button>
				</div>

				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>
@endif
@endif
@endif


@if(Auth::check() && Auth::user()->is_premium_seller() == true)
@include('frontend.seller.save_template_seller_detail')
@endif

<div id="popover_content_wrapper" class="hide cus-ratting-box">
	<div class="ratting-content">
		{!! displayRating($Service->service_rating ,$showFiveStar = 1) !!}
		<span class="fs-18"><b>{{number_format($Service->service_rating,1, '.', '')}} out of 5</b></span>
	</div>
	<div> 
		<span class="pl-3 cus-grey">{{$Service->total_review_count}} global ratings</span>
	</div>
	<table class="table ratting-table">
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
	<hr>
	<div class="text-center  pb-2"> 
		<a href="#review-box" classs="scroll_bottom ">See all reviews</a>
	</div>
</div>
@endsection

@section('css')
<!-- emoji -->
<link rel="stylesheet" href="{{front_asset('css/emoji/emoji.css')}}">
<link rel="stylesheet" type="text/json" href="{{front_asset('css/emoji/emoji.css.map')}}">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">

<style>
.custom_head {
	border-bottom: 0px;
	padding: 0.7rem;
}
.custom_body {
	padding: 0.7rem;
}
.custom_foot {
	padding: 0rem 0.7rem 0.7rem;
	display: flex;
}
.modal_size {
	width: 380px;
}
.display_block {
	display: block !important;
}
.card-body p {
    min-height: 0px;
}
.card-link+.card-link {
    margin-left: 0.25rem;
}
.round_corner {
	border-radius: 15px !important;
}
.all_review_section .row {
	margin-right: 0px;
    margin-left: 0px;
}
</style>
@endsection

@section('scripts')
<!-- emoji -->
<script src="{{front_asset('js/emoji/config.js')}}"></script>
<script src="{{front_asset('js/emoji/util.js')}}"></script>
<script src="{{front_asset('js/emoji/jquery.emojiarea.js')}}"></script>
<script src="{{front_asset('js/emoji/emoji-picker.js')}}"></script>

<!-- Item V1 -->
<script type="text/javascript">
	$('document').ready(function(){
		$('.open_chat').on('click', function(){
			open_chat_click_fn('services',$(this).data('user'),$(this).data('service'));
		});
	});
	var checkMsg={{$showMsg}};
	if(checkMsg == 1)
	{
		//$('#new-message-popup').modal('show');
		setTimeout(function(){ 
			open_chat_click_fn('services',$('.open_chat').data('user'),$('.open_chat').data('service'));
		}, 800);
	}

	var checkCustom={{$showCustomBox}};
	if(checkCustom == 1)
	{
		$('#customorder').modal('show');
	}


	(function($){
		
		var maxLength = 2500;
		$('textarea').keyup(function() {
			var length = $(this).val().length;
			$('#chars').text(length);
		});

		/*Create Custom Quote*/
		$('#descriptions').keyup(function () {
			var length = $(this).val().length;
			$('#chars_desc').text(length);
		});
		/*Pagination through jquery load method*/
		$('body').on('click', '.ajax-pagination-div .pagination a', function (e) {
			e.preventDefault();

			var rating_count = $('#ratting_count_id').val();
			var id = $("#service_id").val();
			var url = $(this).attr('href');
			var rating = $('#review_rating').val();
			
			$.ajax({
				url : url + '&id=' + id+'&rating='+rating+'&rating_count='+rating_count,
				type : "get",
				success : function(data){
					$('.ajax-pagination-div').html(data);
				}
			});
			/*$('.ajax-pagination-div').load(url + '&id=' + id);return false;*/
		});
	/*$(window).scroll(function (event) {
		var scroll = $(window).scrollTop();

		if($(window).width() > 1260){
			//var left_width = ($('.section-wrap').width() - $('.section').width())/2 + $('.post').width() + 63;
			if(scroll >= 300){
				//$(".sidebar-stricky").css("left",left_width+'px');
				$(".sidebar").addClass("sidebar-stricky");
			}else{
				$(".sidebar").removeClass("sidebar-stricky");
			}
		}
	});*/
	if($(window).width() > 1260){
		//var scroll_height = $(window).height() - 55;
		//$(".sidebar-overflow").css("height",scroll_height +'px');
		//$(".sidebar-overflow").css("overflow","auto");
	}
})(jQuery);
</script>

<script>
	$(document).on('click', '.openTextbox', function(){
		var comtId = this.id;
		var SpltId = comtId.split('_');
		var id = SpltId[1];
		$("#replayBox_"+id).fadeToggle(500);
		$('#warningBox_'+id).fadeOut();
		$('#CommentReply_'+id).val('');
		$('textarea').focus().val('').val();
	});

	function submitReplay(id){
		var replayForm = $("#replayForm_"+id).serialize();
		var CommentReply = $("#CommentReply_"+id).val();
		var Comlength =  jQuery.trim(CommentReply).length;
		if(Comlength==0){
			$('#warningBox_'+id).fadeIn();
		}else{
			$('#warningBox_'+id).fadeOut();
			$('.submitReplay_'+id).attr('disabled',true);
			$.ajax({
				url : "{{ URL::route('replayComment') }}",
				type : "post",
				data : replayForm,
				beforeSend: function() {
					$("#loader_"+id).show();
					$("#re_"+id).hide();
				},
				success : function(data){
					$("#loader_"+id).hide();
					$("#re_"+id).show();
					toastr.success(data.message, '');
					$("#replay_"+id).hide();
					$("#replayBox_"+id).hide();
					$("#showComReply_"+id).html('<i class="fa fa-reply" aria-hidden="true"></i> '+data['value']);
				}
			});
		}
	}
</script>

<script>
	(function (i, s, o, g, r, a, m) {
		i['GoogleAnalyticsObject'] = r;
		i[r] = i[r] || function () {
			(i[r].q = i[r].q || []).push(arguments)
		}, i[r].l = 1 * new Date();
		a = s.createElement(o),
		m = s.getElementsByTagName(o)[0];
		a.async = 1;
		a.src = g;
		m.parentNode.insertBefore(a, m)
	})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

	ga('create', 'UA-49610253-3', 'auto');
	ga('send', 'pageview');
</script>

<script>
	window.dataLayer = window.dataLayer || [];
	window.dataLayer.push({
		'event' : 'productDetail',
		fbCustomData :
		{'content_name' : '{{display_title($Service->title)}}',
		'content_category' : '{{$Service->category->category_name}}',
		'content_ids' : '{{$Service->id}}',
		'content_type': 'product',
		'value' : '{{number_format($Service->basic_plans->price,2,'.','')}}',
		'currency' : 'USD'
	}
});
</script>

@if(Auth::check())
<script src="{{front_asset('js/html2canvas.js')}}"></script>
<script>
	$('document').ready(function () {
		var getCanvas;
		var loading_gif = "{{url('public/frontend/assets/img/filter-loader.gif')}}";
		$(document).on('click','.share_link',function(){
			var info = { 
						'service' : "{!! display_title($Service->title) !!}",
						'seller' : "{!! Auth::user()->Name !!}",
						'buyer' : $(this).data('buyer'),
						'review' : $(this).data('review'),
						//'buyer_image' : $('#seller_pro_pic').attr('src'),
						'seller_rating' : $(this).data('sellerratings'),
						'uid' : '{!! Auth::user()->id !!}'
					};
			$.ajax({
					url: "{{route('share_review')}}",
					type: "get",
					data: {
						'_token':"{{ csrf_token() }}",
						'info' : info
					},
					success: function (data) {
						// $('#html-content-holder').html(data);
						$('#html-content-holder-canvas').html(data);
						$('#html-content-holder').html('<div class="text-center d-block"><img src="'+loading_gif+'" class="my-auto share-img" width="50" ></div>');
						html2canvas($("#html-content-holder-canvas"), {
							allowTaint: false,
							useCORS: true,
							width: wi,
							height: he,
							onrendered: function (canvas) {
								getCanvas = canvas;
								var imgageData = getCanvas.toDataURL("image/png");
								var newData = imgageData.replace(/^data:image\/png/, "data:application/octet-stream");
								$('#html-content-holder').html('<img src="'+newData+'" class="share-img" width="400" >');
								
							}
						});
						setTimeout(function(){
							$('#review_modal').modal('show');
						}, 1000);
					}
			});
		});
		var wi = 600;
		var he = 600;
		if (jQuery(window). width() < 600) {
			var wi = 350;
			var he = 350;
		}

		$(document).on('shown.bs.modal','#review_modal',function(){
			// html2canvas($("#html-content-holder-canvas"), {
			// 	scale:2,
			// 	allowTaint: false,
			// 	useCORS: true,
			// 	width: wi,
			// 	height: he,
			// 	onrendered: function (canvas) {
			// 		getCanvas = canvas;
			// 	}
			// });
		});

		$(document).on('click','#download_review',function(){
		
			// html2canvas($('#html-content-holder')[0], {
			// 	scale:4,
			// 	// width: 600, height: 600
			// 	}).then(function(canvas) {
			// 		var a = document.createElement('a');
			// 		a.href = canvas.toDataURL("image/png");
			// 		a.download = 'share_review.png';
			// 		a.click();
			// 	});

			var imgageData = getCanvas.toDataURL("image/png");
			var newData = imgageData.replace(/^data:image\/png/, "data:application/octet-stream");
			$("#download_review").attr("href", newData);
		});
	});
</script>

<script>
$('document').ready(function() {
	$('.faq_link_class').on('click',function(){
		var index = $(this).data('index');
		$('.faq_que_div').css('border-bottom','1px solid rgba(0,0,0,.125)');
		if($('#faq_icon_'+index+' i').hasClass('fa-minus')) {
			$('#faq_icon_'+index+' i').removeClass('fa-minus text-info').addClass('fa-plus text-secondary');
		} else {
			$('#heading_'+index).css('border-bottom','1px solid #17a2b8');
			$('.faq_icon i').removeClass('fa-minus text-info').addClass('fa-plus text-secondary');
			$('#faq_icon_'+index+' i').addClass('fa-minus text-info').removeClass('fa-plus text-secondary');
		}
	});

});

</script>
@endif


<script>
$('document').ready(function() {

	$(".show_rating_popup").popover({ trigger: "manual" , html: true, animation:false,content: function () {
			return $('#popover_content_wrapper').html();
            // $return = $('#popover_content_wrapper').html();
        }})
		.on("mouseenter", function () {
			var _this = this;
			$(this).popover("show");
			review_hover_fn();
			stop_to_open_context_menu();
			$(".popover").on("mouseleave", function () {
				$(_this).popover('hide');
			});
		}).on("mouseleave", function () {
			var _this = this;
			setTimeout(function () {
				if (!$(".popover:hover").length) {
					$(_this).popover("hide");
				}
			}, 300);
	});

	url_params = getUrlVars();
	if( url_params.utm_source !== undefined && url_params.utm_term !== undefined && url_params.utm_source != 0 && url_params.utm_term  != 0) {
		$('.utm_source').val(url_params.utm_source);
		$('.utm_term').val(url_params.utm_term);
		$('.cookie-cart-save').attr('data-utm_source',url_params.utm_source);
		$('.cookie-cart-save').attr('data-utm_term',url_params.utm_term);
		$('#utm_source_for_customorder').val(url_params.utm_source);
		$('#utm_term_for_customorder').val(url_params.utm_term);
	}

    if(url_params !== undefined && url_params['review-edition'] != '1') {
    	clean_url();
	}
	
});
</script>
@endsection
