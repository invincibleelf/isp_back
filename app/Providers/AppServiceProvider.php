<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Services\EmailService','App\Services\EmailServiceImpl');
        $this->app->bind('App\Repositories\MerchantRepository', 'App\Repositories\MerchantRepositoryImpl');
        $this->app->bind('App\Services\MerchantService','App\Services\MerchantServiceImpl');
    }
}
