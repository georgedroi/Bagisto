@php
    $productColumns = config('manpleasure-product-columns');
    $hiddenColumns = array_keys(array_filter($productColumns, fn ($column) => ! $column['default']));
@endphp

@pushOnce('styles')
    <style>
        @foreach (array_keys($productColumns) as $key)
            .msp-product-grid[data-hidden~="{{ $key }}"] [data-col="{{ $key }}"] { display: none; }
        @endforeach
        .msp-product-grid .msp-grid-row { min-width: var(--msp-product-min, 920px); grid-template-columns: var(--msp-product-cols, 20px 2fr 1.1fr .8fr .9fr .7fr 1fr); }
        .msp-product-grid .msp-clip { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
@endPushOnce

<nav class="msp-status-tabs" aria-label="{{ trans('admin::executive-layout.inventory') }}">
    @foreach (['' => 'admin::executive-layout.all-products', ...\App\Repositories\ProductStockRepository::FILTERS] as $filter => $label)
        <a href="{{ route('admin.catalog.products.index', $filter ? ['stock' => $filter] : []) }}" class="{{ request('stock', '') === $filter ? 'is-active' : '' }}" data-action="filter-product-stock" @if (request('stock', '') === $filter) aria-current="page" @endif>
            {{ trans($label) }}
        </a>
    @endforeach
</nav>

<div class="msp-data-grid msp-product-grid" data-hidden="{{ implode(' ', $hiddenColumns) }}">
    <x-admin::datagrid :src="route('admin.catalog.products.index')" :isMultiRow="true">
        <template #header="{ isLoading, available, applied, selectAll, sort }">
            <div class="msp-grid-row msp-grid-head" v-if="! isLoading">
                <div><input v-if="available.massActions.length" type="checkbox" :checked="applied.massActions.meta.mode === 'all'" @change="selectAll" aria-label="{{ trans('admin::executive-layout.select-all') }}"></div>
                <button type="button" data-action="sort-product" @click="sort(available.columns.find(column => column.index === 'name'))" :disabled="! available.columns.find(column => column.index === 'name')?.sortable">{{ trans('admin::executive-layout.product') }}</button>
                @foreach ($productColumns as $key => $column)
                    @if ($column['sort'])
                        <button type="button" data-col="{{ $key }}" data-action="sort-product" @click="sort(available.columns.find(column => column.index === '{{ $column['sort'] }}'))" :disabled="! available.columns.find(column => column.index === '{{ $column['sort'] }}')?.sortable">{{ trans($column['label']) }}</button>
                    @else
                        <span data-col="{{ $key }}">{{ trans($column['label']) }}</span>
                    @endif
                @endforeach
                <span>{{ trans('admin::executive-layout.actions') }}</span>
            </div>
        </template>
        <template #body="{ isLoading, available, applied, performAction }">
            <div v-if="isLoading" class="msp-grid-loading" aria-busy="true"><span class="shimmer"></span></div>
            <template v-else>
                <div class="msp-grid-row" v-for="record in available.records" :key="record.product_id" data-entity="product" :data-id="record.sku">
                    <div><input v-if="available.massActions.length" type="checkbox" :value="record.product_id" v-model="applied.massActions.indices" aria-label="{{ trans('admin::executive-layout.select-row') }}"></div>
                    <div class="msp-product-identity">
                        <img v-if="record.base_image" :src="record.base_image" :alt="record.name" loading="lazy">
                        <span v-else class="msp-image-placeholder icon-image" aria-hidden="true"></span>
                        <div><strong data-field="product.name">@{{ record.name }}</strong><small class="msp-mono" data-field="product.sku">@{{ record.sku }}</small></div>
                    </div>
                    <div data-col="category"><span>@{{ record.category_name || '—' }}</span><small>@{{ record.type }}</small></div>
                    <div data-col="inventory"><span class="msp-stock-pill" :class="{ 'is-empty': Number(record.quantity) <= 0, 'is-low': Number(record.quantity) > 0 && Number(record.quantity) <= 10 }" data-field="product.quantity">@{{ record.quantity ?? 0 }}</span></div>
                    <strong data-col="price" class="msp-money" data-field="product.price">@{{ $admin.formatPrice(record.price) }}</strong>
                    <div data-col="special_price"><template v-if="Number(record.special_price) > 0"><strong class="msp-money" style="text-align: start">@{{ $admin.formatPrice(record.special_price) }}</strong><small v-if="record.special_price_from || record.special_price_to">@{{ (record.special_price_from || '…') + ' → ' + (record.special_price_to || '…') }}</small></template><span v-else>—</span></div>
                    <div data-col="cost"><span v-if="record.cost !== null && record.cost !== undefined">@{{ $admin.formatPrice(record.cost) }}</span><span v-else>—</span></div>
                    <div data-col="margin"><span v-if="record.cost !== null && record.cost !== undefined && Number(record.price) > 0">@{{ (((Number(record.price) - Number(record.cost)) / Number(record.price)) * 100).toFixed(1) }}%</span><span v-else>—</span></div>
                    <div data-col="status" data-field="product.status"><span class="msp-stock-pill" v-if="Number(record.status)">{{ trans('admin::executive-layout.active') }}</span><span class="msp-stock-pill is-empty" v-else>{{ trans('admin::executive-layout.inactive') }}</span></div>
                    <div data-col="family"><span>@{{ record.attribute_family || '—' }}</span></div>
                    <div data-col="flags"><span class="msp-stock-pill" v-if="Number(record.new)">{{ trans('admin::product-columns.flag-new') }}</span> <span class="msp-stock-pill" v-if="Number(record.featured)">{{ trans('admin::product-columns.flag-featured') }}</span><span v-if="! Number(record.new) && ! Number(record.featured)">—</span></div>
                    <div data-col="weight"><span>@{{ record.weight ?? '—' }}</span></div>
                    <div data-col="images"><span>@{{ record.images_count ?? 0 }}</span></div>
                    <div data-col="product_id"><span class="msp-mono">@{{ record.product_id }}</span></div>
                    <div data-col="description"><span class="msp-clip">@{{ record.short_description || '—' }}</span></div>
                    <div data-col="created"><span>@{{ record.created_at }}</span></div>
                    <div data-col="updated"><span>@{{ record.updated_at }}</span></div>
                    <div class="msp-row-actions">
                        @if (bouncer()->hasPermission('catalog.product_pricing'))
                            <button v-if="['simple', 'virtual'].includes(record.type)" type="button" class="msp-review-button" @click="$emitter.emit('open-product-pricing', record.product_id)" data-action="edit-pricing">{{ trans('admin::product-pricing.edit-pricing') }}</button>
                        @endif
                        <a v-if="record.url_key && Number(record.status)" :href="@js(url('/')) + '/' + record.url_key" target="_blank" rel="noopener" class="msp-review-button" data-action="view-storefront">{{ trans('admin::product-pricing.view-storefront') }}</a>
                        <button v-for="(action, actionIndex) in record.actions" :key="actionIndex" type="button" class="msp-review-button" @click="performAction(action)" :title="action.title" :aria-label="action.title" data-action="review-product">@{{ action.title }}</button>
                    </div>
                </div>
                <p v-if="! available.records.length" class="msp-empty">{{ trans('admin::executive-layout.no-records') }}</p>
            </template>
        </template>
    </x-admin::datagrid>
</div>
