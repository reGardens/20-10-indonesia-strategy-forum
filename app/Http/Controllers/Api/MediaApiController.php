<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaApiController extends Controller
{
    public function index($slug)
    {
        if (str_starts_with($slug, 'demo-')) {
            return response()->json([]);
        }

        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $media = Media::where('wedding_id', $wedding->id)->orderBy('created_at', 'desc')->get();

        return response()->json($media->map(fn($m) => $this->formatMedia($m))->toArray());
    }

    public function store(Request $request, $slug)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        
        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("wedding/{$slug}", $filename, 'public');

        $media = Media::create([
            'id' => (string) Str::uuid(),
            'wedding_id' => $wedding->id,
            'url' => '/storage/' . $path,
            'type' => Str::startsWith($file->getMimeType(), 'video') ? 'video' : 'image',
            'size' => $file->getSize(),
        ]);

        return response()->json($this->formatMedia($media), 201);
    }

    public function destroy($slug, $id)
    {
        $wedding = Wedding::where('slug', $slug)->firstOrFail();
        $media = Media::where('wedding_id', $wedding->id)->where('id', $id)->firstOrFail();

        // Delete from public storage
        $relativePath = str_replace('/storage/', '', $media->url);
        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }

        $media->delete();
        return response()->json(['success' => true]);
    }

    private function formatMedia(Media $media)
    {
        return [
            'id' => $media->id,
            'url' => $media->url,
            'type' => $media->type,
            'size' => $media->size,
            'createdAt' => $media->created_at ? $media->created_at->toIso8601String() : null,
            'updatedAt' => $media->updated_at ? $media->updated_at->toIso8601String() : null,
        ];
    }
}
