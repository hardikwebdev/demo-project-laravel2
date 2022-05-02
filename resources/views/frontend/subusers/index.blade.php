@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Sub Users')
@section('content')

{{-- <section class="transactions-header filter-header">
	<div class="container">
		<div class="profile-detail">
			<div class="row cus-filter align-items-center">
				<h2 class="heading">Sub Users</h2>
			</div>    
		</div>    
	</div>    
</section> --}}
<!-- @include('frontend.seller.header') -->


<section class="block-section transactions-table popular-services popular-tab-icon user-profile-tab">
	<div class="container p-0">
		<div class="row m-0 justify-content-center">
			<div class="col-lg-3">
				@include('frontend.seller.myprofile_tabs')
			</div>

			<div class="col-lg-8">
				<div class="popular-tab-item p-0">
					<div class="row cus-filter align-items-center p-4 mt-3 m-0 border-0">
						<div class="col-12 col-sm-4 pad0">
							<div class="transactions-heading"><span>{{count($model)}}</span> Sub Users Found
							</div>
						</div>
						<div class="col-12 col-sm-8 pad0 mt-2 mt-sm-0">
							<div class="sponsore-form">
								<div class="update-profile-btn"> 
									<div class="m-dropdown m-dropdown--inline m-dropdown--arrow m-dropdown--align-right m-dropdown--align-push">
										<a href="javascript:void(0)" data-target="#create_sub_users_modal" data-toggle="modal" data-url="{{route('create_sub_users')}}" class="btn font-14 px-3 py-2 register-bg-light-primary" data-id="0">Create Sub Users</a>
									</div>
								</div>
							</div>    
						</div>
					</div>

					<div class="cus-filter-data border-top px-4 pb-4">
						<div class="cus-container-two">    
							<div class="cus-table-overflow-auto cus-account-table" id="subusers_table">
								@include("frontend.subusers.subusers_table")
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>  
</section>  

<div id="create_sub_users_modal" class="modal fade custompopup" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Create New Sub User</h4>
			</div>
			<div class="modal-body">
				{{ Form::open(['route' => ['store_sub_users'], 'method' => 'POST', 'id' => 'create_frm_sub_user']) }}

				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="fullname">Full Name</label>
							<input type="text" name="name" class="form-control" value="" id="name" placeholder="Enter full name">
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label>Email</label>
							<input type="email" name="email" class="form-control" value="" placeholder="Enter email address">
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-lg-6">
						<div class="form-group">
							<label>Enter Password</label>
							<input type="password" value="" class="form-control" name="password" id="password" placeholder="Enter password">
						</div>
					</div>
					<div class="col-lg-6">
						<div class="form-group">
							<label>Confirm Password</label>
							<input type="password" value="" class="form-control" name="confirm_password" id="confirm_password" placeholder="Enter Confirm Password">
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-4">
						<div class="form-group cusswitch">
							<label for="notification">Status</label>
							<label class="cus-switch togglenotification">
								{{ Form::checkbox('status', 1, 0,["class"=>"toggle-input add_user_status"]) }}
								<span class="checkslider round"></span>
							</label>
						</div>
					</div>
					<div class="col-lg-4">
						<div class="form-group cusswitch">
							<label for="notification">Allow selling access?</label>
							<label class="cus-switch togglenotification">
								@if(Auth::check() && Auth::user()->is_premium_seller() == true)
								{{ Form::checkbox('is_seller_subuser', 1, 0,["class"=>"toggle-input add_seller_permission"]) }}
								<span class="checkslider round"></span>
								@else 
								{{ Form::checkbox('is_seller_subuser', 1, 0,["class"=>"toggle-input add_seller_permission",'disabled'=>'true']) }}
								<span class="checkslider round disabledslider"></span>
								@endif
							</label>
						</div>
					</div>
					<div class="col-lg-4">
						<div class="form-group cusswitch">
							<label for="notification">Allow buying access?</label>
							<label class="cus-switch togglenotification">
								{{ Form::checkbox('is_buyer_subuser', 1, 0,["class"=>"toggle-input add_buyer_permission"]) }}
								<span class="checkslider round"></span>
							</label>
						</div>
					</div>
					<div class="col-lg-12" id="add_buyer_permission" style="display: none;">
						<div class="row">
							<div class="col-lg-12">
								<h6>Permission List</h6>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-12">
								<div class="form-check">
									<input class="form-check-input" type="checkbox" value="1" id="add_can_make_purchases" name="can_make_purchases">
									<label class="form-check-label font-16" for="add_can_make_purchases">
										Can make purchases?
									</label>
								</div>
							</div>
						</div>
						<div class="row add_can_make_purchases box-border p-3 mb-4" style="display: none;">
							<div class="col-lg-6 mt-2">
								<div class="form-check">
									<input class="form-check-input" type="checkbox" value="-1" id="add_unlimited_purchase" name="add_unlimited_purchase">
									<label class="form-check-label" for="add_unlimited_purchase">
										Unlimited Purchase
									</label>
								</div>
							</div>
							<div class="col-lg-6">
								<div class="form-group">
									<input type="hidden" id="add_default_monthly_budget">
									<input class="form-control form-control-sm add_monthly_budget" type="text" placeholder="Monthly Budget" name="add_monthly_budget">
								</div>
								<small>Note: You can access this monthly budget till end of the current month <b>{{Carbon::now()->format('F')}}</b>. <span id="add_used_monthly_budget_msg"></span></small>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-12">
								<div class="form-check">
									<input class="form-check-input" type="checkbox" value="1" id="add_can_use_wallet_funds" name="add_can_use_wallet_funds">
									<label class="form-check-label font-16" for="add_can_use_wallet_funds">
										Can use wallet funds?
									</label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-12">
								<div class="form-check">
									<input class="form-check-input" type="checkbox" value="1" id="add_can_start_order" name="add_can_start_order">
									<label class="form-check-label font-16" for="add_can_start_order">
										Can start order?
									</label>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-12">
								<div class="form-check">
									<input class="form-check-input" type="checkbox" value="1" id="add_can_communicate_with_seller" name="add_can_communicate_with_seller">
									<label class="form-check-label font-16" for="add_can_communicate_with_seller">
										Can communicate with seller?
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="form-group text-right"> 
					<button type="submit" class="btn btn-primary">Create</button>
				</div>
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>

<div id="edit_sub_users_modal" class="modal fade custompopup" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close edit_sub_users_modal_close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Update Sub User</h4>
			</div>
			<div class="modal-body">
				{{ Form::open(['route' => ['update_sub_users'], 'method' => 'POST', 'id' => 'edit_frm_sub_user']) }}
				<input type="hidden" name="id" id="user_id">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="fullname">Full Name</label>
							<input type="text" name="name" class="form-control" id="name_id" placeholder="Enter full name">
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label>Email</label>
							<input type="email" name="email" class="form-control" placeholder="Enter email address" id="email_id">
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-lg-4">
						<div class="form-group cusswitch mb-0">
							<label for="notification">Status</label>
							<label class="cus-switch togglenotification">
								{{ Form::checkbox('status', 1, 1,["class"=>"toggle-input",'id'=>'status_id']) }}
								<span class="checkslider round"></span>
							</label>
						</div>
					</div>
				</div>

				<div class="form-group text-right"> 
					<button type="submit" class="btn btn-primary">Update</button>
				</div>
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>

<div id="security_modal" class="modal fade custompopup" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Change Password</h4>
			</div>
			<div class="modal-body">
				{{ Form::open(['route' => ['change_password_sub_users'], 'method' => 'POST', 'id' => 'security_frm_sub_user']) }}
				<input type="hidden" name="id" id="subuser_id">
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<label for="fullname">Full Name</label>
							<input type="text" name="name" class="form-control" id="sec_name_id" placeholder="Enter full name" disabled>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-lg-6">
						<div class="form-group">
							<label>Enter Password</label>
							<input type="password" value="" class="form-control" name="password" placeholder="Enter password">
						</div>
					</div>
					<div class="col-lg-6">
						<div class="form-group">
							<label>Confirm Password</label>
							<input type="password" value="" class="form-control" name="confirm_password" placeholder="Enter Confirm Password">
						</div>
					</div>
				</div>

				<div class="form-group text-right"> 
					<button type="submit" class="btn btn-primary">Change Password</button>
				</div>
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>

<div id="change_permission_modal" class="modal fade custompopup" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Change Permissions</h4>
			</div>
			<div class="modal-body">
				{{ Form::open(['route' => ['update_permissions'], 'method' => 'POST', 'id' => 'change_permission_frm_sub_user']) }}
				<input type="hidden" name="subuser_id" id="per_subuser_id">
				<div class="row">
					<div class="col-lg-12">
						<h6>Permission List</h6>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="1" id="can_make_purchases_id" name="can_make_purchases">
							<label class="form-check-label font-16" for="can_make_purchases_id">
								Can make purchases?
							</label>
						</div>
					</div>
				</div>
				<div class="row if_make_purchases_div box-border p-3 mb-4">
					<div class="col-lg-6 mt-2">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="-1" id="unlimited_purchase" name="unlimited_purchase">
							<label class="form-check-label" for="unlimited_purchase">
								Unlimited Purchase
							</label>
						</div>
					</div>
					<div class="col-lg-6">
						<div class="form-group">
							<input type="hidden" id="default_monthly_budget">
							<input class="form-control form-control-sm monthly_budget" type="text" placeholder="Monthly Budget" name="monthly_budget">
						</div>
						<small>Note: You can access this monthly budget till end of the current month <b>{{Carbon::now()->format('F')}}</b>. <span id="used_monthly_budget_msg"></span></small>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="1" id="can_use_wallet_funds" name="can_use_wallet_funds">
							<label class="form-check-label font-16" for="can_use_wallet_funds">
								Can use wallet funds?
							</label>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="1" id="can_start_order" name="can_start_order">
							<label class="form-check-label font-16" for="can_start_order">
								Can start order?
							</label>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<div class="form-check">
							<input class="form-check-input" type="checkbox" value="1" id="can_communicate_with_seller" name="can_communicate_with_seller">
							<label class="form-check-label font-16" for="can_communicate_with_seller">
								Can communicate with seller?
							</label>
						</div>
					</div>
				</div>

				<div class="form-group text-right"> 
					<button type="submit" class="btn btn-primary">Change Permissions</button>
				</div>
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>
@endsection

@section('scripts')
<script src="{{front_asset('js/bootbox.min.js')}}"></script>

<script>
$(document).ready(function(){
	var is_buyer_unchecked = 0;

	$(document).on('click', '.page-link', function(event){
		event.preventDefault(); 
		var page = $(this).attr('href');
		if (page!='' && page!=null) {
			$.ajax({
				type: "GET",
				url: page,
				success: function (data) {
					$('#subusers_table').html(data);
				}
			});
		}
	});

	$(document).on('click','.edit_sub_users_link',function(){
		var info = $(this).data('info');
		console.log();
		console.log(info.sub_user_permissions.is_seller_subuser);
		$('#user_id').val(info.id);
    	$('#name_id').val(info.Name);
		$('#email_id').val(info.email);
		if(info.sub_user_permissions.is_buyer_subuser == 0 && info.sub_user_permissions.is_seller_subuser == 0){
			$('#status_id').prop('checked', false);
			$('#status_id').addClass('edit_user_status');
		}else{
			$('#status_id').prop('checked', info.status);
		}
		init();
		$('#edit_sub_users_modal').modal('show');
	});

	$(document).on('click','.edit_sub_users_modal_close',function(){
		$('#edit_frm_sub_user').bootstrapValidator('resetForm', true);
	});

	$(document).on('click','.security_link',function(){
		var info = $(this).data('info');
		$('#subuser_id').val(info.id);
    	$('#sec_name_id').val(info.Name);

		$('#security_modal').modal('show');
	});

	$(document).on('click','.change_permission_link',function(){
		var info = $(this).data('info');
		$('#per_subuser_id').val(info.subuser_id);
    	if(info.can_make_purchases != 0) {
			$('#can_make_purchases_id').prop('checked', true);
		}
		if(info.can_make_purchases == -1) {
			$('#unlimited_purchase').prop('checked', true);
		}
		if(info.can_make_purchases == 0) {
			$('.if_make_purchases_div').hide();
		}else{
			$('.if_make_purchases_div').show();
		}
		if(info.can_make_purchases <= 0) {
			$('.monthly_budget').val('');
			$('.monthly_budget').attr('readonly',true);
		} else {
			$('.monthly_budget').val(info.can_make_purchases);
		}
		$('#default_monthly_budget').val(info.can_make_purchases);
		if(info.can_use_wallet_funds == 1){
			$('#can_use_wallet_funds').prop('checked', true);
		}
		if(info.can_start_order == 1){
			$('#can_start_order').prop('checked', true);
		}
		if(info.can_communicate_with_seller == 1){
			$('#can_communicate_with_seller').prop('checked', true);
		}

		if(info.used_monthly_budget > 0) {
			$('#used_monthly_budget_msg').text('$'+info.used_monthly_budget+' used till now for current month.')
		} else {
			$('#used_monthly_budget_msg').text('');
		}

		$('#change_permission_modal').modal('show');

		var is_unchecked = $(this).data('is_unchecked');
		if(is_unchecked !== undefined && is_unchecked != ""){
			is_buyer_unchecked = is_unchecked;
		}
	});

	$(document).on('change',"#can_make_purchases_id",function(){
		if(this.checked) {
			var default_monthly_budget = $('#default_monthly_budget').val();
			if(default_monthly_budget <= 0) {
				$('#unlimited_purchase').attr('checked',true);
			}
			$(".if_make_purchases_div").show();
		} else {
			$('#change_permission_frm_sub_user').bootstrapValidator('revalidateField', $("[name='monthly_budget']"));
			$(".if_make_purchases_div").hide();
		}
	});

	$(document).on('change',"#unlimited_purchase",function(){
		if(this.checked) {
			$(".monthly_budget").val('');
			$(".monthly_budget").attr('readonly',true);
		} else {
			var default_monthly_budget = $('#default_monthly_budget').val();
			if(parseFloat(default_monthly_budget) > 0) {
				$(".monthly_budget").val(default_monthly_budget);
			} else {
				$(".monthly_budget").val('');
			}
			$(".monthly_budget").removeAttr('readonly');
		}
		$('#change_permission_frm_sub_user').bootstrapValidator('revalidateField', $("[name='monthly_budget']"));
	});

	$(document).on('change','.change_buyer_switch',function(){
		var subuser_id = $(this).data('subuser_id');
		var buyer_flag = 0;
		if(this.checked) {
			buyer_flag = 1;
		}
		$.ajax({
			type: "POST",
			url: "{{route('change_sub_user_permission')}}",
			dataType: 'json',
			data: {"_token": "{{ csrf_token() }}","is_buyer_subuser":buyer_flag,"subuser_id":subuser_id},
			success: function(data) {
				if(data.status == 'success'){
                    alert_success(data.message);
					window.location.reload();
				} else {
					alert_error(data.message);
				}
			}
		});
	});

	$(document).on('change','.change_seller_switch',function(){
		var subuser_id = $(this).data('subuser_id');
		var seller_flag = 0;
		if(this.checked) {
			seller_flag = 1;
		}
		$.ajax({
			type: "POST",
			url: "{{route('change_sub_user_permission')}}",
			dataType: 'json',
			data: {"_token": "{{ csrf_token() }}","is_seller_subuser":seller_flag,"subuser_id":subuser_id},
			success: function(data) {
				if(data.status == 'success'){
                    alert_success(data.message);
					window.location.reload();
				} else {
					alert_error(data.message);
				}
			}
		});
	});

	/* Reload window */ 
	$(document).on('hidden.bs.modal','#change_permission_modal',function () {
		$('#change_permission_frm_sub_user').trigger('reset');
		if(is_buyer_unchecked != 0){
			$('.sec-'+is_buyer_unchecked).prop('checked', false);
			is_buyer_unchecked = 0;
		}
	});
	
	/* Add user buyer permission */
	$(document).on('change','.add_buyer_permission',function(){
		if(this.checked) {
			$('#add_buyer_permission').show();
		}else{
			$('#add_buyer_permission').hide();
			$('#add_can_make_purchases').prop('checked',false);
			$('#add_unlimited_purchase').prop('checked',false);
			$('.add_monthly_budget').val('');
			$('#add_can_use_wallet_funds').prop('checked',false);
			$('#add_can_start_order').prop('checked',false);
			$('#add_can_communicate_with_seller').prop('checked',false);
		}
	});

	$(document).on('change',"#add_can_make_purchases",function(){
		if(this.checked) {
			var default_monthly_budget = $('#add_default_monthly_budget').val();
			if(default_monthly_budget <= 0) {
				$('#add_unlimited_purchase').attr('checked',true);
			}
			$(".add_can_make_purchases").show();
		} else {
			$('#create_frm_sub_user').bootstrapValidator('revalidateField', $("[name='add_monthly_budget']"));
			$(".add_can_make_purchases").hide();
		}
	});

	$(document).on('change',"#add_unlimited_purchase",function(){
		if(this.checked) {
			$(".add_monthly_budget").val('');
			$(".add_monthly_budget").attr('readonly',true);
		} else {
			var default_monthly_budget = $('#add_default_monthly_budget').val();
			if(parseFloat(default_monthly_budget) > 0) {
				$(".add_monthly_budget").val(default_monthly_budget);
			} else {
				$(".add_monthly_budget").val('');
			}
			$(".add_monthly_budget").removeAttr('readonly');
		}
		$('#create_frm_sub_user').bootstrapValidator('revalidateField', $("[name='add_monthly_budget']"));
	});


	/* Edit user buyer permission */
	$(document).on('change','.edit_buyer_permission',function(){
		if(this.checked) {
			$('#edit_buyer_permission').show();
		}else{
			$('#add_buyer_permission').hide();
		}
	});

	$(document).on('change','.add_user_status',function(){
		if($(this).prop('checked') == true){
			if($('.add_seller_permission').prop('checked') == true){
				$('.add_user_status').prop('checked',true);
			}else if($('.add_buyer_permission').prop('checked') == true){
				if($('#add_can_make_purchases').prop('checked') == true || $('#add_can_use_wallet_funds').prop('checked') == true || $('#add_can_start_order').prop('checked') == true || $('#add_can_communicate_with_seller').prop('checked') == true){
					$('.add_user_status').prop('checked',true);
				}else{
					alert_error('Please enable any permission first.');
					$('.add_user_status').prop('checked',false);
				}
			}else{
				alert_error('Please enable any permission firsts.');
				$('.add_user_status').prop('checked',false);
			}
		}
	});

	$(document).on('change','.add_seller_permission',function(){
		check_add_permission();
	});

	$(document).on('change','.add_buyer_permission',function(){
		check_add_permission();
	})

	/* Check permission */
	function check_add_permission(){
		if($('.add_seller_permission').prop('checked') == false && $('.add_buyer_permission').prop('checked') == false){
			$('.add_user_status').prop('checked',false);
		}else{
			if($('.add_seller_permission').prop('checked') == true){
				$('.add_user_status').prop('checked',true);
			}else if($('.add_buyer_permission').prop('checked') == true){
				if($('#add_can_make_purchases').prop('checked') == true || $('#add_can_use_wallet_funds').prop('checked') == true || $('#add_can_start_order').prop('checked') == true || $('#add_can_communicate_with_seller').prop('checked') == true){
					$('.add_user_status').prop('checked',true);
				}else{
					$('.add_user_status').prop('checked',false);
				}
			}else{
				$('.add_user_status').prop('checked',false);
			}
		}
	}

	/* Status make it enable default */
	$(document).on('change','#add_can_make_purchases, #add_can_use_wallet_funds, #add_can_start_order, #add_can_communicate_with_seller', function(){
		if($('.add_buyer_permission').prop('checked') == true){
			if($('#add_can_make_purchases').prop('checked') == true || $('#add_can_use_wallet_funds').prop('checked') == true || $('#add_can_start_order').prop('checked') == true || $('#add_can_communicate_with_seller').prop('checked') == true){
				$('.add_user_status').prop('checked',true);
			}else{
				$('.add_user_status').prop('checked',false);
			}
		}else{
			$('.add_user_status').prop('checked',false);
		}
	});

	function init(){
		$('.edit_user_status').on('change',function(){
			alert_error('Please enable any permission first.');
			$(this).prop('checked',false);
		});
	}
	
});
</script>
@endsection