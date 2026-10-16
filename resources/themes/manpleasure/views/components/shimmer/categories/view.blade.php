<div class="mp-catalog container">
    <div class="mp-catalog-layout flex items-start gap-10 max-lg:gap-5 md:mt-10">
        <div class="max-md:hidden" aria-hidden="true">
            <x-shop::shimmer.categories.filters />
        </div>
        <div class="mp-catalog-results flex-1">
            <div class="max-md:hidden" aria-hidden="true">
                <x-shop::shimmer.categories.toolbar />
            </div>
            <div class="mp-catalog-grid mt-8 max-md:mt-5">
                <x-shop::shimmer.products.cards.grid :count="6" />
            </div>
        </div>
    </div>
</div>
