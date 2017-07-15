<?php
namespace App\Providers;

use mmghv\LumenRouteBinding\RouteBindingServiceProvider as BaseServiceProvider;

class RouteBindingServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider
     */
    public function boot()
    {
        $binder = $this->binder;
        $binder->bind('user_id', 'App\Models\User@get');
        $binder->bind('agent_id', 'App\Models\Agent@get');
        // $binder->compositeBind(['id', 'username'], function($id, $username) {
        //     return [(new \App\Models\User())->get($id), $username];
        // });
    }
}
