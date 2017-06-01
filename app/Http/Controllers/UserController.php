<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Models\User;
use App\Exceptions\ApiException;

class UserController extends Controller
{

    public function login(Request $request)
    {
        $rules = [
            'username' => 'required|max:32|string',
            'passwd' => 'required|max:32|string',
        ];
        $this->validate($request, $rules);
        $lastIp = getIp();
        $userAgent = $request->header('User-Agent');
        $userArr = [
            'username' => $request->username,
            'passwd' => $request->passwd,
            'last_ip' => $lastIp,
            'user_agent' => $userAgent,
        ];
        $userModel = new User();
        $user = $userModel->login($userArr);
        if (empty($user)) {
            throw new ApiException("账号或密码错误", 1);
        }
        return $user;
    }

    public static function get(Request $request)
    {
        return (array) $request->user;
    }
}