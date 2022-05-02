<?php

namespace App\Http\Middleware;
use Closure;
use App\User; 
use Illuminate\Support\Facades\Auth;
use App\SubUserPermission;
use Session;

class CheckStatus
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
            if(Auth::user()->status != 1 || Auth::user()->is_delete == 1){
                Auth::logout();
                return redirect(url('/'));
            }

            /*For sub users*/
            if(Auth::user()->parent_id != 0){
                $is_premium = Auth::user()->is_premium_seller(Auth::user()->parent_id);
                $sub_user_permission = SubUserPermission::where('subuser_id',Auth::user()->id)->select('id','is_buyer_subuser','is_seller_subuser','can_make_purchases','can_use_wallet_funds','can_start_order','can_communicate_with_seller')->first();

                $restrict_buyer_permission = false;
                
                if($sub_user_permission->is_buyer_subuser==0 || ($sub_user_permission->is_buyer_subuser==1 && $sub_user_permission->can_make_purchases==0 && $sub_user_permission->can_use_wallet_funds==0 && $sub_user_permission->can_start_order==0 && $sub_user_permission->can_communicate_with_seller==0)) {
                    $restrict_buyer_permission = true;
                }

                if($restrict_buyer_permission == true && ($is_premium == false || $sub_user_permission->is_seller_subuser==0)){
                    Auth::logout();
                    return redirect(url('/'));
                }
            }

            /*Update Last Login Date*/
            if($request->segment(1) == 'messaging' && $request->ajax()){
                return $response;
            }

            /* check if login from admin */
            if(!Session::has('login_from_admin')) {
                $updateuser = Auth::user();
                $updateuser->last_login_at = date('Y-m-d H:i:s');
                $updateuser->save();
            }

        }

        return $response;
    }
}
