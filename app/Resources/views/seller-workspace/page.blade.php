<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ core()->getCurrentLocale()->direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('admin::seller-workspace.pages.'.$page) }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('manpleasure-admin::seller-workspace.styles')
    <script>try { if (localStorage.getItem('msw-menu-collapsed') === '1') { document.documentElement.classList.add('msw-collapsed'); } } catch (error) {}</script>
</head>
<body>
    <div class="msw" v-pre data-msw-page="{{ $page }}" data-msw-loading="{{ trans('admin::seller-workspace.actions.loading') }}" data-msw-saved="{{ trans('admin::seller-workspace.actions.saved') }}" data-msw-failed="{{ trans('admin::seller-workspace.actions.failed') }}">
        <aside class="msw-menu" aria-label="{{ trans('admin::seller-workspace.title') }}">
            <button type="button" class="msw-menu-toggle" data-msw-menu-toggle data-msw-collapse="{{ trans('admin::seller-workspace.menu.collapse') }}" data-msw-expand="{{ trans('admin::seller-workspace.menu.expand') }}" aria-label="{{ trans('admin::seller-workspace.menu.collapse') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])</button>
            <a class="msw-logo" href="{{ route('admin.seller_workspace.overview') }}">
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
            <nav class="msw-menu-list">
                @php
                    $activeItem = $page === 'add-product' ? 'add' : $page;
                    $openGroup = collect($menuGroups)->except('common')->search(fn (array $items) => in_array($activeItem, $items, true)) ?: 'common';
                @endphp
                @foreach ($menuGroups as $group => $items)
                    <details @if ($group === $openGroup) open @endif data-msw-menu-group>
                        <summary>
                            @include('manpleasure-admin::seller-workspace.icon', ['name' => $group])
                            <span>{{ trans('admin::seller-workspace.menu.'.$group) }}</span>
                            @include('manpleasure-admin::seller-workspace.icon', ['name' => 'up'])
                        </summary>
                        <div class="msw-menu-links">
                        @foreach ($items as $item)
                            @if ($item === 'add')
                                <a href="{{ route('admin.seller_workspace.add_product') }}" @class(['is-active' => $page === 'add-product']) @if ($page === 'add-product') aria-current="page" @endif>{{ trans('admin::seller-workspace.products.add') }}</a>
                            @else
                                <a href="{{ route('admin.seller_workspace.'.$item) }}" @class(['is-active' => $item === $page]) @if ($item === $page) aria-current="page" @endif>{{ trans('admin::seller-workspace.pages.'.$item) }}</a>
                            @endif
                        @endforeach
                        </div>
                    </details>
                @endforeach
            </nav>
            <a class="msw-menu-foot" href="{{ route('admin.dashboard.index') }}">{{ trans('admin::seller-workspace.menu.admin') }}</a>
        </aside>

        <main @class(['msw-main', 'msw-main-hero' => $page === 'overview'])>
            @include('manpleasure-admin::seller-workspace.pages.'.$page)
        </main>

        <aside class="msw-rail" aria-label="{{ trans('admin::seller-workspace.menu.tools') }}">
            <a href="{{ route('admin.notification.index') }}" title="{{ trans('admin::seller-workspace.menu.notifications') }}" aria-label="{{ trans('admin::seller-workspace.menu.notifications') }}">
                @include('manpleasure-admin::seller-workspace.icon', ['name' => 'bell'])
            </a>
            <a href="{{ route('shop.home.index') }}" title="{{ trans('admin::seller-workspace.menu.storefront') }}" aria-label="{{ trans('admin::seller-workspace.menu.storefront') }}">
                @include('manpleasure-admin::seller-workspace.icon', ['name' => 'store'])
            </a>
            <a href="{{ route('admin.account.edit') }}" title="{{ trans('admin::seller-workspace.side.account') }}" aria-label="{{ trans('admin::seller-workspace.side.account') }}">
                @include('manpleasure-admin::seller-workspace.icon', ['name' => 'user'])
            </a>
            <form method="post" action="{{ route('admin.session.destroy') }}">
                @csrf
                @method('DELETE')
                <button type="submit" title="{{ trans('admin::seller-workspace.menu.logout') }}" aria-label="{{ trans('admin::seller-workspace.menu.logout') }}">
                    @include('manpleasure-admin::seller-workspace.icon', ['name' => 'logout'])
                </button>
            </form>
        </aside>
    </div>
    @include('manpleasure-admin::seller-workspace.script')
</body>
</html>
