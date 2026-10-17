<a
    class="mp-nav-help {{ request()->routeIs('admin.help.index') ? 'is-active' : '' }}"
    href="{{ route('admin.help.index') }}"
    aria-label="@lang('admin::app.components.layouts.sidebar.help')"
    title="@lang('admin::app.components.layouts.sidebar.help')"
    @if (request()->routeIs('admin.help.index')) aria-current="page" @endif
>
    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6" />
        <path d="M9.5 9a2.6 2.6 0 0 1 5 1c0 1.7-2.5 2-2.5 3.5M12 17h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
    </svg>
    <span class="mp-nav-label">@lang('admin::app.components.layouts.sidebar.help')</span>
</a>
