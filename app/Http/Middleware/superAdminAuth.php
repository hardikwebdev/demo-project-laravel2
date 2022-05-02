<?php

namespace App\Http\Middleware;

use Closure;
use Session;
use App\Models\Admin;

class superAdminAuth
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
            $Admin  = Admin::where(['status'=>'active','email'=>Session::get('username'),'admin_type'=>'superadmin'])->first();
            if(!$Admin){
                $request->session()->flush();
                return redirect(env('ADMIN_BASE_URL'));
            }
            $request->session()->put('first_name',$Admin['first_name']);
            $request->session()->put('last_name',$Admin['last_name']);
           return $next($request);
        }else{
           return redirect(env('ADMIN_BASE_URL'));
        }
    }
}
