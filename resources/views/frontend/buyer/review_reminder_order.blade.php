@php
use App\Commands\SortableTrait;
@endphp
@extends('layouts.frontend.main')
@section('pageTitle', 'demo - Orders')
@section('content')
<!-- Masthead -->
<header class="masthead text-white">
	<div class="overlay"></div>
    <div class="bg-dark w-100">
		<div class="container py-4">
			<h1 class="font-24 font-weight-bold font-lato text-white mb-0 py-3">Share Review</h1>
		</div>
    </div>
</header>

<section class="transactions-table pad-t4">
    <div class="container">
        <div class="alert alert-success" role="alert">
            <div class="row">
                <div class="col-md-1 text-center top_space">
                    <i class="fa fa-check fa-3x"></i>
                </div>
                <div class="col-md-11">
                    <h5><b>Share Review</b></h5>
                    <h6>Thank you so much for sharing your experience with us. Would you want to share your experience about these other orders and leave them a review?</h6>
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
                            <label class="custom-title-complete-order">Give ratings to seller/author</label>
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
<div id="serviceModal"></div>
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
                    if (data.service_can_share) 
                    {
                        $('#review_modal').modal('hide');
                        $('#serviceModal').append(data['serviceModal']);
                        $('#shareServiceModal').modal('show');
                    }
                    else{
                        window.location.reload();
                    }
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