<?php

$app->get('/ping','PingController@ping');

$app->group(['prefix' => '/v1'], function() use ($app) {
    $app->post('/login', 'UserController@login');
    $app->group(['prefix' => '/user'], function() use ($app) {
    });
    $app->group(['prefix' => '/user/{user_id}', 'where' => ['user_id' => '[0-9]+'], "middleware" => ["my_auth"]], function() use ($app) {
        $app->get('/', 'UserController@get');
    });
    $app->match(['get','post'],'check',['uses'=>'CheckController@check']);
    $app->get('test','CheckController@test');
    $app->any('usertoken',['uses'=>'CheckController@userToken']);
    // $app->any('upload',['uses'=>'CheckController@uploadImg']);
});
