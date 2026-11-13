@include('manpleasure-admin::seller-workspace.heading')

<section class="msw-card">
    <div class="msw-profile-head">
        <span class="msw-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}</span>
        <div>
            <h2>{{ $admin->name }}</h2>
            <p>{{ $admin->role?->name }}</p>
            <p>{{ $admin->email }}</p>
        </div>
    </div>
    @foreach ($groups as $group => $links)
        <h2 class="msw-set-title">{{ trans('admin::seller-workspace.settings.'.$group) }}</h2>
        <div class="msw-grid-3" style="gap: 16px">
            @foreach ($links as $link)
                <a class="msw-set" href="{{ $link['url'] }}">
                    <span>@include('manpleasure-admin::seller-workspace.icon', ['name' => 'settings'])</span>
                    <div><b>{{ trans('admin::seller-workspace.settings.'.$link['label']) }}</b><small>{{ trans('admin::seller-workspace.settings.about.'.$link['label']) }}</small></div>
                </a>
            @endforeach
        </div>
    @endforeach
</section>
