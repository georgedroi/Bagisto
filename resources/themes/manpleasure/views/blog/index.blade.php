@push('meta')
    <meta name="description" content="{{ trans('manpleasure.blog-description') }}">
@endpush

<x-shop::layouts :has-feature="false">
    <x-slot:title>{{ trans('manpleasure.blog-title') }}</x-slot>

    <section class="mp-journal">
        <p class="mp-eyebrow">MANPLEASURE</p>
        <h1>{{ trans('manpleasure.blog-title') }}</h1>
        <p class="mp-journal-description">{{ trans('manpleasure.blog-description') }}</p>

        @if ($pages->count())
            <div class="mp-journal-grid">
                @foreach ($pages as $page)
                    <article class="mp-journal-card">
                        <h2><a href="{{ route('shop.cms.page', ['slug' => $page->url_key]) }}">{{ $page->page_title }}</a></h2>
                        <p>{{ \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($page->html_content))), 200) }}</p>
                        <a class="mp-journal-link" href="{{ route('shop.cms.page', ['slug' => $page->url_key]) }}">{{ trans('manpleasure.read-article') }} <span class="icon-arrow-right" aria-hidden="true"></span></a>
                    </article>
                @endforeach
            </div>
            {{ $pages->links() }}
        @else
            <div class="mp-empty-state">
                <h2>{{ trans('manpleasure.blog-empty-title') }}</h2>
                <p>{{ trans('manpleasure.blog-empty-description') }}</p>
            </div>
        @endif
    </section>
</x-shop::layouts>
