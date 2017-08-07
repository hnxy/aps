<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class FileUploadServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Http\FileUpload\FileInterface', 'App\Http\FileUpload\Upload');
        $this->app->singleton('upload', App\Http\FileUpload\Upload::class);
    }
}