<?php

namespace App\Http\Controllers;

use App\Category;
use App\Newsletter;
use App\Order;
use App\ReactivationRequest;
use App\Service;
use App\User;
use Auth;
use DB;
use Illuminate\Http\Request;
use Session;
use App\BoostedServicesOrder;
use App\UserBlockList;
use Twilio;
use App\SubscribeUser;
use App\Subscription;
use App\HomePageData;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use App\Notification;
use App\Setting;
use App\AimtellSubscriber;
use App\UserSearchTerm;
use App\UserSearchCategory;
use App\HomeSlider;
use App\UserLanguage;
use App\Subcategory;
use App\GeneralSetting;
use App\ReviewFeedback;
use App\BoostingPlan;
use App\UserHomePickService;
use App\UserTwoFactorAuthDetails;
use App\PizzaAppliedHistory;
use App\demoPage;
use App\UserLoginByAdminHistory;
use App\UserDevice;
use App\AppTwoFactorAuth;
use App\OrderExtendRequest;
use App\Models\SmsHistory;
use Validator;

class HomeController extends Controller {

    
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

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
		//get settings
		$settings = Setting::find(1)->select('id','meta_title','meta_keywords','meta_description')->first();
		$sponsered_trading = $this->get_sponsered_trading();
		$treadingServices = $this->get_treading_services();
		$sponsered_middle = $this->get_sponsered_middle();
		$toprateServices = $this->get_top_rate_services();

		if(Auth::check()) {

			//Set top header data session one time
			if(!Session::has('isAnyActiveService')){
				$updateHerader = new Order;
				$updateHerader->updateHeader();
			}

			$boosting_plans = $this->get_boosting_plans();

			$recently_placed_order = Order::select('id','uid','service_id','order_no','delivery_days','status','order_total_amount','is_custom_order')
										->with('service:id,title')
										->where('status','!=','canceled')
										->where('uid',$this->uid)
										->orderBy('id','desc')
										->first();
			if(!is_null($recently_placed_order)) {
				if($recently_placed_order->is_custom_order == 1){
					$recently_placed_order->thumbnail = url('public/frontend/images/customorderthumb.jpg');
				}else{
					$recently_placed_order->thumbnail = get_service_image_url($recently_placed_order->service);
					unset($recently_placed_order->service->images);
				}
			}

			$UserHomePickServiceInfo = UserHomePickService::where('user_id',$this->uid)->select('recently_viewed_service')->first();
			$recently_viewed_service = Service::select('id','uid','seo_url','title','subtitle','descriptions','service_rating','total_review_count')
											->where('id',$UserHomePickServiceInfo->recently_viewed_service);
        									/*Check block by user*/
											$block_users = User::getBlockedByIds();
											if(count($block_users)>0){
												$recently_viewed_service = $recently_viewed_service->whereNotIn('uid',$block_users);
											}
											$recently_viewed_service = $recently_viewed_service->orderBy('updated_at','desc')
											->first();
			if(!is_null($recently_viewed_service)) {
				$recently_viewed_service->thumbnail = get_service_image_url($recently_viewed_service);
				unset($recently_viewed_service->images);
			}

			$deal_of_the_day = $this->get_deal_of_the_day();
			if(!is_null($deal_of_the_day) && isset($deal_of_the_day)) {
				$deal_of_the_day->thumbnail = get_service_image_url($deal_of_the_day);
				unset($deal_of_the_day->images);
			}

			$notifications = Notification::select('id','message','notify_from','notify_to','type','order_id','updated_at')
									->with('order:id,uid,order_no')
									->where('notify_to', Auth::user()->id)
									->where('is_read',0)
									->where('is_delete',0)
									->where('type','!=','payment_failed')
									->orderBy('id', 'desc')
									->limit(2)
									->get();

			/* Add Redirect URL */
			foreach ($notifications as $key => $row) {
				$row->redirect_url = "javascript:void(0)";
				if(isset($row->order->id)){
					$isRejected = OrderExtendRequest::select('id')->where('order_id',$row->order->id)->where('is_accepted','2')->count();
				}else{
					$isRejected = 0;
				}
				if($row->type == 'custom_order') {
					$Service = Service::select('seo_url','custom_order_buyer_uid')->find($row->order_id);
					if(!isset($Service->seo_url)) { $Service->seo_url = ''; }
					if($Service->custom_order_buyer_uid == $row->notify_to) {
						$row->redirect_url = route('buyer_custom_order_details',$Service->seo_url);
					} else {
						$row->redirect_url = route('seller_custom_order_details',$Service->seo_url);
					}
				} else if($row->type == 'job_proposal_send') {
					$Service = 	Service::select('seo_url')->find($row->order_id);
					if(!isset($Service->seo_url)) { $Service->seo_url = ''; }
					$row->redirect_url = route('show.job_detail',$Service->seo_url)."?notification_id".$row->id;
				} else {
					if($row->order_id != 0 && !empty($row->order)) {
						if($row->order->uid == $row->notify_to) {
							$row->redirect_url = route('buyer_orders_details',$row->order->order_no);
						} else {
							if($isRejected) {
								$row->redirect_url = route('seller_extended_order_request',$row->order->order_no);
							} else { 
								$row->redirect_url = route('seller_orders_details',$row->order->order_no);
							}
						}
					}
				}
			}

			$pickedYourServiceListing = $this->get_recent_filtered_services_new();

			return view('home_with_login', compact('settings','sponsered_trading','boosting_plans','sponsered_middle','treadingServices','toprateServices','recently_placed_order','recently_viewed_service','deal_of_the_day','notifications','pickedYourServiceListing'));
		} else {
			//get home slider
			$home_slider = HomeSlider::orderBy('sort_order','asc')->get();

			//get category data
			$categories = Category::limit(6)->where('seo_url','!=','by-us-for-us')->get();

        	$popularGraphicServices = Service::statusof('service')
    		->where('category_id', 1)
        	->orderBy('no_of_purchase', 'desc')
        	->limit(8)
        	->get();


        	$popularSeoServices = Service::statusof('service')
        	->where('category_id', 2)
        	->orderBy('no_of_purchase', 'desc')
        	->limit(8)
        	->get();

        	$popularMarketingServices = Service::statusof('service')
        	->where('category_id', 3)
        	->orderBy('no_of_purchase', 'desc')
        	->limit(8)
        	->get();

        	$popularVedioServices = Service::statusof('service')
        	->where('category_id', 4)
        	->groupBy("services.id")
        	->orderBy('no_of_purchase', 'desc')
        	->limit(8)
        	->get();

        	$popularMusicServices = Service::statusof('service')
        	->where('category_id', 5)
        	->orderBy('no_of_purchase', 'desc')
        	->limit(8)
        	->get();

        	$popularProgrammingServices = Service::statusof('service')
        	->where('category_id', 6)
        	->orderBy('no_of_purchase', 'desc')
        	->limit(8)
        	->get();

			return view('home_without_login', compact('settings','sponsered_trading','treadingServices','sponsered_middle','toprateServices','home_slider','categories','popularGraphicServices','popularSeoServices','popularMarketingServices','popularVedioServices','popularMusicServices','popularProgrammingServices'));
		}
	}

	function get_sponsered_trading() {
		$sponsered_trading = BoostedServicesOrder::with('service.user', 'service.category', 'service.images', 'service.basic_plans')
        ->where('plan_id', '=', 6)
		->where('status','active')
        ->where(function($query) {
            $query->where('start_date', '<=', date('Y-m-d'))
            ->where('end_date', '>=', date('Y-m-d'));
        });
		$sponsered_trading = $sponsered_trading->whereHas('service', function ($query) {
			$query->select('id')->where('is_private', 0)->where('status','active')->where('is_approved',1)->where('is_delete',0);
			$block_users = User::getBlockedByIds();
			if(count($block_users)>0){
				$query->whereNotIn('uid',$block_users); /* Check Blocked Users */
			}
		});
        $sponsered_trading = $sponsered_trading->whereHas('service.user', function ($query) {
            $query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->where('soft_ban', 0);
        });
        $sponsered_trading = $sponsered_trading->first();

		return $sponsered_trading;
	}

	function get_boosting_plans() {
		$boosting_plans_ary = BoostingPlan::whereIn('id',[2,6])->select('id','name','price')->get();
		$boosting_plans = [];
		foreach ($boosting_plans_ary as $key => $value) {
			$boosting_plans[$value->id] = $value;
		}
		return $boosting_plans;
	}

	function get_treading_services() {
		$new_trending_services = HomePageData::where('slug','new_trending_services')->first();
        $diff = time() - strtotime( $new_trending_services->updated_at);
        $hours = $diff / ( 60 * 60 );

        if($hours >= 24 ){
            $treadingServices = Order::where('services.service_rating','=', 5)
			->where('services.is_private', 0)
			->where('services.is_job', 0)
			->where('services.is_custom_order', 0)
			->where('services.status', 'active')
			->where('services.is_delete',0)
            ->where('services.is_approved', 1)
			->where('users.status', 1)
			->where('users.soft_ban', 0)
			->where('users.is_delete', 0)
			->where('users.vacation_mode', 0);

            $treadingServices = $treadingServices->leftjoin("services", "orders.service_id", '=', 'services.id')
			->leftjoin("users", "users.id", '=', 'services.uid')
            ->select("services.id")
            ->distinct("services.id");
			/* Check Blocked Users */
			$block_users = User::getBlockedByIds();
			if(count($block_users)>0){
				$treadingServices = $treadingServices->whereNotIn('services.uid',$block_users); 
			}
            $treadingServices = $treadingServices->OrderBy('orders.created_at', 'desc')
            ->limit(10)
            ->get();

            $topTreadingServiceIds = [];
            foreach ($treadingServices as $raw) {
               $topTreadingServiceIds[] = $raw->id;
            }

            $new_trending_services->service_ids = json_encode($topTreadingServiceIds);
            $new_trending_services->updated_at = date('Y-m-d H:i:s');
            $new_trending_services->save();
        }
		$new_trending_services = HomePageData::where('slug','new_trending_services')->first();
		$service_ids = json_decode($new_trending_services->service_ids);

        $treadingServices = null;
        if(count($service_ids) > 0){
            $ids_ordered = implode(',', $service_ids);

            $treadingServices = Service::whereIn('id',$service_ids)
			->statusof('service')
            ->where('service_rating', 5)
			->orderByRaw("FIELD(id, $ids_ordered)");

			if (isset(Auth::user()->id)) {
                $treadingServices = $treadingServices->where('services.uid','!=' ,Auth::user()->id);
            }
			$treadingServices = $treadingServices->get();

        }
		return $treadingServices;
	}

	function get_sponsered_middle() {
		$sponsered_middle = BoostedServicesOrder::with('service', 'service.user', 'service.category', 'service.images', 'service.basic_plans')
    	->where('plan_id', '=', 2)
		->where('status','active')
    	->where(function($query) {
    		$query->where('start_date', '<=', date('Y-m-d'))
    		->where('end_date', '>=', date('Y-m-d'));
		});
		
		/**Not showing private service  */
		$sponsered_middle = $sponsered_middle->whereHas('service', function ($query) {
    		$query->select('id')->where('is_private', 0)->where('status','active')->where('is_approved',1)->where('is_delete',0);
			/* Check Blocked Users */
			$block_users = User::getBlockedByIds();
			if(count($block_users)>0){
				$query->whereNotIn('uid',$block_users);
			}
    	});
    	$sponsered_middle = $sponsered_middle->whereHas('service.user', function ($query) {
    		$query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->where('soft_ban', 0);
    	});
    	$sponsered_middle = $sponsered_middle->first();

		return $sponsered_middle;
	}

	// No longer required
	function get_top_rate_services__() {
		$toprateServices = null;
		$uid = $this->uid;

		$toprateServices = Service::select('services.id','services.uid','services.seo_url','services.title','services.subtitle','services.descriptions','services.service_rating','services.total_review_count',DB::Raw('ROUND(services.service_rating, 0) As service_round_rating'))
		->where('services.service_rating', "!=", '0')
		->where('services.status', 'active')
		->where('services.is_private', 0)
		->where('services.is_delete',0)
		->where('services.is_job', 0)
		->where('services.is_custom_order', 0)
		->where('services.is_approved', 1);

		if(Auth::check()){
			$toprateServices = $toprateServices->where('services.uid','!=', $uid);
		}
		/* Check Blocked Users */
		$block_users = User::getBlockedByIds();
		if(count($block_users)>0){
			$toprateServices = $toprateServices->whereNotIn('services.uid',$block_users);
		}
		$toprateServices = $toprateServices->join('users','users.id','services.uid')
		->where('users.status',1)
		->where('users.soft_ban', 0)
		->where('users.is_delete',0)
		->where('users.vacation_mode',0)
		->OrderBy('service_round_rating', 'DESC')
		->OrderBy('services.total_review_count', 'DESC')
		->limit(10)
		->get();

		return $toprateServices;
	}

	function get_top_rate_services() {
		$toprateServices = null;
		$uid = $this->uid;

		$new_top_rated_services = HomePageData::where('slug','top_rated_services')->first();
        $diff = time() - strtotime( $new_top_rated_services->updated_at);
        $hours = $diff / ( 60 * 60 );

        if($hours >= 1 ){
            $toprateServices = Service::select('services.id','services.uid','services.seo_url','services.title','services.subtitle','services.descriptions','services.service_rating','services.total_review_count',DB::Raw('ROUND(services.service_rating, 0) As service_round_rating'))
			->where('services.service_rating', "!=", '0')
			->where('services.status', 'active')
			->where('services.is_private', 0)
			->where('services.is_delete',0)
			->where('services.is_job', 0)
			->where('services.is_custom_order', 0)
			->where('services.is_approved', 1)
			->where('users.status',1)
			->where('users.soft_ban', 0)
			->where('users.is_delete',0)
			->where('users.vacation_mode',0);
			
			/* Check Blocked Users */
			$block_users = User::getBlockedByIds();
			if(count($block_users)>0){
				$toprateServices = $toprateServices->whereNotIn('services.uid',$block_users); /* Check Blocked Users */
			}

			$toprateServices = $toprateServices->join('users','users.id','services.uid')
			->OrderBy('service_round_rating', 'DESC')
			->OrderBy('services.total_review_count', 'DESC')
			->limit(15)
			->get();

            $topRatedServiceIds = [];
            foreach ($toprateServices as $raw) {
               $topRatedServiceIds[] = $raw->id;
            }

            $new_top_rated_services->service_ids = json_encode($topRatedServiceIds);
            $new_top_rated_services->updated_at = date('Y-m-d H:i:s');
            $new_top_rated_services->save();
        }

		$service_ids = json_decode($new_top_rated_services->service_ids);
			
		if(count($service_ids) > 0){
			$toprateServices = Service::select('services.id','services.uid','services.seo_url','services.title','services.subtitle','services.descriptions','services.service_rating','services.total_review_count',DB::Raw('ROUND(services.service_rating, 0) As service_round_rating'));

			if (isset(Auth::user()->id)) {
				$toprateServices = $toprateServices->where('services.uid','!=', $uid);
			}
			
			$toprateServices = $toprateServices
			->whereIn('services.id',$service_ids)
			->OrderBy('service_round_rating', 'DESC')
			->OrderBy('services.total_review_count', 'DESC')
			->limit(10)
			->get();
		}
		
		return $toprateServices;
	}

	function get_recent_filtered_services_new(){
		$pickedYourServiceListing = $pick_service_ids = [];

        if (Auth::check()) {
            $uid = $this->uid;
			$pick_service = UserHomePickService::where('user_id',$uid)->select('id','service_ids')->first();
			if(!is_null($pick_service)) {
				$pick_service_ids = $pick_service->service_ids;
				if(count($pick_service_ids) > 0) {
					shuffle($pick_service_ids);
					$checkArray = array_slice($pick_service_ids,0,10);
					$pickedYourServiceListing = Service::select('id','uid','seo_url','title','subtitle','descriptions','service_rating','total_review_count');
					/* Check Blocked Users */
					$block_users = User::getBlockedByIds();
					if(count($block_users)>0){
						$pickedYourServiceListing = $pickedYourServiceListing->whereNotIn('services.uid',$block_users); /* Check Blocked Users */
					}
					$pickedYourServiceListing = $pickedYourServiceListing->where('status','active')->whereIn('id',$checkArray)
						->groupBy('services.id')
						->limit(10)->get();
				}
			}
		}
		return $pickedYourServiceListing;
	}

	function get_deal_of_the_day(){
		$deal = $pick_service_ids = [];

		$uid = $this->uid;
		$pick_service = UserHomePickService::where('user_id',$uid)->select('id','service_ids_for_deal')->first();

		if(!is_null($pick_service)) {
			$pick_service_ids = $pick_service->service_ids_for_deal;
			if(count($pick_service_ids) > 0) {
				shuffle($pick_service_ids);
				$checkArray = array_slice($pick_service_ids,0,1);

				$deal = Service::select('id','uid','seo_url','title','subtitle','descriptions','service_rating','total_review_count')
					->whereIn('id',$checkArray)
					->whereHas('promoted_coupon',function($q) {
						$q->select('id');
					});
        			/*Check block by user*/
					$block_users = User::getBlockedByIds();
					if(count($block_users)>0){
						$deal = $deal->whereNotIn('services.uid',$block_users); /* Check Blocked Users */
					}
					$deal = $deal->groupBy('services.id')
					->first();
			}
		}
		if(is_null($deal) || count($deal) == 0) {
			/*$deal = Service::select('id','uid','seo_url','title','subtitle','descriptions','service_rating','total_review_count')
						->where('uid','!=',$uid)
						->statusof('service')
						->whereHas('promoted_coupon',function($q) {
							$q->select('id');
						})
						->inRandomOrder()
						->first(); */

			$totalCount = Service::select('id')
				->where('uid','!=',$uid)
				->statusof('service')
				->whereHas('promoted_coupon',function($q) {
					$q->select('id');
				});
				/* Check Blocked Users */
				$block_users = User::getBlockedByIds();
				if(count($block_users)>0){
					$totalCount = $totalCount->whereNotIn('uid',$block_users); 
				}
				$totalCount = $totalCount->count();

			$random_offset = rand(1,$totalCount);

			$deal = Service::select('id','uid','seo_url','title','subtitle','descriptions','service_rating','total_review_count')
				->where('uid','!=',$uid)
				->statusof('service')
				->whereHas('promoted_coupon',function($q) {
					$q->select('id');
				})
				->offset($random_offset)
				->first();

		}
		return $deal;
	}

    function get_recent_filtered_services(){
        $pickedYourServiceListing = [];

        if (Auth::check()) {
            $uid = $this->uid;

            $filteredServiceTerms = $filteredServiceCategory = $relatedSellerService = [];

            $serviceWhere = [
                'services.status'=>'active',
                'services.is_approved'=>1,
                'services.is_delete'=>0,
                'services.is_private'=>0,
                'services.is_job'=>0,
                'services.is_custom_order'=>0,
                'users.status'=>1,
                'users.is_delete'=>0,
                'users.vacation_mode'=>0
            ];

            /*begin : get random 100 filtered services*/
            $userSearchTerm = UserSearchTerm::where('user_id',$uid)->first();
            if(!empty($userSearchTerm)){
                $search_term = $userSearchTerm->search_term;
                $filteredServiceTerms = Service::select('services.id')
                ->where($serviceWhere)
                ->where(function($q) use ($search_term){
                    foreach ($search_term as $search_word) {
                        //$search_word = utf8_encode($search_word);
                        $q->orWhere('services.title', 'LIKE', '%' . $search_word . '%');
                        $q->orWhere('category.category_name', 'LIKE', '%' . $search_word . '%');
                        $q->orWhere('users.username', 'LIKE', '%' . $search_word . '%');
                    }
                });
				/* Check Blocked Users */
				$block_users = User::getBlockedByIds();
				if(count($block_users)>0){
					$filteredServiceTerms = $filteredServiceTerms->whereNotIn('services.uid',$block_users); /* Check Blocked Users */
				}
                $filteredServiceTerms = $filteredServiceTerms->join('users', 'services.uid', '=', 'users.id')
                ->join('category', 'category.id', '=', 'services.category_id')
                ->inRandomOrder()
                ->groupBy('services.id')
                ->limit(100)->get()->makeHidden('secret')->toArray();

            }

            /*end : get random 100 filtered services*/

            /*begin : get random 100  browsed category services*/
            $userSearchCategory = UserSearchCategory::select('category_id')
            ->where('updated_at', '>=', Carbon::now()->subDays(7))
            ->where('user_id',$uid)
            ->get()->toArray();
            if(count($userSearchCategory) > 0){

                $filteredServiceCategory = Service::select('services.id')
                ->where($serviceWhere)
                /*->whereHas('order',function($q) use ($uid){
                    $q->where('uid','!=',$uid);
                })*/
                ->whereIn('services.category_id',$userSearchCategory)
                ->whereNotIn('services.id',$filteredServiceTerms)
                ->join('users', 'services.uid', '=', 'users.id')
                ->inRandomOrder()
                ->groupBy('services.id')
                ->limit(100)->get()->makeHidden('secret')->toArray();
            }

            /*end : get random 100  browsed category services*/

            /*begin : new from sellers they bought from in the past */
            $sellerList = Order::distinct()->select('seller_uid')->where('uid',$uid)->inRandomOrder()->limit(5)->get()->toArray();
            
            if(count($sellerList) > 0){

                $relatedSellerService = Service::select('services.id')
                ->whereIn('services.uid',$sellerList)
                ->whereNotIn('services.id',$filteredServiceCategory)
                ->where($serviceWhere)
                ->join('users', 'services.uid', '=', 'users.id')
                ->orderBy('services.created_at','desc')
                ->groupBy('services.id')
                ->limit(100)->get()->makeHidden('secret')->toArray();

                
            }
            /*begin : new from sellers they bought from in the past */

            /*Pick 10 top-rated services form above filtered*/
            $all_service_ids =array_merge($filteredServiceTerms,$filteredServiceCategory,$relatedSellerService);
           
            $pickedYourServiceListing = Service::whereIn('id',$all_service_ids)
            ->orderBy('service_rating','desc')
            ->groupBy('services.id')
            ->limit(10)->get();
        }
        return $pickedYourServiceListing;
    }

    function get_recent_filtered_services_(){
        $pickedYourServiceListing = null;
        if (Auth::check()) {
            $ids_ordered = [];
            $uid = $this->uid;
            $totalSearchCount = 0;
            $filteredServiceIds = [];

            $userSearchTerm = UserSearchTerm::where('user_id',$uid)->first();
            
            if(!empty($userSearchTerm)){
                $search_term = $userSearchTerm->search_term;
                if(count($search_term) > 0){
                   $search_term = array_reverse($search_term);
                }
                
                /*top rated for recent search terms*/
                
                foreach ($search_term as $searchKey) {
                    if($totalSearchCount > 5){
                        continue;
                    }

                    $filteredServiceQuery = Service::distinct()
                    ->select('services.id')
                    ->where('services.status', 'active')
                    ->where('services.is_approved', 1)
                    ->where('services.is_delete', 0)
                    ->where('services.is_private', 0)
                    ->where('services.is_job', 0)
                    ->where('services.is_custom_order', 0)

                    ->where(function($q) use ($searchKey){
                        $q->where('services.title', 'LIKE', '%' . $searchKey . '%');
                        $q->orWhere('category.category_name', 'LIKE', '%' . $searchKey . '%');
                        $q->orWhere('users.username', 'LIKE', '%' . $searchKey . '%');
                    })
                   
                    ->where('users.status', 1)
					->where('users.soft_ban', 0)
                    ->where('users.is_delete', 0)
                    ->where('users.vacation_mode', 0)
                    ->join('users', 'services.uid', '=', 'users.id')
                    ->join('category', 'category.id', '=', 'services.category_id')
                    ->orderBy('service_rating','desc')
                    ->limit(5)->get();

                    if(count($filteredServiceQuery) > 0){
                        foreach ($filteredServiceQuery as $row) {
                            if($totalSearchCount < 5){
                                $filteredServiceIds[] = $row->id;
                                $totalSearchCount++;
                            }
                        }
                    }
                }
            }

            /*new from sellers they bought from in the past */
            /*Get seller list that buyer have purchase services*/
            $sellerList = Order::distinct()->select('seller_uid')->where('uid',$uid)->get()->toArray();
            
            if(count($sellerList) > 0){

                $remainServices = 10 - count($filteredServiceIds);

                $relatedSellerService = Service::distinct()
                ->select('services.id')
                ->where('services.status', 'active')
                ->whereHas('order',function($q) use ($uid){
                    $q->select('id')->where('uid','!=',$uid);
                })
                ->whereIn('services.uid',$sellerList)
                ->whereNotIn('services.id',$filteredServiceIds)
                ->where('services.is_approved', 1)
                ->where('services.is_delete', 0)
                ->where('services.is_private', 0)
                ->where('services.is_job', 0)
                ->where('services.is_custom_order', 0)
                ->where('users.status', 1)
				->where('users.soft_ban', 0)
                ->where('users.is_delete', 0)
                ->where('users.vacation_mode', 0)
                ->join('users', 'services.uid', '=', 'users.id')
                ->inRandomOrder()
                ->orderBy('services.created_at','desc')
                ->limit($remainServices)->get();

                if(count($relatedSellerService) > 0){
                    foreach ($relatedSellerService as $row) {
                        $filteredServiceIds[] = $row->id;
                    }
                }
            }

            if(count($filteredServiceIds) > 0){
                $ids_ordered = implode(',', $filteredServiceIds);
                $pickedYourServiceListing = Service::distinct()
                ->whereIn('id',explode(",", $ids_ordered))
                ->orderByRaw("FIELD(id, $ids_ordered)")->get();
            }
        }
        return $pickedYourServiceListing;
    }

    public function ResendEmail() {
    	$userid = Auth::user()->id;

    	if (empty(Auth::user()->confirmation_code)) {
    		$code = uniqid();
    	} else {
    		$code = Auth::user()->confirmation_code;
    	}

    	DB::table('users')
    	->where('id', $userid)
    	->update(['confirmation_code' => $code]);
    	app('App\Http\Controllers\FrontEmailController')->verificationemail(Auth::User()->Name, Auth::User()->email, $code);

    	return 1;
    }

    public function newsletter(Request $request) {
    	$newsletter = Newsletter::where('email', $request->email)->first();
    	if (count($newsletter) > 0) {
    		return response([
    			'status' => false,
    			'message' => 'You have already subscribed to the list.',
    		]);
    	} else {
    		$newsletter = new Newsletter;
    		$newsletter->email = $request->email;
    		$newsletter->created_at = time();
    		$newsletter->updated_at = time();
    		$newsletter->save();
    		return response([
    			'status' => true,
    			'message' => 'Thank you for subscribing for our newsletter.',
    		]);
    	}
    }

    public function error_404(Request $request) {
    	return view("errors.service");
    }

    public function thankyou() {
    	if (Session::has('path')) {
    		return view('frontend.thankyou');
    	} else {
    		return redirect('/');
    	}
    }

	//Two factor auth on web via app
	public function app_twofactorauth() {
    	if(Session::get('user_id') && Session::get('user_id') != ''){
    		$appAuth = AppTwoFactorAuth::where('user_id',Session::get('user_id'))->where('notification_send',1)->first();
    		if(!empty($appAuth)){
				return view('frontend.app_towfactorauth');
			}
			//return view('frontend.app_towfactorauth');
    	}
		return redirect('/login');
    }

	//Resend notification on app
	public function resend_app_verificarion() {
		if(Session::get('user_id') && Session::get('user_id') != ''){
			$user_devices = UserDevice::select('id','user_id','device_type','device_token')->where('user_id',Session::get('user_id'))->where('device_token','!=','')->get();
    		if(count($user_devices) > 0){
				$noti_data = [];
				$noti_data['title'] = 'Two factor authentication';
				$noti_data['body'] = 'Login is attempted from web';
				$noti_data['type'] = 'two_factor_auth';
				$noti_data['order_id'] = '';
				$noti_data['valid_until'] = 60;
				$noti_data['order'] = (object)[];
				$noti_data['is_new_order'] = false;

				$objNotification = new Notification;
				$objNotification->send_fcm_notification($user_devices,$noti_data);
			}

			$appAuth = AppTwoFactorAuth::where('user_id',Session::get('user_id'))->first();
			if(empty($appAuth)){
				$appAuth = new AppTwoFactorAuth;
				$appAuth->user_id = Session::get('user_id');
			}
			$appAuth->notification_send = 1;
			$appAuth->updated_at = Carbon::now();
			$appAuth->save();
			
			return response()->json([
				'status' => true,
				'message' => 'App verification send successfully.',
			]);
			
			
			// else{
			// 	return response()->json([
			// 		'status' => false,
			// 		'message' => 'Something goes wrong.',
			// 	]);
			// }
    	}
		return response()->json([
			'status' => false,
			'message' => 'Session timeout.',
		]);
	}

	//Verify notification
	function check_app_verificarion(Request $request){
		if(Session::get('user_id') && Session::get('user_id') != ''){
			$appAuth = AppTwoFactorAuth::where('user_id',Session::get('user_id'))->first();
			if(!empty($appAuth)){
				
				if($appAuth->notification_send == 2){
					//verify two factor auth :: login success
					$user = User::find(Session::get('user_id'));
					$redirect_url = Session::get('profileurl');
    				Session::forget('profileurl');
    				Session::forget('user_id');
					
					Auth::login($user);
					$user->last_login_at = date('Y-m-d H:i:s');
					$user->ip_adress = $_SERVER['REMOTE_ADDR'];
					$user->login_attempt = 0;
					$user->login_attempt_date =  Carbon::now();
					$user->save();

					/* clear all attempts */
					clear_two_factor_auth_attempt_details($user->id);

					$appAuth->delete();
					
					return response()->json([
						'status' => true,
						'return_url' => ($redirect_url)?$redirect_url:url('/'),
						'message' => "",
					]);
				}else if($appAuth->notification_send == 3){
					//Cancel verification from app :: redirect to login page
					$appAuth->delete();
					Session::forget('user_id');
					return response()->json([
						'status' => false,
						'message' => "Authentication failed.",
					]);
				}

				$timeDiff = $request->two_factor_timer;
				if($timeDiff <= 0){
					$appAuth->notification_send = 4;
					$appAuth->save();
					return response()->json([
						'status' => false,
						'timeout' => true,
						'message' => "",
					]);
				}else{
					return response()->json([
						'status' => false,
						'timeout' => false,
						'message' => "",
					]);
				}
			}
			//Session::forget('user_id');
		}
		//Not get device login :: redirect to login page
		return response()->json([
			'status' => false,
			'message' => "Session has beed expired, Please try again.",
		]);
	}

    public function twofactorauth() {
    	if(Session::get('user_id') && Session::get('user_id') != ''){
			$user_device_count = 0 ;
			$user = User::select('id','country_code','mobile_no','app_towfactorauth','towfactorauth')->find(Session::get('user_id'));
			
			if($user->app_towfactorauth == 1){
				//Send silent push notification for close app model
				$user_devices = UserDevice::select('id','user_id','device_type','device_token')
				->where('user_id',Session::get('user_id'))->where('device_token','!=','')->get();

				if(count($user_devices) > 0){
					$noti_data = [];
					$noti_data['title'] = 'Two factor authentication complete';
					$noti_data['body'] = 'Two factor authentication';
					$noti_data['type'] = 'two_factor_auth_complete';
					$noti_data['order_id'] = '';
					$noti_data['order'] = (object)[];
					$noti_data['is_new_order'] = false;
		
					$objNotification = new Notification;
					$objNotification->send_fcm_notification($user_devices,$noti_data,$is_silient=1);
				}

				//Remove request from app login
				AppTwoFactorAuth::where('user_id',Session::get('user_id'))->delete();
				$user_device_count = count($user_devices);
			}
    		return view('frontend.towfactorauth',compact('user','user_device_count'));
    	}else{
    		return redirect('/');
    	}
    }

    public function login_verify_towfactorauth(Request $request) {
		if (!$request->ajax()) {
			return redirect()->back();
		}
    	if(Session::get('user_id') && Session::get('user_id') != ''){

    		$user = User::find(Session::get('user_id'));

    		if(strlen($user->mobile_no) == 9){
                //$user->mobile_no = "0".$user->mobile_no;
    		}elseif(strlen($user->mobile_no) == 8){
                //$user->mobile_no = "00".$user->mobile_no;
    		}

			$check_2_factor = check_invalid_two_factor_auth_attempt($user->id);
			if($request->action_type != '3' && $check_2_factor['status'] == true) {
				return response([
					'action_type' => $request->action_type,
					'status' => false,
					'message' => $check_2_factor['message'],
				]);
			} else {
				$ip_address = get_client_ip();
				if($ip_address != 'UNKNOWN') {
					$get_attempts = new UserTwoFactorAuthDetails();
					$get_attempts->user_id = $user->id;
					$get_attempts->ip_address = $ip_address;
					$get_attempts->mobile_no = $user->country_code.$user->mobile_no;
					$get_attempts->attempts = 1;
					$get_attempts->save();
				}
			}
			

    		if($request->action_type == '1' || $request->action_type == '2'){
    			/*1- send sms ,2 - resend sms*/
    			$otp = mt_rand(1000,9999);
    			Session::put('otp',$otp);
    			$message = 'Your demo two factor verification code is : '. $otp;

    			try{
    				Twilio::message('+'.$user->country_code.$user->mobile_no, $message);

    				return response([
    					'action_type' => $request->action_type,
    					'status' => true,
    					'message' => 'SMS send successfully',
    				]);
    			}catch(\Exception $e){
    				return response([
    					'action_type' => $request->action_type,
    					'status' => false,
    					'message' => 'Enter valid mobile number',
    				]);
    			}
    		}elseif($request->action_type == '3'){
    			/*3- Verify mobile otp*/
    			if(trim($request->otp) && (Session::get('otp') == $request->otp) ){
    				Session::forget('otp');
    				Session::forget('profileurl');
    				Session::forget('user_id');
					Auth::login($user);
					$user->login_attempt = 0;
					$user->login_attempt_date =  Carbon::now();
					$user->save();
					/* clear all attempts */
					clear_two_factor_auth_attempt_details($user->id);
    				return response([
    					'action_type' => $request->action_type,
    					'status' => true,
    					'message' => 'OTP verified successfully',
    				]);
    			}else{
    				return response([
    					'action_type' => $request->action_type,
    					'status' => false,
    					'message' => 'Please enter valid OTP',
    				]);
    			}
    		}elseif($request->action_type == '4'){
    			$otp = mt_rand(1000,9999);
    			Session::put('otp',$otp);

    			$otpCode = implode(' ', str_split($otp));

    			try{
    				Twilio::call('+'.$user->country_code.$user->mobile_no, function ($voiceMessage) use ($otpCode){
    					$voiceMessage->say('This is an automated call providing you your OTP from the demo app.');
    					$voiceMessage->say('Your one time password is ' . $otpCode);
    					$voiceMessage->pause(['length' => 1]);
    					$voiceMessage->say('Your one time password is ' . $otpCode);
    					$voiceMessage->say('GoodBye');
    				});
    				return response([
    					'action_type' => $request->action_type,
    					'status' => true,
    					'message' => 'Request call successfully',
    				]);
    			}catch(\Exception $e){
    				return response([
    					'action_type' => $request->action_type,
    					'status' => false,
    					'message' => 'Enter valid mobile number',
    				]);
    			}
    		}else{
    			return response([
    				'action_type' => $request->action_type,
    				'status' => false,
    				'message' => 'Something goes wrong',
    			]);
    		}
    	}else{
    		return response([
    			'action_type' => $request->action_type,
    			'status' => false,
    			'message' => 'Something goes wrong',
    		]);
    	}
    }

    /*In My profile process*/
    public function verify_towfactorauth(Request $request) {
		if (!$request->ajax()) {
			return redirect()->back();
		}
    	if(strlen($request->mobile_no) == 9){
            //$request->mobile_no = "0".$request->mobile_no;
    	}elseif(strlen($request->mobile_no) == 8){
            //$request->mobile_no = "00".$request->mobile_no;
    	}
		/* Response function */
        $returnResponse = function($action_type = null, $status = false, $message = null) {
        	return response([
				'action_type' => $action_type,
				'status' => $status,
				'message' => ($message)? $message : "Something went wrong."
			]);
        };

		/* Check block SMS */
        if($request->action_type != '3'){
            /* Verify recaptcha */
            if($request->action_type == '1'){
                $validator = Validator::make($request->all(), [
                    'g-recaptcha-response' => 'required|recaptcha'
                ],[
                    'g-recaptcha-response.recaptcha' => 'Captcha verification failed',
                    'g-recaptcha-response.required' => 'Please complete the captcha'
                ]);
                if($validator->fails()) {
                    return $returnResponse($request->action_type, false, $validator->errors()->first());
                }
            }
		}

		$mobileExists = User::select('id')->where('mobile_no',$request->mobile_no)->where('country_code',$request->country_code)->count();
    	if($mobileExists > 0){
            return $returnResponse($request->action_type,false,'Mobile number already exists.');
    	}

		/* Check user session data */
        $ip_address = get_client_ip();
		$check_2_factor = check_invalid_two_factor_auth_attempt(Auth::user()->id);
		if($request->action_type != '3' && $check_2_factor['status'] == true) {
			return response([
				'action_type' => $request->action_type,
				'status' => false,
				'message' => $check_2_factor['message'],
			]);
		} else {
			if($ip_address != 'UNKNOWN') {
				$get_attempts = new UserTwoFactorAuthDetails();
				$get_attempts->user_id = Auth::user()->id;
				$get_attempts->ip_address = $ip_address;
				$get_attempts->mobile_no = $request->country_code.$request->mobile_no;
				$get_attempts->attempts = 1;
				$get_attempts->save();
			}
		}

		$session_data = Session::get('custom_security_data');
        if(is_null($session_data)){
            return $returnResponse($request->action_type);
        }
        /* Verify IP Address */
        $verification_response = verifyIpForSMS($session_data['ip_address'],$request->country_code);
        if($verification_response['status'] == false){
            return $returnResponse($request->action_type,false, $verification_response['message']);
        }
		/* Check block SMS */
        if($request->action_type != '3'){
            $sms_history = SmsHistory::store_sms_history($request->country_code);
            if($sms_history == false){
                return $returnResponse($request->action_type, false, 'Something went wrong. Please try after sometime.');
            }
        }

    	if($request->action_type == '1' || $request->action_type == '2'){
    		/*1- send sms ,2 - resend sms*/
    		$otp = mt_rand(1000,9999);
    		Session::put('otp',$otp);
    		$message = 'Your demo two factor verification code is : '. $otp;

    		try{
    			Twilio::message('+'.$request->country_code.$request->mobile_no, $message);
				return $returnResponse($request->action_type,true,'SMS send successfully');
    		}catch(\Exception $e){
				return $returnResponse($request->action_type,false,'Enter valid mobile number');
    		}
    	}elseif($request->action_type == '3'){
    		/*3- Verify mobile otp*/
    		if(trim($request->otp) && (Session::get('otp') == $request->otp) ){
    			Session::flash('vaidOtp','OTP verified successfully');
    			Session::forget('otp');

    			$user = User::find(Auth::user()->id);
    			$user->country_code = $request->country_code;
    			$user->mobile_no = $request->mobile_no;
    			$user->is_verify_towfactorauth = 1;
    			$user->towfactorauth = 1;
    			$user->save();
				/* clear all attempts */
				clear_two_factor_auth_attempt_details($user->id);
				return $returnResponse($request->action_type,true,'OTP verified successfully');
    		}else{
				return $returnResponse($request->action_type,false,'Please enter valid OTP');
    		}
    	}elseif($request->action_type == '4'){
    		$otp = mt_rand(1000,9999);
    		Session::put('otp',$otp);

    		$otpCode = implode(' ', str_split($otp));

    		try{
    			Twilio::call('+'.$request->country_code.$request->mobile_no, function ($voiceMessage) use ($otpCode){
    				$voiceMessage->say('This is an automated call providing you your OTP from the demo app.');
    				$voiceMessage->say('Your one time password is ' . $otpCode);
    				$voiceMessage->pause(['length' => 1]);
    				$voiceMessage->say('Your one time password is ' . $otpCode);
    				$voiceMessage->say('GoodBye');
    			});
				return $returnResponse($request->action_type,true,'Request call successfully');
    		}catch(\Exception $e){
				return $returnResponse($request->action_type,false,'Enter valid mobile number');
    		}
    	}else{
			return $returnResponse($request->action_type,false,'Something goes wrong');
    	}
    }

    public function update_two_factor(Request $request){
    	if(Auth::check()){
    		$user = User::select('id','towfactorauth')->find(Auth::user()->id);
    		$user->towfactorauth = $request->towfactorauth;
    		$user->save();
    		return Response()->json(["success" => true]);
    	}else{
    		return Response()->json(["success" => false]);
    	}
    }

	public function update_app_two_factor(Request $request){
    	if(Auth::check()){
    		$user = User::select('id','app_towfactorauth')->find(Auth::user()->id);
    		$user->app_towfactorauth = $request->app_towfactorauth;
    		$user->save();
    		return Response()->json(["success" => true]);
    	}else{
    		return Response()->json(["success" => false]);
    	}
    }

    public function topfoodblogger() {

        $service_ids = ['139','20131','8106','7592','17295'];
        $ids_ordered = implode(',', $service_ids);
       
        $ServiceSlider1 = Service::where('is_approved',1)
        ->whereHas('user', function($query) {
            $query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->where('soft_ban', 0);
        })
        ->whereIn('status', ['active'])
		->whereIn('id', $service_ids)
		->where('is_private', 0)
        ->where('is_custom_order', 0)
        ->where('is_job',0)
        ->orderByRaw("FIELD(id, $ids_ordered)")
        ->get();

        $service_ids = ['1352','1365','140','278','232'];
        $ids_ordered = implode(',', $service_ids);

        $ServiceSlider2 = Service::where('is_approved',1)
        ->whereHas('user', function($query) {
            $query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->where('soft_ban', 0);
		})
		->where('is_private', 0)
        ->whereIn('status', ['active'])
        ->whereIn('id', $service_ids)
        ->where('is_custom_order', 0)
        ->where('is_job',0)
        ->orderByRaw("FIELD(id, $ids_ordered)")
        ->get();

        $service_ids = ['27570','12981','17236','18351','12222'];
        $ids_ordered = implode(',', $service_ids);

        $ServiceSlider3 = Service::where('is_approved',1)
        ->whereHas('user', function($query) {
            $query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->where('soft_ban', 0);
        })
        ->whereIn('status', ['active'])
        ->whereIn('id', $service_ids)
        ->where('is_custom_order', 0)
		->where('is_job',0)
		->where('is_private', 0)
        ->orderByRaw("FIELD(id, $ids_ordered)")
        ->get();    

        $service_ids = ['24615','21871','26797','18270','1467'];
        $ids_ordered = implode(',', $service_ids);

        $ServiceSlider4 = Service::where('is_approved',1)
        ->whereHas('user', function($query) {
            $query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->where('soft_ban', 0);
        })
        ->whereIn('status', ['active'])
        ->whereIn('id', $service_ids)
        ->where('is_custom_order', 0)
		->where('is_job',0)
		->where('is_private', 0)
        ->orderByRaw("FIELD(id, $ids_ordered)")
        ->get();    


        $service_ids = ['9879','10982','18985','417'];
        $ids_ordered = implode(',', $service_ids);

        $ServiceSlider5 = Service::where('is_approved',1)
        ->whereHas('user', function($query) {
            $query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0)->where('soft_ban', 0);
		})
		->where('is_private', 0)
        ->whereIn('status', ['active'])
        ->whereIn('id', $service_ids)
        ->where('is_custom_order', 0)
        ->where('is_job',0)
        ->orderByRaw("FIELD(id, $ids_ordered)")
        ->get();    

        return view('frontend.footerlink.topfoodblogger',compact('ServiceSlider1','ServiceSlider2','ServiceSlider3','ServiceSlider4','ServiceSlider5'));
    }   

    public function terms() {
    	return view('frontend.footerlink.terms');
    }

    public function accept_tearms(Request $request) {
    	$user = Auth::user();
    	$user->terms_privacy = date('Y-m-d H:i:s');
    	$user->save();
    	return redirect()->back();
    }

    public function submit_new_features(Request $request) {
        $user = Auth::user();
        $user->new_features = date('Y-m-d H:i:s');
        $user->save();
        return response(['success'=>true]);
    }

    public function privacy() {
    	return view('frontend.footerlink.privacy');
    }

    public function organic_searche() {
    	return view('frontend.footerlink.organic_searche');
    }

    public function retail_outsourcing() {
    	return view('frontend.footerlink.retail_outsourcing');
    }

    public function useralreadyexist(Request $request) {
    	/* Check user In Block List */
    	/*$usernameblocked = UserBlockList::select('username')->get();
    	if (!empty($usernameblocked)) {
    		foreach ($usernameblocked as $key => $value) {
    			if (strpos(strtolower($request->username), strtolower($value->username)) !== false) {
    				return Response()->json(["valid" => false]);
    			}
    		}
    	}*/
		$usernameblocked = UserBlockList::select('id')->where('username',$request->username)->count();
		if($usernameblocked > 0){
			return Response()->json(["valid" => false]);
		}

    	$username = User::where('username', '=', $request->username)->where("id", "!=", $request->user_id)->first();
    	if ($username === null) {
    		$isValid = true;
    	} else {
    		$isValid = false;
    	}
    	return Response()->json(["valid" => $isValid]);
    }

    function emailalreadyexist(Request $request){

        /*begin : check for block emails*/
        $disposable_list = block_email_list();
        $domain = substr(strrchr($request->email, "@"), 1);
        if(in_array($domain, $disposable_list)){ 
            return Response()->json(["valid" => false,'message'=>'Invalid domain.']);
            exit();
        }
        /*end : check for block emails*/

        $emailExists = User::where('email', '=', $request->email)->where("id", "!=", $request->user_id)->first();
        if ($emailExists === null) {
            $isValid = true;
            return Response()->json(["valid" => $isValid,'message'=>'']);
        } else {
            $isValid = false;
            return Response()->json(["valid" => $isValid,'message'=>'Email already exists.']);
        }
    }

    public function processdata() {

    }

    public function reactivation(Request $request) {
    	if (Session::has('deactivated_userid')) {
    		if ($request->input()) {
    			$uid = Session::get('deactivated_userid');
    			$isRequested = ReactivationRequest::where(['uid' => $uid, 'status' => 'requested'])->first();
    			if ($isRequested) {
    				Session::flash('tostError', 'You have already requested.');
    			} else {
    				$seller = User::where(['id' => $uid, 'is_active' => 0])->first();
    				if (!empty($seller)) {
    					$model = new ReactivationRequest;
    					$model->uid = $uid;
    					$model->reason = $request->reason;
    					$model->status = 'requested';
    					$model->save();

    					$seller_data = [
    						'username' => $seller->username,
    						'messageDetails' => $request->reason,
    					];

    					/* Send mail to admin */

    					\Mail::send('frontend.emails.v1.account_reactivation', $seller_data, function ($message) use ($seller) {
    						$message->to([env('SUPPORT_EMAIL')])
    						->subject('demo - New Request to reactivate account');
    					});

    					Session::flash('tostSuccess', 'Thank you, we will review your account and get back to you shortly.');
    				} else {
    					Session::flash('tostError', 'Your account already active.');
    				}
    			}
    			return redirect('/');
    		} else {
    			return view('frontend.reactivation');
    		}
    	} else {
    		return redirect('/');
    	}
    }

    public function UpdateHeaderData(Request $request) {
    	if (Auth::check()) {
    		$updateHerader = new Order;
    		$updateHerader->updateHeader();
    	}
    }

    public function endVacation(Request $request) {
    	$user = User::where('id', Auth::user()->id)->first();
    	$user->vacation_mode = 0;
    	$user->save();
    	return redirect()->back();
    }

    public function becomePremiumSeller(){
    	$userId=Auth::user()->id;
    	$subscribeUser = SubscribeUser::where('user_id',$userId)->first();
    	$subscription = Subscription::find(1);
    	if($subscribeUser === null)
    	{

    		$newUserSub=1;
    		return view('frontend.become_premium_seller',compact('newUserSub','subscription'));
    	}
    	else
    	{

    		if(Auth::user()->is_premium_seller() == false)
    		{
                $newUserSub=0;
    			return view('frontend.become_premium_seller',compact('subscription','newUserSub'));
    		}
    		else{
    			return view('frontend.my_premium_subscription',compact('subscription')); 
    		}
    	}
    }

    public function myPremiumSubscription(){
    	$userId=Auth::user()->id;
    	$subscribeUser = SubscribeUser::where('user_id',$userId)->first();
    	$subscription = Subscription::find(1);
    	if($subscribeUser === null)
    	{
    		$newUserSub=1;
    		return view('frontend.become_premium_seller',compact('newUserSub','subscription'));
    	}
    	else
    	{
    		if(Auth::user()->is_premium_seller() == false){
                $newUserSub=0;
    			return view('frontend.become_premium_seller',compact('subscription','newUserSub'));
    		}
    		else{
    			return view('frontend.my_premium_subscription',compact('subscription')); 
    		}
    	}

	}
	
	public function show_premium_payment(Request $request) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('my_premium_subscription')->with('errorFails', get_user_softban_message());
		}

		$subscription = Subscription::find(1);
		if(Auth::user()->earning == 0){
			$fromWalletAmount = 0;
		}elseif(Auth::user()->earning >= $subscription->price){
			$fromWalletAmount = $subscription->price;
		}else{
			$fromWalletAmount = Auth::user()->earning;
		}
		return view('frontend.premium_seller_payment_details',compact('subscription','fromWalletAmount'));
	}

	public function show_premium_payment_options(Request $request) {
		//Admin can make user to soft ban , so user can't place any orders
		if(User::is_soft_ban() == 1){
			return redirect()->route('my_premium_subscription')->with('errorFails', get_user_softban_message());
		}
		$subscription = Subscription::find(1);
		if(Auth::user()->earning == 0){
			$fromWalletAmount = 0;
		}elseif(Auth::user()->earning >= $subscription->price){
			$fromWalletAmount = $subscription->price;
		}else{
			$fromWalletAmount = Auth::user()->earning;
		}
		return view('frontend.premium_seller_payment_options',compact('subscription','fromWalletAmount'));
	}

    public function update_confetti_effect(Request $request){
        $NewOrder = Notification::with('order', 'notifyby')->where(['type' => 'new_order', 'notify_to' => Auth::user()->id, 'is_read' => 0])->orderBy('id', 'desc')->first();
        if(!empty($NewOrder)){
            $NewOrder->is_confetti_effect = 1;
            $NewOrder->save();
        }
        return response(['success'=>true]);

	}
	
	/* store subscriber id for user to send push notification */
	public function store_subscriber_id(Request $request) {
		if(!$request->filled('subscriber_id')) {
			return response()->json(['status' => 'error']);
		}
		AimtellSubscriber::where('subscriber_id',$request->subscriber_id)->delete();
		$user = new AimtellSubscriber;
		$user->user_id = Auth::id();
		$user->subscriber_id = $request->subscriber_id;
		$user->save();
		Session::put('subscriber_id_updated','true');
		Session::put('subscriber_id',$user->subscriber_id);
	
		return response()->json(['status' => 'success']);
	}

    public function redirectNotification(Request $request){
        $link = $request->link;

        $link = openssl_decrypt(base64_decode($link),config('services.encryption.type'),config('services.encryption.secret'));
        if(!$link){
            return redirect('/');
        }

        return view('redirect_notification',compact('link'));
       // return redirect($link);
	}

	public function check_authentication(Request $request) {
        if(Auth::check()) {
            return response()->json(['status'=>'login']);
        } else {
            return response()->json(['status'=>'logout']);
        }
    }

	public function change_web_theme(Request $request) {
		$status = 'error';
		if($request->filled('web_dark_mode')) {
			User::where('id',Auth::id())->update(['web_dark_mode' => $request->web_dark_mode, 'dark_mode' => $request->web_dark_mode]);
			$status = 'success';
		}
		return response()->json(['status' => $status]);
	}
	
	public function apply_hidden_pizza(Request $request) {
		$status = 'error';
		$user_id = $this->uid;
		$today = Carbon::today()->format('Y-m-d');
		$check_for_applied = PizzaAppliedHistory::whereDate('date',$today)->where('user_id',0)->first();
		if($this->is_user_applicable_for_pizza($check_for_applied,$request->all())) { //means still not applied on any one
			$check_for_applied->user_id = $user_id;
			//$check_for_applied->date = Carbon::today()->format('Y-m-d H:i:s');
			$check_for_applied->save();

			//give promotional amount in user's promotional fund
			$user = User::select('id','promotional_fund')->find($user_id);
			if(!is_null($user)) {
				$user->promotional_fund = $user->promotional_fund + env('HIDDEN_PIZZA_AMOUNT');
				$user->save();
			}
			$status = 'success';
		} else {
			$status = 'error';
		}
		return response()->json(['status' => $status]);
	}

	function is_user_applicable_for_pizza($db_page,$input) {
		$return = false;
		$pizza_setting = GeneralSetting::where('settingkey','hidden_pizza')->first();
		$input_token = $input['key'];

		if(!is_null($db_page) && $input_token == $db_page->verification_token && $pizza_setting->settingvalue == '0') {
			if($db_page->pizza_page_id == 2 || $db_page->pizza_page_id == 3) {
				/* if (strpos($db_page->pizza_page_url, 'categories') !== false) {
					$return = true;
				} */
				if($db_page->pizza_page_id == 2 && $input['categoryid'] != '0') {
					$category = Category::select('id','seo_url')->where('seo_url','!=','by-us-for-us')->where('id',$input['categoryid'])->first();
					if(!is_null($category) && $db_page->pizza_page_url == route('services_view',$category->seo_url)) {
						$return = true;
					}
				} else if($db_page->pizza_page_id == 3 && $input['categoryid'] != '0' && $input['subcategoryid'] != '0') {
					$category = Category::select('id','seo_url')->where('seo_url','!=','by-us-for-us')->where('id',$input['categoryid'])->first();
					$sub_category = Subcategory::select('id','seo_url')->where('id',$input['subcategoryid'])->first();
					if(!is_null($category) && !is_null($sub_category) && $db_page->pizza_page_url == route('services_view',[$category->seo_url,$sub_category->seo_url])) {
						$return = true;
					}
				}
			} else if($db_page->pizza_page_url == \URL::previous()) {
				$return = true;
			}
		}
		return $return;
	}

	public function can_show_pizza_for_category(Request $request) {
		$cat = $request->cat ?? 1;
		$sub_cat = $request->sub_cat ?? "";
		$today = Carbon::today()->format('Y-m-d');

		$return = false;
		$image_url = '';

		$check_for_applied = PizzaAppliedHistory::whereDate('date',$today)->where('user_id',0)->first();
		if(!is_null($check_for_applied)) {
			if(($check_for_applied->pizza_page_id == 2 || $check_for_applied->demoPage->slug == 'category_page') && $sub_cat == '') {
				$category = Category::select('id','seo_url')->where('seo_url','!=','by-us-for-us')->where('id',$cat)->first();
				if($check_for_applied->pizza_page_url == route('services_view',[$category->seo_url])) {
					$return = true;
					$image_url = url('public/frontend/assets/img/pizza_hidden.png');
				}
			} else if($check_for_applied->pizza_page_id == 3 || $check_for_applied->demoPage->slug == 'subcategory_page') {
				$category = Category::select('id','seo_url')->where('seo_url','!=','by-us-for-us')->where('id',$cat)->first();
				$sub_category = Subcategory::select('id','seo_url')->where('id',$sub_cat)->first();
				if($check_for_applied->pizza_page_url == route('services_view',[$category->seo_url,$sub_category->seo_url])) {
					$return = true;
					$image_url = url('public/frontend/assets/img/pizza_hidden.png');
				}
			}
		}
		return response()->json(['status' => $return, 'image_url' => $image_url]);
	}

	public function notification_mark_as_read(Request $request) {
		if(!$request->filled('id')) {
			return response()->json(['status' => 'error']);
		}
		Notification::where('id',$request->id)
						->where('notify_to',Auth::user()->id)
						->where('is_read',0)
						->update(['is_read'=>1]);
		return response()->json(['status' => 'success']);
	}

	public function notification_clear(Request $request) {
		if(!$request->filled('id')) {
			return response()->json(['status' => 'error']);
		}
		Notification::where('id',$request->id)
						->where('notify_to',Auth::user()->id)
						->where('is_delete',0)
						->update(['is_delete'=>1]);
		return response()->json(['status' => 'success']);
	}

	public function all_notification_mark_as_read(Request $request) {
		Notification::where('notify_to',Auth::user()->id)
						->where('is_read',0)
						->update(['is_read'=>1]);
		return response()->json(['status' => 'success']);
	}

	public function all_notification_clear(Request $request) {
		Notification::where('notify_to',Auth::user()->id)
						->where('is_delete',0)
						->update(['is_delete'=>1]);
		return response()->json(['status' => 'success']);
	}

	public function supportLogin(Request $request,$support_token){
		//$referer = request()->headers->get('referer');
        $userObject = User::getDecryptedId($support_token);
        if($userObject){
            $userObject = json_decode($userObject);
            if(isset($userObject->timestamp) &&  isset($userObject->ip) && isset($userObject->iv) && isset($userObject->i) && isset($userObject->e) && $userObject->iv == 'u08jhsadlh65sdggdijjf63ddfnnxcv'){
                $time_diff_minutes = (time() - $userObject->timestamp) / 60;
				if($time_diff_minutes <= 15){
				//if($userObject->ip == $_SERVER['REMOTE_ADDR'] && $time_diff_minutes <= 15){
					$user = User::where('email',$userObject->e)->where('id',$userObject->i)->where('status',1)->where('is_delete',0)->where('parent_id',0)->first();
					if(!empty($user)){

						//Logout old user if login
						if(Auth::check()){
							/* store logout history in database */
							$old_history = UserLoginByAdminHistory::where('user_id',Auth::user()->id)
												->where('logout_at',null)
												->select('id','logout_at')
												->first();
							if(!is_null($old_history)) {
								$old_history->logout_at = Carbon::now()->format('Y-m-d H:i:s');
								$old_history->save();
							}

							Auth::logout();
							$request->session()->flush();
							$request->session()->regenerate();
							session_destroy();
						}

						//Login requested user
						/* store login history in database */
						$new_history = new UserLoginByAdminHistory;
						$new_history->user_id = $user->id;
						$new_history->admin_id = $userObject->admin_id;
						$new_history->login_at = Carbon::now()->format('Y-m-d H:i:s');
						$new_history->save();

						Auth::login($user);
						$request->session()->regenerate();
						$_SESSION["username"] = md5($user['username']);
						Session::put('login_from_admin','yes');
						Session::put('subscriber_id_updated','false');
						return redirect('/');
					}
				}
            }
        }
        return redirect('login');
    }
}
