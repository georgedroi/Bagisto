<style>
    .mpp-summary { display: flex; align-items: center; gap: 14px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; }
    .mpp-thumb { flex: none; width: 56px; height: 56px; border: 1px solid #e2e8f0; border-radius: 6px; background: #fff center / cover no-repeat; }
    .mpp-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-block-end: 4px; }
    .mpp-sku { padding: 2px 8px; border-radius: 4px; background: #dbe6fe; color: #1e38c7; font-family: ui-monospace, "JetBrains Mono", Consolas, monospace; font-size: 12px; font-weight: 600; }
    .mpp-stock { display: inline-flex; align-items: center; gap: 6px; padding: 2px 8px; border: 1px solid #a7f3d0; border-radius: 4px; background: #ecfdf5; color: #065f46; font-size: 11px; font-weight: 600; }
    .mpp-stock::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentcolor; }
    .mpp-stock.is-low { border-color: #fde68a; background: #fffbeb; color: #92400e; }
    .mpp-stock.is-out { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
    .mpp-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-block: 16px; }
    .mpp-card { display: flex; flex-direction: column; gap: 8px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
    .mpp-card--deal { border-color: #bfd0ff; background: #f5f8ff; }
    .mpp-card label { font-size: 12px; font-weight: 600; }
    .mpp-money { display: flex; align-items: center; gap: 6px; padding-inline: 10px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; }
    .mpp-money input { flex: 1; min-width: 0; min-height: 40px; border: 0; background: transparent; font-size: 14px; font-weight: 600; font-variant-numeric: tabular-nums; }
    .mpp-money--deal { border-color: #fecaca; background: #fff; color: #b91c1c; }
    .mpp-hint { color: #64748b; font-size: 11px; line-height: 1.5; }
    .mpp-check { display: flex; align-items: center; gap: 8px; min-height: 32px; font-size: 12px; font-weight: 600; color: #2544d6; }
    .mpp-check input { width: 16px; height: 16px; accent-color: #3b5bf5; }
    .mpp-validity { display: flex; flex-direction: column; gap: 12px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fafbfd; }
    .mpp-segment { display: inline-flex; padding: 2px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f1f5f9; }
    .mpp-segment button { min-height: 32px; padding: 0 12px; border: 1px solid transparent; border-radius: 6px; background: transparent; color: #475569; font-size: 12px; font-weight: 600; }
    .mpp-segment button[aria-pressed="true"] { border-color: #e2e8f0; background: #fff; color: #2544d6; }
    .mpp-dates { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .mpp-dates input, .mpp-card > input { width: 100%; min-height: 40px; padding: 0 10px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; font-size: 13px; }
    .mpp-preview { padding: 14px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; }
    .mpp-preview-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-block-end: 12px; }
    .mpp-preview-title { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #475569; }
    .mpp-badge { padding: 3px 10px; border-radius: 999px; background: #fee2e2; color: #b91c1c; font-size: 11px; font-weight: 700; }
    .mpp-badge--muted { background: #f1f5f9; color: #475569; }
    .mpp-price-row { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
    .mpp-final { font-size: 26px; font-weight: 700; font-variant-numeric: tabular-nums; }
    .mpp-final--deal { color: #b91c1c; }
    .mpp-was { color: #64748b; text-decoration: line-through; font-variant-numeric: tabular-nums; }
    .mpp-save { padding: 3px 10px; border: 1px solid #fecaca; border-radius: 999px; background: #fef2f2; color: #b91c1c; font-size: 12px; font-weight: 700; }
    .mpp-margin { margin-inline-start: auto; padding: 4px 10px; border: 1px solid #bfd0ff; background: #f5f8ff; color: #2544d6; font-size: 12px; font-weight: 600; }
    @media (max-width: 639px) { .mpp-grid, .mpp-dates { grid-template-columns: minmax(0, 1fr); } .mpp-margin { margin-inline-start: 0; } }
</style>

<v-product-pricing></v-product-pricing>

@pushOnce('scripts')
    <script type="text/x-template" id="v-product-pricing-template">
        <x-admin::modal ref="pricingModal">
            <x-slot:header>
                <p class="text-lg font-bold text-gray-800 dark:text-white">@lang('admin::product-pricing.modal-title')</p>
            </x-slot>

            <x-slot:content>
                <p class="mpp-hint" v-if="loading" role="status">@lang('admin::product-pricing.loading')</p>
                <p class="msp-error" v-else-if="error" role="alert">@{{ error }}</p>

                <div v-else-if="product">
                    <div class="mpp-summary">
                        <div class="mpp-thumb" :style="product.image ? { backgroundImage: 'url(' + product.image + ')' } : {}"></div>
                        <div style="min-width: 0">
                            <div class="mpp-chips">
                                <span class="mpp-sku">@{{ product.sku }}</span>
                                <span class="mpp-stock" :class="{ 'is-low': product.stock > 0 && product.stock <= 10, 'is-out': product.stock <= 0 }">@{{ stockLabel }}</span>
                            </div>
                            <strong style="display: block">@{{ product.name }}</strong>
                            <span class="mpp-hint" v-if="product.has_cost_field">@{{ costLine }}</span>
                        </div>
                    </div>

                    <div class="mpp-grid">
                        <div class="mpp-card">
                            <label for="mpp-price">@lang('admin::product-pricing.retail-price')</label>
                            <div class="mpp-money"><span>@{{ product.currency }}</span><input id="mpp-price" type="text" inputmode="decimal" v-model="form.price"></div>
                            <small class="msp-error" v-for="message in errors.price">@{{ message }}</small>
                            <span class="mpp-hint">@lang('admin::product-pricing.retail-hint')</span>

                            <template v-if="product.has_cost_field">
                                <label for="mpp-cost" style="margin-block-start: 6px">@lang('admin::product-pricing.cost-label')</label>
                                <div class="mpp-money"><span>@{{ product.currency }}</span><input id="mpp-cost" type="text" inputmode="decimal" v-model="form.cost"></div>
                                <small class="msp-error" v-for="message in errors.cost">@{{ message }}</small>
                                <span class="mpp-hint">@lang('admin::product-pricing.cost-hint')</span>
                            </template>
                            <span class="mpp-hint" v-else>@lang('admin::product-pricing.cost-missing')</span>
                        </div>

                        <div class="mpp-card mpp-card--deal">
                            <label for="mpp-deal">@lang('admin::product-pricing.daily-price')</label>
                            <label class="mpp-check"><input type="checkbox" v-model="form.enabled">@lang('admin::product-pricing.enable-deal')</label>
                            <div class="mpp-money mpp-money--deal"><span>@{{ product.currency }}</span><input id="mpp-deal" type="text" inputmode="decimal" v-model="form.special_price" :disabled="! form.enabled"></div>
                            <small class="msp-error" v-for="message in errors.special_price">@{{ message }}</small>
                            <span class="mpp-hint">@lang('admin::product-pricing.deal-hint')</span>
                        </div>
                    </div>

                    <div class="mpp-validity" v-if="form.enabled">
                        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px">
                            <strong style="font-size: 12px">@lang('admin::product-pricing.deal-validity')</strong>
                            <div class="mpp-segment" role="group" aria-label="@lang('admin::product-pricing.deal-validity')">
                                <button type="button" :aria-pressed="form.validity === 'forever'" @click="form.validity = 'forever'">@lang('admin::product-pricing.forever')</button>
                                <button type="button" :aria-pressed="form.validity === 'dates'" @click="form.validity = 'dates'">@lang('admin::product-pricing.valid-dates')</button>
                            </div>
                        </div>
                        <div class="mpp-dates" v-if="form.validity === 'dates'">
                            <div class="msp-form-field">
                                <label for="mpp-from">@lang('admin::product-pricing.starts')</label>
                                <input id="mpp-from" type="date" v-model="form.special_price_from">
                                <small class="msp-error" v-for="message in errors.special_price_from">@{{ message }}</small>
                            </div>
                            <div class="msp-form-field">
                                <label for="mpp-to">@lang('admin::product-pricing.ends')</label>
                                <input id="mpp-to" type="date" v-model="form.special_price_to">
                                <small class="msp-error" v-for="message in errors.special_price_to">@{{ message }}</small>
                            </div>
                        </div>
                        <span class="mpp-hint" v-if="form.validity === 'dates'">@lang('admin::product-pricing.dates-hint')</span>
                    </div>

                    <div class="mpp-preview" style="margin-block-start: 16px">
                        <div class="mpp-preview-head">
                            <span class="mpp-preview-title">@lang('admin::product-pricing.preview-title')</span>
                            <span class="mpp-badge" :class="{ 'mpp-badge--muted': dealState !== 'active' }">@{{ dealLabel }}</span>
                        </div>
                        <div class="mpp-price-row">
                            <span class="mpp-final" :class="{ 'mpp-final--deal': dealOn }">@{{ product.currency }} @{{ money(dealOn ? form.special_price : form.price) }}</span>
                            <span class="mpp-was" v-if="dealOn">@{{ product.currency }} @{{ money(form.price) }}</span>
                            <span class="mpp-save" v-if="dealOn">@{{ saveLabel }}</span>
                            <span class="mpp-margin" v-if="marginText">@{{ marginText }}</span>
                        </div>
                    </div>
                </div>
            </x-slot>

            <x-slot:footer>
                <a v-if="product && product.storefront_url" :href="product.storefront_url" target="_blank" rel="noopener" class="transparent-button" style="margin-inline-end: auto">@lang('admin::product-pricing.view-storefront')</a>
                <button type="button" class="secondary-button" style="margin-inline-end: 8px" @click="$refs.pricingModal.close()">@lang('admin::product-pricing.cancel')</button>
                <button type="button" class="primary-button" :disabled="! product || busy" :aria-busy="busy" @click="save">@lang('admin::product-pricing.save-pricing')</button>
            </x-slot>
        </x-admin::modal>
    </script>

    <script type="module">
        app.component('v-product-pricing', {
            template: '#v-product-pricing-template',

            data() {
                return {
                    product: null,
                    form: { price: '', cost: '', enabled: false, special_price: '', validity: 'forever', special_price_from: '', special_price_to: '' },
                    errors: {},
                    error: '',
                    loading: false,
                    busy: false,
                    grid: null,
                    showUrl: @js(route('admin.catalog.product_pricing.show', ['id' => 0])),
                    updateUrl: @js(route('admin.catalog.product_pricing.update', ['id' => 0])),
                };
            },

            created() {
                this.$emitter.on('open-product-pricing', this.open);
                this.$emitter.on('change-datagrid', payload => { this.grid = payload.available; });
            },

            computed: {
                retail() {
                    return parseFloat(this.form.price);
                },

                deal() {
                    return parseFloat(this.form.special_price);
                },

                dealOn() {
                    return this.form.enabled && this.deal > 0 && this.deal < this.retail && this.dealState === 'active';
                },

                dealState() {
                    if (! (this.form.enabled && this.deal > 0 && this.deal < this.retail)) {
                        return 'off';
                    }

                    if (this.form.validity !== 'dates') {
                        return 'active';
                    }

                    const today = new Date().toISOString().slice(0, 10);

                    if (this.form.special_price_from && today < this.form.special_price_from) {
                        return 'scheduled';
                    }

                    if (this.form.special_price_to && today > this.form.special_price_to) {
                        return 'expired';
                    }

                    return 'active';
                },

                dealLabel() {
                    if (this.dealState === 'active') {
                        return @js(trans('admin::product-pricing.deal-active')) + ' · ' + (this.form.validity === 'dates' && this.form.special_price_to
                            ? @js(trans('admin::product-pricing.valid-until')).replace(':date', this.form.special_price_to)
                            : @js(trans('admin::product-pricing.no-end-date')));
                    }

                    if (this.dealState === 'scheduled') {
                        return @js(trans('admin::product-pricing.deal-scheduled')).replace(':date', this.form.special_price_from);
                    }

                    return this.dealState === 'expired'
                        ? @js(trans('admin::product-pricing.deal-expired'))
                        : @js(trans('admin::product-pricing.deal-off'));
                },

                saveLabel() {
                    const saved = this.retail - this.deal;

                    return @js(trans('admin::product-pricing.save-percent'))
                        .replace(':percent', Math.round(saved / this.retail * 100))
                        .replace(':amount', this.product.currency + ' ' + saved.toFixed(2));
                },

                marginText() {
                    const cost = parseFloat(this.form.cost);
                    const price = this.dealOn ? this.deal : this.retail;

                    if (! (cost > 0) || ! (price > 0)) {
                        return '';
                    }

                    return @js(trans('admin::product-pricing.margin')) + ': ' + ((price - cost) / price * 100).toFixed(1) + '%';
                },

                stockLabel() {
                    if (this.product.stock <= 0) {
                        return @js(trans('admin::product-pricing.stock-out'));
                    }

                    return (this.product.stock <= 10 ? @js(trans('admin::product-pricing.stock-low')) : @js(trans('admin::product-pricing.stock-in'))) + ' · ' + this.product.stock;
                },

                costLine() {
                    const cost = parseFloat(this.form.cost);

                    return @js(trans('admin::product-pricing.internal-cost')).replace(':cost', cost > 0 ? this.product.currency + ' ' + cost.toFixed(2) : '—');
                },
            },

            methods: {
                money(value) {
                    const number = parseFloat(value);

                    return isNaN(number) ? '0.00' : number.toFixed(2);
                },

                async open(id) {
                    this.product = null;
                    this.errors = {};
                    this.error = '';
                    this.loading = true;
                    this.$refs.pricingModal.open();

                    try {
                        const response = await this.$axios.get(this.showUrl.replace(/0$/, id));
                        const product = response.data.data;

                        this.product = product;
                        this.form = {
                            price: product.price ?? '',
                            cost: product.cost ?? '',
                            enabled: product.special_price !== null,
                            special_price: product.special_price ?? '',
                            validity: product.special_price_from || product.special_price_to ? 'dates' : 'forever',
                            special_price_from: product.special_price_from ?? '',
                            special_price_to: product.special_price_to ?? '',
                        };
                    } catch (error) {
                        this.error = error.response?.data?.message ?? @js(trans('admin::product-pricing.load-failed'));
                    } finally {
                        this.loading = false;
                    }
                },

                async save() {
                    if (this.busy || ! this.product) {
                        return;
                    }

                    this.busy = true;
                    this.errors = {};

                    const dated = this.form.enabled && this.form.validity === 'dates';
                    const payload = {
                        price: this.form.price,
                        special_price: this.form.enabled ? this.form.special_price : null,
                        special_price_from: dated ? this.form.special_price_from || null : null,
                        special_price_to: dated ? this.form.special_price_to || null : null,
                    };

                    if (this.product.has_cost_field) {
                        payload.cost = this.form.cost === '' ? null : this.form.cost;
                    }

                    try {
                        const response = await this.$axios.put(this.updateUrl.replace(/0$/, this.product.id), payload);
                        const row = this.grid?.records.find(record => record.product_id === this.product.id);

                        if (row) {
                            row.price = response.data.data.price;
                        }

                        this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                        this.$refs.pricingModal.close();
                    } catch (error) {
                        this.errors = error.response?.data?.errors ?? {};
                        this.$emitter.emit('add-flash', { type: 'error', message: error.response?.data?.message ?? @js(trans('admin::product-pricing.save-failed')) });
                    } finally {
                        this.busy = false;
                    }
                },
            },
        });
    </script>
@endPushOnce
