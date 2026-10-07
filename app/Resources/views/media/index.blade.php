<x-admin::layouts>
    <x-slot:title>Media Bank</x-slot:title>

    <div class="grid gap-5">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <div>
                <h1 class="text-xl font-bold text-gray-800 dark:text-white">Media Bank</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Reusable validated images and videos for the catalog.</p>
            </div>
            <form method="POST" action="{{ route('admin.media.assets.store') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.mp4" required class="rounded border px-2 py-1 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <button class="primary-button">Upload</button>
            </form>
        </div>

        <form method="GET" class="flex gap-2">
            <input name="search" value="{{ request('search') }}" placeholder="Search filename, MIME type, or checksum" class="w-full rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            <button class="secondary-button">Search</button>
        </form>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($assets as $asset)
                <article class="rounded border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                    @if (str_starts_with($asset->mime_type, 'image/'))
                        <img src="{{ Storage::disk($asset->disk)->url($asset->path) }}" alt="{{ $asset->original_name }}" class="mb-3 aspect-square w-full rounded object-cover">
                    @else
                        <div class="mb-3 flex aspect-square items-center justify-center rounded bg-gray-100 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">Video</div>
                    @endif
                    <p class="truncate text-sm font-semibold text-gray-800 dark:text-white" title="{{ $asset->original_name }}">{{ $asset->original_name }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $asset->mime_type }} · {{ number_format($asset->size / 1024, 1) }} KB</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $asset->width ? $asset->width.'×'.$asset->height : 'Video' }}</p>
                    <form method="POST" action="{{ route('admin.media.assets.delete', $asset) }}" class="mt-3">
                        @csrf
                        @method('DELETE')
                        <button class="secondary-button w-full">Delete</button>
                    </form>
                </article>
            @empty
                <p class="text-sm text-gray-500">No media assets found.</p>
            @endforelse
        </div>

        {{ $assets->links() }}
    </div>
</x-admin::layouts>
