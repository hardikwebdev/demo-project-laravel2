<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Jenssegers\Agent\Agent;
use Carbon\Carbon;
use Validator;
use Thumbnail;
use Session;
use Auth;
use AWS;
use DB;

/* Models */
use App\SellerCategories;
use App\SellerAnalytic;
use App\BundleService;
use App\ServiceMedia;
use App\SaveTemplate;
use App\ServicePlan;
use App\Subcategory;
use App\Influencer;
use App\Category;
use App\Setting;
use App\Order;
use App\User;
use App\Cart;
use App\Models\Admin;

use App\Service;
use App\CourseDetail;
use App\ContentMedia;
use App\UserLanguage;
use App\CourseSection;
use App\UserSearchTerm;
use App\GeneralSetting;
use App\DownloadableContent;
use App\UserSearchCategory;
use App\LearnCourseContent;

/********************Course Approval process
Course - is_approved = 1/0 - that will change in each update of course by seller
Section/Content - is_approve = 1 that section/content only showing to buyer, so after is_approve set 1 it never change to 0
**********************************************/

class CourseController extends Controller
{
    /* user parent id */
    private $uid; 
    private $limit; 
	public function __construct(){
		$this->middleware(function ($request, $next) {
			if(Auth::check()) {
				$this->uid = Auth::user()->id;
				if(Auth::user()->parent_id != 0){
					$this->uid = Auth::user()->parent_id;
				}
			}
            $this->limit = 10;
			return $next($request);
        });
    }

    /* Seller Course */
    public function index(Request $request){
		$uid = $this->uid;
        $Courses = Service::withoutGlobalScope('is_course')
		->where('uid',$uid);
		
		/* Filter */ 
		if ($request->has('status') && $request->status != null) {
			$Courses = $Courses->where(['status' => $request->status]);
		}
		if($request->search != null){
			$Courses = $Courses->where(function($query) use ($request)  {
				$query->where('title','LIKE', '%' . $request->search . '%')
				->orwhere('subtitle','LIKE', '%' . $request->search . '%')
				->orwhere('descriptions','LIKE', '%' . $request->search . '%');
			});
		}
		/* END Filter */

		$Courses = $Courses->where('is_course',1)
		->where('is_custom_order', 0)
		->where('is_job',0)
		->where('is_delete',0)
		->orderBy('id', 'desc')
		->paginate($this->limit)
		->appends($request->all());

        return view('frontend.course.index',compact('Courses'));
    }

    /* Create Course */
    public function overview(Request $request){
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
        $Category = Category::withoutGlobalScope('type')->where('type',1)->pluck('category_name', 'id')->toArray();

		/* Store Course */
		if($request->isMethod('post')){
			
			/* Check Validation */

			if(Auth::user()->is_course_training_account() == false){
				$validator = Validator::make($request->all(), [
					'title' => 'required|max:255',
					'subtitle' => 'max:255',
					'category_id' => 'required',
					'subcategory_id' => 'required',
					'price' => 'required|numeric|min:'.env('MINIMUM_SERVICE_PRICE'),
					'monthly_price' => 'numeric|min:'.env('MINIMUM_SERVICE_PRICE'),
				]);
			}else{
				$validator = Validator::make($request->all(), [
					'title' => 'required|max:255',
					'subtitle' => 'max:255',
					'category_id' => 'required',
					'subcategory_id' => 'required',
				]);
			}

			if ($validator->fails()) {
				return redirect()->back()->withErrors($validator)->withInput();
			}

			$uid = $this->uid;
			$input = $request->input();

			$input['is_course'] = 1;
			$input['is_affiliate_link'] = 0;
			$input['last_updated_by'] = Auth::user()->id;
			$input['uid'] = $uid;
			unset($input['_token']);
			unset($input['price']);
			unset($input['upload_profile']);
			unset($input['upload_image']);
			unset($input['monthly_price']);
			unset($input['is_monthly_course']);

			$is_monthly_course = 0;
			if(Auth::user()->is_premium_seller($uid) == true){

				if($request->filled('is_affiliate_link')){
					$input['is_affiliate_link'] = 1;
					$updUser=User::select('id','is_affiliate_service')->find($uid);
					$updUser->is_affiliate_service = 1;
					$updUser->save();
				}

				if(Auth::user()->is_course_training_account() == false && $request->is_monthly_course == 1){
					$is_monthly_course = 1;
				}
			}
			
			/* Add SEO URL */
			$input['seo_url'] = Service::generate_seo_slug($input['title']);
			$input['last_updated_on'] = Carbon::now()->format('Y-m-d H:i:s');

			/* ---- store Course ---- */
			$course_id = Service::insertGetId($input);

			/* Course Lifetime Plan */
			$CoursePlan = new ServicePlan;
			$CoursePlan->service_id = $course_id;
			$CoursePlan->plan_type = 'lifetime_access';
			$CoursePlan->package_name = 'Lifetime Access';
			if(isset($request->price)){
				$CoursePlan->price = $request->price;
			}
			
			$CoursePlan->save();

			/* Course Monthly Plan */
			$CoursePlan = new ServicePlan;
			$CoursePlan->service_id = $course_id;
			$CoursePlan->plan_type = 'monthly_access';
			$CoursePlan->package_name = 'Monthly Access';
			if(isset($request->monthly_price)){
				$CoursePlan->price = $request->monthly_price;
			}
			$CoursePlan->save();

			// Course Details
			$courseDetail = new CourseDetail;
			$courseDetail->course_id = $course_id;
			$courseDetail->is_monthly_course = $is_monthly_course;
			$courseDetail->save();

			/* ---- store categories ---- */
			$cat = [];
			$cat_array = [
				'uid' => $this->uid,
				'service_id' => $course_id,
				'category_id' => $input['category_id'],
				'sub_category_id' => $input['subcategory_id'],
				'is_default' => true
			];
			array_push($cat, $cat_array);
			SellerCategories::insert($cat);

			/* Upload course image */
			$temp_media = Session::get('course_profile');
            $imageKey = "";
            $thumb_imageKey = "";
            $result_amazonS3 = array();
            $result_amazonS3_thumbnail = array();

            if(!empty($temp_media)){
				$time_md5_key = md5(time(). rand());
				$imageKey = md5($course_id) . '/' . $time_md5_key . '.' . $temp_media['extension'];
				$result_amazonS3 = $this->saveOnAWS($temp_media['source_file'], $imageKey);
				if ($result_amazonS3) {
					if($temp_media['source_url_thumb'] != ''){
						//create thumbnail
						$thumb_imageKey = md5($course_id) . '/thumb/' . $time_md5_key. '.' .$temp_media['extension'];
						$result_amazonS3_thumbnail = $this->saveOnAWS($temp_media['source_url_thumb'], $thumb_imageKey);
					}

					if($result_amazonS3_thumbnail || $temp_media['source_url_thumb'] == ''){
						/* insert in database */
						$addCourseMedia = ServiceMedia::where('service_id',$course_id)->first();
						if($addCourseMedia){
							/* Remove old media */
							$this->remove_file_on_aws($addCourseMedia->photo_s3_key); // Delete old image
							if($addCourseMedia->thumbnail_media_url != ''){
								$thumbnail_key = md5($course_id).'/thumb/'.$addCourseMedia->photo_s3_key;
								$this->remove_file_on_aws($thumbnail_key); // Delete old image
							}
						}else{
							$addCourseMedia = new ServiceMedia;
							$addCourseMedia->service_id = $course_id;
						}

						$addCourseMedia->media_url 				= $result_amazonS3['ObjectURL'];
						$addCourseMedia->thumbnail_media_url 	= $result_amazonS3_thumbnail['ObjectURL'];
						$addCourseMedia->media_type 			= $temp_media['media_type'];;
						$addCourseMedia->photo_s3_key 			= $imageKey;
						$addCourseMedia->save();

					}
				}else{
					Session::flash('errorTost','Course Image not uploaded.');
				}
				$this->delete_temp_file();
            }

			return redirect()->route('course.description', $input['seo_url']);
		}
        return view('frontend.course.overview', compact('Category'));
    }

    /* Update Course Overview*/
    public function update_overview(Request $request,$seo_url){
		
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

		$uid = $this->uid;
		$Course = Service::withoutGlobalScope('is_course')
		->where(['uid' => $uid, 'seo_url' => $seo_url])
		->where('is_course',1)
		->where('is_custom_order', 0)
		->where('is_job', 0)
		->where('is_delete',0)
		->where('status','!=','permanently_denied')
		->first();

		/* Store Course */
		if($request->isMethod('post')){

			/* Check Validation */
			if(Auth::user()->is_course_training_account() == false){
				$validator = Validator::make($request->all(), [
					'title' => 'required|max:255',
					'subtitle' => 'max:255',
					'price' => 'required|numeric|min:'.env('MINIMUM_SERVICE_PRICE'),
					'monthly_price' => 'numeric|min:'.env('MINIMUM_SERVICE_PRICE'),
				]);
			}else{
				$validator = Validator::make($request->all(), [
					'title' => 'required|max:255',
					'subtitle' => 'max:255',
				]);
			}

			if ($validator->fails()) {
				return redirect()->back()->withErrors($validator)->withInput();
			}
			
			$uid = $this->uid;
			$input = $request->input();

			$preview = 'false';
			if($request->filled('preview') && $request->preview == 'true') {
				$preview = 'true';
			}
			unset($input['preview']);

			$input['is_course'] = 1;
			$input['is_affiliate_link'] = 0;	
			$input['last_updated_by'] = Auth::user()->id;
			$input['uid'] = $uid;

			if ($Course->current_step >= "5") {
				if ($Course->status != "denied"){
					unset($input['category_id']);
					unset($input['subcategory_id']);
				}
			}else{
				SellerCategories::where(['service_id' => $Course->id,'is_default' => 1])->update(['category_id'=>$request->category_id,'sub_category_id'=>$request->subcategory_id]);
			}

			if ($Course->current_step == "5"){
				unset($input['current_step']);
				if ($input['title'] != $Course->title || $input['subtitle'] != $Course->subtitle){
					$input['is_approved'] = '0';
				}
			}

			if ($Course->status == "denied" && $input['title'] != $Course->title) {
				/* update seo url while service denied */
				$input['seo_url'] = Service::generate_seo_slug($input['title']);
			}

			$is_monthly_course = 0;
			if(Auth::user()->is_premium_seller($uid) == true){
				if($request->filled('is_affiliate_link')){
					$input['is_affiliate_link'] = 1;
					$updUser=User::select('id','is_affiliate_service')->find(Auth::user()->id);
					$updUser->is_affiliate_service = 1;
					$updUser->save();
				}
				if(Auth::user()->is_course_training_account() == false && $request->is_monthly_course == 1){
					$is_monthly_course = 1;
				}
			}
			
			$input['last_updated_on'] = Carbon::now()->format('Y-m-d H:i:s');

			/* updating input data in database */
			$Course->title = $input['title'];
			$Course->subtitle = $input['subtitle'];
			$Course->last_updated_by = $input['last_updated_by'];
			$Course->is_affiliate_link = $input['is_affiliate_link'];
			$Course->last_updated_on = $input['last_updated_on'];
			if(isset($input['is_approved'])) {
				$Course->is_approved = $input['is_approved'];
			}
			if(isset($input['current_step'])) {
				$Course->current_step = $input['current_step'];
			}
			if(isset($input['category_id'])) {
				$Course->category_id = $input['category_id'];
			}
			if(isset($input['subcategory_id']) && $input['subcategory_id']) {
				$Course->subcategory_id = $input['subcategory_id'];
			}
			if(isset($input['seo_url'])) {
				$Course->seo_url = $input['seo_url'];
			}
			$Course->save();

			/* Update Lifetime Course Plan */
			$Course->lifetime_plans->package_name = 'Lifetime Access';
			if(isset($request->price)){
				$Course->lifetime_plans->price = $request->price;
			}
			$Course->lifetime_plans->save();

			/* Course Monthly Plan */
			$CoursePlan = ServicePlan::where('service_id',$Course->id)->where('plan_type','monthly_access')->first();
			if(empty($CoursePlan)){
				$CoursePlan = new ServicePlan;
				$CoursePlan->service_id = $Course->id;
				$CoursePlan->plan_type = 'monthly_access';
				$CoursePlan->package_name = 'Monthly Access';
			}
			
			if(isset($request->monthly_price)){
				$CoursePlan->price = $request->monthly_price;
			}
			$CoursePlan->save();

			// Update/Create Course Details
			$courseDetail = CourseDetail::where('course_id',$Course->id)->first();
			if(empty($courseDetail)){
				$courseDetail = new CourseDetail;
				$courseDetail->course_id = $Course->id;
			}
			$courseDetail->is_monthly_course = $is_monthly_course;
			$courseDetail->save();

			/* Upload course image */
			$temp_media = Session::get('course_profile');
			$imageKey = "";
			$thumb_imageKey = "";
			$result_amazonS3 = array();
			$result_amazonS3_thumbnail = array();

			if(!empty($temp_media)){
				$time_md5_key = md5(time(). rand());
				$imageKey = md5($Course->id) . '/' . $time_md5_key . '.' . $temp_media['extension'];
				$result_amazonS3 = $this->saveOnAWS($temp_media['source_file'], $imageKey);
				if ($result_amazonS3) {
					if($temp_media['source_url_thumb'] != ''){
						//create thumbnail
						$thumb_imageKey = md5($Course->id) . '/thumb/' . $time_md5_key. '.' .$temp_media['extension'];
						$result_amazonS3_thumbnail = $this->saveOnAWS($temp_media['source_url_thumb'], $thumb_imageKey);
					}

					if($result_amazonS3_thumbnail || $temp_media['source_url_thumb'] == ''){

						unlink($temp_media['source_file']);
						unlink($temp_media['source_url_thumb']);
						Session::forget('course_profile');

						/* insert in database */
						$addCourseMedia = ServiceMedia::where('service_id',$Course->id)->first();
						if($addCourseMedia){
							/* Remove old media */
							$this->remove_file_on_aws($addCourseMedia->photo_s3_key); // Delete old image
							if($addCourseMedia->thumbnail_media_url != ''){
								$thumbnail_key = md5($Course->id).'/thumb/'.$addCourseMedia->photo_s3_key;
								$this->remove_file_on_aws($thumbnail_key); // Delete old image
							}
						}else{
							$addCourseMedia = new ServiceMedia;
							$addCourseMedia->service_id = $Course->id;
						}

						$addCourseMedia->media_url 				= $result_amazonS3['ObjectURL'];
						$addCourseMedia->thumbnail_media_url 	= $result_amazonS3_thumbnail['ObjectURL'];
						$addCourseMedia->media_type 			= $temp_media['media_type'];;
						$addCourseMedia->photo_s3_key 			= $imageKey;
						$addCourseMedia->save();

					}
				}else{
					Session::flash('errorTost','Course Image not uploaded.');
				}
				$this->delete_temp_file();
			}

			if($preview == 'true') {
				return response()->json(['status' => 'success','url'=>route('course_details',['username'=>$Course->user->username,'seo_url'=> $Course->seo_url])]);
			}

			return redirect()->route('course.description', $Course->seo_url);
		}
		
		$this->delete_temp_file();
		$Category = Category::withoutGlobalScope('type')->where('type',1)->pluck('category_name', 'id')->toArray();
		$Subcategory = Subcategory::where('category_id', $Course->category_id)->where('status',1)->pluck('subcategory_name', 'id')->toArray();

		return view('frontend.course.overview', compact('Category', 'Subcategory', 'Course'));
    }

    /* Update Course Description */
	public function description(Request $request, $seo_url) {

		//Admin can make user to soft ban , so user can't place any edit
		if(User::is_soft_ban() == 1){
			return redirect()->route('mycourses')->with('errorFails', get_user_softban_message());
		}

		if ($seo_url != 'null') {

			$uid = $this->uid;

			$Course = Service::withoutGlobalScope('is_course')
			->where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('is_course',1)
			->where('status','!=','permanently_denied')
			->first();

			if (empty($Course)) {
				return redirect()->route('mycourses');
			}

			$this->delete_temp_file();

			if ($request->isMethod('post')) {
				$input = $request->input();

				$preview = 'false';
				if($request->filled('preview') && $request->preview == 'true') {
					$preview = 'true';
				}
				unset($input['preview']);
				unset($input['_token']);

				$input['last_updated_by'] = Auth::user()->id;
				$input['last_updated_on'] = Carbon::now()->format('Y-m-d H:i:s');

				if ($input['current_step'] < $Course->current_step) {
					unset($input['current_step']);
				}
				if ($Course->current_step == "5"){
					if ($input['descriptions'] != $Course->descriptions || $input['meta_description'] != $Course->meta_description) 
					{
						$input['is_approved'] = '0';
					}
				}

				/* updating input data in database */
				$Course->meta_title = $input['meta_title'];
				$Course->tags = $input['tags'];
				$Course->meta_keywords = $input['meta_keywords'];
				$Course->meta_description = $input['meta_description'];
				$Course->descriptions = $input['descriptions'];
				$Course->last_updated_by = $input['last_updated_by'];
				$Course->last_updated_on = $input['last_updated_on'];
				if(isset($input['current_step'])) {
					$Course->current_step = $input['current_step'];
				}
				if(isset($input['is_approved'])) {
					$Course->is_approved = $input['is_approved'];
				}
				$Course->save();

				if($preview == 'true') {
					return response()->json(['status' => 'success','url'=>route('course_details',['username'=>$Course->user->username,'seo_url'=> $Course->seo_url])]);
				}
				return redirect()->route('course.requirement', $seo_url);
			}

			return view('frontend.course.description', compact('Course'));
		}
	}

	/* Update Course Requirement */
	public function requirement(Request $request, $seo_url) {
		//Admin can make user to soft ban , so user can't place any edit
		if(User::is_soft_ban() == 1){
			return redirect()->route('mycourses')->with('errorFails', get_user_softban_message());
		}
		if ($seo_url != 'null') {
			$uid = $this->uid;
			$Course = Service::withoutGlobalScope('is_course')
			->where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_course', 1)
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();

			if (empty($Course)) {
				return redirect()->route('mycourses');
			}

			if ($request->isMethod('post')) {
				$input = $request->input();
				
				$preview = 'false';
				if($request->filled('preview') && $request->preview == 'preview_course') {
					$preview = 'preview_course';
				}
				unset($input['preview']);

				$input['last_updated_by'] = Auth::user()->id;
				unset($input['_token']);

				if ($input['current_step'] < $Course->current_step) {
					unset($input['current_step']);
				}
				
				$input['last_updated_on'] = Carbon::now()->format('Y-m-d H:i:s');
				//Service::where('seo_url', $seo_url)->update($input);

				/* updating input data in database */
				$Course->questions = $input['questions'];
				$Course->last_updated_by = $input['last_updated_by'];
				$Course->last_updated_on = $input['last_updated_on'];
				if(isset($input['current_step'])) {
					$Course->current_step = $input['current_step'];
				}
				$Course->save();

				$courseDetail = CourseDetail::select('id','course_id')->where(['course_id'=>$Course->id])->first();
				if(is_null($courseDetail)){
					$courseDetail = new CourseDetail;
					$courseDetail->course_id = $Course->id;
				}
				$courseDetail->what_you_learn = $request->what_you_learn;
				$courseDetail->save();
				
				if($preview == 'preview_course') {
					return response()->json(['status' => 'success','url'=>route('course_details',['username'=>$Course->user->username,'seo_url'=> $Course->seo_url])]);
				}

				return redirect()->route('course.section', $seo_url);
			}
			return view('frontend.course.requirement', compact('Course'));
		}
	}

	/* Course Section page */
	public function section(Request $request, $seo_url) {
		$uid = $this->uid;

		$Course = Service::withoutGlobalScope('is_course')
			->where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_course', 1)
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();

		if(is_null($Course)) {
			Session::flash('errorFails', 'Invalid Course.');
			return redirect()->route('mycourses');
		}

		/* Delete temp file */
		$this->delete_temp_file();

		/* upload max file */
		if(Auth::user()->is_premium_seller() == true) {
			$maxFilesize = 250;	
		} else {
			$maxFilesize = 100;	
		}

		$course_sections = CourseSection::with(['course:id,uid'])->where('course_id',$Course->id)->where('is_draft',0)->orderBy('short_by')->get();
		return view('frontend.course.section', compact('Course','course_sections','maxFilesize'));
	}

	/* Create/Update content*/
	public function create_section(Request $request, $seo_url){
		$uid = $this->uid;
		$Course = Service::withoutGlobalScope('is_course')
			->select('id','seo_url','current_step')
			->where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_course', 1)
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();

		if(is_null($Course)) {
			Session::flash('errorFails', 'Invalid Course.');
			return redirect()->route('mycourses');
		}

		/* Check Validation */
		$validator = Validator::make($request->all(), [
			'name' => 'required|max:255',
		]);
		if ($validator->fails()) {
			if($request->ajax()){
				return response()->json(['status'=>false,'code'=>401,'message'=>$validator->fails()->message]);
			}else{
				return redirect()->back()->withErrors($validator)->withInput();
			}
		}

		/* Create/Update Course Section*/
		if($request->type == 'new'){
			$last_sort_id = CourseSection::select('id','short_by')
				->where('course_id',$Course->id)
				->orderBy('short_by','desc')
				->first();

			if(!$last_sort_id){
				$last_id = 1;
			}else{
				$last_id = $last_sort_id->short_by+1;
			}

			$courseSection = new CourseSection;
			$courseSection->short_by = $last_id;
			$message = 'Course section created successfully.';
			
			/* Course need to approv by admin*/ 
			$Course->is_approved = 0;
		}else{
			$message = 'Course section updated successfully.';
			$id = CourseSection::getDecryptedId($request->id);
			$courseSection = CourseSection::where('course_id',$Course->id)->where('is_approve',0)->where('id',$id)->first();
			if(!$courseSection){
				if($request->ajax()){
					return response()->json(['status'=>false,'code'=>401,'message'=>'Something went wrong.']);
				}else{
					Session::flash('errorFails', 'Something went wrong.');
					return redirect()->back();
				}
			}

			if($courseSection->name != $request->name){
				/* Course need to approv by admin*/ 
				$Course->is_approved = 0;
			}
		}
		
		$courseSection->course_id = $Course->id;
		$courseSection->name = $request->name;
		$courseSection->save();

		/* Update Course Step */
		$Course->current_step = 5;
		$Course->save();
	
		if($request->ajax()){
			return response()->json(['status'=>true,'code'=>200,'message'=>$message, 'id'=> $courseSection->secret, 'name'=>$courseSection->name]);
		}else{
			Session::flash('errorSuccess', $message);
			return redirect()->back()->with('new_section',$courseSection->secret);
		}
	}
	
	/* get Content form */
	public function get_content_form(Request $request, $type,$seo_url,$secret){
		$uid = $this->uid;
		$Course = Service::withoutGlobalScope('is_course')
			->where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_course', 1)
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();
		if(is_null($Course)) {
			return response()->json(['status'=>false,'code'=>401,'html'=>'','message'=>'Invalid Service.']);
		}

		if($type == 'create'){
			$html = view('frontend.course.include.add_content_media',compact('Course','secret'))->render();
			return response()->json(['status'=>true,'code'=>200,'html'=>$html,'secret'=>$secret]);
		}elseif($type == 'update'){
			$this->delete_temp_file();
			if($request->has('content_secret') && $request->content_secret != ""){
				$course_content_id = ContentMedia::getDecryptedId($secret);
				$id = ContentMedia::getDecryptedId($request->content_secret);
				$contentMedia =  ContentMedia::where('id',$id)->where('course_id',$Course->id)->where('course_content_id',$course_content_id)->where('is_draft',0)->first();
				if($contentMedia){
					$html = view('frontend.course.include.edit_content_media',compact('Course','secret','contentMedia'))->render();
					return response()->json(['status'=>true,'code'=>200,'html'=>$html,'secret'=>$secret]);
				}
			}
			return response()->json(['status'=>false,'code'=>401,'html'=>'','secret'=>$secret,'message'=>'Something went wrong.']);
		}
	}
	/* Public Course */ 
	public function publish(Request $request, $seo_url) {

		//Admin can make user to soft ban , so user can't place any edit
		if(User::is_soft_ban() == 1){
			return redirect()->route('mycourses')->with('errorFails', get_user_softban_message());
		}

		if ($seo_url != 'null') {
			$uid = $this->uid;

			$Course = Service::withoutGlobalScope('is_course')
			->where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_course', 1)
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('current_step','>=',5)
			->where('status','!=','permanently_denied')
			->first();

			if (empty($Course)) {
				return redirect()->route('mycourses');
			}

			/* Check course content */
			$content_medias = ContentMedia::select('id')->where('course_id',$Course->id)->where('is_draft',0)->count();
			if($content_medias == 0){
				return redirect()->back()->with('errorFails', 'Please upload any content.');
			}

			$input['current_step'] = 5;
			$input['status'] = 'active';

			if($Course->is_approved == 1 && $Course->status == 'denied'){
				$input['is_approved'] = '0';
			}

			if($Course->status == 'denied')
			{
				$input['reuse_denied_status'] = 1;
			}

			$input['last_updated_by'] = Auth::user()->id;
			$input['last_updated_on'] = Carbon::now()->format('Y-m-d H:i:s');
			Service::withoutGlobalScope('is_course')->where('seo_url', $seo_url)->update($input);

			if(Str::contains($request->fullUrl(), '?page=service_details')) {
				return redirect(url()->previous());
			}

			return redirect()->route('mycourses');
		}
	}

	/* Course Detail Page */
	public function details($username, $seo_url, $token = null, Request $request) {	

		/* Is admin preview */
		$is_admin = false;
		if(!is_null($token)){
			$master_admin = Admin::where('email','info@demo.com')->select('email','password')->first();
			$encodedParam = json_encode($master_admin);
			$access_token = base64_encode($encodedParam);
			$access_token = str_replace("/","", $access_token);

			if($access_token != $token){
				return redirect('404');
			}

			$is_admin = true;

			$Service = Service::withoutGlobalScope('is_course')
			->where('is_course',1)
			->where('seo_url', $seo_url)
			->where('is_delete',0)
			->first();
		}else{
			$Service = Service::withoutGlobalScope('is_course')
			->where('is_course',1)
			->whereIn('status', ['active', 'denied','draft','paused'])
			->where('seo_url', $seo_url)
			->where('is_delete',0)
			->where('is_job',0)
			->where('is_custom_order',0)
			->first();
		}

		//Check service found or not
		if (empty($Service)) {
			return redirect('404');
		}

		$can_access = true;
		$uid = $this->uid;

		// User have NOT purchased this course than it will be available all time
		$purchaseDetails = Service::purchaseCourseDetails($Service->id,$uid);

		$serviceUser = $Service->user;

		$block_users = User::getBlockedByIds();

		if(empty($purchaseDetails)){
			if($serviceUser->is_delete != 0 || $serviceUser->vacation_mode == 1){
				return redirect('404');
			}
		
			/*Check Blocked users*/
			if(in_array($Service->uid,$block_users)){
				abort(401);
			}
			/*End Check Blocked user*/

			if($is_admin == false){
				if(Auth::check()){
					//With Login Check Access
					if($Service->uid != $uid){ 
						//Other Seller Login
						if($Service->status == 'draft' || $Service->status == 'paused' || $Service->is_approved == 0) {
							$can_access = false;
						}
						if($Service->status == 'denied'){
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
					if($Service->status == 'draft' || $Service->status == 'paused' || $Service->is_approved == 0) {
						$can_access = false;
					}
					if($Service->status == 'denied'){
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

				/* Update Service review Count */
				$total_reviews = Order::select('id')->where(['service_id' => $Service->id])
				->whereIn('status',['cancelled','completed'])
				->where('seller_rating', '>', 0)
				->count();
				
				$Service->total_review_count = $total_reviews;
				$Service->updated_at = Carbon::now()->format('Y-m-d H:i:s');
				$Service->save();
			}
		}

		$id = $Service->id;
		$serviceIds = Service::withoutGlobalScope('is_course')->where('is_course',1)->select('id')->where('id',$id)->orwhere('parent_id',$id)->get()->makeHidden('secret')->toArray();
		
		$Comment = Order::select('id', 'uid', 'seller_uid','service_id', 'completed_note', 'review_date', 'plan_type', 'package_name', 'seller_rating', 'completed_reply','status','cancel_date','helpful_count','is_review_edition');
		
		if($is_admin == true){
			$Comment = $Comment->where(['status' => 'completed','is_review' => 1])->whereIn('service_id',$serviceIds);
		}else{
			$Comment = $Comment->whereIn('status',['cancelled','completed'])
			->where('seller_rating', '>' ,0)
			->whereIn('service_id',$serviceIds);
		}

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
		$Comment->withPath(route('course.get_all_review'));
		$CommentCount = Order::select('id')->where(['status' => 'completed', 'service_id' => $id, 'is_review' => 1])->count();

		$ratingModel = new Order;
		$avg_service_rating = $ratingModel->calculateServiceAverageRating($id);
		$avg_seller_rating = $ratingModel->calculateSellerAverageRating($Service->uid);

		$Service->service_rating = $avg_service_rating;
		$Service->save();

		/*Update Total Service view By Month*/
		if(Auth::check() && $Service->uid != $uid){
			$sellerAnalytic = SellerAnalytic::select('id')->where('service_id',$id)
			->where('buyer_uid',$uid)
			->where('type','service_view')
			->whereMonth('created_at', date('m'))
			->whereYear('created_at', date('Y'))
			->count();
			if($sellerAnalytic == 0){
				$sellerAnalytic = new SellerAnalytic;
				$sellerAnalytic->service_id = $id;
				$sellerAnalytic->buyer_uid = $uid;
				$sellerAnalytic->type = 'service_view';
				$sellerAnalytic->save(); 
			}
		}
		
		$bundleService = BundleService::where('service_id',$Service->id)->first();
		$otherService = null;
		if(count($bundleService)){
			$otherService = BundleService::where('bundle_id',$bundleService->bundle_id)
			->where('service_id','!=',$Service->id)
			->whereHas('service',function($q){
				$q->select('id');
				$q->where('is_approved',1);
				$q->where('status','active');
				$q->where('is_delete',0);
			})
			->get();
		}

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
			->where('seller_rating', '>', 0)
			->count();		

			$orderReview=Order::select('completed_note')->where('plan_type',$key->plan_type)
			->where('service_id',$id)
			->whereIn('status',['cancelled','completed'])
			->where('seller_rating', '>', 0)
			->orderBy('id','desc')->first();

			$completed_note = '';
			if(count($orderReview)){
				$completed_note = $orderReview->completed_note;
			}			
			$reviewPlanData[]=['plan_type' => $key->plan_type,'review' => $completed_note,'total_review' => $orderReviewTotal];
		}

		/*redirect to cart page with service added in cart if guest user login to buy service*/
		$guest_service_id = Session::get('service_id');
		if($guest_service_id != 0)
		{
			if($Service->uid != $uid)
			{
				/*Check for order in queue*/
				$allowbackOrder = $Service->allowBackOrder();
				if($allowbackOrder->can_place_order == true){
					$Service = Service::with('images', 'extra', 'coupon')->withoutGlobalScope('is_course')->where('is_course',1)->find($id);

					$ServicePlan = ServicePlan::where('service_id',$id)->find($guest_service_id);

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
						$cart_exist = Cart::where('uid',$uid)->where('service_id',$id)->where('plan_id',$guest_service_id)->first();
						if(is_null($cart_exist)) {
							$inserData=new Cart;
							$inserData->uid = $uid;
							$inserData->quantity=1;
							$inserData->plan_id=$guest_service_id;
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
			if($Service->uid != $uid)
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
			if($Service->uid != $uid)
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
			if($Service->uid != $uid)
			{
				$bundleservice = BundleService::where('bundle_id',$dyanamicBundle)->get();
				if(!$bundleservice->isempty()){
					foreach ($bundleservice as $allsevices) {
						$service = Service::withoutGlobalScope('is_course')->where('is_course',1)->where('id',$allsevices->service_id)->first();
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
								->where('buyer_uid',$uid)
								->where('type','add_to_cart')
								->whereMonth('created_at', date('m'))
								->whereYear('created_at', date('Y'))
								->count();
								if($sellerAnalytic == 0){
									$sellerAnalytic = new SellerAnalytic;
									$sellerAnalytic->service_id = $cart->service_id;
									$sellerAnalytic->buyer_uid = $uid;
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

		// Seller Profile Info
		$total_seller_rating = $ratingModel->getReviewTotal($serviceUser->id);

		$no_of_courses = Service::withoutGlobalScope('is_course')->where('is_course',1)
			->where('status','active')->where('is_approved',1)->where('is_delete',0)
			->where('uid',$serviceUser->id)->select('id')->count();

		$no_of_students = Order::distinct('uid')->select('uid')->where('status','!=','cancelled')->where('is_course',1)->where('seller_uid',$serviceUser->id)->count();

		// This course includes
		$on_demand_video = ContentMedia::select('id')->where('media_type','video')->where('course_id',$Service->id)->where('is_draft',0)->where('is_approve',1)->sum('media_time');
		$total_articles = ContentMedia::select('id')->where('media_type','article')->where('course_id',$Service->id)->where('is_draft',0)->where('is_approve',1)->count();
		
		// Other courses you may like
		$other_related_courses = Service::withoutGlobalScope('is_course')
		->with(['user:id,username,profile_photo,photo_s3_key,seller_level'])
		->select(
			'service_plan.price','services.id','services.uid','services.title','services.subtitle','services.seo_url',
			'services.descriptions','services.service_rating','services.total_review_count'
			)
		->join('users', 'services.uid', '=', 'users.id')
		->join('service_plan', 'service_plan.service_id', '=', 'services.id')
		 ->where([
		 	'services.is_course'=> 1,
			'services.status'=> 'active',
			'services.is_private'=> 0,
			'services.is_approved'=> 1,
			'services.is_delete'=> 0,
			'services.category_id'=> $Service->category_id,
		 	'service_plan.plan_type'=> 'lifetime_access',
			'users.status'=> 1,
			'users.is_delete'=> 0,
			'users.vacation_mode'=> 0,
		 ])
		->where('services.id','!=',$Service->id)
		->where('services.uid','!=',$uid);

		if(count($block_users)>0){
			$other_related_courses = $other_related_courses->whereNotIn('services.uid', $block_users);
		}

		$dataUser = null;
		if(Auth::check()){
			$purchased_course_ids = Order::distinct('orders.service_id')
			->join('services','services.id','orders.service_id')
			->where('services.category_id',$Service->category_id)
			->where('orders.status','!=','cancelled')->where('orders.is_course',1)->where('orders.uid',$uid)->pluck('orders.service_id')->toArray();
			if(count($purchased_course_ids) > 0){
				$other_related_courses = $other_related_courses->whereNotIn('services.id', $purchased_course_ids);
			}
			$dataUser=User::select('id','affiliate_id')->where('id',$uid)->first();
		}
		
		$other_related_courses = $other_related_courses->orderBy('services.service_rating','desc')->limit(10)->get();
		
		/* Detect Device */
		$agent = new Agent();
		$is_mobile_device = $agent->isMobile();

		return view('frontend.course.details', compact('Service', 'Comment', 'avg_service_rating', 'avg_seller_rating', 'CommentCount','bundleService','otherService','save_template','save_template_chat','reviewPlanData','username','seo_url','showMsg','showCustomBox','total_seller_rating','serviceUser','no_of_courses','no_of_students','on_demand_video','total_articles','other_related_courses','is_admin','token','is_mobile_device','dataUser'));
	}

	/* get course review and rating */
	public function get_all_review(Request $request) {
		if ($request->input()) {
			$Service = Service::withoutGlobalScope('is_course')->with('user.country', 'category', 'images', 'video', 'pdf', 'basic_plans', 'standard_plans', 'premium_plans')->where('is_course',1);
			if (Auth::check()) {
				$Service = $Service->with('favorite');
			}
			$Service = $Service->whereIn('status', ['active', 'denied'])->where('seo_url',$request->seo_url)->first();
			$id = $Service->id;

			$serviceid = Service::withoutGlobalScope('is_course')->where('is_course',1)->select('id')->where('id',$id)->orwhere('parent_id',$id)->get()->makeHidden('secret')->toArray();

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

			$Comment->withPath(route('course.get_all_review'));
			return view('frontend.course.include.rating_review', compact('Comment', 'Service','seo_url'))->render();
		}
	}

	/* Store Content*/
	public function store_content(Request $request,$seo_url,$secret){
		$validator = Validator::make($request->all(), [
			'name' => 'required|max:120',
		]);
		if ($validator->fails()) {
			return response()->json(['success'=>false,'status'=>401,'message'=>$validator->errors()->first()]);
		}

		if($request->media_type == "video" && $request->upload_media == ""){
			return response()->json(['success'=>false,'status'=>401,'message'=>'Please upload video.']);
		}

		/* Check User Course*/
		$uid = $this->uid;
		$Course = Service::withoutGlobalScope('is_course')
			->select('id','seo_url','current_step')
			->where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_course', 1)
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();
		if(is_null($Course)) {
			return response()->json(['success'=>false,'status'=>401,'message'=>'Invalid Course.']);
		}

		/* Check course content */
		$id = CourseSection::getDecryptedId($secret);
		$content = CourseSection::select('id')->where('course_id',$Course->id)->where('id',$id)->first();
		if(!$content){
			return response()->json(['status'=>false,'code'=>401,'message'=>'Something went wrong.']);
		}

		/* Update content check id validation */
		$message = "Content created successfully.";
		$create = null;
		if($request->has('id') && $request->id != ""){
			$content_id = ContentMedia::getDecryptedId($request->id);
			$create = ContentMedia::where('id',$content_id)->where('course_id',$Course->id)->where('course_content_id',$content->id)->first();
			$message = "Content updated successfully.";
			if(is_null($create)){
				return response()->json(['status'=>false,'code'=>401,'message'=>'Something went wrong.']);
			}
		}

		// Update course content with basic information
		if($create != null){ 
			$create->is_preview = 0;
			$create->name = $request->name;
			
			if($create->media_type == 'video' && $request->has('is_preview') && $request->is_preview == 1){
				$create->is_preview = 1;
			}
			
			if($create->media_type == 'article'){
				if($request->duration != ""){
					$estimate_time = explode(':',$request->duration);
					$estimate_hours = convert_reading_time($estimate_time[0],'h');
					$estimate_minutes = convert_reading_time($estimate_time[1]);
					$estimate_seconds = $estimate_time[2] + $estimate_minutes + $estimate_hours;
					$create->media_time = $estimate_seconds;
				}
				$create->article_text   = $request->upload_article;
				$create->video_description = "";
			}else{
				$create->video_description = $request->video_description;
			}
			if($create->name != $request->name){
				$create->is_approve = 0;
			}
			$create->save();
			
			/* Get Content List*/
			$contentMedia = ContentMedia::where('course_id',$Course->id)->where('course_content_id',$content->id)->where('is_draft',0)->orderBy('short_by','asc')->get();
			$content_secret = $secret;
			$view = view('frontend.course.include.content_list',compact('content_secret','seo_url','contentMedia'))->render();
			
			return response()->json(['status'=>true,'code'=>200,'message'=>$message,'html'=>$view,'secret'=>$secret]);
		}else{ 
			//Create course content video/article

			/* Create content with video */
			if($request->media_type == "video"){

				$temp_media = Session::get('course_media');
				if(!empty($temp_media)){
					if($temp_media['originalName'] != $request->upload_media && $temp_media['originalName'] != $request->upload_article){
						return response()->json(['success'=>false,'status'=>401,'message'=>'Something went wrong.']);
					}
				}
				
				/* Generate media key */
				$fileKey = md5($Course->id).'/'.md5(time()) . '.' . $temp_media['extension'];
				/* Uploading in aws */
				$result_amazonS3 = $this->saveOnAWS($temp_media['source_file'], $fileKey);
				if ($result_amazonS3) {
					
					$thumb_imageKey = '';
					$result_amazonS3_thumbnail = array();
					if($temp_media['source_url_thumb'] != ''){
						//create thumbnail
						$thumb_imageKey = md5($Course->id).'/thumb/'.md5(time()) . '.png';
						$result_amazonS3_thumbnail = $this->saveOnAWS($temp_media['source_url_thumb'], $thumb_imageKey);
					}

					if ($result_amazonS3_thumbnail || $temp_media['source_url_thumb'] == '') {
						
						unlink($temp_media['source_file']);
						unlink($temp_media['source_url_thumb']);
						Session::forget('course_media');

						$last_sort_id = ContentMedia::select('id','short_by')
						->where('course_id',$Course->id)
						->where('course_content_id',$content->id)
						->orderBy('short_by','desc')
						->first();

						if(!$last_sort_id){
							$last_id = 1;
						}else{
							$last_id = $last_sort_id->short_by+1;
						}

						$create = new ContentMedia;
						$create->course_id           = $Course->id;
						$create->course_content_id   = $content->id;
						$create->short_by       = $last_id;
						

						$create->name           = $request->name;
						$create->media_thumbnail_url  	= (!empty($result_amazonS3_thumbnail))? $result_amazonS3_thumbnail['ObjectURL'] : '';
						$create->media_thumbnail_key   	= ($thumb_imageKey != '')? $thumb_imageKey : '';
						$create->media_s3_key   = $fileKey;
						$create->media_url     	= $result_amazonS3['ObjectURL'];
						$create->media_type     = $temp_media['media_type'];
						$create->media_size     = $temp_media['media_size'];
						$create->media_time     = $temp_media['duration'];
						$create->media_mime     = $temp_media['mime'];
						$create->media_original_name  = $temp_media['originalName'];
						$create->video_description = $request->video_description;
						$create->is_approve  	= 0;
						if($request->has('is_preview') && $request->is_preview == 1){
							$create->is_preview = 1;
						}
						$create->save();

						/* Update course step */
						$Course->is_approved = 0;
						$Course->current_step = $request->input('current_step');
						$Course->save();

						/*Get Content List*/
						$contentMedia = ContentMedia::where('course_id',$Course->id)->where('course_content_id',$content->id)->where('is_draft',0)->orderBy('short_by','asc')->get();
						$content_secret = $secret;
						$view = view('frontend.course.include.content_list',compact('content_secret','seo_url','contentMedia'))->render();

						return response()->json(['status'=>true,'code'=>200,'message'=>$message,'html'=>$view,'secret'=>$secret]);
					}else{
						$this->remove_file_on_aws($fileKey); // Delete 
						return response()->json(['status'=>false,'code'=>401,'message'=>'Something went wrong. Please try again.']);
					}
				}else{
					return response()->json(['status'=>false,'code'=>401,'message'=>'Something went wrong. Please try again.']);
				}
			}else{
				// Create content with article
				$last_sort_id = ContentMedia::select('id','short_by')
					->where('course_id',$Course->id)
					->where('course_content_id',$content->id)
					->orderBy('short_by','desc')
					->first();

				if(!$last_sort_id){
					$last_id = 1;
				}else{
					$last_id = $last_sort_id->short_by+1;
				}

				$estimate_seconds = 0;
				if($request->duration != ""){
					$estimate_time = explode(':',$request->duration);
					$estimate_hours = convert_reading_time($estimate_time[0],'h');
					$estimate_minutes = convert_reading_time($estimate_time[1]);
					$estimate_seconds = $estimate_time[2] + $estimate_minutes + $estimate_hours;
				}

				$create = new ContentMedia;
				$create->course_id           = $Course->id;
				$create->course_content_id   = $content->id;
				$create->media_type     = 'article';
				$create->name           = $request->name;
				$create->short_by       = $last_id;
				if($estimate_seconds != 0){
					$create->media_time = $estimate_seconds;
				}
				$create->article_text   = $request->upload_article;
				$create->video_description = "";
				$create->is_preview = 0;
				$create->save();

				/* Update course step */
				$Course->is_approved = 0;
				$Course->current_step = $request->input('current_step');
				$Course->save();

				/*Get Content List*/
				$contentMedia = ContentMedia::where('course_id',$Course->id)->where('course_content_id',$content->id)->where('is_draft',0)->orderBy('short_by','asc')->get();
				$content_secret = $secret;
				$view = view('frontend.course.include.content_list',compact('content_secret','seo_url','contentMedia'))->render();

				return response()->json(['status'=>true,'code'=>200,'message'=>$message,'html'=>$view,'secret'=>$secret]);

			}
		}
	}

	/* Upload Course Media*/ 
	public function upload_content_media(Request $request){
		if($request->has('upload_profile')){
			$validator = Validator::make($request->all(), [
					'file' => 'mimetypes:image/jpg,image/jpeg,image/png|max:20480'
				],
				[
					'file.max' => 'File is larger than 20MB'
				]
			);
			
			$file = $request->upload_profile;
			/* Get duration */
			$duration = 0;
		}else{
			$validator = Validator::make($request->all(), [
					'file' => 'mimetypes:video/mp4,video/x-msvideo,video/quicktime|max:204800'
				],
				[
					'file.max' => 'File is larger than 200MB'
				]
			);
			$file = $request->file;
			/* Get duration */
			if($request->getHttpHost() != 'localhost'){
				$config_ffprobe = array(
					'ffmpeg.binaries'  => env('FFMPEG_PATH'), 
					'ffprobe.binaries' => env('FFPROBE_PATH'),
					'timeout'          => 3600, // The timeout for the underlying process
         			'ffmpeg.threads'   => 12);
				$duration = \FFMpeg\FFProbe::create($config_ffprobe)->format($file)->get('duration');
			}else{
				$duration = 0;
			}
		}

		if ($validator->fails()) {
			return response()->json(['success'=>false,'status'=>401,'message'=>$validator->errors()->first()]);
		}

        try{        
            $originalName   = $file->getClientOriginalName();
            $mime       = $file->getClientMimeType();
            $filesize   = formatBytes($file->getSize(),2);
            $ext        = $file->extension();
            $fileName   = time().rand().'.'.$ext;

			$media_path = 'courses/media';
			
		
			$file->move(public_path($media_path), $fileName);
			$SourceFile = public_path($media_path).'/'.$fileName;
		

            $source_url_thumb = '';
			$thumbnail_url = '';
            //$thumbnail_url = url('public/frontend/images/document-icon.png');
			$session_name = "course_media";

            //Generate thumbnail
			
            if ($request->has('file') && $request->getHttpHost() != 'localhost') {
				$thumb_name = 'thumb_'.time().rand().'.png';
				$destination_path = public_path($media_path);
                // Video thumbnail
                $thumbnail_status = Thumbnail::getThumbnail($SourceFile,$destination_path,$thumb_name,env('TIME_TO_TAKE_SCREENSHOT'));
                if($thumbnail_status){
                    $source_url_thumb = public_path($media_path).'/'.$thumb_name;
                }else{
                    return response()->json(['success'=>false,'status'=>401,'message'=>'Something went wrong. Please try again.']);
                }

				// Local Thumbnail Url
				$thumbnail_url = url('public/'.$media_path.'/'.$thumb_name);
				$originalImage = Image::make($source_url_thumb);
				$originalImage->fit(env('THUMBNAIL_IMAGE_WIDTH'), env('THUMBNAIL_IMAGE_HEIGHT'),NULL,'top')->save($source_url_thumb,85);
				//End Generate thumbnail
            }

			if($request->has('upload_profile')){

				// Image thumbnail
            	$thumb_name = 'thumb_'.time().rand().'.png';
                $source_url_thumb = public_path($media_path).'/'.$thumb_name;
                Storage::disk('course_image')->copy($fileName, $thumb_name);

				// Local Thumbnail Url
				$thumbnail_url = url('public/'.$media_path.'/'.$thumb_name);
				$originalImage = Image::make($source_url_thumb);
				$originalImage->fit(env('THUMBNAIL_IMAGE_WIDTH'), env('THUMBNAIL_IMAGE_HEIGHT'),NULL,'top')->save($source_url_thumb,85);
				//End Generate thumbnail
				$session_name = "course_profile";
			}

			//Remove old session uploaded files
			$this->delete_temp_file();

			/* Session value */
            $data['source_file']    = $SourceFile;
            if($request->has('upload_profile')){
                $data['media_type'] = 'image';
            }else{
                $data['media_type'] = 'video';
            }

            $data['media_size']     = $filesize;
            $data['media_mime']     = $mime;
            $data['originalName']   = $originalName;
            $data['extension']      = $ext;
            $data['duration']      	= $duration;
            $data['source_url_thumb'] = $source_url_thumb;

            $data['media_path'] = $media_path;
            session()->put($session_name,$data);
			
			/* Response value */
            $data['duration']      	= 0;
            $data['source_url_thumb'] = $thumbnail_url;
            $data['success']    = true;
            $data['status']     = 200;
            $data['message']    = 'Media uploaded successfully';
            $data['source_file']    = '';
            return response()->json($data);
        }catch(\Exception $e){
            return response()->json(['success'=>false,'status'=>401,'message'=>'Something went wrong. Please try again.']);
        }
	}

	/*Temp delete function*/ 
	public function delete_media(Request $request){
		$message = $this->delete_temp_file();
		return response()->json(['success'=>true,'status'=>200,'message'=>$message]);
	}

  	/* private function delete temp file */
	function delete_temp_file(){
		$message = 'File not found';
		$temp_media = Session::get('course_media');
		if(!empty($temp_media)){
			unlink($temp_media['source_file']);
			unlink($temp_media['source_url_thumb']);
			Session::forget('course_media');
			$message = 'File removed successfully';
		}
		
		$temp_media = Session::get('course_profile');
		if(!empty($temp_media)){
			unlink($temp_media['source_file']);
			unlink($temp_media['source_url_thumb']);
			Session::forget('course_profile');
			$message = 'File removed successfully';
		}
		return $message;
	}

	/* private function for save image on aws */
    function saveOnAWS($SourceFile, $imageKey) {
        try{        
            $s3 = AWS::createClient('s3');
            $bucket = env('bucket_course');
            $result_amazonS3 = $s3->putObject([
                'Bucket'        => $bucket,
                'Key'           => $imageKey,
                'SourceFile'    => $SourceFile,
                'StorageClass'  => 'REDUCED_REDUNDANCY',
                'ACL'           => 'public-read',
            ]);
        }catch(Aws\S3\Exception\S3Exception $e){
            $result_amazonS3 = $e;
        }
        return $result_amazonS3;
    }

    /* private function to remove file */
    function remove_file_on_aws($keyData){
        $s3 = AWS::createClient('s3');
        $bucket = env('bucket_course');
        try {
            $result_amazonS3= $s3->deleteObject([
                'Bucket' => $bucket,
                'Key'    => $keyData,
            ]);
        } catch (Aws\S3\Exception\S3Exception $e) {
            $result_amazonS3['ObjectURL'] = '';
        }
        return true;
    }

	/*Change Ordering of Section*/
    public function change_ordering_section(Request $request){
        if($request->has('id') && $request->id != ""){
			if($request->has('type') && $request->type == "content_media"){
				foreach ($request->id as $key => $id) {
					$id = ContentMedia::getDecryptedId($id);
					ContentMedia::where('id',$id)->update(['short_by'=>$key]);
				}
				$response['message'] = "Content media sorting changed successfully.";				
			}else{
				foreach ($request->id as $key => $id) {
					$id = CourseSection::getDecryptedId($id);
					CourseSection::where('id',$id)->update(['short_by'=>$key]);
				}
				$response['message'] = "Content sorting changed successfully.";
			}

            $response['success'] = true;
            $response['status'] = 200;
        }else{
            $response['success'] = false;
            $response['status'] = 401;
        }
        return response()->json($response);
    }

	// Delete Course
	public function remove(Request $request, $seo_url) {
		$uid = $this->uid;
		$Course = Service::withoutGlobalScope('is_course')->where(['uid' => $uid, 'seo_url' => $seo_url])->first();
		if (empty($Course)) {
			return redirect(route('mycourses'));
		}

		if ($Course) {
			$Course->is_delete = 2;
			$Course->deleted_date = Carbon::now();
			$Course->save();
			Session::flash('errorSuccess', 'Course deleted successfully.');
		} else {
			Session::flash('errorFails', 'Something goes wrong.');
		}
		return redirect()->back();
	}

	/* Delete Section */
	public function delete_section($seo_url,$secret){
		$uid = $this->uid;
		$Course = Service::withoutGlobalScope('is_course')
			->select('id','seo_url','current_step','is_approved')
			->where(['uid' => $uid, 'seo_url' => $seo_url])
			->where('is_course', 1)
			->where('is_custom_order', 0)
			->where('is_job', 0)
			->where('is_delete',0)
			->where('status','!=','permanently_denied')
			->first();

		if(is_null($Course)) {
			Session::flash('errorFails', 'Invalid Course.');
			return response()->json(['status'=>false,'code'=>401,'message'=>'Invalid Course.','url',route('mycourses')]);
		}

		$content_id = CourseSection::getDecryptedId($secret);
		$courseSection = CourseSection::where('course_id',$Course->id)->where('id',$content_id)->first();
		
		if(is_null($courseSection)){
			return redirect()->response()->json(['status'=>false,'code'=>401,'message'=>'Something went wrong.','url'=>null]);
		}

		$contentMedia = ContentMedia::where('course_id',$Course->id)->where('course_content_id',$courseSection->id)->get();
		if($courseSection->is_approve == 0){
			foreach ($contentMedia as $key => $value) {
				//remove media from aws server
				$this->remove_file_on_aws($value->media_s3_key); // Delete old image
				if($value->media_thumbnail_key != ''){
					$this->remove_file_on_aws($value->media_thumbnail_key); // Delete old image
				}
				$value->delete();
			}
			$courseSection->delete();
			$message = "Course section deleted successfully.";
		}else{
			
			$courseSection->is_draft = 1;
			$courseSection->save();

			//Send for approval
			$Course->is_approved = 0;
			$Course->save();

			//$message = "Course will be delete permanently after approval by administrator.";
			$message = "Course section deleted successfully.";
		}
		return response()->json(['status'=>true,'code'=>200,'message'=>$message,'url'=>null]);
	}

	// Delete Content
	public function delete_content(Request $request) {
		$uid = $this->uid;
		$id = ContentMedia::getDecryptedId($request->id);
		$contentMedia = ContentMedia::find($id);

		if(is_null($contentMedia)){
			return redirect()->response()->json(['status'=>false,'code'=>401,'message'=>'Something went wrong.','url'=>null]);
		}

		//Verify course
		$Course = Service::withoutGlobalScope('is_course')
			->select('id','is_approved')
			->where('uid',$uid)
			->where('is_course',1)
			->where('id',$contentMedia->course_id)
			->first();

		if(count($Course) == 0) {
			Session::flash('errorFails', 'Invalid Course.');
			return response()->json(['status'=>false,'code'=>401,'message'=>'Invalid Course.','url'=>route('mycourses')]);
		}

		if($contentMedia->is_approve == 0){
			//remove media from aws server
			if($contentMedia->media_type == 'video'){
				$this->remove_file_on_aws($contentMedia->media_s3_key);
				if($contentMedia->media_thumbnail_key != ''){
					$this->remove_file_on_aws($contentMedia->media_thumbnail_key);
				}
			}
			$contentMedia->delete();
		}else{
			$contentMedia->is_approve = 0;
			$contentMedia->is_draft = 1;
			$contentMedia->save();

			//Send for approval
			$Course->is_approved = 0;
			$Course->save();
		}
		
		$message = "Content deleted successfully.";
		return response()->json(['status'=>true,'code'=>200,'message'=>$message,'url'=>null]);
	}

	/* Preview function */
	public function get_preview_content(Request $request){
		$uid = $this->uid;
		$id = ContentMedia::getDecryptedId($request->id);
		$contentMedia = ContentMedia::find($id);
		$duration = 0.00;

		if(empty($contentMedia)){
			return response()->json(['status'=>false,'code'=>400,'message'=>'something went wrong.']);
		}

		if($request->has('token') && $request->token != ""){
			$master_admin = Admin::where('email','info@demo.com')->select('email','password')->first();
			$encodedParam = json_encode($master_admin);
			$access_token = base64_encode($encodedParam);
			$access_token = str_replace("/","", $access_token);

			if($access_token != $request->token){
				return response()->json(['status'=>false,'code'=>400,'message'=>'something went wrong.']);
			}
		}elseif($request->has('course_id') && $request->course_id != ""){
			$course_id = Service::getDecryptedId($request->course_id);
			$course_purchase = Service::purchaseCourseDetails($course_id,$uid);
			if(!$course_purchase){
				return response()->json(['status'=>false,'code'=>400,'message'=>'something went wrong.']);
			}
			/* Update active content status */
			$active_content = LearnCourseContent::update_active_content_status($uid,$course_id,$id);
			$duration = $active_content->duration;
		}else{
			$is_description = false;
			if($request->has('is_description') && $request->is_description == true){
				$is_description = true;
			}
			if($is_description == false && $contentMedia->media_type == 'video' && $contentMedia->is_preview != 1){
				return response()->json(['status'=>false,'code'=>400,'message'=>'something went wrong.']);
			}
		}

		return response()->json(['status'=>true,'code'=>200,'name'=>$contentMedia->name,'type'=>$contentMedia->media_type,'link'=>$contentMedia->media_url,'article_text'=>$contentMedia->article_text,'duration'=>$duration,'video_description'=>$contentMedia->video_description]);
		
	}

	/* upload downloadable content */
	public function upload_downloadable_file(Request $request){
		$validator = Validator::make(
			[
				'file'   => $request->file,
				'extension' 	   => strtolower($request->file->getClientOriginalExtension()),
			],
			[
				'file'   => 'required',
				'extension'        => 'in:doc,csv,xlsx,xls,docx,ppt,zip,pdf',
			]
		);
		$file = $request->file;
		$duration = 0;

		/* BEGIN - Authenticate user */
		
		$id = ContentMedia::getDecryptedId($request->content_media_id);
		$contentMedia = ContentMedia::find($id);
		if(count($contentMedia)==0){
			return response()->json(['status'=>false,'code'=>400,'html'=>'','message'=>'Invalid course.']);
		}

		$uid = $this->uid;
		$course = Service::withoutGlobalScope('is_course')
		->where(['uid' => $uid, 'id' => $contentMedia->course_id])
		->where('is_course',1)
		->where('is_custom_order', 0)
		->where('is_job', 0)
		->where('is_delete',0)
		->where('status','!=','permanently_denied')
		->first();

		if(count($course) == 0){
			return response()->json(['status'=>false,'code'=>400,'html'=>'','message'=>'Invalid course.']);
		}
		/* END - Authenticate user */

		try{        
            $originalName   = $file->getClientOriginalName();
            $mime       = $file->getClientMimeType();
            $filesize   = formatBytes($file->getSize(),2);
            $ext        = $file->extension();
            $fileName   = time().rand().'.'.$ext;

			$downloadable_file_path = 'courses/downloadable_files';
			
			$file->move(public_path($downloadable_file_path), $fileName);
			$SourceFile = public_path($downloadable_file_path).'/'.$fileName;

			/* Generate media key */
			$fileKey = md5($course->id).'/'.md5(time()) . '.' . $ext;
			/* Uploading in aws */
			$result_amazonS3 = $this->saveOnAWS($SourceFile, $fileKey);
			if($result_amazonS3){
				
				$down_content = DownloadableContent::select('short_by')
				->where('course_id',$course->id)
				->where('course_content_id',$contentMedia->course_content_id)
				->where('content_media_id',$contentMedia->id)
				->orderBy('short_by','desc')
				->first();

				if(count($down_content)==0){
					$short_by = 1;
				}else{
					$short_by = $down_content->short_by+1;
				}
				
				$create = new DownloadableContent;
				$create->course_id     	= $course->id;
				$create->course_content_id = $contentMedia->course_content_id;
				$create->content_media_id = $contentMedia->id;
				$create->filename   	= $originalName;
				$create->file_size   	= $filesize;
				$create->file_mime   	= $mime;
				$create->file_s3_key   	= $fileKey;
				$create->url     	= $result_amazonS3['ObjectURL'];
				$create->short_by   	= $short_by;
				$create->save();
				
				/* remove local file */
				unlink($SourceFile);

				/* Response value */
				$downloadable_contents = DownloadableContent::where('course_id',$course->id)
				->where('course_content_id',$contentMedia->course_content_id)
				->where('content_media_id',$contentMedia->id)
				->where('is_draft',0)
				->orderBy('short_by','ASC')
				->paginate($this->limit)
				->appends($request->all())
				->withPath(route('course.get_downloadable_content'));

				$total_resources = $downloadable_contents->total();

				$course->is_approved = 0;
				$course->save();

				$view = view('frontend.course.include.downloadable_content_list',compact('downloadable_contents'))->render();
				return response()->json(['status'=>true,'code'=>200,'html'=>$view,'id'=>$contentMedia->secret,'total_resources'=>$total_resources,'message'=>'Downloadable content uploaded successfully.']);
			}
        }catch(\Exception $e){
            return response()->json(['success'=>false,'status'=>401,'message'=>'Something went wrong. Please try again.']);
        }
	}

	/* get downloadable content */
	public function get_downloadable_content(Request $request){
		$id = ContentMedia::getDecryptedId($request->content_media_id);
		$contentMedia = ContentMedia::find($id);
		if(count($contentMedia)==0){
			return response()->json(['status'=>false,'code'=>400,'html'=>'','message'=>'Invalid course.']);
		}

		$uid = $this->uid;
		$course = Service::withoutGlobalScope('is_course')
		->where(['uid' => $uid, 'id' => $contentMedia->course_id])
		->where('is_course',1)
		->where('is_custom_order', 0)
		->where('is_job', 0)
		->where('is_delete',0)
		->where('status','!=','permanently_denied')
		->first();

		if(count($course) == 0){
			return response()->json(['status'=>false,'code'=>400,'html'=>'','message'=>'Invalid course.']);
		}

		$downloadable_contents = DownloadableContent::where('course_id',$contentMedia->course_id)
		->where('course_content_id',$contentMedia->course_content_id)
		->where('content_media_id',$contentMedia->id)
		->where('is_draft',0)
		->orderBy('short_by','ASC')
		->paginate($this->limit)
		->appends($request->all());

		$total_resources = $downloadable_contents->total();

		$view = view('frontend.course.include.downloadable_content_list',compact('downloadable_contents'))->render();
		return response()->json(['status'=>true,'code'=>200,'html'=>$view,'total_resources'=>$total_resources]);
	}

	/* delete downloadable files */ 
	public function delete_downloadable_file($secret){
		
		$id = DownloadableContent::getDecryptedId($secret);
		$downloadable_content = DownloadableContent::where('id',$id)
				->where('is_draft',0)
				->orderBy('short_by','desc')
				->first();

		if(empty($downloadable_content)){
			return response()->json(['status'=>false,'code'=>400,'message'=>'Something went wrong.']);
		}

		$uid = $this->uid;
		$course = Service::withoutGlobalScope('is_course')
		->where(['uid' => $uid, 'id' => $downloadable_content->course_id])
		->where('is_course',1)
		->where('is_custom_order', 0)
		->where('is_job', 0)
		->where('is_delete',0)
		->where('status','!=','permanently_denied')
		->first();
		
		if(empty($course)){
			return response()->json(['status'=>false,'code'=>400,'message'=>'Invalid course.']);
		}

		if($downloadable_content->is_approve == 0){
			//remove media from aws server
			$this->remove_file_on_aws($downloadable_content->file_s3_key);
			$downloadable_content->delete();
		}else{
			$downloadable_content->is_draft = 1;
			$downloadable_content->save();
			
			/* Course go to approval */ 
			$course->is_approved = 0;
			$course->save();
		}

		return response()->json(['status'=>true,'code'=>200,'message'=>'File deleted successfully.']);

	}

	/* Courses listing */
	public function courses(Request $request) {

		$q = $request->get('q');

		/* Begin : Store search result*/
		$this->store_search_terms($request);
		/* End : Store search result*/

		/*begin : redirect to service details page*/
		if($q && $q != '' && $request->search_by == 'Courses' && $request->filled('service_id') && $request->service_id !=''){
			$serviceObj = Service::withoutGlobalScope('is_course')->where('is_course',1)->select('id','uid','seo_url')->find($request->service_id);
			if(isset($serviceObj->user->username) && isset($serviceObj->seo_url)) {
				return redirect()->route('course_details',[$serviceObj->user->username,$serviceObj->seo_url]);
			} else {
				return redirect()->back();
			}
		}
		/*end : redirect to service details page*/

		$getCategoryId = Category::withoutGlobalScope('type')->where('type',1)->where('seo_url', '=', $request->category)->first();
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

		$category_id = $request->get('categories') ? $request->get('categories') : $defaultCatId;
		$subcategory_id = $request->get('subcategories') ? $request->get('subcategories') : $defaultSubcatId;

		$this->store_search_category($category_id);

		$catid = Subcategory::where('id', $subcategory_id)->select('category_id')->get()->toArray();

		$current_category = $category_id;
		
		$rating_count = DB::Raw('ROUND(services.service_rating, 0) As service_round_rating');
		$Service = Service::withoutGlobalScope('is_course')->select('services.*', 'service_plan.price', $rating_count)
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
			'service_plan.plan_type'=> 'lifetime_access',
			'users.status'=> 1,
			'users.is_delete'=> 0,
			'users.vacation_mode'=> 0,
			'services.is_course'=> 1
			]);

		if ($q && $q != '') {
			$Service = $Service->where(function($query) use ($q,$request) {
				if($request->search_by == 'Courses'){
					if($request->filled('service_id') && $request->service_id !=''){
						$query->where('services.id', $request->service_id);
					}else{
						$query->where('services.title', 'LIKE', '%' . $q . '%');
						$query->orWhere('services.tags', 'LIKE', '%' . $q . '%');
					}
				}
			});
		}

		if ($category_id && $category_id != '') {
			$Service = $Service->where('services.category_id', $category_id);
		}

		if ($subcategory_id && $subcategory_id != '') {
			$Service = $Service->where('services.subcategory_id', $subcategory_id);
		}

		if ($request->get('min_price') && $request->get('min_price') != "") {
			$min_price = $request->get('min_price');
			$Service = $Service->whereHas('lifetime_plans', function($query)use($min_price,$request) {
				$query->select('id')->where('price', '>=', $min_price);
			});
		}

		if ($request->get('max_price') && $request->get('max_price') != "") {
			$max_price = $request->get('max_price');
			$Service = $Service->whereHas('lifetime_plans', function($query)use($max_price,$request) {
				$query->select('id')->where('price', '<=', $max_price);
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
		} else {
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

		$categories = Category::withoutGlobalScope('type')->where('type',1)->get();
		$subcategories = Subcategory::where('category_id', isset($catid[0]) ? $catid[0]['category_id'] : '0')->where('status',1)->get();
		$minPrice = DB::table('service_plan')->min('price');
		$maxPrice = DB::table('service_plan')->max('price');
		$languages = UserLanguage::select('language', 'id')->groupBy('language')->get();


		if ($request->ajax()) {
			$selectedCategory = Category::withoutGlobalScope('type')->where('type',1)->select('category_name')->where('id',$current_category)->first();
			$filterResponse['html_response'] = view('frontend.course.filter_courses', compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category'))->render();
			$filterResponse['category_name'] = (!empty($selectedCategory))?$selectedCategory->category_name:'';
			$filterResponse = mb_convert_encoding($filterResponse, 'UTF-8', 'UTF-8');
			return $filterResponse;
		}

		$imglink = GeneralSetting::where('settingkey','service_job_banner_img')->pluck('settingvalue')->first();
		$bannerlink = GeneralSetting::where('settingkey','service_job_banner_link')->pluck('settingvalue')->first();

		$forusByUsBanner = null;
		if($request->segment(1) == 'by_us_for_us') {
			$forusByUsBanner = GeneralSetting::whereIn('settingkey',['forusbyus_banner','forusbyus_text','forusbyus_text_color','forusbyus_bg_color','forusbyus_title_font_size','forusbyus_subtitle_text','forusbyus_subtitle_color','forusbyus_subtitle_font_size'])->get();
		}

		return view('frontend.course.view', compact('Service', 'categories', 'minPrice', 'maxPrice', 'languages', 'subcategories', 'current_category', 'getCategoryId','getSubCategoryId','defaultSubcatId','imglink','bannerlink','forusByUsBanner'));
	}

	/* SAVE term search data */
	function store_search_terms($request){
		if (Auth::check()) {
			$uid = $this->uid;
			$search_term = '';
			$q = $request->get('q');
			if($q && $q != ''){
				$search_term = $q;
				if($request->search_by == 'Courses' && $request->filled('service_id') && $request->service_id !=''){
					$serviceObj = Service::withoutGlobalScope('is_course')->select('title')->find($request->service_id);
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

	/* update user category */
	function store_search_category($categoryId=null){
		if (Auth::check() && $categoryId) {
			/*remove 7 days previous records*/
			UserSearchCategory::where('user_id',$this->uid)->where('updated_at', '<', Carbon::now()->subDays(7))->delete();
			UserSearchCategory::firstOrCreate(['user_id'=>$this->uid,'category_id'=>$categoryId]);
		}
	}

	/* Update content time */
	public function update_content_time(Request $request){
		$uid = $this->uid;
		$course_id = Service::getDecryptedId($request->course_id);
		$content_media_id = ContentMedia::getDecryptedId($request->content_media_id);
		$duration = $request->duration;
		return LearnCourseContent::where('user_id',$uid)->where('course_id',$course_id)->where('content_media_id',$content_media_id)->update(['duration'=>$duration]);
	}

	public function chunk_upload(Request $request){
		$uid = $this->uid;
		$course_id = Service::getDecryptedId($request->course_id);
		$course = Service::select('id')->where('id',$course_id)->where('uid',$uid);
		if(is_null($course)){
			return response()->json(['success'=>false,'status'=>401,'message'=>'Unauthorized access']);
		}

		// chunk variables
		$fileId = $request->dzuuid;
		$chunkIndex = $request->dzchunkindex + 1;

		$targetPath = public_path('courses/media').'/';
		$fileType = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
		$filename = "{$fileId}-{$chunkIndex}.{$fileType}";
		$targetFile = $targetPath . $filename;
		move_uploaded_file($_FILES['file']['tmp_name'], $targetFile);

		// Be sure that the file has been uploaded
		if ( !file_exists($targetFile) ) {
			return response()->json(['status'=>false,'code'=>401,'message'=>'File not upload']);
		}
		return response()->json(['status'=>true,'code'=>200,'message'=>'File uploaded successfully.']);
	}

	public function upload_course_video(Request $request){
		$fileId 	= $request->dzuuid;
		$chunkTotal = $request->dztotalchunkcount;
		$targetPath = public_path('courses/media').'/';
		$fileType 	= strtolower($request->extension);
		$originalName = $request->fileName;
		$media_path = 'courses/media';
		$thumb_source = "";

		$errorResponse = function ($message = "error",$success = false, $status = 401) {
			die (json_encode( ['success'=>$success,'status'=>$status,'message'=>$message] ));
		};

		// loop through temp files and grab the content
		for ($i = 1; $i <= $chunkTotal; $i++) {

			// target temp file
			$temp_file_path = realpath("{$targetPath}{$fileId}-{$i}.{$fileType}") or $errorResponse('Something went wrong. Please try again.');

			// copy chunk
			$chunk = file_get_contents($temp_file_path);
			if ( empty($chunk) ){
				$errorResponse('Something went wrong. Please try again.');
			} 

			// add chunk to main file
			file_put_contents("{$targetPath}{$fileId}.{$fileType}", $chunk, FILE_APPEND | LOCK_EX);

			// delete chunk
			unlink($temp_file_path);
			if ( file_exists($temp_file_path) ){
				$errorResponse('Something went wrong. Please try again.');
			}
		}

		$source_url = public_path("courses/media")."/"."{$fileId}.{$fileType}";
		$file_size = formatBytes(\File::size($source_url),2);
		$file_mime = \File::mimeType($source_url);
		$thumbnail_url = "";

		/* Get duration */
		$config_ffprobe = array(
			'ffmpeg.binaries'  => env('FFMPEG_PATH'), 
			'ffprobe.binaries' => env('FFPROBE_PATH'),
			'timeout'          => 3600, // The timeout for the underlying process
			 'ffmpeg.threads'   => 12);
		$duration = \FFMpeg\FFProbe::create($config_ffprobe)->format($source_url)->get('duration');

		// Video thumbnail
		$thumb_name = 'thumb_'.time().rand().'.png';
		$thumbnail_status = Thumbnail::getThumbnail($source_url,$targetPath,$thumb_name,env('TIME_TO_TAKE_SCREENSHOT'));
		if($thumbnail_status){
			$thumb_source = public_path("courses/media")."/".$thumb_name;
		}else{
			return response()->json(['success'=>false,'status'=>401,'message'=>'Something went wrong. Please try again.']);
		}

		// Local Thumbnail Url
		$thumbnail_url = url('public/'.$media_path.'/'.$thumb_name);
		$originalImage = Image::make($thumb_source);
		$originalImage->fit(env('THUMBNAIL_IMAGE_WIDTH'), env('THUMBNAIL_IMAGE_HEIGHT'),NULL,'top')->save($thumb_source,85);
		//End Generate thumbnail

		$main_file_link = url('/public/courses/media')."/{$fileId}.{$fileType}";

		$data['media_type']     = 'video';
		$data['media_size']     = $file_size;
		$data['media_mime']     = $file_mime;
		$data['originalName']   = $originalName;
		$data['extension']      = $fileType;
		$data['duration']      	= $duration;
		$data['source_file'] 	= $source_url;
		$data['source_url_thumb'] = $thumb_source;
		$data['media_path'] 	= $media_path;

		session()->put('course_media',$data);
		unset($data['duration']);
		unset($data['media_path']);
		unset($data['source_file']);

		/* Response value */
		$data['source_url_thumb'] = $thumbnail_url;
		$data['success']    = true;
		$data['status']     = 200;
		$data['message']    = 'Media uploaded successfully';
		return response()->json($data);
	}
}
