<?php

use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;

it('renders the workspace sidebar with accessible stable menu groups on every page', function (string $page) {
    $this->loginAsAdmin();

    $html = get(route('admin.seller_workspace.'.$page))->assertOk()->getContent();
    $document = new DOMDocument;
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $groups = $xpath->query('//details[@data-msw-menu-group]');

    expect($groups->length)->toBeGreaterThan(0);

    foreach ($groups as $group) {
        $summary = $xpath->query('./summary', $group)->item(0);
        $panel = $document->getElementById($summary->getAttribute('aria-controls'));

        expect($group->getAttribute('data-msw-menu-group'))->not->toBeEmpty()
            ->and($summary->getAttribute('aria-label'))->not->toBeEmpty()
            ->and($summary->getAttribute('aria-expanded'))->toBeIn(['true', 'false'])
            ->and($panel)->not->toBeNull();
    }
})->with(['overview', 'products', 'orders', 'settings']);

it('shows the same workspace menu on native admin pages instead of the old sidebar', function (string $route, string $active) {
    $this->loginAsAdmin();

    $html = get(route($route))->assertOk()->getContent();
    $document = new DOMDocument;
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $current = $xpath->query('//aside[contains(@class, "msw-menu")]//a[@aria-current="page"]');

    expect($xpath->query('//aside[contains(@class, "msw-menu")]')->length)->toBe(1)
        ->and($xpath->query('//details[@data-msw-menu-group]')->length)->toBeGreaterThan(0)
        ->and($html)->not->toContain('mp-admin-sidebar')
        ->and($html)->not->toContain('data-mp-navigation')
        ->and($html)->not->toContain('sidebarMenuDrawer')
        ->and($current->length)->toBeGreaterThan(0)
        ->and($current->item(0)->getAttribute('href'))->toBe(route($active));
})->with([
    ['admin.sales.orders.index', 'admin.seller_workspace.orders'],
    ['admin.catalog.products.index', 'admin.seller_workspace.products'],
    ['admin.customers.customers.index', 'admin.seller_workspace.customers'],
    ['admin.settings.channels.index', 'admin.seller_workspace.settings'],
    ['admin.account.edit', 'admin.seller_workspace.settings'],
]);

it('shows the same right tool rail on workspace and native admin pages instead of the old utility dock', function (string $route) {
    $this->loginAsAdmin();

    $html = get(route($route))->assertOk()->getContent();
    $document = new DOMDocument;
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $rail = '//aside[contains(@class, "msw-rail")]';

    expect($xpath->query($rail)->length)->toBe(1)
        ->and($xpath->query($rail.'//a[@href="'.route('admin.notification.index').'"]')->length)->toBe(1)
        ->and($xpath->query($rail.'//a[@href="'.route('admin.account.edit').'"]')->length)->toBe(1)
        ->and($xpath->query($rail.'//form[@action="'.route('admin.session.destroy').'"]')->length)->toBe(1)
        ->and($html)->not->toContain('mp-utility-dock')
        ->and($html)->not->toContain('mp-applications-panel');
})->with([
    'admin.seller_workspace.overview',
    'admin.dashboard.index',
    'admin.sales.orders.index',
    'admin.catalog.products.index',
    'admin.settings.channels.index',
]);

it('keeps unauthorized menus out of both sidebar layouts', function () {
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['sales', 'sales.workspace']]);
    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    foreach (['admin.seller_workspace.overview', 'admin.account.edit'] as $route) {
        get(route($route))->assertOk()
            ->assertDontSee('href="'.route('admin.seller_workspace.income').'"', false)
            ->assertDontSee('href="'.route('admin.seller_workspace.products').'"', false)
            ->assertDontSee('href="'.route('admin.settings.users.index').'"', false);
    }

    get(route('admin.seller_workspace.settings'))->assertUnauthorized();
    get(route('admin.settings.users.index'))->assertUnauthorized();
});
