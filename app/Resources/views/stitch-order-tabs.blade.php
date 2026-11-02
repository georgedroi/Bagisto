<nav class="msp-status-tabs" aria-label="{{ trans('admin::executive-layout.status') }}">
    @foreach (['' => 'admin::executive-layout.all-orders', ...\App\Repositories\OrderQueueRepository::QUEUES, 'pending' => 'admin::executive-layout.new-orders', 'fraud' => 'admin::executive-layout.fraud'] as $queue => $label)
        <a href="{{ route('admin.sales.orders.index', [...request()->only(['workflow', 'search']), ...($queue ? ['queue' => $queue] : [])]) }}" class="{{ request('queue', '') === $queue ? 'is-active' : '' }}" data-action="filter-order-status" @if (request('queue', '') === $queue) aria-current="page" @endif>
            {{ trans($label) }}
        </a>
    @endforeach
    @if (bouncer()->hasPermission('sales.shipments'))
        <a href="{{ route('admin.sales.shipments.index') }}">{{ trans('admin::executive-layout.shipping') }}</a>
    @endif
    @if (bouncer()->hasPermission('sales.rma'))
        <a href="{{ route('admin.sales.rma.requests.index') }}">{{ trans('admin::executive-layout.returns') }}</a>
    @endif
</nav>
<form method="GET" action="{{ route('admin.sales.orders.index') }}" class="mb-4 flex flex-wrap items-center gap-2">
    @foreach (['queue', 'search'] as $filter)
        @if (is_string(request($filter)) && request($filter) !== '')
            <input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">
        @endif
    @endforeach
    <label for="order-workflow-filter" class="text-sm font-medium">{{ trans('admin::order-status.management') }}</label>
    <select id="order-workflow-filter" name="workflow" class="min-h-[44px] max-w-full rounded border border-gray-300 px-3 py-2 dark:bg-gray-900">
        <option value="">{{ trans('admin::executive-layout.all-orders') }}</option>
        @foreach (['awaiting_payment', 'awaiting_verification', 'payment_clarification', ...array_keys(\App\Support\OrderWorkflow::TRANSITIONS), 'delivered'] as $stage)
            <option value="{{ $stage }}" @selected(request('workflow') === $stage)>{{ trans('admin::order-status.'.$stage) }}</option>
        @endforeach
    </select>
    <button class="secondary-button">{{ trans('admin::order-status.filter') }}</button>
</form>
