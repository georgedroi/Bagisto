<?php

namespace App\Providers;

use App\Http\Requests\ManualPaymentConfigurationRequest;
use App\Support\OrderWorkflow;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Webkul\Admin\Http\Requests\ConfigurationForm;
use Webkul\Payment\Repositories\PaymentReceiptRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Theme\ViewRenderEventManager;

/**
 * Register project administration tools and guarded order workflow extensions.
 */
class ManPleasureAdminServiceProvider extends ServiceProvider
{
    /**
     * Attempts one account may make from one address per minute at each sign-in step.
     */
    public const ACCOUNT_ATTEMPTS_PER_MINUTE = 5;

    /**
     * Attempts one address may make per minute at each sign-in step, across all accounts.
     */
    public const ADDRESS_ATTEMPTS_PER_MINUTE = 20;

    /**
     * Second-factor attempts one signed-in account may make per minute, from all addresses together.
     */
    public const SECOND_FACTOR_ACCOUNT_ATTEMPTS_PER_MINUTE = 10;

    /**
     * Second-factor attempts one signed-in account may make per hour, from all addresses together.
     */
    public const SECOND_FACTOR_ACCOUNT_ATTEMPTS_PER_HOUR = 30;

    /**
     * Register project administration menus and their explicit permissions.
     */
    public function register(): void
    {
        $this->app->bind(ConfigurationForm::class, ManualPaymentConfigurationRequest::class);

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

        $this->registerAuthenticationRateLimiters();

        Event::listen('bagisto.admin.catalog.product.edit.form.after', function (ViewRenderEventManager $manager): void {
            $manager->addTemplate('manpleasure-admin::product-editor-actions');
        });

        Event::listen('bagisto.admin.sales.order.right_component.before', function (ViewRenderEventManager $manager): void {
            if ($manager->getParam('order')) {
                $order = $manager->getParam('order');
                $workflowOrder = app(OrderRepository::class)
                    ->with(['payment', 'shipments'])
                    ->find($order->id);
                if (OrderWorkflow::supports($workflowOrder)) {
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
                if (OrderWorkflow::supports($workflowOrder)) {
                    $receipts = app(PaymentReceiptRepository::class)
                        ->scopeQuery(fn ($query) => $query->where('order_id', $order->id)->orderByDesc('id'))
                        ->all();

                    $manager->addTemplate('manpleasure-admin::customer-order-receipt', ['order' => $workflowOrder, 'receipts' => $receipts]);
                }
            }
        });

        Event::listen('sales.shipment.save.before*', function (string $event, array $data): void {
            $order = app(OrderRepository::class)->with('payment')->findOrFail($data['order_id']);

            if (OrderWorkflow::supports($order) && ! OrderWorkflow::paymentVerified($order)) {
                throw ValidationException::withMessages(['shipment' => trans('admin::order-status.payment-required')]);
            }
        });

        Event::listen('sales.invoice.save.before*', function (string $event, array $data): void {
            $order = app(OrderRepository::class)->scopeQuery(fn ($query) => $query->lockForUpdate())
                ->with('payment')->findOrFail($data['order_id']);

            if (OrderWorkflow::supports($order) && (! OrderWorkflow::paymentVerified($order) || ! $order->canInvoice())) {
                throw ValidationException::withMessages(['invoice' => trans('admin::order-status.wait-for-payment-action')]);
            }
        });

        Event::listen('bagisto.shop.checkout.success.continue-shopping.before', function (ViewRenderEventManager $manager): void {
            if ($order = $manager->getParam('order')) {
                $order = app(OrderRepository::class)->with('payment')->findOrFail($order->id);

                if (OrderWorkflow::supports($order)) {
                    $manager->addTemplate('manpleasure-admin::checkout-receipt-link', compact('order'));
                }
            }
        });
    }

    /**
     * Give the password step and each second-factor step its own named limiter, so one step never spends
     * another step's attempts. Each limiter allows a few attempts per account and address and a looser cap
     * per address. The address is request()->ip(), which honours only the proxies trusted in
     * config/mansecretpleasure.php, so an untrusted client cannot change it with forwarding headers.
     */
    protected function registerAuthenticationRateLimiters(): void
    {
        RateLimiter::for('admin-login', fn (Request $request) => $this->authenticationLimits('email:'.$this->normalizedEmail($request), $request));

        RateLimiter::for('admin-two-factor-verify', fn (Request $request) => $this->secondFactorLimits($this->adminIdentity($request), $request));

        RateLimiter::for('admin-two-factor-enable', fn (Request $request) => $this->secondFactorLimits($this->adminIdentity($request), $request));
    }

    /**
     * The sign-in limits plus a cap per signed-in account across all addresses. Whoever reaches a second-factor
     * step already holds that account's password, so spreading code guesses over many addresses must not multiply
     * the attempts; the per-minute and per-hour caps bound them for the account as a whole.
     *
     * @return array<int, Limit>
     */
    protected function secondFactorLimits(string $identity, Request $request): array
    {
        return [
            ...$this->authenticationLimits($identity, $request),
            Limit::perMinute(self::SECOND_FACTOR_ACCOUNT_ATTEMPTS_PER_MINUTE)->by('account-any-address:'.$identity),
            Limit::perHour(self::SECOND_FACTOR_ACCOUNT_ATTEMPTS_PER_HOUR)->by('account-any-address-hourly:'.$identity),
        ];
    }

    /**
     * The per-account-and-address limit and the per-address cap for one sign-in step.
     *
     * @return array<int, Limit>
     */
    protected function authenticationLimits(string $identity, Request $request): array
    {
        return [
            Limit::perMinute(self::ACCOUNT_ATTEMPTS_PER_MINUTE)->by('account:'.$identity.'|'.$request->ip()),
            Limit::perMinute(self::ADDRESS_ATTEMPTS_PER_MINUTE)->by('address:'.$request->ip()),
        ];
    }

    /**
     * The submitted email trimmed and lower-cased, so case or spacing changes share one account limit.
     */
    protected function normalizedEmail(Request $request): string
    {
        $email = $request->input('email');

        return is_string($email) ? Str::lower(trim($email)) : '';
    }

    /**
     * The signed-in administrator awaiting or managing a second factor, or the session when none is signed in.
     */
    protected function adminIdentity(Request $request): string
    {
        if ($id = auth('admin')->id()) {
            return 'admin:'.$id;
        }

        return 'session:'.($request->hasSession() ? $request->session()->getId() : '');
    }
}
