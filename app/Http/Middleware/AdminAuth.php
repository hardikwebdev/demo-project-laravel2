<?php

namespace App\Http\Middleware;

use Closure;
use Session;
use App\Models\Admin;
use Cookie;

class AdminAuth
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
        if(Session::has('username')){
            if(!empty(Cookie::get("admin_login_untill"))){
                $Admin  = Admin::where(['status'=>'active','email'=>Session::get('username')])->first();
                if(!$Admin){
                    $request->session()->flush();
                    return redirect(env('ADMIN_BASE_URL'));
                }
                $request->session()->put('first_name',$Admin['first_name']);
                $request->session()->put('last_name',$Admin['last_name']);
            }else{
                $request->session()->flush();
                return redirect(env('ADMIN_BASE_URL'));
            }
            return $next($request);
        }else{
           Cookie::forget('admin_login_untill');
           return redirect(env('ADMIN_BASE_URL'));
        }
    }
}
