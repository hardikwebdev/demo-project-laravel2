@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Services')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Services</h2>
			</div>    
		</div>    
	</div>    
</section>

<!-- popular service -->
<section class="block-section transactions-table pad-t4">
	<div class="container">
		@include('layouts.frontend.messages')
		<div class="row cus-filter align-items-center pt-lg-4">
			<div class="col-md-12 col-12 pad0">
				<div class="sponsore-form service-filter flex-wrap">
					<form id="frmSearch" class="newClass row m-0 mb-lg-3" method="get" name="frmSearch">
						<div class="col-12 col-md-auto d-flex px-2 pr-md-0 ">
							<div class="form-group searchText1 mb-0">
								{{ 
								Form::text('searchtxt',request('searchtxt'),['class'=>'form-control mr-1',"placeholder"=>"Search Keyword",'id' =>"searchtxt"])
								}}
							</div>
							<div class="form-group create-new-service mb-0"> 
								<button type="submit" class="btn mr-1">Search</button>
							</div>
						</div>
						<div class="col-12 col-md-auto mt-3 px-2 mt-md-0  pr-md-0">
							<div class="form-group mb-0">
								{{ Form::select('is_recurring',[''=>'All','2'=>'Normal','1'=>'Recurring','3'=>'Review Edition'],isset($_GET['is_recurring'])?$_GET['is_recurring']:'',['class'=>'form-control','onchange'=>'this.form.submit()']) }}
							</div>
						</div>
						<div class="col-12 col-md-auto mt-3 px-2 mt-md-0   pr-md-0">
							<div class="form-group selectStatus mb-0 px-0">
								{{ Form::select('status',[''=>'Select Status','active'=>'Active','draft'=>'Draft','denied'=>'Denied','permanently_denied'=>'Permanently Denied','paused'=>'Paused'],isset($_GET['status'])?$_GET['status']:'',['class'=>'form-control','id'=>'category_id','onchange'=>'this.form.submit()'])}}
							</div>	
						</div>
					</form>
					
					@if(Auth::user()->username != 'scottfarrar' && App\User::is_soft_ban() == 0)
					<div class="col-12 col-md-auto create-new-service m-0 px-2 mt-3 mt-lg-0 pr-md-0"> 
						<a href="{{route('create_services')}}" class="create_new_service" data-create="{{Auth::user()->can_create_service}}" data-profile="{{ Auth::user()->parent_id == 0 && (!Auth::user()->description || (!Auth::user()->profile_photo || !Auth::user()->photo_s3_key)) ? 1 : 0}}"><button type="button" class="btn">Create New Service</button></a>
					</div>
					@endif

					@if(Auth::user()->is_premium_seller($parent_uid) == true && App\User::is_soft_ban() == 0)
					<div class="col-12 col-md-auto create-new-service m-0 px-2 mt-3 mt-lg-0 pr-md-0"> 
						<a href="{{route('offer_bundle_discount')}}"><button type="button" class="btn">Combo Discount</button></a>
					</div>
					@endif

					<div class="col-12 col-md-auto create-new-service m-0 px-2 mt-3 mt-lg-0 pr-md-0">
						<a href="{{route('trash_services_list')}}"><button type="button" class="btn trash_btn">Trash Services</button></a>
					</div>
				</div>    
			</div>
		</div>
		<div class="row mt-2">
			<div class="col-md-12">
				<div class="transactions-heading"><span>{{count($Service)}}</span> Services Found </div>
			</div>
		</div>


		@foreach($Service as $row)
		<div class="row service-item">
			<div class="col-md-4">
				<div class="service-box">
					<div class="service-image">
					<a href="{{($row->status != 'permanently_denied' && App\User::is_soft_ban() == 0) ? route('overview_update',$row->seo_url) : 'javascript:void(0)'}}">
							@if(isset($row->images[0]))
								@if($row->images[0]->photo_s3_key != '')
								<img alt="product-image" class="img-fluid img-max-height"  src="{{$row->images[0]->media_url}}">
								@else
								<img alt="product-image" class="img-fluid img-max-height"  src="{{url('public/services/images/'.$row->images[0]->media_url)}}">
								@endif
							@endif
						</a>
					</div>
					<div class="service-detail">
						<div class="service-title">
						<a href="{{($row->status != 'permanently_denied' && App\User::is_soft_ban() == 0) ? route('overview_update',$row->seo_url) : 'javascript:void(0)'}}" class="open_service_edit" data-profile="{{ Auth::user()->parent_id == 0 && (!Auth::user()->description || (!Auth::user()->profile_photo || !Auth::user()->photo_s3_key)) ? 1 : 0}}">
								<p class="text-header text-capitalize mb-0 view-order-detail-btn">
									{{$row->title}}
								</p>
							</a>
						</div>
						<div class="service-status">
							@if($row->is_recurring == 1)
								<span class="recurring-lable">Recurring Service</span><br>
							@endif

							@if($row->is_review_edition == 1 && $row->review_edition_count < $row->no_of_review_editions)
							<span class="badge badge-primary">Review Edition</span><br>
							@endif

							<h6 class="mb-0 mb-sm-1">
							@if($row->is_approved == "0" || $row->revisions != null && $row->revisions->is_approved == 0)
								<span class="badge badge-info" >Pending Admin Approval</span>
							@elseif($row->is_approved == "1" && $row->revisions == null )
							@else
								<span class="badge badge-danger">Admin Rejected</span>
							@endif
							</h6>
							{{show_service_status($row->status)}}
							@if($row->is_private == 1)
								<img src="{{url('public/frontend/assets/img/Private.png')}}" class="img-fluid img-max-height private_badge">
							@endif
						</div>  
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="package-detail">
					<div class="package-name">
						<p>Package name: 
							<span>{{isset($row->basic_plans->package_name)?$row->basic_plans->package_name:''}}{{$row->package_name}}
							</span>
						</p>  
					</div>
					@if($row->is_recurring == 0)
					<div class="package-delivery">
						<p>Delivery: 
							<span>
								{{isset($row->basic_plans->delivery_days)?$row->basic_plans->delivery_days.' days':''}}
							</span>
						</p>  
					</div>
					@endif

					<div class="package-price">
						<p>Price: 
							@if($row->is_review_edition == 1 && $row->review_edition_count < $row->no_of_review_editions)
							{{isset($row->basic_plans->price)?'$'.$row->basic_plans->price:''}}
							<span class="re-text-strike">${{$row->basic_plans->review_edition_price}}</span>
							@else
							<span>{{isset($row->basic_plans->price)?'$'.$row->basic_plans->price:''}}</span>
							@endif
						</p> 
						
						@php
						$total_re_processing_orders = $row->get_total_re_processing_orders();
						$reviewText = '';
						if($total_re_processing_orders == 1){
							$reviewText = '('.$total_re_processing_orders.' order processing)';
						}elseif($total_re_processing_orders > 1){
							$reviewText = '('.$total_re_processing_orders.' orders processing)';
						}
						@endphp

						@if($row->review_edition_count > 0 || $total_re_processing_orders > 0)
						<p>Review Edition Orders : {{$row->get_total_review_editions()}} {{$reviewText}}</p>
						@endif
					</div> 

					
				</div>
			</div>
			@if($row->status != "denied" && $row->status != "permanently_denied")
			<div class="col-md-4">
				<div class="service-btn">
					<form class="form-inline d-block d-md-inline-block" action="#">
						<div class="form-group">
							<select class="category_submit form-control custom_select_width custom_select_width_full" id="category_id" data-id="{{$row->id}}" name="status">
								<option value="" selected="selected">Action</option>
								<option value="{{route('overview_update',$row->seo_url)}}">Edit</option>
								<option value="{{route('trash_service',$row->seo_url)}}">Delete</option>
								@if($row->status != 'paused' && $row->current_step >= '5')
								<option value="pause">Pause</option>
								@elseif($row->current_step == '6')
								<option value="{{route('change_status',['id'=>$row->id,'status'=>'active'])}}">Active</option>
								@else
								<option value="{{route('change_status',['id'=>$row->id,'status'=>'draft'])}}">Draft</option>
								@endif

								@if($row->status == 'paused' && $row->current_step >= '5')
								<option value="{{route('service_publish',$row->seo_url)}}">Reactivate</option>
								@endif

								@if($row->status == 'active')
									<option value="{{route('services_gallery',$row->seo_url)}}">Edit Video</option>
									@if($row->is_recurring == 0)
										<option value="{{ route('coupan',[ 'id' => $row->id, 'type' => $row->basic_plans->plan_type ] )}}">Coupon</option>
									@endif
								@endif

								@if((Auth::user()->is_premium_seller() == true && $row->is_recurring == 0) || (Auth::user()->parent_id != 0 && $row->is_recurring == 0))
									<option value="{{route('offer_volume_discount',$row->id)}}">Volume Discount</option>
								@endif

							</select>
						</div>
					</form>
					@if($row->status == 'active' && Auth::user()->parent_id == 0 && $row->is_private == 0 )
					<div class="prompt-btn d-block d-md-inline-block mt-sm-3 mt-lg-0"> 
						<a href="{{route('boostService',[$row->seo_url])}}">
							<button type="button" class="btn">Promote Service</button>
						</a>
					</div>
					@endif
					@if( $row->status == 'active' && Auth::user()->parent_id == 0 && $row->is_approved == '1')
					<div class="pacakge-private prompt-btn mt-3 d-block d-md-inline-block">
						<button type="button" data-clipboard-text="{{route('services_details',[$row->user->username,$row->seo_url])}}" class="btn copy_btn custom_select_width_full">Copy URL</button>
					</div>
					@endif
					@if($row->current_step >= 5 && $row->uid == Auth::id() && ($row->status == 'draft' || $row->status == 'paused' || $row->is_approved == "0"))
					<div class="prompt-btn d-block d-md-inline-block @if($row->status == 'active' && Auth::user()->parent_id == 0 && $row->is_private == 0 ) pacakge-private mt-3 @endif"> 
						<a href="{{route('services_details',[$row->user->username,$row->seo_url])}}" target="_blank">
							<button type="button" class="btn custom_select_width_full">Preview</button>
						</a>
					</div>
					@endif
				</div>
			</div>
			@else 
			<div class="col-md-4">
				<div class="service-btn">
					<form class="form-inline" action="#">
						<div class="form-group">
							<select class="category_submit form-control custom_select_width" id="category_id" data-id="{{$row->id}}" name="status">
								<option value="" selected="selected">Action</option>
								@if($row->status == "denied" && App\User::is_soft_ban() == 0)
								<option value="{{route('overview_update',$row->seo_url)}}">Edit</option>
								@endif
								<option value="{{route('trash_service',$row->seo_url)}}">Delete</option>
							</select>
						</div>
					</form>
				</div>
			</div>
			@endif
		</div>
		@endforeach
		
		<div class="clearfix"></div>
		{{ $Service->links("pagination::bootstrap-4") }}

	</div>        
</section>  
@endsection


@section('css')
<style>
.custom_select_width {
	width: 115px !important;
}
.trash_btn {
	background: linear-gradient(90deg, #ef3d4e , #ff8e98 ) !important;
}

</style>
@endsection

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script>
	function isSponseredFunction(element) {
		var id = element;
		var confirmPause ;
		$.ajax({
			url : "{{route('check_if_sponsered')}}",
			data : {'_token':"{{ csrf_token() }}",'id' :id},
			type : 'post',
			success : function(data){
				var messgeConfirm;
				if(data.status == 200){

					messgeConfirm =  'There is pending service promotion request scheduled on '+data.startdate+'. If Job is in Pause status on scheduled date will not be visible to other users. Do you still want to continue?';

				}else{
					messgeConfirm = 'Are you sure you want to pause this service?';
				}
				bootbox.confirm({
					message: messgeConfirm,
					buttons: {
						confirm: {
							label: 'Continue',
							className: 'btn-default'
						},
						cancel: {
							label: 'Cancel',
							className: 'btn-default'
						}
					},
					callback: function (result) {
						if(result == true){
							$.ajax({
								type : 'get',
								data :{'_token':"{{ csrf_token() }}",'id':id ,'status':'paused'},
								url :"{{route('change_status')}}",
								success:function(){
									window.location.reload();
								}
							})
						}else{
							$(".category_submit").prop('selectedIndex',0);
						}
					}
				});
			}
		});
	}

	$(document).on('click', '.create_new_service', function (e) {
		e.preventDefault();
		var href = $(this).attr('href');
		var allow = $(this).data('create');
		var profile = $(this).data('profile');
		if(profile == 1) {
			toastr.error("Please update your profile to include a profile photo and description before creating or editing a service.", "Error");
			setTimeout(() => {
				window.location = href;
			}, 1500);
		} else if(allow == 0) {
			window.location = href;
		} else {
			toastr.error("Your account is unable to create new services at this time. Please contact support for more information.", "Error");
		}
	});

	$(document).on('click', '.open_service_edit', function (e) {
		e.preventDefault();
		var href = $(this).attr('href');
		var profile = $(this).data('profile');
		if(profile == 1 && href != "javascript:void(0)") {
			toastr.error("Please update your profile to include a profile photo and description before creating or editing a service.", "Error");
			setTimeout(() => {
				window.location = href;
			}, 1500);
		} else {
			window.location = href;
		}
	});
</script>
@endsection