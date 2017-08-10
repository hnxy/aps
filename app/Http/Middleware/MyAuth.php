<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;
use App\Models\User;
use App\Exceptions\ApiException;

class MyAuth
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
        $user = current($request->route()[2]);
        $token = getToken($request);
        $time = time();
        $primaryKey = $user->getPrimaryKey();
        if (is_null($user->$primaryKey)) {
            throw new ApiException("", 2, 404);
        }
        if (empty($token) || $token != $user->token) {
            throw new ApiException("token不正确", 3, 401);
        }
        if ($user->token_expired < $time) {
            throw new ApiException("登录已过期", 3, 401);
        }
        return $next($request);
    }
}
