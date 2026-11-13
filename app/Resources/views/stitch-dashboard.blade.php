@if (bouncer()->hasPermission('sales.orders'))
    @php
        $orderQueues = [
            'awaiting_payment',
            'to_ship',
            'shipping',
            'failed_delivery',
            'returns',
        ];
        $queueCounts = app(\App\Repositories\OrderQueueRepository::class)->queueCounts();
        $workflowCounts = app(\App\Repositories\OrderQueueRepository::class)->workflowCounts();
        $stockCounts = bouncer()->hasPermission('catalog.products') ? app(\App\Repositories\ProductStockRepository::class)->stockCounts() : [];

        $recentOrders = app(\Webkul\Sales\Repositories\OrderRepository::class)->with(['payment', 'shipments'])->orderBy('created_at', 'desc')->paginate(6);
    @endphp

    <section class="msp-todo-panel">
        <div class="msp-section-title"><h2>{{ trans('admin::executive-layout.todo') }}</h2><span>{{ trans('admin::executive-layout.all-time') }}</span></div>
        <div class="msp-kpi-grid">
            @foreach ($orderQueues as $queue)
                <a class="msp-kpi {{ in_array($queue, ['failed_delivery', 'returns'], true) ? 'msp-kpi--urgent' : '' }}" href="{{ route('admin.sales.orders.index', ['queue' => $queue]) }}" data-entity="order" data-action="filter-order-status">
                    <span class="msp-kpi-icon" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <strong data-field="order.count.{{ $queue }}">{{ number_format($queueCounts[$queue]) }}</strong>
                    <h3>{{ trans(\App\Repositories\OrderQueueRepository::QUEUES[$queue]) }}</h3>
                    <span class="msp-kpi-footer">{{ trans('admin::executive-layout.review') }} <span aria-hidden="true">→</span></span>
                </a>
            @endforeach
        </div>
        <div class="msp-task-groups">
            @foreach (\App\Repositories\OrderQueueRepository::WORKFLOW_TASKS as $group => $stages)
                <div class="msp-task-group">
                    <h3>{{ trans('admin::executive-layout.'.$group) }}</h3>
                    @foreach ($stages as $stage)
                        <a href="{{ route('admin.sales.orders.index', ['workflow' => $stage]) }}" data-entity="order" data-action="filter-order-workflow">
                            <span>{{ trans('admin::order-status.'.$stage) }}</span>
                            <strong class="{{ $workflowCounts[$stage] ? 'has-tasks' : '' }}" data-field="order.count.{{ $stage }}">{{ number_format($workflowCounts[$stage]) }}</strong>
                        </a>
                    @endforeach
                </div>
            @endforeach
            @if (bouncer()->hasPermission('catalog.products'))
                <div class="msp-task-group">
                    <h3>{{ trans('admin::executive-layout.task-products') }}</h3>
                    @foreach (\App\Repositories\ProductStockRepository::FILTERS as $filter => $label)
                        <a href="{{ route('admin.catalog.products.index', ['stock' => $filter]) }}" data-entity="product" data-action="filter-product-stock">
                            <span>{{ trans($label) }}</span>
                            <strong class="{{ $stockCounts[$filter] ? 'has-tasks' : '' }}" data-field="product.count.{{ $filter }}">{{ number_format($stockCounts[$filter]) }}</strong>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="msp-panel">
        <div class="msp-section-title"><h2>{{ trans('admin::executive-layout.quick-view') }}</h2></div>
        <div class="msp-quick-grid">
            @if (bouncer()->hasPermission('sales.workspace'))
                <a href="{{ route('admin.seller_workspace.overview') }}">{{ trans('admin::seller-workspace.title') }} <span aria-hidden="true">→</span></a>
            @endif
            @if (bouncer()->hasPermission('catalog.product_entry'))
                <a href="{{ route('admin.seller_workspace.add_product') }}">{{ trans('admin::product-entry.menu') }} <span aria-hidden="true">→</span></a>
            @endif
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
                    @php
                        $recentStatus = $queueOrder->payment?->method === 'duitnowqr_manual'
                            ? \App\Support\OrderWorkflow::current($queueOrder)
                            : match ($queueOrder->status) {
                                'pending' => 'to-confirm',
                                'pending_payment' => 'pending-payment',
                                'processing' => $queueOrder->shipments->isEmpty() ? 'to-ship' : 'shipping',
                                'completed' => 'shipping',
                                'canceled' => 'cancelled',
                                'closed' => 'to-return-refund',
                                default => 'on-hold',
                            };
                    @endphp
                    <tr data-entity="order" data-id="{{ $queueOrder->increment_id }}">
                        <td><strong class="msp-mono">#{{ $queueOrder->increment_id }}</strong><small>{{ $queueOrder->created_at }}</small></td>
                        <td>{{ $queueOrder->customer_first_name }} {{ $queueOrder->customer_last_name }}</td>
                        <td><span class="msp-stock-pill">{{ trans('admin::order-status.'.$recentStatus) }}</span></td>
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
