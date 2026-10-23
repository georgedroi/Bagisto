<div class="fixed bottom-12 left-4 right-4 z-50 flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white/95 p-3 shadow-lg backdrop-blur dark:border-gray-800 dark:bg-gray-900/95" style="position: fixed; bottom: 5rem; left: 1rem; right: 1rem; z-index: 50;" data-editor-actions>
    <p class="text-xs text-gray-500 dark:text-gray-400" data-editor-message>Native Bagisto form submission remains enabled.</p>
    <div class="flex gap-2">
        <button type="button" class="secondary-button" data-editor-action="draft">Save Draft</button>
        @if (! empty($product?->url_key))
            <a href="{{ route('shop.product_or_category.index', $product->url_key) }}" target="_blank" class="secondary-button">Preview</a>
        @else
            <button type="button" class="secondary-button" data-editor-action="preview">Preview</button>
        @endif
        <button type="button" class="primary-button" data-editor-action="publish">Publish / Submit</button>
    </div>
</div>