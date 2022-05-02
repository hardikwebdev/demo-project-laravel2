@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Jobs')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Jobs</h2>
			</div>    
		</div>    
	</div>    
</section>

<!-- popular service -->
<section class="block-section transactions-table pad-t4">
	<div class="container">
		@include('layouts.frontend.messages')
		<div class="row cus-filter align-items-center">
			<div class="col-md-4 col-12 pad0">
				<!--Display total number of job created by buyer-->
				<div class="transactions-heading"><span>{{count($Service)}}</span> Jobs Found
				</div>
			</div>
			<div class="col-md-8 col-12 pad0">
				<div class="sponsore-form service-filter">

					<form id="frmSearch" class="newClass" method="get" name="frmSearch">
							
						&nbsp;&nbsp;
						<!--search box for searching result-->
						<div class="form-group searchText1">
							{{ 
							  Form::text('searchtxt','',['class'=>'form-control mr-1',"placeholder"=>"Search Keyword",'id' =>"searchtxt",'style' => 'width:150px'])
							}}
						</div>
						<div class="form-group create-new-service ml-0"> 
							<button type="submit" class="btn mr-1">Search</button>
						</div>	
						<!--search status for job-->
						<div class="form-group selectStatus">
							{{ Form::select('status',[''=>'Select Status','active'=>'Active','draft'=>'Draft','awarded'=>'Awarded','expired'=>'Expired'],isset($_GET['status'])?$_GET['status']:'',['class'=>'form-control','id'=>'category_id','onchange'=>'this.form.submit()','style' => 'width:124px'])}}
						</div>	
						
					</form>
					<!--Add new job button-->
					@if(Auth::check() && Auth::user()->parent_id == 0 && App\User::is_soft_ban() == 0)
					<div class="ml-0 create-new-service"> 
						<a href="{{route('jobs.create')}}"><button type="button" class="btn">Create New Job</button></a>
					</div>
					@endif
				</div>    
			</div>
		</div>

	<!--List all jobs created by us-->
		@foreach($Service as $row)
		<div class="row service-item">
			<div class="col-md-4">
				<div class="service-box">
				
					<div class="service-detail">
						<div class="service-title">
							@if( (Auth::check() && Auth::user()->parent_id != 0 ) || App\User::is_soft_ban() == 1)
							<p class="text-header">
								{{$row->title}}
							</p>
							@else
							<a href="{{ ($row->status != 'denied' && strtotime($row->expire_on) >= time() || $row->status != 'denied' && $row->expire_on == null) ? route('jobs.edit',$row->seo_url) : 'javascript:void(0)'}}">
								<!--Display job title-->
								<p class="text-header">
									{{$row->title}}
								</p>
							</a>
							@endif
						</div>
						<div class="service-status">
							<!--Display job is recurring or not-->
							@if($row->status != 'draft') 
							<h6>
							@if($row->is_approved == '0' || $row->revisions != null && $row->revisions->is_approved == 0)
								<span class="badge badge-info" >Pending Admin Approval</span>
							@elseif($row->is_approved == 2 || $row->revisions != null && $row->revisions->is_approved == 2)
								<span class="badge badge-danger">Admin Rejected</span>
							@endif
							</h6>
							@endif

							@php
								$getData=$row->job_offers->where('status','accepted')->first();
							@endphp
							@if($getData != null)
								Awarded		
							@else
								@if($row->expire_on != null && strtotime($row->expire_on) <= time())
									<span class="text-danger">Expired</span>
								@else
									{{ucFirst($row->status)}}
								@endif
							@endif
							
							
						</div>  
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="package-detail">
					<div class="package-name">
						<!--Display job package type-->
						<p>Package name: 
							<span>{{isset($row->basic_plans->package_name)?$row->basic_plans->package_name:''}}{{$row->package_name}}
							</span>
						</p>  
					</div>

					<div class="package-price">
						<!--Display price for that job-->
						<p>Price: 
							<span>
								{{isset($row->basic_plans->price)?'$'.$row->job_min_price.' - '.'$'.$row->job_max_price:''}}
							</span>
						</p>  
					</div> 
				</div>
			</div>
			<!--Display options for modification of that particular job-->
			@if($row->status != "denied" && App\User::is_soft_ban() == 0)
			<div class="col-md-4">
				
				<div class="service-btn">
					<form class="form-inline" action="#">
						<div class="form-group">
							
							@if(Auth::check() && Auth::user()->parent_id == 0)
							@if($getData != null || $row->expire_on != null && strtotime($row->expire_on) < time())
							<select class="category_submit form-control hideSelectAwarted">
								<option>Option</option>
							</select>
							@else
								<select class="category_submit form-control" id="category_id" data-id="{{$row->secret}}" name="status">
									<option value="" selected="selected">Action</option>
									<!--to edit the job-->
									<option value="{{route('jobs.edit',$row->seo_url)}}">Edit</option>
									<!--to delete the job-->
									<option value="{{route('jobs.delete',$row->secret)}}">Delete</option>

									@if($row->status != 'paused')
									<!-- <option value="pause">Pause</option> -->
									@elseif($row->current_step == '6')
									<option value="{{route('change_status',['id'=>$row->secret,'status'=>'active'])}}">Active</option>
									@else
									<option value="{{route('change_status',['id'=>$row->secret,'status'=>'draft'])}}">Draft</option>
									@endif
								
								</select>
							@endif
							@endif
							
							<div class="ml-5 create-new-service"> 
								@if(Auth::check() && Auth::user()->parent_id == 0)
								@if($getData == null && $row->is_repost==0 && ($row->expire_on != null && strtotime($row->expire_on) <= time()))
									<div class="mb-1">
										<a id="repost-job" href="{{ route('job.repost',$row->seo_url)}}">
											<button type="button" class="btn">Repost</button>
										</a>
									</div>
								@endif
								@endif

								<!--View all bids for that job only by user-->
								<div class="mt-2-">
									<a href="{{ route('show.job_detail',$row->seo_url)}}">
										<button type="button" class="btn">View Bids</button>
									</a>
								</div>
							</div>
							

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

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
<script>

	$('#repost-job').click(function(e){
		e.preventDefault();
		bootbox.confirm({
			message: "Are you sure want to repost this job?",
			buttons: {
				confirm: {
					label: 'Confirm',
					className: 'btn-default'
				},
				cancel: {
					label: 'Cancel',
					className: 'btn-default'
				}
			},
			callback: function (result) {
				if(result == true){
					window.location.href=$('#repost-job').attr('href');
				}
			}
		});
	});


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
</script>
@endsection