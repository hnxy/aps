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
use App\Models\Payment;

class OrderController extends Controller
{
    /**
     * [展示临时订单]
     * @param  Request $request [Request实例]
     * @return [Array]           [返回包含临时订单的信息]
     */

    public function preOrder(Request $request, $user)
    {
        $rules = [
            'addr_id' => 'integer',
            'goods_car_ids' => 'required|string',
        ];
        $this->valIdate($request, $rules);
        $goodsCarIds = explode(',', $request->input('goods_car_ids'));
        array_pop($goodsCarIds);
        $addrId = $request->input('addr_id', null);
        $orderModel = new Order();
        $addressModel = new Address();
        $goodsCarModel = new GoodsCar();
        // 获取购物车的信息,该返回的数据为对象数组
        $goodsCars = $goodsCarModel->mgetByGoodsCarIds($user->id, $goodsCarIds);
        $this->checkGoodsCarWork($goodsCars, $goodsCarIds);
        if ( ($addrDetail = $this->hasAddr($user->id, $addrId)) === false) {
            return config('response.addr_not_exist');
        }
        return [
            'rcv_info' => $addressModel->getFullAddr($addrDetail),
            'orders_info' => $orderModel->getPrice($goodsCars, null, 'code'),
        ];
    }
    /**
     * [展示某一个订单]
     * @param  Request $request [Request实例]
     * @return [Array]           [返回订单有关的信息]
     */
    public function show(Request $request, $user, $orderId)
    {
        $addressModel = new Address();
        $orderModel = new Order();
        $order = $orderModel->get($user->id, $orderId);
        $rsp = config('response.success');
        if(!$orderModel->isExist($order)) {
            return config('response.order_not_exist');
        }
        if ( ($addrDetail = $this->hasAddr($user->id, $order->addr_id)) === false) {
            return config('response.addr_not_exist');
        }
        $rsp = $orderModel->getOrderInfo($order);
        $rsp['addr_info'] = $addressModel->getFullAddr($addrDetail);
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
        $this->valIdate($request, $rules);
        $goodsCarIds = explode(',', $request->input('goods_car_ids'));
        array_pop($goodsCarIds);
        $payId = $request->input('pay_id');
        $addrId = $request->input('addr_id', null);
        $couponId = $request->input('coupon_id', null);
        $agentId = $request->input('agent_id', null);
        $rsp = config('response.success');
        $orderModel = new Order();
        $addressModel = new Address();
        $goodsModel = new Goods();
        $goodsCarModel = new GoodsCar();
        if (($addr = $this->hasAddr($user->id, $addrId)) === false) {
            return config('error.addr_null_err');
        }
        // 获取购物车的信息,该返回的数据为对象数组
        $goodsCars = $goodsCarModel->mgetByGoodsCarIds($user->id, $goodsCarIds);
        $this->checkGoodsCarWork($goodsCars, $goodsCarIds);
        $this->checkOrderArgs($goodsCars, $couponId, $agentId, $payId);
        try {
            app('db')->beginTransaction();
            // 更新购物车的状态
            $goodsCarModel->updateStatus($user->id, $goodsCarIds, 1);
            //更新商品的库存
            $goodsModel->modifyStock(array_column(obj2arr($goodsCars), 'goods_num', 'goods_id'), 'decrement');
            //更新优惠券使用次数
            if(!is_null($couponId)) {
               $couponModel->modifyById($couponId);
            }
            //创建订单
            $time = time();
            //4是pay_Id现在还没有，先用4代替着
            $combinePayId = Order::getCombinePayId($user->id, $payId);
            foreach ($goodsCars as $goodsCar) {
                $orderNum = $this->getOrderNum(16);
                $orderDatas[] = [
                    'order_num' => $orderNum,
                    'pay_id' => $payId,
                    'addr_id' => $addrId,
                    'send_time' => mktime(0, 0, 0, date('m'), date('d')+1, date('Y')),
                    'time_space' => 3,
                    'send_price' => 0,
                    'coupon_id' => $couponId,
                    'pay_status' => 1,
                    'order_status' => 1,
                    'user_id' => $user->id,
                    'created_at' => $time,
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
    /**
     * [判断地址是否存在]
     * @param  [type]  $userId [description]
     * @param  [type]  $addrId [description]
     * @return boolean         [description]
     */
    protected function hasAddr($userId, $addrId)
    {
       return (new Address())->isExist($userId, $addrId);
    }
    /**
     * [生成订单号]
     * @param  [type] $len [description]
     * @return [type]      [description]
     */
    private function getOrderNum($len)
    {
        return getRandomString($len);
    }
    /**
     * [检查订单参数是否合法]
     * @param  [type] $goodsCars [description]
     * @param  [type] $couponId  [description]
     * @param  [type] $agentId   [description]
     * @return [type]            [description]
     */
    private function checkOrderArgs($goodsCars, $couponId, $agentId, $payId)
    {
        $couponModel = new Coupon();
        $agentModel = new Agent();
        $goodsModel = new Goods();
        $paymentModel = new Payment();
        if (!$paymentModel->payEnable($payId)) {
            throw new ApiException(config('error.pay_not_work.msg'), config('error.pay_not_work.code'));
        }
        //检查代理是否存在
        if (!is_null($agentId) && !$agentModel->has($agentId)) {
            throw new ApiException(config('error.not_work_agent_exception.msg'), config('error.not_work_agent_exception.code'));
        }
        //检查优惠码是否有效
        $goodsIds = array_column(obj2arr($goodsCars), 'goods_Id');
        if (!is_null($couponId) && !$couponModel->checkWork($couponId, 'Id', $goodsIds)) {
            throw new ApiException(config('error.not_work_coupon_exception.msg'), config('error.not_work_coupon_exception.code'));
        }
        //判断购物车是否有过期商品或商品库存是否足够
        if (($abnormal = $goodsModel->isAbnormal($goodsCars)) !== false) {
            throw new ApiException($abnormal['msg'], $abnormal['code']);
        }
    }
    private function checkGoodsCarWork($goodsCars, $goodsCarIds)
    {
        if (count(obj2arr($goodsCars)) != count($goodsCarIds)) {
            throw new ApiException(config('error.goods_exception.msg'), config('error.goods_exception.code'));
        }
        return true;
    }
    /**
     * [合并支付]
     * @param  Request $request [description]
     * @param  [type]  $user    [description]
     * @return [type]           [description]
     */
    public function combinePay(Request $request, $user)
    {
        $rules = [
            'order_ids' => 'required|string',
        ];
        $this->valIdate($request, $rules);
        $orderIds = $request->input('order_ids');
        // $combinePayId = getCombinePayId($user->Id);
        // Order::mofifyCombinPayId($orderIds, $combinePayId);
    }
    /**
     * [删除订单]
     * @param  Request $request [Request实例]
     * @return [Integer]           [0表示成功1表示失败]
     */
    public function delete(Request $request, $user, $orderId)
    {
        $rsp = config('response.success');
        $orderModel = new Order();
        $order = $orderModel->get($user->id, $orderId);
        if (!$orderModel->canDelete($order)) {
            return config('response.order_rm_fail');
        }
        $orderModel->remove($user->id, $orderId);
        return $rsp;
    }
    /**
     * [获取分类订单]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function index(Request $request, $user)
    {
        $statuses = ['全部', '待付款', '待发货', '待收货'];
        $rules = [
            'limit' => 'integer|max:100|min:10',
            'page' => 'integer|min:1',
            'status' => 'integer|max:4|min:0'
        ];
        $this->valIdate($request, $rules);
        $rsp = config('response.items');
        $status = $request->input('status', 0);
        $limit = $request->input('limit', 10);
        $page = $request->input('page', 1);
        $orderModel = new Order();
        $orders = $orderModel->mget($user->id, $limit, $page, $status);
        foreach ($statuses as $key => $value) {
            $rsp['actives'][] = ['index' => $key, 'actived' => $status == $key ? true : false ];
        }
        if(empty(obj2arr($orders))) {
            $rsp['status'] = 0;
            $rsp['items'] = [];
            $rsp['num'] = 0;
            $rsp['msg'] = '您还没有此类型订单';
        } else {
            $rsp['status'] = 0;
            $rsp['items'] = $orderModel->getOrdersInfo($orders);
            $rsp['num'] = count($rsp['items']);
        }
        return $rsp;
    }
    /**
     * [更新订单状态为等待发货]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    protected function waitSend($orderIds, $userId)
    {
        if (is_array($orderIds)) {
            (new Order())->mModifyByUser($orderIds, $userId, ['order_status' => 2, 'pay_status' => 2,'pay_time' => time()]);
        } else {
            (new Order())->modifyByUser($orderIds, $userId, ['order_status' => 2, 'pay_status' => 2, 'pay_time' => time()]);
        }
    }
    /**
     * [完成收货]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function finishRecv(Request $request, $user, $orderId)
    {
        $rsp = config('response.success');
        $orderModel = new Order();
        //获取订单
        $order = $orderModel->get($user->id, $orderId);
        if (!$orderModel->canFinish($order)) {
            return config('response.order_cannot_finish');
        }
        //根据该该订单的物流单号更新所有有关该物流的订单
        $orderModel->updateByLogstics($order->logistics_code, ['order_status' => 4]);
        return $rsp;
    }
    /**
     * [取消订单]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function cancel(Request $request, $user, $orderId)
    {
        $orderModel = new Order();
        $goodsModel = new Goods();
        $order = $orderModel->get($user->id, $orderId);
        $rsp = config('response.success');
        if (!$orderModel->cancelable($order)) {
            return config('response.order_no_cancel');
        }
        try {
                app('db')->beginTransaction();
                //更新库存
                $goodsModel->modifyStock([$order->goods_id => $order->goods_num]);
                //更新订单状态
                $orderModel->modifyByUser($orderId, $user->id, ['order_status' => 5]);
                app('db')->commit();
            } catch(Exceptions $e) {
                app('db')->rollBack();
            }
        return $rsp;
    }
    public function getTypeCount($user)
    {
        $orderModel = new Order();
        return $orderModel->getTypeCount($user->id);
    }
}
?>