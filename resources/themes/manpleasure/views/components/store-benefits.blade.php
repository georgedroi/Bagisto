@php
    $benefits = [
        ['key' => 'delivery', 'path' => 'M1 3h14v12H1z M15 7h4l4 4v4h-8 M5 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4 M19 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4'],
        ['key' => 'quality', 'path' => 'M12 2 3 6v6c0 5 9 10 9 10s9-5 9-10V6z M8 12l3 3 5-6'],
        ['key' => 'checkout', 'path' => 'M5 10h14v12H5z M8 10V6a4 4 0 0 1 8 0v4 M12 14v4'],
        ['key' => 'returns', 'path' => 'm12 2 9 5v10l-9 5-9-5V7z M3 7l9 5 9-5 M12 12v10 M7 4l10 5'],
        ['key' => 'help', 'path' => 'M3 14v-3a9 9 0 0 1 18 0v3 M3 12H1v7h4v-7z M21 12h2v7h-4v-7z M21 19c0 2-4 3-8 3'],
    ];
@endphp

<section {{ $attributes->merge(['class' => 'mp-benefits']) }} aria-label="{{ trans('manpleasure.benefits-label') }}">
    @foreach ($benefits as $benefit)
        <div>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $benefit['path'] }}" /></svg>
            <p><strong>{{ trans('manpleasure.'.$benefit['key'].'-title') }}</strong><small>{{ trans('manpleasure.'.$benefit['key'].'-description') }}</small></p>
        </div>
    @endforeach
</section>
