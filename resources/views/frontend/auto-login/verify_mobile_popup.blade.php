<form id="cus-verify-mobile-form" name="verify-mobile-form" action="{{route('verify_otp_from_cart')}}" method="post">
    <input  name="_token" class="form-control"  type="hidden" value="{{ csrf_token() }}">
    <div class="row mt-2 mb-2">
        <div class="col-12"> 
            <div class="avtar mb-3">
                <div class="avtar-img avtar-40">
                    <figure class="user-avatar cus-med">
                        <img src="{{front_asset('images/profile-default-image.jpg')}}" alt="profile-image">
                    </figure>
                </div>
                <div class="avtar-detail">
                    <div class="custom-text-header">
                        <p>{{\Session::get('register_data_from_cart')['email']}}</p>
                    </div>
                </div>
            </div>
            <div class="content">
                <div class="row">
                    <div class="col-12">
                        <h5 class="">Verify your phone number</h5>
                        <p class="mb-1">To make sure this number is yours, demo will send you a text message with a 4-digit verification code.</p> 
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div id="response_message"></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-danger" style="display:none;" id="forgot_error_msg_div">
                <span id="forgot_error_msg"></span>
            </div>
        </div>
    </div>
    @if($phase == "add_number")
    <input type="hidden" name="action_type" id="action_type_modal_btn" value="1"/>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <div class="input-group">
                    {{Form::select('country_code',country_code_list(),1,["id"=>"country_code","class"=>"form-control"])}}
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <input type="text" class="form-control mobile-height" name="mobile_no" placeholder="Enter mobile number" value="" maxlength="12" >
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            @if(config('services.recaptcha.sitekey'))
                <div class="g-recaptcha" data-sitekey="{{config('services.recaptcha.sitekey')}}"></div>
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group register-btn text-center"> 
                <button type="submit" class="btn register-btn cus-w-80" id="send_otp_modal_btn">Next</button>
            </div>
        </div>
    </div>
    @endif

    @if($phase == 'enter_otp')
    <input type="hidden" name="action_type" id="action_type_modal_btn" value="3"/>
    <div class="row">
        <div class="col-md-12 text-m-number">
            <strong>Mo : <span class="text-mobile">{{\Session::get('register_data_from_cart')['mobile_no']}}</span></strong>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <input type="text" class="form-control" id="otp_modal_btn" name="otp" placeholder="Enter OTP">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <button type="button" id="verify_go_back_modal_btn" class="btn btn-link">Back</button>
        </div>
        <div class="col-md-9 text-right">
            <button type="submit" id="request_call_modal_btn" class="btn btn-link">Call instead</button>
            <button class="btn btn-primary" id="verify_otp_modal_btn" type="submit">Verify</button>
        </div>
    </div>
    @endif
</form>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
$(document).ready(function () {
    $('#send_otp').removeAttr('disabled');
    $("#country_code").select2();

    $('#verify_otp_modal_btn').on('click',function(){
        $('#action_type_modal_btn').val(3);
    });

    $('#request_call_modal_btn').on('click',function(){
        $('#action_type_modal_btn').val(4);
    });

    $('#verify_go_back_modal_btn').on('click',function(){
        
        $.ajax({
            type: "POST",
            url: "{{route('get_verify_number_content')}}",
            data: {'_token':"{{ csrf_token() }}"},
            success: function (result)
            {
                if(result.status) {
                    $('.cus-login-model').html();
                    $('.cus-login-model').html(result.data);
                }
            }
        });
    });
});

$('#cus-verify-mobile-form').bootstrapValidator({
    fields: {
        mobile_no: {
            validators: {
                notEmpty: {
                    message: 'Mobile number is required.'
                }, 
                regexp: {
                    regexp: '^[0-9]*$',
                    message: 'Mobile number must be only numbers.'
                }
            }
        }
    }
}).on('success.form.bv', function (e) {
    // Prevent form submission
    e.preventDefault();
    // Get the form instance
    var $form = $(e.target);
    // Get the BootstrapValidator instance
    var bv = $form.data('bootstrapValidator');
    // Use Ajax to submit form data
    $('#forgot_error_msg_div').hide();
    $('#forgot_success_msg_div').hide();
    var form_data = $form.serialize();
    if($('#action_type_modal_btn').val() != 1) {
        form_data += "&mobile_no={{\Session::get('register_data_from_cart')['mobile_no']}}";  
    }
    $.ajax({
        type: "POST",
        url: $form.attr('action'),
        data: form_data,
        success: function (result)
        {
            /* Reset recaptcha */
            if($('.g-recaptcha').length > 0){
                grecaptcha.reset();
            }

            $('#verify_otp_modal_btn').attr('disabled',false);
            $('#request_call_modal_btn').attr('disabled',false);
            $('#send_otp_modal_btn').attr('disabled',false);

            if (result.status == true && (result.action_type == 1 || result.action_type == 2)) {
                $('.cus-login-model').html();
                $('.cus-login-model').html(result.data);
            } else if (result.status == true && result.action_type == 3) {
                $('#cus-verify-mobile-form').bootstrapValidator('resetForm', true);
                $.magnificPopup.close();
                alert_success(result.message);
                if(quick_checkout_id != ""){
                    if($('#'+quick_checkout_id).length > 0){
                        $('input[name="_token"]').val(result.token);
                        $('#'+quick_checkout_id).trigger('click');
                        return;
                    }
                }
                setTimeout(function () {
                    window.location.reload();
                }, 1500);
            } else if (result.status == true && result.action_type == 4) {
                
            } else {
                $('#response_message').css('display','block');
                $('#response_message').html('<label class="error">' + result.message + '</label>');
                setTimeout(function () {
                    $('#response_message').fadeOut(3000);
                }, 500);
            }
        },
        error: function (){
            $('#response_message').css('display','block');
            $('#response_message').html('<label class="error">Something went wrong.</label>');
            setTimeout(function () {
                $('#response_message').fadeOut(2000);
            }, 1000);
            $('#verify_otp_modal_btn').attr('disabled',false);
            $('#request_call_modal_btn').attr('disabled',false);
            $('#send_otp_modal_btn').attr('disabled',false);
        }
    });
});
</script>