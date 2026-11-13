<h1 class="msw-hero-title">{{ trans('admin::seller-workspace.titles.overview') }}</h1>
<div class="msw-top">
    @if ($orderRoute || $stock !== null)
    <section class="msw-card">
        @if ($orderRoute)
        <h2 class="msw-title">{{ trans('admin::seller-workspace.overview.tasks') }}</h2>
        <div class="msw-tasks">
            @foreach ([['awaiting_payment', 'finance'], ['to_ship', 'box'], ['shipping', 'check'], ['failed_delivery', 'alert'], ['returns', 'return']] as [$queue, $icon])
                <a class="msw-task" href="{{ route($orderRoute, ['queue' => $queue]) }}">
                    <b data-field="order.count.{{ $queue }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => $icon]){{ number_format($queues[$queue]) }}</b>
                    <small>{{ trans(\App\Repositories\OrderQueueRepository::QUEUES[$queue]) }}</small>
                    @include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])
                </a>
            @endforeach
        </div>
        @endif
        <div class="msw-task-groups">
            @if ($orderRoute)
            @foreach (\App\Repositories\OrderQueueRepository::WORKFLOW_TASKS as $group => $groupStages)
                <div>
                    <h3>{{ trans('admin::seller-workspace.overview.'.\Illuminate\Support\Str::after($group, 'task-')) }}</h3>
                    @foreach ($groupStages as $stage)
                        <a class="msw-task-line" href="{{ route($orderRoute, ['workflow' => $stage]) }}">
                            <span>{{ trans('admin::order-status.'.$stage) }}</span>
                            <b data-field="order.count.{{ $stage }}">{{ number_format($stages[$stage]) }}@include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])</b>
                        </a>
                    @endforeach
                </div>
            @endforeach
            @endif
            @if ($stock !== null)
                <div>
                    <h3>{{ trans('admin::seller-workspace.menu.products') }}</h3>
                    @foreach (\App\Repositories\ProductStockRepository::FILTERS as $filter => $label)
                        <a class="msw-task-line" href="{{ route($productRoute, $filter === 'inactive' && $productRoute === 'admin.seller_workspace.products' ? ['tab' => 'inactive'] : ['stock' => $filter]) }}">
                            <span>{{ trans($label) }}</span>
                            <b data-field="product.count.{{ $filter }}">{{ number_format($stock[$filter]) }}@include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])</b>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
    @endif

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
                @if (bouncer()->hasPermission('sales.invoices.workspace'))
                    <a href="{{ route('admin.seller_workspace.income') }}" aria-label="{{ trans('admin::seller-workspace.pages.income') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'wallet'])</a>
                @endif
                <a href="{{ route('admin.account.edit') }}" aria-label="{{ trans('admin::seller-workspace.side.account') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'user'])</a>
            </div>
        </section>
        @if ($orderRoute)
        <section class="msw-card">
            <h2 class="msw-title">{{ trans('admin::seller-workspace.side.attention') }}</h2>
            @foreach (['awaiting_payment', 'to_ship', 'failed_delivery', 'returns'] as $queue)
                <a class="msw-task-line" href="{{ route($orderRoute, ['queue' => $queue]) }}">
                    <span>{{ trans(\App\Repositories\OrderQueueRepository::QUEUES[$queue]) }}</span>
                    <b>{{ number_format($queues[$queue]) }}</b>
                </a>
            @endforeach
        </section>
        @endif
    </div>
</div>

@if ($quickLinks->isNotEmpty())
    <section class="msw-card">
        <h2 class="msw-title">{{ trans('admin::executive-layout.quick-view') }}</h2>
        <div class="msw-grid-3">
            @foreach ($quickLinks as [$permission, $linkRoute, $label])
                <a class="msw-button" href="{{ route($linkRoute) }}">{{ trans($label) }} @include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])</a>
            @endforeach
        </div>
    </section>
@endif

@if ($business)
    @php
        $rangeTiles = [
            ['range-sales', $business['range']['total_sales']['current'], true, $business['range']['total_sales']['progress']],
            ['range-orders', $business['range']['total_orders']['current'], false, $business['range']['total_orders']['progress']],
            ['range-customers', $business['range']['total_customers']['current'], false, $business['range']['total_customers']['progress']],
            ['range-average', $business['range']['avg_sales']['current'], true, $business['range']['avg_sales']['progress']],
        ];
        $todayTiles = [
            ['revenue', $business['today']['sales']['current'], true, $business['today']['sales']['progress']],
            ['orders', $business['today']['orders']['current'], false, $business['today']['orders']['progress']],
            ['customers-today', $business['today']['customers']['current'], false, $business['today']['customers']['progress']],
            ['unpaid', $business['range']['total_unpaid_invoices']['total'], true, null],
        ];
        $labelEvery = max(1, (int) ceil(count($business['overTime']) / 8));
    @endphp
    <section class="msw-card">
        <div class="msw-card-head">
            <h2 class="msw-title">{{ trans('admin::seller-workspace.overview.business') }}</h2>
            <form class="msw-filter" method="get" action="{{ $overviewUrl }}">
                @if ($business['channels']->count() > 1)
                    <label class="msw-field msw-field-sm">
                        <span>{{ trans('admin::seller-workspace.overview.channel') }}</span>
                        <select name="channel">
                            <option value="">{{ trans('admin::seller-workspace.overview.all-channels') }}</option>
                            @foreach ($business['channels'] as $option)
                                <option value="{{ $option->code }}" @selected($business['channel'] === $option->code)>{{ $option->name }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <label class="msw-field msw-field-sm"><span>{{ trans('admin::seller-workspace.fees.from') }}</span><input type="date" name="start" value="{{ $business['start']->format('Y-m-d') }}"></label>
                <label class="msw-field msw-field-sm"><span>{{ trans('admin::seller-workspace.fees.to') }}</span><input type="date" name="end" value="{{ $business['end']->format('Y-m-d') }}"></label>
                <button class="msw-button msw-primary" type="submit">{{ trans('admin::seller-workspace.search') }}</button>
            </form>
        </div>

        <h3 class="msw-set-title">{{ trans('admin::seller-workspace.overview.range', ['from' => $business['start']->translatedFormat('d M Y'), 'to' => $business['end']->translatedFormat('d M Y')]) }}</h3>
        <div class="msw-grid-4" style="margin-top: 12px">
            @foreach ($rangeTiles as [$label, $value, $money, $progress])
                <div class="msw-tile" data-field="business.{{ $label }}">
                    <span>{{ trans('admin::seller-workspace.overview.'.$label) }}</span>
                    <strong>{{ $money ? core()->formatBasePrice($value) : number_format($value) }}</strong>
                    <small @class(['msw-change', 'is-up' => $progress > 0, 'is-down' => $progress < 0])>{{ trans('admin::seller-workspace.overview.vs-previous', ['value' => ($progress > 0 ? '+' : '').number_format($progress, 1).'%']) }}</small>
                </div>
            @endforeach
        </div>

        <h3 class="msw-set-title">{{ trans('admin::seller-workspace.overview.today') }}</h3>
        <div class="msw-grid-4" style="margin-top: 12px">
            @foreach ($todayTiles as [$label, $value, $money, $progress])
                <div class="msw-tile" data-field="business.{{ $label }}">
                    <span>{{ trans('admin::seller-workspace.overview.'.$label) }}</span>
                    <strong>{{ $money ? core()->formatBasePrice($value) : number_format($value) }}</strong>
                    @if ($progress === null)
                        <small class="msw-change">{{ trans('admin::seller-workspace.overview.all-time') }}</small>
                    @else
                        <small @class(['msw-change', 'is-up' => $progress > 0, 'is-down' => $progress < 0])>{{ trans('admin::seller-workspace.overview.vs-yesterday', ['value' => ($progress > 0 ? '+' : '').number_format($progress, 1).'%']) }}</small>
                    @endif
                </div>
            @endforeach
        </div>

        <h3 class="msw-set-title">{{ trans('admin::seller-workspace.overview.sales-over-time') }}</h3>
        <div class="msw-bars" role="img" aria-label="{{ trans('admin::seller-workspace.overview.sales-over-time') }}: {{ core()->formatBasePrice($business['range']['total_sales']['current']) }}">
            @foreach ($business['overTime'] as $point)
                <div class="msw-bar" title="{{ $point['label'] }}: {{ core()->formatBasePrice($point['total']) }} · {{ trans('admin::seller-workspace.overview.order-count', ['count' => $point['count']]) }}">
                    <i style="height: {{ $business['peak'] > 0 ? max(2, (int) round($point['total'] / $business['peak'] * 100)) : 2 }}%"></i>
                    <small>{{ ($loop->index % $labelEvery === 0) ? $point['label'] : '' }}</small>
                </div>
            @endforeach
        </div>
    </section>

    <div class="msw-grid-2">
        <section class="msw-card">
            <h2 class="msw-title">{{ trans('admin::seller-workspace.overview.top-products') }}</h2>
            <div class="msw-rank">
                @forelse ($business['products'] as $item)
                    <div class="msw-rank-row">
                        <div class="msw-product">
                            @if ($image = $item['images']?->first()?->url)
                                <img src="{{ $image }}" alt="">
                            @else
                                <i aria-hidden="true"></i>
                            @endif
                            <div><b>{{ $item['name'] }}</b><small>{{ $item['formatted_price'] }}</small></div>
                        </div>
                        <strong>{{ $item['formatted_revenue'] }}</strong>
                    </div>
                @empty
                    <p class="msw-empty">{{ trans('admin::seller-workspace.empty') }}</p>
                @endforelse
            </div>
        </section>

        <section class="msw-card">
            <h2 class="msw-title">{{ trans('admin::seller-workspace.overview.top-customers') }}</h2>
            <div class="msw-rank">
                @forelse ($business['customers'] as $buyer)
                    <div class="msw-rank-row">
                        <div>
                            @if ($buyer->id && bouncer()->hasPermission('customers.customers'))
                                <a class="msw-blue" href="{{ route('admin.customers.customers.view', $buyer->id) }}"><b>{{ $buyer->full_name }}</b></a>
                            @else
                                <b>{{ $buyer->full_name }}</b>
                            @endif
                            <small>{{ $buyer->email }} · {{ trans('admin::seller-workspace.overview.order-count', ['count' => $buyer->orders]) }}</small>
                        </div>
                        <strong>{{ $buyer->formatted_total }}</strong>
                    </div>
                @empty
                    <p class="msw-empty">{{ trans('admin::seller-workspace.empty') }}</p>
                @endforelse
            </div>
        </section>
    </div>
@endif

@if ($lowest !== null)
    <section class="msw-card">
        <div class="msw-card-head">
            <h2 class="msw-title">{{ trans('admin::seller-workspace.overview.lowest-stock') }}</h2>
            <a class="msw-blue" href="{{ route($productRoute, ['stock' => 'low']) }}">{{ trans('admin::seller-workspace.overview.all-low') }}</a>
        </div>
        <div class="msw-rank">
            @forelse ($lowest as $product)
                <div class="msw-rank-row">
                    <div class="msw-product">
                        @if ($product->base_image)
                            <img src="{{ Storage::url($product->base_image) }}" alt="">
                        @else
                            <i aria-hidden="true"></i>
                        @endif
                        <div><b>{{ $product->name }}</b><small>{{ trans('admin::seller-workspace.columns.sku') }}: {{ $product->sku }}</small></div>
                    </div>
                    <strong @class(['is-down' => (int) $product->quantity <= 0])>{{ number_format((int) $product->quantity) }}</strong>
                </div>
            @empty
                <p class="msw-empty">{{ trans('admin::seller-workspace.overview.stock-fine') }}</p>
            @endforelse
        </div>
    </section>
@endif

@if ($recent !== null)
<section class="msw-card">
    <div class="msw-card-head">
        <h2 class="msw-title">{{ trans('admin::seller-workspace.overview.recent') }}</h2>
        <a class="msw-blue" href="{{ route($orderRoute) }}">{{ trans('admin::seller-workspace.overview.all-orders') }}</a>
    </div>
</section>
@include('manpleasure-admin::seller-workspace.order-table', ['rows' => $recent])
@endif
