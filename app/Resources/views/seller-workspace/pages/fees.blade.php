@include('manpleasure-admin::seller-workspace.heading')

<p class="msw-note">{{ trans('admin::seller-workspace.fees.note') }}</p>

<section class="msw-card">
    <form class="msw-filter" method="get" action="{{ route('admin.seller_workspace.fees') }}">
        <label class="msw-field"><span>{{ trans('admin::seller-workspace.fees.from') }}</span><input type="date" name="from" value="{{ $from }}"></label>
        <label class="msw-field"><span>{{ trans('admin::seller-workspace.fees.to') }}</span><input type="date" name="to" value="{{ $to }}"></label>
        <button class="msw-button msw-primary" type="submit">{{ trans('admin::seller-workspace.search') }}</button>
    </form>
</section>

<section class="msw-card">
    <div class="msw-grid-4" style="margin-top: 0">
        <div class="msw-tile"><span>{{ trans('admin::seller-workspace.fees.charged') }}</span><strong>{{ core()->formatBasePrice($charged) }}</strong></div>
        <div class="msw-tile"><span>{{ trans('admin::seller-workspace.fees.invoiced') }}</span><strong>{{ core()->formatBasePrice($invoiced) }}</strong></div>
    </div>

    <div class="msw-table msw-table-soft"><table>
        <thead><tr>
            @foreach (['order', 'carrier', 'charged', 'invoiced', 'date'] as $column)
                <th>{{ trans('admin::seller-workspace.columns.'.$column) }}</th>
            @endforeach
        </tr></thead>
        <tbody>
            @forelse ($rows as $order)
                <tr>
                    <td>#{{ $order->increment_id }}</td>
                    <td>{{ $order->shipping_title ?: '—' }}</td>
                    <td>{{ core()->formatBasePrice($order->base_shipping_amount) }}</td>
                    <td>{{ core()->formatBasePrice($order->base_shipping_invoiced) }}</td>
                    <td>{{ $order->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><p class="msw-empty">{{ trans('admin::seller-workspace.empty') }}</p></td></tr>
            @endforelse
        </tbody>
    </table></div>
</section>
@include('manpleasure-admin::seller-workspace.pager')
