<?php

namespace App\Models;

use App\Models\Goods;
use App\Models\Coupon;
use App\Models\Express;
use App\Exceptions\ApiException;
use App\Models\Db\Order as DbOrder;

class Order extends Model
{
    public static $model = 'Order';
    /**
     * [创建新订单]
     * @param  [Array] $orderMsg [订单的信息]
     * @return [Integer]           [返回订单的ID]
     */
    public static function create($orderMsg)
    {
        return DbOrder::create($orderMsg);
    }
    /**
     * [根据订单ID获取订单]
     * @param  [integer] $id [订单ID]
     * @return [Object]     [包含订单信息的对象]
     */
    public static function get($userId, $id)
    {
        return DbOrder::get(['where' => [
                ['user_id', '=', $userId],
                ['id', '=', $id],
            ]]);
    }
    /**
     * [获取价格有关的信息]
     * @param  [Object] $goodsCars [购物车对象的集合]
     * @param  [String] $value     [$key字段的值]
     * @param  string $key       [字段名称]
     * @return [Array]            [价格有关的信息]
     */
    public function getPrice($goodsCars) {
        $priceInfos = $goodsCarMap = $goodsCarInfos = [];
        $allPrice = 0;
        $time = time();
        $sendTime = 99999999999;
        $timespace = 999;
        $goodsCarMap = getMap($goodsCars, 'goods_id');
        $goodses = Goods::mgetByIds(array_keys($goodsCarMap));
        foreach ($goodses as $goodsInfo) {
            $goodsCar = $goodsCarMap[$goodsInfo->id];
            $currentPrice = sprintf('%.2f', $goodsInfo->price*$goodsCar->goods_num);
            $allPrice += $currentPrice;
            $goodsCarInfos[] = [
                'goods_car_id' => $goodsCar->id,
                'goods_id' => $goodsInfo->id,
                'name' => $goodsInfo->title,
                'value' => '￥'.$currentPrice,
                'num' => $goodsCar->goods_num,
                'unit' => $goodsInfo->unit,
            ];
            $sendTime = min($sendTime, $goodsInfo->send_time);
            $timespace = min($timespace, $goodsInfo->timespace);
        }
        $allPrice = sprintf('%.2f', $allPrice);
        $priceInfos[] = ['name' => '配送费', 'value' => '￥00.00'];
        $priceInfos[] =  ['name' => '订单总价格', 'value' => '￥'.$allPrice];
        $priceInfos[] = ['name' => '优惠金额', 'value' => '￥00.00'];
        $sendTime = formatY($sendTime);
        return [
            'send_time' => ["预计{$sendTime}发货", "预计{$timespace}天后到货"],
            'coupon' => '暂无可用的优惠码',
            'price_info' => $priceInfos,
            'goods_car_info' => $goodsCarInfos,
        ];
    }
    /**
     * [删除订单]
     * @param  [integer] $id [订单ID]
     * @return [integer]     [返回影响的行数]
     */
    public static function remove($userId, $id)
    {
        return DbOrder::remove($userId, $id);
    }
    public static function mget($userId, $limit, $page, $state)
    {
        $arr['limit'] = $limit;
        $arr['page'] = $page;
        if($state == 0) {
            $arr['where'] = [
                ['user_id', '=', $userId],
                ['is_del', '=', 0],
            ];
        } else {
            $arr['where'] = [
                ['user_id', '=', $userId],
                ['order_status', '=', $state],
                ['is_del', '=', 0],
            ];
        }
        return DbOrder::mget($arr);
    }
    public function getOrdersInfo($orders)
    {
        $orderInfos = $otherInfo = $goodsIds = $orderMap = [];
        $time = time();
        //获取商品id,同时获取商品与订单之间的映射
        $orderMap = getMap($orders, 'goods_id');
        $goodses = Goods::mgetByIds(array_keys($orderMap));
        foreach ($goodses as $goods) {
            $allPrice = 0;
            $order = $orderMap[$goods->id];
            $allPrice += sprintf('%.2f', $goods->price*$order->goods_num);
            $orderInfo = $this->formatOrder($order);
            $goodsInfo = $this->formatGoods($order, $goods);
            $express = $this->getExpress($order->express_id);
            $allPrice = $this->getAllPrice($order->coupon_id, $allPrice);
            $goodsInfo['value'] = '￥'.$allPrice;
            $sendPrice = sprintf('%.2f', $order->send_price);
            $sendTime = formatTime($goods->send_time);
            $priceInfo[] = ['text' => '配送费', 'value' => '￥'.$sendPrice];
            $otherInfo = $this->getOtherInfo($order->order_status);
            $orderInfos[] =[
                'order_info' => $orderInfo,
                'goods_info' => $goodsInfo,
                'express_info' => $express,
                'price_info' => $priceInfo,
                'otherInfo' => $otherInfo,
            ];
            $goodsInfo = [];
        }
        return $orderInfos;
    }
    protected function getAllPrice($couponId, $allPrice)
    {
        if(!is_null($couponId)) {
            $coupon = Coupon::getById($couponId);
            $allPrice -= $coupon->price;
        }
        return sprintf('%.2f', $allPrice);
    }
    protected function formatGoods($order, $goods)
    {
        $goodsInfo = [
                'goods_id' => $order->goods_id,
                'name' => $goods->title,
                'goods_desc' => $goods->description,
                // 'value' => '￥'.$allPrice,
                'num' => $order->goods_num,
                'unit' => $goods->unit,
                'goods_img' => $goods->goods_img,
            ];

        if($goods->end_time < time()) {
            $goodsInfo['status'] = 1;
            $goodsInfo['status_text'] = '该商品已下架';
        } else {
            $goodsInfo['status'] = 0;
            $goodsInfo['status_text'] = null;
        }
        return $goodsInfo;
    }
    protected function formatOrder($order)
    {
        return [
                'order_id' => $order->id,
                'order_num' => $order->order_num,
                'created_at' => formatTime($order->created_at),
            ];
    }
    private function getOtherInfo($status)
    {
        switch ($status) {
                case '1':
                    $otherInfo[] = ['type' => 'button', 'name' => '取消订单'];
                    $otherInfo[] = ['type' => 'button', 'name' => '立即支付'];
                    break;
                case '2':
                    $otherInfo[] = ['type' => 'text', 'value' => '发货时间'];
                    $otherInfo[] = ['type' => 'text', 'value' => "{$sendTime}起"];
                    break;
                case '3':
                    $otherInfo[] = ['type' => 'button', 'name' => '查看物流'];
                    $otherInfo[] = ['type' => 'button', 'name' => '确认收货'];
                    break;
                case '4':
                    $otherInfo[] = ['type' => 'button', 'name' => '查看物流'];
                    $otherInfo[] = ['type' => 'button', 'name' => '联系客服'];
                    break;
                case '5':
                    $otherInfo[] = ['type' => 'button', 'name' => '删除订单'];
                    break;
            }
        return $otherInfo;
    }
    private function getExpress($expressId)
    {
        $express = null;
        if(!is_null($expressId)) {
            $express = Express::get($expressId);
            $express->phone = config("wx.express_offic_phone.{$express->code}");
        }
        return $express;
    }
    public function getOrderInfo($order)
    {
        $priceInfos = $pay_info = [];
        $goodsInfo = Goods::get($order->goods_id);
        $allPrice = sprintf('%.2f', $goodsInfo->price*$order->goods_num);
        $sendTime = formatY($goodsInfo->send_time);
        $express = null;
        if(!is_null($order->express_id)) {
            $express = Express::get($order->express_id);
            $express->phone = config("wx.express_offic_phone.{$express->code}");
        }
        //支付相关
        $payTime = null;
        if(!is_null($order->pay_time))
            $payTime = formatTime($order->pay_time);
        $pay_info[] = ['name' => '订单状态:', 'value' => config('wx.order_status')[$order->order_status]];
        $pay_info[] = ['name' => '订单号:', 'value' => $order->id];
        $pay_info[] = ['name' => '支付方式:', 'value' => config('wx.pay_by')[$order->pay_by]];
        $pay_info[] = ['name' => '支付时间:', 'value' => $payTime];
        //价格相关
        $priceInfos[] = ['name' => '订单总价', 'value' => '￥'.$allPrice];
        $priceInfos[] = ['name' => '配送费', 'value' => '￥00.00'];
        // $priceInfos[] = ['name' => '优惠券', 'value' => '-￥00.00'];
        $goods_car_infos[] =  [
                'goods_id' => $goodsInfo->id,
                'name' => $goodsInfo->title,
                'value' => '￥'.$allPrice,
                'num' => $order->goods_num,
                'unit' => $goodsInfo->unit,
        ];
        return [
            'send_time' => ["预计{$sendTime}发货", "预计{$goodsInfo->timespace}天后到货"],
            'pay_info' => $pay_info,
            'price_info' => $priceInfos,
            'goods_car_info' => $goods_car_infos,
        ];
    }
    public static function modify($orderId, $userId, $arr)
    {
        $uarr['where'] = [
            ['id', '=', $orderId],
            ['user_id', '=', $userId],
        ];
        $uarr['update'] = $arr;
        return DbOrder::modify($uarr);
    }
    public static function mModify($orderIds, $arr)
    {
        $uarr['whereIn']['key'] = 'id';
        $uarr['whereIn']['values'] = $orderIds;
        $uarr['update'] = $arr;
        return DbOrder::mModify($uarr);
    }
    public static function mModifyByUser($orderIds, $userId, $arr)
    {
        $uarr['where'] = ['user_id' => $userId];
        $uarr['whereIn']['key'] = 'id';
        $uarr['whereIn']['values'] = $orderIds;
        $uarr['update'] = $arr;
        return DbOrder::mModify($uarr);
    }
    public static function getByAgentId($agentId, $id)
    {
        return DbOrder::getByAgentId($agentId, $id);
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
        return DbOrder::getByTime($agentId, $start, $end, $limit, $page);
    }
    /**
     * [addLogistics description]
     * @param [type] $orderIds [description]
     * @param [type] $orderArr [description]
     */
    public static function addLogistics($orderIds, $orderArr)
    {
        $uarr['whereIn']['key'] = 'id';
        $uarr['whereIn']['values'] = $orderIds;
        $uarr['update'] = $orderArr;
        return DbOrder::mModify($uarr);
    }
    public static function updateByLogstics($logstics, $arr)
    {
        $uarr['where'] = ['logistics_code' => $logstics];
        $uarr['update'] = $arr;
        return DbOrder::modify($uarr);
    }
    public static function mgetByOrderIds($userId, $orderIds)
    {
        return DbOrder::mgetByOrderIds($userId, $orderIds);
    }
    public static function modifyCombinePayId($orderIds, $combinePayId)
    {
        $arr['whereIn']['key'] = 'id';
        $arr['whereIn']['values'] = $orderIds;
        $arr['update'] = ['combine_pay_id' => $combinePayId];
        return DbOrder::modify($arr);
    }
}
?>
