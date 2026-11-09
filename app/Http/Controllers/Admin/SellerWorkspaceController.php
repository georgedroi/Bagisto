<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireUpdateOwner;
use App\Repositories\OrderQueueRepository;
use App\Repositories\ProductLifecycleRepository;
use App\Repositories\ProductStockAlertRepository;
use App\Repositories\ProductStockRepository;
use App\Support\AdminMenu;
use App\Support\OrderWorkflow;
use App\Support\ProductEntryCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Webkul\Admin\Helpers\Dashboard;
use Webkul\Admin\Helpers\Reporting\Customer as CustomerReporting;
use Webkul\Admin\Helpers\Reporting\Sale;
use Webkul\CartRule\Repositories\CartRuleRepository;
use Webkul\CatalogRule\Repositories\CatalogRuleRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Repositories\ProductReviewRepository;
use Webkul\RMA\Repositories\RMARepository;
use Webkul\RMA\Repositories\RMAStatusRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\RefundRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

class SellerWorkspaceController extends Controller
{
    /**
     * Rows shown on one page of a workspace list.
     */
    protected const PER_PAGE = 20;

    /**
     * Review states an admin can filter by.
     */
    protected const REVIEW_TABS = ['pending', 'approved', 'disapproved'];

    /**
     * Rows listed on each overview ranking.
     */
    protected const TOP_LIMIT = 5;

    /**
     * Links on the settings page: group, label, permission, route and route parameters.
     */
    protected const SETTINGS = [
        'store' => [
            ['channels', 'settings.channels', 'admin.settings.channels.index'],
            ['locales', 'settings.locales', 'admin.settings.locales.index'],
            ['currencies', 'settings.currencies', 'admin.settings.currencies.index'],
            ['exchange-rates', 'settings.exchange_rates', 'admin.settings.exchange_rates.index'],
            ['taxes', 'settings.taxes', 'admin.settings.taxes.rates.index'],
            ['tax-categories', 'settings.taxes.tax_categories', 'admin.settings.taxes.categories.index'],
        ],
        'catalog' => [
            ['categories', 'catalog.categories', 'admin.catalog.categories.index'],
            ['attributes', 'catalog.attributes', 'admin.catalog.attributes.index'],
            ['families', 'catalog.families', 'admin.catalog.families.index'],
            ['media', 'catalog.media_bank', 'admin.media.index'],
            ['all-products', 'catalog.products', 'admin.catalog.products.index'],
        ],
        'content' => [
            ['cms', 'cms', 'admin.cms.index'],
            ['themes', 'appearance.themes', 'admin.appearance.themes.index'],
            ['login-page', 'appearance.login_page_builder', 'admin.appearance.login_page_builder.index'],
        ],
        'customers' => [
            ['customer-groups', 'customers.groups', 'admin.customers.groups.index'],
        ],
        'sales' => [
            ['transactions', 'sales.transactions', 'admin.sales.transactions.index'],
            ['return-reasons', 'sales.rma.reasons', 'admin.sales.rma.reasons.index'],
            ['return-rules', 'sales.rma.rules', 'admin.sales.rma.rules.index'],
            ['return-statuses', 'sales.rma.statuses', 'admin.sales.rma.statuses.index'],
            ['return-fields', 'sales.rma.custom-fields', 'admin.sales.rma.custom-fields.index'],
        ],
        'marketing' => [
            ['email-templates', 'marketing.communications.email_templates', 'admin.marketing.communications.email_templates.index'],
            ['events', 'marketing.communications.events', 'admin.marketing.communications.events.index'],
            ['campaigns', 'marketing.communications.campaigns', 'admin.marketing.communications.campaigns.index'],
            ['subscribers', 'marketing.communications.subscribers', 'admin.marketing.communications.subscribers.index'],
            ['url-rewrites', 'marketing.search_seo.url_rewrites', 'admin.marketing.search_seo.url_rewrites.index'],
            ['search-terms', 'marketing.search_seo.search_terms', 'admin.marketing.search_seo.search_terms.index'],
            ['search-synonyms', 'marketing.search_seo.search_synonyms', 'admin.marketing.search_seo.search_synonyms.index'],
            ['sitemaps', 'marketing.search_seo.sitemaps', 'admin.marketing.search_seo.sitemaps.index'],
        ],
        'reports' => [
            ['sales-report', 'reporting.sales', 'admin.reporting.sales.index'],
            ['customers-report', 'reporting.customers', 'admin.reporting.customers.index'],
            ['products-report', 'reporting.products', 'admin.reporting.products.index'],
        ],
        'fulfilment' => [
            ['inventory', 'settings.inventory_sources', 'admin.settings.inventory_sources.index'],
            ['payments', 'configuration', 'admin.configuration.index', ['slug' => 'sales', 'slug2' => 'payment_methods']],
            ['carriers', 'configuration', 'admin.configuration.index', ['slug' => 'sales', 'slug2' => 'carriers']],
        ],
        'team' => [
            ['users', 'settings.users', 'admin.settings.users.index'],
            ['roles', 'settings.roles', 'admin.settings.roles.index'],
            ['account', null, 'admin.account.edit'],
        ],
        'tools' => [
            ['imports', 'settings.data_transfer', 'admin.settings.data_transfer.imports.index'],
            ['configuration', 'configuration', 'admin.configuration.index'],
            ['system-updates', 'settings.system_updates', 'admin.settings.system_updates.index'],
        ],
    ];

    /**
     * Create a controller instance.
     */
    public function __construct(
        protected OrderQueueRepository $orderQueueRepository,
        protected ProductStockRepository $productStockRepository,
        protected ProductStockAlertRepository $productStockAlertRepository,
        protected ProductEntryCatalog $productEntryCatalog,
        protected ShipmentRepository $shipmentRepository,
        protected RMARepository $rmaRepository,
        protected RMAStatusRepository $rmaStatusRepository,
        protected ProductReviewRepository $productReviewRepository,
        protected CartRuleRepository $cartRuleRepository,
        protected CatalogRuleRepository $catalogRuleRepository,
        protected InvoiceRepository $invoiceRepository,
        protected RefundRepository $refundRepository,
        protected ProductLifecycleRepository $productLifecycleRepository,
        protected ProductRepository $productRepository,
        protected CustomerRepository $customerRepository,
        protected AdminMenu $adminMenu
    ) {}

    /**
     * Show everything waiting for action and, for admins who may open the dashboard, the store's
     * figures for a date range and channel.
     */
    public function overview(): View
    {
        $data = request()->validate([
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start'],
            'channel' => ['nullable', 'string', 'in:'.core()->getAllChannels()->pluck('code')->implode(',')],
        ]);

        $orderRoute = bouncer()->hasPermission('sales.workspace')
            ? 'admin.seller_workspace.orders'
            : (bouncer()->hasPermission('sales.orders') ? 'admin.sales.orders.index' : null);

        $productRoute = bouncer()->hasPermission('catalog.products.workspace')
            ? 'admin.seller_workspace.products'
            : (bouncer()->hasPermission('catalog.products') ? 'admin.catalog.products.index' : null);

        return $this->page('overview', [
            'orderRoute' => $orderRoute,
            'productRoute' => $productRoute,
            'stages' => $orderRoute ? $this->orderQueueRepository->workflowCounts() : null,
            'stock' => bouncer()->hasPermission('catalog.products') ? $this->productStockRepository->stockCounts() : null,
            'lowest' => bouncer()->hasPermission('catalog.products') ? $this->productStockRepository->lowestStock(self::TOP_LIMIT) : null,
            'business' => bouncer()->hasPermission('dashboard') ? $this->businessFigures($data['channel'] ?? null) : null,
            'recent' => $orderRoute ? $this->orderList(null, null, 6) : null,
            'quickLinks' => collect([
                ['catalog.product_entry', 'admin.seller_workspace.add_product', 'admin::product-entry.menu'],
                ['sales.workspace', 'admin.seller_workspace.shipping', 'admin::executive-layout.shipping'],
                ['sales.shipments', 'admin.sales.shipments.index', 'admin::executive-layout.shipping'],
                ['sales.refunds.workspace', 'admin.seller_workspace.refunds', 'admin::executive-layout.refunds'],
                ['sales.refunds', 'admin.sales.refunds.index', 'admin::executive-layout.refunds'],
                ['sales.rma.workspace', 'admin.seller_workspace.returns', 'admin::executive-layout.returns'],
                ['sales.rma', 'admin.sales.rma.requests.index', 'admin::executive-layout.returns'],
                ['catalog.products.workspace', 'admin.seller_workspace.products', 'admin::executive-layout.catalog'],
                ['catalog.products', 'admin.catalog.products.index', 'admin::executive-layout.catalog'],
            ])->filter(fn (array $link) => bouncer()->hasPermission($link[0]))->unique(2)->values(),
        ]);
    }

    /**
     * List orders by operational queue, task list stage and search text.
     */
    public function orders(): View
    {
        $data = request()->validate([
            'queue' => ['nullable', 'string', 'in:'.implode(',', array_keys(OrderQueueRepository::QUEUES))],
            'workflow' => ['nullable', 'string', 'in:'.implode(',', array_merge(...array_values(OrderQueueRepository::WORKFLOW_TASKS)))],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return $this->page('orders', [
            'queue' => $data['queue'] ?? null,
            'workflow' => $data['workflow'] ?? null,
            'search' => $data['search'] ?? null,
            'stages' => $this->orderQueueRepository->workflowCounts(),
            'rows' => $this->orderList($data['queue'] ?? null, $data['search'] ?? null, self::PER_PAGE, $data['workflow'] ?? null),
        ]);
    }

    /**
     * Show the shipping queues and the shipments already created.
     */
    public function shipping(): View
    {
        request()->validate(['page' => ['nullable', 'integer', 'min:1']]);

        return $this->page('shipping', [
            'stages' => $this->orderQueueRepository->workflowCounts(),
            'rows' => $this->shipmentRepository->resetScope()
                ->with('order')
                ->scopeQuery(fn ($query) => $query->orderByDesc('id'))
                ->paginate(self::PER_PAGE),
        ]);
    }

    /**
     * List return requests by status.
     */
    public function returns(): View
    {
        $data = request()->validate([
            'status' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        return $this->page('returns', [
            'status' => $data['status'] ?? null,
            'order' => $data['order'] ?? null,
            'statuses' => $this->rmaStatusRepository->all(),
            'rows' => $this->rmaRepository->resetScope()
                ->with('status')
                ->scopeQuery(fn ($query) => $query
                    ->when($data['status'] ?? null, fn ($query, $status) => $query->where('rma_status_id', $status))
                    ->when($data['order'] ?? null, fn ($query, $order) => $query->where('order_id', $order))
                    ->orderByDesc('id'))
                ->paginate(self::PER_PAGE),
        ]);
    }

    /**
     * List product reviews with moderation counts and the approved average.
     */
    public function reviews(): View
    {
        $data = request()->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', self::REVIEW_TABS)],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $summary = $this->productReviewRepository->resetScope()
            ->scopeQuery(fn ($query) => $query->selectRaw("SUM(status = 'pending') as pending, AVG(CASE WHEN status = 'approved' THEN rating END) as average"))
            ->first();

        return $this->page('reviews', [
            'status' => $data['status'] ?? null,
            'rating' => $data['rating'] ?? null,
            'tabs' => self::REVIEW_TABS,
            'pending' => (int) $summary?->pending,
            'average' => $summary?->average === null ? null : round((float) $summary->average, 1),
            'rows' => $this->productReviewRepository->resetScope()
                ->with('product')
                ->scopeQuery(fn ($query) => $query
                    ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                    ->when($data['rating'] ?? null, fn ($query, $rating) => $query->where('rating', $rating))
                    ->orderByDesc('id'))
                ->paginate(self::PER_PAGE),
        ]);
    }

    /**
     * List customers by name, email or phone.
     */
    public function customers(): View
    {
        $data = request()->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = $data['search'] ?? null;

        return $this->page('customers', [
            'search' => $search,
            'total' => $this->customerRepository->resetScope()->count(),
            'rows' => $this->customerRepository->resetScope()
                ->with('group')
                ->scopeQuery(function ($query) use ($search) {
                    if ($search !== null && $search !== '') {
                        $term = '%'.addcslashes($search, '\\%_').'%';

                        $query = $query->where(fn ($query) => $query
                            ->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('phone', 'like', $term));
                    }

                    return $query->orderByDesc('id');
                })
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    /**
     * List products by status, search text, out or low stock filter and raised stock alerts.
     */
    public function products(): View
    {
        $data = request()->validate([
            'tab' => ['nullable', 'string', 'in:'.implode(',', array_keys(ProductStockRepository::PRODUCT_TABS))],
            'search' => ['nullable', 'string', 'max:255'],
            'stock' => ['nullable', 'string', 'in:out,low'],
            'alert' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $tab = $data['tab'] ?? 'all';

        $filter = ProductStockRepository::PRODUCT_TABS[$tab];

        $rows = $this->productStockRepository->paginateList(
            $filter['status'],
            $data['search'] ?? null,
            $data['stock'] ?? null,
            self::PER_PAGE,
            (bool) ($data['alert'] ?? false),
            $filter['state']
        );

        return $this->page('products', [
            'tab' => $tab,
            'tabs' => array_keys(ProductStockRepository::PRODUCT_TABS),
            'states' => $this->productLifecycleRepository->states($rows->pluck('product_id')->all()),
            'search' => $data['search'] ?? null,
            'stockFilter' => $data['stock'] ?? null,
            'stock' => $this->productStockRepository->stockCounts(),
            'alert' => (bool) ($data['alert'] ?? false),
            'alertCount' => $this->productStockRepository->alertedCount(),
            'thresholds' => $this->productStockAlertRepository->thresholds($rows->pluck('product_id')->all()),
            'rows' => $rows,
        ]);
    }

    /**
     * Show the Add product form; it saves through the existing product entry route.
     */
    public function addProduct(): View
    {
        return $this->page('add-product', ['entry' => $this->productEntryCatalog->pageData()]);
    }

    /**
     * Show the Add Product page filled in with a product, or the product editor for what it cannot hold.
     */
    public function editProduct(int $id): View|RedirectResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.products.edit'), 403);

        $editing = $this->productEntryCatalog->productData($id);

        if (! $editing) {
            return redirect()->route('admin.catalog.products.edit', $id);
        }

        $previous = url()->previous();

        return $this->page('add-product', [
            'entry' => $this->productEntryCatalog->pageData(),
            'editing' => $editing,
            'back' => str_starts_with($previous, route('admin.seller_workspace.products')) ? $previous : route('admin.seller_workspace.products', ['tab' => $editing['status'] ? 'active' : 'inactive']),
        ]);
    }

    /**
     * Put a product online or offline from its switch, or mark it obsolete, which also takes
     * it off the store. Online and Offline clear any Draft or Obsolete state.
     */
    public function saveLifecycle(int $id): RedirectResponse
    {
        abort_unless(bouncer()->hasPermission('catalog.products.edit'), 403);

        $data = request()->validate(['state' => ['required', 'string', 'in:'.ProductLifecycleRepository::OBSOLETE.',offline,online']]);

        $this->productRepository->findOrFail($id);

        DB::transaction(function () use ($data, $id): void {
            Event::dispatch('catalog.product.update.before', $id);

            $product = $this->productRepository->update([
                'status' => $data['state'] === 'online' ? 1 : 0,
                'channel' => core()->getDefaultChannelCode(),
                'locale' => core()->getDefaultLocaleCodeFromDefaultChannel(),
            ], $id, ['status']);

            $this->productLifecycleRepository->saveState($id, $data['state'] === ProductLifecycleRepository::OBSOLETE ? ProductLifecycleRepository::OBSOLETE : null);

            Event::dispatch('catalog.product.update.after', $product);
        });

        return back()->with('success', trans('admin::seller-workspace.actions.saved'));
    }

    /**
     * Show the store's cart and catalog promotions.
     */
    public function promotions(): View
    {
        return $this->page('promotions', [
            'cartRules' => $this->cartRuleRepository->resetScope()->scopeQuery(fn ($query) => $query->orderByDesc('id')->limit(self::PER_PAGE))->all(),
            'catalogRules' => $this->catalogRuleRepository->resetScope()->scopeQuery(fn ($query) => $query->orderByDesc('id')->limit(self::PER_PAGE))->all(),
        ]);
    }

    /**
     * Show invoice totals, refunds and the invoice list.
     */
    public function income(): View
    {
        $data = request()->validate([
            'state' => ['nullable', 'string', 'in:pending,pending_payment,paid,overdue,refunded'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $invoices = $this->invoiceRepository->resetScope()
            ->scopeQuery(fn ($query) => $query->selectRaw("SUM(CASE WHEN state = 'paid' THEN base_grand_total ELSE 0 END) as paid, SUM(CASE WHEN state IN ('pending', 'pending_payment', 'overdue') THEN base_grand_total ELSE 0 END) as outstanding"))
            ->first();

        $refunds = $this->refundRepository->resetScope()
            ->scopeQuery(fn ($query) => $query->selectRaw('SUM(base_grand_total) as total'))
            ->first();

        return $this->page('income', [
            'state' => $data['state'] ?? null,
            'paid' => (float) $invoices?->paid,
            'outstanding' => (float) $invoices?->outstanding,
            'refunded' => (float) $refunds?->total,
            'rows' => $this->invoiceRepository->resetScope()
                ->with('order')
                ->scopeQuery(fn ($query) => $query
                    ->when($data['state'] ?? null, fn ($query, $state) => $query->where('state', $state))
                    ->orderByDesc('id'))
                ->paginate(self::PER_PAGE),
        ]);
    }

    /**
     * Show the refunds given to customers.
     */
    public function refunds(): View
    {
        request()->validate(['page' => ['nullable', 'integer', 'min:1']]);

        $refunds = $this->refundRepository->resetScope()
            ->scopeQuery(fn ($query) => $query->selectRaw('SUM(base_grand_total) as total, COUNT(*) as count'))
            ->first();

        return $this->page('refunds', [
            'refunded' => (float) $refunds?->total,
            'count' => (int) $refunds?->count,
            'rows' => $this->refundRepository->resetScope()
                ->with('order')
                ->scopeQuery(fn ($query) => $query->orderByDesc('id'))
                ->paginate(self::PER_PAGE),
        ]);
    }

    /**
     * Show the shipping charged to customers per order, by date range.
     */
    public function fees(): View
    {
        $data = request()->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $range = fn ($query) => $query
            ->when($data['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($data['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to));

        $summary = $this->orderQueueRepository->resetScope()
            ->scopeQuery(fn ($query) => $range($query)->selectRaw('SUM(base_shipping_invoiced) as invoiced, SUM(base_shipping_amount) as charged'))
            ->first();

        return $this->page('fees', [
            'from' => $data['from'] ?? null,
            'to' => $data['to'] ?? null,
            'charged' => (float) $summary?->charged,
            'invoiced' => (float) $summary?->invoiced,
            'rows' => $this->orderQueueRepository->resetScope()
                ->scopeQuery(fn ($query) => $range($query)->orderByDesc('id'))
                ->paginate(self::PER_PAGE),
        ]);
    }

    /**
     * Group the existing admin settings pages the signed-in admin may open.
     */
    public function settings(): View
    {
        $groups = collect(self::SETTINGS)
            ->map(fn (array $links) => collect($links)
                ->filter(fn (array $link) => Route::has($link[2]) && ($link[1] === null || bouncer()->hasPermission($link[1])))
                ->reject(fn (array $link) => $link[0] === 'system-updates' && ! RequireUpdateOwner::allows(auth()->guard('admin')->user()))
                ->map(fn (array $link) => ['label' => $link[0], 'url' => route($link[2], $link[3] ?? [])])
                ->values())
            ->filter(fn ($links) => $links->isNotEmpty());

        return $this->page('settings', ['groups' => $groups]);
    }

    /**
     * Gather the admin dashboard's figures for the requested dates and channel: range and today
     * totals with their change, sales over time, and the best selling products and customers.
     */
    protected function businessFigures(?string $channel): array
    {
        $dashboard = app(Dashboard::class);

        $sale = app(Sale::class);

        $customer = app(CustomerReporting::class);

        $overTime = $sale->getCurrentTotalSalesOverTime();

        return [
            'start' => $dashboard->getStartDate(),
            'end' => $dashboard->getEndDate(),
            'channel' => $channel,
            'channels' => core()->getAllChannels(),
            'range' => $dashboard->getOverAllStats(),
            'today' => [
                'sales' => $sale->getTodaySalesProgress(),
                'orders' => $sale->getTodayOrdersProgress(),
                'customers' => $customer->getTodayCustomersProgress(),
            ],
            'overTime' => $overTime,
            'peak' => max(array_column($overTime, 'total') ?: [0]),
            'products' => $dashboard->getTopSellingProducts(),
            'customers' => $dashboard->getTopCustomers(),
        ];
    }

    /**
     * Page through orders, optionally by queue, task list stage and order number or customer name.
     */
    protected function orderList(?string $queue, ?string $search, int $perPage, ?string $workflow = null)
    {
        $rows = $this->orderQueueRepository->resetScope()
            ->with(['payment', 'shipments'])
            ->scopeQuery(function ($query) use ($queue, $search, $workflow) {
                if ($queue) {
                    $this->orderQueueRepository->constrainQueue($query, $queue);
                }

                if ($workflow) {
                    $this->orderQueueRepository->constrainWorkflow($query, $workflow);
                }

                if ($search !== null && $search !== '') {
                    $term = '%'.addcslashes($search, '\\%_').'%';

                    $query->where(fn ($query) => $query
                        ->where('orders.increment_id', 'like', $term)
                        ->orWhere('orders.customer_first_name', 'like', $term)
                        ->orWhere('orders.customer_last_name', 'like', $term));
                }

                return $query->orderByDesc('orders.id');
            })
            ->paginate($perPage)
            ->withQueryString();

        $rows->getCollection()->each(fn ($order) => $order->setAttribute('workspace_status', $this->orderStatusKey($order)));

        return $rows;
    }

    /**
     * Choose the order status label, keeping invoice completion separate from delivery.
     */
    protected function orderStatusKey($order): string
    {
        if ($order->payment?->method === 'duitnowqr_manual') {
            return OrderWorkflow::current($order);
        }

        return match ($order->status) {
            'pending' => 'to-confirm',
            'pending_payment' => 'pending-payment',
            'processing' => $order->shipments->isEmpty() ? 'to-ship' : 'shipping',
            'completed' => 'shipping',
            'canceled' => 'cancelled',
            'closed' => 'to-return-refund',
            default => 'on-hold',
        };
    }

    /**
     * Render a page in the standalone workspace layout with the shared admin menu.
     */
    protected function page(string $page, array $data): View
    {
        return view('manpleasure-admin::seller-workspace.page', $data + [
            'page' => $page,
            'overviewUrl' => $this->adminMenu->overviewUrl(),
            'queues' => $this->orderQueueRepository->queueCounts(),
            'admin' => auth()->guard('admin')->user(),
        ]);
    }
}
