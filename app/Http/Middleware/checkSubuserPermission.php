<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Route;
use Request;
use App\User;

class checkSubuserPermission
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
        if(Auth::check() && Auth::user()->parent_id != 0){

            /* sub user allow to start order process */
            $sub_user_allowed_routes_for_start_order = ['buyer_orders','buyer_orders_details','save_answers','order_submit_requirements','payment_detail','security'];
            if(in_array(Route::currentRouteName(),$sub_user_allowed_routes_for_start_order) && User::check_sub_user_permission('can_start_order')) {
                return $next($request);
            }
            /* sub user allow to make purchase */
            $sub_user_allowed_routes_for_purchase = ['buyer_orders','buyer_orders_details','paypal_express_checkout','bluesnap.checkout','paypal_express_checkout_success','bluesnap.thankyou','payment_detail','order_submit_requirements','custom_order_request','request_custom_quote','jobs','security','skrill.checkout','skrill.upgradeorder.checkout','skrill.thankyou','skrill.checkpayment','skrill.boost.checkout'];
            if((in_array(Route::currentRouteName(),$sub_user_allowed_routes_for_purchase) || Request::segment(1) == 'cart' || (Request::segment(1) == 'bluesnap' && Request::segment(1) == 'ipn')) && User::check_sub_user_permission('can_make_purchases')) {
                return $next($request);
            }

            return redirect('/');
        }
        return $next($request);
    }
}
