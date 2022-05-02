<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use App\Facades\ChatMessenger as Chatify;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Message;
use App\MessageDetail;
use ChristofferOK\LaravelEmojiOne\LaravelEmojiOne;
use App\Models\Admin;
use App\Service;
use App\Order;
use App\Mail\NewChatMessage;
use App\Jobs\SendEmail;
use App\SpamReport;
use App\ServicePlan;
use App\BlockUser;
use Carbon\Carbon;
use Emojione\Client;
use Emojione\Ruleset;

class MessagesController extends Controller
{
    private $uid;

    public function __construct(){
        $this->client = new Client(new Ruleset());
        $this->middleware(function ($request, $next) {
            $this->uid = Auth::user()->id;
            $this->uid_secret = Auth::user()->secret;
            $this->uid_name = Auth::user()->Name;
            if(Auth::user()->parent_id != 0){
                $this->uid = Auth::user()->parent_id;
                $parentUser = User::select('id')->find(Auth::user()->parent_id);
                $this->uid_secret = $parentUser->secret;
                $this->uid_name = $parentUser->Name;
            }
            return $next($request);
        });
    }

    /**
     * Authinticate the connection for pusher
     *
     * @param Request $request
     * @return void
     */
    public function pusherAuth(Request $request)
    {
        // Auth data
        $authData = json_encode([
            'user_id' => Auth::user()->secret,
            'user_info' => [
                'username' => Auth::user()->username
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
        return new Response('Unauthorized', 401);
    }

    /**
     * Returning the view of the app with the required data.
     *
     * @param int $id
     * @return void
     */
    public function index($id = null,Request $request)
    {
        // get current route
        $route = (in_array(\Request::route()->getName(), ['users', config('chatify.path')]))
        ? 'users'
        : \Request::route()->getName();

        // prepare id
        if($id == 'conversations') {
            $view = 'frontend.chatify.pages.main';
        } else {
            $view = 'frontend.chatify.pages.app';
        }

        return view($view, [
            'id' => ($id == null) ? 0 : $route . '_' . $id,
            'route' => $route,
            'messengerColor' => Auth::user()->messenger_color,
            'dark_mode' => Auth::user()->dark_mode < 1 ? 'light' : 'dark',
        ]);
    }


    /**
     * Fetch data by id for (user/group)
     *
     * @param Request $request
     * @return collection
     */
    public function idFetchData(Request $request)
    {
        // Favorite
        //$favorite = Chatify::inFavorite($request['id']);

        // User data
        $message = null;
        if ($request['type'] == 'users' || $request['type'] == 'services' || $request['type'] == 'orders') {
            $is_admin = 0;
            if($request->filled('msg_id')) {
                $msg_id = Message::getDecryptedId($request->msg_id);
                $message = Message::select('id','is_admin','service_id','order_id','last_message')->find($msg_id);
                $is_admin = $message->is_admin;
            }
            if($is_admin == 1) {
                $user_id = Admin::getDecryptedId($request->id);
                $fetch = Admin::select('id','created_at','updated_at')->where('id', $user_id)->first();
                $user_profile_url = 'javascript:void(0)';
            } else {
                $user_id = User::getDecryptedId($request->id);
                $fetch = User::select('id','created_at','updated_at','profile_photo','photo_s3_key','Name','username','is_active','active_status','dark_mode','messenger_color')->where('id', $user_id)->first();
                if(!is_null($fetch) && isset($fetch->username)) {
                    $user_profile_url = route('viewuserservices',$fetch->username);
                } else {
                    $user_profile_url = 'javascript:void(0)';
                }
            }
        }

        // get profie url
        if($is_admin == 1) {
            $profile_url = url('public/frontend/assets/img/logo/favicon.png');
        } else {
            $profile_url = get_user_profile_image_url($fetch);
        }
        $msg_order_obj = $message->order;
        $new_convo='no';
        if($message->last_message == "") {
            $new_convo='yes';
        }
        $query_params = '?from=expand&type='.$request['type'].'&user_id='.$request->id.'&service='.$message->service->secret.'&order='.$msg_order_obj->order_no.'&new_convo='.$new_convo;
        $link = url('messaging/conversations').$query_params;

        $allow_upgrade_order = 'no';
        $details_link_text = $details_link = '';
        if($request['type'] == 'services') {
            if(!is_null($message->service)) {
                $details_link_text = Str::limit($message->service->title, 25);
                if(isset($message->service->user)) {
                    $details_link = route('services_details',[$message->service->user->username,$message->service->seo_url]);
                }
            } else {
                $details_link_text = $details_link = "";
            }
        } else if($request['type'] == 'orders') {
            $details_link_text = '#'.$msg_order_obj->order_no;
            if($msg_order_obj->uid == $this->uid) {
                $details_link = route('buyer_orders_details',$msg_order_obj->order_no);
            } else if($msg_order_obj->seller_uid == $this->uid) {
                $details_link = route('seller_orders_details', $msg_order_obj->order_no);
            }
            //check for upgrade order
            $allow_upgrade_order = allow_to_upgrade_order($msg_order_obj);
        }

        // send the response
        return Response::json([
            'favorite' => [],
            'fetch' => $fetch,
            'user_avatar' => $profile_url,
            'user_profile_url' => $user_profile_url,
            'last_message_time' => $message->latestMessage ? $message->latestMessage->created_at->diffForHumans() : '',
            'expand_link' => $link,
            'details_link_text' => $details_link_text,
            'details_link' => $details_link,
            'allow_upgrade_order' => $allow_upgrade_order
        ]);
    }

    /**
     * This method to make a links for the attachments
     * to be downloadable.
     *
     * @param string $fileName
     * @return void
     */
    public function download($fileName)
    {
        $path = storage_path() . '/app/public/' . config('chatify.attachments.folder') . '/' . $fileName;
        if (file_exists($path)) {
            return Response::download($path, $fileName);
        } else {
            return abort(404, "Sorry, File does not exist in our server or may have been deleted!");
        }
        
    }

    /**
     * Send a message to database
     *
     * @param Request $request
     * @return JSON response
     */
    public function send(Request $request)
    {
        $request->message = trim($request->message);

        if (!$request->hasFile('file') && strlen($request->message) == 0) {
            return Response::json([
                'status' => '200',
                'error' => 1,
                'error_msg' => 'Invalid message, Please try again.',
                'message' => '',
                'tempID' => $request['temporaryMsgId'],
            ]);
        }elseif($request->hasFile('file')){
            $validator = \Validator::make($request->all(), [
                'file' => 'required|mimes:doc,docx,csv,xlsx,xls,jpeg,png,jpg,gif,svg,pdf,txt,zip,rar'
            ]);

            if ($validator->fails()) {
                return Response::json([
                    'status' => '200',
                    'error' => 1,
                    'error_msg' => $validator->errors()->first(),
                    'message' => '',
                    'tempID' => $request['temporaryMsgId'],
                ]);
            }
        }

        $uid = $this->uid;
        $userid = User::getDecryptedId($request->id);
        $user_id = (int)$userid;

        //validate except admin as to user
        if($user_id != 1){
            /*Check user status*/
            $checkToUserStatus = User::select('id')->where('id',$user_id)->where('status',1)->where('is_delete',0)->first();
            if(!$checkToUserStatus){
                // send the response
                return Response::json([
                    'status' => '200',
                    'error' => 1,
                    'error_msg' => 'This user no longer available.',
                    'message' => '',
                    'tempID' => $request['temporaryMsgId'],
                ]);
            }
            /*END Check user status*/

            /* Check Block user*/
            $block_users = User::isBlockMyProfile($user_id,$this->uid);
            if($block_users){
                return Response::json([
                    'status' => '200',
                    'error' => 1,
                    'error_msg' => 'Something went wrong.',
                    'message' => '',
                    'tempID' => $request['temporaryMsgId'],
                ]);
            }
            /* End | Check Block user*/
        }
        
        $emoji = new LaravelEmojiOne;
        $reply_msg = $emoji->toShort($request->message); 
        // $reply_msg = convertToEmoji($reply_msg);
        $reply_msg_for_details = $reply_msg;
        // default variables
        $is_attachment = $service_id = $order_id = 0;
        $photo_s3_key = '';
        $file_name = $error_msg = $attachment = $attachment_title = null;
        $service_secret = $order_no = '';
        $reply_msg_for_details_file = '';
        $messageDataHtml = null;

        if($request->filled('type') && $request->type == 'services' && $request->filled('service_id')) {
            $service_id = Service::getDecryptedId($request->service_id);
            $service_secret = $request->service_id;
        } else if($request->filled('type') && $request->type == 'orders' && $request->filled('order_id')) {
            $order = Order::where('order_no',$request->order_id)->select('id','order_no','service_id')->first();
            if(!is_null($order)) {
                $order_id = $order->id;
                $service_id = $order->service_id;
                $service_secret = $order->service->secret;
                $order_no = $request->order_id;
            }
        }

        // if there is attachment [file]
        if ($request->hasFile('file')) {
            // allowed extensions
            /* $allowed_images = Chatify::getAllowedImages();
            $allowed_files  = Chatify::getAllowedFiles();
            $allowed        = array_merge($allowed_images, $allowed_files); */

            $file = $request->file('file');
            // if size less than 20MB
            if ($file->getSize() < 20971520) {
                /* if(in_array($file->getClientOriginalExtension(), $allowed)) { */
                    $attachment = $request->file('file');
                    $result = Chatify::storeAttachment($attachment);
                    //if text with file
                    if(strlen($request->message) > 0) {
                        $reply_msg_for_details_file = $result['file_url'];
                    } else {
                        $reply_msg = 'Shared a file';
                        $reply_msg_for_details = $result['file_url'];
                    }
                    $file_name = $attachment->getClientOriginalName();
                    $photo_s3_key = $result['photo_s3_key'];
                    $is_attachment = 1;
                /* } else {
                    $error_msg = "File extension not allowed!";
                } */
            } else {
                $error_msg = "The file may not be greater than 20 mb.";
            }
        }

        if (!$error_msg) {
            // send to database
            $message = $message_details = [];
            if($request->msg_id == 'undefined' || $request->msg_id == '') {
                $type = 'new';
            } else {
                $type = 'old';
                $msg_id = Message::getDecryptedId($request->msg_id);
                $message['id'] = $msg_id;
            }

            $message['service_id'] = $service_id;
            $message['order_id'] = $order_id;
            $message['from_user'] = $uid;
            $message['to_user'] = $user_id;
            $message['last_message'] = $reply_msg;
            $message['platform'] = 0;
            $message_id = Chatify::newMessage($message, $type);

            if($request->hasFile('file') && strlen($request->message) > 0 && strlen($reply_msg_for_details_file) > 0) {
                $message_details['message'] = $reply_msg_for_details_file;
                $message_details['photo_s3_key'] = $photo_s3_key;
                $message_details['msg_id'] = $message_id;
                $message_details['from_user'] = $uid;
                $message_details['to_user'] = $user_id;
                $message_details['attachment'] = $is_attachment;
                $message_details['file_name'] = $file_name;
                $message_details_id = Chatify::newMessageDetail($message_details);
                $photo_s3_key = '';
                $is_attachment = 0;
                $file_name = null;
                // fetch message to send it with the response
                $messageData = Chatify::fetchMessage($message_details_id);
                $messageDataHtml .= Chatify::messageCard(@$messageData);
            }

            $message_details['message'] = $reply_msg_for_details;
            $message_details['photo_s3_key'] = $photo_s3_key;
            $message_details['msg_id'] = $message_id;
            $message_details['from_user'] = $uid;
            $message_details['to_user'] = $user_id;
            $message_details['attachment'] = $is_attachment;
            $message_details['file_name'] = $file_name;
            $message_details_id = Chatify::newMessageDetail($message_details);

            // fetch message to send it with the response
            $messageData = Chatify::fetchMessage($message_details_id);
            $messageDataHtml .= Chatify::messageCard(@$messageData);

            $newCreatedMessage = Message::find($message_id);
            if(!empty($newCreatedMessage) && $newCreatedMessage->is_admin == 1){ /*Send mail to admin*/
                //now sending from cron

                /* $sender = User::select('username')->find($uid);
                $messageDetails = nl2br($emoji->toImage($reply_msg));
                //$admin = Admin::find($newCreatedMessage->to_user);
                $link = env('ADMIN_PANEL_BASE_URL').'/message/details/'.$newCreatedMessage->secret;
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

            }else{  /*Send mail to user */

                $user = User::select('id','username','email','web_notification','notification','last_login_at','last_message_read_on')->find($user_id);

                $message_details_for_api = MessageDetail::with(['fromUser:id,Name,profile_photo,active_status', 'toUser:id,Name,profile_photo,active_status'])
                    ->select('id','msg_id', 'from_user', 'to_user', 'message', 'is_read', 'attachment', 'is_admin', 'created_at', 'file_name')
                    ->where('msg_id', $message_id)
                    ->where(function ($q) use ($uid) {
                        $q->where('from_user', $uid);
                        $q->orWhere('to_user', $uid);
                    })
                    ->orderBy('created_at', 'desc')
                    /* ->get(); // old code bk */
                    ->first()->append('time');
            
                $message_details_for_api->is_service_preview = false;
                $service_details = (object)[];
                if(app('App\Http\Controllers\Api\V1\MessagesController')->check_for_share_service($message_details_for_api->message)) {
                    $message_details_for_api->is_service_preview = true;
                    $service_details = app('App\Http\Controllers\Api\V1\MessagesController')->get_service_details($message_details_for_api->message);
                }
                $message_details_for_api->service_details = $service_details;
                $message_details_for_api->message = app('App\Http\Controllers\Api\V1\MessagesController')->display_message($message_details_for_api->message);
                $message_details_for_api->timestamp = Carbon::parse($message_details_for_api->created_at)->timestamp;
                $message_details_for_api->message_detail_count = Chatify::countUnseenMessagesForPusher($this->uid,$user_id,$message_id);
                $message_details_for_api->order_no = $order_no;
                $message_details_for_api->service_name = '';
                if(!is_null($newCreatedMessage->service)) {
                    $message_details_for_api->service_name = $newCreatedMessage->service->title;
                }

                /*Check Block user*/
                $is_conversation_block = $is_blocked = 0;
                $block_users = User::getBlockedByIds();
                if(in_array($user_id,$block_users)){
                    $is_conversation_block = 1;
                }
                /* Check to user is block or not*/
                $blockUser = User::isUserBlock($user_id,$this->uid);
                if($blockUser){
                    $is_blocked = 1;
                }
                
                // send to user using pusher
                Chatify::push('private-chatify', 'messaging', [
                    'from_id' => $this->uid_secret,
                    'to_id' => $user->secret,
                    'conversation_id' => $newCreatedMessage->secret,
                    'tab' => $request->type,
                    'service' => $service_secret,
                    'order' => $order_no,
                    'message' => Chatify::messageCard($messageData, 'default'),
                    'message_detail' => $message_details_for_api,
                    'is_conversation_block' => $is_conversation_block,
                    'is_blocked' => $is_blocked // this for mobile app 
                ]);

                //send push notification
                if($user->web_notification == 1){
                    $requesttype = isset($request->type) ? $request->type : 'users';
                    $show_message = html_entity_decode($this->client->shortnameToUnicode($reply_msg));
                    $info = [
                        'id' => $user_id,
                        'from_user' => $this->uid_secret,
                        'message' => $show_message,
                        'type' => $requesttype,
                        'service_id' => $service_secret,
                        'order_id' => $order_no,
                        'conversation_id' => $newCreatedMessage->secret
                    ];
                    Chatify::sendPushNotification($info);
                }

                /*Send email notification to admin*/


                //Send email notification to user
                /*if ($user->notification == "1") {*/
                if ($user->notification == "1") {

                    /*begin : Check last mail read on*/
                    $minutes = 0;

                    /*$lastReadMessage = MessageDetail::select('updated_at')->where('is_read',1)->where('is_admin',0)->where('to_user',$user->id)->orderBy('updated_at','desc')->first();*/

                    if($user->last_message_read_on){
                        $timeDiff = time() - strtotime($user->last_message_read_on);
                        if($timeDiff > 0){
                            $minutes = $timeDiff / 60;
                        }
                    }

                    /*end : Check last mail read on*/
                    if($user->last_login_at){
                        $is_logged_in_now = time() - strtotime($user->last_login_at) <= 600 ? true : false;
                    }else{
                        $is_logged_in_now = false;
                    }
                    
                    if($is_logged_in_now == false || ($is_logged_in_now == true && $minutes > env('CHAT_TIME_INTERVAL_SEND_MAIL'))){

                        $messageDetails = MessageDetail::select('id')->find($message_details_id);
                        if(!empty($messageDetails)){
                            $messageDetails->mail_send_status = 2;
                            $messageDetails->save();
                        }
                    }
                }
            }
        }

        // send the response
        return Response::json([
            'status' => '200',
            'error' => $error_msg ? 1 : 0,
            'error_msg' => $error_msg,
            'message' => $messageDataHtml,
            'tempID' => $request['temporaryMsgId'],
        ]);
    }

    /**
     * Create a new conversation to database
     *
     * @param Request $request
     * @return JSON response
     */
    public function createNewConversation(Request $request) 
    {
        $uid = $this->uid;
        $userid = User::getDecryptedId($request->id);
        $user_id = (int)$userid;
        /* Check Blocked Users */
        $block_users = User::getBlockedByIds();
        if(in_array($user_id,$block_users)){
            // send the response
            return Response::json([
                'status' => '401',
                'is_block' => 1,
                'message' => 'You account is blocked by user.',
            ]);
        }

        $service_id = $order_id = 0;
        if($request->filled('service_id') && $request->service_id != '') {
            $service_id = Service::getDecryptedId($request->service_id);
        } else if($request->filled('order_id') && $request->order_id != '') {
            $order = Order::where('order_no',$request->order_id)->select('id','order_no','service_id')->first();
            if(!is_null($order)) {
                $order_id = $order->id;
                $service_id = $order->service_id;
            }
        }
        
        $exist = Message::select('id')->where('service_id',$service_id)->where('order_id',$order_id)
                    ->where(function($q) use($user_id, $uid) {
                        $q->where(function($q1) use($user_id, $uid) {
                            $q1->where('from_user',$user_id)->where('to_user',$uid);
                        })->orWhere(function($q2) use($user_id, $uid) {
                            $q2->where('from_user',$uid)->where('to_user',$user_id);
                        });
                    })->first();

        if(count($exist) == 0 && $uid != $user_id) {
            $message['service_id'] = $service_id;
            $message['order_id'] = $order_id;
            $message['from_user'] = $uid;
            $message['to_user'] = $user_id;
            $message['last_message'] = '';
            $message_id = Chatify::newMessage($message, 'new');
        }else{
            $exist->updated_at = date('Y-m-d H:i:s');
            $exist->save();
        }

        // send the response
        return Response::json([
            'status' => '200',
            'is_block' => 0,
            'message' => 'New conversation is created successfully.',
        ]);
    }
    

    /**
     * fetch [user/group] messages from database
     *
     * @param Request $request
     * @return JSON response
     */
    public function fetch(Request $request)
    {
        $msg_id = Message::getDecryptedId($request->msg_id);
        $user_id = User::getDecryptedId($request->id);
        $user = User::select('id','Name','is_delete','status')->find($user_id);
        $is_demo_user = false;
        $is_block = 0;
        $block_from_user = 0;
        $user_block_unblock_url = 'none';
        $is_admin = 1;
        if(isset($user)) {
            $is_admin = 0;
            if($user->Name == 'demo User' && $user->is_delete == 1) {
                $is_demo_user = true;
            }elseif($user->status == 0){
                $is_demo_user = true;
            }elseif($user->is_delete == 1){
                $is_demo_user = true;
            }
            
            /* Check to user is block or not*/
            $blockUser = User::isUserBlock($user->id,$this->uid);
            if($blockUser){
                $is_block = 1;
                /* Unblock to user url*/
                $user_block_unblock_url = route('unblock_user',$user->secret);
            }else{
                /* Block to user url*/
                $user_block_unblock_url = route('block_user',$user->secret);
            }

            /* Check from user is block or not*/
            $blockFrom = User::isUserBlock($this->uid,$user->id);
            if($blockFrom){
                $block_from_user = 1;
            }
        }
        // messages variable
        $allMessages = null;
        // fetch messages

        $per_page = 20;
        $scroll_chat_page = 1;
        if(isset($request->page) && $request->page == 1){
            $manually_unread = MessageDetail::select('id')
                ->where('to_user',$this->uid)
                ->where('is_unread_first',1)
                ->where('msg_id',$msg_id)->orderBy('id','desc')->first();

            if(!empty($manually_unread)){

                $manually_unread_count = MessageDetail::select('id')
                ->where('msg_id',$msg_id)->where('id','>=',$manually_unread->id)->count();
                
                //Add one extra page due to scroll works from middle
                $scroll_chat_page = round($manually_unread_count/20,0,PHP_ROUND_HALF_UP) + 1;
                $per_page = $scroll_chat_page * 20;
            }
        }

        $query = MessageDetail::select('id')->where('msg_id',$msg_id)->orderBy('message_details.id', 'desc');
        $total_count = $query->count();
        $messages = $query->paginate($per_page);
        $total_msg_count = $messages->count();

        // if there is a messages 
        if ($total_msg_count > 0 && $total_count > 0) {
            $index = $total_msg_count - 1;

            //Define manually first unread ID
            $unread_first_msg_id = (isset($manually_unread->id))?$manually_unread->id:0;

            for($i = $index; $i >= 0; $i--) {
                $allMessages .= Chatify::messageCard(
                    Chatify::fetchMessage($messages[$i]->id,$unread_first_msg_id)
                );
            }
            /* foreach ($messages as $message) {
                $allMessages .= Chatify::messageCard(
                    Chatify::fetchMessage($message->id)
                );
            } */
            // send the response
            return Response::json([
                'count' => $total_count,
                'messages' => $allMessages,
                'status' => 0,
                'is_demo_user' => $is_demo_user,
                'scroll_chat_page' => $scroll_chat_page,
                'user_block_unblock_url' => $user_block_unblock_url,
                'is_block' => $is_block,
                'block_my_profile' => $block_from_user,
                'is_admin' => $is_admin,
            ]);
        }

        if($request->page > 1) {
            $text = '';
        } else {
            $text = '<p class="message-hint"><span>Say \'hi\' and start messaging</span></p><br>';
        }
        
        // send the response
        return Response::json([
            'count' => $total_count,
            'messages' => $text,
            'status' => 1,
            'is_demo_user' => $is_demo_user,
            'scroll_chat_page' => $scroll_chat_page,
            'user_block_unblock_url' => $user_block_unblock_url,
            'is_block' => $is_block,
            'block_my_profile' => $block_from_user,
        ]);
    }

    /**
     *  
     * Private function to check unread message pagination
     * 
     */
    function get_unread_msg_page($msgs = array(),$page_limit,$total_count=0){
        if(count($msgs)>0){
            $page = 1;
            $key = 1;
            $is_first = 0;
            $allMessages = null;

            $total_msg_count = $msgs->count();
            if ($total_msg_count > 0 && $total_count > 0) {
                $index = $total_msg_count - 1;
                for($i = $index; $i >= 0; $i--) {
                        // dd($msgs[$i]->secret,$msgs);
                    $message_array[] = Chatify::messageCard(
                        Chatify::fetchMessage($msgs[$i]->id)
                    );
                    if($msgs[$i]->is_unread_first == 1){
                        $is_first = 1;
                        // dd($total_msg_count,$page,$key,$is_first,$page_limit);
                    }
                    
                    if($key > $page_limit){
                        if($is_first == 1){
                        // dd($total_msg_count,$page,$key,$is_first,$page_limit);
                            foreach(array_reverse($message_array) as $arrMsg){
                                $allMessages .=$arrMsg; 
                            }
                            $data['page'] = $page;
                            $data['allMessages'] = $allMessages;
                            return $data;
                        }
                        $page = $page+1; 
                        $key = 1;
                    }
                    if($total_msg_count <= $page_limit && $key == $total_msg_count || ($total_msg_count/$page_limit) == $page && $is_first == 1 && $i == 0){
                        // dd($total_msg_count,$page,$key,$is_first,$page_limit);
                        if($is_first == 1){
                        // dd($total_msg_count,$page,$key,$is_first,$page_limit);
                            foreach(array_reverse($message_array) as $arrMsg){
                                $allMessages .=$arrMsg; 
                            }
                            $data['page'] = $page;
                            $data['allMessages'] = $allMessages;
                            return $data;
                        }
                        $page = $page+1; 
                        $key = 1;
                    }
                    $key++;
                }
            }

            // foreach ($msgs as $value) {
            //     $allMessages .= Chatify::messageCard(
            //         Chatify::fetchMessage($value->id)
            //     );
            //     if($value->is_unread_first == 1){
            //         $is_first = 1;
            //                 // dd($total_msg_count,$page,$key,$is_first,$page_limit);
            //     }
            //     // Get page from start unread message
            //     if($key > $page_limit){
            //         if($is_first == 1){
            //             // dd($total_msg_count,$page,$key,$is_first,$page_limit);
            //             $data['page'] = $page;
            //             $data['allMessages'] = $allMessages;
            //             return $data;
            //         }
            //         $page = $page+1; 
            //         $key = 1;
            //     }elseif($total_msg_count <= $page_limit && $key == $total_msg_count || ($total_msg_count/$page_limit) == $page && $is_first == 1 && $i == 0){
            //         // dd($total_msg_count,$page,$key,$is_first,$page_limit);
            //         if($is_first == 1){
            //             $data['page'] = $page;
            //             $data['allMessages'] = $allMessages;
            //             return $data;
            //         }
            //     }
            //     $key++;
            // }
        }
                        dd('true',$msgs->toArray());
        
        return array();
    }

    /**
     * Make messages as seen
     *
     * @param Request $request
     * @return void
     */
    public function seen(Request $request)
    {
        $user_id = User::getDecryptedId($request->id);
        $msg_id = Message::getDecryptedId($request->msg_id);

        // make as seen
        $seen = Chatify::makeSeen($user_id,$msg_id,$request->is_manualy_unread);

        if($seen){
            $user = User::select('id')->find($this->uid);
            if(!empty($user)){
                $user->last_message_read_on = date('Y-m-d H:i:s');
                $user->save();
            }
        }

        //Get Total User Unread message
        $unread_message_count = Chatify::countUnseenMessagesForUser($this->uid);

        // send the response
        return Response::json([
            'status' => $seen,
            'unreadMessageCount' => $unread_message_count,
        ], 200);
    }

    /**
     * Get contacts list
     *
     * @param Request $request
     * @return JSON response
     */
    public function getContacts(Request $request)
    {
        //\DB::enableQueryLog();
        $uid = $this->uid;
        $request_type = 'users';
        $messenger_id = User::getDecryptedId($request->messenger_id);
        // get all users that received/sent message from/to [Auth user]
       /* $conversations = Message::where(function($query) {
            $query->whereHas('fromUser')->orWhereHas('fromAdmin');
            $query->whereHas('fromUser');
        })
        ->whereHas('toUser');*/

        // When chat open through mail or expand 
        if(isset($request->expand_data) && isset($request->type) && count($request->expand_data) > 0){
            $request_type = $request->type;
            if($request_type == 'users') {
                $user_id = User::getDecryptedId($request->expand_data['user']);
                Message::where(function($q) use($uid) {
                    $q->where('to_user', $uid)->orWhere('from_user', $uid);
                })->where('service_id',0)->where('order_id',0)->update(['updated_at'=>date('Y-m-d H:i:s')]);
            } else if($request_type == 'services') {
                $service_id = Service::getDecryptedId($request->expand_data['service']);
                Message::where(function($q) use($uid) {
                    $q->where('to_user', $uid)->orWhere('from_user', $uid);
                })->where('service_id',$service_id)->where('order_id',0)->update(['updated_at'=>date('Y-m-d H:i:s')]);
            } else if($request_type == 'orders') {
                $order_no = $request->expand_data['order'];
                $order = Order::select('id')->where('order_no',$order_no)->first();
               
                Message::where(function($q) use($uid) {
                    $q->where('to_user', $uid)->orWhere('from_user', $uid);
                })->where('service_id','!=',0)->where('order_id',$order->id)->update(['updated_at'=>date('Y-m-d H:i:s')]);
            }
        }

        $conversations = Message::select('*');
        if($request->filled('show_new_convo') && $request->show_new_convo == "true") {
            $conversations = $conversations->where(function($q) use($uid) {
                $q->where('to_user', $uid)->orWhere('from_user', $uid);
            });
        } else {
            $conversations = $conversations->where(function($q) use($uid) {
                $q->where('to_user', $uid)->orWhere('from_user', $uid);
            });
            $conversations = $conversations->where('last_message','!=',"");
        }

        /*$conversations = $conversations->where(function($q) use($uid) {
            $q->where('to_user', $uid)->orWhere('from_user', $uid);
        });*/

        if($request->filled('type')) {
            $request_type = $request->type;
            if($request_type == 'users') {
                $conversations = $conversations->where('service_id',0)->where('order_id',0);
            } else if($request_type == 'services') {
                $conversations = $conversations->where('service_id','!=',0)->where('order_id',0);
            } else if($request_type == 'orders') {
                $conversations = $conversations->where('service_id','!=',0)->where('order_id','!=',0);
            }
        }

        if($request->filled('search_text')) {
            $search_text = $request->search_text;
            if($request_type == 'users') {
                $conversations = $conversations->where(function($query) use($search_text) {
                    $query->whereHas('toUser',function($q) use($search_text) {
                        $q->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%")->select('id');
                    })->orWhere(function($q2) use($search_text) {
                        //check specific keywords related to admin - in_arrayi is custom function from helper
                        if(in_arrayi($search_text)) {
                            $q2 = $q2->where('is_admin',1);
                        } else {
                            $q2->whereHas('fromUser',function($q3) use($search_text) {
                                $q3->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%")->select('id');
                            })/* ->orWhereHas('fromAdmin',function($q4) use($search_text) {
                                $q4->whereRaw("demo Support Team = ?",['%'.$search_text.'%'])/* where('Name', 'LIKE', "%{$search_text}%");
                            }) */;
                        }
                    });
                });
            } else if($request_type == 'services') {
                $conversations = $conversations->where(function($query) use($search_text) {
                    $query->WhereHas('service',function($q) use($search_text) {
                        $q->where('title', 'LIKE', "%{$search_text}%")->select('id');
                    });

                    $query->orWhereHas('fromUser',function($q3) use($search_text) {
                        $q3->where('id','!=',$this->uid);
                        $q3->where(function($q4) use($search_text) {
                            $q4->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%");
                        })->select('id');
                    });

                    $query->orWhereHas('toUser',function($q3) use($search_text) {
                        $q3->where('id','!=',$this->uid);
                        $q3->where(function($q4) use($search_text) {
                            $q4->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%");
                        })->select('id');
                    });
                   /* $query->orWhereHas('service.user',function($q3) use($search_text) {
                        $q3->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%");
                    });*/

                });
            } else if($request_type == 'orders') {
                $conversations = $conversations->where(function($query) use($search_text) {
                    $query->whereHas('order',function($q) use($search_text) {
                        $q->where('order_no', 'LIKE', "%{$search_text}%")->select('id');
                    });

                    $query->orWhereHas('fromUser',function($q3) use($search_text) {
                        $q3->where('id','!=',$this->uid);
                        $q3->where(function($q4) use($search_text) {
                            $q4->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%");
                        })->select('id');
                    });

                    $query->orWhereHas('toUser',function($q3) use($search_text) {
                        $q3->where('id','!=',$this->uid);
                        $q3->where(function($q4) use($search_text) {
                            $q4->where('Name', 'LIKE', "%{$search_text}%")->orWhere('username', 'LIKE', "%{$search_text}%");
                        })->select('id');
                    });

                });
            }
        }

        $conversations = $conversations->orderBy('message.updated_at', 'desc')
        ->select('id','from_user','to_user','is_admin')
        ->paginate(10);
        //->limit(10)
        //->get();

       // \Log::info(\DB::getQueryLog());

        if (count($conversations) > 0) {
            // fetch contacts
            $contacts = null;
            foreach ($conversations as $chat) {
                if ($chat->from_user != $this->uid) {
                    if($chat->is_admin == 1) {
                        // Get user data
                        $userCollection = Admin::where('id', $chat->from_user)->select('id')->first();
                    } else {
                        // Get user data
                        $userCollection = User::where('id', $chat->from_user)->select('id','Name','username','profile_photo','photo_s3_key','active_status')->first();
                    }
                } else {
                    // Get user data
                    $userCollection = User::where('id', $chat->to_user)->select('id','Name','username','profile_photo','photo_s3_key','active_status')->first();
                }
                // Get all user data
                $contacts .= Chatify::getContactItem($messenger_id, $userCollection,$chat->id,$chat->is_admin,$request_type,'get');
            }
        }

        $status = 0;
        if(count($conversations) == 0 && $request->page > 1) {
            $text = '<br><p class="message-hint"><span>No more contacts</span></p>';
            $status = 1;
        } else {
            $text = '<br><p class="message-hint"><span>Your contact list is empty</span></p>';
        }

        // send the response
        return Response::json([
            'contacts' => count($conversations) > 0 ? $contacts : $text, 'status' => $status, 'current_tab' => $request->type
        ], 200);
    }

    /**
     * Update user's list item data
     *
     * @param Request $request
     * @return JSON response
     */
    public function updateContactItem(Request $request)
    {
        $user_id = User::getDecryptedId($request->user_id);
        $msg_id = Message::getDecryptedId($request->msg_id);
        $messenger_id = User::getDecryptedId($request->messenger_id);
        //get message details
        $chat = Message::select('id','from_user','to_user','is_admin')->find($msg_id);

        // Get user data
        if($chat->is_admin == 1) {
            $userCollection = Admin::where('id', $chat->from_user)->select('id')->first();
        } else {
            $userCollection = User::where('id', $user_id)->select('id','Name','profile_photo','photo_s3_key','active_status')->first();
        }
        $contactItem = Chatify::getContactItem($messenger_id, $userCollection,$chat->id,$chat->is_admin,$request['type'],'update');
        
        //Get Total User Unread message
        $unread_message_count = Chatify::countUnseenMessagesForUser($this->uid);

        // send the response
        return Response::json([
            'contactItem' => $contactItem,
            'unreadMessageCount' => $unread_message_count
        ], 200);
    }

    /**
     * Put a user in the favorites list
     *
     * @param Request $request
     * @return void
     */
    public function favorite(Request $request)
    {
        // check action [star/unstar]
        if (Chatify::inFavorite($request['user_id'])) {
            // UnStar
            Chatify::makeInFavorite($request['user_id'], 0);
            $status = 0;
        } else {
            // Star
            Chatify::makeInFavorite($request['user_id'], 1);
            $status = 1;
        }

        // send the response
        return Response::json([
            'status' => @$status,
        ], 200);
    }

    /**
     * Get favorites list
     *
     * @param Request $request
     * @return void
     */
    public function getFavorites(Request $request)
    {
        $favoritesList = null;
        $favorites = Favorite::where('user_id', Auth::user()->id);
        foreach ($favorites->get() as $favorite) {
            // get user data
            $user = User::where('id', $favorite->favorite_id)->first();
            $favoritesList .= view('frontend.chatify.layouts.favorite', [
                'user' => $user,
            ]);
        }
        // send the response
        return Response::json([
            'favorites' => $favorites->count() > 0
            ? $favoritesList
            : '',
        ], 200);
    }

    /**
     * Search in messenger
     *
     * @param Request $request
     * @return void
     */
    public function search(Request $request)
    {
        $getRecords = null;
        $input = trim(filter_var($request['input'], FILTER_SANITIZE_STRING));

        $records = User::where('Name', 'LIKE', "%{$input}%")->orWhere('username', 'LIKE', "%{$input}%");
        if($request->filled('search_type')) {
            if($request->search_type == 'users') {

            }
        }
        foreach ($records->get() as $record) {
            $getRecords .= view('frontend.chatify.layouts.listItem', [
                'get' => 'search_item',
                'type' => 'users',
                'user' => $record,
            ])->render();
        }
        // send the response
        return Response::json([
            'records' => $records->count() > 0
            ? $getRecords
            : '<p class="message-hint"><span>Nothing to show.</span></p>',
            'addData' => 'html'
        ], 200);
    }

    /**
     * Get shared photos
     *
     * @param Request $request
     * @return void
     */
    public function sharedPhotos(Request $request)
    {
        $user_id = User::getDecryptedId($request->user_id);
        $msg_id = Message::getDecryptedId($request->msg_id);
        //$shared = Chatify::getSharedPhotos($user_id,$msg_id);

        $shared = array(); // Default
        // Get messages
        $msgs = Chatify::fetchMessagesQuery($user_id)
                        ->where('msg_id',$msg_id)
                        ->where('attachment',1)
                        ->orderBy('created_at','DESC')
                        ->paginate(10);
        
        if($msgs->count() > 0){
            foreach ($msgs as $msg) {
                // If message has attachment
                if($msg->attachment == 1){
                    $attachment = $msg->message; // Attachment
                    // determine the type of the attachment
                    in_array(pathinfo($attachment, PATHINFO_EXTENSION), Chatify::getAllowedImages())
                    ? array_push($shared, $attachment) : '';
                }
            }
        }

        $sharedPhotos = null;
        $message = '';
        $status = 0;
        if(count($shared) == 0 && $request->page > 1) {
            $status = 1;
            $message = '<p class="message-hint"><span>No more shared</span></p>';
        }
        if(count($shared) == 0 && $request->page == 1) {
            $message = '<p class="message-hint"><span>Nothing shared yet</span></p>';
        }

        // shared with its template
        for ($i = 0; $i < count($shared); $i++) {
            $sharedPhotos .= view('frontend.chatify.layouts.listItem', [
                'get' => 'sharedPhoto',
                'image' => $shared[$i],
            ])->render();
        }
        // send the response
        return Response::json([
            'shared' => count($shared) > 0 ? $sharedPhotos : $message,
            'status' => $status,
        ], 200);
    }

    /**
     * Delete conversation
     *
     * @param Request $request
     * @return void
     */
    public function deleteConversation(Request $request)
    {
        // delete
        $delete = Chatify::deleteConversation($request['id']);

        // send the response
        return Response::json([
            'deleted' => $delete ? 1 : 0,
        ], 200);
    }

    public function updateSettings(Request $request)
    {
        $msg = null;
        $error = $success = 0;

        // dark mode
        if ($request['dark_mode']) {
            $request['dark_mode'] == "dark"
                ? User::where('id', Auth::user()->id)->update(['dark_mode' => 1])  // Make Dark
                : User::where('id', Auth::user()->id)->update(['dark_mode' => 0]); // Make Light
        }

        // If messenger color selected
        if ($request['messengerColor']) {
            $messenger_color = explode('-', trim(filter_var($request['messengerColor'], FILTER_SANITIZE_STRING)));
            $messenger_color = Chatify::getMessengerColors()[$messenger_color[1]];
            User::where('id', Auth::user()->id)
            ->update(['messenger_color' => $messenger_color]);
        }
        // if there is a [file]
        if ($request->hasFile('avatar')) {
            // allowed extensions
            $allowed_images = Chatify::getAllowedImages();

            $file = $request->file('avatar');
            // if size less than 150MB
            if ($file->getSize() < 150000000) {
                if (in_array($file->getClientOriginalExtension(), $allowed_images)) {
                // delete the older one
                    if (Auth::user()->avatar != config('chatify.user_avatar.default')) {
                        $path = storage_path('app/public/' . config('chatify.user_avatar.folder') . '/' . Auth::user()->avatar);
                        if (file_exists($path)) {
                            @unlink($path);
                        }
                    }
                // upload
                    $avatar = Str::uuid() . "." . $file->getClientOriginalExtension();
                    $update = User::where('id', Auth::user()->id)->update(['avatar' => $avatar]);
                    $file->storeAs("public/" . config('chatify.user_avatar.folder'), $avatar);
                    $success = $update ? 1 : 0;
                } else {
                    $msg = "File extension not allowed!";
                    $error = 1;
                }
            } else {
                $msg = "File extension not allowed!";
                $error = 1;
            }
        }

        // send the response
        return Response::json([
            'status' => $success ? 1 : 0,
            'error' => $error ? 1 : 0,
            'message' => $error ? $msg : 0,
        ], 200);
    }

    /**
     * Set user's active status
     *
     * @param Request $request
     * @return void
     */
    public function setActiveStatus(Request $request)
    {
        $user_id = User::getDecryptedId($request->user_id);
        $update = $request['status'] > 0
        ? User::where('id', $user_id)->update(['active_status' => 1])
        : User::where('id', $user_id)->update(['active_status' => 0]);
        // send the response
        return Response::json([
            'status' => $update,
            'active_status' => $request['status'] > 0 ? 1 : 0,
        ], 200);
    }

    public function get_message_notification_list_for_header(Request $request) {
        $uid = $this->uid;

        /*$ids = \DB::select("select * from message as main_msg WHERE main_msg.id=(select msg_id from (select * from message_details where msg_id=main_msg.id order by id desc limit 1) as md where md.to_user=".$uid.") order by updated_at desc limit 5");

        $conversation_ids = [];
        foreach($ids as $row) {
            array_push($conversation_ids,$row->id);
        }

        $conversations = Message::whereIn('id',$conversation_ids)->orderBy('updated_at','desc')->get();
        */

        $conversations = Message::whereHas('latestMessageChat',function($q){
            $q->select('id');
        })
        ->where(function($q) use ($uid){
            $q->where('from_user',$uid)->orWhere('to_user',$uid);
        });
        
        /* Check Blocked Users */
        $block_users = User::getBlockedByIds();
        if(count($block_users)>0){
            $conversations = $conversations->whereNotIn('from_user',$block_users);
        }
        
        $conversations = $conversations->where('last_message','!=',"")
        ->select('id','from_user','to_user','is_admin','order_id','service_id','updated_at')
        ->orderBy('updated_at','desc')->take(5)
        ->get();

        $html = view('frontend.chatify.layouts.header_message_list', ['messages' => $conversations,'uid' => $uid])->render();

        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        // send the response
        return Response::json([
            'status' => 'success',
            'html' => $html,
        ], 200);
    }

    /* report conversation as spam */
    public function report_as_spam(Request $request) {
        if(!$request->filled('msg_secret') || ($request->filled('msg_secret') && strlen($request->msg_secret) == 0)) {
            // send the response
            return Response::json(['status' => 'error','message' => 'Something went wrong',], 200);
        }
        $id = Message::getDecryptedId($request->msg_secret);
        try{
            if(empty($id)){
                return Response::json(['status' => 'error','message' => 'Something went wrong',], 200);
            }
        }catch(\Exception $e){
            return Response::json(['status' => 'error','message' => 'Something went wrong',], 200);
        }

        $Message = Message::find($id);
        if(empty($Message)){
            return Response::json(['status' => 'error','message' => 'Conversation not found',], 200);
        }

        $uid = $this->uid;
        $from_user = $uid;
        
        if($uid == $Message->from_user){
            $to_user = $Message->to_user;
        }else{
            $to_user = $Message->from_user;
        }

        $Spam = new SpamReport;
        $Spam->conversion_id = $Message->id;
        $Spam->from_user = $from_user;
        $Spam->to_user = $to_user;
        $Spam->reason = $request->reason;
        $Spam->created_at = time();
        $Spam->updated_at = time();
        $Spam->save();
        return Response::json(['status' => 'success','message' => 'Conversation report as spam.',], 200);
    }

    public function check_report_as_spam(Request $request, $secret) {
        $id = Message::getDecryptedId($secret);
        try{
            if(empty($id)){
                return Response::json(['status' => 'error','message' => 'Something went wrong',], 200);
            }
        }catch(\Exception $e){
            return Response::json(['status' => 'error','message' => 'Something went wrong',], 200);
        }

        $uid = $this->uid;
        $count = SpamReport::where('from_user',$uid)->where('conversion_id',$id)->count();
        return Response::json(['status' => 'success','count' => $count,], 200);
    }

    public function service_send_as_message(Request $request) {
        if(empty($request->service_id)) {
            return Response::json(['status' => 'error','message' => 'Please select service']);
        }
        $service_id = Service::getDecryptedId($request->service_id);
        $message = $message_details = [];
        $reply_msg_for_details = '[{@SERVICE_ID='.$request->service_id.'@}]';
        $msg_id = Message::getDecryptedId($request->msg_id);

        $message['id'] = $msg_id;
        $message['last_message'] = 'Shared a Service';
        $message['platform'] = 0;
        $message_id = Chatify::newMessage($message, 'old');

        $newCreatedMessage = Message::find($message_id);
        $from_user = $this->uid;
        if($from_user == $newCreatedMessage->from_user) {
            $to_user = $newCreatedMessage->to_user;
        } else {
            $to_user = $newCreatedMessage->from_user;
        }

        $message_details['message'] = $reply_msg_for_details;
        $message_details['photo_s3_key'] = '';
        $message_details['msg_id'] = $newCreatedMessage->id;
        $message_details['from_user'] = $from_user;
        $message_details['to_user'] = $to_user;
        $message_details['attachment'] = 0;
        $message_details_id = Chatify::newMessageDetail($message_details);

        $messageData = Chatify::fetchMessage($message_details_id);
        $messageDataHtml = Chatify::messageCard(@$messageData);

        $message_details_for_api = MessageDetail::with(['fromUser:id,Name,profile_photo,active_status', 'toUser:id,Name,profile_photo,active_status'])
                    ->select('id','msg_id', 'from_user', 'to_user', 'message', 'is_read', 'attachment', 'is_admin', 'created_at', 'file_name')
                    ->where('msg_id', $newCreatedMessage->id)
                    ->where(function ($q) use ($from_user) {
                        $q->where('from_user', $from_user);
                        $q->orWhere('to_user', $from_user);
                    })
                    ->orderBy('created_at', 'desc')
                    /* ->get(); // old code bk */
                    ->first()->append('time');
            
        $message_details_for_api->is_service_preview = false;
        $service_details = (object)[];
        if(app('App\Http\Controllers\Api\V1\MessagesController')->check_for_share_service($message_details_for_api->message)) {
            $message_details_for_api->is_service_preview = true;
            $service_details = app('App\Http\Controllers\Api\V1\MessagesController')->get_service_details($message_details_for_api->message);
        }
        $message_details_for_api->service_details = $service_details;
        $message_details_for_api->message = app('App\Http\Controllers\Api\V1\MessagesController')->display_message($message_details_for_api->message);
        $message_details_for_api->timestamp = Carbon::parse($message_details_for_api->created_at)->timestamp;
        $message_details_for_api->message_detail_count = Chatify::countUnseenMessagesForPusher($this->uid,$to_user,$newCreatedMessage->id);
        $message_details_for_api->order_no = "";
        if(!is_null($newCreatedMessage->order)) {
            $message_details_for_api->order_no = $newCreatedMessage->order->order_no;
        }
        $message_details_for_api->service_name = '';
        if(!is_null($newCreatedMessage->service)) {
            $message_details_for_api->service_name = $newCreatedMessage->service->title;
        }

        //send push notification
        $user = User::select('id','username','email','web_notification','notification','last_login_at','last_message_read_on')->find($to_user);
        if($user->web_notification == 1){
            if($newCreatedMessage->service_id != 0 && $newCreatedMessage->order_id != 0) {
                $requesttype = "orders";
            } else if($newCreatedMessage->service_id == 0 && $newCreatedMessage->order_id != 0) {
                $requesttype = "services";
            } else {
                $requesttype = "users";
            }
            $service_secret = $order_no = "";
            if(isset($newCreatedMessage->service)) {
                $service_secret = $newCreatedMessage->service->secret;
            }
            if(isset($newCreatedMessage->order)) {
                $order_no = $newCreatedMessage->order->order_no;
            }
            $info = [
                'id' => $to_user,
                'from_user' => $this->uid_secret,
                'message' => "Shared a Service",
                'type' => $requesttype,
                'service_id' => $service_secret,
                'order_id' => $order_no,
                'conversation_id' => $newCreatedMessage->secret
            ];
            Chatify::sendPushNotification($info);
        }
        // send to user using pusher for mobile only
        Chatify::push('private-chatify', 'messaging', [
            'message_detail' => $message_details_for_api, // this for mobile app
            'conversation_id' => $newCreatedMessage->secret,
            'tab' => $requesttype,
        ]);

        // send the response
        return Response::json([
            'status' => '200',
            'message' => $messageDataHtml,
        ]);
    }

    public function upgrade_order(Request $request,$order_no) {
        $order = Order::upgradeorderstatus()->where('order_no',$order_no)
						->where('uid',Auth::id())
                        ->where('plan_type','!=','premium')
                        ->select('id','service_id','plan_type')
						->first();
        if(is_null($order)) {
            return response()->json(['status' => 'error']);
        }

        $service_plans = ServicePlan::where('service_id',$order->service_id)
                                ->where('plan_type','!=',$order->plan_type)
                                ->where('price','>=',$order->plan->price);
        if($order->plan_type == 'standard') {
            $service_plans = $service_plans->where('plan_type','!=','basic');
        }
        $service_plans = $service_plans->get();
        if(count($service_plans) == 0) {
            return response()->json(['status' => 'error']);
        }
        //return view('frontend.order_upgrade.view',compact('order','service_plans'));
        return response()->json(['status'=>'success','service_plans'=>$service_plans,'plan_type'=>$order->plan_type]);
    }

    public function check_unread_message_count(Request $request) {
        //$count = MessageDetail::where('to_user',$this->uid)->where('is_read',0)->count();
        //return response()->json(['status'=>'success','count'=>$count]);
        return response()->json(['status'=>'success','count'=>0]);
    }


    /**
     * manually mark as unseen messages
     *
     * @param Request $request
     * @return void
     */
    public function unSeen(Request $request)
    {
        $id = MessageDetail::getDecryptedId($request->messenger_id);
        $user_id = User::getDecryptedId($request->id);

        $unread_message_count = 0;
        $uid = $this->uid;

        // get selected message
        $selectedMsgUnread = MessageDetail::where('id',$id)
            ->where(function($q) use($uid){
                $q->Where('from_user',$uid);
                $q->orWhere('to_user',$uid);
            })->first();
            
        if(!empty($selectedMsgUnread)){
            $msg_id = $selectedMsgUnread->msg_id;
            $to_user_id = $selectedMsgUnread->to_user;
            $from_user_id = $selectedMsgUnread->from_user;

            // Reset to user message seen
            MessageDetail::Where('from_user',$user_id)
                ->where('to_user',$uid)
                ->where('is_unread_first',1)
                ->where('msg_id',$msg_id)
                ->update(['is_unread_first' => 0]);

            //If selected message sent by me
            if($from_user_id == $uid){

                // get first unread message
                $selectedMsgUnread = MessageDetail::select('id','is_unread_first')
                ->Where('from_user',$to_user_id)
                ->where('to_user',$uid)
                ->where('msg_id',$msg_id)
                ->whereRaw('DATE_FORMAT(created_at, "%Y-%m-%d %H:%i:%s") < "'.$selectedMsgUnread->created_at.'"')
                ->orderBy('id','desc')
                ->limit(1)->first();

                if(!empty($selectedMsgUnread)){
                    // mark as is first unread message
                    $selectedMsgUnread->is_unread_first = 1;
                    $selectedMsgUnread->save();
                }
            }else{
                // mark as is first unread message
                $selectedMsgUnread->is_unread_first = 1;
                $selectedMsgUnread->save();
            }

            if(isset($selectedMsgUnread->id)){
                $unread_message_count = MessageDetail::select('id')
                ->where('to_user',$uid)
                ->where('msg_id',$msg_id)
                ->where('id','>=',$selectedMsgUnread->id)
                ->count();
            }
        }

        //Get Total User Unread message
        $total_unread_message_count = Chatify::countUnseenMessagesForUser($this->uid);

        // send the response
        return Response::json([
            'status' => 1,
            'unreadMessageCount' => $unread_message_count,
            'total_unread_message_count' => $total_unread_message_count
        ], 200);
    }

}
