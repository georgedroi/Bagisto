@php
    $channel = core()->getCurrentChannel();
    $campaigns = collect(['mp-hero', 'mp-benefits', 'mp-promos'])->mapWithKeys(function ($class) use ($sections) {
        return [$class => $sections->first(fn ($section) => $section->type === \Webkul\Theme\Enums\SectionTypeEnum::STATIC_CONTENT->value && str_contains($section->options['html'] ?? '', $class))];
    });
    $heroSection = $campaigns['mp-hero'] ?? $sections->firstWhere('type', \Webkul\Theme\Enums\SectionTypeEnum::IMAGE_CAROUSEL->value);
    $benefitsSection = $campaigns['mp-benefits'] ?? $sections->firstWhere('type', \Webkul\Theme\Enums\SectionTypeEnum::SERVICES_CONTENT->value);
    $categoryLinks = collect($categories->resolve())->keyBy('name');
@endphp

@push('meta')
    <meta name="title" content="{{ $channel->home_seo['meta_title'] ?? 'MANPLEASURE' }}">
    <meta name="description" content="{{ $channel->home_seo['meta_description'] ?? '' }}">
    <meta name="keywords" content="{{ $channel->home_seo['meta_keywords'] ?? '' }}">
@endpush

<x-shop::layouts>
    <x-slot:title>{{ $channel->home_seo['meta_title'] ?? 'MANPLEASURE' }}</x-slot>

    @if ($heroSection)
        @include('shop::home.section', ['section' => $heroSection])
    @else
        <section class="mp-hero" aria-label="{{ trans('manpleasure.campaign-alt') }}">
            <img class="mp-hero-image" src="{{ asset('themes/shop/manpleasure/hero.webp') }}" alt="{{ trans('manpleasure.campaign-alt') }}" width="825" height="311" fetchpriority="high">
            <div class="mp-hero-copy">
                <p class="mp-eyebrow">{{ trans('manpleasure.eyebrow') }}</p>
                <h1><span>{{ trans('manpleasure.hero-explore') }}</span><span>{{ trans('manpleasure.hero-your') }}</span><span class="mp-pink">{{ trans('manpleasure.hero-style') }}</span></h1>
                <p class="mp-hero-description">{{ trans('manpleasure.hero-description') }}<br>{{ trans('manpleasure.hero-description-second') }}</p>
                <a class="mp-button" href="{{ route('shop.search.index') }}">{{ trans('manpleasure.shop-now') }} <span class="icon-arrow-right" aria-hidden="true"></span></a>
            </div>
        </section>
    @endif

    @if ($benefitsSection)
        @include('shop::home.section', ['section' => $benefitsSection])
    @else
        <x-shop::store-benefits />
    @endif

    <div class="mp-storefront-sections">
        @if (! $sections->contains('type', \Webkul\Theme\Enums\SectionTypeEnum::CATEGORY_CAROUSEL->value))
            <section class="mp-section" aria-label="{{ trans('manpleasure.featured-categories') }}">
                <x-shop::categories.carousel :title="trans('manpleasure.featured-categories')" :src="route('shop.api.categories.index', ['parent_id' => $channel->root_category_id])" :navigation-link="route('shop.search.index')" />
            </section>
        @endif

        @foreach ($sections as $section)
            @continue ($campaigns->filter()->contains('id', $section->id))
            @continue ($section->id === $heroSection?->id || $section->id === $benefitsSection?->id)
            @continue ($section->getTypeInstance()?->rendersInLayout())

            @include('shop::home.section', ['section' => $section])
        @endforeach

        @if (! $sections->contains('type', \Webkul\Theme\Enums\SectionTypeEnum::PRODUCT_CAROUSEL->value))
            <section class="mp-section" id="mp-products">
                <x-shop::products.carousel :title="trans('manpleasure.featured-products')" :src="route('shop.api.products.index')" :navigation-link="route('shop.search.index')" />
            </section>
        @endif

        @if ($campaigns['mp-promos'])
            @include('shop::home.section', ['section' => $campaigns['mp-promos']])
        @else
            <section class="mp-promos" aria-label="{{ trans('manpleasure.collections-label') }}">
                <a class="mp-promo" href="{{ $categoryLinks->get('Men’s Care')['url'] ?? $categoryLinks->get("Men's Care")['url'] ?? route('shop.search.index') }}">
                    <img src="{{ asset('themes/shop/manpleasure/care-banner.webp') }}" alt="{{ trans('manpleasure.care-title') }}" loading="lazy">
                    <div>
                        <h2>{{ trans('manpleasure.care-title') }}<br>{{ trans('manpleasure.care-subtitle') }}</h2>
                        <p>{{ trans('manpleasure.care-description') }}</p>
                        <span class="mp-promo-action">{{ trans('manpleasure.shop-now') }} <span class="icon-arrow-right" aria-hidden="true"></span></span>
                    </div>
                </a>
                <a class="mp-promo" href="{{ $categoryLinks->get('For Couples')['url'] ?? route('shop.search.index') }}">
                    <img src="{{ asset('themes/shop/manpleasure/couple-banner.webp') }}" alt="{{ trans('manpleasure.promo-elevate') }}" loading="lazy">
                    <div>
                        <h2>{{ trans('manpleasure.promo-elevate') }}<br><span class="mp-pink">{{ trans('manpleasure.promo-everyday') }}</span></h2>
                        <p>{{ trans('manpleasure.promo-explore') }}</p>
                        <span class="mp-promo-action">{{ trans('manpleasure.explore-now') }} <span class="icon-arrow-right" aria-hidden="true"></span></span>
                    </div>
                </a>
            </section>
        @endif
    </div>

    @if ($preview ?? false)
        @include('shop::home.preview-bridge')
    @endif
</x-shop::layouts>
