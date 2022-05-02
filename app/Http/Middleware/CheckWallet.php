<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckWallet
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
        /*if(Auth::check() && Auth::user()->earning < 0 && Auth::user()->dispute_amount > 0){
            return redirect('/update-wallet');
        }*/
        return $next($request);
    }
}
