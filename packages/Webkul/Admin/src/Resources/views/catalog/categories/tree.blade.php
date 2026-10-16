<section class="mp-category-tree" data-category-tree v-pre>
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
</section>

@include('admin::catalog.categories.tree-script')
