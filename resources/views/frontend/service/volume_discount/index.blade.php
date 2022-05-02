@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Offer Volume Discounts')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Offer Volume Discounts</h2>
			</div>    
		</div>    
	</div>    
</section>


<section class="block-section transactions-table">
	<div class="container">
		@include('layouts.frontend.messages')

		<div class="row cus-filter align-items-center mt-4">
			<div class="col-md-8 col-12 pad0">
				<div class="transactions-heading"><span>{{count($model)}}</span> Volume Offer Found (<span class="text-capitalize">{{$service->title}}</span>)
				</div>
			</div>
			<div class="col-md-4 col-12 pad0">
				<div class="sponsore-form">
					<div class="update-profile-btn"> 
						<div class="m-dropdown m-dropdown--inline m-dropdown--arrow m-dropdown--align-right m-dropdown--align-push">
							<button type="button" class="btn edit-volume-offer" data-id="0">Create Volume Offer</button>
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
								<td class="text-center">Service Volume</td>
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
										{{$row->volume}}
									</td>
									<td class="text-center">
										{{$row->discount}}%
									</td>
									<td class="text-center">
										<a href="javascript:void(0);" data-id="{{$row->id}}" data-volume="{{$row->volume}}" data-discount="{{$row->discount}}" data-is_combined_other="{{$row->is_combined_other}}" class="edit-volume-offer"><img src="{{url('public/frontend/images/dashboard/edit.png')}}"></a>
										&nbsp;&nbsp;
										<a href="#" class="delete-volume-offer" data-url="{{route('delete_volume_service',$row->id)}}"><img src="{{url('public/frontend/images/dashboard/delete.png')}}"></a>
									</td>
								</tr>
								@endforeach
							@else	
								<tr>
									<td colspan="7" class="text-center">
										No any volume offer found
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


<!--begin::Create offer Modal-->
<div class="modal fade custommodel-new" id="modal_offer_volume_discount" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header req-withdrawal-header">
				<h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Offer Volume Discounts</h5>

				<button type="button" class="close colse-offer-modal" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			{!! Form::open(['route' => ['store_volume_discount'],'method' => 'post', 'id' => 'frm_offer_volume_discount']) !!}
			<input type="hidden" name="id" value="0" id="offer_id">
			<input type="hidden" name="service_id" value="{{$service->id}}" id="service_id">
			<input type="hidden" id="service_price" value="{{$service->basic_plans->price}}">

			<div class="modal-body">
				<div class="col-lg-12">
					<div id="withdraw_response"></div>
				</div>

				<div class="col-lg-12">
					<label for="recipient-name" class="form-control-label">Quantity of service</label>
				</div>

				<div class="col-lg-12">
					<div class="form-group">
						{!! Form::text('volume', null,['class' => 'form-control','id'=>'volume','placeholder' => 'Enter quantity of service']) !!}
					</div>
				</div>

				<div class="col-lg-12">
					<label for="recipient-name" class="form-control-label">Discount</label>
				</div>

				<div class="col-lg-12">
					<div class="form-group">
						<div class="input-group">
							{!! Form::text('discount', null,['class' => 'form-control','id'=>'discount','placeholder' => 'Enter discount','autocomplete' => 'off']) !!}
							<div class="input-group-prepend">
								<span class="input-group-text group-before-text"> &nbsp;%&nbsp; </span>
							</div>
						</div>
					</div>
				</div>

				<div class="col-lg-12">
					<div class="form-group  is-combined-discount">
						<label class="cus-checkmark">  
							<input name="is_combined_other" type="checkbox" value="1" id="is_combined_other">  
							<span class="checkmark"></span>
						</label>
						<div class="detail-box">
							<lable>can not be combined with other discounts</lable>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer center-block">
				<button type="submit" class="btn send-request-buttom-new btn-volume-submit withdraw-width">Create</button>
			</div>
			{{ Form::close() }}
		</div>
	</div>
</div>
<!--end::Create offer Modal-->

</section>  

@endsection

@section('scripts')
<!--Bootbox-->
<script src="{{front_asset('js/bootbox.min.js')}}"></script>
@endsection