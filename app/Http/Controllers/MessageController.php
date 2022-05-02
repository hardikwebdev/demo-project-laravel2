<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Message;
use App\MessageDetail;
use App\EmailTemplate;
use App\User;
use App\SpamReport;
use ChristofferOK\LaravelEmojiOne\LaravelEmojiOne;
use App\Order;
use AWS;
use App\SaveTemplate;
use Auth;
use App\Mail\NewChatMessage;
use App\Jobs\SendEmail;
use Illuminate\Support\Str;
use App\Service;
use Session;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\Admin;

class MessageController extends Controller {

    private $uid;

    public function __construct(){
        $this->middleware(function ($request, $next) {
            $this->uid = Auth::user()->id;
            if(Auth::user()->parent_id != 0){
                $this->uid = Auth::user()->parent_id;
            }
            return $next($request);
        });
    }


    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {

    }

    /* Sent messages */

    public function sent() {
    	$uid = $this->uid;
    	$Message = Message::with('toUser')->where('from_user', $uid)->paginate(50);
    	return view('frontend.message.sent', compact('Message'));
    }

    /* Archived Message */

    public function archived() {

    }

    /* Message Details */
    public function details($secret) {
        $msgId = Message::getDecryptedId($secret);
        try{
            if(empty($msgId)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }

    	$uid = $this->uid;

        /* ======== new code of chat ============ */
        $message = Message::find($msgId);
        $type = 'users';
        $service_id = $order_id = '';
        $from_user = $message->from_user;
        if($message->from_user == $uid) {
            $from_user = $message->to_user;
        }
        if($message->is_admin == 1) {
            $fromdata = Admin::find($from_user);
        } else {
            $fromdata = User::select('id')->find($from_user);
        }
        if($message->order_id != 0 && $message->service_id != 0) {
            $type = 'orders';
            $service_id = $message->service->secret;
            $order_id = $message->order->order_no;
        } else if($message->order_id == 0 && $message->service_id != 0) {
            $type = 'services';
            $service_id = $message->service->secret;
        }
        $query_params = '?from=notification&type='.$type.'&user_id='.$fromdata->secret.'&service='.$service_id.'&order='.$order_id;
        $link = url('messaging/conversations').$query_params;


        if($type == 'orders'){
            if($message->order->uid == $uid){
                $link = route('buyer_orders_details',[$order_id]).'?open_order_chat=1';
            }else{
                $link = route('seller_orders_details',[$order_id]).'?open_order_chat=1';
            }
        }

        return redirect($link);
        /* ======== new code of chat ============ */

    	$CheckMessage = MessageDetail::with('fromUser', 'toUser')->where('msg_id', $msgId)
    	->where(function ($query) use ($uid) {
    		$query->where('to_user', $uid)->orWhere('from_user', $uid);
    	})->first();

    	$CkMsg = Message::where('id', $msgId)->first();

    	if ($uid == $CkMsg->to_user) {
    		$isSeen = 1;
    		$is_seen_buyer = 0;
    	}
    	if ($uid == $CkMsg->from_user) {
    		$isSeen = 1;
    		$is_seen_buyer = 1;
    	}
    	$seen = Order::where('id', '=', $CkMsg->order_id)->where('service_id', '=', $CkMsg->service_id)
    	->update([
    		'is_seen' => $isSeen,
    		'is_seen_buyer' => $is_seen_buyer
    	]);

    	if (!empty($CheckMessage)) {

    		/* Read Mark Messages */
    		$is_read_buyer = MessageDetail::where(['is_read' => 0, 'to_user' => $uid, 'msg_id' => $msgId])->update(['is_read' => 1]);

    		$messageDetail = MessageDetail::with('toUser', 'fromUser', 'messages')->where('msg_id', $msgId)->get();

            return view('frontend.message.details', compact('messageDetail', 'msgId','secret'));
    	} else {
    		return redirect(route('msg_conversations'));
    	}
    }

    /* Inbox Messages */
    function conversations() {
    	$uid = $this->uid;
    	$Message = Message::with('fromUser', 'toUser', 'service','messageDetail')
        ->where(function($query) {
            $query->whereHas('fromUser',function($q){
                $q->select('id');
            })->orWhereHas('fromAdmin',function($q){
                $q->select('id');
            });
        })
        ->whereHas('toUser',function($q){
            $q->select('id');
        });
		
    	$Message = $Message->whereHas('messageDetail', function($q) use($uid) {
    		$q->where('to_user', $uid)->orWhere('from_user', $uid)->select('id');
    	});
    	$Message = $Message->orderBy("updated_at", "DESC")->paginate(50);

    	return view('frontend.message.conversations', compact('Message'));
    }
    /* canned replay list*/
    function cannnedReplay() {
        /* Sub user check permission */ 
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}
        
        $uid = $this->uid;
        $template = SaveTemplate::where('seller_uid',$uid)->orderBy("updated_at", "DESC")->paginate(50);
        return view('frontend.message.canned-replay', compact('template'));
    }

    /* Compose main message */
    public function messageCompose(Request $request,$type=null,$slug=null,$job_offer_by=null) {

        $emoji = new LaravelEmojiOne;
        if ($request->input() && $type && $slug) {

            $from_user = $this->uid;
            $order_id = $service_id = 0;

            $is_mail_to_seller = false;

            if ($type == 'job') {
                $service = Service::select('id')->where('is_job',1)->where('seo_url',$slug)->first();
                if(!empty($service)){
                    $service_id = $service->id;

                    $toUser = User::select('id')->where('username',$job_offer_by)->first();
                    if(empty($toUser)){
                        Session::flash('errorFails', 'Something goes wrong');
                        return redirect()->back();
                    }
                    $to_user = $toUser->id;

                    if($from_user != $to_user){
                        $is_mail_to_seller = true;
                    }

                }else{
                    Session::flash('errorFails', 'Something goes wrong');
                    return redirect()->back();
                }
            }elseif ($type == 'user') {
                /*Message send to seller*/
                $toUser = User::select('id')->where('username',$slug)->first();
                if(empty($toUser)){
                    Session::flash('errorFails', 'Something goes wrong');
                    return redirect()->back();
                }
                $to_user = $toUser->id;

                if($from_user != $to_user){
                    $is_mail_to_seller = true;
                }

            }elseif ($type == 'order') {
                /*Message send to in order chat*/
                $Order = Order::where('order_no', $slug)->first();
                if(!empty($Order)){
                    $service_id = $Order->service_id;
                    $order_id = $Order->id;
                    $to_user = ($Order->uid == $from_user)?$Order->seller_uid:$Order->uid;

                    if($from_user == $Order->uid){
                        $is_mail_to_seller = true;
                    }

                }else{
                    Session::flash('errorFails', 'Something goes wrong');
                    return redirect()->back();
                }
            }elseif ($type == 'service') { 
                /*Message send to service owner*/
                $service = Service::select('id','uid')->where('seo_url',$slug)->first();
                if(!empty($service)){
                    $service_id = $service->id;
                    $to_user = $service->uid;

                    if($from_user != $to_user){
                        $is_mail_to_seller = true;
                    }

                }else{
                    Session::flash('errorFails', 'Something goes wrong');
                    return redirect()->back();
                }
            }else{
                Session::flash('errorFails', 'Something goes wrong');
                return redirect()->back();
            }

            $Message = Message::where('service_id', $service_id)
            ->where('order_id', $order_id)
            ->whereRaw("((from_user = '{$from_user}' && to_user = '{$to_user}') || (from_user = '{$to_user}' && to_user = '{$from_user}'))")
            ->where('is_admin',0)
            ->first();

            if($request->filled('chat_message'))
            {
                $reply_msg = $emoji->toShort($request->chat_message);
            }
            else
            {
                $reply_msg = $emoji->toShort($request->message);    
            }

            $reply_msg = convertToEmoji($reply_msg);

            if (empty($Message)) {
                $Message = new Message;
                $Message->service_id = $service_id;
                $Message->from_user = $from_user;
                $Message->to_user = $to_user;

                $Message->last_message = $reply_msg;
                $Message->order_id = $order_id;

                $Message->created_at = time();
                $Message->updated_at = time();
                $Message->save();
            }

            $MessageDetail = new MessageDetail;
            $MessageDetail->msg_id = $Message->id;
            $MessageDetail->from_user = $from_user;
            $MessageDetail->to_user = $to_user;
            $MessageDetail->message = $reply_msg;
            $MessageDetail->created_at = time();
            $MessageDetail->updated_at = time();
            $MessageDetail->save();
            Session::flash('tostSuccess', 'Your Message Has Been Sent Successfully.');

            $user = User::find($to_user);
            if ($user->notification == "1") {

                $sender = User::find($from_user);
                $messageDetails = nl2br($emoji->toImage($reply_msg));
                
                if ($order_id != 0) {
                    if ($Order->uid == $from_user) {
                        $link = route('seller_orders_details',[$Order->order_no]).'#Chat';
                    } else if ($Order->seller_uid == $from_user) {
                        $link = route('buyer_orders_details',[$Order->order_no]).'#Chat';
                    }
                    $Order->is_seen = 0;
                    $Order->is_seen_buyer = 0;
                    $Order->save();
                } else {
                    $link = route('msg_details',[$Message->secret]);
                }
            
                $data = [
                    'username' => $user->username,
                    'sender' => $sender->username,
                    'messageDetails' => $messageDetails,
                    'link' => $link,
                    'subject' => 'New message from ' . $sender->username . ' on demo.com',
                    'template' => 'frontend.emails.v1.new_message',
                    'email_to' => $user->email
                ];
                SendEmail::dispatch($data, new NewChatMessage($data));

                if($is_mail_to_seller == true){

                    /*Send mail to sub users*/
                    $userObj = new User;
                    $userObj->send_mail_to_subusers('is_chat_mail',$user->id,$data,'username');
                }


            }
        }
        if(Str::contains(url()->previous(), '/buyer/orders/details/') || Str::contains(url()->previous(), '/seller/orders/details/')) {
            return redirect(url()->previous().'#Chat');
        }
        return redirect()->back();
    }

    public function reply($secret, Request $request) {

        $id = Message::getDecryptedId($secret);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }
        
    	$emoji = new LaravelEmojiOne;
    	if ($request->input()) {
    		$Message = Message::find($id);
    		$reply_msg = $emoji->toShort($request->message);
    		$reply_msg = convertToEmoji($reply_msg);
            
    		$from_user = $this->uid;

    		if ($Message->from_user == $from_user) {
    			$to_user = $Message->to_user;
    		} else {
    			$to_user = $Message->from_user;
    		}

    		$MessageDetail = new MessageDetail;
    		$MessageDetail->msg_id = $Message->id;
    		$MessageDetail->from_user = $from_user;
    		$MessageDetail->to_user = $to_user;
    		$MessageDetail->message = $reply_msg;
    		$MessageDetail->created_at = date('Y-m-d H:i:s');
    		$MessageDetail->updated_at = date('Y-m-d H:i:s');

    		$MessageDetail->save();

    		$Message->last_message = $reply_msg;
    		$Message->updated_at = date('Y-m-d H:i:s');
    		$Message->save();
    		
    		Session::flash('tostSuccess', 'Message Sent Successfully.');

            $sender = User::find($from_user);
            if($Message->is_admin == 1) {
                $user = Admin::find($to_user);
                $messageDetails = nl2br($emoji->toImage($reply_msg));
                $uid = $this->uid;
                $link = url('message/details') . "/" . $secret;

                $data = [
                    'username' => $user->first_name .' '.$user->last_name,
                    'sender' => $sender->username,
                    'messageDetails' => $messageDetails,
                    'link' => $link,
                    'subject' => 'New message from ' . $sender->username . ' on demo.com',
                    'template' => 'frontend.emails.v1.new_message',
                    'email_to' => $user->email
                ];
                SendEmail::dispatch($data, new NewChatMessage($data));
            } else {
                $user = User::find($to_user);
                if ($user->notification == "1") {
                    $messageDetails = nl2br($emoji->toImage($reply_msg));
                    $uid = $this->uid;
                    $Order = Order::where('id', $Message->order_id)->first();
    
                    if (count($Order)) {
                        if ($Order->uid == $uid) {
                            $link = url('/') . "/seller/orders/details/" . $Order->order_no.'#Chat';
                        } else if ($Order->seller_uid == $uid) {
                            $link = url('/') . "/buyer/orders/details/" . $Order->order_no.'#Chat';
                        }
                        $Order->is_seen = 0;
                        $Order->is_seen_buyer = 0;
                        $Order->save();
                    } else {
                        $link = url('message/details') . "/" . $secret;
                    }
    
                    $data = [
                        'username' => $user->username,
                        'sender' => $sender->username,
                        'messageDetails' => $messageDetails,
                        'link' => $link,
                        'subject' => 'New message from ' . $sender->username . ' on demo.com',
                        'template' => 'frontend.emails.v1.new_message',
                        'email_to' => $user->email
                    ];
                    SendEmail::dispatch($data, new NewChatMessage($data));
                }
            }
		}
		if(Str::contains(url()->previous(), '/buyer/orders/details/') || Str::contains(url()->previous(), '/seller/orders/details/')) {
			return redirect(url()->previous().'#Chat');
		}
    	return redirect()->back();
    }

    public function msg_attachment($secret, Request $request) {

        $id = Message::getDecryptedId($secret);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }

    	if ($id != 'null') {
    		sleep(1);
    		//$bucket = $request->input('bucket');
            $bucket = env('bucket_service');

    		$Message = Message::find($id);
    		if (empty($Message)) {
    			return redirect()->back();
    		}
    		if ($request->input()) {
    			$from_user = $this->uid;

    			if ($Message->from_user == $from_user) {
    				$to_user = $Message->to_user;
    			} else {
    				$to_user = $Message->from_user;
    			}

    			/* Upload Images */
    			/*$media_type = $request->input('media_type');*/
                $media_type = 'attachment';

    			if ($media_type == 'attachment') {
    				$this->validate($request, [
        					'attachment' => 'max:20480',
        				], [
        					'attachment.max' => 'The file may not be greater than 20 mb.',
        				]
        			);

    				$attachment = $request->file('attachment');
    				$input['message'] = uniqid() . '.' . $attachment->getClientOriginalExtension();
    				$destinationPath = public_path('/conversations_attachment');
    				$attachment->move($destinationPath, $input['message']);

    				try {
    					$s3 = AWS::createClient('s3');
    					$ext = $attachment->getClientOriginalExtension();
    					$imageKey = md5($Message->id) . '/' . md5(time()) . '.' . $ext;
    					$result_amazonS3 = $s3->putObject([
    						'Bucket' => $bucket,
    						'Key' => $imageKey,
    						'SourceFile' => $destinationPath . '/' . $input['message'],
    						'StorageClass' => 'REDUCED_REDUNDANCY',
    						'ACL' => 'public-read',
    					]);

    					unlink($destinationPath . '/' . $input['message']);

    					$input['message'] = $result_amazonS3['ObjectURL'];
    					$input['photo_s3_key'] = $imageKey;

    					$input['msg_id'] = $Message->id;
    					$input['from_user'] = $from_user;
    					$input['to_user'] = $to_user;
    					$input['attachment'] = 1;
    					$input['file_name'] = $attachment->getClientOriginalName();
    					$input['created_at'] = date('Y-m-d H:i:s');
    					$input['updated_at'] = date('Y-m-d H:i:s');

    					$Message->last_message = 'Shared a file';
    					$Message->updated_at = date('Y-m-d H:i:s');
    					$Message->save();
    					$mid = $Message->id;

    					Session::flash('tostSuccess', 'Message Sent Successfully.');

    					MessageDetail::insert($input);
    				} catch (Aws\S3\Exception\S3Exception $e) {
    					echo "There was an error uploading the file.\n";
    				}
    			}
    		}
    		return redirect()->back();
    	}
    }

    public function spamReport($secret,Request $request) {
        $id = Message::getDecryptedId($secret);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }

        $Message = Message::find($id);
        if(empty($Message)){
            return redirect()->back();
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
        return redirect()->back();
    }

    public function unsubscribe(Request $request,$secret){
        try {
            $username = Crypt::decryptString($secret);
        } catch (DecryptException $e) {
            exit('Something goes wrong');
        }
        $user = User::where('username',$username)
        //->where('is_unsubscribe',0)
        ->first();
        if(!empty($user)){
            $user->is_unsubscribe = 1;
            $user->notification = 0;
            $user->save();
        }else{
            return redirect('404');
        }
        return view('frontend.unsubscribe',compact('user'));
    }

    public function subscribemail(Request $request,$secret){
        try {
            $username = Crypt::decryptString($secret);
        } catch (DecryptException $e) {
            exit('Something goes wrong');
        }
        $user = User::where('username',$username)
        //->where('is_unsubscribe',0)
        ->first();
        if(!empty($user)){
            $user->is_unsubscribe = 0;
            $user->last_login_at = date('Y-m-d H:i:s');
            $user->save();
        }else{
            return redirect('404');
        }
        return view('frontend.subscribemail',compact('user'));
    }

}
