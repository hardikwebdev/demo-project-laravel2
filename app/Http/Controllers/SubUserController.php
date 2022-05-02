<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Country;
use Auth;
use Session;
use Illuminate\Support\Facades\Hash;
use App\SubUserPermission;
use App\SubUserTransaction;
use Carbon\Carbon;

class SubUserController extends Controller
{
    public function __construct(){
		 $this->middleware(function ($request, $next) {
            if(Auth::user()->is_premium_seller() == false){
            	//return response()->view('errors.404', [], 404);
            }
            return $next($request);
        });
	}

	public function index(Request $request)
    {
    	$user  = Auth::user();
     	$model = User::with('sub_user_permissions')->where(['parent_id' => $user->id])
        ->orderBy('id', 'desc')
        ->paginate(10);
        foreach ($model as $key => $value) {
            $value->sub_user_permissions->used_monthly_budget = 0;
            if($value->sub_user_permissions->can_make_purchases > 0 && count($value->sub_user_transactions) > 0) {
                $current_month = Carbon::now()->format('m');
                $current_year = Carbon::now()->format('Y');
                
                $total_used_amount = SubUserTransaction::where('sub_user_id',$value->id)->whereMonth('created_at',$current_month)->whereYear('created_at',$current_year)->sum('used_amount');

                $value->sub_user_permissions->used_monthly_budget = $total_used_amount;
            }
        }
        if($request->ajax())
		{
			return view('frontend.subusers.subusers_table',compact('model'))->render();
		}
        return view('frontend.subusers.index', compact('model'));
    }

    public function create(){

    	$user  = Auth::user();
    	$country = Country::pluck('country_name', 'id')->toArray();
    	return view('frontend.subusers.create',compact('country'));
    }
    public function store(Request $request){
        $user  = Auth::user();

        $checkExists = $this->alreadyEmail($request);

        $jsonResponse = json_decode($checkExists, true);

        if($jsonResponse['valid'] == false){
        	return redirect()->back()->with('errorFails',$jsonResponse['message']);
        }

       /* $checkExists = $this->alreadyUser($request);
        $jsonResponse = json_decode($checkExists, true);

        if($jsonResponse['valid'] == false){
        	return redirect()->back()->with('errorFails',$jsonResponse['message']);
        }*/
        
		// $this->validate($request, [
		// 	'email' => 'required|string|email|max:255|unique:users',
		// ]);
        
        $status = 0;
        if($request->filled('status') && $request->status == 1){
            $status = 1;
        }

    	$model = new User;
        $model->parent_id = $user->id;
        $model->Name = $request->name;
        $model->email = $request->email;
        $model->username = $request->email;
        $model->password = Hash::make($request->password);
        $model->city = $user->city;
        $model->state = $user->state;
        $model->address = $user->address;
        $model->country_id = $user->country_id;
        $model->status = $status;
        $model->save();

        $user_permision = new SubUserPermission;
        $user_permision->subuser_id = $model->id;
        /* Seller Permission */ 
        if($request->filled('is_seller_subuser') && $request->is_seller_subuser == "1") {
            $user_permision->is_seller_subuser = 1;
        }
        /* Buyer Permission */ 
        if($request->filled('is_buyer_subuser') && $request->is_buyer_subuser == "1") {
            $is_buyer_permission = false;

            if($request->filled('can_make_purchases') && $request->can_make_purchases == "1") {
                if($request->filled('add_unlimited_purchase') && $request->add_unlimited_purchase == "-1") {
                    $user_permision->can_make_purchases = -1;
                } else if($request->filled('add_monthly_budget')){
                    $user_permision->can_make_purchases = $request->add_monthly_budget;
                }
                $is_buyer_permission = true;
            }

            if($request->filled('add_can_use_wallet_funds') && $request->add_can_use_wallet_funds == "1") {
                $user_permision->can_use_wallet_funds = 1;
                $is_buyer_permission = true;
            }

            if($request->filled('add_can_start_order') && $request->add_can_start_order == "1") {
                $user_permision->can_start_order = 1;
                $is_buyer_permission = true;
            }

            if($request->filled('add_can_communicate_with_seller') && $request->add_can_communicate_with_seller == "1") {
                $user_permision->can_communicate_with_seller = 1;
                $is_buyer_permission = true;
            }

            if($is_buyer_permission != false){
                $user_permision->is_buyer_subuser = 1;
            }
        }
        $user_permision->save();

        $message = 'Sub user created successfully.';
        return redirect()->route('sub_users')->with('errorSuccess',$message);
       
    }

    
    public function security(Request $request,$id){
        $user  = Auth::user();
        $model = User::where('parent_id',$user->id)->find($id);
        if(count($model) > 0){
            return view('frontend.subusers.security',compact('model'));
        }else{
            return response()->view('errors.404', [], 404);
        }
    }
    
    public function changePassword(Request $request){
    	$user  = Auth::user();
        $model = User::where('parent_id',$user->id)->find($request->id);
        if(count($model) > 0){
        	$model->password = Hash::make($request->password);
	        $model->save();

	        $message = 'Password changed successfully.';
	        return redirect()->route('sub_users')->with('errorSuccess',$message);
        }else{
            return response()->view('errors.404', [], 404);
        }
    }

    public function edit(Request $request,$id){
        $user  = Auth::user();
        $model = User::where('parent_id',$user->id)->find($id);
        if(count($model) > 0){
        	$country = Country::pluck('country_name', 'id')->toArray();
            return view('frontend.subusers.edit',compact('model','country'));
        }else{
            return response()->view('errors.404', [], 404);
        }
    }

    public function update(Request $request){
        $checkExists = $this->alreadyEmail($request);
        $jsonResponse = json_decode($checkExists, true);

        if($jsonResponse['valid'] == false){
        	return redirect()->back()->with('errorFails',$jsonResponse['message']);
        }

       /* $checkExists = $this->alreadyUser($request);
        $jsonResponse = json_decode($checkExists, true);

        if($jsonResponse['valid'] == false){
        	return redirect()->back()->with('errorFails',$jsonResponse['message']);
        }*/
       
        /*$this->validate($request, [
			'email' => 'required|email|unique:users,email,' . $request->id,
		]);*/

        $user  = Auth::user();
        $model = User::where('parent_id',$user->id)->find($request->id);

        if(count($model) > 0){

            $status = 0;
            if($request->filled('status') && $request->status == 1){
                $status = 1;
            }
            
        	$model->Name = $request->name;
        	$model->email = $request->email;
	        $model->status = $status;
	        $model->save();

	        $message = 'Sub user updated successfully.';
	        return redirect()->route('sub_users')->with('errorSuccess',$message);
        }else{
            return response()->view('errors.404', [], 404);
        }
    }

    public function alreadyEmail(Request $request) {

    	if($request->filled('id') && $request->id != ''){
    		$useremail = User::where('email', '=', $request->email)->where('id','!=',$request->id)->first();
    	}else{
    		$useremail = User::where('email', '=', $request->email)->first();
    	}

    	if ($useremail === null) {
    		$isValid = true;
    	} else {
    		$isValid = false;
    	}

    	return json_encode([
    		'valid' => $isValid,
    		"message" => "This email is already exists."
    	]);
    }

    public function alreadyUser(Request $request) {
    	if($request->filled('id') && $request->id != ''){
    		$modelUser = User::where('username', '=', $request->username)->where('id','!=',$request->id)->first();
    	}else{
    		$modelUser = User::where('username', '=', $request->username)->first();
    	}

    	if ($modelUser === null) {
    		$isValid = true;
    	} else {
    		$isValid = false;
    	}

    	return json_encode([
    		'valid' => $isValid,
    		"message" => "This username is already exists."
    	]);
    }

    public function change_permission(Request $request) {
        $status = 'success';
        $msg = 'Permission updated successfully';
        $sub_user = User::select('id','status')->where('id',$request->subuser_id)->where('parent_id',Auth::id())->first();
        if($sub_user == null) {
            $status = 'error';
            $msg = 'Invalid user.';
            return response()->json(['status'=>$status,'message'=>$msg]);
        }

        $user_permision = SubUserPermission::where('subuser_id',$request->subuser_id)->first();
        if(!is_null($user_permision)){
            if($request->filled('is_seller_subuser') && $request->is_seller_subuser == "1") {
                $user_permision->is_seller_subuser = 1;
            } else if($request->filled('is_seller_subuser') && $request->is_seller_subuser == "0"){
                $user_permision->is_seller_subuser = 0;
            }

            if($request->filled('is_buyer_subuser') && $request->is_buyer_subuser == "1") {
                $user_permision->is_buyer_subuser = 1;
            } else if($request->filled('is_buyer_subuser') && $request->is_buyer_subuser == "0"){
                $user_permision->is_buyer_subuser = 0;
                $user_permision->can_make_purchases = 0;
                $user_permision->can_use_wallet_funds = 0;
                $user_permision->can_start_order = 0;
                $user_permision->can_communicate_with_seller = 0;
            }

            /* Active/Inactive sub user status */ 
            if($user_permision->is_buyer_subuser == 1 || $user_permision->is_seller_subuser == 1){
                $sub_user->status = 1;
            }else{
                $sub_user->status = 0;
            }
            $sub_user->save();
            /* END Active/Inactive sub user status */ 

            $user_permision->save();
        } else {
            $status = 'error';
            $msg = 'Something went wrong';
        }
        return response()->json(['status'=>$status,'message'=>$msg]);
    }

    public function update_permissions(Request $request) {
        
        $sub_user = User::select('id')->where('id',$request->subuser_id)->where('parent_id',Auth::id())->first();
        if($sub_user == null) {
            return redirect()->route('sub_users')->with('errorFails','Invalid user.');
        }
        $is_buyer_permission = false;

        $user_permision = SubUserPermission::where('subuser_id',$request->subuser_id)->first();
        if(!is_null($user_permision)) {

            if($request->filled('can_make_purchases') && $request->can_make_purchases == "1") {
                if($request->filled('unlimited_purchase') && $request->unlimited_purchase == "-1") {
                    $user_permision->can_make_purchases = -1;
                } else if($request->filled('monthly_budget')){
                    $user_permision->can_make_purchases = $request->monthly_budget;
                }
                $is_buyer_permission = true;
            } else {
                $user_permision->can_make_purchases = 0;
            }

            if($request->filled('can_use_wallet_funds') && $request->can_use_wallet_funds == "1") {
                $user_permision->can_use_wallet_funds = 1;
                $is_buyer_permission = true;
            } else {
                $user_permision->can_use_wallet_funds = 0;
            }

            if($request->filled('can_start_order') && $request->can_start_order == "1") {
                $user_permision->can_start_order = 1;
                $is_buyer_permission = true;
            } else {
                $user_permision->can_start_order = 0;
            }

            if($request->filled('can_communicate_with_seller') && $request->can_communicate_with_seller == "1") {
                $user_permision->can_communicate_with_seller = 1;
                $is_buyer_permission = true;
            }
            else {
                $user_permision->can_communicate_with_seller = 0;
            }
            /* Check Buyer permission */
            if($is_buyer_permission == true){
                $user_permision->is_buyer_subuser = 1;
            }else{
                $user_permision->is_buyer_subuser = 0;
            }
            
            /* Active/Inactive sub user status */ 
            if($user_permision->is_buyer_subuser == 1 || $user_permision->is_seller_subuser == 1){
                $sub_user->status = 1;
            }else{
                $sub_user->status = 0;
            }
            $sub_user->save();
            /* END Active/Inactive sub user status */ 

            $user_permision->save();
        }
        return redirect()->route('sub_users')->with('errorSuccess','Permissions updated successfully.');
    }

    /**
     * Check parent & sub user name.
     *
     * @return \Illuminate\Http\Response
     */
    public function checkSubUserName(Request $request){
        $check_user = User::select('id')
        ->where(function($q){
            $q->where('id',Auth::user()->id);
            $q->orWhere('parent_id',Auth::user()->id);
        });
        if($request->has('id') && $request->id != ""){
            $check_user = $check_user->where('id','!=',$request->id);
        }
        $check_user = $check_user->where('name',$request->name);
        $check_user = $check_user->count();
        if ($check_user == 0) {
            return Response()->json(["valid" => true,'message'=>'']);
        } else {
            return Response()->json(["valid" => false,'message'=>'This name is already exists.']);
        }
    }
}
