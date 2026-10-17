<?php

namespace App\Providers;

use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;
use Webkul\Theme\ViewRenderEventManager;

class ManPleasureStorefrontServiceProvider extends ServiceProvider
{
    /**
     * Register storefront configuration fields.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('manpleasure-storefront-system.php'), 'core');
    }

    /**
     * Bootstrap storefront services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(app_path('Resources/views'), 'manpleasure-storefront');

        Route::middleware(['web', 'shop', PreventRequestsDuringMaintenance::class])
            ->get('blog', [BlogController::class, 'index'])
            ->name('manpleasure.blog.index');

        Event::listen('bagisto.shop.layout.body.before', function (ViewRenderEventManager $manager): void {
            $manager->addTemplate('manpleasure-storefront::age-gate');
        });

        Event::listen('bagisto.shop.layout.footer.after', function (ViewRenderEventManager $manager): void {
            $manager->addTemplate('manpleasure-storefront::privacy-links');
        });
    }
}
