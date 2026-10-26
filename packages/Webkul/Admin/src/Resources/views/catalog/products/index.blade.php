<x-admin::layouts>
    <x-slot:title>{{ trans('admin::executive-layout.catalog') }}</x-slot:title>

    <section class="msp-page-header">
        <div>
            <p class="msp-eyebrow">{{ trans('admin::admin-navigation.store') }} / {{ trans('admin::executive-layout.catalog') }}</p>
            <h1>{{ trans('admin::executive-layout.catalog') }}</h1>
            <p>{{ trans('admin::executive-layout.catalog-info') }}</p>
        </div>
        <div class="msp-header-actions">
            <x-admin::datagrid.export :src="route('admin.catalog.products.index')" />
            @if (bouncer()->hasPermission('catalog.products.create'))
                <a href="{{ route('admin.catalog.products.create') }}" class="primary-button" data-action="create-product">+ {{ trans('admin::app.catalog.products.index.create-btn') }}</a>
            @endif
        </div>
    </section>

    @php
        $products->load('inventories');
        $catalogMetrics = [
            'total-skus' => $products->count(),
            'active-products' => $products->filter(fn ($product) => (bool) $product->status)->count(),
            'low-stock' => $products->filter(fn ($product) => $product->inventories->sum('qty') > 0 && $product->inventories->sum('qty') <= 10)->count(),
            'out-stock' => $products->filter(fn ($product) => $product->inventories->sum('qty') <= 0)->count(),
        ];
    @endphp

    <section class="msp-panel msp-catalog-snapshot">
        <h2 class="msp-eyebrow">{{ trans('admin::executive-layout.inventory-health') }}</h2>
        <div class="msp-kpi-grid msp-kpi-grid--four">
            @foreach ($catalogMetrics as $label => $value)
                <div class="msp-kpi {{ $label === 'out-stock' ? 'msp-kpi--urgent' : '' }}" data-entity="product">
                    <span>{{ trans('admin::executive-layout.'.$label) }}</span>
                    <strong data-field="catalog.{{ $label }}">{{ number_format($value) }}</strong>
                </div>
            @endforeach
        </div>
    </section>

    @include('manpleasure-admin::stitch-product-grid')

    @include('manpleasure-admin::product-pricing-modal')
</x-admin::layouts>
