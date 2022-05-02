<form id="cartlogin-form" name="login-form" action="{{ route('speedLogin') }}" method="post">

    <input type="hidden" id="is_check_captch" value="0">

    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-danger" style="display:none;" id="forgot_error_msg_div">
                <span id="forgot_error_msg"></span>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class=""> 
                <div class="avtar mb-3">
                    <div class="avtar-img avtar-40">
                        <figure class="user-avatar cus-med">
                            <img src="{{get_user_profile_image_url($user)}}" alt="profile-image">
                        </figure>
                    </div>
                    <div class="avtar-detail">
                        <div class="custom-text-header">
                        
                            <p>{{$user->Name}}</p>
                            <a href="javascript:;" class="cus-back-login" >Not Your email? go back </a>
                        </div>
                    </div>
                </div>
                <div class="content">
                    <div class="row">
                        <div class="col-12 col-md-9">
                            <h5 class="">Welcome back, {{$user->Name}}!</h5>
                            <p class="mb-1">Great to see you again. Enter Your Password to continue. 
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <input type="hidden" name="service_id" value="{{ isset($service_id) ? $service_id : '0' }}">
                <input type="hidden" name="sendmsg" value="{{ isset($sendmsg) ? $sendmsg : '0' }}">
                <input type="hidden" name="customOrder" value="{{ isset($customOrder) ? $customOrder : '0' }}">

                <input type="hidden" name="combo_plan_id" value="{{ isset($combo_plan_id) ? $combo_plan_id : '0' }}">
                <input type="hidden" name="bundle_id" value="{{ isset($bundle_id) ? $bundle_id : '0' }}">
                <input type="hidden" name="packageType" value="{{ isset($packageType) ? $packageType : '0' }}">
                <input type="hidden" name="job_url" value="{{ isset($job_url) ? $job_url : '0' }}">
                <input type="hidden" name="jobAdd" value="{{ isset($jobAdd) ? $jobAdd : '0' }}">
                
                <input  name="_token" class="form-control"  type="hidden" value="{{ csrf_token() }}">
                <input  name="email" placeholder="Email" class="form-control" value="{{$username}}"  type="hidden">
                @php
                $currentPage = \Request::route()->getName();
                @endphp
                <input type="hidden" id="profileurl" name="profileurl" value="{{\URL::previous()}}">
                <input type="hidden" id="reactivation_url" value="{{route('reactivation')}}">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <input  name="email" placeholder="Email" class="form-control" value="{{$user->email}}"  type="hidden">
                <label>Password</label>
                <input  name="password" placeholder="Password" class="form-control"  type="password">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group  add-extra-detail">
                <label class="cus-checkmark">    
                    <input id="rememberme" name="remember" type="checkbox" value="true">
                    <span class="checkmark"></span>
                </label>
                <div class="detail-box">
                    <label>Stay Signed In</label>
                </div>
                
                <div class="forget-password">
                    <p>Forgot your password? 
                        <a href="javascript:;" class="forgot_pwd_btn" data-email="{{$user->email}}">Click Here!</a>
                    </p>  
                </div>
               
            </div>
        </div>    
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group login-btn text-center" > 
                <button type="submit" class="btn cus-w-80">Login</button>
            </div>
        </div>   
    </div>

</form>

<script>
    $('#cartlogin-form').bootstrapValidator({
    	fields: {
    		email: {
    			validators: {
    				notEmpty: {
    					message: 'Email/Username is required.'
    				},
    				regexp: {
    					regexp: '^[^@\\s]+@([^@\\s]+\\.)+[^@\\s]+$',
    					message: 'The value is not a valid email address'
    				}
    			}
    		},
    		password: {
    			validators: {
    				notEmpty: {
    					message: 'Password is required.'
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

        if($('#is_check_captch').val() == 0){

        	$.ajax({
        		type: "POST",
        		url: $form.attr('action'),
        		data: $form.serialize(),
        		success: function (result)
        		{
        			if (result.success == true) {
                        if(quick_checkout_id != ""){
                            if($('#'+quick_checkout_id).length ){
                                $('input[name="_token"]').val(result.token);
                                $('#'+quick_checkout_id).trigger('click');
                                return;
                            }
                        }
                        window.location.href = result.redirect_url;
        			} else {
        				if(result.is_active == true){
        					$('#forgot_error_msg_div').show();
        					$('#forgot_error_msg').html(result.message);
        					$('#forgot_error_msg_div').delay(3000).slideUp(300);
        				}else{
        					window.location.href = $("#reactivation_url").val();
        				}
        			}
        		}
        	});

            
        }else{
        	if($("#login-form .g-recaptcha-response").val().length > 0){

        		$.ajax({
        			type: "POST",
        			url: $form.attr('action'),
        			data: $form.serialize(),
        			success: function (result)
        			{
        				if (result.success == true) {
        					// window.location.href = $("#profileurl").val();
        				} else {
        					if(result.is_active == true){
        						$('#forgot_error_msg_div').show();
        						$('#forgot_error_msg').html(result.message);
        						$('#forgot_error_msg_div').delay(3000).slideUp(300);
        					}else{
        						// window.location.href = $("#reactivation_url").val();
        					}
        				}
        			}
        		});

            } else {
            	$('#forgot_error_msg_div').show();
            	$('#forgot_error_msg').html("Please validate captcha");
            	$('#forgot_error_msg_div').delay(3000).slideUp(300);

            	$('.login-btn').prop("disabled",false);
            }
        }

    });

    $('.forgot_pwd_btn').on('click', function() {
        var email = $(this).data('email');
        $.ajax({
            type: "POST",
            url: "{{route('forgot_password_popup')}}",
            data: { 'email' : email, "_token":"{{csrf_token()}}" },
            success: function (result)
            {
                if (result.success == true) {
                    $('.cus-login-model').html();
                    $('.cus-login-model').html(result.data);
                } else {
                    
                }
            }
        });
    });
</script>