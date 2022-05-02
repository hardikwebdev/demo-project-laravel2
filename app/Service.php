<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\ServicePlan;
use App\Order;
use App\Specialaffiliatedusers;
use Auth;
use App\GeneralSetting;
use App\User;
use App\TempOrder;
use App\Models\ServiceRevision;

class Service extends Model
{

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('is_course', function (Builder $builder) {
            $builder->where('services.is_course', 0);
        });

		/* ************************
		Use without globle scop as below 
		Service::withoutGlobalScope('is_course')->first(); 
		***************************/
    }

    /*Use function without join only*/
    public function scopeStatusof($query, $for = 'service')
    {
        if ($for == 'service') {
            return $query->where(['is_private' => 0, 'is_job' => 0, 'is_custom_order' => 0, 'status' => 'active', 'is_approved' => 1, 'is_delete' => 0, 'is_course' => 0])
                ->whereHas('user', function ($query1) {
                    $query1->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
                });
        } elseif ($for == 'job') {
            return $query->where(['is_private' => 0, 'is_job' => 1, 'is_custom_order' => 0, 'status' => 'active', 'is_approved' => 1, 'is_delete' => 0, 'is_course' => 0])
                ->whereHas('user', function ($query1) {
                    $query1->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
                });
        }
    }

    
    public function images()
    {
        return $this->hasMany('App\ServiceMedia', 'service_id', 'id')->where('media_type', 'image')->orderBy('order_id', 'asc');
    }

    public function fbimages()
    {
        return $this->hasMany('App\ServiceMedia', 'service_id', 'id')->where('media_type', 'fb_image');
    }

    public function video()
    {
        return $this->hasMany('App\ServiceMedia', 'service_id', 'id')->where('media_type', 'video');
    }

    public function extra()
    {
        return $this->hasMany('App\ServiceExtra', 'service_id', 'id');
    }

    public function faqs()
    {
        return $this->hasMany('App\ServiceFAQ', 'service_id', 'id');
    }

    public function pdf()
    {
        return $this->hasMany('App\ServiceMedia', 'service_id', 'id')->where('media_type', 'pdf');
    }

    public function basic_plans()
    {
        return $this->belongsTo('App\ServicePlan', 'id', 'service_id')->where('plan_type', 'basic');
    }

    public function standard_plans()
    {
        return $this->belongsTo('App\ServicePlan', 'id', 'service_id')->where('plan_type', 'standard');
    }

    public function premium_plans()
    {
        return $this->belongsTo('App\ServicePlan', 'id', 'service_id')->where('plan_type', 'premium');
    }

    public function question_list()
    {
        return $this->hasMany('App\ServiceQuestion', 'service_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo('App\User', 'uid', 'id');
    }

    public function seller()
    {
        return $this->belongsTo('App\User', 'custom_order_seller_uid', 'id');
    }

    public function buyer()
    {
        return $this->belongsTo('App\User', 'custom_order_buyer_uid', 'id');
    }

    public function category()
    {
        return $this->belongsTo('App\Category', 'category_id', 'id')->withoutGlobalScope('type');
        //return $this->hasOne('App\SellerCategories','service_id','id')->where('is_default', true);
    }

    public function subcategory()
    {
        return $this->belongsTo('App\Subcategory', 'subcategory_id', 'id');
    }

    public function coupon()
    {
        return $this->hasMany('App\Coupan', 'service_id', 'id');
    }

    public function promoted_coupon()
    {
        return $this->hasOne('App\Coupan', 'service_id', 'id')->where('coupans.is_promo', '1')
		->where('coupans.is_delete', 0)
		->where('coupans.expiry_date','>=' , date('Y-m-d'))
		->whereRaw('coupans.no_of_uses > (SELECT count(*) FROM coupan_applied WHERE coupans.id = coupan_applied.coupan_code_id)');
    }

    public function order()
    {
        return $this->belongsTo('App\Order', 'id', 'service_id');
    }

    public function jobMedia()
    {
        return $this->hasMany('App\ServiceMedia', 'service_id', 'id');
    }

    public function favorite()
    {
        $query = $this->hasOne('App\FavoriteService', 'service_id', 'id');
        if (\Auth::check()) {
            $uid = get_user_id();
            $query = $query->where("user_id", $uid);
        } else {
            $query = $query->where("user_id", '0');
        }
        return $query;
    }

    public function volume_discount()
    {
        return $this->hasMany('App\VolumeDiscount', 'service_id', 'id')->orderBy('volume', 'ASC');
    }

    /*public function getPriceRange(){
        for($p=5;$p<=999;$p=$p+5){
            $price[$p] = "$".$p;
        }
        return $price;
    }*/
    /*public function getPriceDeliveryDays(){
        for($d=1;$d<=30;$d++){
            $delivery[$d] = $d." days Delivery";
        }
        return $delivery;
    }*/

    public function Spamcustomorder()
    {
        return $this->hasOne('App\Spamcustomorderdetails', 'service_id', 'id');
    }

    public function job_offers()
    {
        return $this->hasMany('App\JobOffer', 'service_id', 'id');
    }

    public function job_accept()
    {
        return $this->hasMany('App\JobOffer', 'service_id', 'id')->orderBy('is_promoted_job', 'desc');
    }

    public function job_bids()
    {
        return $this->hasMany('App\JobOffer', 'service_id', 'id')->where('is_hide', 0)->orderBy('is_promoted_job', 'desc')->orderBy('rating', 'desc');
    }

    public function job_accepted()
    {
        return $this->hasOne('App\JobOffer', 'service_id', 'id')->where('status', 'accepted');
    }

    public function seller_categories()
    {
        return $this->hasMany('App\SellerCategories', 'service_id', 'id');
    }

    // Begin : Course Relationship
    public function course_detail(){
        return $this->belongsTo('App\CourseDetail', 'id', 'course_id');
    }

    public function monthly_plans()
    {
        return $this->belongsTo('App\ServicePlan', 'id', 'service_id')->where('plan_type', 'monthly_access');
    }

    public function lifetime_plans()
    {
        return $this->belongsTo('App\ServicePlan', 'id', 'service_id')->where('plan_type', 'lifetime_access');
    }

    public function course_sections(){
        //If owner login than
            //show without approval
        //else
            // show approved only
            
        $uid = get_user_id();
        if(\Request::segment(1) == 'course-details-page'){ 
            return $this->hasMany('App\CourseSection', 'course_id', 'id')->orderBy('short_by','asc');
        }elseif($this->uid == $uid){
            // For preview course
            return $this->hasMany('App\CourseSection', 'course_id', 'id')->where('is_draft',0)->orderBy('short_by','asc');
        }else{
            return $this->hasMany('App\CourseSection', 'course_id', 'id')->where('is_draft',0)->where('is_approve',1)->orderBy('short_by','asc');
        }
    }

    public function downloadable_resources(){
        //If owner login than
            //show without approval
        //else
            // show approved only
        $uid = get_user_id();
        if(\Request::segment(1) == 'course-details-page'){ 
            return $this->hasMany(DownloadableContent::class, 'course_id', 'id')->orderBy('short_by','asc');
        }elseif($this->uid == $uid){
            return $this->hasMany(DownloadableContent::class, 'course_id', 'id')->where('is_draft',0)->orderBy('short_by','asc');
        }else{
            return $this->hasMany(DownloadableContent::class, 'course_id', 'id')->where('is_draft',0)->where('is_approve',1)->orderBy('short_by','asc');
        }
    }
    
    // End : Course Relationship

    public function subscription()
    {
        $date = date('Y-m-d', strtotime(' - 2 days'));

        return $this->hasOne('App\SubscribeUser', 'user_id', 'uid')
            //->where('is_cancel',0)
            ->whereDate('end_date', '>=', $date);
    }

    protected $appends = ['secret'];

    public function getSecretAttribute()
    {
        $encrypted_string = openssl_encrypt($this->id, config('services.encryption.type'), config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }

    public static function getDecryptedId($secret)
    {
        return openssl_decrypt(base64_decode($secret), config('services.encryption.type'), config('services.encryption.secret'));
    }

    /**get service by Id  */

    public static function getservices($id){
        $service = Service::where('id',$id)->first();
        return $service;
    }
    
    /**get service Plan by Id and service id  */

    public static function getServicePlan($id,$serviceId){
        $servicePlan = ServicePlan::where('id',$id)->where('service_id',$serviceId)->first();
        return $servicePlan;
    }
    /**get service all service id  */

    public static function getServiceAllPlan($serviceId){
        $servicePlan = ServicePlan::where('service_id',$serviceId)->get();
        return $servicePlan;
    }

    /**get Service Extra  **/

    public static function ServiceExtra($id){
        $ServiceExtra = ServiceExtra::where('id',$id)->first();
        return $ServiceExtra;
    }
    
    public function getTotalQueueOrdersCount(){
        $total_queue_orders = 0;
        if ($this->limit_no_of_orders > 0) {
            $total_queue_orders = Order::where('status', 'on_hold')->where('service_id', $this->id)->where('is_pause', 0)->count();
        }
        return $total_queue_orders;
    }

    public function getTotalActiveOrdersCount()
    {
        $total_active_orders = 0;
        if ($this->limit_no_of_orders > 0) {
            $total_active_orders = Order::whereIn('status', ['active', 'in_revision', 'delivered'])->where('service_id', $this->id)->where('is_pause', 0)->count();
        }
        return $total_active_orders;
    }

    public function allowBackOrder()
    {
        $allow_back_order = false;
        $allow_back_order_msg = '';
        $can_place_order = true;
        $total_active_orders = $this->getTotalActiveOrdersCount();

        if ($this->allow_backorders == 1 && $total_active_orders > 0 && ($this->limit_no_of_orders <= $total_active_orders)) {
            $allow_back_order = true;
            $allow_back_order_msg = '';
        }

        if ($this->limit_no_of_orders > 0 && $this->allow_backorders == 0 && $total_active_orders > 0 && ($this->limit_no_of_orders <= $total_active_orders)) {
            $can_place_order = false;
        }

        return (object)['allow_back_order' => $allow_back_order, 'allow_back_order_msg' => $allow_back_order_msg, 'can_place_order' => $can_place_order];

    }

    public function getExpectedDeliveredDays($plan = 'basic_plans')
    {

        /*************Plans*********
         * basic_plans
         * standard_plans
         * premium_plans
         ***************************/

        $total_active_orders = $this->getTotalActiveOrdersCount();
        $estimated_delivered_days = 0;
        if ($this->allow_backorders == 1 && $total_active_orders > 0 && ($this->limit_no_of_orders <= $total_active_orders)) {

            $orders_count = Order::select('id', 'status', 'delivery_days')
                ->whereIn('status', ['active', 'in_revision', 'delivered', 'on_hold'])
                ->where('service_id', $this->id)
                ->where('is_pause', 0)
                ->where('is_job', 0)
                ->where('is_recurring', 0)
                ->where('is_custom_order', 0)
                ->orderByRaw("FIELD(status , 'active','in_revision','delivered','on_hold') ASC")
                ->get();

            $index_to_check = $orders_count->count() + 1 - $this->limit_no_of_orders;

            while ($index_to_check > 0) {

                $order_lists = Order::select('id', 'status', 'delivery_days')
                    ->whereIn('status', ['active', 'in_revision', 'delivered', 'on_hold'])
                    ->where('service_id', $this->id)->where('is_pause', 0)
                    ->where('is_job', 0)
                    ->where('is_recurring', 0)
                    ->where('is_custom_order', 0)
                    ->orderByRaw("FIELD(status , 'active','in_revision','delivered','on_hold') ASC");

                if ($index_to_check > 0) {
                    $order_lists = $order_lists->skip($index_to_check - 1);
                }

                $order_lists = $order_lists->first();

                if (!empty($order_lists) > 0) {
                    $estimated_delivered_days += $order_lists->delivery_days;
                    if (!empty($order_lists->extra)) {
                        foreach ($order_lists->extra as $extra) {
                            $estimated_delivered_days += $extra->delivery_days;
                        }
                    }
                }
                $index_to_check = $index_to_check - $this->limit_no_of_orders;
            }

            /*current service plan delivery days*/
            $expected_delivery_of_current_plan = 0;
            if ($this->$plan) {
                $expected_delivery_of_current_plan = $this->$plan->delivery_days;
            }
            $estimated_delivered_days += ($expected_delivery_of_current_plan * 2);

        }

        $message = '';
        if ($estimated_delivered_days > 0) {
            $message = 'On Backorder: Estimated delivery ' . $estimated_delivered_days . ' days';
        }
        return (object)['estimated_delivered_days' => $estimated_delivered_days, 'estimated_delivered_days_msg' => $message];
    }

    public static function get_affiliate_discount($service)
    {
        $percentage = 0;
        $discount = 0;
        $amount = 0;
        $uid = Auth::user()->id;

        if ($service != null) {
            if (isset($service->basic_plans->price)) {
                $amount = $service->basic_plans->price;
            }

            /* Special Affiliate discount*/
            $specialAffiliateUser = Specialaffiliatedusers::select('id')->where('uid', $uid)->first();
            if ($specialAffiliateUser != null) {
                $discount = ($amount * 25) / 100;
                $percentage = 25;
            } elseif ($service->user->is_special_seller == 1) {
                /* Special Seller discount*/
                $discount = ($amount * 15) / 100;
                $percentage = 15;
            } else {
                /* Normal user discount*/
                $admin_charge = ($amount * env('ADMIN_CHARGE_PER')) / 100;
                $discount = (($amount - $admin_charge) * 15) / 100;
                $percentage = 15;
            }
        }
        return ['percentage' => $percentage, 'discount' => $discount];
    }

    public function getServiceTitleAttribute()
    {
        return display_title($this->title);
    }

    public static function getServiceAllPlanWithGreaterPrice($serviceId,$price,$currentPlanId){
        $servicePlan = ServicePlan::where('service_id',$serviceId)->where('price','>=',$price)->where('id','!=',$currentPlanId)->get();
        return $servicePlan;
    }

    public function latest_question()
    {
        return $this->hasOne('App\ServiceQuestion', 'service_id', 'id')->orderBy('updated_at','desc');
    }

    public function latest_media()
    {
        return $this->hasOne('App\ServiceMedia', 'service_id', 'id')->orderBy('updated_at','desc');
    }

    public function latest_faq()
    {
        return $this->hasOne('App\ServiceFAQ', 'service_id', 'id')->orderBy('updated_at','desc');
    }

    public function latest_service_extra()
    {
        return $this->hasOne('App\ServiceExtra', 'service_id', 'id')->orderBy('updated_at','desc');
    }

    //Its allow seller to add no of review edition on service create/edit
    public function get_no_of_review_editions(){
        $no_of_review_edition = 1;
        if(Auth::check()){
            $this->uid = Auth::user()->id;
            if(Auth::user()->parent_id != 0){
                $this->uid = Auth::user()->parent_id;
            }
            $is_subscribe = Auth::user()->is_premium_seller($this->uid);
            if($is_subscribe == true){
                $setting = GeneralSetting::select('settingvalue')->where('settingkey','premium_user_no_of_review_editions')->first();
                if(!empty($setting)){
                    $no_of_review_edition = $setting->settingvalue;
                }
            }else{
                $setting = GeneralSetting::select('settingvalue')->where('settingkey','free_user_no_of_review_editions')->first();
                if(!empty($setting)){
                    $no_of_review_edition = $setting->settingvalue;
                }
            }
        }
        return $no_of_review_edition;
    }

    //when order place / add to cart -> check service apply review edition or not
    public function is_allow_review_edition(){
        $allow = false;
        if($this->is_review_edition == 1 && $this->no_of_review_editions >= 1){
            $no_of_review_edition = $this->no_of_review_editions;
            
            // Check for subscription
            $userObj = new User;
            $is_subscribe = $userObj->is_premium_seller($this->uid);
            if($is_subscribe == false){
                // For normal user set it default 1
                $setting = GeneralSetting::select('settingvalue')->where('settingkey','free_user_no_of_review_editions')->first();
                if(!empty($setting)){
                    $no_of_review_edition = $setting->settingvalue;
                }
            }

            if($this->review_edition_count < $no_of_review_edition){
                if(Auth::check()){
                    $login_uid = User::get_parent_id();
                    // check buyer have previously purchased this review edition?
                    $purchased_review_edition_count = Order::select('id')->where('service_id',$this->id)
                    ->where('uid',$login_uid)->where('status','!=','cancelled')
                    ->where('is_review_edition',1)->count();
                    
                    if($purchased_review_edition_count == 0){
                        $allow = true;
                    }
                }else{
                    $allow = true;
                }
            }
        }       
        return $allow;
    }

    // get total no of review edition apply on specific servie
    public function get_total_review_editions(){
        return Order::select('id')->where('is_review_edition',1)->where('service_id',$this->id)->count();
    }

    public function get_total_re_processing_orders(){
        return TempOrder::select('id')->where('payment_status','Pending')->where('is_review_edition',1)->where('service_id',$this->id)->count();
    }

	/*Generate seo slug*/
    public static function generate_seo_slug($title=null,$slug=null){
		if($slug == null){
			$new_seo_url = Str::slug($title, '-');
		}else{
			$new_seo_url = $slug . '-' . time();
        }
		$exists_seo_url = Service::withoutGlobalScope('is_course')->where('seo_url', $new_seo_url)->select('id')->first();
		if (count($exists_seo_url)>0) {
			return Service::generate_seo_slug($title,$new_seo_url);
		}
		return $new_seo_url;
	}
    
    public static function purchaseCourseDetails__($service_id,$uid,$is_recurring=2){
        $order = Order::select('id','order_no','created_at','is_recurring','is_dispute','dispute_favour','status')
        ->where('is_course',1)->where('service_id',$service_id)->where('uid',$uid);
        
        if($is_recurring == 2){
            // Check One of plan purchased
            $order = $order->where(function($q){
                $q->where(function($q1){
                    $q1->where('status','completed')->where('is_recurring',0);
                });
                $q->orWhere(function($q1) {
                   // $q1->where('status','active')->where('is_recurring',1);
                   $q1->where('is_recurring',1);
                });
            });
        }elseif($is_recurring == 1){
            // Check monthly plan purchased
            $order = $order->where('is_recurring',1);
           // $order = $order->whereIn('status',['active','new'])->where('is_recurring',1);
        }else{
            // Check lifetime plan purchased
            $order = $order->where('status','completed')->where('is_recurring',0);
        }
        $order = $order->first();

        if(!empty($order) && $order->is_recurring == 1){
            if($order->subscription->is_cancel == 1){
                date_default_timezone_set('America/Los_Angeles');
                //Added expiry date + 1 day because we are renew subscription after 1 day of expiry date
                $end_date = date('Y-m-d',strtotime($order->subscription->end_date . "+1 days"));
                if(strtotime($end_date) < time()){
                    $order = null;
                }
                date_default_timezone_set('EST');
            }
        }

        return $order;
    }

    // User have NOT purchased this course than it will be available
    public static function purchaseCourseDetails($service_id,$uid,$is_recurring=2){
        $currentTime =  \Carbon\Carbon::now()->setTimezone('America/Los_Angeles')->timestamp;

        //Get Last Order
        $order = Order::select('id','order_no','created_at','is_recurring','is_dispute','dispute_favour','status')
        ->where('is_course',1)
        ->where('service_id',$service_id)
        ->where('uid',$uid)
        ->OrderBy('id','desc')->first();
        
        if(!empty($order)){
            if($is_recurring == 2){
                // Check One of plan purchased
                if($order->is_recurring == 0){
                    if($order->status != "completed"){
                        $order = null;
                    }
                }else{
                    if($order->status == "cancelled"){
                        $order = null;
                    }elseif($order->subscription->is_cancel == 1){
                        //Added expiry date + 1 day because we are renew subscription after 1 day of expiry date
                        //$expiry_date = date('Y-m-d',strtotime($order->subscription->expiry_date . "+1 days"));
                        $expiry_date = $order->subscription->expiry_date;
                        if(strtotime($expiry_date) < $currentTime){
                            $order = null;
                        }
                    }
                }
            }elseif($is_recurring == 1){
                // Check monthly plan purchased
                if($order->is_recurring == 1){
                    if($order->status == "cancelled"){
                        $order = null;
                    }elseif($order->subscription->is_cancel == 1){
                        //Added expiry date + 1 day because we are renew subscription after 1 day of expiry date
                        //$expiry_date = date('Y-m-d',strtotime($order->subscription->expiry_date . "+1 days"));
                        $expiry_date = $order->subscription->expiry_date;
                        if(strtotime($expiry_date) < $currentTime){
                            $order = null;
                        }
                    }
                }else{
                    $order = null;
                }
            }else{
                // Check lifetime plan purchased
                if($order->is_recurring == 0){
                    if($order->status != "completed"){
                        $order = null;
                    }
                }else{
                    $order = null;
                }
            }
        }

        return $order;
    }

    public function is_enable_course_monthly_plan(){
        $userObj = new User;
        $is_enable_monthly_plan = false;
        
        if($userObj->is_premium_seller($this->uid) && $this->course_detail->is_monthly_course == 1 && $this->monthly_plans->price > 0){
            $is_enable_monthly_plan = true;
        }

        //Check if lifetime plan purchased
        if(Auth::check()){
            $buyerId = get_user_id();
            //Check lifetime package is purchase
            $purchaseDetails = Service::purchaseCourseDetails($this->id,$buyerId,3);
            if(!empty($purchaseDetails)){
                $is_enable_monthly_plan = false;
            }
        }

        return $is_enable_monthly_plan;
    }

    /**
     * Get the service revision extras.
     */
    public function revision_extra(){
        return $this->hasMany('App\Models\ServiceExtraRevision', 'service_id', 'id');
    }
    /*
    * Relation with service revisions
    */ 
    public function revisions(){
        return $this->belongsTo(ServiceRevision::class, 'id', 'service_id');
    }

    /*
    * get Pending Service Count
    */ 
    public static function getPendingServiceCount($whereArray = [],$leftJoins = []){
        $query = Service::withoutGlobalScope('is_course')->select('services.id')
        ->where('services.is_delete',0)
        ->where(function($q) {
            $q->where('services.is_revision_approved',0);
            $q->orWhere('services.is_approved',0);
        });
        if(!empty($whereArray)){
            $query = $query->where($whereArray);
        }
        if(!empty($leftJoins)){
            foreach($leftJoins as $table){
                $query = $query->leftJoin($table['name'],$table['column'],'services.uid');
            }
        }
        return $query->count();
    }
}
