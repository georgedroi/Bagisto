@include('manpleasure-admin::seller-workspace.heading')

<div class="msw-tabs">
    <a href="{{ route('admin.seller_workspace.returns') }}" @class(['is-active' => ! $status])>{{ trans('admin::seller-workspace.all') }}</a>
    @foreach ($statuses as $option)
        <a href="{{ route('admin.seller_workspace.returns', ['status' => $option->id]) }}" @class(['is-active' => (int) $status === $option->id])>{{ $option->title }}</a>
    @endforeach
</div>

<section class="msw-card msw-summary">{{ trans('admin::seller-workspace.page-summary', ['page' => $rows->currentPage(), 'from' => $rows->firstItem() ?? 0, 'to' => $rows->lastItem() ?? 0, 'total' => $rows->total()]) }}</section>

@forelse ($rows as $request)
    <section class="msw-card">
        <div class="msw-return">
            <div><small>{{ trans('admin::seller-workspace.columns.request') }}:</small> #{{ $request->id }}</div>
            <div><small>{{ trans('admin::seller-workspace.columns.date') }}:</small> {{ $request->created_at }}</div>
            <div><span class="msw-pill">{{ $request->getRelation('status')?->title }}</span></div>
            <div><small>{{ trans('admin::seller-workspace.columns.order') }}:</small> #{{ $request->order_id }}</div>
        </div>
        <div class="msw-return-foot">
            <a class="msw-button" href="{{ route('admin.sales.rma.requests.view', $request->id) }}">{{ trans('admin::seller-workspace.open') }}</a>
        </div>
    </section>
@empty
    <p class="msw-empty">{{ trans('admin::seller-workspace.empty') }}</p>
@endforelse
@include('manpleasure-admin::seller-workspace.pager')
