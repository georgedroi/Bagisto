<?php

namespace Webkul\Payment\Providers;

use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\ServiceProvider;
use Webkul\Payment\Listeners\RejectUnapprovedGateway;
use Webkul\Payment\Payment\Adapters\DefaultPaymentGatewayAdapter;
use Webkul\Payment\Payment\Adapters\PaymentGatewayAdapterInterface;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__.'/../Http/helpers.php';

        $this->app['events']->listen(
            RouteMatched::class,
            RejectUnapprovedGateway::class
        );

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Http/routes.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'payment');

        $this->app->register(EventServiceProvider::class);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();

        $this->app->singleton(
            PaymentGatewayAdapterInterface::class,
            DefaultPaymentGatewayAdapter::class
        );
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/payment-methods.php',
            'payment_methods'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/system.php',
            'core'
        );
    }
}
