<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;
use app\Models\User;
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
        $userId = $request->route()[2]['user_id'];
        $user = User::get($userId);
        $token = $request->input('token');
        $time = time();
        if (empty($user)) {
            throw new ApiException("", 1, 404);
        }
        if (empty($token) || $token != $user->token) {
            throw new ApiException("token不正确", 3, 401);
        }
        if ($user->token_expired < $time) {
            return response("登录已经过期", 401);
        }
        $request->user = $user;
        return $next($request);
    }
}
