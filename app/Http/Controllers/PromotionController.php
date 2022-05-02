<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use App\Subcategory;
use App\Service;
use App\ServiceMedia;
use App\ServicePlan;
use Illuminate\Support\Facades\DB;
use App\UserLanguage;
use App\User;
use App\ServiceExtra;
use App\Order;
use App\Message;
use App\MessageDetail;
use Auth;
use App\BoostingPlan;
use App\BoostedServicesOrder;
use App\ServiceQuestion;
use App\Coupan;
use App\SellerAnalytic;
use App\Cart;
use AWS;
use Carbon\Carbon;
use Aws\Exception\AwsException;
use App\SaveTemplate;
use App\BundleService;
use App\SearchFeedback;
use ChristofferOK\LaravelEmojiOne\LaravelEmojiOne;
use Redirect;
use Session;
use App\CartCombo;
use App\CartExtraCombo;
use App\SellerCategories;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Admin;
use App\SponsorCoupon;
use App\GeneralSetting;
use App\SpecialGroup;
use App\SpecialService;

class PromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    
	/**
	 * Service Promotion 
	 * 
	 */

	public function servicePromo(Request $request) {

		$q = $request->get('q');

		/*begin : redirect to service details page*/
		if($q && $q != '' && $request->search_by == 'Services' && $request->filled('service_id') && $request->service_id !=''){
			$serviceObj = Service::find($request->service_id);
			return redirect()->route('services_details',[$serviceObj->user->username,$serviceObj->seo_url]);
		}
		/*end : redirect to service details page*/

		$getCategoryId = Category::where('seo_url', '=', $request->category)->first();
		$getSubCategoryId = Subcategory::where('seo_url', '=', $request->subcategory)->first();

		if (!empty($getCategoryId)) {
			$defaultCatId = $getCategoryId->id;
		} else {
			$defaultCatId = 0;
		}

		if (!empty($getSubCategoryId)) {
			$defaultSubcatId = $getSubCategoryId->id;
		} else {
			$defaultSubcatId = 0;
		}

		$category_id = $request->get('categories') ? $request->get('categories') : $defaultCatId;
		$subcategory_id = $request->get('subcategories') ? $request->get('subcategories') : $defaultSubcatId;

		$featured = Service::with('user', 'category', 'images', 'basic_plans')->where("is_featured", 1)->where('is_private', 0);

		$featured = $featured->whereHas('user', function ($query) {
			$query->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->select('id');
		});
		if (Auth::check()) {
			$featured = $featured->with('favorite');
		}
		$featured = $featured->where('status', "active")->first();

		$catid = Subcategory::where('id', $subcategory_id)->select('category_id')->get()->toArray();

		$current_category = $category_id;
		
		/*$product_count = DB::raw("(SELECT count(*) FROM orders WHERE services.id = orders.service_id) as num_purchage");*/

		/*$rating_count = DB::raw("(SELECT sum(orders.seller_rating) FROM orders WHERE services.id = orders.service_id) as rating_count");*/

		$rating_count = DB::Raw('ROUND(services.service_rating, 0) As service_round_rating');

		$Service = Service::select('services.*', 'service_plan.price', $rating_count, 'coupans.discount_type','coupans.discount','coupans.is_promo','coupans.expiry_date','coupans.is_delete','coupans.no_of_uses')->with(['user', 'category', 'images', 'basic_plans','coupon'])
			->with(['coupon' => function($q2){
				 $q2->withCount('coupan_applied');
			}])
			->where(['status' => 'active'])->where('is_private', 0);

		$Service = $Service->where('is_approved', 1);
		$Service = $Service->where('is_custom_order', 0);


		if (Auth::check()) {
			$Service = $Service->with('favorite');
		}

		/* Remove deleted user services */
		$Service = $Service->whereHas('user', function($query) {
			$query->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->select('id');
		});
        
        /* Only Added Promotion Page */
		// $Service = $Service->whereHas('coupon', function($query)  {
		// 	$query->where('is_promo', '1');
		// 	$query->where('is_delete', 0);
		// 	$query->where('expiry_date','>=' , date('Y-m-d'));
		// 	$query->whereRaw('no_of_uses > (SELECT count(*) FROM coupan_applied WHERE  coupans.id = coupan_applied.coupan_code_id)');
  //       });

        /**Exit */
		
		$Service = $Service->orderBy('no_of_purchase', 'desc');
		
		if(Auth::check()){
			$uid = get_user_id();
		}
		/* Check Blocked Users */
		$block_users = User::getBlockedByIds();
		if(count($block_users)>0){
			$Service = $Service->whereNotIn('services.uid',$block_users); /* Check Blocked Users */
		}
		
		$Service = $Service->orderBy('no_of_purchase', 'desc');

		$Service = $Service->join('service_plan', 'service_plan.service_id', '=', 'services.id')
		->where('service_plan.plan_type', 'basic');
		$Service = $Service->join('coupans', 'coupans.service_id', '=', 'services.id')
		->where('coupans.is_promo', '1')
		->where('coupans.is_delete', 0)
		->where('coupans.expiry_date','>=' , date('Y-m-d'))
		->whereRaw('coupans.no_of_uses > (SELECT count(*) FROM coupan_applied WHERE coupans.id = coupan_applied.coupan_code_id)');
		$Service = $Service->where('services.is_delete',0)->distinct()->paginate(21);
		
		$categories = Category::with('subcategory')
		->where('seo_url','!=','by-us-for-us')
		->withCount(['services' => function ($query) {
			$query->where('status', '=', 'active');
			$query = $query->whereHas('user', function($user) {
				$user->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->select('id');
			});
		}])
		->get();
		
		$minPrice = DB::table('service_plan')->min('price');
		$maxPrice = DB::table('service_plan')->max('price');
		$languages = UserLanguage::select('language', 'id')->groupBy('language')->get();

		$subcategories = Subcategory::where('category_id', isset($catid[0]) ? $catid[0]['category_id'] : '0')->where('status',1)->get();
		// echo '<pre>';
		// print_r($Service->toArray());
		// exit;
		if ($request->ajax()) {
			return view('frontend.service.promofilterservices', compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category', 'featured'))->render();
		}
		$bannerGeneral = GeneralSetting::whereIn('settingkey',['promo_banner','promo_text','promo_text_color','promo_bg_color'])->get();
		$isPromate = 1;

		return view('frontend.service.promo', compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category', 'featured','getCategoryId','getSubCategoryId','defaultSubcatId','bannerGeneral','isPromate'));
	}

	public function filterServices(Request $request) {
		
		/*$product_count = DB::raw("(SELECT count(*) FROM orders WHERE services.id = orders.service_id) as num_purchage");*/

		/*$rating_count = DB::raw("(SELECT sum(orders.seller_rating) FROM orders WHERE services.id = orders.service_id) as rating_count");*/

		$rating_count = DB::Raw('ROUND(services.service_rating, 0) As service_round_rating');

		$query = Service::select('services.*', 'service_plan.price', $rating_count,'coupans.discount_type','coupans.discount','coupans.is_promo','coupans.expiry_date','coupans.is_delete','coupans.no_of_uses')
		->with('user', 'user.language', 'category', 'category.subcategory', 'images', 'basic_plans')
		->with(['coupon' => function($q2){
			$q2->withCount('coupan_applied');
	   }])
		->where('services.status', 'active')->where('services.is_approved', 1)
		->where('is_private', 0)->where('is_custom_order', 0);
		
		/* Remove deleted user services */
		$query = $query->whereHas('user', function($query) {
			$query->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->select('id');
		});

		if (Auth::check()) {
			$query = $query->with('favorite');
		}
		
		if ($request->get('categories') != "" && $request->categories != 0) {
			$query = $query->where('services.category_id', $request->get('categories'));
		}
		if ($request->get('subcategories') != "" && $request->subcategories != 0) {
			$query = $query->where('services.subcategory_id', $request->get('subcategories'));
		}
		if ($request->get('deliverydays') != "any") {
			$query = $query->whereHas('basic_plans', function($query)use($request) {
				$query->whereBetween('delivery_days', [1, $request->get('deliverydays')])->select('id');
			});
		}
		if ($request->get('sellerlanguages') != "") {
			$languages = explode(',', $request->get('sellerlanguages'));
			$usersIds = UserLanguage::distinct('uid')->whereIn('language', $languages)->pluck('uid')->toArray();
			if (count($usersIds)) {
				$query = $query->whereIn('services.uid', $usersIds);
			}
		}

		if ($request->get('min_price') != "") {
			$min_price = $request->get('min_price');
			$query = $query->whereHas('basic_plans', function($query)use($min_price) {
				$query->where('price', '>=', $min_price)->select('id');
			});
		}
		if ($request->get('max_price') != "") {
			$max_price = $request->get('max_price');
			$query = $query->whereHas('basic_plans', function($query)use($max_price) {
				$query->where('price', '<=', $max_price)->select('id');
			});
		}


		$Service = $query->where('services.status', 'active')->where('services.is_approved', 1);

		if (trim($request->get('searchtext')) != "") {
			$Service = $Service->where(function($query) use ($request) {
				
				/*$query->orWhere('services.descriptions', 'LIKE', '%' . $request->get('searchtext') . '%');*/
				if($request->search_by == 'Services'){
					$query->Where('services.title', 'LIKE', '%' . $request->get('searchtext') . '%');
					$query->orWhere('services.tags', 'LIKE', '%' . $request->get('searchtext') . '%');
				}elseif($request->search_by == 'Categories'){
					$query->Where('category.category_name', 'LIKE', '%' . $request->get('searchtext') . '%');
				}elseif($request->search_by == 'Users'){
					$query->Where('users.username', 'LIKE', '%' . $request->get('searchtext') . '%');
				}
				
			});
		}

		
		if ($request->get('online_seller') && $request->online_seller == 1) 
		{
			$Service = $Service->orderBy('users.last_login_at','desc');
		}
		
        /* Only Added Promotion Page */
		// $Service = $Service->whereHas('coupon', function($query)  {
		// 	$query->where('is_promo', '1');
		// 	$query->where('is_delete', 0);
		// 	$query->where('expiry_date','>=' , date('Y-m-d'));
		// 	$query->whereRaw('no_of_uses > (SELECT count(*) FROM coupan_applied WHERE  coupans.id = coupan_applied.coupan_code_id)');
  //       });
		
		if($request->segment(1) == 'recently-uploaded') {
			$Service = $Service->orderBy('services.created_at', 'desc');
		} else {
			$order_by = $request->get('sort_by');
			if ($order_by && $order_by != '') {
				if ($order_by == 'most_popular') {
					$Service = $Service->orderBy('no_of_purchase', 'desc');
				} elseif ($order_by == 'amt_low_to_high') {
					$Service = $Service->where('coupans.discount_type','amount')->orderBy('coupans.discount', 'asc');
				} elseif ($order_by == 'amt_high_to_low') {
					$Service = $Service->where('coupans.discount_type','amount')->orderBy('coupans.discount', 'desc');
				} elseif ($order_by == 'per_low_to_high') {
					$Service = $Service->where('coupans.discount_type','percentage')->orderBy('coupans.discount', 'asc');
				} elseif ($order_by == 'per_high_to_low') {
					$Service = $Service->where('coupans.discount_type','percentage')->orderBy('coupans.discount', 'desc');
				}
			} else {
				$Service = $Service->orderBy('no_of_purchase', 'desc');
			}
		}

		$getPausedService = Service::select('id')
		->where(function($q){
			$q->where('status','paused')->orWhere('is_private',1);
		})->whereHas('user', function ($query) {
			$query->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
		})->get()->toArray();


		$category_id = $request->get('categories');
		$defaultSubcatId = $request->get('subcategories');
		
		$sponseredService = null;
		
		$Service = $Service->join('category', 'category.id', '=', 'services.category_id')
		->join('users', 'services.uid', '=', 'users.id')
		->join('service_plan', 'service_plan.service_id', '=', 'services.id')
		->join('coupans', 'coupans.service_id', '=', 'services.id')
		->where('coupans.is_promo', '1')
		->where('coupans.is_delete', 0)
		->where('coupans.expiry_date','>=' , date('Y-m-d'))
		->whereRaw('coupans.no_of_uses > (SELECT count(*) FROM coupan_applied WHERE coupans.id = coupan_applied.coupan_code_id)')
		->where('service_plan.plan_type', 'basic')
		->where('services.is_delete',0)
		->distinct()
		->paginate(21);
		$isPromate = 1;
		return view('frontend.service.promofilterservices', compact('Service', 'sponseredService','isPromate'))->render();
	}

	public function getSubCategories(Request $request) {
		$html = '';
		if (isset($request->id)) {
			$subcategories = Subcategory::where('category_id', $request->id)->where('status',1)->get();
			if (count($subcategories)) {
				foreach ($subcategories as $key => $value) {
					$html .= '<li class="dropdown-item" id="' . $value->id . '">
					<a href="javascript:void(0)" id="' . $value->id . '" class="subcategory-name-promo">' . $value->subcategory_name . '</a>
					</li>';
				}
				return response()->json([
					'status' => 200,
					'html' => $html
				]);
			} else {
				return response()->json([
					'status' => 401,
					'html' => $html
				]);
			}
		} else {
			return response()->json([
				'status' => 401,
				'html' => $html
			]);
		}
	}

	public function specials_group(Request $request,$slug) {
		$group = SpecialGroup::where('slug',$slug)->first();
		if(is_null($group)) {
			return response()->view('errors.404', [], 404);
		}
		$group_services = SpecialService::where('group_id',$group->id)->pluck('service_id')->toArray();

		$q = $request->get('q');

		/*begin : redirect to service details page*/
		if($q && $q != '' && $request->search_by == 'Services' && $request->filled('service_id') && $request->service_id !=''){
			$serviceObj = Service::find($request->service_id);
			return redirect()->route('services_details',[$serviceObj->user->username,$serviceObj->seo_url]);
		}
		/*end : redirect to service details page*/

		$getCategoryId = Category::where('seo_url', '=', $request->category)->first();
		$getSubCategoryId = Subcategory::where('seo_url', '=', $request->subcategory)->first();

		if (!empty($getCategoryId)) {
			$defaultCatId = $getCategoryId->id;
		} else {
			$defaultCatId = 0;
		}

		if (!empty($getSubCategoryId)) {
			$defaultSubcatId = $getSubCategoryId->id;
		} else {
			$defaultSubcatId = 0;
		}

		$category_id = $request->get('categories') ? $request->get('categories') : $defaultCatId;
		$subcategory_id = $request->get('subcategories') ? $request->get('subcategories') : $defaultSubcatId;

		$featured = Service::with('user', 'category', 'images', 'basic_plans')->where("is_featured", 1)->where('is_private', 0);

		$featured = $featured->whereHas('user', function ($query) {
			$query->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->select('id');
		});
		if (Auth::check()) {
			$featured = $featured->with('favorite');
		}
		$featured = $featured->where('status', "active")->first();

		$catid = Subcategory::where('id', $subcategory_id)->select('category_id')->get()->toArray();

		$current_category = $category_id;

		$Service = Service::whereIn('services.id',$group_services);
		if ($q && $q != '') {
			$Service = $Service->with('user','category','images','basic_plans','user.country','coupon')->where('is_private', 0)
			->where('services.status', 'active')
			->where(function($query) use ($q,$request) {
				if($request->search_by == 'Services'){
					if($request->filled('service_id') && $request->service_id !=''){
						$query->where('services.id', $request->service_id);
					}else{
						$query->where('services.title', 'LIKE', '%' . $q . '%');
						$query->orWhere('services.tags', 'LIKE', '%' . $q . '%');
					}
				}elseif($request->search_by == 'Categories'){
					$query->Where('category.category_name', 'LIKE', '%' . $q . '%');

					/*$filerCategory = Category::select('id')->where('category_name', 'LIKE', '%' . $q . '%')->first();
					if(count($filerCategory) > 0){
						$category_id = $filerCategory->id;
						$query = $query->whereHas('seller_categories', function($q1) use ($category_id){
							$q1->where('category_id', $category_id);
						});
					}else{
						$query->Where('category.category_name', 'LIKE', '%' . $q . '%');
					}*/
				}elseif($request->search_by == 'Users'){
					$query->Where('users.username', 'LIKE', '%' . $q . '%');
				}
			})
			->join('category', 'category.id', '=', 'services.category_id')
			->join('users', 'services.uid', '=', 'users.id');

		} else {
			$Service = $Service->with(['user', 'category', 'images', 'basic_plans','coupon'])
			->where(['status' => 'active'])->where('is_private', 0);
		}

		$Service = $Service->where('is_approved', 1);
		$Service = $Service->where('is_custom_order', 0);

		/* if (Auth::check()) {
			$Service = $Service->with('favorite');
		} */

		/* Remove deleted user services */
		$Service = $Service->whereHas('user', function($query) {
			$query->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->select('id');
		});

		if ($category_id && $category_id != '') {
			$Service = $Service->where('services.category_id', $category_id);
		}

		if ($subcategory_id && $subcategory_id != '') {
			$Service = $Service->where('services.subcategory_id', $subcategory_id);
		}
        
		$order_by = $request->get('sort_by');
		if ($order_by && $order_by != '') {
			if ($order_by == 'top_rated') {
				/* $Service = $Service->orderBy('service_round_rating', 'desc'); */
				$Service = $Service->orderBy('services.total_review_count', 'desc');
			} elseif ($order_by == 'recently_uploaded') {
				$Service = $Service->orderBy('services.created_at', 'desc');
			} elseif ($order_by == 'most_popular') {
				$Service = $Service->orderBy('no_of_purchase', 'desc');
			} elseif ($order_by == 'low_to_high') {
				$Service = $Service->orderBy('service_plan.price', 'asc');
			} elseif ($order_by == 'high_to_low') {
				$Service = $Service->orderBy('service_plan.price', 'desc');
			}
		} else {
			$Service = $Service->orderBy('no_of_purchase', 'desc');
		}

		$Service = $Service/* ->join('service_plan', 'service_plan.service_id', '=', 'services.id') */
		/* ->where('service_plan.plan_type', 'basic')->where('services.is_delete',0) */->paginate(21);

		$categories = Category::with('subcategory')
		->withCount(['services' => function ($query) {
			$query->where('status', '=', 'active');
			$query = $query->whereHas('user', function($user) {
				$user->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->select('id');
			});
		}])
		->where('seo_url','!=','by-us-for-us')
		->get();
		
		$minPrice = DB::table('service_plan')->min('price');
		$maxPrice = DB::table('service_plan')->max('price');
		$languages = UserLanguage::select('language', 'id')->groupBy('language')->get();

		$subcategories = Subcategory::where('category_id', isset($catid[0]) ? $catid[0]['category_id'] : '0')->where('status',1)->get();
		
		if ($request->ajax()) {
			return view('frontend.service.promofilterservices', compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category', 'featured','group','slug'))->render();
		}

		return view('frontend.service.specials', compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category', 'featured','getCategoryId','getSubCategoryId','defaultSubcatId','group','slug'));
	}
}
