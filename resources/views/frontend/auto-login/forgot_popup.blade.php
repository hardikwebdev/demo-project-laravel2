<form id="cus-forgot-form" name="forgot-form" action="{{url('password/email')}}" method="post">
    <input  name="_token" class="form-control"  type="hidden" value="{{ csrf_token() }}">
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
            </div>
        </div>
    </div>

    <div class="row">
		<div class="col-12">
			<h5 class="mb-3 mt-3">Forgot Password</h5>
            <div class="subheading">Please enter your email address. You will receive a link to create a new password via email.
            </div>
		</div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Email*</label>
                <input name="email" placeholder="Email" class="form-control" type="text">
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group register-btn text-center"> 
                <button type="submit" class="btn cus-w-80">Reset Password</button>
            </div>
        </div>
    </div>
</form>

<script>
    $('#cus-forgot-form').bootstrapValidator({
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
    	}
    }).on('success.form.bv', function (e) {
        // Prevent form submission
        e.preventDefault();
        // Get the form instance
        var $form = $(e.target);
        // Get the BootstrapValidator instance
        var bv = $form.data('bootstrapValidator');
        // Use Ajax to submit form data

        $.ajax({
            type: "POST",
            url: $form.attr('action'),
            data: $form.serialize(),
            success: function (result)
            {
                if (result.success == true) {
                    $('.cus-login-model').html();
                    $('.cus-login-model').html(result.data);
                }
            }
        });
    });
</script>