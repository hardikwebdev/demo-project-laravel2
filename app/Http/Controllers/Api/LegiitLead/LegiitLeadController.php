<?php

namespace App\Http\Controllers\Api\demoLead;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\demoLeadCategory;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Service;

class demoLeadController extends Controller
{
    public function service_list(Request $request) {
        $validator = Validator::make($request->all(), [
            'search_type' => 'required',
        ]);

        if ($validator->fails()) {
            return response(['success' => false, 'message' => $validator->errors()->first(), "code" => 400], 400);
        }

        $cat_data = demoLeadCategory::where('category_slug',$request->search_type)->select('id','service_ids','category_slug')->first();

        if(is_null($cat_data->service_ids)) {
            return response([
                "code" => 200,
                'success' => true,
                'services' => []
            ],200);
        }

        $services = Service::whereIn('id',$cat_data->service_ids)
                            ->select('id','uid','title','descriptions','seo_url','service_rating as rating','total_review_count as total_review')
                            ->with('user:id,Name,username,seller_level')
                            ->inRandomOrder()->limit(5)->get();
        foreach ($services as $key => $value) {
            $value->title = ucwords($value->title);
            $value->amount = isset($value->basic_plans->price)?$value->basic_plans->price:'0.0';
            $value->service_image = get_service_image_url($value);
			$value->user_image = get_user_profile_image_url($value->user);
            
            $value->service_link = route('services_details',[$value->user->username,$value->seo_url]).'?utm_source=demoleads&utm_term='.get_utm_term_for_demo_lead_category($cat_data->category_slug);

            unset($value->id);
            unset($value->uid);
            unset($value->user->id);
            unset($value->images);
            unset($value->basic_plans);
        }

        return response([
            "code" => 200,
            'success' => true,
            'services' => $services
        ],200);
    }
}