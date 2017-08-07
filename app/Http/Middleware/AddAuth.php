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
        $agent = $request->route()[2]['agent_id'];
        if($agent->level !== 1) {
            throw new ApiException("你没有此权限", 4, 401);
        }
        return $next($request);
    }
}
