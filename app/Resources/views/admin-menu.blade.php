@php
    $adminMenu = app(\App\Support\AdminMenu::class);
    $menuGroups = $adminMenu->groups();
    $menuHome = $adminMenu->overviewUrl();
    $activeItem = $adminMenu->activeItem($menuPage ?? null);
    $openGroup = collect($menuGroups)->except('common')->search(fn (array $items) => in_array($activeItem, $items, true)) ?: 'common';
@endphp

<aside class="msw-menu" aria-label="{{ trans('admin::admin-navigation.navigation') }}">
    <button type="button" class="msw-menu-toggle" data-msw-menu-toggle data-msw-collapse="{{ trans('admin::seller-workspace.menu.collapse') }}" data-msw-expand="{{ trans('admin::seller-workspace.menu.expand') }}" aria-label="{{ trans('admin::seller-workspace.menu.collapse') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])</button>
    <a class="msw-logo" href="{{ $menuHome }}">
        <img class="msw-logo-mark" src="{{ asset('images/msp-admin-mark.png') }}" alt="{{ config('app.name') }}">
        @if ($logo = core()->getConfigData('general.design.admin_logo.logo_image'))
            <img src="{{ Storage::url($logo) }}" alt="{{ config('app.name') }}">
        @else
            <strong>{{ config('app.name') }}</strong>
        @endif
    </a>
    <label class="msw-menu-search">
        <span class="msw-hidden">{{ trans('admin::seller-workspace.menu.search') }}</span>
        <input type="search" placeholder="{{ trans('admin::seller-workspace.menu.search') }}" data-msw-menu-search>
        @include('manpleasure-admin::seller-workspace.icon', ['name' => 'search'])
    </label>
    <button class="msw-menu-search-shortcut" type="button" data-msw-search-shortcut aria-label="{{ trans('admin::seller-workspace.menu.search') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'search'])</button>
    <nav class="msw-menu-list">
        @foreach ($menuGroups as $group => $items)
            <details @if ($group === $openGroup) open @endif data-msw-menu-group="{{ $group }}">
                <summary aria-controls="msw-menu-{{ $group }}" aria-expanded="{{ $group === $openGroup ? 'true' : 'false' }}" aria-label="{{ trans('admin::admin-navigation.toggle-section', ['name' => trans('admin::seller-workspace.menu.'.$group)]) }}">
                    @include('manpleasure-admin::seller-workspace.icon', ['name' => $group])
                    <span>{{ trans('admin::seller-workspace.menu.'.$group) }}</span>
                    @include('manpleasure-admin::seller-workspace.icon', ['name' => 'up'])
                </summary>
                <div class="msw-menu-links" id="msw-menu-{{ $group }}">
                @foreach ($items as $item)
                    @if ($item === 'add')
                        <a href="{{ route('admin.seller_workspace.add_product') }}" @class(['is-active' => $activeItem === 'add']) @if ($activeItem === 'add') aria-current="page" @endif>{{ trans('admin::seller-workspace.products.add') }}</a>
                    @else
                        <a href="{{ $item === 'overview' ? $menuHome : route('admin.seller_workspace.'.$item) }}" @class(['is-active' => $item === $activeItem]) @if ($item === $activeItem) aria-current="page" @endif>{{ trans('admin::seller-workspace.pages.'.$item) }}</a>
                    @endif
                @endforeach
                </div>
            </details>
        @endforeach
    </nav>
    @if (bouncer()->hasPermission('settings.workspace'))
        <a class="msw-menu-foot" href="{{ route('admin.seller_workspace.settings') }}">{{ trans('admin::seller-workspace.menu.settings') }}</a>
    @else
        <a class="msw-menu-foot" href="{{ route('admin.account.edit') }}">{{ trans('admin::seller-workspace.side.account') }}</a>
    @endif
</aside>
