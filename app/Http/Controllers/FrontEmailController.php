<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Redirect;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\UnsubscribeEmail;

class FrontEmailController extends Controller
{
    /*verfication email */
    public function verificationemail($name, $email, $code)
    {
        $data = [
            'subject' => 'Registration',
            'template' => 'frontend.emails.registration',
            'email_to' => $email,
            'name' => $name,
            'confirmcode' => $code,
        ];
        QueueEmails::dispatch($data, new SendEmailInQueue($data));
    }

    /*function for verify account */
    public function VerifyAccount($confirmation_code)
    {
        if (!$confirmation_code) {
            \Session::flash('errorLogin', 'Confirmation code required.');
            return Redirect::route('accountsetting');
        }

        $user = User::whereConfirmationCode($confirmation_code)->first();

        if (!$user) {
            \Session::flash('errorLogin', 'You already confirm your account.');
            return Redirect::route('accountsetting');
        }

        $user->is_verify = '1';
        $user->confirmation_code = null;
        $user->save();


        \Session::flash('status', 'You have successfully verified your account.');
        return Redirect::route('accountsetting');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function unsubscribeEmail(Request $request,$token){
        try {
            $secret = decrypt($token);
            if($secret == Auth::user()->secret){
                if($request->method() == 'POST'){
                    $email_types = [];
                    if($request->has('email')){
                        foreach ($request->email as $key => $email_type) {
                            $email_types[] = $email_type;  
                            UnsubscribeEmail::updateStatus(Auth::user()->id,$email_type,0);
                        }
                    }
                    UnsubscribeEmail::where('user_id',Auth::user()->id)->whereNotIn('email_type',$email_types)->update(['status' => 1]);
                    Session::flash('tostSuccess','Settings updated successfully.');
                    return redirect()->back();
                }else{
                    $unsubscribe_email_ids = UnsubscribeEmail::select('email_type')->where('user_id',Auth::user()->id)->where('status',1)->pluck('email_type')->toArray();
                    return view('frontend.emails.unsubscribe_email',compact('token','unsubscribe_email_ids'));
                }
            }
        } catch (DecryptException $e) {
            Session::flash('tostError','Something went wrong.');
        }
        return redirect('/');
    }
}
