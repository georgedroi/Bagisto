<aside
    class="mp-admin-sidebar"
    aria-label="@lang('admin::admin-navigation.navigation')"
>
    <button
        type="button"
        class="mp-sidebar-toggle mp-sidebar-edge-toggle"
        data-mp-sidebar-toggle
        data-expand-label="@lang('admin::admin-navigation.expand-sidebar')"
        data-collapse-label="@lang('admin::admin-navigation.collapse-sidebar')"
        aria-expanded="{{ request()->cookie('sidebar_collapsed') == 1 ? 'false' : 'true' }}"
        aria-controls="mp-admin-desktop-navigation"
        aria-label="@lang(request()->cookie('sidebar_collapsed') == 1 ? 'admin::admin-navigation.expand-sidebar' : 'admin::admin-navigation.collapse-sidebar')"
        title="@lang(request()->cookie('sidebar_collapsed') == 1 ? 'admin::admin-navigation.expand-sidebar' : 'admin::admin-navigation.collapse-sidebar')"
    >
        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
            <path d="m12 5-5 5 5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <span data-mp-collapse-label>
            @lang('admin::admin-navigation.collapse-sidebar')
        </span>
    </button>

    <div class="mp-admin-sidebar__scroll">
        @include('manpleasure-admin::admin-navigation', ['navigationMode' => 'desktop'])
    </div>

    <div class="mp-admin-sidebar__footer">
        @include('manpleasure-admin::admin-navigation-help')

    </div>
</aside>

@pushOnce('scripts')
    @include('manpleasure-admin::admin-navigation-script')
@endPushOnce
