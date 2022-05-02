<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use App\Coupan;
use App\Service;
use App\ServicePlan;
use App\CoupanApplied;
use App\User;
use Auth;

class CoupanController extends Controller
{
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

	public function index($id,$type = ''){
		$service = Service::withoutGlobalScope('is_course')->where('uid',$this->uid)->find($id);
		if(count($service) == 0){
			return redirect('404');
		}else{
			$coupans = Coupan::with('service','coupan_applied')->where(['service_id'=>$id,'is_delete'=>0,'coupon_type'=>0,'user_id'=>$this->uid])->get();
			return view('frontend.service.coupan.coupan',['id'=>$id,'coupans'=>$coupans,'type'=>$type,'service'=>$service]);
		}

	}

	public function showAddCoupan($id,$type = ''){
		$service = Service::withoutGlobalScope('is_course')->where('uid',$this->uid)->find($id);
		if(count($service) == 0){
			return redirect('404');
		}

		$ServicePlan = ServicePlan::where(['service_id'=>$id])->get();
		if(count($ServicePlan) == 0){
			return redirect('404');
		}
		return view('frontend.service.coupan.addcoupan',['id'=>$id,'ServicePlan'=>$ServicePlan,'type'=>$type]);
	}

	public function coupanSubmit(Request $request){
		
		$service = Service::withoutGlobalScope('is_course')->where('uid',$this->uid)->find($request->service_id);
		if(count($service) == 0){
			return redirect('404');
		}

		$type = $request->plan_type;
		$service_id = $request->service_id;
		$coupanCount = Coupan::where('coupan_code',$request->coupan_code)
								->where(function($query) use ($service_id)  {
									$query->where('service_id',$service_id)
									->orWhere('coupon_type',1);
								})
								->where('is_delete',0)
								->where('user_id',$this->uid)
								->count();
		if($coupanCount > 0){
			$request->session()->flash('errorFails', 'Coupon Code has already exist please try other');
			return redirect()->route('coupan',[$request->service_id,$type]);
		}else{

			$coupans = new Coupan;
			$coupans->service_id = $request->service_id;
			$coupans->coupan_code = $request->coupan_code;
			$coupans->expiry_date = date('Y-m-d',strtotime($request->expiry_date));
			$coupans->no_of_uses =$request->no_of_uses;
			$coupans->discount =$request->discount;
			$coupans->discount_type =$request->payment_method;
			$coupans->user_id = $this->uid;
			$coupans->coupon_type = 0;
			if($request->has('is_follower_mail_disabled') && $request->is_follower_mail_disabled == 1){
				$coupans->is_follower_mail_sent = 0;
			}else{
				$coupans->is_follower_mail_sent = 1;
			}
			
			if($request->filled('is_combined_other') && $request->is_combined_other == 1) {
				$coupans->is_combined_other = 1;
			}
			$coupans->save();

			return redirect()->route('coupan',[$request->service_id,$type]);
		}
	}

	public function showcoupanEdit($id,$type){
		$coupans = Coupan::with('service_plan')->where('id',$id)->where('user_id',$this->uid)->first();

		$service = Service::withoutGlobalScope('is_course')->where('uid',$this->uid)->find($coupans->service_id);
		if(count($service) == 0){
			return redirect('404');
		}

		return view('frontend.service.coupan.editcoupan',['id'=>$id,'coupan'=>$coupans,'type' => $type]);
	}

	public function submitCoupanEdit(Request $request, $id, $type){

		$service = Service::withoutGlobalScope('is_course')->where('uid',$this->uid)->find($request->service_id);
		if(count($service) == 0){
			return redirect('404');
		}

		$service_id = $request->service_id;
		$coupanCount = Coupan::where('coupan_code',$request->coupan_code)
			->where('id','<>',$id)
			->where('is_delete',0)
			->where(function($query) use ($service_id)  {
				$query->where('service_id',$service_id)
				->orwhere('coupon_type',1);
			})
			->where('user_id',$this->uid)
			->count();

		if($coupanCount > 0){
			$request->session()->flash('errorFails', 'Coupon Code has already exist, please try other');
		}

		$coupanApplied = CoupanApplied::where('coupan_code_id',$id)->count();
		if($coupanApplied <= $request->no_of_uses){
			$input = $request->input();
			$input['expiry_date'] = date('Y-m-d',strtotime($request->expiry_date));
			if($request->filled('is_combined_other') && $request->is_combined_other == 1){
				$input['is_combined_other'] = 1;
			}else{
				$input['is_combined_other'] = 0;
			}
			
			unset($input['_token']);
			Coupan::where('id', $id)->update($input);
			
			$request->session()->flash('errorSuccess', 'Coupon updated successfully');
		}else{
			$request->session()->flash('errorFails', 'Number of uses must be greater then '.$coupanApplied);
		}
		
		return redirect()->route('coupan',[$request->service_id,$type]);
	}

	public function coupanDelete(Request $request){

		$coupanApplied = CoupanApplied::where('coupan_code_id',$request->id)->count();
		if($coupanApplied > 0){
			Coupan::where('id', $request->id)->update(['is_delete'=>1]);
		}else{
			Coupan::where('id', $request->id)->delete();
		}
		
		return response()->json([
			'status' => 200,
			'message' => "Coupon Deleted Successfully.",
			]);
	}

	/* general coupons */
	public function seller_coupons(Request $request){
		/* Sub user check permission */ 
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}

		$uid = $this->uid;
		$coupans = Coupan::with('service','coupan_applied')->where(['user_id'=>$uid,'is_delete'=>0,'coupon_type'=>1])->get();
		return view('frontend.service.general_coupon.coupan',compact('coupans'));
	}

	public function add_general_coupon(Request $request){
		/* Sub user check permission */ 
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}
		return view('frontend.service.general_coupon.addcoupan');
	}

	public function save_add_general_coupon(Request $request){
		/* Sub user check permission */ 
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}

		$uid = $this->uid;
		$coupanCount = Coupan::where('coupan_code',$request->coupan_code)->where('user_id',$uid)->where('is_delete',0)->first();
		if(!is_null($coupanCount) && count($coupanCount) > 0){
			if($coupanCount->service_id != 0) {
				$msg = "Coupon Code has already used in a service (".$coupanCount->service->title."), Please try different code.";
			} else {
				$msg = "Coupon Code has already used. Please try other.";
			}
			$request->session()->flash('errorFails', $msg);
			return redirect()->route('seller_coupons');
		}else{
			$coupans = new Coupan;
			$coupans->service_id = 0;
			$coupans->user_id = $uid;
			$coupans->coupan_code = $request->coupan_code;
			$coupans->expiry_date = date('Y-m-d',strtotime($request->expiry_date));
			$coupans->no_of_uses =$request->no_of_uses;
			$coupans->discount =$request->discount;
			$coupans->discount_type =$request->payment_method;
			$coupans->coupon_type = 1;
			if($request->has('is_follower_mail_disabled') && $request->is_follower_mail_disabled == 1){
				$coupans->is_follower_mail_sent = 0;
			}else{
				$coupans->is_follower_mail_sent = 1;
			}
			if($request->filled('is_combined_other') && $request->is_combined_other == 1) {
				$coupans->is_combined_other = 1;
			}

			if($request->filled('allow_on_recurring_order') && $request->allow_on_recurring_order == 1) {
				$coupans->allow_on_recurring_order = 1;
			}else{
				$coupans->allow_on_recurring_order = 0;
			}
			
			$coupans->save();

			return redirect()->route('seller_coupons');
		}
	}

	public function edit_general_coupon(Request $request,$enc_id){
		/* Sub user check permission */ 
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}

		$id = Coupan::getDecryptedId($enc_id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
		}
		$uid = $this->uid;
		$coupan = Coupan::where(['user_id'=>$uid,'id'=>$id])->first();
		return view('frontend.service.general_coupon.editcoupan',compact('coupan'));
	}

	public function save_edit_general_coupon(Request $request){
		/* Sub user check permission */ 
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}

		$enc_id = $request->secret;
		$id = Coupan::getDecryptedId($enc_id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
		}
		$uid = $this->uid;
		$coupanCount = Coupan::where('coupan_code',$request->coupan_code)
			->where('id','<>',$id)
			->where('is_delete',0)
			->where('user_id',$uid)
			->first();

		if(!is_null($coupanCount) && count($coupanCount) > 0){
			if($coupanCount->service_id != 0) {
				$msg = "Coupon Code has already used in a service (".$coupanCount->service->title."), Please try different code.";
			} else {
				$msg = "Coupon Code has already used. Please try other.";
			}
			$request->session()->flash('errorFails', $msg);
			return redirect()->route('seller_coupons');
		}

		$coupanApplied = CoupanApplied::where('coupan_code_id',$id)->count();
		if($coupanApplied <= $request->no_of_uses){
			$input = $request->input();
			$input['expiry_date'] = date('Y-m-d',strtotime($request->expiry_date));
			if($request->filled('is_combined_other') && $request->is_combined_other == 1){
				$input['is_combined_other'] = 1;
			}else{
				$input['is_combined_other'] = 0;
			}

			if($request->filled('allow_on_recurring_order') && $request->allow_on_recurring_order == 1) {
				$input['allow_on_recurring_order'] = 1;
			}else{
				$input['allow_on_recurring_order'] = 0;
			}
			
			unset($input['_token']);
			unset($input['secret']);
			Coupan::where('id', $id)->where('user_id',$uid)->update($input);
			
			$request->session()->flash('errorSuccess', 'Coupon updated successfully');
		}else{
			$request->session()->flash('errorFails', 'Number of uses must be greater then '.$coupanApplied);
		}
		return redirect()->route('seller_coupons');
	}

	public function delete_general_coupon(Request $request,$enc_id){
		/* Sub user check permission */ 
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}
		
		$uid = $this->uid;
		$id = Coupan::getDecryptedId($enc_id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
		}
		
		$coupanApplied = CoupanApplied::where('coupan_code_id',$id)->count();
		if($coupanApplied > 0){
			Coupan::where('id', $id)->where('user_id',$uid)->update(['is_delete'=>1]);
		}else{
			Coupan::where('id', $id)->where('user_id',$uid)->delete();
		}
		$request->session()->flash('errorSuccess', 'Coupon deleted successfully');
		return redirect()->route('seller_coupons');
	}
	
	public function updatePromotionStatus(Request $request ){
		$uid = $this->uid;
		$id = $request->id;
		$id = Coupan::getDecryptedId($id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
		}
		
		$coupan = Coupan::where('id', $id)->where('user_id',$uid)->first();
		if($coupan == null ){
			return response()->json([
				'status' => 400,
				'message' => 'Something went worng please try again ',
			]);
		}

		if($request->status == 'Active'){
			$Service = Service::where(['status' => 'active'])->where('is_private', 0);
			/* Only Added Promotion Page */
			$Service = $Service->whereHas('coupon', function($query)  {
				$query->where('is_promo', '1');
				$query->where('is_delete', 0)->select('id');
			});
			$Service = $Service->where('uid',$uid)->where('is_delete',0)->count();
			if($Service >= 3 ){
				return response()->json([
					'status' => 400,
					'message' => 'You cannot select more than three services to promote service.',
				]);
			}
		}
		$coupanServiceCount = Coupan::where('service_id', $coupan->service_id)->where('user_id',$uid)->count();
		if($coupanServiceCount > 0 && $request->status == 'Active'){
			Coupan::where('service_id', $coupan->service_id)->where('user_id',$uid)->update(['is_promo'=>'0']);
		}	
		$coupan->is_promo = ($request->status == 'Active' ) ? '1' : '0' ;
		$coupan->save();
		return response()->json([
		'status' => 200,
		'message' => 'Promotion successfully '.$request->status,
		]);
	}
}
