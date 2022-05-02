<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Service;
use App\ServicePlan;
use App\Cart;
use App\CartExtra;
use App\CartCombo;
use App\CartExtraCombo;
use App\Order;
use App\OrderExtra;
use App\ServiceExtra;
use App\PaymentLog;
use App\BuyerTransaction;
use App\SellerEarning;
use App\Notification;
use App\User;
use App\EmailTemplate;
use Srmklive\PayPal\Services\AdaptivePayments;
use Srmklive\PayPal\Services\ExpressCheckout;
use Cookie;
use Auth;
use App\AffiliateEarning;
use App\BuyerReorderPromo;
use App\Specialaffiliatedusers;
use App\Withdraw;
use App\Coupan;
use App\CoupanApplied;
use App\BoostedServicesOrder;
use App\ServiceQuestion;
use AWS;
use Aws\Exception\AwsException;
use Mail;
use App\SellerAnalytic;
use App\DiscountPriority;
use App\BundleService;
use App\BundleDiscount;
use DB;
use App\JobOffer;
use App\Country;
use App\BillingInfo;
use Illuminate\Support\Arr;
use App\UserFile;
use App\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Session;
use App\Influencer;
use App\BoostingPlan;

class NewCartController extends Controller {

	private $uid;
	private $auth_user;

	public function __construct(){
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

	public function view_new_cart(Request $request) {
		if(Auth::check()){

			if(Session::get('submit_re_agreement')){
				Session::forget('submit_re_agreement',1);
			}

			if(Session::get('submit_course_agreement')){
				Session::forget('submit_course_agreement',1);
			}
			if(Session::get('is_direct_checkout')){
				Session::forget('is_direct_checkout','true');
			}
			
			if(User::check_sub_user_permission('can_make_purchases') == false){
				return redirect('/');
			}
			
			$cartAdded = $this->sessionCartAdded($request);
			
			$uid = $this->uid;
			$userObj = new User;
			$cartObj = new Cart;
			
	
			/*Begin : Remove unwanted services*/
			if(!$request->ajax()){
				$cartObj->remove_unwanted_services();
			}
			/*End : Remove unwanted services*/
	
			$cart = Cart::where('uid',$uid)->where('direct_checkout',0)->OrderBy('id','desc')->get();
	
			$cartSubtotal = $selectedExtra = $totalDeliveryTime = $totalDeliveryTimeExtra = $totalPromoDiscount = $addDiscount = 0;  
	
			$is_recurring_service = $is_review_edition = $is_course = 0;
			foreach($cart as $index => $row){
				if($row->service->is_recurring == 1 || $row->plan->plan_type == 'monthly_access'){
					$is_recurring_service = 1;
				}
			}

			if($is_recurring_service == 1) {
				$cart = Cart::get_recurring_from_cart();
			}

			foreach($cart as $index => $row){

				//Check recurring service has include review edition?
				if($row->service->is_recurring == 1 && $row->is_review_edition == 1){
					$row->delete();
				}

				//Update price for review edition
				if($row->is_review_edition == 1){
					$row->plan->price = $row->plan->review_edition_price;

					if($is_recurring_service == 0){
						$is_review_edition = 1;
					}
				}

				if($row->is_course == 1){
					$is_course = 1;
				}

				$cartSubtotal += ($row->plan->price * $row->quantity);
				$totalDeliveryTime +=  $row->plan->delivery_days;
	
				$buyerPromo = null;
				//For review edition service do not apply re-order discount
				if($row->remove_reorder_promo == 0 && $row->is_review_edition == 0){
					$buyerPromo = BuyerReorderPromo::where('seller_id',$row->service->uid)
					->where('buyer_id',$uid)
					->where('service_id',$row->service->id)
					->where('is_used',0)
					->first();
				}
				
				$discount_per = 0;
	
				if(!is_null($buyerPromo)){
					$discount_per = $buyerPromo->amount;
	
					$discount_amount = (($row->plan->price * $row->quantity) * $buyerPromo->amount ) / 100;
	
					$totalPromoDiscount += $discount_amount;
	
					$cart[$index]->reorder_promo_discount = $discount_amount;
					$cart[$index]->reorder_promo_discount_per = $buyerPromo->amount;
				}
	
				$image_url = url('public/frontend/assets/img/No-image-found.jpg');
				if(isset($row->service->images[0])){
					if(!is_null($row->service->images[0]->thumbnail_media_url)) {
						$image_url = $row->service->images[0]->thumbnail_media_url;
					} else if($row->service->images[0]->photo_s3_key != ''){
						$image_url = $row->service->images[0]->media_url; 
					}else{
						$image_url = url('public/services/images/'.$row->service->images[0]->media_url); 
					}
				}
				$cart[$index]->image_url = $image_url;
				
				$added_extra = [];
				$list = [];
				if(sizeof($row->extra) > 0) {
					foreach ($row->extra as $key => $value) {
						array_push($list, $value->service_extra_id);
						$added_extra[$value->service_extra_id] = $value;
					}
				}
				$cart[$index]->cart_extra_ids = $list;
				$cart[$index]->added_extra = $added_extra;
			}
			
			$summary = $this->cart_summary_calculation($cart);

			$can_apply_promo = true;
			if(count($summary['all_services']) == 0 || $summary['final_total'] == 0 || ($is_review_edition == 1 && count($summary['all_services']) == 1)){
				$can_apply_promo = false;
			}

			return view('frontend.new_cart.cart_new', compact('cart','is_recurring_service','buyerPromo','userObj','summary','is_review_edition','can_apply_promo','is_course'));
		}else{
			
			/* Remove deleted extras */
			$cart = Session::get('cart');
			if($cart != ''){
				foreach($cart as $key => $row){
					foreach ($row['extra'] as $extra_key => $extras) {
						$check_extras = ServiceExtra::select('id')->find($extra_key);
						if(!$check_extras){
							unset($cart[$key]['extra'][$extra_key]);
							Session::put('cart', $cart);
						}
					}
				}
			}
			/* END Remove deleted extras */

			$cart = Session::get('cart');
			
			$settings = Setting::find(1);
			$is_recurring_service = 0;
			if($cart != ''){
				foreach($cart as $key => $row){
					if(isset($row['is_review_edition']) && $row['is_review_edition'] == 1 && $row['quantity'] > 1){
						$cart[$key]['quantity'] = 1;
						Session::put('cart', $cart);
					}
					if(isset($row['is_course']) && $row['is_course'] == 1 && $row['quantity'] > 1){
						$cart[$key]['quantity'] = 1;
						Session::put('cart', $cart);
					}
					$service = Service::withoutGlobalScope('is_course')->select('id','is_recurring')->find($row['service_id']);
					if($is_recurring_service == 0 && $service->is_recurring == 1){
						$is_recurring_service = 1;
					}
					if($service->plan->plan_type == 'monthly_access'){
						$is_recurring_service = 1;
					}
				}
			}
			$summary = $this->cart_summary_calculation_without_login($cart,$is_recurring_service);
			return view('frontend.new_cart.cart_session_new', compact('cart','is_recurring_service','buyerPromo','userObj','interestedServices','sponsered_cart','summary','is_custom_order','is_job','fromWalletAmount','settings','fromPromotionalAmount'));
		}
	}

	public function cart_summary_calculation_without_login($Cart,$is_recurring_service) {
		$userObj = new User;
		$cartObj = new CartCombo;

        $cartSubtotal = $selectedExtra = $totalDeliveryTime = $totalDeliveryTimeExtra = $totalPromoDiscount = $totalCoupenDiscount = $totalVolumeDiscount = $totalComboDiscount = 0; 
		
		$all_services = [];

		$discountPriority = DiscountPriority::OrderBy('priority','desc')->get();

		foreach($Cart as $index => $row) {
			
			$service = Service::withoutGlobalScope('is_course')->select('id','title','is_recurring')->find($row['service_id']);
			// if($is_recurring_service == 1 && $service->is_recurring == 0 ){
			// 	continue;
			// }
			$servicePlan = Service::getServicePlan($row['plan_id'],$row['service_id']);
			$all_services[$index]['cart_id'] = $row->id;
			$all_services[$index]['title'] = $service->title;
			$all_services[$index]['delivery_days'] = $servicePlan->delivery_days;

			//Update price for review edition
			if(isset($row['is_review_edition']) && $row['is_review_edition'] == 1){
				$servicePlan->price = $servicePlan->review_edition_price;
			}

			$cartSubtotal += ($servicePlan->price * $row['quantity']);

			$totalDeliveryTime +=  $servicePlan->delivery_days;
			$afterDiscountPrice = $servicePlan->price * $row['quantity'];
			

			$cart_extra_price_total = 0;
			$all_services[$index]['extra'] = [];
			
			foreach($service->extra as $key => $extra){
				$checked = '';
				$selectedQty=1;
				foreach($row['extra'] as $k => $cartExtra){
					
					$serviceExtra = Service::ServiceExtra($cartExtra['cart_extra_ids']);
					if($cartExtra['cart_extra_ids'] == $extra->id){
						$all_services[$index]['extra'][$k]['title'] = $serviceExtra->title;
						$all_services[$index]['extra'][$k]['price'] = $serviceExtra->price;
						$all_services[$index]['extra'][$k]['qty'] = $cartExtra['quantity'];
						$all_services[$index]['delivery_days'] = $all_services[$index]['delivery_days'] + $serviceExtra->delivery_days;
						$checked = 'checked';
						$selectedQty = $cartExtra['quantity'];
						
						$selectedExtra += $serviceExtra->price*$cartExtra['quantity'];
						$cart_extra_price_total += $serviceExtra->price*$cartExtra['quantity'];
						$totalDeliveryTimeExtra += $extra->delivery_days;
					}
				}
			}
			
			$all_services[$index]['service_cost'] = ($servicePlan->price * $row['quantity']);
		    /* + $cart_extra_price_total */
			/*Check for priority*/
			$buyerPromo = null;
			

			
			foreach ($discountPriority as $priority) {
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
							$all_services[$index]['reorder_promo_discount'] = $discountAmount;
							$all_services[$index]['reorder_promo_discount_per'] = $buyerPromo->amount;
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
								$all_services[$index]['coupon_discount'] = $discountAmount;
								$all_services[$index]['coupon_code'] = $row->coupon->coupan_code;
								$totalCoupenDiscount += $discountAmount;
								$afterDiscountPrice -= $discountAmount;
							}
						} else {
							/* $discountAmount = 1 * (($row->coupon->discount/100) * $row->plan->price); */
							$discountAmount = 1 * (($row->coupon->discount/100) * ($afterDiscountPrice + $cart_extra_price_total));
							$checkDiscountedPrice = ($afterDiscountPrice + $cart_extra_price_total) - $discountAmount;
							if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
								$all_services[$index]['coupon_discount'] = $discountAmount;
								$all_services[$index]['coupon_code'] = $row->coupon->coupan_code;
								$totalCoupenDiscount += $discountAmount;
								$afterDiscountPrice -= $discountAmount;
							}
						}
					}
				}elseif($priority->discount_type == 'volume_discount'){
					if($userObj->is_premium_seller($row->service->uid)){
						$v_discount_per = 0;
						foreach($row->service->volume_discount as $value1){
							if($row['quantity'] >= $value1->volume){
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
		}
		
		
        $final_sub_total = $cartSubtotal + $selectedExtra;
		$final_total = round_price($cartSubtotal + $selectedExtra - $totalPromoDiscount - $totalCoupenDiscount - $totalVolumeDiscount - $totalComboDiscount);
        $return = [
            'discountPriority' => $discountPriority, 
            'final_sub_total' => $final_sub_total, 
            'final_total'=>$final_total,
            'totalPromoDiscount' => round_price($totalPromoDiscount),
            'totalCoupenDiscount' => round_price($totalCoupenDiscount),
            'totalComboDiscount' => round_price($totalComboDiscount),
			'totalVolumeDiscount' => round_price($totalVolumeDiscount),
			'all_services' => $all_services
        ];
        return $return;
	}
	
	public function cart_customize(Request $request) {
		$uid = $this->uid;
		if ($request->input()) {

			if($request->has('direct_checkout') && $request->direct_checkout == 1){
				return $this->quick_checkout($request);
			}

			$ServicePlan = ServicePlan::where('service_id',$request->id)->find($request->plan_id);
			if(count($ServicePlan) == 0){
				return redirect('404');
			}

			//Check for influencer
			$influencer = $request->influencer ?? '';
			$influencer_id = 0;
			if(!empty($influencer)) {
				$influencer_data = Influencer::where('slug',$influencer)->select('id')->first();
				if(!is_null($influencer_data)) {
					$influencer_id = $influencer_data->id;
				}
			}

			//Check for recurring service
			$service = Service::with('extra:id,service_id')->withoutGlobalScope('is_course')->find($request->id);

			$is_course = $service->is_course;

			if($service->is_recurring == 1 || $ServicePlan->plan_type == 'monthly_access') {
				Cart::remove_recurring_from_cart();
			}

			//Check for review edition on cart add only
			$is_review_edition = 0;
			if(isset($request->is_review_edition) && $request->is_review_edition == 1){
				if($service->is_allow_review_edition() == true){
					$is_review_edition = 1;
				}
			}
			
			$quantity = 1;
			if($request->has('quantity') && $request->quantity > 1){
				if($service->is_recurring == 0 && $is_review_edition == 0 && $service->is_job == 0 && $service->is_custom_order == 0 && $is_course == 0){
					$quantity = $request->quantity;
				}
			}

			$settings = Setting::find(1)->first();
			$abandoned_cart = json_decode($settings->abandoned_cart_email);

			/*Check for order in queue*/
			if($is_course == 0){
				$allowbackOrder = $service->allowBackOrder();
				if($allowbackOrder->can_place_order == false){
					\Session::flash('tostError', 'No of order in queue has been over.');
					return redirect()->back();
				}
			}

			$cart_exist = Cart::where('uid',$uid)->where('service_id',$request->id)->where('plan_id',$request->plan_id)->first();

			if(is_null($cart_exist)) {
				$cart_exist=new Cart;
				$cart_exist->uid = $uid;
				$cart_exist->quantity=$quantity;
				$cart_exist->plan_id=$request->plan_id;
				$cart_exist->service_id=$request->id;
				$cart_exist->coupon_id=0;
				$cart_exist->is_course=$is_course;
				$cart_exist->email_index = 1;
				$cart_exist->email_send_at = date('Y-m-d H:i:s', strtotime(' + '.$abandoned_cart[0]->duration.' '.$abandoned_cart[0]->span.'s'));
				$cart_exist->influencer_id = $influencer_id;
				$cart_exist->is_review_edition = $is_review_edition;
				if($request->filled('utm_source')) {
					$cart_exist->utm_source = $request->utm_source;
				}
				if($request->filled('utm_term')) {
					$cart_exist->utm_term = $request->utm_term;
				}
				$cart_exist->save();

			} else {
				if($cart_exist->influencer_id == 0 && $influencer_id != 0) {
					$cart_exist->influencer_id = $influencer_id;
				}

				if($cart_exist->is_review_edition == 0 && $is_review_edition == 1) {
					$cart_exist->is_review_edition = $is_review_edition;
				}

				if($request->filled('utm_source') && $cart_exist->utm_source == null) {
					$cart_exist->utm_source = $request->utm_source;
				}
				if($request->filled('utm_term') && $cart_exist->utm_term == null) {
					$cart_exist->utm_term = $request->utm_term;
				}

				if($cart_exist->is_course == 1){
					$cart_exist->quantity = 1;
				}else{
					$cart_exist->quantity = (int)$cart_exist->quantity + $quantity;
				}

				$cart_exist->save();
			}

			//Enroll Free Course For Training Video
			if($cart_exist->is_course == 1 && Auth::user()->is_course_training_account($cart_exist->service) == true){
				$txn_id = $this->generate_txnid();
				$cart =  Cart::where('id',$cart_exist->id)->get();
				app('App\Http\Controllers\PaypalPaymentController')->new_createNewOrder($cart,0,0,$txn_id,$payBy='wallet');
				return redirect()->back();
			}
		} else {
			return redirect()->back();
		}
		return redirect()->route('view_cart');
	}

	function generate_txnid() {
    	return "TXN" . time() . 'WL' . rand('11', '99');
    }

	public function cookieCart(Request $request){
		if ($request->input()) {
			$influencer = $request->influencer ?? '';
			$cart = Session::get('cart');

			$ServicePlan = ServicePlan::where('service_id',$request->service_id)->find($request->plan_id);
			if(count($ServicePlan) == 0){
				return response([
					'status' => false,
					'message' => 'Item is not found please try again'
				]); 
			}

			$check_recurring = Service::withoutGlobalScope('is_course')->find($request->service_id);
			
			if($check_recurring->is_recurring == 1 || $ServicePlan->plan_type == 'monthly_access') {
				$cart = Session::forget('cart');
			}

			//Check for review edition on cart add only
			$is_review_edition = 0;
			if(isset($request->is_review_edition) && $request->is_review_edition == 1){
				if($check_recurring->is_allow_review_edition() == true){
					$is_review_edition = 1;
				}
			}

			$is_course = $check_recurring->is_course;
			
			$quantity = 1;
			if($check_recurring->is_recurring == 0 && $is_review_edition == 0 && $check_recurring->is_job == 0 && $check_recurring->is_custom_order == 0 && $check_recurring->is_course == 0){
				if($request->has('quantity') && $request->quantity > 1){
					$quantity = $request->quantity;
				}
			}

			$i = 0;
			if(!$cart) {
				$cart = [
						$i => [
							"service_id" => $request->service_id,
							"plan_id" => $request->plan_id,
							"quantity" => $quantity,
							"influencer" => $influencer,
							"is_review_edition" => $is_review_edition,
							"is_course" => $is_course,
							"utm_source" => $request->utm_source ?? '',
							"utm_term" => $request->utm_term ?? '',
						]
				];
			}else{
				
				$i = 0;
				$is_recurring_service = 0;
				foreach ($cart as $key => $value) {
					$service = Service::getservices($value['service_id']);
					if($is_recurring_service == 0 && $service->is_recurring == 1){
						unset($cart[$key]);
					}
					if($value['service_id'] == $request->service_id && $value['plan_id'] == $request->plan_id ){
						$cart[$key]['service_id'] = $request->service_id;
						$cart[$key]['plan_id'] = $request->plan_id;

						if($is_course == 1){
							$cart[$key]['quantity'] = $quantity;
						}else{
							$cart[$key]['quantity'] = $cart[$key]['quantity'] + $quantity;
						}

						$cart[$key]['influencer'] = $influencer;

						if(!isset($value['is_review_edition'])){
							$cart[$key]['is_review_edition'] = $is_review_edition;
						}else if($value['is_review_edition'] == 0 && $is_review_edition == 1){
							$cart[$key]['is_review_edition'] = 1;
						}

						$cart[$key]['is_course'] = $is_course;

						$cart[$key]["utm_source"] = $request->utm_source ?? '';
						$cart[$key]["utm_term"] = $request->utm_term ?? '';
						$i--;
					}
					$i++;
				}

				if(count($cart) < $i+1 ){
					$cartNew = [
						$i  => [
							"service_id" => $request->service_id,
							"plan_id" => $request->plan_id,
							"quantity" => 1,
							"influencer" => $influencer,
							"is_review_edition" => $is_review_edition,
							"is_course" => $is_course,
							"utm_source" => $request->utm_source ?? '',
							"utm_term" => $request->utm_term ?? '',
						]
					];
					
					$cart = array_merge($cart,$cartNew);
				}
			}
			Session::put('cart', $cart);
			//Session::flash('errorSuccess', 'Item successfully added to your cart');
			return response([
				'status' => 'success',
				'message' => 'Item successfully added to your cart'
			]); 
		}
	}

	function add_to_cart_combo_session(Request $request) {
		if ($request->input()) {
			$cart = Session::get('cart');
			$bundleService = BundleService::where('bundle_id',$request->bundle_id)->get();
			
			if(!$bundleService->isempty()){
				foreach ($bundleService as $allsevices) {
					$service = Service::withoutGlobalScope('is_course')->find($allsevices->service_id);
					$is_course = $service->is_course;

					$plan_type = 'basic';
					$is_course = 0;
					if($service->is_course == 1){
						$plan_type = 'lifetime_access';
						$is_course = 1;
					}

					if($request->id == $service->id){
						$plan_type = $request->package;
					}
					$servicePlan = ServicePlan::where(['service_id'=>$service->id,'plan_type' => $plan_type])->first();
					if($service){
						$seviceid = $service->id;
						$planid = $servicePlan->id;
						$qnt = 1;
						$i = 0;

						//Check for review edition on cart add only
						$is_review_edition = 0;
						if(isset($request->is_review_edition) && $request->is_review_edition == 1){
							if($service->is_allow_review_edition() == true){
								$is_review_edition = 1;
							}
						}

						$quantity = 1;
						if($service->is_recurring == 0 && $is_review_edition == 0 && $service->is_job == 0 && $service->is_custom_order == 0 && $service->is_course == 0){
							if($request->has('quantity') && $request->quantity > 1){
								$quantity = $request->quantity;
							}
						}
						
						if(!$cart) {
							$cart = [
									$i => [
										"service_id" => $seviceid,
										"plan_id" => $planid,
										"quantity" => $quantity,
										"is_review_edition" => $is_review_edition,
										"is_course" => $is_course
									]
							];
						}else{
							$i = 0;
							$is_recurring_service = 0;
							foreach ($cart as $key => $value) {
								if($is_recurring_service == 0 && $service->is_recurring == 1){
									unset($cart[$key]);
								}
								if($value['service_id'] == $seviceid && $value['plan_id'] ==$planid ){
									$cart[$key]['service_id'] = $seviceid;
									$cart[$key]['plan_id'] = $planid;

									if($is_course == 1){
										$cart[$key]['quantity'] = $quantity;
									}else{
										$cart[$key]['quantity'] = $cart[$key]['quantity'] + $quantity;
									}

									if(!isset($value['is_review_edition'])){
										$cart[$key]['is_review_edition'] = $is_review_edition;
									}else if($value['is_review_edition'] == 0 && $is_review_edition == 1){
										$cart[$key]['is_review_edition'] = 1;
									}
									$cart[$key]['is_course'] = $is_course;
									
									$i--;
								}
								$i++;
							}
							
							if(count($cart) < $i+1 ){
								$cartNew = [
									$i  => [
										"service_id" => $seviceid,
										"plan_id" => $planid,
										"quantity" => $quantity,
										"is_review_edition" => $is_review_edition,
										"is_course" => $is_course
									]
								];
								
								$cart = array_merge($cart,$cartNew);
							}
						}
					}
				} 
			}
			
			Session::put('cart', $cart);
			//Session::flash('errorSuccess', 'Item successfully added to your cart');
			return response([
				'status' => 'success',
				'message' => 'Item successfully added to your cart'
			]); 
		} else {
			return response([
				'status' => 'failed',
				'message' => 'Somethings went wrong please check after some times.'
			]);
		}
	}
	
	public function update_cart_session(Request $request){
		if ($request->input()) {
			$cart = Session::get('cart');
			$id = $request->id;
			if($request->filled('quantity')) {
				$cart[$id]['quantity'] = $request->quantity;
			}
			if($request->filled('plan_id')) {
				$cart[$id]['plan_id'] = $request->plan_id;
			}
			Session::put('cart', $cart);

			return response([
				'status' => 'success',
				'message' => 'Item successfully updated'
			]); 
		}
	}

	public function update_add_ons_session(Request $request){
		if(!$request->filled('cart_id') && !$request->filled('service_extra_id') && !$request->filled('qty')) {
			return response()->json(['status'=>'error', 'message'=>'Please fill all required parameters']);
		}
		$cart = Session::get('cart');
		
		if(is_null($cart)) {
			return response()->json(['status'=>'error' ,'message' => 'Invalid cart']);
		}
		
		$i = 0;
		$cart_id = $request->cart_id;
	

		if(isset($cart[$cart_id]['extra']) && count($cart[$cart_id]['extra']) > 0){
			$i = 0;
			
			foreach ($cart[$cart_id]['extra'] as $key => $value){
				if($value['cart_id'] == $request->cart_id && $value['cart_extra_ids']  == $request->service_extra_id ){
					$cart[$cart_id]['extra'][$request->service_extra_id]['quantity'] = $request->qty;
					$i--;
				}else{
					$cart[$cart_id]['extra'][$request->service_extra_id] = array(
						"cart_id" => $request->cart_id,
						"cart_extra_ids" => $request->service_extra_id,
						"quantity" => $request->qty,
					);
				}

				$i++;	
			}
			if(count($cart[$cart_id]['extra']) < $i+1 ){
				
				// $cart[$cart_id]['extra'][] = [
				// 	$i  => [
				// 		"cart_id" => $request->cart_id,
				// 		"cart_extra_ids" => $request->service_extra_id,
				// 		"quantity" => $request->qty,
				// 	]
				// ];
				// $cart = array_merge($cart,$cartNew);
			}

		}else{
			//First Extra service cart added 
			$cart[$cart_id]['extra'] = [
				$request->service_extra_id => [
					"cart_id" => $request->cart_id,
					"cart_extra_ids" => $request->service_extra_id,
					"quantity" => $request->qty,
				]
			];
		}
		// echo '<pre>';
		// print_r($cart);
		// echo '</pre>';
		Session::put('cart', $cart);
        // $cartExtra = new CartExtra;
        // $cartExtra->cart_id = $request->cart_id;
        // $cartExtra->service_extra_id = $request->service_extra_id;
        // $cartExtra->qty = $request->qty;
        // $cartExtra->save();
		$total_cart_extra = count($cart[$cart_id]['extra']);
        return response()->json(['status'=>'success','total_cart_extra'=>$total_cart_extra]);
	}
	
	public function remove_add_ons_session(Request $request){
		if ($request->input()) {
			$cart = Session::get('cart');
			$cartId = $request->cart_extra_id;
			$extraid = $request->extra_id;
			
			unset($cart[$cartId]['extra'][$extraid]);
			Session::put('cart', $cart);
			$total_cart_extra = count($cart[$cartId]['extra']);
			return response([
				'status' => 'success',
				'message' => 'Product removed from cart.',
				'total_cart_extra' => $total_cart_extra,
			]);
		}else{
			return response([
				'status' => 'failed',
				'message' => 'Somethings went wrong please check after some times.'
			]);
		}
	}

	public function update_extra_cart_session(Request $request){
		if ($request->input()) {
			$cart = Session::get('cart');
			$cartId = $request->id;
			$extraid = $request->extraCartId;
			$quantity = $request->quantity;
			if($request->filled('quantity')) {
				$cart[$cartId]['extra'][$extraid]['cart_id'] = $cartId;
				$cart[$cartId]['extra'][$extraid]['cart_extra_ids'] = $extraid;
				$cart[$cartId]['extra'][$extraid]['quantity'] = $request->quantity;
				Session::put('cart', $cart);
			}
			return response([
				'status' => 'success',
				'message' => 'Product removed from cart.'
			]);
		}else{
			return response([
				'status' => 'failed',
				'message' => 'Somethings went wrong please check after some times.'
			]);
		}
	}

	public function remove_cart_session(Request $request){
		if ($request->input()) {
			$cart = Session::get('cart');
			$id = $request->id;
			unset($cart[$id]);
			Session::put('cart', $cart);
			return response([
				'status' => 'success',
				'message' => 'Product removed from cart.'
			]);
		}else{
			return response([
				'status' => 'failed',
				'message' => 'Somethings went wrong please check after some times.'
			]);
		}
	}

	public function sessionCartAdded($request){
		$cart = Session::get('cart');
		$uid = $this->uid;
		
		if($cart != '') {
			$own_service_error = '';
			foreach ($cart as $key => $value) {
				$influencer = $value['influencer'] ?? '';
				$influencer_id = 0;
				if(!empty($influencer)) {
					$influencer_data = Influencer::where('slug',$influencer)->select('id')->first();
					if(!is_null($influencer_data)) {
						$influencer_id = $influencer_data->id;
					}
				}
				# code...
				$check_recurring = Service::withoutGlobalScope('is_course')->find($value['service_id']);

				if($uid != $check_recurring->uid ){

					$ServicePlan = ServicePlan::where('service_id',$value['service_id'])->find($value['plan_id']);
					if(count($ServicePlan) == 0){
						continue;
					}

					if($check_recurring->is_recurring == 1 || $ServicePlan->plan_type == 'monthly_access') {
						Cart::remove_recurring_from_cart();
					}

					//Check for review edition on cart add only
					$is_review_edition = 0;
					if(isset($value['is_review_edition']) && $value['is_review_edition'] == 1){
						if($check_recurring->is_allow_review_edition() == true){
							$is_review_edition = 1;
						}
					}

					$is_course = $check_recurring->is_course;

					$settings = Setting::find(1)->first();
					$abandoned_cart = json_decode($settings->abandoned_cart_email);

					$cart_exist = Cart::where('uid',$uid)->where('service_id',$value['service_id'])->where('plan_id',$value['plan_id'])->first();

					if(is_null($cart_exist)) {
						$inserData=new Cart;
						$inserData->uid = $uid;
						$inserData->quantity=$value['quantity'];;
						$inserData->plan_id=$value['plan_id'];
						$inserData->service_id=$value['service_id'];
						$inserData->coupon_id=0;
						$inserData->email_index = 1;
						$inserData->email_send_at = date('Y-m-d H:i:s', strtotime(' + '.$abandoned_cart[0]->duration.' '.$abandoned_cart[0]->span.'s'));
						$inserData->influencer_id = $influencer_id;
						$inserData->is_review_edition = $is_review_edition;
						$inserData->is_course = $is_course;
						if(isset($value['utm_source'])) {
							$inserData->utm_source = $value['utm_source'];
						}
						if(isset($value['utm_term'])) {
							$inserData->utm_term = $value['utm_term'];
						}
						
						$inserData->save();
					} else {
						if($cart_exist->influencer_id == 0 && $influencer_id != 0) {
							$cart_exist->influencer_id = $influencer_id;
						}

						if($cart_exist->is_review_edition == 0 && $is_review_edition == 1) {
							$cart_exist->is_review_edition = $is_review_edition;
						}

						$cart_exist->is_course = $is_course;

						if(isset($value['utm_source']) && $cart_exist->utm_source == null) {
							$cart_exist->utm_source = $value['utm_source'];
						}
						if(isset($value['utm_term']) && $cart_exist->utm_term == null) {
							$cart_exist->utm_term = $value['utm_term'];
						}
						$cart_exist->quantity = (int)$cart_exist->quantity + 1;
						$cart_exist->save();
					}
					if(isset($value['extra'])){
						foreach($value['extra'] as $extraKey => $extrValue){
							if(is_null($cart_exist)) {
								$cartValue = $inserData;
							}else{
								$cartValue = $cart_exist;
							}
							$cartExtra = CartExtra::where('cart_id',$cartValue->id)->where('service_extra_id',$extrValue['cart_extra_ids'])->first();
							$quantity = $extrValue['quantity'];
							if(is_null($cartExtra)){
								$cartExtra = new CartExtra;
							}else{
								$quantity = $cartExtra->qty + $extrValue['quantity'];
							}
							$cartExtra->cart_id = $cartValue->id;
							$cartExtra->service_extra_id = $extrValue['cart_extra_ids'];
							$cartExtra->qty =  $extrValue['quantity'];
							$cartExtra->save();
						}
					}
				} else {
					$own_service_error = "Some of services are your own, so it's removed from your cart.";
				}
			}
			$cart = Session::forget('cart');
			if(strlen($own_service_error) > 0) {
				\Session::flash('errorFails', $own_service_error);
			}
		}
		return 'Cart added successfully';
	}

	// Add to cart for job and custom order
	public function add_to_cart(Request $request) {
		$uid = $this->uid;
		if ($request->input()) {
			if ($request->filled('is_custom_order')) {
				$validate = true;
				if ($request->is_custom_order != '') {
					$service = Service::with('basic_plans')->find($request->is_custom_order);
					if (!empty($service)) {
						/* check for sub user can purchase or not */
						if(Auth::user()->parent_id != 0) {
							$next_cart_bill =  $service->basic_plans->price;
							if(User::check_sub_user_permission('can_make_purchases',$next_cart_bill) == false) {
								$notEnoughBalanceMsg = User::get_subuser_remaining_budget_message();
								\Session::flash('tostError', $notEnoughBalanceMsg);
								return redirect()->back();
							}
						}
						$cart = Cart::where(['uid' => $uid, 'service_id' => $service->id, 'plan_id' => $service->basic_plans->id])->first();
						if (empty($cart)) {
							$cart = new Cart;
							$cart->uid = $uid;
							$cart->service_id = $service->id;
							$cart->plan_id = $service->basic_plans->id;
							$cart->quantity = 1;
							$cart->is_custom_order = 1;
							$cart->utm_source = $service->utm_source;
							$cart->utm_term = $service->utm_term;
							$cart->save();
	
							$is_custom_order = 1;
						} else {
							$validate = true;
						}
					} else {
						$validate = false;
					}
				} else {
					$validate = false;
				}
				if($validate == false) {
					\Session::flash('tostError', 'Something went wrong.');
					return redirect()->back();
				}
			} else if($request->filled('job')) {
				$offer_id = JobOffer::getDecryptedId($request->job);
				try{
					if(empty($offer_id)){
						return redirect()->back();
					}
				}catch(\Exception $e){
					return redirect()->back();
				}

				$jobCheck = JobOffer::where('id',$offer_id)
				->whereHas('service',function($q){
					$q->where('expire_on','>=',Carbon::now()->format('Y-m-d H:i:s'))->select('id');
				})
				->first();

				if(count($jobCheck))
				{
					if ($offer_id != '') {
						
						/*Check Blocked user*/
						$block_users = User::getBlockedByIds();
						if($jobCheck->seller_id == $uid){
							if(in_array($jobCheck->buyer_id, $block_users)){
            					\Session::flash('tostError', 'You are not able to award this job. Your account is blocked by user.');
								return redirect()->back();
							}
						}else{
							if(in_array($jobCheck->seller_id, $block_users)){
            					\Session::flash('tostError', 'You are not able to award this job. Your account is blocked by user.');
								return redirect()->back();
							}
						}

						$jobDetail = JobOffer::find($offer_id);
						$service = Service::with('basic_plans')->find($jobDetail->service_id);

						if(!is_null($service)) {
							/* check for sub user can purchase or not */
							if(Auth::user()->parent_id != 0) {
								$next_cart_bill =  $service->basic_plans->price;
								if(User::check_sub_user_permission('can_make_purchases',$next_cart_bill) == false) {
									$notEnoughBalanceMsg = User::get_subuser_remaining_budget_message();
									\Session::flash('tostError', $notEnoughBalanceMsg);
									return redirect()->back();
								}
							}
						}

						$jobDetail->status = 'is_payment';
						$jobDetail->save();
		
						$uncheckAll=JobOffer::where('service_id',$jobDetail->service_id)->whereNotIn('id',[$jobDetail->id])->get();
							
						foreach ($uncheckAll as $check) {
							$updData=JobOffer::find($check->id);
							$updData->status='pending';
							$updData->save();
						}
	
						$servicePlan=ServicePlan::where('service_id',$jobDetail->service_id)->update(['price'=>$jobDetail->price]);
	
						if (count($service)) {
							$cart = Cart::where(['uid' => $uid, 'service_id' => $service->id, 'plan_id' => $service->basic_plans->id])->first();
							if (count($cart) == 0) {
								$cart = new Cart;
								$cart->uid = $uid;
								$cart->service_id = $service->id;
								$cart->plan_id = $service->basic_plans->id;
								$cart->quantity = 1;
								$cart->is_job = 1;
								$cart->save();

								$is_job = 1;
							}else {
								/*\Session::flash('errorFails', 'Something went wrong.');
								return redirect()->back();*/
							}
						}
					}else {
						\Session::flash('errorFails', 'Something went wrong.');
						return redirect()->back();
					}
				}
				else
				{	
					\Session::flash('errorFails', 'Job offer not found.');
					return redirect()->back();   
				}
			}
			return redirect()->route('view_cart');
		}
	}

	public function get_interestedServices($uid) {
		/*begin : you may also interested in services*/
		$cartServices = Cart::distinct()->select('service_id')->where('uid',$uid);
		$selectUsers = Order::distinct()->select('uid')
		->whereHas('user', function($query) use ($uid){
			$query->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
			$query->where('id', '!=',$uid)->select('id');
		})->whereIn('service_id',$cartServices);

		$interestedServices = Order::with('service')->distinct()
		->select(DB::raw("service_id,count(service_id) AS total_count"))
		->whereNotIN('service_id',$cartServices)
		->whereIn('uid',$selectUsers)
		->whereHas('service', function($query) {
			$query->where('status', 'active')->where('is_approved',1)->select('id');
		})
		->where('is_custom_order',0)
		->where('is_job',0)
		->groupBy('service_id')
		->having('total_count', '>' , 1)
		->orderBy('total_count','desc')
		->limit(10)->get();	
		return $interestedServices;
	}

	public function get_sponsered_cart() {
        $sponsered_cart = BoostedServicesOrder::where('plan_id', '=', 7)
		->where('status','active')
        ->where(function($query) {
            $query->where('start_date', '<=', date('Y-m-d'))->where('end_date', '>=', date('Y-m-d'));
        });

        $sponsered_cart = $sponsered_cart->whereHas('service', function ($query) {
    		$query->where('is_private', 0)->where('status','active')->where('is_approved',1)->where('is_delete',0)->select('id');
    	});

        $sponsered_cart = $sponsered_cart->whereHas('service.user', function ($query) {
            $query->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->select('id');
        });
		$sponsered_cart = $sponsered_cart->groupBy('service_id')->get();
		return $sponsered_cart;
	}

	public function update_cart(Request $request) {
		$cart = Cart::where('id', $request->id)->first();
		if(is_null($cart)) {
			\Session::flash('tostError', 'Something went wrong.');
			return response()->json(['status'=>'error']);
		}
		
		if($request->filled('quantity')) {
			$cart->quantity = $request->quantity;
		}
		if($request->filled('plan_id')) {
			$cart->plan_id = $request->plan_id;
		}
		$cart->save();

		$estimated_delivered_days_msg = '';
		$service_plan = '';
		$service_plan_delivery_days = '';

		if($cart->plan){
			$service_plan = display_title($cart->plan->package_name,17);
			$service_plan_delivery_days = $cart->plan->delivery_days.' days delivery';
		}

		if($cart->service->allow_backorders == 1){
			$service_paln = 'basic_plans';
	        if($cart->plan->plan_type == 'standard'){
	            $service_paln = 'standard_plans';
	        }elseif($cart->plan->plan_type == 'premium'){
	            $service_paln = 'premium_plans';
	        }
			$estimated_delivered_days_msg = $cart->service->getExpectedDeliveredDays($service_paln)->estimated_delivered_days_msg;
		}

		return response()->json(['status'=>'success','estimated_delivered_days_msg'=>$estimated_delivered_days_msg,'service_plan'=>$service_plan,'service_plan_delivery_days'=>$service_plan_delivery_days]);
	}

	public function cart_summary_calculation($Cart) {
		$uid = $this->uid;
		$userObj = new User;
		$cartObj = new CartCombo;

        $cartSubtotal = $selectedExtra = $totalDeliveryTime = $totalDeliveryTimeExtra = $totalPromoDiscount = $totalCoupenDiscount = $totalVolumeDiscount = $totalComboDiscount = 0; 
		
		$all_services = [];

		$discountPriority = DiscountPriority::OrderBy('priority','desc')->get();
		$is_recurring_service = 0;

		foreach($Cart as $index => $row) {

			//Update price for review edition
			if($row->is_review_edition == 1){
				$row->plan->price = $row->plan->review_edition_price;
			}

			$service_url = '';
			if($row->is_job == 1){
				$service_url = route('show.job_detail',[$row->service->seo_url]);
			}elseif($row->is_course == 1){
				$service_url = route('course_details',[$row->service->user->username,$row->service->seo_url]);
			}elseif($row->is_job == 0 && $row->is_custom_order == 0){
				//For normal service
				$service_url = route('services_details',[$row->service->user->username,$row->service->seo_url]);
			}
			$all_services[$index]['service_url'] = $service_url;

			$all_services[$index]['cart_id'] = $row->id;
			$all_services[$index]['title'] = $row->service->title;
			$all_services[$index]['delivery_days'] = $row->plan->delivery_days;
			$all_services[$index]['quantity'] = $row->quantity;
			$all_services[$index]['plan_title'] = $row->plan->package_name;
			$all_services[$index]['is_course'] = $row->is_course;
			$cartSubtotal += ($row->plan->price * $row->quantity);
			$totalDeliveryTime +=  $row->plan->delivery_days;

			$afterDiscountPrice = $row->plan->price * $row->quantity;

			if($is_recurring_service == 0 && $row->service->is_recurring == 1){
				$is_recurring_service = 1;
			}

			$cart_extra_price_total = 0;
			$all_services[$index]['extra'] = [];
			foreach($row->service->extra as $key => $extra){
				$checked = '';
				$selectedQty=1;
				foreach($row->extra as $k => $cartExtra){
					if($cartExtra->service_extra_id==$extra->id){
						$all_services[$index]['extra'][$k]['title'] = $cartExtra->service_extra->title;
						$all_services[$index]['extra'][$k]['price'] = $cartExtra->service_extra->price;
						$all_services[$index]['extra'][$k]['qty'] = $cartExtra->qty;
						$all_services[$index]['delivery_days'] = $all_services[$index]['delivery_days'] + $cartExtra->service_extra->delivery_days;
						$serviceExtraPrice=ServiceExtra::where('id',$cartExtra->service_extra_id)->first();
						$checked = 'checked';
						$selectedQty = $cartExtra->qty;
						$selectedExtra += $serviceExtraPrice->price*$cartExtra->qty;
						$cart_extra_price_total += $serviceExtraPrice->price*$cartExtra->qty;
						$totalDeliveryTimeExtra += $extra->delivery_days;
					}
				}
			}
			$all_services[$index]['service_cost'] = ($row->plan->price * $row->quantity);

			/*Check for priority*/
			$buyerPromo = null;
			//For review edition service do not apply re-order discount
			if($row->remove_reorder_promo == 0 && $row->is_review_edition == 0){
				$buyerPromo = BuyerReorderPromo::where('seller_id',$row->service->uid)
				->where('buyer_id',$uid)
				->where('service_id',$row->service->id)
				->where('is_used',0)
				->first();
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
							$all_services[$index]['reorder_promo_discount'] = $discountAmount;
							$all_services[$index]['reorder_promo_discount_per'] = $buyerPromo->amount;
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
								$all_services[$index]['coupon_discount'] = $discountAmount;
								$all_services[$index]['coupon_code'] = $row->coupon->coupan_code;
								$totalCoupenDiscount += $discountAmount;
								$afterDiscountPrice -= $discountAmount;
							}
						} else {
							/* $discountAmount = 1 * (($row->coupon->discount/100) * $row->plan->price); */
							$discountAmount = 1 * (($row->coupon->discount/100) * ($afterDiscountPrice + $cart_extra_price_total));
							$checkDiscountedPrice = ($afterDiscountPrice + $cart_extra_price_total) - $discountAmount;
							if($checkDiscountedPrice >= env('MINIMUM_SERVICE_PRICE')){
								$all_services[$index]['coupon_discount'] = $discountAmount;
								$all_services[$index]['coupon_code'] = $row->coupon->coupan_code;
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
        }
        $final_sub_total = $cartSubtotal + $selectedExtra;
        $final_total = round_price($cartSubtotal + $selectedExtra - $totalPromoDiscount - $totalCoupenDiscount - $totalVolumeDiscount - $totalComboDiscount);

        $return = [
            'discountPriority' => $discountPriority, 
            'final_sub_total' => $final_sub_total, 
            'final_total'=>$final_total,
            'totalPromoDiscount' => round_price($totalPromoDiscount),
            'totalCoupenDiscount' => round_price($totalCoupenDiscount),
            'totalComboDiscount' => round_price($totalComboDiscount),
			'totalVolumeDiscount' => round_price($totalVolumeDiscount),
			'all_services' => $all_services
        ];

        return $return;
    }
    
	public function apply_coupen(Request $request) {
		$uid = $this->uid;
		$carts = Cart::where('uid',$uid)->orderBy('id','desc')
		->whereHas('service',function($q){
			$q->where(['is_job'=>0,'is_custom_order'=>0,'status'=>'active','is_approved'=>1,'is_delete'=>0])->select('id');
		})
		->get();
		
		$is_recurring_service = 0;
		foreach($carts as $index => $row){
			if($is_recurring_service == 0 && $row->service->is_recurring == 1){
				$is_recurring_service = 1;
			}
			if($row->plan->plan_type == 'monthly_access') {
				$is_recurring_service = 1;
			}
		}
		if($is_recurring_service == 1) {
			$carts = Cart::get_recurring_from_cart();
		}
		
		$status = "error";
		$msg = "Invalid coupon code";

		foreach ($carts as $key => $value) {
			$coupon = Coupan::where('coupan_code',$request->coupon_code)->where('is_delete',0)->where('service_id',$value->service_id)->where('coupon_type',0)->first();
			if(is_null($coupon))
			{
				/* check for general coupon */
				$coupon = Coupan::where('coupan_code',$request->coupon_code)->where('is_delete',0)->where('coupon_type',1)->where('user_id',$value->service->uid)->first();
				if(is_null($coupon))
				{
					$msg = "Not valid coupon code";
					continue;
				}
			}

			if($coupon->expiry_date < date('Y-m-d')) {
				$msg = "Coupon is expired";
				continue;
			}

			$is_course = $value->is_course;

			if($is_course == 0){
				/* Check promo is valid for recurring service or not */
				if($value->service->is_recurring == 1 && $coupon->allow_on_recurring_order == 0){
					$msg = "Not valid coupon code";
					continue;
				}
				/* Check promo for normal service */
				if($value->service->is_recurring == 0 && $coupon->allow_on_recurring_order == 1){
					$msg = "Not valid coupon code";
					continue;
				}
			}else{
				//Check for recurring monthly course
				if($value->plan->plan_type == "monthly_access"){
					if($coupon->allow_on_recurring_order == 0){
						$msg = "Not valid coupon code";
						continue;
					}
				}else{
					if($coupon->allow_on_recurring_order == 1){
						$msg = "Not valid coupon code";
						continue;
					}
				}
			}
			
			$coupon_applied_count = CoupanApplied::where('coupan_code_id', $coupon->id)->count();
			if($coupon_applied_count >= $coupon->no_of_uses) {
				$msg = "Number of uses limit exceed";
				continue;
			}

			//For review edition no any discount
			if($value->is_review_edition == 1){
				if($value->coupon_id > 0) {
					$value->coupon_id = 0;
					$value->save();
				}
				continue;
			}

			/*Check for re-order pomo discount applied*/
			if($value->remove_reorder_promo == 0 && $value->is_review_edition == 0){
				$buyerPromo = BuyerReorderPromo::where('seller_id',$value->service->uid)
				->where('buyer_id',$uid)
				->where('service_id',$value->service->id)
				->where('is_used',0)
				->where('is_combined_other',1)
				->first();

				if(!empty($buyerPromo)){
					$msg = "Re-order promo discount already applied";
					continue;
				}
			}

			if($coupon->discount_type == 'amount') {
				if($value->plan->price - $coupon->discount < env('MINIMUM_SERVICE_PRICE') ) {
					$msg = "Opps! Price must be greater than $".env('MINIMUM_SERVICE_PRICE')." after apply discount";
					continue;
				}
			} else {
				$dis = ($value->plan->price * $coupon->discount) / 100;
				if($value->plan->price - $dis < env('MINIMUM_SERVICE_PRICE') ) {
					$msg = "Opps! Price must be greater than $".env('MINIMUM_SERVICE_PRICE')." after apply discount";
					continue;
				}
			}
			
			if($value->coupon_id == 0) {
				//update coupon id in cart table
				$value->coupon_id = $coupon->id;
				$value->save();
				$status = "success";
				$msg = "Coupon applied";
				return response()->json(["status" => $status, "message" => $msg]);
			}else{
				$msg = "Already one promo discount already applied";
			}
		}

		/*Check for coupon already applied*/
		if($status == 'error'){
			$coupon = Coupan::where('coupan_code',$request->coupon_code)->where('is_delete',0)->first();
			if(!empty($coupon)){
				$already_applied_coupon = Cart::where('uid', $uid)
					->where('coupon_id',$coupon->id)
					->whereHas('service',function($q){
						$q->where(['is_job'=>0,'is_custom_order'=>0,'status'=>'active','is_approved'=>1,'is_delete'=>0])->select('id');
					})
					->count();
				if ($already_applied_coupon > 0) {
					$msg = "This coupon code is already applied";
				}
			}
		}

		return response()->json(["status" => $status, "message" => $msg]);
	}

    function add_to_cart_combo(Request $request) {   
		if ($request->input()) {
			$uid = $this->uid;

			/*Check for order in queue*/
			$checkService = Service::withoutGlobalScope('is_course')->find($request->id);

			if($checkService->is_course == 0){
				$allowbackOrder = $checkService->allowBackOrder();
				if($allowbackOrder->can_place_order == false){
					\Session::flash('tostError', 'No of order in queue has been over.');
					return redirect()->back();
				}
			}

			$bundleService = BundleService::where('bundle_id',$request->bundle_id)->get();
			
			if(!$bundleService->isempty()){
				/* check for sub user can purchase or not */
				if(Auth::user()->parent_id != 0) {
					$next_cart_bill = 0;
					foreach ($bundleService as $allsevices) {
						$service = Service::withoutGlobalScope('is_course')->select('id','is_course')->find($allsevices->service_id);
						
						$plan_type = 'basic';
						if($service->is_course == 1){
							$plan_type = 'lifetime_access';
						}
						
						if($request->id == $service->id){
							$plan_type = $request->package;
						}
						$servicePlan = ServicePlan::where(['service_id'=>$service->id,'plan_type' => $plan_type])->select('id','price')->first();

						$cart = Cart::where(['uid' => $uid, 'service_id' => $service->id, 'plan_id' => $servicePlan->id])->select('id','quantity')->first();
						if (empty($cart)) {
							$next_cart_bill +=  $servicePlan->price;
						}elseif($service->is_course == 1){
							$next_cart_bill +=  $servicePlan->price;
						} else {
							$next_cart_bill +=  $servicePlan->price * ($cart->quantity + 1);
						}
					}
					if(User::check_sub_user_permission('can_make_purchases',$next_cart_bill) == false) {
						$notEnoughBalanceMsg = User::get_subuser_remaining_budget_message();
						\Session::flash('tostError', $notEnoughBalanceMsg);
						return redirect()->back();
					}
				}
				foreach ($bundleService as $allsevices) {
					$service = Service::withoutGlobalScope('is_course')->find($allsevices->service_id);

					$plan_type = 'basic';
					$is_course = 0;
					if($service->is_course == 1){
						$plan_type = 'lifetime_access';
						$is_course = 1;
					}

					if($request->id == $service->id){
						$plan_type = $request->package;
					}

					$servicePlan = ServicePlan::where(['service_id'=>$service->id,'plan_type' => $plan_type])->select('id')->first();
					if($service){
						$seviceid = $service->id;
						$planid = $servicePlan->id;

						//Check for review edition on cart add only
						$is_review_edition = 0;
						if(isset($request->is_review_edition) && $request->is_review_edition == 1){
							if($service->is_allow_review_edition() == true){
								$is_review_edition = 1;
							}
						}
						
						$quantity = 1;
						if($request->has('quantity') && $request->quantity > 1){
							if($service->is_recurring == 0 && $is_review_edition == 0 && $service->is_job == 0 && $service->is_custom_order == 0 && $is_course == 0){
								$quantity = $request->quantity;
							}
						}

						$cart = Cart::where(['uid' => $uid, 'service_id' => $seviceid, 'plan_id' => $planid])->first();

						if (empty($cart)) {
							$cart = new Cart;
							$cart->uid = $uid;
							$cart->service_id = $seviceid;
							$cart->plan_id = $planid;
							if ($request->filled('coupon_id')) {
								$cart->coupon_id = $request->coupon_id;
							}
							$cart->quantity = $quantity;
							$cart->is_review_edition = $is_review_edition;
							$cart->is_course = $is_course;
							if($request->filled('utm_source')) {
								$cart->utm_source = $request->utm_source;
							}
							if($request->filled('utm_term')) {
								$cart->utm_term = $request->utm_term;
							}
							$cart->save();
							\Session::put('dataLayerCartId',$cart->id);

						} else {
							$cart->coupon_id = $request->coupon_id;
							if($cart->service->is_recurring == 0 && $is_course == 0){
								$cart->quantity = $cart->quantity + $quantity;
							}else{
								$cart->quantity = $quantity;
							}
							if($cart->is_review_edition == 0 && $is_review_edition == 1){
								$cart->is_review_edition = $is_review_edition;
							}
							$cart->is_course = $is_course;
							if($request->filled('utm_source') && $cart->utm_source == null) {
								$cart->utm_source = $request->utm_source;
							}
							if($request->filled('utm_term') && $cart->utm_term == null) {
								$cart->utm_term = $request->utm_term;
							}
							$cart->save();
						}

						/*Update Total Add to cart By Month*/
						$sellerAnalytic = SellerAnalytic::where('service_id',$cart->service_id)
						->where('buyer_uid',$uid)
						->where('type','add_to_cart')
						->whereMonth('created_at', date('m'))
						->whereYear('created_at', date('Y'))
						->count();
						if($sellerAnalytic == 0){
							$sellerAnalytic = new SellerAnalytic;
							$sellerAnalytic->service_id = $cart->service_id;
							$sellerAnalytic->buyer_uid = $uid;
							$sellerAnalytic->type = 'add_to_cart';
							$sellerAnalytic->save(); 
						}

						/* Add Extra */
						$extra_ids = $request->input('extra_chk');
						if (count($extra_ids)) {
							foreach ($extra_ids as $key => $extra_id) {
								$qty = $_POST['extra_qty_'.$extra_id];
								$cartExtra = new CartExtraCombo;
								$cartExtra->cartcombo_id = $cart->id;
								$cartExtra->service_extra_id = $extra_id;
								$cartExtra->qty = $qty;
								$cartExtra->save();
							}
						}
					}
				} 
				\Session::flash('tostSuccess', 'Item added to cart.');
			}
			return redirect(route('view_cart'));
		} else {
			return redirect(url('/'));
		}
    }
    
    public function update_add_ons(Request $request) {
        if(!$request->filled('cart_id') && !$request->filled('service_extra_id') && !$request->filled('qty')) {
			return response()->json(['status'=>'error', 'message'=>'Please fill all required parameters']);
		}
        $cart = Cart::where('id', $request->cart_id)->first();
		if(is_null($cart)) {
			return response()->json(['status'=>'error' ,'message' => 'Invalid cart']);
		}
		
        $cartExtra = new CartExtra;
        $cartExtra->cart_id = $request->cart_id;
        $cartExtra->service_extra_id = $request->service_extra_id;
        $cartExtra->qty = $request->qty;
        $cartExtra->save();
		$total_cart_extra = CartExtra::where('cart_id',$request->cart_id)->select('id')->count();
        return response()->json(['status'=>'success','total_cart_extra'=>$total_cart_extra]);
    }

    public function remove_add_ons(Request $request) {
        if(!$request->filled('cart_extra_id')) {
			return response()->json(['status'=>'error', 'message'=>'Please fill all required parameters']);
        }
        CartExtra::where('id', $request->cart_extra_id)->delete();
		$total_cart_extra = CartExtra::where('cart_id',$request->cart_id)->select('id')->count();
        return response()->json(['status'=>'success','total_cart_extra'=>$total_cart_extra]);
	}
	
	public function update_extra_cart(Request $request) {
		$cart = CartExtra::where('id', $request->id)->first();
		if(is_null($cart)) {
			return response()->json(['status'=>'error']);
		}
		if($request->filled('quantity')) {
			$cart->qty = $request->quantity;
		}
		$cart->save();
		return response()->json(['status'=>'success']);
	}

	public function remove_coupon_code(Request $request, $id) {
		$uid = $this->uid;
		$cart = Cart::where('id',$id)->where('uid',$uid)->first();
		if(!is_null($cart)) {
			$cart->coupon_id = 0;
			$cart->save();
			$msg = "Coupon removed successfully";
			$status = 'success';
		} else {
			$msg = "Something went wrong";
			$status = 'error';
		}
		return response()->json(['status'=>$status, 'message'=>$msg]);
	}

	public function remove_reorder_promo_code(Request $request, $id) {
		$uid = $this->uid;
		$cart = Cart::where('id',$id)->where('uid',$uid)->first();
		if(!is_null($cart)) {
			$cart->remove_reorder_promo = 1;
			$cart->save();
			$msg = "Coupon removed successfully";
			$status = 'success';
		} else {
			$msg = "Something went wrong";
			$status = 'error';
		}
		return response()->json(['status'=>$status, 'message'=>$msg]);
	}

	public function reorder_service(Request $request) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('errorFails', get_user_softban_message());
		}
		
		$uid = $this->uid;
		$order = Order::where('order_no',$request->order_no)
						->where('uid',$uid)
						->where('is_custom_order',0)
						->where('is_job',0)
						->where('status', 'completed')
						->first();
		if(is_null($order)) {
			Session::flash('errorFails', 'Invalid order');
			return redirect()->back();
		}

		$ServicePlan = ServicePlan::where('service_id',$order->service_id)->find($order->plan->id);
		if(count($ServicePlan) == 0){
			return redirect('404');
		}

		$check_recurring = Service::withoutGlobalScope('is_course')->where('id',$order->service_id)->first();
		if($check_recurring->is_recurring == 1 || $ServicePlan->plan_type == 'monthly_access') {
			Cart::remove_recurring_from_cart();
		}
		
		$settings = Setting::find(1)->first();
		$abandoned_cart = json_decode($settings->abandoned_cart_email);

		$newcart = Cart::where('uid',$uid)->where('service_id',$order->service_id)->where('plan_id',$order->plan->id)->first();
		if(!is_null($newcart)) {
			CartExtra::where('cart_id', $newcart->id)->delete();
			Cart::where(['id' => $newcart->id])->delete();
		}

		$newcart=new Cart;
		$newcart->uid = $uid;
		$newcart->quantity=$order->qty;
		$newcart->plan_id=$order->plan->id;
		$newcart->service_id=$order->service_id;
		$newcart->coupon_id=0;
		$newcart->email_index = 1;
		$newcart->email_send_at = date('Y-m-d H:i:s', strtotime(' + '.$abandoned_cart[0]->duration.' '.$abandoned_cart[0]->span.'s'));
		$newcart->save();

		$order_extra = OrderExtra::where('order_id',$order->id)->where('service_id',$order->service_id)->select('id','title','qty')->get();
		foreach ($order_extra as $key => $value) {
			$service_extra = ServiceExtra::where('service_id',$order->service_id)->where('title',$value->title)->first();
			if(!is_null($service_extra)) {
				$cartExtra = new CartExtra;
				$cartExtra->cart_id = $newcart->id;
				$cartExtra->service_extra_id = $service_extra->id;
				$cartExtra->qty = $value->qty;
				$cartExtra->save();
			}
		}

		return redirect()->route('view_cart');
	}

	public function cart_payment_options(Request $request) {
		$request->merge([
			'is_quick_checkout' => 0,
		]);
		return $this->payment_options($request);
	}

	public function accept_agreement(Request $request){
		if(Auth::check()){
			$uid = $this->uid;
			$reviewEditionCount = Cart::select('id')->where('uid',$uid)->where('is_review_edition',1)->count();
			if($reviewEditionCount > 0){
				return redirect()->route('review_edition_agreement');
			}
			$courseCount = Cart::select('id')->where('uid',$uid)->where('is_course',1)->count();
			if($courseCount > 0){
				return redirect()->route('course_agreement');
			}
			
		}else{
			return redirect('/');
		}
	}

	public function review_edition_agreement(){
		if(Auth::check()){

			$uid = $this->uid;
			$cartObj = new Cart;
	
			/*Begin : Remove unwanted services*/
			$cartObj->remove_unwanted_services();
			/*End : Remove unwanted services*/
	
			$cart = Cart::where('uid',$uid)->where('is_review_edition',1)->OrderBy('id','desc')->get();
			
			if(count($cart) > 0){
				return view('frontend.new_cart.review_edition_agreement',compact('cart'));
			}else{
				return redirect()->route('cart_payment_options');
			}
		}else{
			return redirect('/');
		}
	}

	public function course_agreement(){
		if(Auth::check()){

			$uid = $this->uid;
			$cartObj = new Cart;
	
			/*Begin : Remove unwanted services*/
			$cartObj->remove_unwanted_services();
			/*End : Remove unwanted services*/
	
			$cart = Cart::where('uid',$uid)->where('is_course',1)->OrderBy('id','desc')->get();
			
			if(count($cart) > 0){
				return view('frontend.new_cart.course_agreement',compact('cart'));
			}else{
				return redirect()->route('cart_payment_options');
			}
		}else{
			return redirect('/');
		}
	}

	// Submit review edition agreement 
	public function submit_agreement(Request $request){
		if(!isset($request->confirm_checkbox) || !$request->confirm_checkbox){
			\Session::flash('tostError', 'Please agree the terms of agreement.');
			return redirect()->back();
		}

		Session::put('submit_re_agreement',1);

		if(Session::get('is_direct_checkout')){
			return redirect()->route('quick.checkout.payment');
		}
		//Check for course is found or not
		$uid = $this->uid;
		$courseCount = Cart::select('id')->where('uid',$uid)->where('is_course',1)->count();
		if($courseCount > 0){
			return redirect()->route('course_agreement');
		}
		return redirect()->route('cart_payment_options');
	}

	//Course agreement submit
	public function submit_course_agreement(Request $request){
		if(!isset($request->confirm_checkbox) || !$request->confirm_checkbox){
			\Session::flash('tostError', 'Please agree the terms of agreement.');
			return redirect()->back();
		}
		
		Session::put('submit_course_agreement',1);
		if(Session::get('is_direct_checkout')){
			return redirect()->route('quick.checkout.payment');
		}

		return redirect()->route('cart_payment_options');
	}

	/* Quick checkout payment options */ 
	public function quick_checkout_payment_options(Request $request) {
		$request->merge([
			'is_quick_checkout' => 1,
		]);
		return $this->payment_options($request);
	}

	/* Quick checkout */ 
	function quick_checkout($request){
		//Check for recurring service
		$uid = $this->uid;
		$service = Service::withoutGlobalScope('is_course')->find($request->id);
		if($service->uid == $uid){
			return redirect()->back();
		}
		if(count($service->extra) > 0){
			\Session::flash('tostError', 'Something went wrong.');
			return redirect()->back();
		}

		$ServicePlan = ServicePlan::where('service_id',$request->id)->find($request->plan_id);
		if(count($ServicePlan) == 0){
			return redirect('404');
		}

		//Check for influencer
		$influencer = $request->influencer ?? '';
		$influencer_id = 0;
		if(!empty($influencer)) {
			$influencer_data = Influencer::where('slug',$influencer)->select('id')->first();
			if(!is_null($influencer_data)) {
				$influencer_id = $influencer_data->id;
			}
		}

		$is_course = $service->is_course;

		if($service->is_recurring == 1 || $ServicePlan->plan_type == 'monthly_access') {
			Cart::remove_recurring_from_cart();
		}

		//Check for review edition on cart add only
		$is_review_edition = 0;
		if(isset($request->is_review_edition) && $request->is_review_edition == 1){
			if($service->is_allow_review_edition() == true){
				$is_review_edition = 1;
			}
		}
		
		$quantity = 1;
		if($request->has('quantity') && $request->quantity > 1){
			if($service->is_recurring == 0 && $is_review_edition == 0 && $service->is_job == 0 && $service->is_custom_order == 0 && $is_course == 0){
				$quantity = $request->quantity;
			}
		}

		$settings = Setting::find(1)->first();
		$abandoned_cart = json_decode($settings->abandoned_cart_email);

		/*Check for order in queue*/
		if($is_course == 0){
			$allowbackOrder = $service->allowBackOrder();
			if($allowbackOrder->can_place_order == false){
				\Session::flash('tostError', 'No of order in queue has been over.');
				return redirect()->back();
			}
		}

		/* BEGIN - remove exists item */
		Cart::where('uid',$uid)->where('direct_checkout',1)->delete();
		/* BEGIN - remove exists item */

		$cart_item=new Cart;
		$cart_item->uid = $uid;
		$cart_item->quantity=$quantity;
		$cart_item->plan_id=$request->plan_id;
		$cart_item->service_id=$request->id;
		$cart_item->coupon_id=0;
		$cart_item->is_course=$is_course;
		$cart_item->email_index = 1;
		$cart_item->email_send_at = date('Y-m-d H:i:s', strtotime(' + '.$abandoned_cart[0]->duration.' '.$abandoned_cart[0]->span.'s'));
		$cart_item->influencer_id = $influencer_id;
		$cart_item->is_review_edition = $is_review_edition;
		$cart_item->direct_checkout = 1;
		if($request->filled('utm_source')) {
			$cart_item->utm_source = $request->utm_source;
		}
		if($request->filled('utm_term')) {
			$cart_item->utm_term = $request->utm_term;
		}
		$cart_item->save();

		//Enroll Free Course For Training Video
		if($cart_item->is_course == 1 && Auth::user()->is_course_training_account($cart_item->service) == true){
			$txn_id = $this->generate_txnid();
			$cart =  Cart::where('id',$cart_item->id)->get();
			app('App\Http\Controllers\PaypalPaymentController')->new_createNewOrder($cart,0,0,$txn_id,$payBy='wallet');
			return redirect()->back();
		}

		Session::put('is_direct_checkout','true');
		if($is_review_edition == 1){
			$cart = Cart::where('is_review_edition',1)->where('uid',$uid)->where('direct_checkout',1)->get();
			return view('frontend.new_cart.review_edition_agreement',compact('cart'));
		}elseif($is_course == 1){
			$cart = Cart::where('is_course',1)->where('uid',$uid)->where('direct_checkout',1)->get();
			return view('frontend.new_cart.course_agreement',compact('cart'));
		}else{
			return redirect()->route('quick.checkout.payment');
		}
	}

	/* payment option common function */ 
	function payment_options($request){
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('view_cart')->with('errorFails', get_user_softban_message());
		}
		$is_quick_checkout = 0;
		if(isset($request->is_quick_checkout) && $request->is_quick_checkout == 1){
			$is_quick_checkout = 1;
		}

		$cartObj = new Cart;
		/*Begin : Remove unwanted services*/
		$cartObj->remove_unwanted_services();
		/*End : Remove unwanted services*/

		$uid = $this->uid;

		$cart = Cart::where('uid',$uid)->where('direct_checkout',$is_quick_checkout)->get();

		if(count($cart) == 0){
			return redirect()->route('view_cart');
		}

		$is_recurring_service = $is_review_edition = $is_course = 0;
		
		$auth_user = $this->auth_user;

		$service_id_list = [];
		foreach($cart as $index => $row){
			array_push($service_id_list,$row->service->id);
			if($is_recurring_service == 0 && $row->service->is_recurring == 1){
				$is_recurring_service = 1;
			}
			if($row->plan->plan_type == 'monthly_access') {
				$is_recurring_service = 1;
			}
		}
		if($is_recurring_service == 1) {
			$cart = Cart::get_recurring_from_cart($is_quick_checkout);
		}

		foreach($cart as $index => $row){

			//Update price for review edition
			if($row->is_review_edition == 1){
				$row->plan->price = $row->plan->review_edition_price;

				if($is_recurring_service == 0){
					$is_review_edition = 1;
				}
			}

			if($row->is_course == 1){
				$is_course = 1;
			}


			$cartSubtotal += ($row->plan->price * $row->quantity);
			$totalDeliveryTime +=  $row->plan->delivery_days;

			$buyerPromo = null;
			//For review edition service do not apply re-order discount
			if($row->remove_reorder_promo == 0 && $row->is_review_edition == 0){
				$buyerPromo = BuyerReorderPromo::where('seller_id',$row->service->uid)
				->where('buyer_id',$uid)
				->where('service_id',$row->service->id)
				->where('is_used',0)
				->first();
			}
			
			$discount_per = 0;

			if(!is_null($buyerPromo)){
				$discount_per = $buyerPromo->amount;

				$discount_amount = (($row->plan->price * $row->quantity) * $buyerPromo->amount ) / 100;

				$totalPromoDiscount += $discount_amount;

				$cart[$index]->reorder_promo_discount = $discount_amount;
				$cart[$index]->reorder_promo_discount_per = $buyerPromo->amount;
			}

			$image_url = url('public/frontend/assets/img/No-image-found.jpg');
			if(isset($row->service->images[0])){
				if(!is_null($row->service->images[0]->thumbnail_media_url)) {
					$image_url = $row->service->images[0]->thumbnail_media_url;
				} else if($row->service->images[0]->photo_s3_key != ''){
					$image_url = $row->service->images[0]->media_url; 
				}else{
					$image_url = url('public/services/images/'.$row->service->images[0]->media_url); 
				}
			}
			$cart[$index]->image_url = $image_url;
			
			$added_extra = [];
			$list = [];
			if(sizeof($row->extra) > 0) {
				foreach ($row->extra as $key => $value) {
					array_push($list, $value->service_extra_id);
					$added_extra[$value->service_extra_id] = $value;
				}
			}
			$cart[$index]->cart_extra_ids = $list;
			$cart[$index]->added_extra = $added_extra;
		}

		if($is_review_edition == 1 && !Session::has('submit_re_agreement')){
			return redirect()->back();
		}

		if($is_course == 1 && !Session::has('submit_course_agreement')){
			return redirect()->back();
		}


		$summary = $this->cart_summary_calculation($cart);
		if($is_recurring_service == 0){
			if($auth_user->earning == 0){
				$fromWalletAmount = 0;
			}elseif($auth_user->earning >= $summary['final_total']){
				$fromWalletAmount = $summary['final_total'];
			}else{
				$fromWalletAmount = $auth_user->earning;
			}
			if($auth_user->promotional_fund == 0){
				$fromPromotionalAmount = 0;
			}elseif($auth_user->promotional_fund >= $summary['final_total']){
				$fromPromotionalAmount = $summary['final_total'];
			}else{
				$fromPromotionalAmount = $auth_user->promotional_fund;
			}
		}else{
			$fromWalletAmount = 0;
			$fromPromotionalAmount = 0;
		}

		if(Auth::user()->parent_id != 0) {
			$fromPromotionalAmount = 0;
			if(User::check_sub_user_permission('can_use_wallet_funds') == false) {
				$fromWalletAmount = 0;
			}
		}
		$is_custom_order = 0;
		$is_job = 0;
		$settings = Setting::find(1);

		return view('frontend.new_cart.payment_options',compact('cart','summary','fromWalletAmount','fromPromotionalAmount','is_custom_order','is_job','settings','is_recurring_service','service_id_list','is_quick_checkout'));
	}

	/* Add extras */
	public function checkout_extras_payment(Request $request,$order_no){
		$uid = $this->uid;
		$auth_user = $this->auth_user;

		$order = Order::withoutGlobalScope('parent_id')
				->select('id','service_id','uid')
				->where('uid',$uid)
				->where('order_no',$order_no)
				->whereIn('status',['delivered','active'])
				->where('is_custom_order',0)
				->where('is_recurring',0)
				->where('is_dispute',0)
				->where('is_course',0)
				->where('is_job',0)
				->first();

		if(is_null($order) && !$request->has('extras_id')){
			\Session::flash('tostError', 'Something went wrong, Please try again!');
			return redirect()->back();
		}			

		$is_error = $final_amount = 0;
		$extras = [];
		foreach ($request->extras_id as $key => $value) {
			$extras_id = ServiceExtra::getDecryptedId($value);
			$check_extras = ServiceExtra::where('service_id',$order->service_id)->find($extras_id);
			if(is_null($check_extras)){
				$is_error = 1;
				break;
			}
			$quantity = (int)$request->quantity[$value];
			$extras[$key]['id'] = $extras_id;
			$extras[$key]['title'] = $check_extras->title;
			$extras[$key]['quantity'] = $quantity;
			$extras[$key]['price'] = $check_extras->price;
			$extras[$key]['delivery_days'] = $check_extras->delivery_days;

			$final_amount = $final_amount + ($check_extras->price*$quantity);
		}
		if($is_error == 1){
			\Session::flash('tostError', 'Something went wrong, Please try again!');
			return redirect()->back();
		}

		$summary['extra'] = $extras;
		$summary['service_id'] = $order->service_id;
		$summary['order_id'] = $order->id;
		$summary['final_total'] = $final_amount;
		session()->put('extras_purchased_data', $summary);

		if($auth_user->earning == 0){
			$fromWalletAmount = 0;
		}elseif($auth_user->earning >= $summary['final_total']){
			$fromWalletAmount = $summary['final_total'];
		}else{
			$fromWalletAmount = $auth_user->earning;
		}

		if($auth_user->promotional_fund == 0){
			$fromPromotionalAmount = 0;
		}elseif($auth_user->promotional_fund >= $summary['final_total']){
			$fromPromotionalAmount = $summary['final_total'];
		}else{
			$fromPromotionalAmount = $auth_user->promotional_fund;
		}

		if(Auth::user()->parent_id != 0) {
			$fromPromotionalAmount = 0;
			if(User::check_sub_user_permission('can_use_wallet_funds') == false) {
				$fromWalletAmount = 0;
			}
		}
		$settings = Setting::find(1);

		return view('frontend.new_cart.payment_option_for_extras',compact('summary','fromWalletAmount','fromPromotionalAmount','settings'));

	} 

}