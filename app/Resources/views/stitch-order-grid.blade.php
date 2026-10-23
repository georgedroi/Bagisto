<div class="msp-data-grid msp-order-grid">
    <x-admin::datagrid :src="route('admin.sales.orders.index')" :isMultiRow="true">
        <template #header="{ isLoading, available, applied, selectAll, sort }">
            <div class="msp-grid-row msp-grid-head" v-if="! isLoading">
                <button type="button" data-action="sort-order" @click="sort(available.columns.find(column => column.index === 'increment_id'))" :disabled="! available.columns.find(column => column.index === 'increment_id')?.sortable">{{ trans('admin::executive-layout.order-number') }}</button>
                <button type="button" data-action="sort-order" @click="sort(available.columns.find(column => column.index === 'full_name'))" :disabled="! available.columns.find(column => column.index === 'full_name')?.sortable">{{ trans('admin::executive-layout.customer') }}</button>
                <button type="button" data-action="sort-order" @click="sort(available.columns.find(column => column.index === 'status'))" :disabled="! available.columns.find(column => column.index === 'status')?.sortable">{{ trans('admin::executive-layout.status') }}</button>
                <button type="button" data-action="sort-order" @click="sort(available.columns.find(column => column.index === 'base_grand_total'))" :disabled="! available.columns.find(column => column.index === 'base_grand_total')?.sortable">{{ trans('admin::executive-layout.amount-items') }}</button>
                <span>{{ trans('admin::executive-layout.actions') }}</span>
            </div>
        </template>
        <template #body="{ isLoading, available, applied, performAction }">
            <div v-if="isLoading" class="msp-grid-loading" aria-busy="true"><span class="shimmer"></span></div>
            <template v-else>
                <div class="msp-grid-row" v-for="record in available.records" :key="record.id" data-entity="order" :data-id="record.increment_id">
                    <div><strong class="msp-mono" data-field="order.number">#@{{ record.increment_id }}</strong><small>@{{ record.created_at }}</small></div>
                    <div><strong>@{{ record.full_name }}</strong><small>@{{ record.customer_email }}</small></div>
                    <div data-field="order.status" v-html="record.status"></div>
                    <div class="msp-money"><strong data-field="order.amount">@{{ $admin.formatPrice(record.base_grand_total) }}</strong><small>@{{ record.total_qty_ordered ?? '—' }} {{ trans('admin::app.sales.orders.index.datagrid.items') }}</small></div>
                    <div class="msp-row-actions">
                        <button v-for="(action, actionIndex) in record.actions" :key="actionIndex" type="button" class="msp-review-button" @click="performAction(action)" :title="action.title" :aria-label="action.title" data-action="review-order">@{{ action.title }}</button>
                    </div>
                </div>
                <p v-if="! available.records.length" class="msp-empty">{{ trans('admin::executive-layout.no-records') }}</p>
            </template>
        </template>
    </x-admin::datagrid>
</div>
