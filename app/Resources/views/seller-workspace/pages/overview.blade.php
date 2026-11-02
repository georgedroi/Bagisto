<h1 class="msw-hero-title">{{ trans('admin::seller-workspace.titles.overview') }}</h1>
<div class="msw-top">
    <section class="msw-card">
        <h2 class="msw-title">{{ trans('admin::seller-workspace.overview.tasks') }}</h2>
        <div class="msw-tasks">
            @foreach ([['awaiting_payment', 'finance'], ['to_ship', 'box'], ['shipping', 'check'], ['failed_delivery', 'alert'], ['returns', 'return']] as [$queue, $icon])
                <a class="msw-task" href="{{ route('admin.seller_workspace.orders', ['queue' => $queue]) }}">
                    <b>@include('manpleasure-admin::seller-workspace.icon', ['name' => $icon]){{ number_format($queues[$queue]) }}</b>
                    <small>{{ trans(\App\Repositories\OrderQueueRepository::QUEUES[$queue]) }}</small>
                    @include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])
                </a>
            @endforeach
        </div>
        <div class="msw-task-groups">
            @foreach (array_chunk($stages, 4, true) as $index => $group)
                <div>
                    <h3>{{ trans('admin::seller-workspace.overview.'.($index ? 'fulfilment' : 'payments')) }}</h3>
                    @foreach ($group as $stage => $count)
                        <a class="msw-task-line" href="{{ route('admin.sales.orders.index', ['workflow' => $stage]) }}">
                            <span>{{ trans('admin::order-status.'.$stage) }}</span>
                            <b>{{ number_format($count) }}@include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])</b>
                        </a>
                    @endforeach
                </div>
            @endforeach
            @if ($stock !== null)
                <div>
                    <h3>{{ trans('admin::seller-workspace.menu.products') }}</h3>
                    @foreach (\App\Repositories\ProductStockRepository::FILTERS as $filter => $label)
                        <a class="msw-task-line" href="{{ route('admin.catalog.products.index', ['stock' => $filter]) }}">
                            <span>{{ trans($label) }}</span>
                            <b>{{ number_format($stock[$filter]) }}@include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])</b>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <div>
        <section class="msw-card msw-profile">
            <div class="msw-profile-top">
                <span class="msw-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}</span>
                <div>
                    <strong>{{ $admin->name }}</strong>
                    <span class="msw-tag">{{ $admin->role?->name }}</span>
                </div>
            </div>
            <div class="msw-profile-tools">
                <a href="{{ route('admin.account.edit') }}">{{ trans('admin::seller-workspace.side.account') }}</a>
                <a href="{{ route('shop.home.index') }}" aria-label="{{ trans('admin::seller-workspace.menu.storefront') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'store'])</a>
                <a href="{{ route('admin.seller_workspace.income') }}" aria-label="{{ trans('admin::seller-workspace.pages.income') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'wallet'])</a>
                <a href="{{ route('admin.account.edit') }}" aria-label="{{ trans('admin::seller-workspace.side.account') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'user'])</a>
            </div>
        </section>
        <section class="msw-card">
            <h2 class="msw-title">{{ trans('admin::seller-workspace.side.attention') }}</h2>
            @foreach (['awaiting_payment', 'to_ship', 'failed_delivery', 'returns'] as $queue)
                <a class="msw-task-line" href="{{ route('admin.seller_workspace.orders', ['queue' => $queue]) }}">
                    <span>{{ trans(\App\Repositories\OrderQueueRepository::QUEUES[$queue]) }}</span>
                    <b>{{ number_format($queues[$queue]) }}</b>
                </a>
            @endforeach
        </section>
    </div>
</div>

<section class="msw-card">
    <div class="msw-card-head">
        <h2 class="msw-title">{{ trans('admin::seller-workspace.overview.business') }}</h2>
    </div>
    <div class="msw-grid-4">
        <div class="msw-tile"><span>{{ trans('admin::seller-workspace.overview.revenue') }}</span><strong>{{ core()->formatBasePrice($sales['current']) }}</strong></div>
        <div class="msw-tile"><span>{{ trans('admin::seller-workspace.overview.orders') }}</span><strong>{{ number_format($ordersToday['current']) }}</strong></div>
    </div>
</section>

<section class="msw-card">
    <div class="msw-card-head">
        <h2 class="msw-title">{{ trans('admin::seller-workspace.overview.recent') }}</h2>
        <a class="msw-blue" href="{{ route('admin.seller_workspace.orders') }}">{{ trans('admin::seller-workspace.overview.all-orders') }}</a>
    </div>
</section>
@include('manpleasure-admin::seller-workspace.order-table', ['rows' => $recent])
