<aside class="msw-rail" aria-label="{{ trans('admin::seller-workspace.menu.tools') }}">
    <a href="{{ route('admin.notification.index') }}" title="{{ trans('admin::seller-workspace.menu.notifications') }}" aria-label="{{ trans('admin::seller-workspace.menu.notifications') }}">
        @include('manpleasure-admin::seller-workspace.icon', ['name' => 'bell'])
    </a>
    <a href="{{ route('shop.home.index') }}" title="{{ trans('admin::seller-workspace.menu.storefront') }}" aria-label="{{ trans('admin::seller-workspace.menu.storefront') }}">
        @include('manpleasure-admin::seller-workspace.icon', ['name' => 'store'])
    </a>
    <a href="{{ route('admin.account.edit') }}" title="{{ trans('admin::seller-workspace.side.account') }}" aria-label="{{ trans('admin::seller-workspace.side.account') }}">
        @include('manpleasure-admin::seller-workspace.icon', ['name' => 'user'])
    </a>
    <form method="post" action="{{ route('admin.session.destroy') }}">
        @csrf
        @method('DELETE')
        <button type="submit" title="{{ trans('admin::seller-workspace.menu.logout') }}" aria-label="{{ trans('admin::seller-workspace.menu.logout') }}">
            @include('manpleasure-admin::seller-workspace.icon', ['name' => 'logout'])
        </button>
    </form>
</aside>
