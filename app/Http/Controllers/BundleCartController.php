<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Service;
use App\Cart;
use App\CartExtra;
use App\BundleCartService;
use App\BundleCart;
use App\BundleCartExtraService;
use App\AffiliateEarning;
use App\BuyerReorderPromo;
use App\Specialaffiliatedusers;
use App\Withdraw;
use App\Coupan;
use App\CoupanApplied;
use App\BoostedServicesOrder;
use App\ServiceQuestion;
use App\User;
use App\CartCombo;
use App\DiscountPriority;
use App\ServiceExtra;
use App\Setting;
use App\GeneralSetting;
use Illuminate\Support\Facades\Log;
use Mail;
use DB;
use AWS;
use Cookie;
use Auth;

class BundleCartController extends Controller
{
    //
    private $uid;
    protected $provider;
	public function __construct(){
		$this->middleware(function ($request, $next) {
			if(Auth::check()) {	
				$this->uid = Auth::user()->id;
				if(Auth::user()->parent_id != 0){
					$this->uid = Auth::user()->parent_id;
				}
			}
			return $next($request);
        }); 
    }

    /**
     * Bundle cart index
     */

    Public function index(){
        $bundleCart = BundleCart::where('uid',$this->uid)->orderBy('created_at','desc')->paginate(10);
        return view('frontend.bundle.index',compact('bundleCart'));
    }

	public function removeEmptyBundle(){
		$bundleCart = BundleCart::with('budlecartservice')->where('uid',$this->uid)->whereHas('budlecartservice', function($query) {
			$query->where('is_recurring', 1)->select('id');
		})->get();
	}
    /**
     * Bundle cart Details 
     * 
     */
    Public function bundleCartDetails(Request $request,$secret){
		
		$id = BundleCart::getDecryptedId($secret);
		try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
		}
		$userObj = new User;
		$bundleCartObj = new BundleCartService;

		/*Begin : Remove unwanted services*/
		$bundleCartObj->remove_unwanted_services($this->uid);
		
        $bundleCart = BundleCart::where('id',$id)->where('uid',$this->uid)->orderBy('created_at','desc')->first();
        
		$cart = BundleCartService::with('extra','service.images','service.extra','service.user','plan','extra','coupon','service_plan')->where('bundle_cart_id',$bundleCart->id)->where('uid',$this->uid)->OrderBy('id','desc')->get();

		if(count($cart) == 0){
			$bundleCart = BundleCart::where('id',$id)->where('uid',$this->uid)->orderBy('created_at','desc')->delete();
			return redirect(route('view_cart'));
		}
		$cartSubtotal = $selectedExtra = $totalDeliveryTime = $totalDeliveryTimeExtra = $totalPromoDiscount = $addDiscount = 0;  

		$is_recurring_service = 0;
		foreach($cart as $index => $row){
			if($is_recurring_service == 0 && $row->service->is_recurring == 1){
				$is_recurring_service = 1;
			}
		}
		if($is_recurring_service == 1) {
			$cart = BundleCartService::with('extra','service.images','service.extra','service.user','plan','coupon','service_plan')
			->whereHas('service', function($query) {
				$query->where('is_recurring', 1)->select('id');
			})
			->where('uid',$this->uid)->OrderBy('id','desc')->get();
		}
		foreach($cart as $index => $row){
			if($is_recurring_service == 0 && $row->service->is_recurring == 1){
				$is_recurring_service = 1;
			}
			$cartSubtotal += ($row->plan->price * $row->quantity);
			$totalDeliveryTime +=  $row->plan->delivery_days;

			$buyerPromo = null;
			if($row->remove_reorder_promo == 0){
				$buyerPromo = BuyerReorderPromo::where('seller_id',$row->service->uid)
				->where('buyer_id',$this->uid)
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
				if($row->service->images[0]->photo_s3_key != ''){
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
		
		$sponsered_cart = $this->get_sponsered_cart();
		$summary = $this->cart_summary_calculation($cart);
		$is_custom_order = 0;
		$is_job = 0;
		if($is_recurring_service == 0){
			if(Auth::user()->earning == 0){
				$fromWalletAmount = 0;
			}elseif(Auth::user()->earning >= $summary['final_total']){
				$fromWalletAmount = $summary['final_total'];
			}else{
				$fromWalletAmount = Auth::user()->earning;
			}
			if(Auth::user()->promotional_fund == 0){
				$fromPromotionalAmount = 0;
			}elseif(Auth::user()->promotional_fund >= $summary['final_total']){
				$fromPromotionalAmount = $summary['final_total'];
			}else{
				$fromPromotionalAmount = Auth::user()->promotional_fund;
			}
		}else{
			$fromWalletAmount = 0;
			$fromPromotionalAmount = 0;
		}
		$settings = Setting::find(1);
        return view('frontend.bundle.details_new', compact('bundleCart','cart','is_recurring_service','buyerPromo','userObj','sponsered_cart','summary','is_custom_order','is_job','fromWalletAmount','settings','fromPromotionalAmount','secret'));
    }


    public function cart_summary_calculation($Cart) {
		$userObj = new User;
		$cartObj = new CartCombo;

        $cartSubtotal = $selectedExtra = $totalDeliveryTime = $totalDeliveryTimeExtra = $totalPromoDiscount = $totalCoupenDiscount = $totalVolumeDiscount = $totalComboDiscount = 0; 
		
		$all_services = [];

		$discountPriority = DiscountPriority::OrderBy('priority','desc')->get();
		$is_recurring_service = 0;

		foreach($Cart as $index => $row) {
			$all_services[$index]['cart_id'] = $row->id;
			$all_services[$index]['title'] = $row->service->title;
			$all_services[$index]['delivery_days'] = $row->plan->delivery_days;
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

		    /* + $cart_extra_price_total */
			/*Check for priority*/
			$buyerPromo = null;
			if($row->remove_reorder_promo == 0){
				$buyerPromo = BuyerReorderPromo::where('seller_id',$row->service->uid)
				->where('buyer_id',Auth::user()->id)
				->where('service_id',$row->service->id)
				->where('is_used',0)
				->first();
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
    
    /***
     * 
     * Bundle cart  save in cart 
     */
    public function bundleCartProcess(Request $request,$secret){
		$id = BundleCart::getDecryptedId($secret);
		try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
		}

		$bundleCart = BundleCart::where('id',$id)->first();
		/* Check Blocked Users */
		$is_block = User::isBlockMyProfile($this->uid, $bundleCart->uid);
		if($is_block == 1){
			\Session::flash('errorFails', 'Due to blocked user cart items not added. ');
			return redirect(route('view_cart'));
		}
		$is_recurring_service = 0;
        if($bundleCart != null  && isset($bundleCart->budlecartservice) && count($bundleCart->budlecartservice) > 0){
			
			$settings = Setting::find(1)->first();
			$abandoned_cart = json_decode($settings->abandoned_cart_email);

			foreach($bundleCart->budlecartservice as $index => $row){
				if($is_recurring_service == 0 && $row->service->is_recurring == 1){
					$is_recurring_service = 1;
				}
			}
			if($is_recurring_service == 1) {
				$cart = cart::whereHas('service', function($query) {
					$query->where('is_recurring', 1)->select('id');
				})
				->where('uid',$this->uid)->OrderBy('id','desc')->delete();
			}
            foreach($bundleCart->budlecartservice as $key=>$value){		
				$cart = Cart::with('extra')->where('uid',$this->uid)->where('plan_id',$value->plan_id)->where('service_id',$value->service_id)->first();
				
				if($cart == null){
					$cart = new Cart;
					$cart->email_index = 1;
					$cart->email_send_at = date('Y-m-d H:i:s', strtotime(' + '.$abandoned_cart[0]->duration.' '.$abandoned_cart[0]->span.'s'));
					
					$cart->quantity= ($value->quantity == null ) ? 0 : $value->quantity;
				}else{
					if($value->service != "" && $value->service->is_course == 1){
						$cart->quantity= 1;
					}else{
						$cart->quantity= $cart->quantity + $value->quantity;
					}
				}
				$cart->uid= $this->uid;
				$cart->plan_id= $value->plan_id;
				$cart->service_id=$value->service_id;
				$cart->is_job=$value->is_job;
				$cart->coupon_id=0;
				$cart->save();
				if(isset($value->extra) && count($value->extra) > 0 ){
					foreach($value->extra as $ekey => $evalue){
						$cartExtra = CartExtra::where('cart_id',$cart->id)->where('service_extra_id',$evalue->service_extra_id)->first();
						if($cartExtra == null){
							$cartExtra = new CartExtra;
							$cartExtra->qty = $evalue->qty;
						}else{
							$cartExtra->qty = $cartExtra->qty + $evalue->qty;
						}
						$cartExtra->cart_id = $cart->id;
						$cartExtra->service_extra_id = $evalue->service_extra_id;
						$cartExtra->save();
					}
				}
				$check_recurring = Service::select('id')->where('id',$value->service_id)->where('is_recurring',1)->count();
				if($check_recurring > 0) {
					break;
				}
            }
        }
        return redirect(route('view_cart'));
	}
	
    public function bundleCartNameCheck(Request $request){
		$bundleCart = BundleCart::where('uid',$this->uid)->where('name',$request->name)->first();
        if ($useremail === null) {
			$isValid = true;
			$message = "Title is not exists.";
        } else {
			$isValid = false;
			$message = "Title is already exists.";
        }
        return json_encode(array(
            'valid' => $isValid,
            "message" => $message
        ));
	}

    /**
     * Cart value save on bundle
     * 
     */
    public function BundleCartService(Request $request){
        $cart = Cart::where('uid',$this->uid)->get();
        $code = 400;
		$message = 'Your cart is empty.';
        if(count($cart) > 0){
			$bundleCart = BundleCart::where('uid',$this->uid)->where('name',$request->name)->first();
			if($bundleCart != null){
				
				$code = 400;
				$message = 'Title is already exists.';
				return response()->json([
					'status' => $code,
					'message' => $message,
					]);
			}
			$normalServiceCount = 0;
			foreach($cart as $index => $row){
				if($is_recurring_service == 0 && $row->service->is_recurring == 1){
					$is_recurring_service = 1;
				}

				/*if($row->service->status != 'active'){
					$message = 'Service '.$row->service->title. ' is paused or In-active.';
					return response()->json([
						'status' => $code,
						'message' => $message,
						]);
				}*/

				if($row->service->is_job == 1){
					continue;
				}
				if($row->service->is_custom_order == 1){
					continue;
				}
				$normalServiceCount++;
			}
			
			if($normalServiceCount == 0){
				return response()->json([
					'status' => $code,
					'message' => 'Custom orders or Jobs can not be saved as part of bundles.Please add one normal service.',
					]);
			}

			if($is_recurring_service == 1) {
				$cart = Cart::whereHas('service', function($query) {
					$query->where('is_recurring', 1)->select('id');
				})
				->where('uid',$this->uid)->OrderBy('id','desc')->get();
			}
			$bundleCartLimit = GeneralSetting::where('settingkey','bundle_cart_limit')->first();
			$bundleCount = BundleCart::select('id')->where('uid',$this->uid)->count();
			if($bundleCartLimit->settingvalue <= $bundleCount){
				$code = 400;
				$message = 'Bulk limit reached.';
				return response()->json([
					'status' => $code,
					'message' => $message,
					]);
			}
            $bundlecart = new BundleCart;
            $bundlecart->uid = $this->uid;
            $bundlecart->name = $request->name;
            $bundlecart->save();
            foreach($cart as $key => $value){
                if($value->is_custom_order == 0 && $value->is_job == 0){
                    $bundleCartService = new BundleCartService;
                    $bundleCartService->bundle_cart_id  = $bundlecart->id;
                    $bundleCartService->uid  = $this->uid;
                    $bundleCartService->quantity = $value->quantity;
                    $bundleCartService->plan_id   = $value->plan_id ;
                    $bundleCartService->service_id   = $value->service_id ;
                    $bundleCartService->coupon_id   = $value->coupon_id ;
                    $bundleCartService->is_custom_order   = 0;
                    $bundleCartService->is_job    = $value->is_job  ;
                    $bundleCartService->remove_reorder_promo   = $value->remove_reorder_promo ;
                    $bundleCartService->save();

                    if(isset($value->extra) && count($value->extra) > 0){
                        foreach($value->extra as $extraKey => $extraValue){
                            $BundleCartExtraService = new BundleCartExtraService;
                            $BundleCartExtraService->bundle_cart_service_id =$bundleCartService->id;
                            $BundleCartExtraService->service_extra_id =$extraValue->service_extra_id ;
                            $BundleCartExtraService->qty =$extraValue->qty;
                            $BundleCartExtraService->save();
                        }
                    }
                } 
            }
            $message = 'Bundle saved successfully.';
            $code = 200;
        }
		return response()->json([
        'status' => $code,
        'message' => $message,
        ]);
    }

    /**
     * 
     * bundle Cart delete 
     */
    public function delete(Request $request, $secret){
		$id = BundleCart::getDecryptedId($secret);

        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
		}
		
        $bundleCart = BundleCart::where('uid',$this->uid)->where('id',$id)->first();
        if($bundleCart != null){
            if(isset($bundleCart->budlecartservice) && count($bundleCart->budlecartservice) > 0){
                foreach($bundleCart->budlecartservice as $key =>$value){
                    if(isset($value->extra) && count($value->extra) > 0){
                        foreach($value->extra as $eKey => $eValue){
                            $eValue->delete();
                        }
                    }
                    $value->delete();
                }
            }
            $bundleCart->delete(); 
            \Session::flash('errorSuccess', 'Bundle deleted successfully ');
            return redirect()->back();
        }
        \Session::flash('errorFails', 'Bundle not found');
        return redirect()->back();
	}
	
	/***
	 * Remove cart 
	 * 
	 */
	public function bundle_remove_cart (Request $request) {
		$id = $request->input('id');
		BundleCartExtraService::where('bundle_cart_service_id', $id)->delete();
		$cart = BundleCartService::where('uid',$this->uid)->where(['id' => $id])->delete();
		if ($cart) {
			return response([
				'success' => true,
				'message' => 'Product removed from cart.'
			]);
		} else {
			return response([
				'success' => false,
				'message' => 'Something goes wrong.'
			]);
		}
	}
	/**
	 * 
	 * Update Bundle cart
	 */
	public function bundle_update_cart (Request $request) {
		$cart = BundleCartService::where('uid',$this->uid)->where('id', $request->id)->first();
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

	/**
	 * Extra addon added
	 */
	 
    public function bundle_update_add_ons(Request $request) {
        if(!$request->filled('cart_id') && !$request->filled('service_extra_id') && !$request->filled('qty')) {
			return response()->json(['status'=>'error', 'message'=>'Please fill all required parameters']);
		}
        $cart = BundleCartService::where('id', $request->cart_id)->first();
		if(is_null($cart)) {
			return response()->json(['status'=>'error' ,'message' => 'Invalid cart']);
		}
        $cartExtra = new BundleCartExtraService;
        $cartExtra->bundle_cart_service_id = $request->cart_id;
        $cartExtra->service_extra_id = $request->service_extra_id;
        $cartExtra->qty = $request->qty;
        $cartExtra->save();
		$total_cart_extra = BundleCartExtraService::where('bundle_cart_service_id',$request->cart_id)->select('id')->count();
        return response()->json(['status'=>'success','total_cart_extra'=>$total_cart_extra]);
	}
	
	/***
	 * Remove Extra Add on 
	 * 
	 */
	public function bundle_remove_add_ons(Request $request) {
        if(!$request->filled('cart_extra_id')) {
			return response()->json(['status'=>'error', 'message'=>'Please fill all required parameters']);
        }
        BundleCartExtraService::where('id', $request->cart_extra_id)->delete();
        
		$total_cart_extra = BundleCartExtraService::where('bundle_cart_service_id',$request->cart_id)->select('id')->count();
        return response()->json(['status'=>'success','total_cart_extra'=>$total_cart_extra]);
	}
	
	/***
	 * update Extra Add on 
	 * 
	 */

	public function bundle_update_extra_cart(Request $request) {
		$cart = BundleCartExtraService::where('id', $request->id)->first();
		if(is_null($cart)) {
			return response()->json(['status'=>'error']);
		}
		if($request->filled('quantity')) {
			$cart->qty = $request->quantity;
		}
		$cart->save();
		return response()->json(['status'=>'success']);
	}

}   