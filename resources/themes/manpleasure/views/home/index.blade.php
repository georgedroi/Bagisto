@php
    $channel = core()->getCurrentChannel();
    $productSection = $sections->firstWhere('type', \Webkul\Theme\Enums\SectionTypeEnum::PRODUCT_CAROUSEL->value);
    $productsAnchor = $productSection ? 'mp-products-'.$productSection->id : 'mp-products';
@endphp

@push('meta')
    <meta name="title" content="{{ $channel->home_seo['meta_title'] ?? 'MANPLEASURE' }}">
    <meta name="description" content="{{ $channel->home_seo['meta_description'] ?? '' }}">
@endpush

<x-shop::layouts>
    <x-slot:title>
        {{ $channel->home_seo['meta_title'] ?? 'MANPLEASURE' }}
    </x-slot>

    <section class="mp-hero">
        <img class="mp-hero-image" src="{{ asset('themes/shop/manpleasure/hero.jpg') }}" alt="{{ trans('manpleasure.campaign-alt') }}">
        <div class="mp-hero-copy">
            <p class="mp-eyebrow">{{ trans('manpleasure.eyebrow') }}</p>
            <h1>{{ trans('manpleasure.hero-explore') }}<br>{{ trans('manpleasure.hero-your') }}<br><em>{{ trans('manpleasure.hero-style') }}</em></h1>
            <p class="mp-hero-description">{{ trans('manpleasure.hero-description') }}<br>{{ trans('manpleasure.hero-description-second') }}</p>
            <a class="mp-button" href="#{{ $productsAnchor }}">{{ trans('manpleasure.shop-now') }} <span aria-hidden="true">→</span></a>
        </div>
        <div class="mp-hero-note" aria-hidden="true"><b>{{ trans('manpleasure.note-feel') }}<br>{{ trans('manpleasure.note-your') }}<br>{{ trans('manpleasure.note-story') }}</b><span>{{ trans('manpleasure.note-private') }}<br>{{ trans('manpleasure.note-confident') }}<br>{{ trans('manpleasure.note-authentic') }}<br>{{ trans('manpleasure.note-you') }}</span></div>
    </section>

    <section class="mp-benefits" aria-label="{{ trans('manpleasure.benefits-label') }}">
        <div><i aria-hidden="true">◇</i><span><b>{{ trans('manpleasure.delivery-title') }}</b><small>{{ trans('manpleasure.delivery-description') }}</small></span></div>
        <div><i aria-hidden="true">♡</i><span><b>{{ trans('manpleasure.quality-title') }}</b><small>{{ trans('manpleasure.quality-description') }}</small></span></div>
        <div><i aria-hidden="true">✦</i><span><b>{{ trans('manpleasure.checkout-title') }}</b><small>{{ trans('manpleasure.checkout-description') }}</small></span></div>
        <div><i aria-hidden="true">⌁</i><span><b>{{ trans('manpleasure.returns-title') }}</b><small>{{ trans('manpleasure.returns-description') }}</small></span></div>
        <div><i aria-hidden="true">✧</i><span><b>{{ trans('manpleasure.help-title') }}</b><small>{{ trans('manpleasure.help-description') }}</small></span></div>
    </section>

    <div class="mp-storefront-sections">
        @if (! $sections->contains('type', \Webkul\Theme\Enums\SectionTypeEnum::CATEGORY_CAROUSEL->value))
            <section class="mp-section">
                <x-shop::categories.carousel
                    :title="trans('manpleasure.featured-categories')"
                    :src="route('shop.api.categories.index')"
                    :navigation-link="route('shop.home.index')"
                    aria-label="{{ trans('shop::app.home.index.categories-carousel') }}"
                />
            </section>
        @endif

        @foreach ($sections as $section)
            @php ($data = $section->options)
            @switch ($section->type)
                @case (\Webkul\Theme\Enums\SectionTypeEnum::IMAGE_CAROUSEL->value)
                    <x-shop::carousel
                        :options="$section->getTypeInstance()?->sanitize((array) $data) ?? $data"
                        aria-label="{{ trans('shop::app.home.index.image-carousel') }}"
                    />
                    @break
                @case (\Webkul\Theme\Enums\SectionTypeEnum::STATIC_CONTENT->value)
                    @if (! empty($data['css']))
                        @push('styles')
                            <style>{!! $data['css'] !!}</style>
                        @endpush
                    @endif
                    @if (! empty($data['html']))
                        {!! $data['html'] !!}
                    @endif
                    @break
                @case (\Webkul\Theme\Enums\SectionTypeEnum::CATEGORY_CAROUSEL->value)
                    <section class="mp-section">
                        <x-shop::categories.carousel
                            :title="$data['title'] ?? ''"
                            :src="route('shop.api.categories.index', $data['filters'] ?? [])"
                            :navigation-link="route('shop.home.index')"
                            aria-label="{{ trans('shop::app.home.index.categories-carousel') }}"
                        />
                    </section>
                    @break
                @case (\Webkul\Theme\Enums\SectionTypeEnum::PRODUCT_CAROUSEL->value)
                    <section class="mp-section" id="mp-products-{{ $section->id }}">
                        <x-shop::products.carousel
                            :title="$data['title'] ?? ''"
                            :src="route('shop.api.products.index', $data['filters'] ?? [])"
                            :navigation-link="route('shop.search.index', $data['filters'] ?? [])"
                            aria-label="{{ trans('shop::app.home.index.product-carousel') }}"
                        />
                    </section>
                    @break
            @endswitch
        @endforeach

        @if (! $sections->contains('type', \Webkul\Theme\Enums\SectionTypeEnum::PRODUCT_CAROUSEL->value))
            <section class="mp-section" id="mp-products">
                <x-shop::products.carousel
                    :title="trans('manpleasure.featured-products')"
                    :src="route('shop.api.products.index')"
                    :navigation-link="route('shop.search.index')"
                    aria-label="{{ trans('shop::app.home.index.product-carousel') }}"
                />
            </section>
        @endif

        <section class="mp-promos" aria-label="{{ trans('manpleasure.collections-label') }}">
            <article class="mp-promo">
                <img src="{{ asset('themes/shop/manpleasure/banner-men.jpg') }}" alt="">
                <div>
                    <h2>{{ trans('manpleasure.promo-elevate') }}<br><em>{{ trans('manpleasure.promo-everyday') }}</em></h2>
                    <p>{{ trans('manpleasure.promo-explore') }}<br>{{ trans('manpleasure.promo-modern') }}</p>
                    <a href="{{ route('shop.search.index') }}">{{ trans('manpleasure.shop-now') }} <span aria-hidden="true">→</span></a>
                </div>
            </article>
            <article class="mp-promo">
                <img src="{{ asset('themes/shop/manpleasure/banner-women.jpg') }}" alt="">
                <div>
                    <h2>{{ trans('manpleasure.promo-style') }}<br><em>{{ trans('manpleasure.promo-limits') }}</em></h2>
                    <p>{{ trans('manpleasure.promo-discover') }}<br>{{ trans('manpleasure.promo-you') }}</p>
                    <a href="{{ route('shop.search.index') }}">{{ trans('manpleasure.explore-now') }} <span aria-hidden="true">→</span></a>
                </div>
            </article>
        </section>
    </div>

    @if ($preview ?? false)
        @include('shop::home.preview-bridge')
    @endif
</x-shop::layouts>
