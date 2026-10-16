@php
    $navigationMode = $navigationMode ?? 'desktop';
    $menuGroups = ['overview' => [], 'store' => [], 'growth' => [], 'system' => []];

    foreach (menu()->getItems('admin') as $menuItem) {
        $menuPath = parse_url($menuItem->getUrl(), PHP_URL_PATH) ?? '';
        $menuGroup = match (true) {
            (bool) preg_match('~/(dashboard)(/|$)~', $menuPath) => 'overview',
            (bool) preg_match('~/(settings|configuration|configure|appearance)(/|$)~', $menuPath) => 'system',
            (bool) preg_match('~/(cms|marketing|reporting)(/|$)~', $menuPath) => 'growth',
            default => 'store',
        };

        $menuGroups[$menuGroup][] = $menuItem;
    }
@endphp

<nav
    id="mp-admin-{{ $navigationMode }}-navigation"
    class="mp-admin-navigation mp-admin-navigation--{{ $navigationMode }}"
    data-mp-navigation="{{ $navigationMode }}"
    aria-label="@lang('admin::admin-navigation.navigation')"
>
    @foreach ($menuGroups as $groupKey => $groupItems)
        @if (count($groupItems))
            <section class="mp-nav-section">
                <h2 class="mp-nav-section__title">
                    @lang('admin::admin-navigation.'.$groupKey)
                </h2>

                <div class="mp-nav-section__items">
                    @foreach ($groupItems as $menuItem)
                        @php
                            $panelId = 'mp-nav-'.$navigationMode.'-'.$groupKey.'-'.$loop->index;
                            $isMenuActive = (bool) $menuItem->isActive();
                            $isInitiallyOpen = $isMenuActive && ($navigationMode === 'mobile' || request()->cookie('sidebar_collapsed') != 1);
                        @endphp

                        <div
                            class="mp-nav-group {{ $isMenuActive ? 'is-active' : '' }}"
                            data-mp-group
                            data-mp-open="{{ $isMenuActive ? '1' : '0' }}"
                        >
                            <div class="mp-nav-row">
                                <a
                                    class="mp-nav-link"
                                    href="{{ $menuItem->getUrl() }}"
                                    aria-label="{{ $menuItem->getName() }}"
                                    title="{{ $menuItem->getName() }}"
                                    @if (rtrim(url()->current(), '/') === rtrim($menuItem->getUrl(), '/')) aria-current="page" @endif
                                >
                                    <span class="mp-nav-icon {{ $menuItem->getIcon() }}" aria-hidden="true"></span>
                                    <span class="mp-nav-label">{{ $menuItem->getName() }}</span>
                                </a>

                                @if ($menuItem->haveChildren())
                                    <button
                                        type="button"
                                        class="mp-nav-disclosure"
                                        data-mp-disclosure
                                        aria-controls="{{ $panelId }}"
                                        aria-expanded="{{ $isInitiallyOpen ? 'true' : 'false' }}"
                                        aria-label="{{ trans('admin::admin-navigation.toggle-section', ['name' => $menuItem->getName()]) }}"
                                        title="{{ trans('admin::admin-navigation.toggle-section', ['name' => $menuItem->getName()]) }}"
                                    >
                                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                            <path d="m6 8 4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            @if ($menuItem->haveChildren())
                                <div
                                    id="{{ $panelId }}"
                                    class="mp-nav-submenu"
                                    @if (! $isInitiallyOpen) hidden @endif
                                >
                                    @foreach ($menuItem->getChildren() as $subMenuItem)
                                        <a
                                            class="mp-nav-child {{ $subMenuItem->isActive() ? 'is-active' : '' }}"
                                            href="{{ $subMenuItem->getUrl() }}"
                                            title="{{ $subMenuItem->getName() }}"
                                            @if (rtrim(url()->current(), '/') === rtrim($subMenuItem->getUrl(), '/')) aria-current="page" @endif
                                        >
                                            {{ $subMenuItem->getName() }}
                                        </a>

                                        @if ($subMenuItem->haveChildren())
                                            <div class="mp-nav-grandchildren">
                                                @foreach ($subMenuItem->getChildren() as $subSubMenuItem)
                                                    <a
                                                        class="mp-nav-child {{ $subSubMenuItem->isActive() ? 'is-active' : '' }}"
                                                        href="{{ $subSubMenuItem->getUrl() }}"
                                                        title="{{ $subSubMenuItem->getName() }}"
                                                        @if (rtrim(url()->current(), '/') === rtrim($subSubMenuItem->getUrl(), '/')) aria-current="page" @endif
                                                    >
                                                        {{ $subSubMenuItem->getName() }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @endforeach

    @if ($navigationMode === 'mobile')
        <div class="mp-nav-support">
            @include('manpleasure-admin::admin-navigation-help')
        </div>
    @endif
</nav>
