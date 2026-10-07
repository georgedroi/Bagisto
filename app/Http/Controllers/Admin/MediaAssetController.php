<?php

namespace App\Http\Controllers\Admin;

use App\Models\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaAssetController
{
    public function index(Request $request)
    {
        $query = MediaAsset::query()->latest();

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('original_name', 'like', "%{$search}%")
                    ->orWhere('mime_type', 'like', "%{$search}%")
                    ->orWhere('checksum', 'like', "%{$search}%");
            });
        }

        $assets = $query->paginate(24)->withQueryString();

        return view('manpleasure-admin::media.index', compact('assets'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,mp4', 'max:51200'],
            'collection' => ['nullable', 'string', 'max:100'],
        ]);

        $file = $validated['file'];
        $mime = $file->getMimeType();
        $isVideo = Str::startsWith($mime, 'video/');
        $dimensions = $isVideo ? [null, null] : @getimagesize($file->getRealPath());

        abort_unless($isVideo || $dimensions !== false, 422, 'The uploaded file is not a valid image.');

        $checksum = hash_file('sha256', $file->getRealPath());
        $existing = MediaAsset::where('checksum', $checksum)->first();

        if ($existing) {
            return response()->json(['data' => $existing, 'reused' => true]);
        }

        $path = $file->storeAs('media-library/'.now()->format('Y/m'), Str::uuid().'.'.$file->extension(), 'public');
        $asset = MediaAsset::create([
            'disk' => 'public', 'path' => $path, 'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime, 'size' => $file->getSize(), 'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null, 'checksum' => $checksum,
            'collection' => $validated['collection'] ?? 'library', 'uploaded_by' => auth('admin')->id(),
        ]);

        return response()->json(['data' => $asset, 'reused' => false], 201);
    }


    public function destroy(MediaAsset $asset): JsonResponse
    {
        Storage::disk($asset->disk)->delete($asset->path);
        $asset->delete();

        return response()->json(['message' => 'Media asset deleted.']);
    }
}
