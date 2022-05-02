<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\User;
use Carbon\Carbon;
use Session;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\ServiceFAQ;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class FAQController extends Controller {

    private $uid;

	public function __construct(){
		$this->middleware(function ($request, $next) {
            $this->uid = Auth::user()->id;
            if(Auth::user()->parent_id != 0){
                $this->uid = Auth::user()->parent_id;
            }
            return $next($request);
        });
    }
    
    /* view FAQ page */
    public function get_faq(Request $request, $seo_url) {
        $uid = $this->uid;
        $Service = Service::where('uid',$uid)->where('seo_url',$seo_url)->first();
        if(is_null($Service)) {
            Session::flash('errorFails', 'Invalid Service.');
			return redirect()->route('services');
        }
        $faqs = ServiceFAQ::where('service_id',$Service->id)->whereHas('service', function($query) use($uid) {
			$query->where('uid', $uid)->select('id');
		})->get();
        return view('frontend.service.faq.index', compact('faqs','Service'));
    }

    /* save faq */
    public function save_faq(Request $request) {
        $uid = $this->uid;
        $validator = Validator::make($request->all(), [
            'service_seo' => 'required',
            'question' => 'required|max:255',
            'answer' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $service = Service::where('seo_url',$request->service_seo)->where('uid',$uid)->first();
        if(is_null($service)) {
            Session::flash('errorFails', 'Invalid Service.');
			return redirect()->back();
        }

        $check_total_faqs = ServiceFAQ::where('service_id',$request->service_id)->count();
        if($check_total_faqs >= 10) {
            Session::flash('errorFails', 'Can not add more than 10 FAQs for a service.');
			return redirect()->route('get_faq',$service->seo_url);
        }

        $faq = new ServiceFAQ;
        $faq->service_id = $service->id;
        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->save();

        $service->last_updated_on = Carbon::now()->format('Y-m-d H:i:s');
        $service->save();

        Session::flash('errorSuccess', 'FAQ added successfully.');
        return redirect()->route('get_faq',$service->seo_url);
    }

    /* save edit faq */
    public function update_faq(Request $request) {
        $uid = $this->uid;
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'question' => 'required',
            'answer' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $faq = ServiceFAQ::where('id',$request->id)->whereHas('service', function($query) use($uid) {
			$query->where('uid', $uid)->select('id');
		})->first();
        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->save();

        $service = Service::where('id',$faq->service_id)->where('uid',$uid)->select('id','seo_url')->first();
        $service->last_updated_on = Carbon::now()->format('Y-m-d H:i:s');
        $service->save();

        Session::flash('errorSuccess', 'FAQ edited successfully.');
        return redirect()->route('get_faq',$service->seo_url);
    }

    /* delete faq */
    public function delete_faq(Request $request, $enc_id) {
        $uid = $this->uid;
        $id = ServiceFAQ::getDecryptedId($enc_id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }
        $faq = ServiceFAQ::where('id',$id)->whereHas('service', function($query) use($uid) {
			$query->where('uid', $uid)->select('id');
		})->select('id','service_id')->first();
        $service = Service::where('id',$faq->service_id)->where('uid',$uid)->select('id')->first();

        $faq->delete();
        
        $service->last_updated_on = Carbon::now()->format('Y-m-d H:i:s');
        $service->save();

        Session::flash('errorSuccess', 'FAQ deleted successfully.');
        return redirect()->back();
    }

    /* get faq details */
    public function get_faq_details(Request $request) {
        $uid = $this->uid;
        $id = ServiceFAQ::getDecryptedId($request->id);
        try{
            if(empty($id)){
                return redirect()->back();
            }
        }catch(\Exception $e){
            return redirect()->back();
        }
        $faq = ServiceFAQ::where('id',$id)->whereHas('service', function($query) use($uid) {
			$query->where('uid', $uid)->select('id');
		})->select('id','question','answer')->first();
        return response()->json(['status'=>'success','data'=>$faq]);
    }
}