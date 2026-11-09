@include('manpleasure-admin::seller-workspace.heading')

<section class="msw-card">
    <div class="msw-grid-4" style="margin-top: 0">
        <div class="msw-tile"><span>{{ trans('admin::seller-workspace.income.refunded') }}</span><strong>{{ core()->formatBasePrice($refunded) }}</strong></div>
        <div class="msw-tile"><span>{{ trans('admin::seller-workspace.refunds.count') }}</span><strong>{{ number_format($count) }}</strong></div>
    </div>

    <div class="msw-table msw-table-soft"><table>
        <thead><tr>
            @foreach (['refund', 'order', 'customer', 'total', 'date', 'action'] as $column)
                <th>{{ trans('admin::seller-workspace.columns.'.$column) }}</th>
            @endforeach
        </tr></thead>
        <tbody>
            @forelse ($rows as $refund)
                <tr>
                    <td>#{{ $refund->increment_id ?: $refund->id }}</td>
                    <td>#{{ $refund->order?->increment_id }}</td>
                    <td>{{ $refund->order?->customer_first_name }} {{ $refund->order?->customer_last_name }}</td>
                    <td>{{ core()->formatBasePrice($refund->base_grand_total) }}</td>
                    <td>{{ $refund->created_at }}</td>
                    <td>
                        @if (bouncer()->hasPermission('sales.refunds.view'))
                            <a href="{{ route('admin.sales.refunds.view', $refund->id) }}">{{ trans('admin::seller-workspace.open') }}</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><p class="msw-empty">{{ trans('admin::seller-workspace.empty') }}</p></td></tr>
            @endforelse
        </tbody>
    </table></div>
</section>
@include('manpleasure-admin::seller-workspace.pager')
