<section class="mp-category-tree" data-category-tree data-revision="{{ $revision }}" data-delete-category="{{ request()->integer('delete_category') }}"
    @if (bouncer()->hasPermission('catalog.categories.edit'))
        data-move-url="{{ route('admin.catalog.categories.organize') }}"
    @endif
    @if (bouncer()->hasPermission('catalog.categories.delete'))
        data-delete-preview-url="{{ route('admin.catalog.categories.delete_preview') }}" data-delete-url="{{ route('admin.catalog.categories.delete_tree') }}"
    @endif
    @if (bouncer()->hasPermission('catalog.categories.edit') || bouncer()->hasPermission('catalog.categories.delete'))
        data-csrf="{{ csrf_token() }}"
    @endif
    v-pre>
    <div data-category-messages hidden
        @foreach (['saving', 'saved', 'error-generic', 'error-stale', 'error-cycle', 'error-root', 'error-scope', 'error-invalid', 'drop-before', 'drop-inside', 'drop-after', 'last', 'move-title', 'delete-title', 'delete-loading', 'delete-summary', 'delete-shared', 'delete-variants', 'delete-sample', 'delete-confirm', 'delete-confirm-products', 'delete-saving', 'error-delete-generic', 'delete-none', 'error-delete-password', 'error-delete-throttle'] as $key)
            data-{{ $key }}="{{ trans('admin::category-tree.'.$key) }}"
        @endforeach
    ></div>
    <div class="mp-category-toolbar">
        <div class="mp-category-search">
            <label for="mp-category-search">@lang('admin::category-tree.search')</label>
            <input
                id="mp-category-search"
                type="search"
                placeholder="@lang('admin::category-tree.search-placeholder')"
                autocomplete="off"
                data-category-search
                aria-describedby="mp-category-guidance"
            >
        </div>

        <div class="mp-category-tree-controls">
            <button type="button" data-category-expand-all>@lang('admin::category-tree.expand-all')</button>
            <button type="button" data-category-collapse-all>@lang('admin::category-tree.collapse-all')</button>
        </div>
    </div>

    <p id="mp-category-guidance" class="mp-category-guidance">@lang('admin::category-tree.guidance')</p>

    <p class="mp-category-guidance mp-category-storefront-key"><span aria-hidden="true">●</span> @lang('admin::category-tree.storefront-guidance')</p>
    @if (bouncer()->hasPermission('catalog.categories.edit'))
        <p class="mp-category-guidance">@lang('admin::category-tree.move-guidance')</p>
    @endif
    <p class="mp-category-feedback" data-category-feedback role="status" aria-live="polite"></p>

    <div class="mp-category-legend">
        <span>@lang('admin::category-tree.hierarchy')</span>
        <span class="mp-category-menu-heading">@lang('admin::category-tree.menu-visibility')</span>
    </div>

    <ul class="mp-category-roots" aria-label="@lang('admin::category-tree.hierarchy')">
        @foreach ($categories as $category)
            @include('admin::catalog.categories.tree-node', ['category' => $category])
        @endforeach
    </ul>

    <p class="mp-category-empty" data-category-empty @if ($categories->isNotEmpty()) hidden @endif>
        @lang('admin::category-tree.empty')
    </p>

    <p class="mp-category-result" data-category-result aria-live="polite" aria-atomic="true">
        <span data-category-count></span> @lang('admin::category-tree.shown')
    </p>
    @if (bouncer()->hasPermission('catalog.categories.edit'))
        <dialog class="mp-category-dialog" data-category-dialog aria-labelledby="mp-move-title">
            <form data-category-move-form>
                <h2 id="mp-move-title" data-category-move-title>@lang('admin::category-tree.move-title')</h2>
                <p>@lang('admin::category-tree.move-dialog-guidance')</p>
                <label for="mp-move-parent">@lang('admin::category-tree.move-parent')</label>
                <select id="mp-move-parent" name="parent_id" data-category-parent required></select>
                <label for="mp-move-before">@lang('admin::category-tree.move-before')</label>
                <select id="mp-move-before" name="before_id" data-category-before></select>
                <div class="mp-category-dialog-actions">
                    <button type="button" data-category-cancel>@lang('admin::category-tree.cancel')</button>
                    <button type="submit">@lang('admin::category-tree.save-move')</button>
                </div>
            </form>
        </dialog>
    @endif
    @if (bouncer()->hasPermission('catalog.categories.delete'))
        <dialog class="mp-category-dialog mp-category-delete-dialog" data-category-delete-dialog aria-labelledby="mp-delete-title">
            <form data-category-delete-form>
                <h2 id="mp-delete-title" data-category-delete-title>@lang('admin::category-tree.delete-title')</h2>
                <p data-category-delete-summary aria-live="polite"></p>
                <p class="mp-category-delete-warning">@lang('admin::category-tree.delete-branch-warning')</p>
                <details data-category-delete-details hidden>
                    <summary>@lang('admin::category-tree.delete-details')</summary>
                    <p data-category-delete-sample></p>
                    <strong>@lang('admin::category-tree.delete-categories')</strong>
                    <ul data-category-delete-categories></ul>
                    <strong>@lang('admin::category-tree.delete-products-list')</strong>
                    <ul data-category-delete-products-list></ul>
                </details>
                <div data-category-delete-choices hidden>
                    <p>@lang('admin::category-tree.delete-keep')</p>
                    <p data-category-delete-shared></p>
                    <p data-category-delete-variants></p>
                    <label class="mp-category-check">
                        <input type="checkbox" data-category-delete-products>
                        <span>@lang('admin::category-tree.delete-products')</span>
                    </label>
                    <p data-category-delete-permission hidden>@lang('admin::category-tree.delete-product-permission')</p>
                    <label class="mp-category-check mp-category-delete-warning" data-category-delete-ack-label hidden>
                        <input type="checkbox" data-category-delete-ack>
                        <span>@lang('admin::category-tree.delete-ack')</span>
                    </label>
                </div>
                <label for="mp-category-delete-approver">@lang('admin::category-tree.delete-approver')</label>
                <input type="email" id="mp-category-delete-approver" name="approver_email" data-category-delete-approver autocomplete="username" required maxlength="254">
                <label for="mp-category-delete-password">@lang('admin::category-tree.delete-password')</label>
                <input type="password" id="mp-category-delete-password" name="password" data-category-delete-password autocomplete="current-password" required maxlength="1024" aria-describedby="mp-delete-password-help">
                <p id="mp-delete-password-help">@lang('admin::category-tree.delete-password-help')</p>
                <p data-category-delete-error role="alert"></p>
                <div class="mp-category-dialog-actions">
                    <button type="button" data-category-delete-cancel>@lang('admin::category-tree.cancel')</button>
                    <button type="submit" class="mp-category-danger" data-category-delete-confirm disabled>@lang('admin::category-tree.delete-button')</button>
                </div>
            </form>
        </dialog>
    @endif
</section>

@include('admin::catalog.categories.tree-script')
