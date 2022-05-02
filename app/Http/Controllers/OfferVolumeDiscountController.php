<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\VolumeDiscount;
use Auth;
use Session;
use App\Service;

class OfferVolumeDiscountController extends Controller
{
    private $uid;
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

    public function index(Request $request, $service_id)
    {
        $service = Service::where('uid',$this->uid)->where('is_recurring',0)->find($service_id);
        if($service){
            $model = VolumeDiscount::where('service_id',$service_id)
            ->where(['user_id' => $this->uid])
            ->orderBy('volume', 'asc')
            ->paginate(20);

            return view('frontend.service.volume_discount.index', compact('model','service'));
        }else{
            return response()->view('errors.404', [], 404);
        }
    }
    public function store(Request $request)
    {

        $checkExists = $this->checkExists($request);
        $jsonResponse = json_decode($checkExists->getContent(), true);

        if($jsonResponse['valid']){
            if($request->id){
                $model = VolumeDiscount::find($request->id);
                $message = 'Offer Volume discount updated successfully.';
            }else{
                $model = new VolumeDiscount;
                $model->user_id = $this->uid;
                $model->service_id = $request->service_id;
                $message = 'Offer Volume discount created successfully.';
            }
            
            if($request->filled('is_combined_other') && $request->is_combined_other == 1){
                $model->is_combined_other = 1;
            }else{
                $model->is_combined_other = 0;    
            }

            $model->volume = $request->volume;
            $model->discount = $request->discount;
            $model->save();

            return redirect()->back()->with('errorSuccess',$message);
        }else{
            return redirect()->back()->with('errorFails',$jsonResponse['message']);
        }
    }
    public function delete(Request $request,$id){
        $model = VolumeDiscount::where('user_id',$this->uid)->find($id);
        if($model){
            $model->delete();
            return redirect()->back()->with('errorSuccess',"Volume Offer deleted successfully.");
        }else{
            return redirect()->back()->with('errorFails',"Something goes wrong.");
        }
    }

    public function checkExists(Request $request) {
        
        $model = VolumeDiscount::where('user_id',$this->uid)
            ->where('service_id', $request->service_id)
            ->where('volume', $request->volume);

        if($request->id){
            $model = $model->where('id','!=',$request->id);
        }
        $model = $model->first();
        if ($model === null) {
            $isValid = true;
            $message = '';
        } else {
            $isValid = false;
            $message = 'No of Service is already exists.';
        }
        return response()->json(['valid' => $isValid, 'message' => $message]);
    }

}
