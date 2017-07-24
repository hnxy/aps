<?php

namespace App\Models\Db;

class Address extends Model
{
    public static $model = 'address';
    /**
     * [获取地址信息]
     * @param  [Integer] $id [地址的ID]
     * @return [Object]     [地址信息对象]
     */
    public static function get($userId, $id = null)
    {
        if(is_null($id)) {
            $default = app('db')->table(self::$model)
                                ->where([
                                    ['is_default', '=', 1],
                                    ['is_del', '=', 0],
                                    ['user_id', '=', $userId],
                                ])
                                ->first();
            if(!empty($default)) {
                return $default;
            }
            return app('db')->table(self::$model)
                            ->where([
                                ['user_id', '=', $userId],
                                ['is_del', '=', 0],
                            ])
                            ->orderBy('id', 'desc')
                            ->first();
        }
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id],
                            ['user_id', '=', $userId],
                            ['is_del', '=', 0],
                        ])
                        ->first();
    }
     /**
     * [新增地址信息]
     * @param [Array] $addrArr [地址信息数组]
     */
    public static function add($addrArr)
    {
        return app('db')->table(self::$model)
                        ->insert($addrArr);
    }

    public static function mget($userId, $limit, $page)
    {
        return app('db')->table(self::$model)
                        ->limit($limit)
                        ->offset(($page-1)*$limit)
                        ->where([
                            ['user_id', '=', $userId],
                            ['is_del', '=', 0]
                        ])
                        ->orderBy('created_at', 'desc')
                        ->get();
    }

    public static function remove($userId, $id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id],
                            ['user_id', '=', $userId],
                        ])
                        ->update(['is_del' => 1]);
    }
    public static function setDefault($userId, $id)
    {
        app('db')->table(self::$model)
                 ->where([
                        ['user_id', '=', $userId],
                        ['is_del', '=', 0],
                    ])
                 ->update(['is_default' => 0]);
        return  app('db')->table(self::$model)
                         ->where([
                            ['id', '=', $id],
                            ['user_id', '=', $userId],
                            ['is_del', '=', 0],
                        ])
                         ->update(['is_default' => 1]);
    }
     /**
     * [更新地址信息]
     * @param  [Integer] $id      [地址ID]
     * @param  [Array] $addrArr [要更新的地址信息]
     * @return [Integer]          [影响的行数]
     */
    public static  function modify($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->update($arr['update']);
    }
}