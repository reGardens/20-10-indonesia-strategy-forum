<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\SectionItem;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BuyNowSocialController extends Controller
{
    protected string $slug = 'buy_now_social';

    public function __construct(
        protected MediaService $mediaService,
    ) {}

    public function create()
    {
        $section = Section::where('slug', $this->slug)->firstOrFail();
        return Inertia::render('Cms/Sections/CreateItem', [
            'section' => $section,
        ]);
    }

    public function store(Request $request)
    {
        $section = Section::where('slug', $this->slug)->firstOrFail();
        
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.image' => ['nullable', 'image', 'max:5120'],
            'items.*.link' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($validated['items'] as $index => $itemData) {
            $imagePath = null;
            if (isset($itemData['image'])) {
                $imagePath = $itemData['image']->store('sections', 'public');
            }

            $section->items()->create([
                'title' => $itemData['title'],
                'image' => $imagePath,
                'link' => $itemData['link'] ?? '#',
                'button_text' => 'social',
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('cms.buy-now.index')->with('success', 'Hubungi Kami berhasil ditambahkan.');
    }
}
