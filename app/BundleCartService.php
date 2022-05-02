<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BundleCartService extends Model
{
    public function service()
	{
		return $this->belongsTo('App\Service','service_id','id')->withoutGlobalScope('is_course');
	}
	public function plan()
	{
		return $this->belongsTo('App\ServicePlan','plan_id','id');
	}

	public function coupon(){
		return $this->hasOne('App\Coupan','id','coupon_id')->where('is_delete', '0');
    }
    
    public function extra(){
		return $this->hasMany('App\BundleCartExtraService','bundle_cart_service_id','id');
    }
    
    public function user_email(){
		return $this->hasOne('App\User','id','uid')->select('id', 'email','Name');
	}

	public function service_plan(){
		return $this->hasMany('App\ServicePlan','service_id','service_id');
	}

	
	public function remove_unwanted_services($userId){
		
		$cart = BundleCartService::where('uid', $userId)->get();

		$message = "Some of services are not longer available, so it's removed from your cart.";
		if(count($cart) > 0){
			foreach($cart as $row){
				$servicePlanCheck = ServicePlan::where('service_id',$row->service_id)->find($row->plan_id);
	            if(count($servicePlanCheck) == 0){
	            	/*delete services which have not proper plan ID*/
	                $row->delete();
	            }elseif($row->service->is_delete != 0) {
					/*delete services which are deleted*/
					$row->delete();
				}elseif($row->service->status != 'custom_order' && $row->service->status != 'active') {
					/*delete services which are not active*/
					$row->delete();
				}elseif($row->service->allow_backorders == 0){
					/*Check for order in queue*/
					$allowbackOrder = $row->service->allowBackOrder();
					if($allowbackOrder->can_place_order == false){
						/*No of order in queue has been over*/
						$row->delete();
					}
				}

				/* Check Blocked Users */
				$block_users = User::getBlockedByIds();
				if(in_array($row->service->uid, $block_users)){
					/* Check Blocked Users */
					\Session::flash('errorFails', $message);
					$row->delete();
				}
			}
		}
		return true;
	}
}
