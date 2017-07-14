<?php
    return [
        'curl_err' => ['msg' => '', 'code' => 1],
        'get_web_token_err' => ['msg' => '', 'code' => 2],
        'insert_user_arr_err' => ['msg' => '', 'code' => 3],
        'update_user_arr_err' => ['msg' => '', 'code' => 4],
        'update_goods_car_err' => ['msg' =>'', 'code' => 5],
        'goods_exception' => ['msg' =>'包含异常的商品信息,请重新选择', 'code' => 6],
        'add_goods_exception' => ['msg' =>'存在商品未开售或者已下架', 'code' => 7],
        'addr_null_err' => ['msg' =>'请填写您的收获地址', 'code' => 8],
        'goods_car_empty_err' => ['msg' =>'请填写您的收获地址', 'code' => 9],
        'order_empty_err' => ['msg' =>'该订单不存在', 'code' => 10],
        'order_goods_car_diff_err' => ['msg' =>'订单信息与购物车不一致', 'code' => 11],
        'logistics_request_err' => ['msg' => '', 'code' => 12],
        'goods_not_enough_exception' => ['msg' => '', 'code' => 13],
        'no_traces_exception' => ['msg' => '', 'code' => 14],
        'not_work_coupon_exception' => ['msg' => '', 'code' => 15],
    ];
?>