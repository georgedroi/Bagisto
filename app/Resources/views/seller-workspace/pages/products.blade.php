<div class="msw-title-row">
    <div>@include('manpleasure-admin::seller-workspace.heading')</div>
    @if (bouncer()->hasPermission('catalog.product_entry'))
        <a class="msw-button msw-primary" style="margin: 28px 0 -4px" href="{{ route('admin.seller_workspace.add_product') }}">+ {{ trans('admin::seller-workspace.products.add') }}</a>
    @endif
</div>

<section class="msw-card">
    <h2>{{ trans('admin::seller-workspace.products.overview') }}</h2>
    <div class="msw-grid-3">
        @foreach (\App\Repositories\ProductStockRepository::FILTERS as $filter => $label)
            <a @class(['msw-tile', 'is-active' => $stockFilter === $filter || ($filter === 'inactive' && $tab === 'inactive')]) href="{{ route('admin.seller_workspace.products', $filter === 'inactive' ? ['tab' => 'inactive'] : ['stock' => $filter]) }}"><span>{{ trans($label) }}</span><strong>{{ number_format($stock[$filter]) }}</strong></a>
        @endforeach
    </div>
</section>

<div class="msw-tabs">
    @foreach ($tabs as $key)
        <a href="{{ route('admin.seller_workspace.products', array_filter(['tab' => $key, 'search' => $search, 'stock' => $stockFilter, 'alert' => $alert ? 1 : null])) }}" @class(['is-active' => $tab === $key])>{{ trans('admin::seller-workspace.products.tab-'.$key) }}</a>
    @endforeach
</div>

<section class="msw-card">
    <form class="msw-filter" method="get" action="{{ route('admin.seller_workspace.products') }}">
        <input type="hidden" name="tab" value="{{ $tab }}">
        @if ($alert)
            <input type="hidden" name="alert" value="1">
        @endif
        @if ($stockFilter)
            <input type="hidden" name="stock" value="{{ $stockFilter }}">
        @endif
        <div class="msw-chips" style="flex-basis: 100%; min-height: 44px; align-items: flex-start; gap: 12px; line-height: 32px">
            <span>{{ trans('admin::seller-workspace.products.filter') }}</span>
            @foreach (['out', 'low'] as $filter)
                <a @class(['msw-chip', 'is-active' => $stockFilter === $filter]) style="height: 32px" href="{{ route('admin.seller_workspace.products', array_filter(['tab' => $tab, 'search' => $search, 'stock' => $stockFilter === $filter ? null : $filter, 'alert' => $alert ? 1 : null])) }}">{{ trans('admin::seller-workspace.products.'.$filter) }}</a>
            @endforeach
            <a @class(['msw-chip', 'is-active' => $alert]) style="height: 32px" href="{{ route('admin.seller_workspace.products', array_filter(['tab' => $tab, 'search' => $search, 'stock' => $stockFilter, 'alert' => $alert ? null : 1])) }}">{{ trans('admin::seller-workspace.products.alerts', ['count' => number_format($alertCount)]) }}</a>
        </div>
        <label class="msw-field">
            <span>{{ trans('admin::seller-workspace.products.search') }}</span>
            <input type="search" name="search" value="{{ $search }}" maxlength="255">
            <button type="submit" aria-label="{{ trans('admin::seller-workspace.search') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'search'])</button>
        </label>
    </form>
</section>

<section class="msw-card">
    <p class="msw-toolbar">{{ trans('admin::seller-workspace.page-summary', ['page' => $rows->currentPage(), 'from' => $rows->firstItem() ?? 0, 'to' => $rows->lastItem() ?? 0, 'total' => $rows->total()]) }}</p>
    <div class="msw-table msw-product-table" style="margin-top: 0"><table>
        <thead><tr>
            @foreach (['product', 'price', 'stock', 'status', 'action'] as $column)
                <th>{{ trans('admin::seller-workspace.columns.'.$column) }}</th>
            @endforeach
        </tr></thead>
        <tbody>
            @forelse ($rows as $product)
                <tr>
                    <td>
                        <div class="msw-product">
                            @if ($product->base_image)
                                <img src="{{ Storage::url($product->base_image) }}" alt="">
                            @else
                                <i aria-hidden="true"></i>
                            @endif
                            <div><b>{{ $product->name }}</b><small>{{ trans('admin::seller-workspace.columns.sku') }}: {{ $product->sku }}</small></div>
                        </div>
                    </td>
                    <td>
                        @php
                            $editable = in_array($product->type, ['simple', 'virtual'], true);
                            $onDeal = $product->special_price !== null && (float) $product->special_price < (float) $product->price && core()->isChannelDateInInterval(null, $product->special_price_to);
                            $dealDates = trim(collect([$product->special_price_from, $product->special_price_to])->map(fn ($date) => $date ? \Illuminate\Support\Carbon::parse($date)->format('d-m-Y') : '')->implode(' ~ '));
                        @endphp
                        @if ($onDeal && $dealDates !== '~')
                            <small class="msw-deal-dates">{{ $dealDates }}</small>
                        @endif
                        <span class="msw-price">{{ core()->formatBasePrice((float) ($onDeal ? $product->special_price : $product->price)) }}</span>
                        @if ($editable && bouncer()->hasPermission('catalog.product_pricing'))
                            <button type="button" class="msw-pencil" data-msw-open="msw-price-{{ $product->product_id }}" aria-label="{{ trans('admin::seller-workspace.actions.edit-price') }}" title="{{ trans('admin::seller-workspace.actions.edit-price') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'pencil'])</button>
                        @endif
                        @if ($onDeal)
                            <s class="msw-was">{{ core()->formatBasePrice((float) $product->price) }}</s>
                        @endif
                    </td>
                    <td>
                        {{ number_format((int) $product->quantity) }}
                        @if ($editable && bouncer()->hasPermission('catalog.product_stock'))
                            <button type="button" class="msw-pencil" data-msw-open="msw-stock-{{ $product->product_id }}" aria-label="{{ trans('admin::seller-workspace.actions.edit-stock') }}" title="{{ trans('admin::seller-workspace.actions.edit-stock') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'pencil'])</button>
                        @endif
                        @if ($thresholds->has($product->product_id) && (int) $product->quantity < $thresholds[$product->product_id])
                            <span class="msw-alert-badge">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'bell']) {{ trans('admin::seller-workspace.products.alert-badge') }}</span>
                        @endif
                    </td>
                    @php
                        $statusText = trans($product->status ? 'admin::seller-workspace.products.tab-active' : 'admin::seller-workspace.products.tab-inactive');
                    @endphp
                    <td>
                        <span @class(['msw-online', 'is-on' => $product->status])>{{ $statusText }}</span>
                    </td>
                    <td>
                        <div class="msw-action">
                            @if (bouncer()->hasPermission('catalog.products.edit'))
                                <form class="msw-status" method="post" action="{{ route('admin.seller_workspace.product_lifecycle', $product->product_id) }}">
                                    @csrf
                                    <input type="hidden" name="state" value="{{ $product->status ? 'offline' : 'online' }}">
                                    <button type="submit" @class(['msw-switch', 'is-on' => $product->status]) role="switch" aria-checked="{{ $product->status ? 'true' : 'false' }}" aria-label="{{ $statusText }}" title="{{ $statusText }}"></button>
                                </form>
                            @else
                                <span @class(['msw-switch', 'is-on' => $product->status]) role="img" aria-label="{{ $statusText }}" title="{{ $statusText }}"></span>
                            @endif
                            <div class="msw-more">
                                <button type="button" data-msw-more aria-expanded="false">{{ trans('admin::seller-workspace.products.more') }} @include('manpleasure-admin::seller-workspace.icon', ['name' => 'down'])</button>
                                <div class="msw-more-menu" hidden>
                                    <a href="{{ app(\App\Support\ProductEntryCatalog::class)->editUrl($product->product_id, $product->type) }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'pencil']) {{ trans('admin::seller-workspace.edit') }}</a>
                                    @if (bouncer()->hasPermission('catalog.products.copy'))
                                        <form method="post" action="{{ route('admin.catalog.products.copy', $product->product_id) }}" data-msw-json data-msw-strict>
                                            @csrf
                                            <button type="submit">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'copy']) {{ trans('admin::seller-workspace.products.copy') }}</button>
                                            <small data-msw-message role="status"></small>
                                        </form>
                                    @endif
                                    @if ($editable && bouncer()->hasPermission('catalog.product_stock'))
                                        <button type="button" data-msw-open="msw-alert-{{ $product->product_id }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'bell']) {{ trans($thresholds->has($product->product_id) ? 'admin::seller-workspace.editor.alert-edit' : 'admin::seller-workspace.editor.alert-create') }}</button>
                                    @endif
                                    @if (bouncer()->hasPermission('catalog.products.edit'))
                                        @php
                                            $isObsolete = ! $product->status && ($states[$product->product_id] ?? null) === \App\Repositories\ProductLifecycleRepository::OBSOLETE;
                                        @endphp
                                        <form method="post" action="{{ route('admin.seller_workspace.product_lifecycle', $product->product_id) }}">
                                            @csrf
                                            <input type="hidden" name="state" value="{{ $isObsolete ? 'offline' : 'obsolete' }}">
                                            <button type="submit">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'alert']) {{ trans($isObsolete ? 'admin::seller-workspace.products.restore' : 'admin::seller-workspace.products.mark-obsolete') }}</button>
                                        </form>
                                    @endif
                                    @if (bouncer()->hasPermission('catalog.products.delete'))
                                        <form method="post" action="{{ route('admin.catalog.products.delete', $product->product_id) }}" data-msw-json data-msw-confirm="{{ trans('admin::seller-workspace.products.delete-confirm', ['name' => $product->name]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'trash']) {{ trans('admin::seller-workspace.products.delete') }}</button>
                                            <small data-msw-message role="status"></small>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5"><p class="msw-empty">{{ trans('admin::seller-workspace.empty') }}</p></td></tr>
            @endforelse
        </tbody>
    </table></div>
</section>
@include('manpleasure-admin::seller-workspace.product-dialogs')
@include('manpleasure-admin::seller-workspace.pager')
