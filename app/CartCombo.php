<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;
use Auth;
class CartCombo extends Model
{
	public $table  = 'cart_combos';
	protected $guarded =['*'];

	public function service()
	{
		return $this->belongsTo('App\Service','service_id','id')->withoutGlobalScope('is_course');
	}
	public function plan()
	{
		return $this->belongsTo('App\ServicePlan','plan_id','id');
	}

	public function extra(){
		return $this->hasMany('App\CartExtraCombo','cartcombo_id','id');
	}

	public function service_plan(){
		return $this->hasMany('App\ServicePlan','service_id','service_id');
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
}
