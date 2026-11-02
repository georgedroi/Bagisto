@include('manpleasure-admin::seller-workspace.heading')

<div class="msw-tabs">
    <a href="{{ route('admin.seller_workspace.orders', array_filter(['search' => $search])) }}" @class(['is-active' => ! $queue])>{{ trans('admin::seller-workspace.all') }}</a>
    @foreach (\App\Repositories\OrderQueueRepository::QUEUES as $key => $label)
        <a href="{{ route('admin.seller_workspace.orders', array_filter(['queue' => $key, 'search' => $search])) }}" @class(['is-active' => $queue === $key])>{{ trans($label) }}@if ($queues[$key])<sup>{{ number_format($queues[$key]) }}</sup>@endif</a>
    @endforeach
</div>

@if ($queue === 'to_ship')
    <div class="msw-subtabs">
        @foreach (['packing', 'ready_to_ship'] as $stage)
            <a href="{{ route('admin.sales.orders.index', ['workflow' => $stage]) }}">{{ trans('admin::order-status.'.$stage) }} ({{ number_format($stages[$stage]) }})</a>
        @endforeach
    </div>
@endif

<section class="msw-card">
    <form class="msw-filter" method="get" action="{{ route('admin.seller_workspace.orders') }}">
        @if ($queue)
            <input type="hidden" name="queue" value="{{ $queue }}">
        @endif
        <label class="msw-field">
            <span>{{ trans('admin::seller-workspace.orders.search') }}</span>
            <input type="search" name="search" value="{{ $search }}" maxlength="255">
            <button type="submit" aria-label="{{ trans('admin::seller-workspace.search') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'search'])</button>
        </label>
    </form>
</section>

<section class="msw-card msw-summary">{{ trans('admin::seller-workspace.page-summary', ['page' => $rows->currentPage(), 'from' => $rows->firstItem() ?? 0, 'to' => $rows->lastItem() ?? 0, 'total' => $rows->total()]) }}</section>

@include('manpleasure-admin::seller-workspace.order-table')
@include('manpleasure-admin::seller-workspace.pager')
