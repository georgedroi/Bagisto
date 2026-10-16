<li class="mp-category-node" data-category-node data-category-name="{{ $category->name }}">
    <div class="mp-category-row">
        @if ($category->children->isNotEmpty())
            <button
                type="button"
                class="mp-category-toggle"
                aria-expanded="true"
                aria-controls="mp-category-children-{{ $category->id }}"
                aria-label="{{ trans('admin::category-tree.toggle', ['name' => $category->name]) }}"
                data-category-toggle
            >
                <svg viewBox="0 0 20 20" aria-hidden="true"><path d="m6 8 4 4 4-4" /></svg>
            </button>
        @else
            <span class="mp-category-gutter" aria-hidden="true"></span>
        @endif

        <svg class="mp-category-folder" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10H3Z" />
        </svg>

        <span class="mp-category-name">{{ $category->name }}</span>

        @if ($category->children->isNotEmpty())
            <span class="mp-category-child-count">{{ trans('admin::category-tree.children', ['count' => $category->children->count()]) }}</span>
        @endif

        <span class="mp-category-status {{ $category->status ? 'is-visible' : 'is-hidden' }}">
            @lang($category->status ? 'admin::category-tree.visible' : 'admin::category-tree.hidden')
        </span>

        @if (bouncer()->hasPermission('catalog.categories.edit'))
            <a
                class="mp-category-edit"
                href="{{ route('admin.catalog.categories.edit', $category->id) }}"
                aria-label="{{ trans('admin::category-tree.edit', ['name' => $category->name]) }}"
            >
                @lang('admin::app.catalog.categories.index.datagrid.edit')
            </a>
        @endif
    </div>

    @if ($category->children->isNotEmpty())
        <ul class="mp-category-children" id="mp-category-children-{{ $category->id }}" data-category-children>
            @foreach ($category->children as $child)
                @include('admin::catalog.categories.tree-node', ['category' => $child])
            @endforeach
        </ul>
    @endif
</li>
