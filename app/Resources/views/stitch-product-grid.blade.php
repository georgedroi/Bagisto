<div class="msp-data-grid msp-product-grid">
    <x-admin::datagrid :src="route('admin.catalog.products.index')" :isMultiRow="true">
        <template #header="{ isLoading, available, applied, selectAll, sort }">
            <div class="msp-grid-row msp-grid-head" v-if="! isLoading">
                <div><input v-if="available.massActions.length" type="checkbox" :checked="applied.massActions.meta.mode === 'all'" @change="selectAll" aria-label="{{ trans('admin::executive-layout.select-all') }}"></div>
                <button type="button" data-action="sort-product" @click="sort(available.columns.find(column => column.index === 'name'))" :disabled="! available.columns.find(column => column.index === 'name')?.sortable">{{ trans('admin::executive-layout.product') }}</button>
                <button type="button" data-action="sort-product" @click="sort(available.columns.find(column => column.index === 'category_name'))" :disabled="! available.columns.find(column => column.index === 'category_name')?.sortable">{{ trans('admin::executive-layout.category-type') }}</button>
                <button type="button" data-action="sort-product" @click="sort(available.columns.find(column => column.index === 'quantity'))" :disabled="! available.columns.find(column => column.index === 'quantity')?.sortable">{{ trans('admin::executive-layout.inventory') }}</button>
                <button type="button" data-action="sort-product" @click="sort(available.columns.find(column => column.index === 'price'))" :disabled="! available.columns.find(column => column.index === 'price')?.sortable">{{ trans('admin::executive-layout.price') }}</button>
                <button type="button" data-action="sort-product" @click="sort(available.columns.find(column => column.index === 'status'))" :disabled="! available.columns.find(column => column.index === 'status')?.sortable">{{ trans('admin::executive-layout.status') }}</button>
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
                    <div><span>@{{ record.category_name || '—' }}</span><small>@{{ record.type }}</small></div>
                    <div><span class="msp-stock-pill" :class="{ 'is-empty': Number(record.quantity) <= 0, 'is-low': Number(record.quantity) > 0 && Number(record.quantity) <= 10 }" data-field="product.quantity">@{{ record.quantity ?? 0 }}</span></div>
                    <strong class="msp-money" data-field="product.price">@{{ $admin.formatPrice(record.price) }}</strong>
                    <div data-field="product.status"><span class="msp-stock-pill" v-if="Number(record.status)">{{ trans('admin::executive-layout.active') }}</span><span class="msp-stock-pill is-empty" v-else>{{ trans('admin::executive-layout.inactive') }}</span></div>
                    <div class="msp-row-actions">
                        <button v-for="(action, actionIndex) in record.actions" :key="actionIndex" type="button" class="msp-review-button" @click="performAction(action)" :title="action.title" :aria-label="action.title" data-action="review-product">@{{ action.title }}</button>
                    </div>
                </div>
                <p v-if="! available.records.length" class="msp-empty">{{ trans('admin::executive-layout.no-records') }}</p>
            </template>
        </template>
    </x-admin::datagrid>
</div>
