<x-admin::layouts>
    <x-slot:title>{{ trans('admin::executive-layout.setup') }}</x-slot:title>
    <v-msp-product-setup></v-msp-product-setup>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-msp-product-setup-template">
            <form @submit.prevent="submit" data-entity="product">
                @csrf
                <div class="msp-page-header">
                    <div>
                        <p class="msp-eyebrow">{{ trans('admin::executive-layout.catalog') }} / {{ trans('admin::executive-layout.setup') }}</p>
                        <h1>{{ trans('admin::executive-layout.setup') }}</h1>
                        <p>{{ trans('admin::executive-layout.setup-info') }}</p>
                    </div>
                    <div class="msp-header-actions">
                        <a href="{{ route('admin.catalog.products.index') }}" class="secondary-button">{{ trans('admin::executive-layout.back') }}</a>
                        <button type="submit" class="primary-button" :disabled="busy" :aria-busy="busy" data-action="create-product">{{ trans('admin::executive-layout.continue') }}</button>
                    </div>
                </div>
                <div class="msp-product-setup-layout">
                    <section class="msp-panel">
                        <h2>{{ trans('admin::executive-layout.basic') }}</h2>
                        <p v-if="message" role="alert" class="msp-error">@{{ message }}</p>
                        <div class="msp-form-grid">
                            <div class="msp-form-field">
                                <label for="msp-product-type">{{ trans('admin::executive-layout.type') }}</label>
                                <select id="msp-product-type" v-model="form.type" @change="resetVariants" required name="type" data-field="product.type">
                                    @foreach (config('product_types') as $productType)
                                        <option value="{{ $productType['key'] }}">{{ trans($productType['name']) }}</option>
                                    @endforeach
                                </select>
                                <small class="msp-error" v-for="error in errors.type">@{{ error }}</small>
                            </div>
                            <div class="msp-form-field">
                                <label for="msp-product-family">{{ trans('admin::executive-layout.family') }}</label>
                                <select id="msp-product-family" v-model="form.attribute_family_id" @change="resetVariants" required name="attribute_family_id">
                                    @foreach ($families as $family)
                                        <option value="{{ $family->id }}">{{ $family->name }}</option>
                                    @endforeach
                                </select>
                                <small class="msp-error" v-for="error in errors.attribute_family_id">@{{ error }}</small>
                            </div>
                            <div class="msp-form-field msp-form-field--wide">
                                <label for="msp-product-sku">{{ trans('admin::executive-layout.sku') }}</label>
                                <input id="msp-product-sku" v-model="form.sku" required name="sku" data-field="product.sku" autocomplete="off">
                                <small class="msp-error" v-for="error in errors.sku">@{{ error }}</small>
                            </div>
                        </div>
                        <div v-if="attributes.length" class="msp-form-grid">
                            <h3 class="msp-form-field--wide">{{ trans('admin::executive-layout.variants') }}</h3>
                            <div class="msp-form-field" v-for="attribute in attributes" :key="attribute.id">
                                <label :for="'msp-variant-' + attribute.id">@{{ attribute.name }}</label>
                                <select :id="'msp-variant-' + attribute.id" multiple v-model="variants[attribute.code]" required>
                                    <option v-for="option in attribute.options" :key="option.id" :value="option.id">@{{ option.name }}</option>
                                </select>
                                <small class="msp-error" v-for="error in errors['super_attributes.' + attribute.code]">@{{ error }}</small>
                            </div>
                        </div>
                    </section>
                    <aside class="msp-panel msp-setup-steps">
                        <strong>{{ trans('admin::executive-layout.step-one') }}</strong>
                        <p>{{ trans('admin::executive-layout.step-two') }}</p>
                        <p>{{ trans('admin::executive-layout.setup-info') }}</p>
                    </aside>
                </div>
            </form>
        </script>
        <script type="module">
            app.component('v-msp-product-setup', {
                template: '#v-msp-product-setup-template',

                data() {
                    return {
                        form: {
                            type: 'simple',
                            attribute_family_id: @js((string) ($families->first()?->id ?? '')),
                            sku: '',
                        },
                        attributes: [],
                        variants: {},
                        errors: {},
                        message: '',
                        busy: false,
                    };
                },

                methods: {
                    resetVariants() {
                        this.attributes = [];
                        this.variants = {};
                        this.errors = {};
                    },

                    async submit() {
                        if (this.busy) {
                            return;
                        }

                        this.busy = true;
                        this.errors = {};
                        this.message = '';

                        const payload = { ...this.form };

                        if (this.attributes.length) {
                            payload.super_attributes = this.variants;
                        }

                        try {
                            const response = await this.$axios.post(@js(route('admin.catalog.products.store')), payload);
                            const result = response.data.data;

                            if (result.redirect_url) {
                                window.location.assign(result.redirect_url);
                            } else if (result.attributes) {
                                this.attributes = result.attributes;
                                this.variants = Object.fromEntries(this.attributes.map(attribute => [attribute.code, []]));
                            }
                        } catch (error) {
                            this.errors = error.response?.data?.errors ?? {};
                            this.message = error.response?.data?.message ?? @js(trans('admin::executive-layout.save-error'));
                        } finally {
                            this.busy = false;
                        }
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
