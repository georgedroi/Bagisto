<?php

namespace App\Repositories;

use App\Models\MediaAsset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Core\Eloquent\Repository;

class MediaAssetRepository extends Repository
{
    /**
     * Specify the model class name.
     */
    public function model(): string
    {
        return MediaAsset::class;
    }

    /**
     * List the newest assets first, filtered by file name, type or checksum.
     */
    public function search(?string $search, int $perPage): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->latest()
            ->when($search, fn ($query) => $query->where(function ($builder) use ($search) {
                $builder->where('original_name', 'like', "%{$search}%")
                    ->orWhere('mime_type', 'like', "%{$search}%")
                    ->orWhere('checksum', 'like', "%{$search}%");
            }))
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Store a checked upload on the public disk, or give back the asset that already holds the same file.
     *
     * @return array{0: MediaAsset, 1: bool} the asset and whether it already existed
     */
    public function storeUpload(UploadedFile $file, string $collection, ?array $dimensions): array
    {
        $checksum = hash_file('sha256', $file->getRealPath());

        $existing = $this->model->newQuery()->where('checksum', $checksum)->first();

        if ($existing) {
            return [$existing, true];
        }

        $path = $file->storeAs('media-library/'.now()->format('Y/m'), Str::uuid().'.'.$file->extension(), 'public');

        $asset = $this->model->newQuery()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'checksum' => $checksum,
            'collection' => $collection,
            'uploaded_by' => auth('admin')->id(),
        ]);

        return [$asset, false];
    }

    /**
     * Give the site-relative address of an asset, so a saved page works on any host.
     */
    public function publicPath(MediaAsset $asset): string
    {
        return (string) parse_url(Storage::disk($asset->disk)->url($asset->path), PHP_URL_PATH);
    }

    /**
     * Delete an asset's file and its record.
     */
    public function deleteAsset(int $id): void
    {
        $asset = $this->findOrFail($id);

        Storage::disk($asset->disk)->delete($asset->path);

        $asset->delete();
    }
}
