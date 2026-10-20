<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BuyNowController extends Controller
{
    public function index()
    {
        $marketplaceSection = Section::where('slug', 'buy_now_marketplace')->with(['items' => fn($q) => $q->orderBy('sort_order')])->firstOrFail();
        $socialSection = Section::where('slug', 'buy_now_social')->with(['items' => fn($q) => $q->orderBy('sort_order')])->firstOrFail();

        return Inertia::render('Cms/Sections/BuyNow/Index', [
            'marketplaceSection' => $marketplaceSection,
            'socialSection' => $socialSection,
        ]);
    }

    public function updateTitles(Request $request)
    {
        $validated = $request->validate([
            'marketplace_title' => ['required', 'string'],
            'social_title' => ['required', 'string'],
        ]);

        Section::where('slug', 'buy_now_marketplace')->update(['title' => $validated['marketplace_title']]);
        Section::where('slug', 'buy_now_social')->update(['title' => $validated['social_title']]);

        return redirect()->back()->with('success', 'Judul berhasil diperbarui.');
    }
}
