<?php
    return [
        'token' => 'shop',
        'appid' => 'wxbc7cdae8c2052270',
        'shopid' => '1435822002',
        'apiSecret' => '192006250b4c09247ec02edce69f6a1a',
        'appSecret' => '572ca55969322d28de4e3136d798280a',
        'index' => 'http://xnd.cg0.me',
        'EBusinessID' => '1293532',
        'AppKey' => '7266fdfe-70a1-47ba-b3e5-2c99ebdf70dc',
        'ReqURL' => 'http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx',
        'pay_status' => ['', '未支付', '已支付', '已退款'],
        'order_status' => ['所有订单', '待付款', '待发货', '待收货', '已收货', '已取消'],
        'pay_by' => ['微信支付'],
        'express_offic_phone' => ['SF' => '0731-55820626'],
        'order_work_time' => 12*3600,//以秒为单位
        'max_goods_num' => 999,
        'host' => ['aps.cg0.me'],
        'notify_url' => url('/v1/order/recive'),
        'qrcode_path' => '/upload/xnd/images/er_code/',
        'goodsimg_path' => '/upload/xnd/images/goods/',
    ]
?>