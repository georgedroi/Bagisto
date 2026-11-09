<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ core()->getCurrentLocale()->direction }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('admin::seller-workspace.pages.'.$page) }}</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&display=swap" rel="stylesheet">
    @include('manpleasure-admin::admin-menu-styles')
    @include('manpleasure-admin::seller-workspace.styles')
    @include('manpleasure-admin::admin-theme')
</head>
<body>
    <div class="msw" v-pre data-msw-page="{{ $page }}" data-msw-loading="{{ trans('admin::seller-workspace.actions.loading') }}" data-msw-saved="{{ trans('admin::seller-workspace.actions.saved') }}" data-msw-failed="{{ trans('admin::seller-workspace.actions.failed') }}">
        @include('manpleasure-admin::admin-menu', ['menuPage' => $page])

        <main @class(['msw-main', 'msw-main-hero' => $page === 'overview'])>
            @include('manpleasure-admin::seller-workspace.pages.'.$page)
        </main>

        @include('manpleasure-admin::admin-rail')
    </div>
    @include('manpleasure-admin::admin-menu-script')
    @include('manpleasure-admin::seller-workspace.script')
</body>
</html>
