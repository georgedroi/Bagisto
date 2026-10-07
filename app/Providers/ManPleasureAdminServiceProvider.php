<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Webkul\Theme\ViewRenderEventManager;

class ManPleasureAdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(app_path('Resources/views'), 'manpleasure-admin');

        Event::listen('bagisto.admin.catalog.product.edit.form.before', function (ViewRenderEventManager $manager): void {
            $manager->addTemplate('manpleasure-admin::product-editor-blueprint');
        });
    }
}
