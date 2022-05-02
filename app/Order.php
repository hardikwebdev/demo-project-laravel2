<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Commands\SortableTrait;
use Auth;
use App\Service;
use App\AffiliateEarning;
use App\Specialaffiliatedusers;
use App\ReviewFeedback;
use DateTime;
use App\Notification;
use App\BuyerTransaction;
use App\SellerEarning;
use App\User;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use App\OrderSubscription;
use DB;
use App\Http\Controllers\BluesnapPaymentController;
use App\Http\Controllers\PaypalPaymentController;
use Carbon\Carbon;

class Order extends Model {

	public $table = 'orders';
	use SortableTrait;

	/**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('parent_id', function (Builder $builder) {
            $builder->where('orders.parent_id', 0);
        });

		/* ************************
		Use without globle scop as below 
		Order::withoutGlobalScope('parent_id')->first(); 
		***************************/
    }

	public function user() {
		return $this->belongsTo('App\User', 'uid', 'id');
	}

	public function created_by_user() {
		return $this->belongsTo('App\User', 'created_by', 'id');
	}

	public function parent() {
		return $this->hasOne('App\Order','id','parent_id')->select('id','order_no','delivered_note');
	}

	public function child() {
		return $this->hasMany('App\Order','parent_id','id')->withoutGlobalScope('parent_id')->where('parent_id','!=',0)->orderBy('id','ASC');
	}

	public function latest_child() {
		return $this->hasOne('App\Order','parent_id','id')->withoutGlobalScope('parent_id')->select('id','order_no','parent_id')->where('parent_id','!=',0)->orderBy('id','DESC');
	}

	//Check child order is latest child order or not
	public function is_latest_child_order($Order){
		$is_latest_child_order = false;
		if($Order->is_recurring == 1 && $Order->parent_id != 0){
			// check is last child ?
			$parentOrder = $this->select('id','parent_id')->where('id',$Order->parent_id)->first();
			if(!empty($parentOrder->latest_child)){
				if($parentOrder->latest_child->order_no == $Order->order_no){
					$is_latest_child_order = true;
				}
			}
		}
		return $is_latest_child_order;
	}

	public function affiliate() {
		return $this->belongsTo('App\User','affiliate_id', 'id');
	}

	public function subscription() {
		return $this->hasOne('App\OrderSubscription', 'order_id', 'id');
	}

	public function subscription_history() {
		return $this->hasMany('App\OrderSubscriptionHistory', 'order_id', 'id');
	}

	public function plan() {
		return $this->hasOne('App\ServicePlan', 'plan_type', 'plan_type')->where('service_id',$this->service_id);
	}

	/* Seller */
	public function seller() {
		return $this->belongsTo('App\User', 'seller_uid', 'id');
	}

	public function service() {
		return $this->belongsTo('App\Service', 'service_id', 'id')->withoutGlobalScope('is_course');
	}

	public function active_service(){
		return $this->belongsTo('App\Service', 'service_id', 'id')->where('status','active')->withoutGlobalScope('is_course');
	}

	public function extra() {
		return $this->hasMany('App\OrderExtra', 'order_id', 'id');
	}

	public function seller_work() {
		return $this->hasMany('App\SellerWork', 'order_id', 'id');
	}

	public function review_log() {
		return $this->hasMany('App\Order_review_log', 'order_id', 'id');
	}

	public function coupon_applied(){
		return $this->hasOne('App\CoupanApplied','order_id','id');
	}

	public function order_tip(){
		return $this->hasOne('App\OrderTip','order_id','id');
	}

	public function  order_extend_requests(){
		return $this->hasMany('App\OrderExtendRequest', 'order_id', 'id');
	}

	public function dispute_order(){
		return $this->belongsTo('App\DisputeOrder','order_no','order_no');
	}

    public function message(){
        return $this->hasOne('App\Message', 'order_id', 'id');
    }

    public function track_order_history()
    {
        return $this->hasMany('App\TrackOrderChange', 'order_id', 'id');
    }

	public function displaySellerRating($seller_rating = 0, $size = '15', $width = '50px' ,$showFiveStar = 0) {

		$rating_star = '';
		if($showFiveStar){
			for ($i = 1; $i <= 5; $i++) {
				if ($i <= $seller_rating) {
					$rating_star .= '<li class="rating-item">
					<svg class="svg-star" style="width:' . $size . 'px;height:' . $size . 'px;">
					<use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-star"></use>
					</svg>
					</li>';
					$color = '#ffc000';
				} else {
					$rating_star .= '<li class="rating-item empty">
					<svg class="svg-star" style="width:' . $size . 'px;height:' . $size . 'px;">
					<use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-star"></use>
					</svg>
					</li>';
					$color = '#cfcfcf';
				}
				$returnData = '<ul class="rating" style="width: ' . $width . ';margin: 0 auto;">' . $rating_star . '</ul>';
			}
		}else{
			if($seller_rating != 0){
				$rating_star .= '<li class="rating-item">
				<svg class="svg-star" style="width:' . $size . 'px;height:' . $size . 'px;">
				<use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-star"></use>
				</svg>
				</li>';
				$color = '#ffc000';
			} else {
				$rating_star .= '<li class="rating-item empty">
				<svg class="svg-star" style="width:' . $size . 'px;height:' . $size . 'px;">
				<use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-star"></use>
				</svg>
				</li>';
				$color = '#cfcfcf';
			}
			$returnData = '<ul class="rating" style="width: ' . $width . ';margin: 0 auto;">' . $rating_star . '<span style="font-family: Titillium Web,sans-seriz; color:'.$color.';font-weight: 700;">'.number_format($seller_rating,'1','.',',').'</span></ul>';
		}


		return $returnData;
	}

	public function displaySellerRating_old($seller_rating = 0, $size = '11', $width = '75px') {

		$rating_star = '';
		for ($i = 1; $i <= 5; $i++) {
			if ($i <= $seller_rating) {
				$rating_star .= '<li class="rating-item">
				<svg class="svg-star" style="width:' . $size . 'px;height:' . $size . 'px;">
				<use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-star"></use>
				</svg>
				</li>';
			} else {
				$rating_star .= '<li class="rating-item empty">
				<svg class="svg-star" style="width:' . $size . 'px;height:' . $size . 'px;">
				<use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#svg-star"></use>
				</svg>
				</li>';
			}
		}
		return '<ul class="rating" style="width: ' . $width . ';margin: 0 auto;">' . $rating_star . '</ul>';
	}

	public function calculateServiceAverageRating($service_id) {
		$no_users = $this->select('id')->where(['service_id' => $service_id])
		->where('seller_rating', '!=', 0)
		->where("is_review", 1)
		->whereRaw("((status = 'completed') OR (status = 'cancelled' AND seller_rating = 1))")
		->count();

		/* $sum_rating =  $this->where(['service_id'=>$service_id,'status' => 'completed'])->sum('seller_rating'); */

		$fiveStar = $this->select('id')->where(['service_id' => $service_id, 'status' => 'completed', 'seller_rating' => 5, "is_review" => 1])->count();

		$fourStar = $this->select('id')->where(['service_id' => $service_id, 'status' => 'completed', 'seller_rating' => 4, "is_review" => 1])->count();

		$threeStar = $this->select('id')->where(['service_id' => $service_id, 'status' => 'completed', 'seller_rating' => 3, "is_review" => 1])->count();

		$twoStar = $this->select('id')->where(['service_id' => $service_id, 'status' => 'completed', 'seller_rating' => 2, "is_review" => 1])->count();

		$oneStar = $this->select('id')->where(['service_id' => $service_id, 'seller_rating' => 1, "is_review" => 1])->whereIn('status',['completed','cancelled'])->count();

		$sum_rating = ($fiveStar * 5 + $fourStar * 4 + $threeStar * 3 + $twoStar * 2 + $oneStar * 1);

		$rating = 0;
		if ($no_users > 0) {
			$rating = $sum_rating / $no_users;
		}
		return $rating;
	}

	public function calculateSellerAverageRating($seller_id) {

		$no_users = $this->select('id')->where(['seller_uid' => $seller_id])
		->where('seller_rating', '!=', 0)
		->where("is_review", 1)
		->whereRaw("((status = 'completed') OR (status = 'cancelled' AND seller_rating = 1))")
		->count();

		$fiveStar = $this->select('id')->where(['seller_uid' => $seller_id, 'status' => 'completed', 'seller_rating' => 5, "is_review" => 1])->count();

		$fourStar = $this->select('id')->where(['seller_uid' => $seller_id, 'status' => 'completed', 'seller_rating' => 4, "is_review" => 1])->count();

		$threeStar = $this->select('id')->where(['seller_uid' => $seller_id, 'status' => 'completed', 'seller_rating' => 3, "is_review" => 1])->count();

		$twoStar = $this->select('id')->where(['seller_uid' => $seller_id, 'status' => 'completed', 'seller_rating' => 2, "is_review" => 1])->count();

		$oneStar = $this->select('id')->where(['seller_uid' => $seller_id, 'seller_rating' => 1, "is_review" => 1])->whereIn('status',['completed','cancelled'])->count();

		$sum_rating = ($fiveStar * 5 + $fourStar * 4 + $threeStar * 3 + $twoStar * 2 + $oneStar * 1);

		$rating = 0;
		if ($no_users > 0) {
			$rating = $sum_rating / $no_users;
		}
		return $rating;
	}

	public static function calculateSellerAverageRatingCheck($seller_id) {

		$no_users = Order::select('id')->where(['seller_uid' => $seller_id])
		->where('seller_rating', '!=', 0)
		->where("is_review", 1)
		->whereRaw("((status = 'completed') OR (status = 'cancelled' AND seller_rating = 1))")
		->count();

		$fiveStar = Order::select('id')->where(['seller_uid' => $seller_id, 'status' => 'completed', 'seller_rating' => 5, "is_review" => 1])->count();

		$fourStar = Order::select('id')->where(['seller_uid' => $seller_id, 'status' => 'completed', 'seller_rating' => 4, "is_review" => 1])->count();

		$threeStar = Order::select('id')->where(['seller_uid' => $seller_id, 'status' => 'completed', 'seller_rating' => 3, "is_review" => 1])->count();

		$twoStar = Order::select('id')->where(['seller_uid' => $seller_id, 'status' => 'completed', 'seller_rating' => 2, "is_review" => 1])->count();

		$oneStar = Order::select('id')->where(['seller_uid' => $seller_id, 'seller_rating' => 1, "is_review" => 1])->whereIn('status',['completed','cancelled'])->count();

		$sum_rating = ($fiveStar * 5 + $fourStar * 4 + $threeStar * 3 + $twoStar * 2 + $oneStar * 1);

		$rating = 0;
		if ($no_users > 0) {
			$rating = $sum_rating / $no_users;
		}

		return $rating;
	}

	function get_order_detail($order_no) {

		$OrderT = $this->with('service', 'user', 'seller', 'extra')->where('order_no', $order_no)->get();

		$tableBody = '';
		foreach ($OrderT as $row) {
			$extra_price = 0;

			if (isset($row->extra)) {
				foreach ($row->extra as $extra) {
					$extra_price += $extra->price * $extra->qty;
				}
			}
			$totalAmount = ($row->price * $row->qty) + $extra_price;

			$tableBody .= '<tr>
			<td align="center" valign="top">
			' . $row->user->username . '
			</td>
			<td align="center" valign="top">
			' . $row->seller->username . '
			</td>
			<td align="center" valign="top">
			' . $row->txn_id . '
			</td>
			<td align="center" valign="top">
			' . $row->service->title . '
			</td>
			<td align="center" valign="top">
			' . $row->start_date . '
			</td>
			<td align="center" valign="top">
			' . $row->end_date . '
			</td>
			<td align="center" valign="top">
			$' . $totalAmount . '
			</td>
			</tr>';
		}

		return '<table border="1" cellpadding="0" cellspacing="0" height="100%" width="600" id="bodyTable">
		<tr>
		<td align="center" valign="top">
		<table border="0" cellpadding="20" cellspacing="0" width="600" id="emailContainer">
		<tr>
		<th align="center" valign="top" style="background-color: #e8e5e5;">
		Buyer
		</th>
		<th align="center" valign="top" style="background-color: #e8e5e5;">
		Seller
		</th>
		<th align="center" valign="top" style="background-color: #e8e5e5;">
		Txn ID
		</th>
		<th align="center" valign="top" style="background-color: #e8e5e5;">
		Service
		</th>
		<th align="center" valign="top" style="background-color: #e8e5e5;">
		Order Date
		</th>
		<th align="center" valign="top" style="background-color: #e8e5e5;">
		Due Date
		</th>
		<th align="center" valign="top" style="background-color: #e8e5e5;">
		Amount
		</th>
		</tr>
		' . $tableBody . '
		</table>
		</td>
		</tr>
		</table>';
	}

	public static function getReviewTotal($seller_id)
	{
		$no_users = Order::select('id')->where(['seller_uid' => $seller_id, 'status' => 'completed'])->where('seller_rating', '!=', 0)->where("is_review", 1)->count();
		return $no_users;
	}

	
	public function complete_order($orderId, $type = "") {

		if ($orderId) {
			$specialAffiliateFlag = 0;
			$affiliate_per = 15;
			$cdate = date('Y-m-d H:i:s');
			$Order = Order::where(['id' => $orderId, 'status' => 'delivered','is_pause' => 0,'is_course' => 0])
			->first();

			if (empty($Order)) {
				return false;
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
				
				app('App\Http\Controllers\PaypalPaymentController')->cancelPremiumOrder($Order->subscription->profile_id);

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
			if ($type == 'admin') {
				/*for admin*/
				$Order->admin_completed = "1";
			} else {
				/*for cron*/
				$Order->admin_completed = '2';
			}
			$Order->service_charge = $serviceCharge;
			$Order->completed_date = $cdate;
			$Order->is_review = 0;
			$Order->save();

			//Update last child recurrence (complete order process)
			if($Order->is_recurring == 1){
				app('App\Http\Controllers\PaypalPaymentController')->updateLastChildRecurrence($Order);
			}

			$earning = $product_price - $serviceCharge;
			$User = User::find($Order->seller_uid);

			//For recurring order net income will add only on seller payment receive
			if($Order->is_recurring == 0){
				if ($Order->is_affiliate == "1") {
					$affiliate_income = ($product_price * $affiliate_per) / 100;
					$User->freeze = $User->freeze + ($earning - $affiliate_income);
					$User->net_income = $User->net_income + ($earning - $affiliate_income);
				} else {
					$User->freeze = $User->freeze + $earning;
					$User->net_income = $User->net_income + $earning;
				}
				$User->save();
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
			$notify_from = 0;
			$notify_to = $Order->uid;

			$notification = new Notification;
			$notification->notify_to = $notify_to;
			$notification->notify_from = $notify_from;
			$notification->notify_by = 'admin';
			$notification->order_id = $Order->id;
			$notification->is_read = 0;
			$notification->type = 'complete_order';
			$notification->message = 'Order #' . $Order->order_no . ' has completed';
			$notification->created_at = time();
			$notification->updated_at = time();
			$notification->save();

			/*for seller*/
			$notify_to = $Order->seller_uid;

			$notification = new Notification;
			$notification->notify_to = $notify_to;
			$notification->notify_from = $notify_from;
			$notification->notify_by = 'admin';
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

				$this->makeOrderOnHoldToActive($services);

				/* Send Email to Seller */
				//$orderDetailTable = $Order->get_order_detail($Order->order_no);
				//$orderDetail = Order::with('service', 'user', 'seller', 'extra')->where('order_no', $Order->order_no)->get();
				//$orderDetail_data = Order::where('order_no', $Order->order_no)->select('id','uid','service_id','created_at','order_no','txn_id')->first();
				$orderDetail = [];
				$orderDetail['buyer'] = $Order->user->username;
				$orderDetail['created_at'] = $Order->created_at;
				$orderDetail['service'] = $Order->service->title;
				$orderDetail['order_no'] = $Order->order_no;
				$orderDetail['txn_id'] = $Order->txn_id;

				$seller = User::select('id','username','email','Name')->find($Order->seller_uid);
				$buyer = User::select('id','username','email','Name')->find($Order->uid);

				/*Send mail to seller*/
				$data = [
					'receiver_secret' => $seller->secret,
					'email_type' => 1,
                    'subject' => 'Your order has been completed',
                    'template' => 'frontend.emails.v1.complete_order',
                    'email_to' => $seller->email,
                    'username' => $seller->username,
					'name' => $buyer->username,
					'orderNumber' => $Order->order_no,
					'orderDetail' => $orderDetail,
					'order_detail_url' => route('seller_orders_details',[$Order->order_no]),
                ];
                QueueEmails::dispatch($data, new SendEmailInQueue($data));

                /*Send mail to sub users*/
                $userObj = new User;
                $userObj->send_mail_to_subusers('is_order_mail',$seller->id,$data,'username');

                /*Send mail to buyer*/
				$data = [
					'receiver_secret' => $buyer->secret,
					'email_type' => 1,
                    'subject' => 'Your order has been completed',
                    'template' => 'frontend.emails.v1.complete_order',
                    'email_to' => $buyer->email,
                    'username' => $buyer->username,
					'name' => $seller->username,
					'orderNumber' => $Order->order_no,
					'orderDetail' => $orderDetail,
					'order_detail_url' => route('buyer_orders_details',[$Order->order_no]),
                ];
                QueueEmails::dispatch($data, new SendEmailInQueue($data));

			}
			return true;
		}
		return false;
	}

	//Cancel order by admin/cron
	public function cancel_order($Order,$cancelled_by=1){
		//$cancelled_by 1 - admin, 2- auto cancelled by cron
        DB::beginTransaction();
        try {

            $cdate = date('Y-m-d H:i:s');
            $uid = $Order->uid;

			//Update order status
            $Order->cancel_by = $uid;
            $Order->status = 'cancelled';
            $Order->cancel_date = $cdate;
			if($cancelled_by == 1){
				$Order->cancel_note = "Cancelled Order By Admin.";
			}else{
				$Order->cancel_note = "Order Auto-Cancelled.";
			}
            
            $Order->save();

			//Update Review edition count
			if($Order->is_review_edition == 1){
				if($Order->service->review_edition_count > 0){
					$Order->service->review_edition_count = $Order->service->review_edition_count - 1;
					$Order->service->save();
				}
			}

            /*update buyer wallet full amount*/
            $extra = 0;
            if(!empty($Order->extra)){
                foreach($Order->extra as $row){
                    $extra += $row->qty*$row->price;
                }
            }

            $earning = ($Order->price*$Order->qty) + $extra - $Order->reorder_discount_amount - $Order->coupon_discount-$Order->volume_discount-$Order->combo_discount;

            /*For recurring service*/
			$can_refund_amount = true;
            if($Order->is_recurring == 1){
				if($Order->is_course == 0){
					$can_refund_amount = $Order->can_refund_recurring_order();
				}else{
					$can_refund_amount = false;
				}
                
                app('App\Http\Controllers\PaypalPaymentController')->cancelPremiumOrder($Order->subscription->profile_id);

                //Update last child recurrence (cancel order process)
                app('App\Http\Controllers\PaypalPaymentController')->updateLastChildRecurrence($Order);

                $profile_receipt = json_decode($Order->subscription['receipt'],true);
                /*For Second recurring service*/
                if($profile_receipt['NUMCYCLESCOMPLETED'] != "0"){ 
                    /*Servie Price * qty + Extra*/
					$findBuyerTransaction = BuyerTransaction::where('order_id',$Order->id)->select('id','payment_processing_fee')->first();
					if(!is_null($findBuyerTransaction) && $findBuyerTransaction->payment_processing_fee > 0) {
						$earning = $profile_receipt['AMT'] - $findBuyerTransaction->payment_processing_fee;
					} else {
						$earning = $profile_receipt['AMT']; 
					}
                }
            }

			$User  = User::select('id','promotional_fund','earning','username','Name')->find($Order->uid); 

            if ($Order->payment_by == "bluesnap") 
            {
                if(count($Order->upgrade_history) > 0) {
                    // calculate earning for first order payment before upgrade
                    $bluesnap_transfer = ($Order->upgrade_history[0]->previous_amount * $Order->qty) + $extra - $Order->reorder_discount_amount - $Order->coupon_discount-$Order->volume_discount-$Order->combo_discount;
                    // calculate amount which not paid by bluesnap in upgrade order
                    
                    $total_payable = $wallet_amount = $promotional_amount = 0;
                    foreach ($Order->upgrade_history as $key => $value) {
                        if($value->payment_by != 'bluesnap') {
                            $total_payable = $total_payable + $value->payable_amount;
                            if($value->used_promotional_fund > 0) {
                                $promotional_amount = $promotional_amount + $value->used_promotional_fund;
                                $remaining = $value->payable_amount - $value->used_promotional_fund;
                                if($remaining > 0) {
                                    $wallet_amount = $wallet_amount + $remaining;
                                }
                            } else {
                                $wallet_amount = $wallet_amount + $value->payable_amount;
                            }
                        } else {
                            $response = app('App\Http\Controllers\BluesnapPaymentController')->refundTransaction($value->txn_id ,$value->payable_amount);
                            if ($response != "")
                            {
                                /* Buyer Transactions Start */
                                $buyerTransaction = new BuyerTransaction;
                                $buyerTransaction->order_id = $Order->id;
                                $buyerTransaction->buyer_id = $Order->uid;
                                $buyerTransaction->note = 'Cancelled Order By Admin';
                                $buyerTransaction->anount = $value->payable_amount;
                                $buyerTransaction->status = 'pending_payment';
                                $buyerTransaction->credit_to = 'bluesnap';
                                $buyerTransaction->created_at = time();
                                $buyerTransaction->save();
                                /* Buyer Transactions End */
                            }
                            else
                            {
                                /* Buyer Transactions Start */
                                $buyerTransaction = new BuyerTransaction;
                                $buyerTransaction->order_id = $Order->id;
                                $buyerTransaction->buyer_id = $Order->uid;
                                $buyerTransaction->note = 'Cancelled Order By Admin';
                                $buyerTransaction->anount = $value->payable_amount;
                                $buyerTransaction->status = 'cancelled';
                                $buyerTransaction->credit_to = 'bluesnap';
                                $buyerTransaction->created_at = time();
                                $buyerTransaction->save();
                                /* Buyer Transactions End */
                            }
                        }
                    }
                    if($wallet_amount > 0 || $promotional_amount > 0) {
                        app('App\Http\Controllers\PaypalPaymentController')->refundUpgradeOrderAmount($Order,$User,$wallet_amount,$promotional_amount,$total_payable);
                    }
                } else {
                    $bluesnap_transfer = $earning;
                }

                $response = app('App\Http\Controllers\BluesnapPaymentController')->refundTransaction($Order->txn_id ,$bluesnap_transfer);

                if ($response != "") 
                {
                    /* $error = $response->data;*/

                    /* Buyer Transactions Start */
                    $buyerTransaction = new BuyerTransaction;
                    $buyerTransaction->order_id = $Order->id;
                    $buyerTransaction->buyer_id = $Order->uid;
                    $buyerTransaction->note = 'Cancelled Order By Admin';
                    $buyerTransaction->anount = $bluesnap_transfer;
                    $buyerTransaction->status = 'pending_payment';
                    $buyerTransaction->credit_to = 'bluesnap';
                    $buyerTransaction->created_at = time();
                    $buyerTransaction->save();
                    /* Buyer Transactions End */
                }
                else
                {
                    /* Buyer Transactions Start */
                    $buyerTransaction = new BuyerTransaction;
                    $buyerTransaction->order_id = $Order->id;
                    $buyerTransaction->buyer_id = $Order->uid;
                    $buyerTransaction->note = 'Cancelled Order By Admin';
                    $buyerTransaction->anount = $bluesnap_transfer;
                    $buyerTransaction->status = 'cancelled';
                    $buyerTransaction->credit_to = 'bluesnap';
                    $buyerTransaction->created_at = time();
                    $buyerTransaction->save();
                    /* Buyer Transactions End */
                }
            }
            else
            {
                $user_earning = $earning;
                
                if($Order->used_promotional_fund > 0) {
                    $User->promotional_fund = $User->promotional_fund + $Order->used_promotional_fund;
                    $remaining = $earning - $Order->used_promotional_fund;
                    //check for bluesnap amount in upgrade order
                    if(count($Order->upgrade_history) > 0) {
                        $bluesnap_transfer = 0;
                        foreach ($Order->upgrade_history as $key => $value) {
                            if($value->payment_by == 'bluesnap') {
                                if($value->used_promotional_fund > 0) {
                                    $temp = $value->payable_amount - $value->used_promotional_fund;
                                    $bluesnap_transfer = $bluesnap_transfer + $temp;
                                } else {
                                    $bluesnap_transfer = $bluesnap_transfer + $value->payable_amount;
                                }
                                if($value->payable_amount > 0) {
                                    $response = app('App\Http\Controllers\BluesnapPaymentController')->refundTransaction($value->txn_id ,$value->payable_amount);
                                    if ($response != "")
                                    {
                                        /* Buyer Transactions Start */
                                        $buyerTransaction = new BuyerTransaction;
                                        $buyerTransaction->order_id = $Order->id;
                                        $buyerTransaction->buyer_id = $Order->uid;
                                        $buyerTransaction->note = 'Cancelled Order By Admin';
                                        $buyerTransaction->anount = $value->payable_amount;
                                        $buyerTransaction->status = 'pending_payment';
                                        $buyerTransaction->credit_to = 'bluesnap';
                                        $buyerTransaction->created_at = time();
                                        $buyerTransaction->save();
                                        /* Buyer Transactions End */
                                    } else {
                                        /* Buyer Transactions Start */
                                        $buyerTransaction = new BuyerTransaction;
                                        $buyerTransaction->order_id = $Order->id;
                                        $buyerTransaction->buyer_id = $Order->uid;
                                        $buyerTransaction->note = 'Cancelled Order By Admin';
                                        $buyerTransaction->anount = $value->payable_amount;
                                        $buyerTransaction->status = 'cancelled';
                                        $buyerTransaction->credit_to = 'bluesnap';
                                        $buyerTransaction->created_at = time();
                                        $buyerTransaction->save();
                                        /* Buyer Transactions End */
                                    }
                                }
                            }
                        }
                    }
                    if($bluesnap_transfer > 0) {
                        $remaining = $remaining - $bluesnap_transfer;
                        $user_earning = $earning - $bluesnap_transfer;
                    }
                    if($remaining > 0) {
                        $User->earning = $User->earning + $remaining;
                    }
                    /* create promotional transaction history */
                    $promotional_transaction = new UserPromotionalFundTransaction;
                    $promotional_transaction->user_id = $User->id;
                    $promotional_transaction->order_id = $Order->id;
                    $promotional_transaction->amount = $Order->used_promotional_fund;
                    $promotional_transaction->type = 0; //type - service
                    $promotional_transaction->transaction_type = 1; //transaction_type - credit
                    $promotional_transaction->save();
                } else {
                    //check for bluesnap amount in upgrade order
                    if(count($Order->upgrade_history) > 0) {
                        $bluesnap_transfer = 0;
                        foreach ($Order->upgrade_history as $key => $value) {
                            if($value->payment_by == 'bluesnap') {
                                if($value->used_promotional_fund > 0) {
                                    $temp = $value->payable_amount - $value->used_promotional_fund;
                                    $bluesnap_transfer = $bluesnap_transfer + $temp;
                                } else {
                                    $bluesnap_transfer = $bluesnap_transfer + $value->payable_amount;
                                }
                                if($value->payable_amount > 0) {
                                    $response = app('App\Http\Controllers\BluesnapPaymentController')->refundTransaction($value->txn_id ,$value->payable_amount);
                                    if ($response != "")
                                    {
                                        /* Buyer Transactions Start */
                                        $buyerTransaction = new BuyerTransaction;
                                        $buyerTransaction->order_id = $Order->id;
                                        $buyerTransaction->buyer_id = $Order->uid;
                                        $buyerTransaction->note = 'Cancelled Order By Admin';
                                        $buyerTransaction->anount = $value->payable_amount;
                                        $buyerTransaction->status = 'pending_payment';
                                        $buyerTransaction->credit_to = 'bluesnap';
                                        $buyerTransaction->created_at = time();
                                        $buyerTransaction->save();
                                        /* Buyer Transactions End */
                                    } else {
                                        /* Buyer Transactions Start */
                                        $buyerTransaction = new BuyerTransaction;
                                        $buyerTransaction->order_id = $Order->id;
                                        $buyerTransaction->buyer_id = $Order->uid;
                                        $buyerTransaction->note = 'Cancelled Order By Admin';
                                        $buyerTransaction->anount = $value->payable_amount;
                                        $buyerTransaction->status = 'cancelled';
                                        $buyerTransaction->credit_to = 'bluesnap';
                                        $buyerTransaction->created_at = time();
                                        $buyerTransaction->save();
                                        /* Buyer Transactions End */
                                    }
                                }
                            }
                        }
                    }
                    if($bluesnap_transfer > 0) {
                        $user_earning = $earning - $bluesnap_transfer;
                    }
                    $User->earning = $User->earning + $user_earning;
                }

				if($can_refund_amount == true){
					/*Buyer Transactions Start*/
					$buyerTransaction = new BuyerTransaction;
					$buyerTransaction->order_id = $Order->id;
					$buyerTransaction->buyer_id = $Order->uid;
					$buyerTransaction->note = 'Cancelled Order By Admin';
					$buyerTransaction->anount = $user_earning;
					$buyerTransaction->status = 'cancelled';
					$buyerTransaction->credit_to = 'wallet';
					$buyerTransaction->created_at = time();
					$buyerTransaction->save();
					/*Buyer Transactions End*/ 

					$User->save();
				}
                
            }
            
            /*begin : Seller Earnings Start*/

            /*begin : get admin service charge*/
            $serviceCharge = get_service_change($earning,$Order->is_new);
            /*end : get admin service charge*/

            if($Order->is_special_order == 1){
                $serviceCharge = 0;
            }

            $SellerEarning = SellerEarning::where(['status'=>'pending_clearance','order_id'=>$Order->id,'seller_id'=>$Order->seller_uid])->first();
            
            if (empty($SellerEarning)) {
                $SellerEarning = new SellerEarning;
                $SellerEarning->order_id = $Order->id;
                $SellerEarning->seller_id = $Order->seller_uid;
            }
            $SellerEarning->note = 'Cancelled Order';
            
            if($Order->is_affiliate == "1"){
                $affiliate_income = ($earning*15)/100;                    
                $SellerEarning->anount = $earning - $serviceCharge - $affiliate_income;
            }else{
                $SellerEarning->anount = $earning - $serviceCharge;
            }
            
            $SellerEarning->status = 'cancelled';
            $SellerEarning->created_at = time();
            $SellerEarning->save();

            /*end : Seller Earnings End*/

            /*Affiliate Earnings */
            if($Order->is_affiliate == "1"){
                $Affiliate = AffiliateEarning::where(['status'=>'pending_clearance','order_id'=>$Order->id,'seller_id'=>$Order->seller_uid])->first();
                if(!empty($Affiliate)){
                    $Affiliate->status='cancelled';
                    $Affiliate->save();
                }
            }
            /*Affiliate Earnings End*/

            

            $Order->makeOrderOnHoldToActive($Order->service);

            /*Send Notification to buyer Start*/
            $notify_from = $uid;
            if($notify_from == $Order->uid){
                $notify_to = $Order->seller_uid;
            }elseif($notify_from == $Order->seller_uid){
                $notify_to = $Order->uid;
            }

            $notification = new Notification;
            $notification->notify_to = $notify_to;
            $notification->notify_from = $notify_from;
            $notification->notify_by = 'admin';
            $notification->order_id = $Order->id;
            $notification->is_read = 0;
            $notification->type = 'cancel_order';
            $notification->message = 'Order #'.$Order->order_no.' has cancelled';
            $notification->created_at = time();
            $notification->updated_at = time();
            $notification->save();
            /*Send Notification to buyer End*/

            DB::commit();
            return (object)['status'=>true,'message'=>''];
        } catch (\Exception $e) {
            DB::rollback();
            // something went wrong
            return (object)['status'=>false,'message'=>'Something goes wrong.'];
        }
    }

	public function updateHeader(){
		if(Auth::check()){

			$this->uid = get_user_id();
            
			$isAnyActiveService = Service::select('id')->where('uid','=',$this->uid)->count();

			$openOrders = Order::select('id')->where('seller_uid','=',$this->uid)
			->where('status',"=",'active')
			->count();

			$orderDue = Order::select('id','is_recurring','end_date','order_no')
			->where('seller_uid','=',$this->uid)
			->where('status',"=",'active')
			->where('end_date','>',date('Y-m-d H:i:s'))
			->orderBy('end_date','asc')
			->first();

			$order_no = '';
			$dueOn = '';
			$orderDueId='';
			if(!empty($orderDue)){

				if($orderDue->is_recurring == 0){
					$end_date = $orderDue->end_date;
				}else{
					$end_date = $orderDue->subscription->expiry_date;
				}

				$diffDate = new DateTime($end_date);
				$now = new DateTime();
				$interval = date_diff($diffDate, $now);

				if($interval->format('%a') == 0 && $interval->format('%h') == 0){
					$dueOn = $interval->format('%i').' minutes ';
				}elseif($interval->format('%a') == 0 ){
					$dueOn = $interval->format('%h').' hours '.$interval->format('%i').' minutes ';
				}else{
					$dueOn = $interval->format('%a').' days '.$interval->format('%h').' hours '.$interval->format('%i').' minutes ';
				}

				$orderDueId = $orderDue->order_no;
			}

			$unansweredReviews = Order::select('orders.id')
			->where('seller_uid','=',$this->uid)
			->where('orders.status',"=",'completed')
			->where('orders.is_review','=',1)
			->whereNull('orders.completed_reply')
			//->where('services.status',"=",'active')
			//->join('services','services.id','orders.service_id')
			->count();

			if ($unansweredReviews == 1) {
				$oneOrder = Order::select('order_no')->where('seller_uid','=',$this->uid)
				->where('orders.status',"=",'completed')
				->where('orders.is_review','=',1)
				->whereNull('orders.completed_reply')
				//->where('services.status',"=",'active')
				//->join('services','services.id','orders.service_id')
				->first();
				$order_no = $oneOrder->order_no;
			}
			session(['isAnyActiveService'=>$isAnyActiveService,
				'open_orders' => $openOrders,
				'orderDue'=>$dueOn,
				'orderDueId'=>$orderDueId,
				'unansweredReviews' => $unansweredReviews,
				'order_no'=> $order_no
			]);
		}
	}

	public function order_revisions() {
		return $this->hasMany('App\OrderRevisions', 'order_id', 'id');
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
	
	public function last_order_revision() {
        return $this->hasOne('App\OrderRevisions', 'order_id', 'id')->orderBy('id','desc');
	}
	
	public function reviewFeedbackCount(){
		$this->uid = get_user_id();
		$helpfulCount = ReviewFeedback::select('id')->where(['service_id' => $this->service_id,'order_id' => $this->id,'user_id' => $this->uid,'type'=>'helpful'])->count();
		return $helpfulCount;
	}

	public function reviewFeedbackReportCount(){
		$this->uid = get_user_id();
		$helpfulCount = ReviewFeedback::select('id')->where(['service_id' => $this->service_id,'order_id' => $this->id,'user_id' => $this->uid,'type'=>'report_abuse'])->count();
		return $helpfulCount;
	
	}

	public function reportFeedbackLast(){
		$this->uid = get_user_id();
		$lasTReview = null;
		$lasTReview = ReviewFeedback::select('type')->where(['service_id' => $this->service_id,'order_id' => $this->id,'user_id' => $this->uid])->orderBy('id','desc')->first();
		return $lasTReview;
	}
    
    public function makeOrderOnHoldToActive($service,$limit=1){
        if(!empty($service)){
        	if($service->is_recurring == 0){
        		/*Get first created order which are on hold*/

        		$orders = $this->where('status','on_hold')
        		->where('service_id',$service->id)
        		->where('is_pause',0)
        		->orderBy('created_at','ASC')
        		->limit($limit)
        		->get();

        		if(count($orders) > 0){

        			foreach ($orders as $order) {

	        			/*Make order active and update end date*/
	        			$total_days = $order->delivery_days;
						foreach ($order->extra as $extra) {
							$total_days += $extra->delivery_days;
						}
	    				$end_date = date('Y-m-d H:i:s', strtotime("+" . $total_days . " days"));
	    				$order->status = 'active';
	    				$order->start_date = date('Y-m-d H:i:s');
	    				$order->end_date = $end_date;
	    				$order->save();
	    				/*Make order active and update end date*/

	        			/* Send Notification to seller */
			    		$notify_from = $order->uid;
			    		$notify_to = $order->seller_uid;

			    		$notification = new Notification;
			    		$notification->notify_to = $notify_to;
			    		$notification->notify_from = $notify_from;
			    		$notification->notify_by = 'buyer';
			    		$notification->order_id = $order->id;
			    		$notification->is_read = 0;
			    		$notification->type = 'new_order';
			    		$notification->message = 'Your Order is Active Now!! #' . $order->order_no;
			    		$notification->created_at = time();
			    		$notification->updated_at = time();
			    		$notification->save();

			    		/* Send mail to seller*/
			    		$seller = User::where('id', '=', $order->seller_uid)->first();
			    		$buyer = User::where('id', '=', $order->uid)->first();

			    		/*send mail to inform subscription renewed*/
		                $data = [
							'receiver_secret' => $seller->secret,
							'email_type' => 1,
		                    'subject' => 'Your Order is Active Now!',
		                    'template' => 'frontend.emails.v1.order_placed',
		                    'email_to' => $seller->email,
		                    'username' => $seller->username,
			    			'orderNumber' => $order->order_no,
			    			'delivery_days' => $order->delivery_days,
			    			'price' => $order->price,
			    			'start_date' => $order->start_date,
			    			'end_date' => $order->end_date,
			    			'txn_id' => $order->txn_id,
			    			'buyer' => $buyer->username,
			    			'seller_email' => $seller->email,
			    			'total_amount' => $order->order_total_amount,
		                ];
		                QueueEmails::dispatch($data, new SendEmailInQueue($data));

		                /*Send mail to sub users*/
						$userObj = new User;
						$userObj->send_mail_to_subusers('is_order_mail',$seller->id,$data,'username');

			    		/*Send Notification to Buyer */
			    		$notification = new Notification;
			    		$notification->notify_to = $notify_from;
			    		$notification->notify_from = $notify_to;
			    		$notification->notify_by = 'seller';
			    		$notification->order_id = $order->id;
			    		$notification->is_read = 0;
			    		$notification->type = 'new_order';
			    		$notification->message = 'Your Order is Active Now!! #' . $order->order_no;
			    		$notification->created_at = time();
			    		$notification->updated_at = time();
			    		$notification->save();

			    		/* Send mail to seller*/
			    		$data = [
							'receiver_secret' => $buyer->secret,
							'email_type' => 1,
		                    'subject' => 'Your Order is Active Now!',
		                    'template' => 'frontend.emails.v1.buyer_order_start',
		                    'email_to' => $buyer->email,
		                    'username' => $buyer->username,
			    			'orderNumber' => $order->order_no,
			    			'delivery_days' => $order->delivery_days,
			    			'price' => $order->price,
			    			'start_date' => $order->start_date,
			    			'end_date' => $order->end_date,
			    			'txn_id' => $order->txn_id,
			    			'seller' => $seller->username,
			    			'seller_email' => $seller->email,
			    			'total_amount' => $order->order_total_amount,
		                ];
		                QueueEmails::dispatch($data, new SendEmailInQueue($data));
		    		}
        		}
        	}
        }
    }

    public function getOrderStatusAttribute()
	{	
		$cdate = date('Y-m-d H:i:s');
		if($this->status == 'active'){
			if($this->delivered_date == null && $this->end_date < $cdate){
				$status = 'Late';
			}else{
				$status = 'In Progress';
			}
		}elseif ($this->status == 'cancelled'){
			$status = ucFirst($this->status);
		}elseif ($this->status == 'in_revision'){
			$status = 'In Revision';
		}elseif ($this->status == 'on_hold') {
			$status = 'On Hold';
		}else{
			$status = ucFirst($this->status);
		}
		
	    return $status;
	}
	
	public function scopeUpgradeorderstatus($query)
    {
		return $query->where('is_job',0)
					 ->where('is_custom_order',0)
					 ->where('is_recurring',0)
					 ->whereIn('status',['new','active'])
					 ->whereHas('service', function ($query1) {
						$query1->where('status', 'active')->where('is_delete', 0)->where('is_approved', 1)->where('three_plan',1)->select('id');
					 });
    }

	public function upgrade_history() {
		return $this->hasMany('App\OrderUpgradeHistory', 'order_id', 'id');
	}

	public function check_is_new_recurring_order($created_at){
		if(strtotime($created_at) >= strtotime(env('AFFELIATION_START_FROM_NEW_RECURRING_ORDER'))){
			return true;
		}
		return false;
	}

	public function taglist() {
		return $this->hasMany('App\BuyerOrderTagDetails', 'order_id', 'id');
	}

	public function update_cc_earning_on_cancel_order(){
		if(isset($this->used_cc_deposit) && $this->used_cc_deposit > 0 && $this->status == 'cancelled'){
			$buyer = User::select('id','cc_earning')->find($this->uid);
			
			//get total cc amount before order create
			$total_credit_amt_by_cc = BuyerTransaction::where('status','add_money_to_wallet')
			->where('buyer_id',$this->uid)
			->where('creditcard_amount','>',0)
			->where('cc_refund_status',0)
			->sum('creditcard_amount');

			if($total_credit_amt_by_cc > 0){
				//check current cc earning and total credited amount
				if($buyer->cc_earning < $total_credit_amt_by_cc){
					$cc_earning = $buyer->cc_earning + $this->used_cc_deposit;
					if($cc_earning > $total_credit_amt_by_cc){
						$cc_earning =  $total_credit_amt_by_cc;
					}
					$buyer->cc_earning = $cc_earning;
					$buyer->save();
				}
			}
		}
	}

	public function get_invoice_url($order,$is_buyer = 0){

		if($order){
			$order_total = $order->order_total_amount;
			$payment_status = $order->payment_status;

			if ( $payment_status == 'Completed' ){
				$payment_status = 'Paid';
			}
			if($is_buyer == 1){
				$invoice = array(
					'order_date'     => date('F j Y',strtotime($order->start_date)),
					'order_id'       => $order->order_no,
					'status'         => $payment_status . ' via ' . $order->payment_by,
					'buyer'          => ucwords($order->user->Name),
					'seller'         => ucwords($order->seller->Name),
					'order'          => $order->service->title,
					'description'    => $order->service->title,
					'amount'         => ''.$order_total,
					'seller_id'      => $order->seller->id,
					'buyer_id'       => $order->user->id,
					'id'			 => $order->id,
					'processing_fee' => $order->get_processing_fee(),
					'parent_id'      => $order->parent_id,
					'created_at'     => $order->created_at,
					'is_course'      => $order->is_course
				);
			}else{
				$invoice = array(
					'order_date'     => date('F j Y',strtotime($order->start_date)),
					'order_id'       => $order->order_no,
					'status'         => $payment_status . ' via ' . $order->payment_by,
					'buyer'          => ucwords($order->user->Name),
					'seller'         => ucwords($order->seller->Name),
					'order'          => $order->service->title,
					'description'    => $order->service->title,
					'amount'         => ''.$order_total,
					'seller_id'      => $order->seller->id,
					'buyer_id'       => $order->user->id,
					'is_course'      => $order->is_course
				);
			}

			return \Crypt::encrypt($invoice);
		}
		return NULL;
	}
	
	//Get Review edition order which have completed and pedning for review
	public function get_pending_review_edition_order(){
		$this->uid = User::get_parent_id();
		return $this->select('id','order_no')
                    ->where(['status'=>'completed','uid'=>$this->uid,'is_review_edition'=>1])
                    ->whereRaw('(completed_note is null AND seller_rating = 0)')
                    ->first();
	}
	
	public function can_refund_recurring_order(){
		$can_refund_amount = false;
		$orderSubscription = OrderSubscription::where('order_id',$this->id)
			->where('is_cancel',0)
			->where('is_payment_received',1)
			->first();
		if(!empty($orderSubscription)){
			if(!is_null($orderSubscription->last_seller_payment_receive_date) && $orderSubscription->last_seller_payment_receive_date != ""){
				// check can send payment to seller
				if(strtotime($orderSubscription->last_seller_payment_receive_date) < strtotime($orderSubscription->last_buyer_payment_date)){
					$can_refund_amount = true;
				}
			}else{
				$can_refund_amount = true;
			}
		}
		return $can_refund_amount;
	}

	public function get_processing_fee(){
		return BuyerTransaction::select('payment_processing_fee')->where('order_id',$this->id)->sum('payment_processing_fee');
	}

	public function generate_orderno() {
		$order = Order::select('id')->orderBy('id', 'desc')->limit(1)->first();
		if (count($order) > 0) {
			$orderId = $order->id + rand(1,500);
		} else {
			$orderId = 1;
		}
		$order_no = "LE" . time() . $orderId;

		//Check order No exists ?
		$checkOrderExists = Order::select('id')->where('order_no',$order_no)->count();
		if($checkOrderExists > 0){
			return $this->generate_orderno();
		}
		return $order_no;
	}
	
	public static function storeSellerEarningPaymentDate($order){
		$sellerEarning = SellerEarning::where(['order_id' => $order->id, 'status'=>"pending_clearance", 'seller_id' => $order->seller_uid])->whereNull('payment_date')
		->orderBy('id','desc')->first();

		if(!empty($sellerEarning)){
			if($order->is_recurring == 1){
				if($order->is_course == 1){
					$payment_date = Carbon::createFromFormat('Y-m-d', $order->subscription->last_buyer_payment_date)->addDays(1)->setTimezone('America/Los_Angeles')->format('Y-m-d');
				}else{
					$payment_date = Carbon::createFromFormat('Y-m-d', $order->subscription->last_buyer_payment_date)->addDays(1)->setTimezone('America/Los_Angeles')->format('Y-m-d');
				}
				$sellerEarning->payment_date = $payment_date;
				$sellerEarning->save();

				//Update affiliate payment date
				if ($order->is_affiliate == "1") {
					$affiliate = AffiliateEarning::select('id','payment_date')->where(['status' => 'pending_clearance','order_id' => $order->id, 'seller_id' => $order->seller_uid])->first();
					if (!empty($affiliate)) {
						$affiliate->payment_date = $payment_date;
						$affiliate->save();
					}
				}

			}elseif($order->status == "completed"){
				if($order->is_course == 1){
					$payment_date = date('Y-m-d',strtotime($order->completed_date. " +30 days"));
				}else{
					$payment_date = date('Y-m-d',strtotime($order->completed_date. " +5 days"));
				}
				$sellerEarning->payment_date = $payment_date;
				$sellerEarning->save();

				//Update affiliate payment date
				if ($order->is_affiliate == "1") {
					$affiliate = AffiliateEarning::select('id','payment_date')->where(['status' => 'pending_clearance','order_id' => $order->id, 'seller_id' => $order->seller_uid])->first();
					if (!empty($affiliate)) {
						$affiliate->payment_date = $payment_date;
						$affiliate->save();
					}
				}
			}
		}
	}

	/* Refund order payment */
	public static function refundOrderPayment($order,$is_dispute=0){
		$earning = $order->order_total_amount;
		$wallet_amount = $promotional_fund = 0;
		
		/* Store Buyer Transaction History */
		$storeBuyerTransaction = function($order,$amount,$response,$txn_id="",$note="Cancelled Order",$credit_to="bluesnap") {
			/* Buyer Transactions Start */
			$buyerTransaction = new BuyerTransaction;
			$buyerTransaction->order_id = $order->id;
			$buyerTransaction->buyer_id = $order->uid;
			$buyerTransaction->note = $note;
			$buyerTransaction->anount = $amount;
			$buyerTransaction->transaction_id = $txn_id;
			if ($response != ""){
				$buyerTransaction->creditcard_amount = $amount;
				$buyerTransaction->status = 'pending_payment';
			}else{
				$buyerTransaction->status = 'cancelled';
			}
			$buyerTransaction->credit_to = $credit_to;
			$buyerTransaction->created_at = time();
			$buyerTransaction->save();
			/* Buyer Transactions End */
		};

		/* upgrade order function */
		$refundUpgradeOrder = function($order,$storeBuyerTransaction) {
			$total_payable = $promotional_amount = $total_wallet_amount = 0;
			if(count($order->upgrade_history) > 0) {
				foreach ($order->upgrade_history as $key => $value) {
					if($value->payment_by != 'bluesnap') {
						if($value->used_promotional_fund > 0) {
							$promotional_amount = $promotional_amount + $value->used_promotional_fund;
							$remaining = $value->payable_amount - $value->used_promotional_fund;
							if($remaining > 0) {
								$total_wallet_amount = $total_wallet_amount + $remaining;
							}
						} else {
							$total_wallet_amount = $total_wallet_amount + $value->payable_amount;
						}
					} else {
						$total_payable = $total_payable + $value->payable_amount;
						if($value->payable_amount > 0) {
							$bluesnapControllerObj = new BluesnapPaymentController();
							$response = $bluesnapControllerObj->refundTransaction($value->txn_id ,$value->payable_amount);
							/* Buyer Transactions Start */
							$storeBuyerTransaction($order,$value->payable_amount,$response,$value->txn_id);
							/* Buyer Transactions End */
						}
					}
				}
			}
			$data['total_payable'] = $total_payable;
			$data['promotional_amount'] = $promotional_amount;
			$data['total_wallet_amount'] = $total_wallet_amount;
			$data['total_upgraded_amount'] = $total_payable + $promotional_amount + $total_wallet_amount;
			return $data;
			/* Buyer Transactions End */
		};

		/*For recurring service*/
		$can_refund_amount = true;
		if($order->is_recurring == 1){
			$can_refund_amount = $order->can_refund_recurring_order();
			$paypalControllerObj = new PaypalPaymentController();
			$paypalControllerObj->cancelPremiumOrder($order->subscription->profile_id);
			//Update last child recurrence (cancel order process)
			$paypalControllerObj->updateLastChildRecurrence($order);
			$profile_receipt = json_decode($order->subscription['receipt'],true);
			/*For Second recurring service*/
			if($profile_receipt['NUMCYCLESCOMPLETED'] != "0"){ 
				/*Servie Price * qty + Extra*/
				$findBuyerTransaction = BuyerTransaction::where('order_id',$order->id)->select('id','payment_processing_fee')->first();
				if(!is_null($findBuyerTransaction) && $findBuyerTransaction->payment_processing_fee > 0) {
					$earning = $profile_receipt['AMT'] - $findBuyerTransaction->payment_processing_fee; 
				} else {
					$earning = $profile_receipt['AMT']; 
				}
			}
		}

		if($can_refund_amount == true){
			/* BEGIN - Refund extra order payment */
			$extra_used_wallet = $extra_used_bluesnap = $total_extra_payment = 0;
			if($order->is_course == 0){
				$extra_transactions = BuyerTransaction::where('buyer_id',$order->uid)->where('order_id',$order->id)->where('status','deposit_extra')->get();
				if(count($extra_transactions) > 0){
					foreach ($extra_transactions as $value) {
						/* total extra amount */
						$total_extra_payment += $value->anount;
						/* Check Credit Cart Payment */
						if($value->creditcard_amount > 0){
							$extra_used_bluesnap += $value->creditcard_amount;
							/* Refund credit card payment*/
							$bluesnapControllerObj = new BluesnapPaymentController();
							$response = $bluesnapControllerObj->refundTransaction($value->transaction_id ,$value->creditcard_amount);
							/* Buyer Transactions Start */
							$storeBuyerTransaction($order,$value->creditcard_amount,$response,$value->transaction_id);
							/* Buyer Transactions End */
						}else{
							/* Check promotional amount */
							if($value->promotional_amount > 0){
								/* Transfer to promotional amount */
								$promotional_fund += $value->promotional_amount;  
								$extra_used_wallet += $value->anount - $value->promotional_amount;
							}else{
								/* Transfer to wallet */
								$extra_used_wallet += $value->anount;
							}
						}
					}
				}
			}
			/* END - Refund extra order payment */ 
		
			$user = User::find($order->uid);
			$user_earning = $earning - $extra_used_bluesnap;
			/* Order payment by bluesnap */ 
			if ($order->payment_by == "bluesnap"){
				$refund_response = $refundUpgradeOrder($order,$storeBuyerTransaction);
				$bluesnap_transfer = $user_earning; /* Remove extras amount from total order */
				/* BEGIN - Extras purchased amount */
				$wallet_amount += $extra_used_wallet;
				/* END - Extras purchased amount */
				if($refund_response['total_upgraded_amount'] > 0){
					$wallet_amount += $refund_response['total_wallet_amount'];
					$promotional_fund += $refund_response['promotional_amount'];
					$bluesnap_transfer = $user_earning - $refund_response['total_upgraded_amount'];
				}
				$bluesnap_transfer = $bluesnap_transfer - $total_extra_payment;
				/* Refund order amount */
				$bluesnapControllerObj = new BluesnapPaymentController();
				$response = $bluesnapControllerObj->refundTransaction($order->txn_id ,$bluesnap_transfer);
				/* Buyer Transactions Start */
				$storeBuyerTransaction($order,$bluesnap_transfer,$response);
				/* Buyer Transactions End */
			}else{
				$refund_response = $refundUpgradeOrder($order,$storeBuyerTransaction);
				$promotional_fund =  $promotional_fund + $order->used_promotional_fund;
				$wallet_amount = $user_earning;
				if($refund_response['total_payable'] > 0){
					$wallet_amount = $wallet_amount - $refund_response['total_payable'];
				}
				$wallet_amount = $wallet_amount - $promotional_fund;
				//update cc earning wallet if order have used cc earnings
				$order->update_cc_earning_on_cancel_order();
			}

			$user->promotional_fund = $user->promotional_fund + $promotional_fund;
			$user->earning = $user->earning + $wallet_amount;
			$user->save();

			if($promotional_fund > 0){
				/* create promotional transaction history */
				$promotional_transaction = new UserPromotionalFundTransaction;
				$promotional_transaction->user_id = $user->id;
				$promotional_transaction->order_id = $order->id;
				$promotional_transaction->amount = $promotional_fund;
				$promotional_transaction->type = 0; //type - service
				$promotional_transaction->transaction_type = 1; //transaction_type - credit
				$promotional_transaction->save();	
				$user->save();
			}
		}
		if($wallet_amount > 0 || $promotional_fund > 0){
			/* BEGIN - Buyer Transactions */
			$refund_amount = $wallet_amount + $promotional_fund;
			$storeBuyerTransaction($order,$refund_amount,"","","Cancelled Order","wallet");
			/* END - Buyer Transactions Start */
		}
		return true;
	}
	
	/* Send wallet transaction email to admin */ 
	public static function sendWalletTransactionEmail($data){
		$data['subject'] = "demo - Wallet Sales";
		$data['template'] = 'frontend.emails.v1.wallet_transaction_email';
		$data['email_to'] = env('SUPPORT_EMAIL');
		$data['transaction_date'] = date('m/d/Y H:i:s');
		QueueEmails::dispatch($data, new SendEmailInQueue($data));
	}
	
	public static function service_can_share($Order)
	{
		$can_share = false;
		if($Order->is_course == 0)
		{
			if(isset($Order->service) && $Order->service->status == 'active' && $Order->service->is_delete == 0 && $Order->service->is_approved == 1 && $Order->service->is_private == 0 &&
			$Order->status == 'completed' && $Order->seller_rating > 3 && $Order->is_custom_order == 0 && $Order->is_job == 0 &&
			Auth::user()->parent_id == 0 && isset($Order->seller) && $Order->seller->is_delete == 0 && $Order->seller->vacation_mode == 0 && 
			$Order->seller->soft_ban == 0 && $Order->seller->status == 1)
			{
				$can_share = true;
			}
		}
		else
		{
			if(isset($Order->service) && $Order->service->status == 'active' && $Order->service->is_delete == 0 && $Order->service->is_approved == 1 && $Order->service->is_private == 0 &&
			$Order->seller_rating > 3 && Auth::user()->parent_id == 0 && isset($Order->seller) && 
			$Order->seller->is_delete == 0 && $Order->seller->vacation_mode == 0 && $Order->seller->soft_ban == 0 && $Order->seller->status == 1)
			{
				$can_share = true;
			}
		}
		
		return $can_share;
	}
}
