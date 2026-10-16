<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.categories.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <h1 class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.catalog.categories.index.title')
        </h1>

        <div class="flex items-center gap-x-2.5">
            {!! view_render_event('bagisto.admin.catalog.categories.index.create-button.before') !!}

            @if (bouncer()->hasPermission('catalog.categories.create'))
                <a class="primary-button" href="{{ route('admin.catalog.categories.create') }}">
                    @lang('admin::app.catalog.categories.index.add-btn')
                </a>
            @endif

            {!! view_render_event('bagisto.admin.catalog.categories.index.create-button.after') !!}
        </div>
    </div>

    <nav class="mp-category-view-switch" aria-label="@lang('admin::category-tree.view-label')">
        <a href="{{ route('admin.catalog.categories.index') }}" @if (request()->query('view') !== 'list') aria-current="page" @endif>
            @lang('admin::category-tree.tree-view')
        </a>
        <a href="{{ route('admin.catalog.categories.index', ['view' => 'list']) }}" @if (request()->query('view') === 'list') aria-current="page" @endif>
            @lang('admin::category-tree.table-view')
        </a>
    </nav>

    {!! view_render_event('bagisto.admin.catalog.categories.list.before') !!}

    @if (request()->query('view') === 'list')
        <x-admin::datagrid :src="route('admin.catalog.categories.index')" />
    @else
        @include('admin::catalog.categories.tree')
    @endif

    {!! view_render_event('bagisto.admin.catalog.categories.list.after') !!}

    @include('admin::catalog.categories.tree-styles')
</x-admin::layouts>
