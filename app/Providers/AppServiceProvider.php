<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Auth;
use App\Order;
use App\Service;
use App\Category;
use App\User;
use App\NewFeatureSetting;
use App\Notification;
use Carbon\Carbon;
use App\SaveTemplate;
use Validator;
use App\MessageDetail;
use App\PizzaAppliedHistory;
use App\Facades\ChatMessenger as Chatify;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        view()->composer('*', function ($view) {
            if (Auth::check()) {
                $this->uid = Auth::user()->id;
                $this->username = Auth::user()->username;
                $this->countOrderPurchaseService = 0;
                $this->countOrderPurchaseProfile = 0;

                /*get parent user ID*/
                if(Auth::user()->parent_id != 0){
                    $this->uid = Auth::user()->parent_id;
                    $this->username = Auth::user()->parent->username;
                }

                /*unable share affiliate link or not*/
                if(\Request::route() != null && \Request::route()->getName() == 'services_details'){
                    $seo_url = \Request::segment(2);
                    $objService = Service::select('id','uid')->where('seo_url', $seo_url)->first();
                    if(count($objService)){
                        if(Auth::user()->id == $objService->user->id){
                            $this->countOrderPurchaseService = 1;
                        }else{
                            $this->countOrderPurchaseService = Order::select('id')->where('uid',$this->uid)->where('seller_uid',$objService->uid)->count();
                        }
                    }
                }else if(\Request::route() != null && \Request::route()->getName() == 'viewuserservices'){
                    $username = \Request::segment(1);
                    $userObj = User::select('id')->where('username', $username)->where('status',1)->where('is_delete',0)->first();
                    if(count($userObj)){
                        if(Auth::user()->id == $userObj->id){
                            $this->countOrderPurchaseProfile = 1;
                        }else{
                            $this->countOrderPurchaseProfile = Order::select('id')->where('uid',$this->uid)->where('seller_uid',$userObj->id)->count();
                        }
                    }    
                }

                if(\Request::segment(1) == 'messaging' && \Request::ajax()){ 
                    
                    /*No need for message system*/
                    view()->share([
                        'parent_uid' => $this->uid, 
                        'parent_username' => $this->username,
                        'countOrderPurchaseService' =>  $this->countOrderPurchaseService,
                        'countOrderPurchaseProfile' =>  $this->countOrderPurchaseProfile,
                        'footer_category' => null,
                        'new_features' => null,
                        'NewOrder' => null,
                        'lateOrder' => null,
                        'recuringOrder' => null,
                        'recurring_order_for' => 'seller',
                        'ordersReviewCount' => 0
                    ]);
                }else{

                    $footer_category = Category::select('category_name','seo_url')->where('seo_url','!=','by-us-for-us')->get();
                    $new_features = NewFeatureSetting::where('status',1)->find(1);
                    $NewOrder = Notification::with('order', 'notifyby')->where(['type' => 'new_order', 'notify_to' => Auth::user()->id, 'is_read' => 0])->orderBy('id', 'desc')->first();

                    $uid = $this->uid;

                    $cdate = date('Y-m-d H:i:s');
                    $lateOrder = Order::select('id')->where('orders.status', 'active')->whereRaw("seller_uid = '{$uid}' AND ((delivered_date is null and end_date < '{$cdate}') OR (delivered_date is not null and delivered_date > end_date))")->first();
                    
                    $before3Days = Date("Y-m-d",strtotime(Carbon::now()->subDays(3)));

                    $recurring_order_for = 'seller';
                    $recuringOrder = Order::select('id')->whereIn('status', ['active','delivered','in_revision'])
                    ->whereHas('subscription',function($q) use ($before3Days){
                        $q->whereDate('expiry_date','>=',$before3Days)->select('id');
                    })
                    ->where('seller_uid', $uid)
                    ->where('is_recurring', 1)
                    ->first();

                    if(empty($recuringOrder)){
                        $recurring_order_for = 'buyer';
                        $recuringOrder = Order::select('id')->whereIn('status', ['active','delivered','in_revision'])
                        ->whereHas('subscription',function($q) use ($before3Days){
                            $q->whereDate('expiry_date','>=',$before3Days)->select('id');
                        })
                        ->where('uid', $uid)
                        ->where('is_recurring', 1)
                        ->first();
                    }

                    $ordersReviewCount = Order::select('id')->where('uid',$uid)
                    ->where('status','completed')
                    ->whereRaw('(completed_note is null AND seller_rating = 0)')
                    ->count();

                    $save_template_chat_popup = SaveTemplate::where('seller_uid',$uid)
                    ->where('template_for',2) //for Chat
                    ->orderBy('title', 'asc')
                    ->pluck('title', 'id')
                    ->toArray();

                    $unread_message_count = Chatify::countUnseenMessagesForUser($uid);
                    $token_for_pizza = PizzaAppliedHistory::whereDate('date',Carbon::today()->format('Y-m-d'))->where('user_id',0)->first();

                    $pizza_verification_code = '';
                    if(!is_null($token_for_pizza)) {
                        $pizza_verification_code = $token_for_pizza->verification_token;
                    }

                    $orderObj = new Order;
                    $pendingReviewForReviewEdition = $orderObj->get_pending_review_edition_order();
                     
                    view()->share([
                        'parent_uid' => $this->uid, 
                        'parent_username' => $this->username,
                        'countOrderPurchaseService' =>  $this->countOrderPurchaseService,
                        'countOrderPurchaseProfile' =>  $this->countOrderPurchaseProfile,
                        'footer_category' => $footer_category,
                        'new_features' => $new_features,
                        'NewOrder' => $NewOrder,
                        'lateOrder' => $lateOrder,
                        'recuringOrder' => $recuringOrder,
                        'recurring_order_for' => $recurring_order_for,
                        'save_template_chat_popup' => $save_template_chat_popup,
                        'ordersReviewCount' => $ordersReviewCount,
                        'unreadMessageCount' => $unread_message_count,
                        'pizza_verification_code' => $pizza_verification_code,
                        'pendingReviewForReviewEdition' => $pendingReviewForReviewEdition,
                    ]);
                }
                
            }
            else
            {
                $footer_category = Category::select('category_name','seo_url')->where('seo_url','!=','by-us-for-us')->get();
                $sessionCart =  \Session::get('cart');
                view()->share([
                    'footer_category' => $footer_category,
                    'sessionCart' => $sessionCart,
                ]);
            }
        });

        Validator::extend('recaptcha', 'App\\Validators\\ReCaptcha@validate');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
