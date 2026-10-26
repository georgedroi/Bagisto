<x-admin::layouts>
    <x-slot:title>@lang('admin::product-entry.title')</x-slot>

    @pushOnce('styles')
    <style>
        .mpe-back { margin-block-end: 6px; font-size: 12px; }
        .mpe-back a { color: #475569; text-decoration: none; }
        .mpe-back a:hover { color: #0f172a; }
        .mpe-layout { display: grid; grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); gap: 20px; align-items: start; }
        .mpe-main, .mpe-side { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
        .mpe-layout .msp-panel { margin-block: 0; padding: 0; overflow: hidden; }
        .mpe-head { display: flex; align-items: center; gap: 10px; padding: 14px 20px; border-block-end: 1px solid #e2e8f0; background: #fcfdff; }
        .mpe-head h2 { margin: 0; font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #0f172a; }
        .mpe-head::before { content: ""; flex: none; width: 8px; height: 8px; border-radius: 2px; background: #3b5bf5; }
        .mpe-body { display: flex; flex-direction: column; gap: 14px; padding: 20px; }
        .mpe-row { display: flex; flex-wrap: wrap; gap: 14px; }
        .mpe-row > * { flex: 1 1 200px; min-width: 0; }
        .mpe-layout textarea, .mpe-layout .msp-form-field input[type="date"] { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 5px; background: #f3f6ff; color: #334155; font-size: 12px; }
        .mpe-mono, .mpe-mono input { font-family: ui-monospace, "JetBrains Mono", Consolas, monospace !important; font-variant-numeric: tabular-nums; }
        .mpe-hint { color: #64748b; font-size: 12px; line-height: 1.5; }
        .mpe-required { color: #b91c1c; }
        .mpe-divider { height: 1px; margin-block: 2px; background: #e2e8f0; }
        .mpe-picker-button { display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%; min-height: 44px; padding: 0 12px; border: 1px solid #e2e8f0; border-radius: 5px; background: #f3f6ff; color: #334155; font-size: 13px; text-align: start; }
        .mpe-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
        .mpe-chip { min-height: 36px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 999px; background: #fff; color: #334155; font-size: 12px; font-weight: 500; }
        .mpe-chip[aria-pressed="true"] { border-color: #3b5bf5; background: #eef1ff; color: #2947dc; }
        .mpe-info { display: flex; flex-wrap: wrap; gap: 8px 24px; padding: 12px 14px; border-radius: 8px; background: #eef6f1; color: #0f5132; font-size: 12px; }
        .mpe-picker { display: grid; grid-template-columns: 1fr 1fr; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .mpe-picker-column { display: flex; flex-direction: column; gap: 2px; min-height: 260px; padding: 6px; }
        .mpe-picker-column + .mpe-picker-column { border-inline-start: 1px solid #e2e8f0; }
        .mpe-picker-item { min-height: 44px; padding: 0 12px; border: 1px solid transparent; border-radius: 6px; background: transparent; color: #334155; font-size: 13px; text-align: start; }
        .mpe-picker-item[aria-pressed="true"] { border-color: #3b5bf5; background: #eef1ff; color: #2947dc; font-weight: 600; }
        .mpe-picker-item[disabled] { color: #94a3b8; }
        .mpe-bar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; font-size: 12px; color: #475569; }
        .mpe-images { display: flex; flex-wrap: wrap; gap: 12px; }
        .mpe-tile { position: relative; display: flex; flex-direction: column; justify-content: flex-end; width: 120px; height: 120px; border: 1px solid #cbd5e1; border-radius: 10px; background: #e2e8f0 center / cover no-repeat; overflow: hidden; }
        .mpe-tile--main { border: 2px solid #3b5bf5; }
        .mpe-tile-badge { position: absolute; top: 6px; inset-inline-start: 6px; padding: 1px 8px; border-radius: 999px; background: #3b5bf5; color: #fff; font-size: 11px; font-weight: 600; }
        .mpe-tile-actions { display: flex; justify-content: space-between; padding: 4px; background: rgb(15 23 42 / 70%); }
        .mpe-tile-actions button { min-height: 32px; padding: 0 8px; border: 0; border-radius: 4px; background: transparent; color: #fff; font-size: 11px; }
        .mpe-add { width: 120px; height: 120px; border: 2px dashed #94a3b8; border-radius: 10px; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; text-align: center; }
        .mpe-warn { color: #92400e; font-size: 12px; }
        .mpe-check { display: flex; align-items: center; gap: 10px; min-height: 44px; font-size: 13px; font-weight: 500; }
        .mpe-check input { width: 20px; height: 20px; accent-color: #3b5bf5; }
        .mpe-deal { display: flex; flex-direction: column; gap: 10px; padding: 14px; border: 1px solid #bfd0ff; border-radius: 8px; background: #f5f8ff; }
        .mpe-segment { display: inline-flex; padding: 2px; border: 1px solid #e2e8f0; border-radius: 8px; background: #f1f5f9; }
        .mpe-segment button { min-height: 32px; padding: 0 12px; border: 1px solid transparent; border-radius: 6px; background: transparent; color: #475569; font-size: 12px; font-weight: 600; }
        .mpe-segment button[aria-pressed="true"] { border-color: #e2e8f0; background: #fff; color: #2544d6; }
        .mpe-view { padding: 14px; border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; }
        .mpe-view-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-block-end: 10px; }
        .mpe-view-title { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #475569; }
        .mpe-badge { padding: 3px 10px; border-radius: 999px; background: #fee2e2; color: #b91c1c; font-size: 11px; font-weight: 700; }
        .mpe-badge--muted { background: #f1f5f9; color: #475569; }
        .mpe-price-row { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
        .mpe-final { font-size: 22px; font-weight: 700; }
        .mpe-final--deal { color: #b91c1c; }
        .mpe-was { color: #64748b; text-decoration: line-through; }
        .mpe-save { padding: 3px 10px; border: 1px solid #fecaca; border-radius: 999px; background: #fef2f2; color: #b91c1c; font-size: 12px; font-weight: 700; }
        .mpe-margin { margin-inline-start: auto; padding: 4px 10px; border: 1px solid #bfd0ff; background: #f5f8ff; color: #2544d6; font-size: 12px; font-weight: 600; }
        .mpe-phone { width: 250px; margin-inline: auto; border: 8px solid #0f172a; border-radius: 28px; background: #fff; overflow: hidden; }
        .mpe-phone-image { position: relative; height: 200px; background: #e2e8f0 center / cover no-repeat; }
        .mpe-phone-badge { position: absolute; top: 10px; padding: 2px 10px; border-radius: 999px; color: #fff; font-size: 11px; font-weight: 700; }
        .mpe-phone-body { display: flex; flex-direction: column; gap: 6px; padding: 14px; }
        .mpe-phone-body button { margin-block-start: 6px; min-height: 44px; border: 0; border-radius: 6px; background: #3b5bf5; color: #fff; font-weight: 600; }
        .mpe-meter { height: 8px; border-radius: 99px; background: #e2e8f0; overflow: hidden; }
        .mpe-meter > span { display: block; height: 100%; background: #3b5bf5; }
        .mpe-list { display: flex; flex-direction: column; gap: 8px; margin: 12px 0 0; padding: 0; list-style: none; font-size: 13px; }
        .mpe-list li { display: flex; align-items: baseline; gap: 10px; }
        .mpe-dot { flex: none; width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1; }
        .mpe-dot--done { background: #15803d; }
        .mpe-audit { display: grid; grid-template-columns: auto 1fr; gap: 6px 14px; margin: 0; font-size: 12px; }
        .mpe-audit dt { color: #64748b; }
        .mpe-audit dd { margin: 0; text-align: end; font-weight: 600; word-break: break-word; }
        @media (max-width: 1023px) { .mpe-layout { grid-template-columns: minmax(0, 1fr); } }
        @media (max-width: 479px) { .mpe-picker { grid-template-columns: minmax(0, 1fr); } .mpe-picker-column + .mpe-picker-column { border-inline-start: 0; border-block-start: 1px solid #e2e8f0; } .mpe-margin { margin-inline-start: 0; } }
    </style>
    @endPushOnce

    <v-product-entry></v-product-entry>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-product-entry-template">
            <div class="mpe-page">
                <p class="mpe-back"><a :href="entry.urls.back">← @lang('admin::product-entry.back')</a></p>

                <div class="msp-page-header">
                    <div>
                        <p class="msp-eyebrow">@lang('admin::product-entry.eyebrow')</p>
                        <h1>@lang('admin::product-entry.title')</h1>
                        <p>@lang('admin::product-entry.subtitle')</p>
                    </div>
                    <div class="msp-header-actions">
                        <a :href="entry.urls.back" class="secondary-button">@lang('admin::product-entry.discard')</a>
                        <button type="button" class="secondary-button" :disabled="busy" @click="submit(0)">@lang('admin::product-entry.save-draft')</button>
                        <button type="button" class="primary-button" :disabled="busy" :aria-busy="busy" @click="submit(1)">@lang('admin::product-entry.save-publish')</button>
                    </div>
                </div>

                <p v-if="message" role="alert" class="msp-error">@{{ message }}</p>

                <div class="mpe-layout">
                    <div class="mpe-main">

                        <section class="msp-panel">
                            <div class="mpe-head"><h2>@lang('admin::product-entry.section-category')</h2></div>
                            <div class="mpe-body">
                                <div class="msp-form-field">
                                    <label for="mpe-category">@lang('admin::product-entry.category-label') <span class="mpe-required">*</span></label>
                                    <button type="button" id="mpe-category" class="mpe-picker-button" @click="openPicker">
                                        <span>@{{ selected ? selected.main.name + ' › ' + selected.name : @js(trans('admin::product-entry.choose-category')) }}</span>
                                        <span aria-hidden="true">▾</span>
                                    </button>
                                    <small class="msp-error" v-for="error in errors.category_id">@{{ error }}</small>
                                    <div class="mpe-chips" v-if="recentCategories.length">
                                        <span class="mpe-hint">@lang('admin::product-entry.recently-used')</span>
                                        <button type="button" class="mpe-chip" v-for="category in recentCategories" :key="category.id" :aria-pressed="selected && selected.id === category.id" @click="requestCategory(category)">@{{ category.name }}</button>
                                    </div>
                                    <span class="mpe-hint">@lang('admin::product-entry.picker-hint')</span>
                                </div>
                                <div class="mpe-info" v-if="family">
                                    <span><strong>@lang('admin::product-entry.family'):</strong> @{{ family.name }}</span>
                                </div>
                                <div class="msp-form-field" v-if="family" style="max-width: 360px">
                                    <label for="mpe-type">@lang('admin::product-entry.product-type') <span class="mpe-required">*</span></label>
                                    <select id="mpe-type" v-model="form.mp_product_type">
                                        <option value="">@lang('admin::product-entry.select')</option>
                                        <option v-for="type in entry.productTypes" :key="type.value" :value="type.value">@{{ type.label }}</option>
                                    </select>
                                    <small class="msp-error" v-for="error in errors.mp_product_type">@{{ error }}</small>
                                </div>

                                <div class="mpe-divider"></div>

                                <div class="mpe-bar">
                                    <span>@lang('admin::product-entry.images-hint')</span>
                                    <span class="mpe-mono">@{{ imagesCount }}</span>
                                </div>
                                <div class="mpe-images">
                                    <div class="mpe-tile" :class="{ 'mpe-tile--main': index === 0 }" v-for="(image, index) in images" :key="image.id" :style="{ backgroundImage: 'url(' + image.url + ')' }">
                                        <span class="mpe-tile-badge" v-if="index === 0">@lang('admin::product-entry.main-image')</span>
                                        <div class="mpe-tile-actions">
                                            <button type="button" :disabled="index === 0" :aria-label="@js(trans('admin::product-entry.move-earlier')) + ' ' + (index + 1)" @click="moveImage(index)">‹</button>
                                            <button type="button" :aria-label="@js(trans('admin::product-entry.remove-image')) + ' ' + (index + 1)" @click="removeImage(index)">×</button>
                                        </div>
                                    </div>
                                    <label class="mpe-add" for="mpe-files">+ @lang('admin::product-entry.add-image')</label>
                                    <input id="mpe-files" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple hidden @change="pickImages">
                                </div>
                                <p class="mpe-warn" v-for="warning in imageWarnings">@{{ warning }}</p>
                                <small class="msp-error" v-for="error in imageErrors">@{{ error }}</small>
                            </div>
                        </section>

                        <section class="msp-panel">
                            <div class="mpe-head"><h2>@lang('admin::product-entry.section-basic')</h2></div>
                            <div class="mpe-body">
                                <div class="msp-form-field">
                                    <label for="mpe-name">@lang('admin::product-entry.name') <span class="mpe-required">*</span></label>
                                    <input id="mpe-name" type="text" v-model="form.name" maxlength="255" @input="onName">
                                    <small class="msp-error" v-for="error in errors.name">@{{ error }}</small>
                                </div>
                                <div class="mpe-row">
                                    <div class="msp-form-field mpe-mono">
                                        <label for="mpe-sku">@lang('admin::product-entry.sku') <span class="mpe-required">*</span></label>
                                        <input id="mpe-sku" type="text" v-model="form.sku" maxlength="40" @input="skuTouched = true">
                                        <span class="mpe-hint">@lang('admin::product-entry.sku-hint')</span>
                                        <small class="msp-error" v-for="error in errors.sku">@{{ error }}</small>
                                    </div>
                                    <div class="msp-form-field mpe-mono">
                                        <label for="mpe-url">@lang('admin::product-entry.url-key') <span class="mpe-required">*</span></label>
                                        <input id="mpe-url" type="text" v-model="form.url_key" maxlength="100" @input="urlTouched = true">
                                        <span class="mpe-hint">@lang('admin::product-entry.url-key-hint')</span>
                                        <small class="msp-error" v-for="error in errors.url_key">@{{ error }}</small>
                                    </div>
                                </div>
                                <div class="msp-form-field">
                                    <label for="mpe-short">@lang('admin::product-entry.short-description') <span class="mpe-required">*</span></label>
                                    <textarea id="mpe-short" rows="2" maxlength="255" v-model="form.short_description"></textarea>
                                    <span class="mpe-hint">@{{ countHint(form.short_description, 255) }}</span>
                                    <small class="msp-error" v-for="error in errors.short_description">@{{ error }}</small>
                                </div>
                                <div class="msp-form-field">
                                    <label for="mpe-long">@lang('admin::product-entry.full-description') <span class="mpe-required">*</span></label>
                                    <textarea id="mpe-long" rows="5" v-model="form.description"></textarea>
                                    <span class="mpe-hint">@{{ form.description.trim().length }}</span>
                                    <small class="msp-error" v-for="error in errors.description">@{{ error }}</small>
                                </div>
                            </div>
                        </section>

                        <section class="msp-panel" v-if="family">
                            <div class="mpe-head"><h2>@lang('admin::product-entry.section-specs')</h2></div>
                            <div class="mpe-body">
                                <p class="mpe-hint">@{{ detailsHint }}</p>
                                <div class="msp-form-grid">
                                    <div class="msp-form-field" v-for="field in fields" :key="field.code" :class="{ 'msp-form-field--wide': field.type === 'textarea' }">
                                        <label v-if="field.type !== 'boolean'" :for="'mpe-f-' + field.code">@{{ field.label }} <span class="mpe-required" v-if="field.required">*</span></label>
                                        <select v-if="field.type === 'select'" :id="'mpe-f-' + field.code" v-model="values[field.code]">
                                            <option value="">@lang('admin::product-entry.select')</option>
                                            <option v-for="option in field.options" :key="option.value" :value="option.value">@{{ option.label }}</option>
                                        </select>
                                        <textarea v-else-if="field.type === 'textarea'" :id="'mpe-f-' + field.code" rows="3" v-model="values[field.code]"></textarea>
                                        <label v-else-if="field.type === 'boolean'" class="mpe-check"><input type="checkbox" v-model="values[field.code]">@{{ field.label }}</label>
                                        <input v-else-if="field.type === 'date'" :id="'mpe-f-' + field.code" type="date" v-model="values[field.code]">
                                        <input v-else :id="'mpe-f-' + field.code" type="text" :inputmode="entry.numeric.includes(field.code) ? 'decimal' : 'text'" v-model="values[field.code]">
                                        <small class="msp-error" v-for="error in errors[field.code]">@{{ error }}</small>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="msp-panel">
                            <div class="mpe-head"><h2>@lang('admin::product-entry.section-pricing')</h2></div>
                            <div class="mpe-body">
                                <div class="mpe-row">
                                    <div class="msp-form-field mpe-mono">
                                        <label for="mpe-price">@lang('admin::product-pricing.retail-price') (@{{ entry.currency }}) <span class="mpe-required">*</span></label>
                                        <input id="mpe-price" type="text" inputmode="decimal" v-model="form.price">
                                        <span class="mpe-hint" v-if="usd(form.price)">@{{ usdHint(form.price) }}</span>
                                        <small class="msp-error" v-for="error in errors.price">@{{ error }}</small>
                                    </div>
                                    <div class="msp-form-field mpe-mono" v-if="hasCost">
                                        <label for="mpe-cost">@lang('admin::product-pricing.cost-label') (@{{ entry.currency }})</label>
                                        <input id="mpe-cost" type="text" inputmode="decimal" v-model="form.cost">
                                        <span class="mpe-hint">@lang('admin::product-pricing.cost-hint')</span>
                                        <small class="msp-error" v-for="error in errors.cost">@{{ error }}</small>
                                    </div>
                                </div>

                                <div class="mpe-deal">
                                    <label class="mpe-check" style="min-height: 32px"><input type="checkbox" v-model="form.dealEnabled">@lang('admin::product-pricing.enable-deal')</label>
                                    <template v-if="form.dealEnabled">
                                        <div class="msp-form-field mpe-mono" style="max-width: 320px">
                                            <label for="mpe-special">@lang('admin::product-pricing.daily-price') (@{{ entry.currency }})</label>
                                            <input id="mpe-special" type="text" inputmode="decimal" v-model="form.special_price">
                                            <span class="mpe-hint" v-if="usd(form.special_price)">@{{ usdHint(form.special_price) }}</span>
                                            <small class="msp-error" v-for="error in errors.special_price">@{{ error }}</small>
                                        </div>
                                        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px">
                                            <strong style="font-size: 12px">@lang('admin::product-pricing.deal-validity')</strong>
                                            <div class="mpe-segment" role="group" aria-label="@lang('admin::product-pricing.deal-validity')">
                                                <button type="button" :aria-pressed="form.validity === 'forever'" @click="form.validity = 'forever'">@lang('admin::product-pricing.forever')</button>
                                                <button type="button" :aria-pressed="form.validity === 'dates'" @click="form.validity = 'dates'">@lang('admin::product-pricing.valid-dates')</button>
                                            </div>
                                        </div>
                                        <div class="mpe-row" v-if="form.validity === 'dates'">
                                            <div class="msp-form-field">
                                                <label for="mpe-from">@lang('admin::product-pricing.starts')</label>
                                                <input id="mpe-from" type="date" v-model="form.special_price_from">
                                                <small class="msp-error" v-for="error in errors.special_price_from">@{{ error }}</small>
                                            </div>
                                            <div class="msp-form-field">
                                                <label for="mpe-to">@lang('admin::product-pricing.ends')</label>
                                                <input id="mpe-to" type="date" v-model="form.special_price_to">
                                                <small class="msp-error" v-for="error in errors.special_price_to">@{{ error }}</small>
                                            </div>
                                        </div>
                                        <span class="mpe-hint" v-if="form.validity === 'dates'">@lang('admin::product-pricing.dates-hint')</span>
                                    </template>
                                </div>

                                <div class="mpe-view">
                                    <div class="mpe-view-head">
                                        <span class="mpe-view-title">@lang('admin::product-pricing.preview-title')</span>
                                        <span class="mpe-badge" :class="{ 'mpe-badge--muted': dealState !== 'active' }">@{{ dealLabel }}</span>
                                    </div>
                                    <div class="mpe-price-row mpe-mono">
                                        <span class="mpe-final" :class="{ 'mpe-final--deal': onSale }">@{{ entry.currency }} @{{ money(onSale ? form.special_price : form.price) }}</span>
                                        <span class="mpe-was" v-if="onSale">@{{ entry.currency }} @{{ money(form.price) }}</span>
                                        <span class="mpe-save" v-if="onSale">@{{ saveLabel }}</span>
                                        <span class="mpe-margin" v-if="marginText">@{{ marginText }}</span>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="msp-panel">
                            <div class="mpe-head"><h2>@lang('admin::product-entry.section-inventory')</h2></div>
                            <div class="mpe-body">
                                <div class="mpe-row">
                                    <div class="msp-form-field mpe-mono">
                                        <label for="mpe-stock">@lang('admin::product-entry.stock') <span class="mpe-required">*</span></label>
                                        <input id="mpe-stock" type="text" inputmode="numeric" v-model="form.stock">
                                        <small class="msp-error" v-for="error in errors.stock">@{{ error }}</small>
                                    </div>
                                    <div class="msp-form-field mpe-mono">
                                        <label for="mpe-weight">@lang('admin::product-entry.weight') <span class="mpe-required">*</span></label>
                                        <input id="mpe-weight" type="text" inputmode="decimal" v-model="form.weight">
                                        <span class="mpe-hint">@lang('admin::product-entry.weight-hint')</span>
                                        <small class="msp-error" v-for="error in errors.weight">@{{ error }}</small>
                                    </div>
                                </div>
                            </div>
                        </section>

                    </div>

                    <aside class="mpe-side">
                        <section class="msp-panel">
                            <div class="mpe-head"><h2>@lang('admin::product-entry.section-status')</h2></div>
                            <div class="mpe-body">
                                <div>
                                    <span class="mpe-hint">@lang('admin::product-entry.status-label')</span>
                                    <p style="margin: 4px 0 0"><span class="mpe-badge mpe-badge--muted">@lang('admin::product-entry.status-draft')</span></p>
                                    <p class="mpe-hint" style="margin-block-start: 6px">@lang('admin::product-entry.status-hint')</p>
                                </div>
                                <label class="mpe-check"><input type="checkbox" v-model="form.new">@lang('admin::product-entry.mark-new')</label>
                                <label class="mpe-check"><input type="checkbox" v-model="form.featured">@lang('admin::product-entry.featured')</label>
                            </div>
                        </section>

                        <section class="msp-panel">
                            <div class="mpe-head"><h2>@lang('admin::product-entry.preview-title')</h2></div>
                            <div class="mpe-body">
                                <div class="mpe-phone">
                                    <div class="mpe-phone-image" :style="images.length ? { backgroundImage: 'url(' + images[0].url + ')' } : {}">
                                        <span class="mpe-phone-badge" style="inset-inline-start: 10px; background: #0f172a" v-if="form.new">@lang('admin::product-entry.badge-new')</span>
                                        <span class="mpe-phone-badge" style="inset-inline-end: 10px; background: #3b5bf5" v-if="onSale">@lang('admin::product-entry.badge-sale')</span>
                                    </div>
                                    <div class="mpe-phone-body">
                                        <span class="mpe-hint">@{{ selected ? selected.name : '' }}</span>
                                        <strong>@{{ form.name.trim() || @js(trans('admin::product-entry.preview-placeholder')) }}</strong>
                                        <span>
                                            <strong>@{{ entry.currency }} @{{ money(onSale ? form.special_price : form.price) }}</strong>
                                            <s class="mpe-hint" v-if="onSale">@{{ entry.currency }} @{{ money(form.price) }}</s>
                                        </span>
                                        <span class="mpe-hint" v-if="onSale">@{{ saveLabel }}</span>
                                        <button type="button" tabindex="-1">@lang('admin::product-entry.add-to-cart')</button>
                                    </div>
                                </div>
                                <p class="mpe-hint" style="text-align: center">@lang('admin::product-entry.preview-hint')</p>
                            </div>
                        </section>

                        <section class="msp-panel">
                            <div class="mpe-head"><h2>@lang('admin::product-entry.checklist-title')</h2></div>
                            <div class="mpe-body" style="gap: 6px">
                                <div class="mpe-meter" aria-hidden="true"><span :style="{ width: fillRate + '%' }"></span></div>
                                <p class="mpe-hint">@lang('admin::product-entry.fill-rate'): @{{ fillRate }}%</p>
                                <ul class="mpe-list">
                                    <li v-for="item in checklist" :key="item.label"><span class="mpe-dot" :class="{ 'mpe-dot--done': item.done }"></span><span>@{{ item.label }}</span></li>
                                </ul>
                            </div>
                        </section>

                        <section class="msp-panel">
                            <div class="mpe-head"><h2>@lang('admin::product-entry.section-audit')</h2></div>
                            <div class="mpe-body">
                                <dl class="mpe-audit">
                                    <dt>@lang('admin::product-entry.created-by')</dt><dd>@{{ entry.admin }}</dd>
                                    <dt>@lang('admin::product-entry.family')</dt><dd>@{{ family ? family.name : '—' }}</dd>
                                    <dt>@lang('admin::product-entry.sku')</dt><dd class="mpe-mono">@{{ form.sku || '—' }}</dd>
                                    <dt>@lang('admin::product-entry.status-label')</dt><dd>@lang('admin::product-entry.status-draft')</dd>
                                </dl>
                            </div>
                        </section>
                    </aside>
                </div>

                <x-admin::modal ref="pickerModal">
                    <x-slot:header>
                        <p class="text-lg font-bold text-gray-800 dark:text-white">@lang('admin::product-entry.picker-title')</p>
                    </x-slot>

                    <x-slot:content>
                        <div class="msp-form-field" style="margin-block-end: 12px">
                            <label for="mpe-search">@lang('admin::product-entry.picker-search')</label>
                            <input id="mpe-search" type="search" v-model="pickerSearch" autocomplete="off">
                        </div>
                        <div class="mpe-chips" style="margin-block-end: 12px" v-if="recentCategories.length">
                            <span class="mpe-hint">@lang('admin::product-entry.recently-used')</span>
                            <button type="button" class="mpe-chip" v-for="category in recentCategories" :key="category.id" :aria-pressed="pickerPending && pickerPending.id === category.id" @click="pickerPending = category; pickerMain = category.main.id">@{{ category.name }}</button>
                        </div>
                        <div class="mpe-picker">
                            <div class="mpe-picker-column">
                                <button type="button" class="mpe-picker-item" v-for="main in pickerMains" :key="main.id" :aria-pressed="pickerMain === main.id" :disabled="! main.family" @click="pickerMain = main.id">@{{ main.name }}<small v-if="! main.family"> · @lang('admin::product-entry.picker-no-family')</small></button>
                            </div>
                            <div class="mpe-picker-column">
                                <button type="button" class="mpe-picker-item" v-for="sub in pickerSubs" :key="sub.id" :aria-pressed="pickerPending && pickerPending.id === sub.id" @click="pickerPending = sub">@{{ sub.name }}</button>
                                <p class="mpe-hint" v-if="! pickerSubs.length" style="padding: 12px">@lang('admin::product-entry.picker-empty')</p>
                            </div>
                        </div>
                        <p class="mpe-hint" style="margin-block-start: 12px"><strong>@lang('admin::product-entry.picker-current'):</strong> @{{ pickerPending ? pickerPending.main.name + ' › ' + pickerPending.name : '—' }}</p>
                    </x-slot>

                    <x-slot:footer>
                        <button type="button" class="secondary-button" style="margin-inline-end: 8px" @click="$refs.pickerModal.close()">@lang('admin::product-entry.picker-cancel')</button>
                        <button type="button" class="primary-button" :disabled="! pickerPending" @click="confirmPicker">@lang('admin::product-entry.picker-confirm')</button>
                    </x-slot>
                </x-admin::modal>
            </div>
        </script>

        <script type="module">
            app.component('v-product-entry', {
                template: '#v-product-entry-template',

                data() {
                    return {
                        entry: @json($entry),

                        form: {
                            category_id: null,
                            mp_product_type: '',
                            name: '',
                            sku: '',
                            url_key: '',
                            price: '',
                            cost: '',
                            dealEnabled: false,
                            validity: 'forever',
                            special_price: '',
                            special_price_from: '',
                            special_price_to: '',
                            stock: '',
                            weight: '',
                            new: false,
                            featured: false,
                            short_description: '',
                            description: '',
                        },

                        values: {},
                        images: [],
                        imageWarnings: [],
                        imageErrors: [],
                        errors: {},
                        message: '',
                        busy: false,
                        skuTouched: false,
                        urlTouched: false,
                        pickerSearch: '',
                        pickerMain: null,
                        pickerPending: null,
                        recentIds: [],
                    };
                },

                created() {
                    try {
                        this.recentIds = JSON.parse(localStorage.getItem('mpe-recent') || '[]');
                    } catch (error) {
                        this.recentIds = [];
                    }
                },

                computed: {
                    subCategories() {
                        return this.entry.categories.flatMap(main => main.children.map(sub => ({ ...sub, main })));
                    },

                    selected() {
                        return this.subCategories.find(sub => sub.id === this.form.category_id) || null;
                    },

                    recentCategories() {
                        return this.recentIds
                            .map(id => this.subCategories.find(sub => sub.id === id))
                            .filter(sub => sub && sub.main.family)
                            .slice(0, 5);
                    },

                    family() {
                        return this.selected ? this.entry.families[this.selected.main.family] || null : null;
                    },

                    fields() {
                        return this.family ? this.family.fields : [];
                    },

                    hasCost() {
                        return this.family ? this.family.has_cost : true;
                    },

                    imagesCount() {
                        return @js(trans('admin::product-entry.images-count')).replace(':count', this.images.length).replace(':max', 10);
                    },

                    detailsHint() {
                        return @js(trans('admin::product-entry.details-hint')).replace(':family', this.family ? this.family.name : '');
                    },

                    pickerMains() {
                        const term = this.pickerSearch.trim().toLowerCase();

                        if (! term) {
                            return this.entry.categories;
                        }

                        return this.entry.categories.filter(main => main.children.some(sub => sub.name.toLowerCase().includes(term)));
                    },

                    pickerSubs() {
                        const term = this.pickerSearch.trim().toLowerCase();
                        const main = this.entry.categories.find(item => item.id === this.pickerMain);

                        if (! main) {
                            return [];
                        }

                        return main.children
                            .filter(sub => ! term || sub.name.toLowerCase().includes(term))
                            .map(sub => ({ ...sub, main }));
                    },

                    retail() {
                        return parseFloat(this.form.price);
                    },

                    deal() {
                        return parseFloat(this.form.special_price);
                    },

                    dealState() {
                        if (! (this.form.dealEnabled && this.deal > 0 && this.deal < this.retail)) {
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

                    onSale() {
                        return this.dealState === 'active';
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
                            .replace(':amount', this.entry.currency + ' ' + saved.toFixed(2));
                    },

                    marginText() {
                        const cost = parseFloat(this.form.cost);
                        const price = this.onSale ? this.deal : this.retail;

                        if (! this.hasCost || ! (cost > 0) || ! (price > 0)) {
                            return '';
                        }

                        return @js(trans('admin::product-pricing.margin')) + ': ' + ((price - cost) / price * 100).toFixed(1) + '%';
                    },

                    checklist() {
                        const filled = value => String(value ?? '').trim() !== '';
                        const required = this.fields.filter(field => field.required);

                        return [
                            { label: @js(trans('admin::product-entry.check-category')), done: !! this.selected && filled(this.form.mp_product_type) },
                            { label: @js(trans('admin::product-entry.check-identifiers')), done: filled(this.form.name) && filled(this.form.sku) && filled(this.form.url_key) },
                            { label: @js(trans('admin::product-entry.check-price')), done: filled(this.form.price) && filled(this.form.stock) && filled(this.form.weight) },
                            { label: @js(trans('admin::product-entry.check-required')), done: required.every(field => field.type === 'boolean' ? true : filled(this.values[field.code])) },
                            { label: @js(trans('admin::product-entry.check-description')), done: this.form.short_description.trim().length >= 20 && this.form.description.trim().length >= 60 },
                            { label: @js(trans('admin::product-entry.check-image')), done: this.images.length > 0 },
                        ];
                    },

                    fillRate() {
                        const filled = value => String(value ?? '').trim() !== '';
                        const base = [this.selected, this.form.mp_product_type, this.form.name, this.form.price, this.form.stock, this.form.weight, this.form.short_description, this.form.description, this.images.length ? 'yes' : ''];
                        const own = this.fields.map(field => field.type === 'boolean' ? (this.values[field.code] ? 'yes' : '') : this.values[field.code]);
                        const all = base.concat(own);

                        return Math.round(all.filter(value => filled(value)).length / all.length * 100);
                    },
                },

                methods: {
                    slugify(text) {
                        return text.toLowerCase()
                            .normalize('NFKD')
                            .replace(/[̀-ͯ]/g, '')
                            .replace(/&/g, ' and ')
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-+|-+$/g, '')
                            .slice(0, 100);
                    },

                    money(value) {
                        const number = parseFloat(value);

                        return isNaN(number) ? '0.00' : number.toFixed(2);
                    },

                    usd(value) {
                        const number = parseFloat(value);

                        return number > 0 ? (number / this.entry.rate).toFixed(2) : '';
                    },

                    usdHint(value) {
                        return @js(trans('admin::product-entry.usd-approx')).replace(':amount', this.usd(value)).replace(':currency', this.entry.currency).replace(':rate', this.entry.rate.toFixed(2));
                    },

                    countHint(text, max) {
                        return @js(trans('admin::product-entry.characters')).replace(':count', text.trim().length).replace(':max', max);
                    },

                    hasValue(value) {
                        return value !== undefined && value !== null && value !== '' && value !== false;
                    },

                    onName() {
                        if (! this.urlTouched) {
                            this.form.url_key = this.slugify(this.form.name);
                        }
                    },

                    openPicker() {
                        this.pickerSearch = '';
                        this.pickerPending = this.selected;
                        this.pickerMain = this.selected ? this.selected.main.id : (this.entry.categories.find(main => main.family) || {}).id;
                        this.$refs.pickerModal.open();
                    },

                    confirmPicker() {
                        const target = this.pickerPending;

                        this.$refs.pickerModal.close();

                        if (target) {
                            this.requestCategory(target);
                        }
                    },

                    requestCategory(target) {
                        if (! this.selected || this.selected.id === target.id || this.selected.main.family === target.main.family) {
                            this.applyCategory(target);

                            return;
                        }

                        const next = this.entry.families[target.main.family].fields;
                        const hidden = this.fields.filter(field => this.hasValue(this.values[field.code]) && ! next.some(item => item.code === field.code)).map(field => field.label);
                        const added = next.filter(item => ! this.fields.some(field => field.code === item.code)).map(item => item.label);
                        const parts = [@js(trans('admin::product-entry.change-kept'))];

                        if (hidden.length) {
                            parts.push(@js(trans('admin::product-entry.change-hidden')).replace(':fields', hidden.join(', ')));
                        }

                        if (added.length) {
                            parts.push(@js(trans('admin::product-entry.change-new')).replace(':fields', added.join(', ')));
                        }

                        parts.push(@js(trans('admin::product-entry.change-final')));

                        this.$emitter.emit('open-confirm-modal', {
                            title: @js(trans('admin::product-entry.change-title')).replace(':category', target.main.name + ' › ' + target.name),
                            message: parts.join(' '),
                            options: {
                                btnDisagree: @js(trans('admin::product-entry.change-keep')).replace(':category', this.selected.name),
                                btnAgree: @js(trans('admin::product-entry.change-confirm')),
                            },
                            agree: () => this.applyCategory(target),
                        });
                    },

                    applyCategory(target) {
                        this.form.category_id = target.id;
                        this.errors = {};

                        const defaultType = this.entry.productTypes.find(type => type.label === target.type);

                        if (defaultType) {
                            this.form.mp_product_type = defaultType.value;
                        }

                        this.recentIds = [target.id].concat(this.recentIds.filter(id => id !== target.id)).slice(0, 5);

                        try {
                            localStorage.setItem('mpe-recent', JSON.stringify(this.recentIds));
                        } catch (error) {
                            this.recentIds = this.recentIds.slice();
                        }

                        if (! this.skuTouched) {
                            this.suggestSku();
                        }
                    },

                    async suggestSku() {
                        try {
                            const response = await this.$axios.get(this.entry.urls.sku, { params: { category_id: this.form.category_id } });

                            if (response.data.sku && ! this.skuTouched) {
                                this.form.sku = response.data.sku;
                            }
                        } catch (error) {
                            this.form.sku = this.form.sku || '';
                        }
                    },

                    pickImages(event) {
                        const warnings = [];

                        Array.from(event.target.files).forEach(file => {
                            if (this.images.length >= 10) {
                                return;
                            }

                            if (! /^image\/(jpeg|png|webp)$/.test(file.type)) {
                                warnings.push(file.name + ': ' + @js(trans('admin::product-entry.image-type')));

                                return;
                            }

                            const image = { id: Date.now() + '-' + Math.random(), file, url: URL.createObjectURL(file) };

                            if (file.size > 5 * 1048576) {
                                warnings.push(file.name + ': ' + @js(trans('admin::product-entry.image-size')));
                            }

                            const probe = new Image();

                            probe.onload = () => {
                                const ratio = probe.naturalWidth / probe.naturalHeight;

                                if (Math.min(probe.naturalWidth, probe.naturalHeight) < 800) {
                                    this.imageWarnings.push(file.name + ': ' + @js(trans('admin::product-entry.image-small')));
                                }

                                if (ratio < 0.85 || ratio > 1.18) {
                                    this.imageWarnings.push(file.name + ': ' + @js(trans('admin::product-entry.image-square')));
                                }
                            };

                            probe.src = image.url;
                            this.images.push(image);
                        });

                        this.imageWarnings = warnings;
                        event.target.value = '';
                    },

                    moveImage(index) {
                        if (index > 0) {
                            const moved = this.images.splice(index, 1)[0];

                            this.images.splice(index - 1, 0, moved);
                        }
                    },

                    removeImage(index) {
                        this.images.splice(index, 1);
                    },

                    escapeHtml(text) {
                        return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    },

                    toHtml(text) {
                        return text.trim().split(/\n\s*\n/).map(paragraph => '<p>' + this.escapeHtml(paragraph.trim()).replace(/\n/g, '<br>') + '</p>').join('');
                    },

                    buildPayload(status) {
                        const data = new FormData();
                        const add = (key, value) => {
                            if (value !== undefined && value !== null && value !== '') {
                                data.append(key, value);
                            }
                        };
                        const dated = this.form.dealEnabled && this.form.validity === 'dates';

                        add('category_id', this.form.category_id);
                        add('mp_product_type', this.form.mp_product_type);
                        add('name', this.form.name.trim());
                        add('sku', this.form.sku.trim());
                        add('url_key', this.form.url_key.trim());
                        add('price', this.form.price);
                        add('cost', this.hasCost ? this.form.cost : '');
                        add('special_price', this.form.dealEnabled ? this.form.special_price : '');
                        add('special_price_from', dated ? this.form.special_price_from : '');
                        add('special_price_to', dated ? this.form.special_price_to : '');
                        add('stock', this.form.stock);
                        add('weight', this.form.weight);
                        add('short_description', this.form.short_description.trim());
                        add('description', this.form.description.trim() ? this.toHtml(this.form.description) : '');
                        add('status', status);

                        if (this.form.new) {
                            add('new', 1);
                        }

                        if (this.form.featured) {
                            add('featured', 1);
                        }

                        this.fields.forEach(field => {
                            const value = this.values[field.code];

                            if (field.type === 'boolean') {
                                if (value) {
                                    add(field.code, 1);
                                }
                            } else {
                                add(field.code, typeof value === 'string' ? value.trim() : value);
                            }
                        });

                        this.images.forEach((image, index) => data.append('images[files][image_' + index + ']', image.file));

                        return data;
                    },

                    async submit(status) {
                        if (this.busy) {
                            return;
                        }

                        this.busy = true;
                        this.errors = {};
                        this.imageErrors = [];
                        this.message = '';

                        try {
                            const response = await this.$axios.post(this.entry.urls.store, this.buildPayload(status));

                            window.location.assign(response.data.data.redirect_url);
                        } catch (error) {
                            const errors = error.response?.data?.errors ?? {};

                            this.errors = errors;
                            this.imageErrors = Object.keys(errors).filter(key => key.startsWith('images.')).flatMap(key => errors[key]);
                            this.message = error.response?.data?.message ?? @js(trans('admin::product-entry.save-failed'));
                            this.$emitter.emit('add-flash', { type: 'error', message: @js(trans('admin::product-entry.save-failed')) });
                        } finally {
                            this.busy = false;
                        }
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
