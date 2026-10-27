<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploadController extends Controller
{
    /**
     * Upload an image file for CMS content and return its public URL.
     * Max size: 5MB. Allowed: jpeg, png, jpg, gif, webp, svg.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,gif,webp,svg', 'max:5120'],
        ]);

        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension() ?: 'webp';
        $filename = time() . '_' . Str::random(10) . '.' . $extension;

        // Store into public/storage/uploads
        $path = $file->storeAs('uploads', $filename, 'public');

        $url = '/storage/' . $path;

        return response()->json([
            'success' => true,
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
