<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Service;
use App\ServicePlan;
use App\Cart;
use App\CartExtra;
use App\Order;
use App\OrderExtra;
use App\ServiceExtra;
use App\PaymentLog;
use App\BuyerTransaction;
use App\SellerEarning;
use App\Notification;
use App\User;
use App\AffiliateEarning;
use App\BuyerReorderPromo;
use App\Specialaffiliatedusers;
use App\CoupanApplied;
use App\BoostedServicesOrder;
use App\BoostingPlan;
use App\Subscription;
use App\SubscribeUser;
use App\DiscountPriority;
use App\BundleDiscount;
use Cookie;
use Auth;
use Session;
use Carbon\Carbon;
use Srmklive\PayPal\Services\ExpressCheckout;
use App\SellerAnalytic;
use App\OrderSubscription;
use App\JobOffer;
use Illuminate\Support\Facades\Log;
use App\SponsorCoupon;
use Illuminate\Support\Arr;
use App\BoostedServicesOrdersDate;
use App\SubscribeTransactionHistory;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use App\UserPromotionalFundTransaction;
use App\InfluencerService;
use App\Influencer;
use App\OrderUpgradeHistory;
use App\TempOrder;
use App\OrderSubscriptionHistory;
use App\BluesnapTempTransaction;
use App\TempOrderExtra;
use App\TempCoupanApplied;
use App\TempAffiliateEarning;
use DB;

use App\TrackOrderChange;
use App\SubUserTransaction;
use App\SubUserChangesHistory;
use App\Http\Controllers\SkrillPaymentController;
use App\Http\Controllers\BluesnapPaymentController;

class PaypalPaymentController extends Controller
{
	protected $provider;
	private $uid;
	private $auth_user;

	public function __construct() {
		$this->provider = new ExpressCheckout();
		$this->middleware(function ($request, $next) {
			if(Auth::check()) {	
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

	public function createOrderTesting(){
		$user = Auth::user();
		$cartObj = new Cart;

		//dd($user->toArray());

		$Cart = Cart::where('uid',$user->id)
		->whereHas('service', function($query) {
			$query->where('is_recurring', 1)->select('id');
		})
		->OrderBy('id', 'desc')->get();

		if(count($Cart) == 0){
			exit();
		}

		$fromWalletAmount = $is_custom_order = $fromPromotionalAmount = 0;
		$txn_id = $profile_id = 'I-BWCS201XY56S';

		$response_profile_detail = $this->provider->getRecurringPaymentsProfileDetails($profile_id);
		//dd($response_profile_detail);

		$this->new_createNewOrder($Cart,$fromWalletAmount,$is_custom_order,$txn_id,$payBy='paypal',$profile_id,$response_profile_detail,null,false, $fromPromotionalAmount);
		exit('testing done');
	}

	public function getPaypalRequestData($Cart,$fromWalletAmount=0,$invoice_id,$fromPromotionalAmount=0){
		$user = Auth::user();
		if(Auth::user()->parent_id != 0){
			$user = User::find(Auth::user()->parent_id);
		}

		$userObj = new User;
		$cartObj = new Cart;
		$discountPriority = DiscountPriority::OrderBy('priority','desc')->get();

		$cartSubtotal = $selectedExtra = $totalPromoDiscount = $totalCoupenDiscount = $totalVolumeDiscount = $totalComboDiscount = 0; 
		$key = 0;
		$items_arr = [];
		$amount_details = [];

		foreach($Cart as $row){
			
			//Update price for review edition
			if($row->is_review_edition == 1){
				$row->plan->price = $row->plan->review_edition_price;
			}

			$cartSubtotal += ($row->plan->price * $row->quantity);
			$afterDiscountPrice = $row->plan->price * $row->quantity;

			$buyerPromo = null;
			//For review edition service do not apply re-order discount
			if($row->remove_reorder_promo == 0  && $row->is_review_edition == 0){
				$buyerPromo = BuyerReorderPromo::where('seller_id',$row->service->uid)
				->where('buyer_id',$user->id)
				->where('service_id',$row->service->id)
				->where('is_used',0)
				->first();
			}

			$cart_extra_price_total = 0;
			foreach($row->extra as $extra) {
				$cart_extra_price_total += $extra->service_extra->price*$extra->qty;
			}

			/*Check any one discount is single*/
			$is_single = $cartObj->check_is_single_discount($discountPriority,$row,$buyerPromo,$Cart);
			foreach ($discountPriority as $priority) {

				//For review edition no any discount
				if($row->is_review_edition == 1){
					continue;
				}

				if($afterDiscountPrice <= env('MINIMUM_SERVICE_PRICE')){
					continue;
				}

				if($is_single != ''){
					if($is_single != $priority->discount_type){
						continue;
					} 
				}

				if($priority->discount_type == 'reorder_promo'){
					if(count($buyerPromo) > 0){
						//$amount_to_used = $afterDiscountPrice + $cart_extra_price_total;
						$discountAmount = ($afterDiscountPrice * $buyerPromo->amount ) / 100;
						$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
						if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
							$totalPromoDiscount += $discountAmount;
							$afterDiscountPrice -= $discountAmount;
						}
					}
				}elseif($priority->discount_type == 'coupan'){
					if($row->coupon){
						if($row->coupon->discount_type=="amount"){
							$discountAmount = $row->coupon->discount;
							$checkDiscountedPrice = ($afterDiscountPrice + $cart_extra_price_total) - $discountAmount;
							if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
								$totalCoupenDiscount += $discountAmount;
								$afterDiscountPrice -= $discountAmount;
							}
						} else {
							$discountAmount = 1 * (($row->coupon->discount/100) * ($afterDiscountPrice + $cart_extra_price_total));
							$checkDiscountedPrice = ($afterDiscountPrice + $cart_extra_price_total) - $discountAmount;
							if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
								$totalCoupenDiscount += $discountAmount;
								$afterDiscountPrice -= $discountAmount;
							}
						}
					}
				}elseif($priority->discount_type == 'volume_discount'){
					if($userObj->is_premium_seller($row->service->uid)){
						$v_discount_per = 0;
						foreach($row->service->volume_discount as $value1){
							if($row->quantity >= $value1->volume){
								$v_discount_per = $value1->discount;
							}
						}
						if($v_discount_per > 0){
							$discountAmount = ($afterDiscountPrice * $v_discount_per ) / 100;
							$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
							if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
								$totalVolumeDiscount += $discountAmount;
								$afterDiscountPrice -= $discountAmount;
							}
						}
					}
				}elseif($priority->discount_type == 'combo_discount'){
					if($userObj->is_premium_seller($row->service->uid)){
						$combo_detail = $cartObj->check_is_combo($row->service_id,$Cart);
						if($combo_detail->combo_discount_per > 0){
							$discountAmount = ($afterDiscountPrice * $combo_detail->combo_discount_per ) / 100;
							$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
							if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
								$totalComboDiscount += $discountAmount;
								$afterDiscountPrice -= $discountAmount;
							}
						}
					}
				}
			}

			$items_arr[$key]['name'] = ucwords($row->service->title);
			$items_arr[$key]['price'] = $row->plan->price;
			$items_arr[$key]['qty'] = $row->quantity;
			$key++;

			foreach($row->extra as $extra){
				$selectedExtra += $extra->service_extra->price*$extra->qty;

				$items_arr[$key]['name'] = $extra->service_extra->title;
				$items_arr[$key]['price'] = $extra->service_extra->price;
				$items_arr[$key]['qty'] = $extra->qty;
				$key++;
			}
		}

		if ($fromPromotionalAmount > 0) {
			$total_cart_bill_dis =  round_price($totalPromoDiscount)+round_price($totalCoupenDiscount)+round_price($totalVolumeDiscount)+round_price($totalComboDiscount);
			$total_cart_bill = $cartSubtotal+$selectedExtra-$total_cart_bill_dis;

			$fromPromotionalAmount = $user->promotional_fund;
		}

		if ($fromWalletAmount > 0) {
			$total_cart_bill_dis =  round_price($totalPromoDiscount)+round_price($totalCoupenDiscount)+round_price($totalVolumeDiscount)+round_price($totalComboDiscount);
			$total_cart_bill = $cartSubtotal+$selectedExtra-$total_cart_bill_dis;
			if($user->earning >= $total_cart_bill){
				$fromWalletAmount = $total_cart_bill;
			}else{
				$fromWalletAmount = $user->earning;
			}
		}
		$totalDiscount = round_price($totalPromoDiscount)+round_price($totalCoupenDiscount)+round_price($totalVolumeDiscount)+round_price($totalComboDiscount)+round_price($fromWalletAmount)+round_price($fromPromotionalAmount);
		
		$totalAmount = $cartSubtotal+$selectedExtra-$totalDiscount;
		
		if ($totalDiscount > 0) {

			foreach ($discountPriority as $priority){
				if($priority->discount_type == 'reorder_promo'){
					if ($totalPromoDiscount > 0) {
						$items_arr[$key]['name'] = $priority->title;
						$items_arr[$key]['price'] = "-" . round_price($totalPromoDiscount);
						$items_arr[$key]['qty'] = 1;
						$key++;
					}
				}elseif($priority->discount_type == 'coupan'){
					if ($totalCoupenDiscount > 0) {
						$items_arr[$key]['name'] = $priority->title;
						$items_arr[$key]['price'] = "-" . round_price($totalCoupenDiscount);
						$items_arr[$key]['qty'] = 1;
						$key++;
					}
				}elseif($priority->discount_type == 'volume_discount'){
					if ($totalVolumeDiscount > 0) {
						$items_arr[$key]['name'] = $priority->title;
						$items_arr[$key]['price'] = "-" . round_price($totalVolumeDiscount);
						$items_arr[$key]['qty'] = 1;
						$key++;
					}
				}elseif($priority->discount_type == 'combo_discount'){
					if ($totalComboDiscount > 0) {
						$items_arr[$key]['name'] = $priority->title;
						$items_arr[$key]['price'] = "-" . round_price($totalComboDiscount);
						$items_arr[$key]['qty'] = 1;
						$key++;
					}
				}
			}
			
			$amount_details['fromWalletAmount'] = 0;
			$amount_details['fromPromotionalAmount'] = 0;
			if ($fromWalletAmount > 0) {
				$items_arr[$key]['name'] = "From Wallet";
				$items_arr[$key]['price'] = "-" . round_price($fromWalletAmount);
				$items_arr[$key]['qty'] = 1;
				$key++;
				$amount_details['fromWalletAmount'] = $fromWalletAmount;
			}

			if ($fromPromotionalAmount > 0) {
				$items_arr[$key]['name'] = "From demo Bucks";
				$items_arr[$key]['price'] = "-" . round_price($fromPromotionalAmount);
				$items_arr[$key]['qty'] = 1;
				$key++;
				$amount_details['fromPromotionalAmount'] = $fromPromotionalAmount;
			}
		}

		/* apply processing fee - start */
		$processing_fee = calculate_payment_processing_fee($totalAmount);
		$items_arr[$key]['name'] = "Payment Processing Fee";
		$items_arr[$key]['price'] = $processing_fee;
		$items_arr[$key]['qty'] = 1;
		$key++;

		$totalAmount += $processing_fee;
		/* apply processing fee - end */

		$request_data = [
			'items' => $items_arr,
			'return_url' => route('paypal_express_checkout_success'),
			'invoice_id' => $invoice_id,
			'invoice_description' => "Invoice #" . $invoice_id,
			'cancel_url' => route('view_cart'),
			'service_total' => round_price($cartSubtotal),
			'extra_total' => round_price($selectedExtra),
			'discount_total' => round_price($totalDiscount)-round_price($fromWalletAmount)-round_price($fromPromotionalAmount),
			'from_wallet' => round_price($fromWalletAmount),
			'from_demo_bucks' => round_price($fromPromotionalAmount),
			'processing_fee' => round_price($processing_fee),
			'total' => round_price($totalAmount),
			'currency' => 'USD'
		];

		return ['request_data' => $request_data, 'amount_details' => $amount_details];
	}

	/* Service Order Payment Not Used*/
	public function expressCheckout(Request $request) {

		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('view_cart')->with('errorFails', get_user_softban_message());
		}

		//Check first review edition review is pending, Than user can't place any order
		$orderObj = new Order;
		$pendingReviews = $orderObj->get_pending_review_edition_order();
		if(!empty($pendingReviews)){
			return redirect()->back()->with(['errorFails'=>'you don\'t have sufficient permissions.']);
		}
		
		$cartObj = new Cart;
		
		$is_from_wallet = $request->is_from_wallet;
		$is_from_promotional = $request->is_from_promotional;
		$user =$this->auth_user;
		$invoice_id = $orderObj->generate_orderno();

		if($is_from_wallet == 1){
			$fromWalletAmount = $user->earning;
		}else{
			$fromWalletAmount = 0;
		}

		if($is_from_promotional == 1){
			$fromPromotionalAmount = $user->promotional_fund;
		}else{
			$fromPromotionalAmount = 0;
		}

		/*Begin : Remove unwanted services*/
        $cartObj->remove_unwanted_services();
		/*End : Remove unwanted services*/
		
		$direct_checkout = 0;
		if($request->has('is_quick_checkout') && $request->is_quick_checkout == 1){
			$direct_checkout = 1;
		}

		$Cart = Cart::where('uid',$user->id)->where('direct_checkout', $direct_checkout)->OrderBy('id', 'desc')->get();

	    $requestCartIds = [];

		$is_recurring_service = $this->check_is_recurring($Cart);
		if($is_recurring_service == 1) {
			$Cart = Cart::get_recurring_from_cart($direct_checkout);
		}
		$get_paypal_request_data = $this->getPaypalRequestData($Cart,$fromWalletAmount,$invoice_id,$fromPromotionalAmount);
		$request_data = $get_paypal_request_data['request_data'];

		/* check for sub user allow to purchase or not */
		if(Auth::user()->parent_id != 0) {
			$next_cart_bill = $request_data['total'];
			if(count($get_paypal_request_data['amount_details']) > 0) {
				$next_cart_bill += $get_paypal_request_data['amount_details']['fromWalletAmount'] + $get_paypal_request_data['amount_details']['fromPromotionalAmount'];
			}
			if(User::check_sub_user_permission('can_make_purchases',$next_cart_bill) == false) {
				$notEnoughBalanceMsg = User::get_subuser_remaining_budget_message();
				return redirect()->route('view_cart')->with('errorFails', $notEnoughBalanceMsg);
			}
		}
		
		/*begin : check iteam price is less then 5$*/
		if(count($Cart) > 0){
			foreach ($Cart as $key_check => $value_check) {

				$requestCartIds[] = $value_check->id;

				if($value_check->is_review_edition == 1){
					continue;
				}

				if($value_check->plan->price < env('OLD_MINIMUM_SERVICE_PRICE')){
					return redirect()->route('view_cart')->with('errorFails', 'Minimum order amount for individual service cannot be less than '.env('OLD_MINIMUM_SERVICE_PRICE').'.');
				}
			}
		}else{
			return redirect()->route('view_cart')->with('errorFails', 'Your cart is empty.');
		}
		/*end : check iteam price is less then 5$*/

		/* begin : check for paypal deduction is not less than 1 */
		if($request_data['total'] < env('PAYPAL_MINIMUM_PAY_AMOUNT')) {
			return redirect()->route('view_cart')->with('errorFails', 'You need to pay minimum $'.env('PAYPAL_MINIMUM_PAY_AMOUNT').' from Paypal.');
		}
		/* end : check for paypal deduction is not less than 1 */

		try{
			$options = [
			    'SOLUTIONTYPE' => 'Sole',
			];
			$response = $this->provider->addOptions($options)->setExpressCheckout($request_data,$is_recurring_service);
			/*Create Log for payment request data*/
			$log = new PaymentLog;
			$log->user_id = $user->id;
			$log->receipt = json_encode($response);
			$log->status = "Request data";
			$log->payment_for = "service";
			$log->save();
		}catch(\Exception $e){
			return redirect()->route('view_cart')->with('errorFails', 'Something went wrong with PayPal');
		}

		if (!$response['paypal_link']) {
			return redirect()->route('view_cart')->with('errorFails', 'Something went wrong with PayPal');
		}else{
			$sessionData = [
				'requestCartIds' => $requestCartIds,
				'paypal_custom_data' => $request->all(),
				'from_wallet' => $get_paypal_request_data['amount_details']['fromWalletAmount'],
				'from_promotional' => $get_paypal_request_data['amount_details']['fromPromotionalAmount']
			];
			Session::put($invoice_id,$sessionData);
			/*Session::put('paypal_custom_data',$request->all());*/
			return redirect($response['paypal_link']);
		}
	}

	public function expressCheckoutSuccess(Request $request) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('view_cart')->with('errorFails', get_user_softban_message());
		}

		$user =$this->auth_user;
		$cartObj = new Cart;

		if (!$user) {
		    return redirect()->route('view_cart')->with('errorFails', 'Session timeout.');
		}

		$token = $request->get('token');
		$PayerID = $request->get('PayerID');

		$profile_id = '';
		$response_profile_detail = [];

		$response = $this->provider->getExpressCheckoutDetails($token);

		$profile_desc = $response['DESC'];

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($response);
		$log->status = "Payment response";
		$log->payment_for = "service";
		$log->save();

		if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
			return redirect()->route('view_cart')->with('errorFails', 'Error processing PayPal payment');
		}

		$invoice_id = $response['INVNUM'];

		if (!Session::has($invoice_id)) {
		    return redirect()->route('view_cart')->with('errorFails', 'Error processing PayPal payment');
		}
		
		$sessionData = Session::get($invoice_id);
		$fromWalletAmount = $sessionData['from_wallet'];
		$fromPromotionalAmount = $sessionData['from_promotional'];
		$paypal_custom_data = $sessionData['paypal_custom_data'];
		$requestCartIds = $sessionData['requestCartIds'];

		$is_custom_order = $paypal_custom_data['is_custom_order'];
		$is_job = $paypal_custom_data['is_job'];

		/*Begin : Remove unwanted services*/
        $cartObj->remove_unwanted_services();
		/*End : Remove unwanted services*/
		
		$Cart = Cart::where('uid',$user->id)->whereIn('id', $requestCartIds)->OrderBy('id', 'desc')->get();

		$review_edition_service_id = 0;
		if(count($Cart) > 0){
			foreach ($Cart as $key_check => $value_check) {
				if($value_check->is_review_edition == 1){
					$review_edition_service_id = $value_check->service_id;
					continue;
				}
				if($value_check->plan->price < env('OLD_MINIMUM_SERVICE_PRICE')){
					return redirect()->route('view_cart')->with('errorFails', 'Minimum order amount for individual service cannot be less than '.env('OLD_MINIMUM_SERVICE_PRICE').'.');
				}
			}
		}

		/*begin : check wallet amount*/
		if($fromWalletAmount > 0 && $fromPromotionalAmount > 0){
			if($fromWalletAmount > $user->earning || $fromPromotionalAmount > $user->promotional_fund){
				return redirect()->route('view_cart')->with('errorFails', 'You have not sufficient amount in your wallet and promotional fund.');
			}
		}else if($fromWalletAmount > 0){
			if($fromWalletAmount > $user->earning){
				return redirect()->route('view_cart')->with('errorFails', 'You have not sufficient amount in your wallet.');
			}
		}
		else if($fromPromotionalAmount > 0){
			if($fromPromotionalAmount > $user->promotional_fund){
				return redirect()->route('view_cart')->with('errorFails', 'You have not sufficient amount in your promotional fund.');
			}
		}
		/*end : check wallet amount*/

		/*Start :  Create recurring service profile*/	
		$is_recurring_service = $this->check_is_recurring($Cart);

		if($is_recurring_service == true){

			//Verify request amount and cart amount
			$get_paypal_request_data = $this->getPaypalRequestData($Cart,0,$invoice_id,0);
			$request_data = $get_paypal_request_data['request_data']['items'];
			$totalAmount = 0;
			foreach($request_data as $item){
				$item = (object)$item;
				$totalAmount += $item->price * $item->qty;
			}
			if($response['AMT'] != round($totalAmount,2)){
				$this->send_failed_notification($user->id);
				return redirect()->route('view_cart')->with('errorFails', 'Requested amount and order amount is not match, reload your cart page and try again');
			}

			$startdate = Carbon::now()->toAtomString();

			/*Total amount without discount*/
			/*$cartSubtotal = $selectedExtra = 0;
			foreach ($Cart as $row) {
				$cartSubtotal += ($row->plan->price * $row->quantity);
				foreach($row->extra as $cartExtra){
					$selectedExtra += $cartExtra->service_extra->price*$cartExtra->qty;
				}
			}
			$totalAmount = $cartSubtotal + $selectedExtra;*/

			$recuuring_data = [
				'PROFILESTARTDATE' => $startdate,
				'DESC' => $profile_desc,
			    'BILLINGPERIOD' => (env('PAYPAL_MODE') == 'sandbox')?'Day':'Month', // Can be 'Day', 'Week', 'SemiMonth', 'Month', 'Year'
			    'BILLINGFREQUENCY' => 1,
			    'AMT' => round_price($totalAmount),
			    'CURRENCYCODE' => 'USD'
			];

			$response_profile = $this->provider->createRecurringPaymentsProfile($recuuring_data, $token);

			if (isset($response_profile['ACK'])  && $response_profile['ACK'] == 'Failure') {
				$this->send_failed_notification($user->id);
				return redirect()->route('view_cart')->with('errorFails', 'Something went wrong while creating recurring profile');
			}

			
			$profile_id = $response_profile['PROFILEID'];

			sleep(3);

			$response_profile_detail = $this->provider->getRecurringPaymentsProfileDetails($profile_id);

			$status = $response_profile_detail['STATUS'];
			$txn_id = $response_profile_detail['PROFILEID'];
			
			if ($status == 'Active') {
				$status = 'Completed';
			}
		}else{

			//Check review edition service exists than increase count in services table for review edition
			$reviewEditionService = null;
			if($review_edition_service_id > 0){
				$reviewEditionService = Service::withoutGlobalScope('is_course')->select('id','review_edition_count','no_of_review_editions')->find($review_edition_service_id);
				if(!empty($reviewEditionService)){
					if($reviewEditionService->review_edition_count < $reviewEditionService->no_of_review_editions){
						$reviewEditionService->review_edition_count = $reviewEditionService->review_edition_count + 1;
						$reviewEditionService->save();
					}else{
						return redirect()->route('view_cart')->with('errorFails', 'Requested amount and order amount is not match, reload your cart page and try again');
					}
				}else{
					return redirect()->route('view_cart')->with('errorFails', 'Requested amount and order amount is not match, reload your cart page and try again');
				}
			}

			//Verify request amount and cart amount
			$get_paypal_request_data = $this->getPaypalRequestData($Cart,$fromWalletAmount,$invoice_id,$fromPromotionalAmount);
			$request_data = $get_paypal_request_data['request_data'];

			$totalAmount = 0;
			foreach($request_data['items'] as $item){
				$item = (object)$item;
				$totalAmount += $item->price * $item->qty;
			}

			if($response['AMT'] != round($totalAmount,2)){
				$this->send_failed_notification($user->id);

				//Descrease review edition purchase count
				if(!empty($reviewEditionService)){
					$reviewEditionService->review_edition_count = $reviewEditionService->review_edition_count - 1;
					$reviewEditionService->save();
				}
				return redirect()->route('view_cart')->with('errorFails', 'Requested amount and order amount is not match, reload your cart page and try again');
			}

			$payment_status = $this->provider->doExpressCheckoutPayment($request_data, $token, $PayerID);

			/*Create Log for payment response*/
			$log = new PaymentLog;
			$log->user_id = $user->id;
			$log->receipt = json_encode($payment_status);
			$log->status = "Payment response verification";
			$log->payment_for = "service";
			$log->save();

			if (isset($payment_status['ACK']) && $payment_status['ACK'] == 'Failure') {
				$this->send_failed_notification($user->id);
				return redirect()->route('view_cart')->with('errorFails', 'Something went wrong with PayPal');
			}

			$status = $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'];
			$txn_id = $payment_status['PAYMENTINFO_0_TRANSACTIONID'];
		}

		/*Session::forget('invoice_id');
		Session::forget('paypal_custom_data');
		Session::forget('from_wallet');*/
		Session::forget($invoice_id);

		if ($status == 'Completed') {
			$this->new_createNewOrder($Cart,$fromWalletAmount,$is_custom_order,$txn_id,$payBy='paypal',$profile_id,$response_profile_detail,null,false, $fromPromotionalAmount);

			return redirect("payment/details/".$txn_id)->with('errorSuccess','Order placed successfully');
		}else{
			return redirect()->route('view_cart')->with('error_msg', 'Error processing PayPal payment');
		}
	}

	public function check_is_recurring($Cart){
		
		$is_recurring_service = false;
		foreach ($Cart as $key => $value) {
			if($value->service->is_recurring == 1 || $value->plan->plan_type == 'monthly_access'){
				$is_recurring_service = true;
			}
		}
		return $is_recurring_service;
	}

	function activeCampaignEcomOrder($orderID){
		try{
			$order = Order::find($orderID);

			$customer = User::select('id','email','campaign_id')->find($order->uid);
			$connectionID = env('ACTIVECAMPAIGN_CONNECTION_ID');

			/*begin : ecomCustomer */
			$customerid = $customer->campaign_id;
			if(!$customer->campaign_id){
				$post = ['ecomCustomer' => 
					[
						'connectionid' => $connectionID,
						'externalid' => 'LGCUST'.$customer->id,
						'email' => $customer->email
					]
				];

				$response = active_campaign_api('ecomCustomers',$post);
				if($response && count($response) > 0 ){
					$response = json_decode($response);
					if(!isset($response->errors)){
						if(isset($response->ecomCustomer)){
							$customerid = $response->ecomCustomer->id;
							$customer->campaign_id = $customerid;
							$customer->save();
						}
					}
				}
			}
			/*end : ecomCustomer*/

			/*begin : ecomOrder */
			if($customerid){
				$extra = 0;
				$image_url = url('public/frontend/assets/img/No-image-found.jpg');
				if(isset($order->service->images[0])){
					if($order->service->images[0]->photo_s3_key != ''){
						$image_url = $order->service->images[0]->media_url; 
					}
				}

				$orderProducts[] = [
					"externalid" => 'SERVICE'.$order->service->id,
                    "name" => $order->service->title,
                    "price" => $order->price,
                    "quantity" => $order->qty,
                    "category" => ($order->service->category)?$order->service->category->category_name:'',
                    "sku" => $order->service->seo_url,
                    "imageUrl" => $image_url,
                    "productUrl" => route('services_details',[$order->service->user->username,$order->service->seo_url])
				];

    			foreach ($order->extra as $row) {
    				$extra += $row->qty * $row->price;

    				$orderProducts[] = [
						"externalid" => 'EXTRA'.$row->id,
	                    "name" => $row->title,
	                    "price" => $row->price,
	                    "quantity" => $row->qty
					];
    			}

    			$total_discount = $order->reorder_discount_amount + $order->coupon_discount + $order->volume_discount + $order->combo_discount;

    			$total_price = (($order->price * $order->qty) + $extra) - $total_discount;

    			$post = array(
		            "ecomOrder" => [
		                "externalid"   => 'LGORD'.$order->id,
		                "source"       => 1,
		                "email"     => $customer->email,
		                "orderProducts"      => $orderProducts,
		                "orderDiscounts" => [[
		                    "name"=> "Discount",
		                    "type"=> "order",
		                    "discountAmount"=> $total_discount
		                ]],
		                //"externalCreatedDate"=> "2016-04-13T17:41:39-04:00",
                		//"externalUpdatedDate"=> "2016-04-14T17:41:39-04:00",
		                "externalCreatedDate"=> date('Y-m-dTH:i:s-04:00',strtotime($order->created_at)),
		                "externalUpdatedDate"=> date('Y-m-dTH:i:s-04:00',strtotime($order->created_at)),
		                "shippingMethod"=> $order->payment_by,
		                "totalPrice"=> $total_price,
		                "discountAmount"=> $total_discount,
		                "currency"=> "USD",
		                "orderNumber"=> $order->order_no,
		                "connectionid"=> $connectionID,
		                "customerid"=> $customerid
		            ]
		        );

		        $response = active_campaign_api('ecomOrders',$post);

				if($response && count($response) > 0 ){
					$response = json_decode($response);
					if(!isset($response->errors)){
						if(isset($response->ecomOrder)){
							$ecomOrderId = $response->ecomOrder->id;
							$order->campaign_id = $ecomOrderId;
							$order->save();
						}
					}
				}
			}
			/*end : ecomOrder */

		}catch(\Exception $e){

		}
		return 1;
	}

	public function new_createNewOrder($Cart,$fromWalletAmount=0,$is_custom_order,$txn_id,$payBy='paypal',$profile_id = '',$profile_receipt = [],$bluesnapTempData = null,$from_ipn = false, $used_promotional=0){
		
		$affiliate_user = $affiliate_type = $affiliate_service_id = '';
		$allowed_service_list = [];

		if($from_ipn == true){
			$uid = $bluesnapTempData->user_id;
			$loginUser = User::find($uid);
		}else{
			$uid = get_user_id();
			if(Auth::user()->parent_id == 0) {
				$loginUser = Auth::user();
			} else {
				$loginUser = User::find($uid);
			}
		}

		$userObj = new User;
		$cartObj = new Cart;
		$discountPriority = DiscountPriority::OrderBy('priority','desc')->get();
		
		/*Create Orders*/

		$to_be_used_promotional = $used_promotional;
		$to_be_used_wallet = $fromWalletAmount;
		$to_be_used_cc_deposit = $loginUser->cc_earning;

		foreach ($Cart as $row) {
			if($row->influencer_id != 0) {
				$affiliate_type = 'influencer';
				$influencerid = $row->influencer_id;
				$allowed_service_list = InfluencerService::where('influencer_id',$influencerid)->pluck('service_id')->toArray();
				$influencer_data = Influencer::find($influencerid);
				if(!is_null($influencer_data) && !is_null($influencer_data->affiliate_user)) {
					$affiliate_id = $influencer_data->affiliate_user->affiliate_id;
				}
			} else if($row->influencer_id == 0) {
				if($from_ipn == true){
					$sessionData = json_decode($bluesnapTempData->cart_data);
					$affiliate_id = $sessionData->affiliate_id;
					$affiliate_type = $sessionData->affiliate_type ?? '';
					$affiliate_service_id = $sessionData->affiliate_service_id ?? '';
				}else{
					$affiliate_id = Cookie::get("affiliate_id");
					if(!empty(Cookie::get("affiliate_type"))) {
						$affiliate_type = Cookie::get("affiliate_type");
					}
					if(!empty(Cookie::get("affiliate_service_id"))) {
						$affiliate_service_id = Cookie::get("affiliate_service_id");
					}
				}
				if($affiliate_type == 'service' && $affiliate_service_id != '') {
					$affiliate_service = Service::withoutGlobalScope('is_course')->where('id',$affiliate_service_id)->select('id','uid')->first();
				}
			}

			$specialAffiliateFlag = 0;
			$affiliate_per = 15;

			$service = Service::withoutGlobalScope('is_course')->find($row->service_id);
			$plan = ServicePlan::find($row->plan_id);
			if (!empty($service) && !empty($plan)) {

				if($service->is_course == 1){
					//Check if user have already active monthly plan for same course than cancel that order
					$purchaseDetails = Service::purchaseCourseDetails($service->id,$uid,1);
					if(!empty($purchaseDetails)){
						if($purchaseDetails->subscription->is_cancel == 0){
							$oldCourseOrder = Order::find($purchaseDetails->id);
							// 2 - for auto cancel order
							$oldCourseOrder->cancel_order($oldCourseOrder,2); 
							sleep(1);
						}
					}
				}

				//Update price for review edition
				if($row->is_review_edition == 1){
					$row->plan->price = $row->plan->review_edition_price;
					$plan->price = $plan->review_edition_price;
				}

				$total_days = $plan->delivery_days;

				if (isset($row->extra)) {
					foreach ($row->extra as $extra) {
						$total_days += $extra->service_extra->delivery_days;
					}
				}
				if($service->is_job == 1)
				{
					$getSeller=JobOffer::where('service_id',$service->id)->where('status','is_payment')->orderBy('id','desc')->first();
					if($getSeller)
					{
						$seller_user_id=$getSeller->seller_id;	
						$delivery_days=$getSeller->delivery_days;
					}
					else
					{
						$seller_user_id=$service->uid;
						$delivery_days=$plan->delivery_days;
					}
				}
				else
				{
					$seller_user_id=$service->uid;	
					$delivery_days=$plan->delivery_days;
				}
				/* New order */

				$Order = new Order;
				$Order->no_of_revisions = $plan->no_of_revisions;
				$Order->uid = $uid;
				$Order->created_by = Auth::user()->id;
				$Order->discount_priority = json_encode($discountPriority);
				$Order->utm_source = $row->utm_source;
				$Order->utm_term = $row->utm_term;

				$Order->order_no = $Order->generate_orderno();
				if($service->is_job == 1)
				{
					$Order->seller_uid = $seller_user_id;
				}
				else
				{
					$Order->seller_uid = $service->uid;
				}
				$Order->service_id = $service->id;

				/* begin : affiliate demo for only normal service and custom order*/
				if (!empty($affiliate_id) && $service->is_job == 0 && $service->is_custom_order == 0) {
					
					/*begin : Check for premium user affiliate is enable*/
					$is_affiliate_enable = true;
					if($userObj->is_premium_seller($service->uid) == true){
						if($service->is_affiliate_link == 0){
							$is_affiliate_enable = false;
						}
					}
					/*end : Check for premium user affiliate is enable*/

					if($is_affiliate_enable == true){
						if(($affiliate_type == 'influencer' && in_array($service->id, $allowed_service_list)) || ($affiliate_type == 'service' && $affiliate_service->id == $service->id) || $affiliate_type == 'profile' || $affiliate_type == 'demo' || $affiliate_type == '') {
							$user = User::select('id')->where("affiliate_id", $affiliate_id)->first();
							if (!empty($user)) {
								if ($loginUser->id != $user->id && $user->id != $service->uid) {
										$Order->is_affiliate = '1';
										$Order->affiliate_id = $user->id;
										$affiliate_user = $user->id;

										/* ===== Special Affiliate ========= */
										$specialAffiliatedUser = Specialaffiliatedusers::where('uid', $user->id)->first();
										if ($specialAffiliatedUser != null) {
											$specialAffiliateFlag = 1;
										}
								}
							}
						}
					}
				}
				/* end : affiliate demo for only normal service and custom order*/

				$Order->plan_type = $plan->plan_type;
				if($service->is_job == 1)
				{
					$Order->delivery_days = $delivery_days;
					$Order->package_name = 'Job Package';
				}
				else
				{
					$Order->delivery_days = $plan->delivery_days;
					$Order->package_name = $plan->package_name;
				}
				$Order->price = $plan->price;
				$Order->qty = $row->quantity;
				$Order->start_date = date('Y-m-d H:i:s');
				
				if($profile_id){
					$days = (env('PAYPAL_MODE') == 'sandbox')?'day':'months';
					$Order->end_date = date("Y-m-d H:i:s", strtotime(" +1 ".$days));
				}else{
					$Order->end_date = date('Y-m-d H:i:s', strtotime("+" . $total_days . " days"));
				}
				
				$Order->txn_id = $txn_id;
				
				$Order->payment_by = $payBy;
				if ($payBy == "bluesnap") 
				{
					$Order->payment_status = 'Completed';
				}
				else
				{
					$Order->payment_status = 'Completed';
				}
				$Order->is_custom_order = $service->is_custom_order;
				$Order->is_job = $service->is_job;
				$Order->is_course = $service->is_course;

				if($Order->is_course == 1){
					if($profile_id){
						$Order->status = 'active'; 
						// new -> active (when payment success) -> complete / cancel
					}else{
						$Order->completed_date = date('Y-m-d H:i:s');
						$Order->status = 'completed';
					}
				}else{
					$Order->status = 'new';
				}

				if($profile_id){
					$Order->is_recurring = 1;
				}

				if($row->is_review_edition == 1){
					$Order->is_review_edition = 1;
				}

				$Order->is_new = 1;
				$Order->created_at = time();
				$Order->updated_at = time();
				$Order->save();

				if($service->is_job == 1)
				{
					$getJobOffer=JobOffer::where('service_id',$service->id)->where('status','is_payment')->orderBy('id','desc')->first();

					
					if(count($getJobOffer))
					{
						$getJobOffer->status='accepted';
						$getJobOffer->save();
					}
				}

				/*Create recurring order details*/
				if($profile_id){
					$orderSubscription = OrderSubscription::where('order_id',$Order->id)->first();
					if(count($orderSubscription) == 0){
						$orderSubscription = new OrderSubscription;
						$orderSubscription->order_id = $Order->id;
						$orderSubscription->profile_id = $profile_id;
					}

					$days = (env('PAYPAL_MODE') == 'sandbox')?'day':'months';
					$orderSubscription->expiry_date = date("Y-m-d H:i:s", strtotime(" +1 ".$days));
					$orderSubscription->last_buyer_payment_date = date("Y-m-d");
					if(count($profile_receipt)){
						$orderSubscription->receipt = json_encode($profile_receipt);
					}
					$orderSubscription->save();

				}

				if ($service->is_custom_order == 1) {
					$service->custom_order_status = 3;
					$service->save();
				}
				
				/* Buyer purchase order using wallet amount */ 
				$wallet_transaction_history = [];
				
				/* New order Extra */
				if($payBy == "wallet" || $payBy == "promotional"){
					$wallet_transaction_history['buyer'] = $Order->user->username;
					$wallet_transaction_history['buyer_email'] = $Order->user->email;
					$wallet_transaction_history['seller'] = $Order->seller->username;
					$wallet_transaction_history['seller_email'] = $Order->seller->email;
					$wallet_transaction_history['invoice_id'] = $Order->order_no;
					$wallet_transaction_history['transaction_id'] = $Order->txn_id;
					$wallet_transaction_history['total_amount'] = round($Order->qty*$Order->price,2);
					$wallet_transaction_history['reason'] = ($Order->is_course == 1)? "purchasing courses" : "purchasing services";
					$wallet_transaction_history['transactions'][] = [
						'title' => $service->title,
						'price' => $Order->price,
						'quantity' 	=> $Order->qty,
						'total' => round($Order->qty*$Order->price,2)
					];
				}
				if (isset($row->extra)) {
					foreach ($row->extra as $extra) {
						$serviceExtra = ServiceExtra::find($extra->service_extra_id);
						if (!empty($serviceExtra)) {
							$orderExtra = new OrderExtra;
							$orderExtra->order_id = $Order->id;
							$orderExtra->service_id = $service->id;
							$orderExtra->title = $serviceExtra->title;
							$orderExtra->description = $serviceExtra->description;
							$orderExtra->delivery_days = $serviceExtra->delivery_days;
							$orderExtra->price = $serviceExtra->price;
							$orderExtra->qty = $extra->qty;
							$orderExtra->created_at = time();
							$orderExtra->updated_at = time();
							$orderExtra->save();

							/* used wallet transactions history */
							if($payBy == "wallet" || $payBy == "promotional"){
								$wallet_transaction_history['transactions'][] = [
									'title' => $serviceExtra->title,
									'price' => $serviceExtra->price,
									'quantity' 	=> $extra->qty,
									'total' => round($extra->qty*$serviceExtra->price,2)
								];
								$wallet_transaction_history['total_amount'] = $wallet_transaction_history['total_amount'] + round($extra->qty*$serviceExtra->price,2);
							}
						}
					}
				}
				$extra_product_price = 0;
				if (isset($row->extra)) {
					foreach ($row->extra as $extra) {
						$extra_product_price += $extra->service_extra->price * $extra->qty;
					}
				}
				
				$buyerPromo = null;
				//For review edition service do not apply re-order discount
				if($row->remove_reorder_promo == 0 && $row->is_review_edition == 0){
					$buyerPromo = BuyerReorderPromo::where('seller_id', $Order->seller_uid)
					->where('buyer_id', $Order->uid)
					->where('service_id', $Order->service_id)
					->where('is_used', 0)
					->first();
				}

				$afterDiscountPrice = $row->plan->price * $row->quantity;
				$promoDiscountAmount = $couponDiscountAmount = $volumeDiscountAmount = $comboDiscountAmount = $bundle_id = 0; 

				/*Check any one discount is single*/
				$is_single = $cartObj->check_is_single_discount($discountPriority,$row,$buyerPromo,$Cart);
				foreach ($discountPriority as $priority) {

					//For review edition no any discount
					if($row->is_review_edition == 1){
						continue;
					}

					if($afterDiscountPrice <= env('MINIMUM_SERVICE_PRICE')){
						continue;
					}

					if($is_single != ''){
						if($is_single != $priority->discount_type){
							continue;
						} 
					}

					if($priority->discount_type == 'reorder_promo'){
						if(count($buyerPromo) > 0){
							$discountAmount = ($afterDiscountPrice * $buyerPromo->amount ) / 100;
							$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
							if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
								
								$promoDiscountAmount = $discountAmount;
								$buyerPromo->order_id = $Order->id;
								$buyerPromo->discount_amount = $promoDiscountAmount;
								$buyerPromo->is_used = 1;
								$buyerPromo->save();

								/* Update Order for discount price */
								$Order->reorder_discount_amount = round_price($promoDiscountAmount);
								$Order->save();

								$afterDiscountPrice -= $discountAmount;
							}
						}
					}elseif($priority->discount_type == 'coupan'){
						if($row->coupon){
							$coupanApplied = CoupanApplied::where('coupan_code_id', $row->coupon->id)->count();
							if ($coupanApplied < $row->coupon->no_of_uses) {
								if($row->coupon->discount_type=="amount"){
									$discountAmount = $row->coupon->discount;
									$checkDiscountedPrice = ($afterDiscountPrice + $extra_product_price) - $discountAmount;
									if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
										$couponDiscountAmount = $discountAmount;
										$afterDiscountPrice -= $discountAmount;
									}
								} else {
									/* $discountAmount = 1 * (($row->coupon->discount/100) * $row->plan->price); */
									$discountAmount = 1 * (($row->coupon->discount/100) * ($afterDiscountPrice + $extra_product_price));
									$checkDiscountedPrice = ($afterDiscountPrice + $extra_product_price) - $discountAmount;
									if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
										$couponDiscountAmount = $discountAmount;
										$afterDiscountPrice -= $discountAmount;
									}
								}

								/* Update Order for discount price */
								$Order->coupon_discount = round_price($couponDiscountAmount);
								$Order->save();

								$couponApplied = new CoupanApplied;
								$couponApplied->order_id = $Order->id;
								$couponApplied->coupan_code_id = $row->coupon->id;
								$couponApplied->service_id = $row->coupon->service_id;
								$couponApplied->coupan_code = $row->coupon->coupan_code;
								$couponApplied->expiry_date = $row->coupon->expiry_date;
								$couponApplied->discount_type = $row->coupon->discount_type;
								$couponApplied->discount = $row->coupon->discount;
								$couponApplied->save();

							}
						}
					}elseif($priority->discount_type == 'volume_discount'){
						if($userObj->is_premium_seller($row->service->uid)){
							$v_discount_per = 0;
							foreach($row->service->volume_discount as $value1){
								if($row->quantity >= $value1->volume){
									$v_discount_per = $value1->discount;
								}
							}
							if($v_discount_per > 0){
								$discountAmount = ($afterDiscountPrice * $v_discount_per ) / 100;
								$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
								if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
									$volumeDiscountAmount = $discountAmount;
									$afterDiscountPrice -= $discountAmount;

									$Order->volume_discount = round_price($volumeDiscountAmount);
									$Order->save();
								}
							}
						}
					}elseif($priority->discount_type == 'combo_discount'){
						if($userObj->is_premium_seller($row->service->uid)){
							$combo_detail = $cartObj->check_is_combo($row->service_id,$Cart);
							if($combo_detail->combo_discount_per > 0){
								$discountAmount = ($afterDiscountPrice * $combo_detail->combo_discount_per ) / 100;
								$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
								if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
									$comboDiscountAmount = $discountAmount;
									$afterDiscountPrice -= $discountAmount;

									$bundle_id = $combo_detail->bundle_id;

									$Order->combo_discount = round_price($comboDiscountAmount);
									$Order->save();
								}
							}
						}
					}
				}

				if($comboDiscountAmount > 0){
					$bundleDiscount = BundleDiscount::with('bundle_services')->find($bundle_id);
					if(count($bundleDiscount) > 0){
						$Order->combo_service_data = json_encode($bundleDiscount);
					}
				}

				$product_price = ($row->plan->price * $row->quantity) + $extra_product_price - round_price($promoDiscountAmount) - round_price($couponDiscountAmount) - round_price($volumeDiscountAmount) - round_price($comboDiscountAmount);

				$product_price = round_price($product_price);

				/*begin : get admin service charge*/
				$product_service_charge = get_service_change($product_price,$Order->is_new);
				/*end : get admin service charge*/

				/* calculate total amount */
				$Order->order_total_amount = $this->calculate_amount($Order);

				//Reset affeliation settings : For review edition if total order amount is 0
				if($Order->order_total_amount == 0 && $row->is_review_edition == 1){
					$Order->is_affiliate = 0;
					$Order->affiliate_id = 0;
					$specialAffiliateFlag = 0;
					$product_service_charge = 0;
				}

				if ($specialAffiliateFlag == 1) {
					$product_service_charge = 0;
					$affiliate_per = 25;
				}

				/*For special seller*/
				if($Order->seller->is_special_seller == 1 || $service->by_us_for_us == 1){
					$product_service_charge = 0;
					$Order->is_special_order = 1;
				}
				
				//Update used promotional amount to specific order
				if($Order->is_recurring == 0 && $to_be_used_promotional > 0){
					if($Order->order_total_amount < $to_be_used_promotional) {
						$Order->used_promotional_fund = $Order->order_total_amount;
					} else {
						$Order->used_promotional_fund = $to_be_used_promotional;
					}
					$to_be_used_promotional -= $Order->used_promotional_fund;
				}

				//Update used wallet amount to specific order
				if($Order->is_recurring == 0 && $to_be_used_wallet > 0){
					if(($Order->order_total_amount - $Order->used_promotional_fund) < $to_be_used_wallet) {
						$Order->used_wallet_fund = ($Order->order_total_amount - $Order->used_promotional_fund);
					} else {
						$Order->used_wallet_fund = $to_be_used_wallet;
					}
					$to_be_used_wallet -= $Order->used_wallet_fund;
				}
				
				//Updated used cc_earning to specific order
				if($Order->is_recurring == 0 && $to_be_used_cc_deposit > 0){
					if(isset($Order->used_wallet_fund) && $Order->used_wallet_fund > 0){
						if($Order->used_wallet_fund < $to_be_used_cc_deposit) {
							$Order->used_cc_deposit = $Order->used_wallet_fund; // new field
						} else {
							$Order->used_cc_deposit = $to_be_used_cc_deposit;
						}
						$to_be_used_cc_deposit -= $Order->used_cc_deposit;
					}
				}

				//Save order data
				$Order->service_charge = $product_service_charge;
				$Order->save();

				/*Update no of service purchase*/
				$service->no_of_purchase = $service->no_of_purchase + 1;

				// Update review edition purchase count
				if($row->is_review_edition == 1){
					//already increase it before make payment
					//$service->review_edition_count = $service->review_edition_count + 1;
				}

				$service->save();

				/* Buyer Transactions Start */
				$buyerTransaction = new BuyerTransaction;
				$buyerTransaction->order_id = $Order->id;
				$buyerTransaction->buyer_id = $Order->uid;
				if($payBy == 'paypal'){
					$buyerTransaction->note = 'Debit from Credit Card/Paypal';
					if(isset($Order->used_promotional_fund) && $Order->used_promotional_fund && isset($Order->used_wallet_fund) && $Order->used_wallet_fund){
						$buyerTransaction->paypal_amount = ($product_price  - $Order->used_promotional_fund - $Order->used_wallet_fund);
						$buyerTransaction->wallet_amount = $Order->used_wallet_fund;
						$buyerTransaction->promotional_amount = $Order->used_promotional_fund;
					}else if(isset($Order->used_promotional_fund) && $Order->used_promotional_fund){
						$buyerTransaction->paypal_amount = ($product_price  - $Order->used_promotional_fund);
						$buyerTransaction->promotional_amount = $Order->used_promotional_fund;
					}else if(isset($Order->used_wallet_fund) && $Order->used_wallet_fund){
						$buyerTransaction->paypal_amount = ($product_price  - $Order->used_wallet_fund);
						$buyerTransaction->wallet_amount = $Order->used_wallet_fund;
					}else{
						$buyerTransaction->paypal_amount = $product_price;
					}
					$buyerTransaction->payment_processing_fee = calculate_payment_processing_fee($buyerTransaction->paypal_amount);
				}
				elseif ($payBy == 'bluesnap'){
					$buyerTransaction->note = 'Debit from Credit Card';
					$buyerTransaction->creditcard_amount = $product_price;
					$buyerTransaction->payment_processing_fee = calculate_payment_processing_fee($buyerTransaction->creditcard_amount);
				}
				else{
					$buyerTransaction->note = 'Debit from Wallet';
					$buyerTransaction->wallet_amount = $product_price;
				}
				
				$buyerTransaction->anount = $product_price;
				$buyerTransaction->status = 'deposit';
				$buyerTransaction->created_at = time();
				$buyerTransaction->save();
				/* Buyer Transactions End */

				/* Seller Earnings Start */
				$SellerEarning = new SellerEarning;
				$SellerEarning->order_id = $Order->id;
				$SellerEarning->seller_id = $Order->seller_uid;
				$SellerEarning->note = 'Pending Clearance';

				if ($Order->is_affiliate == "1") {
					$total_main_amount = $product_price - $product_service_charge;
					$SellerEarning->anount = $total_main_amount - (($product_price * $affiliate_per) / 100);
				} else {
					$SellerEarning->anount = $product_price - $product_service_charge;
				}

				$SellerEarning->status = 'pending_clearance';
				$SellerEarning->created_at = time();
				$SellerEarning->save();
				/* Seller Earnings End */

				/* Affiliate Earnings End */
				if ($Order->is_affiliate == "1") {
					$affiliate_earning = new AffiliateEarning;
					$affiliate_earning->order_id = $Order->id;
					$affiliate_earning->affiliate_user_id = $affiliate_user;
					$affiliate_earning->seller_id = $Order->seller_uid;
					$affiliate_earning->status = 'pending_clearance';
					if($affiliate_type == '' || $affiliate_type != 'influencer') {
						$affiliate_earning->affiliate_type = "demo";
						$affiliate_earning->amount = ($product_price * $affiliate_per) / 100;
					} else {
						$affiliate_earning->affiliate_type = $affiliate_type;
						$affiliate_earning->amount = ($product_price * 15) / 100; //15% for influencer
					}
					$affiliate_earning->save();

					//remove cookies of affiliation 
					Cookie::queue(Cookie::forget('affiliate_id'));
					Cookie::queue(Cookie::forget('affiliate_type'));
					Cookie::queue(Cookie::forget('affiliate_service_id'));
				}

				if($Order->is_course == 1 && $Order->is_recurring == 0){
					//Update payment date on seller earning
					Order::storeSellerEarningPaymentDate($Order);
				}

				/* Send wallet transaction email to admin */
				if($payBy == "wallet" || $payBy == "promotional"){
					$discount = $Order->reorder_discount_amount + $Order->coupon_discount + $Order->volume_discount + $Order->combo_discount;
					if($discount > 0){
						$wallet_transaction_history['discount'] = $discount;
					}
					$Order->sendWalletTransactionEmail($wallet_transaction_history);
				}
			}

			//Delete Cart
			CartExtra::where('cart_id',$row->id)->delete();
			$row->delete();

			// send order data to wicked report
			send_order_data_to_wicked_report($Order);

			//store transaction history if purchase by sub user
			if(Auth::user()->parent_id != 0) {
				$this->store_sub_user_transaction_history($Order);
			}
		}

		//Send order notification
		$this->send_order_complete_notification($txn_id);
		
		/* Update buyer wallet (when pay by wallet and paypal)*/
		if($fromWalletAmount > 0){
			//$buyer = User::find($uid);
			$loginUser->earning = $loginUser->earning - $fromWalletAmount;
			$loginUser->pay_from_wallet = $loginUser->pay_from_wallet + $fromWalletAmount;
			$loginUser->save();
		}
		/* Update buyer promotional fund (when pay by promotional fund and paypal)*/
		if($used_promotional > 0){
			//$buyer = User::find($uid);
			$loginUser->promotional_fund = $loginUser->promotional_fund - $used_promotional;
			$loginUser->save();

			/* create promotional transaction history */
			$promotional_transaction = new UserPromotionalFundTransaction;
			$promotional_transaction->user_id = $uid;
			$promotional_transaction->order_id = $Order->id;
			$promotional_transaction->amount = $used_promotional;
			$promotional_transaction->type = 0; //type - service
			$promotional_transaction->transaction_type = 0; //type - deduct
			$promotional_transaction->save();
		}

		return 1;
	}

	//Create temp order for credit card /skrill order
	public function createTempOrder($request_payment_data = null){
		if(count($request_payment_data) == 0){
			return (Object)['success'=>false,'message'=>'Request data not found.'];
		}
		DB::beginTransaction();
		try {
			$uid = User::get_parent_id();
			$Cart = $request_payment_data->Cart;
			$payBy = $request_payment_data->payBy;
			$txn_id = $request_payment_data->txn_id;
			
			$form_request = $request_payment_data->form_request;
			$fromWalletAmount = $request_payment_data->fromWalletAmount;
			$fromPromotionalAmount = $request_payment_data->fromPromotionalAmount;
			
			$loginUser = User::find($uid);

			$userObj = new User;
			$cartObj = new Cart;
			$orderObj = new Order;

			$affiliate_user = $affiliate_type = $affiliate_service_id = '';
			$allowed_service_list = [];
			
			$discountPriority = DiscountPriority::OrderBy('priority','desc')->get();
			
			/*Create Orders*/
			$to_be_used_promotional = $fromPromotionalAmount;
			$to_be_used_wallet = $fromWalletAmount;
			$to_be_used_cc_deposit = $loginUser->cc_earning;
			$used_affiliate = false;

			foreach ($Cart as $row) {

				if($row->influencer_id != 0) {
					$affiliate_type = 'influencer';
					$influencerid = $row->influencer_id;
					$allowed_service_list = InfluencerService::where('influencer_id',$influencerid)->pluck('service_id')->toArray();
					$influencer_data = Influencer::find($influencerid);
					if(!is_null($influencer_data) && !is_null($influencer_data->affiliate_user)) {
						$affiliate_id = $influencer_data->affiliate_user->affiliate_id;
					}
				} else if($row->influencer_id == 0) {
					$affiliate_id = Cookie::get("affiliate_id");
					if(!empty(Cookie::get("affiliate_type"))) {
						$affiliate_type = Cookie::get("affiliate_type");
					}
					if(!empty(Cookie::get("affiliate_service_id"))) {
						$affiliate_service_id = Cookie::get("affiliate_service_id");
					}
					if($affiliate_type == 'service' && $affiliate_service_id != '') {
						$affiliate_service = Service::withoutGlobalScope('is_course')->where('id',$affiliate_service_id)->select('id','uid')->first();
					}
				}

				$specialAffiliateFlag = 0;
				$affiliate_per = 15;

				$service = Service::withoutGlobalScope('is_course')->find($row->service_id);
				$plan = ServicePlan::find($row->plan_id);
				if (!empty($service) && !empty($plan)) {

					//Update price for review edition
					if($row->is_review_edition == 1){
						$row->plan->price = $row->plan->review_edition_price;
						$plan->price = $plan->review_edition_price;
					}

					$total_days = $plan->delivery_days;

					if (isset($row->extra)) {
						foreach ($row->extra as $extra) {
							$total_days += $extra->service_extra->delivery_days;
						}
					}
					if($service->is_job == 1)
					{
						$getSeller=JobOffer::where('service_id',$service->id)->where('status','is_payment')->orderBy('id','desc')->first();
						if($getSeller)
						{
							$seller_user_id=$getSeller->seller_id;	
							$delivery_days=$getSeller->delivery_days;
						}
						else
						{
							$seller_user_id=$service->uid;
							$delivery_days=$plan->delivery_days;
						}
					}
					else
					{
						$seller_user_id=$service->uid;	
						$delivery_days=$plan->delivery_days;
					}
					/* New order */

					$Order = new TempOrder;
					$Order->no_of_revisions = $plan->no_of_revisions;
					$Order->uid = $uid;
					$Order->created_by = Auth::user()->id;
					$Order->discount_priority = json_encode($discountPriority);
					$Order->utm_source = $row->utm_source;
					$Order->utm_term = $row->utm_term;

					$Order->order_no = $orderObj->generate_orderno();
					if($service->is_job == 1)
					{
						$Order->seller_uid = $seller_user_id;
					}
					else
					{
						$Order->seller_uid = $service->uid;
					}
					$Order->service_id = $service->id;

					/* begin : affiliate demo for only normal service and custom order*/
					if ($used_affiliate == false && !empty($affiliate_id) && $service->is_job == 0 && $service->is_custom_order == 0) {
						
						/*begin : Check for premium user affiliate is enable*/
						$is_affiliate_enable = true;
						if($userObj->is_premium_seller($service->uid) == true){
							if($service->is_affiliate_link == 0){
								$is_affiliate_enable = false;
							}
						}
						/*end : Check for premium user affiliate is enable*/

						if($is_affiliate_enable == true){
							if(($affiliate_type == 'influencer' && in_array($service->id, $allowed_service_list)) || ($affiliate_type == 'service' && $affiliate_service->id == $service->id) || $affiliate_type == 'profile' || $affiliate_type == 'demo' || $affiliate_type == '') {
								$user = User::select('id')->where("affiliate_id", $affiliate_id)->first();
								if (!empty($user)) {
									if ($loginUser->id != $user->id && $user->id != $service->uid) {
											$Order->is_affiliate = '1';
											$Order->affiliate_id = $user->id;
											$affiliate_user = $user->id;

											/* ===== Special Affiliate ========= */
											$specialAffiliatedUser = Specialaffiliatedusers::where('uid', $user->id)->first();
											if ($specialAffiliatedUser != null) {
												$specialAffiliateFlag = 1;
											}
									}
								}
							}
						}
					}
					/* end : affiliate demo for only normal service and custom order*/

					$Order->plan_type = $plan->plan_type;
					if($service->is_job == 1)
					{
						$Order->delivery_days = $delivery_days;
						$Order->package_name = 'Job Package';
					}
					else
					{
						$Order->delivery_days = $plan->delivery_days;
						$Order->package_name = $plan->package_name;
					}
					$Order->price = $plan->price;
					$Order->qty = $row->quantity;
					$Order->start_date = date('Y-m-d H:i:s');
					
					if($service->is_recurring == 1){
						$Order->is_recurring = 1;
						$days = (env('PAYPAL_MODE') == 'sandbox')?'day':'months';
						$Order->end_date = date("Y-m-d H:i:s", strtotime(" +1 ".$days));
					}else{
						$Order->end_date = date('Y-m-d H:i:s', strtotime("+" . $total_days . " days"));
					}
					
					$Order->txn_id = $txn_id;
					$Order->payment_by = $payBy;
					$Order->payment_status = 'Pending';
					$Order->is_custom_order = $service->is_custom_order;
					$Order->is_job = $service->is_job;
					$Order->is_course = $service->is_course;

					if($Order->is_course == 1){
						$Order->completed_date = date('Y-m-d H:i:s');
						$Order->status = 'completed';
					}else{
						$Order->status = 'new';
					}

					if($row->is_review_edition == 1){
						$Order->is_review_edition = 1;
					}
					$Order->is_new = 1;
					$Order->save();

					/* New order Extra */
					$extra_product_price = 0;
					if (isset($row->extra)) {
						foreach ($row->extra as $extra) {
							$serviceExtra = ServiceExtra::find($extra->service_extra_id);
							if (!empty($serviceExtra)) {
								$orderExtra = new TempOrderExtra;
								$orderExtra->order_id = $Order->id;
								$orderExtra->service_id = $service->id;
								$orderExtra->title = $serviceExtra->title;
								$orderExtra->description = $serviceExtra->description;
								$orderExtra->delivery_days = $serviceExtra->delivery_days;
								$orderExtra->price = $serviceExtra->price;
								$orderExtra->qty = $extra->qty;
								$orderExtra->save();
								$extra_product_price += $serviceExtra->price * $extra->qty;
							}
						}
					}
					
					$buyerPromo = null;
					$usedPromoIds = [];
					//For review edition service do not apply re-order discount
					if($row->remove_reorder_promo == 0 && $row->is_review_edition == 0){
						$buyerPromo = BuyerReorderPromo::where('seller_id', $Order->seller_uid)
						->where('buyer_id', $Order->uid)
						->where('service_id', $Order->service_id)
						->where('is_used', 0)
						->whereNotIn('id',$usedPromoIds)
						->first();
						if(!empty($buyerPromo)){
							array_push($usedPromoIds,$buyerPromo->id);
						}
					}

					$afterDiscountPrice = $row->plan->price * $row->quantity;
					$promoDiscountAmount = $couponDiscountAmount = $volumeDiscountAmount = $comboDiscountAmount = $bundle_id = 0; 

					/*Check any one discount is single*/
					$is_single = $cartObj->check_is_single_discount($discountPriority,$row,$buyerPromo,$Cart);
					foreach ($discountPriority as $priority) {

						//For review edition no any discount
						if($row->is_review_edition == 1){
							continue;
						}

						if($afterDiscountPrice <= env('MINIMUM_SERVICE_PRICE')){
							continue;
						}

						if($is_single != ''){
							if($is_single != $priority->discount_type){
								continue;
							} 
						}

						if($priority->discount_type == 'reorder_promo'){
							if(count($buyerPromo) > 0){
								$discountAmount = ($afterDiscountPrice * $buyerPromo->amount ) / 100;
								$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
								if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
									$promoDiscountAmount = $discountAmount;
									/* Update Order for discount price */
									$Order->reorder_discount_amount = round_price($promoDiscountAmount);
									$Order->save();

									$afterDiscountPrice -= $discountAmount;
								}
							}
						}elseif($priority->discount_type == 'coupan'){
							if($row->coupon){
								$coupanApplied = CoupanApplied::where('coupan_code_id', $row->coupon->id)->count();
								if ($coupanApplied < $row->coupon->no_of_uses) {
									if($row->coupon->discount_type=="amount"){
										$discountAmount = $row->coupon->discount;
										$checkDiscountedPrice = ($afterDiscountPrice + $extra_product_price) - $discountAmount;
										if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
											$couponDiscountAmount = $discountAmount;
											$afterDiscountPrice -= $discountAmount;
										}
									} else {
										/* $discountAmount = 1 * (($row->coupon->discount/100) * $row->plan->price); */
										$discountAmount = 1 * (($row->coupon->discount/100) * ($afterDiscountPrice + $extra_product_price));
										$checkDiscountedPrice = ($afterDiscountPrice + $extra_product_price) - $discountAmount;
										if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
											$couponDiscountAmount = $discountAmount;
											$afterDiscountPrice -= $discountAmount;
										}
									}

									/* Update Order for discount price */
									$Order->coupon_discount = round_price($couponDiscountAmount);
									$Order->save();

									$couponApplied = new TempCoupanApplied;
									$couponApplied->order_id = $Order->id;
									$couponApplied->coupan_code_id = $row->coupon->id;
									$couponApplied->service_id = $row->coupon->service_id;
									$couponApplied->coupan_code = $row->coupon->coupan_code;
									$couponApplied->expiry_date = $row->coupon->expiry_date;
									$couponApplied->discount_type = $row->coupon->discount_type;
									$couponApplied->discount = $row->coupon->discount;
									$couponApplied->save();

								}
							}
						}elseif($priority->discount_type == 'volume_discount'){
							if($userObj->is_premium_seller($row->service->uid)){
								$v_discount_per = 0;
								foreach($row->service->volume_discount as $value1){
									if($row->quantity >= $value1->volume){
										$v_discount_per = $value1->discount;
									}
								}
								if($v_discount_per > 0){
									$discountAmount = ($afterDiscountPrice * $v_discount_per ) / 100;
									$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
									if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
										$volumeDiscountAmount = $discountAmount;
										$afterDiscountPrice -= $discountAmount;

										$Order->volume_discount = round_price($volumeDiscountAmount);
										$Order->save();
									}
								}
							}
						}elseif($priority->discount_type == 'combo_discount'){
							if($userObj->is_premium_seller($row->service->uid)){
								$combo_detail = $cartObj->check_is_combo($row->service_id,$Cart);
								if($combo_detail->combo_discount_per > 0){
									$discountAmount = ($afterDiscountPrice * $combo_detail->combo_discount_per ) / 100;
									$checkDiscountedPrice = $afterDiscountPrice - $discountAmount;
									if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
										$comboDiscountAmount = $discountAmount;
										$afterDiscountPrice -= $discountAmount;

										$bundle_id = $combo_detail->bundle_id;

										$Order->combo_discount = round_price($comboDiscountAmount);
										$Order->save();
									}
								}
							}
						}
					}

					if($comboDiscountAmount > 0){
						$bundleDiscount = BundleDiscount::with('bundle_services')->find($bundle_id);
						if(count($bundleDiscount) > 0){
							$Order->combo_service_data = json_encode($bundleDiscount);
						}
					}

					$product_price = ($row->plan->price * $row->quantity) + $extra_product_price - round_price($promoDiscountAmount) - round_price($couponDiscountAmount) - round_price($volumeDiscountAmount) - round_price($comboDiscountAmount);

					$product_price = round_price($product_price);

					/*begin : get admin service charge*/
					$product_service_charge = get_service_change($product_price,$Order->is_new);
					/*end : get admin service charge*/

					/* calculate total amount */
					$Order->order_total_amount = $this->calculate_amount($Order);

					//Reset affeliation settings : For review edition if total order amount is 0
					if($Order->order_total_amount == 0 && $row->is_review_edition == 1){
						$Order->is_affiliate = 0;
						$Order->affiliate_id = 0;
						$specialAffiliateFlag = 0;
						$product_service_charge = 0;
					}

					if ($specialAffiliateFlag == 1) {
						$product_service_charge = 0;
						$affiliate_per = 25;
					}

					/*For special seller*/
					if($Order->seller->is_special_seller == 1 || $service->by_us_for_us == 1){
						$product_service_charge = 0;
						$Order->is_special_order = 1;
					}
					
					//Update used promotional amount to specific order
					if($Order->is_recurring == 0 && $to_be_used_promotional > 0){
						if($Order->order_total_amount < $to_be_used_promotional) {
							$Order->used_promotional_fund = $Order->order_total_amount;
						} else {
							$Order->used_promotional_fund = $to_be_used_promotional;
						}
						$to_be_used_promotional -= $Order->used_promotional_fund;
					}

					//Update used wallet amount to specific order
					if($Order->is_recurring == 0 && $to_be_used_wallet > 0){
						if($Order->order_total_amount < $to_be_used_wallet) {
							$Order->used_wallet_fund = $Order->order_total_amount;
						} else {
							$Order->used_wallet_fund = $to_be_used_wallet;
						}
						$to_be_used_wallet -= $Order->used_wallet_fund;
					}
					
					//Updated used cc_earning to specific order
					if($Order->is_recurring == 0 && $to_be_used_cc_deposit > 0){
						if(isset($Order->used_wallet_fund) && $Order->used_wallet_fund > 0){
							if($Order->used_wallet_fund < $to_be_used_cc_deposit) {
								$Order->used_cc_deposit = $Order->used_wallet_fund; // new field
							} else {
								$Order->used_cc_deposit = $to_be_used_cc_deposit;
							}
							$to_be_used_cc_deposit -= $Order->used_cc_deposit;
						}
					}

					$valid_order = true;
					if($Order->is_review_edition == 1){
						//It will release after 60 minutes by cron if not purchase order
						if($service->review_edition_count < $service->no_of_review_editions){
							$service->review_edition_count = $service->review_edition_count + 1;
							$service->save();
						}else{
							$valid_order = false;
						}
					}

					//Save order data
					$Order->service_charge = $product_service_charge;
					$Order->save();

					/* Affiliate Earnings End */
					if ($Order->is_affiliate == "1") {
						$affiliate_earning = new TempAffiliateEarning;
						$affiliate_earning->order_id = $Order->id;
						$affiliate_earning->affiliate_user_id = $affiliate_user;
						$affiliate_earning->seller_id = $Order->seller_uid;
						$affiliate_earning->status = 'pending_clearance';
						if($affiliate_type == '' || $affiliate_type != 'influencer') {
							$affiliate_earning->affiliate_type = "demo";
							$affiliate_earning->amount = ($product_price * $affiliate_per) / 100;
						} else {
							$affiliate_earning->affiliate_type = $affiliate_type;
							$affiliate_earning->amount = ($product_price * 15) / 100; //15% for influencer
						}
						$affiliate_earning->save();

						$used_affiliate = true;
					}


				}
			}

			if($valid_order == true){
				DB::commit();
				Session::put('temp_txn_id',$txn_id);
				return (Object)['success'=>true,'txn_id'=>$txn_id,'Temp order created successfully.'];
			}else{
				DB::rollback();
				return (Object)['success'=>false,'message'=>'Something went wrong.'];
			}
		} catch (\Exception $e) {
			DB::rollback();
			return (Object)['success'=>false,'message'=>'Something went wrong, while creating order.'];
		}
	}

	public function processTempOrder($tempOrders,$txn_id)
    {
        /* clone temp order to main orders table */
        DB::beginTransaction();
		try {
			$new_order_id = 0;
			$orderObj = new Order;
			$current_time = Carbon::now();
            foreach($tempOrders as $order){
                //Create main order
                $newOrder = $order;
                $newOrder = $newOrder->toArray();
                $newOrder['order_no'] = $orderObj->generate_orderno();
                $newOrder['payment_status'] = 'Completed';
                $newOrder['start_date'] = $current_time;
                $newOrder['created_at'] = $current_time;
                $newOrder['updated_at'] = $current_time;
                unset($newOrder['id']);
                unset($newOrder['order_id']);
                $new_order_id = Order::insertGetId($newOrder);
                
                //Create extra
                $tempNewExtra = TempOrderExtra::where('order_id',$order->id)->get();
                if(count($tempNewExtra) > 0){
                    foreach($tempNewExtra as $extra){
                        $cloneExtra = $extra;
                        $cloneExtra = $cloneExtra->toArray();
                        unset($cloneExtra['id']);
                        $cloneExtra['created_at'] = $current_time;
                        $cloneExtra['updated_at'] = $current_time;
                        $cloneExtra['order_id'] = $new_order_id;
                        OrderExtra::insert($cloneExtra);

                        //delete temp extra
                        $extra->delete();
                    }
                }
            
                //Create Coupon apply
                $tempCoupanApplied = TempCoupanApplied::where('order_id',$order->id)->first();
                if(count($tempCoupanApplied) > 0){
                    $cloneCouponApplied = $tempCoupanApplied;
                    $cloneCouponApplied = $cloneCouponApplied->toArray();
                    unset($cloneCouponApplied['id']);
                    $cloneCouponApplied['created_at'] = $current_time;
                    $cloneCouponApplied['updated_at'] = $current_time;
                    $cloneCouponApplied['order_id'] = $new_order_id;
                    CoupanApplied::insert($cloneCouponApplied);

                    //delete temp coupon applied
                    $tempCoupanApplied->delete();
                }

                //Create affiliate earning
                if($order->is_affiliate == 1){
                    $tempAffiliateEarning = TempAffiliateEarning::where('order_id',$order->id)->first();
                    if(!empty($tempAffiliateEarning)){
                        $cloneAffiliateEarning = $tempAffiliateEarning;
                        $cloneAffiliateEarning = $cloneAffiliateEarning->toArray();
                        unset($cloneAffiliateEarning['id']);
                        $cloneAffiliateEarning['created_at'] = $current_time;
                        $cloneAffiliateEarning['updated_at'] = $current_time;
                        $cloneAffiliateEarning['order_id'] = $new_order_id;
                        AffiliateEarning::insert($cloneAffiliateEarning);
    
                        //delete temp coupon applied
                        $tempAffiliateEarning->delete();
                    }
                }

                //delete cart
                $cart = Cart::where('service_id',$order->service_id)->first();
                if(!empty($cart)){
                    CartExtra::where('cart_id',$cart->id)->delete();
                    $cart->delete();
                }

                //Delete Temp Order
                $order->delete();

                //Get Order data
                $Order = Order::find($new_order_id);

                //If order as job?
                if($Order->is_job == 1){
					$jobOffer=JobOffer::select('id','status')->where('service_id',$Order->service_id)->where('status','is_payment')->orderBy('id','desc')->first();
					if(!empty($jobOffer)){
						$jobOffer->status='accepted';
						$jobOffer->save();
					}
				}

                //Get Service Object
                $service = $Order->service;

                //If Order as Custom order
                if ($Order->is_custom_order == 1) {
                    if($service && $service->is_custom_order == 1){
                        $service->custom_order_status = 3;
                    }
				}

                //Update review edition purchase count
                if($Order->is_review_edition == 1){
                    //Note : count already added by temp order
                    //$service->review_edition_count = $service->review_edition_count + 1;
                }

                //Update no of service purchase
                $service->no_of_purchase = $service->no_of_purchase + 1;
                $service->save();

                //Check re-order promo applied
                if($Order->reorder_discount_amount > 0){
                    $buyerPromo = BuyerReorderPromo::where('seller_id', $Order->seller_uid)
					->where('buyer_id', $Order->uid)
					->where('service_id', $Order->service_id)
					->where('is_used', 0)
					->first();
                    if(!empty($buyerPromo)){
                        $buyerPromo->order_id = $Order->id;
                        $buyerPromo->discount_amount = $Order->reorder_discount_amount;
                        $buyerPromo->is_used = 1;
                        $buyerPromo->save();
                    }
                }

                //Reset affiliate settings : For review edition if total order amount is 0
                
				$product_service_charge = get_service_change($Order->order_total_amount,$Order->is_new);

                //For special seller and for us by us service purchase
				if($Order->order_total_amount == 0 || $Order->is_special_order == 1){
					$product_service_charge = 0;
				}

                //get affiliate user amount
                $affiliate_amount = 0;
                if($Order->is_affiliate == 1){
                    $specialAffiliatedUser = Specialaffiliatedusers::select('id')
                    ->where('uid', $Order->affiliate_id)->count();

                    if ($specialAffiliatedUser > 0) {
                        $product_service_charge = 0;
                    }

                    $affiliateEarning = AffiliateEarning::where('order_id',$Order->id)->first();
                    if(!empty($affiliateEarning)){
                        $affiliate_amount = $affiliateEarning->amount;
                    }
                }

				$used_wallet_fund = $Order->used_wallet_fund;
                
                // Buyer Transactions 
				$buyerTransaction = new BuyerTransaction;
				$buyerTransaction->order_id = $Order->id;
				$buyerTransaction->buyer_id = $Order->uid;

				if($Order->payment_by == 'skrill'){
					$skrill_amount = $Order->order_total_amount - $Order->used_wallet_fund - $Order->used_promotional_fund;
					$payment_processing_fee = calculate_payment_processing_fee($skrill_amount);
					$buyerTransaction->note = 'Debit from Skrill';
                	$buyerTransaction->skrill_amount = $skrill_amount;
				}elseif($Order->payment_by == 'bluesnap'){
					$payment_processing_fee = calculate_payment_processing_fee($Order->order_total_amount);
					$buyerTransaction->note = 'Debit from Credit Card';
                	$buyerTransaction->creditcard_amount = $Order->order_total_amount;
				}

                $buyerTransaction->payment_processing_fee = $payment_processing_fee;
				$buyerTransaction->anount = $Order->order_total_amount;
				$buyerTransaction->status = 'deposit';
				$buyerTransaction->created_at = time();
				$buyerTransaction->save();

                /* Seller Earnings Start */
				$SellerEarning = new SellerEarning;
				$SellerEarning->order_id = $Order->id;
				$SellerEarning->seller_id = $Order->seller_uid;
				$SellerEarning->note = 'Pending Clearance';
                $SellerEarning->anount  = $Order->order_total_amount - $product_service_charge - $affiliate_amount;
				$SellerEarning->status = 'pending_clearance';
				$SellerEarning->created_at = time();
				$SellerEarning->save();
				/* Seller Earnings End */

				if($Order->is_course == 1 && $Order->is_recurring == 0){
					//Update payment date on seller earning
					Order::storeSellerEarningPaymentDate($Order);
				}
            }

			$uid = $tempOrders[0]->uid;
			$buyer = User::select('id','earning','promotional_fund','pay_from_wallet')->find($uid);
			/* Update buyer wallet (when pay by wallet and paypal)*/
			$fromWalletAmount = Order::where('txn_id',$txn_id)->sum('used_wallet_fund');
			if($fromWalletAmount > 0){
				$buyer->earning = $buyer->earning - $fromWalletAmount;
				$buyer->pay_from_wallet = $buyer->pay_from_wallet + $fromWalletAmount;
				$buyer->save();
			}
			/* Update buyer promotional fund (when pay by promotional fund and paypal)*/
			$used_promotional = Order::where('txn_id',$txn_id)->sum('used_promotional_fund');
			if($used_promotional > 0){
				$buyer->promotional_fund = $buyer->promotional_fund - $used_promotional;
				$buyer->save();

				/* create promotional transaction history */
				$promotional_transaction = new UserPromotionalFundTransaction;
				$promotional_transaction->user_id = $uid;
				$promotional_transaction->order_id = $new_order_id;
				$promotional_transaction->amount = $used_promotional;
				$promotional_transaction->type = 0; //type - service
				$promotional_transaction->transaction_type = 0; //type - deduct
				$promotional_transaction->save();
			}

            DB::commit();
            $this->send_order_complete_notification($txn_id);
        } catch (\Exception $e) {
			DB::rollback();
            \Log::info('------Issue on cc/skrill order payment------');
			\Log::info($e->getMessage());
            \Log::info($tempOrders->toArray());
		}
    }

	//After create successful order send notification to buyer and seller
	public function send_order_complete_notification($txn_id){
        //****************Send notification***************
        $Orders = Order::where('txn_id',$txn_id)
        //->where('payment_status','Completed')
        ->get();
        foreach($Orders as $Order){
            /* Send Notification to seller Start */
            $notification = new Notification;
            $notification->notify_to = $Order->seller_uid;
            $notification->notify_from = $Order->uid;
            $notification->notify_by = 'buyer';
            $notification->order_id = $Order->id;
            $notification->is_read = 0;
            $notification->type = 'new_order';
            $notification->message = 'You Have A New Order On demo! #' . $Order->order_no;
            $notification->created_at = time();
            $notification->updated_at = time();
            $notification->save();

            /*Update Total purchase By Month*/
            $sellerAnalytic = SellerAnalytic::where('service_id',$Order->service_id)
            ->where('buyer_uid',$Order->uid)
            ->where('type','purchase')
            ->whereMonth('created_at', date('m'))
            ->whereYear('created_at', date('Y'))
            ->count();
            if($sellerAnalytic == 0){
                $sellerAnalytic = new SellerAnalytic;
                $sellerAnalytic->service_id = $Order->service_id;
                $sellerAnalytic->buyer_uid = $Order->uid;
                $sellerAnalytic->type = 'purchase';
                $sellerAnalytic->save(); 
            }
             
            $orderDetail = Order::select('id', 'order_no', 'uid', 'seller_uid','service_id','txn_id','created_at','package_name','is_course')->where('order_no', $Order->order_no)->get();
            $seller = User::select('id','email','username','Name','is_unsubscribe')->find($Order->seller_uid);
            $buyer = User::select('id','email','username','Name','is_unsubscribe')->find($Order->uid);

            /* Send Email to Buyer */
            $data = [
				'receiver_secret' => $buyer->secret,
				'email_type' => 1,
                'subject' => 'Thank you for your order!',
                'template' => 'frontend.emails.v1.buyer_order',
                'email_to' => $buyer->email,
                'username' => $buyer->username,
                'txnId' => $Order->txn_id,
                'orderNumber' => $Order->order_no,
                'orderDetail' => $orderDetail,
                'seller' => $seller->username,
                'total_amount' => $Order->order_total_amount,
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));

            /* Send Email to Seller */
            $data = [
				'receiver_secret' => $seller->secret,
				'email_type' => 1,
                'subject' => 'You Have A New Order On demo!',
                'template' => 'frontend.emails.v1.seller_order',
                'email_to' => $seller->email,
                'username' => $seller->username,
                'txnId' => $Order->txn_id,
                'orderNumber' => $Order->order_no,
                'orderDetail' => $orderDetail,
                'buyer' => $buyer->username,
                'total_amount' => $Order->order_total_amount,
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));

            /*Send mail to sub users*/
            $userObj = new User;
            $userObj->send_mail_to_subusers('is_order_mail',$seller->id,$data,'username');

            /*check_first_purchase*/
            $check_first_purchase = Order::select('id')->where('uid',$buyer->id)->count();
            if ($check_first_purchase == 1 && $buyer->is_unsubscribe == 0) {
                $data = [
					'receiver_secret' => $buyer->secret,
					'email_type' => 1,
                    'subject' => 'Thanks for making your first demo purchase!',
                    'template' => 'frontend.emails.v1.check_first_purchase',
                    'email_to' => $buyer->email,
                    'firstname' => $buyer->Name,
                ];
                QueueEmails::dispatch($data, new SendEmailInQueue($data));
            }

            /*check_first_sale*/
            $check_first_sale = Order::select('id')->where('seller_uid',$seller->id)->count();
            if ($check_first_sale == 1 && $seller->is_unsubscribe == 0) {
                $data = [
					'receiver_secret' => $seller->secret,
					'email_type' => 1,
                    'subject' => 'Thanks for making your first demo sale!',
                    'template' => 'frontend.emails.v1.check_first_sale',
                    'email_to' => $seller->email,
                    'firstname' => $seller->Name,
                ];
                QueueEmails::dispatch($data, new SendEmailInQueue($data));

                /*Send mail to sub users*/
                $userObj = new User;
                $userObj->send_mail_to_subusers('is_promotion_mail',$seller->id,$data,'firstname');
                
            }

            /*begin : store order in active campaign */
            $this->activeCampaignEcomOrder($Order->id);

            // send order data to wicked report
            send_order_data_to_wicked_report($Order);
        }
    }
	
	function store_sub_user_transaction_history($order) {
		$history = new SubUserTransaction;
		$history->sub_user_id = Auth::id(); // need to set sub user id so can not use $this->uid
		$history->used_for = 'order';
		$history->used_amount = $order->order_total_amount;
		$history->order_id = $order->id;
		$history->txn_id = $order->txn_id;
		$history->save();

		/* start : store history if order is purchased by sub user */
		$sub_user_history = new SubUserChangesHistory;
		$sub_user_history->subuser_id = Auth::id(); //can not use $this->uid because we need sub user's id
		$sub_user_history->order_id = $order->id;
		$sub_user_history->action = 'purchase_order';
		$sub_user_history->save();
		/* end : store history if order is purchased by sub user */

		return true;
	}

	public function calculate_amount($order) {
        $total_price = 0;
		$extra_price = 0;
		if($order->extra){
			foreach($order->extra as $extra){
				$extra_price += $extra->price*$extra->qty;
			}
		}
		$total_price = ($order->price*$order->qty) + $extra_price - $order->reorder_discount_amount - $order->coupon_discount - $order->volume_discount - $order->combo_discount;
        
        return $total_price;
    }

	function generate_txnid() {
    	return "TXN" . get_microtime() . 'WL' . rand('11', '99');
    }

	/*Boost Service payment from Paypal*/
	public function expressCheckoutBoost(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		$user =$this->auth_user;

		/*Get Request parameter from session*/
		$sponser_request_data = (object) Session::get('sponser_request_data');

		$service_id = $sponser_request_data->service_id;
		$service_seo_url = $sponser_request_data->service_seo_url;
		$total_days = $sponser_request_data->total_days;
		$category_slot = $sponser_request_data->category_slot;
		$selected_pack = $sponser_request_data->selected_pack;

		if($request->coupon_id){
			/*Replace secret with ID*/
			$request->coupon_id = SponsorCoupon::getDecryptedId($request->coupon_id);
		}

		$request->merge([
			'service_id' => $service_id,
			'service_seo_url' => $service_seo_url,
			'total_days' => $total_days,
			'category_slot' => $category_slot,
			'selected_pack' => $selected_pack,
			'coupon_id' => $request->coupon_id,
		]);

		$from_wallet = $request->is_from_wallet;
		$from_promotional = $request->is_from_promotional;
		$selectedPlan = BoostingPlan::where('id', '=', $selected_pack)->first();

		$baseAmount = $total_days*$selectedPlan->price;
		if(($selectedPlan->id == 4 || $selectedPlan->id == 5) && $category_slot == 2){
			$baseAmount = $total_days*$selectedPlan->sub_price;
		}

		/*Check for valid coupon*/
		$coupon_data = null;
		$discount = 0;
		$checkAppiedCount = BoostedServicesOrder::where('uid',$this->uid)->where('coupon_id',$request->coupon_id)->count();
		if($checkAppiedCount == 0){
			$coupon_data = SponsorCoupon::where('id', $request->coupon_id)->first();
			if(!empty($coupon_data)){
				if($coupon_data->discount_type == 1) {
					$discount = ($baseAmount * $coupon_data->discount) / 100;
				} else {
					$discount = $coupon_data->discount;
				}
			}
		}

		//Payable amount from paypal
		$payable_amount = $baseAmount - $discount;

		/*Validate for 100% dicount*/
		if($payable_amount < 0){
			\Session::flash('errorFails', 'Incorrect amount to pay.');
			return redirect()->route('boostService',[$service_seo_url]);
		}elseif($payable_amount == 0){
			if($baseAmount != $discount){
				\Session::flash('errorFails', 'Something goes wrong.');
				return redirect()->route('boostService',[$service_seo_url]);
			}
		}

		if($from_wallet == 1){
			$fromWalletAmount = $user->earning;
		}else{
			$fromWalletAmount = 0;
		}

		if($from_promotional == 1){
			$fromPromotionalAmount = $user->promotional_fund;
		}else{
			$fromPromotionalAmount = 0;
		}

		$payable_amount = $payable_amount - $fromWalletAmount - $fromPromotionalAmount;

		$baseAmount = number_format($baseAmount, 2, '.', '');
		$request_data = $items_arr = [];

		$key = 0;
		$items_arr[$key]['name'] = $selectedPlan->name;
		$items_arr[$key]['price'] = $baseAmount;
		$items_arr[$key]['qty'] = 1;

		if ($fromWalletAmount > 0) {
			$key++;
			$items_arr[$key]['name'] = "From wallet";
			$items_arr[$key]['price'] = "-" . round_price($fromWalletAmount);
			$items_arr[$key]['qty'] = 1;
		}

		if ($fromPromotionalAmount > 0) {
			$key++;
			$items_arr[$key]['name'] = "From promotional";
			$items_arr[$key]['price'] = "-" . round_price($fromPromotionalAmount);
			$items_arr[$key]['qty'] = 1;
		}

		if($discount > 0){
			$key++;
			$items_arr[$key]['name'] = "Promo discount";
			$items_arr[$key]['price'] = "-" . round_price($discount);
			$items_arr[$key]['qty'] = 1;
		}

		$request_data['items'] = $items_arr;

		$invoice_id = $this->generate_sponsered_orderno();
		Session::put('invoice_id',$invoice_id);

		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = "Boost Service #".$invoice_id;
		$request_data['return_url'] = route('paypal_express_checkout_boost_success');
		$request_data['cancel_url'] = route('boostService',$service_seo_url);
		$request_data['total'] = $payable_amount;

		try{

			$options = [
			    'SOLUTIONTYPE' => 'Sole',
			];
			$response = $this->provider->addOptions($options)->setExpressCheckout($request_data);
			/*Create Log for payment request data*/
			$log = new PaymentLog;
			$log->user_id = $user->id;
			$log->receipt = json_encode($response);
			$log->status = "Request data";
			$log->payment_for = "boost_service";
			$log->save();

		}catch(\Exception $e){
			return redirect()->route('boostService',$service_seo_url)->with('errorFails', 'Something went wrong with PayPal');
		}

		if (!$response['paypal_link']) {
			return redirect()->route('boostService',$service_seo_url)->with('errorFails', 'Something went wrong with PayPal');
		}else{
			Session::put('paypal_custom_data',$request->all());
			return redirect($response['paypal_link']);
		}
	}

	/*Boost Service payment from Wallet / Promotional*/
	public function expressCheckoutBoostPaynow(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		$user =$this->auth_user;

		/*Get Request parameter from session*/
		$sponser_request_data = (object) Session::get('sponser_request_data');

		$service_id = $sponser_request_data->service_id;
		$service_seo_url = $sponser_request_data->service_seo_url;
		$total_days = $sponser_request_data->total_days;
		$category_slot = $sponser_request_data->category_slot;
		$selected_pack = $sponser_request_data->selected_pack;

		if($request->coupon_id){
			/*Replace secret with ID*/
			$request->coupon_id = SponsorCoupon::getDecryptedId($request->coupon_id);
		}

		$request->merge([
			'service_id' => $service_id,
			'service_seo_url' => $service_seo_url,
			'total_days' => $total_days,
			'category_slot' => $category_slot,
			'selected_pack' => $selected_pack,
			'coupon_id' => $request->coupon_id,
		]);

		$from_wallet = $request->is_from_wallet;
		$from_promotional = $request->is_from_promotional;
		$selectedPlan = BoostingPlan::where('id', '=', $selected_pack)->first();

		$totalAmountToCheck = $total_days*$selectedPlan->price;
		if(($selectedPlan->id == 4 || $selectedPlan->id == 5) && $category_slot == 2){
			$totalAmountToCheck = $total_days*$selectedPlan->sub_price;
		}

		/*Check for valid coupon*/
		$coupon_data = null;
		$discount = 0;
		$checkAppiedCount = BoostedServicesOrder::where('uid',$this->uid)->where('coupon_id',$request->coupon_id)->count();
		if($checkAppiedCount == 0){
			$coupon_data = SponsorCoupon::where('id', $request->coupon_id)->first();

			if(!empty($coupon_data)){
				if($coupon_data->discount_type == 1) {
					$discount = ($totalAmountToCheck * $coupon_data->discount) / 100;
					$totalAmountToCheck = $totalAmountToCheck - $discount;
				} else {
					$discount = $coupon_data->discount;
					$totalAmountToCheck = $totalAmountToCheck - $discount;
				}
			}
		}

		/*Validate for 100% dicount*/
		if($totalAmountToCheck < 0){
			\Session::flash('errorFails', 'Incorrect amount to pay.');
			return redirect()->route('boostService',[$service_seo_url]);
		}elseif($totalAmountToCheck == 0){
			$baseAmount = $total_days*$selectedPlan->price;
			if(($selectedPlan->id == 4 || $selectedPlan->id == 5) && $category_slot == 2){
				$baseAmount = $total_days*$selectedPlan->sub_price;
			}

			if($baseAmount != $discount){
				\Session::flash('errorFails', 'Something goes wrong.');
				return redirect()->route('boostService',[$service_seo_url]);
			}
		}

		/*begin : Make payment form wallet + promotional*/
		if($from_wallet == 1 && $from_promotional == 1){
			if( $totalAmountToCheck <= ($user->earning + $user->promotional_fund)) {
				$txn_id = $this->generate_txnid();
				if($user->promotional_fund >= $totalAmountToCheck){
					$used_promotional = $totalAmountToCheck;
				}else{
					$used_promotional = $user->promotional_fund;
				}

				$used_wallet_amount = $totalAmountToCheck - $used_promotional;

				/* create boosted order */
				$this->createBoostOrder($selectedPlan,$service_id,$total_days,$request->selected_pack,$category_slot,$txn_id,$payBy='wallet',null,false,$coupon_data,$used_promotional,$used_wallet_amount);

				return redirect()->route('payment_detail_boost',$txn_id)->with('errorSuccess','Boost service order placed successfully');
			}else{
				\Session::flash('errorFails', 'You have not sufficient amount in your wallet and promotional funds');
				return redirect()->route('boostService',[$service_seo_url]);
			}
		}
		/*end : Make payment form wallet + promotional*/

		/*begin : Make payment form promotional*/
		 else if($from_promotional == 1){
			if( $totalAmountToCheck <= $user->promotional_fund){
				$txn_id = $this->generate_txnid();
				if($user->promotional_fund >= $totalAmountToCheck){
					$used_promotional = $totalAmountToCheck;
				}else{
					$used_promotional = $user->promotional_fund;
				}

				$used_wallet_amount = 0;

				/* create boosted order */
				$this->createBoostOrder($selectedPlan,$service_id,$total_days,$request->selected_pack,$category_slot,$txn_id,$payBy='promotional',null,false,$coupon_data,$used_promotional,$used_wallet_amount);

				return redirect()->route('payment_detail_boost',$txn_id)->with('errorSuccess','Boost service order placed successfully');
			}else{
				\Session::flash('errorFails', 'You have not sufficient amount in your promotional funds');
				return redirect()->route('boostService',[$service_seo_url]);
			}
		}
		/*end : Make payment form promotional*/
		
		/*begin : Make payment form wallet*/
		else if($from_wallet == 1){
			if( $totalAmountToCheck <= $user->earning){
				$txn_id = $this->generate_txnid();

				$used_promotional = 0;
				$used_wallet_amount = $totalAmountToCheck;

				/* create boosted order */
				$this->createBoostOrder($selectedPlan,$service_id,$total_days,$request->selected_pack,$category_slot,$txn_id,$payBy='wallet',null,false,$coupon_data,$used_promotional,$used_wallet_amount);

				return redirect()->route('payment_detail_boost',$txn_id)->with('errorSuccess','Boost service order placed successfully');
			}else{
				\Session::flash('errorFails', 'You have not sufficient amount in your wallet');
				return redirect()->route('boostService',[$service_seo_url]);
			}
		}
		/*end : Make payment form wallet*/
	}
	
	public function expressCheckoutBoostSuccess(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}
		
		$user =$this->auth_user;
		$token = $request->get('token');
		$PayerID = $request->get('PayerID');
		$invoice_id = Session::get('invoice_id');

		$paypal_custom_data = Session::get('paypal_custom_data');
		$service_id = $paypal_custom_data['service_id'];
		$total_days = $paypal_custom_data['total_days'];
		$selected_pack = $paypal_custom_data['selected_pack'];
		$category_slot = $paypal_custom_data['category_slot'];
		$service_seo_url = $paypal_custom_data['service_seo_url'];
		$from_wallet = $paypal_custom_data['is_from_wallet'];
		$from_promotional = $paypal_custom_data['is_from_promotional'];

		$response = $this->provider->getExpressCheckoutDetails($token);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($response);
		$log->status = "Payment response";
		$log->payment_for = "boost_service";
		$log->save();

		if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
			return redirect()->route('boostService',$service_seo_url)->with('errorFails', 'Error processing PayPal payment');
		}

		$selectedPlan = BoostingPlan::where('id', '=', $selected_pack)->first();

		$baseAmount = number_format($total_days*$selectedPlan->price, 2, '.', '');

		if(( $selectedPlan->id == 4 || $selectedPlan->id == 5 ) && $category_slot == 2){
			$baseAmount = number_format($total_days*$selectedPlan->sub_price, 2, '.', '');
		}

		/*Check for coupon discount*/
		$coupon_data = SponsorCoupon::where('id', $paypal_custom_data['coupon_id'])->first();

		$discount = 0;
		if($coupon_data != null) {
			if($coupon_data->discount_type == 1) {
				$discount = ($totalAmount * $coupon_data->discount) / 100;
			} else {
				$discount = $coupon_data->discount;
			}
		}
		
		$payable_amount = $baseAmount - $discount;

		/*Validate for 100% dicount*/
		if($payable_amount < 0){
			\Session::flash('errorFails', 'Incorrect amount to pay.');
			return redirect()->route('boostService',[$service_seo_url]);
		}elseif($payable_amount == 0){
			if($baseAmount != $discount){
				\Session::flash('errorFails', 'Something goes wrong.');
				return redirect()->route('boostService',[$service_seo_url]);
			}
		}

		if($from_wallet == 1){
			$fromWalletAmount = $user->earning;
		}else{
			$fromWalletAmount = 0;
		}

		if($from_promotional == 1){
			$fromPromotionalAmount = $user->promotional_fund;
		}else{
			$fromPromotionalAmount = 0;
		}

		/*begin : check wallet amount*/
		if($fromWalletAmount > 0 && $fromPromotionalAmount > 0){
			if($fromWalletAmount > $user->earning || $fromPromotionalAmount > $user->promotional_fund){
				return redirect()->route('boostService',[$service_seo_url])->with('errorFails', 'You have not sufficient amount in your wallet and promotional fund.');
			}
		}else if($fromWalletAmount > 0){
			if($fromWalletAmount > $user->earning){
				return redirect()->route('boostService',[$service_seo_url])->with('errorFails', 'You have not sufficient amount in your wallet.');
			}
		}
		else if($fromPromotionalAmount > 0){
			if($fromPromotionalAmount > $user->promotional_fund){
				return redirect()->route('boostService',[$service_seo_url])->with('errorFails', 'You have not sufficient amount in your promotional fund.');
			}
		}
		/*end : check wallet amount*/

		//Payable amount from paypal
		$payable_amount = $payable_amount - $fromWalletAmount - $fromPromotionalAmount;

		if($response['AMT'] != round($payable_amount,2)){
            $this->send_failed_notification($user->id);
            return redirect()->route('boostService',[$service_seo_url])->with('errorFails', 'Requested amount and payable amount is not match.');
        }

		$baseAmount = number_format($baseAmount, 2, '.', '');
		$request_data = $items_arr = [];

		$key = 0;
		$items_arr[$key]['name'] = $selectedPlan->name;
		$items_arr[$key]['price'] = $baseAmount;
		$items_arr[$key]['qty'] = 1;

		if ($fromWalletAmount > 0) {
			$key++;
			$items_arr[$key]['name'] = "From wallet";
			$items_arr[$key]['price'] = "-" . round_price($fromWalletAmount);
			$items_arr[$key]['qty'] = 1;
		}

		if ($fromPromotionalAmount > 0) {
			$key++;
			$items_arr[$key]['name'] = "From promotional";
			$items_arr[$key]['price'] = "-" . round_price($fromPromotionalAmount);
			$items_arr[$key]['qty'] = 1;
		}

		if($discount > 0){
			$key++;
			$items_arr[$key]['name'] = "Promo discount";
			$items_arr[$key]['price'] = "-" . round_price($discount);
			$items_arr[$key]['qty'] = 1;
		}

		$request_data['items'] = $items_arr;
		
		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = "Boost Service #".$invoice_id;
		$request_data['return_url'] = route('paypal_express_checkout_boost_success');
		$request_data['cancel_url'] = route('boostService',$service_seo_url);
		$request_data['total'] = $payable_amount;

		$payment_status = $this->provider->doExpressCheckoutPayment($request_data, $token, $PayerID);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($payment_status);
		$log->status = "Payment response verification";
		$log->payment_for = "boost_service";
		$log->save();

		if ($payment_status['ACK'] == 'Failure') {
			$this->send_failed_notification($user->id);
			return redirect()->route('boostService',$service_seo_url)->with('errorFails', 'Something went wrong with PayPal');
		}

		$status = $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'];
		$txn_id = $payment_status['PAYMENTINFO_0_TRANSACTIONID'];

		Session::forget('invoice_id');
		Session::forget('paypal_custom_data');

		if ($status == 'Completed') {
			$used_promotional = $fromPromotionalAmount;
			$used_wallet_amount = $fromWalletAmount;
			$this->createBoostOrder($selectedPlan,$service_id,$total_days,$selected_pack,$category_slot,$txn_id,$payBy='paypal',null,false,$coupon_data,$used_promotional,$used_wallet_amount);

			return redirect()->route('payment_detail_boost',$txn_id)->with('errorSuccess','Boost service order placed successfully');
		}else{
			return redirect()->route('boostService',$service_seo_url)->with('error_msg', 'Error processing PayPal payment');
		}
	}
	public function generate_sponsered_orderno() {
		$order = BoostedServicesOrder::orderBy('id', 'desc')->first();
		if (count($order) > 0) {
			$orderId = $order->id + 1;
		} else {
			$orderId = 1;
		}
		return "LE" . time() . $orderId;
	}

	public function generate_sponsered_ref() {
		$order = BoostedServicesOrder::orderBy('id', 'desc')->first();
		if (count($order) > 0) {
			$orderId = $order->id + 1;
		} else {
			$orderId = 1;
		}
		return "REF" . time() . $orderId;
	}

	public function createBoostOrder($selectedPlan,$service_id,$total_days,$selected_pack,$category_slot = null,$txn_id,$payment_by = 'paypal',$bluesnapTempData = null,$from_ipn = false,$coupon_data,$used_promotional=0,$used_wallet_amount=0){

		if($from_ipn == true){
			$uid = $bluesnapTempData->user_id;
			$user = User::find($uid);
		}else{
			$user =$this->auth_user;
		}

		$totalAmount = $total_days*$selectedPlan->price;
		if(($selectedPlan->id == 4 || $selectedPlan->id == 5) && $category_slot == 2){
			$totalAmount = $total_days*$selectedPlan->sub_price;
		}

		$total_payable_amount = $totalAmount;
		$discount = 0;
		if($coupon_data != null) {
			if($coupon_data->discount_type == 1) {
				$discount = ($totalAmount * $coupon_data->discount) / 100;
				$totalAmount = $totalAmount - $discount;
			} else {
				$discount = $coupon_data->discount;
				$totalAmount = $totalAmount - $discount;
			}
		}

		$OrderExists = BoostedServicesOrder::where('txn_id', $txn_id)->first();
		if (count($OrderExists) > 0) {
			return 1;
		}

		$dates_array = [];
		if ($selected_pack == 4 || $selected_pack == 5) {
			$dates_array = BoostedServicesOrder::get_category_sponser_dates($service_id,$category_slot,$total_days,$selected_pack);
  			$yourStartDate = $yourEndDate =  null;
		}elseif($selected_pack == 7){

  			$getServiceCategory = Service::where('id', '=', $service_id)->first();

  			if (!empty($getServiceCategory)) {
  				$subCatId = $getServiceCategory->subcategory_id;

  				$getServicesOfSameCategory = Service::select('id')->where('subcategory_id', '=', $subCatId)->get()->toArray();

  				$getServicesOfSameCategory = Arr::flatten($getServicesOfSameCategory);

  				if (count($getServicesOfSameCategory)) {
  					$startdate = BoostedServicesOrder::get_cart_sponsor_startdate($service_id);
  					$yourStartDate = date('Y-m-d 00:00:00', strtotime($startdate));
  					$yourEndDate = date('Y-m-d 23:59:59', strtotime($yourStartDate . "+" . ($total_days - 1) . " days"));
  				} else {
  					$yourStartDate = date('Y-m-d 00:00:00', strtotime("+1" . " days"));
  					$yourEndDate = date('Y-m-d 23:59:59', strtotime($yourStartDate . "+" . ($total_days - 1) . " days"));
  				}
  			}
  		} else {
			$getServiceCategory = Service::where('id', '=', $service_id)->first();
			if (!empty($getServiceCategory)) {
				$subCatId = $getServiceCategory->subcategory_id;
				$getServicesOfSameCategory = Service::select('id')->where('subcategory_id', '=', $subCatId)->get()->toArray();

				$getServicesOfSameCategory = Arr::flatten($getServicesOfSameCategory);

				if (count($getServicesOfSameCategory)) {
					$serviceTurn = BoostedServicesOrder::where('plan_id', '=', $selected_pack)->where('status','!=','cancel')->orderby('id', 'desc')->first();
					if (!empty($serviceTurn)) {
						/*Check if enddate is less then or equal to current date*/
  						$end_date = date('Y-m-d',strtotime($serviceTurn->end_date));
  						if(strtotime($end_date) > strtotime(date('Y-m-d'))){
  							$yourStartDate = date('Y-m-d 00:00:00', strtotime($serviceTurn->end_date . "+" . 1 . " days"));
							$yourEndDate = date('Y-m-d 23:59:59', strtotime($yourStartDate . "+" . ($total_days - 1) . " days"));
  						}else{
  							$yourStartDate = date('Y-m-d 00:00:00', strtotime("+1" . " days"));
							$yourEndDate = date('Y-m-d 23:59:59', strtotime($yourStartDate . "+" . ($total_days - 1) . " days"));
  						}
					} else {
						$yourStartDate = date('Y-m-d 00:00:00', strtotime("+1" . " days"));
						$yourEndDate = date('Y-m-d 23:59:59', strtotime($yourStartDate . "+" . ($total_days - 1) . " days"));
					}
				} else {
					$yourStartDate = date('Y-m-d 00:00:00', strtotime("+1" . " days"));
					$yourEndDate = date('Y-m-d 23:59:59', strtotime($yourStartDate . "+" . ($total_days - 1) . " days"));
				}
			}   
		}

		if (!in_array($selected_pack, [4,5])) {
			$now = date('Y-m-d 00:00:00');
			$dateDifference = date_diff(date_create($now), date_create($yourStartDate));

			if ($dateDifference->days > 0 && $dateDifference->invert == 1) {
				$yourStartDate = date('Y-m-d 00:00:00', strtotime("+1" . " days"));
				$yourEndDate = date('Y-m-d 23:59:59', strtotime($yourStartDate . "+" . ($total_days - 1) . " days"));
			}
		}

		$createOrder = new BoostedServicesOrder;
		$createOrder->uid = $user->id;
		$createOrder->amount = $totalAmount;
		$createOrder->order_no = $this->generate_sponsered_orderno();
		$createOrder->service_id = $service_id;
		$createOrder->plan_id = $selectedPlan->id;
		$createOrder->slot = $category_slot;
		$createOrder->txn_id = $txn_id;
		$createOrder->receipt = null;
		if (!in_array($selected_pack, [4,5])) {
			$createOrder->start_date = $yourStartDate;
			$createOrder->end_date = $yourEndDate;
		}
		$createOrder->total_days = $total_days;
		if($coupon_data != null) {
			$createOrder->coupon_id = $coupon_data->id;
			$createOrder->coupon_code = $coupon_data->coupon_code;
			$createOrder->coupon_amount = $coupon_data->discount;
			$createOrder->coupon_amount_type = $coupon_data->discount_type;
		}

		if ($payment_by == 'wallet') {
			$createOrder->payment_status = "Completed";
			$createOrder->payment_by = 'wallet';
		}elseif($payment_by == 'paypal'){
			$createOrder->payment_status = "Completed";
			$createOrder->payment_by = 'paypal';
		}elseif($payment_by == 'bluesnap'){
			$createOrder->payment_status = 'Completed';
			$createOrder->payment_by = 'bluesnap';
		}elseif($payment_by == 'promotional'){
			$createOrder->payment_status = 'Completed';
			$createOrder->payment_by = 'promotional';
		}elseif($payment_by == 'skrill'){
			$createOrder->payment_status = 'Completed';
			$createOrder->payment_by = 'skrill';
		}
		$createOrder->used_promotional_fund = $used_promotional;
		$createOrder->save();

		/*Create order slot*/
		if (in_array($selected_pack, [4,5])) {

			BoostedServicesOrdersDate::where('is_temp',1)->where('user_id',$user->id)->delete();
			$service = Service::where('id', '=', $service_id)->first();

			foreach ($dates_array as $date) {
				$tempModel = new BoostedServicesOrdersDate;
				$tempModel->user_id = $service->uid;
				$tempModel->boosted_order_id = $createOrder->id;
				$tempModel->plan_id = $selected_pack;
				$tempModel->category_id = $service->category_id;
				if($selected_pack == 5){
					$tempModel->subcategory_id = $service->subcategory_id;
				}
				$tempModel->slot = $category_slot;
				$tempModel->date = $date;
				$tempModel->save();
			}

			/*Store start data and end date of category for search easy*/
			$tempOrder = BoostedServicesOrder::find($createOrder->id);
			$tempOrder->start_date = $tempOrder->get_category_assign_startdate->date.' 00:00:00';
			$tempOrder->end_date = $tempOrder->get_category_assign_enddate->date.' 23:59:59';
			$tempOrder->save();

		}

		$buyerTransaction = new BuyerTransaction;
		$buyerTransaction->order_id = $createOrder->id;
		$buyerTransaction->buyer_id = $createOrder->uid;
		$buyerTransaction->note = 'Payment for Service Sponser';
		$buyerTransaction->anount = $totalAmount;
		
		if ($createOrder->payment_status == "Completed") {
			$buyerTransaction->status = 'payment';
		}
		else
		{
			$buyerTransaction->status = 'pending_payment';
		}
		$buyerTransaction->is_sponsered = 1;
		$buyerTransaction->save();

		$product_price = $createOrder->amount;

		$buyer = User::find($user->id);

		if ($payment_by == 'wallet') {
			if($used_promotional > 0) {
				$buyer->promotional_fund = $buyer->promotional_fund - $used_promotional;
				$buyer->earning = $buyer->earning - ($product_price - $used_promotional);
			} else {
				$buyer->earning = $buyer->earning - $product_price;
			}
			$buyer->save();
		}

		if ($payment_by == 'promotional') {
			$buyer->promotional_fund = $buyer->promotional_fund - $product_price;
			$buyer->save();
		}

		if ($payment_by == 'paypal' || $payment_by == 'skrill') {
			if($used_promotional > 0) {
				$buyer->promotional_fund = $buyer->promotional_fund - $used_promotional;
				$buyer->save();
			}
			if($used_wallet_amount > 0) {
				$buyer->earning = $buyer->earning - $used_wallet_amount;
				$buyer->save();
			}
		}

		if($used_promotional > 0) {
			/* create promotional transaction history */
			$promotional_transaction = new UserPromotionalFundTransaction;
			$promotional_transaction->user_id = $user->id;
			$promotional_transaction->boost_order_id = $createOrder->id;
			$promotional_transaction->amount = $used_promotional;
			$promotional_transaction->type = 1; //type - sponsor
			$promotional_transaction->transaction_type = 0; //type - deduct
			$promotional_transaction->save();
		}

		$serviceChargeJim = ($product_price * env('JIM_CHARGE_PER')) / 100;
		if ($createOrder->payment_status == "Completed") 
		{
			/* Send 10 out of 10% in Jim Acc. */
			$UserJim = User::select('id','earning','net_income')->where('id', '38')->first();
			if (!empty($UserJim)) 
			{
				$UserJim->earning = $UserJim->earning + $serviceChargeJim;
				$UserJim->net_income = $UserJim->net_income + $serviceChargeJim;
				$UserJim->save();
				
				/* Jim Commission Transactions Start */
				$buyerTransaction = new BuyerTransaction;
				$buyerTransaction->order_id = $createOrder->id;
				$buyerTransaction->buyer_id = $UserJim->id;
				$buyerTransaction->note = 'Commission for the #' . $createOrder->order_no . ' for sponser service';
				$buyerTransaction->anount = $serviceChargeJim;
				$buyerTransaction->status = 'commission ';
				$buyerTransaction->created_at = time();
				$buyerTransaction->is_sponsered = 1;
				$buyerTransaction->save();
				/* Jim Commission Transactions End */
			}
		}

		/* Send Email to Buyer */
		$orderDetail = BoostedServicesOrder::where('order_no', $createOrder->order_no)->first();
		if (count($orderDetail) && count($buyer)) {

			/* Send Email to Seller */
			$data = [
				'receiver_secret' => $buyer->secret,
				'email_type' => 2,
				'subject' => 'Service Sponsored',
				'template' => 'frontend.emails.v1.sponser_service',
				'email_to' => $buyer->email,
				'username' => $buyer->username,
				'txnId' => $createOrder->txn_id,
				'orderNumber' => $createOrder->order_no,
				'orderDetail' => $orderDetail,
			];
			QueueEmails::dispatch($data, new SendEmailInQueue($data));
		}

		/* Send wallet transaction email to admin */
		if($payment_by == "wallet" || $payment_by == "promotional"){
			$wallet_transaction_history['buyer'] = $buyer->username;
			$wallet_transaction_history['buyer_email'] = $buyer->email;
			$wallet_transaction_history['transaction_id'] = $txn_id;
			$wallet_transaction_history['total_amount'] = round($total_payable_amount,2);
			$wallet_transaction_history['reason'] = "service sponsored";
			$wallet_transaction_history['transactions'][] = [
				'title' => "Service sponsored",
				'price' => $total_payable_amount,
				'quantity' 	=> 1,
				'total' => round($total_payable_amount,2)
			];
			if($discount > 0){
				$wallet_transaction_history['discount'] = $discount;
			}
			Order::sendWalletTransactionEmail($wallet_transaction_history);
		}

		return 1;
	}

	
	public function expressCheckoutDeposit(Request $request){
		$user =$this->auth_user;

		$totalAmount = number_format(-$user->earning, 2, '.', '');

		$request_data = [];
		$request_data['items'] = [
			[
				'name' => "Deposit Wallet",
				'price' => $totalAmount,
				'qty' => 1
			]
		];

		$invoice_id = "LE".time();
		Session::put('invoice_id',$invoice_id);

		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = "Deposit Wallet #".$invoice_id;
		$request_data['return_url'] = route('paypal_express_checkout_deposit_success');
		$request_data['cancel_url'] = route('update_wallet');
		$request_data['total'] = $totalAmount;

		try{
			$options = [
			    'SOLUTIONTYPE' => 'Sole',
			];
			$response = $this->provider->addOptions($options)->setExpressCheckout($request_data);
			/*Create Log for payment request data*/
			$log = new PaymentLog;
			$log->user_id = $user->id;
			$log->receipt = json_encode($response);
			$log->status = "Request data";
			$log->payment_for = "deposit_wallet";
			$log->save();

		}catch(\Exception $e){
			return redirect()->route('update_wallet')->with('errorFails', 'Something went wrong with PayPal');
		}

		if (!$response['paypal_link']) {
			return redirect()->route('update_wallet')->with('errorFails', 'Something went wrong with PayPal');
		}else{
			return redirect($response['paypal_link']);
		}
	}
	public function expressCheckoutDepositSuccess(Request $request){
		$user =$this->auth_user;
		$token = $request->get('token');
		$PayerID = $request->get('PayerID');
		$invoice_id = Session::get('invoice_id');
		
		$response = $this->provider->getExpressCheckoutDetails($token);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($response);
		$log->status = "Payment response";
		$log->payment_for = "deposit_wallet";
		$log->save();

		if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
			return redirect()->route('update_wallet')->with('errorFails', 'Error processing PayPal payment');
		}

		$totalAmount = number_format(-$user->earning, 2, '.', '');

		$request_data = [];
		$request_data['items'] = [
			[
				'name' => "Deposit Wallet",
				'price' => $totalAmount,
				'qty' => 1
			]
		];

		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = "Deposit Wallet #".$invoice_id;
		$request_data['return_url'] = route('paypal_express_checkout_deposit_success');
		$request_data['cancel_url'] = route('update_wallet');
		$request_data['total'] = $totalAmount;

		$payment_status = $this->provider->doExpressCheckoutPayment($request_data, $token, $PayerID);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($payment_status);
		$log->status = "Payment response verification";
		$log->payment_for = "deposit_wallet";
		$log->save();

		if ($payment_status['ACK'] == 'Failure') {
			$this->send_failed_notification($user->id);
			return redirect()->route('update_wallet')->with('errorFails', 'Something went wrong with PayPal');
		}

		$status = $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'];
		$txn_id = $payment_status['PAYMENTINFO_0_TRANSACTIONID'];

		Session::forget('invoice_id');

		if ($status == 'Completed') {
			$traExists = BuyerTransaction::where('transaction_id',$txn_id)->first();
			if(empty($traExists)){
				$user = User::find($user->id);
				$user->earning = $user->earning + $totalAmount;
				$user->save();

				$buyerTransaction = new BuyerTransaction;
				$buyerTransaction->buyer_id = $user->id;
				$buyerTransaction->note = "Deposit wallet amount";
				$buyerTransaction->anount = $totalAmount;
				$buyerTransaction->status = 'deposite_amount';
				$buyerTransaction->transaction_id = $txn_id;
				$buyerTransaction->created_at = time();
				$buyerTransaction->save();
			}
			return redirect()->route('transactions')->with('errorSuccess','Wallet deposit successfully');
		}else{
			return redirect()->route('update_wallet')->with('error_msg', 'Error processing PayPal payment');
		}
	}

	/*Cancel premium subscription using cron if not payment receive*/
	public function cancelPremiumSubscriptionCron($profile_id){
		$response = $this->provider->cancelRecurringPaymentsProfile($profile_id);
		if ($response['ACK'] == 'Failure') {

			$premiumSubscription = SubscribeUser::where('profile_id',$profile_id)->first();
			if(count($premiumSubscription) > 0){
				$log = new PaymentLog;
				$log->user_id = $premiumSubscription->user_id;
				$log->receipt = json_encode($response);
				$log->status = "Profile details cancel - cancelPremiumSubscriptionCron";
				$log->payment_for = "premium_seller";
				$log->save();
			}
			
			return false;
		}else{
			$subscribeUser = SubscribeUser::where('profile_id',$profile_id)->first();

			if(count($subscribeUser) > 0){
				$subscribeUser->is_cancel = 1;
				$subscribeUser->save();

				$buyerTransaction = new BuyerTransaction;
				$buyerTransaction->buyer_id = $subscribeUser->user_id;
				$buyerTransaction->note = 'Cancel Premium subscription due to recurring payment fails';
				$buyerTransaction->anount = 0;
				$buyerTransaction->status = 'premium_subscription_cancel';
				$buyerTransaction->created_at = time();
				$buyerTransaction->save();

				/* create transaction history for cancel */
				$is_renew_val = SubscribeTransactionHistory::where('user_id',$subscribeUser->user_id)
					->orderBy('id','desc')
					->select('id','is_renew')->first();
				if(isset($is_renew_val) && $is_renew_val->is_renew == 1) {
					$is_renew = 1;
				} else {
					$is_renew = 0;
				}
				$subscribe_transactions = new SubscribeTransactionHistory;
				$subscribe_transactions->user_id = $subscribeUser->user_id;
				$subscribe_transactions->subscription_id = $subscribeUser->subscription_id;
				$subscribe_transactions->transaction_id = $subscribeUser->transaction_id;
				$subscribe_transactions->profile_id = $subscribeUser->profile_id;
				$subscribe_transactions->start_date = date('Y-m-d H:i:s');
				$subscribe_transactions->end_date = $subscribeUser->end_date;
				$subscribe_transactions->receipt = $subscribeUser->receipt;
				$subscribe_transactions->is_renew = $is_renew;
				$subscribe_transactions->note = 'Cancel premium subscription due to payment not received';
				$subscribe_transactions->save();
			}
			return true;
		}
	}

	/*Cancel premium subscription using web*/
	public function cancelPremiumSubscription(){
		$user =$this->auth_user;
		$subscription = Subscription::find(1);
		$subscribeUser = SubscribeUser::where('user_id',$user->id)->where('is_cancel',0)->first();
		if(!empty($subscribeUser)){
			// cancel for paypal or wallet
			$subscribeUser->is_cancel = 1;
			$subscribeUser->save();

			$buyerTransaction = new BuyerTransaction;
			$buyerTransaction->buyer_id = $user->id;
			$buyerTransaction->note = 'Cancel Premium subscription';
			$buyerTransaction->anount = 0;
			$buyerTransaction->status = 'premium_subscription_cancel';
			$buyerTransaction->created_at = time();
			$buyerTransaction->save();

			/* create transaction history for cancel */
			$is_renew_val = SubscribeTransactionHistory::where('user_id',$subscribeUser->user_id)
				->orderBy('id','desc')
				->select('id','is_renew')->first();
			if(isset($is_renew_val) && $is_renew_val->is_renew == 1) {
				$is_renew = 1;
			} else {
				$is_renew = 0;
			}
			$subscribe_transactions = new SubscribeTransactionHistory;
			$subscribe_transactions->user_id = $subscribeUser->user_id;
			$subscribe_transactions->subscription_id = $subscription->id;
			$subscribe_transactions->transaction_id = $subscribeUser->transaction_id;
			$subscribe_transactions->profile_id = $subscribeUser->profile_id;
			$subscribe_transactions->start_date = date('Y-m-d H:i:s');
			$subscribe_transactions->end_date = $subscribeUser->end_date;
			$subscribe_transactions->receipt = $subscribeUser->receipt;
			$subscribe_transactions->is_renew = $is_renew;
			$subscribe_transactions->note = 'Cancel premium subscription by user';
			$subscribe_transactions->save();

			if($subscribeUser->payment_by == 0) { //paypal cancel
				$profile_id = $subscribeUser->profile_id;
				$response = $this->provider->cancelRecurringPaymentsProfile($profile_id);
				if ($response['ACK'] == 'Failure') {
					return redirect()->route('my_premium_subscription')->with('errorFails', $response['L_SHORTMESSAGE0']);
				}
			}

			return redirect()->route('my_premium_subscription')->with('errorSuccess', 'Premium subscription cancel successfully');
		}else{
			return redirect()->route('my_premium_subscription')->with('errorFails', 'Something went wrong.');
		}
	}

	/*Renew premium subscription*/
	public function renewPremiumSubscription($subscribeUser){
		$subscription = Subscription::find(1);
		date_default_timezone_set('America/Los_Angeles');

		$profile_id = $subscribeUser->profile_id;
		$response_profile_detail = $this->provider->getRecurringPaymentsProfileDetails($profile_id);
		
		$log = new PaymentLog;
		$log->user_id = $subscribeUser->user_id;
		$log->receipt = json_encode($response_profile_detail);
		$log->status = "Profile details renew - getRecurringPaymentsProfileDetails";
		$log->payment_for = "premium_seller";
		$log->save();

		if (isset($response_profile_detail['STATUS']) && $response_profile_detail['ACK'] == 'Success' && $response_profile_detail['STATUS'] == "Active") {
			$receipt = json_decode($subscribeUser->receipt);
			if(isset($receipt->NEXTBILLINGDATE) && ($receipt->NEXTBILLINGDATE != $response_profile_detail['NEXTBILLINGDATE'])){

				$subscribeUser->end_date = date("Y-m-d H:i:s", strtotime($response_profile_detail['NEXTBILLINGDATE']));
				/*$subscribeUser->end_date = date("Y-m-d H:i:s", strtotime(" +1 months"));*/
				$subscribeUser->receipt = json_encode($response_profile_detail);
				$subscribeUser->is_cancel = 0;
				$subscribeUser->is_renew = 1;
				$subscribeUser->is_sync = 0;

				$subscribeUser->save();

				$totalAmount = $response_profile_detail['AMT'];

				$buyerTransaction = new BuyerTransaction;
				$buyerTransaction->buyer_id = $subscribeUser->user_id;
				$buyerTransaction->note = 'Premium Subscription Renew from Credit Card/Paypal';
				$buyerTransaction->anount = $totalAmount;
				$buyerTransaction->status = 'premium_subscription';
				$buyerTransaction->transaction_id = $subscribeUser->transaction_id;
				$buyerTransaction->paypal_amount = $totalAmount;
				$buyerTransaction->created_at = time();
				$buyerTransaction->save();

				/* Send 0.10% in Jim Acc. */
				$serviceChargeJim = ($totalAmount * env('JIM_CHARGE_PER')) / 100;
				$txn_id = $subscribeUser->transaction_id;
				$UserJim = User::where('id', '38')->first();
				if (!empty($UserJim)) {
					$UserJim->earning = $UserJim->earning + $serviceChargeJim;
					$UserJim->net_income = $UserJim->net_income + $serviceChargeJim;
					$UserJim->save();

					/* Jim Commission Transactions Start */
					$buyerTransaction = new BuyerTransaction;
					$buyerTransaction->buyer_id = $UserJim->id;
					$buyerTransaction->note = 'Commission for the renew premium subscription #' . $txn_id;
					$buyerTransaction->anount = $serviceChargeJim;
					$buyerTransaction->status = 'premium_subscription';
					$buyerTransaction->created_at = time();
					$buyerTransaction->save();
					/* Jim Commission Transactions End */
				}

				$subscribe_transactions = new SubscribeTransactionHistory;
				$subscribe_transactions->user_id = $subscribeUser->user_id;
				$subscribe_transactions->subscription_id = $subscription->id;
				$subscribe_transactions->transaction_id = $txn_id;
				$subscribe_transactions->profile_id = $profile_id;
				$subscribe_transactions->start_date = date('Y-m-d H:i:s');
				$subscribe_transactions->end_date = $subscribeUser->end_date;
				$subscribe_transactions->receipt = $subscribeUser->receipt;
				$subscribe_transactions->is_renew = 1;
				$subscribe_transactions->note = 'Renew premium subscription by Paypal';
				$subscribe_transactions->save();

				// send mail to inform subscription renewed
				$user = User::where('id',$subscribeUser->user_id)->first();
				$data = [
					'receiver_secret' => $user->secret,
					'email_type' => 5,
					'subject' => 'Your premium subscription has renewed!',
					'template' => 'frontend.emails.v1.premium_subscription_renew',
					'email_to' => $user->email,
					'name' => $user->Name
				];
				QueueEmails::dispatch($data, new SendEmailInQueue($data));
			}
		}elseif(isset($response_profile_detail['STATUS']) && $response_profile_detail['STATUS'] == "Cancelled"){
			$subscribeUser->receipt = json_encode($response_profile_detail);
			$subscribeUser->is_cancel = 1;
			$subscribeUser->save();

			$this->cancelPremiumSubscriptionCron($profile_id);
		}else{
			if (isset($response_profile_detail['type']) && $response_profile_detail['type'] == 'error'){
				$mail_to = "";
			}else{
				$mail_to = env('NEW_HELP_EMAIL');
			}

			$user = User::select('email','username')->where('id',$subscribeUser->user_id)->first();
			$data = [
				'subject' => 'Problem with premium subscription renew profile',
				'template' => 'frontend.emails.v1.premium_subscription_renew_issue',
				'email_to' => $mail_to,
				'username' => $user->username,
				'profile_id' => $profile_id,
			];
			QueueEmails::dispatch($data, new SendEmailInQueue($data));
		}
		return 1;
	}

	/*Paypal Premium seller*/
	

	public function expressCheckoutPremium(Request $request){

		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('my_premium_subscription')->with('errorFails', get_user_softban_message());
		}

		$user =$this->auth_user;
		$subscription = Subscription::find(1);
		$totalAmount = number_format($subscription->price, 2, '.', '');
		$from_wallet = $request->from_wallet;
		
		if($from_wallet == '1') {
			if($subscription->price > $user->earning) {
				return redirect()->route('my_premium_subscription')->with('errorFails', 'You have not sufficient amount in your wallet.');
			}

			// all payment from wallet
			$this->generate_premium_wallet_payment($user->id, $totalAmount, 'subscription');

			$user->earning = $user->earning - $subscription->price;
			$user->pay_from_wallet = $user->pay_from_wallet + $subscription->price;
			$user->save();

			return redirect()->route('transactions')->with('errorSuccess','Premium seller subscribe successfully');
		} else {

			$request_data = [];
			$request_data['items'] = [
				[
					'name' => $subscription->package_name,
					'price' => $totalAmount,
					'qty' => 1
				]
			];

			$invoice_id = "LE".time();
			$request_data['invoice_id'] = $invoice_id;
			$request_data['invoice_description'] = $subscription->package_name." #".$invoice_id;
			$request_data['return_url'] = route('paypal_express_checkout_premium_success');
			$request_data['cancel_url'] = route('my_premium_subscription');
			$request_data['total'] = $totalAmount;

			try{
				$response = $this->provider->setExpressCheckout($request_data,true);
				/*Create Log for payment request data*/
				$log = new PaymentLog;
				$log->user_id = $user->id;
				$log->receipt = json_encode($response);
				$log->status = "Request data";
				$log->payment_for = "premium_seller";
				$log->save();

			}catch(\Exception $e){
				return redirect()->route('my_premium_subscription')->with('errorFails', 'Something went wrong with PayPal');
			}

			if (!$response['paypal_link']) {
				return redirect()->route('my_premium_subscription')->with('errorFails', 'Something went wrong with PayPal');
			}else{
				return redirect($response['paypal_link']);
			}
		}
	}

	public function generate_premium_wallet_payment($user_id, $totalAmount, $payment_for = 'renew') {
		$subscription = Subscription::find(1);

		if($subscription->billing_period == 'Day'){
			$end_date_duration = "+1 days";
		}elseif ($subscription->billing_period == 'Week'){
			$end_date_duration = "+7 days";
		}elseif ($subscription->billing_period == 'SemiMonth'){
			$end_date_duration = "+15 days";
		}elseif ($subscription->billing_period == 'Month'){
			$end_date_duration = "+1 months";
		}elseif ($subscription->billing_period == 'Year'){
			$end_date_duration = "+1 year";
		} else {
			$end_date_duration = "+1 months";
		}

		$transaction_id = $this->generate_txnid();
		$subscribeUser = SubscribeUser::where('user_id',$user_id)->first();
		if(empty($subscribeUser)){
			$subscribeUser = new SubscribeUser;
			$subscribeUser->user_id = $user_id;
			$end_date = date("Y-m-d H:i:s", strtotime($end_date_duration));
		} else {
			if($subscribeUser->end_date > date("Y-m-d H:i:s")) {
				$end_date = date("Y-m-d H:i:s", strtotime($end_date_duration, strtotime($subscribeUser->end_date)));
			} else {
				$end_date = date("Y-m-d H:i:s", strtotime($end_date_duration));
			}
		}
		$subscribeUser->subscription_id = $subscription->id;
		$subscribeUser->transaction_id = $transaction_id;
		$subscribeUser->profile_id = null;
		$subscribeUser->end_date = $end_date;
		$subscribeUser->receipt = null;
		$subscribeUser->is_cancel = 0;
		$subscribeUser->is_sync = 0;
		$subscribeUser->is_payment_received = 1;
		$subscribeUser->profile_start_date = date('Y-m-d H:i:s');
		if($payment_for == 'renew') {
			$subscribeUser->is_renew = 1;
		} else {
			$subscribeUser->is_renew = 0;
		}
		$subscribeUser->payment_by = 1;
		$subscribeUser->save();

		if($payment_for == 'renew') {
			$note_msg = 'Premium subscription renew from Wallet';
		} else {
			$note_msg = 'Premium subscription from Wallet';
		}
		$buyerTransaction = new BuyerTransaction;
		$buyerTransaction->buyer_id = $user_id;
		$buyerTransaction->note = $note_msg;
		$buyerTransaction->anount = $totalAmount;
		$buyerTransaction->status = 'premium_subscription';
		$buyerTransaction->wallet_amount = $totalAmount;
		$buyerTransaction->created_at = time();
		$buyerTransaction->save();

		$subscribe_transactions = new SubscribeTransactionHistory;
		$subscribe_transactions->user_id = $user_id;
		$subscribe_transactions->subscription_id = $subscription->id;
		$subscribe_transactions->transaction_id = $transaction_id;
		$subscribe_transactions->profile_id = null;
		$subscribe_transactions->start_date = date('Y-m-d H:i:s');
		$subscribe_transactions->end_date = $end_date;
		$subscribe_transactions->receipt = null;
		if($payment_for == 'renew') {
			$subscribe_transactions->is_renew = 1;
			$note = "Renew premium subscription by Wallet";
		} else {
			$subscribe_transactions->is_renew = 0;
			$note = "Premium subscription by Wallet";
		}
		$subscribe_transactions->note = $note;
		$subscribe_transactions->save();

		/* Send 0.10% in Jim Acc. */
		$serviceChargeJim = ($totalAmount * env('JIM_CHARGE_PER')) / 100;
		$UserJim = User::where('id', '38')->first();
		if (!empty($UserJim)) {
			$UserJim->earning = $UserJim->earning + $serviceChargeJim;
			$UserJim->net_income = $UserJim->net_income + $serviceChargeJim;
			$UserJim->save();

			/* Jim Commission Transactions Start */
			$buyerTransaction = new BuyerTransaction;
			$buyerTransaction->buyer_id = $UserJim->id;
			$buyerTransaction->note = 'Commission for the premium subscription #' . $transaction_id;
			$buyerTransaction->anount = $serviceChargeJim;
			$buyerTransaction->status = 'premium_subscription';
			$buyerTransaction->created_at = time();
			$buyerTransaction->save();
			/* Jim Commission Transactions End */
		}
		
		/* Send wallet transaction email to admin */
		$wallet_transaction_history['buyer'] = $subscribeUser->seller->username;
		$wallet_transaction_history['buyer_email'] = $subscribeUser->seller->email;
		$wallet_transaction_history['transaction_id'] = $transaction_id;
		$wallet_transaction_history['total_amount'] = round($totalAmount,2);
		$wallet_transaction_history['reason'] = ($payment_for == 'renew')? "renew premium subscription" : "premium subscription";
		$wallet_transaction_history['transactions'][] = [
			'title' => ($payment_for == 'renew')? "Renew premium subscription" : "Premium subscription",
			'price' => $totalAmount,
			'quantity' 	=> 1,
			'total' => round($totalAmount,2)
		];
		Order::sendWalletTransactionEmail($wallet_transaction_history);
		return true;
	}

	public function expressCheckoutPremiumSuccess(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('my_premium_subscription')->with('errorFails', get_user_softban_message());
		}
		
		$user =$this->auth_user;
		$subscribeUser = SubscribeUser::where('user_id',$user->id)->first();
		$token = $request->get('token');
		$PayerID = $request->get('PayerID');

		$response = $this->provider->getExpressCheckoutDetails($token);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($response);
		$log->status = "Payment response - getExpressCheckoutDetails";
		$log->payment_for = "premium_seller";
		$log->save();

		if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
			return redirect()->route('my_premium_subscription')->with('errorFails', 'Error processing PayPal payment');
		}

		$subscription = Subscription::find(1);
		$totalAmount = number_format($subscription->price, 2, '.', '');

		$invoice_id = $response['INVNUM'];
		$profile_desc = $response['DESC'];

		/*Start - Create recurring profile*/
		if(empty($subscribeUser)){
			$startdate = Carbon::now()->toAtomString();
		} else {
			if($subscribeUser->end_date > date("Y-m-d H:i:s")) {
				$startdate = Carbon::parse($subscribeUser->end_date)->addDays(1)->toAtomString();
			} else {
				$startdate = Carbon::now()->toAtomString();
			}
		}

		$recuuring_data = [
			'PROFILESTARTDATE' => $startdate,
			'DESC' => $profile_desc,
		    'BILLINGPERIOD' => $subscription->billing_period, // Can be 'Day', 'Week', 'SemiMonth', 'Month', 'Year'
		    'BILLINGFREQUENCY' => $subscription->billing_frequency,
		    'AMT' => $totalAmount,
		    'CURRENCYCODE' => 'USD',
		   /* 'TRIALBILLINGPERIOD' => $subscription->billing_period,
		    'TRIALBILLINGFREQUENCY' => 1,
		    'TRIALTOTALBILLINGCYCLES' => 1, 
		    'TRIALAMT' => 0,*/
		];

		$response_profile = $this->provider->createRecurringPaymentsProfile($recuuring_data, $token);

		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($response_profile);
		$log->status = "Create profile response - createRecurringPaymentsProfile";
		$log->payment_for = "premium_seller";
		$log->save();

		if ($response_profile['ACK'] == 'Failure') {
			$this->send_failed_notification($user->id);
			return redirect()->route('my_premium_subscription')->with('errorFails', 'Something went wrong while creating recurring profile');
		}

		$profile_id = $response_profile['PROFILEID'];

		sleep(3);

		$response_profile_detail = $this->provider->getRecurringPaymentsProfileDetails($profile_id);
		
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($response_profile_detail);
		$log->status = "Profile details - getRecurringPaymentsProfileDetails";
		$log->payment_for = "premium_seller";
		$log->save();
		/*End - Create recurring profile*/

		/*$request_data = [];
		$request_data['items'] = [
		    [
		        'name' => $subscription->package_name,
		        'price' => $totalAmount,
		        'qty' => 1
		    ]
		];

		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = $profile_desc;
		$request_data['return_url'] = route('paypal_express_checkout_premium_success');
		$request_data['cancel_url'] = route('my_premium_subscription');
		$request_data['total'] = $totalAmount;

		$payment_status = $this->provider->doExpressCheckoutPayment($request_data, $token, $PayerID);

		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($payment_status);
		$log->status = "Payment response - doExpressCheckoutPayment";
		$log->payment_for = "premium_seller";
		$log->save();

		if ($payment_status['ACK'] == 'Failure') {
			return redirect()->route('my_premium_subscription')->with('errorFails', 'Something went wrong with PayPal');
		}*/

		$status = $response_profile_detail['STATUS'];
		$txn_id = $response_profile_detail['PROFILEID'];
		
		if ($status == 'Active') {
			
			if(empty($subscribeUser)){
				$subscribeUser = new SubscribeUser;
				$subscribeUser->user_id = $user->id;
			}
			//$subscribeUser->end_date = date("Y-m-d H:i:s");
			$subscribeUser->end_date = $startdate;
			$subscribeUser->subscription_id = $subscription->id;
			$subscribeUser->transaction_id = $txn_id;
			$subscribeUser->profile_id = $profile_id;
			$subscribeUser->receipt = json_encode($response_profile_detail);
			$subscribeUser->is_cancel = 0;
			$subscribeUser->is_payment_received = 0;
			$subscribeUser->profile_start_date = $startdate;
			$subscribeUser->is_renew = 0;
			$subscribeUser->is_sync = 0;
			$subscribeUser->payment_by = 0;
			$subscribeUser->save();

			$buyerTransaction = new BuyerTransaction;
            $buyerTransaction->buyer_id = $subscribeUser->user_id;
            $buyerTransaction->note = 'Premium subscribe from Credit Card/Paypal (pending)';
            $buyerTransaction->transaction_id = $subscribeUser->transaction_id;
            $buyerTransaction->anount = $totalAmount;
            $buyerTransaction->status = 'premium_subscription';
			$buyerTransaction->paypal_amount = $totalAmount;
            $buyerTransaction->created_at = time();
            $buyerTransaction->save();


			return redirect()->route('transactions')->with('errorSuccess','Premium seller subscribe successfully');
		}else{
			return redirect()->route('my_premium_subscription')->with('error_msg', 'Error processing PayPal payment');
		}
	}

	/*recurring service order*/
	public function cancelPremiumOrder($profile_id){
		$response = $this->provider->cancelRecurringPaymentsProfile($profile_id);
		if ($response['ACK'] == 'Failure') {
			$orderSubscription = OrderSubscription::where('profile_id',$profile_id)->first();
			if(!empty($orderSubscription)){
				$log = new PaymentLog;
				$log->user_id = $orderSubscription->order->uid;
				$log->receipt = json_encode($response);
				$log->status = "Profile details cancel - cancelPremiumOrder";
				$log->payment_for = "service";
				$log->save();
			}
			return false;
		}else{
			$orderSubscription = OrderSubscription::where('profile_id',$profile_id)
			->first();
			if(count($orderSubscription) > 0){
				$orderSubscription->is_cancel = 1;
				$orderSubscription->save();
				$this->sellerCancelRecurringMail($profile_id);
				$this->buyerCancelRecurringMail($profile_id);
			}
			return true;
		}
	}

	/*send cancel mail to seller*/
	public function sellerCancelRecurringMail($profile_id)
	{
		$orderSubscription = OrderSubscription::select('id','order_id','profile_id')->where('profile_id',$profile_id)->first();

		$Order = Order::select('id','order_no','uid','seller_uid')->where('id',$orderSubscription->order_id)->first();

		if (!empty($Order)) 
		{
			/* Send Email to Seller */

			$orderDetail = Order::select('id', 'order_no', 'uid', 'seller_uid','service_id','txn_id','created_at')->where('order_no', $Order->order_no)->get();

			$seller = User::select('id','email','username')->find($Order->seller_uid);
			$buyer = User::select('id','email','username')->find($Order->uid);

			$data = [
				'receiver_secret' => $seller->secret,
				'email_type' => 1,
				'subject' => 'Your recurring service has been stopped',
				'template' => 'frontend.emails.v1.cancel_recurring_order_seller',
				'email_to' => $seller->email,
				'username' => $seller->username,
				'orderNumber' => $Order->order_no,
				'orderDetail' => $orderDetail,
				'name' => $buyer->username,
				'profile_id' => $orderSubscription->profile_id,
			];
			QueueEmails::dispatch($data, new SendEmailInQueue($data));
		}
	}
	/*send cancel mail to buyer*/
	public function buyerCancelRecurringMail($profile_id)
	{
		$orderSubscription = OrderSubscription::select('id','order_id','profile_id')->where('profile_id',$profile_id)->first();

		$Order = Order::select('id','order_no','uid','seller_uid')->where('id',$orderSubscription->order_id)->first();

		if (!empty($Order)) 
		{
			/* Send Email to buyer */
			$orderDetail = Order::select('id', 'order_no', 'uid', 'seller_uid','service_id','txn_id','created_at')->where('order_no', $Order->order_no)->get();

			$buyer = User::select('id','email','username')->find($Order->uid);

			$data = [
				'receiver_secret' => $buyer->secret,
				'email_type' => 1,
				'subject' => 'Your recurring service has been stopped',
				'template' => 'frontend.emails.v1.cancel_recurring_order_buyer',
				'email_to' => $buyer->email,
				'username' => $buyer->username,
				'orderNumber' => $Order->order_no,
				'orderDetail' => $orderDetail,
				'name' => $buyer->username,
				'profile_id' => $orderSubscription->profile_id,
			];
			QueueEmails::dispatch($data, new SendEmailInQueue($data));
		}
	}

	//When cancel/complete/dispute recurring order update last child recurrence
	public function updateLastChildRecurrence($Order){
		if($Order->status == 'cancelled'){
			$childOrderFind = Order::withoutGlobalScope('parent_id')->where('parent_id',$Order->id)->orderBy('id','desc')->first();
			if(count($childOrderFind)){
				$childOrderFind->status = $Order->status;
				$childOrderFind->cancel_date = $Order->cancel_date;
				$childOrderFind->cancel_note = $Order->cancel_note;
				$childOrderFind->save();
			}
		}elseif($Order->status == 'completed'){
			$childOrderFind = Order::withoutGlobalScope('parent_id')->where('parent_id',$Order->id)->get();
			foreach($childOrderFind as $childOrder){
				$childOrder->status = $Order->status;
				$childOrder->completed_date = $Order->completed_date;
				$childOrder->completed_note = $Order->completed_note;
				$childOrder->save();
			}
		}elseif($Order->status == 'delivered'){
			$childOrderFind = Order::withoutGlobalScope('parent_id')->where('parent_id',$Order->id)->orderBy('id','desc')->first();
			if(count($childOrderFind)){
				$childOrderFind->status = $Order->status;
				$childOrderFind->delivered_date = $Order->delivered_date;
				$childOrderFind->delivered_note = $Order->delivered_note;
				$childOrderFind->save();
			}
		}
	}

	//Create new child recurring order
	private function createChildOrderNo($Order){
		$order_no = $Order->order_no;
		if($Order->latest_child){
			$order_no = $Order->latest_child->order_no;
		}

		$orderArray = explode("-",$order_no);
		$new_order_no = $orderArray[0];
		
		if(isset($orderArray[1])){
			$orderIndex = (int)$orderArray[1] + 1;
			$new_order_no .= '-'.$orderIndex;
		}else{
			$new_order_no .= '-1';
		}
		return $new_order_no;
	}

	//Create new child Recurring order
	public function createNewChildRecurringOrder($Order,$receipt){

		//$childOrderCount = Order::where('parent_id',$Order->id)->count();
        //$total_cycle_completed = $childOrderCount + 1;
		//$childOrderCount = Order::withoutGlobalScope('parent_id')->where('parent_id',$Order->id)->select('id')->count();
        //$total_cycle_completed = $childOrderCount;

		$billing_period = 'months';
		if($receipt['BILLINGPERIOD'] == 'Day'){
			$billing_period = 'day';
		}

		$childOrderFind = Order::withoutGlobalScope('parent_id')->where('parent_id',$Order->id)->orderBy('id','desc')->first();

		$total_cycle_completed = $receipt['NUMCYCLESCOMPLETED'];

		if(count($childOrderFind) == 0){
			// For first recurrence
			// $start_date = date("Y-m-d H:i:s", strtotime($receipt['PROFILESTARTDATE']));
			// $Order->start_date = $start_date;

			$start_date =  Carbon::parse($receipt['LASTPAYMENTDATE'])->setTimezone('America/Los_Angeles')->format('Y-m-d H:i:s');
			$Order->start_date = $start_date;
		}else{
			$start_date =  Carbon::parse($childOrderFind->end_date)->setTimezone('America/Los_Angeles')->format('Y-m-d H:i:s');
			//$start_date = date("Y-m-d H:i:s", strtotime($childOrderFind->end_date));
		}

		if(isset($receipt['NEXTBILLINGDATE'])){
			$end_date =  Carbon::parse($receipt['NEXTBILLINGDATE'])->setTimezone('America/Los_Angeles')->format('Y-m-d H:i:s');
			//$end_date = date("Y-m-d H:i:s", strtotime($receipt['NEXTBILLINGDATE']));
		}else{
			$end_date = Carbon::now()->addDays(1)->setTimezone('America/Los_Angeles')->format('Y-m-d H:i:s');
			//$end_date = date('Y-m-d H:i:s', strtotime($start_date . "+1 months"));
		}

		//Delivered previous child order if found
        if(count($childOrderFind) && $total_cycle_completed >= 1){

			if($Order->is_course == 0){
				$childOrderFind->delivered_date = $childOrderFind->end_date;
				$childOrderFind->delivered_note = $Order->delivered_note;

				if($Order->status == 'delivered'){
					$childOrderFind->delivered_date = $Order->delivered_date;
					$childOrderFind->delivered_note = $Order->delivered_note;
				}

				$childOrderFind->status = 'delivered';
				$childOrderFind->save();

				/* Send Notification to buyer */
				$notification = new Notification;
				$notification->notify_to = $childOrderFind->uid;
				$notification->notify_from = $childOrderFind->seller_uid;
				$notification->notify_by = 'admin';
				$notification->order_id = $childOrderFind->id;
				$notification->is_read = 0;
				$notification->type = 'delivered_order';
				$notification->message = 'Order #' . $childOrderFind->order_no . ' has delivered';
				$notification->created_at = time();
				$notification->updated_at = time();
				$notification->save();

				$orderDetail = [];
				$orderDetail['seller'] = $childOrderFind->seller->username;
				$orderDetail['created_at'] = $childOrderFind->created_at;
				$orderDetail['service'] = $childOrderFind->service->title;
				$orderDetail['order_no'] = $childOrderFind->order_no;
			
				$buyer = User::select('id','email','username')->find($childOrderFind->uid);
				$data = [
					'receiver_secret' => $buyer->secret,
					'email_type' => 1,
					'subject' => 'Your Order has been delivered',
					'template' => 'frontend.emails.v1.delivery_order',
					'email_to' => $buyer->email,
					'username' => $buyer->username,
					'orderNumber' => $childOrderFind->order_no,
					'orderDetail' => $orderDetail,
				];
				QueueEmails::dispatch($data, new SendEmailInQueue($data));

				/* Send Notification to Seller */
				$notification = new Notification;
				$notification->notify_to = $childOrderFind->seller_uid;
				$notification->notify_from = $childOrderFind->uid;
				$notification->notify_by = 'admin';
				$notification->order_id = $childOrderFind->id;
				$notification->is_read = 0;
				$notification->type = 'delivered_order';
				$notification->message = 'Order #' . $childOrderFind->order_no . ' has delivered';
				$notification->created_at = time();
				$notification->updated_at = time();
				$notification->save();

				//Moved attached file to new child order
				\DB::table('user_files')->where('order_id',$Order->id)
				->where('created_at','>=',$childOrderFind->start_date)
				->where('created_at','<=',$childOrderFind->end_date)->update(['order_id'=>$childOrderFind->id]);

				//Moved seller work to new child order
				\DB::table('sellers_work')->where('order_id',$Order->id)
				->where('created_at','>=',$childOrderFind->start_date)
				->where('created_at','<=',$childOrderFind->end_date)->update(['order_id'=>$childOrderFind->id]);
			}else{
				$childOrderFind->status = 'completed';
				$childOrderFind->save();
			}
        }

		//Create new child active order from parent order
		$childOrder = $Order->refresh()->replicate();
		$childOrder->order_no = $this->createChildOrderNo($Order);

		//Check order is old orders then no affiation for each recurrence
		$is_new_order = $Order->check_is_new_recurring_order($Order->created_at);
		if($is_new_order == false){ 
			$childOrder->is_affiliate = 0;
			$childOrder->affiliate_id = 0;

			// First child order must have parent affeliation to show
			$childOrderCount = Order::withoutGlobalScope('parent_id')->where('parent_id',$Order->id)->select('id')->count();
			if($childOrderCount == 0){ 
				$childOrder->is_affiliate = $Order->is_affiliate;
				$childOrder->affiliate_id = $Order->affiliate_id;
			}
		}

		$findBuyerTransaction = BuyerTransaction::where('order_id',$Order->id)->select('id','payment_processing_fee')->first();

		$childOrder->is_transfer_to_seller_wallet = 0;
		$childOrder->helpful_count = NULL;
		$childOrder->report_abuse_count = NULL;
		$childOrder->parent_id = $Order->id;
		$childOrder->start_date = $start_date;
		if(!is_null($findBuyerTransaction) && $findBuyerTransaction->payment_processing_fee > 0) {
			$childOrder->order_total_amount = $receipt['AMT'] - $findBuyerTransaction->payment_processing_fee;
		} else {
			$childOrder->order_total_amount = $receipt['AMT'];
		}
        $childOrder->end_date = $end_date;
		$childOrder->created_at = Carbon::now();
		$childOrder->status = 'active';
		$childOrder->save();

		//Create new child order extra
		if(!empty($Order->extra)){
			foreach($Order->extra as $extra){
				$childOrderExtra = $extra->refresh()->replicate();
				$childOrderExtra->order_id = $childOrder->id;
				$childOrderExtra->created_at = Carbon::now();
				$childOrderExtra->updated_at = Carbon::now();
				$childOrderExtra->save();
			}
		}

		//Update main order total_amount and end_date
		$Order->end_date = $end_date;

		if($receipt['AGGREGATEAMT'] > 0){
			if(!is_null($findBuyerTransaction) && $findBuyerTransaction->payment_processing_fee > 0 && $receipt['NUMCYCLESCOMPLETED'] > 0) {
				$Order->order_total_amount = $receipt['AGGREGATEAMT'] - ($findBuyerTransaction->payment_processing_fee * $receipt['NUMCYCLESCOMPLETED']);
			} else {
				$Order->order_total_amount = $receipt['AGGREGATEAMT'];
			}
		}else{
			if(!is_null($findBuyerTransaction) && $findBuyerTransaction->payment_processing_fee > 0) {
				$Order->order_total_amount = $receipt['AMT'] - $findBuyerTransaction->payment_processing_fee;
			} else {
				$Order->order_total_amount = $receipt['AMT'];
			}
		}

		//Update parent order status "active" if its "delivered/new"
		if($Order->status == 'delivered'){
			$Order->status = 'active';
			$Order->delivered_date = NULL;
			$Order->delivered_note = NULL;
		}

		$Order->save();

		//Moved attached file to new child order
        \DB::table('user_files')->where('order_id',$Order->id)
        ->where('created_at','>=',$start_date)
        ->where('created_at','<=',$end_date)->update(['order_id'=>$childOrder->id]);

        //Moved seller work to new child order
        \DB::table('sellers_work')->where('order_id',$Order->id)
        ->where('created_at','>=',$start_date)
        ->where('created_at','<=',$end_date)->update(['order_id'=>$childOrder->id]);

		return $childOrder->id;
	}

	public function fix_recurring_order($orderSubscription){
		date_default_timezone_set('America/Los_Angeles');
		$profile_id = $orderSubscription->profile_id;
		$response_profile_detail = $this->provider->getRecurringPaymentsProfileDetails($profile_id);
		$receipt = json_decode($orderSubscription->receipt);
		if(isset($receipt->NEXTBILLINGDATE) && ($receipt->NEXTBILLINGDATE != $response_profile_detail['NEXTBILLINGDATE']) && $receipt->NUMCYCLESCOMPLETED != $response_profile_detail['NUMCYCLESCOMPLETED']){
			if(isset($receipt->NEXTBILLINGDATE) && ($receipt->NEXTBILLINGDATE != $response_profile_detail['NEXTBILLINGDATE'])){
				$Order = Order::find($orderSubscription->order_id);
				
				//Create new order from old order details
				$this->createNewChildRecurringOrder($Order,$response_profile_detail);

				//Create new order subscription clone form old subscription
				$orderSubscription->last_buyer_payment_date = date("Y-m-d");
				$orderSubscription->expiry_date = date("Y-m-d H:i:s", strtotime($response_profile_detail['NEXTBILLINGDATE']));
				$orderSubscription->receipt = json_encode($response_profile_detail);
				$orderSubscription->is_cancel = 0;
				$orderSubscription->save();

				//Create Buyer Transaction History
				$findOldTrancaction = BuyerTransaction::where('order_id',$Order->id)
				->where('buyer_id',$Order->uid)
				->where('status','deposit')
				->first();
				
				if(!empty($findOldTrancaction)){
					$buyerTransaction = $findOldTrancaction->replicate();
					$buyerTransaction->note = 'Payment for Renew Recurring Service';
					$buyerTransaction->created_at = time();
					$buyerTransaction->updated_at = time();
					$buyerTransaction->save();
				}

				//Create Pending Clearance transaction
				$sellerEarning = SellerEarning::select('id','anount')->where(['order_id' => $Order->id, 'seller_id' => $Order->seller_uid])->first();
				if(!empty($sellerEarning)){
					//Check pending clearance entry exists
					$earningCount = SellerEarning::select('id')->where(['status'=>'pending_clearance','order_id' => $Order->id, 'seller_id' => $Order->seller_uid])->count();
					if($earningCount == 0){

						//Check affiliate for earning (for next recurrence affilite will skip)
						$affiliate_amount = 0;
						if ($Order->is_affiliate == "1") {
							$Affiliate = AffiliateEarning::select('id','amount')->where(['order_id' => $Order->id, 'seller_id' => $Order->seller_uid])->first();
							if (!empty($Affiliate)) {
								$affiliate_amount = $Affiliate->amount;
							}
						}

						$newSellerEarning = new SellerEarning;
						$newSellerEarning->order_id = $Order->id;
						$newSellerEarning->seller_id = $Order->seller_uid;
						$newSellerEarning->note = 'Pending Clearance';
						$newSellerEarning->anount = $sellerEarning->anount + $affiliate_amount;
						$newSellerEarning->status = 'pending_clearance';
						$newSellerEarning->save();
					}
				}
				
				//Update payment date on seller earning
				Order::storeSellerEarningPaymentDate($Order);
				
				//Store recurring order history
				$this->storeRecurringHistory($response_profile_detail,$orderSubscription->order_id,$profile_id);
				
			}
		}
	}

	/*Renew Order subscription*/
	public function renewRecurringOrderSubscription($orderSubscription){
		date_default_timezone_set('America/Los_Angeles');
		$profile_id = $orderSubscription->profile_id;
		$response_profile_detail = $this->provider->getRecurringPaymentsProfileDetails($profile_id);
		$receipt = json_decode($orderSubscription->receipt);

		if(isset($receipt->NEXTBILLINGDATE) && ($receipt->NEXTBILLINGDATE != $response_profile_detail['NEXTBILLINGDATE']) && $receipt->NUMCYCLESCOMPLETED != $response_profile_detail['NUMCYCLESCOMPLETED']){
			$Order = Order::find($orderSubscription->order_id);
			
			//Create new order from old order details
			$this->createNewChildRecurringOrder($Order,$response_profile_detail);

			//Create new order subscription clone form old subscription
			$orderSubscription->last_buyer_payment_date = date("Y-m-d");

			if(isset($response_profile_detail['NEXTBILLINGDATE'])){
				$orderSubscription->expiry_date = date("Y-m-d H:i:s", strtotime($response_profile_detail['NEXTBILLINGDATE']));
			}else{
				$orderSubscription->expiry_date = date('Y-m-d H:i:s', strtotime($orderSubscription->expiry_date . "+1 months"));
			}
			
			$orderSubscription->receipt = json_encode($response_profile_detail);
			$orderSubscription->is_cancel = 0;
			$orderSubscription->save();

			//Create Buyer Transaction History
			$findOldTrancaction = BuyerTransaction::where('order_id',$Order->id)
			->where('buyer_id',$Order->uid)
			->where('status','deposit')
			->first();
			
			if(!empty($findOldTrancaction)){
				$buyerTransaction = $findOldTrancaction->replicate();
				$buyerTransaction->note = 'Payment for Renew Recurring Service';
				$buyerTransaction->created_at = time();
				$buyerTransaction->updated_at = time();
				$buyerTransaction->save();
			}

			//Create Pending Clearance transaction
			$sellerEarning = SellerEarning::select('id','anount')->where(['order_id' => $Order->id, 'seller_id' => $Order->seller_uid])->first();
			if(!empty($sellerEarning)){
				//Check pending clearance entry exists
				//Check affiliate for earning (for next recurrence affilite will skip)
				$affiliate_amount = 0;
				if ($Order->is_affiliate == "1") {
					$Affiliate = AffiliateEarning::select('id','amount')->where(['order_id' => $Order->id, 'seller_id' => $Order->seller_uid])->first();
					if (!empty($Affiliate)) {
						$affiliate_amount = $Affiliate->amount;
					}
				}

				$newSellerEarning = new SellerEarning;
				$newSellerEarning->order_id = $Order->id;
				$newSellerEarning->seller_id = $Order->seller_uid;
				$newSellerEarning->note = 'Pending Clearance';
				$newSellerEarning->anount = $sellerEarning->anount + $affiliate_amount;
				$newSellerEarning->status = 'pending_clearance';
				$newSellerEarning->save();
			}
			
			//Update payment date on seller earning
			Order::storeSellerEarningPaymentDate($Order);
			
			//Store recurring order history
			$this->storeRecurringHistory($response_profile_detail,$orderSubscription->order_id,$profile_id);
			
		}elseif(isset($response_profile_detail['STATUS']) && $response_profile_detail['STATUS'] == "Cancelled"){
			/*begin : cancel order*/
    		$order = Order::where(['id'=>$orderSubscription->order_id])
    			->whereNotIn('status', ['cancelled','completed'])
    			->first();
    		if(count($order) > 0){
    			$order->cancel_by = 1;
				$order->status = 'cancelled';
				$order->cancel_date = date('Y-m-d H:i:s');
				$order->cancel_note = "Cancelled order by admin due to recurring payment cancelled";
				$order->save();

				//Update last child recurrence (cancel order process)
				$this->updateLastChildRecurrence($order);

				/* Send Notification to buyer Start */
				$notify_from = 0;
				$notify_to = $order->uid;

				$notification = new Notification;
				$notification->notify_to = $notify_to;
				$notification->notify_from = $notify_from;
				$notification->notify_by = 'admin';
				$notification->order_id = $order->id;
				$notification->is_read = 0;
				$notification->type = 'cancel_order';
				$notification->message = 'Order #' . $order->order_no . ' has cancelled';
				$notification->created_at = time();
				$notification->updated_at = time();
				$notification->save();

				/*for seller*/
				$notify_to = $order->seller_uid;

				$notification = new Notification;
				$notification->notify_to = $notify_to;
				$notification->notify_from = $notify_from;
				$notification->notify_by = 'admin';
				$notification->order_id = $order->id;
				$notification->is_read = 0;
				$notification->type = 'cancel_order';
				$notification->message = 'Order #' . $order->order_no . ' has cancelled';
				$notification->created_at = time();
				$notification->updated_at = time();
				$notification->save();
				/* Send Notification to buyer End */

				$this->cancelPremiumOrder($profile_id);
    		}
    		
			/*end : cancel order*/

			$orderSubscription->receipt = json_encode($response_profile_detail);
			$orderSubscription->is_cancel = 1;
			$orderSubscription->save();
			
		}else{
			if (isset($response_profile_detail['type']) && $response_profile_detail['type'] == 'error'){
			}else{
				$mail_to = env('NEW_HELP_EMAIL');
			}
			//Send mail to admin about cancel this order
			$Order = Order::select('id','uid','order_no')->find($orderSubscription->order_id);
			$user = User::select('email','username')->where('id',$Order->uid)->first();
			$data = [
				'subject' => 'Problem with recurring order #'.$Order->order_no.' renew profile',
				'template' => 'frontend.emails.v1.premium_subscription_renew_issue',
				'email_to' => $mail_to,
				'username' => $user->username,
				'profile_id' => $profile_id,
			];
			QueueEmails::dispatch($data, new SendEmailInQueue($data));
		}
		return 1;
	}

	public function storeRecurringHistory($receipt,$order_id,$profile_id,$type="renew",$order_amt=0) {
		$findBuyerTransaction = BuyerTransaction::where('order_id',$order_id)->select('id','payment_processing_fee')->first();
		//create subscription history
		$last_pay_date = Carbon::parse($receipt['LASTPAYMENTDATE'])->format('Y-m-d H:i:s');
		$next_bill_date = Carbon::parse($receipt['NEXTBILLINGDATE'])->format('Y-m-d H:i:s');
		$check_history_exist = OrderSubscriptionHistory::where('order_id',$order_id)
				->where('profile_id',$profile_id)
				->whereDate('payment_date',date('Y-m-d',strtotime($last_pay_date)))
				->whereDate('expiry_date',date('Y-m-d',strtotime($next_bill_date)))
				->count();

		if($check_history_exist == 0) {
			$history = new OrderSubscriptionHistory;
			$history->order_id = $order_id;
			$history->profile_id = $profile_id;
			if($type == "initial") {
				$history->amount = $order_amt;
			} else {
				if(!is_null($findBuyerTransaction) && $findBuyerTransaction->payment_processing_fee > 0) {
					$history->amount = $receipt['AMT'] - $findBuyerTransaction->payment_processing_fee;
				} else {
					$history->amount = $receipt['AMT'];
				}
			}
			$history->payment_date = $last_pay_date;
			$history->expiry_date = $next_bill_date;
			$history->receipt = json_encode($receipt);
			$history->save();
		}
		return true;
	}

	/*get recurring profile details*/
	public function checkRecurringPaymentReceive($profile_id){
		$is_payment_received = false;
		$response_profile_detail = $this->provider->getRecurringPaymentsProfileDetails($profile_id);
		if(isset($response_profile_detail['LASTPAYMENTAMT'])){
			$lastPayment = str_replace( ',', '', $response_profile_detail['LASTPAYMENTAMT']);
			if($lastPayment > 0){
				$is_payment_received = true;
			}
		}
		return $is_payment_received;
	}

	/* begin : Add amount to wallet by CC */
	public function ccDepositeAmtCheckPayment(Request $request){
		//Check payment 
        $tempData = BluesnapTempTransaction::where('merchanttransactionid',$request->invoice_id)
        ->whereNotNull('receipt')
        ->where('payment_for',4)
        ->first();

        if(count($tempData) > 0){
            $response = json_decode($tempData->receipt);
            $txn_id = $response->referenceNumber;
            return response()->json(['success'=>true,'url'=> route('transactions')]);
        }else{
            return response()->json(['success'=>false]);
        }
        exit();
    }

	public function ccDepositeAmtThankyou(Request $request,$invoice_id){
		//Thank you page
        return view('frontend.cart.bluesnap_deposite_wallet_thankyou',compact('invoice_id'));
    }

	private function expressCheckoutAddMoneyByCC($request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('transactions')->with('errorFails', get_user_softban_message());
		}

		$user =$this->auth_user;

		// only allow if wallet < $50
		if($user->earning >= env('CC_MIN_WALLET_AMT_REQ_TO_DEPOSITE')){
			return redirect()->back()->with('errorFails', 'You account wallet have enough balance.');
		}

		//allow max $300
		if($request->wallet_amount > env('CC_MAX_DEPOSITE_TO_WALLET')){
			return redirect()->back()->with('errorFails', 'Please enter amount less then or equal to '.env('CC_MAX_DEPOSITE_TO_WALLET'));
		}

		$totalAmount = number_format($request->wallet_amount, 2, '.', '');
		
		/*store referance transaction*/
		$invoice_id = "LE".get_microtime();
		$request_data = [];
		$request_data['items'] = [
			[
				'name' => "Deposit Amount to Wallet",
				'price' => $totalAmount,
				'qty' => 1
			]
		];

		/* apply processing fee - start */
		$processing_fee = calculate_payment_processing_fee($totalAmount);
		$temp_item = [
			'name' => "Payment Processing Fee",
			'price' => $processing_fee,
			'qty' => 1
		];
		array_push($request_data['items'],$temp_item);

		$totalAmount += $processing_fee;
		/* apply processing fee - end */

		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = "Deposit Amount to Wallet #".$invoice_id;
		$request_data['return_url'] = route('cc_deposite_amt.thankyou',[$invoice_id]);
		$request_data['cancel_url'] = route('transactions');
		$request_data['total'] = round_price($totalAmount);

		$sessionData = [
			'cc_custom_data' => $request->all(),
			'processing_fee' => $processing_fee
		];
		
		$tempData = new BluesnapTempTransaction;
		$tempData->merchanttransactionid = $invoice_id;
		$tempData->user_id = $user->id;
		$tempData->cart_data = json_encode($sessionData);
		$tempData->payment_for = 4;
		$tempData->save();
		
		/* payment new flow - start */
		$token_string = env('BlueSnapID').':'.env('BlueSnapPassword');
		$token = base64_encode($token_string);

		$url = env('BlueSnapParamsEncryptionURL');
		$bluesnapControllerObj = new BluesnapPaymentController();
		$xml = $bluesnapControllerObj->get_xml_params($request_data,$invoice_id,'cc_deposite_amt.thankyou');
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
			Log::info(curl_error($ch));
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
			return redirect()->route('transactions')->with('errorFails', 'Something went wrong, Please try again');
		}
		/* payment new flow - end */
 	}
	/* end : Add amount to wallet by CC */

	/*begin : Add Amount to wallet from paypal*/
	public function expressCheckoutAddMoney(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('transactions')->with('errorFails', get_user_softban_message());
		}

		if(!in_array($request->request_from,[1,2,3])){
			return redirect()->back()->with('errorFails', 'Somethig goes wrong');
		}

		if($request->request_from == 2){
			//Pay with credit card
			return $this->expressCheckoutAddMoneyByCC($request);
		}

		if($request->request_from == 3){
			//Pay with Skrill
			$skrillControllerObj = new SkrillPaymentController();
			return $skrillControllerObj->depositMoneyToWallet($request);
		}

		if($request->wallet_amount < 1){
			return redirect()->back()->with('errorFails', 'Please enter amount greater then or equal to 1');
		}

		$totalAmount = number_format($request->wallet_amount, 2, '.', '');

		$request_data = [];
		$request_data['items'] = [
			[
				'name' => "Deposit Amount to Wallet",
				'price' => $totalAmount,
				'qty' => 1
			]
		];

		/* apply processing fee - start */
		$processing_fee = calculate_payment_processing_fee($totalAmount);
		$temp_item = [
			'name' => "Payment Processing Fee",
			'price' => $processing_fee,
			'qty' => 1
		];
		array_push($request_data['items'],$temp_item);

		$totalAmount += $processing_fee;
		/* apply processing fee - end */

		$user =$this->auth_user;

		$invoice_id = "LE".get_microtime();

		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = "Deposit Amount to Wallet #".$invoice_id;
		$request_data['return_url'] = route('add_money_to_wallet_success');
		$request_data['cancel_url'] = route('transactions');
		$request_data['total'] = round_price($totalAmount);

		try{

			$options = [
			    'SOLUTIONTYPE' => 'Sole',
			];
			$response = $this->provider->addOptions($options)->setExpressCheckout($request_data);
			/*Create Log for payment request data*/
			$log = new PaymentLog;
			$log->user_id = $user->id;
			$log->receipt = json_encode($response);
			$log->status = "Request data";
			$log->payment_for = "add_money_to_wallet";
			$log->save();

		}catch(\Exception $e){
			return redirect()->route('transactions')->with('errorFails', 'Something went wrong with PayPal');
		}

		if (!$response['paypal_link']) {
			return redirect()->route('transactions')->with('errorFails', 'Something went wrong with PayPal');
		}else{
			return redirect($response['paypal_link']);
		}
	}
	public function expressCheckoutAddMoneySuccess(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('transactions')->with('errorFails', get_user_softban_message());
		}

		$user =$this->auth_user;
		$token = $request->get('token');
		$PayerID = $request->get('PayerID');
		
		
		$response = $this->provider->getExpressCheckoutDetails($token);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($response);
		$log->status = "Payment response";
		$log->payment_for = "add_money_to_wallet";
		$log->save();

		if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
			return redirect()->route('transactions')->with('errorFails', 'Error processing PayPal payment');
		}

		$invoice_id = $response['INVNUM'];
		$totalAmount = $response['L_AMT0'];
		$inv_desc = $response['DESC'];
		$paypal_buyer_email = $response['EMAIL'];

		$request_data = [];
		$request_data['items'] = [
			[
				'name' => "Deposit Amount to Wallet",
				'price' => $totalAmount,
				'qty' => 1
			]
		];

		/* apply processing fee - start */
		$temp_item = [
			'name' => "Payment Processing Fee",
			'price' => $response['L_AMT1'],
			'qty' => 1
		];
		array_push($request_data['items'],$temp_item);
		/* apply processing fee - end */

		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = $inv_desc;
		$request_data['return_url'] = route('add_money_to_wallet_success');
		$request_data['cancel_url'] = route('transactions');
		$request_data['total'] = $response['AMT'];

		$payment_status = $this->provider->doExpressCheckoutPayment($request_data, $token, $PayerID);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($payment_status);
		$log->status = "Payment response verification";
		$log->payment_for = "add_money_to_wallet";
		$log->save();

		if ($payment_status['ACK'] == 'Failure') {
			$this->send_failed_notification($user->id);
			return redirect()->route('transactions')->with('errorFails', 'Something went wrong with PayPal');
		}

		$status = $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'];
		$txn_id = $payment_status['PAYMENTINFO_0_TRANSACTIONID'];
		

		if ($status == 'Completed') {
			$traExists = BuyerTransaction::where('transaction_id',$txn_id)->first();
			if(empty($traExists)){
				$user = User::find($user->id);
				$beginning_wallet_balance = $user->earning;

				$user->earning = $user->earning + $totalAmount;
				$user->save();

				$buyerTransaction = new BuyerTransaction;
				$buyerTransaction->buyer_id = $user->id;
				$buyerTransaction->note = "Deposit Amount to Wallet By Paypal";
				$buyerTransaction->anount = $totalAmount;
				$buyerTransaction->status = 'add_money_to_wallet';
				$buyerTransaction->transaction_id = $txn_id;
				$buyerTransaction->payment_processing_fee = $response['L_AMT1'];
				$buyerTransaction->created_at = time();
				$buyerTransaction->save();

				/*Send email to seller email*/
				$data = [
					'receiver_secret' => $user->secret,
					'email_type' => 4,
					'subject' => 'You’ve made a wallet deposit',
					'template' => 'frontend.emails.v1.wallet_deposit_conformation',
					'beginning_wallet_balance' => $beginning_wallet_balance,
					'deposit_amount' => $totalAmount,
					'new_wallet_balance' => $user->earning,
					'deposited_via' => $paypal_buyer_email,
					'email_to' => $user->email,
					'name' => $user->Name
				];
				QueueEmails::dispatch($data, new SendEmailInQueue($data));

				/*Send email to paypal email*/
				$data = [
					'subject' => 'You’ve made a wallet deposit',
					'template' => 'frontend.emails.v1.wallet_deposit_conformation',
					'beginning_wallet_balance' => $beginning_wallet_balance,
					'deposit_amount' => $totalAmount,
					'new_wallet_balance' => $user->earning,
					'deposited_via' => $paypal_buyer_email,
					'email_to' => $paypal_buyer_email,
					'name' => $user->Name
				];
				QueueEmails::dispatch($data, new SendEmailInQueue($data));

			}
			return redirect()->route('transactions')->with('errorSuccess','Wallet amount updated successfully');
		}else{
			return redirect()->route('transactions')->with('errorFails', 'Error processing PayPal payment');
		}
	}
	/*end : Add Money to wallet from paypal*/

	public function send_failed_notification($user_id) {
		$notification = new Notification;
		$notification->notify_to = $user_id;
		$notification->notify_from = 0;
		$notification->notify_by = 'admin';
		$notification->is_read = 0;
		$notification->type = 'payment_failed';
		$notification->message = 'Your payment has been failed';
		$notification->created_at = time();
		$notification->updated_at = time();
		$notification->save();
		return true;
	}

	public function check_affiliate_user_purchase_service($seo_url,$user_id) {
		$count = 0;
		$objService = Service::withoutGlobalScope('is_course')->select('uid')->where('seo_url', $seo_url)->first();
		if(count($objService)){
			$count = Order::where('uid',$user_id)->where('seller_uid',$objService->uid)->count();
		}
		return $count;
	}

	public function walletPayUpgradeOrder(Request $request) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('errorFails', get_user_softban_message());
		}
		
		$user =$this->auth_user;
		$order = Order::upgradeorderstatus()->where('order_no',$request->order_no)->where('uid',$user->id)->first();
        if(is_null($order)) {
            return redirect()->back();
        }
		$selected_plan = ServicePlan::where('id',$request->plan_id)->first();
		if($selected_plan->service_id != $order->service_id) {
			return redirect()->back()->with('errorFails', 'Please select valid package.');
		} else if(check_upgrade_plan_status($order->plan_type, $selected_plan->plan_type) == false) {
            return redirect()->back()->with('errorFails', 'Please select greater plan than current plan.');
        }
		$payable = ($selected_plan->price * $order->qty) - ($order->price * $order->qty);
		if($payable < 0) {
            return redirect()->back()->with('errorFails', 'Please select plan with greater amount than current plan.');
		} else if($payable == 0) {
			$txn_id = $this->generate_txnid();
            $this->upgradeOrder($order,$selected_plan,0,$txn_id,$payBy='wallet',0,0,null,'Completed');
            return redirect()->route('buyer_orders_details',$order->order_no)->with('errorSuccess','Order upgraded successfully');
        }

		$from_wallet = $request->is_from_wallet;
		$from_promotional = $request->is_from_promotional;
		$used_promotional = $fromWalletAmount = 0;

		/*begin : Make payment form wallet + promotional*/
		if($from_wallet == 1 && $from_promotional == 1){
			if( $payable <= ($user->earning + $user->promotional_fund)) {
				$txn_id = $this->generate_txnid();
				if($user->promotional_fund >= $payable){
					$used_promotional = $payable;
				}else{
					$used_promotional = $user->promotional_fund;
				}
				$fromWalletAmount = $payable - $used_promotional;
				/* upgrade order */
				$this->upgradeOrder($order,$selected_plan,$payable,$txn_id,$payBy='wallet',$used_promotional,$fromWalletAmount,null);

				return redirect()->route('buyer_orders_details',$order->order_no)->with('errorSuccess','Order upgraded successfully');
			}else{
				\Session::flash('errorFails', 'You have not sufficient amount in your wallet and promotional funds');
				return redirect()->back();
			}
		}
		/*end : Make payment form wallet + promotional*/

		/*begin : Make payment form promotional*/
		 else if($from_promotional == 1){
			if( $payable <= $user->promotional_fund){
				$txn_id = $this->generate_txnid();
				if($user->promotional_fund >= $payable){
					$used_promotional = $payable;
				}else{
					$used_promotional = $user->promotional_fund;
				}

				/* update order */
				$this->upgradeOrder($order,$selected_plan,$payable,$txn_id,$payBy='promotional',$used_promotional,$fromWalletAmount,null);

				return redirect()->route('buyer_orders_details',$order->order_no)->with('errorSuccess','Order upgraded successfully');
			}else{
				\Session::flash('errorFails', 'You have not sufficient amount in your promotional funds');
				return redirect()->back();
			}
		}
		/*end : Make payment form promotional*/
		
		/*begin : Make payment form wallet*/
		else if($from_wallet == 1){
			if( $payable <= $user->earning){
				$txn_id = $this->generate_txnid();
				$fromWalletAmount = $payable;
				/* update order */
				$this->upgradeOrder($order,$selected_plan,$payable,$txn_id,$payBy='wallet',$used_promotional,$fromWalletAmount,null);

				return redirect()->route('buyer_orders_details',$order->order_no)->with('errorSuccess','Order upgraded successfully');
			}else{
				\Session::flash('errorFails', 'You have not sufficient amount in your wallet');
				return redirect()->back();
			}
		}
		/*end : Make payment form wallet*/
		return redirect()->route('buyer_orders_details',$order->order_no);
	}

	public function expressCheckoutUpgradeOrder(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('errorFails', get_user_softban_message());
		}

		$orderObj = new Order;

		$user =$this->auth_user;
		$order = Order::upgradeorderstatus()->where('order_no',$request->order_no)->where('uid',$user->id)->first();
        if(is_null($order)) {
            return redirect()->back();
        }
		$selected_plan = ServicePlan::where('id',$request->plan_id)->first();
		if($selected_plan->service_id != $order->service_id) {
			return redirect()->back()->with('errorFails', 'Please select valid package.');
		} else if(check_upgrade_plan_status($order->plan_type, $selected_plan->plan_type) == false) {
            return redirect()->back()->with('errorFails', 'Please select greater plan than current plan.');
        }
		$payable = ($selected_plan->price * $order->qty) - ($order->price * $order->qty);
		if($payable < 0) {
            return redirect()->back()->with('errorFails', 'Please select plan with greater amount than current plan.');
		} else if($payable == 0) {
			$txn_id = $this->generate_txnid();
			$this->upgradeOrder($order,$selected_plan,0,$txn_id,$payBy='paypal',0,0,null,'Completed',null);
            return redirect()->route('buyer_orders_details',$order->order_no)->with('errorSuccess','Order upgraded successfully');
        }

		$is_from_wallet = $request->is_from_wallet;
		$is_from_promotional = $request->is_from_promotional;

		if($is_from_wallet == 1){
			$fromWalletAmount = $user->earning;
		}else{
			$fromWalletAmount = 0;
		}

		if($is_from_promotional == 1){
			$fromPromotionalAmount = $user->promotional_fund;
		}else{
			$fromPromotionalAmount = 0;
		}

		if ($fromPromotionalAmount > 0) {
			$fromPromotionalAmount = $user->promotional_fund;
		}

		if ($fromWalletAmount > 0) {
			if($user->earning >= $payable){
				$fromWalletAmount = $payable;
			}else{
				$fromWalletAmount = $user->earning;
			}
		}

		/*Total amount with deduct coupon discount*/
		$totalAmount = number_format($payable, 2, '.', '');
		$totalDeduction = $fromWalletAmount + $fromPromotionalAmount;

		if($totalDeduction >= $totalAmount) {
			return redirect()->back()->with('errorFails', 'Please select valid payment option.');
		}
		$temp_wallet = [];
		if ($fromWalletAmount > 0) {
			$temp_wallet['name'] = "From wallet";
			$temp_wallet['price'] = "-" . round_price($fromWalletAmount);
			$temp_wallet['qty'] = 1;
		}
		$temp_promo = [];
		if ($fromPromotionalAmount > 0) {
			$temp_promo['name'] = "From demo Bucks";
			$temp_promo['price'] = "-" . round_price($fromPromotionalAmount);
			$temp_promo['qty'] = 1;
		}
		$request_data = [];
		$request_data['items'] = [
			[
				'name' => ucwords($order->service->title),
				'price' => $totalAmount,
				'qty' => 1
			]
		];
		if(count($temp_wallet) > 0) {
			array_push($request_data['items'],$temp_wallet);
		}
		if(count($temp_promo) > 0) {
			array_push($request_data['items'],$temp_promo);
		}

		/* apply processing fee - start */
		$temp_fee = [];
		$processing_fee = calculate_payment_processing_fee($totalAmount- $totalDeduction);
		$temp_fee['name'] = "Payment Processing Fee";
		$temp_fee['price'] = $processing_fee;
		$temp_fee['qty'] = 1;

		array_push($request_data['items'],$temp_fee);
		/* apply processing fee - end */

		/* begin : check for paypal deduction is not less than 1 */
		if((($totalAmount - $totalDeduction) + $processing_fee) < env('PAYPAL_MINIMUM_PAY_AMOUNT')) {
			\Session::flash('tostError', 'You need to pay minimum $'.env('PAYPAL_MINIMUM_PAY_AMOUNT').' from Paypal.');
			return redirect()->route('buyer_orders_details',$order->order_no);
		}
		/* end : check for paypal deduction is not less than 1 */

		$invoice_id = $orderObj->generate_orderno();
		Session::put('invoice_id',$invoice_id);

		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = "Upgrade Order #".$invoice_id;
		$request_data['return_url'] = route('paypal_express_checkout_upgrade_order_success');
		$request_data['cancel_url'] = route('buyer_orders_details',$order->order_no);
		$request_data['total'] = ($totalAmount - $totalDeduction) + $processing_fee;

		try{

			$options = [
			    'SOLUTIONTYPE' => 'Sole',
			];
			$response = $this->provider->addOptions($options)->setExpressCheckout($request_data);
			/*Create Log for payment request data*/
			$log = new PaymentLog;
			$log->user_id = $user->id;
			$log->receipt = json_encode($response);
			$log->status = "Request data";
			$log->payment_for = "upgrade_order";
			$log->save();

		}catch(\Exception $e){
			return redirect()->route('buyer_orders_details',$order->order_no)->with('errorFails', 'Something went wrong with PayPal');
		}

		if (!$response['paypal_link']) {
			return redirect()->route('buyer_orders_details',$order->order_no)->with('errorFails', 'Something went wrong with PayPal');
		}else{
			Session::put('paypal_custom_data',$request->all());
			return redirect($response['paypal_link']);
		}
	}
	
	public function expressCheckoutUpgradeOrderSuccess(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('errorFails', get_user_softban_message());
		}

		$user =$this->auth_user;
		$token = $request->get('token');
		$PayerID = $request->get('PayerID');
		$invoice_id = Session::get('invoice_id');

		$paypal_custom_data = Session::get('paypal_custom_data');
		
		$order = Order::upgradeorderstatus()->where('order_no',$paypal_custom_data['order_no'])->where('uid',$user->id)->first();
        if(is_null($order)) {
            return redirect()->back();
        }
		$selected_plan = ServicePlan::where('id',$paypal_custom_data['plan_id'])->first();
		$payable = ($selected_plan->price * $order->qty) - ($order->price * $order->qty);

		$is_from_wallet = $paypal_custom_data['is_from_wallet'];
		$is_from_promotional = $paypal_custom_data['is_from_promotional'];

		if($is_from_wallet == 1){
			$fromWalletAmount = $user->earning;
		}else{
			$fromWalletAmount = 0;
		}

		if($is_from_promotional == 1){
			$fromPromotionalAmount = $user->promotional_fund;
		}else{
			$fromPromotionalAmount = 0;
		}

		if ($fromPromotionalAmount > 0) {
			$fromPromotionalAmount = $user->promotional_fund;
		}

		if ($fromWalletAmount > 0) {
			if($user->earning >= $payable){
				$fromWalletAmount = $payable;
			}else{
				$fromWalletAmount = $user->earning;
			}
		}

		$totalAmount = number_format($payable, 2, '.', '');
		$totalDeduction = $fromWalletAmount + $fromPromotionalAmount;
		
		$temp_wallet = [];
		if ($fromWalletAmount > 0) {
			$temp_wallet['name'] = "From wallet";
			$temp_wallet['price'] = "-" . round_price($fromWalletAmount);
			$temp_wallet['qty'] = 1;
		}
		$temp_promo = [];
		if ($fromPromotionalAmount > 0) {
			$temp_promo['name'] = "From demo Bucks";
			$temp_promo['price'] = "-" . round_price($fromPromotionalAmount);
			$temp_promo['qty'] = 1;
		}
		$request_data = [];
		$request_data['items'] = [
			[
				'name' => ucwords($order->service->title),
				'price' => $totalAmount,
				'qty' => 1
			]
		];
		if(count($temp_wallet) > 0) {
			array_push($request_data['items'],$temp_wallet);
		}
		if(count($temp_promo) > 0) {
			array_push($request_data['items'],$temp_promo);
		}

		/* apply processing fee - start */
		$temp_fee = [];
		$processing_fee = calculate_payment_processing_fee($totalAmount- $totalDeduction);
		$temp_fee['name'] = "Payment Processing Fee";
		$temp_fee['price'] = $processing_fee;
		$temp_fee['qty'] = 1;

		array_push($request_data['items'],$temp_fee);
		/* apply processing fee - end */

		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = "Upgrade Order #".$invoice_id;
		$request_data['return_url'] = route('paypal_express_checkout_upgrade_order_success');
		$request_data['cancel_url'] = route('buyer_orders_details',$order->order_no);
		$request_data['total'] = ($totalAmount - $totalDeduction) + $processing_fee;
		
		$response = $this->provider->getExpressCheckoutDetails($token);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($response);
		$log->status = "Payment response";
		$log->payment_for = "upgrade_order";
		$log->save();

		if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
			return redirect()->route('buyer_orders_details',$order->order_no)->with('errorFails', 'Error processing PayPal paymen');
		}

		$payment_status = $this->provider->doExpressCheckoutPayment($request_data, $token, $PayerID);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($payment_status);
		$log->status = "Payment response verification";
		$log->payment_for = "upgrade_order";
		$log->save();

		if ($payment_status['ACK'] == 'Failure') {
			$this->send_failed_notification($user->id);
			return redirect()->route('buyer_orders_details',$order->order_no)->with('errorFails', 'Error processing PayPal payment');
		}

		$status = $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'];
		$txn_id = $payment_status['PAYMENTINFO_0_TRANSACTIONID'];

		Session::forget('invoice_id');
		Session::forget('paypal_custom_data');

		if ($status == 'Completed') {
			$this->upgradeOrder($order,$selected_plan,$payable,$txn_id,$payBy='paypal',$fromPromotionalAmount,$fromWalletAmount,null,$status,null);

			return redirect()->route('buyer_orders_details',$order->order_no)->with('errorSuccess', 'Order upgraded successfully');
		} else {
			return redirect()->route('buyer_orders_details',$order->order_no)->with('error_msg', 'Error processing PayPal payment');
		}
	}

	public function upgradeOrder($order,$selected_plan,$payable,$txn_id,$payBy='wallet',$used_promotional,$fromWalletAmount,$bluesnapTempData = null,$payment_status='Completed',$receipt=null,$from_ipn=false) {

		if($from_ipn == true){
			$uid = $bluesnapTempData->user_id;
			$loginUser = User::find($uid);
		}else{
			$loginUser = $this->auth_user;
			$uid = $loginUser->id;
		}
		$total_days = $selected_plan->delivery_days;
		
		/* clone order for backup */
		$clone = $order;
		$clone = $clone->toArray();
		$clone['order_id'] = $clone['id'];
		unset($clone['id']);
		unset($clone['secret']);
		if (array_key_exists("service",$clone))
		{
			unset($clone['service']);
		}
		if (array_key_exists("plan",$clone))
		{
			unset($clone['plan']);
		}
		TempOrder::insert($clone);

		/* store upgrade history */
		$upgrade_history = new OrderUpgradeHistory;
		$upgrade_history->order_id = $order->id;
		$upgrade_history->service_id = $order->service_id;
		$upgrade_history->previous_plan = $order->plan_type;
		$upgrade_history->upgraded_plan = $selected_plan->plan_type;
		$upgrade_history->previous_amount = $order->price;
		$upgrade_history->upgraded_amount = $selected_plan->price;
		$upgrade_history->payable_amount = $payable;
		$upgrade_history->payment_by = $payBy;
		$upgrade_history->txn_id = $txn_id;
		$upgrade_history->payment_status = $payment_status;
		$upgrade_history->receipt = $receipt;
		$upgrade_history->used_promotional_fund = $order->id;
		if($payable < $used_promotional) {
			$upgrade_history->used_promotional_fund = $payable;
		} else {
			$upgrade_history->used_promotional_fund = $used_promotional;
		}
		$upgrade_history->save();

		/*update Orders*/
		$order->plan_type = $upgrade_history->upgraded_plan;
		$order->package_name = $selected_plan->package_name;
		$order->delivery_days = $selected_plan->delivery_days;
		$order->price = $selected_plan->price;
		if($payable < $used_promotional) {
			$order->used_promotional_fund = $order->used_promotional_fund + $payable;
		} else {
			$order->used_promotional_fund = $order->used_promotional_fund + $used_promotional;
		}
		$order->end_date = Carbon::parse($order->start_date)->addDays($total_days)->toAtomString();
		$order->order_total_amount = $this->calculate_amount($order);
		$order->save();

		$extra_product_price = 0;
		if (isset($order->extra)) {
			foreach ($order->extra as $extra) {
				$extra_product_price += $extra->service_extra->price * $extra->qty;
			}
		}

		$product_price = ($selected_plan->price * $order->qty) + $extra_product_price - round_price($order->reorder_discount_amount) - round_price($order->coupon_discount) - round_price($order->volume_discount) - round_price($order->combo_discount);

		$product_price = round_price($product_price);
		$product_service_charge = get_service_change($product_price,$order->is_new);

		/*For special seller*/
		if($order->seller->is_special_seller == 1){
			$product_service_charge = 0;
		}
		$affiliate_per = 15;
		$specialAffiliatedUser = Specialaffiliatedusers::where('uid', $order->affiliate_id)->first();
		if ($specialAffiliatedUser != null) {
			$affiliate_per = 25;
			$product_service_charge = 0;
		}

		/* Buyer Transactions Start */
		$buyerTransaction = new BuyerTransaction;
		$buyerTransaction->order_id = $order->id;
		$buyerTransaction->buyer_id = $order->uid;
		if($payBy == 'paypal'){
			$buyerTransaction->note = 'Debit from Credit Card/Paypal';
			if($fromWalletAmount > 0 && $used_promotional > 0){
				$buyerTransaction->paypal_amount = ($payable  - $used_promotional - $fromWalletAmount);
				$buyerTransaction->wallet_amount = $fromWalletAmount;
				$buyerTransaction->promotional_amount = $used_promotional;
			}else if($used_promotional > 0){
				$buyerTransaction->paypal_amount = ($payable  - $used_promotional);
				$buyerTransaction->promotional_amount = $used_promotional;
			}else if($fromWalletAmount > 0){
				$buyerTransaction->paypal_amount = ($payable  - $fromWalletAmount);
				$buyerTransaction->wallet_amount = $fromWalletAmount;
			}else{
				$buyerTransaction->paypal_amount = $payable;
			}
			$buyerTransaction->payment_processing_fee = calculate_payment_processing_fee($buyerTransaction->paypal_amount);
		}
		elseif($payBy == 'skrill'){
			$buyerTransaction->note = 'Debit from Skrill';
			if($fromWalletAmount > 0 && $used_promotional > 0){
				$buyerTransaction->skrill_amount = ($payable  - $used_promotional - $fromWalletAmount);
				$buyerTransaction->wallet_amount = $fromWalletAmount;
				$buyerTransaction->promotional_amount = $used_promotional;
			}else if($used_promotional > 0){
				$buyerTransaction->skrill_amount = ($payable  - $used_promotional);
				$buyerTransaction->promotional_amount = $used_promotional;
			}else if($fromWalletAmount > 0){
				$buyerTransaction->skrill_amount = ($payable  - $fromWalletAmount);
				$buyerTransaction->wallet_amount = $fromWalletAmount;
			}else{
				$buyerTransaction->skrill_amount = $payable;
			}
			$buyerTransaction->payment_processing_fee = calculate_payment_processing_fee($buyerTransaction->paypal_amount);
		}
		elseif ($payBy == 'bluesnap'){
			$buyerTransaction->note = 'Debit from Credit Card';
			$buyerTransaction->creditcard_amount = $payable;
			$buyerTransaction->payment_processing_fee = calculate_payment_processing_fee($buyerTransaction->creditcard_amount);
		}
		else{
			$buyerTransaction->note = 'Debit from Wallet';
		}
		
		$buyerTransaction->anount = $payable;
		$buyerTransaction->status = 'deposit';
		$buyerTransaction->created_at = time();
		$buyerTransaction->save();
		/* Buyer Transactions End */

		/* Seller Earnings Start */
		$SellerEarning = SellerEarning::where('order_id',$order->id)->first();
		if(!is_null($SellerEarning)) {
			$SellerEarning->anount = $SellerEarning->anount + $payable;
			if ($order->is_affiliate == "1") {
				$total_main_amount = $product_price - $product_service_charge;
				$SellerEarning->anount = $total_main_amount - (($product_price * $affiliate_per) / 100);
			} else {
				$SellerEarning->anount = $product_price - $product_service_charge;
			}
			$SellerEarning->save();
		}
		/* Seller Earnings End */

		/* Affiliate Earnings End */
		if ($order->is_affiliate == "1") {
			$affiliate_earning = AffiliateEarning::where('order_id',$order->id)->first();
			if(!is_null($affiliate_earning)) {
				if($affiliate_earning->affiliate_type == 'influencer') {
					$affiliate_earning->amount = ($product_price * 15) / 100; //15% for influencer
				} else {
					$affiliate_earning->amount = ($product_price * $affiliate_per) / 100; //for demo
				}
				$affiliate_earning->save();
			}
		}

		/* Update buyer wallet (when pay by wallet and paypal)*/
		if($fromWalletAmount > 0){
			$buyer = User::find($uid);
			$buyer->earning = $buyer->earning - $fromWalletAmount;
			$buyer->pay_from_wallet = $buyer->pay_from_wallet + $fromWalletAmount;
			$buyer->save();
		}
		/* Update buyer promotional fund (when pay by promotional fund and paypal)*/
		if($used_promotional > 0){
			$buyer = User::find($uid);
			$buyer->promotional_fund = $buyer->promotional_fund - $used_promotional;
			$buyer->save();

			/* create promotional transaction history */
			$promotional_transaction = new UserPromotionalFundTransaction;
			$promotional_transaction->user_id = $uid;
			$promotional_transaction->order_id = $order->id;
			$promotional_transaction->amount = $used_promotional;
			$promotional_transaction->type = 0; //type - service
			$promotional_transaction->transaction_type = 0; //type - deduct
			$promotional_transaction->save();
		}

		/* Send wallet transaction email to admin */
		if($payBy == "wallet" || $payBy == "promotional"){
			$wallet_transaction_history['buyer'] = $order->user->username;
			$wallet_transaction_history['buyer_email'] = $order->user->email;
			$wallet_transaction_history['seller'] = $order->seller->username;
			$wallet_transaction_history['seller_email'] = $order->seller->email;
			$wallet_transaction_history['invoice_id'] = $order->order_no;
			$wallet_transaction_history['transaction_id'] = $order->txn_id;
			$wallet_transaction_history['total_amount'] = round($payable,2);
			$wallet_transaction_history['reason'] = "upgrade a order";
			$wallet_transaction_history['transactions'][] = [
				'title' => "Upgrade order #{$order->order_no}",
				'price' => $payable,
				'quantity' 	=> 1,
				'total' => round($payable,2)
			];
			$order->sendWalletTransactionEmail($wallet_transaction_history);
		}
		return 1;
	}

	function refundUpgradeOrderAmount($Order,$User,$wallet_amount,$promotional_amount,$total_payable) {
		if($promotional_amount > 0) {
			$User->promotional_fund = $User->promotional_fund + $promotional_amount;
			/* create promotional transaction history */
			$promotional_transaction = new UserPromotionalFundTransaction;
			$promotional_transaction->user_id = $User->id;
			$promotional_transaction->order_id = $Order->id;
			$promotional_transaction->amount = $promotional_amount;
			$promotional_transaction->type = 0; //type - service
			$promotional_transaction->transaction_type = 1; //transaction_type - credit
			$promotional_transaction->save();
		}
		if($wallet_amount > 0) {
			$User->earning = $User->earning + $wallet_amount;
		}
		$User->save();

		/* Buyer Transactions Start */
		$buyerTransaction = new BuyerTransaction;
		$buyerTransaction->order_id = $Order->id;
		$buyerTransaction->buyer_id = $Order->uid;
		$buyerTransaction->note = 'Cancelled Order';
		$buyerTransaction->anount = $total_payable;
		$buyerTransaction->status = 'cancelled';
		$buyerTransaction->credit_to = 'wallet';
		$buyerTransaction->created_at = time();
		$buyerTransaction->save();
		/* Buyer Transactions End */
		return true;
	}

	public function ccDepositeAmtRefund(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('transactions')->with('errorFails', get_user_softban_message());
		}

		if($request->ajax()){
			$txnId = $request->txnId;
			$buyer = $this->auth_user;
			if(trim($txnId) == ''){
				return response()->json(['success'=>false,'message'=>'Transaction ID not found.']);
			}
			//Check transaction 
			$id = BuyerTransaction::getDecryptedId($request->secret);
			try{
				if(empty($id)){
					return response()->json(['success'=>false,'message'=>'Something goes wrong.']);
				}
			}catch(\Exception $e){
				return response()->json(['success'=>false,'message'=>'Something goes wrong.']);
			}

			$buyerTransaction = BuyerTransaction::select('id','transaction_id','creditcard_amount')
			->where('transaction_id',$txnId)
			->where('status','add_money_to_wallet')
			->where('creditcard_amount','>',0)
			->where('anount','<=',$buyer->cc_earning)
			->where('buyer_id',$buyer->id)
			->where('cc_refund_status',0)
			->where('id',$id)->first();
			
			if(empty($buyerTransaction)){
				return response()->json(['success'=>false,'message'=>'You have not sufficient amount to refund.']);
			}

			//Refund amount to credit card
			$bluesnapControllerObj = new BluesnapPaymentController();
			$response = $bluesnapControllerObj->refundTransaction($buyerTransaction->transaction_id ,$buyerTransaction->creditcard_amount);
			
			if ($response != "") 
			{
				$buyerTransaction->cc_refund_status = '1';
				$buyerTransaction->save();
				\Session::flash('errorSuccess', "Requested refund amount will be credited to your credit card shortly.");
			}else{
				$buyerTransaction->cc_refund_status = '2';
				$buyerTransaction->save();

				$buyer->earning = $buyer->earning - $buyerTransaction->creditcard_amount;
				$buyer->save();

				\Session::flash('errorSuccess', "Requested refund amount credited to your credit card.");

			}
			return response()->json(['success'=>true,'message'=>'']);
			
		}else{
			return redirect()->back();
		}
	}

	/*Extras payment from Wallet / Promotional*/
	public function expressCheckoutExtrasPaynow(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('buyer_orders')->with('errorFails', get_user_softban_message());
		}

		$user =$this->auth_user;

		/*Get Request parameter from session*/
		$extras_request_data = (object)Session::get('extras_purchased_data');
		Session::forget('extras_purchased_data');

		if(is_null($extras_request_data)){
			\Session::flash('tostError', 'Session timeout. Please try again.');
			return redirect('/');
		}
		
		$request->merge([
			'service_id' => $extras_request_data->service_id,
			'order_id' => $extras_request_data->order_id,
		]);

		$from_wallet = $request->is_from_wallet;
		$from_promotional = $request->is_from_promotional;
		$totalAmountToCheck = $extras_request_data->final_total;
		$extra = $extras_request_data->extra;

		$order = Order::withoutGlobalScope('parent_id')->find($request->order_id);

		/*Validate for 100% dicount*/
		if($totalAmountToCheck < 0){
			\Session::flash('errorFails', 'Incorrect amount to pay.');
			return redirect()->route('buyer_orders_details',[$order->order_no]);
		}

		/*begin : Make payment form wallet + promotional*/
		if($from_wallet == 1 && $from_promotional == 1){
			if( $totalAmountToCheck <= ($user->earning + $user->promotional_fund)) {
				if($user->promotional_fund >= $totalAmountToCheck){
					$used_promotional = $totalAmountToCheck;
				}else{
					$used_promotional = $user->promotional_fund;
				}
				$used_wallet_amount = $totalAmountToCheck - $used_promotional;
				/* extend extra on order */
				$response = $this->createExtrasOrder($request->order_id,$extra,$user->id,$txn_id=null,$payBy='wallet',$used_promotional,$used_wallet_amount);
				if($response == 0){
					return redirect()->route('buyer_orders_details',$order->order_no)->with('tostError','Something went wrong.');
				}
				return redirect()->route('buyer_orders_details',$order->order_no)->with('tostSuccess','Extra added your order successfully');
			}else{
				\Session::flash('errorFails', 'You have not sufficient amount in your wallet and promotional funds');
				return redirect()->route('buyer_orders_details',$order->order_no);
			}
		}
		/*end : Make payment form wallet + promotional*/
		/*begin : Make payment form promotional*/
		else if($from_promotional == 1){
			if( $totalAmountToCheck <= $user->promotional_fund){
				if($user->promotional_fund >= $totalAmountToCheck){
					$used_promotional = $totalAmountToCheck;
				}else{
					$used_promotional = $user->promotional_fund;
				}
				$used_wallet_amount = 0;
				/* extend extra on order */
				$response = $this->createExtrasOrder($request->order_id,$extra,$user->id,$txn_id=null,$payBy='promotional',$used_promotional,$used_wallet_amount);
				if($response == 0){
					return redirect()->route('buyer_orders_details',$order->order_no)->with('tostError','Something went wrong.');
				}
				return redirect()->route('buyer_orders_details',$order->order_no)->with('tostSuccess','Extra added your order successfully');
			}else{
				\Session::flash('errorFails', 'You have not sufficient amount in your promotional funds');
				return redirect()->route('buyer_orders_details',$order->order_no);
			}
		}
		/*end : Make payment form promotional*/
		
		/*begin : Make payment form wallet*/
		else if($from_wallet == 1){
			if( $totalAmountToCheck <= $user->earning){
				$used_promotional = 0;
				$used_wallet_amount = $totalAmountToCheck;
				/* extend extra on order */
				$response = $this->createExtrasOrder($request->order_id,$extra,$user->id,$txn_id=null,$payBy='wallet',$used_promotional,$used_wallet_amount);
				if($response == 0){
					return redirect()->route('buyer_orders_details',$order->order_no)->with('tostError','Something went wrong.');
				}
				return redirect()->route('buyer_orders_details',$order->order_no)->with('tostSuccess','Extra added your order successfully');
			}else{
				\Session::flash('errorFails', 'You have not sufficient amount in your wallet');
				return redirect()->route('buyer_orders_details',$order->order_no);
			}
		}
		/*end : Make payment form wallet*/
	}
	/*Extras payment from Paypal*/
	public function expressCheckoutExtrasPaypal(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('buyer_orders')->with('errorFails', get_user_softban_message());
		}

		$user =$this->auth_user;

		/*Get Request parameter from session*/
		$extras_request_data = (object)Session::get('extras_purchased_data');
		
		$from_wallet = $request->is_from_wallet;
		$from_promotional = $request->is_from_promotional;
		$totalAmountToCheck = $extras_request_data->final_total;
		$extra = $extras_request_data->extra;
		$extras_request_data->is_from_wallet = $request->is_from_wallet;
		$extras_request_data->is_from_promotional = $request->is_from_promotional;

		$order = Order::withoutGlobalScope('parent_id')->find($extras_request_data->order_id);

		if($from_wallet == 1){
			$fromWalletAmount = $user->earning;
		}else{
			$fromWalletAmount = 0;
		}

		if($from_promotional == 1){
			$fromPromotionalAmount = $user->promotional_fund;
		}else{
			$fromPromotionalAmount = 0;
		}

		$processing_fee = calculate_payment_processing_fee($totalAmountToCheck);
		$payable_amount = ($processing_fee + $totalAmountToCheck) - $fromWalletAmount - $fromPromotionalAmount;

		$baseAmount = number_format($totalAmountToCheck, 2, '.', '');
		$request_data = $items_arr = [];

		$key = 0;
		$items_arr[$key]['name'] = 'Order Extra Payment #'.$order->order_no;
		$items_arr[$key]['price'] = $baseAmount;
		
		$key++;
		$items_arr[$key]['name'] = "Payment Processing Fee";
		$items_arr[$key]['price'] = $processing_fee;

		if ($fromWalletAmount > 0) {
			$key++;
			$items_arr[$key]['name'] = "From wallet";
			$items_arr[$key]['price'] = "-" . round_price($fromWalletAmount);
		}

		if ($fromPromotionalAmount > 0) {
			$key++;
			$items_arr[$key]['name'] = "From promotional";
			$items_arr[$key]['price'] = "-" . round_price($fromPromotionalAmount);
		}

		$invoice_id = "REF" . get_microtime() . $order->id;
		$request_data = [
			'items' => $items_arr,
			'return_url' => route('checkout.extras.paypal.success'),
			'invoice_id' => $invoice_id,
			'invoice_description' => "Invoice #" . $invoice_id,
			'cancel_url' => route('buyer_orders_details',$order->order_no),
			'extra_total' => round_price($payable_amount),
			'from_wallet' => round_price($fromWalletAmount),
			'from_demo_bucks' => round_price($fromPromotionalAmount),
			'processing_fee' => round_price($processing_fee),
			'total' => round_price($payable_amount),
			'currency' => 'USD'
		];
		
		try{

			$options = [
			    'SOLUTIONTYPE' => 'Sole',
			];
			$response = $this->provider->addOptions($options)->setExpressCheckout($request_data);
			/*Create Log for payment request data*/
			$log = new PaymentLog;
			$log->user_id = $user->id;
			$log->receipt = json_encode($response);
			$log->status = "Request data";
			$log->payment_for = "extras";
			$log->save();

		}catch(\Exception $e){
			return redirect()->route('buyer_orders_details',$order->order_no)->with('tostError','Something went wrong with PayPal.');
		}

		if (!$response['paypal_link']) {
			return redirect()->route('buyer_orders_details',$order->order_no)->with('tostError','Something went wrong with PayPal');
		}else{
			Session::put('extras_purchased_data',$extras_request_data);
			return redirect($response['paypal_link']);
		}

	}
	/*Extras payment from Paypal Success*/
	public function expressCheckoutPaypalSuccess(Request $request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}
		
		$user =$this->auth_user;
		$token = $request->get('token');
		$PayerID = $request->get('PayerID');
		
		$paypal_custom_data = (object)Session::get('extras_purchased_data');
		
		$order = Order::withoutGlobalScope('parent_id')->find($paypal_custom_data->order_id);

		$extra = $paypal_custom_data->extra;
		$from_wallet = $paypal_custom_data->is_from_wallet;
		$from_promotional = $paypal_custom_data->is_from_promotional;
		$total_amount = $paypal_custom_data->final_total;

		$response = $this->provider->getExpressCheckoutDetails($token);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($response);
		$log->status = "Payment response";
		$log->payment_for = "extras";
		$log->save();

		if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
			return redirect()->route('buyer_orders_details',$order->order_no)->with('tostError','Error processing PayPal payment');
		}

		/*Validate for 100% dicount*/
		if($total_amount < 0){
			\Session::flash('tostError', 'Incorrect amount to pay.');
			return redirect()->route('buyer_orders_details',$order->order_no);
		}

		if($from_wallet == 1){
			$fromWalletAmount = $user->earning;
		}else{
			$fromWalletAmount = 0;
		}

		if($from_promotional == 1){
			$fromPromotionalAmount = $user->promotional_fund;
		}else{
			$fromPromotionalAmount = 0;
		}

		/*begin : check wallet amount*/
		if($fromWalletAmount > 0 && $fromPromotionalAmount > 0){
			if($fromWalletAmount > $user->earning || $fromPromotionalAmount > $user->promotional_fund){
				\Session::flash('tostError', 'You have not sufficient amount in your wallet and promotional fund.');
				return redirect()->route('buyer_orders_details',$order->order_no);
			}
		}else if($fromWalletAmount > 0){
			if($fromWalletAmount > $user->earning){
				\Session::flash('tostError', 'You have not sufficient amount in your wallet.');
				return redirect()->route('buyer_orders_details',$order->order_no);
			}
		}
		else if($fromPromotionalAmount > 0){
			if($fromPromotionalAmount > $user->promotional_fund){
				\Session::flash('tostError', 'You have not sufficient amount in your promotional fund.');
				return redirect()->route('buyer_orders_details',$order->order_no);
			}
		}
		/*end : check wallet amount*/

		$processing_fee = calculate_payment_processing_fee($total_amount);
		//Payable amount from paypal
		$payable_amount = ($processing_fee + $total_amount) - $fromWalletAmount - $fromPromotionalAmount;

		if($response['AMT'] != round($payable_amount,2)){
            $this->send_failed_notification($user->id);
			\Session::flash('tostError', 'Requested amount and payable amount is not match.');
			return redirect()->route('buyer_orders_details',$order->order_no);
		}

		$baseAmount = number_format($total_amount, 2, '.', '');
		$request_data = $items_arr = [];

		$key = 0;
		$items_arr[$key]['name'] = 'Extra Payment';
		$items_arr[$key]['price'] = $baseAmount;
		
		$key++;
		$items_arr[$key]['name'] = "Payment Processing Fee";
		$items_arr[$key]['price'] = $processing_fee;

		if ($fromWalletAmount > 0) {
			$key++;
			$items_arr[$key]['name'] = "From wallet";
			$items_arr[$key]['price'] = "-" . round_price($fromWalletAmount);
		}

		if ($fromPromotionalAmount > 0) {
			$key++;
			$items_arr[$key]['name'] = "From promotional";
			$items_arr[$key]['price'] = "-" . round_price($fromPromotionalAmount);
		}

		$invoice_id = $response['INVNUM'];
		$request_data = [
			'items' => $items_arr,
			'return_url' => route('checkout.extras.paypal.success'),
			'invoice_id' => $invoice_id,
			'invoice_description' => "Invoice #" . $invoice_id,
			'cancel_url' => route('buyer_orders_details',$order->order_no),
			'extra_total' => round_price($payable_amount),
			'from_wallet' => round_price($fromWalletAmount),
			'from_demo_bucks' => round_price($fromPromotionalAmount),
			'processing_fee' => round_price($processing_fee),
			'total' => round_price($payable_amount),
			'currency' => 'USD'
		];

		$payment_status = $this->provider->doExpressCheckoutPayment($request_data, $token, $PayerID);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $user->id;
		$log->receipt = json_encode($payment_status);
		$log->status = "Payment response verification";
		$log->payment_for = "extras";
		$log->save();

		if ($payment_status['ACK'] == 'Failure') {
			$this->send_failed_notification($user->id);
			Session::flash('tostError', 'Something went wrong with PayPal');
			return redirect()->route('buyer_orders_details',$order->order_no);
		}

		$status = $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'];
		$txn_id = $payment_status['PAYMENTINFO_0_TRANSACTIONID'];

		if ($status == 'Completed') {
			Session::forget('extras_purchased_data');
			$used_promotional = $fromPromotionalAmount;
			$used_wallet_amount = $fromWalletAmount;
			$response = $this->createExtrasOrder($order->id,$extra,$user->id,$txn_id,$payBy='paypal',$used_promotional,$used_wallet_amount);
			if($response == 0){
				return redirect()->route('buyer_orders_details',$order->order_no)->with('tostError','Something went wrong.');
			}
			return redirect()->route('buyer_orders_details',$order->order_no)->with('tostSuccess','Extra added your order successfully');
		}else{
			return redirect()->route('buyer_orders_details',$order->order_no)->with('tostError','Error processing PayPal payment.');
		}
	}
	/* Create extras order */
	public function createExtrasOrder($order_id,$extra,$user_id,$txn_id,$payment_by = 'paypal',$used_promotional=0,$used_wallet_amount=0){

		$order = Order::withoutGlobalScope('parent_id')->find($order_id);
		if (is_null($order)) {
			return 0;
		}

		$buyer = User::find($user_id);

		/*Create order slot*/
		$total_days = $totalAmount = 0;
		foreach ($extra as $key => $value) {
			$value = (object)$value;
			$serviceExtra = ServiceExtra::find($value->id);	
			$quantity = $value->quantity;
			$price = $value->price;

			$orderExtra = new OrderExtra;
			$orderExtra->order_id = $order->id;
			$orderExtra->service_id = $order->service_id;
			$orderExtra->title = $serviceExtra->title;
			$orderExtra->description = $serviceExtra->description;
			$orderExtra->delivery_days = $serviceExtra->delivery_days;
			$orderExtra->price = $price;
			$orderExtra->qty = $quantity;
			$orderExtra->save();

			$total_days = $total_days + ($serviceExtra->delivery_days * $quantity);
			$totalAmount = $totalAmount + ($quantity * $price);
		}

		$buyerTransaction = new BuyerTransaction;
		$buyerTransaction->order_id = $order->id;
		$buyerTransaction->buyer_id = $buyer->id;
		$buyerTransaction->anount = $totalAmount;
		$buyerTransaction->transaction_id = $txn_id;

		if ($payment_by == 'wallet' || $payment_by == 'promotional') {
			$buyerTransaction->note = 'Debit from Wallet';
			$buyerTransaction->wallet_amount = $used_wallet_amount;
			$buyerTransaction->promotional_amount = $used_promotional;
		}elseif($payment_by == 'paypal'){
			$buyerTransaction->note = 'Debit from Credit Card/Paypal';
			$buyerTransaction->wallet_amount = $used_wallet_amount;
			$buyerTransaction->promotional_amount = $used_promotional;
			$buyerTransaction->paypal_amount = $totalAmount - ($used_promotional + $used_wallet_amount);
			$buyerTransaction->payment_processing_fee = calculate_payment_processing_fee($totalAmount);
		}elseif($payment_by == 'bluesnap'){
			$buyerTransaction->note = 'Debit from Credit Card';
			$buyerTransaction->creditcard_amount = $totalAmount;
			$buyerTransaction->payment_processing_fee = calculate_payment_processing_fee($totalAmount);
		}elseif($payment_by == 'skrill'){
			$buyerTransaction->note = 'Debit from Skrill';
			$buyerTransaction->wallet_amount = $used_wallet_amount;
			$buyerTransaction->promotional_amount = $used_promotional;
			$buyerTransaction->skrill_amount = $totalAmount - ($used_promotional + $used_wallet_amount);
			$buyerTransaction->payment_processing_fee = calculate_payment_processing_fee($totalAmount);
		}
		
		$buyerTransaction->status = "deposit_extra";
		$buyerTransaction->save();


		/* Update the buyer wallet */
		if ($payment_by == 'wallet') {
			if($used_promotional > 0) {
				$buyer->promotional_fund = $buyer->promotional_fund - $used_promotional;
				$buyer->earning = $buyer->earning - ($totalAmount - $used_promotional);
			} else {
				$buyer->earning = $buyer->earning - $totalAmount;
			}
			$buyer->save();
		}

		if ($payment_by == 'promotional') {
			$buyer->promotional_fund = $buyer->promotional_fund - $totalAmount;
			$buyer->save();
		}

		if ($payment_by == 'paypal' || $payment_by == 'skrill') {
			if($used_promotional > 0) {
				$buyer->promotional_fund = $buyer->promotional_fund - $used_promotional;
				$buyer->save();
			}
			if($used_wallet_amount > 0) {
				$buyer->earning = $buyer->earning - $used_wallet_amount;
				$buyer->save();
			}
		}

		if($used_promotional > 0) {
			/* create promotional transaction history */
			$promotional_transaction = new UserPromotionalFundTransaction;
			$promotional_transaction->user_id = $buyer->id;
			$promotional_transaction->order_id = $order->id;
			$promotional_transaction->amount = $used_promotional;
			$promotional_transaction->type = 0; //type - service
			$promotional_transaction->transaction_type = 0; //type - deduct
			$promotional_transaction->save();
		}

		/* calculate total amount */
		$old_total_amount = $order->order_total_amount;
		$order->order_total_amount = $this->calculate_amount($order);
		$product_price = $order->order_total_amount;
		
		/* begin : get admin service charge */
		$product_service_charge = get_service_change($product_price,$order->is_new);
		/* end : get admin service charge */

		/* Save order data */
		$order->service_charge = $product_service_charge;
		$order->status = 'active';
		$order->delivered_date = null;
		$order->end_date = Carbon::parse($order->end_date)->addDays($total_days);
		$order->save();
		
		/* Create Logs */
		$tracker = new TrackOrderChange;
		$tracker->order_id  = $order->id;
		$tracker->column_key = 'order_total_amount';
		$tracker->old_value = $old_total_amount;
		$tracker->new_value = $order->order_total_amount;
		$tracker->updated_by_role = 'user';
		$tracker->updated_by = $buyer->id;
		$tracker->extra_note = 'Buyer purchased extras';
		$tracker->save();

		/* Affiliate Earnings End */
		$affiliate_per = 0;
		if ($order->is_affiliate == "1") {
			$affiliate_earning = AffiliateEarning::where('order_id',$order->id)->where('seller_id',$order->seller_uid)->where('status','pending_clearance')->first();
			if(!is_null($affiliate_earning)){
				$affiliate_per = get_affiliation_percentage($old_total_amount,$affiliate_earning->amount);
				$affiliate_earning->amount = ($product_price * $affiliate_per) / 100;
				$affiliate_earning->save();
			}
		}

		/* Seller Earnings Start */
		$SellerEarning = SellerEarning::where('order_id',$order->id)->where('seller_id',$order->seller_uid)->where('status','pending_clearance')->first();
		if(!is_null($SellerEarning)){
			if ($order->is_affiliate == "1" && $affiliate_per > 0) {
				$total_main_amount = $product_price - $product_service_charge;
				$SellerEarning->anount = $total_main_amount - (($product_price * $affiliate_per) / 100);
			} else {
				$SellerEarning->anount = $product_price - $product_service_charge;
			}
			$SellerEarning->save();
		}
		/* Seller Earnings End */
		$seller = User::select('id','username','email')->find($order->seller_uid);
		/* Send Email to seller */
		$data = [
			'receiver_secret' => $seller->secret,
			'email_type' => 1,
			'subject' => 'You Have New Extras Added In Order On demo!',
			'template' => 'frontend.emails.v1.seller_extras_purchased',
			'email_to' => $seller->email,
			'username' => $seller->username,
			'order_no' => $order->order_no,
			'buyer' => $buyer->username
		];
		QueueEmails::dispatch($data, new SendEmailInQueue($data));

		return 1;
	}
}
