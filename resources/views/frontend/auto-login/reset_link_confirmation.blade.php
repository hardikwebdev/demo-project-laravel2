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
                        {{-- <a href="javascript:;" class="cus-back-login" >Not Your email? go back </a> --}}
                    </div>
                </div>
            </div>
            <div class="content">
                <div class="row">
                    <div class="col-12 col-md-9">
                        <h5 class="">Confirmation!</h5>
                        <p class="mb-1">Please check your email for reset password link. 
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="forget-password">
            <p>To continue with login 
                <a href="javascript:;" class="continue_login_btn" data-email="{{$user->email}}">Click Here!</a>
            </p>  
        </div>
    </div>
</div>

<script>
    $('.continue_login_btn').on('click', function() {
        var email = $(this).data('email');
        $.ajax({
            type: "POST",
            url: "{{route('show_login_popup')}}",
            data: { 'email' : email, "_token":"{{csrf_token()}}" },
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