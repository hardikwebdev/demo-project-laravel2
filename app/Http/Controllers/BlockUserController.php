<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\User;
use App\BlockUser;

class BlockUserController extends Controller
{
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
    
    /* Blocked user list*/
    public function blocked_user_list(Request $request){        
        $blocked_users = BlockUser::select('users.Name','users.username','block_users.block_user_id','block_users.id','block_users.block_by','block_users.created_at')
            ->where('block_by',$this->uid)
            ->join('users','users.id','block_users.block_user_id')
            ->where('users.is_delete',0)
            ->paginate(20);

        return view('frontend.seller.block_user_list',compact('blocked_users'));
    }

    /* Block user function?  */ 
    public function user_block(Request $request, $secret){
        /* Descrypt user id */ 
        $block_user_id = User::getDecryptedId($secret);
        /* Check block user exists or not */ 
        $checkBlockUser = BlockUser::select('id')->where('block_by',$this->uid)->where('block_user_id',$block_user_id)->count();
        if(!$checkBlockUser){
            /* Block new user */ 
            $block = new BlockUser;
            $block->block_user_id = $block_user_id;
            $block->block_by = $this->uid;
            $block->save();
            $message = 'User blocked successfully.';
            $status = true;
        }else{
            /* User is already block */
            $status = false;
            $message = 'This user is already blocked.';
        }

        if($request->ajax()){
            /* Response from ajax */ 
            return response()->json(['success'=>$status,'status'=>200,'message'=>$message]);
        }else{
            /* Response back */
            return redirect()->back()->with('success',$message);
        }
    }

    /* Unblock user */
    public function user_unblock(Request $request, $secret){
        /* Decrypt user id */
        $block_user_id = User::getDecryptedId($secret);
        /* Check block user exists or not */
        $unblockUser = BlockUser::select('id')->where('block_by',$this->uid)->where('block_user_id',$block_user_id)->first();
        if($unblockUser){
            /* Unblock user */
            $unblockUser->delete();
            $message = 'User unblocked successfully.';
            $status = true;
        }else{
            /* User is not block */
            $status = false;
            $message = 'Something went wrong.';
        }

        if($request->ajax()){
            /* Response from ajax */ 
            return response()->json(['success'=>$status,'status'=>200,'message'=>$message]);
        }else{
            /* Response back */
            return redirect()->back()->with('success',$message);
        }
    }

}
