<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\BundleDiscount;
use App\BundleService;
use Auth;
use Session;
use App\Service;
use Redirect;

class CourseBundleDiscountController extends Controller
{
    private $uid;
    private $type = 'courses';
    public function __construct(){
		 $this->middleware(function ($request, $next) {
            if(Auth::user()->parent_id == 0 && Auth::user()->is_premium_seller() == false){
            	return response()->view('errors.404', [], 404);
            }
            $this->uid = Auth::user()->id;
            if(Auth::user()->parent_id != 0){
                $this->uid = Auth::user()->parent_id;
            }
            return $next($request);
        });
	}

    public function index(Request $request)
    {
     	$model = BundleDiscount::where('type',1)->where(['user_id' => $this->uid])
        ->orderBy('updated_at', 'desc')
        ->paginate(20);
        $type = $this->type;
        return view('frontend.service.bundle_discount.index', compact('model','type'));
    }
    public function create(){
    	$serviceList = Service::withoutGlobalScope('is_course')
            ->where('uid',$this->uid)
            ->where('is_custom_order', 0)
            ->where('is_recurring', 0)
            ->where('is_job', 0)
            ->where('is_delete', 0)
            ->where('is_approved', 1)
            ->where('is_private', 0)
            ->where('is_course', 1)
            ->whereIn('status', ['active'])
            ->pluck('title','id')
            ->toArray();

        $type = $this->type;

    	return view('frontend.service.bundle_discount.create',compact('serviceList','type'));
    }
    public function store(Request $request){

        for($i=0;$i<count($request->service_ids);$i++)
        {
            $checkServiceId=BundleService::where('service_id',$request->service_ids[$i])->first();
            if($checkServiceId)
            {
                
                return Redirect::back()->withErrors(['Course is already added in other combo']);
            }
        }
        
        $checkExists = $this->checkDiscount($request);
        $jsonResponse = json_decode($checkExists->getContent(), true);

        if($jsonResponse['valid']){
            $model = new BundleDiscount;
            $model->user_id = $this->uid;
            $model->discount = $request->discount;
            $model->type = 1;
            if($request->filled('is_combined_other') && $request->is_combined_other == 1){
                $model->is_combined_other = 1;
            }else{
                $model->is_combined_other = 0;    
            }
            $model->save();

            if($request->filled('service_ids') && count($request->service_ids) > 0){
                foreach ($request->service_ids as $key => $value) {
                    $bundleService = new BundleService;
                    $bundleService->bundle_id = $model->id;
                    $bundleService->service_id = $value;
                    $bundleService->save();
                }
            }
            $message = 'Combo discount created successfully.';
            return redirect()->route('course.offer_bundle_discount')->with('errorSuccess',$message);
        }else{
            return redirect()->back()->with('errorFails',$jsonResponse['message']);
        }
    }

    public function edit(Request $request,$id){
        $model = BundleDiscount::where('type',1)->where('user_id',$this->uid)->find($id);
        if(count($model) > 0){
            $selectedServiceIds = [];
            foreach($model->bundle_services as $bundle){
                $selectedServiceIds[] = $bundle->service_id;
            }

            $serviceList = Service::withoutGlobalScope('is_course')
            ->where('uid',$this->uid)
            ->where('is_custom_order', 0)
            ->where('is_recurring', 0)
            ->where('is_job',0)
            ->where('is_approved', 1)
            ->where('is_delete', 0)
            ->where('is_private', 0)
            ->where('is_course', 1)
            ->whereIn('status', ['active'])
            ->pluck('title','id')
            ->toArray();
            $type = $this->type;
            return view('frontend.service.bundle_discount.edit',compact('serviceList','model','selectedServiceIds','type'));
        }else{
            return response()->view('errors.404', [], 404);
        }
    }

    public function update(Request $request){
        $checkExists = $this->checkDiscount($request);
        $jsonResponse = json_decode($checkExists->getContent(), true);

        if($jsonResponse['valid']){
            $model = BundleDiscount::where('type',1)->where('user_id',$this->uid)->find($request->id);
            if(count($model) > 0){
                $model->discount = $request->discount;
                
                if($request->filled('is_combined_other') && $request->is_combined_other == 1){
                    $model->is_combined_other = 1;
                }else{
                    $model->is_combined_other = 0;    
                }
                $model->save();

                BundleService::where('bundle_id',$model->id)->delete();
                if($request->filled('service_ids') && count($request->service_ids) > 0){
                    foreach ($request->service_ids as $key => $value) {
                        $bundleService = new BundleService;
                        $bundleService->bundle_id = $model->id;
                        $bundleService->service_id = $value;
                        $bundleService->save();
                    }
                }
                $message = 'Combo discount updated successfully.';
                return redirect()->route('course.offer_bundle_discount')->with('errorSuccess',$message);
            }else{
                return response()->view('errors.404', [], 404);
            }
        }else{
            return redirect()->back()->with('errorFails',$jsonResponse['message']);
        }
    }

    public function checkDiscount(Request $request){
        $isValid = true;
        $message = '';
        if($request->filled('service_ids') && count($request->service_ids) > 0 && $request->filled('discount') && $request->discount !=''){
           
            $service = Service::withoutGlobalScope('is_course')
            ->where('uid',$this->uid)
            ->where('is_custom_order', 0)
            ->where('is_recurring', 0)
            ->where('is_job',0)
            ->where('is_approved', 1)
            ->where('is_delete', 0)
            ->where('is_course', 1)
            ->whereIn('status', ['active'])
            ->whereIn('id', $request->service_ids)
            ->get();
            foreach ($service as $key => $value) {
                if($isValid == false){
                    continue;
                }
                if($value->basic_plans){
                    $discount = ($value->basic_plans->price * $request->discount)/100;
                    $afterDiscountPrice = $value->basic_plans->price - $discount;
                    if($afterDiscountPrice < env('MINIMUM_SERVICE_PRICE')){
                        $isValid = false;
                        $message = 'Service price must be $'.env('MINIMUM_SERVICE_PRICE').' after discount.';
                    }
                }
            }
        }
        return response()->json(['valid' => $isValid, 'message' => $message]);
    }

    public function delete(Request $request,$id){
        $model = BundleDiscount::where('type',1)->where('user_id',$this->uid)->find($id);
        if($model){
        	/*Remove related Services*/
        	BundleService::where('bundle_id',$model->id)->delete();
            $model->delete();
            return redirect()->back()->with('errorSuccess',"Combo offer deleted successfully.");
        }else{
            return redirect()->back()->with('errorFails',"Something goes wrong.");
        }
    }
}
