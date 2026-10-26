<div class="sticky bottom-0 z-30 mt-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-800 dark:bg-gray-900" data-editor-actions>
    <p class="text-xs text-gray-500 dark:text-gray-400" data-editor-message>@lang('admin::product-editor.hint')</p>

    <div class="flex gap-2">
        <button type="button" class="secondary-button" data-editor-action="draft">@lang('admin::product-editor.draft')</button>

        @if (! empty($product?->url_key))
            <a href="{{ route('shop.product_or_category.index', $product->url_key) }}" target="_blank" class="secondary-button">@lang('admin::product-editor.preview')</a>
        @else
            <button type="button" class="secondary-button" data-editor-action="preview">@lang('admin::product-editor.preview')</button>
        @endif

        <button type="button" class="primary-button" data-editor-action="publish">@lang('admin::product-editor.publish')</button>
    </div>
</div>

@pushOnce('scripts')
    <script type="module">
        const actions = document.querySelector('[data-editor-actions]');
        const form = actions?.closest('form');

        if (actions && form) {
            const message = actions.querySelector('[data-editor-message]');

            const setStatus = (value) => {
                form.querySelectorAll('[name="status"], [name="status[]"]').forEach((control) => {
                    if (control.type === 'checkbox' || control.type === 'radio') {
                        control.checked = value === '1';
                    } else {
                        control.value = value;
                    }
                });
            };

            actions.querySelector('[data-editor-action="draft"]')?.addEventListener('click', () => {
                setStatus('0');
                message.textContent = @js(trans('admin::product-editor.saving-draft'));
                form.requestSubmit();
            });

            actions.querySelector('[data-editor-action="publish"]')?.addEventListener('click', () => {
                setStatus('1');
                message.textContent = @js(trans('admin::product-editor.publishing'));
                form.requestSubmit();
            });

            actions.querySelector('[data-editor-action="preview"]')?.addEventListener('click', () => {
                message.textContent = @js(trans('admin::product-editor.save-first'));
            });
        }
    </script>
@endPushOnce
