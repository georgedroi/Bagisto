@include('manpleasure-admin::seller-workspace.heading', ['titleClass' => 'msw-h1-strong'])

<section class="msw-card msw-card-round">
    <h2 class="msw-title" style="line-height: 25px !important; font-weight: 700 !important">{{ trans('admin::seller-workspace.income.overview') }}</h2>
    <div class="msw-grid-3">
        <div class="msw-tile"><span>{{ trans('admin::seller-workspace.income.paid') }}</span><strong style="font-size: 24px; font-weight: 700; line-height: 32px">{{ core()->formatBasePrice($paid) }}</strong></div>
        <div class="msw-tile"><span>{{ trans('admin::seller-workspace.income.outstanding') }}</span><strong style="font-size: 24px; font-weight: 700; line-height: 32px">{{ core()->formatBasePrice($outstanding) }}</strong></div>
        <div class="msw-tile"><span>{{ trans('admin::seller-workspace.income.refunded') }}</span><strong style="font-size: 24px; font-weight: 700; line-height: 32px">{{ core()->formatBasePrice($refunded) }}</strong></div>
    </div>
</section>

<section class="msw-card msw-card-round">
    <div class="msw-tabs msw-tabs-sm">
        <a href="{{ route('admin.seller_workspace.income') }}" @class(['is-active' => ! $state])>{{ trans('admin::seller-workspace.all') }}</a>
        @foreach (['pending', 'paid', 'overdue', 'refunded'] as $option)
            <a href="{{ route('admin.seller_workspace.income', ['state' => $option]) }}" @class(['is-active' => $state === $option])>{{ trans('admin::seller-workspace.income.'.$option) }}</a>
        @endforeach
    </div>

    <div class="msw-table msw-table-soft"><table>
        <thead><tr>
            @foreach (['invoice', 'order', 'status', 'total', 'date', 'action'] as $column)
                <th>{{ trans('admin::seller-workspace.columns.'.$column) }}</th>
            @endforeach
        </tr></thead>
        <tbody>
            @forelse ($rows as $invoice)
                <tr>
                    <td>#{{ $invoice->increment_id ?: $invoice->id }}</td>
                    <td>#{{ $invoice->order?->increment_id }}</td>
                    <td><span class="msw-pill">{{ $invoice->status_label }}</span></td>
                    <td>{{ core()->formatBasePrice($invoice->base_grand_total) }}</td>
                    <td>{{ $invoice->created_at }}</td>
                    <td>
                        @if (bouncer()->hasPermission('sales.invoices.view'))
                            <a href="{{ route('admin.sales.invoices.view', $invoice->id) }}">{{ trans('admin::seller-workspace.open') }}</a>
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
