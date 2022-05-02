@extends('layouts.frontend.main')

@section('pageTitle', 'demo - Update Combo Discount')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">{{ucFirst($type)}} - Update Combo Discount</h2>
			</div>    
		</div>    
	</div>    
</section>

<section class="overview-section">
	<div class="container">
		<div class="row">
			<div class="col-lg-12">
				<div class="popular-grid">
					<div class="seller pb-2">
						Update Combo Discount
					</div> 
					{{ Form::open(['route' => [($type=='courses') ? 'course.update_bundle_offer':'update_bundle_offer'], 'method' => 'POST', 'id' => 'frm_bundle_offer']) }}
					<input type="hidden" name="id" value="{{$model->id}}">
					<div class="row mb-3">
						
						<div class="col-lg-6">
							<div class="form-group text-color-3">
								<label class="text-color-2">Select Services <span class="text-danger">*</span></label>
								{{Form::select('service_ids[]',$serviceList,$selectedServiceIds,["class"=>"serviceList form-control","multiple"=>"multiple","id"=>"service_ids"])}}
					        </div>
						</div>
						<div class="col-lg-6"></div>

						<div class="col-lg-3">
							<div class="form-group">
								<label>Enter Discount <span class="text-danger">*</span></label>
								<div class="input-group">
									{!! Form::text('discount', $model->discount,['class' => 'form-control','id'=>'discount','placeholder' => 'Enter Discount','autocomplete' => 'off']) !!}
									<div class="input-group-prepend">
										<span class="input-group-text group-before-text"> &nbsp;%&nbsp; </span>
									</div>
								</div>
							</div>
						</div>
						<div class="col-lg-9"></div>

						<div class="col-lg-6">
							<div class="form-group  is-combined-discount">
								<label class="cus-checkmark">  
									{!! Form::checkbox('is_combined_other', 1,$model->is_combined_other) !!}
									<span class="checkmark"></span>
								</label>
								<div class="detail-box">
									<lable>can not be combined with other discounts</lable>
								</div>
							</div>
						</div>
						<div class="col-lg-6"></div>

						<div class="col-lg-2">
							<button type="submit" class="send-request-buttom btn-bundle-submit">Update</button>
						</div>
						
					</div>
					{{ Form::close() }}
				</div>
			</div>   
		</div>
	</div>
</section>
@endsection

@section('css')
<link href="{{front_asset('select2/select2.min.css')}}" rel="stylesheet" />
@endsection

@section('scripts')
<script src="{{front_asset('select2/select2.min.js')}}"></script>

<script type="text/javascript">
  	$(".serviceList").select2({
        allowClear: true,
    });
</script>
@endsection
