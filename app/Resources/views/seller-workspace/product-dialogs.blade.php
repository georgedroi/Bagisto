@foreach ($rows as $product)
    @continue(! in_array($product->type, ['simple', 'virtual'], true))

    @if (bouncer()->hasPermission('catalog.product_pricing'))
        <dialog class="msw-modal msw-modal-price" id="msw-price-{{ $product->product_id }}" aria-labelledby="msw-price-title-{{ $product->product_id }}" data-msw-load="{{ route('admin.catalog.product_pricing.show', $product->product_id) }}" data-msw-kind="price">
            <form method="post" action="{{ route('admin.catalog.product_pricing.update', $product->product_id) }}" data-msw-json>
                @csrf
                @method('PUT')
                <input type="hidden" name="special_price_from" value="">
                <input type="hidden" name="special_price_to" value="">
                <h2 class="msw-modal-title" id="msw-price-title-{{ $product->product_id }}">{{ trans('admin::seller-workspace.actions.edit-price') }}</h2>
                <button type="button" class="msw-modal-close" data-msw-close aria-label="{{ trans('admin::seller-workspace.editor.close') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'close'])</button>
                <div class="msw-modal-body">
                    <table class="msw-modal-table">
                        <thead><tr>
                            <th>{{ trans('admin::seller-workspace.editor.product-info') }}</th>
                            <th>{{ trans('admin::seller-workspace.editor.retail') }}</th>
                            <th>{{ trans('admin::seller-workspace.editor.daily') }}</th>
                        </tr></thead>
                        <tbody><tr>
                            @include('manpleasure-admin::seller-workspace.product-sku')
                            <td data-label="{{ trans('admin::seller-workspace.editor.retail') }}">
                                <label class="msw-money"><span data-msw-currency></span><input type="text" inputmode="numeric" name="price" data-msw-money required aria-label="{{ trans('admin::seller-workspace.editor.retail') }}"></label>
                            </td>
                            <td data-label="{{ trans('admin::seller-workspace.editor.daily') }}">
                                <div class="msw-money-row">
                                    <label class="msw-money"><span data-msw-currency></span><input type="text" inputmode="numeric" name="special_price" data-msw-money aria-label="{{ trans('admin::seller-workspace.editor.daily') }}"></label>
                                    <button type="button" class="msw-clock" data-msw-date-open title="{{ trans('admin::seller-workspace.editor.date-title') }}" aria-label="{{ trans('admin::seller-workspace.editor.date-title') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'clock'])</button>
                                </div>
                            </td>
                        </tr></tbody>
                    </table>
                </div>
                <div class="msw-modal-foot">
                    <small data-msw-message role="status"></small>
                    <button type="button" class="msw-button msw-modal-cancel" data-msw-close>{{ trans('admin::seller-workspace.editor.cancel') }}</button>
                    <button type="submit" class="msw-button msw-primary">{{ trans('admin::seller-workspace.editor.ok') }}</button>
                </div>

                <div class="msw-date" data-msw-date hidden data-msw-prev-year="{{ trans('admin::seller-workspace.editor.prev-year') }}" data-msw-prev-month="{{ trans('admin::seller-workspace.editor.prev-month') }}" data-msw-next-month="{{ trans('admin::seller-workspace.editor.next-month') }}" data-msw-next-year="{{ trans('admin::seller-workspace.editor.next-year') }}">
                    <button type="button" class="msw-date-close" data-msw-date-close aria-label="{{ trans('admin::seller-workspace.editor.close') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'close'])</button>
                    <b>{{ trans('admin::seller-workspace.editor.date-title') }}</b>
                    <div class="msw-date-select">
                        <button type="button" data-msw-date-mode aria-expanded="false" data-msw-placeholder="{{ trans('admin::seller-workspace.editor.date-set') }}">
                            <span></span>
                            @include('manpleasure-admin::seller-workspace.icon', ['name' => 'down'])
                        </button>
                        <div class="msw-date-options" data-msw-date-options hidden>
                            <button type="button" data-msw-date-pick="long">{{ trans('admin::seller-workspace.editor.long-term') }}</button>
                            <button type="button" data-msw-date-pick="set">{{ trans('admin::seller-workspace.editor.set-date') }}</button>
                        </div>
                    </div>
                    <div class="msw-date-range" data-msw-date-range hidden>
                        <button type="button" data-msw-date-field>
                            <span data-msw-date-from data-msw-placeholder="{{ trans('admin::seller-workspace.editor.start') }}"></span>
                            <i>-</i>
                            <span data-msw-date-to data-msw-placeholder="{{ trans('admin::seller-workspace.editor.end') }}"></span>
                            @include('manpleasure-admin::seller-workspace.icon', ['name' => 'calendar'])
                        </button>
                        <div class="msw-calendar" data-msw-calendar hidden>
                            <div class="msw-calendar-head" data-msw-calendar-head></div>
                            <div class="msw-calendar-grid" data-msw-calendar-grid></div>
                            <div class="msw-calendar-foot"><button type="button" class="msw-button msw-primary" data-msw-calendar-ok>{{ trans('admin::seller-workspace.editor.ok') }}</button></div>
                        </div>
                    </div>
                    <div class="msw-date-foot">
                        <button type="button" class="msw-button msw-modal-cancel" data-msw-date-close>{{ trans('admin::seller-workspace.editor.cancel') }}</button>
                        <button type="button" class="msw-button msw-primary" data-msw-date-confirm>{{ trans('admin::seller-workspace.editor.confirm') }}</button>
                    </div>
                </div>
            </form>
        </dialog>
    @endif

    @if (bouncer()->hasPermission('catalog.product_stock'))
        <dialog class="msw-modal msw-modal-alert" id="msw-alert-{{ $product->product_id }}" aria-labelledby="msw-alert-title-{{ $product->product_id }}">
            <form method="post" action="{{ route('admin.catalog.product_stock.alert', $product->product_id) }}" data-msw-json>
                @csrf
                @method('PUT')
                <h2 class="msw-modal-title" id="msw-alert-title-{{ $product->product_id }}">{{ trans($thresholds->has($product->product_id) ? 'admin::seller-workspace.editor.alert-edit' : 'admin::seller-workspace.editor.alert-create') }}</h2>
                <p class="msw-modal-intro">{{ trans('admin::seller-workspace.editor.alert-intro') }}</p>
                <button type="button" class="msw-modal-close" data-msw-close aria-label="{{ trans('admin::seller-workspace.editor.close') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'close'])</button>
                <div class="msw-modal-body">
                    <table class="msw-modal-table">
                        <thead><tr>
                            <th>{{ trans('admin::seller-workspace.editor.product-info') }}</th>
                            <th>{{ trans('admin::seller-workspace.editor.alert-status') }}</th>
                            <th>{{ trans('admin::seller-workspace.editor.alert-level') }}</th>
                        </tr></thead>
                        <tbody><tr>
                            @include('manpleasure-admin::seller-workspace.product-sku')
                            <td data-label="{{ trans('admin::seller-workspace.editor.alert-status') }}">
                                <label class="msw-switch-field">
                                    <input type="checkbox" name="enabled" value="1" @checked($thresholds->has($product->product_id)) aria-label="{{ trans('admin::seller-workspace.editor.alert-status') }}">
                                    <span class="msw-switch" aria-hidden="true"></span>
                                </label>
                            </td>
                            <td data-label="{{ trans('admin::seller-workspace.editor.alert-level') }}">
                                <input class="msw-alert-input" type="number" name="threshold" step="1" min="1" value="{{ $thresholds[$product->product_id] ?? \App\Repositories\ProductStockRepository::LOW_STOCK_LIMIT }}" required aria-label="{{ trans('admin::seller-workspace.editor.alert-level') }}">
                            </td>
                        </tr></tbody>
                    </table>
                </div>
                <div class="msw-modal-foot">
                    <small data-msw-message role="status"></small>
                    <button type="button" class="msw-button msw-modal-cancel" data-msw-close>{{ trans('admin::seller-workspace.editor.cancel') }}</button>
                    <button type="submit" class="msw-button msw-primary">{{ trans('admin::seller-workspace.editor.save') }}</button>
                </div>
            </form>
        </dialog>

        <dialog class="msw-modal msw-modal-stock" id="msw-stock-{{ $product->product_id }}" aria-labelledby="msw-stock-title-{{ $product->product_id }}" data-msw-load="{{ route('admin.catalog.product_stock.show', $product->product_id) }}" data-msw-kind="stock">
            <form method="post" action="{{ route('admin.catalog.product_stock.update', $product->product_id) }}" data-msw-json>
                @csrf
                @method('PUT')
                <input type="hidden" name="batches_sync" value="1">
                <h2 class="msw-modal-title" id="msw-stock-title-{{ $product->product_id }}">{{ trans('admin::seller-workspace.actions.edit-stock') }}</h2>
                <button type="button" class="msw-modal-close" data-msw-close aria-label="{{ trans('admin::seller-workspace.editor.close') }}">@include('manpleasure-admin::seller-workspace.icon', ['name' => 'close'])</button>
                <div class="msw-modal-body">
                    <table class="msw-modal-table">
                        <thead><tr>
                            <th>{{ trans('admin::seller-workspace.editor.product-info') }}</th>
                            <th>{{ trans('admin::seller-workspace.editor.warehouse') }}</th>
                            <th>{{ trans('admin::seller-workspace.editor.batch-no') }}</th>
                            <th><span class="msw-help" title="{{ trans('admin::seller-workspace.editor.stock-help') }}">{{ trans('admin::seller-workspace.editor.stock') }} @include('manpleasure-admin::seller-workspace.icon', ['name' => 'info'])</span></th>
                            <th>{{ trans('admin::seller-workspace.editor.ship-date') }}</th>
                            <th>{{ trans('admin::seller-workspace.columns.action') }}</th>
                        </tr></thead>
                        <tbody><tr>
                            @include('manpleasure-admin::seller-workspace.product-sku', ['batchBadge' => true])
                            <td class="msw-stock-cell" colspan="5">
                                <table class="msw-stock-table">
                                    <thead><tr>
                                        <th>{{ trans('admin::seller-workspace.editor.all-warehouses') }}</th>
                                        <th></th>
                                        <th data-msw-total></th>
                                        <th></th>
                                        <th></th>
                                    </tr></thead>
                                    <tbody data-msw-sources data-msw-ready="{{ trans('admin::seller-workspace.editor.ready') }}" data-msw-sla="{{ trans('admin::seller-workspace.editor.sla') }}" data-msw-batch="{{ trans('admin::seller-workspace.editor.batch-label') }}" data-msw-add="{{ trans('admin::seller-workspace.editor.add-batch') }}" data-msw-remove="{{ trans('admin::seller-workspace.editor.remove-batch') }}" data-msw-dates="{{ trans('admin::seller-workspace.editor.ship-date') }}" data-msw-max="{{ \App\Http\Requests\ProductStockRequest::MAX_BATCHES }}"></tbody>
                                </table>
                            </td>
                        </tr></tbody>
                    </table>
                </div>
                <div class="msw-modal-foot">
                    <small data-msw-message role="status"></small>
                    <button type="button" class="msw-button msw-modal-cancel" data-msw-close>{{ trans('admin::seller-workspace.editor.cancel') }}</button>
                    <button type="submit" class="msw-button msw-primary">{{ trans('admin::seller-workspace.editor.ok') }}</button>
                </div>
                <div class="msw-calendar msw-batch-cal" data-msw-batch-cal hidden data-msw-prev-year="{{ trans('admin::seller-workspace.editor.prev-year') }}" data-msw-prev-month="{{ trans('admin::seller-workspace.editor.prev-month') }}" data-msw-next-month="{{ trans('admin::seller-workspace.editor.next-month') }}" data-msw-next-year="{{ trans('admin::seller-workspace.editor.next-year') }}">
                    <div class="msw-calendar-head" data-msw-calendar-head></div>
                    <div class="msw-calendar-grid" data-msw-calendar-grid></div>
                    <div class="msw-calendar-foot"><button type="button" class="msw-button msw-primary" data-msw-calendar-ok>{{ trans('admin::seller-workspace.editor.ok') }}</button></div>
                </div>
            </form>
        </dialog>
    @endif
@endforeach
