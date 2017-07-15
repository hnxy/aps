<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;
use App\Models\Agent;
use App\Exceptions\ApiException;


class AgentAuth
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
        $token = getToken($request);
        $time = time();
        if (empty($agent)) {
            throw new ApiException("", 1, 404);
        }
        if (empty($token) || $token != $agent->token) {
            throw new ApiException("token不正确", 3, 401);
        }
        if ($agent->token_expired < $time) {
            return response("登录已经过期", 401);
        }
        return $next($request);
    }
}
