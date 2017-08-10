<?php
    return [
        'token' => 'shop',
        'appid' => 'wx048ce38473905f83',
        'shopid' => '1485838782',
        'apiSecret' => '192006250b4c09247ec02edce69f5a1a',
        'appSecret' => '693d4dadacd6aa7b08aa2dc1e723e3a5',
        'index' => 'http://xnd.cg0.me/?#/',
        'EBusinessID' => '1293532',
        'AppKey' => '7266fdfe-70a1-47ba-b3e5-2c99ebdf70dc',
        'ReqURL' => 'http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx',
        'pay_status' => ['', '未支付', '已支付', '已退款'],
        'order_status' => ['所有订单', '待付款', '待发货', '待收货', '已收货', '已取消'],
        'pay_by' => ['微信支付'],
        'express_offic_phone' => ['SF' => '0731-55820626'],
        'order_work_time' => 12*3600,//以秒为单位
        'max_goods_num' => 999,
        'host' => 'xnd.cg0.me',
        'notify_url' => 'http://aps.cg0.me/v1/order/recive',
        'body' => '鲜农达-海鲜',
        'qrcode_path' => env('DOWN_LOAD_FILE') . '/iamges/qr_code',
        'review_status' => ['未审核', '已通过审核', '未通过审核'],
        'image_visit_path' => '/upload/xnd/iamges',
    ]
?>