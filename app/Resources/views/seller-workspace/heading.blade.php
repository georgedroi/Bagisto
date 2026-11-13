<nav class="msw-crumb" aria-label="{{ trans('admin::seller-workspace.home') }}">
    <a href="{{ $overviewUrl }}">{{ trans('admin::seller-workspace.home') }}</a>
    @include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])
    <strong>{{ trans('admin::seller-workspace.pages.'.$page) }}</strong>
</nav>
<h1 @class([$titleClass ?? null])>{{ trans('admin::seller-workspace.titles.'.$page) }}</h1>
