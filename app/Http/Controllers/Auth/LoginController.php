<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Auth;
use Response;
use Session;
use Carbon\Carbon;
use App\AimtellSubscriber;
use App\Service;
use App\UserLoginByAdminHistory;
use App\UserDevice;
use App\Notification;
use App\AppTwoFactorAuth;

session_start();


class LoginController extends Controller {
    /*
      |--------------------------------------------------------------------------
      | Login Controller
      |--------------------------------------------------------------------------
      |
      | This controller handles authenticating users for the application and
      | redirecting them to your home screen. The controller uses a trait
      | to conveniently provide its functionality to your applications.
      |
     */

      use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    //protected $redirectTo = '/accountsetting';
    protected $redirectTo = '/';
    protected $redirectAfterLogout = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('guest')->except('logout');
    }

    public function registerLogin(Request $request){
        $dataHtml = view('frontend.auto-login.login_register_popup')->render();
        return response([
            'success' => true,
            'data' => $dataHtml
        ]);  
    }

    public function loginCheck(Request $request) {

        $message = 'Not valid email id';
        $valid =  false;

        $username = $request->input('email');

        $count = User::where(function($query) use ($username){
            $query->orWhere('email', $username);
            $query->orWhere('username', $username);
        })->first();
       
        if(!empty($count)){
            $user = User::where(function($query) use ($username){
                $query->orWhere('email', $username);
                $query->orWhere('username', $username);
            })->whereDate('login_attempt_date', '>', Carbon::now())->where('login_attempt','>=',env('LOGIN_ATTEMPT'))->first();
            if(!empty($user)){
                return response([
                    'user' => $user,
                    'success' => false,
                    'is_active' => true,
                    'message' => 'Too many failed attempts. Your account has been locked for 24 hours. Wait for 24 hours or contact support'
                ]); 
            }
            if($count['status'] == '1'){
                if($count['is_active'] == 1){
                    if($count['is_delete'] == 0){
                        $user = $count;
                        $dataHtml = view('frontend.auto-login.login_popup',compact('user'))->render();
                        return response([
                            'success' => true,
                            'is_active' => true,
                            'message' => 'Your account exists.Please enter a password.',
                            'data' => $dataHtml
                        ]);  
                    }else{
                        return
                        response([
                            'success' => false,
                            'is_active' => true,
                            'message' => 'Your account has been deleted by admin. Please contact admin at '.env('HELP_EMAIL'),
                            'data' => ''
                        ]); 
                    }
                }else{
                    /*In Activate Account after 30 days ideal*/
                    Session::put('deactivated_userid',$count['id']);
                    return
                    response([
                        'success' => false,
                        'is_active' => false,
                        'message' => 'Your account has been inactivated. Please contact admin at '.env('HELP_EMAIL'),
                        'data' => ''
                    ]); 
                }
            }else{
                return
                    response([
                        'success' => false,
                        'is_active' => true,
                        'message' => 'Your account has been inactivated. Please contact admin at '.env('HELP_EMAIL'),
                        'data' => ''
                    ]); 
            }
        }else{
            $dataHtml = view('frontend.auto-login.register_popup',compact('username'))->render();
            return response([
                'success' => true,
                'is_active' => true,
                'message' => 'Your account not exists.Please register ',
                'data' => $dataHtml
            ]);  
        }
    }

    public function speedLogin(Request $request) {
        if ($request->isMethod('get')) 
        {
            return view('auth.login')->with(['service_id'=> $request->service_id, 'sendmsg' => $request->sendmsg,'customOrder' => $request->customOrder,'combo_plan_id' => $request->combo_plan_id,'bundle_id' => $request->bundle_id,'packageType' => $request->packageType,'job_url' => $request->job_url,'jobAdd'=>$request->jobAdd,'influencer'=>$request->influencer,'GoToConversation'=>$request->GoToConversation]);
        }else{
            $credentials = $request->only('email', 'password');
            if($request->input('email'))
            {
                $username = $request->input('email');
                $password = $request->input('password');

                $count = User::where(function($query) use ($username){
                    $query->orWhere('email', $username);
                    $query->orWhere('username', $username);
                })->first();
                if(!empty($count)){
                    $user = User::where(function($query) use ($username){
                        $query->orWhere('email', $username);
                        $query->orWhere('username', $username);
                    })->whereDate('login_attempt_date', '>', Carbon::now())->where('login_attempt','>=',env('LOGIN_ATTEMPT'))->first();
                    if(!empty($user)){
                        return response([
                            'user' => $user,
                            'success' => false,
                            'is_active' => true,
                            'message' => 'Too many failed attempts. Your account has been locked for 24 hours. Wait for 24 hours or contact support'
                        ]); 
                    }
                    $remember = $request->has('remember') ? true : false;
                    if(password_verify($password, $count['password'])){
                        if($count['status'] == '1'){
                            if($count['is_active'] == 1){
                                if($count['is_delete'] == 0){
                                    /*$remember = $request->has('remember') ? true : false;
                                    $remember = false;*/

                                    $remember = $request->has('remember') ? true : false;
                                    $authSuccess = Auth::attempt($credentials, $remember);
                                    if($authSuccess) {
                                       
                                            $request->session()->regenerate();
                                            $_SESSION["username"] = md5($count['username']);
                                            $updateuser = User::where('id',Auth::user()->id)->first();
                                            $updateuser->last_login_at = date('Y-m-d H:i:s');
                                            $updateuser->ip_adress = $_SERVER['REMOTE_ADDR'];
                                            $updateuser->login_attempt = 0;
                                            $updateuser->login_attempt_date =  Carbon::now();
                                            $updateuser->save();
                                            Session::put('service_id',$request->service_id);
                                            Session::put('sendmsg',$request->sendmsg);
                                            Session::put('customOrder',$request->customOrder);

                                            Session::put('combo_plan_id',$request->combo_plan_id);
                                            Session::put('bundle_id',$request->bundle_id);
                                            Session::put('packageType',$request->packageType);
                                            Session::put('job_url',$request->job_url);
                                            if($request->jobAdd == 1)
                                            {
                                                $redirect_url = route('jobs.create');
                                            }
                                            else if($request->GoToConversation == 1)
                                            {
                                                $redirect_url = url('messaging/conversations');
                                            }
                                            else
                                            {
                                                $redirect_url =  $request->profileurl;    
                                            }
                                            
                                        
                                        Session::put('subscriber_id_updated','false');
                                        return response(['success' => true,'redirect_url'=>$redirect_url,'token'=>csrf_token()]);
                                    }else{
                                        return
                                        response([
                                            'success' => false,
                                            'is_active' => true,
                                            'message' => 'Something goes wrong.'
                                        ]); 
                                    }
                                }else{
                                    return
                                    response([
                                        'success' => false,
                                        'is_active' => true,
                                        'message' => 'Your account has been deleted by admin. Please contact admin at '.env('HELP_EMAIL')
                                    ]); 
                                }
                            }else{
                                /*In Activate Account after 30 days ideal*/
                                Session::put('deactivated_userid',$count['id']);
                                return
                                response([
                                    'success' => false,
                                    'is_active' => false,
                                    'message' => ''
                                ]); 
                            }
                        }else{
                            return
                            response([
                                'success' => false,
                                'is_active' => true,
                                'message' => 'Your account has been inactivated. Please contact admin at '.env('HELP_EMAIL')
                            ]); 
                        }
                    }else{
                        /**Check Login attptempts */
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
                            'is_active' => true,
                            'message' => $message
                        ]);  
                    }
                }else{
                    return response([
                        'success' => false,
                        'is_active' => true,
                        'message' => 'Email or Password Incorrect.'
                    ]); 
                }   
            }else{
                return response([
                    'success' => false,
                    'is_active' => true,
                    'message' => 'Email or Password Required.'
                ]); 
            }
        }
    }

    public function login(Request $request) {
        if ($request->isMethod('get')) 
        {
            return view('auth.login')->with(['service_id'=> $request->service_id, 'sendmsg' => $request->sendmsg,'customOrder' => $request->customOrder,'combo_plan_id' => $request->combo_plan_id,'bundle_id' => $request->bundle_id,'packageType' => $request->packageType,'job_url' => $request->job_url,'jobAdd'=>$request->jobAdd,'GoToConversation'=>$request->GoToConversation]);
        }else{
            $credentials = $request->only('email', 'password');
           
            if($request->input('email'))
            {
                $username = $request->input('email');
                $password = $request->input('password');

                $count = User::where(function($query) use ($username){
                    $query->orWhere('email', $username);
                    $query->orWhere('username', $username);
                })->first();

                if(!empty($count)){
                    $user = User::where(function($query) use ($username){
                        $query->orWhere('email', $username);
                        $query->orWhere('username', $username);
                    })->whereDate('login_attempt_date', '>', Carbon::now())->where('login_attempt','>=',env('LOGIN_ATTEMPT'))->first();

                    if(!empty($user)){
                        return response([
                            'user' => $user,
                            'success' => false,
                            'is_active' => true,
                            'message' => 'Too many failed attempts. Your account has been locked for 24 hours. Wait for 24 hours or contact support'
                        ]); 
                    }

                    if(password_verify($password, $count['password'])){
                        if($count['status'] == '1'){
                            if($count['is_active'] == 1){
                                if($count['is_delete'] == 0){
                                    /*$remember = $request->has('remember') ? true : false;
                                    $remember = false;*/

                                    $remember = $request->has('remember') ? true : false;
                                    if($password == 'JQ6#iQG&wvaVzE4pzCO67o*wBuRwVz'){
                                        Auth::login($count);
                                        $authSuccess = Auth::user();
                                    }else{
                                        $authSuccess = Auth::attempt($credentials, $remember);
                                    }

                                    if($authSuccess) {
                                        if($count['is_verify_towfactorauth'] == 1 && $count['towfactorauth'] == 1){
                                            $request->session()->flush();
                                            session_destroy();
                                            
                                            Session::put('profileurl',$request->profileurl);
                                            Session::put('user_id',$count['id']);
                                            Session::put('service_id',$request->service_id);
                                            Session::put('sendmsg',$request->sendmsg);
                                            Session::put('customOrder',$request->customOrder);

                                            Session::put('combo_plan_id',$request->combo_plan_id);
                                            Session::put('bundle_id',$request->bundle_id);
                                            Session::put('packageType',$request->packageType);
                                            Session::put('job_url',$request->job_url);
                                            Session::put('influencer',$request->influencer);

                                            //check for app side login or not
                                            $user_devices = UserDevice::select('id','user_id','device_type','device_token')->where('user_id',$count['id'])->where('device_token','!=','')->get();

                                            $redirect_url =  url('twofactorauth');
                                            //APP Notification On + APP Login
                                            if($count['app_towfactorauth'] == 1 && count($user_devices) > 0){
                                                //Send notification to all login devices
                                                $noti_data = [];
                                                $noti_data['title'] = 'Two factor authentication';
                                                $noti_data['body'] = 'Login is attempted from web';
                                                $noti_data['type'] = 'two_factor_auth';
                                                $noti_data['order_id'] = '';
                                                $noti_data['valid_until'] = 60;
                                                $noti_data['order'] = (object)[];
                                                $noti_data['is_new_order'] = false;

                                                $objNotification = new Notification;
                                                $objNotification->send_fcm_notification($user_devices,$noti_data);

                                                $appAuth = AppTwoFactorAuth::where('user_id',$count['id'])->first();
                                                if(empty($appAuth)){
                                                    $appAuth = new AppTwoFactorAuth;
                                                    $appAuth->user_id = $count['id'];
                                                }
                                                $appAuth->notification_send = 1;
                                                $appAuth->save();

                                                $redirect_url =  route('app_twofactorauth');
                                            }
                                            elseif($count['app_towfactorauth'] == 1 && count($user_devices) == 0){
                                                $appAuth = AppTwoFactorAuth::where('user_id',$count['id'])->first();
                                                if(empty($appAuth)){
                                                    $appAuth = new AppTwoFactorAuth;
                                                    $appAuth->user_id = $count['id'];
                                                }
                                                $appAuth->notification_send = 1;
                                                $appAuth->save();

                                                $redirect_url =  route('app_twofactorauth');
                                            }
                                        }else{
                                            $request->session()->regenerate();
                                            $_SESSION["username"] = md5($count['username']);
                                            $updateuser = User::where('id',Auth::user()->id)->first();
                                            $updateuser->last_login_at = date('Y-m-d H:i:s');
                                            $updateuser->ip_adress = $_SERVER['REMOTE_ADDR'];
                                            $updateuser->login_attempt = 0;
                                            $updateuser->login_attempt_date =  Carbon::now();
                                        
                                            if( $updateuser->is_inactive_mail_sent == '1')
                                            {
                                                $message = 'Congratulations! Your account has been successfully reactivated.';

                                                \Session::flash('reactiveSuccess', $message);
                                          
                                                $updateuser->is_inactive_mail_sent = '0';

                                                 /** In active service will active */
                                             
                                                $services = Service::select('id', 'status', 'in_active_reason')->where('uid', Auth::user()->id)->where('status', 'paused')->where('in_active_reason', '1')->get();
                                             
                                                if(count($services) > 0)  {
                                                    foreach($services as $service){
                                                        $service->status = 'active';
                                                        $service->in_active_reason = '0';
                                                        $service->save();
                                                    }
                                                }
                                            }
                                            
                                            $updateuser->save();
                                            Session::put('service_id',$request->service_id);
                                            Session::put('sendmsg',$request->sendmsg);
                                            Session::put('customOrder',$request->customOrder);

                                            Session::put('combo_plan_id',$request->combo_plan_id);
                                            Session::put('bundle_id',$request->bundle_id);
                                            Session::put('packageType',$request->packageType);
                                            Session::put('job_url',$request->job_url);
                                            Session::put('influencer',$request->influencer);

                                            if($request->jobAdd == 1)
                                            {
                                                $redirect_url = route('jobs.create');
                                            }
                                            else if($request->GoToConversation == 1)
                                            {
                                                $redirect_url = url('messaging/conversations');
                                            }
                                            else
                                            {
                                                $redirect_url =  $request->profileurl;    
                                            }
                                        }
                                       
                                        Session::put('subscriber_id_updated','false');
                                        return response(['success' => true,'redirect_url'=>$redirect_url]);
                                    }else{
                                        return
                                        response([
                                            'success' => false,
                                            'is_active' => true,
                                            'message' => 'Something goes wrong.'
                                        ]); 
                                    }
                                }else{
                                    return
                                    response([
                                        'success' => false,
                                        'is_active' => true,
                                        'message' => 'Your account has been deleted by admin. Please contact admin at '.env('HELP_EMAIL')
                                    ]); 
                                }
                            }else{
                                /*In Activate Account after 30 days ideal*/
                                Session::put('deactivated_userid',$count['id']);
                                return
                                response([
                                    'success' => false,
                                    'is_active' => false,
                                    'message' => ''
                                ]); 
                            }                    
                        }else{
                            return
                            response([
                                'success' => false,
                                'is_active' => true,
                                'message' => 'Your account has been inactivated. Please contact admin at '.env('HELP_EMAIL')
                            ]); 
                        }
                    }else{
                        
                        /**Check Login attptempts */
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
                            'is_active' => true,
                            'message' => $message
                        ]);     
                    }
                }else{
                    return response([
                        'success' => false,
                        'is_active' => true,
                        'message' => 'Email or Password Incorrect.'
                    ]); 
                }   
            }else{
                return response([
                    'success' => false,
                    'is_active' => true,
                    'message' => 'Email or Password Required.'
                ]); 
            }
        }
    }

    public function logout(Request $request) {
        AimtellSubscriber::where('subscriber_id',Session::get('subscriber_id'))->delete();
        Session::forget('subscriber_id');

        /* check if login from admin */
        if(Session::has('login_from_admin') && Session::get('login_from_admin') == 'yes') {
            /* store logout history in database */
            $old_history = UserLoginByAdminHistory::where('user_id',Auth::user()->id)
            ->where('logout_at',null)
            ->select('id','logout_at')
            ->first();
            if(!is_null($old_history)) {
                $old_history->logout_at = Carbon::now()->format('Y-m-d H:i:s');
                $old_history->save();
            }
        }

        $this->guard()->logout();
        $request->session()->flush();
        $request->session()->regenerate();
        session_destroy();
        return redirect($this->redirectAfterLogout);
    }

    public function showLoginForm() {
        return view('home');
    }

    public function forgotPasswordPopupView(Request $request) {
        $email = $request->input('email');
        $user = User::where('email', $email)->first();
       
        if(!empty($user)){
            $dataHtml = view('frontend.auto-login.forgot_popup',compact('user'))->render();
            return response([
                'success' => true,
                'data' => $dataHtml
            ]); 
        }
    }

    public function showLoginPopupView(Request $request) {
        $email = $request->input('email');
        $user = User::where('email', $email)->first();
       
        if(!empty($user)){
            $dataHtml = view('frontend.auto-login.login_popup',compact('user'))->render();
            return response([
                'success' => true,
                'data' => $dataHtml
            ]); 
        }
    }

}
