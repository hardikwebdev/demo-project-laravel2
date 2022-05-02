<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Service;
use App\Order;
use App\Category;
use App\Subcategory;
use App\UserLanguage;
use App\GeneralSetting;
use DB;
use Auth;
use Cookie;

class AffiliateController extends Controller
{
	private $lifetime = 24*60*7;
	private $uid;

	public function __construct(){
        $this->middleware(function ($request, $next) {
        	if(Auth::check()){
	            $this->uid = Auth::user()->id;
	            if(Auth::user()->parent_id != 0){
	                $this->uid = Auth::user()->parent_id;
	            }
            }
            return $next($request);
        });
    }

	public function AffiliateRedirect(Request $request,$affiliate_id = "",$secret_id = "")
	{
		if($request->is('promoteservice/*') == true)
		{
			$affiliate_type_cookie = Cookie("affiliate_type","service", $this->lifetime);
			$Service = Service::withoutGlobalScope('is_course')->with('user')->find($secret_id);

			if(empty($Service)){
				$service_id = Service::getDecryptedId($secret_id);
				$Service = Service::withoutGlobalScope('is_course')->with('user')->find($service_id);
			}
			if(is_null($Service)) {
				return redirect()->back();
			}
			$affiliate_service_id_cookie = Cookie("affiliate_service_id",$Service->id, $this->lifetime);
			
			if(empty(Cookie::get("affiliate_id")))
			{
				$affiliate_cookie = Cookie("affiliate_id",$affiliate_id, $this->lifetime);
				if($Service->is_course == 1){
					return redirect()->route('course_details',[$Service->user->username,$Service->seo_url])->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie)->Cookie($affiliate_service_id_cookie);
				}else{
					return redirect()->route('services_details',[$Service->user->username,$Service->seo_url])->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie)->Cookie($affiliate_service_id_cookie);
				}
			}
			else
			{
				if(Cookie::get('affiliate_id') != $affiliate_id)
				{
					$affiliate_cookie = Cookie("affiliate_id",$affiliate_id, $this->lifetime);
					if($Service->is_course == 1){
						return redirect()->route('course_details',[$Service->user->username,$Service->seo_url])->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie)->Cookie($affiliate_service_id_cookie);
					}else{
						return redirect()->route('services_details',[$Service->user->username,$Service->seo_url])->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie)->Cookie($affiliate_service_id_cookie);
					}
				}
			}
			if($Service->is_course == 1){
				return redirect()->route('course_details',[$Service->user->username,$Service->seo_url]);
			}else{
				return redirect()->route('services_details',[$Service->user->username,$Service->seo_url]);
			}
		}
		else if($request->is('revieweditionservice/*') == true){
			$affiliate_type_cookie = Cookie("affiliate_type","service", $this->lifetime);
			$Service = Service::with('user')->find($secret_id);

			if(empty($Service)){
				$service_id = Service::getDecryptedId($secret_id);
				$Service = Service::with('user')->find($service_id);
			}
			if(is_null($Service)) {
				return redirect()->back();
			}
			$affiliate_service_id_cookie = Cookie("affiliate_service_id",$Service->id, $this->lifetime);
			
			if(empty(Cookie::get("affiliate_id")))
			{
				
				$affiliate_cookie = Cookie("affiliate_id",$affiliate_id, $this->lifetime);

				return redirect()->route('services_details',['username' => $Service->user->username,'seo_url'=>$Service->seo_url,'review-edition'=>1])->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie)->Cookie($affiliate_service_id_cookie);
			}
			else
			{
				if(Cookie::get('affiliate_id') != $affiliate_id)
				{
					$affiliate_cookie = Cookie("affiliate_id",$affiliate_id, $this->lifetime);

					return redirect()->route('services_details',['username' => $Service->user->username,'seo_url'=>$Service->seo_url,'review-edition'=>1])->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie)->Cookie($affiliate_service_id_cookie);
				}
			}
			return redirect()->route('services_details',['username' => $Service->user->username,'seo_url'=>$Service->seo_url,'review-edition'=>1]);
		}
		else if($request->is('promoteprofile/*') == true)
		{
			$affiliate_type_cookie = Cookie("affiliate_type","profile", $this->lifetime);
			$promo_id = $secret_id;
			$user_by = User::where("affiliate_id",$promo_id)->first();
			$user_to = User::where("affiliate_id",$affiliate_id)->first();
			if(!empty($user_by) && !empty($user_to))
			{
				if(empty(Cookie::get("affiliate_id")))
				{
					$affiliate_cookie = Cookie("affiliate_id",$promo_id, $this->lifetime);

					return redirect()->route('viewuserservices',[$user_to->username])->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie);
				}
				else
				{
					if(Cookie::get('affiliate_id') != $promo_id)
					{
						$affiliate_cookie = Cookie("affiliate_id",$promo_id, $this->lifetime);

						return redirect()->route('viewuserservices',[$user_to->username])->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie);
					}
				}
				return redirect()->route('viewuserservices',[$user_to->username]);
			}
			else
			{
				return redirect('/');	
			}
		}
		else if($request->is('promotedemo/*') == true)
		{
			$affiliate_type_cookie = Cookie("affiliate_type","demo", $this->lifetime);
			if(empty(Cookie::get("affiliate_id")))
			{
				$affiliate_cookie = Cookie("affiliate_id",$affiliate_id, $this->lifetime);
				return redirect('/')->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie);
			}
			else
			{
				if(Cookie::get('affiliate_id') != $affiliate_id)
				{
					$affiliate_cookie = Cookie("affiliate_id",$affiliate_id, $this->lifetime);
					return redirect('/')->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie);
				}
			}
			return redirect('/');
	 	}
	}

	public function AffiliateOffers(Request $request){
		/* Sub user check permission */ 
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}

		$uid = $this->uid;
		// $seller_uids = Order::select('seller_uid')
		// 			->where('uid',$uid)
		// 			->get()
		// 			->makeHidden('secret')
		// 			->unique('seller_uid')->toArray();

		$query = Service::select('services.*',\DB::raw('(select price from service_plan where service_plan.service_id = services.id and service_plan.plan_type = "basic") as basic_price'));

		$query = $query->with('user', 'basic_plans')
			->Statusof('service')
			->where(function($q1){
			   $q1->whereHas('subscription');
			   $q1->where('is_affiliate_link',1);
			   $q1->orWhere(function($q2){
			   		$q2->doesntHave('subscription');
			   });
			});
			
		// $query = $query->where(function($q) use($uid,$seller_uids){
		// 		$q->Where('uid',$uid);
		// 		$q->orWhereIn('uid',$seller_uids);
		// });
		/* Check block user*/
		$block_users = User::getBlockedByIds();
		if(count($block_users) > 0){
			$query = $query->whereNotIn('services.uid',$block_users);
		}

		$query = $query->orderBy('basic_price', 'desc');
		$Service = $query->distinct()->paginate(21);

		//dd($query->distinct()->toSql());

		$categories = Category::with('subcategory')
		->where('seo_url','!=','by-us-for-us')
		->withCount(['services' => function ($query) {
			$query->where('status', '=', 'active');
			$query = $query->whereHas('user', function($user) {
				$user->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
			});
		}])
		->get();
		
		$minPrice = DB::table('service_plan')->min('price');
		$maxPrice = DB::table('service_plan')->max('price');
		$languages = UserLanguage::select('language', 'id')->groupBy('language')->get();

		$subcategories = Subcategory::where('category_id', isset($catid[0]) ? $catid[0]['category_id'] : '0')->where('status',1)->get();
		
		$bannerGeneral = GeneralSetting::whereIn('settingkey',['affiliate_banner','affiliate_text','affiliate_text_color','affiliate_bg_color','affiliate_text_size','affiliate_sub_text','affiliate_sub_text_size','affiliate_sub_text_color'])->get();
		$isAffiliate = 1;

		if($request->ajax()){
			return view('frontend.service.affiliate_filter_service', compact('Service','isAffiliate'))->render();
		}

		$userData= '';
		$parent_data=Auth::user()->parent_id;
		if($parent_data != null){
			$userData=User::where('id',$parent_data)->first();
		}

		return view('frontend.service.affiliates', compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories','isAffiliate','bannerGeneral','userData'));
	}

	public function AffiliateOffersFilter(Request $request){

		$query = Service::select('services.*',\DB::raw('(select price from service_plan where service_plan.service_id = services.id and service_plan.plan_type = "basic") as basic_price'));

		$query = $query->Statusof('service')
		->where(function($q1){
		   $q1->whereHas('subscription');
		   $q1->where('is_affiliate_link',1);
		   $q1->orWhere(function($q2){
		   		$q2->doesntHave('subscription');
		   });
		});

		if ($request->get('categories') != "" && $request->categories != 0) {
			$query = $query->where('services.category_id', $request->get('categories'));
		}
		if ($request->get('subcategories') != "" && $request->subcategories != 0) {
			$query = $query->where('services.subcategory_id', $request->get('subcategories'));
		}
		if ($request->get('deliverydays') != "any") {
			$query = $query->whereHas('basic_plans', function($q)use($request) {
				$q->whereBetween('delivery_days', [1, $request->get('deliverydays')])->select('id');
			});
		}
		

		if ($request->get('min_price') != "") {
			$min_price = $request->get('min_price');
			$query = $query->whereHas('basic_plans', function($q)use($min_price) {
				$q->where('price', '>=', $min_price)->select('id');
			});
		}
		if ($request->get('max_price') != "") {
			$max_price = $request->get('max_price');
			$query = $query->whereHas('basic_plans', function($q)use($max_price) {
				$q->where('price', '<=', $max_price)->select('id');
			});
		}

		// $uid = $this->uid;
		// $seller_uids = Order::select('seller_uid')
		// 	->where('uid',$uid)
		// 	->get()
		// 	->makeHidden('secret')
		// 	->unique('seller_uid')->toArray();
					
		// $query = $query->where(function($q) use($uid,$seller_uids){
		// 		$q->Where('uid',$uid);
		// 		$q->orWhereIn('uid',$seller_uids);
		// });
		/* Check block user*/
		$block_users = User::getBlockedByIds();
		if(count($block_users) > 0){
			$query = $query->whereNotIn('services.uid',$block_users);
		}

		$query = $query->orderBy('basic_price', 'desc');
		$Service = $query->distinct()->paginate(21);
		$isAffiliate = 1;

		$userData= '';
		$parent_data=Auth::user()->parent_id;
		if($parent_data != null){
			$userData=User::where('id',$parent_data)->first();
		}

		if($request->ajax()){
			return view('frontend.service.affiliate_filter_service', compact('Service','isAffiliate','userData'))->render();
		}
		return redirect()->route('affiliate_offers');

	}
}