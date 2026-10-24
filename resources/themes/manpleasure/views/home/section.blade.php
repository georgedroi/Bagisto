@php
    $data = $section->getTypeInstance()?->sanitize((array) $section->options) ?? $section->options;
@endphp

<section
    class="mp-section mp-managed-section"
    id="mp-section-{{ $section->id }}"
    @if ($preview ?? false)
        data-section-id="{{ $section->id }}"
        data-section-name="{{ $section->name }}"
    @endif
>
    @switch ($section->type)
        @case (\Webkul\Theme\Enums\SectionTypeEnum::IMAGE_CAROUSEL->value)
            <x-shop::carousel :options="$data" aria-label="{{ trans('shop::app.home.index.image-carousel') }}" />
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
            <x-shop::categories.carousel :title="$data['title'] ?? ''" :src="route('shop.api.categories.index', $data['filters'] ?? [])" :navigation-link="route('shop.search.index')" aria-label="{{ trans('shop::app.home.index.categories-carousel') }}" />
            @break

        @case (\Webkul\Theme\Enums\SectionTypeEnum::PRODUCT_CAROUSEL->value)
            <x-shop::products.carousel :title="$data['title'] ?? ''" :src="route('shop.api.products.index', $data['filters'] ?? [])" :navigation-link="route('shop.search.index', $data['filters'] ?? [])" aria-label="{{ trans('shop::app.home.index.product-carousel') }}" />
            @break

        @case (\Webkul\Theme\Enums\SectionTypeEnum::SERVICES_CONTENT->value)
            <div class="mp-benefits" aria-label="{{ trans('manpleasure.benefits-label') }}">
                @foreach ($data['services'] ?? [] as $service)
                    <div>
                        <span class="mp-benefit-icon {{ $service['service_icon'] }}" aria-hidden="true"></span>
                        <p><strong>{{ $service['title'] }}</strong><small>{{ $service['description'] }}</small></p>
                    </div>
                @endforeach
            </div>
            @break
    @endswitch
</section>
