<?php

namespace App\Models\Db;

class Coupon extends Model
{
    public static $model = 'coupon';
    /**
     * [get description]
     * @param  [type] $goodsId [description]
     * @param  [type] $agentId [description]
     * @return [type]          [description]
     */
    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where(isset($arr['where']) ? $arr['where'] : [])
                        ->whereColumn(isset($arr['whereColumn']) ? $arr['whereColumn'] : [])
                        ->first();
    }
    public static function mget($arr)
    {
        $limit = isset($arr['limit']) ? $arr['limit'] : 10;
        $page = isset($arr['page']) ? $arr['page'] : 1;
        return app('db')->table(self::$model)
                        ->where(isset($arr['where']) ? $arr['where'] : [])
                        ->limit($limit)
                        ->offset(($page - 1) * $limit)
                        ->get();
    }
    public static function add($arr)
    {
        return app('db')->table(self::$model)
                        ->insert($arr);
    }
    public static function modifyById($id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id],
                        ])
                        ->increment('times');
    }
    public static function modify($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->update($arr['update']);
    }
    public static function remove($id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id]
                        ])
                        ->delete();
    }
}

?>