<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
//use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Contracts\Auth\PasswordBroker;
use App\User;
use Carbon\Carbon;

class FrontUserForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

   // use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }



    public function sendResetLinkEmailnew(Request $request)
    {
        $data = [
            $request->email
        ];
        $user = User::where('email',  $request->email)->where('status',1)->where('is_active',1)->where('is_delete',0)->first();
        if ($user) {
           //so we can have dependency 
           $password_broker = app(PasswordBroker::class);
           //create reset password token 
           $token = $password_broker->createToken($user); 

           \DB::table('password_resets')->insert(['email' => $user->email, 'token' => $token, 'created_at' => new Carbon]); 

            $send = \Mail::send('frontend.emails.forgotpassword', [
                'reseturl'     => route('password.resetFront', $token),
                'name' => $user->Name,
            ], function($message) use($data){
                $message->subject('demo - Change Password');
                $message->to($data[0]);
            });
            $pre_url = explode('/',url()->previous());
            if($request->ajax() && $pre_url[count($pre_url)-1] == 'cart') {
                $dataHtml = view('frontend.auto-login.reset_link_confirmation',compact('user'))->render();
                return response([
                    'success' => true,
                    'data' => $dataHtml
                ]); 
            }
            return response(['success' => true,'message' => 'If the supplied email ID exists, an e-mail has been sent to the e-mail address associated.']);
            
       }
       /* return response(['success' => false,'message' => "We cannot find user with below email address."]); */
       return response(['success' => true,'message' => "If the supplied email ID exists, an e-mail has been sent to the e-mail address associated."]);
      

        // $response = $this->broker()->sendResetLink(
        //     $request->only('email')
        // );
		// if($response == Password::RESET_LINK_SENT)
		// {
		// 	return response(['success' => true,'message' => 'A password link has been sent to your e-mail address.']);
		// }
		// else if($response == Password::INVALID_USER)
		// {
		// 	return response(['success' => false,'message' => "We cannot find user with below email address."]);
		// }
    }
	
	public function broker()
    {
       return Password::broker('users');
    }
	
}
