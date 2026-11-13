<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminMenu
{
    /**
     * Workspace pages in menu order: route name suffix and the permission that opens it.
     */
    public const PAGES = [
        'overview' => 'sales.workspace',
        'orders' => 'sales.workspace',
        'shipping' => 'sales.workspace',
        'returns' => 'sales.rma.workspace',
        'reviews' => 'customers.reviews.workspace',
        'customers' => 'customers.customers.workspace',
        'products' => 'catalog.products.workspace',
        'promotions' => 'marketing.promotions.workspace',
        'income' => 'sales.invoices.workspace',
        'refunds' => 'sales.refunds.workspace',
        'fees' => 'sales.workspace',
        'settings' => 'settings.workspace',
    ];

    /**
     * Left menu groups and their pages; "add" opens the Add product page.
     */
    public const MENU = [
        'common' => ['overview', 'orders', 'promotions'],
        'products' => ['products', 'add'],
        'orders' => ['orders', 'shipping', 'returns', 'reviews'],
        'customers' => ['customers'],
        'marketing' => ['promotions'],
        'finance' => ['income', 'refunds', 'fees'],
        'settings' => ['settings'],
    ];

    /**
     * Native admin route name prefixes and the menu item that holds them; other admin pages sit under Settings.
     */
    protected const NATIVE = [
        'admin.dashboard.' => 'overview',
        'admin.sales.orders.' => 'orders',
        'admin.sales.shipments.' => 'shipping',
        'admin.sales.rma.requests.' => 'returns',
        'admin.sales.invoices.' => 'income',
        'admin.sales.refunds.' => 'refunds',
        'admin.catalog.products.' => 'products',
        'admin.customers.customers.review.' => 'reviews',
        'admin.customers.customers.' => 'customers',
        'admin.marketing.promotions.' => 'promotions',
    ];

    /**
     * Menu groups holding only the pages the signed-in admin may open.
     */
    public function groups(): Collection
    {
        $allowed = collect(self::PAGES)->filter(fn (string $permission, string $item) => bouncer()->hasPermission($permission)
            || ($item === 'overview' && bouncer()->hasPermission('dashboard')))->keys();

        return collect(self::MENU)
            ->map(fn (array $items) => array_values(array_filter($items, fn (string $item) => $item === 'add'
                ? bouncer()->hasPermission('catalog.product_entry')
                : $allowed->contains($item))))
            ->filter();
    }

    /**
     * Home address for the signed-in admin: the dashboard, the workspace overview or the account page.
     */
    public function overviewUrl(): string
    {
        return route(bouncer()->hasPermission('dashboard') ? 'admin.dashboard.index'
            : (bouncer()->hasPermission('sales.workspace') ? 'admin.seller_workspace.overview' : 'admin.account.edit'));
    }

    /**
     * Menu item to highlight for a workspace page, or for the current native admin page when none is given.
     */
    public function activeItem(?string $page = null): string
    {
        if ($page !== null) {
            return $page === 'add-product' ? 'add' : $page;
        }

        $route = (string) Route::currentRouteName();

        foreach (self::NATIVE as $prefix => $item) {
            if (str_starts_with($route, $prefix)) {
                return $item;
            }
        }

        return 'settings';
    }
}
