@include('manpleasure-admin::seller-workspace.heading')

<section class="msw-card">
    <div class="msw-grid-4" style="margin-top: 0">
        <div class="msw-tile"><span>{{ trans('admin::seller-workspace.customers.total') }}</span><strong>{{ number_format($total) }}</strong></div>
    </div>
</section>

<section class="msw-card">
    <form class="msw-filter" method="get" action="{{ route('admin.seller_workspace.customers') }}">
        <label class="msw-field">
            <span>{{ trans('admin::seller-workspace.customers.search') }}</span>
            <input type="search" name="search" value="{{ $search }}" maxlength="255">
            <button type="submit" aria-label="{{ trans('admin::seller-workspace.search') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'search'])</button>
        </label>
    </form>
</section>

<section class="msw-card">
    <p class="msw-toolbar">{{ trans('admin::seller-workspace.page-summary', ['page' => $rows->currentPage(), 'from' => $rows->firstItem() ?? 0, 'to' => $rows->lastItem() ?? 0, 'total' => $rows->total()]) }}</p>
    <div class="msw-table msw-table-soft" style="margin-top: 0"><table>
        <thead><tr>
            @foreach (['customer', 'phone', 'group', 'status', 'date', 'action'] as $column)
                <th>{{ trans('admin::seller-workspace.columns.'.$column) }}</th>
            @endforeach
        </tr></thead>
        <tbody>
            @forelse ($rows as $customer)
                <tr>
                    <td><b>{{ $customer->first_name }} {{ $customer->last_name }}</b><small style="display: block">{{ $customer->email }}</small></td>
                    <td>{{ $customer->phone ?: '—' }}</td>
                    <td>{{ $customer->group?->name ?: '—' }}</td>
                    <td><span class="msw-pill">{{ trans($customer->status ? 'admin::seller-workspace.customers.active' : 'admin::seller-workspace.customers.inactive') }}</span></td>
                    <td>{{ $customer->created_at }}</td>
                    <td>
                        @if (bouncer()->hasPermission('customers.customers'))
                            <a href="{{ route('admin.customers.customers.view', $customer->id) }}">{{ trans('admin::seller-workspace.open') }}</a>
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
