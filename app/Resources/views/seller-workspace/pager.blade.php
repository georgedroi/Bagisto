<nav class="msw-pager" aria-label="{{ trans('admin::seller-workspace.pager') }}">
    <a @if ($rows->previousPageUrl()) href="{{ $rows->previousPageUrl() }}" @else aria-disabled="true" @endif>@include('manpleasure-admin::seller-workspace.icon', ['name' => 'left']){{ trans('admin::seller-workspace.previous') }}</a>
    <strong aria-current="page">{{ $rows->currentPage() }}</strong>
    <a @if ($rows->nextPageUrl()) href="{{ $rows->nextPageUrl() }}" @else aria-disabled="true" @endif>{{ trans('admin::seller-workspace.next') }}@include('manpleasure-admin::seller-workspace.icon', ['name' => 'right'])</a>
</nav>
