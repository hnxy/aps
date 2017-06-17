<?php

namespace app\Models;

use DB;
use App\Exceptions\ApiException;

class User
{

    public static $model = "user";

    /**
     * [通过id获取永固实例]
     * @param  [int] $id [id]
     * @return [Object]     [包含用户信息的对象]
     */
    public static function get($id)
    {
        return app('db')->table(self::$model)->where(['id' => $id])->first();
    }


    /**
     * [login description]
     * 用户登录
     * @author cg
     * @param  [array] $userArr [username,passwd,last_ip]
     * @return [array]          [[id => '用户id',token => '安全token']]
     */
    public function login($userArr)
    {
        $user = app('db')->table(self::$model)->where([
                'username' => $userArr['username']
            ])->first();
        if (empty($user)) {
            throw new ApiException("此用户不存在", 2);
        }
        // 密码不正确
        if (!password_verify($userArr['passwd'], $user->passwd)) {
            return [];
        }
        $token = genToken();
        $lastLoginTime = time();
        static::updateById($user->id, [
            'last_login_time' => $lastLoginTime,
            'login_count' => $user->login_count + 1,
            'token' => $token,
            'token_expired' => $lastLoginTime + 3600 * 24,
            'user_agent' => $userArr['user_agent'],
            'last_ip' => $userArr['last_ip'],
            ]
        );
        return ['id' => $user->id, 'token' => $token]   ;
    }
    /**
     * [通过userid来更新用户的登录情况]
     * @param  [String] $userId [用户ID]
     * @param  [Array] $arr    [包含用户信息的数组]
     * @return [Bool]         [更新是否成功]
     */
    private static function updateById($userId, $arr)
    {
        return app('db')->table(self::$model)->where(['id' => $userId])->update($arr);
    }
    /**
     * [通过openid来更新用户的登录情况]
     * @param  [Array] $userArr [包含用户信息的数组]
     * @return [Bool]          [更新是否成功]
     */
    public static function updateByOpenid(Request $request, $userArr)
    {
        $lastIp = getIp();
        $userAgent = $request->header('User-Agent');
        $user = app('db')->table(self::$model)->where([
                'openid' => $userArr['openid']
        ])->first();
        $token = genToken();
        $lastLoginTime = time();
        $arr = [
            'last_login_time' => $lastLoginTime,
            'login_count' => $user->login_count + 1,
            'token' => $token,
            'token_expired' => $lastLoginTime + 3600 * 24,
            'user_agent' => $userAgent,
            'last_ip' => $lastIp,
        ];
        return app('db')->table(self::$model)->where(['openid' => $userArr['openid']])->update($arr);
    }
}
