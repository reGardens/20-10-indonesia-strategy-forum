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
            'kebijakan publik' => 'Kebijakan',
            'ekonomi & pasar' => 'Ekonomi',
            'ruu' => 'RUU',
            'kebijakan' => 'Kebijakan',
            'ekonomi' => 'Ekonomi',
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
            ->get(['id', 'title', 'category', 'link_url', 'published_at'])
            ->map(function ($a) use ($categoriesBySlug) {
                $catSlug = strtolower(trim((string) $a->category));
                $catLabel = $categoriesBySlug[$catSlug] ?? ($a->category ? ucfirst($a->category) : 'Berita Utama');
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'category' => $catLabel,
                    'to' => $a->link_url ?: '/beranda',
                    'url' => $a->link_url ?: '/beranda',
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
            $catLabel = $categoriesBySlug[$catSlug] ?? ($a->category ? ucfirst($a->category) : 'Berita Utama');
            $hotNews[] = [
                'id' => $id++,
                'title' => $a->title,
                'category' => $catLabel,
                'to' => $a->link_url ?: '/beranda',
                'url' => $a->link_url ?: '/beranda',
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
                    'category' => 'Ekonomi',
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
}
