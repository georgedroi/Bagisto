<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Theme\ViewRenderEventManager;

class ManPleasureAdminServiceProvider extends ServiceProvider
{
    /**
     * Register the project update menu and its explicit permission mappings.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('manpleasure-update-menu.php'), 'menu.admin');
        $this->mergeConfigFrom(config_path('manpleasure-update-acl.php'), 'acl');
    }

    /**
     * Bootstrap the existing administration views and project update routes.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(app_path('Resources/views'), 'manpleasure-admin');
        $this->loadRoutesFrom(base_path('routes/manpleasure-updates.php'));

        Event::listen('bagisto.admin.catalog.product.edit.form.before', function (ViewRenderEventManager $manager): void {
            $manager->addTemplate('manpleasure-admin::product-editor-blueprint');
        });
    }
}
