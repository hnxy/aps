<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\GoodsCar;
use App\Models\Goods;
use App\Models\Address;
use App\Models\Area;
use App\Models\Province;
use App\Models\City;
use App\Models\Coupon;
use App\Exceptions\ApiException;
use App\Models\User;
use App\Models\Agent;

class OrderController extends Controller
{
    /**
     * [展示临时订单]
     * @param  Request $request [Request实例]
     * @return [Array]           [返回包含临时订单的信息]
     */
    public function showPreOrder(Request $request, $user)
    {
        $rules = [
            'addr_id' => 'integer',
            'goods_car_ids' => 'required|string',
        ];
        $this->validate($request, $rules);
        $goodsCarIDs = explode(',', $request->input('goods_car_ids'));
        array_pop($goodsCarIDs);
        $addrID = $request->input('addr_id', null);
        $orderModel = new Order();
        $addressModel = new Address();
        $goodsCarModel = new GoodsCar();
        // 获取购物车的信息,该返回的数据为对象数组
        $goodsCars = $goodsCarModel->mgetByGoodsCarIds($user->id, $goodsCarIDs);
        if(count(obj2arr($goodsCars)) != count($goodsCarIDs)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        return [
            'rcv_info' => $addressModel->getFullAddr($user->id, $addrID),
            'orders_info' => $orderModel->getPrice($goodsCars, null, 'code'),
        ];
    }
    /**
     * [展示某一个订单]
     * @param  Request $request [Request实例]
     * @return [Array]           [返回订单有关的信息]
     */
    public function show(Request $request, $user)
    {
        $orderID = $request->route()[2]['id'];
        $addressModel = new Address();
        $orderModel = new Order();
        $order = $orderModel->get($user->id, $orderID);
        $rsp = config('wx.msg');
        if(empty($order)) {
            $rsp['state'] = 1;
            $rsp['msg'] = '该订单不存在';
            return $rsp;
        }
        //判断收货地址是否存在
        $addrID = $order->addr_id;
        $rsp = $orderModel->getOrderInfo($order);
        $rsp['addr_info'] = $addressModel->getFullAddr($user->id, $addrID);
        return $rsp;
    }
    /**
     * [创建订单]
     * @param  Request $request [Request实例]
     * @return [type]           [description]
     */
    public function store(Request $request, $user)
    {
        $rules = [
            'pay_id' => 'required|integer',
            'addr_id' => 'integer',
            'goods_car_ids' => 'required|string',
            'agent_id' => 'integer',
        ];
        $this->validate($request, $rules);
        $goodsCarIDs = explode(',', $request->input('goods_car_ids'));
        array_pop($goodsCarIDs);
        $payId = $request->input('pay_id');
        $addrID = $request->input('addr_id', null);
        $couponID = $request->input('coupon_id', null);
        $agentID = $request->input('agent_id', null);
        $rsp = config('wx.msg');
        $orderModel = new Order();
        $addressModel = new Address();
        $goodsModel = new Goods();
        $couponModel = new Coupon();
        $goodsCarModel = new GoodsCar();
        $agentModel = new Agent();
        // 获取地址的id
        $addrId = $addressModel->getAddrId($user->id, $addrID);
        // 没有填写收货地址或者收货地址的ID不对
        if(is_null($addrId)) {
            return config('error.addr_null_err');
        }

        try {
            app('db')->beginTransaction();
             // 获取购物车的信息,该返回的数据为对象数组
            $goodsCars = $goodsCarModel->mgetByGoodsCarIds($user->id, $goodsCarIDs);
            if(count(obj2arr($goodsCars)) != count($goodsCarIDs)) {
                throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
            }
            //检查优惠码是否有效
            $goodsIds = array_column(obj2arr($goodsCars), 'goods_id');
            if(!$coupon->checkWork($couponID, 'id', $goodsIds)) {
                throw new ApiException('无效的优惠码信息', config('error.not_work_coupon_exception.code'));
            }
            //检查代理是否存在
            if(!is_null($agentID) && !$agentModel->has($agentID)) {
                throw new ApiException('无效的代理者', config('error.not_work_agent_exception.code'));
            }
            //判断购物车是否有过期商品或商品库存是否足够
            if(($abnormal = $goods->isAbnormal($goodsCars)) !== false) {
                throw new ApiException($abnormal['msg'], $abnormal['code']);
            }
            // 更新购物车的状态
            if(!$agentModel->updateState($user->id, $goodsCarIDs, 1)) {
                throw new ApiException("购物车更新失败", config('error.update_goods_car_err.code'));
            }
            //更新商品的库存
            $goodsModel->modifyStock(array_column(obj2arr($goodsCars), 'goods_num', 'goods_id'), 'decrement');
            /**
             * 创建订单
             */
            $time = time();
            $orderNums = [];
            foreach ($goodsCars as $goodsCar) {
                $orderNum = getRandomString(16);
                $combinePayId = getCombinePayId($user->id, $payId);
                $orderDatas[] = [
                    'order_num' => $orderNum,
                    'pay_id' => $payId,
                    'addr_id' => $addrId,
                    'send_time' => mktime(0, 0, 0, date('m'), date('d')+1, date('Y')),
                    'time_space' => 3,
                    'send_price' => 0,
                    'coupon_id' => $couponID,
                    'pay_status' => 1,
                    'pay_by' => 0,
                    'order_status' => 1,
                    'user_id' => $user->id,
                    'created_at' => $time,
                    'order_expired' => $time + config('wx.order_work_time')*3600,
                    'goods_id' => $goodsCar->goods_id,
                    'goods_num' => $goodsCar->goods_num,
                    'combine_pay_id' => $combinePayId,
                ];
            }
            $orderModel->create($orderDatas);
            app('db')->commit();
        } catch(Exceptions $e) {
            app('db')->rollBack();
        }
        //创建订单完成,跳转到支付
        return  $rsp;
    }
    public function combinePay(Request $request, $user)
    {
        $rules = [
            'order_ids' => 'required|string',
        ];
        $this->validate($request, $rules);
        $orderIds = $request->input('order_ids');
        // $combinePayId = getCombinePayId($user->id);
        // Order::mofifyCombinPayId($orderIds, $combinePayId);
    }
    /**
     * [删除订单]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function delete(Request $request, $user)
    {
        $rsp = config('wx.msg');
        if(!(new Order())->remove($user->id, $request->route()[2]['id'])) {
            $rsp['state'] = 1;
            $rsp['msg'] = '删除订单失败';
        }
        return $rsp;
    }
    /**
     * [getClassesOrder description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function getClassesOrder(Request $request, $user)
    {
        $rules = [
            'limit' => 'integer|max:100|min:10',
            'page' => 'integer|min:1',
            'status' => 'integer|max:4|min:0'
        ];
        $this->validate($request, $rules);
        $rsp = config('wx.addr');
        $status = $request->input('status', 0);
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $orderModel = new Order();
        $orders = $orderModel->mget($user->id, $limit, $page, $status);
        if(empty(obj2arr($orders))) {
            $rsp['state'] = 1;
            $rsp['msg'] = '您还没有此类型的订单哦';
        } else {
            $rsp['state'] = 0;
            $rsp['items'] = $orderModel->getOrdersInfo($orders);
            $rsp['num'] = count($rsp['items']);
        }
        return $rsp;
    }
    /**
     * [waitSend description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    protected function waitSend($orderIds, $userId)
    {
        if(is_array($orderIds)) {
            (new Order())->mModifyByUser($orderIds, $userId, ['order_status' => 2, 'pay_status' => 2,'pay_time' => time()]);
        } else {
            (new Order())->modify($orderIds, $userId, ['order_status' => 2, 'pay_status' => 2, 'pay_time' => time()]);
        }
    }
    /**
     * [finishRecv description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function finishRecv(Request $request, $user)
    {
        $rsp = config('wx.msg');
        $id = $request->route()[2]['id'];
        $orderModel = new Order();
        //获取订单
        $order = $orderModel->get($user->id, $id);
        if(empty($order))  {
            $rsp['state'] = 1;
            $rsp['msg'] = '该订单不存在,确认收货失败';
        } else if(is_null($order->logistics_code)) {
            $rsp['state'] = 1;
            $rsp['msg'] = '该订单不存在物流';
        } else if($order->order_status != 3) {
            $rsp['state'] = 1;
            $rsp['msg'] = '该订单无法完成收货';
        } else {
            //根据该该订单的物流单号更新所有有关该物流的订单
            $orderModel->updateByLogstics($order->logistics_code, ['order_status' => 4]);
        }
        return $rsp;
    }
    /**
     * [cancleOrder description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function cancel(Request $request, $user)
    {
        $id = $request->route()[2]['id'];
        $orderModel = new Order();
        $goodsModel = new Goods();
        $orderInfo = $orderModel->get($user->id, $id);
        $rsp = config('wx.msg');
        if(empty($orderInfo))  {
            $rsp['state'] = 1;
            $rsp['msg'] = '该订单不存在';
        } else if(!in_array($orderInfo->order_status, [1 , 4])) {
            $rsp['state'] = 1;
            $rsp['msg'] = '该订单不能取消';
        } else {
            try {
                app('db')->beginTransaction();
                //更新库存
                $goodsModel->modifyStock([$orderInfo->goods_id => $orderInfo->goods_num]);
                //更新订单状态
                $orderModel->modify($id, $user->id, ['order_status' => 5]);
                app('db')->commit();
            } catch(Exceptions $e) {
                app('db')->rollBack();
            }
        }
        return $rsp;
    }
}
?>