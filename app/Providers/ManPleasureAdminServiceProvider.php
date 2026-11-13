<?php

namespace App\Providers;

use App\Support\OrderWorkflow;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Webkul\Payment\Repositories\PaymentReceiptRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Theme\ViewRenderEventManager;

class ManPleasureAdminServiceProvider extends ServiceProvider
{
    /**
     * Register project administration menus and their explicit permissions.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('manpleasure-update-menu.php'), 'menu.admin');
        $this->mergeConfigFrom(config_path('manpleasure-update-acl.php'), 'acl');
        $this->mergeConfigFrom(config_path('manpleasure-product-entry-menu.php'), 'menu.admin');
        $this->mergeConfigFrom(config_path('manpleasure-product-entry-acl.php'), 'acl');
        $this->mergeConfigFrom(config_path('manpleasure-order-management-acl.php'), 'acl');
        $this->mergeConfigFrom(config_path('manpleasure-seller-workspace-acl.php'), 'acl');
    }

    /**
     * Bootstrap project administration routes and order workflow hooks.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(app_path('Resources/views'), 'manpleasure-admin');
        $this->loadRoutesFrom(base_path('routes/manpleasure-updates.php'));
        $this->loadRoutesFrom(base_path('routes/manpleasure-product-entry.php'));
        $this->loadRoutesFrom(base_path('routes/manpleasure-order-management.php'));
        $this->loadRoutesFrom(base_path('routes/manpleasure-seller-workspace.php'));

        Event::listen('bagisto.admin.catalog.product.edit.form.after', function (ViewRenderEventManager $manager): void {
            $manager->addTemplate('manpleasure-admin::product-editor-actions');
        });

        Event::listen('bagisto.admin.sales.order.right_component.before', function (ViewRenderEventManager $manager): void {
            if ($manager->getParam('order')) {
                $order = $manager->getParam('order');
                $workflowOrder = app(OrderRepository::class)
                    ->with(['payment', 'shipments'])
                    ->find($order->id);
                if ($workflowOrder->payment?->method === 'duitnowqr_manual') {
                    $receipts = app(PaymentReceiptRepository::class)
                        ->scopeQuery(fn ($query) => $query->where('order_id', $order->id)->orderByDesc('id'))
                        ->all();

                    $manager->addTemplate('manpleasure-admin::order-workflow', ['order' => $workflowOrder, 'receipts' => $receipts]);
                }
                $manager->addTemplate('manpleasure-admin::order-whatsapp');
            }
        });

        Event::listen('bagisto.shop.customers.account.orders.view.after', function (ViewRenderEventManager $manager): void {
            if ($order = $manager->getParam('order')) {
                $workflowOrder = app(OrderRepository::class)
                    ->with('payment')
                    ->find($order->id);
                if ($workflowOrder->payment?->method === 'duitnowqr_manual') {
                    $receipts = app(PaymentReceiptRepository::class)
                        ->scopeQuery(fn ($query) => $query->where('order_id', $order->id)->orderByDesc('id'))
                        ->all();

                    $manager->addTemplate('manpleasure-admin::customer-order-receipt', ['order' => $workflowOrder, 'receipts' => $receipts]);
                }
            }
        });

        Event::listen('sales.shipment.save.before*', function (string $event, array $data): void {
            $order = app(OrderRepository::class)->with('payment')->findOrFail($data['order_id']);

            if ($order->payment?->method === 'duitnowqr_manual' && ! OrderWorkflow::paymentVerified($order)) {
                throw ValidationException::withMessages(['shipment' => trans('admin::order-status.payment-required')]);
            }
        });

        Event::listen('bagisto.shop.checkout.success.continue-shopping.before', function (ViewRenderEventManager $manager): void {
            if ($order = $manager->getParam('order')) {
                $order = app(OrderRepository::class)->with('payment')->findOrFail($order->id);

                if ($order->payment?->method === 'duitnowqr_manual') {
                    $manager->addTemplate('manpleasure-admin::checkout-receipt-link', compact('order'));
                }
            }
        });
    }
}
