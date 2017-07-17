<?php


return [
    'success' => ['code' => 0, 'msg' => 'success' ],
    'items' => ['status' => 0, 'msg' =>'success', 'num' => 0,'items' => [] ],
    'addr_not_work' => ['code' => 1, 'msg' => '该地址不合法' ],
    'addr_rm_fail' => ['code' => 2, 'msg' => '删除地址失败' ],
    'addr_set_fail' => ['code' => 3, 'msg' => '设置默认地址失败' ],
    'coupon_get_fail' => ['code' => 4, 'msg' => '无法兑换该优惠码' ],
    'coupon_not_work' => ['code' => 5, 'msg' => '该优惠码无效' ],
    'order_not_exist' => ['code' => 6, 'msg' => '该订单不存在' ],
    'order_rm_fail' => ['code' => 7, 'msg' => '删除订单失败' ],
    'order_cannot_finish' => ['code' => 8, 'msg' => '该订单无法完成收货' ],
    'order_no_cancel' => ['code' => 9, 'msg' => '该订单不能取消' ],
    'order_cannot_cancel' => ['code' => 10, 'msg' => '该订单不能删除' ],
    'addr_not_exist' => ['code' => 11, 'msg' => '地址不存在' ],
    'goods_car_rm_fail' => ['code' => 12, 'msg' => '购物车删除失败' ],
    'goods_car_update_fail' => ['code' => 13, 'msg' => '购物车更新失败' ],
];
?>