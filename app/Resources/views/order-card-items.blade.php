<div class="msp-order-items">
    @forelse ($order->items as $item)
        <div class="msp-order-item">
            <div class="msp-order-item__image">
                @if ($item->product?->images?->count())
                    <img src="{{ $item->product->base_image_url }}" alt="{{ $item->name }}" loading="lazy">
                @else
                    <img src="{{ bagisto_asset('images/product-placeholders/front.svg') }}" alt="{{ trans('admin::app.sales.invoices.view.product-image') }}" loading="lazy">
                @endif
                <span>{{ (int) $item->qty_ordered }}</span>
            </div>
            <div class="msp-order-item__details">
                <strong>{{ $item->name }}</strong>
                <small>{{ trans('admin::app.catalog.products.index.datagrid.sku') }}: {{ $item->sku }}</small>
            </div>
        </div>
    @empty
        <span>—</span>
    @endforelse
</div>
