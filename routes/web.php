<?php

$app->get('/ping', 'PingController@ping');
$app->get('/check', 'UserController@check');
$app->group(['prefix' => '/v1'], function () use ($app) {
    $app->get('/js_api_params', 'JsApiController@getParams');
    $app->get('login3', 'UserController@login3');
    $app->get('login3_callback', 'UserController@login3Callback');
    $app->post('/login', 'UserController@login');
    $app->post('/order/recive', 'OrderController@recive');
    $app->group(['prefix' => '/user/{user_id}', 'where' => ['user_id' => '[0-9]+'], "middleware" => ["my_auth"]], function() use ($app) {
        $app->get('/', 'UserController@get');
        //订单相关
        $app->group(['prefix' => '/order'], function() use ($app) {
            $app->post('/', 'OrderController@store');
            $app->get('/preOrder', 'OrderController@preOrder');
            $app->get('/', 'OrderController@index');
            $app->get('/count', 'OrderController@getTypeCount');
            $app->get('/unifiedorder', 'OrderController@combinePay');
            $app->group(['prefix' => '/{id}', 'where' => ['id' => '[0-9]{1, 11}'] ], function() use($app){
                $app->get('/', 'OrderController@show');
                $app->put('finish', 'OrderController@finishRecv');
                $app->put('cancel', 'OrderController@cancel');
                $app->patch('/', 'OrderController@delete');
                //物流相关
                $app->group(['prefix' => '/logistics'], function() use ($app) {
                    $app->get('/', 'LogisticsController@getOrderTraces');
                });
            });
        });
        //购物车相关
        $app->group(['prefix' => '/goods_car'], function() use ($app) {
            $app->post('/', 'GoodsCarController@store');
            $app->get('/', 'GoodsCarController@index');
            $app->get('/all', 'GoodsCarController@getAll');
            $app->put('/', 'GoodsCarController@addLogistics');
            $app->group(['prefix' => '/{id}', 'where' => ['id' => '[0-9]{1, 11}'] ], function() use ($app) {
                $app->put('/', 'GoodsCarController@update');
                $app->patch('/', 'GoodsCarController@delete');
            });
        });
        //地址相关
        $app->group(['prefix' => '/address'], function() use ($app) {
            $app->post('/', 'AddressController@store');
            $app->get('/', 'AddressController@index');
            $app->group(['prefix' => 'set/{id}', 'where' => ['id' => '[0-9]{1, 11}'] ], function() use ($app) {
                 $app->put('/', 'AddressController@setDefault');
            });
            $app->group(['prefix' => '/{id}', 'where' => ['id' => '[0-9]{1, 11}'] ], function() use ($app) {
                $app->get('/', 'AddressController@show');
                $app->put('/', 'AddressController@update');
                $app->patch('/', 'AddressController@delete');
            });
        });
        $app->group(['prefix' => 'coupon'], function() use ($app) {
            $app->get('/', 'CouponController@checkCode');
        });
    });
    $app->group(['prefix' => '/goods'], function() use ($app) {
        $app->get('/', 'GoodsController@index');
        $app->group(['prefix' => '/{goods_id}', 'where' => ['goods_id' => '[0-9]{1, 11}']], function () use ($app) {
            $app->get('/', 'GoodsController@show');
        });
    });
    //代理后台相关
    $app->group(['prefix' => '/agent', 'namespace' => 'Agent'], function() use ($app) {
        $app->post('/login', 'UserController@login');
        $app->group(['prefix' => '/{agent_id}', 'where' => ['agent_id' => '[0-9]{1,11}'], 'middleware' => ['my_auth'] ], function () use ($app) {
            $app->group(['middleware' => ['get_auth'] ], function () use ($app) {
                $app->get('/', 'UserController@get');
                $app->group(['prefix' => '/sub_agent'], function () use ($app) {
                    $app->get('/', 'UserController@index');
                    $app->group(['prefix' => '/{sub_agent_id}', 'where' => ['sub_agent_id' => '[0-9]{1, 11}']], function () use ($app) {
                        $app->get('/', 'UserController@show');
                    });
                });
                $app->post('/agent_qr', 'UserController@createAgentQrcode');
                $app->post('/share_qr', 'UserController@createShareQrcode');
                $app->group(['prefix' => '/order'], function() use ($app) {
                    $app->get('/', 'OrderController@index');
                    $app->get('/trade', 'OrderController@trade');
                    $app->group(['prefix' => '/{order_num}', 'where' => ['id' => '[0-9]{1,11}'] ], function () use ($app) {
                        $app->get('/', 'OrderController@show');
                    });
                });
            });
            $app->group(['middleware' => ['add_auth']], function () use ($app) {
                $app->post('/', 'UserController@store');
                $app->group(['prefix' => '/sub_agent/{sub_agent_id}', 'where' => ['sub_agent_id' => '[0-9]{1, 11}']], function () use ($app) {
                        $app->put('/', 'UserController@update');
                        $app->delete('/', 'UserController@delete');
                });
            });
            $app->group(['prefix' => '/coupon'], function() use ($app) {
                $app->get('/', 'CouponController@index');
                $app->post('/', 'CouponController@store');
                $app->group(['prefix' => '/{id}', 'where' => ['id' => '[0-9]{1,11}'] ], function() use ($app) {
                    $app->delete('/', 'CouponController@delete');
                });
            });
        });
    });
    //管理员相关
    $app->group(['prefix' => '/admin', 'namespace' => 'Admin'], function () use ($app) {
        $app->group(['prefix' => '/{admin_id}', 'where' => ['admin_id' => '[0-9]{1,11}'], 'middleware' => ['my_auth'] ], function () use ($app) {
            //商品相关
            $app->group(['prefix' => '/goods'], function () use ($app) {
                $app->post('/', 'GoodsController@store');
                $app->group(['prefix' => '/{goods_id}', 'where' => ['goods_id' => '[0-9]{1, 11}']], function () use ($app) {
                    $app->post('/img', 'GoodsController@saveImg');
                });
            });
            $app->post('/agent_qr', 'UserController@createAgentQrcode');
            //订单相关
            $app->group(['prefix' => '/order'], function() use ($app) {
                $app->get('/', 'OrderController@index');
                $app->put('/logistics', 'OrderController@addLogistics');
                $app->group(['prefix' => '/{id}', 'where' => ['id' => '[0-9]{1,11}'] ], function () use ($app) {
                        $app->get('/', 'OrderController@show');
                });
            });
        });
    });
});


