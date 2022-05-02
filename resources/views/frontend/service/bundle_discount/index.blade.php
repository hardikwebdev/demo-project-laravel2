@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Offer Combo Discounts')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">{{ucFirst($type)}} - Offer Combo Discounts</h2>
			</div>    
		</div>    
	</div>    
</section>


<section class="block-section transactions-table">
	<div class="container">
		@include('layouts.frontend.messages')

		<div class="row cus-filter align-items-center mt-4">
			<div class="col-md-8 col-12 pad0">
				<div class="transactions-heading"><span>{{count($model)}}</span> Combo Offer Found
				</div>
			</div>
			<div class="col-md-4 col-12 pad0">
				<div class="sponsore-form">
					<div class="update-profile-btn"> 
						<div class="m-dropdown m-dropdown--inline m-dropdown--arrow m-dropdown--align-right m-dropdown--align-push">
							<a href="{{ ($type=='courses') ? route('course.create_bundle_offer') : route('create_bundle_offer') }}" class="btn" data-id="0">Create Combo Offer</a>
						</div>
					</div>
				</div>    
			</div>
		</div>

		<div class="cus-filter-data">
			<div class="cus-container-two">    
				<div class="table-responsive">
					<table class="manage-sale-tabel custom">
						<thead>
							<tr class="manage-sale-head custom-bold-header">
								<td class="width180">Created On</td>
								<td class="text-center">Services</td>
								<td class="text-center">Discount (%)</td>
								<td class="text-center">Action</td>
							</tr>
						</thead>
						<tbody>
							@if(count($model))
							@foreach($model as $row)
							<tr>
								<td>
									{{date('d M Y',strtotime($row->created_at))}}
								</td>
								<td class="text-center">
									@foreach($row->bundle_services as $bundle)
									<label class="completed text-capitalize">{{$bundle->service->title}}</label> 
									@endforeach
								</td>
								<td class="text-center">
									{{$row->discount}}%
								</td>
								<td class="text-center">
									<a href="{{($type=='courses') ? route('course.edit_bundle_offer',$row->id) : route('edit_bundle_offer',$row->id)}}"><img src="{{url('public/frontend/images/dashboard/edit.png')}}"></a>
									&nbsp;&nbsp;
									<a href="javascript:void(0)" class="delete-bundle-offer" data-url="{{($type=='courses') ? route('course.delete_bundle_discount',$row->id) : route('delete_bundle_discount',$row->id)}}"><img src="{{url('public/frontend/images/dashboard/delete.png')}}"></a>
								</td>
							</tr>
							@endforeach
							@else	
							<tr>
								<td colspan="7" class="text-center">
									No any combo offer found
								</td>
							</tr>
							@endif

						</tbody>
					</table>

					<div class="clearfix"></div>
					<div class="text-center">
						@if(count($model))
						{{ $model->links("pagination::bootstrap-4") }}
						@endif
					</div>
				</div>
			</div>
		</div>
	</div>  
</section>  

@endsection

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
@endsection