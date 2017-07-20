<?php

namespace App\Models\Db;

class User extends Model
{
    public static $model = 'user';

    public static function get($arr)
    {
        return app('db')->table(self::$model)->where($arr['where'])->first();
    }

    public static function mget($arr = [])
    {
        return app('db')->table(self::$model)->where(isset($arr['where']) ? $arr['where'] : [])->get();
    }
    /**
     * [通过userid来更新用户的登录情况]
     * @param  [String] $userId [用户ID]
     * @param  [Array] $arr    [包含用户信息的数组]
     * @return [Bool]         [更新是否成功]
     */
    public static function updateById($userId, $arr)
    {
        return app('db')->table(self::$model)->where(['id' => $userId])->update($arr);
    }
    /**
     * [add description]
     * @param [type] $userArr [description]
     */
    public static function add($userArr)
    {

        return app('db')->table(self::$model)
                        ->insert();
    }
     /**
     * [通过openid来更新用户的登录情况]
     * @param  [Array] $userArr [包含用户信息的数组]
     * @return [Bool]          [更新是否成功]
     */
    public static function updateByOpenid($openId, $arr)
    {
        return app('db')->table(self::$model)->where(['openid' => $openId])->update($arr);
    }
}
