<?php

namespace App\Models;

use App\Models\Db\Admin as DbAdmin;
use App\Exceptions\ApiException;

class Admin extends Model
{
    public static $model = 'Admin';

    public function get($id)
    {
        $this->primaryValue = $id;
        return $this;
    }

    public function add($arr)
    {
        return DbAdmin::add($arr);
    }
    public function mget()
    {
        return DbAdmin::mget();
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
        $user = DbAdmin::get(['where' => ['username' => $userArr['username']] ]);
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
        DbAdmin::update($arr);
        return ['id' => $user->id, 'token' => $token]   ;
    }
    public function has($adminId)
    {
        if(empty($admin = DbAdmin::get(['where' => ['id' => $adminId] ]))) {
            return false;
        }
        return true;
    }
    public function hasUsername($username)
    {
        if (empty($admin = DbAdmin::get(['where' => ['username' => $username] ]))) {
            return false;
        }
        return true;
    }
    public function hasApply($userId)
    {
        $arr['where'] = [
            ['user_id', '=', $userId],
        ];
        if (empty($admin = DbAdmin::get($arr))) {
            return false;
        }
        return $admin;
    }
}

?>