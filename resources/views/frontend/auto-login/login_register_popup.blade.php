{{-- <div class="continue-guest text-center">
	<a href="javacsript:;" class="btn btn-round btn-primary btn-outline w-80 btn-outline-primary"  data-dismiss="modal">Continue as guest</a>
</div> --}}
<form id="cus-login-form" name="login-form" action="{{route('login_check')}}" method="post">
    <input  name="_token" class="form-control"  type="hidden" value="{{ csrf_token() }}">
    <div class="row">
		<div class="col-12">
			<h5 class="mb-3 mt-3">Sign in or Register</h5>
		</div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Email*</label>
                <input name="email" placeholder="Email" class="form-control" type="text">
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group register-btn text-center"> 
                <button type="submit" class="btn register-btn cus-w-80">Continue</button>
            </div>
        </div>
    </div>
</form>