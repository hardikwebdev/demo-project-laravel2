@php
use App\Commands\SortableTrait;
@endphp
@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Orders')
@section('content')
<section class="transactions-header filter-header">
    <div class="container">
        <div class="profile-detail">
            <div class="row cus-filter align-items-center">
                <h2 class="heading">Review Submitted</h2>
            </div>
        </div>
    </div>
</section>


<section class="transactions-table pad-t4">
    <div class="container">
        <div class="alert alert-success" role="alert">
            <div class="row">
                <div class="col-md-1 text-center top_space">
                    <i class="fa fa-check fa-3x"></i>
                </div>
                <div class="col-md-11">
                    <h5><b>Review submitted - Thank You!</b></h5>
                    <h6>Thank you so much for sharing your experience with us.  Would you want to share your experience about these other orders and leave them a review?</h6>
                    @if(count($Order->order_tip) == 0)
                        <h6><a href="{{route('buyer_orders_details',$Order->order_no)}}">Want to leave a tip? Click here to send one!</a></h6>
                    @endif
                </div>
            </div>
        </div>
        @include('layouts.frontend.messages')
    </div>
</section>

<section class="custom-block-section">
    <div class="container">
        @include('frontend.buyer.review_reminder_table')
    </div>
</section>

<div id="review_modal" class="modal fade custompopup" role="dialog">
	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Rating</h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-lg-12">
						<div class="form-group mb-3 custom">
                            <label class="custom-title-complete-order">Give ratings to seller</label>
                            <input type="hidden" id="order_secret_id">
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
                                <div class="has-error" id="id_rating_error" style="text-align: left; display: none;"><small class="help-block">Please give star ratings.</small></div>
							</div>
						</div>
                    </div>
                    <div class="col-lg-12">
						<div class="form-group">
							<textarea class="form-control" rows="6" id="complete_note" name="complete_note" placeholder="Enter your reviews here..." maxlength="2500"></textarea>
							<div class="has-error text-left" id="note_error" style="display: none;"><small class="help-block">Please add a description.</small></div>
							<p class="text-right"><span id="chars">0</span>/2500 Characters Max</p>  
						</div>
					</div>
					<div class="col-lg-12 create-new-service update-account text-right">
						<button type="button" class="btn btn-primary" id="save_rating_btn">Save</button> 
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="completeorderpopup" class="modal fade custompopup" role="dialog">
	<div class="modal-dialog">
		<!-- Modal content-->
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title"></h4>
			</div>
			<div class="modal-body">
				{{ Form::open(['url' => '', 'method' => 'POST','class'=>'','id'=>'frmCompleteOrder']) }}
				<div class="row">
					<div class="col-lg-12">
						<div class="form-group mb-3 custom">
							<label class="custom-title-complete-order">Give ratings to seller</label>
							<div class="star-ratings">
								<div class="stars stars-example-fontawesome-o">
									<select id="seller_rating_new" name="seller_rating" data-current-rating="0" autocomplete="off">
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
					</div>

					<div class="col-lg-12">
						<div class="form-group">
							<textarea class="form-control" rows="6" id="complete_note" name="complete_note" placeholder="Enter here..." maxlength="2500"></textarea>
							<div class="text-danger text-left note-error"></div>
							<p class="text-right"><span id="chars">0</span>/2500 character Max</p>  
						</div>
					</div>
					<div class="col-lg-12 create-new-service update-account text-right">
						<button type="submit" class="btn btn-primary btn-complte-order"></button> 
					</div>
				</div>
				{{ Form::close() }}
			</div>
		</div>
	</div>
</div>

<div class="modal fade custommodel-new" id="tip-popup" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header req-withdrawal-header">
                <h5 class="modal-title bold-lable" id="exampleModalLabel bold-lable">Leave Seller Tip</h5>

                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {!! Form::open(['route' => ['orderTipCheckout'],'method' => 'post', 'id' => 'frm_tip_checkout']) !!}
            <div class="modal-body">
                <div class="col-lg-12">
                    <div id="tip_response"></div>
                </div>
                <div class="col-lg-12">
                    <div class="form-group">
                        <select class="form-control" name="payment_from" id="payment_from">
                            {{-- <option value="">Select Payment Option</option> --}}
                            <option value="1">Paypal</option>
                            <option value="2">Credit Card</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="order_id" value="{{$Order->id}}">

                <div class="col-lg-12 from_wallet_amount">
                    @if(Auth::user()->earning > 0)
                    <div class="input-container">
                        <label class="cus-checkmark from-wallet-chk">  
                            <input type="checkbox" id="from_wallet" name="from_wallet" class="cus-checkmark from-wallet-chk" value="1" checked="">

                            <label for="from_wallet" class="label-check">
                                <span class="checkbox primary"><span></span></span>
                                Use From Wallet (${{dispay_money_format(Auth::user()->earning)}})
                            </label>
                            <span class="checkmark"></span>
                        </label>
                    </div>
                    @endif
                </div>

                <div class="col-lg-12 hide">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text group-before-text"> &nbsp;$&nbsp; </span>
                            </div>
                            {!! Form::text('tip_amount', 5,['class' => 'form-control','placeholder' => 'Enter The Amount You Wish To Tip']) !!}
                        </div>
                        <br>
                    </div>
                </div>
                <hr>
                <div class="help text-center">Select a tip to send to the seller.  They'll receive 100% of the tip you give them.</div>

                <div class="col-lg-12 center-block">
                    <button type="button" class="tip-price round-button selected" data-price="5">$5</button>
                    <button type="button" class="tip-price round-button" data-price="10">$10</button>
                    <button type="button" class="tip-price round-button" data-price="15">$15</button>
                    <button type="button" class="tip-price round-button" data-price="20">$20</button>
                    <button type="button" class="tip-price round-button" data-price="25">$25</button>
                </div>
            </div>
            
            <div class="modal-footer center-block">
                <button type="submit" name="Send Tip" class="btn send-request-buttom-new withdraw-width">Send Tip</button>
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>

{{-- Share service modal --}}
@if (request()->input('is_share') == 1 && $service_can_share == true )
    @include('frontend.buyer.include.social_media_share_modal')
@endif
@endsection

@section('css')
<link rel="stylesheet" href="{{front_asset('rating/dist/themes/css-stars.css')}}">
<style>
    .custom-block-section {
        background: white;
    }
    .first_div {
        font-size: 20px;
        text-align: center;
    }
    .star_checked {
        color: #ffa200;
    }
    .star_unchecked {
        color: gray;
    }
    .top_space {
        top: 20px;
    }
</style>
@endsection

@section('scripts')
<script src="{{front_asset('rating/jquery.barrating.js')}}"></script>
<script type="text/javascript">
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
    $('document').ready(function(){
        $('.first_div').on('click', function(){
            $('#order_secret_id').val($(this).data('order'));
            $('#review_modal').modal('show');
        });

        $('#save_rating_btn').on('click', function(){
            $('#id_rating_error').hide();
            $('#note_error').hide();
            var order_id = $('#order_secret_id').val();
            var seller_rating = $('#seller_rating').val();
            var complete_note = $('#complete_note').val();
            complete_note = complete_note.trim();
            if(seller_rating.length == 0 && complete_note.length == 0) {
                $('#id_rating_error').show();
                $('#note_error').show();
                return false;
            } else if(seller_rating.length == 0) {
                $('#id_rating_error').show();
                return false;
            } else if(complete_note.length == 0) {
                $('#note_error').show();
                return false;
            } else {
                $('#id_rating_error').hide();
                $('#note_error').hide();
            }
            $.ajax({
                type : 'post',
                url : "{{route('update_seller_rating')}}",
                data : {
                    '_token':"{{csrf_token()}}",
                    'order_id' : order_id,
                    'seller_rating':seller_rating,
                    'complete_note':complete_note
                },
                success : function(data){
                    window.location.reload();
                }
            });
        });

        //Complete order process
        $('.btn_complete_order').on('click', function(){
            var url = $(this).data('url');
            var is_recurring = $(this).data('is_recurring');
            $('#frmCompleteOrder').attr('action',url);
            console.log(is_recurring);
            console.log(url);

            if(is_recurring == 1){
                $('#completeorderpopup .modal-title').text('Cancel subscription & Complete your order');
                $('#completeorderpopup .btn-complte-order').text('Cancel subscription & Complete order');
            }else{
                $('#completeorderpopup .modal-title').text('Complete your order');
                $('#completeorderpopup .btn-complte-order').text('Complete order');
            }
            $('#completeorderpopup').modal('show');
        });

        $('#shareServiceModal').modal('show');
        
        var currentRating = $('#seller_rating_new').data('current-rating');
        $('#seller_rating_new').barrating({
            theme: 'css-stars',
            showSelectedRating: false,
            initialRating: currentRating,
            onSelect: function(value, text) {

            },
            onClear: function(value, text) {

            }
        });
    });

    var maxLength = 2500;
    $('textarea').keyup(function() {
        var length = $(this).val().length;
        $('#chars').text(length);
    });

    $("#review_modal").on('hidden.bs.modal', function () {
        $("#order_secret_id").val('');
        $('#seller_rating').barrating('clear');
        $("#complete_note").val('');
        $('#chars').text(0);
    });

    $(document).on('click','.skip-order-rating',function(){
        var url = $(this).data('url');
        bootbox.confirm("Are you sure you want to skip review for this order?", function(result){ 
			if (result) {
                $.ajax({
                    type : 'post',
                    url : url,
                    data : {
                        '_token':"{{csrf_token()}}",
                    },
                    success : function(data){
                        if(data.status == 200){
                            window.location.reload();
                        }else{
                            alert_error('Something went wrong.');
                        }
                    },
                    error : function(e){
                        alert_error('Something went wrong.');
                    }
                });
			}	 
		});
    });
</script>
@endsection