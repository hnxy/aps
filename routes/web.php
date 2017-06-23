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
        $app->post('/', 'OrdersController@store');
        $app->post('/show', 'OrdersController@showPreOrder');
        $app->group(['prefix' => '/{id}', 'where' => ['id' => '\d{1,16}'] ], function() use($app){
            $app->get('/', 'OrdersController@show');
            $app->delete('/', 'OrdersController@delete');
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
        $app->group(['prefix' => '/{id}', 'where' => ['id' => '\d{1,16}'] ], function() use ($app) {
            $app->post('/', 'AddressController@show');
            $app->put('/', 'AddressController@update');
            $app->delete('/', 'AddressController@delete');
        });
    });
});
