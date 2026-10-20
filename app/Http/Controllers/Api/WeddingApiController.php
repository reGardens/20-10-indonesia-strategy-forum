<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wedding;
use App\Models\Couple;
use App\Models\Setting;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeddingApiController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Wedding::orderBy('created_at', 'desc');
        
        // Filter by user unless super-admin
        if ($user && !$user->hasRole('super-admin')) {
            $query->where('user_id', $user->id);
        }

        return response()->json($query->get()->map(function($w) {
            return [
                'id' => $w->id,
                'slug' => $w->slug,
                'label' => $w->label,
                'createdAt' => $w->created_at->toIso8601String()
            ];
        }));
    }

    public function show($slug)
    {
        $wedding = Wedding::where('slug', $slug)->first();
        if (!$wedding) {
            return response()->json(['message' => 'Pernikahan tidak ditemukan'], 404);
        }
        return response()->json([
            'id' => $wedding->id,
            'slug' => $wedding->slug,
            'label' => $wedding->label,
            'createdAt' => $wedding->created_at->toIso8601String()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'slug' => 'required|string|unique:weddings,slug',
            'label' => 'required|string',
        ]);

        $wedding = DB::transaction(function() use ($request) {
            $w = Wedding::create([
                'slug' => $request->slug,
                'label' => $request->label,
                'user_id' => auth()->id(),
            ]);

            // Create blank couple
            Couple::create([
                'wedding_id' => $w->id,
                'groom_full_name' => '',
                'groom_nickname' => '',
                'groom_photo' => '',
                'groom_father_name' => '',
                'groom_mother_name' => '',
                'groom_instagram_url' => '',
                'groom_child_order' => '',
                'bride_full_name' => '',
                'bride_nickname' => '',
                'bride_photo' => '',
                'bride_father_name' => '',
                'bride_mother_name' => '',
                'bride_instagram_url' => '',
                'bride_child_order' => '',
                'music_url' => '',
            ]);

            // Create default setting
            Setting::create([
                'wedding_id' => $w->id,
                'template_id' => 'batik-elegance',
                'theme_colors' => [
                    'primary' => '#8B4513',
                    'secondary' => '#D2691E',
                    'accent' => '#FFD700',
                ],
                'gallery_layout' => 'masonry',
                'moderation_enabled' => true,
                'password_protected' => false,
                'password' => '',
                'show_watermark' => true,
                'custom_domain' => '',
                'health_protocol' => '',
                'live_stream_url' => '',
                'shipping_address' => '',
                'seo_meta' => [
                    'title' => '',
                    'description' => '',
                    'image' => '',
                ],
                'qris_image_url' => '',
            ]);

            // Create blank payment
            Payment::create([
                'wedding_id' => $w->id,
                'bank_accounts' => [],
                'gifts' => [],
            ]);

            return $w;
        });

        return response()->json([
            'id' => $wedding->id,
            'slug' => $wedding->slug,
            'label' => $wedding->label,
            'createdAt' => $wedding->created_at->toIso8601String()
        ], 201);
    }

    public function destroy($slug)
    {
        $wedding = Wedding::where('slug', $slug)->first();
        if (!$wedding) {
            return response()->json(['message' => 'Pernikahan tidak ditemukan'], 404);
        }

        $wedding->delete();
        return response()->json(['success' => true]);
    }

    public function exists($slug)
    {
        if (str_starts_with($slug, 'demo-')) {
            return response()->json(['exists' => true]);
        }
        $exists = Wedding::where('slug', $slug)->exists();
        return response()->json(['exists' => $exists]);
    }
}
