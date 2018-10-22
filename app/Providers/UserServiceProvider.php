<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Services\UserService', 'App\Services\UserServiceImpl');
        $this->app->bind('App\Repositories\UserRepository','App\Repositories\UserRepositoryImpl');
    }
}
