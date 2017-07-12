<?php

namespace App\Models;

use DB;
use App\Exceptions\ApiException;
use App\Models\Db\User as DbUser;
use ReflectionClass;

class User extends Model
{

    public static $model = 'User';

    public function mget()
    {
        return DbUser::mget();
    }

    public function get($id)
    {
        $this->primaryValue = $id;
        return $this;
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
    public static function loginBy3($userInfo3)
    {
        $user = static::getByOpenid($userInfo3['openid']);
        if (empty($user)) {
            if(static::add($userInfo3)) {
                return self::getByOpenid($userInfo3['openid']);
            } else {
                throw new ApiException('用户信息插入失败', config('err.insert_user_arr_err.code') );
            }
        } else {
            if(static::updateByOpenid($user, $userInfo3['User-Agent'])) {
                return self::getByOpenid($userInfo3['openid']);
            } else {
                throw new ApiException('用户信息更新失败', config('err.insert_user_arr_err.code') );
            }
        }
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
    public static function updateByOpenid($user, $UserAgent)
    {
        $lastIp = getIp();
        $token = genToken();
        $lastLoginTime = time();
        $arr = [
            'last_login_time' => $lastLoginTime,
            'login_count' => $user->login_count + 1,
            'token' => $token,
            'token_expired' => $lastLoginTime + 3600 * 24,
            'user_agent' => $UserAgent,
            'last_ip' => $lastIp,
        ];
        return app('db')->table(self::$model)->where(['openid' => $user->openid])->update($arr);
    }
    private static function add($userArr)
    {
        $lastIp = getIp();
        $token = genToken();
        $lastLoginTime = time();
        return app('db')->table(self::$model)
                        ->insert([
                            'last_login_time' => $lastLoginTime,
                            'login_count' => 1,
                            'token' => $token,
                            'token_expired' => $lastLoginTime + 3600 * 24,
                            'user_agent' => $userArr['User-Agent'],
                            'last_ip' => $lastIp,
                            'openid' => $userArr['openid']
                        ]);
    }
    private static function getByOpenid($openid)
    {
        return  $user = app('db')->table(self::$model)
                                 ->where([
                                    'openid' => $openid
                                ])
                                ->first();
    }
}
