<?php

$app->get('/ping', 'PingController@ping');
$app->get('user/{id}/{username}', 'UserController@test');
$app->get('check', 'UserController@check');
$app->group(['prefix' => '/v1'], function () use ($app) {
    $app->post('/login', 'UserController@login');
    $app->group(['prefix' => '/user/{user_id}', 'where' => ['user_id' => '[0-9]+'], "middleware" => ["my_auth"]], function() use ($app) {
        $app->get('/', 'UserController@get');
        //订单相关
        $app->group(['prefix' => '/order'], function() use ($app) {
            $app->post('/', 'OrderController@store');
            $app->get('/preOrder', 'OrderController@showPreOrder');
            $app->get('/', 'OrderController@getClassesOrder');
            $app->group(['prefix' => '/{id}', 'where' => ['id' => '[0-9]{1,16}'] ], function() use($app){
                $app->get('/', 'OrderController@show');
                $app->put('finish', 'OrderController@finishRecv');
                $app->put('cancel', 'OrderController@cancel');
                $app->patch('/', 'OrderController@delete');
            });
        });
        //购物车相关
        $app->group(['prefix' => '/goods_car'], function() use ($app) {
            $app->post('/', 'GoodsCarController@store');
            $app->get('/', 'GoodsCarController@index');
            $app->get('/all', 'GoodsCarController@getAll');
            $app->put('/', 'GoodsCarController@addLogistics');
            $app->group(['prefix' => '/{id}', 'where' => ['id' => '[0-9]{1,16}'] ], function() use ($app) {
                $app->put('/', 'GoodsCarController@update');
                $app->patch('/', 'GoodsCarController@delete');
            });
        });
        //地址相关
        $app->group(['prefix' => '/address'], function() use ($app) {
            $app->post('/', 'AddressController@store');
            $app->get('/', 'AddressController@index');
            $app->group(['prefix' => 'set/{id}', 'where' => ['id' => '[0-9]{1,16}'] ], function() use ($app) {
                 $app->put('/', 'AddressController@setDefault');
            });
            $app->group(['prefix' => '/{id}', 'where' => ['id' => '[0-9]{1,16}'] ], function() use ($app) {
                $app->get('/', 'AddressController@show');
                $app->put('/', 'AddressController@update');
                $app->patch('/', 'AddressController@delete');
            });
        });
        $app->group(['prefix' => 'coupon'], function() use ($app) {
            $app->get('/', 'CouponController@checkCode');
        });
        //物流相关
        $app->group(['prefix' => '/logistics'], function() use ($app) {
            $app->get('/', 'LogisticsController@getOrderTraces');
        });

    });

    $app->get('login3', 'UserController@login3');
    $app->get('login3_callback', 'UserController@login3Callback');

    //后台相关
    $app->group(['prefix' => '/admin', 'namespace' => 'Admin'], function() use ($app) {
        $app->group(['prefix' => '/agent/{agent_id}', 'where' => ['agent_id' => '[0-9]{1,11}'], 'middleware' => ['agent_auth'] ], function () use ($app) {
            $app->group(['middleware' => ['add_auth'] ], function () use ($app) {
                $app->post('/goods', 'GoodsController@store');
                $app->group(['prefix' => '/order'], function() use ($app) {
                    $app->put('/logistics', 'OrderController@addLogistics');
                });
            });
            $app->group(['middleware' => ['get_auth'] ], function () use ($app) {
                $app->group(['prefix' => '/order'], function() use ($app) {
                    $app->get('/', 'OrderController@index');
                    $app->group(['prefix' => '/{id}', 'where' => ['id' => '[0-9]{1,11}'] ], function () use ($app) {

                    });
                });
                $app->group(['prefix' => '/coupon'], function() use ($app) {
                    $app->get('/', 'CouponController@index');
                });
            });
            $app->group(['prefix' => '/coupon'], function() use ($app) {
                $app->post('/', 'CouponController@store');
                $app->group(['prefix' => '/{id}', 'where' => ['id' => '[0-9]{1,11}'] ], function() use ($app) {
                    $app->delete('/', 'CouponController@delete');
                });
            });
        });
        $app->group(['prefix' => '/agent'], function () use ($app) {
            $app->post('/', 'UserController@store');
            $app->post('/login', 'UserController@login');
        });
    });
});


