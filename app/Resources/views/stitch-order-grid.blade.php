<div class="msp-data-grid msp-order-grid">
    <x-admin::datagrid :src="route('admin.sales.orders.index')" :isMultiRow="true">
        <template #header="{ isLoading, available, sort }">
            <div class="msp-order-list-head" v-if="! isLoading">
                <span>{{ trans('admin::executive-layout.product') }}</span>
                <button type="button" data-action="sort-order" @click="sort(available.columns.find(column => column.index === 'base_grand_total'))" :disabled="! available.columns.find(column => column.index === 'base_grand_total')?.sortable">{{ trans('admin::app.sales.orders.create.types.simple.total-amount') }}</button>
                <span>{{ trans('admin::app.sales.orders.view.shipping-method') }}</span>
                <button type="button" data-action="sort-order" @click="sort(available.columns.find(column => column.index === 'status'))" :disabled="! available.columns.find(column => column.index === 'status')?.sortable">{{ trans('admin::executive-layout.status') }}</button>
                <span>{{ trans('admin::executive-layout.actions') }}</span>
            </div>
        </template>
        <template #body="{ isLoading, available, performAction }">
            <div v-if="isLoading" class="msp-grid-loading" aria-busy="true"><span class="shimmer"></span></div>
            <template v-else>
                <article class="msp-order-card" v-for="record in available.records" :key="record.id" data-entity="order" :data-id="record.increment_id">
                    <header class="msp-order-card__header">
                        <span class="msp-order-card__avatar" aria-hidden="true">@{{ (record.full_name || '?').charAt(0).toUpperCase() }}</span>
                        <strong class="msp-order-card__customer">@{{ record.full_name || record.customer_email }}</strong>
                        <span class="msp-order-card__items">@{{ record.total_qty_ordered ?? 0 }} {{ trans('admin::app.sales.orders.index.datagrid.items') }}</span>
                        <span class="msp-order-card__number">{{ trans('admin::executive-layout.order-number') }}: <a v-if="record.actions?.length" :href="record.actions[0].url" data-field="order.number">#@{{ record.increment_id }}</a><strong v-else data-field="order.number">#@{{ record.increment_id }}</strong></span>
                        <time class="msp-order-card__date">@{{ record.updated_at || record.created_at }}</time>
                    </header>

                    <div class="msp-order-card__body">
                        <div class="msp-order-card__products" v-html="record.items"></div>
                        <div class="msp-order-card__total">
                            <span class="msp-order-card__field-label">{{ trans('admin::app.sales.orders.create.types.simple.total-amount') }}</span>
                            <strong data-field="order.amount">@{{ $admin.formatPrice(record.base_grand_total) }}</strong>
                            <small>@{{ record.total_qty_ordered ?? 0 }} {{ trans('admin::app.sales.orders.index.datagrid.items') }}</small>
                        </div>
                        <div class="msp-order-card__delivery">
                            <span class="msp-order-card__field-label">{{ trans('admin::app.sales.orders.view.shipping-method') }}</span>
                            <strong>@{{ record.shipping_title || record.shipping_description || record.shipping_method || '—' }}</strong>
                            <small v-if="record.shipment_tracking">@{{ record.shipment_tracking }}</small>
                            <small v-else-if="record.shipping_description">@{{ record.shipping_description }}</small>
                        </div>
                        <div class="msp-order-card__status" data-field="order.status">
                            <span class="msp-order-card__field-label">{{ trans('admin::executive-layout.status') }}</span>
                            <div v-html="record.status"></div>
                        </div>
                        <div class="msp-order-card__actions">
                            <button v-for="(action, actionIndex) in record.actions" :key="actionIndex" type="button" class="msp-review-button" @click="performAction(action)" :title="action.title" :aria-label="action.title" data-action="review-order">@{{ action.title }}</button>
                        </div>
                    </div>
                </article>
                <p v-if="! available.records.length" class="msp-empty">{{ trans('admin::executive-layout.no-records') }}</p>
            </template>
        </template>
    </x-admin::datagrid>
</div>
