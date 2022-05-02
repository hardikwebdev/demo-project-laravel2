@extends('layouts.frontend.main')

@section('pageTitle', 'demo - Create Combo Discount')
@section('content')

<section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">{{ucFirst($type)}} - Create Combo Discount</h2>
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
						Create Combo Discount
					</div> 

					@if($errors->any())
				<div class="alert alert-danger" role="alert">
						{{$errors->first()}}
					</div>
				@endif
					
					{{ Form::open(['route' => [($type=='courses') ? 'course.store_bundle_offer' : 'store_bundle_offer'], 'method' => 'POST', 'id' => 'frm_bundle_offer']) }}

					<div class="row mb-3">
						
						<div class="col-lg-6">
							<div class="form-group text-color-3">
								<label class="text-color-2">Select Services <span class="text-danger">*</span></label>
								{{Form::select('service_ids[]',$serviceList,null,["class"=>"serviceList form-control","multiple"=>"multiple","id"=>"service_ids"])}}
					        </div>
						</div>
						<div class="col-lg-6"></div>

						<div class="col-lg-3">
							<div class="form-group">
								<label>Enter Discount <span class="text-danger">*</span></label>
								<div class="input-group">
									{!! Form::text('discount', null,['class' => 'form-control','id'=>'discount','placeholder' => 'Enter Discount','autocomplete' => 'off']) !!}
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
									<input name="is_combined_other" type="checkbox" value="1">  
									<span class="checkmark"></span>
								</label>
								<div class="detail-box">
									<lable>can not be combined with other discounts</lable>
								</div>
							</div>
						</div>
						<div class="col-lg-6"></div>

						<div class="col-lg-2">
							<button type="submit" class="send-request-buttom btn-bundle-submit">Create</button>
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
       // placeholder: "Select a Services",
        allowClear: true,
    });
</script>
@endsection
