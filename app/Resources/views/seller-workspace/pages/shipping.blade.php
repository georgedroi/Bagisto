<h1 style="margin-top: 0; color: rgba(46, 51, 70, .88)">{{ trans('admin::seller-workspace.titles.shipping') }}</h1>

<section class="msw-card">
    <div class="msw-card-head" style="min-height: 76px; align-items: flex-end">
        <h2 style="align-self: flex-start; margin-top: 32px; font-size: 16px; font-weight: 600; line-height: 16px; color: #2e3346">{{ trans('admin::seller-workspace.shipping.backlog') }}</h2>
        @if (bouncer()->hasPermission('sales.shipments'))
            <a class="msw-button" style="border-color: #d9d9d9; color: rgba(46, 51, 70, .88)" href="{{ route('admin.sales.shipments.index') }}">{{ trans('admin::seller-workspace.shipping.shipments') }}</a>
        @endif
    </div>
    <div class="msw-grid-4" style="gap: 12px; margin-top: 12px">
        @foreach (['packing', 'ready_to_ship', 'out_for_delivery'] as $stage)
            <a @class(['msw-tile', 'msw-outline', 'is-active' => $loop->first]) href="{{ route('admin.sales.orders.index', ['workflow' => $stage]) }}"><span>{{ trans('admin::order-status.'.$stage) }}</span><strong>{{ number_format($stages[$stage]) }}</strong></a>
        @endforeach
        <a class="msw-tile msw-outline" href="{{ route('admin.seller_workspace.orders', ['queue' => 'failed_delivery']) }}"><span>{{ trans(\App\Repositories\OrderQueueRepository::QUEUES['failed_delivery']) }}</span><strong>{{ number_format($queues['failed_delivery']) }}</strong></a>
    </div>

    <div class="msw-table msw-table-soft"><table>
        <thead><tr>
            @foreach (['shipment', 'order', 'carrier', 'tracking', 'quantity', 'date'] as $column)
                <th>{{ trans('admin::seller-workspace.columns.'.$column) }}</th>
            @endforeach
        </tr></thead>
        <tbody>
            @forelse ($rows as $shipment)
                <tr>
                    <td>
                        @if (bouncer()->hasPermission('sales.shipments.view'))
                            <a href="{{ route('admin.sales.shipments.view', $shipment->id) }}">#{{ $shipment->id }}</a>
                        @else
                            #{{ $shipment->id }}
                        @endif
                    </td>
                    <td>#{{ $shipment->order?->increment_id }}</td>
                    <td>{{ $shipment->carrier_title ?: '—' }}</td>
                    <td>{{ $shipment->track_number ?: '—' }}</td>
                    <td>{{ number_format((int) $shipment->total_qty) }}</td>
                    <td>{{ $shipment->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><p class="msw-empty">{{ trans('admin::seller-workspace.empty') }}</p></td></tr>
            @endforelse
        </tbody>
    </table></div>
</section>
@include('manpleasure-admin::seller-workspace.pager')
