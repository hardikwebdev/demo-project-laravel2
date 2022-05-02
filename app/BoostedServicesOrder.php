<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Service;
use App\BoostedServicesOrdersDate;

class BoostedServicesOrder extends Model
{
	protected $fillable = [
		'id' ,'uid' ,'amount','order_no','service_id' ,'plan_id' ,'txn_id','receipt','start_date' ,'end_date' ,'payment_status','payment_by' ,'is_visited'
	];

	public function service(){
		return $this->belongsTo('App\Service','service_id','id');
	}

	public function boosting_plan(){
		return $this->belongsTo('App\BoostingPlan','plan_id','id');
	}

	public function boosting_assign_dates(){
		return $this->hasMany('App\BoostedServicesOrdersDate','boosted_order_id','id');
	}

	public static function get_cart_sponsor_startdate($service_id,$date = null){

		if($date == null){
			$date = date('Y-m-d', strtotime("+1 days"));

			/*Check for service is purchased or not*/
			$bootsOrdersPurchased = Self::where('plan_id', 7)
			->whereDate("end_date",">=",$date)
			->where('service_id',$service_id)
			->where('status','!=','cancel')
			->orderBy('created_at','desc')
			->first();


			if(count($bootsOrdersPurchased) > 0){
				$end_date = date('Y-m-d', strtotime($bootsOrdersPurchased->end_date));
				$date = date('Y-m-d', strtotime($end_date . "+1 days"));
			}
		}

		$bootsOrders = Self::where('plan_id', 7)
		->whereRaw(" '".$date."' between Date(start_date) AND Date(end_date)")
		->count();

		if($bootsOrders >= 5){
			$date = date('Y-m-d', strtotime($date . "+1 days"));
			return Self::get_cart_sponsor_startdate($service_id,$date);
		}
		return $date;
	}
	public static function get_category_sponser_dates($service_id,$category_slot,$total_days,$selected_pack){

		$from_check_date = date('Y-m-d', strtotime("+1 days"));
		$dates_array = [];
		$service = Service::where('id', '=', $service_id)->first();

		BoostedServicesOrdersDate::where('is_temp',1)->where('user_id',$service->uid)->delete();

		$bootsOrdersPurchasedDate = BoostedServicesOrdersDate::select('date')->whereIn('plan_id', [4,5])
			//->whereDate("category_id",$service->category_id)
			->orderBy('date','desc')
			->first();

		$to_check_date = date('Y-m-d', strtotime("+7 days"));
		if(!empty($bootsOrdersPurchasedDate)){
			if(strtotime($bootsOrdersPurchasedDate->date) >= time()){
				$to_check_date = date('Y-m-d', strtotime($bootsOrdersPurchasedDate->date . "+3 days"));
			}
		}

		$check_count = 1;
		if($category_slot == 2){
			$check_count = 2;
		}

		while($from_check_date <= $to_check_date){

			/*if($selected_pack == 4){
				$bootsOrdersPurchasedCount = BoostedServicesOrdersDate::select('date')->whereIn('plan_id', [4,5])
				->where('slot',$category_slot)
				->where("category_id",$service->category_id)
				->where("subcategory_id",$service->subcategory_id)
				->where('date',$from_check_date)
				->where('is_cancel',0)
				->whereRaw('((is_temp=1) OR (is_temp = 0 AND user_id='.$service->uid.'))')
				->count();



				if($bootsOrdersPurchasedCount == 0){
					$bootsOrdersPurchasedCount = BoostedServicesOrdersDate::select('date')->whereIn('plan_id', [4,5])
					->where('slot',$category_slot)
					->where("category_id",$service->category_id)
					->where('date',$from_check_date)
					->where('is_cancel',0)
					->whereRaw('((is_temp=1) OR (is_temp = 0 AND user_id='.$service->uid.'))')
					->count();
				}
			}else{
				$bootsOrdersPurchasedCount = BoostedServicesOrdersDate::select('date')->whereIn('plan_id', [4,5])
				->where('slot',$category_slot)
				->where("category_id",$service->category_id)
				->where('date',$from_check_date)
				->where('is_cancel',0)
				->whereRaw('((is_temp=1) OR (is_temp = 0 AND user_id='.$service->uid.'))')
				->count();

				if($bootsOrdersPurchasedCount == 0){
					$bootsOrdersPurchasedCount = BoostedServicesOrdersDate::select('date')->whereIn('plan_id', [4,5])
					->where('slot',$category_slot)
					->where("category_id",$service->category_id)
					->where("subcategory_id",$service->subcategory_id)
					->where('date',$from_check_date)
					->where('is_cancel',0)
					->whereRaw('((is_temp=1) OR (is_temp = 0 AND user_id='.$service->uid.'))')
					->count();
				}
			}*/

			$bootsOrdersPurchasedCount1 = BoostedServicesOrdersDate::select('date')->whereIn('plan_id', [4,5])
				->where('slot',$category_slot)
				->where("category_id",$service->category_id)
				->where("subcategory_id",$service->subcategory_id)
				->where('date',$from_check_date)
				->where('is_cancel',0)
				->whereRaw('((is_temp=0) OR (is_temp = 1 AND user_id='.$service->uid.'))')
				->count();

			$bootsOrdersPurchasedCount2 = BoostedServicesOrdersDate::select('date')->whereIn('plan_id', [4,5])
					->where('slot',$category_slot)
					->where("category_id",$service->category_id)
					->whereNull('subcategory_id')
					->where('date',$from_check_date)
					->where('is_cancel',0)
					->whereRaw('((is_temp=0) OR (is_temp = 1 AND user_id='.$service->uid.'))')
					->count();

			$bootsOrdersPurchasedCount = $bootsOrdersPurchasedCount1 + $bootsOrdersPurchasedCount2;

			/*if($category_slot == 1){
				if($bootsOrdersPurchasedCount == 0){
					$dates_array[] = $from_check_date;
					$from_check_date = date('Y-m-d', strtotime($from_check_date . "+1 days"));
				}
			}else{
				if($bootsOrdersPurchasedCount == 0){
					if($total_days == 1){
						$dates_array[] = $from_check_date;
						$from_check_date = date('Y-m-d', strtotime($from_check_date . "+1 days"));

					}else if($total_days == 2){
						$dates_array[] = $from_check_date;
						$from_check_date = date('Y-m-d', strtotime($from_check_date . "+1 days"));

						$dates_array[] = $from_check_date;
						$from_check_date = date('Y-m-d', strtotime($from_check_date . "+1 days"));

					}else if($total_days == 3){
						$dates_array[] = $from_check_date;
						$from_check_date = date('Y-m-d', strtotime($from_check_date . "+1 days"));

						$dates_array[] = $from_check_date;
						$from_check_date = date('Y-m-d', strtotime($from_check_date . "+1 days"));

						$dates_array[] = $from_check_date;
						$from_check_date = date('Y-m-d', strtotime($from_check_date . "+1 days"));
					}
				}
			}*/


			if($bootsOrdersPurchasedCount < $check_count){

				/*Check for service already exists for that date*/
				$checkServiceExistsOnDate = Self::whereIn('plan_id', [4,5])
					->where('service_id',$service_id)
					->where('status','!=','cancel')
					->whereHas('boosting_assign_dates',function($q) use($from_check_date){
						$q->where('date',$from_check_date)->select('id');
					})->count();

				if($checkServiceExistsOnDate == 0){
					$tempModel = new BoostedServicesOrdersDate;
					$tempModel->user_id = $service->uid;
					$tempModel->plan_id = $selected_pack;
					$tempModel->category_id = $service->category_id;
					if($selected_pack == 5){
						$tempModel->subcategory_id = $service->subcategory_id;
					}
					$tempModel->slot = $category_slot;
					$tempModel->date = $from_check_date;
					$tempModel->is_temp = 1;
					$tempModel->save();

					$dates_array[] = $from_check_date;
				}
			}

			$from_check_date = date('Y-m-d', strtotime($from_check_date . "+1 days"));

			if(count($dates_array) == $total_days){
				return $dates_array;
			}
		}
	}

	public function get_category_assign_startdate(){
		return $this->hasOne('App\BoostedServicesOrdersDate','boosted_order_id','id')->orderBy('date','asc');
	}

	public function get_category_assign_enddate(){
		return $this->hasOne('App\BoostedServicesOrdersDate','boosted_order_id','id')->orderBy('date','desc');
	}
}
