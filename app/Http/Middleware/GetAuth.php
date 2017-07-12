<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;
use App\Exceptions\ApiException;


class GetAuth
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
        $id = (int) $request->input('search');
        if($request->agent->level !== 1 && $request->agent->id !== $id) {
            throw new ApiException("你没有此权限", 3, 401);
        }
        return $next($request);
    }
}
