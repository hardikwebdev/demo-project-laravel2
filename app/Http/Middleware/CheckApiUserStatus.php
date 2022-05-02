<?php

namespace App\Http\Middleware;
use Closure;
use App\User; 
use Auth;

class CheckApiUserStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if(Auth::check()){
            
            /*Check User status*/
            if(Auth::user()->status == 0 || Auth::user()->is_delete == 1){
               return response(['success' => false, 'message' =>'Unauthorized', "code" => 401], 401);
            }

            /*For sub users*/
            if(Auth::user()->parent_id != 0){
                $is_premium = Auth::user()->is_premium_seller(Auth::user()->parent_id);
                if($is_premium == false){
                    return response(['success' => false, 'message' =>'Unauthorized', "code" => 401], 401);
                }
            }

            /*Update Last Login Date*/
            $updateuser = User::where('id',Auth::user()->id)->first();
            $updateuser->last_login_at = date('Y-m-d H:i:s');
            $updateuser->save();
        }

        return $response;
    }
}