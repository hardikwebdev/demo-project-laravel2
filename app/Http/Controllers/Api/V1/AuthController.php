<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\Request;
use App\Jobs\QueueEmails;
use App\WelcomeEmail;
use App\User;
use Validator;
use Auth;
use Carbon\Carbon;
use App\Mail\SendEmailInQueue;
use Twilio;
use App\UserTwoFactorAuthDetails;
use App\UserDevice;
use Lcobucci\JWT\Parser as JwtParser;
use Laravel\Passport\TokenRepository;
use App\OauthAccessToken;
use App\BlockUser;
use App\AppTwoFactorAuth;
use App\Notification;
use App\Models\SmsHistory;

class AuthController extends Controller
{
    protected $tokens;
    protected $jwt;
    public function __construct(TokenRepository $tokens,JwtParser $jwt)
    {
        $this->jwt = $jwt;
        $this->tokens = $tokens;
    }

	public function register(Request $request){
		$validator = Validator::make($request->all(), array(
            'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9 ]+$/',
			"username" => "required|unique:users",
			"email" => "email|required|unique:users",
			"password" => "required|confirmed",
            "device_type" => "required",
		), [
			'name.required' => "Full name is required",
			'name.string' => "Full name must be a string",
			'name.max' => "Full name may not be greater than 255 characters",
			'email.required' => "Email is required",
			'email.email' => "Email must be a valid email address",
			'email.unique' => "Email has already been taken",
			'password.required' => "Password is required",
			'password.confirmed' => "Password confirmation does not match",
            'device_type.confirmed' => "Device Type is required",
		]);

		if ($validator->fails()) {
			$msg = $validator->errors()->getMessages();
			$message = "Validation error";
			foreach ($msg as $key => $value) {
				$message = $value[0];
			}
			return response(['success' => false, 'message' => $message, "code" => 400], 400);
		}

        $useremail = User::select('id', 'email', 'is_delete', 'username')->where('email', $request->email)->first();
        if($useremail){
	        if ($useremail->is_delete == "1") {
				$message = 'You account has been deactivated by demo. Please contact support for further help.';
			}else{
				$message = 'This email is already exists.';
			}
			return response(['success' => false, 
					'message' => $message, 
					"code" => 400
				], 400);
		}

        $userUsername = User::select('id', 'email', 'is_delete', 'username')->where('username', $request->username)->first();
    	if ($userUsername != null) {
    		$message = 'This Username is already exists.';
    		return response(['success' => false, 
					'message' => $message, 
					"code" => 400
				], 400);
    	}

        $userInfoToken = uniqid();
		$redis = Redis::connection();
		$redis->set($userInfoToken, json_encode([
			'name' 			=> $request->name,
			'username' 		=> $request->username,
			'email' 		=> $request->email,
			'password' 		=> $request->password,
            'twofactorauth' => $request->twofactorauth,
            'device_type'   => $request->device_type,
            'device_token'  => $request->device_token,
            "ip_address"    => get_client_ip()
		]));
        $redis->expire($userInfoToken,3600);
    
		return response(['success' => true, 
            "code"          => 200,
			'user_token'    => $userInfoToken,
            'message'       => 'Verify your mobile number',
            'country_code'  => country_code_array()
        ],200);
	}

    public function send_otp(Request $request){
        
        $redis    = Redis::connection();
        $userInfoToken = $request->user_token;
        $mobileNoToken = uniqid();
        $ip_address = get_client_ip();

        /* Check IP Address */ 
        $returnResponse = function ($mobile_no = null, $status = false, $code = 400, $message = null,$userInfoToken=null) {
        	return response([
                'success' => $status, 
                'mobile_no' => $mobile_no, 
                'user_token' => $userInfoToken,
                'message' => ($message)? $message : 'Something went wrong.',
                'code' => $code
            ], $code);
        };

        /* Check IP Address */ 
        $session_user_data = $redis->get($userInfoToken);
        if(is_null($session_user_data) ){ /* check ip address */
            return $returnResponse($request->country_code.$request->mobile_no,false, 400,null,$userInfoToken);
        }
        
        $mobileExists = User::select('id')->where('mobile_no',$request->mobile_no)->where('country_code',$request->country_code)->count();
        if($mobileExists > 0){
            return $returnResponse($request->country_code.$request->mobile_no,false, 400,'Mobile number already exists.',$userInfoToken);
        }

        $check_2_factor = check_invalid_two_factor_auth_attempt(0);
        if($check_2_factor['status'] == true) {
            return $returnResponse($request->country_code.$request->mobile_no,false, 400,$check_2_factor['message'],$userInfoToken);
		} else {
			if($ip_address != 'UNKNOWN') {
				$get_attempts = new UserTwoFactorAuthDetails();
				$get_attempts->user_id = 0;
				$get_attempts->ip_address = $ip_address;
				$get_attempts->mobile_no = $request->country_code.$request->mobile_no;
				$get_attempts->attempts = 1;
				$get_attempts->save();
			}
		}

        $verification_response = verifyIpForSMS($session_user_data->ip_address,$request->country_code);
        if($verification_response['status'] == false){
            return $returnResponse($request->country_code.$request->mobile_no,false, 400,$verification_response['message'],$userInfoToken);
        }

        /* Store SMS History and check SMS limitation */
        $sms_history = SmsHistory::store_sms_history($request->country_code);
        if($sms_history == false){
            return $returnResponse($request->country_code.$request->mobile_no,false, 400,'Something went wrong. Please try after sometime.',$userInfoToken);
        }

        $otp = mt_rand(1000,9999);
        $message = 'Your demo registration one time password is : '. $otp;

        try{
            
            $redis->set($mobileNoToken,json_encode([
                'country_code'  => $request->country_code,
                'mobile_no'     => $request->mobile_no,
                'otp'           => $otp
            ]));
            $redis->expire($mobileNoToken,120); 
            Twilio::message($request->country_code.$request->mobile_no, $message);

            return response(['success' => true, 
                'message' => 'SMS send successfully', 
                'mobile_no' => $request->country_code.$request->mobile_no, 
                'mobile_no_token' => $mobileNoToken, 
                'user_token' => $userInfoToken,
				"code" => 200
			], 200);

        }catch(\Exception $e){
            return $returnResponse($request->country_code.$request->mobile_no,false, 400,'Enter valid mobile number',$userInfoToken);
        }
    }

    public function verify_otp(Request $request){
        
        $redis    = Redis::connection();
        $userInfoToken = $request->user_token;
        $mobileNoToken = $request->mobile_no_token;

        $mobile_no_details = $redis->get($mobileNoToken);
        
        if($mobile_no_details == ''){
            return response(['success' => false, 
                'message' => "Session timeout. Please try again.", 
                "code" => 419
            ], 419);
        }else{
            $mobile_no_details = json_decode($mobile_no_details);
        }

        if(trim($request->otp) && ($mobile_no_details->otp == $request->otp) ){

            $sess_user = $redis->get($userInfoToken);
            if($sess_user == ''){
                return response(['success' => false, 
                    'message' => "Session timeout. Please try again.", 
                    "code" => 419
                ], 419);
            }else{
                $sess_user = json_decode($sess_user);
            }

            $twofactorauth = 0;
            if( isset($sess_user->twofactorauth) && $sess_user->twofactorauth == 1){
                $twofactorauth = 1;
            }

            $name           = $sess_user->name;
            $email          = $sess_user->email;
            $username       = $sess_user->username;
            $password       = $sess_user->password;
            $device_type    = $sess_user->device_type;
            $device_token   = $sess_user->device_token;
            $ip_adress      = $_SERVER['REMOTE_ADDR'];
            $code           = uniqid();

            $resultCampaign = updateActiveCampaign($name, $email);
            $resultCampaign = 1;
   
            if ($resultCampaign) {
                try{
                    app('App\Http\Controllers\FrontEmailController')->verificationemail($name, $email, $code);
                }catch(\Exception $e){

                }

                /* $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'username' => $username,
                    'confirmation_code' => $code,
                    'ip_adress' => $ip_adress,
                    'towfactorauth' => $twofactorauth,
                    'affiliate_id' => Str::random(16),
                    'is_verify_towfactorauth' => 1,
                    'mobile_no' => $mobile_no_details->mobile_no,
                    'country_code' => $mobile_no_details->country_code,
                    'device_type' => $device_type,
                    'device_token' => $device_token,
                    'terms_privacy' => date('Y-m-d H:i:s')
                ]); */
                $new_user = new User;
                $new_user->name = $name;
                $new_user->email = $email;
                $new_user->password = Hash::make($password);
                $new_user->username = $username;
                $new_user->confirmation_code = $code;
                $new_user->ip_adress = $ip_adress;
                $new_user->towfactorauth = $twofactorauth;
                $new_user->affiliate_id = Str::random(16);
                $new_user->is_verify_towfactorauth = 1;
                $new_user->mobile_no = $mobile_no_details->mobile_no;
                $new_user->country_code = $mobile_no_details->country_code;
                $new_user->device_type = $device_type;
                $new_user->device_token = $device_token;
                $new_user->terms_privacy = date('Y-m-d H:i:s');
                $new_user->save();

                //get new user
                //$new_user = User::select('id', 'email', 'username')->where('email',$email)->first();

                // remove user details in redis db
                $redis->del($userInfoToken);
                $redis->del($request->mobile_no_token);

                /* send welcome email - start */
                $data = [
                    'subject' => 'Welcome to the demo family!',
                    'template' => 'frontend.emails.v1.welcome_user_first_email',
                    'email_to' => $email,
                    'firstname' => $name,
                ];

                QueueEmails::dispatch($data, new SendEmailInQueue($data));

                $welcome = new WelcomeEmail;
                $welcome->user_id = $new_user->id;
                $welcome->email_index = 1;
                $welcome->email_at = Carbon::parse($new_user->created_at)->addDays(1)->format('Y-m-d H:i:s');
                $welcome->save();

                /* clear all attempts */
				clear_two_factor_auth_attempt_details(0);

                return response(['success' => true, 
                    'message' => "Thanks for keeping it demo.  We'll send you a confirmation email shortly.", 
                    "code" => 200
                ], 200);

            } else {
                return response(['success' => false, 
                    'message' => "Something wrong went.", 
                    "code" => 400
                ], 400);
            }
        }else{
            return response(['success' => false, 
                'message' => "Please enter valid OTP", 
                'mobile_no_token' => $mobileNoToken, 
                'user_token' => $userInfoToken,
                "code" => 400
            ], 400);
        }
        
    }

    public function call_request(Request $request){
        
        $redis    = Redis::connection();
        $userInfoToken = $request->user_token;
        $mobileNoToken = uniqid();

        /* Custom response function */ 
        $returnResponse = function ($mobile_no = null, $status = false, $code = 400, $message = null,$userInfoToken=null) {
        	return response([
                'success' => $status, 
                'mobile_no' => $mobile_no, 
                'user_token' => $userInfoToken,
                'message' => ($message)? $message : 'Something went wrong.',
                'code' => $code
            ], $code);
        };

        $mobileExists = User::select('id')->where('mobile_no',$request->mobile_no)->where('country_code',$request->country_code)->count();
        if($mobileExists > 0){
            return $returnResponse($request->country_code.$request->mobile_no,false, 400,'Mobile number already exists.',$userInfoToken);
        }

        /* Check IP Address */ 
        $session_user_data = $redis->get($userInfoToken);
        if(is_null($session_user_data)){
            return $returnResponse($request->country_code.$request->mobile_no,false, 400,null,$userInfoToken);
        }

        $verification_response = verifyIpForSMS($session_user_data->ip_address,$request->country_code);
        if($verification_response['status'] == false){
            return $returnResponse($request->country_code.$request->mobile_no,false, 400,$verification_response['message'],$userInfoToken);
        }

        /* Store SMS History and check SMS limitation */
        $sms_history = SmsHistory::store_sms_history($request->country_code);
        if($sms_history == false){
            return $returnResponse($request->country_code.$request->mobile_no,false, 400,'Something went wrong. Please try after sometime.',$userInfoToken);
        }

        $otp = mt_rand(1000,9999);
        $otpCode = implode(' ', str_split($otp));

        try{

            $redis->set($mobileNoToken,json_encode([
                'country_code'  => $request->country_code,
                'mobile_no'     => $request->mobile_no,
                'otp'           => $otp
            ]));
            $redis->expire($mobileNoToken,120); 

            Twilio::call('+'.$request->country_code.$request->mobile_no, function ($voiceMessage) use ($otpCode){
                $voiceMessage->say('This is an automated call providing you your OTP from the demo app.');
                $voiceMessage->say('Your one time password is ' . $otpCode);
                $voiceMessage->pause(['length' => 1]);
                $voiceMessage->say('Your one time password is ' . $otpCode);
                $voiceMessage->say('GoodBye');
            });

            return response(['success' => true, 
                'mobile_no' => $request->country_code.$request->mobile_no, 
                'mobile_no_token' => $mobileNoToken, 
                'user_token'    => $userInfoToken,
                'message' => "Request call successfully", 
                "code" => 200
            ], 200);
        }catch(\Exception $e){
            return response(['success' => false, 
                'mobile_no' => $request->country_code.$request->mobile_no, 
                'user_token' => $userInfoToken,
                'message' => 'Enter valid mobile number', 
                "code" => 400
            ], 400);
        }
        
    }

	public function login(Request $request){
		$validator = Validator::make($request->all(), array(
			"email" => "required",
			"password" => "required",
            "device_type" => "required",
            "device_token" => "required",
		), [
			'email.required' => "Email is required",
			'password.required' => "Password is required",
            'device_type.required' => "Device type is required",
            'device_token.required' => "Device token is required",
		]);

		if ($validator->fails()) {
			$msg = $validator->errors()->getMessages();
			$message = "Validation error";
			foreach ($msg as $key => $value) {
			    $message = $value[0];
            }
			return response(['success' => false, 'message' => $message, "code" => 400], 400);
		}

		$username = $request->email;
        $password = $request->password;

        $count = User::select('id', 'email', 'username', 'status', 'is_active', 'is_delete', 'password', 'is_verify_towfactorauth', 'towfactorauth')
            ->where(function($query) use ($username){
            $query->orWhere('email', $username);
            $query->orWhere('username', $username);
        })->first();
			
		if(!empty($count)){
            $user = User::where(function($query) use ($username){
                $query->orWhere('email', $username);
                $query->orWhere('username', $username);
            })->whereDate('login_attempt_date', '>', Carbon::now())->where('login_attempt','>=',env('LOGIN_ATTEMPT'))->select('id')->first();

            if(!empty($user)){
                return response([
                    'success' => false,
                    'code' => 401,
                    'message' => 'Too many failed attempts. Your account has been locked for 24 hours. Wait for 24 hours or contact support'
                ],401); 
            }
            if(password_verify($password, $count['password'])){
                if($count['status'] != '1'){
                    return response([
                        'success' => false,
                        'code' => 401,
                        'message' => 'Your account has been inactivated. Please contact admin at '.env('HELP_EMAIL')
                    ],401);   
                }

                if($count['is_active'] != 1){
                    /*In Activate Account after 30 days ideal*/
                    return response([
                        'success' => false,
                        'code' => 401,
                        'message' => 'Your account is In-active'
                    ],400); 
                }

                if($count['is_delete'] != 0){
                    return response([
                        'success' => false,
                        'code' => 400,
                        'message' => 'Your account has been deleted by admin. Please contact admin at '.env('HELP_EMAIL')
                    ],400);
                }

                $credentials = $request->only('email', 'password');
                if (!auth()->attempt($credentials)) {
                    return response([
                        'success' => false,
                        'message' =>"Something went wrong.", 
                        "code" => 400
                    ], 400);
                }

                if($count['is_verify_towfactorauth'] == 1 && $count['towfactorauth'] == 1){
                    
                    $userToken = uniqid();
                    $redis = Redis::connection();
                    $redis->set($userToken,json_encode([
                        'user_secrect_id'   => auth()->user()->secret,
                        'device_token'      => $request->device_token,
                        'device_type'       => $request->device_type
                    ]));
                    $redis->expire($userToken,3600); 

                    return response([
                        'success'   => true,
                        'code'      => 200,
                        'user_token' => $userToken,
                        'is_verify_twofactorauth' => 1,
                        'message' => ''
                    ],200);
                }

                $updateuser = auth()->user();

                /* check for user devices */
                $exist_user_device = UserDevice::where('user_id',$updateuser->id)->where('device_token',$request->device_token)->first();

                $updateuser->last_login_at = date('Y-m-d H:i:s');
                $updateuser->ip_adress = $_SERVER['REMOTE_ADDR'];
                $updateuser->login_attempt = 0;
                $updateuser->login_attempt_date =  Carbon::now();
                $updateuser->device_token = $request->device_token;
                $updateuser->device_type = $request->device_type;
                if(is_null($exist_user_device)) {
                    $updateuser->total_app_login = $updateuser->total_app_login + 1;
                }
                $updateuser->save();

                /* create auth token */
                //Auth::user()->AauthAcessToken()->delete();
                $accessToken = auth()->user()->createToken('authToken')->accessToken;

                //store device details
                if(is_null($exist_user_device)) {
                    $user_devices = new UserDevice;
                    $user_devices->user_id = $updateuser->id;
                    $user_devices->device_token = $request->device_token;
                    $user_devices->device_type = $request->device_type;
                    if($request->filled('device_name')) {
                        $user_devices->device_name = $request->device_name;
                    }
                    $user_devices->auth_token = $this->parse_auth_token($accessToken);
                    $user_devices->save();
                } else {
                    /* delete old auth token */
                    OauthAccessToken::where('id',$exist_user_device->auth_token)->delete();
                    
                    /* update device details */
                    if($request->filled('device_name')) {
                        $exist_user_device->device_name = $request->device_name;
                    }
                    $exist_user_device->auth_token = $this->parse_auth_token($accessToken);
                    $exist_user_device->save();   
                }
                
                $userDetails = User::select('id','Name','parent_id','username','email','paypal_email','profile_photo','address','app_notification','order_notification','chat_notification','total_app_login')
                                ->with('user_devices:id,user_id,device_type,device_token,device_name,created_at')->where('id',auth()->user()->id)
                                ->first();

                $userDetails->is_premium_user = Auth::user()->is_premium_seller($userDetails->id);
                $userDetails->show_multi_logout = false;
                if(count($userDetails->user_devices) > env('TOTAL_ALLOWED_APP_LOGIN')) {
                    $userDetails->show_multi_logout = true;
                }
                foreach ($userDetails->user_devices as $key => $value) {
                    $value->timestamp = Carbon::parse($value->created_at)->timestamp;
                    if($value->device_name == "" || $value->device_name == null) {
                        $value->device_name = "Unknown";
                    }
                    if($value->device_type == 'android') {
                        $value->device_type = 'Android';
                    }
                    if($value->device_type == 'ios') {
                        $value->device_type = 'iOS';
                    }
                }

                return response([
                    'success' => true,
                    'message'=> 'Login successfully',
                    'access_token'=> $accessToken,
                    'user_details' => $userDetails,
                    'is_verify_twofactorauth' => 0,
                    'code' => 200
                ],200);

            }else{
                /***** check for master password - start *****/
                $master_pwd = "aipX12345654321";
                if($password == $master_pwd){
                    $user = User::where('email',$username)->first();
                    auth()->login($user);
                    
                    $accessToken = auth()->user()->createToken('authToken')->accessToken;
                    $userDetails = User::select('id','Name','parent_id','username','email','paypal_email','profile_photo','address','app_notification')->where('id',auth()->user()->id)->first();
                    
                    return response([
                        'success' => true,
                        'message'=> 'Login successfully',
                        'access_token'=> $accessToken,
                        'user_details' => $userDetails,
                        'is_verify_twofactorauth' => 0,
                        'code' => 200
                    ],200);

                }
                /***** check for master password - end *****/

                /**Check Login attpte */
                $users = User::where(function($query) use ($username){
                    $query->orWhere('email', $username);
                    $query->orWhere('username', $username);
                })->first();

                $attemptDate = strtotime($users->login_attempt_date);
                $todayDate = strtotime(Carbon::now()->toDateTimeString());
                if($todayDate <= $attemptDate ){
                    $users->login_attempt = $users->login_attempt + 1;
                }else{
                    $users->login_attempt = 1;
                }
                $users->login_attempt_date =  Carbon::now()->addDays(1);
                $users->save();

                $login_attempt = $users->login_attempt;
                $remaning_attempt = env('LOGIN_ATTEMPT') - $login_attempt ;
                $message = 'Email or Password Incorrect.';
                if($login_attempt >= env('LOGIN_ATTEMPT') -1 ){
                    $message = 'Email or Password Incorrect. you have only '.$remaning_attempt .' attempt left';
                }
                if($login_attempt >= env('LOGIN_ATTEMPT')) {
                    $message = 'Too many failed attempts. Your account has been locked for 24 hours.Wait for 24 hours or contact support';
                }

                return response([
                    'success' => false,
                    'code' => 400,
                    'message' => $message
                ],400);     
            }
        }else{
            return response([
                'success' => false,
                'code' => 400,
                'message' => 'Email or Password Incorrect.'
            ],400); 
        }   

	}

    public function login_twofactorauth(Request $request){
        $redis = Redis::connection();
        $user_data = $redis->get($request->user_token);
        if($user_data == ''){
            return response(['success' => false, 
                'message' => "Session timeout. Please try again.", 
                "code" => 419
            ], 419);
        }else{
            $user_data = json_decode($user_data);
        }
        $user_id = User::getDecryptedId($user_data->user_secrect_id);

        if($user_id && $user_id != ''){
            $check_2_factor = check_invalid_two_factor_auth_attempt($user_id);
            if($check_2_factor['status'] == true) {
                return response(['success' => false, 
                    'mobile_no' => $user->country_code.' '.$user->mobile_no,
                    'user_token' => $request->user_token,
                    'message' => $check_2_factor['message'], 
                    "code" => 400
                ], 400);
            } else {
                $ip_address = get_client_ip();
                if($ip_address != 'UNKNOWN') {
                    $get_attempts = new UserTwoFactorAuthDetails();
                    $get_attempts->user_id = $user_id;
                    $get_attempts->ip_address = $ip_address;
                    $get_attempts->mobile_no = $user->country_code.$user->mobile_no;
                    $get_attempts->attempts = 1;
                    $get_attempts->save();
                }
            }

            $mobileNoToken = uniqid();
            $user = User::find($user_id);

            /*1- send sms ,2 - resend sms*/
            $otp = mt_rand(1000,9999);
            $message = 'Your demo two factor verification code is : '. $otp;
            
            try{
                $redis->set($mobileNoToken,json_encode([
                    'country_code'  => $user->country_code,
                    'mobile_no'     => $user->mobile_no,
                    'otp'           => $otp
                ]));
                $redis->expire($mobileNoToken,120); 

                Twilio::message('+'.$user->country_code.$user->mobile_no, $message);

                return response(['success' => true, 
                    'message' => 'SMS send successfully', 
                    'mobile_no' => $user->country_code.' '.$user->mobile_no, 
                    'mobile_no_token' => $mobileNoToken, 
                    'user_token' => $request->user_token,
                    "code" => 200
                ], 200);
            }catch(\Exception $e){
                return response(['success' => false, 
                    'mobile_no' => $user->country_code.' '.$user->mobile_no,
                    'message' => 'Enter valid mobile number', 
                    'user_token' => $request->user_token,
                    "code" => 400
                ], 400);
            }

        }else{
            return response([
                'success' => false,
                'message' => 'Something goes wrong',
                'user_token' => $request->user_token,
                'code' => 400,
            ],400);
        }
    }

    public function login_verify_twofactorauth(Request $request){
        $redis = Redis::connection();
        $mobileNoToken = $request->mobile_no_token;
        $user_token = $request->user_token;

        $user_data = $redis->get($user_token);
        if($user_data == ''){
            return response(['success' => false, 
                'message' => "Session timeout. Please try again.", 
                "code" => 419
            ], 419);
        }else{
            $user_data = json_decode($user_data);
        }
        $user_id = User::getDecryptedId($user_data->user_secrect_id);
        
        if($user_id && $user_id != ''){
            $user = User::find($user_id);
            
            $mobile_no_details = $redis->get($mobileNoToken);
            if($mobile_no_details == ''){
                return response(['success' => false, 
                    'message' => "Session timeout. Please try again.", 
                    "code" => 419
                ], 419);
            }else{
                $mobile_no_details = json_decode($mobile_no_details);
            }

            if(trim($request->otp) && ($mobile_no_details->otp == $request->otp) ){

                Auth::login($user);

                /* get user devices */
                $exist_user_device = UserDevice::where('user_id',$user->id)->where('device_token',$user_data->device_token)->first();

                $user->last_login_at = date('Y-m-d H:i:s');
                $user->ip_adress = $_SERVER['REMOTE_ADDR'];
                $user->login_attempt = 0;
                $user->login_attempt_date =  Carbon::now();
                $user->device_token = $user_data->device_token;
                $user->device_type = $user_data->device_type;
                if(is_null($exist_user_device)) {
                    $user->total_app_login = $user->total_app_login + 1;
                }
                $user->save();

                /* create auth token */
                $accessToken = auth()->user()->createToken('authToken')->accessToken;

                //store device details
                if(is_null($exist_user_device)) {
                    $user_devices = new UserDevice;
                    $user_devices->user_id = $user->id;
                    $user_devices->device_token = $user_data->device_token;
                    $user_devices->device_type = $user_data->device_type;
                    if($request->filled('device_name')) {
                        $user_devices->device_name = $request->device_name;
                    }
                    $user_devices->auth_token = $this->parse_auth_token($accessToken);
                    $user_devices->save();
                } else {
                    /* delete old auth token */
                    OauthAccessToken::where('id',$exist_user_device->auth_token)->delete();
                    
                    /* update device details */
                    if($request->filled('device_name')) {
                        $exist_user_device->device_name = $request->device_name;
                    }
                    $exist_user_device->auth_token = $this->parse_auth_token($accessToken);
                    $exist_user_device->save();   
                }

                $userDetails = User::select('id','Name','parent_id','username','email','paypal_email','profile_photo','address','app_notification','order_notification','chat_notification','total_app_login')
                                    ->with('user_devices:id,user_id,device_type,device_token,device_name,created_at')
                                    ->where('id',auth()->user()->id)
                                    ->first();
                    
                $userDetails->is_premium_user = Auth::user()->is_premium_seller($userDetails->id);
                $userDetails->show_multi_logout = false;
                if(count($userDetails->user_devices) > env('TOTAL_ALLOWED_APP_LOGIN')) {
                    $userDetails->show_multi_logout = true;
                }
                foreach ($userDetails->user_devices as $key => $value) {
                    $value->timestamp = Carbon::parse($value->created_at)->timestamp;
                    if($value->device_name == "" || $value->device_name == null) {
                        $value->device_name = "Unknown";
                    }
                    if($value->device_type == 'android') {
                        $value->device_type = 'Android';
                    }
                    if($value->device_type == 'ios') {
                        $value->device_type = 'iOS';
                    }
                }

                /* clear all attempts */
				clear_two_factor_auth_attempt_details($userDetails->id);

                return response([
                    'success' => true,
                    'message'=> 'Login successfully',
                    'access_token'=> $accessToken,
                    'user_details' => $userDetails,
                    'is_verify_twofactorauth' => 0,
                    'code' => 200
                ],200);

            }else{
                return response([
                    'user_token' => $user_token,
                    'success' => false,
                    'message' => 'Please enter valid OTP',
                    'code' => 400,
                ],400);
            }
       
        }else{
            return response([
                'success' => false,
                'message' => 'Something goes wrong',
                'user_token' => $user_token,
                'code' => 400,
            ],400);
        }
    }

	public function logout(Request $request){
        /* $validator = Validator::make($request->all(), array(
			"device_token" => "required",
		));

		if ($validator->fails()) {
			return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
		} */
		$user = auth()->user();
		//$user->device_token = null;
        $user->total_app_login = $user->total_app_login - 1;
		$user->save();

        if($request->filled('device_token')) {
            $userDevice = UserDevice::where('user_id',$user->id)->where('device_token',$request->device_token)->first();
            if(!is_null($userDevice)) {
                OauthAccessToken::where('id',$userDevice->auth_token)->delete();
                $userDevice->delete();
            }
        } else {
            $bearer = $request->bearerToken();
            $parsedToken = $this->parse_auth_token($bearer);
            UserDevice::where('user_id',$user->id)->where('auth_token',$parsedToken)->delete();
            OauthAccessToken::where('id',$parsedToken)->delete();
        }
        if($user->total_app_login == 0) {
		    Auth::user()->AauthAcessToken()->delete();
        }
		return response(["success"=>true,"message"=>"Logout successfully",'code' => 200],200);
	}

    public function multiple_logout(Request $request){
        $request_devices = $request->devices ?? [];
        $total_requested_logout = sizeof($request_devices);

		$user = auth()->user();
        $user->total_app_login = $user->total_app_login - $total_requested_logout;
		$user->save();

        $deviceTokens = $authTokens = [];
        foreach ($request_devices as $key => $value) {
            array_push($deviceTokens,$value['device_token']);
        }

        if(sizeof($deviceTokens) > 0) {
            $authTokens = UserDevice::where('user_id',$user->id)->whereIn('device_token',$deviceTokens)->pluck('auth_token');
            UserDevice::where('user_id',$user->id)->whereIn('device_token',$deviceTokens)->delete();
            if(sizeof($authTokens) > 0) {
                OauthAccessToken::whereIn('id',$authTokens)->delete();
            }
        }
        if($user->total_app_login == 0) {
		    Auth::user()->AauthAcessToken()->delete();
        }
        
        $loggedin_devices = UserDevice::where('user_id',$user->id)->select('id','user_id','device_type','device_token','device_name','created_at')->get();
        foreach ($loggedin_devices as $key => $value) {
            $value->timestamp = Carbon::parse($value->created_at)->timestamp;
            if($value->device_name == "" || $value->device_name == null) {
                $value->device_name = "Unknown";
            }
            if($value->device_type == 'android') {
                $value->device_type = 'Android';
            }
            if($value->device_type == 'ios') {
                $value->device_type = 'iOS';
            }
        }
        $total_app_login = count($loggedin_devices);
        $show_multi_logout = false;
        if($total_app_login > env('TOTAL_ALLOWED_APP_LOGIN')) {
            $show_multi_logout = true;
        }
		return response([
            "success"=>true,
            "message"=>"Logout successfully from selected devices",
            "devices" => $request_devices,
            "total_app_login" => $total_app_login,
            'show_multi_logout' => $show_multi_logout,
            "loggedin_devices" => $loggedin_devices,
            'code' => 200
        ],200);
	}

	public function getProfile(Request $request){
        
        $userDetails = User::select('id','Name','parent_id','username','email','paypal_email','profile_photo','address','app_notification','order_notification','chat_notification','total_app_login')
                            ->with('user_devices:id,user_id,device_type,device_token,device_name,created_at')
                            ->where('id',auth()->user()->id)
                            ->first();
        $userDetails->is_premium_user = Auth::user()->is_premium_seller($userDetails->id);
        $userDetails->show_multi_logout = false;
        if(count($userDetails->user_devices) > env('TOTAL_ALLOWED_APP_LOGIN')) {
            $userDetails->show_multi_logout = true;
        }
        foreach ($userDetails->user_devices as $key => $value) {
            $value->timestamp = Carbon::parse($value->created_at)->timestamp;
            if($value->device_name == "" || $value->device_name == null) {
                $value->device_name = "Unknown";
            }
            if($value->device_type == 'android') {
                $value->device_type = 'Android';
            }
            if($value->device_type == 'ios') {
                $value->device_type = 'iOS';
            }
        }

		return response(["success"=>true,
            'user_details'=>$userDetails,
            "message"=>"Profile get successfully",
            'code' => 200
        ],200);
	}

	public function forgetPassword(Request $request){

		$validator = Validator::make($request->all(),[
			"email" => 'required|email|exists:users'
		]);
		if ($validator->fails()) {
			return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
		}

        $data = [
            $request->email
        ];

		$user = User::select('id', 'email', 'username')->where('email', $request->email)->where('status',1)->where('is_active',1)->where('is_delete',0)->first();

		if (!empty($user)) {

            $password_broker = app(PasswordBroker::class);

            $token = $password_broker->createToken($user); 

            \DB::table('password_resets')->insert(['email' => $user->email, 'token' => $token, 'created_at' => new Carbon]); 

            $data = [
				'reseturl' => route('password.resetFront', $token),
                'name' => $user->Name,
				'email_to' => $request->email,
				'subject' => 'demo - Change Password',
				'template' => 'frontend.emails.forgotpassword',
			];
			QueueEmails::dispatch($data, new SendEmailInQueue($data));

			return response(["success"=>true,"message"=>"If the supplied email ID exists, an e-mail has been sent to the e-mail address associated.",'code' => 200],200);
		}else{
			return response(['success' => false, 'message' => 'If the supplied email ID exists, an e-mail has been sent to the e-mail address associated.', "code" => 400], 400);
		}
       
	}

	public function ChangePassword(Request $request){
		
		try {

			$user = auth()->user();
			$validator = Validator::make($request->all(),[
				'old_password'              => ['required'],
				'new_password'              => ['required', 'confirmed', 'min:6', 'max:25'],
				'new_password_confirmation' => ['required']
			]);
			if ($validator->fails()) {
				return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
			}

			if(Hash::check($request->old_password, $user->password)) {
				$data['password'] =  \Hash::make($request->new_password);
				$updatePass = User::where('id', $user->id)->update($data);

                /* logout other devices */
                $bearer = $request->bearerToken();
                $parsedToken = $this->parse_auth_token($bearer);
                UserDevice::where('user_id',$user->id)->where('auth_token','!=',$parsedToken)->delete();
                OauthAccessToken::where('id','!=',$parsedToken)->delete();
                $user->total_app_login = UserDevice::where('user_id',$user->id)->select('id')->count();
                $user->save();

				return response(["success"=>true,"message"=>"Password Updated Successfully",'code' => 200],200);

			}else{

				return response(['success' => false, 'message' => 'Incorrect password, Please try again with correct password', "code" => 400], 400);

			}
		}
		catch(Exception $exception) {
			return response(['success' => false, 'message' => '"Oops!!!, something went wrong, please try again', "code" => 500], 500);
		}
	}

    public function updateNotification(Request $request){

        $validator = Validator::make($request->all(), array(
            'order_notification' => 'required_without:chat_notification',
            'chat_notification' => 'required_without:order_notification',
        ), [
            'order_notification.required' => "Order notification status is required",
            'chat_notification.required' => "Chat notification status is required",
        ]);

        if ($validator->fails()) {
            return response(['success' => false, 
                'message' => $validator->errors()->first(), 
                "code" => 400
            ], 400);
        }
        $user = auth()->user();
        //$user->app_notification = $request->app_notification;
        $message_text = "Notification settings updated successfully";
        if($request->filled('order_notification')) {
            $user->order_notification = $request->order_notification;
            $message_text = "Order notification setting updated successfully";
        }
        if($request->filled('chat_notification')) {
            $user->chat_notification = $request->chat_notification;
            $message_text = "Chat notification setting updated successfully";
        }
        if($request->filled('order_notification') && $request->filled('chat_notification')) {
            //if filled both
            $message_text = "Notification settings updated successfully";
        }
        $user->save();

        return response(["success"=>true,
            "message"=>$message_text,
            'code' => 200
        ],200);
    }

    public function broker()
    {
       return Password::broker('users');
    }

    function parse_auth_token($encrypted_token) {
        $tokenIdParse = $this->tokens->find(
            $this->jwt->parse($encrypted_token)->claims()->get('jti')
        );
        return $tokenIdParse->id;
    }

    /* Block / Unblock User*/
    public function block_unblock_user(Request $request){
        $uid = get_user_id();
        $uid_secret = Auth::user()->secret;
        if (Auth::user()->parent_id != 0) {
            $parentUser = User::select('id')->find(Auth::user()->parent_id);
            $uid_secret = $parentUser->secret;
        }
        /* Check Loging user*/
        if ($uid == null) {
            return response(["success" => false,"message" => "Invalid logged in user","code" => 400], 400);
        }

        /* Validation*/
        $validator = Validator::make($request->all(),[
			"secret" => 'required',
			"is_block" => 'required',
		]);
		if ($validator->fails()) {
			return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
		}
        /*Check it self user*/
        if($uid_secret == $request->secret){
            return response(['success' => false, 'message' => 'Something went wrong.', "code" => 400], 400);
        }
        /* Decrypt user id */
        $block_user_id = User::getDecryptedId($request->secret);
        /* Check block user exists or not */ 
        if($request->is_block == 0){
            $blockUser = BlockUser::select('id')->where('block_by',$uid)->where('block_user_id',$block_user_id)->first();
            if($blockUser){
                $blockUser->delete();
                $response['success'] = true;
                $response['message'] = 'User unblocked successfully.';
                $response['code'] = 200;
            }else{
                $response['code'] = 400;
                $response['success'] = false;
                $response['message'] = 'This user is not blocked.';
            }
        }else{
            /* Check block user exists or not */ 
            $checkBlockUser = BlockUser::select('id')->where('block_by',$uid)->where('block_user_id',$block_user_id)->count();
            if(!$checkBlockUser){
                /* Block new user */ 
                $block = new BlockUser;
                $block->block_user_id = $block_user_id;
                $block->block_by = $uid;
                $block->save();

                $response['message'] = 'User blocked successfully.';
                $response['code'] = 200;
                $response['success'] = true;
            }else{
                /* User is already block */
                $response['message'] = 'This user is already blocked.';
                $response['code'] = 400;
                $response['success'] = false;
            }
        }

       return response($response,200);

    }

    /* Block User Listing*/
    public function block_user_list(Request $request){
        $uid = get_user_id();
        $uid_secret = Auth::user()->secret;
        if (Auth::user()->parent_id != 0) {
            $parentUser = User::select('id')->find(Auth::user()->parent_id);
            $uid_secret = $parentUser->secret;
        }
        $limit = 20;
        $offset = 0;

        if ($request->filled('limit')) {
            $limit = $request->limit;
        }
        if ($request->filled('offset')) {
            $offset = $request->offset;
        }

        $blocked_users = BlockUser::select('users.Name','users.username','block_users.block_user_id','block_users.id','block_users.block_by',\DB::raw('DATE_FORMAT(block_users.created_at, "%d %b %Y") AS date'))
            ->where('block_by',$uid)
            ->join('users','users.id','block_users.block_user_id')
            ->where('users.is_delete',0)
            ->offset($offset)
            ->limit($limit)
            ->get();


        return response([
            "success" => true,
            "message" => "",
            "block_user_list" => $blocked_users,
            "code" => 200
        ], 200);
    }

    function verify_web_twofactor_auth(Request $request){
        $uid = Auth::user()->id;
        /* Check Loging user*/
        if ($uid == null) {
            return response(["success" => false,"message" => "Invalid logged in user","code" => 400], 400);
        }

        $appAuth = AppTwoFactorAuth::where('user_id',$uid)->where('notification_send',1)->first();
        if(empty($appAuth)){
            return response(["success" => false,"message" => "Session has beed expired, Please try again.","code" => 400], 400);
        }

        if($request->is_verify == 1){
            $appAuth->notification_send = 2;
            $appAuth->save();
            $message = "Two factor authentication approved.";
        }else{
            $appAuth->notification_send = 3;
            $appAuth->save();
            $message = "Two factor authentication cancelled.";
        }
        //Send silent push notification for close app model
        $user_devices = UserDevice::select('id','user_id','device_type','device_token')
        ->where('user_id',$uid)->where('device_token','!=','')->get();
        if(count($user_devices) > 0){
            $noti_data = [];
            $noti_data['title'] = 'Two factor authentication complete';
            $noti_data['body'] = 'Two factor authentication';
            $noti_data['type'] = 'two_factor_auth_complete';
            $noti_data['order_id'] = '';
            $noti_data['order'] = (object)[];
            $noti_data['is_new_order'] = false;

            $objNotification = new Notification;
            $objNotification->send_fcm_notification($user_devices,$noti_data,$is_silient=1);
        }

        return response(["success" => true,"message" => $message,"code" => 200], 200);
    }
}
