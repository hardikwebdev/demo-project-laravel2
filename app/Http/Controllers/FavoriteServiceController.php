<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\FavoriteService;
use Auth;
use App\User;
use App\Service;

class FavoriteServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $uid = get_user_id();
        $services = FavoriteService::with('favoriteservice',"favoriteservice.user", 'favoriteservice.category', 'favoriteservice.images', 'favoriteservice.basic_plans')
        ->where('user_id',$uid)->where('service_type',0);

        /*Check block by user*/
		$block_users = User::getBlockedByIds();
        $type = $request->type;
        $services = $services->whereHas('favoriteservice', function($query) use($block_users,$type){
            $query->where('status', 'active')->select('id');
            if(count($block_users) > 0){
                $query->whereNotIn('uid',$block_users); /* Check Blocked Users */
            }
            if($type == 'courses'){
                $query->where('is_course', 1);
            }else{
                $query->where('is_course', 0);
            }
        });
        $services = $services->orderBy('id', 'desc')
        ->paginate(10)
        ->appends($request->all());

        return view('frontend.buyer.favorite_service',compact('services'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * mark or unmark service favorite for user.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function favorite(Request $request)
    {
    	$input = $request->all();
    	$validator = Validator::make($request->all(), [
    		'service_id' => 'required',
    		'status' => 'required',
    	]);
    	if($validator->fails())
    	{
    		return Response()->json(['status'=>false,'message'=>'Validation Error']);
    	}
    	else
    	{
            $service = Service::withoutGlobalScope('is_course')->select('id','is_course')->find($input['service_id']);
            if($service){
                $message = "Service";
                if($service->is_course == 1){
                    $message = "Course";
                }
                if($input['status'] == "0")
                {
                    $count = FavoriteService::where('user_id',Auth::user()->id)->where("service_id",$service->id)->count();
                    if($count == 0)
                    {
                        $favorite = new FavoriteService;
                        $favorite->user_id = Auth::user()->id;
                        $favorite->service_id = $service->id;
                        $favorite->save();
                        return Response()->json(['status'=>true,'message'=> $message.' added to your favorite.']);
                    }
                }
                else
                {
                    $service = FavoriteService::where('user_id',Auth::user()->id)->where("service_id",$service->id)->first();
                    if(!empty($service))
                    {
                        $service->delete();
                        return Response()->json(['status'=>true,'message'=>$message.' removed from your favorite.']);
                    }
                }
            }
    	}
    	return Response()->json(['status'=>false,'message'=>'Something goes wrong']);
    }
}
