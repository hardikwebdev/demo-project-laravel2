<?php

namespace App\Http\Middleware;

use Closure;
use Route;
use Illuminate\Support\Facades\Auth;

class CheckUserAddress
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
        if(Auth::check() && Route::currentRouteName() != 'accountsetting' && $request->method() == "GET" && Route::currentRouteName() != 'logout'){
            if(Auth::user()->address == NULL && Route::currentRouteName() != 'accountsetting') {
                \Session::flash('tostError', 'Please update your address information.');
                return redirect()->route('accountsetting');
            }
        }
        return $next($request);
    }
}
