@props(['count' => 0])

<div class="mp-category-carousel container" aria-hidden="true">
    <div class="mp-category-track scrollbar-hide">
        @for ($index = 0; $index < min((int) $count, 6); $index++)
            <div class="mp-category-card mp-category-skeleton">
                <div class="mp-category-image shimmer"></div>
                <div class="mp-category-caption">
                    <span class="mp-skeleton-line shimmer"></span>
                    <span class="mp-skeleton-line mp-skeleton-line-short shimmer"></span>
                </div>
            </div>
        @endfor
    </div>
</div>
