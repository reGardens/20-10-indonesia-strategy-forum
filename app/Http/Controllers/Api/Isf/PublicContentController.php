<?php

namespace App\Http\Controllers\Api\Isf;

use App\Http\Controllers\Controller;
use App\Http\Resources\Isf\AlertResource;
use App\Http\Resources\Isf\ArticleResource;
use App\Http\Resources\Isf\ChartResource;
use App\Http\Resources\Isf\InfographicResource;
use App\Http\Resources\Isf\TokohResource;
use App\Models\Isf\Alert;
use App\Models\Isf\Article;
use App\Models\Isf\Banner;
use App\Models\Isf\Briefing;
use App\Models\Isf\Category;
use App\Models\Isf\Chart;
use App\Models\Isf\Infographic;
use App\Models\Isf\Setting;
use App\Models\Isf\Tokoh;
use App\Services\Isf\IsfCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public, read-only ISF content API (GET only). Serves ONLY published
 * ("terbit") content, in the shape used by the public Vue pages.
 * Dilengkapi caching server-side otomatis & diagnostic HTTP headers.
 */
class PublicContentController extends Controller
{
    /**
     * Helper untuk membungkus respons dengan diagnostic cache headers.
     */
    protected function cachedResponse(array $result): JsonResponse
    {
        $response = response()->json($result['data'])
            ->header('X-Cache-Status', $result['status'])
            ->header('X-Cache-Driver', $result['driver']);

        if (!empty($result['error'])) {
            $response->header('X-Cache-Error', substr($result['error'], 0, 100));
        }

        return $response;
    }

    /**
     * GET /api/isf/beranda
     */
    public function beranda(Request $request): JsonResponse
    {
        $result = IsfCacheService::rememberWithStatus(IsfCacheService::KEY_BERANDA, function () use ($request) {
            $settings = Setting::get('beranda', [
                'topBar' => ['badge' => '', 'announcement' => '', 'info' => ''],
                'pekanIni' => ['dateStart' => null, 'dateEnd' => null],
            ]);
            $topBar = $settings['topBar'] ?? ['badge' => 'Sorotan', 'announcement' => '', 'info' => ''];
            $topBars = $settings['topBars'] ?? null;
            if ($topBars === null) {
                $topBars = [
                    [
                        'badge' => !empty($topBar['badge']) ? $topBar['badge'] : 'Sorotan',
                        'announcement' => !empty($topBar['announcement']) ? $topBar['announcement'] : 'KUHP baru resmi berlaku sejak 2 Januari 2026 — simak dampaknya bagi dunia usaha.',
                        'info' => !empty($topBar['info']) ? $topBar['info'] : 'Edisi Pekan Ini',
                        'status' => 'terbit',
                    ],
                    [
                        'badge' => 'Regulasi',
                        'announcement' => 'DPR jadwalkan rapat kerja lanjutan pembahasan RUU Perampasan Aset bersama pemerintah.',
                        'info' => 'Pembaruan Terkini',
                        'status' => 'terbit',
                    ],
                    [
                        'badge' => 'Kebijakan',
                        'announcement' => 'Pedoman tata kelola dan kepatuhan ESG korporasi mulai diimplementasikan bertahap.',
                        'info' => 'Sorotan Industri',
                        'status' => 'terbit',
                    ],
                ];
            }
            $activeTopBars = array_values(array_filter($topBars, fn ($item) => ($item['status'] ?? 'terbit') === 'terbit'));
            if (empty($activeTopBars) && !empty($topBar['announcement'])) {
                $activeTopBars = [$topBar];
            }

            $banners = Banner::query()->terbit()
                ->orderBy('order', 'asc')
                ->orderByDesc('id')
                ->get()
                ->map(fn (Banner $b) => [
                    'id' => (string) $b->id,
                    'kicker' => $b->kicker,
                    'title' => $b->title,
                    'deck' => $b->deck,
                    'to' => $b->to ?: '#',
                    'imageUrl' => $b->image_url,
                    'imageLabel' => $b->image_label ?: $b->title,
                ]);

            $hero = Article::currentHero();

            $terkini = Article::query()->terbit()->where('show_in_terkini', true)
                ->orderByDesc('published_at')->orderByDesc('id')->limit(6)->get();

            $infografik = Infographic::query()->terbit()
                ->orderByDesc('published_at')->orderByDesc('id')->limit(4)->get();

            $charts = Chart::query()->terbit()
                ->orderByDesc('is_beranda')
                ->orderBy('order')
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();
            $chart = $charts->first();

            $pekanIniItems = [];
            if ($tokoh = Tokoh::currentFeatured()) {
                $pekanIniItems[] = (new TokohResource($tokoh))->pekanIniItem();
            }

            $mediaSorotan = $settings['mediaSorotan'] ?? [];
            $activeMedia = array_values(array_filter($mediaSorotan, fn ($item) => ($item['status'] ?? 'terbit') === 'terbit'));
            if (empty($activeMedia)) {
                $activeMedia = $this->defaultMediaItems();
            }
            usort($activeMedia, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

            $hotNews = $settings['hotNews'] ?? null;
            $activeHotNews = is_array($hotNews)
                ? array_values(array_filter($hotNews, fn ($item) => ($item['status'] ?? 'terbit') === 'terbit'))
                : [];

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

            $activeHotNews = array_map(function ($it) use ($categoryMap) {
                $cat = $it['category'] ?? '';
                $key = strtolower(trim((string) $cat));
                if (isset($categoryMap[$key])) {
                    $it['category'] = $categoryMap[$key];
                }
                return $it;
            }, $activeHotNews);

            if (empty($activeHotNews)) {
                $categoriesBySlug = Category::query()->terbit()->pluck('name', 'slug')->all();
                $activeHotNews = Article::query()->terbit()
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get()
                    ->map(function (Article $a) use ($categoriesBySlug) {
                        $catSlug = strtolower(trim((string) $a->category));
                        $catLabel = $categoriesBySlug[$catSlug] ?? ($a->category ? ucfirst($a->category) : 'Berita Utama');
                        return [
                            'id' => (string) $a->id,
                            'title' => $a->title,
                            'category' => $catLabel,
                            'to' => $a->link_url ?: '/beranda',
                            'url' => $a->link_url ?: '/beranda',
                            'published_at' => $a->published_at ? $a->published_at->format('d M Y') : date('d M Y'),
                            'status' => 'terbit',
                        ];
                    })
                    ->all();
            }

            if (empty($activeHotNews)) {
                $activeHotNews = [
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

            $rawIklan = Setting::get('iklan', \App\Http\Controllers\Api\Isf\Cms\SettingController::defaultIklan());
            $defaultIklan = \App\Http\Controllers\Api\Isf\Cms\SettingController::defaultIklan();

            $iklanItems = Setting::get('iklan_items');
            $now = now();

            if (is_array($iklanItems) && !empty($iklanItems)) {
                $isItemActive = function ($it) use ($now) {
                    if (empty($it['is_active']) || ($it['status'] ?? 'terbit') !== 'terbit') return false;
                    if (!empty($it['start_date']) && $now->lt(\Carbon\Carbon::parse($it['start_date'])->startOfDay())) return false;
                    if (!empty($it['end_date']) && $now->gt(\Carbon\Carbon::parse($it['end_date'])->endOfDay())) return false;
                    return true;
                };

                $activeBannerItem = collect($iklanItems)
                    ->filter(fn ($it) => in_array($it['placement'], ['spanduk', 'banner']))
                    ->filter($isItemActive)
                    ->sortBy('order')
                    ->first();

                // Jika tidak ada banner aktif, cek apakah ada iklan body terjadwal yang periodenya aktif hari ini
                if (!$activeBannerItem) {
                    $autoBannerCandidate = collect($iklanItems)
                        ->filter(fn ($it) => in_array($it['placement'], ['spanduk', 'banner']))
                        ->filter(function ($it) use ($now) {
                            if (($it['status'] ?? 'terbit') !== 'terbit') return false;
                            if (!empty($it['start_date']) && $now->lt(\Carbon\Carbon::parse($it['start_date'])->startOfDay())) return false;
                            if (!empty($it['end_date']) && $now->gt(\Carbon\Carbon::parse($it['end_date'])->endOfDay())) return false;
                            return true;
                        })
                        ->sortBy('order')
                        ->first();

                    if ($autoBannerCandidate) {
                        $activeBannerItem = $autoBannerCandidate;
                        $syncNeeded = false;
                        foreach ($iklanItems as &$it) {
                            if ($it['id'] === $autoBannerCandidate['id']) {
                                if (empty($it['is_active'])) {
                                    $it['is_active'] = true;
                                    $syncNeeded = true;
                                }
                            } elseif (in_array($it['placement'], ['spanduk', 'banner'])) {
                                if (!empty($it['is_active'])) {
                                    $it['is_active'] = false;
                                    $syncNeeded = true;
                                }
                            }
                        }
                        unset($it);
                        if ($syncNeeded) {
                            Setting::put('iklan_items', $iklanItems);
                        }
                    }
                }

                $activePopupBannerItem = collect($iklanItems)
                    ->filter(fn ($it) => in_array($it['placement'], ['popup_banner', 'popup']) && ($it['type'] ?? 'banner') === 'banner')
                    ->filter($isItemActive)
                    ->sortBy('order')
                    ->first();

                $activePopupVideoItem = collect($iklanItems)
                    ->filter(fn ($it) => in_array($it['placement'], ['popup_video', 'popup_duration', 'popup']) && ($it['type'] ?? '') === 'video')
                    ->filter($isItemActive)
                    ->sortBy('order')
                    ->first();

                $activeTopPopup = collect($iklanItems)
                    ->filter(fn ($it) => in_array($it['placement'], ['popup_banner', 'popup_video', 'popup_duration', 'popup']))
                    ->filter($isItemActive)
                    ->sortBy('order')
                    ->first();

                $bannerConfig = $activeBannerItem ?: array_merge($defaultIklan['banner'], $rawIklan['banner'] ?? []);
                $popupConfig = $activeTopPopup ?: array_merge($defaultIklan['popup'], $rawIklan['popup'] ?? []);
                $popupBannerConfig = $activePopupBannerItem ?: array_merge($defaultIklan['popup'], ['type' => 'banner']);
                $popupVideoConfig = $activePopupVideoItem ?: array_merge($defaultIklan['popup'], ['type' => 'video', 'video_duration' => 30]);

                $bannerConfig['is_active'] = !empty($activeBannerItem);
                $popupConfig['is_active'] = !empty($activeTopPopup);
                $popupBannerConfig['is_active'] = !empty($activePopupBannerItem);
                $popupVideoConfig['is_active'] = !empty($activePopupVideoItem);
            } else {
                $bannerConfig = array_merge($defaultIklan['banner'], $rawIklan['banner'] ?? []);
                $popupConfig = array_merge($defaultIklan['popup'], $rawIklan['popup'] ?? []);
                $popupBannerConfig = array_merge($popupConfig, ['type' => 'banner']);
                $popupVideoConfig = array_merge($popupConfig, ['type' => 'video', 'video_duration' => 30]);

                // Check banner active status & date window
                $bannerActive = ($bannerConfig['status'] ?? 'terbit') === 'terbit';
                if ($bannerActive && !empty($bannerConfig['start_date'])) {
                    if ($now->lt(\Carbon\Carbon::parse($bannerConfig['start_date'])->startOfDay())) {
                        $bannerActive = false;
                    }
                }
                if ($bannerActive && !empty($bannerConfig['end_date'])) {
                    if ($now->gt(\Carbon\Carbon::parse($bannerConfig['end_date'])->endOfDay())) {
                        $bannerActive = false;
                    }
                }
                $bannerConfig['is_active'] = $bannerActive;

                // Check popup active status & date window
                $popupActive = !empty($popupConfig['is_active']) && ($popupConfig['status'] ?? 'terbit') === 'terbit';
                if ($popupActive && !empty($popupConfig['start_date'])) {
                    if ($now->lt(\Carbon\Carbon::parse($popupConfig['start_date'])->startOfDay())) {
                        $popupActive = false;
                    }
                }
                if ($popupActive && !empty($popupConfig['end_date'])) {
                    if ($now->gt(\Carbon\Carbon::parse($popupConfig['end_date'])->endOfDay())) {
                        $popupActive = false;
                    }
                }
                $popupConfig['is_active'] = $popupActive;
                $popupBannerConfig['is_active'] = $popupActive && ($popupConfig['type'] ?? 'banner') === 'banner';
                $popupVideoConfig['is_active'] = $popupActive && ($popupConfig['type'] ?? '') === 'video';
            }

            return [
                'topBar' => $settings['topBar'] ?? null,
                'topBars' => $activeTopBars,
                'banners' => $banners,
                'hotNews' => $activeHotNews,
                'hero' => $hero ? (new ArticleResource($hero))->hero() : null,
                'factBlocks' => $hero?->fact_blocks ?? [],
                'chart' => $chart ? (new ChartResource($chart))->resolve($request) : null,
                'charts' => ChartResource::collection($charts)->resolve($request),
                'mediaSorotan' => $activeMedia,
                'terkini' => ArticleResource::collection($terkini)->resolve($request),
                'iklan' => [
                    'banner' => [
                        'title' => $bannerConfig['title'] ?? '',
                        'kicker' => $bannerConfig['kicker'] ?? 'SPONSOR RESMI',
                        'imageUrl' => $bannerConfig['image_url'] ?? '',
                        'targetUrl' => $bannerConfig['target_url'] ?? '',
                        'status' => $bannerConfig['status'] ?? 'terbit',
                        'isActive' => $bannerConfig['is_active'] ?? true,
                        'startDate' => $bannerConfig['start_date'] ?? null,
                        'endDate' => $bannerConfig['end_date'] ?? null,
                    ],
                    'popup' => [
                        'title' => $popupConfig['title'] ?? '',
                        'type' => $popupConfig['type'] ?? 'banner',
                        'imageUrl' => $popupConfig['image_url'] ?? '',
                        'videoUrl' => $popupConfig['video_url'] ?? '',
                        'videoDuration' => (int) ($popupConfig['video_duration'] ?? 30),
                        'targetUrl' => $popupConfig['target_url'] ?? '',
                        'status' => $popupConfig['status'] ?? 'terbit',
                        'isActive' => $popupConfig['is_active'] ?? true,
                        'startDate' => $popupConfig['start_date'] ?? null,
                        'endDate' => $popupConfig['end_date'] ?? null,
                    ],
                    'popup_banner' => [
                        'title' => $popupBannerConfig['title'] ?? '',
                        'type' => 'banner',
                        'imageUrl' => $popupBannerConfig['image_url'] ?? '',
                        'targetUrl' => $popupBannerConfig['target_url'] ?? '',
                        'status' => $popupBannerConfig['status'] ?? 'terbit',
                        'isActive' => $popupBannerConfig['is_active'] ?? true,
                        'startDate' => $popupBannerConfig['start_date'] ?? null,
                        'endDate' => $popupBannerConfig['end_date'] ?? null,
                    ],
                    'popup_video' => [
                        'title' => $popupVideoConfig['title'] ?? '',
                        'type' => 'video',
                        'videoUrl' => $popupVideoConfig['video_url'] ?? '',
                        'videoDuration' => (int) ($popupVideoConfig['video_duration'] ?? 30),
                        'targetUrl' => $popupVideoConfig['target_url'] ?? '',
                        'status' => $popupVideoConfig['status'] ?? 'terbit',
                        'isActive' => $popupVideoConfig['is_active'] ?? true,
                        'startDate' => $popupVideoConfig['start_date'] ?? null,
                        'endDate' => $popupVideoConfig['end_date'] ?? null,
                    ],
                ],
                'infografik' => InfographicResource::collection($infografik)->resolve($request),
                'pekanIni' => [
                    'dateStart' => $settings['pekanIni']['dateStart'] ?? null,
                    'dateEnd' => $settings['pekanIni']['dateEnd'] ?? null,
                    'items' => $pekanIniItems,
                ],
            ];
        });

        return $this->cachedResponse($result);
    }

    /**
     * GET /api/isf/infografik
     */
    public function infografik(Request $request): JsonResponse
    {
        $result = IsfCacheService::rememberWithStatus(IsfCacheService::KEY_INFOGRAFIK, function () use ($request) {
            $infografik = Infographic::query()->terbit()
                ->orderByDesc('published_at')->orderByDesc('id')->get();

            return [
                'data' => InfographicResource::collection($infografik)->resolve($request),
            ];
        });

        return $this->cachedResponse($result);
    }

    /**
     * GET /api/isf/tokoh/{slug}
     */
    public function tokoh(Request $request, string $slug): JsonResponse
    {
        $tokoh = Tokoh::query()->terbit()->where('slug', $slug)->first();

        if (! $tokoh) {
            return $this->notFound('Tokoh tidak ditemukan.');
        }

        $result = IsfCacheService::rememberWithStatus(IsfCacheService::PREFIX_TOKOH . $slug, function () use ($request, $tokoh) {
            return (new TokohResource($tokoh))->resolve($request);
        });

        return $this->cachedResponse($result);
    }

    /**
     * GET /api/isf/anggota
     */
    public function anggota(Request $request): JsonResponse
    {
        $result = IsfCacheService::rememberWithStatus(IsfCacheService::KEY_ANGGOTA, function () use ($request) {
            $alerts = Alert::query()->terbit()->orderByDesc('date')->orderByDesc('id')->get();

            // "Tautan Langsung" is navigation: computed from the current weekly content.
            // The public briefing preview 404s without a published briefing, so link it only when one exists.
            $tautan = [];
            if (Briefing::query()->terbit()->exists()) {
                $tautan[] = ['label' => 'Pratinjau Email Briefing', 'href' => '/anggota/briefing/preview', 'type' => 'external'];
            }
            if ($tokoh = Tokoh::currentFeatured()) {
                $tautan[] = ['label' => 'Profil Tokoh Pekan Ini', 'to' => "/tokoh/{$tokoh->slug}"];
            }
            $tautan[] = ['label' => 'Beranda ISF', 'to' => '/beranda'];

            return [
                'user' => Setting::get('anggota', [
                    'name' => '', 'role' => '', 'organization' => '', 'plan' => '', 'sektor' => [],
                ]),
                'alerts' => AlertResource::collection($alerts)->resolve($request),
                'tautanLangsung' => $tautan,
            ];
        });

        return $this->cachedResponse($result);
    }


    /**
     * Endpoint diagnostik & auto-heal Redis jika mengalami kendala disk snapshot.
     */
    public function cacheHeal(): JsonResponse
    {
        $driver = (string) config('cache.default', 'file');
        $healed = IsfCacheService::healRedisMisconf();

        $testResult = 'skipped';
        if ($driver === 'redis' || $healed) {
            try {
                \Illuminate\Support\Facades\Cache::put('isf:test:ping', 'pong', 60);
                $testVal = \Illuminate\Support\Facades\Cache::get('isf:test:ping');
                $testResult = $testVal === 'pong' ? 'SUCCESS (WRITE & READ OK)' : 'FAILED_READ';
            } catch (\Throwable $e) {
                $testResult = 'FAILED: ' . $e->getMessage();
            }
        }

        return response()->json([
            'driver' => $driver,
            'auto_heal_executed' => $healed,
            'cache_write_test' => $testResult,
            'message' => $healed
                ? 'Redis stop-writes-on-bgsave-error berhasil diset ke no.'
                : 'Driver bukan redis atau perintah CONFIG tidak didukung.',
        ]);
    }

    /**
     * Detail media sorotan publik dengan embed video & artikel ulasan pendamping.
     */
    public function mediaDetail(Request $request, string $id): JsonResponse
    {
        $settings = Setting::get('beranda', []);
        $mediaSorotan = $settings['mediaSorotan'] ?? [];
        $activeMedia = array_values(array_filter($mediaSorotan, fn ($item) => ($item['status'] ?? 'terbit') === 'terbit'));

        if (empty($activeMedia)) {
            $activeMedia = $this->defaultMediaItems();
        }

        $item = collect($activeMedia)->first(function ($it) use ($id) {
            return (string) ($it['id'] ?? '') === (string) $id
                || (string) ($it['slug'] ?? '') === (string) $id;
        });

        if (!$item) {
            return $this->notFound('Media sorotan tidak ditemukan.');
        }

        // Normalisasi URL YouTube dummy agar player embed video selalu memuat video valid
        if (empty($item['mediaUrl']) || $item['mediaUrl'] === 'https://www.youtube.com' || $item['mediaUrl'] === 'https://www.youtube.com/') {
            $item['mediaUrl'] = 'https://www.youtube.com/watch?v=aBxlmrCPjBA';
        }

        if (empty($item['content'])) {
            $item['content'] = $this->generateDefaultCompanionArticle($item);
        }
        if (empty($item['summary'])) {
            $item['summary'] = $item['title'] . ' — Diskusi dan ulasan strategis dari Dewan Pakar Indonesia Strategy Forum.';
        }
        if (empty($item['author'])) {
            $item['author'] = 'Tim Redaksi ISF';
        }

        $related = array_values(array_filter($activeMedia, fn ($it) => (string) ($it['id'] ?? '') !== (string) ($item['id'] ?? '')));
        $related = array_slice($related, 0, 4);

        $recentArticles = Article::query()->terbit()->latest('published_at')->take(4)->get();
        $recentArticlesFormatted = ArticleResource::collection($recentArticles)->resolve($request);

        return response()->json([
            'data' => $item,
            'related' => $related,
            'recentArticles' => $recentArticlesFormatted,
        ]);
    }

    /**
     * Default fallback media items untuk YouTube & TikTok.
     */
    private function defaultMediaItems(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'Diskusi Panel: Transformasi Digital dan Kepatuhan Regulasi Perbankan',
                'imageUrl' => '',
                'mediaUrl' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'category' => 'YouTube',
                'publishedAt' => '2026-10-20',
                'order' => 1,
                'status' => 'terbit',
                'is_popular' => true,
                'author' => 'Dewan Pakar Ekonomi & Regulasi ISF',
                'summary' => 'Kajian mendalam regulator dan praktisi perbankan terkait kepatuhan tata kelola AI, perlindungan privasi data nasabah, dan kesiapan sistem pembayaran nasional.',
                'content' => '',
            ],
            [
                'id' => 2,
                'title' => 'Sorotan Kilat: Implikasi Regulasi Transisi Energi Terbarukan bagi Industri',
                'imageUrl' => '',
                'mediaUrl' => 'https://www.tiktok.com/@indonesiastrategyforum/video/7200000000000000000',
                'category' => 'TikTok',
                'publishedAt' => '2026-10-18',
                'order' => 2,
                'status' => 'terbit',
                'is_popular' => false,
                'author' => 'Divisi Riset ISF',
                'summary' => 'Ringkasan visual 60 detik mengenai dampak pengenaan standar emisi baru bagi manufaktur dan skema insentif fiskal hijau.',
                'content' => '',
            ],
            [
                'id' => 3,
                'title' => 'Kupas Tuntas: RUU Perampasan Aset dan Efek Jeranya bagi Koruptor',
                'imageUrl' => '',
                'mediaUrl' => 'https://www.youtube.com/watch?v=ScMzIvxBSi4',
                'category' => 'YouTube',
                'publishedAt' => '2026-10-16',
                'order' => 3,
                'status' => 'terbit',
                'is_popular' => true,
                'author' => 'Dewan Pakar Hukum ISF',
                'summary' => 'Telaah kritis terhadap instrumen perampasan aset non-conviction based forfeiture dan penguatan transparansi sistem peradilan.',
                'content' => '',
            ],
            [
                'id' => 4,
                'title' => 'Penjelasan Singkat: Dinamika Anggaran Pendidikan dan Gaji Pendidik 2026',
                'imageUrl' => '',
                'mediaUrl' => 'https://www.tiktok.com/@indonesiastrategyforum/video/7200000000000000001',
                'category' => 'TikTok',
                'publishedAt' => '2026-10-15',
                'order' => 4,
                'status' => 'terbit',
                'is_popular' => true,
                'author' => 'Divisi Kebijakan Publik ISF',
                'summary' => 'Sorotan visual cepat mengenai alokasi mandatori 20% APBN dan efektivitas belanja transfer daerah di sektor pendidikan.',
                'content' => '',
            ],
        ];
    }

    /**
     * Menghasilkan naskah artikel pendamping standar dengan format ala EU Reporter Roundtable.
     */
    private function generateDefaultCompanionArticle(array $item): string
    {
        $title = htmlspecialchars($item['title'] ?? 'Diskusi Meja Bundar');

        return <<<HTML
<div class="space-y-6 text-neutral-800 leading-relaxed font-serif text-[17px]">
    <p class="text-[18px] sm:text-[19px] font-semibold italic text-neutral-900 border-l-4 border-isf-red pl-4 py-1.5 bg-neutral-50/80">
        Menjelang akhir tahun dan di tengah dinamika kebijakan strategis yang berkembang pesat, diskursus kepatuhan dan tata kelola regulasi kembali menjadi sorotan utama. Forum meja bundar (roundtable) ini mempertemukan para regulator, pembuat kebijakan, analis independen, dan pimpinan industri untuk membedah tantangan nyata implementasi di lapangan. Simak pandangan kritis para pakar, akademisi, dan pemangku kebijakan berikut:
    </p>

    <div class="space-y-2 pt-2">
        <p class="font-sans font-bold text-neutral-900 text-base">
            Dr. Hendrik Setiawan — Peneliti Senior Hukum & Tata Kelola Kebijakan
        </p>
        <p>
            "Regulasi yang efektif bermula dari kepastian prinsip hukum yang mendasarinya. Penghindaran atau kompromi berlebihan terhadap transparansi hanya akan menciptakan celah ketidakpastian bagi iklim investasi jangka panjang."
        </p>
    </div>

    <div class="space-y-2 pt-2">
        <p class="font-sans font-bold text-neutral-900 text-base">
            Prof. Diana Kusuma — Pakar Ekonomi Terapan & Hubungan Kelembagaan
        </p>
        <p>
            "Tidak ada pembuat kebijakan yang boleh mengabaikan preseden sejarah dan dampak sistemik regulasi. Sebaliknya, reformasi tata kelola harus menjadi momentum penguatan daya saing dan kepatuhan yang berkeadilan."
        </p>
    </div>

    <blockquote class="my-6 border-l-4 border-isf-red pl-5 py-2.5 italic text-neutral-800 bg-neutral-50/60 font-serif text-[18px]">
        "Kepatuhan regulasi tidak boleh dipandang sekadar beban kepatuhan administratif di hilir, melainkan pilar utama ketahanan strategis dan nilai reputasi institusi di hadapan publik dan pemangku kepentingan internasional."
    </blockquote>

    <div class="space-y-2 pt-2">
        <p class="font-sans font-bold text-neutral-900 text-base">
            Ahmad Fauzi, M.Sc. — Pengamat Regulasi Strategis & Advokasi Industri
        </p>
        <p>
            "Refleksi mendalam terhadap dinamika sektoral membuktikan bahwa dialog terbuka dan kolaborasi lintas sektor adalah satu-satunya jalan untuk memastikan kepatuhan industri serta keberlanjutan roda perekonomian nasional."
        </p>
    </div>

    <p class="pt-4 text-neutral-700">
        Indonesia Strategy Forum memandang pentingnya pembentukan forum dialog berkala antara regulator dan perwakilan industri agar interpretasi klausul aturan tidak menimbulkan multi-tafsir penegakan di lapangan. Liputan lengkap dan tayangan video analisis di atas disajikan sebagai bahan rujukan strategis bagi penyusunan arah kebijakan kelembagaan Anda.
    </p>
</div>
HTML;
    }

    private function notFound(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 404);
    }
}
