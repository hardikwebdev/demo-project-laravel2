<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Onboarding;
use Illuminate\Http\Response;
use Validator;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $onBoardings = Onboarding::select('title', 'description', 'button_text', 'button_url', 'slug' )->get();

        return response([
            "success" => true,
            "message" => "Onboarding added successfully.",
            'onBoarding' => $onBoardings,
            "code" => 200
        ], 200);
    }

}
