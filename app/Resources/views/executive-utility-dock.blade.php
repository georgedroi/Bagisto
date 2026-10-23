<aside class="mp-utility-dock" aria-label="{{ trans('admin::executive-layout.utilities') }}">
    <button type="button" class="mp-dock-button" data-action="open-dock-drawer" aria-controls="mp-applications-panel" aria-expanded="false" aria-label="{{ trans('admin::executive-layout.applications') }}" title="{{ trans('admin::executive-layout.applications') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="6" height="6" rx="1" /><rect x="15" y="3" width="6" height="6" rx="1" /><rect x="3" y="15" width="6" height="6" rx="1" /><rect x="15" y="15" width="6" height="6" rx="1" /></svg>
    </button>
    <button type="button" class="mp-dock-button" data-action="toggle-compact-view" aria-pressed="false" aria-label="{{ trans('admin::executive-layout.compact') }}" title="{{ trans('admin::executive-layout.compact') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2" /><path d="M3 9h18M9 9v11M15 9v11" /></svg>
    </button>
    <button type="button" class="mp-dock-button" data-action="nav-search" aria-label="{{ trans('admin::app.components.layouts.header.mega-search.title') }}" title="{{ trans('admin::app.components.layouts.header.mega-search.title') }} (Ctrl+K)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="10" cy="10" r="6" /><path d="m15 15 6 6" /></svg>
    </button>
    <a class="mp-dock-button mp-dock-home" href="{{ route('admin.help.index') }}" title="{{ trans('admin::app.components.layouts.sidebar.help') }}" aria-label="{{ trans('admin::app.components.layouts.sidebar.help') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M9.5 9a2.6 2.6 0 0 1 5 1c0 1.7-2.5 2-2.5 3.5M12 17h.01" /></svg>
    </a>
</aside>

<section id="mp-applications-panel" class="mp-utility-panel" aria-labelledby="mp-applications-title" hidden>
    <h2 id="mp-applications-title">{{ trans('admin::executive-layout.applications') }}</h2>
    @foreach (menu()->getItems('admin') as $menuItem)
        <a href="{{ $menuItem->getUrl() }}" data-action="navigate-module">
            <span class="{{ $menuItem->getIcon() }}" aria-hidden="true"></span>
            {{ $menuItem->getName() }}
        </a>
    @endforeach
</section>
