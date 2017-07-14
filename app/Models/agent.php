<?php

namespace App\Models;

use App\Models\Db\Agent as DbAgent;

class Agent extends Model
{
    public static $model = 'Agent';
    public static function get($agentId)
    {
        return DbAgent::get(['where' => ['id' => $agentId] ]);
    }

    public static function add($arr)
    {
        return DbAgent::add($arr);
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
        $user = DbAgent::get(['where' => ['username' => $userArr['username']] ]);
        if (empty($user)) {
            throw new ApiException("此用户不存在", 2);
        }
        // 密码不正确
        if (!password_verify($userArr['passwd'], $user->passwd)) {
            return [];
        }
        $token = genToken();
        $lastLoginTime = time();
        $arr['where'] = ['id' => $user->id];
        $arr['update'] = [
            'last_login_time' => $lastLoginTime,
            'login_count' => $user->login_count + 1,
            'token' => $token,
            'token_expired' => $lastLoginTime + 3600 * 24,
            'user_agent' => $userArr['user_agent'],
            'last_ip' => $userArr['last_ip'],
            ];
        DbAgent::update($arr);
        return ['id' => $user->id, 'token' => $token]   ;
    }
    public static function has($agentId)
    {
        if(empty($agent = static::get($agentId))) {
            return false;
        }
        return true;
    }
}

?>