<a
    href="{{ route('shop.home.index') }}"
    class="mp-brand m-[0_auto_20px_auto]"
    aria-label="{{ trans('manpleasure.home') }}"
    data-store-brand
>
    @if (core()->getCurrentChannel()->logo_url)
        <img
            src="{{ core()->getCurrentChannel()->logo_url }}"
            alt="{{ core()->getCurrentChannel()->logo_alt ?: trans('shop::app.customers.brand') }}"
            width="180"
            height="32"
        >
    @else
        <span>{{ trans('shop::app.customers.brand') }}</span>
        <small>{{ trans('manpleasure.tagline') }}</small>
    @endif
</a>
