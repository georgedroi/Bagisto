<aside class="mp-admin-sidebar" data-entity="navigation">
    <button
        type="button"
        class="mp-sidebar-toggle mp-sidebar-edge-toggle"
        data-mp-sidebar-toggle
        data-action="toggle-navigation"
        data-expand-label="{{ trans('admin::admin-navigation.expand-sidebar') }}"
        data-collapse-label="{{ trans('admin::admin-navigation.collapse-sidebar') }}"
        aria-expanded="true"
        aria-label="{{ trans('admin::admin-navigation.collapse-sidebar') }}"
        aria-controls="mp-admin-desktop-navigation"
    >
        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m12 5-5 5 5 5" stroke="currentColor" stroke-width="1.5" /></svg>
        <span data-mp-collapse-label>{{ trans('admin::admin-navigation.collapse-sidebar') }}</span>
    </button>

    <div class="mp-admin-sidebar__scroll">
        <button type="button" class="msp-sidebar-search" data-action="nav-search" title="{{ trans('admin::app.components.layouts.header.mega-search.title') }} (Ctrl+K)">
            <span class="icon-search" aria-hidden="true"></span>
            <span class="mp-nav-label">{{ trans('admin::app.components.layouts.header.mega-search.title') }}</span>
            <kbd aria-hidden="true">⌘K</kbd>
        </button>
        @include('manpleasure-admin::admin-navigation', ['navigationMode' => 'desktop'])
    </div>

    <div class="mp-admin-sidebar__footer">
        @include('manpleasure-admin::admin-navigation-help')
    </div>
</aside>
