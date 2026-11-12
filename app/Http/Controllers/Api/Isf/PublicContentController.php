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
                'sections' => $this->getBerandaSections($request, $activeMedia),
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


    /**
     * Data struktur Full-Layer Sections (Politik, Hukum, Kebijakan, Video) ala EU Reporter.
     */
    private function getBerandaSections(Request $request, array $activeMedia): array
    {
        // 1. Politik
        $politikArticles = Article::query()->terbit()
            ->where('category', 'politik')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $politikLead = null;
        $politikItems = [];

        if ($politikArticles->isNotEmpty()) {
            $first = $politikArticles->shift();
            $politikLead = [
                'id' => (string) $first->id,
                'title' => $first->title,
                'kicker' => 'ANALISIS POLITIK',
                'category' => 'Politik',
                'summary' => $first->summary ?: 'Telaah peta koalisi parlemen dan dinamika lobi antar-fraksi terkait prioritas legislasi nasional.',
                'publishedAt' => $first->published_at ? $first->published_at->format('Y-m-d') : date('Y-m-d'),
                'author' => [
                    'name' => $first->author_name ?: 'Tim Riset Politik ISF',
                    'role' => $first->author_role ?: 'Analis Parlemen',
                ],
                'readingMinutes' => $first->reading_minutes ?: 5,
                'imageUrl' => $first->image_url ?: 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?q=80&w=1200&auto=format&fit=crop',
                'to' => $first->link_url ?: '/beranda',
            ];

            foreach ($politikArticles as $art) {
                $politikItems[] = [
                    'id' => (string) $art->id,
                    'title' => $art->title,
                    'kicker' => 'PARLEMEN',
                    'publishedAt' => $art->published_at ? $art->published_at->format('Y-m-d') : date('Y-m-d'),
                    'summary' => $art->summary ?: '',
                    'imageUrl' => $art->image_url ?: '',
                    'to' => $art->link_url ?: '/beranda',
                ];
            }
        }

        if (!$politikLead) {
            $politikLead = [
                'id' => 'pol-1',
                'title' => 'Konsolidasi Parlemen: Peta Koalisi Pembahasan Agenda Legislasi Prioritas 2026',
                'kicker' => 'ANALISIS POLITIK',
                'category' => 'Politik',
                'summary' => 'Dinamika lobi antar-fraksi di Senayan mengindikasikan pergeseran prioritas regulasi strategis. Simak telaah peta kekuatan koalisi dan implikasinya terhadap stabilitas kebijakan nasional.',
                'publishedAt' => date('Y-m-d'),
                'author' => [
                    'name' => 'Tim Riset Politik ISF',
                    'role' => 'Analis Parlemen & Tata Kelola',
                ],
                'readingMinutes' => 5,
                'imageUrl' => 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?q=80&w=1200&auto=format&fit=crop',
                'to' => '/beranda',
            ];
        }

        if (count($politikItems) < 3) {
            $defaultPolitik = [
                [
                    'id' => 'pol-sub-1',
                    'title' => 'Penyusunan RUU Prioritas Komisi II: Uji Publik & Evaluasi Sistem Pemilu',
                    'kicker' => 'KOMISI II DPR',
                    'publishedAt' => date('Y-m-d', strtotime('-1 day')),
                    'summary' => 'Harmonisasi draf revisi regulasi kepemiluan mulai memasuki tahap dengar pendapat bersama pakar tata negara.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?q=80&w=400&auto=format&fit=crop',
                    'to' => '/beranda',
                ],
                [
                    'id' => 'pol-sub-2',
                    'title' => 'Dinamika Badan Anggaran: Harmonisasi Alokasi Dana Alokasi Khusus (DAK)',
                    'kicker' => 'BADAN ANGGARAN',
                    'publishedAt' => date('Y-m-d', strtotime('-2 days')),
                    'summary' => 'Pemerintah dan Banggar sepakati formula pengawasan realisasi belanja transfer ke daerah berbasis kinerja.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1450133064473-71024230f91b?q=80&w=400&auto=format&fit=crop',
                    'to' => '/beranda',
                ],
                [
                    'id' => 'pol-sub-3',
                    'title' => 'Studi Komparatif Tata Kelola Parlemen Regional: Catatan untuk Reformasi Birokrasi',
                    'kicker' => 'KAJIAN REGIONAL',
                    'publishedAt' => date('Y-m-d', strtotime('-3 days')),
                    'summary' => 'Benchmarking efektivitas fungsi pengawasan legislatif di tingkat regional untuk meningkatkan akuntabilitas publik.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=400&auto=format&fit=crop',
                    'to' => '/beranda',
                ],
            ];
            foreach ($defaultPolitik as $def) {
                if (count($politikItems) < 3) {
                    $politikItems[] = $def;
                }
            }
        }

        // 2. Hukum (4-Column Ruled Grid ala EU Reporter)
        $hukumArticles = Article::query()->terbit()
            ->whereIn('category', ['hukum', 'ruu'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(4)
            ->get();

        $hukumItems = [];
        foreach ($hukumArticles as $art) {
            $hukumItems[] = [
                'id' => (string) $art->id,
                'title' => $art->title,
                'kicker' => strtoupper($art->category ?: 'Hukum'),
                'publishedAt' => $art->published_at ? $art->published_at->format('Y-m-d') : date('Y-m-d'),
                'summary' => $art->summary ?: 'Ulasan mendalam aspek yuridis, kepatuhan tata kelola, dan konsekuensi hukum bagi pemangku kepentingan.',
                'imageUrl' => $art->image_url ?: '',
                'to' => $art->link_url ?: '/beranda',
            ];
        }

        if (count($hukumItems) < 4) {
            $defaultHukum = [
                [
                    'id' => 'huk-1',
                    'title' => 'Pembaruan KUHP Nasional: Standardisasi Penilaian Risiko Pidana Korporasi',
                    'kicker' => 'PIDANA KORPORASI',
                    'publishedAt' => date('Y-m-d'),
                    'summary' => 'Pedoman operasional kepatuhan bagi organ perseroan guna memitigasi risiko delik korporasi pasca berlakunya KUHP baru.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?q=80&w=800&auto=format&fit=crop',
                    'to' => '/beranda',
                ],
                [
                    'id' => 'huk-2',
                    'title' => 'Yurisprudensi Mahkamah Agung tentang Batasan Tanggung Jawab Direksi',
                    'kicker' => 'YURISPRUDENSI',
                    'publishedAt' => date('Y-m-d', strtotime('-1 day')),
                    'summary' => 'Analisis doktrin Business Judgment Rule dalam putusan perdata mutakhir yang memperkuat perlindungan itikad baik direksi.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1505664194779-8beaceb93744?q=80&w=800&auto=format&fit=crop',
                    'to' => '/beranda',
                ],
                [
                    'id' => 'huk-3',
                    'title' => 'Pelacakan RUU Perampasan Aset: Harmonisasi Mekanisme Non-Conviction',
                    'kicker' => 'LEGISLASI TRACKER',
                    'publishedAt' => date('Y-m-d', strtotime('-2 days')),
                    'summary' => 'Mekanisme non-conviction based asset forfeiture ditinjau dari prinsip perlindungan hak kepemilikan dan hukum acara peradilan.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1453728013993-6d66e9c9123a?q=80&w=800&auto=format&fit=crop',
                    'to' => '/beranda',
                ],
                [
                    'id' => 'huk-4',
                    'title' => 'Kepatuhan Regulasi ESG & Audit Lingkungan: Kewajiban Pelaporan Emiten 2026',
                    'kicker' => 'KEPATUHAN TATA KELOLA',
                    'publishedAt' => date('Y-m-d', strtotime('-3 days')),
                    'summary' => 'Regulator pertegas sanksi administratif bagi keterlambatan pengungkapan jejak karbon dan dampak sosial operasional industri.',
                    'imageUrl' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=800&auto=format&fit=crop',
                    'to' => '/beranda',
                ],
            ];
            foreach ($defaultHukum as $def) {
                if (count($hukumItems) < 4) {
                    $hukumItems[] = $def;
                }
            }
        }

        // 3. Kebijakan (3-Column Asymmetric Analytical Grid)
        $kebijakanLead = [
            'id' => 'kbj-lead-1',
            'title' => 'Kebijakan Fiskal Hijau: Skema Insentif Pajak & Transisi Energi Bersih 2026',
            'kicker' => 'ANALISIS FISKAL',
            'category' => 'Kebijakan',
            'summary' => 'Pemerintah mematangkan peta jalan insentif perpajakan bagi industri pelopor energi baru terbarukan sembari mempersiapkan pemberlakuan tarif pajak karbon secara bertahap.',
            'imageUrl' => 'https://images.unsplash.com/photo-1473341304170-971dccb5ac1e?q=80&w=1000&auto=format&fit=crop',
            'publishedAt' => date('Y-m-d'),
            'author' => 'Dr. Hendra Wijaya, Dewan Pakar Fiskal ISF',
            'to' => '/beranda',
        ];

        $kebijakanImpact = [
            'title' => 'Pemetaan Dampak Regulasi Moneter & Suku Bunga Sektoral',
            'kicker' => 'DAMPAK REGULASI',
            'publishedAt' => date('Y-m-d', strtotime('-1 day')),
            'keyPoints' => [
                'Likuiditas Perbankan: Ketahanan rasio kecukupan modal menghadapi volatilitas pasar global.',
                'Sektor Riil & Manufaktur: Efisiensi beban pinjaman modal kerja industri berorientasi ekspor.',
                'Iklim Investasi Domestik: Dampak stabilisasi nilai tukar terhadap arus penanaman modal asing.',
            ],
            'to' => '/beranda',
        ];

        $kebijakanBrief = [
            'title' => 'Rekomendasi Kebijakan: Akselerasi Deregulasi Perizinan Berbasis Risiko',
            'kicker' => 'POLICY BRIEF',
            'publishedAt' => date('Y-m-d', strtotime('-2 days')),
            'author' => 'Prof. Siti Rahmawati',
            'authorRole' => 'Ketua Tim Kajian Deregulasi ISF',
            'quote' => 'Kepastian regulasi dan simplifikasi birokrasi digital adalah pilar fundamental untuk mendongkrak daya saing investasi Indonesia.',
            'to' => '/beranda',
        ];

        // 4. Video (Multimedia Hub)
        $videoFeatured = null;
        $videoPlaylist = [];

        if (!empty($activeMedia)) {
            $firstMedia = $activeMedia[0];
            $videoFeatured = [
                'id' => $firstMedia['id'] ?? 1,
                'title' => $firstMedia['title'] ?? 'Diskusi Panel: Transformasi Digital dan Kepatuhan Regulasi',
                'summary' => $firstMedia['summary'] ?? 'Kajian mendalam pakar regulator dan praktisi terkait kepatuhan tata kelola digital.',
                'category' => 'Diskusi Strategis',
                'duration' => '18:45',
                'author' => $firstMedia['author'] ?? 'Dewan Pakar ISF',
                'publishedAt' => $firstMedia['publishedAt'] ?? date('Y-m-d'),
                'mediaUrl' => $firstMedia['mediaUrl'] ?? '',
                'imageUrl' => !empty($firstMedia['imageUrl']) ? $firstMedia['imageUrl'] : 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?q=80&w=1200&auto=format&fit=crop',
                'to' => '/media/' . ($firstMedia['id'] ?? 1),
            ];

            $otherMedia = array_slice($activeMedia, 1, 3);
            foreach ($otherMedia as $idx => $m) {
                $videoPlaylist[] = [
                    'id' => $m['id'] ?? ($idx + 2),
                    'title' => $m['title'] ?? 'Sorotan Kilat Media Analisis',
                    'category' => $m['category'] ?? 'Video Analisis',
                    'duration' => $idx === 0 ? '08:20' : ($idx === 1 ? '12:15' : '05:40'),
                    'author' => $m['author'] ?? 'Redaksi ISF',
                    'publishedAt' => $m['publishedAt'] ?? date('Y-m-d'),
                    'imageUrl' => !empty($m['imageUrl']) ? $m['imageUrl'] : 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=600&auto=format&fit=crop',
                    'to' => '/media/' . ($m['id'] ?? ($idx + 2)),
                ];
            }
        }

        if (!$videoFeatured) {
            $videoFeatured = [
                'id' => 1,
                'title' => 'Diskusi Panel: Transformasi Digital dan Kepatuhan Regulasi Perbankan',
                'summary' => 'Kajian mendalam regulator dan praktisi perbankan terkait kepatuhan tata kelola AI, perlindungan privasi data nasabah, dan kesiapan sistem pembayaran nasional.',
                'category' => 'Diskusi Strategis',
                'duration' => '18:45',
                'author' => 'Dewan Pakar Ekonomi & Regulasi ISF',
                'publishedAt' => date('Y-m-d'),
                'mediaUrl' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'imageUrl' => 'https://images.unsplash.com/photo-1517048676732-d65bc937f952?q=80&w=1200&auto=format&fit=crop',
                'to' => '/media/1',
            ];
        }

        if (empty($videoPlaylist)) {
            $videoPlaylist = [
                [
                    'id' => 2,
                    'title' => 'Sorotan Kilat: Implikasi Regulasi Transisi Energi Terbarukan bagi Industri',
                    'category' => 'Video Analisis',
                    'duration' => '08:20',
                    'author' => 'Divisi Riset ISF',
                    'publishedAt' => date('Y-m-d', strtotime('-2 days')),
                    'imageUrl' => 'https://images.unsplash.com/photo-1497435334941-8c899ee9e8e9?q=80&w=600&auto=format&fit=crop',
                    'to' => '/media/2',
                ],
                [
                    'id' => 3,
                    'title' => 'Kupas Tuntas: RUU Perampasan Aset dan Efek Jeranya bagi Koruptor',
                    'category' => 'Wawancara Eksklusif',
                    'duration' => '14:10',
                    'author' => 'Dewan Pakar Hukum ISF',
                    'publishedAt' => date('Y-m-d', strtotime('-4 days')),
                    'imageUrl' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=600&auto=format&fit=crop',
                    'to' => '/media/3',
                ],
                [
                    'id' => 4,
                    'title' => 'Briefing Redaksi: Tinjauan Ketahanan Moneter & Kebijakan Likuiditas',
                    'category' => 'Editorial Briefing',
                    'duration' => '06:45',
                    'author' => 'Redaksi ISF',
                    'publishedAt' => date('Y-m-d', strtotime('-5 days')),
                    'imageUrl' => 'https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?q=80&w=600&auto=format&fit=crop',
                    'to' => '/media/4',
                ],
            ];
        }

        return [
            'politik' => [
                'lead' => $politikLead,
                'items' => $politikItems,
            ],
            'hukum' => [
                'items' => $hukumItems,
            ],
            'kebijakan' => [
                'lead' => $kebijakanLead,
                'impact' => $kebijakanImpact,
                'brief' => $kebijakanBrief,
            ],
            'video' => [
                'featured' => $videoFeatured,
                'playlist' => $videoPlaylist,
            ],
        ];
    }

    private function notFound(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 404);
    }
}
