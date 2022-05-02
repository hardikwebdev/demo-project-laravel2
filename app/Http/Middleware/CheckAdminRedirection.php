<?php

namespace App\Http\Middleware;

use Closure;
use App\URLRedirection;
use Route;

class CheckAdminRedirection
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
        $url_list = URLRedirection::select('from_url','to_url')->get()->toArray();
        $current_url = $request->url();
        $current_route = $request->path();
        foreach ($url_list as $key => $value) {
            if($value['from_url'] == $current_url || $value['from_url'] == $current_route || $value['from_url'] == '/'.$current_route) {
                return redirect($value['to_url']);
            }
        }
        return $next($request);
    }
}
