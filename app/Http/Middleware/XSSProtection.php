<?php

namespace App\Http\Middleware;

use Closure;

class XSSProtection
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
        $input = $request->all();
        array_walk_recursive($input, function(&$input) {
            $input = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $input);
            $input = preg_replace('#<script(.*?)>#is', '', $input);

            $input = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $input);
            $input = preg_replace('#<style(.*?)>#is', '', $input);
            $input = preg_replace('#<link(.*?)>#is', '', $input);
        });
        $request->merge($input);
        return $next($request);
    }
}
