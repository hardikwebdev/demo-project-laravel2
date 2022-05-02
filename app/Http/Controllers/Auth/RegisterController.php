<?php

namespace App\Http\Controllers\Auth;

use App\User;
use Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\UserBlockList;
use Twilio;
use App\RestrictEmailDomain;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use App\WelcomeEmail;
use Carbon\Carbon;
use App\UserTwoFactorAuthDetails;
use App\Models\SmsHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class RegisterController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | Register Controller
      |--------------------------------------------------------------------------
      |
      | This controller handles the registration of new users as well as their
      | validation and creation. By default this controller uses a trait to
      | provide this functionality without requiring any additional code.
      |
     */

      use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
    	$this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data) {
    	return Validator::make($data, [
    		'name' => 'required|string|max:255',
    		'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
    	]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data) {
    	return User::create([
    		'name' => $data['name'],
    		'email' => $data['email'],
    		'password' => bcrypt($data['password']),
    	]);
    }

    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            //'captcha' => 'required|captcha',
            'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9 ]+$/',
            'email' => 'required|email',
            'g-recaptcha-response' => 'required|recaptcha'
        ],[
            'g-recaptcha-response.recaptcha' => 'Captcha verification failed',
            'g-recaptcha-response.required' => 'Please complete the captcha'
        ]);
        if ($validator->fails()) {
            $result['message'] = $validator->errors()->first();
            //$result['message'] = "Captcha verification failed";
            $result['status'] = 0;
            $result['captcha'] = false;
        }else{
            $request->merge([
                "ip_address" => get_client_ip() 
            ]);
            $returnResponse = function ( $status = false, $message = null) {
                return response([
                    // 'action_type' => $action_type,
                    'status' => $status,
                    'message' => ($message)? $message : "Something went wrong."
                ]);
            };
            $towfactorauth = 0;
            $name = $request['name'];
                $email = $request['email'];
                $username = $request['username'];
                $password = $request['password'];
                $interested_in = $request['interested_in'];
                $ip_adress = $_SERVER['REMOTE_ADDR'];
                $code = uniqid();

                $resultCampaign = updateActiveCampaign($name, $email);
                $resultCampaign = 1;
       
                if ($resultCampaign) {
                    try{
                        app('App\Http\Controllers\FrontEmailController')->verificationemail($name, $email, $code);
                    }catch(\Exception $e){

                    }

                    $new_user = new User;
                    $new_user->name = $name;
                    $new_user->email = $email;
                    $new_user->password = Hash::make($password);
                    $new_user->username = $username;
                    $new_user->confirmation_code = $code;
                    $new_user->ip_adress = $ip_adress;
                    $new_user->towfactorauth = $towfactorauth;
                    $new_user->affiliate_id = Str::random(16);
                    $new_user->is_verify_towfactorauth = 0;
                    // $new_user->mobile_no = $request->mobile_no;
                    // $new_user->country_code = $request->country_code;
                    $new_user->interested_in = $interested_in;
                    $new_user->terms_privacy = date('Y-m-d H:i:s');
                    $new_user->save();

                    Auth::login($new_user->fresh());

                    Session::flash("path", "register");

                    /* send welcome email - start */
                    $data = [
                        'subject' => 'Welcome to the demo family!',
                        'template' => 'frontend.emails.v1.welcome_user_first_email',
                        'email_to' => $email,
                        'firstname' => $name,
                    ];
                    QueueEmails::dispatch($data, new SendEmailInQueue($data));

                    //$new_user = User::where('email',$email)->first();
                    $welcome = new WelcomeEmail;
                    $welcome->user_id = $new_user->id;
                    $welcome->email_index = 1;
                    $welcome->email_at = Carbon::parse($new_user->created_at)->addDays(1)->format('Y-m-d H:i:s');
                    $welcome->save();
                    /* send welcome email - end */
                    Session::flash("tostSuccess","Thanks for keeping it demo.  We'll send you a confirmation email shortly.");
                    return $returnResponse( true, "Thanks for keeping it demo.  We'll send you a confirmation email shortly.");
                } else {
                    return $returnResponse();
                }
        }
        return response()->json($result);
    }

    public function verify_mobile(){
        if(Session::get('register_data') && Session::get('register_data') != ''){
            $user = Session::get('register_data');
            return view('auth.verify_register_mobile')->with('user',$user);
        }else{
            return redirect('/');
        }
    }

    /* already email exist */

    public function AlreadyEmail(Request $request) {
        /*begin : check for block emails*/
        $disposable_list = block_email_list();
        $domain = substr(strrchr($request->email, "@"), 1);
        if(in_array($domain, $disposable_list)){ 

            echo json_encode(
                [
                    'valid' => false,
                    "message" =>'Invalid domain.'
                ]
            );
            exit();
        }
        /*end : check for block emails*/

        $useremail = User::where('email', '=', $request->email)->first();

        if ($useremail === null) {
            $isValid = true;
            $isDelete = false;
        } else {
            if ($useremail->is_delete == "1") {
                $isDelete = true;
                $isValid = false;
            } else {
                $isValid = false;
                $isDelete = false;
            }
        }
        echo json_encode(array(
            'valid' => $isValid,
            "message" => ($isDelete == true) ? "You account has been deactivated by demo. Please contact support for further help." : "This email is already exists."
        ));
    }

    /* already username exist */

    public function AlreadyUser(Request $request) {
    	/*Check user In Block List*/
    	$usernameblocked = UserBlockList::select('username')->get();
    	if(!empty($usernameblocked)){
    		foreach ($usernameblocked as $key => $value) {
    			if (strpos(strtolower($request->username), strtolower($value->username)) !== false){
    				$isValid = false;
    				$message = 'This Username is already exists.';
    				return response()->json(['valid' => $isValid, 'message' => $message]);
    			}
    		}
    	}

    	$username = User::where('username', '=', $request->username)->first();
    	if ($username === null) {
    		$isValid = true;
    		$message = '';
    	} else {
    		$isValid = false;
    		$message = 'This Username is already exists.';
    	}
    	return response()->json(['valid' => $isValid, 'message' => $message]);
    }

    public function restrict_email_domain(Request $request) {
        $domains = RestrictEmailDomain::select('domain_name')->get()->toArray();
        return response()->json(['domains' => $domains]);
    }

    public  function registerCart(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'g-recaptcha-response' => 'required|recaptcha'
        ],[
            'g-recaptcha-response.recaptcha' => 'Captcha verification failed',
            'g-recaptcha-response.required' => 'Please complete the captcha'
        ]);
        if ($validator->fails()) {
            $result['message'] = $validator->errors()->first();
            //$result['message'] = "Captcha verification failed";
            $result['status'] = 0;
            $result['captcha'] = false;
            return response($result);
        }
        $request->merge([
            "ip_address" => get_client_ip() 
        ]);
        $returnResponse = function( $status = false, $message = null) {
        	return response([
                'status' => $status,
                'message' => ($message)? $message : "Something went wrong.",
            ]);
        };
        $towfactorauth = 0;
        $name = $request['name'];
        $email = $request['email'];
        $username = $request['username'];
        $password = $request['password'];
        $ip_adress = $_SERVER['REMOTE_ADDR'];
        $code = uniqid();

        $resultCampaign = updateActiveCampaign($name, $email);
        $resultCampaign = 1;

        if ($resultCampaign) {
            try{
                app('App\Http\Controllers\FrontEmailController')->verificationemail($name, $email, $code);
            }catch(\Exception $e){

            }

            $new_user = new User;
            $new_user->name = $name;
            $new_user->email = $email;
            $new_user->password = Hash::make($password);
            $new_user->username = $username;
            $new_user->confirmation_code = $code;
            $new_user->ip_adress = $ip_adress;
            $new_user->towfactorauth = $towfactorauth;
            $new_user->affiliate_id = Str::random(16);
            $new_user->is_verify_towfactorauth = 0;
            $new_user->terms_privacy = date('Y-m-d H:i:s');
            $new_user->save();

            $credentials = array('email'=>$email,'password'=>$password);
            $authSuccess = \Auth::attempt($credentials, false);
            if($authSuccess) {
                $request->session()->regenerate();
                $_SESSION["username"] = md5($request['username']);
                $updateuser = User::where('id',\Auth::user()->id)->first();
                $updateuser->last_login_at = date('Y-m-d H:i:s');
                $updateuser->ip_adress = $_SERVER['REMOTE_ADDR'];
                $updateuser->login_attempt = 0;
                $updateuser->login_attempt_date =  Carbon::now();
                $updateuser->save();
                Session::put('service_id',$request['service_id']);
                Session::put('sendmsg',$request['sendmsg']);
                Session::put('customOrder',$request['customOrder']);

                Session::put('combo_plan_id',$request['combo_plan_id']);
                Session::put('bundle_id',$request['bundle_id']);
                Session::put('packageType',$request['packageType']);
                Session::put('job_url',$request['job_url']);
            }

            Session::flash("path", "register");

            /* send welcome email - start */
            $data = [
                'subject' => 'Welcome to the demo family!',
                'template' => 'frontend.emails.v1.welcome_user_first_email',
                'email_to' => $email,
                'firstname' => $name,
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));

            $new_user = User::where('email',$email)->first();
            $welcome = new WelcomeEmail;
            $welcome->user_id = $new_user->id;
            $welcome->email_index = 1;
            $welcome->email_at = Carbon::parse($new_user->created_at)->addDays(1)->format('Y-m-d H:i:s');
            $welcome->save();
            /* send welcome email - end */

            /* clear all attempts */

            return $returnResponse(true, "Thanks for keeping it demo.  We'll send you a confirmation email shortly.");
        } else {
            return $returnResponse();
        }
    }

    public function get_verify_number_content(Request $request) {
        $phase = 'add_number';
        $dataHtml = view('frontend.auto-login.verify_mobile_popup',compact('phase'))->render();
        return response([
            'status' => true,
            'message' => 'Verify your phone number',
            'data' => $dataHtml
        ]); 
    }

    /* Verify admin created new user */
    public function verify_new_user(Request $request, $confirmation_code){
        
        $user = User::select('id','mobile_no','country_code','status')->whereConfirmationCode($confirmation_code)->first();
        if($user){
            Session::put('user_data',$user);
            return redirect()->route('user.change_new_password');
        }

        Session::flash('tostError','Something went wrong!');
        return redirect()->route('home');
    }

    /* Change new password */
    public function change_new_password(Request $request){
        
        if($request->method() == 'POST'){
            $sess_user = Session::get('user_data');
            $user = User::select('id','name','email','password','confirmation_code','affiliate_id','ip_adress','created_at','is_verify_towfactorauth','status')->where('is_delete',0)->where('id',$sess_user->id)->first();
            if($user->email != $request->email){
                Session::flash('tostError','Invalid email address.');
                return redirect()->back();
            }
            /* Check backend validation */
            $request->validate([
                'password' => 'required|confirmed',
            ]);

            $password = $request->password;
            $ip_adress = $_SERVER['REMOTE_ADDR'];
            $code = uniqid();
            $name = $user->name;
            $email = $user->email;

            $resultCampaign = updateActiveCampaign($name, $email);
            $resultCampaign = 1;
            
            if ($resultCampaign) {

                $user->is_verify = '1';
                $user->confirmation_code = null;
                $user->password = \Hash::make($password);
                $user->confirmation_code = $code;
                $user->ip_adress = $ip_adress;
                $user->affiliate_id = \Str::random(16);
                $user->is_verify_towfactorauth = 1;
                $user->save();

                /* send welcome email - start */
                $data = [
                    'subject' => 'Welcome to the demo family!',
                    'template' => 'frontend.emails.v1.welcome_user_first_email',
                    'email_to' => $email,
                    'firstname' => $name,
                ];
                QueueEmails::dispatch($data, new SendEmailInQueue($data));
                
                //$new_user = User::where('email',$email)->first();
                $welcome = new WelcomeEmail;
                $welcome->user_id = $user->id;
                $welcome->email_index = 1;
                $welcome->email_at = Carbon::parse($user->created_at)->addDays(1)->format('Y-m-d H:i:s');
                $welcome->save();
                /* send welcome email - end */

                /* clear all attempts */
                clear_two_factor_auth_attempt_details(0);

                Session::flash("tostSuccess", "Thanks for keeping it demo.  We\'ll send you a confirmation email shortly.");
                return redirect('login');
            } else {
                Session::flash('tostError','Something went wrong.');
                return redirect()->route('home');
            }
        }else{
            $sess_user = Session::get('user_data');
            if($sess_user){
                return view('auth.passwords.change_new_password');
            }
            Session::flash('tostError','Something went wrong.');
            return redirect()->route('home');
        }
    }
}
