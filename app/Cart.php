<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;
use Auth;
use App\ServicePlan;

class Cart extends Model
{
	public $table  = 'cart';

	public function service()
	{
		return $this->belongsTo('App\Service','service_id','id')->withoutGlobalScope('is_course');
	}
	public function plan()
	{
		return $this->belongsTo('App\ServicePlan','plan_id','id');
	}

	public function extra(){
		return $this->hasMany('App\CartExtra','cart_id','id');
	}

	public function coupon(){
		return $this->hasOne('App\Coupan','id','coupon_id')->where('is_delete', '0');
	}
	public function check_is_single_discount($discountPriority,$row,$buyerPromo=array() ,$cart = array()){
		$userObj = new User;
		$is_single = '';
		foreach ($discountPriority as $priority) {
			if($priority->discount_type == 'reorder_promo'){
				if(count($buyerPromo) > 0 && $buyerPromo->is_combined_other == 1){
					/* 1- can not be combined with other discounts*/
					$is_single = $priority->discount_type;
					break;
				}
			}elseif($priority->discount_type == 'coupan'){
				if($row->coupon && $row->coupon->is_combined_other == 1){
					$is_single = $priority->discount_type;
					break;
				}
			}elseif($priority->discount_type == 'volume_discount'){
				if($userObj->is_premium_seller($row->service->uid)){
					$v_discount_per = 0;
					$volume_key = 0;
					foreach($row->service->volume_discount as $key1 => $value1){
						if($row->quantity >= $value1->volume){
							$v_discount_per = $value1->discount;
							$volume_key = $key1;
						}
					}
					if($v_discount_per > 0){
						if($row->service->volume_discount && $row->service->volume_discount[$volume_key]->is_combined_other == 1){
							$is_single = $priority->discount_type;
							break;
						}
					}
				}
			}elseif($priority->discount_type == 'combo_discount'){
				if($userObj->is_premium_seller($row->service->uid)){
					if(count($cart) > 1){
						$combo_detail = $this->check_is_combo($row->service_id,$cart);
						if($combo_detail->is_combined_other == 1){
							$is_single = $priority->discount_type;
							break;
						}
					}
				}
			}
		}
		return $is_single;
	}

	public function check_is_combo($service_id,$cart){
		$is_product_combo = 0;
		$combo_discount_per = 0;
		$is_combined_other = 0;
		$bundle_id = 0;
		
		$bundleDiscount = BundleDiscount::whereHas('bundle_services',function($q) use ($service_id){
				$q->where('service_id',$service_id)->select('id');
			})->get();

		if(count($bundleDiscount) > 0){

			/*Get cart all service list*/
			$cart_serive_ids = [];
			foreach ($cart as $key => $value) {
				$cart_serive_ids[] = $value->service_id;
			}
			
			foreach ($bundleDiscount as $key => $value) {

				/*Get combo all service list*/
				$bundle_service_ids = [];
				foreach ($value->bundle_services as $bundle_services) {
					$bundle_service_ids[] = $bundle_services->service_id;
				}

				/*Check cart services exists in combo service*/
				$result = array_intersect($cart_serive_ids, $bundle_service_ids);
				if(!empty($result)){
					$result = array_unique($result);
					if(count($result) == count($bundle_service_ids)){
						$is_product_combo = 1;
						if($value->discount > $combo_discount_per){
							$combo_discount_per = $value->discount;
							$is_combined_other = $value->is_combined_other;
							$bundle_id = $value->id;
						}
					}
				}
			}
		}
		return (object)['is_product_combo'=>$is_product_combo,'combo_discount_per'=>$combo_discount_per,'is_combined_other'=>$is_combined_other,'bundle_id' => $bundle_id];
	}

	public function user_email(){
		return $this->hasOne('App\User','id','uid')->select('id', 'email','Name');
	}

	public function service_plan(){
		return $this->hasMany('App\ServicePlan','service_id','service_id');
	}

	public function remove_unwanted_services(){
		$uid = User::get_parent_id();
		$cart = Cart::where('uid', $uid)->get();

		$is_recurring_service = 0;
		foreach($cart as $index => $row){
			if($is_recurring_service == 0 && $row->service->is_recurring == 1){
				$is_recurring_service = 1;
			}
			if($row->plan->plan_type == 'monthly_access') {
				$is_recurring_service = 1;
			}
		}
		if($is_recurring_service == 1) {
			$cart = Cart::get_recurring_from_cart();
		}
		
		$message = "Some of services are not longer available, so it's removed from your cart.";
		
		if(count($cart) > 0){
			foreach($cart as $row){
				
				$servicePlanCheck = ServicePlan::where('service_id',$row->service_id)->find($row->plan_id);
	            if(count($servicePlanCheck) == 0){
					/*delete services which have not proper plan ID*/
					\Session::flash('errorFails', $message);
	                $row->delete();
	            }elseif($row->service->is_delete != 0) {
					/*delete services which are deleted*/
					\Session::flash('errorFails', $message);
					$row->delete();
				}elseif($row->service->status != 'custom_order' && $row->service->status != 'active') {
					/*delete services which are not active*/
					\Session::flash('errorFails', $message);
					$row->delete();
				}elseif($row->service->allow_backorders == 0){
					/*Check for order in queue*/
					$allowbackOrder = $row->service->allowBackOrder();
					if($allowbackOrder->can_place_order == false){
						/*No of order in queue has been over*/
						\Session::flash('errorFails', $message);
						$row->delete();
					}
				}
				
				if($row->is_review_edition == 1){
					//Verify review edition
					if($row->service->is_allow_review_edition() == false){
						\Session::flash('errorFails', $message);
						$row->delete();
					}else{
						// verify that review edition service exists with other plan
						$check_exists = Cart::select('id')->where('uid', $uid)->where('is_review_edition',1)->where('service_id',$row->service_id)->count();
						if($check_exists > 1){
							\Session::flash('errorFails', $message);
							$row->delete();
						}else{
							//Change qty if set more
							if($row->quantity > 1){
								$row->quantity = 1;
								$row->save();
							}
						}
					}
				}

				if($row->is_course == 1){
					if($row->quantity > 1){
						// Check course quantity is greater than 1? than set it 1
						$row->quantity = 1;
						$row->save();
					}
					// Check course already purchased, than remove it from cart
					$purchaseDetails = Service::purchaseCourseDetails($row->service_id,$uid);
					
					if(count($purchaseDetails) > 0){
						//Check  order is on dispute process?
						if($purchaseDetails->is_dispute == 1 && $purchaseDetails->dispute_favour == 0){
							\Session::flash('errorFails', $message);
							$row->delete();
						}elseif($purchaseDetails->is_recurring == 0){
							// Lifetime plan already purchased : no any plan can purchase
							\Session::flash('errorFails', $message);
							$row->delete();
						}else{
							if($row->plan->plan_type == 'monthly_access'){
								// Monthly plan already purchased
								\Session::flash('errorFails', $message);
								$row->delete();
							}else{
								//Check payment received for monthly active plan?
								if($purchaseDetails->subscription->is_payment_received == 0){
									\Session::flash('errorFails', $message);
									$row->delete();
								}

								// can purchase lifetime with cancel old monthly plan
							}
						}
					}
				}
				
				$block_users = User::getBlockedByIds();
				if(in_array($row->service->uid, $block_users)){
					/* Check Blocked Users */
					\Session::flash('errorFails', $message);
					$row->delete();
				}

				/* Check Extras service */
				if(count($row->extra) > 0){
					foreach ($row->extra as $extra) {
						if($extra->service_extra == ""){
							$extra->delete();
						}
					}
				}
			}
		}
		//Remove all expired/deleted coupons
		$this->remove_expired_coupon($cart);
		return true;
	}

	//Remove all expired/deleted coupons
	function remove_expired_coupon($cart){
		foreach($cart as $row){
			if($row->coupon){
				$is_valid = true;
				$message = 'Some of applied coupon are not longer available.';

				$is_course = $row->is_course;

				//Check for expiry date and is delete
				if($row->coupon->expiry_date < date('Y-m-d') || $row->coupon->is_delete == 1) {
					$is_valid = false;
				}

				if($is_course == 0){
					/* Check promo is valid for recurring service or not */
					if($row->service->is_recurring == 1 && $row->coupon->allow_on_recurring_order == 0){
						$is_valid = false;
					}

					/* Check promo for normal service */
					if($row->service->is_recurring == 0 && $row->coupon->allow_on_recurring_order == 1){
						$is_valid = false;
					}

				}else{
					//Check for recurring monthly course
					if($row->plan->plan_type == "monthly_access"){
						if($row->coupon->allow_on_recurring_order == 0){
							$is_valid = false;
						}
					}else{
						if($row->coupon->allow_on_recurring_order == 1){
							$is_valid = false;
						}
					}
				}

				//Check for coupon usage limit 
				$coupon_applied_count = CoupanApplied::where('coupan_code_id', $row->coupon_id)->count();
				if($coupon_applied_count >= $row->coupon->no_of_uses) {
					$is_valid = false;
				}

				if($is_valid == false){
					\Session::flash('errorFails', $message);
					$row->coupon_id = 0;
					$row->save();
				}
			}
		}
	}

	public static function remove_recurring_from_cart(){
		$buyerId = get_user_id();
		Cart::select('cart.*')->where('cart.uid',$buyerId)
		->leftjoin("services", "services.id", '=', 'cart.service_id')
		->leftjoin("service_plan", "service_plan.id", '=', 'cart.plan_id')
		->whereRaw("(services.is_recurring = 1 || service_plan.plan_type = 'monthly_access')")->delete();
	}

	public static function get_recurring_from_cart($direct_checkout=0){
		$buyerId = get_user_id();
		return Cart::select('cart.*')->where('cart.uid',$buyerId)
		->where('cart.direct_checkout',$direct_checkout)
		->leftjoin("services", "services.id", '=', 'cart.service_id')
		->leftjoin("service_plan", "service_plan.id", '=', 'cart.plan_id')
		->whereRaw("(services.is_recurring = 1 || service_plan.plan_type = 'monthly_access')")
		->OrderBy('id','desc')->get();
	}
}
