@php
    $channel = core()->getCurrentChannel();
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
        <img class="mp-hero-image" src="{{ asset('themes/shop/manpleasure/hero.jpg') }}" alt="MANPLEASURE lifestyle campaign">
        <div class="mp-hero-copy">
            <p class="mp-eyebrow">PRIVATE WELLNESS COLLECTION</p>
            <h1>EXPLORE<br>YOUR<br><em>BOLDER STYLE</em></h1>
            <p class="mp-hero-description">Premium wellness for modern lifestyles.<br>Confident. Considered. Always you.</p>
            <a class="mp-button" href="#mp-products">SHOP NOW <span aria-hidden="true">→</span></a>
        </div>
        <div class="mp-hero-note" aria-hidden="true"><b>Feel<br>your<br>story</b><span>PRIVATE<br>CONFIDENT<br>AUTHENTIC<br>YOU</span></div>
    </section>

    <section class="mp-benefits" aria-label="Store benefits">
        <div><i aria-hidden="true">◇</i><span><b>DISCREET DELIVERY</b><small>Thoughtful, private shipping</small></span></div>
        <div><i aria-hidden="true">♡</i><span><b>CURATED QUALITY</b><small>Considered products</small></span></div>
        <div><i aria-hidden="true">✦</i><span><b>SECURE CHECKOUT</b><small>Your privacy matters</small></span></div>
        <div><i aria-hidden="true">⌁</i><span><b>EASY RETURNS</b><small>Friendly, simple support</small></span></div>
        <div><i aria-hidden="true">✧</i><span><b>HERE TO HELP</b><small>Care at every step</small></span></div>
    </section>

    <div class="mp-storefront-sections">
        @if (! $sections->contains('type', \Webkul\Theme\Enums\SectionTypeEnum::CATEGORY_CAROUSEL->value))
            <section class="mp-section">
                <x-shop::categories.carousel
                    title="Featured Categories"
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
                    <section class="mp-section" id="mp-products">
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
                    title="Featured Products"
                    :src="route('shop.api.products.index')"
                    :navigation-link="route('shop.search.index')"
                    aria-label="{{ trans('shop::app.home.index.product-carousel') }}"
                />
            </section>
        @endif

        <section class="mp-promos" aria-label="Featured collections">
            <article class="mp-promo">
                <img src="{{ asset('themes/shop/manpleasure/banner-men.jpg') }}" alt="">
                <div>
                    <h2>ELEVATE<br><em>YOUR EVERYDAY</em></h2>
                    <p>Explore the collection<br>Made for modern days</p>
                    <a href="{{ route('shop.search.index') }}">SHOP NOW <span aria-hidden="true">→</span></a>
                </div>
            </article>
            <article class="mp-promo">
                <img src="{{ asset('themes/shop/manpleasure/banner-women.jpg') }}" alt="">
                <div>
                    <h2>STYLE<br><em>WITHOUT LIMITS</em></h2>
                    <p>Discover your favorites<br>For every version of you</p>
                    <a href="{{ route('shop.search.index') }}">EXPLORE NOW <span aria-hidden="true">→</span></a>
                </div>
            </article>
        </section>
    </div>

    @if ($preview ?? false)
        @include('shop::home.preview-bridge')
    @endif
</x-shop::layouts>
