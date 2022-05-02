<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Notification;
use App\AimtellSubscriber;
use App\User;
use App\Jobs\PushNotification;
use Illuminate\Support\Str;
use App\CronDetail;

class SendNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send desktop notifications on user subscribe id using Aimtell. We will send notification on some events like new order, order delivered, order cancelled, order completed, payment failed. This script is running at every minute.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('==== Process - start =====');
        $cron_details_obj = new CronDetail;
        if($cron_details_obj->start('notification:send')) {
            $orderno = '';
            $link = env('APP_URL');
            $allowed_notification_type = ['new_order','delivered_order','payment_failed','complete_order','cancel_order'];
            $notifications = Notification::where('send_notification',false)->whereIn('type',$allowed_notification_type)->get();
            foreach ($notifications as $key => $notification) {
                $sub_list = AimtellSubscriber::where('user_id',$notification->notify_to)->pluck('subscriber_id');
                if(sizeof($sub_list) > 0) {
                    if($notification->type == 'new_order') {
                        $title = "New Order";
                    } else if($notification->type == 'delivered_order') {
                        $title = "Order Delivered";
                    } else if($notification->type == 'payment_failed') {
                        $title = "Payment Failed";
                    } else if($notification->type == 'complete_order') {
                        $title = "Order Completed";
                    } else if($notification->type == 'cancel_order') {
                        $title = "Order Cancelled";
                    } 

                    if(!is_null($notification->order)) {
                        $orderno = $notification->order->order_no;
                        $this->info('orderno :  '.$orderno);
                    }
                    if(strlen($orderno) > 0) {
                        if($notification->type == 'new_order') {
                            $link = route('seller_orders_details',$orderno);
                        } else if($notification->type == 'delivered_order' || $notification->type == 'complete_order') {
                            $link = route('buyer_orders_details',$orderno);
                        } else if($notification->type == 'payment_failed') {
                            $link = route('view_cart');
                        } else if($notification->type == 'cancel_order' && $notification->notify_by == 'buyer') {
                            $link = route('seller_orders_details',$orderno);
                        } else if($notification->type == 'cancel_order' && $notification->notify_by == 'seller') {
                            $link = route('buyer_orders_details',$orderno);
                        }
                    }
                    $user = User::where('id',$notification->notify_to)->first();
                    if($user->web_notification == 1){
                        
                        $encrypted_string=openssl_encrypt($link,config('services.encryption.type'),config('services.encryption.secret'));
                        $encript_link = base64_encode($encrypted_string);

                        $data = [
                            'subscriber_id' => $sub_list,
                            'message' => $notification->message,
                            'title' => $title,
                            'link' => route('redirect_notification')."?link=".$encript_link
                        ];
                        \Log::channel('notificationlog')->info('data  '.json_encode($data));
                        PushNotification::dispatch($data);
                    }
                    $notification->send_notification = true;
                    $notification->save();
                }
            }
            $cron_details_obj->end('notification:send');
        }
        $this->info('==== Process - end =====');
    }
}
