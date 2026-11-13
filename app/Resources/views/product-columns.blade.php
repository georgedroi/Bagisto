@php
    $columnOptions = collect(config('manpleasure-product-columns'))
        ->map(fn ($column, $key) => ['key' => $key, 'label' => trans($column['label']), 'default' => $column['default'], 'width' => $column['width']])
        ->values();
@endphp

@pushOnce('styles')
    <style>
        .mpc { position: relative; }
        .mpc-panel { position: absolute; inset-block-start: calc(100% + 6px); inset-inline-end: 0; z-index: 30; width: 280px; max-width: calc(100vw - 32px); padding: 14px; border: 1px solid #e2e8f0; border-radius: 10px; background: #fff; box-shadow: 0 12px 32px rgb(15 23 42 / 14%); }
        .mpc-title { margin: 0 0 4px; color: #0f172a; font-size: 13px; font-weight: 700; }
        .mpc-hint { margin: 0 0 10px; color: #64748b; font-size: 12px; }
        .mpc-list { display: flex; flex-direction: column; max-height: 340px; margin: 0; padding: 0; overflow: auto; list-style: none; }
        .mpc-list label { display: flex; align-items: center; gap: 10px; min-height: 44px; padding: 0 6px; border-radius: 6px; color: #334155; font-size: 13px; cursor: pointer; }
        .mpc-list label:hover { background: #f5f7ff; }
        .mpc-list input { width: 18px; height: 18px; accent-color: #3b5bf5; }
        .mpc-reset { margin-block-start: 10px; padding: 0; border: 0; background: transparent; color: #284be7; font-size: 12px; font-weight: 600; cursor: pointer; }
        .dark .mpc-panel { background: #101725; border-color: #263144; }
        .dark .mpc-title { color: #e2e8f0; }
        .dark .mpc-hint, .dark .mpc-list label { color: #cbd5e1; }
        .dark .mpc-list label:hover { background: #17213a; }
        .dark .mpc-reset { color: #a5b4fc; }
    </style>
@endPushOnce

<v-product-columns></v-product-columns>

@pushOnce('scripts')
    <script type="text/x-template" id="v-product-columns-template">
        <div class="mpc" ref="root" @focusout="blur">
            <button type="button" ref="trigger" class="secondary-button" :aria-expanded="open ? 'true' : 'false'" aria-controls="mpc-panel" @click="toggle" data-action="choose-columns">{{ trans('admin::product-columns.columns') }}</button>

            <div id="mpc-panel" ref="panel" class="mpc-panel" v-if="open" role="group" aria-labelledby="mpc-title">
                <p id="mpc-title" class="mpc-title">{{ trans('admin::product-columns.columns-title') }}</p>
                <p class="mpc-hint">{{ trans('admin::product-columns.columns-hint') }}</p>

                <ul class="mpc-list">
                    <li v-for="column in options" :key="column.key">
                        <label>
                            <input type="checkbox" :value="column.key" v-model="visible" @change="save">
                            <span>@{{ column.label }}</span>
                        </label>
                    </li>
                </ul>

                <button type="button" class="mpc-reset" @click="reset">{{ trans('admin::product-columns.columns-reset') }}</button>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-product-columns', {
            template: '#v-product-columns-template',

            data() {
                return {
                    open: false,
                    visible: [],
                    options: @js($columnOptions),
                    storageKey: 'msp.product.columns',
                };
            },

            created() {
                this.visible = this.stored() ?? this.defaults();
            },

            mounted() {
                this.apply();

                document.addEventListener('click', this.close);
                document.addEventListener('keydown', this.escape);
            },

            beforeUnmount() {
                document.removeEventListener('click', this.close);
                document.removeEventListener('keydown', this.escape);
            },

            methods: {
                toggle() {
                    this.open = ! this.open;

                    if (this.open) {
                        this.$nextTick(() => this.$refs.panel?.querySelector('input')?.focus());
                    }
                },

                defaults() {
                    return this.options.filter(column => column.default).map(column => column.key);
                },

                stored() {
                    try {
                        const saved = JSON.parse(localStorage.getItem(this.storageKey));
                        const known = this.options.map(column => column.key);

                        return Array.isArray(saved) ? saved.filter(key => known.includes(key)) : null;
                    } catch (error) {
                        return null;
                    }
                },

                save() {
                    try {
                        localStorage.setItem(this.storageKey, JSON.stringify(this.visible));
                    } catch (error) {
                    }

                    this.apply();
                },

                reset() {
                    this.visible = this.defaults();

                    this.save();
                },

                apply() {
                    const grid = document.querySelector('.msp-product-grid');

                    if (! grid) {
                        return;
                    }

                    const shown = this.options.filter(column => this.visible.includes(column.key));

                    grid.setAttribute('data-hidden', this.options.filter(column => ! this.visible.includes(column.key)).map(column => column.key).join(' '));
                    grid.style.setProperty('--msp-product-cols', ['20px', '2fr', ...shown.map(column => column.width), '1.3fr'].join(' '));
                    grid.style.setProperty('--msp-product-min', Math.max(920, 380 + shown.length * 125) + 'px');
                },

                close(event) {
                    if (this.open && ! this.$refs.root.contains(event.target)) {
                        this.open = false;
                    }
                },

                blur(event) {
                    if (! this.$refs.root.contains(event.relatedTarget)) {
                        this.open = false;
                    }
                },

                escape(event) {
                    if (this.open && event.key === 'Escape') {
                        this.open = false;
                        this.$refs.trigger.focus();
                    }
                },
            },
        });
    </script>
@endPushOnce
