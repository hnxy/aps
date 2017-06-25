<?php

$app->get('/ping','PingController@ping');
$app->post('check', 'UserController@check');
$app->group(['prefix' => '/v1'], function() use ($app) {
    $app->post('/login', 'UserController@login');
    $app->group(['prefix' => '/user'], function() use ($app) {
    });

    $app->group(['prefix' => '/user/{user_id}', 'where' => ['user_id' => '[0-9]+'], "middleware" => ["my_auth"]], function() use ($app) {
        $app->get('/', 'UserController@get');
    });
    $app->get('login3', 'UserController@login3');
    $app->get('login3_callback', 'UserController@login3Callback');
    $app->group(['prefix' => '/goods'], function() use ($app) {
        $app->get('/', 'GoodsController@index');
        $app->get('/{id}', 'GoodsController@show');
    });
    $app->group(['prefix' => '/order'], function() use ($app) {
        $app->post('/', 'OrderController@store');
        $app->post('/show', 'OrderController@showPreOrder');
        $app->group(['prefix' => '/classes/{state}', 'where' => ['state' => '\d'] ], function() use($app){
            $app->get('/', 'OrderController@getClassesOrder');
        });
        $app->group(['prefix' => '/{id}', 'where' => ['id' => '\d{1,16}'] ], function() use($app){
            $app->get('/', 'OrderController@show');
            $app->delete('/', 'OrderController@delete');
        });
    });
    $app->group(['prefix' => '/goods_car'], function() use ($app) {
        $app->post('/', 'GoodsCarController@store');
        $app->get('/', 'GoodsCarController@index');
        $app->group(['prefix' => '/{id}', 'where' => ['id' => '\d{1,16}'] ], function() use ($app) {
            $app->put('/', 'GoodsCarController@update');
            $app->delete('/', 'GoodsCarController@delete');
        });
    });
    $app->group(['prefix' => '/address'], function() use ($app) {
        $app->post('/', 'AddressController@store');
        $app->get('/', 'AddressController@index');
        $app->get('/province', 'AddressController@getProvince');
        $app->get('/city', 'AddressController@getCity');
        $app->get('/area', 'AddressController@getArea');
        $app->group(['prefix' => 'set/{id}', 'where' => ['id' => '\d{1,16}'] ], function() use ($app) {
             $app->put('/', 'AddressController@setDefault');
        });
        $app->group(['prefix' => '/{id}', 'where' => ['id' => '\d{1,16}'] ], function() use ($app) {
            $app->post('/', 'AddressController@show');
            $app->put('/', 'AddressController@update');
            $app->delete('/', 'AddressController@delete');
        });
    });
    $app->group(['prefix' => '/coupon'], function() use ($app) {
        $app->post('/', 'CouponController@store');
        $app->get('/', 'CouponController@getCode');
        $app->post('/check', 'CouponController@checkCode');
        $app->group(['prefix' => '/{id}', 'where' => ['id' => '\d{1,16}'] ], function() use ($app) {
            // $app->post('/', 'CouponController@show');
            $app->put('/', 'CouponController@update');
            $app->delete('/', 'CouponController@delete');
        });
    });
});
