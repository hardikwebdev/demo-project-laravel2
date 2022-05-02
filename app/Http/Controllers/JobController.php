<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Aws\Exception\AwsException;
use App\Category;
use App\Subcategory;
use App\Service;
use App\ServicePlan;
use App\ServiceMedia;
use App\Order;
use AWS;
use Auth;
use Session;
use App\JobOffer;
use Validator;
use App\Notification;
use DB;
use App\User;
use App\Setting;
use App\SaveTemplate;
use App\SellerCategories;
use Illuminate\Support\Str;
use App\JobPromotedBidTransaction;
use Srmklive\PayPal\Services\ExpressCheckout;
use App\PaymentLog;
use App\BuyerTransaction;
use Carbon\Carbon;
use Obydul\LaraSkrill\SkrillClient;
use Obydul\LaraSkrill\SkrillRequest;
use App\SkrillTempTransaction;
use Redirect;
use App\Jobs\QueueEmails;
use App\Mail\SendEmailInQueue;
use App\Models\ServiceRevision;

class JobController extends Controller
{
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
        $this->provider = new ExpressCheckout();
    }
    public function index(request $request)
    {
    	$status = $request->input('status');    	


		$Service = Service::with('jobMedia', 'basic_plans')
		->where('uid', $this->uid)
		->where('is_job', 1);

		if ($status != null) {
            if($status == 'active' || $status == 'draft'){
                $Service = $Service->where(['status' => $status])->where('expire_on','>=',Carbon::now()->format('Y-m-d H:i:s'));
            }elseif($status == 'expired'){
                $Service = $Service->where('expire_on','<',Carbon::now()->format('Y-m-d H:i:s'))
                ->where(function($q1){
                    $q1->doesntHave('job_accepted')->orDoesntHave('job_offers');
                });
            }elseif($status == 'awarded'){
                $Service = $Service->whereHas('job_offers',function($q){
                    $q->select('id')->where('status','accepted');
                });
            }
		}

		$searchtxt = $request->input('searchtxt');

		if($request->searchtxt != null){
			$Service = $Service->where(function($query) use ($searchtxt)  {
				$query->where('title','LIKE', '%' . $searchtxt . '%')
				->orwhere('subtitle','LIKE', '%' . $searchtxt . '%')
				->orwhere('descriptions','LIKE', '%' . $searchtxt . '%');
			})->orderBy('id', 'desc');
		} 

		$Service = $Service->where('is_custom_order', 0)->where('is_job',1)
		->orderBy('id', 'desc')
		->paginate(20);
  
		return view('frontend.job.index', compact('Service'));
    }
    public function create()
    {
        //Admin can make user to soft ban , so user can't create new job
		if(User::is_soft_ban() == 1){
			return redirect()->route('jobs')->with('errorFails', get_user_softban_message());
		}

        /* Sub user check permission */ 
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}
        
    	$categories=Category::where('seo_url','!=','by-us-for-us')->pluck('category_name','id')->toArray();
    	return view('frontend.job.create_job',compact('categories'));
    }
    public function storeImage(request $request)
    {
    	$data='';    	
    	
    	if($request->hasFile('image'))
    	{

    		$images=$request->file('image');


    		if($images->getClientMimeType() == 'image/jpeg' || $images->getClientMimeType() == 'image/jpg' || $images->getClientMimeType() == 'image/png' || $images->getClientMimeType() == 'image/gif')
    		{
    			$file_type='image';
    		}

    		if($images->getClientMimeType() == 'application/pdf')
    		{
    			$file_type='pdf';
    		}
    		if($images->getClientMimeType() == 'application/doc' || $images->getClientMimeType() == 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
    		{
    			$file_type='doc';
    		}

    		if($images->getClientMimeType() == 'application/zip')
    		{
    			$file_type='zip';
    		}
    		if($images->getClientMimeType() == 'video/mp4')
    		{
    			$file_type='video';
    		}
       		$originalPath = public_path().'/services/images/';
            $name=time(). mt_rand(10000, 99999);
            $imageName = $name .'.'. $images->getClientOriginalExtension();
            $images->move($originalPath, $imageName);
            $path= url('images/'.$imageName);
            $data.='    <input type="hidden" name="imagesend[]" value="'.$imageName.'" data-type="'.$images->getClientMimeType().'" data-value="'.$name.'">
            <input type="hidden" name="type[]" value="'.$file_type.'" data-value="'.$name.'">';
    	}
		return response()->json(['data'=>$data]);
    }
    public function store(request $request)
    {
        //Admin can make user to soft ban , so user can't create new job
		if(User::is_soft_ban() == 1){
			return redirect()->route('jobs')->with('errorFails', get_user_softban_message());
		}

        /* Sub user check permission */ 
		if(User::check_sub_user_permission('allow_selling') == false){
			return redirect()->route('home');
		}
        
        if($request->min_price >= $request->max_price) {
            Session::flash('errorFails', 'Maximum price must be greater than minimum price.');
            return redirect()->back();
        }
        if(sizeof(explode(',',$request->tags)) > 3) {
            Session::flash('errorFails', 'Tags must not be greater than three.');
            return redirect()->back();
        }
        /* Add SEO URL */
        
		$seo_url = Str::slug($request->job_title, '-');

		$exists = Service::where('seo_url', $seo_url)->first();
		if (count($exists)) {
			$seoSlug = $seo_url . '-' . time();
		} else {
			$seoSlug = $seo_url;
        }

    	$service = new Service;
    	$service->uid = Auth::user()->id;
    	$service->title = $request->job_title;
    	$service->seo_url = $seoSlug;
    	$service->category_id = $request->category_id;
    	$service->subcategory_id = $request->subcategory_id;
    	$service->tags = $request->tags;
    	$service->descriptions = $request->description;
        $service->is_job = 1;
        $service->is_approved = 0;
    	$service->job_min_price = $request->min_price;
    	$service->job_max_price = $request->max_price;
        $service->status = $request->status;
    	$service->save();

        $seller_category = new SellerCategories;
        $seller_category->uid = Auth::user()->id;
        $seller_category->service_id = $service->id;
        $seller_category->category_id = $request->category_id;
        $seller_category->sub_category_id = $request->subcategory_id;
        $seller_category->is_default = true;
        $seller_category->save();

    	$servicePlan=new ServicePlan;
    	$servicePlan->service_id=$service->id;
    	$servicePlan->plan_type='basic';
    	$servicePlan->package_name = 'Job Package';
    	$servicePlan->price = $request->max_price;
    	$servicePlan->save();			

        /* Job Revisions */
        $job_revision = new ServiceRevision();
        $job_revision->service_id = $service->id;
        $job_revision->title = $service->title;
        $job_revision->category_id = $service->category_id;
        $job_revision->subcategory_id = $service->subcategory_id;
        $job_revision->descriptions = $service->descriptions;
        $job_revision->save();
        $service->is_revision_approved = 0; /* update service table */
        $service->save();
        /* Job Revisions */

		$bucket=env('bucket_order');
        
        for($media=0;$media < count($request->imagesend); $media++)
        {
            $destinationPath = public_path('/services/images/'.$request->imagesend[$media]);
            try {
                $s3 = AWS::createClient('s3');
                $imageKey = md5($request->service_id) . '/' . md5(time().rand()) . '.' . $request->imagesend[$media];
                $result_amazonS3 = $s3->putObject([
                    'Bucket' => $bucket,
                    'Key' => $imageKey,
                    'SourceFile' => $destinationPath,
                    'StorageClass' => 'REDUCED_REDUNDANCY',
                    'ACL' => 'public-read',
                ]);
                unlink($destinationPath);
                $media_url = $result_amazonS3['ObjectURL'];
                $photo_s3_key = $imageKey;

                $mediaTable=new ServiceMedia;
                $mediaTable->service_id=$service->id;
                $mediaTable->media_type=$request->type[$media];
                $mediaTable->media_url=$media_url;
                $mediaTable->photo_s3_key=$photo_s3_key;
                $mediaTable->save();

            } catch (Aws\S3\Exception\S3Exception $e) {
                echo "There was an error uploading the file.\n";
            }
        }
		
		
		/*foreach($request->imagesend as $key => $value) {
			$destinationPath = public_path('/services/images/'.$value);
			try {
				$s3 = AWS::createClient('s3');
				$imageKey = 'jobs/'.md5($service->id) . '/' . md5(time().rand()) . '.' . $value;
                
				$result_amazonS3 = $s3->putObject([
					'Bucket' => $bucket,
					'Key' => $imageKey,
					'SourceFile' => $destinationPath,
					'StorageClass' => 'REDUCED_REDUNDANCY',
					'ACL' => 'public-read',
				]);
				unlink($destinationPath);
				$media_url = $result_amazonS3['ObjectURL'];
				$photo_s3_key = $imageKey;

				$mediaTable=new ServiceMedia;
				$mediaTable->service_id=$service->id;
				$mediaTable->media_type=$request->type[$key];
				$mediaTable->media_url=$media_url;
				$mediaTable->photo_s3_key=$photo_s3_key;
				$mediaTable->save();

			} catch (Aws\S3\Exception\S3Exception $e) {
				echo "There was an error uploading the file.\n";
			}
		}*/
		Session::flash('errorSuccess', 'Job added successfully.');
		return redirect()->route('jobs');
    }
    //delete the jobs
    public function delete($secret)
    {
        $id = Service::getDecryptedId($secret);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }
    	$uid = $this->uid;

		$bucket=env('bucket_order');
		$Service = Service::where(['uid' => $uid, 'id' => $id])
        ->where(function($q){
            $q->where('expire_on','>=',Carbon::now()->format('Y-m-d H:i:s'));
            $q->orWhereNull('expire_on');
        })
        ->first();

		if (empty($Service)) {
            Session::flash('errorFails', 'Something goes wrong.');
			return redirect(route('jobs'));
		}

		if ($Service) {
            /* Delete Revision history */
            ServiceRevision::where('service_id',$Service->id)->delete();

            /*$jobOffer=JobOffer::where('service_id',$id)->first();
            if(count($jobOffer) > 0)
            {
                Session::flash('errorFails', 'This job is relation with other offers.');
            }
            else
            {*/
                $orders = Order::where(['service_id' => $id])->first();
                if (count($orders) > 0) {
                    Session::flash('errorFails', 'This job is relation with other orders.');
                } else {
                    $ServiceMedia = ServiceMedia::where('service_id', $id)->get();
                    if (count($ServiceMedia) > 0) {
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

                            }
                        }
                        ServiceMedia::where('service_id', $id)->delete();
                        // ServiceQuestion::where('service_id', $id)->delete();
                    }

                    /*delete all offers/notifications*/
                    JobOffer::where('service_id',$id)->delete();
                    Notification::where('order_id',$id)->where('type','job_proposal_send')->delete();
                    
                    Service::where('id', $id)->delete();
                    Session::flash('errorSuccess', 'Job removed successfully.');
                }
            /*}*/
		} else {
			Session::flash('errorFails', 'Something goes wrong.');
		}
		return redirect()->back();
    }

    //edit the jobs
    public function edit($seo_url)
    {
        //Admin can make user to soft ban , so user can't edit job
		if(User::is_soft_ban() == 1){
			return redirect()->route('jobs')->with('errorFails', get_user_softban_message());
		}

    	$uid = $this->uid;
		$service = Service::with('images', 'basic_plans')
        ->where(['uid' => $uid, 'seo_url' => $seo_url])
        ->where(function($q){
            $q->where('expire_on','>=',Carbon::now()->format('Y-m-d H:i:s'));
            $q->orWhereNull('expire_on');
        })
        ->first();

        if($service)
        {
            if($service->is_job == 0)
            {
                return redirect('404');
            }
        }

        /* Check revision */ 
        if($service->revisions != null){
            $service->title = $service->revisions->title;
            $service->category_id = $service->revisions->category_id;
            $service->subcategory_id = $service->revisions->subcategory_id;
            $service->descriptions = $service->revisions->descriptions;
        }

		$categories=Category::where('seo_url','!=','by-us-for-us')->pluck('category_name','id')->toArray();
		$selected_sub=Subcategory::where('category_id',$service->category_id)->pluck('subcategory_name' ,'id')->toArray();
		$service_imgs = [];
        if($service)
        {
		$service_imgs=ServiceMedia::where('service_id',$service->id)->get();
        }
        return view('frontend.job.edit',compact('categories','service','selected_sub','service_imgs'));
    }

    /*remove selected media from bucket as well as database table*/
    public function remove_media($secret)
    {
        $id = ServiceMedia::getDecryptedId($secret);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }
    	$uid = $this->uid;
    	$bucket=env('bucket_order');
    	$Service = ServiceMedia::where(['id' => $id])->first();
		if (empty($Service)) {
			return redirect()->back();
		}

			$row = ServiceMedia::where('id', $id)->first();
				if ($row) {
					

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
			
					// ServiceMedia::where('service_id', $id)->delete();
					// ServiceQuestion::where('service_id', $id)->delete();
				}

				ServiceMedia::where('id', $id)->delete();

				Session::flash('errorSuccess', 'Media removed successfully.');
				return redirect()->back();
		}
    }

     /*update the jobs*/
    public function update(request $request)
    {
        //Admin can make user to soft ban , so user can't edit job
		if(User::is_soft_ban() == 1){
			return redirect()->route('jobs')->with('errorFails', get_user_softban_message());
		}
        
        $id = Service::getDecryptedId($request->service_id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }

        $service = Service::select('*')
        ->where(function($q){
            $q->where('expire_on','>=',Carbon::now()->format('Y-m-d H:i:s'));
            $q->orWhereNull('expire_on');
        })
        ->find($id);
        if(empty($service))
        {
            return redirect('404');
        }

        if(sizeof(explode(',',$request->tags)) > 3) {
            Session::flash('errorFails', 'Tags must not be greater than three.');
            return redirect()->back();
        }

        $is_update = false;
        // $setting = Setting::find(1);
        // $jobModrate =  explode(",",$setting->meta_job_moderate);
        // $jobModrateExist = false;
        // $job_title = strtolower($request->job_title);
        // $job_description = strtolower($request->description);
        // foreach ($jobModrate as $key => $value) {
        //     $lower_Value = strtolower(trim($value));
        //     if (preg_match("/\b".$lower_Value."\b/", $job_title)|| preg_match("/\b".$lower_Value."\b/", $job_description) ) {
        //         $jobModrateExist = true;
        //     }
        // }
        
        /* Check revision update */
        if($service->title != $request->job_title || $service->category_id != $request->category_id || $service->subcategory_id != $request->subcategory_id || $service->descriptions != $request->description){
            $is_update = true;
        }

        if($service->is_approved != 1){
            $is_update = true;
            $service->title = $request->job_title;
            $service->category_id = $request->category_id;
            $service->subcategory_id = $request->subcategory_id;
            $service->descriptions = $request->description;
        }

        $service->uid = Auth::user()->id;
    	$service->tags = $request->tags;
        // $service->is_approved = ($jobModrateExist == true) ? 0 : 1;
    	$service->is_job = 1;
    	$service->job_min_price = $request->min_price;
    	$service->job_max_price = $request->max_price;
    	$service->status = $request->status;
    	$service->save();

        SellerCategories::where('service_id',$id)
                        ->update(['category_id'=>$request->category_id,'sub_category_id'=>$request->subcategory_id]);

    	$updServicePlan=ServicePlan::where('service_id',$id)->first();

    	$servicePlan=ServicePlan::find($updServicePlan->id);
    	$servicePlan->service_id=$id;
    	$servicePlan->plan_type='basic';
    	$servicePlan->package_name = 'Job Package';
    	$servicePlan->price = $request->max_price;
    	$servicePlan->save();
			
    	$bucket=env('bucket_order');
    
        for($media=0;$media < count($request->imagesend); $media++)
        {
            $destinationPath = public_path('/services/images/'.$request->imagesend[$media]);
            try {
                $s3 = AWS::createClient('s3');
                $imageKey = md5($id) . '/' . md5(time().rand()) . '.' . $request->imagesend[$media];
                $result_amazonS3 = $s3->putObject([
                    'Bucket' => $bucket,
                    'Key' => $imageKey,
                    'SourceFile' => $destinationPath,
                    'StorageClass' => 'REDUCED_REDUNDANCY',
                    'ACL' => 'public-read',
                ]);
                unlink($destinationPath);
                $media_url = $result_amazonS3['ObjectURL'];
                $photo_s3_key = $imageKey;

                $mediaTable=new ServiceMedia;
                $mediaTable->service_id=$id;
                $mediaTable->media_type=$request->type[$media];
                $mediaTable->media_url=$media_url;
                $mediaTable->photo_s3_key=$photo_s3_key;
                $mediaTable->save();

            } catch (Aws\S3\Exception\S3Exception $e) {
                echo "There was an error uploading the file.\n";
            }
        }

        if($is_update == true){
            /* Job Revisions */
            $job_revision = ServiceRevision::where('service_id',$service->id)->first();
            
            if(!$job_revision){
                $job_revision = new ServiceRevision();
                $job_revision->service_id = $service->id;
            }
            $job_revision->title = $request->job_title;
            $job_revision->category_id = $request->category_id;
            $job_revision->subcategory_id = $request->subcategory_id;
            $job_revision->descriptions = $request->description;
            $job_revision->is_approved = 0;
            $job_revision->save();
            $service->is_revision_approved = 0; /* update service table */
            $service->save();
            /* Job Revisions */
        }

		Session::flash('errorSuccess', 'Job updated successfully.');
		return redirect()->route('jobs');
    }

    /*return list of all the jobs available to user*/
    public function browseJob(request $request)
    {
    	$uid = $this->uid;
        $limit=7;
        $offset = 0;

        if($request->filled('offset') && $request->ajax()){
            $offset = $request->offset;
        }
    	
        $jobs=Service::with('images', 'basic_plans','user','job_accept','category')
        ->where('is_job',1)
        ->where('status','active')
        ->where('is_approved', 1)  // 1 Approve 0 = Admin need to aproove
        ->where('is_delete', 0) 
        ->where('expire_on','>=',Carbon::now()->format('Y-m-d H:i:s'))
        ->orderBy('id','desc');
        
        /* Check Blocked Users */
        $block_users = User::getBlockedByIds();
        if(count($block_users)>0){
			$jobs = $jobs->whereNotIn('uid',$block_users); /* Check Blocked Users */
		}

    	$jobs=$jobs->whereHas('user', function($query) {
			$query->select('id')->where('status', 1)->where('is_delete', 0)->where('vacation_mode', 0);
		});

        $jobs=$jobs->where(function($query){
            $query->doesntHave('job_accept','or',function($q1){
                $q1->where('status','accepted');
            });
        });

        if($request->filled('category_search') && $request->category_search != null)
        {
            $getCat=Subcategory::where('category_id',$request->category_search)->pluck('id'); 
            $jobs=$jobs->whereIn('subcategory_id',$getCat);           
        }          

    	if($request->filled('subcat') && $request->subcat != null)
    	{
    		$jobs=$jobs->whereIn('subcategory_id',$request->subcat);
    	}

    	if($request->filled('skills') && $request->skills != null)
    	{
            if(strpos($request->skills, ' ') !== false){
                $tag = explode(" ",$request->skills);
            } else if(strpos($request->skills, ',') !== false){
                $tag = explode(",",$request->skills);
            } else {
                $tag = [$request->skills];
            }
    		foreach ($tag as $key => $value) {
    			$jobs= $jobs->where('tags', 'like',  "%{$value}%");
    		}
        }
   
    	if($request->filled('time') && $request->time != null && $request->time != 'any_time')
    	{
            
            $key = $request->time;

            $now = \Carbon\Carbon::now();
            if($key=='today')
            {
                $dates=date('Y-m-d');
            }
            else if($key==3)
            {
                $dates = date('Y-m-d', strtotime('-3 days', strtotime(date('Y-m-d'))));
            }
            else if($key==5)
            {
               $dates = date('Y-m-d', strtotime('-5 days', strtotime(date('Y-m-d')))); 
            }
            else if($key==10)
            {
                $dates = date('Y-m-d', strtotime('-10 days', strtotime(date('Y-m-d')))); 
            }
            else if($key==15)
            {
                $dates = date('Y-m-d', strtotime('-15 days', strtotime(date('Y-m-d')))); 
            }
            else if($key==20)
            {
                $dates = date('Y-m-d', strtotime('-20 days', strtotime(date('Y-m-d')))); 
            }
            
            if($key=='today')
            {
                $jobs= $jobs->whereDate('created_at',date('Y-m-d'));
            }
            else
            {
                $jobs= $jobs->whereBetween('created_at', [$dates,date("Y-m-d", time() + 86400)]);
            }
    	}
        

    	if($request->filled('min_price') && $request->min_price != null)
    	{
    		$jobs=$jobs->where('job_min_price','>=',$request->min_price);
    	}
    	if($request->filled('max_price') && $request->max_price != null)
    	{
    		$jobs=$jobs->where('job_max_price','<=',$request->max_price);	
    	}
    	
        $total_result=$jobs->count();

    	/*$jobs=$jobs->offset($offset)->limit($limit)->get();    	*/
        $jobs=$jobs->paginate(20);     

    	if($request->ajax())
    	{
            if(count($jobs) > 0){
                $display_loader = false;
                if($total_result == ($offset+$limit))
                {
                    $display_loader = false;
                }
                else
                {
                    if(count($jobs) == $limit){
                        $display_loader = true;
                    }    
                }
                $display_loader = false;

                $html=view('frontend.job.dyanamic_section',compact('jobs'))->render();    

                return response()->json(['success'=>true,'html'=>$html,'offset'=>$offset+$limit,'display_loader'=>$display_loader,'total_result'=>$total_result]);
            }else{
                return response()->json(['success'=>false]);
            }


    		/*$count = count($jobs);
    		$html=view('frontend.job.dyanamic_section',compact('jobs'))->render();
    		return response()->json(['html'=>$html,'count' => $count]);*/
    	}
    	
		$categories=Category::with(['jobs'=>function($q){
            $q->where('expire_on','>=',Carbon::now()->format('Y-m-d H:i:s'));
            $q->where('is_approved',1);
        }])->where('seo_url','!=','by-us-for-us')->select('*')->get();

        if(Session::has('job_url'))
        {
            $dyanmicJob=Session::get('job_url');
            if($dyanmicJob != '0')
            {   
                    Session::forget('job_url'); 
                    return redirect()->route('show.job_detail',$dyanmicJob);   
            }   
            else
            {
                Session::forget('job_url'); 
            }
        }
        
    	return view('frontend.job.browse_job',compact('jobs','categories','limit','total_result'));
    }

    /*search browseJob  */
    public function browseJobSearch(request $request)
    {
    	/*echo "string";
    	exit();*/
    }
    /*show all the details of particular job*/
    public function showJobDetail(request $request,$seo)
    {

        $job=Service::where('seo_url',$seo)
        ->where('status','active')
        ->where('is_job',1)
        ->where('is_delete', 0) 
       /* ->where(function($q){
            $q->where('expire_on','>=',Carbon::now()->format('Y-m-d H:i:s'))
            ->orWhereHas('job_offers',function($q1){
                $q1->where('status','accepted');
            });
        })*/
        ->first();

        if($job->uid != $this->uid && $job->is_approved == 0){
			return redirect('404');
        }

        if(count($job) == 0){
            return redirect('404');
        }
        /* Check Blocked Users */
        $block_users = User::getBlockedByIds();
        if(in_array($job->uid,$block_users)){
            abort('401');
        }

        $serviceUser = User::select('id')->where('id',$job->uid)->where('status', 1)->where('is_delete', 0)->where('soft_ban',0)->where('vacation_mode', 0)->first();
		if(is_null($serviceUser)) {
			return redirect('404');
		}

        $is_promoted_bid_exist = JobOffer::select('id')->where('service_id',$job->id)->where('is_promoted_job',1)->count();

        if($request->filled('notification_id') && $request->notification_id != null)
        {
            $updNotify=Notification::find($request->notification_id);
            $updNotify->is_read = 1;
            $updNotify->save();
        }
        $save_template_chat = SaveTemplate::where('seller_uid',$job->uid)
        ->where('template_for',2)
        ->orderBy('title', 'asc')
        ->pluck('title', 'id')
        ->toArray();

    	return view('frontend.job.job_detail',compact('job','save_template_chat','is_promoted_bid_exist'));
    }

    /*store proposal send by user to buyer requesting for job*/
    public function storeJobProposal(request $request)
    {
        
        //Admin can make user to soft ban , so user can't give offer on job
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('errorFails', get_user_softban_message());
		}
        
        if($request->filled('offer_id'))
    	{
            $offer_id = JobOffer::getDecryptedId($request->offer_id);
            try{
                if(empty($offer_id)){
                    return redirect()->back();
                }
            }catch(\Exception $e){
                return redirect()->back();
            }
        }
        $service_id = Service::getDecryptedId($request->service_id);
        try{
            if(empty($service_id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }
        
        /* Check sub user limitation */ 
        if(Auth::user()->parent_id != 0) {
            if(User::check_sub_user_permission('can_make_purchases',$request->price) == false) {
                $notEnoughBalanceMsg = User::get_subuser_remaining_budget_message();
                return redirect()->back()->with('errorFails', $notEnoughBalanceMsg);
            }
        }
        /* END Check sub user limitation */ 

        /*begin : check for job is accepted*/
        $job=Service::where('is_job',1)
        ->whereHas('job_accept',function($q){
            $q->select('id')->where('status','accepted');
        })->find($service_id);

        if(count($job) > 0){
            \Session::flash('tostError', 'This job already accepted.');
            return redirect()->back();
        }
        /*end : check for job is accepted*/

        $is_promoted_bid_exist = JobOffer::where('service_id',$service_id)->where('is_promoted_job',1)->count();
        $uid = $this->uid;
        $payable = env('JOB_PROMOTE_BID_FEE');
        $user = User::where('id',$uid)->first();
        $invoice_id = "LE".get_microtime();

        
        $job=Service::where('is_job',1)
        ->where('status','active')
        ->where('is_delete',0)
        ->where('expire_on','>=',Carbon::now()->format('Y-m-d H:i:s'))
        ->find($service_id);

        if(empty($job)){
            \Session::flash('tostError', 'This job no longer available.');
            return redirect()->back();
        }

        $buyer_id = $job->uid;

        /*Check Block User*/
        $block_users = User::getBlockedByIds();
        if(in_array($buyer_id,$block_users)){
            \Session::flash('tostError', 'Your account is blocked by user.');
            return redirect()->route('home');
        }

    	if($request->filled('offer_id'))
    	{
    		$data=JobOffer::find($offer_id);
	    	$data->seller_id=$uid;
	    	$data->buyer_id=$buyer_id;
	    	$data->service_id=$service_id;
	    	$data->delivery_days=$request->days;
	    	$data->description=$request->description;
	    	$data->price=$request->price;
	    	$data->status='pending';
	    	$data->save();	
    	}
    	else
    	{
            //paypal payment for promote bid
            if($request->filled('promote_bid') && $request->promote_bid == 'on' && $is_promoted_bid_exist == 0) {
                /* payment code - start */
                //wallet payment for promote bid
                if($request->payment_by == 'wallet') { //from wallet
                    if($user->earning >= $payable) {
                        $txn_id = $this->generate_txnid();

                        //update wallet
                        $user->earning = $user->earning - $payable;
                        $user->pay_from_wallet = $user->pay_from_wallet + $payable;
                        $user->save();

                        $data=new JobOffer;
                        $data->seller_id=$uid;
                        $data->buyer_id=$buyer_id;
                        $data->service_id=$service_id;
                        $data->delivery_days=$request->days;
                        $data->description=$request->description;
                        $data->price=$request->price;
                        $data->status='pending';
                        $data->is_promoted_job = 1;
                        $data->save();

                        // create bid transaction
                        $promoted_bid = new JobPromotedBidTransaction;
                        $promoted_bid->job_offer_id = $data->id;
                        $promoted_bid->amount = $payable;
                        $promoted_bid->payment_by = 1;
                        $promoted_bid->transaction_id = $txn_id;
                        $promoted_bid->invoice_id = $invoice_id;
                        $promoted_bid->receipt = null;
                        $promoted_bid->save();
                        
                        // create buyer transaction
                        $buyerTransaction = new BuyerTransaction;
                        $buyerTransaction->buyer_id = $user->id;
                        $buyerTransaction->note = 'Promoted bid on job offer from Wallet';
                        $buyerTransaction->anount = $payable;
                        $buyerTransaction->status = 'promote_bid_on_job';
                        $buyerTransaction->created_at = time();
                        $buyerTransaction->save();

                        /* Send 0.10% in Jim Acc. */
                        $serviceChargeJim = ($payable * env('JIM_CHARGE_PER')) / 100;
                        $UserJim = User::select('id','earning','net_income')->where('id', '38')->first();
                        if (!empty($UserJim)) {
                            $UserJim->earning = $UserJim->earning + $serviceChargeJim;
                            $UserJim->net_income = $UserJim->net_income + $serviceChargeJim;
                            $UserJim->save();

                            /* Jim Commission Transactions Start */
                            $buyerTransaction = new BuyerTransaction;
                            $buyerTransaction->buyer_id = $UserJim->id;
                            $buyerTransaction->note = 'Commission for the bid promotion on job offer #' . $txn_id;
                            $buyerTransaction->anount = $serviceChargeJim;
                            $buyerTransaction->status = 'promote_bid_on_job';
                            $buyerTransaction->created_at = time();
                            $buyerTransaction->save();
                            /* Jim Commission Transactions End */
                        }

                        /* Send wallet transaction email to admin */
                        $wallet_transaction_history['buyer'] = $user->username;
                        $wallet_transaction_history['buyer_email'] = $user->email;
                        $wallet_transaction_history['seller'] = $job->user->username;
                        $wallet_transaction_history['seller_email'] = $job->user->email;
                        $wallet_transaction_history['invoice_id'] = "";
                        $wallet_transaction_history['transaction_id'] = $txn_id;
                        $wallet_transaction_history['total_amount'] = round($payable,2);
                        $wallet_transaction_history['reason'] = "bid on job";
                        $wallet_transaction_history['transactions'][] = [
                            'title' => $job->title,
                            'price' => $payable,
                            'quantity' 	=> 1,
                            'total' => round($payable,2)
                        ];
                        Order::sendWalletTransactionEmail($wallet_transaction_history);
                    }
                } 
                elseif($request->payment_by == 'skrill'){
                    $payable = (float)$payable;
                    $processing_fee = calculate_payment_processing_fee($payable);
                    // Start Payment Process
                    $totalAmount = $payable + $processing_fee;
                    $this->skrilRequest = new SkrillRequest();
                    $this->skrilRequest->pay_to_email = env('MERCHANT_EMAIL');
                    $this->skrilRequest->logo_url = url("public/frontend/assets/img/logo/LogoHeader.png");
                    $this->skrilRequest->transaction_id = $invoice_id;
                    $this->skrilRequest->currency = 'USD';
                    $this->skrilRequest->language = 'EN';
                    $this->skrilRequest->prepare_only = '1';
                    $this->skrilRequest->merchant_fields = 'demo';
                    $this->skrilRequest->site_name = 'demo';
                    $this->skrilRequest->customer_email = $user->email;
                    $this->skrilRequest->detail1_description = "Promote Bid On Job Offer";
                    $this->skrilRequest->detail1_text = $payable;
                    $this->skrilRequest->detail2_description = "Payment Processing Fee";
                    $this->skrilRequest->detail2_text = $processing_fee;
                    $this->skrilRequest->amount = round_price($totalAmount);
                    $this->skrilRequest->return_url = route('skrill.thankyou');
                    $this->skrilRequest->cancel_url = route('browse.job');
                    $this->skrilRequest->status_url = url('skrill/ipn');
                    $this->skrilRequest->status_url2 = '';

                    try{
                        // create object instance of SkrillClient
                        $client = new SkrillClient($this->skrilRequest);
                        $sid = $client->generateSID(); //return SESSION ID
                
                        // handle error
                        $jsonSID = json_decode($sid);
                        if ($jsonSID != null && $jsonSID->code == "BAD_REQUEST"){
                            return redirect()->back()->with('errorFails', 'Something went wrong with Skrill');;
                        }

                        $request->request->add(['buyer_id' => $buyer_id,'service_id'=>$service_id]);
                        $sessionData = [
                            'paypal_custom_data' => $request->all()
                        ];
                        $tempData = new SkrillTempTransaction;
                        $tempData->merchanttransactionid = $invoice_id;
                        $tempData->user_id = $user->id;
                        $tempData->cart_data = json_encode($sessionData);
                        $tempData->payment_for = 5;
                        $tempData->save();
        
                        $redirectUrl = $client->paymentRedirectUrl($sid); //return redirect url
                        return Redirect::to($redirectUrl); // redirect user to Skrill payment page
                       
                    }catch(\Exception $e){
                        return redirect()->back()->with('errorFails', 'Something went wrong with Skrill');
                    }

                }
                else { //from paypal
                    $payable = (float)$payable;
                    $processing_fee = calculate_payment_processing_fee($payable);
                    $request_data = [];
                    $request_data['items'] = [
                        [
                            'name' => 'Promote Bid On Job Offer',
                            'price' => $payable,
                            'qty' => 1
                        ],
                        [
                            'name' => 'Payment Processing Fee',
                            'price' => $processing_fee,
                            'qty' => 1
                        ]
                    ];
                    $request_data['invoice_id'] = $invoice_id;
                    $request_data['invoice_description'] = "Promote Bid On Job Offer #".$invoice_id;
                    $request_data['return_url'] = route('paypal_express_checkout_promote_bid_success');
                    $request_data['cancel_url'] = route('browse.job');
                    $request_data['total'] = $payable + $processing_fee;
                    try{
                        $response = $this->provider->setExpressCheckout($request_data,true);
                        /*Create Log for payment request data*/
                        $log = new PaymentLog;
                        $log->user_id = $user->id;
                        $log->receipt = json_encode($response);
                        $log->status = "Request data";
                        $log->payment_for = "promote_bid_on_job";
                        $log->save();

                    }catch(\Exception $e){
                        // error
                    }

                    if ($response['paypal_link']) {
                        $input_session_data = $request->all();
                        $input_session_data['buyer_id'] = $buyer_id;
                        $input_session_data['service_id'] = $service_id;
                        Session::put($invoice_id,$input_session_data);
                        return redirect($response['paypal_link']);
                    }
                }
                /* payment code - end */
            } else {
                $data=new JobOffer;
                $data->seller_id=$uid;
                $data->buyer_id=$buyer_id;
                $data->service_id=$service_id;
                $data->delivery_days=$request->days;
                $data->description=$request->description;
                $data->price=$request->price;
                $data->status='pending';
                $data->is_promoted_job = 0;
                $data->save();
            }	

            $service=Service::select('id','title','seo_url')->find($service_id);

            $notification = new Notification;
            $notification->notify_to = $buyer_id;
            $notification->notify_from = $uid;
            $notification->notify_by = 'seller';
            $notification->order_id = $service_id;
            $notification->is_read = 0;
            $notification->type = 'job_proposal_send';
            $notification->message = 'Proposal for job  #' . $service->title . ' received';
            $notification->created_at = time();
            $notification->updated_at = time();
            $notification->save();

            $buyerUser=User::select('id','email')->where('id',$buyer_id)->first();
            $data = [
                'receiver_secret' => $buyerUser->secret,
				'email_type' => 3,
                'subject' => 'Job Proposal Offer',
                'template' => 'frontend.emails.v1.job_proposal_offer',
                'email_to' => $buyerUser->email,
                'username' => $user->username,
                'service_title' => $service->title,
                'service_delivery_days' => $request->days,
                'service_delivery_price' => $request->price,
                'service_delivery_description' => $request->description,
                'service_detail' => 'Proposal for job  #' . $service->title . ' received',
                'order_detail_url' => url('/show/job_detail/' . $service->seo_url)
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));
        }
    	
    	\Session::flash('tostSuccess', 'Proposal sent successfully.');
		return redirect()->route('seller.mybids');
    }

    function generate_txnid() {
    	return "TXN" . time() . 'WL' . rand('11', '99');
    }

    /* promote bid for job - paypal response */
	public function expressCheckoutPromoteBidSuccess(Request $request) {
        //Admin can make user to soft ban , so user can't give offer on job
		if(User::is_soft_ban() == 1){
			return redirect()->back()->with('errorFails', get_user_softban_message());
		}
        
		$payable = env('JOB_PROMOTE_BID_FEE');
        $payable = (float)$payable;
        $processing_fee = calculate_payment_processing_fee($payable);

        $uid = $this->uid;
        $user = User::where('id',$uid)->first();

		if (!$user) {
		    return redirect()->back()->with('errorFails', 'Something goes wrong.');
        }
        
		$token = $request->get('token');
		$PayerID = $request->get('PayerID');

		$response = $this->provider->getExpressCheckoutDetails($token);

		$profile_desc = $response['DESC'];

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $uid;
		$log->receipt = json_encode($response);
		$log->status = "Payment response";
		$log->payment_for = "promote_bid_on_job";
		$log->save();

		if (!in_array(strtoupper($response['ACK']), ['SUCCESS', 'SUCCESSWITHWARNING'])) {
			return redirect()->back()->with('errorFails', 'Error processing PayPal payment');
		}

		$invoice_id = $response['INVNUM'];

		if (!Session::has($invoice_id)) {
		    return redirect()->back()->with('errorFails', 'Error processing PayPal payment');
        }
        
        $requestFormData = Session::get($invoice_id);
		$request_data = [];
		$request_data['items'] = [
			[
				'name' => 'Promote Bid On Job Offer',
				'price' => $payable,
				'qty' => 1
            ],
            [
                'name' => 'Payment Processing Fee',
                'price' => $processing_fee,
                'qty' => 1
            ]
		];
		$request_data['invoice_id'] = $invoice_id;
		$request_data['invoice_description'] = "Promote Bid On Job Offer #".$invoice_id;
		$request_data['return_url'] = route('paypal_express_checkout_promote_bid_success');
		$request_data['cancel_url'] = route('browse.job');
		$request_data['total'] = $payable + $processing_fee;

		$payment_status = $this->provider->doExpressCheckoutPayment($request_data, $token, $PayerID);

		/*Create Log for payment response*/
		$log = new PaymentLog;
		$log->user_id = $uid;
		$log->receipt = json_encode($payment_status);
		$log->status = "Payment response verification";
		$log->payment_for = "promote_bid_on_job";
		$log->save();

		if (isset($payment_status['ACK']) && $payment_status['ACK'] == 'Failure') {
			$this->send_failed_notification($uid);
			return redirect()->route('view_cart')->with('errorFails', 'Something went wrong with PayPal');
		}

		$status = $payment_status['PAYMENTINFO_0_PAYMENTSTATUS'];
		$txn_id = $payment_status['PAYMENTINFO_0_TRANSACTIONID'];

        Session::forget($invoice_id);
		if ($status == 'Completed') {
            //create job offer
            $data=new JobOffer;
            $data->seller_id=$uid;
            $data->buyer_id=$requestFormData['buyer_id'];
            $data->service_id=$requestFormData['service_id'];
            $data->delivery_days=$requestFormData['days'];
            $data->description=$requestFormData['description'];
            $data->price=$requestFormData['price'];
            $data->status='pending';
            $data->is_promoted_job = 1;
            $data->save();

			// create bid transaction
			$promoted_bid = new JobPromotedBidTransaction;
			$promoted_bid->job_offer_id = $data->id;
			$promoted_bid->amount = $payable;
			$promoted_bid->payment_by = 0;
            $promoted_bid->transaction_id = $txn_id;
            $promoted_bid->invoice_id = $invoice_id;
			$promoted_bid->receipt = json_encode($payment_status);
			$promoted_bid->save();
			
			// create buyer transaction
			$buyerTransaction = new BuyerTransaction;
			$buyerTransaction->buyer_id = $uid;
			$buyerTransaction->note = 'Promoted bid on job offer from Paypal';
			$buyerTransaction->anount = $payable;
			$buyerTransaction->status = 'promote_bid_on_job';
			$buyerTransaction->created_at = time();
            $buyerTransaction->payment_processing_fee = $processing_fee;
			$buyerTransaction->save();

			/* Send 0.10% in Jim Acc. */
			$serviceChargeJim = ($payable * env('JIM_CHARGE_PER')) / 100;
			$UserJim = User::select('id','earning','net_income')->where('id', '38')->first();
			if (!empty($UserJim)) {
				$UserJim->earning = $UserJim->earning + $serviceChargeJim;
				$UserJim->net_income = $UserJim->net_income + $serviceChargeJim;
				$UserJim->save();

				/* Jim Commission Transactions Start */
				$buyerTransaction = new BuyerTransaction;
				$buyerTransaction->buyer_id = $UserJim->id;
				$buyerTransaction->note = 'Commission for the bid promotion on job offer #' . $txn_id;
				$buyerTransaction->anount = $serviceChargeJim;
				$buyerTransaction->status = 'promote_bid_on_job';
				$buyerTransaction->created_at = time();
				$buyerTransaction->save();
				/* Jim Commission Transactions End */
            }
            
            $service=Service::select('id','title','seo_url')->find($requestFormData['service_id']);

            $notification = new Notification;
            $notification->notify_to = $requestFormData['buyer_id'];
            $notification->notify_from = $uid;
            $notification->notify_by = 'seller';
            $notification->order_id = $requestFormData['service_id'];
            $notification->is_read = 0;
            $notification->type = 'job_proposal_send';
            $notification->message = 'Proposal for job  #' . $service->title . ' received';
            $notification->created_at = time();
            $notification->updated_at = time();
            $notification->save();

            $buyerUser=User::select('id','email')->where('id',$requestFormData['buyer_id'])->first();
            $data = [
                'receiver_secret' => $buyerUser->secret,
				'email_type' => 3,
                'subject' => 'Job Proposal Offer',
                'template' => 'frontend.emails.v1.job_proposal_offer',
                'email_to' => $buyerUser->email,
                'username' => $user->username,
                'service_title' => $service->title,
                'service_delivery_days' => $requestFormData['days'],
                'service_delivery_price' => $requestFormData['price'],
                'service_delivery_description' => $requestFormData['description'],
                'service_detail' => 'Proposal for job  #' . $service->title . ' received',
                'order_detail_url' => url('/show/job_detail/' . $service->seo_url)
            ];
            QueueEmails::dispatch($data, new SendEmailInQueue($data));

		}
		\Session::flash('tostSuccess', 'Proposal sent successfully.');
		return redirect()->route('seller.mybids');
	}

    /*shows edit form of proposal if user needs to update proposal detail*/
    public function showEditForm(request $request)
    {
        $id = JobOffer::getDecryptedId($request->id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }
        
        $getData=JobOffer::where('id',$id)->first();
    	$html=view('frontend.job.dyanamicEdit_section',compact('getData'))->render();
    	return response()->json(['html' => $html]);
    }
    /*delete proposal by user if he/she wants to delete that particular proposal*/
    public function destroyProposal(request $request)
    {
        $id = JobOffer::getDecryptedId($request->id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }
        $getData=JobOffer::find($id);

        /*begin : check for job is accepted*/
        $job=Service::where('is_job',1)->whereHas('job_accept',function($q){
            $q->select('id')->where('status','accepted');
        })->find($getData->service_id);

        if(count($job) > 0){
            \Session::flash('tostError', 'This job already accepted.');
            return response()->json(['success' => false]);
        }
        /*end : check for job is accepted*/

    	$getData->delete();

    	\Session::flash('tostSuccess', 'Proposal deleted successfully.');
    	return response()->json(['success' => true]);
    }
    /*functions accepts ny buyers that accept proposal of seller according to his or her requirement*/
    public function acceptProposal(request $request)
    {
        $getData=JobOffer::find($request->id);
        $getData->status='new';
        $getData->save();

        \Session::flash('tostSuccess', 'Proposal request sent successfully.');
        return response()->json(['success' => true]);
    }

    /*here seller can reject the proposal awarted by buyer if he/she wanted*/
    public function rejectProposalSeller(request $request)
    {
        $id = JobOffer::getDecryptedId($request->id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }
        $getData=JobOffer::find($id);
        $getData->status='cancelled';
        $getData->save();
        \Session::flash('tostSuccess', 'Proposal request rejected successfully.');
        return response()->json(['success' => true]);
    }   

    /*here seller can accept the proposal awarted by buyer if he/she wanted*/
    public function acceptProposalSeller(request $request)
    {
        $id = JobOffer::getDecryptedId($request->id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }
        $getData=JobOffer::find($id);
        $getData->status='accepted';
        $getData->save();
        \Session::flash('tostSuccess', 'Proposal request accepted successfully.');
        return response()->json(['success' => true]);
    }
    public function showBuyersPropsal()
    {
          $jobs=JobOffer::with('service','user','buyer');
          $jobs=$jobs->where('buyer_id',Auth::user()->id);
          $jobs=$jobs->orderBy('id','desc')->paginate(20);
          return view('frontend.job.job_proposals',compact('jobs'));
    }
    /*function used for get dyanamic data and return to view of seller profile*/
    public function loadSeller(request $request)
    {
        $seller=User::where('id',$request->id)->first();
        $html=view('frontend.job.dyanamicseller',compact('seller'))->render();
        return $html;
    }

    public function jobRepost(request $request,$seo_url){
        $uid = $this->uid;
        $job = Service::where('expire_on','<',Carbon::now()->format('Y-m-d H:i:s'))
        ->where('is_job',1)
        ->where('is_repost',0)
        ->where(['uid' => $uid, 'seo_url' => $seo_url])
        ->first();

        if(empty($job))
        {
            \Session::flash('tostError', 'Job not longer available.');
            return redirect('404');
        }
        
        /*Clone previous job*/

        $seo_url = Str::slug($job->title, '-');
        $seoSlug = $seo_url . '-' . time();

        $service = new Service;
        $service->uid = Auth::user()->id;
        $service->title = $job->title;
        $service->seo_url = $seoSlug;

        $service->category_id = $job->category_id;
        $service->subcategory_id = $job->subcategory_id;
        $service->tags = $job->tags;
        $service->descriptions = $job->descriptions;
        $service->is_job = 1;
        $service->is_approved = $job->is_approved;
        $service->job_min_price = $job->job_min_price;
        $service->job_max_price = $job->job_max_price;
        $service->status = $job->status;
        $service->expire_on = Carbon::now()->addDays(30);
        $service->save();

        $job->is_repost = 1;
        $job->save();

        $oldSellerCategories = SellerCategories::where('service_id',$job->id)->first();
        if(!empty($oldSellerCategories)){
            $seller_category = new SellerCategories;
            $seller_category->uid = Auth::user()->id;
            $seller_category->service_id = $service->id;
            $seller_category->category_id = $oldSellerCategories->category_id;
            $seller_category->sub_category_id = $oldSellerCategories->sub_category_id;
            $seller_category->is_default = $oldSellerCategories->is_default;
            $seller_category->save();
        }

        $oldServicePlan = ServicePlan::where('service_id',$job->id)->first();
        if(!empty($oldServicePlan)){
            $servicePlan=new ServicePlan;
            $servicePlan->service_id=$service->id;
            $servicePlan->plan_type='basic';
            $servicePlan->package_name = 'Job Package';
            $servicePlan->price = $oldServicePlan->price;
            $servicePlan->save(); 
        }

        $oldServicePlan = ServiceMedia::where('service_id',$job->id)->get();
        if(count($oldServicePlan) > 0){
            foreach ($oldServicePlan as $media) {
                $mediaTable=new ServiceMedia;
                $mediaTable->service_id=$service->id;
                $mediaTable->media_type=$media->media_type;
                $mediaTable->media_url=$media->media_url;
                $mediaTable->photo_s3_key=$media->photo_s3_key;
                $mediaTable->save();
            }
        }

        \Session::flash('tostSuccess', 'Job repost successfully.');
        return redirect()->route('jobs.edit',$service->seo_url);
    }

    public function update_job_bid_rating(Request $request) {
        $id = JobOffer::getDecryptedId($request->bid_id);
        try{
            if(empty($id)){
                return response()->json(['status'=>'error']);
            }
        }catch(\Exception $e){
            return response()->json(['status'=>'error']);
        }
        $getBidData=JobOffer::where('id',$id)->where('buyer_id',$this->uid)->first();
        if(is_null($getBidData)) {
            return response()->json(['status'=>'error']);
        }
        $getBidData->rating = $request->rating;
        $getBidData->save();
        return response()->json(['status'=>'success']);
    }

    public function hide_job_bid(Request $request,$secret) {
        $id = JobOffer::getDecryptedId($secret);
        try{
            if(empty($id)){
                return response()->json(['status'=>'error']);
            }
        }catch(\Exception $e){
            return response()->json(['status'=>'error']);
        }
        $getBidData=JobOffer::where('id',$id)->where('buyer_id',$this->uid)->first();
        if(is_null($getBidData)) {
            return response()->json(['status'=>'error']);
        }
        $getBidData->is_hide = 1;
        $getBidData->save();
        \Session::flash('tostSuccess', 'Job bid hidden successfully.');
        return response()->json(['status'=>'success']);
    }
}
