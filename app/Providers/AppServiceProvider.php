<?php

namespace App\Providers;

use App\Vk\ApiClient;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $vk = ApiClient::instance();
        View::composer('layout', function ($view) {
            $view->with('vkAccount', ApiClient::instance()->getAccount());
            $view->with('vkClientId', ApiClient::instance()->getClientId());
        });
    }
}
