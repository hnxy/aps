<?php

namespace App\Models\Db;

use Illuminate\Support\Facades\DB;

class Order extends Model
{
    private static $model = 'order';
    const orderStatus = [
        'WAIT_PAY' => 1,
        'WAIT_SEND' => 2,
        'WAIT_RECV' => 3,
        'IS_FINISH' => 4,
        'IS_CANCEL' => 5,
    ];
    /**
     * [创建新订单]
     * @param  [Array] $orderMsg [订单的信息]
     * @return [Integer]           [返回订单的ID]
     */
    public static function create($orderMsg)
    {
        return app('db')->table(self::$model)
                        ->insert($orderMsg);
    }
    /**
     * [根据订单ID获取订单]
     * @param  [integer] $id [订单ID]
     * @return [Object]     [包含订单信息的对象]
     */
    public static function get($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->first();
    }
    public static function count($arr)
    {
        return app('db')->table(self::$model)
                        ->where(isset($arr['where']) ? $arr['where'] : [])
                        ->count();
    }
    /**
     * [删除订单]
     * @param  [integer] $id [订单ID]
     * @return [integer]     [返回影响的行数]
     */
    public static function remove($userId, $id)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['id', '=', $id],
                            ['user_id', '=', $userId],
                        ])
                        ->update(['is_del' => 1]);
    }
    public static function mget($arr)
    {
        $limit = isset($arr['limit']) ? $arr['limit'] : 10;
        $page = isset($arr['page']) ? $arr['page'] : 1;
        return app('db')->table(self::$model)
                        ->limit($limit)
                        ->offset(($page-1) * $limit)
                        ->where($arr['where'])
                        ->orderBy('id', 'desc')
                        ->get();
    }
    public static function modify($arr)
    {
        return app('db')->table(self::$model)
                        ->where($arr['where'])
                        ->update($arr['update']);
    }
    public static function mModify($arr)
    {
        return app('db')->table(self::$model)
                        ->where(isset($arr['where']) ? $arr['where'] : [])
                        ->whereIn($arr['whereIn']['key'], $arr['whereIn']['values'])
                        ->update($arr['update']);
    }
    public static function getByAgentId($agentId, $orderNum)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['agent_id', '=', $agentId],
                            ['order_num', '=', $orderNum],
                            ['is_del', '=', 0],
                        ])
                        ->whereIn('order_status', [self::orderStatus['WAIT_SEND'], self::orderStatus['WAIT_RECV'], self::orderStatus['IS_FINISH']])
                        ->first();
    }
    /**
     * [getByTime description]
     * @param  [type] $agentId [description]
     * @param  [type] $start   [description]
     * @param  [type] $end     [description]
     * @param  [type] $limit   [description]
     * @param  [type] $page    [description]
     * @return [type]          [description]
     */
    public static function getByTime($agentId, $start, $end, $limit, $page)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['agent_id', '=', $agentId],
                        ])
                        ->whereBetween('created_at', [$start, $end])
                        ->whereIn('order_status', [self::orderStatus['WAIT_SEND'], self::orderStatus['WAIT_RECV'], self::orderStatus['IS_FINISH']])
                        ->limit($limit)
                        ->offset(($page - 1) * $limit)
                        ->orderBy('created_at','desc')
                        ->get();
    }
    public static function mgetByOrderIds($arr)
    {
        return app('db')->table(self::$model)
                        ->where(isset($arr['where']) ? $arr['where'] : [])
                        ->whereIn($arr['whereIn']['key'], $arr['whereIn']['values'])
                        ->get();
    }
    public static function mgetByCombinePayId($combinePayId)
    {
        return app('db')->table(self::$model)
                        ->where('combine_pay_id', $combinePayId)
                        ->get();
    }
    public static function all($arr)
    {
        if (isset($arr['whereIn'])) {
            return app('db')->table(self::$model)
                            ->where(isset($arr['where']) ? $arr['where'] : [])
                            ->whereIn($arr['whereIn']['key'], $arr['whereIn']['values'])
                            ->count('id');
        }
        return app('db')->table(self::$model)
                        ->where(isset($arr['where']) ? $arr['where'] : [])
                        ->count('id');
    }
    public static function getTrade($agentId, $start, $end)
    {
        return app('db')->table(self::$model)
                        ->where([
                            ['agent_id', '=', $agentId],
                        ])
                        ->select(app('db')->raw('count(id) as totel, FROM_UNIXTIME(created_at, "%m-%d") as day'))
                        ->whereBetween('created_at', [$start, $end])
                        ->whereIn('order_status', [self::orderStatus['WAIT_SEND'], self::orderStatus['WAIT_RECV'], self::orderStatus['IS_FINISH']])
                        ->groupBy(app('db')->raw('FROM_UNIXTIME(created_at, "%Y-%m-%d")'))
                        ->get();
    }
}
?>
