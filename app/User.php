<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Service;
use App\Order;
use App\Message;
use App\MessageDetail;
use Carbon\Carbon;
use Laravel\Passport\HasApiTokens;
use App\SubUserMailSetting;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use App\UserDevice;
use App\BlockUser;
use Auth;
use App\SubUserPermission;
use Config;
use DB;
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','username','confirmation_code','affiliate_id','seller_level_updated_at','seller_level','ip_adress','towfactorauth','is_verify_towfactorauth','mobile_no','country_code','interested_in','terms_privacy'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Database connection 
     * 
     */
    public static function set_db_connection($database = null){
        if($database == env('DB_DATABASE_BLOG')){
            Config::set('database.connections.mysql.host',env('DB_HOST_BLOG'));
            Config::set('database.connections.mysql.database',$database);
            Config::set('database.connections.mysql.username', env('DB_USERNAME_BLOG'));
            Config::set('database.connections.mysql.password', env('DB_PASSWORD_BLOG'));
        }else{
            Config::set('database.connections.mysql.host',env('DB_HOST'));
            Config::set('database.connections.mysql.database',env('DB_DATABASE'));
            Config::set('database.connections.mysql.username', env('DB_USERNAME'));
            Config::set('database.connections.mysql.password', env('DB_PASSWORD'));
        }
        DB::reconnect('mysql');
    }


    public function parent()
    {
        return $this->hasOne('App\User','id','parent_id');
    }
    public function seller_order(){
        return $this->hasMany('App\Order','seller_uid','id');
    }

    public function ontimedelivery()
    {
        return $this->hasOne('App\OnTimeDeliveryReport','seller_id','id');
    }

    public function seller_active_services()
    {
        return $this->hasMany('App\Service','uid','id')->where('status','active');
    }

    public function language()
    {
        return $this->hasMany('App\UserLanguage','uid','id');
    }
    public function skill()
    {
        return $this->hasMany('App\UserSkill','uid','id');
    }
    public function education()
    {
        return $this->hasMany('App\UserEducation','uid','id');
    }
    public function certification()
    {
        return $this->hasMany('App\UserCertification','uid','id');
    }

    public function country()
    {
        return $this->belongsTo('App\Country','country_id','id');
    }
    public function subscription(){
        return $this->hasOne('App\SubscribeUser','user_id','id');
    }

    public function billinginfo(){
        return $this->hasOne('App\BillingInfo','uid','id');
    }

    public function followers(){
        return $this->hasMany('App\UserFollow', 'user_id', 'id');
    }

    public function AauthAcessToken(){
        return $this->hasMany('\App\OauthAccessToken');
    }
    
    public function is_premium_seller($userId=null){
        date_default_timezone_set('America/Los_Angeles');
        $is_premium = false;

        if(!$userId){
            $userId = \Auth::user()->id;
        }

        if($userId == '17608' || $userId == '942' || $userId == '3111' || $userId == '38' || $userId == '14'){
            date_default_timezone_set('EST');
            return true;
        }

        $subscribeUser = SubscribeUser::select('id','end_date')->where('user_id',$userId)->first();
        if(count($subscribeUser) > 0){
            /*if(strtotime($subscribeUser->end_date) >= time()){
                $is_premium = true;
            }*/
            $grace_days = env('PREMIUM_SELLER_SUBSCRIPTION_GRACE_DAYS');
            $end_date = date('Y-m-d',strtotime($subscribeUser->end_date . "+".$grace_days." days"));
            if(strtotime($end_date) >= time()){
                $is_premium = true;
            }
            
        }
        date_default_timezone_set('EST');
        return $is_premium;
    }

    public function is_sub_user($id=null)
    {
        $is_sub = false;

        if(!$id){
            $id = \Auth::user()->id;
        }

        $data=User::where('id',$id)->first();
        $dataSub=$data->parent_id;
        if($dataSub == 0)
        {
            $is_sub=false;
        }
        else
        {
            $is_sub=true;  
        }

        return $is_sub;
    }

    public function calculate_base_count($id){
        
        /*Average Service Rating */
        /*Average Response Time */
        /*Successful Orders*/
        /*On Time Delivery Rate*/
        $avg_service_rating = $service_rating = $total_service = $total_orders = $total_hours = $average_response_time = $completed_orders = $successfull_orders = $delivered_orders = $on_time_delivered_orders = $on_time_delivered_val = $seller_base_count = 0;

        $seller_base_level = 'Unranked';

        $Order = new Order;
        $ServiceList = Service::select('services.id','service_rating')
            ->where('services.uid', $id)
            ->where('status','!=','draft')
            ->get();
        if(count($ServiceList)){

            foreach ($ServiceList as $rowService) {
                $avgServiceRating = $rowService->service_rating;
                //$avgServiceRating = $Order->calculateServiceAverageRating($rowService->id);
                if($avgServiceRating){
                    $service_rating += $avgServiceRating;
                    $total_service++;
                }
                
                $OrderList = Order::select('id','service_id','created_at','status','delivered_date','end_date')->where('service_id',$rowService->id)->get();

                //dd($OrderList->toArray());
                $total_orders += count($OrderList);

                if(count($OrderList)){

                    foreach ($OrderList as $rowOrder) {

                        /*Average Respose Time*/
                        $Message = Message::select('id')->where('service_id',$rowOrder->service_id)
                            ->where('order_id',$rowOrder->id)
                            ->first();    
                        if(count($Message)){
                            $messageDetail = MessageDetail::select('id','created_at')->where('msg_id',$Message->id)->where('from_user',$id)->first(); 
                            if(count($messageDetail)){
                                $datetime1 = new \DateTime($rowOrder->created_at);
                                $datetime2 = new \DateTime($messageDetail->created_at);
                                $interval = $datetime1->diff($datetime2);
                                $total_hours += $interval->format('%h');
                            }
                        }

                        /*Total Completed Orders*/
                        if($rowOrder->status == 'completed'){
                            $completed_orders++;
                        }

                        /*On TIme Delivered Orders*/
                        if($rowOrder->status == 'completed' || $rowOrder->status == 'delivered'){
                            $delivered_orders++;
                            if($rowOrder->delivered_date != '' ){
                                if(strtotime($rowOrder->delivered_date) <= strtotime($rowOrder->end_date)){
                                    $on_time_delivered_orders++;
                                }
                            }
                        }
                    }
                }
            }   

            /*Average Respose Time*/
            if($total_orders){
                $average_total_hours = $total_hours / $total_orders;
                if($average_total_hours <= 12){
                    $average_response_time = 5;
                }else if($average_total_hours <= 24){
                    $average_response_time = 4;
                }else if($average_total_hours <= 48){
                    $average_response_time = 3;
                }else if($average_total_hours <= 72){
                    $average_response_time = 2;
                }else if($average_total_hours <= 120){
                    $average_response_time = 1;
                }else {
                    $average_response_time = 0;
                }
            }

            /*Successful Orders*/
            if($completed_orders > 1000){
                $successfull_orders = 5;
            }else if($completed_orders > 500){
                $successfull_orders = 4.5;
            }else if($completed_orders > 250){
                $successfull_orders = 4;
            }else if($completed_orders > 100){
                $successfull_orders = 3;
            }else if($completed_orders > 25){
                $successfull_orders = 2;
            }else if($completed_orders > 1){
                $successfull_orders = 1;
            }else{
                $successfull_orders = 0;
            }

            /*On Time Delivery Rate*/
            if($delivered_orders > 0){
                $on_time_delivered_val = round(($on_time_delivered_orders * 100) / $delivered_orders)/10;
            }

            /*Average Service Rating */
            if($total_service){
                $avg_service_rating = $service_rating / $total_service;
            }

            /*Seller Base Count*/
            $seller_base_count = ( ($avg_service_rating * 2) + ($average_response_time) + ($successfull_orders) + ($on_time_delivered_val) ) / 35;

            $seller_base_count = round($seller_base_count,2);

            if($seller_base_count <= 0.24){
                $seller_base_level = 'Unranked';
            }else if($seller_base_count <= 0.39){
                $seller_base_level = 'Level 1';
            }else if($seller_base_count <= 0.59){
                $seller_base_level = 'Level 2';
            }else if($seller_base_count <= 0.74){
                $seller_base_level = 'Level 3';
            }else if($seller_base_count <= 0.89){
                $seller_base_level = 'Level 4';
            }else{
                $seller_base_level = 'Level 5';
            }
        }
        
        $response['total_orders'] = $total_orders;
        $response['total_hours'] = $total_hours;
        $response['avg_service_rating'] = round($avg_service_rating,2);
        $response['average_response_time'] = $average_response_time;
        $response['completed_orders'] = $completed_orders;
        $response['successfull_orders'] = $successfull_orders;
        $response['delivered_orders'] = $delivered_orders;
        $response['on_time_delivered_val'] = $on_time_delivered_val;
        $response['seller_base_count'] = $seller_base_count;
        $response['seller_base_level'] = $seller_base_level;

        return $seller_base_level;
        
    }
    public static function checkPremiumUser($id)
    {
        date_default_timezone_set('America/Los_Angeles');
        $is_premium = false;

        $userId=$id;

        if($userId == '17608' || $userId == '942' || $userId == '3111' || $userId == '38' || $userId == '14' ){
            return true;
        }

        $subscribeUser = SubscribeUser::select('id','end_date')->where('user_id',$userId)->first();

        if(count($subscribeUser) > 0){

        	$grace_days = env('PREMIUM_SELLER_SUBSCRIPTION_GRACE_DAYS');
            $end_date = date('Y-m-d',strtotime($subscribeUser->end_date . "+".$grace_days." days"));
            if(strtotime($end_date) >= time()){
                $is_premium = true;
            }
        }
        return $is_premium;
    }

    public function getOntimedelivery($sellerId){
        $lastMonthTime = new Carbon('first day of last month');
        $lastMonth = date('m',strtotime($lastMonthTime));
        $lastYear = date('Y',strtotime($lastMonthTime));

        $totalOders = Order::select('id')->where('seller_uid', $sellerId)
            ->where('status','active')
            //->where('isUpdatedQuestionAnswer',1)
            ->whereMonth('start_date',$lastMonth)
            ->whereYear('start_date',$lastYear)
            ->count();

        if ($totalOders) {
            $OnTimeDelivered = Order::select('id')->where('seller_uid', $sellerId)
                ->where('status','active')
                //->where('isUpdatedQuestionAnswer',1)
                ->whereMonth('start_date',$lastMonth)
                ->whereYear('start_date',$lastYear)
                ->where('end_date','>=',date('Y-m-d H:i:s'))
                ->count();

            $LateDelivered = Order::select('id')->where('seller_uid', $sellerId)
                ->where('status','active')
                //->where('isUpdatedQuestionAnswer',1)
                ->whereMonth('start_date',$lastMonth)
                ->whereYear('start_date',$lastYear)
                ->where('end_date','<',date('Y-m-d H:i:s'))
                ->count();    


            $totalDeliveredPer = ($OnTimeDelivered * 100) / $totalOders;
        } else {
            $totalDeliveredPer = $OnTimeDelivered = 100;
        }
        return (object) [
            'total_oders' => $totalOders,
            'on_time_delivered' => $OnTimeDelivered,
            'late_delivered' => $LateDelivered,
            'ontime_delivery_per' => round($totalDeliveredPer,2),
        ];
    }

    public function send_mail_to_subusers($check_for,$userId,$data=[],$replace_to_name=null){
        $is_subscribe = $this->is_premium_seller($userId);
        if($is_subscribe == true){
            $mail_setting = SubUserMailSetting::where('user_id',$userId)->first();
            if(!empty($mail_setting) && $mail_setting[$check_for] == 1){
                
                $subUsers = $this->select('email','Name')->where(['parent_id' => $userId])
                ->where('status',1)->where('is_delete',0)
                ->where('notification',1)
                ->get();

                if(count($subUsers) > 0){
                    foreach ($subUsers as $user) {
                        $data['email_to'] = $user->email;
                        if($replace_to_name){
                            $data[$replace_to_name] = $user->Name;
                        }
                        QueueEmails::dispatch($data, new SendEmailInQueue($data));
                    }
                }
            }
        }
    }

    /*Block user function*/
    public static function isUserBlock($block_user_id,$block_by){
        return BlockUser::select('id')->where('block_user_id',$block_user_id)->where('block_by',$block_by)->count(); 
    }
    /* Check block user both side */
    public static function isBlockMyProfile($block_user_id,$block_by){
        if(!User::isUserBlock($block_user_id,$block_by)){
            if(!User::isUserBlock($block_by,$block_user_id)){
                return 0;
            }
        }
        return 1;
    }
    /* Blocked users Ids */
    public static function getBlockedByIds(){
        if(Auth::check()){
            $uid = get_user_id();
            $arr1 = BlockUser::select('block_user_id')
            ->where('block_by',$uid)
            ->pluck('block_user_id')
            ->toArray();
            
            $arr2 = BlockUser::select('block_by')
            ->where('block_user_id',$uid)
            ->pluck('block_by')
            ->toArray();
            return array_merge($arr1,$arr2);
        }
        return array();
    }
    /*End Block user function*/

    protected $appends = ['secret'];

    public function getSecretAttribute()
    {
        $encrypted_string=openssl_encrypt($this->id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }
    public static function getDecryptedId($secret){
        return openssl_decrypt(base64_decode($secret),config('services.encryption.type'),config('services.encryption.secret'));
    }

    public function user_devices(){
        return $this->hasMany('App\UserDevice','user_id','id');
    }

    public static function get_parent_id(){
        $uid = Auth::user()->id;
		if(Auth::user()->parent_id != 0){
			$uid = Auth::user()->parent_id;
		}
        return $uid;
    }
    
    public function sub_user_permissions()
    {
        return $this->hasOne('App\SubUserPermission','subuser_id','id');
    }

    public function sub_user_transactions()
    {
        return $this->hasOne('App\SubUserTransaction','sub_user_id','id');
    }

    public static function check_sub_user_permission($permission='',$next_order_amount=0) {
        if(!Auth::check()) {
            return false;
        }
        $logged_in_user = \Auth::user();
        if($logged_in_user->parent_id == 0) {
            return true;
        }
        $return = false;
        $sub_user_permission = SubUserPermission::where('subuser_id',$logged_in_user->id)->first();
        if(!is_null($sub_user_permission)) {
            if($permission == 'allow_selling') {
                $userObj = new User;
                if($sub_user_permission->is_seller_subuser == 1 && $userObj->is_premium_seller($logged_in_user->parent_id) == true) {
                    $return = true;
                }
            } else if($permission == 'can_make_purchases') {
                if($sub_user_permission->is_buyer_subuser == 1 && $sub_user_permission->can_make_purchases != 0) {
                    if($sub_user_permission->can_make_purchases == -1) { //allow unlimited
                        $return = true;
                    } else if($sub_user_permission->can_make_purchases > 0){ //check for monthly budget
                        $current_month = Carbon::now()->format('m');
                        $current_year = Carbon::now()->format('Y');
                        
                        $total_used_amount = SubUserTransaction::where('sub_user_id',$logged_in_user->id)->whereMonth('created_at',$current_month)->whereYear('created_at',$current_year)->sum('used_amount');
                        
                        if($next_order_amount == 0 && $total_used_amount < $sub_user_permission->can_make_purchases) {
                            $return = true;
                        } else if($next_order_amount > 0 && $total_used_amount + $next_order_amount <= $sub_user_permission->can_make_purchases) {
                            $return = true;
                        }
                    }
                }
            } else if($permission == 'can_start_order'){
                if($sub_user_permission->is_buyer_subuser == 1 && $sub_user_permission->can_start_order == 1) {
                    $return = true;
                }
            } else if($permission == 'can_communicate_with_seller'){
                $userObj = new User;
                if(($sub_user_permission->is_buyer_subuser == 1 && $sub_user_permission->can_communicate_with_seller == 1) || ($sub_user_permission->is_seller_subuser == 1 && $userObj->is_premium_seller($logged_in_user->parent_id) == true)) {
                    $return = true;
                }
            } else if($permission == 'can_use_wallet_funds'){
                if($sub_user_permission->is_buyer_subuser == 1 && $sub_user_permission->can_use_wallet_funds == 1) {
                    $return = true;
                }
            }
        }
        return $return;
    }

    public static function check_sub_user_permission_for_all() {
        if(!Auth::check()) {
            return [];
        }
        $logged_in_user = \Auth::user();
        if($logged_in_user->parent_id == 0) {
            return [
                'allow_selling' => true,
                'can_make_purchases' => true,
                'can_start_order' => true,
                'can_communicate_with_seller' => true,
                'can_use_wallet_funds' => true,
            ];
        }
        $return = [];
        $return['allow_selling'] = false;
        $return['can_make_purchases'] = false;
        $return['can_start_order'] = false;
        $return['can_communicate_with_seller'] = false;
        $return['can_use_wallet_funds'] = false;

        $sub_user_permission = SubUserPermission::where('subuser_id',$logged_in_user->id)->first();
        if(!is_null($sub_user_permission)) {
            /* check for allow_selling - start */
            $userObj = new User;
            if($sub_user_permission->is_seller_subuser == 1 && $userObj->is_premium_seller($logged_in_user->parent_id) == true) {
                $return['allow_selling'] = true;
            }
            /* check for allow_selling - end */

            /* check for can_make_purchases - start */
            if($sub_user_permission->is_buyer_subuser == 1 && $sub_user_permission->can_make_purchases != 0) {
                if($sub_user_permission->can_make_purchases == -1) { //allow unlimited
                    $return['can_make_purchases'] = true;
                } else if($sub_user_permission->can_make_purchases > 0){ //check for monthly budget
                    $return['can_make_purchases'] = true;
                }
            }
            /* check for can_make_purchases - end */

            /* check for can_start_order - start */
            if($sub_user_permission->is_buyer_subuser == 1 && $sub_user_permission->can_start_order == 1) {
                $return['can_start_order'] = true;
            }
            /* check for can_start_order - end */

            /* check for can_communicate_with_seller - start */
            if(($sub_user_permission->is_buyer_subuser == 1 && $sub_user_permission->can_communicate_with_seller == 1) || $sub_user_permission->is_seller_subuser == 1) {
                $return['can_communicate_with_seller'] = true;
            }
            /* check for can_communicate_with_seller - end */

            /* check for can_use_wallet_funds - start */
            if($sub_user_permission->is_buyer_subuser == 1 && $sub_user_permission->can_use_wallet_funds == 1) {
                $return['can_use_wallet_funds'] = true;
            }
            /* check for can_use_wallet_funds - end */
        }
        return $return;
    }

    public static function get_subuser_remaining_budget_message() {
        $logged_in_user = \Auth::user();
        $can_make_purchases = $logged_in_user->sub_user_permissions->can_make_purchases;
        $message = 'You have not sufficient monthly budget to purchase.';
        if($can_make_purchases > 0){ //check for monthly budget
            $current_month = Carbon::now()->format('m');
            $current_year = Carbon::now()->format('Y');
            
            $total_used_amount = SubUserTransaction::where('sub_user_id',$logged_in_user->id)->whereMonth('created_at',$current_month)->whereYear('created_at',$current_year)->sum('used_amount');

            $message = 'You have not sufficient monthly budget to purchase. You have used $'.$total_used_amount . ' from your monthly budget $'.$can_make_purchases .'.';
        }
        return $message;
    }
    
    /* Relation With Social Media */
    public function social_links()
    {
        return $this->hasOne(UserSocialLink::class,'user_id','id');
    }
    
    public static function is_soft_ban(){
        $uid = get_user_id();
        $user = User::select('soft_ban')->find($uid);
        $is_soft_ban = 0;
        if(!empty($user) && $user->soft_ban == 1){
            $is_soft_ban = 1;
        }
        return $is_soft_ban;
    }

    public function is_course_training_account($service=null){
        $is_training_course = false;
        if($service == null){
            if(Auth::check()){
                if(Auth()->user()->username == "demoTraining"){
                    $is_training_course = true;
                }else if(Auth()->user()->parent->username == "demoTraining"){
                    $is_training_course = true;
                }
            }
        }else{
            if($service->user->username == "demoTraining"){
                $is_training_course = true;
            }
        }
        return $is_training_course;
    }

    /* Relation to get user details */ 
    public function userDetails()
    {
        return $this->belongsTo('App\Models\UserDetail','id','user_id');
    }

    /* Relation to introduction video history */ 
    public function introductionVideoHistory()
    {
        return $this->belongsTo('App\Models\IntroductionVideoHistory','id','user_id');
    }
}
