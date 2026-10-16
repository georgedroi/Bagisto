@php
    $isChannelRoot = in_array((int) $category->id, $channelRootIds, true);
    $isStorefrontMain = (bool) $category->status && in_array((int) $category->parent_id, $channelRootIds, true);
    $isProtected = $isChannelRoot || $category->parent_id === null || (int) $category->id === 1;
@endphp
<li class="mp-category-node" data-category-node data-category-name="{{ $category->name }}"
    data-category-id="{{ $category->id }}" data-parent-id="{{ $category->parent_id }}" data-protected="{{ $isProtected ? 'true' : 'false' }}">

    <div class="mp-category-row {{ $isStorefrontMain ? 'is-storefront-main' : '' }}">
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

        @if (bouncer()->hasPermission('catalog.categories.edit') && ! $isProtected)
            <button type="button" class="mp-category-drag" draggable="true" data-category-drag
                aria-label="{{ trans('admin::category-tree.move', ['name' => $category->name]) }}" title="@lang('admin::category-tree.drag')">⠿</button>
        @endif

        <svg class="mp-category-folder" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M3 7a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v10H3Z" />
        </svg>

        <span class="mp-category-name">{{ $category->name }}</span>

        @if ($category->children->isNotEmpty())
            <span class="mp-category-child-count">{{ trans('admin::category-tree.children', ['count' => $category->children->count()]) }}</span>
        @endif

        @if ($isStorefrontMain)
            <span class="mp-category-storefront">@lang('admin::category-tree.storefront')</span>
        @endif

        <span class="mp-category-status {{ $category->status ? 'is-visible' : 'is-hidden' }}">
            @lang($category->status ? 'admin::category-tree.visible' : 'admin::category-tree.hidden')
        </span>

        @if (bouncer()->hasPermission('catalog.categories.edit'))
            @if (! $isProtected)
                <button type="button" class="mp-category-move" data-category-move aria-label="{{ trans('admin::category-tree.move', ['name' => $category->name]) }}">@lang('admin::category-tree.move-button')</button>
            @endif
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
