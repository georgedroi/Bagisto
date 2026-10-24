@props(['navigationLink' => false])

<div class="mp-product-carousel container" aria-hidden="true">
    <div class="mp-section-heading">
        <span class="mp-skeleton-heading shimmer"></span>
    </div>
    <div class="mp-product-track scrollbar-hide">
        <x-shop::shimmer.products.cards.grid :count="6" />
    </div>
</div>
