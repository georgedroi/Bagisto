<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Theme\ViewRenderEventManager;

class ManPleasureStorefrontServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(app_path('Resources/views'), 'manpleasure-storefront');

        Event::listen('bagisto.shop.layout.body.before', function (ViewRenderEventManager $manager): void {
            if (request()->is('admin/*') || request()->is('api/*')) {
                return;
            }

            $manager->addTemplate('manpleasure-storefront::age-gate');
        });

        Event::listen('bagisto.shop.layout.footer.after', function (ViewRenderEventManager $manager): void {
            $manager->addTemplate('manpleasure-storefront::privacy-links');
        });
    }
}
