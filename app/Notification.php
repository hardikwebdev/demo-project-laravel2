<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Edujugon\PushNotification\PushNotification;
use App\User;

class Notification extends Model
{
    public $table  = 'notification';

    public function order()
    {
    	return $this->belongsTo('App\Order','order_id','id');	
    }
    public function notifyby()
    {
    	return $this->belongsTo('App\User','notify_from','id');
    }
    public function service()
    {
    	return $this->belongsTo('App\Service','order_id','id')->withoutGlobalScope('is_course');
    }
    
    protected static function boot() {
        static::created(function($notificationData) {
            if($notificationData->order_id != 0){
                $order_types = ['new_order','cancel_order','complete_order','delivered_order','extend_order_date','revision_request_order','dispute_order'];

                if(in_array($notificationData->type, $order_types))
                { 
                    $notify_to  = User::select('id')
                        ->where('id',$notificationData->notify_to)
                        ->where('order_notification',1)
                        ->where('is_delete',0)
                        ->where('status',1)
                        ->first();

                    $user_devices = UserDevice::where('user_id',$notificationData->notify_to)->select('id','device_token','device_type')->get();
                    
                    if($notify_to != null && $user_devices != null && count($user_devices) > 0)
                    {
                        $msg        = $notificationData->message; 
                        $title      = $notificationData->notifyby->Name;
                        $type       = $notificationData->type;
                        $order_id   = $notificationData->order_id;

                        $is_new_order = false;
                        if($notificationData->order) {
                            $info = app('App\Http\Controllers\Api\V1\OrderController')->get_order_detail($notificationData->order->id);
                            if(!empty($info) && $info->status == 'new'){
                                $is_new_order = true;
                            }
                        } else {
                            $info = (object)[];
                        }

                        $not_data = [];
                        $not_data['title'] = ($title != '') ? $title : 'New Notification';
                        $not_data['body'] = $msg;
                        $not_data['type'] = $type;
                        $not_data['order_id'] = $order_id;
                        $not_data['order'] = $info;
                        $not_data['is_new_order'] = $is_new_order;

                        $objNotification = new Notification;
                        $objNotification->send_fcm_notification($user_devices,$not_data);
                    }
                }
            }
        });
    }

    public function send_fcm_notification($user_devices,$data,$is_silient = 0) {
        $ios_devices = $android_devices = $payload_ios =  $payload_data_ios = $payload_android =  $payload_data_android = [];

        foreach ($user_devices as $key => $value) {
            if($value->device_type == 'ios') {
                array_push($ios_devices,$value->device_token);
            }
            if($value->device_type == 'android') {
                array_push($android_devices,$value->device_token);
            }
        }

        /* for ios devices */
        if(sizeof($ios_devices) > 0) {
            /* set sound */
            if($data['is_new_order'] == true){
                $sound = 'cash_register_01.wav';
            }else{
                $sound = 'notification_5.wav';
            }
            /* set payload */
            $payload_data_ios = [
                'title' => $data['title'],
                'body'  => $data['body'],
                'sound' => $sound,
                'type' => $data['type'],
                'order_id' => $data['order_id'],
                'order' => $data['order']
            ];

            $payload_data_ios['apns-collapse-id'] = $data['order_id'];

            if(isset($data['valid_until'])){
                $payload_data_ios['valid_until'] = $data['valid_until'];
            }

            if($is_silient == 0){
                $payload_ios = [
                    'notification' => $payload_data_ios,
                    'apns' => [
                        'headers' => [
                            'apns-collapse-id' => $data['order_id']
                        ]
                    ],
                ];
            }else{
                $payload_ios = [
                    'apns' => [
                        'headers' => [
                            'apns-collapse-id' => $data['order_id']
                        ]
                    ],
                ];

                $payload_ios['data'] = $payload_data_ios;
                $payload_ios['content_available'] = true;
                $payload_ios['priority'] = 'high';
                $payload_ios['apns']['headers']['apns-push-type'] = 'background';
                $payload_ios['apns']['headers']['apns-priority'] = 5;
            }
            
            try {
                $push = new PushNotification('fcm');
                $push->setMessage($payload_ios)
                ->setApiKey(env('NOTIFICATION_SERVER_KEY'))
                ->setDevicesToken($ios_devices)
                ->send();
                \Log::channel('notificationlog')->info('ios payload: '.json_encode($payload_ios));
                \Log::channel('notificationlog')->info('ios device token: '.json_encode($ios_devices));
                \Log::channel('notificationlog')->info('ios notification sent  '.json_encode($push->getFeedback()));
            } catch (\Exception $ex) {
                \Log::channel('notificationlog')->info('IOS - notification failed  '.json_encode($ex));
            }
        }

        /* for android devices */
        if(sizeof($android_devices) > 0) {
            /* set sound */
            if($data['is_new_order'] == true){
                $sound = 'cash_register_01.wav';
            }else{
                $sound = 'notification_5.wav';
            }
            /* set payload */
            $payload_data_android = [
                'title' => $data['title'],
                'body'  => $data['body'],
                'type' => $data['type'],
                'order_id' => $data['order_id'],
                'order' => $data['order']
            ];

            $payload_data_android['collapseKey'] = $data['order_id'];

            if(isset($data['valid_until'])){
                $payload_data_android['valid_until'] = $data['valid_until'];
            }

            $payload_android = [
                'data' => $payload_data_android
            ];

            try {
                $push = new PushNotification('fcm');
                $push->setMessage($payload_android)
                ->setApiKey(env('NOTIFICATION_SERVER_KEY'))
                ->setDevicesToken($android_devices)
                ->send();
                \Log::channel('notificationlog')->info('android payload: '.json_encode($payload_android));
                \Log::channel('notificationlog')->info('android device token: '.json_encode($android_devices));
                \Log::channel('notificationlog')->info('android notification sent  '.json_encode($push->getFeedback()));
            } catch (\Exception $ex) {
                \Log::channel('notificationlog')->info('Android - notification failed  '.json_encode($ex));
            }
        }
    }

    public function getTimeAttribute(){
        return date('d M Y H:i A',strtotime($this->updated_at));
    }
}
