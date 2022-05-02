<?php

namespace App\Facades;

use Pusher\Pusher;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Message;
use App\MessageDetail;
use AWS;
use App\AimtellSubscriber;
use App\Jobs\PushNotification;
use App\Service;
use App\User;

class Chat
{
    private $uid;

    public function __construct(){
        $this->uid = Auth::user()->id;
        if(Auth::user()->parent_id != 0){
            $this->uid = Auth::user()->parent_id;
        }
    }

    /**
     * Allowed extensions to upload attachment
     * [Images / Files]
     *
     * @var
     */
    public static $allowed_images = array('png','jpg','jpeg','gif');
    public static $allowed_files  = array('zip','rar','txt','pdf','sql');

    /**
     * This method returns the allowed image extensions
     * to attach with the message.
     *
     * @return array
     */
    public function getAllowedImages(){
        return self::$allowed_images;
    }

    /**
     * This method returns the allowed file extensions
     * to attach with the message.
     *
     * @return array
     */
    public function getAllowedFiles(){
        return self::$allowed_files;
    }

    /**
     * Returns an array contains messenger's colors
     *
     * @return array
     */
    public function getMessengerColors(){
        return [
            '1' => '#2180f3',
            '2' => '#2196F3',
            '3' => '#00BCD4',
            '4' => '#3F51B5',
            '5' => '#673AB7',
            '6' => '#4CAF50',
            '7' => '#FFC107',
            '8' => '#FF9800',
            '9' => '#ff2522',
            '10' => '#9C27B0',
        ];
    }

    /**
     * Pusher connection
     */
    public function pusher()
    {
        return new Pusher(
            config('chatify.pusher.key'),
            config('chatify.pusher.secret'),
            config('chatify.pusher.app_id'),
            [
                'cluster' => config('chatify.pusher.options.cluster'),
                'useTLS' => config('chatify.pusher.options.useTLS')
            ]
        );
    }

    /**
     * Trigger an event using Pusher
     *
     * @param string $channel
     * @param string $event
     * @param array $data
     * @return void
     */
    public function push($channel, $event, $data)
    {
        return $this->pusher()->trigger($channel, $event, $data);
    }

    /**
     * Authintication for pusher
     *
     * @param string $channelName
     * @param string $socket_id
     * @param array $data
     * @return void
     */
    public function pusherAuth($channelName, $socket_id, $data = []){
        return $this->pusher()->socket_auth($channelName, $socket_id, $data);
    }

    /**
     * Fetch message by id and return the message card
     * view as a response.
     *
     * @param int $id
     * @return array
     */
    public function fetchMessage($id,$unread_first_msg_id = 0){
        $attachment = $attachment_type = $attachment_title = $photo_s3_key = null;
        $is_attached = 0;
        $msg = MessageDetail::where('id',$id)->first();

        // If message has attachment
        if($msg->attachment == 1){
            // Get attachment and attachment title
            //$att = explode(',',$msg->attachment);
            $attachment       = $msg->message;
            $attachment_title = $msg->file_name;
            $photo_s3_key = $msg->photo_s3_key;


            // determine the type of the attachment
            $ext = pathinfo($attachment, PATHINFO_EXTENSION);
            $attachment_type = in_array($ext,$this->getAllowedImages()) ? 'image' : 'file';
            $message = null;
            $is_attached = 1;
        } else {
            $message = $msg->message;
            $is_attached = 0;
        }

        return [
            'id' => $msg->secret,
            'from_secret' => $msg->fromUser->secret,
            'from_id' => $msg->from_user,
            'to_id' => $msg->to_user,
            'message' => $message,
            'attachment' => [$attachment, $attachment_title, $attachment_type,$photo_s3_key],
            'time' => $msg->created_at->diffForHumans(),
            'fullTime' => $msg->created_at,
            'viewType' => ($msg->from_user == $this->uid) ? 'sender' : 'default',
            'seen' => $msg->is_read,
            'is_attached' => $is_attached,
            'platform' => $msg->platform,

            //parameters for mark as unread message
            'is_unread_first' => ($msg->from_user == $this->uid) ? 0 : $msg->is_unread_first,
            //'from_unread' => ($unread_first_msg_id && $id >= $unread_first_msg_id ) ? 1 : 0,
            //'to_unread' => ($unread_first_msg_id && $id >= $unread_first_msg_id ) ? 1 : 0,
            'from_unread' => 0,
            'to_unread' => 0,
        ];
    }

    /**
     * Return a message card with the given data.
     *
     * @param array $data
     * @param string $viewType
     * @return void
     */
    public function messageCard($data, $viewType = null){
        $data['viewType'] = ($viewType) ? $viewType : $data['viewType'];
        $data['message_card'] = '';
        $data['service'] = null;
        
        if(substr($data['message'],0,14) == '[{@SERVICE_ID=' && substr($data['message'],strlen($data['message']) - 3,strlen($data['message'])) == '@}]') {
            $trimmed = str_replace('[{@SERVICE_ID=', '', $data['message']) ;
            $service_secret = str_replace('@}]', '', $trimmed) ;
            $service_id = Service::getDecryptedId($service_secret);
            $data['service'] = Service::with('user','images','basic_plans')
                            ->select('id','uid','title','service_rating','total_review_count','seo_url')
                            ->find($service_id);
            if(!is_null($data['service'])) {
                $data['message_card'] = 'send_service';
            }
        }
        return view('frontend.chatify.layouts.messageCard',$data)->render();
    }

    /**
     * Default fetch messages query between a Sender and Receiver.
     *
     * @param int $user_id
     * @return Collection
     */
    public function fetchMessagesQuery($user_id){
        $uid = $this->uid;
        return MessageDetail::where(function($q) use($user_id, $uid) {
            $q->where(function($q1) use($user_id, $uid) {
                $q1->where('from_user',$user_id)->where('to_user',$uid);
            })->orWhere(function($q2) use($user_id, $uid) {
                $q2->where('from_user',$uid)->where('to_user',$user_id);
            });
        });
    }

    /**
     * create a new message to database
     *
     * @param array $data
     * @return void
     */
    public function newMessage($data, $type='new'){   
        $message = null;
        //check for duplicate
        if($type == 'new') {
            $message = Message::where('service_id',$data['service_id'])
                    ->where('order_id',$data['order_id'])
                    ->where(function ($q) use ($data) {
                        $q->where(function ($q1) use ($data) {
                            $q1->where('from_user', $data['to_user'])->where('to_user', $data['from_user']);
                        })->orWhere(function ($q2) use ($data) {
                            $q2->where('from_user', $data['from_user'])->where('to_user', $data['to_user']);
                        });
                    })
                    ->first();
        }
        if($type == 'old' && isset($data['id'])) {
            $message = Message::find($data['id']);
        }
        if(is_null($message)) {
            $message = new Message();
            $message->service_id = $data['service_id'];
            $message->order_id = $data['order_id'];
            $message->from_user = $data['from_user'];
            $message->to_user = $data['to_user'];
        }
        if(isset($data['platform'])) {
            $message->platform = $data['platform'];
        }
        $message->last_message = $data['last_message'];
        $message->save();
        return $message->id;
    }

    /**
     * create a new message detail to database
     *
     * @param array $data
     * @return void
     */
    public function newMessageDetail($data){
        $main_is_admin = Message::select('is_admin')->find($data['msg_id']);

        $message_details = new MessageDetail();
        $message_details->message = $data['message'];
        $message_details->photo_s3_key = $data['photo_s3_key'];
        $message_details->from_user = $data['from_user'];
        $message_details->to_user = $data['to_user'];
        $message_details->msg_id = $data['msg_id'];
        $message_details->attachment = $data['attachment'];
        $message_details->file_name = $data['file_name'];
        if(isset($data['platform'])) {
            $message_details->platform = $data['platform'];
        }
        if($message_details->to_user == 1 && $main_is_admin->is_admin == 1) {
            $message_details->mail_send_status = 2;
        }
        $message_details->save();
        return $message_details->id;
    }

    /**
     * Make messages between the sender [Auth user] and
     * the receiver [User id] as seen.
     *
     * @param int $user_id
     * @return bool
     */
    public function makeSeen($user_id,$msg_id,$is_manualy_unread=0){
        if($is_manualy_unread == 1){
            // To user message seen
            return MessageDetail::Where('from_user',$user_id)
                ->where('to_user',$this->uid)
                ->where(function($query){
                    $query->where('is_read',0);
                    $query->orWhere('is_unread_first',1);
                })
                ->where('msg_id',$msg_id)
                ->update(['is_read' => 1,'is_unread_first' => 0]);
        }else{
            return MessageDetail::Where('from_user',$user_id)
                ->where('to_user',$this->uid)
                ->where('is_read',0)
                ->where('msg_id',$msg_id)
                ->update(['is_read' => 1]);
        }
    }

    /**
     * Get last message for a specific user
     *
     * @param int $user_id
     * @return Collection
     */
    public function getLastMessageQuery($user_id,$msg_id=''){
        return self::fetchMessagesQuery($user_id)->where('msg_id',$msg_id)->orderBy('created_at','DESC')->latest()->first();
    }

    /**
     * Count Unseen messages for conversation
     *
     * @param int $user_id
     * @return Collection
     */
    public function countUnseenMessages($user_id,$msg_id){
        return $this->countUnseenMessagesForPusher($user_id,$this->uid,$msg_id);
    }

    public function countUnseenMessagesForPusher($from_user,$to_user,$msg_id) {
        // Get total manually mark as unread message
        $manually_unread = MessageDetail::select('id')
                ->where('msg_id',$msg_id)
                ->where('from_user',$from_user)
                ->where('to_user',$to_user)
                ->where('is_unread_first',1)
                ->orderBy('id','desc')->first();

        if(!empty($manually_unread)){
            return MessageDetail::select('id')
            ->where('msg_id',$msg_id)
            ->where('from_user',$from_user)
            ->where('to_user',$to_user)
            ->where('id','>=',$manually_unread->id)
            ->count();
        }

        return MessageDetail::select('id')
            ->where('from_user',$from_user)
            ->where('to_user',$to_user)
            ->where('msg_id',$msg_id)
            ->where('is_read',0)
            ->count();
    }

    /**
     * Count Unseen messages for user
     *
     * @param int $user_id
     * @return Collection
     */
    public function countUnseenMessagesForUser($user_id){
        $checkManuallyUnreadMsgId = MessageDetail::where('to_user',$user_id)
        ->where('is_unread_first',1)
        ->groupBy('msg_id')
        ->pluck('msg_id')->toArray();

        $unreadMessageCount = 0;
        if(count($checkManuallyUnreadMsgId) > 0){
            foreach($checkManuallyUnreadMsgId as $msg_id){
                $manually_unread = MessageDetail::select('id')
                        ->where('msg_id',$msg_id)
                        ->where('to_user',$user_id)
                        ->where('is_unread_first',1)
                        ->orderBy('id','desc')->first();

                if(!empty($manually_unread)){
                    $unreadMessageCount += MessageDetail::select('id')
                    ->where('msg_id',$msg_id)
                    ->where('to_user',$user_id)
                    ->where('id','>=',$manually_unread->id)
                    ->count();
                } 
            }
            $unreadMessageCount += MessageDetail::where('to_user',$user_id)
            ->whereNotIn('msg_id',$checkManuallyUnreadMsgId)
            ->where('is_read',0)->count();
        }else{
            $unreadMessageCount = MessageDetail::where('to_user',$user_id)->where('is_read',0)->count();
        }
        return $unreadMessageCount;
    }

    /**
     * Get user list's item data [Contact Itme]
     * (e.g. User data, Last message, Unseen Counter...)
     *
     * @param int $messenger_id
     * @param Collection $user
     * @return void
     */
    public function getContactItem($messenger_id, $user,$msg_id='',$is_admin=0,$request_type='users',$api='get'){
        $service = $order = [];
        // get last message
        $lastMessage = self::getLastMessageQuery($user->id,$msg_id);
        if(is_null($lastMessage)) {
            $main = Message::select('id','updated_at','from_user')->find($msg_id);
            $last_msg = [];
            $last_msg['msg_id'] = $main->id;
            $last_msg['secret'] = $main->secret;
            $last_msg['created_at'] = $main->updated_at;
            $last_msg['from_user'] = $main->from_user;
            $last_msg['message'] = '';
            $last_msg['attachment'] = 0;
            $last_msg['platform'] = $main->platform;
            $lastMessage = (object) $last_msg;
        }
        
        // Get Unseen messages counter
        $unseenCounter = self::countUnseenMessages($user->id,$msg_id);

        // get service data
        if($request_type == 'services') {
            $msg = Message::select('id','service_id')->where('id',$msg_id)->first();
            $service = $msg->service;
        }

        // get order data
        if($request_type == 'orders') {
            $msg = Message::select('id','order_id')->where('id',$msg_id)->first();
            $order = $msg->order;
        }

        // get profie url
        if($is_admin == 1) {
            $profile_url = url('public/frontend/assets/img/logo/favicon.png');
        } else {
		    /* User Profile Picture */
            $profile_url = get_user_profile_image_url($user);
		    /* END User Profile Picture */
        }

        if(substr($lastMessage->message,0,14) == '[{@SERVICE_ID=' && substr($lastMessage->message,strlen($lastMessage->message) - 3,strlen($lastMessage->message)) == '@}]') {
            $lastMessage->attachment = 2;
        }

        return view('frontend.chatify.layouts.listItem', [
            'get' => $request_type,
            'user' => $user,
            'lastMessage' => $lastMessage,
            'unseenCounter' => $unseenCounter,
            'type'=>$request_type,
            'id' => $messenger_id,
            'profile_url' => $profile_url,
            'service' => $service,
            'order' => $order,
            'api' => $api,
        ])->render();
    }

    /**
     * Check if a user in the favorite list
     *
     * @param int $user_id
     * @return boolean
     */
    public function inFavorite($user_id){
        return Favorite::where('user_id', Auth::user()->id)
                        ->where('favorite_id', $user_id)->count() > 0
                        ? true : false;

    }

    /**
     * Make user in favorite list
     *
     * @param int $user_id
     * @param int $star
     * @return boolean
     */
    public function makeInFavorite($user_id, $action){
        if ($action > 0) {
            // Star
            $star = new Favorite();
            $star->id = rand(9,99999999);
            $star->user_id = Auth::user()->id;
            $star->favorite_id = $user_id;
            $star->save();
            return $star ? true : false;
        }else{
            // UnStar
            $star = Favorite::where('user_id',Auth::user()->id)->where('favorite_id',$user_id)->delete();
            return $star ? true : false;
        }
    }

    /**
     * Get shared photos of the conversation
     *
     * @param int $user_id
     * @return array
     */
    public function getSharedPhotos($user_id,$msg_id){
        $images = array(); // Default
        // Get messages
        $msgs = $this->fetchMessagesQuery($user_id)
                        ->where('msg_id',$msg_id)
                        ->where('attachment',1)
                        ->orderBy('created_at','DESC')->take(6);
        if($msgs->count() > 0){
            foreach ($msgs->get() as $msg) {
                // If message has attachment
                if($msg->attachment == 1){
                    $attachment = $msg->message; // Attachment
                    // determine the type of the attachment
                    in_array(pathinfo($attachment, PATHINFO_EXTENSION), $this->getAllowedImages())
                    ? array_push($images, $attachment) : '';
                }
            }
        }
        return $images;

    }

    /**
     * Delete Conversation
     *
     * @param int $user_id
     * @return boolean
     */
    public function deleteConversation($user_id){
        try {
            foreach ($this->fetchMessagesQuery($user_id)->get() as $msg) {
                // delete from database
                $msg->delete();
                // delete file attached if exist
                if ($msg->attachment) {
                    $path = storage_path('app/public/'.config('chatify.attachments.folder').'/'.explode(',', $msg->attachment)[0]);
                    if(file_exists($path)){
                        @unlink($path);
                    }
                }
            }
            return 1;
        }catch(Exception $e) {
            return 0;
        }
    }

    /**
     * Store file on aws
     *
     * @param file $attachment
     * @return array aws response array
     */
    public function storeAttachment($attachment) {
        $bucket = env('bucket_service');
        $file_url = uniqid() . '.' . $attachment->getClientOriginalExtension();
        $destinationPath = public_path('/conversations_attachment');
        $attachment->move($destinationPath, $file_url);
        $imageKey = '';

        try {
            $s3 = AWS::createClient('s3');
            $ext = $attachment->getClientOriginalExtension();
            $imageKey = md5($Message->id) . '/' . md5(time()) . '.' . $ext;
            $result_amazonS3 = $s3->putObject([
                'Bucket' => $bucket,
                'Key' => $imageKey,
                'SourceFile' => $destinationPath . '/' . $file_url,
                'StorageClass' => 'REDUCED_REDUNDANCY',
                'ACL' => 'public-read',
            ]);

            unlink($destinationPath . '/' . $file_url);

            $file_url = $result_amazonS3['ObjectURL'];
        } catch (Aws\S3\Exception\S3Exception $e) {
            echo "There was an error uploading the file.\n";
        }
        $result = ['file_url' => $file_url, 'photo_s3_key' => $imageKey];
        return $result;
    }

    /**
     * Send push notification
     *
     * @param array $data
     * @return boolean true
     */
    public function sendPushNotification($info) {
       /* $query_params = '?from=notification&type='.$info['type'].'&user_id='.$info['from_user'].'&service='.$info['service_id'].'&order='.$info['order_id'];
        $link = url('messaging/conversations').$query_params;
        $encrypted_string=openssl_encrypt($link,config('services.encryption.type'),config('services.encryption.secret'));
        $encript_link = base64_encode($encrypted_string);*/
        
        $link = route('msg_details',[$info['conversation_id']]);

        $sub_list = AimtellSubscriber::where('user_id',$info['id'])->pluck('subscriber_id');
        $data = [
            'subscriber_id' => $sub_list,
            'message' => $info['message'],
            'title' => 'New Message',
            //'link' => route('redirect_notification')."?link=".$encript_link
            'link' => $link
        ];
        PushNotification::dispatch($data);
    }
}
