<section class="mp-category-tree" data-category-tree data-revision="{{ $revision }}"
    @if (bouncer()->hasPermission('catalog.categories.edit'))
        data-move-url="{{ route('admin.catalog.categories.organize') }}" data-csrf="{{ csrf_token() }}"
    @endif
    v-pre>
    <div data-category-messages hidden
        @foreach (['saving', 'saved', 'error-generic', 'error-stale', 'error-cycle', 'error-root', 'error-scope', 'error-invalid', 'drop-before', 'drop-inside', 'drop-after', 'last', 'move-title'] as $key)
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
</section>

@include('admin::catalog.categories.tree-script')
