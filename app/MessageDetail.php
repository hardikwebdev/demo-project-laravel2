<?php

namespace App;

use Edujugon\PushNotification\PushNotification;
use Illuminate\Database\Eloquent\Model;
use App\Message;
use App\User;
use Carbon\Carbon;
use App\Facades\ChatMessenger as Chatify;

class MessageDetail extends Model
{
    public $table  = 'message_details';

    //protected $fillable = ['msg_id','from_user','to_user','message','is_read','is_unread_first','attachment','file_name','photo_s3_key','is_admin','mail_send_status','platform'];

    public function fromUser() 
    {
        return $this->belongsTo('App\User','from_user','id');
    }
    public function toUser() 
    {
        return $this->belongsTo('App\User','to_user','id');
    }
    public function messages(){
    	return $this->hasOne('App\Message','id','msg_id');
    }

    public function fromAdmin() 
    {
        return $this->belongsTo('App\Models\Admin','from_user','id');
    }

    protected $appends = ['secret'];

    public function getSecretAttribute()
    {
        $encrypted_string=openssl_encrypt($this->id,config('services.encryption.type'),config('services.encryption.secret'));
        return base64_encode($encrypted_string);
    }
    public static function getDecryptedId($secret){
        return openssl_decrypt(base64_decode($secret),config('services.encryption.type'),config('services.encryption.secret'));
    }

    public function getTimeAttribute(){
        $time = $this->created_at->diffForHumans();
        return $time;
    }

    protected static function boot() {
        static::created(function($messageDetails) 
        {
            if(isset($messageDetails->to_user) && $messageDetails->to_user != ''){
                //$notify_to  = User::select('id','device_token','device_type','chat_notification')->find($messageDetails->to_user);
                $user_devices = UserDevice::where('user_id',$messageDetails->to_user)->select('id','device_token','device_type')->get();
                
                $block_users = User::isBlockMyProfile($messageDetails->to_user,$messageDetails->from_user);
                if($user_devices != null && count($user_devices) > 0 && $block_users == 0)
                {
                    $uid = get_user_id();

                    $message_details = MessageDetail::with(['fromUser:id,Name,profile_photo,active_status', 'toUser:id,Name,profile_photo,active_status,chat_notification'])
                    ->select('id','msg_id', 'from_user', 'to_user', 'message', 'is_read', 'attachment', 'is_admin', 'created_at', 'file_name')
                    ->where('id', $messageDetails->id)
                    ->whereHas('toUser', function ($q){
                        $q->where('is_delete',0)->where('status',1)->select('id');
                    })
                    ->orderBy('created_at', 'desc')
                    ->first()->append('time');

                    if($uid == $message_details->to_user){
                        $user_id = $message_details->from_user;
                    }else{
                        $user_id = $message_details->to_user;
                    }
            
                    $message_details->is_service_preview = false;
                    $service_details = (object)[];
                    if(app('App\Http\Controllers\Api\V1\MessagesController')->check_for_share_service($message_details->message)) {
                        $message_details->is_service_preview = true;
                        $service_details = app('App\Http\Controllers\Api\V1\MessagesController')->get_service_details($message_details->message);
                    }
                    $message_details->service_details = $service_details;
                    $message_details->message = app('App\Http\Controllers\Api\V1\MessagesController')->display_message($message_details->message);
                    $message_details->timestamp = Carbon::parse($message_details->created_at)->timestamp;
                    $message_details->message_detail_count = Chatify::countUnseenMessagesForPusher($uid,$user_id,$message_details->msg_id);
                    
                    if(isset($message_details->messages->order)) {
                        $message_details->order_no = $message_details->messages->order->order_no;
                    } else {
                        $message_details->order_no = '';
                    }

                    if(isset($message_details->messages->service)) {
                        $message_details->service_name = $message_details->messages->service->title;
                        $message_details->service_secret = $message_details->messages->service->secret;
                    } else {
                        $message_details->service_name = '';
                        $message_details->service_secret = '';
                    }
                    if($message_details->messages->service_id != 0 && $message_details->messages->order_id != 0) {
                        $message_details->type = "orders";
                    } else if($message_details->messages->service_id != 0 && $message_details->messages->order_id == 0) {
                        $message_details->type = "services";
                    } else {
                        $message_details->type = "users";
                    }

                    if($message_details != null && $message_details->is_admin != 1)
                    {

                        if($user_devices != null && count($user_devices) > 0)
                        {
                            $msg        = $message_details->message; 
                            if(check_for_share_service($msg)) {
                                $msg = "Shared a Service";
                            } else if($message_details->attachment == 1) {
                                $msg = "Shared a File";
                            }

                            $title      = $message_details->fromUser->Name;
                            $type       = "chat";
                            $conversation_id   = $message_details->messages->secret;
                            $msg = html_entity_decode($msg);
                            if(strlen($msg) > 10) {
                                //$msg = trim(substr($msg, 0, 10));
                            }

                            /*Check Block user*/
                            $is_conversation_block = $is_blocked = 0;
                            $block_users = User::getBlockedByIds();
                            if(in_array($user_id,$block_users)){
                                $is_conversation_block = 1;
                            }
                            /* Check to user is block or not*/
                            $blockUser = User::isUserBlock($user_id,$uid);
                            if($blockUser){
                                $is_blocked = 1;
                            }

                            $not_data = [];
                            $not_data['title'] = ($title != '') ? $title : 'New Notification';
                            $not_data['body'] = $msg;
                            $not_data['type'] = $type;
                            $not_data['conversation_id'] = $conversation_id;
                            $not_data['is_conversation_block'] = $is_conversation_block;
                            $not_data['is_blocked'] = $is_blocked;
                            //$not_data['conversation'] = $conversation;
                            $not_data['convo_id_int'] = $message_details->messages->id;
                            $not_data['chat_notification'] = $message_details->toUser->chat_notification;
                            unset($message_details->messages);
                            $not_data['message_detail'] = $message_details;

                            $objNotification = new MessageDetail;
                            $objNotification->send_fcm_notification($user_devices,$not_data);
                        }
                    }
                }
            }
        });
    }

    public function send_fcm_notification($user_devices,$data) {

        $ios_devices = $android_devices = $payload_ios =  $payload_data_ios = $payload_android =  $payload_data_android = [];

        foreach ($user_devices as $key => $value) {
            if($value->device_type == 'ios') {
                array_push($ios_devices,$value->device_token);
            }
            if($value->device_type == 'android') {
                array_push($android_devices,$value->device_token);
            }
        }

        //For ios 
        if(sizeof($ios_devices) > 0) {
            $sound = 'notification_6.wav';
            $payload_data_ios = [
                'title' => $data['title'],
                'body'  => $data['body'],
                'sound' => $sound,
                'type' => $data['type'],
                'conversation_id' => $data['conversation_id'],
                'message_detail' => $data['message_detail']
            ];

            $payload_data_ios['apns-collapse-id'] = $data['convo_id_int'];
            if($data['chat_notification'] == 0) {
                //$payload_data['content-available'] = true;
                //$payload_data['priority'] = 'high';
                //$payload_data['sound'] = '';

                $payload_ios = [
                    //'notification' => $payload_data,
                    'apns' => [
                        'headers' => [
                            'apns-collapse-id' => $data['convo_id_int']
                        ]
                    ],
                ];

                $payload_ios['data'] = $payload_data_ios;
                $payload_ios['content_available'] = true;
                $payload_ios['priority'] = 'high';
                $payload_ios['apns']['headers']['apns-push-type'] = 'background';
                $payload_ios['apns']['headers']['apns-priority'] = 5;
            }else{
                $payload_ios = [
                    'notification' => $payload_data_ios,
                    'apns' => [
                        'headers' => [
                            'apns-collapse-id' => $data['convo_id_int']
                        ]
                    ],
                ];
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

        //For android 
        if(sizeof($android_devices) > 0) {
            $sound = 'notification_6.wav';
            $payload_data_android = [
                'title' => $data['title'],
                'body'  => $data['body'],
                'type' => $data['type'],
                'conversation_id' => $data['conversation_id'],
                'message_detail' => $data['message_detail']
            ];

            $payload_data_android['collapseKey'] = $data['convo_id_int'];
            $payload_android = [
                'data' => $payload_data_android
            ];

            try {
                $push = new PushNotification('fcm');
                $push->setMessage($payload_android)
                ->setApiKey(env('NOTIFICATION_SERVER_KEY'))
                ->setDevicesToken($android_devices)
                ->send();
                \Log::channel('notificationlog')->info('payload: '.json_encode($payload_android));
                \Log::channel('notificationlog')->info('device token: '.json_encode($android_devices));
                \Log::channel('notificationlog')->info('notification sent  '.json_encode($push->getFeedback()));
            } catch (\Exception $ex) {
                \Log::channel('notificationlog')->info('Android - notification failed  '.json_encode($ex));
            }
        }
    }
}
