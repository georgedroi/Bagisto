<nav class="msp-status-tabs" aria-label="{{ trans('admin::executive-layout.status') }}">
    @foreach (['' => 'all-orders', 'pending' => 'new-orders', 'pending_payment' => 'unpaid', 'processing' => 'processing', 'completed' => 'completed', 'canceled' => 'canceled', 'closed' => 'closed', 'fraud' => 'fraud'] as $queue => $label)
        <a href="{{ route('admin.sales.orders.index', $queue ? ['queue' => $queue] : []) }}" class="{{ request('queue', '') === $queue ? 'is-active' : '' }}" data-action="filter-order-status" @if (request('queue', '') === $queue) aria-current="page" @endif>
            {{ trans('admin::executive-layout.'.$label) }}
        </a>
    @endforeach
    @if (bouncer()->hasPermission('sales.shipments'))
        <a href="{{ route('admin.sales.shipments.index') }}">{{ trans('admin::executive-layout.shipping') }}</a>
    @endif
    @if (bouncer()->hasPermission('sales.rma'))
        <a href="{{ route('admin.sales.rma.requests.index') }}">{{ trans('admin::executive-layout.returns') }}</a>
    @endif
</nav>
