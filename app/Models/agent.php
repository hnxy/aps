<?php

namespace App\Models;

class Agent
{
    private static $model = 'agent';

    public static function add($AgentArr)
    {
        return app('db')->table(self::$model)
                        ->insert($AgentArr);
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

    private static function updateById($userId, $arr)
    {
        return app('db')->table(self::$model)->where(['id' => $userId])->update($arr);
    }
}

?>