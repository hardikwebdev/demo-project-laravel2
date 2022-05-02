<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Service;
use App\Influencer;
use App\Category;
use App\Subcategory;
use App\UserLanguage;
use App\BoostedServicesOrder;
use App\InfluencerService;
use App\GeneralSetting;
use Auth;
use DB;
use Cookie;

class InfluencerController extends Controller
{	
    private $lifetime = 24*60*7;

    public function influencer(Request $request){
    	$influencers = Influencer::with('influencer_services')    
        ->orderBy('id','desc')
        ->get();

        $influencer_services = InfluencerService::pluck('service_id')->toArray();

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
            $query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
        });

        if (Auth::check()) {
            $featured = $featured->with('favorite');
        }
        $featured = $featured->where('status', "active")->first();

        $catid = Subcategory::where('id', $subcategory_id)->select('category_id')->get()->toArray();

        $current_category = $category_id;

        //\DB::enableQueryLog();
        
        $rating_count = DB::Raw('ROUND(services.service_rating, 0) As service_round_rating');

        $Service = Service::select('services.*', 'service_plan.price', $rating_count)->with('user', 'category', 'images', 'basic_plans');
        
        $Service = $Service->whereIn('services.id',$influencer_services);

        /* selected user services */
        // $Service = $Service->Statusof('service');

        if (Auth::check()) {
            $Service = $Service->with('favorite');
        }

        /* Remove deleted user services */
        $Service = $Service->whereHas('user', function($query) {
            $query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
        });

        if ($category_id && $category_id != '') {
            $Service = $Service->where('services.category_id', $category_id);
        }

        if ($subcategory_id && $subcategory_id != '') {
            $Service = $Service->where('services.subcategory_id', $subcategory_id);
        }

        if ($request->get('min_price') != "") {
            $min_price = $request->get('min_price');
            $Service = $Service->whereHas('basic_plans', function($query)use($min_price) {
                $query->select('id')->where('price', '>=', $min_price);
            });
        }

        if ($request->get('max_price') != "") {
            $max_price = $request->get('max_price');
            $Service = $Service->whereHas('basic_plans', function($query)use($max_price) {
                $query->select('id')->where('price', '<=', $max_price);
            });
        }

        if($request->segment(1) == 'recently-uploaded') {
            $Service = $Service->orderBy('services.created_at', 'desc');
        } else {
            $order_by = $request->get('sort_by');
            if ($order_by && $order_by != '') {
                if ($order_by == 'top_rated') {
                    /*$Service = $Service->orderBy('rating_count', 'desc');*/
                    $Service = $Service->orderBy('service_round_rating', 'desc');
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
                /*$Service = $Service->orderBy('rating_count', 'desc');*/
                $Service = $Service->orderBy('service_round_rating', 'desc');
                $Service = $Service->orderBy('services.total_review_count', 'desc');
            }
        }

       

        $Service = $Service->join('service_plan', 'service_plan.service_id', '=', 'services.id')
        ->where('service_plan.plan_type', 'basic')->where('services.is_delete',0)->paginate(21);

        //dd(\DB::getQueryLog($Service));
        
        $categories = Category::with('subcategory')
        ->where('seo_url','!=','by-us-for-us')
        ->withCount(['services' => function ($query) {
            $query->where('status', '=', 'active');
            $query = $query->whereHas('user', function($user) {
                $user->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
            });
        }])
        ->get();

        
        
        $minPrice = DB::table('service_plan')->min('price');
        $maxPrice = DB::table('service_plan')->max('price');
        $languages = UserLanguage::select('language', 'id')->groupBy('language')->get();

        $subcategories = Subcategory::where('category_id', isset($catid[0]) ? $catid[0]['category_id'] : '0')->get();

        $sponseredService = null;
        

        if ($request->ajax()) {
            $selectedCategory = Category::select('category_name','display_title','category_description')->where('id',$current_category)->first();
			$filterResponse['html_response'] = view('frontend.service.filterservices', compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category', 'featured', 'sponseredService'))->render();
			$filterResponse['category_name'] = (!empty($selectedCategory))?$selectedCategory->category_name:'';
			$filterResponse['display_title'] = (!empty($selectedCategory))?$selectedCategory->display_title:'';
			$filterResponse['category_description'] =  (!empty($selectedCategory))?$selectedCategory->category_description:'';
			$filterResponse = mb_convert_encoding($filterResponse, 'UTF-8', 'UTF-8');
			return $filterResponse;
        }

        $bannerGeneral = GeneralSetting::whereIn('settingkey',['influencer_banner_img','influencer_title_bgcolor','influencer_title_textcolor','influencer_text_color','influencer_title_text','influencer_title_font_size'])->get();

    	return view('influencer',compact('influencers','bannerGeneral','Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category', 'featured', 'sponseredService'));
    }

    public function influencersingle(Request $request,$slug){
        
        $q = $request->get('q');
        
        $influencer = Influencer::with('influencer_services')
            ->where('slug',$slug)
            ->first(); 

        $influencer_services = InfluencerService::where('influencer_id',$influencer->id)->pluck('service_id')->toArray();
        
        if(!$influencer){
            return redirect('404');
        }

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
            $query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
        });

        if (Auth::check()) {
            $featured = $featured->with('favorite');
        }
        $featured = $featured->where('status', "active")->first();

        $catid = Subcategory::where('id', $subcategory_id)->select('category_id')->get()->toArray();

        $current_category = $category_id;
        
        $rating_count = DB::Raw('ROUND(services.service_rating, 0) As service_round_rating');

        $Service = Service::select('services.*', 'service_plan.price', $rating_count)->with('user', 'category', 'images', 'basic_plans');
        
        $Service = $Service->whereIn('services.id',$influencer_services);

        /* selected user services */
        $Service = $Service->Statusof('service');

        if (Auth::check()) {
            $Service = $Service->with('favorite');
        }

        if ($category_id && $category_id != '') {
            $Service = $Service->where('services.category_id', $category_id);
        }

        if ($subcategory_id && $subcategory_id != '') {
            $Service = $Service->where('services.subcategory_id', $subcategory_id);
        }

        if ($request->get('min_price') != "") {
            $min_price = $request->get('min_price');
            $Service = $Service->whereHas('basic_plans', function($query)use($min_price) {
                $query->select('id')->where('price', '>=', $min_price);
            });
        }

        if ($request->get('max_price') != "") {
            $max_price = $request->get('max_price');
            $Service = $Service->whereHas('basic_plans', function($query)use($max_price) {
                $query->select('id')->where('price', '<=', $max_price);
            });
        }

        if($request->segment(1) == 'recently-uploaded') {
            $Service = $Service->orderBy('services.created_at', 'desc');
        } else {
            $order_by = $request->get('sort_by');
            if ($order_by && $order_by != '') {
                if ($order_by == 'top_rated') {
                    /*$Service = $Service->orderBy('rating_count', 'desc');*/
                    $Service = $Service->orderBy('service_round_rating', 'desc');
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
                /*$Service = $Service->orderBy('rating_count', 'desc');*/
                $Service = $Service->orderBy('service_round_rating', 'desc');
                $Service = $Service->orderBy('services.total_review_count', 'desc');
            }
        }

        $Service = $Service->join('service_plan', 'service_plan.service_id', '=', 'services.id')
        ->where('service_plan.plan_type', 'basic')->where('services.is_delete',0)->paginate(21);

        $categories = Category::with('subcategory')
        ->where('seo_url','!=','by-us-for-us')
        ->withCount(['services' => function ($query) {
            $query->where('status', '=', 'active');
            $query = $query->whereHas('user', function($user) {
                $user->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
            });
        }])
        ->get();
        
        $minPrice = DB::table('service_plan')->min('price');
        $maxPrice = DB::table('service_plan')->max('price');
        $languages = UserLanguage::select('language', 'id')->groupBy('language')->get();

        $subcategories = Subcategory::where('category_id', isset($catid[0]) ? $catid[0]['category_id'] : '0')->get();

        $sponseredService = null;
        

        if ($request->ajax()) {
            $selectedCategory = Category::select('category_name','display_title','category_description')->where('id',$current_category)->first();
			$filterResponse['html_response'] = view('frontend.service.filterservices', compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category', 'featured', 'sponseredService'))->render();
			$filterResponse['category_name'] = (!empty($selectedCategory))?$selectedCategory->category_name:'';
			$filterResponse['display_title'] = (!empty($selectedCategory))?$selectedCategory->display_title:'';
			$filterResponse['category_description'] =  (!empty($selectedCategory))?$selectedCategory->category_description:'';
			$filterResponse = mb_convert_encoding($filterResponse, 'UTF-8', 'UTF-8');
			return $filterResponse;
        }

        $bannerGeneral = GeneralSetting::whereIn('settingkey',['single_influencer_text_color','single_influencer_banner_img'])->get();
        
        return view('influencersingle',compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category', 'featured', 'sponseredService','getCategoryId','getSubCategoryId','defaultSubcatId','bannerGeneral','influencer'));
    }

    public function influencer_affiliate_redirect(Request $request,$influencer_slug='',$affiliate_id = "")
	{
        //closed this functionality
        return redirect()->back();
        $influencer = Influencer::where('slug',$influencer_slug)->first();
        $affiliate_type_cookie = Cookie("affiliate_type","influencer", $this->lifetime);
        $influencer_id_cookie = Cookie("influencer_id",$influencer->secret, $this->lifetime);
        if(empty(Cookie::get("affiliate_id")))
        {
            
            $affiliate_cookie = Cookie("affiliate_id",$affiliate_id, $this->lifetime);

            return redirect()->route('influencerSingle',$influencer->slug)->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie)->Cookie($influencer_id_cookie);
        }
        else
        {
            if(Cookie::get('affiliate_id') != $affiliate_id)
            {
                $affiliate_cookie = Cookie("affiliate_id",$affiliate_id, $this->lifetime);

                return redirect()->route('influencerSingle',$influencer->slug)->Cookie($affiliate_cookie)->Cookie($affiliate_type_cookie)->Cookie($influencer_id_cookie);
            }
        }
        return redirect()->route('influencerSingle',$influencer->slug);
    }
}
