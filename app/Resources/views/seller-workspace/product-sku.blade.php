<td class="msw-modal-info">
    <div class="msw-product-info">
        @if ($product->base_image)
            <img src="{{ Storage::url($product->base_image) }}" alt="">
        @else
            <i aria-hidden="true"></i>
        @endif
        <span title="{{ $product->sku }}">{{ trans('admin::seller-workspace.columns.sku') }}: {{ $product->sku }}</span>
        <button type="button" class="msw-copy" data-msw-copy="{{ $product->sku }}" data-msw-copied="{{ trans('admin::seller-workspace.editor.copied') }}" title="{{ trans('admin::seller-workspace.editor.copy') }}" aria-label="{{ trans('admin::seller-workspace.editor.copy') }}">
            @include('manpleasure-admin::seller-workspace.icon', ['name' => 'copy'])
        </button>
    </div>
    @if ($batchBadge ?? false)
        <small class="msw-preorder" data-msw-preorder hidden>{{ trans('admin::seller-workspace.editor.preorder') }}</small>
    @endif
</td>
