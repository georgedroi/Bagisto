<?php

namespace App\Http\Controllers\Admin;

use App\Repositories\MediaAssetRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MediaAssetController
{
    /**
     * Assets shown on one page of the Media Bank.
     */
    protected const PER_PAGE = 24;

    /**
     * Create a new controller instance.
     */
    public function __construct(protected MediaAssetRepository $mediaAssetRepository) {}

    /**
     * List the Media Bank, newest first, filtered by search text.
     */
    public function index(Request $request): View
    {
        $assets = $this->mediaAssetRepository->search(trim((string) $request->query('search')) ?: null, self::PER_PAGE);

        return view('manpleasure-admin::media.index', compact('assets'));
    }

    /**
     * Store an uploaded picture or video, reusing the asset that already holds the same file.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4', 'max:51200'],
            'collection' => ['nullable', 'string', 'max:100'],
        ]);

        $file = $validated['file'];
        $isVideo = Str::startsWith($file->getMimeType(), 'video/');
        $dimensions = $isVideo ? null : @getimagesize($file->getRealPath());

        abort_unless($isVideo || $dimensions !== false, 422, trans('admin::product-editor.media-invalid-image'));

        [$asset, $reused] = $this->mediaAssetRepository->storeUpload($file, $validated['collection'] ?? 'library', $dimensions ?: null);

        return response()->json(['data' => $asset, 'reused' => $reused], $reused ? 200 : 201);
    }

    /**
     * Delete an asset and its file.
     */
    public function destroy(int $asset): JsonResponse
    {
        $this->mediaAssetRepository->deleteAsset($asset);

        return response()->json(['message' => trans('admin::product-editor.media-deleted')]);
    }
}
