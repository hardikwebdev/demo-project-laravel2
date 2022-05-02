<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Facades\ChatMessenger as Chatify;
use App\Jobs\SendEmail;
use App\Mail\NewChatMessage;
use App\SaveTemplate;
use App\SpamReport;
use ChristofferOK\LaravelEmojiOne\LaravelEmojiOne;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\MessageDetail;
use App\Notification;
use App\Message;
use App\Service;
use App\Order;
use App\User;
use Auth;
use AWS;
use Illuminate\Http\Response;
use Pusher\Pusher;
use Validator;
use App\Models\Admin;
use Emojione\Client;
use Emojione\Ruleset;
use Carbon\Carbon;

class MessagesController extends Controller
{
    private $uid,$client;

    public function __construct()
    {
        $this->client = new Client(new Ruleset());

        $this->middleware(function ($request, $next) {
            $this->uid = Auth::user()->id;
            $this->uid_secret = Auth::user()->secret;
            $this->uid_name = Auth::user()->Name;
            if (Auth::user()->parent_id != 0) {
                $this->uid = Auth::user()->parent_id;
                $parentUser = User::select('id')->find(Auth::user()->parent_id);
                $this->uid_secret = $parentUser->secret;
                $this->uid_name = $parentUser->Name;
            }
            return $next($request);
        });
    }

    /**
     * @param Request $request
     * @return \Illuminate\Support\Facades\Response
     */
    public function pusherAuth(Request $request)
    {
        $user = User::whereId($this->uid)->first();
        // Auth data
        $authData = json_encode([
            'user_id' => $user->secret,
            'user_info' => [
                'username' => $user->username
            ]
        ]);

        // check if user authorized
        if (Auth::check()) {
            return Chatify::pusherAuth(
                $request['channel_name'],
                $request['socket_id'],
                $authData
            );
        }
        // if not authorized
        return Response::json([
            'success' => false,
            'message' => 'Unauthorized',
            'code' => 401,
        ], 401);
    }


    public function send_message(Request $request)
    {
        $validator = Validator::make($request->all(), array(
            'user_id' => 'required',
            'message' => 'required_without:file',
            'file' => 'required_without:message',
            //'message_id' => 'required|exists:message,id',
        ), [
            'user_id.required' => "The user ID field is required",
            'message.required' => "The message field is required",
            'message_id.required' => "The message ID field is required",
            'message_id.exists' => "Please enter valid message ID",
        ]);

        if ($validator->fails()) {
            return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
        }

        $uid = $this->uid;
        $user_id = User::getDecryptedId($request->user_id);
        if($user_id == 0 || $user_id == '') {
            return response([
                'success' => false,
                'message' => "Invalid user ID.",
                'messages' => '',
                'code' => 400,
            ], 400);
        }
        if($uid == $user_id) {
            return response([
                'success' => false,
                'message' => "Invalid user ID.",
                'messages' => '',
                'code' => 400,
            ], 400);
        }

        //validate except admin as to user
        if($user_id != 1){
            /*Check user status*/
            $checkToUserStatus = User::select('id')->where('id',$user_id)->where('status',1)->where('is_delete',0)->first();
            if(!$checkToUserStatus){
                // send the response
                return response([
                    'success' => false,
                    'message' => "This user no longer available.",
                    'messages' => '',
                    'code' => 400,
                ], 400);
            }
            /*END Check user status*/

            /*Check Block Users*/ 
            $block_users = User::getBlockedByIds();
            if(in_array($user_id,$block_users)){
                return response([
                    'success' => false,
                    'message' => "You have blocked by user.",
                    'messages' => '',
                    'code' => 400,
                ], 400);
            }
        }
        
        /* $emoji = new LaravelEmojiOne;
        $reply_msg = $emoji->toShort($request->message); */
        $reply_msg = $request->message;
        $reply_msg_for_details = $reply_msg;

        // default variables
        $is_attachment = $service_id = $order_id = 0;
        $photo_s3_key = '';
        $file_name = $error_msg = $attachment = $attachment_title = null;
        $service_secret = $order_no = '';

        if ($request->filled('type') && $request->type == 'services' && $request->filled('service_id')) {
            $service_id = Service::getDecryptedId($request->service_id);
            $service_secret = Service::select('id')->where('id', $service_id)->first()->secret;
        } else if ($request->filled('type') && $request->type == 'orders' && $request->filled('order_id')) {
            $order = Order::where('order_no', $request->order_id)->select('id', 'order_no', 'service_id')->first();
            if (!is_null($order)) {
                $order_id = $order->id;
                $service_id = $order->service_id;
                $service_secret = $order->service->secret;
                $order_no = $request->order_id;
            }
        }

        // if there is attachment [file]
        if ($request->hasFile('file')) {
            // allowed extensions

            $file = $request->file('file');
            // if size less than 20MB
            if ($file->getSize() < 20971520) {
                $attachment = $request->file('file');
                $result = Chatify::storeAttachment($attachment);
                $reply_msg = 'Shared a file';
                $file_name = $attachment->getClientOriginalName();
                $photo_s3_key = $result['photo_s3_key'];
                $is_attachment = 1;
                $reply_msg_for_details = $result['file_url'];
            } else {
                $error_msg = "The file may not be greater than 20 mb.";
            }
        }

        if ($error_msg) {
            // send the response
            return response([
                'success' => false,
                'message' => $error_msg,
                'messages' => '',
                'code' => 400,
            ], 400);
        }

        // send to database
        $message = $message_details = [];
        if($request->message_id == 'undefined' || !$request->filled('message_id')) {
            $type = 'new';
        } else {
            $type = 'old';
            $msg_id = Message::getDecryptedId($request->message_id);
            $message['id'] = $msg_id;
        }

        if($this->check_for_share_service($reply_msg)) {
            $reply_msg = "Shared a service";
        }

        $message['service_id'] = $service_id;
        $message['order_id'] = $order_id;
        $message['from_user'] = $uid;
        $message['to_user'] = $user_id;
        $message['last_message'] = $reply_msg;
        if($request->filled('device_type') && $request->device_type == 'ios') {
            $message['platform'] = 1;
        } else if($request->filled('device_type') && $request->device_type == 'android') {
            $message['platform'] = 2;
        }
        $message_id = Chatify::newMessage($message, $type);

        $message_details['message'] = $reply_msg_for_details;
        $message_details['photo_s3_key'] = $photo_s3_key;
        $message_details['msg_id'] = $message_id;
        $message_details['from_user'] = $uid;
        $message_details['to_user'] = $user_id;
        $message_details['attachment'] = $is_attachment;
        $message_details['file_name'] = $file_name;
        if($request->filled('device_type') && $request->device_type == 'ios') {
            $message_details['platform'] = 1;
        } else if($request->filled('device_type') && $request->device_type == 'android') {
            $message_details['platform'] = 2;
        }
        $message_details_id = Chatify::newMessageDetail($message_details);

        // fetch message to send it with the response
        $messageData = $this->fetchMessage($message_details_id);
        
        $message_details = $this->get_message_details_object($message_id,$user_id);

        $newCreatedMessage = Message::find($message_id);

        if (!empty($newCreatedMessage) && $newCreatedMessage->is_admin == 1) { /*Send mail to admin*/
            //now sending from cron
            
            /* $sender = User::select('username')->find($uid);
            $messageDetails = nl2br($reply_msg);
            $link = env('ADMIN_PANEL_BASE_URL') . '/message/details/' . $newCreatedMessage->secret;
            $data = [
                'username' => "demo Support Team",
                'sender' => $sender->username,
                'messageDetails' => $messageDetails,
                'link' => $link,
                'subject' => 'New message from ' . $sender->username . ' on demo.com',
                'template' => 'frontend.emails.v1.new_message',
                'email_to' => env('HELP_EMAIL')
            ];
            SendEmail::dispatch($data, new NewChatMessage($data)); */
        } else {  /*Send mail to user */

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

            // send to user using pusher
            Chatify::push('private-chatify', 'messaging', [
                'from_id' => $this->uid_secret,
                'to_id' => $request->user_id,
                'order' => $order_no,
                'service' => $service_secret,
                'message' => Chatify::messageCard($messageData, 'default'),
                'conversation_id' => $newCreatedMessage->secret,
                'tab' => isset($request->type) ? $request->type : 'users',
                'message_detail' => $message_details,
                'is_conversation_block' => $is_conversation_block,
                'is_blocked' => $is_blocked
            ]);

            Chatify::push('private-chatify', 'client-contactItem', [
                'update_for' => $request->user_id,
                'update_to' => $this->uid_secret,
                'updating' => true,
                'msgid' => $newCreatedMessage->secret,
                'activetab' => isset($request->type) ? $request->type : 'users',
                'service' => strlen($service_secret) > 0 ? $service_secret : 0,
                'order' => strlen($order_no) > 0 ? $order_no : 0,
            ]);

            //send push notification
            $user = User::select('id', 'username', 'email', 'web_notification', 'notification', 'last_login_at')->find($user_id);
            if ($user->web_notification == 1) {
                $requesttype = isset($request->type) ? $request->type : 'users';
                $info = [
                    'id' => $user_id,
                    'from_user' => $this->uid_secret,
                    'to_id' => $user->secret,
                    'conversation_id' => $newCreatedMessage->secret,
                    'message' => $reply_msg,
                    'type' => $requesttype,
                    'service_id' => $service_secret,
                    'order_id' => $order_no
                ];
                Chatify::sendPushNotification($info);
            }
            /*Send email notification to admin*/

            //Send email notification to user
            if ($user->notification == "1") {

                /*begin : Check last mail read on*/
                $minutes = 0;
                $lastReadMessage = MessageDetail::select('updated_at')->where('is_read', 1)->where('is_admin', 0)->where('to_user', $user->id)->orderBy('updated_at', 'desc')->first();
                if (!empty($lastReadMessage)) {
                    $timeDiff = time() - strtotime($lastReadMessage->updated_at);
                    if ($timeDiff > 0) {
                        $minutes = $timeDiff / 60;
                    }
                }

                /*end : Check last mail read on*/
                if ($user->last_login_at) {
                    $is_logged_in_now = time() - strtotime($user->last_login_at) <= 600 ? true : false;
                } else {
                    $is_logged_in_now = false;
                }

                if ($is_logged_in_now == false || ($is_logged_in_now == true && $minutes > env('CHAT_TIME_INTERVAL_SEND_MAIL'))) {

                    $messageDetails = MessageDetail::find($message_details_id);
                    if (!empty($messageDetails)) {
                        $messageDetails->mail_send_status = 2;
                        $messageDetails->save();
                    }
                }
            }
        }

        // send the response
        return response([
            "success" => true,
            'message' => '',
            'message_detail' => $message_details,
            "code" => 200
        ], 200);
    }

    public function conversation_list(Request $request)
    {

        $limit = 10;
        $offset = 0;
        $request_type = 'users';
        $uid = $this->uid;

        if ($uid == null) {
            return response([
                "success" => false,
                "message" => "Something went wrong",
                "code" => 400
            ], 400);
        }

        if ($request->filled('limit')) {
            $limit = $request->limit;
        }
        if ($request->filled('offset')) {
            $offset = $request->offset;
        }

        // get all users that received/sent message from/to [Auth user]
        $conversations = Message::select('*');

        $conversations = $conversations->where(function ($q) use ($uid) {
            $q->where('to_user', $uid)->orWhere('from_user', $uid);
        });
        $conversations = $conversations->where('last_message','!=',"");

        if ($request->filled('type')) {
            $request_type = $request->type;
            if ($request_type == 'users') {
                $conversations = $conversations->where('service_id', 0)->where('order_id', 0);
            } else if ($request_type == 'services') {
                $conversations = $conversations->where('service_id', '!=', 0)->where('order_id', 0)
                ->whereHas('service',function($q){
                    $q->select('id');
                });
            } else if ($request_type == 'orders') {
                $conversations = $conversations->where('service_id', '!=', 0)->where('order_id', '!=', 0)
                ->whereHas('order',function($q){
                    $q->select('id');
                });
            }
        } else {
            $conversations = $conversations->where('service_id', 0)->where('order_id', 0);
        }

        if ($request->filled('search_text')) {
            $search_text = $request->search_text;
            if ($request_type == 'users') {
                $conversations = $conversations->where(function ($query) use ($search_text) {
                    $query->whereHas('toUser', function ($q) use ($search_text) {
                        $q->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%")->select('id');
                    })->orWhere(function ($q2) use ($search_text) {
                        //check specific keywords related to admin - in_arrayi is custom function from helper
                        if (in_arrayi($search_text)) {
                            $q2 = $q2->where('is_admin', 1);
                        } else {
                            $q2->whereHas('fromUser', function ($q3) use ($search_text) {
                                $q3->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%")->select('id');
                            });
                        }
                    });
                });
            } else if ($request_type == 'services') {
                $conversations = $conversations->where(function ($query) use ($search_text) {
                    $query->WhereHas('service', function ($q) use ($search_text) {
                        $q->where('title', 'LIKE', "%{$search_text}%")->select('id');
                    });

                    $query->orWhereHas('fromUser', function ($q3) use ($search_text) {
                        $q3->where('id', '!=', $this->uid);
                        $q3->where(function ($q4) use ($search_text) {
                            $q4->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%");
                        })->select('id');
                    });

                    $query->orWhereHas('toUser', function ($q3) use ($search_text) {
                        $q3->where('id', '!=', $this->uid);
                        $q3->where(function ($q4) use ($search_text) {
                            $q4->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%");
                        })->select('id');
                    });

                });
            } else if ($request_type == 'orders') {
                $conversations = $conversations->where(function ($query) use ($search_text) {
                    $query->whereHas('order', function ($q) use ($search_text) {
                        $q->where('order_no', 'LIKE', "%{$search_text}%")->select('id');
                    });

                    $query->orWhereHas('fromUser', function ($q3) use ($search_text) {
                        $q3->where('id', '!=', $this->uid);
                        $q3->where(function ($q4) use ($search_text) {
                            $q4->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%");
                        })->select('id');
                    });

                    $query->orWhereHas('toUser', function ($q3) use ($search_text) {
                        $q3->where('id', '!=', $this->uid);
                        $q3->where(function ($q4) use ($search_text) {
                            $q4->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%");
                        })->select('id');
                    });

                });
            }
        }

        $conversations = $conversations->orderBy('message.updated_at', 'desc')
            ->select('id', 'from_user', 'to_user', 'is_admin', 'last_message', 'created_at','updated_at', 'service_id', 'order_id')
            ->with('fromUser:id,Name,profile_photo,active_status', 'toUser:id,Name,profile_photo,active_status');
        $conversations = $conversations->withCount('unreadMessages as message_detail_count');
        $conversations = $conversations->offset($offset)->limit($limit)->get()
            ->each(function ($items) {
                $items->append('time');
            });

        foreach ($conversations as $key => $value) {
            $service_name = "";
            if($value->service) {
                $service_name = strlen($value->service->title) > 50 ? trim(substr($value->service->title,0,50)).'..' : $value->service->title;
            }
            $value->service_secret = $value->service ? $value->service->secret : "";
            $value->service_name = $service_name;
            $value->order_no = $value->order ? $value->order->order_no : "";
            $value->last_message = $this->display_message($value->last_message);
            $value->is_spam = $this->check_spam_report($uid, $value->id);
            $value->timestamp = Carbon::parse($value->updated_at)->timestamp;
            $value->type = $request_type;
            unset($value->service);
            unset($value->order);
            
            if($value->is_admin == 1) {
                $admin_profile = url('public/frontend/assets/img/logo/favicon.png');
                $admin = Admin::where('id',$value->from_user)->select('id')->first();
                $admin->profile_photo = $admin_profile;
                $admin->active_status = 0;
                if(!is_null($admin)) {
                    $value->from_admin = $admin;
                }
            }
        }

        return response([
            "success" => true,
            "message" => "",
            "conversations" => $conversations,
            "code" => 200
        ], 200);
    }

    public function seen(Request $request){
        $uid = $this->uid;
        if ($uid == null) {
            return response(["success" => false,"message" => "Invalid logged in user","code" => 400], 400);
        }
        $message_id = Message::getDecryptedId($request->message_id);

        if ($message_id == null) {
            return response([
                "success" => false,
                "message" => "Invalid message ID",
                "code" => 400
            ], 400);
        }

        $conversation = Message::select('id', 'from_user', 'to_user', 'is_admin', 'last_message', 'created_at', 'updated_at', 'service_id', 'order_id')
            ->where('id', $message_id)
            ->where(function ($q) use ($uid) {
                $q->where('from_user', $uid);
                $q->orWhere('to_user', $uid);
            })
            ->orderBy('id', 'desc')
            ->first();

        if (is_null($conversation)) {
            return response([
                "success" => false,
                "message" => "Invalid message ID",
                "code" => 400
            ], 400);
        }

        // make this conversation all messages as read
        if($uid == $conversation->to_user){
            $from_user = $conversation->from_user;
        }else{
            $from_user = $conversation->to_user;
        }

        Chatify::makeSeen($from_user,$message_id);
        
        return response([
            "success" => true,
            "message" => "",
            "code" => 200
        ], 200);

    }

    public function fetch_conversation(Request $request)
    {
        $uid = $this->uid;
        if ($uid == null) {
            return response(["success" => false,"message" => "Invalid logged in user","code" => 400], 400);
        }

        $offset = 0;
        $limit = 10;
        $is_blocked = 0;
        $is_conversation_block = 0;
        $message_id = Message::getDecryptedId($request->message_id);

        if ($request->filled('limit')) {
            $limit = $request->limit;
        }
        if ($request->filled('offset')) {
            $offset = $request->offset;
        }

        if ($message_id == null) {
            return response([
                "success" => false,
                "message" => "Invalid message ID",
                "code" => 400
            ], 400);
        }

        $conversation = Message::select('id', 'from_user', 'to_user', 'is_admin', 'last_message', 'created_at', 'updated_at', 'service_id', 'order_id')
            ->where('id', $message_id)
            ->where(function ($q) use ($uid) {
                $q->where('from_user', $uid);
                $q->orWhere('to_user', $uid);
            })
            ->orderBy('id', 'desc')
            ->first();

        if (is_null($conversation)) {
            return response([
                "success" => false,
                "message" => "Invalid message ID",
                "code" => 400
            ], 400);
        }

        // make this conversation all messages as read
        if($uid == $conversation->to_user){
            $from_user = $conversation->from_user;
        }else{
            $from_user = $conversation->to_user;
        }
        Chatify::makeSeen($from_user,$message_id);

        // fetch messages
        $query = MessageDetail::with(['fromUser:id,Name,profile_photo,active_status', 'toUser:id,Name,profile_photo,active_status'])
            ->select('id','msg_id', 'from_user', 'to_user', 'message', 'is_read', 'attachment', 'is_admin', 'created_at', 'file_name')
            ->where('msg_id', $message_id)
            ->where(function ($q) use ($uid) {
                $q->where('from_user', $uid);
                $q->orWhere('to_user', $uid);
            })
            ->orderBy('created_at', 'desc');

        $message_details = $query->offset($offset)
            ->limit($limit)
            ->get()
            ->each(function ($items) {
                $items->append('time');
            });

        foreach ($message_details as $key => $value) {
            $value->message = $this->display_message($value->message);
            $value->is_service_preview = false;
            $service_details = (object)[];
            if($this->check_for_share_service($value->message)) {
                $value->is_service_preview = true;
                $service_details = $this->get_service_details($value->message);
            }
            $value->service_details = $service_details;
            $value->timestamp = Carbon::parse($value->created_at)->timestamp;
        }
        
        /*Check Block user*/
		$block_users = User::getBlockedByIds();
        if(in_array($from_user,$block_users)){
            $is_conversation_block = 1;
        }
        
        /* Check to user is block or not*/
        $blockUser = User::isUserBlock($from_user,$this->uid);
        if($blockUser){
            $is_blocked = 1;
        }

        /*Check user status*/
        $is_user_available = 1;
        if($conversation->is_admin == 0){
            $checkToUserStatus = User::select('id')->where('id',$from_user)->where('status',1)->where('is_delete',0)->first();
            if(!$checkToUserStatus){
                $is_user_available = 0;
            }
        }
        /*END Check user status*/

        return response([
            "success" => true,
            "message" => "",
            "conversations" => $message_details,
            "service_id" => $conversation->service_id,
            "is_blocked" => $is_blocked,
            "is_conversation_block" => $is_conversation_block,
            "order_id" => $conversation->order_id,
            "is_spam" => $this->check_spam_report($uid, $conversation->id),
            'time' => $conversation->updated_at->diffForHumans(),
            "is_user_available" => $is_user_available,
            "code" => 200
        ], 200);
    }

    /**
     * Create a new conversation to database
     *
     * @param Request $request
     * @return JSON|Application|ResponseFactory|Response
     */
    public function createNewConversation(Request $request)
    {
        $uid = $this->uid;
        $user_id = User::getDecryptedId($request->user_id);
        if($user_id == 0 || $user_id == '') {
            return response([
                'success' => false,
                'message' => "Invalid user ID.",
                'messages' => '',
                'code' => 400,
            ], 400);
        }

        $service_id = $order_id = 0;
        $conv_type = 'users';
        $message_type = 'old';

        if ($request->filled('order_id') && $request->filled('service_id')) {
            $order = Order::where('order_no', $request->order_id)->select('id', 'order_no', 'service_id')->first();
            if (!is_null($order)) {
                $order_id = $order->id;
                $service_id = $order->service_id;
                $conv_type = 'orders';
            } else {
                return response([
                    "success" => false,
                    "message" => "Invalid order no.",
                    "code" => 400
                ], 400);
            }
        } else if ($request->filled('service_id')) {
            $service_id = Service::getDecryptedId($request->service_id);
            $conv_type = 'services';
        }

        if(($request->filled('service_id') || $request->filled('order_id')) && $service_id == 0){
            return response([
                "success" => false,
                "message" => "Something went wrong",
                "code" => 400
            ], 400);
        }

        $exist = Message::with('fromUser', 'toUser')->select('id', 'created_at', 'service_id', 'order_id')
            ->where('service_id', $service_id)
            ->where('order_id', $order_id)
            ->where(function ($q) use ($user_id, $uid) {
                $q->where(function ($q1) use ($user_id, $uid) {
                    $q1->where('from_user', $user_id)->where('to_user', $uid);
                })->orWhere(function ($q2) use ($user_id, $uid) {
                    $q2->where('from_user', $uid)->where('to_user', $user_id);
                });
            })->first();

        if (count($exist) == 0 && $uid != $user_id) {
            /* Check Blocked Users */
            $block_users = User::getBlockedByIds();
            if(in_array($user_id,$block_users)){
                // send the response
                return response([
                    'success' => false,
                    'message' => 'Your account is blocked by user.',
                    'messages' => '',
                    'code' => 400,
                ], 400);
            }

            $message['service_id'] = $service_id;
            $message['order_id'] = $order_id;
            $message['from_user'] = $uid;
            $message['to_user'] = $user_id;
            $message['last_message'] = '';
            if($request->filled('device_type') && $request->device_type == 'ios') {
                $message['platform'] = 1;
            } else if($request->filled('device_type') && $request->device_type == 'android') {
                $message['platform'] = 2;
            }
            $message_id = Chatify::newMessage($message, 'new');
            $message_type = 'new';
        } else {
            /* $exist->updated_at = date('Y-m-d H:i:s');
            $exist->save(); */

            $message_id = $exist->id;
        }

        $conversations = MessageDetail::with(['fromUser:id,Name,profile_photo,active_status', 'toUser:id,Name,profile_photo,active_status'])
            ->orderBy('created_at', 'desc')
            ->select('id','msg_id', 'from_user', 'to_user', 'message', 'is_read', 'attachment', 'is_admin', 'created_at', 'file_name')
            ->where('msg_id', $message_id)
            ->get()
            ->each(function ($items) {
                $items->append('time');
            });

        $message = Message::with(['fromUser:id,Name,profile_photo,active_status', 'toUser:id,Name,profile_photo,active_status'])->whereId($message_id)->select('id','from_user','to_user','service_id', 'order_id', 'created_at')->first();

        if ($message) {
            $time = $message->created_at->diffForHumans();
        } else {
            $time = null;
        }

        // send the response
        return response([
            "success" => true,
            "message" => '',
            "message_id" => $message_id,
            "message_secret" => $message->secret,
            "from_user" => $message->fromUser,
            "to_user" => $message->toUser,
            "message_type" => $message_type,
            "conversations" => $conversations,
            "service_id" => $message->service_id,
            "order_id" => $message->order_id,
            "time" => $time,
            "type" => $conv_type,
            "code" => 200
        ], 200);
    }

    function fetchMessage($id)
    {
        $attachment = $attachment_type = $attachment_title = $photo_s3_key = null;
        $is_attached = 0;
        $msg = MessageDetail::where('id', $id)->first();

        // If message has attachment
        if ($msg->attachment == 1) {
            // Get attachment and attachment title
            $attachment = $msg->message;
            $attachment_title = $msg->file_name;
            $photo_s3_key = $msg->photo_s3_key;

            // determine the type of the attachment
            $ext = pathinfo($attachment, PATHINFO_EXTENSION);
            $attachment_type = in_array($ext, Chatify::getAllowedImages()) ? 'image' : 'file';
            $message = null;
            $is_attached = 1;
        } else {
            $message = $msg->message;
            $is_attached = 0;
        }

        return [
            'id' => $msg->id,
            'message_id' => $msg->msg_id,
            'from_id' => $msg->fromUser->id,
            'from_name' => $msg->fromUser->Name,
            'from_username' => $msg->fromUser->username,
            'to_id' => $msg->toUser->id,
            'to_name' => $msg->toUser->Name,
            'to_username' => $msg->toUser->username,
            'message' => $message,
            'attachment' => [
                "attachment" => $attachment,
                "attachment_type" => $attachment_type,
            ],
            'time' => $msg->created_at->diffForHumans(),
            'fullTime' => $msg->created_at,
            'viewType' => ($msg->from_user == $this->uid) ? 'sender' : 'default',
            'seen' => $msg->is_read,
            'is_attached' => $is_attached,
            'platform' => $msg->platform
        ];
    }

    /* report conversation as spam */
    public function reportAsSpam(Request $request)
    {
        $validator = Validator::make($request->all(), array(
            'reason' => 'required',
            'msg_secret' => 'required'
        ));

        if ($validator->fails()) {
            return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
        }

        $id = Message::getDecryptedId($request->msg_secret);
        $uid = $this->uid;
        $from_user = $uid;

        try {
            if (empty($id)) {
                return response(["code" => 400,'success' => false, 'message' => 'Something went wrong',], 400);
            }
        } catch (\Exception $e) {
            return response(["code" => 400,'success' => false, 'message' => 'Something went wrong',], 400);
        }

        $message = Message::find($id);

        if (empty($message)) {
            return response(["code" => 400,'success' => false, 'message' => 'Conversation not found',], 400);
        }

        if ($message->is_admin == true || $message->is_admin == 1) {
            return response(["code" => 400,'success' => false, 'message' => 'You can not report as spam to admin',], 400);
        }

        $count = SpamReport::where('from_user', $uid)->where('conversion_id', $id)->count();

        if ($count != 0) {
            return response(["code" => 400,'success' => false, 'message' => 'Conversation already reported as spam',], 400);
        }

        if ($uid == $message->from_user) {
            $to_user = $message->to_user;
        } else {
            $to_user = $message->from_user;
        }

        $Spam = new SpamReport;
        $Spam->conversion_id = $message->id;
        $Spam->from_user = $from_user;
        $Spam->to_user = $to_user;
        $Spam->reason = $request->reason;
        $Spam->created_at = time();
        $Spam->updated_at = time();
        $Spam->save();

        return response(["code" => 200,'success' => true, 'message' => 'Conversation report as spam.',],200);
    }

    public function templates()
    {
        $uid = $this->uid;
        $save_template_chat_popup = SaveTemplate::where('seller_uid',$uid)
            ->where('template_for',2) //for Chat
            ->orderBy('title', 'asc')
            ->select('id','title','message')
            ->get()
            ->toArray();

        return response([
            "code" => 200,
            'success' => true,
            'templates' => $save_template_chat_popup
        ],200);
    }

    public function selectTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), array(
            'id' => 'required|exists:save_template,id',
        ));

        if ($validator->fails()) {
            return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
        }

        if(Auth::user()->is_premium_seller() == false && Auth::user()->parent_id == 0){
           
            return response([
                'success' => false,
                'message' => 'Something went wrong. You are not a premium user.',
                'code' => 400
            ], 400);
        }

        $template = SaveTemplate::select('id', 'title', 'message', 'template_for')->find($request->id);

        return response([
            'success' => true,
            'templates' => $template
        ]);
    }

    public function storeTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), array(
            'template_for' => 'required',
            'title' =>  'required|unique:save_template,title',
            'message' => 'required',
        ));

        if ($validator->fails()) {
            return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
        }

        if(Auth::user()->is_premium_seller() == false && Auth::user()->parent_id == 0){
           
            return response([
                'success' => false,
                'message' => 'Something went wrong. You are not a premium user.',
                'code' => 400
            ], 400);
        }
       
        $uid = $this->uid;

            $save_template = new SaveTemplate;
            $save_template->seller_uid = $uid;
            $save_template->title = $request->title;

            if($request->template_for == 1){
                $save_template->message = $request->message;
            }else{
                /*$emoji = new LaravelEmojiOne;
                $message = $emoji->toShort($request->template_data);
                $save_template->message =  convertToEmoji($message);*/
                $save_template->message =  br2newline(remove_emoji($request->message));
            }

            $save_template->template_for = $request->template_for;
            $save_template->save();
        
       
        return response([
            'code' => 200,
            'status' => true,
            'message' => 'Template saved',
            'templates' => [
               'id' => $save_template->id,
               'title' => $save_template->title,
               'message' => $save_template->message,
               'template_for' => $save_template->template_for
             ]
            ],200);
    }

    public function updateTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), array(
            'template_for' => 'required',
            'template_id' => 'required|exists:save_template,id',
            'message' => 'required'
        ));

        if ($validator->fails()) {
            return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
        }

        if(Auth::user()->is_premium_seller() == false && Auth::user()->parent_id == 0){
         
            return response([
                'success' => false,
                'message' => 'Something went wrong. You are not a premium user.',
                'code' => 400
            ], 400);
        }
          
            $uid = $this->uid;

            $id = $request->template_id;

            $save_template = SaveTemplate::select('id', 'title', 'message', 'template_for')->find($id);

            if($request->template_for == 1){
                $save_template->message = $request->$request->message;
            }else{
                $save_template->message =  br2newline( remove_emoji($request->message) );
            }

            $save_template->save();

        return response([
            'code' => 200,
            'status' => true,
            'message' => 'Template updated',
            'templates' => $save_template
        ],200);
    }

    public function display_message($msg) {
        $new_msg = $this->client->shortnameToUnicode($msg);
        if (strpos($new_msg, '&#') !== false) {
            $new_msg = str_replace("<","&lt;",$new_msg);
            $new_msg = str_replace(">","&gt;",$new_msg);
        }
        return $new_msg;
    }

    public function check_for_share_service($msg) {
        $result = false;
        if(substr($msg,0,14) == '[{@SERVICE_ID=' && substr($msg,strlen($msg) - 3,strlen($msg)) == '@}]') {
            $result = true;
        }
        return $result;
    }

    public function get_service_details($msg) {
        $service = [];
        $trimmed = str_replace('[{@SERVICE_ID=', '', $msg) ;
        $service_secret = str_replace('@}]', '', $trimmed) ;
        $service_id = Service::getDecryptedId($service_secret);
        
        if(is_null($service_id)) {
            return (object)$service;
        }
        
        $temp = Service::with('user','images','basic_plans')
                        ->select('id','uid','title','service_rating','total_review_count','seo_url')
                        ->find($service_id);
        if(is_null($temp)) {
            return (object)$service;
        }

        $image_url = url('public/frontend/assets/img/No-image-found.jpg');
        if(isset($temp->images[0])) {
            if($temp->images[0]->photo_s3_key != '') {
                $image_url = $temp->images[0]->media_url;
            } else {
                $image_url = url('public/services/images/'.$temp->images[0]->media_url);
            }
        }
        $service['image_url'] = $image_url;
        $service['title'] = display_title_for_api($temp->title, 30);
        $service['rating'] = $temp->service_rating;
        $service['total_review'] = $temp->total_review_count;
        $service['price'] = isset($temp->basic_plans->price)?$temp->basic_plans->price:'0.0';
        if(isset($temp->user->username) && isset($temp->seo_url)) {
            $service['service_url']  = route('services_details',[$temp->user->username,$temp->seo_url]);
        } else {
            $service['service_url']  = "";
        }
        return $service;
    }

    public function check_spam_report($uid, $msg_id) {
        $result = false;
        $count = SpamReport::where('from_user',$uid)->where('conversion_id',$msg_id)->count();
        if($count > 0) {
            $result = true;
        }
        return $result;
    }

    public function get_conversations_of_two_users(Request $request) {
        $limit = 10;
        $offset = 0;
        $uid = $this->uid;

        if ($uid == null) {
            return response([
                "success" => false,
                "message" => "Something went wrong",
                "code" => 400
            ], 400);
        }

        $user_id = User::getDecryptedId($request->user_id);
        if($user_id == 0 || $user_id == '') {
            return response([
                'success' => false,
                'message' => "Invalid user ID.",
                'messages' => '',
                'code' => 400,
            ], 400);
        }

        if ($request->filled('limit')) {
            $limit = $request->limit;
        }
        if ($request->filled('offset')) {
            $offset = $request->offset;
        }

        // get all users that received/sent message from/to [Auth user]
        $conversations = Message::select('*');

        $conversations = $conversations->where(function ($q) use ($uid) {
            $q->where('to_user', $uid)->orWhere('from_user', $uid);
        });
        $conversations = $conversations->where(function ($q) use ($uid,$user_id) {
            $q->where(function ($q1) use ($uid,$user_id) {
                $q1->where('from_user', $uid)->where('to_user', $user_id);
            })->orWhere(function ($q2) use ($uid,$user_id) {
                $q2->where('from_user', $user_id)->where('to_user', $uid);
            });
        });
        $conversations = $conversations->where('last_message','!=',"");

        if ($request->filled('search_text')) {
            $search_text = $request->search_text;
            $conversations = $conversations->where(function ($query) use ($search_text) {
                $query->whereHas('service', function ($q) use ($search_text) {
                    $q->where('title', 'LIKE', "%{$search_text}%")->select('id');
                })
                ->orWhereHas('order', function ($q) use ($search_text) {
                    $q->where('order_no', 'LIKE', "%{$search_text}%")->select('id');
                });
            });
        }

        $conversations = $conversations->orderBy('message.updated_at', 'desc')
            ->select('id', 'from_user', 'to_user', 'is_admin', 'last_message', 'created_at','updated_at', 'service_id', 'order_id')
            ->with('fromUser:id,Name,profile_photo,active_status', 'toUser:id,Name,profile_photo,active_status');
        $conversations = $conversations->withCount('unreadMessages as message_detail_count');
        $conversations = $conversations->offset($offset)->limit($limit)->get()
            ->each(function ($items) {
                $items->append('time');
            });

        foreach ($conversations as $key => $value) {
            $service_name = "";
            if($value->service) {
                $service_name = strlen($value->service->title) > 50 ? trim(substr($value->service->title,0,50)).'..' : $value->service->title;
            }
            $value->service_secret = $value->service ? $value->service->secret : "";
            $value->service_name = $service_name;
            $value->order_no = $value->order ? $value->order->order_no : "";
            $value->last_message = $this->display_message($value->last_message);
            $value->is_spam = $this->check_spam_report($uid, $value->id);
            $value->timestamp = Carbon::parse($value->updated_at)->timestamp;
            unset($value->service);
            unset($value->order);
            
            if(is_null($value->fromUser) && $value->is_admin == 1) {
                $admin_profile = url('public/frontend/assets/img/logo/favicon.png');
                $admin = Admin::where('id',$value->from_user)->select('id')->first();
                $admin->profile_photo = $admin_profile;
                $admin->active_status = 0;
                if(!is_null($admin)) {
                    $value->from_admin = $admin;
                }
            }

            $value->type = get_current_chat_tab($value->service_id,$value->order_id);
        }

        return response([
            "success" => true,
            "message" => "",
            "conversations" => $conversations,
            "code" => 200
        ], 200);
    }

    public function notifications(Request $request) {

        $uid = $this->uid;
        $offset = 0;
        $limit = 10;

        if ($request->filled('limit')) {
            $limit = $request->limit;
        }
        if ($request->filled('offset')) {
            $offset = $request->offset;
        }

        if ($uid == null) {
            return response([
                "success" => false,
                "message" => "Something went wrong",
                "code" => 400
            ], 400);
        }

        $notifications = Notification::with('order:id,order_no,seller_uid,uid,service_id,start_date,end_date,status,is_review,seller_rating,order_note,is_job,is_custom_order,order_total_amount,price','order.seller:id,Name,username','order.user:id,Name,username','order.service:id,title')
                            ->select('id', 'notify_to', 'notify_from', 'order_id', 'message', 'type', 'created_at', 'is_read','updated_at')
                            ->where('notify_to', $uid)
                            /* ->where('is_read', 0) */
                            ->where('type','!=','payment_failed')
                            ->where('is_delete', 0);
                            /* ->whereDate('created_at','>=',Carbon::now()->subDays(30)->format('Y-m-d')); */

        $notifications = $notifications->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()->each(function ($items) {
                $items->append('time');
                if(isset($items->order) && !is_null($items->order)) {
                    $items->order->append('OrderStatus');
                    $items->order->order_type = 'Service';
                    if($items->order->is_custom_order == 1) {
                        $items->order->order_type = 'Custom order';
                    } else if($items->order->is_job == 1) {
                        $items->order->order_type = 'Job';
                    }
                    if($items->order->order_total_amount != null) {
                        $items->order->price = $items->order->order_total_amount;
                    }
                } else {
                    $items->order->OrderStatus = "";
                }
            });

        foreach ($notifications as $key => $value) {
            if($value->type == 'custom_order') {
                unset($value->order);
                $value->order = null;
            }
        }

        return response([
            "success" => true,
            "message" => "",
            "notifications" => $notifications,
            "code" => 200
        ], 200);
    }

    public function delete_notification(Request $request) {
        $validator = Validator::make($request->all(), array(
            'id' => 'required',
        ));

        if ($validator->fails()) {
            return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
        }
        $notification = Notification::find($request->id);
        if(is_null($notification)) {
            return response(['success' => false, 'message' => "Invalid notification ID", "code" => 400], 400);
        }
        //update in database
        $notification->is_delete = 1;
        $notification->save();

        return response([
            "success" => true,
            "message" => "Notification deleted successfully",
            "code" => 200
        ], 200);
    }

    // private function
    public function get_message_details_object($msg_id,$user_id) {
        $uid = $this->uid;
        $message_details = MessageDetail::with(['fromUser:id,Name,profile_photo,active_status', 'toUser:id,Name,profile_photo,active_status'])
        ->select('id','msg_id', 'from_user', 'to_user', 'message', 'is_read', 'attachment', 'is_admin', 'created_at', 'file_name')
        ->where('msg_id', $msg_id)
        ->where(function ($q) use ($uid) {
            $q->where('from_user', $uid);
            $q->orWhere('to_user', $uid);
        })
        ->orderBy('created_at', 'desc')
        /* ->get(); // old code bk */
        ->first()->append('time');

        $message_details->is_service_preview = false;
        $service_details = (object)[];
        if($this->check_for_share_service($message_details->message)) {
            $message_details->is_service_preview = true;
            $service_details = $this->get_service_details($message_details->message);
        }
        $message_details->service_details = $service_details;
        $message_details->message = $this->display_message($message_details->message);
        $message_details->timestamp = Carbon::parse($message_details->created_at)->timestamp;
        $message_details->message_detail_count = Chatify::countUnseenMessagesForPusher($uid,$user_id,$msg_id);
        $message_details->order_no = '';
        $message_details->service_name = '';
        if(!is_null($message_details->messages->service)) {
            $message_details->service_name = $message_details->messages->service->title;
        }
        if(!is_null($message_details->messages->order)) {
            $message_details->order_no = $message_details->messages->order->order_no;
        }
        $message_details->type = get_current_chat_tab($message_details->messages->service_id,$message_details->messages->order_id);
        unset($message_details['messages']);
        return $message_details;
    }
}
