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
use Auth;
use App\BoostingPlan;
use App\BoostedServicesOrder;
use App\ServiceQuestion;
use App\Coupan;
use App\SellerAnalytic;
use App\Cart;
use AWS;
use Carbon\Carbon;
use App\SaveTemplate;
use App\BundleService;
use Redirect;
use Session;
use App\SellerCategories;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\Admin;
use App\SponsorCoupon;
use App\UserSearchTerm;
use App\UserSearchCategory;
use App\GeneralSetting;
use App\Setting;
use App\ReviewFeedback;
use App\Influencer;
use Intervention\Image\ImageManagerStatic as Image;
use App\LandingPage;
use App\UserHomePickService;
use Validator;
use App\Portfolio;
use App\UserFollow;
use App\Models\ServiceRevision;
use App\Models\ServiceExtraRevision;

class ServiceController extends Controller {

	private $uid;
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

	public function index(Request $request) {
		/* Check Sub user permission */
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}

		$status = $request->input('status');

		$is_recurring = $request->input('is_recurring');

		$uid = $this->uid;

		$Service = Service::with('images', 'basic_plans')
		->where('uid',$uid);
		
		if ($status != null) {
			$Service = $Service->where(['status' => $status]);
		}

		if($request->is_recurring == 1){
			$Service = $Service->where(['is_recurring' => $is_recurring]);
		}else if($request->is_recurring == 3){
			$Service = $Service->where('services.is_review_edition',1);
			$Service = $Service->whereRaw('services.review_edition_count < services.no_of_review_editions');
		}
		
		$searchtxt = $request->input('searchtxt');

		if($request->searchtxt != null){
			$Service = Service::with('images', 'basic_plans')
			->where(['uid' => $uid])
			->where('is_custom_order', 0)
			->where('is_job',0)
			->where(function($query) use ($searchtxt)  {
				$query->where('title','LIKE', '%' . $searchtxt . '%')
				->orwhere('subtitle','LIKE', '%' . $searchtxt . '%')
				->orwhere('descriptions','LIKE', '%' . $searchtxt . '%');
			})->orderBy('id', 'desc');
		} else if ($status) {
			$Service = Service::with('images', 'basic_plans')
			->where(['uid' => $uid, 'status' => $status])
			->where('is_custom_order', 0)->where('is_job',0)->orderBy('id', 'desc');
			

		}
		else if($request->is_recurring == 1)
		{
			$Service = $Service->where(['is_recurring' => $is_recurring]);
		}
		else if($request->is_recurring == 2)
		{

			$Service = $Service->where('is_recurring',0);
		}	
		else if($request->is_recurring == 3){
			$Service = $Service->where('services.is_review_edition',1);
			$Service = $Service->whereRaw('services.review_edition_count < services.no_of_review_editions');
		}
		else {
			$Service = Service::where(['uid' => $uid])
			->with('images', 'basic_plans')
			->where('is_custom_order', 0)
			->where('is_job',0)
			->orderBy('id', 'desc');
		}

		$Service = $Service->where('is_custom_order', 0)->where('is_job',0)->where('is_delete',0)
		->orderBy('id', 'desc')
		->paginate(20)
		->appends($request->all());

		return view('frontend.service.index', compact('Service'));
	}

	public function getSuggestion(Request $request){
		$result = [];

		//sleep( 2 );
		// no term passed - just exit early with no response
		if (empty($request->term)){
			exit;
		} 
		$q = strtolower($request->term);
		// remove slashes if they were magically added
		/* if (get_magic_quotes_gpc()){
			$q = stripslashes($q);
		}  */

		/*begin : Service Search*/
		if($request->search_by =='Services' || $request->search_by =='Courses'){
			$Service = Service::withoutGlobalScope('is_course')
			->select('services.title','services.id')
			->where('services.status', 'active')
			->where('services.is_approved', 1)
			->where('services.is_delete', 0)
			->where('is_private', 0)
			->where('is_job', 0)
			->where('is_custom_order', 0)
			->where('services.title', 'LIKE', '%' . $q . '%');

			/* Check course condition */
			if($request->search_by =='Courses'){
				$Service = $Service->where('is_course',1);
			}else{
				$Service = $Service->where('is_course',0);
			}

			/* Check Blocked Users */
			$block_users = User::getBlockedByIds();
			if(count($block_users)>0){
				$Service = $Service->whereNotIn('services.uid',$block_users);
			}
			
			$Service = $Service->whereHas('user', function($query) {
				$query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
			})->limit(6)->get();
			
			if(count($Service)){
				foreach ($Service as $key => $value) {
					array_push($result, array("service_id"=>$value->id,"label"=>display_title_predict_search($value->title), "category" => 'Courses'));
				}
			}
		}
		
		
		/*end : Service Search*/

		/*begin : users Search*/
		if($request->search_by =='Users'){
			$Users = Service::distinct()->select('users.username')
			->where('services.status', 'active')
			->where('services.is_approved', 1)
			->where('services.is_delete', 0)
			->where('is_private', 0)
			->where('is_job', 0)
			->where('is_custom_order', 0)
			->where('users.username', 'LIKE', '%' . $q . '%');

			/* Check Blocked Users */
			$block_users = User::getBlockedByIds();
			if(count($block_users)>0){
				$Users = $Users->whereNotIn('users.id',$block_users);
			}

			$Users = $Users->join('users', 'services.uid', '=', 'users.id')
			->whereHas('user', function($query) {
				$query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
			})->limit(6)->get();

			if(count($Users)){
				foreach ($Users as $key => $value) {
					array_push($result, array("label"=>display_title_predict_search($value->username), "category" => 'Users'));
				}
			}
		}
		/*end : users Search*/

		/*begin : categories Search*/
		if($request->search_by =='Categories'){
			$Category = Service::distinct()->select('category.category_name')
			->where('services.status', 'active')
			->where('services.is_approved', 1)
			->where('services.is_delete', 0)
			->where('is_private', 0)
			->where('is_job', 0)
			->where('is_custom_order', 0);
			/* Check Blocked Users */
			$block_users = User::getBlockedByIds();
			if(count($block_users)>0){
				$Category = $Category->whereNotIn('services.uid',$block_users);
			}
			$Category = $Category->where('category.category_name', 'LIKE', '%' . $q . '%')
			->join('category', 'category.id', '=', 'services.category_id')
			->whereHas('user', function($query) {
				$query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
			})->limit(6)->get();

			if(count($Category)){
				foreach ($Category as $key => $value) {
					array_push($result, array("label"=>display_title_predict_search($value->category_name), "category" => 'Categories'));
				}
			}
		}
		/*end : users categories*/

		$output = json_encode($result);
		if (isset($_GET["callback"]) && $_GET["callback"]) {
			// Escape special characters to avoid XSS attacks via direct loads of this
			// page with a callback that contains HTML. This is a lot easier than validating
			// the callback name.
			$output = htmlspecialchars($_GET["callback"]) . "($output);";
		}
		echo $output;
		exit();
	}

	function store_search_terms($request){
		if (Auth::check()) {
			$uid = $this->uid;
			$search_term = '';
			$q = $request->get('q');
			if($q && $q != ''){
				$search_term = $q;
				if($request->search_by == 'Services' && $request->filled('service_id') && $request->service_id !=''){
					$serviceObj = Service::select('title')->find($request->service_id);
					if(!empty($serviceObj)){
						$search_term = $serviceObj->title;
					}
				}
				$userSearchTerm = UserSearchTerm::where('user_id',$uid)->first();
				if(empty($userSearchTerm)){
					$userSearchTerm = New UserSearchTerm;
					$userSearchTerm->search_term = [$search_term];
					$userSearchTerm->user_id = $uid;
					$userSearchTerm->save();
				}else{
					$search_result = $userSearchTerm->search_term;
					if(count($search_result) >= 5){
						unset($search_result[0]);
					}
					array_push($search_result,$search_term);
					$userSearchTerm->search_term = array_values($search_result);
					$userSearchTerm->save();
				}
			}
		}
	}

	function store_search_category($categoryId=null){
		if (Auth::check() && $categoryId) {
			/*remove 7 days previous records*/
			UserSearchCategory::where('user_id',$this->uid)->where('updated_at', '<', Carbon::now()->subDays(7))->delete();
			UserSearchCategory::firstOrCreate(['user_id'=>$this->uid,'category_id'=>$categoryId]);
		}
	}

	public function view(Request $request) {

		$q = $request->get('q');

		/*begin : redirect to courses page*/
		if($request->search_by == 'Courses'){
			return redirect()->route('courses',['q'=>$q,'search_by'=>$request->search_by,'service_id'=>$request->service_id]);
		}
		/*end : redirect to courses page*/

		/* Begin : Store search result*/
		$this->store_search_terms($request);
		/* End : Store search result*/

		/*begin : redirect to service details page*/
		if($q && $q != '' && $request->search_by == 'Services' && $request->filled('service_id') && $request->service_id !=''){
			$serviceObj = Service::select('id','uid','seo_url')->find($request->service_id);
			if(isset($serviceObj->user->username) && isset($serviceObj->seo_url)) {
				return redirect()->route('services_details',[$serviceObj->user->username,$serviceObj->seo_url]);
			} else {
				return redirect()->back();
			}
		}
		/*end : redirect to service details page*/

		$getCategoryId = Category::where('seo_url', '=', $request->category)->first();
		if($q && $q != '' && $request->search_by == 'Categories'){
			$getCategoryId = Category::where('category_name', '=', str_replace('   ',' & ',$q ))->first();
		}

		if (!$request->ajax()) {
			if($getCategoryId->seo_url == 'by-us-for-us'){
				return redirect('404');
			}
		}

		$getSubCategoryId = Subcategory::where('seo_url', '=', $request->subcategory)->first();

		if (!empty($getCategoryId)) {
			$defaultCatId = $getCategoryId->id;
		} else {
			$defaultCatId = 0;
		}

		if (!empty($getSubCategoryId)) {
			$defaultSubcatId = $getSubCategoryId->id;
			if($getSubCategoryId->status == 0){
				return redirect('/');
			}
		} else {
			$defaultSubcatId = 0;
		}

		if ($request->ajax()) {
			$category_id = $request->get('categories') ? $request->get('categories') : '';
			$subcategory_id = $request->get('subcategories') ? $request->get('subcategories') : '';
		}else{
			$category_id = $request->get('categories') ? $request->get('categories') : $defaultCatId;
			$subcategory_id = $request->get('subcategories') ? $request->get('subcategories') : $defaultSubcatId;
		}
		
		$this->store_search_category($category_id);

		$featured = Service::with('user', 'category', 'images', 'basic_plans')->where("is_featured", 1)->where('is_private', 0);

		$featured = $featured->whereHas('user', function ($query) {
			$query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
		});
		if (Auth::check()) {
			$featured = $featured->with('favorite');
		}
		/* Check block user*/
		$block_users = User::getBlockedByIds();
		if($block_users > 0){
			$featured = $featured->whereNotIn('uid', $block_users);
		}
		$featured = $featured->where('status', "active")->first();

		$catid = Subcategory::where('id', $subcategory_id)->select('category_id')->get()->toArray();

		$current_category = $category_id;
		
		/*$product_count = DB::raw("(SELECT count(*) FROM orders WHERE services.id = orders.service_id) as num_purchage");*/

		/*$rating_count = DB::raw("(SELECT sum(orders.seller_rating) FROM orders WHERE services.id = orders.service_id) as rating_count");*/

		$rating_count = DB::Raw('ROUND(services.service_rating, 0) As service_round_rating');
		$Service = Service::select('services.*', 'service_plan.price', $rating_count)
		->join('category', 'category.id', '=', 'services.category_id')
		->join('users', 'services.uid', '=', 'users.id')
		->join('service_plan', 'service_plan.service_id', '=', 'services.id');
		
		/*Check Blocked users*/
		$block_users = User::getBlockedByIds();
		if(count($block_users)>0){
			$Service = $Service->whereNotIn('services.uid', $block_users);
		}
		$Service = $Service->where([
			'services.status'=> 'active',
			'services.is_private'=> 0,
			'services.is_approved'=> 1,
			'services.is_custom_order'=> 0,
			'services.is_job'=> 0,
			'services.is_delete'=> 0,
			'service_plan.plan_type'=> 'basic',
			'users.status'=> 1,
			'users.is_delete'=> 0,
			'users.vacation_mode'=> 0,
			]);

		if ($q && $q != '') {
			$Service = $Service->where(function($query) use ($q,$request) {
				if($request->search_by == 'Services'){
					if($request->filled('service_id') && $request->service_id !=''){
						$query->where('services.id', $request->service_id);
					}else{
						$query->where('services.title', 'LIKE', '%' . $q . '%');
						$query->orWhere('services.tags', 'LIKE', '%' . $q . '%');
					}
				}elseif($request->search_by == 'Categories'){
					$query->Where('category.category_name', 'LIKE', '%' . $q . '%');
				}elseif($request->search_by == 'Users'){
					$query->Where('users.username', 'LIKE', '%' . $q . '%');
				}
			});
		}

		if ($category_id && $category_id != '') {
			$Service = $Service->where('services.category_id', $category_id);
		}
		
		if ($subcategory_id && $subcategory_id != '') {
			$Service = $Service->where('services.subcategory_id', $subcategory_id);
		}

		if ($request->get('deliverydays') && $request->get('deliverydays') != "any") {
			$Service = $Service->whereHas('basic_plans', function($query)use($request) {
				$query->select('id')->whereBetween('delivery_days', [1, $request->get('deliverydays')]);
			});
		}

		if ($request->get('min_price') && $request->get('min_price') != "") {
			$min_price = $request->get('min_price');
			$Service = $Service->whereHas('basic_plans', function($query)use($min_price,$request) {
				if($request->segment(1) == 'review-editions') {
					$query->select('id')->where('review_edition_price', '>=', $min_price);
				}else{
					$query->select('id')->where('price', '>=', $min_price);
				}
			});
		}

		if ($request->get('max_price') && $request->get('max_price') != "") {
			$max_price = $request->get('max_price');
			$Service = $Service->whereHas('basic_plans', function($query)use($max_price,$request) {
				if($request->segment(1) == 'review-editions') {
					$query->select('id')->where('review_edition_price', '<=', $max_price);
				}else{
					$query->select('id')->where('price', '<=', $max_price);
				}
			});
		}

		if($request->segment(1) == 'premium-services') {
			$grace_days = env('PREMIUM_SELLER_SUBSCRIPTION_GRACE_DAYS');
			$end_date = Carbon::now()->subDays($grace_days)->format('Y-m-d H:i:s');
			$Service = $Service->join('subscribe_users', 'subscribe_users.user_id', '=', 'services.uid')
								->where('subscribe_users.end_date','>=',$end_date);
		}

		if($request->segment(1) == 'review-editions') {
			$Service = $Service->where('services.is_review_edition',1);
			$Service = $Service->whereRaw('services.review_edition_count < services.no_of_review_editions');
			//Exclude services which have already buy review ediions
			$exclude_service_ids = Order::distinct('service_id')->where('uid',$this->uid)
			->where('is_review_edition',1)->where('status','!=','cancelled')->pluck('service_id');
			if (count($exclude_service_ids) > 0) {
				$Service = $Service->whereNotIn('services.id', $exclude_service_ids);
			}
		}
		
		if($request->segment(1) == 'by_us_for_us') {
			$Service = $Service->where('by_us_for_us',1);
		}

		if ($request->get('online_seller') && $request->online_seller == 1) {
			$Service = $Service->orderBy('users.last_login_at','desc');
		}

		

		if($request->segment(1) == 'recently-uploaded') {
			$Service = $Service->orderBy('services.created_at', 'desc');
		}else if($request->segment(1) == 'top-rated') {
			$Service = $Service->orderBy('services.total_review_count', 'desc');
			$Service = $Service->orderBy('service_round_rating', 'desc');
		}else if($request->segment(1) == 'best-seller') {
			$Service = $Service->orderBy('no_of_purchase', 'desc');
		}else if($request->segment(1) == 'recurring') {
			$Service = $Service->where('services.is_recurring',1);
			$Service = $Service->orderBy('services.total_review_count', 'desc');
			$Service = $Service->orderBy('service_round_rating', 'desc');
		}
		else {
			$order_by = $request->get('sort_by');
			if ($order_by && $order_by != '') {
				if ($order_by == 'top_rated') {
					/*$Service = $Service->orderBy('rating_count', 'desc');*/
					$Service = $Service->orderBy('services.total_review_count', 'desc');
					$Service = $Service->orderBy('service_round_rating', 'desc');
				} elseif ($order_by == 'recently_uploaded') {
					$Service = $Service->orderBy('services.created_at', 'desc');
				} elseif ($order_by == 'most_popular') {
					$Service = $Service->orderBy('no_of_purchase', 'desc');
				} elseif ($order_by == 'low_to_high') {
					if($request->segment(1) == 'review-editions') {
						$Service = $Service->orderBy('service_plan.review_edition_price', 'asc');
					}else{
						$Service = $Service->orderBy('service_plan.price', 'asc');
					}
				} elseif ($order_by == 'high_to_low') {
					if($request->segment(1) == 'review-editions') {
						$Service = $Service->orderBy('service_plan.review_edition_price', 'desc');
					}else{
						$Service = $Service->orderBy('service_plan.price', 'desc');
					}
				} elseif($order_by == 'least_reviews') {
					$Service = $Service->orderBy('services.total_review_count', 'asc');
					$Service = $Service->orderBy('service_round_rating', 'asc');
				}
			}else {
				if($request->segment(1) == 'review-editions') {
					$Service = $Service->orderBy('services.total_review_count', 'asc');
					$Service = $Service->orderBy('service_round_rating', 'asc');
				}else{
					$Service = $Service->orderBy('services.total_review_count', 'desc');
					$Service = $Service->orderBy('service_round_rating', 'desc');
				}
			}
		}

		$Service = $Service->paginate(21);

		//dd($Service);

		$categories = Category::where('seo_url','!=','by-us-for-us')->get();
		$subcategories = Subcategory::where('category_id', isset($catid[0]) ? $catid[0]['category_id'] : '0')->where('status',1)->get();
		$minPrice = DB::table('service_plan')->min('price');
		$maxPrice = DB::table('service_plan')->max('price');
		$languages = UserLanguage::select('language', 'id')->groupBy('language')->get();

		$sponseredService = null;
		if(!isset($request->page) || (isset($request->page) && $request->page == 1)){
			$sponseredService = $this->getCategoryAndSubcategoryPageSponserService($category_id,$defaultSubcatId);
		}

		if ($request->ajax()) {
			$selectedCategory = Category::select('category_name','display_title','category_description','seo_url')->where('id',$current_category)->first();
			$filterResponse['html_response'] = view('frontend.service.filterservices', compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category', 'featured', 'sponseredService'))->render();
			$filterResponse['category_name'] = (!empty($selectedCategory))?$selectedCategory->category_name:'';
			$filterResponse['display_title'] = (!empty($selectedCategory) && !empty($selectedCategory->display_title))?$selectedCategory->display_title:'';
			$filterResponse['category_description'] =  (!empty($selectedCategory) && !empty($selectedCategory->category_description))?$selectedCategory->category_description:'';
			$selectedSubCategory = Subcategory::select('display_title','subcategory_description','seo_url')->where('id',$subcategory_id)->first();
			$filterResponse['subdisplay_title'] = (!empty($selectedSubCategory) && !empty($selectedSubCategory->display_title))?$selectedSubCategory->display_title:'';
			$filterResponse['subcategory_description'] =  (!empty($selectedSubCategory) && !empty($selectedSubCategory->subcategory_description))?$selectedSubCategory->subcategory_description:'';
			
			if(!empty($selectedSubCategory)){
				$filterResponse['url'] = route('services_view',[$selectedCategory->seo_url,$selectedSubCategory->seo_url]);
			}else{
				$filterResponse['url'] = route('services_view',[$selectedCategory->seo_url]);
			}
			$filterResponse = mb_convert_encoding($filterResponse, 'UTF-8', 'UTF-8');
			return $filterResponse;
		}

		$imglink = GeneralSetting::where('settingkey','service_job_banner_img')->pluck('settingvalue')->first();
		$bannerlink = GeneralSetting::where('settingkey','service_job_banner_link')->pluck('settingvalue')->first();

		$forusByUsBanner = null;
		if($request->segment(1) == 'by_us_for_us') {
			$forusByUsBanner = GeneralSetting::whereIn('settingkey',['forusbyus_banner','forusbyus_text','forusbyus_text_color','forusbyus_bg_color','forusbyus_title_font_size','forusbyus_subtitle_text','forusbyus_subtitle_color','forusbyus_subtitle_font_size'])->get();
		}

		return view('frontend.service.view', compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category', 'featured', 'sponseredService','getCategoryId','getSubCategoryId','defaultSubcatId','imglink','bannerlink','forusByUsBanner'));
	}

	private function getCategoryAndSubcategoryPageSponserService($category_id,$defaultSubcatId=null){
		if($defaultSubcatId){
				
			/*For category page*/
			$sponseredServiceCategory = BoostedServicesOrder::select('boosted_services_orders.*')
			->whereNull('bsod.subcategory_id')
			->where([
				'boosted_services_orders.plan_id' => 4,
				'boosted_services_orders.status' => 'active',
				'bsod.date' => date('Y-m-d'),
				'bsod.category_id' => $category_id,
				'services.status'=> 'active',
				'services.is_private'=> 0,
				'services.is_approved'=> 1,
				'services.is_delete'=> 0,
				'users.status'=> 1,
				'users.is_delete'=> 0,
				'users.vacation_mode'=> 0,
				])
			->join('users','users.id','boosted_services_orders.uid')
			->join('services','services.id','boosted_services_orders.service_id')
			->join('boosted_services_orders_dates as bsod','boosted_services_orders.id','bsod.boosted_order_id');

			/*For subcategory page*/
			$sponseredService = BoostedServicesOrder::select('boosted_services_orders.*')
			->where([
				'boosted_services_orders.plan_id' => 5,
				'boosted_services_orders.status' => 'active',
				'bsod.date' => date('Y-m-d'),
				'bsod.category_id' => $category_id,
				'bsod.subcategory_id' => $defaultSubcatId,
				'services.status'=> 'active',
				'services.is_private'=> 0,
				'services.is_approved'=> 1,
				'services.is_delete'=> 0,
				'users.status'=> 1,
				'users.is_delete'=> 0,
				'users.vacation_mode'=> 0,
				])
			->join('boosted_services_orders_dates as bsod','boosted_services_orders.id','bsod.boosted_order_id')
			->join('users','users.id','boosted_services_orders.uid')
			->join('services','services.id','boosted_services_orders.service_id')
			->union($sponseredServiceCategory)->orderBy('slot','ASC')->get();

			$sponseredService = $sponseredService->unique('service_id');

		}else{
			/*For category page*/
			$sponseredService = BoostedServicesOrder::select('boosted_services_orders.*')
			->whereNull('bsod.subcategory_id')
			->where([
				'boosted_services_orders.plan_id' => 4,
				'boosted_services_orders.status' => 'active',
				'bsod.date' => date('Y-m-d'),
				'bsod.category_id' => $category_id,
				'services.status'=> 'active',
				'services.is_private'=> 0,
				'services.is_approved'=> 1,
				'services.is_delete'=> 0,
				'users.status'=> 1,
				'users.is_delete'=> 0,
				'users.vacation_mode'=> 0,
				])
			->join('boosted_services_orders_dates as bsod','boosted_services_orders.id','bsod.boosted_order_id')
			->join('users','users.id','boosted_services_orders.uid')
			->join('services','services.id','boosted_services_orders.service_id')
			->orderBy('boosted_services_orders.slot','ASC')->get();

			$sponseredService = $sponseredService->unique('service_id');

		}
		return $sponseredService;
	}

	public function filterServices(Request $request) {
		/* This Function no Longer Usage */
		
		/*$product_count = DB::raw("(SELECT count(*) FROM orders WHERE services.id = orders.service_id) as num_purchage");*/

		/*$rating_count = DB::raw("(SELECT sum(orders.seller_rating) FROM orders WHERE services.id = orders.service_id) as rating_count");*/

		$rating_count = DB::Raw('ROUND(services.service_rating, 0) As service_round_rating');

		$query = Service::select('services.*', 'service_plan.price', $rating_count)
		->where('services.status', 'active')
		->where('services.is_approved', 1)
		->where('is_job', 0)
		->where('is_private', 0)
		->where('is_custom_order', 0);
		
		/* Remove deleted user services */
		$query = $query->whereHas('user', function($query) {
			$query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
		});

		if ($request->get('categories') != "" && $request->categories != 0) {
			$query = $query->where('services.category_id', $request->get('categories'));
			$cat1 = $request->categories;
			$this->store_search_category($cat1);
		}
		if ($request->get('subcategories') != "" && $request->subcategories != 0) {
			$query = $query->where('services.subcategory_id', $request->get('subcategories'));
		}
		if ($request->get('deliverydays') != "any") {
			$query = $query->whereHas('basic_plans', function($query)use($request) {
				$query->select('id')->whereBetween('delivery_days', [1, $request->get('deliverydays')]);
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
				$query->select('id')->where('price', '>=', $min_price);
			});
		}

		if ($request->get('max_price') != "") {
			$max_price = $request->get('max_price');
			$query = $query->whereHas('basic_plans', function($query)use($max_price) {
				$query->select('id')->where('price', '<=', $max_price);
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
		
		if($request->segment(1) == 'recently-uploaded') {
			$Service = $Service->orderBy('services.created_at', 'desc');
		}else if($request->segment(1) == 'top-rated') {
			$Service = $Service->orderBy('services.total_review_count', 'desc');
			$Service = $Service->orderBy('service_round_rating', 'desc');
		}else if($request->segment(1) == 'best-seller') {
			$Service = $Service->orderBy('no_of_purchase', 'desc');
		}else if($request->segment(1) == 'recurring') {
			$Service = $Service->where('services.is_recurring',1);
			$Service = $Service->orderBy('services.total_review_count', 'desc');
			$Service = $Service->orderBy('service_round_rating', 'desc');
		}
		else {
			$order_by = $request->get('sort_by');
			if ($order_by == 'top_rated') {
				//$Service = $Service->orderBy('rating_count', 'desc');
				$Service = $Service->OrderBy('services.total_review_count', 'desc');
				$Service = $Service->orderBy('service_round_rating', 'desc');
			} elseif ($order_by == 'recently_uploaded') {
				$Service = $Service->orderBy('services.created_at', 'desc');
			} elseif ($order_by == 'most_popular') {
				$Service = $Service->orderBy('no_of_purchase', 'desc');
			} elseif ($order_by == 'low_to_high') {
				$Service = $Service->orderBy('service_plan.price', 'asc');
			} elseif ($order_by == 'high_to_low') {
				$Service = $Service->orderBy('service_plan.price', 'desc');
			} elseif($order_by == 'least_reviews') {
				$Service = $Service->orderBy('services.total_review_count', 'asc');
				$Service = $Service->orderBy('service_round_rating', 'asc');
			} else {
				if($request->segment(1) == 'review-editions') {
					$Service = $Service->orderBy('services.total_review_count', 'asc');
					$Service = $Service->orderBy('service_round_rating', 'asc');
				}else{
					$Service = $Service->orderBy('services.total_review_count', 'desc');
					$Service = $Service->orderBy('service_round_rating', 'desc');
				}
			}
		}

		$category_id = $request->get('categories');
		$defaultSubcatId = $request->get('subcategories');
		
		$sponseredService = null;
		if(!isset($request->page) || (isset($request->page) && $request->page == 1)){
			$sponseredService = $this->getCategoryAndSubcategoryPageSponserService($category_id,$defaultSubcatId);
		}

		if($request->segment(1) == 'premium-services') {
			$grace_days = env('PREMIUM_SELLER_SUBSCRIPTION_GRACE_DAYS');
			$end_date = Carbon::now()->subDays($grace_days)->format('Y-m-d H:i:s');
			$Service = $Service->join('subscribe_users', 'subscribe_users.user_id', '=', 'services.uid')
								->where('subscribe_users.end_date','>=',$end_date);
		}

		if($request->segment(1) == 'by_us_for_us') {
			$Service = $Service->where('by_us_for_us',1);
		}

		$Service = $Service->join('category', 'category.id', '=', 'services.category_id')
		->join('users', 'services.uid', '=', 'users.id')
		->join('service_plan', 'service_plan.service_id', '=', 'services.id')
		->where('service_plan.plan_type', 'basic')
		->where('services.is_delete',0)
		->distinct()
		->paginate(21);
		$imglink = GeneralSetting::where('settingkey','service_job_banner_img')->pluck('settingvalue')->first();
		$bannerlink = GeneralSetting::where('settingkey','service_job_banner_link')->pluck('settingvalue')->first();

		return view('frontend.service.filterservices', compact('Service', 'sponseredService','imglink','bannerlink'))->render();
	}

	public function details($username, $seo_url,Request $request) {
	
		$Service = Service::whereIn('status', ['active', 'denied','draft','paused'])
		->where('seo_url', $seo_url)
		->where('is_delete',0)
		->where('is_job',0)
		->where('is_custom_order',0)
		->first();

		//Check service found or not
		if (empty($Service)) {
			return redirect('404');
		}

		$serviceUser = User::select('id','status','soft_ban')->where('id',$Service->uid)
			->where('is_delete', 0)->where('vacation_mode', 0)->where('username',$username)->first();
		
		if(is_null($serviceUser)) {
			return redirect('404');
		}

		$can_access = true;
		if(Auth::check()){
			//With Login Check Access
			if($Service->uid != Auth::id()){ 
				//Other Seller Login
				if($Service->status == 'draft' || $Service->is_approved == 0) {
					$can_access = false;
				}
				if($Service->status == 'paused'){
					if($Service->current_step < 5){
						$can_access = false;
					}
				}elseif($Service->status == 'denied'){
					if($serviceUser->status == 1 && $serviceUser->soft_ban == 0){
						$can_access = false;
					}
					if($Service->current_step < 5 || $Service->is_delete != 0){
						$can_access = false;
					}
				}
			}else{
				//Seller Owner Login than access to preview
			}
		}else{
			//Without Login Check Access
			if($Service->status == 'draft' || $Service->is_approved == 0) {
				$can_access = false;
			}

			if($Service->status == 'paused'){
				if($Service->current_step < 5){
					$can_access = false;
				}
			}elseif($Service->status == 'denied'){
				if($serviceUser->status == 1 && $serviceUser->soft_ban == 0){
					$can_access = false;
				}
				if($Service->current_step < 5 || $Service->is_delete != 0){
					$can_access = false;
				}
			}
			
		}

		if($can_access == false){
			return redirect('404');
		}
		
		/*Check Blocked users*/
		$block_users = User::getBlockedByIds();
		if(in_array($Service->uid,$block_users)){
			abort(401);
		}
		/*End Check Blocked user*/

		/* Update Service review Count */
		$total_reviews = Order::select('id')->where(['service_id' => $Service->id])
		->whereIn('status',['cancelled','completed'])
		//->whereRaw('(completed_note is not null OR seller_rating > 0)')
		->where('seller_rating', '>', 0)
		->count();
		
		$Service->total_review_count = $total_reviews;
		$Service->updated_at = Carbon::now()->format('Y-m-d H:i:s');
		$Service->save();

		$id = $Service->id;

		if($Service->uid != $this->uid) {
			$home_page_data = UserHomePickService::where('user_id',$this->uid)->first();
			if(is_null($home_page_data)) {
				$user_home_data = new UserHomePickService;
				$user_home_data->user_id = $this->uid;
				$user_home_data->recently_viewed_service = $id;
				$user_home_data->save();
			} else {
				$home_page_data->recently_viewed_service = $id;
				$home_page_data->save();
			}
		}

		$serviceid = Service::select('id')->where('id',$id)->orwhere('parent_id',$id)->get()->makeHidden('secret')->toArray();
		
		$Comment = Order::select('id', 'uid','service_id', 'completed_note', 'review_date', 'plan_type', 'package_name', 'seller_rating', 'completed_reply','status','cancel_date','helpful_count','is_review_edition')
		->whereIn('status',['cancelled','completed'])
		//->whereRaw('(completed_note is not null OR seller_rating > 0)')
		->where('seller_rating', '>' ,0)
		->whereIn('service_id',$serviceid);

		if($request->filled('rating')){
			if($request->rating == "best"){
				$Comment = $Comment->where('seller_rating','>=',3)->orderBy('seller_rating','desc');

			}
			else if($request->rating == "worst"){
				$Comment = $Comment->where('seller_rating','<=',2)->orderBy('seller_rating','asc');			
			}
			else if($request->rating == "newest"){
				$Comment = $Comment->orderBy('review_date','desc');
			}
			else if($request->rating == "oldest"){

				$Comment = $Comment->orderBy('review_date','ASC');
			}else{
				$Comment = $Comment->orderBy('review_date','desc');
			}

		}else{
			$Comment = $Comment->orderBy('review_date','desc');
		}

		$Comment = $Comment->paginate(10);

		$Comment->withPath(route('getallreview'));

		$CommentCount = Order::select('id')->where(['status' => 'completed', 'service_id' => $id, 'is_review' => 1])->count();

		$ratingModel = new Order;

		$avg_service_rating = $ratingModel->calculateServiceAverageRating($id);
		$avg_seller_rating = $ratingModel->calculateSellerAverageRating($Service->uid);

		$Service->service_rating = $avg_service_rating;
		$Service->save();

		/*Update Total Service view By Month*/
		if(Auth::check() && $Service->uid != Auth::user()->id){
			$sellerAnalytic = SellerAnalytic::select('id')->where('service_id',$id)
			->where('buyer_uid',Auth::user()->id)
			->where('type','service_view')
			->whereMonth('created_at', date('m'))
			->whereYear('created_at', date('Y'))
			->count();
			if($sellerAnalytic == 0){
				$sellerAnalytic = new SellerAnalytic;
				$sellerAnalytic->service_id = $id;
				$sellerAnalytic->buyer_uid = Auth::user()->id;
				$sellerAnalytic->type = 'service_view';
				$sellerAnalytic->save(); 
			}
		}

		$buddleservice = BundleService::where('service_id',$Service->id)->first();
		$otherservice = null;
		if(count($buddleservice)){
			$otherservice = BundleService::where('bundle_id',$buddleservice->bundle_id)
			->where('service_id','!=',$Service->id)
			->whereHas('service',function($q){
				$q->select('id');
				$q->where('is_approved',1);
				$q->where('status','active');
				$q->where('is_delete',0);
			})
			->get();
		}

		$uid = $this->uid;

		$save_template = SaveTemplate::where('seller_uid',$uid)
		->where('template_for',1)
		->orderBy('title', 'asc')
		->pluck('title', 'id')
		->toArray();

		$save_template_chat = SaveTemplate::where('seller_uid',$uid)
		->where('template_for',2)
		->orderBy('title', 'asc')
		->pluck('title', 'id')
		->toArray();		


		$reviewPlanData=[];
		$servicePlanData=ServicePlan::where('service_id',$id)->get();
		foreach ($servicePlanData as $key) {

			$orderReviewTotal=Order::select('id')->where('service_id',$id)
			->where('plan_type',$key->plan_type)
			->whereIn('status',['cancelled','completed'])
			//->whereRaw('(completed_note is not null OR seller_rating > 0)')
			->where('seller_rating', '>', 0)
			->count();		

			$orderReview=Order::select('completed_note')->where('plan_type',$key->plan_type)
			->where('service_id',$id)
			->whereIn('status',['cancelled','completed'])
			//->whereRaw('(completed_note is not null OR seller_rating > 0)')
			->where('seller_rating', '>', 0)
			->orderBy('id','desc')->first();

			$completed_note = '';
			if(count($orderReview)){
				$completed_note = $orderReview->completed_note;
			}			
			$reviewPlanData[]=['plan_type' => $key->plan_type,'review' => $completed_note,'total_review' => $orderReviewTotal];
		}


		/*redirect to cart page with service added in cart if guest user login to buy service*/
		$dyanmicOrder=Session::get('service_id');
		if($dyanmicOrder != 0)
		{
			if($Service->uid != Auth::user()->id)
			{

				/*Check for order in queue*/
				$allowbackOrder = $Service->allowBackOrder();
				if($allowbackOrder->can_place_order == true){
					$Service = Service::with('images', 'extra', 'coupon')->find($id);

					$ServicePlan = ServicePlan::where('service_id',$id)->find($dyanmicOrder);

					if(!empty($ServicePlan)){

						$settings = Setting::find(1)->first();
						$abandoned_cart = json_decode($settings->abandoned_cart_email);
						$influencer = Session::get('influencer');
						$influencer_id = 0;
						if(!empty($influencer)) {
							$influencer_data = Influencer::where('slug',$influencer)->select('id')->first();
							if(!is_null($influencer_data)) {
								$influencer_id = $influencer_data->id;
							}
						}
						$cart_exist = Cart::where('uid',Auth::id())->where('service_id',$id)->where('plan_id',$dyanmicOrder)->first();
						if(is_null($cart_exist)) {
							$inserData=new Cart;
							$inserData->uid = Auth::id();
							$inserData->quantity=1;
							$inserData->plan_id=$dyanmicOrder;
							$inserData->service_id=$id;
							$inserData->coupon_id=0;
							$inserData->email_index = 1;
							$inserData->email_send_at = date('Y-m-d H:i:s', strtotime(' + '.$abandoned_cart[0]->duration.' '.$abandoned_cart[0]->span.'s'));
							$inserData->influencer_id = $influencer_id;
							$inserData->save();
						} else {
							if($cart_exist->service->is_recurring == 0){
								$cart_exist->quantity = (int)$cart_exist->quantity + 1;
							}else{
								$cart_exist->quantity = 1;
							}
							if($cart_exist->influencer_id == 0 && $influencer_id != 0) {
								$cart_exist->influencer_id = $influencer_id;
							}
							$cart_exist->save();
						}
					}
				}else{
					Session::flash('tostError', 'No of order in queue has been over.');
				}
				Session::forget('service_id');
				Session::forget('influencer'); 
				return redirect(route('view_cart'));
			}
			else
			{
				Session::forget('service_id'); 
				Session::forget('influencer'); 
				return redirect(url('/'));
			}
		}

		/*check if guest user logins and wants to send message after login*/
		$dyanmicmsg=Session::get('sendmsg');
		if($dyanmicmsg != 0)
		{
			if($Service->uid != Auth::user()->id)
			{
				Session::forget('sendmsg'); 
				$showMsg=1;
			}
			else
			{
				Session::forget('sendmsg'); 
				$showMsg=0;
			}
		}
		else 
		{
			$showMsg=0;
		}

		/*check if guest user logins and wants to send custom order request after login*/
		$dyanmicCustom=Session::get('customOrder');
		if($dyanmicCustom != 0)
		{
			if($Service->uid != Auth::user()->id)
			{
				Session::forget('customOrder'); 
				$showCustomBox=1;
			}
			else
			{
				Session::forget('customOrder'); 
				$showCustomBox=0;
			}
		}
		else 
		{
			$showCustomBox=0;
		}

		/* check if guest user wants to buys combo service has to login first after that continues the flow */
		$dyanamicBundle=Session::get('bundle_id');
		if($dyanamicBundle != 0)
		{
			if($Service->uid != Auth::user()->id)
			{

				$uid = Auth::user()->id;

				$bundleservice = BundleService::where('bundle_id',$dyanamicBundle)->get();

				if(!$bundleservice->isempty()){
					foreach ($bundleservice as $allsevices) {
						$service = Service::where('id',$allsevices->service_id)->first();
						/*Check for order in queue*/
						$allowbackOrder = $service->allowBackOrder();
						if($allowbackOrder->can_place_order == true){
							$plan_type='basic';
							if($id == $service->id)
							{
								$plan_type = Session::get('packageType');	
							}
							$servicePlan = ServicePlan::where(['service_id'=>$service->id,'plan_type' => $plan_type])->first();
							if($service && $servicePlan){
								$seviceid = $service->id;
								$planid = $servicePlan->id;
								$qnt = 1;

								$cart = Cart::where(['uid' => $uid, 'service_id' => $service->id, 'plan_id' => $planid])->first();

								if (empty($cart)) {
									$cart = new Cart;
									$cart->uid = $uid;
									$cart->service_id = $service->id;
									$cart->plan_id = $planid;
									$cart->quantity = $qnt;
									$cart->save();
									\Session::put('dataLayerCartId',$cart->id);
								} else {
									if($cart->service->is_recurring == 0){
										$cart->quantity = $cart->quantity + $qnt;
									}else{
										$cart->quantity = 1;
									}
									$cart->save();
								}

								/*Update Total Add to cart By Month*/
								$sellerAnalytic = SellerAnalytic::select('id')->where('service_id',$cart->service_id)
								->where('buyer_uid',Auth::user()->id)
								->where('type','add_to_cart')
								->whereMonth('created_at', date('m'))
								->whereYear('created_at', date('Y'))
								->count();
								if($sellerAnalytic == 0){
									$sellerAnalytic = new SellerAnalytic;
									$sellerAnalytic->service_id = $cart->service_id;
									$sellerAnalytic->buyer_uid = Auth::user()->id;
									$sellerAnalytic->type = 'add_to_cart';
									$sellerAnalytic->save(); 
								}
							}
						}else{
							Session::flash('tostError', 'No of order in queue has been over.');
						}
					} 
				}

				Session::flash('tostSuccess', 'Item added to cart.');
				Session::forget('bundle_id'); 
				Session::forget('combo_plan_id'); 
				Session::forget('packageType'); 
				return redirect(route('view_cart'));
			}
			else
			{
				Session::forget('bundle_id'); 
				Session::forget('combo_plan_id'); 
				Session::forget('packageType'); 
				return redirect(url('/'));
			}
		}

		/*get total order in queue*/
		$total_queue_orders = $Service->getTotalQueueOrdersCount();

		/* Check extra is available or not */
		$is_extra_available = true;
		if(count($Service->extra) > 0){
			$is_extra_available = false; 
		}
		return view('frontend.service.details', compact('Service', 'Comment', 'avg_service_rating', 'avg_seller_rating', 'CommentCount','buddleservice','otherservice','save_template','save_template_chat','reviewPlanData','username','seo_url','showMsg','showCustomBox','total_queue_orders','is_extra_available'));

	}

	public function detailsForAdmin($username, $seo_url,$token,request $request) {

		$Service = Service::where('seo_url', $seo_url)->where('is_delete',0)->first();

		if (empty($Service)) {
			return redirect('404');
		}

		$serviceUser = User::select('id')->where('id',$Service->uid)->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->where('username',$username)->first();
		if(is_null($serviceUser)) {
			return redirect('404');
		}

		/*$master_admin = Admin::where('email','info@demo.com')->select('email','password')->first();
        $encodedParam = json_encode($master_admin);
        $access_token = base64_encode($encodedParam);
        $access_token = str_replace("/","", $access_token);

        if($access_token != $token){
        	return redirect('404');
        }*/

		$id = $Service->id;
		$serviceid = Service::select('id')->where('id',$id)->orwhere('parent_id',$id)->get()->makeHidden('secret')->toArray();
		$Comment = Order::select('id', 'uid','service_id', 'completed_note', 'review_date', 'plan_type', 'package_name', 'seller_rating', 'completed_reply','status','cancel_date','helpful_count','is_review_edition')
		->where(['status' => 'completed','is_review' => 1])->whereIn('service_id',$serviceid);

		if($request->has('rating')){
			if($request->rating == "best"){
				$Comment = $Comment->where('seller_rating','>=',3)->orderBy('seller_rating','desc');

			}
			else if($request->rating == "worst"){
				$Comment = $Comment->where('seller_rating','<=',2)->orderBy('seller_rating','asc');			
			}
			else if($request->rating == "newest"){
				$Comment = $Comment->orderBy('review_date','desc');
			}
			else if($request->rating == "oldest"){

				$Comment = $Comment->orderBy('review_date','ASC');
			}else{
				$Comment = $Comment->orderBy('review_date','desc');
			}

		}else{

			$Comment = $Comment->orderBy('review_date','desc');
		}

		$Comment = $Comment->paginate(10);

		$Comment->withPath(route('getallreview'));

		$CommentCount = Order::select('id')->where(['status' => 'completed', 'service_id' => $id, 'is_review' => 1])->count();

		$ratingModel = new Order;

		$avg_service_rating = $ratingModel->calculateServiceAverageRating($id);
		$avg_seller_rating = $ratingModel->calculateSellerAverageRating($Service->uid);

		$Service->service_rating = $avg_service_rating;
		$Service->save();
		
		$buddleservice = BundleService::where('service_id',$Service->id)->first();
		$otherservice = null;
		if(count($buddleservice)){
			$otherservice = BundleService::where('bundle_id',$buddleservice->bundle_id)
			->where('service_id','!=',$Service->id)
			->whereHas('service',function($q){
				$q->select('id');
				$q->where('is_approved',1);
				$q->where('status','active');
				$q->where('is_delete',0);
			})
			->get();
		}

		$uid = $this->uid;

		$save_template = SaveTemplate::where('seller_uid',$uid)
		->where('template_for',1)
		->orderBy('title', 'asc')
		->pluck('title', 'id')
		->toArray();

		$save_template_chat = SaveTemplate::where('seller_uid',$uid)
		->where('template_for',2)
		->orderBy('title', 'asc')
		->pluck('title', 'id')
		->toArray();		


		$reviewPlanData=[];
		$servicePlanData=ServicePlan::where('service_id',$id)->get();
		foreach ($servicePlanData as $key) {

			$orderReviewTotal=Order::select('id')->where('service_id',$id)
			->where('plan_type',$key->plan_type)
			->whereIn('status',['cancelled','completed'])
			//->whereRaw('(completed_note is not null OR seller_rating > 0)')
			->where('seller_rating', '>', 0)
			->count();		

			$orderReview=Order::select('completed_note')->where('plan_type',$key->plan_type)
			->where('service_id',$id)
			->whereIn('status',['cancelled','completed'])
			//->whereRaw('(completed_note is not null OR seller_rating > 0)')
			->where('seller_rating', '>', 0)
			->orderBy('id','desc')->first();

			$completed_note = '';
			if(count($orderReview)){
				$completed_note = $orderReview->completed_note;
			}			
			$reviewPlanData[]=['plan_type' => $key->plan_type,'review' => $completed_note,'total_review' => $orderReviewTotal];
		}

		$showMsg = $showCustomBox = 0;

		return view('frontend.service.admin.details', compact('Service', 'Comment', 'avg_service_rating', 'avg_seller_rating', 'CommentCount','buddleservice','otherservice','save_template','save_template_chat','reviewPlanData','username','seo_url','showMsg','showCustomBox'));

	}

	public function servicedetails($id) {
		$Service = Service::with('user');

		$Service = $Service->whereHas('user', function($query) {
			$query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
		});
		$Service = $Service->find($id);
		if(is_null($Service)) {
			return redirect()->back();
		}
		return redirect()->route('services_details', [$Service->user->username, $Service->seo_url]);
	}

	public function getallreview(Request $request) {


		if ($request->input()) {

			$Service = Service::with('user.country', 'category', 'images', 'video', 'pdf', 'basic_plans', 'standard_plans', 'premium_plans');
			if (Auth::check()) {
				$Service = $Service->with('favorite');
			}
			$Service = $Service->whereIn('status', ['active', 'denied','custom_order'])->find($request->id);
			$id =$request->id;
			$serviceid = Service::select('id')->where('id',$id)->orwhere('parent_id',$id)->get()->makeHidden('secret')->toArray();

			$Comment = Order::with('user', "review_log")
			->select('id', 'uid','service_id','helpful_count' ,'completed_note', 'review_date', 'plan_type', 'package_name', 'seller_rating', 'completed_reply','status','cancel_date','is_review_edition')
			->whereIn('status',['cancelled','completed'])
			//->whereRaw('(completed_note is not null OR seller_rating > 0)')
			->where('seller_rating', '>' ,0)
			->whereIn('service_id',$serviceid);

			if($request->filled('rating')){
				if($request->rating == "best"){
					$Comment = $Comment->where('seller_rating','>=',3)->orderBy('seller_rating','desc');

				}
				else if($request->rating == "worst"){
					$Comment = $Comment->where('seller_rating','<=',2)->orderBy('seller_rating','asc');			
				}
				else if($request->rating == "newest"){
					$Comment = $Comment->orderBy('review_date','desc');
				}
				else if($request->rating == "oldest"){

					$Comment = $Comment->orderBy('review_date','ASC');
				}else{
					$Comment = $Comment->orderBy('review_date','desc');
				}

			}else{

				$Comment = $Comment->orderBy('review_date','desc');
			}

			if($request->filled('rating_count') && $request->rating_count > 0){
				$Comment = $Comment->where('seller_rating',$request->rating_count);
			}
			
			$Comment = $Comment->paginate(10);

			$seo_url=$request->seo_url;
			$username=$request->username;

			$reviewPlanData=[];
			$servicePlanData=ServicePlan::where('service_id',$id)->get();
			foreach ($servicePlanData as $key) {

				$orderReviewTotal=Order::select('id')->where('service_id',$id)
				->where('plan_type',$key->plan_type)
				->whereIn('status',['cancelled','completed'])
				//->whereRaw('(completed_note is not null OR seller_rating > 0)')
				->where('seller_rating', '>', 0)
				->count();		

				$orderReview=Order::select('completed_note')->where('plan_type',$key->plan_type)
				->where('service_id',$id)
				->whereIn('status',['cancelled','completed'])
				//->whereRaw('(completed_note is not null OR seller_rating > 0)')
				->where('seller_rating', '>', 0)
				->orderBy('id','desc')->first();

				$completed_note = '';
				if(count($orderReview)){
					$completed_note = $orderReview->completed_note;
				}			
				$reviewPlanData[]=['plan_type' => $key->plan_type,'review' => $completed_note,'total_review' => $orderReviewTotal];
			}

			$Comment->withPath(route('getallreview'));
			return view('frontend.service.reviewlist', compact('Comment', 'Service','seo_url','username','reviewPlanData'))->render();
		}
	}

	public function overview(Request $request) {
		if(Auth::user()->username == 'scottfarrar'){
			return redirect('404');
		}

		//Admin can make user to soft ban , so user can't place any service
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		if(Auth::user()->parent_id == 0 && (!Auth::user()->description || (!Auth::user()->profile_photo || !Auth::user()->photo_s3_key))) {
			Session::flash('errorFails', 'Please update your profile to include a profile photo and description before creating or editing a service.');
			if(!Auth::user()->profile_photo || !Auth::user()->photo_s3_key) {
				return redirect()->route('accountsetting');
			} else {
				return redirect()->route('seller_profile');
			}
		}
		if ($request->input()) {
			if(Auth::user()->can_create_service == 1) {
				Session::flash('errorFails', 'Your account is unable to create new services at this time. Please contact support for more information.');
				return redirect()->route('services');
			}
			$input = $request->input();

			/*$secondary_cat_group = $input['secondary_cat_group'];
			unset($input['secondary_cat_group']);
			*/

			$input['limit_no_of_orders'] = 0;
			$input['allow_backorders'] = 0;

			if($request->filled('is_recurring'))
			{
				$input['is_recurring'] = 1;
			}
			else
			{
				$input['is_recurring'] = 0;

				if($request->filled('limit_no_of_orders') && $request->limit_no_of_orders > 0){
					$input['limit_no_of_orders'] = $request->limit_no_of_orders;
					if($request->filled('allow_backorders'))
					{
						$input['allow_backorders'] = 1;
					}
					else
					{
						$input['allow_backorders'] = 0;
					}
				}
			}	

			if($request->filled('is_affiliate_link'))
			{
				$input['is_affiliate_link'] = 1;

				$updUser=User::select('id','is_affiliate_service')->find(Auth::user()->id);
				$updUser->is_affiliate_service = 1;
				$updUser->save();
			}
			else
			{
				$input['is_affiliate_link'] = 0;	
			}
			
			if(Auth::user()->is_premium_seller($uid) == true){
				$input['is_private'] = ($request->filled('is_private')) ? 0 : 1;
			}else{
				$input['is_private'] = 0;
			}

			$uid = $this->uid;
			
			$input['last_updated_by'] = Auth::user()->id;
			$input['uid'] = $uid;
			unset($input['_token']);

			/* Add SEO URL */
			$seo_url = Str::slug($input['title'], '-');

			$exists = Service::select('id')->where('seo_url', $seo_url)->first();
			if (count($exists)) {
				$input['seo_url'] = $seo_url . '-' . time();
			} else {
				$input['seo_url'] = $seo_url;
			}
			$input['last_updated_on'] = Carbon::now()->format('Y-m-d H:i:s');

			//Check if category is by us for us
			if(isset($input['category_id'])) {
				$selectedCategory = Category::select('seo_url')->where('seo_url','by-us-for-us')->find($input['category_id']);
				if(!empty($selectedCategory)){
					$input['by_us_for_us'] = 1;
				}else{
					$input['by_us_for_us'] = 0;
				}
			}

			$id = Service::insertGetId($input);
			
			/* Store Revision */
			$revision_input['service_id'] = $id; 
			$revision_input['title'] = $input['title']; 
			$revision_input['subtitle'] = $input['subtitle'];
			$revision_input['category_id'] = $input['category_id'];
			$revision_input['subcategory_id'] = $input['subcategory_id'];
			ServiceRevision::create($revision_input);
			
			/* ---- store categories ---- */
			$cat = [];
			$cat_array = [
				'uid' => $this->uid,
				'service_id' => $id,
				'category_id' => $input['category_id'],
				'sub_category_id' => $input['subcategory_id'],
				'is_default' => true
			];
			array_push($cat, $cat_array);
			/*if(isset($secondary_cat_group)) {
				for ($i=1; $i < count($secondary_cat_group); $i++) { 
					$cat_array = [
						'uid' => $this->uid,
						'service_id' => $id,
						'category_id' => $secondary_cat_group[$i]['secondary_category_id'],
						'sub_category_id' => $secondary_cat_group[$i]['secondary_subcategory_id'],
						'is_default' => false
					];
					array_push($cat, $cat_array);
				}
			}*/
			SellerCategories::insert($cat);
			$servc = Service::where('id',$id)->select('id','seo_url')->first();
			return redirect(route('services_pricing', $servc->seo_url));
		} else {
			$Category = Category::pluck('category_name', 'id')->toArray();
			return view('frontend.service.create', compact('Category'));
		}
	}

	public function overview_update(Request $request, $seo_url) {
		//Admin can make user to soft ban , so user can't place any edit
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		if(Auth::user()->parent_id == 0 && (!Auth::user()->description || (!Auth::user()->profile_photo || !Auth::user()->photo_s3_key))) {
			Session::flash('errorFails', 'Please update your profile to include a profile photo and description before creating or editing a service.');
			if(!Auth::user()->profile_photo || !Auth::user()->photo_s3_key) {
				return redirect()->route('accountsetting');
			} else {
				return redirect()->route('seller_profile');
			}
		}
		$uid = $this->uid;

		$Service = Service::where(['uid' => $uid, 'seo_url' => $seo_url])
		->where('is_custom_order', 0)
		->where('is_job', 0)
		->where('is_delete',0)
		->where('status','!=','permanently_denied')
		->first();
		
		if (empty($Service)) {
			return redirect(route('services'));
		}
		
		if($Service->is_recurring==1){
			$recuring=$Service->is_recurring;
		}
		if ($request->input()) {
			$input = $request->input();

			$preview = 'false';
			if($request->filled('preview') && $request->preview == 'true') {
				$preview = 'true';
			}
			unset($input['preview']);


			if ($Service->current_step >= "5") 
			{
				if ($Service->status != "denied"){
					unset($input['category_id']);
					unset($input['subcategory_id']);
				}
			}else{
				SellerCategories::where(['service_id' => $Service->id,'is_default' => 1])->update(['category_id'=>$request->category_id,'sub_category_id'=>$request->subcategory_id]);
			}

			//dd($input);
		
			/*foreach ($input['seller_cat_id'] as $key => $value) {
				$seller_category = SellerCategories::find($value);

				if(isset($input['subcategory_id']) && isset($input['subcategory_id'][$key])){
					$seller_category->sub_category_id = $input['subcategory_id'][$key];
					$seller_category->save();

					if(isset($input['category_id']) && isset($input['category_id'][$key])){
						$seller_category->category_id = $input['category_id'][$key];
						$seller_category->save();
					}
				}
			}*/

			/* ---- store categories ---- */
			/*$secondary_cat_group = $input['secondary_cat_group'];
			if(isset($secondary_cat_group)) {
				$cat = [];
				for ($i=1; $i < count($secondary_cat_group); $i++) { 
					$cat_array = [
						'uid' => $this->uid,
						'service_id' => $Service->id,
						'category_id' => $secondary_cat_group[$i]['secondary_category_id'],
						'sub_category_id' => $secondary_cat_group[$i]['secondary_subcategory_id'],
					];
					array_push($cat, $cat_array);
				}
				SellerCategories::insert($cat);
			}
			
			unset($input['seller_cat_id']);
			unset($input['secondary_cat_group']);
			$input['category_id'] = $input['category_id'][0];
			$input['subcategory_id'] = $input['subcategory_id'][0];
			*/

			if ($Service->current_step == "5") 
			{
				unset($input['current_step']);
				if ($input['title'] != $Service->title || $input['subtitle'] != $Service->subtitle) 
				{
					/* Update Service Revision */ 
					$service_revision = ServiceRevision::where('service_id',$Service->id)->first();
					if(is_null($service_revision)){
						$service_revision = new ServiceRevision;
						$service_revision->service_id = $Service->id;
					}
					$service_revision->title = $input['title'];
					$service_revision->subtitle = $input['subtitle'];
					$service_revision->is_approved = 0;
					$service_revision->save();

					$Service->is_revision_approved = 0;
					$Service->save();
				}
			}

			if ($Service->status == "denied" && $input['title'] != $Service->title) {
				/* update seo url while service denied */
				$new_seo_url = Str::slug($input['title'], '-');

				$exists_seo_url = Service::where('seo_url', $new_seo_url)->select('id')->first();
				if (count($exists_seo_url)) {
					$input['seo_url'] = $new_seo_url . '-' . time();
				} else {
					$input['seo_url'] = $new_seo_url;
				}
			}
			
			$input['last_updated_by'] = Auth::user()->id;



			if($request->filled('limit_no_of_orders') && $request->limit_no_of_orders > 0 && $Service->is_recurring == 0){
				$input['limit_no_of_orders'] = $request->limit_no_of_orders;
				if($request->filled('allow_backorders'))
				{
					$input['allow_backorders'] = 1;
				}
				else
				{
					$input['allow_backorders'] = 0;
				}

				
			}else{
				$input['limit_no_of_orders'] = 0;
				$input['allow_backorders'] = 0;
			}

			/*if($request->filled('is_recurring'))
			{
				$input['is_recurring'] = 1;
			}
			else
			{
				$input['is_recurring'] = 0;
			}	*/

			if($request->filled('is_affiliate_link'))
			{
				$input['is_affiliate_link'] = 1;

				$updUser=User::select('id','is_affiliate_service')->find(Auth::user()->id);
				$updUser->is_affiliate_service = 1;
				$updUser->save();
			}
			else
			{
				$input['is_affiliate_link'] = 0;	
			}

			if(Auth::user()->is_premium_seller($uid) == true){
				$input['is_private'] = ($request->filled('is_private')) ? 0 : 1;
			}else{
				$input['is_private'] = 0;
			}

			//dd($input);exit();



			unset($input['_token']);
			$input['last_updated_on'] = Carbon::now()->format('Y-m-d H:i:s');
			//Service::where('id', $Service->id)->update($input);

			/* updating input data in database */
			if($Service->is_approved == 0){
				$Service->title = $input['title'];
				$Service->subtitle = $input['subtitle'];
			}
			$Service->limit_no_of_orders = $input['limit_no_of_orders'];
			$Service->last_updated_by = $input['last_updated_by'];
			$Service->allow_backorders = $input['allow_backorders'];
			$Service->is_affiliate_link = $input['is_affiliate_link'];
			$Service->is_private = $input['is_private'];
			$Service->last_updated_on = $input['last_updated_on'];
			if(isset($input['current_step'])) {
				$Service->current_step = $input['current_step'];
			}
			if(isset($input['category_id'])) {
				$Service->category_id = $input['category_id'];

				//Check if category is by us for us
				$selectedCategory = Category::select('seo_url')->where('seo_url','by-us-for-us')->find($Service->category_id);
				if(!empty($selectedCategory)){
					$Service->by_us_for_us = 1;
				}else{
					$Service->by_us_for_us = 0;
				}
			}
			if(isset($input['subcategory_id']) && $input['subcategory_id']) {
				$Service->subcategory_id = $input['subcategory_id'];
			}
			if(isset($input['is_recurring'])) {
				$Service->is_recurring = $input['is_recurring'];
			}
			if(isset($input['seo_url'])) {
				$Service->seo_url = $input['seo_url'];
			}
			$Service->save();
			$seo_url = $Service->seo_url;

			/*Check new limit no of order is greater then old limit then start remaining orders*/
			if($request->filled('limit_no_of_orders') && $Service->is_recurring == 0 && $Service->limit_no_of_orders > 0 && ($request->limit_no_of_orders > $Service->limit_no_of_orders)){

				$total_queue_orders = $Service->getTotalQueueOrdersCount();
				if($total_queue_orders > 0){
					$total_active_orders = $Service->getTotalActiveOrdersCount();
					$orders_to_be_active = $request->limit_no_of_orders - $total_active_orders;
					$orderObj = new Order;
					$orderObj->makeOrderOnHoldToActive($Service,$orders_to_be_active);
				}
			}

			if($preview == 'true') {
				$current_url = '';
				if(isset($input['seo_url'])) {
					$current_url = route('overview_update', $seo_url);
				}
				//Check for review edition
				if($Service->is_review_edition == 1 && $Service->review_edition_count < $Service->no_of_review_editions){
					return response()->json(['status' => 'success','url'=>route('services_details',['username'=>$Service->user->username,'seo_url'=> $Service->seo_url,'review-edition'=>1]),'current_url'=>$current_url]);
				}else{
					return response()->json(['status' => 'success','url'=>route('services_details',[$Service->user->username,$Service->seo_url]),'current_url'=>$current_url]);
				}
			}
			return redirect(route('services_pricing', $seo_url));
		} else {
			
			/* Show service changes */
			if($Service->revisions != null){
				if($Service->revisions->title != ""){
					$Service->title = $Service->revisions->title;
				}
				if($Service->revisions->subtitle != ""){
					$Service->subtitle = $Service->revisions->subtitle;
				}
				if($Service->revisions->category_id != ""){
					$Service->category_id = $Service->revisions->category_id;
				}
				if($Service->revisions->subcategory_id != ""){
					$Service->subcategory_id = $Service->revisions->subcategory_id;
				}
			}

			$Category = Category::pluck('category_name', 'id')->toArray();
			$Subcategory = Subcategory::where('category_id', $Service->category_id)->where('status',1)->pluck('subcategory_name', 'id')->toArray();
			//dd($Subcategory);
			/*$Subcategory = [];
			foreach ($Service['seller_categories'] as $key => $value) {
				$sub = Subcategory::where('category_id', $value['category_id'])->pluck('subcategory_name', 'id')->toArray();
				array_push($Subcategory, $sub);
			}*/

			$category_slug = null;
			$selectedCategory = Category::select('seo_url')->find($Service->category_id);
			if(!empty($selectedCategory)){
				$category_slug = $selectedCategory->seo_url;
			}

			return view('frontend.service.update', compact('Category', 'Subcategory', 'Service','recuring','category_slug'));
		}
	}

	public function delete_seller_category(Request $request) {
		SellerCategories::where('id', $request['c_id'])->delete();	
		return response()->json(['status'=>'success']);
	}

	public function pricing(Request $request, $seo_url) {

		//Admin can make user to soft ban , so user can't place any edit
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		if ($seo_url != 'null') {

			$uid = $this->uid;

			$Service = Service::where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();

			if (empty($Service)) {
				return redirect(route('services'));
			}

			if ($request->input()) {
				$input = $request->input();
				$input['last_updated_by'] = Auth::user()->id;
				unset($input['_token']);

				if ($input['current_step'] < $Service->current_step) {
					unset($input['current_step']);
				} else {
					$Service->current_step = $request->current_step;
				}
				if (isset($request->three_plan)) {
					$Service->three_plan = $request->three_plan;
				} else {
					$Service->three_plan = 0;
				}

				//Update review editions
				if (isset($request->is_review_edition)) {
					$Service->is_review_edition = 1;
					$max_review_review_editions = $Service->get_no_of_review_editions();
					if($request->no_of_review_editions <= $max_review_review_editions){
						$Service->no_of_review_editions = $request->no_of_review_editions;
					}else{
						$Service->no_of_review_editions = $max_review_review_editions;
					}
				} else {
					$Service->is_review_edition = 0;
					$Service->no_of_review_editions = 0;
				}
				
				/* Create/Update Service Basic Plans */
				if (isset($input['basic']) && count($input['basic'])) {

					if(!isset($input['basic']['package_name']) || !isset($input['basic']['offering_details']) || !isset($input['basic']['delivery_days']) || !isset($input['basic']['price'])) {
						Session::flash('errorFails', 'Please fill all the details');
						return redirect()->back();
					}

					if($input['basic']['price'] < env('MINIMUM_SERVICE_PRICE')){
						Session::flash('errorFails', 'Price must be greater than or equal to $'.env('MINIMUM_SERVICE_PRICE').'.');
						return redirect()->back();
					}


					$basicPlan = ServicePlan::where(['service_id' => $Service->id, 'plan_type' => 'basic'])->first();
					if (empty($basicPlan)) {
						$basicPlan = new ServicePlan;
						$basicPlan->service_id = $Service->id;
						$basicPlan->plan_type = 'basic';
					}
					$basicPlan->package_name = $input['basic']['package_name'];
					$basicPlan->offering_details = $input['basic']['offering_details'];
					$basicPlan->delivery_days = $input['basic']['delivery_days'];
					$basicPlan->price = $input['basic']['price'];

					//Update review edition price
					if($Service->is_review_edition == 1){
						if($basicPlan->price > $request->re_basic_price){
							$basicPlan->review_edition_price = $request->re_basic_price;
						}else{
							$basicPlan->review_edition_price = 0;
						}
					}else{
						$basicPlan->review_edition_price = 0;
					}

					if($Service->is_recurring == 1){
						$basicPlan->no_of_revisions = 0;
					}else{
						$basicPlan->no_of_revisions = (isset($input['basic']['no_of_revisions'])) ? $input['basic']['no_of_revisions'] : 0;
					}
					$basicPlan->save();
				}

				/* Create/Update Service STANDARD Plans */
				if (isset($input['standard']) && count($input['standard'])) {

					if(!isset($input['standard']['package_name']) || !isset($input['standard']['offering_details']) || !isset($input['standard']['delivery_days']) || !isset($input['standard']['price'])) {
						Session::flash('errorFails', 'Please fill all the details');
						return redirect()->back();
					}

					if (isset($request->three_plan)) {
						if($input['standard']['price'] < env('MINIMUM_SERVICE_PRICE')){
							Session::flash('errorFails', 'Price must be greater than or equal to $'.env('MINIMUM_SERVICE_PRICE').'.');
							return redirect()->back();
						}
						if($input['standard']['price'] < $input['basic']['price']){
							Session::flash('errorFails', 'Standard price must be greater than or equal to basic plan.');
							return redirect()->back();
						}
					}

					$basicPlan = ServicePlan::where(['service_id' => $Service->id, 'plan_type' => 'standard'])->first();
					if (empty($basicPlan)) {
						$basicPlan = new ServicePlan;
						$basicPlan->service_id = $Service->id;
						$basicPlan->plan_type = 'standard';
					}
					$basicPlan->package_name = $input['standard']['package_name'];
					$basicPlan->offering_details = $input['standard']['offering_details'];
					$basicPlan->delivery_days = $input['standard']['delivery_days'];
					$basicPlan->price = $input['standard']['price'];

					//Update review edition price
					if($Service->is_review_edition == 1){
						if($basicPlan->price > $request->re_standard_price){
							$basicPlan->review_edition_price = $request->re_standard_price;
						}else{
							$basicPlan->review_edition_price = 0;
						}
					}else{
						$basicPlan->review_edition_price = 0;
					}

					$basicPlan->no_of_revisions = (isset($input['standard']['no_of_revisions'])) ? $input['standard']['no_of_revisions'] : 0;
					$basicPlan->save();
				}

				/* Create/Update Service PREMIUM Plans */
				if (isset($input['premium']) && count($input['premium'])) {

					if(!isset($input['premium']['package_name']) || !isset($input['premium']['offering_details']) || !isset($input['premium']['delivery_days']) || !isset($input['premium']['price'])) {
						Session::flash('errorFails', 'Please fill all the details');
						return redirect()->back();
					}

					if (isset($request->three_plan)) {
						if($input['premium']['price'] < env('MINIMUM_SERVICE_PRICE')){
							Session::flash('errorFails', 'Price must be greater than or equal to $'.env('MINIMUM_SERVICE_PRICE').'.');
							return redirect()->back();
						}
						if($input['premium']['price'] < $input['standard']['price'] || $input['premium']['price'] < $input['basic']['price']){
							Session::flash('errorFails', 'Premium price must be greater than or equal to basic/standard plan.');
							return redirect()->back();
						}
					}


					$basicPlan = ServicePlan::where(['service_id' => $Service->id, 'plan_type' => 'premium'])->first();
					if (empty($basicPlan)) {
						$basicPlan = new ServicePlan;
						$basicPlan->service_id = $Service->id;
						$basicPlan->plan_type = 'premium';
					}
					$basicPlan->package_name = $input['premium']['package_name'];
					$basicPlan->offering_details = $input['premium']['offering_details'];
					$basicPlan->delivery_days = $input['premium']['delivery_days'];
					$basicPlan->price = $input['premium']['price'];

					//Update review edition price
					if($Service->is_review_edition == 1){
						if($basicPlan->price > $request->re_premium_price){
							$basicPlan->review_edition_price = $request->re_premium_price;
						}else{
							$basicPlan->review_edition_price = 0;
						}
					}else{
						$basicPlan->review_edition_price = 0;
					}

					if(!isset($input['premium']['no_of_revisions']) && $input['unlimited_revision'] == 'true') {
						$basicPlan->no_of_revisions = -1;
					} else if(!isset($input['premium']['no_of_revisions'])) {
						$basicPlan->no_of_revisions = 0;
					} else {
						$basicPlan->no_of_revisions = $input['premium']['no_of_revisions'];
					}
					$basicPlan->save();
				}

				$Service->last_updated_on = Carbon::now()->format('Y-m-d H:i:s');
				$Service->save();

				/* Delete Service Revision Extras  */ 
				ServiceExtraRevision::where('service_id',$Service->id)->delete();
				
				/* Create/Update/Delete Extra */
				$available_extras = array();
				$is_extras_update = false;
				if (isset($input['extra']) && count($input['extra'])) {
					foreach ($input['extra'] as &$row) {
						$existServiceExtras = ServiceExtra::select('id')
							->where('service_id', $Service->id)
							->where('title', $row['title'])
							->where('description', $row['description'])
							->where('price', $row['price'])
							->where('delivery_days', $row['delivery_days'])
							->first();

						if($existServiceExtras){
							/* Add question id in array */
							$available_extras[] = $existServiceExtras->id;
						}else{
							$row['service_id'] = $Service->id;
							ServiceExtraRevision::create($row);
							$is_extras_update = true;
						}
					}
				}

				/* Delete Extras */ 
				$delete_extra = ServiceExtra::where('service_id', $Service->id);
				if(!empty($available_extras)){
					$delete_extra = $delete_extra->whereNotIn('id',$available_extras);
				}
				$delete_extra = $delete_extra->get();

				if($delete_extra){
					foreach ($delete_extra as $key => $value) {
						$value->is_delete = 1;
						$value->save();
						$is_extras_update = true;
					}
				}
				/* Added revisions */ 
				if($is_extras_update == true){
					$service_revision = ServiceRevision::where('service_id',$Service->id)->first();
					if(is_null($service_revision)){
						$service_revision = new ServiceRevision;
						$service_revision->service_id = $Service->id;
					}
					$service_revision->is_approved = 0;
					$service_revision->save();
					/* Update Service Revision */ 
					ServiceExtraRevision::where('service_id',$Service->id)->update(['service_revision_id' => $service_revision->id]);
					
					$Service->is_revision_approved = 0;
					$Service->save();
				}
				/* END Create/Update/Delete Extra */

				if($request->filled('preview') && $request->preview == 'true') {
					//Check for review edition
					if($Service->is_review_edition == 1 && $Service->review_edition_count < $Service->no_of_review_editions){
						return response()->json(['status' => 'success','url'=>route('services_details',['username'=>$Service->user->username,'seo_url'=> $Service->seo_url,'review-edition'=>1])]);
					}else{
						return response()->json(['status' => 'success','url'=>route('services_details',[$Service->user->username,$Service->seo_url])]);
					}
				}

				return redirect(route('services_desc', $Service->seo_url));
			} else {
				return view('frontend.service.pricing', compact('Service'));
			}
		}
	}

	public function description(Request $request, $seo_url) {

		//Admin can make user to soft ban , so user can't place any edit
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		if ($seo_url != 'null') {

			$uid = $this->uid;

			$Service = Service::where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();

			if (empty($Service)) {
				return redirect(route('services'));
			}

			if ($request->input()) {
				$input = $request->input();

				if(sizeof(explode(',',$request->tags)) > 3) {
					Session::flash('errorFails', 'Tags must not be greater than three.');
					return redirect()->back();
				}

				$preview = 'false';
				if($request->filled('preview') && $request->preview == 'true') {
					$preview = 'true';
				}
				unset($input['preview']);

				$input['last_updated_by'] = Auth::user()->id;
				unset($input['_token']);

				if ($input['current_step'] < $Service->current_step) {
					unset($input['current_step']);
				}
				
				if ($input['descriptions'] != $Service->descriptions || $input['meta_description'] != $Service->meta_description) 
				{
					/* Update Service Revision */ 
					$service_revision = ServiceRevision::where('service_id',$Service->id)->first();
					if(is_null($service_revision)){
						$service_revision = new ServiceRevision;
						$service_revision->service_id = $Service->id;
					}
					$service_revision->descriptions = $input['descriptions'];
					$service_revision->meta_description = $input['meta_description'];
					$service_revision->is_approved = 0;
					$service_revision->save();
					
					$Service->is_revision_approved = 0;
					$Service->save();
				}

				$input['last_updated_on'] = Carbon::now()->format('Y-m-d H:i:s');
				//Service::where('seo_url', $seo_url)->update($input);

				/* updating input data in database */
				$Service->youtube_url = $input['youtube_url'];
				$Service->tags = $input['tags'];
				$Service->meta_title = $input['meta_title'];
				$Service->meta_keywords = $input['meta_keywords'];
				if($Service->is_approved == 0){
					$Service->meta_description = $input['meta_description'];
					$Service->descriptions = $input['descriptions'];
				}
				$Service->last_updated_by = $input['last_updated_by'];
				$Service->last_updated_on = $input['last_updated_on'];
				if(isset($input['current_step'])) {
					$Service->current_step = $input['current_step'];
				}
				$Service->save();

				if($preview == 'true') {
					//Check for review edition
					if($Service->is_review_edition == 1 && $Service->review_edition_count < $Service->no_of_review_editions){
						return response()->json(['status' => 'success','url'=>route('services_details',['username'=>$Service->user->username,'seo_url'=> $Service->seo_url,'review-edition'=>1])]);
					}else{
						return response()->json(['status' => 'success','url'=>route('services_details',['username'=>$Service->user->username,'seo_url'=> $Service->seo_url])]);
					}
				}

				return redirect(route('services_req', $seo_url));
			} else {
				/* Show service changes */
				if($Service->revisions != null){
					if($Service->revisions->meta_description != ""){
						$Service->meta_description = $Service->revisions->meta_description;
					}
					if($Service->revisions->descriptions != ""){
						$Service->descriptions = $Service->revisions->descriptions;
					}
				}
				return view('frontend.service.description', compact('Service'));
			}
		}
	}

	public function requirement(Request $request, $seo_url) {
		//Admin can make user to soft ban , so user can't place any edit
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		if ($seo_url != 'null') {

			$uid = $this->uid;

			$Service = Service::with('question_list')->where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();

			if (empty($Service)) {
				return redirect(route('services'));
			}

			if ($request->input()) {
				$input = $request->input();
				$preview = 'false';
				if($request->filled('preview') && $request->preview == 'preview_service') {
					$preview = 'preview_service';
				}
				if($request->filled('preview') && $request->preview == 'preview_requirements') {
					$preview = 'preview_requirements';
				}
				unset($input['preview']);

				$input['basic']['last_updated_by'] = Auth::user()->id;
				unset($input['_token']);

				if ($input['current_step'] < $Service->current_step) {
					unset($input['current_step']);
				}
				if (!isset($input['basic']['que_is_required'])) {
					$input['basic']['que_is_required'] = 0;
				}
				$input['basic']['last_updated_on'] = Carbon::now()->format('Y-m-d H:i:s');
				//Service::where('seo_url', $seo_url)->update($input['basic']);

				/* updating input data in database */
				$Service->questions = $input['basic']['questions'];
				$Service->que_is_required = $input['basic']['que_is_required'];
				$Service->last_updated_by = $input['basic']['last_updated_by'];
				$Service->last_updated_on = $input['basic']['last_updated_on'];
				if(isset($input['current_step'])) {
					$Service->current_step = $input['current_step'];
				}
				$Service->save();

				$available_extras = array();
				if (isset($input['extra']) && count($input['extra'])) {
					foreach ($input['extra'] as $key => $value) {
						if ($value['expacted_answer'] != '') {
							$answers = ($value['expacted_answer']);
						} else {
							$answers = '';
						}
						$checkExistQuestion = ServiceQuestion::select('id')
												->where('service_id',$Service->id)
												->where('question', $value['question_info'])
												->where('answer_type', $value['answer_info'])
												->where('expacted_answer', $answers)
												->where('is_required', $value['is_required_question'])
												->first();
						if($checkExistQuestion){
							/* Add question id in array */ 
							$available_extras[] = $checkExistQuestion->id;
						}else{
							$addQuestionAnswer = new ServiceQuestion;
							$addQuestionAnswer->service_id = $Service->id;
							$addQuestionAnswer->question = $value['question_info'];
							$addQuestionAnswer->answer_type = $value['answer_info'];
							$addQuestionAnswer->expacted_answer = $answers;
							$addQuestionAnswer->is_required = $value['is_required_question'];
							$addQuestionAnswer->save();
							/* Add question id in array */ 
							$available_extras[] = $addQuestionAnswer->id;
						}
					}
				}

				/* Delete Extras */ 
				$OldServiceQuestion = ServiceQuestion::where('service_id', $Service->id)->whereNotIn('id',$available_extras)->get();
				if(count($OldServiceQuestion) > 0){
					/* Delete Extras */ 
					$OldServiceQuestion->each->delete();
				}
				/* END Delete Extras */ 
				

				if($preview == 'preview_service') {
					//Check for review edition
					if($Service->is_review_edition == 1 && $Service->review_edition_count < $Service->no_of_review_editions){
						return response()->json(['status' => 'success','url'=>route('services_details',['username'=>$Service->user->username,'seo_url'=> $Service->seo_url,'review-edition'=>1])]);
					}else{
						return response()->json(['status' => 'success','url'=>route('services_details',['username'=>$Service->user->username,'seo_url'=> $Service->seo_url])]);
					}
				} else if($preview == 'preview_requirements') {
					return response()->json(['status' => 'success','url'=>route('submit_requirement_preview',$Service->seo_url)]);
				}
				return redirect(route('services_gallery', $seo_url));
			} else {
				return view('frontend.service.requirement', compact('Service'));
			}
		}
	}

	public function submit_requirement_preview(Request $request, $seo_url) {
		//Admin can make user to soft ban , so user can't place any edit
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		if ($seo_url != 'null') {
			$uid = $this->uid;

			$service = Service::with('question_list')->where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();

			if (empty($service)) {
				return redirect(route('services'));
			}

			$questions = ServiceQuestion::where('service_id', $service->id)->get();
		
			return view('frontend.service.requirement_submit_preview', compact('service', 'questions'));
		}
		return redirect(route('services'));
	}

	public function gallery(Request $request, $seo_url) {

		//Admin can make user to soft ban , so user can't place any edit
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		if ($seo_url != 'null') {
			$bucket = $request->input('bucket');

			$uid = $this->uid;

			$Service = Service::with('images', 'video', 'pdf','fbimages')->where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();

			if (empty($Service)) {
				return redirect(route('services'));
			}

			if ($request->input()) {

				/* Upload Images */
				$media_type = $request->input('media_type');
				if ($media_type == 'image') {
					$this->validate($request, [
						'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:512',//old-max:2048
						], [
							'image.image' => 'This file type is not supported. Please try one of the following: Jpeg, Png, Jpg, gif.',
							'image.mimes' => 'This file type is not supported. Please try one of the following: Jpeg, Png, Jpg, gif.',
							'image.max' => 'The image may not be greater than 2 mb.',
						]
					);

					$image = $request->file('image');
					$input['media_url'] = time() . rand(). '.' . $image->getClientOriginalExtension();

					$destinationPath = public_path('/services/images');

					$image->move($destinationPath, $input['media_url']);

					$source_url = $destinationPath . '/' . $input['media_url'];

					// thumbnail
					$thumb_name = 'thumb_'.$input['media_url'];
					$thumb_name = str_replace(".gif",".png",$thumb_name);
					$source_url_thumb = $destinationPath.'/'.$thumb_name;
					\Storage::disk('service_images')->copy($input['media_url'], $thumb_name);

					//create thumbnail
					$originalImage = Image::make($source_url);
					$originalImage->resize(env('SERVICE_IMAGE_THUMBNAIL_WIDTH'), env('SERVICE_IMAGE_THUMBNAIL_HEIGHT')/* , function ($constraint) {
						$constraint->aspectRatio();
					} */)->save($source_url_thumb,85);

					try {

						$s3 = AWS::createClient('s3');
						$ext = $image->getClientOriginalExtension();
						$time_md5_key = md5(time(). rand());
						$imageKey = md5($Service->id) . '/' . $time_md5_key . '.' . $ext;

						// thumbnail
						if($image->getClientOriginalExtension() == 'gif') {
							$thumb_imageKey = md5($Service->id) . '/thumb/' . $time_md5_key. '.png';
						} else {
							$thumb_imageKey = md5($Service->id) . '/thumb/' . $time_md5_key. '.' . $ext;
						}

						$result_amazonS3 = $s3->putObject([
							'Bucket' => $bucket,
							'Key' => $imageKey,
							'SourceFile' => $source_url,
							'StorageClass' => 'REDUCED_REDUNDANCY',
							'ACL' => 'public-read',
						]);
						unlink($source_url);

						//upload thumbnail
						$result_amazonS3_thumbnail = $s3->putObject([
							'Bucket' => $bucket,
							'Key' => $thumb_imageKey,
							'SourceFile' => $source_url_thumb,
							'StorageClass' => 'REDUCED_REDUNDANCY',
							'ACL' => 'public-read',
						]);
						unlink($source_url_thumb);
						$input['thumbnail_media_url'] = $result_amazonS3_thumbnail['ObjectURL'];

						$input['media_type'] = $media_type;
						$input['service_id'] = $Service->id;

						if ($request->input('current_step') > $Service->current_step) {
							Service::where('id', $Service->id)->update(['current_step' => $request->input('current_step')]);
						}
						$input['media_url'] = $result_amazonS3['ObjectURL'];
						$input['photo_s3_key'] = $imageKey;
						//ServiceMedia::insert($input);

						/* insert in database */
						$addServiceMedia = new ServiceMedia;
						$addServiceMedia->media_url = $input['media_url'];
						$addServiceMedia->thumbnail_media_url = $input['thumbnail_media_url'];
						$addServiceMedia->media_type = $input['media_type'];
						$addServiceMedia->service_id = $input['service_id'];
						$addServiceMedia->photo_s3_key = $input['photo_s3_key'];
						$addServiceMedia->save();

					} catch (Aws\S3\Exception\S3Exception $e) {
						echo "There was an error uploading the file.\n";
					}
				} else if ($media_type == 'video') {

					$video = $request->file('video');
					$mime = $video->getMimeType();

					if ($mime == 'video/mp4') {
						$input['media_url'] = time() . '.' . $video->getClientOriginalExtension();

						$destinationPath = public_path('/services/video');
						$video->move($destinationPath, $input['media_url']);
						$source_url = $destinationPath . '/' . $input['media_url'];

						//create thumbnail
						$thumb_name = 'thumb_'.time().'.png';
						\Thumbnail::getThumbnail($source_url,$destinationPath,$thumb_name,env('TIME_TO_TAKE_SCREENSHOT'));
						$source_url_thumb = $destinationPath.'/'.$thumb_name;

						try {
							$s3 = AWS::createClient('s3');
							$ext = $video->getClientOriginalExtension();
							$time_md5_key = md5(time(). rand());
							$imageKey = md5($Service->id) . '/' . $time_md5_key . '.' . $ext;
							$thumb_imageKey = md5($Service->id) . '/thumb/' . $time_md5_key. '.png';

							$result_amazonS3 = $s3->putObject([
								'Bucket' => $bucket,
								'Key' => $imageKey,
								'SourceFile' => $destinationPath . '/' . $input['media_url'],
								'StorageClass' => 'REDUCED_REDUNDANCY',
								'ACL' => 'public-read',
							]);

							unlink($destinationPath . '/' . $input['media_url']);
							$input['media_type'] = $media_type;
							$input['service_id'] = $Service->id;

							//upload thumbnail
							$result_amazonS3_thumbnail = $s3->putObject([
								'Bucket' => $bucket,
								'Key' => $thumb_imageKey,
								'SourceFile' => $source_url_thumb,
								'StorageClass' => 'REDUCED_REDUNDANCY',
								'ACL' => 'public-read',
							]);
							unlink($source_url_thumb);
							$input['thumbnail_media_url'] = $result_amazonS3_thumbnail['ObjectURL'];

							if ($request->input('current_step') > $Service->current_step) {
								Service::where('id', $Service->id)->update(['current_step' => $request->input('current_step')]);
							}

							$input['media_url'] = $result_amazonS3['ObjectURL'];
							$input['photo_s3_key'] = $imageKey;
							//ServiceMedia::insert($input);

							/* insert in database */
							$addServiceMedia = new ServiceMedia;
							$addServiceMedia->media_url = $input['media_url'];
							$addServiceMedia->media_type = $input['media_type'];
							$addServiceMedia->service_id = $input['service_id'];
							$addServiceMedia->photo_s3_key = $input['photo_s3_key'];
							$addServiceMedia->thumbnail_media_url = $input['thumbnail_media_url'];
							$addServiceMedia->save();
						} catch (Aws\S3\Exception\S3Exception $e) {
							echo "There was an error uploading the file.\n";
						}
					} else {

						Session::flash('errorFails', 'This file type is not supported. Please try one of the following: mp4');
					}
				} else if ($media_type == 'pdf') {

					$pdf = $request->file('pdf');
					$mime = $pdf->getMimeType();

					if ($mime == 'application/pdf') {
						$input['media_url'] = time() . '.' . $pdf->getClientOriginalExtension();

						$destinationPath = public_path('/services/pdf');

						$pdf->move($destinationPath, $input['media_url']);
						try {
							$s3 = AWS::createClient('s3');
							$ext = $pdf->getClientOriginalExtension();
							$imageKey = md5($Service->id) . '/' . md5(time()) . '.' . $ext;
							$result_amazonS3 = $s3->putObject([
								'Bucket' => $bucket,
								'Key' => $imageKey,
								'SourceFile' => $destinationPath . '/' . $input['media_url'],
								'StorageClass' => 'REDUCED_REDUNDANCY',
								'ACL' => 'public-read',
							]);
							
							unlink($destinationPath . '/' . $input['media_url']);
							$input['media_type'] = $media_type;
							$input['service_id'] = $Service->id;

							if ($request->input('current_step') > $Service->current_step) {
								Service::where('id', $Service->id)->update(['current_step' => $request->input('current_step')]);
							}

							$input['media_url'] = $result_amazonS3['ObjectURL'];
							$input['photo_s3_key'] = $imageKey;
							//ServiceMedia::insert($input);

							/* insert in database */
							$addServiceMedia = new ServiceMedia;
							$addServiceMedia->media_url = $input['media_url'];
							$addServiceMedia->media_type = $input['media_type'];
							$addServiceMedia->service_id = $input['service_id'];
							$addServiceMedia->photo_s3_key = $input['photo_s3_key'];
							$addServiceMedia->save();
						} catch (Aws\S3\Exception\S3Exception $e) {
							echo "There was an error uploading the file.\n";
						}

						
					} else {

						Session::flash('errorFails', 'This file type is not supported. Please try one of the following: pdf');
					}
				} else if ($media_type == 'fb_image') {
					$this->validate($request, [
						'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048|dimensions:min_width=1100,min_height=528,max_width=1200,max_height=628',
						], [
							'image.image' => 'This file type is not supported. Please try one of the following: Jpeg, Png, Jpg, gif.',
							'image.mimes' => 'This file type is not supported. Please try one of the following: Jpeg, Png, Jpg, gif.',
							'image.max' => 'The image may not be greater than 2 mb.',
						]
					);

					$image = $request->file('fb_image');

					$input['media_url'] = time() . '.' . $image->getClientOriginalExtension();

					$destinationPath = public_path('/services/images');

					$image->move($destinationPath, $input['media_url']);

					$ServiceFbMedia = ServiceMedia::where('media_type','fb_image')->where('service_id',$Service->id)->first();
					if (count($ServiceFbMedia) > 0) {
						$this->remove_media($ServiceFbMedia->id);
					}

					try {
						$s3 = AWS::createClient('s3');
						$ext = $image->getClientOriginalExtension();
						$imageKey = md5($Service->id) . '/' . md5(time()) . '.' . $ext;
						$result_amazonS3 = $s3->putObject([
							'Bucket' => $bucket,
							'Key' => $imageKey,
							'SourceFile' => $destinationPath . '/' . $input['media_url'],
							'StorageClass' => 'REDUCED_REDUNDANCY',
							'ACL' => 'public-read',
						]);

						unlink($destinationPath . '/' . $input['media_url']);
						$input['media_type'] = $media_type;
						$input['service_id'] = $Service->id;

						if ($request->input('current_step') > $Service->current_step) {
							Service::where('id', $Service->id)->update(['current_step' => $request->input('current_step')]);
						}
						$input['media_url'] = $result_amazonS3['ObjectURL'];
						$input['photo_s3_key'] = $imageKey;
						//ServiceMedia::insert($input);

						/* insert in database */
						$addServiceMedia = new ServiceMedia;
						$addServiceMedia->media_url = $input['media_url'];
						$addServiceMedia->media_type = $input['media_type'];
						$addServiceMedia->service_id = $input['service_id'];
						$addServiceMedia->photo_s3_key = $input['photo_s3_key'];
						$addServiceMedia->save();
					} catch (Aws\S3\Exception\S3Exception $e) {
						echo "There was an error uploading the file.\n";
					}
				}
				$Service->last_updated_by = Auth::user()->id;
				$Service->last_updated_on = Carbon::now()->format('Y-m-d H:i:s');
				$Service->save();
				
				return redirect()->back();
			}
			return view('frontend.service.gallery', compact('Service'));
		}
	}

	public function galleryReorder(Request $request){
		
		if($request->filled('ids')){
			$ids = $request->ids;
			foreach ($ids as $key => $value) {
				# code...
				$serviceMedia = ServiceMedia::where('id',$value)->first();
				if($serviceMedia != null){
					$serviceMedia->order_id = $key +1 ;
					$serviceMedia->save();
				}
			}
			
			return response()->json([
				'success' => true
			]);
		}else{
            return response()->json(['success'=>false]); 
        }
		
		
	}
	public function publish(Request $request, $seo_url) {

		//Admin can make user to soft ban , so user can't place any edit
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		if ($seo_url != 'null') {
			$uid = $this->uid;

			$Service = Service::where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();

			if (empty($Service)) {
				return redirect(route('services'));
			}


			/*if($Service->current_step != 5){
				$seller = User::find($uid);
				$ontime_delivery = $seller->getOntimedelivery($seller->id);
	            if($ontime_delivery->ontime_delivery_per <= env('ONTIME_DELIVERY_PER')){
	            	Session::flash('errorFails', 'You have more active late orders. Complete pending orders to active and receive more orders');
	            	$input['status'] = 'paused';
	            }else{
	            	$input['status'] = 'active';
	            }
			}else{
				$input['status'] = 'active';
			}*/
			$Service->status = "active";
			$Service->save();

			$input['current_step'] = 5;

			if($Service->is_approved == 1 && $Service->status == 'denied'){
				$input['is_approved'] = '0';
			}

			if($Service->status == 'denied')
			{
				$input['reuse_denied_status'] = 1;
			}

			$input['last_updated_by'] = Auth::user()->id;
			$input['last_updated_on'] = Carbon::now()->format('Y-m-d H:i:s');
			Service::where('seo_url', $seo_url)->update($input);
			if(Str::contains($request->fullUrl(), '?page=service_details')) {
				return redirect(url()->previous());
			}

			return redirect(route('services'));
		}
	}

	public function remove_media($id) {

		$uid = $this->uid;

		$ServiceMedia = ServiceMedia::where('id', $id)->first();
		$service_id = $ServiceMedia->service_id;
		$Service = Service::withoutGlobalScope('is_course')->where(['uid' => $uid, 'id' => $service_id])->first();
		$bucket = env('bucket_service');

		if ($ServiceMedia) {
			if ($ServiceMedia->media_type == 'image') {
				$destinationPath = public_path('/services/images/');
				if (file_exists($destinationPath . $ServiceMedia->media_url) && $ServiceMedia->photo_s3_key == '') {
					unlink($destinationPath . $ServiceMedia->media_url);
				} else {
					$keyData = $ServiceMedia->photo_s3_key;
					$s3 = AWS::createClient('s3');

					try {
						$result_amazonS3 = $s3->deleteObject([
							'Bucket' => $bucket,
							'Key' => $keyData,
						]);
					} catch (Aws\S3\Exception\S3Exception $e) {
						$result_amazonS3['ObjectURL'] = '';
						echo "There was an error uploading the file.\n";
					}

					//delete thumbnail
					if($ServiceMedia->thumbnail_media_url != null) {
						$thumb_imageKey_ary = explode('/',$ServiceMedia->photo_s3_key);
						$keyDataThumb = $thumb_imageKey_ary[0] .'/thumb/'.str_replace(".gif",".png",$thumb_imageKey_ary[1]);
						try {
							$result_amazonS3 = $s3->deleteObject([
								'Bucket' => $bucket,
								'Key' => $keyDataThumb,
							]);
						} catch (Aws\S3\Exception\S3Exception $e) {
							//error
						}
					}
				}

				Session::flash('errorSuccess', 'Photo removed successfully.');
			} else if ($ServiceMedia->media_type == 'video') {
				$destinationPath = public_path('/services/video/');
				if (file_exists($destinationPath . $ServiceMedia->media_url) && $ServiceMedia->photo_s3_key == '') {
					unlink($destinationPath . $ServiceMedia->media_url);
				} else {
					$keyData = $ServiceMedia->photo_s3_key;
					$s3 = AWS::createClient('s3');

					try {
						$result_amazonS3 = $s3->deleteObject([
							'Bucket' => $bucket,
							'Key' => $keyData,
						]);
					} catch (Aws\S3\Exception\S3Exception $e) {
						$result_amazonS3['ObjectURL'] = '';
						echo "There was an error uploading the file.\n";
					}

					//delete thumbnail
					if($ServiceMedia->thumbnail_media_url != null) {
						$thumb_imageKey_ary = explode('/',$ServiceMedia->photo_s3_key);
						$thumbKey = explode('.',$thumb_imageKey_ary[1]);
						$keyDataThumb = $thumb_imageKey_ary[0] .'/thumb/'.$thumbKey[0].'.png';

						try {
							$result_amazonS3 = $s3->deleteObject([
								'Bucket' => $bucket,
								'Key' => $keyDataThumb,
							]);
					} catch (Aws\S3\Exception\S3Exception $e) {
							//error
						}
					}
				}
				Session::flash('errorSuccess', 'Video removed successfully.');
			} else if ($ServiceMedia->media_type == 'pdf') {
				$destinationPath = public_path('/services/pdf/');
				if (file_exists($destinationPath . $ServiceMedia->media_url) && $ServiceMedia->photo_s3_key == '') {
					unlink($destinationPath . $ServiceMedia->media_url);
				} else {
					$keyData = $ServiceMedia->photo_s3_key;
					$s3 = AWS::createClient('s3');

					try {
						$result_amazonS3 = $s3->deleteObject([
							'Bucket' => $bucket,
							'Key' => $keyData,
						]);
					} catch (Aws\S3\Exception\S3Exception $e) {
						$result_amazonS3['ObjectURL'] = '';
						echo "There was an error uploading the file.\n";
					}
				}
				Session::flash('errorSuccess', 'Pdf removed successfully.');
			} else if ($ServiceMedia->media_type == 'fb_image') {
				$destinationPath = public_path('/services/images/');
				if (file_exists($destinationPath . $ServiceMedia->media_url) && $ServiceMedia->photo_s3_key == '') {
					unlink($destinationPath . $ServiceMedia->media_url);
				} else {
					$keyData = $ServiceMedia->photo_s3_key;
					$s3 = AWS::createClient('s3');

					try {
						$result_amazonS3 = $s3->deleteObject([
							'Bucket' => $bucket,
							'Key' => $keyData,
						]);
					} catch (Aws\S3\Exception\S3Exception $e) {
						$result_amazonS3['ObjectURL'] = '';
						echo "There was an error uploading the file.\n";
					}
				}
				Session::flash('errorSuccess', 'Photo removed successfully.');
			}
			$Service->last_updated_by = Auth::user()->id;
			$Service->last_updated_on = Carbon::now()->format('Y-m-d H:i:s');
			$Service->save();
			//ServiceMedia::where('id', $id)->delete();

			/* delete from database */
			$ServiceMedia->delete();
		}
		return redirect()->back();
	}

	public function remove_service($id) {
		$uid = $this->uid;

		$bucket = env('bucket_service');
		$Service = Service::where(['uid' => $uid, 'id' => $id])->first();
		if (empty($Service)) {
			return redirect(route('services'));
		}

		if ($Service) {
			$orders = Order::where(['service_id' => $id])->first();
			if (count($orders) > 0) {
				Session::flash('errorFails', 'This service is relation with other orders.');
			} else {
				$ServiceMedia = ServiceMedia::where('service_id', $id)->get();
				if ($ServiceMedia) {
					foreach ($ServiceMedia as $row) {

						if ($row->media_type == 'image') {
							$destinationPath = public_path('/services/images/');
						} else if ($row->media_type == 'video') {
							$destinationPath = public_path('/services/video/');
						} else if ($row->media_type == 'pdf') {
							$destinationPath = public_path('/services/pdf/');
						}

						if (file_exists($destinationPath . $row->media_url) && $row->photo_s3_key == '') {
							unlink($destinationPath . $row->media_url);
						} else {
							$keyData = $row->photo_s3_key;
							$s3 = AWS::createClient('s3');

							try {
								$result_amazonS3 = $s3->deleteObject([
									'Bucket' => $bucket,
									'Key' => $keyData,
								]);
							} catch (Aws\S3\Exception\S3Exception $e) {

							}

							//delete thumbnail
							if($row->thumbnail_media_url != null) {
								$thumb_imageKey_ary = explode('/',$row->photo_s3_key);
								$keyDataThumb = $thumb_imageKey_ary[0] .'/thumb/'.str_replace(".gif",".png",$thumb_imageKey_ary[1]);
								try {
									$result_amazonS3 = $s3->deleteObject([
										'Bucket' => $bucket,
										'Key' => $keyDataThumb,
									]);
								} catch (Aws\S3\Exception\S3Exception $e) {
									//error
								}
							}
						}
					}
					ServiceMedia::where('service_id', $id)->delete();
					ServiceQuestion::where('service_id', $id)->delete();
				}
				Service::where('id', $id)->delete();
				Session::flash('errorSuccess', 'Service removed successfully.');
			}
		} else {
			Session::flash('errorFails', 'Something goes wrong.');
		}
		return redirect()->back();
	}

	public function change_status(Request $request) {
		$input = $request->input();

		$uid = $this->uid;

		if (isset($input['status']) && isset($input['id'])) {

			Service::withoutGlobalScope('is_course')
			->where('id', $input['id'])
			->where(['uid' => $uid])
			->update(['status' => $input['status'],'last_updated_by' => Auth::user()->id]);

			Session::flash('errorSuccess', 'Status changed successfully.');
			$status = 200;
		} else {
			Session::flash('errorFails', 'Something goes wrong.');
			$status = 401;
		}
		if ($request->ajax()) {
			return $status;
		}
		return redirect()->back();
	}

	public function get_subcategory(Request $request) {
		$category_id = $request->input('category_id');
		$findCategory = Category::withoutGlobalScope('type')->select('seo_url')->find($category_id);

		$Subcategory = null;
		$category_slug = null;
		if(!empty($findCategory)){
			$Subcategory = Subcategory::select('subcategory_name', 'id')->where('category_id', $category_id)->where('status',1)->get()->toArray();
			$category_slug = $findCategory->seo_url;
		}
		echo json_encode(['subcategory'=>$Subcategory,'category_slug'=>$category_slug]);
	}

    // Added by ramesh chudasama
	public function viewUserServices(Request $request, $username) {
		$user = User::where('username', $username)->where('status',1)->where('is_delete',0);

		if(!Auth::check()){
			$user = $user->where('soft_ban', 0);
		}else{
			if(Auth::user()->username != $username){
				$user = $user->where('soft_ban', 0);
			}
		}

		$user = $user->first();
		
		if (empty($user)) {
			//return redirect('404');
			/* code for landing page - start */
			$landing_page_url = $username;
			$exist = LandingPage::select('id')->where('page_url',$landing_page_url)->count();
			if($exist == 0) {
				return redirect('404');
			}
			$token = $request->admin ?? '';
			$master_admin = Admin::where('email','info@demo.com')->select('email','password')->first();
			$encodedParam = json_encode($master_admin);
			$access_token = base64_encode($encodedParam);
			$access_token = str_replace("/","", $access_token);

			$page = LandingPage::where('page_url',$landing_page_url);
			if($token != '' && $access_token == $token) {
				$page = $page->with('landing_page_sections.landing_page_section_services');
			} else {
				$page = $page->where('status',1)
							->with(['landing_page_sections'=> function($q) {
								$q->where('status',1);
							}]);
			}

			$page = $page->first();
			if(is_null($page)) {
				return redirect('404');
			}
			return view('landing_page',compact('page'));
			/* code for landing page - end */
		}

		/*Check Block Users*/ 
		$block_users = User::getBlockedByIds();
		if(in_array($user->id,$block_users)){
			abort(401);
		}
		/*End Check Block Users*/ 
		
		if($user->parent_id != 0) {
			$user = User::where('id', $user->parent_id)->where('status',1)->where('is_delete',0)->first();
		}
		if (empty($user)) {
			return redirect('404');
		}
		$id = $user->id;
		$Service = Service::select('services.*')->with('user', 'category', 'images', 'basic_plans')
		->where('services.status', 'active')
		->where('services.is_approved', 1)
		->where('is_private', 0)
		->where('is_job', 0)
		->where('is_custom_order', 0)
		->where('services.uid', $id)
		->where('services.is_delete',0)
		->join('category', 'category.id', '=', 'services.category_id')
		->distinct()
		->orderBy('sort_by', 'asc');

		$Service = $Service->whereHas('user', function($query) {
			$query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
		});

		$Service = $Service->paginate(21);

		if ($request->ajax()) {
			return view('frontend.service.filterservices', compact('Service'))->render();
		}	

		$seller_profile = '';
		$allOrders = Order::select('id')->where('seller_uid', $user->id)->whereNotIn('id',['52213'])->count();
		if ($allOrders) {

			$totalOrders = Order::select('id')->where('seller_uid', $user->id)->whereIn('orders.status', ['delivered', 'completed'])->count();

			$cdate = date('Y-m-d H:i:s');

			$allDelivered = Order::select('id')->where('seller_uid', $user->id)->whereIn('orders.status', ['delivered', 'completed'])->whereRaw('delivered_date < end_date')
			//->where('isUpdatedQuestionAnswer',1)
			->count();

			$dileveredLate = Order::select('id')->whereIn('orders.status', ['cancelled','delivered', 'completed'])
			->whereRaw("seller_uid = '{$user->id}' AND ((delivered_date is null and end_date < cancel_date) OR (delivered_date is not null and delivered_date > end_date))")->whereNotIn('id',['52213'])
			//->where('isUpdatedQuestionAnswer',1)
			->where('cancel_note','!=','Cancelled Order By Admin')
			->count();

			$cancelAfterDelay = Order::select('id')->whereIn('orders.status', ['cancelled'])
			->whereRaw("seller_uid = '{$user->id}' AND (delivered_date is null and end_date < cancel_date)")->whereNotIn('id',['52213'])
			//->where('isUpdatedQuestionAnswer',1)
			->where('cancel_note','!=','Cancelled Order By Admin')
			->count();

			//echo $dileveredLate;die;


			//$CancelBeforeLateCount = Order::whereIn('orders.status', ['cancelled'])->whereRaw("seller_uid = '{$user->id}' AND (delivered_date is null and end_date < cancel_date)")->whereNotIn('id',['52213'])->count();

			//$allDelivered = $allDelivered + $dileveredLate; 

			if ($allDelivered) {
				/*$totalDelivered = Order::where('seller_uid', $user->id)->whereIn('orders.status', ['delivered', 'completed'])->whereRaw('delivered_date < end_date')->count();*/

				$totalDeliveredPer = ($allDelivered * 100) / ($allDelivered + $dileveredLate);
			} else {
				$totalDeliveredPer = 0;
			}

			/*$allDelivered = Order::where('seller_uid', $user->id)->whereIn('orders.status', ['delivered', 'completed','cancelled'])
			->count();*/


			if ($allDelivered) {
				$dileveredLatePer = ($dileveredLate * 100) / ($allDelivered + $dileveredLate);
			} else {
				$dileveredLatePer = 0;
			}

		/*	$caceledAfterLate = Order::where('orders.status', 'cancelled')
			->where('seller_uid', $user->id)
			->whereRaw('end_date < cancel_date')
			->whereNotIn('id',['52213'])
			->where('isUpdatedQuestionAnswer',1)
			->count();*/
			// $caceledAfterLatePer = ($caceledAfterLate * 100) / $allOrders;

			if ($cancelAfterDelay) {
				$caceledAfterLatePer = ($cancelAfterDelay * 100) / ($allDelivered + $dileveredLate);
			} else {
				$caceledAfterLatePer = 0;
			}
		} else {
			$totalOrders = 0;
			/*$totalCustomOrders = 0;*/
			$totalDeliveredPer = 0;
			$dileveredLatePer = 0;
			$caceledAfterLatePer = 0;
		}

		$uid = $this->uid;

		$save_template = SaveTemplate::where('seller_uid',$id)
		->where('template_for',1)
		->orderBy('title', 'asc')
		->pluck('title', 'id')
		->toArray();

		$save_template_chat = SaveTemplate::where('seller_uid',$uid)
		->where('template_for',2)
		->orderBy('title', 'asc')
		->pluck('title', 'id')
		->toArray();

		$ServiceCustOrder = Service::select('id','title','service_rating')->where(['uid'=> $id,'status' => 'custom_order','parent_id'=>'0'])->get();

		$dataCustom=[];
		if(count($ServiceCustOrder) > 0) {
			foreach ($ServiceCustOrder as $key) {
				$newData=Order::select('id','uid','completed_note')->where('service_id',$key->id)
				//->whereRaw('(completed_note is not null OR seller_rating > 0)')
				->where('seller_rating', '>', 0)
				->where('status','completed','cancelled')
				->first();
				if($newData) {
					$dataCustom[]=['service_id' => $key->id,'order_name' => $key->title,'review' => $newData->completed_note,'user' => $newData->user->username,'rating' => $key->service_rating];
				}
			}
		}

		$totalNumberCustomOrders= $ServiceCustOrder->count();
		$customUser=$id;
		$countReviewCustom=count($dataCustom);

		/*check if guest user logins and wants to send message after login*/
		$dyanmicmsg=Session::get('sendmsg');
		if($dyanmicmsg != 0)
		{
			if($id != $uid)
			{
				Session::forget('sendmsg'); 
				$showMsg=1;
			}
			else
			{
				Session::forget('sendmsg'); 
				$showMsg=0;
			}
		}
		else 
		{
			$showMsg=0;
		}

		/*check if guest user logins and wants to send custom order request after login*/
		$dyanmicCustom=Session::get('customOrder');
		if($dyanmicCustom != 0)
		{
			if($id != $uid)
			{
				Session::forget('customOrder'); 
				$showCustomBox=1;
			}
			else
			{
				Session::forget('customOrder'); 
				$showCustomBox=0;
			}
		}
		else 
		{
			$showCustomBox=0;
		}
		
		//Seller service review 
		$services_review = Order::with('user:id,Name,profile_photo,photo_s3_key')->select('id','seller_uid','uid','completed_note','seller_rating')
		->where('seller_uid',$user->id)
		->where('status','completed')
		->where("is_review", 1)
		->where('seller_rating', '>=', 4)
		->whereRaw('LENGTH(completed_note) >= 40')
		->inRandomOrder()
		->limit(3)
		->get();

		$getJobOrderCount=Order::where('seller_uid',$id)->where('is_job',1)
		//->whereRaw('(completed_note is not null OR seller_rating > 0)')
		->where('seller_rating', '>', 0)
		->where('status','completed','cancelled')
		->get();

		$dataCustomJob=[];
		if(!$getJobOrderCount->isempty()) {
			foreach ($getJobOrderCount as $key) {
				$dataCustomJob[]=['service_id' => $key->id,'order_name' => $key->title,'review' => $key->completed_note,'user' => $key->user->username,'rating' => $key->service_rating];
			}
		}
		$totalNumberJobOrder= $getJobOrderCount->count();
		$countReviewJob=count($dataCustomJob);
		$jobUser=$id;

		$ratingModel = new Order;
    	$avg_seller_rating = $ratingModel->calculateSellerAverageRating($user->id);
    	$total_seller_rating = $ratingModel->getReviewTotal($user->id);

    	$portfolio = array();
    	if(count($Service)>0){
    		$portfolio = Portfolio::select('id','media_link','media_type','media_mime','title','thumbnail_url')
    		->where('user_id',$user->id)
    		->where('is_delete',0)
    		->orderBy('sort_by','asc')
    		->limit(6)
    		->get();
    	}

		/* Get Followers count */
		$total_followers = UserFollow::select('user_follows.id')
			->where('user_follows.user_id',$user->id)
			->where('user_follows.status',1)
			->where('users.status',1)
			->where('users.soft_ban',0)
			->where('users.is_delete',0)
			->join('users', 'user_follows.follower_id', '=', 'users.id');
			
			/* Check Block users*/
			if($block_users){
				$total_followers = $total_followers->whereNotIn('users.id',$block_users);
			}
			/* END Check Block users*/

			$total_followers = $total_followers->count();
			/* END Get Followers count */

		/* Get Followers count */
		$total_following = UserFollow::select('user_follows.id')
			->where('user_follows.follower_id',$user->id)
			->where('user_follows.status',1)
			->where('users.status',1)
			->where('users.soft_ban',0)
			->where('users.is_delete',0)
			->join('users', 'user_follows.user_id', '=', 'users.id');
			/* Check Block users*/
			if($block_users){
				$total_following = $total_following->whereNotIn('users.id',$block_users);
			}
			/* END Check Block users*/
			$total_following = $total_following->count();
			/* END Get Followers count */

		/* Start Direct Follow link */
		if(isset($request->follow_confirmation) && $request->follow_confirmation == 1){
			if(!Auth::check())
			{
				return redirect(route('login'));
			}
		}
		$direct_follow_response = $this->followUser($request,$username);
		/* End Direct Follow link */	

		return view('frontend.service.viewuserservices', compact('Service', 'user', 'seller_profile', 'totalOrders', 'dileveredLatePer', 'caceledAfterLatePer', 'totalDeliveredPer','customUser','totalNumberCustomOrders','ServiceCustOrder','dataCustom','countReviewCustom','totalCustomOrders','save_template','save_template_chat','showCustomBox','showMsg','getJobOrderCount','totalNumberJobOrder','countReviewJob','jobUser','total_seller_rating','avg_seller_rating','services_review','portfolio','total_followers','total_following', 'direct_follow_response'));
	}



	public function getCustomOrderDetailPage(request $request)
	{
		$id = User::getDecryptedId($request->id);
		$user = User::where('id', $id)->where('status',1)->where('is_delete',0)->first();
		if (empty($user)) {
			return redirect('404');
		}

		/*$Service = Service::select('services.*')->with('user', 'category', 'images', 'basic_plans','order')
		->where('services.status', 'custom_order')                
		->where('services.uid', $id)
		->where('parent_id',"0")
		->where('services.is_delete',0)
		->orderBy('id', 'desc')->get();*/
		$Service = null;

		$dataCustomCount= Order::select('id')->where('seller_uid',$id)
		->where('is_custom_order',1)
		->where('status','completed','cancelled')
		//->whereRaw('(completed_note is not null OR seller_rating > 0)')
		->where('seller_rating', '>', 0)
		->count();

		$dataCustom= Order::where('seller_uid',$id)
		->where('is_custom_order',1)
		->where('status','completed','cancelled')
		//->whereRaw('(completed_note is not null OR seller_rating > 0)')
		->where('seller_rating', '>', 0)
		->orderby('id','desc')->paginate(10);
	    return view('frontend.service.viewuserCustomservices',compact('Service','user','dataCustom','id','dataCustomCount'))->render();
	}

	/* Old Route */
	public function sellerProfileDetails(Request $request, $id) {
		$user = User::where('id', $id)->first();
		return redirect()->route('viewuserservices', $user->username);
	}

	public function getSubCategories(Request $request) {
		$html = '';
		if (isset($request->id)) {
			$subcategories = Subcategory::select('id','subcategory_name')->where('status',1)->where('category_id', $request->id)->get();
			if (count($subcategories)) {
				foreach ($subcategories as $key => $value) {
					$html .= '<li class="dropdown-item" id="' . $value->id . '">
					<a href="javascript:void(0)" id="' . $value->id . '" class="subcategory-name">' . $value->subcategory_name . '</a>
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

	public function replayComment(Request $request) {

		$order = Order::find($request->id);
		$order->completed_reply = $request->completed_reply;
		$order->save();

		$user = $request->username;
		$sender = $request->sender;
		$messageDetails = $request->completed_reply;
		$useremail = $request->userEmail;
		$completed_note = $request->completed_note;
		$data = [
			'username' => $user,
			'sender' => $sender,
			'messageDetails' => $messageDetails,
			'completed_note' => $completed_note,
		];
		try {
            \Mail::send('frontend.emails.v1.replay_message', $data, function($message) use ($useremail, $sender) {
				$message->to($useremail)
				->subject('Reply message from ' . $sender . ' on demo.com');
			});
        } catch (\Exception $e) {
            \Log::channel('emaillog')->info('Mail sending to invalid email: '.$useremail);
        }

		return response()->json([
			'status' => 200,
			'message' => "Comment reply successfully.",
			'value' => $request->completed_reply
		]);
	}
	public function replaySellerComment(Request $request) {
		$order = Order::find($request->id);
		$order->completed_reply = $request->completed_reply;
		$order->save();

		$order_obj = new Order;
		$order_obj->updateHeader();
		return redirect(route('seller_orders_details', $order->order_no));
	}
	public function DeleteSellerComment(Request $request, $id) {
		$order = Order::find($id);
		$order->completed_reply = null;
		$order->save();

		$order_obj = new Order;
		$order_obj->updateHeader();

		return redirect(route('seller_orders_details', $order->order_no));
	}

	/*view for package selection for sponser service*/
	public function boostTheService(Request $request, $seo_url) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		$service = Service::where('seo_url',$seo_url)->first();
		if(is_null($service) || (!is_null($service) && $service->is_delete != 0)) {
			return redirect('404');
		}

		if($service->category->seo_url == 'by-us-for-us'){
			$planInfo = BoostingPlan::whereNotIn('id',['1','3','4','5'])->get();
		}else{
			$planInfo = BoostingPlan::whereNotIn('id',['1','3','7'])->get();
		}
		
		return view('frontend.service.boostservice.boost', compact('planInfo', 'seo_url'));
	}

	/*payment for sponsered service*/
	public function boostPayment(Request $request) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}
		
		if($request->total_days == null)
		{
			return Redirect::back()->withErrors(['Please Select Number of Days']);
		}
		$service_seo_url = $request->service_seo_url;

		$selectedPlan = BoostingPlan::where('id', '=', $request->selected_pack)->first();
		$serviceData = Service::where('seo_url', '=', $service_seo_url)->first();
		$service_id = $serviceData->id;
		$request->merge([
			'service_id' => $service_id,
		]);

		$dates_array = [];
		$total_days = $request->total_days;
		if ($request->selected_pack == 4 || $request->selected_pack == 5) {
			$dates_array = BoostedServicesOrder::get_category_sponser_dates($service_id,$request->category_slot,$total_days,$request->selected_pack);
			$yourStartDate == $yourEndDate =  null;
		}elseif($request->selected_pack == 7){
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
		}else {
			/*For 2 - home page and 6 - new trading*/
			$getServiceCategory = Service::where('id', '=', $service_id)->first();

			if (!empty($getServiceCategory)) {
				$subCatId = $getServiceCategory->subcategory_id;

				$getServicesOfSameCategory = Service::select('id')->where('subcategory_id', '=', $subCatId)->get()->toArray();

				$getServicesOfSameCategory = Arr::flatten($getServicesOfSameCategory);

				if (count($getServicesOfSameCategory)) {
					$serviceTurn = BoostedServicesOrder::where('plan_id', '=', $request->selected_pack)
					->where('status','!=','cancel')
					->orderby('id', 'desc')->first();

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

		if (!in_array($request->selected_pack, [4,5])) {
			$now = date('Y-m-d 00:00:00');
			$dateDifference = date_diff(date_create($now), date_create($yourStartDate));
			if ($dateDifference->days > 0 && $dateDifference->invert == 1) {
				$yourStartDate = date('Y-m-d 00:00:00', strtotime("+1" . " days"));
				$yourEndDate = date('Y-m-d 23:59:59', strtotime($yourStartDate . "+" . ($total_days - 1) . " days"));
			}
		}

		$subtotal = $selectedPlan->price;
		$final_total = $total_days * $selectedPlan->price;
		if ($request->selected_pack == 4 || $request->selected_pack == 5) {
			if($request->category_slot == 2){
				$final_total = $total_days * $selectedPlan->sub_price;
				$subtotal = $selectedPlan->sub_price;
			}
		}

		$category_slot = $request->category_slot;
		
		if(Auth::user()->earning == 0){
			$fromWalletAmount = 0;
		}elseif(Auth::user()->earning >= $final_total){
			$fromWalletAmount = $final_total;
		}else{
			$fromWalletAmount = Auth::user()->earning;
		}

		if(Auth::user()->promotional_fund == 0){
			$fromPromotionalAmount = 0;
		}elseif(Auth::user()->promotional_fund >= $final_total){
			$fromPromotionalAmount = $final_total;
		}else{
			$fromPromotionalAmount = Auth::user()->promotional_fund;
		}

		Session::put('sponser_request_data',$request->all());
		Session::forget('rentAdSpotPlanId'); //destroy session for plan id for rent spot
		/* return view('frontend.service.boostservice.payment_new', compact('selectedPlan', 'yourStartDate', 'yourEndDate', 'serviceData', 'total_days','fromWalletAmount','final_total','dates_array','subtotal','fromPromotionalAmount')); */
		return view('frontend.service.boostservice.boost_cart', compact('selectedPlan', 'yourStartDate', 'yourEndDate', 'serviceData', 'total_days','fromWalletAmount','final_total','dates_array','subtotal','fromPromotionalAmount'));
	}

	public function boost_cart_payment_options(Request $request) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}
		
		$validator = Validator::make($request->all(), array(
            //'coupon_id' => 'required',
            'service_id' => 'required',
			'sub_total' => 'required',
			'final_total' => 'required',
			'total_days' => 'required',
        ));

        if ($validator->fails()) {
			\Session::flash('errorFails', "Something went wrong");
            return redirect()->back();
        }
		$coupon_id = $request->coupon_id ?? '0';
		$service_id = $request->service_id;
		$sub_total = $request->sub_total;
		$final_total = $request->final_total;
		$total_days = $request->total_days;

		$is_recurring_service = 0;
		$service = Service::select('id','is_recurring','title')->find($service_id);
		if($service->is_recurring == 1) {
			$is_recurring_service = 1;
		}
		if($is_recurring_service == 0){
			if(Auth::user()->earning == 0){
				$fromWalletAmount = 0;
			}elseif(Auth::user()->earning >= $final_total){
				$fromWalletAmount = $final_total;
			}else{
				$fromWalletAmount = Auth::user()->earning;
			}
			if(Auth::user()->promotional_fund == 0){
				$fromPromotionalAmount = 0;
			}elseif(Auth::user()->promotional_fund >= $final_total){
				$fromPromotionalAmount = $final_total;
			}else{
				$fromPromotionalAmount = Auth::user()->promotional_fund;
			}
		}else{
			$fromWalletAmount = 0;
			$fromPromotionalAmount = 0;
		}
		$settings = Setting::find(1);

		return view('frontend.service.boostservice.boost_cart_payment_options', compact('coupon_id','is_recurring_service','sub_total','final_total','fromWalletAmount','fromPromotionalAmount','settings','service','total_days'));
	}
		
	public function apply_coupon_on_sponsor_service(Request $request) {

		$sponser_request_data = (object) Session::get('sponser_request_data');

		$selectedPlan = BoostingPlan::where('id', '=', $sponser_request_data->selected_pack)->first();
		$subtotal = $selectedPlan->price;
		$final_total = $sponser_request_data->total_days * $selectedPlan->price;
		if ($selectedPlan->id == 4 || $selectedPlan->id == 5) {
			if($sponser_request_data->category_slot == 2){
				$final_total = $sponser_request_data->total_days * $selectedPlan->sub_price;
				$subtotal = $selectedPlan->sub_price;
			}
		}

		$coupon = SponsorCoupon::where('coupon_code', $request->coupon_code)
				->where('status',0)
				->whereDate('start_date','<=' ,Carbon::today())
				->first();

		if(is_null($coupon)) {
			return response()->json(['status'=>'error', 'message' => 'Invalid coupon']);
		}
		if($coupon->end_date != null && Carbon::parse($coupon->end_date) < Carbon::today()) {
			return response()->json(['status'=>'error', 'message' => 'This coupon has beed expired.']);
		}

		/*Check for coupon applied*/
		$checkAppiedCount = BoostedServicesOrder::select('id')->where('uid',Auth::user()->id)->where('coupon_id',$coupon->id)->count();
		if($checkAppiedCount > 0){
			return response()->json(['status'=>'error', 'message' => 'Invalid coupon']);
		}

		if($coupon->discount_type == 1) {
			$discount = ($final_total * $coupon->discount) / 100;
			$price = $final_total - $discount;
		} else {
			$discount = $coupon->discount;
			$price = $final_total - $discount;
		}
		if($price < 0) {
			return response()->json(['status'=>'error', 'message' => 'Incorrect amount to pay.']);
		}
		/*$price = $price * $sponser_request_data->total_days;*/

		if(Auth::user()->earning == 0){
			$fromWalletAmount = 0;
		}elseif(Auth::user()->earning >= $price){
			$fromWalletAmount = $price;
		}else{
			$fromWalletAmount = Auth::user()->earning;
		}

		if(Auth::user()->promotional_fund == 0){
			$fromPromotionalAmount = 0;
		}elseif(Auth::user()->promotional_fund >= $price){
			$fromPromotionalAmount = $price;
		}else{
			$fromPromotionalAmount = Auth::user()->promotional_fund;
		}

		return response()->json(['status'=>'success', 'message' => 'Coupon applied', 'price' => round_price($price), 'coupon_code'=>$coupon->coupon_code,'discount'=>round_price($discount), 'fromWalletAmount'=>$fromWalletAmount, 'coupon_id' => $coupon->secret, 'fromPromotionalAmount'=>$fromPromotionalAmount]);
	}

	public function isServiceSponsered(Request $request) {
		$isSponsered = BoostedServicesOrder::where('service_id', '=', $request->id)
		->where(function($query) {
			$query->where('start_date', '<=', date('Y-m-d'))
			->where('end_date', '>=', date('Y-m-d'));
		})
		->orWhere(function($query) {
			$query->where('start_date', '>=', date('Y-m-d'));
		})->first();

		if ($isSponsered) {
			$status = 200;
			$startdate = $isSponsered['start_date'];
		} else {
			$status = 401;
			$startdate = '';
		}
		return response()->json([
			'status' => $status,
			'startdate' => date('M d,Y', strtotime($startdate))
		]);
	}

	public function validate_service_price(Request $request, $secret) {

		$service_id = Service::getDecryptedId($secret);
		$isValid = true;

		if ((isset($_GET['basic']['price']) && $_GET['basic']['price'] < 0) || (isset($_GET['standard']['price']) && $_GET['standard']['price'] < 0) || (isset($_GET['premium']['price']) && $_GET['premium']['price'] < 0)) {
			$isValid = false;
			return response()->json(['valid' => $isValid]);
		}

		if (isset($_GET['basic']['price']) && $_GET['basic']['price'] >= 0) {
			$price = $_GET['basic']['price'];
		} else if (isset($_GET['standard']['price']) && $_GET['standard']['price'] >= 0) {
			$price = $_GET['standard']['price'];
		} else if (isset($_GET['premium']['price']) && $_GET['premium']['price'] >= 0) {
			$price = $_GET['premium']['price'];
		}

		$minCouponPrice = Coupan::select('discount')
		->where('service_id', $service_id)
		->where('is_delete', 0)
		->where('discount_type', 'amount')
		->orderBy('discount', 'DESC')
		->first();

		if (count($minCouponPrice)) {
			if ($price <= $minCouponPrice->discount) {
				$isValid = false;
			}
		}

		echo json_encode(array(
			'valid' => $isValid,
		));
	}
	public function facebookServices() {

		ini_set('memory_limit','1000M');

		$headers = [
			'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
			,   'Content-type'        => 'text/csv'
			,   'Content-Disposition' => 'attachment; filename=facebookServices.csv'
			,   'Expires'             => '0'
			,   'Pragma'              => 'public'
		];

		$list = Service::with('user','category', 'subcategory', 'basic_plans', 'images')
		->where(['status' => 'active'])
		->get()->toArray();
		array_unshift($list, array_keys($list[0]));

		$callback = function() use ($list) 
		{

			$FH = fopen('php://output', 'w');
			$i = 0; 
			$gtin = 0;
			fputcsv($FH, array('# ref_application_id 1845975179000456'));
			fputcsv($FH, array('# ref_asset_id 123'));
			fputcsv($FH, array('id','title','ios_url','ios_app_store_id','ios_app_name','android_url','android_package','android_app_name','windows_phone_url','windows_phone_app_id','windows_phone_app_name','description','google_product_category','product_type','link','image_link','condition','availability','price','sale_price','sale_price_effective_date','gtin','brand','mpn','item_group_id','gender','age_group','color','size','shipping','custom_label_0'));

			foreach ($list as $row) { 
				/*fputcsv($FH, array($row['id'],$row['title'],'','','','','','','','','',$row_description,$row['subcategory']['subcategory_name'],$row['category']['category_name'],$row_url,$image_url,'new','in stock',$price,'','',$gtin,'demo','','','','','','','',''));*/
				if ($i == 0) {
					$i++;
				}else{

					$row_url = route('services_details',[$row['user']['username'],$row['seo_url']]);


					$image_url = url('public/frontend/assets/img/No-image-found.jpg');

					if(count($row['images']) > 0){
						if($row['images'][0]['photo_s3_key'] != ''){
							$image_url = $row['images'][0]['media_url'];
						}
						else{	
							$image_url = url('public/services/images/'.$row['images'][0]['media_url']);
						}
					}
					if ($row['subtitle'] != '') {
						$row_description = $row['subtitle'];
					}else{
						$row_description = str_replace("&nbsp;", " ",strip_tags($row['descriptions']));
						$row_description = strip_tags($row_description);

						if ($row_description != '') {
							if (strlen($row_description) > 140) {
								$row_description = substr($row_description, 0, 140)."...";
							}else{
								$row_description = substr($row_description, 0, 140);
							}
						}else{
							if (strlen($row['title']) > 140) {
								$row_description = substr($row['title'], 0, 140)."...";
							}else{
								$row_description = substr($row['title'], 0, 140);
							}
						}
					}
					$price = $row['basic_plans']['price'].' USD';

					fputcsv($FH, array($row['id'],$row['title'],'','','','','','','','','',$row_description,$row['subcategory']['subcategory_name'],$row['category']['category_name'],$row_url,$image_url,'new','in stock',$price,'','',$gtin,'demo','','','','','','','',''));
					
				}
				$gtin++;
			}

			fclose($FH);
		};

		return \Response::stream($callback, 200, $headers);

	}

	public function viewAnalytics(){

		if(Auth::user()->is_premium_seller() == false){
			return redirect('404');
		}
		
		$uid = $this->uid;

		$total_service_view = SellerAnalytic::select('id')->whereHas('service',function($q) use($uid){
			$q->select('id')->where('uid',$uid);
		})->where('type','service_view')->groupBy('buyer_uid')->get()->count();

		$total_in_cart = SellerAnalytic::select('id')->whereHas('service',function($q) use($uid){
			$q->select('id')->where('uid',$uid);
		})->where('type','add_to_cart')->groupBy('buyer_uid')->get()->count();

		$total_orders = SellerAnalytic::select('id')->whereHas('service',function($q) use($uid){
			$q->select('id')->where('uid',$uid);
		})->where('type','purchase')->groupBy('buyer_uid')->get()->count();
		$yearList = [];
		for($year=2019;$year<=date('Y');$year++){
			$yearList[] = $year;
		}

		return view('frontend.view_analytics',compact('total_service_view','total_in_cart','total_orders','yearList'));
	}
	public function searchAnalytics(Request $request){
		if(Auth::user()->is_premium_seller() == false){
			return redirect('404');
		}
		$user = Auth::user();

		$response = [];

		$year = $request->get('year');
		$type = $request->get('type');

		$service = Service::where('uid', $user->id)
		->where('is_delete',0)->where('is_custom_order', 0);

		if($type == 'service_view'){
			$service = $service->where('is_job', 0);
		}

		$service = $service->orderBy('id', 'desc')->get();
		
		foreach ($service as $key => $value) {
			$rows = array();
			$rows['name'] = $value->title;
			for($month=1;$month<=12;$month++){

				$total_service_view = SellerAnalytic::select('id')
				->whereHas('service',function($q){
					$q->select('id')->where('uid',Auth::user()->id);
				})->where('type',$type)
				->groupBy('buyer_uid')
				->where('service_id',$value->id)
				->whereMonth('created_at',$month)
				->whereYear('created_at',$year)
				->get()
				->count();
				$rows['data'][] = $total_service_view;
			}
			array_push($response,$rows);
		}
		return json_encode($response, JSON_NUMERIC_CHECK);
	}

	public function select_template(Request $request) {
		if(Auth::user()->is_premium_seller() == false && Auth::user()->parent_id == 0){
			return response()->view('errors.404', [], 404);
		}
		$template = SaveTemplate::find($request->id);

		if($template->template_for == 1){
			$message = $template->message;
		}else{
			$message = $template->message;
		}

		$output = array(
			'id' => $template->id,
			'title' => $template->title,
			'template_for' => $template->template_for,
			'message' => $message,
		);
		echo json_encode($output);
	}

	public function update_template(Request $request) {

		if(Auth::user()->is_premium_seller() == false && Auth::user()->parent_id == 0){
			return response()->view('errors.404', [], 404);
		}
		$uid = $this->uid;

		if ($request->is_edit) {

			$id = $request->template_id;

			$save_template = SaveTemplate::find($id);
			$save_template->title = $request->edit_title;

			if($request->template_for == 1){
				$save_template->message = $request->edit_message;
			}else{
				$save_template->message =  br2newline( remove_emoji($request->edit_message) );
			}

			$save_template->save();


			if($request->messageUpdate == "1")
			{
				$status = 201;
			}
			else
			{
				$status = 200;
			}
			
			
			$message = "Template updated";


		} else {

			$getData=SaveTemplate::where(['seller_uid' => $uid,'title' => $request->title])->first();

			if($request->title==""){
				return response()->json([
					'status' => 2,
					'message' => 'Title should not be blank',
				]);
			}
			if($request->template_Data==""){
				return response()->json([
					'status' => 4,
					'message' => 'Template should not be blank',
				]);	
			}
			if($getData)
			{
				return response()->json([
					'status' => 3,
					'message' => 'Same title is not allow',
				]);
			}

			$save_template = new SaveTemplate;
			$save_template->seller_uid = $uid;
			$save_template->title = $request->title;

			if($request->template_for == 1){
				$save_template->message = $request->template_Data;
			}else{
				$save_template->message =  br2newline(remove_emoji($request->template_Data));
			}

			$save_template->template_for = $request->template_for;
			$save_template->save();
			$status = 200;
			$message = "Template saved";
		}

		return response()->json([
			'status' => $status,
			'message' => $message,
			'id' => $save_template->id
		]);
	}


	public function delete_template(Request $request) {
		if(Auth::user()->is_premium_seller() == false && Auth::user()->parent_id == 0){
			return response()->view('errors.404', [], 404);
		}
		$id = $request->template_id;
		$template = SaveTemplate::find($id);
		$template->delete();
	}

	public function getJobOrderDetailPage(request $request)
	{

		$id = User::getDecryptedId($request->id);
		
		$user = User::where('id', $id)->where('status',1)->where('is_delete',0)->with('language')->with('country')->with('skill')->first();
		if (empty($user)) {
			return redirect('404');
		}

		$dataJob= Order::where('seller_uid',$id)
		->where('is_job',1)
		//->whereRaw('(completed_note is not null OR seller_rating > 0)')
		->where('seller_rating', '>', 0)
		->where('status','completed','cancelled')
		->orderby('id','desc');

		$countDataJob=$dataJob->count();
		$dataJob=$dataJob->paginate(10);
		return view('frontend.service.viewjoborderservices',compact('user','dataJob','id','countDataJob'))->render();
	}
	
	public function share_review(Request $request) {
		$info = $request->info;
		$img_url = front_asset('images/profile-default-image.jpg');
		
		$user = User::select('id','parent_id','profile_photo','photo_s3_key')->where('id',$info['uid'])->first()->toArray();
		if(isset($user['parent_id']) && $user['parent_id'] != 0 ){
			$user = User::select('id','parent_id','profile_photo','photo_s3_key')->where('id',$user['parent_id'])->first()->toArray();
		}
		if(isset($user['profile_photo'])) {
			if(isset($user['photo_s3_key']) != '') {
				$img_url = $user['profile_photo'];
			} else {
				$img_url = url('public/seller/profile/'.$user['profile_photo']);
			}
		}
		$img = file_get_contents($img_url); 
		$info['buyer_image'] = "data:image/png;base64,".base64_encode($img); 
		return view('frontend.service.share_review', compact('info'));
	}

	public function trash_service(Request $request, $seo_url) {
		$uid = $this->uid;
		$Service = Service::where(['uid' => $uid, 'seo_url' => $seo_url])->first();
		if (empty($Service)) {
			return redirect(route('services'));
		}

		if ($Service) {
			$Service->is_delete = 1;
			$Service->deleted_date = Carbon::now()->addDays(90);
			$Service->save();
			Session::flash('errorSuccess', 'Service trashed successfully.');
		} else {
			Session::flash('errorFails', 'Something goes wrong.');
		}
		return redirect()->back();
	}

	public function trash_services_list(Request $request) {
		$uid = $this->uid;

		$Service = Service::with('images', 'basic_plans')
					->where('uid',$uid)
					->where('is_delete',1)
					->orderBy('id', 'desc')
					->paginate(20);

		return view('frontend.service.trash_services', compact('Service'));
	}

	public function restore_trashed_service(Request $request, $seo_url) {
		$uid = $this->uid;
		$Service = Service::where(['uid' => $uid, 'seo_url' => $seo_url])->first();
		if (empty($Service)) {
			return redirect(route('services'));
		}

		//Admin can make user to soft ban , so user can't restore service
		if(User::is_soft_ban() == 1){
			return redirect()->route('services')->with('errorFails', get_user_softban_message());
		}

		if ($Service) {
			$Service->is_delete = 0;
			$Service->deleted_date = null;
			$Service->save();
			Session::flash('errorSuccess', 'Service restored successfully.');
		} else {
			Session::flash('errorFails', 'Something goes wrong.');
		}
		return redirect()->back();
	}

	public function remove_service_new($seo_url) {
		$uid = $this->uid;
		$Service = Service::where(['uid' => $uid, 'seo_url' => $seo_url])->first();
		if (empty($Service)) {
			return redirect(route('services'));
		}

		if ($Service) {
			/* $orders = Order::where('service_id', $Service->id)->count();
			$boost_orders = BoostedServicesOrder::where('service_id', $Service->id)->count();
			if ($orders > 0 || $boost_orders > 0) {
				$Service->is_delete = 2;
				$Service->save();
			} else {
				Service::where('id', $Service->id)->delete();
			} */

			$bucket = env('bucket_service');
			$ServiceMedia = ServiceMedia::where('service_id', $Service->id)->get();
			if ($ServiceMedia) {
				foreach ($ServiceMedia as $row) {

					if ($row->media_type == 'image') {
						$destinationPath = public_path('/services/images/');
					} else if ($row->media_type == 'video') {
						$destinationPath = public_path('/services/video/');
					} else if ($row->media_type == 'pdf') {
						$destinationPath = public_path('/services/pdf/');
					}

					if (file_exists($destinationPath . $row->media_url) && $row->photo_s3_key == '') {
						unlink($destinationPath . $row->media_url);
					} else {
						$keyData = $row->photo_s3_key;
						$s3 = AWS::createClient('s3');

						try {
							$result_amazonS3 = $s3->deleteObject([
								'Bucket' => $bucket,
								'Key' => $keyData,
							]);
						} catch (Aws\S3\Exception\S3Exception $e) {

						}

						//delete thumbnail
						if($row->thumbnail_media_url != null) {
							$thumb_imageKey_ary = explode('/',$row->photo_s3_key);
							$keyDataThumb = $thumb_imageKey_ary[0] .'/thumb/'.str_replace(".gif",".png",$thumb_imageKey_ary[1]);
							try {
								$result_amazonS3 = $s3->deleteObject([
									'Bucket' => $bucket,
									'Key' => $keyDataThumb,
								]);
							} catch (Aws\S3\Exception\S3Exception $e) {
								//error
							}
						}
					}
				}
				ServiceMedia::where('service_id', $Service->id)->delete();
				ServiceQuestion::where('service_id', $Service->id)->delete();
			}

			//update service as delete
			$Service->is_delete = 2;
			$Service->save();

			Session::flash('errorSuccess', 'Service removed successfully.');
		} else {
			Session::flash('errorFails', 'Something goes wrong.');
		}
		return redirect()->back();
	}

	public function remove_all_service(Request $request) {
		$uid = $this->uid;
		$services = Service::where('uid',$uid)->where('is_delete',1)->get();
		if(!is_null($services)) {
			foreach ($services as $key => $value) {
				$orders = Order::select('id')->where('service_id', $value->id)->count();
				$boost_orders = BoostedServicesOrder::select('id')->where('service_id', $value->id)->count();
				if ($orders > 0 || $boost_orders > 0) {
					$value->is_delete = 2;
					$value->save();
				} else {
					Service::where('id', $value->id)->delete();
				}
				$bucket = env('bucket_service');
				$ServiceMedia = ServiceMedia::where('service_id', $value->id)->get();
				if ($ServiceMedia) {
					foreach ($ServiceMedia as $row) {

						if ($row->media_type == 'image') {
							$destinationPath = public_path('/services/images/');
						} else if ($row->media_type == 'video') {
							$destinationPath = public_path('/services/video/');
						} else if ($row->media_type == 'pdf') {
							$destinationPath = public_path('/services/pdf/');
						}

						if (file_exists($destinationPath . $row->media_url) && $row->photo_s3_key == '') {
							unlink($destinationPath . $row->media_url);
						} else {
							$keyData = $row->photo_s3_key;
							$s3 = AWS::createClient('s3');

							try {
								$result_amazonS3 = $s3->deleteObject([
									'Bucket' => $bucket,
									'Key' => $keyData,
								]);
							} catch (Aws\S3\Exception\S3Exception $e) {

							}

							//delete thumbnail
							if($row->thumbnail_media_url != null) {
								$thumb_imageKey_ary = explode('/',$row->photo_s3_key);
								$keyDataThumb = $thumb_imageKey_ary[0] .'/thumb/'.str_replace(".gif",".png",$thumb_imageKey_ary[1]);
								try {
									$result_amazonS3 = $s3->deleteObject([
										'Bucket' => $bucket,
										'Key' => $keyDataThumb,
									]);
								} catch (Aws\S3\Exception\S3Exception $e) {
									//error
								}
							}
						}
					}
					ServiceMedia::where('service_id', $value->id)->delete();
					ServiceQuestion::where('service_id', $value->id)->delete();
				}
			}
			Session::flash('errorSuccess', 'Services removed successfully.');
		}  else {
			Session::flash('errorFails', 'Something goes wrong.');
		}
		return redirect()->back();
	}

	public function reviewFeedback(Request $request){
		$status = 400;
		$message = 'Something went wrong.please try agin somtimes.';
		$count = 0;
		$order_id = Order::getDecryptedId($request->order_id);
		
		$message = 'Order not found.';
		try{
			if(empty($order_id)){
				$message = 'Order not found.';
			}
		}catch(\Exception $e){
			$message = 'Order not found.';
		}
		$Order = Order::where('id',$order_id)->first();
		if($Order !=null){
			
			$user_id = Auth::user()->id;
			$service_id = $Order->service_id;
			$service = Service::where('id',$service_id)->first();
			
			if(isset($request->order_id) && isset($user_id) && isset($service_id)  && isset($request->type) ){
				$reviewFeedback = ReviewFeedback::where('order_id',$order_id)->where('user_id',$user_id)->where('service_id',$service_id)->where('type',$request->type)->select('id')->count();
				if($reviewFeedback == 0){
					$status = 200;
					$message = 'This review was helpful.';	
					$reviewFeedback = new ReviewFeedback;
					$reviewFeedback->order_id = $order_id;
					$reviewFeedback->user_id = $user_id;
					$reviewFeedback->service_id = $service_id;
					$reviewFeedback->type = $request->type; 
					$reviewFeedback->save();
					if($reviewFeedback->type == 'helpful'){
						$Order->helpful_count = $Order->helpful_count + 1;
					}else{
						$Order->report_abuse_count = $Order->report_abuse_count + 1;
					}
					$Order->save();

					$service = Service::where('id',$service_id)->first();
					if($service != null){
						if($reviewFeedback->type == 'helpful'){
							$service->helpful_count = $service->helpful_count + 1;
						}else{
							$service->report_abuse_count = $service->report_abuse_count + 1;
						}
					}
					$service->save();
					$count = $Order->helpful_count;
				}else{
					$status = 400;
					$message = 'Your feedback already submitted';	
				}
			}
		}
		return response()->json([
			'status' => $status,
			'message' => $message,
			'count' => $count,
		]);
	}

	public function get_my_service_list(Request $request) {
		if(!$request->filled('searchTerm')) {
			return redirect()->back();
		}
		$searchTerm = strtolower($request->searchTerm);

		$services_data = Service::statusof('service')
		->where('services.uid', $this->uid)
		->where('services.title', 'LIKE', '%' . $searchTerm . '%')
		->select('services.title','services.id')
		->limit(6)->get();

		$services = [];
		foreach ($services_data as $key => $value) {
			$temp = [];
			$temp['id'] = $value->secret;
			$temp['text'] = $value->title;
			array_push($services,$temp);
		}

		return response()->json(['status'=>'success','services'=>$services]);
	}

	public function get_service_card_preview(Request $request) {
		$service_id = Service::getDecryptedId($request->service_id);
		$service = Service::with('user','images','basic_plans')
							->select('id','uid','title','service_rating','total_review_count','seo_url')->find($service_id);
		if(is_null($service)) {
			return response()->json(['status' => 'error']);
		}
		$html = view('frontend.chatify.layouts.service_card',compact('service'))->render();
		return response()->json(['status' => 'success', 'html' => $html]);
	}

	public function rentAdSpot(Request $request) {
		$plan_secret = $request->plan_secret;
		$plan_id = BoostingPlan::getDecryptedId($plan_secret);
		$service_secret = $request->search_service_term;
		$service_id = Service::getDecryptedId($service_secret);
		$service = Service::where('id',$service_id)->select('id','seo_url')->first();
		if(is_null($service)) {
			Session::flash('errorFails', 'Invalid service');
			return redirect()->back();
		}
		Session::put('rentAdSpotPlanId',$plan_id);
		return redirect()->route('boostService',$service->seo_url);
	}

	// Change service ordering 
	public function changeOrdering(Request $request){
		if($request->has('id') && $request->id != ""){
            foreach ($request->id as $key => $id) {
				$id = Service::getDecryptedId($id);
                $service = Service::select('id','sort_by')->where('is_delete', 0)->where('uid',$this->uid)->where('id',$id)->first();
				if($service){
                    $service->sort_by = $key;
                    $service->save();
                }else{
                    break;
                    $response['success'] = false;
					$response['message'] = "Something went wrong. Please try again.";
					$response['status'] = 401;
                    return response()->json($response);
                }
            }
            $response['message'] = "Service sorting changed successfully";
            $response['success'] = true;
            $response['status'] = 200;
        }else{
            $response['success'] = false;
            $response['status'] = 401;
        }
        return response()->json($response);
	}

	/*Seller services*/
	public function serviceChangeOrder(Request $request){
		/* Get services */
		$query = Service::select('services.*')
			->where('services.status', 'active')
			->where('services.is_approved', 1)
			->where('is_private', 0)
			->where('is_job', 0)
			->where('is_custom_order', 0)
			->where('services.uid', $this->uid)
			->where('services.is_delete',0)
			->join('category', 'category.id', '=', 'services.category_id')
			->where('users.status',1)
			->where('users.is_delete',0)
			->where('users.vacation_mode',0)
			->join('users', 'users.id', '=', 'services.uid')
			->distinct()
			->orderBy('sort_by', 'asc');
			
		/* Count services */
		$service_count = $query;
		$Service = $query->paginate(21)->appends($request->all());
		
		/* Load pagination */
		if($request->ajax()){
			return view('frontend.seller.load_services_ordering',compact('Service'))->render();
		}

		/* Check Services is exists or not */
		if(!$service_count->count())
		{
			return redirect()->back();
		}

		/* View services */
		return view('frontend.seller.service_ordering',compact('Service'));
	}

	public function followUser($request, $username)
	{
		$follow_user = User::select('id','parent_id')->where('username', $username)->first();
		$response = ['type' => 'success', 'msg' => "Would you like to follow {$username}?", 'user_id' => $follow_user->secret];
		if(isset($request->follow_confirmation) && $request->follow_confirmation == 1){
			/* Checking if same user or parent user */
			$parentData = User::select('username')->where('id', $this->uid)->first();
			if ($parentData->username == $username){
				$response['type'] = 'error';
				$response['msg'] = 'You can\'t follow yourself';
			}

			/* Check Blocked Users */
			$is_block = User::isBlockMyProfile($this->uid, $follow_user->id);
			if($is_block == 1){
				$response['type'] = 'error';
				$response['msg'] = "{$username} is blocked";
			}

			/* Check already followed Users */
			$follower = UserFollow::select('id', 'status')->whereUserId($follow_user->id)->whereFollowerId($this->uid)->first();
			if(isset($follower) && $follower->status == 1){
				$response['type'] = 'error_already_follow';
				$response['msg'] = "You are already following {$username}";
			}
		} else {
			$response['type'] = 'error';
			$response['msg'] = "Follow confirmation not found";
		}
		return $response;
	}
}