<form id="cus-createaccount" name="createaccount" action="{{route('register_cart')}}" method="post">
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
                    
                        <p>{{$username}}</p>
                        <a href="javascript:;" class="cus-back-login" >Use different email address. </a>
                    </div>
                </div>
            </div>
            <div class="content">
                <div class="row">
                    <div class="col-12 col-md-9">
                        <h5 class="">Create your account</h5>
                        <p class="mb-1">Register is easy.</p> 
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

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label>Full Name*</label>
                <input name="name" placeholder="Full Name" class="form-control" type="text" >
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <input name="email" placeholder="Email" class="form-control" value="{{$username}}"  type="hidden">
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Username*</label>
                <input name="username" placeholder="Username" class="form-control" type="text" data-bv-field="username">
                <input  name="_token" class="form-control"  type="hidden" value="{{ csrf_token() }}" id="_token">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label>Password*</label>
                <input name="password" placeholder="Password" class="form-control" type="password">
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Confirm Password*</label>
                <input name="confirm_password" placeholder="Confirm Password" class="form-control" type="password" data-bv-field="confirm_password">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @if(config('services.recaptcha.sitekey'))
                <div class="g-recaptcha"
                    data-sitekey="{{config('services.recaptcha.sitekey')}}">
                </div>
            @endif
        </div>
    </div>

    <div class="row align-items-center justify-content-between mt-2">
        <div class="col-md-12">
            <div class="register-btn text-center"> 
                <button type="submit" class="btn register-btn cus-w-80" id="register_btn_id">Register</button>
            </div>
        </div>   
    </div>
</form>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
    $('#cus-createaccount').bootstrapValidator({
        fields: {
            name: {
                validators: {
                    notEmpty: {
                        message: 'Full name is required.'
                    },
                    regexp: {
                        regexp: '^[a-zA-Z ]*$',
                        message: 'Please input a valid name.'
                    }
                }
            },
            password: {
                validators: {
                    notEmpty: {
                        message: 'Password is required.'
                    },
                    stringLength: {
                        min: 8,
                        message: 'Password  must be 8 digits only.'
                    },
                    regexp: {
                        regexp: /^(?=(.*[a-z]){1,})(?=(.*[\d]){1,})(?=(.*[\W]){1,})(?!.*\s).{8,}$/,
                        message: 'The password should contain Minimum 8 characters at least 1 Uppercase Alphabet, 1 Lowercase Alphabet, 1 Special Character.'
                    }
                }
            },
            confirm_password: {
                validators: {
                    notEmpty: {
                        message: 'Confirm password is required.'
                    },
                    identical: {
                        field: 'password',
                        message: 'The password and its confirm are not the same'
                    }
                }
            },
            username: {
                validators: {
                    remote: {
                        data: {"_token": _token},
                        url: username_already,
                        type: 'POST'/*,
                        message: 'This Username is already exists.'*/
                    },
                    notEmpty: {
                        message: 'Username is required.'
                    },
                    regexp: {
                        regexp: /^[a-zA-Z0-9._]+$/,
                        message: 'The username can only consist of alphabetical, number, dot and underscore'
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
        $.post($form.attr('action'), $form.serialize(), function (result) {
            if(result.captcha == false){
                $('#response_message').html('<label class="error">' + result.message + '</label>');
                setTimeout(function () {
                    $('#response_message').fadeOut();
                }, 2000);
                $('#register_btn_id').prop("disabled", false);
            } else if (result.status) {
                $('.cus-login-model').html();
                $('.cus-login-model').html(result.data);
                $('#cus-createaccount').bootstrapValidator('resetForm', true);
                $.magnificPopup.close();
                alert_success(result.message);
                setTimeout(function () {
                    window.location.reload();
                }, 1500);
            } else {
                $('#response_message').html('<label class="error">' + result.message + '</label>');
                setTimeout(function () {
                    $('#response_message').fadeOut();

                }, 500);
                /* $('#response').html('<label class="error">' + result.message + '</label>');
                setTimeout(function () {
                    $('#response').fadeOut();

                }, 500); */
            }
        }, 'json');
    });
</script>