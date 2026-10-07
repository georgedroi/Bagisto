@php
    $sections = [
        ['name' => 'Basic Information', 'description' => 'Name, SKU, URL key, status, and visibility.'],
        ['name' => 'Product Images', 'description' => 'Primary image, supporting images, and variant media.'],
        ['name' => 'Product Video', 'description' => 'Optional feature video with controlled media validation.'],
        ['name' => 'Category', 'description' => 'Primary merchandising category and additional categories.'],
        ['name' => 'Description', 'description' => 'Short description, full description, and care guidance.'],
        ['name' => 'Specifications', 'description' => 'ManPleasure family attributes and filterable product details.'],
        ['name' => 'Variations', 'description' => 'Configurable attributes, variant SKUs, prices, and inventory.'],
        ['name' => 'Pricing', 'description' => 'Price, special pricing, and customer-group pricing.'],
        ['name' => 'Inventory', 'description' => 'Source quantities, stock management, and availability.'],
        ['name' => 'Shipping', 'description' => 'Weight, dimensions, and fulfilment details.'],
        ['name' => 'SEO', 'description' => 'Meta title, description, URL key, and image alt text.'],
        ['name' => 'Status', 'description' => 'Publication status, storefront visibility, and guest checkout.'],
    ];
@endphp

<div class="box-shadow mb-3.5 rounded bg-white p-4 dark:bg-gray-900" data-editor-blueprint>
    <div class="mb-4 flex items-start justify-between gap-4">
        <div>
            <p class="text-base font-semibold text-gray-800 dark:text-white">ManPleasure Product Editor Blueprint</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Grouped workflow over Bagisto's native product fields and persistence.</p>
        </div>
        <span class="rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Phase 4</span>
    </div>

    <div class="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($sections as $section)
            <button type="button" class="rounded border border-gray-200 p-3 text-left transition hover:border-blue-400 hover:bg-blue-50 focus:border-blue-500 focus:outline-none dark:border-gray-800 dark:hover:bg-gray-800" data-editor-section="{{ $section['name'] }}">
                <span class="block text-sm font-semibold text-gray-800 dark:text-white">{{ $section['name'] }}</span>
                <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $section['description'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="mt-3 flex items-center justify-between rounded border border-dashed border-gray-300 p-3 dark:border-gray-700" data-media-picker>
        <span class="text-xs text-gray-500 dark:text-gray-400" data-media-selection>No media selected. Use Media Bank to choose reusable assets.</span>
        <a href="{{ route('admin.media.index') }}" target="_blank" class="secondary-button">Open Media Bank</a>
    </div>
</div>

<div class="sticky bottom-4 z-10 mt-3 flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white/95 p-3 shadow-lg backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
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

@pushOnce('scripts')
    <script type="module">
        const blueprint = document.querySelector('[data-editor-blueprint]');

        if (blueprint) {
            const form = blueprint.closest('form');
            const message = blueprint.parentElement.querySelector('[data-editor-message]');

            const setStatus = (value) => {
                const controls = form?.querySelectorAll('[name="status"], [name="status[]"]') || [];
                controls.forEach((control) => {
                    control.checked = control.type === 'checkbox' || control.type === 'radio' ? value === '1' : control.checked;
                    if (control.type !== 'checkbox' && control.type !== 'radio') control.value = value;
                });
            };

            blueprint.querySelectorAll('[data-editor-section]').forEach((button) => {
                button.addEventListener('click', () => {
                    const target = button.dataset.editorSection.toLowerCase();
                    const heading = [...document.querySelectorAll('p')].find((node) => node.textContent.trim().toLowerCase() === target);
                    if (heading) heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            blueprint.parentElement.querySelector('[data-editor-action="draft"]')?.addEventListener('click', () => {
                setStatus('0');
                if (message) message.textContent = 'Saving this product as a draft…';
                form?.requestSubmit();
            });

            blueprint.parentElement.querySelector('[data-editor-action="publish"]')?.addEventListener('click', () => {
                setStatus('1');
                if (message) message.textContent = 'Publishing this product…';
                form?.requestSubmit();
            });

            blueprint.parentElement.querySelector('[data-editor-action="preview"]')?.addEventListener('click', () => {
                if (message) message.textContent = 'Save the product before previewing it.';
            });
        }
    </script>
@endPushOnce
