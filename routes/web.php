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
        $app->post('/', 'GoodsController@store');
        $app->get('/{id}', 'GoodsController@show');
    });
});
