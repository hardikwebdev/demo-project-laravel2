<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\SellerWork;
use App\User;
use App\Service;
use App\BuyerTransaction;
use App\SellerEarning;
use App\Notification;
use App\Message;
use App\MessageDetail;
use App\EmailTemplate;
use App\Order_review_log;
use App\AffiliateEarning;
use App\UserFile;
use App\BuyerReorderPromo;
use App\ServicePlan;
use App\Specialaffiliatedusers;
use App\BoostedServicesOrder;
use App\OrderExtendRequest;
use AWS;
use Aws\Exception\AwsException;
use App\DisputeOrder;
use App\DisputeReason;
use Auth;
use App\DisputeMessage;
use App\DisputeMessageDetail;
use ChristofferOK\LaravelEmojiOne\LaravelEmojiOne;
use App\Models\Admin;
use Carbon\Carbon;
use Srmklive\PayPal\Services\ExpressCheckout;
use App\PaymentLog;
use App\OrderTip;
use App\BluesnapTempTransaction;
use App\Setting;
use App\OrderRevisions;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use App\UserPromotionalFundTransaction;
use DateTime;
use App\ServiceQuestion;
use App\BuyerOrderTags;
use App\BuyerOrderTagDetails;
use Obydul\LaraSkrill\SkrillClient;
use Obydul\LaraSkrill\SkrillRequest;
use App\SkrillTempTransaction;
use Redirect;
use App\Http\Controllers\PaypalPaymentController;
use App\Http\Controllers\BluesnapPaymentController;
use App\CourseSection;
use App\ContentMedia;
use App\LearnCourseContent;
use App\ServiceExtra;
use Jenssegers\Agent\Agent;
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter as Adapter;

class BuyerController extends Controller {
	
	private $uid;
	private $auth_user;

    public function __construct(){
    	$this->provider = new ExpressCheckout();
    	$environment = env('BlueSnapEnvironment'); // or 'production'
        \tdanielcox\Bluesnap\Bluesnap::init($environment,env('BlueSnapID'),env('BlueSnapPassword'));

        $this->middleware(function ($request, $next) {
        	if(Auth::check()){
	            $this->uid = Auth::user()->id;
				$this->auth_user = Auth::user();
	            if(Auth::user()->parent_id != 0){
	                $this->uid = Auth::user()->parent_id;
					$this->auth_user = User::find($this->uid);
	            }
            }
            return $next($request);
        });
    }

	function orders(Request $request) {
		$status = $request->input('status');
		$search = $request->input('search');
		$created_by_filter = isset($request->created_by_filter) ? User::getDecryptedId($request->created_by_filter) : null; 
		$from_date = $request->input('from_date');
		$to_date = $request->input('to_date');
		$ordertype=$request->input('ordertype');
		$filterbydate=$request->input('filterbydate');
		$total_filter_tags=$request->total_filter_tags ?? '';
		$ordertags = [];
		if(strlen($total_filter_tags) > 0) {
			$ordertags = explode(',',$total_filter_tags);
		}

		//$uid = $this->uid;
		$uid = $this->uid;
		$cdate = date('Y-m-d H:i:s');

		if ($status) {
			if ($status == 'late') {
				$Order = Order::where(['orders.uid' => $uid])
				->where('orders.status', 'active')
				->where('orders.is_recurring',0)
				->whereRaw("((delivered_date is null and end_date < '" . $cdate . "') OR (delivered_date is not null and delivered_date > end_date))");
			}
			else if($status == 'recursive' && $request->filled('from_header'))
			{
				$before3Days = Date("Y-m-d",strtotime(Carbon::now()->subDays(3)));
				$Order = Order::whereIn('orders.status', ['active','delivered','in_revision'])
				->whereHas('subscription',function($q) use ($before3Days){
					$q->whereDate('expiry_date','>=',$before3Days)->select('id');
				})
				->where('orders.uid', $uid)
				->where('orders.is_recurring', 1);
			} else {
				$Order = Order::where(['orders.uid' => $uid, 'orders.status' => $status]);
			}
		} 
		else 
		{
			$Order = Order::where(['orders.uid' => $uid]);
		}
		
		if (!empty($search)) {
			$Order = $Order->where(function($q) use($search){
				$q->whereHas('service', function($q1) use($search) {
					$q1->where('title', 'like', '%' . $search . '%')->select('id');
				})->orWhereHas('seller',function($q1) use ($search){
					$q1->where('username', 'like', '%' . $search . '%');
				})->orWhere('order_no', 'like', '%' . $search . '%');
			});
		}
		if(!empty($created_by_filter))
		{ 
			$Order = $Order->where('created_by', $created_by_filter);
		}

		if (!empty($filterbydate)) {
			$now = Carbon::now();
			$startDate = $now;
			$endDate = $now->addDay(1)->format('Y-m-d');
			if($filterbydate == 'week'){
				$startDate = $now->startOfWeek()->format('Y-m-d');
			}elseif($filterbydate == 'month'){
				$startDate = $now->firstOfMonth()->format('Y-m-d');
			}elseif($filterbydate == 'year'){
				$startDate = $now->startOfYear()->format('Y-m-d');
			}elseif($filterbydate == 'custom'){
				if ((!empty($from_date)) && (!empty($to_date))) {
					$startDate = Carbon::createFromFormat('m/d/Y', $from_date)->format('Y-m-d');
					$endDate = Carbon::createFromFormat('m/d/Y', $to_date);
					$endDate = $endDate->addDay(1)->format('Y-m-d');
				}
			}
			$Order = $Order->whereBetween('end_date', [$startDate, $endDate]);
		}

		if(!empty($ordertype))
		{
			if($ordertype == 'service')
			{
				$Order=$Order->whereHas('service',function($query1)
        		{
					$query1->where('is_job',0);
					$query1->where('is_custom_order',0)->select('id');
				});
			}
			else if($ordertype == 'customorder')
			{
				$Order=$Order->whereHas('service',function($query1)
        		{
					$query1->where('is_custom_order',1)->select('id');
				});	
			}
			else if($ordertype == 'job')
			{
				$Order=$Order->whereHas('service',function($query1)
        		{
					$query1->where('is_job',1)->select('id');
				});	
			}
			else if($ordertype == 'recursive')
			{
				$Order = $Order->where('orders.uid',$uid)->where('orders.is_recurring',1);
			}
			else if($ordertype == 'course')
			{
				$Order = $Order->where('orders.uid',$uid)->where('orders.is_course',1);
			}
		}

		if(count($ordertags) > 0) {
			$order_ids = BuyerOrderTagDetails::whereIn('tag_id',$ordertags)->pluck('order_id');
			$Order = $Order->whereIn('orders.id',$order_ids);
		}

		$Order = $Order->join('services', 'services.id', 'orders.service_id')
		->join('users', 'users.id', 'orders.seller_uid')
		->select('orders.*', 'services.title', 'services.descriptions');

		$Order = $Order->with(['order_extend_requests' => function($q) {
			$q->where('is_accepted', '0');
		}])->sortable();


		if(!empty($ordertype) && $ordertype == 'recursive'){
			$Order = $Order->orderBy('end_date', 'desc');
		}else{
			$Order = $Order->orderBy('id', 'desc');
		}

		$Order = $Order->paginate(20);

		$Order->appends($request->all());
		//$Order->appends(['status' => $status, 'search' => $search, 'from_date' => $from_date, 'to_date' => $to_date]);
		foreach ($Order as $key => $value) {
			$added_tags = [];
			foreach ($value->taglist as $k => $v) {
				array_push($added_tags,$v->tag_id);
			}
			$value->added_tags = $added_tags;
		}

		$CountOrder['active_order'] = Order::where(['uid' => $uid, 'status' => 'active'])->count();
		$CountOrder['late_order'] = Order::where(['uid' => $uid])
		->where('status', 'active')
		->whereRaw("((delivered_date is null and end_date < '" . $cdate . "') OR (delivered_date is not null and delivered_date > end_date))")
		->count();

		$CountOrder['delivered_order'] = Order::where(['uid' => $uid, 'status' => 'delivered'])->count();
		$CountOrder['completed_order'] = Order::where(['uid' => $uid, 'status' => 'completed'])->count();
		$CountOrder['cancelled_order'] = Order::where(['uid' => $uid, 'status' => 'cancelled'])->count();

		$OrderTags = BuyerOrderTags::where('buyer_id',$this->uid)/* ->whereNotNull('order_ids') */->select('tag_name','id')->get();

		$MostUsedOrderTags = BuyerOrderTags::where('buyer_id',$this->uid)
									->select('tag_name','id')
									->withCount('tag_orders')
									->orderBy('tag_orders_count','desc')
									->limit(10)
									->get();
		$parent_id = Auth::user()->parent_id;
		if($parent_id == 0)
		{
			$parent_id = Auth::user()->id;
		}
		$subusers = User::where('parent_id',$parent_id)->orWhere('id',$parent_id)->get()->pluck('Name','secret')->toArray();
		if($request->ajax()){
			$view = view('frontend.buyer.include.order_list', compact('Order', 'CountOrder','OrderTags','MostUsedOrderTags'))->render();
			return response()->json(['status'=>200,'html'=>$view]);
		}
		return view('frontend.buyer.order', compact('Order', 'CountOrder','OrderTags','MostUsedOrderTags','subusers'));
	}

	public function buyer_extended_order_request(Request $request, $order_id) {
		$uid = $this->uid;
		$search = $request->input('search');
		$from_date = $request->input('from_date');
		$to_date = $request->input('to_date');
		$cdate = date('Y-m-d H:i:s');

		$mainOrder = Order::where('order_no', $order_id)->first();

		$Order = OrderExtendRequest::with('order')
		->where('order_extend_requests.order_id', $mainOrder->id);


		if (!empty($search)) {
			$Order = $Order->whereHas('order.service', function($q) use($search) {
				$q->where('title', 'like', '%' . $search . '%')->select('id');
			});
		}

		if ((!empty($from_date)) && (!empty($to_date))) {
			$startDate = date('Y-m-d', strtotime($from_date));
			$endDate = date('Y-m-d', strtotime($to_date));
			$endDate = date('Y-m-d', strtotime('+1 days ' . $endDate));
			$Order = $Order->join('orders', 'orders.id', 'order_extend_requests.order_id')
			->whereBetween('orders.end_date', [$startDate, $endDate]);
		}
		$Order = $Order->orderBy('order_extend_requests.id', 'desc')->paginate(20);
		$Order->appends(['search' => $search, 'from_date' => $from_date, 'to_date' => $to_date]);
		return view('frontend.buyer.extended_order', compact('Order'));
	}

	function order_derails($order_no = "") {
		//$uid = $this->uid;
		$uid = $this->uid;
		if (trim($order_no) == "") {
			return redirect(route('buyer_orders'));
		}

		$Order = Order::withoutGlobalScope('parent_id')->withCount('order_revisions')
		->where(['uid' => $uid, 'order_no' => $order_no]);

		if (isset($_GET['extend']) && $_GET['extend'] != '0') {
			$Order = $Order->with(['order_extend_requests', 'service.images', 'extra', 'seller', 'seller_work', "review_log", 'dispute_order'])
			->first();
		} else {
			$Order = $Order->with(['order_extend_requests' => function($q) {
				$q->where('is_accepted', '0');
			}, 'service.images', 'extra', 'seller', 'seller_work', "review_log", 'dispute_order'])
			->first();
		}

		if(empty($Order)){
			return redirect(route('buyer_orders'));
		}

		/* redirect course order */
		if($Order->is_course == 1){
			return $this->course_order_details($order_no);
		}

		// Update buyer order seen flag
		if($Order->is_seen_buyer == 0){
			$Order->is_seen_buyer = 1;
			$Order->save();
		}

		$notification = Notification::where(['notify_to' => $uid, 'order_id' => $Order->id, 'is_read' => 0])->update(['is_read' => 1]);

		if($Order->status == "new"){
			return redirect()->route('order_submit_requirements',[$order_no]);
		}
		
		$Message = Message::where('service_id', $Order->service_id)
		->where('order_id', $Order->id)
		->first();

		if (!empty($Message)) {
			$messageDetail = MessageDetail::with('toUser', 'fromUser')->where('msg_id', $Message->id)->get();
			$msgId = $Message->id;
		} else {
			$messageDetail = '';
			$msgId = '';
		}

		// Check recursive order last child then display main order delivered files
		$is_latest_child_order = $Order->is_latest_child_order($Order);
		$checkOrderId = $Order->id;
		if($is_latest_child_order == true){
			$checkOrderId = $Order->parent_id;

			//Check Is in progress then redirect to main order
			if($Order->status == 'active'){
				return redirect()->route('buyer_orders_details',[$Order->parent->order_no]);
			}
		}

		$UserFiles = UserFile::with('user')->where(['order_id' => $checkOrderId])->orderBy('id', 'DESC')->paginate(10);
		$UserFiles->withPath(route('getallfiles'));

		$buyerPromo = BuyerReorderPromo::where('seller_id', $Order->seller_uid)
		->where('buyer_id', $Order->uid)
		->where('service_id', $Order->service_id)
		->where('is_used', 0)
		->first();

		$ServicePlan = ServicePlan::where('service_id', $Order->service_id)
		->where('plan_type', $Order->plan_type)
		->first();

		$curr= date('Y-m-d H:i:s');
		$to = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $Order->start_date);
		$from = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$curr);
		$diff_in_hours = $to->diffInHours($from);

		if($diff_in_hours >= 72){
			$replyFrom=Message::where('order_id',$Order->id)->first();
			if($replyFrom){
				$particularData=MessageDetail::where('msg_id',$replyFrom->id)->where('from_user',$Order->seller_uid)->first();	
					if($particularData){
						$checkHours="true";
					}	
					else{
						$checkHours="false";
					}			
			}else{
				$checkHours="false";
			}		
		}else{
			$checkHours="true";	
		}

		/* start - code of timer */
		if($Order->is_recurring == 0){
			$end_date = $Order->end_date;
		}else{
			$end_date = $Order->subscription->expiry_date;
		}
		$diffDate = new DateTime($end_date);
		$now = new DateTime();
		/*if(isset($Order->seller->timezone)) {
			$now = convert_timezone($now,$Order->seller->timezone,config('app.timezone'));
			$diffDate = convert_timezone($diffDate,$Order->seller->timezone,config('app.timezone'));
		}*/
		//$interval = $diffDate->diff($now);
		$interval = date_diff($diffDate, $now);
		$Order->new_end_date =  $end_date;
		$Order->new_interval = $interval;
		$Order->new_interval_days =  $interval->format('%a');
		$Order->new_interval_hours =  $interval->format('%h');
		$Order->new_interval_minutes =  $interval->format('%i');
		/* start - code of timer */

		/* $total_revision_row = ServicePlan::where(['service_id' => $Order->service_id, 'plan_type' => $Order->plan_type])->select('id','no_of_revisions')->first();
		$total_revision_count = $total_revision_row->no_of_revisions; */

		/* check for allow to upgrade order */
		$allow_upgrade_order = allow_to_upgrade_order($Order);
		
		$service_extra = null;
		if($Order->status =='delivered' && $Order->is_dispute == 0 && $Order->is_custom_order == 0 && $Order->is_job == 0 && $Order->is_recurring == 0 && $Order->is_course == 0 || $Order->status =='active' && $Order->is_dispute == 0 && $Order->is_custom_order == 0 && $Order->is_job == 0 && $Order->is_recurring == 0 && $Order->is_course == 0){
			$service_extra = ServiceExtra::select('id','service_id','title','description','price','delivery_days')
			->where('service_id',$Order->service_id)
			->get();
		}
		
		$serviceLink = route('services_details',[$Order->seller->username, $Order->service->seo_url]);
		$shareComponent = \Share::page($serviceLink)->facebook()->twitter()->whatsapp()->getRawLinks();
		$service_can_share = Order::service_can_share($Order);
		
		return view('frontend.buyer.order_details', compact('Order', 'messageDetail', 'msgId', 'UserFiles', 'buyerPromo', 'ServicePlan', 'couponApplied','checkHours','allow_upgrade_order','service_extra','shareComponent','service_can_share'));
	}

	function start_order($id, Request $request) {
		if ($id) {
			$order = Order::with('extra')->where(['id' => $id, 'status' => 'new'])->first();
			if (count($order)) {

				$total_days = $order->delivery_days;
				if (count($order->extra) > 0) {
					foreach ($order->extra as $rowExtra) {
						$total_days += $rowExtra->delivery_days;
					}
				}

				$order->start_date = date('Y-m-d H:i:s');

				if($order->is_recurring){
					$order->end_date = date("Y-m-d H:i:s", strtotime(" +1 months"));
				}else{
					$order->end_date = date('Y-m-d H:i:s', strtotime("+" . $total_days . " days"));
				}

				$order->status = 'active';
				$order->save();
			}
		}
		return redirect(route('buyer_orders', ['status' => 'active']));
	}

	function cancel_order($id, Request $request) {
		if ($id) {
			$specialAffiliateFlag = 0;
			$affiliate_per = 15;

			$uid = $this->uid;
			$cdate = date('Y-m-d H:i:s');

			$Order = Order::where(['uid' => $uid, 'id' => $id])
			->whereNotIn('status', ['cancelled', 'completed'])
			->first();

			if (empty($Order)) {
				return redirect(route('buyer_orders'));
			} else if($Order->is_recurring == 0) {
				/* $Order = Order::where(['uid' => $uid, 'id' => $id])
				->whereNotIn('status', ['cancelled', 'completed'])
				/*->whereRaw("((delivered_date is null and end_date < '" . $cdate . "') OR (delivered_date is not null and delivered_date > end_date) OR (delivered_date is null and end_date > '" . $cdate . "'))")
				->whereRaw("((delivered_date is null and end_date < '{$cdate}') OR (delivered_date is not null and delivered_date > end_date))")
				->first(); */
				if((is_null($Order->delivered_date) && $Order->end_date >= $cdate) || (!is_null($Order->delivered_date) && $Order->delivered_date <= $Order->end_date)) {
					return redirect(route('buyer_orders'));
				}
			}
			if ($request->input()) {
				/*If Order is Paused then Not able to complete that*/
				if($Order->is_pause == 1){
					\Session::flash('tostError', 'Your order has been blocked! Contact administrator for further details!');
					return redirect(route('buyer_orders'));
				}
				
				$Order->cancel_by = $uid;
				$Order->status = 'cancelled';
				$Order->cancel_date = $cdate;
				$Order->cancel_note = $request->cancel_note;

				if($Order->is_recurring == 0) {
					$Order->seller_rating = 1;
					$Order->is_review = 1;
					$Order->review_date = $cdate;
					$Order->completed_note = "Seller did not deliver. Order canceled after being late.";
				}

				$Order->save();

				/*Update total review count*/
				if($Order->is_recurring == 0) {
					$Order->service->total_review_count = $Order->service->total_review_count + 1;
					$Order->service->save();
				}

				//Update Review edition count
				if($Order->is_review_edition == 1){
					if($Order->service->review_edition_count > 0){
						$Order->service->review_edition_count = $Order->service->review_edition_count - 1;
						$Order->service->save();
					}
				}

				$earning = $Order->order_total_amount;

				/* Refund payment */
				$Order->refundOrderPayment($Order);
					
				/* Seller Earnings Start */
				/* ======== Special Affiliate ============== */
				if ($Order->is_affiliate == "1") {
					/* ===== Special Affiliate ========= */
					$Affiliate = AffiliateEarning::where(['status' => 'pending_clearance', 'order_id' => $Order->id, 'seller_id' => $Order->seller_uid])->first();
					if (!empty($Affiliate)) {
						$specialAffiliatedUser = Specialaffiliatedusers::where('uid', $Affiliate->affiliate_user_id)->first();
						if ($specialAffiliatedUser != null) {
							$specialAffiliateFlag = 1;
						}
					}
				}
				/* =========== End ============ */

				/*begin : get admin service charge*/
				$serviceCharge = get_service_change($earning,$Order->is_new);
				/*end : get admin service charge*/

				if ($specialAffiliateFlag == 1) {
					$serviceCharge = 0;
					$affiliate_per = 25;
				}

				/*For special seller*/
				if($Order->is_special_order == 1){
					$serviceCharge = 0;
				}

				$SellerEarning = SellerEarning::where(['status' => 'pending_clearance', 'order_id' => $Order->id, 'seller_id' => $Order->seller_uid])->first();

				if (count($SellerEarning) == 0) {
					$SellerEarning = new SellerEarning;
					$SellerEarning->order_id = $Order->id;
					$SellerEarning->seller_id = $Order->seller_uid;
				}
				$SellerEarning->note = 'Cancelled Order';

				if ($Order->is_affiliate == "1") {
					$affiliate_income = ($earning * $affiliate_per) / 100;
					$SellerEarning->anount = $earning - $serviceCharge - $affiliate_income;
				} else {
					$SellerEarning->anount = $earning - $serviceCharge;
				}

				$SellerEarning->status = 'cancelled';
				$SellerEarning->created_at = time();
				$SellerEarning->save();

				/* Seller Earnings End */

				/* Affiliate Earnings */
				if ($Order->is_affiliate == "1") {
					$Affiliate = AffiliateEarning::where(['status' => 'pending_clearance', 'order_id' => $Order->id, 'seller_id' => $Order->seller_uid])->first();
					if (!empty($Affiliate)) {
						$Affiliate->status = 'cancelled';
						$Affiliate->save();
					}
				}
				/* Affiliate Earnings End */


				/* Send Notification to buyer Start */

				$notify_from = $uid;
				if ($notify_from == $Order->uid) {
					$notify_to = $Order->seller_uid;
				} elseif ($notify_from == $Order->seller_uid) {
					$notify_to = $Order->uid;
				}


				$notification = new Notification;
				$notification->notify_to = $notify_to;
				$notification->notify_from = $notify_from;
				$notification->notify_by = 'buyer';
				$notification->order_id = $Order->id;
				$notification->is_read = 0;
				$notification->type = 'cancel_order';
				$notification->message = 'Order #' . $Order->order_no . ' has cancelled';
				$notification->created_at = time();
				$notification->updated_at = time();
				$notification->save();
				/* Send Notification to buyer End */

				if (!empty($Order)) {

					$Order->makeOrderOnHoldToActive($Order->service);

					/* Send Email to Seller */
					$orderDetail = Order::with(['seller' => function ($q) {
						$q->select('id', 'Name', 'username');
					}, 'user' => function ($q) {
						$q->select('id', 'Name', 'username');
					}, 'service' => function ($q) {
						$q->select('id', 'title');
					}])->select('id', 'order_no', 'seller_uid',
						'price', 'uid', 'service_id', 'start_date',
						'end_date', 'status', 'order_note', 'is_review',
						'seller_rating','created_at','txn_id')
					->where('order_no', $Order->order_no)->get();

					$seller = User::select('id','email','username')->find($Order->seller_uid);
					$buyer = User::select('id','email','username')->find($Order->uid);

					/* Send Email to Seller */
					$data = [
		                'receiver_secret' => $seller->secret,
		                'email_type' => 1,
		                'subject' => 'Your order has been cancelled',
		                'template' => 'frontend.emails.v1.cancel_order',
		                'email_to' => $seller->email,
		                'username' => $seller->username,
						'orderNumber' => $Order->order_no,
						'orderDetail' => $orderDetail,
						'name' => $buyer->username,
						'order_details_link' => route('seller_orders_details',[$Order->order_no]),
		            ];
		            QueueEmails::dispatch($data, new SendEmailInQueue($data));

					/*Send mail to sub users*/
					$userObj = new User;
					$userObj->send_mail_to_subusers('is_order_mail',$seller->id,$data,'username');


				}

				return redirect(route('buyer_orders_details', $Order->order_no)); 
			} else {
				return view('frontend.buyer.cancel_order', compact('Order'));
			}
		}
		return redirect(route('buyer_orders', ['status' => 'active']));
	}

	function download_source($orderId = '', $id = '') {
		if ($id != '') {
			$uid = $this->uid;
			$work = SellerWork::where(['id' => $id, 'order_id' => $orderId])->first();
			if ($work) {
				$file = 'public/seller/upload-work/' . $work->filename;
				$ctype = "application/vnd.openxmlformats-officedocument.presentationml.presentation";

				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private", false);
				header("Content-Type: " . $ctype);
				header("Content-Disposition: attachment; filename=\"" . basename($file) . "\";");
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: " . filesize($file));

				ob_clean();
				flush();
				readfile($file);
			}
		}
		exit;
	}

	function download_files($id = '') {
		if ($id != '') {
			$UserFiles = UserFile::find($id);
			if (count($UserFiles)) {
				$file = 'public/services/files/' . $UserFiles->filename;
				$ctype = "application/vnd.openxmlformats-officedocument.presentationml.presentation";

				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private", false);
				header("Content-Type: " . $ctype);
				header("Content-Disposition: attachment; filename=\"" . basename($file) . "\";");
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: " . filesize($file));

				ob_clean();
				flush();
				readfile($file);
			}
		}
		exit;
	}

	function download_files_s3(Request $request){
		
		if($request->filled('key') && $request->filled('bucket') && $request->filled('filename')){
			ini_set('memory_limit','1600M');
			
			$bucket = $request->bucket;


			try{
				$s3 = AWS::createClient('s3');
				
				$result = $s3->getObject(array(
					'Bucket' => $bucket,
					'Key'    => $request->key,
				));

				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private", false);

				header('Content-type: ' . $result['ContentType']);
				header("Content-Disposition: attachment; filename=\"" . basename($request->filename) . "\";");
				header("Content-Transfer-Encoding: binary");
				header('Content-length:' . $result['ContentLength']);

				echo $result['Body'];

			} catch (AwsException $e){
				
				\Session::flash('tostError', 'Something goes wrong');
				return redirect()->back();
			}
		}else{
			\Session::flash('tostError', 'Something goes wrong.');
			return redirect()->back();
		}
	}

	function validateCompleteOrder($Order,$request){
		$request->seller_rating = trim($request->seller_rating);
		$request->complete_note = trim($request->complete_note);

		/*Validate complete job*/
		$valid_description = true;
		$valid_rating = true;
		if($Order->is_review_edition == 1){
			if(!$request->complete_note){
				$valid_description = false;
			}
			if(!$request->seller_rating){
				$valid_rating = false;
			}
		}else{
			if($request->seller_rating != ''){
				if($request->complete_note == ''){
					$valid_description = false;
				}
			}
			if($request->complete_note != ''){
				if($request->seller_rating == ''){
					$valid_rating = false;
				}
			}
		}
		return (object)['valid_description'=>$valid_description,'valid_rating'=>$valid_rating];
	}
	
	function complete_order($id, Request $request) {
		if ($id) {
			$specialAffiliateFlag = 0;
			$affiliate_per = 15;
			$uid = $this->uid;
			$cdate = date('Y-m-d H:i:s');
			$Order = Order::where(['uid' => $uid, 'id' => $id, 'status' => 'delivered','is_course' => 0])->first();

			if (empty($Order)) {
				return response()->json(['success'=>false,'message'=>'Something goes wrong']);
			}

			/*If Order is Paused then Not able to complete that*/
			if($Order->is_pause == 1){
				return response()->json(['success'=>false,'message'=>'Your order has been blocked! Contact administrator for further details!']);
			}

			if ($request->input()) {
				$validateProcess = $this->validateCompleteOrder($Order,$request);

				if($validateProcess->valid_description == false){
					return response()->json(['success'=>false,'message'=>'Description is required.']);
				}

				if($validateProcess->valid_rating == false){
					return response()->json(['success'=>false,'message'=>'Rating is required.']);
				}

				$services = Service::find($Order->service_id);
				/* update seller wallet amount - services charge 5% */
				$extra = 0;
				if (!empty($Order->extra)) {
					foreach ($Order->extra as $row) {
						$extra += $row->qty * $row->price;
					}
				}
				/* ======== Special Affiliate ============== */
				if ($Order->is_affiliate == "1") {
					/* ===== Special Affiliate ========= */
					$Affiliate = AffiliateEarning::where(['status' => 'pending_clearance', 'order_id' => $Order->id, 'seller_id' => $Order->seller_uid])->first();
					if (!empty($Affiliate)) {
						$specialAffiliatedUser = Specialaffiliatedusers::where('uid', $Affiliate->affiliate_user_id)->first();
						if ($specialAffiliatedUser != null) {
							$specialAffiliateFlag = 1;
						}
					}
				}
				/* =========== End ============ */

				$product_price = (($Order->price * $Order->qty) + $extra) - $Order->reorder_discount_amount - $Order->coupon_discount - $Order->volume_discount - $Order->combo_discount;

				/*For recurring service*/
				if($Order->is_recurring == 1){
					$paypalControllerObj = new PaypalPaymentController();
					$paypalControllerObj->cancelPremiumOrder($Order->subscription->profile_id);

					$profile_receipt = json_decode($Order->subscription['receipt'],true);
					/*For Second recurring service*/

					if($profile_receipt['NUMCYCLESCOMPLETED'] != "0"){ 
						/*Servie Price * qty + Extra*/
						$findBuyerTransaction = BuyerTransaction::where('order_id',$Order->id)->select('id','payment_processing_fee')->first();
						if(!is_null($findBuyerTransaction) && $findBuyerTransaction->payment_processing_fee > 0) {
							$product_price = $profile_receipt['AMT'] - $findBuyerTransaction->payment_processing_fee;
						} else {
							$product_price = $profile_receipt['AMT'];
						}
					}

				}

				/*begin : get admin,jim service charge*/
				$serviceCharge = get_service_change($product_price,$Order->is_new);
                $serviceChargeJim = get_jim_service_change($product_price,$Order->is_new);
                /*end : get admin,jim service charge*/

				if ($specialAffiliateFlag == 0) {
					
				} else {
					$serviceCharge = 0;
					$affiliate_per = 25;
				}

				/*For special seller*/
				if($Order->is_special_order == 1){
					$serviceCharge = 0;
				}

				$Order->status = 'completed';
				$Order->service_charge = $serviceCharge;
				$Order->completed_date = $cdate;

				if ($request->seller_rating) {
					$Order->is_review = 1;
					$Order->review_date = $cdate;
					$services->total_review_count = $services->total_review_count + 1;
				} else {
					$Order->is_review = 0;
				}

				$seller = User::find($Order->seller_uid);

				if(trim($request->complete_note)){
					$Order->completed_note = $request->complete_note;
				}
				
				$Order->seller_rating = $request->seller_rating;
				$Order->save();

				//Update last child recurrence (complete order process)
				if($Order->is_recurring == 1){
					$paypalControllerObj = new PaypalPaymentController();
					$paypalControllerObj->updateLastChildRecurrence($Order);
				}

				/*check_first_five_start*/
				$check_first_five_start = Order::select('id')->where('seller_uid',$Order->seller_uid)->where('seller_rating',5)->count();

				if ($check_first_five_start == 1 && $seller->is_unsubscribe == 0) {
					$data = [
						'receiver_secret' => $seller->secret,
                    	'email_type' => 1,
		                'subject' => 'You just rocked it',
		                'template' => 'frontend.emails.v1.first_five_star_review',
		                'email_to' => $seller->email,
		                'firstname' => $seller->Name,
		            ];
		            QueueEmails::dispatch($data, new SendEmailInQueue($data));

		            /*Send mail to sub users*/
					$userObj = new User;
					$userObj->send_mail_to_subusers('is_promotion_mail',$seller->id,$data,'firstname');
				}

				/*check_first_five_start*/
				$review_log = new Order_review_log;
				$review_log->order_id = $Order->id;
				$review_log->log = json_encode(array("review" => $request->complete_note, "review_date" => $cdate, "seller_rating" => $request->seller_rating));
				$review_log->save();

				/* Send Review Email To Seller */
				$seller_rating = $request->seller_rating;
				$complete_note = $request->complete_note;

				$seller = User::select('id','username','email','freeze','net_income','earning')->find($Order->seller_uid);
				$buyer = User::select('id','username','email','freeze','net_income','earning')->find($Order->uid);

				/*Send mail to seller */
				if($seller_rating > 0){
					$orderDetail = Order::with(['seller' => function ($q) {
						$q->select('id', 'Name', 'username');
					}, 'user' => function ($q) {
						$q->select('id', 'Name', 'username');
					}, 'service' => function ($q) {
						$q->select('id', 'title');
					}])->select('id', 'order_no', 'seller_uid',
						'price', 'uid', 'service_id', 'start_date',
						'end_date', 'status', 'order_note', 'is_review',
						'seller_rating','created_at')->where('order_no', $Order->order_no)->first();
	
					$data = [
						'receiver_secret' => $seller->secret,
                    	'email_type' => 1,
						'subject' => "Your Customer ".$buyer->username." Just Left You A Review For Order #".$Order->order_no,
						'template' => 'frontend.emails.v1.review_email',
						'email_to' => $seller->email,
						'username' => $seller->username,
						'orderNumber' => $Order->order_no,
						'orderDetail' => $orderDetail,
						'name' => $buyer->username,
						'order_detail_url' => route('seller_orders_details', [$Order->order_no]),
						'seller_rating' => $seller_rating,
						'complete_note' => $complete_note,
					];
					QueueEmails::dispatch($data, new SendEmailInQueue($data));

					/*Send mail to sub users*/
					$userObj = new User;
					$userObj->send_mail_to_subusers('is_order_mail',$seller->id,$data,'username');
				}
				
				$earning = $product_price - $serviceCharge;
				
				//For recurring order net income will add only on seller payment receive
				if($Order->is_recurring == 0){
					if ($Order->is_affiliate == "1") {
						$affiliate_income = ($product_price * $affiliate_per) / 100;
						$seller->freeze = $seller->freeze + ($earning - $affiliate_income);
						$seller->net_income = $seller->net_income + ($earning - $affiliate_income);
					} else {
						$seller->freeze = $seller->freeze + $earning;
						$seller->net_income = $seller->net_income + $earning;
					}
					$seller->save();
				}

				/* Buyer Transactions Start */
				$buyerTransaction = new BuyerTransaction;
				$buyerTransaction->order_id = $Order->id;
				$buyerTransaction->buyer_id = $Order->uid;
				$buyerTransaction->note = 'Payment for Service Purchase';
				$buyerTransaction->anount = $product_price;
				$buyerTransaction->status = 'payment';
				$buyerTransaction->created_at = time();
				$buyerTransaction->save();
				/* Buyer Transactions End */

				/* Send Notification to buyer Start */
				$notify_from = $uid;
				if ($notify_from == $Order->uid) {
					$notify_to = $Order->seller_uid;
				} elseif ($notify_from == $Order->seller_uid) {
					$notify_to = $Order->uid;
				}

				$notification = new Notification;
				$notification->notify_to = $notify_to;
				$notification->notify_from = $notify_from;
				$notification->notify_by = 'buyer';
				$notification->order_id = $Order->id;
				$notification->is_read = 0;
				$notification->type = 'complete_order';
				$notification->message = 'Order #' . $Order->order_no . ' has completed';
				$notification->created_at = time();
				$notification->updated_at = time();
				$notification->save();
				/* Send Notification to buyer End */

				if($Order->is_recurring == 0){
					//Update payment date on seller earning
					Order::storeSellerEarningPaymentDate($Order);
				}

				if (!empty($Order)) {

					$objOrder = new Order;
					$avgRating = $objOrder->calculateServiceAverageRating($Order->service_id);
					$services->service_rating = $avgRating;
					$services->save();

					$Order->makeOrderOnHoldToActive($services);

					/* Send Email to Seller */
					$orderDetail_data = Order::where('order_no', $Order->order_no)->select('id','uid','service_id','created_at','order_no','txn_id')->first();
					$orderDetail = [];
					if(!is_null($orderDetail_data)) {
						$orderDetail['buyer'] = $orderDetail_data->user->username;
						$orderDetail['created_at'] = $orderDetail_data->created_at;
						$orderDetail['service'] = $orderDetail_data->service->title;
						$orderDetail['order_no'] = $orderDetail_data->order_no;
						$orderDetail['txn_id'] = $orderDetail_data->txn_id;
					}

					$data = [
						'receiver_secret' => $seller->secret,
                    	'email_type' => 1,
		                'subject' => 'Your order has been completed',
		                'template' => 'frontend.emails.v1.complete_order',
		                'email_to' => $seller->email,
		                'username' => $seller->username,
						'orderNumber' => $Order->order_no,
						'orderDetail' => $orderDetail,
						'name' => $buyer->username,
						'order_detail_url' => route('seller_orders_details', [$Order->order_no]),
		            ];
		            QueueEmails::dispatch($data, new SendEmailInQueue($data));

		            /*Send mail to sub users*/
					$userObj = new User;
					$userObj->send_mail_to_subusers('is_order_mail',$seller->id,$data,'username');

				}

				$orderCount = Order::select('id')->where('uid',$uid)
						//->where('status','completed')
						//->where('is_review',0)
						//->where('seller_rating',0)
						->whereRaw('( (status = "completed" && completed_note is null AND seller_rating = 0) OR (status = "delivered" and is_recurring = 0) )')
						->count();
				$review_reminder_params = ['secret' => $Order->secret];
				$buyer_order_params = ['id' => $Order->order_no];
				if ($request->seller_rating > 3) {
					$review_reminder_params += ['is_share' => 1];
					$buyer_order_params += ['is_share' => 1];
				}
				if ($request->seller_rating && $orderCount > 0) {
					return response()->json(['success'=>true,'message'=>'','url'=>route('review_reminder',$review_reminder_params)]);
				} else {
					return response()->json(['success'=>true,'message'=>'','url'=>route('buyer_orders_details',$buyer_order_params)]);
				}
			} else {
				return response()->json(['success'=>false,'message'=>'Something goes wrong']);
			}
		}
		return redirect(route('buyer_orders', ['status' => 'active']));
	}

	function complete_monthly_course_order($id, Request $request) {
		if (!$id) {
			return response()->json(['success'=>false,'message'=>'Something goes wrong']);
		}

		if (!$request->input()) {
			return response()->json(['success'=>false,'message'=>'Something goes wrong']);
		}

		$uid = $this->uid;
		$cdate = date('Y-m-d H:i:s');

		$Order = Order::where(['uid' => $uid, 'id' => $id,'is_course'=>1,'is_recurring'=>1,'status'=>'active'])
		->where('status','!=','completed')->first();

		if (empty($Order)) {
			return response()->json(['success'=>false,'message'=>'Something goes wrong']);
		}

		/*If Order is Paused then Not able to complete that*/
		if($Order->is_pause == 1){
			return response()->json(['success'=>false,'message'=>'Your order has been blocked! Contact administrator for further details!']);
		}

		/*For recurring service*/
		app('App\Http\Controllers\PaypalPaymentController')->cancelPremiumOrder($Order->subscription->profile_id);

		$Order->status = 'completed';
		$Order->completed_date = $cdate;
		$Order->save();

		//Update last child recurrence (complete order process)
		app('App\Http\Controllers\PaypalPaymentController')->updateLastChildRecurrence($Order);

		/* Send Review Email To Seller */
		$seller = User::select('id','username','email','freeze','net_income','earning')->find($Order->seller_uid);
		$buyer = User::select('id','username','email','freeze','net_income','earning')->find($Order->uid);

		/* Send Notification to buyer Start */
		$notify_from = $uid;
		if ($notify_from == $Order->uid) {
			$notify_to = $Order->seller_uid;
		} elseif ($notify_from == $Order->seller_uid) {
			$notify_to = $Order->uid;
		}

		$notification = new Notification;
		$notification->notify_to = $notify_to;
		$notification->notify_from = $notify_from;
		$notification->notify_by = 'buyer';
		$notification->order_id = $Order->id;
		$notification->is_read = 0;
		$notification->type = 'complete_order';
		$notification->message = 'Order #' . $Order->order_no . ' has completed';
		$notification->created_at = time();
		$notification->updated_at = time();
		$notification->save();
		/* Send Notification to buyer End */

		/* Send Email to Seller */
		$orderDetail = [];
		$orderDetail['buyer'] = $Order->user->username;
		$orderDetail['created_at'] = $Order->created_at;
		$orderDetail['service'] = $Order->service->title;
		$orderDetail['order_no'] = $Order->order_no;
		$orderDetail['txn_id'] = $Order->txn_id;

		$data = [
			'receiver_secret' => $seller->secret,
			'email_type' => 1,
			'subject' => 'Your order has been completed',
			'template' => 'frontend.emails.v1.complete_order',
			'email_to' => $seller->email,
			'username' => $seller->username,
			'orderNumber' => $Order->order_no,
			'orderDetail' => $orderDetail,
			'name' => $buyer->username,
			'order_detail_url' => route('seller_orders_details', [$Order->order_no]),
		];
		QueueEmails::dispatch($data, new SendEmailInQueue($data));

		/*Send mail to sub users*/
		$userObj = new User;
		$userObj->send_mail_to_subusers('is_order_mail',$seller->id,$data,'username');

		\Session::flash('tostSuccess', 'Your monthly subscription cancelled successfully.');

		$return_url = route('course_details',[$seller->username,$Order->service->seo_url]);
		return response()->json(['success'=>true,'message'=>'','url'=>$return_url]);
		
	}

	function transactions(Request $request) {
		$uid = $this->uid;
		$myBalance = Auth::user()->earning;
		$status = $request->input('status');
		if ($status) {
			if($status == 'deposit'){
				$transactions = BuyerTransaction::with('order')->where(['buyer_id' => $uid])->whereIn('status', ['deposit','deposit_extra'])->orderBy('id', 'desc')->paginate(20);
			}else{
				$transactions = BuyerTransaction::with('order')->where(['buyer_id' => $uid, 'status' => $status])->orderBy('id', 'desc')->paginate(20);
			}
		} else {
			$transactions = BuyerTransaction::with('order', 'service_order')->where(['buyer_id' => $uid])->orderBy('id', 'desc')->paginate(20);
		}

		$purchage = BuyerTransaction::where(['buyer_id' => $uid])->whereIn('status', ['deposit','deposit_extra'])->sum('anount');

		$activeOrderPurchage = BuyerTransaction::where(['buyer_transactions.buyer_id' => $uid, 'orders.status' => 'active'])
		->join('orders', 'orders.id', '=', 'buyer_transactions.order_id')
		->sum('buyer_transactions.anount');

		$completedOrderPurchage = BuyerTransaction::where(['buyer_transactions.buyer_id' => $uid, 'orders.status' => 'completed'])
		->join('orders', 'orders.id', '=', 'buyer_transactions.order_id')
		->sum('buyer_transactions.anount');


		$sponseredAmount = BoostedServicesOrder::where(['uid' => $uid, 'payment_status' => "completed"])->sum('amount');
		return view('frontend.buyer.transactions', compact('transactions', 'myBalance', 'purchage', 'activeOrderPurchage', 'completedOrderPurchage', 'sponseredAmount'));
	}

	public function sponsered_transaction(Request $request) {

		$uid = $this->uid;
		$currentDate = date('Y-m-d');
		$sponseredCount = BoostedServicesOrder::where(['uid' => $uid, 'payment_status' => "completed"])->count();

		$status = $request->input('status');


		$Order = BoostedServicesOrder::select('*')->where(['uid' => $uid, 'payment_status' => "completed"]);

		if ($status == 'Cancelled') {
			$Order = $Order = $Order->where('status','cancel');
		}elseif ($status == 'Pending') {
			$Order = $Order->where('start_date','>',$currentDate)->where('status','active');
		}elseif ($status == 'In progress') {
			$Order = $Order->whereRaw(" '".$currentDate."' between Date(start_date) AND Date(end_date)")->where('status','active');
		}elseif ($status == 'Completed') {
			$Order = $Order->whereDate('end_date','<',$currentDate)->where('status','active');
		}

		$Order = $Order->orderBy('id', 'desc')->paginate(20);

		return view('frontend.buyer.sponsered_transaction', compact('Order', 'sponseredCount'));
	}

	public function premiumTransaction(Request $request) {
		$uid = $this->uid;
		$myBalance = Auth::user()->earning;
		$status = 'premium_subscription';
		
		$transactions = BuyerTransaction::with('order')->where(['buyer_id' => $uid ])->where(function ($query){
			$query->where('status','premium_subscription')->orwhere('status','premium_subscription_cancel');
		})->orderBy('id', 'desc')->paginate(20);


	return view('frontend.buyer.premium_transaction', compact('transactions'));
	}

	function update_note($id, Request $request) {
		$order = Order::find($id);
		if (count($order) > 0) {
			if (trim($request->order_note) == '') {
				return response([
					'success' => false,
					'message' => 'Requirement is required.'
				]);
			} else {
				$order->order_note = $request->order_note;
				$order->save();

				if (strlen($request->order_note) >= 15) {
					$note = substr($request->order_note, 0, 15) . '...';
				} else {
					$note = $request->order_note;
				}
				return response([
					'id' => $id,
					'note' => $note,
					'success' => true,
					'message' => 'Requirement updated successfully.'
				]);
			}
		} else {
			return response([
				'success' => false,
				'message' => 'Something goes wrong.'
			]);
		}
	}

	public function updatereview(Request $request) {
		$order = Order::find($request->order_id);
		$hasoldreview = Order_review_log::where('order_id', $request->order_id)->count();
		if ($order->completed_note != $request->complete_note || $order->seller_rating != $request->seller_rating) {
			if ($hasoldreview == 0) {
				$review_log = new Order_review_log;
				$review_log->order_id = $request->order_id;
				$review_log->log = json_encode(array("review" => $order->completed_note, "review_date" => $order->completed_date, "seller_rating" => $order->seller_rating));
				$review_log->save();
			}
			$order->completed_note = $request->complete_note;

			$old_ratting = $order->seller_rating;

			$order->seller_rating = $request->seller_rating != null ? $request->seller_rating : $order->seller_rating;

			$services = Service::find($order->service_id);

			if ($order->seller_rating) {
				$order->is_review = 1;
				$order->review_date = date('Y-m-d H:i:s');
				if ($old_ratting == 0) {
					$services->total_review_count = $services->total_review_count + 1;
				}
			} else {
				$order->is_review = 0;
			}



			$order->save();

			/*check_first_five_start*/
			$check_first_five_start = Order::where('seller_uid',$order->seller_uid)->where('seller_rating',5)->count();
			$seller = User::where('id',$order->seller_uid)->first();

			if ($check_first_five_start == 1 && $seller->is_unsubscribe == 0) {
				$data = [
					'receiver_secret' => $seller->secret,
					'email_type' => 1,
		            'subject' => 'You just rocked it',
		            'template' => 'frontend.emails.v1.first_five_star_review',
		            'email_to' => $seller->email,
		            'firstname' => $seller->Name,
		        ];
		        QueueEmails::dispatch($data, new SendEmailInQueue($data));

		        /*Send mail to sub users*/
				$userObj = new User;
				$userObj->send_mail_to_subusers('is_promotion_mail',$seller->id,$data,'firstname');

			}
			/*check_first_five_start*/

			$objOrder = new Order;
			$avgRating = $objOrder->calculateServiceAverageRating($order->service_id);

			$services->service_rating = $avgRating;
			$services->save();

			$review_log = new Order_review_log;
			$review_log->order_id = $request->order_id;
			$review_log->log = json_encode(array("review" => $order->completed_note, "review_date" => date('Y-m-d h:i:s'), "seller_rating" => $order->seller_rating));
			$review_log->save();

			$orderCount = Order::where('uid',$order->uid)
					->where('status','completed')
					->where('is_review',0)
					->where('seller_rating',0)
					->count();
			$review_reminder_params = ['secret' => $order->secret];
			if ($request->seller_rating > 3) {
				$review_reminder_params += ['is_share' => 1];
			}
			if ($order->seller_rating && $order->is_review == 1 && $orderCount > 0) {
				return redirect()->route('review_reminder',$review_reminder_params);
			}
		}
		return redirect(route('buyer_orders_details', $order->order_no));
	}

	public function upload_temp_file(Request $request){
		ini_set('MAX_EXECUTION_TIME', '-1');
		if ($request->input()) {
			$file = $request->file('file');
			$time = round(microtime(true) * 1000);
			$filename = 'ans_'.$time.'_'. preg_replace('/[^a-zA-Z0-9_.]/', '_', $file->getClientOriginalName());
			$destinationPath = 'public/services/answers/';
			$file->move($destinationPath, $filename);
			return response(
				['code'=>200,'message'=>"",'filename'=>$filename,'file_id'=>$request->file_id]
			);
		}else{
			return response(
				['code'=>404,'message'=>"Something goes wrong."]
			);
		}
	}

	public function upload_files(Request $request) {
		if ($request->input()) {
			$uid = $this->uid;

			$file = $request->file('file');
			$filename = time() . '_' . $file->getClientOriginalName();
			$filenameSize = $file->getClientSize();

			$TotalUSerFileSize = UserFile::where(['order_id' => $request->order_id])->sum('filename_size');
			$remainFileSize = env('LIMIT_FILE_SIZE') - $TotalUSerFileSize;

			if ($filenameSize > $remainFileSize) {
				return response(
					['code' => '404', 'message' => 'Order total filezile must be less then 100MB.']
				);
			}

			$destinationPath = public_path('/services/files');
			$file->move($destinationPath, $filename);

			$UserFile = new UserFile;
			$UserFile->order_id = $request->order_id;
			$UserFile->uid = $uid;
			$UserFile->filename = $filename;
			$UserFile->filename_size = $filenameSize;
			$UserFile->save();

			$UserFiles = UserFile::with('user')->where(['order_id' => $request->order_id])->orderBy('id', 'DESC')->paginate(10);
			$UserFiles->withPath(route('getallfiles'));

			$listView = view('frontend.buyer.file_list', compact('UserFiles'))->render();

			return response(
				['code' => '200', 'message' => 'Success', 'data' => $listView]
			);
		}
	}

	public function upload_files_s3(Request $request){

		ini_set('MAX_EXECUTION_TIME', '-1');

		if ($request->input()) {
			$uid = $this->uid;
			$bucket = $request->bucket;
			$hostname = $_SERVER['HTTP_HOST'];
			$expired_at = NULL;

			$file = $request->file('file');
			$filename = time().'_'. preg_replace('/[^a-zA-Z0-9_.]/', '_', $file->getClientOriginalName());
			$filenameSize = $file->getSize(); 

			$TotalUSerFileSize = UserFile::where(['order_id'=>$request->order_id])->sum('filename_size');
			if(Auth::user()->is_premium_seller() == true) {
				$limit_file_size = (int)env('LIMIT_PREMIUM_FILE_SIZE');
				$file_limit_message = 'Order total filezile must be less then 250MB.';
				// check for expiry date
				$limit_file_size_for_expiry = (int)env('LIMIT_PREMIUM_FILE_SIZE_FOR_EXPIRE');
				if(($filenameSize + $TotalUSerFileSize) > $limit_file_size_for_expiry) {
					$expired_at = date('Y-m-d H:i:s', strtotime('+30 days'));
				}
			} else {
				$limit_file_size = (int)env('LIMIT_FILE_SIZE');
				$file_limit_message = 'Order total filezile must be less then 100MB.';
			}
			$remainFileSize = $limit_file_size - $TotalUSerFileSize;
			if($filenameSize > $remainFileSize){
				return response(
					['code'=>'404','message'=>$file_limit_message]
				);
			}

			$destinationPath = public_path('/services/files');
			$file->move($destinationPath, $filename);
			try {
				$s3 = AWS::createClient('s3');
				$order_id = $request->order_id ;
				$ext = $filename;
				$s3_key = md5($order_id).'/'.md5(time()).'.'.$ext;

				$result_amazonS3= $s3->putObject([
					'Bucket' => $bucket,
					'Key'    => $s3_key,
					'SourceFile'  =>  $destinationPath .'/'.$filename,
					'StorageClass' => 'REDUCED_REDUNDANCY',
				]);  

				$UserFile = new UserFile;
				$UserFile->order_id = $order_id;
				$UserFile->uid = $uid;
				$UserFile->filename = $filename;
				$UserFile->filename_size = $filenameSize;
				$UserFile->photo_s3_key = $s3_key;
				$UserFile->expired_at = $expired_at;
				$UserFile->save();
				if($UserFile){
					unlink($destinationPath .'/'.$filename);
				}
				$UserFiles = UserFile::with('user')->where(['order_id'=>$request->order_id])->orderBy('id','DESC')->paginate(10);
				$UserFiles->withPath(route('getallfiles'));

				$listView = view('frontend.buyer.file_list',compact('UserFiles'))->render();
				$code = 200;
				$message = 'Success';
				$data = $listView;
			} catch (Aws\S3\Exception\S3Exception $e) {
				$code = 401;
				$message = 'Error';
				$data = ' ';
				echo "There was an error uploading the file.\n";
			}

			return response(
				['code'=>$code,'message'=>$message,'data'=>$data,'total_attach_files'=>count($UserFiles)]
			);

		}
	}

	public function getallfiles(Request $request){
		if ($request->input()) {
			$UserFiles = UserFile::with('user')->where(['order_id'=>$request->order_id])->orderBy('id','DESC')->paginate(10);
			$UserFiles->withPath(route('getallfiles'));
			return view('frontend.buyer.file_list',compact('UserFiles'))->render();
		}
	}

	public function removefile(Request $request){
		if ($request->input()) {
			$UserFiles = UserFile::find($request->id);
			if(count($UserFiles)){
				if(isset($request->bucket)){
					$keyData = $UserFiles->photo_s3_key;
					$bucket = $request->bucket;
					$s3 = AWS::createClient('s3');
					try {
						$result_amazonS3= $s3->deleteObject([
							'Bucket' => $bucket,
							'Key'    => $keyData,
						]);  
					} catch (Aws\S3\Exception\S3Exception $e) {
						$result_amazonS3['ObjectURL'] = '';
						echo "There was an error uploading the file.\n";
					}
				}else{
					$filename = $UserFiles->filename;
					$filePath = public_path('/services/files/'.$filename);
					if (file_exists($filePath)) {
						unlink($filePath);
					}
				}
				$UserFiles->delete();
			}
		}
	}

	public function request_custom_quote(Request $request) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('tostError', get_user_softban_message());
		}

		/* Accept Request By Seller */
		if ($request->filled('id')) {
			$id = $request->id;

			$service = Service::with('seller', 'buyer')->find($id);
			
			//Check buyer is available
			if($service->buyer->status == 0 || $service->buyer->is_delete == 1 || $service->buyer->soft_ban == 1 ){
				\Session::flash('tostError', 'This user is not longer available.');
				return redirect()->back();
			}

			if (count($service)) {

				if($request->price < env('MINIMUM_SERVICE_PRICE')){
					\Session::flash('tostError', 'Price must be greater than or equal to $'.env('MINIMUM_SERVICE_PRICE').'.');
					return redirect()->back();
				}

				$service->custom_order_status = 1;
				$service->save();

				$servicePlan = ServicePlan::where('service_id', $service->id)->first();
				if (count($servicePlan)) {
					$servicePlan->delivery_days = $request->delivery_days;
					$servicePlan->price = $request->price;
					$servicePlan->save();
				}

				$sellername = $service->seller->username;

				$notification = new Notification;
				$notification->notify_to = $service->custom_order_buyer_uid;
				$notification->notify_from = $service->custom_order_seller_uid;
				$notification->notify_by = 'seller';
				$notification->message = 'Custom offer accepted by ' . $sellername;

				$notification->order_id = $service->id;
				$notification->is_read = 0;
				$notification->type = 'custom_order';

				$notification->created_at = time();
				$notification->updated_at = time();
				$notification->save();

				/* Send mail to buyer */
				$data = [
					'receiver_secret' => $service->buyer->secret,
					'email_type' => 1,
		            'subject' => 'Custom offer accepted by ' . $sellername,
		            'template' => 'frontend.emails.v1.custom_order',
		            'email_to' => $service->buyer->email,
		            'to' => $service->buyer->username,
					'buyername' => $service->buyer->username,
					'sellername' => $service->seller->username,
					'message_text' => 'Custom offer accepted by ' . $sellername,
					'service' => $service,
					'detail_url' => route('buyer_custom_order_details', $service->seo_url)
		        ];
		        QueueEmails::dispatch($data, new SendEmailInQueue($data));

				\Session::flash('tostSuccess', 'Custom offer send successfully.');
			} else {
				\Session::flash('tostError', 'Something goes wrong.');
			}
			return redirect()->back();
		}

		/* Custom Offer Created By Buyer and send it to seller for respond */

		if($request->price < env('MINIMUM_SERVICE_PRICE')){
			\Session::flash('tostError', 'Price must be greater than or equal to $'.env('MINIMUM_SERVICE_PRICE').'.');
			return redirect()->back();
		}

		$uid = $this->uid;
		$title = "custom-order-" . time();

		$seller = User::select('id','email','Name','username')->where(['status'=>1,'is_delete'=> 0,'soft_ban'=>0])->find($request->seller_uid);
		
		//Check user is available
		if(empty($seller)){
			\Session::flash('tostError', 'This user is not longer available.');
			return redirect()->back();
		}

		$service = new Service;
		$service->uid = $seller->id;
		$service->title = $title;
		$service->seo_url = $title;
		$service->category_id = 0;
		$service->subcategory_id = 0;
		$service->descriptions = $request->descriptions;
		$service->status = "custom_order";
		$service->is_custom_order = 1;
		$service->custom_order_seller_uid = $seller->id;
		$service->custom_order_buyer_uid = $uid;
		$service->custom_order_status = 0;
		if($request->filled('utm_source')) {
			$service->utm_source = $request->utm_source;
		}
		if($request->filled('utm_term')) {
			$service->utm_term = $request->utm_term;
		}
		$service->save();

		$servicePlan = new ServicePlan;
		$servicePlan->service_id = $service->id;
		$servicePlan->plan_type = "basic";
		$servicePlan->package_name = "Custom Package";
		$servicePlan->offering_details = "";
		$servicePlan->delivery_days = $request->delivery_days;
		$servicePlan->price = $request->price;
		$servicePlan->save();

		$message_text = 'Get new custom offer by ' . Auth::user()->username;

		$notification = new Notification;
		$notification->notify_to = $seller->id;
		$notification->notify_from = $uid;
		$notification->notify_by = 'buyer';
		$notification->order_id = $service->id;
		$notification->is_read = 0;
		$notification->type = 'custom_order';
		$notification->message = $message_text;
		$notification->created_at = time();
		$notification->updated_at = time();
		$notification->save();

		/* Send mail to seller */
		$data = [
			'receiver_secret' => $seller->secret,
			'email_type' => 1,
            'subject' => $message_text,
            'template' => 'frontend.emails.v1.custom_order',
            'email_to' => $seller->email,
            'to' => $seller->username,
			'buyername' => Auth::user()->username,
			'sellername' => $seller->username,
			'message_text' => $message_text,
			'service' => $service,
			'detail_url' => route('seller_custom_order_details', $service->seo_url),
        ];
        QueueEmails::dispatch($data, new SendEmailInQueue($data));

        /*Send mail to sub users*/
		$userObj = new User;
		$userObj->send_mail_to_subusers('is_order_mail',$seller->id,$data,'to');

		/* Redirect to custom order page */
		\Session::flash('tostSuccess', 'Your custom request has been submitted to ' . $seller->username . '.They will respond as soon as possible.');
		return redirect()->route('custom_order_request');
	}

	public function custom_order_request() {
		$uid = $this->uid;

		$Service = Service::with('basic_plans', 'seller', 'buyer')
		->where(['custom_order_buyer_uid' => $uid, 'status' => 'custom_order'])
		->orderBy('id', 'desc')
		->paginate(20);
		return view('frontend.buyer.custom_service', compact('Service'));
	}

	public function custom_order_details(Request $request, $seo_url) {
		$uid = $this->uid;
		$Service = Service::where(['custom_order_buyer_uid' => $uid, 'status' => 'custom_order'])
		->where('seo_url', $seo_url)
		->first();
		if (count($Service)) {

			$notification = Notification::where('type', 'custom_order')
			->where('order_id', $Service->id)
			->where('notify_by', 'seller')
			->where('notify_to', $uid)
			->update(['is_read' => 1]);

			return view('frontend.buyer.custom_service_details', compact('Service'));
		} else {
			return redirect('404');
		}
	}

	public function accept_extend_order_date($id, $reqId, $isAccept, Request $request) {
		if ($id) {
			$uid = $this->uid;
			$Order = Order::where(['uid' => $uid, 'id' => $id])
			->with(['order_extend_requests' => function($q) use ($reqId) {
				$q->where('id', $reqId);
			}])
			->where('status', 'active')
			->first();

			if (empty($Order)) {
				return redirect(route('buyer_orders'));
			}

			if ($request->isMethod('post')) {
				$isExtendRequest = OrderExtendRequest::where('id', '=', $reqId)->first();
				$OrderExtendRequest = OrderExtendRequest::find($isExtendRequest->id);
				$OrderExtendRequest->buyer_note = $request->buyer_note;
				$OrderExtendRequest->is_accepted = $isAccept;
				$OrderExtendRequest->save();

				/* Send Notification to buyer Start */
				$notify_from = $uid;
				if ($notify_from == $Order->uid) {
					$notify_to = $Order->seller_uid;
				} elseif ($notify_from == $Order->seller_uid) {
					$notify_to = $Order->uid;
				}

				if ($isAccept == 1) {

					if(strtotime($Order->end_date) < time()){
						$end_date = date('Y-m-d H:i:s', strtotime('+'.$isExtendRequest->extend_days.' days'));
					}else{
						$end_date = date('Y-m-d H:i:s', strtotime($Order->end_date . "+" . $isExtendRequest->extend_days . " days"));
					}

					$updateDate = Order::where('id', '=', $Order->id)->update([
						'end_date' => $end_date
					]);

					$notificationMessage = 'Request to extend due date on Order #' . $Order->order_no . ' by ' . $isExtendRequest->extend_days . ' days has been approved';
					$subject = 'Your Request Has Been Accepted';
					\Session::flash('successMessage', 'approved_extend_due_date');
				} else {
					$notificationMessage = 'Request to extend due date on Order #' . $Order->order_no . ' by ' . $isExtendRequest->extend_days . ' days has been rejected';
					$subject = 'Your Request Has Been Rejected';
					$alertMessage = 'Extend order due date request rejected by you.';
					\Session::flash('errorSuccess', 'Extend order due date request rejected by you.');
				}

				$notification = new Notification;
				$notification->notify_to = $notify_to;
				$notification->notify_from = $notify_from;
				$notification->notify_by = 'seller';
				$notification->order_id = $Order->id;
				$notification->is_read = 0;
				$notification->type = 'extend_order_date';
				$notification->message = $notificationMessage;
				$notification->created_at = time();
				$notification->updated_at = time();
				$notification->save();

				$seller = User::find($Order->seller_uid);
				$buyer = User::find($Order->uid);

				$data = [
					'receiver_secret' => $seller->secret,
					'email_type' => 1,
		            'subject' => $subject,
		            'template' => 'frontend.emails.v1.extend_delivery_date',
		            'email_to' => $seller->email,
		            'username' => $seller->username,
					'orderNumber' => $Order->order_no,
					'orderDetail' => $Order,
					'name' => $buyer->username,
					'notificationMessage' => $notificationMessage,
					'isAccept' => $isAccept,
					'detail_url' => route('seller_orders_details',[$Order->order_no]),
		        ];
		        QueueEmails::dispatch($data, new SendEmailInQueue($data));

		        /*Send mail to sub users*/
				$userObj = new User;
				$userObj->send_mail_to_subusers('is_order_mail',$seller->id,$data,'username');

				return redirect(route('buyer_orders_details', $Order->order_no));
			} else {
				return view('frontend.buyer.extend_date', compact('Order', 'isAccept'));
			}
		} else {
			return redirect(route('buyer_orders', ['status' => 'active']));
		}
	}

	public function get_reasons_for_dispute(Request $request) {
		$status = 401;
		$view = '';
		$order_id = $request->order_id;
		$order = Order::select('id','is_course')->where('status','!=','cancelled')->find($order_id);
		if(!empty($order)){
			$reasons = DisputeReason::where('is_course',$order->is_course)->get()->toArray();
			
			$user_type = $request->user_type;
			if (sizeof($reasons)) {
				$status = 200;
				$view = view('frontend.buyer.reason_list', compact('reasons', 'order_id', 'user_type'))->render();
			}
		}

		return response()->json([
			'status' => $status,
			'view' => $view
		]);
	}
	public function dispute_order(Request $request, $id = 0) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('errorFails', get_user_softban_message());
		}
		
		$updateOrder = Order::find($id);
		
		$findBuyerTransaction = BuyerTransaction::where('order_id',$updateOrder->id)->select('id','payment_processing_fee')->first();

		$extra = 0;

		$order_exist = DisputeOrder::where('order_no', $updateOrder->order_no)
		->where('status', '<>' ,'in_progress')
		->first();
		if (count($order_exist)) {
			\Session::flash('errorFails', "You can't submit same dispute multiple time.");
			return redirect()->back();
		}

		if($updateOrder->is_recurring == 1){
			//Check for payment receive ot not
			if($updateOrder->subscription->is_payment_received == 0){
				\Session::flash('errorFails', "You can't create dispute on this order because your payment has not been received yet.");
				return redirect()->back();
			}
		}

		if($request->user_type == 'admin')
		{
			$is_Admin = 1;
		}
		else
		{
			$is_Admin = 0;	
		}

		if ($updateOrder->status == "completed" || $updateOrder->is_course == 1) {

			foreach ($updateOrder->extra as $key) {
				$extra += $key->price * $key->qty;
			}

			$amount = $updateOrder->order_total_amount;

			/*For recurring service*/
			if($updateOrder->is_recurring == 1){

				if($updateOrder->is_course == 1){
					app('App\Http\Controllers\PaypalPaymentController')->cancelPremiumOrder($updateOrder->subscription->profile_id);
				}

				$profile_receipt = json_decode($updateOrder->subscription->receipt);
				/*For Second recurring service*/
				if($profile_receipt->NUMCYCLESCOMPLETED != "0"){ 
					/*Servie Price * qty + Extra*/
					if(!is_null($findBuyerTransaction) && $findBuyerTransaction->payment_processing_fee > 0) {
						$amount = $profile_receipt->AMT - $findBuyerTransaction->payment_processing_fee;
					} else {
						$amount = $profile_receipt->AMT;
					} 
				}
			}

			$orderDispute = new DisputeOrder;
			$orderDispute->dispute_id = $this->generate_disputeno();
			$orderDispute->user_id = $updateOrder->uid;
			$orderDispute->order_no = $updateOrder->order_no;
			$orderDispute->reason = $request->dispute_option;
			$orderDispute->comment = $request->dispute_comment;
			$orderDispute->user_type = $request->user_type;
			$orderDispute->amount = $amount;
			$orderDispute->status = 'open';
			$orderDispute->save();
			/*upadte order status to dispute*/
			$updateOrder->is_dispute = 1;
			$updateOrder->save();

			$emoji = new LaravelEmojiOne;
			$Message = DisputeMessage::where('dispute_id', $orderDispute->id)->first();

			$reply_msg = $emoji->toShort($request->dispute_comment);
			$reply_msg = $this->convertToEmoji($reply_msg);
			if (empty($Message)) {

				$Message = new DisputeMessage;
				$Message->dispute_id = $orderDispute->id;
				$Message->from_user = $updateOrder->uid;
				$Message->to_user = 1;
				$Message->last_message = $reply_msg;
				$Message->is_from_admin = $is_Admin;
				$Message->save();
			} else {
				$Message = DisputeMessage::find($Message->id);
				$Message->from_user = $updateOrder->uid;
				$Message->to_user = 1;
				$Message->last_message = $reply_msg;
				$Message->is_from_admin = $is_Admin;
				$Message->save();
			}

			$MessageDetail = new DisputeMessageDetail;
			$MessageDetail->msg_id = $Message->id;
			$MessageDetail->from_user = $updateOrder->uid;
			$MessageDetail->to_user = 1;
			$MessageDetail->message = $reply_msg;
			$MessageDetail->is_from_admin = $is_Admin;
			$MessageDetail->save();

			$disputeDetail = $orderDispute;
			$orderDetail = Order::where('order_no', $updateOrder->order_no)->get();
			$buyer = User::find($orderDetail[0]->uid);
			$seller = User::find($orderDetail[0]->seller_uid);

			/* Send Notification to Seller End */
			$notification = new Notification;
			$notification->notify_to = $seller->id;
			$notification->notify_from = $buyer->id;
			$notification->notify_by = 'buyer';
			$notification->order_id = $id;
			$notification->is_read = 0;
			$notification->type = 'cancel_order';
			$notification->message = 'Dispute filed on Order #' . $updateOrder->order_no;
			$notification->created_at = time();
			$notification->updated_at = time();
			$notification->save();
			/* Send Notification to Seller End */

			if ($request->user_type == "admin") {
				/* Send Notification to Seller End */
				$notification = new Notification;
				$notification->notify_to = $buyer->id;
				$notification->notify_from = '0';
				$notification->notify_by = 'Admin';
				$notification->order_id = $id;
				$notification->is_read = 0;
				$notification->type = 'dispute_order';
				$notification->message = 'Dispute filed on Order #' . $updateOrder->order_no . " by admin";
				$notification->created_at = time();
				$notification->updated_at = time();
				$notification->save();
				/* Send Notification to Seller End */
			}

			/* Affiliate Earnings Start */
			$Affiliate_amount = 0;
			if ($updateOrder->is_affiliate == "1") {
				$Affiliate = AffiliateEarning::where(['order_id' => $updateOrder->id, 'seller_id' => $updateOrder->seller_uid])->first();
				if (!empty($Affiliate)) {
					$Affiliate_amount = $Affiliate->amount;
				}
			}
			/* Affiliate Earnings End */

			/* freezing dispute amount from seller */
			$sellerEarning = SellerEarning::where(['order_id' => $updateOrder->id, 'seller_id' => $updateOrder->seller_uid])->first();

			if(!empty($sellerEarning)){
				$seller_earning_amount = $sellerEarning->anount;
			}else{
				$seller_earning_amount = $amount - $updateOrder->service_charge - $Affiliate_amount;
			}
			
			if ($updateOrder->is_transfer_to_seller_wallet == 0) {
				$seller->freeze = $seller->freeze - $seller_earning_amount;
			}else{
				$seller->earning = $seller->earning - $seller_earning_amount;
			}
			$seller->dispute_amount = $seller->dispute_amount + $seller_earning_amount;
			/*when dispute is favored on buyer side admin have to pay service charges to buyer */
			$seller->save();

			/* freezing dispute amount from seller */
			$data = [
				'receiver_secret' => $seller->secret,
				'email_type' => 1,
                'subject' => 'Dispute has filed on your order',
                'template' => 'frontend.emails.v1.dispute_order_seller',
                'email_to' => $seller->email,
                'username' => $seller->username,
				'orderNumber' => $disputeDetail->order_no,
				'orderDetail' => $orderDetail,
				'disputeDetail' => $disputeDetail,
				'buyer' => $buyer->username
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));

            /*Send mail to sub users*/
			$userObj = new User;
			$userObj->send_mail_to_subusers('is_dispute_mail',$seller->id,$data,'username');

			/*send mail to buyer*/
			$data = [
				'receiver_secret' => $buyer->secret,
				'email_type' => 1,
                'subject' => 'Dispute has filed on your order',
                'template' => 'frontend.emails.v1.dispute_order_buyer',
                'email_to' => $buyer->email,
                'username' => $buyer->username,
				'orderNumber' => $disputeDetail->order_no,
				'orderDetail' => $orderDetail,
				'disputeDetail' => $disputeDetail,
				'seller' => $seller->username
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));

			/* Send Email to Admin */
			$data = [
                'subject' => 'User Has Filed a Dispute On demo!',
                'template' => 'frontend.emails.v1.dispute_order_admin',
                'email_to' => env('NEW_HELP_EMAIL'),
                'username' => $buyer->username,
				'orderNumber' => $disputeDetail->order_no,
				'orderDetail' => $orderDetail,
				'disputeDetail' => $disputeDetail,
				'seller' => $seller->username
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));

			if ($orderDispute) {
				if ($request->user_type == "admin") {
					\Session::flash('errorSuccess', 'Dispute has been added successfully.');
				}else{
					\Session::flash('errorSuccess', 'Your dispute has been submitted.  We will look into your request as soon as possible and notify you with more information when it becomes available.');
				}
			}

		}elseif ($updateOrder->status == "delivered") {

			foreach ($updateOrder->extra as $key) {
				$extra += $key->price * $key->qty;
			}
			$amount = $updateOrder->order_total_amount;

			/*For recurring service*/
			if($updateOrder->is_recurring == 1){
				$paypalControllerObj = new PaypalPaymentController();
				$paypalControllerObj->cancelPremiumOrder($updateOrder->subscription->profile_id);

				$profile_receipt = json_decode($updateOrder->subscription->receipt);

				/*For Second recurring service*/
				if(isset($profile_receipt->NUMCYCLESCOMPLETED) && $profile_receipt->NUMCYCLESCOMPLETED != "0"){ 
					/*Servie Price * qty + Extra*/
					if(!is_null($findBuyerTransaction) && $findBuyerTransaction->payment_processing_fee > 0) {
						$amount = $profile_receipt->AMT - $findBuyerTransaction->payment_processing_fee;
					} else {
						$amount = $profile_receipt->AMT;
					}  
				}
			}

			$orderDispute = new DisputeOrder;
			$orderDispute->dispute_id = $this->generate_disputeno();
			$orderDispute->user_id = $updateOrder->uid;
			$orderDispute->order_no = $updateOrder->order_no;
			$orderDispute->reason = $request->dispute_option;
			$orderDispute->comment = $request->dispute_comment;
			$orderDispute->user_type = $request->user_type;
			$orderDispute->amount = $amount;
			$orderDispute->status = 'open';
			$orderDispute->save();

			/*upadte order status to dispute*/
			$updateOrder->is_dispute = 1;
			$updateOrder->save();
			
			$emoji = new LaravelEmojiOne;
			$Message = DisputeMessage::where('dispute_id', $orderDispute->id)->first();

			$reply_msg = $emoji->toShort($request->dispute_comment);
			$reply_msg = $this->convertToEmoji($reply_msg);
			if (empty($Message)) {

				$Message = new DisputeMessage;
				$Message->dispute_id = $orderDispute->id;
				$Message->from_user = $updateOrder->uid;
				$Message->to_user = 1;
				$Message->last_message = $reply_msg;
				$Message->is_from_admin = $is_Admin;;
				$Message->save();
			} else {
				$Message = DisputeMessage::find($Message->id);
				$Message->from_user = $updateOrder->uid;
				$Message->to_user = 1;
				$Message->last_message = $reply_msg;
				$Message->is_from_admin = $is_Admin;;
				$Message->save();
			}

			$MessageDetail = new DisputeMessageDetail;
			$MessageDetail->msg_id = $Message->id;
			$MessageDetail->from_user = $updateOrder->uid;
			$MessageDetail->to_user = 1;
			$MessageDetail->message = $reply_msg;
			$MessageDetail->is_from_admin = $is_Admin;;
			$MessageDetail->save();

			$disputeDetail = $orderDispute;
			$orderDetail = Order::where('order_no', $updateOrder->order_no)->get();
			$buyer = User::find($orderDetail[0]->uid);
			$seller = User::find($orderDetail[0]->seller_uid);

			/* Send Notification to Seller End */
			$notification = new Notification;
			$notification->notify_to = $seller->id;
			$notification->notify_from = $buyer->id;
			$notification->notify_by = 'buyer';
			$notification->order_id = $id;
			$notification->is_read = 0;
			$notification->type = 'cancel_order';
			$notification->message = 'Dispute filed on Order #' . $updateOrder->order_no;
			$notification->created_at = time();
			$notification->updated_at = time();
			$notification->save();
			/* Send Notification to Seller End */

			if ($request->user_type == "admin") {
				/* Send Notification to Seller End */
				$notification = new Notification;
				$notification->notify_to = $buyer->id;
				$notification->notify_from = '0';
				$notification->notify_by = 'Admin';
				$notification->order_id = $id;
				$notification->is_read = 0;
				$notification->type = 'dispute_order';
				$notification->message = 'Dispute filed on Order #' . $updateOrder->order_no . " by admin";
				$notification->created_at = time();
				$notification->updated_at = time();
				$notification->save();
				/* Send Notification to Seller End */
			}

			$seller->dispute_amount = $seller->dispute_amount + $amount;
			/*when dispute is favored on buyer side admin have to pay service charges to buyer */
			$seller->save();

			/* freezing dispute amount from seller */
			$data = [
				'receiver_secret' => $seller->secret,
				'email_type' => 1,
                'subject' => 'Dispute has filed on your order',
                'template' => 'frontend.emails.v1.dispute_order_seller',
                'email_to' => $seller->email,
                'username' => $seller->username,
				'orderNumber' => $disputeDetail->order_no,
				'orderDetail' => $orderDetail,
				'disputeDetail' => $disputeDetail,
				'buyer' => $buyer->username
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));

            /*Send mail to sub users*/
			$userObj = new User;
			$userObj->send_mail_to_subusers('is_dispute_mail',$seller->id,$data,'username');

			/*send mail to buyer*/
			$data = [
				'receiver_secret' => $buyer->secret,
				'email_type' => 1,
                'subject' => 'Dispute has filed on your order',
                'template' => 'frontend.emails.v1.dispute_order_buyer',
                'email_to' => $buyer->email,
                'username' => $buyer->username,
				'orderNumber' => $disputeDetail->order_no,
				'orderDetail' => $orderDetail,
				'disputeDetail' => $disputeDetail,
				'seller' => $seller->username
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));

			/* Send Email to Admin */
			$data = [
                'subject' => 'User Has Filed a Dispute On demo!',
                'template' => 'frontend.emails.v1.dispute_order_admin',
                'email_to' => env('NEW_HELP_EMAIL'),
                'username' => $buyer->username,
				'orderNumber' => $disputeDetail->order_no,
				'orderDetail' => $orderDetail,
				'disputeDetail' => $disputeDetail,
				'seller' => $seller->username
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));

			if ($orderDispute) {
				if ($request->user_type == "admin") {
					\Session::flash('errorSuccess', 'Dispute has been added successfully.');
				}else{
					\Session::flash('errorSuccess', 'Your dispute has been submitted.  We will look into your request as soon as possible and notify you with more information when it becomes available.');
				}
			}
		}
		return redirect()->back();
	}

	function generate_disputeno() {
		$order = DisputeOrder::orderBy('id', 'desc')->limit(1)->first();
		if (count($order) > 0) {
			$orderId = $order->id + 1;
		} else {
			$orderId = 1;
		}
		return "LEDO" . date('dm') . rand('4', '9999') . $orderId;
	}

	function dispute_order_list(Request $request) {
		/* Sub user check permission */ 
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}
		
		$uid = $this->uid;
		$status = $request->input('status');
		$search = $request->input('search');
		
		$order = DisputeOrder::with('messages.messages_detail');

		if(Auth::user()->parent_id == 0){
			$dispute_type = $request->input('dispute_type');
			if($dispute_type == 'seller'){
				$order = $order->whereHas('orderData', function($q) use($uid) {
					$q->where('seller_uid',  $uid)->select('id');
				});
			}elseif($dispute_type == 'buyer'){
				$order = $order->where('user_id', $this->uid);
			}else{
				$order = $order->where(function($q) use($uid){
					$q->where('user_id',$this->uid);
					$q->orWhereHas('orderData', function($q2) use($uid) {
						$q2->where('seller_uid',  $uid)->select('id');
					});
				});
			}
		}else{
			$order = $order->whereHas('orderData', function($q) use($uid) {
				$q->where('seller_uid',  $uid)->select('id');
			});
		}
		
		if ($status) {
			$order = $order->where('status', $status);
		}
		if ($search) {
			$order = $order->where(function($q) use($search){
				$q->where('dispute_id', 'like', '%' . $search . '%');
				$q->orWhere('order_no', 'like', '%' . $search . '%');
			});
		}

		/* for filter of disputed date - coming from earning report page - start */
		if($request->filled('disputed_from') && $request->filled('disputed_to')) {
			$disputed_from = Carbon::parse($request->disputed_from)->format('Y-m-d');
			$disputed_to = Carbon::parse($request->disputed_to)->format('Y-m-d');
			$order = $order->whereIn('status',['open','in_progress'])
			->whereDate('updated_at', '>=', $disputed_from)
			->whereDate('updated_at', '<=', $disputed_to)
			->where('user_id',$uid);
		}
		/* for filter of disputed date - coming from earning report page - end */

		$order = $order->orderBy('id', 'desc')->paginate(10)->appends($request->all());
		return view('frontend.buyer.dispute_orders.dispute_order_list', compact('order'));
	}

	function view_dispute_message(Request $request, $secret) {

		$id = DisputeOrder::getDecryptedId($secret);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }

        //echo $id;die;

		//$messages = DisputeMessage::find($dispute_id);


		/*if(Auth::user()->parent_id != 0)
		{
			$uid=Auth::user()->parent_id;
		}
		else
		{
			$uid=$this->uid;	
		}*/

		$uid = $this->uid;

		/* Read Mark Messages */
		DisputeMessageDetail::where(['is_read' => 0, 'to_user' => $uid])
		->whereHas('messageDetails',function($q)use($id){
			$q->where('dispute_id',$id)->select('id');
		})->update(['is_read' => 1]);

		/*if(Auth::user()->parent_id == 0)
		{
			$messageDetail = DisputeOrder::with('messages.messages_detail', 'user')
			->where('id', $id)
			->whereHas('orderData',function($q){
				$q->where('uid',$this->uid);
				$q->orWhere('seller_uid',$this->uid);
			});
			$messageDetail = $messageDetail->first();
		}
		else
		{		
			$messageDetail = DisputeOrder::with('messages.messages_detail', 'user')
			->where('id', $messages->dispute_id)
			->whereHas('orderData',function($q){
				$q->orWhere('seller_uid',$uid);
			});
			$messageDetail = $messageDetail->first();
		}*/

		$messageDetail = DisputeOrder::with('messages.messages_detail', 'user','orderData')
		->where('id', $id)
		->whereHas('orderData',function($q) use ($uid){
			$q->where('uid',$uid);
			$q->orWhere('seller_uid',$uid)->select('id');
		})
		->first();

		/* Check Block user Id*/
		$is_blocked = 0;
		$block_users = User::getBlockedByIds();
		if($messageDetail->orderData->uid == $uid){
			if(in_array($messageDetail->orderData->seller_uid, $block_users)){
				$is_blocked = 1;
			}
		}else{
			if(in_array($messageDetail->orderData->uid, $block_users)){
				$is_blocked = 1;
			}
		}

		if(!empty($messageDetail)){
			return view('frontend.buyer.dispute_orders.dispute_conversation', compact('messageDetail','secret','is_blocked'));
		}else{
			return redirect('404');
		}
	}

	public function compose_dispute_message(Request $request,$secret) {
	    $id = DisputeOrder::getDecryptedId($secret);

        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }

		$emoji = new LaravelEmojiOne;
		if ($request->input()) {

			$uid = $this->uid;
			$disputeOrder = DisputeOrder::find($id);

			$from_user = $uid;
			$to_user = $disputeOrder->user->id;
			$Message = DisputeMessage::where('dispute_id', $id)->first();
			
			$is_blocked = 0;
			$block_users = User::getBlockedByIds();
			if(in_array($to_user, $block_users)){
				\Session::flash('tostError', 'You are blocked by user.');
				return redirect()->back();
			}

			$reply_msg = $emoji->toShort($request->message);
			$reply_msg = $this->convertToEmoji($reply_msg);
			if (empty($Message)) {

				DisputeOrder::where('id', $id)->update([
					'status' => 'in progress']);

				$Message = new DisputeMessage;
				$Message->dispute_id = $id;
				$Message->from_user = $from_user;
				$Message->to_user = $to_user;
				$Message->last_message = $reply_msg;
				$Message->is_from_admin = 0;
				$Message->save();
			} else {
				$Message = DisputeMessage::find($Message->id);
				$Message->from_user = $from_user;
				$Message->to_user = $to_user;
				$Message->last_message = $reply_msg;
				$Message->is_from_admin = 0;
				$Message->save();
			}

			$MessageDetail = new DisputeMessageDetail;
			$MessageDetail->msg_id = $Message->id;
			$MessageDetail->from_user = $from_user;
			$MessageDetail->to_user = $to_user;
			$MessageDetail->message = $reply_msg;
			$MessageDetail->is_from_admin = 0;
			$MessageDetail->save();
			\Session::flash('tostSuccess', 'Message sent successfully.');

			/*$template = EmailTemplate::find(5);*/
			$user = User::find($to_user);
			$sender = User::find($from_user);
			$messageDetails = nl2br($emoji->toImage($reply_msg));

			$link = env('ADMIN_PANEL_BASE_URL') . "/dispute-order/" . $disputeOrder->dispute_id;
			$data = [
                'subject' => 'New message from ' . $sender->username . ' on demo.com',
                'template' => 'frontend.emails.v1.new_message',
                'email_to' => env('NEW_HELP_EMAIL'),
                'username' => $user->username,
				'sender' => $sender->username,
				'messageDetails' => $messageDetails,
				'link' => $link,
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));



			/*mail send to buyer / seller*/
			$order = Order::where('order_no',$disputeOrder->order_no)->first();

			$buyer = User::find($order->uid);
			$seller = User::find($order->seller_uid);
			$link = route('viewDispueMessage',[$secret]);

			if($uid == $order->uid){
				/*Send mail to seller*/
				$data = [
					'receiver_secret' => $seller->secret,
					'email_type' => 6,
	                'subject' => 'New message from ' . $buyer->username . ' on demo.com',
	                'template' => 'frontend.emails.v1.new_message',
	                'email_to' => $seller->email,
	                'username' => $seller->username,
					'sender' => $buyer->username,
					'messageDetails' => $messageDetails,
					'link' => $link,
	            ];
	            QueueEmails::dispatch($data, new SendEmailInQueue($data));

	            /*Send mail to sub users*/
				$userObj = new User;
				$userObj->send_mail_to_subusers('is_dispute_mail',$seller->id,$data,'username');


			}else{
				/*Send mail to buyer*/
				$data = [
					'receiver_secret' => $buyer->secret,
					'email_type' => 6,
	                'subject' => 'New message from ' . $seller->username . ' on demo.com',
	                'template' => 'frontend.emails.v1.new_message',
	                'email_to' => $buyer->email,
	                'username' => $buyer->username,
					'sender' => $seller->username,
					'messageDetails' => $messageDetails,
					'link' => $link,
	            ];
	            QueueEmails::dispatch($data, new SendEmailInQueue($data));

			}
		}

		return redirect()->back();
	}

	public function convertToEmoji($message) {
		$shortNames = [
			"<3" => ":heart:",
			"</3" => ":broken_heart:",
			":')" => ":joy:",
			":'-)" => ":joy:",
			":D" => ":smiley:",
			":-D" => ":smiley:",
			"=D" => ":smiley:",
			":)" => ":slight_smile:",
			":-)" => ":slight_smile:",
			"=]" => ":slight_smile:",
			"=)" => ":slight_smile:",
			":]" => ":slight_smile:",
			"':)" => ":sweat_smile:",
			"':-)" => ":sweat_smile:",
			"'=)" => ":sweat_smile:",
			"':D" => ":sweat_smile:",
			"':-D" => ":sweat_smile:",
			"'=D" => ":sweat_smile:",
			">:)" => ":laughing:",
			">;)" => ":laughing:",
			">=)" => ":laughing:",
			";)" => ":wink:",
			"':(" => ":sweat:",
			":*" => ":kissing_heart:",
			">:P" => ":stuck_out_tongue_winking_eye:",
			":(" => ":disappointed:",
			">:(" => ":angry:",
			":@" => ":angry:",
			";(" => ":cry:",
			">.<" => ":persevere:",
			"D:" => ":fearful:",
			":$" => ":flushed:",
			"O:-)" => ":innocent:",
			"B)" => ":sunglasses:",
			"-_-" => ":expressionless:",
			"=/" => ":confused:",
			":P" => ":stuck_out_tongue:",
			":p" => ":stuck_out_tongue:",
			":O" => ":open_mouth:",
			":o" => ":open_mouth:",
			":X" => ":no_mouth:",
			":x" => ":no_mouth:"
		];
		$newMessage = $message;
		foreach ($shortNames as $key => $value) {
			if (strpos($newMessage, $key) !== false) {
				$newMessage = str_replace($key, $value, $newMessage);
			}
		}
		return $newMessage;
	}

	public function cancel_dispute(Request $request, $id = 0) {

		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('errorFails', get_user_softban_message());
		}
		

		$uid = $this->uid;
		$specialAffiliateFlag = 0;
    	$affiliate_per = 15;

        $disputeOrder =  DisputeOrder::where('user_id',$uid)->find($id);

        if(count($disputeOrder) == 0){
            \Session::flash('tostError', "Enable to find your dispute.");
        }

    	if ($disputeOrder->status == 'approved' && $disputeOrder->status == 'rejected') {
    		\Session::flash('tostError', 'Enable to cancel as Dispute is already '.$disputeOrder->status);
    	}

    	$getOrderDetail  = Order::where('order_no',$disputeOrder->order_no)->first();
    	
    	if ($getOrderDetail->status == "completed" || $getOrderDetail->is_course == 1) {
    		/*transfer money to jim and seller*/
    		$getSellerDetail = User::where('id',$getOrderDetail->seller_uid)->first();

    		$affiliate_income = 0;
    		/* Affiliate Earnings Start */
    		if ($getOrderDetail->is_affiliate == "1") {
    			$Affiliate = AffiliateEarning::where(['order_id' => $getOrderDetail->id, 'seller_id' => $getOrderDetail->seller_uid])->first();
    			if (!empty($Affiliate)) {
    				$affiliate_income = $Affiliate->amount;
    			}
    		}
    		/* Affiliate Earnings End */

    		/*transfer money to jim and seller*/
    		/*update seller wallet*/
    		$current_date = date('Y-m-d');

    		$product_price = $disputeOrder->amount;

            /*begin : get admin service charge*/
            $serviceCharge = get_service_change($product_price,$getOrderDetail->is_new);
            /*end : get admin service charge*/

            if($getOrderDetail->is_special_order == 1){
                $serviceCharge = 0;
            }

            $serviceChargeJim = ($serviceCharge * env('JIM_CHARGE_PER')) / 100;

    		$seller_dispute_amount = $disputeOrder->amount - $serviceCharge - $affiliate_income;

    		
    		$completed_date = date('Y-m-d',strtotime($getOrderDetail->completed_date));

			if($getOrderDetail->is_course == 1){
				$check_end_date = date('Y-m-d', strtotime($completed_date. ' + 30 days'));
				if($getOrderDetail->is_recurring == 1){
					$check_end_date = date('Y-m-d', strtotime($getOrderDetail->subscription->last_buyer_payment_date. ' + 30 days'));
				}
			}else{
				$check_end_date = date('Y-m-d', strtotime($completed_date. ' + 5 days'));
				if($getOrderDetail->is_recurring == 1){
					$check_end_date = date('Y-m-d', strtotime($getOrderDetail->subscription->last_buyer_payment_date. ' + 15 days'));
				}
			}
    		

    		if ($check_end_date <= $current_date) {

    			$buyerTransaction = BuyerTransaction::where('order_id', $getOrderDetail->id)
    			->where('status', 'commission')
    			->where('buyer_id','38')->first();

    			if ($specialAffiliateFlag == 0 & count($buyerTransaction) == 0) {
    				/* Send 10 out of 10% in Jim Acc. */
    				$UserJim = User::where('id', '38')->first();
    				if (!empty($UserJim) && $serviceCharge > 0) {
    					$UserJim->earning = $UserJim->earning + $serviceChargeJim;
    					$UserJim->net_income = $UserJim->net_income + $serviceChargeJim;
    					$UserJim->save();

    					/* Jim Commission Transactions Start */
    					$buyerTransaction = new BuyerTransaction;
    					$buyerTransaction->order_id = $getOrderDetail->id;
    					$buyerTransaction->buyer_id = $UserJim->id;
    					$buyerTransaction->note = 'Commission for the #' . $getOrderDetail->order_no;
    					$buyerTransaction->anount = $serviceChargeJim;
    					$buyerTransaction->status = 'commission';
    					$buyerTransaction->created_at = time();
    					$buyerTransaction->save();
    					/* Jim Commission Transactions End */
    				}
    			} else {
    				$serviceCharge = 0;
    				$affiliate_per = 25;
    			}
    			/* transfering money to JIM/ADMIN */

    			$disputeOrder->is_refund = 1;
    			$disputeOrder->save();

				$updateSellerWallet = User::where('id',$getOrderDetail->seller_uid)->first();
				if($getOrderDetail->used_promotional_fund > 0) {
					$updateSellerWallet->promotional_fund = $getSellerDetail->promotional_fund + $getOrderDetail->used_promotional_fund;
					$remaining = $seller_dispute_amount - $getOrderDetail->used_promotional_fund;
					if($remaining > 0) {
						$updateSellerWallet->earning = $getSellerDetail->earning + $remaining;
					}
					/* create promotional transaction history */
					$promotional_transaction = new UserPromotionalFundTransaction;
					$promotional_transaction->user_id = $getSellerDetail->id;
					$promotional_transaction->order_id = $getOrderDetail->id;
					$promotional_transaction->amount = $getOrderDetail->used_promotional_fund;
					$promotional_transaction->type = 0; //type - service
					$promotional_transaction->transaction_type = 1; //transaction_type - credit
					$promotional_transaction->save();
				} else {
					$updateSellerWallet->earning = $getSellerDetail->earning + $seller_dispute_amount;
				}
    			$updateSellerWallet->dispute_amount = $getSellerDetail->dispute_amount - $seller_dispute_amount;
    			$updateSellerWallet->save();

    			$getOrderDetail->dispute_favour = 2;
    			$getOrderDetail->is_transfer_to_seller_wallet = 1;
    			$getOrderDetail->save();

                /*SELLER EARNING START*/
                $SellerEarning = SellerEarning::where(['status' => 'pending_clearance', 'order_id' => $getOrderDetail->id, 'seller_id' => $getOrderDetail->seller_uid])->first();
                if (count($SellerEarning) == 0) {
                    $SellerEarning = new SellerEarning;
                    $SellerEarning->order_id = $getOrderDetail->id;
                    $SellerEarning->seller_id = $getOrderDetail->seller_uid;
                }
                $SellerEarning->note = 'Funds Cleared';
                $SellerEarning->anount = $seller_dispute_amount;
                $SellerEarning->status = 'cleared';
                $SellerEarning->save();
                /*SELLER EARNING END*/

    			/* Affiliate Earnings Start */
    			if ($getOrderDetail->is_affiliate == "1") {
    				$Affiliate = AffiliateEarning::where(['status' => 'pending_clearance' ,'order_id' => $getOrderDetail->id, 'seller_id' => $getOrderDetail->seller_uid])->first();
    				if (!empty($Affiliate)) {

    					$Affiliate->status = 'cleared';
    					$Affiliate->save();
    					$User = User::find($Affiliate->affiliate_user_id);
    					if (!empty($User)) {
    						$User->earning = $User->earning + $Affiliate->amount;
    						$User->net_income = $User->net_income + $Affiliate->amount;
    						$User->save();
    					}
    				}
    			}
    			/* Affiliate Earnings End */
    		}else{
    			$disputeOrder->is_refund = 0;

    			$updateSellerWallet = User::where('id',$getOrderDetail->seller_uid)->first();
    			$updateSellerWallet->freeze = $getSellerDetail->freeze + $seller_dispute_amount;
    			$updateSellerWallet->dispute_amount = $getSellerDetail->dispute_amount - $seller_dispute_amount;
    			$updateSellerWallet->save();

    			$getOrderDetail->dispute_favour = 2;
    			$getOrderDetail->is_transfer_to_seller_wallet = 0;
    			$getOrderDetail->save();
    		}
    		/*end update seller wallet*/

    		$getOrderDetail  = Order::where('order_no',$disputeOrder->order_no)->first();
    		$getOrderDetail->dispute_favour = 2;
    		$getOrderDetail->save();

    		$notificationMsg = 'Your dispute with ID #'.$disputeOrder->dispute_id.' has been cancelled.';
    		
    		/*notification to seller*/
    		$notification = new Notification;
    		$notification->notify_to = $getOrderDetail->seller_uid;
    		$notification->notify_from = $getOrderDetail->uid;
    		$notification->notify_by = 'Buyer';
    		$notification->order_id = $disputeOrder->orderData->id;
    		$notification->is_read = 0;
    		$notification->type = 'dispute_order';
    		$notification->message = 'Buyer has cancelled dispute with ID #'.$disputeOrder->dispute_id;
    		$notification->created_at = time();
    		$notification->updated_at = time();
    		$notification->save();
    		/*notification to seller*/ 
    	} else if($getOrderDetail->status == "delivered") {
    		if($getOrderDetail->is_recurring == 1) {
                /*transfer money to jim and seller*/
                $getSellerDetail = User::where('id',$getOrderDetail->seller_uid)->first();

                $affiliate_income = 0;
                /* Affiliate Earnings Start */
                if ($getOrderDetail->is_affiliate == "1") {
                    $Affiliate = AffiliateEarning::where(['order_id' => $getOrderDetail->id, 'seller_id' => $getOrderDetail->seller_uid])->first();
                    if (!empty($Affiliate)) {
                        $affiliate_income = $Affiliate->amount;
                        $specialAffiliatedUser = Specialaffiliatedusers::where('uid', $Affiliate->affiliate_user_id)->first();
                        if ($specialAffiliatedUser != null) {
                            $specialAffiliateFlag = 1;
                        }
                    }
                }
                /* Affiliate Earnings End */

                /*transfer money to jim and seller*/
                /*update seller wallet*/
                $current_date = date('Y-m-d');

                $product_price = $disputeOrder->amount;

                /*begin : get admin,jim service charge*/
                $serviceCharge = get_service_change($product_price,$getOrderDetail->is_new);
                $serviceChargeJim = get_jim_service_change($product_price,$getOrderDetail->is_new);
                /*end : get admin,jim service charge*/

                if($getOrderDetail->is_special_order == 1){
                    $serviceCharge = 0;
                }

                if ($specialAffiliateFlag == 1) {
                    $serviceCharge = 0;
                    $affiliate_per = 25;
                }

                $seller_dispute_amount = $disputeOrder->amount - $serviceCharge - $affiliate_income;
                
                $check_end_date = date('Y-m-d', strtotime($getOrderDetail->subscription->last_buyer_payment_date. ' + 15 days'));

                if (strtotime($check_end_date) <= strtotime($current_date)) {
                    $buyerTransaction = BuyerTransaction::where('order_id', $getOrderDetail->id)
                    ->where('status', 'commission')
                    ->where('buyer_id','38')->first();

                    if ($specialAffiliateFlag == 0 & count($buyerTransaction) == 0) {
                        /* Send 10 out of 10% in Jim Acc. */
                        $UserJim = User::where('id', '38')->first();
                        if (!empty($UserJim) && $serviceCharge > 0) {
                            $UserJim->earning = $UserJim->earning + $serviceChargeJim;
                            $UserJim->net_income = $UserJim->net_income + $serviceChargeJim;
                            $UserJim->save();

                            /* Jim Commission Transactions Start */
                            $buyerTransaction = new BuyerTransaction;
                            $buyerTransaction->order_id = $getOrderDetail->id;
                            $buyerTransaction->buyer_id = $UserJim->id;
                            $buyerTransaction->note = 'Commission for the #' . $getOrderDetail->order_no;
                            $buyerTransaction->anount = $serviceChargeJim;
                            $buyerTransaction->status = 'commission';
                            $buyerTransaction->created_at = time();
                            $buyerTransaction->save();
                            /* Jim Commission Transactions End */
                        }
                    } else {
                        $serviceCharge = 0;
                        $affiliate_per = 25;
                    }
                    /* transfering money to JIM/ADMIN */

                    $disputeOrder->is_refund = 1;
                    $disputeOrder->save();

					$updateSellerWallet = User::where('id',$getOrderDetail->seller_uid)->first();
					if($getOrderDetail->used_promotional_fund > 0) {
						$updateSellerWallet->promotional_fund = $getSellerDetail->promotional_fund + $getOrderDetail->used_promotional_fund;
						$remaining = $seller_dispute_amount - $getOrderDetail->used_promotional_fund;
						if($remaining > 0) {
							$updateSellerWallet->earning = $getSellerDetail->earning + $remaining;
						}
						/* create promotional transaction history */
						$promotional_transaction = new UserPromotionalFundTransaction;
						$promotional_transaction->user_id = $getSellerDetail->id;
						$promotional_transaction->order_id = $getOrderDetail->id;
						$promotional_transaction->amount = $getOrderDetail->used_promotional_fund;
						$promotional_transaction->type = 0; //type - service
						$promotional_transaction->transaction_type = 1; //transaction_type - credit
						$promotional_transaction->save();
					} else {
						$updateSellerWallet->earning = $getSellerDetail->earning + $seller_dispute_amount;
					}
                    $updateSellerWallet->dispute_amount = $getSellerDetail->dispute_amount - $seller_dispute_amount;
                    $updateSellerWallet->save();

                    $getOrderDetail->dispute_favour = 2;
                    $getOrderDetail->is_transfer_to_seller_wallet = 1;
                    $getOrderDetail->completed_date = date('Y-m-d H:i:s');
                    $getOrderDetail->status = 'completed';
                    $getOrderDetail->save();

                    //Update last child recurrence (complete order process)
					$paypalControllerObj = new PaypalPaymentController();
					$paypalControllerObj->updateLastChildRecurrence($getOrderDetail);

                    /*SELLER EARNING START*/
                    $SellerEarning = SellerEarning::where(['status' => 'pending_clearance', 'order_id' => $getOrderDetail->id, 'seller_id' => $getOrderDetail->seller_uid])->first();
                    if (count($SellerEarning) == 0) {
                        $SellerEarning = new SellerEarning;
                        $SellerEarning->order_id = $getOrderDetail->id;
                        $SellerEarning->seller_id = $getOrderDetail->seller_uid;
                    }
                    $SellerEarning->note = 'Funds Cleared';
                    $SellerEarning->anount = $seller_dispute_amount;
                    $SellerEarning->status = 'cleared';
                    $SellerEarning->save();
                    /*SELLER EARNING END*/

                    /* Affiliate Earnings Start */
                    if ($getOrderDetail->is_affiliate == "1") {
                        $Affiliate = AffiliateEarning::where(['status' => 'pending_clearance' ,'order_id' => $getOrderDetail->id, 'seller_id' => $getOrderDetail->seller_uid])->first();
                        if (!empty($Affiliate)) {

                            $Affiliate->status = 'cleared';
                            $Affiliate->save();
                            $User = User::find($Affiliate->affiliate_user_id);
                            if (!empty($User)) {
                                $User->earning = $User->earning + $Affiliate->amount;
                                $User->net_income = $User->net_income + $Affiliate->amount;
                                $User->save();
                            }
                        }
                    }
                    /* Affiliate Earnings End */
                }else{
                    $disputeOrder->is_refund = 0;

                    $updateSellerWallet = User::where('id',$getOrderDetail->seller_uid)->first();
                   /* $updateSellerWallet->freeze = $getSellerDetail->freeze + $seller_dispute_amount;
                    $updateSellerWallet->dispute_amount = $getSellerDetail->dispute_amount - $seller_dispute_amount;*/
                    
                    $updateSellerWallet->dispute_amount = $getSellerDetail->dispute_amount - $disputeOrder->amount;
                   	$updateSellerWallet->save();

                    $getOrderDetail->dispute_favour = 2;
                    $getOrderDetail->is_transfer_to_seller_wallet = 0;
                    $getOrderDetail->completed_date = date('Y-m-d H:i:s');
                    $getOrderDetail->status = 'completed';
                    $getOrderDetail->save();
                }
                /*end update seller wallet*/
            }else{
                $getSellerDetail = User::where('id',$getOrderDetail->seller_uid)->first();
                $getSellerDetail->dispute_amount = $getSellerDetail->dispute_amount - $disputeOrder->amount;
                $getSellerDetail->save();
            }

    		$getOrderDetail->dispute_favour = 2;
    		$getOrderDetail->save();
    		
    		$notificationMsg = 'Your dispute with ID #'.$disputeOrder->dispute_id.' has been cancelled.';

    		/*notification to seller*/
    		$notification = new Notification;
    		$notification->notify_to = $getOrderDetail->seller_uid;
    		$notification->notify_from = $getOrderDetail->uid;
    		$notification->notify_by = 'Buyer';
    		$notification->order_id = $disputeOrder->orderData->id;
    		$notification->is_read = 0;
    		$notification->type = 'dispute_order';
    		$notification->message = 'Buyer has cancelled his dispute with ID #'.$disputeOrder->dispute_id;
    		$notification->created_at = time();
    		$notification->updated_at = time();
    		$notification->save();
    		/*notification to seller*/
    	}

    	$Message = DisputeMessage::where('dispute_id', $disputeOrder->id)->first();
	    if (count($Message) == 0) {
	    	$Message = new DisputeMessage;
	    	$Message->dispute_id = $id;
	    	$Message->from_user = $getOrderDetail->uid;
	    	$Message->to_user = $getOrderDetail->seller_uid;
	    	$Message->last_message = $notificationMsg;
	    	$Message->save();
	    }else{
	    	$Message = DisputeMessage::find($Message->id);
	    	$Message->from_user = $getOrderDetail->uid;
	    	$Message->last_message = $notificationMsg;
	    	$Message->save();
	    }

	    $dispute_favour = "seller";
	    
	    $MessageDetail = new DisputeMessageDetail;
	    $MessageDetail->msg_id = $Message->id;
	    $MessageDetail->from_user = $getOrderDetail->uid;
	    $MessageDetail->to_user = $getOrderDetail->seller_uid;
	    $MessageDetail->dispute_favour = $dispute_favour;
	    $MessageDetail->message = $notificationMsg;
	    $MessageDetail->save();

	    /*update status*/
	    $disputeOrder->status = 'rejected';
	    $disputeOrder->cancelled_by = 2;
	    $disputeOrder->save();

	    \Session::flash('tostSuccess', "Your dispute has been cancelled successfully.");
	    return redirect()->back();
	}

	public function orderTipCheckout(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('tostError', get_user_softban_message());
		}

		$user = $this->auth_user;

		$order_id = Order::getDecryptedId($request->order_id);
		$request['order_id'] = $order_id;
		$Order = Order::whereDoesntHave('order_tip')
		->where('id',$order_id)
		->where('uid',$user->id)
		->where('status', 'completed')
		->first();
		
		if(count($Order) > 0){

			$fromWalletAmount = 0;
			$tip_amount = $request->tip_amount;

			if($tip_amount < 5){
				\Session::flash('tostError', 'Minimum tip amount cannot be less than $5.');
				return redirect()->route('buyer_orders_details',[$Order->order_no])->with('errorFails', 'Minimum tip amount cannot be less than $5.');
			}
			if($tip_amount > 25){
				\Session::flash('tostError', 'Tip amount cannot be greater than 25.');
				return redirect()->route('buyer_orders_details',[$Order->order_no])->with('errorFails', 'Minimum tip amount cannot be greater than 25.');
			}

			/*Payment by paypal or paypal + wallet*/
			if($request->payment_from == 1){
				
				if($request->has('from_wallet') && $request->from_wallet == 1){
					if($user->earning > 0 && $tip_amount <= $user->earning){
						/*payment from wallet*/
						$fromWalletAmount = $tip_amount;
						$this->sendTipProcess($Order,$tip_amount,$fromWalletAmount,$txn_id='',$payBy='wallet');
						\Session::flash('tostSuccess', 'Tip amount credited successfully.');
						return redirect()->route('transactions')->with('errorSuccess','Tip amount credited successfully.');
					}else{
						/*Payment from paypal and wallet*/
						$fromWalletAmount = $user->earning;
					}
				}

				/*Payment from paypal*/
				$request_data = [];
				if ($fromWalletAmount > 0) {
					$request_data['items'] = [
						[
							'name' => "From wallet",
							'price' => "-" . round_price($fromWalletAmount),
							'qty' => 1
						],
						[
							'name' => "Tip Amount",
							'price' => round_price($tip_amount),
							'qty' => 1
						]
					];
				}else{
					$request_data['items'] = [
						[
							'name' => "Tip Amount",
							'price' => $tip_amount,
							'qty' => 1
						]
					];
				}

				$totalAmount = $tip_amount-$fromWalletAmount;

				$invoice_id = "LE".get_microtime();
				$request_data['invoice_id'] = $invoice_id;
				$request_data['invoice_description'] = "Tip Amount #".$invoice_id;
				$request_data['return_url'] = route('tipPaypalSuccess');
				$request_data['cancel_url'] = route('buyer_orders_details',[$Order->order_no]);
				$request_data['total'] = $totalAmount;

				try{

					$options = [
					    'SOLUTIONTYPE' => 'Sole',
					];
					$response = $this->provider->addOptions($options)->setExpressCheckout($request_data);
					/*Create Log for payment request data*/
					$log = new PaymentLog;
					$log->user_id = $Order->uid;
					$log->receipt = json_encode($response);
					$log->status = "Request data";
					$log->payment_for = "order_tip";
					$log->save();

				}catch(\Exception $e){
					return redirect()->route('buyer_orders_details',[$Order->order_no])->with('errorFails', 'Something went wrong with PayPal');
				}

				if (!$response['paypal_link']) {
					return redirect()->route('buyer_orders_details',[$Order->order_no])->with('errorFails', 'Something went wrong with PayPal');
				}else{
					$sessionData = [
						'paypal_custom_data' => $request->all(),
						'from_wallet' => $fromWalletAmount
					];
					\Session::put($invoice_id,$sessionData);
					return redirect($response['paypal_link']);
				}
			}
			/* Payment by skrill or skrill + wallet*/
			elseif($request->payment_from == 3){
				if($request->has('from_wallet') && $request->from_wallet == 1){
					if($user->earning > 0 && $tip_amount <= $user->earning){
						/*payment from wallet*/
						$fromWalletAmount = $tip_amount;
						$this->sendTipProcess($Order,$tip_amount,$fromWalletAmount,$txn_id='',$payBy='wallet');
						\Session::flash('tostSuccess', 'Tip amount credited successfully.');
						return redirect()->route('transactions')->with('errorSuccess','Tip amount credited successfully.');
					}else{
						/*Payment from skrill and wallet*/
						$fromWalletAmount = round_price($user->earning,2);
					}
				}

				/*Payment on skrill*/
				$request_data = [];
				$invoice_id = "LE".get_microtime();

				$totalAmount = $tip_amount-$fromWalletAmount;

				$this->skrilRequest = new SkrillRequest();
				$this->skrilRequest->pay_to_email = env('MERCHANT_EMAIL');
				$this->skrilRequest->logo_url = url("public/frontend/assets/img/logo/LogoHeader.png");
				$this->skrilRequest->transaction_id = $invoice_id;
				$this->skrilRequest->currency = 'USD';
				$this->skrilRequest->language = 'EN';
				$this->skrilRequest->prepare_only = '1';
				$this->skrilRequest->merchant_fields = 'demo';
				$this->skrilRequest->site_name = 'demo';
				$this->skrilRequest->customer_email = $user->email;
				$this->skrilRequest->detail1_description = 'Tip Amount';
				$this->skrilRequest->detail1_text = round_price($totalAmount);

				$this->skrilRequest->amount = round_price($totalAmount);
				$this->skrilRequest->return_url = route('skrill.thankyou');
				$this->skrilRequest->cancel_url = route('buyer_orders_details',[$Order->order_no]);
				$this->skrilRequest->status_url = url('skrill/ipn');
				$this->skrilRequest->status_url2 = '';
				
				try{
					// create object instance of SkrillClient
					$client = new SkrillClient($this->skrilRequest);
					$sid = $client->generateSID(); //return SESSION ID
			
					// handle error
					$jsonSID = json_decode($sid);
					if ($jsonSID != null && $jsonSID->code == "BAD_REQUEST"){
						return redirect()->back()->with('errorFails', 'Something went wrong with Skrill');;
					}

					//Store request data for verification
					$request->request->add(['from_wallet' => $fromWalletAmount]);
					$sessionData = [
						'paypal_custom_data' => $request->all()
					];
					$tempData = new SkrillTempTransaction;
					$tempData->merchanttransactionid = $invoice_id;
					$tempData->user_id = $user->id;
					$tempData->cart_data = json_encode($sessionData);
					$tempData->payment_for = 2;
					$tempData->save();
			
					// do the payment
					$redirectUrl = $client->paymentRedirectUrl($sid); //return redirect url
					return Redirect::to($redirectUrl); // redirect user to Skrill payment page
				}catch(\Exception $e){
					return redirect()->route('buyer_orders_details',[$Order->order_no])->with('errorFails', 'Something went wrong with Skrill');
				}
			}
			else{
				/*Payment from bluesnap*/

				/*Check max tip amount of bluesnap*/
		        $settings = Setting::find(1);
		        if(!empty($settings) && $settings->max_bluesnap_order_amount){
		            if($tip_amount > $settings->max_bluesnap_order_amount){
		                return redirect()->route('buyer_orders_details',[$Order->order_no])->with('errorFails', 'Total tip amount should not be grater then $'.$settings->max_bluesnap_order_amount);
		            }
		        }

		        /*store referance transaction*/
		        $invoice_id = "LE".get_microtime();
		        $request_data = [];
		        $request_data['items'] = [
					[
						'name' => "Tip Amount",
						'price' => $tip_amount,
						'qty' => 1
					]
				];

				$request_data['invoice_id'] = $invoice_id;
				$request_data['invoice_description'] = "Tip Amount #".$invoice_id;
				$request_data['return_url'] = route('tipPaypalSuccess');
				$request_data['cancel_url'] = route('buyer_orders_details',[$Order->order_no]);
				$request_data['total'] = $tip_amount;

		        $sessionData = [
					'paypal_custom_data' => $request->all()
				];
				

		        $tempData = new BluesnapTempTransaction;
		        $tempData->merchanttransactionid = $invoice_id;
		        $tempData->user_id = $user->id;
		        $tempData->cart_data = json_encode($sessionData);
		        $tempData->payment_for = 2;
		        $tempData->save();
				//return view('frontend.cart.bluesnap_tip_payment_process',compact('request_data','invoice_id'));
				
				/* payment new flow - start */
				$token_string = env('BlueSnapID').':'.env('BlueSnapPassword');
				$token = base64_encode($token_string);
		
				$url = env('BlueSnapParamsEncryptionURL');
				$bluesnapControllerObj = new BluesnapPaymentController();
				$xml = $bluesnapControllerObj->get_xml_params($request_data,$invoice_id,'bluesnap.tipthankyou');
				$headers = array(
					"Content-type: application/xml",
					"Authorization: Basic ".$token,
					"Content-length: " . strlen($xml),
					"Connection: close"
				);
		
				$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL,$url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 10);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($ch, CURLOPT_USERPWD, $token_string);
		
				$enc_params_response = curl_exec($ch); 
				
				if(curl_errno($ch)) {
					\Log::info(curl_error($ch));
					return redirect('404');
				} else {
					curl_close($ch);
				}
				//get token
				if (strpos($enc_params_response, '<encrypted-token>') !== false) {
					$params_token = get_string_between($enc_params_response,"<encrypted-token>","</encrypted-token>");
					//generate payment URL
					$payment_URL = env('BlueSnapCheckoutPageURL')."?enc=".$params_token."&merchantid=".env('BlueSnapMerchantID');
					return redirect($payment_URL);
				} else {
					return redirect()->route('view_cart')->with('errorFails', 'Something went wrong, Please try again');
				}
				/* payment new flow - end */
			}
		}

		\Session::flash('tostError', "Something goes wrong.");
		return redirect()->back();
	}

	public function sendTipProcess($Order,$tip_amount,$fromWalletAmount=0,$txn_id='',$payBy='wallet'){
		$buyer = User::find($Order->uid);
		if($fromWalletAmount > 0){
			$buyer->earning = $buyer->earning - $fromWalletAmount;
			$buyer->pay_from_wallet = $buyer->pay_from_wallet + $fromWalletAmount;
			$buyer->save();
		}
		
		$seller = User::find($Order->seller_uid);
		$seller->earning = $seller->earning + $tip_amount;
		$seller->save();

		$orderTip = new OrderTip;
		$orderTip->buyer_uid = $Order->uid;
		$orderTip->seller_uid = $Order->seller_uid;
		$orderTip->order_id  = $Order->id;
		$orderTip->amount = $tip_amount;
		$orderTip->save();

		/* Buyer Transactions Start */
		$buyerTransaction = new BuyerTransaction;
		$buyerTransaction->order_id = $Order->id;
		$buyerTransaction->buyer_id = $Order->uid;
		if($payBy == 'paypal'){
			$buyerTransaction->note = 'Debit tip amount from Credit Card/Paypal';
			if($fromWalletAmount > 0){
				$buyerTransaction->wallet_amount = $fromWalletAmount;
				$buyerTransaction->paypal_amount = $tip_amount - $buyerTransaction->wallet_amount;
			} else {
				$buyerTransaction->paypal_amount = $tip_amount;
			}
		}
		elseif ($payBy == 'bluesnap'){
			$buyerTransaction->note = 'Debit tip amount from Credit Card';
			$buyerTransaction->creditcard_amount = $tip_amount;
		}
		elseif ($payBy == 'skrill'){
			$buyerTransaction->note = 'Debit tip amount from Skrill';
			if($fromWalletAmount > 0){
				$buyerTransaction->wallet_amount = $fromWalletAmount;
				$buyerTransaction->skrill_amount = $tip_amount - $buyerTransaction->wallet_amount;
			} else {
				$buyerTransaction->skrill_amount = $tip_amount;
			}
		}
		else{
			$buyerTransaction->note = 'Debit tip amount from Wallet';
		}
		if($txn_id){
			$buyerTransaction->transaction_id = $txn_id;
		}
		
		$buyerTransaction->anount = $tip_amount;
		$buyerTransaction->status = 'tip_deposit';
		$buyerTransaction->created_at = time();
		$buyerTransaction->save();
		/* Buyer Transactions End */

		/*SELLER EARNING START*/
        $SellerEarning = new SellerEarning;
        $SellerEarning->order_id = $Order->id;
        $SellerEarning->seller_id = $Order->seller_uid;
        $SellerEarning->note = 'Credit Tip Amount';
        $SellerEarning->anount = $tip_amount;
        $SellerEarning->status = 'tip_credit';
        $SellerEarning->save();
        /*SELLER EARNING END*/

		$data = [
			'receiver_secret' => $seller->secret,
			'email_type' => 1,
            'subject' => 'You Got A Tip From Buyer!',
            'template' => 'frontend.emails.v1.seller_order_tip',
            'email_to' => $seller->email,
            'seller_username' => $seller->username,
			'orderNumber' => $Order->order_no,
			'order' => $Order,
			'buyer_username' => $buyer->username,
			'order_detail_url' => route('seller_orders_details',[$Order->order_no]),
			'tip_amount' => $tip_amount,
        ];
        QueueEmails::dispatch($data, new SendEmailInQueue($data));

        /*Send mail to sub users*/
		/*$userObj = new User;
		$userObj->send_mail_to_subusers('is_order_mail',$seller->id,$data,'seller_username');*/

		/* Send wallet transaction email to admin */
		if($payBy == "wallet"){
			$wallet_transaction_history['buyer'] = $buyer->username;
			$wallet_transaction_history['buyer_email'] = $buyer->email;
			$wallet_transaction_history['seller'] = $seller->username;
			$wallet_transaction_history['seller_email'] = $seller->email;
			$wallet_transaction_history['invoice_id'] = $Order->order_no;
			$wallet_transaction_history['transaction_id'] = $Order->txn_id;
			$wallet_transaction_history['total_amount'] = round($tip_amount,2);
			$wallet_transaction_history['reason'] = "give a tip";
			$wallet_transaction_history['transactions'][] = [
				'title' => "Tip on order #{$Order->order_no}",
				'price' => $tip_amount,
				'quantity' 	=> 1,
				'total' => round($tip_amount,2)
			];
			$Order->sendWalletTransactionEmail($wallet_transaction_history);
		}

	}

	public function tipPaypalSuccess(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('errorFails', get_user_softban_message());
		}

		$user = $this->auth_user;
		$token = $request->get('token');
		$PayerID = $request->get('PayerID');
		
		$response = $this->provider->getExpressCheckoutDetails($token);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($response);
		$log->status = "Payment response";
		$log->payment_for = "order_tip";
		$log->save();

		if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
			return redirect()->route('transactions')->with('errorFails', 'Error processing PayPal payment');
		}

		$invoice_id = $response['INVNUM'];
		$totalAmount = $response['AMT'];
		$inv_desc = $response['DESC'];


		$sessionData = \Session::get($invoice_id);
		$fromWalletAmount = $sessionData['from_wallet'];
		$paypal_custom_data = $sessionData['paypal_custom_data'];

		$order_id = $paypal_custom_data['order_id'];		
		$tip_amount = $paypal_custom_data['tip_amount'];	

		$Order = Order::find($order_id);	

		$request_data = [];
		if ($fromWalletAmount > 0) {
			$request_data['items'] = [
				[
					'name' => "From wallet",
					'price' => "-" . round_price($fromWalletAmount),
					'qty' => 1
				],
				[
					'name' => "Tip Amount",
					'price' => round_price($tip_amount),
					'qty' => 1
				]
			];
		}else{
			$request_data['items'] = [
				[
					'name' => "Tip Amount",
					'price' => $tip_amount,
					'qty' => 1
				]
			];
		}

		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = $inv_desc;
		$request_data['return_url'] = route('tipPaypalSuccess');
		$request_data['cancel_url'] = route('buyer_orders_details',[$Order->order_no]);
		$request_data['total'] = $totalAmount;

		$payment_status = $this->provider->doExpressCheckoutPayment($request_data, $token, $PayerID);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($payment_status);
		$log->status = "Payment response verification";
		$log->payment_for = "order_tip";
		$log->save();

		if ($payment_status['ACK'] == 'Failure') {
			return redirect()->route('buyer_orders_details',[$Order->order_no])->with('errorFails', 'Something went wrong with PayPal');
		}

		$status = $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'];
		$txn_id = $payment_status['PAYMENTINFO_0_TRANSACTIONID'];

		\Session::forget($invoice_id);

		if ($status == 'Completed') {
			$traExists = BuyerTransaction::where('transaction_id',$txn_id)->first();
			if(empty($traExists)){
				$this->sendTipProcess($Order,$tip_amount,$fromWalletAmount,$txn_id,$payBy='paypal');
			}
			\Session::flash('tostSuccess', 'Tip amount credited successfully.');
			return redirect()->route('transactions')->with('errorSuccess','Tip amount credited successfully.');
		}else{
			return redirect()->route('buyer_orders_details',[$Order->order_no])->with('errorFails', 'Error processing PayPal payment');
		}
	}

	public function request_for_revision(Request $request) {
		if(Auth::user()->parent_id != 0){
			\Session::flash('tostError', 'Something went wrong.');
			return redirect()->back();
		}
		
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			\Session::flash('tostError', get_user_softban_message());
			return redirect()->back();
		}

		if(!$request->filled('order_id') || !$request->filled('request_note')) {
			\Session::flash('tostError', 'Description is required.');
			return redirect()->back();
		}
		if(strlen(strip_tags($request->request_note)) <= 0) {
			\Session::flash('tostError', 'Description is required.');
			return redirect()->back();
		}

		$uid = $this->uid;

		$Order = Order::withCount('order_revisions')->where('status','delivered')->where('uid',$uid)->where('is_recurring',0)->where('id',$request->order_id)->first();
		if(is_null($Order)) {
			return redirect('404');
		}

		$total_revision_row = ServicePlan::where(['service_id' => $Order->service_id, 'plan_type' => $Order->plan_type])->select('id','no_of_revisions')->first();
		
		$total_revision_count = $total_revision_row->no_of_revisions;
		if($Order->order_revisions_count < $total_revision_count || $total_revision_count == -1) {
			$order_revisions = new OrderRevisions;
			$order_revisions->order_id = $request->order_id;
			$order_revisions->description = $request->request_note;
			$order_revisions->save();

			// change order status to active
			$Order->status = 'in_revision';
			$Order->save();
			
			/* Send Notification to seller Start */
			$notify_from = $uid;
			if ($notify_from == $Order->uid) {
				$notify_to = $Order->seller_uid;
			} elseif ($notify_from == $Order->seller_uid) {
				$notify_to = $Order->uid;
			}

			$notification = new Notification;
			$notification->notify_to = $notify_to;
			$notification->notify_from = $notify_from;
			$notification->notify_by = 'buyer';
			$notification->order_id = $Order->id;
			$notification->is_read = 0;
			$notification->type = 'revision_request_order';
			$notification->message = 'You Have A New Revision Request for Order On demo! #' . $Order->order_no;
			$notification->created_at = time();
			$notification->updated_at = time();
			$notification->save();
			/* Send Notification to seller End */

			/* Send Email to Seller */
			$seller = User::find($notify_to);
			$buyer = User::find($notify_from);

			$data = [
				'receiver_secret' => $seller->secret,
				'email_type' => 1,
	            'subject' => 'You have a new revision request on demo!',
	            'template' => 'frontend.emails.v1.order_revision',
	            'email_to' => $seller->email,
	            'username' => $seller->username,
				'sellername' => $seller->Name,
				'buyername' => $buyer->Name,
				'orderNumber' => $Order->order_no,
				'order_date' => date_format($Order->created_at,"d M,Y h:m A"),
				'order_revisions' => $order_revisions->description,
	        ];
	        QueueEmails::dispatch($data, new SendEmailInQueue($data));

	        /*Send mail to sub users*/
			$userObj = new User;
			$userObj->send_mail_to_subusers('is_order_mail',$seller->id,$data,'username');

			\Session::flash('tostSuccess', 'Revision request sent successfully.');
			return redirect()->back();
		} else {
			\Session::flash('tostError', 'You have reached at max revision limit.');
			return redirect()->back();
		}
	}

	public function review_reminder(Request $request,$secret) {
		$uid = $this->uid;
		$order_id = Order::getDecryptedId($secret);
        try{
            if(empty($order_id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
		}
		$Order = Order::where('id',$order_id)->where('uid',$uid)->select('id','order_no','status','seller_rating','seller_uid','service_id','is_course')
						->with(['service', 'seller'])
						->first();
		
		$orders = Order::where('uid',$uid)
		//->where('status','completed')
		//->whereRaw('(completed_note is null AND seller_rating = 0)')
		->where('skip_rating',0)
		->whereRaw('( (status = "completed" && completed_note is null AND seller_rating = 0) OR (status = "delivered" and is_recurring = 0) )')
		->sortable()
		->orderBy('orders.id', 'desc')
		->paginate(20);
		
		$serviceLink = route('services_details',[$Order->seller->username, $Order->service->seo_url]);
		$shareComponent = \Share::page($serviceLink)->facebook()->twitter()->whatsapp()->getRawLinks();
		$service_can_share = Order::service_can_share($Order);
		return view('frontend.buyer.review_reminder', compact('orders','Order','shareComponent','service_can_share'));
	}

	public function review_reminder_order(Request $request){
		
		$uid = $this->uid;
		$orders = Order::where('uid',$uid)
		->where('skip_rating',0)
		->whereRaw('( (status = "completed" && completed_note is null AND seller_rating = 0) OR (status = "delivered" and is_recurring = 0) )');
		
		/*Check block by user*/
		// $block_users = User::getBlockedByIds();
		// if(count($block_users) > 0){
		// 	$orders = $orders->whereNotIn('seller_uid',$block_users);
		// }
		
		$orders = $orders->sortable()
		->orderBy('orders.id', 'desc')
		->paginate(20);

		return view('frontend.buyer.review_reminder_order', compact('orders'));
	}
	public function update_seller_rating(Request $request) {
		$uid = $this->uid;
		$order_id = Order::getDecryptedId($request->order_id);
        try{
            if(empty($order_id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
		}
		$order = Order::with(['service', 'seller'])->where('id',$order_id)->where('uid',$uid)->first();
		if(!is_null($order)) {
			$cdate = date('Y-m-d H:i:s');

			$order->seller_rating = $request->seller_rating;
			$order->is_review = 1;
			$order->review_date = $cdate;
			$order->completed_note = $request->complete_note;
			$order->save();

			$service = Service::withoutGlobalScope('is_course')->find($order->service_id);
			$service->total_review_count = $service->total_review_count + 1;
			$service->save();

			/*review log*/
			$review_log = new Order_review_log;
			$review_log->order_id = $order->id;
			$review_log->log = json_encode(array("review" => $request->complete_note, "review_date" => $cdate, "seller_rating" => $request->seller_rating));
			$review_log->save();

			/* Send Review Email To Seller */
			if($request->seller_rating > 0){
				$seller = User::select('id','username','email')->find($order->seller_uid);
				$buyer = User::select('id','username','email')->find($order->uid);

				$orderDetail = Order::with(['seller' => function ($q) {
					$q->select('id', 'Name', 'username');
				}, 'user' => function ($q) {
					$q->select('id', 'Name', 'username');
				}, 'service' => function ($q) {
					$q->select('id', 'title');
				}])->select('id', 'order_no', 'seller_uid',
					'price', 'uid', 'service_id', 'start_date',
					'end_date', 'status', 'order_note', 'is_review',
					'seller_rating','created_at')
				->where('order_no', $order->order_no)->first();

				$data = [
					'receiver_secret' => $seller->secret,
					'email_type' => 1,
					'username' => $seller->username,
					'orderNumber' => $order->order_no,
					'orderDetail' => $orderDetail,
					'name' => $buyer->username,
					'order_detail_url' => url('seller/orders/details/' . $order->order_no),
					'seller_rating' => $request->seller_rating,
					'complete_note' => $request->complete_note,
					'email_to' => $seller->email,
					'subject' => "Your Customer ".$buyer->username." Just Left You A Review For Order #".$order->order_no,
					'template' => 'frontend.emails.v1.review_email',
				];
				QueueEmails::dispatch($data, new SendEmailInQueue($data));
			}
			
			\Session::flash('tostSuccess', 'Your review for order #'.$order->order_no.' stored successfully!');
			$serviceLink = route('services_details',[$order->seller->username, $order->service->seo_url]);
			$shareComponent = \Share::page($serviceLink)->facebook()->twitter()->whatsapp()->getRawLinks();
			$serviceModal = view('frontend.buyer.include.social_media_share_modal',['Order' => $order, 'shareComponent' => $shareComponent])->render();
			$service_can_share = Order::service_can_share($order);
			return response()->json(['status'=>'success', 'serviceModal' => $serviceModal,'service_can_share' => $service_can_share]);
		} else {
			\Session::flash('tostError', 'Something went wrong, Please try again!');
		}
		return response()->json(['status'=>'success']);
	}

	public function upgrade_order_payment(Request $request) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('errorFails', get_user_softban_message());
		}

		$order = Order::upgradeorderstatus()->where('order_no',$request->order_no)->where('uid',Auth::id())->first();
		if(is_null($order)) {
            return redirect()->back();
        }
		$selected_plan = ServicePlan::where('id',$request->plan_id)->first();
		if($selected_plan->service_id != $order->service_id) {
			\Session::flash('errorFails', "Please select valid package.");
			return redirect()->back();
		} else if(check_upgrade_plan_status($order->plan_type, $selected_plan->plan_type) == false) {
			\Session::flash('errorFails', "Please select greater plan than current plan.");
            return redirect()->back();
        } else if($selected_plan->price < $order->plan->price) {
			\Session::flash('errorFails', "Please select plan with greater amount than current plan.");
			return redirect()->back();
		}

		$payable = ($selected_plan->price * $order->qty) - ($order->price * $order->qty);
		if($payable < 0) {
			\Session::flash('errorFails', "Please select plan with greater amount than current plan.");
            return redirect()->back();
		} else if($payable == 0) {
			$paypalControllerObj = new PaypalPaymentController();
			$txn_id = $paypalControllerObj->generate_txnid();
            $paypalControllerObj->upgradeOrder($order,$selected_plan,0,$txn_id,$payBy='wallet',0,0,null,'Completed');
            return redirect()->route('buyer_orders_details',$order->order_no)->with('errorSuccess','Order upgraded successfully');
        }
	
		if(Auth::user()->earning == 0){
			$fromWalletAmount = 0;
		}elseif(Auth::user()->earning >= $payable){
			$fromWalletAmount = $payable;
		}else{
			$fromWalletAmount = Auth::user()->earning;
		}

		if(Auth::user()->promotional_fund == 0){
			$fromPromotionalAmount = 0;
		}elseif(Auth::user()->promotional_fund >= $payable){
			$fromPromotionalAmount = $payable;
		}else{
			$fromPromotionalAmount = Auth::user()->promotional_fund;
		}

		$image_url = url('public/frontend/assets/img/No-image-found.jpg');
        if(isset($order->service->images[0])){
			if(!is_null($order->service->images[0]->thumbnail_media_url)) {
				$image_url = $order->service->images[0]->thumbnail_media_url;
			} else if($order->service->images[0]->photo_s3_key != ''){
				$image_url = $order->service->images[0]->media_url; 
			}else{
				$image_url = url('public/services/images/'.$order->service->images[0]->media_url); 
			}

        }
        $order->image_url = $image_url;
		
		return view('frontend.order_upgrade.payment_details',compact('order','selected_plan','payable','fromWalletAmount','fromPromotionalAmount'));
	}

	public function upgrade_order_payment_options(Request $request) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('errorFails', get_user_softban_message());
		}
		
		$order = Order::upgradeorderstatus()->where('order_no',$request->order_no)->where('uid',Auth::id())->first();
		if(is_null($order)) {
            return redirect()->back();
        }
		$selected_plan = ServicePlan::where('id',$request->plan_id)->first();
		if($selected_plan->service_id != $order->service_id) {
			\Session::flash('errorFails', "Please select valid package.");
			return redirect()->back();
		} else if(check_upgrade_plan_status($order->plan_type, $selected_plan->plan_type) == false) {
			\Session::flash('errorFails', "Please select greater plan than current plan.");
            return redirect()->back();
        } else if($selected_plan->price < $order->plan->price) {
			\Session::flash('errorFails', "Please select plan with greater amount than current plan.");
			return redirect()->back();
		}

		$payable = ($selected_plan->price * $order->qty) - ($order->price * $order->qty);
		if($payable <= 0) {
			\Session::flash('errorFails', "Please select plan with greater amount than current plan.");
            return redirect()->back();
		}
	
		if(Auth::user()->earning == 0){
			$fromWalletAmount = 0;
		}elseif(Auth::user()->earning >= $payable){
			$fromWalletAmount = $payable;
		}else{
			$fromWalletAmount = Auth::user()->earning;
		}

		if(Auth::user()->promotional_fund == 0){
			$fromPromotionalAmount = 0;
		}elseif(Auth::user()->promotional_fund >= $payable){
			$fromPromotionalAmount = $payable;
		}else{
			$fromPromotionalAmount = Auth::user()->promotional_fund;
		}

		$is_recurring_service = 0;
		if($order->service->is_recurring == 1) {
			$is_recurring_service = 1;
		}
		$service_id_list = [];
		array_push($service_id_list,$order->service->id);
		
		return view('frontend.order_upgrade.payment_options',compact('order','selected_plan','payable','fromWalletAmount','fromPromotionalAmount','is_recurring_service','service_id_list'));
	}

	public function order_submit_requirements(Request $request,$order_no) {
		/* Check Start order permission */ 
		if(Auth::user()->check_sub_user_permission('can_start_order') == false){
			return redirect()->route('home');
		}

		$defaultQuestion = Order::where('order_no', $order_no)->whereIn('status',['new','on_hold'])->where('uid',$this->uid)->first();
		if(isset($defaultQuestion)) {
			if($defaultQuestion->status == 'new') {
				$questions = ServiceQuestion::where('service_id', $defaultQuestion->service_id)->get();
				$UserFiles = UserFile::with('user')->where(['order_id' => $defaultQuestion->id])->orderBy('id', 'DESC')->paginate(10);
				$UserFiles->withPath(route('getallfiles'));
			
				return view('frontend.new_cart.submit_requirements', compact('defaultQuestion', 'questions','UserFiles'));
			} else {
				return redirect()->route('buyer_orders_details',$defaultQuestion->order_no);
			}
		} else {
			return redirect()->back();
		}
	}

	/*Skip Rating*/
	public function skip_rating($order_secret){
		$uid = $this->uid;
		$id = Order::getDecryptedId($order_secret);
		$orders = Order::select('id','skip_rating')->whereId($id)->whereUid($uid)->whereStatus('completed')->first();
		if($orders){
			$orders->skip_rating = 1;
			$orders->save();
			return response()->json(['status'=>200,'message'=>'success']);
		}
		return response()->json(['status'=>400,'message'=>'Something went wrong.']);
	}

	/* Course order */
	function course_order_details($order_no){
	
		$uid = $this->uid;
		$block_users = User::getBlockedByIds();

		if (trim($order_no) == "") {
			return redirect(route('buyer_orders'));
		}

		$order = Order::withoutGlobalScope('parent_id')
		->with(['service.images', 'seller', "review_log", 'dispute_order'])
		->where(['uid' => $uid, 'order_no' => $order_no,'is_course' => 1])
		->first();

		if(empty($order)){
			return redirect(route('buyer_orders'));
		}

		if($order->parent_id != 0){
			return redirect()->route('buyer_orders_details',[$order->parent->order_no]);
		}

		//Check Active Course 
		$purchaseDetails = Service::purchaseCourseDetails($order->service_id,$uid);
		if(empty($purchaseDetails)){
			return redirect(route('buyer_orders'));
		}

		// Update buyer order seen flag
		if($order->is_seen_buyer == 0){
			$order->is_seen_buyer = 1;
			$order->save();
		}
		
		/* Is read notification */ 
		$notification = Notification::where(['notify_to' => $uid, 'order_id' => $order->id, 'is_read' => 0])->update(['is_read' => 1]);

		$message = Message::where('service_id', $order->service_id)
		->where('order_id', $order->id)
		->first();

		if (!empty($message)) {
			$message_detail = MessageDetail::with('toUser', 'fromUser')->where('msg_id', $message->id)->get();
			$msg_id = $message->id;
		} else {
			$message_detail = '';
			$msg_id = '';
		}

		$course_plan = ServicePlan::where('service_id', $order->service_id)
		->where('plan_type', $order->plan_type)
		->first();

		$curr= date('Y-m-d H:i:s');
		$to = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $order->created_at);
		$from = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s',$curr);
		$diff_in_days = $to->diffInDays($from);

		if($diff_in_days >= 30){
			$is_cancel = false;
		}else{
			$is_cancel = true;	
		}

		/* Get Course */ 
		$course = Service::withoutGlobalScope('is_course')->where('is_course',1)->find($order->service_id);
		/* Get Course Section */ 
		$course_sections = CourseSection::where('course_id',$course->id)->orderBy('short_by','ASC')->get();
		/* Get First Content Media */ 
		$learn_course_content = LearnCourseContent::select('content_media_id','duration')->where('user_id',$uid)->where('course_id',$course->id)->where('status',1)->first();
		/* Completed Learn Course contents */ 
		$completed_learn_content_ids = LearnCourseContent::select('content_media_id')->where('user_id',$uid)->where('course_id',$course->id)->where('completed_status',1)->pluck('content_media_id')->toArray();

		$query = ContentMedia::where('course_id',$course->id);
		$active_content_media = null;
		$active_content_duration = 0.00;
		/* Active course content */
		if($learn_course_content){
			$active_content_duration = $learn_course_content->duration;
			$active_content_media = $query->where('id',$learn_course_content->content_media_id)->orderBy('short_by','ASC')->first(); 
		}
		if(is_null($active_content_media)){
			$active_content_media = $query->orderBy('short_by','ASC')->first();
			if(!empty($active_content_media)){
				LearnCourseContent::update_active_content_status($uid,$course->id,$active_content_media->id);
			}
		}

		/* Course User */
		$course_user = $course->user;
		/* total no of students */ 
		$no_of_students = Order::distinct('uid')->select('id')->where('status','!=','cancelled')->where('is_course',1)->where('seller_uid',$course_user->id)->count();
		/* Total no of course */
		$no_of_courses = Service::withoutGlobalScope('is_course')->where('is_course',1)
			->where('status','active')->where('is_approved',1)->where('is_delete',0)
			->where('uid',$course->uid)->select('id')->count();

		/* Review and rating */
		$Comment = Order::select('id', 'uid', 'seller_uid','service_id', 'completed_note', 'review_date', 'plan_type', 'package_name', 'seller_rating', 'completed_reply','status','cancel_date','helpful_count','is_review_edition');
		
		$Comment = $Comment->whereIn('status',['cancelled','completed'])
		->where('seller_rating', '>' ,0)
		->where('service_id',$course->id);
		$Comment = $Comment->orderBy('review_date','desc');
		$Comment = $Comment->paginate(10);
		$Comment->withPath(route('course.get_all_review'));
		$CommentCount = Order::select('id')->where(['service_id' => $course->id, 'is_review' => 1])->whereIn('status',['cancelled','completed'])->count();

		$ratingModel = new Order;
		$avg_service_rating = $ratingModel->calculateServiceAverageRating($course->id);
		$avg_seller_rating = $ratingModel->calculateSellerAverageRating($course->uid);

		$total_seller_rating = $ratingModel->getReviewTotal($course_user->id);

		/* check for allow to upgrade order */
		$allow_upgrade_order = allow_to_upgrade_order($order);

		/* Detect Device */
		$agent = new Agent();
		$is_mobile_device = $agent->isMobile();

		// Other courses you may like
		$other_related_courses = Service::withoutGlobalScope('is_course')
		->with(['user:id,username,profile_photo,photo_s3_key,seller_level'])
		->select(
			'service_plan.price','services.id','services.uid','services.title','services.subtitle','services.seo_url',
			'services.descriptions','services.service_rating','services.total_review_count'
			)
		->join('users', 'services.uid', '=', 'users.id')
		->join('service_plan', 'service_plan.service_id', '=', 'services.id')
		 ->where([
		 	'services.is_course'=> 1,
			'services.status'=> 'active',
			'services.is_private'=> 0,
			'services.is_approved'=> 1,
			'services.is_delete'=> 0,
			'services.category_id'=> $course->category_id,
		 	'service_plan.plan_type'=> 'lifetime_access',
			'users.status'=> 1,
			'users.is_delete'=> 0,
			'users.vacation_mode'=> 0,
		 ])
		->where('services.uid','!=',$uid);

		if(count($block_users)>0){
			$other_related_courses = $other_related_courses->whereNotIn('services.uid', $block_users);
		}
		$Order = $order;
		$other_related_courses = $other_related_courses->orderBy('services.service_rating','desc')->limit(10)->get();
		$serviceLink = route('course_details',[$course_user->username,$course->seo_url]);
		$shareComponent = \Share::page($serviceLink)->facebook()->twitter()->whatsapp()->getRawLinks(); 
		$service_can_share = Order::service_can_share($order);
		return view('frontend.buyer.course_order_details', compact('order', 'message_detail', 'msg_id',  'course_plan', 'is_cancel','allow_upgrade_order','course','course_sections','CommentCount','avg_service_rating','avg_seller_rating','Comment','no_of_courses','course_user','no_of_students','total_seller_rating','active_content_media','is_mobile_device','completed_learn_content_ids','active_content_duration','other_related_courses','shareComponent','Order','service_can_share'));
	}

	function update_learn_course_content(Request $request){
		$uid = $this->uid;
		$course_id = Service::getDecryptedId($request->course_id);
		$content_media_id = ContentMedia::getDecryptedId($request->content_media_id);
		$learn_content = LearnCourseContent::update_learn_course_content($uid,$course_id,$content_media_id);
		return response()->json(['status'=>true,'code'=>200,'payload'=>['course_id'=>$request->course_id,'content_media_id'=>$request->content_media_id],'message'=>'Learn course content updated successfully.']);
	}
	
	/* download all media */
	public function donwload_all_media($order_id){
		$order_id = Order::getDecryptedId($order_id);
		$seller_works = SellerWork::where('order_id',$order_id)->get();
		if(count($seller_works) == 0){
			return redirect()->back()->with('tostError','No files found');
		}
		$fileName = 'seller/upload-work/delivered_files_'.date('YmdHis').'_'.rand().'.zip';
        $zip = new Filesystem(new Adapter(public_path($fileName)));
        foreach($seller_works as $file){
			$bucket = env('bucket_order');
			$file_link = "https://".$bucket.".s3.amazonaws.com/".$file->photo_s3_key;
			$files = explode('/',$file->photo_s3_key);
			$file_name = $files[1];
            $zip->put($file_name, file_get_contents($file_link));
        }
        $zip->getAdapter()->getArchive()->close();

		header('Content-type: application/zip');
		header('Content-Disposition: attachment; filename="'.basename($fileName).'"');
		header("Content-length: " . filesize(public_path($fileName)));
		header("Pragma: no-cache");
		header("Expires: 0");
		
		ob_clean();
		flush();

		readfile(public_path($fileName));

		ignore_user_abort(true);
		unlink(public_path($fileName));
	}

	/* Course review and rating Submit */ 
	public function course_rating_and_review(Request $request,$order_secret) {
		$uid = $this->uid;
		$order_id = Order::getDecryptedId($order_secret);
		if(empty($order_id)){
			return redirect()->back();
		}
		$order = Order::where('id',$order_id)->where('uid',$uid)->where('is_course',1)->first();
		if(!is_null($order)) {
			$cdate = date('Y-m-d H:i:s');

			$order->seller_rating = $request->seller_rating;
			$order->is_review = 1;
			$order->review_date = $cdate;
			$order->completed_note = $request->complete_note;
			$order->save();

			$service = Service::withoutGlobalScope('is_course')->where('is_course',1)->find($order->service_id);
			$service->total_review_count = $service->total_review_count + 1;
			$service->save();

			/*review log*/
			$review_log = new Order_review_log;
			$review_log->order_id = $order->id;
			$review_log->log = json_encode(array("review" => $request->complete_note, "review_date" => $cdate, "seller_rating" => $request->seller_rating));
			$review_log->save();

			/* Send Review Email To Seller */
			if($request->seller_rating > 0){
				$seller = User::select('id','username','email')->find($order->seller_uid);
				$buyer = User::select('id','username','email')->find($order->uid);

				$orderDetail = Order::with(['seller' => function ($q) {
					$q->select('id', 'Name', 'username');
				}, 'user' => function ($q) {
					$q->select('id', 'Name', 'username');
				}, 'service' => function ($q) {
					$q->select('id', 'title');
				}])->select('id', 'order_no', 'seller_uid',
					'price', 'uid', 'service_id', 'start_date',
					'end_date', 'status', 'order_note', 'is_review',
					'seller_rating','created_at')
				->where('order_no', $order->order_no)->first();

				$data = [
					'receiver_secret' => $seller->secret,
					'email_type' => 1,
					'username' => $seller->username,
					'orderNumber' => $order->order_no,
					'orderDetail' => $orderDetail,
					'name' => $buyer->username,
					'order_detail_url' => url('seller/orders/details/' . $order->order_no),
					'seller_rating' => $request->seller_rating,
					'complete_note' => $request->complete_note,
					'email_to' => $seller->email,
					'subject' => "Your Customer ".$buyer->username." Just Left You A Review For Order #".$order->order_no,
					'template' => 'frontend.emails.v1.review_email',
				];
				QueueEmails::dispatch($data, new SendEmailInQueue($data));
			}
			$buyer_order_params = ['id' => $order->order_no];
			if ($request->seller_rating > 3) {
				$buyer_order_params += ['is_share' => 1];
			}
			\Session::flash('tostSuccess', 'Your review for order #'.$order->order_no.' stored successfully!');
			return redirect()->route('buyer_orders_details',$buyer_order_params);
		}
		
		\Session::flash('tostError', 'Something went wrong, Please try again!');
		return redirect()->back();
	}

	/**
     * get buyer courses .
     *
     * @return \Illuminate\Http\Response
     */
	function myCourses(Request $request) {
		$status = $request->input('status');
		$search = $request->input('search');
		$created_by_filter = isset($request->created_by_filter) ? User::getDecryptedId($request->created_by_filter) : null; 
		$from_date = $request->input('from_date');
		$to_date = $request->input('to_date');
		$filterbydate=$request->input('filterbydate');
		$total_filter_tags=$request->total_filter_tags ?? '';
		$ordertags = [];
		if(strlen($total_filter_tags) > 0) {
			$ordertags = explode(',',$total_filter_tags);
		}

		//$uid = $this->uid;
		$uid = $this->uid;
		$cdate = date('Y-m-d H:i:s');

		if ($status) {
			if ($status == 'late') {
				$Order = Order::where(['orders.uid' => $uid])
				->where('orders.status', 'active')
				->where('orders.is_recurring',0)
				->whereRaw("((delivered_date is null and end_date < '" . $cdate . "') OR (delivered_date is not null and delivered_date > end_date))");
			}
			else if($status == 'recursive' && $request->filled('from_header'))
			{
				$before3Days = Date("Y-m-d",strtotime(Carbon::now()->subDays(3)));
				$Order = Order::whereIn('orders.status', ['active','delivered','in_revision'])
				->whereHas('subscription',function($q) use ($before3Days){
					$q->whereDate('expiry_date','>=',$before3Days);
				})
				->where('orders.uid', $uid)
				->where('orders.is_recurring', 1);
			} else {
				$Order = Order::where(['orders.uid' => $uid, 'orders.status' => $status]);
			}
		} 
		else 
		{
			$Order = Order::where(['orders.uid' => $uid]);
		}

		$Order = $Order->where('orders.is_course',1);
		
		if (!empty($search)) {
			$Order = $Order->where(function($q) use($search){
				$q->whereHas('service', function($q1) use($search) {
					$q1->where('title', 'like', '%' . $search . '%');
				})->orWhereHas('seller',function($q1) use ($search){
					$q1->where('username', 'like', '%' . $search . '%');
				})->orWhere('order_no', 'like', '%' . $search . '%');
			});
		}
		if(!empty($created_by_filter))
		{ 
			$Order = $Order->where('created_by', $created_by_filter);
		}

		if (!empty($filterbydate)) {
			$now = Carbon::now();
			$startDate = $now;
			$endDate = $now->addDay(1)->format('Y-m-d');
			if($filterbydate == 'week'){
				$startDate = $now->startOfWeek()->format('Y-m-d');
			}elseif($filterbydate == 'month'){
				$startDate = $now->firstOfMonth()->format('Y-m-d');
			}elseif($filterbydate == 'year'){
				$startDate = $now->startOfYear()->format('Y-m-d');
			}elseif($filterbydate == 'custom'){
				if ((!empty($from_date)) && (!empty($to_date))) {
					$startDate = Carbon::createFromFormat('m/d/Y', $from_date)->format('Y-m-d');
					$endDate = Carbon::createFromFormat('m/d/Y', $to_date);
					$endDate = $endDate->addDay(1)->format('Y-m-d');
				}
			}
			$Order = $Order->whereBetween('end_date', [$startDate, $endDate]);
		}

		if(count($ordertags) > 0) {
			$order_ids = BuyerOrderTagDetails::whereIn('tag_id',$ordertags)->pluck('order_id');
			$Order = $Order->whereIn('orders.id',$order_ids);
		}

		$Order = $Order->join('services', 'services.id', 'orders.service_id')
		->join('users', 'users.id', 'orders.seller_uid')
		->select('orders.*', 'services.title', 'services.descriptions');

		$Order = $Order->with(['order_extend_requests' => function($q) {
			$q->where('is_accepted', '0');
		}])->sortable();
		
		$Order = $Order->orderBy('id', 'desc')->paginate(20)->appends($request->all());

		foreach ($Order as $key => $value) {
			$added_tags = [];
			foreach ($value->taglist as $k => $v) {
				array_push($added_tags,$v->tag_id);
			}
			$value->added_tags = $added_tags;
		}

		$CountOrder['active_order'] = Order::where(['uid' => $uid, 'status' => 'active'])->count();
		$CountOrder['late_order'] = Order::where(['uid' => $uid])
		->where('status', 'active')
		->whereRaw("((delivered_date is null and end_date < '" . $cdate . "') OR (delivered_date is not null and delivered_date > end_date))")
		->count();

		$CountOrder['delivered_order'] = Order::where(['uid' => $uid, 'status' => 'delivered'])->count();
		$CountOrder['completed_order'] = Order::where(['uid' => $uid, 'status' => 'completed'])->count();
		$CountOrder['cancelled_order'] = Order::where(['uid' => $uid, 'status' => 'cancelled'])->count();

		$OrderTags = BuyerOrderTags::where('buyer_id',$this->uid)/* ->whereNotNull('order_ids') */->select('tag_name','id')->get();

		$MostUsedOrderTags = BuyerOrderTags::where('buyer_id',$this->uid)
									->select('tag_name','id')
									->withCount('tag_orders')
									->orderBy('tag_orders_count','desc')
									->limit(10)
									->get();
		$parent_id = Auth::user()->parent_id;
		if($parent_id == 0)
		{
			$parent_id = Auth::user()->id;
		}
		$subusers = User::where('parent_id',$parent_id)->orWhere('id',$parent_id)->get()->pluck('Name','secret')->toArray();
		if($request->ajax()){
			$view = view('frontend.buyer.include.order_list', compact('Order', 'CountOrder','OrderTags','MostUsedOrderTags'))->render();
			return response()->json(['status'=>200,'html'=>$view]);
		}
		return view('frontend.buyer.mycourses', compact('Order', 'CountOrder','OrderTags','MostUsedOrderTags','subusers'));
	}
}