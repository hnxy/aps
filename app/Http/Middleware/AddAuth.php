<?php

namespace App\Http\Middleware;

use Closure;
use App\Exceptions\ApiException;


class AddAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if($request->agent->level !== 1) {
            throw new ApiException("你没有此权限", 3, 401);
        }
        return $next($request);
    }
}
