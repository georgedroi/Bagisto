@props(['count' => 0])

@for ($index = 0; $index < (int) $count; $index++)
    <div {{ $attributes->class(['mp-product-card', 'mp-product-skeleton']) }} aria-hidden="true">
        <div class="mp-product-image shimmer"></div>
        <div class="mp-product-copy">
            <span class="mp-skeleton-line shimmer"></span>
            <span class="mp-skeleton-line mp-skeleton-line-short shimmer"></span>
        </div>
    </div>
@endfor
