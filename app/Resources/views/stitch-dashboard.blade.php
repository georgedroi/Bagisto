@if (bouncer()->hasPermission('sales.orders'))
    @php
        $orderQueues = [
            'pending_payment' => 'unpaid',
            'pending' => 'new-orders',
            'processing' => 'processing',
            'canceled' => 'canceled',
            'closed' => 'closed',
        ];
        $queueCounts = [];

        foreach ($orderQueues as $status => $label) {
            $queueCounts[$status] = app(\Webkul\Sales\Repositories\OrderRepository::class)->count(['status' => $status]);
        }

        $recentOrders = app(\Webkul\Sales\Repositories\OrderRepository::class)->orderBy('created_at', 'desc')->paginate(6);
    @endphp

    <section class="msp-todo-panel">
        <div class="msp-section-title"><h2>{{ trans('admin::executive-layout.todo') }}</h2><span>{{ trans('admin::executive-layout.all-time') }}</span></div>
        <div class="msp-kpi-grid">
            @foreach ($orderQueues as $status => $label)
                <a class="msp-kpi {{ $status === 'canceled' ? 'msp-kpi--urgent' : '' }}" href="{{ route('admin.sales.orders.index', ['queue' => $status]) }}" data-entity="order" data-action="filter-order-status">
                    <span class="msp-kpi-icon" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <strong data-field="order.count.{{ $status }}">{{ number_format($queueCounts[$status]) }}</strong>
                    <h3>{{ trans('admin::executive-layout.'.$label) }}</h3>
                    <span class="msp-kpi-footer">{{ trans('admin::executive-layout.review') }} <span aria-hidden="true">→</span></span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="msp-panel">
        <div class="msp-section-title"><h2>{{ trans('admin::executive-layout.quick-view') }}</h2></div>
        <div class="msp-quick-grid">
            @foreach ([['sales.shipments', 'admin.sales.shipments.index', 'shipping'], ['sales.refunds', 'admin.sales.refunds.index', 'refunds'], ['sales.rma', 'admin.sales.rma.requests.index', 'returns'], ['catalog.products', 'admin.catalog.products.index', 'catalog']] as [$permission, $route, $label])
                @if (bouncer()->hasPermission($permission))
                    <a href="{{ route($route) }}">{{ trans('admin::executive-layout.'.$label) }} <span aria-hidden="true">→</span></a>
                @endif
            @endforeach
        </div>
    </section>

    <section class="msp-panel msp-recent-orders">
        <div class="msp-section-title"><h2>{{ trans('admin::executive-layout.queue') }}</h2><a href="{{ route('admin.sales.orders.index') }}">{{ trans('admin::executive-layout.all-orders') }} →</a></div>
        <div class="msp-table-scroll"><table>
            <thead><tr>
                @foreach (['order-number', 'customer', 'status', 'amount-items', 'actions'] as $label)
                    <th>{{ trans('admin::executive-layout.'.$label) }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @forelse ($recentOrders as $queueOrder)
                    <tr data-entity="order" data-id="{{ $queueOrder->increment_id }}">
                        <td><strong class="msp-mono">#{{ $queueOrder->increment_id }}</strong><small>{{ $queueOrder->created_at }}</small></td>
                        <td>{{ $queueOrder->customer_first_name }} {{ $queueOrder->customer_last_name }}</td>
                        <td><span class="msp-stock-pill">{{ $queueOrder->status_label }}</span></td>
                        <td class="msp-money">{{ core()->formatBasePrice($queueOrder->base_grand_total) }}<small>{{ trans('admin::executive-layout.items', ['count' => $queueOrder->total_qty_ordered]) }}</small></td>
                        <td>
                            @if (bouncer()->hasPermission('sales.orders.view'))
                                <a href="{{ route('admin.sales.orders.view', $queueOrder->id) }}" class="msp-review-button" data-action="review-order">{{ trans('admin::executive-layout.review') }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="msp-empty">{{ trans('admin::executive-layout.no-records') }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
@endif
