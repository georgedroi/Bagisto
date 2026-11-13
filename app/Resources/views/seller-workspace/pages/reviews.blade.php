@include('manpleasure-admin::seller-workspace.heading')

<section class="msw-card msw-card-tall">
    <h2 class="msw-title">{{ trans('admin::seller-workspace.reviews.overview') }}</h2>
    <div class="msw-grid-4">
        <div class="msw-tile msw-tile-big"><span>{{ trans('admin::seller-workspace.reviews.pending') }}</span><strong>{{ number_format($pending) }}</strong></div>
        <div class="msw-tile msw-tile-big"><span>{{ trans('admin::seller-workspace.reviews.average') }}</span><strong>{{ $average ?? '—' }}</strong></div>
    </div>
</section>

<div class="msw-tabs">
    <a href="{{ route('admin.seller_workspace.reviews') }}" @class(['is-active' => ! $status])>{{ trans('admin::seller-workspace.all') }}</a>
    @foreach ($tabs as $tab)
        <a href="{{ route('admin.seller_workspace.reviews', ['status' => $tab]) }}" @class(['is-active' => $status === $tab])>{{ trans('admin::seller-workspace.reviews.'.$tab) }}</a>
    @endforeach
</div>

<section class="msw-card">
    <div class="msw-chips">
        <span>{{ trans('admin::seller-workspace.columns.rating') }}</span>
        @foreach (range(1, 5) as $value)
            <a @class(['msw-chip', 'is-active' => (int) $rating === $value]) href="{{ route('admin.seller_workspace.reviews', array_filter(['status' => $status, 'rating' => (int) $rating === $value ? null : $value])) }}">{{ $value }} / 5</a>
        @endforeach
    </div>

    <div class="msw-table msw-table-soft"><table>
        <thead><tr>
            @foreach (['review', 'product', 'rating', 'status', 'date', 'action'] as $column)
                <th>{{ trans('admin::seller-workspace.columns.'.$column) }}</th>
            @endforeach
        </tr></thead>
        <tbody>
            @forelse ($rows as $review)
                <tr>
                    <td><strong>{{ $review->title }}</strong><small>{{ $review->name }}</small>{{ \Illuminate\Support\Str::limit($review->comment, 160) }}</td>
                    <td>{{ $review->product?->name }}</td>
                    <td>{{ $review->rating }} / 5</td>
                    <td><span class="msw-pill">{{ trans('admin::seller-workspace.reviews.'.$review->status) }}</span></td>
                    <td>{{ $review->created_at }}</td>
                    <td>
                        @if (bouncer()->hasPermission('customers.reviews.edit'))
                            @foreach (['approved' => 'approve', 'disapproved' => 'disapprove'] as $value => $label)
                                @if ($review->status !== $value)
                                    <form class="msw-inline" method="post" action="{{ route('admin.customers.customers.review.update', $review->id) }}" data-msw-json>
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="status" value="{{ $value }}">
                                        <button class="msw-button" type="submit">{{ trans('admin::seller-workspace.actions.'.$label) }}</button>
                                        <small data-msw-message role="status"></small>
                                    </form>
                                @endif
                            @endforeach
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
