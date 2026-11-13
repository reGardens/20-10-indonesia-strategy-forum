<?php

namespace App\Http\Controllers\Api\Isf\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Isf\SettingRequest;
use App\Models\Isf\Article;
use App\Models\Isf\Category;
use App\Models\Isf\Setting;
use App\Services\Isf\IsfCacheService;
use Illuminate\Http\JsonResponse;

/**
 * CMS API — Pengaturan Halaman (feature: pengaturan_halaman).
 * Page-level copy: Beranda top bar + "Pekan Ini" period, sample member profile.
 */
class SettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->current()]);
    }

    public function update(SettingRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $beranda = $validated['beranda'];

        if (!empty($beranda['topBars'])) {
            $firstActive = collect($beranda['topBars'])->first(fn ($item) => ($item['status'] ?? 'terbit') === 'terbit')
                ?? ($beranda['topBars'][0] ?? null);

            if ($firstActive) {
                $beranda['topBar'] = [
                    'badge' => $firstActive['badge'] ?? 'Sorotan',
                    'announcement' => $firstActive['announcement'] ?? '',
                    'info' => $firstActive['info'] ?? '',
                ];
            }
        }

        $existingBeranda = Setting::get('beranda', []);
        $beranda['mediaSorotan'] = $beranda['mediaSorotan'] ?? ($existingBeranda['mediaSorotan'] ?? []);
        $beranda['hotNews'] = $beranda['hotNews'] ?? ($existingBeranda['hotNews'] ?? []);

        Setting::put('beranda', $beranda);
        if (!empty($validated['anggota'])) {
            Setting::put('anggota', $validated['anggota']);
        }

        return response()->json(['data' => $this->current()]);
    }

    public function getRunningText(\Illuminate\Http\Request $request): JsonResponse
    {
        $current = $this->current();
        $items = $current['beranda']['topBars'] ?? [];

        $q = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', '');

        if ($q !== '') {
            $items = array_values(array_filter($items, function ($it) use ($q) {
                return str_contains(strtolower($it['announcement'] ?? ''), strtolower($q))
                    || str_contains(strtolower($it['badge'] ?? ''), strtolower($q))
                    || str_contains(strtolower($it['info'] ?? ''), strtolower($q));
            }));
        }

        if ($status !== '') {
            $items = array_values(array_filter($items, function ($it) use ($status) {
                return ($it['status'] ?? 'terbit') === $status;
            }));
        }

        return response()->json([
            'data' => $items,
            'total' => count($items),
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 50,
        ]);
    }

    public function showRunningText(int $id): JsonResponse
    {
        $current = $this->current();
        $items = $current['beranda']['topBars'] ?? [];
        $item = collect($items)->first(fn ($it) => (int) ($it['id'] ?? 0) === $id);

        if (!$item) {
            abort(404, 'Running text tidak ditemukan.');
        }

        return response()->json(['data' => $item]);
    }

    public function createRunningText(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'announcement' => 'required|string|max:500',
            'badge' => 'nullable|string|max:50',
            'info' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:terbit,draf',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'is_popular' => 'nullable|boolean',
        ]);

        $beranda = Setting::get('beranda', []);
        $topBars = $beranda['topBars'] ?? ($this->current()['beranda']['topBars'] ?? []);

        $nextId = (int) (collect($topBars)->max('id') ?? 0) + 1;
        $newItem = [
            'id' => $nextId,
            'badge' => $validated['badge'] ?: 'Sorotan',
            'announcement' => $validated['announcement'],
            'info' => $validated['info'] ?: 'Edisi Pekan Ini',
            'status' => $validated['status'] ?: 'terbit',
        ];

        $topBars[] = $newItem;
        $beranda['topBars'] = $topBars;
        $this->syncFirstActiveTopBar($beranda, $topBars);
        Setting::put('beranda', $beranda);

        return response()->json(['data' => $newItem], 201);
    }

    public function updateRunningTextItem(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'announcement' => 'required|string|max:500',
            'badge' => 'nullable|string|max:50',
            'info' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:terbit,draf',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'is_popular' => 'nullable|boolean',
        ]);

        $beranda = Setting::get('beranda', []);
        $topBars = $beranda['topBars'] ?? ($this->current()['beranda']['topBars'] ?? []);

        $index = collect($topBars)->search(fn ($it) => (int) ($it['id'] ?? 0) === $id);
        if ($index === false) {
            abort(404, 'Running text tidak ditemukan.');
        }

        $topBars[$index] = array_merge($topBars[$index], [
            'badge' => $validated['badge'] ?: 'Sorotan',
            'announcement' => $validated['announcement'],
            'info' => $validated['info'] ?: 'Edisi Pekan Ini',
            'status' => $validated['status'] ?: 'terbit',
        ]);

        $beranda['topBars'] = array_values($topBars);
        $this->syncFirstActiveTopBar($beranda, $beranda['topBars']);
        Setting::put('beranda', $beranda);

        return response()->json(['data' => $topBars[$index]]);
    }

    public function deleteRunningTextItem(int $id): JsonResponse
    {
        $beranda = Setting::get('beranda', []);
        $topBars = $beranda['topBars'] ?? ($this->current()['beranda']['topBars'] ?? []);

        $topBars = array_values(array_filter($topBars, fn ($it) => (int) ($it['id'] ?? 0) !== $id));
        $beranda['topBars'] = $topBars;
        $this->syncFirstActiveTopBar($beranda, $topBars);
        Setting::put('beranda', $beranda);

        return response()->json(['message' => 'Running text berhasil dihapus.']);
    }

    public function updateRunningText(\Illuminate\Http\Request $request): JsonResponse
    {
        $items = $request->input('items', []);
        $beranda = Setting::get('beranda', []);
        $beranda['topBars'] = $items;
        $this->syncFirstActiveTopBar($beranda, $items);
        Setting::put('beranda', $beranda);
        return response()->json(['data' => $beranda['topBars']]);
    }

    public function getMediaSorotan(\Illuminate\Http\Request $request): JsonResponse
    {
        $current = $this->current();
        $items = $current['beranda']['mediaSorotan'] ?? [];

        $q = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', '');

        if ($q !== '') {
            $items = array_values(array_filter($items, function ($it) use ($q) {
                return str_contains(strtolower($it['title'] ?? ''), strtolower($q))
                    || str_contains(strtolower($it['category'] ?? ''), strtolower($q))
                    || str_contains(strtolower($it['mediaUrl'] ?? ''), strtolower($q));
            }));
        }

        if ($status !== '') {
            $items = array_values(array_filter($items, function ($it) use ($status) {
                return ($it['status'] ?? 'terbit') === $status;
            }));
        }

        usort($items, fn ($a, $b) => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));

        return response()->json([
            'data' => $items,
            'total' => count($items),
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 50,
        ]);
    }

    public function showMediaSorotan(int $id): JsonResponse
    {
        $current = $this->current();
        $items = $current['beranda']['mediaSorotan'] ?? [];
        $item = collect($items)->first(fn ($it) => (int) ($it['id'] ?? 0) === $id);

        if (!$item) {
            abort(404, 'Media sorotan tidak ditemukan.');
        }

        return response()->json(['data' => $item]);
    }

    public function createMediaSorotan(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'imageUrl' => 'nullable|string|max:500',
            'mediaUrl' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:50',
            'publishedAt' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'status' => 'nullable|string|in:terbit,draf',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'is_popular' => 'nullable|boolean',
            // Tampilan section Multimedia & Video Beranda (kosong = nilai bawaan lama).
            'duration' => ['nullable', 'string', 'max:8', 'regex:/^\d{1,2}:[0-5]\d(:[0-5]\d)?$/'],
            'label' => 'nullable|string|max:40',
        ], [
            'duration.regex' => 'Format durasi mm:ss atau jj:mm:ss, contoh 18:45.',
        ]);

        $beranda = Setting::get('beranda', []);
        $mediaSorotan = $beranda['mediaSorotan'] ?? ($this->current()['beranda']['mediaSorotan'] ?? []);

        $nextId = (int) (collect($mediaSorotan)->max('id') ?? 0) + 1;
        $maxOrder = (int) (collect($mediaSorotan)->max('order') ?? 0);
        $order = isset($validated['order']) && $validated['order'] !== null && $validated['order'] !== ''
            ? (int) $validated['order']
            : ($maxOrder + 1);

        $newItem = [
            'id' => $nextId,
            'title' => $validated['title'],
            'imageUrl' => $validated['imageUrl'] ?? '',
            'mediaUrl' => $validated['mediaUrl'] ?? '',
            'category' => $validated['category'] ?: 'YouTube',
            'publishedAt' => $validated['publishedAt'] ?: date('Y-m-d'),
            'order' => $order,
            'status' => $validated['status'] ?: 'terbit',
            'summary' => $validated['summary'] ?? '',
            'content' => $validated['content'] ?? '',
            'author' => $validated['author'] ?? 'Tim Redaksi ISF',
            'is_popular' => (bool) ($validated['is_popular'] ?? false),
            'duration' => $validated['duration'] ?? '',
            'label' => $validated['label'] ?? '',
        ];

        $mediaSorotan[] = $newItem;
        usort($mediaSorotan, fn ($a, $b) => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));
        $beranda['mediaSorotan'] = $mediaSorotan;
        Setting::put('beranda', $beranda);

        return response()->json(['data' => $newItem], 201);
    }

    public function updateMediaSorotanItem(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'imageUrl' => 'nullable|string|max:500',
            'mediaUrl' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:50',
            'publishedAt' => 'nullable|string|max:50',
            'order' => 'nullable|integer',
            'status' => 'nullable|string|in:terbit,draf',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'is_popular' => 'nullable|boolean',
            // Tampilan section Multimedia & Video Beranda (kosong = nilai bawaan lama).
            'duration' => ['nullable', 'string', 'max:8', 'regex:/^\d{1,2}:[0-5]\d(:[0-5]\d)?$/'],
            'label' => 'nullable|string|max:40',
        ], [
            'duration.regex' => 'Format durasi mm:ss atau jj:mm:ss, contoh 18:45.',
        ]);

        $beranda = Setting::get('beranda', []);
        $mediaSorotan = $beranda['mediaSorotan'] ?? ($this->current()['beranda']['mediaSorotan'] ?? []);

        $index = collect($mediaSorotan)->search(fn ($it) => (int) ($it['id'] ?? 0) === $id);
        if ($index === false) {
            abort(404, 'Media sorotan tidak ditemukan.');
        }

        $mediaSorotan[$index] = array_merge($mediaSorotan[$index], [
            'title' => $validated['title'],
            'imageUrl' => $validated['imageUrl'] ?? '',
            'mediaUrl' => $validated['mediaUrl'] ?? '',
            'category' => $validated['category'] ?: 'YouTube',
            'publishedAt' => $validated['publishedAt'] ?: date('Y-m-d'),
            'order' => (int) ($validated['order'] ?? ($mediaSorotan[$index]['order'] ?? 1)),
            'status' => $validated['status'] ?: 'terbit',
            'summary' => $validated['summary'] ?? ($mediaSorotan[$index]['summary'] ?? ''),
            'content' => $validated['content'] ?? ($mediaSorotan[$index]['content'] ?? ''),
            'author' => $validated['author'] ?? ($mediaSorotan[$index]['author'] ?? 'Tim Redaksi ISF'),
            'is_popular' => isset($validated['is_popular']) ? (bool) $validated['is_popular'] : ($mediaSorotan[$index]['is_popular'] ?? false),
            // Field tidak dikirim → nilai lama dipertahankan; dikirim kosong → dikosongkan.
            'duration' => $request->has('duration') ? ($validated['duration'] ?? '') : ($mediaSorotan[$index]['duration'] ?? ''),
            'label' => $request->has('label') ? ($validated['label'] ?? '') : ($mediaSorotan[$index]['label'] ?? ''),
        ]);

        usort($mediaSorotan, fn ($a, $b) => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));
        $beranda['mediaSorotan'] = array_values($mediaSorotan);
        Setting::put('beranda', $beranda);

        return response()->json(['data' => $mediaSorotan[$index]]);
    }

    public function deleteMediaSorotanItem(int $id): JsonResponse
    {
        $beranda = Setting::get('beranda', []);
        $mediaSorotan = $beranda['mediaSorotan'] ?? ($this->current()['beranda']['mediaSorotan'] ?? []);

        $mediaSorotan = array_values(array_filter($mediaSorotan, fn ($it) => (int) ($it['id'] ?? 0) !== $id));
        $beranda['mediaSorotan'] = $mediaSorotan;
        Setting::put('beranda', $beranda);

        return response()->json(['message' => 'Media sorotan berhasil dihapus.']);
    }

    public function updateMediaSorotan(\Illuminate\Http\Request $request): JsonResponse
    {
        $items = $request->input('items', []);
        if (is_array($items)) {
            usort($items, fn ($a, $b) => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));
        }
        $beranda = Setting::get('beranda', []);
        $beranda['mediaSorotan'] = $items;
        Setting::put('beranda', $beranda);
        return response()->json(['data' => $beranda['mediaSorotan']]);
    }

    public function getHotNews(\Illuminate\Http\Request $request): JsonResponse
    {
        $current = $this->current();
        $items = $current['beranda']['hotNews'] ?? [];

        $categoryMap = [
            'hukum & regulasi' => 'Hukum',
            'kebijakan publik' => 'Kebijakan Publik',
            // RUU & Ekonomi digabung ke Hukum & Kebijakan (migration 2026_11_14_000003).
            'ekonomi & pasar' => 'Kebijakan Publik',
            'ruu' => 'Hukum',
            'kebijakan' => 'Kebijakan Publik',
            'ekonomi' => 'Kebijakan Publik',
            'politik & parlemen' => 'Politik',
            'kebijakan publik & fiskal' => 'Kebijakan Publik',
            'politik' => 'Politik',
            'hukum' => 'Hukum',
        ];

        $items = array_map(function ($it) use ($categoryMap) {
            $cat = $it['category'] ?? '';
            $key = strtolower(trim((string) $cat));
            if (isset($categoryMap[$key])) {
                $it['category'] = $categoryMap[$key];
            }
            return $it;
        }, $items);

        $q = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', '');

        if ($q !== '') {
            $items = array_values(array_filter($items, function ($it) use ($q) {
                return str_contains(strtolower($it['title'] ?? ''), strtolower($q))
                    || str_contains(strtolower($it['category'] ?? ''), strtolower($q));
            }));
        }

        if ($status !== '') {
            $items = array_values(array_filter($items, function ($it) use ($status) {
                return ($it['status'] ?? 'terbit') === $status;
            }));
        }

        return response()->json([
            'data' => $items,
            'total' => count($items),
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => 50,
        ]);
    }

    public function showHotNews(int $id): JsonResponse
    {
        $current = $this->current();
        $items = $current['beranda']['hotNews'] ?? [];
        $item = collect($items)->first(fn ($it) => (int) ($it['id'] ?? 0) === $id);

        if (!$item) {
            abort(404, 'Hot news tidak ditemukan.');
        }

        return response()->json(['data' => $item]);
    }

    public function createHotNews(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'to' => 'nullable|string|max:500',
            'url' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
            'published_at' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:terbit,draf',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'is_popular' => 'nullable|boolean',
        ]);

        $beranda = Setting::get('beranda', []);
        $hotNews = $beranda['hotNews'] ?? ($this->current()['beranda']['hotNews'] ?? []);

        $nextId = (int) (collect($hotNews)->max('id') ?? 0) + 1;
        $target = $validated['to'] ?? ($validated['url'] ?? '/beranda');

        $newItem = [
            'id' => $nextId,
            'title' => $validated['title'],
            'to' => $target,
            'url' => $target,
            'category' => $validated['category'] ?: 'Berita Utama',
            'published_at' => $validated['published_at'] ?: date('d M Y'),
            'status' => $validated['status'] ?: 'terbit',
        ];

        $hotNews[] = $newItem;
        $beranda['hotNews'] = $hotNews;
        Setting::put('beranda', $beranda);
        IsfCacheService::forget(IsfCacheService::KEY_BERANDA);

        return response()->json(['data' => $newItem], 201);
    }

    public function updateHotNewsItem(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'to' => 'nullable|string|max:500',
            'url' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
            'published_at' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:terbit,draf',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'author' => 'nullable|string|max:255',
            'is_popular' => 'nullable|boolean',
        ]);

        $beranda = Setting::get('beranda', []);
        $hotNews = $beranda['hotNews'] ?? ($this->current()['beranda']['hotNews'] ?? []);

        $index = collect($hotNews)->search(fn ($it) => (int) ($it['id'] ?? 0) === $id);
        if ($index === false) {
            abort(404, 'Hot news tidak ditemukan.');
        }

        $target = $validated['to'] ?? ($validated['url'] ?? ($hotNews[$index]['to'] ?? '/beranda'));

        $hotNews[$index] = array_merge($hotNews[$index], [
            'title' => $validated['title'],
            'to' => $target,
            'url' => $target,
            'category' => $validated['category'] ?: ($hotNews[$index]['category'] ?? 'Berita Utama'),
            'published_at' => $validated['published_at'] ?: ($hotNews[$index]['published_at'] ?? date('d M Y')),
            'status' => $validated['status'] ?: 'terbit',
        ]);

        $beranda['hotNews'] = array_values($hotNews);
        Setting::put('beranda', $beranda);
        IsfCacheService::forget(IsfCacheService::KEY_BERANDA);

        return response()->json(['data' => $hotNews[$index]]);
    }

    public function deleteHotNewsItem(int $id): JsonResponse
    {
        $beranda = Setting::get('beranda', []);
        $hotNews = $beranda['hotNews'] ?? ($this->current()['beranda']['hotNews'] ?? []);

        $hotNews = array_values(array_filter($hotNews, fn ($it) => (int) ($it['id'] ?? 0) !== $id));
        $beranda['hotNews'] = $hotNews;
        Setting::put('beranda', $beranda);
        IsfCacheService::forget(IsfCacheService::KEY_BERANDA);

        return response()->json(['message' => 'Hot news berhasil dihapus.']);
    }

    public function updateHotNews(\Illuminate\Http\Request $request): JsonResponse
    {
        $items = $request->input('items', []);
        $beranda = Setting::get('beranda', []);
        $beranda['hotNews'] = is_array($items) ? array_values($items) : [];
        Setting::put('beranda', $beranda);
        IsfCacheService::forget(IsfCacheService::KEY_BERANDA);

        return response()->json(['data' => $beranda['hotNews']]);
    }

    public function getHotNewsArticleOptions(): JsonResponse
    {
        $categoriesBySlug = Category::query()->terbit()->pluck('name', 'slug')->all();

        $articles = Article::query()->terbit()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(15)
            ->get(['id', 'slug', 'title', 'category', 'link_url', 'published_at'])
            ->map(function ($a) use ($categoriesBySlug) {
                $catSlug = strtolower(trim((string) $a->category));
                // Label pendek kategori (Politik / Hukum / Kebijakan) agar ticker tetap ringkas.
                $catLabel = $a->categoryKey() ? \App\Support\IsfRubrik::label($a->categoryKey()) : ($categoriesBySlug[$catSlug] ?? ($a->category ? ucfirst($a->category) : 'Berita Utama'));
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'category' => $catLabel,
                    'to' => $a->publicUrl(),
                    'url' => $a->publicUrl(),
                    'published_at' => $a->published_at ? $a->published_at->format('d M Y') : date('d M Y'),
                ];
            });

        return response()->json(['data' => $articles]);
    }

    public function syncHotNewsArticles(): JsonResponse
    {
        $categoriesBySlug = Category::query()->terbit()->pluck('name', 'slug')->all();

        $articles = Article::query()->terbit()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $hotNews = [];
        $id = 1;
        foreach ($articles as $a) {
            $catSlug = strtolower(trim((string) $a->category));
            // Label pendek kategori (Politik / Hukum / Kebijakan) agar ticker tetap ringkas.
                $catLabel = $a->categoryKey() ? \App\Support\IsfRubrik::label($a->categoryKey()) : ($categoriesBySlug[$catSlug] ?? ($a->category ? ucfirst($a->category) : 'Berita Utama'));
            $hotNews[] = [
                'id' => $id++,
                'title' => $a->title,
                'category' => $catLabel,
                'to' => $a->publicUrl(),
                'url' => $a->publicUrl(),
                'published_at' => $a->published_at ? $a->published_at->format('d M Y') : date('d M Y'),
                'status' => 'terbit',
            ];
        }

        if (empty($hotNews)) {
            $hotNews = [
                [
                    'id' => 1,
                    'title' => 'RUU Perampasan Aset Masuk Pembahasan Tingkat I di DPR Pekan Ini',
                    'to' => '/beranda',
                    'url' => '/beranda',
                    'category' => 'Hukum',
                    'published_at' => date('d M Y'),
                    'status' => 'terbit',
                ],
                [
                    'id' => 2,
                    'title' => 'Kemenkeu Rilis Panduan Insentif Fiskal Transisi Energi Terbarukan 2026',
                    'to' => '/beranda',
                    'url' => '/beranda',
                    'category' => 'Kebijakan',
                    'published_at' => date('d M Y'),
                    'status' => 'terbit',
                ],
            ];
        }

        $beranda = Setting::get('beranda', []);
        $beranda['hotNews'] = $hotNews;
        Setting::put('beranda', $beranda);
        IsfCacheService::forget(IsfCacheService::KEY_BERANDA);

        return response()->json([
            'message' => 'Hot news berhasil disinkronkan dengan artikel terkini.',
            'data' => $hotNews,
        ]);
    }

    private function syncFirstActiveTopBar(array &$beranda, array $topBars): void
    {
        $firstActive = collect($topBars)->first(fn ($item) => ($item['status'] ?? 'terbit') === 'terbit')
            ?? ($topBars[0] ?? null);

        if ($firstActive) {
            $beranda['topBar'] = [
                'badge' => $firstActive['badge'] ?? 'Sorotan',
                'announcement' => $firstActive['announcement'] ?? '',
                'info' => $firstActive['info'] ?? '',
            ];
        }
    }

    private function current(): array
    {
        $beranda = Setting::get('beranda', []);
        $topBar = $beranda['topBar'] ?? ['badge' => 'Sorotan', 'announcement' => '', 'info' => ''];
        $topBars = $beranda['topBars'] ?? null;

        if ($topBars === null) {
            $topBars = [
                [
                    'id' => 1,
                    'badge' => !empty($topBar['badge']) ? $topBar['badge'] : 'Sorotan',
                    'announcement' => !empty($topBar['announcement']) ? $topBar['announcement'] : 'KUHP baru resmi berlaku sejak 2 Januari 2026 — simak dampaknya bagi dunia usaha.',
                    'info' => !empty($topBar['info']) ? $topBar['info'] : 'Edisi Pekan Ini',
                    'status' => 'terbit',
                ],
                [
                    'id' => 2,
                    'badge' => 'Regulasi',
                    'announcement' => 'DPR jadwalkan rapat kerja lanjutan pembahasan RUU Perampasan Aset bersama pemerintah.',
                    'info' => 'Pembaruan Terkini',
                    'status' => 'terbit',
                ],
                [
                    'id' => 3,
                    'badge' => 'Kebijakan',
                    'announcement' => 'Pedoman tata kelola dan kepatuhan ESG korporasi mulai diimplementasikan bertahap.',
                    'info' => 'Sorotan Industri',
                    'status' => 'terbit',
                ],
            ];
            $beranda['topBars'] = $topBars;
        }

        $hotNews = $beranda['hotNews'] ?? null;
        if ($hotNews === null) {
            $hotNews = [
                [
                    'id' => 1,
                    'title' => 'RUU Perampasan Aset Masuk Pembahasan Tingkat I di DPR Pekan Ini',
                    'to' => '/beranda',
                    'url' => '/beranda',
                    'category' => 'Hukum',
                    'published_at' => date('d M Y'),
                    'status' => 'terbit',
                ],
                [
                    'id' => 2,
                    'title' => 'Kemenkeu Rilis Panduan Insentif Fiskal Transisi Energi Terbarukan 2026',
                    'to' => '/beranda',
                    'url' => '/beranda',
                    'category' => 'Kebijakan',
                    'published_at' => date('d M Y'),
                    'status' => 'terbit',
                ],
                [
                    'id' => 3,
                    'title' => 'IHSG Menguat ke Level 7.450 Didorong Sektor Keuangan dan Konsumer',
                    'to' => '/beranda',
                    'url' => '/beranda',
                    'category' => 'Kebijakan',
                    'published_at' => date('d M Y'),
                    'status' => 'terbit',
                ],
            ];
            $beranda['hotNews'] = $hotNews;
        }

        $mediaSorotan = $beranda['mediaSorotan'] ?? [
            [
                'id' => 1,
                'title' => 'Diskusi Panel: Transformasi Digital dan Kepatuhan Regulasi Perbankan',
                'imageUrl' => '/images/isf/ruu-kuhp.jpeg',
                'mediaUrl' => 'https://www.youtube.com',
                'category' => 'YouTube',
                'publishedAt' => '2026-10-20',
                'order' => 1,
                'status' => 'terbit',
            ],
            [
                'id' => 2,
                'title' => 'Sorotan Visual: Implikasi UU Energi Terbarukan bagi Sektor Industri',
                'imageUrl' => '/images/isf/ruu-energi.jpeg',
                'mediaUrl' => 'https://www.instagram.com',
                'category' => 'Instagram',
                'publishedAt' => '2026-10-18',
                'order' => 2,
                'status' => 'terbit',
            ],
        ];

        return [
            'beranda' => [
                'topBar' => $topBar,
                'topBars' => $topBars,
                'mediaSorotan' => $mediaSorotan,
                'hotNews' => $hotNews,
                'pekanIni' => $beranda['pekanIni'] ?? ['dateStart' => '', 'dateEnd' => ''],
            ],
            'anggota' => Setting::get('anggota', [
                'name' => '', 'role' => '', 'organization' => '', 'plan' => '', 'sektor' => [],
            ]),
        ];
    }

    /**
     * Default konfigurasi slot iklan banner dan modal popup.
     */
    public static function defaultIklan(): array
    {
        return [
            'banner' => [
                'title' => 'Kemitraan Strategis & Solusi Riset Kebijakan ISF 2026',
                'kicker' => 'SPONSOR RESMI',
                'image_url' => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=800&q=80',
                'target_url' => '/register',
                'status' => 'terbit',
                'start_date' => null,
                'end_date' => null,
            ],
            'popup' => [
                'is_active' => true,
                'type' => 'banner',
                'title' => 'Indonesia Strategy Forum — Annual Summit & Briefing 2026',
                'image_url' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=1200&q=80',
                'video_url' => 'https://www.youtube.com/watch?v=aBxlmrCPjBA',
                'video_duration' => 30,
                'target_url' => '/register',
                'status' => 'terbit',
                'start_date' => null,
                'end_date' => null,
            ],
        ];
    }

    /**
     * Default list data iklan untuk tampilan tabel CMS.
     */
    public static function defaultIklanItems(): array
    {
        return [
            [
                'id' => 1,
                'placement' => 'spanduk',
                'type' => 'banner',
                'title' => 'Kemitraan Strategis & Solusi Riset Kebijakan ISF 2026',
                'kicker' => 'SPONSOR RESMI',
                'image_url' => '/images/isf-ad-banner-default.jpg',
                'video_url' => '',
                'video_duration' => 30,
                'interval_minutes' => 0,
                'target_url' => '/register',
                'status' => 'terbit',
                'is_active' => true,
                'order' => 1,
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-15',
            ],
            [
                'id' => 2,
                'placement' => 'popup',
                'type' => 'banner',
                'title' => 'Indonesia Strategy Forum — Annual Summit & Briefing 2026',
                'kicker' => 'SPONSOR RESMI',
                'image_url' => '/images/isf-ad-popup-default.jpg',
                'video_url' => '',
                'video_duration' => 30,
                'interval_minutes' => 0,
                'target_url' => '/register',
                'status' => 'terbit',
                'is_active' => true,
                'order' => 1,
                'start_date' => '2026-11-05',
                'end_date' => '2026-11-20',
            ],
            [
                'id' => 3,
                'placement' => 'popup',
                'type' => 'video',
                'title' => 'Briefing Video Kebijakan ISF 2026',
                'kicker' => 'SPONSOR RESMI',
                'image_url' => '',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'video_duration' => 30,
                'interval_minutes' => 10,
                'target_url' => '/register',
                'status' => 'terbit',
                'is_active' => true,
                'order' => 2,
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-18',
            ],
            [
                'id' => 4,
                'placement' => 'spanduk',
                'type' => 'banner',
                'title' => 'Program Akselerasi Riset & Regulasi Industri Hijau',
                'kicker' => 'MITRA ENERGI',
                'image_url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80',
                'video_url' => '',
                'video_duration' => 30,
                'interval_minutes' => 0,
                'target_url' => '/register',
                'status' => 'draf',
                'is_active' => false,
                'order' => 2,
                'start_date' => '2026-11-16',
                'end_date' => '2026-11-30',
            ],
            [
                'id' => 5,
                'placement' => 'popup',
                'type' => 'banner',
                'title' => 'Peluncuran Buku Putih Transformasi Fiskal & Tata Kelola',
                'kicker' => 'PUBLIKASI KHUSUS',
                'image_url' => 'https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&w=800&q=80',
                'video_url' => '',
                'video_duration' => 30,
                'interval_minutes' => 15,
                'target_url' => '/register',
                'status' => 'draf',
                'is_active' => false,
                'order' => 3,
                'start_date' => '2026-12-01',
                'end_date' => '2026-12-15',
            ],
        ];
    }

    /**
     * Sinkronisasi data iklan items dengan konfigurasi iklan beranda.
     */
    protected function syncIklanSettings(array $items): void
    {
        Setting::put('iklan_items', array_values($items));

        $defaults = self::defaultIklan();
        $current = Setting::get('iklan', $defaults);

        $firstBanner = collect($items)
            ->filter(fn ($it) => in_array($it['placement'], ['spanduk', 'banner']))
            ->where('is_active', true)
            ->sortBy('order')
            ->first();

        $firstPopupBanner = collect($items)
            ->filter(fn ($it) => in_array($it['placement'], ['popup_banner', 'popup']) && ($it['type'] ?? 'banner') === 'banner')
            ->where('is_active', true)
            ->sortBy('order')
            ->first();

        $firstPopupVideo = collect($items)
            ->filter(fn ($it) => in_array($it['placement'], ['popup_video', 'popup_duration', 'popup']) && ($it['type'] ?? '') === 'video')
            ->where('is_active', true)
            ->sortBy('order')
            ->first();

        $firstPopup = collect($items)
            ->filter(fn ($it) => in_array($it['placement'], ['popup_banner', 'popup_video', 'popup_duration', 'popup']))
            ->where('is_active', true)
            ->sortBy('order')
            ->first();

        $banner = $firstBanner ? [
            'title' => $firstBanner['title'] ?? '',
            'kicker' => $firstBanner['kicker'] ?? 'SPONSOR RESMI',
            'image_url' => $firstBanner['image_url'] ?? '',
            'target_url' => $firstBanner['target_url'] ?? '',
            'status' => $firstBanner['status'] ?? 'terbit',
            'is_active' => (bool) ($firstBanner['is_active'] ?? true),
            'start_date' => $firstBanner['start_date'] ?? null,
            'end_date' => $firstBanner['end_date'] ?? null,
        ] : $current['banner'];

        $popup = $firstPopup ? [
            'is_active' => (bool) ($firstPopup['is_active'] ?? true),
            'type' => $firstPopup['type'] ?? ($firstPopup['placement'] === 'popup_video' ? 'video' : 'banner'),
            'title' => $firstPopup['title'] ?? '',
            'image_url' => $firstPopup['image_url'] ?? '',
            'video_url' => $firstPopup['video_url'] ?? '',
            'video_duration' => (int) ($firstPopup['video_duration'] ?? 30),
            'target_url' => $firstPopup['target_url'] ?? '',
            'status' => $firstPopup['status'] ?? 'terbit',
            'start_date' => $firstPopup['start_date'] ?? null,
            'end_date' => $firstPopup['end_date'] ?? null,
        ] : $current['popup'];

        Setting::put('iklan', [
            'banner' => $banner,
            'popup' => $popup,
        ]);
    }

    public static function normalizeCategory(string $placement, string $type = ''): string
    {
        if (in_array($placement, ['spanduk', 'banner'])) {
            return 'spanduk';
        }
        if (in_array($placement, ['popup_duration', 'popup_video']) || ($placement === 'popup' && $type === 'video')) {
            return 'popup_duration';
        }
        return 'popup';
    }

    /**
     * GET /api/cms/iklan
     */
    public function getIklan(\Illuminate\Http\Request $request): JsonResponse
    {
        $items = Setting::get('iklan_items');
        if (!is_array($items) || count($items) < 5) {
            $items = self::defaultIklanItems();
            Setting::put('iklan_items', $items);
            $this->syncIklanSettings($items);
        } else {
            $today = now()->format('Y-m-d');
            $changed = false;

            // Auto-heal data dummy lama jika ada
            foreach ($items as &$it) {
                if (($it['id'] ?? 0) === 1 && ($it['end_date'] ?? '') === '2026-11-25') {
                    $it['end_date'] = '2026-11-15';
                    $changed = true;
                }
                if (($it['id'] ?? 0) === 4 && ($it['start_date'] ?? '') === '2026-11-20') {
                    $it['start_date'] = '2026-11-16';
                    $changed = true;
                }
            }
            unset($it);

            // 1. Nonaktifkan iklan body yang tanggal akhirnya sudah lewat (Berakhir)
            foreach ($items as &$it) {
                if (in_array($it['placement'] ?? '', ['spanduk', 'banner'])) {
                    if (!empty($it['end_date']) && $it['end_date'] < $today && !empty($it['is_active'])) {
                        $it['is_active'] = false;
                        $changed = true;
                    }
                }
            }
            unset($it);

            // 2. Evaluasi iklan body aktif: hanya boleh maksimal 1 iklan body yang aktif
            $activeBodyIndices = [];
            foreach ($items as $idx => $it) {
                if (in_array($it['placement'] ?? '', ['spanduk', 'banner']) && !empty($it['is_active'])) {
                    $activeBodyIndices[] = $idx;
                }
            }

            if (count($activeBodyIndices) > 1) {
                // Cari iklan body yang saat ini dalam periode hari berjalan (start_date <= today <= end_date)
                $keepActiveIdx = null;
                foreach ($activeBodyIndices as $idx) {
                    $s = $items[$idx]['start_date'] ?? null;
                    $e = $items[$idx]['end_date'] ?? null;
                    $inRange = true;
                    if ($s && $s > $today) $inRange = false;
                    if ($e && $e < $today) $inRange = false;
                    if ($inRange) {
                        $keepActiveIdx = $idx;
                        break;
                    }
                }
                if ($keepActiveIdx === null) {
                    $keepActiveIdx = $activeBodyIndices[0];
                }
                foreach ($activeBodyIndices as $idx) {
                    if ($idx !== $keepActiveIdx) {
                        $items[$idx]['is_active'] = false;
                        $changed = true;
                    }
                }
            } elseif (count($activeBodyIndices) === 0) {
                // 3. Jika TIDAK ada iklan body yang aktif, auto-aktifkan iklan terjadwal yang periodenya aktif hari ini
                foreach ($items as &$it) {
                    if (in_array($it['placement'] ?? '', ['spanduk', 'banner'])) {
                        $s = $it['start_date'] ?? null;
                        $e = $it['end_date'] ?? null;
                        $inRange = true;
                        if ($s && $s > $today) $inRange = false;
                        if ($e && $e < $today) $inRange = false;

                        if ($inRange && ($it['status'] ?? 'terbit') === 'terbit') {
                            $it['is_active'] = true;
                            $changed = true;
                            break;
                        }
                    }
                }
                unset($it);
            }

            if ($changed) {
                Setting::put('iklan_items', $items);
                $this->syncIklanSettings($items);
            }
        }

        $q = trim((string) $request->input('q', ''));
        $status = (string) $request->input('status', '');

        $filtered = $items;

        if ($q !== '') {
            $filtered = array_values(array_filter($filtered, function ($it) use ($q) {
                return str_contains(strtolower($it['title'] ?? ''), strtolower($q))
                    || str_contains(strtolower($it['kicker'] ?? ''), strtolower($q))
                    || str_contains(strtolower($it['target_url'] ?? ''), strtolower($q));
            }));
        }

        if ($status !== '') {
            $filtered = array_values(array_filter($filtered, function ($it) use ($status) {
                return ($it['status'] ?? 'terbit') === $status;
            }));
        }

        usort($filtered, fn ($a, $b) => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));

        $defaults = self::defaultIklan();
        $legacy = Setting::get('iklan', $defaults);

        return response()->json([
            'data' => $filtered,
            'items' => $filtered,
            'total' => count($filtered),
            'banner' => $legacy['banner'] ?? $defaults['banner'],
            'popup' => $legacy['popup'] ?? $defaults['popup'],
        ]);
    }

    /**
     * POST /api/cms/iklan
     */
    public function createIklan(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'placement' => 'required|string|in:spanduk,popup_banner,popup_video,popup_duration,banner,popup',
            'type' => 'nullable|string|in:banner,video',
            'kicker' => 'nullable|string|max:100',
            'image_url' => 'nullable|string|max:1000',
            'video_url' => 'nullable|string|max:1000',
            'video_duration' => 'nullable|integer|min:5|max:300',
            'interval_minutes' => 'nullable|integer|min:0|max:1440',
            'target_url' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:terbit,draf',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'order' => 'nullable|integer',
        ]);

        $items = Setting::get('iklan_items', self::defaultIklanItems());
        $nextId = (int) (collect($items)->max('id') ?? 0) + 1;
        $maxOrder = (int) (collect($items)->max('order') ?? 0);
        $order = isset($validated['order']) && $validated['order'] !== null && $validated['order'] !== ''
            ? (int) $validated['order']
            : ($maxOrder + 1);

        $placement = $validated['placement'];
        $type = $validated['type'] ?? (in_array($placement, ['popup_video', 'popup_duration']) ? 'video' : 'banner');

        if (in_array($placement, ['spanduk', 'banner']) && !empty($validated['start_date'])) {
            $newStart = $validated['start_date'];
            $newEnd = $validated['end_date'] ?: '2099-12-31';

            foreach ($items as $existing) {
                if (!in_array($existing['placement'] ?? '', ['spanduk', 'banner'])) continue;
                if (empty($existing['start_date'])) continue;

                $existStart = $existing['start_date'];
                $existEnd = $existing['end_date'] ?: '2099-12-31';

                if ($newStart <= $existEnd && $newEnd >= $existStart) {
                    return response()->json([
                        'message' => 'Tanggal penayangan bertabrakan dengan jadwal iklan body lain "' . ($existing['title'] ?? 'Iklan') . '" (' . $existStart . ' s/d ' . ($existing['end_date'] ?: 'Seterusnya') . '). Iklan body tidak boleh memiliki tanggal tayang ganda.',
                        'errors' => [
                            'start_date' => ['Tanggal penayangan bertabrakan dengan jadwal iklan body lain.'],
                        ],
                    ], 422);
                }
            }
        }

        $newItemIsActive = isset($validated['is_active']) ? (bool) $validated['is_active'] : true;
        if ($newItemIsActive && in_array($placement, ['spanduk', 'banner'])) {
            foreach ($items as &$other) {
                if (in_array($other['placement'] ?? '', ['spanduk', 'banner'])) {
                    $other['is_active'] = false;
                }
            }
            unset($other);
        }

        $newItem = [
            'id' => $nextId,
            'placement' => $placement,
            'type' => $type,
            'title' => $validated['title'],
            'kicker' => $validated['kicker'] ?: 'SPONSOR RESMI',
            'image_url' => $validated['image_url'] ?? '',
            'video_url' => $validated['video_url'] ?? '',
            'video_duration' => (int) ($validated['video_duration'] ?? 30),
            'interval_minutes' => (int) ($validated['interval_minutes'] ?? 10),
            'target_url' => $validated['target_url'] ?? '/register',
            'status' => $validated['status'] ?: 'terbit',
            'is_active' => $newItemIsActive,
            'order' => $order,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ];

        $items[] = $newItem;

        usort($items, fn ($a, $b) => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));
        $this->syncIklanSettings($items);

        return response()->json(['data' => $newItem, 'message' => 'Iklan berhasil ditambahkan.'], 201);
    }

    /**
     * GET /api/cms/iklan/{id}
     */
    public function showIklan(int $id): JsonResponse
    {
        $items = Setting::get('iklan_items', self::defaultIklanItems());
        $item = collect($items)->first(fn ($it) => (int) ($it['id'] ?? 0) === $id);

        if (!$item) {
            abort(404, 'Iklan tidak ditemukan.');
        }

        return response()->json(['data' => $item]);
    }

    /**
     * PUT /api/cms/iklan/{id}
     */
    public function updateIklanItem(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'placement' => 'required|string|in:spanduk,popup_banner,popup_video,popup_duration,banner,popup',
            'type' => 'nullable|string|in:banner,video',
            'kicker' => 'nullable|string|max:100',
            'image_url' => 'nullable|string|max:1000',
            'video_url' => 'nullable|string|max:1000',
            'video_duration' => 'nullable|integer|min:5|max:300',
            'interval_minutes' => 'nullable|integer|min:0|max:1440',
            'target_url' => 'nullable|string|max:1000',
            'status' => 'nullable|string|in:terbit,draf',
            'is_active' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'order' => 'nullable|integer',
        ]);

        $items = Setting::get('iklan_items', self::defaultIklanItems());
        $index = collect($items)->search(fn ($it) => (int) ($it['id'] ?? 0) === $id);
        if ($index === false) {
            abort(404, 'Iklan tidak ditemukan.');
        }

        $placement = $validated['placement'];
        $type = $validated['type'] ?? (in_array($placement, ['popup_video', 'popup_duration']) ? 'video' : 'banner');

        if (in_array($placement, ['spanduk', 'banner']) && !empty($validated['start_date'])) {
            $newStart = $validated['start_date'];
            $newEnd = $validated['end_date'] ?: '2099-12-31';

            foreach ($items as $existing) {
                if ((int) ($existing['id'] ?? 0) === $id) continue;
                if (!in_array($existing['placement'] ?? '', ['spanduk', 'banner'])) continue;
                if (empty($existing['start_date'])) continue;

                $existStart = $existing['start_date'];
                $existEnd = $existing['end_date'] ?: '2099-12-31';

                if ($newStart <= $existEnd && $newEnd >= $existStart) {
                    return response()->json([
                        'message' => 'Tanggal penayangan bertabrakan dengan jadwal iklan body lain "' . ($existing['title'] ?? 'Iklan') . '" (' . $existStart . ' s/d ' . ($existing['end_date'] ?: 'Seterusnya') . '). Iklan body tidak boleh memiliki tanggal tayang ganda.',
                        'errors' => [
                            'start_date' => ['Tanggal penayangan bertabrakan dengan jadwal iklan body lain.'],
                        ],
                    ], 422);
                }
            }
        }

        $updatedIsActive = isset($validated['is_active']) ? (bool) $validated['is_active'] : ($items[$index]['is_active'] ?? true);
        if ($updatedIsActive && in_array($placement, ['spanduk', 'banner'])) {
            foreach ($items as $idx => &$other) {
                if ($idx !== $index && in_array($other['placement'] ?? '', ['spanduk', 'banner'])) {
                    $other['is_active'] = false;
                }
            }
            unset($other);
        }

        $items[$index] = array_merge($items[$index], [
            'placement' => $placement,
            'type' => $type,
            'title' => $validated['title'],
            'kicker' => $validated['kicker'] ?: 'SPONSOR RESMI',
            'image_url' => $validated['image_url'] ?? '',
            'video_url' => $validated['video_url'] ?? '',
            'video_duration' => (int) ($validated['video_duration'] ?? ($items[$index]['video_duration'] ?? 30)),
            'interval_minutes' => (int) ($validated['interval_minutes'] ?? ($items[$index]['interval_minutes'] ?? 10)),
            'target_url' => $validated['target_url'] ?? '/register',
            'status' => $validated['status'] ?: 'terbit',
            'is_active' => $updatedIsActive,
            'order' => isset($validated['order']) ? (int) $validated['order'] : ($items[$index]['order'] ?? 1),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        usort($items, fn ($a, $b) => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));
        $this->syncIklanSettings($items);

        return response()->json(['data' => $items[$index], 'message' => 'Iklan berhasil diperbarui.']);
    }

    /**
     * DELETE /api/cms/iklan/{id}
     */
    public function deleteIklanItem(int $id): JsonResponse
    {
        $items = Setting::get('iklan_items', self::defaultIklanItems());
        $items = array_values(array_filter($items, fn ($it) => (int) ($it['id'] ?? 0) !== $id));

        foreach ($items as $idx => &$item) {
            $item['order'] = $idx + 1;
        }
        unset($item);

        $this->syncIklanSettings($items);

        return response()->json(['message' => 'Iklan berhasil dihapus.']);
    }

    /**
     * POST /api/cms/iklan/reorder
     */
    public function reorderIklan(\Illuminate\Http\Request $request): JsonResponse
    {
        $payload = $request->input('items', []);
        $items = Setting::get('iklan_items', self::defaultIklanItems());

        if (is_array($payload) && !empty($payload)) {
            $orderMap = [];
            foreach ($payload as $idx => $entry) {
                if (is_array($entry) && isset($entry['id'])) {
                    $orderMap[(int) $entry['id']] = isset($entry['order']) ? (int) $entry['order'] : ($idx + 1);
                }
            }

            foreach ($items as &$it) {
                $id = (int) ($it['id'] ?? 0);
                if (isset($orderMap[$id])) {
                    $it['order'] = $orderMap[$id];
                }
            }
            unset($it);

            usort($items, fn ($a, $b) => ((int) ($a['order'] ?? 0)) <=> ((int) ($b['order'] ?? 0)));
            $this->syncIklanSettings($items);
        }

        return response()->json(['message' => 'Urutan iklan berhasil diperbarui.']);
    }

    /**
     * POST /api/cms/iklan/{id}/toggle
     */
    public function toggleIklanActive(int $id): JsonResponse
    {
        $items = Setting::get('iklan_items', self::defaultIklanItems());
        $index = collect($items)->search(fn ($it) => (int) ($it['id'] ?? 0) === $id);
        if ($index === false) {
            abort(404, 'Iklan tidak ditemukan.');
        }

        $willBeActive = !($items[$index]['is_active'] ?? true);
        $items[$index]['is_active'] = $willBeActive;

        $placement = $items[$index]['placement'] ?? '';
        if ($willBeActive && in_array($placement, ['spanduk', 'banner'])) {
            foreach ($items as $idx => &$other) {
                if ($idx !== $index && in_array($other['placement'] ?? '', ['spanduk', 'banner'])) {
                    $other['is_active'] = false;
                }
            }
            unset($other);
        }

        $this->syncIklanSettings($items);

        return response()->json([
            'data' => $items[$index],
            'message' => 'Status aktif iklan berhasil diperbarui.',
        ]);
    }

    /**
     * PUT /api/cms/iklan
     */
    public function updateIklan(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'banner' => ['nullable', 'array'],
            'banner.title' => ['nullable', 'string', 'max:255'],
            'banner.kicker' => ['nullable', 'string', 'max:100'],
            'banner.image_url' => ['nullable', 'string', 'max:1000'],
            'banner.target_url' => ['nullable', 'string', 'max:1000'],
            'banner.status' => ['nullable', 'string', 'in:terbit,draf'],
            'banner.start_date' => ['nullable', 'date'],
            'banner.end_date' => ['nullable', 'date'],

            'popup' => ['nullable', 'array'],
            'popup.is_active' => ['nullable', 'boolean'],
            'popup.type' => ['nullable', 'string', 'in:banner,video'],
            'popup.title' => ['nullable', 'string', 'max:255'],
            'popup.image_url' => ['nullable', 'string', 'max:1000'],
            'popup.video_url' => ['nullable', 'string', 'max:1000'],
            'popup.video_duration' => ['nullable', 'integer', 'min:5', 'max:300'],
            'popup.target_url' => ['nullable', 'string', 'max:1000'],
            'popup.status' => ['nullable', 'string', 'in:terbit,draf'],
            'popup.start_date' => ['nullable', 'date'],
            'popup.end_date' => ['nullable', 'date'],
        ]);

        $current = Setting::get('iklan', self::defaultIklan());
        $defaults = self::defaultIklan();

        $banner = array_merge($defaults['banner'], $current['banner'] ?? [], $validated['banner'] ?? []);
        $popup = array_merge($defaults['popup'], $current['popup'] ?? [], $validated['popup'] ?? []);

        if (isset($validated['popup']['is_active'])) {
            $popup['is_active'] = (bool) $validated['popup']['is_active'];
        }

        $data = [
            'banner' => $banner,
            'popup' => $popup,
        ];

        Setting::put('iklan', $data);

        return response()->json([
            'message' => 'Pengaturan iklan berhasil disimpan.',
            'data' => $data,
        ]);
    }
}
