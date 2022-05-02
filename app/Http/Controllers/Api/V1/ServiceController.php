<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Service;
use App\ServiceMedia;
use App\ServicePlan;
use Illuminate\Support\Facades\DB;
use App\User;
use App\ServiceExtra;
use App\Order;
use App\Message;
use App\MessageDetail;
use Auth;
use AWS;
use Carbon\Carbon;
use Aws\Exception\AwsException;
use Session;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ServiceController extends Controller {

    private $uid;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->uid = Auth::user()->id;
            $this->uid_secret = Auth::user()->secret;
            if (Auth::user()->parent_id != 0) {
                $this->uid = Auth::user()->parent_id;
                $parentUser = User::select('id')->find(Auth::user()->parent_id);
                $this->uid_secret = $parentUser->secret;
            }
            return $next($request);
        });
    }

    /* api for get login user's services */
    public function get_my_service_list(Request $request) {
		$uid = $this->uid;

		$services_data = Service::statusof('service')
		->where('services.uid', $uid)
		->select('id','uid','title','seo_url','service_rating as rating','total_review_count as total_review')
		->get()
        ->append('ServiceTitle');

        foreach ($services_data as $key => $value) {
            $image_url = url('public/frontend/assets/img/No-image-found.jpg');
            if(isset($value->images[0])) {
                if($value->images[0]->photo_s3_key != '') {
                    $image_url = $value->images[0]->media_url;
                } else {
                    $image_url = url('public/services/images/'.$value->images[0]->media_url);
                }
            }
            $value->image_url = $image_url;
            if(isset($value->user->username) && isset($value->seo_url)) {
                $value->service_url  = route('services_details',[$value->user->username,$value->seo_url]);
            } else {
                $value->service_url = "";
            }
            $value->price = isset($value->basic_plans->price)?$value->basic_plans->price:'0.0';
            unset($value->user);
            unset($value->images);
            unset($value->basic_plans);
        }

		return response([
            "code" => 200,
            'success' => true,
            'services' => $services_data
        ],200);
	}
}