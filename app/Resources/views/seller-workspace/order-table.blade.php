<div class="msw-order-head">
    <span>{{ trans('admin::seller-workspace.columns.order') }}</span>
    <span>{{ trans('admin::seller-workspace.columns.total') }}</span>
    <span>{{ trans('admin::seller-workspace.columns.carrier') }}</span>
    <span>{{ trans('admin::seller-workspace.columns.status') }}</span>
    <span>{{ trans('admin::seller-workspace.columns.action') }}</span>
</div>
@forelse ($rows as $order)
    <div class="msw-order-row" data-entity="order" data-id="{{ $order->increment_id }}">
        <div><strong>#{{ $order->increment_id }}</strong><small>{{ $order->customer_first_name }} {{ $order->customer_last_name }}</small><small>{{ $order->created_at }}</small></div>
        <div>{{ core()->formatBasePrice($order->base_grand_total) }}<small>{{ trans('admin::executive-layout.items', ['count' => $order->total_qty_ordered]) }}</small></div>
        <div>{{ $order->shipping_title ?: '—' }}</div>
        <div><span class="msw-pill">{{ trans('admin::order-status.'.$order->workspace_status) }}</span></div>
        <div>
            @if (bouncer()->hasPermission('sales.orders.view'))
                <a class="msw-blue" href="{{ route('admin.sales.orders.view', $order->id) }}">{{ trans('admin::seller-workspace.open') }}</a>
            @endif
            @if ($order->payment?->method === 'duitnowqr_manual' && bouncer()->hasPermission('sales.orders.workflow'))
                @foreach (array_diff(\App\Support\OrderWorkflow::next($order), ['failed_to_deliver']) as $step)
                    <form class="msw-inline" method="post" action="{{ route('admin.sales.orders.workflow.update', $order->id) }}" data-msw-json data-msw-confirm="{{ trans('admin::seller-workspace.actions.confirm-step') }}">
                        @csrf
                        <input type="hidden" name="workflow_status" value="{{ $step }}">
                        <input type="hidden" name="expected_status" value="{{ $order->workspace_status }}">
                        <button class="msw-button" type="submit">{{ trans('admin::order-status.'.$step) }}</button>
                        <small data-msw-message role="status"></small>
                    </form>
                @endforeach
            @endif
        </div>
    </div>
@empty
    <p class="msw-empty">{{ trans('admin::seller-workspace.empty') }}</p>
@endforelse
